<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$website = '/var/www/clients/client9/web123/web';
$sites = [
    ['demo', '/var/www/clients/client9/web121/web', 'demo.eduvixo.com'],
    ['shoudu', '/var/www/clients/client59/web119/web', 'shoudu.lrn.asia'],
];
$count = 0;
$assert = static function (bool $pass, string $label) use (&$count): void {
    if (!$pass) throw new RuntimeException('FAIL ' . $label);
    $count++;
    echo 'PASS ' . $label . PHP_EOL;
};
$request = static function (string $url, array $headers = []): array {
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'Eduvixo-Marketplace-System-Audit/1.0']);
    $body = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $error = curl_error($curl); curl_close($curl);
    if ($body === false) throw new RuntimeException('HTTP audit failed: ' . $error);
    return [$status, $body];
};

require $sites[0][1] . '/app/Core/OfficialCatalog.php';
[$status, $raw] = $request('https://www.eduvixo.com/api/marketplace/v1/official/');
$assert($status === 200, 'official catalog endpoint');
$catalog = App\Core\OfficialCatalog::verify($raw);
$installable = array_values(array_filter($catalog['products'], static fn(array $product): bool => !empty($product['installable'])));
$assert(($catalog['core']['version'] ?? '') === '1.0.4' && count($catalog['products']) === 13 && count($installable) === 10, 'signed 13-product catalog and 10 system-installable components');
$identities = array_map(static fn(array $product): string => $product['type'] . ':' . $product['slug'], $installable);
$assert(count($identities) === count(array_unique($identities)), 'installable catalog identities are unique');
$assert(count(array_filter($installable, static fn(array $product): bool => (bool) preg_match('/^[a-f0-9]{32}$/D', (string) ($product['id'] ?? '')))) === 10, 'all installable package identifiers use the 32-character contract');
[$status] = $request('https://www.eduvixo.com/api/marketplace/v1/package/?id=' . $installable[0]['id']);
$assert($status === 401, 'package distribution rejects unauthenticated access');
[$status] = $request('https://www.eduvixo.com/storage/marketplace/packages/eduvixo-core-1.0.4.zip');
$assert($status === 403, 'direct core package path is denied');

foreach ($sites as [$name, $root, $host]) {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    $config = (static fn(string $path): array => require $path)($root . '/config/app.php');
    spl_autoload_register(static function (string $class) use ($root): void { if (str_starts_with($class, 'App\\')) { $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php'; if (is_file($file)) require_once $file; } });
    $db = (new App\Core\Database($config['database']))->connection();
    $release = json_decode((string) file_get_contents($root . '/app/release.json'), true, 8, JSON_THROW_ON_ERROR);
    $assert(($release['version'] ?? '') === '1.0.4', $name . ' core 1.0.4');
    $state = (new App\Core\SystemUpdate($db, $root, $config))->status();
    $assert(!$state['available'] && ($state['job']['status'] ?? '') === 'completed' && empty($state['error']), $name . ' update state healthy');
    $siteInstallable = array_values(array_filter($state['products'], static fn(array $product): bool => !empty($product['installable'])));
    $assert(count($siteInstallable) === 10, $name . ' sees 10 installable official components');
    $assert(!is_file($root . '/storage/system-updates/maintenance.json') && is_file($root . '/storage/system-updates/' . $state['job']['recovery'] . '/files.zip') && is_file($root . '/storage/system-updates/' . $state['job']['recovery'] . '/database.sql.gz'), $name . ' recovery artifacts and maintenance state');
    [$loginStatus, $login] = $request('https://' . $host . '/login');
    $assert($loginStatus === 200 && str_contains($login, 'name="captcha"'), $name . ' protected login availability');
    [$marketplaceStatus] = $request('https://' . $host . '/marketplace');
    $assert($marketplaceStatus === 303, $name . ' Marketplace requires authentication');
    foreach (['/app/Core/PackageManager.php', '/storage/system-updates/catalog.json'] as $private) {
        [$privateStatus] = $request('https://' . $host . $private);
        $assert($privateStatus === 403, $name . ' private path denied: ' . $private);
    }
    $source = (string) file_get_contents($root . '/app/Views/console-marketplace.php');
    $javascript = (string) file_get_contents($root . '/public/theme/eduvixo-marketplace.js');
    $assert(str_contains($source, 'data-marketplace-filter="price"') && str_contains($source, 'data-marketplace-license') && !str_contains($source, 'console-official-marketplace'), $name . ' canonical Marketplace view without duplicate legacy section');
    $assert(str_contains($javascript, 'data-marketplace-install') && str_contains($javascript, 'data-marketplace-uninstall') && str_contains($javascript, 'is-configure') && !str_contains($javascript, 'Download'), $name . ' lifecycle action contract');
}

echo 'Assertions: ' . $count . PHP_EOL;
