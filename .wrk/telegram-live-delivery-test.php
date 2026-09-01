<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$root = rtrim((string) ($argv[1] ?? ''), '/');
if ($root !== '/var/www/clients/client9/web121/web') throw new RuntimeException('Unexpected live test target.');
foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) {
        $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require_once $file;
    }
});
$config = (static fn(string $path): array => require $path)($root . '/config/app.php');
$db = (new App\Core\Database($config['database']))->connection();
$user = $db->query("SELECT id FROM users WHERE id=1 AND active=1 AND is_demo=0 LIMIT 1")->fetchColumn();
if (!$user) throw new RuntimeException('The authorized Telegram test recipient is unavailable.');
$settings = $db->query("SELECT encrypted_settings,enabled,last_verified_at,last_error FROM notification_channel_settings WHERE plugin_slug='telegram-notifications' AND subject_type='installation' AND subject_id=0")->fetch();
if (!$settings || !(bool) $settings['enabled'] || !$settings['last_verified_at'] || $settings['last_error']) throw new RuntimeException('Telegram is not enabled and verified.');
if (preg_match('/[0-9]{6,15}:[A-Za-z0-9_-]{30,100}/', (string) $settings['encrypted_settings'])) throw new RuntimeException('Telegram credentials are not encrypted.');
if (in_array('--audit-only', $argv, true)) {
    $sent = (int) $db->query("SELECT COUNT(*) FROM notification_deliveries WHERE plugin_slug='telegram-notifications' AND user_id=1 AND status='sent'")->fetchColumn();
    if ($sent < 1) throw new RuntimeException('No confirmed Telegram delivery exists.');
    echo json_encode(['ok' => true, 'channel' => 'telegram-notifications', 'enabled' => true, 'verified' => true, 'credentials' => 'encrypted', 'confirmed_deliveries' => $sent], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    exit;
}
$db->prepare("INSERT INTO user_notifications(user_id,type,title,message,url,created_at) VALUES (?,'system','Telegram channel active','Eduvixo notification delivery has been verified successfully.','/system/notifications',NOW())")->execute([(int) $user]);
$notice = (int) $db->lastInsertId();
$result = (new App\Core\NotificationDispatcher($db, $config, $root))->run(50);
$delivery = $db->prepare("SELECT status,last_error FROM notification_deliveries WHERE plugin_slug='telegram-notifications' AND user_id=? AND event_key=? ORDER BY id DESC LIMIT 1");
$delivery->execute([(int) $user, hash('sha256', 'user:' . $notice)]);
$state = $delivery->fetch();
if (!$state || $state['status'] !== 'sent' || $state['last_error'] !== null) throw new RuntimeException('Telegram did not confirm the live delivery.');
echo json_encode(['ok' => true, 'channel' => 'telegram-notifications', 'delivery' => 'sent', 'worker' => $result], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
