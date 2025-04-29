<?php

//автозаполнение списка конкурсов для левого меню
//список конкурсов
$list=$db->fetchall("SELECT id,title,type,url 
                     FROM ".$sys_tables['konkurs']." ORDER BY id DESC");
$type_allow_category=array('doverie','ambition','photokonkurs');
//если конкурс одного из указанных типов, то категории нужны
foreach($list as $key=>$item){
    if (in_array($item['type'],$type_allow_category))
        $menu_mapping['service']['childs']['konkurs']['childs'][$item['id']] = array(
            'title'=>$item['title'],
            'menu'=>true,
            'childs'=>array(
                'categories'=>array('title'=>'Категории',
                'menu'=>true
                )
            )
        );
    else
        $menu_mapping['service']['childs']['konkurs']['childs'][$item['id']] = array(
           'title'=>$item['title'],
           'menu'=>true,
        );

} 

$GLOBALS['js_set'][] = '/modules/konkurs/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Конкурсы'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['category'])) $get_parameters['f_category'] = $filters['category']; 
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; 
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн (id конкурса)
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
if(!empty($action)){
    // по id получаем тип конкурса
    $item=$db->fetch("SELECT type,title FROM ".$sys_tables['konkurs']." WHERE id=?",$action);
    //запоминаем id, чтобы потом искать по нему категории и записи
    $id_konkurs=$action;
    $konkurs_title=$item['title'];
    $type=$item['type'];
}

// обработка action-ов
switch(TRUE){ 
    /**************************\
    |*  Работа с фотографиями  *|
    \**************************/
    case ($action=='photos'):
        if($ajax_mode){
            $ajax_result['error'] = '';
            // переопределяем экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            
            switch($action){
                case 'list':
                    //получение списка фотографий
                    //id текущего конкурса
                    $id = Request::GetInteger('id', METHOD_POST);
                    if(!empty($id)){
                        $item = Photos::getList('konkurs_members',$id);
                        if(!empty($item)){
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $item;
                            $ajax_result['folder'] = Config::$values['img_folders']['konkurs'];
                        } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    //загрузка фотографий
                    //id текущей записи
                    $id = Request::GetInteger('id', METHOD_POST);                
                    //задаем опции для маленькой и большой картинки
                    Photos::$__folder_options=array(
                            'sm'=>array(160,120,'cut',65),
                            'big'=>array(1200,960,'',50)
                            );                 
                    if(!empty($id)){
                        $res = Photos::Add('konkurs_members',$id,false,false,false, false, false,true);
                        $ajax_result['res']=$res;
                        $ajax_result['query']='';
                        $ajax_result['error']=$db->error;
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
                        $res = Photos::setTitle('konkurs_members',$id, $title);
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
                        $res = Photos::Delete('konkurs_members',$id_photo);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;
    case !empty($type):
        //добавление title
        Response::SetString('konkurs_title',$konkurs_title);
        Response::SetString('konkurs_url',$action);
        switch($type){
            case 'doverie':
                $this_page->manageMetadata(array('title'=>'Доверие потребителя'));
                //указываем, что не надо удалять фотографии при удалении участника
                $delete_photo=FALSE;
                break;
            case 'ambition':
                $this_page->manageMetadata(array('title'=>'Премия Амбиция'));
                //указываем, что не надо удалять фотографии при удалении участника
                $delete_photo=FALSE;
                break;
            case 'photokonkurs':
                $this_page->manageMetadata(array('title'=>'Фотоконкурс'));
                
                //чтобы можно было загружать фотографии
                $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                
                //устанавливаем флаг, по которому появится добавление фотографий
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                Response::SetString('add_photo',TRUE && !empty($id));
                
                //указываем, что надо удалить фотографии при удалении участника
                $delete_photo = TRUE;
                break;
        }
        
        //
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2]; 
        switch($action){
            //////////////////////////////////////////////////////////////////////////////
            // управление категориями
            //////////////////////////////////////////////////////////////////////////////
            case 'categories':
                // переопределяем экшн
                $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                switch($action){
                    //////////////////////////////////////////////////////////////////////////////
                    // добавление и редактирование категорий
                    //////////////////////////////////////////////////////////////////////////////
                    case 'add':
                    case 'edit':
                        $module_template = 'admin.categories.edit.html';
                        $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                        if($action=='add'){
                            // создание болванки новой записи
                            $info = $db->prepareNewRecord($sys_tables['konkurs_members_categories']);
                        } else {
                            // получение данных из БД
                            $info = $db->fetch("SELECT *
                                                FROM ".$sys_tables['konkurs_members_categories']." 
                                                WHERE id=? AND id_konkurs=?", $id, $id_konkurs) ;
                        }
                        
                        // перенос дефолтных (считанных из базы) значений в мэппинг формы
                        foreach($info as $key=>$field){
                            if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $info[$key];
                        }
                        // получение данных, отправленных из формы
                        $post_parameters = Request::GetParameters(METHOD_POST);
                
                        // если была отправка формы - начинаем обработку
                        if(!empty($post_parameters['submit'])){
                            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                            foreach($post_parameters as $key=>$field){
                                if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $post_parameters[$key];
                            }
                            
                            //заполняем id_parent
                            $mapping['categories']['id_konkurs']['value']=$id_konkurs;
                            
                            // проверка значений из формы
                            $errors = Validate::validateParams($post_parameters,$mapping['categories']);
                            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                            foreach($errors as $key=>$value){
                                if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['error'] = $value;
                            }
                            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                            if(empty($errors)) {
                                // подготовка всех значений для сохранения
                                foreach($info as $key=>$field){
                                    if(isset($mapping['categories'][$key]['value'])) $info[$key] = $mapping['categories'][$key]['value'];
                                }
                                // сохранение в БД
                                if($action=='edit'){
                                    $res = $db->updateFromArray($sys_tables['konkurs_members_categories'], $info, 'id') or die($db->error);
                                } else {
                                    $res = $db->insertFromArray($sys_tables['konkurs_members_categories'], $info, 'id');
                                    if(!empty($res)){
                                        $new_id = $db->insert_id;
                                        // редирект на редактирование свеженькой страницы
                                        if(!empty($res)) {
                                            header('Location: '.Host::getWebPath('/admin/service/konkurs/'.$id_konkurs.'/categories/edit/'.$new_id.'/'));
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
                        Response::SetArray('data_mapping',$mapping['categories']);
                        break;
                    //////////////////////////////////////////////////////////////////////////////
                    // удаление категорий
                    //////////////////////////////////////////////////////////////////////////////
                    case 'del':
                        $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                        $res = $db->querys("DELETE FROM ".$sys_tables['konkurs_members_categories']." WHERE id=?", $id);
                        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                        if($ajax_mode){
                            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                            break;
                        }
                    //////////////////////////////////////////////////////////////////////////////
                    // список категорий
                    //////////////////////////////////////////////////////////////////////////////
                    default:
                        $module_template = 'admin.categories.list.html';
                        // формирование фильтра по названию
                        $conditions = array(' '.$sys_tables['konkurs_members_categories'].'.'.'id_konkurs='.$id_konkurs.' ');
                        if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                        // формирование списка для фильтра
                        $condition = implode(" AND ",$conditions);        
                        // создаем пагинатор для списка
                        $paginator = new Paginator($sys_tables['konkurs_members_categories'], 30, $condition);
                        // get-параметры для ссылок пагинатора
                        $get_in_paginator = [];
                        foreach($get_parameters as $gk=>$gv){
                            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                        }
                        // ссылка пагинатора
                        $paginator->link_prefix = '/admin/service/konkurs/'.$id_konkurs.'/categories'                  // модуль
                                                  ."/?"                                       // конечный слеш и начало GET-строки
                                                  .implode('&',$get_in_paginator)             // GET-строка
                                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                        if($paginator->pages_count>0 && $paginator->pages_count<$page){
                            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                            exit(0);
                        }
                
                        $sql = "SELECT ".$sys_tables['konkurs_members_categories'].".* FROM ".$sys_tables['konkurs_members_categories'];
                        if(!empty($condition)) $sql .= " WHERE ".$condition;
                        $sql .= " ORDER BY position ASC, id ";
                        $sql .= " LIMIT ".$paginator->getLimitString($page); 
                        $list = $db->fetchall($sql);
                        // формирование списка
                        Response::SetArray('list', $list);
                        if($paginator->pages_count>1){
                            Response::SetArray('paginator', $paginator->Get($page));
                        }
                        break;
                }
                break;
            //////////////////////////////////////////////////////////////////////////////
            //добавление и редактирование участников
            //////////////////////////////////////////////////////////////////////////////        
            case 'add':
            case 'edit':
                

                $module_template = 'admin.konkurs.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['konkurs_members']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['konkurs_members']." 
                                        WHERE id=? AND id_konkurs=?", $id, $id_konkurs) ;
                }
                
                //отображение полей
                switch($type){
                    case 'doverie':
                    case 'ambition':
                        unset($mapping['members']['phone']);
                        unset($mapping['members']['email']);
                        unset($mapping['members']['text']);
                        break;
                }
                
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['members'][$key])) $mapping['members'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['konkurs_members_categories']." WHERE id_konkurs=".$id_konkurs." ORDER BY id");
                foreach($categories as $key=>$val){
                    $mapping['members']['id_category']['values'][$val['id']] = $val['title'];
                }
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['members'][$key])) $mapping['members'][$key]['value'] = $post_parameters[$key];
                    }
                    
                    //заполняем id_parent
                    $mapping['members']['id_konkurs']['value']=$id_konkurs;
                    
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['members']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['members'][$key])) $mapping['members'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['members'][$key]['value'])) $info[$key] = $mapping['members'][$key]['value'];
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['konkurs_members'], $info, 'id') or die($db->error);
                        } else {
                            $res = $db->insertFromArray($sys_tables['konkurs_members'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/service/konkurs/'.$id_konkurs.'/edit/'.$new_id.'/'));
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
                Response::SetArray('data_mapping',$mapping['members']);
                break;
            //////////////////////////////////////////////////////////////////////////////
            // удаление участников
            //////////////////////////////////////////////////////////////////////////////
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->querys("DELETE FROM ".$sys_tables['konkurs_members']." WHERE id=?", $id);
                $results['delete'] = $res ? $id : -1;
                if ($delete_photo) {
                    Photos::$__folder_options=array(
                            'sm'=>array(160,120,'cut',65),
                            'big'=>array(1200,960,'',50)
                            ); 
                    Photos::DeleteAll('konkurs',$id);
                }                    
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            //////////////////////////////////////////////////////////////////////////////
            // список участников
            //////////////////////////////////////////////////////////////////////////////
            default:
                $module_template = 'admin.konkurs.list.html';
                // формирование списка
                $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['konkurs_members_categories']." WHERE id_konkurs=".$db->real_escape_string($id_konkurs)." ORDER BY id");
                Response::SetArray('categories',$categories);
                
                $conditions = array(' '.$sys_tables['konkurs_members'].'.'.'id_konkurs='.$id_konkurs.' ');
                if(!empty($filters)){
                    if(!empty($filters['title'])) $conditions['title'] = $sys_tables['konkurs_members'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                    if(!empty($filters['category'])) $conditions['category'] = $sys_tables['konkurs_members'].".`id_category` = ".$db->real_escape_string($filters['category'])."";
                    if(!empty($filters['status'])) $conditions['status'] = "`status` = ".$filters['status'];
                }
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);        
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['konkurs_members'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/service/konkurs/'.$id_konkurs       // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT ".$sys_tables['konkurs_members'].".*, 
                        ".$sys_tables['konkurs_members_categories'].".title as category_title,
                        ".$sys_tables['konkurs_members_photos'].".`name` as `photo`, 
                        LEFT (".$sys_tables['konkurs_members_photos'].".`name`,2) as `subfolder`
                        FROM ".$sys_tables['konkurs_members']."
                        LEFT JOIN  ".$sys_tables['konkurs_members_photos']." ON ".$sys_tables['konkurs_members_photos'].".id=".$sys_tables['konkurs_members'].".id_main_photo 
                        LEFT JOIN  ".$sys_tables['konkurs_members_categories']." ON ".$sys_tables['konkurs_members_categories'].".id=".$sys_tables['konkurs_members'].".id_category ";
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " GROUP BY ".$sys_tables['konkurs_members'].".id ";
                $sql .= " ORDER BY ".$sys_tables['konkurs_members'].".amount DESC, ".$sys_tables['konkurs_members'].".title";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetString('img_folder',Config::Get('img_folders/konkurs'));
                Response::SetArray('paginator', $paginator->Get($page));
                //всего голосов
                Response::SetInteger( 'total_votes', $db->fetch( " SELECT SUM(amount) as total_votes FROM " . $sys_tables['konkurs_members'] . " WHERE " . $condition )['total_votes'] );
                break;
            }
        //
        break;    
    default:
        //переопределяем $action
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch($action){
            //////////////////////////////////////////////////////////////////////////////
            // добавление и редактирование конкурсов
            //////////////////////////////////////////////////////////////////////////////
            case 'add':
            case 'edit':
                $module_template = 'admin.konkurs.edit.html';
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['konkurs']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['konkurs']." 
                                        WHERE id=?", $id) ;
                }
                
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['konkurs'][$key])) $mapping['konkurs'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                // формирование дополнительных данных для формы (не из основной таблицы)
                $types = $db->fetchall("SELECT DISTINCT type FROM ".$sys_tables['konkurs']." ORDER BY id");
                foreach($types as $key=>$val){
                    $mapping['konkurs']['type']['values'][$val['type']] = $val['type'];
                }
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['konkurs'][$key])) $mapping['konkurs'][$key]['value'] = $post_parameters[$key];
                    }
                    
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['konkurs']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['konkurs'][$key])) $mapping['konkurs'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['konkurs'][$key]['value'])) $info[$key] = $mapping['konkurs'][$key]['value'];
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['konkurs'], $info, 'id') or die($db->error);
                        } else {
                            $res = $db->insertFromArray($sys_tables['konkurs'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/service/konkurs/edit/'.$new_id.'/'));
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
                Response::SetArray('data_mapping',$mapping['konkurs']);
                break;
            //////////////////////////////////////////////////////////////////////////////
            // удаление конкурсов
            //////////////////////////////////////////////////////////////////////////////
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $type = $db->fetch("SELECT type FROM ".$sys_tables['konkurs']." WHERE id=?",$id);
                $type = $type['type'];
                //читаем список всех участников удаляемого конкурса
                $list = $db->fetchall("SELECT id FROM ".$sys_tables['konkurs_members']." WHERE id_konkurs=".$db->real_escape_string($id));
                //удаляем картинки всех участников из базы и с сервера
                //задаем те же опции
                Photos::$__folder_options=array(
                        'sm'=>array(100,150,'q',65),
                        'big'=>array(560,800,'q',50)
                        );                 
                $res=true;
                if ($type=='photokonkurs')
                    foreach($list as $key=>$item){
                        Photos::DeleteAll('konkurs',$item['id']);
                    }
                //удаляем все категории
                $res = $res && $db->querys("DELETE FROM ".$sys_tables['konkurs_members_categories']." WHERE id_konkurs=?",$id);
                //удаляем всех участников
                $res = $res && $db->querys("DELETE FROM ".$sys_tables['konkurs_members']." WHERE id_konkurs=?", $id);
                //удаляем сам конкурс
                $res = $res && $db->querys("DELETE FROM ".$sys_tables['konkurs']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
                break;
            //////////////////////////////////////////////////////////////////////////////
            // список конкурсов
            //////////////////////////////////////////////////////////////////////////////
            default:
                // собираем GET-параметры
                if(empty($filters)) $filters=[];
                if(empty($get_parameters)) $get_parameters = [];
                $filters['type'] = Request::GetString('f_type',METHOD_GET);
                $filters['status'] = Request::GetString('f_status',METHOD_GET);
                
                if(!empty($filters['title'])) {
                    $conditions[] = " title LIKE '%".$db->real_escape_string(urldecode($filters['title']))."%' ";
                }
                if(!empty($filters['type'])) {
                    $conditions[] = " type ='".$db->real_escape_string(urldecode($filters['type']))."'";
                    $get_parameters['f_type'] = $filters['type'];
                }
                if(!empty($filters['status'])) {
                    $conditions[] = " status=".$db->real_escape_string(urldecode($filters['status']))." ";
                    $get_parameters['f_status'] = $filters['status'];
                }
                
                if(!empty($conditions)) $condition = implode(' AND ',$conditions);
                else $condition = '';
                $page = Request::GetInteger('page',METHOD_GET);
                if(empty($page)) $page = 1;
                else $get_parameters['page'] = $page;
                
                $module_template = 'admin.konkurs.html';
                //создаем список типов
                $types = $db->fetchall("SELECT DISTINCT type FROM ".$sys_tables['konkurs']." ORDER BY id");
                Response::SetArray('types',$types);
                
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['konkurs'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv) if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;

                // ссылка пагинатора
                $paginator->link_prefix = '/admin/service/konkurs/?'                  // конечный слеш и начало get-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT id, title, type, url ,
                        IF(status=1,'Активен','Не активен') AS status
                        FROM ".$sys_tables['konkurs']."";
                if (!empty($condition)) $sql.=" WHERE ".$condition." ";
                $sql .= " ORDER BY id DESC";
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