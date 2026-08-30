<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$stamp = gmdate('Ymd-His');
$archive = '/root/eduvixo-deploy/marketplace-filter-release.tar.gz';
$stage = '/root/eduvixo-deploy/marketplace-filter-' . $stamp;
$web = '/var/www/clients/client9/web123/web';
$owner = 'web123'; $group = 'client9';
$files = [
    'app/views/pages/marketplace.php',
    'lang/de.json', 'lang/en.json', 'lang/lo.json', 'lang/pl.json', 'lang/th.json', 'lang/vi.json', 'lang/zh.json',
    'public/assets/css/site.min.css', 'public/assets/icons.svg', 'public/assets/js/site.min.js',
    'resources/pages.css', 'resources/site.js',
];
$run = static function (array $command): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: ' . $command[0]);
};
$copy = static function (string $source, string $target, int $mode) use ($owner, $group): void {
    if (!is_file($source)) throw new RuntimeException('Missing staged file: ' . $source);
    $temporary = $target . '.marketplace-filter-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, $mode) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) {
        @unlink($temporary);
        throw new RuntimeException('Atomic publish failed: ' . $target);
    }
};
if (realpath($web) !== $web || !is_file($web . '/public/index.php')) throw new RuntimeException('Unexpected production root.');
if (!is_file($archive)) throw new RuntimeException('Release archive is missing.');
$backup = '/root/eduvixo-backups/marketplace-filter-pre-' . $stamp;
if (!mkdir($backup, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
$run(array_merge(['tar', '-czf', $backup . '/website-files.tar.gz', '-C', $web], $files));
file_put_contents($backup . '/ROLLBACK.txt', "Extract website-files.tar.gz into {$web}, restore web123:client9 ownership and existing file modes, reload php8.4-fpm, then repeat marketplace-filter-production-audit.php. No database rollback is required.\n", LOCK_EX);
chmod($backup . '/website-files.tar.gz', 0600); chmod($backup . '/ROLLBACK.txt', 0600);
if (filesize($backup . '/website-files.tar.gz') < 1024) throw new RuntimeException('Deployment backup is incomplete.');
if (!mkdir($stage, 0700, true)) throw new RuntimeException('Cannot create staging directory.');
$run(['tar', '-xzf', $archive, '-C', $stage]);
foreach ($files as $file) if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete staged release: ' . $file);
$run(['php', '-l', $stage . '/app/views/pages/marketplace.php']);
foreach (glob($stage . '/lang/*.json') ?: [] as $language) {
    $data = json_decode((string) file_get_contents($language), true, 512, JSON_THROW_ON_ERROR);
    foreach (['search_label', 'filter_type', 'filter_price', 'filter_all', 'filter_free', 'filter_paid', 'results_count', 'no_results_title', 'clear_filters'] as $key) {
        if (trim((string) ($data['marketplace'][$key] ?? '')) === '') throw new RuntimeException('Missing Marketplace translation: ' . basename($language) . ':' . $key);
    }
}
if (!str_contains((string) file_get_contents($stage . '/resources/site.js'), 'data-marketplace-filter') || !str_contains((string) file_get_contents($stage . '/public/assets/js/site.min.js'), 'data-marketplace-filter')) throw new RuntimeException('Marketplace filter JavaScript validation failed.');
if (!str_contains((string) file_get_contents($stage . '/resources/pages.css'), '.marketplace-discovery') || !str_contains((string) file_get_contents($stage . '/public/assets/css/site.min.css'), '.marketplace-discovery')) throw new RuntimeException('Marketplace filter CSS validation failed.');
if (!str_contains((string) file_get_contents($stage . '/public/assets/icons.svg'), 'id="search"')) throw new RuntimeException('Search icon validation failed.');
foreach ($files as $file) $copy($stage . '/' . $file, $web . '/' . $file, str_starts_with($file, 'public/') ? 0644 : 0640);
$run(['systemctl', 'reload', 'php8.4-fpm']);
$run(['php', '-l', $web . '/app/views/pages/marketplace.php']);
foreach (['apache2', 'php8.4-fpm'] as $service) $run(['systemctl', 'is-active', '--quiet', $service]);
echo json_encode(['ok' => true, 'backup' => $backup, 'backup_size' => filesize($backup . '/website-files.tar.gz'), 'files' => count($files)], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
