#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);
echo $root;
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
include('cron/robot/robot_functions.php');  // функции  из крона
if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
include('includes/class.host.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$sys_tables = Config::$sys_tables;

$list = $db->fetchall( " SELECT ip, city, country  FROM ".$sys_tables['visitors_ips_full']."  WHERE `page_url` LIKE '/' AND `referer` LIKE '/' AND `country` LIKE 'RU' AND `cookie` NOT LIKE '%ym%' GROUP BY ip HAVING COUNT(*) > 7 ORDER BY `visitors_ips_full`.`id`  DESC ");
if( !empty( $list ) ) {
    foreach( $list as $k => $item ){
        //$db->query( " INSERT IGNORE INTO ".$sys_tables['blacklist_ips']." SET ip=?, city=?, country=? ", $item['ip'], $item['city'], $item['country'] );
        $db->query( " DELETE FROM " . $sys_tables['visitors_ips_full'] . " WHERE ip = ?", $item['ip'] );
    }
}

$list = $db->fetchall( " SELECT ip, city, country  FROM ".$sys_tables['visitors_ips_full']."  WHERE country != 'RU' GROUP BY ip HAVING COUNT(*) > 25 ORDER BY `visitors_ips_full`.`id`  DESC ");
if( !empty( $list ) ) {
    foreach( $list as $k => $item ){
        //$db->query( " INSERT IGNORE INTO ".$sys_tables['blacklist_ips']." SET ip=?, city=?, country=? ", $item['ip'], $item['city'], $item['country'] );
        $db->query( " DELETE FROM " . $sys_tables['visitors_ips_full'] . " WHERE ip = ?", $item['ip'] );
    }
}

?>