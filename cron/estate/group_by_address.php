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
include('includes/class.robot.php');      // 

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


$estates = array('build', 'commercial', 'live_archive', 'build_archive', 'commercial_archive');
$estates = array('live');
//$estates = array('live');
$robot = new Robot( 0 );

// 1.
foreach($estates as $estate_type){
    $list = $db->fetchall("SELECT * FROM ".$sys_tables[$estate_type]." WHERE group_id = 0 ORDER BY published = 1 DESC, id DESC LIMIT 75000");
   
    foreach($list as $k=>$item){
        $robot = new Robot($item['id_user']);
        $addr = array();
        //группировка по адресу
        if( !empty( $item['id_region'] ) ){
            $addr = $db->fetch("SELECT group_id FROM ".$sys_tables[$estate_type]." WHERE id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=? AND house=? AND corp IN (".(!empty($item['corp']) ? "'".$item['corp']."'" : '"0",""').") AND group_id>0 LIMIT 1",
                $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street'], $item['house']
            );
        }
        
        if( !empty( $addr ) ) $db->querys("UPDATE ".$sys_tables[$estate_type]." SET group_id=? WHERE id=?", $addr['group_id'], $item['id']);
        else {
            $robot->groupByAddress($estate_type, $item, false);
        }
         
    }
}
die();

// 2. обновление координат для группы 
foreach($estates as $estate_type){

    $list = $db->fetchall("SELECT COUNT(*), id, lat, lng, group_id, id_region, id_area, id_city, id_place, id_street, house, corp, txt_addr FROM ".$sys_tables[$estate_type]." WHERE lat < 1  GROUP BY group_id ORDER BY COUNT(*) DESC");
    foreach( $list as $k => $item ){
        $robot = new Robot( 0 );
        $geodata = $db->fetch( " SELECT * FROM " . $sys_tables['geodata'] ." WHERE id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_street = ? ",
            $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']
        );
        if( empty( $geodata ) ) {
            $geodata = $robot->getGeodataDdata( $robot->fullAddress( $item ) );    
            if( !empty( $geodata ) ) 
                $db->querys("UPDATE " . $sys_tables[$estate_type] ." 
                            SET id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ? WHERE group_id = ?", 
                            $geodata['id_region'], $geodata['id_area'], $geodata['id_city'], $geodata['id_place'], $geodata['id_street'], $item['group_id'] );
            
        }
        if( !empty( $geodata ) ) {
            $item['txt_addr'] =  $robot->fullAddress( $item );
            if( !empty( $item['txt_addr'] ) ) {
                list( $lat, $lng ) = $robot->getCoords( $item );
                if( $lat > 1 && $lng > 1 ) $db->querys(" UPDATE " . $sys_tables[$estate_type] ." SET lat = ?, lng = ? WHERE group_id = ?", $lat, $lng, $item['group_id'] );
            }
        }
        echo '2.' . $estate_type . ' - ' . $k . "\n";
    }
}

// 3. запись адреса
foreach($estates as $estate_type){
    $list = $db->fetchall("SELECT COUNT(*), lat, lng, id_region, id_area, id_city, id_place, id_street, house, corp, txt_addr FROM ".$sys_tables[$estate_type]." WHERE house > 0 AND group_id > 0 AND lat > 1 AND lng > 1  GROUP BY group_id ORDER BY COUNT(*) DESC");
    foreach( $list as $k => $item ){
        $db->querys(" INSERT IGNORE INTO " . $sys_tables['geodata_spb_addresses'] ." SET 
                     id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ?, house = ?, corp = ?, lat = ?, lng = ?, address = ? ",
                     $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street'], $item['house'], $item['corp'], $item['lat'], $item['lng'], $item['txt_addr']
        );
        echo '3.' . $estate_type . ' - ' . $k . "\n";
    }
}
/*

// 4. Переопределение адреса для повторяющихся координат
$list = $db->fetchall( " SELECT COUNT(*), lat, lng FROM " . $sys_tables['geodata_spb_addresses'] ." GROUP BY lat, lng HAVING COUNT(*) > 2 ORDER BY COUNT(*) DESC ");
foreach( $list as $k => $coords ){
   $addresses = $db->fetchall( " SELECT * FROM " . $sys_tables['geodata_spb_addresses'] ." WHERE lat = ? AND lng = ? ", false, $coords['lat'], $coords['lng'] );     
   foreach( $addresses as $a => $address ){
        $estate_list = $db->fetchall( " SELECT * FROM " . $sys_tables['live'] ." 
                                        WHERE id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_street = ?", false,
                                        $address['id_region'], $address['id_area'], $address['id_city'], $address['id_place'], $address['id_street']
        );
        foreach( $estate_list as $e => $estate ){    
            $geodata = $robot->getGeodataDdata( $robot->fullAddress( $estate ) );    
            if( !empty( $geodata ) ) {
                if( !empty( $geodata['id_street'] ) && $geodata['id_street'] != $address['id_street'] ) {
                    $db->querys( " UPDATE " . $sys_tables['live'] ." SET id_street = ?, lat='0.000000',lng='0.000000' WHERE id = ?", $geodata['id_street'], $estate['id'] );
                }
                
            }
        }
        $db->querys( " DELETE FROM " . $sys_tables['geodata_spb_addresses'] ." WHERE lat= ? AND lng = ? ", $address['lat'], $address['lng'] );
        $db->querys( " UPDATE " . $sys_tables['live'] ." SET lat='0.000000',lng='0.000000' WHERE lat= ? AND lng = ? ", $address['lat'], $address['lng'] );
   }
}  

die();

// 5. обновление координат для группы 
foreach($estates as $estate_type){

    $list = $db->fetchall("SELECT COUNT(*), id, lat, lng, group_id, id_region, id_area, id_city, id_place, id_street, house, corp, txt_addr FROM ".$sys_tables[$estate_type]." WHERE lat < 1  GROUP BY group_id ORDER BY COUNT(*) DESC");
    foreach( $list as $k => $item ){
        $robot = new Robot( 0 );
        $geodata = $db->fetch( " SELECT * FROM " . $sys_tables['geodata'] ." WHERE id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_street = ? ",
            $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']
        );
        if( empty( $geodata ) ) {
            $geodata = $robot->getGeodataDdata( $robot->fullAddress( $item ) );    
            if( !empty( $geodata ) ) 
                $db->querys("UPDATE " . $sys_tables[$estate_type] ." 
                            SET id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ? WHERE group_id = ?", 
                            $geodata['id_region'], $geodata['id_area'], $geodata['id_city'], $geodata['id_place'], $geodata['id_street'], $item['group_id'] );
            
        }
        if( !empty( $geodata ) ) {
            $item['txt_addr'] =  $robot->fullAddress( $item );
            list( $lat, $lng ) = $robot->getCoords( $item['txt_addr'] );
            $db->querys(" UPDATE " . $sys_tables[$estate_type] ." SET lat = ?, lng = ? WHERE group_id = ?", $lat, $lng, $item['group_id'] );
        }
    }
} 

// 6. запись адреса
foreach($estates as $estate_type){
    $list = $db->fetchall("SELECT COUNT(*), lat, lng, id_region, id_area, id_city, id_place, id_street, house, corp, txt_addr FROM ".$sys_tables[$estate_type]." WHERE house > 0 AND group_id > 0 AND lat > 1 AND lng > 1  GROUP BY group_id ORDER BY COUNT(*) DESC");
    foreach( $list as $k => $item ){
        $db->querys(" INSERT IGNORE INTO " . $sys_tables['geodata_spb_addresses'] ." SET 
                     id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ?, house = ?, corp = ?, lat = ?, lng = ?, address = ? ",
                     $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street'], $item['house'], $item['corp'], $item['lat'], $item['lng'], $item['txt_addr']
        );
        
    }
}
*/
?>
