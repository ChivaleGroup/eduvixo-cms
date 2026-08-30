<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$archive = '/root/eduvixo-deploy/marketplace-catalog-id-fix.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$sites = [
    ['root' => '/var/www/clients/client9/web121/web', 'owner' => 'web121', 'group' => 'client9'],
    ['root' => '/var/www/clients/client59/web119/web', 'owner' => 'web119', 'group' => 'client59'],
];
$stamp = gmdate('Ymd-His'); $stage = '/root/eduvixo-deploy/marketplace-catalog-id-' . $stamp; $backup = '/root/eduvixo-backups/marketplace-catalog-id-pre-' . $stamp;
$run = static function (array $command): void { $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes); if (!is_resource($process)) throw new RuntimeException('Cannot start command.'); fclose($pipes[0]); if (proc_close($process) !== 0) throw new RuntimeException('Command failed: ' . $command[0]); };
$copy = static function (string $source, string $target, string $owner, string $group, int $mode): void { $temporary = $target . '.id-fix-' . bin2hex(random_bytes(4)); if (!copy($source, $temporary) || !chmod($temporary, $mode) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) throw new RuntimeException('Atomic copy failed.'); };
if (!is_file($archive) || !mkdir($stage, 0700, true) || !mkdir($backup, 0700, true)) throw new RuntimeException('Private deployment workspace is unavailable.');
$run(['tar', '-xzf', $archive, '-C', $stage]);
$config = (static fn(string $path): array => require $path)($stage . '/config/marketplace.php');
foreach ($config['packages'] as $id => $package) if (!preg_match('/^[a-f0-9]{32}$/D', (string) $id)) throw new RuntimeException('Invalid package identity: ' . ($package['slug'] ?? 'unknown'));
require '/var/www/clients/client9/web121/web/app/Core/OfficialCatalog.php';
$catalog = App\Core\OfficialCatalog::verify((string) file_get_contents($stage . '/storage/marketplace/official-catalog.json'));
$installable = array_values(array_filter($catalog['products'], static fn(array $product): bool => !empty($product['installable'])));
if (($catalog['core']['version'] ?? '') !== '1.0.4' || count($catalog['products']) !== 13 || count($installable) !== 10) throw new RuntimeException('Signed catalog is incomplete.');
foreach ($installable as $product) if (!preg_match('/^[a-f0-9]{32}$/D', (string) $product['id'])) throw new RuntimeException('Signed package identity is invalid.');
$run(['tar', '-czf', $backup . '/website-catalog.tar.gz', '-C', $website, 'config/marketplace.php', 'storage/marketplace/official-catalog.json']);
file_put_contents($backup . '/ROLLBACK.txt', "Restore website-catalog.tar.gz into {$website}, restore web123:client9 ownership, then request a signed catalog refresh in both installations. No database or core file rollback is required.\n", LOCK_EX);
foreach (glob($backup . '/*') ?: [] as $file) { chmod($file, 0600); if (filesize($file) < 100) throw new RuntimeException('Catalog backup is incomplete.'); echo 'BACKUP ' . basename($file) . ' ' . filesize($file) . ' ' . hash_file('sha256', $file) . PHP_EOL; }
$copy($stage . '/config/marketplace.php', $website . '/config/marketplace.php', 'web123', 'client9', 0640);
$copy($stage . '/storage/marketplace/official-catalog.json', $website . '/storage/marketplace/official-catalog.json', 'web123', 'client9', 0640);
foreach ($sites as $site) {
    $script = $site['root'] . '/storage/marketplace-catalog-refresh.php';
    $copy($stage . '/.wrk/marketplace-catalog-refresh.php', $script, $site['owner'], $site['group'], 0600);
    try { $run(['runuser', '-u', $site['owner'], '--', 'php', $script, $site['root']]); } finally { if (is_file($script)) unlink($script); }
}
echo json_encode(['ok' => true, 'backup' => $backup, 'core' => $catalog['core']['version'], 'installable' => count($installable)], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
