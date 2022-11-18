#!/usr/bin/php
<?php

/*

DELETE FROM `pages_seo` WHERE pretty_url LIKE ('%streets-%');
DELETE FROM `pages_seo` WHERE pretty_url LIKE ('%artpay%');

DELETE FROM `pages_seo` WHERE url LIKE ('build/sell/?%');
DELETE FROM `pages_seo` WHERE url LIKE ('country/sell/?%') OR url LIKE ('country/rent/?%');
DELETE FROM `pages_seo` WHERE url LIKE ('commercial/sell/?%') OR url LIKE ('commercial/rent/?%');
DELETE FROM `pages_seo` WHERE url LIKE ('live/sell/?%') OR url LIKE ('live/rent/?%');
DELETE FROM `pages_seo` WHERE url LIKE ('inter/sell/?%') OR url LIKE ('inter/rent/?%');

UPDATE `pages_seo` SET title = REPLACE(title, 'четырех комнат в', 'комнат в четырехкомнатных квартирах'), h1_title = REPLACE(h1_title, 'четырех комнат в', 'комнат в четырехкомнатных квартирах'), description = REPLACE(description, 'четырех комнат в', 'комнат в четырехкомнатных квартирах'), keywords = REPLACE(keywords, 'четырех комнат в', 'комнат в четырехкомнатных квартирах'), seo_text = REPLACE(seo_text, 'четырех комнат в', 'комнат в четырехкомнатных квартирах');
UPDATE `pages_seo` SET title = REPLACE(title, 'трех комнат в', 'комнат в трехкомнатных квартирах'), h1_title = REPLACE(h1_title, 'трех комнат в', 'комнат в трехкомнатных квартирах'), description = REPLACE(description, 'трех комнат в', 'комнат в трехкомнатных квартирах'), keywords = REPLACE(keywords, 'трех комнат в', 'комнат в трехкомнатных квартирах'), seo_text = REPLACE(seo_text, 'трех комнат в', 'комнат в трехкомнатных квартирах');
UPDATE `pages_seo` SET title = REPLACE(title, 'двух комнат в', 'комнат в двухкомнатных квартирах'), h1_title = REPLACE(h1_title, 'двух комнат в', 'комнат в двухкомнатных квартирах'), description = REPLACE(description, 'двух комнат в', 'комнат в двухкомнатных квартирах'), keywords = REPLACE(keywords, 'двух комнат в', 'комнат в двухкомнатных квартирах'), seo_text = REPLACE(seo_text, 'двух комнат в', 'комнат в двухкомнатных квартирах');
UPDATE `pages_seo` SET title = REPLACE(title, 'одной комнаты в', 'комнат в однокомнатных квартирах'), h1_title = REPLACE(h1_title, 'одной комнаты в', 'комнат в однокомнатных квартирах'), description = REPLACE(description, 'одно комнат в', 'комнат в однокомнатных квартирах'), keywords = REPLACE(keywords, 'одной комнаты в', 'комнат в однокомнатных квартирах'), seo_text = REPLACE(seo_text, 'одной комнаты в', 'комнат в однокомнатных квартирах');

UPDATE `pages_seo` SET title = REPLACE(title, 'четырехкомнатных и более', 'четырехкомнатных+'), h1_title = REPLACE(h1_title, 'четырехкомнатных и более', 'четырехкомнатных+'), description = REPLACE(description, 'четырехкомнатных и более', 'четырехкомнатных+'), keywords = REPLACE(keywords, 'четырехкомнатных и более', 'четырехкомнатных+'), seo_text = REPLACE(seo_text, 'четырехкомнатных и более', 'четырехкомнатных+');


локально
from 464752
*/
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
define('TEST', !empty($_SERVER['PWD']) && preg_match('/.+test\.bsn\.ru/i', $_SERVER['PWD']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : ( TEST ? realpath('/home/bsn/sites/test.bsn.ru/public_html/trunk/' ) : realpath('/home/bsn/sites/bsn.ru/public_html/' ) );

if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');



// подключение классов ядра
include_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
include_once('includes/functions.php');          // функции  из модуля
include_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
include_once('includes/class.estate.php');   
include_once('includes/class.estate.subscriptions.php'); 
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
// вспомогательные таблицы
$sys_tables = Config::$sys_tables; 
$sys_tables['pages_seo'] = 'common.pages_seo';
//массив всех улиц СПб
$streets_array = $db->fetchall("SELECT 'geodata' as type, 'streets' as geodata_type, id, CONCAT(shortname,' ',offname) as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND a_level = 5");        
$places_array = $db->fetchall("SELECT 'geodata' as type, 'place' as geodata_type, id, CONCAT(shortname,' ',offname) as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level < 5 AND a_level > 2 AND shortname IN ('город', 'городок', 'деревня', 'тер', 'поселок', 'шоссе')");        
$cities_array = $db->fetchall("SELECT 'geodata' as type, 'city' as geodata_type, id, CONCAT(shortname,' ',offname) as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level < 5 AND a_level > 2 AND shortname IN ('город')");        

/*
// Бизнес-центры
$params = array(
                'class', 'business_center', 'streets', 'cities', 'subways','districts','district_areas'
);
$result_array = array();
$arrr = pc_array_power_set($params, 3);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
    if( 
        ( in_array('business_center', $a) && count($a) > 1 ) ||
        ( in_array('cities', $a) && count($a) > 2 ) ||
        ( in_array('cities',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('streets', $a) && count($a) > 2 ) ||
        ( in_array('streets',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('subways',$a) && ( in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('districts',$a) && ( in_array('district_areas',$a)) )
    ) unset($arrr[$k]);
    else if(!in_array($a, $result_array)) $result_array[] = $a;    
}
$deal_types = array('sell');
$estate_type = 'business_centers';
foreach($result_array as $arr){
    
    $sql_array = array();
    
    if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
    if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title FROM ".$sys_tables['districts']."  ");
    if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2  ");        
    if(in_array('streets',$arr)) $sql_array[] = $streets_array;
    if(in_array('cities',$arr)) $sql_array[] = $cities_array;
    if(in_array('business_center',$arr)) $sql_array[] = $db->fetchall("SELECT 'business_center' as type, 'bc' as url_type, id, title  FROM ".$sys_tables['business_centers']);        

    if(in_array('class',$arr)) {
        $sql_array[] = array(
            1=>array('type'=>'class', 'id'=>'a', 'url_type'=>'class','h1_title'=>'класса А','title'=>'a'),                                                       
            2=>array('type'=>'class', 'id'=>'b', 'url_type'=>'class','h1_title'=>'класса B', 'title'=>'b'),                                                       
            3=>array('type'=>'class', 'id'=>'bplus', 'url_type'=>'class','h1_title'=>'класса B+', 'title'=>'bplus'),                                                       
            4=>array('type'=>'class', 'id'=>'c', 'url_type'=>'class','h1_title'=>'класса C', 'title'=>'c'),                                                       
            5=>array('type'=>'class', 'id'=>'no', 'url_type'=>'class','h1_title'=>'без класса', 'title'=>'no')                                                      
        );                                                        
    }
    
    if(in_array('apartments',$arr)) {
        $sql_array[] = array(
            1=>array('type'=>'apartments', 'id'=>1, 'title_prepositional'=>'apartments','h1_title'=>'с апартаментами')                                                       
        );                                                        
    }
    
    $result = cartesian($sql_array);
    foreach($result as $k=>$item){
        //набор для ЧПУ и хлебных крошек
        $chpu = $query = array();
        $chpu[] = array($estate_type, 'Бизнес-центры');
        genChpu($estate_type, $deal_type = '', $estate_type, $chpu, $item, $arr);
    }
}      
die();
//Загородная
$params = array(
                'obj_type', 'places', 'district_areas',
                'cost_range', 'square_live_range', 'square_ground_range',
                'ownership', 'heating', 'bathroom', 'electricity', 'water_supply', 'gas', 'toilet', 'user_objects'
                
);

$result_array = array();
$arrr = pc_array_power_set($params, 3);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
    if( 
        ( in_array('places', $a) && count($a) > 2 ) ||
        ( in_array('places',$a) && ( in_array('district_areas',$a)) )
    ) unset($arrr[$k]);
    else if(!in_array($a, $result_array)) $result_array[] = $a;    
}
$deal_types = array('sell','rent');
$estate_type = 'country';
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        
        $sql_array = array();
        if(in_array('obj_type',$arr)) $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, alias as title FROM ".$sys_tables['object_type_groups']." WHERE `type` = 'country'");
        else  $sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>''));
        if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2  ");        
        if(in_array('cost_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT * FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'cost', $estate_type
            );
        }
        if(in_array('places',$arr)) $sql_array[] = $places_array;
        if(in_array('square_live_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'zhil_pl' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_live', $estate_type
            );
        }
        if(in_array('square_ground_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'pl_uchas' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_ground', $estate_type
            );
        }                        
        
        if(in_array('security',$arr)) {
            $sql_array[] = array(
                1=>array('type'=>'security', 'id'=>1, 'title_prepositional'=>'s_ohranoy','h1_title'=>'с охраной')                                                        
            );                                                        
        }
        
        if(in_array('electricity',$arr)) $sql_array[] = $db->fetchall("SELECT 'electricity' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['electricities']." ORDER BY title");
        if(in_array('ownership',$arr)) $sql_array[] = $db->fetchall("SELECT 'ownership' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['ownerships']." ORDER BY title");
        if(in_array('heating',$arr)) $sql_array[] = $db->fetchall("SELECT 'heating' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['heatings']." ORDER BY title");
        if(in_array('bathroom',$arr)) $sql_array[] = $db->fetchall("SELECT 'bathroom' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['bathrooms']." ORDER BY title");
        if(in_array('water_supply',$arr)) $sql_array[] = $db->fetchall("SELECT 'water_supply' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['water_supplies']." ORDER BY title");
        if(in_array('gas',$arr)) $sql_array[] = $db->fetchall("SELECT 'gas' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['gases']." ORDER BY title");
        if(in_array('toilet',$arr)) $sql_array[] = $db->fetchall("SELECT 'toilet' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['toilets']." WHERE id IN (3,4,5,10) ORDER BY title");
        if(in_array('user_objects',$arr)) {
            $sql_array[] = array(
                1=>array('type'=>'user_objects', 'id'=>1, 'title_prepositional'=>'private','h1_title'=>'от частных лиц'),
                2=>array('type'=>'user_objects', 'id'=>2, 'title_prepositional'=>'agency','h1_title'=>'от агентств')                                                      
            );                                                        
        }        
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'country/'.$deal_type;
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('country','Загородная');
            if(!empty($item[0]['title'])) {
                $chpu[] = array($deal_type.'/'.$item[0]['title'],($deal_type=='rent'?'Аренда ':'Продажа '));
            }
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();
            
            genChpu($estate_type, $deal_type, $query_catalogs, $chpu, $item, $arr);
        }
    }        
}
  die();
// Коттеджные поселки
$params = array(
                'object_type', 'cottage', 'cottage_districts', 'direction', 'developer'
);
$result_array = array();
$arrr = pc_array_power_set($params, 3);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
    if(                                      
        ( in_array('cottage', $a) && count($a) > 1 ) 
    ) unset($arrr[$k]);
    else if(!in_array($a, $result_array)) $result_array[] = $a;    
}
$deal_types = array('sell');
$estate_type = 'cottedzhnye_poselki';
foreach($result_array as $arr){
    
    $sql_array = array();
    
    if(in_array('cottage_districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'cottage_districts' as type, id, title  FROM ".$sys_tables['district_areas']);        
    if(in_array('direction',$arr)) $sql_array[] = $db->fetchall("SELECT 'direction' as type, id, title  FROM ".$sys_tables['directions']);        
    if(in_array('cottage',$arr)) $sql_array[] = $db->fetchall("SELECT 'cottage' as type, 'kp' as url_type, id, title  FROM ".$sys_tables['cottedzhnye_poselki']);        
    if(in_array('developer',$arr)) $sql_array[] = $db->fetchall("SELECT 
                                                                        'developer' as type,
                                                                        ".$sys_tables['cottages'].".id_user as id,  
                                                                        ".$sys_tables['agencies'].".title
                                                                FROM ".$sys_tables['cottages']." 
                                                                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['cottages'].".id_user
                                                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                                                WHERE ".$sys_tables['cottages'].".id_stady = 2 AND ".$sys_tables['users'].".id_agency > 0  AND ".$sys_tables['agencies'].".title!=''
                                                                GROUP BY  ".$sys_tables['cottages'].".id_user
                                                                ORDER BY `title`");

    if(in_array('object_type',$arr)) {
        $sql_array[] = array(
            1=>array('type'=>'object_type', 'id'=>1, 'title_prepositional'=>'uchastki','h1_title'=>'участки'),
            2=>array('type'=>'object_type', 'id'=>2, 'title_prepositional'=>'cottages','h1_title'=>'Коттеджи'),
            3=>array('type'=>'object_type', 'id'=>3, 'title_prepositional'=>'townhouses','h1_title'=>'Таунхаусы'),
            4=>array('type'=>'object_type', 'id'=>4, 'title_prepositional'=>'lats','h1_title'=>'Квартиры'),
        );                                                        
    }
    
    $result = cartesian($sql_array);
    foreach($result as $k=>$item){
        //набор для ЧПУ и хлебных крошек
        $chpu = $query = array();
        $chpu[] = array($estate_type, 'Коттеджные поселки');
        genChpu($estate_type, $deal_type = '', $estate_type, $chpu, $item, $arr);
    }
}  


// Жилые комплексы

//  build sell   ?district_areas=30574&districts=5&obj_type=1&rooms=2
// /элитность/тип_недвижимости/тип_объекта/тип_сделки/количество_комнат/посуточно/метро/район/район_ло/страна/с_фото/

$params = array(
                'streets', 'cities', 'class','apartments', 'low_rise', 'developer', 'housing_estate', 'subways', 'districts','district_areas',
                'build_complete', '214_fz'
);
$result_array = array();
$arrr = pc_array_power_set($params, 3);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
    if( 
        ( in_array('housing_estate', $a) && count($a) > 1 ) ||
        ( in_array('streets', $a) && count($a) > 2 ) ||
        ( in_array('cities', $a) && count($a) > 2 ) ||
        ( in_array('cities',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('streets',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('subways',$a) && ( in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('districts',$a) && ( in_array('district_areas',$a)) )
    ) unset($arrr[$k]);
    else if(!in_array($a, $result_array)) $result_array[] = $a;    
}
$deal_types = array('sell');
$estate_type = 'zhiloy_kompleks';
foreach($result_array as $arr){
    
    $sql_array = array();
    
    if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
    if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title FROM ".$sys_tables['districts']."  ");
    if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2  ");        
    if(in_array('streets',$arr)) $sql_array[] = $streets_array;
    if(in_array('cities',$arr)) $sql_array[] = $cities_array;
    if(in_array('class',$arr)) $sql_array[] = $db->fetchall("SELECT *, 'class' as type  FROM ".$sys_tables['housing_estate_classes']);        
    if(in_array('housing_estate',$arr)) $sql_array[] = $db->fetchall("SELECT 'housing_estate' as type, 'zhk' as url_type, id, title  FROM ".$sys_tables['housing_estates']);        
    if(in_array('build_complete',$arr)) $sql_array[] = $db->fetchall("SELECT 'build_complete' as type, '' as url_type, id, title_prepositional, title  FROM ".$sys_tables['build_complete']." WHERE id = 4 OR (decade = 0 AND `year` >= YEAR(CURDATE()))");        

    if(in_array('developer',$arr)) 
        $sql_array[] = $db->fetchall("SELECT 
                                            'developer' as type,
                                            ".$sys_tables['housing_estates'].".id_user as id,  
                                            ".$sys_tables['agencies'].".title
                                     FROM ".$sys_tables['agencies']." 
                                     RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                     RIGHT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['housing_estates'].".id_user = ".$sys_tables['users'].".id
                                     WHERE   ".$sys_tables['users'].".id > 0  AND ".$sys_tables['agencies'].".title!='' AND ".$sys_tables['housing_estates'].".published = 1
                                     GROUP BY  ".$sys_tables['users'].".id
                                     ORDER BY ".$sys_tables['agencies'].".title");

                                 
    if(in_array('low_rise',$arr)) {
        $sql_array[] = array(
            1=>array('type'=>'low_rise', 'id'=>1, 'title_prepositional'=>'maloetazhnyi','h1_title'=>'малоэтажная застройка')                                                       
        );                                                        
    }
    if(in_array('214_fz',$arr)) {
        $sql_array[] = array(
            1=>array('type'=>'214_fz', 'id'=>1, 'title_prepositional'=>'214_fz','h1_title'=>'с ФЗ 214')                                                       
        );                                                        
    }
    
    if(in_array('apartments',$arr)) {
        $sql_array[] = array(
            1=>array('type'=>'apartments', 'id'=>1, 'title_prepositional'=>'apartments','h1_title'=>'с апартаментами')                                                       
        );                                                        
    }
    
    $result = cartesian($sql_array);
    foreach($result as $k=>$item){
        //набор для ЧПУ и хлебных крошек
        $chpu = $query = array();
        $chpu[] = array($estate_type, 'Жилые комплексы');
        genChpu($estate_type, $deal_type = '', $estate_type, $chpu, $item, $arr);
    }
}        

//Зарубежка

$params = array( 'obj_type', 'country' );

$result_array = array();
$arrr = pc_array_power_set($params, 3);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
        if(!in_array($a, $result_array)) $result_array[] = $a;    
}
$deal_types = array('sell','rent');
$estate_type = 'inter';
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        
        $sql_array = array();
        if(in_array('obj_type',$arr)) $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, alias as title FROM ".$sys_tables['object_type_groups']." WHERE `type` = 'inter'");
        else  $sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>''));

        if(in_array('country',$arr)) $sql_array[] = $db->fetchall("SELECT 'country' as type, '' as url_type, id, title_genitive as title_prepositional, title FROM ".$sys_tables['inter_countries']." ORDER BY title");


        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'inter/'.$deal_type;
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('inter','Зарубежная');
            if(!empty($item[0]['title'])) {
                $chpu[] = array($deal_type.'/'.$item[0]['title'],($deal_type=='rent'?'Аренда ':'Продажа '));
            }
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();
            
            genChpu($estate_type, $deal_type, $query_catalogs, $chpu, $item, $arr);
        }
    }        
}

//Строящаяся

//  build sell   ?district_areas=30574&districts=5&obj_type=1&rooms=2
// /элитность/тип_недвижимости/тип_объекта/тип_сделки/количество_комнат/посуточно/метро/район/район_ло/страна/с_фото/

$params = array(
                'rooms','streets','cities','subways','districts','district_areas',
                'cost_range', 'square_full_range', 'square_live_range', 'square_kitchen_range',
                'class', 'building_type', 'build_complete', 'user_objects', 'toilet', 'decoration', 'balcon', 'elevator'
);
$result_array = array();
$arrr = pc_array_power_set($params, 3);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
    if( 
        ( in_array('cities', $a) && count($a) > 2 ) ||
        ( in_array('cities',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('streets', $a) && count($a) > 2 ) ||
        ( in_array('streets',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('subways',$a) && ( in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('districts',$a) && ( in_array('district_areas',$a)) ) || 
        ( in_array('cost_range',$a) && ( in_array('class',$a)) ) 
    ) unset($arrr[$k]);
    else if(!in_array($a, $result_array)) $result_array[] = $a;    
}
$deal_types = array('sell');
$estate_type = 'build';
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        
        $sql_array = array();

        if(in_array('rooms',$arr)) {
            $sql_array[] = array(
                0=>array('type'=>'rooms','id'=>0,'title'=>0,'h1_title'=>'квартир-студий'),
                1=>array('type'=>'rooms','id'=>1,'title'=>1,'h1_title'=>'однокомнатных квартир'),
                2=>array('type'=>'rooms','id'=>2,'title'=>2,'h1_title'=>'двухкомнатных квартир'),
                3=>array('type'=>'rooms','id'=>3,'title'=>3,'h1_title'=>'трехкомнатных квартир'),
                4=>array('type'=>'rooms','id'=>4,'title'=>4,'h1_title'=>'четырехкомнатных квартир'),                                                        
            );                                                        
        }
 
        if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
        if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title FROM ".$sys_tables['districts']."  ");
        if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2  ");        
        if(in_array('streets',$arr)) $sql_array[] = $streets_array;
        if(in_array('cities',$arr)) $sql_array[] = $cities_array;
        if(in_array('class',$arr)) $sql_array[] = $db->fetchall("SELECT *, 'class' as type  FROM ".$sys_tables['housing_estate_classes']);        
        if(in_array('building_type',$arr)) $sql_array[] = $db->fetchall("SELECT 'building_type' as type, '' as url_type, id, title  FROM ".$sys_tables['building_types']);        
        if(in_array('build_complete',$arr)) $sql_array[] = $db->fetchall("SELECT 'build_complete' as type, '' as url_type, id, title_prepositional, title  FROM ".$sys_tables['build_complete']." WHERE id = 4 OR (decade = 0 AND `year` >= YEAR(CURDATE()))");        
        if(in_array('toilet',$arr)) $sql_array[] = $db->fetchall("SELECT 'toilet' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['toilets']." WHERE id IN (3,4,5,10) ORDER BY title");
         
        if(in_array('user_objects',$arr)) {
            $sql_array[] = array(
                1=>array('type'=>'user_objects', 'id'=>1, 'title_prepositional'=>'private','h1_title'=>'от частных лиц'),
                2=>array('type'=>'user_objects', 'id'=>2, 'title_prepositional'=>'agency','h1_title'=>'от агентств'),                                                        
                3=>array('type'=>'user_objects', 'id'=>3, 'title_prepositional'=>'developer','h1_title'=>'от застройщика')                                                        
            );                                                        
        }
        if(in_array('balcon',$arr)) $sql_array[] = $db->fetchall("SELECT 'balcon' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['balcons']." ORDER BY title");
        if(in_array('elevator',$arr)) $sql_array[] = $db->fetchall("SELECT 'elevator' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['elevators']." ORDER BY title");
        if(in_array('cost_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT * FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'cost', $estate_type
            );
        }
        if(in_array('square_full_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'ob_pl' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_full', $estate_type
            );
        }
        if(in_array('square_live_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'pl_kom' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_live', $estate_type
            );
        }
        if(in_array('square_kitchen_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'pl_kuh' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_kitchen', $estate_type
            );
        }                        
        
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'build/'.$deal_type;
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('build','Новостройки');
            if(!empty($item[0]['title'])) {
                $chpu[] = array($deal_type,($deal_type=='rent'?'Аренда ':'Продажа '));
            }
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();
            
            genChpu($estate_type, $deal_type, $query_catalogs, $chpu, $item, $arr);
        }
    }        
}
*/                                                       
//Жилая 
//  live sell   ?district_areas=30574&districts=5&obj_type=1&rooms=2
// /элитность/тип_недвижимости/тип_объекта/тип_сделки/количество_комнат/посуточно/метро/район/район_ло/страна/с_фото/

$params = array(
                'obj_type','rooms','rooms_sale','subways','districts','district_areas',
                'by_the_day', 'building_type', 'toilet', 'facing', 'balcon', 'elevator', 'user_objects'
);
$result_array = array();
$arrr = pc_array_power_set($params, 1);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
    if( 
        ( in_array('cities', $a) && count($a) > 2 ) ||
        ( in_array('cities',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('streets', $a) && count($a) > 2 ) ||
        ( in_array('streets',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('subways',$a) && ( in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('districts',$a) && ( in_array('district_areas',$a)) ) || 
        ( in_array('rooms_sale',$a) && ( in_array('rooms_sale',$a)) ) 
    ) unset($arrr[$k]);
    else if(!in_array($a, $result_array)) $result_array[] = $a;    
}

$deal_types = array('sell','rent');
$estate_type = 'live';
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        
        $sql_array = array();
        if(in_array('obj_type',$arr)) {
            $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, alias as title FROM ".$sys_tables['object_type_groups']." WHERE `type` = 'live'");
            if(in_array('rooms',$arr)) {
                $sql_array[] = array(
                    0=>array('type'=>'rooms','id'=>0,'title'=>0,'h1_title'=>'квартир-студий'),
                    1=>array('type'=>'rooms','id'=>1,'title'=>1,'h1_title'=>'однокомнатных квартир'),
                    2=>array('type'=>'rooms','id'=>2,'title'=>2,'h1_title'=>'двухкомнатных квартир'),
                    3=>array('type'=>'rooms','id'=>3,'title'=>3,'h1_title'=>'трехкомнатных квартир'),
                    4=>array('type'=>'rooms','id'=>4,'title'=>4,'h1_title'=>'четырехкомнатных квартир'),                                                        
                );                                                        
            }
            if(in_array('rooms_sale',$arr)) {
                $sql_array[] = array(
                    1=>array('type'=>'rooms_sale','id'=>1,'title'=>1,'h1_title'=>'одной комнаты'),
                    2=>array('type'=>'rooms_sale','id'=>2,'title'=>2,'h1_title'=>'двух комнат'),
                    3=>array('type'=>'rooms_sale','id'=>3,'title'=>3,'h1_title'=>'трех комнат'),
                    4=>array('type'=>'rooms_sale','id'=>4,'title'=>4,'h1_title'=>'четырех комнат'),                                                        
                );                                                        
            }
        }
        else  $sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>''));
        if(in_array('by_the_day',$arr) && $deal_type=='rent') $sql_array[] = array(0=>array('type'=>'by_the_day', 'id'=>1));
        if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
        if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title FROM ".$sys_tables['districts']."  ");
        if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2  ");        
        if(in_array('streets',$arr)) $sql_array[] = $streets_array;
        if(in_array('cities',$arr)) $sql_array[] = $cities_array;
        if(in_array('building_type',$arr)) $sql_array[] = $db->fetchall("SELECT 'building_type' as type, '' as url_type, id, title  FROM ".$sys_tables['building_types']);        
        if(in_array('build_complete',$arr)) $sql_array[] = $db->fetchall("SELECT 'build_complete' as type, '' as url_type, id, title_prepositional, title  FROM ".$sys_tables['build_complete']." WHERE id = 4 OR (decade = 0 AND `year` >= YEAR(CURDATE()))");        
        if(in_array('toilet',$arr)) $sql_array[] = $db->fetchall("SELECT 'toilet' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['toilets']." WHERE id IN (3,4,5,10) ORDER BY title");
        if(in_array('facing',$arr)) $sql_array[] = $db->fetchall("SELECT 'facing' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['facings']." ORDER BY title");
        if(in_array('balcon',$arr)) $sql_array[] = $db->fetchall("SELECT 'balcon' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['balcons']." ORDER BY title");
        if(in_array('elevator',$arr)) $sql_array[] = $db->fetchall("SELECT 'elevator' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['elevators']." ORDER BY title");
        if(in_array('cost_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT * FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'cost', $estate_type
            );
        }
        if(in_array('square_full_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'ob_pl' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_full', $estate_type
            );
        }
        if(in_array('user_objects',$arr)) {
            $sql_array[] = array(
                1=>array('type'=>'user_objects', 'id'=>1, 'title_prepositional'=>'private','h1_title'=>'от частных лиц'),
                2=>array('type'=>'user_objects', 'id'=>2, 'title_prepositional'=>'agency','h1_title'=>'от агентств'),                                                        
                2=>array('type'=>'user_objects', 'id'=>2, 'title_prepositional'=>'developer','h1_title'=>'от застройщика')                                                        
            );                                                        
        }
        
        if(in_array('square_live_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'pl_kom' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_live', $estate_type
            );
        }
        if(in_array('square_kitchen_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'pl_kuh' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_kitchen', $estate_type
            );
        }                        
        
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'live/'.$deal_type;
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('live','Жилая');
            if(!empty($item[0]['title'])) {
                $chpu[] = array($deal_type.'/'.$item[0]['title'],($deal_type=='rent'?'Аренда ':'Продажа '));
            }
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();
            
            genChpu($estate_type, $deal_type, $query_catalogs, $chpu, $item, $arr);
        }
    }        
}

//коммерческая     

$params = array(
                'parking', 'obj_type','streets','cities','subways','districts','district_areas',
                'cost_range', 'square_full_range', 'square_usefull_range', 'square_ground_range',
                'heating', 'security', 'electricity', 'facing', 'enter', 'user_objects'
                
);

$result_array = array();
$arrr = pc_array_power_set($params, 3);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
    if( 
        ( in_array('cities', $a) && count($a) > 2 ) ||
        ( in_array('cities',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('streets', $a) && count($a) > 2 ) ||
        ( in_array('streets',$a) && ( in_array('subways',$a) || in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('subways',$a) && ( in_array('districts',$a) || in_array('district_areas',$a)) ) || 
        ( in_array('districts',$a) && ( in_array('district_areas',$a)) )
    ) unset($arrr[$k]);
    else if(!in_array($a, $result_array)) $result_array[] = $a;    
}
$deal_types = array('sell','rent');
$estate_type = 'commercial';
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        
        $sql_array = array();
        if(in_array('obj_type',$arr)) $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, alias as title FROM ".$sys_tables['object_type_groups']." WHERE `type` = 'commercial'");
        else  $sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>''));
        if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
        if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title FROM ".$sys_tables['districts']."  ");
        if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2  ");        
        if(in_array('streets',$arr)) $sql_array[] = $streets_array;
        if(in_array('cities',$arr)) $sql_array[] = $cities_array;
        if(in_array('cost_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT * FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'cost', $estate_type
            );
        }
        if(in_array('square_full_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'ob_pl' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_full', $estate_type
            );
        }
        if(in_array('square_usefull_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'pl_polez' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_usefull', $estate_type
            );
        }
        if(in_array('square_ground_range',$arr)) {
            $sql_array[] = $db->fetchall("SELECT *, 'pl_uchas' as url_type FROM ".$sys_tables['estate_search_params']." WHERE type = ? AND estate_type = ? ORDER BY type, id",
                false, 'square_ground', $estate_type
            );
        }                        
        if(in_array('heating',$arr)) {
            $sql_array[] = array(
                1=>array('type'=>'heating', 'id'=>1, 'title_prepositional'=>'s_otopleniem','h1_title'=>'с отоплением'),
                2=>array('type'=>'heating', 'id'=>2, 'title_prepositional'=>'bez_otopleniya','h1_title'=>'без отопления')                                                      
            );                                                        
        }        
        if(in_array('user_objects',$arr)) {
            $sql_array[] = array(
                1=>array('type'=>'user_objects', 'id'=>1, 'title_prepositional'=>'private','h1_title'=>'от частных лиц'),
                2=>array('type'=>'user_objects', 'id'=>2, 'title_prepositional'=>'agency','h1_title'=>'от агентств')                                                      
            );                                                        
        }
        
        if(in_array('parking',$arr)) {
            $sql_array[] = array(
                1=>array('type'=>'parking', 'id'=>1, 'title_prepositional'=>'s_parkingom','h1_title'=>'с паркингом'),                                                        
                2=>array('type'=>'parking', 'id'=>2, 'title_prepositional'=>'bez_parkinga','h1_title'=>'без паркингом')                                                        
            );                                                        
        }
        
        if(in_array('security',$arr)) {
            $sql_array[] = array(
                1=>array('type'=>'security', 'id'=>1, 'title_prepositional'=>'s_ohranoy','h1_title'=>'с охраной')                                                        
            );                                                        
        }
        
        if(in_array('electricity',$arr)) $sql_array[] = $db->fetchall("SELECT 'electricity' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['electricities']." ORDER BY title");
        if(in_array('facing',$arr)) $sql_array[] = $db->fetchall("SELECT 'facing' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['facings']." ORDER BY title");
        if(in_array('enter',$arr)) $sql_array[] = $db->fetchall("SELECT 'enter' as type, '' as url_type, id, title_prepositional, title FROM ".$sys_tables['enters']." ORDER BY title");
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'commercial/'.$deal_type;
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('commercial','Коммерческая');
            if(!empty($item[0]['title'])) {
                $chpu[] = array($deal_type.'/'.$item[0]['title'],($deal_type=='rent'?'Аренда ':'Продажа '));
            }
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();
            
            genChpu($estate_type, $deal_type, $query_catalogs, $chpu, $item, $arr);
        }
    }        
} 
  


echo "Lines updated: ".$counter."\r\n";

function pc_array_power_set($array, $limit = false) {
    // инициализируем пустым множеством
    $results = array(array());
    foreach ($array as $element){
        foreach ($results as $combination){
            $array = array_merge($combination,array($element));
            if(empty($limit) || count($array)<=$limit) array_push($results, array_merge($array));
        }
    }
    return $results;
}
function cartesian($input) {
    $result = array();

    while (list($key, $values) = each($input)) {
        // If a sub-array is empty, it doesn't affect the cartesian product
        if (empty($values)) {
            continue;
        }

        // Seeding the product array with the values from the first sub-array
        if (empty($result)) {
            foreach($values as $value) {
                $result[] = array($key => $value);
            }
        }
        else {
            // Second and subsequent input sub-arrays work like this:
            //   1. In each existing array inside $product, add an item with
            //      key == $key and value == first item in input sub-array
            //   2. Then, for each remaining item in current input sub-array,
            //      add a copy of each existing array inside $product with
            //      key == $key and value == first item of input sub-array

            // Store all items to be added to $product here; adding them
            // inside the foreach will result in an infinite loop
            $append = array();

            foreach($result as &$product) {
                // Do step 1 above. array_shift is not the most efficient, but
                // it allows us to iterate over the rest of the items with a
                // simple foreach, making the code short and easy to read.
                $product[$key] = array_shift($values);

                // $product is by reference (that's why the key we added above
                // will appear in the end result), so make a copy of it here
                $copy = $product;

                // Do step 2 above.
                foreach($values as $item) {
                    $copy[$key] = $item;
                    $append[] = $copy;
                }

                // Undo the side effecst of array_shift
                array_unshift($values, $product[$key]);
            }

            // Out of the foreach, we can add to $results now
            $result = array_merge($result, $append);
        }
    }

    return $result;
}

function genChpu($estate_type, $deal_type, $query_catalogs, $chpu, $item, $arr){
    global $db, $sys_tables;
    foreach($item as $k=>$values){
        //формирование параметров для справочных данных
        if(!empty($values['from_value']) || !empty($values['to_value'])){
            $values['type'] = $values['type'].'_range';
            if(!empty($values['deal_type']) && $values['deal_type'] != $deal_type) break; 

            if(!empty($values['id_type_object'])){
                if(!in_array('obj_type',$arr)) break;
                if($item[array_search('obj_type', $arr)]['id'] != $values['id_type_object']) break;
            }
            $title_prepositional = array();
            if(!empty($values['url_type'])) $title_prepositional[] = $values['url_type'];
            if( $values['from_value'] > 0 ) $title_prepositional[] = 'от ' . rtrim(rtrim(number_format($values['from_value'], 2, ".", ""), "0"), ".");
            if( $values['to_value'] > 0 ) $title_prepositional[] = 'до '.rtrim(rtrim(number_format($values['to_value'], 2, ".", ""), "0"), ".");
            $title_prepositional[] = $values['prefix'];
            $values['title_prepositional'] = implode('_', $title_prepositional);
            
        }
        //формирование параметров запроса
        if((!empty($values['type']) && !empty($values['id']) || (!empty($values['type']) && isset($values['id']) && $values['id'] == 0)) ) $query[$values['type']] = $values['id'];
        //формирование ЧПУ
        $chpu_title = '';
        if( ($k>0 && $estate_type!='build' ) || in_array( $estate_type, array( 'build' , 'zhiloy_kompleks', 'business_centers', 'cottedzhnye_poselki' ) ) ){
            if(!empty($values['title_prepositional'])){
                $chpu_title = explode('_',Convert::chpuTitle($values['title_prepositional'], '.'));
                array_walk(
                    $chpu_title, 
                    function(&$value) {
                        $value = substr( $value, 0, Validate::isConsonantsEn( substr($value, 3, 1) ) ? 4 : 5 );
                    }
                );
                $chpu_title = implode('_', $chpu_title);
            }
            if(!empty($chpu_title))  $chpu[] = array($chpu_title); 
            else if(empty($values['title']) && !isset($values['title'])) $chpu[] = array($values['type']);
            else {
                $chpu[] = array( (isset($values['url_type']) ? ( !empty($values['url_type']) ? $values['url_type']. '-' : '' ) : ( $values['type'] == 'geodata' ? $values['geodata_type'] : $values['type'] ) . '-' )  .createCHPUTitle($values['title']));
            }
        }
    }
    if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
         
    $sql_chpu = array();
    foreach($chpu as $k_chpu=>$v_chpu) $sql_chpu[] = $v_chpu[0]; 

    $description = "";
    $page_seo_item = $db->fetch("SELECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND pretty_url = ?", $query_catalogs, implode('/',$sql_chpu));


    $estate_search = new EstateSearch();
    list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams('/' . $query_catalogs, false, 'live', $deal_type);

    EstateSubscriptions::Init( '/' . $query_catalogs );

    $h1_title = trim(EstateSubscriptions::getTitle(false, $parameters, true));
    $h1_title =  preg_replace('/ {2,}/',' ',$h1_title);

    if(empty($page_seo_item)){
        if(!empty($h1_title)){
            $insert = $db->querys("
                               INSERT IGNORE INTO common.pages_seo
                               SET 
                                  url = '".$query_catalogs."',
                                  pretty_url = '".implode('/',$sql_chpu)."',
                                  h1_title = '".$h1_title."',
                                  title = '".$h1_title."',
                                  description = '".$description."'
                               ON DUPLICATE KEY UPDATE
                                  h1_title = '".$h1_title."',
                                  title = '".$h1_title."',
                                  description = '".$description."'
            ");
        }
    } 
}

 ?>        