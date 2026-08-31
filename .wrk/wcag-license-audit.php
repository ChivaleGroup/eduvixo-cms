<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
foreach (['demo' => '/var/www/clients/client9/web121/web', 'shoudu' => '/var/www/clients/client59/web119/web'] as $name => $root) {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    spl_autoload_register(static function (string $class) use ($root): void {
        if (!str_starts_with($class, 'App\\')) return;
        $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require_once $file;
    });
    $config = (static fn(string $path): array => require $path)($root . '/config/app.php');
    $service = new App\Core\LicenseService($config['license'], (string) $config['engine_version']);
    try {
        $status = $service->enforce((string) $config['base_url']);
        $result = ['ok' => true, 'status' => $status['status'] ?? null, 'valid' => $status['valid'] ?? null];
    } catch (Throwable $error) {
        $result = ['ok' => false, 'error' => $error->getMessage()];
    }
    echo json_encode(['site' => $name, 'base_url' => $config['base_url'], 'product_name' => $config['license']['product_name'] ?? null, 'product_model' => $config['license']['product_model'] ?? null, 'engine' => $config['engine_version'], 'license' => $result], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
}
