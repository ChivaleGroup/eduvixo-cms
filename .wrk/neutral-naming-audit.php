<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$sites=['demo'=>'/var/www/clients/client9/web121/web','shoudu'=>'/var/www/clients/client59/web119/web'];
foreach($sites as$name=>$root){
    foreach(array_keys(getenv())as$key)if(str_starts_with($key,'CMS_'))putenv($key);
    $config=(static fn(string$path):array=>require$path)($root.'/config/app.php');
    $db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$config['database']['name'].';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $release=json_decode((string)file_get_contents($root.'/app/release.json'),true,16,JSON_THROW_ON_ERROR);
    $packages=$db->query("SELECT type,slug,name,version,source,active FROM extension_packages WHERE slug IN ('eduvixo','shoudu','calendar','google-calendar','apple-calendar','microsoft-365-calendar','telegram-notifications','whatsapp-notifications','google-analytics','ai-translation-assistant') ORDER BY type,slug")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['site'=>$name,'version'=>$release['version']??null,'packages'=>$packages],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
}
