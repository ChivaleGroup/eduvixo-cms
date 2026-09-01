<?php
declare(strict_types=1);
$root=dirname(__DIR__).'/.plugins/EduvixoCalendar/source/lang';
$flatten=static function(array$value,string$prefix='')use(&$flatten):array{$result=[];foreach($value as$key=>$item){$path=$prefix===''?(string)$key:$prefix.'.'.$key;if(is_array($item))$result+=$flatten($item,$path);else$result[$path]=$item;}return$result;};
$reference=$flatten(json_decode((string)file_get_contents($root.'/en.json'),true,64,JSON_THROW_ON_ERROR));$errors=[];
foreach(['de','en','lo','pl','th','vi','zh']as$locale){$copy=$flatten(json_decode((string)file_get_contents($root.'/'.$locale.'.json'),true,64,JSON_THROW_ON_ERROR));$missing=array_diff_key($reference,$copy);$extra=array_diff_key($copy,$reference);$empty=array_filter($copy,static fn($value):bool=>is_string($value)&&trim($value)==='');if($missing||$extra||$empty)$errors[$locale]=['missing'=>array_keys($missing),'extra'=>array_keys($extra),'empty'=>array_keys($empty)];}
if($errors)throw new RuntimeException(json_encode($errors,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo json_encode(['ok'=>true,'locales'=>7,'keys'=>count($reference)],JSON_UNESCAPED_SLASHES).PHP_EOL;
