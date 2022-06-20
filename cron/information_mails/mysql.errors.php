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

/**
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.template.php');
require_once('includes/class.email.php');


$cron_values = array(false,true);

foreach($cron_values as $key=>$cron){
    $list = $db->loadErrorsData(ROOT_PATH,$cron);
    if(!empty($list)){
        Response::SetArray('list', $list);
        Response::SetBoolean('cron', $cron);
        $eml_tpl = new Template('mysql.errors.html', 'cron/information_mails/');
        $mailer = new EMailer('mail');
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        // параметры письма
        $mailer->Body = $html;
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Ошибки '.(!empty($cron)?"CRON":"").' MySQL на BSN.ru');
        $mailer->IsHTML(true);
        //$mailer->AddAddress(Config::Get('emails/web2'));
        //$mailer->AddAddress(Config::Get('emails/web'));
        //$mailer->AddAddress('kya82@mail.ru');
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
        // попытка отправить
        //$mailer->Send();
        //$db->clearErrorsData(ROOT_PATH);
    }
}

//изменения в views_count_last_week вносятся в daily_stats.php
/*
$db->query("UPDATE ".$sys_tables['live']." SET `views_count_week` = `views_count` WHERE 1");
$db->query("UPDATE ".$sys_tables['build']." SET `views_count_week` = `views_count` WHERE 1");
$db->query("UPDATE ".$sys_tables['commercial']." SET `views_count_week` = `views_count` WHERE 1");
$db->query("UPDATE ".$sys_tables['country']." SET `views_count_week` = `views_count` WHERE 1");
*/
?>
