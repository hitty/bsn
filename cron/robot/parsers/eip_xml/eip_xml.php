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
$error_log = ROOT_PATH.'/cron/robot/parsers/eip_xml/error.log';
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

//читаем агентство которое должно сейчас выгружаться
$agency_xml_info = $db->fetch("SELECT ".$sys_tables['agencies'].".id,
                                      ".$sys_tables['users'].".id AS id_user,
                                      xml_link,
                                      xml_status,
                                      xml_alias
                               FROM ".$sys_tables['agencies']."
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency            
                               WHERE ".$sys_tables['agencies'].".xml_time = ".date('H')." AND (xml_day=0 OR xml_day=".date('N').") AND xml_status=1 AND xml_type=2");
//если что-то нашли, скачиваем файл
if($agency_xml_info) $log['download'][] = downloadXmlFile("eip",$agency_xml_info['xml_alias'],$agency_xml_info['xml_link'],$agency_xml_info['id_user'],false);

$rent_titles = array(1=>'аренда', 2=>'продажа'); //типы сделок

//массив пар external_id - bsn_url для Александр-недвижимость
$ids_conformity = array();

//папка с xml файлами 
$dir = ROOT_PATH."/cron/robot/files/eip_xml/";
//флаг однократного обновления
$update_flag = true;
$dh = opendir($dir);
$mail_text = '';  // текст письма
while($filename = readdir($dh)){
    //берем только свой файл
    if(!empty($agency_xml_info) && $filename == $agency_xml_info['id_user']."_".$agency_xml_info['xml_alias'].".xml")
    {
        exec("chmod 777 ".$dir.$filename);
        
        $mail_text .= 'Файл:'.$dir.$filename.'<br />';  // текст письма
        $errors_log = array();  // ошибки
        $counter = array('live_sell'=>0,            'live_rent'=>0,             'commercial_sell'=>0,            'commercial_rent'=>0,          'build'=>0,            'country_sell'=>0,            'country_rent'=>0, 
                         'live_sell_promo'=>0,      'live_rent_promo'=>0,       'commercial_sell_promo'=>0,      'commercial_rent_promo'=>0,    'build_sell_promo'=>0,      'country_sell_promo'=>0,      'country_rent_promo'=>0, 
                         'live_sell_premium'=>0,    'live_rent_premium'=>0,     'commercial_sell_premium'=>0,    'commercial_rent_premium'=>0,  'build_sell_premium'=>0,    'country_sell_premium'=>0,    'country_rent_premium'=>0, 
                         'live_sell_elite'=>0,      'live_rent_elite'=>0,       'commercial_sell_elite'=>0,      'commercial_rent_elite'=>0,    'build_sell_elite'=>0,      'country_sell_elite'=>0,      'country_rent_elite'=>0,
                         'total'=>0
        );
        //счетчик "склеенных" объектов
        $counter_joint = array('live_sell'=>0,'live_rent'=>0,'commercial_sell'=>0,'commercial_rent'=>0,'build'=>0,'country_sell'=>0,'country_rent'=>0,'total'=>0);
        $previous_id = 0;
        //Определение id_user по начальному имени файла
        $id_user = explode('_',$filename);
        $id_user = Convert::ToInt($id_user[0]); 
        if($id_user<1) $mail_text.="Ошибка авторизации";
        else
        {
            //информация об агентстве
            $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".*,
                                         ".$sys_tables['managers'].".`email` as email,
                                         ".$sys_tables['users'].".id_tarif,
                                         IF(".$sys_tables['tarifs'].".title IS NOT NULL,".$sys_tables['tarifs'].".title,'') AS tarif_title
                                  FROM ".$sys_tables['agencies']."
                                  RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
                                  LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager 
                                  LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['users'].".id_tarif = ".$sys_tables['tarifs'].".id
                                  WHERE ".$sys_tables['users'].".`id` = ?",
                                  $id_user) ;            
            //записываем ограничения на промо и премиум
            $promo_limit = $agency['limit_promo'];
            $premium_limit = $agency['limit_premium'];
            $promo_vars = 0;
            $premium_vars = 0;
            if(empty($agency)) $mail_text.="Ошибка авторизации"; //агентство не найдено
            elseif(!empty($agency['id_tarif'])) $mail_text .= "Объекты агентства ".$agency['title']." не выгрузились, т.к. для него установлен тариф ".$agency['tarif_title'];
            else {
                
                //рекламное агентство
                $advert_agency = $agency['activity']%pow(2,2)>=pow(2,1)?true:false;
                //текст письма
                $mail_text .= "Обработка объектов агентства ".$agency['title']."<br /><br />";
                
                if($update_flag){
                    // постановка в архив всех объектов этой компании
                    $db->querys("UPDATE ".$sys_tables['live']." SET `published` = '2', `date_change` = NOW() WHERE id_user = ? AND info_source=? AND `published` = 1",$id_user,3);
                    $db->querys("UPDATE ".$sys_tables['build']." SET `published` = '2', `date_change` = NOW() WHERE id_user = ? AND info_source=? AND `published` = 1",$id_user,3);
                    $db->querys("UPDATE ".$sys_tables['country']." SET `published` = '2', `date_change` = NOW() WHERE id_user = ? AND info_source=? AND `published` = 1",$id_user,3);
                    $db->querys("UPDATE ".$sys_tables['commercial']." SET `published` = '2', `date_change` = NOW() WHERE id_user = ? AND info_source=? AND `published` = 1",$id_user,3);
                    $update_flag = false;
                }
                $xml_values = array(); 
                //читаем в строку нужный файл
                $contents = file_get_contents($dir.$filename);
                $xml_str=xml2array($contents);
                
                //по строке создаем объект simplexml
                if($xml_str===FALSE || empty($xml_str['eip']['rec'][0]))  $errors_log['fatal'] = 'Файл '.$dir.$filename.' не может быть обработан, т.к. имеет невалидные теги'; 
                else {
                    foreach ($xml_str['eip']['rec'] as $object) $xml_values[] =  $object;
                    
                    //отмечаем в базе, что в данный момент файл агентства выгружается (пишем количество объектов в файле)
                    $xml_size = count($xml_values);
                    $db->querys("UPDATE ".$sys_tables['agencies']." SET xml_file_processing = ? WHERE id = ".$agency_xml_info['id'],$xml_size);
                    
                    //обработка полученных значений
                    foreach($xml_values as $key=>$values){
                        //картинки
                        $main_photo = '';
                        if(!empty($values['image'])){
                            if(is_array($values['image'])){
                                foreach($values['image'] as $key=>$img){
                                    if(!empty($img['link']) && strlen($img['link'])>10) {
                                        $images[]=$img['link'];
                                        if(!empty($img['titul']) && $img['titul']==1) $main_photo = $img['link'];
                                    }
                                }
                            } elseif(is_string($values['image']) && strlen($values['image'])>10) $images[] = $values['image'];
                            else  $images[] = $values['image']['image'];
                        } else if(!empty($values['image_attr']['link'])) $images[] = $values['image_attr']['link'];

                        //приведение всех ключей в нижний регистр
                        foreach($values as $k=>$val) {
					        $values[strtolower($k)] = $val;
		                }
                        //"костыль" для объектов от А-Недвижимость (по 2 раза наименование нас.пункта)
                         if(!empty($values['address'])){
                            $addr = explode(" ",trim($values['address']));
                            $similar = array_count_values($addr);
                            foreach($addr as $addr_value){
                                $key = array_keys($addr,$addr_value,true);
                                if(count($key)>1 && strlen($addr_value)>5) unset($addr[$key[0]]);
                            }
                            $values['address'] = implode(" ",$addr);
                        }
                        
                        if(empty($values['viewtype'])) $values['viewtype'] = 0;
                        
                        $fields = $photo_list = array();                    
                        $robot = new EIPXmlRobot($id_user);
                        $fields = $robot->getConvertedFields($values, $agency);
                        
						$current_object_id = $fields['external_id'];

                        //проверка лимита
                        $deal_type = $robot->estate_type == 'build' ? '' : ($fields['rent'] == 1 ? '_rent' : '_sell');
                        $check_limit = ($robot->estate_type.$deal_type == 'live_rent' && $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects']) || 
											  ($robot->estate_type.$deal_type != 'live_rent' && ($agency['id_tarif']==1 || 
																													   $agency['id_tarif']==7 || 
																													   $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects'])); 
                        if(!empty($fields)) {
                            //сумма всех итераций
                            ++$counter['total'];
                            //счетчик сверх лимита
                            if(!$check_limit) {
                                if(empty($errors_log['over_limit'])) $errors_log['over_limit'] = 0;
                                $errors_log['over_limit']++;
                                empty($counter[$robot->estate_type.$deal_type.'_over_limit']) ? $counter[$robot->estate_type.$deal_type.'_over_limit'] = 1 : $counter[$robot->estate_type.$deal_type.'_over_limit']++;
                            }
                            else
                            if((!empty($promo_limit) && $promo_vars>$promo_limit) || (!empty($premium_limit) && $premium_vars>$premium_limit))
                                $errors_log['over_limit']++;
                        }
                        
                        if( !empty($fields) && $check_limit && 
                            ($agency['xml_only_with_photo'] == 2 || !empty($images)) &&
                            ( (empty($promo_limit) || ( ($values['viewtype'] == 1 && $promo_vars <= $promo_limit) || $values['viewtype'] != 1 )) &&
                              (empty($premium_limit) || ( ($values['viewtype'] == 2 && $premium_vars <= $premium_limit) || $values['viewtype'] != 2 ))
                             )
                          ){ //отсечение лимитов
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
                                                    $fields['external_id'], $id_user, 3
                            );
                            if(!empty($check_object)) { 
                                $fields['id'] = $check_object['id'];
                                //updat'им данные
                                $res = $db->updateFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                                $result = $db->affected_rows;
                                
                                //если перезаписали объект из этой выгрузки, увеличиваем счетчик склеенных (для стройки не нужен постфикс)
                                if($check_object['published'] == 1){
                                    ++$counter_joint[$robot->estate_type.($robot->estate_type!='build'?($fields['rent']==2?'_sell':'_rent'):"")];
                                    ++$counter_joint['total'];
                                } 
                                
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
                                                            $fields['external_id'], $id_user, 3);
                                    $fields['id_object'] = $fields['id'];
                                    unset($fields['id']);
                                    $fields['id_moderate_status'] = $moderate_status;
                                    //если есть - update
                                    if(!empty($check_object_new)) $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id_object');
                                    //еси нет - вставка
                                    else $res = $db->insertFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id_object');
                                    $result = $db->affected_rows;
                                }                       
                            }
                            else 
                            {
                               //поиск ранее загруженного объекта в таблице _new
                                $check_object_new = $db->fetch("SELECT `id` FROM ".$sys_tables[($robot->estate_type).'_new']." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",
                                                        $fields['external_id'], $id_user, 3
                                );
                                if(!empty($check_object_new)) {
                                    $fields['id'] = $check_object_new['id'];
                                    //updat'им данные
                                    $fields['date_in']= date('Y-m-d H:i:s');
                                    $fields['id_moderate_status'] = $moderate_status;
                                    $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                                    $result = $db->affected_rows;
                                    
                                    //определение списка фотографий, которых нет в БД
                                    if(!empty($images)) list($photos['in'],$photos['out']) = $robot->getPhotoList($images, $check_object_new['id'],'_new');
                                    print_r($photos);
                                    //удаление фоток (из базы и с сервера)
                                    $photo_list = $db->fetchall("SELECT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                                 WHERE `id_parent_new` = ".$check_object_new['id']." 
                                                                 ".(!empty($photos['in'])?"AND `external_img_src` NOT IN (".implode(',', $photos['in']).")":""));
                                    /*
                                    if(!empty($photo_list)){
                                        foreach($photo_list as $k => $val) Photos::Delete($robot->estate_type,$val['id'],"_new");
                                        $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".implode(',', $photo_list['in']).")");
                                    }
                                    */    
                                    $inserted_id = $check_object_new['id'];
                                    $prefix = '_new';
                                    
                                } else {
                                    $fields['date_in']=$fields['date_change']= date('Y-m-d H:i:s');
                                    if($advert_agency && $moderate_status==1){ // для рекламных агентств прошедших модерацию - нет проверки на склейку
                                         $fields['date_change'] = date('Y-m-d H:i:s');
                                         $res = $db->insertFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                                         $result = $db->affected_rows;
                                    } else {
                                         $fields['date_in'] = date('Y-m-d H:i:s');
                                         $prefix = '_new';
                                         $res = $db->insertFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                                         $result = $db->affected_rows;
                                    }
                                    if(!empty($images)) $photos['out'] =  $images;
                                    $inserted_id = $db->insert_id;
                                }
                            }         
                            echo $inserted_id.' : ';
                            echo $robot->estate_type.' : '.$prefix.' : ';
                            
							//записываем external_id и bsn_id в массив
							if(!empty($inserted_id)){
                                $ids_conformity[] = array('external_id'=>$current_object_id,
                                                          'url'=>"https://www.bsn.ru/".$robot->estate_type."/".preg_replace('/[^a-z]/','',$deal_type)."/".$inserted_id."/");
                            }

                            ///считаем и записываем вес объекта (в таблицу estate_type или estate_type_new
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
                            if(!empty($photos['out']) && $inserted_id>0) {
                                $external_img_sources = Photos::MultiDownload($photos['out'], ROOT_PATH.'/'.Config::$values['img_folders'][$robot->estate_type].'/');
                                foreach($external_img_sources as $k=>$img) {
                                    $photo_add_result = Photos::Add($robot->estate_type, $inserted_id, $prefix, $img['external_img_src'], $img['filename']);
                                    if(!is_array($photo_add_result)) $errors_log['img'][] = $img['external_img_src'];
                                    if($main_photo==$img['external_img_src']) Photos::setMain($robot->estate_type, $inserted_id,null,$prefix);
                                }
                            }
                            //обновление главной фотки
                            if($main_photo!='') {
                                $photo_id = $db->fetch("SELECT id FROM ".$sys_tables[$robot->estate_type."_photos"]." WHERE id_parent".$prefix."=? AND external_img_src = ?",$inserted_id,$main_photo);
                                if(!empty($photo_id)) $db->querys("UPDATE ".$sys_tables[$robot->estate_type.$prefix]." SET id_main_photo = ? WHERE id = ?",$photo_id['id'],$inserted_id);
                            }
                            //обновление главной фотографии объекта если она не прикреплена
							/*
                            if($inserted_id>0 && !empty($photos['in']) && !empty($check_object['id']) && $check_object['id_main_photo']==0){
                                $photo_id = $db->fetch("SELECT id FROM ".$sys_tables[($robot->estate_type)."_photos"]." WHERE id_parent = ?",$inserted_id);
                                if(!empty($photo_id)) $db->querys("UPDATE ".$sys_tables[($robot->estate_type)]." SET id_main_photo = ? WHERE id = ?",$photo_id['id'],$inserted_id);
                            }
							*/
                            
                            //модерация новых объектов
                            if($prefix=='_new') {
                                $moderate = new Moderation($robot->estate_type,$inserted_id);
                                $moderate->checkObject();
                            }
							//обновление главной фотографии объекта если она не прикреплена и объект прошел модерацию
							if($moderate_status == 1 && $inserted_id>0  && empty($main_photo)){
                                $photo_id = $db->fetch("SELECT id FROM ".$sys_tables[($robot->estate_type)."_photos"]." WHERE id_parent = ?",$inserted_id);
                                if(!empty($photo_id)) $db->querys("UPDATE ".$sys_tables[($robot->estate_type)]." SET id_main_photo = ? WHERE id = ?",$photo_id['id'],$inserted_id);
                            } 

                            //счетчик кол-ва вариантов
                            if($moderate_status == 1 && !empty($result)) $counter[$robot->estate_type.$deal_type]++;
                            else {
                                if(!empty($fields['elite']) && $fields['elite']==1) $counter[$robot->estate_type.$deal_type.'_elite']--;
                            }
                            unset($fields); unset($values); unset($images); unset($photos); unset($photo_list);
                        } //end of: проверка на лимит жилой аренды
                    }  //end of: //обработка полученных значений foreach($xml_values as $key=>$values)
                }//end of: fatal_error = 0 (валидность xml)
            } //end of if(empty($agency)) - ошибка авторизации
        } // end of: if($id_user>1) - ошибка авторизации 
        //удаление файла
        unlink($dir.$filename);

		$counter_real = array();
        
        //учитываем "склеенные" объекты
        foreach($counter_joint as $key=>$item){
            $counter_real[$key] = $counter[$key] - $counter_joint[$key];
        }
        
        //для Адвекс-новостроек и Рускола, удаляем дубли
        if(!empty($counter['total']) && ($id_user == 3991 || $id_user = 236)){
            $duplicates = array();
            
            $duplicates['total'] = 0;
            //типы недвижимости
            $estate_types = array('build','live','commercial','country');
            //дополнительное условие на дубль для разнах типов разное
            $estate_restrictions = array('level','level','txt_level','house');
            //
            foreach($estate_types as $key=>$item){
                //аренда
                $db->querys("UPDATE ".$sys_tables[$item]." n1, ".$sys_tables[$item]." n2
                            SET n1.published=4
                            WHERE n1.id_user=3991 AND n2.id_user=3991 AND
                                  n2.published=1 AND n1.published=1 AND
                                  n1.status=2 AND n2.status=2 AND
                                  n2.rent=1 AND n1.rent=1 AND
                                  n1.txt_addr=n2.txt_addr AND n1.notes=n2.notes AND
                                  n1.cost=n2.cost AND
                                  n1.".$estate_restrictions[$key]." = n2.".$estate_restrictions[$key]." AND
                                  n1.weight=n2.weight AND
                                  n1.id<n2.id");
                $duplicates[$item."_rent"] = max(0,mysqli_affected_rows($db));
                //продажа
                $db->querys("UPDATE ".$sys_tables[$item]." n1, ".$sys_tables[$item]." n2
                            SET n1.published=4
                            WHERE n1.id_user=3991 AND n2.id_user=3991 AND
                                  n2.published=1 AND n1.published=1 AND
                                  n1.status=2 AND n2.status=2 AND
                                  n2.rent=2 AND n1.rent=2 AND
                                  n1.txt_addr=n2.txt_addr AND n1.notes=n2.notes AND
                                  n1.cost=n2.cost AND
                                  n1.".$estate_restrictions[$key]." = n2.".$estate_restrictions[$key]." AND
                                  n1.weight=n2.weight AND
                                  n1.id<n2.id");
                $duplicates[$item."_sell"] = max(0,mysqli_affected_rows($db));
                if($item == 'build'){
                    $duplicates[$item] = max(0,mysqli_affected_rows($db));
                    if(!empty($duplicates[$item])){
                        $counter_real[$item] = $counter[$item] - $duplicates[$item];
                        $counter_real[$item] = $counter_real[$item]." ( Удалено дубликатов: ".$duplicates[$item].")";
                    }
                    else $counter_real[$item] = $counter[$item];
                    $duplicates['total'] += $duplicates[$item];
                } 
                else{
                    $duplicates[$item."_sell"] = max(0,mysqli_affected_rows($db));
                    
                    if(!empty($duplicates[$item."_rent"])){
                        $counter_real[$item."_rent"] = $counter[$item."_rent"] - $duplicates[$item."_rent"];
                        $counter_real[$item."_rent"] = $counter_real[$item."_rent"]." ( Удалено дубликатов: ".$duplicates[$item."_rent"].")";
                    }
                    else $counter_real[$item."_rent"] = $counter[$item."_rent"];
                    
                    if(!empty($duplicates[$item."_sell"])){
                        $counter_real[$item."_sell"] = $counter[$item."_sell"] - $duplicates[$item."_sell"];
                        $counter_real[$item."_sell"] = $counter_real[$item."_sell"]." ( Удалено дубликатов: ".$duplicates[$item."_sell"].")";
                    }
                    else $counter_real[$item."_sell"] = $counter[$item."_sell"];
                    
                    $duplicates['total'] = $duplicates['total'] + $duplicates[$item."_rent"] + $duplicates[$item."_sell"];
                    
                } 
            }
        }
        
        //отправка отчета о вариантах
        if(!empty($counter['total'])){
            $total = $counter['total'];
            $mail_text .= "Обработано всего объектов:".$total;
            $mail_text .= "<br />Добавлено объектов: ".($counter['live_sell']+$counter['live_rent']+$counter['build']+$counter['country_sell']+$counter['commercial_sell']+$counter['country_rent']+$counter['commercial_rent'] - (!empty($duplicates['total'])?$duplicates['total']:"") - (!empty($counter_joint['total'])?$counter_joint['total']:""))."<br />
            - жилая (продажа): ".$counter_real['live_sell'].
                                 ($counter_joint['live_sell']>0?" (склеено объектов: ".$counter_joint['live_sell'].")":"").
                                 ($counter['live_sell_promo']>0? ", промо: ".$counter['live_sell_promo']:"").
                                 ($counter['live_sell_premium']>0? ", премиум: ".$counter['live_sell_premium']:"").
                                 ($counter['live_sell_elite']>0? ", элитная: ".$counter['live_sell_elite']:"")."<br />
            - жилая (аренда): ".$counter_real['live_rent'].
                                ($counter_joint['live_rent']>0?" (склеено объектов: ".$counter_joint['live_rent'].")":"").
                                ($counter['live_rent_promo']>0? ", промо: ".$counter['live_rent_promo']:"").
                                ($counter['live_rent_premium']>0? ", премиум: ".$counter['live_rent_premium']:"").
                                ($counter['live_rent_elite']>0? ", элитная: ".$counter['live_rent_elite']:"")."<br />
            - стройка: ".$counter_real['build'].
                        ($counter_joint['build']>0?" (склеено объектов: ".$counter_joint['build'].")":"").
                        ($counter['build_sell_promo']>0? ", промо: ".$counter['build_sell_promo']:"").
                        ($counter['build_sell_premium']>0? ", премиум: ".$counter['build_sell_premium']:"").
                        ($counter['build_sell_elite']>0? ", элитная: ".$counter['build_sell_elite']:"")."<br />
            - коммерческая (продажа): ".$counter_real['commercial_sell'].
                                        ($counter_joint['commercial_sell']>0?" (склеено объектов: ".$counter_joint['commercial_sell'].")":"").
                                        ($counter['commercial_sell_promo']>0? ", промо: ".$counter['commercial_sell_promo']:"").
                                        ($counter['commercial_sell_premium']>0? ", премиум: ".$counter['commercial_sell_premium']:"").
                                        ($counter['commercial_sell_elite']>0? ", элитная: ".$counter['commercial_sell_elite']:"")."<br />
            - коммерческая (аренда): ".$counter_real['commercial_rent'].
                                       ($counter_joint['commercial_rent']>0?" (склеено объектов: ".$counter_joint['commercial_rent'].")":"").
                                       ($counter['commercial_rent_promo']>0? ", промо: ".$counter['commercial_rent_promo']:"").
                                       ($counter['commercial_rent_premium']>0? ", премиум: ".$counter['commercial_rent_premium']:"").
                                       ($counter['commercial_rent_elite']>0? ", элитная: ".$counter['commercial_rent_elite']:"")."<br />
            - загородная (продажа): ".$counter_real['country_sell'].
                                      ($counter_joint['country_sell']>0?" (склеено объектов: ".$counter_joint['country_sell'].")":"").
                                      ($counter['country_sell_promo']>0? ", промо: ".$counter['country_sell_promo']:"").
                                      ($counter['country_sell_premium']>0? ", премиум: ".$counter['country_sell_premium']:"").
                                      ($counter['country_sell_elite']>0? ", элитная: ".$counter['country_sell_elite']:"")."<br />
            - загородная (аренда): ".$counter_real['country_rent'].
                                     ($counter_joint['country_rent']>0?" (склеено объектов: ".$counter_joint['country_rent'].")":"").
                                     ($counter['country_rent_promo']>0? ", промо: ".$counter['country_rent_promo']:"").
                                     ($counter['country_rent_premium']>0? ", премиум: ".$counter['country_rent_premium']:"").
                                     ($counter['country_rent_elite']>0? ", элитная: ".$counter['country_rent_elite']:"")."<br /><br />

            ";
        }
        $photos_text = '';
		

        //логирование ошибок
        if(!empty($errors_log)){
            $mail_text .= "<br /><br />При обработке файла возникли следующие ошибки:";
            if(!empty($errors_log['over_limit'])) $mail_text .= "<br /><br />Объекты сверх установленного лимита (не выгружены): ".$errors_log['over_limit']."<br />"; 
            $mail_text .= "
            ".(!empty($counter['live_sell_over_limit'])?"- жилая (продажа): ".$counter['live_sell_over_limit']."<br />":"")."
            ".(!empty($counter['live_sell_promo_over_limit'])?" - жилая (продажа) промо: ".$counter['live_sell_promo_over_limit']."<br />":"")."
            ".(!empty($counter['live_sell_premium_over_limit'])?" - жилая (продажа) премиум: ".$counter['live_sell_premium_over_limit']."<br />":"")."
            ".(!empty($counter['live_rent_over_limit'])?"- жилая (аренда): ".$counter['live_rent_over_limit']."<br />":"")."
            ".(!empty($counter['live_rent_promo_over_limit'])?" - жилая (аренда) промо: ".$counter['live_rent_promo_over_limit']."<br />":"")."
            ".(!empty($counter['live_rent_premium_over_limit'])?" - жилая (аренда) премиум: ".$counter['live_rent_premium_over_limit']."<br />":"")."
            ".(!empty($counter['build_over_limit'])?"- стройка: ".$counter['build_over_limit']."<br />":"")."
            ".(!empty($counter['build_sell_promo_over_limit'])?" - стройка промо: ".$counter['build_sell_promo_over_limit']."<br />":"")."
            ".(!empty($counter['build_sell_premium_over_limit'])?" - стройка премиум: ".$counter['build_sell_premium_over_limit']."<br />":"")."
            ".(!empty($counter['commercial_sell_over_limit'])?"- коммерческая (продажа): ".$counter['commercial_sell_over_limit']."<br />":"")."
            ".(!empty($counter['commercial_sell_promo_over_limit'])?" - коммерческая (продажа) промо: ".$counter['commercial_sell_promo_over_limit']."<br />":"")."
            ".(!empty($counter['commercial_sell_premium_over_limit'])?" - коммерческая (продажа) премиум: ".$counter['commercial_sell_premium_over_limit']."<br />":"")."
            ".(!empty($counter['commercial_rent_over_limit'])?"- коммерческая (аренда): ".$counter['commercial_rent_over_limit']."<br />":"")."
            ".(!empty($counter['commercial_rent_promo_over_limit'])?" - коммерческая (аренда) промо: ".$counter['commercial_rent_promo_over_limit']."<br />":"")."
            ".(!empty($counter['commercial_rent_premium_over_limit'])?" - коммерческая (аренда) премиум: ".$counter['commercial_rent_premium_over_limit']."<br />":"")."
            ".(!empty($counter['country_sell_over_limit'])?"- загородная (продажа): ".$counter['country_sell_over_limit']."<br />":"")."
            ".(!empty($counter['country_sell_promo_over_limit'])?" - загородная (продажа) промо: ".$counter['country_sell_promo_over_limit']."<br />":"")."
            ".(!empty($counter['country_sell_premium_over_limit'])?" - загородная (продажа) премиум: ".$counter['country_sell_premium_over_limit']."<br />":"")."
            ".(!empty($counter['country_rent_over_limit'])?"- загородная (аренда): ".$counter['country_rent_over_limit']."<br />":"")."
            ".(!empty($counter['country_rent_promo_over_limit'])?" - загородная (аренда) промо: ".$counter['country_rent_promo_over_limit']."<br />":"")."
            ".(!empty($counter['country_rent_premium_over_limit'])?" - загородная (аренда) премиум: ".$counter['country_rent_premium_over_limit']."<br />":"")."
            ";
            if(!empty($errors_log['fatal'])) $mail_text .= "<br /><br />".$errors_log['fatal']; 
            if(!empty($errors_log['moderation']))  {
                $mail_text .= "<br /><br /><strong>Не прошли модерацию: ".count($errors_log['moderation'])."</strong>";
                foreach($errors_log['moderation'] as $k=>$moderation) $mail_text .= "<br />external_id: <strong>".$k.'</strong>, статус: <i>'.$moderate_statuses[$moderation[1]]."</i>, значение: ".$moderation[0];
            }    
            if(!empty($errors_log['estate_type']))  {
                $mail_text .= "<br /><br /><strong>Не найден тип недвижимости: ".count($errors_log['estate_type'])."</strong>";
                foreach($errors_log['estate_type'] as $k=>$estate_id) $mail_text .= "<br />external_id: <strong>".$k.'</strong>, значение: <i>'.$estate_id;
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
    //если все в порядке с адресом и у агентства нет тарифа (то есть выгрузка скорее всего прошла), шлем письмо в компанию
    if(empty($agency['id_tarif']) && !empty($agency['email_service'])  && Validate::isEmail($agency['email_service'])) $mailer->AddAddress($agency['email_service']);     //отправка письма агентству
    
    $mailer->AddAddress('hitty@bsn.ru');
    $mailer->AddAddress('web@bsn.ru');
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
    $mailer->AddAddress('web@bsn.ru');
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
    $mailer->AddAddress('web@bsn.ru');
    $mailer->From = 'agregator@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Агрегатор BSN');
    // попытка отправить
    $mailer->Send();        
    echo $html;
    
    //отмечаем в базе, что работа с файлом агентства завершена
    $db->querys("UPDATE ".$sys_tables['agencies']." SET xml_file_processing = 0,xml_last_size = ".$xml_size." WHERE id = ".$agency_xml_info['id']);
}
?>