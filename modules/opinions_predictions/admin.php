<?php

/*
//запрос для импорта статей из старой базы в новую
INSERT INTO `content`.`opinions` ( `id`, `type`, `id_expert`, `text`, `id_estate_type`, `annotation`, `date`, `in_spam`) 
SELECT `id`, `type`, `id_expert_name`, `text`, `id_estate_type`, `annotation`, `date`, `in_spam`
FROM bsnweb.opinion;

INSERT INTO `content`.`opinions_estate_types` ( `id`, `title`) 
SELECT `id`, `name`
FROM bsnweb.opinion_type_estate;

INSERT INTO `content`.`opinions_expert_agencies` ( `id`, `title`) 
SELECT `id`, `name`
FROM bsnweb.opinion_experts_agencies;

INSERT INTO `content`.`opinions_expert_profiles` ( `id`, `title`, `company`, `id_agency`, `bio`, `email`) 
SELECT `id`, `name`, `company`, `id_agency`, `bio`, `email`
FROM bsnweb.opinion_experts_profiles;

INSERT INTO `content`.`opinions_expert_profiles_photos` ( `id_parent`, `name`) 
SELECT `id`, `img`
FROM bsnweb.opinion_experts_profiles;


*/

$GLOBALS['js_set'][] = '/modules/opinions_predictions/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Мнения и прогнозы экспертов'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['opinion_type'] = Request::GetInteger('f_opinion_type',METHOD_GET);
$filters['estate_type'] = Request::GetInteger('f_estate_type',METHOD_GET);
$filters['expert'] = Request::GetInteger('f_expert',METHOD_GET);
$filters['agency'] = Request::GetInteger('f_agency',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['opinion_type'])) $get_parameters['f_opinion_type'] = $filters['opinion_type'];
else $filters['opinion_type'] = 1;
if(!empty($filters['estate_type'])) $get_parameters['f_estate_type'] = $filters['estate_type']; 
if(!empty($filters['expert'])) $get_parameters['f_expert'] = $filters['expert']; 
if(!empty($filters['agency'])) $get_parameters['f_agency'] = $filters['agency']; 
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
        if($ajax_mode){
            $types = array(
                'ajax::bsn::opinions/block/live',
                'ajax::bsn::opinions/block/commercial',
                'ajax::bsn::opinions/block/country',
                'ajax::bsn::opinions/block/build',
                'ajax::bsn::opinions/block/',
                'block::bsn::opinions/block/',
                'block::bsn::opinions/block/analytics',
            );
            foreach($types as $type) if($memcache->get($type)) $memcache->delete($type);
            $ajax_result['ok']=true;
        }
        break;
    case 'flush_memcache':
        if($ajax_mode){
            $memcache->flush();
            $ajax_result['ok']=true;
        }
        break;
    /*********************************\
    |*  Работа с Каталогом агенств    *|
    \*********************************/
    case 'agencies':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        Photos::$__folder_options = array('sm'=>array(120,100,'',65));
        switch($action){
        /**************************\
        |*  Работа с фотографиями  *|
        \**************************/
        case 'photos':
            if($ajax_mode){
                $ajax_result['error'] = '';
                // переопределяем экшн
                $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                switch($action){
                    case 'list':
                        //получение списка фотографий
                        //id текущей новости
                        $id = Request::GetInteger('id', METHOD_POST);
                        if(!empty($id)){
                            $list = Photos::getList('opinions_expert_agencies',$id);
                            if(!empty($list)){
                                $ajax_result['ok'] = true;
                                $ajax_result['list'] = $list;
                                $ajax_result['folder'] = Config::$values['img_folders']['opinions_expert_profiles'];
                            } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                    case 'add':
                        //загрузка фотографий
                        //id текущей новости
                        $id = Request::GetInteger('id', METHOD_POST);                
                        if(!empty($id)){
                            //default sizes 120x100 removed
                            $res = Photos::Add('opinions_expert_agencies',$id,false,false,false,false,false,true);
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
                        $res = Photos::setTitle('opinions_expert_agencies',$id, $title);
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
                            $res = Photos::Delete('opinions_expert_agencies',$id_photo);
                            
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
                $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
				$module_template = 'admin.agencies.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['opinions_expert_agencies']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['opinions_expert_agencies']." 
										WHERE id=?", $id) ;
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['agencies'][$key])) $mapping['agencies'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
		
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['agencies'][$key])) $mapping['agencies'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['agencies']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['agencies'][$key])) $mapping['agencies'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['agencies'][$key]['value'])) $info[$key] = $mapping['agencies'][$key]['value'];
						}
						// сохранение в БД
						if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['opinions_expert_agencies'], $info, 'id') or die($db->error);
						} else {
							$res = $db->insertFromArray($sys_tables['opinions_expert_agencies'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
                                                       
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/content/opinions_predictions/agencies/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['agencies']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$res = $db->querys("DELETE FROM ".$sys_tables['opinions_expert_agencies']." WHERE id=?", $id);
                //удаление фото агентства
                $del_photos = Photos::DeleteAll('opinions_expert_agencies',$id);    
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			default:
				$module_template = 'admin.agencies.list.html';
				// формирование фильтра по названию
				$conditions = [];
				if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['opinions_expert_agencies'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/content/opinions_predictions/agencies'                  // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
        
				$sql = "SELECT ".$sys_tables['opinions_expert_agencies'].".*,
                            CONCAT_WS('/','".Config::$values['img_folders']['opinions_expert_profiles']."','sm',LEFT(photos.name,2)) as agency_photo_folder,
                            photos.name as agency_photo
                        FROM ".$sys_tables['opinions_expert_agencies']."
                        LEFT JOIN  ".$sys_tables['opinions_expert_agencies_photos']." photos ON photos.id_parent=".$sys_tables['opinions_expert_agencies'].".id";
				if(!empty($condition)) $sql .= " WHERE ".$condition;
				$sql .= " ORDER BY title";
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
    /*********************************\
    |*  Работа с типами недвижимости *|
    \*********************************/
    case 'estate_types':
        // переопределяем экшн
       $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
			case 'add':
			case 'edit':
				$module_template = 'admin.estate_types.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['opinions_expert_estate_types']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['opinions_expert_estate_types']." 
										WHERE id=?", $id) ;
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['estate_types'][$key])) $mapping['estate_types'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
		
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['estate_types'][$key])) $mapping['estate_types'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['estate_types']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['estate_types'][$key])) $mapping['estate_types'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['estate_types'][$key]['value'])) $info[$key] = $mapping['estate_types'][$key]['value'];
						}
						// сохранение в БД
						if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['opinions_expert_estate_types'], $info, 'id') or die($db->error);
						} else {
							$res = $db->insertFromArray($sys_tables['opinions_expert_estate_types'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/content/opinions_predictions/estate_types/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['estate_types']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$res = $db->querys("DELETE FROM ".$sys_tables['opinions_expert_estate_types']." WHERE id=?", $id);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			default:
				$module_template = 'admin.estate_types.list.html';
				// формирование фильтра по названию
				$conditions = [];
				if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['opinions_expert_estate_types'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/content/opinions_predictions/estate_types'                  // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
		
				$sql = "SELECT id,title FROM ".$sys_tables['opinions_expert_estate_types'];
				if(!empty($condition)) $sql .= " WHERE ".$condition;
				$sql .= " ORDER BY title";
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
    /***********************************\
    |*  Работа с профилями экспертов   *|
    \***********************************/		
     case 'experts':
	    // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		// дефолтное значение папки выгрузки и свойств фото
		Photos::$__folder_options = array('sm'=>array(70,90,'cut',85), 'med'=>array(210,270,'cut',80), 'big'=>array(800,600,'',75));
        switch($action){
		/**************************\
		|*  Работа с фотографиями  *|
		\**************************/
		case 'photos':
			if($ajax_mode){
				$ajax_result['error'] = '';
				// переопределяем экшн
				$action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
				switch($action){
					case 'list':
						//получение списка фотографий
						//id текущей новости
						$id = Request::GetInteger('id', METHOD_POST);
						if(!empty($id)){
							$list = Photos::getList('opinions_expert_profiles',$id);
							if(!empty($list)){
								$ajax_result['ok'] = true;
								$ajax_result['list'] = $list;
								$ajax_result['folder'] = Config::$values['img_folders']['opinions_expert_profiles'];
							} else $ajax_result['error'] = 'Невозможно построить список фотографий';
						} else $ajax_result['error'] = 'Неверные входные параметры';
						break;
					case 'add':
						//загрузка фотографий
						//id текущей новости
						$id = Request::GetInteger('id', METHOD_POST);				
						if(!empty($id)){
                            //default sizes 70x90 removed
							$res = Photos::Add('opinions_expert_profiles',$id,false,false,false,false,false,true);
							if(!empty($res)){
								$ajax_result['ok'] = true;
								$ajax_result['list'] = $res;
							} else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
						} else $ajax_result['error'] = 'Неверные входные параметры';
						break;
                        case 'setTitle':
                            //добавление названия
                            $id = Request::GetInteger('id_photo', METHOD_POST);                
                            $title = Request::GetString('title', METHOD_POST);                
                            if(!empty($id)){
                                $res = Photos::setTitle('opinions_expert_profiles',$id, $title);
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
							$res = Photos::Delete('opinions_expert_profiles',$id_photo);
							
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
				// переопределяем экшн
				$action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];    
				$GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
				$GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';                                                
				$module_template = 'admin.experts.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['opinions_expert_profiles']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['opinions_expert_profiles']." 
										WHERE id=?", $id) ;
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['experts'][$key])) $mapping['experts'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
				// формирование дополнительных данных для формы (не из основной таблицы)
				$agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['opinions_expert_agencies']." ORDER BY title, id");
				foreach($agencies as $key=>$val){
					$mapping['experts']['id_agency']['values'][$val['id']] = $val['title'];
				}
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['experts'][$key])) $mapping['experts'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['experts']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['experts'][$key])) $mapping['experts'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['experts'][$key]['value'])) $info[$key] = $mapping['experts'][$key]['value'];
						}
						// сохранение в БД
						if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['opinions_expert_profiles'], $info, 'id') or die($db->error);
						} else {
							$res = $db->insertFromArray($sys_tables['opinions_expert_profiles'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/content/opinions_predictions/experts/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['experts']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$res = $db->querys("DELETE FROM ".$sys_tables['opinions_expert_profiles']." WHERE id=?", $id);
				//удаление фото эксперта
				$del_photos = Photos::DeleteAll('opinions_expert_profiles',$id);				
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			default:
				$module_template = 'admin.experts.list.html';
				// формирование списка
				$agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['opinions_expert_agencies']." ORDER BY title");
				Response::SetArray('agencies',$agencies);
				$conditions = [];
				if(!empty($filters)){
					if(!empty($filters['title'])) $conditions['title'] = $sys_tables['opinions_expert_profiles'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
					if(!empty($filters['agency'])) $conditions['agency'] = $sys_tables['opinions_expert_profiles'].".`id_agency` = ".$db->real_escape_string($filters['agency'])."";
				}
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['opinions_expert_profiles'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/content/opinions_predictions/experts/'                // модуль
										  ."?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
				
				$sql = "SELECT 
							".$sys_tables['opinions_expert_profiles'].".*, 
							agencies.title as agency_title, 
							CONCAT_WS('/','".Config::$values['img_folders']['opinions_expert_profiles']."','med',LEFT(photos.name,2)) as expert_photo_folder,
                            photos.name as expert_photo 
						FROM ".$sys_tables['opinions_expert_profiles'];
				$sql .= " LEFT JOIN  ".$sys_tables['opinions_expert_agencies']." agencies ON agencies.id=".$sys_tables['opinions_expert_profiles'].".id_agency";
				$sql .= " LEFT JOIN  ".$sys_tables['opinions_expert_profiles_photos']." photos ON photos.id_parent=".$sys_tables['opinions_expert_profiles'].".id";
				if(!empty($condition)) $sql .= " WHERE ".$condition;
				$sql .= " GROUP BY ".$sys_tables['opinions_expert_profiles'].".id
                          ORDER BY ".$sys_tables['opinions_expert_profiles'].".id DESC";
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
    /************************************\
    |*  Работа с мнениями / прогнозами  *|
    \************************************/		
	case 'add':
	case 'edit':
		$module_template = 'admin.opinions_predictions.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['opinions_predictions']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['opinions_predictions']." 
								WHERE id=?", $id) ;
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['opinions_predictions'][$key])) $mapping['opinions_predictions'][$key]['value'] = $info[$key];
		}
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
        // формирование дополнительных данных для формы (не из основной таблицы)
        $estate_types = $db->fetchall("SELECT id,title FROM ".$sys_tables['opinions_expert_estate_types']." ORDER BY id");
        foreach($estate_types as $key=>$val){
            $mapping['opinions_predictions']['id_estate_type']['values'][$val['id']] = $val['title'];
        }
        $experts = $db->fetchall("SELECT id,title FROM ".$sys_tables['opinions_expert_profiles']." ORDER BY title, id");
        foreach($experts as $key=>$val){
            $mapping['opinions_predictions']['id_expert']['values'][$val['id']] = $val['title'];
        }
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['opinions_predictions'][$key])) $mapping['opinions_predictions'][$key]['value'] = $post_parameters[$key];
			}
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['opinions_predictions']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['opinions_predictions'][$key])) $mapping['opinions_predictions'][$key]['error'] = $value;
			}
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['opinions_predictions'][$key]['value'])) $info[$key] = $mapping['opinions_predictions'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
					$res = $db->updateFromArray($sys_tables['opinions_predictions'], $info, 'id') or die($db->error);
				} else {
					$res = $db->insertFromArray($sys_tables['opinions_predictions'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
                        
                        //Матвей:формирование ЧПУ-строки
                        $db->querys( "UPDATE ".$sys_tables['opinions_predictions']." SET `chpu_title` = ? WHERE `id` = ?", $new_id.'_'.createCHPUTitle($info['annotation']), $new_id);
                        //Матвей:end
                        
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/content/opinions_predictions/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['opinions_predictions']);
		break;
	case 'del':
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$res = $db->querys("DELETE FROM ".$sys_tables['opinions_predictions']." WHERE id=?", $id);
		$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
		if($ajax_mode){
			$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
			break;
		}
	default:
		$module_template = 'admin.opinions_predictions.list.html';
		// формирование списка
		Response::SetArray('opinion_types',$mapping['opinions_predictions']['type']['values']);
        $estate_types = $db->fetchall("SELECT id,title FROM ".$sys_tables['opinions_expert_estate_types']." ORDER BY id");
        Response::SetArray('estate_types',$estate_types);
        $experts = $db->fetchall("SELECT id,title FROM ".$sys_tables['opinions_expert_profiles']." ORDER BY title, id");
        Response::SetArray('experts',$experts);
		$conditions = [];
		if(!empty($filters)){
			if(!empty($filters['title'])) $conditions['title'] = "`annotation` LIKE '%".$db->real_escape_string($filters['title'])."%'";
			if(!empty($filters['opinion_type'])) $conditions['opinion_type'] = "`type` = ".$db->real_escape_string($filters['opinion_type'])."";
			if(!empty($filters['estate_type'])) $conditions['estate_type'] = "`id_estate_type` = ".$db->real_escape_string($filters['estate_type'])."";
			if(!empty($filters['expert'])) $conditions['expert'] = "`id_expert` = ".$db->real_escape_string($filters['expert'])."";
		}
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['opinions_predictions'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = [];
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/content/opinions_predictions'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

		$sql = "
            SELECT 
                opinions.*, 
                experts.title as expert_name,
               IF(".$sys_tables['opinions_predictions'].".type=1, 'opinions',
                    IF(".$sys_tables['opinions_predictions'].".type=2,'predictions','interview')
               ) AS type_url,
               ".$sys_tables['opinions_expert_estate_types'].".url as estate_url
                 
            FROM ".$sys_tables['opinions_predictions']." opinions
		    LEFT JOIN ".$sys_tables['opinions_expert_estate_types']." ON ".$sys_tables['opinions_expert_estate_types'].".id = ".$sys_tables['opinions_predictions'].".id_estate_type 
            LEFT JOIN  ".$sys_tables['opinions_expert_profiles']." experts ON experts.id = opinions.id_expert
            
        ";
		if(!empty($condition)) $sql .= " WHERE ".$condition;
		$sql .= " ORDER BY opinions.id DESC";
		$sql .= " LIMIT ".$paginator->getLimitString($page); 
		$list = $db->fetchall($sql);
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