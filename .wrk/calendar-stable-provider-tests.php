<?php
declare(strict_types=1);
$root=dirname(__DIR__).'/.plugins';
$googleProvider=require $root.'/EduvixoGoogleCalendar/source/src/provider.php';
$appleProvider=require $root.'/EduvixoAppleCalendar/source/src/provider.php';
$microsoftProvider=require $root.'/EduvixoMicrosoft365/source/src/provider.php';
$event=['uid'=>'stable-test','title'=>'All-day DST test','description'=>'','location'=>'','all_day'=>true,'timezone'=>'Europe/Warsaw','start_at'=>'2026-10-23 22:00:00','end_at'=>'2026-10-25 23:00:00'];$tests=0;
$assert=static function(bool$condition,string$name)use(&$tests):void{if(!$condition)throw new RuntimeException('FAIL: '.$name);$tests++;echo 'PASS '.$name.PHP_EOL;};
$google=$googleProvider->payload($event);$assert(($google['start']['date']??'')==='2026-10-24'&&($google['end']['date']??'')==='2026-10-26','Google exclusive all-day dates across DST');
$apple=$appleProvider->calendarData($event);$assert(str_contains($apple,"DTSTART;VALUE=DATE:20261024\r\n")&&str_contains($apple,"DTEND;VALUE=DATE:20261026\r\n"),'Apple RFC 5545 all-day dates');
$microsoft=$microsoftProvider->payload($event);$assert(($microsoft['isAllDay']??false)===true&&($microsoft['start']['dateTime']??'')==='2026-10-24T00:00:00'&&($microsoft['end']['dateTime']??'')==='2026-10-26T00:00:00'&&($microsoft['start']['timeZone']??'')==='Europe/Warsaw','Microsoft all-day local midnights');
echo json_encode(['ok'=>true,'tests'=>$tests]).PHP_EOL;
