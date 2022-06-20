#!/usr/bin/php
<?php
define('DEBUG', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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

////////////////////////////////////////////////////////////////////////////////////////////////
// Переносим статистику роботов из суточной в общую
////////////////////////////////////////////////////////////////////////////////////////////////

$crawlers = array('google');
foreach($crawlers as $key=>$item){
    $res = $res && $db->query("INSERT INTO ".$sys_tables['pages_visits_'.$item.'_full']." (`date`,visits_amount,links_shown,old_pages_visits,pages_added) VALUES
                               (CURDATE() - INTERVAL 1 DAY,
                               (SELECT COUNT(*) AS visits_amount FROM  ".$sys_tables['pages_visits_'.$item.'_day']."),
                               (SELECT SUM(shown_today) AS links_shown FROM ".$sys_tables['pages_not_indexed_'.$item]."),
                               (SELECT COUNT(*) AS old_pages_visits 
                                FROM ".$sys_tables['pages_visits_'.$item.'_day']."
                                LEFT JOIN ".$sys_tables['pages_not_indexed_'.$item]." ON ".$sys_tables['pages_visits_'.$item.'_day'].".id_page_in_stack = ".$sys_tables['pages_not_indexed_'.$item].".id
                                WHERE DATEDIFF(NOW(),date_out) >= 1),
                               (SELECT COUNT(*) AS pages_added FROM ".$sys_tables['pages_not_indexed_'.$item]." WHERE DATEDIFF(NOW(),date_out) = 0))");
    $res = $res && $db->query("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET shown_today = 0");
    $res = $res && $db->query("TRUNCATE ".$sys_tables['pages_visits_'.$item.'_day']);
}
//$res = $res && $db->query("INSERT INTO ".);
$log['apps_archive'] = "Статистика поисковых роботов: ".((!$res)?$db->error:"OK")."<br />";


$res = true;

$log = implode('<br />',$log);

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
$mailer->Send();

?>