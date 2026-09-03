<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
foreach (array_keys(getenv()) as $key) if (str_starts_with($key,'CMS_')) putenv($key);
$root='/var/www/clients/client9/web121/web';
$config=(static fn(string $path):array=>require $path)($root.'/config/app.php');
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$config['database']['name'].';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$package=$db->query("SELECT type,slug,name,version,active,signature_status,install_path,package_checksum FROM extension_packages WHERE type='plugin' AND slug='ai-translation-assistant'")->fetch()?:null;
$runtime=$db->query("SELECT slug,version,active,JSON_UNQUOTE(JSON_EXTRACT(settings,'$.provider')) provider,JSON_UNQUOTE(JSON_EXTRACT(settings,'$.endpoint')) endpoint,JSON_UNQUOTE(JSON_EXTRACT(settings,'$.model')) model,COALESCE(CHAR_LENGTH(JSON_UNQUOTE(JSON_EXTRACT(settings,'$.api_key_encrypted'))),0) encrypted_length,NULLIF(JSON_UNQUOTE(JSON_EXTRACT(settings,'$.verified_at')),'null') verified_at FROM installed_plugins WHERE slug='ai-translation-assistant'")->fetch()?:null;
echo json_encode(['package'=>$package,'runtime'=>$runtime,'files'=>is_file($root.'/plugins/ai-translation-assistant/plugin.json')],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
