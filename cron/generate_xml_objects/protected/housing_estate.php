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
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
require_once('includes/class.housing_estates.php');
require_once('includes/class.photos.php');


define('__XMLPATH__',ROOT_PATH.'/xml/he.xml');
define('__URL__','http://www.bsn.ru/');


$db->select_db('estate');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = '".Config::$values['mysql']['lc_time_names']."';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$log = array();
$xml = new DOMDocument('1.0','windows-1251');

$xmlUrlset = $xml->appendChild($xml->createElement('root'));

//Init xml-making
$array = array();
$housing_estates = new HousingEstates();
$list = $housing_estates->Search($sys_tables['housing_estates'].'.published = 1', 10, 0);
foreach($list as $k=>$item) {
    $housing_estate_item = $housing_estates->getItem($item['id']);
    $titles = $housing_estates->getTitles($item['id']);
    $array[$k]['id'] = $item['id']; 
    $array[$k]['title'] = $item['title']; 
    if(!empty($item['class_title'])) $array[$k]['class'] = $item['class_title']; 
    if($item['lat'] > 10) $array[$k]['lat'] = $item['lat']; 
    if($item['lng'] > 10) $array[$k]['lng'] = $item['lng']; 
    if(!empty($item['district'])) $array[$k]['district'] = $item['district'].' район'; 
    if(!empty($item['district_area'])) $array[$k]['district_area'] = $item['district_area'].' район ЛО'; 
    if(!empty($item['subway'])) $array[$k]['subway'] = $item['subway']; 
    if(!empty($item['txt_addr'])) $array[$k]['address'] = $item['txt_addr']; 
    if(!empty($item['txt_addr'])) $array[$k]['address'] = $item['txt_addr']; 
    if(!empty($item['building_type'])) $array[$k]['build_type'] = $item['building_type']; 
    if(!empty($item['floors'])) $array[$k]['floors'] = $item['floors']; 
    $queries = $housing_estates->getQueries($item['id']);
    if(!empty($queries)){
        $array[$k]['queries'] = '';
        foreach($queries as $q => $query) {
              $array[$k]['queries'] .= 'Очередь: '.$query['query_num_list'].', Срок сдачи: '.$query['build_complete_title'].', Корпуса: '.$query['corpuses_list'].';'; 
        }
    }
    if(!empty($item['date_change'])) $array[$k]['datetime'] = $item['date_change']; 
    if(!empty($item['developer'])) $array[$k]['developer'] = $item['developer']; 
    if(!empty($item['site'])) $array[$k]['site'] = $item['site']; 
    if(!empty($item['declaration'])) $array[$k]['declaration'] = $item['declaration']; 
    $photos_list = Photos::getList('housing_estates', $item['id']);
    if(!empty($photos_list)){
        foreach($photos_list as $p => $photo){
            //if(file_exists(ROOT_PATH."/".Config::Get('img_folders/live')."/big/".$photo['subfolder']."/".$photo['name']))
                $array[$k]['photo_'.($p+1)] = "http://www.bsn.ru/".Config::Get('img_folders/live')."/big/".$photo['subfolder']."/".$photo['name'];
        }
    }
}

echo json_encode($array);


?>