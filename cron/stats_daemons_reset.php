#!/usr/bin/php
<?php
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

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/cottages/error.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');
/**
* Обработка новых объектов
*/
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/functions.php');          // функции  из модуля
require_once('includes/class.daemons.manager.php');          // функции  из модуля
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.email.php');



$actions_manager = new DaemonsManager();
$res = $actions_manager->resetDaemons();

$manager_mailer = new EMailer('mail');
$mail_text = "Статус демонов ".(!empty($res['result']) ? "успешно" : "не")." обновлен, строк затронуто: ".(!empty($res['affected_rows']) ? $res['affected_rows'] : 0)."\r\n";
if(!empty($res['errors'])) $mail_text .= "Ошибки: ".$res['errors'];

$full_log = ob_clean();
if(!empty($full_log)) $mail_text .= "<br />Лог: ".$full_log;

$html = iconv('UTF-8', $manager_mailer->CharSet, $mail_text);
// параметры письма
$manager_mailer->Subject = iconv('UTF-8', $manager_mailer->CharSet, 'Обновление статуса демонов на bsn.ru');
$manager_mailer->Body = nl2br($html);
$manager_mailer->AltBody = nl2br($html);
$manager_mailer->IsHTML(true);
$manager_mailer->AddAddress("kya1982@gmail.com");
$manager_mailer->From = 'xml_parser@bsn.ru';
$manager_mailer->FromName = iconv('UTF-8', $manager_mailer->CharSet,' DM BSN.ru');
// попытка отправить
$res = $res && $manager_mailer->Send();
if($res) echo "DM: daemons refreshed, mail successfully send\r\n";
?>