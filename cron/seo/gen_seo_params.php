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



// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
// вспомогательные таблицы
$sys_tables = Config::$sys_tables; 
 
$seo_table = 'service.seo_estate_pages';
$deals = array(
    'sell'=>'Продажа',
    'rent'=>'Аренда'
);
$estate_types = array(
    'live'=>'жилой', 'build'=>'строящейся', 'country'=>'загородной', 'commercial'=>'коммерческой'
);
                                                                       

//<Тип сделки> <Сегмент рынка> в <Район> Санкт-Петербурга           
$districts = $db->fetchall("SELECT * FROM ".$sys_tables['districts']);
$district_areas = $db->fetchall("SELECT *
                                 FROM ".$sys_tables['geodata']."
                                 WHERE  id_region = 47 AND a_level = 2
                                 ");
$subways = $db->fetchall("SELECT * FROM ".$sys_tables['subways']." WHERE parent_id = 34142");                                 
/*
foreach($deals as $deal_url => $deal_title){
    foreach($estate_types as $estate_type => $estate_title){
        if($deal_title=='Аренда' && $estate_type=='build') echo '';
        else {
            if($estate_type!='country'){
                foreach($districts as $district){
                   echo $deal_title.' '.$estate_title.' недвижимости в '.$district['title_prepositional'].' районе Санкт-Петербурга';
                   echo '  :  ';
                   echo '/'.$estate_type.'/'.$deal_url.'/?districts='.$district['id'];
                   echo '  <br />  ';
                }
            }
            foreach($district_areas as $district_area){
               echo $deal_title.' '.$estate_title.' недвижимости '.$district_area['title_prepositional'].' районе Ленинградской области';
               echo '  :  ';
               echo '/'.$estate_type.'/'.$deal_url.'/?district_areas='.$district_area['id'];
               echo '  <br />  ';
            }
        }
    }
}

//<Тип сделки> <Тип объекта> в <Район> Санкт-Петербурга
foreach($deals as $deal_url => $deal_title){
    foreach($estate_types as $estate_type => $estate_title){
        if($deal_title=='Аренда' && $estate_type=='build') echo '';
        else {
            if($estate_type=='build') $type_objects = array(0=>array('id'=>false,'title_genitive_plural'=>'квартир в новостройках '));
            else $type_objects = $db->fetchall("SELECT * FROM ".$sys_tables['type_objects'][$estate_type]);
            
            foreach($type_objects as $type_object){
                if($estate_type!='country'){
                    foreach($districts as $district){
                       echo $deal_title.' '.$type_object['title_genitive_plural'].' в '.$district['title_prepositional'].' районе Санкт-Петербурга';
                       echo '  :  ';
                       echo '/'.$estate_type.'/'.$deal_url.'/?districts='.$district['id'].(!empty($type_object['id'])?'&obj_type='.$type_object['id']:'');
                       echo '  <br />  ';
                    }
                }
                foreach($district_areas as $district_area){
                       echo $deal_title.' '.$type_object['title_genitive_plural'].' '.$district_area['title_prepositional'].' районе Ленинградской области';
                       echo '  :  ';
                       echo '/'.$estate_type.'/'.$deal_url.'/?district_areas='.$district_area['id'].(!empty($type_object['id'])?'&obj_type='.$type_object['id']:'');
                       echo '  <br />  ';
                }
            }
        }
    }
}

//<Тип сделки> <Сегмент рынка> у метро <Метро> 

foreach($deals as $deal_url => $deal_title){
    foreach($estate_types as $estate_type => $estate_title){
        if($deal_title=='Аренда' && $estate_type=='build') echo '';
        else {
            if($estate_type!='country'){
                foreach($subways as $subway){
                   echo $deal_title.' '.$estate_title.' недвижимости у метро '.$subway['title'];
                   echo '  :  ';
                   echo '/'.$estate_type.'/'.$deal_url.'/?subways='.$subway['id'];
                   echo '  <br />  ';
                }
            }

        }
    }
}
*/

foreach($deals as $deal_url => $deal_title){
    foreach($estate_types as $estate_type => $estate_title){
        if($deal_title=='Аренда' && $estate_type=='build') echo '';
        else {
            if($estate_type=='build') $type_objects = array(0=>array('id'=>false,'title_genitive_plural'=>'квартир в новостройках '));
            else $type_objects = $db->fetchall("SELECT * FROM ".$sys_tables['type_objects'][$estate_type]);
            
            foreach($type_objects as $type_object){
                if($estate_type!='country'){
                    foreach($subways as $subway){
                       echo $deal_title.' '.$type_object['title_genitive_plural'].' у метро '.$subway['title'];
                       echo '  :  ';
                       echo '/'.$estate_type.'/'.$deal_url.'/?subways='.$subway['id'].(!empty($type_object['id'])?'&obj_type='.$type_object['id']:'');
                       echo '  <br />  ';
                    }
                }
            }
        }
    }
}
 ?>