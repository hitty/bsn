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

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

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
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;     // Photos (работа с графикой)
require_once('includes/class.email.php');
//Session::Init();
Session::Init(null,null,'public',true);
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$db->select_db('estate');
//првоерка на структуру новой таблицы
$previous_field = '';

//$estate_types = array('build','commercial','country','live');
$estate_types = array('build');
$mail_text = "";
foreach($estate_types as $estate){
    $objects = $db->fetchall("SELECT ".$sys_tables[$estate."_archive"].".id,
                                     GROUP_CONCAT(".$sys_tables[$estate."_photos"].".id) AS photos_ids,
                                     COUNT(*) AS photos_amount
                              FROM ".$sys_tables[$estate."_archive"]."
                              LEFT JOIN ".$sys_tables[$estate."_photos"]." ON ".$sys_tables[$estate."_photos"].".id_parent = ".$sys_tables[$estate."_archive"].".id
                              WHERE ".$sys_tables[$estate."_photos"].".id IS NOT NULL
                              GROUP BY ".$sys_tables[$estate."_archive"].".id
                              HAVING photos_amount > 1
                              ORDER BY date_change ASC
                              LIMIT 1000");
    $image_folder = !empty(Config::$values['img_folders'][$estate]) ? Config::$values['img_folders'][$estate] : Config::$values['img_folders']['basic'];
    $counter = 0;
    if(!empty($objects)) {
        foreach($objects as $o=>$object){
			echo $estate." ".$object['id']." -> ".$object['photos_ids']."\r\n";
            $photos_to_delete = array_slice(explode(',',$object['photos_ids']),1);
            foreach($photos_to_delete as $k=>$i){
                $res = Photos::Delete($estate,$i);
                $counter += (!empty($res)?1:0);
            }
                
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