#!/usr/bin/php
<?php
error_reporting(0);
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
define('TEST_MODE', !empty($_SERVER['SCRIPT_FILENAME']) && preg_match('/test\.bsn\.ru/sui', $_SERVER['SCRIPT_FILENAME']) ? true : false);

$root = TEST_MODE ? realpath( '/home/bsn/sites/test.bsn.ru/public_html/trunk/' ) : ( DEBUG_MODE ? realpath( "../.." ) : realpath('/home/bsn/sites/bsn.ru/public_html/' ) ) ;

if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/comagic/spam_error.log';
file_put_contents($error_log,'');
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
require_once('includes/class.email.php');        // для отправки писем
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;        // для отправки писем
require_once('includes/functions.php');    // функции  из крона
require_once('includes/getid3/getid3.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql_remote']['host'], Config::$values['mysql_remote']['user'], Config::$values['mysql_remote']['pass']);
$db->querys("set names ".Config::$values['mysql_remote']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$db->querys( " DELETE FROM " . $sys_tables['estate_complexes_stats_full_clicks'] . ' WHERE id_parent = 539 AND type = 3 AND YEAR(date) = 2019');
$db->querys( " DELETE FROM " . $sys_tables['estate_complexes_stats_full_shows'] . ' WHERE id_parent = 539 AND type = 3 AND YEAR(date) = 2019');

for( $i=0; $i < 80; $i++ ){
    $data = [
        'id_parent' => 539,
        'type' => 3,
        'date' => ' "2019-01-01" + INTERVAL ' . $i .' DAY',
        'amount' => mt_rand( 1, 4 ) + mt_rand( -2, 2 )
    ];
    $amount = mt_rand( 1, 6 ) + mt_rand( -3, 3 );
    if( $amount <= 0 ) $amount = 1;
    
    $db->querys( " INSERT INTO " .$sys_tables['estate_complexes_stats_full_clicks'] . " SET 
                    id_parent = 539,
                    type = 3,
                    date = '2019-01-01' + INTERVAL " . $i ." DAY,
                    amount = " . $amount 
    );
                    
    $amount = ( $amount * 8 ) + mt_rand( 10, 30 );
    $db->querys( " INSERT INTO " .$sys_tables['estate_complexes_stats_full_shows'] . " SET 
                    id_parent = 539,
                    type = 3,
                    date = '2019-01-01' + INTERVAL " . $i ." DAY,
                    amount = " . $amount 
    );
}
?>
