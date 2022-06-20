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
$db->query("set names ".Config::$values['mysql']['charset']);
// вспомогательные таблицы
$sys_tables = Config::$sys_tables; 
$sys_tables['pages_seo'] = 'common.pages_seo';
                                                               
//скрипт модифицирует URL поиска по районам для жилой продажи квартир комнат согласно заданию по сео                                                               
                                                               
//  Жилая 
//  live sell   ?district_areas=30574&districts=5&obj_type=1&rooms=2
// /элитность/тип_недвижимости/тип_объекта/тип_сделки/количество_комнат/посуточно/метро/район/район_ло/страна/с_фото/

$params = array('obj_type','rooms','by_the_day','streets','subways','districts','district_areas');
$result_array = array();
$arrr = pc_array_power_set($params);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
        //флаг, что url входит в задание и ему нужно сео
        $add = false;
        if(in_array('streets',$a)){
            if(in_array('obj_type',$a)) $add = true;
            if(in_array('subways',$a)) unset($a[array_search('subways', $a)]);
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } elseif(in_array('subways',$a)){
            if(in_array('obj_type',$a)) $add = true;
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } else if(in_array('districts',$a)){
            if(in_array('obj_type',$a)) $add = true;
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        }
        if(count($a) == 2 && $add) $result_array[] = $a;
}
//$deal_types = array('sell','rent');
$deal_types = array('sell');

//считаем сколько url изменили
$counter = array('districts'=>0,'streets'=>0,'subways'=>0);

//убираем повторы из массива вариантов url
$result_array = array_map("unserialize", array_unique(array_map("serialize", $result_array)));

foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        
        $sql_array = array();
        //вывбтираем только квартиры и комнаты, согласно заданию
        $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, new_alias as title, title_accusative, title_genitive_plural as h1_title, title_genitive_plural as breadcrumbs FROM ".$sys_tables['type_objects_live']." WHERE id IN (1,2)");
        if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title, CONCAT('у метро ',title) as h1_title, CONCAT('метро ',title) as breadcrumbs FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
        if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type,title_prepositional, id, title, CONCAT('в ',title_prepositional,' районе Санкт-Петербурга') as h1_title, CONCAT (title, ' район СПб') as breadcrumbs FROM ".$sys_tables['districts']."  ");
        //if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, CONCAT(title_prepositional,' районе Ленинградской области') as h1_title, CONCAT (offname, ' район ЛО') as breadcrumbs, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2  ");        
        
        if(in_array('streets',$arr)) {
            if(empty($street_ids)){
                $streets_list = $db->fetchall("SELECT id_street FROM ".$sys_tables['live']." WHERE id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND id_street > 0 GROUP BY id_street");
                if(!empty($streets_list)){
                    $street_ids = array();
                    foreach($streets_list as $k=>$sid) $street_ids[] = $sid['id_street'];
                }
            }
            $sql_array[] = $db->fetchall("SELECT 'streets' as type, id_street as id, CONCAT(shortname,' ',offname) as title, CONCAT('по адресу ',shortname,' ',offname) as h1_title, CONCAT(shortname,' ',offname) as breadcrumbs, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND a_level = 5 AND id_street IN (".implode(', ',$street_ids).")");
        }
        
        
        $result = cartesian($sql_array);
        unset($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'live/'.$deal_type.'';
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            $query = array();
            
            
            
            foreach($item as $kk=>$it){
                
                //поиск по улице
                if(in_array('streets',$it)){
                    $seo_title = "Продажа ".$item[0]['h1_title']." на ".$it['title']." в Санкт-Петербурге - Купить ".$item[0]['title_accusative']." на вторичном рынке - BSN.ru";
                    $seo_description = "Объявления о продаже ".$item[0]['h1_title']." на ".$it['title']." в Санкт-Петербурге. Онлайн поиск, фотографии и цены на портале BSN.ru";
                }
                
                //поиск по району
                if(in_array('districts',$it)){
                    $seo_title = "Продажа ".$item[0]['h1_title']." в ".$it['title_prepositional']." районе Санкт-Петербурга - Купить ".$item[0]['title_accusative']." на вторичном рынке";
                    $seo_description = "Объявления о продаже ".$item[0]['h1_title']." в ".$it['title_prepositional']." районе Санкт-Петербурга. Онлайн поиск, фотографии и цены на портале BSN.ru";
                }
                
                //поиск по метро
                if(in_array('subways',$it)){
                    $seo_title = "Продажа ".$item[0]['h1_title']." у метро ".$it['title']." в Санкт-Петербурге - Купить ".$item[0]['title_accusative']." на вторичном рынке - BSN.ru";
                    $seo_description = "Объявления о продаже ".$item[0]['h1_title']." у метро ".$it['title']." в Санкт-Петербурге. Онлайн поиск, фотографии и цены на портале BSN.ru";
                }
            }
           
            //выбираем только те url, которые нужно изменить
            if( in_array($item[0]['title'],array('flats','rooms')) && in_array($item[1]['type'],array('districts','streets','subways')) && count($item) == 2 ){
                foreach($item as $k=>$values){
                    //формирование параметров запроса
                    if(!empty($values['type']) && !empty($values['id'])) $query[$values['type']] = $values['id'];
                }
                if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
               
                $update = $db->query("UPDATE ".$sys_tables['pages_seo']." 
                                      SET title = ?, description = ?
                                      WHERE url = ?
                ",$seo_title,$seo_description,$query_catalogs);
                ++$counter[$item[1]['type']];
            }
        }
    }        
    foreach($counter as $type=>$amount)
        echo $type." urls updated ".$amount.",\r\n";
}

function pc_array_power_set($array) {
    // инициализируем пустым множеством
    $results = array(array());
    foreach ($array as $element)
    foreach ($results as $combination)
    array_push($results, array_merge($combination,array($element)));
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
?>