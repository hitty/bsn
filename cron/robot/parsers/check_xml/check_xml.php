#!/usr/bin/php
<?php



// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../../../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/robot/parsers/check_xml/error.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

/**
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
include('includes/class.moderation.php'); // Moderation (процедура модерации)
include('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
include('cron/robot/class.xml2array.php');  // конвертация xml в array
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//$link = Request::GetString('link',METHOD_POST);
//if(empty($link)) $link = "";

//$link = "http://www.vashdom-spb.ru/reclama_1c/files/WIN/photo_spb_and_reg55.xml";
//$link = "http://export.web3a.eyetronic.ru/export/bn/export.xml";
//$link = "http://zipal.ru/export/BSN";
//$link = "http://export.web3a.eyetronic.ru/export/bn/export.xml";
//$link = "http://td-nevsky.ru/yAndex_123.xml";
//$link = "http://www.unisto-petrostal.ru/yrl.php";
//$link = "http://limestate.ru/bsn_test.xml";
//$link = "http://www.spbrealty.ru/generate/xml/bsn/1500";
//$link = "http://alexander.pro.bkn.ru/yf/export/bsn/0c3b5b60c3f411e4957dd43d7ef8ee66/objects.xml";
//$link = "http://nev-al.ru/nb/xml/neval_eip.xml";
//$link = "http://odsxml.advecs.com/Bsn.xml";
$link = "http://novostroykaspb.ru/xml/yrl.xml";
//$link = "http://www.mkparser.ru/download.php?file=eipBsn.xml";
//$link = "http://odsxml.advecs.com/Bsn.xml";
//$link = "http://domplusoffice.pro.bkn.ru/yf/export/yandex/a833eff1f3eb11e4957dd43d7ef8ee66/yandex.xml";
//$link = "http://kn.domplusoffice.ru/upload/abc/yandex.xml";
//$link = "http://alexander.pro.bkn.ru/yf/export/bsn/0c3b5b60c3f411e4957dd43d7ef8ee66/objects.xml";
//$link = "td-nevsky.ru/yAndex_123.xml";
$link = "http://rfn.pro.bkn.ru/yf/export/EIP/067af953f8b311e4957dd43d7ef8ee66/eip.xml";

//скачиваем файл
if(!empty($link)) $log['download'][] = downloadXmlFile("check","check",$link,42379,false);

$rent_titles = array(1=>'аренда', 2=>'продажа'); //типы сделок

//массив пар external_id - bsn_url для Александр-недвижимость
$ids_conformity = array();

//папка с xml файлами 
$dir = ROOT_PATH."/cron/robot/files/check_xml/";
//флаг однократного обновления
$update_flag = true;
$dh = opendir($dir);
$mail_text = '';  // текст письма
while($filename = readdir($dh)){
    //берем только свой файл
    if($filename!='.' && $filename!='..'){
        exec("chmod 777 ".$dir.$filename);
        
        $mail_text .= 'Файл:'.$dir.$filename.'<br />';  // текст письма
        $errors_log = array();  // ошибки
        $xml_values = array(); 
        //читаем в строку нужный файл
        $contents = file_get_contents($dir.$filename);
        $xml_str=xml2array($contents);
        
        //определяем тип файла:
        switch(true){
            case preg_match('/eip/',substr($contents,0,150)):
                $file_type = "eip";
                break;
            case preg_match('/realty-feed/',substr($contents,0,150)):
                $file_type = "yandex";
                break;
            case preg_match('/root/',substr($contents,0,150)):
                $file_type = "bn";
                break;
        }
        
        $counter = array('live'=>0,'build'=>0,'commercial'=>0,'country'=>0);
        $external_ids = array();
        switch($file_type){
            case 'eip':
                if($xml_str===FALSE || empty($xml_str['eip']['rec'][0]))  echo 'Файл EIPXML '.$dir.$filename.' не может быть обработан, т.к. имеет невалидные теги';
                else{
					$he =array();
                    foreach ($xml_str['eip']['rec'] as $object) $xml_values[] =  $object;
                    foreach($xml_values as $key=>$item){
                        if($item['what']=='гар') $item['what'] = 'скл';
                        switch(true){
                            case $item['what'] == 'ксд': 
								++$counter['build'];
								if(!empty($item['build_complex_title'])) $he[] = $item['build_complex_title'];
							break;
                            case in_array($item['what'],array('кв','ком','ктж')): ++$counter['live'];break;
                            case in_array($item['what'],array('дом','зу')): ++$counter['country'];break;
                            case in_array($item['what'],array('кзу','кн','ндв','нжф','обс','осз','офс','скл')): ++$counter['commercial'];break;
                        }
                    }
                    $result_string = "Файл корректен, EIPXML, ".count($xml_str['eip']['rec'])." объектов, в том числе:\r\n";
                    $result_string .= " -стройка: ".$counter['build']."\r\n";
                    $result_string .= " -жилая: ".$counter['live']."\r\n";
                    $result_string .= " -коммерческая: ".$counter['commercial']."\r\n";
                    $result_string .= " -загородная: ".$counter['country']."\r\n";
                    if(!empty($he)){
						$he = array_unique($he);
						$result_string .= "ЖК: ".implode(', ',$he)."\r\n";
						$result_string .=" Всего ЖК: ".count($he)."\r\n";
					}
					else $result_string .= " ЖК нету ";
                    echo $result_string;
                } 
                break;
            case 'bn':
                if($xml_str===FALSE || (empty($xml_str['root']['objects']['object']) && empty($xml_str['objects']['object']))) echo 'Файл  BNXML '.$dir.$filename.' не может быть обработан, т.к. имеет невалидные теги'; 
                else{
                    //подсчитываем количество объектов по типам:
                    if (empty($xml_str['root']['objects']['object'])) foreach ($xml_str['objects']['object'] as $object) $xml_values[] =  $object;
                    else foreach ($xml_str['root']['objects']['object'] as $object) $xml_values[] =  $object;
                    $external_ids = array();
					$he =array();
                    foreach($xml_values as $key=>$item){
                        $external_ids[] = $item['external_id'];
                        switch(true){
                            case $item['type_id'] == '14':
								++$counter['build'];
								if(!empty($item['build_complex_title'])) $he[] = $item['build_complex_title'];
								break;
                            case in_array($item['type_id'],array(16,17)): ++$counter['live'];break;
                            case in_array($item['type_id'],array(9,11,12,26,28)): ++$counter['country'];break;
                            case in_array($item['type_id'],array(2,3,4,5,6,7,34)): ++$counter['commercial'];break;
                        }
                    }
					
                    $result_string = "Файл корректен, BNXML, ".count($xml_str['root']['objects']['object'])." объектов, в том числе:\r\n";
                    $result_string .= " -стройка: ".$counter['build']."\r\n";
                    $result_string .= " -жилая: ".$counter['live']."\r\n";
                    $result_string .= " -коммерческая: ".$counter['commercial']."\r\n";
                    $result_string .= " -загородная: ".$counter['country']."\r\n";
					if(!empty($he)){
						$he = array_unique($he);
						$result_string .= "ЖК: ".implode(', ',$he)."\r\n";
						$result_string .=" Всего ЖК: ".count($he)."\r\n";
					}
					if(!empty($external_ids)) $result_string .= " различных id ".count(array_unique($external_ids))."\r\n";
                    echo $result_string;
                } 
                break;
            case 'yandex':
                if($xml_str===FALSE || empty($xml_str['realty-feed']['offer'][0]))  echo 'Файл YRXML '.$dir.$filename.' не может быть обработан, т.к. имеет невалидные теги'; 
                else{
					$he =array();
					preg_match_all('/offer internal-id="\d*"/',$contents,$internal_ids);
					$internal_ids = array_unique($internal_ids[0]);
					foreach($xml_str['realty-feed']['offer'] as $key=>$item){
						if(!empty($item['new-flat'])){
							++$counter['build'];
							if(!empty($item['build_complex_title'])) $he[] = $item['build_complex_title'];
						}
					}
					echo "Файл корректен, YRXML, ".count($xml_str['realty-feed']['offer'])." объектов, уникальных ".count($internal_ids);
					echo "Стройка: ".$counter['build'].",\r\n различных ЖК: ";
					if(!empty($he)){
						$he = array_unique($he);
						$result_string = " ".implode(', ',$he)."\r\n";
						$result_string .=" Всего ЖК: ".count($he)."\r\n";
						echo $result_string;
					}
					else echo "нету";
				}
                break;
            default:
                echo "Тип файла не определен";
        }
        //unlink($dir.$filename);
    }
}
?>