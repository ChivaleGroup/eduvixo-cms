<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/marketplace-google-analytics-' . $stamp;
$archive = '/root/eduvixo-deploy/marketplace-google-analytics-release.tar.gz';
$web = '/var/www/clients/client9/web123/web';
$owner = 'web123'; $group = 'client9';
$files = [
    'app/MarketplaceService.php', 'app/Site.php', 'app/views/layout.php', 'app/views/pages/marketplace.php',
    'config/marketplace.php', 'config/site.php',
    'lang/de.json', 'lang/en.json', 'lang/lo.json', 'lang/pl.json', 'lang/th.json', 'lang/vi.json', 'lang/zh.json',
    'public/assets/css/site.min.css', 'public/assets/icons.svg', 'public/assets/js/site.min.js',
    'resources/site.css', 'resources/site.js', 'scripts/build-core-release.php', 'scripts/build-official-catalog.php',
    'storage/marketplace/official-catalog.json', 'storage/marketplace/packages/google-analytics-1.0.0.zip',
];
$existing = array_values(array_filter($files, static fn(string $file): bool => is_file($web . '/' . $file)));
$run = static function (array $command): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: ' . $command[0]);
};
$copy = static function (string $source, string $target, int $mode) use ($owner, $group): void {
    if (!is_file($source)) throw new RuntimeException('Missing staged file: ' . $source);
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0750, true) && !is_dir(dirname($target))) throw new RuntimeException('Cannot create target directory.');
    $temporary = $target . '.ga-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, $mode) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) {
        @unlink($temporary); throw new RuntimeException('Atomic publish failed: ' . $target);
    }
};
if (realpath($web) !== $web || !is_file($web . '/public/index.php')) throw new RuntimeException('Unexpected production root.');
if (!is_file($archive)) throw new RuntimeException('Release archive is missing.');
$backup = '/root/eduvixo-backups/marketplace-google-analytics-pre-' . $stamp;
if (!mkdir($backup, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
$run(array_merge(['tar', '-czf', $backup . '/website-files.tar.gz', '-C', $web], $existing));
file_put_contents($backup . '/ROLLBACK.txt', "Extract website-files.tar.gz into {$web}, restore web123:client9 ownership, remove only the newly added Google Analytics package if required, reload php8.4-fpm and repeat health checks.\n", LOCK_EX);
chmod($backup . '/website-files.tar.gz', 0600); chmod($backup . '/ROLLBACK.txt', 0600);
if (filesize($backup . '/website-files.tar.gz') < 1024) throw new RuntimeException('Deployment backup is incomplete.');
if (!mkdir($stage, 0700, true)) throw new RuntimeException('Cannot create staging directory.');
$run(['tar', '-xzf', $archive, '-C', $stage]);
foreach ($files as $file) if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete staged release: ' . $file);
foreach (['app/MarketplaceService.php', 'app/Site.php', 'app/views/layout.php', 'app/views/pages/marketplace.php', 'config/marketplace.php', 'config/site.php', 'scripts/build-core-release.php', 'scripts/build-official-catalog.php'] as $file) $run(['php', '-l', $stage . '/' . $file]);
foreach (glob($stage . '/lang/*.json') ?: [] as $language) json_decode((string) file_get_contents($language), true, 512, JSON_THROW_ON_ERROR);
$package = $stage . '/storage/marketplace/packages/google-analytics-1.0.0.zip';
if (filesize($package) !== 8845 || !hash_equals('68ddbc291b03e87afbaeb4ac2fef1d966b1ca982edc40ff9d5d38e15b9ad4c1f', hash_file('sha256', $package))) throw new RuntimeException('Google Analytics package integrity failure.');
$zip = new ZipArchive();
if ($zip->open($package, ZipArchive::RDONLY) !== true) throw new RuntimeException('Google Analytics package is unreadable.');
$manifestRaw = $zip->getFromName('eduvixo-package.json'); $signature = base64_decode((string) $zip->getFromName('signature.ed25519'), true); $public = base64_decode('q+WweIoNkskiUOzyLl80Bc9V2TkBdHXXrtOufSRIg54=', true);
if (!is_string($manifestRaw) || !is_string($signature) || !is_string($public) || !sodium_crypto_sign_verify_detached($signature, $manifestRaw, $public)) throw new RuntimeException('Google Analytics publisher signature failure.');
$manifest = json_decode($manifestRaw, true, 64, JSON_THROW_ON_ERROR);
if (($manifest['slug'] ?? '') !== 'google-analytics' || ($manifest['version'] ?? '') !== '1.0.0') throw new RuntimeException('Google Analytics package identity failure.');
foreach ((array) ($manifest['files'] ?? []) as $file => $hash) { $content = $zip->getFromName('payload/' . $file); if (!is_string($content) || !hash_equals((string) $hash, hash('sha256', $content))) throw new RuntimeException('Google Analytics payload integrity failure.'); }
$zip->close();
$catalogDocument = json_decode((string) file_get_contents($stage . '/storage/marketplace/official-catalog.json'), true, 8, JSON_THROW_ON_ERROR);
$payload = base64_decode((string) ($catalogDocument['signed_payload'] ?? ''), true); $catalogSignature = base64_decode((string) ($catalogDocument['signature'] ?? ''), true);
if (!is_string($payload) || !is_string($catalogSignature) || !sodium_crypto_sign_verify_detached($catalogSignature, $payload, $public)) throw new RuntimeException('Official catalog signature failure.');
$catalog = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
if (count((array) ($catalog['products'] ?? [])) !== 12) throw new RuntimeException('Official catalog product count failure.');
foreach ($files as $file) $copy($stage . '/' . $file, $web . '/' . $file, str_starts_with($file, 'public/') ? 0644 : ($file === 'scripts/build-official-catalog.php' || $file === 'scripts/build-core-release.php' ? 0750 : 0640));
$run(['systemctl', 'reload', 'php8.4-fpm']);
$run(['php', '-l', $web . '/public/index.php']);
$run(['php', '-l', $web . '/app/Site.php']);
foreach (['apache2', 'php8.4-fpm'] as $service) $run(['systemctl', 'is-active', '--quiet', $service]);
echo json_encode(['ok' => true, 'backup' => $backup, 'backup_size' => filesize($backup . '/website-files.tar.gz'), 'package_sha256' => hash_file('sha256', $web . '/storage/marketplace/packages/google-analytics-1.0.0.zip')], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
