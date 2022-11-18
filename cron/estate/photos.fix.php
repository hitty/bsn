#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  (крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
setlocale(LC_ALL, 'rus');
/**
* Обработка новых объектов
*/
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/functions.php');          // функции  (модуля
Session::Init();
Request::Init();
Cookie::Init(); 
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("set lc_time_names = 'ru_RU'");
require_once('includes/class.email.php');
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;     // Photos (работа с графикой)
require_once('includes/class.moderation.php'); // Moderation (процедура модерации)
require_once('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$estate_types = array('live','build','country','commercial');

/* Список объектов у которых id_main_photo совпадает с удаленной фотографий */
/*
foreach($estate_types as $estate){
    $list = $db->fetchall("
        SELECT 
            ".$sys_tables[$estate].".id_main_photo, 
            ".$sys_tables[$estate].".id, 
            ".$sys_tables[$estate.'_photos'].".name, 
            LEFT(".$sys_tables[$estate.'_photos'].".name,2) as subfolder, 
            ".$sys_tables[$estate.'_photos'].".id as photo_id 
        FROM  ".$sys_tables[$estate.'_photos']."
        LEFT JOIN ".$sys_tables[$estate]." ON ".$sys_tables[$estate].".id = ".$sys_tables[$estate.'_photos'].".id_parent
        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables[$estate].".id_user
        WHERE ".$sys_tables['users'].".agency_admin = 1 AND ".$sys_tables['users'].".id = 48765 
        GROUP BY ".$sys_tables[$estate.'_photos'].".id
    ");
    foreach($list as $k=>$item){
        $db->querys("UPDATE ".$sys_tables[$estate]." SET id_main_photo = 0, published = 2 WHERE id = ?",$item['id']);
        $db->querys("DELETE FROM  ".$sys_tables[$estate.'_photos']." WHERE id = ?", $item['photo_id']);
        unlink($root . '/img/uploads/sm/' . $item['subfolder']. '/' . $item['name']);
        unlink($root . '/img/uploads/med/' . $item['subfolder']. '/' . $item['name']);
        unlink($root . '/img/uploads/big/' . $item['subfolder']. '/' . $item['name']);
            
    }
}
die();
 Список фотографий у которых id_main_photo = 0 , но записи в фото сущестуют 
foreach($estate_types as $estate){
    $list = $db->fetchall("
        SELECT 
            ".$sys_tables[$estate].".id, 
            ".$sys_tables[$estate.'_photos'].".id, 
            ".$sys_tables[$estate.'_photos'].".external_img_src,
            ".$sys_tables[$estate.'_photos'].".name,
            LEFT(".$sys_tables[$estate.'_photos'].".name, 2) as subfolder
        FROM  ".$sys_tables[$estate.'_photos']."
        LEFT JOIN ".$sys_tables[$estate]." ON ".$sys_tables[$estate].".id = ".$sys_tables[$estate.'_photos'].".id_parent
        WHERE 
            ".$sys_tables[$estate.'_photos'].".id >0 AND 
            ".$sys_tables[$estate].".id_main_photo = 0
    ");
    echo $estate.' : '.count($list)."\n";
    foreach($list as $k=>$item){
        $same_photo = array();
        if(!empty($item['external_img_src'])) $same_photo = $db->fetchall("SELECT *, LEFT(".$sys_tables[$estate.'_photos'].".name, 2) as subfolder FROM ".$sys_tables[$estate.'_photos']." WHERE external_img_src = ? AND external_img_src!=''", false, $item['external_img_src']);
        if(count($same_photo) <= 1) {
            if(file_exists(ROOT_PATH.'/img/uploads/sm/'.$item['subfolder'].'/'.$item['name'])) {
                unlink(ROOT_PATH.'/img/uploads/sm/'.$item['subfolder'].'/'.$item['name']);
            }
            if(file_exists(ROOT_PATH.'/img/uploads/med/'.$item['subfolder'].'/'.$item['name'])) unlink(ROOT_PATH.'/img/uploads/med/'.$item['subfolder'].'/'.$item['name']);
            if(file_exists(ROOT_PATH.'/img/uploads/big/'.$item['subfolder'].'/'.$item['name'])) unlink(ROOT_PATH.'/img/uploads/big/'.$item['subfolder'].'/'.$item['name']);
        }
        $db->querys("DELETE FROM ".$sys_tables[$estate.'_photos']." WHERE id = ?", $item['id']);
    }
}
*/
/* Список фотографий у которых нет объектов */
foreach($estate_types as $estate){
    $list = $db->fetchall("
        SELECT 
            ".$sys_tables[$estate].".id, 
            ".$sys_tables[$estate.'_photos'].".id, 
            ".$sys_tables[$estate.'_photos'].".external_img_src,
            ".$sys_tables[$estate.'_photos'].".name,
            LEFT(".$sys_tables[$estate.'_photos'].".name, 2) as subfolder
        FROM  ".$sys_tables[$estate.'_photos']."
        LEFT JOIN ".$sys_tables[$estate]." ON ".$sys_tables[$estate].".id = ".$sys_tables[$estate.'_photos'].".id_parent
        WHERE 
            ".$sys_tables[$estate.'_photos'].".id >0 AND 
            ".$sys_tables[$estate].".id_main_photo IS NULL
        ORDER BY ".$sys_tables[$estate.'_photos'].".id DESC
        LIMIT 75000
    ");
    echo "\n\n".$estate.' : '.count($list)."\n";
    foreach($list as $k=>$item){
      
        $photo = $db->fetch("SELECT *, LEFT(".$sys_tables[$estate.'_photos'].".name, 2) as subfolder FROM ".$sys_tables[$estate.'_photos']." WHERE id = ?", $item['id']);
        $same_photo = array();
        if(!empty($item['external_img_src'])) $same_photo = $db->fetchall("SELECT *, LEFT(".$sys_tables[$estate.'_photos'].".name, 2) as subfolder FROM ".$sys_tables[$estate.'_photos']." WHERE external_img_src = ? AND external_img_src!=''", false, $item['external_img_src']);
        
        if(count($same_photo) <= 1) {
            //echo $item['name'].' : '.$item['id'].' : ';
            if(file_exists(ROOT_PATH.'/img/uploads/sm/'.$item['subfolder'].'/'.$item['name'])) {
                if(unlink(ROOT_PATH.'/img/uploads/sm/'.$item['subfolder'].'/'.$item['name'])) echo 'sm : ';
            }
            if(file_exists(ROOT_PATH.'/img/uploads/med/'.$item['subfolder'].'/'.$item['name'])) {
                if(unlink(ROOT_PATH.'/img/uploads/med/'.$item['subfolder'].'/'.$item['name'])) echo 'med : ';
            }
            if(file_exists(ROOT_PATH.'/img/uploads/big/'.$item['subfolder'].'/'.$item['name'])) {
                if(unlink(ROOT_PATH.'/img/uploads/big/'.$item['subfolder'].'/'.$item['name'])) echo 'big';
            }
        }
        echo "\n";
        
        $db->querys("DELETE FROM ".$sys_tables[$estate.'_photos']." WHERE id = ?", $item['id']);
    }
}



/* Список фотографий у которых нет объектов
foreach($estate_types as $estate){
    $list = $db->fetchall("
        SELECT 
            ".$sys_tables[$estate].".id_main_photo, 
            ".$sys_tables[$estate].".id, 
            ".$sys_tables[$estate.'_photos'].".name, 
            LEFT( ".$sys_tables[$estate.'_photos'].".name, 2 ) AS subfolder
        FROM ".$sys_tables[$estate]."
        LEFT JOIN ".$sys_tables[$estate.'_photos']." ON ".$sys_tables[$estate].".id = ".$sys_tables[$estate.'_photos'].".id_parent
        WHERE ".$sys_tables[$estate.'_photos'].".id IS NULL 
        AND ".$sys_tables[$estate].".id_main_photo >0
        LIMIT 400000
    ");
    
    echo "\n\n".$estate.' : '.count($list)."\n";
    foreach($list as $k=>$item) {
        $db->querys("UPDATE ".$sys_tables[$estate]." SET id_main_photo = 0, has_photo = 1 WHERE id = ?", $item['id']);
        $photo_id = $db->fetch("SELECT id FROM ".$sys_tables[$estate."_photos"]." WHERE id_parent = ? ORDER BY id ASC",$item['id']);
        if(!empty($photo_id)) $db->querys("UPDATE ".$sys_tables[$estate]." SET id_main_photo = ?, has_photo = 2 WHERE id = ?",$photo_id['id'], $item['id']);
        
    }
}
 */
/* Список удаленных физически фотографий у которых есть записи
foreach($estate_types as $estate){
    $list = $db->fetchall("
        SELECT 
            ".$sys_tables[$estate].".id, 
            ".$sys_tables[$estate.'_photos'].".id as photo_id, 
            ".$sys_tables[$estate.'_photos'].".external_img_src,
            ".$sys_tables[$estate.'_photos'].".name,
            LEFT(".$sys_tables[$estate.'_photos'].".name, 2) as subfolder
        FROM  ".$sys_tables[$estate.'_photos']."
        LEFT JOIN ".$sys_tables[$estate]." ON ".$sys_tables[$estate].".id = ".$sys_tables[$estate.'_photos'].".id_parent
    ");
    echo $estate.' : '.count($list)."\n";
    foreach($list as $k=>$item){
    echo $item['id'].' : '.$item['photo_id']."\n";
        if(
            !file_exists(ROOT_PATH.'/img/uploads/sm/'.$item['subfolder'].'/'.$item['name']) 
            && !file_exists(ROOT_PATH.'/img/uploads/med/'.$item['subfolder'].'/'.$item['name']) 
            && !file_exists(ROOT_PATH.'/img/uploads/big/'.$item['subfolder'].'/'.$item['name']) 
        ) {
            print_r($item);
            //удаляем из базы фотки у которых данный external_id
            $db->querys("DELETE FROM ".$sys_tables[$estate.'_photos']." WHERE id = ?", $item['photo_id']);
            if(!empty($item['id'])){
                //определение заглавной фотографии
                $main_photo_id = $db->fetch("SELECT id FROM ".$sys_tables[$estate]." WHERE id = ? AND id_main_photo = ?", $item['id'], $item['photo_id']);
                if(!empty($main_photo_id)) {
                    $db->querys("UPDATE ".$sys_tables[$estate]." SET id_main_photo = 0, has_photo = 1 WHERE id = ?",$item['id']);
                    $photo_id = $db->fetch("SELECT id FROM ".$sys_tables[$estate."_photos"]." WHERE id_parent = ? ORDER BY id ASC",$item['id']);
                    if(!empty($photo_id)) $db->querys("UPDATE ".$sys_tables[$estate]." SET id_main_photo = ?, has_photo = 2 WHERE id = ?",$photo_id['id'], $item['id']);
                }
            }
            
        }
    }
}
 */
?>