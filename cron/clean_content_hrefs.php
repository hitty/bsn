#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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
$db->querys("set names ".Config::$values['mysql']['charset']);
// вспомогательные таблицы
$sys_tables = Config::$sys_tables; 
$tables = array('opinions_predictions', 'news', 'articles', 'calendar_events', 'partners_articles');     
$count = 0; 
foreach($tables as $table){
    switch($table){
        case 'news':
        case 'articles':
        case 'partners_articles':
            $date_sql = 'WHERE YEAR(datetime) < 2016';
            break;
        case 'opinions_predictions':
            $date_sql = 'WHERE YEAR(date) < 2016';
            break;
        case 'calendar_events':
            $date_sql = '';
            break;
        
    }
    $list = $db->fetchall("SELECT * FROM ".$sys_tables[$table]." ".$date_sql);
    
    foreach($list as $k=>$item){    
        $text = !empty($item['content']) ? $item['content'] : $item['text'];
        $field = !empty($item['content']) ? 'content' : 'text';
        preg_match_all('/(<a[^>]*)href=(\"?)([^\s\">]+?)(\"?)([^>]*>)/ismU', $text, $res);  
        if(!empty($res[3][0]) && strstr($res[3][0],'http')!='' && (
                            strstr($res[3][0],'bsn.ru')=='' &&
                            strstr($res[3][0],'interestate.ru')=='' &&
                            strstr($res[3][0],'are-rus.ru')=='' &&
                            strstr($res[3][0],'proestate.ru')=='' &&
                            strstr($res[3][0],'gud-estate.ru')=='' &&
                            strstr($res[3][0],'dizbook.com')=='' &&
                            strstr($res[3][0],'facebook.com')=='' &&
                            strstr($res[3][0],'vk.com')==''
        ))  {
            echo $res[3][0].' - '.$item['id'].' - '.$table;
            echo ++$count."\n<br />";
            $new_text = preg_replace("~<a[^>]+href\s*=\s*[\x27\x22]?[^\x20\x27\x22\x3E]+[\x27\x22]?[^>]*>(.+?)</a>~is", '$1', $text);
            $db->querys("UPDATE ".$sys_tables[$table]." SET ".$field." = ? WHERE id = ?", $new_text, $item['id']);
        }
    }
}               
