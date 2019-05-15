#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);

include('cron/robot/robot_functions.php');    // ???????  ?? ?????

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

/**
* ????????? ????? ????????
*/
// ??????????? ??????? ????
include('includes/class.config.php');       // Config (???????????? ?????)
Config::Init();
include('includes/class.convert.php');      // Convert, Valdate (???????????????, ???????? ??????????)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // ???????  ?? ??????
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (???? ??????)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
require_once('includes/class.paginator.php');
require_once('includes/class.estate.php');
require_once('includes/class.estate.statistics.php');
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;

define('__XMLPATH__',ROOT_PATH.'/elama_realty.xml');
define('__URL__','https://www.bsn.ru/');


$db->select_db('estate');
// ????????????? ??????? ???????
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = '".Config::$values['mysql']['lc_time_names']."';");

// ??????????????? ??????? ??????
$sys_tables = Config::$sys_tables;

if(empty($_GET['d'])){
    $estate_types = array(1=>'live',2=>'build',3=>'commercial',4=>'country');
    foreach($estate_types as $key=>$estate){
        //1. ??????????? id-?????? ??, ?? ? ?????????
        $list = $db->fetchall("
                                SELECT ".$sys_tables[$estate].".id_user
                                FROM ".$sys_tables[$estate]." 
                                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables[$estate].".id_user
                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                WHERE ( ".$sys_tables['agencies'].".activity&2 AND ".$sys_tables['users'].".id_agency > 0 )  OR ".$sys_tables[$estate].".info_source = 4 OR ".$sys_tables['users'].".id_agency = 0
                                GROUP BY ".$sys_tables[$estate].".id_user");
        $ids = array(0);
        if(!empty($list)){
            foreach($list as $k=>$id) $ids[] = $id['id_user'];
        }
        //2. ??????? ?????????? ?????????
        if(!empty($ids)){
            $list = $db->fetchall("
                SELECT ".$sys_tables[$estate].".id_user, 
                ".$sys_tables[$estate].".id,
                ".$sys_tables['agencies'].".title, 
                ".$sys_tables['agencies'].".phones, 
                COUNT(*), 
                ".$sys_tables[$estate].".seller_phone 
                FROM ".$sys_tables[$estate]."
                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables[$estate].".id_user
                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                WHERE ".$sys_tables[$estate].".id_user NOT IN (".implode(',',$ids).") AND ".$sys_tables['users'].".id > 0
                GROUP BY ".$sys_tables[$estate].".id_user, ".$sys_tables['agencies'].".phones, ".$sys_tables[$estate].".seller_phone
            ");
            if(!empty($list)){
                foreach($list as $k=>$item){
                    $phones = array();
                    $phones = array_merge(Convert::ToPhone($item['phones']),Convert::ToPhone($item['seller_phone']));
                    if(!empty($phones)){
                        foreach($phones as $k_p=>$phone) {
                            if(strlen($phone)>=7) $db->query("INSERT INTO ".$sys_tables['phone_prefixes']." SET type=?, phone_number=?, id_user=?", $key, $phone, $item['id_user']);
                        }
                    }
                }
            }
        }
    }
}

$db->query("set names cp1251") or die($db->error);
$list = $db->fetchall("SELECT id as prefix, phone_number FROM ".$sys_tables['phone_prefixes']);
foreach($list as $k=>$item){
    $filename =  'phones';;
    download_send_headers(createCHPUTitle($filename.date("d-m-Y")).".csv");
    file_put_contents('excel/phones.csv',array2csv($list));
    exit(0);        
}

function array2csv(array &$csv_array)
{
   if (count($csv_array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'w');
   
   fputcsv($df, array_keys(reset($csv_array)),';');
   foreach ($csv_array as $row) {
      fputcsv($df, $row,';');
   }
   fclose($df);  
   return ob_get_clean();
}

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    header("Cache-Control: public"); 
    header("Content-Type: application/octet-stream");
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment;filename={$filename}");
}
function createCHPUTitle($title){
    $title = trim($title);
    $ru=array('?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?',' ','.',',','-','"','/','\\');
    $en=array('a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya','_','','','','','','');
    $chpu_title = mb_strtolower($title, 'UTF-8');
    $chpu_title = str_replace($ru,$en,$chpu_title);
    return trim($chpu_title);
}

?>