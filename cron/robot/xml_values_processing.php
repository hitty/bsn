#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SCRIPT_NAME']) && preg_match('/.+\.int/i', $_SERVER['SCRIPT_NAME']) || !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int/i', $_SERVER['SERVER_NAME']) ? true : false);
define('TEST_MODE', !empty($_SERVER['SCRIPT_FILENAME']) && preg_match('/test\.bsn\.ru/sui', $_SERVER['SCRIPT_FILENAME']) ? true : false);

/** @var TYPE_NAME $root */
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
/**
* Обработка новых объектов
*/
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
$db->querys("set names ".Config::$values['mysql_remote']['charset']);

$db->querys("set lc_time_names = 'ru_RU'");
require_once('includes/class.host.php');
require_once('includes/class.email.php');
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;     // Photos (работа с графикой)
require_once('includes/class.moderation.php'); // Moderation (процедура модерации)
require_once('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array
require_once("includes/class.unisender.php");

//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
//выгрузка агентства по времени
$where = $sys_tables['processes'].".id = 1";;
//доп.загрузка с переданным параметром ID админа агентства
//локально
//доп.загрузка с переданным параметром ID админа агентства
$argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;
$where = $sys_tables['processes'].".status = 1 AND ".$sys_tables['processes'].".last_action < NOW() - INTERVAL 5 MINUTE AND  ".$sys_tables['xml_parse'].".id > 0";
if(DEBUG_MODE) $where = $sys_tables['processes'].".status = 1 AND  ".$sys_tables['xml_parse'].".id > 0 AND type IN (1,2)";
if(!empty($argc)) $where = $sys_tables['processes'].".id_agency = ".$argc;   
//$where = $sys_tables['processes'].".id_agency = 330";   
$base_memory_usage = memory_get_usage();
echo '; DEBUG: ' . DEBUG_MODE . ';';
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
                                      ".$sys_tables['processes'].".sent_report
                               FROM ".$sys_tables['agencies']."
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency AND ".$sys_tables['users'].".agency_admin = 1           
                               LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                               LEFT JOIN ".$sys_tables['processes']." ON ".$sys_tables['processes'].".id_agency = ".$sys_tables['agencies'].".id
                               LEFT JOIN ".$sys_tables['xml_parse']." ON ".$sys_tables['xml_parse'].".id_agency = ".$sys_tables['processes'].".id_agency
                               WHERE ".$where."
                               GROUP BY ".$sys_tables['agencies'].".id
                               ORDER BY ".$sys_tables['processes'].". id DESC"
);
if(empty($process['id'])) die('113');
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
print_r($process) ;
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
    
    $errors_log = array(
        'external_id'   =>  array(), 
        'address'       =>  array(), 
        'moderation'    =>  array(), 
        'estate_type'   =>  array(), 
        'img'           =>  array(), 
        'rooms'         =>  array(), 
        'rooms_square'  =>  array() 
    );  
    //типы сделок
    $rent_titles = array(1=>'аренда', 2=>'продажа'); 
    //обработка строк
    $process_id = $process['id_process'];
    //счетчик "склеенных" объектов
    $counter_nophoto = $counter_joint = array('live_sell' => 0,
                           'live_rent' => 0,
                           'commercial_sell' => 0,
                           'commercial_rent' => 0,
                           'build_sell_new' => 0,
                           'country_sell' => 0,
                           'country_rent' => 0,
                           'total' => 0,
                           'live_sell_new' => 0,
                           'live_rent_new' => 0,
                           'commercial_sell_new' => 0,
                           'commercial_rent_new' => 0,
                           'build_new' => 0,
                           'country_sell_new' => 0,
                           'country_rent_new' => 0,
                           'total_new' => 0
                          );
    $nophoto_ids = array();
    $nophoto_objects = false;
    //счетчик объектов не попавших из-за лимита
    $counter_limit = array('live_sell' => 0,'live_rent' => 0,'commercial_sell' => 0,'commercial_rent' => 0,'build' => 0,'country_sell' => 0,'country_rent' => 0);
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
                     'build_promo'=>$process['build_promo'],      
                     'country_sell_promo'=>$process['country_sell_promo'],      
                     'country_rent_promo'=>$process['country_rent_promo'], 
                     'live_sell_premium'=>$process['live_sell_premium'],    
                     'live_rent_premium'=>$process['live_rent_premium'],     
                     'commercial_sell_premium'=>$process['commercial_sell_premium'],    
                     'commercial_rent_premium'=>$process['commercial_rent_premium'],  
                     'build_premium'=>$process['build_premium'],    
                     'country_sell_premium'=>$process['country_sell_premium'],    
                     'country_rent_premium'=>$process['country_rent_premium'], 
                     'live_sell_vip'=>$process['live_sell_vip'],        
                     'live_rent_vip'=>$process['live_rent_vip'],         
                     'commercial_sell_vip'=>$process['commercial_sell_vip'],      
                     'commercial_rent_vip'=>$process['commercial_rent_vip'],    
                     'build_vip'=>$process['build_vip'],      
                     'country_sell_vip'=>$process['country_sell_vip'],      
                     'country_rent_vip'=>$process['country_rent_vip'],
                     'total'=>0
    );
    $count = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['xml_parse']." WHERE id_agency = ?", $process['id'])['cnt'];
    $limit = 10;
    echo $db->last_query;
    do{
        $field_values = $db->fetchall("SELECT * FROM  ".$sys_tables['xml_parse']." WHERE id_agency = ? ORDER BY id ASC LIMIT ".$limit, false, $process['id']);
        echo ' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n"; 
        foreach($field_values as $key=>$item){
            $file_type = $item['file_type'];
            $values = json_decode($item['xml_values'], true);
            $xml_parse_id = $item['id'];
            unset($item);
            $main_photo = '';
            //режим поулчения фоток 
            $multi_download = true;
            //сумма всех итераций
            ++$counter['total'];
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
                case 'EMLS':
                    $robot = new EMLSXmlRobot($id_user); 
                    $info_source = 11;
                    break;
                case 'BN_NEW':
                    $robot = new BNNEWXmlRobot($id_user);
                    $info_source = 12;
                    break;
                case 'Cian_new':
                    $robot = new CianNewXmlRobot($id_user);
                    $info_source = 13;
                    break;
                    
            }
            
            echo ' ; memory: '.memoryUsage( memory_get_usage(), $base_memory_usage )."\n"; 
            $fields = $robot->getConvertedFields($values, $process);

            echo ' ; memory: '.memoryUsage( memory_get_usage(), $base_memory_usage )."\n"; 
            $deal_type = $robot->estate_type == 'build' ? '' : ($fields['rent'] == 1 ? '_rent' : '_sell');

            if(!empty($fields)){
                
                $check_limit = $robot->checkLimits();
                if($robot->fields['status'] != $fields['status']){
                    $fields['status'] = $robot->fields['status'];
                    $fields['status_date_end'] = $robot->fields['status_date_end'];
                }
                //отсечение лимитов
                $fields['external_id'] = ( empty( $fields['external_id'] ) ? 0 : $fields['external_id'] );
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
                        $errors_log['moderation'][ !empty($fields['real_internal_id']) ? $fields['real_internal_id'] : $fields['external_id'] ] = array(($moderate_status!=4?(!empty($fields['cost'])?$fields['cost'].', ':'').$rent_titles[$fields['rent']]:$fields['txt_addr']),$moderate_status); 
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
                            //была ошибка для тех у кого безлимитный тариф - там был 0, уменьшать было нечего
                            if($counter[$robot->estate_type.$deal_type_title] > 0){
                                --$counter[$robot->estate_type.$deal_type_title];     
                                $db->querys("UPDATE ".$sys_tables['processes']." SET ".$robot->estate_type.$deal_type_title." = ".$robot->estate_type.$deal_type_title." - 1 WHERE id = ?", $process_id);
                            }
                        }

                        //определение списка фотографий, которых нет в БД

                        if(!empty($fields['images'])) {

                            list($photos['to_delete'],$photos['to_add']) = $robot->getPhotoList($fields['images'], $check_object['id']);
                            
                            echo $count."!@#@!#".count($fields['images']);
                            
                            //удаление фоток (из базы и с сервера), которые не вошли в xml

                            $photos_list_in = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                             WHERE `id_parent` = ".$check_object['id']."
                                                             ".(!empty($photos['to_delete'])?" AND `external_img_src` IN (".implode(',', $photos['to_delete']).")":""),'id');
                            $photos_list = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                          WHERE `id_parent` = ".$check_object['id']."
                                                          ".(!empty($photos_list_in)?" AND `id` NOT IN (".implode(',', array_keys($photos_list_in)).")":""),'id');
                        } else {
                            $photos_list = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                          WHERE `id_parent` = ".$check_object['id']
                            );
                            
                        }
                        
                        if(!empty($photos_list)){
                            foreach($photos_list as $k => $val) Photos::Delete($robot->estate_type,$val['id']);
                            $photos_to_delete_ids = implode(',', array_keys($photos_list));
                            if(!empty($photos_to_delete_ids)) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".$photos_to_delete_ids.")");
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
                            if(!empty($fields['images'])) list($photos['to_delete'],$photos['to_add']) = $robot->getPhotoList($fields['images'], $check_object_new['id'],'_new');
                            //удаление фоток (из базы и с сервера)
                            $photos_list = $db->fetchall("SELECT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                         WHERE `id_parent_new` = ".$check_object_new['id']." 
                                                         ".(!empty($photos['to_delete'])?"AND `external_img_src` NOT IN (".implode(',', $photos['to_delete']).")":""));
                            if(!empty($photos_list)){
                                foreach($photos_list as $k => $val) Photos::Delete($robot->estate_type,$val['id'],"_new");
                                $photos_to_delete_ids = implode(',', $photos_list['to_delete']);
                                if(!empty($photos_to_delete_ids)) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".$photos_to_delete_ids.")");
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
                            if(!empty($fields['images'])) list($photos['to_delete'],$photos['to_add']) = $robot->getPhotoList($fields['images'], $check_object_new['id'],'_new');
                            $inserted_id = $db->insert_id;
                        }
                    }         
                    
                    //модерация новых объектов
                    if($prefix=='_new') {
                        $moderate = new Moderation($robot->estate_type, $inserted_id);
                        $moderate->checkObject();
                    }
                    
                    //счетчик кол-ва вариантов

                    if($moderate_status==1){
                        $inserted_id = !empty($moderate->id) ? $moderate->id : $inserted_id;
                        
                        $has_main = false;

                        //загрузка фотографий
                        print_r($photos);                  
                        
                        ++$counter[$robot->estate_type.$deal_type];
 
                        //если нет фотографий, записываем и переходим к следующему
                        if(empty( $fields['images'] ) || count($fields['images']) == 0){
                            $db->querys("UPDATE ".$sys_tables[$robot->estate_type]." SET published = 3 WHERE id = ?",$inserted_id);
                            ++$counter_nophoto[$robot->estate_type.$deal_type];
                            $nophoto_ids[] =  !empty($fields['real_internal_id']) ? $fields['real_internal_id'] : $fields['external_id'] ;
                            $nophoto_objects = true;
                            //кол-во обработанных объектов
                            $db->querys("UPDATE ".$sys_tables['processes']." SET last_action = NOW() WHERE id = ?", $process_id);    
                            $db->querys("DELETE FROM ".$sys_tables['xml_parse']." WHERE id = ?", $xml_parse_id);    
                            --$counter[$robot->estate_type.$deal_type_title];
                            continue;
                        }
                        
                        //обнуление главной фотки
                        $db->querys("UPDATE ".$sys_tables[$robot->estate_type]." SET id_main_photo = 0, has_photo = 1 WHERE id = ?", $inserted_id);
						$main_photo_manually_added = false;

                        print_r( $photos );
						if(!empty($photos['to_add']) && $inserted_id>0) {
                            //чтобы средняя и маленькая картинки вписывались а не резались
                            Photos::$__folder_options=array(
                                'sm'=>array(110,82,'cut',65),
                                'med'=>array(260,190,'cut',80),
                                'big'=>array(1200,800,'',80)
                            );     
                            //флаг загруженной фотки
                            $has_photos = false; 
                            //лимит фоток для агентств
                            $photos['to_add'] = array_slice($photos['to_add'], 0 , DEBUG_MODE ? 3 : 20, $preserve_keys = true);
                            
                            $multi_download = count($photos['to_add']) > 2;
                            if( empty( $nophoto ) ) {
                                //режим скачивания картинок
                                if(!empty($multi_download)){
                                    $external_img_sources = Photos::MultiDownload($photos['to_add'], ROOT_PATH.'/'.Config::$values['img_folders'][$robot->estate_type].'/');
                                    echo 'multi. external_img_sources';
                                    var_dump( $external_img_sources );
                                    foreach($external_img_sources as $k=>$img) {
                                        print_r($img);
                                        $photo_add_result = Photos::Add($robot->estate_type, $inserted_id, '', $img['external_img_src'], $img['filename'], false, false, false, Config::Get('watermark_src'));
                                        echo 'multi add. photo_add_result';
                                        var_dump( $photo_add_result );
                                        if(!is_array($photo_add_result)) {
                                            $errors_log['img'][] = $img['external_img_src'];
                                            //незагруженная фотка является главной
                                            if(empty($fields['main_photo']) || $img['external_img_src'] == $fields['main_photo']) $fields['main_photo'] = '';
                                        } else $has_photos = true;
                                    }
                                } 
                                if(empty($multi_download)){
                                    foreach($photos['to_add'] as $k=>$img) {
                                        print_r($img);
                                        $photo_add_result = Photos::Add($robot->estate_type, $inserted_id, '', $img);
                                        echo 'empty multi. photo_add_result';
                                        var_dump( $photo_add_result );
                                        if(!is_array($photo_add_result)) {
                                            $errors_log['img'][] = $img;
                                            //незагруженная фотка является главной
                                            if(!empty( $fields['main_photo'] ) && !empty( $img ) && $img == $fields['main_photo']) $fields['main_photo'] = '';
                                        } else $has_photos = true;
                                    }
                                    
                                }
                                //ни одной фотки не загружено
                                if(empty($has_photos)){
                                    Photos::setMain($robot->estate_type, $inserted_id,false,false,(!empty($fields['main_photo']) ? $fields['main_photo'] : ''));  
                                    $main_photo = Photos::getMainPhoto($robot->estate_type, $inserted_id);
                                    if(empty($main_photo)) Photos::setMain($robot->estate_type, $inserted_id);
                                    $main_photo = Photos::getMainPhoto($robot->estate_type, $inserted_id);
                                    if(empty($main_photo)){
                                        $db->querys("UPDATE ".$sys_tables[$robot->estate_type]." SET published = 3 WHERE id = ?",$inserted_id);
                                        ++$counter_nophoto[$robot->estate_type.$deal_type];  
                                        $nophoto_ids[] =  !empty($fields['real_internal_id']) ? $fields['real_internal_id'] : $fields['external_id'] ;
                                        $nophoto_objects = true;     
                                        //кол-во обработанных объектов
                                        $db->querys("UPDATE ".$sys_tables['processes']." SET last_action = NOW() WHERE id = ?", $process_id);    
                                        $db->querys("DELETE FROM ".$sys_tables['xml_parse']." WHERE id = ?", $xml_parse_id);    
                                        --$counter[$robot->estate_type.$deal_type];
                                        continue;
                                    }else $main_photo_manually_added = true;
                                }
                            }
                        }
                        //заглавная фотка
                        if(empty($main_photo_manually_added))
                            Photos::setMain($robot->estate_type, $inserted_id,false,false,(!empty($fields['main_photo']) ? $fields['main_photo'] : ''));  
                        
                        
                        ///считаем и записываем вес объекта (в таблицу estate_type или estate_type_new):
                        switch($robot->estate_type){
                            case 'live':$item_weight = new Estate(TYPE_ESTATE_LIVE);break;
                            case 'build':$item_weight = new Estate(TYPE_ESTATE_BUILD);break;
                            case 'country':$item_weight = new Estate(TYPE_ESTATE_COUNTRY);break;
                            case 'commercial':$item_weight = new Estate(TYPE_ESTATE_COMMERCIAL);break;
                        }
                        $item_weight = $item_weight->getItemWeight($inserted_id,$robot->estate_type);
                        $res_weight = $db->querys("UPDATE ".$sys_tables[$robot->estate_type]." SET weight=? WHERE id=?", $item_weight, $inserted_id);
                        
                        //предупреждение о большой площади комнат
                        if($robot->estate_type == 'live' && $fields['id_type_object'] == 2 && ( empty($fields['square_live']) || $fields['square_live'] > 50) ){
                            $errors['rooms_square'][  !empty($fields['real_internal_id']) ? $fields['real_internal_id'] : $fields['external_id']  ] = 'значение: ' . $fields['square_live'] . 'м2'; 
                        }  
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
                echo $count."!@#";
            }
        }
        unset($field_values);
        $count = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['xml_parse']." WHERE id_agency = ?", $process['id'])['cnt'];

    } while($count > 0);

    // --------------------------------------
    $mail_text = '';
    if($nophoto){
        //оправляем оповещение о "без фотографий"
        
    }
    //отправка отчета о вариантах
    if( !empty($counter['total']) ){
        $total = $process['total_amount'];
        $total_added = $counter['live_sell'] + $counter['live_rent'] + $counter['build'] + $counter['country_sell'] + $counter['commercial_sell'] + $counter['country_rent'] + $counter['commercial_rent'];
        $mail_text .= "<br/>Обработано всего объектов: " . $counter['total'] . "<br/>";
        $mail_text .= "<br/>Добавлено объектов: " . $total_added . "
        <br/>- жилая (продажа): ".$counter['live_sell'].
                                         ($counter_joint['live_sell']>0 || $counter_limit['live_sell']>0 ? " (".
                                             ($counter_joint['live_sell']>0?" склеено: ".$counter_joint['live_sell'].($counter_limit['live_sell']>0?", ":"") :"").
                                             ($counter_limit['live_sell']>0? $counter_limit['live_sell']." не выгружено":"").
                                         ")" : "" ).
                                         ($counter_nophoto['live_sell']>0? ", нет фотографий: ".$counter_nophoto['live_sell']:"").
                                         ($counter['live_sell_promo']>0? ", промо: ".$counter['live_sell_promo']:"").
                                         ($counter['live_sell_premium']>0? ", премиум: ".$counter['live_sell_premium']:"").
                                         ($counter['live_sell_vip']>0? ", VIP: ".$counter['live_sell_vip']:"")."
        <br/>- жилая (аренда): ".$counter['live_rent'].
                             ($counter_joint['live_rent']>0 || $counter_limit['live_rent']>0 ? " (".
                                 ($counter_joint['live_rent']>0?" склеено: ".$counter_joint['live_rent'].($counter_limit['live_rent']>0?", ":"") :"").
                                 ($counter_limit['live_rent']>0? $counter_limit['live_rent']." не выгружено":"").
                             ")" : "" ).
                           ($counter_nophoto['live_rent']>0? ", нет фотографий: ".$counter_nophoto['live_rent']:"").
                           ($counter['live_rent_promo']>0? ", промо: ".$counter['live_rent_promo']:"").
                           ($counter['live_rent_premium']>0? ", премиум: ".$counter['live_rent_premium']:"").
                           ($counter['live_rent_vip']>0? ", VIP: ".$counter['live_rent_vip']:"")."
        <br/>- стройка: ".$counter['build'].
                             ($counter_joint['build_sell']>0 || $counter_limit['build']>0 ? " (".
                                 ($counter_joint['build_sell']>0?" склеено: ".$counter_joint['build_sell'].($counter_limit['build']>0?", ":"") :"").
                                 ($counter_limit['build']>0? $counter_limit['build']." не выгружено":"").
                             ")" : "" ).
                            ($counter_nophoto['build']>0? ", нет фотографий: ".$counter_nophoto['build']:"").
                            ($counter['build_promo']>0? ", промо: ".$counter['build_promo']:"").
                            ($counter['build_premium']>0? ", премиум: ".$counter['build_premium']:"").
                            ($counter['build_vip']>0? ", VIP: ".$counter['build_vip']:"")."
        <br/>- коммерческая (продажа): ".$counter['commercial_sell'].
                             ($counter_joint['commercial_sell']>0 || $counter_limit['commercial_sell']>0 ? " (".
                                 ($counter_joint['commercial_sell']>0?" склеено: ".$counter_joint['commercial_sell'].($counter_limit['commercial_sell']>0?", ":"") :"").
                                 ($counter_limit['commercial_sell']>0? $counter_limit['commercial_sell']." не выгружено":"").
                             ")" : "" ).
                               ($counter_nophoto['commercial_sell']>0? ", нет фотографий: ".$counter_nophoto['commercial_sell']:"").
                               ($counter['commercial_sell_promo']>0? ", промо: ".$counter['commercial_sell_promo']:"").
                               ($counter['commercial_sell_premium']>0? ", премиум: ".$counter['commercial_sell_premium']:"").
                               ($counter['commercial_sell_vip']>0? ", VIP: ".$counter['commercial_sell_vip']:"")."
        <br/>- коммерческая (аренда): ".$counter['commercial_rent'].
                             ($counter_joint['commercial_rent']>0 || $counter_limit['commercial_rent']>0 ? " (".
                                 ($counter_joint['commercial_rent']>0?" склеено: ".$counter_joint['commercial_rent'].($counter_limit['commercial_rent']>0?", ":"") :"").
                                 ($counter_limit['commercial_rent']>0? $counter_limit['commercial_rent']." не выгружено":"").
                             ")" : "" ).
                             ($counter_nophoto['commercial_rent']>0? ", нет фотографий: ".$counter_nophoto['commercial_rent']:"").
                             ($counter['commercial_rent_promo']>0? ", промо: ".$counter['commercial_rent_promo']:"").
                             ($counter['commercial_rent_premium']>0? ", премиум: ".$counter['commercial_rent_premium']:"").
                             ($counter['commercial_rent_vip']>0? ", VIP: ".$counter['commercial_rent_vip']:"")."
        <br/>- загородная (продажа): ".$counter['country_sell'].
                                 ($counter_joint['country_sell']>0 || $counter_limit['country_sell']>0 ? " (".
                                     ($counter_joint['country_sell']>0?" склеено: ".$counter_joint['country_sell'].($counter_limit['country_sell']>0?", ":"") :"").
                                     ($counter_limit['country_sell']>0? $counter_limit['country_sell']." не выгружено":"").
                                 ")" : "" ).
                               ($counter_nophoto['country_sell']>0? ", нет фотографий: ".$counter_nophoto['country_sell']:"").
                               ($counter['country_sell_promo']>0? ", промо: ".$counter['country_sell_promo']:"").
                               ($counter['country_sell_premium']>0? ", премиум: ".$counter['country_sell_premium']:"").
                               ($counter['country_sell_vip']>0? ", VIP: ".$counter['country_sell_vip']:"")."
        <br/>- загородная (аренда): ".$counter['country_rent'].
                             ($counter_joint['country_rent']>0 || $counter_limit['country_rent']>0 ? " (".
                                 ($counter_joint['country_rent']>0?" склеено: ".$counter_joint['country_rent'].($counter_limit['country_rent']>0?", ":"") :"").
                                 ($counter_limit['country_rent']>0? $counter_limit['country_rent']." не выгружено":"").
                             ")" : "" ).
                             ($counter_nophoto['country_rent']>0? ", нет фотографий: ".$counter_nophoto['country_rent']:"").
                             ($counter['country_rent_promo']>0? ", промо: ".$counter['country_rent_promo']:"").
                             ($counter['country_rent_premium']>0? ", премиум: ".$counter['country_rent_premium']:"").
                             ($counter['country_rent_vip']>0? ", VIP: ".$counter['country_rent_vip']:"");
    }

    $photos_text = '';
    $total_errors = ( !empty($errors_log['address']) ? count($errors_log['address']) : 0 ) + ( !empty($errors_log['moderation']) ? count($errors_log['moderation']) : 0 ) + ( !empty($nophoto_ids) ? count($nophoto_ids) : 0 ) + (!empty($errors_log['estate_type']) ? count($errors_log['estate_type']) : 0 ) + ( !empty($errors_log['rooms']) ? count($errors_log['rooms']) : 0) + ( !empty($errors_log['rooms_square']) ? count($errors_log['rooms_square']) : 0) + ( !empty($errors_log['img']) ? count($errors_log['img']) : 0);
    $full_errors_log = $errors_log;
    $logs_limit = 50;
    
    //логирование ошибок

    if($total_errors > 0){
        $mail_text .= "<br/><br/>При обработке файла возникли следующие ошибки:<br/>";
        if(!empty($errors_log['address']))  {
            $address_count = count( $errors_log['address'] );
            $errors_log['address'] = array_slice( $errors_log['address'], 0, $logs_limit, $preserve_keys = true);
            
            $mail_text .= "<br/><b>Неверный адрес: (".$address_count.")</b><br/>";        
            foreach($errors_log['address'] as $k=>$address) $mail_text .= "external_id: <b>".$k.'</b>' .(!empty($address)?", значение: ".$address:"")."<br/>";
            if( $address_count > $logs_limit ) $mail_text .= "и еще ".($address_count - $logs_limit)." таких ошибок.\r<br/> За полным логом обратитесь в тех. поддержку БСН.\r<br/>";
        }    
        if(!empty($nophoto_ids)){
            $nophoto_count = count($nophoto_ids);
            $nophoto_ids = array_slice($nophoto_ids,0,$logs_limit, $preserve_keys = true);
            $mail_text .= "<br/><b>Нет фотографий: (".$nophoto_count.")</b><br/>";
            foreach($nophoto_ids as $k=>$id) $mail_text .= "external_id: <b>".$id."</b>"."<br/>";
            if($nophoto_count > $logs_limit) $mail_text .= "и еще ".($nophoto_count - $logs_limit)." таких ошибок.\r<br/> За полным логом обратитесь в тех. поддержку БСН.\r<br/>";
        }
        if(!empty($errors_log['external_id']))  {
            $external_id_count = count($errors_log['external_id']);
            $errors_log['external_id'] = array_slice($errors_log['external_id'],0,$logs_limit, $preserve_keys = true);
            
            $mail_text .= "<br/><b>Пустой уникальный идентификатор: (".$external_id_count.")</b><br/>";
            foreach($errors_log['external_id'] as $k=>$estate_id) $mail_text .= (!empty($estate_id)?", значение: ".$estate_id:"")."</i><br/>";
            if($external_id_count > $logs_limit) $mail_text .= "и еще ".($external_id_count - $logs_limit)." таких ошибок.\r<br/> За полным логом обратитесь в тех. поддержку БСН.\r<br/>";
        }    

        if(!empty($errors_log['moderation']))  {
            $moderation_count = count($errors_log['moderation']);
            $errors_log['moderation'] = array_slice($errors_log['moderation'],0,$logs_limit, $preserve_keys = true);
            
            $mail_text .= "<br/><b>Не прошли модерацию: (".$moderation_count.")</b><br/>";        
            foreach($errors_log['moderation'] as $k=>$moderation) $mail_text .= "external_id: <b>".$k.'</b>, статус: <i>'.$moderate_statuses[$moderation[1]]."</i>".(!empty($moderation[0])?", значение: ".$moderation[0]:"")."<br/>";
            if($moderation_count > $logs_limit) $mail_text .= "и еще ".($moderation_count - $logs_limit)." таких ошибок.\r<br/> За полным логом обратитесь в тех. поддержку БСН.\r<br/>";
        }    
        if(!empty($errors_log['estate_type']))  {
            $estate_type_count = count($errors_log['estate_type']);
            $errors_log['estate_type'] = array_slice($errors_log['estate_type'],0,$logs_limit, $preserve_keys = true);
            
            $mail_text .= "<br/><b>Не найден тип недвижимости/тип объекта: (".$estate_type_count.")</b><br/>";
            foreach($errors_log['estate_type'] as $k=>$estate_id) $mail_text .= "external_id: <b>".$k.'</b>'.(!empty($estate_id)?", значение: ".$estate_id:"")."</i><br/>";
            if($estate_type_count > $logs_limit) $mail_text .= "и еще ".($estate_type_count - $logs_limit)." таких ошибок.\r<br/> За полным логом обратитесь в тех. поддержку БСН.\r<br/>";
        }    
        if(!empty($errors_log['rooms']))  {
            $rooms_count = count($errors_log['rooms']);
            $errors_log['rooms'] = array_slice($errors_log['rooms'],0,$logs_limit, $preserve_keys = true);
            
            $mail_text .= "<br/><b>Неверно указана комнатность: (".$rooms_count.")</b><br/>";
            foreach($errors_log['rooms'] as $k=>$estate_id) $mail_text .= "external_id: <b>".$k.'</b>'.(!empty($estate_id)?", значение: ".$estate_id:"")."</i><br/>";
            if($rooms_count > $logs_limit) $mail_text .= "и еще ".($rooms_count - $logs_limit)." таких ошибок.\r<br/> За полным логом обратитесь в тех. поддержку БСН.\r<br/>";
        }    
        if(!empty($errors_log['rooms_square']))  {
            $rooms_square_count = count($errors_log['rooms_square']);
            $errors_log['rooms_square'] = array_slice($errors_log['rooms_square'],0,$logs_limit, $preserve_keys = true);
            
            $mail_text .= "<br/><b>Объекты выгрузились, но у них подозрительно большая или не указана площадь комнат: (".$rooms_square_count.")</b><br/>";
            foreach($errors_log['rooms_square'] as $k=>$estate_id) $mail_text .= "external_id: <b>".$k.'</b>'.(!empty($estate_id)?", значение: ".$estate_id:"")."</i><br/>";
            if($rooms_square_count > $logs_limit) $mail_text .= "и еще ".($rooms_square_count - $logs_limit)." таких ошибок.\r<br/> За полным логом обратитесь в тех. поддержку БСН.\r<br/>";
        }            
        if(!empty($errors_log['img'])){ //ошибки загрузки фото
            $unloaded_photos_count = count($errors_log['img']);
            $errors_log['img'] = array_slice($errors_log['img'],0,$logs_limit, $preserve_keys = true);
            
            $mail_text .= "<br/>Незагруженные фотографии объектов (".$unloaded_photos_count."):<br/>";
            foreach($errors_log['img'] as $k=>$img) $mail_text .= "".$img."<br/>";
            if($unloaded_photos_count > $logs_limit) $mail_text .= "и еще ".($unloaded_photos_count - $logs_limit)." таких ошибок.\r<br/> За полным логом обратитесь в тех. поддержку БСН.\r<br/>";
        }
    }
                       
    //процесс окончен
    $db->querys("UPDATE ".$sys_tables['processes']." SET status = ?, total_added = ?, total_errors = ?, full_log = CONCAT (full_log, '\n', log, '\n', ?, '\n','___________________________________',''), log='', datetime_end = NOW() WHERE id = ?", 2, $total_added, $total_errors, $mail_text, $process_id);
    $db->querys("UPDATE ".$sys_tables['processes']." SET full_log = REPLACE(full_log, '<br/>', '\n') WHERE id = ?", $process_id);
    file_put_contents( ROOT_PATH . '/cron/robot/logs/' . $id_user . "_" . createCHPUTitle($process['title']) . '.error.log', print_r( $full_errors_log, true ) );
    //если были ошибки выполнения скрипта
    if( filesize( $error_log )>10 ){
        $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
        $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
        $error_log_text .= '</font>';
    } else $error_log_text = "";
    Response::SetArray('agency', $process);
    Response::SetInteger('process_id', $process_id);

    $mail_text = "Обработка объектов агентства ".$process['title']."<br /><br />".$mail_text;
    if(!empty($error_text)) $mail_text = "<br/>".$error_text."<br/><br/>".$mail_text;
    $content = "ID пользователя: ".$process['id_user'].", " . $mail_text;
    Response::SetString( 'content', $content );    
    $eml_tpl = new Template('report.html', 'modules/mailers/');
    $html = $eml_tpl->Processing();
    print_r( '0' );

    // параметры письма
    $mailer_title = 'Обработка формата ' . $file_type . ' XML. '.date('Y-m-d H:i:s') . ', «' . $process['title'] . '»';

    $emails = array(
        array(
            'name' => '',
            'email'=> 'kya1982@gmail.com'
        )
    );
    if(  $process['sent_report'] == 1)
        $emails[] = array(
            'name' => '',
            'email'=> $process['manager_email']
        );

    //отправка письма
    $sendpulse = new Sendpulse();
    $result = $sendpulse->sendMail( $mailer_title, $html, 'Парсинг '.(!empty($file_type) ? $file_type : '').' XML файла', 'no-reply@bsn.ru', $emails );
    print_r( '1' );
    print_r( $emails );
    print_r( $result );

    if($process['sent_report'] == 1){
        $eml_tpl = new Template('parse.xml.notification.html', 'cron/robot/');
        // перевод письма в кодировку мейлера
        $html = $eml_tpl->Processing();
        //отчет
        $report = $db->fetch("SELECT 
                                        *,
                                        IF(YEAR(`datetime_start`) < Year(CURDATE()),DATE_FORMAT(`datetime_start`,'%e %M %Y'),DATE_FORMAT(`datetime_start`,'%e %M')) as normal_date,  
                                        DATE_FORMAT(`datetime_start`,'%k:%i') as normal_date_start,
                                        DATE_FORMAT(`datetime_end`,'%k:%i') as normal_date_end
                                  FROM ".$sys_tables['processes']." 
                                  WHERE id = ?", $process_id 
        );

        $mailer_title = "Отчет о загрузке объектов «" . $process['title'] . "» от ".$report['normal_date']." ".$report['normal_date_start']." > ".$report['normal_date_end'];

        $sender_email = 'no-reply@bsn.ru';
        $sender_name = 'XML парсер BSN.ru';

        $emails = array(
            array(
                'name' => '',
                'email'=> 'kya1982@gmail.com'
            )
        );
        if( !empty( $process['user_email'] )    && $process['xml_notification'] == 1 && Validate::isEmail($process['user_email']) )
            $emails[] = array(
                'name' => '',
                'email'=> $process['user_email']
            );
        if( !empty( $process['email_service'] ) && $process['xml_notification'] == 1 && Validate::isEmail($process['email_service']) )
            $emails[] = array(
                'name' => '',
                'email'=> $process['email_service']
            );
        print_r( '2' );
        print_r( $emails );

        //отправка письма
        $sendpulse = new Sendpulse();
        $result = $sendpulse->sendMail( $mailer_title, $html, $sender_name, $sender_email, $emails );
        print_r( $result );
        
        //отправка письма менеджеру
        if(!empty($process['manager_email'])){

            Response::SetArray('agency', $process);
            $eml_tpl = new Template('parse.xml.manager.notification.html', 'cron/robot/');

            $mailer_title = "Отчет о загрузке объектов агентства ".$process['title']." ID ".$process['id_user']." от ".$report['normal_date']." ".$report['normal_date_start']." > ".$report['normal_date_end'];

            $sender_email = 'no-reply@bsn.ru';
            $sender_name = 'XML парсер BSN.ru, «' . $process['title'] . '»';
            
            $emails = array(
                array(
                    'name' => '',
                    'email'=> 'kya1982@gmail.com'
                )
            );
            if( !empty( $process['manager_email']) )
                $emails[] = array(
                    'name' => '',
                    'email'=> $process['manager_email']
                );
            //отправка письма
            $sendpulse = new Sendpulse();
            $result = $sendpulse->sendMail( $mailer_title, $html, $sender_name, $sender_email, $emails );
            print_r( '3' );
            print_r( $emails );

            print_r( $result );
        }
    } 
    //отправка письма менеджеру от ненайденных комплексах
    if(!empty($estate_complexes_log)){
        $mail_text = "Ненайденные комплексы агентства ".$process['title']."<br /><br />".implode("<br />", $estate_complexes_log);
        $content = "ID пользователя: ".$process['id_user'].", " . $mail_text . $error_log_text;
        Response::SetString( 'content', $content );    
        $eml_tpl = new Template('report.html', 'modules/mailers/');
        $html = $eml_tpl->Processing();

        // параметры письма
        $mailer_title = 'Ненайденные комплексы агентства '.$process['title'];
        $sender_email = 'no-reply@bsn.ru';
        $sender_name = 'XML парсер BSN.ru';
        
        $emails = array(
            array(
                'name' => '',
                'email'=> !DEBUG_MODE ? $process['manager_email'] : 'kya1982@gmail.com'
            )
        );
        //отправка письма
        $sendpulse = new Sendpulse();
        $result = $sendpulse->sendMail( $mailer_title, $html, $sender_name, $sender_email, $emails );
        print_r( '4' );
        print_r( $emails );

        print_r( $result );
        
    }   
}
?>
