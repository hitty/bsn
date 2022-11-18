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
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");


// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$page_num = 1;

/*
{
   "common": {
      "version": "1.0",
      "api_key": "ABM6WU0BAAAANfFuIQIAV1pUEYIBeogyUNvVbhNaJPWeM-AAAAAAAAAAAACRXgDsaYNpZWpBczn4Lq6QmkwK6g=="
   },
   "gsm_cells": [
       {
          "countrycode": 250,
          "operatorid": 99,
          "cellid": 42332,
          "lac": 36002,
          "signal_strength": -80,
          "age": 5555
       }
   ],
   "wifi_networks": [
       {
          "mac": "00-1C-F0-E4-BB-F5",
          "signal_strength": -88,
          "age": 0,
       }
   ],
   "ip": {
     "address_v4": "178.247.233.32"
   }
}
*/

//$ip = "5.18.225.6";
$ip = "109.167.249.172";
$url = "http://api.lbs.yandex.net/geolocation";
$user_cookie_file = dirname(__FILE__).'/cookies.txt'; 
                                                                                                                                                                   
$data = array();
$data['common'] = array("version"=>"1.0","api_key"=>"ACA6dVcBAAAAGA82MAIAhLoWszu5ZstNGyHwSxc-tToLW3cAAAAAAAAAAAA9KMdaW8LwKpEdi_WqH0UlkEdSvQ==");
$data['gsm_cells'] = array(array("countrycode"=>250,"operatorid"=>0,"cellid"=>0,"lac"=>0,"signal_strength"=>0,"age"=>0),array("countrycode"=>250,"operatorid"=>0,"cellid"=>0,"lac"=>0,"signal_strength"=>0,"age"=>0));
$data['wifi_networks'] = array(array("mac"=>"","signal_strength"=>0,"age"=>0));
$data['ip'] = array("address_v4"=>$ip);

$curl = curl_init($url);
//устанаваливаем URL
curl_setopt($curl, CURLOPT_URL,$url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',' format : json'));
//результат должен отдаваться в переменную
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
//нужно, чтобы не выдавало ошибку SSL
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//нужно, чтобы проходило редиректы
curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
//автоматическая установка поля Referer при редиректах
curl_setopt($curl,CURLOPT_AUTOREFERER,true);
//если включить след. строку, не будут писаться cookie, заголовки включатся в html и сможет работать get_headers(url)
//curl_setopt($curl, CURLOPT_HEADER, true); 
//версия браузера
curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)");
//файл, где будут храниться cookie
curl_setopt($curl, CURLOPT_COOKIEFILE, $user_cookie_file);
//сюда сохранятся текущие cookie после curl_close
curl_setopt($curl, CURLOPT_COOKIEJAR, $user_cookie_file);
//флаг, что будем использовать POST
curl_setopt($curl, CURLOPT_POST,1);

//передаваемые данные
curl_setopt($curl, CURLOPT_POSTFIELDS, "json=".json_encode($data));
//передаваемые заголовки (без этого не будет отдаваться код страницы)
$headers = array(
    'POST /geolocation HTTP/1.1',
    'Host: api.lbs.yandex.net',
    'Accept-Encoding: identity',
    'Content-type: application/x-www-form-urlencoded'
);
curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);

//делаем запрос
$html = curl_exec($curl);
$header  = curl_getinfo($curl);
//@$response_headers=get_headers($url);
if(!$html){
    $error = curl_error($curl).'('.curl_errno($curl).')';
    return FALSE;
}  
curl_close($curl);

?>