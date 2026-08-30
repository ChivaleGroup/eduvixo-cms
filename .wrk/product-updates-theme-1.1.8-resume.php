<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$archive = '/root/eduvixo-deploy/product-updates-theme-1.1.8.tar.gz';
$backup = '/root/eduvixo-backups/product-updates-theme-1.1.8-pre-20260830-180950';
$repairStage = '/root/eduvixo-deploy/product-updates-theme-1.1.8-repair-' . gmdate('Ymd-His');
$sites = [
    ['name' => 'demo', 'root' => '/var/www/clients/client9/web121/web', 'owner' => 'web121', 'group' => 'client9', 'active_theme' => 'eduvixo'],
    ['name' => 'shoudu', 'root' => '/var/www/clients/client59/web119/web', 'owner' => 'web119', 'group' => 'client59', 'active_theme' => 'shoudu'],
];
$run = static function (array $command): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start recovery command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Recovery command failed: ' . implode(' ', $command));
};
$copy = static function (string $source, string $target, string $owner, string $group): void {
    $temporary = $target . '.eduvixo-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, 0600) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) throw new RuntimeException('Recovery publish failed: ' . $target);
};
$load = static function (string $root): array {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    return (static fn(string $path): array => require $path)($root . '/config/app.php');
};
if (!is_file($archive) || !is_dir($backup) || !mkdir($repairStage, 0700, true)) throw new RuntimeException('Recovery inputs are unavailable.');
$run(['tar', '-xzf', $archive, '-C', $repairStage]);
foreach (['.wrk/theme-update-bootstrap.php', '.wrk/package-rollback-bootstrap.php', 'storage/marketplace/packages/eduvixo-theme-1.1.8.zip'] as $file) if (!is_file($repairStage . '/' . $file)) throw new RuntimeException('Recovery bundle is incomplete: ' . $file);

$demo = $sites[0];
$demoTheme = realpath($demo['root'] . '/themes/eduvixo');
$expectedDemoTheme = $demo['root'] . '/themes/eduvixo';
if ($demoTheme !== $expectedDemoTheme) throw new RuntimeException('Unexpected Demo theme path.');
$demoManifest = json_decode((string) file_get_contents($demoTheme . '/theme.json'), true, 32, JSON_THROW_ON_ERROR);
if (($demoManifest['version'] ?? '') === '1.1.8') {
    $preserved = $backup . '/demo-misaligned-eduvixo-theme-1.1.8';
    if (file_exists($preserved) || !rename($demoTheme, $preserved)) throw new RuntimeException('Demo misaligned theme could not be preserved.');
    $run(['tar', '-xzf', $backup . '/demo-eduvixo-theme.tar.gz', '-C', $demo['root']]);
    $restored = json_decode((string) file_get_contents($demo['root'] . '/themes/eduvixo/theme.json'), true, 32, JSON_THROW_ON_ERROR);
    if (($restored['version'] ?? '') !== '1.1.7') throw new RuntimeException('Demo theme backup was not restored cleanly.');
}

$shoudu = $sites[1];
$shouduConfig = $load($shoudu['root']);
$shouduDb = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=' . $shouduConfig['database']['name'] . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$shouduVersion = (string) $shouduDb->query("SELECT version FROM extension_packages WHERE type='theme' AND slug='eduvixo' LIMIT 1")->fetchColumn();
if ($shouduVersion === '1.1.8') {
    $rollback = $shoudu['root'] . '/storage/package-rollback-bootstrap.php';
    $copy($repairStage . '/.wrk/package-rollback-bootstrap.php', $rollback, $shoudu['owner'], $shoudu['group']);
    try {
        $run(['runuser', '-u', $shoudu['owner'], '--', 'php', $rollback, $shoudu['root'], 'theme', 'eduvixo']);
    } finally {
        if (is_file($rollback)) unlink($rollback);
    }
}

foreach ($sites as $site) {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    $package = $site['root'] . '/storage/eduvixo-theme-1.1.8.zip';
    $bootstrap = $site['root'] . '/storage/theme-update-bootstrap-1.1.8.php';
    $copy($repairStage . '/storage/marketplace/packages/eduvixo-theme-1.1.8.zip', $package, $site['owner'], $site['group']);
    $copy($repairStage . '/.wrk/theme-update-bootstrap.php', $bootstrap, $site['owner'], $site['group']);
    try {
        $run(['runuser', '-u', $site['owner'], '--', 'php', $bootstrap, $site['root'], $package, '1.1.8']);
    } finally {
        if (is_file($bootstrap)) unlink($bootstrap);
        if (is_file($package)) unlink($package);
    }
}

foreach ($sites as $site) {
    $config = $load($site['root']);
    $db = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=' . $config['database']['name'] . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $packageVersion = (string) $db->query("SELECT version FROM extension_packages WHERE type='theme' AND slug='eduvixo' LIMIT 1")->fetchColumn();
    $manifest = json_decode((string) file_get_contents($site['root'] . '/themes/eduvixo/theme.json'), true, 32, JSON_THROW_ON_ERROR);
    $query = $db->prepare('SELECT value FROM settings WHERE `key` = ?');
    $query->execute(['active_theme']);
    $activeTheme = json_decode((string) $query->fetchColumn(), true);
    $page = (string) file_get_contents($site['root'] . '/themes/eduvixo/views/page.php');
    if ($packageVersion !== '1.1.8' || ($manifest['version'] ?? '') !== '1.1.8' || $activeTheme !== $site['active_theme'] || !str_contains($page, '>Hosting provided by Chivale</a>') || str_contains($page, 'Hosting provided by Chivale.') || str_contains($page, '—')) throw new RuntimeException($site['name'] . ' did not reach a consistent final state.');
}
echo json_encode(['ok' => true, 'recovered' => true, 'theme' => '1.1.8', 'backup' => $backup, 'repair_stage' => $repairStage], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
