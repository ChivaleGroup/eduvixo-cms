<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-cache');header('X-Content-Type-Options: nosniff');header('X-Robots-Tag: noindex');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit('{"error":"Method not allowed"}');}
$file=dirname(__DIR__,5).'/storage/marketplace/official-catalog.json';
if(!is_file($file)){http_response_code(503);exit('{"error":"Catalog unavailable"}');}
readfile($file);
