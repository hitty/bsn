#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
$root = 'D:\\server\\www\\bsn.my\\trunk\\';
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  (крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
setlocale(LC_ALL, 'rus');
/**
* Обработка новых объектов
*/
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/functions.php');          // функции  (модуля
Session::Init();
Request::Init();
Cookie::Init(); 
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("set lc_time_names = 'ru_RU'");
require_once('includes/class.email.php');
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
require_once('includes/class.photos.php');     // Photos (работа с графикой)
require_once('includes/class.moderation.php'); // Moderation (процедура модерации)
require_once('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
//выгрузка агентства по времени
$where = $sys_tables['processes'].".id = 1";;
//режим поулчения фоток 
$multi_download = true;
//доп.загрузка с переданным параметром ID админа агентства
//локально
//доп.загрузка с переданным параметром ID админа агентства
$argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;
$where = $sys_tables['processes'].".status = 1 AND ".$sys_tables['processes'].".last_action < NOW() - INTERVAL 2 MINUTE AND  ".$sys_tables['xml_parse'].".id > 0";
if(DEBUG) $where = $sys_tables['processes'].".status = 1 AND  ".$sys_tables['xml_parse'].".id > 0";
if(!empty($argc)) $where = $sys_tables['processes'].".id_agency = ".$argc;   
$base_memory_usage = memory_get_usage();
$process = $db->fetch("         SELECT          
                                      ".$sys_tables['users'].".id AS id_user,
                                      ".$sys_tables['users'].".email AS user_email,
                                      ".$sys_tables['users'].".xml_notification,
                                      CONCAT(".$sys_tables['users'].".name, ' ',  ".$sys_tables['users'].".lastname) AS user_name,
                                      ".$sys_tables['managers'].".name as manager_name,
                                      ".$sys_tables['managers'].".email as manager_email,
                                      ".$sys_tables['agencies'].".*,
                                      xml_link,
                                      xml_status,
                                      xml_alias,
                                      ".$sys_tables['processes'].".id as id_process,
                                      ".$sys_tables['processes'].".total_amount,
                                      ".$sys_tables['processes'].".live_rent,
                                      ".$sys_tables['processes'].".live_sell,
                                      ".$sys_tables['processes'].".build,
                                      ".$sys_tables['processes'].".commercial_rent,
                                      ".$sys_tables['processes'].".commercial_sell,
                                      ".$sys_tables['processes'].".country_rent,
                                      ".$sys_tables['processes'].".country_sell,
                                      ".$sys_tables['processes'].".live_rent_promo,
                                      ".$sys_tables['processes'].".live_sell_promo,
                                      ".$sys_tables['processes'].".build_promo,
                                      ".$sys_tables['processes'].".commercial_rent_promo,
                                      ".$sys_tables['processes'].".commercial_sell_promo,
                                      ".$sys_tables['processes'].".country_rent_promo,
                                      ".$sys_tables['processes'].".country_sell_promo,
                                      ".$sys_tables['processes'].".live_rent_premium,
                                      ".$sys_tables['processes'].".live_sell_premium,
                                      ".$sys_tables['processes'].".build_premium,
                                      ".$sys_tables['processes'].".commercial_rent_premium,
                                      ".$sys_tables['processes'].".commercial_sell_premium,
                                      ".$sys_tables['processes'].".country_rent_premium,
                                      ".$sys_tables['processes'].".country_sell_premium,
                                      ".$sys_tables['processes'].".live_rent_vip,
                                      ".$sys_tables['processes'].".live_sell_vip,
                                      ".$sys_tables['processes'].".build_vip,
                                      ".$sys_tables['processes'].".commercial_rent_vip,
                                      ".$sys_tables['processes'].".commercial_sell_vip,
                                      ".$sys_tables['processes'].".country_rent_vip,
                                      ".$sys_tables['processes'].".country_sell_vip,
                                      (".$sys_tables['processes'].".not_sent_report = 1) as not_sent_report
                               FROM ".$sys_tables['agencies']."
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency AND ".$sys_tables['users'].".agency_admin = 1           
                               LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                               LEFT JOIN ".$sys_tables['processes']." ON ".$sys_tables['processes'].".id_agency = ".$sys_tables['agencies'].".id
                               LEFT JOIN ".$sys_tables['xml_parse']." ON ".$sys_tables['xml_parse'].".id_agency = ".$sys_tables['processes'].".id_agency
                               WHERE ".$where."
                               GROUP BY ".$sys_tables['agencies'].".id
                               ORDER BY ".$sys_tables['processes'].". id DESC"
);
$agency = $db->fetch("         SELECT          
                                      ".$sys_tables['users'].".id AS id_user,
                                      ".$sys_tables['users'].".email AS user_email,
                                      ".$sys_tables['users'].".xml_notification,
                                      CONCAT(".$sys_tables['users'].".name, ' ',  ".$sys_tables['users'].".lastname) AS user_name,
                                      ".$sys_tables['managers'].".name as manager_name,
                                      ".$sys_tables['managers'].".email as manager_email,
                                      ".$sys_tables['agencies'].".*,
                                      xml_link,
                                      xml_status,
                                      xml_alias
                               FROM ".$sys_tables['agencies']."
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency  AND ".$sys_tables['users'].".agency_admin = 1           
                               LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                               WHERE ".$sys_tables['agencies'].".id = ".$process['id']);
echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n"; 
echo $db->last_query; 
if(!empty($process)){
    $id_user = $process['id_user'];
    //запись всех ошибок в лог
    $error_log = ROOT_PATH.'/cron/robot/logs/'.$id_user."_".createCHPUTitle($process['title'])."".'-'.date('d.m.Y').'.error.log';
    file_put_contents($error_log,'');
    ini_set('error_log', $error_log);
    ini_set('log_errors', 'On');
    $total = $total_added = $total_errors = 0;
    $success = true;
    //рекламное агентство
    $advert_agency = $process['activity']%pow(2,2)>=pow(2,1)?true:false;
    // ошибки
    $errors_log = array('moderation'=>array(), 'estate_type'=>array(), 'img'=>array());  
    //типы сделок
    $rent_titles = array(1=>'аренда', 2=>'продажа'); 
    //обработка строк
    $process_id = $process['id_process'];
    //счетчик "склеенных" объектов
    $counter_joint = array('live_sell'=>0,'live_rent'=>0,'commercial_sell'=>0,'commercial_rent'=>0,'build_sell_new'=>0,'country_sell'=>0,'country_rent'=>0,'total'=>0,
        'live_sell_new'=>0,'live_rent_new'=>0,'commercial_sell_new'=>0,'commercial_rent_new'=>0,'build_new'=>0,'country_sell_new'=>0,'country_rent_new'=>0,'total_new'=>0
    );
    //счетчик объектов не попавших из-за лимита
    $counter_limit = array('live_sell'=>0,'live_rent'=>0,'commercial_sell'=>0,'commercial_rent'=>0,'build'=>0,'country_sell'=>0,'country_rent'=>0);
    //невыгруженные ЖК, КП и БЦ
    $estate_complexes_log = array();
    //счетчики объектов
    $counter = array(
                     'live_sell'=>$process['live_sell'],
                     'live_rent'=>$process['live_rent'],             
                     'commercial_sell'=>$process['commercial_sell'],            
                     'commercial_rent'=>$process['commercial_rent'],          
                     'build'=>$process['build'],            
                     'country_sell'=>$process['country_sell'],            
                     'country_rent'=>$process['country_rent'], 
                     'live_sell_promo'=>$process['live_sell_promo'],      
                     'live_rent_promo'=>$process['live_rent_promo'],       
                     'commercial_sell_promo'=>$process['commercial_sell_promo'],      
                     'commercial_rent_promo'=>$process['commercial_rent_promo'],    
                     'build_sell_promo'=>$process['build_promo'],      
                     'country_sell_promo'=>$process['country_sell_promo'],      
                     'country_rent_promo'=>$process['country_rent_promo'], 
                     'live_sell_premium'=>$process['live_sell_premium'],    
                     'live_rent_premium'=>$process['live_rent_premium'],     
                     'commercial_sell_premium'=>$process['commercial_sell_premium'],    
                     'commercial_rent_premium'=>$process['commercial_rent_premium'],  
                     'build_sell_premium'=>$process['build_premium'],    
                     'country_sell_premium'=>$process['country_sell_premium'],    
                     'country_rent_premium'=>$process['country_rent_premium'], 
                     'live_sell_vip'=>$process['live_sell_vip'],        
                     'live_rent_vip'=>$process['live_rent_vip'],         
                     'commercial_sell_vip'=>$process['commercial_sell_vip'],      
                     'commercial_rent_vip'=>$process['commercial_rent_vip'],    
                     'build_sell_vip'=>$process['build_vip'],      
                     'country_sell_vip'=>$process['country_sell_vip'],      
                     'country_rent_vip'=>$process['country_rent_vip'],
                     'total'=>0
    );
    $count = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['xml_parse']." WHERE id_agency = ? ", $process['id'])['cnt'];
    $limit = 10;
    do{
        $field_values = $db->fetchall("SELECT * FROM  ".$sys_tables['xml_parse']." WHERE id_agency = ? ORDER BY id ASC LIMIT ".$limit, false, $process['id']);
        echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n"; 
        foreach($field_values as $key=>$item){
            $file_type = $item['file_type'];
            $values = unserialize($item['xml_values']);
            $xml_parse_id = $item['id'];
            unset($item);
            $main_photo = '';
            //сумма всех итераций
            ++$counter['total'];
            //EIP
            switch($file_type){
                case 'EIP':
                break;
            }
            switch($file_type){
                case 'BN':
                    $robot = new BNXmlRobot($id_user); 
                    $info_source = 2;
                    break;
                case 'EIP':
                    $robot = new EIPXmlRobot($id_user); 
                    $info_source = 3;
                    break;
                case 'Yandex':
                    $robot = new YandexRXmlRobot($id_user); 
                    $info_source = 8;
                    break;
                case 'Gdeetotdom':
                    $robot = new GdeetotXmlRobot($id_user); 
                    $info_source = 7;
                    break;
                case 'Avito':
                    $robot = new AvitoRXmlRobot($id_user); 
                    $info_source = 9;
                    break;
                case 'Cian':
                    $robot = new CianXmlRobot($id_user);
                    $info_source = 10;
                    break;
            }
            echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n"; 
            $fields = $robot->getConvertedFields($values, $process);
            echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n"; 
            if(!empty($fields)){
                //проверка лимита
                $deal_type = $robot->estate_type == 'build' ? '' : ($fields['rent'] == 1 ? '_rent' : '_sell');
                $check_limit = ($robot->estate_type.$deal_type == 'live_rent' && $counter[$robot->estate_type.$deal_type] < $process[$robot->estate_type.$deal_type.'_objects']) || ($robot->estate_type.$deal_type != 'live_rent' && ( ($process['id_tarif']==7 && $process[$robot->estate_type.$deal_type.'_objects'] == 0) || ($counter[$robot->estate_type.$deal_type] < $process[$robot->estate_type.$deal_type.'_objects']))); 
                //отсечение лимитов
                $fields['external_id'] = (empty($fields['external_id'])?0:$fields['external_id']);
                echo "\n".$check_limit." : ".$fields['external_id']."\n";
                if(empty($check_limit)) $counter_limit[$robot->estate_type.$deal_type]++;
                else { 
                    //получение статуса модерации объекта
                    $moderate = new Moderation($robot->estate_type,0);
                    $moderate_status = $moderate->getModerateStatus($fields);
                    $fields['hash'] = $moderate->makeHash();
                    //для непрошедших модерацию
                    if($moderate_status>1){
                        $fields['published'] = 3; //на модерации
                        $errors_log['moderation'][$fields['external_id']] = array(($moderate_status!=4?(!empty($fields['cost'])?$fields['cost'].', ':'').$rent_titles[$fields['rent']]:$fields['txt_addr']),$moderate_status); 
                    } else $fields['published'] = 1;
                    //массив с фото
                    $photos = array();
                    //префикс для фото
                    $prefix = '';
                    //поиск ранее загруженного объекта в основной таблице
                    $check_object = $db->fetch("SELECT `id`, `id_main_photo`,`published`,id_promotion 
                                                FROM ".$sys_tables[($robot->estate_type)]." 
                                                WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",
                                            $fields['external_id'], $id_user, $info_source
                    );
                    
                    $deal_type_title = ($robot->estate_type!='build'?($fields['rent']==2?'_sell':'_rent'):"");
                    
                    if(!empty($check_object)) {
                        $fields['id'] = $check_object['id'];
                        
                        //чтобы не перетирался статус объектов прикрепленных к акциям
                        if($check_object['id_promotion'] > 0) $fields['status'] = 7;
                        
                        //updat'им данные
                        $res = $db->updateFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                        if($check_object['published'] == 1){
                            ++$counter_joint[$robot->estate_type.($robot->estate_type!='build'?($fields['rent']==2?'_sell':'_rent'):"")];
                            ++$counter_joint['total'];
                            //вычитаем (лимита склееные
                            --$counter[$robot->estate_type.$deal_type_title];
                            $db->querys("UPDATE ".$sys_tables['processes']." SET ".$robot->estate_type.$deal_type_title." = ".$robot->estate_type.$deal_type_title." - 1 WHERE id = ?", $process_id);
                        } 
                        
                        //определение списка фотографий, которых нет в БД
                        if(!empty($fields['images'])) list($photos['in'],$photos['out']) = $robot->getPhotoList($fields['images'], $check_object['id']);
                        //удаление фоток (из базы и с сервера), которые не вошли в xml
                        $photos_list_in = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                         WHERE `id_parent` = ".$check_object['id']."
                                                         ".(!empty($photos['in'])?" AND `external_img_src` IN (".implode(',', $photos['in']).")":""),'id');
                        $photos_list = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                      WHERE `id_parent` = ".$check_object['id']."
                                                      ".(!empty($photos_list_in)?" AND `id` NOT IN (".implode(',', array_keys($photos_list_in)).")":""),'id');
                        
                        echo '197';print_r($photos);
                        if(!empty($photos_list)){
                            foreach($photos_list as $k => $val) Photos::Delete($robot->estate_type,$val['id']);
                            if(!empty($photos_list['in'])) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".implode(',', array_keys($photos_list)).")");
                        }
                        $inserted_id = $check_object['id'];

                        //если объект на модерации
                        if($moderate_status>1){
                            //проверяем его наличие в таблице new
                            $check_object_new = $db->fetch("SELECT * FROM ".$sys_tables[($robot->estate_type).'_new']." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",
                                                    $fields['external_id'], $id_user, $info_source);
                            $fields['id_object'] = $fields['id'];
                            unset($fields['id']);
                            $fields['id_moderate_status'] = $moderate_status;
                            //если есть - update
                            if(!empty($check_object_new)){
                                $fields['id'] = $check_object_new['id'];
                                //updat'им данные
                                $fields['date_in']= date('Y-m-d H:i:s');
                                $fields['id_moderate_status'] = $moderate_status;
                                $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                                
                                //$res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id_object');
                                
                                if($check_object_new['published'] == 1){
                                    ++$counter_joint[$robot->estate_type.($robot->estate_type!='build'?($fields['rent']==2?'_sell':'_rent'):"")."_new"];
                                    ++$counter_joint['total_new'];
                                    //вычитаем (лимита склееные
                                    --$counter[$robot->estate_type.$deal_type_title];
                                    $db->querys("UPDATE ".$sys_tables['processes']." SET ".$robot->estate_type.$deal_type_title." = ".$robot->estate_type.$deal_type_title." - 1 WHERE id = ?", $process_id);
                                }
                                
                            } 
                            //еси нет - вставка
                            else $res = $db->insertFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id_object');
                        }                       
                    }
                    else 
                    {
                        //поиск ранее загруженного объекта в таблице _new
                        $check_object_new = $db->fetch("SELECT * FROM ".$sys_tables[($robot->estate_type).'_new']." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",
                                                $fields['external_id'], $id_user, $info_source
                        );
                        if(!empty($check_object_new)) {
                            $fields['id'] = $check_object_new['id'];
                            //updat'им данные
                            $fields['date_in']= date('Y-m-d H:i:s');
                            $fields['id_moderate_status'] = $moderate_status;
                            $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                            
                            if($check_object_new['published'] == 1){
                                ++$counter_joint[$robot->estate_type.($robot->estate_type!='build'?($fields['rent']==2?'_sell':'_rent'):"")."_new"];
                                ++$counter_joint['total_new'];
                                //вычитаем из лимита склееные
                                --$counter[$robot->estate_type.$deal_type_title];
                                $db->querys("UPDATE ".$sys_tables['processes']." SET ".$robot->estate_type.$deal_type_title." = ".$robot->estate_type.$deal_type_title." - 1 WHERE id = ?", $process_id);
                            }
                            
                            //определение списка фотографий, которых нет в БД
                            if(!empty($fields['images'])) list($photos['in'],$photos['out']) = $robot->getPhotoList($fields['images'], $check_object_new['id'],'_new');
                            echo '245';print_r($photos);//удаление фоток (из базы и с сервера)
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
                            
                            if($advert_agency && $moderate_status==1){ // для рекламных агентств прошедших модерацию - нет проверки на склейку
                                $fields['date_change']= date('Y-m-d H:i:s');
                                $res = $db->insertFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                            } else {
                                $fields['date_in']= date('Y-m-d H:i:s');
                                $prefix = '_new';
                                $res = $db->insertFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                            }
                            if(!empty($fields['images'])) list($photos['in'],$photos['out']) = $robot->getPhotoList($fields['images'], $check_object_new['id'],'_new');
                            echo '268';print_r($photos);$inserted_id = $db->insert_id;
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
                    
                    $has_main = false;

                    //загрузка фотографий
                    print_r($photos);                  
                    if(!empty($photos['out']) && $inserted_id>0) {
                        //режим скачивания картинок
                        if(!empty($multi_download)){
                            $external_img_sources = Photos::MultiDownload($photos['out'], ROOT_PATH.'/'.Config::$values['img_folders'][$robot->estate_type].'/');
                            foreach($external_img_sources as $k=>$img) {
                                print_r($img);
                                $photo_add_result = Photos::Add($robot->estate_type, $inserted_id, $prefix, $img['external_img_src'], $img['filename'], false, false, false, Config::Get('watermark_src'));
                                if(!is_array($photo_add_result)) $errors_log['img'][] = $img['external_img_src'];
                            }
                        } 
                        if(empty($multi_download)){
                            foreach($photos['out'] as $k=>$img) {
                                print_r($img);
                                $photo_add_result = Photos::Add($robot->estate_type, $inserted_id, $prefix, $img['external_img_src']);
                                if(!is_array($photo_add_result)) $errors_log['img'][] = $img['external_img_src'];
                            }
                            
                        }
                    }
                    //если нет главной фотки - первая загруженная главная
                    if(!empty($fields['main_photo']) ) {
                         Photos::setMain($robot->estate_type, $inserted_id,null,$prefix,$fields['main_photo']);
                    } else {
                        $photo_id = $db->fetch("SELECT id FROM ".$sys_tables[$robot->estate_type."_photos"]." WHERE id_parent".$prefix."=? ORDER BY id ASC",$inserted_id);
                        if(!empty($photo_id)) $db->querys("UPDATE ".$sys_tables[$robot->estate_type.$prefix]." SET id_main_photo = ? WHERE id = ?",$photo_id['id'],$inserted_id);
                    }      
                    //модерация новых объектов
                    if($prefix=='_new') {
                        $moderate = new Moderation($robot->estate_type,$inserted_id);
                        $moderate->checkObject();
                    }
                    
                    //счетчик кол-ва вариантов
                    if($moderate_status==1){
                        ++$counter[$robot->estate_type.$deal_type];
                        $db->querys("UPDATE ".$sys_tables['processes']." SET ".$robot->estate_type.$deal_type." = ".$robot->estate_type.$deal_type." + 1, current_amount = current_amount + 1 WHERE id = ?", $process_id);
                    } 
                    elseif(!empty($fields['vip']) && $fields['vip']==1){
                        --$counter[$robot->estate_type.$deal_type.'_vip'];
                        $db->querys("UPDATE ".$sys_tables['processes']." SET ".$robot->estate_type.$deal_type."_vip = ".$robot->estate_type.$deal_type."_vip - 1 WHERE id = ?", $process_id);
                    }
                        
                } // отсечение лимитов
                //кол-во обработанных объектов
                $db->querys("UPDATE ".$sys_tables['processes']." SET last_action = NOW() WHERE id = ?", $process_id);
                $db->querys("DELETE FROM ".$sys_tables['xml_parse']." WHERE id = ?", $xml_parse_id);
            }else{
                $db->querys("DELETE FROM ".$sys_tables['xml_parse']." WHERE id = ?", $xml_parse_id);
                
            }
        }
        unset($field_values);
        $count = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['xml_parse']." WHERE id_agency = ?", $process['id'])['cnt'];
    } while($count > 0);

    // --------------------------------------
    $mail_text = '';
    //отправка отчета о вариантах
    if(!empty($counter['total'])){
        $total = $process['total_amount'];
        $total_added = $counter['live_sell']+$counter['live_rent']+$counter['build']+$counter['country_sell']+$counter['commercial_sell']+$counter['country_rent']+$counter['commercial_rent'];
        $mail_text .= "\nОбработано всего объектов: ".$counter['total']."\n";
        $mail_text .= "\nДобавлено объектов: ".$total_added."
        - жилая (продажа): ".$counter['live_sell'].
                                         ($counter_joint['live_sell']>0 || $counter_limit['live_sell']>0 ? " (".
                                             ($counter_joint['live_sell']>0?" склеено: ".$counter_joint['live_sell'].($counter_limit['live_sell']>0?", ":"") :"").
                                             ($counter_limit['live_sell']>0? $counter_limit['live_sell']." не выгружено":"").
                                         ")" : "" ).
                                         ($counter['live_sell_promo']>0? ", промо: ".$counter['live_sell_promo']:"").
                                         ($counter['live_sell_premium']>0? ", премиум: ".$counter['live_sell_premium']:"").
                                         ($counter['live_sell_vip']>0? ", VIP: ".$counter['live_sell_vip']:"")."
        - жилая (аренда): ".$counter['live_rent'].
                             ($counter_joint['live_rent']>0 || $counter_limit['live_rent']>0 ? " (".
                                 ($counter_joint['live_rent']>0?" склеено: ".$counter_joint['live_rent'].($counter_limit['live_rent']>0?", ":"") :"").
                                 ($counter_limit['live_rent']>0? $counter_limit['live_rent']." не выгружено":"").
                             ")" : "" ).
                           ($counter['live_rent_promo']>0? ", промо: ".$counter['live_rent_promo']:"").
                           ($counter['live_rent_premium']>0? ", премиум: ".$counter['live_rent_premium']:"").
                           ($counter['live_rent_vip']>0? ", VIP: ".$counter['live_rent_vip']:"")."
        - стройка: ".$counter['build'].
                             ($counter_joint['build_sell']>0 || $counter_limit['build']>0 ? " (".
                                 ($counter_joint['build_sell']>0?" склеено: ".$counter_joint['build_sell'].($counter_limit['build']>0?", ":"") :"").
                                 ($counter_limit['build']>0? $counter_limit['build']." не выгружено":"").
                             ")" : "" ).
                            ($counter['build_promo']>0? ", промо: ".$counter['build_promo']:"").
                            ($counter['build_premium']>0? ", премиум: ".$counter['build_premium']:"").
                            ($counter['build_vip']>0? ", VIP: ".$counter['build_vip']:"")."
        - коммерческая (продажа): ".$counter['commercial_sell'].
                             ($counter_joint['commercial_sell']>0 || $counter_limit['commercial_sell']>0 ? " (".
                                 ($counter_joint['commercial_sell']>0?" склеено: ".$counter_joint['commercial_sell'].($counter_limit['commercial_sell']>0?", ":"") :"").
                                 ($counter_limit['commercial_sell']>0? $counter_limit['commercial_sell']." не выгружено":"").
                             ")" : "" ).
                               ($counter['commercial_sell_promo']>0? ", промо: ".$counter['commercial_sell_promo']:"").
                               ($counter['commercial_sell_premium']>0? ", премиум: ".$counter['commercial_sell_premium']:"").
                               ($counter['commercial_sell_vip']>0? ", VIP: ".$counter['commercial_sell_vip']:"")."
        - коммерческая (аренда): ".$counter['commercial_rent'].
                             ($counter_joint['commercial_rent']>0 || $counter_limit['commercial_rent']>0 ? " (".
                                 ($counter_joint['commercial_rent']>0?" склеено: ".$counter_joint['commercial_rent'].($counter_limit['commercial_rent']>0?", ":"") :"").
                                 ($counter_limit['commercial_rent']>0? $counter_limit['commercial_rent']." не выгружено":"").
                             ")" : "" ).
                             ($counter['commercial_rent_promo']>0? ", промо: ".$counter['commercial_rent_promo']:"").
                             ($counter['commercial_rent_premium']>0? ", премиум: ".$counter['commercial_rent_premium']:"").
                             ($counter['commercial_rent_vip']>0? ", VIP: ".$counter['commercial_rent_vip']:"")."
        - загородная (продажа): ".$counter['country_sell'].
                                 ($counter_joint['country_sell']>0 || $counter_limit['country_sell']>0 ? " (".
                                     ($counter_joint['country_sell']>0?" склеено: ".$counter_joint['country_sell'].($counter_limit['country_sell']>0?", ":"") :"").
                                     ($counter_limit['country_sell']>0? $counter_limit['country_sell']." не выгружено":"").
                                 ")" : "" ).
                               ($counter['country_sell_promo']>0? ", промо: ".$counter['country_sell_promo']:"").
                               ($counter['country_sell_premium']>0? ", премиум: ".$counter['country_sell_premium']:"").
                               ($counter['country_sell_vip']>0? ", VIP: ".$counter['country_sell_vip']:"")."
        - загородная (аренда): ".$counter['country_rent'].
                             ($counter_joint['country_rent']>0 || $counter_limit['country_rent']>0 ? " (".
                                 ($counter_joint['country_rent']>0?" склеено: ".$counter_joint['country_rent'].($counter_limit['country_rent']>0?", ":"") :"").
                                 ($counter_limit['country_rent']>0? $counter_limit['country_rent']." не выгружено":"").
                             ")" : "" ).
                             ($counter['country_rent_promo']>0? ", промо: ".$counter['country_rent_promo']:"").
                             ($counter['country_rent_premium']>0? ", премиум: ".$counter['country_rent_premium']:"").
                             ($counter['country_rent_vip']>0? ", VIP: ".$counter['country_rent_vip']:"");
    }

    $photos_text = '';
    $total_errors = ( !empty($errors_log['moderation']) ? count($errors_log['moderation']) : 0 ) + (!empty($errors_log['estate_type']) ? count($errors_log['estate_type']) : 0 ) + ( !empty($errors_log['img']) ? count($errors_log['img']) : 0);
    //логирование ошибок
    if($total_errors > 0){
        $mail_text .= "\n\nПри обработке файла возникли следующие ошибки:\n";
        if(!empty($errors_log['moderation']))  {
            $mail_text .= "\n<b>Не прошли модерацию: (".count($errors_log['moderation']).")</b>\n";        
            foreach($errors_log['moderation'] as $k=>$moderation) $mail_text .= "external_id: <b>".$k.'</b>, статус: <i>'.$moderate_statuses[$moderation[1]]."</i>".(!empty($moderation[0])?", значение: ".$moderation[0]:"")."\n";
        }    
        if(!empty($errors_log['estate_type']))  {
            $mail_text .= "\n<b>Не найден тип недвижимости: (".count($errors_log['estate_type']).")</b>\n";
            foreach($errors_log['estate_type'] as $k=>$estate_id) $mail_text .= "external_id: <b>".$k.'</b>'.(!empty($estate_id)?", значение: ".$estate_id:"")."</i>\n";
        }    
        if(!empty($errors_log['img'])){ //ошибки загрузки фото
            $mail_text .= "\nНезагруженные фотографии объектов (".count($errors_log['img'])."):\n";
            foreach($errors_log['img'] as $k=>$img) $mail_text .= "".$img."\n";
        }
    }
                       
    //процесс окончен
    $db->querys("UPDATE ".$sys_tables['processes']." SET status = ?, total_added = ?, total_errors = ?, full_log = CONCAT (full_log,'\n',log,'\n','".$mail_text."','\n','___________________________________',''), log='', datetime_end = NOW() WHERE id = ?", 2, $total_added, $total_errors, $process_id);

    //если были ошибки выполнения скрипта
    if(filesize($error_log)>10){
        $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
        $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
        $error_log_text .= '</font>';
    } else $error_log_text = "";
    Response::SetArray('agency', $process);
    Response::SetInteger('process_id', $process_id);

    //отправка письма на web@bsn
    $admin_mailer = new EMailer('mail');
    $mail_text = "Обработка объектов агентства ".$process['title']."<br /><br />".$mail_text;
    if(!empty($error_text)) $mail_text = "\n".$error_text."\n\n".$mail_text;
    $html = iconv('UTF-8', $admin_mailer->CharSet, "ID пользователя: ".$process['id_user'].", ".$mail_text);
    //$html = iconv('UTF-8', $admin_mailer->CharSet, "ID пользователя: ".$process['id_user'].", ".$mail_text.$error_log_text);
    // параметры письма
    $admin_mailer->Subject = iconv('UTF-8', $admin_mailer->CharSet, !empty($file_type) ? 'Обработка формата '.$file_type.' XML. '.date('Y-m-d H:i:s') : date('Y-m-d H:i:s'));
    $admin_mailer->Body = nl2br($html);
    $admin_mailer->AltBody = nl2br($html);
    $admin_mailer->IsHTML(true);
    $admin_mailer->AddAddress('scald@bsn.ru');
    $admin_mailer->AddAddress('hitty@bsn.ru');
    $admin_mailer->From = 'bsnxml@bsn.ru';
    $admin_mailer->FromName = iconv('UTF-8', $admin_mailer->CharSet,'Парсинг '.(!empty($file_type) ? $file_type : '').' XML файла');
    // попытка отправить
    $admin_mailer->Send();        


    if(!$process['not_sent_report']){
        $mailer = new EMailer('mail');
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

        if(!empty($process['user_email']) && Validate::isEmail($process['user_email']) ) $mailer->AddAddress($process['user_email']);     //отправка письма ответственному менеджеру
        //если у агентства не установлен тариф и все в порядке с адресом, и стоит галочка оповещений, отправляем письмо
        if(!empty($process['email_service']) && $process['xml_notification'] == 1 && Validate::isEmail($process['email_service'])) $mailer->AddAddress($process['email_service']);     //отправка письма агентству

        $mailer->AddAddress('hitty@bsn.ru');
        $mailer->AddAddress('hitty@bsn.ru');
        $mailer->From = 'xml_parser@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'XML парсер BSN.ru');
        // попытка отправить
        $mailer->Send();        

        //отправка письма менеджеру
        if(!empty($process['manager_email'])){
            $manager_mailer = new EMailer('mail');
            Response::SetArray('agency', $process);
            $eml_tpl = new Template('parse.xml.manager.notification.html', 'cron/robot/');
            // перевод письма в кодировку мейлера   
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $manager_mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $manager_mailer->Body = $html;
            $manager_mailer->IsHTML(true);

            $manager_mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Отчет о загрузке объектов агентства ".$process['title']." ID ".$process['id_user']." от ".$report['normal_date']." ".$report['normal_date_start']." > ".$report['normal_date_end']);

            $manager_mailer->AddAddress($process['manager_email']);     //отправка письма ответственному менеджеру
            $mailer->AddAddress('hitty@bsn.ru');
            $manager_mailer->From = 'xml_parser@bsn.ru';
            $manager_mailer->FromName = iconv('UTF-8', $manager_mailer->CharSet,'XML парсер BSN.ru');
            // попытка отправить
            $manager_mailer->Send();      
        }
    } 
    //отправка письма менеджеру от ненайденных комплексах
    if(!empty($estate_complexes_log)){
        
        $manager_mailer = new EMailer('mail');
        $mail_text = "Ненайденные комплексы агентства ".$process['title']."<br /><br />".implode("<br />", $estate_complexes_log);
        $html = iconv('UTF-8', $manager_mailer->CharSet, "ID пользователя: ".$process['id_user'].", ".$mail_text.$error_log_text);
        // параметры письма
        $manager_mailer->Subject = iconv('UTF-8', $manager_mailer->CharSet, 'Ненайденные комплексы агентства '.$process['title']);
        $manager_mailer->Body = nl2br($html);
        $manager_mailer->AltBody = nl2br($html);
        $manager_mailer->IsHTML(true);
        $manager_mailer->AddAddress($process['manager_email']);
        $manager_mailer->From = 'xml_parser@bsn.ru';
        $manager_mailer->FromName = iconv('UTF-8', $manager_mailer->CharSet,'XML парсер BSN.ru');
        // попытка отправить
        $manager_mailer->Send();  
    }   
}
?>