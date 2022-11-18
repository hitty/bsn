#!/usr/bin/php
<?php
die();
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

//записываем пользователей, у которых есть истекшие заявки и у которых включено оповещение
$db->querys("SET lc_time_names = 'ru_RU'");
$apps_list = $db->fetchall(" SELECT  id_user,
                                    ".$sys_tables['users'].".name,
                                    ".$sys_tables['users'].".email,
                                    ".$sys_tables['applications'].".id AS app_id,
                                    ".$sys_tables['applications'].".estate_type,
                                    ".$sys_tables['applications'].".id_parent,
                                    DATE_FORMAT(".$sys_tables['applications'].".`datetime`,'%e %M, %k:%i') AS app_date,
                                    ".$sys_tables['application_types'].".lifetime
                             FROM ".$sys_tables['applications']."
                             LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                             LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['applications'].".id_user = ".$sys_tables['users'].".id
                             WHERE ".$sys_tables['applications'].".status=2 AND 
                                   visible_to_all = 2 AND ".$sys_tables['users'].".application_notification = 1",'app_id');

//делаем видимыми все заявки, не взятые в работу по истечении lifetime минут

$res = $db->querys("UPDATE ".$sys_tables['applications']."
                   LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                   SET visible_to_all = 1
                   WHERE ".$sys_tables['applications'].".status=2 AND 
                         visible_to_all = 2 AND
                         NOW()>DATE_ADD(".$sys_tables['applications'].".`datetime`, INTERVAL ".$sys_tables['application_types'].".lifetime MINUTE)");

//пользователи, которые теряют заявки
$users_list = array();
//список id пользователей, чьи заявки уходят в общий пул
$users_lose_apps = array();
if(!empty($apps_list)){
    foreach($apps_list as $key=>$item){
        switch($item['estate_type']){
            case '1':
                $estateItem = new EstateItemLive($item['id_parent']);
                break;
            case '2':
                $estateItem = new EstateItemBuild($item['id_parent']);
                break;
            case '3':
                $estateItem = new EstateItemCommercial($item['id_parent']);
                break;
            case '4':
                $estateItem = new EstateItemCountry($item['id_parent']);
                break;
            case '5':
                $estateItem = new HousingEstates($item['id_parent']);
                break;
            case '6':
                $estateItem = new Cottages($item['id_parent']);
                break;        
            case '7':
                $estateItem = new BusinessCenters($item['id_parent']);
                break;
            default:
                $estateItem = null;
                break;
        }
        $apps_list[$key]['object_title'] = (empty($estateItem)?"":$estateItem->getTitles($item['id_parent'])['header']);
        $item['object_title'] = $apps_list[$key]['object_title'];
        
        if(!empty($item['email']) && Validate::isEmail($item['email'])) $users_list[$item['id_user']][$item['app_id']] = $item;
        if(!in_array($item['id_user'],$users_lose_apps)) $users_lose_apps[] = $item['id_user'];  
    }
    $users_lose_apps = implode(',',$users_lose_apps);
    //читаем всех пользователей, отмечаем, теряют ли они в данный момент свои заявки
    $users_getting_apps = $db->fetchall("SELECT id,email,
                                                (foreign_application_notification = 1 AND (agency_admin = 1 OR id_tarif > 0)) AS foreign_application_notification,
                                                IF(id NOT IN(".$users_lose_apps."),true,false) AS only_grab
                                         FROM ".$sys_tables['users']."
                                         WHERE (id_agency>0 OR id_tarif>0) AND application_notification = 1");
    $users_grab_apps = array();
    //оповещаем пользователей о появившихся в пуле заявках
    foreach($users_getting_apps as $key=>$item){
        //записываем пользователей которые только подбирают заявки - для них письмо будет одинаковое
        if($item['only_grab'] && ($item['foreign_application_notification'] == 1))
            $users_grab_apps[$item['id']] = $item['email'];
        else{
            //исключаем заявки этого пользователя
            //$user_foreign_apps = array_diff_assoc($apps_list,$users_list[$item['id']]);
            //вместо diff_assoc, которое видимо не работает
            $user_foreign_apps = $apps_list;
            foreach($users_list[$item['id']] as $k=>$i){
                if(!empty($user_foreign_apps[$k])) unset($user_foreign_apps[$k]);
            }
            
            Response::SetString('letter_starting','Уважаемый партнер!');
            Response::SetString('letter_ending','С уважением,');
            Response::SetArray('apps_list',$user_foreign_apps);
            
            if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
            $sender_title = 'Заявки на bsn.ru';         
            $subject = 'В общем пуле появились новые заявки';         
            $eml_tpl = new Template('/cron/application_scripts/templates/applications_open_email.html');
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
    
    //отправляем письмо Всем пользователям, которые не теряют заявок и могут их подобрать - для всех них письмо одинаковое
    Response::SetString('letter_starting','Уважаемый партнер!');
    Response::SetString('letter_ending','С уважением,');
    
    Response::SetArray('apps_list',$apps_list);
    
    if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
    $sender_title = 'Заявки на bsn.ru';         
    $subject = 'В общем пуле появились новые заявки';         
    $eml_tpl = new Template('/cron/application_scripts/templates/applications_open_email.html');
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
    
    //оповещаем пользователей, у которых заявки ушли в общий пул
    foreach($users_list as $key=>$item){
        
        Response::SetString('letter_starting','Уважаемый партнер!');
        Response::SetString('letter_ending','С уважением,');
        
        Response::SetArray('apps_list',$item);
        
        $eml_tpl = new Template('/cron/application_scripts/templates/applications_lose_email.html');
        $html = $eml_tpl->Processing();
        $mailer = new EMailer('mail');
        $mail_text = iconv('UTF-8', $mailer->CharSet, $html);
        if(!empty($data['subject'])) $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Заявки на bsn.ru");
        $mailer->Body = $mail_text;
        $mailer->AltBody = strip_tags($mail_text);
        $mailer->IsHTML(true);        
        $mailer->AddAddress(array_values($item)[0]['email']);
		$mailer->AddAddress('web@bsn.ru');
        //оповещаем системным сообщением о новой заявке
        $messages->Send(0,$key,"Вам поступила новая заявка",0,1,"");
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
        // попытка отправить
        $mailer->Send();
        unset($mailer);
        
        
        if( !class_exists( 'Sendpulse' ) ) require_once( "includes/class.sendpulse.php" );
        $sender_title = 'Заявки на bsn.ru';         
        $subject = 'Вам поступила новая заявка';         
        $eml_tpl = new Template( '/cron/application_scripts/templates/applications_open_email.html' );
        $html = $eml_tpl->Processing();
        $emails = array(
            array(
                'name' => '',
                'email'=> 'web@bsn.ru'
            )
        );
        $emails[] = array( 'name' => '', 'email'=> array_values( $item )[0]['email'] );
        //отправка письма
        $sendpulse = new Sendpulse( );
        $result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );
        
    }
}

//перенос заявок из ожидающих в опубликованные
require_once('includes/class.applications.php');
//достаем с учетом только рабочих дней, без учета времени работы
AppsFunctions::publishWaiting();
//обновляем таблицу компаний с ценами с SALE
AppsFunctions::refreshSaleAgencies();
?>