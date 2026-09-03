<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$demoRoot='/var/www/clients/client9/web121/web';
$shouduRoot='/var/www/clients/client59/web119/web';
$slug='ai-translation-assistant';
$expected='25d697c09fe5cf4c426435198aa14a8944ff00a4e71e118295876279a6aa075a';
$connect=static function(string$root):PDO{
    foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);
    $config=(static fn(string$path):array=>require$path)($root.'/config/app.php');
    $name=(string)$config['database']['name'];
    if(!preg_match('/^[A-Za-z0-9_]+$/D',$name))throw new RuntimeException('Unsafe database name.');
    return new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$name.';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
};
foreach([$demoRoot,$shouduRoot]as$root)if(realpath($root)!==$root||!is_file($root.'/public/index.php'))throw new RuntimeException('Unexpected CMS root.');
$demoDb=$connect($demoRoot);
$demoCheck=$demoDb->prepare("SELECT 1 FROM extension_packages WHERE type='plugin' AND slug=? LIMIT 1");$demoCheck->execute([$slug]);
if($demoCheck->fetchColumn()||!is_dir($demoRoot.'/plugins/'.$slug))throw new RuntimeException('Interrupted deployment state does not match the expected demo state.');
$shouduDb=$connect($shouduRoot);
$package=$shouduDb->prepare("SELECT * FROM extension_packages WHERE type='plugin' AND slug=? LIMIT 1");$package->execute([$slug]);$package=$package->fetch();
$runtime=$shouduDb->prepare('SELECT * FROM installed_plugins WHERE slug=? LIMIT 1');$runtime->execute([$slug]);$runtime=$runtime->fetch();
if(!$package||!$runtime||($package['version']??'')!=='1.0.0'||($package['package_checksum']??'')!==$expected||is_dir($shouduRoot.'/plugins/'.$slug))throw new RuntimeException('Unexpected Shoudu state; repair stopped without changes.');
$releases=$shouduDb->prepare('SELECT * FROM extension_package_releases WHERE package_id=? ORDER BY id');$releases->execute([(int)$package['id']]);
$migrations=$shouduDb->prepare('SELECT * FROM extension_migrations WHERE package_type=? AND package_slug=? ORDER BY id');$migrations->execute(['plugin',$slug]);
$backup='/root/eduvixo-backups/ai-translation-routing-repair-'.gmdate('Ymd-His');
if(!mkdir($backup,0700,true))throw new RuntimeException('Cannot create repair backup.');
$snapshot=['reason'=>'Recovery from interrupted deployment with inherited CMS environment variables.','package'=>$package,'runtime'=>$runtime,'releases'=>$releases->fetchAll(),'migrations'=>$migrations->fetchAll()];
file_put_contents($backup.'/shoudu-erroneous-state.json',json_encode($snapshot,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),LOCK_EX);
file_put_contents($backup.'/ROLLBACK.txt',"Restore the backed-up rows only if the accidental Shoudu database registration must be reconstructed. No Shoudu plugin files existed and no intended Shoudu package was removed.\n",LOCK_EX);
chmod($backup.'/shoudu-erroneous-state.json',0600);chmod($backup.'/ROLLBACK.txt',0600);
$shouduDb->beginTransaction();
try{
    $delete=$shouduDb->prepare('DELETE FROM extension_package_releases WHERE package_id=?');$delete->execute([(int)$package['id']]);
    $delete=$shouduDb->prepare('DELETE FROM extension_migrations WHERE package_type=? AND package_slug=?');$delete->execute(['plugin',$slug]);
    $delete=$shouduDb->prepare('DELETE FROM installed_plugins WHERE slug=?');$delete->execute([$slug]);
    $delete=$shouduDb->prepare("DELETE FROM extension_packages WHERE type='plugin' AND slug=?");$delete->execute([$slug]);
    $shouduDb->commit();
}catch(Throwable$error){if($shouduDb->inTransaction())$shouduDb->rollBack();throw$error;}
$verify=$shouduDb->prepare("SELECT (SELECT COUNT(*) FROM extension_packages WHERE type='plugin' AND slug=?) packages,(SELECT COUNT(*) FROM installed_plugins WHERE slug=?) runtime");$verify->execute([$slug,$slug]);$state=$verify->fetch();
if((int)$state['packages']!==0||(int)$state['runtime']!==0)throw new RuntimeException('Repair verification failed.');
echo json_encode(['ok'=>true,'site'=>'shoudu.lrn.asia','removed'=>'accidental database registration only','files'=>'unchanged','backup'=>$backup],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
