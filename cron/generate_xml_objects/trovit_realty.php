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
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
require_once('includes/class.estate.php');


define('__XMLPATH__',ROOT_PATH.'/trovit_realty.xml');
define('__URL__','https://www.bsn.ru/');


$db->select_db('estate');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = '".Config::$values['mysql']['lc_time_names']."';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$log = array();
$xml = new DOMDocument('1.0','UTF-8');


$xmlUrlset = $xml->appendChild($xml->createElement('trovit'));

//Init xml-making
$xmlItem = new xmlGenerate;

$live_count=0; $build_count=0; $country_count=0;

/*
* ЖИЛАЯ НЕДВИЖИМОСТЬ
*/       
$base_memory_usage = memory_get_usage();

//обшее условие для жилой
//            DATEDIFF(CURDATE(),".$sys_tables['live'].".date_change) < 28 AND
//            DATEDIFF(CURDATE(),".$sys_tables['live'].".date_in) < 90 AND

$where =   "
            ".$sys_tables['live'].".rooms_total >=1  AND
            ".$sys_tables['live'].".cost>0 AND
            ".$sys_tables['live'].".square_full>0 AND
            ".$sys_tables['live'].".published = 1 AND
            ".$sys_tables['live'].".id_type_object = 1 AND
            ".$sys_tables['live'].".id_main_photo > 0 AND
            ".$sys_tables['live'].".weight > 20
            ";

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
            ".$sys_tables['agencies'].".title as 'agency_title', 
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
foreach($res as $k=>$item){
    if(!empty($item)){
        foreach($item as $k=>$v) $item[$k] = trim($v);
        $xmlItem->append();
        
        $xmlItem->append('id', $item['id'], 1);
        $xmlItem->append('url', 'https://www.bsn.ru/live/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1);

        $xmlItem->append('type', $item['rent'] == 1 ? 'For Rent' : 'For Sale', 1);
        $estate = new EstateItemLive($item['id']);
        $description = $estate->getTextDescription();
        $title = $estate->getTitles();
        $title = $title['header'];
        if(!empty($item['district_title'])) $title .= ", " . $item['district_title'] . " район";
        if(!empty($item['subway_title'])) $title .= ", метро " . $item['subway_title'];
        if(!empty($item['level'])) {
            $title .= ", этаж " . $item['level'];
            if(!empty($item['level_total'])) $title .= " из " . $item['level_total'];
        }
        if(Convert::ToInt($item['square_full']) > 0) $title .= ", общей площадью " . $item['square_full'];
        if(Convert::ToInt($item['square_kitchen']) > 0) $title .= ", площадью кухни " . $item['square_kitchen'];
        $xmlItem->append('title', $title,1);
        
        if(!empty($description))  $xmlItem->append('content', clearstr($description), 1);                      
        
        $xmlItem->append('date', date('c',strtotime($item['date_in'])),1);
        $xmlItem->append('expiration_date', date('c',strtotime($item['date_end'])),1);
        $xmlItem->append('mobile_url', 'http://m.bsn.ru/live/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1);
         
        if($item['rent'] == 1){
            $xmlItem->append('price', $item['by_the_day'] == 1 ? $item['cost']*7 : $item['cost'], 1);
            $xmlItem->attr('period', $item['by_the_day'] == 1 ? 'weekly' : 'monthly', 1);
        } else $xmlItem->append('price', $item['cost'], 1);
        $xmlItem->append('property_type', 'квартира',1);

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

        if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : '') ,1);
        elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],1);     
        if($item['id_region'] == 47) {
            $xmlItem->append('region','Ленинградская область',1);
            if(!empty($city['title'])) $xmlItem->append('city',$city['title'],1);
            
        } else {
            $xmlItem->append('city',!empty($city['title']) ? $city['title']  : 'Санкт-Петербург' ,1);
            
        }
        
        if(!empty($item['district_title'])) $xmlItem->append('city_area',$item['district_title'],1);
        
        if(!empty($item['agency_title']) && !empty($item['id_agency'])) $xmlItem->append('agency', $item['agency_title'],1); 
        else $xmlItem->append('by_owner', true, 1); 

        if($item['lat']>0 && $item['lng']>0){
            $xmlItem->append('latitude', $item['lat'],1);
            $xmlItem->append('longitude', $item['lng'],1);
        }
                
        if((int)$item['square_full']>0) {
                $xmlItem->append('floor_area', $item['square_full'],1);
                    $xmlItem->attr('unit', 'meters'); 
        }
        if(!empty($item['square_ground'])){
            $xmlItem->append('plot_area', $item['square_ground']*0.01,1); 
                $xmlItem->attr('unit', 'hectares'); 
        } 
        //Поля для жилой недвижимости
        $xmlItem->append('rooms', $item['rooms_total'],1);             
        if($item['id_facing']>2) $xmlItem->append('condition', $item['facing_title'],1);
        if($item['id_toilet']>2  && $item['id_toilet']<6) $xmlItem->append('bathrooms', $item['toilet_title'],1);
        
        $res_image = $db->querys("SELECT name, LEFT (".$sys_tables['live_photos'].".`name`,2) as `subfolder` 
                                 FROM ".$sys_tables['live_photos']." 
                                 WHERE id_parent = ?",$item['id']);
        $xmlItem->append('pictures', '',1);
        while($item_image = $res_image->fetch_array()) {
            if(file_exists(ROOT_PATH."/".Config::Get('img_folders/live')."/med/".$item_image['subfolder']."/".$item_image['name'])){
                
                $xmlItem->append('picture', '',2);
                
                $xmlItem->append('picture_url', "https://www.bsn.ru/".Config::Get('img_folders/live')."/med/".$item_image['subfolder']."/".$item_image['name'],3);
                $xmlItem->append('picture_title', $title, 3);
            }
        }
        $xmlItem->append('segmentation_text', 'vip', 1);
       
        $live_count++;
    } else $log['empty_phone'][] = 'Жилая, id объекта '.$item['id'].', '.(!empty($item['id_agency'])?"Агентство-".$item['id_agency']:"Пользователь-".$item['id_user']).', '.$item['agency_title'];
    unset($item);
}
echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";          
unset($res);
$where =   "
            ".$sys_tables['build'].".rooms_sale >=1 AND ".$sys_tables['build'].".rooms_sale <=5 AND
            ".$sys_tables['build'].".cost>0 AND
            ".$sys_tables['build'].".square_full>0 AND
            ".$sys_tables['build'].".published = 1 AND
            ".$sys_tables['build'].".id_main_photo > 0 AND
            DATEDIFF(CURDATE(),".$sys_tables['build'].".date_change) < 28
            AND ".$sys_tables['build'].".weight > 15";

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
            ".$sys_tables['agencies'].".title as 'agency_title', 
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
    if(!empty($item)){
        foreach($item as $k=>$v) $item[$k] = trim($v);
        $xmlItem->append();
        
        $xmlItem->append('id', $item['id'], 1);
        $xmlItem->append('url', 'https://www.bsn.ru/build/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1);
        $xmlItem->append('is_new', true, 1);

        $xmlItem->append('type', $item['rent'] == 1 ? 'For Rent' : 'For Sale', 1);
        $estate = new EstateItemBuild($item['id']);
        $description = $estate->getTextDescription();

        $title = $estate->getTitles();
        $title = $title['header'];
        if(!empty($item['district_title'])) $title .= ", " . $item['district_title'] . " район";
        if(!empty($item['subway_title'])) $title .= ", метро " . $item['subway_title'];
        if(!empty($item['level'])) {
            $title .= ", этаж " . $item['level'];
            if(!empty($item['level_total'])) $title .= " из " . $item['level_total'];
        }
        if(Convert::ToInt($item['square_full']) > 0) $title .= ", общей площадью " . $item['square_full'];
        if(Convert::ToInt($item['square_kitchen']) > 0) $title .= ", площадью кухни " . $item['square_kitchen'];
        $xmlItem->append('title', $title,1);

        if(!empty($description))  $xmlItem->append('content', clearstr($description), 1);                      
        $xmlItem->append('date', date('c',strtotime($item['date_in'])),1);
        $xmlItem->append('expiration_date', date('c',strtotime($item['date_end'])),1);
        $xmlItem->append('mobile_url', 'http://m.bsn.ru/build/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1);
        $xmlItem->append('price', $item['cost'], 1);
        $xmlItem->append('property_type', 'квартира',1);

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

        if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : '') ,1);
        elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],1);     
        if($item['id_region'] == 47) {
            $xmlItem->append('region','Ленинградская область',1);
            if(!empty($city['title'])) $xmlItem->append('city',$city['title'],1);
            
        } else {
            $xmlItem->append('city',!empty($city['title']) ? $city['title']  : 'Санкт-Петербург' ,1);
            
        }
        
        if(!empty($item['district_title'])) $xmlItem->append('city_area',$item['district_title'],1);
        
        if(!empty($item['agency_title']) && !empty($item['id_agency'])) $xmlItem->append('agency', $item['agency_title'],1); 
        else $xmlItem->append('by_owner', true, 1); 

        if($item['lat']>0 && $item['lng']>0){
            $xmlItem->append('latitude', $item['lat'],1);
            $xmlItem->append('longitude', $item['lng'],1);
        }
                
        if((int)$item['square_full']>0) {
                $xmlItem->append('floor_area', $item['square_full'],1);
                    $xmlItem->attr('unit', 'meters'); 
        }
        if(!empty($item['square_ground'])){
            $xmlItem->append('plot_area', $item['square_ground']*0.01,1); 
                $xmlItem->attr('unit', 'hectares'); 
        } 
        //Поля для жилой недвижимости
        $xmlItem->append('rooms', $item['rooms_sale'],1);             
        if($item['id_facing']>2) $xmlItem->append('condition', $item['facing_title'],1);
        if($item['id_toilet']>2  && $item['id_toilet']<6) $xmlItem->append('bathrooms', $item['toilet_title'],1);
        
        $res_image = $db->querys("SELECT name, LEFT (".$sys_tables['build_photos'].".`name`,2) as `subfolder` 
                                 FROM ".$sys_tables['build_photos']." 
                                 WHERE id_parent = ?",$item['id']);
        $xmlItem->append('pictures', '',1);
        while($item_image = $res_image->fetch_array()) {
            if(file_exists(ROOT_PATH."/".Config::Get('img_folders/build')."/med/".$item_image['subfolder']."/".$item_image['name'])){
                    $xmlItem->append('picture', '',2);
                    $xmlItem->append('picture_url', "https://www.bsn.ru/".Config::Get('img_folders/build')."/med/".$item_image['subfolder']."/".$item_image['name'],3);
                    $xmlItem->append('picture_title', $title,3);
            }
        }
        $xmlItem->append('segmentation_text', 'vip', 1);
       
        $build_count++;
    } else $log['empty_phone'][] = 'Жилая, id объекта '.$item['id'].', '.(!empty($item['id_agency'])?"Агентство-".$item['id_agency']:"Пользователь-".$item['id_user']).', '.$item['agency_title'];
    unset($item);
}
echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";          
        
$xml->formatOutput = true;
$trovit_realty = $xml->saveXML(); // put string in trovit_realty
file_put_contents(__XMLPATH__, $trovit_realty);
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
<tr><th>Варианты для trovit-realty</th>';
$html .= "<tr><td>
- жилая: $live_count<br/>
- стройка: $build_count<br/>
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
$mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Генерация фида в Trovita');
$mailer->Body = $html;
$mailer->AltBody = strip_tags($html);
$mailer->IsHTML(true);
$mailer->AddAddress(Config::Get('emails/web'));     //отправка письма агентству
$mailer->AddAddress(Config::Get('emails/web2'));     //отправка письма агентству
$mailer->From = 'yabsnxml@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Генерация фида в Trovita');
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
            $this->currentitem = $this->xmlUrlset->appendChild($this->xml->createElement('ad'));
            $this->itemContent[0] = array('ad' => array($this->currentitem) );
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