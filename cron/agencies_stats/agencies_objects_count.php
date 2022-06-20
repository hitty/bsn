#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);
echo $root;

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.context_campaigns.php');
require_once('includes/class.template.php');
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


//log для письма
$log = array();
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Подсчитываем количество активных объектов для пользователей и обновляем поля в users
////////////////////////////////////////////////////////////////////////////////////////////////     
$common_user_ids = $db->fetchall("SELECT users.id,b.build,l.live,c.commercial,ct.country 
                                  FROM ".$sys_tables['users']." 
                                  LEFT JOIN (SELECT id_user,COUNT(*) AS 'build' FROM ".$sys_tables['build']." WHERE published = 1 GROUP BY id_user) b ON ".$sys_tables['users'].".id = b.id_user 
                                  LEFT JOIN (SELECT id_user,COUNT(*) AS 'live' FROM ".$sys_tables['live']." WHERE published = 1 GROUP BY id_user) l ON ".$sys_tables['users'].".id = l.id_user 
                                  LEFT JOIN (SELECT id_user,COUNT(*) AS 'commercial' FROM ".$sys_tables['commercial']." WHERE published = 1 GROUP BY id_user) c ON ".$sys_tables['users'].".id = c.id_user 
                                  LEFT JOIN (SELECT id_user,COUNT(*) AS 'country' FROM ".$sys_tables['country']." WHERE published = 1 GROUP BY id_user) ct ON ".$sys_tables['users'].".id = ct.id_user");
//записываем то что прочитали по пользователям
//обновляем поля
$counter = array('build'=>0,'live'=>0,'commercial'=>0,'country'=>0);
foreach($common_user_ids as $key=>$item){
    $update_query = array();
    if(!empty($item['build'])){
        $update_query[] = "active_build = ".$item['build'];
        $counter['build'] += $item['build'];
    }else $update_query[] = "active_build = 0";
    if(!empty($item['live'])){
        $update_query[] = "active_live = ".$item['live'];
        $counter['live'] += $item['live'];
    }else $update_query[] = "active_live = 0";
    if(!empty($item['country'])){
        $update_query[] = "active_country = ".$item['country'];
        $counter['country'] += $item['country'];
    }else $update_query[] = "active_country = 0";
    if(!empty($item['commercial'])){
        $update_query[] = "active_commercial = ".$item['commercial'];
        $counter['commercial'] += $item['commercial'];
    }else $update_query[] = "active_commercial = 0";
    
    if(!empty($update_query)) $res = $res && $db->query("UPDATE ".$sys_tables['users']." SET ".implode(',',$update_query)." WHERE id = ".$item['id']);
}
$log['stats_users'] = "Обновление статистики объектов в пользователях: ".((!$res)?$db->error:"OK")."<br />".
                " -жилая: ".$counter['live']."<br />"." -стройка: ".$counter['build']."<br />"." -загородная: ".$counter['country']."<br />"." -коммерческая: ".$counter['commercial']."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Подсчитываем количество активных объектов для агентств и обновляем поля в agencies
////////////////////////////////////////////////////////////////////////////////////////////////     
$agencies_list = $db->fetchall("SELECT id_agency,SUM(active_build) as build,SUM(active_live) as live,SUM(active_commercial) AS commercial,SUM(active_country) AS country
                          FROM ".$sys_tables['users']."
                          WHERE id_agency !=0
                          GROUP BY id_agency");
/*
$agencies_list['99']
: array = 
  id: string = 266
  id_agency: string = 99
  build: string = 520
  has_active: bool = TRUE

*/
//обновляем поля
$counter = array('build'=>0,'live'=>0,'commercial'=>0,'country'=>0);
foreach($agencies_list as $key=>$item){
    $update_query = array();
    if(!empty($item['build'])){
        $update_query[] = "active_build = ".$item['build'];
        $counter['build'] += $item['build'];
    } 
    else $update_query[] = "active_build = 0";
    if(!empty($item['live'])){
        $update_query[] = "active_live = ".$item['live'];
        $counter['live'] += $item['live'];
    } 
    else $update_query[] = "active_live = 0";
    if(!empty($item['country'])){
        $update_query[] = "active_country = ".$item['country'];
        $counter['country'] += $item['country'];
    } 
    else $update_query[] = "active_country = 0";
    if(!empty($item['commercial'])){
        $update_query[] = "active_commercial = ".$item['commercial'];
        $counter['commercial'] += $item['commercial'];
    } 
    else $update_query[] = "active_commercial = 0";
    if(!empty($update_query)) $res = $res && $db->query("UPDATE ".$sys_tables['agencies']." SET ".implode(',',$update_query)." WHERE id = ".$item['id_agency']);
}
$log['stats'] = "Обновление статистики объектов в агентствах: ".((!$res)?$db->error:"OK")."<br />".
                " -жилая: ".$counter['live']."<br />"." -стройка: ".$counter['build']."<br />"." -загородная: ".$counter['country']."<br />"." -коммерческая: ".$counter['commercial']."<br />";
//$res = true;

$log = implode('<br />',$log);


if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
$mailer_title = 'Статистика агентств на bsn.ru:';         
$html = "Cтатистика агентств на bsn.ru:<br />".$log ;
$emails = array(
    array(
        'name' => '',
        'email'=> 'web@bsn.ru'
    )
);
/*
if(!empty( $agency_info['email_service'] ) ) 
    $emails[] = array(
        'name' => '',
        'email'=> $agency_info['email_service']
    );
*/
//отправка письма
$sendpulse = new Sendpulse( );
$result = $sendpulse->sendMail( $mailer_title, $html, false, false, $mailer_title, 'no-reply@bsn.ru', $emails );
    
?>