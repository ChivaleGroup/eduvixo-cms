<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$archive = '/root/eduvixo-deploy/product-performance.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$files = [
    'app/Site.php', 'app/views/pages/product.php', 'public/.htaccess', 'public/assets/css/site.min.css',
    'public/assets/js/site.min.js', 'public/sitemap.xml', 'resources/pages.css', 'resources/vitals.js',
    'public/demo/index.php', 'scripts/build-assets.php', 'lang/de.json', 'lang/en.json', 'lang/lo.json', 'lang/pl.json',
    'lang/th.json', 'lang/vi.json', 'lang/zh.json',
];
$newFiles = ['resources/vitals.js'];
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/product-performance-' . $stamp;
$backup = '/root/eduvixo-backups/product-performance-pre-' . $stamp;

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
foreach ($files as $file) {
    if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete release: ' . $file);
    if (!is_file($website . '/' . $file) && !in_array($file, $newFiles, true)) throw new RuntimeException('Incomplete production state: ' . $file);
}
foreach (['app/Site.php', 'app/views/pages/product.php', 'public/demo/index.php', 'scripts/build-assets.php'] as $file) $run(['php', '-l', $stage . '/' . $file]);

$reference = null;
foreach (['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'] as $locale) {
    $copyData = json_decode((string) file_get_contents($stage . '/lang/' . $locale . '.json'), true, 512, JSON_THROW_ON_ERROR);
    if (isset($copyData['product']['metrics'])) throw new RuntimeException($locale . ': obsolete product metrics remain.');
    $flat = $flatten($copyData);
    if (count($flat) !== 585) throw new RuntimeException($locale . ': unexpected translation-key count.');
    if ($reference === null) $reference = array_keys($flat);
    elseif (array_keys($flat) !== $reference) throw new RuntimeException($locale . ': translation-key parity failed.');
}

$site = (string) file_get_contents($stage . '/app/Site.php');
$product = (string) file_get_contents($stage . '/app/views/pages/product.php');
$css = (string) file_get_contents($stage . '/public/assets/css/site.min.css');
$js = (string) file_get_contents($stage . '/public/assets/js/site.min.js');
$access = (string) file_get_contents($stage . '/public/.htaccess');
$sitemap = (string) file_get_contents($stage . '/public/sitemap.xml');
if (!str_contains($site, 'private bool $sessionStarted = false') || !str_contains($site, 'private, max-age=300, stale-while-revalidate=60') || !str_contains($site, "session_cache_limiter('')") || substr_count($site, '$this->noStore();') < 4) throw new RuntimeException('Lazy-session cache implementation is incomplete.');
if (str_contains($product, 'metric-grid') || !str_contains($product, 'product-modules') || str_contains($css, '.metric-grid')) throw new RuntimeException('Product counter removal is incomplete.');
if (!str_contains($js, "'PerformanceObserver'in window") || !str_contains($js, "'web_vital'")) throw new RuntimeException('Web Vitals monitor is missing.');
if (!str_contains($access, '<FilesMatch "\.json$">') || str_contains($access, 'php|json')) throw new RuntimeException('Apache cache boundary is unsafe.');
if (substr_count($sitemap, '<url>') !== 84) throw new RuntimeException('Sitemap verification failed.');

$existingFiles = array_values(array_diff($files, $newFiles));
$run(array_merge(['tar', '-czf', $backup . '/website-files.tar.gz', '-C', $website], $existingFiles));
file_put_contents($backup . '/ROLLBACK.txt', "Extract website-files.tar.gz over {$website}, remove resources/vitals.js, restore web123:client9 ownership and mode 0640, then verify Apache responses, cache boundaries, CSRF sessions, all 84 localized routes and the Product layout.\n", LOCK_EX);
foreach (glob($backup . '/*') ?: [] as $file) {
    chmod($file, 0600);
    if (filesize($file) < 100) throw new RuntimeException('Incomplete backup: ' . $file);
    echo 'BACKUP ' . basename($file) . ' ' . filesize($file) . ' ' . hash_file('sha256', $file) . PHP_EOL;
}

foreach ($files as $file) $copy($stage . '/' . $file, $website . '/' . $file);
foreach ($files as $file) if (!hash_equals(hash_file('sha256', $stage . '/' . $file), hash_file('sha256', $website . '/' . $file))) throw new RuntimeException('Published checksum mismatch: ' . $file);

echo json_encode(['ok' => true, 'backup' => $backup, 'stage' => $stage, 'files' => count($files), 'database' => false], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
