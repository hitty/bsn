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
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
// вспомогательные таблицы
$sys_tables = Config::$sys_tables; 
include('includes/excel_reader2.php');  // конвертация excel в array

$file = $root.'/cron/seo/seo.xls';

$data = new Spreadsheet_Excel_Reader($file);
 
if(!empty($data)){
    for($row=2; $row<=$data->rowcount(); $row++){
        for($col=1; $col<=$data->colcount(); $col++){
            $rows[$row][$col] = $data->val($row,$col);
        }    
    } 
}

foreach($rows as $key=>$value){
    $pretty_url = str_replace('https://www.bsn.ru/','',$value[2]);
    echo $pretty_url = trim($pretty_url,'/');
    echo "\n";
    $seo_text = $db->fetch("SELECT seo_text FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",$pretty_url);
    $db->query("INSERT INTO ".$sys_tables['pages_seo']." SET pretty_url=?,title=?,h1_title=?,description=?,keywords=?,seo_text=?
               ON DUPLICATE KEY UPDATE                                 title=?,h1_title=?,description=?,keywords=?,seo_text=?",
               $pretty_url, $value[3], $value[4], $value[5], $value[6], !empty($seo_text['seo_text'])?$seo_text['seo_text']:$value[7],
                            $value[3], $value[4], $value[5], $value[6], !empty($seo_text['seo_text'])?$seo_text['seo_text']:$value[7]
    );

}


