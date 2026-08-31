<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$targets = [
    '.cms/source/app/Views',
    '.cms/source/public/theme',
    '.cms/source/themes/eduvixo',
    '.wrk/shoudu-wcag-source/shoudu',
];
$changed = [];
foreach ($targets as $target) {
    $path = $root . '/' . $target;
    $iterator = is_dir($path)
        ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS))
        : [];
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->isLink() || !preg_match('/\.(?:php|js|css|json)$/i', $file->getFilename())) continue;
        $source = (string) file_get_contents($file->getPathname());
        if (!str_contains($source, '—')) continue;
        $updated = str_replace('—', '-', $source);
        if (file_put_contents($file->getPathname(), $updated, LOCK_EX) !== strlen($updated)) throw new RuntimeException('Cannot update ' . $file->getPathname());
        $changed[] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    }
}
echo json_encode(['changed' => $changed, 'count' => count($changed)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
