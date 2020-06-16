<?php
$GLOBALS['js_set'][] = '/modules/cottages/ajax_actions.js';
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Кампании'));

$cottage_photo_folder = Config::$values['img_folders']['cottages'];
$GLOBALS['js_set'][] = '/modules/estate/form_estate.js';            
        
// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['has_email'] = Request::GetInteger('f_has_email',METHOD_GET);
$filters['stady'] = Request::GetInteger('f_stady',METHOD_GET);
$filters['year'] = Request::GetInteger('f_year',METHOD_GET);
$filters['referer'] = Request::GetString('referer',METHOD_GET);
$filters['manager'] = Request::GetInteger('f_manager',METHOD_GET);
if(!empty($filters['manager'])) {
    $get_parameters['f_manager'] = $filters['manager'];
}       
$filters['seller'] = Request::GetInteger('f_seller',METHOD_GET);
if(!empty($filters['seller'])) {
    $get_parameters['f_seller'] = $filters['seller'];
}    
$filters['developer'] = Request::GetInteger('f_developer',METHOD_GET);
if(!empty($filters['developer'])) {
    $get_parameters['f_developer'] = $filters['developer'];
}    
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['developer'])) $get_parameters['f_developer'] = $filters['developer']; else $filters['developer'] = 0;
if(!empty($filters['has_email'])) $get_parameters['f_has_email'] = $filters['has_email']; 
if(!empty($filters['referer'])) $get_parameters['f_referer'] = $filters['referer']; else $filters['developer'] = false;
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

/*
for($i=2; $i<=40; $i++){
   $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_full_shows']." SET date = CURDATE() - INTERVAL ".$i." DAY, amount = ".mt_rand(150,300).", type = 2, id_parent = 163");
   $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_full_clicks']." SET date = CURDATE() - INTERVAL ".$i." DAY, amount = ".mt_rand(15,30).", type = 2, id_parent = 163");
}
*/

// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
// обработка action-ов
switch($action){
    case 'save_manager':
        $id = Request::GetInteger('id', METHOD_POST);
        $id_manager = Request::GetInteger('id_manager', METHOD_POST);
        if(!empty($id_manager) && !empty($id)) {
            $res = $db->query("UPDATE ".$sys_tables['cottages']." SET id_manager = ? WHERE id = ?", $id_manager, $id);
            $ajax_result['ok'] = $res;
        }
        break;               
    /*************************************************\
    |*  Работа с Каталогом девелоперов кот.поселков  *|
    \*************************************************/
    case 'developers':
        // переопределяем экшн
       $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
			case 'add':
			case 'edit':
				$module_template = 'admin.developers.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['cottages_developers']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['cottages_developers']." 
										WHERE id=?", $id) ;
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['developers'][$key])) $mapping['developers'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
		
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['developers'][$key])) $mapping['developers'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['developers']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['developers'][$key])) $mapping['developers'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['developers'][$key]['value'])) $info[$key] = $mapping['developers'][$key]['value'];
						}
						// сохранение в БД
						if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['cottages_developers'], $info, 'id') or die($db->error);
						} else {
							$res = $db->insertFromArray($sys_tables['cottages_developers'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/estate/cottages/developers/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['developers']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$res = $db->query("DELETE FROM ".$sys_tables['cottages_developers']." WHERE id=?", $id);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			default:
				$module_template = 'admin.developers.list.html';
				// формирование фильтра по названию
				$conditions = [];
                if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
				if(!empty($filters['has_email'])) $filters['has_email']==1 ? $conditions['email'] = "`email` !=''" : $conditions['email'] = "`email` =''";
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['cottages_developers'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/estate/cottages/developers'                  // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
		
				$sql = "SELECT id,title FROM ".$sys_tables['cottages_developers'];
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
    /*************************************************\
    |*  Анализ спроса коттеджных поселков            *|
    \*************************************************/        
    case 'settlements':
        // переопределяем экшн
       $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            /*************************************************\
            |*  Анализ спроса коттеджных поселков            *|
            \*************************************************/   
            case 'country_demand':
            // переопределяем экшн
            $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
            switch($action){   
                case 'add':
                case 'edit':
                    $module_template = 'admin.country_demand.edit.html';
                    $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                    if($action=='add'){
                        // создание болванки новой записи
                        $info = $db->prepareNewRecord($sys_tables['country_demand']);
                        $info['tblock_7'] = '<div><i>Предложенный анализ отражает лишь общие тенденции движения загородного рынка. Заинтересованным девелоперам и инвесторам мы предлагаем подробные маркетинговые исследования о структуре предложения и продаж по конкретным ценовым сегментам, территориям и пр.<br /><br />Телефон для контактов +7 (964) 334-40-28<br />e-mail: <a href="mailto:d.speransky@yandex.ru">d.speransky@yandex.ru</a></i></div>';
                        $info['tblock_2'] = '<table width="100%" border="1" cellpadding="1" cellspacing="1"><tbody><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table>';
                    } else {
                        // получение данных из БД
                        $info = $db->fetch("SELECT *, YEAR(date) as year, MONTH(date) as month
                                            FROM ".$sys_tables['country_demand']." 
                                            WHERE id=?", $id);
                        if($info['month']==1) { 
                            $info['month'] = 12; 
                            $info['year'] =$info['year']-1;
                        } else $info['month'] = $info['month']-1;
                    }
                    // перенос дефолтных (считанных из базы) значений в мэппинг формы
                    foreach($info as $key=>$field){
                        if(!empty($mapping['country_demand'][$key])) $mapping['country_demand'][$key]['value'] = $info[$key];
                    }
                    // получение данных, отправленных из формы
                    $post_parameters = Request::GetParameters(METHOD_POST);
            
                    // если была отправка формы - начинаем обработку
                    if(!empty($post_parameters['submit'])){
                        Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                        // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                        foreach($post_parameters as $key=>$field){
                            if(!empty($mapping['country_demand'][$key])) $mapping['country_demand'][$key]['value'] = $post_parameters[$key];
                        }
                        // проверка значений из формы
                        $errors = Validate::validateParams($post_parameters,$mapping['country_demand']);
                        // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                        foreach($errors as $key=>$value){
                            if(!empty($mapping['country_demand'][$key])) $mapping['country_demand'][$key]['error'] = $value;
                        }
                        // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                        if(empty($errors)) {
                            // подготовка всех значений для сохранения
                            foreach($info as $key=>$field){
                                if(isset($mapping['country_demand'][$key]['value'])) $info[$key] = $mapping['country_demand'][$key]['value'];
                            }
                            // сохранение в БД
                            if($action=='edit'){
                                $res = $db->updateFromArray($sys_tables['country_demand'], $info, 'id') or die($db->error);
                            } else {
                                $res = $db->insertFromArray($sys_tables['country_demand'], $info, 'id');
                                if(!empty($res)){
                                    $new_id = $db->insert_id;
                                    // редирект на редактирование свеженькой страницы
                                    if(!empty($res)) {
                                        header('Location: '.Host::getWebPath('/admin/estate/cottages/settlements/country_demand/edit/'.$new_id.'/'));
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
                    Response::SetArray('data_mapping',$mapping['country_demand']);
                    break;
                case 'del':
                    $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                    $res = $db->query("DELETE FROM ".$sys_tables['country_demand']." WHERE id=?", $id);
                    $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                    if($ajax_mode){
                        $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                        break;
                    }
                default:
                    $module_template = 'admin.country_demand.list.html';
                    // формирование списка
                    $years = $db->fetchall("SELECT DISTINCT(year) FROM ".$sys_tables['country_demand']." ORDER BY year DESC");
                    Response::SetArray('years',$years);
                    $conditions = [];
                    if(!empty($filters['year'])) $conditions['year'] = "`year` = ".$db->real_escape_string($filters['year'])."";
                    // формирование списка для фильтра
                    $condition = implode(" AND ",$conditions);        
                    // создаем пагинатор для списка
                    $paginator = new Paginator($sys_tables['country_demand'], 30, $condition);
                    // get-параметры для ссылок пагинатора
                    $get_in_paginator = [];
                    foreach($get_parameters as $gk=>$gv){
                        if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                    }
                    // ссылка пагинатора
                    $paginator->link_prefix = '/admin/estate/cottages/settlements/country_demand' // модуль
                                              ."/?"                                       // конечный слеш и начало GET-строки
                                              .implode('&',$get_in_paginator)             // GET-строка
                                              .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                    if($paginator->pages_count>0 && $paginator->pages_count<$page){
                        Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                        exit(0);
                    }
            
                    $sql = "SELECT * FROM ".$sys_tables['country_demand'];
                    if(!empty($condition)) $sql .= " WHERE ".$condition;
                    $sql .= " ORDER BY date DESC";
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
            /*************************************************\
            |*  Участники анализ спроса коттеджных поселков  *|
            \*************************************************/   
            case 'country_demand_members':
            // переопределяем экшн
            $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
            switch($action){   
                case 'add':
                case 'edit':
                    $module_template = 'admin.country_demand_members.edit.html';
                    $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                    if($action=='add'){
                        // создание болванки новой записи
                        $info = $db->prepareNewRecord($sys_tables['country_demand_members']);
                    } else {
                        // получение данных из БД
                        $info = $db->fetch("SELECT *
                                            FROM ".$sys_tables['country_demand_members']." 
                                            WHERE id=?", $id);
                    }
                    // перенос дефолтных (считанных из базы) значений в мэппинг формы
                    foreach($info as $key=>$field){
                        if(!empty($mapping['country_demand_members'][$key])) $mapping['country_demand_members'][$key]['value'] = $info[$key];
                    }
                    // получение данных, отправленных из формы
                    $post_parameters = Request::GetParameters(METHOD_POST);
            
                    // если была отправка формы - начинаем обработку
                    if(!empty($post_parameters['submit'])){
                        Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                        // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                        foreach($post_parameters as $key=>$field){
                            if(!empty($mapping['country_demand_members'][$key])) $mapping['country_demand_members'][$key]['value'] = $post_parameters[$key];
                        }
                        // проверка значений из формы
                        $errors = Validate::validateParams($post_parameters,$mapping['country_demand_members']);
                        // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                        foreach($errors as $key=>$value){
                            if(!empty($mapping['country_demand_members'][$key])) $mapping['country_demand_members'][$key]['error'] = $value;
                        }
                        // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                        if(empty($errors)) {
                            // подготовка всех значений для сохранения
                            foreach($info as $key=>$field){
                                if(isset($mapping['country_demand_members'][$key]['value'])) $info[$key] = $mapping['country_demand_members'][$key]['value'];
                            }
                            // сохранение в БД
                            if($action=='edit'){
                                $res = $db->updateFromArray($sys_tables['country_demand_members'], $info, 'id') or die($db->error);
                            } else {
                                $res = $db->insertFromArray($sys_tables['country_demand_members'], $info, 'id');
                                if(!empty($res)){
                                    $new_id = $db->insert_id;
                                    // редирект на редактирование свеженькой страницы
                                    if(!empty($res)) {
                                        header('Location: '.Host::getWebPath('/admin/estate/cottages/settlements/country_demand_members/edit/'.$new_id.'/'));
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
                    Response::SetArray('data_mapping',$mapping['country_demand_members']);
                    break;
                case 'del':
                    $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                    $res = $db->query("DELETE FROM ".$sys_tables['country_demand_members']." WHERE id=?", $id);
                    $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                    if($ajax_mode){
                        $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                        break;
                    }
                default:
                    $module_template = 'admin.country_demand_members.list.html';
                    // формирование списка
                    $years = $db->fetchall("SELECT DISTINCT(year) FROM ".$sys_tables['country_demand_members']." ORDER BY year DESC");
                    Response::SetArray('years',$years);
                    $conditions = [];
                    if(!empty($filters['year'])) $conditions['year'] = "`year` = ".$db->real_escape_string($filters['year'])."";
                    // формирование списка для фильтра
                    $condition = implode(" AND ",$conditions);        
                    // создаем пагинатор для списка
                    $paginator = new Paginator($sys_tables['country_demand_members'], 30, $condition);
                    // get-параметры для ссылок пагинатора
                    $get_in_paginator = [];
                    foreach($get_parameters as $gk=>$gv){
                        if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                    }
                    // ссылка пагинатора
                    $paginator->link_prefix = '/admin/estate/cottages/settlements/country_demand_members' // модуль
                                              ."/?"                                       // конечный слеш и начало GET-строки
                                              .implode('&',$get_in_paginator)             // GET-строка
                                              .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                    if($paginator->pages_count>0 && $paginator->pages_count<$page){
                        Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                        exit(0);
                    }
            
                    $sql = "SELECT * FROM ".$sys_tables['country_demand_members'];
                    if(!empty($condition)) $sql .= " WHERE ".$condition;
                    $sql .= " ORDER BY name";
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
                        
            case 'add':
            case 'edit':
                $module_template = 'admin.settlements.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['analytics_cottage_settlements']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['analytics_cottage_settlements']." 
                                        WHERE id=?", $id) ;
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['settlements'][$key])) $mapping['settlements'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
        
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['settlements'][$key])) $mapping['settlements'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['settlements']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['settlements'][$key])) $mapping['settlements'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['settlements'][$key]['value'])) $info[$key] = $mapping['settlements'][$key]['value'];
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['analytics_cottage_settlements'], $info, 'id') or die($db->error);
                        } else {
                            $res = $db->insertFromArray($sys_tables['analytics_cottage_settlements'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/estate/cottages/settlements/edit/'.$new_id.'/'));
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
                Response::SetArray('data_mapping',$mapping['settlements']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->query("DELETE FROM ".$sys_tables['analytics_cottage_settlements']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            default:
                $module_template = 'admin.settlements.list.html';
                // формирование списка
                $years = $db->fetchall("SELECT DISTINCT(year) FROM ".$sys_tables['analytics_cottage_settlements']." ORDER BY year DESC");
                Response::SetArray('years',$years);
                $conditions = [];
                if(!empty($filters['year'])) $conditions['year'] = "`year` = ".$db->real_escape_string($filters['year'])."";
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);        
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['analytics_cottage_settlements'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/estate/cottages/settlements'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
        
                $sql = "SELECT id,year, month FROM ".$sys_tables['analytics_cottage_settlements'];
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " ORDER BY year DESC, month DESC";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
                break;
        }
        break;        
     /**************************\
    |*  Работа с фотографиями  *|
    \**************************/
    case 'agencies':
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            switch($action){
                case 'list':
                    $search_string = Request::GetString('search_string',METHOD_POST);
                    $list = $db->fetchall("SELECT ".$sys_tables['users'].".id, ".$sys_tables['agencies'].".title FROM
                                            ".$sys_tables['users']."
                                            LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                            WHERE ".$sys_tables['agencies'].".title LIKE '%".$search_string."%'  AND ".$sys_tables['users'].".agency_admin = 1
                                            GROUP BY ".$sys_tables['agencies'].".id
                                            ORDER BY  ".$sys_tables['agencies'].".title
                                            LIMIT 10
                    ");
                    $ajax_result['ok'] = true;
                    if(!empty($list)) $ajax_result['list'] = $list;
                    else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Такое агентство не найдено'));
                    
                break;
            }
        break;

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
						$list = Photos::getList('cottages',$id);
						if(!empty($list)){
							$ajax_result['ok'] = true;
							$ajax_result['list'] = $list;
							$ajax_result['folder'] = Config::$values['img_folders']['cottages'];
						} else $ajax_result['error'] = 'Невозможно построить список фотографий';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
					//загрузка фотографий
					//id текущей новости
					$id = Request::GetInteger('id', METHOD_POST);				

					if(!empty($id)){
                        //removed default sizes
						$res = Photos::Add('cottages',$id,false,false,false,false,false,true);
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
                        $res = Photos::setTitle('cottages',$id, $title);
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
						$res = Photos::Delete('cottages',$id_photo);
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
						$res = Photos::setMain('cottages', $id, $id_photo);
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
						$res = Photos::Sort('cottages', $order);
						if(!empty($res)){
							$ajax_result['ok'] = true;
						} else $ajax_result['error'] = 'Невозможно отсортировать';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;
    /*************************\
    |*  Работа со статитикой *|
    \*************************/
    case 'stats':
        // переопределяем экшн
        $module_template = 'admin.stats.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
        //получение данных по объекту из базы
        $info = $db->fetch("SELECT 
                                `id`,
                                `title`
                            FROM ".$sys_tables['cottages']."
                            WHERE `id` = ?",$id);
        $photo = Photos::getMainPhoto('cottages',$id);                    
        Response::SetString('photo',$cottage_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name']);
        $post_parameters = Request::GetParameters(METHOD_POST);
        // если была отправка формы - выводим данные 
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //передача данных в шаблон
            $date_start = $post_parameters['date_start'];
            $date_end = $post_parameters['date_end'];
            $info['date_start'] = $date_start;
            $info['date_end'] = $date_end;
            $stats = $db->fetchall("
                    SELECT IFNULL(a.show_amount,0) as show_amount, IFNULL(b.click_amount,0) as click_amount, a.date FROM 
                    (
                        (
                            SELECT 
                                SUM(IFNULL(`amount`,0)) as show_amount, 
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date
                            FROM ".$sys_tables['estate_complexes_stats_full_shows']." 
                            WHERE
                                `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND type = 2
                            GROUP BY `date`
                        ) a
                        LEFT JOIN 
                        (
                            SELECT 
                                SUM(IFNULL(`amount`,0)) as click_amount, 
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date
                            FROM ".$sys_tables['estate_complexes_stats_full_clicks']."
                            WHERE
                                `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."  AND type = 2
                            GROUP BY `date`
                        ) b ON a.date = b.date
                    ) UNION (
                        SELECT IFNULL(c.show_amount,0) as show_amount, IFNULL(d.click_amount,0) as click_amount, c.date FROM 
                        (
                            SELECT 
                                IFNULL(COUNT(*),0) as show_amount, 
                                'сегодня' as date,
                                id_parent
                            FROM ".$sys_tables['estate_complexes_stats_day_shows']."  
                            WHERE `id_parent` = ".$id." AND type = 2
                        ) c
                        LEFT JOIN 
                        (
                            SELECT 
                                IFNULL(COUNT(*),0) as click_amount, 
                                'сегодня' as date,
                                id_parent
                            FROM ".$sys_tables['estate_complexes_stats_day_clicks']."  
                            WHERE `id_parent` = ".$id." AND type = 2
                        ) d ON c.id_parent = d.id_parent

                    )
                ");  
            Response::SetArray('stats',$stats); // статистика объекта    
            // общее количество показов/кликов/
        }
        Response::SetArray('info',$info); // информация об объекте                                        
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
        
		$module_template = 'admin.cottages.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['cottages']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['cottages']." 
								WHERE id=?", $id) ;
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['cottages'][$key])) $mapping['cottages'][$key]['value'] = $info[$key];
		}
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
        // формирование дополнительных данных для формы (не из основной таблицы)
        $developers = $db->fetchall("SELECT id,title FROM ".$sys_tables['cottages_developers']." ORDER BY title");
        foreach($developers as $key=>$val){
            $mapping['cottages']['id_developer']['values'][$val['id']] = $val['title'];
        }
        $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
        foreach($managers as $key=>$val){
            $mapping['cottages']['id_manager']['values'][$val['id']] = $val['name'];
        }
        if(!empty($info['id_user'])){
            $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".title FROM
                                    ".$sys_tables['agencies']."
                                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    WHERE ".$sys_tables['users'].".id = ?", $info['id_user']);
            if(!empty($agency)) Response::SetString('agency_title',$agency['title']);
            $agency_seller = $db->fetch("SELECT ".$sys_tables['agencies'].".title FROM
                                    ".$sys_tables['agencies']."
                                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    WHERE ".$sys_tables['users'].".id = ?", $info['id_seller']);
            if(!empty($agency_seller)) Response::SetString('seller_title',$agency_seller['title']);
        }
        
        $district_areas = $db->fetchall("SELECT id,title FROM information.district_areas ORDER BY title");
        foreach($district_areas as $key=>$val){
            $mapping['cottages']['id_district_area']['values'][$val['id']] = $val['title'];
        }
        $directions = $db->fetchall("SELECT id,title FROM information.directions ORDER BY title");
        foreach($directions as $key=>$val){
            $mapping['cottages']['id_direction']['values'][$val['id']] = $val['title'];
        }
        $build_completes = $db->fetchall("SELECT id,title FROM information.build_complete ORDER BY title");
        foreach($build_completes as $key=>$val){
            $mapping['cottages']['id_build_complete']['values'][$val['id']] = $val['title'];
        }
        $stady = $db->fetchall("SELECT id,title FROM ".$sys_tables['cottages_stadies']." ORDER BY id");
        foreach($stady as $key=>$val){
            $mapping['cottages']['id_stady']['values'][$val['id']] = $val['title'];
        }
        $status = $db->fetchall("SELECT id, title FROM ".$sys_tables['union_status']." ORDER BY id");
        foreach($status as $key=>$val) {
            $mapping['cottages']['id_u_status']['values'][$val['id']] = $val['title'];
        }
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['cottages'][$key])) $mapping['cottages'][$key]['value'] = $post_parameters[$key];
			}
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['cottages']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['cottages'][$key])) $mapping['cottages'][$key]['error'] = $value;
			}
            //проверка на похожее название
            if($action == 'add') $cottage_item = $db->fetch("SELECT * FROM ".$sys_tables['cottages']." WHERE title = ?", $mapping['cottages']['title']['value']);
            else if($action == 'edit') $cottage_item = $db->fetch("SELECT * FROM ".$sys_tables['cottages']." WHERE title = ? AND id != ?", $mapping['cottages']['title']['value'], $info['id']);
            if(!empty($cottage_item)) $errors['title'] = $mapping['cottages']['title']['error'] = 'Такое название КП уже существует';
            
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['cottages'][$key]['value'])) $info[$key] = $mapping['cottages'][$key]['value'];
				}
                if(!empty($info['site']) && strstr($info['site'],'http://')=='') $info['site'] = 'http://'.$info['site'];
				// сохранение в БД
                 //дата дообавления объекта
                $info['date_change'] = date("Y-m-d H:i:s");
				if($action=='edit'){
					//статус - отредактирован объект
					$info['object_status'] = 2;
					$res = $db->updateFromArray($sys_tables['cottages'], $info, 'id') or die($db->error);
                    //редирект по нажатию на сохранить+перейти в список поселков
                    $redirect =  Request::GetString('redirect',METHOD_GET);
                    if(!empty($redirect)) {
                        $cookie_page = Cookie::GetString('admin_cottages_page');
                        $cookie_admin_params = Cookie::GetArray('admin_cottages_params');
                        if(!empty($cookie_admin_params)){
                            $params  = [];
                            foreach($cookie_admin_params as $k=>$val) $params[] = $k.'='.$val;
                            Host::Redirect("/admin/estate/cottages/?".implode('&',$params));
                        }
                        elseif(empty($cookie_page)) $cookie_page = 1;
                        Host::Redirect("/admin/estate/cottages/?page=".$cookie_page);
                    }
				} else {
					//дата дообавления объекта
					$info['idate'] = date('Y-m-d');

					$res = $db->insertFromArray($sys_tables['cottages'], $info, 'id');
                    $new_id = $db->insert_id;
                    //обновление ЧПУ
                    $chpu_title = createCHPUTitle($info['title']);
                    $chpu_item = $db->fetch("SELECT * FROM ".$sys_tables['cottages']." WHERE chpu_title = ?", $chpu_title);
                    $db->query("UPDATE ".$sys_tables['cottages']." SET chpu_title = ? WHERE id = ?", $chpu_title.(!empty($chpu_item)?"_".$new_id:""), $new_id);
					if(!empty($res)){
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/estate/cottages/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['cottages']);
        Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
		break;
	case 'del':
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$del_photos = Photos::DeleteAll('cottages',$id);
		$res = $db->query("DELETE FROM ".$sys_tables['cottages']." WHERE id=?", $id);
		$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
		if($ajax_mode){
			$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
			break;
		}
	default:
        $GLOBALS['css_set'][] = '/modules/cottages/style.css';
		$module_template = 'admin.cottages.list.html';
		// формирование списка
        $developers = $db->fetchall("SELECT id,title FROM ".$sys_tables['cottages_developers']." ORDER BY title");
        Response::SetArray('developers',$developers);
        $managers_list = $db->fetchall("SELECT id, name as title FROM ".$sys_tables['managers']." WHERE bsn_manager=1 UNION SELECT 99 as id, 'не проставлен' as name");
        $managers = [];
        foreach($managers_list as  $k=>$item) $managers[$item['id']] = $item['title'];
        Response::SetArray('managers',$managers);
        $stadies = $db->fetchall("SELECT id,title FROM ".$sys_tables['cottages_stadies']." ORDER BY title");
        Response::SetArray('stadies',$stadies);
		$conditions = [];
		if(!empty($filters)){
            if(!empty($filters['title'])) $conditions['title'] = $sys_tables['cottages'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['manager'])) $conditions[] = "`id_manager` = ".($filters['manager']!=99 ? $filters['manager'] : 0);
			if(!empty($filters['seller'])) $conditions[] = "`id_seller` ".($filters['seller']==1 ? '>0' : '=0');
            if(!empty($filters['developer'])) $conditions[] = "`id_developer` ".($filters['developer']==1 ? '>0' : '=0');
			if(!empty($filters['stady'])) $conditions['stady'] = "`id_stady` = ".$db->real_escape_string($filters['stady'])."";
		}
        $get_parameters = Request::GetParameters(METHOD_GET);
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        if(empty($sortby)) $sortby = 1;
        $orderby = "";
        switch($sortby){
            case 4: 
                // по дате изменения
                $orderby .= $sys_tables['cottages'].".date_change DESC"; 
                break;
            case 3: 
                // по дате изменения убывание
                $orderby .= $sys_tables['cottages'].".date_change ASC"; 
                break;
            case 2: 
                // по дате по возрастанию
                $orderby .= $sys_tables['cottages'].".`id` ASC"; 
                break;
            case 1: 
            default: 
                // по дате по убыванию
                $orderby .= $sys_tables['cottages'].".advanced, ".$sys_tables['cottages'].".`id` DESC"; 
                break;
        }
        unset($get_parameters['path']);
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['cottages'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = [];
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
        $url_params = parse_url($this_page->requested_url);
        $paginator_link_base = '/'.$this_page->requested_path.'/?'.(!empty($url_params['query'])?''.$url_params['query'].'&':'');
        $paginator->link_prefix = $paginator_link_base.(!empty($sortby)?'sortby='.$sortby.'&':'').'page=';
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}
        Response::SetString('sorting_url', $paginator_link_base.'page='.$page.'&sortby=');
        Response::SetInteger('sortby', $sortby);

        
        $sql = "SELECT 
                        cottage.*,   
                        developer.title as expert_name, 
                        district.title as district_name,                     
                        IFNULL(a.cnt_day,0) as cnt_day,
                        IFNULL(e.cnt_full_last_days,0) as cnt_full_last_days,
                        IFNULL(c.cnt_click_day,0) as cnt_click_day,
                        IFNULL(f.cnt_click_full_last_days,0) as cnt_click_full_last_days
                  FROM ".$sys_tables['cottages']." cottage";
        $sql .= " LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$sys_tables['estate_complexes_stats_day_shows']." WHERE type = 2 GROUP BY id_parent) a ON a.id_parent = cottage.id";        
        $sql .= " LEFT JOIN (SELECT COUNT(*) as cnt_click_day, id_parent FROM ".$sys_tables['estate_complexes_stats_day_clicks']." WHERE type = 2 GROUP BY id_parent) c ON c.id_parent = cottage.id";        
        $sql .= " LEFT JOIN (SELECT AVG(amount) as cnt_full_last_days, id_parent FROM ".$sys_tables['estate_complexes_stats_full_shows']." WHERE type = 2 AND date > CURDATE() - INTERVAL 30  DAY AND date <= CURDATE() - INTERVAL 1 DAY GROUP BY id_parent) e ON e.id_parent = cottage.id";        
        $sql .= " LEFT JOIN (SELECT AVG(amount) as cnt_click_full_last_days, id_parent FROM ".$sys_tables['estate_complexes_stats_full_clicks']." WHERE type = 2 AND date > CURDATE() - INTERVAL 30  DAY AND date <= CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) f ON f.id_parent = cottage.id";        
        $sql .= " LEFT JOIN   information.district_areas district ON district.id=cottage.id_district_area";
        $sql .= " LEFT JOIN  ".$sys_tables['cottages_developers']." developer ON developer.id=cottage.id_developer";
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY ".$orderby;
        $sql .= " LIMIT ".$paginator->getLimitString($page); 
                
		$list = $db->fetchall($sql);
		// определение главной фотки для поселка
		foreach($list as $key=>$value){
			$photo = Photos::getMainPhoto('cottages',$value['id']);
			if(!empty($photo)) {
				$list[$key]['photo'] = $cottage_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
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