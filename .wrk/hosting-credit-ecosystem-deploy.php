<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$archive = '/root/eduvixo-deploy/hosting-credit-ecosystem-1.0.5.tar.gz';
$website = '/var/www/clients/client9/web123/web';
$sites = [
    ['name'=>'demo','root'=>'/var/www/clients/client9/web121/web','owner'=>'web121','group'=>'client9'],
    ['name'=>'shoudu','root'=>'/var/www/clients/client59/web119/web','owner'=>'web119','group'=>'client59'],
];
$stamp = gmdate('Ymd-His');
$stage = '/root/eduvixo-deploy/hosting-credit-ecosystem-' . $stamp;
$backup = '/root/eduvixo-backups/hosting-credit-ecosystem-pre-' . $stamp;
$run = static function (array $command, ?string $output = null): void {
    $spec = [0=>['pipe','r'],1=>$output?['file',$output,'wb']:STDOUT,2=>STDERR];
    $process = proc_open($command, $spec, $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start deployment command.');
    fclose($pipes[0]);
    if (proc_close($process) !== 0) throw new RuntimeException('Deployment command failed: ' . implode(' ', $command));
};
$copy = static function (string $source, string $target, string $owner, string $group, int $mode = 0640): void {
    if (!is_file($source)) throw new RuntimeException('Missing staged file: ' . $source);
    $parent = dirname($target);
    $created = !is_dir($parent);
    if ($created && !mkdir($parent, 0750, true) && !is_dir($parent)) throw new RuntimeException('Cannot create target directory.');
    if ($created && (!chmod($parent, 0750) || !chown($parent, $owner) || !chgrp($parent, $group))) throw new RuntimeException('Cannot secure target directory.');
    $temporary = $target . '.eduvixo-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, $mode) || !chown($temporary, $owner) || !chgrp($temporary, $group) || !rename($temporary, $target)) throw new RuntimeException('Atomic publish failed: ' . $target);
};
$load = static function (string $root): array {
    foreach (array_keys(getenv()) as $key) if (str_starts_with($key, 'CMS_')) putenv($key);
    return (static fn(string $path): array => require $path)($root . '/config/app.php');
};

if (!is_file($archive) || !mkdir($stage, 0700, true) || !mkdir($backup, 0700, true)) throw new RuntimeException('Private deployment workspace is unavailable.');
$run(['tar','-xzf',$archive,'-C',$stage]);
$websiteFiles = [
    'app/views/layout.php','app/views/pages/home.php','app/views/pages/product.php','app/views/pages/services.php','app/views/partials/ecosystem.php',
    'lang/en.json','lang/de.json','lang/zh.json','lang/vi.json','lang/th.json','lang/lo.json','lang/pl.json',
    'resources/pages.css','public/assets/css/site.min.css','config/marketplace.php','storage/marketplace/core-release.json','storage/marketplace/official-catalog.json',
    'storage/marketplace/packages/eduvixo-core-1.0.5.zip','storage/marketplace/packages/eduvixo-theme-1.1.7.zip',
];
foreach (array_merge($websiteFiles, ['.wrk/system-update-bootstrap.php','.wrk/theme-update-bootstrap.php']) as $file) if (!is_file($stage . '/' . $file)) throw new RuntimeException('Incomplete release archive: ' . $file);
foreach (array_filter($websiteFiles, static fn(string $file): bool => str_ends_with($file, '.php')) as $file) $run(['php','-l',$stage . '/' . $file]);
$run(['php','-l',$stage . '/.wrk/system-update-bootstrap.php']);
$run(['php','-l',$stage . '/.wrk/theme-update-bootstrap.php']);
require $stage . '/.cms/source/app/Core/OfficialCatalog.php';
$catalog = App\Core\OfficialCatalog::verify((string) file_get_contents($stage . '/storage/marketplace/official-catalog.json'));
if (($catalog['core']['version'] ?? '') !== '1.0.5' || count((array) ($catalog['products'] ?? [])) !== 13) throw new RuntimeException('Signed catalog is incomplete.');
$theme = array_values(array_filter($catalog['products'], static fn(array $product): bool => ($product['slug'] ?? '') === 'eduvixo'))[0] ?? null;
if (($theme['version'] ?? '') !== '1.1.7') throw new RuntimeException('Signed theme release is missing.');
if (!hash_equals((string)$catalog['core']['checksum'], hash_file('sha256',$stage.'/storage/marketplace/packages/eduvixo-core-1.0.5.zip'))) throw new RuntimeException('Core checksum mismatch.');
if (!hash_equals((string)$theme['package_checksum'], hash_file('sha256',$stage.'/storage/marketplace/packages/eduvixo-theme-1.1.7.zip'))) throw new RuntimeException('Theme checksum mismatch.');

foreach ($sites as $site) {
    $config = $load($site['root']);
    if (!str_starts_with((string)($config['base_url']??''), 'https://')) throw new RuntimeException('Unexpected installation URL for ' . $site['name']);
    $database = (string) $config['database']['name'];
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $database)) throw new RuntimeException('Unsafe database identity.');
    $run(['mariadb-dump','--socket=/run/mysqld/mysqld.sock','--user=root','--single-transaction','--routines','--triggers','--databases',$database],$backup.'/'.$site['name'].'.sql');
    $run(['tar','-czf',$backup.'/'.$site['name'].'.tar.gz','-C',$site['root'],'.']);
}
$run(['tar','-czf',$backup.'/website.tar.gz','-C',$website,'.']);
file_put_contents($backup.'/ROLLBACK.txt', "Use each installation's latest storage/system-updates recovery package for the core rollback and its extension release archive for the Eduvixo theme rollback. Restore website.tar.gz to revert the product website and signed catalog. Full CMS tar and SQL backups are disaster-recovery fallbacks; preserve post-backup writes before restoring them. Validate ownership, PHP syntax, license enforcement, login, theme state and public pages after rollback.\n", LOCK_EX);
foreach (glob($backup.'/*') ?: [] as $file) { chmod($file,0600); if (filesize($file)<100) throw new RuntimeException('Incomplete backup: '.$file); echo 'BACKUP '.basename($file).' '.filesize($file).' '.hash_file('sha256',$file).PHP_EOL; }

foreach ($websiteFiles as $file) $copy($stage.'/'.$file,$website.'/'.$file,'web123','client9',str_contains($file,'/scripts/')?0750:0640);
foreach ($sites as $site) {
    $coreBootstrap=$site['root'].'/storage/system-update-bootstrap-1.0.5.php';
    $copy($stage.'/.wrk/system-update-bootstrap.php',$coreBootstrap,$site['owner'],$site['group'],0600);
    try { $run(['runuser','-u',$site['owner'],'--','php',$coreBootstrap,$site['root'],'1.0.5']); } finally { if (is_file($coreBootstrap)) unlink($coreBootstrap); }
    $themePackage=$site['root'].'/storage/eduvixo-theme-1.1.7.zip';
    $themeBootstrap=$site['root'].'/storage/theme-update-bootstrap-1.1.7.php';
    $copy($stage.'/storage/marketplace/packages/eduvixo-theme-1.1.7.zip',$themePackage,$site['owner'],$site['group'],0600);
    $copy($stage.'/.wrk/theme-update-bootstrap.php',$themeBootstrap,$site['owner'],$site['group'],0600);
    try { $run(['runuser','-u',$site['owner'],'--','php',$themeBootstrap,$site['root'],$themePackage,'1.1.7']); } finally { if (is_file($themeBootstrap)) unlink($themeBootstrap); if (is_file($themePackage)) unlink($themePackage); }
}

foreach ($sites as $site) {
    $run(['php','-l',$site['root'].'/public/index.php']);
    $run(['php','-l',$site['root'].'/app/Http/DashboardController.php']);
    $run(['php','-l',$site['root'].'/themes/eduvixo/views/page.php']);
    $release=json_decode((string)file_get_contents($site['root'].'/app/release.json'),true,16,JSON_THROW_ON_ERROR);
    $themeManifest=json_decode((string)file_get_contents($site['root'].'/themes/eduvixo/theme.json'),true,32,JSON_THROW_ON_ERROR);
    if (($release['version']??'')!=='1.0.5'||($themeManifest['version']??'')!=='1.1.7') throw new RuntimeException($site['name'].' version verification failed.');
    if (is_file($site['root'].'/storage/system-updates/maintenance.json')) throw new RuntimeException($site['name'].' remained in maintenance mode.');
    $config=$load($site['root']);$db=(new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$config['database']['name'].';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION])) ;
    $q=$db->prepare('SELECT value FROM settings WHERE `key`=?');$q->execute(['show_hosting_credit']);$stored=$q->fetchColumn();
    if ($stored!==false && json_decode((string)$stored,true)!==true) throw new RuntimeException($site['name'].' did not preserve the default-visible hosting credit.');
}
echo json_encode(['ok'=>true,'version'=>'1.0.5','theme'=>'1.1.7','backup'=>$backup,'stage'=>$stage,'sites'=>array_column($sites,'name')],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
