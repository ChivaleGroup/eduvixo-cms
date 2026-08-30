<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$root='/var/www/clients/client9/web121/web';
spl_autoload_register(static function($c)use($root){if(str_starts_with($c,'App\\'))require $root.'/app/'.str_replace('\\','/',substr($c,4)).'.php';});
$config=require $root.'/config/app.php';$db=(new App\Core\Database($config['database']))->connection();
$r=['packages'=>$db->query("SELECT type,slug,version,active,install_path FROM extension_packages WHERE slug='calendar' OR slug LIKE '%calendar%' OR slug LIKE '%notifications'")->fetchAll(), 'calendar_tables'=>$db->query("SHOW TABLES LIKE 'calendar_%'")->fetchAll(PDO::FETCH_COLUMN)];
foreach(['calendar_events','calendar_reminders','calendar_integration_settings'] as $table)$r['counts'][$table]=(int)$db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
require $root.'/addons/calendar/src/CalendarRepository.php';
require $root.'/addons/calendar/src/CalendarIntegrationManager.php';
try{$repo=new Eduvixo\Calendar\CalendarRepository($db);$r['options_counts']=array_map('count',$repo->options(null));$r['events_count']=count($repo->events('2026-08-01','2026-10-01',null));}catch(Throwable $e){$r['repository_error']=$e->getMessage();}
try{$r['integrations']=array_column((new Eduvixo\Calendar\CalendarIntegrationManager($db,$root,$config['secrets_key']))->catalog(),'slug');}catch(Throwable $e){$r['integration_error']=$e->getMessage();}
$r['calendar_permissions']=$db->query("SELECT slug FROM permissions WHERE slug LIKE 'calendar.%'")->fetchAll(PDO::FETCH_COLUMN);
$r['navigation']=(new App\Core\PackageManager($db,$root,'1.0'))->navigation(['system.owner']);
echo json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
