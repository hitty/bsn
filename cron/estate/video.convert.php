#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );

if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');



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
require_once('includes/class.email.php');
include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
include('includes/class.videos.php');     // Photos (работа с графикой)

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$estate_types = array('live', 'build', 'commercial', 'country');
foreach($estate_types as $k=>$estate_type){
    $list = Videos::getList($estate_type, false, false, "videos.`status` = 1");
    foreach($list as $k => $item){
        $convert = Videos::Convert($estate_type, $item['id'], $item);
        if(!empty($convert['file_name'])){
                if(!class_exists('EMailer')) include('includes/class.email.php');
                if(!class_exists('Template')) include('includes/class.template.php');
                
                //отправка менеджерам
                $mailer = new EMailer('mail');           
                // инициализация шаблонизатора
                $eml_tpl = new Template('video.convert.email.html', 'cron/estate/');
                Response::SetString('estate_type', $estate_type);
                Response::SetInteger('id', $item['id_parent']);
                $html = $eml_tpl->Processing();
                $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                // параметры письма
                $mailer->Body = $html;
                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Новое объявление с видео');
                $mailer->IsHTML(true);
                $mailer->AddAddress(Config::Get('emails/manager'));
                $mailer->AddAddress(Config::Get('emails/web'));
                $mailer->From = 'no-reply@bsn.ru';
                $mailer->FromName = iconv('UTF-8', $mailer->CharSet, "BSN.ru");
                // попытка отправить
                $mailer->Send();                 
                
                //отправка пользователю
                $user = $db->fetch("SELECT 
                                        ".$sys_tables['users'].".email
                                    FROM ".$sys_tables['users']."
                                    RIGHT JOIN ".$sys_tables[$estate_type]." ON ".$sys_tables[$estate_type].".id_user = ".$sys_tables['users'].".id
                                    RIGHT JOIN ".$sys_tables[$estate_type."_videos"]." ON ".$sys_tables[$estate_type."_videos"].".id_parent = ".$sys_tables[$estate_type].".id
                                    WHERE ".$sys_tables[$estate_type].".id = ?
                    
                ", $item['id_parent']);
                if(!empty($user['email']) && Validate::isEmail($user['email'])){
                    $mailer = new EMailer('mail');           
                    // инициализация шаблонизатора
                    $eml_tpl = new Template('video.convert.email.user.html', 'cron/estate/');
                    Response::SetString('estate_type', $estate_type);
                    Response::SetInteger('id', $item['id_parent']);
                    $html = $eml_tpl->Processing();
                    $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                    // параметры письма
                    $mailer->Body = $html;
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Ваше объявление с видео на BSN.ru');
                    $mailer->IsHTML(true);
                    $mailer->AddAddress($user['email']);
                    $mailer->From = 'no-reply@bsn.ru';
                    $mailer->FromName = iconv('UTF-8', $mailer->CharSet, "BSN.ru");
                    // попытка отправить
                    $mailer->Send();   
                }                   
                         
        }
        break;
        exit;
    }
}
?>
