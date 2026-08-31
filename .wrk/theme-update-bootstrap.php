<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$root = rtrim((string) ($argv[1] ?? ''), '/');
$package = (string) ($argv[2] ?? '');
$expected = (string) ($argv[3] ?? '1.1.7');
$expectedSlug = (string) ($argv[4] ?? 'eduvixo');
if (!is_file($root . '/config/app.php') || !is_file($package)) throw new RuntimeException('Theme update inputs are unavailable.');
foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require_once $file;
});
$config = (static fn(string $path): array => require $path)($root . '/config/app.php');
$db = (new App\Core\Database($config['database']))->connection();
$actor = (int) $db->query("SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id AND r.active=1 JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id AND p.slug='system.owner' WHERE u.active=1 ORDER BY u.id LIMIT 1")->fetchColumn();
if (!$actor) throw new RuntimeException('No active owner can authorize the theme update.');
$marketplace = (array) ($config['marketplace'] ?? []);
$marketplace['installation_url'] = (string) $config['base_url'];
$manager = new App\Core\PackageManager($db, $root, (string) $config['engine_version'], $marketplace);
$manager->trustPublisher('chivale-eduvixo-2026', 'QUANT Software House', 'https://www.ittsp.com/', 'q+WweIoNkskiUOzyLl80Bc9V2TkBdHXXrtOufSRIg54=', $actor);
$stage = $manager->stageLocalFile($package, $actor);
if (($stage['type'] ?? '') !== 'theme' || ($stage['slug'] ?? '') !== $expectedSlug || ($stage['version'] ?? '') !== $expected) throw new RuntimeException('Unexpected theme package identity.');
$installed = $manager->install((string) $stage['token'], $actor);
$manifest = json_decode((string) file_get_contents($root . '/themes/' . $expectedSlug . '/theme.json'), true, 32, JSON_THROW_ON_ERROR);
if (($manifest['version'] ?? '') !== $expected) throw new RuntimeException('Theme update did not reach the expected version.');
echo json_encode(['ok'=>true,'site'=>parse_url((string)$config['base_url'],PHP_URL_HOST),'theme'=>$expectedSlug,'version'=>$expected,'active'=>(bool)($installed['active']??false)], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL;
