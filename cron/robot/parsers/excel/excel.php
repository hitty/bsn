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
$dir = ROOT_PATH."/cron/robot/files/emls_xls/";
$mail_text = '';
//сначала обработка файлов с объектами, потом фото
//$files = array('comm|country|flats|new|rooms','photo');   
$files = array('comm|country|flats|new|rooms','photo');   
foreach($files as $iteration => $file_format){
    $dh = opendir($dir);
    while($filename = readdir($dh))
    {
        
        if($filename!='.' && $filename!='..')
        {
            exec("chmod 777 ".$dir.$filename);
            
            if(preg_match("#\-(".$file_format.")\-#is",$filename)){ //обработка файла
            
                $mail_text .= 'Файл:'.$dir.$filename.'<br />';  
                $errors_log=array();  // ошибки               
                //Определение id_user по начальному имени файла
                $id_user = explode('_',$filename);
                $id_user = Convert::ToInt($id_user[0]); 
                if($id_user<1) $mail_text.="Ошибка авторизации";
                else
                {
                        
                    //определение рынка недвижимости по типу файла
                    preg_match_all("#(".$file_format.")#is",$filename,$file_type);
                    $file_type = $file_type[0][0];
                    //счетчик объектов
                    if($file_format != 'photo'){ // проверка обработки файлов с объектами
                        $counter = array('live_sell'=>0,            'live_rent'=>0,             'commercial_sell'=>0,            'commercial_rent'=>0,          'build'=>0,            'country_sell'=>0,            'country_rent'=>0, 
                                         'live_sell_promo'=>0,      'live_rent_promo'=>0,       'commercial_sell_promo'=>0,      'commercial_rent_promo'=>0,    'build_promo'=>0,      'country_sell_promo'=>0,      'country_rent_promo'=>0, 
                                         'live_sell_premium'=>0,    'live_rent_premium'=>0,     'commercial_sell_premium'=>0,    'commercial_rent_premium'=>0,  'build_premium'=>0,    'country_sell_premium'=>0,    'country_rent_premium'=>0, 
                                         'live_sell_elite'=>0,      'live_rent_elite'=>0,       'commercial_sell_elite'=>0,      'commercial_rent_elite'=>0,    'build_elite'=>0,      'country_sell_elite'=>0,      'country_rent_elite'=>0,
                                         'total'=>0
                        );
                    }
                    
                    $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".*,
                                                 ".$sys_tables['agencies'].".id_tarif AS agency_tarif,
                                                 ".$sys_tables['managers'].".`email` as email,
                                                 ".$sys_tables['users'].".email as admin_email,
                                                 ".$sys_tables['users'].".id_tarif,
                                                 ".$sys_tables['users'].".id AS id_user,
                                                 IF(".$sys_tables['tarifs'].".title IS NOT NULL,".$sys_tables['tarifs'].".title,'') AS tarif_title
                                          FROM ".$sys_tables['agencies']."
                                          RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
                                          LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager 
                                          LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['users'].".id_tarif = ".$sys_tables['tarifs'].".id
                                          WHERE ".$sys_tables['users'].".`id` = ?",
                                          $id_user) ;        
                    if(empty($agency)) $mail_text.="Ошибка авторизации";
                    elseif(!empty($agency['id_tarif'])) $mail_text .= "Объекты агентства ".$agency['title']." не выгрузились, потому что для агентства установлен тариф ".$agency['tarif_title'];
                    else
                    {
                        
                        if($file_format != 'photo'){ // проверка обработки файлов с объектами
                            //рекламное агентство
                            $advert_agency = $agency['activity']%pow(2,2)>=pow(2,1)?true:false;
                            //текст письма
                            $mail_text .= "Агентство <i>".$agency['title']."</i><br />";

                            $id_type_object = 0;
                            //простановка в архив объектов по заданному типу недвижимости от этого агентства
                            switch($file_type){
                                case 'flats':
                                    $mail_text .= '<strong>Жилая. Квартиры.</strong><br/>';
                                    $id_type_object = 1;
                                    $rent = 1;
                                    $estate_type='live';   
                                    break;   
                                case 'rooms': 
                                    $mail_text .= '<strong>Жилая. Комнаты.</strong><br/>';
                                    $id_type_object = 2;
                                    $rent = 1;
                                    $estate_type='live';
                                    break;   
                                case 'comm': 
                                    $estate_type = 'commercial';
                                    $id_type_object = false;
                                    break;   
                                case 'country': 
                                    $estate_type = 'country';
                                    $id_type_object = false;
                                    $rent = 1;
                                    break;   
                                case 'new': 
                                    $estate_type = 'build';
                                    $rent = 1;
                                    $rent = 1;
                                    break;   
                            }
                            $db->querys("UPDATE estate.".$estate_type." SET `published` = 2, `date_change` = NOW() WHERE id_user = ? AND info_source!=4 AND published=1 AND elite!=1 ".(!empty($id_type_object)?" AND id_type_object = ".$id_type_object:""),$id_user);
                            
                            $rows = $fields_types = $data = array();

                            //$data = new Spreadsheet_Excel_Reader($dir.$filename);
                            
                            try{
                                $filetype = PHPExcel_IOFactory::identify($dir.$filename);
                            }
                            catch(PHPExcel_Reader_Exception $e){
                                $mailer = new Emailer('mail');
                                $mailer->sendEmail(array("web@bsn.ru","scald@bsn.ru"),
                                                   array('Миша','Юрий'),
                                                   "Сбой в чтении файла XLS",
                                                   false,
                                                   false,
                                                   false,
                                                   $e
                                                   );
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
                                'тел'=>'seller_phone', 'район'=>'district', 'адрес'=>'address', 'транспорт'=>'subway', 
                                'удаленность'=>'way_time', 'тип'=>'type_object', 'общая'=>'square_full', 'цена'=>'cost', 
                                'примечание'=>'notes', 'регион'=>'district', 'n'=>'external_id', 'участок'=>'square_ground','кухня'=>'square_kitchen',
                                'регион'=>'district_region', 'юрстатус'=>'ownerships', 'этажей'=>'level_total', 'жилая'=>'square_live',
                                'этаж'=>'level', 'этажность'=>'level_total', 'колкомнат'=>'rooms_sale', 'строка площадей'=>'square_rooms',
                                'тип дома'=>'building_type', 'санузел'=>'toilet', 'колкомнат на продаже'=>'rooms_sale', 'всего комнат'=>'rooms_total'
                            );
                            
                            if(!empty($rows)){
                                for($col=0; $col<count($columns); $col++) {
                                    $val = str_replace('.','',$columns[$col]);
                                    $fields_types[] = empty($mapping[$val]) ? '' : $mapping[$val];
                                }
                            }

                            //обработка полученных значений
                            foreach($rows as $key=>$values){
                                $robot = new ExcelRobot($id_user);
                                
                                //если строка пустая, переходим к следующей
                                $empty_line = true;
                                foreach($values as $k=>$v){
                                    if(!empty($v)) $empty_line = false;
                                }
                                if($empty_line) continue;
                                
                                $fields = $robot->getConvertedFields($values, $fields_types, $estate_type, $id_type_object); 
                                
                                //проверка лимита
                                $deal_type = $robot->estate_type == 'build' ? '' : ($fields['rent'] == 1 ? '_rent' : '_sell');
                                $check_limit = ($robot->estate_type.$deal_type == 'live_rent' && 
                                                $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects']) || 
                                                ($robot->estate_type.$deal_type != 'live_rent' && 
                                                 ($agency['agency_tarif']==1 || $agency['agency_tarif']==7 || ( $agency['agency_tarif']==8 && $robot->estate_type != 'build') || $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects'])
                                                 ); 
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
                                        
                                        //чистим фотки объекта
                                        $photo_list = $db->fetchall("SELECT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id_parent` = ".$check_object['id']);
                                        if(!empty($photo_list)){
                                            foreach($photo_list as $k => $val) Photos::Delete($robot->estate_type,$val['id']);
                                            if(!empty($photo_list['in'])) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".implode(',', $photo_list['in']).")");
                                        }
                                        unset($photo_list);
                                        
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
                            
                        } //if($file_format != 'photo'){
                        else { //обработка файла с фотографиями
                            //распарсивание файла с изображениями
                            $images = array(); //массив с фотографиями вида (N фото,N объета,база,ссылка)
                            
                            //$images_data = new Spreadsheet_Excel_Reader($dir.$filename);
                            
                            try{
                                $filetype = PHPExcel_IOFactory::identify($dir.$filename);
                            }
                            catch(PHPExcel_Reader_Exception $e){
                                require_once('includes/class.email.php');
                                $mailer = new Emailer('mail');
                                $mailer->sendEmail(array("web@bsn.ru","scald@bsn.ru"),
                                                   array('Миша','Юрий'),
                                                   "Сбой в чтении файла XLS",
                                                   false,
                                                   false,
                                                   false,
                                                   $e
                                                   );
                            }
                            
                            $excel_reader = PHPExcel_IOFactory::createReader($filetype);
                            $excel_reader->setReadDataOnly(true);
                            $data = $excel_reader->load($dir.$filename);
                            
                            $sheet = $data->getSheet(0); 
                            $highestRow = $sheet->getHighestRow(); 
                            $highestColumn = $sheet->getHighestColumn();
                            $images = array();
                            $columnData = $sheet->rangeToArray('A' . 1 . ':' . $highestColumn . 1,NULL,TRUE,FALSE);
                            if(!empty($columnData) && !empty($columnData[0])) $columns = $columnData[0];
                            
                            for ($row = 2; $row <= $highestRow; $row++){ 
                                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,NULL,TRUE,FALSE);
                                if(!empty($rowData) && !empty($rowData[0])) $images[] = $rowData[0];
                            }
                            
                            if(!empty($images)){
                                $robot = new ExcelRobot($id_user);
                                $datas = array();
                                foreach($images as $image) $datas[$image[2]][$image[1]][] = $image[3]; //формирование массива фотографиий для каждого объекта
                                
                                $file_types = array('rooms'=>'live', 'flats'=>'live', 'new'=>'build', 'country'=>'country', 'comm'=>'commercial'); // соответствие типа файла и типа недвижимости (таблицы)
                                Photos::$__folder_options['sm'] = array(110,82,'',65);
                                Photos::$__folder_options['med'] = array(560,415,'',75);
                                foreach($file_types as $file_type=>$file_type_estate){
                                    if(!empty($datas[$file_type])){
                                        foreach($datas[$file_type] as $external_id => $images){
                                            //поиск ранее загруженного объекта в основной таблице
                                            $check_object = $db->fetch("SELECT `id`,`id_main_photo` FROM ".$sys_tables[$file_type_estate]." WHERE `external_id` = ? AND `id_user` = ?  AND `info_source` = ?",
                                                                    $external_id, $id_user, 7
                                            );
                                            if(!empty($check_object)) { 
                                                $photos = array();
                                                $robot->estate_type = $file_type_estate;
                                                
                                                list($photos['in'],$photos['out']) = $robot->getPhotoList($images, $check_object['id']);
                                                //удаление фоток (из базы и с сервера), которые не вошли в xml
                                                if(!empty($photos['in'])){
                                                    $photo_list = $db->fetchall("SELECT `id` FROM ".$sys_tables[$file_type_estate.'_photos']." 
                                                                             WHERE `id_parent` = ".$check_object['id']." 
                                                                             ".(!empty($photos['in'])?"AND `external_img_src` NOT IN (".implode(',', $photos['in']).")":""));
                                                    if(!empty($photo_list)){
                                                        foreach($photo_list as $k => $val) Photos::Delete($file_type_estate,$val['id']);
                                                        if(!empty($photo_list['in'])) $db->querys("DELETE FROM ".$sys_tables[$file_type_estate.'_photos']." WHERE `id` IN (".implode(',', $photo_list['in']).")");
                                                    }
                                                }

                                                if(!empty($photos['out']) && $check_object['id']>0) {
                                                    $external_img_sources = Photos::MultiDownload($photos['out'], ROOT_PATH.'/'.Config::$values['img_folders'][$file_type_estate].'/');
                                                    $external_img_sources = array_reverse($external_img_sources);
                                                    foreach($external_img_sources as $k=>$img) {
                                                        Photos::Add($file_type_estate, $check_object['id'], '', $img['external_img_src'], $img['filename'],false,false,true,false,false,false,false,false,true);
                                                        //Photos::Add('users',           $id,                 false, false,                 false,          false,false,true);
                                                        if(!empty($main_photo) && $main_photo==$img['external_img_src']) Photos::setMain($file_type_estate, $check_object['id'],null,'');
                                                        echo $file_type_estate.": ".$check_object['id']."->photo";
                                                    }
                                                }
                                                
                                            }
                                        }
                                    }
                                }
                            unlink($dir.$filename);
                            } 
                        }
                    } //end of : if($id_user<1) 
                } // if empty id_user     
            } else { // файл не распознан
                   if($iteration == 1)  unlink($dir.$filename);//$mail_text .= 'Файл '.$filename.' не распознан.';    
            }  //end of: if(preg_match("#\.(all|ard|kn|ned|zd)#is")){ 
            if($iteration == 0){ // логировать только при обработке файлов с объектами
                if(!empty($counter)){
                    $total = $counter['total'];
                    $mail_text .= "Обработано всего объектов:".$total;
                    $mail_text .= "<br />Добавлено объектов: ".($counter['live_sell']+$counter['live_rent']+$counter['build']+$counter['country_sell']+$counter['commercial_sell']+$counter['country_rent']+$counter['commercial_rent'])."<br />
                    - жилая (продажа): ".$counter['live_sell'].($counter['live_sell_promo']>0? ", промо: ".$counter['live_sell_promo']:"").($counter['live_sell_premium']>0? ", премиум: ".$counter['live_sell_premium']:"").($counter['live_sell_elite']>0? ", элитных: ".$counter['live_sell_elite']:"")."<br />
                    - жилая (аренда): ".$counter['live_rent'].($counter['live_rent_promo']>0? ", промо: ".$counter['live_rent_promo']:"").($counter['live_rent_premium']>0? ", премиум: ".$counter['live_rent_premium']:"").($counter['live_rent_elite']>0? ", элитных: ".$counter['live_rent_elite']:"")."<br />
                    - стройка: ".$counter['build'].($counter['build_promo']>0? ", промо: ".$counter['build_promo']:"").($counter['build_premium']>0? ", премиум: ".$counter['build_premium']:"").($counter['build_elite']>0? ", элитных: ".$counter['build_elite']:"")."<br />
                    - коммерческая (продажа): ".$counter['commercial_sell'].($counter['commercial_sell_promo']>0? ", промо: ".$counter['commercial_sell_promo']:"").($counter['commercial_sell_premium']>0? ", премиум: ".$counter['commercial_sell_premium']:"").($counter['commercial_sell_elite']>0? ", элитных: ".$counter['commercial_sell_elite']:"")."<br />
                    - коммерческая (аренда): ".$counter['commercial_rent'].($counter['commercial_rent_promo']>0? ", промо: ".$counter['commercial_rent_promo']:"").($counter['commercial_rent_premium']>0? ", премиум: ".$counter['commercial_rent_premium']:"").($counter['commercial_rent_elite']>0? ", элитных: ".$counter['commercial_rent_elite']:"")."<br />
                    - загородная (продажа): ".$counter['country_sell'].($counter['country_sell_promo']>0? ", промо: ".$counter['country_sell_promo']:"").($counter['country_sell_premium']>0? ", премиум: ".$counter['country_sell_premium']:"").($counter['country_sell_elite']>0? ", элитных: ".$counter['country_sell_elite']:"")."<br />
                    - загородная (аренда): ".$counter['country_rent'].($counter['country_rent_promo']>0? ", промо: ".$counter['country_rent_promo']:"").($counter['country_rent_premium']>0? ", премиум: ".$counter['country_rent_premium']:"").($counter['country_rent_elite']>0? ", элитных: ".$counter['country_rent_elite']:"")."<br /><br />
                    ";
                }
                $photos_text = '';
                //логирование ошибок
                
                //если указан общий ограничитель, убираем все что больше
                if(!empty($agency['total_objects'])){
                    $counts = $db->fetch("SELECT
                                          (SELECT COUNT(*) FROM ".$sys_tables['live']." WHERE id_user = ".$agency['id_user']." AND published=1) AS live,
                                          (SELECT COUNT(*) FROM ".$sys_tables['build']." WHERE id_user = ".$agency['id_user']." AND published=1) AS build,
                                          (SELECT COUNT(*) FROM ".$sys_tables['commercial']." WHERE id_user = ".$agency['id_user']." AND published=1) AS commercial,
                                          (SELECT COUNT(*) FROM ".$sys_tables['country']." WHERE id_user = ".$agency['id_user']." AND published=1) AS country");
                    $total_published = array_sum($counts);
                    foreach($counts as $key=>$count){
                        if($total_published > $agency['total_objects']){
                            if($key == 'build'){
                                $db->querys("UPDATE ".$sys_tables[$key]." SET published = 2 WHERE id_user = ? AND published = 1 LIMIT ".($total_published - $agency['total_objects']),$agency['id_user']);
                                $counter[$key.'_over_limit'] = $db->affected_rows;
                                $total_published -= $counter[$key.'_over_limit'];
                            }
                            else{
                                $db->querys("UPDATE ".$sys_tables[$key]." SET published = 2 WHERE id_user = ? and rent = 1 AND published = 1 LIMIT ".($total_published - $agency['total_objects']),$agency['id_user']);
                                $counter[$key.'_rent_over_limit'] = $db->affected_rows;
                                $total_published -= $counter[$key.'_rent_over_limit'];
                                $db->querys("UPDATE ".$sys_tables[$key]." SET published = 2 WHERE id_user = ? and rent = 2 AND published = 1 LIMIT ".($total_published - $agency['total_objects']),$agency['id_user']);
                                $counter[$key.'_sell_over_limit'] = $db->affected_rows;
                                $total_published -= $counter[$key.'_sell_over_limit'];
                            }
                        }
                    }
                }
                
                if(!empty($errors_log)){
                    $mail_text .= "<br /><br />При обработке файла возникли следующие ошибки: превышение лимита:";
                    if(!empty($errors_log['over_limit'])) $mail_text .= "<br /><br />Объекты сверх установленного лимита (не выгружены):".$errors_log['over_limit']; 
                    $mail_text .= "
                    ".(!empty($counter['live_sell_over_limit'])?"- жилая (продажа): ".$counter['live_sell_over_limit']."<br />":"")."
                    ".(!empty($counter['live_rent_over_limit'])?"- жилая (аренда): ".$counter['live_rent_over_limit']."<br />":"")."
                    ".(!empty($counter['build_over_limit'])?"- стройка: ".$counter['build_over_limit']."<br />":"")."
                    ".(!empty($counter['commercial_sell_over_limit'])?"- коммерческая (продажа): ".$counter['commercial_sell_over_limit']."<br />":"")."
                    ".(!empty($counter['commercial_rent_over_limit'])?"- коммерческая (аренда): ".$counter['commercial_rent_over_limit']."<br />":"")."
                    ".(!empty($counter['country_sell_over_limit'])?"- загородная (продажа): ".$counter['country_sell_over_limit']."<br />":"")."
                    ".(!empty($counter['country_rent_over_limit'])?"- загородная (аренда): ".$counter['country_rent_over_limit']."<br />":"")."
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
            }
           
        } // end of: if($filename!='.' && $filename!='..')
    } // while($filename = readdir($dh))
} //foreach(file_format)
//если были ошибки выполнения скрипта

//после обработки всех файлов, убираем в архив объекты без фото
$nophoto = array('build' => 0, 'live' => 0, 'commercial' => 0, 'country' => 0);
$estate_types = array_keys($nophoto);
foreach($estate_types as $key => $estate_type){
    $db->querys("UPDATE ".$sys_tables[$estate_type]." 
                SET published = 3 
                WHERE id_user = ? AND published = 1 AND id_main_photo = 0", $id_user);
    $nophoto[$estate_type] = $db->affected_rows;
}

if(array_filter($nophoto)){
    $mail_text .= "Объектов без фото(не опубликованы):<br />";
    if(!empty($nophoto['build'])) $mail_text .= "стройка: ".$nophoto['build']."<br />\r\n";
    if(!empty($nophoto['live'])) $mail_text .= "жилая: ".$nophoto['live']."<br />\r\n";
    if(!empty($nophoto['commercial'])) $mail_text .= "коммерческая: ".$nophoto['commercial']."<br />\r\n";
    if(!empty($nophoto['country'])) $mail_text .= "загородная: ".$nophoto['country']."<br />\r\n";
}


if(filesize($error_log)>10){
    $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
    $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
    $error_log_text .= '</font>';
} else $error_log_text = "";
echo $mail_text;
$mail_text= iconv("UTF-8", "CP1251//TRANSLIT", $mail_text);
$error_log_text= iconv("UTF-8", "CP1251//TRANSLIT", $error_log_text);
if($mail_text!=''){//отсылка программеру со всеми типами ошибок
    // перевод письма в кодировку мейлера
    $html = $mail_text;//.$error_log_text;
   
    if( !class_exists( 'Sendpulse' ) ) require_once( "includes/class.sendpulse.php" );
   
    // инициализация шаблонизатора
    $mailer_title = iconv('UTF-8', $mailer->CharSet, 'Обработка формата excel. '.date('Y-m-d H:i:s'));
    $emails = array(
        array( 'name' => '', 'email'=> 'web@bsn.ru' )
    );
    if(!empty( $agency_info['email_service'] ) ) $emails[] = array( 'name' => '', 'email'=> $agency_info['email_service'] );
    //отправка письма
    $sendpulse = new Sendpulse( );
    $result = $sendpulse->sendMail( $mailer_title, $html, false, false, $mailer_title, 'bsnexcel@bsn.ru', $emails );
 

    $emails = array(
        array( 'name' => '', 'email'=> 'web@bsn.ru' )
        ,array( 'name' => '', 'email'=> 'd.salova@bsn.ru' )
    );

    if( !empty( $agency['email'] ) ) $emails[] = array( 'name' => '', 'email'=> $agency['email'] );     //отправка письма ответственному менеджеру
    
    if(empty($agency['id_tarif']) && !empty($agency['email_service']) && Validate::isEmail($agency['email_service'])) $emails[] = array( 'name' => '', 'email'=> $agency['email_service'] ) ;   //отправка письма агентству
    if(empty($agency['id_tarif']) && !empty($agency['admin_email']) && Validate::isEmail($agency['admin_email'])) $emails[] = array( 'name' => '', 'email'=> $agency['admin_email'] );          //отправка письма админу агентства

    $result = $sendpulse->sendMail( $mailer_title, $html, false, false, $mailer_title, 'bsnexcel@bsn.ru', $emails );
}
?>