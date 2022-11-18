<?php

$GLOBALS['js_set'][] = '/modules/webinars/ajax_actions.js';
$GLOBALS['css_set'][] = '/modules/webinars/styles.css';

if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

require_once('includes/class.paginator.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Вебинары'));
// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['webinar'] = Request::GetString('f_webinar',METHOD_GET);
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
if(!empty($filters['webinar'])) {
    $filters['webinar'] = urldecode($filters['webinar']);
    $get_parameters['f_webinar'] = $filters['webinar'];
}
$filters['title'] = Request::GetString('f_title',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; 
//страница для паджинатора
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
    
    //фотки
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
                        $list = Photos::getList('webinars',$id);
                        if(!empty($list)){
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                            $ajax_result['folder'] = Config::$values['img_folders']['webinars'];
                        } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    //загрузка фотографий
                    //id текущей новости
                    Photos::$__folder_options=array(
                        'sm'=>array(170,100,'cut',65),
                        'med'=>array(370,220,'cut',75),
                        'big'=>array(800,600,'',70)
                    );// свойства папок для загрузки и формата фотографий
                    $id = Request::GetInteger('id', METHOD_POST);                
                    if(!empty($id)){
                        //default sizes removed
                        $res = Photos::Add('webinars',$id,false,false,false,false,false, true);
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
                        $res = Photos::setTitle('webinars',$id, $title);
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
                        $res = Photos::Delete('webinars',$id_photo);
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
                        $res = Photos::setMain('webinars', $id, $id_photo);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно установить статус';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'sort':
                    // сортировка фото 
                    //порядок следования фотографий
                    $order = Request::GetArray('order', METHOD_POST);
                    if(!empty($order)){
                        $res = Photos::Sort('webinars', $order);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно отсортировать';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;
    //###########################################################################
    // список записей
    //###########################################################################
    case 'users':
    case 'users_mails':
        // определяем запрошенный экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            //###########################################################################
            // удаление записи
            //###########################################################################
            case 'del':
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $res = $db->querys("DELETE FROM ".$sys_tables['webinars_users']." WHERE id=?", $id);
                    $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                    if($ajax_mode){
                        $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                        break;
                    }
                break;

          default:          
            $module_template = 'admin.'.$this_page->page_parameters[1].'.list.html';
            $webinars = $db->fetchall("SELECT id, title FROM ".$sys_tables['webinars']." ORDER BY title");
            Response::SetArray('webinars', $webinars);
            $where = false;
            if($this_page->page_parameters[1] == 'users_mails') $count = 1000;
            else $count = 30;
            //фильтр по названию события
            if(!empty($filters['webinar'])) $where = " `id_parent` = ".$db->real_escape_string($filters['webinar']);
            // создаем пагинатор для списка
            $paginator = new Paginator($sys_tables['webinars_users'], $count, $where);
            // get-параметры для ссылок пагинатора
            $get_in_paginator = array();
            foreach($get_parameters as $gk=>$gv){
                if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
            }
            // ссылка пагинатора
            $paginator->link_prefix = '/admin/service/webinars/users'             // модуль
                                      ."/?"                                       // конечный слеш и начало GET-строки
                                      .implode('&',$get_in_paginator)             // GET-строка
                                      .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
            if($paginator->pages_count>0 && $paginator->pages_count<$page){
                Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                exit(0);
            }
            //выбираем страницы для отображения
            $sql = "SELECT ".$sys_tables['webinars_users'].".*,
                           CONCAT(".$sys_tables['users'].".lastname, ' ', ".$sys_tables['users'].".name) as user_name,
                           ".$sys_tables['webinars'].".title as webinar_title,
                           ".$sys_tables['users'].".email
                    FROM ".$sys_tables['webinars_users']."
                    LEFT JOIN ".$sys_tables['webinars']." ON ".$sys_tables['webinars'].".id = ".$sys_tables['webinars_users'].".id_parent
                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['webinars_users'].".id_user";
            if(!empty($where)) $sql.=" WHERE ".$where;
            $sql .= " GROUP BY ".$sys_tables['users'].".id 
                      ORDER BY `email`";
            $sql .= " LIMIT ".$paginator->getLimitString($page);
            $list = $db->fetchall($sql);
            
            // формирование списка
            Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Вебинары' : $this_page->page_seo_h1);
            Response::SetArray('list', $list);
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }else Response::SetInteger('paginator_items_count',$paginator->items_count);
            break;
        }
        break;
    //###########################################################################
    // удаление записи
    //###########################################################################
    case 'del':
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            $res = $db->querys("DELETE FROM ".$sys_tables['webinars']." WHERE id=?", $id);
            $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                break;
            }
        break;
    //###########################################################################
    // добавление и редактирование записи
    //###########################################################################
    case 'add':
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/modules/content/tags_autocomplette.js';
        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
        $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
        $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['webinars']);
            $info['datetime'] = date('d.m.Y H:i');
        }
        else{
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            // получение данных из БД
            $info = $db->fetch("SELECT *, DATE_FORMAT(`datetime`,'%d.%m.%Y %H:%i') as datetime
                                FROM ".$sys_tables['webinars']." 
                                WHERE id=?", $id);
        }
        
        $module_template = 'admin.webinars.edit.html';
        
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['webinars'][$key])) $mapping['webinars'][$key]['value'] = $info[$key];
        }
        
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['webinars'][$key])) $mapping['webinars'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['webinars']);
            //проверяем, что url правильный
            
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['webinars'][$key])) $mapping['webinars'][$key]['error'] = $value;
            }
            if(empty($mapping['webinars']['url']['value']) && !empty($mapping['webinars']['title']['value'])) $mapping['webinars']['url']['value'] = createCHPUTitle($mapping['webinars']['title']['value']);
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['webinars'][$key]['value'])) $info[$key] = $mapping['webinars'][$key]['value'];
                }
                $info['datetime'] = date("Y-m-d H:i:s", strtotime($info['datetime'])); 
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['webinars'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['webinars'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/service/webinars/edit/'.$new_id.'/'));
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
        
        Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Вебинары' : $this_page->page_seo_h1);
        // запись данных для отображения на странице
        Response::SetArray('data_mapping',$mapping['webinars']);
        break;
    //###########################################################################
    // список записей
    //###########################################################################
    default:
        if ((isset($page))&&($page==0)) Host::Redirect("admin/service/webinars/?page=1"); 

        $module_template = 'admin.webinars.list.html';
        $where = false;
        //фильтр по названию события
        if(!empty($filters['title'])) $conditions['title'] = " `title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
        if(!empty($filters['status'])) $conditions['status'] = "`status` = ".$filters['status'];
        if(!empty($conditions)) $where = implode(" AND ", $conditions);
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['webinars'], 30, $where);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = array();
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/service/webinars'                  // модуль
                                  ."/?"                                       // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)             // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }
        //выбираем страницы для отображения
        $sql = "SELECT *, DATE_FORMAT(`datetime`,'%d.%m.%Y %H:%i') as normal_datetime FROM ".$sys_tables['webinars'];
        if(!empty($where)) $sql.=" WHERE ".$where;
        $sql .= " ORDER BY `datetime` DESC,`id` DESC";
        $sql .= " LIMIT ".$paginator->getLimitString($page);
        $list = $db->fetchall($sql);
        
        // формирование списка
        Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Вебинары' : $this_page->page_seo_h1);
        Response::SetArray('list', $list);
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }else Response::SetInteger('paginator_items_count',$paginator->items_count);
}

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));
?>