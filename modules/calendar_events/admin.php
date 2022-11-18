<?php

$GLOBALS['js_set'][] = '/modules/calendar_events/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

$this_page->manageMetadata(array('title'=>'Календарь событий'));

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
$filters['date_begin'] = Request::GetString('f_date_begin',METHOD_GET);
$filters['date_end'] = Request::GetString('f_date_end',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['date_begin'])) {
    $filters['date_begin'] = urldecode($filters['date_begin']);
    $get_parameters['f_date_begin'] = $filters['date_begin'];
}
if(!empty($filters['date_end'])) {
    $filters['date_end'] = urldecode($filters['date_end']);
    $get_parameters['f_date_end'] = $filters['date_end'];
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
    /*********************************\
    |* Очистка кеша мнений           *|
    \*********************************/
    case 'delete_memcache':
        for($year = 2009; $year<=2015; $year++){
            for($month = 1; $month<=12; $month++){
               if($memcache->get('ajax::bsn::calendar/block/'.$year.'/'.$month)) $memcache->delete('ajax::bsn::calendar/block/'.$year.'/'.$month);    
            }
        }
        $ajax_result['ok']=true;
    case 'flush_memcache':
        if($ajax_mode){
            $memcache->flush();
            $ajax_result['ok']=true;
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
                        $list = Photos::getList('calendar_events',$id);
                        if(!empty($list)){
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                            $ajax_result['folder'] = Config::$values['img_folders']['calendar_events'];
                        } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    //загрузка фотографий
                    //id текущей новости
                    $id = Request::GetInteger('id', METHOD_POST);                
                    if(!empty($id)){
                        //removed default min sizes
                        $res = Photos::Add('calendar_events',$id,false,false,false,Config::Get('images/min_width'),Config::Get('images/min_height'), true,false,false,false,false,false,false,true);
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
                        $res = Photos::setTitle('calendar_events',$id, $title);
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
                        $res = Photos::Delete('calendar_events',$id_photo);
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
                        $res = Photos::setMain('calendar_events', $id, $id_photo);
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
                        $res = Photos::Sort('calendar_events', $order);
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
        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
		$module_template = 'admin.calendar_events.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['calendar_events']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['calendar_events']." 
								WHERE id=?", $id) ;
		}
        
        if(empty($info['registration_invited_text'])) $info['registration_invited_text'] = 'Ждем Вас '.date('d',strtotime($info['date_begin']))." ".Config::$values['months_genitive'][date('n',strtotime($info['date_begin']))]." по адресу: ".$info['place'];
        if(empty($info['retigstration_title'])) $info['registration_title'] = $info['title'];
        
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['calendar_events'][$key])) $mapping['calendar_events'][$key]['value'] = $info[$key];
		}
        
        $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['calendar_events_categories']);
        foreach($categories as $key=>$val){
            $mapping['calendar_events']['id_category']['values'][$val['id']] = $val['title'];
        }
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);

		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
                if(!empty($mapping['calendar_events'][$key])) $mapping['calendar_events'][$key]['value'] = $post_parameters[$key];
			}
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['calendar_events']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['calendar_events'][$key])) $mapping['calendar_events'][$key]['error'] = $value;
			}
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['calendar_events'][$key]['value'])) $info[$key] = $mapping['calendar_events'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
					$res = $db->updateFromArray($sys_tables['calendar_events'], $info, 'id') or die($db->error);
				} else {
					$res = $db->insertFromArray($sys_tables['calendar_events'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
                        
                        //Матвей:формирование ЧПУ-строки
                        $db->querys( "UPDATE ".$sys_tables['calendar_events']." SET `chpu_title` = ? WHERE `id` = ?", $new_id.'_'.createCHPUTitle($info['title']), $new_id);
                        //Матвей:end                        
                        
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/content/calendar_events/edit/'.$new_id.'/'));
							exit(0);
						}
					}
				}
				Response::SetBoolean('saved', $res); // результат сохранения
			} else Response::SetBoolean('errors', true); // признак наличия ошибок
		}
        // возможность комментирования
        if($mapping['calendar_events']['paid']['value']==1){
            $mapping['calendar_events']['show_comments']['hidden'] = false;
        } else {
            $mapping['calendar_events']['show_comments']['hidden'] = true;
        }        

		// если мы попали на страницу редактирования путем редиректа с добавления, 
		// значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
		$referer = Host::getRefererURL();
		if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
			Response::SetBoolean('form_submit', true);
			Response::SetBoolean('saved', true);
		}
		// запись данных для отображения на странице
		Response::SetArray('data_mapping',$mapping['calendar_events']);
		break;
	case 'del':
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($id>0){
            $del_photos = Photos::DeleteAll('calendar_events',$id);
		    $res = $db->querys("DELETE FROM ".$sys_tables['calendar_events']." WHERE id=?", $id);
		    $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
		    if($ajax_mode){
			    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
			    break;
		    }
        }
	default:
		$module_template = 'admin.calendar_events.list.html';
        // формирование спискоф для фильтров
        $categories = $db->fetchall("SELECT id, title FROM ".$sys_tables['calendar_events_categories']);
        Response::SetArray('categories',$categories);
		// формирование списка
		$conditions = [];
		if(!empty($filters)){
			if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
			if(!empty($filters['date_begin'])) $conditions['date_begin'] = "`date_begin` >= '".$db->real_escape_string($filters['date_begin'])."'";
			if(!empty($filters['date_end'])) $conditions['date_end'] = "`date_end` <= '".$db->real_escape_string($filters['date_end'])."'";
            if(!empty($filters['category'])) $conditions[] = "`id_category` = ".$db->real_escape_string($filters['category']);
        }
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['calendar_events'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = [];
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/content/calendar_events'                  // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

		$sql = "SELECT id,title,date_begin,date_end FROM ".$sys_tables['calendar_events'];
		if(!empty($condition)) $sql .= " WHERE ".$condition;
		$sql .= " ORDER BY date_begin DESC";
		$sql .= " LIMIT ".$paginator->getLimitString($page); 
		$list = $db->fetchall($sql);
        $calendar_photo_folder=Config::$values['img_folders']['calendar_events'];
        foreach($list as $key=>$value){
            $photo = Photos::getMainPhoto('calendar_events',$value['id']);
            if(!empty($photo)) {
                $list[$key]['photo'] = $calendar_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
            }
        }
		// формирование списка
		Response::SetArray('list', $list);
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