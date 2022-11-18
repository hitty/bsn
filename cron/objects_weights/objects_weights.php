#!/usr/bin/php
<?php
$overall_time_counter = microtime(true);
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

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/comagic/spam_error.log';
$test_performance = ROOT_PATH.'/cron/gen_sitemap/test_performance.log';
file_put_contents($error_log,'');
file_put_contents($test_performance,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.estate.php');     // методы для подсчета весов
require_once('includes/functions.php');    // функции  из крона
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");



// вспомогательные таблицы модуля

$sys_tables = Config::$sys_tables;
// /home/bsn/sites/bsn.ru/public_html/cron/objects_weights/objects_weights.php
//читаем веса количества фотографий
$photos_weights = $db->fetchall("SELECT * FROM ".$sys_tables['weights_photos'],'id');
//для всех типов недвижимости кроме build, build отдельно

//$estate_types = array('live','commercial','country');
$estate_types=array('country');
foreach($estate_types as $key=>$estate_type){
    switch($estate_type){
        case 'live':$weight = new Estate(TYPE_ESTATE_LIVE);break;
        case 'build':$weight = new Estate(TYPE_ESTATE_BUILD);break;
        case 'country':$weight = new Estate(TYPE_ESTATE_COUNTRY);break;
        case 'commercial':$weight = new Estate(TYPE_ESTATE_COMMERCIAL);break;
    }
    //читаем невзвешенные объекты по жилой и таблицу весов
    $list_unweighted = $db->fetchall("SELECT id FROM ".$sys_tables[$estate_type]." WHERE weight=0");
    //$list_unweighted = $db->fetchall("SELECT * FROM ".$sys_tables[$estate_type]." WHERE id=2326635");
    $weights = $weight->getWeightsList($estate_type)[$estate_type];
    foreach($list_unweighted as $k=>$item_data){
        //считаем, сколько фотографий у объекта
        $item_weight = $weight->getItemWeight($item_data['id'],$estate_type);
        $db->querys("UPDATE ".$sys_tables[$estate_type]." SET weight=? WHERE id=?",$item_weight,$item_data['id']);
    }
}
/*
$estate_type = 'build';
//для build разбиваем выборку по 500 элементов
$fetch_limit = 500;
$list_unweighted = $db->fetchall("SELECT * FROM ".$sys_tables[$estate_type]." WHERE weight=0 AND published=1 LIMIT 0,".$fetch_limit);
while ($list_unweighted){
    $build_weights = Estate::getWeightsList($db,$estate_type);
    foreach($list_unweighted as $k=>$item_data){
        //считаем, сколько фотографий у объекта
        $photos_count = $db->fetch("SELECT COUNT(*) AS count FROM ".$sys_tables[$estate_type.'_photos']." WHERE id_parent=".$item_data['id'])['count'];
        $item_weight = Estate::getItemWeight($item_data,$photos_count,$build_weights,$photos_weights,$estate_type);
        $db->querys("UPDATE ".$sys_tables[$estate_type]." SET weight=? WHERE id=?",$item_weight,$item_data['id']);
    }
    $fetch_limit += 500;
    $list_unweighted = $db->fetchall("SELECT * FROM ".$sys_tables[$estate_type]." WHERE weight=0 LIMIT ".($fetch_limit-500).",".$fetch_limit);
}
*/
?>