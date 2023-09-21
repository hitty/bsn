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
require_once('includes/simple_html_dom.php');    //класс для парсинга html
require_once('includes/class.robot.php');        // класс с функциями робота, нужен для получения адреса
require_once('includes/functions.php');          // функции  из крона
require_once('includes/class.email.php');
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");


// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//require_once('includes/class.ipgeobase.php');          // класс определения адреса

$ip_to_found = $db->fetchall("SELECT DISTINCT ip FROM ".$sys_tables['ip_geodata']." WHERE txt_addr = '' AND id_geodata = 0",'ip');
//$ip_to_found = array("5.18.225.6"=>"5.18.225.6");
if(empty($ip_to_found)){
    //шлем отчет и выходим
    $mailer = new EMailer('mail');
    $mail_html = "новых IP для поиска геоданных не найдено";
    $mail_html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $mail_html);
    $mailer->Body = $mail_html;
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Поиск геоданных по IP через ipgeobase');
    $mailer->IsHTML(true);
    $mailer->AddAddress("hitty@bsn.ru");
    $mailer->From = 'no-reply@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
    $mailer->Send();
    die();
}else $ip_to_found = array_keys($ip_to_found);


$ips_total = count($ip_to_found);
$ips_geo_found = 0;

$url = "http://api.2ip.com.ua/geo.xml?ip=";

foreach($ip_to_found as $key=>$item){
    
    $curl = curl_init($url.$item);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 3);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);

    //делаем запрос
    $html = curl_exec($curl);
    //$header  = curl_getinfo($curl);
    $data = xml2array($html);
    $data = $data['geo_api'];
    if($data['region_rus'] == 'Санкт-Петербург'){
        $city = (!empty($data['city_rus'])?$data['city_rus']:$data['region_rus']);
        $robot = new BNXmlRobot();
        list($id_geodata,$txt_addr) = array_values($robot->getGeoIdFromString($city,1));
    }
    else $txt_addr = (!empty($data['country_rus'])?$data['country_rus'].", ":"").$data['region_rus'].($data['region_rus']!=$data['city_rus']?", ".$data['city_rus']:"");
    if(!empty($id_geodata)){
        ++$ips_geo_found;
        $db->querys("UPDATE ".$sys_tables['ip_geodata']." SET id_geodata = ?, txt_addr = ? WHERE ip = ?",$id_geodata,$txt_addr,$item);
    }else $db->querys("UPDATE ".$sys_tables['ip_geodata']." SET txt_addr = ? WHERE ip = ?",$txt_addr,$item);
}

//отправляем отчет
$mailer = new EMailer('mail');
$mail_html = $ips_total." ip проставлено, втч определено ".$ips_geo_found;
$mail_html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $mail_html);
$mailer->Body = $mail_html;
$mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Поиск геоданных по IP через ipgeobase');
$mailer->IsHTML(true);
$mailer->AddAddress("hitty@bsn.ru");
$mailer->From = 'no-reply@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
$mailer->Send();

?>