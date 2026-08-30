<?php
declare(strict_types=1);
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
$root=dirname(__DIR__,5);
require $root.'/app/MarketplaceService.php';
$settings=(static fn($path)=>require $path)($root.'/config/site.php');
$market=(static fn($path)=>require $path)($root.'/config/marketplace.php');
(new Eduvixo\Website\MarketplaceService($market,$settings['base_url'],$settings['rate_key']))->streamCoreUpdater();
