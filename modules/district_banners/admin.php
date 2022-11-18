<?php
$GLOBALS['js_set'][] = '/modules/district_banners/ajax_actions.js';

require_once('includes/class.paginator.php');

// таблицы модуля
$sys_tables = Config::$sys_tables;
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Спонсор района'));

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['district'] = $db->real_escape_string(Request::GetInteger('f_district',METHOD_GET));
$filters['status'] = Request::GetString('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}                                                                                                                        
if(!empty($filters['district'])) $get_parameters['f_district'] = $filters['district']; 
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
		$GLOBALS['js_set'][] = '/modules/district_banners/datepick_actions.js';
		//получение данных по объекту из базы
		$info = $db->fetch("SELECT 
								`id`,
								`title`,
								IF(img_link='',
									  CONCAT_WS('/','".Config::$values['img_folders']['district_banners']."',`img_src`),
								`img_link`) as photo
							FROM ".$sys_tables['district_banners']."
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
					  FROM ".$sys_tables['district_banners_stats_full_shows']."
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
					  FROM ".$sys_tables['district_banners_stats_full_clicks']."
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
    |*  Работа с баннерами Спонсор района  *|
    \****************************/		
	case 'add':
	case 'edit':
		$module_template = 'admin.banners.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['district_banners']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['district_banners']." 
								WHERE id=?", $id) ;
			//предустановка ссылки на картинки на главной и в шапке
			$cnt = count($_POST);
			Response::SetString('img_link_double', $cnt==0?$info['img_link']:$_POST['img_link_double']); 					
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['value'] = $info[$key];
		}
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
		// формирование дополнительных данных для формы (не из основной таблицы)
		$districts = $db->fetchall("SELECT id,title FROM ".$sys_tables['districts']." 
                                    WHERE id NOT IN (
                                        SELECT id_district as id FROM ".$sys_tables['district_banners']." WHERE enabled = 1 AND published = 1 ".(!empty($id)?' AND id!='.$id:'')."
                                        )
                                    ORDER BY title");
		foreach($districts as $key=>$val){
			$mapping['banners']['id_district']['values'][$val['id']] = $val['title'];
		}	
        $mapping['banners']['id_district']['values'][47] = 'Лен.область';
        			
		Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
		//папки для картинок спецпредложений
		Response::SetString('img_folder', Config::$values['img_folders']['district_banners']); // папка для Спонсор района
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['banners']);
			// замена фотографий Спонсор района
			if(!empty($_FILES)){
				foreach ($_FILES as $fname => $data){
					if ($data['error']==0) {
                        $size = getimagesize($data['tmp_name']);
                        if($size[0]!=520 && $size[1]!=60) $mapping['banners']['img_src']['error'] = 'Размер файла должен быть 520x60px. Размер вашего файла'.$size[0].'x'.$size[1].'px';
                        else{
                                
                            $_folder = Host::$root_path.'/'.Config::$values['img_folders']['district_banners'].'/'; // папка для файлов  Спонсор района
						    $_temp_folder = Host::$root_path.'/img/uploads/'; // папка для файлов  Спонсор района
						    $fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
						    $fileParts = pathinfo($data['name']);
						    $targetExt = $fileParts['extension'];
						    $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
						    if (in_array(strtolower($targetExt),$fileTypes)) {
							    move_uploaded_file($data['tmp_name'],$_temp_folder.$_targetFile);
                                copy($_temp_folder.$_targetFile,$_folder.$_targetFile);
                                if(file_exists($_temp_folder.$_targetFile) && is_file($_temp_folder.$_targetFile)) unlink($_temp_folder.$_targetFile);
							    if(file_exists($_folder.$mapping['banners'][$fname]['value']) && is_file($_folder.$mapping['banners'][$fname]['value'])) unlink($_folder.$mapping['banners'][$fname]['value']);
							    $post_parameters[$fname] = $_targetFile;
						    }
                        }
					}
				}
			}
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['value'] = $post_parameters[$key];
			}

			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['error'] = $value;
			}
		
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['banners'][$key]['value'])) $info[$key] = $mapping['banners'][$key]['value'];
				}
				//переопределение ссылок на картинку на главной и на картинку в шапке
				$info['img_link'] = !empty($post_parameters['img_link_double']) ? $post_parameters['img_link_double'] : '';
				// сохранение в БД
				if($action=='edit'){
					if( date('Y-m-d') >= date($info['date_start']) && date('Y-m-d') < date($info['date_end']) && $info['published']==2) $info['published']=1;
                    //статус - отредактирован объект
					$res = $db->updateFromArray($sys_tables['district_banners'], $info, 'id') or die($db->error);
				} else {
					//дата дообавления объекта
					$res = $db->insertFromArray($sys_tables['district_banners'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/advert_objects/district_banners/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['banners']);
		break;
	case 'restore':
	case 'archive':
		$id =  empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		//значение чекбокса
		$value = Request::GetString('value',METHOD_POST);
		$status = $action=='restore'?1:3;
		if($id>0){
			$res = $db->querys("UPDATE ".$sys_tables['district_banners']." SET `published` = ? WHERE id=?", $status, $id) or die($db->error);
			$results['setStatus'] = ($res && $db->affected_rows) ? $id : -1;
			if($ajax_mode){
				$ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
				break;
			}
		} else $ajax_result = false;
		break;				
    case 'setStatus':
        //установка флагов для объектов
         $id = Request::GetInteger('id',METHOD_POST);
        //значение чекбокса
        $value = Request::GetString('value',METHOD_POST);
         $status = $value == 'checked'?1:2;
        if($id>0){
            $res = $db->querys("UPDATE ".$sys_tables['district_banners']." SET `enabled` = ? WHERE id=?", $status, $id);
            $results['setStatus'] = $db->affected_rows>0 ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
                break;
            }
        } else $ajax_result = false;
        break;
	default:
		$module_template = 'admin.banners.list.html';
		//кол-во эл-ов в каждом блоке размещения
		 $sql_where  = !empty($filters['district'])?" AND `id_district` = ".$filters['district']." ":"";
		 $sql = "
			SELECT  
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['district_banners']." WHERE `published` !=3 $sql_where) AS alls,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['district_banners']." WHERE `published` = 1 and enabled = 1 $sql_where) AS active,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['district_banners']." WHERE `published` = 3 $sql_where) AS archive
			FROM dual";
		$counts = $db->fetch($sql) or die($sql.$db->error);
		Response::SetArray('statuses',array(
											'active'	=>	'Активные - '.$counts['active'],
											'alls'		=>	'Все - '.$counts['alls'],
											'archive'	=>	'В архиве - '.$counts['archive']
											));
		// формирование дополнительных данных для формы (не из основной таблицы)
		$districts = $db->fetchall("SELECT id,title FROM ".$sys_tables['districts']." ORDER BY id");
		Response::SetArray('districts',$districts);												
		$conditions = array();
		if(!empty($filters)){
			if(!empty($filters['district'])) $conditions['district'] = 'id_district = '.$db->real_escape_string($filters['district']);
			switch($filters['status']){
				case  'active'	: $conditions['status'] = '`published` = 1 and enabled = 1';    break;
				case  'alls'	: $conditions['status'] = '`published` !=3'; 	break;
				case  'archive'	: $conditions['status'] = '`published` = 3'; 	break;
			}
		} 
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['district_banners'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = array();
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/advert_objects/district_banners'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

		$sql = "SELECT 
                        ban.*, 
						IF(ban.id_district=47,'Лен.обл',".$sys_tables['districts'].".title) as district_title, 
						IF(ban.img_link='',
							  CONCAT_WS('/','".Config::$values['img_folders']['district_banners']."',`ban`.`img_src`),
						`ban`.`img_link`) as photo,
                        `ban`.`img_src`,
						DATE_FORMAT(`ban`.`date_start`,'%d.%m.%Y') as `date_start`,
						DATE_FORMAT(`ban`.`date_end`,'%d.%m.%Y') as `date_end`,
						IF(`ban`.`date_start`<=NOW() AND `ban`.`date_end`>=NOW(), 'true', 'false') as `compare`,							
						IFNULL(a.cnt_day,0) as cnt_day,
						IFNULL(b.cnt_full,0) as cnt_full,
						IFNULL(c.cnt_click_day,0) as cnt_click_day,
						IFNULL(d.cnt_click_full,0) as cnt_click_full
				FROM ".$sys_tables['district_banners']." ban
		        LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$sys_tables['district_banners_stats_day_shows']." GROUP BY id_parent) a ON a.id_parent = ban.id	
		        LEFT JOIN (SELECT SUM(amount) as cnt_full, id_parent FROM ".$sys_tables['district_banners_stats_full_shows']." GROUP BY id_parent) b ON b.id_parent = ban.id	
		        LEFT JOIN (SELECT COUNT(*) as cnt_click_day, id_parent FROM ".$sys_tables['district_banners_stats_day_clicks']." GROUP BY id_parent) c ON c.id_parent = ban.id		
                LEFT JOIN (SELECT SUM(amount) as cnt_click_full, id_parent FROM ".$sys_tables['district_banners_stats_full_clicks']." GROUP BY id_parent) d ON d.id_parent = ban.id       
		        LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ban.id_district";		
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