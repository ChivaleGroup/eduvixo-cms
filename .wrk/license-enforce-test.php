<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$root = rtrim((string) ($argv[1] ?? ''), '/');
if (!is_file($root . '/config/app.php')) throw new RuntimeException('Invalid installation root.');
spl_autoload_register(static function (string $class) use ($root): void { if (str_starts_with($class, 'App\\')) { $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php'; if (is_file($file)) require $file; } });
$config = (static fn(string $path): array => require $path)($root . '/config/app.php');
try {
    (new App\Core\LicenseService($config['license'], $config['engine_version']))->enforce($config['base_url']);
    echo json_encode(['ok' => true, 'host' => parse_url($config['base_url'], PHP_URL_HOST)], JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $error) {
    echo json_encode(['ok' => false, 'host' => parse_url($config['base_url'], PHP_URL_HOST), 'class' => get_class($error), 'message' => $error->getMessage()], JSON_THROW_ON_ERROR) . PHP_EOL;
    exit(1);
}
