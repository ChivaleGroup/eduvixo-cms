<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$archive = '/root/eduvixo-deploy/product-updates-theme-1.1.8.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$sites = [
    ['name' => 'demo', 'root' => '/var/www/clients/client9/web121/web', 'owner' => 'web121', 'group' => 'client9', 'active_theme' => 'eduvixo'],
    ['name' => 'shoudu', 'root' => '/var/www/clients/client59/web119/web', 'owner' => 'web119', 'group' => 'client59', 'active_theme' => 'shoudu'],
];
$websiteFiles = [
    'app/views/pages/updates.php', 'app/views/partials/ecosystem.php', 'config/marketplace.php',
    'lang/de.json', 'lang/en.json', 'lang/lo.json', 'lang/pl.json', 'lang/th.json', 'lang/vi.json', 'lang/zh.json',
    'resources/pages.css', 'public/assets/css/site.min.css', 'public/sitemap.xml', 'storage/marketplace/official-catalog.json',
    'storage/marketplace/packages/eduvixo-theme-1.1.8.zip',
];
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/product-updates-theme-1.1.8-' . $stamp;
$backup = '/root/eduvixo-backups/product-updates-theme-1.1.8-pre-' . $stamp;

$run = static function (array $command, ?string $output = null): void {
    $spec = [0 => ['pipe', 'r'], 1 => $output ? ['file', $output, 'wb'] : STDOUT, 2 => STDERR];
    $process = proc_open($command, $spec, $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: ' . implode(' ', $command));
};
$copy = static function (string $source, string $target, string $owner, string $group, int $mode = 0640): void {
    if (!is_file($source)) throw new RuntimeException('Missing staged file: ' . $source);
    $parent = dirname($target);
    if (!is_dir($parent) && (!mkdir($parent, 0750, true) || !chmod($parent, 0750) || !chown($parent, $owner) || !chgrp($parent, $group))) throw new RuntimeException('Cannot secure target directory: ' . $parent);
    $temporary = $target . '.eduvixo-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, $mode) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) throw new RuntimeException('Atomic publish failed: ' . $target);
};
$load = static function (string $root): array {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    return (static fn(string $path): array => require $path)($root . '/config/app.php');
};

if (!is_file($archive) || !mkdir($stage, 0700, true) || !mkdir($backup, 0700, true)) throw new RuntimeException('Private deployment workspace is unavailable.');
$run(['tar', '-xzf', $archive, '-C', $stage]);
foreach (array_merge($websiteFiles, ['.wrk/theme-update-bootstrap.php', '.cms/source/app/Core/OfficialCatalog.php']) as $file) if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete release archive: ' . $file);
foreach (array_filter($websiteFiles, static fn(string $file): bool => str_ends_with($file, '.php')) as $file) $run(['php', '-l', $stage . '/' . $file]);
$run(['php', '-l', $stage . '/.wrk/theme-update-bootstrap.php']);
require $stage . '/.cms/source/app/Core/OfficialCatalog.php';
$catalog = App\Core\OfficialCatalog::verify((string) file_get_contents($stage . '/storage/marketplace/official-catalog.json'));
$theme = array_values(array_filter((array) ($catalog['products'] ?? []), static fn(array $product): bool => ($product['slug'] ?? '') === 'eduvixo' && ($product['type'] ?? '') === 'theme'))[0] ?? [];
$package = $stage . '/storage/marketplace/packages/eduvixo-theme-1.1.8.zip';
if (count((array) ($catalog['products'] ?? [])) !== 13 || ($theme['version'] ?? '') !== '1.1.8' || !hash_equals((string) ($theme['package_checksum'] ?? ''), hash_file('sha256', $package))) throw new RuntimeException('Signed release verification failed.');

$existingWebsite = array_values(array_filter($websiteFiles, static fn(string $file): bool => is_file($website . '/' . $file)));
$run(array_merge(['tar', '-czf', $backup . '/website-files.tar.gz', '-C', $website], $existingWebsite));
foreach ($sites as $site) {
    $config = $load($site['root']);
    $database = (string) ($config['database']['name'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $database)) throw new RuntimeException('Unsafe database identity for ' . $site['name']);
    $run(['mariadb-dump', '--socket=/run/mysqld/mysqld.sock', '--user=root', '--single-transaction', '--routines', '--triggers', '--databases', $database], $backup . '/' . $site['name'] . '.sql');
    $run(['tar', '-czf', $backup . '/' . $site['name'] . '-eduvixo-theme.tar.gz', '-C', $site['root'], 'themes/eduvixo']);
}
file_put_contents($backup . '/ROLLBACK.txt', "Restore website-files.tar.gz over the product website. Restore each site theme archive over its CMS root and restore the matching SQL dump only if package metadata also needs rollback. Preserve all writes made after this backup. Reapply documented owner/group values, then verify PHP syntax, active theme, package signature, license enforcement and public pages.\n", LOCK_EX);
foreach (glob($backup . '/*') ?: [] as $file) {
    chmod($file, 0600);
    if (filesize($file) < 100) throw new RuntimeException('Incomplete backup: ' . $file);
    echo 'BACKUP ' . basename($file) . ' ' . filesize($file) . ' ' . hash_file('sha256', $file) . PHP_EOL;
}

foreach ($websiteFiles as $file) $copy($stage . '/' . $file, $website . '/' . $file, 'web123', 'client9');
foreach ($sites as $site) {
    $sitePackage = $site['root'] . '/storage/eduvixo-theme-1.1.8.zip';
    $bootstrap = $site['root'] . '/storage/theme-update-bootstrap-1.1.8.php';
    $copy($package, $sitePackage, $site['owner'], $site['group'], 0600);
    $copy($stage . '/.wrk/theme-update-bootstrap.php', $bootstrap, $site['owner'], $site['group'], 0600);
    try {
        $run(['runuser', '-u', $site['owner'], '--', 'php', $bootstrap, $site['root'], $sitePackage, '1.1.8']);
    } finally {
        if (is_file($bootstrap)) unlink($bootstrap);
        if (is_file($sitePackage)) unlink($sitePackage);
    }
}

foreach ($sites as $site) {
    $run(['php', '-l', $site['root'] . '/themes/eduvixo/views/page.php']);
    $manifest = json_decode((string) file_get_contents($site['root'] . '/themes/eduvixo/theme.json'), true, 32, JSON_THROW_ON_ERROR);
    $page = (string) file_get_contents($site['root'] . '/themes/eduvixo/views/page.php');
    if (($manifest['version'] ?? '') !== '1.1.8' || !str_contains($page, '>Hosting provided by Chivale</a>') || str_contains($page, 'Hosting provided by Chivale.') || str_contains($page, '—')) throw new RuntimeException($site['name'] . ' theme content verification failed.');
    if (is_file($site['root'] . '/storage/system-updates/maintenance.json')) throw new RuntimeException($site['name'] . ' remained in maintenance mode.');
    $config = $load($site['root']);
    $db = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=' . $config['database']['name'] . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $query = $db->prepare('SELECT value FROM settings WHERE `key` = ?');
    $query->execute(['active_theme']);
    $activeTheme = json_decode((string) $query->fetchColumn(), true);
    if ($activeTheme !== $site['active_theme']) throw new RuntimeException($site['name'] . ' active theme changed unexpectedly.');
}

echo json_encode(['ok' => true, 'theme' => '1.1.8', 'backup' => $backup, 'stage' => $stage, 'sites' => array_column($sites, 'name')], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
