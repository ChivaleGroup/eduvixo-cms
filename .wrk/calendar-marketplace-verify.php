<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$root=$argv[1]??dirname(__DIR__);$config=require $root.'/config/marketplace.php';$icons=file_get_contents($root.'/public/assets/icons.svg');$count=0;
foreach($config['packages']as$id=>$package){
    if(!str_contains($icons,'id="'.$package['icon'].'"'))throw new RuntimeException('Missing icon: '.$package['slug']);
    foreach(($package['variants']??[$package])as$variant){if(!is_file($variant['file'])||filesize($variant['file'])!==$variant['size']||!hash_equals($variant['checksum'],hash_file('sha256',$variant['file'])))throw new RuntimeException('Package integrity failure: '.$package['slug']);$count++;}
    if(($package['release_channel']??'stable')==='beta'){
        $zip=new ZipArchive();if($zip->open($package['file'],ZipArchive::RDONLY)!==true)throw new RuntimeException('Unreadable package.');
        $raw=$zip->getFromName('eduvixo-package.json');$signature=base64_decode($zip->getFromName('signature.ed25519'),true);$public=base64_decode('q+WweIoNkskiUOzyLl80Bc9V2TkBdHXXrtOufSRIg54=',true);
        if(!sodium_crypto_sign_verify_detached($signature,$raw,$public))throw new RuntimeException('Invalid publisher signature.');
        $manifest=json_decode($raw,true,64,JSON_THROW_ON_ERROR);if($manifest['version']!==$package['version']||$manifest['release_channel']!=='beta')throw new RuntimeException('Manifest/catalog mismatch.');
        foreach($manifest['files']as$file=>$hash){$content=$zip->getFromName('payload/'.$file);if(!is_string($content)||!hash_equals($hash,hash('sha256',$content)))throw new RuntimeException('Payload integrity failure.');}
        $zip->close();
    }
}
foreach(['en','de','zh','vi','th','lo','pl']as$lang){$data=json_decode(file_get_contents($root.'/lang/'.$lang.'.json'),true,512,JSON_THROW_ON_ERROR);foreach($config['packages']as$package){$key=substr($package['copy_key'],12);if(empty($data['marketplace'][$key]))throw new RuntimeException('Missing product translation.');}if(empty($data['marketplace']['beta'])||empty($data['marketplace']['calendar_beta_notice']))throw new RuntimeException('Missing beta translation.');}
echo json_encode(['ok'=>true,'products'=>count($config['packages']),'files'=>$count,'languages'=>7]).PHP_EOL;
