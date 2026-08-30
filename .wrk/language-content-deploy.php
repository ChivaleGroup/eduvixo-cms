<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$archive = '/root/eduvixo-deploy/language-content-refinement.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$files = ['lang/de.json', 'lang/en.json', 'lang/lo.json', 'lang/pl.json', 'lang/th.json', 'lang/vi.json', 'lang/zh.json', 'public/sitemap.xml'];
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/language-content-refinement-' . $stamp;
$backup = '/root/eduvixo-backups/language-content-refinement-pre-' . $stamp;

$run = static function (array $command): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: ' . implode(' ', $command));
};

$flatten = static function (array $data, string $prefix = '') use (&$flatten): array {
    $result = [];
    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) $result += $flatten($value, $path);
        else $result[$path] = $value;
    }
    return $result;
};

$copy = static function (string $source, string $target): void {
    if (!is_file($source)) throw new RuntimeException('Missing staged file: ' . $source);
    $temporary = $target . '.eduvixo-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, 0640) || !chown($temporary, 'web123') || !chgrp($temporary, 'client9') || !rename($temporary, $target)) {
        @unlink($temporary);
        throw new RuntimeException('Atomic publish failed: ' . $target);
    }
};

if (!is_file($archive) || !mkdir($stage, 0700, true) || !mkdir($backup, 0700, true)) throw new RuntimeException('Private deployment workspace is unavailable.');
$run(['tar', '-xzf', $archive, '-C', $stage]);
foreach ($files as $file) if (!is_file($stage . '/' . $file) || !is_file($website . '/' . $file)) throw new RuntimeException('Incomplete release or production state: ' . $file);

$reference = [];
foreach (['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'] as $locale) {
    $data = json_decode((string) file_get_contents($stage . '/lang/' . $locale . '.json'), true, 512, JSON_THROW_ON_ERROR);
    $flat = $flatten($data);
    if (count($flat) !== 593) throw new RuntimeException($locale . ': unexpected translation-key count.');
    if ($reference === []) $reference = array_keys($flat);
    elseif (array_keys($flat) !== $reference) throw new RuntimeException($locale . ': translation-key parity failed.');
    foreach ($flat as $path => $value) {
        if (!is_string($value) || trim($value) === '' || str_contains($value, '—') || str_contains($value, "\u{FFFD}") || preg_match('/[\p{Cf}\p{Cc}]/u', $value) === 1) {
            throw new RuntimeException($locale . ':' . $path . ': invalid translated value.');
        }
    }
}
$sitemap = (string) file_get_contents($stage . '/public/sitemap.xml');
if (substr_count($sitemap, '<url>') !== 84 || substr_count($sitemap, 'hreflang=') < 588) throw new RuntimeException('Sitemap verification failed.');

$run(array_merge(['tar', '-czf', $backup . '/website-language-files.tar.gz', '-C', $website], $files));
file_put_contents($backup . '/ROLLBACK.txt', "Extract website-language-files.tar.gz over {$website}, set owner/group to web123:client9 and mode 0640, then verify all 84 localized routes and SEO metadata.\n", LOCK_EX);
foreach (glob($backup . '/*') ?: [] as $file) {
    chmod($file, 0600);
    if (filesize($file) < 100) throw new RuntimeException('Incomplete backup: ' . $file);
    echo 'BACKUP ' . basename($file) . ' ' . filesize($file) . ' ' . hash_file('sha256', $file) . PHP_EOL;
}

foreach ($files as $file) $copy($stage . '/' . $file, $website . '/' . $file);
foreach ($files as $file) {
    if (!hash_equals(hash_file('sha256', $stage . '/' . $file), hash_file('sha256', $website . '/' . $file))) throw new RuntimeException('Published checksum mismatch: ' . $file);
}

echo json_encode(['ok' => true, 'backup' => $backup, 'stage' => $stage, 'files' => count($files), 'languages' => 7, 'routes' => 84], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
