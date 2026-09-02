<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$root=rtrim((string)($argv[1]??''),'/');$packageRoot=rtrim((string)($argv[2]??''),'/');
if(!is_file($root.'/config/app.php')||!is_dir($packageRoot))throw new RuntimeException('Invalid extension update target.');
foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);
spl_autoload_register(static function(string$class)use($root):void{if(str_starts_with($class,'App\\')){$file=$root.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require_once$file;}});
$config=(static fn(string$path):array=>require$path)($root.'/config/app.php');$db=(new App\Core\Database($config['database']))->connection();
$owner=$db->query("SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id AND r.active=1 JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id AND p.slug='system.owner' WHERE u.active=1 AND COALESCE(u.is_demo,0)=0 ORDER BY u.id LIMIT 1")->fetchColumn();
if(!$owner)throw new RuntimeException('No active non-demo Owner can authorize extension updates.');
$_SESSION=[];$manager=new App\Core\PackageManager($db,$root,(string)$config['engine_version'],(array)$config['marketplace']+['installation_url'=>(string)$config['base_url']]);
$releases=[
    ['theme','eduvixo','1.1.10','eduvixo-theme-1.1.10.zip'],
    ['theme','shoudu','1.1.4','shoudu-theme-1.1.4.zip'],
    ['addon','calendar','1.1.5','eduvixo-calendar-1.1.5.zip'],
    ['plugin','google-calendar','1.1.1','google-calendar-1.1.1.zip'],
    ['plugin','apple-calendar','1.1.1','apple-calendar-1.1.1.zip'],
    ['plugin','microsoft-365-calendar','1.1.1','microsoft-365-calendar-1.1.1.zip'],
    ['plugin','telegram-notifications','1.0.2-beta.2','telegram-notifications-1.0.2-beta.2.zip'],
    ['plugin','whatsapp-notifications','1.1.0-beta.2','whatsapp-notifications-1.1.0-beta.2.zip'],
    ['plugin','google-analytics','1.0.1','google-analytics-1.0.1.zip'],
    ['plugin','ai-translation-assistant','1.0.0-beta.2','ai-translation-assistant-1.0.0-beta.2.zip'],
];
$result=[];foreach($releases as[$type,$slug,$version,$file]){$current=$manager->package($type,$slug);if(!$current){$result[$slug]='not-installed';continue;}if(version_compare((string)$current['version'],$version,'>=')){$result[$slug]='current';continue;}$active=(bool)$current['active'];$path=$packageRoot.'/'.$file;if(!is_file($path))throw new RuntimeException('Missing package for installed extension: '.$file);$stage=$manager->stageLocalFile($path,(int)$owner);$installed=$manager->install((string)$stage['token'],(int)$owner);$verified=$manager->package($type,$slug);if(!$verified||$verified['version']!==$version||(bool)$verified['active']!==$active||$verified['name']!==$installed['name'])throw new RuntimeException('Extension verification failed: '.$slug);$result[$slug]=$installed['name'].' '.$installed['version'];}
echo json_encode(['ok'=>true,'updates'=>$result],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
