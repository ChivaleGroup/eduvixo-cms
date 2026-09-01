<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$stage = '/root/eduvixo-deploy/meta-connect-hotfix';
$sites = [
    ['name' => 'demo', 'root' => '/var/www/clients/client9/web121/web', 'owner' => 'web121', 'group' => 'client9'],
    ['name' => 'shoudu', 'root' => '/var/www/clients/client59/web119/web', 'owner' => 'web119', 'group' => 'client59'],
];
$files = [
    'app/Views/console-notification-settings.php',
    'app/Views/console.php',
    'config/app.php',
    'public/theme/eduvixo-ui.js',
];
$run = static function (array $command): void {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed.');
};
$publish = static function (string $source, string $target, string $owner, string $group): void {
    if (!is_file($source) || !is_file($target)) throw new RuntimeException('A deployment file is unavailable.');
    $temp = $target . '.meta-connect-' . bin2hex(random_bytes(5));
    if (!copy($source, $temp) || !chmod($temp, 0640) || !chown($temp, $owner) || !chgrp($temp, $group) || !rename($temp, $target)) {
        if (is_file($temp)) unlink($temp);
        throw new RuntimeException('Atomic deployment failed.');
    }
};

foreach ($files as $file) if (!is_file($stage . '/' . $file)) throw new RuntimeException('Deployment stage is incomplete.');
if (!str_contains((string) file_get_contents($stage . '/app/Views/console-notification-settings.php'), 'data-eduvixo-native')) throw new RuntimeException('Native Meta action marker is missing.');
if (!str_contains((string) file_get_contents($stage . '/public/theme/eduvixo-ui.js'), "event.submitter?.hasAttribute('data-eduvixo-native')")) throw new RuntimeException('Native form bypass is missing.');
if (!str_contains((string) file_get_contents($stage . '/app/Views/console.php'), 'eduvixo-ui.js?v=20260902-meta-connect-1')) throw new RuntimeException('Cache version was not updated.');
if (!str_contains((string) file_get_contents($stage . '/config/app.php'), "'whatsapp_onboarding_url'")) throw new RuntimeException('WhatsApp onboarding configuration is missing.');

$backup = '/root/eduvixo-backups/meta-connect-pre-' . gmdate('Ymd-His');
if (!mkdir($backup, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
foreach ($sites as $site) {
    $archive = $backup . '/' . $site['name'] . '.tar.gz';
    $run(['tar', '-czf', $archive, '-C', $site['root'], ...$files]);
    chmod($archive, 0600);
    if (!is_file($archive) || filesize($archive) < 100) throw new RuntimeException('Backup verification failed.');
    foreach ($files as $file) $publish($stage . '/' . $file, $site['root'] . '/' . $file, $site['owner'], $site['group']);
    $run(['php', '-l', $site['root'] . '/app/Views/console-notification-settings.php']);
    $run(['php', '-l', $site['root'] . '/app/Views/console.php']);
    $run(['php', '-l', $site['root'] . '/config/app.php']);
    $run(['node', '--check', $site['root'] . '/public/theme/eduvixo-ui.js']);
    foreach(array_keys(getenv()) as $key) if(str_starts_with($key,'CMS_')) putenv($key);
    $config=(static fn(string $file):array=>require $file)($site['root'].'/config/app.php');
    if (($config['integrations']['whatsapp_onboarding_url']??'') !== 'https://www.eduvixo.com/api/integrations/whatsapp/onboarding') throw new RuntimeException('WhatsApp onboarding URL verification failed.');
}
$run(['apache2ctl', 'configtest']);
echo json_encode(['ok' => true, 'backup' => $backup], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
