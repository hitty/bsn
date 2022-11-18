<?php

$GLOBALS['js_set'][] = '/modules/pages/ajax_actions.js';


require_once('includes/class.paginator.php');

// добавление title
$this_page->manageMetadata(array('title'=>'География'));
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['id_user'] = Request::GetString('f_user',METHOD_GET);
$filters['id_agency'] = Request::GetString('f_agency',METHOD_GET);
$filters['status'] = Request::GetString('f_status',METHOD_GET);
$filters['format'] = Request::GetString('f_format',METHOD_GET);
$filters['id_country'] = Request::GetString('id_country',METHOD_GET);
$filters['id_region'] = Request::GetString('id_region',METHOD_GET);
$filters['id_area'] = Request::GetString('id_area',METHOD_GET);
$filters['parent'] = Request::GetString('f_parent',METHOD_GET);
$filters['subway_line'] = Request::GetString('f_subway_line',METHOD_GET);

if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['id_user'])) {
    $get_parameters['f_user'] = $filters['id_user'];
} else $filters['id_user'] = 0;

if(!empty($filters['id_agency'])) {
    $get_parameters['f_agency'] = $filters['id_agency'];
} else $filters['id_agency'] = 0;

if(!empty($filters['status'])) {
    $get_parameters['f_status'] = $filters['status'];
} else $filters['status'] = 0;

if(!empty($filters['format'])) {
    $get_parameters['f_format'] = $filters['format'];
} else $filters['format'] = "";

if(!empty($filters['id_country'])) {
    $get_parameters['id_country'] = $filters['id_country'];
} else $filters['id_country'] = 0;

if(!empty($filters['parent'])) {
    $get_parameters['parent'] = $filters['parent'];
} else $filters['parent'] = 0;

if(!empty($filters['subway_line'])) {
    $get_parameters['subway_line'] = $filters['subway_line'];
} else $filters['subway_line'] = 0;

if(!empty($filters['id_region'])) {
    $get_parameters['id_region'] = $filters['id_region'];
} else $filters['id_region'] = 0;

if(!empty($filters['id_area'])) {
    $get_parameters['id_area'] = $filters['id_area'];
} else $filters['id_area'] = 0;

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];






// обработка action-ов
switch($action){
    /********************************\
    |*  Работа с районами городов   *|
    \********************************/	
	case 'districts':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
		switch($action){
            case 'add':
            case 'edit':		
                $module_template = 'admin.districts.edit.html';
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['districts']);
					// установка action для формы
					Response::SetString('form_parameter', 'add');
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['districts']." 
										WHERE id='".$id."'");
					// установка action для формы
					Response::SetString('form_parameter', 'edit/'.$id);
					if(empty($info)) Host::Redirect('/admin/geodata/districts/add/');	
				}
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['districts'][$key])) $mapping['districts'][$key]['value'] = $info[$key];
                }
				// формирование дополнительных данных для формы (не из основной таблицы)
				$parents = $db->fetchall("SELECT id, offname FROM ".$sys_tables['geodata']." WHERE (`a_level`=1 and (`id_region`=78 or `id_region`=77)) OR (`id_city`=1 and a_level=4 and id_area=0) ORDER BY `id_region`=78 DESC, `id_region`=77 DESC, offname") or die($db->error);
				foreach($parents as $key=>$val){
					$mapping['districts']['parent_id']['values'][$val['id']] = $val['offname'];
				}				
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['districts'][$key])) $mapping['districts'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['districts']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['districts'][$key])) $mapping['districts'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['districts'][$key]['value'])) $info[$key] = $mapping['districts'][$key]['value'];
                        }
                        
                        // сохранение в БД
                        
                        $res = $db->updateFromArray($sys_tables['districts'], $info, 'id');
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['districts']);		
				break;
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $res = $db->querys("DELETE FROM ".$sys_tables['districts']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }				
			default:
				$module_template = 'admin.districts.list.html';
		
				// формирование списка
				$conditions = [];
				if(!empty($filters)){
					if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
					if(!empty($filters['parent'])) $conditions['parent'] = "`parent_id` = '".$db->real_escape_string($filters['parent'])."'";
				}
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);
				$parents = $db->fetchall("SELECT districts.parent_id,geodata.offname FROM ".$sys_tables['districts']." districts 
										  LEFT JOIN ".$sys_tables['geodata']." geodata ON geodata.id=districts.parent_id
										  WHERE districts.parent_id>0
										  GROUP BY districts.parent_id
										  ORDER BY geodata.offname");
				Response::SetArray('parents',$parents);
			
				
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['districts'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/geodata/districts/'			           // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}	
				$sql = "SELECT districts.id,districts.title,geodata.offname FROM ".$sys_tables['districts']." districts
						LEFT JOIN ".$sys_tables['geodata']." geodata ON geodata.id=districts.parent_id";
				if(!empty($condition)) $sql .= " WHERE ".$condition;
				$sql .= " ORDER BY districts.`title`";
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
    /********************\
    |*  Работа с метро	*|
    \********************/	
	case 'subways':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
		switch($action){
			/****************************\
			|*  Работа с линиями метро	*|
			\****************************/	
			case 'lines':
				// переопределяем экшн
				$action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
				switch($action){
					case 'add':
					case 'edit':
						$GLOBALS['css_set'][] = '/admin/js/colorpicker/css/colorpicker.css';		
						$GLOBALS['js_set'][] = '/admin/js/colorpicker/js/colorpicker.js';		
						$module_template = 'admin.subway_lines.edit.html';
						$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
						if($action=='add'){
							// создание болванки новой записи
							$info = $db->prepareNewRecord($sys_tables['subway_lines']);
							// установка action для формы
							Response::SetString('form_parameter', 'add');
						} else {
							// получение данных из БД
							$info = $db->fetch("SELECT *
												FROM ".$sys_tables['subway_lines']." 
												WHERE id='".$id."'");
							// установка action для формы
							Response::SetString('form_parameter', 'edit/'.$id);
							if(empty($info)) Host::Redirect('/admin/geodata/subways/lines/add/');	
						}
						// перенос дефолтных (считанных из базы) значений в мэппинг формы
						foreach($info as $key=>$field){
							if(!empty($mapping['subway_lines'][$key])) $mapping['subway_lines'][$key]['value'] = $info[$key];
						}
						// формирование дополнительных данных для формы (не из основной таблицы)
						$parents = $db->fetchall("SELECT id, offname FROM ".$sys_tables['geodata']." WHERE (`a_level`=1 and (`id_region`=78 or `id_region`=77)) OR (`id_city`=1 and a_level=4 and id_area=0) ORDER BY `id_region`=78 DESC, `id_region`=77 DESC, offname") or die($db->error);
						foreach($parents as $key=>$val){
							$mapping['subway_lines']['parent_id']['values'][$val['id']] = $val['offname'];
						}				
						// получение данных, отправленных из формы
						$post_parameters = Request::GetParameters(METHOD_POST);
						// если была отправка формы - начинаем обработку
						if(!empty($post_parameters['submit'])){
							Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
							// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
							foreach($post_parameters as $key=>$field){
								if(!empty($mapping['subway_lines'][$key])) $mapping['subway_lines'][$key]['value'] = $post_parameters[$key];
							}
							// проверка значений из формы
							$errors = Validate::validateParams($post_parameters,$mapping['subway_lines']);
							// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
							foreach($errors as $key=>$value){
								if(!empty($mapping['subway_lines'][$key])) $mapping['subway_lines'][$key]['error'] = $value;
							}
							// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
							if(empty($errors)) {
								// подготовка всех значений для сохранения
								foreach($info as $key=>$field){
									if(isset($mapping['subway_lines'][$key]['value'])) $info[$key] = $mapping['subway_lines'][$key]['value'];
								}
								
								// сохранение в БД
								if($action=='edit'){
									$res = $db->updateFromArray($sys_tables['subway_lines'], $info, 'id');
								} else {
									$res = $db->insertFromArray($sys_tables['subway_lines'], $info, 'id');
									if(!empty($res)){
										$new_id = $db->insert_id;
										// редирект на редактирование свеженькой страницы
										if(!empty($res)) {
											header('Location: '.Host::getWebPath('/admin/geodata/subways/lines/edit/'.$new_id.'/'));
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
						Response::SetArray('data_mapping',$mapping['subway_lines']);		
						break;
					case 'del':
						$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
						$res = $db->querys("DELETE FROM ".$sys_tables['subway_lines']." WHERE id=?", $id);
						$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
						if($ajax_mode){
							$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
							break;
						}				
					default:
						$module_template = 'admin.subway_lines.list.html';
				
						// формирование списка
						$conditions = [];
						if(!empty($filters)){
							if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
							if(!empty($filters['parent'])) $conditions['parent'] = "`parent_id` = '".$db->real_escape_string($filters['parent'])."'";
						}
						// формирование списка для фильтра
						$condition = implode(" AND ",$conditions);
						$parents = $db->fetchall("SELECT subways.parent_id,geodata.offname FROM ".$sys_tables['subway_lines']." subways 
												  LEFT JOIN ".$sys_tables['geodata']." geodata ON geodata.id=subways.parent_id
												  WHERE subways.parent_id>0
												  GROUP BY subways.parent_id
												  ORDER BY geodata.offname");
												  
						Response::SetArray('parents',$parents);
						// создаем пагинатор для списка
						$paginator = new Paginator($sys_tables['subway_lines'], 30, $condition);
						// get-параметры для ссылок пагинатора
						$get_in_paginator = [];
						foreach($get_parameters as $gk=>$gv){
							if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
						}
						// ссылка пагинатора
						$paginator->link_prefix = '/admin/geodata/subways/lines/'			           // модуль
												  ."/?"                                       // конечный слеш и начало GET-строки
												  .implode('&',$get_in_paginator)             // GET-строка
												  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
						if($paginator->pages_count>0 && $paginator->pages_count<$page){
							Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
							exit(0);
						}	
						$sql = "SELECT subways.id,subways.title,geodata.offname,subways.color FROM ".$sys_tables['subway_lines']." subways
								LEFT JOIN ".$sys_tables['geodata']." geodata ON geodata.id=subways.parent_id";
						if(!empty($condition)) $sql .= " WHERE ".$condition;
						$sql .= " ORDER BY subways.`title`";
						$sql .= " LIMIT ".$paginator->getLimitString($page); 
						$list = $db->fetchall($sql) or die($sql.$db->error);;
						
						// формирование списка
						Response::SetArray('list', $list);
						if($paginator->pages_count>1){
							Response::SetArray('paginator', $paginator->Get($page));
						}				
						break;		
					
				}
				break;				
            case 'add':
            case 'edit':		
                $module_template = 'admin.subways.edit.html';
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['subways']);
					// установка action для формы
					Response::SetString('form_parameter', 'add');
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['subways']." 
										WHERE id='".$id."'");
					// установка action для формы
					Response::SetString('form_parameter', 'edit/'.$id);
					if(empty($info)) Host::Redirect('/admin/geodata/subways/add/');	
				}
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['subways'][$key])) $mapping['subways'][$key]['value'] = $info[$key];
                }
				// формирование дополнительных данных для формы (не из основной таблицы)
				$parents = $db->fetchall("SELECT id, offname FROM ".$sys_tables['geodata']." WHERE (`a_level`=1 and (`id_region`=78 or `id_region`=77)) OR (`id_city`=1 and a_level=4 and id_area=0) ORDER BY `id_region`=78 DESC, `id_region`=77 DESC, offname") or die($db->error);
				foreach($parents as $key=>$val){
					$mapping['subways']['parent_id']['values'][$val['id']] = $val['offname'];
				}				
				$subway_lines = $db->fetchall("SELECT id, title FROM ".$sys_tables['subway_lines']." ORDER BY `id`") or die($db->error);
				foreach($subway_lines as $key=>$val){
					$mapping['subways']['id_subway_line']['values'][$val['id']] = $val['title'];
				}				
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);

                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['subways'][$key])) $mapping['subways'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['subways']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['subways'][$key])) $mapping['subways'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['subways'][$key]['value'])) $info[$key] = $mapping['subways'][$key]['value'];
                        }
                        
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['subways'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['subways'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/geodata/subways/edit/'.$new_id.'/'));
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
                Response::SetArray('data_mapping',$mapping['subways']);		
				break;
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $res = $db->querys("DELETE FROM ".$sys_tables['subways']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }				
			default:
				$module_template = 'admin.subways.list.html';
		
				// формирование списка
				$conditions = [];
				if(!empty($filters)){
					if(!empty($filters['title'])) $conditions['title'] = $sys_tables['subways'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
					if(!empty($filters['parent'])) $conditions['parent'] = $sys_tables['subways'].".`parent_id` = '".$db->real_escape_string($filters['parent'])."'";
					if(!empty($filters['subway_line'])) $conditions['subway_line'] = $sys_tables['subways'].".`id_subway_line` = '".$db->real_escape_string($filters['subway_line'])."'";
				}
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);
				$parents = $db->fetchall("SELECT subways.parent_id,geodata.offname 
                                          FROM ".$sys_tables['subways']." subways 
										  LEFT JOIN ".$sys_tables['geodata']." geodata ON geodata.id=subways.parent_id
										  WHERE subways.parent_id>0
										  GROUP BY subways.parent_id
										  ORDER BY geodata.offname");
                                          
				Response::SetArray('parents',$parents);
				if(!empty($filters['parent'])){
					$subway_lines = $db->fetchall("SELECT id,title,color FROM ".$sys_tables['subway_lines']." WHERE parent_id=".$filters['parent']." ORDER BY title");
					Response::SetArray('subway_lines',$subway_lines); 
				}
			
				
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['subways'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/geodata/subways/'			           // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}	
				$sql = "SELECT subways.id,subways.title,geodata.offname,subway_lines.color FROM ".$sys_tables['subways']." subways
						LEFT JOIN ".$sys_tables['geodata']." geodata ON geodata.id=subways.parent_id
						LEFT JOIN ".$sys_tables['subway_lines']." subway_lines ON subway_lines.id=subways.id_subway_line ";
				if(!empty($condition)) $sql .= " WHERE ".$condition;
				$sql .= " ORDER BY subways.`title`";
				$sql .= " LIMIT ".$paginator->getLimitString($page); 
				$list = $db->fetchall($sql) or die($sql.$db->error);;
				
				// формирование списка
				Response::SetArray('list', $list);
				if($paginator->pages_count>1){
					Response::SetArray('paginator', $paginator->Get($page));
				}				
				break;		
			
		}
		break;		
    /*************************\
    |*  Работа с геоданными   *|
    \*************************/		
	case 'objects':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
		
		switch($action){	
			case 'add':
			case 'edit':
				$module_template = 'admin.geodata.edit.html';
				$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['geodata']);
					// установка action для формы
					Response::SetString('form_parameter', 'add');
				} else {
					// получение данных из БД
					
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['geodata']." 
										WHERE id='".$id."'");
					// установка action для формы
					Response::SetString('form_parameter', 'edit/'.$id);
					if(empty($info)) Host::Redirect('/admin/geodata/objects/add/');
					//редирект на страницу с параметрами при редактировании
					$redirect_parameters = [];
					if(empty($get_parameters)){
						if($info['id_country']>0 && $info['a_level']>0) $redirect_parameters['id_country'] = $info['id_country']; 
						if($info['id_region']>0 && $info['a_level']>1) $redirect_parameters['id_region'] = $info['id_region']; 
						if($info['id_area']>0 && $info['a_level']>3) $redirect_parameters['id_area'] = $info['id_area'];
						foreach($redirect_parameters as $rpk=>$rpv) $redirect_parameters[$rpk] = $rpk."=".$rpv;
						if(!empty($redirect_parameters)) Host::Redirect('/admin/geodata/objects/edit/'.$id.'/?'.implode('&',$redirect_parameters));
					}
				}
				
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
				$get_params = Request::GetString(METHOD_GET);
		
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['geodata'][$key])) $mapping['geodata'][$key]['value'] = $info[$key];
				}
				// формирование дополнительных данных для фильтра
				$id_country = $db->fetchall("SELECT id_country, offname FROM ".$sys_tables['geodata']." WHERE `a_level`=0  ORDER BY `id_country` = 20 DESC, offname");
				Response::SetArray('id_country',$id_country);
				if(!empty($filters['id_country'])) {
					$id_region = $db->fetchall("SELECT id_region, CONCAT_WS(' ',offname,shortname) as offname FROM ".$sys_tables['geodata']." WHERE `a_level`=1 ".(!empty($filters['id_country']) ? "AND id_country=".$filters['id_country']:"")." ORDER BY offname");
					Response::SetArray('id_region',$id_region);
				}
				if(!empty($filters['id_region'])) {
					$id_area = $db->fetchall("SELECT id_area, CONCAT_WS(' ',offname,shortname) as offname FROM ".$sys_tables['geodata']." WHERE `a_level`=3 ".(!empty($filters['id_region']) ? "AND id_region=".$filters['id_region']:"")."".(!empty($filters['id_country']) ? " AND id_country=".$filters['id_country']:"")." ORDER BY offname");
					Response::SetArray('id_area',$id_area);
				}
				
				// формирование типа объекта в зависимости от выбранного уровня географической привязки
				if(!empty($filters['id_area'])) { // тип объекта город level=4
					$mapping['geodata']['levels']['values'] = array('4'=>'город');
				} elseif(!empty($filters['id_region'])) { // тип объекта региональный объект или город level=4 || level=3
					$mapping['geodata']['levels']['values'] = array('3'=>'регональный объект','4'=>'город');
				} elseif(!empty($filters['id_country'])) { // тип объекта региональный объект или город level=1 (может быть и столица страны)
					$mapping['geodata']['levels']['values'] = array('1'=>'регион');
				} else { // пустая форма 
					$mapping['geodata']['levels']['values'] = array('0'=>'страна');
				}
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['geodata'][$key])) $mapping['geodata'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['geodata']);
					//проверка на существование записи
					$duplicate = $db->fetchall("SELECT `aoguid` FROM ".$sys_tables['geodata']." WHERE 
						`a_level`=".$mapping['geodata']['levels']['value'].
						($filters['id_country']>0?" AND `id_country` = ".$filters['id_country']." ":
							($mapping['geodata']['levels']['value']>0 && $filters['id_region']>0?" AND `id_region` = ".$filters['id_region']." ":
								($mapping['geodata']['levels']['value']>1 && $filters['id_area']>0?" AND `id_area` = ".$filters['id_area']." ":"")
							)
						).
						($action=='edit' && $id>0?" AND `id`!=".$id:"").  
						" AND `offname` = '".$mapping['geodata']['offname']['value']."'");
					if(!empty($duplicate)) $mapping['geodata']['offname']['error'] = 'Запись уже существует';
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['geodata'][$key])) $mapping['geodata'][$key]['error'] = $value;
					}
					
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(!empty($mapping['geodata'][$key]['value'])) $info[$key] = $mapping['geodata'][$key]['value'];
						}
						if($action=='add'){
							$info['id_city'] = 0;
							$info['id_area'] = $filters['id_area'];
							$info['id_region'] = $filters['id_region'];
							$info['id_country'] = $filters['id_country'];
							$info['a_level'] = $mapping['geodata']['levels']['value'];				
							//формирование списка значений для выбранного типа объекта
							switch($info['a_level']){
								case 4: //выбран город
									$city = $db->fetch("SELECT MAX(id_city) as id, `parentguid` FROM ".$sys_tables['geodata']." WHERE `a_level`=4 AND `id_country` = ".$info['id_country']." AND `id_region` = ".$info['id_region']."  AND `id_area` = ".$info['id_area']);
									$info['id_city'] = $city['id']+1;
									if(empty($city['parentguid'])){
										$parentguid = $db->fetch("SELECT `aoguid` FROM ".$sys_tables['geodata']." WHERE `a_level`<4 AND `id_country` = ".$info['id_country']." AND `id_region` = ".$info['id_region']." AND `id_area` = ".$info['id_area']);
										$info['parentguid'] = $parentguid['aoguid'];
									} else $info['parentguid'] = $city['parentguid'];
									break;
								case 3: //выбран региональный объект (область)
									$area = $db->fetch("SELECT MAX(id_area) as id, `parentguid` FROM ".$sys_tables['geodata']." WHERE `a_level`=3 AND `id_country` = ".$info['id_country']." AND `id_region` = ".$info['id_region']);
									$info['id_area'] = $area['id']+1;
									//определение parentguid если это первая запись для объекта
									if(empty($area['parentguid'])){
										$parentguid = $db->fetch("SELECT `aoguid` FROM ".$sys_tables['geodata']." WHERE `a_level`=1 AND `id_country` = ".$info['id_country']." AND `id_region` = ".$info['id_region']);
										$info['parentguid'] = $parentguid['aoguid'];
									} else $info['parentguid'] = $area['parentguid'];
									break;
								case 1: //выбран регион
									$region = $db->fetch("SELECT MAX(id_region) as id, `parentguid` FROM ".$sys_tables['geodata']." WHERE `a_level`=1 AND `id_country` = ".$info['id_country']);
									$info['id_region'] = $region['id']+1;
									//определение parentguid если это первая запись для объекта
									if(empty($region['parentguid'])){
										$parentguid = $db->fetch("SELECT `aoguid` FROM ".$sys_tables['geodata']." WHERE `a_level`=0 AND `id_country` = ".$info['id_country']);
										$info['parentguid'] = $parentguid['aoguid'];
									} else $info['parentguid'] = $region['parentguid'];
									break;
								default: //ничего не выбрано, значит страна
									$country = $db->fetch("SELECT MAX(id_country) as id FROM ".$sys_tables['geodata']." WHERE `a_level`=0");
									$info['id_country'] = $country['id']+1;
									$info['parentguid'] = NULL;
									break;
								
							}
							// определение уникального aoguid				
							do {
								$aoguid = substr(sha1(time()),0,36);
								$checkaoguid = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']." WHERE `aoguid`='".$aoguid."'");
							} while (!empty($checkaoguid));
							$info['aoguid'] = $aoguid;
						}
						// сохранение в БД
						if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['geodata'], $info, 'id');
						} else {
							$res = $db->insertFromArray($sys_tables['geodata'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/geodata/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['geodata']);
				break;			
			case 'del':
				$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
				$values = $db->fetch("SELECT * FROM ".$sys_tables['geodata']." WHERE id=?", $id);
				$res = $db->querys("DELETE FROM ".$sys_tables['geodata']." WHERE 
									`a_level`>=".$values['a_level'].
									($values['id_country']>0?" AND `id_country` = ".$values['id_country']:"").
									($values['id_region']>0?" AND `id_region` = ".$values['id_region']:"").
									($values['id_area']>0?" AND `id_area` = ".$values['id_area']:"")
									);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			default:
				$module_template = 'admin.geodata.list.html';
		
				// формирование списка
				$conditions = [];
				if(!empty($filters)){
					if(!empty($filters['title'])) $conditions['title'] = "`offname` LIKE '%".$db->real_escape_string($filters['title'])."%'";
					if(!empty($filters['id_country'])) $conditions['id_country'] = "`id_country` = '".$db->real_escape_string($filters['id_country'])."'";
					if(!empty($filters['id_region'])) $conditions['region'] = "`id_region` = '".$db->real_escape_string($filters['id_region'])."'";
				}
				//выбор последнего элемента для поиска по parenguid
				$condition = implode(" AND ",$conditions);
				// формирование списка для фильтра
				$countries = $db->fetchall("SELECT id_country, offname FROM ".$sys_tables['geodata']." WHERE `a_level`=0 ORDER BY id_country=20 DESC ,offname");
				Response::SetArray('countries',$countries);
				$regions = $db->fetchall("SELECT id_region, CONCAT_WS(' ',shortname,offname) as offname FROM ".$sys_tables['geodata']." WHERE `a_level`=1 ".(!empty($conditions['id_country']) ? "AND ".$conditions['id_country']."":"")." ORDER BY offname");
				Response::SetArray('regions',$regions);
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['geodata'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/geodata'			           // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
		
				$sql = "SELECT 
							`id`,
							IF(`a_level`=0,'страна',
								IF(`a_level`=1,'регион',
									IF(`a_level`=3,'региональный объект',
										IF(`a_level`=4,'город','')
									)
								)
							) as `a_level`,
							CONCAT_WS(' ',shortname,offname) as `offname` 
						FROM ".$sys_tables['geodata'];
				if(!empty($condition)) $sql .= " WHERE ".$condition;				
				$sql .= " ORDER BY `a_level`, `offname`";
				$sql .= " LIMIT ".$paginator->getLimitString($page); 
				$list = $db->fetchall($sql);
				// формирование списка
				Response::SetArray('list', $list);
				if($paginator->pages_count>1){
					Response::SetArray('paginator', $paginator->Get($page));
				}
		}
        break;
        
        /***************************************\
        |*  Работа с нераспознанными адресами  *|
        \**************************************/
        case 'wrong_streets': 
            // сохраняем изменения для адреса: записываем id_street и true_title
            $old_action=$action;
            $action=Request::GetString('action', METHOD_POST);
            //если нужно сохранить изменения
            if (@$this_page->page_parameters[1]=='save_street'){
                $id=Request::GetInteger('id',METHOD_POST);
                $id_street=Request::GetInteger('id_street', METHOD_POST);
                $title_street = Request::GetString('title_street', METHOD_POST);
                if (empty($id)||empty($id_street)||empty($title_street)) return false;
                $query="UPDATE ".$sys_tables['wrong_streets']." SET id_street=".$id_street.", true_title='".$title_street."'"." WHERE id=".$id;
                $db->querys($query) or die($db->error);
                
                // запоминаем для шаблона GET - параметры
                Response::SetArray('get_array', $get_parameters);
                foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk."=".$gv;
                Response::SetString('get_string', implode('&',$get_parameters));
                exit(0);
            }
            else{
                //выводим список улиц для выпадающего списка
                if(@$action=='street_list'){
                    $geo_id=34142;//СПБ
                    if($geo_id==0) $ajax_result['ok'] = false;
                    else {
                        $info = $db->fetch("SELECT `aoguid` FROM ".$sys_tables['geodata']."
                                            WHERE id=?", $geo_id);
                        $search_str = Request::GetString('search_string', METHOD_POST);
                        $list = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']." WHERE parentguid=? AND a_level=5 AND offname LIKE ? ORDER BY offname LIMIT ?", false, $info['aoguid'], "%".$search_str."%", 10);
                        $ajax_result['ok'] = true;
                        $ajax_result['query'] = '';
                        $ajax_result['list'] = $list;
                        
                    }
                }
                else{
                    $GLOBALS['css_set'][] = '/modules/geodata/autocomplete.css';
                    $GLOBALS['js_set'][] = '/modules/geodata/ajax_actions.js';
                    $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                    $GLOBALS['js_set'][] = '/js/jquery.addrselector.js';
                    $GLOBALS['js_set'][] = '/modules/geodata/streets_autocomplette.js';
                    $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                    $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                    $module_template = 'admin.wrong_streets.list.html';
                    $filters = [];
                    
                    //читаем фильтры
                    $where = false;
                    
                    //часть адреса
                    $filters['title'] = Request::GetString('f_title',METHOD_GET);
                    //1-адрес распознан, 2-нет
                    $filters['status'] = Request::GetString('f_status',METHOD_GET);
                    //пишем в get-параметры
                    if(!empty($filters['title'])) {
                        $filters['title'] = urldecode($filters['title']);
                        $get_parameters['f_title'] = $filters['title'];
                    }
                    if(!empty($filters['status'])) {
                        $get_parameters['f_status'] = $filters['status'];
                    }
                    //составляем WHERE
                    if(!empty($filters['title'])) $where = " `title` LIKE '%".$filters['title']."%'";
                    if(!empty($filters['status'])){
                        if (!empty($where)){
                            if($filters['status']==1) $where.= " AND true_title!=''";
                            if($filters['status']==2) $where.= " AND true_title=''";
                        } 
                        else{
                            if($filters['status']==1) $where.= " true_title!=''";
                            if($filters['status']==2) $where.= " true_title=''";
                        } 
                    }
                    
                    // создаем пагинатор для списка
                    $paginator = new Paginator($sys_tables['wrong_streets'], 30, $where);
                    // get-параметры для ссылок пагинатора
                    $get_in_paginator = [];
                    foreach($get_parameters as $gk=>$gv){
                        if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                    }
                    // ссылка пагинатора
                    $paginator->link_prefix = '/admin/geodata/wrong_streets'                  // модуль
                                              ."/?"                                       // конечный слеш и начало GET-строки
                                              .implode('&',$get_in_paginator)             // GET-строка
                                              .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                    if($paginator->pages_count>0 && $paginator->pages_count<$page){
                        Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                        exit(0);
                    }
                    //выбираем страницы для отображения
                    $sql = "SELECT id,title,true_title FROM ".$sys_tables['wrong_streets'];
                    if(!empty($where)) $sql.=" WHERE ".$where;
                    $sql .= " ORDER BY `id`";
                    $sql .= " LIMIT ".$paginator->getLimitString($page);
                    $list = $db->fetchall($sql);
                    
                    // формирование списка
                    Response::SetArray('list', $list);
                    if($paginator->pages_count>1){
                        Response::SetArray('paginator', $paginator->Get($page));
                    }
                    
                    //определение улицы
                    $street = $db->fetch("SELECT `offname`, `shortname` FROM ".$sys_tables['geodata']." WHERE a_level = 5 AND id_region=78 AND id_area=0 AND id_city=0 AND id_place=0");
                    $info['txt_street'] = $street['offname'].' '.$street['shortname'];
                }
            }
            
            
        break;
    default:
        // переопределяем экшн
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch($action){
            //добавление адресов из XML, которые не нашлись у нас
            case 'address_adding':
                // переопределяем экшн
                $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                
                switch($action){
                    case 'add':
                        if($ajax_mode){
                            $id = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                            //читаем информацию по улице:
                            $object_info = $db->fetch("SELECT * FROM ".$sys_tables['addresses_to_add']." WHERE id = ?",$id);
                            
                            //если как-то попали на добавление привязанного
                            if($object_info['id_geodata'] > 0){
                                $ajax_result['error'] = "Нельзя добавлять привязанные улицы";
                                $ajax_result['ok'] = true;
                                break;
                            }
                            
                            $geo_condition = [];
                            foreach($object_info as $key=>$item){
                                if(in_array($key,array('id_region','id_area','id_city','id_place'))||($key == 'id_district' && in_array($key,array(27,29,38,43,53)))) $geo_condition[] = $key." = ".$item;
                            }
                            $geo_condition = implode(' AND ',$geo_condition);
                            $condition = (!empty($geo_condition)?$geo_condition." AND a_level = 5":"a_level = 5");
                            
                            //смотрим максимальный id по этому месту
                            $parent_info = $db->fetch("SELECT MAX(id_street) AS max_id,parentguid
                                                  FROM ".$sys_tables['geodata']." 
                                                  WHERE ".$condition);
                            //если там нет улиц, читаем aoguid родителя
                            if(empty($parent_info)) $parent_info = $db->fetch("SELECT 0 AS max_id,aoguid AS parentguid
                                                                               FROM ".$sys_tables['geodata']." 
                                                                               WHERE ".$geo_condition);
                            else{
                                //проверяем, что там нет улицы с таким названием
                                $exists_already = $db->fetch("SELECT id FROM ".$sys_tables['geodata']." WHERE ".$geo_condition." AND offname = ?",$object_info['offname']);
                                if(!empty($exists_already)){
                                    $ajax_result['error'] = "Такая улица уже есть в этом месте";
                                    $ajax_result['ok'] = true;
                                    break;
                                }
                            }
                            
                            if(empty($parent_info)){
                                $ajax_result['error'] = "Не удалось прочитать запись";
                                $ajax_result['ok'] = true;
                                break;
                            }
                            
                            //генерим aoguid: md5 = c88c9036539a89c979afbb5a9b849376 => c88c9036-539a-89c9-79af-bb5a9b849376
                            $new_aoguid = md5(json_encode($object_info));
                            $new_aoguid = substr($new_aoguid,0,8)."-".substr($new_aoguid,8,4)."-".substr($new_aoguid,12,4)."-".substr($new_aoguid,16,4)."-".substr($new_aoguid,20);
                            $addr_id = $object_info['id'];
                            unset($object_info['id']);
                            $object_info['id_street'] = $parent_info['max_id'] + 1;
                            $object_info['aoguid'] = $new_aoguid;
                            $object_info['parentguid'] = $parent_info['parentguid'];
                            $object_info['a_level'] = 5;
                            $object_info['offname'] = mb_convert_case($object_info['offname'], MB_CASE_TITLE, "UTF-8");
                            
                            $ajax_result['res'] = $db->insertFromArray($sys_tables['geodata'],$object_info);
                            $ajax_result['ok'] = true;
                            
                            //если все хорошо, отмечаем что улица добавлена: ставим время добавления
                            if(!empty($ajax_result['res'])){
                                $db->querys("UPDATE ".$sys_tables['addresses_to_add']." SET date_out = CURRENT_TIMESTAMP WHERE id = ?",$addr_id);
                                $ajax_result['ids'] = array($addr_id);
                            }else $ajax_result['error'] = "Ошибка запроса к базе";
                        }
                        break;
                    //ставим в соответствие адрему из таблицы адрес из geodata
                    case 'match':
                        if($ajax_mode){
                            $id = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                            $geo_id = Request::GetInteger('geo_id',METHOD_POST);
                            $ajax_result['res'] = $db->querys("UPDATE ".$sys_tables['addresses_to_add']." SET date_out = CURRENT_TIMESTAMP, id_geodata = ? WHERE id = ?",$geo_id,$id);
                            $ajax_result['ids'] = array($id);
                            $ajax_result['ok'] = true;
                        }
                        break;
                    case 'area_list':
                        // список районов ЛО для автокомплита
                        $search_string = Request::GetString('search_string', METHOD_POST);
                        $geo_data = Request::GetArray('geo_data', METHOD_POST);
                        $geo_condition = [];
                        $geo_condition[] = "offname LIKE '%".$db->real_escape_string($search_string)."%'";
                        $geo_condition[] = "a_level = 2";
                        if(!empty($geo_condition)) $geo_condition = implode(' AND ',$geo_condition);
                        if(empty($search_string)) $ajax_result['ok'] = false;
                        else {
                            $list = $db->fetchall("SELECT offname,shortname,id_area AS id FROM ".$sys_tables['geodata']." WHERE ".$geo_condition);
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                        }
                        break;
                    case 'district_list':
                        // список районов СПБ для автокомплита
                        $search_string = Request::GetString('search_string', METHOD_POST);
                        $geo_data = Request::GetArray('geo_data', METHOD_POST);
                        $geo_condition = "title LIKE '%".$db->real_escape_string($search_string)."%'";
                        if(empty($search_string)) $ajax_result['ok'] = false;
                        else {
                            $list = $db->fetchall("SELECT title AS offname, 'р-н' AS shortname, id FROM ".$sys_tables['districts']." WHERE ".$geo_condition);
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                        }
                        break;
                    case 'city_list':
                        // список городов для автокомплита
                        $search_string = Request::GetString('search_string', METHOD_POST);
                        $geo_data = Request::GetArray('geo_data', METHOD_POST);
                        $geo_condition = [];
                        $geo_condition[] = "offname LIKE '%".$db->real_escape_string($search_string)."%'";
                        $geo_condition[] = "a_level = 3";
                        unset($geo_data['city']);
                        unset($geo_data['place']);
                        foreach($geo_data as $key=>$item){
                            if(!empty($item)) $geo_condition[] = "id_".$db->real_escape_string($key)." = ".$db->real_escape_string($item);
                        }
                        if(!empty($geo_condition)) $geo_condition = implode(' AND ',$geo_condition);
                        if(empty($search_string)) $ajax_result['ok'] = false;
                        else {
                            $list = $db->fetchall("SELECT offname,shortname,id_city AS id FROM ".$sys_tables['geodata']." WHERE ".$geo_condition);
                            $ajax_result['ok'] = true;
                            $ajax_result['query'] = '';
                            $ajax_result['list'] = $list;
                        }
                        break;
                    case 'place_list':
                        // список поселков для автокомплита
                        $search_string = Request::GetString('search_string', METHOD_POST);
                        $geo_data = Request::GetArray('geo_data', METHOD_POST);
                        $geo_condition = [];
                        $geo_condition[] = "offname LIKE '%".$db->real_escape_string($search_string)."%'";
                        $geo_condition[] = "a_level = 4";
                        unset($geo_data['place']);
                        foreach($geo_data as $key=>$item){
                            if(!empty($item)) $geo_condition[] = "id_".$db->real_escape_string($key)." = ".$db->real_escape_string($item);
                        }
                        if(!empty($geo_condition)) $geo_condition = implode(' AND ',$geo_condition);
                        if(empty($search_string)) $ajax_result['ok'] = false;
                        else {
                            $list = $db->fetchall("SELECT offname,shortname,id_place AS id FROM ".$sys_tables['geodata']." WHERE ".$geo_condition);
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                        }
                        break;
                    case 'street_list':
                        // список улиц для автокомплита
                        $search_string = Request::GetString('search_string', METHOD_POST);
                        $geo_data = Request::GetArray('geo_data', METHOD_POST);
                        $geo_condition = [];
                        $geo_condition[] = "offname LIKE '%".$db->real_escape_string($search_string)."%'";
                        $geo_condition[] = "a_level = 5";
                        //ищем без учета района
                        unset($geo_data['district']);
                        foreach($geo_data as $key=>$item){
                             if(!empty($item)) $geo_condition[] = "id_".$db->real_escape_string($key)." = ".$db->real_escape_string($item);
                        }
                        if(!empty($geo_condition)) $geo_condition = implode(' AND ',$geo_condition);
                        if(empty($search_string)) $ajax_result['ok'] = false;
                        else {
                            $list = $db->fetchall("SELECT offname,shortname,id,id_place,id_city FROM ".$sys_tables['geodata']." WHERE ".$geo_condition);
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                        }
                        break;
                    case 'edit':
                        
                        $GLOBALS['css_set'][] = '/modules/geodata/autocomplete.css';
                        $GLOBALS['js_set'][] = '/modules/geodata/ajax_actions.js';
                        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                        $GLOBALS['js_set'][] = '/js/jquery.addrselector.js';
                        $GLOBALS['js_set'][] = '/modules/geodata/streets_autocomplette.js';
                        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                        
                        $module_template = 'admin.address_adding.edit.html';
                        $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                        
                        //добавления нет, только редактирование
                        // получение данных из БД
                        $info = $db->fetch("SELECT *
                                            FROM ".$sys_tables['addresses_to_add']." 
                                            WHERE id='".$id."'");
                        
                        // установка action для формы
                        Response::SetString('form_parameter', 'edit/'.$id);
                        if(empty($info)) Host::Redirect('/admin/service/geodata/address_adding/');
                        
                        // перенос дефолтных (считанных из базы) значений в мэппинг формы
                        foreach($info as $key=>$field){
                            if(!empty($mapping['geo_object'][$key])) $mapping['geo_object'][$key]['value'] = $info[$key];
                        }
                        
                        //если редактируем добавленную в базу - нельзя сохранять и привязывать
                        $added_to_base = (empty($info['id_geodata']) && $info['date_out'] != '0000-00-00 00:00:00');
                        Response::SetBoolean('added_to_base',$added_to_base);
                        if($added_to_base){
                            unset($mapping['geo_object']['txt_street']);
                        } 
                        
                        if($info['id_region'] == 47) $mapping['geo_object']['txt_district']['disabled'] = true;
                        else $mapping['geo_object']['txt_area']['disabled'] = true;
                        
                        //читаем то что есть:
                        //район ЛО
                        if(!empty($info['id_area'])){
                            $mapping['geo_object']['txt_area']['value'] = $db->fetch("SELECT offname 
                                                                                      FROM ".$sys_tables['geodata']." 
                                                                                      WHERE id_region = ? AND id_area = ? AND a_level = 2",$info['id_region'],$info['id_area']);
                            $mapping['geo_object']['txt_area']['value'] = (empty($mapping['geo_object']['txt_area']['value'])?"":$mapping['geo_object']['txt_area']['value']['offname']);
                        }
                        //город
                        if(!empty($info['id_city'])){
                            $mapping['geo_object']['txt_city']['value'] = $db->fetch("SELECT offname 
                                                                                      FROM ".$sys_tables['geodata']." 
                                                                                      WHERE id_region = ? AND
                                                                                            ".(!empty($info['id_area'])?$info['id_area']." AND ":"")."
                                                                                            id_city = ? AND
                                                                                            a_level = 3",$info['id_region'],$info['id_city']);
                            $mapping['geo_object']['txt_city']['value'] = (empty($mapping['geo_object']['txt_city']['value'])?"":$mapping['geo_object']['txt_city']['value']['offname']);
                        }
                        //поселок/деревня
                        if(!empty($info['id_place'])){
                            $mapping['geo_object']['txt_place']['value'] = $db->fetch("SELECT offname 
                                                                                       FROM ".$sys_tables['geodata']." 
                                                                                       WHERE id_region = ? AND
                                                                                             ".(!empty($info['id_area'])?$info['id_area']." AND ":"")."
                                                                                             ".(!empty($info['id_city'])?$info['id_city']." AND ":"")."
                                                                                             id_place = ? AND
                                                                                             a_level = 4",$info['id_region'],$info['id_place']);
                            $mapping['geo_object']['txt_place']['value'] = (empty($mapping['geo_object']['txt_place']['value'])?"":$mapping['geo_object']['txt_place']['value']['offname']);
                        }
                        //улица
                        if(!empty($info['id_geodata'])){
                            $mapping['geo_object']['txt_street']['value'] = $db->fetch("SELECT offname 
                                                                                        FROM ".$sys_tables['geodata']." 
                                                                                        WHERE id_region = ? AND
                                                                                              ".(!empty($info['id_area'])?$info['id_area']." AND ":"")."
                                                                                              ".(!empty($info['id_city'])?$info['id_city']." AND ":"")."
                                                                                              ".(!empty($info['id_place'])?$info['id_place']." AND ":"")."
                                                                                              id = ? AND
                                                                                              a_level = 5",$info['id_region'],$info['id_geodata']);
                            $mapping['geo_object']['txt_street']['value'] = (empty($mapping['geo_object']['txt_street']['value'])?"":$mapping['geo_object']['txt_street']['value']['offname']);
                        }
                        //район города
                        if(!empty($info['id_district'])){
                            $mapping['geo_object']['txt_district']['value'] = $db->fetch("SELECT title FROM ".$sys_tables['districts']." 
                                                                                          WHERE id = ?",$info['id_district']);
                            $mapping['geo_object']['txt_district']['value'] = (empty($mapping['geo_object']['txt_district']['value'])?"":$mapping['geo_object']['txt_district']['value']['title']);
                        }
                        
                        /*
                        //районы области
                        $district_area = $db->fetchall("SELECT id, offname FROM ".$sys_tables['geodata']." WHERE id_region = 47 AND a_level = 2");
                        foreach($district_areas as $key=>$val){
                            $mapping['geo_object']['district_areas']['values'][$val['id']] = $val['offname'];
                        }                
                        //города
                        $cities = $db->fetchall("SELECT id,id_region,id_area,offname FROM ".$sys_tables['geodata']." WHERE a_level = 3");
                        foreach($cities as $key=>$val){
                            $mapping['geo_object']['cities']['values'][$val['id']] = $val['offname'];
                        }
                        //деревни/поселки
                        $places = $db->fetchall("SELECT id,id_region,id_area,id_city,offname FROM ".$sys_tables['geodata']." WHERE a_level = 4");
                        foreach($places as $key=>$val){
                            $mapping['geo_object']['places']['values'][$val['id']] = $val['offname'];
                        }
                        */
                        
                        // получение данных, отправленных из формы
                        $post_parameters = Request::GetParameters(METHOD_POST);
                        //добавленные в базу нельзя редактировать
                        if(!empty($added_to_base)) unset($post_parameters);
                        // если была отправка формы - начинаем обработку
                        if(!empty($post_parameters['submit'])){
                            
                            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                            foreach($post_parameters as $key=>$field){
                                if(!empty($mapping['geo_object'][$key])) $mapping['geo_object'][$key]['value'] = $post_parameters[$key];
                            }
                            // проверка значений из формы
                            $errors = Validate::validateParams($post_parameters,$mapping['districts']);
                            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                            foreach($errors as $key=>$value){
                                if(!empty($mapping['geo_object'][$key])) $mapping['geo_object'][$key]['error'] = $value;
                            }
                            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                            if(empty($errors)) {
                                //если прикрепили к объект
                                if(empty($info['id_geodata']) && !empty($mapping['geo_object']['id_geodata']['value'])) $info['date_out'] = date("Y-m-d H:i:s",time());
                                // подготовка всех значений для сохранения
                                foreach($info as $key=>$field){
                                    if(isset($mapping['geo_object'][$key]['value'])) $info[$key] = $mapping['geo_object'][$key]['value'];
                                }
                                
                                // сохранение в БД
                                if($action=='edit'){
                                    
                                    $res = $db->updateFromArray($sys_tables['addresses_to_add'], $info, 'id');
                                    //если флаг есть, еще и добавляем геообъект
                                    if(!empty($submit_and_add)){
                                        
                                    }
                                } else {
                                    $res = $db->insertFromArray($sys_tables['addresses_to_add'], $info, 'id');
                                    if(!empty($res)){
                                        $new_id = $db->insert_id;
                                        // редирект на редактирование свеженькой страницы
                                        if(!empty($res)) {
                                            header('Location: '.Host::getWebPath('/admin/service/geodata/address_adding/edit/'.$new_id.'/'));
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
                        Response::SetArray('data_mapping',$mapping['geo_object']);
                        break;
                    case 'del':
                        $id = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                        $ajax_result['ok'] = $db->querys("DELETE FROM ".$sys_tables['addresses_to_add']." WHERE id = ?",$id);
                        if(!$ajax_result['ok']) $ajax_result['error'] = "Ошибка запроса к базе";
                        else $ajax_result['ids'] = array($id);
                        break;
                    //общий список
                    default:
                        require_once('includes/class.geo.php');
                
                        //собираем список форматов
                        $formats = array('yrxml'=>'Yandex','bnxml'=>'BN XML','eipxml'=>'EIP XML','avitoxml'=>'Avito');
                        Response::SetArray('formats',$formats);
                
                        $where = [];
                        
                        if(!empty($filters['title'])) $where[] = "`addr_source` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                        if(!empty($filters['format'])) $where[] = "`file_format` = '".$db->real_escape_string($filters['format'])."'";
                        if(!empty($filters['id_agency'])){
                            $id_agency_user = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id_agency = ? AND agency_admin = 1",$filters['id_agency']);
                            if(!empty($id_agency_user)) $where[] = "`id_user` = '".$db->real_escape_string($id_agency_user['id'])."'";
                        }
                        
                        if(!empty($filters['id_user'])) $where[] = "`id_user` = '".$db->real_escape_string($filters['id_user'])."'";
                        if(!empty($filters['status'])) $where[] = ($filters['status'] == 1)?"`date_out` LIKE '0000%'":"`date_out` NOT LIKE '0000%'";
                        
                        if(!empty($where)) $where = implode(' AND ',$where);
                        else $where = "";
                        
                        // создаем пагинатор для списка
                        $paginator = new Paginator($sys_tables['addresses_to_add'], 30, $where);
                        // get-параметры для ссылок пагинатора
                        $get_in_paginator = [];
                        foreach($get_parameters as $gk=>$gv){
                            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                        }
                        // ссылка пагинатора
                        $paginator->link_prefix = '/admin/service/geodata/address_adding'                  // модуль
                                                  ."/?"                                       // конечный слеш и начало GET-строки
                                                  .implode('&',$get_in_paginator)             // GET-строка
                                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                        if($paginator->pages_count>0 && $paginator->pages_count<$page){
                            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                            exit(0);
                        }
                        //выбираем страницы для отображения
                        $sql = "SELECT ".$sys_tables['addresses_to_add'].".*,
                                       DATE_FORMAT(".$sys_tables['addresses_to_add'].".date_in,'%d.%m.%Y %h:%i%:%s') AS date_in_formatted,
                                       ".$sys_tables['addresses_to_add'].".date_out NOT LIKE '0000%' AS added,
                                       ".$sys_tables['users'].".id AS user_id,
                                       ".$sys_tables['users'].".email AS user_email,
                                       ".$sys_tables['users'].".name AS user_name,
                                       ".$sys_tables['agencies'].".id AS agency_id,
                                       ".$sys_tables['agencies'].".title AS agency_title
                                FROM ".$sys_tables['addresses_to_add']."
                                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['addresses_to_add'].".id_user = ".$sys_tables['users'].".id
                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id";
                        if(!empty($where)) $sql.=" WHERE ".$where;
                        $sql .= " ORDER BY `id`";
                        $sql .= " LIMIT ".$paginator->getLimitString($page);
                        
                        $list = $db->fetchall($sql);
                        
                        //для всего списка формируем адреса
                        /*
                        foreach($list as $key=>$item){
                            $list[$key]['address'] = Geo::getAddress($item);
                        }
                        */
                        foreach($list as $key=>$item){
                            $list[$key]['address'] = Geo::getAddress($item);// $object->getAddress($item['id'],true);
                            if(!empty($item['id_geodata'])){
                                $geo = $db->fetch("SELECT * FROM ".$sys_tables['geodata']." WHERE id = ?",$item['id_geodata']);
                                $list[$key]['matched_address'] = Geo::getAddress($geo);
                            }
                            $list[$key]['addr_variants'] = Geo::getAddrList($item['offname']." ".$item['shortname'],$item);
                            unset($object);
                        }
                        
                        Response::SetArray('list', $list);
                        if($paginator->pages_count>1){
                            Response::SetArray('paginator', $paginator->Get($page));
                        }
                        
                        $module_template = "admin.address_adding.html";
                        break;
                }
                break;
            //коррекция дублей в адресах
            case 'address_matching':
                break;
            //заглавная страница
            default:
                $module_template = "admin.geodata.html";
                break;
        }
        break;
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk."=".$gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>