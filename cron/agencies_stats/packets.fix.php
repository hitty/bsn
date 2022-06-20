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

// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.context_campaigns.php');
require_once('includes/class.template.php');
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


$list = $db->fetchall("SELECT 
                            ".$sys_tables['agencies'].".id as id_agency, 
                            ".$sys_tables['tarifs_agencies'].".* 
                       FROM ".$sys_tables['agencies']." 
                       RIGHT JOIN ".$sys_tables['tarifs_agencies']." ON ".$sys_tables['tarifs_agencies'].".id = ".$sys_tables['agencies'].".id_tarif
                       WHERE ".$sys_tables['agencies'].".id_tarif > 0");
foreach($list as $k=>$item){
    $db->query("UPDATE ".$sys_tables['agencies']." SET 
        promo = ?, premium = ?, vip = ?, staff_number = ?, action = ?, video = ?, tarif_end = '2015-11-01' WHERE id = ?",
        $item['promo'], $item['premium'], $item['vip'], $item['staff_number'], $item['action'], $item['video'], $item['id_agency']
    );
                                  
}

?>