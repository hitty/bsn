#!/usr/bin/php
<?php
error_reporting(0);
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
require_once('includes/class.check.email.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


$email_list = $db->fetchall("SELECT DISTINCT s.email, s.id, s.type FROM ( 
                                (SELECT email, id, 'subscribed_users' as type FROM ".$sys_tables['subscribed_users']." WHERE published=1 AND email != '' AND checked_email = 2 AND email NOT LIKE '%@rambler.ru' AND email NOT LIKE '%@ro.ru') 
                                UNION 
                                (SELECT email, id, 'users' as type FROM ".$sys_tables['users']." WHERE subscribe_news = 1 AND email != '' AND checked_email = 2 AND email NOT LIKE '%@rambler.ru' AND email NOT LIKE '%@ro.ru') 
                            ) as s GROUP BY s.email");   
foreach($email_list as $email){
    if(!Validate::isEmail($email['email'])){
        preg_match('!([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,4}!i', (string) $email['email'], $matches);
        if(!empty($matches[0])) $email['email'] = $matches[0];
        else $email['email'] = null;
    }
    if( !empty( $email['email'] ) ) {
        //проверка на существование email
        $alter = new CCheckMail();
        if( !$alter->execute( $email['email'] ) ){
            if( $email['type'] == 'users' ) $db->query( " UPDATE " . $sys_tables[$email['type']] . " SET active_email = 2, checked_email = 1 WHERE id = ?", $email['id'] );
            else $db->query( " UPDATE " . $sys_tables[$email['type']] . " SET published = 9, checked_email = 1 WHERE id = ?", $email['id'] );
        }  else {
            
            $db->query( " UPDATE " . $sys_tables[$email['type']] . " SET active_email = 2, checked_email = 1 WHERE id = ?", $email['id'] );
        }
    } else {
        
        if( $email['type'] == 'users' ) $db->query( " UPDATE " . $sys_tables[$email['type']] . " SET active_email = 2, checked_email = 1 WHERE id = ?", $email['id'] );
        else $db->query( " UPDATE " . $sys_tables[$email['type']] . " SET published = 9, checked_email = 1 WHERE id = ?", $email['id'] );
    }                                
}
                            
?>
