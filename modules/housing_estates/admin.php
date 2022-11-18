<?php
$GLOBALS['js_set'][] = '/modules/housing_estates/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Жилые комплексы'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['rating'] = Request::GetInteger('f_rating',METHOD_GET);
$filters['coords'] = Request::GetInteger('f_coords',METHOD_GET);
$filters['stady'] = Request::GetInteger('f_stady',METHOD_GET);
$filters['year'] = Request::GetInteger('f_year',METHOD_GET);
$filters['referer'] = Request::GetString('referer',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['coords'])) $get_parameters['f_coords'] = $filters['coords']; else $filters['coords'] = 0;
if(!empty($filters['rating'])) $get_parameters['f_rating'] = $filters['rating']; else $filters['rating'] = 0;
if(!empty($filters['referer'])) $get_parameters['f_referer'] = $filters['referer']; else $filters['rating'] = false;
if(!empty($filters['stady'])) {
    $get_parameters['f_stady'] = $filters['stady'];
    Response::SetInteger('stady_id',$filters['stady']);
}   else $filters['stady'] = 0;
if(!empty($filters['year'])) $get_parameters['f_year'] = $filters['year']; 
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else {
    $get_parameters['page'] = $page;
}
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
    /*************************************************\
    |*  Работа с Рейтинг ЖК
    \*************************************************/
    case 'ratings':
        // переопределяем экшн
       $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
       switch($action){
			case 'add':
			case 'edit':
				$module_template = 'admin.ratings.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['housing_estates_districts']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['housing_estates_districts']." 
										WHERE id=?", $id) ;
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['ratings'][$key])) $mapping['ratings'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
		
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['ratings'][$key])) $mapping['ratings'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['ratings']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['ratings'][$key])) $mapping['ratings'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(isset($mapping['ratings']['description']['value'])) $mapping['ratings']['description']['value'] = strip_tags($mapping['ratings']['description']['value'],'<a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3><blockquote>');
                    if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['ratings'][$key]['value'])) $info[$key] = $mapping['ratings'][$key]['value'];
						}
						// сохранение в БД
                        
						if(empty($info['chpu_title'])) $info['chpu_title'] = Convert::chpuTitle($info['title']);
                        if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['housing_estates_districts'], $info, 'id') or die($db->error);
						} else {
							$res = $db->insertFromArray($sys_tables['housing_estates_districts'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/estate/housing_estates/ratings/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['ratings']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$res = $db->querys("DELETE FROM ".$sys_tables['housing_estates_districts']." WHERE id=?", $id);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			default:
				$module_template = 'admin.ratings.list.html';
				// формирование фильтра по названию
				$conditions = [];
				if(!empty($filters['title'])) $conditions['title'] = $sys_tables['housing_estates_districts'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['housing_estates_districts'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/estate/housing_estates/ratings'                  // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
		
				$sql = "SELECT id,title FROM ".$sys_tables['housing_estates_districts'];
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
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>