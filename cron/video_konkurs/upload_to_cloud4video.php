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

/**
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.host.php');       // Config (конфигурация сайта)
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/class.template.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
include('cron/robot/class.xml2array.php');  // конвертация xml в array
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$_folder = $root.Host::$root_path.'/'.Config::$values['video_folders']['konkurs_2015'].'/';
$dh = opendir($_folder);
$mail_text = '';  // текст письма
while($_targetFile = readdir($dh)){
    if($_targetFile!='.' && $_targetFile!='..')
    {
        $item = $db->fetch("SELECT * FROM ".$sys_tables['video_konkurs']." WHERE link = ?", $_targetFile);
        if(!empty($item)){
            //удаление видео (если по какой-то причине оно есть)
            $url = "http://ficus-n2.cloud4video.ru:8089/rest-api/file?login=pm%40bsn.ru&password=4d651eb627";
            $body = '<?xml version="1.0" encoding="utf-8"?>
            <root>
            <file id="bsn_id_'.$item['id'].'" />
            </root>';
            $result = curlThis($url, 'DELETE', false, true, $body); 
            print_r($result);
            //загрузка видео на cloud4video
            $url = "http://ficus-n2.cloud4video.ru:8089/rest-api/file?login=pm@bsn.ru&password=4d651eb627&gen_int_id=true";
            $body = '<?xml version="1.0" encoding="utf-8"?>
            <root>
            <file id="bsn_id_'.$item['id'].'" convert_formats="flash_h264_hq@360p-512;">http://st.bsn.ru/img/video/konkurs_2015/'.$_targetFile.'</file>
            </root>';
             
            $result = curlThis($url, 'POST', false, true, $body); 
            $xml_str = xml2array($result);
            print_r($xml_str);
            if($xml_str['response']['status'] == 'WITH_ERRORS') {
                $errors[] = 'Произошла непредвиденная ошибка. Попробуйте загрузить ваше видео позднее.';
                Response::SetArray('errors', $errors);
            } else {
                //ждем пока не загрузится файл
                $url = "http://ficus-n2.cloud4video.ru:8089/rest-api/infos/externalstatuslist?login=pm@bsn.ru&password=4d651eb627&externalIds=bsn_id_".$item['id'];
                for($i=0; $i<=1000; $i++){
                    $status = curlThis($url, 'GET'); 
                    $xml_str = xml2array($status);
                    if(!empty($xml_str['response']['files']['file']['status']) && $xml_str['response']['files']['file']['status'] == 'done') break;
                    sleep(1);
                }

                //получение свойств файла из cloud4video
                $url = "http://ficus-n2.cloud4video.ru:8089/rest-api/file/bsn_id_".$item['id']."?login=pm@bsn.ru&password=4d651eb627";
                $info = curlThis($url, 'GET'); 
                $xml_str = xml2array($info);
                print_r($xml_str);
                $external_link = $xml_str['response']['video']['formats']['format'][1]['types']['type']['encoded_url'];
                if(!empty($external_link)) {
                    $res = $db->query("UPDATE ".$sys_tables['video_konkurs']." SET external_link = ? WHERE id = ?",
                                   $external_link, $item['id']
                    );  
                    echo $db->last_query;
                    if($res){
                        if(file_exists($_folder.$_targetFile)) unlink($_folder.$_targetFile);      
                        Response::SetBoolean('ok', true);
                        
                        //письмо менеджеру
                        require_once('includes/class.email.php');
                        $mailer = new EMailer('mail');
                        Response::SetArray('parameters', $item);
                        Response::SetString('id', $item['id']);
                        $eml_tpl = new Template('/modules/video_konkurs/templates/mail.manager.html', $this_page->module_path);
                        $html = $eml_tpl->Processing();
                        $html = iconv('UTF-8', $mailer->CharSet, $html);
                        $mailer->ClearAddresses();
                        $mailer->AddAddress('pm@bsn.ru');
                        $mailer->Subject = iconv('UTF-8', $mailer->CharSet,'Новое видео в разделе Видео конкурс ЖК');
                        $mailer->Body = $html;
                        $mailer->IsHTML(true);
                        $mailer->From = 'no-reply@bsn.ru';
                        $mailer->FromName = 'bsn.ru';
                        $mailer->Send();
                    }
                } else {
                    Response::SetBoolean('ok', true);
                    $errors[] = 'Ваше видео обрабатывается. Появится в течении ближайшего часа.';
                    Response::SetArray('errors', $errors);   
                }
            }
        }
    }
}

?>