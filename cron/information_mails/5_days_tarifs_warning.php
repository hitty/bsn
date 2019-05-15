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
require_once('includes/class.email.php');
require_once('includes/class.template.php');
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

////////////////////////////////////////////////////////////////////////////////////////////////
// Отправка писем о перемещении объектов в архив
////////////////////////////////////////////////////////////////////////////////////////////////   
$list = $db->fetchall("SELECT ".$sys_tables['users'].".*,
                                        DATEDIFF( tarif_end, CURDATE( )) as date_diff, 
                                        ".$sys_tables['tarifs'].".title,
                                        ".$sys_tables['tarifs'].".cost,
                                        ".$sys_tables['tarifs'].".premium_available,
                                        ".$sys_tables['tarifs'].".promo_available,
                                        ".$sys_tables['tarifs'].".vip_available
                                 FROM ".$sys_tables['users']."
                                 LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users'].".id_tarif
                                 WHERE ".$sys_tables['users'].".id_tarif > 0 AND 
                                        CURDATE() >=  `tarif_end` - INTERVAL 5 DAY AND 
                                       ".$sys_tables['users'].".tarif_renewal = 1 AND
                                       ".$sys_tables['users'].".balance < ".$sys_tables['tarifs'].".cost 
                                        "
);


if(!empty($list)) {
    foreach($list as $k=>$item){
        //отправка письма пользователю
        if(!empty($item['email']) && Validate::isEmail($item['email'])){
            Response::SetArray('item', $item);
            $eml_tpl = new Template('mail.tarif.warning.html', 'cron/information_mails/');
            $mailer = new EMailer('mail');
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Окончание срока действия тарифа на BSN.ru");
            $mailer->IsHTML(true);
            $mailer->AddAddress($item['email']);
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'Большой Сервер Недвижимости bsn.ru');
            // попытка отправить
            $mailer->Send();
        }
    }
}
?>
