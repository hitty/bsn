<?php
require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
require_once('includes/class.moderation.php');
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
$GLOBALS['js_set'][] = '/js/main.js';

$this_page->manageMetadata(array('title'=>'Модерация объектов'));
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['id'] = Request::GetInteger('f_id',METHOD_GET);
if(!empty($filters['id'])) {
    $get_parameters['f_id'] = $filters['id'];
}
$filters['id_user'] = Request::GetInteger('f_user_id',METHOD_GET);
if(!empty($filters['id_user'])) {
    $get_parameters['f_user_id'] = $filters['id_user'];
}


// определяем запрошенный экшн

$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

$ajax_action = Request::GetString('action', METHOD_POST);

if($ajax_mode && !empty($ajax_action)) $action = 'ajax';

$selector = (!empty($this_page->page_parameters[2]) ? $this_page->page_parameters[2] : false);
if(!empty($selector) && preg_match("/[A-z]+\_[0-9]+/si",$selector)) list($estate,$id) = explode('_',$selector);

// обработка общих action-ов 
switch(true){
    case $action == 'ajax':
        switch($ajax_action){
            case 'geoitems':
                // addrselector:: иерархический список геопозиций от нулевого ID до указанного + районы и метро
                $item_id = Request::GetInteger('item_id', METHOD_POST);
                $district_id = Request::GetString('district_id', METHOD_POST);
                $subway_id = Request::GetString('subway_id', METHOD_POST);
                $multiselect = Request::GetBoolean('multiselect', METHOD_POST);
                if($item_id==0){
                    $info = array('id'=>0, 'aoguid'=>'', 'id_region'=>0, 'id_area'=>0, 'id_city'=>0, 'id_place'=>0, 'id_street'=>0);
                } else {
                    $info = $db->fetch("SELECT * FROM ".$sys_tables['geodata']."
                                        WHERE id=?", $item_id);
                }
                if(empty($info)){
                    $ajax_result['error'] = 'can not find element by item_id';
                    break;
                }
                // определение геоданных объекта
                $geodata = $db->fetchall("
                    SELECT ".$sys_tables['geodata'].".*,
                           ".$sys_tables['districts'].".title as district_title
                    FROM ".$sys_tables['geodata']."       
                    LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables['geodata'].".id_district
                    WHERE a_level < 5 AND (
                              (id_region=? AND id_area=? AND id_city=? AND id_place=?)
                           OR (id_region=? AND id_area=? AND id_city=? AND id_place=0)
                           OR (id_region=? AND id_area=? AND id_city=0 AND id_place=0)
                           OR (id_region=? AND id_area=0 AND id_city=0 AND id_place=0)
                       )
                    ORDER BY a_level"
                    , false
                    , $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place']
                    , $info['id_region'], $info['id_area'], $info['id_city']
                    , $info['id_region'], $info['id_area']
                    , $info['id_region']
                );
                $geolocation = array(array('id'=>0, 'title'=>'Россия'));
                while(!empty($geodata)){
                    $location = array_shift($geodata);
                    $geolocation[] = array(
                        'id'=>$location['id'],
                        'title'=>$location['offname'].' '.$location['shortname'],
                        'id_region'=>$location['id_region'],
                        'id_area'=>$location['id_area'],
                        'id_city'=>$location['id_city'],
                        'id_district'=>$location['id_district'],
                        'district_title'=>$location['district_title']
                    );
                }
                $ajax_result['ok'] = true;                
                $ajax_result['geoitems_query'] = '';
                $ajax_result['items'] = $geolocation;
                // определение района
                $districts = $db->fetchall("SELECT * FROM ".$sys_tables['districts']."
                                            WHERE parent_id=? ORDER BY title", false, $item_id);
                if(!empty($districts)){
                    $ajax_result['district'] = array('items'=>[],'selected'=>[]);
                    foreach($districts as $distr){
                        $item = array('id'=>$distr['id'], 'title'=>$distr['title']);
                        if(!empty($multiselect) && in_array($distr['id'],explode(',',$district_id))){
                            $item['selected'] = true;
                            $ajax_result['district']['selected'][] = $item;
                        }
                        $ajax_result['district']['items'][] = $item;
                    }
                }
                // определение метро
                $subways = $db->fetchall("SELECT * FROM ".$sys_tables['subways']."
                                          WHERE parent_id=? ORDER BY title", false, $item_id);
                if(!empty($subways)){
                    $ajax_result['subway'] = array('items'=>[]);
                    foreach($subways as $metro){
                        $item = array('id'=>$metro['id'], 'title'=>$metro['title']);
                        if(!empty($multiselect) && in_array($metro['id'],explode(',',$subway_id))){
                            $item['selected'] = true;
                            $ajax_result['subway']['selected'][] = $item;
                        }
                        $ajax_result['subway']['items'][] = $item;
                    }
                }
                break;
            case 'geolist':
                // addrselector:: список геопозиций - потомков геопозиции с указанным ID
                $item_id = Request::GetInteger('item_id', METHOD_POST);
                if($item_id==0){
                    $info = array('id'=>0, 'aoguid'=>'');
                } else {
                    $info = $db->fetch("SELECT * FROM ".$sys_tables['geodata']."
                                        WHERE id=?", $item_id);
                }
                if(empty($info)){
                    $ajax_result['error'] = 'can not find element by item_id';
                    break;
                }
                $geoitems = $db->fetchall("
                    SELECT * FROM ".$sys_tables['geodata']."
                    WHERE parentguid = ? AND a_level<5
                    ORDER BY offname, shortname"
                    , false
                    , $info['aoguid']
                    
                );
                $geolist = [];
                foreach($geoitems as $location){
                    $geolist[] = array('id'=>$location['id'], 'title'=>$location['offname'].' '.$location['shortname']);
                }
                $ajax_result['ok'] = true;
                $ajax_result['geolist_query'] = '';
                $ajax_result['items'] = $geolist;
                break;
            case 'streets_list':
                // список улиц для автокомплита
                $geo_id = Request::GetInteger('geo_id', METHOD_POST);
                if($geo_id==0) $ajax_result['ok'] = false;
                else {
                    $info = $db->fetch("SELECT `aoguid` FROM ".$sys_tables['geodata']."
                                        WHERE id=?", $geo_id);
                    $search_str = Request::GetString('search_string', METHOD_POST);
                    $list = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']." WHERE parentguid=? AND a_level=5 AND offname LIKE ? ORDER BY offname LIMIT ?", false, $info['aoguid'], "%".$search_str."%", 10);
                    $ajax_result['ok'] = true;
                    $ajax_result['list'] = $list;
                }
                break;
        }
    break;
    
    // пропускаем
    case $action == 'pass':
        $res = $db->querys("UPDATE ".$sys_tables[$estate]." SET published = 1 WHERE id = ?", $id);
        
        if($ajax_mode) $ajax_result = array('ok' => $res && $db->affected_rows, 'ids'=>array($id));
        
        break;
    
    // возвращаем
    case $action == 'stop':     
        $res = $db->querys("UPDATE ".$sys_tables[$estate]." SET published = 4 WHERE id = ?", $id);
        
        if($ajax_mode) $ajax_result = array('ok' => $res && $db->affected_rows, 'ids'=>array($id));
        
        break;
    
    //смотрим-редактируем
    case $action == 'edit' && !empty($id):
        
        $GLOBALS['js_set'][] = '/modules/estate_moderation/admin.js';
        $GLOBALS['js_set'][] = '/modules/estate/ajax_actions.js';
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/modules/members/form_estate.js';
        $GLOBALS['css_set'][] = '/css/autocomplete.css';            
        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
        $GLOBALS['css_set'][] = '/css/jquery.addrselector.css';
        $GLOBALS['js_set'][] = '/js/form.validate.js';
        
        $estate_suffix = "";
        
        $module_template = '/modules/estate/templates/admin.'.$estate.'.edit.html';
        
        $info = $db->fetch("SELECT main.*,
                                   ".($estate!='country'?"IFNULL(distr.title,'') as district_title,":"")."
                                   IFNULL(subway.title,'') as subway_title
                            FROM ".$sys_tables[$estate]." main
                            ".($estate!='country'?"LEFT JOIN ".$sys_tables['districts']." distr ON distr.id=main.id_district":"")."
                            LEFT JOIN ".$sys_tables['subways']." subway ON subway.id=main.id_subway
                            WHERE main.id=?", $id);
        
        
        $geodata = $db->fetchall("
            SELECT * FROM ".$sys_tables['geodata']."
            WHERE ( (a_level > 1 AND a_level < 5 AND id_region = 47 ) OR (a_level < 5 AND id_region = 78) ) AND (
                      (id_region=? AND id_area=? AND id_city=? AND id_place=?)
                   OR (id_region=? AND id_area=? AND id_city=? AND id_place=0)
                   OR (id_region=? AND id_area=? AND id_city=0 AND id_place=0)
                   OR (id_region=? AND id_area=0 AND id_city=0 AND id_place=0)
               )
            ORDER BY a_level"
            , false
            , $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place']
            , $info['id_region'], $info['id_area'], $info['id_city']
            , $info['id_region'], $info['id_area']
            , $info['id_region']
        );
        
        $geolocation = $location = [];
        while(!empty($geodata)){
            $location = array_shift($geodata);
            if(empty($geodata)) {
                $mapping[$estate]['geo_id']['value'] = $location['id'];
                $mapping[$estate]['txt_region']['value'] = $location['shortname_cut'].'. '.$location['offname'];
            }  else  $geolocation[] = $location['offname'].' '.$location['shortname'];
        }
        
        $mapping[$estate]['geolocation']['value'] = implode(', ',$geolocation);
        
        if(!empty($info['id_street'])) {
            $street = $db->fetch("
                SELECT `offname`, `shortname` FROM ".$sys_tables['geodata']."
                WHERE a_level = 5 AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place'], $info['id_street']
            );
            $info['txt_street'] = $street['offname'].' '.$street['shortname'];
        }
        
        if(!empty($info['id_district'])) {
            $district = $db->fetch("SELECT title FROM ".$sys_tables['districts']." WHERE id=?",$info['id_district']);
            $info['txt_district'] = $district['title'];
        } elseif($info['id_region']==47){
            $info['txt_district'] = '-';
            $mapping[$estate]['txt_district']['disabled'] = true;
        }
        
        if(!empty($info['id_subway'])) {
            $subway = $db->fetch("SELECT title FROM ".$sys_tables['subways']." WHERE id=?",$info['id_subway']);
            $info['txt_subway'] = $subway['title'];
        }
        
        foreach($info as $key=>$field){
            if(!empty($mapping[$estate][$key])) $mapping[$estate][$key]['value'] = $info[$key];
        }
        
        
        if($estate == 'live'){
            // квартира/комната
            if($mapping[$estate]['id_type_object']['value']==2){
                $mapping[$estate]['rooms_sale']['hidden'] = false;
            } else {
                $mapping[$estate]['rooms_sale']['hidden'] = true;
                //учитываем студии
                if($mapping[$estate]['id_type_object']['value']==1){
                    $mapping[$estate]['rooms_sale']['min'] = 0;
                    $mapping[$estate]['rooms_total']['min'] = 0;
                    $mapping[$estate]['rooms_sale']['allow_empty'] = true;
                    $mapping[$estate]['rooms_total']['allow_empty'] = true;
                }
            }
            // продажа/аренда
            if($mapping[$estate]['rent']['value']==1){
                $mapping[$estate]['rent_duration']['hidden'] = false;
                $mapping[$estate]['by_the_day']['hidden'] = false;
            } else {
                $mapping[$estate]['rent_duration']['hidden'] = true;
                $mapping[$estate]['by_the_day']['hidden'] = true;
            }
        }
        elseif($estate == 'build'){
            // рассрочка
            if($mapping[$estate]['installment']['value']!=1){
                $mapping[$estate]['installment_months']['hidden'] = false;
            } else {
                $mapping[$estate]['installment_months']['hidden'] = true;
            }
            //учитываем студии
            $mapping[$estate]['rooms_sale']['min'] = 0;
            $mapping[$estate]['rooms_total']['min'] = 0;
            $mapping[$estate]['rooms_sale']['allow_empty'] = true;
            $mapping[$estate]['rooms_total']['allow_empty'] = true;
        }
        elseif($estate == 'commercial'){
            // продажа/аренда
            if($mapping[$estate]['rent']['value']==1){
                $mapping[$estate]['rent_duration']['hidden'] = false;
            } else {
                $mapping[$estate]['rent_duration']['hidden'] = true;
            }
        }
        // формирование дополнительных данных для формы (не из основной таблицы)
        $sprav_list = array(
            'id_building_type' => 'building_types',
            'id_toilet' => 'toilets',
            'id_balcon' => 'balcons',
            'id_elevator' => 'elevators',
            'id_enter' => 'enters',
            'id_window' => 'windows',
            'id_floor' => 'floors',
            'id_hot_water' => 'hot_waters',
            'id_facing' => 'facings',
            'id_heating' => 'heatings',
            'id_river' => 'rivers',
            'id_gas' => 'gases',
            'id_garden' => 'gardens',
            'id_bathroom' => 'bathrooms',
            'id_building_progress' => 'building_progresses',
            'id_electricity' => 'electricities',
            'id_way_type' => 'way_types',
            'id_build_complete' => 'build_complete',
            'id_housing_estate' => 'housing_estates',
            'id_business_center' => 'business_centers',
            'id_cottage' => 'cottages',
            'id_developer_status' => 'developer_statuses',
            'id_ownership' => 'ownerships',
            'id_construct_material' => 'construct_materials',
            'id_water_supply' => 'water_supplies',
            'id_roof_material' => 'roof_materials'
        );
        foreach($sprav_list as $sprav_field=>$sprav_table){
            if(isset($mapping[$estate][$sprav_field])){
                $sprav_rows = $db->fetchall("SELECT id,title FROM ".$sys_tables[$sprav_table]." ORDER BY title");
                foreach($sprav_rows as $key=>$val){
                    $mapping[$estate][$sprav_field]['values'][$val['id']] = $val['title'];
                }
            }
        }

        if($estate != 'build'){
            $type_objects = $db->fetchall("SELECT id,title FROM ".$sys_tables['type_objects_'.$estate]." ORDER BY title");
            foreach($type_objects as $key=>$val){
                $mapping[$estate]['id_type_object']['values'][$val['id']] = $val['title'];
            }
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);
        
        // если была отправка формы - начинаем обработку
        if(empty($post_parameters['submit'])){
            
            $blocking = $db->querys("UPDATE ".$sys_tables[$estate]."
                                    SET blocking_time=ADDTIME(NOW(), '00:15:00'), blocking_id_user=?
                                    WHERE id=?"
                                    , $auth->id
                                    , $id);
            
        } else {
            Response::SetBoolean('form_submit', true); 
            $is_moderated = ($post_parameters['published'] == 4 || $post_parameters['published'] == 1) && $mapping[$estate]["published"] != $post_parameters['published'];
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(isset($mapping[$estate][$key])) $mapping[$estate][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping[$estate]);
            //проверка кол-ва продаваемых комнат только если выбрана комната
            if($estate=='live' && $info['id_type_object']==1 && !empty($errors['rooms_sale'])) unset($errors['rooms_sale']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(isset($mapping[$estate][$key])) $mapping[$estate][$key]['error'] = $value;
            }
            
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                //определение гео данных для полученного geo_id
                if(!empty($mapping[$estate]['geo_id']['value'])){
                    $geolocation = $db->fetch("SELECT id_region,id_area,id_city,id_place FROM ".$sys_tables['geodata']." WHERE `id` = ".$mapping[$estate]['geo_id']['value']);
                    $mapping[$estate]['id_region']['value']     = $geolocation['id_region'];
                    $mapping[$estate]['id_area']['value']       = $geolocation['id_area'];
                    $mapping[$estate]['id_city']['value']       = $geolocation['id_city'];
                    $mapping[$estate]['id_place']['value']      = $geolocation['id_place'];
                    if($mapping[$estate]['id_region']['value'] == '78'){
                        $mapping[$estate]['geolocation']['hidden'] = true;
                    } else {
                         $mapping[$estate]['txt_district']['hidden'] = true;
                    }
                }                        
                                                    
                
                foreach($info as $key=>$field){
                    if(isset($mapping[$estate][$key]['value'])) $info[$key] = $mapping[$estate][$key]['value'];
                }
                if(strlen($info['seller_phone'])<7) $info['seller_phone'] = '';
                
                $moderate = new Moderation($estate,!empty($new_id)?$new_id:$id);
                foreach($mapping[$estate] as $key=>$values)
                    $object_data[$key] = (!empty($values['value'])?$values['value']:0);
                $moderate->moderated_status = $moderate->getModerateStatus($object_data); 
                $moderate->moderated_status = 1;//QWE
                if($moderate->moderated_status > 1){
                    $moderate_text = $db->fetch("SELECT title FROM ".$sys_tables['moderate_statuses']." WHERE id = ".$moderate->moderated_status);
                    Response::SetBoolean('saved', false);
                    Response::SetBoolean('errors', true);
                    Response::SetArray('data_mapping', $mapping[$estate]);
                    Response::SetString('error_text',$moderate_text['title']);
                    $res = false;
                    break;
                }
                elseif($moderate->moderated_status == 1){
                    
                    //если изменили статус модерации, отправляем оповещения
                    if($is_moderated){
                        $data = [];
                        switch($estate){
                            case "build":
                                $item = new EstateItemBuild($id);
                                break;
                            case "live":
                                $item = new EstateItemLive($id);
                                break;
                            case "commercial":
                                $item = new EstateItemCommercial($id);
                                break;
                            case "country":
                                $item = new EstateItemCountry($id);
                                break;
                        }
                        $object_info = $item->getInfo();
                        $data['object_title'] = $item->getTitles()['header'];
                        $data['object_id'] = $id;
                        $data['user_title'] = $object_info['user_name'];
                        $data['object_link'] = $_SERVER['HTTP_HOST']."/".$estate."/".($info['rent'] == 1 ? 'rent' : 'sell')."/".$id."/";
                        $data['moderation_title'] = "какая-то причина такой модерации";
                        $user_email = $object_info['user_email'];
                        $moderation_success = ($post_parameters['published'] == 1);
                        $mailer = new EMailer('mail');
                        
                        $mailer->sendEmail(array($user_email,"hitty@bsn.ru"),
                                           array($data['user_title'],"Тех. поддержка"),
                                           "Ваш объект ".($moderation_success ? "" : "не ")."прошел модерацию на bsn.ru",
                                           "/modules/estate_moderation/templates/mail.user.".($moderation_success ? "success" : "return").".html",
                                           "",
                                           array('letter_data' => $data),
                                           false,
                                           false,
                                           true);
                    }
                    $res = true;
                    //$res = $db->updateFromArray($sys_tables[$estate], $info, 'id');
                } 
                
                Response::SetBoolean('saved', $res);
            } else Response::SetBoolean('errors', true);
        }
        
        $referer = Host::getRefererURL();
        
        Response::SetArray('data_mapping', $mapping[$estate]);
        
        break;
        
    // список
    default:        
        $GLOBALS['js_set'][] = '/modules/estate/ajax_actions.js';
        
        $module_template = 'admin.list.html';
        
        // формирование условий для списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['id'])) $conditions[] = "`id` = ".$filters['id'];
            if(!empty($filters['id_user'])) $conditions[] = "`id_user` = ".$filters['id_user'];
        }
        
        if(empty($filters['m_status']) && !empty($estate_suffix)) $conditions[] = 'id_moderate_status > 1';
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '';
        
        $where = "published = 3 AND ".$sys_tables["users"].".id_agency = 0";
        
        $sql="SELECT  ".$sys_tables["build"].".id,
                      'build' AS estate,
                      IF(".$sys_tables["build"].".rent = 1 , 'rent' , 'sell') AS rent_alias,
                      DATE_FORMAT(".$sys_tables["build"].".date_change,'%H:%i:%s') AS date_change_formatted,
                      CONCAT('#',".$sys_tables["build"].".id_user,' ') AS user_info,
                      ".$sys_tables["build"].".txt_addr AS object_info,
                      (CASE info_source
                          WHEN 2 THEN 'Бесплатный'
                            WHEN 3 THEN 'Промо'
                            WHEN 4 THEN 'Премиум'
                            WHEN 5 THEN 'Обычный оплаченный'
                            WHEN 6 THEN 'VIP'
                            WHEN 7 THEN 'Акция'
                      END) AS object_highlighting
              FROM ".$sys_tables["build"]."
              LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables["build"].".id_user = ".$sys_tables['users'].".id
              WHERE ".$where."
              UNION
              SELECT  ".$sys_tables["live"].".id,
                      'live' AS estate,
                      IF(".$sys_tables["live"].".rent = 1 , 'rent' , 'sell') AS rent_alias,
                      DATE_FORMAT(".$sys_tables["live"].".date_change,'%H:%i:%s') AS date_change_formatted,
                      CONCAT('#',".$sys_tables["live"].".id_user,' ') AS user_info,
                      ".$sys_tables["live"].".txt_addr AS object_info,
                      (CASE info_source
                          WHEN 2 THEN 'Бесплатный'
                          WHEN 3 THEN 'Промо'
                          WHEN 4 THEN 'Премиум'
                          WHEN 5 THEN 'Обычный оплаченный'
                          WHEN 6 THEN 'VIP'
                          WHEN 7 THEN 'Акция'
                      END) AS object_highlighting
              FROM ".$sys_tables["live"]."
              LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables["live"].".id_user = ".$sys_tables['users'].".id
              WHERE ".$where."
              ORDER BY date_change_formatted";
        
        $list = $db->fetchall($sql);
        
        Response::SetArray('list', $list);
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk.'='.$gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>