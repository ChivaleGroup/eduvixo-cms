<?php
declare(strict_types=1);
foreach (['demo'=>'/var/www/clients/client9/web121/web','shoudu'=>'/var/www/clients/client59/web119/web'] as $site=>$dir) {
    foreach(array_keys(getenv()) as $envKey) if(str_starts_with($envKey,'CMS_')) putenv($envKey);
    $config=(static fn($path)=>require $path)($dir.'/config/app.php');
    $db=new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',$config['database']['host'],$config['database']['port'],$config['database']['name']),$config['database']['user'],$config['database']['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $out=['site'=>$site,'root'=>$dir,'engine'=>$config['engine_version'],'base_url'=>$config['base_url'],'demo_enabled'=>$config['demo']['enabled']??false,'tables'=>$tables,'migrations'=>array_map('basename',glob($dir.'/database/migrations/*.sql')),'files'=>[]];
    foreach(['app/Core/Auth.php','app/Core/CaptchaService.php','app/Http/AuthController.php','public/theme/eduvixo-auth-navigation.js','public/index.php'] as $file) $out['files'][$file]=is_file($dir.'/'.$file)?hash_file('sha256',$dir.'/'.$file):null;
    foreach(['marketplace_sources','extension_publishers'] as $table) if(in_array($table,$tables,true))$out[$table]=$db->query('SELECT * FROM '.$table)->fetchAll();
    if(in_array('is_demo',array_column($db->query('SHOW COLUMNS FROM users')->fetchAll(),'Field'),true)) {
        $query=$db->prepare('SELECT id,name,email,active,is_demo,last_login_at FROM users WHERE LOWER(email)=?');$query->execute(['mario@chivale.email']);$out['mario']=$query->fetchAll();
        foreach($out['mario'] as &$user){$q=$db->prepare('SELECT r.id,r.slug,r.name,r.active FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=?');$q->execute([$user['id']]);$user['roles']=$q->fetchAll();}unset($user);
    }
    $out['settings']=$db->query("SELECT * FROM settings WHERE `key` IN ('active_theme','captcha_settings','admin_captcha','language')")->fetchAll();
    $out['migration_rows']=$db->query('SELECT * FROM migrations')->fetchAll();
    echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
