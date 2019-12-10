#!/usr/bin/php
<?php

// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
define('TEST_MODE', !empty($_SERVER['SCRIPT_FILENAME']) && preg_match('/test\.bsn\.ru/sui', $_SERVER['SCRIPT_FILENAME']) ? true : false);

$root = TEST_MODE ? realpath( '/home/bsn/sites/test.bsn.ru/public_html/trunk/' ) : ( DEBUG_MODE ? realpath( "../.." ) : realpath('/home/bsn/sites/bsn.ru/public_html/' ) ) ;
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  (крона
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
setlocale(LC_ALL, 'rus');

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/robot/xml_parser_error.log';
$test_performance = ROOT_PATH.'/cron/robot/xml_parser_performance.log';
file_put_contents($error_log,'');
file_put_contents($test_performance,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/functions.php');          // функции  (модуля
Request::Init();                                                                                    
Cookie::Init(); 
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = !TEST_MODE ? new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']) : new mysqli_db(Config::$values['mysql_remote']['host'], Config::$values['mysql_remote']['user'], Config::$values['mysql_remote']['pass']);
$db->query("set names ".Config::$values['mysql_remote']['charset']);
$db->query("set lc_time_names = 'ru_RU'");
require_once('includes/class.host.php');
require_once('includes/class.email.php');
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;     // Photos (работа с графикой)
require_once('includes/class.moderation.php'); // Moderation (процедура модерации)
require_once('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
//перечень типов для Cian
$cian_types = array('flats_rent','flats_for_sale','commerce','suburbian');

//выгрузка агентства по времени
$where = " ( `can_download` = 1 OR ( xml_time > DATE_SUB( NOW( ) , INTERVAL 3 MINUTE ) AND xml_time < DATE_ADD( NOW( ) , INTERVAL 3 MINUTE ) ) )  AND ".$sys_tables['users'].".agency_admin = 1 AND xml_status = 1 AND xml_time!='00:00:00'";
//доп.загрузка с переданным параметром ID админа агентства
$argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;
$sent_report = ( empty($argc) ? 1 : 2 );
//локально

if(DEBUG_MODE) $where = $sys_tables['users'].".id_agency = 5145 ";
if(!empty($argc)) $where = $sys_tables['users'].".id_agency = ".$argc." AND ".$sys_tables['users'].".agency_admin = 1";

$agency = $db->fetch("         SELECT          
                                      ".$sys_tables['users'].".id AS id_user,
                                      ".$sys_tables['users'].".email AS user_email,
                                      ".$sys_tables['users'].".xml_notification,
                                      CONCAT(".$sys_tables['users'].".name, ' ',  ".$sys_tables['users'].".lastname) AS user_name,
                                      ".$sys_tables['managers'].".name as manager_name,
                                      ".$sys_tables['managers'].".email as manager_email,
                                      ".$sys_tables['agencies'].".*,
                                      TRIM(xml_link) AS xml_link,
                                      xml_status,
                                      xml_alias
                               FROM ".$sys_tables['agencies']."
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency            
                               LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                               WHERE ".$where 
);
echo $db->last_query;       

$total = $total_added = $total_errors = 0;
$success = true;
if(empty($agency)) {
	echo "empty agency";
    die();
}

if(!empty($success)){
    $id_user = $agency['id_user'];
    /*
    if( !class_exists( 'Photos' ) ) require_once( 'includes/class.photos.php' ) ;
    //удаление фотографий агентства
    $tables = array( 'live', 'build', 'commercial', 'country' );
    foreach( $tables as $table ){
        $list = $db->fetchall( " SELECT * FROM " . $sys_tables[ $table ] . " WHERE id_user = ?", false, $id_user );
        foreach( $list as $k => $item ) Photos::DeleteAll( $table, $item['id'] );
    }
    */
    //обновление флага выгрузки по времени
    $db->query("UPDATE ".$sys_tables['agencies']." SET can_download = 2 WHERE id = ?",$agency['id']);
    //обнуление всех старых процессов
    $db->fetch("UPDATE ".$sys_tables['processes']." SET status = ? WHERE id_agency = ? AND type = ? AND status = ?", 2, $agency['id'], 2, 1);
    //запуск нового процесса
    $process_data = $db->prepareNewRecord( $sys_tables['processes'] );
    $process_data['id_agency'] = $agency['id'];
    $process_data['type'] = 2;
    $process_data['status'] = 1;
    $process_data['sent_report'] = $sent_report;
    $process_data['last_action'] = 'NOW() - INTERVAL 1 HOUR';
    print_r( $process_data );
    $res = $db->insertFromArray( $sys_tables['processes'], $process_data );
    echo "\n\nLast Query: " . $db->last_query . "\n\n";
    echo 'ERROR: ' . $db->error;
    $process_id = $db->insert_id;
    //проверка пакета для загрузки
    if(empty($agency['id_tarif'])){
        $error_text = 'У агентства нет тарифа'; 
        $db->query("UPDATE ".$sys_tables['processes']." SET full_log = CONCAT (log,'\n\nК сожалению у вас не выбран тарифа.'), log='', status = 2 WHERE id = ?", $process_id);
        $success = false;
    }  
    if(!empty($success)){
        //проеверяем ссылку
        if(!DEBUG_MODE){
            
            $link_response = get_http_response_code($agency['xml_link']);
            if($link_response != 200){
                $agency['xml_link'] = curlThis($agency['xml_link'],false,false,false,false,true);
                if(empty($agency['xml_link'])){
                    $db->query("UPDATE ".$sys_tables['processes']." SET full_log = CONCAT (log,'Файл недоступен'), log = '', status = 2 WHERE id = ?", $process_id);
                    $success = false;
                    file_unavailiable_notify($agency);
                }
            }
        }    
        if(DEBUG_MODE && file_exists(ROOT_PATH."/".Config::Get('xml_file_folders/downloads')."/54913_fyvfyvaaaafyvfyv.xml")) $filename = ROOT_PATH."/".Config::Get('xml_file_folders/downloads')."/54913_fyssdvfyvfyvfyv.xml";
        else $filename = downloadXmlFile(
            "bn",
            $agency['title'],
            $agency['xml_link'],
            $id_user,
            false,
            Config::Get('xml_file_folders/downloads'), 
            true,
            $agency['id'] == 1807 ? false : true
        )[0];
        if(empty($filename) || !file_exists($filename)) {
            
            $db->query("UPDATE ".$sys_tables['processes']." SET full_log = CONCAT (log,'Файл недоступен'), log = '', status = 2 WHERE id = ?", $process_id);
            $success = false;
            //сразу отправляем письма отв. менеджеру, в компанию и на web@bsn.ru
            file_unavailiable_notify($agency);
            
        }
        if(!empty($success)){
            //успешное скачивание
            $db->query("UPDATE ".$sys_tables['processes']." SET log = CONCAT (log,'Загрузка файла: ОК','\n\n','Анализ файла: ') WHERE id = ?", $process_id);

            //счетчики объектов
            $counter_analyse = $counter = array(
                             'live_sell'=>0,            'live_rent'=>0,             'commercial_sell'=>0,            'commercial_rent'=>0,          'build'=>0,            'country_sell'=>0,            'country_rent'=>0, 
                             'live_sell_promo'=>0,      'live_rent_promo'=>0,       'commercial_sell_promo'=>0,      'commercial_rent_promo'=>0,    'build_sell_promo'=>0,      'country_sell_promo'=>0,      'country_rent_promo'=>0, 
                             'live_sell_premium'=>0,    'live_rent_premium'=>0,     'commercial_sell_premium'=>0,    'commercial_rent_premium'=>0,  'build_sell_premium'=>0,    'country_sell_premium'=>0,    'country_rent_premium'=>0, 
                             'live_sell_vip'=>0,        'live_rent_vip'=>0,         'commercial_sell_vip'=>0,       'commercial_rent_vip'=>0,       'build_sell_vip'=>0,      'country_sell_vip'=>0,      'country_rent_vip'=>0,
                             'total'=>0
            );

             //парсим файл
            $contents = file_get_contents($filename);
            $xml_str = xml2array($contents);
            
            //unset($filename) ;
            switch(true){
                case !empty($xml_str['root']['objects']['object']) && !empty($xml_str['root']['objects']['object'][0]['status']):
                    $file_type = 'EMLS';
                    
                    $values_array = (!empty($xml_str['root']['objects']['object']['external_id']) ? array($xml_str['root']['objects']['object']) : $xml_str['root']['objects']['object']);
                    foreach($values_array as $key=>$item) if(empty($item['building']) || $item['status'] != 'в продаже') unset($values_array[$key]);
                    $robot = new EMLSXmlRobot($id_user); 
                    $info_source = 11;
                    break;
                case !empty($xml_str['root']['objects']['object']):
                    $file_type = 'BN';
                    $values_array = (!empty($xml_str['root']['objects']['object']['external_id']) ? array($xml_str['root']['objects']['object']) : $xml_str['root']['objects']['object']);
                    $robot = new BNXmlRobot($id_user); 
                    $info_source = 2;
                    break;
                case !empty($xml_str['bn-feed']['bn-object']):
                    $file_type = 'BN_NEW';
                    $values_array = $xml_str['bn-feed']['bn-object'];
                    $robot = new BNNEWXmlRobot($id_user);
                    $info_source = 2;
                    break;
                case !empty($xml_str['eip']['rec'][0]):
                    $file_type = 'EIP';
                    $values_array = $xml_str['eip']['rec'];
                    $robot = new EIPXmlRobot($id_user); 
                    $info_source = 3;
                    break;
                case !empty($xml_str['realty-feed']['offer'][0]) || !empty($xml_str['realty-feed']['offer']['type']):
                    $file_type = 'Yandex';
                    $values_array = (!empty($xml_str['realty-feed']['offer']['type']) ? array($xml_str['realty-feed']['offer']) : $xml_str['realty-feed']['offer']);
                    $robot = new YandexRXmlRobot($id_user); 
                    $info_source = 8;
                    //прочитали все internal-id, являющиеся атрибутами <offer> 
                    preg_match_all("#internal-id\=[\"|\']([a-zA-Z0-9\-\_]{1,})[\"|\']#sui", $contents, $internal_ids);
                    $internal_ids = $internal_ids[1];
                    foreach($internal_ids as $key=>$value){
                        $internal_ids[$key] = $value;
                    }
                    
                    break;
                case !empty($xml_str['ADS']['OBJECTS']):
                    $file_type = 'Gdeetotdom';
                    $values_array = $xml_str['ADS']['OBJECTS'];
                    $robot = new GdeetotXmlRobot($id_user); 
                    $info_source = 7;
                    break;
                case !empty($xml_str['Ads']['Ad'][0]):
                    $file_type = 'Avito';
                    $values_array = $xml_str['Ads']['Ad'];
                    $robot = new AvitoRXmlRobot($id_user); 
                    $info_source = 9;
                    break;
                case in_array( implode('', array_keys( $xml_str )), $cian_types ):
                    $file_type = 'Cian';
                    //заполянем тип для циана - переносим значение
                    $cian_type_infile = implode('',array_keys($xml_str));
                    $values_array = $xml_str[$cian_type_infile]['offer'];
                    foreach($values_array as $key=>$value){
                        $values_array[$key]['type'] = $cian_type_infile;
                    }
                    $robot = new CianXmlRobot($id_user);
                    $info_source = 10;
                    break;
                case !empty( array_keys($xml_str)[0] ) && !empty( array_keys($xml_str[array_keys($xml_str)[0]]) ) && array_keys($xml_str[array_keys($xml_str)[0]])[1] == 'object' :
                    $file_type = 'Cian_new';
                    $values_array = $xml_str[array_keys($xml_str)[0]]['object'];
                    $robot = new CianNewXmlRobot($id_user); 
                    $info_source = 13;
                    break;
                default:
                    break;
            }
            if(empty($file_type)) {
                $error_text = 'файл неизвестного формата'; 
                $db->query("UPDATE ".$sys_tables['processes']." SET status = ?, full_log = CONCAT (log,'".$error_text."'), log='' WHERE id = ?", 2, $process_id);
                $success = false;
            }
             if(!empty($success)){
                 unset($contents);
                 unset($xml_str);
                //обновляем инфу лога
                $db->query("UPDATE ".$sys_tables['processes']." SET log = CONCAT (log,'".$file_type." XML') WHERE id = ?", $process_id);
                //формирование значений полей
                $field_values = array();
                //список по типам недвижимости + сделки
                foreach($values_array as $key=>$values){
                    if(count($values) > 10){
                        //приведение всех ключей в нижний регистр
                        foreach($values as $k=>$val) {
                            if(!is_array($val))  $values[strtolower($k)] = htmlspecialchars(strip_tags($val));
                            else {
                                foreach($val as $kv=>$ki){
                                    if(!empty($ki)) {
                                        if(!is_array($ki))  $values[$k.'_attr'][$kv] = htmlspecialchars(strip_tags($ki));
                                        else foreach($ki as $kkv=>$kki) if(!empty($kki) && !is_array($kki)) $values[$k.'_attr'][$kkv] = htmlspecialchars(strip_tags($kki));
                                    }
                                }
                            }
                        }       
                        if(!empty($internal_ids[$key])) $values['internal-id'] = $internal_ids[$key];  
                        $res = $db->query("INSERT INTO ".$sys_tables['xml_parse']." SET id_agency = ?, file_type = ?, `xml_values` = ?, id_process = ?, hash = ?", $agency['id'], $file_type, json_encode($values), $process_id, sha1(http_build_query($values)));
                        if(!empty($values) && !empty($res)){
                            $fields = $robot->getConvertedFields($values, false, false, true);
                            if(!empty($fields['rent'])) ++$counter_analyse[$robot->estate_type.($robot->estate_type!='build'?($fields['rent']==2?'_sell':'_rent'):"")];
                        }
                    }
                }
                unset($values_array);
                unset($values);
                $text_counters = "
                    - жилая (продажа): ".$counter_analyse['live_sell']." ".($agency['id_tarif'] == 7 && $agency['live_sell_objects'] == 0 ? "" : " (лимит: ".$agency['live_sell_objects'].")")."
                    - жилая (аренда): ".$counter_analyse['live_rent']." ".($agency['live_rent_objects'] >= 9999 ? "" : " (лимит: ".$agency['live_rent_objects'].")")."
                    - стройка: ".$counter_analyse['build']." ".($agency['id_tarif'] == 7 && $agency['build_objects'] == 0 ? "" : " (лимит: ".$agency['build_objects'].")")."
                    - коммерческая (продажа): ".$counter_analyse['commercial_sell']." ".($agency['id_tarif'] == 7 && $agency['commercial_sell_objects'] == 0 ? "" : " (лимит: ".$agency['commercial_sell_objects'].")")."
                    - коммерческая (аренда): ".$counter_analyse['commercial_rent']." ".($agency['id_tarif'] == 7 && $agency['commercial_rent_objects'] == 0 ? "" : " (лимит: ".$agency['commercial_rent_objects'].")")."
                    - загородная (продажа): ".$counter_analyse['country_sell']." ".($agency['id_tarif'] == 7 && $agency['country_sell_objects'] == 0 ? "" : " (лимит: ".$agency['country_sell_objects'].")")."
                    - загородная (аренда): ".$counter_analyse['country_rent']." ".($agency['id_tarif'] == 7 && $agency['country_rent_objects'] == 0 ? "" : " (лимит: ".$agency['country_rent_objects'].")")."";        
                               
                //общее кол-во объектов в файле
                $total_amount = array_sum($counter_analyse);
                $db->query("UPDATE ".$sys_tables['processes']." SET log = CONCAT (log,'\n','".$text_counters."'), total_amount = ? WHERE id = ?", $total_amount, $process_id);

                // постановка в архив всех объектов этой компании (кроме объектов от недвижимости города)
                $db->query("UPDATE ".$sys_tables['build']." SET `published` = '2', `status` = 2, status_date_end = '0000-00-00 00:00:00', `date_change` = NOW() WHERE id_user = '".$id_user."' AND info_source > 1 AND `published` = 1");
                $db->query("UPDATE ".$sys_tables['live']." SET `published` = '2', `status` = 2, status_date_end = '0000-00-00 00:00:00', `date_change` = NOW() WHERE id_user = '".$id_user."' AND info_source > 1 AND `published` = 1");
                $db->query("UPDATE ".$sys_tables['commercial']." SET `published` = '2', `status` = 2, status_date_end = '0000-00-00 00:00:00', `date_change` = NOW() WHERE id_user = '".$id_user."' AND info_source > 1 AND `published` = 1");
                $db->query("UPDATE ".$sys_tables['country']." SET `published` = '2', `status` = 2, status_date_end = '0000-00-00 00:00:00', `date_change` = NOW() WHERE id_user = '".$id_user."' AND info_source > 1 AND `published` = 1");
             }
        }
    }
}
//удаление файла
if(!empty($filename) && file_exists($filename) && !DEBUG_MODE) unlink($filename);


function file_unavailiable_notify($agency){
    global $db;
    global $sent_report;
    $admin_mailer = new EMailer('mail');
    $mail_text = "Обработка объектов агентства ".$agency['title']."<br /><br />";
    $mail_text = 'Файл агентства #'.$agency['id'].' "'.$agency['title'].'" по ссылке '.$agency['xml_link'].' недоступен для скачивания<br />';
    $html = iconv('UTF-8', $admin_mailer->CharSet, $mail_text);
    // параметры письма
    $admin_mailer->Subject = iconv('UTF-8', $admin_mailer->CharSet, !empty($file_type) ? 'Обработка формата '.$file_type.' XML. '.date('Y-m-d H:i:s') : date('Y-m-d H:i:s'));
    $admin_mailer->Body = nl2br($html);
    $admin_mailer->AltBody = nl2br($html);
    $admin_mailer->IsHTML(true);
    $admin_mailer->AddAddress('scald@bsn.ru');
    $admin_mailer->AddAddress('web@bsn.ru');
    $admin_mailer->From = 'bsnxml@bsn.ru';
    $admin_mailer->FromName = iconv('UTF-8', $admin_mailer->CharSet,'Парсинг '.(!empty($file_type) ? $file_type : '').' XML файла');
    // попытка отправить
    $admin_mailer->Send();        


    if($sent_report == 1){
        $mailer = new EMailer('mail');
        Response::SetArray('agency',$agency);
        Response::SetString('file_error_text','Ваш файл, расположенный по ссылке '.$agency['xml_link'].' , недоступен для скачивания<br />');
        $eml_tpl = new Template('parse.xml.notification.html', 'cron/robot/');
        // перевод письма в кодировку мейлера
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        // параметры письма
        $mailer->Body = $html;
        $mailer->IsHTML(true);
        //отчет
        $report = $db->fetch("SELECT 
                                        *,
                                        IF(YEAR(`datetime_start`) < Year(CURDATE()),DATE_FORMAT(`datetime_start`,'%e %M %Y'),DATE_FORMAT(`datetime_start`,'%e %M')) as normal_date,  
                                        DATE_FORMAT(`datetime_start`,'%k:%i') as normal_date_start,
                                        DATE_FORMAT(`datetime_end`,'%k:%i') as normal_date_end
                                  FROM ".$sys_tables['processes']." 
                                  WHERE id = ?", $process_id 
        );

        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Отчет о загрузке объектов от ".$report['normal_date']." ".$report['normal_date_start']." > ".$report['normal_date_end']);

        if(!empty($agency['user_email']) && Validate::isEmail($agency['user_email']) ) $mailer->AddAddress($agency['user_email']);     //отправка письма ответственному менеджеру
        //если у агентства не установлен тариф и все в порядке с адресом, и стоит галочка оповещений, отправляем письмо
        if(!empty($agency['email_service']) && $agency['xml_notification'] == 1 && Validate::isEmail($agency['email_service'])) $mailer->AddAddress($agency['email_service']);     //отправка письма агентству

        $mailer->AddAddress('hitty@bsn.ru');
        $mailer->AddAddress('web@bsn.ru');
        $mailer->From = 'xml_parser@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'XML парсер BSN.ru');
        // попытка отправить
        $mailer->Send();        

        //отправка письма менеджеру
        if(!empty($agency['manager_email'])){
            $manager_mailer = new EMailer('mail');
            Response::SetArray('agency', $agency);
            Response::SetString('file_error_text','Файл агентства #'.$agency['id'].' "'.$agency['title'].'", расположенный по ссылке '.$agency['xml_link'].' , недоступен для скачивания<br />');
            $eml_tpl = new Template('parse.xml.manager.notification.html', 'cron/robot/');
            // перевод письма в кодировку мейлера   
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $manager_mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $manager_mailer->Body = $html;
            $manager_mailer->IsHTML(true);

            $manager_mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Отчет о загрузке объектов агентства ".$process['title']." ID ".$process['id_user']." от ".$report['normal_date']." ".$report['normal_date_start']." > ".$report['normal_date_end']);

            $manager_mailer->AddAddress($agency['manager_email']);     //отправка письма ответственному менеджеру
            $mailer->AddAddress('web@bsn.ru');
            $manager_mailer->From = 'xml_parser@bsn.ru';
            $manager_mailer->FromName = iconv('UTF-8', $manager_mailer->CharSet,'XML парсер BSN.ru');
            // попытка отправить
            $manager_mailer->Send();      
        }
    }
    
    die();
}
?>
