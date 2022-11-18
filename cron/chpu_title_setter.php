#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("set names ".Config::$values['mysql']['charset']);

$sys_tables = Config::$sys_tables;
header('Content-Type: text/html; Charset='.Config::$values['site']['charset']);   

 
function fill_tables($systbl,$sourceFieldName){
    global $db;
    $r=0;
    echo "<br>Processing TABLE '".$systbl."'<br>";
    $list = $db->fetchall("SELECT `id`,`".$sourceFieldName."` FROM ".$systbl."");
    foreach($list as $k=>$item){
        $chpu_title = $item['id'].'_'.Convert::ToTranslit($item[$sourceFieldName]);
        $db->querys("UPDATE ".$systbl." SET `chpu_title` =? WHERE id=?", $chpu_title, $item['id'] );
        $r++;
    }
    echo "Records updated: ".$r."<br>";
}
 
$proc_tables = array(
    $sys_tables['agencies'] => 'title'/*,
    $sys_tables['news'] => 'title',
    $sys_tables['articles'] => 'title',
    $sys_tables['calendar_events'] => 'title',
    $sys_tables['opinions_predictions']  => 'annotation'*/
);

foreach($proc_tables as $systbl=>$sourceFieldName){
    fill_tables($systbl,$sourceFieldName);
}

?>
