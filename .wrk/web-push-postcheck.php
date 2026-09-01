<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$sites = [
    ['name' => 'demo', 'root' => '/var/www/clients/client9/web121/web', 'owner' => 'web121'],
    ['name' => 'shoudu', 'root' => '/var/www/clients/client59/web119/web', 'owner' => 'web119'],
];

$result = [];
foreach ($sites as $site) {
    foreach (array_keys(getenv()) as $key) {
        if (str_starts_with($key, 'CMS_')) {
            putenv($key);
        }
    }

    $config = (static fn(string $path): array => require $path)($site['root'] . '/config/app.php');
    $database = (string) $config['database']['name'];
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $database)) {
        throw new RuntimeException('Unsafe database identifier.');
    }

    $db = new PDO(
        'mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=' . $database . ';charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $release = json_decode((string) file_get_contents($site['root'] . '/app/release.json'), true, 8, JSON_THROW_ON_ERROR);
    $keyFile = $site['root'] . '/storage/private/web-push-vapid.json';
    $tables = [];
    foreach (['web_push_preferences', 'web_push_subscriptions', 'web_push_deliveries'] as $table) {
        $statement = $db->prepare('SHOW TABLES LIKE ?');
        $statement->execute([$table]);
        $tables[$table] = (bool) $statement->fetchColumn();
    }

    $result[$site['name']] = [
        'core' => (string) ($release['version'] ?? ''),
        'migration_025' => (bool) $db->query("SELECT 1 FROM migrations WHERE name='025_web_push.sql'")->fetchColumn(),
        'tables' => $tables,
        'subscriptions' => (int) $db->query('SELECT COUNT(*) FROM web_push_subscriptions WHERE active=1')->fetchColumn(),
        'pending_deliveries' => (int) $db->query("SELECT COUNT(*) FROM web_push_deliveries WHERE status IN ('pending','retrying')")->fetchColumn(),
        'failed_deliveries' => (int) $db->query("SELECT COUNT(*) FROM web_push_deliveries WHERE status='failed'")->fetchColumn(),
        'key_mode' => substr(sprintf('%o', fileperms($keyFile)), -4),
        'key_owner' => posix_getpwuid(fileowner($keyFile))['name'] ?? null,
        'service_worker' => hash_file('sha256', $site['root'] . '/public/service-worker.js'),
    ];
}

$demo = $sites[0]['root'];
foreach (array_keys(getenv()) as $key) {
    if (str_starts_with($key, 'CMS_')) {
        putenv($key);
    }
}
$config = (static fn(string $path): array => require $path)($demo . '/config/app.php');
$db = new PDO(
    'mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=' . $config['database']['name'] . ';charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$calendar = $db->query("SELECT version,signature_status,active FROM extension_packages WHERE type='addon' AND slug='calendar'")->fetch(PDO::FETCH_ASSOC);
$result['calendar'] = $calendar ?: null;

echo json_encode(['ok' => true, 'checks' => $result], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
