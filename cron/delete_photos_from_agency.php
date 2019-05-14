#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/cottages/error.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');
/**
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
require_once('includes/class.photos.php');     // Photos (работа с графикой)
require_once('includes/class.email.php');
//Session::Init();
Session::Init(null,null,'public',true);
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$db->select_db('estate');
//првоерка на структуру новой таблицы
$previous_field = '';

$id_user = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 39126;
$estate_types = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][2]) ? array( $_SERVER['argv'][2] ) : array('commercial');

//$estate_types = array('build','commercial','country','live');
$mail_text = "";                                   
foreach($estate_types as $estate){
    $objects = $db->fetchall("
        SELECT photos.* FROM " . $sys_tables[$estate] . "_photos photos
        RIGHT JOIN " . $sys_tables[$estate] . " b ON b.id = photos.id_parent
        WHERE b.id_user = " . $id_user . " AND photos.id > 0
    ");
    
    $image_folder = !empty(Config::$values['img_folders'][$estate]) ? Config::$values['img_folders'][$estate] : Config::$values['img_folders']['basic'];
    $counter = 0;
    if(!empty($objects)) {
        foreach($objects as $o=>$object){
			$res = Photos::Delete( $estate, $object['id'] );
        }
    }
    echo $estate.": ".$counter." photos deleted from ".count($objects)."objects \r\n";
    $mail_text .= $estate.": ".$counter." фотографий удалено из ".count($objects)." объектов\r\n";
}

$manager_mailer = new EMailer('mail');
$mail_text = "Очистка фотографий из таблицы архивных:\r\n".$mail_text;
$html = iconv('UTF-8', $manager_mailer->CharSet, $mail_text);
// параметры письма
$manager_mailer->Body = nl2br($html);
$manager_mailer->AltBody = nl2br($html);
$manager_mailer->IsHTML(true);
$manager_mailer->AddAddress("web@bsn.ru");
$manager_mailer->From = 'photo_cleaner@bsn.ru';
$manager_mailer->FromName = iconv('UTF-8', $manager_mailer->CharSet,'BSN.ru');
// попытка отправить
$manager_mailer->Send();  

?>