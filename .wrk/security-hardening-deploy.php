<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$archive = '/root/eduvixo-deploy/security-hardening.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$files = ['.htaccess', 'app/ContactService.php', 'app/MarketplaceService.php', 'app/Site.php', 'config/site.php', 'public/.htaccess', 'public/.well-known/security.txt'];
$newFiles = ['public/.well-known/security.txt'];
$secret = $website . '/storage/.site-rate-key';
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/security-hardening-' . $stamp;
$backup = '/root/eduvixo-backups/security-hardening-pre-' . $stamp;

$run = static function (array $command): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: ' . implode(' ', $command));
};
$copy = static function (string $source, string $target): void {
    if (!is_file($source)) throw new RuntimeException('Missing staged file: ' . $source);
    $parent = dirname($target);
    if (!is_dir($parent) && (!mkdir($parent, 0755, true) || !chown($parent, 'web123') || !chgrp($parent, 'client9'))) throw new RuntimeException('Cannot create target directory: ' . $parent);
    $temporary = $target . '.eduvixo-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, 0640) || !chown($temporary, 'web123') || !chgrp($temporary, 'client9') || !rename($temporary, $target)) {
        @unlink($temporary);
        throw new RuntimeException('Atomic publish failed: ' . $target);
    }
};

if (!is_file($archive) || !mkdir($stage, 0700, true) || !mkdir($backup, 0700, true)) throw new RuntimeException('Private deployment workspace is unavailable.');
$run(['tar', '-xzf', $archive, '-C', $stage]);
foreach ($files as $file) {
    if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete release: ' . $file);
    if (!is_file($website . '/' . $file) && !in_array($file, $newFiles, true)) throw new RuntimeException('Incomplete production state: ' . $file);
}
foreach (['app/ContactService.php', 'app/MarketplaceService.php', 'app/Site.php', 'config/site.php'] as $file) $run(['php', '-l', $stage . '/' . $file]);

$site = (string) file_get_contents($stage . '/app/Site.php');
$contact = (string) file_get_contents($stage . '/app/ContactService.php');
$marketplace = (string) file_get_contents($stage . '/app/MarketplaceService.php');
$config = (string) file_get_contents($stage . '/config/site.php');
$access = (string) file_get_contents($stage . '/public/.htaccess');
$rootAccess = (string) file_get_contents($stage . '/.htaccess');
$security = (string) file_get_contents($stage . '/public/.well-known/security.txt');
if (!str_contains($site, "session.use_strict_mode") || !str_contains($site, "object-src 'none'") || !str_contains($site, 'upgrade-insecure-requests') || !str_contains($site, 'methodNotAllowed')) throw new RuntimeException('Application hardening is incomplete.');
if (!str_contains($contact, 'flock($handle, LOCK_EX)') || !str_contains($marketplace, 'flock($handle, LOCK_EX)') || !str_contains($marketplace, 'flock($handle, LOCK_SH)')) throw new RuntimeException('Atomic limiter hardening is incomplete.');
if (!str_contains($config, "random_bytes(32)") || str_contains($config, "hash('sha256', \$root)")) throw new RuntimeException('Runtime security key hardening is incomplete.');
foreach (['LimitRequestBody 65536', 'Strict-Transport-Security', 'Cross-Origin-Opener-Policy', '[R=405,L]', '[R=413,L]'] as $required) if (!str_contains($access, $required)) throw new RuntimeException('Apache hardening is incomplete: ' . $required);
if (!str_contains($rootAccess, '-MultiViews') || !str_contains($rootAccess, 'LimitRequestBody 65536') || !str_contains($rootAccess, '[R=413,L]') || str_contains($rootAccess, '[F,L')) throw new RuntimeException('Repository boundary hardening is incomplete.');
if (!str_contains($security, 'Canonical: https://www.eduvixo.com/.well-known/security.txt') || !str_contains($security, 'Contact: mailto:info@eduvixo.com')) throw new RuntimeException('security.txt validation failed.');

$secretExisted = is_file($secret);
$existingFiles = array_values(array_filter($files, static fn(string $file): bool => is_file($website . '/' . $file)));
$backupFiles = $existingFiles;
if ($secretExisted) $backupFiles[] = 'storage/.site-rate-key';
$run(array_merge(['tar', '-czf', $backup . '/website-files.tar.gz', '-C', $website], $backupFiles));
$rollbackSecret = $secretExisted ? 'restore storage/.site-rate-key from the archive' : 'remove storage/.site-rate-key';
file_put_contents($backup . '/ROLLBACK.txt', "Extract website-files.tar.gz over {$website}, remove public/.well-known/security.txt if it was absent before this release, {$rollbackSecret}, restore web123:client9 ownership and recorded modes, then rerun the security, route, cache and Marketplace audits. No database rollback is required.\n", LOCK_EX);
foreach (glob($backup . '/*') ?: [] as $file) { chmod($file, 0600); if (filesize($file) < 100) throw new RuntimeException('Incomplete backup: ' . $file); echo 'BACKUP ' . basename($file) . ' ' . filesize($file) . ' ' . hash_file('sha256', $file) . PHP_EOL; }

if (!$secretExisted) {
    $temporary = $secret . '.eduvixo-new-' . bin2hex(random_bytes(4));
    $value = bin2hex(random_bytes(32));
    if (file_put_contents($temporary, $value, LOCK_EX) !== 64 || !chmod($temporary, 0640) || !chown($temporary, 'web123') || !chgrp($temporary, 'client9') || !rename($temporary, $secret)) { @unlink($temporary); throw new RuntimeException('Secure runtime key creation failed.'); }
    unset($value);
}
if (!preg_match('/^[a-f0-9]{64}$/D', trim((string) file_get_contents($secret)))) throw new RuntimeException('Secure runtime key verification failed.');

foreach ($files as $file) $copy($stage . '/' . $file, $website . '/' . $file);
foreach ($files as $file) if (!hash_equals(hash_file('sha256', $stage . '/' . $file), hash_file('sha256', $website . '/' . $file))) throw new RuntimeException('Published checksum mismatch: ' . $file);

echo json_encode(['ok' => true, 'backup' => $backup, 'stage' => $stage, 'files' => count($files), 'runtime_secret' => $secretExisted ? 'preserved' : 'generated', 'database' => false], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
