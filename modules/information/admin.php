<?php
$GLOBALS['js_set'][] = '/modules/information/ajax_actions.js';
require_once('includes/class.paginator.php');
require_once('includes/class.content.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.email.php');
$docs_folder = Config::$values['docs_folders'];
Response::SetString('docs_folder', $docs_folder);
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Статьи'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['date'] = Request::GetString('f_date',METHOD_GET);
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
$filters['type'] = Request::GetInteger('f_type',METHOD_GET);
$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}

if(!empty($filters['status'])) {
    $get_parameters['f_status'] = $filters['status'];
}
if(!empty($filters['type'])) {
    $get_parameters['f_type'] = $filters['type'];
}
if(!empty($filters['category'])) {
    $get_parameters['f_category'] = $filters['category'];
}
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
    case 'del_doc':
        if($ajax_mode){
            //удаление документа
            //id товара
            $id = Request::GetInteger('id', METHOD_POST);                
            $type = Request::GetString('type', METHOD_POST);                
            if(!empty($id) && !empty($type)){
                $item =  $db->fetch("SELECT fileattach as document FROM ".$sys_tables['references_docs']." WHERE id=?",$id);
                if(!empty($item)) {
                    unlink(ROOT_PATH.'/'.Config::$values['docs_folders'].'/'.$item['document']);
                    $res = $db->query("UPDATE ".$sys_tables['references_docs']." SET fileattach = '' WHERE id=?",$id);
                    if(!empty($res)){
                        $ajax_result['ok'] = true;
                    } else $ajax_result['error'] = 'Невозможно выполнить удаление документа';
                }  else $ajax_result['error'] = 'Невозможно выполнить удаление документа';
            } else $ajax_result['error'] = 'Неверные входные параметры';
            break;
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
					//id текущей Статьи
					$id = Request::GetInteger('id', METHOD_POST);
                    if(!empty($id)){
						$list = Photos::getList('references_docs',$id);
						if(!empty($list)){
							$ajax_result['ok'] = true;
							$ajax_result['list'] = $list;
							$ajax_result['folder'] = Config::$values['img_folders']['references_docs'];
						} else $ajax_result['error'] = 'Невозможно построить список фотографий';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    //загрузка фотографий
                    //id текущей Статьи
                    Photos::$__folder_options=array(
                        'sm'=>array(110,82,'cut',65),
                        'med'=>array(355,266,'',75),
                        'big'=>array(800,600,'',70)
                    );// свойства папок для загрузки и формата фотографий
                    $id = Request::GetInteger('id', METHOD_POST);                
                    if(!empty($id)){
                        $res = Photos::Add('references_docs',$id,false,false,false,false,false, true);
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
						$res = Photos::setTitle('references_docs',$id, $title);
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
						$res = Photos::Delete('references_docs',$id_photo);
						if(!empty($res)){
							$ajax_result['ok'] = true;
						} else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'setMain':
					// установка флага "главное фото" для объекта
					//id текущей Статьи
					$id = Request::GetInteger('id', METHOD_POST);
					//id фотки
					$id_photo = Request::GetInteger('id_photo', METHOD_POST);				
					if(!empty($id_photo)){
						$res = Photos::setMain('references_docs', $id, $id_photo);
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
						$res = Photos::Sort('references_docs', $order);
						if(!empty($res)){
							$ajax_result['ok'] = true;
						} else $ajax_result['error'] = 'Невозможно отсортировать';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;
    /********************************\
    |*  Работа со списком типов  *|
    \********************************/
    /********************************\
    |*  Работа со списком типов  *|
    \********************************/
    case 'offices':
    case 'types':
        $action_type = $action;
        Response::SetArray('action_type', $action_type);
        // переопределяем экшн
        $action =  empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            case 'add':
            case 'edit':
                $module_template = 'admin.'.$action_type.'.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['references_docs_'.$action_type]);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['references_docs_'.$action_type]." 
                                        WHERE id=?", $id);
                    if(empty($info)) Host::Redirect('/admin/content/information/'.$action_type.'/add/');
                }

                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping[$action_type][$key])) $mapping[$action_type][$key]['value'] = $info[$key];
                }

                // формирование дополнительных данных для формы (не из основной таблицы)
                $categories = $db->fetchall("SELECT id, title FROM ".$sys_tables['references_docs_'.($action_type=='offices'?'types':'categories')]." ORDER BY title");
                foreach($categories as $key=>$val) $mapping[$action_type]['id_category']['values'][$val['id']] = $val['title'];

                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);


                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping[$action_type][$key])) $mapping[$action_type][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping[$action_type]);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping[$action_type][$key])) $mapping[$action_type][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping[$action_type][$key]['value'])) $info[$key] = $mapping[$action_type][$key]['value'];
                        }
                        $info['chpu_title'] = createCHPUTitle($info['title']);
                        // сохранение в БД
                        if($action_type=='edit'){
                            $res = $db->updateFromArray($sys_tables['references_docs_'.$action_type], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['references_docs_'.$action_type], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/information/'.$action_type.'/edit/'.$new_id.'/'));
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
                if($action_type=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping[$action_type]);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->query("DELETE FROM ".$sys_tables['references_docs_'.$action_type]." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            
            default:
                $module_template = 'admin.'.$action_type.'.list.html';

                // формирование дополнительных данных для формы (не из основной таблицы)
                $categories = $db->fetchall("SELECT id, title FROM ".$sys_tables['references_docs_'.($action_type=='offices'?'types':'categories')]." ORDER BY title");
                Response::SetArray('categories', $categories);
                foreach($categories as $key=>$val) $mapping[$action_type]['id_category']['values'][$val['id']] = $val['title'];

                if(!empty($filters)){
                    if(!empty($filters['title'])) $conditions[] = $sys_tables['references_docs'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                    if(!empty($filters['category'])) $conditions[] = "".$sys_tables['references_docs_'.$action_type].".`id_category` = ".$db->real_escape_string($filters['category']);
                }
                if(!empty($conditions)) $condition = implode(' AND ',$conditions);
                else $condition = '1';

                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['references_docs_'.$action_type], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/information/'.$action_type                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT ".$sys_tables['references_docs_'.$action_type].".*,
                                            ".$sys_tables['references_docs_'.($action_type=='offices'?'types':'categories')].".title as category_title  
                                        FROM ".$sys_tables['references_docs_'.$action_type]." 
                                        LEFT JOIN ".$sys_tables['references_docs_'.($action_type=='offices'?'types':'categories')]." ON ".$sys_tables['references_docs_'.$action_type].".id_category = ".$sys_tables['references_docs_'.($action_type=='offices'?'types':'categories')].".id
                                        WHERE ".$condition;
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
        }
        break;
        
    /*********************************\
    |*  Работа со списком категорий  *|
    \*********************************/
    case 'categories':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            case 'add':
            case 'edit':
                $module_template = 'admin.categories.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['references_docs_categories']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['references_docs_categories']." 
                                        WHERE id=?", $id);
                    if(empty($info)) Host::Redirect('/admin/content/information/categories/add/');
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
                            if(isset($mapping['categories'][$key]['value'])) $info[$key] = strip_tags($mapping['categories'][$key]['value'],'<a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3><blockquote>');
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['references_docs_categories'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['references_docs_categories'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/information/categories/edit/'.$new_id.'/'));
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
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->query("DELETE FROM ".$sys_tables['references_docs_categories']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
 
            default:
                $module_template = 'admin.categories.list.html';
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['references_docs_categories'], 30);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/information/categories'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT * FROM ".$sys_tables['references_docs_categories'];
                $sql .= " WHERE 1";
                $sql .= " ORDER BY `title`";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
        }
        break;
    /************************\
    |*  Работа с Статьями  *|
    \************************/
    case 'add':
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
	    $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
        
        
        
        $module_template = 'admin.references_docs.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
	        
		if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['references_docs']);
            $info['content'] = $info['content_short'] = "";
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *
                                FROM ".$sys_tables['references_docs']." 
                                WHERE id=?", $id);
            if(empty($info)) Host::Redirect('/admin/content/information/add/');
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field) if(!empty($mapping['references_docs'][$key])) $mapping['references_docs'][$key]['value'] = $info[$key];
        
        // формирование дополнительных данных для формы (не из основной таблицы)
        $types = $db->fetchall("SELECT id,title, code FROM ".$sys_tables['references_docs_types']." ORDER BY title");
        foreach($types as $key=>$val) $mapping['references_docs']['id_type']['values'][$val['id']] = $val['title'];
            
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);
	
        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['references_docs'][$key])) $mapping['references_docs'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['references_docs']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['references_docs'][$key])) $mapping['references_docs'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                if(!empty($_FILES)){
                    foreach ($_FILES as $fname => $data){
                        if ($data['error']==0) {
                            $_folder = Host::$root_path.'/'.$docs_folder.'/'; // папка для файлов документов
                            $fileParts = pathinfo($data['name']);
                            $targetExt = $fileParts['extension'];
                            $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                            move_uploaded_file($data['tmp_name'],$_folder.$_targetFile);
                            $db->query("UPDATE ".$sys_tables['references_docs']." SET `fileattach` = ? WHERE id = ?", $_targetFile, $id);
                            $post_parameters[$fname] = $_targetFile;
                            $mapping['references_docs']['fileattach']['value'] = $_targetFile;
                        }
                    }
                } 
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if (isset($mapping['references_docs'][$key]['value'])) $info[$key] = strip_tags($mapping['references_docs'][$key]['value'],'<table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3><blockquote>');
                }

                //преобразование даты в Mysql-формат

                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['references_docs'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['references_docs'], $info, 'id');

                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        
                        //Матвей:формирование ЧПУ-строки
                        $db->query( "UPDATE ".$sys_tables['references_docs']." SET `chpu_title` = ? WHERE `id` = ?", $new_id.'_'.createCHPUTitle($info['title']), $new_id);
                        //Матвей:end
                        
                        // редирект на редактирование свеженькой страницы
                        header('Location: '.Host::getWebPath('/admin/content/information/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['references_docs']);
        break;
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$del_photos = Photos::DeleteAll('references_docs',$id);
        $res = $db->query("DELETE FROM ".$sys_tables['references_docs']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    default:
        $module_template = 'admin.references_docs.list.html';
        // формирование спискоф для фильтров
        if(!empty($filters['category'])){
            $types = $db->fetchall("SELECT id, title FROM ".$sys_tables['references_docs_types']." WHERE id_category = ".$filters['category']." ORDER BY title");
            Response::SetArray('types',$types);
        }
        $categories = $db->fetchall("SELECT id, title FROM ".$sys_tables['references_docs_categories']." ORDER BY title");
        Response::SetArray('categories',$categories);
        // формирование списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['title'])) $conditions[] = $sys_tables['references_docs'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['status'])) $conditions[] = "".$sys_tables['references_docs'].".`status` = ".$db->real_escape_string($filters['status']);
            if(!empty($filters['type'])) $conditions[] = "".$sys_tables['references_docs'].".`id_type` = ".$db->real_escape_string($filters['type']);
            if(!empty($filters['category'])) $conditions[] = "".$sys_tables['references_docs_types'].".`id_category` = ".$db->real_escape_string($filters['category']);
        }
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '1';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['references_docs'], 30, false, "SELECT count(*) as items_count FROM ".$sys_tables['references_docs']." LEFT JOIN ".$sys_tables['references_docs_types']." ON ".$sys_tables['references_docs_types'].".id = ".$sys_tables['references_docs'].".id_type WHERE  ".$condition." GROUP BY ".$sys_tables['references_docs'].".id");
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/content/information'                           // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        $sql = "SELECT 
                     ".$sys_tables['references_docs'].".*,
                     ".$sys_tables['references_docs_photos'].".`name` as `photo`, 
                     LEFT (".$sys_tables['references_docs_photos'].".`name`,2) as `subfolder`,
                     ".$sys_tables['references_docs_types'].".chpu_title as type_chpu_title,
                     ".$sys_tables['references_docs_categories'].".chpu_title as category_chpu_title,
                     ".$sys_tables['references_docs_types'].".title as type_title,
                     ".$sys_tables['references_docs_categories'].".title as category_title,

                        CONCAT_WS('/',".$sys_tables['references_docs_types'].".chpu_title, ".$sys_tables['references_docs'].".chpu_title)
                        as chpu_title
                FROM ".$sys_tables['references_docs']."
                LEFT JOIN ".$sys_tables['references_docs_photos']." ON ".$sys_tables['references_docs_photos'].".id = ".$sys_tables['references_docs'].".id_main_photo 
                LEFT JOIN ".$sys_tables['references_docs_types']." ON ".$sys_tables['references_docs_types'].".id = ".$sys_tables['references_docs'].".id_type
                LEFT JOIN ".$sys_tables['references_docs_categories']." ON ".$sys_tables['references_docs_categories'].".id = ".$sys_tables['references_docs_types'].".id_category
                WHERE  ".$condition."
                GROUP BY ".$sys_tables['references_docs'].".id
                ORDER BY ".$sys_tables['references_docs'].".id DESC
                LIMIT ".$paginator->getLimitString($page); 
        $list = $db->fetchall($sql);
		// определение главной фотки для Статьи
		$references_docs_photo_folder = Config::$values['img_folders']['references_docs'];
		foreach($list as $key=>$value){
			$photo = Photos::getMainPhoto('references_docs',$value['id']);
			if(!empty($photo)) {
				$list[$key]['photo'] = $references_docs_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
			}
		}
        // формирование списка
        Response::SetArray('list', $list);
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
}
// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk.'='.$gv;
Response::SetString('get_string', implode('&',$get_parameters));
?>