#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 


/**
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
$sys_tables = Config::$sys_tables;
$tables = array('cottages', 'business_centers', 'housing_estates');
foreach($tables as $table){
    $db->query("UPDATE ".$sys_tables[$table]." SET random_sorting = 0");
    $list = $db->fetchall("SELECT id FROM ".$sys_tables[$table]." WHERE advanced = 1");
    foreach($list as $k=>$item) $db->query("UPDATE ".$sys_tables[$table]." SET random_sorting = ? WHERE id = ?", mt_rand(1,1000), $item['id']);
}
?>