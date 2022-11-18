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
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

// скрипт предназначен для объединения старых системных сообщений с id_user_from = 0 в диалоги по id_user_to

//читаем заглавные сообщения и ветки к ним
$dialog_heads = $db->fetchall("SELECT h.*,GROUP_CONCAT(".$sys_tables['messages'].".id) AS child_ids
                               FROM               
                               (SELECT h.id AS id_head,h.id_user_to
                                FROM ".$sys_tables['messages']." h
                                WHERE h.id_user_from = 0 GROUP BY h.id_user_to ORDER BY h.`datetime_create` ASC) AS h
                               LEFT JOIN ".$sys_tables['messages']." ON h.id_user_to = ".$sys_tables['messages'].".id_user_to AND 
                                                                        ".$sys_tables['messages'].".id_user_from = 0 
                                                                        AND ".$sys_tables['messages'].".id != h.id_head
                               GROUP BY id_head",'id_head');
foreach($dialog_heads as $key=>$item){
    $db->querys("UPDATE ".$sys_tables['messages']." SET id_user_from = 45523 WHERE id = ?",$item['id_head']);
    $parent_msg_id = $item['id_head'];
    if(!empty($item['child_ids']))
        $item['child_ids'] = explode(',',$item['child_ids']);
        foreach($item['child_ids'] as $k=>$i){
            $db->querys("UPDATE ".$sys_tables['messages']." SET id_user_from = 45523,id_parent = ? WHERE id = ?",$parent_msg_id,$i);
            $parent_msg_id = $i;
        }
}

?>
