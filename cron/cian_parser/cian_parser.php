#!/usr/bin/php
<?php
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/comagic/spam_error.log';
$test_performance = ROOT_PATH.'/cron/gen_sitemap/test_performance.log';
file_put_contents($error_log,'');
file_put_contents($test_performance,'');
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
require_once('includes/simple_html_dom.php');    //класс для парсинга html
require_once('includes/class.robot.php');        // класс с функциями робота, нужен для получения адреса
require_once('includes/functions.php');          // функции  из крона
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;     // Photos (работа с графикой)
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");



// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$page_num = 1;
$id_user = 38582;
$phone = "8 (812) 680-35-35";

//удаляем старые объекты
$ids = $db->fetchall("SELECT id FROM ".$sys_tables['build']." WHERE id_user= ".$id_user);
foreach($ids as $key=>$info){
    Photos::DeleteAll('build',$info['id']);
}
$db->query("DELETE FROM ".$sys_tables['build']." WHERE id_user = ".$id_user);

echo "old objects deleted\r\n";

$total_counter = array();

//$url_to_read = "http://www.cian.ru/cat.php?deal_type=sale&engine_version=2&id_user=12497761&offer_type=flat&p=".
$url_to_read = "https://www.cian.ru/cat.php?deal_type=sale&engine_version=2&id_user=12534850&offer_type=flat&p=";
$page_limit = 20;

while($page_num < $page_limit){
    // Create DOM from URL or file
    
    $robot = new BNTxtRobot($id_user);
    $robot->estate_type = 'build';
    /*
    $html = file_get_contents($url_to_read.$page_num);
    file_put_contents('cian_renov.txt',$html);
    */
    
    $html = file_get_html($url_to_read.$page_num);
    if(empty($html)){
        echo "empty page\r\n";
        continue;
    }
    //$html = file_get_html('cian_renov.txt');
    
    //читаем информацию по объектам: название ЖК, адрес, метро, застройщик, срок сдачи
    $counter = 0;
    //читаем информацию поселекторно, для скорости:
    
    $tag_fields = array(
                        'description'=>'div[class="serp-item__description__text js-serp-item-description-text"]',
                        'photo'=>'div[class="serp-item__photo-col"]',
                        'metro'=>'div[class="serp-item__solid serp-item__metro"]',
                        'metro_distance'=>'div[class="serp-item__distance"]',
                        'address'=>'div[class="serp-item__address-precise"]',
                        'cost'=>'div[class="serp-item__price-col"]',
                        'rooms'=>'div[class="serp-item__type-col"]',
                        'square_full'=>'div[class="serp-item__area-col"]',
                        'floor_info'=>'div[class="serp-item__floor-col"]'
                       );
    $tag_children = array(
                            'div[class="serp-item__price-col"]'=>array('cost','cost2meter'),
                            'div[class="serp-item__type-col"]'=>array('rooms','house_type','is_build','build_ending'),
                            'div[class="serp-item__type-col"]'=>array('rooms','house_type','is_build','build_ending'),
                            'div[class="serp-item__area-col"]'=>array('square_full','square_kitchen','square_live'),
                            'div[class="serp-item__floor-col"]'=>array('floor_info','balcony_info','lift_info'),
                         );
    $objects_info = array();
    foreach($tag_fields as $key=>$selector){
        $elements = $html->find($selector);
        //из описания берем только внешний ID
        if($key == 'description'){
            foreach($elements as $element_key => $element){
                $external_id = 0;
                preg_match('/[0-9]+$/sui',trim($element->innertext()),$external_id);
                if(!empty($external_id) && !empty($external_id[0])) $objects_info[] = array('external_id' => $external_id[0]);
            }
        }
        //фотографии - пишем ссылки
        elseif($key == 'photo'){
            $counter = 0;
            foreach($elements as $element_key => $element){
                preg_match('/(?<=\')http.*(?=\')/si',$element->children(0)->attr['style'],$img);
                if(!empty($img) && !empty($img[0])) $objects_info[$counter][$key] = $img[0];
                ++$counter;
            }
        }
        else{
            if(empty($tag_children[$selector])){
                $counter = 0;
                foreach($elements as $element_key => $element){
                    $element_text = trim(strip_tags($element->innertext()));
                    $objects_info[$counter][$key] = preg_replace('/\s+/sui',' ',$element_text);
                    ++$counter;
                }
            }else{
                //если есть потомки с информацией
                $counter = 0;
                foreach($elements as $element_key => $element){
                    $element_children = $element->children();
                    foreach($element_children as $element_children_key=>$element_child){
                        if(empty($tag_children[$selector][$element_children_key])) continue;
                        $element_child_text = trim(strip_tags($element_child->innertext()));
                        $objects_info[$counter][$tag_children[$selector][$element_children_key]] = preg_replace('/\s+/sui',' ',$element_child_text);
                    }
                    ++$counter;
                    
                }
            }
        }
    }
    
    //все прочитали, теперь пишем в базу:
    foreach($objects_info as $key=>$object_info){
        
        $robot->estate_type = $estate_type = 'build';
        
        $robot->fields['external_id'] = $object_info['external_id'];
        $robot->fields['id_subway'] = $robot->getInfoFromTable($sys_tables['subways'],$object_info['metro'],'title')['id'];
        $robot->fields['way_time'] = preg_replace('/[^0-9]/sui','',$object_info['metro_distance']);
        $robot->fields['id_way_type'] = (strstr($object_info['metro_distance'],'трансп') ? 3 : 2);
        $address_array = explode(',',$object_info['address']);
        foreach($address_array as $k=>$i){
            if(strstr($i,'район')){
                $temp = $address_array[1];
                $address_array[1] = $i;
                $address_array[$k] = $temp;
            }
        }
        $object_info['address'] = implode(',',$address_array);
        $robot->getTxtGeodata($object_info['address']);
        //при отсутствии полей, утсанавливаем в 0, чтобы они затерлись
        if(empty($robot->fields['id_street'])) $robot->fields['id_street'] = 0;
        if(empty($robot->fields['id_city'])) $robot->fields['id_city'] = 0;
        if(empty($robot->fields['id_place'])) $robot->fields['id_place'] = 0;
        if(empty($robot->fields['id_district'])) $robot->fields['id_district'] = 0;
        if(empty($robot->fields['id_area'])) $robot->fields['id_area'] = 0;
        if(empty($robot->fields['house'])) $robot->fields['house'] = 0;
        if(empty($robot->fields['corp'])) $robot->fields['corp'] = 0;
        $robot->groupByAddress($robot->estate_type, $robot->fields, true);
        $cost_value = (float)preg_replace('/[^0-9\,\.]/sui','',$object_info['cost']);
        $robot->fields['txt_cost'] = $object_info['cost'];
        $robot->fields['cost'] = (strstr($object_info['cost'],'млн') ? floor($cost_value * 1000000.0) : $cost_value );
        $robot->fields['cost2meter'] = (int)preg_replace('/[^0-9]/sui','',$object_info['cost2meter']);
        if($object_info['rooms'] == 'Студия'){
            if(!$object_info['is_build']){
                $robot->fields['rooms'] = 0;
            } 
            else{
                $robot->fields['rooms_sale'] = 0;
            } 
        }else{
            if(!$object_info['is_build']) $robot->fields['rooms_sale'] = (int)preg_replace('/[^0-9]/sui','',$object_info['rooms']);
            else $robot->fields['rooms_sale'] = (int)preg_replace('/[^0-9]/sui','',$object_info['rooms']);
        }
        $robot->fields['id_building_type'] = $robot->getInfoFromTable($sys_tables['building_types'],$object_info['house_type'],'title')['id'];
        $build_ending = preg_replace('/[^0-9IVX]/sui','',$object_info['build_ending']);
        $robot->fields['id_build_complete'] = $robot->getInfoFromTable($sys_tables['build_complete'],$build_ending,'title')['id'];
        preg_match('/(?<=^|[^0-9])([0-9\.\,]+)(?=$|[^0-9])/sui',$object_info['square_full'],$robot->fields['square_full']);
        $robot->fields['square_full'] = str_replace(',','.',$robot->fields['square_full'][0]);
        
        preg_match('/(?<=^|[^0-9])([0-9\.\,]+)(?=$|[^0-9])/sui',$object_info['square_kitchen'],$robot->fields['square_kitchen']);
        $robot->fields['square_kitchen'] = str_replace(',','.',$robot->fields['square_kitchen'][0]);
        
        preg_match('/(?<=^|[^0-9])([0-9\.\,]+)(?=$|[^0-9])/sui',$object_info['square_live'],$robot->fields['square_live']);
        $robot->fields['square_live'] = str_replace(',','.',$robot->fields['square_live'][0]);
        
        preg_match_all('/[0-9]+/sui',$object_info['floor_info'],$levels);
        $robot->fields['level'] = (!empty($levels[0]) ? $levels[0][0] : 0);
        $robot->fields['level_total'] = (!empty($levels[0]) ? $levels[0][1] : 0);
        $robot->fields['id_balcon'] = $robot->getInfoFromTable($sys_tables['balcons'],$object_info['balcony_info'],'title')['id'];
        $robot->fields['id_elevator'] = $robot->getInfoFromTable($sys_tables['elevators'],$object_info['lift_info'],'title')['id'];
        $robot->fields['date_in'] = date("Y-m-d",time());
        $robot->fields['published'] = 1;
        
        //ищем в базе этот объект: смотрим совпадение улицы, метро, района,
        //формируем условие сравнения
        $conditions = array();
        if (!empty($robot->fields['id_region'])) $conditions[] = "id_region = ".$robot->fields['id_region'];
        if (!empty($robot->fields['id_area'])) $conditions[] = "id_area = ".$robot->fields['id_area'];
        if (!empty($robot->fields['id_district'])) $conditions[] = "id_district = ".$robot->fields['id_district'];
        if (!empty($robot->fields['id_street'])) $conditions[] = "id_street = ".$robot->fields['id_street'];
        if (!empty($robot->fields['txt_addr'])) $conditions[] = "txt_addr = '".$robot->fields['txt_addr']."'";
        if (!empty($robot->fields['level'])) $conditions[] = "level = ".$robot->fields['level'];
        if (!empty($robot->fields['cost'])) $conditions[] = "cost = ".$robot->fields['cost'];
        if (!empty($robot->fields['id_user'])) $conditions[] = "id_user = ".$robot->fields['id_user'];
        
        $conditions = implode(" AND ",$conditions);
        
        $robot->fields['seller_phone'] = $phone;
        
        $robot->fields['date_change'] = date('Y-m-d h.j.s',time());
        $robot->fields['seller_phone'] = Convert::ToPhone("+7 812 680-35-35");
        $robot->fields['seller_name'] = "Реновация";
        $robot->fields['info_source'] = 99;
        $robot->fields['id_user'] = $id_user;
        $img_link = $object_info['photo'];
        unset($robot->fields['photo']);
        
        //ищем ранее загруженный
        $check_object = $db->fetch("SELECT `id`, `id_main_photo`,`published`
                                    FROM ".$sys_tables[($robot->estate_type)]." 
                                    WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",$robot->fields['external_id'], $id_user, 99
                                  );
        //если ничего не нашли, добавляем в базу
        if (true){
            $db->insertFromArray($sys_tables[$estate_type],$robot->fields);
            $robot->fields['id'] = $db->insert_id;
            ++$total_counter['added'];
        }
        else{
            $robot->fields['id'] = $check_object['id'];
            $db->updateFromArray($sys_tables[$estate_type],$robot->fields,'id');
            ++$total_counter['existed'];
        }
        
        if(!empty($img_link)){
            $prefix = "";
            //определение списка фотографий, которых нет в БД
            list($photos['in'],$photos['out']) = $robot->getPhotoList(array($img_link), $robot->fields['id']);
            //удаление фоток (из базы и с сервера), которые не вошли в xml
            $photos_list_in = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                             WHERE `id_parent` = ".$robot->fields['id']."
                                             ".(!empty($photos['in'])?" AND `external_img_src` IN (".implode(',', $photos['in']).")":""),'id');
            $photos_list = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                          WHERE `id_parent` = ".$robot->fields['id']."
                                          ".(!empty($photos_list_in)?" AND `id` NOT IN (".implode(',', array_keys($photos_list_in)).")":""),'id');
            if(!empty($photos_list)){
                foreach($photos_list as $k => $val) Photos::Delete($robot->estate_type,$val['id']);
                $photos_to_delete_ids = implode(',', $photos_list['in']);
                if(!empty($photos_to_delete_ids)) $db->query("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".$photos_to_delete_ids.")");
            }
            //добавляем новую картинку:
            if(!empty($photos['out'])){
                
                $external_img_sources = Photos::MultiDownload($photos['out'], ROOT_PATH.'/'.Config::$values['img_folders'][$robot->estate_type].'/');
                foreach($external_img_sources as $k=>$img) {
                    print_r($img);
                    $photo_add_result = Photos::Add($robot->estate_type, $robot->fields['id'], $prefix, $img['external_img_src'], $img['filename'], false, false, false, Config::Get('watermark_src'));
                    if(!is_array($photo_add_result)) $errors_log['img'][] = $img['external_img_src'];
                }
            }else $db->query("UPDATE ".$sys_tables[$robot->estate_type]." SET id_main_photo = ? WHERE id = ?",array_keys($photos_list_in)[0],$robot->fields['id']);
        }
        
        $inserted_id = $check_object['id'];
            
        $robot->fields = array();
        //$robot->estate_type="";
        ++$total_counter['total'];
        ++$counter;
        
    }
    
    ++$page_num;
    echo $page_num." page processed. added ".$counter.", total: ".$total_counter['total']."\r\n";
}
echo $total_counter['added']." added,\r\n".$total_counter['existed']." updated,\r\n".$total_counter['total']." processed total from ".($page_num-1)." pages\r\n";

$db->query("UPDATE ".$sys_tables['build']." SET id_housing_estate = 2252 WHERE id_user = ? and published = 1 and txt_addr LIKE '%колпин%'",$id_user);
$db->query("UPDATE ".$sys_tables['build']." SET id_housing_estate = 2588 WHERE id_user = ? and published = 1 and txt_addr LIKE '%ковалев%'",$id_user);
?>