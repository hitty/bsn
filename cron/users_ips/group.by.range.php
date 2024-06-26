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
$db->querys("set names ".Config::$values['mysql']['charset']);
$sys_tables = Config::$sys_tables;

$list = $db->fetchall("

                        SELECT
                            ip,
                            SUBSTRING_INDEX( ip, '.', 3 ) as subip,
                            COUNT(ip) as count
                        FROM ".$sys_tables['blacklist_ips']." 
                        WHERE `range` = 2                      
                        GROUP BY ( subip )
                        HAVING COUNT > 3
                        ORDER BY COUNT DESC"
);
foreach($list as $k=>$item){
            $db->querys("DELETE 
                        FROM 
                            ".$sys_tables['blacklist_ips']." 
                        WHERE 
                            `range` = 2 AND ip LIKE '".$item['subip']."%' " 
            );  
            //$db->querys("INSERT INTO ".$sys_tables['blacklist_ips']." SET `range` = 1, ip = CONCAT(?, '.')", $item['subip'])  ;
}
?>