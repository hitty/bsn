#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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
$prox = 'ip_вашей_машины или localhost:9050';
$a = get('internet.yandex.ru',$prox); 
echo $a;

 function get($url,$proxy) { 
        $ch = curl_init();   
        curl_setopt($ch, CURLOPT_URL,$url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.1) Gecko/2008070208'); 
        curl_setopt($ch, CURLOPT_PROXY, "$proxy"); 
        $ss = curl_exec($ch); 
        if(curl_error($ch)) echo 'error:' . curl_error($ch);
        curl_close($ch); 
        return $ss; 
} 
?>
