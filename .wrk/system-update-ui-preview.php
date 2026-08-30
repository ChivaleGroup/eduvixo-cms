<?php
declare(strict_types=1);
$previewRoot=dirname(__DIR__).'/.cms/source';$requestPath=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);exit;}
if(str_starts_with($requestPath,'/theme/')||str_starts_with($requestPath,'/assets/'))return false;
require $previewRoot.'/app/Core/OfficialCatalog.php';$catalog=App\Core\OfficialCatalog::verify(file_get_contents(dirname(__DIR__).'/storage/marketplace/official-catalog.json'));
$updateState=['version'=>'1.0.0','latest'=>$catalog['core'],'available'=>true,'checked_at'=>time(),'error'=>null,'job'=>[],'worker_at'=>time(),'products'=>$catalog['products']];
if(isset($_GET['status'])){header('Content-Type: application/json');echo json_encode(['ok'=>true,'data'=>$updateState]);exit;}
if(!in_array($requestPath,['/system/update','/marketplace'],true)){header('Content-Type: application/json');echo '{"ok":true,"data":{"total":0,"items":[]}}';exit;}
$title='Update';$screen=$requestPath==='/marketplace'?'marketplace':'system-update';$csrf='preview-only';$flash=null;$licenseNotice=null;$sounds=[];$liveChatSettings=[];$accessPermissions=['system.owner'];$currentRoleNames=['Preview'];$extensionNavigation=[];$user=['id'=>0,'name'=>'Local preview','email'=>'preview@example.invalid'];$isDemoUser=false;$showDemoWelcome=false;$liveChatUnread=$formUnread=$surveyUnread=$packageUpdates=$editorialUnread=0;
$officialMarketplace=$updateState;$marketplace=['items'=>[],'summary'=>[],'facets'=>[],'pagination'=>['total'=>0,'page'=>1,'pages'=>1],'filters'=>[]];
require $previewRoot.'/app/Views/console.php';
