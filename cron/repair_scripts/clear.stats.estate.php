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

$estate_types = array( 'build', 'live', 'commercial', 'country');
$tables = array( '_stats_show_full', '_stats_search_full', '_stats_from_search_full' );
foreach($estate_types as $estate){
    foreach($tables as $table){
        $list = $db->fetchall("SELECT id_parent FROM " . $sys_tables[$estate . $table] . " GROUP BY id_parent", false);
        if(!empty($list)){
            foreach($list as $k=>$item){
                $id = $item['id_parent'];
                //объект отсутствует
                $item = $db->fetch("SELECT * FROM " . $sys_tables[$estate] . " WHERE id = ?", $id);
                if( empty($item) ) {
                    $db->query("DELETE FROM " . $sys_tables[$estate . $table] . " WHERE id_parent = ?", $id);
                }
            }
        }
    }
    
}
?>