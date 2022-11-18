<?php
$GLOBALS['js_set'][] = '/modules/partners_landings/ajax_actions.js';
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Лендинг'));

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
// обработка action-ов
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
					//id текущей лендинга
					$id = Request::GetInteger('id', METHOD_POST);
                    if(!empty($id)){
						$list = Photos::getList('partners_landings',$id);
						if(!empty($list)){
							$ajax_result['ok'] = true;
							$ajax_result['list'] = $list;
							$ajax_result['folder'] = Config::$values['img_folders']['partners_landings'];
						} else $ajax_result['error'] = 'Невозможно построить список фотографий';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
					//загрузка фотографий
					//id текущей лендинга
					$id = Request::GetInteger('id', METHOD_POST);				
					if(!empty($id)){                  
                        Photos::$__folder_options = array(
                            'sm'  => array( 90, 90, 'cut', 65 ),
                            'med' => array( 280, 190, 'cut', 75 ),
                            'big' => array( 2000, 1600, '', 70 )
                            );                 // свойства папок для загрузки и формата фотографий

						$res = Photos::Add( 'partners_landings', $id );
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
                        $res = Photos::setTitle('partners_landings',$id, $title);
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
						$res = Photos::Delete('partners_landings',$id_photo);
						if(!empty($res)){
							$ajax_result['ok'] = true;
						} else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'setMain':
					// установка флага "главное фото" для объекта
					//id текущей лендинга
					$id = Request::GetInteger('id', METHOD_POST);
					//id фотки
					$id_photo = Request::GetInteger('id_photo', METHOD_POST);				
					if(!empty($id_photo)){
						$res = Photos::setMain('partners_landings', $id, $id_photo);
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
						$res = Photos::Sort('partners_landings', $order);
						if(!empty($res)){
							$ajax_result['ok'] = true;
						} else $ajax_result['error'] = 'Невозможно отсортировать';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;		
    case 'add':
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
	    $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
	    $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';

        $module_template = 'admin.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['partners_landings']);
            $info['content'] = $info['content_short'] = "";
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *
                                FROM ".$sys_tables['partners_landings']." 
                                WHERE id=?", $id);
            $info['pretty_url'] = $db->fetch(" SELECT pretty_url FROM " . $sys_tables['pages_seo'] . " WHERE url = ?", 'partners_landings/' . $id)['pretty_url'];
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['partners_landings'][$key])) $mapping['partners_landings'][$key]['value'] = $info[$key];
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['partners_landings'][$key])) $mapping['partners_landings'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['partners_landings']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['partners_landings'][$key])) $mapping['partners_landings'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['partners_landings'][$key]['value'])) $info[$key] = strip_tags($mapping['partners_landings'][$key]['value'],'<table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                }
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['partners_landings'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['partners_landings'], $info, 'id');
                }
                
                //добавление записи в сео
                $item = $db->fetch(" SELECT * FROM " . $sys_tables['pages_seo'] . " WHERE url = ?", 'partners_landings/' . $id);
                if( empty( $item ) ) 
                    $db->querys( 
                        " INSERT IGNORE INTO " . $sys_tables['pages_seo'] . " 
                                  SET 
                                    url = ?, 
                                    pretty_url = ? 
                        ", 'partners_landings/' . $id, $info['pretty_url']
                    );
                else 
                    $db->querys( 
                        " UPDATE " . $sys_tables['pages_seo'] . " 
                          SET 
                            pretty_url = ?
                          WHERE 
                            url = ?  
                                   
                        ", $info['pretty_url'], 'partners_landings/' . $id
                    );
                    
                    
                if(!empty($res) && $action == 'add'){
                    $new_id = $db->insert_id;
                    // редирект на редактирование свеженькой страницы
                    if(!empty($res)) {
                        header('Location: '.Host::getWebPath('/admin/service/partners_landings/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['partners_landings']);
        break;
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$del_photos = Photos::DeleteAll('partners_landings',$id);
        $res = $db->querys("DELETE FROM ".$sys_tables['partners_landings']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    default:
        $module_template = 'admin.list.html';
        // формирование списка
        $conditions = array();
        if(!empty($filters)){
            if(!empty($filters['title'])) $conditions[] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
        }
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['partners_landings'], 30, $condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = array();
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/service/partners_landings'                           // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        $sql = "SELECT id,title FROM ".$sys_tables['partners_landings'];
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY `id` DESC";
        $sql .= " LIMIT ".$paginator->getLimitString($page); 
        $list = $db->fetchall($sql);
		// определение главной фотки для лендинга
		$partners_landings_photo_folder=Config::$values['img_folders']['partners_landings'];
		foreach($list as $key=>$value){
			$photo = Photos::getMainPhoto('partners_landings',$value['id']);
			if(!empty($photo)) {
				$list[$key]['photo'] = $partners_landings_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
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
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>