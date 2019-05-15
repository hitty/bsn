#!/usr/bin/php
<?php
ini_set("memory_limit", "8024M");
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
require_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/gen_sitemap/error.log';
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
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.sitemap.php');       // подключение класса генератора xml   
require_once('includes/class.email.php');
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array
 
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");
$GLOBALS['db']=$db;
$url=DEBUG_MODE?'https://www.bsnnew.int':'https://www.bsn.ru';

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//логи для почты
$data = $files = $log = array();

//получение списка файлов
$filename = 'sitemap.xml';
$contents = file_get_contents($filename);
$xml_str=xml2array($contents); unset($contents);
foreach ($xml_str['sitemapindex']['sitemap'] as $object) $files[] =  str_replace('https://www.bsn.ru/','',$object['loc']);      
unset($xml_str); unset($filename);
$index = 0;
//распарсивание файлов
foreach($files as $file){
    echo $file." : ".convert(memory_get_usage(true))."\n";
    $contents = file_get_contents($file); 
    $xml_str=xml2array($contents); unset($contents);  
    // preg_match_all("/sitemap([0-9]{0,2})\.xml/sui",$file,$file_index);
    // $file_index = $file_index[1][0];
    foreach ($xml_str['urlset']['url'] as $url){
        $data[$index]['url'] = str_replace('https://www.bsn.ru','',$url['loc']);        
        $data[$index]['changefreq'] = $url['changefreq'];        
        $data[$index]['priority'] = $url['priority'];    
        $data[$index]['priority'] = $url['priority'];    
        $data[$index]['index_file'] = str_replace('sitemaps/sitemap_',$file);    
        ++$index;
    }
    unset($xml_str);
}
//create csv
download_send_headers("urls.csv");
file_put_contents('excel/urls.csv',array2csv($data));
exit(0);   

function memoryUsage($usage, $base_memory_usage) {
    return "Bytes diff: ".($usage - $base_memory_usage);
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
function convert($size)
 {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
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
?>
