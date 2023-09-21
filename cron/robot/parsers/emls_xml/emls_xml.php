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

/**
* Обработка новых объектов
*/
// подключение классов ядра


require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/functions.php');          // функции  из модуля
Session::Init(false,false,false,true);
Request::Init();
Cookie::Init(); 
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
require_once('includes/class.photos.php');     // Photos (работа с графикой)
require_once('includes/class.moderation.php'); // Moderation (процедура модерации)
require_once('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array
require_once("includes/class.sendpulse.php");

//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$moderate_statuses = array(2=>'маленькая стоимость',3=>'большая стоимость',4=>'нет адреса'); //статусы модерации
$rent_titles = array(1=>'аренда', 2=>'продажа'); //типы сделок
$files = array('http://emls.ru/export/bsn_base.xml'=>2,'http://emls.ru/export/bsn_promo.xml'=>3, 'http://emls.ru/export/bsn_premium.xml'=>4);
          
foreach($files as $file=>$key){
    $link_response = get_http_response_code($file);
    if($link_response != 200){
        $error_text = 'Файл недоступен.';
        echo $error_text;
        $db->querys("UPDATE ".$sys_tables['processes']." SET full_log = CONCAT (log,'".$error_text."'), log = '', status = 2 WHERE id = ?", $process_id);
        $success = false;
        //сразу отправляем письма отв. менеджеру и на hitty@bsn.ru
        $admin_mailer = new EMailer('mail');
        $mail_text = 'Файл агентства #2898 "КРИС" по ссылке '.$file.' недоступен для скачивания';
        $html = iconv('UTF-8', $admin_mailer->CharSet, $mail_text);
        // параметры письма
        $admin_mailer->Subject = iconv('UTF-8', $admin_mailer->CharSet, "Файл агентства #".$agency['id']." недоступен ".$process['id_user']." ".date('Y-m-d H:i:s'));
        $admin_mailer->Body = nl2br($html);
        $admin_mailer->AltBody = nl2br($html);
        $admin_mailer->IsHTML(true);
        $admin_mailer->AddAddress('d.salova@bsn.ru');
        $admin_mailer->AddAddress('scald@bsn.ru');
        $admin_mailer->AddAddress('hitty@bsn.ru');
        $admin_mailer->From = 'bsnxml@bsn.ru';
        $admin_mailer->FromName = iconv('UTF-8', $admin_mailer->CharSet,'Парсинг '.(!empty($file_type) ? $file_type : '').' XML файла');
        // попытка отправить
        $admin_mailer->Send();
    }
}


$log['download'][] = downloadXmlFile("emls","emls",'http://emls.ru/export/bsn_base.xml',9915,false);
$log['download'][] = downloadXmlFile("emls","promo",'http://emls.ru/export/bsn_promo.xml',9915,false); 
$log['download'][] = downloadXmlFile("emls","premium",'http://emls.ru/export/bsn_premium.xml',9915,false);
//папка с xml файлами 
$dir = ROOT_PATH."/cron/robot/files/emls_xml/";

$dh = opendir($dir);
$mail_text = '';  // текст письма
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..')
    {
        echo "\n".$dir.$filename."\n";
        exec("chmod 777 ".$dir.$filename);
        $mail_text .= 'Файл:'.$dir.$filename.'<br />';  
        $errors_log = array('img' => array(), 'moderation' => array(), 'over_limit' => 0, 'fatal' => "");  // ошибки
        $counter = array('live_sell'=>0,             'commercial_sell'=>0,            'country_sell'=>0,           'build'=>0,
                         'live_rent'=>0,             'commercial_rent'=>0,            'country_rent'=>0,
                         'live_promo'=>0,       'commercial_promo'=>0,      'country_promo'=>0,     'build_promo'=>0,
                         'live_premium'=>0,     'commercial_premium'=>0,    'country_premium'=>0,   'build_premium'=>0,
                         'live_elite'=>0,       'commercial_elite'=>0,      'country_elite'=>0,     'build_elite'=>0,
                         'total'=>0
        );
        switch($filename){
            case '9915_emls.xml': $status=2; break;
            case '9915_promo.xml': $status=3; break;
            case '9915_premium.xml': $status=4; break;
       } 
        //Определение id_user по начальному имени файла
        $id_user = explode('_',$filename);
        $id_user = Convert::ToInt($id_user[0]); 
        if($id_user<1) $mail_text.="Ошибка авторизации";
        else
        {
            //информация об агентстве
            $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".*, 
                                         ".$sys_tables['managers'].".`email`, 
                                         ".$sys_tables['agencies'].".`email_service`, 
                                         ".$sys_tables['managers'].".`email`,
                                         ".$sys_tables['users'].".id_tarif AS user_tarif,
                                         IF(".$sys_tables['tarifs'].".title IS NOT NULL,".$sys_tables['tarifs'].".title,'') AS tarif_title
                                  FROM ".$sys_tables['agencies']."
                                  RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
                                  LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager 
                                  LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['users'].".id_tarif = ".$sys_tables['tarifs'].".id
                                  WHERE ".$sys_tables['users'].".`id` = ?",
                                  $id_user) ;            
            if(empty($agency)) $mail_text.="Ошибка авторизации"; //агентство не найдено
            //elseif(!empty($agency['id_tarif'])) $mail_text .= "Объекты агентства ".$agency['title']." не выгрузились, потому что для агентства установлен тариф ".$agency['tarif_title'];//есть тариф - нет выгрузки
            else {
                
                $xml_values = array();
                //рекламное агентство
                $advert_agency = $agency['activity']%pow(2,2)>=pow(2,1)?true:false;
                //текст письма
                $mail_text .= "Обработка объектов агентства ".$agency['title']."<br /><br />";
                // постановка в архив всех объектов этой компании (кроме объектов от недвижимости города)
                $db->querys("UPDATE ".$sys_tables['live']." SET `published` = '2', `date_change` = NOW() WHERE id_user = '".$id_user."' AND info_source != 4 AND `published` = 1 AND status=".$status);
                $db->querys("UPDATE ".$sys_tables['build']." SET `published` = '2', `date_change` = NOW() WHERE id_user = '".$id_user."' AND info_source != 4 AND `published` = 1 AND status=".$status);
                $db->querys("UPDATE ".$sys_tables['commercial']." SET `published` = '2', `date_change` = NOW() WHERE id_user = '".$id_user."' AND info_source != 4 AND `published` = 1 AND status=".$status);
                $db->querys("UPDATE ".$sys_tables['country']." SET `published` = '2', `date_change` = NOW() WHERE id_user = '".$id_user."' AND info_source != 4 AND `published` = 1 AND status=".$status);
                
                //читаем в строку нужный файл
                $contents = file_get_contents($dir.$filename);
                $xml_str=xml2array($contents);
                 
                //по строке создаем объект simplexml
                if($xml_str===FALSE) {$errors_log['fatal'] = 'Файл '.$dir.$filename.' не может быть обработан, т.к. имеет невалидные теги'; break;}
                
                if(!empty($xml_str['root']['objects']['object']['external_id'])) $xml_values = array($xml_str['root']['objects']['object']);
                else foreach ($xml_str['root']['objects']['object'] as $object) $xml_values[] =  $object;
                
                //обработка полученных значений
                foreach($xml_values as $key=>$values){
                    //приведение всех ключей в нижний регистр
                    foreach($values as $k=>$val) $values[strtolower($k)] = !is_array($val)?$val:array_unique($val);
                    //сумма всех итераций
                    //++$counter['total'];
                    if(strstr($values['price_str'],'тыс') && $values['price']<5100000 && $values['price_type_id']!=22) $values['price'] = $values['price']*1000;
                    $robot = new BNXmlRobot($id_user);
                    $fields = $robot->getConvertedFields($values,$agency);
                   
                    //получение статуса модерации объекта
                    $moderate = new Moderation($robot->estate_type,0);
                    $moderate_status = $moderate->getModerateStatus($fields);
                    $fields['hash'] = $moderate->makeHash();
                    //для непрошедших модерацию
                    if($moderate_status>1){
                        $fields['published'] = 3; //на модерации
                        $errors_log['moderation'][$values['external_id']] = array(($moderate_status!=4?$fields['cost'].', '.$rent_titles[$fields['rent']]:$fields['txt_addr']),$moderate_status);        
                    } else $fields['published'] = 1;
                    //массив с фото
                    $photos = array();
                    $fields['status']=$status;
                    $fields['sms_sum']=60;
                    //префикс для фото
                    $prefix = '';
                    echo ";;;".$status."'''".$fields['status'];
                   
                    $deal_type = $robot->estate_type == 'build' ? '' : ($fields['rent'] == 1 ? '_rent' : '_sell');
                    $check_limit = ($robot->estate_type.$deal_type == 'live_rent' && $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects']) || $agency['id_tarif']==1 || $agency['id_tarif']==7 || $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects']; 
					/*$check_limit = TRUE;*/
                    if(!empty($fields)) {
                        //сумма всех итераций
                        ++$counter['total'];                  
                        //счетчик сверх лимита
                        if(!$check_limit) {
                            if(empty($errors_log['over_limit'])) $errors_log['over_limit'] = 0;
                            $errors_log['over_limit']++;
                            $counter[$robot->estate_type.$deal_type.'_over_limit']++;
                        }
                    }
                    
                    if(!empty($fields) && $check_limit){ //отсечение лимитов
                    
                        //поиск ранее загруженного объекта в основной таблице
                        $check_object = $db->fetch("SELECT `id`, `id_main_photo` FROM ".$sys_tables[($robot->estate_type)]." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` != ?",
                                                $fields['external_id'], $id_user, 4
                        );
                        
                        if(!empty($check_object)) {
                            $fields['id'] = $check_object['id'];
                            //updat'им данные
                            $res = $db->updateFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                            
                            //определение списка фотографий, которых нет в БД
                            if(!empty($fields['images'])) list($photos['in'],$photos['out']) = $robot->getPhotoList($fields['images'], $check_object['id']);
                            //удаление фоток (из базы и с сервера), которые не вошли в xml
                            $photos_in = implode(',', $photos['in']);
                            $photo_list = $db->fetchall("SELECT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                         WHERE `id_parent` = ".$check_object['id']." 
                                                         ".(!empty($photos['in'])?" AND `external_img_src` NOT IN (".$photos_in.")":""));
                            if(!empty($photo_list)){
                                foreach($photo_list as $k => $val) Photos::Delete($robot->estate_type,$val['id']);
                                if(!empty($photos_in)) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".$photos_in.")");
                            }
                            $inserted_id = $check_object['id'];

                            //если объект на модерации
                            if($moderate_status>1){
                                //проверяем его наличие в таблице new
                                $check_object_new = $db->fetch("SELECT `id` FROM ".$sys_tables[($robot->estate_type).'_new']." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` != ?",
                                                        $fields['external_id'], $id_user, 4);
                                $fields['id_object'] = $fields['id'];
                                unset($fields['id']);
                                //если есть - update
                                if(!empty($check_object_new)) $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id_object');
                                //еси нет - вставка
                                else $res = $db->insertFromArray($sys_tables[($robot->estate_type)], $fields, 'id_object');
                            }                       
                        }
                        else 
                        {
                            //поиск ранее загруженного объекта в таблице _new
                            $check_object_new = $db->fetch("SELECT `id`, `id_main_photo` FROM ".$sys_tables[($robot->estate_type).'_new']." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` != ?",
                                                    $fields['external_id'], $id_user, 4
                            );
                            if(!empty($check_object_new)) {
                                $fields['id'] = $check_object_new['id'];
                                //updat'им данные
                                $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                                
                                //определение списка фотографий, которых нет в БД
                                if(!empty($fields['images'])) list($photos['in'],$photos['out']) = $robot->getPhotoList($fields['images'], $check_object_new['id'],'_new');
                                //удаление фоток (из базы и с сервера)
                                $photos_in = implode(',', $photos['in']);
                                $photo_list = $db->fetchall("SELECT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                             WHERE `id_parent_new` = ".$check_object_new['id']." 
                                                             ".(!empty($photos['in'])?"AND `external_img_src` NOT IN (".$photos_in.")":""));
                                
                                if(!empty($photo_list)){
                                    foreach($photo_list as $k => $val) Photos::Delete($robot->estate_type,$val['id'],"_new");
                                    if(!empty($photos_in)) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".$photos_in.")");
                                }
                                    
                                $inserted_id = $check_object_new['id'];
                                $prefix = '_new';
                                
                            } else {
                                $fields['date_in']=$fields['date_change']= date('Y-m-d H:i:s');
                                if($advert_agency && $moderate_status==1){ // для рекламных агентств прошедших модерацию - нет проверки на склейку
                                    $res = $db->insertFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                                } else {
                                    $prefix = '_new';
                                    $res = $db->insertFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                                }
                                if(!empty($fields['images'])) $photos['out'] =  $fields['images'];
                                $inserted_id = $db->insert_id;
                            }
                        }         
                        
                        ///считаем и записываем вес объекта (в таблицу estate_type или estate_type_new):
                        switch($robot->estate_type){
                            case 'live':$item_weight = new Estate(TYPE_ESTATE_LIVE);break;
                            case 'build':$item_weight = new Estate(TYPE_ESTATE_BUILD);break;
                            case 'country':$item_weight = new Estate(TYPE_ESTATE_COUNTRY);break;
                            case 'commercial':$item_weight = new Estate(TYPE_ESTATE_COMMERCIAL);break;
                        }
                        $item_weight = $item_weight->getItemWeight($inserted_id,$robot->estate_type);
                        $res_weight = $db->querys("UPDATE ".$sys_tables[$robot->estate_type.$prefix]." SET weight=? WHERE id=?",$item_weight,$inserted_id);
                        ///
                        
                            // если есть картинки - присоединяем
                        echo $inserted_id.":".$counter[$robot->estate_type.$deal_type].":".$robot->estate_type.$deal_type;
                        if(!empty($photos['out'])) print_r($photos['out']);
                        echo "\n"; 
                                           
                        $photos['out'] = array_slice($photos['out'], 0 , DEBUG_MODE ? 1 : 15);    
                        if(!empty($photos['out']) && $inserted_id>0) {
                            $external_img_sources = Photos::MultiDownload($photos['out'], ROOT_PATH.'/'.Config::$values['img_folders'][$robot->estate_type].'/');
                            //foreach($external_img_sources as $k=>$img) Photos::Add($robot->estate_type, $inserted_id, $prefix, $img['external_img_src'], $img['filename']);
                            foreach($external_img_sources as $k=>$img){
                                $photo_add_result = Photos::Add($robot->estate_type, $inserted_id, $prefix, $img['external_img_src'], $img['filename'], false, false, false, Config::Get('watermark_src'));
                                if(!is_array($photo_add_result)) $errors_log['img'][] = $img['external_img_src'];
                            }
                        }
                        //обновление главной фотографии объекта если она не прикреплена
                        if($inserted_id>0 && !empty($photos['in']) && !empty($check_object['id']) && $check_object['id_main_photo']==0){
                            $photo_id = $db->fetch("SELECT id FROM ".$sys_tables[($robot->estate_type)."_photos"]." WHERE id_parent = ?",$inserted_id);
                            if(!empty($photo_id)) $db->querys("UPDATE ".$sys_tables[($robot->estate_type)]." SET id_main_photo = ? WHERE id = ?",$photo_id['id'],$inserted_id);
                        }
                        
                        //модерация новых объектов
                        if($prefix=='_new') {
                            $moderate = new Moderation($robot->estate_type,$inserted_id);
                            $moderate->checkObject();
                        }
                        
                        //счетчик кол-ва вариантов
                        
                        if($moderate_status==1) $counter[$robot->estate_type.$deal_type]++;

                    } //end of if(empty($agency)) - ошибка авторизации
                } //end of: //обработка полученных значений foreach($xml_values as $key=>$values)
            }
        } // end of: if($id_user>1) - ошибка авторизации 
        //удаление файла
        unlink($dir.$filename);
        //отправка отчета о вариантах
        if(!empty($counter)){
            $total_added = $counter['live_rent'] + $counter['build'] + $counter['country_rent'] + $counter['commercial_rent'] + 
                           $counter['live_sell'] + $counter['country_sell'] + $counter['commercial_sell'];
            $mail_text .= "Обработано всего объектов:".$counter['total'];
            $mail_text .= "<br />Добавлено объектов: ".$total_added."<br />
            - жилая (продажа): ".$counter['live_sell']."<br />
            - жилая (аренда): ".$counter['live_rent']."<br />
            - стройка: ".$counter['build']."<br />
            - коммерческая (продажа): ".$counter['commercial_sell']."<br />
            - коммерческая (аренда): ".$counter['commercial_rent']."<br />
            - загородная (продажа): ".$counter['country_sell']."<br />
            - загородная (аренда): ".$counter['country_rent']."<br />";
        }
        //логирование ошибок
        if(!empty($errors_log)){
            $mail_text .= "<br /><br />При обработке файла возникли следующие ошибки:";
            if(!empty($errors_log['fatal'])) $mail_text .= "<br /><br />".$errors_log['fatal']; 
            if(!empty($errors_log['moderation']))  {
                $mail_text .= "<br /><br /><strong>Не прошли модерацию: ".count($errors_log['moderation'])."</strong>";
                foreach($errors_log['moderation'] as $k=>$moderation) 
                    if(!empty($moderate_statuses[$moderation[1]]) && !empty($moderation[0]))
                        $mail_text .= "<br />external_id: <strong>".$k.'</strong>, статус: <i>'.$moderate_statuses[$moderation[1]]."</i>, значение: ".$moderation[0];
            }    
            if(!empty($errors_log['img'])){ //ошибки загрузки фото
                $mail_text .= "<br /><br />Незагруженные фотографии объектов:";
                foreach($errors_log['img'] as $k=>$img) $mail_text .= "<br />".$img;
            }
        }
        $mail_text .= "<br /><br />";
    } // end of: $filename=='xml-file'
}
//если были ошибки выполнения скрипта
if(filesize($error_log)>10){
    $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
    $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
    $error_log_text .= '</font>';
} else $error_log_text = "";

if($mail_text!=''){
    $emails = array(
        array(
            'name' => '',
            'email'=> 'hitty@bsn.ru'
        )
    );
    
    if(!empty($agency['email']) ) $emails[] = array(
                                        'name' => '',
                                        'email'=> $agency['email']
                                    );
    if(empty($agency['id_tarif']) && !empty($agency['email_service']) && Validate::isEmail($agency['email_service'])) $emails[] = array(
                                                                                                                            'name' => '',
                                                                                                                            'email'=> $agency['email_service']
                                                                                                                        );
   
    //отправка письма
    $sendpulse = new Sendpulse( );
    $result = $sendpulse->sendMail( 'Обработка формата BNXML. '.date('Y-m-d H:i:s'), $mail_text, false, false, 'BSN.ru', 'no-reply@bsn.ru', $emails );
    
    require_once("cron/robot/parsers/emls_xml/reports/emls_report.php");
}
    
if(!empty($log)){
    
    $html = "";
    //отчеты о загрузке файлов
    if(!empty($log['download'])){
        foreach($log['download'] as $k=>$text)   $html .= date('d.m.Y H:i:s')." : ".$text.'<br /><br />';
    }
    if(empty($html)) $html = $mail_text;
    
    $emails = array(
        array(
            'name' => '',
            'email'=> 'hitty@bsn.ru'
        )
    );
    //отправка письма
    $sendpulse = new Sendpulse( );
    $result = $sendpulse->sendMail( 'EMLS XML : Обработка файлов робота объектов. '.date('Y-m-d H:i:s'), $html, false, false, 'BSN.ru', 'no-reply@bsn.ru', $emails );
    
}
?>