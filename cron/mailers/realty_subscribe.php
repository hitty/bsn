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
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");
$GLOBALS['db']=$db;

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//логи для почты
$log = array();

//последняя выгрузка
$date = $db->fetch("SELECT lasttime_update FROM ".$sys_tables['users']." WHERE id = 17397");
$estate = new EstateListLive();
$list = $estate->Search($sys_tables['live'].".date_change >='".$date['lasttime_update']."' AND ".$sys_tables['live'].".id_type_object = 1 AND rent = 2 AND ".$sys_tables['live'].".cost < 4000000 AND ".$sys_tables['live'].".id_district > 0  AND ".$sys_tables['live'].".id_district < 17 AND ".$sys_tables['live'].".id_region = 78  AND ".$sys_tables['live'].".square_full > 45  AND ".$sys_tables['live'].".square_kitchen >=6 ",30,0, 'date_change DESC, cost ');

Response::SetArray('list',$list) ;
$eml_tpl = new Template('spam.estate.objects.html', 'cron/mailers/');
$html = $eml_tpl->Processing();

$mailer = new EMailer('mail');
$html = iconv('UTF-8', $mailer->CharSet, $html);
$mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Подписка на объекты от частников');
$mailer->Body = $html;
$mailer->AltBody = strip_tags($html);
$mailer->IsHTML(true);
$mailer->AddAddress('kya82@mail.ru');
$mailer->AddAddress('margo7787@bk.ru');
$mailer->From = 'no-reply@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'BSN.ru');
$mailer->Send();  

$db->query("UPDATE  ".$sys_tables['users']." SET lasttime_update = NOW() WHERE id = 17397");
