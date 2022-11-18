#!/usr/bin/php
<?php
$overall_time_counter = microtime(true);
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
$error_log = ROOT_PATH.'/cron/banners/error.log';
$test_performance = ROOT_PATH.'/cron/banners/test_performance.log';
file_put_contents($error_log,'');
file_put_contents($test_performance,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.email.php');
require_once('includes/class.banners.php');
require_once('includes/functions.php'); 

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

$argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//---------- СТАТИСТИКА СПЕЦПРЕДЛОЖЕНИЙ, ОБЩАЯ ----------------------
//подсчет статистики кликов по телефону
for( $i=1; $i<=10; $i++ ){
    $res = $db->querys("
        INSERT INTO ".$sys_tables['banners_stats_show_full']."  
            ( id_parent,amount,date)  
        SELECT 
            id_parent, count(*), CURDATE() - INTERVAL " . $i  . " DAY 
        FROM  ".$sys_tables['banners_stats_show_day']."  
        WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY
        GROUP BY  id_parent 
    ");
    $res = $res && $db->querys("
        INSERT INTO ".$sys_tables['banners_stats_click_full']." 
            ( id_parent,amount,date,`from`, position)  
        SELECT 
            id_parent,  count(*), CURDATE() - INTERVAL " . $i  . " DAY , `from`, position  
        FROM  ".$sys_tables['banners_stats_click_day']." 
        WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY
        GROUP BY  id_parent, `from`, position 
    ");
    $db->querys( " DELETE FROM " . $sys_tables['banners_stats_show_day'] ." WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY ");
    $db->querys( " DELETE FROM " . $sys_tables['banners_stats_click_day'] ." WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY ");
}
?>