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
include('includes/class.robot.php');          // функции  из модуля
include('includes/class.cottages.php');          // функции  из модуля
//Session::Init();
Session::Init(null,null,'public',true);
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
 $sys_tables = Config::$sys_tables;
 header('Content-Type: text/html; Charset='.Config::$values['site']['charset']);   
 $cottages = new Cottages();
                                                  
$list = $cottages->getList(100,0, $sys_tables['cottages'] .".lat > 59.9378 AND " . $sys_tables['cottages'] .".lat < 59.93783 AND " . $sys_tables['cottages'] .".lng > 30.31178 AND " . $sys_tables['cottages'] .".lng < 30.31180 " );
foreach( $list as $k => $item ){
    $robot = new Robot( $item['id_user'] );
    $addr =  'Ленинградская область, ' .
            ( !empty( $item['district_title'] ) ? $item['district_title'] .' район' : '' ) .
            ( !empty( $item['txt_addr'] ) ? ', ' . $item['txt_addr'] : '' ) ;
    $geo = curlThis("http://geocode-maps.yandex.ru/1.x/?format=json&kind=street&geocode=".$addr);
    $geo = json_decode($geo);
    if(!empty($geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos)){
        $point = explode(" ",$geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
        if( !empty( $point[0] ) && $point[0] > 0 ){
            $data = [
                'id' => $item['id'],
                'lat' => $point[1],
                'lng' => $point[0],
            ];
            $db->updateFromArray( $sys_tables['cottages'], $data, 'id' );
        }
    }
}
?>
