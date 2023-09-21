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
$error_log = ROOT_PATH.'/cron/mailers/spam_error.log';
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
require_once('includes/functions.php');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//3 дня на подтверждение
//читаем пользователей которые оставили подписку и три(4,5) дня не заходили
$days_not_seen = array(15,16,17);
$max_days_not_seen = max($days_not_seen);
$owners_ids = array();
$mail_html = "";
$bsn_url = Host::getWebPath('/');
foreach($days_not_seen as $k=>$days){
    
    $list = $db->fetchall("SELECT ".$sys_tables['users'].".id,
                                  ".$sys_tables['users'].".email,
                                  ".$sys_tables['users'].".login,
                                  TRIM(CONCAT(
                                  CONCAT(UCASE(LEFT(".$sys_tables['users'].".name, 1)), SUBSTRING(".$sys_tables['users'].".name, 2)),' ',
                                  CONCAT(UCASE(LEFT(".$sys_tables['users'].".lastname, 1)), SUBSTRING(".$sys_tables['users'].".lastname, 2))
                                  )) AS full_name,
                                  GROUP_CONCAT(".$sys_tables['objects_subscriptions'].".title SEPARATOR '#') AS subscriptions_titles,
                                  GROUP_CONCAT(CONCAT(?,".$sys_tables['objects_subscriptions'].".url) SEPARATOR '#') AS subscriptions_urls,
                                  SUM(".$sys_tables['objects_subscriptions'].".new_objects) AS new_objects,
                                  COUNT(".$sys_tables['objects_subscriptions'].".id) AS subscribes_amount,
                                  GROUP_CONCAT(".$sys_tables['objects_subscriptions'].".id) AS subscriptions_ids
                           FROM ".$sys_tables['objects_subscriptions']." 
                           LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['objects_subscriptions'].".id_user = ".$sys_tables['users'].".id
                           WHERE DATEDIFF(".$sys_tables['objects_subscriptions'].".last_delivery,".$sys_tables['objects_subscriptions'].".last_seen) = ?
                           GROUP BY ".$sys_tables['users'].".id",false,$bsn_url,$days);
    foreach($list as $k=>$item){
        if(!Validate::isEmail($item['email'])) continue;
        $subscr_urls_list = explode('#',$item['subscriptions_urls']);
        $subscr_urls_list = array_unique($subscr_urls_list);
        $subscr_titles_list = explode('#',$item['subscriptions_titles']);
        Response::SetArray('urls_list',$subscr_urls_list);
        Response::SetArray('titles_list',$subscr_titles_list);
        $item['days_remain'] = $max_days_not_seen - $days;
        $item['days_not_seen'] = $days;
        
        Response::SetArray('item',$item);
        
        if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
        $sender_title = 'BSN.ru';         
        $subject = $item['full_name'].', подтвердите Вашу подписку на BSN.ru';         
        $eml_tpl = new Template('objects_subscribes.confirm.html', 'cron/mailers/');
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
        
        $owners_ids[] = "#".$item['id']." (".count(explode(',',$item['subscriptions_ids'])).")";
    }
    if(isset($item['days_remain'])) $mail_html .= "Предупреждения за ".$item['days_remain']." дней ".count($owners_ids)." пользователям: <br />".implode("<br />",$owners_ids)."<br />";
    unset($list);
    unset($item);
}

//удаляем подписки, в которые не заходили > 17 дней
$list = $db->fetchall("SELECT ".$sys_tables['users'].".id,
                              ".$sys_tables['users'].".email,
                              TRIM(CONCAT(
                              CONCAT(UCASE(LEFT(".$sys_tables['users'].".name, 1)), SUBSTRING(".$sys_tables['users'].".name, 2)),' ',
                              CONCAT(UCASE(LEFT(".$sys_tables['users'].".lastname, 1)), SUBSTRING(".$sys_tables['users'].".lastname, 2))
                              )) AS full_name,
                              GROUP_CONCAT(".$sys_tables['objects_subscriptions'].".title SEPARATOR '#') AS subscriptions_titles,
                              GROUP_CONCAT(".$sys_tables['objects_subscriptions'].".id) AS subscriptions_ids,
                              GROUP_CONCAT(DATEDIFF(".$sys_tables['objects_subscriptions'].".last_delivery,".$sys_tables['objects_subscriptions'].".last_seen) SEPARATOR '#') AS days_not_enter
                       FROM ".$sys_tables['objects_subscriptions']." 
                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['objects_subscriptions'].".id_user = ".$sys_tables['users'].".id
                       WHERE DATEDIFF(".$sys_tables['objects_subscriptions'].".last_delivery,".$sys_tables['objects_subscriptions'].".last_seen) > ?
                       GROUP BY ".$sys_tables['users'].".id",false,$max_days_not_seen);
$ids_to_delete = array();
$owners_ids = array();
foreach($list as $k=>$item){
    if(!Validate::isEmail($item['email'])) continue;
    $item['subscriptions_titles'] = explode('#',$item['subscriptions_titles']);
    //$item['subscriptions_titles'] = array_unique($item['subscriptions_titles']);
    $item['days_not_enter'] = explode('#',$item['days_not_enter']);
    $eml_tpl = new Template('objects_subscribes.delete.html', 'cron/mailers/');
    Response::SetArray('item',$item);
    
    if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
    $sender_title = 'BSN.ru';         
    $subject = $item['full_name'].', ваша подписка на BSN.ru удалена';         
    $eml_tpl = new Template('objects_subscribes.delete.html', 'cron/mailers/');
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
    
    $ids_to_delete[] = $item['subscriptions_ids'];
    $owners_ids[] = "#".$item['id']." (".count(explode(',',$item['subscriptions_ids'])).")";
}
if(!empty($ids_to_delete)){
    $ids_to_delete = implode(',',$ids_to_delete);
    $subscr_to_delete = count(explode(',',$ids_to_delete));
    //удаляем подписки
    $db->querys("DELETE FROM ".$sys_tables['objects_subscriptions']." WHERE id IN (".$ids_to_delete.")");
}
$mailer = new EMailer('mail');
$mail_html .= $subscr_to_delete." подписок удалено у ".count($owners_ids)." пользователей:<br /> ".implode("<br />",$owners_ids);
$mail_html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $mail_html);

if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
$sender_title = 'BSN.ru';         
$subject = 'Очистка старых подписок';
$html = $mail_html;
$emails = array(
    array(
        'name' => '',
        'email'=> 'hitty@bsn.ru'
    )
);
//отправка письма
$sendpulse = new Sendpulse( );
$result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );

?>