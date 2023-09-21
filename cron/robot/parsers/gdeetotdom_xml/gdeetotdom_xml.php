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
$error_log = ROOT_PATH.'/cron/robot/parsers/gdeetotdom_xml/error.log';
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


/*
if(date('H')==8) {
    $log['download'][] = downloadXmlFile("eip","alex_n_1","http://alexander.rmbd.ru/yf/export/EIP/eip.xml",4764); 
    $log['download'][] = downloadXmlFile("eip","alex_n_2","http://alexander.rmbd.ru/yf/export/EIPreg/eip.xml",4764); 
}
*/
/*
if(date('H')==1) {
    $log['download'][] = downloadXmlFile("eip","ecn_1","http://ecngroup.ru/dump/xml-eip.xml",27145);
}
if(date('H')==2 && (date('N')==2 || date('N')==5)) $log['download'][] = downloadXmlFile("eip","mirkvartir_build","http://www.mkparser.ru/download.php?file=eipBsn.xml",1072); 
if(date('H')==3) $log['download'][] = downloadXmlFile("eip","itlab","https://assis.ru/export/BSN",33915);
if(date('H')==4) $log['download'][] = downloadXmlFile("eip","matrix","http://www.mxcity.ru/importfiles/export/bsn.xml",32304);
if(date('H')==5) $log['download'][] = downloadXmlFile("eip","roslex","http://www.roslex-estate.ru/xml/eip/eipformat.xml",760);
if(date('H')==6) $log['download'][] = downloadXmlFile("eip","evrometr","http://evro-metrspb.ru/dateip.xml",23921);
if(date('H')==7) $log['download'][] = downloadXmlFile("eip","gja","http://gja.pro.bkn.ru/yf/export/EIP/eip.xml",3887,false);       
if(date('H')==8) $log['download'][] = downloadXmlFile("eip","pennylane","http://map.bazanda.ru/export/spbretail/eip.xml",4342);    
*/


$rent_titles = array(1=>'аренда', 2=>'продажа'); //типы сделок
//папка с xml файлами 
$dir = ROOT_PATH."/cron/robot/files/gdeetotdom_xml/";
//флаг однократного обновления
$update_flag = true;
$dh = opendir($dir);
$mail_text = '';  // текст письма
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..')
    {
        exec("chmod 777 ".$dir.$filename);
        
        $mail_text .= 'Файл:'.$dir.$filename.'<br />';  // текст письма
        $errors_log=array();  // ошибки
        $counter = array('live_sell'=>0,            'live_rent'=>0,             'commercial'=>0,            'country'=>0,           'build'=>0,
                         'live_sell_promo'=>0,      'live_rent_promo'=>0,       'commercial_promo'=>0,      'country_promo'=>0,     'build_promo'=>0,
                         'live_sell_premium'=>0,    'live_rent_premium'=>0,     'commercial_premium'=>0,    'country_premium'=>0,   'build_premium'=>0,
                         'live_sell_elite'=>0,      'live_rent_elite'=>0,       'commercial_elite'=>0,      'country_elite'=>0,     'build_elite'=>0,
                         'total'=>0
        );
        
        //Определение id_user по начальному имени файла
        $id_user = explode('_',$filename);
        $id_user = Convert::ToInt($id_user[0]); 
        if($id_user<1) $mail_text.="Ошибка авторизации";
        else
        {
            //информация об агентстве
            $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".`id`, 
                                         ".$sys_tables['agencies'].".`title`,
                                         ".$sys_tables['agencies'].".`elite_objects`,
                                         ".$sys_tables['agencies'].".`live_rent_objects`,
                                         ".$sys_tables['agencies'].".`activity`, 
                                         ".$sys_tables['managers'].".`email`, 
                                         ".$sys_tables['agencies'].".`email_service` 
                                  FROM ".$sys_tables['agencies']."
                                  RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
                                  LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager 
                                  WHERE ".$sys_tables['users'].".`id` = ?",
                                  $id_user) ;            
            if(empty($agency)) $mail_text.="Ошибка авторизации"; //агентство не найдено
            else {
                
                //рекламное агентство
                $advert_agency = $agency['activity']%pow(2,2)>=pow(2,1)?true:false;
                //текст письма
                $mail_text .= "Обработка объектов агентства ".$agency['title']."<br /><br />";
                /*
                if($update_flag){
                    // постановка в архив всех объектов этой компании
                    $db->querys("UPDATE ".$sys_tables['live']." SET `published` = '2', `date_change` = NOW() WHERE id_user = ? AND info_source=? AND `published` = 1",$id_user,3);
                    $db->querys("UPDATE ".$sys_tables['build']." SET `published` = '2', `date_change` = NOW() WHERE id_user = ? AND info_source=? AND `published` = 1",$id_user,3);
                    $db->querys("UPDATE ".$sys_tables['country']." SET `published` = '2', `date_change` = NOW() WHERE id_user = ? AND info_source=? AND `published` = 1",$id_user,3);
                    $db->querys("UPDATE ".$sys_tables['commercial']." SET `published` = '2', `date_change` = NOW() WHERE id_user = ? AND info_source=? AND `published` = 1",$id_user,3);
                    $update_flag = false;
                }
                */
                $xml_values = array(); 
                //читаем в строку нужный файл
                $contents = file_get_contents($dir.$filename);
                $contents=mb_convert_encoding($contents,"UTF-8","Windows-1251");
                $xml_str=xml2array($contents);
                $xml_str=$xml_str['LISTINGS'];
                
                
                //по строке создаем объект simplexml
                if(($xml_str===FALSE)||($xml_str['ADS']['OBJECTS']))  $errors_log['fatal'] = 'Файл '.$dir.$filename.' не может быть обработан, т.к. имеет невалидные теги'; 
                else {
                    foreach ($xml_str['ADS']['OBJECT'] as $object) $xml_values[] =  $object;
                    
                    //обработка полученных значений
                    foreach($xml_values as $key=>$values){
                        //картинки
                        if(!empty($values['FILES']['FILE'])){
                            if(is_array($values['FILES']['FILE'])){
                                foreach($values['FILES']['FILE'] as $key=>$img){
                                    if(!empty($img['FILEPATH']) && strlen($img['FILEPATH'])>10) {
                                        $images[]=$img['FILEPATH'];
                                    }
                                }
                            }
                        }
                        //так как главное фото не указывается, берем первое
                        $main_photo=$images[0];
                        
                        //приведение всех ключей в нижний регистр
                        xmlstructtolower($values);
                        $fields = $photo_list = array();                    
                        $robot = new GdeetotXmlRobot($id_user);
                        $fields = $robot->getConvertedFields($values, $agency);
                        if(!($robot->estate_type == 'live' && $fields['rent'] == 1 && $counter['live_rent']>=$agency['live_rent_objects'])){ //отсечение аренды жилой
                            //сумма всех итераций
                            ++$counter['total'];
                            //получение статуса модерации объекта
                            $moderate = new Moderation($robot->estate_type,0);
                            $moderate_status = $moderate->getModerateStatus($fields);
                            $fields['hash'] = $moderate->makeHash();
                            //для непрошедших модерацию
                            if($moderate_status>1){
                                $fields['published'] = 3; //на модерации
                                $errors_log['moderation'][$fields['external_id']] = array(($moderate_status!=4?$fields['cost'].', '.$rent_titles[$fields['rent']]:$fields['txt_addr']),$moderate_status);        
                            } else $fields['published'] = 1;
                            //массив с фото
                            $photos = array('out'=>array(),'in'=>array());
                            //префикс для фото
                            $prefix = '';
                            //поиск ранее загруженного объекта в основной таблице
                            $check_object = $db->fetch("SELECT `id`,`id_main_photo` FROM ".$sys_tables[($robot->estate_type)]." WHERE `external_id` = ? AND `id_user` = ?  AND `info_source` = ?",
                                                    $fields['external_id'], $id_user, $fields['info_source']
                            );
                            if(!empty($check_object)) { 
                                $fields['id'] = $check_object['id'];
                                //updat'им данные
                                $res = $db->updateFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                                
                                //определение списка фотографий, которых нет в БД
                                if(!empty($images)) list($photos['in'],$photos['out']) = $robot->getPhotoList($images, $check_object['id']);
                                //удаление фоток (из базы и с сервера), которые не вошли в xml
                                $photo_list = $db->fetchall("SELECT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                             WHERE `id_parent` = ".$check_object['id']." 
                                                             ".(!empty($photos['in'])?"AND `external_img_src` NOT IN (".implode(',', $photos['in']).")":""));
                                if(!empty($photo_list)){
                                    foreach($photo_list as $k => $val) Photos::Delete($robot->estate_type,$val['id']);
                                    if(!empty($photo_list['in'])) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".implode(',', $photo_list['in']).")");
                                }
                                $inserted_id = $check_object['id'];

                                //если объект на модерации
                                if($moderate_status>1){
                                    //проверяем его наличие в таблице new
                                    $check_object_new = $db->fetch("SELECT `id` FROM ".$sys_tables[($robot->estate_type).'_new']." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",
                                                            $fields['external_id'], $id_user, $fields['info_source']);
                                    $fields['id_object'] = $fields['id'];
                                    unset($fields['id']);
                                    $fields['id_moderate_status'] = $moderate_status;
                                    //если есть - update
                                    if(!empty($check_object_new)) $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id_object');
                                    //еси нет - вставка
                                    else $res = $db->insertFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id_object');
                                }                       
                            }
                            else 
                            {
                               //поиск ранее загруженного объекта в таблице _new
                                $check_object_new = $db->fetch("SELECT `id` FROM ".$sys_tables[($robot->estate_type).'_new']." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",
                                                        $fields['external_id'], $id_user, $fields['info_source']
                                );
                                if(!empty($check_object_new)) {
                                    $fields['id'] = $check_object_new['id'];
                                    //updat'им данные
                                    $fields['date_in']= date('Y-m-d H:i:s');
                                    $fields['id_moderate_status'] = $moderate_status;
                                    $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                                    
                                    //определение списка фотографий, которых нет в БД
                                    if(!empty($images)) list($photos['in'],$photos['out']) = $robot->getPhotoList($images, $check_object_new['id'],'_new');
                                    print_r($photos);
                                    //удаление фоток (из базы и с сервера)
                                    $photo_list = $db->fetchall("SELECT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                                 WHERE `id_parent_new` = ".$check_object_new['id']." 
                                                                 ".(!empty($photos['in'])?"AND `external_img_src` NOT IN (".implode(',', $photos['in']).")":""));
                                    if(!empty($photo_list)){
                                        foreach($photo_list as $k => $val) Photos::Delete($robot->estate_type,$val['id'],"_new");
                                        $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".implode(',', $photo_list['in']).")");
                                    }
                                        
                                    $inserted_id = $check_object_new['id'];
                                    $prefix = '_new';
                                    
                                } else {
                                    $fields['date_in']=$fields['date_change']= date('Y-m-d H:i:s');
                                    if($advert_agency && $moderate_status==1){ // для рекламных агентств прошедших модерацию - нет проверки на склейку
                                         $fields['date_change'] = date('Y-m-d H:i:s');
                                         $res = $db->insertFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                                    } else {
                                        $fields['date_in'] = date('Y-m-d H:i:s');
                                        $prefix = '_new';
                                        $res = $db->insertFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                                    }
                                    if(!empty($images)) $photos['out'] =  $images;
                                    $inserted_id = $db->insert_id;
                                }
                            }         
                            echo $inserted_id.' : ';
                            echo $robot->estate_type.' : '.$prefix.' : ';
                            // если есть картинки - присоединяем
                            if(!empty($photos['out']) && $inserted_id>0) {
                                $external_img_sources = Photos::MultiDownload($photos['out'], ROOT_PATH.'/'.Config::$values['img_folders'][$robot->estate_type].'/');
                                foreach($external_img_sources as $k=>$img) {
                                    Photos::Add($robot->estate_type, $inserted_id, $prefix, $img['external_img_src'], $img['filename']);
                                    if($main_photo==$img['external_img_src']) Photos::setMain($robot->estate_type, $inserted_id,null,$prefix);
                                }
                            }
                            //обновление главной фотки
                            if($main_photo!='') {
                                $photo_id = $db->fetch("SELECT id FROM ".$sys_tables[$robot->estate_type."_photos"]." WHERE id_parent".$prefix."=? AND external_img_src = ?",$inserted_id,$main_photo);
                                if(!empty($photo_id)) $db->querys("UPDATE ".$sys_tables[$robot->estate_type.$prefix]." SET id_main_photo = ? WHERE id = ?",$photo_id['id'],$inserted_id);
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
                            if($moderate_status==1) $counter[$robot->estate_type.$fields['rent_prefix']]++;
                            else {
                                if(!empty($fields['elite']) && $fields['elite']==1) $counter[$robot->estate_type.$fields['rent_prefix'].'_elite']--;
                            }
                            unset($fields); unset($values); unset($images); unset($photos); unset($photo_list);
                        } //end of: проверка на лимит жилой аренды
                    }  //end of: //обработка полученных значений foreach($xml_values as $key=>$values)
                }//end of: fatal_error = 0 (валидность xml)
            } //end of if(empty($agency)) - ошибка авторизации
        } // end of: if($id_user>1) - ошибка авторизации 
        //удаление файла
        //unlink($dir.$filename);
        //отправка отчета о вариантах
        if(!empty($counter)){
            $total = $counter['total'];
            $mail_text .= "Обработано всего объектов:".$total;
            $mail_text .= "<br />Добавлено объектов: ".($counter['live_sell']+$counter['live_rent']+$counter['build']+$counter['country']+$counter['commercial'])."<br />
            - жилая (продажа): ".$counter['live_sell'].($counter['live_sell_promo']>0? ", промо: ".$counter['live_sell_promo']:"").($counter['live_sell_premium']>0? ", премиум: ".$counter['live_sell_premium']:"").($counter['live_sell_elite']>0? ", элитных: ".$counter['live_sell_elite']:"")."<br />
            - стройка: ".$counter['build'].($counter['build_promo']>0? ", промо: ".$counter['build_promo']:"").($counter['build_premium']>0? ", премиум: ".$counter['build_premium']:"").($counter['build_elite']>0? ", элитных: ".$counter['build_elite']:"")."<br />
            - коммерческая: ".$counter['commercial'].($counter['commercial_promo']>0? ", промо: ".$counter['commercial_promo']:"").($counter['commercial_premium']>0? ", премиум: ".$counter['commercial_premium']:"").($counter['commercial_elite']>0? ", элитных: ".$counter['commercial_elite']:"")."<br />
            - загородная: ".$counter['country'].($counter['country_promo']>0? ", промо: ".$counter['country_promo']:"").($counter['country_premium']>0? ", премиум: ".$counter['country_premium']:"").($counter['country_elite']>0? ", элитных: ".$counter['country_elite']:"")."<br /><br />
            - жилая (аренда): ".$counter['live_rent'].($counter['live_rent_promo']>0? ", промо: ".$counter['live_rent_promo']:"").($counter['live_rent_premium']>0? ", премиум: ".$counter['live_rent_premium']:"").($counter['live_rent_elite']>0? ", элитных: ".$counter['live_rent_elite']:"")."<br />

            ";
        }
        //логирование ошибок
        if(!empty($errors_log)){
            $mail_text .= "<br /><br />При обработке файла возникли следующие ошибки:";
            if(!empty($errors_log['fatal'])) $mail_text .= "<br /><br />".$errors_log['fatal']; 
            if(!empty($errors_log['moderation']))  {
                $mail_text .= "<br /><br /><strong>Не прошли модерацию: ".count($errors_log['moderation'])."</strong>";
                foreach($errors_log['moderation'] as $k=>$moderation) $mail_text .= "<br />external_id: <strong>".$k.'</strong>, статус: <i>'.$moderate_statuses[$moderation[1]]."</i>, значение: ".$moderation[0];
            }    
            if(!empty($errors_log['img'])){ //ошибки загрузки фото
                $mail_text .= "<br /><br />Незагруженные фотографии объектов:";
                foreach($errors_log['img'] as $k=>$img) $mail_text .= "<br />".$img;
            }
        }
        $mail_text .= "<br /><br />";
    } // end of: $filename=='xml-file'
}
if($mail_text!=''){
    $mailer = new EMailer('mail');
    
    // перевод письма в кодировку мейлера
    $html = iconv('UTF-8', $mailer->CharSet, $mail_text);
    // параметры письма
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Обработка формата EIPXML. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
    if(!empty($agency['email']) && Validate::isEmail($agency['email']) )$mailer->AddAddress($agency['email']);     //отправка письма ответственному менеджеру
    if(!empty($agency['email_service'])  && Validate::isEmail($agency['email_service']) )$mailer->AddAddress($agency['email_service']);     //отправка письма агентству
    $mailer->AddAddress('hitty@bsn.ru');
    $mailer->From = 'bsnxml@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Парсинг EIPXML файла');
    // попытка отправить
    $mailer->Send();        
}
//если были ошибки выполнения скрипта
if(filesize($error_log)>10){
    $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
    $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
    $error_log_text .= '</font>';
} else $error_log_text = "";
    
if($mail_text!=''){
    $mailer = new EMailer('mail');
    
    // перевод письма в кодировку мейлера
    $html = iconv('UTF-8', $mailer->CharSet, $mail_text.$error_log_text);
    // параметры письма
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Обработка формата EIPXML. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
    $mailer->AddAddress('hitty@bsn.ru');
    $mailer->From = 'bsnxml@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Парсинг EIPXML файла');
    // попытка отправить
    $mailer->Send();        
}
if(!empty($log['download'])){
    $mailer = new EMailer('mail');
    // перевод письма в кодировку мейлера
    $html = "";
    //отчеты о загрузке файлов
    if(!empty($log['download'])){
        foreach($log['download'] as $k=>$text)   $html .= date('d.m.Y H:i:s')." : ".$text.'<br /><br />';
    }
    $html = iconv('UTF-8', $mailer->CharSet, $html);
    // параметры письма
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'eip_xml.php : Проверка выгрузки файлов EIP XML. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
    $mailer->AddAddress('scald@bsn.ru');
    $mailer->From = 'agregator@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Агрегатор BSN');
    // попытка отправить
    $mailer->Send();        
    echo $html;
}
/**
* функция для переведения в нижний регистр всех ключей объекта из xml-файла
* @param mixed &$xmlstruct - объект полученный с помощью функции xml2array
*/
function xmlstructtolower(&$xmlstruct){
    foreach($xmlstruct as $key=>$value){
        if ($value == null){
            $xmlstruct[strtolower($key)]=$value;
            if ($key != strtolower($key))
                unset($xmlstruct[$key]);
            else break;
        }
        if (gettype($value) != TYPE_ARRAY){
            $xmlstruct[strtolower($key)] = $value;
            if ($key != strtolower($key))
                unset($xmlstruct[$key]);
            else break;
        } 
        elseif ($value != null){
            foreach ($value as $vkey=>$vvalue){
                if (gettype($vvalue) != TYPE_ARRAY&&$vkey!=strtolower($vkey)){
                    $xmlstruct[strtolower($key)][strtolower($vkey)] = $vvalue;
                    if ($vkey != strtolower($vkey))
                        unset($xmlstruct[$key][$vkey]);
                    else break;
                } 
                else{
                    $xmlstruct[strtolower($key)][strtolower($vkey)] = $xmlstruct[$key][$vkey];
                    if ($key != strtolower($key)){
                        unset($xmlstruct[$key][$vkey]);
                        xmlstructtolower($xmlstruct[strtolower($key)][strtolower($vkey)]);
                    }
                }
            }
        }
    }
    foreach($xmlstruct as $key=>$value){
        if (empty($value)) unset($xmlstruct[$key]);
    }
}
?>
