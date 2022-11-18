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
require_once('includes/class.email.php');
require_once('includes/class.estate.php');
require_once('includes/class.estate.statistics.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");
$GLOBALS['db']=$db;

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//логи для почты
$log = array();

//список сообщений
$list = $db->fetchall("SELECT  
                            ".$sys_tables['messages'].".*,
                            to_user.email,
                            TRIM(CONCAT(TRIM(to_user.name),   ' ', TRIM(to_user.lastname))) as to_user_name,
                            TRIM(CONCAT(TRIM(from_user.name), ' ', TRIM(from_user.lastname))) as from_user_name,
                            IF(".$sys_tables['messages'].".id_parent>0,".$sys_tables['messages'].".id_parent, ".$sys_tables['messages'].".id) as first_message_id
                        FROM  ".$sys_tables['messages']."
                        LEFT JOIN ".$sys_tables['users']." to_user ON to_user.id = ".$sys_tables['messages'].".id_user_to 
                        LEFT JOIN ".$sys_tables['users']." from_user ON from_user.id = ".$sys_tables['messages'].".id_user_from
                        WHERE ".$sys_tables['messages'].".is_unread = 1 
                            AND ".$sys_tables['messages'].".datetime_create + INTERVAL 5 MINUTE < NOW()
                            AND ".$sys_tables['messages'].".email_notification = 2
                            AND to_user.message_notification = 1
");
foreach($list as $k=>$item){
    if(!empty($item['email']) && Validate::isEmail($item['email'])){
        $mailer = new EMailer('mail');
        Response::SetArray('item',$item);
        // формирование html-кода письма по шаблону
        $eml_tpl = new Template('/cron/mailers/templates/messages_notification.html');
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet, $html);
        // параметры письма
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Вам поступило новое сообщение на BSN.ru');
        $mailer->Body = $html;
        $mailer->AltBody = strip_tags($html);
        $mailer->IsHTML(true);
        $mailer->AddAddress($item['email'], iconv('UTF-8',$mailer->CharSet, ""));
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = 'bsn.ru';                                                                
        if ($mailer->Send()) $db->querys("UPDATE ".$sys_tables['messages']." SET ".$sys_tables['messages'].".email_notification = 1 WHERE id = ?",$item['id']);
    }  else $db->querys("UPDATE ".$sys_tables['messages']." SET ".$sys_tables['messages'].".email_notification = 1 WHERE id = ?",$item['id']);

    
}