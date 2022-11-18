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

//#############################################################################################
//# Подсчет индексов квартир новостроек+вторичка по параметрам: тип дома, комнатность, район  
//#############################################################################################
//СТРОЯЩАЯСЯ НЕДВИЖИМОСТЬ
//Общая отсечка
    $sql_where = "".$sys_tables['build'].".published = 1 AND ".$sys_tables['build'].".square_full > 10 AND ".$sys_tables['build'].".square_full < 1000 AND ".$sys_tables['build'].".cost > 500000 AND (".$sys_tables['build'].".cost/".$sys_tables['build'].".square_full) > 40000 AND (".$sys_tables['build'].".cost/".$sys_tables['build'].".square_full) < 150000 AND ( (".$sys_tables['build'].".id_district > 1 and ".$sys_tables['build'].".id_district < 17) OR (".$sys_tables['build'].".id_area = 5 AND ".$sys_tables['build'].".id_region = 47) )";
//массив - список полей для стройки 
    $values = array('index_main','index_kirp', 'index_panel', 'index_kirp_monol', 'index_1kk', 'index_2kk', 'index_3kk', 'index_4kk', 'index_mnkk', 'index_vasil', 'index_vyborg', 'index_kalininsk', 'index_kirov', 'index_krasnogv', 'index_krasnosel', 'index_mosk', 'index_nevsk', 'index_petrogr', 'index_prim', 'index_frunz', 'index_centr','index_vsevol');
    $count=0; $insert_sql = '';
//Все ИНДЕКСы 
    $sql = "
    (
    SELECT avg(cost/square_full) as 'avgcost', 'общий', count(*) as cn  FROM ".$sys_tables['build']." WHERE $sql_where
    ) UNION (
    SELECT avg(cost/square_full), 'кирпичные', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND id_building_type = 10
    ) UNION (
    SELECT avg(cost/square_full), 'панельные', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND id_building_type = 9
    ) UNION (
    SELECT avg(cost/square_full), 'кирпично-монолитные', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND id_building_type = 33
    ) UNION (
    SELECT avg(cost/square_full), '1кк', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND rooms_sale = 1
    ) UNION (
    SELECT avg(cost/square_full), '2кк', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND rooms_sale = 2
    ) UNION (
    SELECT avg(cost/square_full), '3кк', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND rooms_sale = 3
    ) UNION (
    SELECT avg(cost/square_full), '4кк', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND rooms_sale = 4
    ) UNION (
    SELECT avg(cost/square_full), 'многокк', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND rooms_sale > 4 and rooms_sale < 10
    )
    UNION
    (
      SELECT IFNULL(avg(".$sys_tables['build'].".cost/".$sys_tables['build'].".square_full),1)  as 'avgcost', ib.title, count(".$sys_tables['build'].".id)
      FROM 
          ".$sys_tables['districts']." ib, ".$sys_tables['build']."
      WHERE 
          $sql_where and ib.id = ".$sys_tables['build'].".id_district AND ib.id != 2 
      GROUP BY
          ib.id 
    ) UNION (
    SELECT avg(cost/square_full)  as 'avgcost', 'всеволожский', count(*) FROM  ".$sys_tables['build']." WHERE $sql_where AND ".$sys_tables['build'].".id_area = 5 AND ".$sys_tables['build'].".id_region = 47
    )";
    $list = $db->fetchall($sql);
    foreach($list as $k=>$item)
    {
        $insert_sql.= "$values[$count] = '".($item['avgcost'])."', ";
        $count++;
    }
    
    //Запись индексов в таблицу
    $sql = "INSERT INTO ".$sys_tables['analytics_indexes_build']." SET $insert_sql date = NOW()";
    $db->querys($sql) or die($sql.$db->error);
    
// ВТОРИЧНАЯ НЕДВИЖИМОСТЬ
//Общая отсечка
    $sql_where = "".$sys_tables['live'].".published = 1 AND ".$sys_tables['live'].".square_full > 10 AND ".$sys_tables['live'].".square_full < 1000 AND ".$sys_tables['live'].".cost > 500000 AND (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) > 40000 AND (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) < 150000 AND ".$sys_tables['live'].".id_type_object = 1 AND ".$sys_tables['live'].".rent = 2 AND ".$sys_tables['live'].".rooms_sale > 0  AND ( (".$sys_tables['live'].".id_district > 1 and ".$sys_tables['live'].".id_district < 17) ) ";
//массив - список полей для стройки  Старый фонд, сталинки, кирпичные дома, панельные дома
    $values = array('index_main','index_kirp', 'index_starf', 'index_stalin', 'index_panel', 'index_1kk', 'index_2kk', 'index_3kk', 'index_4kk', 'index_mnkk', 'index_admir', 'index_vasil', 'index_vyborg', 'index_kalininsk', 'index_kirov', 'index_krasnogv', 'index_krasnosel', 'index_mosk', 'index_nevsk', 'index_petrogr', 'index_prim', 'index_frunz', 'index_centr');
    $count=0; $insert_sql = '';
//Все ИНДЕКСы 
    $sql = "
    (
    SELECT avg(cost/square_full) as 'avgcost', 'общий', count(*) as cn  FROM ".$sys_tables['live']." WHERE $sql_where
    ) UNION (
    SELECT avg(cost/square_full), 'кирпичные', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND id_building_type = 10
    ) UNION (
    SELECT avg(cost/square_full), 'Старый фонд', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND id_building_type IN (2,3)
    ) UNION (
    SELECT avg(cost/square_full), 'сталинки', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND id_building_type = 6
    ) UNION (
    SELECT avg(cost/square_full), 'панельные дома', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND id_building_type = 9
    ) UNION (
    SELECT avg(cost/square_full), '1кк', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND rooms_sale = 1
    ) UNION (
    SELECT avg(cost/square_full), '2кк', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND rooms_sale = 2
    ) UNION (
    SELECT avg(cost/square_full), '3кк', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND rooms_sale = 3
    ) UNION (
    SELECT avg(cost/square_full), '4кк', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND rooms_sale = 4
    ) UNION (
    SELECT avg(cost/square_full), 'многокк', count(*) FROM  ".$sys_tables['live']." WHERE $sql_where AND rooms_sale > 4 and rooms_sale < 10
    )
    UNION
    (
      SELECT IFNULL(avg(".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full),0) as 'avgcost', ib.title, count(".$sys_tables['live'].".id)
      FROM 
          ".$sys_tables['districts']." ib, ".$sys_tables['live']."
      WHERE 
          $sql_where and ib.id = ".$sys_tables['live'].".id_district
      GROUP BY
          ".$sys_tables['live'].".id_district 
    ) ";

   
   $list = $db->fetchall($sql);
    $insert_sql = '';
    foreach($list as $k=>$item)
    {
        $insert_sql.= "$values[$count] = '".($item['avgcost'])."', ";
        $count++;
    }    //Запись индексов в таблицу
    $sql = "INSERT INTO ".$sys_tables['analytics_indexes_live']." SET $insert_sql date = NOW(), type = 'sell'";
    $db->querys($sql) or die($sql.$db->error);
   
    
//#############################################################################################
//# Подсчет индексов квартир вторички для 1-2-3 комнатных по просторности  
//#############################################################################################
$conditions = array("cost","cost/square_full");
$sql = array();
foreach($conditions as $condition){

    $sql_1kk = "
              ( select avg(".$condition.")  from ".$sys_tables['live']." where square_full between 15 and 38 and cost/square_full between 40000 and 150000 and published=1 and id_type_object = 1  and rent = 2 and rooms_sale = 1 and ( (id_district > 1 and id_district < 17) ) )              union
              (select avg(".$condition.")  from ".$sys_tables['live']." where square_full > 38 and square_full < 100 and cost/square_full between 40000 and 150000 and published=1 and id_type_object = 1  and rent = 2 and rooms_sale = 1 and ( (id_district > 1 and id_district < 17)))";
    $res = $db->querys($sql_1kk) or die($sql_1kk.$db->error);
    $kompakt = $res->fetch_row();
    $prostor = $res->fetch_row();
    if($condition != 'cost/square_full') { $kompakt[0]=$kompakt[0]/1000 ; $prostor[0]=$prostor[0]/1000 ; }
    $sql[] = "`1kk_".$condition."_kompakt` = ".(int)$kompakt[0];
    $sql[] = "`1kk_".$condition."_prostor` = ".(int)$prostor[0];
    
    
    $sql_2kk = "
              (select avg(".$condition.")  from ".$sys_tables['live']." where square_full between 15 and 55 and cost/square_full between 40000 and 150000 and published=1 and id_type_object = 1  and rent = 2 and rooms_sale = 2 and ( (id_district > 1 and id_district < 17)) )
              union
              (select avg(".$condition.")  from ".$sys_tables['live']." where square_full > 55 and square_full < 150 and cost/square_full between 40000 and 150000 and published=1 and id_type_object = 1  and rent = 2 and rooms_sale = 2 and ( (id_district > 1 and id_district < 17)) )";
    $res = $db->querys($sql_2kk) or die($sql_2kk.$db->error);
    $kompakt = $res->fetch_row();
    $prostor = $res->fetch_row();
    if($condition != 'cost/square_full') { $kompakt[0]=$kompakt[0]/1000 ; $prostor[0]=$prostor[0]/1000 ; }
    $sql[] = "`2kk_".$condition."_kompakt` = ".(int)($kompakt[0]);
    $sql[] = "`2kk_".$condition."_prostor` = ".(int)($prostor[0]);

    $sql_3kk = "
              (select avg(".$condition.")  from ".$sys_tables['live']." where square_full between 15 and 79 and cost/square_full between 40000 and 150000 and published=1 and id_type_object = 1  and rent = 2 and rooms_sale = 3 and ( (id_district > 1 and id_district < 17)) )
              union
              (select avg(".$condition.")  from ".$sys_tables['live']." where square_full > 79 and square_full < 250 and cost/square_full between 40000 and 150000 and published=1 and id_type_object = 1  and rent = 2 and rooms_sale = 3 and ( (id_district > 1 and id_district < 17)) )";
    $res = $db->querys($sql_3kk) or die($sql_3kk.$db->error);
    $kompakt = $res->fetch_row();
    $prostor = $res->fetch_row();
    if($condition != 'cost/square_full') { $kompakt[0]=$kompakt[0]/1000 ; $prostor[0]=$prostor[0]/1000 ; }
    $sql[] = "`3kk_".$condition."_kompakt` = ".(int)($kompakt[0]);
    $sql[] = "`3kk_".$condition."_prostor` = ".(int)($prostor[0]);

}
$db->querys("INSERT INTO ".$sys_tables['analytics_indexes_flats_sizes']." SET date = NOW(), ".implode(", ",$sql)) or die("INSERT INTO ".$sys_tables['analytics_indexes_flats_sizes']." SET date = NOW(), ".implode(", ",$sql).$db->error);

//Общая отсечка
$sql_where = "".$sys_tables['live'].".published = 1 AND ".$sys_tables['live'].".square_full > 10 AND ".$sys_tables['live'].".square_full < 1000 AND ".$sys_tables['live'].".cost > 500000 AND (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) > 40000 AND (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) < 150000 AND ".$sys_tables['live'].".id_type_object = 1 AND ".$sys_tables['live'].".rent = 2 AND ".$sys_tables['live'].".rooms_sale > 0  AND ".$sys_tables['live'].".id_district > 1 and ".$sys_tables['live'].".id_district < 17 ";
$sql_where_all = " ".$sys_tables['live'].".published = 1 AND ".$sys_tables['live'].".square_full > 10 AND ".$sys_tables['live'].".square_full < 1000 AND ".$sys_tables['live'].".cost > 500000 AND (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) > 40000 AND (".$sys_tables['live'].".cost/".$sys_tables['live'].".square_full) < 150000 AND ".$sys_tables['live'].".id_type_object = 1 AND ".$sys_tables['live'].".rent = 2 AND ".$sys_tables['live'].".rooms_sale > 0 ";

$sql_where_new = " ".$sys_tables['live'].".date_in = CURDATE() ";
$sql_where_new_square_full = " AND ( (".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100) OR (".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150) OR (".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full <= 250))";

$sql_where_blocks = "( (".$sys_tables['live'].".id_district > 1 and ".$sys_tables['live'].".id_district < 17) ) ";
//массив - список полей для стройки  Старый фонд, сталинки, кирпичные дома, панельные дома
 $values = array(
    'index_main'=>'Средняя цена квадратного метра предложения',
    'index_main_new'=>'Средняя цена кв.м. новых поступлений',

    'all_vars'=>'Число вариантов всего',
    'all_vars_new'=>'Число новых вариантов',

    'index_1kk'=>'Средняя цена единичек',
    'index_1kk_cost_kompakt'=>'Средняя цена компактных единичек',
    'index_1kk_cost_prostor'=>'Средняя цена просторных единичек',
    '1kk_count'=>'Число единичек',
    '1kk_count_kompakt'=>'Число компактных единичек',
    '1kk_count_prostor'=>'Число просторных единичек',

    'index_2kk'=>'Средняя цена двушек',
    'index_2kk_cost_kompakt'=>'Средняя цена компактных двушек',
    'index_2kk_cost_prostor'=>'Средняя цена просторных двушек',
    '2kk_count'=>'Число двушек',
    '2kk_count_kompakt'=>'Число компактных двушек',
    '2kk_count_prostor'=>'Число просторных двушек',

    'index_3kk'=>'Средняя цена трешек',
    'index_3kk_cost_kompakt'=>'Средняя цена компактных трешек',
    'index_3kk_cost_prostor'=>'Средняя цена просторных трешек',
    '3kk_count'=>'Число трешек',
    '3kk_count_kompakt'=>'Число компактных трешек',
    '3kk_count_prostor'=>'Число просторных трешек',
    
    'index_1kk_new'=>'Средняя цена единичек (новые поступления)',
    'index_1kk_cost_kompakt_new'=>'Средняя цена компактных единичек (новые поступления)',
    'index_1kk_cost_prostor_new'=>'Средняя цена просторных единичек (новые поступления)',
    '1kk_count_new'=>'Число единичек (новые поступления)',
    '1kk_count_kompakt_new'=>'Число компактных единичек (новые поступления)',
    '1kk_count_prostor_new'=>'Число просторных единичек (новые поступления)',

    'index_2kk_new'=>'Средняя цена двушек (новые поступления)',
    'index_2kk_cost_kompakt_new'=>'Средняя цена компактных двушек (новые поступления)',
    'index_2kk_cost_prostor_new'=>'Средняя цена просторных двушек (новые поступления)',
    '2kk_count_new'=>'Число двушек (новые поступления)',
    '2kk_count_kompakt_new'=>'Число компактных двушек (новые поступления)',
    '2kk_count_prostor_new'=>'Число просторных двушек (новые поступления)',

    'index_3kk_new'=>'Средняя цена трешек (новые поступления)',
    'index_3kk_cost_kompakt_new'=>'Средняя цена компактных трешек (новые поступления)',
    'index_3kk_cost_prostor_new'=>'Средняя цена просторных трешек (новые поступления)',
    '3kk_count_new'=>'Число трешек (новые поступления)',
    '3kk_count_kompakt_new'=>'Число компактных трешек (новые поступления)',
    '3kk_count_prostor_new'=>'Число просторных трешек',
    
    'count_vars_admir_1kk'=>'',
    'count_vars_vasil_1kk'=>'',
    'count_vars_vyborg_1kk'=>'',
    'count_vars_kalininsk_1kk'=>'',
    'count_vars_kirov_1kk'=>'',
    'count_vars_krasnogv_1kk'=>'',
    'count_vars_krasnosel_1kk'=>'',
    'count_vars_mosk_1kk'=>'',
    'count_vars_nevsk_1kk'=>'',
    'count_vars_petrogr_1kk'=>'',
    'count_vars_prim_1kk'=>'',
    'count_vars_frunz_1kk'=>'',
    'count_vars_centr_1kk'=>'',
    
    'count_vars_admir_2kk'=>'',
    'count_vars_vasil_2kk'=>'',
    'count_vars_vyborg_2kk'=>'',
    'count_vars_kalininsk_2kk'=>'',
    'count_vars_kirov_2kk'=>'',
    'count_vars_krasnogv_2kk'=>'',
    'count_vars_krasnosel_2kk'=>'',
    'count_vars_mosk_2kk'=>'',
    'count_vars_nevsk_2kk'=>'',
    'count_vars_petrogr_2kk'=>'',
    'count_vars_prim_2kk'=>'',
    'count_vars_frunz_2kk'=>'',
    'count_vars_centr_2kk'=>'',

    'count_vars_admir_3kk'=>'',
    'count_vars_vasil_3kk'=>'',
    'count_vars_vyborg_3kk'=>'',
    'count_vars_kalininsk_3kk'=>'',
    'count_vars_kirov_3kk'=>'',
    'count_vars_krasnogv_3kk'=>'',
    'count_vars_krasnosel_3kk'=>'',
    'count_vars_mosk_3kk'=>'',
    'count_vars_nevsk_3kk'=>'',
    'count_vars_petrogr_3kk'=>'',
    'count_vars_prim_3kk'=>'',
    'count_vars_frunz_3kk'=>'',
    'count_vars_centr_3kk'=>'',

    'count_vars_admir_1kk_new'=>'',
    'count_vars_vasil_1kk_new'=>'',
    'count_vars_vyborg_1kk_new'=>'',
    'count_vars_kalininsk_1kk_new'=>'',
    'count_vars_kirov_1kk_new'=>'',
    'count_vars_krasnogv_1kk_new'=>'',
    'count_vars_krasnosel_1kk_new'=>'',
    'count_vars_mosk_1kk_new'=>'',
    'count_vars_nevsk_1kk_new'=>'',
    'count_vars_petrogr_1kk_new'=>'',
    'count_vars_prim_1kk_new'=>'',
    'count_vars_frunz_1kk_new'=>'',
    'count_vars_centr_1kk_new'=>'',
    
    'count_vars_admir_2kk_new'=>'',
    'count_vars_vasil_2kk_new'=>'',
    'count_vars_vyborg_2kk_new'=>'',
    'count_vars_kalininsk_2kk_new'=>'',
    'count_vars_kirov_2kk_new'=>'',
    'count_vars_krasnogv_2kk_new'=>'',
    'count_vars_krasnosel_2kk_new'=>'',
    'count_vars_mosk_2kk_new'=>'',
    'count_vars_nevsk_2kk_new'=>'',
    'count_vars_petrogr_2kk_new'=>'',
    'count_vars_prim_2kk_new'=>'',
    'count_vars_frunz_2kk_new'=>'',
    'count_vars_centr_2kk_new'=>'',

    'count_vars_admir_3kk_new'=>'',
    'count_vars_vasil_3kk_new'=>'',
    'count_vars_vyborg_3kk_new'=>'',
    'count_vars_kalininsk_3kk_new'=>'',
    'count_vars_kirov_3kk_new'=>'',
    'count_vars_krasnogv_3kk_new'=>'',
    'count_vars_krasnosel_3kk_new'=>'',
    'count_vars_mosk_3kk_new'=>'',
    'count_vars_nevsk_3kk_new'=>'',
    'count_vars_petrogr_3kk_new'=>'',
    'count_vars_prim_3kk_new'=>'',
    'count_vars_frunz_3kk_new'=>'',
    'count_vars_centr_3kk_new'=>'',
        
    );

$sql = "
    ( 
        SELECT avg(cost/square_full)  as 'avgcost', 'общий'  FROM ".$sys_tables['live']." WHERE $sql_where
    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'общий (новых)'  FROM ".$sys_tables['live']." WHERE $sql_where  AND $sql_where_new
    ) UNION (
        SELECT count(*), 'число вариантов всего'  FROM ".$sys_tables['live']." WHERE $sql_where
    ) UNION (
        SELECT count(*), 'число вариантов всего (новых)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new $sql_where_new_square_full

    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100
    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена компактных единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full < 38
    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена просторных единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 38 and square_full <= 100
    ) UNION (
        SELECT count(*), 'Число единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100
    ) UNION (
        SELECT count(*), 'Число компактных единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full < 38
    ) UNION (
        SELECT count(*), 'Число просторных единичек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 38 and square_full <= 100

    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150
    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена компактных двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full < 55
    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена просторных двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 55 and square_full <= 150
    ) UNION (
        SELECT count(*), 'Число двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150
    ) UNION (
        SELECT count(*), 'Число компактных двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full < 55
    ) UNION (
        SELECT count(*), 'Число просторных двушек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 55 and square_full <= 150

    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full <= 250
    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена компактных трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full < 79
    ) UNION (
        SELECT avg(cost/square_full)  as 'avgcost', 'Средняя цена просторных трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 79 and square_full <= 250
    ) UNION (
        SELECT count(*), 'Число трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3  and square_full >= 15 and square_full <= 250
    ) UNION (
        SELECT count(*), 'Число компактных трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full < 79
    ) UNION (
        SELECT count(*), 'Число просторных трешек'  FROM ".$sys_tables['live']." WHERE $sql_where AND ".$sys_tables['live'].".rooms_sale = 3 and square_full > 79 and square_full >= 79 and square_full <= 250
    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена единичек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100
    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена компактных единичек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full < 38
    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена просторных единичек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 38 and square_full <= 100
    ) UNION (
        SELECT count(*), 'Число единичек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100
    ) UNION (
        SELECT count(*), 'Число компактных единичек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full < 38
    ) UNION (
        SELECT count(*), 'Число просторных единичек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 38 and square_full <= 100

    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена двушек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150
    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена компактных двушек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full < 55
    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена просторных двушек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 55 and square_full <= 150
    ) UNION (
        SELECT count(*), 'Число двушек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150
    ) UNION (
        SELECT count(*), 'Число компактных двушек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full < 55
    ) UNION (
        SELECT count(*), 'Число просторных двушек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 55 and square_full <= 150

    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена трешек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full <= 250
    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена компактных трешек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full < 79
    ) UNION (
        SELECT avg(cost)  as 'avgcost', 'Средняя цена просторных трешек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 79 and square_full <= 250
    ) UNION (
        SELECT count(*), 'Число трешек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full <= 250
    ) UNION (
        SELECT count(*), 'Число компактных трешек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full < 79
    ) UNION (
        SELECT count(*), 'Число просторных трешек(новые)'  FROM ".$sys_tables['live']." WHERE $sql_where AND $sql_where_new AND ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 79 and square_full <= 250
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

    $sql = "INSERT INTO ".$sys_tables['analytics_indexes_flats_districts_size']." SET $insert_sql date = NOW()";
    $db->querys($sql) or die($sql.$db->error);
    $_ID_ = $db->insert_id;

    $sql_where_blocks_array = array(2,3,4,5,6,7,8,10,11,12,13,15,16);
    $sql_1 = " AND ".$sys_tables['live'].".rooms_sale = 1 and ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100";
    $sql_2 = " AND ".$sys_tables['live'].".rooms_sale = 2 and ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150";
    $sql_3 = " AND ".$sys_tables['live'].".rooms_sale = 3 and ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full <= 250";
    $sql_4 = " AND ".$sys_tables['live'].".rooms_sale = 1 and $sql_where_new and ".$sys_tables['live'].".rooms_sale = 1 and square_full >= 15 and square_full <= 100";
    $sql_5 = " AND ".$sys_tables['live'].".rooms_sale = 2 and $sql_where_new and ".$sys_tables['live'].".rooms_sale = 2 and square_full >= 15 and square_full <= 150";
    $sql_6 = " AND ".$sys_tables['live'].".rooms_sale = 3 and $sql_where_new and ".$sys_tables['live'].".rooms_sale = 3 and square_full >= 15 and square_full <= 250";

    $sql_array = array($sql_1, $sql_2, $sql_3, $sql_4, $sql_5, $sql_6);
    $insert_sql = '';
    
    $count--;
    
    foreach($sql_array as $sql_act){
        foreach($sql_where_blocks_array as $sql_where_block){
            $count++;
            $sql = "
                  SELECT count(*), ib.title
                  FROM 
                      ".$sys_tables['districts']." ib, ".$sys_tables['live']."
                  WHERE 
                      $sql_where_all AND ".$sys_tables['live'].".id_district = $sql_where_block AND ib.id = ".$sys_tables['live'].".id_district $sql_act
                  GROUP BY
                      ".$sys_tables['live'].".id_district 
          ";
            $res = $db->querys($sql) or die($sql.$db->error);
            if($res->num_rows == 0) $insert_sql.= "".$arr[$count]." = 0, ";
            else{
                while($row = $res->fetch_row()){
                    $insert_sql.= "".$arr[$count]." = '".($row[0])."', ";
                }
            }
        }
    }

    $sql = "UPDATE ".$sys_tables['analytics_indexes_flats_districts_size']." SET $insert_sql date = NOW() WHERE id=$_ID_";
    $db->querys($sql) or die($sql.$db->error);
?>
