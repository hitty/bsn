#!/usr/bin/php
<?php
error_reporting(0);
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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
$error_log = ROOT_PATH.'/cron/comagic/spam_error.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.email.php');        // для отправки писем
require_once('includes/functions.php');    // функции  из крона
require_once('includes/getid3/getid3.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;

//ID app = 824b4f6c10b14c7cb5a6d4a48c3d9d4b
//ya.bsnru@yandex.ru
//bsnruNO13parol
//token - from 21012016 763385506acf4709a837b5976b803fe8 - до 21 июля 2015 (180 дней)
//763385506acf4709a837b5976b803fe8&token_type=bearer&expires_in=15552000 - 
//пользователь: https://webmaster.yandex.ru/api/199073612

$token = "763385506acf4709a837b5976b803fe8";

//получаем id пользователя по токену
//$result = yandexWM_getstats('https://webmaster.yandex.ru/api/me',array('Authorization: OAuth '.$token));
//$user_id = str_replace('https://webmaster.yandex.ru/api/','',$result["redirect_url"]);
$user_id = 199073612;

//список сайтов в виде XML
//$result=yandexWM_getstats('https://webmaster.yandex.ru/api/'.$user_id.'/hosts',array('Authorization: OAuth '.$token));
//11706 - id BSN
$bsn_id = 11706;

$site_href="https://webmaster.yandex.ru/api/".$user_id."/hosts/".$bsn_id;

//https://yandex.ru/search/xml
//yxmlk = 03.199073612:63b3aeaf3fbee2a26d1e28f5856b04d4


//$limits = new SimpleXMLIterator($limits);


$limits = yandex_get_limits();
if(empty($limits)) $mail_text =  "Не удалось запросить лимиты";
else{
    $mail_text = "";
    $inserting = array();
    foreach($limits as $key=>$item){
        $time_start = new DateTime($item->attributes()['from']->__toString());
        $time_start = $time_start->format("H");
        if($time_start == date('H')) $limit = $item->__toString();
        $inserting[] = "(".$time_start.",".$item->__toString().")";
    }
    if(!empty($inserting)){
        $db->querys("TRUNCATE ".$sys_tables['yandex_xml_limits']);
        $db->querys("INSERT INTO ".$sys_tables['yandex_xml_limits']." (hour,query_limit) VALUES ".implode(',',$inserting));
    }
}


$url = "https://yandex.ru/search/xml?user=ya-bsnru&key=03.199073612:63b3aeaf3fbee2a26d1e28f5856b04d4&l10n=ru&sortby=tm.order%3Dascending&filter=strict&groupby=attr%3D%22%22.mode%3Dflat.groups-on-page%3D10.docs-in-group%3D1";

//число страниц которое будем проверять - чтобы не выйти за лимит Я.XML
$limit = (!empty($argc)?$argc:$limit);

//число страниц которое будем проверять - чтобы не выйти за лимит Я.XML
if(empty($limit)) $limit = 440;

//проверяем что не превышен общий лимит в 4400
$queries_sended = $db->fetch("SELECT COUNT(*) as amount FROM ".$sys_tables['pages_not_indexed_yandex']." WHERE DATEDIFF(NOW(),index_checked) = 0")['amount'];
$limit = min((4400 - $queries_sended),$limit);

//если лимит установлен, начинаем проверять
if(!empty($limit) && $limit > 0){
    //читаем страницы, которые нужно проверить на то что они в индексе (те которые выстояны 2 недели после захода на них робота). Лимит - чтобы не превысить лимит в Я.XML
    //с 02022016 снижено до 1 недели. вечером того же дня до 4 дней
    $list_to_check = $db->fetchall("SELECT id,url FROM ".$sys_tables['pages_not_indexed_yandex']." WHERE DATEDIFF(NOW(),date_out) > 4 AND (DATEDIFF(NOW(),index_checked) > 4 OR index_checked = '0000-00-00 00:00:00') LIMIT ".$limit);

    if(!empty($list_to_check)){
        $in_index = array();
        $not_in_index = array();

        foreach($list_to_check as $key=>$item){
            $xml = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><request></request>");
            $xml -> addChild('query', "site:https://www.bsn.ru/".$item['url']);
            $query_result = yandex_send_post($url,$xml->asXML());
            
            if(!empty($query_result->response->results)){
                $in_index[] = $item['url'];
                $db->querys("UPDATE ".$sys_tables['pages_not_indexed_yandex']." SET in_index = 1,index_checked = NOW() WHERE id = ".$item['id']);
            }else{
                //возвращаем в показы те, что не в индексе
                $db->querys("UPDATE ".$sys_tables['pages_not_indexed_yandex']." SET index_checked = NOW(),date_out = '0000-00-00 00:00:00', shown_today = 0 WHERE id = ".$item['id']);
                $not_in_index[] = $item['url'];
            } 
        }
        $mail_text = "Проверено: ".$limit."<br/>В индексе (".count($in_index)."): <br/>".implode('<br/>',$in_index)."<br/>Не в индексе: (".count($not_in_index)."): <br/>".implode('<br/>',$not_in_index);
    }else $mail_text = "Нет страниц подходящих для проверки.";
}else $mail_text = "Лимит не задан";

$mailer = new EMailer('mail');
$mail_text = iconv('UTF-8', $mailer->CharSet, $mail_text);
if(!empty($data['subject'])) $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Проверка индексирования стека Яндекса:");
$mailer->Body = $mail_text;
$mailer->AltBody = strip_tags($mail_text);
$mailer->IsHTML(true);
$mailer->AddAddress('hitty@bsn.ru');
$mailer->From = 'no-reply@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
// попытка отправить
$mailer->Send();

?>
<?php
function yandex_send_post($url,$body){
    $curl=curl_init();
    
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: application/xml"));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    curl_setopt($curl, CURLOPT_POST, true);
    
    $response = curl_exec($curl);
    $res = new SimpleXMLElement($response);
    return $res;
}
function yandex_get_limits(){
    $url_get_limits = "https://yandex.ru/search/xml?action=limits-info&user=ya-bsnru&key=03.199073612:63b3aeaf3fbee2a26d1e28f5856b04d4";
    
    $limits = yandex_send_post($url_get_limits,"");
    $limits = $limits->asXML();
    $limits = str_replace('time-interval','time_interval',$limits);
    $limits = new SimpleXMLElement($limits);
    $limits = $limits->response->limits->time_interval;
    return $limits;
}
?>