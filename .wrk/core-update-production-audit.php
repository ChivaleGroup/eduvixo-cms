<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$website='/var/www/clients/client9/web123/web';$sites=[['demo','/var/www/clients/client9/web121/web','demo.eduvixo.com','eduvixo'],['shoudu','/var/www/clients/client59/web119/web','shoudu.lrn.asia','shoudu']];
$count=0;$assert=static function(bool $ok,string $label)use(&$count):void{if(!$ok)throw new RuntimeException('FAIL '.$label);$count++;echo 'PASS '.$label.PHP_EOL;};
$request=static function(string $url,array $headers=[]):array{$body='';$curl=curl_init($url);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>$headers,CURLOPT_USERAGENT=>'Eduvixo-Production-Audit/1.0']);$body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_error($curl);curl_close($curl);if($body===false)throw new RuntimeException('HTTP audit failed: '.$error);return[$status,$body];};
require $sites[0][1].'/app/Core/OfficialCatalog.php';
[$status,$raw]=$request('https://www.eduvixo.com/api/marketplace/v1/official/');$assert($status===200,'official catalog endpoint online');$catalog=App\Core\OfficialCatalog::verify($raw);$assert(($catalog['core']['version']??'')==='1.0.3'&&count($catalog['products'])===12,'signed catalog is current and complete');
foreach(['en','de','zh','vi','th','lo','pl']as$locale){[$status,$page]=$request('https://www.eduvixo.com/'.$locale.'/marketplace/');$assert($status===200&&substr_count($page,'id="package-')===12,'website Marketplace anchors available: '.$locale);}
[$status]=$request('https://www.eduvixo.com/api/marketplace/v1/core-package/');$assert($status===401,'core package rejects unauthenticated access');
foreach($sites as[$name,$root,$host,$theme]){
    foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);$config=(static fn($path)=>require $path)($root.'/config/app.php');
    spl_autoload_register(static function(string $class)use($root):void{if(str_starts_with($class,'App\\')){$file=$root.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require_once$file;}});
    $db=(new App\Core\Database($config['database']))->connection();$release=json_decode((string)file_get_contents($root.'/app/release.json'),true);
    $assert(($release['version']??'')==='1.0.3'&&$config['engine_version']==='1.0',$name.' has build 1.0.3 on licensed engine 1.0');
    $state=(new App\Core\SystemUpdate($db,$root,$config))->status();$assert(!$state['available']&&($state['job']['status']??'')==='completed'&&empty($state['error']),$name.' update state is healthy and current');
    $assert(!is_file($root.'/storage/system-updates/maintenance.json')&&is_file($root.'/storage/system-updates/'.$state['job']['recovery'].'/files.zip')&&is_file($root.'/storage/system-updates/'.$state['job']['recovery'].'/database.sql.gz'),$name.' maintenance cleared and recovery artifacts exist');
    foreach(['023_demo_user_read_only.sql','024_system_notifications.sql']as$migration){$q=$db->prepare('SELECT 1 FROM migrations WHERE name=?');$q->execute([$migration]);$assert((bool)$q->fetchColumn(),$name.' migration present: '.$migration);}
    $setting=$db->prepare('SELECT value FROM settings WHERE `key`="active_theme"');$setting->execute();$assert(json_decode((string)$setting->fetchColumn(),true)===$theme,$name.' active presentation theme preserved');
    $license=new App\Core\LicenseService($config['license'],$config['engine_version']);$headers=$license->marketplaceHeaders($config['base_url']);[$packageStatus,$package]=$request('https://www.eduvixo.com/api/marketplace/v1/core-package/',$headers);unset($headers,$license);$assert($packageStatus===200&&hash_equals($catalog['core']['checksum'],hash('sha256',$package)),$name.' licensed product downloads the verified core package');unset($package);
    [$loginStatus,$login]=$request('https://'.$host.'/login');$assert($loginStatus===200&&str_contains($login,'name="captcha"')&&str_contains($login,'name="csrf"'),$name.' web login and security controls available');
    foreach(['/app/Core/SystemUpdate.php','/storage/system-updates/catalog.json']as$private){[$privateStatus]=$request('https://'.$host.$private);$assert($privateStatus===403,$name.' private update path is denied');}
    if($name==='demo'){$q=$db->prepare("SELECT u.active,u.is_demo,COUNT(DISTINCT CASE WHEN p.slug='system.owner' THEN p.id END) owner_permission FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id AND r.active=1 LEFT JOIN role_permissions rp ON rp.role_id=r.id LEFT JOIN permissions p ON p.id=rp.permission_id WHERE LOWER(u.email)=? GROUP BY u.id,u.active,u.is_demo");$q->execute(['mario@chivale.email']);$mario=$q->fetch();$assert($mario&&(int)$mario['active']===1&&(int)$mario['is_demo']===0&&(int)$mario['owner_permission']===1,'Mario is active Owner and not a demo user');}
}
echo 'Assertions: '.$count.PHP_EOL;
