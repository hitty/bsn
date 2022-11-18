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
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
include('includes/class.housing_estates.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
$sys_tables = Config::$sys_tables;
$estates = array('housing_estates','live','build','commercial','country');

foreach($estates as $estate_type){  
    $db->querys("update ".$sys_tables[$estate_type]." set lat=0.00, lng=0.00 WHERE lat <=59.941 and lat>=59.937 AND lng<=30.321 and lng>=30.309");
    switch($estate_type){
        case 'build':
            $estate = new EstateListBuild();
            break;
        case 'commercial':
            $estate = new EstateListCommercial();
            break;
        case 'country':
            $estate = new EstateListCountry();
            break;
        case 'live':
            $estate = new EstateListLive();
            break;
        case 'housing_estates':
            $estate = new HousingEstates();
            break;
    }

    $list = $estate->Search($sys_tables[$estate_type].".published = 1 AND (".$sys_tables[$estate_type].".lat < 45 OR ".$sys_tables[$estate_type].".lng > 50)",15000,0,false);
    if(!empty($list)){
        foreach($list as $k=>$item){
            $geo = curlThis("http://geocode-maps.yandex.ru/1.x/?format=json&kind=street&geocode=".$item['full_address']);
            $geo = json_decode($geo);
            if(!empty($geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos)){
                $point = explode(" ",$geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
                if(($point[0]>=59.941 || $point[0]<=59.937) && ($point[1]>=30.321 || $point[1]<=30.309)){
                    $item['lng'] = $point[0];
                    $item['lat'] = $point[1];
                    
                    $db->querys("UPDATE ".$sys_tables[$estate_type]." SET lat=?, lng=? WHERE id=?",$item['lat'], $item['lng'],$item['id']);
                }
            }        }
    }
}

?>
