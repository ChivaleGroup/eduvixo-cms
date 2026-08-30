<?php

declare(strict_types=1);

namespace Eduvixo\Website;

final class MarketplaceService
{
    public function __construct(private readonly array $config, private readonly string $baseUrl, private readonly string $secret) {}

    public function publicItems(string $ip): array
    {
        $licenseState = $this->browserLicenseState($ip);
        $items = [];
        foreach ($this->config['packages'] as $id => $package) {
            $variants = [];
            foreach ((array) ($package['variants'] ?? []) as $key => $variant) {
                if (!is_array($variant)) continue;
                $variants[] = [
                    'key' => (string) $key, 'label_key' => (string) ($variant['label_key'] ?? ''),
                    'size' => $this->size((int) ($variant['size'] ?? 0)), 'recommended' => (bool) ($variant['recommended'] ?? false),
                ];
            }
            $items[] = [
                'id' => $id, 'type' => $package['type'], 'name' => $package['name'], 'version' => $package['version'],
                'size' => $variants ? '' : $this->size((int) $package['size']), 'enabled' => (bool) $package['browser_enabled'],
                'licensed' => (bool) ($package['license_download_enabled'] ?? false), 'locked' => $licenseState['locked'],
                'icon' => $package['icon'], 'copy_key' => $package['copy_key'], 'variants' => $variants,
                'meta_keys' => (array) ($package['meta_keys'] ?? []), 'note_key' => (string) ($package['note_key'] ?? ''),
                'card_class' => preg_match('/^[a-z0-9-]{1,60}$/D', (string) ($package['card_class'] ?? '')) ? (string) $package['card_class'] : '',
            ];
        }
        return $items;
    }

    public function issueBrowserToken(string $id, string $variant, string $ip, string $userAgent): string
    {
        $package = $this->package($id, 'browser_enabled', $variant);
        $this->assertPackage($package);
        $this->rate('browser', $ip, 10, 3600, 3);
        return $this->createBrowserToken($id, 'browser_enabled', $ip, $userAgent, $variant);
    }

    public function issueLicensedBrowserToken(string $id, string $licenseKey, string $ip, string $userAgent): array
    {
        $package = $this->package($id, 'license_download_enabled');
        $state = $this->browserLicenseState($ip);
        if ($state['locked']) return ['ok' => false, 'locked' => true, 'remaining' => 0, 'retry_after' => $state['retry_after']];
        $licenseKey = trim($licenseKey);
        $maximum = (int) $this->config['license_key_max_length'];
        $validFormat = $licenseKey !== '' && strlen($licenseKey) <= $maximum && preg_match('/^[A-Za-z0-9._-]+$/D', $licenseKey) === 1;
        if (!$validFormat || !$this->validateBrowserLicense($licenseKey, $package)) {
            $state = $this->recordLicenseFailure($ip);
            return ['ok' => false, 'locked' => $state['locked'], 'remaining' => max(0, (int) $this->config['license_failure_limit'] - $state['attempts']), 'retry_after' => $state['retry_after']];
        }
        $this->clearLicenseFailures($ip);
        $this->assertPackage($package);
        $this->rate('browser', $ip, 10, 3600, 3);
        return ['ok' => true, 'download_url' => $this->createBrowserToken($id, 'license_download_enabled', $ip, $userAgent)];
    }

    public function streamBrowser(string $token, string $ip, string $userAgent): never
    {
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/D', $token)) $this->fail('Download not found.', 404);
        $path = $this->tokenPath($token, false);
        $used = $path . '.used-' . bin2hex(random_bytes(6));
        if (!is_file($path) || !@rename($path, $used)) $this->fail('Download not found or already used.', 404);
        try { $payload = json_decode((string) file_get_contents($used), true, 8, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { @unlink($used); $this->fail('Download token is invalid.', 404); }
        @unlink($used);
        if ((int) ($payload['expires'] ?? 0) < time() || !hash_equals((string) ($payload['ip'] ?? ''), $this->fingerprint($ip)) || !hash_equals((string) ($payload['ua'] ?? ''), $this->fingerprint($userAgent))) $this->fail('Download token has expired.', 410);
        $capability = in_array(($payload['capability'] ?? ''), ['browser_enabled', 'license_download_enabled'], true) ? (string) $payload['capability'] : 'browser_enabled';
        $package = $this->package((string) ($payload['package'] ?? ''), $capability, (string) ($payload['variant'] ?? ''));
        $this->stream($package);
    }

    public function updaterCatalog(string $type, string $slug): array
    {
        $this->authenticateUpdater();
        $releases = [];
        foreach ($this->config['packages'] as $id => $package) {
            if (!$package['update_enabled'] || $package['type'] !== $type || $package['slug'] !== $slug) continue;
            $this->assertPackage($package);
            $releases[] = [
                'type' => $package['type'], 'slug' => $package['slug'], 'version' => $package['version'], 'release_channel' => 'stable',
                'package_url' => $this->baseUrl . '/api/marketplace/v1/package/?id=' . rawurlencode($id), 'package_checksum' => $package['checksum'],
            ];
        }
        return ['schema' => 1, 'releases' => $releases];
    }

    public function streamUpdater(string $id): never
    {
        $this->authenticateUpdater();
        $this->stream($this->package($id, 'update_enabled'));
    }

    private function authenticateUpdater(): void
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!preg_match('/^Bearer ([A-Za-z0-9._-]{1,128})$/D', $header, $match)) $this->jsonError('Authorization required.', 401);
        $license = $match[1];
        $domain = trim((string) ($_SERVER['HTTP_X_EDUVIXO_DOMAIN'] ?? ''));
        if (!$this->httpsUrl($domain)) $this->jsonError('Installation identity is invalid.', 401);
        $this->rate('updater', $this->clientIp() . "\0" . hash_hmac('sha256', $license, $this->secret), 120, 3600, 0);
        $cache = $this->directory('license-cache') . '/' . hash_hmac('sha256', $license . "\0" . $domain, $this->secret) . '.json';
        $cached = is_file($cache) ? json_decode((string) @file_get_contents($cache), true) : null;
        if (is_array($cached) && (int) ($cached['expires'] ?? 0) >= time()) return;
        $version = preg_match('/^[0-9A-Za-z.-]{1,30}$/D', (string) ($_SERVER['HTTP_X_EDUVIXO_VERSION'] ?? ''), $versionMatch) ? $versionMatch[0] : '1.0.0';
        $payload = http_build_query(['type' => 'software', 'LicenseKey' => $license, 'DomainUrl' => $domain, 'ProductName' => 'Eduvixo', 'ProductModel' => 'Education Digital Experience & Communication Platform', 'ProductVersion' => $version], '', '&', PHP_QUERY_RFC3986);
        $curl = curl_init((string) $this->config['license_endpoint']);
        if ($curl === false) $this->jsonError('Authorization service is unavailable.', 503);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'], CURLOPT_USERAGENT => 'Eduvixo-Marketplace/1.0', CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS]);
        $response = curl_exec($curl); $error = curl_error($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
        $data = is_string($response) ? json_decode($response, true) : null;
        if ($error !== '' || $status < 200 || $status >= 300 || !is_array($data) || !empty($data['error']) || empty($data['data'])) $this->jsonError('License authorization failed.', 403);
        @file_put_contents($cache, json_encode(['expires' => time() + (int) $this->config['license_cache_ttl']], JSON_THROW_ON_ERROR), LOCK_EX);
        @chmod($cache, 0640);
    }

    private function stream(array $package): never
    {
        $path = $this->assertPackage($package);
        $defaultName = preg_replace('/[^a-z0-9.-]/', '-', strtolower($package['slug'] . '-' . $package['version'])) . '.zip';
        $name = basename((string) ($package['download_name'] ?? $defaultName));
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,159}$/D', $name)) $name = $defaultName;
        $contentType = (string) ($package['content_type'] ?? 'application/zip');
        if (!preg_match('~^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$~Di', $contentType)) $contentType = 'application/octet-stream';
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        @set_time_limit(0);
        while (ob_get_level() > 0) @ob_end_clean();
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        readfile($path);
        exit;
    }

    private function createBrowserToken(string $id, string $capability, string $ip, string $userAgent, string $variant = ''): string
    {
        $this->cleanupTokens();
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $payload = ['package' => $id, 'variant' => $variant, 'capability' => $capability, 'expires' => time() + (int) $this->config['token_ttl'], 'ip' => $this->fingerprint($ip), 'ua' => $this->fingerprint($userAgent)];
        $path = $this->tokenPath($token);
        $handle = @fopen($path, 'x');
        if (!$handle) throw new \RuntimeException('Download token could not be created.', 503);
        try {
            if (!flock($handle, LOCK_EX) || fwrite($handle, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)) === false) throw new \RuntimeException('Download token could not be saved.', 503);
        } finally { fclose($handle); }
        @chmod($path, 0640);
        return '/download/file/?token=' . rawurlencode($token);
    }

    private function validateBrowserLicense(string $licenseKey, array $package): bool
    {
        $payload = http_build_query([
            'type' => 'software', 'LicenseKey' => $licenseKey, 'DomainUrl' => $this->baseUrl,
            'ProductName' => (string) ($package['license_product_name'] ?? $this->config['license_product_name']),
            'ProductModel' => (string) ($package['license_product_model'] ?? $this->config['license_product_model']),
            'ProductVersion' => (string) ($package['license_product_version'] ?? $this->config['license_product_version']),
        ], '', '&', PHP_QUERY_RFC3986);
        $curl = curl_init((string) $this->config['license_endpoint']);
        if ($curl === false) throw new \RuntimeException('License service is unavailable.', 503);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'], CURLOPT_USERAGENT => 'Eduvixo-Marketplace/1.0', CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS]);
        $response = curl_exec($curl); $error = curl_error($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
        $data = is_string($response) ? json_decode($response, true) : null;
        if ($error !== '' || $status >= 500 || $status < 200 || $status >= 300 || !is_array($data)) throw new \RuntimeException('License service is unavailable.', 503);
        return empty($data['error']) && !empty($data['data']);
    }

    private function browserLicenseState(string $ip): array
    {
        $path = $this->licenseAttemptPath($ip);
        if (!is_file($path)) return ['attempts' => 0, 'locked' => false, 'retry_after' => 0];
        $data = json_decode((string) @file_get_contents($path), true);
        return $this->normalizeLicenseState(is_array($data) ? $data : []);
    }

    private function recordLicenseFailure(string $ip): array
    {
        $path = $this->licenseAttemptPath($ip);
        $handle = @fopen($path, 'c+');
        if (!$handle || !flock($handle, LOCK_EX)) { if (is_resource($handle)) fclose($handle); throw new \RuntimeException('License protection is unavailable.', 503); }
        try {
            $contents = stream_get_contents($handle); $data = json_decode(is_string($contents) ? $contents : '', true);
            $state = $this->normalizeLicenseState(is_array($data) ? $data : []); $now = time();
            if (!$state['locked']) {
                $started = (int) ($state['window_started'] ?? 0);
                if ($started === 0 || $started < $now - (int) $this->config['license_failure_window']) $state = ['attempts' => 0, 'window_started' => $now, 'locked_until' => 0, 'locked' => false, 'retry_after' => 0];
                $state['attempts']++;
                if ($state['attempts'] >= (int) $this->config['license_failure_limit']) $state['locked_until'] = $now + (int) $this->config['license_lock_ttl'];
                $state = $this->normalizeLicenseState($state);
                rewind($handle); ftruncate($handle, 0); fwrite($handle, json_encode(['attempts' => $state['attempts'], 'window_started' => $state['window_started'], 'locked_until' => $state['locked_until']], JSON_THROW_ON_ERROR)); fflush($handle);
            }
        } finally { flock($handle, LOCK_UN); fclose($handle); }
        @chmod($path, 0640);
        return $state;
    }

    private function normalizeLicenseState(array $data): array
    {
        $now = time(); $started = max(0, (int) ($data['window_started'] ?? 0)); $lockedUntil = max(0, (int) ($data['locked_until'] ?? 0)); $attempts = max(0, min((int) $this->config['license_failure_limit'], (int) ($data['attempts'] ?? 0)));
        if ($lockedUntil <= $now && $started < $now - (int) $this->config['license_failure_window']) return ['attempts' => 0, 'window_started' => 0, 'locked_until' => 0, 'locked' => false, 'retry_after' => 0];
        return ['attempts' => $attempts, 'window_started' => $started, 'locked_until' => $lockedUntil, 'locked' => $lockedUntil > $now, 'retry_after' => max(0, $lockedUntil - $now)];
    }

    private function clearLicenseFailures(string $ip): void { $path = $this->licenseAttemptPath($ip); if (is_file($path)) @unlink($path); }
    private function licenseAttemptPath(string $ip): string { return $this->directory('license-attempts') . '/' . hash_hmac('sha256', $ip, $this->secret) . '.json'; }

    private function assertPackage(array $package): string
    {
        $root = realpath((string) $this->config['package_root']);
        $path = realpath((string) ($package['file'] ?? ''));
        if (!$root || !$path || !str_starts_with(str_replace('\\', '/', $path), rtrim(str_replace('\\', '/', $root), '/') . '/') || !is_file($path)) throw new \RuntimeException('Package is unavailable.', 503);
        if (filesize($path) !== (int) $package['size'] || !hash_equals((string) $package['checksum'], hash_file('sha256', $path))) throw new \RuntimeException('Package integrity verification failed.', 503);
        return $path;
    }

    private function package(string $id, string $capability, string $variant = ''): array
    {
        $package = $this->config['packages'][$id] ?? null;
        if (!is_array($package) || empty($package[$capability])) throw new \RuntimeException('Package is unavailable.', 404);
        $variants = (array) ($package['variants'] ?? []);
        if ($variants) {
            if ($variant === '' || !isset($variants[$variant]) || !is_array($variants[$variant])) throw new \RuntimeException('Package variant is unavailable.', 404);
            $package = array_replace($package, $variants[$variant]);
            unset($package['variants']);
        } elseif ($variant !== '') throw new \RuntimeException('Package variant is unavailable.', 404);
        return $package;
    }

    private function rate(string $scope, string $subject, int $limit, int $window, int $minimum): void
    {
        $path = $this->directory('rate-' . $scope) . '/' . hash_hmac('sha256', $subject, $this->secret) . '.json';
        $now = time(); $data = is_file($path) ? json_decode((string) @file_get_contents($path), true) : [];
        $entries = array_values(array_filter(array_map('intval', is_array($data) ? $data : []), static fn(int $time): bool => $time > $now - $window));
        if (count($entries) >= $limit || ($minimum > 0 && $entries && max($entries) > $now - $minimum)) throw new \RuntimeException('Too many download requests.', 429);
        $entries[] = $now; @file_put_contents($path, json_encode($entries, JSON_THROW_ON_ERROR), LOCK_EX); @chmod($path, 0640);
    }

    private function cleanupTokens(): void
    {
        $files = glob($this->directory('tokens') . '/*') ?: []; $now = time(); $checked = 0;
        foreach ($files as $file) { if (++$checked > 30) break; if (is_file($file) && filemtime($file) < $now - 900) @unlink($file); }
    }

    private function tokenPath(string $token, bool $createDirectory = true): string
    {
        $directory = $createDirectory ? $this->directory('tokens') : $this->config['package_root'] . '/../tokens';
        return rtrim($directory, '/\\') . '/' . hash_hmac('sha256', $token, $this->secret) . '.json';
    }

    private function directory(string $name): string
    {
        $path = dirname((string) $this->config['package_root']) . '/' . $name;
        if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) throw new \RuntimeException('Marketplace storage is unavailable.', 503);
        return $path;
    }

    private function fingerprint(string $value): string { return hash_hmac('sha256', $value, $this->secret); }
    private function clientIp(): string { return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'); }
    private function httpsUrl(string $url): bool { return filter_var($url, FILTER_VALIDATE_URL) !== false && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' && parse_url($url, PHP_URL_USER) === null && parse_url($url, PHP_URL_PASS) === null; }
    private function size(int $bytes): string { return $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024) . ' KB'; }
    private function jsonError(string $message, int $status): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); echo json_encode(['error' => true, 'message' => $message], JSON_THROW_ON_ERROR); exit; }
    private function fail(string $message, int $status): never { http_response_code($status); header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow, noarchive'); echo $message; exit; }
}
