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
$error_log = ROOT_PATH.'/cron/robot/parsers/excel/error.log';
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
include('includes/class.excel.reader.php');  // конвертация excel в array
include('includes/phpexcel/PHPExcel.php'); //класс для всех типов Excel

//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$moderate_statuses = array(2=>'маленькая стоимость',3=>'большая стоимость',4=>'нет адреса'); //статусы модерации
$rent_titles = array(1=>'аренда', 2=>'продажа'); //типы сделок
    
//папка с txt файлами 
$dir = ROOT_PATH."/cron/robot/files/excel_custom/";
$mail_text = '';
$id_user = 12526;
$target_dir = ROOT_PATH."/img/uploads/mail_objects_images/".$id_user."_images";

$dh = opendir($dir);
//архивы с фотографиями распаковываем отдельно
/*
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..' && preg_match('/^'.$id_user.'/sui',$filename))
    {
        exec("chmod 777 ".$dir.$filename);
        if(preg_match('/(rar|zip)$/sui',$filename)){
            //создаем папку под картинки
            if(!is_dir($target_dir)) mkdir($target_dir);
            //извлекаем картинки
            switch(true){
                case preg_match('/\.zip$/si',$filename):
                    $zip = new ZipArchive;
                    $zip->open($dir.$filename);
                    $zip->extractTo($target_dir);
                    $zip->close();
                    echo $filename." extracted";
                    break;
                case preg_match('/\.rar$/si',$filename):
                    $rar_arch = RarArchive::open($dir.$filename);
                    $entries_list = $rar_arch->getEntries();
                    foreach($entries_list as $entry) {                        
                        if(empty($entry)) continue;
                        $entry->extract($target_dir);
                    }
                    rar_close($rar_arch);
                    echo $filename." extracted";
                    break;
            }
        }
    }
}
$dh = closedir($dir);
echo "\r\n";
*/
//переименовываем файлы и папки в транслит

$dh = opendir($dir);

while($filename = readdir($dh))
{   echo $filename.';';
    if($filename!='.' && $filename!='..' && preg_match('/^'.$id_user.'/sui',$filename))
    {
        //файлы xls,xlsx,csv
        if(preg_match('/(xlsx|xls)$/sui',$filename)){
        
            $mail_text .= 'Файл:'.$dir.$filename.'<br />';  
            $errors_log=array();  // ошибки               
            //Определение id_user по начальному имени файла
            $id_user = explode('_',$filename);
            $id_user = Convert::ToInt($id_user[0]); 
            if($id_user<1) $mail_text.="Ошибка авторизации";
            else
            {
                $agency = $db->fetch("SELECT          
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
               WHERE ".$sys_tables['users'].".id = ".$id_user." AND ".$sys_tables['users'].".agency_admin = 1");
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

                try{
                    //$filename = "41410_garant_1208.xlsx";
                    $filetype = PHPExcel_IOFactory::identify($dir.$filename);
                }
                catch(Exception $e){
                    echo $e;
                }

                $excel_reader = PHPExcel_IOFactory::createReader($filetype);
                $excel_reader->setReadDataOnly(true);
                $data = $excel_reader->load($dir.$filename);

                $sheet = $data->getSheet(0); 
                $highestRow = $sheet->getHighestRow(); 
                $highestColumn = $sheet->getHighestColumn();
                $rows = array();
                $columnData = $sheet->rangeToArray('A' . 1 . ':' . $highestColumn . 1,NULL,TRUE,FALSE);
                if(!empty($columnData) && !empty($columnData[0])) $columns = array_map(mb_strtolower,$columnData[0]);

                for ($row = 2; $row <= $highestRow; $row++){ 
                    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,NULL,TRUE,FALSE);
                    if(!empty($rowData) && !empty($rowData[0])) $rows[] = $rowData[0];
                }

                $mapping = array(
                    'external_id' => 'external_id',
                    'building' => 'id_housing_estate',
                    'city' => 'outer_address',
                    'adres' => 'inner_address',
                    'deadline' => 'id_build_complete',
                    //idn - ?
                    'level' => 'level',
                    'section' => 'corp',
                    'kkv' => 'rooms_sale',
                    'so' => 'square_full',
                    'sl' => 'square_live',
                    'sk' => 'square_kitchen',
                    //sh,ss - ?
                    'price' => 'cost',
                    'image_plan' => 'image'
                );

                if(!empty($rows)){
                    for($col=0; $col<count($columns); $col++) {
                        $val = str_replace('.','',$columns[$col]);
                        $fields_types[] = empty($mapping[$val]) ? '' : $mapping[$val];
                    }
                }

                //обработка полученных значений
                foreach($rows as $key=>$values){
                    $robot = new CustomExcelRobot($id_user);
                    
                    //от них идет только стройка
                    $robot->estate_type = 'build';
                    $estate_type = 'build';
                    
                    //если строка пустая, переходим к следующей
                    $empty_line = true;
                    foreach($values as $k=>$v){
                        if(!empty($v)) $empty_line = false;
                    }
                    if($empty_line) continue;
                    
                    $fields = $robot->getConvertedFields($values, $fields_types, $estate_type, $id_type_object); 
                    
                    $fields['id_main_photo'] = 0;
                    
                    //проверка лимита
                    $deal_type = $robot->estate_type == 'build' ? '' : ($fields['rent'] == 1 ? '_rent' : '_sell');
                    $check_limit = ($robot->estate_type.$deal_type == 'live_rent' && $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects']) || ($robot->estate_type.$deal_type != 'live_rent' && ($agency['id_tarif']==1 || $agency['id_tarif']==7 || $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects'])); 
                    $check_limit = true;
                    if(!empty($fields)) {
                        //сумма всех итераций
                        //++$counter['total'];
                        //счетчик сверх лимита
                        if(!$check_limit) {
                            if(empty($errors_log['over_limit'])) $errors_log['over_limit'] = 0;
                            $errors_log['over_limit']++;
                            empty($counter[$robot->estate_type.$deal_type.'_over_limit']) ? $counter[$robot->estate_type.$deal_type.'_over_limit'] = 1 : $counter[$robot->estate_type.$deal_type.'_over_limit']++;
                        }
                    }
                    
                    if(!empty($fields) && $check_limit){ //отсечение лимитов

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
                        //префикс для фото
                        $prefix = '';
                        //поиск ранее загруженного объекта в основной таблице
                        $check_object = $db->fetch("SELECT `id` FROM ".$sys_tables[($robot->estate_type)]." WHERE `external_id` = ? AND `id_user` = ?  AND `info_source` = ?",
                                                $fields['external_id'], $id_user, 7
                        );
                        if(!empty($check_object)) { 
                            $fields['id'] = $check_object['id'];
                            
                            //updat'им данные
                            $res = $db->updateFromArray($sys_tables[($robot->estate_type)], $fields, 'id');
                            $inserted_id = $check_object['id'];
                            //если объект на модерации
                            if($moderate_status>1){
                                //проверяем его наличие в таблице new
                                $check_object_new = $db->fetch("SELECT `id` FROM ".$sys_tables[($robot->estate_type).'_new']." WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",
                                                        $fields['external_id'], $id_user, 7);
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
                                                    $fields['external_id'], $id_user, 7
                            );
                            if(!empty($check_object_new)) {
                                $fields['id'] = $check_object_new['id'];
                                //updat'им данные
                                $fields['date_in']= date('Y-m-d H:i:s');
                                $fields['id_moderate_status'] = $moderate_status;
                                $res = $db->updateFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
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
                                $inserted_id = $db->insert_id;
                            }
                        }         
                        echo $inserted_id.' : ';
                        echo $robot->estate_type.' : '.$prefix.' : ';
                        
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
                        if( !empty( $fields['image'][0] )) $fields['image'] = $fields['image'][0];
                        $image_filename = Convert::ToTranslit($fields['image'],true);
                        if(strstr($fields['image'],' ')){
                            $img_folder = explode(' ',$fields['image'])[0];
                            $fields['image'] = $img_folder."/".$fields['image'];
                            $fields['image'] = Convert::ToTranslit($fields['image'],true);
                        }
                        
                        //определение списка фотографий, которых нет в БД
                        list($photos['in'],$photos['out']) = $robot->getPhotoList(($target_dir."/".$fields['image']), $fields['id']);
                        if(!empty($fields['id'])){
                            //удаление фоток (из базы и с сервера), которые не вошли в xml
                            $photos_list_in = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                             WHERE `id_parent` = ".$fields['id']."
                                                             ".(!empty($photos['in'])?" AND `external_img_src` IN (".implode(',', $photos['in']).")":""),'id');
                            $photos_list = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                          WHERE `id_parent` = ".$fields['id']."
                                                          ".(!empty($photos_list_in)?" AND `id` NOT IN (".implode(',', array_keys($photos_list_in)).")":""),'id');
                            if(!empty($photos_list)){
                                foreach($photos_list as $k => $val) Photos::Delete($robot->estate_type,$val['id']);
                                $photos_to_delete_ids = implode(',', $photos_list['in']);
                                if(!empty($photos_to_delete_ids)) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".$photos_to_delete_ids.")");
                            }
                        }
                        
                        //добавляем новую картинку:
                        if(!empty($photos['out']) && file_exists(mb_convert_encoding($target_dir."/".$fields['image'],"utf-8"))){
                            
                            //$external_img_sources = Photos::MultiDownload($photos['out'], ROOT_PATH.'/'.Config::$values['img_folders'][$robot->estate_type].'/');
                            //foreach($external_img_sources as $k=>$img) {
                            copy($target_dir."/".$fields['image'],ROOT_PATH.'/'.Config::$values['img_folders'][$robot->estate_type].'/'.$image_filename);
                             $photo_add_result = Photos::Add($robot->estate_type, $fields['id'], $prefix, false, $image_filename, false, false, false, Config::Get('watermark_src'),false,false,false,false,true);
                             if(!is_array($photo_add_result)) $errors_log['img'][] = $img['external_img_src'];
                            //}
                        }else $db->querys("UPDATE ".$sys_tables[$robot->estate_type]." SET id_main_photo = ? WHERE id = ?",array_keys($photos_list_in)[0],$robot->fields['id']);
                        
                        //модерация новых объектов
                        if($prefix=='_new') {
                            $moderate = new Moderation($robot->estate_type,$inserted_id);
                            $moderate->checkObject();
                        }
                        //счетчик кол-ва вариантов
                        if($moderate_status==1) $counter[$robot->estate_type.$deal_type]++;
                        else {
                            if(!empty($fields['elite']) && $fields['elite']==1) $counter[$robot->estate_type.$deal_type.'_elite']--;
                        }
                        unset($fields); unset($values); 
                    } //end of: проверка на лимит жилой аренды                        
                    
                    unset($rows[$key]);
                    
                } // end of : foreach($rows as $key=>$values){
            }
        }
    }
}

exec("rm -rf ".$target_dir);
?>