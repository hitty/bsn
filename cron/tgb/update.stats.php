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
$error_log = ROOT_PATH.'/cron/tgb/error.log';
$test_performance = ROOT_PATH.'/cron/tgb/test_performance.log';
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
require_once('includes/class.tgb.php');
require_once('includes/functions.php'); 

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

$argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//---------- СТАТИСТИКА СПЕЦПРЕДЛОЖЕНИЙ, ОБЩАЯ ----------------------

$tables = [
    [
        'click_day' => 'phone_clicks_day',
        'click_full' => 'phone_clicks_full'
    ]
    ,[
        'click_day' => 'tgb_stats_day_clicks',
        'click_full' => 'tgb_stats_full_clicks',
        'show_day' => 'tgb_stats_day_shows',
        'show_full' => 'tgb_stats_full_shows'
    ]
    ,[
        'click_day' => 'banners_stats_click_day',
        'click_full' => 'banners_stats_click_full',
        'show_day' => 'banners_stats_show_day',
        'show_full' => 'banners_stats_show_full'
    ]
];
$tables = [];

foreach( $tables as $table ) {
    $click_info = !empty( $sys_tables[ $table['click_day'] ]) ? $db->prepareNewRecord( $sys_tables[ $table['click_day'] ]) : false ;
    $click_fields = [];
    array_key_exists( 'id_parent', $click_info) ? $click_fields[] = "id_parent" : "";
    array_key_exists( 'in_estate', $click_info) ? $click_fields[] = "in_estate" : "";
    array_key_exists( 'id_object', $click_info) ? $click_fields[] = "id_object" : "";
    array_key_exists( 'type', $click_info) ? $click_fields[] = "type" : "";
    array_key_exists( 'status', $click_info) ? $click_fields[] = "status" : "";
    array_key_exists( 'from', $click_info) ? $click_fields[] = "`from`" : "" ;
    $click_fields = implode( ', ', $click_fields );
    
    $show_info = !empty( $sys_tables[ $table['show_day'] ]) ? $db->prepareNewRecord( $sys_tables[ $table['show_day'] ]) : false ;
    for( $i=7; $i>=1; $i-- ){
        if( !empty( $sys_tables[ $table['show_full'] ] ) ) {
            $res = $db->querys("
                INSERT INTO ".$sys_tables[ $table['show_full'] ]."  
                    ( 
                        id_parent,
                        " . ( array_key_exists( 'in_estate', $show_info) ? "in_estate," : "" ) . " 
                        " . ( array_key_exists( 'id_object', $show_info) ? "id_object," : "" ) . " 
                        " . ( array_key_exists( 'type', $show_info) ? "type," : "" ) . " 
                        " . ( array_key_exists( 'status', $show_info) ? "status," : "" ) . " 
                        amount,
                        date
                    )  
                SELECT 
                    id_parent,
                    " . ( array_key_exists( 'in_estate', $show_info) ? "in_estate," : "" ) . " 
                    count(*), 
                    CURDATE() - INTERVAL " . $i  . " DAY 
                FROM  ".$sys_tables[ $table['show_day'] ]."  
                WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY
                GROUP BY  
                    id_parent
                    " . ( array_key_exists( 'in_estate', $show_info) ? ",in_estate" : "" ) . "  
            ");
             $db->querys( " DELETE FROM " . $sys_tables[ $table['show_day'] ] ." WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY ");
        }
        
        if( !empty( $sys_tables[ $table['click_full'] ] ) ) {
            $res = $db->querys("
                INSERT INTO ".$sys_tables[ $table['click_full']  ]." 
                    ( 
                        " . $click_fields . "
                        " . ( array_key_exists( 'amount', $click_info) ? ",amount" : "" ) . "
                        " . ( array_key_exists( 'date', $click_info) ? ",date" : "" ) . " 

                    )  
                SELECT 
                    " . $click_fields . "
                    " . ( array_key_exists( 'amount', $click_info) ? ",count(*)" : "" ) . "
                    " . ( array_key_exists( 'date', $click_info) ? ",CURDATE() - INTERVAL " . $i  . " DAY" : "" ) . " 
                FROM  ".$sys_tables[ $table['click_day']  ]." 
                WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY
                GROUP BY  
                    id_parent
                    , `from`
                    , position
                    " . ( array_key_exists( 'in_estate', $click_info) ? ",in_estate" : "" ) . "  
            ");
            $db->querys( " DELETE FROM " . $sys_tables[ $table['click_day'] ] ." WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY ");
        }
    }
}


for( $i=7; $i>=1; $i-- ){
    $db->querys("INSERT INTO ".$sys_tables['credit_calculator_stats_show_full']."  ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL $i DAY, `type` FROM  ".$sys_tables['credit_calculator_stats_show_day']."  WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY GROUP BY  id_parent, `type`  ");
    $db->querys("INSERT INTO ".$sys_tables['credit_calculator_stats_click_full']." ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL $i DAY, `type` FROM  ".$sys_tables['credit_calculator_stats_click_day']." WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY GROUP BY  id_parent, `type` ");
    $db->querys( " DELETE FROM " . $sys_tables['credit_calculator_stats_show_day'] ." WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY ");
    $db->querys( " DELETE FROM " . $sys_tables['credit_calculator_stats_click_day'] ." WHERE DATE(`datetime`) = CURDATE() - INTERVAL " . $i  . " DAY ");
}

?>