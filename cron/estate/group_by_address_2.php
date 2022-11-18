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

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/cottages/error.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');
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
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
include('includes/class.robot.php');      // 

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


//$estates = array('build', 'live','commercial','country');
$estates = array('build');

foreach($estates as $estate_type){
    //$list = $db->fetchall("SELECT * FROM ".$sys_tables[$estate_type]." WHERE group_id = 0 AND id_user = 27051 AND published=1 ORDER BY id DESC LIMIT 15000");
    $list = $db->fetchall("SELECT * FROM ".$sys_tables[$estate_type]." WHERE id IN(17161839,17520650,17869304,17869314,17730105,17755449,17686043,18052440,18052441,18052442,18052443,18094781,18167639)
                           ORDER BY id DESC");
    echo $db->last_query;
    echo $db->error;
    foreach($list as $k=>$item){
        $robot = new Robot($item['id_user']);
        $robot->groupByAddress($estate_type, $item, false);
    }
}
?>