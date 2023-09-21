#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/test.bsn.ru/public_html/trunk/' );

if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/files.properties.fix.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

include('cron/robot/robot_functions.php');    // функции  из крона
if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

$files_to_update = array('/cron/robot/xml_parser.php',
                         '/cron/robot/xml_values_processing.php',
                         '/cron/mailers/sent_news_telegram.php');
$res = array();
foreach($files_to_update as $key=>$file_path)
    $res[] = ROOT_PATH.$file_path." to 755 and dos2unix results: ".(exec("chmod 755 ".ROOT_PATH.$file_path) !== null ? true : false).", ".(exec("dos2unix ".ROOT_PATH.$file_path) !== null ? true : false);
$res = implode('<br /><br />',$res);

$minutes = date('i') % 30;
//if(empty($minutes)){
if(true){
    require_once('includes/class.config.php');
    Config::Init();
    require_once('includes/class.convert.php');
    require_once('includes/class.storage.php');
    require_once('includes/functions.php');
    //Session::Init();
    Session::Init(null,null,'public',true);
    Request::Init();
    require_once('includes/class.host.php');
    require_once('includes/class.email.php');
    require_once('includes/class.email.php');
    $mailer = new EMailer();
    $mailer->sendEmail('hitty@bsn.ru',"Миша","Исполняемые файлы test.bsn",false,false,false,"Результат исполнения: <br />".$res);
    $mailer->sendEmail('hitty@bsn.ru',"Юра","Исполняемые файлы test.bsn",false,false,false,"Результат исполнения: <br />".$res);
}

?>