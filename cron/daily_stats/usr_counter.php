#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);


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
$db->query("set names ".Config::$values['mysql']['charset']);

$sys_tables = Config::$sys_tables;
header('Content-Type: text/html; Charset='.Config::$values['site']['charset']);   
 $db->select_db('estate');
 $list = $db->fetchall("
 SELECT IFNULL(l.cnt,0) + IFNULL(b.cnt,0) + IFNULL(c.cnt,0) + IFNULL(co.cnt,0) as summ, l.id_user FROM (
 
    (SELECT IFNULL(COUNT(*),0) as cnt, common.users.id as id_user
    FROM `live`  
    LEFT JOIN common.users ON common.users.id = live.id_user
    WHERE published = 1 AND common.users.id_agency = 0
    GROUP BY id_user) l
    
    LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cnt, common.users.id as id_user
    FROM `build`  
    LEFT JOIN common.users ON common.users.id = build.id_user
    WHERE published = 1 AND common.users.id_agency = 0
    GROUP BY id_user) b ON b.id_user = l.id_user
    
    LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cnt, common.users.id as id_user
    FROM `commercial`  
    LEFT JOIN common.users ON common.users.id = commercial.id_user
    WHERE published = 1 AND common.users.id_agency = 0
    GROUP BY id_user) c ON c.id_user = l.id_user

    LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cnt, common.users.id as id_user
    FROM `country`  
    LEFT JOIN common.users ON common.users.id = country.id_user
    WHERE published = 1 AND common.users.id_agency = 0
    GROUP BY id_user) co ON co.id_user = l.id_user

) GROUP BY l.id_user");
if(!empty($db->error)) die($db->error);
$count = 0;
foreach ($list as $k => $item) if($item['summ']>=2 and $item['summ']<=3) $count++;

echo "---------->>>".$count; //кол-во пользователей имеющих по 2 или 3 размещенных объекта
?>
