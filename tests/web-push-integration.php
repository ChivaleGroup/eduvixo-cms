<?php
declare(strict_types=1);

if(PHP_SAPI!=='cli')exit("CLI only\n");
$core=rtrim((string)(getenv('EDUVIXO_CORE')?:dirname(__DIR__).'/.cms/source/app/Core'),'/\\');
foreach(['Auth','AccessControl','Secrets','NotificationAudience','WebPushKeyStore','WebPushCrypto','WebPushSender','WebPushSubscriptions']as$class)require$core.'/'.$class.'.php';

use App\Core\NotificationAudience;
use App\Core\Secrets;
use App\Core\WebPushSubscriptions;

$admin=new PDO((string)(getenv('EDUVIXO_TEST_DSN')?:'mysql:unix_socket=/var/run/mysqld/mysqld.sock;charset=utf8mb4'),'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$database='eduvixo_webpush_test_'.bin2hex(random_bytes(5));
if(!preg_match('/^eduvixo_webpush_test_[a-f0-9]{10}$/D',$database))throw new RuntimeException('Unsafe test database name.');
$private=sys_get_temp_dir().'/'.$database;
try{
    $admin->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $db=new PDO(preg_replace('/;charset=/',';dbname='.$database.';charset=',(string)(getenv('EDUVIXO_TEST_DSN')?:'mysql:unix_socket=/var/run/mysqld/mysqld.sock;charset=utf8mb4')),'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $db->exec('CREATE TABLE users(id BIGINT UNSIGNED PRIMARY KEY,active TINYINT NOT NULL,is_demo TINYINT NOT NULL)');
    $db->exec('CREATE TABLE permissions(id BIGINT UNSIGNED PRIMARY KEY,slug VARCHAR(120) NOT NULL)');
    $db->exec('CREATE TABLE roles(id BIGINT UNSIGNED PRIMARY KEY,active TINYINT NOT NULL)');
    $db->exec('CREATE TABLE role_permissions(role_id BIGINT UNSIGNED NOT NULL,permission_id BIGINT UNSIGNED NOT NULL)');
    $db->exec('CREATE TABLE user_roles(user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL)');
    foreach(["INSERT INTO users VALUES(1,1,0)","INSERT INTO permissions VALUES(1,'system.owner')","INSERT INTO roles VALUES(1,1)","INSERT INTO role_permissions VALUES(1,1)","INSERT INTO user_roles VALUES(1,1)"]as$sql)$db->exec($sql);
    $migration=(string)file_get_contents((string)(getenv('EDUVIXO_WEB_PUSH_MIGRATION')?:dirname(__DIR__).'/.cms/source/database/migrations/025_web_push.sql'));
    foreach(array_filter(array_map('trim',explode(';',$migration)))as$sql)$db->exec($sql);
    $config=['base_url'=>'https://demo.eduvixo.com','secrets_key'=>bin2hex(random_bytes(32)),'web_push'=>['subject'=>'mailto:info@eduvixo.com','key_file'=>$private.'/vapid.json']];
    $push=new WebPushSubscriptions($db,$config,$private);
    $endpoint='https://fcm.googleapis.com/fcm/send/eduvixo-integration-test';$subscription=['endpoint'=>$endpoint,'expirationTime'=>null,'keys'=>['p256dh'=>'BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcxaOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4','auth'=>'BTBZMqHH6r4Tts7J_aSIgg'],'contentEncoding'=>'aes128gcm'];
    $push->subscribe(1,$subscription,'Mozilla/5.0 (X11; Linux x86_64) Chrome/140');$status=$push->status(1);
    if(count($status['devices'])!==1||!in_array(hash('sha256',$endpoint),$status['subscription_hashes'],true))throw new RuntimeException('Subscription status mismatch.');
    $stored=$db->query('SELECT encrypted_subscription FROM web_push_subscriptions')->fetchColumn();if(!is_string($stored)||str_contains($stored,$endpoint))throw new RuntimeException('Subscription capability URL was stored in plaintext.');
    $plain=(new Secrets($config['secrets_key']))->decrypt($stored);if(!str_contains($plain,$endpoint))throw new RuntimeException('Encrypted subscription cannot be recovered.');
    $event=['key'=>'license:test','source'=>'license','subject'=>'installation','user_id'=>1,'title'=>'License status','message'=>'Test notification','url'=>'/license','created_at'=>gmdate('Y-m-d H:i:s'),'channels'=>[]];
    if($push->enqueue($event,new NotificationAudience($db))!==1||(int)$db->query('SELECT COUNT(*) FROM web_push_deliveries')->fetchColumn()!==1)throw new RuntimeException('Notification queue test failed.');
    if($push->enqueue($event,new NotificationAudience($db))!==0||(int)$db->query('SELECT COUNT(*) FROM web_push_deliveries')->fetchColumn()!==1)throw new RuntimeException('Notification deduplication test failed.');
    $push->savePreferences(1,['calendar']);$event['key']='license:filtered';if($push->enqueue($event,new NotificationAudience($db))!==0)throw new RuntimeException('Notification preference filtering failed.');
    $push->unsubscribe(1,$endpoint);if((int)$db->query('SELECT COUNT(*) FROM web_push_subscriptions')->fetchColumn()!==0||(int)$db->query('SELECT COUNT(*) FROM web_push_deliveries')->fetchColumn()!==0)throw new RuntimeException('Subscription revocation failed.');
    echo json_encode(['ok'=>true,'database'=>$database,'checks'=>['encrypted_storage','status','queue','deduplication','preferences','revocation']],JSON_THROW_ON_ERROR).PHP_EOL;
}finally{
    $admin->exec('DROP DATABASE IF EXISTS `'.$database.'`');
    if(is_dir($private)){foreach(glob($private.'/*')?:[]as$file)if(is_file($file))unlink($file);rmdir($private);}
}
