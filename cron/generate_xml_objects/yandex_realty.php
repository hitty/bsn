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
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');


define('__XMLPATH__',ROOT_PATH.'/yandex_realty.xml');
define('__URL__','https://www.bsn.ru/');


$db->select_db('estate');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = '".Config::$values['mysql']['lc_time_names']."';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$log = array();
$xml = new DOMDocument('1.0','UTF-8');


$xmlUrlset = $xml->appendChild($xml->createElement('realty-feed'));
$xmlUrlset->setAttribute('xmlns','http://webmaster.yandex.ru/schemas/feed/realty/2010-06');

$xmlgenerationdate = $xmlUrlset->appendChild($xml->createElement('generation-date'));
$xmlgenerationdate-> appendChild($xml->createTextNode( date('c') ));

//Init xml-making
$xmlItem = new xmlGenerate;

$live_count=0; $build_count=0; $country_count=0;

/*
* ЖИЛАЯ НЕДВИЖИМОСТЬ
*/       
$base_memory_usage = memory_get_usage();

//обшее условие для жилой
$where =   "(
                (
                    (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) BETWEEN 50000 AND 250000 AND ".$sys_tables['live'].".id_region = 78 AND ".$sys_tables['live'].".id_district IN (2,3,4,5,6,7,8,10,11,12,13,15,16,27,29,38,43,53)
                ) OR (
                    (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) BETWEEN 30000 AND 150000 AND ".$sys_tables['live'].".id_region = 47 AND (".$sys_tables['live'].".id_area>0 OR ".$sys_tables['live'].".id_city>0)
                )
            ) AND 
            ".$sys_tables['live'].".rooms_total >=1  AND
            ".$sys_tables['live'].".cost>0 AND
            ".$sys_tables['live'].".square_full>0 AND
            ".$sys_tables['live'].".published = 1 AND
            ".$sys_tables['live'].".id_type_object = 1 AND
            DATEDIFF(CURDATE(),".$sys_tables['live'].".date_change) < 28 AND
            DATEDIFF(CURDATE(),".$sys_tables['live'].".date_in) < 90 AND
            ".$sys_tables['live'].".rent = 2
            AND ".$sys_tables['live'].".weight > 20
            ";

//получение 10% с аименшей и наибольшей ценой
$items = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['live']." WHERE ".$where);
$total = $items['cnt'];
unset($items);
$limits = $db->fetchall("SELECT cost FROM (
                            (SELECT cost FROM ".$sys_tables['live']." WHERE ".$where." ORDER BY cost DESC LIMIT ".(int)($total*0.05).", 1)
                            UNION 
                            (SELECT cost FROM ".$sys_tables['live']." WHERE ".$where." ORDER BY cost DESC LIMIT ".(int)($total*0.95).", 1)
                         ) as a
");
$where .= " AND ".$sys_tables['live'].".cost BETWEEN ".$limits[1]['cost']." AND ".$limits[0]['cost']." ";


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
            ".$sys_tables['facings'].".title as facing_title, 
            ".$sys_tables['floors'].".title as floor_title, 
            ".$sys_tables['windows'].".title as window_title, 
            ".$sys_tables['users'].".email as 'agent_email', 
            ".$sys_tables['users'].".skype as 'skype', 
            IF(".$sys_tables['agencies'].".id<2,'владелец',".$sys_tables['agencies'].".title) as 'agency_title', 
            ".$sys_tables['agencies'].".url as 'agency_url', 
            ".$sys_tables['agencies'].".phone_1 as 'agency_phone_1', 
            ".$sys_tables['agencies'].".phone_2 as 'agency_phone_2', 
            ".$sys_tables['agencies'].".phone_3 as 'agency_phone_3', 
            ".$sys_tables['agencies'].".id as 'id_agency', 
            ".$sys_tables['agencies'].".fax as 'agency_fax'
        FROM ".$sys_tables['live']." 
        LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables['live'].".id_district 
        LEFT JOIN ".$sys_tables['type_objects_live']." ON ".$sys_tables['type_objects_live'].".id = ".$sys_tables['live'].".id_type_object 
        LEFT JOIN ".$sys_tables['building_types']." ON ".$sys_tables['building_types'].".id = ".$sys_tables['live'].".id_building_type
        LEFT JOIN ".$sys_tables['elevators']." ON ".$sys_tables['elevators'].".id = ".$sys_tables['live'].".id_elevator
        LEFT JOIN ".$sys_tables['balcons']." ON ".$sys_tables['balcons'].".id = ".$sys_tables['live'].".id_balcon
        LEFT JOIN ".$sys_tables['toilets']." ON ".$sys_tables['toilets'].".id = ".$sys_tables['live'].".id_toilet
        LEFT JOIN ".$sys_tables['subways']." ON ".$sys_tables['subways'].".id = ".$sys_tables['live'].".id_subway 
        LEFT JOIN ".$sys_tables['facings']." ON ".$sys_tables['facings'].".id = ".$sys_tables['live'].".id_facing 
        LEFT JOIN ".$sys_tables['floors']." ON ".$sys_tables['floors'].".id = ".$sys_tables['live'].".id_floor 
        LEFT JOIN ".$sys_tables['windows']." ON ".$sys_tables['windows'].".id = ".$sys_tables['live'].".id_window 
        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['live'].".id_user 
        LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
        WHERE 
            ".$where."  
        ORDER BY ".$sys_tables['live'].".`date_change` DESC , ".$sys_tables['live'].".id_type_object, ".$sys_tables['live'].".id DESC     
        
";
$res = $db->fetchall($sql);
unset($limits);
foreach($res as $k=>$item){
    if(!empty($item) && (($item['id_agency']>1 && ($item['agency_phone_1']!='' || $item['agency_phone_2']!='' || $item['agency_phone_3']!='')) || ( (empty($item['id_agency']) || $item['id_agency']==1 ) && $item['seller_phone']!='' ) )){
        foreach($item as $k=>$v) $item[$k] = trim($v);
        $xmlItem->append();
        $xmlItem->attr("internal-id",$item['id']);

            $xmlItem->append('type', $item['rent'] == 1 ? 'аренда' : 'продажа', 1); // * обязательное поле
            $xmlItem->append('property-type', 'жилая',1); // * обязательное поле
        
            $xmlItem->append('category', $item['type_object_title'],1); // * обязательное поле

            $xmlItem->append('url', 'https://www.bsn.ru/live/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1); // * обязательное поле
            $xmlItem->append('creation-date', date('c',strtotime($item['date_in'])),1); // * обязательное поле
            $xmlItem->append('last-update-date', date('c',strtotime($item['date_change'])),1); 
            $xmlItem->append('expire-date', date('c',strtotime($item['date_end'])),1); 
            $xmlItem->append('payed-adv', $item['status']>2 ? '+' : '-',1); 
            $xmlItem->append('manually-added', $item['info_source'] == 1 ? '+' : '-',1); 

            //формирование адреса и контактной информации агентства/пользоваетля
            makeAddressAndContacts($item);
        
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
            
            if(!empty($item['notes']))  $xmlItem->append('description', clearstr($item['notes']), 1);
            
            if((int)$item['square_full']>0){
                $xmlItem->append('area', '',1); // общая площадь
                  $xmlItem->append('value', number_format($item['square_full'],1,'.',' '),2); 
                  $xmlItem->append('unit', 'кв. м.',2); 
            }
        
            if((int)$item['square_live']>0){
                $xmlItem->append('living-space', '',1); // жилая площадь (при продаже комнаты — площадь комнаты)
                    $xmlItem->append('value', number_format($item['square_live'],1,'.',' '),2);   
                    $xmlItem->append('unit', 'кв. м.',2);   
            }
            
            if((int)$item['square_kitchen']>0){
                $xmlItem->append('kitchen-space', '',1); // площадь кухни
                    $xmlItem->append('value', number_format($item['square_kitchen'],1,'.',' '),2);   
                    $xmlItem->append('unit', 'кв. м.',2);   
            }
            
            //Поля для жилой недвижимости
            $xmlItem->append('rooms', $item['rooms_total'],1); // * обязательное поле ДЛЯ ЖИЛОЙ НЕДВИЖИМОСТИ
            if($item['id_type_object']==2) $xmlItem->append('rooms-offered', $item['rooms_sale'],1);

            if($item['id_facing']>2) $xmlItem->append('renovation', $item['facing_title'],1);
            if($item['phone'] == 1) $xmlItem->append('phone', '+',1);
            if($item['furniture'] == 1) $xmlItem->append('room-furniture', '+',1);
            if($item['wash_mash'] == 1) $xmlItem->append('washing-machine', '+',1); 
            if($item['refrigerator'] == 1) $xmlItem->append('refrigerator',  '+' ,1); 
            if($item['id_balcon']>1 && $item['id_balcon']<6) $xmlItem->append('balcony', $item['balcon_title'],1);
            if($item['id_toilet']>2  && $item['id_toilet']<6) $xmlItem->append('bathroom-unit', $item['toilet_title'],1);
            if($item['id_floor']>2  && $item['id_floor']<9) $xmlItem->append('floor-covering', $item['floor_title'],1);
            
            if($item['id_window']>1 && $item['id_window']<6) $xmlItem->append('window-view', $item['window_title'],1);
            if(!empty($item['level'])) $xmlItem->append('floor', $item['level'],1);
            if(!empty($item['level_total'])) $xmlItem->append('floors-total', $item['level_total'],1);
            if($item['id_elevator']!=1 && $item['id_elevator']!=5) $xmlItem->append('lift',  '+' ,1); 
            if(in_array($item['id_building_type'],array(9,10,17))) $xmlItem->append('building-type', $item['building_type_title'],1);
            if(!empty($item['ceiling_height']) && $item['ceiling_height']>0) $xmlItem->append('ceiling-height', $item['ceiling_height'],1);
            $xmlItem->append('is-elite', $item['elite']==1?'+':'-',1);
            
            // window-view     вид из окон (рекомендуемые значения — «во двор», «на улицу»)
            
        $live_count++;
    } else $log['empty_phone'][] = 'Жилая, id объекта '.$item['id'].', '.(!empty($item['id_agency'])?"Агентство-".$item['id_agency']:"Пользователь-".$item['id_user']).', '.$item['agency_title'];
    unset($item);
}
echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";          
unset($res);
echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";          
/*
* СТРОЯЩАЯСЯ НЕДВИЖИМОСТЬ
*/         
//обшее условие для строящейся
$where =   "(
                (
                    (".$sys_tables['build'].".cost/".$sys_tables['build'].".square_full) BETWEEN 50000 AND 250000 AND ".$sys_tables['build'].".id_region = 78 AND ".$sys_tables['build'].".id_district IN (2,3,4,5,6,7,8,10,11,12,13,15,16,27,29,38,43,53)
                ) OR (
                    (".$sys_tables['build'].".cost/".$sys_tables['build'].".square_full) BETWEEN 30000 AND 150000 AND ".$sys_tables['build'].".id_region = 47 AND (".$sys_tables['build'].".id_area>0 OR ".$sys_tables['build'].".id_city>0)
                )
            ) AND 
            ".$sys_tables['build'].".rooms_sale >=1 AND ".$sys_tables['build'].".rooms_sale <=5 AND
            ".$sys_tables['build'].".cost>0 AND
            ".$sys_tables['build'].".square_full>0 AND
            ".$sys_tables['build'].".published = 1 AND
            DATEDIFF(CURDATE(),".$sys_tables['build'].".date_change) < 28
            AND ".$sys_tables['build'].".weight > 15";

//получение срока сдачи объекта (не позднее текущего квартала)
$year = date('Y'); $decade = (int)(date('m')/4+1); $complete_ids = array(4,5);
$complete = $db->fetchall("SELECT * FROM ".$sys_tables['build_complete']." WHERE (year = ".$year." AND decade >".$decade.") OR year > ".$year);
foreach($complete as $k=>$item) $complete_ids[] = $item['id'];
$where .= " AND  ".$sys_tables['build'].".id_build_complete IN (".implode(', ',$complete_ids).")";
//получение 10% с Наименшей и наибольшей ценой
$items = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['build']." WHERE ".$where);
$total = $items['cnt'];
unset($items);
$limits = $db->fetchall("SELECT cost FROM (
                            (SELECT cost FROM ".$sys_tables['build']." WHERE ".$where." ORDER BY cost DESC LIMIT ".(int)($total*0.05).", 1)
                            UNION 
                            (SELECT cost FROM ".$sys_tables['build']." WHERE ".$where." ORDER BY cost DESC LIMIT ".(int)($total*0.95).", 1)
                         ) as a
");
$where .= " AND ".$sys_tables['build'].".cost BETWEEN ".$limits[1]['cost']." AND ".$limits[0]['cost']." ";
unset($limits);

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
            ".$sys_tables['facings'].".title as facing_title, 
            ".$sys_tables['windows'].".title as window_title, 
            ".$sys_tables['users'].".email as 'agent_email', 
            ".$sys_tables['users'].".skype as 'skype', 
            IF(".$sys_tables['agencies'].".id<2,'владелец',".$sys_tables['agencies'].".title) as 'agency_title', 
            ".$sys_tables['agencies'].".url as 'agency_url', 
            ".$sys_tables['agencies'].".phone_1 as 'agency_phone_1', 
            ".$sys_tables['agencies'].".phone_2 as 'agency_phone_2', 
            ".$sys_tables['agencies'].".phone_3 as 'agency_phone_3', 
            ".$sys_tables['agencies'].".id as 'id_agency', 
            ".$sys_tables['agencies'].".fax as 'agency_fax'
        FROM ".$sys_tables['build']." 
        LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables['build'].".id_district 
        LEFT JOIN ".$sys_tables['building_types']." ON ".$sys_tables['building_types'].".id = ".$sys_tables['build'].".id_building_type
        LEFT JOIN ".$sys_tables['build_complete']." ON ".$sys_tables['build_complete'].".id = ".$sys_tables['build'].".id_build_complete
        LEFT JOIN ".$sys_tables['elevators']." ON ".$sys_tables['elevators'].".id = ".$sys_tables['build'].".id_elevator
        LEFT JOIN ".$sys_tables['balcons']." ON ".$sys_tables['balcons'].".id = ".$sys_tables['build'].".id_balcon
        LEFT JOIN ".$sys_tables['toilets']." ON ".$sys_tables['toilets'].".id = ".$sys_tables['build'].".id_toilet
        LEFT JOIN ".$sys_tables['subways']." ON ".$sys_tables['subways'].".id = ".$sys_tables['build'].".id_subway 
        LEFT JOIN ".$sys_tables['facings']." ON ".$sys_tables['facings'].".id = ".$sys_tables['build'].".id_facing
        LEFT JOIN ".$sys_tables['windows']." ON ".$sys_tables['windows'].".id = ".$sys_tables['build'].".id_window 
        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['build'].".id_user 
        LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
        WHERE 
            ".$where."
        ORDER BY ".$sys_tables['build'].".id DESC
        
";
$res = $db->fetchall($sql);
echo ' 300 - ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";          
foreach($res as $k=>$item){
    if(!empty($item) && (($item['id_agency']>1 && ($item['agency_phone_1']!='' || $item['agency_phone_2']!='' || $item['agency_phone_3']!='')) || ( (empty($item['id_agency']) || $item['id_agency']==1 ) && $item['seller_phone']!='' ) )){
        foreach($item as $k=>$v) $item[$k] = trim($v);
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

        
            //формирование адреса и контактной информации агентства/пользоваетля
            makeAddressAndContacts($item);
        
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
            
            if(!empty($item['notes']))  $xmlItem->append('description', clearstr($item['notes']), 1);
            
            if((int)$item['square_full']>0){
                $xmlItem->append('area', '',1); // общая площадь
                  $xmlItem->append('value', number_format($item['square_full'],1,'.',' '),2); 
                  $xmlItem->append('unit', 'кв. м.',2); 
            }
        
            if((int)$item['square_live']>0){
                $xmlItem->append('living-space', '',1); // жилая площадь (при продаже комнаты — площадь комнаты)
                    $xmlItem->append('value', number_format($item['square_live'],1,'.',' '),2);   
                    $xmlItem->append('unit', 'кв. м.',2);   
            }
            
            if((int)$item['square_kitchen']>0){
                $xmlItem->append('kitchen-space', '',1); // площадь кухни
                    $xmlItem->append('value', number_format($item['square_kitchen'],1,'.',' '),2);   
                    $xmlItem->append('unit', 'кв. м.',2);   
            }
            
            //Поля для строящейся недвижимости
            $xmlItem->append('new-flat', '+',1);
            $xmlItem->append('rooms', $item['rooms_sale'],1); // * обязательное поле ДЛЯ строящейся НЕДВИЖИМОСТИ
            
            if($item['id_facing']>2) $xmlItem->append('renovation', $item['facing_title'],1);
            if($item['id_balcon']>1 && $item['id_balcon']<6) $xmlItem->append('balcony', $item['balcon_title'],1);
            if($item['id_toilet']>2  && $item['id_toilet']<6) $xmlItem->append('bathroom-unit', $item['toilet_title'],1);
            if(!empty($item['level'])) $xmlItem->append('floor', $item['level'],1);
            if(!empty($item['level_total'])) $xmlItem->append('floors-total', $item['level_total'],1);
            if($item['id_window']>1 && $item['id_window']<6) $xmlItem->append('window-view', $item['window_title'],1);
            if($item['id_elevator']!=1 && $item['id_elevator']!=5) $xmlItem->append('lift', '+',1); 
            if(in_array($item['id_building_type'],array(9,10,17))) $xmlItem->append('building-type', $item['building_type_title'],1);
            if(!empty($item['ceiling_height']) && $item['ceiling_height']>0) $xmlItem->append('ceiling-height', $item['ceiling_height'],1);
            $xmlItem->append('is-elite', $item['elite']==1?'+':'-',1);
            if($item['id_build_complete']==4) $xmlItem->append('building-state', 'hand-over',1);
            elseif($item['id_build_complete']==5) $xmlItem->append('building-state', 'built',1);
            elseif($item['id_build_complete']>5) {
                $xmlItem->append('building-state', 'unfinished',1);
                $xmlItem->append('built-year', $item['build_complete_year'],1);
                if(!empty($item['build_complete_decade'])) $xmlItem->append('ready-quarter', $item['build_complete_decade'],1);
            }
            
        $build_count++;
    } else $log['empty_phone'][] = 'Стройка, id объекта '.$item['id'].', '.(!empty($item['id_agency'])?"Агентство-".$item['id_agency']:"Пользователь-".$item['id_user']).', '.$item['agency_title'];
    unset($items);
}
unset($res);
echo '380 - ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";          
/*
* ЗАГОРОДНАЯ НЕДВИЖИМОСТЬ
*/
$deal_types = array(1=>'rent',2=>'sell');
foreach($deal_types as $val => $deal_type){
    //условие для загородки
    if($deal_type=='rent') $where = $sys_tables['country'].".`cost` BETWEEN 15000 AND 550000 AND ".$sys_tables['country'].".rent=1 AND ".$sys_tables['country'].".id_type_object!=13";
    else  $where = $sys_tables['country'].".`cost` BETWEEN 1000000 AND 350000000 AND ".$sys_tables['country'].".rent=2";
    $where .= " AND ".$sys_tables['country'].".`published` = 1
                AND DATEDIFF(CURDATE(),".$sys_tables['country'].".date_change) < 28 
                AND DATEDIFF(CURDATE(),".$sys_tables['country'].".date_in) < 90 
                AND ".$sys_tables['country'].".square_ground < 100
                AND ".$sys_tables['country'].".weight > 10";
    //получение 10% с аименшей и наибольшей ценой
    $items = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['country']." WHERE ".$where);
    $total = $items['cnt'];
    unset($items);
    $limits = $db->fetchall("SELECT cost FROM (
                                (SELECT cost FROM ".$sys_tables['country']." WHERE ".$where." ORDER BY cost DESC LIMIT ".(int)($total*0.05).", 1)
                                UNION 
                                (SELECT cost FROM ".$sys_tables['country']." WHERE ".$where." ORDER BY cost DESC LIMIT ".(int)($total*0.95).", 1)
                             ) as a
    ");
    $where .= " AND ".$sys_tables['country'].".cost BETWEEN ".$limits[1]['cost']." AND ".$limits[0]['cost']." ";
    unset($limit);
    $sql = "SELECT ".$sys_tables['country'].".*, 
                    ".$sys_tables['country'].".date_change + INTERVAL 30 DAY as date_end,
                    ".$sys_tables['type_objects_country'].".`yandex_value` as 'type_object_title', 
                    ".$sys_tables['users'].".email as 'agent_email', 
                    ".$sys_tables['users'].".skype as 'skype', 
                    IF(".$sys_tables['agencies'].".id<2,'владелец',".$sys_tables['agencies'].".title) as 'agency_title', 
                    ".$sys_tables['agencies'].".url as 'agency_url', 
                    ".$sys_tables['agencies'].".phone_1 as 'agency_phone_1', 
                    ".$sys_tables['agencies'].".phone_2 as 'agency_phone_2', 
                    ".$sys_tables['agencies'].".phone_3 as 'agency_phone_3', 
                    ".$sys_tables['agencies'].".id as 'id_agency', 
                    ".$sys_tables['agencies'].".fax as 'agency_fax'
            FROM ".$sys_tables['country']." 
            LEFT JOIN ".$sys_tables['type_objects_country']." ON ".$sys_tables['type_objects_country'].".`id` = ".$sys_tables['country'].".`id_type_object` 
            LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".`id` = ".$sys_tables['country'].".`id_user` 
            LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".`id` = ".$sys_tables['users'].".`id_agency` 
            WHERE 
                ".$where."
            ORDER BY ".$sys_tables['country'].".id_type_object, ".$sys_tables['country'].".id DESC     
    ";
    $list = $db->fetchall($sql);

    foreach($list as $k=>$item){
    if(!empty($item) && (($item['id_agency']>1 && ($item['agency_phone_1']!='' || $item['agency_phone_2']!='' || $item['agency_phone_3']!='')) || ( (empty($item['id_agency']) || $item['id_agency']==1 ) && $item['seller_phone']!='' ) )){
            foreach($item as $k=>$v) $item[$k] = trim($v);
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
                
                 $res_image = $db->query("SELECT name, LEFT (".$sys_tables['country_photos'].".`name`,2) as `subfolder` 
                                         FROM ".$sys_tables['country_photos']." 
                                         WHERE id_parent = ?",$item['id']);
                while($item_image = $res_image->fetch_array()) {
                    if(file_exists(ROOT_PATH."/".Config::Get('img_folders/country')."/big/".$item_image['subfolder']."/".$item_image['name']))
                        $xmlItem->append('image', "https://www.bsn.ru/".Config::Get('img_folders/country')."/big/".$item_image['subfolder']."/".$item_image['name'],1);
                }

            
                if(!empty($item['photo_name'])) $xmlItem->append('image', 'https://www.bsn.ru/'.$img_folder.'/big/'.$item['photo_subfolder'].'/'.$item['photo_name'],1);
            
                if(!empty($item['id_subway'])) unset($item['id_subway']);
                makeAddressAndContacts($item);
                
                $xmlItem->append('price', '', 1); // * обязательное поле
                    $xmlItem->append('value', $item['cost'],2); // * обязательное поле
                    $xmlItem->append('currency', 'RUB',2); // * обязательное поле
                    if($item['rent']==1) $xmlItem->append('period', $item['by_the_day']==1 ? 'день' : 'месяц',2); 
                
                if(in_array($item['id_type_object'],array(2,3,9,10,11,12,14,15,19,20)) && (int)$item['square_full']>0){
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
                        $xmlItem->append('unit', 'сот',2); 
                } 
                if(!empty($item['notes']))  $xmlItem->append('description', clearstr($item['notes']), 1);
            /* ЗАГОРОДНАЯ НЕДВИЖИМОСТЬ */
                if($item['id_toilet']==2 || $item['id_toilet']==3 || $item['id_toilet']==6 || $item['id_toilet']==7 || $item['id_toilet']==9) $xmlItem->append('toilet', 'в доме',1);
                elseif($item['id_toilet']==8)$xmlItem->append('toilet', 'на улице',1);
                
                if($item['id_bathroom']==4 || $item['id_bathroom']==3) $xmlItem->append('sauna', '+',1);
                
                if($item['id_heating']!=1 && $item['id_heating']!=3) $xmlItem->append('heating-supply', '+',1);
                
                if($item['id_toilet']==4) $xmlItem->append('sewerage-supply', '+',1);
                
                if($item['id_electricity']==2 || $item['id_electricity']==4) $xmlItem->append('electricity-supply', '+',1);
                
                if($item['id_gas']==2 || $item['id_gas']==4) $xmlItem->append('gas-supply', '+',1);              
                
                ++$country_count; 
            } else $log['empty_phone'][] = 'Загородка, id объекта '.$item['id'].', '.(!empty($item['id_agency'])?"Агентство-".$item['id_agency']:"Пользователь-".$item['id_user']).', '.$item['agency_title'];
            unset($item);
    } 
}   unset($list);
echo ' 501 - ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";          
$xml->formatOutput = true;
$yandex_realty = $xml->saveXML(); // put string in yandex_realty
$yandex_realty = str_replace(array("<![CDATA[","]]>"),"",$yandex_realty);
file_put_contents(__XMLPATH__,$yandex_realty);
if(file_exists(__XMLPATH__.".gz")) unlink(__XMLPATH__.".gz");
exec("gzip -rv ".__XMLPATH__);
exec("chmod 777 ".__XMLPATH__.".gz");

$html = '
<style>
    body, table, div {font-family:Calibri, "Palatino Linotype", "Book Antiqua", Palatino, serif;}
    table {font-size:15px; border-collapse:collapse;  table-layout:auto;}
    .mail_text td, .mail_text th { text-align:center; vertical-align:top;}
    .mail_small_text {text-align:left; font-size:9px!important; width:95%;}
</style>
<table width="99%" class="mail_text" cellpadding="2" cellspacing="0">
<tr><th>Варианты для yandex-realty</th>';
$html .= "<tr><td>
- жилая: $live_count<br/>
- стройка: $build_count<br/>
- загородка: $country_count<br/>
</td></tr> ";
if(!empty($log['empty_phone'])){
   $html .= "<tr><td><hr size=\"1\" width=\"100%\" color=\"#999999\" /></td></tr>";
   $html .= "<tr><td>Пустые телефоны агентств/пользователей ".count($log['empty_phone']).":<br >";
   foreach($log['empty_phone'] as $id => $title) $html .= $title."<br/>";
   $html .= "</td></tr> ";
}
$html .= "<tr><td><hr size=\"1\" width=\"100%\" color=\"#999999\" /></td></tr>";
$html .= "</table>";

$mailer = new EMailer('mail');

// перевод письма в кодировку мейлера
$html = iconv('UTF-8', $mailer->CharSet, $html);
// параметры письма
$mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Генерация фида в Я.недвижимость');
$mailer->Body = $html;
$mailer->AltBody = strip_tags($html);
$mailer->IsHTML(true);
$mailer->AddAddress(Config::Get('emails/web'));     //отправка письма агентству
$mailer->AddAddress(Config::Get('emails/web2'));     //отправка письма агентству
$mailer->From = 'yabsnxml@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Генерация фида в Я.недвижимость');
// попытка отправить
$mailer->Send();        

class xmlGenerate{
    
    public $item = 0;
    public $currentitem = '';
    public $subitem = '';
    public $checkitem = '';
    
    public $itemContent = array();

    public function __construct()
    {
        global $db, $xmlUrlset, $xml;
        $this->db=&$db;
        $this->xmlUrlset=& $xmlUrlset;
        $this->xml=&$xml;
        
    }
    
    public function append($child = false, $nodeText = false, $sub = false)
    {
        if($child == false) {
            $this->currentitem = $this->xmlUrlset->appendChild($this->xml->createElement('offer'));
            $this->itemContent[0] = array('offer' => array($this->currentitem) );
        } elseif($sub > 0) {
            $key = array_keys($this->itemContent[($sub-1)]);
            $current = $this->itemContent[($sub-1)][$key[0]];
            $current = $current[0];
            $this->currentitem = $current->appendChild($this->xml->createElement($child));
            $this->itemContent[$sub] = array($child => array($this->currentitem) );
        }
        if($nodeText!=false) $this->setNode($nodeText);
    }

    /* Create node Text */
    public function setNode($nodeText = false)
    {
        if($nodeText!=false) $this->currentitem -> appendChild($this->xml->createCDATASection(htmlspecialchars($nodeText)));
    }

    public function attr($title, $value)
    {
        $this->currentitem -> setAttribute($title, $value);
    }

}
?>