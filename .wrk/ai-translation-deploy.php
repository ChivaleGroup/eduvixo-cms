<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/ai-translation-' . $stamp;
$archive = '/root/eduvixo-deploy/ai-translation-release.tar.gz';
$web = '/var/www/clients/client9/web123/web';
$owner = 'web123'; $group = 'client9';
$packageName = 'ai-translation-assistant-1.0.0-beta.1.zip';
$files = [
    'config/marketplace.php',
    'lang/de.json', 'lang/en.json', 'lang/lo.json', 'lang/pl.json', 'lang/th.json', 'lang/vi.json', 'lang/zh.json',
    'public/assets/icons.svg', 'storage/marketplace/official-catalog.json', 'storage/marketplace/packages/' . $packageName,
];
$run = static function (array $command): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]); if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: ' . $command[0]);
};
$copy = static function (string $source, string $target, int $mode) use ($owner, $group): void {
    if (!is_file($source)) throw new RuntimeException('Missing staged file: ' . $source);
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0750, true) && !is_dir(dirname($target))) throw new RuntimeException('Cannot create target directory.');
    $temporary = $target . '.ai-translation-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, $mode) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) {
        @unlink($temporary); throw new RuntimeException('Atomic publish failed: ' . $target);
    }
};
if (realpath($web) !== $web || !is_file($web . '/public/index.php')) throw new RuntimeException('Unexpected production root.');
if (!is_file($archive)) throw new RuntimeException('Release archive is missing.');
$existing = array_values(array_filter($files, static fn(string $file): bool => is_file($web . '/' . $file)));
$packagePreviouslyExisted = is_file($web . '/storage/marketplace/packages/' . $packageName);
$backup = '/root/eduvixo-backups/ai-translation-pre-' . $stamp;
if (!mkdir($backup, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
$run(array_merge(['tar', '-czf', $backup . '/website-files.tar.gz', '-C', $web], $existing));
file_put_contents($backup . '/ROLLBACK.txt', "Extract website-files.tar.gz into {$web}, " . ($packagePreviouslyExisted ? "retain the restored storage/marketplace/packages/{$packageName}" : "remove only storage/marketplace/packages/{$packageName}") . ", restore web123:client9 ownership, reload php8.4-fpm and repeat the Marketplace audit. No database rollback is required.\n", LOCK_EX);
chmod($backup . '/website-files.tar.gz', 0600); chmod($backup . '/ROLLBACK.txt', 0600);
if (filesize($backup . '/website-files.tar.gz') < 1024) throw new RuntimeException('Deployment backup is incomplete.');
if (!mkdir($stage, 0700, true)) throw new RuntimeException('Cannot create staging directory.');
$run(['tar', '-xzf', $archive, '-C', $stage]);
foreach ($files as $file) if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete staged release: ' . $file);
$run(['php', '-l', $stage . '/config/marketplace.php']);
foreach (glob($stage . '/lang/*.json') ?: [] as $language) {
    $copyData = json_decode((string) file_get_contents($language), true, 512, JSON_THROW_ON_ERROR);
    if (trim((string) ($copyData['marketplace']['ai_translation_copy'] ?? '')) === '') throw new RuntimeException('Missing localized AI Translation copy.');
}
$package = $stage . '/storage/marketplace/packages/' . $packageName;
if (filesize($package) !== 15628 || !hash_equals('d82f312c24037509814323371ce63dd52cd9037e4ed2a347d67f9a98c4ca7c72', hash_file('sha256', $package))) throw new RuntimeException('AI Translation package integrity failure.');
$zip = new ZipArchive();
if ($zip->open($package, ZipArchive::RDONLY) !== true) throw new RuntimeException('AI Translation package is unreadable.');
$raw = $zip->getFromName('eduvixo-package.json'); $signature = base64_decode((string) $zip->getFromName('signature.ed25519'), true); $public = base64_decode('q+WweIoNkskiUOzyLl80Bc9V2TkBdHXXrtOufSRIg54=', true);
if (!is_string($raw) || !is_string($signature) || !is_string($public) || !sodium_crypto_sign_verify_detached($signature, $raw, $public)) throw new RuntimeException('AI Translation publisher signature failure.');
$manifest = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
if (($manifest['slug'] ?? '') !== 'ai-translation-assistant' || ($manifest['version'] ?? '') !== '1.0.0-beta.1' || ($manifest['license']['model'] ?? '') !== 'free') throw new RuntimeException('AI Translation package identity failure.');
foreach ((array) ($manifest['files'] ?? []) as $file => $hash) { $content = $zip->getFromName('payload/' . $file); if (!is_string($content) || !hash_equals((string) $hash, hash('sha256', $content))) throw new RuntimeException('AI Translation payload integrity failure.'); }
$zip->close();
$catalogDocument = json_decode((string) file_get_contents($stage . '/storage/marketplace/official-catalog.json'), true, 8, JSON_THROW_ON_ERROR);
$payload = base64_decode((string) ($catalogDocument['signed_payload'] ?? ''), true); $catalogSignature = base64_decode((string) ($catalogDocument['signature'] ?? ''), true);
if (!is_string($payload) || !is_string($catalogSignature) || !sodium_crypto_sign_verify_detached($catalogSignature, $payload, $public)) throw new RuntimeException('Official catalog signature failure.');
$catalog = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
if (count((array) ($catalog['products'] ?? [])) !== 13) throw new RuntimeException('Official catalog product count failure.');
foreach ($files as $file) $copy($stage . '/' . $file, $web . '/' . $file, str_starts_with($file, 'public/') ? 0644 : 0640);
$run(['systemctl', 'reload', 'php8.4-fpm']);
$run(['php', '-l', $web . '/config/marketplace.php']);
foreach (['apache2', 'php8.4-fpm'] as $service) $run(['systemctl', 'is-active', '--quiet', $service]);
echo json_encode(['ok' => true, 'backup' => $backup, 'backup_size' => filesize($backup . '/website-files.tar.gz'), 'package_sha256' => hash_file('sha256', $web . '/storage/marketplace/packages/' . $packageName)], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
