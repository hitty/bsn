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
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
include('includes/class.moderation.php'); // Moderation (процедура модерации)
include('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$estate_types = array('live', 'build', 'commercial', 'country');
foreach($estate_types as $k=>$estate_type){
for($i=1; $i<=5; $i++){
     $item = $db->fetchall("
        SELECT COUNT( * ) , MAX( id ) AS id, info_source
        FROM  ".$sys_tables[$estate_type]." 
        GROUP BY cost, date_change, date_in, id_user, id_region, txt_addr, notes, external_id, id_subway, id_district, date_moderated, admin_moderated,`status`
        HAVING COUNT( * ) >1
        ORDER BY id DESC 
    ");
    
    if(empty($item)) break;
    
    $db->query("
        DELETE FROM ".$sys_tables[$estate_type]." WHERE id IN (
        SELECT b.id FROM (
        SELECT MAX(id) as id
        FROM  ".$sys_tables[$estate_type]." 
        GROUP BY cost, date_change, date_in, id_user, id_region, txt_addr, notes, external_id, id_subway, id_district, date_moderated, admin_moderated,`status`
        HAVING COUNT( * ) >1
        ORDER BY  `blocking_time` DESC ) b )
        ");
    }
}
?>
