<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
$production='/var/www/clients/client9/web121/web';
$source=$argv[1]??__DIR__.'/calendar-candidate';
require_once $source.'/src/CalendarConflictException.php'; require_once $source.'/src/CalendarRepository.php';
spl_autoload_register(static function(string $class)use($production):void { if(str_starts_with($class,'App\\')) { $file=$production.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require $file; } });
require_once $source.'/src/CalendarIntegrationManager.php';require_once $source.'/src/CalendarDispatcher.php';
$config=require $production.'/config/app.php'; $live=$config['database']['name'];
if(!preg_match('/^[a-zA-Z0-9_]+$/D',$live))throw new RuntimeException('Invalid source DB identifier.');
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$scratch='eduvixo_calendar_test_'.bin2hex(random_bytes(5)); echo 'Scratch database: '.$scratch.PHP_EOL; $db->exec("CREATE DATABASE `$scratch` CHARACTER SET utf8mb4"); $db->exec("USE `$scratch`");
$tests=0;
$assert=static function(bool $ok,string $name)use(&$tests):void {if(!$ok)throw new RuntimeException('FAIL: '.$name);$tests++;echo 'PASS '.$name.PHP_EOL;};
$sql=static function(string $path)use($db):void {foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\R|$)/',(string)file_get_contents($path)))) as $statement)$db->exec($statement);};
try {
    foreach(['users','roles','permissions','role_permissions','campuses','campus_translations','languages','activity_log','extension_packages'] as $table)$db->exec("CREATE TABLE `$table` LIKE `$live`.`$table`");
    $db->exec("INSERT INTO roles(slug,name) VALUES ('owner','Test owner')");
    $db->exec("INSERT INTO languages(locale,name,native_name,enabled,is_default,sort_order) VALUES ('en','English','English',1,1,1)");
    for($i=1;$i<=3;$i++)$db->prepare('INSERT INTO users(id,name,username,email,password,active,created_at,updated_at) VALUES (?,?,?,?,?,1,NOW(),NOW())')->execute([$i,'Calendar test '.$i,'caltest'.$i,'caltest'.$i.'@example.invalid',bin2hex(random_bytes(32))]);
    for($i=1;$i<=2;$i++)$db->prepare('INSERT INTO campuses(id,uid,city_slug,campus_slug,status,created_at,updated_at) VALUES (?,?,?,? ,"active",NOW(),NOW())')->execute([$i,sprintf('00000000-0000-4000-8000-%012d',$i),'test-city','test-campus-'.$i]);
    $sql($source.'/migrations/up.sql');$sql($source.'/migrations/up.sql');$assert(true,'migration repeat safety');
    $sql($source.'/migrations/deliveries-up.sql');$sql($source.'/migrations/deliveries-up.sql');$assert(true,'delivery migration repeat safety');
    $repo=new Eduvixo\Calendar\CalendarRepository($db,1,true);$scoped=new Eduvixo\Calendar\CalendarRepository($db,2,false);$outsider=new Eduvixo\Calendar\CalendarRepository($db,3,false);
    $assert(count($repo->options(null)['campuses'])===2,'actual campus schema options');
    $resource=$repo->saveResource(['name'=>'Test room','campus_id'=>1],1,null);
    $input=['title'=>'Test event','start_at'=>'2026-10-20T10:00','end_at'=>'2026-10-20T11:00','timezone'=>'Europe/Warsaw','campus_id'=>1,'participant_ids'=>[1,2],'resource_ids'=>[$resource['id']],'reminder_offsets'=>[0,5,60,1440,21600],'channels'=>['internal'],'visibility'=>'participants'];
    $saved=$repo->save($input,1,null,false);$id=$saved['event']['id'];
    $assert($saved['event']['start_at']==='2026-10-20 08:00:00','UTC conversion');
    $assert(count($saved['event']['reminders'])===5,'five supported reminders');
    $assert($scoped->event($id,[1])!==null && $outsider->event($id,[1])===null,'participant detail visibility');
    $assert(count($outsider->events('2026-10-01','2026-11-01',[1]))===0,'participant listing visibility');
    $assert($repo->event($id,[])===null && $repo->event($id,[2])===null,'campus isolation');
    try{$repo->save($input,1,null,false);throw new LogicException('Expected conflict');}catch(Eduvixo\Calendar\CalendarConflictException){$assert(true,'conflicting write rejected');}
    $cross=$input;$cross['campus_id']=2;$cross['resource_ids']=[];
    try{$repo->save($cross,1,null,false);throw new LogicException('Expected cross-campus conflict');}catch(Eduvixo\Calendar\CalendarConflictException){$assert(true,'cross-campus participant conflict');}
    $adjacent=$input;$adjacent['start_at']='2026-10-20T11:00';$adjacent['end_at']='2026-10-20T12:00';$assert($repo->save($adjacent,1,null,false)['occurrences']===1,'adjacent events allowed');
    $update=$input;$update['id']=$id;$update['reminder_offsets']=[];$updated=$repo->save($update,1,null,false);$assert($updated['event']['reminders']===[],'reminders can be disabled');
    $private=$input;$private['id']=$id;$private['visibility']='private';$repo->save($private,1,null,false);$assert($scoped->event($id,[1])===null,'private event owner-only');
    $repo->cancel($id,1,null);$repo->cancel($id,1,null);$assert($repo->event($id,null)['status']==='cancelled','idempotent cancellation');
    $repeat=$input;$repeat['start_at']='2026-10-18T10:00';$repeat['end_at']='2026-10-18T11:00';$repeat['recurrence']='weekly';$repeat['recurrence_until']='2026-11-01';
    $series=$repo->save($repeat,1,null,false);$assert($series['occurrences']===3,'weekly recurrence count');
    $rows=$db->query("SELECT start_at FROM calendar_events WHERE series_uid IS NOT NULL ORDER BY start_at")->fetchAll(PDO::FETCH_COLUMN);$assert($rows[0]==='2026-10-18 08:00:00' && $rows[1]==='2026-10-25 09:00:00','recurrence preserves wall clock across DST');
    $monthly=$repeat;$monthly['start_at']='2027-01-31T10:00';$monthly['end_at']='2027-01-31T11:00';$monthly['recurrence']='monthly';$monthly['recurrence_until']='2027-03-31';$assert($repo->save($monthly,1,null,false)['occurrences']===2,'monthly missing days skipped');
    foreach(['2026-02-30T10:00','','tomorrow'] as $bad){$invalid=$input;$invalid['start_at']=$bad;try{$repo->save($invalid,1,null,false);throw new LogicException('Expected invalid time');}catch(RuntimeException){$assert(true,'invalid date rejected');}}
    $bad=$input;$bad['reminder_offsets']=[7];$bad['start_at']='2028-01-01T10:00';$bad['end_at']='2028-01-01T11:00';try{$repo->save($bad,1,null,false);throw new LogicException('Expected invalid reminder');}catch(RuntimeException){$assert(true,'invalid reminder rejected');}
    $future=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->modify('+20 minutes');
    $notice=$input;$notice['start_at']=$future->format('Y-m-d H:i:s');$notice['end_at']=$future->modify('+1 hour')->format('Y-m-d H:i:s');$notice['timezone']='UTC';$notice['reminder_offsets']=[60];
    $noticeId=$repo->save($notice,1,null,false)['event']['id'];
    $worker=new Eduvixo\Calendar\CalendarDispatcher($db,$config,$production);
    $assert(isset($worker->run()['skipped']),'inactive calendar worker gate');
    $db->exec("INSERT INTO extension_packages(type,slug,name,version,description,publisher,source,signature_status,active,manifest,installed_at,updated_at) VALUES ('addon','calendar','Test calendar','1.0.1-beta.1','','Test','package','verified',1,'{}',NOW(),NOW())");
    $worker->run();$worker->run();
    $count=$db->query('SELECT COUNT(*) FROM calendar_notifications WHERE event_id='.(int)$noticeId)->fetchColumn();
    $assert((int)$count===2,'internal reminders exactly once per recipient');
    $assert((int)$db->query("SELECT COUNT(*) FROM calendar_deliveries WHERE status='sent'")->fetchColumn()===2,'recipient delivery ledger');
    $assert((int)$db->query("SELECT COUNT(*) FROM calendar_reminders WHERE event_id=".(int)$noticeId." AND status='sent'")->fetchColumn()===1,'reminder completion');
    $ids=$db->query('SELECT id FROM calendar_notifications WHERE user_id=1')->fetchAll(PDO::FETCH_COLUMN);
    $assert($repo->readNotifications(3,$ids)===0,'notification read isolation');
    $repo->readNotifications(1,$ids);$assert($repo->notifications(1)[0]['read_at']!==null,'notification acknowledgement');
    $other=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$scratch.';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $lock=substr('eduvixo.calendar.'.hash('sha256',$scratch),0,64);$other->prepare('SELECT GET_LOCK(?,0)')->execute([$lock]);
    try{$repo->save($notice,1,null,false);throw new LogicException('Expected lock contention');}catch(RuntimeException $error){$assert($error->getCode()===409,'concurrent writes serialized');}finally{$other->prepare('SELECT RELEASE_LOCK(?)')->execute([$lock]);}
    $sql($source.'/migrations/deliveries-down.sql');$assert((int)$db->query('SELECT COUNT(*) FROM calendar_deliveries')->fetchColumn()===2,'rollback preserves delivery audit');
    $sql($source.'/migrations/down.sql');$assert(count($db->query("SHOW TABLES LIKE 'calendar_%'")->fetchAll())===1,'reverse migration preserves delivery ledger');
    echo json_encode(['ok'=>true,'tests'=>$tests,'database'=>'isolated scratch only']).PHP_EOL;
} finally {
    if(!preg_match('/^eduvixo_calendar_test_[a-f0-9]{10}$/D',$scratch)||$scratch===$live)throw new RuntimeException('Refuse unsafe cleanup');
    $cleanup=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $cleanup->exec("DROP DATABASE `$scratch`");
}
