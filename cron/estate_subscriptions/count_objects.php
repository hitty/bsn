#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);    
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
require_once('includes/class.estate.php');
require_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');
require_once('includes/class.email.php');
require_once('includes/class.paginator.php');
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);

$tables = Config::$sys_tables;
$list = $db->fetchall("SELECT *,UNIX_TIMESTAMP(`last_seen_setinterval`) AS `check_time` FROM ".$tables['objects_subscriptions']." WHERE confirmed = 1");       // список подтвержденных подписок

foreach($list as $k => $item){     

    if((time() > $item['check_time']) && ($item['check_time'] != 0))  // Обновление поля last_seen и last_delivery (last_delivery также обновляется в newsletter.php)
        $db->querys("UPDATE ".$tables['objects_subscriptions']." SET `last_seen` = NOW(), `new_objects` = '0' ,`last_seen_setinterval` = '0000-00-00 00:00' WHERE `id`=?", $item['id']);

    
    $url = $item['url'];  
    $parsed_url = parse_url($url);     
    $params = explode('&',$parsed_url['query']);          
    $clauses = array();
    $parameters = $work_params_data = array();
    foreach($params as $k => $v){
        $param = explode('=',$v);
        $parameters[$param[0]] = $param[1];
    }
    
    // определяем тип недвижимости
    $estate_type = "";
    $estate_types = array('live','build','commercial','country','inter');
    foreach ($estate_types as $k => $v){
        if ( strstr( $url, $v . '/' ) != '' ) {
            $estate_type = $v;  
            break;
        }
    }
    switch($estate_type){
        case 'inter':
            $estate = new EstateListInter();
            break;
        case 'build':
            $estate = new EstateListBuild();
            break;
        case 'commercial':
            $estate = new EstateListCommercial();
            break;
        case 'country':
            $estate = new EstateListCountry();
            break;
        case 'live':
            $estate = new EstateListLive();
            break;
    }  
    if(empty($estate_type)) continue;
    // определяем тип сделки
    $deal_type = '';
    $deal_types = array('rent','sell');
    foreach ($deal_types as $k => $v){
        if (strpos($url,$v)) $deal_type = $v;  
    }
  
    $estate_search = new EstateSearch();
    list( $parameters, $clauses, $get_parameters, $reg_where ) = $estate_search->formParams( $url, false, $estate_type, $deal_type );
    // "прямые" условия
    $where = array();
    $where_clauses = $estate->makeWhereClause($clauses);
    if(!empty($where_clauses)) $where[] = $where_clauses;
    
    if(!empty($reg_where)) $where[] = " (".implode(" OR ", $reg_where).") ";
    $where[] = $estate->work_table.".`date_in`>'".$item['last_seen']."'";
    $count = new Paginator($estate->work_table, 1, implode(" AND ", $where)); 
    $db->querys("UPDATE ".$tables['objects_subscriptions']." SET `new_objects`=? WHERE `id`=?", $count->items_count,$item['id']); // Обновление поля с количеством новых объектов
}
?>
