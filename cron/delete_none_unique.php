#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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
$error_log = ROOT_PATH.'/cron/cottages/error.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');
/**
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
//Session::Init();
Session::Init(null,null,'public',true);
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$db->select_db('estate');
//првоерка на структуру новой таблицы
$previous_field = '';

/*
$estate_types = array('country','commercial','live','build');
foreach($estate_types as $estate){
    $normal = $db->fetchall("DESCRIBE ".$sys_tables[$estate]);
    $archive = $db->fetchall("DESCRIBE ".$sys_tables[$estate."_archive"]);
    echo $db->last_query."\n";
    foreach($normal as $k=>$n){
        if(!findColumn($n['Field'],$archive)){
            //ALTER TABLE  `country` ADD  `gui` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '' AFTER  `date_in` , ADD INDEX (  `gui` );
            $db->querys("ALTER TABLE ".$sys_tables[$estate."_archive"]." ADD `".$n['Field']."` ".$n['Type']."  DEFAULT  ".$n['Default']." AFTER  `".$previous_field."`");
            echo $db->last_query."\n";
        }
        $previous_field = $n['Field'];
    }
}
  //287571
*/
//перемещение в архив всех объектов, у которых дата меньша чем 250 дней
$estate_types = array('country','commercial','live','build');
foreach($estate_types as $estate){
    $db->querys("INSERT IGNORE INTO ".$sys_tables[$estate."_archive"]." SELECT * FROM ".$sys_tables[$estate]." WHERE published = 2 AND date_change < CURDATE() - INTERVAL  200 DAY");
    echo $db->last_query."\n";
    $db->querys("DELETE FROM ".$sys_tables[$estate]." WHERE published = 2 AND date_change < CURDATE() - INTERVAL 200 DAY");
    echo $db->last_query."\n";
}    
//SHOW TABLES;

$estate_types = array('commercial','country','live','build');
foreach($estate_types as $estate){
    $objects = $db->fetchall("SELECT external_id, id, id_user FROM ".$sys_tables[$estate]." WHERE external_id > 0 GROUP BY id_user, external_id HAVING COUNT(*) > 1");
    if(!empty($objects)) {
        foreach($objects as $o=>$object){
            $db->querys("DELETE FROM ".$sys_tables[$estate]."            WHERE external_id = ? AND id_user = ? AND published = 2", $object['external_id'], $object['id_user']);
            $db->querys("DELETE FROM ".$sys_tables[$estate.'_archive']." WHERE external_id = ? AND id_user = ? AND published = 2", $object['external_id'], $object['id_user']);
        }
    }

    $archive_objects = $db->fetchall("SELECT * FROM ".$sys_tables[$estate.'_archive']." WHERE external_id > 0 GROUP BY id_user, external_id HAVING COUNT(*) > 1");
    if(!empty($archive_objects)) {
        foreach($archive_objects as $ao=>$archive_object) $db->querys("DELETE FROM ".$sys_tables[$estate.'_archive']." WHERE external_id = ?  AND id_user = ? ",$archive_object['external_id'],$archive_object['id_user']);
    }
    
}


function findColumn($field, $array){
    foreach($array as $k=>$item){
        if($item['Field']==$field) return true;
    }
    return false;
}
/*
$seo_list = $db->fetchall("SELECT * FROM ".$sys_tables['pages_seo']." WHERE id >=19258 AND id<=49579 ");
foreach($seo_list as $k=>$item){
    $estate_type = explode("/",$item['pretty_url']);
    if(!empty($estate_type[2]) && !empty($estate_type[3]) && in_array($estate_type[3],array('sell','rent'))){
        $object = $estate_type[2];
        $deal = $estate_type[3];
        $alias = $db->fetch("SELECT * FROM ".$sys_tables['type_objects_'.$estate_type[1]]." WHERE old_alias = ?",$object);
        if(!empty($alias)) {
            $new_alias = str_replace($object.'/'.$deal,$deal.'/'.$alias['new_alias'],$item['pretty_url']);
            $already_exists = $db->fetch("SELECT * FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",$new_alias);
            if(!empty($already_exists)){
                if(empty($already_exists['seo_text']))
                    $db->querys("UPDATE ".$sys_tables['pages_seo']." SET url = ?, title = ?, description = ?, keywords = ?, seo_text = ?, breadcrumbs = ?, filled = ? WHERE pretty_url = ?",
                       $alias['url'], $alias['title'], $alias['description'], $alias['keywords'], $alias['seo_text'], $alias['breadcrumbs'], $alias['filled'], $alias['pretty_url']
                    );
                $db->querys("DElETE FROM ".$sys_tables['pages_seo']." WHERE id = ?",$item['id']);
                
            } else  {
                $db->querys("UPDATE ".$sys_tables['pages_seo']." SET pretty_url = ? WHERE id = ?",
                   $new_alias ,$item['id']
                );
            }
        }
    }
    
}
*/


?>
