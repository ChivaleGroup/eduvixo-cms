<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$stage=$argv[1]??'/root/eduvixo-deploy/notification-candidate';$root=$stage.'/cms';$liveRoot='/var/www/clients/client9/web121/web';
spl_autoload_register(static function(string $class)use($root):void{if(str_starts_with($class,'App\\')){$file=$root.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require $file;}});
$config=(static fn(string $file)=>require $file)($liveRoot.'/config/app.php');$live=$config['database']['name'];if(!preg_match('/^[a-zA-Z0-9_]+$/D',$live))throw new RuntimeException('Unsafe DB name.');
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$scratch='eduvixo_notifications_test_'.bin2hex(random_bytes(5));$db->exec("CREATE DATABASE `$scratch` CHARACTER SET utf8mb4");$db->exec("USE `$scratch`");$GLOBALS['notificationTestDb']=$db;$GLOBALS['notificationTestFail']=false;
$count=0;$assert=static function(bool $ok,string $label)use(&$count):void{if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;$count++;};
$sql=static function(string $file)use($db):void{if(!is_file($file))throw new RuntimeException('Missing test migration');foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\R|$)/',(string)file_get_contents($file))))as$query)$db->exec($query);};
try {
    foreach(['users','roles','permissions','role_permissions','user_roles','user_campuses','team_campuses','live_chat_team_users','live_chat_teams','ai_conversations','ai_messages','form_submissions','survey_responses','survey_campuses','surveys','user_notifications','extension_packages','activity_log','pages','posts','campuses','campus_translations','languages']as$table)$db->exec("CREATE TABLE `$table` LIKE `$live`.`$table`");
    $db->exec("INSERT INTO permissions SELECT * FROM `$live`.permissions");
    $db->exec("INSERT INTO roles(id,slug,name) VALUES(1,'owner','Test Owner'),(2,'scoped','Scoped Test')");
    $db->exec("INSERT INTO role_permissions(role_id,permission_id) SELECT 1,id FROM permissions WHERE slug='system.owner'");
    $db->exec("INSERT INTO role_permissions(role_id,permission_id) SELECT 2,id FROM permissions WHERE slug IN ('console.access','forms.view','surveys.responses','chat.view','calendar.view','content.pages.view')");
    for($i=1;$i<=4;$i++)$db->prepare('INSERT INTO users(id,name,username,email,password,active,is_demo,created_at,updated_at) VALUES(?,?,?,?,?,1,?,NOW(),NOW())')->execute([$i,'Test user '.$i,'test'.$i,'notification'.$i.'@example.invalid',bin2hex(random_bytes(32)),$i===4?1:0]);
    $db->exec('INSERT INTO user_roles(user_id,role_id) VALUES(1,1),(2,2),(3,2),(4,1)');
    $db->exec('INSERT INTO user_campuses(user_id,campus_id,created_at) VALUES(2,1,NOW()),(3,2,NOW())');
    $sql($root.'/database/migrations/024_system_notifications.sql');$sql($root.'/database/migrations/024_system_notifications.sql');$sql($root.'/database/migrations/025_web_push.sql');$sql($root.'/database/migrations/025_web_push.sql');$assert(true,'additive migrations are repeatable');
    $db->exec('CREATE TABLE notification_test_receipts(id INT AUTO_INCREMENT PRIMARY KEY, recipient VARCHAR(50), title VARCHAR(180), body TEXT)');
    foreach(['telegram-notifications','whatsapp-notifications']as$slug){$manifest=json_decode(file_get_contents($root.'/plugins/'.$slug.'/plugin.json'),true);$db->prepare('INSERT INTO extension_packages(type,slug,name,version,publisher,active,manifest,install_path,installed_at,updated_at) VALUES("plugin",?,?,?,"Test",1,?,?,NOW(),NOW())')->execute([$slug,$manifest['name'],$manifest['version'],json_encode($manifest),'plugins/'.$slug]);}
    $config['secrets_key']=bin2hex(random_bytes(32));$config['base_url']='https://example.invalid';$config['license']['path']=$stage.'/empty-license';
    $channels=new App\Core\NotificationChannels($db,$root,$config['secrets_key']);$assert(count($channels->catalog())===2,'messenger catalog works with no Calendar tables or add-on');
    $input=['enabled'=>1,'consent_confirmed'=>1,'bot_token'=>'test-secret','recipient_map'=>'{"1":"11111111","2":"22222222","3":"33333333"}'];
    try{$channels->save('telegram-notifications',$input+['x'=>'']);$assert(true,'private consented recipients accepted');}catch(Throwable$error){throw$error;}
    $invalid=$input;$invalid['consent_confirmed']=0;try{$channels->save('telegram-notifications',$invalid);throw new LogicException('Expected consent rejection');}catch(RuntimeException){$assert(true,'explicit opt-in is required');}
    $invalid=$input;$invalid['recipient_map']='{"4":"44444444"}';try{$channels->save('telegram-notifications',$invalid);throw new LogicException('Expected demo rejection');}catch(RuntimeException){$assert(true,'demo recipients cannot receive messages');}
    $invalid=$input;$invalid['recipient_map']='{"1":"-1001234567890"}';try{$channels->save('telegram-notifications',$invalid);throw new LogicException('Expected group rejection');}catch(RuntimeException){$assert(true,'Telegram group destination rejected');}
    $assert(!str_contains((string)$db->query('SELECT encrypted_settings FROM notification_channel_settings LIMIT 1')->fetchColumn(),'test-secret'),'provider credentials encrypted at rest');
    $catalog=$channels->catalog(true);$token=array_values(array_filter($catalog[0]['fields'],fn($f)=>$f['name']==='bot_token'));$assert($token[0]['value']==='','secret values are not returned to UI');
    $db->exec("UPDATE notification_channel_settings SET updated_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR)");
    $plain=(new App\Core\Secrets($config['secrets_key']))->decrypt((string)$db->query('SELECT encrypted_settings FROM notification_channel_settings LIMIT 1')->fetchColumn());$assert(is_array(json_decode($plain,true,32,JSON_THROW_ON_ERROR)),'existing ciphertext format decrypts correctly');
    $db->exec("INSERT INTO user_notifications(user_id,type,title,message,url,created_at) VALUES(1,'system','Private test','A system notice.','/dashboard',NOW())");
    $runner=fn()=>(new App\Core\NotificationDispatcher($db,$config,$root))->run(200);
    $before=(int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn();$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+1,'user system notification delivered without Calendar');
    $runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+1,'repeat worker does not duplicate delivery');
    $db->exec("INSERT INTO ai_conversations(id,locale,channel,status,assigned_user_id,created_at,updated_at) VALUES('00000000-0000-4000-8000-000000000001','en','human','assigned',2,NOW(),NOW())");
    $ai=new App\Core\AiRepository($db);$ai->message('00000000-0000-4000-8000-000000000001','visitor','Do not leak this private visitor content.');
    $before=(int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn();$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+1,'chat alerts respect conversation assignment');
    $receipt=$db->query('SELECT * FROM notification_test_receipts ORDER BY id DESC LIMIT 1')->fetch();$assert($receipt['recipient']==='22222222'&&!str_contains($receipt['body'],'private visitor content'),'chat payload contains no visitor message');
    $db->exec("INSERT INTO form_submissions(form_id,uid,payload,campus_id,created_at,updated_at) VALUES(1,UUID(),'{}',1,NOW(),NOW())");
    $before=(int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn();$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+2,'form alerts limited to owner and matching campus');
    $db->exec("INSERT INTO surveys(id,uid,slug,created_at,updated_at) VALUES(1,UUID(),'test-survey',NOW(),NOW())");$db->exec('INSERT INTO survey_campuses(survey_id,campus_id) VALUES(1,2)');
    $db->exec("INSERT INTO survey_responses(id,uid,survey_id,locale,status,started_at,completed_at) VALUES(1,UUID(),1,'en','started',DATE_SUB(NOW(),INTERVAL 5 DAY),NULL),(2,UUID(),1,'en','completed',NOW(),NOW())");
    $before=(int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn();$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+2,'survey alerts obey survey campus scope');
    $db->exec('UPDATE survey_responses SET status="completed",completed_at=DATE_ADD(NOW(),INTERVAL 1 SECOND) WHERE id=1');$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+4,'late completion of older survey ID is not missed');
    App\Core\SystemNotifications::record($db,'handoff-test',0,'chat','00000000-0000-4000-8000-000000000001','Live chat','Waiting for assistance.','/conversations');
    $before=(int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn();$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+1,'durable chat handoff broadcast respects audience');
    $db->exec("INSERT INTO user_notifications(user_id,type,title,message,url,created_at) VALUES(1,'system','Unknown result','Test only.','/dashboard',NOW())");$GLOBALS['notificationTestFail']=true;$runner();$GLOBALS['notificationTestFail']=false;
    $assert((int)$db->query('SELECT COUNT(*) FROM notification_deliveries WHERE status="unknown"')->fetchColumn()===1,'ambiguous delivery marked unknown');$before=(int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn();$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before,'unknown result never automatically resent');
    $db->exec("INSERT INTO user_notifications(user_id,type,title,message,url,created_at) VALUES(2,'system','Revoked','Test only.','/dashboard',NOW())");$notice=(int)$db->lastInsertId();
    $dispatcher=new App\Core\NotificationDispatcher($db,$config,$root);$reflection=new ReflectionClass($dispatcher);$reflection->getProperty('channels')->setValue($dispatcher,$channels->active());$reflection->getProperty('deadline')->setValue($dispatcher,microtime(true)+40);$reflection->getMethod('collect')->invoke($dispatcher,200);
    $db->exec('UPDATE users SET active=0 WHERE id=2');$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_deliveries WHERE status="skipped"')->fetchColumn()>=1,'access is rechecked before sending queued messages');$db->exec('UPDATE users SET active=1 WHERE id=2');
    $sql($stage.'/calendar/migrations/up.sql');$sql($stage.'/calendar/migrations/deliveries-up.sql');
    $db->exec("INSERT INTO extension_packages(type,slug,name,version,publisher,active,manifest,install_path,installed_at,updated_at) VALUES('addon','calendar','Calendar','1.0.2-beta.1','Test',1,'{}','addons/calendar',NOW(),NOW())");
    $db->exec("INSERT INTO calendar_events(id,uid,owner_id,campus_id,title,description,event_type,status,visibility,timezone,start_at,end_at,created_at,updated_at) VALUES(1,UUID(),2,1,'Private calendar test','','meeting','confirmed','participants','UTC',DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 2 HOUR),NOW(),NOW())");$db->exec('INSERT INTO calendar_event_participants(event_id,user_id) VALUES(1,2)');
    App\Core\SystemNotifications::record($db,'calendar-test',2,'calendar','1','Calendar reminder','An event reminder.','/calendar?event=1',['telegram-notifications']);$before=(int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn();$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+1,'Calendar reminder uses shared messenger queue');
    App\Core\SystemNotifications::record($db,'calendar-test',2,'calendar','1','Calendar reminder','An event reminder.','/calendar?event=1');$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+1,'internal and external reminder requests deduplicate');
    $audience=new App\Core\NotificationAudience($db);$assert(!$audience->allows(3,['source'=>'calendar','subject'=>'1']),'Calendar private event does not leak to another campus');
    $assert(!$audience->allows(2,['source'=>'calendar','subject'=>'1','context'=>['start_at'=>'2000-01-01 00:00:00']]),'rescheduled reminder is suppressed');
    $assert(!$audience->allows(2,['source'=>'calendar','subject'=>'1','context'=>['expires_at'=>'2000-01-01 00:00:00']]),'expired reminder is suppressed');
    $db->exec("UPDATE extension_packages SET active=0 WHERE slug='calendar'");$assert(!$audience->allows(2,['source'=>'calendar','subject'=>'1']),'disabled Calendar suppresses Calendar messages');
    $db->exec("INSERT INTO user_notifications(user_id,type,title,message,url,created_at) VALUES(1,'system','After Calendar disabled','System still works.','/dashboard',NOW())");$before=(int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn();$runner();$assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts')->fetchColumn()===$before+1,'system notifications continue when Calendar is disabled');
    $db->exec("UPDATE extension_packages SET active=0 WHERE slug='telegram-notifications'");$assert(count($channels->active())===0,'deactivated plugin cannot send');
    $wa=['enabled'=>1,'consent_confirmed'=>1,'access_token'=>'test-token','phone_number_id'=>'12345678','api_version'=>'v23.0','recipient_map'=>'{"1":"12345678901"}','template_name'=>'eduvixo_alert','template_language'=>'en_US'];
    $channels->save('whatsapp-notifications',$wa);$assert(count($channels->active())===1,'WhatsApp configuration works independently');
    $db->exec("UPDATE notification_channel_settings SET updated_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE plugin_slug='whatsapp-notifications'");
    $db->exec("INSERT INTO user_notifications(user_id,type,title,message,url,created_at) VALUES(1,'system','WhatsApp only','Independent channel.','/dashboard',NOW())");$runner();
    $assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts WHERE recipient="12345678901"')->fetchColumn()>=1,'WhatsApp transports system notifications with Calendar disabled');
    $db->exec("UPDATE extension_packages SET available_version='2.0.0' WHERE slug='whatsapp-notifications'");$runner();
    $assert((int)$db->query('SELECT COUNT(*) FROM notification_test_receipts WHERE title="Extension update available"')->fetchColumn()===1,'update notification delivered to authorized administrator');
    echo 'RESULT '.$count." assertions passed; no external messages sent.\n";
} finally {
    $db=null;unset($GLOBALS['notificationTestDb']);$cleanup=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4','root','');
    if(!preg_match('/^eduvixo_notifications_test_[0-9a-f]{10}$/D',$scratch))throw new RuntimeException('Unsafe cleanup');$cleanup->exec("DROP DATABASE `$scratch`");echo "Scratch database removed.\n";
}
