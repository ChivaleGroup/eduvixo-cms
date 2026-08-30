<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$website = '/var/www/clients/client9/web123/web';
$sites = [
    ['name' => 'demo', 'root' => '/var/www/clients/client9/web121/web', 'active_theme' => 'eduvixo'],
    ['name' => 'shoudu', 'root' => '/var/www/clients/client59/web119/web', 'active_theme' => 'shoudu'],
];
$failures = [];
$report = ['routes' => ['checked' => 0], 'website' => [], 'systems' => [], 'services' => []];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$request = static function (string $url, bool $follow = true): array {
    $body = '';
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Eduvixo-Production-Audit/1.0',
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
            if (strlen($body) < 2_000_000) $body .= substr($chunk, 0, 2_000_000 - strlen($body));
            return strlen($chunk);
        },
    ]);
    $ok = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    return ['ok' => $ok !== false, 'status' => $status, 'body' => $body, 'error' => $error];
};
$loadConfig = static function (string $root): array {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    return (static fn(string $path): array => require $path)($root . '/config/app.php');
};

$routes = ['', 'product/', 'services/', 'marketplace/', 'support/', 'support/docs/', 'support/faq/', 'support/knowledge-base/', 'updates/', 'contact/', 'privacy/', 'terms/'];
foreach (['de', 'en', 'lo', 'pl', 'th', 'vi', 'zh'] as $locale) {
    foreach ($routes as $route) {
        $path = '/' . $locale . '/' . $route;
        $response = $request('https://www.eduvixo.com' . $path);
        $validLanguage = preg_match('/<html\s+lang=["\']' . preg_quote($locale, '/') . '["\']/i', $response['body']) === 1;
        $assert($response['ok'] && $response['status'] === 200, 'Website route failed: ' . $path . ' HTTP ' . $response['status']);
        $assert($validLanguage, 'Website locale marker is invalid: ' . $path);
        $assert(str_contains($response['body'], '</html>'), 'Website route returned an incomplete document: ' . $path);
        $report['routes']['checked']++;
    }
}

$home = $request('https://www.eduvixo.com/en/');
$product = $request('https://www.eduvixo.com/en/product/');
$services = $request('https://www.eduvixo.com/en/services/');
$assert(!str_contains($home['body'], 'Hosting provided by'), 'Public website still exposes the hosting credit.');
$assert(str_contains($home['body'], 'A clear product ecosystem'), 'Homepage ecosystem preview is missing.');
$assert(str_contains($product['body'], 'Eduvixo CMS') && str_contains($product['body'], 'Premium extensions'), 'Product ecosystem presentation is incomplete.');
$assert(str_contains($services['body'], 'Transparent commercial scope'), 'Services commercial scope is missing.');
$assert(is_file($website . '/storage/marketplace/core-release.json'), 'Core release manifest is missing.');
$core = json_decode((string) file_get_contents($website . '/storage/marketplace/core-release.json'), true, 32, JSON_THROW_ON_ERROR);
$envelope = json_decode((string) file_get_contents($website . '/storage/marketplace/official-catalog.json'), true, 16, JSON_THROW_ON_ERROR);
$payload = base64_decode((string) ($envelope['signed_payload'] ?? ''), true);
$signature = base64_decode((string) ($envelope['signature'] ?? ''), true);
$publicKey = base64_decode('q+WweIoNkskiUOzyLl80Bc9V2TkBdHXXrtOufSRIg54=', true);
$assert(is_string($payload) && is_string($signature) && is_string($publicKey) && sodium_crypto_sign_verify_detached($signature, $payload, $publicKey), 'Official catalog signature verification failed.');
$catalog = is_string($payload) ? json_decode($payload, true, 64, JSON_THROW_ON_ERROR) : [];
$products = (array) ($catalog['products'] ?? []);
$installable = array_values(array_filter($products, static fn(array $item): bool => !empty($item['installable'])));
$theme = array_values(array_filter($products, static fn(array $item): bool => ($item['type'] ?? '') === 'theme' && ($item['slug'] ?? '') === 'eduvixo'));
$assert(($core['version'] ?? '') === '1.0.5', 'Official core release is not 1.0.5.');
$assert(count($products) === 13, 'Official catalog does not contain 13 products.');
$assert(count($installable) === 10, 'Official catalog does not contain 10 installable extensions.');
$assert(($theme[0]['version'] ?? '') === '1.1.7', 'Official Eduvixo Theme release is not 1.1.7.');
$report['website'] = ['hosting_credit' => false, 'core' => $core['version'] ?? null, 'catalog_products' => count($products), 'installable' => count($installable), 'theme' => $theme[0]['version'] ?? null];

$coreBlocked = $request('https://www.eduvixo.com/api/marketplace/v1/core-package/', false);
$assert(in_array($coreBlocked['status'], [401, 403], true), 'Core package endpoint is not protected: HTTP ' . $coreBlocked['status']);
$themeUrl = (string) ($theme[0]['package_url'] ?? '');
if ($themeUrl !== '') {
    $themeBlocked = $request($themeUrl, false);
    $assert(in_array($themeBlocked['status'], [401, 403], true), 'Theme package endpoint is not protected: HTTP ' . $themeBlocked['status']);
    $report['website']['package_protection'] = ['core' => $coreBlocked['status'], 'theme' => $themeBlocked['status']];
} else {
    $failures[] = 'Official Eduvixo Theme package URL is missing.';
}

foreach ($sites as $site) {
    $root = $site['root'];
    spl_autoload_register(static function (string $class) use ($root): void {
        if (!str_starts_with($class, 'App\\')) return;
        $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require_once $file;
    });
    $config = $loadConfig($root);
    $db = (new App\Core\Database($config['database']))->connection();
    $release = json_decode((string) file_get_contents($root . '/app/release.json'), true, 16, JSON_THROW_ON_ERROR);
    $themeManifest = json_decode((string) file_get_contents($root . '/themes/eduvixo/theme.json'), true, 32, JSON_THROW_ON_ERROR);
    $setting = static function (PDO $db, string $key, mixed $default): mixed {
        $statement = $db->prepare('SELECT value FROM settings WHERE `key`=? LIMIT 1');
        $statement->execute([$key]);
        $raw = $statement->fetchColumn();
        return $raw === false ? $default : json_decode((string) $raw, true, 32, JSON_THROW_ON_ERROR);
    };
    $activeTheme = (string) $setting($db, 'active_theme', 'eduvixo');
    $hostingCredit = (bool) $setting($db, 'show_hosting_credit', true);
    $publisher = $db->query("SELECT key_id,name,fingerprint,active FROM extension_publishers WHERE key_id='chivale-eduvixo-2026' LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    $packageStatement = $db->prepare("SELECT version,signature_status,last_error FROM extension_packages WHERE type='theme' AND slug='eduvixo' LIMIT 1");
    $packageStatement->execute();
    $package = $packageStatement->fetch(PDO::FETCH_ASSOC) ?: [];
    $license = 'valid';
    try { (new App\Core\LicenseService($config['license'], $config['engine_version']))->enforce($config['base_url']); }
    catch (Throwable $error) { $license = 'invalid'; }
    $assert(($release['version'] ?? '') === '1.0.5', $site['name'] . ' core is not 1.0.5.');
    $assert(($themeManifest['version'] ?? '') === '1.1.7', $site['name'] . ' Eduvixo Theme is not 1.1.7.');
    $assert($activeTheme === $site['active_theme'], $site['name'] . ' active theme changed unexpectedly.');
    $assert($hostingCredit, $site['name'] . ' hosting credit default is not enabled.');
    $assert($license === 'valid', $site['name'] . ' license enforcement failed.');
    $assert(!is_file($root . '/storage/system-updates/maintenance.json'), $site['name'] . ' remains in maintenance mode.');
    $assert(($publisher['active'] ?? 0) == 1 && ($publisher['name'] ?? '') === 'QUANT Software House', $site['name'] . ' official publisher trust is invalid.');
    $assert(($package['version'] ?? '') === '1.1.7' && ($package['signature_status'] ?? '') === 'verified', $site['name'] . ' theme package record is invalid.');
    $login = $request(rtrim((string) $config['base_url'], '/') . '/license', false);
    $assert(in_array($login['status'], [301, 302, 303, 307, 308, 401, 403], true), $site['name'] . ' License page is not access-controlled: HTTP ' . $login['status']);
    $report['systems'][$site['name']] = ['core' => $release['version'] ?? null, 'theme_package' => $themeManifest['version'] ?? null, 'active_theme' => $activeTheme, 'hosting_credit' => $hostingCredit, 'license' => $license, 'publisher' => $publisher['key_id'] ?? null, 'signature' => $package['signature_status'] ?? null, 'maintenance' => false, 'license_page' => $login['status']];
}

$demo = $request('https://demo.eduvixo.com/en/', true);
$assert($demo['status'] === 200, 'Demo public page is unavailable: HTTP ' . $demo['status']);
$assert(str_contains($demo['body'], 'Hosting provided by Chivale.'), 'Demo Eduvixo Theme does not render the enabled hosting credit.');

foreach (['apache2', 'php8.3-fpm'] as $service) {
    exec('systemctl is-active ' . escapeshellarg($service) . ' 2>&1', $output, $status);
    $active = $status === 0 && trim((string) end($output)) === 'active';
    $assert($active, $service . ' is not active.');
    $report['services'][$service] = $active ? 'active' : 'inactive';
    $output = [];
}

$report['ok'] = $failures === [];
$report['failures'] = $failures;
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
exit($failures === [] ? 0 : 2);
