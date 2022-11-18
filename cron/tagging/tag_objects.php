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

if (is_running($_SERVER['PHP_SELF'])) {
    file_put_contents('cron/tagging/log.log', "'Already running'\n" );
    die('Already running');
}

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/tagging/taggin_gerror.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
//include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
// вспомогательные таблицы
$sys_tables = Config::$sys_tables;
// кол-во объектов для тегирования в каждом сегменте рынка
$process_count = array('build'=>2000, 'live'=>2000, 'commercial'=>100, 'country'=>100, 'foreign'=>20);

//перетегирование объектов у которых были теги, но по какой-то причине!!!!! удалились из таблицы
foreach($process_count as $estate_type=>$count){
    $db->querys("
                UPDATE ".$sys_tables[$estate_type]." SET ".$sys_tables[$estate_type].".tag_date = '2001-01-01 00-00-00' WHERE id IN (
                    SELECT a.id FROM 
                    (
                        SELECT ".$sys_tables[$estate_type].".id FROM ".$sys_tables[$estate_type]."
                        LEFT JOIN ".$sys_tables["tags_".$estate_type]." ON ".$sys_tables[$estate_type].".id = ".$sys_tables["tags_".$estate_type].".id_object
                        WHERE ".$sys_tables[$estate_type].".published=1 and ".$sys_tables["tags_".$estate_type].".id IS NULL
                    ) a 
                )"
    );
}    

//типы тегов (справочник)
$tag_types = $db->fetchall("SELECT * FROM estate.tag_types", 'id');

// Выборка объектов для тегирования (Новостройки)
$obj_list = $db->fetchall("SELECT main.*
                                 ,".$sys_tables['subways'].".title as subway_title 
                                 ,".$sys_tables['districts'].".title as district_title 
                                 ,".$sys_tables['building_types'].".title as building_type_title 
                                 ,'квартира' as type_object_title 
                                 ,CONCAT_WS(' ',geo_reg.shortname,geo_reg.offname) as region_title 
                                 ,CONCAT_WS(' ',geo_area.shortname,geo_area.offname) as area_title 
                                 ,CONCAT_WS(' ',geo_city.shortname,geo_city.offname) as city_title 
                                 ,CONCAT_WS(' ',geo_place.shortname,geo_place.offname) as place_title 
                                 ,CONCAT_WS(' ',geo_street.shortname,geo_street.offname) as street_title 
                           FROM ".$sys_tables['build']." main
                           LEFT JOIN ".$sys_tables['subways']." ON main.id_subway = ".$sys_tables['subways'].".id
                           LEFT JOIN ".$sys_tables['districts']." ON main.id_district = ".$sys_tables['districts'].".id
                           LEFT JOIN ".$sys_tables['building_types']." ON main.id_building_type = ".$sys_tables['building_types'].".id
                           LEFT JOIN ".$sys_tables['geodata']." geo_reg ON geo_reg.a_level=1 AND main.id_region = geo_reg.id_region
                           LEFT JOIN ".$sys_tables['geodata']." geo_area ON geo_area.a_level=2 AND main.id_region = geo_area.id_region AND main.id_area = geo_area.id_area
                           LEFT JOIN ".$sys_tables['geodata']." geo_city ON geo_city.a_level=3 AND main.id_region = geo_city.id_region AND main.id_area = geo_city.id_area AND main.id_city = geo_city.id_city
                           LEFT JOIN ".$sys_tables['geodata']." geo_place ON geo_place.a_level=4 AND main.id_region = geo_place.id_region AND main.id_area = geo_place.id_area AND main.id_city = geo_place.id_city AND main.id_place = geo_place.id_place
                           LEFT JOIN ".$sys_tables['geodata']." geo_street ON geo_street.a_level=5 AND main.id_region = geo_street.id_region AND main.id_area = geo_street.id_area AND main.id_city = geo_street.id_city AND main.id_place = geo_street.id_place AND main.id_street = geo_street.id_street
                           WHERE main.published =1 AND DATEDIFF(NOW(),main.tag_date)>10
                           ORDER BY main.tag_date LIMIT ".$process_count['build']
                           , 'id');
tagging($obj_list, 'build');

// Выборка объектов для тегирования (Зарубежная недвижимость)
$obj_list = $db->fetchall("SELECT main.*
                                 ,".$sys_tables['foreign_countries'].".title as country_title 
                                 ,".$sys_tables['type_objects_inter'].".title as type_object_title 
                           FROM ".$sys_tables['foreign']." main
                           LEFT JOIN ".$sys_tables['foreign_countries']." ON main.id_country = ".$sys_tables['foreign_countries'].".id
                           LEFT JOIN ".$sys_tables['type_objects_inter']." ON main.id_type_object = ".$sys_tables['type_objects_inter'].".id
                           WHERE main.published =1 AND DATEDIFF(NOW(),main.tag_date)>10
                           ORDER BY main.tag_date LIMIT ".$process_count['foreign']
                           , 'id');
tagging($obj_list, 'foreign');

// Выборка объектов для тегирования (Жилые)
$obj_list = $db->fetchall("SELECT main.*
                                 ,".$sys_tables['subways'].".title as subway_title 
                                 ,".$sys_tables['districts'].".title as district_title 
                                 ,".$sys_tables['building_types'].".title as building_type_title 
                                 ,".$sys_tables['type_objects_live'].".title as type_object_title 
                                 ,CONCAT_WS(' ',geo_reg.shortname,geo_reg.offname) as region_title 
                                 ,CONCAT_WS(' ',geo_area.shortname,geo_area.offname) as area_title 
                                 ,CONCAT_WS(' ',geo_city.shortname,geo_city.offname) as city_title 
                                 ,CONCAT_WS(' ',geo_place.shortname,geo_place.offname) as place_title 
                                 ,CONCAT_WS(' ',geo_street.shortname,geo_street.offname) as street_title 
                           FROM ".$sys_tables['live']." main
                           LEFT JOIN ".$sys_tables['subways']." ON main.id_subway = ".$sys_tables['subways'].".id
                           LEFT JOIN ".$sys_tables['districts']." ON main.id_district = ".$sys_tables['districts'].".id
                           LEFT JOIN ".$sys_tables['building_types']." ON main.id_building_type = ".$sys_tables['building_types'].".id
                           LEFT JOIN ".$sys_tables['type_objects_live']." ON main.id_type_object = ".$sys_tables['type_objects_live'].".id
                           LEFT JOIN ".$sys_tables['geodata']." geo_reg ON geo_reg.a_level=1 AND main.id_region = geo_reg.id_region
                           LEFT JOIN ".$sys_tables['geodata']." geo_area ON geo_area.a_level=2 AND main.id_region = geo_area.id_region AND main.id_area = geo_area.id_area
                           LEFT JOIN ".$sys_tables['geodata']." geo_city ON geo_city.a_level=3 AND main.id_region = geo_city.id_region AND main.id_area = geo_city.id_area AND main.id_city = geo_city.id_city
                           LEFT JOIN ".$sys_tables['geodata']." geo_place ON geo_place.a_level=4 AND main.id_region = geo_place.id_region AND main.id_area = geo_place.id_area AND main.id_city = geo_place.id_city AND main.id_place = geo_place.id_place
                           LEFT JOIN ".$sys_tables['geodata']." geo_street ON geo_street.a_level=5 AND main.id_region = geo_street.id_region AND main.id_area = geo_street.id_area AND main.id_city = geo_street.id_city AND main.id_place = geo_street.id_place AND main.id_street = geo_street.id_street
                           WHERE main.published =1 AND DATEDIFF(NOW(),main.tag_date)>10
                           ORDER BY main.tag_date LIMIT ".$process_count['live']
                           , 'id');
tagging($obj_list, 'live');

// Выборка объектов для тегирования (Коммерческая)
$obj_list = $db->fetchall("SELECT main.*
                                 ,".$sys_tables['subways'].".title as subway_title 
                                 ,".$sys_tables['districts'].".title as district_title 
                                 ,".$sys_tables['type_objects_commercial'].".title as type_object_title 
                                 ,CONCAT_WS(' ',geo_reg.shortname,geo_reg.offname) as region_title 
                                 ,CONCAT_WS(' ',geo_area.shortname,geo_area.offname) as area_title 
                                 ,CONCAT_WS(' ',geo_city.shortname,geo_city.offname) as city_title 
                                 ,CONCAT_WS(' ',geo_place.shortname,geo_place.offname) as place_title 
                                 ,CONCAT_WS(' ',geo_street.shortname,geo_street.offname) as street_title 
                           FROM ".$sys_tables['commercial']." main
                           LEFT JOIN ".$sys_tables['subways']." ON main.id_subway = ".$sys_tables['subways'].".id
                           LEFT JOIN ".$sys_tables['districts']." ON main.id_district = ".$sys_tables['districts'].".id
                           LEFT JOIN ".$sys_tables['type_objects_commercial']." ON main.id_type_object = ".$sys_tables['type_objects_commercial'].".id
                           LEFT JOIN ".$sys_tables['geodata']." geo_reg ON geo_reg.a_level=1 AND main.id_region = geo_reg.id_region
                           LEFT JOIN ".$sys_tables['geodata']." geo_area ON geo_area.a_level=2 AND main.id_region = geo_area.id_region AND main.id_area = geo_area.id_area
                           LEFT JOIN ".$sys_tables['geodata']." geo_city ON geo_city.a_level=3 AND main.id_region = geo_city.id_region AND main.id_area = geo_city.id_area AND main.id_city = geo_city.id_city
                           LEFT JOIN ".$sys_tables['geodata']." geo_place ON geo_place.a_level=4 AND main.id_region = geo_place.id_region AND main.id_area = geo_place.id_area AND main.id_city = geo_place.id_city AND main.id_place = geo_place.id_place
                           LEFT JOIN ".$sys_tables['geodata']." geo_street ON geo_street.a_level=5 AND main.id_region = geo_street.id_region AND main.id_area = geo_street.id_area AND main.id_city = geo_street.id_city AND main.id_place = geo_street.id_place AND main.id_street = geo_street.id_street
                           WHERE main.published =1 AND DATEDIFF(NOW(),main.tag_date)>10
                           ORDER BY main.tag_date LIMIT ".$process_count['commercial']
                           , 'id');
tagging($obj_list, 'commercial');                           
// Выборка объектов для тегирования (Загородная)
$obj_list = $db->fetchall("SELECT main.*
                                 ,".$sys_tables['subways'].".title as subway_title 
                                 ,".$sys_tables['type_objects_country'].".title as type_object_title 
                                 ,CONCAT_WS(' ',geo_reg.shortname,geo_reg.offname) as region_title 
                                 ,CONCAT_WS(' ',geo_area.shortname,geo_area.offname) as area_title 
                                 ,CONCAT_WS(' ',geo_city.shortname,geo_city.offname) as city_title 
                                 ,CONCAT_WS(' ',geo_place.shortname,geo_place.offname) as place_title 
                                 ,CONCAT_WS(' ',geo_street.shortname,geo_street.offname) as street_title 
                           FROM ".$sys_tables['country']." main
                           LEFT JOIN ".$sys_tables['subways']." ON main.id_subway = ".$sys_tables['subways'].".id
                           LEFT JOIN ".$sys_tables['type_objects_country']." ON main.id_type_object = ".$sys_tables['type_objects_country'].".id
                           LEFT JOIN ".$sys_tables['geodata']." geo_reg ON geo_reg.a_level=1 AND main.id_region = geo_reg.id_region
                           LEFT JOIN ".$sys_tables['geodata']." geo_area ON geo_area.a_level=2 AND main.id_region = geo_area.id_region AND main.id_area = geo_area.id_area
                           LEFT JOIN ".$sys_tables['geodata']." geo_city ON geo_city.a_level=3 AND main.id_region = geo_city.id_region AND main.id_area = geo_city.id_area AND main.id_city = geo_city.id_city
                           LEFT JOIN ".$sys_tables['geodata']." geo_place ON geo_place.a_level=4 AND main.id_region = geo_place.id_region AND main.id_area = geo_place.id_area AND main.id_city = geo_place.id_city AND main.id_place = geo_place.id_place
                           LEFT JOIN ".$sys_tables['geodata']." geo_street ON geo_street.a_level=5 AND main.id_region = geo_street.id_region AND main.id_area = geo_street.id_area AND main.id_city = geo_street.id_city AND main.id_place = geo_street.id_place AND main.id_street = geo_street.id_street
                           WHERE main.published =1 AND DATEDIFF(NOW(),main.tag_date)>10
                           ORDER BY main.tag_date LIMIT ".$process_count['country']
                           , 'id');
tagging($obj_list, 'country');
/**
* Тут нужно дописать такие же выборки и запуски для всех остальных типов недвижимости
*/


function tagging($obj_list, $estate_type){
    global $db,$tag_types,$sys_tables;
    // выделение тегов в объектах
    foreach($obj_list as $object) {
        // список тегов объекта
        $tags = define_tags($object, $estate_type);
        $values = $tag_names = $tags_ids_delete = array();
        foreach($tags as $tkey=>$tval){
            // значения тегов для записи в таблицу тегов
            $values[] = $db->quoted($tkey).', '.$db->quoted($tval).', '.$tag_types[$tkey]['type_weight'];
            // чистые значения тегов
            $tag_names[] = $tval;
        }
        /*
        //удаляем теги которые изменились или которые удалились
        $object_tags = $db->fetchall("SELECT ".$sys_tables['tag_'.$estate_type].".*, ".$sys_tables['tags'].".id_tagtype, ".$sys_tables['tags'].".tag_name
                                      FROM  ".$sys_tables['tag_'.$estate_type]."
                                      LEFT JOIN ".$sys_tables['tags']." ON ".$sys_tables['tags'].".id = ".$sys_tables['tag_'.$estate_type].".id_tag
                                      WHERE ".$sys_tables['tag_'.$estate_type].".id_object = ".$object['id']);
        if(!empty($object_tags)){
            foreach($object_tags as $k=>$object_tag){
                $old_tag = $object_tag['tag_name'];
                $new_tag = !empty($tags[$object_tag['id_tagtype']]) ? $tags[$object_tag['id_tagtype']] : false;
                if(empty($new_tag) || $new_tag!=$old_tag){
                    $tags_ids_delete[] =  $object_tag['id_tag'];
                } 
		
                
            }
            if(!empty($tags_ids_delete)) {
                $tags_ids_delete = '('.implode(", ",$tags_ids_delete).')';
                echo $object['id'].":".$object_tag['id_tag'].':'.$tags_ids_delete."--".$estate_type."\n";
                $db->querys("DELETE FROM ".$sys_tables['tag_'.$estate_type]." WHERE id_object = ? AND id_tag IN ".$tags_ids_delete,$object['id']);
                $db->querys("UPDATE ".$sys_tables['tags']." SET tag_count = tag_count -1 WHERE id IN ".$tags_ids_delete);
            }
		
        }
        */
        // запись тегов в таблицу (или обновление)
        $res = $db->querys("INSERT INTO ".$sys_tables['estate_tags']." (id_tagtype,tag_name,tag_weight) 
                                VALUES (".implode("), (",$values).")
                                ON DUPLICATE KEY UPDATE id_tagtype = VALUES(id_tagtype)");
        // получаем ID тегов
        $row = $db->fetchall("SELECT id, tag_weight FROM ".$sys_tables['estate_tags']." WHERE tag_name IN ('".implode("','", $tag_names)."')", 'id');
        $tag_links = array();
        foreach($row as $tag_id=>$tag) {
            $tag_links[] = $tag_id.','.$object['id'].','.$tag['tag_weight']; 
        }
        // записываем связи тег-объект
        $res = $db->querys("INSERT INTO ".$sys_tables['tags_'.$estate_type]." (id_tag, id_object, weight) VALUES (".implode('), (', $tag_links).")
                           ON DUPLICATE KEY UPDATE id_tag = VALUES(id_tag)");
    }
    $res = $db->querys("UPDATE ".$sys_tables[$estate_type]." SET tag_date = NOW() WHERE id IN(".implode(',',array_keys($obj_list)).")");
    echo $db->last_query;
}

function define_tags($object, $estate_type){
    $tags = array();
    // тип недвижимости
    $tags[13] = $estate_type == 'live' ? 'жилая' : (
                    $estate_type == 'build' ? 'строящаяся' : (
                        $estate_type == 'country' ? 'загородная' : (
                            $estate_type == 'commercial' ? 'коммерческая' : 'зарубежная'
                        )
                    )
                );
    // страна
    if(empty($object['country_title'])) $tags[1] = 'Россия';
    else $tags[1] = $object['country_title'];
    // регион страны
    if(!empty($object['region_title'])) $tags[2] = $object['region_title'];
    // район в регионе
    if(!empty($object['area_title'])) $tags[3] = $object['area_title'];
    // город
    if(!empty($object['city_title'])) $tags[4] = $object['city_title'];
    // населенный пункт
    if(!empty($object['place_title'])) $tags[15] = $object['place_title'];
    // район города
    if(!empty($object['district_title'])) $tags[5] = $object['district_title'];
    // метро
    if(!empty($object['subway_title'])) $tags[6] = $object['subway_title'];
    // улица
    if(!empty($object['street_title'])) $tags[7] = $object['street_title'];
    // тип сделки
    if(isset($object['rent'])) $tags[10] = $object['rent']==1 ? 'аренда' : 'продажа';
    // кол-во комнат
    if(!empty($object['rooms_sale'])) $tags[9] = $object['rooms_sale'];
    // жд станция [12]
    if(!empty($object['railstation'])) $tags[12] = $object['railstation'];
    // тип помещения [8]
    if(!empty($object['type_object_title'])) $tags[8] = $object['type_object_title'];
    // тип дома [14]
    if(!empty($object['building_type_title'])) $tags[14] = $object['building_type_title'];
    return $tags;
}
?>
