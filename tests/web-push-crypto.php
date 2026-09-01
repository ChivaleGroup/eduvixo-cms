<?php
declare(strict_types=1);

require dirname(__DIR__).'/.cms/source/app/Core/WebPushKeyStore.php';
require dirname(__DIR__).'/.cms/source/app/Core/WebPushCrypto.php';

if(PHP_OS_FAMILY==='Windows'&&getenv('OPENSSL_CONF')===false){foreach(['D:/Programs/Git/mingw64/etc/ssl/openssl.cnf','D:/Programs/Git/usr/ssl/openssl.cnf']as$config)if(is_file($config)){putenv('OPENSSL_CONF='.$config);break;}}

use App\Core\WebPushCrypto;
use App\Core\WebPushKeyStore;

$decode=static fn(string$value):string=>WebPushKeyStore::decode($value);
// Published RFC 8291 Appendix A test material only; it is not an Eduvixo signing or installation key.
$private=$decode('yfWPiYE-n46HLnH0KqZOF1fJJU3MYrct3AELtAQ-oRw');
$public=$decode('BP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A8');
$der=hex2bin('30770201010420').$private.hex2bin('a00a06082a8648ce3d030107a144034200').$public;
$pem="-----BEGIN EC PRIVATE KEY-----\n".chunk_split(base64_encode($der),64,"\n")."-----END EC PRIVATE KEY-----\n";
$sender=openssl_pkey_get_private($pem);
if(!$sender)throw new RuntimeException('RFC 8291 sender key could not be loaded.');
$body=(new WebPushCrypto())->encrypt('When I grow up, I want to be a watermelon','BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcxaOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4','BTBZMqHH6r4Tts7J_aSIgg',$sender,$decode('DGv6ra1nlYgDCS1FRnbzlw'));
$expected='DGv6ra1nlYgDCS1FRnbzlwAAEABBBP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A_yl95bQpu6cVPTpK4Mqgkf1CXztLVBSt2Ks3oZwbuwXPXLWyouBWLVWGNWQexSgSxsj_Qulcy4a-fN';
if(!hash_equals($expected,WebPushKeyStore::b64($body)))throw new RuntimeException('RFC 8291 encryption vector mismatch.');

$temporary=sys_get_temp_dir().DIRECTORY_SEPARATOR.'eduvixo-web-push-'.bin2hex(random_bytes(6));
try{
    $store=new WebPushKeyStore($temporary,['key_file'=>$temporary.'/private/vapid.json','subject'=>'mailto:info@eduvixo.com']);$stored=$store->ensure();
    if(strlen(WebPushKeyStore::decode($stored['public_key']))!==65||!is_file($temporary.'/private/vapid.json'))throw new RuntimeException('VAPID key store test failed.');
    $authorization=(new WebPushCrypto())->authorization('https://fcm.googleapis.com/fcm/send/test',$store);
    if(!preg_match('/^vapid t=([^.]+)\.([^.]+)\.([A-Za-z0-9_-]+), k=([A-Za-z0-9_-]+)$/D',$authorization,$parts)||strlen(WebPushKeyStore::decode($parts[3]))!==64||!hash_equals($stored['public_key'],$parts[4]))throw new RuntimeException('VAPID assertion test failed.');
    $claims=json_decode(WebPushKeyStore::decode($parts[2]),true,8,JSON_THROW_ON_ERROR);
    if(($claims['aud']??'')!=='https://fcm.googleapis.com'||($claims['sub']??'')!=='mailto:info@eduvixo.com'||($claims['exp']??0)<=time())throw new RuntimeException('VAPID claims test failed.');
}finally{
    foreach([$temporary.'/private/vapid.json',$temporary.'/private/vapid.json.lock']as$file)if(is_file($file))unlink($file);
    if(is_dir($temporary.'/private'))rmdir($temporary.'/private');if(is_dir($temporary))rmdir($temporary);
}
echo json_encode(['ok'=>true,'rfc8291'=>'verified','vapid'=>'verified'],JSON_THROW_ON_ERROR).PHP_EOL;
