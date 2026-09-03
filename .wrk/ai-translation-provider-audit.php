<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$root='/var/www/clients/client9/web121/web';
foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);
$config=(static fn(string$path):array=>require$path)($root.'/config/app.php');
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$config['database']['name'].';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$raw=(string)$db->query("SELECT settings FROM installed_plugins WHERE slug='ai-translation-assistant' AND active=1")->fetchColumn();
$settings=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
require_once $root.'/app/Core/Secrets.php';
require_once $root.'/plugins/ai-translation-assistant/src/TranslationService.php';
$encrypted=(string)($settings['api_key_encrypted']??'');
if($encrypted==='')throw new RuntimeException('Encrypted provider credential is missing.');
$key=(new App\Core\Secrets((string)$config['secrets_key']))->decrypt($encrypted);
$settings['api_key']=$key;unset($settings['api_key_encrypted']);
try{
    (new Eduvixo\AITranslation\TranslationService())->test($settings);
    echo json_encode(['ok'=>true,'provider'=>$settings['provider']??null,'model'=>$settings['model']??null],JSON_THROW_ON_ERROR).PHP_EOL;
}catch(RuntimeException$error){
    echo json_encode(['ok'=>false,'provider'=>$settings['provider']??null,'model'=>$settings['model']??null,'message'=>$error->getMessage()],JSON_THROW_ON_ERROR).PHP_EOL;
    exit(2);
}finally{
    $key=str_repeat("\0",strlen($key));unset($settings['api_key']);
}
