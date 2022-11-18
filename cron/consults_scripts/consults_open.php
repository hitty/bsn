#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);

include('cron/robot/robot_functions.php');    // функции  из крона

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
include('includes/class.host.php');         //
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/class.estate.php');       // Для создания заголовков объектов недвижимости:
include('includes/class.housing_estates.php');       // 
include('includes/class.cottages.php');              // 
include('includes/class.business_centers.php');      // 
require_once('includes/class.messages.php');     // Для отправки сообщений
require_once('includes/class.context_campaigns.php');
require_once('includes/class.template.php');
include('includes/functions.php');          // функции  из модуля
Session::Init(null,null,'public',true);
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


//log для письма
$log = array();
$res = true;

//для системных сообщений
$messages = new Messages();

//записываем пользователей, у которых есть истекшие вопросы и у которых включено оповещение
$db->querys("SET lc_time_names = 'ru_RU'");
$releasing_questions = $db->fetchall(" SELECT  ".$sys_tables['consults'].".id_respondent_user,
                                               ".$sys_tables['users'].".name,
                                               ".$sys_tables['users'].".email,
                                               ".$sys_tables['consults'].".id AS q_id,
                                               ".$sys_tables['consults'].".question,
                                               DATE_FORMAT(".$sys_tables['consults'].".`moderation_datetime`,'%e %M, %k:%i') AS q_date,
                                               ".$sys_tables['consults_categories'].".title AS category_title,
                                               ".$sys_tables['consults_categories'].".lifetime
                                       FROM ".$sys_tables['consults']."
                                       LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['consults'].".id_respondent_user = ".$sys_tables['users'].".id
                                       WHERE id_respondent_user > 0 AND 
                                             ".$sys_tables['consults'].".status = 1 AND visible_to_all = 2 AND 
                                             ".$sys_tables['users'].".consults_notification = 1 AND 
                                             NOW() > DATE_ADD(".$sys_tables['consults'].".`moderation_datetime`, INTERVAL ".$sys_tables['consults_categories'].".lifetime MINUTE)",'q_id');

//делаем видимыми все вопросы, не взятые в работу по истечении lifetime минут
$res = $db->querys("UPDATE ".$sys_tables['consults']."
                   LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                   SET visible_to_all = 1
                   WHERE status = 1 AND 
                         visible_to_all = 2 AND
                         NOW() > DATE_ADD(".$sys_tables['consults'].".`moderation_datetime`, INTERVAL ".$sys_tables['consults_categories'].".lifetime MINUTE)");


//пользователи, которые теряют вопросы
$users_list = array();
//список id пользователей, чьи вопросы уходят в общий пул
$users_lose_apps = array();

//если есть вопросы, уходящие в общий пул, записываем пользователей, чьи они

if(!empty($releasing_questions)){
    foreach($releasing_questions as $key=>$item){
        $releasing_questions[$key]['question'] = mb_substr($releasing_questions[$key]['question'],0,25)."...";
        if(!empty($item['email']) && Validate::isEmail($item['email'])) $users_list[$item['id_user']][$item['q_id']] = $item;
        if(!in_array($item['id_respondent_user'],$users_lose_apps)) $users_lose_apps[] = $item['id_respondent_user'];  
    }
    $users_lose_apps = implode(',',$users_lose_apps);
    //читаем всех пользователей, которые могут отвечать на вопросы, отмечаем теряют они вопросы или нет
    $users_getting_apps = $db->fetchall("SELECT id,name,lastname,email,
                                            IF(id NOT IN(".$users_lose_apps."),true,false) AS only_grab
                                     FROM ".$sys_tables['users']." 
                                     WHERE (user_activity = 2 AND id_tarif > 0) AND consults_notification = 1");
    $users_grab_apps = array();
    //оповещаем пользователей о появившихся в пуле вопросах
    foreach($users_getting_apps as $key=>$item){
        //записываем пользователей которые не теряют вопросы - для них письмо будет одинаковое
        if(!empty($item['only_grab']))
            $users_grab_apps[$item['id']] = $item['email'];
        else{
            //для пользователя который сейчас теряет вопросы, другое
            $user_foreign_apps = $apps_list;
            foreach($users_list[$item['id']] as $k=>$i){
                if(!empty($user_foreign_apps[$k])) unset($user_foreign_apps[$k]);
            }
            
            Response::SetString('letter_starting','Уважаемый партнер!');
            Response::SetString('letter_ending','С уважением,');
            Response::SetArray('apps_list',$user_foreign_apps);
            
            if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
            $sender_title = 'Заявки сервиса Консультант на bsn.ru';         
            $subject = 'В общем пуле появились новые вопросы';         
            $eml_tpl = new Template('/cron/application_scripts/templates/consults_open_email.html');
            $html = $eml_tpl->Processing();
            $emails = array(
                array(
                    'name' => '',
                    'email'=> 'web@bsn.ru'
                )
            );
            if(!empty( $item['email'] ) ) $emails[] = array( 'name' => '', 'email'=> $item['email'] );
            //отправка письма
            $sendpulse = new Sendpulse( );
            $result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );

        }
    }
    
    //отправляем письмо всем пользователям, которые не теряют вопросов и могут их подобрать - для всех них письмо одинаковое
    Response::SetString('letter_ending','С уважением,');
    Response::SetArray('questions_list',$releasing_questions);
    
    $eml_tpl = new Template('/cron/consults_scripts/templates/consults_open_email.html');
    $html = $eml_tpl->Processing();
    
    if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
    $sender_title = 'Заявки сервиса Консультант на bsn.ru';         
    $subject = 'В общем пуле появились новые вопросы';         
    $eml_tpl = new Template('/cron/application_scripts/templates/consults_open_email.html');
    $html = $eml_tpl->Processing();
    $emails = array(
        array(
            'name' => '',
            'email'=> 'web@bsn.ru'
        )
    );
    //if(!empty( $item['email'] ) ) $emails[] = array( 'name' => '', 'email'=> $item['email'] );
    //отправка письма
    $sendpulse = new Sendpulse( );
    $result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );
    
}

//перенос заявок из ожидающих в опубликованные
require_once('includes/class.consults.php');
ConsultQFunctions::publishWaiting();
?>