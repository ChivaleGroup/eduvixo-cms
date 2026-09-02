<?php
declare(strict_types=1);
$previewRoot=dirname(__DIR__).'/.cms/source';
$requestPath=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);exit;}
if($requestPath!=='/system/notifications')return false;
$title='Notification channels';$screen='notification-settings';$csrf='local-preview-only';$flash=null;$licenseNotice=null;$sounds=[];$liveChatSettings=[];$accessPermissions=['system.owner'];$currentRoleNames=['Preview'];$extensionNavigation=[];$user=['id'=>0,'name'=>'Local UI preview','email'=>'preview@example.invalid','avatar_url'=>''];$isDemoUser=false;$showDemoWelcome=false;$liveChatUnread=0;$formUnread=0;$surveyUnread=0;$packageUpdates=0;$editorialUnread=0;$notificationUsers=[];$notificationDeliveries=[];$webPushDeliveries=[];$webPushStats=[];$notificationChannels=[];
foreach(['EduvixoTelegram','EduvixoWhatsApp']as$dir){$manifest=json_decode(file_get_contents(dirname(__DIR__).'/.plugins/'.$dir.'/source/plugin.json'),true);$fields=[];foreach($manifest['notification_integration']['fields']as$field)$fields[]=$field+['value'=>'','configured'=>$manifest['slug']==='telegram-notifications'&&$field['name']==='bot_token'];$notificationChannels[]=['name'=>$manifest['name'],'slug'=>$manifest['slug'],'enabled'=>false,'last_error'=>null,'fields'=>$fields];}
require $previewRoot.'/app/Views/console.php';
