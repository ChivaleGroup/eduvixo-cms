<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$web='/var/www/clients/client9/web123/web';$demo='/var/www/clients/client9/web121/web';$stage='/root/eduvixo-deploy/notification-release';
$run=static function(array $command,?string $output=null):void{$process=proc_open($command,[0=>['pipe','r'],1=>$output?['file',$output,'w']:STDOUT,2=>STDERR],$pipes);if(!is_resource($process))throw new RuntimeException('Process start failed');fclose($pipes[0]);if(proc_close($process)!==0)throw new RuntimeException('Deployment command failed');};
$config=(static fn(string $file)=>require $file)($demo.'/config/app.php');
spl_autoload_register(static function(string $class)use($demo):void{if(str_starts_with($class,'App\\')){$file=$demo.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require $file;}});
if(($argv[1]??'')==='preflight') {
    $license=new App\Core\LicenseService($config['license'],'1.0');
    $headers=$license->marketplaceHeaders($config['base_url']);$key=substr($headers[0],strlen('Authorization: Bearer '));
    $license->validate($key,$config['base_url']);unset($key,$headers);echo "Existing CMS entitlement accepts engine 1.0.\n";exit;
}
$backup='/root/eduvixo-backups/notifications-pre-'.gmdate('Ymd-His');if(!mkdir($backup,0700))throw new RuntimeException('Backup creation failed');
$dbName=$config['database']['name'];if(!preg_match('/^[a-zA-Z0-9_]+$/D',$dbName))throw new RuntimeException('Unsafe database name');
$run(['mariadb-dump','--socket=/run/mysqld/mysqld.sock','--user=root','--single-transaction','--routines','--triggers','--databases',$dbName],$backup.'/demo.sql');chmod($backup.'/demo.sql',0600);
echo "Database backed up.\n";
$run(['tar','-czf',$backup.'/demo.tar.gz','-C',$demo,'.']);echo "Demo files backed up.\n";
$run(['tar','-czf',$backup.'/website.tar.gz','-C',$web,'.']);echo "Website files backed up.\n";
foreach(glob($backup.'/*')as$file){chmod($file,0600);if(filesize($file)<100)throw new RuntimeException('Incomplete backup');echo basename($file).' '.hash_file('sha256',$file).PHP_EOL;}
echo 'Rollback backup: '.$backup.PHP_EOL;
if(($argv[1]??'')!=='apply')exit;
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$dbName.';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\R|$)/',file_get_contents($stage.'/cms/database/migrations/024_system_notifications.sql'))))as$sql)$db->exec($sql);
// The wider system notification scope requires fresh recipient consent.
$db->exec("INSERT IGNORE INTO notification_channel_settings(plugin_slug,subject_type,subject_id,encrypted_settings,enabled,last_verified_at,last_error,updated_at) SELECT plugin_slug,subject_type,subject_id,encrypted_settings,0,NULL,'Confirm recipient consent for system-wide notifications.',UTC_TIMESTAMP() FROM calendar_integration_settings WHERE plugin_slug IN ('telegram-notifications','whatsapp-notifications')");
$replace=static function(string $source,string $target,string $owner,int $mode=0640):void{if(!is_file($source))throw new RuntimeException('Missing staged file: '.basename($source));$temp=$target.'.notifications-new';if(!copy($source,$temp))throw new RuntimeException('Copy failed');chmod($temp,$mode);chown($temp,$owner);chgrp($temp,'client9');if(!rename($temp,$target))throw new RuntimeException('Atomic replacement failed');};
$files=['app/Core/AccessControl.php','app/Core/AiRepository.php','app/Core/Secrets.php','app/Core/SystemNotifications.php','app/Core/NotificationChannels.php','app/Core/NotificationAudience.php','app/Core/NotificationDispatcher.php','app/Http/NotificationController.php','app/Http/DashboardController.php','app/Views/console.php','app/Views/console-notification-settings.php','public/index.php','scripts/notification-worker.php','database/migrations/024_system_notifications.sql'];
foreach($files as$file)$replace($stage.'/cms/'.$file,$demo.'/'.$file,'web121');
if($config['engine_version']!=='1.0')throw new RuntimeException('Unexpected licensed engine version.');
foreach(['eduvixo-calendar','google-calendar','apple-calendar','microsoft-365-calendar','telegram-notifications','whatsapp-notifications']as$name)$run(['php',$demo.'/scripts/extension-package.php','install',$stage.'/packages/'.$name.'-1.0.2-beta.1.zip','--activate']);
$run(['chown','-R','web121:client9',$demo.'/addons/calendar']);foreach(['google-calendar','apple-calendar','microsoft-365-calendar','telegram-notifications','whatsapp-notifications']as$slug)$run(['chown','-R','web121:client9',$demo.'/plugins/'.$slug]);
foreach(glob($stage.'/packages/*.zip')as$file)$replace($file,$web.'/storage/marketplace/packages/'.basename($file),'web123');
foreach(['en','de','zh','vi','th','lo','pl']as$lang)$replace($stage.'/website/lang/'.$lang.'.json',$web.'/lang/'.$lang.'.json','web123');
$replace($stage.'/website/config/marketplace.php',$web.'/config/marketplace.php','web123');
$run(['php','-l',$demo.'/public/index.php']);$run(['php','-l',$demo.'/config/app.php']);
$run(['runuser','-u','web121','--','php',$demo.'/scripts/notification-worker.php','50']);
if(!copy($stage.'/cron/eduvixo-notifications','/etc/cron.d/eduvixo-notifications'))throw new RuntimeException('Cron installation failed');chmod('/etc/cron.d/eduvixo-notifications',0644);
if(!copy($stage.'/cron/eduvixo-notifications-logrotate','/etc/logrotate.d/eduvixo-notifications'))throw new RuntimeException('Log rotation installation failed');chmod('/etc/logrotate.d/eduvixo-notifications',0644);
echo "Deployment complete. No provider credentials or recipients were added.\n";
