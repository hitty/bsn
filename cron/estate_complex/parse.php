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

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

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
include('cron/robot/class.xml2array.php');  // конвертация xml в array
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


$dir = ROOT_PATH."/cron/estate_complex/xml/";                  

//ЖК ОТ Лайма
//downloadFile($dir."27051_lime.xml", "http://limestate.ru/xml/bsn_mini.xml");
$type = 1;

$dh = opendir($dir);
$mail_text = '';



while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..')         
    {
        exec("chmod 777 ".$dir.$filename);
       
        //Определение id_user по начальному имени файла
        $id_user = explode('_',$filename);
        $id_user = Convert::ToInt($id_user[0]); 
        if($id_user<1) $mail_text.="Ошибка авторизации";
        else
        {
            //информация об агентстве
            $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".`id`, 
                                         ".$sys_tables['agencies'].".`title`,
                                         ".$sys_tables['agencies'].".`elite_objects`,
                                         ".$sys_tables['agencies'].".`country_rent_objects`,
                                         ".$sys_tables['agencies'].".`activity`, 
                                         ".$sys_tables['managers'].".`email`, 
                                         ".$sys_tables['agencies'].".`email_service` 
                                  FROM ".$sys_tables['agencies']."
                                  RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
                                  LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager 
                                  WHERE ".$sys_tables['users'].".`id` = ?",
                                  $id_user) ;            
            if(empty($agency)) $mail_text.="Ошибка авторизации"; //агентство не найдено
            else {
                
                $xml_values = array();
                //читаем в строку нужный файл
                $contents = file_get_contents($dir.$filename);
                $xml_str=xml2array($contents);
                //по строке создаем объект simplexml
                if($xml_str===FALSE || empty($xml_str['root']['objects']['item'])) {
                    $errors_log['fatal'] = 'Файл '.$dir.$filename.' не может быть обработан, т.к. имеет невалидные теги';
                } else{
                    foreach ($xml_str['root']['objects']['item'] as $object) $xml_values[] =  $object;       
                    //обработка полученных значений
                    foreach($xml_values as $key=>$values){
                        //приведение всех ключей в нижний регистр
                        foreach($values as $k=>$val) $values[strtolower($k)] = !is_array($val)?$val:array_unique($val);
                        $db->query("INSERT INTO ".$sys_tables['estate_complexes_external']." SET id_user = ?, type = ?, external_id = ?, external_title = ?",
                            $id_user, $type, $values['id'], $values['title']
                        );
                    }
                }
            }
        }
        unlink($dir.$filename);
    }
}
                        
        
       
?>
