<?php

/*
//запрос для импорта фотоблоков
INSERT INTO `advert_objects`.`photoblocks` ( `id`, `title`, `company`, `phone`, `email`, `published`, `datetime`, `txt_addr`, `txt_cost`, `url`, `object_info`, `contact_name`, `search_count`, `views_count`, `r_live_rent`, `r_build`, `r_commercial_rent`, `r_country_rent`) 
SELECT `id`, `name`, `company`, `phone`, `email`, `active`, `datetime`, `txt_addr`, `txt_cost`, `url`, `obj_info`, `contact_name`, `pokazov`, `prosmotrov`, `r_live`, `r_build`, `r_com`, `r_country`
FROM flatdata.photoblocks;
*/

$GLOBALS['js_set'][] = '/modules/photoblocks/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Фотоблоки'));

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; else $filters['status'] = 1;
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];







// обработка action-ов
switch($action){
    /***************************\
    |*  Работа с фотографиями  *|
    \***************************/
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
						$list = Photos::getList('photoblocks',$id);
						if(!empty($list)){
							$ajax_result['ok'] = true;
							$ajax_result['list'] = $list;
							$ajax_result['folder'] = Config::$values['img_folders']['photoblocks'];
						} else $ajax_result['error'] = 'Невозможно построить список фотографий';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
					//загрузка фотографий
					//id текущей новости
					$id = Request::GetInteger('id', METHOD_POST);				
					if(!empty($id)){
						$res = Photos::Add('photoblocks',$id);
						if(!empty($res)){
                            if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                            else {
                                $ajax_result['ok'] = true;
                                $ajax_result['list'] = $res;
                            }
						} else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;

                case 'del':
					//удаление фото
					//id фотки
					$id_photo = Request::GetInteger('id_photo', METHOD_POST);				
					if(!empty($id_photo)){
						$res = Photos::Delete('photoblocks',$id_photo);
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
						$res = Photos::setMain('photoblocks', $id, $id_photo);
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
						$res = Photos::Sort('photoblocks', $order);
						if(!empty($res)){
							$ajax_result['ok'] = true;
						} else $ajax_result['error'] = 'Невозможно отсортировать';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;
    /************************************\
    |*  Работа с коттеджными поселками  *|
    \************************************/		
	case 'add':
	case 'edit':
	    $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
	    $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';

		$module_template = 'admin.photoblocks.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['photoblocks']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['photoblocks']." 
								WHERE id=?", $id) ;
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['photoblocks'][$key])) $mapping['photoblocks'][$key]['value'] = $info[$key];
		}
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['photoblocks'][$key])) $mapping['photoblocks'][$key]['value'] = $post_parameters[$key];
			}
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['photoblocks']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['photoblocks'][$key])) $mapping['photoblocks'][$key]['error'] = $value;
			}
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['photoblocks'][$key]['value'])) $info[$key] = $mapping['photoblocks'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
					
					$res = $db->updateFromArray($sys_tables['photoblocks'], $info, 'id') or die($db->error);
				} else {
					//дата дообавления объекта
					$info['datetime'] = 'NOW()';
					$res = $db->insertFromArray($sys_tables['photoblocks'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/advert_objects/photoblocks/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['photoblocks']);
		break;
	case 'del':
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$del_photos = Photos::DeleteAll('photoblocks',$id);
		$res = $db->query("DELETE FROM ".$sys_tables['photoblocks']." WHERE id=?", $id);
		$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
		if($ajax_mode){
			$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
			break;
		}
	default:
		$module_template = 'admin.photoblocks.list.html';
		// формирование списка
        Response::SetArray('statuses',array('1'=>'Активные','3'=>'Все','2'=>'Не активные'));
		$conditions = array();
		if(!empty($filters)){
			if(!empty($filters['title'])) $conditions['title'] = $sys_tables['photoblocks'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
			if(!empty($filters['status'])) {
				switch($filters['status']){
					case '1': 
					case '2': 
						$conditions['status'] = "`published` = ".$db->real_escape_string($filters['status'])."";
				}
			}
		}
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['photoblocks'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = array();
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/advert_objects/photoblocks'		  // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

        $sql = "SELECT * FROM ".$sys_tables['photoblocks'];
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY `datetime` DESC";
        $sql .= " LIMIT ".$paginator->getLimitString($page); 
		$list = $db->fetchall($sql);
		// определение главной фотки для поселка
		$photoblock_photo_folder=Config::$values['img_folders']['photoblocks'];
		foreach($list as $key=>$value){
			$photo = Photos::getMainPhoto('photoblocks',$value['id']);
			if(!empty($photo)) {
				$list[$key]['photo'] = $photoblock_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
			}
		}
		// формирование списка
		Response::SetArray('list', $list);
		//print_r($list);
		if($paginator->pages_count>1){
			Response::SetArray('paginator', $paginator->Get($page));
		}
		break;
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>