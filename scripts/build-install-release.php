<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$project=dirname(__DIR__);$source=$project.'/.cms/source';$release=json_decode((string)file_get_contents($source.'/app/release.json'),true,16,JSON_THROW_ON_ERROR);
$version=(string)($release['version']??'');$channel=(string)($release['channel']??'');
if(!preg_match('/^\d+\.\d+\.\d+$/D',$version)||$channel!=='stable')throw new RuntimeException('The clean installer can be published only from valid Stable release metadata.');
$outputs=[$project.'/.cms/eduvixo-install-'.$version.'.zip',$project.'/storage/marketplace/packages/eduvixo-install-'.$version.'.zip'];
foreach($outputs as$output)if(is_file($output))throw new RuntimeException('Immutable installer already exists: '.basename($output));
$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source,FilesystemIterator::SKIP_DOTS))as$file){
    if(!$file->isFile()||$file->isLink())continue;$path=str_replace('\\','/',substr($file->getPathname(),strlen($source)+1));
    if($path==='.env'||str_starts_with($path,'.env.')||preg_match('#^(?:\.cfg|\.git|\.wrk|packages)(?:/|$)#D',$path)||str_starts_with($path,'storage/')&&$path!=='storage/.gitkeep')continue;
    $files[$path]=$file->getPathname();
}
ksort($files);foreach(['.htaccess','app/release.json','config/app.php','database/migrations/001_core.sql','public/.htaccess','public/index.php','README.md','themes/eduvixo/theme.json','storage/.gitkeep']as$required)if(!isset($files[$required]))throw new RuntimeException('Installer source is incomplete: '.$required);
$temp=$outputs[0].'.'.bin2hex(random_bytes(5)).'.tmp';$zip=new ZipArchive();if($zip->open($temp,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Cannot create installer archive.');
try{foreach($files as$path=>$file)if(!$zip->addFile($file,$path))throw new RuntimeException('Cannot add installer file: '.$path);}finally{if(!$zip->close())throw new RuntimeException('Cannot finalize installer archive.');}
$verify=new ZipArchive();if($verify->open($temp,ZipArchive::RDONLY)!==true)throw new RuntimeException('Cannot reopen installer archive.');
try{if($verify->numFiles!==count($files))throw new RuntimeException('Installer inventory mismatch.');for($i=0;$i<$verify->numFiles;$i++){$name=$verify->getNameIndex($i);if(!isset($files[$name])||str_contains($name,'..')||str_starts_with($name,'/')||str_contains($name,'\\'))throw new RuntimeException('Unsafe installer entry.');$verify->getExternalAttributesIndex($i,$opsys,$attributes);if(($attributes>>16&0170000)===0120000)throw new RuntimeException('Installer contains a symbolic link.');}}finally{$verify->close();}
if(!rename($temp,$outputs[0])||!copy($outputs[0],$outputs[1]))throw new RuntimeException('Cannot publish installer archive.');
echo json_encode(['version'=>$version,'channel'=>$channel,'files'=>count($files),'size'=>filesize($outputs[0]),'sha256'=>hash_file('sha256',$outputs[0])],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
