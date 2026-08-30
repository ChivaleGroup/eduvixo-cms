<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$web='/var/www/clients/client9/web123/web';$demo='/var/www/clients/client9/web121/web';$stage='/root/eduvixo-deploy/calendar-repair';
$run=static function(array $command,?string $output=null):void { $spec=[0=>['pipe','r'],1=>$output?['file',$output,'w']:STDOUT,2=>STDERR];$process=proc_open($command,$spec,$pipes);if(!is_resource($process))throw new RuntimeException('Process start failed.');if(isset($pipes[0]))fclose($pipes[0]);if(proc_close($process)!==0)throw new RuntimeException('Deployment operation failed.'); };
$backup='/root/eduvixo-backups/calendar-repair-pre-'.gmdate('Ymd-His');if(!mkdir($backup,0700))throw new RuntimeException('Backup creation failed.');
$config=require $demo.'/config/app.php';$dbName=$config['database']['name'];if(!preg_match('/^[a-zA-Z0-9_]+$/D',$dbName))throw new RuntimeException('Invalid database name.');
$run(['mariadb-dump','--socket=/run/mysqld/mysqld.sock','--user=root','--single-transaction','--routines','--triggers','--databases',$dbName],$backup.'/demo.sql');chmod($backup.'/demo.sql',0600);
$run(['tar','-czf',$backup.'/demo-files.tar.gz','-C',$demo,'app','public/index.php','addons','plugins','scripts/extension-package.php']);
$run(['tar','-czf',$backup.'/website-files.tar.gz','-C',$web,'app','config','lang','resources','public/assets','storage/marketplace/packages/eduvixo-install-1.0.0.zip']);
foreach(glob($backup.'/*')as$file){chmod($file,0600);if(filesize($file)<100)throw new RuntimeException('Incomplete backup.');echo basename($file).' '.hash_file('sha256',$file).PHP_EOL;}
echo 'Backup: '.$backup.PHP_EOL;
if(($argv[1]??'')!=='apply')exit;
$replace=static function(string $source,string $target,string $owner,int $mode=0640):void {if(!is_file($source))throw new RuntimeException('Missing staged file.');$temp=$target.'.calendar-new';if(!copy($source,$temp))throw new RuntimeException('Staging copy failed.');chmod($temp,$mode);chown($temp,$owner);chgrp($temp,'client9');if(!rename($temp,$target))throw new RuntimeException('Atomic replacement failed.');};
foreach(['app/Http/ExtensionAssetController.php']as$file)$replace($stage.'/cms/'.$file,$demo.'/'.$file,'web121');
foreach(['eduvixo-calendar','google-calendar','apple-calendar','microsoft-365-calendar','telegram-notifications','whatsapp-notifications']as$name){$package=$stage.'/packages/'.$name.'-1.0.1-beta.1.zip';$run(['php','-d','display_errors=1',$demo.'/scripts/extension-package.php','install',$package,'--activate']);}
$run(['chown','-R','web121:client9',$demo.'/addons/calendar']);
foreach(['google-calendar','apple-calendar','microsoft-365-calendar','telegram-notifications','whatsapp-notifications']as$slug)$run(['chown','-R','web121:client9',$demo.'/plugins/'.$slug]);
foreach(glob($stage.'/packages/*.zip')as$file)$replace($file,$web.'/storage/marketplace/packages/'.basename($file),'web123');
foreach(['app/MarketplaceService.php','app/views/pages/marketplace.php','resources/pages.css','public/assets/css/site.min.css','public/assets/icons.svg']as$file)$replace($stage.'/website/'.$file,$web.'/'.$file,'web123',str_starts_with($file,'public/')?0644:0640);
foreach(['en','de','zh','vi','th','lo','pl']as$lang)$replace($stage.'/website/lang/'.$lang.'.json',$web.'/lang/'.$lang.'.json','web123');
$replace($stage.'/website/config/marketplace.php',$web.'/config/marketplace.php','web123');
echo "Repair files deployed. Cron remains unchanged until health checks pass.\n";
