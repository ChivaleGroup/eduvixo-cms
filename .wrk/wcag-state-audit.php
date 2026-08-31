<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$paths = [
    'catalog' => '/var/www/clients/client9/web123/web/storage/marketplace/core-release.json',
    'demo' => '/var/www/clients/client9/web121/web',
    'shoudu' => '/var/www/clients/client59/web119/web',
];
$catalog = json_decode((string) file_get_contents($paths['catalog']), true, 16, JSON_THROW_ON_ERROR);
$result = ['catalog' => $catalog['version'] ?? null, 'sites' => []];
foreach (['demo', 'shoudu'] as $name) {
    $root = $paths[$name];
    $release = json_decode((string) file_get_contents($root . '/app/release.json'), true, 16, JSON_THROW_ON_ERROR);
    $themes = [];
    foreach (['eduvixo', 'shoudu'] as $slug) {
        $file = $root . '/themes/' . $slug . '/theme.json';
        $themes[$slug] = is_file($file) ? (json_decode((string) file_get_contents($file), true, 16, JSON_THROW_ON_ERROR)['version'] ?? null) : null;
    }
    $result['sites'][$name] = [
        'core' => $release['version'] ?? null,
        'themes' => $themes,
        'maintenance' => is_file($root . '/storage/system-updates/maintenance.json'),
        'bootstrap_left' => is_file($root . '/storage/system-update-bootstrap-1.0.6.php'),
    ];
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
