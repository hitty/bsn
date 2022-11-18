#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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
include('includes/class.photos.php');     // Photos (работа с графикой)
$sys_tables = Config::$sys_tables;

$db->querys("TRUNCATE ".$sys_tables['infrastructure']);
$subcats = array(
    1 => array('магазины'),
    2 => array('школы','детские сады','институты','университеты','автошколы','академии','колледжи'),
    3 => array('Парки культуры и отдыха'),
    4 => array('Фитнес-клубы', 'Бассейны', 'Тренажёрные залы', 'Ледовые дворцы / Катки', 'Спортивные школы', 'Лыжные базы / Горнолыжные комплексы', 'Стадионы', 'Теннисные корты'),
    5 => array('кафе', 'рестораны', 'быра', 'столовые', 'ночные клубы', 'Суши-бары / рестораны', 'Антикафе'),
    6 => array('аптеки', 'Стоматологические центры', 'Многопрофильные медицинские центры', 'Больницы', 'Детские поликлиники', 'Стоматологические поликлиники', 'Диагностические центры', 'Диспансеры', 'Родильные дома', 'Женские консультации', 'Госпитали', 'Станции переливания крови'),
    7 => array('кинотеатры', 'театры'),
    8 => array('музеи'),
);
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
$categories = $db->fetchall("SELECT * FROM ".$sys_tables['infrastructure_categories']);
foreach($categories as $k=>$category){
    foreach($subcats[$category['id']] as $item){
        foreach($where_array as $where){
            $pages_limit = 0;
            for($page=1; $page<=550; $page++){
                if($page>$pages_limit && $pages_limit>0) break;
                $result = str_replace('j5(','',curlThis('http://catalog.api.2gis.ru/search?callback=j5&what='.$item.'&where='.$where.'&key=rufsll2928&limit=22000&version=1.3&output=jsonp&pagesize=50&page='.$page));                
                $result = json_decode(substr($result,0,-1),true);   
                if($pages_limit==0) $pages_limit = ((int) ($result['total']/20))+1; 
                if(empty($result['error_message'])){
                    foreach($result['result'] as $k=>$result_item){
                        if(!empty($result_item['lat']) && !empty($result_item['lon'])){
                            $db->querys("INSERT IGNORE INTO ".$sys_tables['infrastructure']." SET id=?, id_category=?, name=?, address=?, lat=?, lng=?",
                                $result_item['id'], $category['id'], $result_item['name'],$result_item['address'],$result_item['lat'],$result_item['lon']
                            );
                        }
                    }
                } else break;
            }
        }
    }
}
?>
