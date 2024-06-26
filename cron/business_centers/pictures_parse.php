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
$error_log = ROOT_PATH.'/cron/business_centers/error.log';
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
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$dir = ROOT_PATH."/cron/business_centers/pictures/";
//флаг однократного обновления
$update_flag = true;
$dh = opendir($dir);
$mail_text = '';  // текст письма
/*
foreach($business_centers as $k=>$cottage){
    $cottage = trim($cottage);
    if($cottage!=''){
        $item = $db->fetch("SELECT * FROM ".$sys_tables['business_centers']." WHERE title = ?",$cottage);
        if(empty($item)) echo $cottage.'<br/>';
        else{
            Photos::DeleteAll('business_centers',$item['id']);
        }
    }
}
die();  
*/ 
while($pic_dir = readdir($dh))
{
    if($pic_dir!='.' && $pic_dir!='..' && !is_file($pic_dir))
    {
        $new_pic_dir = Convert::ToInt($pic_dir);
        rename($dir.$pic_dir,$dir.$new_pic_dir);
        $item = $db->fetch("SELECT * FROM ".$sys_tables['business_centers']." WHERE id = ?",$new_pic_dir);        
        if(!empty($item)){
            $id = $item['id'];
            $images_count = $db->fetchall("SELECT * FROM ".$sys_tables['business_centers_photos']." WHERE id_parent = ".$id);
            if(count($images_count)<3){
                $db->querys("UPDATE ".$sys_tables['business_centers']." SET id_main_photo = 0 WHERE id = ?",$id);
                $db->querys("DELETE FROM ".$sys_tables['business_centers_photos']." WHERE id_parent = ?",$id);
                echo $item['id']."\n";
                $pic_dh = opendir($dir.$id);
                while($images = readdir($pic_dh))
                {                                        
                    $image_name = $dir.$id.'/'.$images;
                    $new_image_name = $dir.$id.'/rotated-'.$images;
                    $size = @getimagesize($dir.$id.'/'.$images);
                    if($images!='.' && $images!='..' && !empty($size)){
                        ++$images_count;
                        if(file_exists($new_image_name)) { echo $new_image_name; unlink($new_image_name); }
		                $new_image = new Imagick(); 
                        $new_image->readImage($image_name); 
                        $new_image->rotateImage(new ImagickPixel('#FFFFFF'), 1.2); 
                        $new_image->writeImage($new_image_name);
                        $new_image->clear(); 
                        $new_image->destroy(); 
                        Photos::imageResize($new_image_name,ROOT_PATH.'/'.Config::$values['img_folders']['business_centers'].'/'.$images,$size[0]*0.9, $size[1]*0.9,'cut_wo_resize');
                        unlink($new_image_name);
                        unlink($image_name);
                       
                        Photos::Add('business_centers', $item['id'], '', false, $images);            
                    }
                }
            } else unlink($dir.$id);
        }
    }
}
?>
