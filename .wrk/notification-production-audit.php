<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$demo='/var/www/clients/client9/web121/web';$web='/var/www/clients/client9/web123/web';
$config=(static fn(string $file)=>require $file)($demo.'/config/app.php');
spl_autoload_register(static function(string $class)use($demo):void{if(str_starts_with($class,'App\\')){$file=$demo.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require $file;}});
$db=(new App\Core\Database($config['database']))->connection();
$rows=$db->query("SELECT slug,version,active,manifest FROM extension_packages WHERE slug IN ('calendar','google-calendar','apple-calendar','microsoft-365-calendar','telegram-notifications','whatsapp-notifications') ORDER BY slug")->fetchAll();$summary=[];
foreach($rows as$row){$manifest=json_decode($row['manifest'],true,64,JSON_THROW_ON_ERROR);$messenger=in_array($row['slug'],['telegram-notifications','whatsapp-notifications'],true);$connector=in_array($row['slug'],['google-calendar','apple-calendar','microsoft-365-calendar'],true);
    if($row['version']!=='1.0.2-beta.1'||!$row['active'])throw new RuntimeException('Extension release mismatch');
    if($messenger && ((float)$manifest['license']['price']!==48.0 || $manifest['dependencies']!==[] || $manifest['config_url']!=='/system/notifications'))throw new RuntimeException('Messenger contract mismatch');
    if($connector && ((float)$manifest['license']['price']!==12.0 || ($manifest['dependencies'][0]['slug']??'')!=='calendar'))throw new RuntimeException('Calendar connector contract mismatch');
    $summary[]=['slug'=>$row['slug'],'version'=>$row['version'],'price'=>$manifest['license']['price'],'requires_calendar'=>$connector];
}
$channels=new App\Core\NotificationChannels($db,$demo,$config['secrets_key']);$catalog=$channels->catalog();
if(count($catalog)!==2)throw new RuntimeException('Notification channel discovery failed');
$translations=0;foreach(['en','de','zh','vi','th','lo','pl']as$lang){$data=json_decode(file_get_contents($web.'/lang/'.$lang.'.json'),true,512,JSON_THROW_ON_ERROR);$curl=curl_init('https://www.eduvixo.com/'.$lang.'/marketplace/');curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15]);$html=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);if($status!==200 || !is_string($html))throw new RuntimeException('Marketplace response failed');foreach(['notification_price','system_notifications','requires_calendar','telegram_copy','whatsapp_copy','google_calendar_copy','apple_calendar_copy','microsoft_calendar_copy']as$key){if(!str_contains($html,htmlspecialchars($data['marketplace'][$key],ENT_QUOTES,'UTF-8')))throw new RuntimeException('Localized contract missing: '.$lang.'/'.$key);}$translations++;}
echo json_encode(['ok'=>true,'extensions'=>$summary,'configured_channels'=>count($channels->active()),'languages_checked_live'=>$translations,'licensed_engine'=>$config['engine_version']],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
