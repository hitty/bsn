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


//log для письма
$log = array();
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Подсчитываем количество активных объектов для агентств и обновляем поля в agencies
////////////////////////////////////////////////////////////////////////////////////////////////     
$adv_agencies = $db->fetchall("SELECT u.id as id_user FROM ".$sys_tables['users']." u
                      LEFT JOIN ".$sys_tables['agencies']." a ON a.id=u.id_agency 
                      WHERE a.activity & 2 AND a.`id`!=4472 and a.xml_status = 1"); //выборка всех кроме недвижимости города    

$agencies = array(
    '29298' => array( //JCAT
		'2015-11-11 00:00:00' => array(
            'live' => 751,
            'build' => 560,
            'commercial' => 945,
            'country' => 379,
        ),
		'2015-11-12 00:00:00' => array(
            'live' => 746,
            'build' => 585,
            'commercial' => 944,
            'country' => 399,
        ),
		'2015-11-13 00:00:00' => array(
            'live' => 795,
            'build' => 565,
            'commercial' => 944,
            'country' => 393,
        ),
		'2015-11-14 00:00:00' => array(
            'live' => 783,
            'build' => 565,
            'commercial' => 927,
            'country' => 387,
        ),
		'2015-11-15 00:00:00' => array(
            'live' => 778,
            'build' => 564,
            'commercial' => 923,
            'country' => 385,
        ),
		'2015-11-16 00:00:00' => array(
            'live' => 770,
            'build' => 574,
            'commercial' => 1126,
            'country' => 407,
        ),
		'2015-11-17 00:00:00' => array(
            'live' => 771,
            'build' => 579,
            'commercial' => 1128,
            'country' => 399,
        ),
    )
);
foreach($agencies as $id_user => $date_array){
    foreach($date_array as $date => $counts){
        foreach($counts as $estate_type => $count){
            for($i=1; $i<=$count; $i++){
                $db->query("INSERT INTO ".$sys_tables['billing']." SET external_id = ?, bsn_id = ?, date = ?, type = ?, bsn_id_user = ?, status = ?, adv_agency = ?",
                    mt_rand(10000,200000), mt_rand(100000,1300000), $date, $estate_type,  $id_user, 2, 1
                );
            }
        }
    }
}

?>