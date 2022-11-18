<?php
$GLOBALS['js_set'][] = '/modules/sale/ajax_actions.js';
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.comagic.php');
//подключение к сервису Comagic
Comagic::Init();
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');
// добавление title
$this_page->manageMetadata(array('title'=>'Спецпредложения'));
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');
// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['published'] = Request::GetInteger('f_published',METHOD_GET);
$filters['agency'] = Request::GetInteger('f_agency',METHOD_GET);
$filters['campaign'] = Request::GetInteger('f_campaign',METHOD_GET);
$filters['offer'] = Request::GetInteger('f_offer',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['published'])) $get_parameters['f_published'] = $filters['published'];
elseif(empty($filters['published']) && $filters['published']==0) $get_parameters['f_published'] = $filters['published'] = 0;

if(!empty($filters['agency'])) $get_parameters['f_agency'] = $filters['agency'];
if(!empty($filters['campaign'])) $get_parameters['f_campaign'] = $filters['campaign'];
if(!empty($filters['offer'])) $get_parameters['f_offer'] = $filters['offer'];

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

// обработка action-ов
switch($action){
    /*********************************\
    |*  Работа с Акциями             *|
    \*********************************/
    case 'campaigns':
        // переопределяем экшн
        $ajax_action = Request::GetString('action', METHOD_POST);
        $action = empty($this_page->page_parameters[1]) ? "" : (empty($ajax_action) ? $this_page->page_parameters[1]: $ajax_action);
        Photos::$__folder_options = array(
                                    'thumb'=>array(70,70,'cut',80)
                                    ,'sm'=>array(260,167,'cut',80)
                                    ,'med'=>array(485,325,'',80)
                                    ,'big'=>array(800,600,'',70)
        );
        switch($action){
        /**************************\
        |*  Работа с геоданными   *|
        \**************************/
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
                    $ajax_result['district'] = array('items'=>array(),'selected'=>array());
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
                if(!empty($location) && !empty($location['id_region']) && $location['id_region']==78) $item_id = 34142;
                $subways = $db->fetchall("SELECT * FROM ".$sys_tables['subways']."
                                          WHERE parent_id=? ORDER BY title", false, $item_id);
                if(!empty($subways)){
                    $ajax_result['subway'] = array('items'=>array());
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
                $geolist = array();
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
        /**************************\
        |*  Работа с фотографиями  *|
        \**************************/
        case 'photos':
            if($ajax_mode){
                $ajax_result['error'] = '';
                // переопределяем экшн
                $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                switch($action){
                    case 'list':
                        //получение списка фотографий
                        //id текущей новости
                        $id = Request::GetInteger('id', METHOD_POST);
                        if(!empty($id)){
                            $list = Photos::getList('campaigns',$id);
                            if(!empty($list)){
                                $ajax_result['ok'] = true;
                                $ajax_result['list'] = $list;
                                $ajax_result['folder'] = Config::$values['img_folders']['campaigns'];
                            } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                    case 'add':
                        //загрузка фотографий
                        //id текущей новости
                        $id = Request::GetInteger('id', METHOD_POST);                
                        if(!empty($id)){
                            //default sizes removed 120x100
                            $res = Photos::Add('campaigns',$id,false,false,false,false,false,true);
                            if(!empty($res)){
                                if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                else {
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $res;
                                }
                            } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                    case 'setTitle':
                        //добавление названия
                        $id = Request::GetInteger('id_photo', METHOD_POST);                
                        $title = Request::GetString('title', METHOD_POST);                
                        if(!empty($id)){
                            $res = Photos::setTitle('campaigns',$id, $title);
                            $ajax_result['last_query'] = '';
                            if(!empty($res)) $ajax_result['ok'] = true;
                            else $ajax_result['error'] = 'Невозможно выполнить обновление названия фото';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;    
                        case 'setMain':
                            // установка флага "главное фото" для объекта
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::setMain('campaigns', $id, $id_photo);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно установить статус';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                    case 'del':
                        //удаление фото
                        //id фотки
                        $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                        if(!empty($id_photo)){
                            $res = Photos::Delete('campaigns',$id_photo);
                            
                            if(!empty($res)){
                                $ajax_result['ok'] = true;
                            } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                }
            }
            break;
            case 'add':
            case 'edit':
                $GLOBALS['js_set'][]='/js/form.validate.js';
                $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                $GLOBALS['js_set'][] = '/js/jquery.addrselector.js';
                $GLOBALS['css_set'][] = '/css/jquery.addrselector.css';
                
                $GLOBALS['js_set'][] = '/modules/sale/streets_autocomplette.js';
                $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                $GLOBALS['js_set'][] = '/modules/sale/gmap_handler.js';
                
                $module_template = 'admin.campaigns.edit.html';
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['campaigns']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT main.*,
                                        IFNULL(distr.title,'') as district_title,
                                        IFNULL(subway.title,'') as subway_title
                                        FROM ".$sys_tables['campaigns']." main 
                                        LEFT JOIN ".$sys_tables['districts']." distr ON distr.id=main.id_district
                                        LEFT JOIN ".$sys_tables['subways']." subway ON subway.id=main.id_subway
                                        WHERE main.id=?", $id);
                }
                // определение геоданных объекта
                $geodata = $db->fetchall("
                    SELECT * FROM ".$sys_tables['geodata']."
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
                $geolocation = $location = array();
                while(!empty($geodata)){
                    $location = array_shift($geodata);
                    if(empty($geodata)) {
                        $mapping['campaigns']['geolocation_id']['value'] = $location['id'];
                    }
                    $geolocation[] = $location['offname'].' '.$location['shortname'];
                }
                $mapping['campaigns']['geolocation']['value'] = implode(' / ',$geolocation);
                //определение улицы
                if(!empty($info['id_street'])) {
                    $street = $db->fetch("
                        SELECT `offname`, `shortname` FROM ".$sys_tables['geodata']."
                        WHERE a_level = 5 AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                        $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place'], $info['id_street']
                    );
                    $info['txt_street'] = $street['offname'].' '.$street['shortname'];
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['value'] = $info[$key];
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['value'] = $info[$key];
                }
                // формирование дополнительных данных для формы (не из основной таблицы)
                //$type_objects = $db->fetchall("SELECT id,title_genitive as title FROM ".$sys_tables['type_objects_sale']);
                //foreach($type_objects as $key=>$val) $mapping['campaigns']['id_type_object']['values'][$val['id']] = $val['title'];
                $agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']." ORDER BY title");
                foreach($agencies as $key=>$val) $mapping['campaigns']['id_agency']['values'][$val['id']] = $val['title'];
                $build_completes = $db->fetchall("SELECT id, title FROM ".$sys_tables['build_complete']." WHERE id >=70 OR id<=5");
                foreach($build_completes as $key=>$val) $mapping['campaigns']['id_build_complete']['values'][$val['id']] = $val['title'];
                $building_types = $db->fetchall("SELECT id, title FROM ".$sys_tables['building_types']." ORDER BY title ASC");
                foreach($building_types as $key=>$val) $mapping['campaigns']['id_building_type']['values'][$val['id']] = $val['title'];
                $way_types = $db->fetchall("SELECT id,title FROM ".$sys_tables['way_types']." ORDER BY title ASC");
                foreach($way_types as $key=>$val) $mapping['campaigns']['id_way_type']['values'][$val['id']] = $val['title'];
                
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
        
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['campaigns']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['campaigns'][$key]['value'])) $info[$key] = $mapping['campaigns'][$key]['value'];
                        }
                        //определение гео данных для полученного geo_id
                        $geolocation = $db->fetch("SELECT id_region,id_area,id_city,id_place FROM ".$sys_tables['geodata']." WHERE `id` = ".$mapping['campaigns']['geolocation_id']['value']);
                        $info['id_region'] = $geolocation['id_region'];
                        $info['id_area']   = $geolocation['id_area'];
                        $info['id_city']   = $geolocation['id_city'];
                        $info['id_place']  = $geolocation['id_place'];;
                        
                        // сохранение в БД
                        if(empty($info['chpu_title'])) $info['chpu_title'] = createCHPUTitle($info['title']);
                        if(!empty($info['discount'])) $info['cost'] = $info['old_cost'] * ((100-$info['discount']) / 100);
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['campaigns'], $info, 'id') or die($db->error);
                            $redirect = Request::GetString('redirect',METHOD_GET);
                            if(!empty($redirect)){
                                $id_campaign = $info['id'];
                                Host::Redirect('/admin/sale/offers/add/?f_campaign='.$id_campaign);
                            }
                        } else {
                            $res = $db->insertFromArray($sys_tables['campaigns'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/sale/campaigns/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления,  значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['campaigns']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $res = $db->querys("DELETE FROM ".$sys_tables['campaigns']." WHERE id=?", $id);
                //удаление фото агентства
                $del_photos = Photos::DeleteAll('campaigns',$id);    
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            default:
                $module_template = 'admin.campaigns.list.html';
                // формирование списка для фильтра
                $agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']." ORDER BY title");
                Response::SetArray('agencies',$agencies);
                // формирование фильтра по названию
                $conditions = array();
                if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                if(!empty($filters['agency'])) $conditions['agency'] = "`id_agency` = ".$db->real_escape_string($filters['agency']);
                if(!empty($filters['published'])) $conditions['published'] = "`published` = ".$db->real_escape_string($filters['published'])."";
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);        
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['campaigns'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = array();
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/sale/campaigns'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
        
                $sql = "SELECT ".$sys_tables['campaigns'].".*,
                            IF(".$sys_tables['campaigns'].".published = 1,'Активное',
                                IF(".$sys_tables['campaigns'].".published = 2,'В архиве','На модерации')
                            ) as status_title,
                            DATEDIFF(".$sys_tables['campaigns'].".date_end, NOW()) as time_left,
                            DATE_FORMAT(".$sys_tables['campaigns'].".date_end,'%e %M %Y') as end_date, 
                            CONCAT_WS('/','".Config::$values['img_folders']['campaigns']."','sm',LEFT(photos.name,2)) as campaign_photo_folder,
                            photos.name as campaign_photo
                        FROM ".$sys_tables['campaigns']."
                        LEFT JOIN  ".$sys_tables['campaigns_photos']." photos ON photos.id_parent=".$sys_tables['campaigns'].".id";
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " GROUP BY ".$sys_tables['campaigns'].".id
                          ORDER BY title";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql); 
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
                break;
        }
        break; 

 /*********************************\
    |*  Работа с предложениями          *|
    \*********************************/
    case 'offers':
        // переопределяем экшн
        $ajax_action = Request::GetString('action', METHOD_POST);
        $action = empty($this_page->page_parameters[1]) ? "" : (empty($ajax_action) ? $this_page->page_parameters[1]: $ajax_action);
        Photos::$__folder_options = array(
                                    'thumb'=>array(70,70,'cut',80)
                                    ,'sm'=>array(235,150,'cut',80)
                                    ,'med'=>array(485,325,'',80)
                                    ,'big'=>array(800,600,'',70)
        );
        switch($action){
            /**************************\
            |*  Работа с фотографиями  *|
            \**************************/
            case 'photos':
                if($ajax_mode){
                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                    switch($action){
                        case 'list':
                            //получение списка фотографий
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            if(!empty($id)){
                                $list = Photos::getList('offers',$id);
                                if(!empty($list)){
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $list;
                                    $ajax_result['folder'] = Config::$values['img_folders']['offers'];
                                } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'add':
                            //загрузка фотографий
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);                
                            if(!empty($id)){
                                //default sizes removed 120x100
                                $res = Photos::Add('offers',$id,false,false,false,false,false,true);
                                if(!empty($res)){
                                    if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                    else {
                                        $ajax_result['ok'] = true;
                                        $ajax_result['list'] = $res;
                                    }
                                } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'setTitle':
                            //добавление названия
                            $id = Request::GetInteger('id_photo', METHOD_POST);                
                            $title = Request::GetString('title', METHOD_POST);                
                            if(!empty($id)){
                                $res = Photos::setTitle('offers',$id, $title);
                                $ajax_result['last_query'] = '';
                                if(!empty($res)) $ajax_result['ok'] = true;
                                else $ajax_result['error'] = 'Невозможно выполнить обновление названия фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;    
                        case 'setMain':
                            // установка флага "главное фото" для объекта
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::setMain('offers', $id, $id_photo);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно установить статус';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'del':
                            //удаление фото
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::Delete('offers',$id_photo);
                                
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                    }
                }
                break;
            case 'add':
            case 'edit':
                $GLOBALS['js_set'][]='/js/form.validate.js';
                $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                
                $module_template = 'admin.offers.edit.html';
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['offers']);
                    if(!empty($filters['campaign'])) $info['id_campaign'] = $filters['campaign'];
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT main.*
                                        FROM ".$sys_tables['offers']." main 
                                        WHERE main.id=?", $id);
                }
                
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['offers'][$key])) $mapping['offers'][$key]['value'] = $info[$key];
                }
                // формирование дополнительных данных для формы (не из основной таблицы)
                $campaigns = $db->fetchall("SELECT id, title FROM ".$sys_tables['campaigns']);
                foreach($campaigns as $key=>$val) $mapping['offers']['id_campaign']['values'][$val['id']] = $val['title'];
                $toilets = $db->fetchall("SELECT id, title FROM ".$sys_tables['toilets']);
                foreach($toilets as $key=>$val) $mapping['offers']['id_toilet']['values'][$val['id']] = $val['title'];
                $facings = $facings = $db->fetchall("SELECT id,title FROM ".$sys_tables['facings']." ORDER BY title");
                foreach($facings as $key=>$val) $mapping['offers']['id_facing']['values'][$val['id']] = $val['title'];
                $balcons = $balcons = $db->fetchall("SELECT id,title FROM ".$sys_tables['balcons']." ORDER BY title");
                foreach($balcons as $key=>$val) $mapping['offers']['id_balcon']['values'][$val['id']] = $val['title'];
                
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
        
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['offers'][$key])) $mapping['offers'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['offers']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['offers'][$key])) $mapping['offers'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['offers'][$key]['value'])) $info[$key] = $mapping['offers'][$key]['value'];
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['offers'], $info, 'id') or die($db->error);
                            $redirect = Request::GetString('redirect',METHOD_GET);
                            if(!empty($redirect)){
                                $id_campaign = $info['id_campaign'];
                                Host::Redirect('/admin/sale/offers/add/?f_campaign='.$id_campaign);
                            }

                        } else {
                            $res = $db->insertFromArray($sys_tables['offers'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/sale/offers/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления,  значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['offers']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $res = $db->querys("DELETE FROM ".$sys_tables['offers']." WHERE id=?", $id);
                //удаление фото агентства
                $del_photos = Photos::DeleteAll('offers',$id);    
                $results['delete'] = $res;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete'], 'ids'=>array($id));
                    break;
                }
            default:
                $module_template = 'admin.offers.list.html';
                // формирование списка для фильтра
                $agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']." ORDER BY title");
                Response::SetArray('agencies',$agencies);
                // формирование фильтра по названию
                $conditions = array();
                if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                if(!empty($filters['agency'])) {
                    $campaigns = $db->fetchall("SELECT id,title FROM ".$sys_tables['campaigns']." WHERE  id_agency = ? ORDER BY title",false,$filters['agency']);
                    Response::SetArray('campaigns',$campaigns);
                    if(!empty($filters['campaign'])) $conditions['campaign'] = "`id_campaign` = ".$db->real_escape_string($filters['campaign']);
                }
                if(!empty($filters['published'])) $conditions['published'] = "`published` = ".$db->real_escape_string($filters['published'])."";
                // формирование списка для фильтра
                echo $condition = implode(" AND ",$conditions);        
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['offers'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = array();
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/sale/offers'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
        
                $sql = "SELECT ".$sys_tables['offers'].".*,
                            IF(".$sys_tables['offers'].".published = 1,'Активное',
                                IF(".$sys_tables['offers'].".published = 2,'В архиве','На модерации')
                            ) as status_title,
                            CONCAT_WS('/','".Config::$values['img_folders']['offers']."','sm',LEFT(photos.name,2)) as photo_folder,
                            photos.name as photo,
                            ".$sys_tables['facings'].".title as facing_title,
                            ".$sys_tables['campaigns'].".title as campaign_title,
                            ".$sys_tables['toilets'].".title as toilet_title
                        FROM ".$sys_tables['offers']."
                        LEFT JOIN  ".$sys_tables['offers_photos']." photos ON photos.id_parent=".$sys_tables['offers'].".id
                        LEFT JOIN  ".$sys_tables['facings']." ON ".$sys_tables['facings'].".id=".$sys_tables['offers'].".id_facing
                        LEFT JOIN  ".$sys_tables['campaigns']." ON ".$sys_tables['campaigns'].".id=".$sys_tables['offers'].".id_campaign
                        LEFT JOIN  ".$sys_tables['toilets']." ON ".$sys_tables['toilets'].".id=".$sys_tables['offers'].".id_toilet";
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " GROUP BY ".$sys_tables['offers'].".id
                          ORDER BY title";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql); 
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
                break;
        }
        break;               
    /******************************\
    |*  Работа с телефонами       *|
    \******************************/
    case 'phones':
        // переопределяем экшн
        $ajax_action = Request::GetString('action', METHOD_POST);
        $action = empty($this_page->page_parameters[1]) ? "" : (empty($ajax_action) ? $this_page->page_parameters[1]: $ajax_action);
        switch($action){
            case 'add':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $res = $db->querys("DELETE FROM ".$sys_tables['phones']." WHERE id=?", $id);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $res = $db->querys("DELETE FROM ".$sys_tables['phones']." WHERE id=?", $id);
            default:
                $module_template = 'admin.phones.list.html';
                // формирование списка для фильтра
                $agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']."  ORDER BY title");
                Response::SetArray('agencies',$agencies);
                // формирование фильтра по названию
                $conditions = array();
                if(!empty($filters['agency'])) {
                    $conditions['agency'] = "`id_agency` = ".$db->real_escape_string($filters['agency']);
                    // формирование списка для фильтра
                    $condition = implode(" AND ",$conditions);        

                    $sql = "SELECT ".$sys_tables['phones'].".*
                                ".$sys_tables['campaigns'].".title as campaign_title
                            FROM ".$sys_tables['phones']."
                            LEFT JOIN  ".$sys_tables['campaigns']." photos ON ".$sys_tables['campaigns'].".id = ".$sys_tables['phones'].".id_campaign
                            WHERE ".$condition."
                            GROUP BY ".$sys_tables['phones'].".id
                            ORDER BY title";
                    $list = $db->fetchall($sql); 
                }
                // формирование списка
                if(!empty($list)) Response::SetArray('list', $list);
                break;
        }
        break; 
        
    /*********************************\
    |*  Работа с тарифами            *|
    \*********************************/
    case 'tarifs':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[1]) ? "" : (empty($ajax_action) ? $this_page->page_parameters[1]: $ajax_action);
        switch($action){
            case 'add':
            case 'edit':
                $GLOBALS['js_set'][]='/js/form.validate.js';
                $module_template = 'admin.tarifs.edit.html';
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['tarifs']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT main.*
                                        FROM ".$sys_tables['tarifs']." main 
                                        WHERE main.id=?", $id);
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['tarifs'][$key])) $mapping['tarifs'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['tarifs'][$key])) $mapping['tarifs'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['tarifs']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['tarifs'][$key])) $mapping['tarifs'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['tarifs'][$key]['value'])) $info[$key] = $mapping['tarifs'][$key]['value'];
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['tarifs'], $info, 'id') or die($db->error);
                        } else {
                            $res = $db->insertFromArray($sys_tables['tarifs'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/sale/tarifs/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления,  значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['tarifs']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $res = $db->querys("DELETE FROM ".$sys_tables['tarifs']." WHERE id=?", $id);
                //удаление фото агентства
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            default:
                $module_template = 'admin.tarifs.list.html';
                // формирование списка для фильтра
                $agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']."  ORDER BY title");
                Response::SetArray('agencies',$agencies);
                // формирование фильтра по названию
                $conditions = array();
                $condition = implode(" AND ",$conditions);        
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['tarifs'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = array();
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/sale/tarifs'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
        
                $sql = "SELECT ".$sys_tables['tarifs'].".* FROM ".$sys_tables['tarifs'];
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql); 
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
                break;
        }
        break;      
          
}
// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>