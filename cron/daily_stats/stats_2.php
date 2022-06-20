#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);
echo $root;

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

/*
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.context_campaigns.php');
require_once('includes/class.template.php');
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


//log для письма
$log = array();
$res = true;


$mailer = new EMailer('mail');
$mail_text = iconv('UTF-8', $mailer->CharSet, "Ежедневная статистика на bsn.ru:<br />".$log);
if(!empty($data['subject'])) $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Ежедневная статистика bsn.ru");
$mailer->Body = $mail_text;
$mailer->AltBody = strip_tags($mail_text);
$mailer->IsHTML(true);
$mailer->AddAddress('web@bsn.ru');
$mailer->From = 'no-reply@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
// попытка отправить
//$mailer->Send();

?>