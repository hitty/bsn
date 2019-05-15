#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

/**
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/functions.php');          // функции  из модуля
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
include('cron/class.xml.generate.php');
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//логирование выгрузок xml-я
$log = array();

$img_folder = Config::Get('img_folders/live');

define('__XMLPATH__',ROOT_PATH.'/xml/build.xml');

$xml = new DOMDocument('1.0','UTF-8');

$xmlUrlset = $xml->appendChild($xml->createElement('realty-feed'));
//$xmlUrlset->setAttribute('xmlns','http://webmaster.yandex.ru/schemas/feed/realty/2010-06');

$xmlgenerationdate = $xmlUrlset->appendChild($xml->createElement('generation-date'));
$xmlgenerationdate -> appendChild($xml->createTextNode( date('c') ));
$list = array();
//Init xml-making
$xmlItem = new generateXml;

$sql = "SELECT  
            ".$sys_tables['build'].".id, 
           CONCAT(
                'Продажа ',
                IF(elite=1,'элитной ',''),
                ".$sys_tables['build'].".rooms_sale,
                '-комнатной квартиры в новостройке',
                IF(".$sys_tables['build'].".txt_addr<>'', CONCAT(' - ', ".$sys_tables['build'].".txt_addr, ' '), '')
           ) as `header`

        FROM ".$sys_tables['build']." 
        WHERE ".$sys_tables['build'].".`published` = 1 
        ORDER BY ".$sys_tables['build'].".`id` ASC
";
$list = $db->fetchall($sql) or die($db->error);
$log['build'] = count($list);
foreach($list as $k=>$item){
    $xmlItem->append();
    $xmlItem->attr("url","https://www.bsn.ru/build/sell/".$item['id']."/");
    $xmlItem->attr("title",$item['header']); 
}
$xml->formatOutput = true;
$xml->save(__XMLPATH__);
if(file_exists(__XMLPATH__.".gz")) unlink(__XMLPATH__.".gz");
exec("gzip -rv ".__XMLPATH__);
exec("chmod 777 ".__XMLPATH__.".gz");
?>
