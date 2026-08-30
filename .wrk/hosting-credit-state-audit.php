<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$sites = [
    ['name'=>'demo','root'=>'/var/www/clients/client9/web121/web','user'=>'web121'],
    ['name'=>'shoudu','root'=>'/var/www/clients/client59/web119/web','user'=>'web119'],
];
foreach ($sites as $site) {
    $root=$site['root'];
    foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);
    spl_autoload_register(static function(string$class)use($root):void{if(str_starts_with($class,'App\\')){$file=$root.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require_once$file;}});
    $config=(static fn(string$path):array=>require$path)($root.'/config/app.php');
    $release=json_decode((string)file_get_contents($root.'/app/release.json'),true,16,JSON_THROW_ON_ERROR);
    $theme=json_decode((string)file_get_contents($root.'/themes/eduvixo/theme.json'),true,32,JSON_THROW_ON_ERROR);
    $license='unknown';
    try{(new App\Core\LicenseService($config['license'],$config['engine_version']))->enforce($config['base_url']);$license='valid';}catch(Throwable$error){$license=get_class($error).': '.$error->getMessage();}
    $db=(new App\Core\Database($config['database']))->connection();
    $update=(new App\Core\SystemUpdate($db,$root,$config))->status();
    echo json_encode(['name'=>$site['name'],'url'=>$config['base_url'],'core'=>$release['version']??null,'theme'=>$theme['version']??null,'license'=>$license,'available'=>$update['available']??null,'latest'=>$update['latest']['version']??null,'job'=>$update['job']['status']??null,'message'=>$update['job']['message']??null,'maintenance'=>is_file($root.'/storage/system-updates/maintenance.json')],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
}
$website='/var/www/clients/client9/web123/web';
echo json_encode(['website'=>['ecosystem'=>str_contains((string)file_get_contents($website.'/app/views/partials/ecosystem.php'),'Premium extensions'),'hosting_public'=>str_contains((string)file_get_contents($website.'/app/views/layout.php'),'Hosting provided by'),'core_release'=>json_decode((string)file_get_contents($website.'/storage/marketplace/core-release.json'),true)['version']??null]],JSON_THROW_ON_ERROR).PHP_EOL;
