<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$archive = '/root/eduvixo-deploy/marketplace-system-1.0.4.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$sites = [
    ['name' => 'demo', 'root' => '/var/www/clients/client9/web121/web', 'owner' => 'web121', 'group' => 'client9'],
    ['name' => 'shoudu', 'root' => '/var/www/clients/client59/web119/web', 'owner' => 'web119', 'group' => 'client59'],
];
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/marketplace-system-1.0.4-' . $stamp;
$backup = '/root/eduvixo-backups/marketplace-system-pre-' . $stamp;

$run = static function (array $command, ?string $output = null): void {
    $spec = [0 => ['pipe', 'r'], 1 => $output ? ['file', $output, 'wb'] : STDOUT, 2 => STDERR];
    $process = proc_open($command, $spec, $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: ' . $command[0]);
};
$copy = static function (string $source, string $target, string $owner, string $group, int $mode = 0640): void {
    if (!is_file($source)) throw new RuntimeException('Missing staged file: ' . $source);
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0750, true)) throw new RuntimeException('Cannot create target directory.');
    $temporary = $target . '.marketplace-system-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, $mode) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) throw new RuntimeException('Atomic publish failed: ' . $target);
};
$config = static function (string $root): array {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    return (static fn(string $path): array => require $path)($root . '/config/app.php');
};

if (!is_file($archive)) throw new RuntimeException('Deployment archive is unavailable.');
if (!mkdir($stage, 0700, true) || !mkdir($backup, 0700, true)) throw new RuntimeException('Private deployment directories could not be created.');
$run(['tar', '-xzf', $archive, '-C', $stage]);

foreach (['config/marketplace.php', 'scripts/build-official-catalog.php', 'scripts/build-core-release.php', 'storage/marketplace/core-release.json', 'storage/marketplace/official-catalog.json', 'storage/marketplace/packages/eduvixo-core-1.0.4.zip', '.wrk/system-update-bootstrap.php'] as $file) {
    if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete release archive: ' . $file);
}
$run(['php', '-l', $stage . '/config/marketplace.php']);
$run(['php', '-l', $stage . '/scripts/build-official-catalog.php']);
$run(['php', '-l', $stage . '/scripts/build-core-release.php']);
$run(['php', '-l', $stage . '/.cms/source/app/Core/PackageManager.php']);
$run(['php', '-l', $stage . '/.cms/source/app/Http/DashboardController.php']);
$run(['php', '-l', $stage . '/.cms/source/app/Views/console-marketplace.php']);

require $stage . '/.cms/source/app/Core/OfficialCatalog.php';
$catalog = App\Core\OfficialCatalog::verify((string) file_get_contents($stage . '/storage/marketplace/official-catalog.json'));
if (($catalog['core']['version'] ?? '') !== '1.0.4' || count((array) ($catalog['products'] ?? [])) !== 13) throw new RuntimeException('Signed catalog release is incomplete.');
$installable = array_filter($catalog['products'], static fn(array $product): bool => !empty($product['installable']));
if (count($installable) !== 10) throw new RuntimeException('Signed installable catalog is incomplete.');
$core = $stage . '/storage/marketplace/packages/eduvixo-core-1.0.4.zip';
if (!hash_equals((string) $catalog['core']['checksum'], hash_file('sha256', $core))) throw new RuntimeException('Core package checksum mismatch.');

foreach ($sites as $site) {
    $siteConfig = $config($site['root']);
    if (!str_starts_with((string) ($siteConfig['base_url'] ?? ''), 'https://')) throw new RuntimeException('Unexpected installation URL for ' . $site['name']);
}

foreach ($sites as $site) {
    $siteConfig = $config($site['root']);
    $database = (string) $siteConfig['database']['name'];
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $database)) throw new RuntimeException('Unsafe database identity.');
    $run(['mariadb-dump', '--socket=/run/mysqld/mysqld.sock', '--user=root', '--single-transaction', '--routines', '--triggers', '--databases', $database], $backup . '/' . $site['name'] . '.sql');
    $run(['tar', '-czf', $backup . '/' . $site['name'] . '.tar.gz', '-C', $site['root'], '.']);
}
$run(['tar', '-czf', $backup . '/website.tar.gz', '-C', $website, '.']);
file_put_contents($backup . '/ROLLBACK.txt', "Prefer each installation's storage/system-updates recovery package to revert core 1.0.4 while preserving later customer data. For disaster recovery, restore the matching full tar and database dump only after preserving all post-backup writes. Restore website.tar.gz to revert the signed catalog and package-distribution configuration. Reapply the recorded site owner/group and validate PHP, login, catalog signatures, package access controls and public availability before leaving maintenance mode.\n", LOCK_EX);
foreach (glob($backup . '/*') ?: [] as $file) {
    chmod($file, 0600);
    if (filesize($file) < 100) throw new RuntimeException('Incomplete backup: ' . $file);
    echo 'BACKUP ' . basename($file) . ' ' . filesize($file) . ' ' . hash_file('sha256', $file) . PHP_EOL;
}

foreach ([
    ['config/marketplace.php', 0640],
    ['scripts/build-official-catalog.php', 0750],
    ['scripts/build-core-release.php', 0750],
    ['storage/marketplace/core-release.json', 0640],
    ['storage/marketplace/official-catalog.json', 0640],
    ['storage/marketplace/packages/eduvixo-core-1.0.4.zip', 0640],
] as [$file, $mode]) $copy($stage . '/' . $file, $website . '/' . $file, 'web123', 'client9', $mode);

foreach ($sites as $site) {
    $bootstrap = $site['root'] . '/storage/system-update-bootstrap-1.0.4.php';
    $copy($stage . '/.wrk/system-update-bootstrap.php', $bootstrap, $site['owner'], $site['group'], 0600);
    try {
        $run(['runuser', '-u', $site['owner'], '--', 'php', $bootstrap, $site['root'], '1.0.4']);
    } finally {
        if (is_file($bootstrap)) unlink($bootstrap);
    }
}

foreach ($sites as $site) {
    $run(['php', '-l', $site['root'] . '/public/index.php']);
    $run(['php', '-l', $site['root'] . '/app/Core/PackageManager.php']);
    $run(['php', '-l', $site['root'] . '/app/Http/DashboardController.php']);
    $release = json_decode((string) file_get_contents($site['root'] . '/app/release.json'), true, 8, JSON_THROW_ON_ERROR);
    if (($release['version'] ?? '') !== '1.0.4') throw new RuntimeException($site['name'] . ' did not reach core 1.0.4.');
    if (is_file($site['root'] . '/storage/system-updates/maintenance.json')) throw new RuntimeException($site['name'] . ' remained in maintenance mode.');
    $cached = App\Core\OfficialCatalog::verify((string) file_get_contents($site['root'] . '/storage/system-updates/catalog.json'));
    if (count(array_filter($cached['products'], static fn(array $product): bool => !empty($product['installable']))) !== 10) throw new RuntimeException($site['name'] . ' cached an incomplete Marketplace catalog.');
}

echo json_encode(['ok' => true, 'version' => '1.0.4', 'backup' => $backup, 'stage' => $stage, 'sites' => array_column($sites, 'name')], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
