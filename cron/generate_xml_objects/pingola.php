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
include('cron/class.xml.generate.php');     // Photos (работа с графикой)
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$img_folder = Config::Get('img_folders/live');

$xml = new DOMDocument('1.0','UTF-8');

$xmlUrlset = $xml->appendChild($xml->createElement('realty-feed'));
$xmlUrlset->setAttribute('xmlns','http://webmaster.yandex.ru/schemas/feed/realty/2010-06');

$xmlgenerationdate = $xmlUrlset->appendChild($xml->createElement('generation-date'));
$xmlgenerationdate -> appendChild($xml->createTextNode( date('c') ));
$list = array();
//Init xml-making
$xmlItem = new generateXml;

/*
* ЖИЛАЯ НЕДВИЖИМОСТЬ


$sql = "SELECT 
             ".$sys_tables['live'].".*, 
            ".$sys_tables['live'].".date_change + INTERVAL 30 DAY as date_end,
            ".$sys_tables['districts'].".title as district_title, 
            ".$sys_tables['type_objects_live'].".title as type_object_title, 
            ".$sys_tables['building_types'].".title as building_type_title, 
            ".$sys_tables['elevators'].".title as elevator_title, 
            ".$sys_tables['balcons'].".title as balcon_title, 
            ".$sys_tables['toilets'].".title as toilet_title, 
            ".$sys_tables['subways'].".title as subway_title, 
            ".$sys_tables['users'].".email as 'agent_email', 
            ".$sys_tables['users'].".skype as 'skype', 
            IF(".$sys_tables['agencies'].".id<2,'владелец',".$sys_tables['agencies'].".title) as 'agency_title', 
            ".$sys_tables['agencies'].".url as 'agency_url', 
            ".$sys_tables['agencies'].".phones as 'agency_phones', 
            ".$sys_tables['agencies'].".phone_1 as 'agency_phone_1', 
            ".$sys_tables['agencies'].".phone_2 as 'agency_phone_2', 
            ".$sys_tables['agencies'].".phone_3 as 'agency_phone_3', 
            ".$sys_tables['agencies'].".fax as 'agency_fax'
        FROM ".$sys_tables['live']." 
        LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables['live'].".id_district 
        LEFT JOIN ".$sys_tables['type_objects_live']." ON ".$sys_tables['type_objects_live'].".id = ".$sys_tables['live'].".id_type_object 
        LEFT JOIN ".$sys_tables['building_types']." ON ".$sys_tables['building_types'].".id = ".$sys_tables['live'].".id_building_type
        LEFT JOIN ".$sys_tables['elevators']." ON ".$sys_tables['elevators'].".id = ".$sys_tables['live'].".id_elevator
        LEFT JOIN ".$sys_tables['balcons']." ON ".$sys_tables['balcons'].".id = ".$sys_tables['live'].".id_balcon
        LEFT JOIN ".$sys_tables['toilets']." ON ".$sys_tables['toilets'].".id = ".$sys_tables['live'].".id_toilet
        LEFT JOIN ".$sys_tables['subways']." ON ".$sys_tables['subways'].".id = ".$sys_tables['live'].".id_subway 
        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['live'].".id_user 
        LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency  
        WHERE 
            (((".$sys_tables['live'].".`cost` BETWEEN 500000 AND 650000000) AND ".$sys_tables['live'].".rent=2) OR ((".$sys_tables['live'].".`cost` BETWEEN 5000 AND 95000) AND ".$sys_tables['live'].".rent=1))
            AND ".$sys_tables['live'].".`published` = 1
            AND ( (".$sys_tables['live'].".`rooms_sale` > 0) AND (".$sys_tables['live'].".`id_type_object` = 1 OR ".$sys_tables['live'].".`id_type_object` = 2) )
";
$list = $db->fetchall($sql);


foreach($list as $k=>$item){
    $xmlItem->append();
    $xmlItem->attr("internal-id",$item['id']);

        $xmlItem->append('type', $item['rent'] == 1 ? 'аренда' : 'продажа', 1); // * обязательное поле
        $xmlItem->append('property-type', 'жилая',1); // * обязательное поле
    
        $xmlItem->append('category', $item['type_object_title'],1); // * обязательное поле

        $xmlItem->append('url', 'https://www.bsn.ru/live/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/?from=pngl',1); // * обязательное поле
        $xmlItem->append('creation-date', date('c',strtotime($item['date_in'])),1); // * обязательное поле
        $xmlItem->append('last-update-date', date('c',strtotime($item['date_change'])),1); 
        $xmlItem->append('expire-date', date('c',strtotime($item['date_end'])),1); 
        $xmlItem->append('payed-adv', $item['status']>2 ? '+' : '-',1); 
        $xmlItem->append('manually-added', $item['info_source'] == 1 ? '+' : '-',1); 

    
    
        $area = $city = $place = $street = false;
        //определение района области
        if(!empty($item['id_area'])){
            $area = $db->fetch("SELECT offname as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=?",
                                  2, $item['id_region'], $item['id_area']);
        }
        //определение города
        if(!empty($item['id_city'])){
            $city = $db->fetch("SELECT offname as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=?",
                                  3, $item['id_region'], $item['id_area'], $item['id_city']);
        }
        //определение части города
        if(!empty($item['id_place'])){
            $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                  4, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place']);
        }
        //определение названия улицы
        if(!empty($item['id_street'])){
            $street = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                  5, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']);
        }
        $xmlItem->append('location', '',1); // * обязательное поле
            $xmlItem->append('country', 'Россия',2); // * обязательное поле
            if($item['id_region'] == 78){
                $xmlItem->append('locality-name','Санкт-Петербург',2);
                if(!empty($item['district_title'])) $xmlItem->append('sub-locality-name',$item['district_title'],2);

                if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : '') ,2);
                elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],2);
                
            }
            elseif($item['id_region'] == 47){
                $xmlItem->append('region', 'Ленинградская область',2); 
                if(!empty($area['title'])) $xmlItem->append('district',$area['title'].' район',2);
                if(!empty($city['title'])) $xmlItem->append('locality-name',$city['title'],2);

                if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', '.$item['house'].', '.$item['corp'] : ', '.$item['house'] ) : '') ,2);
                elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],2);
            }
            if($item['lat']>0 && $item['lng']>0){
                $xmlItem->append('latitude', $item['lat'],2);
                $xmlItem->append('longitude', $item['lng'],2);
            }
            if(!empty($item['id_subway'])) {
                $xmlItem->append('metro','',2);
                $xmlItem->append('name',$item['subway_title'],3);
                if(!empty($item['id_way_type'])){
                    if($item['id_way_type']==2) $xmlItem->append('time-on-foot',$item['id_way_type'],3);
                    if($item['id_way_type']==3) $xmlItem->append('time-on-transport',$item['id_way_type'],3);
                }
            }

            
        $xmlItem->append('sales-agent', '', 1); // * обязательное поле
            if(empty($item['agency_title']) || $item['agency_title'] == 'владелец'){
                $xmlItem->append('phone', $item['seller_phone'],2); // * обязательное поле
                if($item['seller_name']!='')  $xmlItem->append('name', $item['seller_name'],2); 
                $xmlItem->append('category', 'владелец',2);
            }
            elseif(!empty($item['agency_title'])){
                if($item['agency_phones']!='')  $xmlItem->append('phone', $item['agency_phones'],2); // * обязательное поле
                if($item['seller_name']!='')  $xmlItem->append('name', $item['seller_name'],2); 
                $xmlItem->append('category', 'агентство',2);
                $xmlItem->append('organization', $item['agency_title'],2);
                $xmlItem->append('agency-id', $item['id_user'],2);
                if($item['agency_url']!='') $xmlItem->append('url', $item['agency_url'],2);
                if($item['agent_email']!='') $xmlItem->append('email', $item['agent_email'],2);
            }
    
        $xmlItem->append('price', '', 1); // * обязательное поле
            $xmlItem->append('value', $item['cost'],2); // * обязательное поле
            $xmlItem->append('currency', 'RUB',2); // * обязательное поле
            if($item['rent']==1) $xmlItem->append('period', $item['by_the_day']==1 ? 'день' : 'месяц',2); 
    
         $res_image = $db->query("SELECT name, LEFT (".$sys_tables['live_photos'].".`name`,2) as `subfolder` 
                                 FROM ".$sys_tables['live_photos']." 
                                 WHERE id_parent = ?",$item['id']);
        while($item_image = $res_image->fetch_array()) {
            if(file_exists(ROOT_PATH."/".Config::Get('img_folders/live')."/big/".$item_image['subfolder']."/".$item_image['name']))
                $xmlItem->append('image', "https://www.bsn.ru/".Config::Get('img_folders/live')."/big/".$item_image['subfolder']."/".$item_image['name'],1);
        }
        
        if(!empty($item['notes']))  $xmlItem->append('description', Convert::StripText($item['notes']), 1);
        
        if((int)$item['square_full']>0){
            $xmlItem->append('area', '',1); // общая площадь
              $xmlItem->append('value', (int)$item['square_full'],2); 
              $xmlItem->append('unit', 'кв. м.',2); 
        }
    
        if((int)$item['square_live']>0){
            $xmlItem->append('living-space', '',1); // жилая площадь (при продаже комнаты — площадь комнаты)
                $xmlItem->append('value', (int)$item['square_live'],2);   
                $xmlItem->append('unit', 'кв. м.',2);   
        }
        
        if((int)$item['square_kitchen']>0){
            $xmlItem->append('kitchen-space', '',1); // площадь кухни
                $xmlItem->append('value', (int)$item['square_kitchen'],2);   
                $xmlItem->append('unit', 'кв. м.',2);   
        }
        
        //Поля для жилой недвижимости
        $xmlItem->append('rooms', $item['rooms_total'],1); // * обязательное поле ДЛЯ ЖИЛОЙ НЕДВИЖИМОСТИ
        if($item['id_type_object']==2) $xmlItem->append('rooms-offered', $item['rooms_sale'],1);

        if($item['phone'] == 1) $xmlItem->append('phone', '+',1);
        if($item['furniture'] == 1) $xmlItem->append('room-furniture', '+',1);
        if($item['wash_mash'] == 1) $xmlItem->append('washing-machine', '+',1); 
        if($item['refrigerator'] == 1) $xmlItem->append('refrigerator',  '+' ,1); 
        if($item['id_balcon']>1 && $item['id_balcon']<6) $xmlItem->append('balcony', $item['balcon_title'],1);
        if($item['id_toilet']>2  && $item['id_toilet']<6) $xmlItem->append('bathroom-unit', $item['toilet_title'],1);
        if(!empty($item['level'])) $xmlItem->append('floor', $item['level'],1);
        if(!empty($item['level_total'])) $xmlItem->append('floors-total', $item['level_total'],1);
        if($item['id_elevator']!=1 && $item['id_elevator']!=5) $xmlItem->append('lift',  '+' ,1); 
        if(in_array($item['id_building_type'],array(9,10,17))) $xmlItem->append('building-type', $item['building_type_title'],1);
         if(!empty($item['ceiling_height']) && $item['ceiling_height']>0) $xmlItem->append('ceiling-height', $item['ceiling_height'],1);
        $xmlItem->append('is-elite', $item['elite']==1?'+':'-',1);
        
}
$log['live'] = count($list);
/*
* СТРОЯЩАЯСЯ НЕДВИЖИМОСТЬ
*/

$sql = "SELECT  
            ".$sys_tables['build'].".*, 
            ".$sys_tables['build'].".date_change + INTERVAL 30 DAY as date_end,
            ".$sys_tables['districts'].".title as district_title, 
            ".$sys_tables['building_types'].".title as building_type_title, 
            ".$sys_tables['build_complete'].".year as build_complete_year, 
            ".$sys_tables['build_complete'].".decade as build_complete_decade, 
            ".$sys_tables['elevators'].".title as elevator_title, 
            ".$sys_tables['balcons'].".title as balcon_title, 
            ".$sys_tables['toilets'].".title as toilet_title, 
            ".$sys_tables['subways'].".title as subway_title, 
            ".$sys_tables['users'].".email as 'agent_email', 
            ".$sys_tables['users'].".skype as 'skype', 
            IF(".$sys_tables['agencies'].".id<2,'владелец',".$sys_tables['agencies'].".title) as 'agency_title', 
            ".$sys_tables['agencies'].".url as 'agency_url', 
            ".$sys_tables['agencies'].".phones as 'agency_phones', 
            ".$sys_tables['agencies'].".phone_1 as 'agency_phone_1', 
            ".$sys_tables['agencies'].".phone_2 as 'agency_phone_2', 
            ".$sys_tables['agencies'].".phone_3 as 'agency_phone_3', 
            ".$sys_tables['agencies'].".fax as 'agency_fax'
        FROM ".$sys_tables['build']." 
        LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables['build'].".id_district 
        LEFT JOIN ".$sys_tables['building_types']." ON ".$sys_tables['building_types'].".id = ".$sys_tables['build'].".id_building_type
        LEFT JOIN ".$sys_tables['build_complete']." ON ".$sys_tables['build_complete'].".id = ".$sys_tables['build'].".id_build_complete
        LEFT JOIN ".$sys_tables['elevators']." ON ".$sys_tables['elevators'].".id = ".$sys_tables['build'].".id_elevator
        LEFT JOIN ".$sys_tables['balcons']." ON ".$sys_tables['balcons'].".id = ".$sys_tables['build'].".id_balcon
        LEFT JOIN ".$sys_tables['toilets']." ON ".$sys_tables['toilets'].".id = ".$sys_tables['build'].".id_toilet
        LEFT JOIN ".$sys_tables['subways']." ON ".$sys_tables['subways'].".id = ".$sys_tables['build'].".id_subway 
        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['build'].".id_user 
        LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
        WHERE 
            (".$sys_tables['build'].".`cost` BETWEEN 500000 AND 650000000)
            AND ".$sys_tables['build'].".`published` = 1
            AND (".$sys_tables['build'].".`rooms_sale` > 0)
        ORDER BY ".$sys_tables['build'].".`id` ASC
";
$list = $db->fetchall($sql) or die($db->error);

foreach($list as $k=>$item){
    $xmlItem->append();
    $xmlItem->attr("internal-id",$item['id']);

        $xmlItem->append('type', $item['rent'] == 1 ? 'аренда' : 'продажа', 1); // * обязательное поле
        $xmlItem->append('property-type', 'жилая',1); // * обязательное поле
    
        $xmlItem->append('category', 'квартира',1); // * обязательное поле

        $xmlItem->append('url', 'https://www.bsn.ru/build/sell/'.$item['id'].'/',1); // * обязательное поле
        $xmlItem->append('creation-date', date('c',strtotime($item['date_in'])),1); // * обязательное поле
        $xmlItem->append('last-update-date', date('c',strtotime($item['date_change'])),1); 
        $xmlItem->append('expire-date', date('c',strtotime($item['date_end'])),1); 
        $xmlItem->append('payed-adv', $item['status']>2 ? '+' : '-',1); 
        $xmlItem->append('manually-added', $item['info_source'] == 1 ? '+' : '-',1); 

    
        $area = $city = $place = $street = false;
        //определение района области
        if(!empty($item['id_area'])){
            $area = $db->fetch("SELECT offname as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=?",
                                  2, $item['id_region'], $item['id_area']);
        }
        //определение города
        if(!empty($item['id_city'])){
            $city = $db->fetch("SELECT offname as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=?",
                                  3, $item['id_region'], $item['id_area'], $item['id_city']);
        }
        //определение части города
        if(!empty($item['id_place'])){
            $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                  4, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place']);
        }
        //определение названия улицы
        if(!empty($item['id_street'])){
            $street = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                  5, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']);
        }
        $xmlItem->append('location', '',1); // * обязательное поле
            $xmlItem->append('country', 'Россия',2); // * обязательное поле
            if($item['id_region'] == 78){
                $xmlItem->append('locality-name','Санкт-Петербург',2);
                if(!empty($item['district_title'])) $xmlItem->append('sub-locality-name',$item['district_title'],2);

                if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : '') ,2);
                elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],2);
                
            }
            elseif($item['id_region'] == 47){
                $xmlItem->append('region', 'Ленинградская область',2); 
                if(!empty($area['title'])) $xmlItem->append('district',$area['title'].' район',2);
                if(!empty($city['title'])) $xmlItem->append('locality-name',$city['title'],2);

                if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', '.$item['house'].', '.$item['corp'] : ', '.$item['house'] ) : '') ,2);
                elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],2);
            }
            if($item['lat']>0 && $item['lng']>0){
                $xmlItem->append('latitude', $item['lat'],2);
                $xmlItem->append('longitude', $item['lng'],2);
            }
            if(!empty($item['id_subway'])) {
                $xmlItem->append('metro','',2);
                $xmlItem->append('name',$item['subway_title'],3);
                if(!empty($item['id_way_type'])){
                    if($item['id_way_type']==2) $xmlItem->append('time-on-foot',$item['id_way_type'],3);
                    if($item['id_way_type']==3) $xmlItem->append('time-on-transport',$item['id_way_type'],3);
                }
            }

            
        $xmlItem->append('sales-agent', '', 1); // * обязательное поле
            if(empty($item['agency_title']) || $item['agency_title'] == 'владелец'){
                $xmlItem->append('phone', $item['seller_phone'],2); // * обязательное поле
                if($item['seller_name']!='')  $xmlItem->append('name', $item['seller_name'],2); 
                $xmlItem->append('category', 'владелец',2);
            }
            elseif(!empty($item['agency_title'])){
                if($item['agency_phones']!='')  $xmlItem->append('phone', $item['agency_phones'],2); // * обязательное поле
                if($item['seller_name']!='')  $xmlItem->append('name', $item['seller_name'],2); 
                $xmlItem->append('category', 'агентство',2);
                $xmlItem->append('organization', $item['agency_title'],2);
                $xmlItem->append('agency-id', $item['id_user'],2);
                if($item['agency_url']!='') $xmlItem->append('url', $item['agency_url'],2);
                if($item['agent_email']!='') $xmlItem->append('email', $item['agent_email'],2);
            }
    
        $xmlItem->append('price', '', 1); // * обязательное поле
            $xmlItem->append('value', $item['cost'],2); // * обязательное поле
            $xmlItem->append('currency', 'RUB',2); // * обязательное поле
    
         $res_image = $db->query("SELECT name, LEFT (".$sys_tables['build_photos'].".`name`,2) as `subfolder` 
                                 FROM ".$sys_tables['build_photos']." 
                                 WHERE id_parent = ?",$item['id']);
        while($item_image = $res_image->fetch_array()) {
            if(file_exists(ROOT_PATH."/".Config::Get('img_folders/build')."/big/".$item_image['subfolder']."/".$item_image['name']))
                $xmlItem->append('image', "https://www.bsn.ru/".Config::Get('img_folders/build')."/big/".$item_image['subfolder']."/".$item_image['name'],1);
        }
        
        if(!empty($item['notes']))  $xmlItem->append('description', Convert::StripText($item['notes']), 1);
        
        if((int)$item['square_full']>0){
            $xmlItem->append('area', '',1); // общая площадь
              $xmlItem->append('value', (int)$item['square_full'],2); 
              $xmlItem->append('unit', 'кв. м.',2); 
        }
    
        if((int)$item['square_live']>0){
            $xmlItem->append('living-space', '',1); // жилая площадь (при продаже комнаты — площадь комнаты)
                $xmlItem->append('value', (int)$item['square_live'],2);   
                $xmlItem->append('unit', 'кв. м.',2);   
        }
        
        if((int)$item['square_kitchen']>0){
            $xmlItem->append('kitchen-space', '',1); // площадь кухни
                $xmlItem->append('value', (int)$item['square_kitchen'],2);   
                $xmlItem->append('unit', 'кв. м.',2);   
        }
        
        //Поля для строящейся недвижимости
        $xmlItem->append('new-flat', '+',1);
        $xmlItem->append('rooms', $item['rooms_sale'],1); // * обязательное поле ДЛЯ строящейся НЕДВИЖИМОСТИ

        if($item['id_balcon']>1 && $item['id_balcon']<6) $xmlItem->append('balcony', $item['balcon_title'],1);
        if($item['id_toilet']>2  && $item['id_toilet']<6) $xmlItem->append('bathroom-unit', $item['toilet_title'],1);
        if(!empty($item['level'])) $xmlItem->append('floor', $item['level'],1);
        if(!empty($item['level_total'])) $xmlItem->append('floors-total', $item['level_total'],1);
        if($item['id_elevator']!=1 && $item['id_elevator']!=5) $xmlItem->append('lift', '+',1); 
        if(in_array($item['id_building_type'],array(9,10,17))) $xmlItem->append('building-type', $item['building_type_title'],1);
        if(!empty($item['ceiling_height']) && $item['ceiling_height']>0) $xmlItem->append('ceiling-height', $item['ceiling_height'],1);
        $xmlItem->append('is-elite', $item['elite']==1?'+':'-',1);
        if($item['id_build_complete']==4) $xmlItem->append('building-state', 'hand-over',1);
        elseif($item['id_build_complete']==5) $xmlItem->append('building-state', 'built',1);
        elseif($item['id_build_complete']>5) {
            $xmlItem->append('building-state', 'unfinished',1);
            $xmlItem->append('built-year', $item['build_complete_year'],1);
            $xmlItem->append('ready-quarter', $item['build_complete_decade'],1);
        }
}
$log['build'] = count($list);

    
/*
* ЗАГОРОДНАЯ НЕДВИЖИМОСТЬ
*/
$sql = "SELECT ".$sys_tables['country'].".*,
                ".$sys_tables['country'].".date_change + INTERVAL 30 DAY as date_end, 
                ".$sys_tables['type_objects_country'].".`title` as 'type_object_title', 
                ".$sys_tables['users'].".`email` as 'agent_email',
                ".$sys_tables['subways'].".title as subway_title,  
                IF(".$sys_tables['agencies'].".id<2,'владелец',".$sys_tables['agencies'].".title) as 'agency_title', 
                ".$sys_tables['agencies'].".url as 'agency_url', 
                ".$sys_tables['agencies'].".phones as 'agency_phones', 
                ".$sys_tables['agencies'].".phone_1 as 'agency_phone_1', 
                ".$sys_tables['agencies'].".phone_2 as 'agency_phone_2', 
                ".$sys_tables['agencies'].".phone_3 as 'agency_phone_3', 
                ".$sys_tables['agencies'].".fax as 'agency_fax',
                ".$sys_tables['country_photos'].".`name` as 'photo_name', 
                LEFT (".$sys_tables['country_photos'].".`name`,2) as `photo_subfolder` 
        FROM ".$sys_tables['country']." 
        LEFT JOIN ".$sys_tables['type_objects_country']." ON ".$sys_tables['type_objects_country'].".`id` = ".$sys_tables['country'].".`id_type_object` 
        LEFT JOIN ".$sys_tables['subways']." ON ".$sys_tables['subways'].".`id` = ".$sys_tables['country'].".`id_subway` 
        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".`id` = ".$sys_tables['country'].".`id_user` 
        LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".`id` = ".$sys_tables['users'].".`id_agency` 
        LEFT JOIN ".$sys_tables['country_photos']." ON ".$sys_tables['country_photos'].".`id` = ".$sys_tables['country'].".`id_main_photo` 
        WHERE 
            ((".$sys_tables['country'].".`cost` BETWEEN 500000 AND 650000000 AND ".$sys_tables['country'].".rent=2) OR (".$sys_tables['country'].".`cost` BETWEEN 5000 AND 95000 AND ".$sys_tables['country'].".rent=1))
            AND ".$sys_tables['country'].".`published` = 1
        ORDER BY ".$sys_tables['country'].".`id` DESC 
";
$list = $db->fetchall($sql) or die($db->error);
foreach($list as $k=>$item){
    $xmlItem->append();
    $xmlItem->attr("internal-id",$item['id']);

        $xmlItem->append('type', $item['rent'] == 1 ? 'аренда' : 'продажа', 1); // * обязательное поле
        $xmlItem->append('property-type', 'жилая',1); // * обязательное поле

        $xmlItem->append('category', $item['type_object_title'],1); // * обязательное поле
        
        if((int)$item['rooms']>0) $xmlItem->append('rooms', $item['rooms'],1); // * обязательное поле ДЛЯ ЖИЛОЙ НЕДВИЖИМОСТИ

        $xmlItem->append('url', 'https://www.bsn.ru/country/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1); // * обязательное поле
        $xmlItem->append('creation-date', date('c',strtotime($item['date_in'])),1); // * обязательное поле
        $xmlItem->append('last-update-date', date('c',strtotime($item['date_change'])),1); 
        $xmlItem->append('expire-date', date('c',strtotime($item['date_end'])),1); 
        $xmlItem->append('payed-adv', $item['status']>2 ? '+' : '-',1); 
        $xmlItem->append('manually-added', $item['info_source'] == 1 ? '+' : '-',1); 
        
        if(!empty($item['photo_name'])) $xmlItem->append('image', 'https://www.bsn.ru/'.$img_folder.'/sm/'.$item['photo_subfolder'].'/'.$item['photo_name'],1);
    

        $area = $city = $place = $street = false;
        //определение района области
        if(!empty($item['id_area'])){
            $area = $db->fetch("SELECT offname as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=?",
                                  2, $item['id_region'], $item['id_area']);
        }
        //определение города
        if(!empty($item['id_city'])){
            $city = $db->fetch("SELECT offname as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=?",
                                  3, $item['id_region'], $item['id_area'], $item['id_city']);
        }
        //определение части города
        if(!empty($item['id_place'])){
            $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                  4, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place']);
        }
        //определение названия улицы
        if(!empty($item['id_street'])){
            $street = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                  5, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']);
        }
        $xmlItem->append('location', '',1); // * обязательное поле
            $xmlItem->append('country', 'Россия',2); // * обязательное поле
            if($item['id_region'] == 78){
                $xmlItem->append('locality-name','Санкт-Петербург',2);
                if(!empty($item['district_title'])) $xmlItem->append('sub-locality-name',$item['district_title'],2);

                if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : '') ,2);
                elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],2);
                
            }
            elseif($item['id_region'] == 47){
                $xmlItem->append('region', 'Ленинградская область',2); 
                if(!empty($area['title'])) $xmlItem->append('district',$area['title'].' район',2);
                if(!empty($city['title'])) $xmlItem->append('locality-name',$city['title'],2);

                if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', '.$item['house'].', '.$item['corp'] : ', '.$item['house'] ) : '') ,2);
                elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],2);
            }
            if($item['lat']>0 && $item['lng']>0){
                $xmlItem->append('latitude', $item['lat'],2);
                $xmlItem->append('longitude', $item['lng'],2);
            }
            if(!empty($item['id_subway'])) {
                $xmlItem->append('metro','',2);
                $xmlItem->append('name',$item['subway_title'],3);
                if(!empty($item['id_way_type'])){
                    if($item['id_way_type']==2) $xmlItem->append('time-on-foot',$item['id_way_type'],3);
                    if($item['id_way_type']==3) $xmlItem->append('time-on-transport',$item['id_way_type'],3);
                }
            }

            
        $xmlItem->append('sales-agent', '', 1); // * обязательное поле
            if(empty($item['agency_title']) || $item['agency_title'] == 'владелец'){
                $xmlItem->append('phone', $item['seller_phone'],2); // * обязательное поле
                if($item['seller_name']!='')  $xmlItem->append('name', $item['seller_name'],2); 
                $xmlItem->append('category', 'владелец',2);
            }
            elseif(!empty($item['agency_title'])){
                if($item['agency_phones']!='')  $xmlItem->append('phone', $item['agency_phones'],2); // * обязательное поле
                if($item['seller_name']!='')  $xmlItem->append('name', $item['seller_name'],2); 
                $xmlItem->append('category', 'агентство',2);
                $xmlItem->append('organization', $item['agency_title'],2);
                $xmlItem->append('agency-id', $item['id_user'],2);
                if($item['agency_url']!='') $xmlItem->append('url', $item['agency_url'],2);
                if($item['agent_email']!='') $xmlItem->append('email', $item['agent_email'],2);
            }
    
        $xmlItem->append('price', '', 1); // * обязательное поле
            $xmlItem->append('value', $item['cost'],2); // * обязательное поле
            $xmlItem->append('currency', 'RUB',2); // * обязательное поле
            if($item['rent']==1) $xmlItem->append('period', $item['by_the_day']==1 ? 'день' : 'месяц',2); 
            
    
        
        if((int)$item['square_full']>0){
            $xmlItem->append('area', '',1); // общая площадь
              $xmlItem->append('value', (int)$item['square_full'],2); 
              $xmlItem->append('unit', 'кв. м.',2); 
        }
    
        if((int)$item['square_live']>0){
            $xmlItem->append('living-space', '',1); // жилая площадь (при продаже комнаты — площадь комнаты)
                $xmlItem->append('value', (int)$item['square_live'],2);   
                $xmlItem->append('unit', 'кв. м.',2);   
        }
        
        if((int)$item['square_ground']>0){
            $xmlItem->append('lot-area', '',1); // площадь кухни
                $xmlItem->append('value', (int)$item['square_ground'],2);    
                $xmlItem->append('unit', 'сот.',2); 
        } 
        if(!empty($item['notes']))  $xmlItem->append('description', Convert::StripText($item['notes']), 1);
    /* ЗАГОРОДНАЯ НЕДВИЖИМОСТЬ */
        if($item['id_toilet']==2 || $item['id_toilet']==3 || $item['id_toilet']==6 || $item['id_toilet']==7 || $item['id_toilet']==9) $xmlItem->append('toilet', 'в доме',1);
        elseif($item['id_toilet']==8)$xmlItem->append('toilet', 'на улице',1);
        
        if($item['id_bathroom']==4 || $item['id_bathroom']==3) $xmlItem->append('sauna', '+',1);
        
        if($item['id_heating']!=1 && $item['id_heating']!=3) $xmlItem->append('heating-supply', '+',1);
        
        if($item['id_toilet']==4) $xmlItem->append('sewerage-supply', '+',1);
        
        if($item['id_electricity']==2 || $item['id_electricity']==4) $xmlItem->append('electricity-supply', '+',1);
        
        if($item['id_gas']==2 || $item['id_gas']==4) $xmlItem->append('gas-supply', '+',1);              
} 
$log['country'] = count($list);
    
$filename = ROOT_PATH.'/bsn_to_pingola.xml';
$xml->formatOutput = true;
$xml->save($filename);
if(file_exists($filename.".gz")) unlink($filename.".gz");

exec("gzip -rv ".$filename);
exec("chmod 777 ".$filename.".gz");

unlink($filename);
if(!empty($log)){
    $mailer = new EMailer('mail');
    // перевод письма в кодировку мейлера
    $html = "Выгрузилось: <br />
    - жилая: ".$log['live']."<br />
    - стройка: ".$log['build']."<br />
    - загородка: ".$log['country'];
    $html = iconv('UTF-8', $mailer->CharSet, $html);
    // параметры письма
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Выгрузка объектов в пинголу. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
    $mailer->AddAddress('web@bsn.ru');
    $mailer->From = 'no-reply@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Выгрузка объектов в пинголу');
    // попытка отправить
    //$mailer->Send();        
    echo $html;
}
?>
