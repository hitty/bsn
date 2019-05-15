#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);
echo $root;

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
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//читаем список всех тегов
$tags_list = $db->fetchall("SELECT id,id_category,LOWER(title) AS title,id_similar,difference_level FROM ".Config::$values['sys_tables']['content_tags']." WHERE ".Config::$values['sys_tables']['content_tags'].".id_similar = 0",'id');
foreach($tags_list as $key=>$value){
    //убираем все кроме букв и цифр
    $tags_list[$key]['title'] = preg_replace('/[^а-яa-z0-9]/sui','',$value['title']);
}

foreach($tags_list as $key=>$value){
    $k = $key + 1;
    if(empty($tags_list[$key])) continue;
    //флаг, обозначающий, нашли ли такие же в цифрах и буквах
    $has_equals = false;
    //флаг, обозначающий, нашли ли похожие в цифрах и буквах
    $has_similars = false;
    //так как будет много обращений, пихаем id исходного и его title в переменные
    $this_id = $tags_list[$key]['id'];
    $this_title = $tags_list[$key]['title'];
    $this_title_splitted = preg_split('//u',$this_title, -1, PREG_SPLIT_NO_EMPTY);
    //ищем впереди такие же теги (то есть теги, совпадающие в цифрах и буквах)
    while($k<count($tags_list)){
        //если нашли совсем такой же, ставим новому тегу id_similar = id исходного, а исходному свой
        if( !empty($tags_list[$k]) && $this_title == $tags_list[$k]['title']){
            if (!$has_equals){
                $equal_tags_ids = array();
                $has_equals = true;
            }
            //записываем дубль, чтобы потом откорректировать таблицу news_tags
            $equal_tags_ids[] = $tags_list[$k]['id'];
            //удаляем такие же теги из базы и из прочитанного
            $db->query("DELETE FROM ".Config::$values['sys_tables']['content_tags']." WHERE id = ".$tags_list[$k]['id']);
            unset($tags_list[$k]);
        }
        else
        //если длины имен совпадают, значит разница в символах
        if(!empty($tags_list[$k]) && mb_strlen($this_title) == mb_strlen($tags_list[$k]['title'])){
            //смотрим, сколько разных букв:
            $different_letters = 0;
            $new_title = preg_split('//u',$tags_list[$k]['title'], -1, PREG_SPLIT_NO_EMPTY);
            foreach($this_title_splitted as $pos=>$char){
                if($char!=$new_title[$pos])
                    ++$different_letters;
            }
            //если различаются меньше чем на три буквы и меньше чем на половину title
            if($different_letters < 3 && $different_letters < mb_strlen($this_title)/2 && $different_letters < mb_strlen($tags_list[$k]['title'])/2){
                //если это совпадение точнее чем то, что уже было у тега
                if($tags_list[$k]['difference_level'] == 0 || $different_letters < $tags_list[$k]['difference_level']){
                    //устанавливаем исходному тегу свой id в поле id_similar
                    if (!$has_similars){
                        $similar_tags_ids = array();
                        $has_similars = true;
                    }
                    $similar_tags_ids[] = $tags_list[$k]['id'];
                    //устанавливаем найденному похожему тегу id_similar =  id исходного
                    $db->query("UPDATE ".Config::$values['sys_tables']['content_tags']." SET id_similar = ".$this_id.", difference_level = ".$different_letters." WHERE id = ".$tags_list[$k]['id']);
                    $tags_list[$k]['id_similar'] = $this_id;
                    $tags_list[$k]['differnet_letters'] = $different_letters;
                }
                //если у найденного тега есть более похожий, то устанавливаем текущему id_similar = id найденного
                else{
                    $db->query("UPDATE ".Config::$values['sys_tables']['content_tags']." SET id_similar = ".$tags_list[$k]['id'].", difference_level = ".$different_letters." WHERE id = ".$this_id);
                }
            }
        }
        ++$k;
    }
    //если для тега нашлись такие же, корректируем таблицу news_tags
    if($has_equals){
        $equal_tags_ids = implode(', ',$equal_tags_ids);
        echo $tags_list[$key]['id'].': '.$equal_tags_ids.";\r\n";
        $db->query("UPDATE ".Config::$values['sys_tables']['news_tags']." SET id_tag = ".$this_id." WHERE id_tag IN (".$equal_tags_ids.")");
    }
}
/*
SELECT example_id, similar_title, GROUP_CONCAT(tags_title) AS similars, GROUP_CONCAT(tags_id) AS similar_ids FROM
(SELECT tags.id AS tags_id, tags.title AS tags_title,similar.id AS example_id, similar.title AS similar_title, tags.difference_level AS this_diff
FROM tags
INNER JOIN tags as similar ON similar.id = tags.id_similar
GROUP BY tags.id
ORDER BY `tags`.`difference_level` DESC) as a GROUP BY example_id
*/
?>