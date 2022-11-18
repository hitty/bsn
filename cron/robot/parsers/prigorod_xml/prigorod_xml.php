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

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/robot/parsers/prigorod_xml/error.log';
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

//выгрузка Недвижимости города 
$log['download'][] = downloadXmlFile("prigorod","prigorodsu","http://media.industry-soft.ru/Partners/BSN/Country.xml",false,false);

//папка с xml файлами 
$dir = ROOT_PATH."/cron/robot/files/prigorod_xml/";
$rent_titles = array(1=>'аренда', 2=>'продажа'); //типы сделок
$dh = opendir($dir);
$mail_text = '';
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..')
    {
        
        $agencies = array();
        $errors_log = array('no_agency'=>array(),
                            'no_name'=>array(),
                            'no_agency_on_bsn'=>array(),
                            'agencies_list'=>array()
        );
        //читаем в строку нужный файл
        $contents = file_get_contents($dir.$filename);
        $xml_str=xml2array($contents);
        
        //по строке создаем объект simplexml
        if($xml_str===FALSE) {$errors_log['fatal'] = 'Файл '.$dir.$filename.' не может быть обработан, т.к. имеет невалидные теги'; break;}
        
        foreach ($xml_str['root']['objects']['object'] as $object) $xml_values[] =  $object;       
        //Актуальные агентства по загородке    
        $country_agencies = $db->fetchall("
            SELECT 
                ".$sys_tables['agencies'].".title as agency_title,              
                ".$sys_tables['agencies'].".id as agency_id       
            FROM  ".$sys_tables['agencies']."       
            RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id       
            RIGHT JOIN ".$sys_tables['country']." ON ".$sys_tables['country'].".id_user = ".$sys_tables['users'].".id       
            WHERE ( ".$sys_tables['country'].".published = 1 AND ".$sys_tables['country'].".info_source != 4 AND ".$sys_tables['agencies'].".id > 1 ) OR ".$sys_tables['agencies'].".title LIKE 'Александр Н%'
            GROUP BY ".$sys_tables['agencies'].".title     
            ORDER BY ".$sys_tables['agencies'].".title      
        ");
        $agency_ids = array();
        foreach($country_agencies as $k=>$val) $agency_ids[] = $val['agency_id'];
        //обработка полученных значений
        foreach($xml_values as $key=>$values){
            //общий счетчик
            ++$counter['total'];
            $id_user=$id_agent=0; $stoplist = false;
            //определение id агентства
            $SourceId = !empty($values['FirmAgreementId']) && $values['FirmAgreementId']> 0 ? $values['FirmAgreementId'] : $values['SourceId'];
            if($SourceId > 0 || !empty($values['FirmName']))
            {
                if($SourceId > 0) $agency = $db->fetch("SELECT `bsn_id`, `bsn_title` FROM ".$sys_tables['ng_agencies']." WHERE `ng_id` = ?",$SourceId);
                if(empty($agency)) $agency = $db->fetch("SELECT `bsn_id`, `bsn_title` FROM ".$sys_tables['ng_agencies']." WHERE `ng_title` = ?",$values['FirmName']); 
                if(!empty($agency)) {
                    $user = $db->fetch("SELECT `id` FROM ".$sys_tables['users']." WHERE `id_agency` = ?", $agency['bsn_id']);
                    if(empty($user))  {
                        if(!in_array($values['FirmName'], $errors_log['no_agency_on_bsn'])) $errors_log['no_agency_on_bsn'][] = $values['FirmName']; 
                    } else {
                        if(!in_array($agency['bsn_title'], $errors_log['agencies_list'])) $errors_log['agencies_list'][] = $agency['bsn_title']; 
                    }
                } else {
                    if(!in_array($values['FirmName'], $errors_log['no_agency'])) $errors_log['no_agency'][] = $values['FirmName']; 
                }
            } else {
                if(!in_array($values['FirmName'], $errors_log['no_name'])) $errors_log['no_name'][] = $values['FirmName']; 
            }
            
        }
    }
} 
$mail_text = "";

if(!empty($errors_log['agencies_list']))  {
    $mail_text .= "<strong>Список выгружаемых агентств:</strong>";
    foreach($errors_log['agencies_list'] as $agency) $mail_text .= "<br />".$agency;
}    
if(!empty($errors_log['no_agency']))  {
    $mail_text .= "<br /><br /><strong>Ненайденные агентства в базе:</strong>";
    foreach($errors_log['no_agency'] as $agency) $mail_text .= "<br />".$agency;
}    
if(!empty($errors_log['no_agency_on_bsn']))  {
    $mail_text .= "<br /><br /><strong>Ненайденные агентства в базе БСН(нет записи в id_agency в таблице пользователей): </strong>";
    foreach($errors_log['no_agency_on_bsn'] as $agency) $mail_text .= "<br />".$agency;
}    
if(!empty($errors_log['no_name']))  {
    $mail_text .= "<br /><br /><strong>Пустые SourceID и FirmAgreementId в xml:</strong>";
    foreach($errors_log['no_name'] as $agency) $mail_text .= "<br />".$agency;
}        
file_put_contents(ROOT_PATH.'/cron/robot/parsers/prigorod_xml/firms.log',$mail_text)    ;
?>