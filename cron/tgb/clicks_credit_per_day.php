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
$error_log = ROOT_PATH.'/cron/tgb/error.log';
$test_performance = ROOT_PATH.'/cron/tgb/test_performance.log';
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
require_once('includes/class.tgb.php');
require_once('includes/functions.php'); 

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

$argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$list = Tgb::getList(0, 0, $sys_tables['tgb_banners'].".published = 1 AND ".$sys_tables['tgb_banners'].".enabled = 1 AND ".$sys_tables['tgb_banners'].".clicks_limit > 0 AND ".$sys_tables['tgb_banners'].".credit_clicks = 1");
$managers = array();
foreach($list as $k=>$item){
    $item['new_day_limit'] = Tgb::getClicksPerDay($item['id']);
    $stats = Tgb::getItemStats($item['id']);
    $item = array_merge($item, $stats);
    $db->querys("INSERT INTO ".$sys_tables['tgb_banners_credits']." SET `day_limit` = ?, id_banner= ?
                                   ON DUPLICATE KEY UPDATE `day_limit` = ?
                                    ", $item['new_day_limit'], $item['id'], $item['new_day_limit']);
    $item['agency'] = Tgb::getAgency($sys_tables['tgb_campaigns'].".id = ".$item['id_campaign']);
    $managers[$item['id_manager']][] = $item;
}

foreach($managers as $k=>$list){
    Response::SetArray('manager', $list[0]);
    Response::SetArray('list', $list);
    $mailer_title = ( !empty($argc) ? 'Тестовое письмо. ' : '' ) . 'Текущая информация по сквозным ТГБ на '.date('d.m.Y');
    Response::SetString('mailer_title', $mailer_title);
    
    $eml_tpl = new Template('tgb.credit.clicks.html', 'cron/tgb/');
    // перевод письма в кодировку мейлера   
    $html = $eml_tpl->Processing();
    
    if( !class_exists( 'Sendpulse' ) ) require_once( "includes/class.sendpulse.php" );
    $emails = array(
        array( 'name' => '', 'email'=> !empty($argc) ? 'kya1982@gmail.com' : $list[0]['manager_email'] )
    );
    if( empty( $argc ) ) $emails[] = array( 'name' => '', 'email'=> Config::Get('emails/manager') );
    if( empty( $argc ) ) $emails[] = array( 'name' => '', 'email'=> Config::Get('val@bsn.ru') );
    //отправка письма
    $sendpulse = new Sendpulse( );
    $result = $sendpulse->sendMail( $mailer_title, $html, false, false, $mailer_title, 'no-reply@bsn.ru', $emails );
        
    
}
