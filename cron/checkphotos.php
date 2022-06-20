#!/usr/bin/php
<?php
/*
/* Скрипт очистки картинок и записей для несуществующих записей в родительских таблицах
*/

$overall_time_counter = microtime(true);
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
$error_log = ROOT_PATH.'/cron/mailers/spam_error.log';
$test_performance = ROOT_PATH.'/cron/gen_sitemap/test_performance.log';
file_put_contents($error_log,'');
file_put_contents($test_performance,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.email.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");

 
// вспомогательные таблицы модуля
$sys_tables = Config::Get('sys_tables');
$estate_types = array('commercial','country','live','build');
$estate_types = array( 'live', 'build' );
//$estate_types = array('users','agencies','help_categories','calendar_events', 'galleries', 'opinions_expert_profiles', 'opinions_expert_agencies', 'references_docs', 'cottages', 'business_centers_offices', 'housing_estates_progresses', 'weights', 'spec_offers_objects', 'photoblocks', 'context_advertisements', 'diploms', 'konkurs_members', 'mailers', 'promotions', 'webinars', 'news_parsing', 'live_videos', 'build_videos');
$sm = 'img/uploads/sm';
$med = 'img/uploads/med';
$big = 'img/uploads/big';
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;
/*
foreach($estate_types as $estate){
    $list = $db->fetchall("SELECT *, LEFT(name,2) as subfolder FROM ".$sys_tables[$estate.'_photos']);
    echo ':::' . $estate . ':::';
    foreach($list as $k=>$item){
       if( !file_exists( $root . '/' . $sm.'/'.$item['subfolder'].'/'.$item['name']) && !file_exists($root.'/'.$med.'/'.$item['subfolder'].'/'.$item['name'])){
            echo  $item['id'].'; '.' DEL - '.$estate.' ; ';  echo "\n";
            $db->query("DELETE FROM ".$sys_tables[$estate.'_photos']." WHERE id=?",$item['id']);
            //смотрим объект:
            $parent_object = $db->fetch("SELECT id,id_main_photo FROM ".$sys_tables[$estate]." WHERE id = ?",$item['id_parent']);
            //если объект есть, переназначаем mainPhoto
            if(!empty($parent_object) && !empty($parent_object['id']) && $parent_object['id_main_photo'] == $item['id']) Photos::setMain($estate,$parent_object['id']);
       }
    }
}
*/


foreach($estate_types as $estate){
    $list = $db->fetchall("SELECT *, LEFT(name,2) as subfolder FROM ".$sys_tables[$estate.'_photos'] . " WHERE checked = 2 LIMIT 500000");
    echo ':::' . $estate . ':::';
    foreach($list as $k=>$item){
        $estate_item = $db->fetch(" SELECT * FROM ".$sys_tables[$estate]." WHERE id = ?", $item['id_parent'] );
        if( empty( $estate_item ) ) {
            $db->query( " DELETE FROM ".$sys_tables[$estate.'_photos']." WHERE id = ?", $item['id'] );
            $photos = $db->fetchall(" SELECT * FROM ".$sys_tables[$estate.'_photos']." WHERE external_img_src = ?", false, $item['external_img_src'] );
            if( empty( $photos ) ) {
                if( file_exists( $root . '/' . $sm . '/' . $item['subfolder'] . '/' . $item['name'] ) ) unlink( $root . '/' . $sm.'/'.$item['subfolder'].'/'.$item['name'] );
                if( file_exists( $root . '/' . $med . '/' . $item['subfolder'] . '/' . $item['name'] ) ) unlink( $root . '/' . $med.'/'.$item['subfolder'].'/'.$item['name'] );
                if( file_exists( $root . '/' . $big . '/' . $item['subfolder'] . '/' . $item['name'] ) ) unlink( $root . '/' . $big.'/'.$item['subfolder'].'/'.$item['name'] );
                echo ' --- DELETED --- ' . $item['name'] . '  ||  ' . $item['external_img_src'] . "\n";
            }
        } else $db->query( " UPDATE ".$sys_tables[$estate.'_photos']." SET checked = 1 WHERE id = ?", $item['id'] );    
    }
}
