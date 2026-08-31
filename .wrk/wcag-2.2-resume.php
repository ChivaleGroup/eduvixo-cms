<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$stage = '/root/eduvixo-deploy/wcag-2.2-20260831-011718';
$root = '/var/www/clients/client59/web119/web';
$owner = 'web119'; $group = 'client59';
$source = $stage . '/storage/marketplace/packages/shoudu-theme-1.1.2.zip';
$expected = '1b73c0428c2067076f6f3c3281ac9c517a46a9949107661d7bbd8d721517c5a6';
if (!is_file($source) || !hash_equals($expected, hash_file('sha256', $source))) throw new RuntimeException('Verified Shoudu package is unavailable.');
$copy = static function (string $source, string $target, string $owner, string $group): void {
    $temporary = $target . '.wcag-resume-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, 0600) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) throw new RuntimeException('Atomic resume publish failed.');
};
$run = static function (array $command): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start resume command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Resume command failed.');
};
$currentManifest = json_decode((string) file_get_contents($root . '/themes/shoudu/theme.json'), true, 16, JSON_THROW_ON_ERROR);
if (($currentManifest['version'] ?? '') !== '1.1.2') {
    $package = $root . '/storage/shoudu-theme-1.1.2.zip';
    $bootstrap = $root . '/storage/theme-update-bootstrap-1.1.2.php';
    $copy($source, $package, $owner, $group);
    $copy('/root/eduvixo-deploy/theme-update-bootstrap.php', $bootstrap, $owner, $group);
    try {
        foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
        $run(['runuser', '-u', $owner, '--', 'php', $bootstrap, $root, $package, '1.1.2', 'shoudu']);
    } finally {
        if (is_file($bootstrap)) unlink($bootstrap);
        if (is_file($package)) unlink($package);
    }
}
$run(['php', '-l', $root . '/themes/shoudu/views/page.php']);
$core = json_decode((string) file_get_contents($root . '/app/release.json'), true, 16, JSON_THROW_ON_ERROR);
$theme = json_decode((string) file_get_contents($root . '/themes/shoudu/theme.json'), true, 16, JSON_THROW_ON_ERROR);
if (($core['version'] ?? '') !== '1.0.6' || ($theme['version'] ?? '') !== '1.1.2' || is_file($root . '/storage/system-updates/maintenance.json')) throw new RuntimeException('Shoudu resume verification failed.');
foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
$config = (static fn(string $path): array => require $path)($root . '/config/app.php');
$db = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=' . $config['database']['name'] . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$query = $db->prepare('SELECT value FROM settings WHERE `key` = ?'); $query->execute(['active_theme']);
if (json_decode((string) $query->fetchColumn(), true) !== 'shoudu') throw new RuntimeException('Active Shoudu theme changed unexpectedly.');
echo json_encode(['ok' => true, 'site' => 'shoudu.lrn.asia', 'core' => '1.0.6', 'theme' => 'shoudu', 'version' => '1.1.2'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
