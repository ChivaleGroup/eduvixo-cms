<?php

declare(strict_types=1);

namespace Eduvixo\Website;

use Closure;
use RuntimeException;

final class WhatsAppOnboardingService
{
    private const GRAPH_VERSION = 'v26.0';
    private readonly string $key;
    private readonly Closure $http;

    public function __construct(
        private readonly array $config,
        private readonly array $license,
        private readonly string $baseUrl,
        string $secret,
        ?Closure $http = null
    ) {
        if (!preg_match('/^[a-f0-9]{64}$/D', $secret)) throw new RuntimeException('WhatsApp onboarding encryption is unavailable.');
        $this->key = hex2bin($secret) ?: throw new RuntimeException('WhatsApp onboarding encryption is unavailable.');
        $this->http = $http ?? $this->curl(...);
    }

    public function ready(): bool
    {
        return preg_match('/^[0-9]{5,30}$/D', (string) ($this->config['app_id'] ?? '')) === 1
            && preg_match('/^[A-Za-z0-9_-]{10,200}$/D', (string) ($this->config['config_id'] ?? '')) === 1
            && strlen((string) ($this->config['app_secret'] ?? '')) >= 20
            && preg_match('/^[A-Za-z0-9_-]{20,200}$/D', (string) ($this->config['webhook_token'] ?? '')) === 1;
    }

    public function app(): array
    {
        if (!$this->ready()) throw new RuntimeException('WhatsApp onboarding is awaiting Meta configuration.', 503);
        return ['app_id' => (string) $this->config['app_id'], 'config_id' => (string) $this->config['config_id'], 'graph_version' => self::GRAPH_VERSION];
    }

    public function start(array $server): array
    {
        if (!$this->ready()) throw new RuntimeException('WhatsApp onboarding is awaiting Meta approval and configuration.', 503);
        $domain = $this->authenticate($server);
        $this->rate('start', $domain, 10, 3600);
        $token = $this->token();
        $this->write($this->path('starts', $token), ['domain' => $domain, 'expires' => time() + 600]);
        return ['url' => $this->baseUrl . '/system/notifications/whatsapp?state=' . rawurlencode($token), 'expires_in' => 600];
    }

    public function begin(string $token): array
    {
        $record = $this->consume('starts', $token);
        return ['domain' => $this->domain((string) ($record['domain'] ?? '')), 'expires' => min((int) ($record['expires'] ?? 0), time() + 600)];
    }

    public function complete(array $session, array $input): array
    {
        if (!$this->ready()) throw new RuntimeException('WhatsApp onboarding is unavailable.', 503);
        if ((int) ($session['expires'] ?? 0) < time()) throw new RuntimeException('The onboarding session has expired.', 410);
        $domain = $this->domain((string) ($session['domain'] ?? ''));
        $code = trim((string) ($input['code'] ?? ''));
        $event = (string) ($input['event'] ?? '');
        $data = is_array($input['data'] ?? null) ? $input['data'] : [];
        $waba = (string) ($data['waba_id'] ?? '');
        $phone = (string) ($data['phone_number_id'] ?? '');
        $business = (string) ($data['business_id'] ?? '');
        if ($event !== 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING' || !preg_match('/^[0-9]{5,30}$/D', $waba) || !preg_match('/^[0-9]{5,30}$/D', $phone) || ($business !== '' && !preg_match('/^[0-9]{5,30}$/D', $business))) throw new RuntimeException('Meta did not complete WhatsApp Business App onboarding.', 422);
        if (strlen($code) < 20 || strlen($code) > 4096 || preg_match('/[^A-Za-z0-9._-]/', $code)) throw new RuntimeException('Meta returned an invalid authorization code.', 422);
        $token = $this->exchange($code);
        $this->graph('POST', $waba . '/subscribed_apps', $token);
        $status = $this->graph('GET', $phone . '?fields=is_on_biz_app,platform_type', $token);
        if (($status['is_on_biz_app'] ?? null) !== true || (string) ($status['platform_type'] ?? '') !== 'CLOUD_API') throw new RuntimeException('Meta did not activate WhatsApp Business App coexistence.', 422);
        $claim = $this->token();
        $payload = json_encode(['access_token' => $token, 'phone_number_id' => $phone, 'waba_id' => $waba, 'business_id' => $business, 'api_version' => self::GRAPH_VERSION], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->write($this->path('claims', $claim), ['domain' => $domain, 'expires' => time() + 300, 'result' => $this->seal($payload)]);
        return ['return_url' => $domain . '/system/notifications/whatsapp?claim=' . rawurlencode($claim)];
    }

    public function claim(array $server, string $token): array
    {
        $domain = $this->authenticate($server);
        $this->rate('claim', $domain, 20, 3600);
        $record = $this->consume('claims', $token);
        if (!hash_equals((string) ($record['domain'] ?? ''), $domain)) throw new RuntimeException('The onboarding result belongs to another installation.', 403);
        $result = json_decode($this->open((string) ($record['result'] ?? '')), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($result)) throw new RuntimeException('The onboarding result is invalid.', 422);
        return $result;
    }

    public function verifyWebhook(string $mode, string $token, string $challenge): string
    {
        if ($mode !== 'subscribe' || !hash_equals((string) ($this->config['webhook_token'] ?? ''), $token) || !preg_match('/^[A-Za-z0-9._-]{1,512}$/D', $challenge)) throw new RuntimeException('Webhook verification failed.', 403);
        return $challenge;
    }

    public function acceptWebhook(string $body, string $signature): void
    {
        if (strlen($body) > 1048576 || !preg_match('/^sha256=([a-f0-9]{64})$/D', strtolower($signature), $match)) throw new RuntimeException('Webhook signature is invalid.', 403);
        $expected = hash_hmac('sha256', $body, (string) ($this->config['app_secret'] ?? ''));
        if (!hash_equals($expected, $match[1])) throw new RuntimeException('Webhook signature is invalid.', 403);
        try { $payload = json_decode($body, true, 64, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new RuntimeException('Webhook payload is invalid.', 422); }
        if (!is_array($payload) || ($payload['object'] ?? '') !== 'whatsapp_business_account' || !is_array($payload['entry'] ?? null)) throw new RuntimeException('Webhook payload is invalid.', 422);
    }

    private function authenticate(array $server): string
    {
        $header = (string) ($server['HTTP_AUTHORIZATION'] ?? $server['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!preg_match('/^Bearer ([A-Za-z0-9._-]{1,128})$/D', $header, $match)) throw new RuntimeException('Installation authorization is required.', 401);
        $license = $match[1];
        $domain = $this->domain((string) ($server['HTTP_X_EDUVIXO_DOMAIN'] ?? ''));
        $cache = $this->path('license-cache', hash_hmac('sha256', $license . "\0" . $domain, $this->key));
        $cached = is_file($cache) ? json_decode((string) @file_get_contents($cache), true) : null;
        if (is_array($cached) && (int) ($cached['expires'] ?? 0) >= time()) return $domain;
        $version = preg_match('/^[0-9A-Za-z.-]{1,30}$/D', (string) ($server['HTTP_X_EDUVIXO_VERSION'] ?? ''), $versionMatch) ? $versionMatch[0] : '1.0';
        $body = http_build_query(['type' => 'software', 'LicenseKey' => $license, 'DomainUrl' => $domain, 'ProductName' => 'Eduvixo', 'ProductModel' => 'Education Digital Experience & Communication Platform', 'ProductVersion' => $version], '', '&', PHP_QUERY_RFC3986);
        [$status, $response] = ($this->http)('POST', (string) $this->license['endpoint'], ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'], $body);
        $data = json_decode($response, true);
        if ($status < 200 || $status >= 300 || !is_array($data) || !empty($data['error']) || empty($data['data'])) throw new RuntimeException('Installation license authorization failed.', 403);
        $this->write($cache, ['expires' => time() + 300], false);
        return $domain;
    }

    private function exchange(string $code): string
    {
        $url = 'https://graph.facebook.com/' . self::GRAPH_VERSION . '/oauth/access_token?' . http_build_query(['client_id' => (string) $this->config['app_id'], 'client_secret' => (string) $this->config['app_secret'], 'code' => $code], '', '&', PHP_QUERY_RFC3986);
        [$status, $body] = ($this->http)('GET', $url, ['Accept: application/json'], null);
        $data = json_decode($body, true);
        $token = is_array($data) ? (string) ($data['access_token'] ?? '') : trim($body);
        if ($status < 200 || $status >= 300 || strlen($token) < 20 || strlen($token) > 4096 || preg_match('/[\r\n]/', $token)) throw new RuntimeException('Meta token exchange failed.', 502);
        return $token;
    }

    private function graph(string $method, string $path, string $token): array
    {
        [$status, $body] = ($this->http)($method, 'https://graph.facebook.com/' . self::GRAPH_VERSION . '/' . $path, ['Authorization: Bearer ' . $token, 'Accept: application/json'], $method === 'POST' ? '' : null);
        $data = json_decode($body, true);
        if ($status < 200 || $status >= 300 || !is_array($data) || isset($data['error'])) throw new RuntimeException('Meta rejected WhatsApp onboarding.', 502);
        return $data;
    }

    private function curl(string $method, string $url, array $headers, ?string $body): array
    {
        $curl = curl_init($url);
        if ($curl === false) throw new RuntimeException('Secure HTTP transport is unavailable.', 503);
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'Eduvixo-WhatsApp-Onboarding/1.0'];
        if ($method === 'POST') { $options[CURLOPT_POST] = true; $options[CURLOPT_POSTFIELDS] = $body ?? ''; }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl); $error = curl_error($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
        if ($error !== '' || !is_string($response)) throw new RuntimeException('External authorization service is unavailable.', 503);
        return [$status, $response];
    }

    private function domain(string $value): string
    {
        $value = rtrim(trim($value), '/'); $parts = parse_url($value);
        if (!filter_var($value, FILTER_VALIDATE_URL) || !is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host']) || array_intersect(['user', 'pass', 'query', 'fragment'], array_keys($parts))) throw new RuntimeException('Installation identity is invalid.', 401);
        return $value;
    }

    private function token(): string { return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='); }

    private function path(string $type, string $token): string
    {
        if (!preg_match('/^[a-z-]{3,40}$/D', $type) || !preg_match('/^[A-Za-z0-9_-]{32,64}$/D', $token)) throw new RuntimeException('Onboarding token is invalid.', 422);
        $directory = $this->config['storage'] . '/' . $type;
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) throw new RuntimeException('Onboarding storage is unavailable.', 503);
        return $directory . '/' . hash_hmac('sha256', $token, $this->key) . '.json';
    }

    private function write(string $path, array $payload, bool $exclusive = true): void
    {
        $handle = @fopen($path, $exclusive ? 'x' : 'c');
        if (!is_resource($handle)) throw new RuntimeException('Onboarding state could not be stored.', 503);
        try { $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES); if (!flock($handle, LOCK_EX) || (!$exclusive && !ftruncate($handle, 0)) || fwrite($handle, $json) !== strlen($json) || !fflush($handle)) throw new RuntimeException('Onboarding state could not be stored.', 503); }
        finally { fclose($handle); }
        @chmod($path, 0640);
    }

    private function consume(string $type, string $token): array
    {
        $path = $this->path($type, $token); $used = $path . '.used-' . bin2hex(random_bytes(6));
        if (!is_file($path) || !@rename($path, $used)) throw new RuntimeException('Onboarding token is invalid or already used.', 410);
        try { $record = json_decode((string) file_get_contents($used), true, 16, JSON_THROW_ON_ERROR); }
        finally { @unlink($used); }
        if (!is_array($record) || (int) ($record['expires'] ?? 0) < time()) throw new RuntimeException('Onboarding token has expired.', 410);
        return $record;
    }

    private function seal(string $value): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return rtrim(strtr(base64_encode($nonce . sodium_crypto_secretbox($value, $nonce, $this->key)), '+/', '-_'), '=');
    }

    private function open(string $value): string
    {
        $encoded = strtr($value, '-_', '+/'); $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        $raw = base64_decode($encoded, true);
        if (!is_string($raw) || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) throw new RuntimeException('Onboarding result is invalid.', 422);
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $this->key);
        if (!is_string($plain)) throw new RuntimeException('Onboarding result cannot be decrypted.', 422);
        return $plain;
    }

    private function rate(string $scope, string $subject, int $limit, int $window): void
    {
        $path = $this->path('rate-' . $scope, hash_hmac('sha256', $subject, $this->key)); $handle = @fopen($path, 'c+');
        if (!is_resource($handle) || !flock($handle, LOCK_EX)) { if (is_resource($handle)) fclose($handle); throw new RuntimeException('Rate-limit protection is unavailable.', 503); }
        try { $now = time(); $entries = json_decode((string) stream_get_contents($handle), true); $entries = array_values(array_filter(array_map('intval', is_array($entries) ? $entries : []), static fn(int $at): bool => $at > $now - $window)); if (count($entries) >= $limit) throw new RuntimeException('Too many onboarding requests.', 429); $entries[] = $now; rewind($handle); ftruncate($handle, 0); fwrite($handle, json_encode($entries, JSON_THROW_ON_ERROR)); fflush($handle); }
        finally { flock($handle, LOCK_UN); fclose($handle); }
        @chmod($path, 0640);
    }
}
