<?php
require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
require_once('includes/class.moderation.php');
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
$GLOBALS['js_set'][] = '/js/main.js';
$GLOBALS['css_set'][] = '/css/autocomplete.css';            
$GLOBALS['js_set'][] = '/modules/estate/form_estate.js';            

$this_page->manageMetadata(array('title'=>'Недвижимость'));
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
$filters['type'] = Request::GetInteger('f_type',METHOD_GET);
if(!empty($filters['type'])) {
    $get_parameters['f_type'] = $filters['type'];
}
$filters['title'] = Request::GetString('f_title',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
$filters['rent'] = Request::GetInteger('f_rent',METHOD_GET);
if(!empty($filters['rent'])) {
    $get_parameters['f_rent'] = $filters['rent'];
}
$filters['m_status'] = Request::GetInteger('f_m_status',METHOD_GET);
if(!empty($filters['m_status'])) {
    $get_parameters['f_m_status'] = $filters['m_status'];
}
$filters['published'] = Request::GetInteger('f_published',METHOD_GET);
if($filters['published']===null) $filters['published']=1;
if(!empty($filters['published'])) {
    $get_parameters['f_published'] = $filters['published'];
}
$filters['f_agency'] = Request::GetInteger('f_agency',METHOD_GET);
if(!empty($filters['f_agency'])) {
    $get_parameters['f_agency'] = $filters['f_agency'];
}
$filters['manager'] = Request::GetInteger('f_manager',METHOD_GET);
if(!empty($filters['manager'])) {
    $get_parameters['f_manager'] = $filters['manager'];
}
$filters['developer'] = Request::GetInteger('f_developer',METHOD_GET);
if(!empty($filters['developer'])) {
    $get_parameters['f_developer'] = $filters['developer'];
}
$filters['coords'] = Request::GetInteger('f_coords',METHOD_GET);
if(!empty($filters['coords'])) {
    $get_parameters['f_coords'] = $filters['coords'];
}
$filters['agency_check'] = Request::GetInteger('f_agency_check',METHOD_GET);
if(!empty($filters['agency_check'])) {
    $get_parameters['f_agency_check'] = $filters['agency_check'];
}
$filters['fz_214'] = Request::GetInteger('f_fz_214',METHOD_GET);
if(!empty($filters['fz_214'])) {
    $get_parameters['f_fz_214'] = $filters['fz_214'];
}
$filters['show_phone'] = Request::GetInteger('f_show_phone',METHOD_GET);
if(!empty($filters['show_phone'])) {
    $get_parameters['f_show_phone'] = $filters['show_phone'];
}
$filters['apartments'] = Request::GetInteger('f_apartments',METHOD_GET);
if(!empty($filters['apartments'])) {
    $get_parameters['f_apartments'] = $filters['apartments'];
}
$filters['seller'] = Request::GetInteger('f_seller',METHOD_GET);
if(!empty($filters['seller'])) {
    $get_parameters['f_seller'] = $filters['seller'];
}
$filters['class'] = Request::GetInteger('f_class',METHOD_GET);
if(!empty($filters['class'])) {
    $get_parameters['f_class'] = $filters['class'];
}
$filters['admin_moder'] = Request::GetInteger('f_admin_moder',METHOD_GET);
if(!empty($filters['admin_moder'])) {
    $get_parameters['f_admin_moder'] = $filters['admin_moder'];
}

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$estate = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$estate_suffix = "";
if(!in_array($estate, array('live','build','country','commercial','housing_estates'))) $estate = "";
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
if($action == 'new'){
    $estate_suffix = '_new';
    array_splice($this_page->page_parameters, 1, 1);
    $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
    unset($filters['published']);
    unset($get_parameters['f_published']);
}  
$ajax_action = Request::GetString('action', METHOD_POST);
if($ajax_mode && !empty($ajax_action)) $action = 'ajax';
// обработка общих action-ов 
switch($action){
    case 'ajax':
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
/*********************************************************************************************************/
    case 'agencies':
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            switch($action){
                case 'list':
                    $search_string = Request::GetString('search_string',METHOD_POST);
                    $list = $db->fetchall("SELECT ".$sys_tables['users'].".id, ".$sys_tables['agencies'].".title 
                                           FROM ".$sys_tables['users']."
                                           LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                           WHERE ".$sys_tables['agencies'].".title LIKE '%".$search_string."%'  AND ".$sys_tables['users'].".agency_admin = 1
                                           GROUP BY ".$sys_tables['agencies'].".id
                                           ORDER BY  ".$sys_tables['agencies'].".title
                                           
                    ");
                    $ajax_result['ok'] = true;
                    if(!empty($list)) $ajax_result['list'] = $list;
                    else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Такое агентство не найдено'));
                    
                break;
            }
        break;
        
    case 'photos': // сохранение значения поля
        //обработка фотографий
        if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]=='photos'){
            $ajax_result['error'] = '';
            // переопределяем экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            switch($action){
                case 'list':
                    //получение списка фотографий
                    //id текущей новости
                    $id = Request::GetInteger('id', METHOD_POST);
                    if(!empty($id)){
                        $list = Photos::getList($estate,$id,$estate_suffix);
                        if(!empty($list)){
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                            $ajax_result['folder'] = Config::$values['img_folders'][$estate];
                        } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    if($estate == 'housing_estates')
                        Photos::$__folder_options=array(
                            'sm'=>array(90,90,'cut',65),
                            'med'=>array(560,415,'cut',75),
                            'big'=>array(2000,1600,'',90)
                            );     
                    //загрузка фотографий
                    //id текущей новости
                    $id = Request::GetInteger('id', METHOD_POST);   
                    if(!empty($id)){
                        //default sizes removed
                        $res = Photos::Add($estate,$id, $estate_suffix,false,false,false,false,true);
                        if(!empty($res)){
                            if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                            else {
                                if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                else {
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $res;
                                }
                            }
                        } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'setTitle':
                    //добавление названия
                    $id = Request::GetInteger('id_photo', METHOD_POST);                
                    $title = Request::GetString('title', METHOD_POST);                
                    if(!empty($id)){
                        $res = Photos::setTitle($estate,$id, $title);
                        $ajax_result['last_query'] = '';
                        if(!empty($res)) $ajax_result['ok'] = true;
                        else $ajax_result['error'] = 'Невозможно выполнить обновление названия фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'del':
                    //удаление фото
                    //id фотки
                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                    if(!empty($id_photo)){
                        $res = Photos::Delete($estate,$id_photo,$estate_suffix);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'setMain':
                    // установка флага "главное фото" для объекта
                    //id текущей новости
                    $id = Request::GetInteger('id', METHOD_POST);
                    //id фотки
                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                    if(!empty($id_photo)){
                        $res = Photos::setMain($estate, $id, $id_photo, $estate_suffix);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно установить статус';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'rotate':
                    //поворачиваем на 90 по часовой стрелке
                    $id = Request::GetInteger('id', METHOD_POST);
                    //id фотки
                    $ajax_result = [];
                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                    if(!empty($id_photo)){
                        $res = Photos::rotatePhoto($estate,$id_photo);
                        if(!empty($res)){
                            $ajax_result = $res;
                        } else $ajax_result['error'] = 'Невозможно повернуть картинку';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'sort':
                    // сортировка фото 
                    //порядок следования фотографий
                    $order = Request::GetArray('order', METHOD_POST);
                    if(!empty($order)){
                        $res = Photos::Sort($estate, $order);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно отсортировать';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        else $ajax_result['error'] = 'Unknown action';
        break;
    case 'setfield': // сохранение значения поля
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $field = Request::GetString('field', METHOD_POST);
        $value = Request::GetString('value', METHOD_POST);
        if(!empty($id) && !empty($field)){
            $res = $db->query("UPDATE ".$sys_tables[$estate.$estate_suffix]."
                               SET `".$field."`=?
                               WHERE id=?", $value, $id);
            $results['setfield'] = ($res && $db->affected_rows) ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['setfield']>0, 'ids'=>array($id));
            }
        }
        break;
    case 'remoderate':  // отправить на перемодерацию
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $row = $db->fetch("SELECT * FROM ".$sys_tables[$estate.$estate_suffix]."
                           WHERE id=?", $id);
        if(!empty($row)) {
            $row['id_object'] = $row['id']; // перенес ID во временное поле
            unset($row['id']);
            unset($row['date_change']);
            unset($row['tag_date']);
            unset($row['balance']);
            unset($row['status']);                
            if(empty($estate_suffix)){
                $res = $db->insertFromArray($sys_tables[$estate.'_new'], $row, 'id');
                $new_id = $db->insert_id;;
                if(!empty($res)) $res2 = $db->query("UPDATE ".$sys_tables[$estate]." SET published=3, date_change=NOW() WHERE id=?", $id);
                
            }    
            // тут надо еще сделать удаление тегов
            //модерация варианта
            $moderate = new Moderation($estate,!empty($new_id)?$new_id:$id);
            //передаем true,чтобы отметить, что мы модерируем из админки
            $results['remoderate'] = $moderate->checkObject(false,true);
            
        }
        if($ajax_mode){
            $ajax_result = array('ok' => $results['remoderate']===true || is_array($results['remoderate'])
                                ,'ids' => array($id)
                                ,'manual' => is_array($results['remoderate'])
                                ,'isnew' => !empty($estate_suffix));
        }
        break;
    case 'archive':     // отправить в архив
        if($action == "archive" && empty($estate_suffix)) {
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            $res = $db->query("UPDATE ".$sys_tables[$estate]."
                               SET published=2, date_change=NOW()
                               WHERE id=?", $id);
            //отвязываем все строчки, которые были привязаны к этому ЖК
            if($estate == 'housing_estates') $db->query("UPDATE ".$sys_tables['build']." SET id_housing_estate = 0 WHERE id_housing_estate = ?",$id);
            $results['archive'] = ($res && $db->affected_rows) ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['archive']>0, 'ids'=>array($id));
                break;
            }
        }
        break;
    case 'restore':     // восстановить из архива
        if($action == "restore" && empty($estate_suffix)) {
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            $res = $db->query("UPDATE ".$sys_tables[$estate]."
                               SET published=1, date_change=NOW()
                               WHERE id=?", $id);
            $results['restore'] = ($res && $db->affected_rows) ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['restore']>0, 'ids'=>array($id));
                break;
            }
        }
        break;
    case 'del':         // удаление
        if($action == "del") {
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            $res = $db->query("DELETE FROM ".$sys_tables[$estate.$estate_suffix]." WHERE id=?", $id);
            $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                break;
            }
        }
        break;
    /****************************\
    |*  Работа со статитикой ЖК *|
    \****************************/
    case 'stats':
        // переопределяем экшн
        $module_template = 'admin.housing_estates.stats.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
        //получение данных по объекту из базы
        $info = $db->fetch("SELECT 
                                `id`,
                                `title`
                            FROM ".$sys_tables['housing_estates']."
                            WHERE `id` = ?",$id);
        $photo = Photos::getMainPhoto('housing_estates',$id);                    
        Response::SetString('photo',Config::$values['img_folders'][$estate].'/sm/'.$photo['subfolder']."/".$photo['name']);
        $post_parameters = Request::GetParameters(METHOD_POST);
        // если была отправка формы - выводим данные 
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //передача данных в шаблон
            $date_start = $post_parameters['date_start'];
            $date_end = $post_parameters['date_end'];
            $info['date_start'] = $date_start;
            $info['date_end'] = $date_end;
            $stats = $db->fetchall("
                        SELECT IFNULL(a.show_amount,0) as show_amount, IFNULL(b.click_amount,0) as click_amount, a.date FROM 
                        (
                            (
                                SELECT 
                                    SUM(IFNULL(`amount`,0)) as show_amount, 
                                    DATE_FORMAT(`date`,'%d.%m.%Y') as date
                                FROM ".$sys_tables['estate_complexes_stats_full_shows']." 
                                WHERE
                                    `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                    `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND type = 1
                                GROUP BY `date`
                            ) a
                            LEFT JOIN 
                            (
                                SELECT 
                                    SUM(IFNULL(`amount`,0)) as click_amount, 
                                    DATE_FORMAT(`date`,'%d.%m.%Y') as date
                                FROM ".$sys_tables['estate_complexes_stats_full_clicks']."
                                WHERE
                                    `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                    `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."  AND type = 1
                                GROUP BY `date`
                            ) b ON a.date = b.date
                        ) UNION (
                            SELECT IFNULL(c.show_amount,0) as show_amount, IFNULL(d.click_amount,0) as click_amount, c.date FROM 
                            (
                                SELECT 
                                    IFNULL(COUNT(*),0) as show_amount, 
                                    'сегодня' as date,
                                    id_parent
                                FROM ".$sys_tables['estate_complexes_stats_day_shows']."  
                                WHERE `id_parent` = ".$id." AND type = 1
                            ) c
                            LEFT JOIN 
                            (
                                SELECT 
                                    IFNULL(COUNT(*),0) as click_amount, 
                                    'сегодня' as date,
                                    id_parent
                                FROM ".$sys_tables['estate_complexes_stats_day_clicks']."  
                                WHERE `id_parent` = ".$id." AND type = 1
                            ) d ON c.id_parent = d.id_parent

                        )
                    ");         
            Response::SetArray('stats',$stats); // статистика объекта    
            // общее количество показов/кликов/
        }
        Response::SetArray('info',$info); // информация об объекте                                        
        break; 
    case 'save_manager':
        $id = Request::GetInteger('id', METHOD_POST);
        $id_manager = Request::GetInteger('id_manager', METHOD_POST);
        if(!empty($id_manager) && !empty($id)) {
            $res = $db->query("UPDATE ".$sys_tables['housing_estates']." SET id_manager = ? WHERE id = ?", $id_manager, $id);
            $ajax_result['ok'] = $res;
        }
        break;               
    case "progresses":
        $action = $this_page->page_parameters[2];
        switch(true){
            case $action == 'photos':
                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                    $ajax_mode = true;
                    switch($action){
                        case 'list':
                            //получение списка фотографий
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            if(!empty($id)){
                                $list = Photos::getList('housing_estates_progresses',$id);
                                if(!empty($list)){
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $list;
                                    $ajax_result['folder'] = Config::$values['img_folders']['housing_estates_progresses'];
                                } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'add':
                            //загрузка фотографий
                            $id = Request::GetInteger('id', METHOD_POST);                
                            if(!empty($id)){
                                $item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates_progresses']." WHERE id = ?",$id);
                                $errors_log = [];
                                if($item['original_photo']==1) $res = Photos::Add('housing_estates_progresses',$id,false,false,false,false,false, true,'/img/layout/watermark-bsn.png', 100);
                                //default sizes removed
                                else $res = Photos::Add('housing_estates_progresses',$id,false,false,false,false,false, true);
                                if(!empty($res)){
                                    if(gettype($res) == 'string'){
                                        $ajax_result['errors'] = $res;
                                        $ajax_result['error'] = $res;  
                                    } 
                                    else {
                                        if(gettype($res) == 'string'){
                                            $ajax_result['errors'] = $res;
                                            $ajax_result['error'] = $res;  
                                        } 
                                        else {
                                            $ajax_result['ok'] = true;
                                            $ajax_result['list'] = $res;
                                        }
                                    }
                                } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            $ajax_result['errors'] = $errors_log;
                            break;
                        case 'del':
                            //удаление фото
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::Delete('housing_estates_progresses',$id_photo);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'setMain':
                            // установка флага "главное фото" для объекта, id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::setMain('housing_estates_progresses', $id, $id_photo);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно установить статус';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                    }
                break;  
           case $ajax_mode && $action == 'add':
                $id = Request::GetInteger('id', METHOD_POST);
                $id_parent = Request::GetInteger('id_parent', METHOD_POST);
                $value = Request::GetInteger('value', METHOD_POST);
                $type = Request::GetString('type', METHOD_POST);
                $item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates_progresses']." WHERE id = ?", $id);
                if(empty($item)) $db->query("INSERT INTO ".$sys_tables['housing_estates_progresses']." SET `".$type."` = ?, id_parent = ?", $value, $id_parent);
                else $db->query("UPDATE ".$sys_tables['housing_estates_progresses']." SET `".$type."` = ?, id_parent = ? WHERE id=?", $value, $id_parent, $id);
                $ajax_result['ok'] = true;
                break;          
           case $ajax_mode && $action == 'del':
                $id = Request::GetInteger('id', METHOD_POST);
                $db->query("DELETE FROM ".$sys_tables['housing_estates_progresses']." WHERE id=?", $id);
                $del_photos = Photos::DeleteAll('housing_estates_progresses',$id);
                $ajax_result['ok'] = true;
                break;          
        }
    
    case 'add':         // создание новой записи
    case 'edit':        // изменение (редактирование)
        if(!empty($this_page->page_parameters[3]) && $this_page->page_parameters[3] == 'queries-edit'){
            $object_id = $this_page->page_parameters[2];
            $res = true;
            $values = Request::GetArray('values',METHOD_POST);
            //если очередей нет
            if(empty($values)){
                //удаляем все очереди
                $db->query("DELETE FROM ".$sys_tables['housing_estates_queries']." WHERE id_parent = ".$object_id);
                //записываем информацию в наш ЖК
                $res = $db->query("UPDATE ".$sys_tables['housing_estates']." SET phases = '', build_complete = ? WHERE id = ?",$build_complete,$object_id);
            }else{
                
                //удаляем старые очереди
                $db->query("DELETE FROM ".$sys_tables['housing_estates_queries']." WHERE id_parent = ".$object_id);
                //пишем новые очереди
                if(!empty($values)){
                    foreach($values as $key=>$item){
                        $res = $res && $db->query("INSERT INTO ".$sys_tables['housing_estates_queries']." SET id_parent = ?, query_num = ?, id_build_complete = ?, corpuses = ?, start_month = ?, start_year = ?",
                            $object_id, $item['query'], $item['num'], $item['corpuses'], strtolower($item['month']), $item['year']
                        );
                    }
                }
            }
            $ajax_result['ok'] = $res;
            break;
        }
        if(($action == 'add' || $action == 'edit') && $this_page->page_parameters[1] != 'progresses' && empty($this_page->page_parameters[3])) {
            $GLOBALS['js_set'][] = '/modules/estate/ajax_actions.js';
            $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
            $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
            $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
            
            
            
            $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
            $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
            
            
            
            $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
            $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
            if($estate=='housing_estates'){
                //$GLOBALS['js_set'][] = '/modules/business_centers/gmap_handler.js';
            }
            $GLOBALS['css_set'][] = '/css/jquery.addrselector.css';
            $GLOBALS['js_set'][] = '/js/form.validate.js';
            
            $module_template = 'admin.'.$estate.$estate_suffix.'.edit.html';
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            if($action=='add'){
                // создание болванки новой записи
                $info = $db->prepareNewRecord($sys_tables[$estate.$estate_suffix]);
                $info['date_in'] = $info['date_change'] = date('Y-m-d H:i:s');
                $info['district_title'] = $info['metro_title'] = '';
            } else {
                // получение данных из БД
                //для ЖК читаем время последнего изменнеия отформатированное
                if($estate == 'housing_estates') $last_change = ", DATE_FORMAT(main.date_change,'%e %M %Y, %k:%i') AS formatted_last_change";
                else $last_change = "";
                $info = $db->fetch("SELECT main.*,
                                        ".($estate!='country'?"IFNULL(distr.title,'') as district_title,":"")."
                                        IFNULL(subway.title,'') as subway_title".$last_change."
                                    FROM ".$sys_tables[$estate.$estate_suffix]." main
                                    ".($estate!='country'?"LEFT JOIN ".$sys_tables['districts']." distr ON distr.id=main.id_district":"")."
                                    LEFT JOIN ".$sys_tables['subways']." subway ON subway.id=main.id_subway
                                    WHERE main.id=?", $id);
                if(empty($info)) Host::Redirect('/admin/estate/'.$estate.(empty($estate_suffix)?'':'/new').'/add/');
            }
            
            //для черновиков убираем поля выделенности и баланса
            if($info['published'] == 4){
                unset($mapping[$estate.$estate_suffix]['status']);
                unset($mapping[$estate.$estate_suffix]['balance']);
            }
            
            // определение геоданных объекта
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
                    $mapping[$estate.$estate_suffix]['geo_id']['value'] = $location['id'];
                    $mapping[$estate.$estate_suffix]['txt_region']['value'] = $location['shortname_cut'].'. '.$location['offname'];
                }  else  $geolocation[] = $location['offname'].' '.$location['shortname'];
            }
            $mapping[$estate.$estate_suffix]['geolocation']['value'] = implode(', ',$geolocation);
            //определение улицы
            if(!empty($info['id_street'])) {
                $street = $db->fetch("
                    SELECT `offname`, `shortname` FROM ".$sys_tables['geodata']."
                    WHERE a_level = 5 AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                    $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place'], $info['id_street']
                );
                $info['txt_street'] = $street['offname'].' '.$street['shortname'];
            }
            //определение района
            if(!empty($info['id_district'])) {
                $district = $db->fetch("SELECT title FROM ".$sys_tables['districts']." WHERE id=?",$info['id_district']);
                $info['txt_district'] = $district['title'];
            } elseif($info['id_region']==47){
                $info['txt_district'] = '-';
                $mapping[$estate.$estate_suffix]['txt_district']['disabled'] = true;
            }
            //определение метро
            if(!empty($info['id_subway'])) {
                $subway = $db->fetch("SELECT title FROM ".$sys_tables['subways']." WHERE id=?",$info['id_subway']);
                $info['txt_subway'] = $subway['title'];
            }
            // перенос дефолтных (считанных из базы) значений в мэппинг формы
            foreach($info as $key=>$field){
                if(!empty($mapping[$estate.$estate_suffix][$key])) $mapping[$estate.$estate_suffix][$key]['value'] = $info[$key];
            }

            //комментарий к последним изменениям по объекту отдаем в шаблон, освобождаем поле формы для нового комментария
            if(!empty($info['formatted_last_change'])){
                //читаем пользователя, который последним редактировал:
                $last_change_user = $db->fetch("SELECT CONCAT('#',id,', ',name,' ',lastname) AS title FROM ".$sys_tables['users']." WHERE id = ".$info['last_change_user'])['title'];
                Response::SetString('last_change_user',$last_change_user);
                Response::SetString('last_change_comment',$info['last_change_comment']);
                Response::SetString('date_change',$info['formatted_last_change']);
                $info['last_change_comment'] = '';
                $mapping[$estate.$estate_suffix]['last_change_comment']['value'] = '';
            }
            
            // корректировка внешнего вида формы в зависимости от данных
            if(empty($mapping[$estate.$estate_suffix]['elite']) || $mapping[$estate.$estate_suffix]['elite']['value']==2){
                $mapping[$estate.$estate_suffix]['elite_status']['hidden'] = true;
            } else {
                $mapping[$estate.$estate_suffix]['elite_status']['hidden'] = false;
            }
            
            if($estate == 'live'){
                // квартира/комната
                if($mapping[$estate.$estate_suffix]['id_type_object']['value']==2){
                    $mapping[$estate.$estate_suffix]['rooms_sale']['hidden'] = false;
                } else {
                    $mapping[$estate.$estate_suffix]['rooms_sale']['hidden'] = true;
                    //учитываем студии
                    if($mapping[$estate.$estate_suffix]['id_type_object']['value']==1){
                        $mapping[$estate.$estate_suffix]['rooms_sale']['min'] = 0;
                        $mapping[$estate.$estate_suffix]['rooms_total']['min'] = 0;
                        $mapping[$estate.$estate_suffix]['rooms_sale']['allow_empty'] = true;
                        $mapping[$estate.$estate_suffix]['rooms_total']['allow_empty'] = true;
                    }
                }
                // продажа/аренда
                if($mapping[$estate.$estate_suffix]['rent']['value']==1){
                    $mapping[$estate.$estate_suffix]['rent_duration']['hidden'] = false;
                    $mapping[$estate.$estate_suffix]['by_the_day']['hidden'] = false;
                } else {
                    $mapping[$estate.$estate_suffix]['rent_duration']['hidden'] = true;
                    $mapping[$estate.$estate_suffix]['by_the_day']['hidden'] = true;
                }
            }
            if($estate == 'build'){
                // рассрочка
                if($mapping[$estate.$estate_suffix]['installment']['value']!=1){
                    $mapping[$estate.$estate_suffix]['installment_months']['hidden'] = false;
                } else {
                    $mapping[$estate.$estate_suffix]['installment_months']['hidden'] = true;
                }
                //учитываем студии
                $mapping[$estate.$estate_suffix]['rooms_sale']['min'] = 0;
                $mapping[$estate.$estate_suffix]['rooms_total']['min'] = 0;
                $mapping[$estate.$estate_suffix]['rooms_sale']['allow_empty'] = true;
                $mapping[$estate.$estate_suffix]['rooms_total']['allow_empty'] = true;
            }
            if($estate == 'commercial'){
                // продажа/аренда
                if($mapping[$estate.$estate_suffix]['rent']['value']==1){
                    $mapping[$estate.$estate_suffix]['rent_duration']['hidden'] = false;
                } else {
                    $mapping[$estate.$estate_suffix]['rent_duration']['hidden'] = true;
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
                if(isset($mapping[$estate.$estate_suffix][$sprav_field])){
                    $sprav_rows = $db->fetchall("SELECT id,title FROM ".$sys_tables[$sprav_table]." ORDER BY title");
                    foreach($sprav_rows as $key=>$val){
                        $mapping[$estate.$estate_suffix][$sprav_field]['values'][$val['id']] = $val['title'];
                    }
                }
            }

            if($estate != 'build' && $estate != 'housing_estates'){
                $type_objects = $db->fetchall("SELECT id,title FROM ".$sys_tables['type_objects_'.$estate]." ORDER BY title");
                foreach($type_objects as $key=>$val){
                    $mapping[$estate.$estate_suffix]['id_type_object']['values'][$val['id']] = $val['title'];
                }
            }
            // получение данных, отправленных из формы
            $post_parameters = Request::GetParameters(METHOD_POST);
            
            if($estate == 'housing_estates'){
                $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 UNION SELECT 7 as id, 'Инкогнито' as name ORDER BY name ");
                foreach($managers as $key=>$val){
                    $mapping['housing_estates']['id_manager']['values'][$val['id']] = $val['name'];
                }
                $building_types = $db->fetchall("SELECT id, title FROM ".$sys_tables['building_types']);
                foreach($building_types as $key=>$val){
                    $mapping['housing_estates']['id_building_type']['values'][$val['id']] = $val['title'];
                }
                
                $housing_estate_classes = $db->fetchall("SELECT `id`,`title` FROM ".$sys_tables['housing_estate_classes']);
                foreach($housing_estate_classes as $key=>$val){
                    $mapping['housing_estates']['class']['values'][$val['id']] = $val['title'];
                }
                Response::SetArray('classes',$housing_estate_classes);
                $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".title, ".$sys_tables['users'].".id FROM
                                        ".$sys_tables['agencies']."
                                        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                        WHERE ".$sys_tables['users'].".id = ?", isset($post_parameters['id_user'])? $post_parameters['id_user'] : $info['id_user']);
                Response::SetString('agency_title',$agency['title']);
                $post_parameters['id_user'] = !empty($agency['id']) ? $agency['id'] : 0;
                $seller = $db->fetch("SELECT ".$sys_tables['agencies'].".title, ".$sys_tables['users'].".id FROM
                                        ".$sys_tables['agencies']."
                                        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                        WHERE ".$sys_tables['users'].".id = ?", isset($post_parameters['id_seller'])? $post_parameters['id_seller'] : $info['id_seller']);
                Response::SetString('seller_title',$seller['title']);
                $post_parameters['id_seller'] = !empty($seller['id']) ? $seller['id'] : 0;
                $agency_advert = $db->fetch("SELECT ".$sys_tables['agencies'].".title, ".$sys_tables['users'].".id FROM
                                        ".$sys_tables['agencies']."
                                        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                        WHERE ".$sys_tables['users'].".id = ?", isset($post_parameters['id_advert_agency'])? $post_parameters['id_advert_agency'] : $info['id_advert_agency']);
                Response::SetString('advert_agency_title',$agency_advert['title']);
                $post_parameters['id_advert_agency'] = !empty($agency_advert['id']) ? $agency_advert['id'] : 0;
            }
            
            //убираем checkbox со статусами модерации
            unset($mapping[$estate.$estate_suffix]['published']);
            
            // если была отправка формы - начинаем обработку
            if(empty($post_parameters['submit'])){
                // Блокировка записи
                if($action!='add'){
                    $blocking = $db->query("UPDATE ".$sys_tables[$estate.$estate_suffix]."
                                            SET blocking_time=ADDTIME(NOW(), '00:15:00'), blocking_id_user=?
                                            WHERE id=?"
                                            , $auth->id
                                            , $id);
                }
            } else {
                Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                foreach($post_parameters as $key=>$field){
                    if(isset($mapping[$estate.$estate_suffix][$key])) $mapping[$estate.$estate_suffix][$key]['value'] = $post_parameters[$key];
                }
                // проверка значений из формы
                $errors = Validate::validateParams($post_parameters,$mapping[$estate.$estate_suffix]);
                //проверка кол-ва продаваемых комнат только если выбрана комната
                if($estate=='live' && $info['id_type_object']==1 && !empty($errors['rooms_sale'])) unset($errors['rooms_sale']);
                // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                foreach($errors as $key=>$value){
                    if(isset($mapping[$estate.$estate_suffix][$key])) $mapping[$estate.$estate_suffix][$key]['error'] = $value;
                }
                    
                //для ЖК добавляем последнего редактора
                if($estate == 'housing_estates'){
                    $mapping[$estate.$estate_suffix]['last_change_user']['value'] = $auth->id;
                    //проверка на похожее название
                    if($action == 'add') $housing_estate_item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates']." WHERE title = ?", $mapping['housing_estates']['title']['value']);
                    else if($action == 'edit') $housing_estate_item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates']." WHERE title = ? AND id != ?", $mapping['housing_estates']['title']['value'], $info['id']);
                    if(!empty($housing_estate_item)) $errors['title'] = $mapping['housing_estates']['title']['error'] = 'Такое название КП уже существует';
                }
                
                // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                if(empty($errors)) {
                    //определение гео данных для полученного geo_id
                    if(!empty($mapping[$estate.$estate_suffix]['geo_id']['value'])){
                        $geolocation = $db->fetch("SELECT id_region,id_area,id_city,id_place FROM ".$sys_tables['geodata']." WHERE `id` = ".$mapping[$estate.$estate_suffix]['geo_id']['value']);
                        $mapping[$estate.$estate_suffix]['id_region']['value']     = $geolocation['id_region'];
                        $mapping[$estate.$estate_suffix]['id_area']['value']       = $geolocation['id_area'];
                        $mapping[$estate.$estate_suffix]['id_city']['value']       = $geolocation['id_city'];
                        $mapping[$estate.$estate_suffix]['id_place']['value']      = $geolocation['id_place'];
                        if($mapping[$estate.$estate_suffix]['id_region']['value'] == '78'){
                            $mapping[$estate.$estate_suffix]['geolocation']['hidden'] = true;
                        } else {
                             $mapping[$estate.$estate_suffix]['txt_district']['hidden'] = true;
                        }
                    }                        
                                                        
                    // подготовка всех значений для сохранения
                    foreach($info as $key=>$field){
                        if(isset($mapping[$estate.$estate_suffix][$key]['value'])) $info[$key] = $mapping[$estate.$estate_suffix][$key]['value'];
                    }
                    if(strlen($info['seller_phone'])<7) $info['seller_phone'] = '';
                    
                    // сохранение в БД
                    if($action=='edit'){
                        
                        //если переставляли фотографии, запоминаем
                        if(!empty($info['id']) && !empty($post_parameters['photos_order'])) Photos::setListOrder($estate,$info['id'],$post_parameters['photos_order']);
                        
                        if(empty($estate_suffix)){
                            if($estate!='housing_estates'){
                                //модерация варианта
                                $moderate = new Moderation($estate,!empty($new_id)?$new_id:$id);
                                foreach($mapping[$estate.$estate_suffix] as $key=>$values)
                                    $object_data[$key] = (!empty($values['value'])?$values['value']:0);
                                $moderate->moderated_status = $moderate->getModerateStatus($object_data); 
                                //$moderate->checkObject($id);
                                //
                                if($moderate->moderated_status > 1){
                                    $moderate_text = $db->fetch("SELECT title FROM ".$sys_tables['moderate_statuses']." WHERE id = ".$moderate->moderated_status);
                                    Response::SetBoolean('saved', false);
                                    Response::SetBoolean('errors', true);
                                    Response::SetArray('data_mapping', $mapping[$estate.$estate_suffix]);
                                    Response::SetString('error_text',$moderate_text['title']);
                                    $res = false;
                                    break;
                                }
                            }
   
                            if($estate == 'housing_estates'){
                                //если объект не новый, читаем очереди ЖК(если они есть) для данного объекта
                                if(!empty($info['id'])){
                                    $queries_list = $db->fetchall("SELECT ".$sys_tables['housing_estates_queries'].".query_num,
                                                                          ".$sys_tables['housing_estates_queries'].".corpuses,
                                                                          ".$sys_tables['build_complete'].".title
                                                                   FROM ".$sys_tables['housing_estates_queries']."
                                                                   LEFT JOIN ".$sys_tables['build_complete']." ON ".$sys_tables['housing_estates_queries'].".id_build_complete = ".$sys_tables['build_complete'].".id
                                                                   WHERE ".$sys_tables['housing_estates_queries'].".id_parent = ".$info['id']."
                                                                   ORDER BY query_num ASC");
                                    //набираем поля очереди,корпуса,сроки сдачи очередей
                                    $info['phases'] = "";
                                    $old_build_complete = $info['build_complete'];$info['build_complete'] = [];
                                    $old_korpuses = $info['korpuses'];$info['korpuses'] = [];
                                    foreach($queries_list as $key=>$item){
                                        $info['phases'] .= "Очередь ".$item['query_num']." ".$item['title']."\r\n";
                                        if(!empty($item['title'])) $info['build_complete'][] = $item['title'];
                                        if(!empty($item['corpuses'])) $info['korpuses'][] = $item['corpuses'];
                                    }
                                    $info['build_complete'] = (count($info['build_complete']) == 0)?$old_build_complete:implode(',',$info['build_complete']);
                                    $info['korpuses'] = (count($info['korpuses']) == 0)?$old_korpuses:implode(',',$info['korpuses']);
                                }
                            }
                            //если прошли модерацию, записываем данные
                            
                            if($estate == 'housing_estates' || $moderate->moderated_status == 1){
                                //чтобы установилось по умолчанию
                                $info['date_change'] = date('Y-m-d H:i:s'); 
                                $res = $db->updateFromArray($sys_tables[$estate], $info, 'id');
                                //чтобы записалось поле date_change
                                //header('Location: '.Host::getWebPath('/admin/estate/'.$estate.'/'));
                                //exit(0);
                            }
                        } else {
                            
                            //получение статуса модерации
                            $moderate = new Moderation($estate,$id);
                            $info['id_moderate_status'] = $moderate->getModerateStatus($info); 
                            $res = $db->updateFromArray($sys_tables[$estate.'_new'], $info, 'id');
                            header('Location: '.Host::getWebPath('/admin/estate/'.$estate.'/new/'));
                            exit(0);
                        }
                    } else {
                        if($estate!='housing_estates'){
                            $info['id_moderate_status'] = 1;
                            $res = $db->insertFromArray($sys_tables[$estate.'_new'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables[$estate], $info, 'id');
                        }
                        $new_id = $db->insert_id;
                        //обновление ЧПУ
                        if($estate=='housing_estates'){
                            $chpu_title = createCHPUTitle($info['title']);
                            $chpu_item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates']." WHERE chpu_title = ?", $chpu_title);
                            $db->query("UPDATE ".$sys_tables['housing_estates']." SET chpu_title = ? WHERE id = ?", $chpu_title.(!empty($chpu_item)?"_".$new_id:""), $new_id);
                        }

                        if(!empty($res) && $estate!='housing_estates'){
                            //модерация варианта
                            $moderate = new Moderation($estate,$id);
                            $moderate->checkObject();
                            // редирект на редактирование свеженькой страницы
                            if($moderate) {
                                header('Location: '.Host::getWebPath('/admin/estate/'.$estate.(empty($estate_suffix)?'':'/new').'/edit/'.$new_id.'/'));
                                exit(0);
                            }
                        } else {
                            header('Location: '.Host::getWebPath('/admin/estate/'.$estate.(empty($estate_suffix)?'':'/new').'/'));
                            exit(0);
                        }
                    }
                    Response::SetBoolean('saved', $res); // результат сохранения
                } else Response::SetBoolean('errors', true); // признак наличия ошибок
            }
            // если мы попали на страницу редактирования путем редиректа с добавления, 
            // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
            $referer = Host::getRefererURL();
            if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                Response::SetBoolean('form_submit', true);
                Response::SetBoolean('saved', true);
            }
            // запись данных для отображения на странице
            Response::SetArray('data_mapping', $mapping[$estate.$estate_suffix]);
            if($estate == 'housing_estates'){
                $progresses = $db->fetchall("SELECT * FROM ".$sys_tables['housing_estates_progresses']." WHERE id_parent = ? ORDER BY `year` DESC, `month` DESC", false, $id);
                Response::SetArray('progresses',$progresses);     
                $max_progress_id = $db->fetch("SELECT MAX(id) as id FROM ".$sys_tables['housing_estates_progresses']);
                Response::SetInteger('max_progress_id', $max_progress_id['id']);
                Response::SetArray('months',Config::Get('months'));
                $years = [];
                for($year=date('Y');$year>=2012;$year--) $years[] = $year;
                Response::SetArray('years',$years);
            }
            break;
        }
        //блок с формой редактирования очередей объекта
        if(!empty($this_page->page_parameters[3]) && $this_page->page_parameters[3] == 'queries-block'){
            $build_complete_list = $db->fetchall("SELECT * FROM ".$sys_tables['build_complete']." ORDER BY ".$sys_tables['build_complete'].".`year` ASC, ".$sys_tables['build_complete'].".decade ASC");
            Response::SetArray('build_complete_list',$build_complete_list);
            $months = array('1' => 'ЯНВАРЬ', '2' => 'ФЕВРАЛЬ','3' => 'МАРТ', '4' => 'АПРЕЛЬ','5' => 'МАЙ', '6' => 'ИЮНЬ','7' => 'ИЮЛЬ', '8' => 'АВГУСТ','9' => 'СЕНТЯБРЬ', '10' => 'ОКТЯБРЬ','11' => 'НОЯБРЬ', '12' => 'ДЕКАБРЬ');
            Response::SetArray('months',$months);
            Response::SetArray('years', array(
                    array('id' => 17, 'title' => 17)
                    , array('id' => 18, 'title' => 18)
                    , array('id' => 19, 'title' => 19)
                    , array('id' => 20, 'title' => 20)
                    , array('id' => 21, 'title' => 22)
                    , array('id' => 22, 'title' => 22)
                    , array('id' => 23, 'title' => 23)
                    , array('id' => 24, 'title' => 24)
                    , array('id' => 25, 'title' => 25)
                )
            );
            $object_id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            Response::SetInteger('object_id',$object_id);
            //читаем срок сдачи объекта
            $object_info = $db->fetch("SELECT title,build_complete,korpuses FROM ".$sys_tables['housing_estates']." WHERE id = ".$object_id);
            Response::SetArray('object_info',$object_info);
            //читаем очереди объекта
            $object_queries = $db->fetchall("SELECT ".$sys_tables['build_complete'].".id, 
                                                 ".$sys_tables['build_complete'].".title,
                                                 ".$sys_tables['housing_estates_queries'].".id AS query_id,
                                                 ".$sys_tables['housing_estates_queries'].".query_num,
                                                 ".$sys_tables['housing_estates_queries'].".start_month,
                                                 ".$sys_tables['housing_estates_queries'].".start_year,
                                                 ".$sys_tables['housing_estates_queries'].".corpuses
                                             FROM ".$sys_tables['housing_estates_queries']."
                                             LEFT JOIN ".$sys_tables['build_complete']." ON ".$sys_tables['build_complete'].".id = ".$sys_tables['housing_estates_queries'].".id_build_complete
                                             WHERE ".$sys_tables['housing_estates_queries'].".id_parent = ".$object_id."
                                             ORDER BY ".$sys_tables['housing_estates_queries'].".id ASC");
            if(!empty($object_queries)) Response::SetArray('object_queries',$object_queries);
            $module_template = "admin.housing_estates.queries.html";
            $ajax_result['ok'] = true;
        }
        break;
    case 'addr_problems':
        //записываем назначенный адрес
        if($ajax_mode){
            if(!empty($this_page->page_parameters[2]) && !empty($this_page->page_parameters[3])){
                switch($this_page->page_parameters[2]){
                    //назначаем адрес всем объектам этого агентства с таким addr_source
                    case 'edit':
                        $new_geo_id = Request::GetInteger('geo_id',METHOD_POST);
                        $_geo_info = $db->fetch("SELECT id_region,id_area,id_city,id_place,id_street,id_district FROM ".$sys_tables['geodata']." WHERE id = ".$new_geo_id);
                        $object_id = Request::GetInteger('object_id',METHOD_POST);
                        $user_id = Request::GetInteger('user_id',METHOD_POST);
                        $addr_source = Request::GetString('addr_source',METHOD_POST);
                        
                        $no_source = Request::GetString('no_source',METHOD_POST);
                        $no_source = !(empty($no_source) || $no_source == "false");
                        
                        $update_district = Request::GetString('update_district',METHOD_POST);
                        $update_district = !(empty($update_district) || $update_district == "false");
                        
                        if(!empty($object_id)||!empty($addr_source))
                            $res = $db->query("UPDATE ".$sys_tables[$estate]." 
                                               SET id_region = ?,
                                                   id_area = ?, 
                                                   id_city = ?, 
                                                   id_place = ?, 
                                                   id_street = ?,
                                                   ".(!empty($update_district)?"id_district = ".$_geo_info['id_district'].",":"")." 
                                                   addr_problems = 3
                                               WHERE id_user = ? AND 
                                                     ".(!empty($no_source)?" id = ".$object_id:" addr_source = '".$addr_source."'")."",
                                               $_geo_info['id_region'],$_geo_info['id_area'],$_geo_info['id_city'],$_geo_info['id_place'],$_geo_info['id_street'],$user_id);
                        $ajax_result['success'] = $res;
                        $ajax_result['amount'] = $db->affected_rows;
                    break;
                }
            }
            $ajax_result['ok'] = true;
        }
        //отдаем общий список
        else{
            require_once('includes/class.geo.php');
            Response::SetString('estate',$estate);
            
            $conditions = array('addr_problems = 1 AND '.$sys_tables[$estate].'.published = 1');
            if(!empty($filters['id'])) $conditions[] = $sys_tables[$estate].".`id` = ".$filters['id'];
            if(!empty($filters['id_user'])) $conditions[] = $sys_tables[$estate].".`id_user` = ".$filters['id_user'];
            if(!empty($filters['f_agency'])) $conditions[] = $sys_tables['agencies'].".`id_agency` = ".$filters['f_agency'];
            $conditions = implode(' AND ',$conditions);
            
            switch($estate){
                    case 'build':Response::SetString('list_title',"Новостройки");break;
                    case 'live':Response::SetString('list_title',"Жилая");break;
                    case 'commercial':Response::SetString('list_title',"Коммерческая");break;
                    case 'country':Response::SetString('list_title',"Загородная");break;
                }
            
            // создаем пагинатор для списка
            $paginator = new Paginator($sys_tables[$estate.$estate_suffix], 50, $conditions,"","txt_addr");
            
            // get-параметры для ссылок пагинатора
            $get_in_paginator = [];
            foreach($get_parameters as $gk=>$gv){
                if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
            }
            // ссылка пагинатора
            $paginator->link_prefix = '/admin/estate/'.$estate.'/addr_problems'                      // модуль
                                      ."/?"                                         // конечный слеш и начало GET-строки
                                      .implode('&',$get_in_paginator)               // GET-строка
                                      .(empty($get_in_paginator)?"":'&')."page=";   // параметр для номера страницы
            if($paginator->pages_count>0 && $paginator->pages_count<$page){
                Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                exit(0);
            }
            
            $list = $db->fetchall("SELECT ".$sys_tables[$estate].".id,external_id,txt_addr,id_region,id_district,id_area,".$sys_tables[$estate].".id_city,id_place,id_street,house,corp,COUNT(*) AS amount,
                                          (CASE info_source
                                            WHEN 1 THEN 'site'
                                            WHEN 2 THEN 'BN'
                                            WHEN 3 THEN 'EIP'
                                            WHEN 4 THEN 'NG'
                                            WHEN 5 THEN 'BN_TXT'
                                            WHEN 7 THEN 'EXCEL'
                                            WHEN 8 THEN 'Yandex'
                                          END) AS source,
                                          ".$sys_tables['users'].".id AS id_user,
                                          IF(".$sys_tables['agencies'].".id IS NULL,0,".$sys_tables['agencies'].".id) AS id_agency,
                                          IF(".$sys_tables['agencies'].".id IS NULL,'',".$sys_tables['agencies'].".title) AS agency_title,
                                          ".$sys_tables[$estate].".addr_source,
                                          ".$sys_tables[$estate].".txt_addr,
                                          ".$sys_tables['districts'].".title AS district_title,
                                          IF(".$sys_tables[$estate].".rent = 1,'rent','sell') AS rent
                                   FROM ".$sys_tables[$estate]." 
                                   LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables[$estate].".id_user
                                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                   LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables[$estate].".id_district = ".$sys_tables['districts'].".id
                                   WHERE ".$conditions."
                                   GROUP BY txt_addr
                                   LIMIT ".$paginator->getLimitString($page));
            
            foreach($list as $key=>$item){
                switch($estate){
                    case 'build':$object = new EstateItemBuild($item['id']);break;
                    case 'live':$object = new EstateItemLive($item['id']);break;
                    case 'commercial':$object = new EstateItemCommercial($item['id']);break;
                    case 'country':$object = new EstateItemCountry($item['id']);break;
                }
                
                $list[$key]['generated_addr'] = Geo::getAddress($object->GetData());
                $list[$key]['addr_variants'] = Geo::getAddrList($item['txt_addr']);
                
                unset($object);
            }
            Response::SetArray('list',$list);
            Response::SetArray('paginator', $paginator->Get($page));
            $module_template = 'admin.addr_problems.list.html';
        }
        break;
    //Работа с вариантами ЖК - копирование, просмотр структуры
    case 'attached_objects':
        if(empty($this_page->page_parameters[2]) || !Validate::isDigit($this_page->page_parameters[2])) break;
        
        $complex_id = Convert::ToInt($this_page->page_parameters[2]);
        
        switch(true){
            //копируем варианты другому клиенту
            case $ajax_mode && $this_page->page_parameters[3] == 'copy':
                $ajax_result['type'] = "copy";
				set_time_limit(300);
                $id_user_from = Request::GetInteger('id_user_from',METHOD_POST);
                $id_user_to = Request::GetInteger('id_user_to',METHOD_POST);
                
                $user_phone = $db->fetch("SELECT IF(advert_phone='',phones,advert_phone) AS phone
                                          FROM ".$sys_tables['users']." 
                                          LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                          WHERE ".$sys_tables['users'].".id = ?",$id_user_to)['phone'];
                
                $objects_to_copy = $db->fetchall("SELECT *,
                                                         ".$id_user_to." AS id_user, 
                                                         0 AS id_promotion, 
                                                         0 AS id_main_photo, 
                                                         NOW() AS date_change,
                                                         '".$user_phone."' AS seller_phone
                                                  FROM ".$sys_tables['build']." 
                                                  WHERE id_housing_estate = ? AND id_user = ? AND published = 1",false,$complex_id,$id_user_from);
                $photos_count = 0;
                $photos_added = 0;
                $objects_count = count($objects_to_copy);
                foreach($objects_to_copy as $key=>$item){
                    $photo_list = $db->fetchall("SELECT CONCAT('".Config::$values['nginx']['url'][0]."/".Config::$values['img_folders'][$estate]."/big/',sUBSTR(name,1,2),'/',name) AS url FROM ".$sys_tables['build_photos']." WHERE id_parent = ?",false,$item['id']);
                    unset($item['id']);
                    $res = $db->insertFromArray($sys_tables['build'],$item);
                    if(!$res) --$objects_count;
                    $new_id = $db->insert_id;
                    $photos_count += count($photo_list);
                    foreach($photo_list as $key=>$url){
                        $res = @Photos::Add('build',$new_id,false,$url['url'],false,false,false,false,false,false,false,false,false,true);
                        if($res) ++$photos_added;
                    }
                    //$id_main_photo = @Photos::getMainPhoto('build',$new_id);
                    //if(!empty($id_main_photo)) $db->query("UPDATE ".$sys_tables['build']." SET id_main_photo = ? WHERE id = ?",$id_main_photo,$new_id);
                }
                $ajax_result['objects_count'] = $objects_count;
                $ajax_result['photos_count'] = $photos_added."/".$photos_count;
                $ajax_result['ok'] = true;
                break;
            //открепляем варианты от ЖК
            case $ajax_mode && $this_page->page_parameters[3] == 'unattach':
                $id_user_from = Request::GetInteger('id_user_from',METHOD_POST);
                $db->query("UPDATE ".$sys_tables['build']." SET id_housing_estate = 0 WHERE id_housing_estate = ? AND id_user = ? AND published = 1",$complex_id,$id_user_from);
                $ajax_result['objects_count'] = $db->affected_rows;
                $ajax_result['ids'] = array($id_user_from);
                $ajax_result['type'] = "unattach";
                $ajax_result['ok'] = true;
                break;
            //удаляем варианты из базы
            case $ajax_mode && $this_page->page_parameters[3] == 'del':
                $id_user_from = Request::GetInteger('id_user_from',METHOD_POST);
                //удаляем фотографии
                $ids = $db->fetchall("SELECT id FROM ".$sys_tables['build']." WHERE id_housing_estate = ? AND id_user = ? ANd published = 1",'id',$complex_id,$id_user_from);
                if(empty($ids)){
                    $ajax_result['ok'] = false;
                    break;
                }
                $ids = array_keys($ids);
                foreach($ids as $k=>$object_id) @Photos::DeleteAll('build',$object_id);
                //удаляем сами объекты
                $db->query("DELETE FROM ".$sys_tables['build']." WHERE id_housing_estate = ? AND id_user = ? AND published = 1",$complex_id,$id_user_from);
                $ajax_result['ids'] = array($id_user_from);
                $ajax_result['objects_count'] = count($ids);
                $ajax_result['type'] = "del";
                $ajax_result['ok'] = true;
                break;
            //список размещающихся в этом ЖК
            default:
                //проверяем что ЖК есть и опубликован
                $complex_info = $db->fetch("SELECT id,
                                                   title,
                                                   id_user,
                                                   id_developer,
                                                   id_seller,
                                                   exclusive_seller
                                            FROM ".$sys_tables['housing_estates']."
                                            WHERE id = ? AND published = 1",$complex_id);
                Response::SetArray('complex_info',$complex_info);
                //запрос разбит на два, так быстрее примерно в два раза! 0.0161 > 0.0098 на примере #1512
                $complex_users_list = $db->fetchall("SELECT id_user,
                                                            COUNT(*) AS amount
                                                     FROM ".$sys_tables['build']."
                                                     WHERE id_housing_estate = ? AND published = 1
                                                     GROUP BY id_user",'id_user',$complex_id);
                if(empty($complex_users_list)){
                    $module_template = "admin.housing_estates.attached_objects.html";
                    $ajax['ok'] = true;
                    break;
                }
                $users_ids = array_keys($complex_users_list);
                $agencies_info = $db->fetchall("SELECT ".$sys_tables['agencies'].".*,
                                                       ".$sys_tables['users'].".id AS id_user,
                                                       ".$sys_tables['agencies'].".id AS id_agency
                                                FROM ".$sys_tables['users']."
                                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                                WHERE ".$sys_tables['users'].".id IN (".implode(',',$users_ids).")",'id_user');
                foreach($agencies_info as $id_user=>$agency_info){
                    $agencies_info[$id_user]['amount'] = $complex_users_list[$id_user]['amount'];
                    switch(true){
                        case $agency_info['id_user'] == $complex_info['id_user']:
                            $agencies_info[$id_user]['housing_estate_user_status'] = "Размещает этот ЖК";
                            break;
                        case $agency_info['id_user'] == $complex_info['id_developer']:
                            $agencies_info[$id_user]['housing_estate_user_status'] = "Девелопер";
                            break;
                        case $agency_info['id_user'] == $complex_info['id_seller']:
                            $agencies_info[$id_user]['housing_estate_user_status'] = ($complex_info['exclusive_seller'] == 1? "Эксклюзивный п" : "П")."родавец";
                            break;
                        default:
                            $agencies_info[$id_user]['housing_estate_user_status'] = "Брокер";
                    }
                }
                Response::SetArray('list',$agencies_info);
                
                $module_template = "admin.housing_estates.attached_objects.html";
        }
        break;
    default:        // списки элементов
        $GLOBALS['js_set'][] = '/modules/estate/ajax_actions.js';
        
        $module_template = 'admin.'.$estate.$estate_suffix.'.list.html';
        // формирование списков для фильтров
        if(!empty($estate_suffix)){
            $m_status = $db->fetchall("SELECT id, title FROM ".$sys_tables['moderate_statuses'], 'id');
            $m_status[99] = array('id'=>99, 'title'=>'Все статусы');
            Response::SetArray('m_status',$m_status);
        }
        if($estate=='commercial'){
            $type_objects = $db->fetchall("SELECT id, title FROM ".$sys_tables['type_objects_commercial'], 'id');
            Response::SetArray('type_objects',$type_objects);
        }
        if($estate=='country'){
            $type_objects = $db->fetchall("SELECT id, title FROM ".$sys_tables['type_objects_country'], 'id');
            Response::SetArray('type_objects',$type_objects);
        }
        //список менеджеров для ЖК
        if($estate=='housing_estates'){
            $developers = $db->fetchall("SELECT ".$sys_tables['agencies'].".title, ".$sys_tables['users'].".id 
                                         FROM ".$sys_tables['agencies']." 
                                         RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                         RIGHT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['housing_estates'].".id_user = ".$sys_tables['users'].".id
                                         WHERE   ".$sys_tables['users'].".id > 0  AND ".$sys_tables['agencies'].".title!=''
                                         GROUP BY  ".$sys_tables['users'].".id
                                         ORDER BY ".$sys_tables['agencies'].".title");
            Response::SetArray('developers',$developers);
            $sellers = $db->fetchall("SELECT ".$sys_tables['agencies'].".title, ".$sys_tables['users'].".id 
                                         FROM ".$sys_tables['agencies']." 
                                         RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                         RIGHT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['housing_estates'].".id_seller= ".$sys_tables['users'].".id
                                         WHERE   ".$sys_tables['users'].".id > 0  AND ".$sys_tables['agencies'].".title!=''
                                         GROUP BY  ".$sys_tables['users'].".id
                                         ORDER BY ".$sys_tables['agencies'].".title");
            Response::SetArray('sellers',$sellers);

            $managers_list = $db->fetchall("SELECT id, name as title FROM ".$sys_tables['managers']." WHERE bsn_manager=1 UNION SELECT 7 as id, 'Инкогнито' as title");
            $managers = [];
            foreach($managers_list as  $k=>$item) $managers[$item['id']] = $item['title'];
            Response::SetArray('managers',$managers);     
        };
        // формирование условий для списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['id'])) $conditions[] = "`id` = ".$filters['id'];
            if(!empty($filters['id_user'])) $conditions[] = "`id_user` = ".$filters['id_user'];
            if(!empty($filters['type'])) $conditions[] = "`id_type_object` = ".$filters['type'];
            if(!empty($filters['developer']))
            if($filters['developer'] == -1) $conditions[] = "`id_user` = 0";
            else $conditions[] = "`id_user` = ".$filters['developer'];
            if(!empty($filters['manager'])) $conditions[] = $sys_tables[$estate.$estate_suffix].".`id_manager` = ".$filters['manager'];
            if(!empty($filters['title'])) $conditions[] = $sys_tables[$estate.$estate_suffix].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['rent'])) $conditions[] = "`rent` = ".$filters['rent'];
            if(!empty($filters['admin_moder'])) $conditions[] = "`admin_moderated` = ".$filters['admin_moder'];
            if(!empty($filters['m_status']) && !empty($estate_suffix)) {
                if($filters['m_status']!=99) 
                    $conditions[] = "`id_moderate_status` = ".$filters['m_status'];
            }
            if(!empty($filters['published']) && empty($estate_suffix)) $conditions[] = $sys_tables[$estate.$estate_suffix].".`published` = ".$filters['published'];
            if(!empty($filters['coords'])){
              if($filters['coords']==1)  $conditions[] = $sys_tables[$estate.$estate_suffix].".lat > 0 AND ".$sys_tables[$estate.$estate_suffix].".lng > 0 ";
              else  $conditions[] = " lat = 0.00 AND lng = 0.00 ";
            } 
            if(!empty($filters['agency_check'])){
              if($filters['agency_check']==1)  $conditions[] = " id_user > 0";
              else  $conditions[] = " id_user = 0";
            }             
            if(!empty($filters['fz_214']))$conditions[] = " 214_fz = ".$filters['fz_214'].($filters['fz_214']==1?" AND declaration!=''":"");
            if(!empty($filters['show_phone']))$conditions[] = $sys_tables[$estate] . ".show_phone = ".$filters['show_phone'];
            if(!empty($filters['apartments']))$conditions[] = " apartments = ".$filters['apartments'];
            if(!empty($filters['seller'])){
                switch($filters['seller']){
                    case -1: $conditions[] = " id_seller = 0"; break;
                    case -2: $conditions[] = " id_seller > 0"; break;
                    default: $conditions[] = " id_seller = ".$filters['seller'];
                }
            }
            if(!empty($filters['class']))$conditions[] = " class = '".$filters['class']."'";

            if(!empty($filters['f_agency'])){
                //1 означает, что это частное лицо, id_agency=0 или id_agency=1
                if ($filters['f_agency']==1) $conditions[] = "(`id_user` IN (SELECT id FROM ".$sys_tables['users']." WHERE id_agency<2))";
                else $conditions[] = "`id_user` = ".$filters['f_agency'];
            }
        }
        if(empty($filters['m_status']) && !empty($estate_suffix)) $conditions[] = 'id_moderate_status > 1';
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables[$estate.$estate_suffix], 50, $condition);
        
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/estate/'.$estate                      // модуль
                                  .(empty($estate_suffix)?'':'/new')            // на модерации
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)               // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page=";   // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        //формируем список агентств для фильтра
        //выбираем агенства, которые не являются частными лицами,
        //и у которых есть объекты в данном типе недвижимости

        
        if(!empty($filters['published']) && empty($estate_suffix)) $where_new = $sys_tables[$estate.$estate_suffix].".published = ".$filters['published'];
        else  $where_new = $sys_tables[$estate.$estate_suffix].".published = 1";
        $where = $sys_tables[$estate].".published = 3";
        $sql="
            SELECT IF(".$sys_tables['users'].".id_agency>1,".$sys_tables['agencies'].".title,'Частные заявки') as title,
                   IF(".$sys_tables['users'].".id_agency>1,".$sys_tables[$estate.$estate_suffix].".id_user,'1') as id_user
            FROM ".$sys_tables[$estate.$estate_suffix]."
            LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables[$estate.$estate_suffix].".id_user = ".$sys_tables['users'].".id
            LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
            WHERE $where_new
            UNION
            SELECT IF(".$sys_tables['users'].".id_agency>1,".$sys_tables['agencies'].".title,'Частные заявки') as title,
                   IF(".$sys_tables['users'].".id_agency>1,".$sys_tables[$estate].".id_user,'1') as id_user
            FROM ".$sys_tables[$estate]."
            LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables[$estate].".id_user = ".$sys_tables['users'].".id
            LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
            WHERE $where
            GROUP BY id_user
            ORDER BY title
             ";
        
        $list = $db->fetchall($sql);
        foreach($list as $key=>$item){
            $list_agencies[$item['id_user']]=$item['title'];
        }
        unset($list);
        if(!empty($list_agencies)) Response::SetArray('list_agencies', $list_agencies);
        if($estate == 'housing_estates') $order = ' advanced, `date_in` DESC ';
        else $order = ' `date_in` DESC ';        
        
        if($estate == 'housing_estates'){
            //фильтр класс
            $housing_estate_classes = $db->fetchall("SELECT `id`,`title` FROM ".$sys_tables['housing_estate_classes']);
            Response::SetArray('classes',$housing_estate_classes);
            
            $sql = "SELECT 
                            ".$sys_tables[$estate.$estate_suffix].".*, 
                             ".$sys_tables['managers'].".name as manager,  
                             ".$sys_tables['agencies'].".title as developer,  
                            IFNULL(a.cnt_day,0) as cnt_day,
                            IFNULL(e.cnt_full_last_days,0) as cnt_full_last_days,
                            IFNULL(c.cnt_click_day,0) as cnt_click_day,
                            IFNULL(f.cnt_click_full_last_days,0) as cnt_click_full_last_days
                      FROM ".$sys_tables[$estate];
            $sql .= " LEFT JOIN ".$sys_tables['managers']." ON  ".$sys_tables['managers'].".id = ".$sys_tables[$estate.$estate_suffix].".id_manager
                      LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['housing_estates'].".id_user
                      LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency";
            $sql .= " LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$sys_tables['estate_complexes_stats_day_shows']." WHERE type = 1 GROUP BY id_parent) a ON a.id_parent = ".$sys_tables[$estate.$estate_suffix].".id";        
            $sql .= " LEFT JOIN (SELECT COUNT(*) as cnt_click_day, id_parent FROM ".$sys_tables['estate_complexes_stats_day_clicks']." WHERE type = 1 GROUP BY id_parent) c ON c.id_parent = ".$sys_tables[$estate.$estate_suffix].".id";        
            $sql .= " LEFT JOIN (SELECT AVG(amount) as cnt_full_last_days, id_parent FROM ".$sys_tables['estate_complexes_stats_full_shows']." WHERE type = 1 AND date > CURDATE() - INTERVAL 30  DAY AND date <= CURDATE() - INTERVAL 1 DAY GROUP BY id_parent) e ON e.id_parent = ".$sys_tables[$estate.$estate_suffix].".id";        
            $sql .= " LEFT JOIN (SELECT AVG(amount) as cnt_click_full_last_days, id_parent FROM ".$sys_tables['estate_complexes_stats_full_clicks']." WHERE type = 1 AND date > CURDATE() - INTERVAL 30  DAY AND date <= CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) f ON f.id_parent = ".$sys_tables[$estate.$estate_suffix].".id";        
            if(!empty($condition)) $sql .= " WHERE ".$condition;
            $sql .= " ORDER BY ".$order;
            $sql .= " LIMIT ".$paginator->getLimitString($page); 
                
        } else {
            $sql = "SELECT * FROM ".$sys_tables[$estate.$estate_suffix];
            if(!empty($condition)) $sql .= " WHERE ".$condition;
            $sql .= " ORDER BY ".$order;
            $sql .= " LIMIT ".$paginator->getLimitString($page); 
        }
        $list = $db->fetchall($sql);
        // определение главной фотки для объекта
        $estate_photo_folder=Config::$values['img_folders'][$estate];
        foreach($list as $key=>$value){
            $photo = Photos::getMainPhoto($estate,$value['id'],$estate_suffix);
            if(!empty($photo)) {
                $list[$key]['photo'] = $estate_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
            }
        }
        
        //выбор агентства для     
        // формирование списка
        Response::SetArray('list', $list);
            Response::SetArray('paginator', $paginator->Get($page));
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk.'='.$gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>