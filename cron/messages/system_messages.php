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
$error_log = ROOT_PATH.'/cron/gen_sitemap/error.log';
$test_performance = ROOT_PATH.'/cron/gen_sitemap/test_performance.log';
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
require_once('includes/class.messages.php');     // Template (шаблонизатор), FileCache (файловое кеширование)

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");
$GLOBALS['db']=$db;

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//логи для почты
$log = array();
$messages = new Messages();
//читаем список системных сообщений
$list = $db->fetchall('SELECT * FROM '.$sys_tables['system_messages'].' WHERE published=1');
if(!empty($list)){
    //Выбираем костыльного пользователя по саппорту
    $parent_id = 0;
    $recipient = $db->fetch("SELECT id
                                 FROM ".$sys_tables['users']." 
                                 WHERE id_group = ".system_group_number);
    if(!empty($recipient)){
        $list_users = DEBUG_MODE ? $db->fetchall('SELECT * FROM '.$sys_tables['users']." WHERE id IN (3, 39109)")
                            : $db->fetchall('SELECT * FROM '.$sys_tables['users']." WHERE last_enter > CURDATE() - INTERVAL 4 MONTH AND email!=''");
        if(!empty($list_users)){
            foreach ($list as $k=>$item){
                foreach ($list_users as $k=>$item_user){
                    //определение ветви 
                    $parent_message = $db->fetch("SELECT * FROM ".$sys_tables['messages']." WHERE id_user_from = ? AND id_user_to = ? AND id_parent = 0", $recipient['id'], $item_user['id']);
                    if(empty($parent_message)) $parent_message['id'] = 0;
                    $sent_id = $messages->Send($recipient['id'], $item_user['id'], $item['content'], $parent_message['id'], 1, '', false);
                }
                $db->querys('UPDATE '.$sys_tables['system_messages'].' SET published=2, receipts = ? WHERE id=?', count($list_users), $item['id']);
            }
        }
    }
    
}
