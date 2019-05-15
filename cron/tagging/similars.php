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
    file_put_contents('cron/tagging/similar.log', "'Already running'\n" );
    die('Already running');
}
file_put_contents('cron/tagging/similar.log', date('h m s')."\n" );
//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/tagging/similar_error.log';
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
$db->query("set names ".Config::$values['mysql']['charset']);
//include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
// вспомогательные таблицы
$sys_tables = Config::$sys_tables;
 
$process_count = array('live'=>5000, 'commercial'=>5000, 'build'=>5000, 'country'=>5000);
foreach($process_count as $estate_type=>$count){
    
    //удаление тегов у неактуальных обхектов
    $db->query("DELETE FROM ".$sys_tables["tags_".$estate_type]." WHERE id_object IN (SELECT e.id FROM ".$sys_tables[$estate_type]." e WHERE e.published = 2)");   

    if($estate_type=='foreign') {$cost_fields = "cost_rubles, cost_dollars, cost_euros";}
    else $cost_fields = 'cost';
    $obj_list = $db->fetchall("SELECT id, ".$cost_fields."
                               FROM ".$sys_tables[$estate_type]."
                               WHERE similar_date < tag_date AND DATEDIFF(NOW(),tag_date)<10 AND published = 1 
                               ORDER BY similar_date 
                               LIMIT ".$count);
                               print_r($obj_list);
    // поиск похожих
    similaring($obj_list, $estate_type);
}


function similaring($obj_list, $estate_type){
    global $db,  $sys_tables;
    foreach($obj_list as $object){
        $overall_time_counter = microtime(true);
        // получаем ID-шники тегов объекта
        $current_tags = $db->fetchall("SELECT id_tag FROM ".$sys_tables['tags_'.$estate_type]." WHERE id_object = ?", 'id_tag', $object['id']);
        if(!empty($current_tags)){
            $current_tags = array_keys($current_tags);
            if($estate_type=='foreign') {
                $cost_fields = "IF(cost_rubles=0,
                    IF(cost_dollars=0, cost_euros, cost_dollars),
                   cost_rubles 
                )";
                $cost_fields_types = "IF(cost_rubles=0,
                    IF(cost_dollars=0, 'cost_euros', 'cost_dollars'),
                   'cost_rubles'
                )";
            } else {
                $cost_fields = "cost";
                $cost_fields_types = "'cost'";
            }
            // выбор наиболее похожих объектов по тегам
            $sql_1 = "SELECT id_object,
                             count(id_object) as cnt,
                             sum(weight) as weight,
                             ".$sys_tables[$estate_type].".".$cost_fields." as cost,
                             ".$cost_fields_types." as cost_type
                      FROM ".$sys_tables['tags_'.$estate_type]."
                      RIGHT JOIN ".$sys_tables[$estate_type]." ON ".$sys_tables[$estate_type].".id = ".$sys_tables['tags_'.$estate_type].".id_object
                      WHERE id_tag IN (".implode(',',$current_tags).")
                      GROUP BY id_object
                      ORDER BY weight DESC, cnt DESC
                      LIMIT 1000";
            $_like_objects = $db->fetchall($sql_1);
            echo $db->last_query;
            file_put_contents('cron/tagging/similar.log', "query time: ".round(microtime(true) - $overall_time_counter, 4),FILE_APPEND );
            if(sizeof($_like_objects)>15) {

                // - добавление дельты
                foreach($_like_objects as $key => $val) {
                    $_like_objects[$key]['delta'] = $_like_objects[$key]['weight']/(1+abs($_like_objects[$key]['cost'] - $object[$val['cost_type']])/($object[$val['cost_type']]+0.001));
                }
                // - сортировка
                $delta = $cnt = $weight = array();
                foreach($_like_objects as $_key => $_obj){
                    $delta[$_key] = $_obj['delta'];
                    $cnt[$_key] = $_obj['cnt'];
                    $weight[$_key] = $_obj['weight'];
                }
                array_multisort($delta, SORT_DESC, $weight, SORT_DESC, $cnt, SORT_DESC, $_like_objects);
                $similar_ids = array();
                for($i=0;$i<15;$i++) $similar_ids[] = $_like_objects[$i]['id_object'];
                $res = $db->query("UPDATE ".$sys_tables[$estate_type]." SET similar_date = NOW(), similar_ids=? WHERE id=?", implode(',',$similar_ids), $object['id']);
                $overall_time_counter = round(microtime(true) - $overall_time_counter, 4);
                $text = "; all time: ".$overall_time_counter.": id = ".$object['id']." : estate = ".$estate_type."\n";
                file_put_contents('cron/tagging/similar.log', $text,FILE_APPEND );
                echo $text;
            }
        } else {
            die($db->last_query);
        }
    }
        
}
?>
UPDATE tag_live SET tag_live.weight = (SELECT tags.weight FROM tags WHERE tags.id = tag_live.id_tag)
SELECT t.id FROM `tag_build` t WHERE t.id_object IN (SELECT e.id FROM build e WHERE e.published = 2) ORDER BY `t`.`id` ASC