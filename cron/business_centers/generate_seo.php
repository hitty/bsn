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
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$dir = ROOT_PATH."/cron/cottages/pictures/";
//флаг однократного обновления
    $db->query("INSERT IGNORE INTO ".$sys_tables['pages_seo']." SET url=?, pretty_url=?, title=?, h1_title=?, breadcrumbs=?",
        'cottedzhnye_poselki', 
        'cottedzhnye_poselki', 
        'Коттеджные поселки',
        'Коттеджные поселки', 
        'cottedzhnye_poselki=>Коттеджные поселки'
    );
$list = $db->fetchall("SELECT * FROM ".$sys_tables['cottages']);
foreach($list as $k=>$item){
    $db->query("INSERT IGNORE INTO ".$sys_tables['pages_seo']." SET url=?, pretty_url=?, title=?, h1_title=?, breadcrumbs=?",
        'cottedzhnye_poselki/'.$item['id'], 
        'cottedzhnye_poselki/'.createCHPUTitle($item['title']), 
        $item['title'].' | Коттеджные поселки',
        'Коттеджный поселок', 
        'estate=>Недвижимость,country=>Загородная,cottedzhnye_poselki=>Коттеджный поселок,'.createCHPUTitle($item['title']).'=>'.$item['title']
    );
    
}
     
    $db->query("INSERT IGNORE INTO ".$sys_tables['pages_seo']." SET url=?, pretty_url=?, title=?, h1_title=?, breadcrumbs=?",
        'zhiloy_kompleks', 
        'zhiloy_kompleks', 
        'Жилые комплексы',
        'Жилые комплексы', 
        'zhiloy_kompleks=>Жилые комплексы'
    );
    
$list = $db->fetchall("SELECT * FROM ".$sys_tables['housing_estates']);
foreach($list as $k=>$item){
    $db->query("INSERT IGNORE INTO ".$sys_tables['pages_seo']." SET url=?, pretty_url=?, title=?, h1_title=?, breadcrumbs=?",
        'zhiloy_kompleks/'.$item['id'], 
        'zhiloy_kompleks/'.createCHPUTitle($item['title']), 
        $item['title'].' | Жилые комплексы',
        'Жилой комплекс '.$item['title'], 
        'zhiloy_kompleks=>Жилые комплексы,'.createCHPUTitle($item['title']).'=>'.$item['title']
    );
   
}
?>
