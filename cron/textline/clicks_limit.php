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
$error_log = ROOT_PATH.'/cron/textline/error.log';
$test_performance = ROOT_PATH.'/cron/textline/test_performance.log';
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
                , c.enabled
                , c.name
                , c.email
                , c.agency_photo_folder
                , c.agency_photo
                , c.clicks_limit
                , c.date_end
                , c.date_start
                , c.clicks_limit_notification
                , SUM(a.full_click_amount) as full_click_amount
                , a.date
                , b.day_clicks_limit as day_clicks_limit
                , SUM(a.full_click_amount) + b.day_clicks_limit as total_clicks
               FROM (                
               ( SELECT 
                      ".$sys_tables['textline_campaigns'].".id,
                      ".$sys_tables['textline_campaigns'].".enabled,
                      ".$sys_tables['textline_campaigns'].".date_end,
                      ".$sys_tables['textline_campaigns'].".date_start,
                      ".$sys_tables['textline_campaigns'].".clicks_limit,
                      ".$sys_tables['managers'].".name,
                      ".$sys_tables['managers'].".email,
                      ".$sys_tables['textline_campaigns'].".clicks_limit_notification,
                      LEFT(".$sys_tables['agencies_photos'].".name,2) as agency_photo_folder,
                      ".$sys_tables['agencies_photos'].".name as agency_photo
                FROM  ".$sys_tables['textline_campaigns']."
                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['textline_campaigns'].".id_manager
                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['textline_campaigns'].".id_user
                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies'].".id_main_photo = ".$sys_tables['agencies_photos'].".id
                WHERE clicks_limit > 0 AND enabled = 1 AND date_end > CURDATE() AND date_start <= CURDATE()
                GROUP BY ".$sys_tables['textline_campaigns'].".id
               ) c
                LEFT JOIN 
                (
                  SELECT 
                      SUM(IFNULL(`amount`,0)) as full_click_amount
                      , id_parent
                      , date
                  FROM ".$sys_tables['textline_stats_full_clicks']." 
                  GROUP BY id_parent, date              
                ) a ON a.id_parent = c.id   AND a.date >= c.date_start                   
                LEFT JOIN 
                (
                  SELECT 
                      COUNT(*) as day_clicks_limit
                      , id_parent
                  FROM ".$sys_tables['textline_stats_day_clicks']."
                  GROUP BY id_parent
                 ) b ON b.id_parent = c.id
                )
                GROUP BY c.id
        ");
foreach($list as $k => $item){
    Response::SetArray('item', $item);
    //если есть прикрепленные, читаем по ним
    //перемещение в архив
    $total_clicks = $item['full_click_amount'] + $item['day_clicks_limit'];
    Response::SetInteger('total_clicks', $total_clicks);
    if($item['clicks_limit'] <= $total_clicks){
        if(!empty($item['email']))        {
            $manager_mailer = new EMailer('mail');
            $eml_tpl = new Template('limit.warning.html', 'cron/textline/');
            // перевод письма в кодировку мейлера   
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $manager_mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $manager_mailer->Body = $html;
            $manager_mailer->IsHTML(true);

            $manager_mailer->Subject = iconv('UTF-8', $manager_mailer->CharSet, "TextLine ID ".$item['id']." убран в архив.");

            $manager_mailer->AddAddress($item['email']);     //отправка письма ответственному менеджеру
            $manager_mailer->From = 'no-reply@bsn.ru';
            $manager_mailer->FromName = 'BSN.ru';
            // попытка отправить
            $manager_mailer->Send();             
        }
        $db->querys("UPDATE ".$sys_tables['textline_campaigns']." SET enabled = 2 date_end = '0000-00-00' , date_start = '0000-00-00' , clicks_limit = 0 , clicks_limit_notification = 1 WHERE id = ? ", $item['id'] );    
    } else if($item['clicks_limit_notification'] == 1 && ( $item['clicks_limit'] - 10 <= $total_clicks)){ // предупреждение об окончании кликов
        $db->querys("UPDATE ".$sys_tables['textline_campaigns']." SET clicks_limit_notification = 2 WHERE id = ? ", $item['id'] );    
        $clicks_left = $item['clicks_limit'] - ($total_clicks);
        Response::SetInteger( 'clicks_left' , $clicks_left );
        if(!empty($item['email']))        {
            $manager_mailer = new EMailer('mail');
            $eml_tpl = new Template('limit.warning.html', 'cron/textline/');
            // перевод письма в кодировку мейлера   
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $manager_mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $manager_mailer->Body = $html;
            $manager_mailer->IsHTML(true);

            $manager_mailer->Subject = iconv('UTF-8', $manager_mailer->CharSet, "До завершения показов TextLine ID ".$item['id']." осталось ".$clicks_left.' '.makeSuffix( $clicks_left , 'клик' , array('','а','ов') ).".");

            $manager_mailer->AddAddress($item['email']);     //отправка письма ответственному менеджеру
            $manager_mailer->From = 'no-reply@bsn.ru';
            $manager_mailer->FromName = 'BSN.ru';
            // попытка отправить
            $manager_mailer->Send();             
        }
        
    }
}