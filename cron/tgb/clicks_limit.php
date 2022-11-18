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
require_once('includes/functions.php'); 

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$list =  $db->fetchall(
            "
            SELECT 
                c.id
                , c.name
                , c.email
                , c.clicks_limit
                , c.published
                , c.date_end
                , c.date_start
                , c.img_link
                , c.img_src
                , c.clicks_limit_notification
                , c.id_context
                , c.context_date_start
                , SUM(a.full_click_amount) as full_click_amount
                , a.date
                , b.day_clicks_limit as day_clicks_limit
                , SUM(a.full_click_amount) + b.day_clicks_limit as total_clicks
               FROM (                
               ( SELECT 
                      ".$sys_tables['tgb_banners'].".id,
                      ".$sys_tables['tgb_banners'].".published,
                      ".$sys_tables['tgb_banners'].".date_end,
                      ".$sys_tables['tgb_banners'].".date_start,
                      ".$sys_tables['tgb_banners'].".clicks_limit,
                      ".$sys_tables['managers'].".name,
                      ".$sys_tables['managers'].".email,
                      ".$sys_tables['tgb_banners'].".img_link,
                      ".$sys_tables['tgb_banners'].".clicks_limit_notification,
                      ".$sys_tables['tgb_banners'].".img_src,
                      ".$sys_tables['tgb_banners'].".id_context,
                      ".$sys_tables['tgb_banners'].".context_date_start
                FROM  ".$sys_tables['tgb_banners']."
                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['tgb_banners'].".id_manager
                WHERE clicks_limit > 0 AND published = 1 AND date_end > CURDATE() AND date_start <= CURDATE()
                GROUP BY ".$sys_tables['tgb_banners'].".id
               ) c
                LEFT JOIN 
                (
                  SELECT 
                      SUM(IFNULL(`amount`,0)) as full_click_amount
                      , id_parent
                      , date
                  FROM ".$sys_tables['tgb_stats_full_clicks']." 
                  GROUP BY id_parent, date              
                ) a ON a.id_parent = c.id   AND a.date >= c.date_start                   
                LEFT JOIN 
                (
                  SELECT 
                      COUNT(*) as day_clicks_limit
                      , id_parent
                  FROM ".$sys_tables['tgb_stats_day_clicks']."
                  GROUP BY id_parent
                 ) b ON b.id_parent = c.id
                )
                GROUP BY c.id
        ");
         $lq = $db->last_query;
foreach($list as $k => $item){
    Response::SetArray('item', $item);
    //если есть прикрепленные, читаем по ним
    
    $joined_full_amount = 0;
    $joined_day_amount = 0;
    
    //если есть context, читаем по ним
    if(!empty($item['id_context'])){
        $context_full_amount= $db->fetch("SELECT SUM(".$sys_tables['context_stats_click_full'].".amount) AS context_full_amount 
                                          FROM ".$sys_tables['context_stats_click_full']." 
                                          WHERE id_parent IN (".$item['id_context'].") AND date >= '".($item['context_date_start'] > $item['date_start'] ? $item['context_date_start'] : $item['date_start'])."'");
        $context_full_amount = (empty($context_full_amount)?0:$context_full_amount['context_full_amount']);
        $context_day_amount = $db->fetch("SELECT COUNT(".$sys_tables['context_stats_click_day'].".id) AS context_day_amount 
                                         FROM ".$sys_tables['context_stats_click_day']."
                                         WHERE id_parent IN (".$item['id_context'].")")['context_day_amount'];
        $context_day_amount = (empty($context_day_amount)?0:$context_day_amount);
    }
    else{
        $context_full_amount = 0;
        $context_day_amount = 0;
    }    
    //перемещение в архив
    $total_clicks = $item['full_click_amount'] + $item['day_clicks_limit'] + $context_full_amount + $context_day_amount;
    Response::SetInteger('total_clicks', $total_clicks);
    if($item['clicks_limit'] <= $total_clicks){
        if(!empty($item['email']))        {

            if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
            $sender_title = 'BSN.ru';         
            $subject = "ТГБ ID ".$item['id']." убран в архив.";         
            $eml_tpl = new Template( 'limit.warning.html', 'cron/tgb/' );
            $html = $eml_tpl->Processing();
            $emails = array(
                array(
                    'name' => '',
                    'email'=> $item['email']
                )
            );
            //отправка письма
            $sendpulse = new Sendpulse( );
            $result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );
            
            Response::SetString('lq', $lq);
            Response::SetString('context_full_amount', $context_full_amount);
            Response::SetString('context_day_amount', $context_day_amount);
            
            if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
            $sender_title = 'BSN.ru';         
            $subject = "ТГБ ID ".$item['id']." убран в архив.";         
            $eml_tpl = new Template( 'limit.warning.admin.html', 'cron/tgb/' );
            $html = $eml_tpl->Processing();
            $emails = array(
                array(
                    'name' => '',
                    'email'=> Config::Get('emails/manager')
                )
            );
            //отправка письма
            $sendpulse = new Sendpulse( );
            $result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );
        }
        $db->querys("UPDATE ".$sys_tables['tgb_banners']." SET published = 2 , enabled = 2, date_end = '0000-00-00' , date_start = '0000-00-00' , clicks_limit = 0 , clicks_limit_notification = 1 WHERE id = ? ", $item['id'] );
    } else if($item['clicks_limit_notification'] == 1 && ( $item['clicks_limit'] - 10 <= $total_clicks)){ // предупреждение об окончании кликов

        $db->querys("UPDATE ".$sys_tables['tgb_banners']." SET clicks_limit_notification = 2 WHERE id = ? ", $item['id'] );
        $clicks_left = $item['clicks_limit'] - ($total_clicks);
        Response::SetInteger( 'clicks_left' , $clicks_left );
        if(!empty($item['email']))        {
            
            if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
            $sender_title = 'BSN.ru';         
            $subject = "До завершения показов ТГБ ID ".$item['id']." осталось ".$clicks_left.' '.makeSuffix( $clicks_left , 'клик' , array('','а','ов') ).".";         
            $eml_tpl = new Template( 'limit.warning.html', 'cron/tgb/' );
            $html = $eml_tpl->Processing();
            $emails = array(
                array(
                    'name' => '',
                    'email'=> $item['email']
                )
            );
            //отправка письма
            $sendpulse = new Sendpulse( );
            $result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );
                
        }
        
    }
}