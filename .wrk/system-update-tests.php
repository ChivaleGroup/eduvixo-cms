<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
session_start();
$candidate='/root/eduvixo-deploy/core-candidate';$code=$candidate.'/cms';
spl_autoload_register(static function(string $class)use($code):void{if(str_starts_with($class,'App\\')){$file=$code.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require $file;}});
$config=(static fn($path)=>require $path)('/var/www/clients/client59/web119/web/config/app.php');
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$scratch='eduvixo_core_test_'.bin2hex(random_bytes(5));$db->exec("CREATE DATABASE `$scratch` CHARACTER SET utf8mb4");$db->exec("USE `$scratch`");
$fixture=$candidate.'/fixture-'.bin2hex(random_bytes(5));mkdir($fixture,0700);mkdir($fixture.'/app',0755);file_put_contents($fixture.'/app/release.json','{"version":"1.0.0"}');
$count=0;$assert=static function(bool $pass,string $label)use(&$count):void{if(!$pass)throw new RuntimeException('FAIL '.$label);$count++;echo 'PASS '.$label.PHP_EOL;};
$reject=static function(callable $call,string $label)use($assert):void{try{$call();}catch(Throwable){$assert(true,$label);return;}throw new RuntimeException('FAIL expected rejection: '.$label);};
try{
    $license=new App\Core\LicenseService($config['license'],$config['engine_version']);$headers=$license->marketplaceHeaders($config['base_url']);$headerMap=[];foreach($headers as$header)if(str_contains($header,': ')){[$key,$value]=explode(': ',$header,2);$headerMap[$key]=$value;}
    $assert(base64_decode($headerMap['X-Eduvixo-Product-Name'],true)===$config['license']['product_name']&&base64_decode($headerMap['X-Eduvixo-Product-Model'],true)===$config['license']['product_model'],'actual licensed product identity is sent safely to distribution');unset($headers,$headerMap,$license);
    $live=$config['database']['name'];if(!preg_match('/^[a-zA-Z0-9_]+$/D',$live))throw new RuntimeException('Invalid source database.');
    foreach($db->query("SHOW TABLES FROM `$live`")->fetchAll(PDO::FETCH_COLUMN) as $table)$db->exec("CREATE TABLE `$table` LIKE `$live`.`$table`");
    $db->exec("INSERT INTO migrations SELECT * FROM `$live`.migrations");$db->exec("INSERT INTO permissions SELECT * FROM `$live`.permissions");
    $db->exec("INSERT INTO roles(id,slug,name) VALUES(1,'owner','Test Owner')");$db->exec("INSERT INTO role_permissions(role_id,permission_id) SELECT 1,id FROM permissions WHERE slug='system.owner'");
    $password=bin2hex(random_bytes(20));
    $db->prepare('INSERT INTO users(id,name,username,email,password,active,created_at,updated_at) VALUES(1,"Test owner","test-owner","owner@example.invalid",?,1,NOW(),NOW())')->execute([password_hash($password,PASSWORD_DEFAULT)]);
    $db->exec('INSERT INTO user_roles(user_id,role_id) VALUES(1,1)');$db->exec('INSERT INTO settings(`key`,value) VALUES("core_preservation_test","\"keep-this-school-data\"")');
    mkdir($fixture.'/config',0700);file_put_contents($fixture.'/config/app.php','<?php return [];');mkdir($fixture.'/themes',0755);file_put_contents($fixture.'/themes/school.txt','preserve theme');mkdir($fixture.'/storage',0700);file_put_contents($fixture.'/storage/uploads.txt','preserve uploaded data');
    $updater=new App\Core\SystemUpdate($db,$fixture,$config);$catalog=App\Core\OfficialCatalog::verify(file_get_contents($candidate.'/official-catalog.json'));$release=$catalog['core'];$archive=$candidate.'/eduvixo-core-'.$release['version'].'.zip';
    $assert(count($catalog['products'])===12,'official catalog has all website products');
    foreach(['en','de','zh','vi','th','lo','pl'] as $locale)$assert(count(array_filter($catalog['products'],static fn($p)=>!empty($p['copy'][$locale]['description'])))===12,'all descriptions present: '.$locale);
    $assert(count($updater->verifyArchive($archive,$release)['files'])>100,'real signed core archive verifies');
    $reject(static fn()=>App\Core\OfficialCatalog::verify('{"signed_payload":"e30=","signature":"bad"}'),'forged catalog rejected');
    foreach(['../config/app.php','config/app.php','.env','themes/brand.php','plugins/tool.php','public/uploads/exploit.php','app/../config/app.php','app\\evil.php'] as $path)$assert(!App\Core\SystemUpdate::allowed($path),'protected path rejected: '.$path);
    $bad=$candidate.'/tampered-test.zip';copy($archive,$bad);$zip=new ZipArchive();$zip->open($bad);$zip->addFromString('payload/app/release.json','{"version":"9.9.9"}');$zip->close();$reject(fn()=>$updater->verifyArchive($bad,$release),'modified payload rejected');unlink($bad);
    copy($archive,$bad);$zip=new ZipArchive();$zip->open($bad);$zip->addFromString('payload/../config/app.php','bad');$zip->close();$reject(fn()=>$updater->verifyArchive($bad,$release),'extra traversal entry rejected');unlink($bad);
    $reject(fn()=>$updater->verifyArchive($archive,['version'=>'8.0.0']),'release identity mismatch rejected');
    $reflect=new ReflectionClass($updater);$reflect->getMethod('authorizeActor')->invoke($updater,1);$assert(true,'legacy owner can bootstrap before is_demo migration');$reject(fn()=>$reflect->getMethod('authorizeActor')->invoke($updater,999),'missing update actor rejected');
    $job=['id'=>bin2hex(random_bytes(12)),'user_id'=>1];$recovery=$reflect->getMethod('install')->invoke($updater,$archive,$release,$job);
    $assert($updater->version()===$release['version'],'full update installed through real updater');
    $assert(file_get_contents($fixture.'/themes/school.txt')==='preserve theme'&&file_get_contents($fixture.'/storage/uploads.txt')==='preserve uploaded data'&&file_get_contents($fixture.'/config/app.php')==='<?php return [];','theme configuration and uploads preserved');
    $assert($db->query('SELECT value FROM settings WHERE `key`="core_preservation_test"')->fetchColumn()==='"keep-this-school-data"','school database data preserved');
    $assert((int)$db->query('SELECT COUNT(*) FROM migrations WHERE name IN("023_demo_user_read_only.sql","024_system_notifications.sql")')->fetchColumn()===2,'both additive migrations recorded');
    $assert(!is_file($fixture.'/storage/system-updates/maintenance.json'),'maintenance cleared after success');
    $snapshot=$fixture.'/storage/system-updates/'.$recovery.'/database.sql.gz';$assert(is_file($snapshot)&&str_contains(gzdecode(file_get_contents($snapshot)),'keep-this-school-data'),'database recovery snapshot contains original data');
    $old=new ZipArchive();$old->open($fixture.'/storage/system-updates/'.$recovery.'/files.zip');$assert($old->getFromName('app/release.json')==='{"version":"1.0.0"}','previous core metadata backed up');$old->close();
    $db->exec('UPDATE users SET is_demo=1 WHERE id=1');$_SESSION['user_id']=1;$auth=new App\Core\Auth($db);$access=new App\Core\AccessControl($db,$auth);$access->enforceRequest('POST','/login');$assert($access->isDemoUser(),'demo session can reach real login without gaining privileges');
    $reject(fn()=>$reflect->getMethod('authorizeActor')->invoke($updater,1),'demo account cannot approve an update');
    $db->exec('UPDATE users SET is_demo=0 WHERE id=1');$access=new App\Core\AccessControl($db,$auth);$assert(!$access->isDemoUser()&&$access->allows('system.manage')&&$access->allows('extensions.manage'),'owner retains full permissions and is not demo');
    $captcha=new App\Core\CaptchaService();$captcha->saveCodeForImage($captcha->config());$answer=$_SESSION['eduvixo_captcha_login_code'];$assert($captcha->valid($answer),'CAPTCHA still verifies');$assert(!$captcha->valid($answer),'CAPTCHA cannot be reused');
    $assert(!$auth->attempt('owner@example.invalid','incorrect-password'),'incorrect owner password rejected');
    // Force a database failure after file replacement and confirm actual file rollback.
    $db->exec('UPDATE users SET active=1 WHERE id=1');file_put_contents($fixture.'/app/release.json','{"version":"1.0.0"}');
    $db->exec("CREATE TRIGGER core_test_reject_audit BEFORE INSERT ON activity_log FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected test failure'");
    $reject(fn()=>$reflect->getMethod('install')->invoke($updater,$archive,$release,['id'=>bin2hex(random_bytes(12)),'user_id'=>1]),'failed post-migration operation triggers recovery');
    $assert($updater->version()==='1.0.0'&&!is_file($fixture.'/storage/system-updates/maintenance.json'),'previous files restored and maintenance cleared after failure');
    $assert($db->query('SELECT value FROM settings WHERE `key`="core_preservation_test"')->fetchColumn()==='"keep-this-school-data"','rollback retains school data');
    echo 'Assertions: '.$count.PHP_EOL;
}finally{
    $db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$db->exec("DROP DATABASE `$scratch`");
    // The randomly named isolated test filesystem is kept privately for recovery audit, not deployed.
}
