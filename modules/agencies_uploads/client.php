<?php
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.robot.php');
require_once('includes/robot_functions.php');
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array

Response::SetString('page_type', 'agencies_uploads');   //лайфхак для отображения активного элемента меню
//не показывать верхний баннер
Response::SetBoolean('not_show_top_banner',true);
//редирект с главной на звонки
if(!empty($this_page->module_parameters['redirect']))  Host::Redirect('/members/conversions/calls/');
//редирект на новую страницу с /office/
if( strstr( $this_page->real_url, '/office/' ) != '' ) Host::Redirect( str_replace( '/office/', '/objects/', $this_page->real_url ) );
// определяем экшн   
$action = empty($this_page->page_parameters[0]) ? "" : (empty($ajax_action) ? $this_page->page_parameters[0]: $ajax_action);
if($action!='pdf' && ((empty($auth->id_agency) || $auth->agency_admin == 2)  && $auth->id_group!=10 && $auth->id_group!=101)) {
    $this_page->http_code = 403;
}

else {   
    switch($action){
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // тестирование ссылки
       ////////////////////////////////////////////////////////////////////////////////////////////////   
        case 'test_link':
            $link = Request::GetString('link', METHOD_POST);
            //запись новой ссылки агентства
            $db->query("UPDATE ".$sys_tables['agencies']." SET xml_link = ? WHERE id = ?", $link, $auth->id_agency);
            //запуск нового процесса
            $res = $db->query("INSERT INTO ".$sys_tables['processes']." SET id_agency = ?, type = ?, status = ?", $auth->id_agency, 1, 1);
            $process_id = $db->insert_id;
            $ajax_result['ok'] = $res;
            $ajax_result['id'] = $process_id;
            //скачивание ссылки
            $filename = downloadXmlFile("bn",$auth->agency_title,$link,$auth->id,false,Config::Get('xml_file_folders/downloads'), true)[0];
            if(empty($filename)) $db->query("UPDATE ".$sys_tables['processes']." SET log = CONCAT (log,'\n','<span class=\"red\">Файл недоступен</span>') WHERE id = ?", $process_id);
            else {
                //анализ файла
                $db->query("UPDATE ".$sys_tables['processes']." SET log = CONCAT (log,'Загрузка файла: <span class=\"green\">ОК</span>','\n','Анализ файла: ') WHERE id = ?", $process_id);
                //счетчики объектов
                $counter = array('live_sell'=>0,            'live_rent'=>0,             'commercial_sell'=>0,            'commercial_rent'=>0,          'build'=>0,            'country_sell'=>0,            'country_rent'=>0, 
                                 'total'=>0
                );
                $cian_types = array('flats_rent','flats_for_sale','commerce','suburbian');
                //читаем в строку нужный файл
                $contents = file_get_contents($filename);
                $xml_str = xml2array($contents);        
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
                        preg_match_all("#internal-id\=\"([a-zA-Z0-9\-\_]{1,})\"#sui", $contents, $internal_ids);
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
                    case !empty($xml_str['feed']['object'][0]):
                        $file_type = 'Cian_new';
                        $values_array = $xml_str['feed']['object'];
                        $robot = new CianNewXmlRobot($id_user); 
                        $info_source = 13;
                        break;
                    default:
                        //процесс окончен
                        $db->query("UPDATE ".$sys_tables['processes']." SET status = ?, log = CONCAT (log,'<span class=\"red\">файл неизвестного формата</span>') WHERE id = ?", 2, $process_id);
                        break;
                }
                if(!empty($file_type)){
                     $db->query("UPDATE ".$sys_tables['processes']." SET log = CONCAT (log,'<b>".$file_type." XML</b>') WHERE id = ?", $process_id);
                    //список по типам недвижимости + сделки
                    foreach($values_array as $key=>$values){
                        foreach($values as $k=>$val) {
                            if(!is_array($val))  $values[strtolower($k)] = $val;
                            else {
                                foreach($val as $kv=>$ki){
                                    if(!empty($ki)) {
                                        if(!is_array($ki))  $values[$k.'_attr'][$kv] = $ki;
                                        else foreach($ki as $kkv=>$kki) if(!empty($kki) && !is_array($kki)) $values[$k.'_attr'][$kkv] = $kki;
                                    }
                                }
                            }
                        }

                        $fields = $robot->getConvertedFields($values, false, false, true);
                        if(!empty($fields['rent'])) ++$counter[$robot->estate_type.($robot->estate_type!='build'?($fields['rent']==2?'_sell':'_rent'):"")];
                    }

                    $text_counters = "
                        <span class=\"text\">- жилая (продажа): ".$counter['live_sell']."
                        - жилая (аренда): ".$counter['live_rent']."
                        - стройка: ".$counter['build']."
                        - коммерческая (продажа): ".$counter['commercial_sell']."
                        - коммерческая (аренда): ".$counter['commercial_rent']."
                        - загородная (продажа): ".$counter['country_sell']."
                        - загородная (аренда): ".$counter['country_rent']."</span>";
                    $db->query("UPDATE ".$sys_tables['processes']." SET log = CONCAT (log,'".$text_counters."') WHERE id = ?", $process_id);
                }
            }
            //процесс окончен
            $db->query("UPDATE ".$sys_tables['processes']." SET status = ?, log = CONCAT (log,'\n','','') WHERE id = ?", 2, $process_id);
            // удаление скаченного фалйа
            if(file_exists($filename)) unlink($filename);
            
            break;
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // изменение статуса выгрузки
       ////////////////////////////////////////////////////////////////////////////////////////////////   
        case 'status_change':
            $status = Request::GetString('status', METHOD_POST);
            $db->query("UPDATE ".$sys_tables['agencies']." SET xml_status = ?, can_download = 2 WHERE id = ?", empty($status) || $status == 2 ? 2 : 1, $auth->id_agency);
            break;       
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // изменение статуса выгрузки сейчас
       ////////////////////////////////////////////////////////////////////////////////////////////////   
        case 'download_status':
            $status = Request::GetString('status', METHOD_POST);
            $db->query("UPDATE ".$sys_tables['agencies']." SET can_download = ? WHERE id = ?", $status == 'true' ? 2 : 1, $auth->id_agency);
            break;       
        ////////////////////////////////////////////////////////////////////////////////////////////////
       // изменение времени выгрузки 
       ////////////////////////////////////////////////////////////////////////////////////////////////   
        case 'time_change':
            //ближайшее время выгрузки
            $current_hour =Request::GetString('hour', METHOD_POST);
            $current_minute =Request::GetString('minute', METHOD_POST);
            $item = $db->fetch("SELECT * FROM ".$sys_tables['agencies']." WHERE id!=? AND HOUR(xml_time) = ? AND MINUTE(xml_status) = ? AND  xml_status = 1", $auth->id_agency, $current_hour, $current_minute);
            if(empty($item)) $time = $current_hour.':'.$current_minute.':00';
            else $time = $item['xml_time'];
            
            if(!empty($time)){
                $item = $db->fetch("SELECT * FROM ".$sys_tables['agencies']." WHERE id = ?", $auth->id_agency);
                if(empty($item['xml_link'])) $ajax_result['error_text'] = 'Невозможно изменить време выгрузки когда не указан файл выгрузки';
                else {
                    $status = Request::GetString('download_status', METHOD_POST);
                    $db->query("UPDATE ".$sys_tables['agencies']." SET xml_time = ?, can_change_time = ?, can_download = ? WHERE id = ?", $time, !empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'closest' ? 2 : $item['can_change_time'], empty($status) || $status == 'false' ? 2 : 1, $auth->id_agency);
                    //подсчет времени, оставшегося до выгрузки 
                    $datetime1 = new DateTime($time);
                    $datetime2 = new DateTime("now");
                    $interval = $datetime2->diff($datetime1);
                    if($item['can_change_time'] == 2 || $interval->invert == 1) $datetime1->add(new DateInterval('P1D'));
                    $interval1 = $datetime2->diff($datetime1);
                    $days = $interval1->d > 0 ? $interval1->d*24 : 0; 
                    $ajax_result['hour'] = $interval1->h > 0 || $days > 0 ? $interval1->h+$days : 0;             
                    $ajax_result['minute'] = $interval1->i > 0 ? $interval1->i : '';                    
                    $ajax_result['hour_text'] = $interval1->h > 0 || $days > 0 ? (makeSuffix(($interval1->h+$days), 'час',array('','а','ов'))) : '';                    
                    $ajax_result['minute_text'] = $interval1->i > 0 ? makeSuffix($interval1->i, 'минут',array('а','ы','')) : '';                    
                    $ajax_result['ok'] = true;
                }
            }
            break;
        ////////////////////////////////////////////////////////////////////////////////////////////////
       // определение свободных интервалов времени 
       ////////////////////////////////////////////////////////////////////////////////////////////////   
        case 'minutes':
             $hour = Request::GetString('hour', METHOD_POST);
            //список времени выгрузок других агентств
            $list = $db->fetchall("SELECT MINUTE(`xml_time`) as  xml_time FROM ".$sys_tables['agencies']." WHERE id!=? AND HOUR(xml_time) = ? AND xml_status = 1 ORDER BY MINUTE(xml_time)", false, $auth->id_agency, $hour);
            $alowed_times_array = array(00, 10, 20, 30, 40, 50);
            $disalowed_times_array = [];
            foreach($list as $k=>$item) $disalowed_times_array[] = $item['xml_time'];
            $list = array_diff ( $alowed_times_array , $disalowed_times_array);
            if($empty($list)) {
                $ajax_result['ok'] = true;
                $ajax_result['list'] = $list;
            }
            break;
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // получение информации о процессе
       ////////////////////////////////////////////////////////////////////////////////////////////////   
        case 'process_info':
            $process_id = Request::GetInteger('process', METHOD_POST);
            if(!empty($process_id)){
                $item = $db->fetch("SELECT * FROM ".$sys_tables['processes']." WHERE id = ?", $process_id);
                //закончился процесс
                $ajax_result['status'] = $item['status'];
                $ajax_result['log'] = nl2br($item['log']);
                $ajax_result['type'] = $item['type'];
                if($item['type']==2 && $item['total_amount']>0) $ajax_result['percentage'] = Convert::ToInt(($item['current_amount']/$item['total_amount'])*100);
                else $ajax_result['percentage'] = 0;
                $db->query("UPDATE ".$sys_tables['processes']." SET full_log = ?, log = '' WHERE id = ?", $item['full_log'].$item['log'], $process_id);
                //вывод полного лога
                if($item['status'] == 2) $ajax_result['log'] = nl2br($item['full_log'].$item['log']);
            }
            break;
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // генерация PDF отчета
       ////////////////////////////////////////////////////////////////////////////////////////////////
        case 'pdf':
            $id = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false;
            if(empty($id)) die();
            //суперамдинам разрешен просмотр
            if($auth->id_group==10 || $auth->id_group==101) $item = $db->fetch("SELECT ".$sys_tables['processes'].".*,
                                                                                  DATE_FORMAT(".$sys_tables['processes'].".`datetime_start`,'%e %M %Y') as normal_date,
                                                                                  ".$sys_tables['users'].".id as id_user,
                                                                                  LEFT(".$sys_tables['agencies_photos'].".name,2) as `subfolder`,
                                                                                  ".$sys_tables['agencies_photos'].".name as photo_name
                                                                                FROM ".$sys_tables['processes']." 
                                                                                RIGHT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['processes'].".id_agency
                                                                                RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id 
                                                                                LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies_photos'].".id= ".$sys_tables['agencies'].".id_main_photo
                                                                                WHERE ".$sys_tables['processes'].".status = ? AND ".$sys_tables['processes'].".type = ? AND ".$sys_tables['processes'].".id = ? AND ".$sys_tables['users'].".agency_admin = 1",  2, 2, $id);
            else $item = $db->fetch("SELECT ".$sys_tables['processes'].".*,
                                            DATE_FORMAT(".$sys_tables['processes'].".`datetime_start`,'%e %M %Y') as normal_date,
                                            ".$sys_tables['users'].".id as id_user,
                                            LEFT(".$sys_tables['agencies_photos'].".name,2) as `subfolder`,
                                            ".$sys_tables['agencies_photos'].".name as photo_name
                                      FROM ".$sys_tables['processes']."
                                      RIGHT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['processes'].".id_agency
                                      RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id 
                                      LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies_photos'].".id= ".$sys_tables['agencies'].".id_main_photo
                                      WHERE ".$sys_tables['processes'].".id_agency = ?  AND ".$sys_tables['processes'].".status = ? AND ".$sys_tables['processes'].".type = ? AND ".$sys_tables['processes'].".id = ? AND ".$sys_tables['users'].".agency_admin = 1", $auth->id_agency, 2, 2, $id);
            if(empty($item)) {
                //менеджер агентства
                $item = $db->fetch("         
                               SELECT          
                                      ".$sys_tables['processes'].".*,
                                      DATE_FORMAT(".$sys_tables['processes'].".`datetime_start`,'%e %M %Y') as normal_date,
                                      ".$sys_tables['users'].".id as id_user,
                                      LEFT(".$sys_tables['agencies_photos'].".name,2) as `subfolder`,
                                      ".$sys_tables['agencies_photos'].".name as photo_name
                               FROM ".$sys_tables['agencies']."
                               RIGHT JOIN ".$sys_tables['processes']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['processes'].".id_agency
                               RIGHT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                               RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                               LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies_photos'].".id= ".$sys_tables['agencies'].".id_main_photo
                               WHERE ".$sys_tables['managers'].".bsn_id_user = ? AND ".$sys_tables['processes'].".id = ? AND ".$sys_tables['users'].".agency_admin = 1", $auth->id, $id
                );                 
                if(empty($item)){
                    $this_page->http_code = 403;
                    break;
                }
            }
            
            $img = ROOT_PATH.'/'.Config::Get('img_folders/news').'/sm/'.$item['subfolder'].'/'.$item['photo_name'];
            $agency = $db->fetch("SELECT * FROM ".$sys_tables['agencies']." WHERE id = ?", $item['id_agency']);
            require_once('includes/fpdf/fpdf.php');
            $pdf = new PDF_HTML();
            $pdf->AddFont('roboto','','Roboto-Light.php');
            $pdf->AddFont('roboto','L','RobotoCondensed-Light.php');
            $pdf->AddFont('roboto','B','RobotoCondensed-Regular.php');
            $pdf->AddFont('roboto','I','RobotoCondensed-Italic.php');
            $pdf->AddFont('roboto','BI','RobotoCondensed-BoldItalic.php');
            $pdf->AddPage();
            $pdf->SetFont('roboto','',14); 
            if(file_exists($img) && !empty($item['photo_name'])) $pdf->Image('http://st1.bsn.ru/'.Config::Get('img_folders/news').'/sm/'.$item['subfolder'].'/'.$item['photo_name'],160,10,-110);
            $title = "Компания: ".$agency['title']."<br />ID: ".$item['id_user']."<br />Дата: ".$item['normal_date']." г.<br />___________________________________<br />" ;
            $item['full_log'] = nl2br(stripslashes($title.$item['full_log']));
            $pdf->WriteHTML(iconv('UTF-8','CP1251',$item['full_log']));
            $pdf->Output( "report-".createCHPUTitle($auth->agency_title)."".'-'.createCHPUTitle($item['normal_date']).".pdf", "I" );
            die();
            break;
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // главная страница
       ////////////////////////////////////////////////////////////////////////////////////////////////
        default:
            $GLOBALS['js_set'][] = '/modules/agencies_uploads/script.js';
            $GLOBALS['css_set'][] = '/modules/agencies_uploads/style.css';
            $GLOBALS['css_set'][] = '/css/style-cabinet.css';

            $item = $db->fetch("SELECT 
                                    *, 
                                    CONCAT(HOUR(`xml_time`), MINUTE(`xml_time`)) as xml_formatted_time, 
                                    MINUTE(`xml_time`) as  minute,  
                                    HOUR(`xml_time`) as hour                                     
                                FROM ".$sys_tables['agencies']." 
                                WHERE id = ?", 
                                $auth->id_agency
            );
            if(empty($item)) {
                $this_page->http_code = 403;
                break;
            }
            Response::SetArray('item', $item);
            Response::SetBoolean('not_show_top_banner',true);
            //подсчет времени, оставшегося до выгрузки 
            $datetime1 = new DateTime($item['xml_time']);
            $datetime2 = new DateTime("now");
            $interval = $datetime2->diff($datetime1);
            if($item['can_change_time'] == 2 || $interval->invert == 1) $datetime1->add(new DateInterval('P1D'));
            $interval1 = $datetime2->diff($datetime1);
            Response::SetString('time_left', ($interval1->h > 0 ? ($interval1->h.' '.makeSuffix($interval1->h, 'час',array('','а','ов'))) : '') . ' '. ($interval1->i > 0 ? $interval1->i.' '.makeSuffix($interval1->i, 'минут',array('а','ы','')) : '') );

            //получение активного процесса выгрузки объектов
            $process = $db->fetch("SELECT * FROM ".$sys_tables['processes']." WHERE status = 1 AND id_agency = ? AND type = 2", $auth->id_agency);
            if(!empty($process)) Response::SetArray('process', $process);
            
            //архив отчетов
            $reports = $db->fetchall("SELECT 
                                            *,
                                            IF(YEAR(`datetime_start`) < Year(CURDATE()),DATE_FORMAT(`datetime_start`,'%e.%m.%y'),DATE_FORMAT(`datetime_start`,'%e.%m')) as normal_date
                                            
                                      FROM ".$sys_tables['processes']." 
                                      WHERE 
                                        status = 2 AND id_agency = ? AND type = 2 ORDER BY id DESC", 
                                        false, $auth->id_agency
            );
            if(!empty($reports)) {
                foreach($reports as $k=>$item) $reports[$k]['report_size'] = formatSize(mb_strlen($item['full_log']) + 385000);
                Response::SetArray('reports', $reports);
            }
            
            //список времени выгрузок других агентств
            $list = $db->fetchall("SELECT 
                                        id, 
                                        MINUTE(`xml_time`) as  minutes,  
                                        HOUR(`xml_time`) as hours, 
                                        CONCAT(HOUR(`xml_time`),'-', MINUTE(`xml_time`)) as xml_time 
                                   FROM ".$sys_tables['agencies']." 
                                   WHERE id!=? AND xml_status = 1 AND ( MINUTE(`xml_time`) > 0 OR HOUR(`xml_time`) > 0 )
                                   ORDER BY xml_time", false, $auth->id_agency);
            $minutes_list = array(00, 10, 20, 30, 40, 50);
            $hours_list = array(0, 1, 2, 3, 4, 5, 6, 7);
            $alowed_times_array = $all_time = [];
            $key = 0;
            foreach($hours_list as $hour) {
                foreach($minutes_list as $minute) {
                    if($minute > 0 || $hour > 0 ){
                        if(empty($list) || !Convert::arraySearchValueByKey($list, $hour.'-'.$minute, 'xml_time')) {
                            $alowed_times_array[$key]['text'] = $hour.'-'.$minute;
                            $alowed_times_array[$key]['hour'] = $hour;
                            $alowed_times_array[$key]['minute'] = $minute;
                            ++$key;
                        }
                    }
                }
            }
            
            Response::SetArray('time_list', $alowed_times_array);
            $module_template = "main.html";
            
            break;
    }
}
?>