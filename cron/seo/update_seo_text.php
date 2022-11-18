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



// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
include('cron/robot/class.xml2array.php');  // конвертация xml в array

$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
// вспомогательные таблицы
$sys_tables = Config::$sys_tables; 

$list = $db->fetchall("SELECT * FROM common1.pages_seo");
foreach($list as $k=>$item){
    $db->querys("UPDATE ".$sys_tables['pages_seo']." SET ". ( !empty($item['seo_text']) ? "seo_text = '".$item['seo_text']."'," : ""  ) ." title = ?, h1_title = ?, description = ?, keywords = ? WHERE pretty_url = ?",
                                $item['title'], $item['h1_title'], $item['description'], $item['keywords'], $item['pretty_url']
    );
}

$filename = ROOT_PATH.'/cron/seo/texts.xml';
$xml_values = array();
//читаем в строку нужный файл
$contents = file_get_contents($filename);
$xml_str=xml2array($contents);
foreach ($xml_str['Root']['row'] as $object) $xml_values[] =  $object;
foreach($xml_values as $key=>$values){
    $pretty_url = trim(str_replace('https://www.bsn.ru','',$values['url']), '/');
    $item = $db->fetch("UPDATE ".$sys_tables['pages_seo']." SET seo_text = ? WHERE id IN (
                            SELECT a.id FROM ( 
                                SELECT id FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?
                            ) a
                        )",$values['text'],$pretty_url);
}

 ?>        