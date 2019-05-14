<?php
$GLOBALS['js_set'][] = '/modules/markers/ajax_actions.js';

require_once('includes/class.paginator.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Метки'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['status'] = Request::GetString('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}                                                                                                                        
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; else $filters['status'] = 'active';

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];



// обработка action-ов
switch($action){
	/*************************\
    |*  Работа со статитикой *|
    \*************************/
    case 'stats':
        // переопределяем экшн
		$module_template = 'admin.stats.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
		$GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
		$GLOBALS['js_set'][] = '/modules/markers/datepick_actions.js';
		//получение данных по объекту из базы
		$info = $db->fetch("SELECT 
								`id`,
								`title`
							FROM ".$sys_tables['markers']."
							WHERE `id` = ?",$id);
					
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
					  SELECT 
						  SUM(IFNULL(`amount`,0)) as show_amount, 
						  DATE_FORMAT(`date`,'%d.%m.%Y') as date
					  FROM ".$sys_tables['markers_stats_show_full']."
					  WHERE
						  `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
						  `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
					  GROUP BY `date`
					) a
					LEFT JOIN 
					(
					  SELECT 
						  SUM(IFNULL(`amount`,0)) as click_amount, 
						  DATE_FORMAT(`date`,'%d.%m.%Y') as date
					  FROM ".$sys_tables['markers_stats_click_full']."
					  WHERE
						  `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
						  `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
					  GROUP BY `date`
					 ) b ON a.date = b.date
				");
			Response::SetArray('stats',$stats); // статистика объекта	
			// общее количество показов/кликов/
		}
		Response::SetArray('info',$info); // информация об объекте										
		break;
	/****************************\
    |*  Работа с Метками  *|
    \****************************/		
	case 'add':
	case 'edit':
		$module_template = 'admin.markers.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['markers']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['markers']." 
								WHERE id=?", $id) ;
			//предустановка ссылки на картинки на главной и в шапке
			$cnt = count($_POST);
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['markers'][$key])) $mapping['markers'][$key]['value'] = $info[$key];
		}
        
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
		
		Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['markers']);
			
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['markers'][$key])) $mapping['markers'][$key]['value'] = $post_parameters[$key];
			}

			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['markers'][$key])) $mapping['markers'][$key]['error'] = $value;
			}
		
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['markers'][$key]['value'])) $info[$key] = $mapping['markers'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
					if( date('Y-m-d') >= date($info['date_start']) && date('Y-m-d') < date($info['date_end']) && $info['published']==2) $info['published']=1;
                    //статус - отредактирован объект
					$res = $db->updateFromArray($sys_tables['markers'], $info, 'id') or die($db->error);
				} else {
					//дата дообавления объекта
					$res = $db->insertFromArray($sys_tables['markers'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/service/markers/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['markers']);
		break;
    case 'setStatus':
        //установка флагов для объектов
         $id = Request::GetInteger('id',METHOD_POST);
        //значение чекбокса
        $value = Request::GetString('value',METHOD_POST);
         $status = $value == 'checked'?1:2;
        if($id>0){
            $res = $db->query("UPDATE ".$sys_tables['markers']." SET `enabled` = ? WHERE id=?", $status, $id);
            $results['setStatus'] = $db->affected_rows>0 ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
                break;
            }
        } else $ajax_result = false;
        break;
	default:
		$module_template = 'admin.markers.list.html';
		//кол-во эл-ов в каждом блоке размещения
		 $sql = "
			SELECT  
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['markers']." WHERE `enabled` = 1) AS active,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['markers']." WHERE `enabled` = 2) AS noneactive
			FROM dual";
		$counts = $db->fetch($sql) or die($sql.$db->error);
		Response::SetArray('statuses',array(
											'active'	=>	'Активные - '.$counts['active'],
											'noneactive'=>	'Неактивные - '.$counts['noneactive']
											));
        $conditions = [];
		if(!empty($filters)){
			switch($filters['status']){
				case  'active'	: $conditions['status'] = 'enabled = 1';    break;
				case  'noneactive'	: $conditions['noneactive'] = 'enabled=2'; 	break;
			}
		} 
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['markers'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = [];
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/service/markers'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

		$sql = "SELECT 
                        ban.*, 
						IFNULL(a.cnt_day,0) as cnt_day,
						IFNULL(b.cnt_full,0) as cnt_full,
						IFNULL(c.cnt_click_day,0) as cnt_click_day,
						IFNULL(d.cnt_click_full,0) as cnt_click_full
				FROM ".$sys_tables['markers']." ban
		        LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$sys_tables['markers_stats_show_day']." GROUP BY id_parent) a ON a.id_parent = ban.id	
		        LEFT JOIN (SELECT SUM(amount) as cnt_full, id_parent FROM ".$sys_tables['markers_stats_show_full']." GROUP BY id_parent) b ON b.id_parent = ban.id	
		        LEFT JOIN (SELECT COUNT(*) as cnt_click_day, id_parent FROM ".$sys_tables['markers_stats_click_day']." GROUP BY id_parent) c ON c.id_parent = ban.id		
                LEFT JOIN (SELECT SUM(amount) as cnt_click_full, id_parent FROM ".$sys_tables['markers_stats_click_full']." GROUP BY id_parent) d ON d.id_parent = ban.id       
		        ";		
		if(!empty($condition)) $sql .= " WHERE ".$condition;
		$sql .= " ORDER BY ban.id";
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