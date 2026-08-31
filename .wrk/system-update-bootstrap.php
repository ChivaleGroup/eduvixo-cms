<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$root=rtrim((string)($argv[1]??''),'/');if(!is_file($root.'/config/app.php'))throw new RuntimeException('Invalid installation root.');
foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);
spl_autoload_register(static function(string $class)use($root):void{if(str_starts_with($class,'App\\')){$file=$root.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require $file;}});
$config=(static fn($path)=>require $path)($root.'/config/app.php');
$db=(new App\Core\Database($config['database']))->connection();
$actor=(int)$db->query("SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id AND r.active=1 JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id AND p.slug='system.owner' WHERE u.active=1 ORDER BY u.id LIMIT 1")->fetchColumn();
if(!$actor)throw new RuntimeException('No active owner can authorize this update.');
$service=new App\Core\SystemUpdate($db,$root,$config);$service->requestCheck();$first=$service->run();$state=$service->status();
$expected=(string)($argv[2]??'1.0.3');
if(empty($state['available'])||($state['latest']['version']??'')!==$expected)throw new RuntimeException('Signed '.$expected.' release is not available: '.($state['error']??'unknown catalog state'));
$service->requestInstall($expected,$actor);$result=$service->run();$final=$service->status();
if($service->version()!==$expected||($final['job']['status']??'')!=='completed')throw new RuntimeException('Core update did not complete: '.($final['job']['message']??'unknown status'));
echo json_encode(['ok'=>true,'site'=>parse_url($config['base_url'],PHP_URL_HOST),'version'=>$service->version(),'recovery'=>$final['job']['recovery']??null],JSON_THROW_ON_ERROR).PHP_EOL;
