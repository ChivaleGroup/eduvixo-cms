<?php

declare(strict_types=1);

namespace Eduvixo\Website;

use Closure;
use RuntimeException;

final class TelegramBrokerService
{
    private readonly string $key;
    private readonly Closure $http;

    public function __construct(private readonly array $config,private readonly array $license,string $secret,?Closure $http=null)
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$secret))throw new RuntimeException('Telegram connection encryption is unavailable.');
        $this->key=hex2bin($secret)?:throw new RuntimeException('Telegram connection encryption is unavailable.');
        $this->http=$http??$this->curl(...);
    }

    public function ready():bool
    {
        return preg_match('/^[0-9]{6,15}:[A-Za-z0-9_-]{30,100}$/D',(string)($this->config['bot_token']??''))===1
            &&preg_match('/^[A-Za-z0-9_]{5,32}$/D',(string)($this->config['bot_username']??''))===1
            &&preg_match('/^[A-Za-z0-9_-]{32,256}$/D',(string)($this->config['webhook_secret']??''))===1;
    }

    public function start(array$server,array$input):array
    {
        $this->requireReady();$domain=$this->authenticate($server);$user=$this->user($input['user_id']??null);$name=mb_substr(trim((string)($input['user_name']??'')),0,120);
        $this->rate('start',$domain."\0".$user,12,3600);$token=$this->token();$expires=time()+600;
        $this->write($this->statePath('starts',$token),['domain'=>$domain,'user_id'=>$user,'user_name'=>$name,'status'=>'pending','expires'=>$expires],true);
        return['connect_url'=>'https://t.me/'.(string)$this->config['bot_username'].'?start='.rawurlencode($token),'request_token'=>$token,'expires_in'=>600];
    }

    public function status(array$server,array$input):array
    {
        $domain=$this->authenticate($server);$user=$this->user($input['user_id']??null);$token=trim((string)($input['request_token']??''));
        if($token!==''){$record=$this->read($this->statePath('starts',$token));if($record&&(!hash_equals((string)($record['domain']??''),$domain)||(int)($record['user_id']??0)!==$user))throw new RuntimeException('The Telegram connection request belongs to another user.',403);if($record&&(int)($record['expires']??0)<time())$record['status']='expired';}
        $binding=$this->binding($domain,$user);return['connected'=>$binding!==null,'account'=>$binding?['username'=>(string)($binding['username']??''),'name'=>(string)($binding['name']??''),'connected_at'=>(string)($binding['connected_at']??'')]:null,'request_status'=>(string)($record['status']??($token!==''?'expired':''))];
    }

    public function recipients(array$server):array
    {
        $domain=$this->authenticate($server);$users=[];$directory=$this->bindingDirectory($domain,false);
        foreach($directory&&is_dir($directory)?glob($directory.'/*.json')?:[]:[]as$file){$binding=$this->openRecord($file);if($binding&&hash_equals((string)($binding['domain']??''),$domain)&&($id=(int)($binding['user_id']??0))>0)$users[]=$id;}
        sort($users,SORT_NUMERIC);return['user_ids'=>array_values(array_unique($users))];
    }

    public function disconnect(array$server,array$input):array
    {
        $domain=$this->authenticate($server);$user=$this->user($input['user_id']??null);$path=$this->bindingPath($domain,$user,false);if($path&&is_file($path))@unlink($path);
        return['connected'=>false];
    }

    public function deliver(array$server,array$input):array
    {
        $this->requireReady();$domain=$this->authenticate($server);$user=$this->user($input['user_id']??null);$binding=$this->binding($domain,$user);
        if(!$binding)throw new RuntimeException('This user has not connected Telegram.',404);
        $event=strtolower(trim((string)($input['event_key']??'')));$title=trim((string)($input['title']??''));$body=trim((string)($input['body']??''));
        if(!preg_match('/^[a-f0-9]{64}$/D',$event)||$title===''||mb_strlen($title)>180||$body===''||mb_strlen($body)>3500)throw new RuntimeException('The Telegram notification is invalid.',422);
        $this->rate('deliver',$domain,5000,3600);$path=$this->statePath('deliveries',hash_hmac('sha256',$domain."\0".$user."\0".$event,$this->key));
        if(is_file($path)){if(($this->read($path)['status']??'')==='sent')return['sent'=>true,'duplicate'=>true];throw new RuntimeException('The Telegram delivery result requires review.',409);}
        $this->write($path,['status'=>'processing','created_at'=>time()],true);
        try{$this->telegram('sendMessage',['chat_id'=>(string)$binding['chat_id'],'text'=>$title."\n".$body,'disable_web_page_preview'=>true]);$this->write($path,['status'=>'sent','sent_at'=>time()]);}
        catch(\Throwable$error){$this->write($path,['status'=>'unknown','updated_at'=>time()]);throw$error;}
        return['sent'=>true,'duplicate'=>false];
    }

    public function acceptWebhook(string$body,string$secret):void
    {
        $this->requireReady();if(!hash_equals((string)$this->config['webhook_secret'],$secret))throw new RuntimeException('Telegram webhook authorization failed.',403);
        if(strlen($body)>65536)throw new RuntimeException('Telegram webhook payload is too large.',413);
        try{$update=json_decode($body,true,32,JSON_THROW_ON_ERROR);}catch(\JsonException){throw new RuntimeException('Telegram webhook payload is invalid.',422);}
        $message=is_array($update['message']??null)?$update['message']:[];$text=trim((string)($message['text']??''));$chat=is_array($message['chat']??null)?$message['chat']:[];$from=is_array($message['from']??null)?$message['from']:[];
        if(($chat['type']??'')!=='private'||!preg_match('/^\/start(?:@[A-Za-z0-9_]{5,32})?\s+([A-Za-z0-9_-]{43})$/D',$text,$match))return;
        $chatId=(int)($chat['id']??0);$telegramUser=(int)($from['id']??0);if($chatId<1||$telegramUser<1)return;
        $path=$this->statePath('starts',$match[1]);if(!is_file($path))return;$handle=@fopen($path,'r+');if(!is_resource($handle)||!flock($handle,LOCK_EX)){if(is_resource($handle))fclose($handle);return;}
        try{
            $raw=(string)stream_get_contents($handle);$record=json_decode($raw,true);if(!is_array($record)||($record['status']??'')!=='pending'||(int)($record['expires']??0)<time())return;
            $domain=$this->domain((string)($record['domain']??''));$user=$this->user($record['user_id']??null);$username=preg_match('/^[A-Za-z0-9_]{5,32}$/D',(string)($from['username']??''))?(string)$from['username']:'';$name=mb_substr(trim((string)($from['first_name']??'').' '.(string)($from['last_name']??'')),0,120);
            $this->saveBinding($domain,$user,['chat_id'=>$chatId,'telegram_user_id'=>$telegramUser,'username'=>$username,'name'=>$name,'connected_at'=>gmdate('Y-m-d H:i:s')]);
            $record['status']='connected';$record['connected_at']=time();rewind($handle);ftruncate($handle,0);$json=json_encode($record,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);fwrite($handle,$json);fflush($handle);
            $host=(string)(parse_url($domain,PHP_URL_HOST)?:$domain);$this->telegram('sendMessage',['chat_id'=>(string)$chatId,'text'=>'Telegram notifications connected securely to '.$host.'.','disable_web_page_preview'=>true]);
        }finally{flock($handle,LOCK_UN);fclose($handle);}
    }

    public function provisionBinding(string$domain,int$user,int$chatId,int$telegramUser=0,string$username='',string$name=''):void
    {
        $this->saveBinding($this->domain($domain),$this->user($user),['chat_id'=>$chatId,'telegram_user_id'=>$telegramUser?:$chatId,'username'=>$username,'name'=>$name,'connected_at'=>gmdate('Y-m-d H:i:s')]);
    }

    private function authenticate(array$server):string
    {
        $header=(string)($server['HTTP_AUTHORIZATION']??$server['REDIRECT_HTTP_AUTHORIZATION']??'');if(!preg_match('/^Bearer ([A-Za-z0-9._-]{1,128})$/D',$header,$match))throw new RuntimeException('Installation authorization is required.',401);
        $license=$match[1];$domain=$this->domain((string)($server['HTTP_X_EDUVIXO_DOMAIN']??''));$productName=$this->identity((string)($server['HTTP_X_EDUVIXO_PRODUCT_NAME']??''),'Eduvixo');$productModel=$this->identity((string)($server['HTTP_X_EDUVIXO_PRODUCT_MODEL']??''),'Education Digital Experience & Communication Platform');$cache=$this->statePath('license-cache',hash_hmac('sha256',$license."\0".$domain."\0".$productName."\0".$productModel,$this->key));$cached=$this->read($cache);
        if($cached&&(int)($cached['expires']??0)>=time())return$domain;$version=preg_match('/^[0-9A-Za-z.-]{1,30}$/D',(string)($server['HTTP_X_EDUVIXO_VERSION']??''),$m)?$m[0]:'1.0';
        $payload=http_build_query(['type'=>'software','LicenseKey'=>$license,'DomainUrl'=>$domain,'ProductName'=>$productName,'ProductModel'=>$productModel,'ProductVersion'=>$version],'','&',PHP_QUERY_RFC3986);
        [$status,$response]=($this->http)('POST',(string)$this->license['endpoint'],['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],$payload);$data=json_decode($response,true);
        if($status<200||$status>=300||!is_array($data)||!empty($data['error'])||empty($data['data']))throw new RuntimeException('Installation license authorization failed.',403);
        $this->write($cache,['expires'=>time()+300]);return$domain;
    }

    private function telegram(string$method,array$payload):array
    {
        [$status,$body]=($this->http)('POST','https://api.telegram.org/bot'.rawurlencode((string)$this->config['bot_token']).'/'.$method,['Content-Type: application/json','Accept: application/json'],json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));$data=json_decode($body,true);
        if($status<200||$status>=300||!is_array($data)||empty($data['ok']))throw new RuntimeException('Telegram did not confirm the request.',502);return$data;
    }

    private function curl(string$method,string$url,array$headers,?string$body):array
    {
        $curl=curl_init($url);if($curl===false)throw new RuntimeException('Secure HTTP transport is unavailable.',503);curl_setopt_array($curl,[CURLOPT_POST=>$method==='POST',CURLOPT_POSTFIELDS=>$method==='POST'?($body??''):null,CURLOPT_HTTPHEADER=>$headers,CURLOPT_USERAGENT=>'Eduvixo-Telegram-Broker/1.0',CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS]);$response=curl_exec($curl);$error=curl_error($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);if($error!==''||!is_string($response))throw new RuntimeException('External notification service is unavailable.',503);return[$status,$response];
    }

    private function saveBinding(string$domain,int$user,array$data):void
    {
        $chat=(int)($data['chat_id']??0);$telegram=(int)($data['telegram_user_id']??0);if($chat<1||$telegram<1)throw new RuntimeException('Telegram returned an invalid private account.',422);
        $record=['domain'=>$domain,'user_id'=>$user,'chat_id'=>$chat,'telegram_user_id'=>$telegram,'username'=>preg_match('/^[A-Za-z0-9_]{5,32}$/D',(string)($data['username']??''))?(string)$data['username']:'','name'=>mb_substr(trim((string)($data['name']??'')),0,120),'connected_at'=>(string)($data['connected_at']??gmdate('Y-m-d H:i:s'))];
        $this->write($this->bindingPath($domain,$user,true),['sealed'=>$this->seal(json_encode($record,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES))]);
    }

    private function binding(string$domain,int$user):?array{$path=$this->bindingPath($domain,$user,false);return$path?$this->openRecord($path):null;}
    private function openRecord(string$file):?array{$row=$this->read($file);if(!$row||!is_string($row['sealed']??null))return null;try{$data=json_decode($this->open($row['sealed']),true,16,JSON_THROW_ON_ERROR);return is_array($data)?$data:null;}catch(\Throwable){return null;}}
    private function bindingDirectory(string$domain,bool$create):?string{$dir=rtrim((string)$this->config['storage'],'/\\').'/bindings/'.hash_hmac('sha256',$domain,$this->key);if($create&&!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Telegram connection storage is unavailable.',503);return is_dir($dir)?$dir:null;}
    private function bindingPath(string$domain,int$user,bool$create):?string{$dir=$this->bindingDirectory($domain,$create);return$dir?$dir.'/'.hash_hmac('sha256',(string)$user,$this->key).'.json':null;}
    private function statePath(string$type,string$token):string{if(!preg_match('/^[a-z-]{3,40}$/D',$type)||!preg_match('/^[A-Za-z0-9_-]{32,64}$/D',$token))throw new RuntimeException('Telegram connection token is invalid.',422);$dir=rtrim((string)$this->config['storage'],'/\\').'/'.$type;if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Telegram connection storage is unavailable.',503);return$dir.'/'.hash_hmac('sha256',$token,$this->key).'.json';}
    private function write(string$path,array$payload,bool$exclusive=false):void{$json=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);if($exclusive){$handle=@fopen($path,'x');if(!is_resource($handle))throw new RuntimeException('Telegram connection state could not be stored.',503);try{if(fwrite($handle,$json)!==strlen($json)||!fflush($handle))throw new RuntimeException('Telegram connection state could not be stored.',503);}finally{fclose($handle);} }else{$tmp=$path.'.'.bin2hex(random_bytes(5)).'.tmp';if(file_put_contents($tmp,$json,LOCK_EX)!==strlen($json)||!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Telegram connection state could not be stored.',503);}}@chmod($path,0640);}
    private function read(string$path):array{if(!is_file($path))return[];try{$data=json_decode((string)file_get_contents($path),true,16,JSON_THROW_ON_ERROR);return is_array($data)?$data:[];}catch(\Throwable){return[];}}
    private function seal(string$value):string{$nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);return rtrim(strtr(base64_encode($nonce.sodium_crypto_secretbox($value,$nonce,$this->key)),'+/','-_'),'=');}
    private function open(string$value):string{$encoded=strtr($value,'-_','+/').str_repeat('=',(4-strlen($value)%4)%4);$raw=base64_decode($encoded,true);if(!is_string($raw)||strlen($raw)<=SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)throw new RuntimeException('Telegram connection record is invalid.',422);$nonce=substr($raw,0,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);$plain=sodium_crypto_secretbox_open(substr($raw,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),$nonce,$this->key);if(!is_string($plain))throw new RuntimeException('Telegram connection record cannot be decrypted.',422);return$plain;}
    private function rate(string$scope,string$subject,int$limit,int$window):void{$path=$this->statePath('rate-'.$scope,hash_hmac('sha256',$subject,$this->key));$handle=@fopen($path,'c+');if(!is_resource($handle)||!flock($handle,LOCK_EX)){if(is_resource($handle))fclose($handle);throw new RuntimeException('Rate-limit protection is unavailable.',503);}try{$now=time();$entries=json_decode((string)stream_get_contents($handle),true);$entries=array_values(array_filter(array_map('intval',is_array($entries)?$entries:[]),static fn(int$at):bool=>$at>$now-$window));if(count($entries)>=$limit)throw new RuntimeException('Too many Telegram requests.',429);$entries[]=$now;rewind($handle);ftruncate($handle,0);fwrite($handle,json_encode($entries,JSON_THROW_ON_ERROR));fflush($handle);}finally{flock($handle,LOCK_UN);fclose($handle);}@chmod($path,0640);}
    private function domain(string$value):string{$value=rtrim(trim($value),'/');$parts=parse_url($value);if(!filter_var($value,FILTER_VALIDATE_URL)||!is_array($parts)||strtolower((string)($parts['scheme']??''))!=='https'||empty($parts['host'])||array_intersect(['user','pass','query','fragment'],array_keys($parts)))throw new RuntimeException('Installation identity is invalid.',401);return$value;}
    private function identity(string$value,string$fallback):string{if($value==='')return$fallback;$decoded=base64_decode($value,true);if(!is_string($decoded)||$decoded===''||strlen($decoded)>160||preg_match('/[\x00-\x1F\x7F]/',$decoded))throw new RuntimeException('Installation product identity is invalid.',401);return$decoded;}
    private function user(mixed$value):int{$id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false)throw new RuntimeException('The CMS user identity is invalid.',422);return(int)$id;}
    private function token():string{return rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=');}
    private function requireReady():void{if(!$this->ready())throw new RuntimeException('The central Telegram service is not configured.',503);}
}
