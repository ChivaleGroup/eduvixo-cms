<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$demo = ['root'=>'/var/www/clients/client9/web121/web','owner'=>'web121','group'=>'client9','host'=>'demo.eduvixo.com'];
$shoudu = ['root'=>'/var/www/clients/client59/web119/web','host'=>'shoudu.lrn.asia'];
$package = '/var/www/clients/client9/web123/web/storage/marketplace/packages/ai-translation-assistant-1.0.1.zip';
$keyFile = '/root/eduvixo-deploy/openai-api.txt';
$expected = 'bbc4340e8fcf883c727bbcc8474f0a2b96bef573b67002ed865c7065a9ed7f83';
$run = static function (array $command): void {
    $process = proc_open($command, [0=>['pipe','r'],1=>STDOUT,2=>STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]); if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: '.$command[0]);
};
$load = static function (string $root): array {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    return (static fn(string $path): array => require $path)($root.'/config/app.php');
};
$db = static function (array $config): PDO {
    $name = (string) $config['database']['name'];
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $name)) throw new RuntimeException('Unsafe database name.');
    return new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$name.';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
};
try {
    foreach ([$demo,$shoudu] as $site) if (realpath($site['root']) !== $site['root'] || !is_file($site['root'].'/public/index.php')) throw new RuntimeException('Unexpected CMS root: '.$site['host']);
    if (!is_file($package) || !hash_equals($expected, hash_file('sha256', $package))) throw new RuntimeException('Stable package integrity failure.');
    $rawKey = is_file($keyFile) ? (string) file_get_contents($keyFile) : '';
    if (!preg_match('/^OPENAI_API_KEY=(.+)$/m', $rawKey, $match) || strlen(trim($match[1])) < 20) throw new RuntimeException('Deployment key is unavailable.');
    $key = trim($match[1]);
    $shouduConfig = $load($shoudu['root']); $shouduDb = $db($shouduConfig);
    $shouduCheck = $shouduDb->prepare("SELECT 1 FROM extension_packages WHERE type='plugin' AND slug=? LIMIT 1"); $shouduCheck->execute(['ai-translation-assistant']);
    if ($shouduCheck->fetchColumn() || is_dir($shoudu['root'].'/plugins/ai-translation-assistant')) throw new RuntimeException('Shoudu must remain without AI Translation Assistant.');
    $demoConfig = $load($demo['root']); $demoDb = $db($demoConfig);
    $backup = '/root/eduvixo-backups/ai-translation-stable-pre-'.gmdate('Ymd-His');
    if (!mkdir($backup, 0700, true)) throw new RuntimeException('Cannot create deployment backup.');
    $before = $demoDb->prepare("SELECT type,slug,name,version,active,signature_status,install_path,package_checksum FROM extension_packages WHERE type='plugin' AND slug=? LIMIT 1"); $before->execute(['ai-translation-assistant']);
    $settings = $demoDb->prepare('SELECT settings FROM installed_plugins WHERE slug=? LIMIT 1'); $settings->execute(['ai-translation-assistant']);
    file_put_contents($backup.'/state.json', json_encode(['package'=>$before->fetch()?:null,'settings'=>$settings->fetchColumn()?:null], JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR), LOCK_EX);
    file_put_contents($backup.'/ROLLBACK.txt', "Use the PackageManager rollback entry for an upgraded package. For a first installation, disable and uninstall AI Translation Assistant through Marketplace. Restore the encrypted installed_plugins settings value from state.json only when reverting configuration, and never expose or copy it to another installation. Shoudu was not modified.\n", LOCK_EX);
    chmod($backup.'/state.json',0600); chmod($backup.'/ROLLBACK.txt',0600);
    if (is_dir($demo['root'].'/plugins/ai-translation-assistant')) $run(['tar','-czf',$backup.'/plugin-files.tar.gz','-C',$demo['root'],'plugins/ai-translation-assistant']);
    $private = $demo['root'].'/storage/ai-translation-assistant-1.0.1.zip';
    if (!copy($package, $private) || !chmod($private, 0600) || !chown($private, $demo['owner']) || !chgrp($private, $demo['group'])) throw new RuntimeException('Cannot stage package for demo.');
    try { $run(['runuser','-u',$demo['owner'],'--','php',$demo['root'].'/scripts/extension-package.php','install',$private,'--activate']); }
    finally { if (is_file($private)) unlink($private); }
    require_once $demo['root'].'/app/Core/Secrets.php';
    $encrypted = (new App\Core\Secrets((string) $demoConfig['secrets_key']))->encrypt($key);
    $values = ['provider'=>'openai-compatible','endpoint'=>'https://api.openai.com/v1','model'=>'gpt-5-mini','api_key_encrypted'=>$encrypted,'verified_at'=>null];
    $update = $demoDb->prepare('UPDATE installed_plugins SET settings=? WHERE slug=? AND active=1');
    $update->execute([json_encode($values, JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'ai-translation-assistant']);
    $after = $demoDb->prepare("SELECT type,slug,name,version,active,signature_status,install_path,package_checksum FROM extension_packages WHERE type='plugin' AND slug=? LIMIT 1"); $after->execute(['ai-translation-assistant']); $installed = $after->fetch();
    if (($installed['version']??'') !== '1.0.1' || ($installed['signature_status']??'') !== 'verified' || (int)($installed['active']??0) !== 1 || !hash_equals($expected,(string)($installed['package_checksum']??''))) throw new RuntimeException('Demo plugin verification failed.');
    $stored = json_decode((string)$demoDb->query("SELECT settings FROM installed_plugins WHERE slug='ai-translation-assistant'")->fetchColumn(),true,16,JSON_THROW_ON_ERROR);
    $decrypted = !empty($stored['api_key_encrypted']) ? (new App\Core\Secrets((string) $demoConfig['secrets_key']))->decrypt((string) $stored['api_key_encrypted']) : '';
    if (($stored['provider']??'') !== 'openai-compatible' || ($stored['endpoint']??'') !== 'https://api.openai.com/v1' || ($stored['model']??'') !== 'gpt-5-mini' || isset($stored['api_key']) || !hash_equals($key, $decrypted)) throw new RuntimeException('Demo credential isolation verification failed.');
    $decrypted = str_repeat("\0", strlen($decrypted));
    $run(['php','-l',$demo['root'].'/plugins/ai-translation-assistant/src/TranslationService.php']);
    $run(['php','-l',$demo['root'].'/plugins/ai-translation-assistant/src/TranslationController.php']);
    echo json_encode(['ok'=>true,'site'=>$demo['host'],'version'=>$installed['version'],'channel'=>'stable','provider'=>'openai-compatible','model'=>'gpt-5-mini','verified'=>false,'backup'=>$backup,'shoudu'=>'unchanged'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
} finally {
    if (is_file($keyFile)) { file_put_contents($keyFile, '', LOCK_EX); unlink($keyFile); }
    if (isset($key)) $key = str_repeat("\0", strlen($key));
}
