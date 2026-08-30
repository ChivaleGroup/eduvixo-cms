<?php
declare(strict_types=1);
foreach(['/var/www/clients/client9/web121/web','/var/www/clients/client59/web119/web']as$root){
    foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);
    $config=(static fn($path)=>require $path)($root.'/config/app.php');$status=json_decode((string)@file_get_contents($root.'/storage/license/status.json'),true);
    $license=is_array($status['license']??null)?$status['license']:[];
    $safe=[];foreach(['ProductName','ProductModel','ProductVersion','DomainUrl','Status','ValidUntil','LicenseStatus','ExpirationDate','domain','status','expires_at','version','product','valid_from','valid_to']as$key)if(array_key_exists($key,$license))$safe[$key]=$license[$key];
    echo json_encode(['host'=>parse_url($config['base_url'],PHP_URL_HOST),'base_url'=>$config['base_url'],'engine'=>$config['engine_version'],'status_domain'=>$status['domain']??null,'checked_at'=>$status['checked_at']??null,'fresh'=>hash_equals((string)($status['domain']??''),(string)$config['base_url'])&&((int)($status['checked_at']??0)+(int)$config['license']['grace_period']>time()),'license'=>$safe,'license_fields'=>array_values(array_filter(array_keys($license),static fn($key)=>!preg_match('/key|token|secret|password|private/i',$key)))],JSON_UNESCAPED_SLASHES).PHP_EOL;
}
