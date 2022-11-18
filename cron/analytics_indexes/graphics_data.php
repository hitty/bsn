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
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
include('includes/class.moderation.php'); // Moderation (процедура модерации)
include('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


//Шаг для графика 3 (площади)
$gr3_step = 5;

//Шаг для графика 4 (стоимость)
$gr4_step = 500000;

// ВТОРИЧНАЯ НЕДВИЖИМОСТЬ

//Общая отсечка
$sql_where = "".$sys_tables['live'].".published = 1 AND ".$sys_tables['live'].".square_full > 10 AND ".$sys_tables['live'].".square_full < 1000 AND ".$sys_tables['live'].".cost > 500000 AND (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) > 40000 AND (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) < 150000 AND ".$sys_tables['live'].".id_type_object = 1 AND ".$sys_tables['live'].".rent = 2 AND ".$sys_tables['live'].".rooms_sale > 0";

$sql_where_simple = "".$sys_tables['live'].".published = 1 AND ".$sys_tables['live'].".square_full > 10 AND ".$sys_tables['live'].".square_full < 1000 AND ".$sys_tables['live'].".cost > 500000 AND ".$sys_tables['live'].".cost < 1000000000 AND ".$sys_tables['live'].".id_type_object = 1 AND ".$sys_tables['live'].".rent = 2 AND ".$sys_tables['live'].".rooms_sale > 0";

$sql_where_fir = "".$sys_tables['build'].".published = 1 AND ".$sys_tables['build'].".square_full > 10 AND ".$sys_tables['build'].".square_full < 1000 AND ".$sys_tables['build'].".cost > 500000 AND (".$sys_tables['build'].".cost/".$sys_tables['build'].".square_full) > 40000 AND (".$sys_tables['build'].".cost/".$sys_tables['build'].".square_full) < 150000";

$sql_where_new_square_full = " AND ( (".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100) OR (".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150) OR (".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full <= 250))";

$sql_where_liv_blocks = "( ".$sys_tables['live'].".id_district < 17 ) ";
$sql_where_fir_blocks = "( ".$sys_tables['build'].".id_district IN (2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,27,29,38,43,53) ) ";
//массив - список полей для стройки  Старый фонд, сталинки, кирпичные дома, панельные дома
 $values = array(
      '1_f_num_1kk'=>       'Общее кол-во новостроек 1 комнатных',
      '1_f_num_2kk'=>       'Общее кол-во новостроек 2 комнатных',
      '1_f_num_3kk'=>       'Общее кол-во новостроек 3 комнатных',
      '1_l_num_1kk'=>       'Общее кол-во вторички 1 комнатных',
      '1_l_num_2kk'=>       'Общее кол-во вторички 2 комнатных',
      '1_l_num_3kk'=>       'Общее кол-во вторички 3 комнатных',
      '2_f_cost'=>          'Стоимость кв.метра новостроек',
      '2_l_cost'=>          'Стоимость кв.метра вторички',
      '2_gold'=>            'Курс золота на дату',
      '2_dol'=>             'Курс доллара на дату',
      '3_1kk_txt_sqear'=>   'Распределение по площадям для 1кк',
      '3_2kk_txt_sqear'=>   'Распределение по площадям для 2кк',
      '3_3kk_txt_sqear'=>   'Распределение по площадям для 3кк',
      '4_1kk_txt_cost'=>    'Распределение по стоимостям для 1кк',
      '4_2kk_txt_cost'=>    'Распределение по стоимостям для 2кк',
      '4_3kk_txt_cost'=>    'Распределение по стоимостям для 3кк',
      '5_1kk_text'=>        'Распр. по комнатности по кластерам районов для 1кк',
      '5_2kk_text'=>        'Распр. по комнатности по кластерам районов для 2кк',
      '5_3kk_text'=>        'Распр. по комнатности по кластерам районов для 3кк',
      '6_1kk_komp'=>        'Стоимость кв.метра для компактных 1кк',
      '6_1kk_prost'=>       'Стоимость кв.метра для просторных 1кк',
      '6_1kk_all'=>         'Стоимость кв.метра для всех 1кк',
      '6_2kk_komp'=>        'Стоимость кв.метра для компактных 2кк',
      '6_2kk_prost'=>       'Стоимость кв.метра для просторных 2кк',
      '6_2kk_all'=>         'Стоимость кв.метра для всех 2кк',
      '6_3kk_komp'=>        'Стоимость кв.метра для компактных 3кк',
      '6_3kk_prost'=>       'Стоимость кв.метра для просторных 3кк',
      '6_3kk_all'=>         'Стоимость кв.метра для всех 3кк'
        
    );

$sql = "
    ( 
        SELECT count(*) as 'cols', 'кол-во первички'  FROM ".$sys_tables['build']." WHERE $sql_where_fir AND rooms_sale = 1
    ) UNION (
        SELECT count(*) as 'cols', 'кол-во первички'  FROM ".$sys_tables['build']." WHERE $sql_where_fir AND rooms_sale = 2
    ) UNION (
        SELECT count(*) as 'cols', 'кол-во первички'  FROM ".$sys_tables['build']." WHERE $sql_where_fir AND rooms_sale = 3
    ) UNION (
        SELECT count(*) as 'cols', 'кол-во вторички'  FROM ".$sys_tables['live']." WHERE $sql_where_simple AND rooms_sale = 1
    ) UNION (
        SELECT count(*) as 'cols', 'кол-во вторички'  FROM ".$sys_tables['live']." WHERE $sql_where_simple AND rooms_sale = 2
    ) UNION (
        SELECT count(*) as 'cols', 'кол-во вторички'  FROM ".$sys_tables['live']." WHERE $sql_where_simple AND rooms_sale = 3
    ) ";
    
    $arr = array_keys($values);
    $res = $db->querys($sql) or die($db->error);
    
    $count = 0;
    $insert_sql = '';

    while($row = $res->fetch_row()){
        $insert_sql.= "".$arr[$count]." = '".($row[0])."', ";
        $count++;
    }
    //Запись индексов в таблицу

    $sql = "INSERT INTO ".$sys_tables['analytics_graphics']." SET $insert_sql date = NOW()";
    $db->querys($sql) or die($sql.$db->error);
    echo $_ID_ = $db->insert_id;
    

//Данные для графика 2: ценовая динамика по новостройкам и вторичке

    // парсинг цетробанка: доллар и золото
        $infoXml_SpObjects = new DOMDocument('1.0', 'utf-8');
        $infoXml_SpObjects->load('http://www.cbr.ru/scripts/XML_daily.asp');
        $xpath_sp = new DOMXPath($infoXml_SpObjects);
        $links_sp = $xpath_sp->query('//ValCurs/Valute[CharCode = "USD"]/Value');//foo[bar="AAA"]/baz
        foreach($links_sp as $link){
            $usd =  str_replace(',','.',$link->nodeValue);
        }
        $infoXml_SpObjects->load('http://quotes.instaforex.com/get_quotes.php?m=xml');
        $xpath_sp = new DOMXPath($infoXml_SpObjects);
        $links_sp = $xpath_sp->query('//items/item[symbol = "GOLD"]/bid');//foo[bar="AAA"]/baz
        foreach($links_sp as $link){
            $gold =  $link->nodeValue;
        }

//          
      $insert_sql = '';
      $sql = "
          (
            SELECT avg(cost/square_full) as avgcost FROM ".$sys_tables['build']." WHERE $sql_where_fir AND $sql_where_fir_blocks
          ) UNION (
            SELECT avg(cost/square_full) as avgcost FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_liv_blocks
          ) UNION (
            SELECT $gold as gold
          ) UNION (
            SELECT $usd as dol
          )
          ";
        
    $res = $db->querys($sql) or die($sql.$db->error);
    $insert_sql = '';

    while($row = $res->fetch_row()){
        $insert_sql.= "".$arr[$count]." = '".($row[0])."', ";
        $count++;
    }
    $db->querys("UPDATE ".$sys_tables['analytics_graphics']." SET $insert_sql date = NOW() WHERE id=$_ID_") or die($db->error);


//Данные для графика 3: распределение по площадям для вторички для 1/2/3 КК

    $inserts_sql[1] = '';$inserts_sql[2] = '';$inserts_sql[3] = '';
    for($rooms = 1; $rooms < 4; $rooms++){
        for($i=20; $i<=100; $i=$i+$gr3_step){
            $sql = "SELECT COUNT(*) as cnt FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_liv_blocks AND rooms_sale = $rooms AND square_full >= $i AND square_full < ".($i+$gr3_step);
            $res = $db->querys($sql) or die($db->error);
            $row = $res->fetch_array(); 
            $inserts_sql[$rooms].= $row['cnt'].";";
            
        }
    }
    $db->querys("UPDATE ".$sys_tables['analytics_graphics']." SET 3_1kk_txt_sqear = '$inserts_sql[1]', 3_2kk_txt_sqear = '$inserts_sql[2]', 3_3kk_txt_sqear = '$inserts_sql[3]' WHERE id=$_ID_") or die($db->error);
        
//Данные для графика 4: распределение по площадям для вторички для 1/2/3 КК

    $insertr_sql[1] = '';$insertr_sql[2] = '';$insertr_sql[3] = '';
    for($rooms = 1; $rooms < 4; $rooms++){
        for($i=2000000; $i<=9500000; $i=$i+$gr4_step){
            $sql = "SELECT COUNT(*) as cnt FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_liv_blocks AND rooms_sale = $rooms AND cost >= $i AND cost < ".($i+$gr4_step);
            $res = $db->querys($sql) or die($db->error);
            $row = $res->fetch_array(); $insertr_sql[$rooms].= ($row['cnt']>0 ? (integer)$row['cnt'] : 0) .";";
            
        }
    }
    echo "UPDATE ".$sys_tables['analytics_graphics']." SET 4_1kk_txt_cost = '$insertr_sql[1]', 4_2kk_txt_cost = '$insertr_sql[2]', 4_3kk_txt_cost = '$insertr_sql[3]' WHERE id=$_ID_";
    $db->querys("UPDATE ".$sys_tables['analytics_graphics']." SET 4_1kk_txt_cost = '$insertr_sql[1]', 4_2kk_txt_cost = '$insertr_sql[2]', 4_3kk_txt_cost = '$insertr_sql[3]' WHERE id=$_ID_") or die($db->error);

        
//Данные для графика 5: распределение по территория города - для 1/2/3 КК (кол-во)

    $areas = array('sever'=>'4,5,13', 'centr'=>'2,3,12,16', 'ug'=>'6,8,15', 'vostok'=>'7,11', 'mosk' =>'10');
    $areas_sql = array();
    $areas_sql[1] = '';$areas_sql[2] = '';$areas_sql[3] = '';
    for($rooms = 1; $rooms < 4; $rooms++){
        foreach($areas as $field => $ids){
            $sql = "SELECT COUNT(*) as cnt FROM ".$sys_tables['live']." WHERE $sql_where_simple AND rooms_sale = $rooms AND id_district IN ($ids)";
            $res = $db->querys($sql) or die($db->error);
            $row = $res->fetch_array(); $areas_sql[$rooms].= (integer)($row['cnt']).";";
            
        }
    }
    echo "UPDATE ".$sys_tables['analytics_graphics']." SET 5_1kk_text = '$areas_sql[1]', 5_2kk_text = '$areas_sql[2]', 5_3kk_text = '$areas_sql[3]' WHERE id=$_ID_";
    $db->querys("UPDATE ".$sys_tables['analytics_graphics']." SET 5_1kk_text = '$areas_sql[1]', 5_2kk_text = '$areas_sql[2]', 5_3kk_text = '$areas_sql[3]' WHERE id=$_ID_") or die($db->error);
            

//Данные для графика 5: динамика стоимости кв.метра по типам квартир

 $sql = "
    (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена компактных единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full < 38
    ) UNION (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена просторных единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 38 and square_full <= 100
    ) UNION (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100
    ) UNION (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена компактных двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full < 55
    ) UNION (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена просторных двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 55 and square_full <= 150
    ) UNION (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150
    ) UNION (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена компактных трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full < 79
    ) UNION (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена просторных трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 79 and square_full <= 250
    ) UNION (
        SELECT avg(cost/square_full) as 'avgcost', 'Средняя цена трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full <= 250
    )
    ";  

    $count = 19;
    $insert_sql = '';
    $res = $db->querys($sql) or die($db->error);
    while($row = $res->fetch_row()){
        $insert_sql.= "".$arr[$count]." = '".($row[0])."', ";
        $count++;
    }
    //Запись индексов в таблицу

  echo  $sql = "UPDATE ".$sys_tables['analytics_graphics']." SET $insert_sql date = NOW() WHERE id = $_ID_";
   $db->querys($sql) or die($sql.$db->error);
?>
