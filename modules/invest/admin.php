<?php
//$GLOBALS['js_set'][] = '/modules/housing_estates/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos' ) )  require_once('includes/class.photos.php');;

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Презентация'));

//categories
//"Success stories"  "Ongoing" "Future projects"

// собираем GET-параметры
$get_parameters = array();
$filters = array();

$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
if(!empty($filters['category'])) $get_parameters['f_category'] = $filters['category']; else $filters['category'] = 0;

$filters['status'] = Request::GetString('f_status',METHOD_GET);
if($filters['status'] != null) $get_parameters['f_status'] = $filters['status']; 
else unset($filters['status']);


$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else {
    $get_parameters['page'] = $page;
}
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch(true){
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // фотки
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $ajax_mode && $action == 'photos':
        
        $ajax_result['error'] = '';
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        
        switch($action){
            case 'list':
                //получение списка фотографий
                //id текущей новости
                $id = Request::GetInteger('id', METHOD_POST);
                if(!empty($id)){
                    $list = Photos::getList('invest',$id);
                    if(!empty($list)){
                        $ajax_result['ok'] = true;
                        $ajax_result['list'] = $list;
                        $ajax_result['folder'] = Config::$values['img_folders']['invest'];
                    } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                } else $ajax_result['error'] = 'Неверные входные параметры';
                break;
            case 'add':
                //загрузка фотографий
                Photos::$__folder_options=array(
                        'sm'=>array(110,82,'cut',65),
                        'med'=>array(560,415,'cut',75),
                        'big'=>array(800,600,'',70),
                        'very_big'=>array(2000, 800,'',70)
                );  
                $id = Request::GetInteger('id', METHOD_POST);                

                if(!empty($id)){
                    //removed default sizes
                    $res = Photos::Add('invest',$id,false,false,false,false,false,true);
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
                    $res = Photos::setTitle('invest',$id, $title);
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
                    $res = Photos::Delete('invest',$id_photo);
                    if(!empty($res)){
                        $ajax_result['ok'] = true;
                    } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                } else $ajax_result['error'] = 'Неверные входные параметры';
                break;
        }
        break;
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // редактирование карточек
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'add':
    case $action == 'edit':
        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        //$GLOBALS['js_set'][] = '/modules/cottages/gmap_handler.js';
        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
        
        $module_template = 'admin.invest.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        
        if($action=='add') 
            $info = $db->prepareNewRecord($sys_tables['invest']);
        else 
            $info = $db->fetch("SELECT * FROM ".$sys_tables['invest']." WHERE id=?", $id) ;
        
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['invest'][$key])) $mapping['invest'][$key]['value'] = $info[$key];
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);
        
        // формирование дополнительных данных для формы (не из основной таблицы)
        $categories = $db->fetchall("SELECT id,title_eng AS title FROM ".$sys_tables['invest_categories']." ORDER BY title");
        foreach($categories as $key=>$val){
            $mapping['invest']['id_category']['values'][$val['id']] = $val['title'];
        }
        
        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['invest'][$key])) $mapping['invest'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['invest']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['invest'][$key])) $mapping['invest'][$key]['error'] = $value;
            }
            //проверка на похожее название
            if($action == 'add') $item = $db->fetch("SELECT * FROM ".$sys_tables['invest']." WHERE title = ?", $mapping['invest']['title']['value']);
            else if($action == 'edit') $item = $db->fetch("SELECT * FROM ".$sys_tables['invest']." WHERE title = ? AND id != ?", $mapping['invest']['title']['value'], $info['id']);
            if(!empty($item)) $errors['title'] = $mapping['invest']['title']['error'] = 'Такое название КП уже существует';
            
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['invest'][$key]['value'])) $info[$key] = $mapping['invest'][$key]['value'];
                }
                // сохранение в БД
                 //дата дообавления объекта
                $info['date_change'] = date("Y-m-d H:i:s");
                if($action=='edit'){
                    //статус - отредактирован объект
                    $res = $db->updateFromArray($sys_tables['invest'], $info, 'id') or die($db->error);
                    //редирект по нажатию на сохранить+перейти в список поселков
                    $redirect =  Request::GetString('redirect',METHOD_GET);
                    if(!empty($redirect)) {
                        if(!empty($cookie_admin_params)){
                            $params  = array();
                            foreach($cookie_admin_params as $k=>$val) $params[] = $k.'='.$val;
                            Host::Redirect("/admin/content/invest/?".implode('&',$params));
                        }
                        elseif(empty($cookie_page)) $cookie_page = 1;
                        Host::Redirect("/admin/content/invest/?page=".$cookie_page);
                    }
                } else {
                    //дата дообавления объекта
                    $info['idate'] = date('Y-m-d');

                    $res = $db->insertFromArray($sys_tables['invest'], $info, 'id');
                    $new_id = $db->insert_id;
                    //обновление ЧПУ
                    $chpu_title = createCHPUTitle($info['title']);
                    $chpu_item = $db->fetch("SELECT * FROM ".$sys_tables['invest']." WHERE alias = ?", $chpu_title);
                    $db->querys("UPDATE ".$sys_tables['invest']." SET alias = ? WHERE id = ?", $chpu_title.(!empty($chpu_item)?"_".$new_id:""), $new_id);
                    if(!empty($res)){
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/content/invest/edit/'.$new_id.'/'));
                            exit(0);
                        }
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
        Response::SetArray('data_mapping',$mapping['invest']);
        Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
        break;
    case $action == 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $del_photos = Photos::DeleteAll('invest',$id);
        $res = $db->querys("DELETE FROM ".$sys_tables['invest']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // общий список
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    default:
        
        $where = array();
        if(isset($filters['status'])) $where[] = "status = ".$filters['status'];
        if(!empty($filters['category'])) $where[] = "id_category = ".$filters['category'];
        
        $list = $db->fetchall("SELECT * FROM ".$sys_tables['invest'].(!empty($where) ? " WHERE ".implode(" AND ",$where) : ""));
        Response::SetArray('list',$list);
        $categories = $db->fetchall("SELECT id,title_eng AS title FROM ".$sys_tables['invest_categories']." ORDER BY title");
        Response::SetArray('categories',$categories);
        $module_template = "admin.invest.list.html";
        
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>