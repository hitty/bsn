#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(empty($root)) $root = realpath('/home/bsn/sites/test.bsn.ru/public_html/trunk/');

if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

error_reporting( E_ALL );
if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

/**
* Обработка новых объектов
*/
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.content.php');       // Config (конфигурация сайта)
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
//$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
//$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.memcache.php');     // MCache (memcached, кеширование в памяти)
require_once('includes/class.telegram.php');
$memcache = new MCache(Config::$values['memcache']['host'], Config::$values['memcache']['port']);

define ('__SERVER_NAME__',$_SERVER['SERVER_NAME']);
define('IS_DEBUG_MODE', preg_match('/.+\.int/i', __SERVER_NAME__) ? true : false);
$bsn_url = IS_DEBUG_MODE ? "https://www.bsn.ru/" : "http://st.bsn.ru/";
 
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$news = new Content('news');      
switch(true){
    //вечерняя подборка новостей
    case date('H:i') == "17:40":
        $list = $news->getList( 30, 0, false, false, 'published = 1 AND DATE(`datetime`) = CURDATE()' );
        $evening_content = array();
        foreach( $list as $k=>$item ) $evening_content[] = $news->getNewsItemTelegramSnippet( $item, true, true );
        Telegram::pushToChannel( implode( "\r\n\r\n", $evening_content ) );
    break;
    //по умолчанию - просто постим новости
    default:
        
        $list = $news->getList(30,0,false,false,'telegram_feed = 1 AND published = 1 AND datetime <= NOW()');                                                
        foreach($list as $k=>$item){
			//$content['content'] = $news->getNewsItemLink($item);
            $content = $news->getNewsItemTelegramSnippet($item);
			//TelegramController::pushToChannel($content['content'],false);
            Telegram::pushToChannel( $content['content'] );
            if( !IS_DEBUG_MODE ) $db->querys("UPDATE ".$sys_tables['news']." SET `telegram_feed` = 3 WHERE id = ?", $item['id']);
        }
       
}




//если были ошибки выполнения скрипта
if(filesize($error_log)>10){
    $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
    $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
    $error_log_text .= '</font>';
} else $error_log_text = "";

?>