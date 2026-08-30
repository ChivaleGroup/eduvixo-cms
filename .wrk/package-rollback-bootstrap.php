<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$root = rtrim((string) ($argv[1] ?? ''), '/');
$type = (string) ($argv[2] ?? '');
$slug = (string) ($argv[3] ?? '');
if (!is_file($root . '/config/app.php') || !in_array($type, ['theme', 'plugin', 'addon'], true) || !preg_match('/^[a-z0-9][a-z0-9-]{1,63}$/D', $slug)) throw new RuntimeException('Package rollback inputs are unavailable.');
foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require_once $file;
});
$config = (static fn(string $path): array => require $path)($root . '/config/app.php');
$db = (new App\Core\Database($config['database']))->connection();
$actor = (int) $db->query("SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id AND r.active=1 JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id AND p.slug='system.owner' WHERE u.active=1 ORDER BY u.id LIMIT 1")->fetchColumn();
if (!$actor) throw new RuntimeException('No active owner can authorize the package rollback.');
$marketplace = (array) ($config['marketplace'] ?? []);
$marketplace['installation_url'] = (string) $config['base_url'];
$manager = new App\Core\PackageManager($db, $root, (string) $config['engine_version'], $marketplace);
$result = $manager->rollback($type, $slug, $actor);
echo json_encode(['ok' => true, 'site' => parse_url((string) $config['base_url'], PHP_URL_HOST), 'rollback' => $result], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
