#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);

//папка с txt файлами 
$dir = ROOT_PATH."/img/uploads/";

$dh = opendir($dir);
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..')
    {
     $info = pathinfo( $filename );
     if(!empty($info['extension']) && in_array($info['extension'],['jpg','jpeg','png','gif','bmp','webp','avif','svg'])){
        echo $filename ."\n";
         unlink($dir.$filename);
     }
    }
}