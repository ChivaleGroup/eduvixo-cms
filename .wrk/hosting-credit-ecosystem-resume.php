<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$stage='/root/eduvixo-deploy/hosting-credit-ecosystem-20260830-143946';
$backup='/root/eduvixo-backups/hosting-credit-ecosystem-pre-20260830-143946';
$sites=[
    ['name'=>'demo','root'=>'/var/www/clients/client9/web121/web','owner'=>'web121','group'=>'client9'],
    ['name'=>'shoudu','root'=>'/var/www/clients/client59/web119/web','owner'=>'web119','group'=>'client59'],
];
$run=static function(array$command):void{$spec=[0=>['pipe','r'],1=>STDOUT,2=>STDERR];$process=proc_open($command,$spec,$pipes);if(!is_resource($process))throw new RuntimeException('Cannot start resume command.');fclose($pipes[0]);if(proc_close($process)!==0)throw new RuntimeException('Resume command failed: '.implode(' ',$command));};
$copy=static function(string$source,string$target,string$owner,string$group):void{if(!is_file($source))throw new RuntimeException('Missing staged resume file: '.$source);$temporary=$target.'.resume-new-'.bin2hex(random_bytes(4));if(!copy($source,$temporary)||!chmod($temporary,0600)||!chown($temporary,$owner)||!chgrp($temporary,$group)||!rename($temporary,$target))throw new RuntimeException('Cannot publish resume file.');};
$load=static function(string$root):array{foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);return(static fn(string$path):array=>require$path)($root.'/config/app.php');};
spl_autoload_register(static function(string$class)use($sites):void{if(str_starts_with($class,'App\\')){$file=$sites[0]['root'].'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require_once$file;}});
foreach(['ROLLBACK.txt','demo.sql','demo.tar.gz','shoudu.sql','shoudu.tar.gz','website.tar.gz']as$file)if(!is_file($backup.'/'.$file)||filesize($backup.'/'.$file)<100)throw new RuntimeException('Verified rollback checkpoint is incomplete: '.$file);
foreach(['.wrk/system-update-bootstrap.php','.wrk/theme-update-bootstrap.php','storage/marketplace/packages/eduvixo-theme-1.1.7.zip']as$file)if(!is_file($stage.'/'.$file))throw new RuntimeException('Deployment stage is incomplete: '.$file);
foreach($sites as$site){
    $config=$load($site['root']);
    (new App\Core\LicenseService($config['license'],$config['engine_version']))->enforce($config['base_url']);
    echo 'LICENSE_OK '.$site['name'].PHP_EOL;
    $release=json_decode((string)file_get_contents($site['root'].'/app/release.json'),true,16,JSON_THROW_ON_ERROR);
    if(($release['version']??'')!=='1.0.5'){$coreBootstrap=$site['root'].'/storage/system-update-bootstrap-1.0.5.php';$copy($stage.'/.wrk/system-update-bootstrap.php',$coreBootstrap,$site['owner'],$site['group']);try{$run(['runuser','-u',$site['owner'],'--','php',$coreBootstrap,$site['root'],'1.0.5']);}finally{if(is_file($coreBootstrap))unlink($coreBootstrap);}}else echo 'CORE_ALREADY_CURRENT '.$site['name'].PHP_EOL;
    $theme=json_decode((string)file_get_contents($site['root'].'/themes/eduvixo/theme.json'),true,32,JSON_THROW_ON_ERROR);
    if(($theme['version']??'')!=='1.1.7'){$themePackage=$site['root'].'/storage/eduvixo-theme-1.1.7.zip';$themeBootstrap=$site['root'].'/storage/theme-update-bootstrap-1.1.7.php';$copy($stage.'/storage/marketplace/packages/eduvixo-theme-1.1.7.zip',$themePackage,$site['owner'],$site['group']);$copy($stage.'/.wrk/theme-update-bootstrap.php',$themeBootstrap,$site['owner'],$site['group']);try{$run(['runuser','-u',$site['owner'],'--','php',$themeBootstrap,$site['root'],$themePackage,'1.1.7']);}finally{if(is_file($themeBootstrap))unlink($themeBootstrap);if(is_file($themePackage))unlink($themePackage);}}else echo 'THEME_ALREADY_CURRENT '.$site['name'].PHP_EOL;
}
foreach($sites as$site){$config=$load($site['root']);$release=json_decode((string)file_get_contents($site['root'].'/app/release.json'),true,16,JSON_THROW_ON_ERROR);$theme=json_decode((string)file_get_contents($site['root'].'/themes/eduvixo/theme.json'),true,32,JSON_THROW_ON_ERROR);if(($release['version']??'')!=='1.0.5'||($theme['version']??'')!=='1.1.7'||is_file($site['root'].'/storage/system-updates/maintenance.json'))throw new RuntimeException($site['name'].' resume verification failed.');(new App\Core\LicenseService($config['license'],$config['engine_version']))->enforce($config['base_url']);echo 'VERIFIED '.$site['name'].' core=1.0.5 theme=1.1.7'.PHP_EOL;}
echo json_encode(['ok'=>true,'resumed_from'=>$backup,'sites'=>array_column($sites,'name')],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
