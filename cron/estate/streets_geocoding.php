#!/usr/bin/php
<?php
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
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
include('includes/class.housing_estates.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
$sys_tables = Config::$sys_tables;
$estates = array('housing_estates','live','build','commercial','country');

$list = $db->fetchall("SELECT 
                        street.parentguid,
                        street.id,
                        CONCAT(
                            IF(parent_3.offname!='', CONCAT(parent_3.offname , ' ', parent_3.shortname, ', '), ''),
                            IF(parent_2.offname!='', CONCAT(parent_2.offname , ' ', parent_2.shortname, ', '), ''),
                            IF(parent_1.offname!='', CONCAT(parent_1.shortname , ' ', parent_1.offname, ', '), ''),
                            CONCAT(street.shortname , ' ', street.offname)
                        )  as title
                        
                      FROM ".$sys_tables['geodata']." street
                      LEFT JOIN ".$sys_tables['geodata']." parent_1 ON parent_1.aoguid = street.parentguid
                      LEFT JOIN ".$sys_tables['geodata']." parent_2 ON parent_2.aoguid = parent_1.parentguid
                      LEFT JOIN ".$sys_tables['geodata']." parent_3 ON parent_3.aoguid = parent_2.parentguid
                      WHERE street.id_region = 47 AND street.a_level = 5 AND street.lng_center = 0 AND street.lat_center = 0
                      GROUP BY street.id
                     
");
if(!empty($list)){
    foreach($list as $k=>$item){
        $address = $item['title'];
        $geo = curlThis("http://geocode-maps.yandex.ru/1.x/?format=json&kind=street&geocode=".$address);
        $geo = json_decode($geo);
        if(!empty($geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos)){
            $point = explode(" ",$geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
            if( $point[1] != 59.939095 && $point[0] != 30.315868 && $point[0] > 0 && $point[1] > 0){
                $db->query("UPDATE ".$sys_tables['geodata']." SET lat_center = ?, lng_center = ? WHERE id = ?", $point[1], $point[0], $item['id']);
            }
        }
    }
}
$list = $db->fetchall("SELECT 
                        parentguid,
                        street.id,
                        CONCAT(street.shortname , ' ', street.offname) as title
                      FROM ".$sys_tables['geodata']." street
                      WHERE street.id_region = 78 AND street.a_level = 5 AND street.lng_center = 0 AND street.lat_center = 0
                      GROUP BY street.id
");
if(!empty($list)){
    foreach($list as $k=>$item){
        $parent = $db->fetch("SELECT 
                                CONCAT(street.shortname , ' ', street.offname) as title
                              FROM ".$sys_tables['geodata']." street
                              WHERE aoguid = ?
        ", $item['parentguid']);
        echo $address = 'Санкт-Петербург, '.$parent['title'].', '.$item['title'];
        $geo = curlThis("http://geocode-maps.yandex.ru/1.x/?format=json&kind=street&geocode=".$address);
        $geo = json_decode($geo);
        if(!empty($geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos)){
            $point = explode(" ",$geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
            if( $point[1] != 59.939095 && $point[0] != 30.315868 && $point[0] > 0 && $point[1] > 0){
                $db->query("UPDATE ".$sys_tables['geodata']." SET lat_center = ?, lng_center = ? WHERE id = ?", $point[1], $point[0], $item['id']);
            }
        }
    }
}

?>
