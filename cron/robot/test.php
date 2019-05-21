#!/usr/bin/php
<?php
// переход в корневую папку сайта
echo $root = realpath('/home/bsn/sites/bsn.ru/public_html/' );
echo "\n";
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
echo $_SERVER['PHP_SELF'];
echo "\n";
echo 
is_running($_SERVER['PHP_SELF']);
echo "\n";
echo ROOT_PATH;
echo "\n";
    
    
    
?>

