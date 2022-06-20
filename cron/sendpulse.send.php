#!/usr/bin/php
<?php
error_reporting(E_ALL);
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
//запись всех ошибок в лог
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.content.php');        // для отправки писем
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;        // для отправки писем
require_once('includes/class.sendpulse.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


$content_types = array( 'news', 'articles', 'bsntv', 'doverie', 'longread' );
foreach( $content_types as $content_type) {
    $content = new Content( $content_type );
    $item = $content->getList( 1, 0, false, false, $sys_tables[$content_type] . '.datetime <= NOW() AND ' . $sys_tables[$content_type] . '.published = 1 AND ' . $sys_tables[$content_type] . '.push_status = 1 ' );
    
    if( !empty( $item ) ) {
        $item = $item[0];
        $sendpulse = new Sendpulse( );
        $result = $sendpulse->createPush(
            $item['title'],
            $item['content_short'],
            'https://www.bsn.ru/' . $content_type . '/' . ( !empty( $item['category_code'] ) ? $item['category_code'] . '/' : '' ) . ( !empty( $item['region_code'] ) ? $item['region_code'] . '/' : '' ) . ( !empty( $item['chpu_title'] ) ? $item['chpu_title'] . '/' : '' ),
            !empty( $item['photo'] ) ? 'https://st1.bsn.ru/img/uploads/med/' . $item['subfolder'] . '/' . $item['photo'] : ''
        );
        var_dump( $result );
        $db->query( " UPDATE " . $sys_tables[$content_type] . " SET push_status = 2 WHERE id = ? ", $item['id'] );
        die();
    }
}    
?>
