#!/usr/bin/php
<?php
error_reporting(E_ALL);
@ini_set('display_errors', 1);
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root =  DEBUG_MODE ? realpath( ".." ) : realpath('/home/bsn/sites/bsn.ru/public_html/' )  ;
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
require_once('includes/class.email.php');        // для отправки писем
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;        // для отправки писем
require_once('includes/class.sendpulse.php');        // для отправки писем
require_once('includes/functions.php');    // функции  из крона

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");
// вспомогательные таблицы модуля

$sys_tables = Config::$sys_tables;
$content = '123123  123 123 12 3123 12 312 312312 123123  123 123 12 3123 12 312 312312 123123  123 123 12 3123 12 312 312312 123123  123 123 12 3123 12 312 312312 ';
Response::SetString( 'content', $content );    
$eml_tpl = new Template('report.html', 'modules/mailers/');
var_dump( $eml_tpl );
$html = $eml_tpl->Processing();
var_dump( $html );
$emails = array(
    array(
        'name' => '',
        'email'=> 'kya@mail.ru'
    )
);
//отправка письма
$sendpulse = new Sendpulse( );
$result = $sendpulse->sendMail( 'Заголовок', $html, 'Парсинг  XML файла', 'no-reply@bsn.ru', $emails );
?>
