<?php

declare(strict_types=1);

require dirname(__DIR__).'/app/TelegramBrokerService.php';

use Eduvixo\Website\TelegramBrokerService;

$root=sys_get_temp_dir().'/eduvixo-telegram-'.bin2hex(random_bytes(6));mkdir($root,0700,true);$tests=0;$sent=[];$licensed=[];
$assert=static function(bool$ok,string$name)use(&$tests):void{if(!$ok)throw new RuntimeException('FAIL '.$name);$tests++;echo'PASS '.$name.PHP_EOL;};
$http=static function(string$method,string$url,array$headers,?string$body)use(&$sent,&$licensed):array{
    if(str_contains($url,'license.test')){parse_str((string)$body,$licensed);return[200,'{"data":{"valid":true}}'];}
    if(str_contains($url,'api.telegram.org')){$sent[]=json_decode((string)$body,true);return[200,'{"ok":true,"result":{"message_id":1}}'];}
    return[500,''];
};
$cfg=['bot_token'=>'123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghi_12345','bot_username'=>'EduvixoNotificationsBot','webhook_secret'=>'test_webhook_secret_abcdefghijklmnopqrstuvwxyz','storage'=>$root];
$service=new TelegramBrokerService($cfg,['endpoint'=>'https://license.test/'],str_repeat('a',64),$http);
$server=['HTTP_AUTHORIZATION'=>'Bearer abcdefghijklmnopqrstuvwxyz123456','HTTP_X_EDUVIXO_DOMAIN'=>'https://school.example','HTTP_X_EDUVIXO_VERSION'=>'1.0','HTTP_X_EDUVIXO_PRODUCT_NAME'=>base64_encode('Base CMS'),'HTTP_X_EDUVIXO_PRODUCT_MODEL'=>base64_encode('Universal Content Platform')];
try{
    $assert($service->ready(),'broker readiness');$start=$service->start($server,['user_id'=>7,'user_name'=>'Jane Owner']);$assert(($licensed['ProductName']??'')==='Base CMS'&&($licensed['ProductModel']??'')==='Universal Content Platform','licensed product identity');
    $assert(preg_match('#^https://t\.me/EduvixoNotificationsBot\?start=[A-Za-z0-9_-]{43}$#D',$start['connect_url'])===1,'deep link identity');
    $pending=$service->status($server,['user_id'=>7,'request_token'=>$start['request_token']]);$assert(!$pending['connected']&&$pending['request_status']==='pending','pending state isolation');
    try{$service->status($server,['user_id'=>8,'request_token'=>$start['request_token']]);throw new RuntimeException('Cross-user token accepted.');}catch(RuntimeException$error){$assert($error->getCode()===403,'cross-user token rejected');}
    $update=json_encode(['update_id'=>1,'message'=>['text'=>'/start '.$start['request_token'],'chat'=>['id'=>912345678,'type'=>'private'],'from'=>['id'=>912345678,'username'=>'JaneTelegram','first_name'=>'Jane']]],JSON_THROW_ON_ERROR);
    try{$service->acceptWebhook($update,'wrong_secret_value_abcdefghijklmnopqrstuvwxyz');throw new RuntimeException('Invalid webhook accepted.');}catch(RuntimeException$error){$assert($error->getCode()===403,'webhook secret enforced');}
    $service->acceptWebhook($update,$cfg['webhook_secret']);$connected=$service->status($server,['user_id'=>7,'request_token'=>$start['request_token']]);
    $assert($connected['connected']&&$connected['account']['username']==='JaneTelegram','private account connected');$assert($service->recipients($server)['user_ids']===[7],'installation recipient list');
    $key=hash('sha256','event:1');$assert($service->deliver($server,['user_id'=>7,'event_key'=>$key,'title'=>'Private title','body'=>'Private body'])['sent'],'central delivery');$assert($service->deliver($server,['user_id'=>7,'event_key'=>$key,'title'=>'Private title','body'=>'Private body'])['duplicate'],'delivery idempotency');
    $files=glob($root.'/bindings/*/*.json')?:[];$assert(count($files)===1&&!str_contains((string)file_get_contents($files[0]),'912345678'),'binding encrypted at rest');
    $other=$server;$other['HTTP_X_EDUVIXO_DOMAIN']='https://other.example';$assert($service->recipients($other)['user_ids']===[],'installation boundary');
    $service->disconnect($server,['user_id'=>7]);$assert(!$service->status($server,['user_id'=>7])['connected'],'user disconnect');
    $assert(count($sent)===2&&$sent[0]['chat_id']==='912345678'&&$sent[1]['text']==="Private title\nPrivate body",'Telegram payloads scoped');
    echo json_encode(['ok'=>true,'assertions'=>$tests],JSON_THROW_ON_ERROR).PHP_EOL;
}finally{
    $remove=static function(string$path)use(&$remove):void{if(is_dir($path)){foreach(scandir($path)?:[]as$item)if($item!=='.'&&$item!=='..')$remove($path.'/'.$item);rmdir($path);}elseif(is_file($path))unlink($path);};$remove($root);
}
