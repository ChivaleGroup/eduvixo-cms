<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$root = rtrim((string) ($argv[1] ?? ''), '/');
if (!is_file($root . '/config/app.php')) throw new RuntimeException('Invalid installation root.');
spl_autoload_register(static function (string $class) use ($root): void { if (str_starts_with($class, 'App\\')) { $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php'; if (is_file($file)) require $file; } });
$config = (static fn(string $path): array => require $path)($root . '/config/app.php');
$db = (new App\Core\Database($config['database']))->connection();
$service = new App\Core\SystemUpdate($db, $root, $config);
$service->requestCheck();
$service->run();
$state = $service->status();
$installable = array_values(array_filter((array) ($state['products'] ?? []), static fn(array $product): bool => !empty($product['installable'])));
if (!empty($state['error']) || count($installable) !== 10) throw new RuntimeException('Official Marketplace catalog refresh failed.');
foreach ($installable as $product) if (!preg_match('/^[a-f0-9]{32}$/D', (string) ($product['id'] ?? ''))) throw new RuntimeException('Official package identity is invalid.');
echo json_encode(['ok' => true, 'host' => parse_url($config['base_url'], PHP_URL_HOST), 'products' => count($state['products']), 'installable' => count($installable)], JSON_THROW_ON_ERROR) . PHP_EOL;
