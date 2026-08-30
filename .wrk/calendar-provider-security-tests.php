<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$tests=0;
$apple=require $root.'/.plugins/EduvixoAppleCalendar/source/src/provider.php';
foreach(['http://caldav.icloud.com/','https://127.0.0.1/','https://169.254.169.254/','https://caldav.icloud.com.example.invalid/','https://name@caldav.icloud.com/','https://caldav.icloud.com:8443/','https://caldav.icloud.com/?target=internal']as$url){try{$apple->verify(['calendar_url'=>$url,'apple_id'=>'nobody@example.invalid','app_password'=>'not-a-credential']);throw new LogicException('Unsafe URL was accepted.');}catch(RuntimeException){$tests++;}}
$telegram=require $root.'/.plugins/EduvixoTelegram/source/src/provider.php';try{$telegram->verify(['bot_token'=>'invalid']);throw new LogicException('Invalid token was accepted.');}catch(RuntimeException){$tests++;}
$whatsapp=require $root.'/.plugins/EduvixoWhatsApp/source/src/provider.php';try{$whatsapp->send(['template_name'=>'invalid template','template_language'=>'en_US'],[]);throw new LogicException('Invalid template was accepted.');}catch(RuntimeException){$tests++;}
echo json_encode(['ok'=>true,'tests'=>$tests,'outbound_requests'=>0]).PHP_EOL;
