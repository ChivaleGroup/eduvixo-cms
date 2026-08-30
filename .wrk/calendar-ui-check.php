<?php
declare(strict_types=1);
// Local UI fixture only. No production data, credentials or write endpoints.
$root=dirname(__DIR__);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$assets=['/calendar.css'=>['text/css',$root.'/.plugins/EduvixoCalendar/source/assets/calendar.css'],'/calendar.js'=>['text/javascript',$root.'/.plugins/EduvixoCalendar/source/assets/calendar.js']];
if(isset($assets[$path])){header('Content-Type: '.$assets[$path][0]);readfile($assets[$path][1]);exit;}
if($path!=='/'){http_response_code(404);exit;}
$calendarBoot=['events'=>[],'options'=>['campuses'=>[],'users'=>[],'resources'=>[],'types'=>['general','lesson','meeting'],'channels'=>['internal'],'reminder_offsets'=>[['value'=>60,'label'=>'1 hour before']]],'permissions'=>['manage'=>true,'settings'=>true],'notifications'=>[],'integrations'=>[]];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Calendar UI verification - local only</title><link rel="stylesheet" href="/calendar.css"><style>*{box-sizing:border-box}body{margin:0;padding:24px;background:#f3f6fb;font-family:system-ui,sans-serif}.btn{display:inline-flex;gap:8px;align-items:center;padding:12px 18px;border:0;border-radius:10px}.bg-primary{background:#1259d5}.text-white{color:white}.sr-only{position:absolute;width:1px;height:1px;overflow:hidden}h1,h2,p{margin-top:0}button{cursor:pointer}</style></head><body><?php require $root.'/.plugins/EduvixoCalendar/source/views/calendar.php';?><script src="/calendar.js"></script></body></html>
