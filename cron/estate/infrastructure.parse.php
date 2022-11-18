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
include('includes/class.housing_estates.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
$sys_tables = Config::$sys_tables;

$addr = 'Шаврова улица, д.23, к.1';
$url = "https://catalog.api.2gis.ru/3.0/items?type=street%2Cadm_div.city%2Ccrossroad%2Cadm_div.settlement%2Cstation%2Cbuilding%2Cadm_div.district%2Croad%2Cadm_div.division%2Cadm_div.region%2Cadm_div.living_area%2Cattraction%2Cadm_div.place%2Cadm_div.district_area%2Cbranch%2Cparking%2Cgate%2Croute&page=1&page_size=12&locale=ru_RU&fields=request_type%2Citems.adm_div%2Citems.attribute_groups%2Citems.contact_groups%2Citems.flags%2Citems.address%2Citems.rubrics%2Citems.name_ex%2Citems.point%2Citems.geometry.centroid%2Citems.region_id%2Citems.segment_id%2Citems.external_content%2Citems.org%2Citems.group%2Citems.schedule%2Citems.timezone_offset%2Citems.ads.options%2Citems.stat%2Citems.reviews%2Citems.purpose%2Csearch_type%2Ccontext_rubrics%2Csearch_attributes%2Cwidgets%2Cfilters&stat%5Bsid%5D=0f01fbfe-13d0-4b63-bf83-723e9ce1ae0b&stat%5Buser%5D=3e70592f-1f0b-482b-bec9-ab53f3931232&key=rulikm8232&q=";
$result = json_decode( curlThis( $url . 'Санкт-Петербург,' . $addr ) );                 


$db->querys("TRUNCATE ".$sys_tables['infrastructure']);
$categories = $db->fetchall("SELECT * FROM ".$sys_tables['infrastructure_categories']);

$subcats = $db->fetchall("SELECT id_parent,GROUP_CONCAT(CONCAT(id,'#',title) SEPARATOR '&') AS items FROM ".$sys_tables['infrastructure_subcategories']." GROUP BY id_parent",'id_parent');

foreach($subcats as $key=>$item){
    $item = explode('&',$item['items']);
    foreach($item as $k => $v){
        $elem = explode('#',$v);
        $item[$k] = array('id' => $elem[0],'title' => $elem[1]);
    }
    $subcats[$key] = $item;
}
$where_array = array(
    'Санкт-Петербург, Выборгский район',
    'Санкт-Петербург, Калининский район',
    'Санкт-Петербург, Кировский район',
    'Санкт-Петербург, Колпинский район',
    'Санкт-Петербург, Красногвардейский район',
    'Санкт-Петербург, Красносельский район',
    'Санкт-Петербург, Кронштадтский район',
    'Санкт-Петербург, Курортный район',
    'Санкт-Петербург, Московский район',
    'Санкт-Петербург, Невский район',
    'Санкт-Петербург, Петроградский район',
    'Санкт-Петербург, Петродворцовый район',
    'Санкт-Петербург, Приморский район',
    'Санкт-Петербург, Пушкинский район',
    'Санкт-Петербург, Фрунзенский район',
    'Санкт-Петербург, Центральный район',
    'Санкт-Петербург',
    'Сертолово',
    'Всеволожск',
    'Тихвин',
    'Выборг (Выборгский район)',
    'Кириши'
);
/*
$subcats = array(
    1 => array('магазины'),
    2 => array('школы','детские сады','институты','университеты','автошколы','академии','колледжи'),
    3 => array('Парки культуры и отдыха'),
    4 => array('Фитнес-клубы', 'Бассейны', 'Тренажёрные залы', 'Ледовые дворцы / Катки', 'Спортивные школы', 'Лыжные базы / Горнолыжные комплексы', 'Стадионы', 'Теннисные корты'),
    5 => array('кафе', 'рестораны', 'бары', 'столовые', 'ночные клубы', 'Суши-бары / рестораны', 'Антикафе'),
    6 => array('аптеки', 'Стоматологические центры', 'Многопрофильные медицинские центры', 'Больницы', 'Детские поликлиники', 'Стоматологические поликлиники', 'Диагностические центры', 'Диспансеры', 'Родильные дома', 'Женские консультации', 'Госпитали', 'Станции переливания крови'),
    7 => array('кинотеатры', 'театры'),
    8 => array('музеи'),
);
*/

foreach($categories as $k=>$category){
    foreach($subcats[$category['id']] as $subcategories){
        if(!empty($subcategories['id'])) $subcategories = array($subcategories);
        foreach($where_array as $where){
            $pages_limit = 0;
            for($page=1; $page<=550; $page++){
                if($page>$pages_limit && $pages_limit>0) break;
                foreach($subcategories as $subcategory){
                    
                    
                    $result = str_replace('j5(','',curlThis('http://catalog.api.2gis.ru/search?callback=j5&what='.$subcategory['title'].'&where='.$where.'&key=rulikm8232&limit=22&version=1.3&output=jsonp&pagesize=50&page='.$page));                
                    
                    $result = json_decode(substr($result,0,-1),true);   
                    if($pages_limit==0) $pages_limit = ((int) ($result['total']/20))+1; 
                    if(empty($result['error_message'])){
                        $insert_array = array();
                        foreach($result['result'] as $k=>$result_item){
                            if(!empty($result_item['lat']) && !empty($result_item['lon'])){
                                $insert_array[] = array('id' => $result_item['id'],
                                                        'id_category' => $category['id'],
                                                        'id_subcategory' => $subcategory['id'],
                                                        'name' => $result_item['name'],
                                                        'address' => $result_item['address'],
                                                        'lat' => $result_item['lat'],
                                                        'lng' => $result_item['lon']
                                                        );
                            }
                        }
                        $db->insertFromArrays($sys_tables['infrastructure'],$insert_array,false,false,true);
                    } else break;
                    
                }
            }
        }
    }
}

?>
