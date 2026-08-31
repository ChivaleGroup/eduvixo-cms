<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$archive = '/root/eduvixo-deploy/wcag-2.2-release.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$sites = [
    ['name' => 'demo', 'root' => '/var/www/clients/client9/web121/web', 'owner' => 'web121', 'group' => 'client9', 'active_theme' => 'eduvixo', 'themes' => [['eduvixo-theme-1.1.9.zip', '1.1.9', 'eduvixo']]],
    ['name' => 'shoudu', 'root' => '/var/www/clients/client59/web119/web', 'owner' => 'web119', 'group' => 'client59', 'active_theme' => 'shoudu', 'themes' => [['eduvixo-theme-1.1.9.zip', '1.1.9', 'eduvixo'], ['shoudu-theme-1.1.2.zip', '1.1.2', 'shoudu']]],
];
$websiteFiles = [
    'app/views/layout.php', 'config/marketplace.php',
    'lang/de.json', 'lang/en.json', 'lang/lo.json', 'lang/pl.json', 'lang/th.json', 'lang/vi.json', 'lang/zh.json',
    'resources/accessibility.css', 'resources/accessibility.js', 'resources/text-zoom.css',
    'public/assets/css/site.min.css', 'public/assets/js/site.min.js', 'public/assets/icons.svg',
    'storage/marketplace/core-release.json', 'storage/marketplace/official-catalog.json',
    'storage/marketplace/packages/eduvixo-core-1.0.6.zip',
    'storage/marketplace/packages/eduvixo-theme-1.1.9.zip',
    'storage/marketplace/packages/shoudu-theme-1.1.2.zip',
];
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/wcag-2.2-' . $stamp;
$backup = '/root/eduvixo-backups/wcag-2.2-pre-' . $stamp;

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
    if (!is_dir($parent) && (!mkdir($parent, 0750, true) || !chown($parent, $owner) || !chgrp($parent, $group))) throw new RuntimeException('Cannot create target directory: ' . $parent);
    $temporary = $target . '.wcag-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, $mode) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) throw new RuntimeException('Atomic publish failed: ' . $target);
};
$load = static function (string $root): array {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    return (static fn(string $path): array => require $path)($root . '/config/app.php');
};
$clearCms = static function (): void {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
};

if (!is_file($archive) || !mkdir($stage, 0700, true) || !mkdir($backup, 0700, true)) throw new RuntimeException('Private deployment workspace is unavailable.');
$run(['tar', '-xzf', $archive, '-C', $stage]);
foreach (array_merge($websiteFiles, ['.wrk/system-update-bootstrap.php', '.wrk/theme-update-bootstrap.php', '.cms/source/app/Core/OfficialCatalog.php']) as $file) if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete release archive: ' . $file);
foreach (['app/views/layout.php', 'config/marketplace.php', '.wrk/system-update-bootstrap.php', '.wrk/theme-update-bootstrap.php'] as $file) $run(['php', '-l', $stage . '/' . $file]);
foreach (['resources/accessibility.js', '.cms/source/public/theme/eduvixo-shell.js', '.cms/source/themes/eduvixo/assets/eduvixo-accessibility.js', '.wrk/shoudu-wcag-source/shoudu/assets/js/shoudu-accessibility.js'] as $file) $run(['node', '--check', $stage . '/' . $file]);

require $stage . '/.cms/source/app/Core/OfficialCatalog.php';
$catalog = App\Core\OfficialCatalog::verify((string) file_get_contents($stage . '/storage/marketplace/official-catalog.json'));
if (($catalog['core']['version'] ?? '') !== '1.0.6' || count((array) ($catalog['products'] ?? [])) !== 13) throw new RuntimeException('Signed catalog identity is invalid.');
foreach ([
    ['slug' => 'eduvixo', 'version' => '1.1.9', 'file' => 'eduvixo-theme-1.1.9.zip'],
    ['slug' => 'shoudu', 'version' => '1.1.2', 'file' => 'shoudu-theme-1.1.2.zip'],
] as $expected) {
    $product = array_values(array_filter((array) $catalog['products'], static fn(array $item): bool => ($item['slug'] ?? '') === $expected['slug']))[0] ?? [];
    $package = $stage . '/storage/marketplace/packages/' . $expected['file'];
    if (($product['version'] ?? '') !== $expected['version'] || !hash_equals((string) ($product['package_checksum'] ?? ''), hash_file('sha256', $package))) throw new RuntimeException('Signed theme release mismatch: ' . $expected['slug']);
}
if (!hash_equals((string) $catalog['core']['checksum'], hash_file('sha256', $stage . '/storage/marketplace/packages/eduvixo-core-1.0.6.zip'))) throw new RuntimeException('Core release checksum mismatch.');

$existingWebsite = array_values(array_filter($websiteFiles, static fn(string $file): bool => is_file($website . '/' . $file)));
$run(array_merge(['tar', '-czf', $backup . '/website-files.tar.gz', '-C', $website], $existingWebsite));
foreach ($sites as $site) {
    $config = $load($site['root']);
    $database = (string) ($config['database']['name'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $database)) throw new RuntimeException('Unsafe database identity for ' . $site['name']);
    $run(['mariadb-dump', '--socket=/run/mysqld/mysqld.sock', '--user=root', '--single-transaction', '--routines', '--triggers', '--databases', $database], $backup . '/' . $site['name'] . '.sql');
    $paths = array_values(array_filter(['app/release.json', 'app/Views/console.php', 'app/Views/login.php', 'public/theme/eduvixo-shell.js', 'public/theme/eduvixo-accessibility.css', 'public/theme/eduvixo-text-zoom.css', 'themes/eduvixo', 'themes/shoudu'], static fn(string $path): bool => file_exists($site['root'] . '/' . $path)));
    $run(array_merge(['tar', '-czf', $backup . '/' . $site['name'] . '-files.tar.gz', '-C', $site['root']], $paths));
}
file_put_contents($backup . '/ROLLBACK.txt', "Preserve all writes made after this backup. Restore website-files.tar.gz over web123 and remove only the three newly introduced accessibility source files if a complete website rollback is required. Prefer each CMS updater/package recovery archive for core or theme rollback. Use the targeted site files archive only with the matching SQL dump when package metadata must also be restored; a SQL restore discards later writes. Reapply documented owners and verify syntax, login, active theme, license enforcement and public pages before removing maintenance.\n", LOCK_EX);
foreach (glob($backup . '/*') ?: [] as $file) {
    chmod($file, 0600);
    if (filesize($file) < 100) throw new RuntimeException('Incomplete backup: ' . $file);
    echo 'BACKUP ' . basename($file) . ' ' . filesize($file) . ' ' . hash_file('sha256', $file) . PHP_EOL;
}

foreach ($websiteFiles as $file) $copy($stage . '/' . $file, $website . '/' . $file, 'web123', 'client9');

foreach ($sites as $site) {
    $bootstrap = $site['root'] . '/storage/system-update-bootstrap-1.0.6.php';
    $copy($stage . '/.wrk/system-update-bootstrap.php', $bootstrap, $site['owner'], $site['group'], 0600);
    try {
        $clearCms();
        $run(['runuser', '-u', $site['owner'], '--', 'php', $bootstrap, $site['root'], '1.0.6']);
    } finally {
        if (is_file($bootstrap)) unlink($bootstrap);
    }
}

foreach ($sites as $site) {
    foreach ($site['themes'] as [$file, $version, $slug]) {
        $package = $site['root'] . '/storage/' . $file;
        $bootstrap = $site['root'] . '/storage/theme-update-bootstrap-' . $version . '.php';
        $copy($stage . '/storage/marketplace/packages/' . $file, $package, $site['owner'], $site['group'], 0600);
        $copy($stage . '/.wrk/theme-update-bootstrap.php', $bootstrap, $site['owner'], $site['group'], 0600);
        try {
            $clearCms();
            $run(['runuser', '-u', $site['owner'], '--', 'php', $bootstrap, $site['root'], $package, $version, $slug]);
        } finally {
            if (is_file($bootstrap)) unlink($bootstrap);
            if (is_file($package)) unlink($package);
        }
    }
}

foreach ($sites as $site) {
    foreach (['app/Views/console.php', 'app/Views/login.php', 'themes/eduvixo/views/page.php', 'themes/shoudu/views/page.php'] as $file) if (is_file($site['root'] . '/' . $file)) $run(['php', '-l', $site['root'] . '/' . $file]);
    $release = json_decode((string) file_get_contents($site['root'] . '/app/release.json'), true, 16, JSON_THROW_ON_ERROR);
    $eduvixo = json_decode((string) file_get_contents($site['root'] . '/themes/eduvixo/theme.json'), true, 16, JSON_THROW_ON_ERROR);
    $shouduFile = $site['root'] . '/themes/shoudu/theme.json';
    $shoudu = is_file($shouduFile) ? json_decode((string) file_get_contents($shouduFile), true, 16, JSON_THROW_ON_ERROR) : null;
    if (($release['version'] ?? '') !== '1.0.6' || ($eduvixo['version'] ?? '') !== '1.1.9' || ($site['name'] === 'shoudu' && ($shoudu['version'] ?? '') !== '1.1.2')) throw new RuntimeException($site['name'] . ' release versions are inconsistent.');
    if (is_file($site['root'] . '/storage/system-updates/maintenance.json')) throw new RuntimeException($site['name'] . ' remained in maintenance mode.');
    $config = $load($site['root']);
    $db = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=' . $config['database']['name'] . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $query = $db->prepare('SELECT value FROM settings WHERE `key` = ?');
    $query->execute(['active_theme']);
    if (json_decode((string) $query->fetchColumn(), true) !== $site['active_theme']) throw new RuntimeException($site['name'] . ' active theme changed unexpectedly.');
}

echo json_encode(['ok' => true, 'core' => '1.0.6', 'themes' => ['eduvixo' => '1.1.9', 'shoudu' => '1.1.2'], 'backup' => $backup], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
