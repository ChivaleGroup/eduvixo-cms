<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$archive = '/root/eduvixo-deploy/wcag-2.2-finalize.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$sites = [
    ['name' => 'demo', 'root' => '/var/www/clients/client9/web121/web', 'owner' => 'web121', 'group' => 'client9', 'active_theme' => 'eduvixo'],
    ['name' => 'shoudu', 'root' => '/var/www/clients/client59/web119/web', 'owner' => 'web119', 'group' => 'client59', 'active_theme' => 'shoudu'],
];
$stamp = gmdate('Ymd-His'); $stage = '/root/eduvixo-deploy/wcag-final-' . $stamp; $backup = '/root/eduvixo-backups/wcag-final-pre-' . $stamp;
$run = static function (array $command, ?string $output = null): void { $process = proc_open($command, [0 => ['pipe','r'], 1 => $output ? ['file',$output,'wb'] : STDOUT, 2 => STDERR], $pipes); if (!is_resource($process)) throw new RuntimeException('Cannot start command.'); fclose($pipes[0]); if (proc_close($process) !== 0) throw new RuntimeException('Command failed: ' . implode(' ', $command)); };
$copy = static function (string $source, string $target, string $owner, string $group, int $mode = 0640): void { if (!is_file($source)) throw new RuntimeException('Missing staged file.'); $temporary = $target . '.final-' . bin2hex(random_bytes(4)); if (!copy($source,$temporary) || !chmod($temporary,$mode) || !chown($temporary,$owner) || !chgrp($temporary,$group) || !rename($temporary,$target)) throw new RuntimeException('Atomic publish failed: ' . $target); };
$clear = static function (): void { foreach (array_keys(getenv()) as $key) if (str_starts_with($key,'CMS_')) putenv($key); };
$load = static function (string $root) use ($clear): array { $clear(); return (static fn(string $path): array => require $path)($root . '/config/app.php'); };

if (!is_file($archive) || !mkdir($stage,0700,true) || !mkdir($backup,0700,true)) throw new RuntimeException('Finalize workspace unavailable.');
$run(['tar','-xzf',$archive,'-C',$stage]);
$files = ['config/marketplace.php','storage/marketplace/core-release.json','storage/marketplace/official-catalog.json','storage/marketplace/packages/eduvixo-core-1.0.7.zip','storage/marketplace/packages/shoudu-theme-1.1.3.zip','.wrk/system-update-bootstrap.php','.wrk/theme-update-bootstrap.php','.cms/source/app/Core/OfficialCatalog.php'];
foreach ($files as $file) if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete finalize archive: ' . $file);
require $stage . '/.cms/source/app/Core/OfficialCatalog.php';
$catalog = App\Core\OfficialCatalog::verify((string) file_get_contents($stage . '/storage/marketplace/official-catalog.json'));
$shoudu = array_values(array_filter((array) $catalog['products'], static fn(array $item): bool => ($item['slug'] ?? '') === 'shoudu'))[0] ?? [];
if (($catalog['core']['version'] ?? '') !== '1.0.7' || ($shoudu['version'] ?? '') !== '1.1.3' || !hash_equals((string)$catalog['core']['checksum'],hash_file('sha256',$stage.'/storage/marketplace/packages/eduvixo-core-1.0.7.zip')) || !hash_equals((string)$shoudu['package_checksum'],hash_file('sha256',$stage.'/storage/marketplace/packages/shoudu-theme-1.1.3.zip'))) throw new RuntimeException('Finalize signature verification failed.');

$run(['tar','-czf',$backup.'/website.tar.gz','-C',$website,'config/marketplace.php','storage/marketplace/core-release.json','storage/marketplace/official-catalog.json']);
foreach ($sites as $site) { $config=$load($site['root']);$db=(string)$config['database']['name'];if(!preg_match('/^[A-Za-z0-9_]+$/D',$db))throw new RuntimeException('Unsafe database identity.');$run(['mariadb-dump','--socket=/run/mysqld/mysqld.sock','--user=root','--single-transaction','--databases',$db],$backup.'/'.$site['name'].'.sql');$run(['tar','-czf',$backup.'/'.$site['name'].'-release.tar.gz','-C',$site['root'],'app/release.json']); }
$run(['tar','-czf',$backup.'/shoudu-theme.tar.gz','-C',$sites[1]['root'],'themes/shoudu']);
file_put_contents($backup.'/ROLLBACK.txt',"Preserve later writes. Prefer core and package-manager recovery archives. Restore the targeted files and matching SQL only when package metadata must be reverted. Verify owners, signatures, active themes, login and public pages before ending maintenance.\n",LOCK_EX);
foreach(glob($backup.'/*')?:[]as$file){chmod($file,0600);if(filesize($file)<100)throw new RuntimeException('Incomplete finalize backup.');echo 'BACKUP '.basename($file).' '.filesize($file).' '.hash_file('sha256',$file).PHP_EOL;}

foreach (['config/marketplace.php','storage/marketplace/core-release.json','storage/marketplace/official-catalog.json','storage/marketplace/packages/eduvixo-core-1.0.7.zip','storage/marketplace/packages/shoudu-theme-1.1.3.zip'] as $file) $copy($stage.'/'.$file,$website.'/'.$file,'web123','client9');
foreach($sites as$site){$bootstrap=$site['root'].'/storage/system-update-bootstrap-1.0.7.php';$copy($stage.'/.wrk/system-update-bootstrap.php',$bootstrap,$site['owner'],$site['group'],0600);try{$clear();$run(['runuser','-u',$site['owner'],'--','php',$bootstrap,$site['root'],'1.0.7']);}finally{if(is_file($bootstrap))unlink($bootstrap);}}
$site=$sites[1];$package=$site['root'].'/storage/shoudu-theme-1.1.3.zip';$bootstrap=$site['root'].'/storage/theme-update-bootstrap-1.1.3.php';$copy($stage.'/storage/marketplace/packages/shoudu-theme-1.1.3.zip',$package,$site['owner'],$site['group'],0600);$copy($stage.'/.wrk/theme-update-bootstrap.php',$bootstrap,$site['owner'],$site['group'],0600);try{$clear();$run(['runuser','-u',$site['owner'],'--','php',$bootstrap,$site['root'],$package,'1.1.3','shoudu']);}finally{if(is_file($bootstrap))unlink($bootstrap);if(is_file($package))unlink($package);}
foreach($sites as$site){$release=json_decode((string)file_get_contents($site['root'].'/app/release.json'),true,16,JSON_THROW_ON_ERROR);if(($release['version']??'')!=='1.0.7'||is_file($site['root'].'/storage/system-updates/maintenance.json'))throw new RuntimeException($site['name'].' core finalize failed.');$config=$load($site['root']);$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$config['database']['name'].';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$query=$db->prepare('SELECT value FROM settings WHERE `key`=?');$query->execute(['active_theme']);if(json_decode((string)$query->fetchColumn(),true)!==$site['active_theme'])throw new RuntimeException($site['name'].' active theme changed.');}
$theme=json_decode((string)file_get_contents($sites[1]['root'].'/themes/shoudu/theme.json'),true,16,JSON_THROW_ON_ERROR);if(($theme['version']??'')!=='1.1.3')throw new RuntimeException('Shoudu theme finalize failed.');
echo json_encode(['ok'=>true,'core'=>'1.0.7','shoudu_theme'=>'1.1.3','backup'=>$backup],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
