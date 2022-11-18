<?php

$GLOBALS['js_set'][] = '/modules/promotions/ajax_actions.js';
$GLOBALS['css_set'][] = '/css/autocomplete.css';
$GLOBALS['js_set'][] = '/modules/promotions/admin.autocomplette.js';
$GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
$GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
$GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.promotions.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Акции'));

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['estate_complex_type'] = Request::GetInteger('f_estate_complex_type',METHOD_GET);
$filters['estate_type'] = Request::GetInteger('f_estate_type',METHOD_GET);
$filters['published'] = Request::GetInteger('f_published',METHOD_GET);
$filters['agency'] = Request::GetInteger('f_agency',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['estate_complex_type'])) $get_parameters['f_estate_complex_type'] = $filters['estate_complex_type']; 
if(!empty($filters['estate_type'])) $get_parameters['f_estate_type'] = $filters['estate_type']; 
if(!empty($filters['published'])) $get_parameters['f_published'] = $filters['published']; 
if(!empty($filters['agency'])) $get_parameters['f_agency'] = $filters['agency']; 
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
   
    case 'business_centers_titles':
    case 'housing_estates_titles':
    case 'cottages_titles':
        $search_string = Request::GetString('search_string',METHOD_POST);
        $table = str_replace('_titles', '', $action);
        if($action == 'cottages_titles'){
            $list = $db->fetchall("SELECT 
                                        ".$sys_tables[$table].".*
                                        , TRIM(BOTH 'x' FROM txt_addr) as txt_addr
                                        , ".$sys_tables['district_areas'].".title as district_area_title 
                                    FROM ".$sys_tables[$table]."
                                    LEFT JOIN ".$sys_tables['district_areas']." ON ".$sys_tables['district_areas'].".id = ".$sys_tables[$table].".id_district_area
                                    WHERE ".$sys_tables[$table].".title LIKE '%".$search_string."%' AND published = 1
                                    GROUP BY ".$sys_tables[$table].".id
                                    ORDER BY  ".$sys_tables[$table].".title
                                    LIMIT 10
            ");
        } else {
            $list = $db->fetchall("SELECT 
                                        ".$sys_tables[$table].".*
                                        , TRIM(BOTH ',' FROM txt_addr) as txt_addr
                                        , ".$sys_tables['subways'].".title as subway_title 
                                        , ".$sys_tables['way_types'].".title as way_type_title 
                                        , ".$sys_tables['districts'].".title as district_title 
                                    FROM ".$sys_tables[$table]."
                                    LEFT JOIN ".$sys_tables['subways']." ON ".$sys_tables['subways'].".id = ".$sys_tables[$table].".id_subway
                                    LEFT JOIN ".$sys_tables['way_types']." ON ".$sys_tables['way_types'].".id = ".$sys_tables[$table].".id_way_type
                                    LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables[$table].".id_district
                                    WHERE ".$sys_tables[$table].".title LIKE '%".$search_string."%' AND published = 1
                                    GROUP BY ".$sys_tables[$table].".id
                                    ORDER BY  ".$sys_tables[$table].".title
                                    LIMIT 10
            ");
            
        }
        $ajax_result['ok'] = true;
        if(!empty($list)) $ajax_result['list'] = $list;
        else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Такой комплекс найден'));
        break;           
    case 'agencies_titles':
        $search_string = Request::GetString('search_string',METHOD_POST);
        $list = $db->fetchall("SELECT ".$sys_tables['users'].".id, ".$sys_tables['agencies'].".title 
                                FROM
                                ".$sys_tables['agencies']."
                                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                WHERE ".$sys_tables['agencies'].".title LIKE '%".$search_string."%' AND ".$sys_tables['users'].".agency_admin = 1
                                GROUP BY ".$sys_tables['agencies'].".id
                                ORDER BY ".$sys_tables['agencies'].".title
        ");
        $ajax_result['ok'] = true;
        if(!empty($list)) $ajax_result['list'] = $list;
        else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Агентство не найдено'));
        break;    
    case 'districts_titles':
        $search_string = Request::GetString('search_string',METHOD_POST);
        $list = $db->fetchall("SELECT *, title as district_title 
                                FROM
                                ".$sys_tables['districts']."
                                WHERE ".$sys_tables['districts'].".title LIKE '%".$search_string."%'
                                ORDER BY ".$sys_tables['districts'].".title
        ");
        $ajax_result['ok'] = true;
        if(!empty($list)) $ajax_result['list'] = $list;
        else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Район не найден'));
        break;    
    case 'district_areas_titles':
        $search_string = Request::GetString('search_string',METHOD_POST);
        $list = $db->fetchall("SELECT *, title as district_area_title 
                                FROM
                                ".$sys_tables['district_areas']."
                                WHERE ".$sys_tables['district_areas'].".title LIKE '%".$search_string."%'
                                ORDER BY ".$sys_tables['district_areas'].".title
        ");
        $ajax_result['ok'] = true;
        if(!empty($list)) $ajax_result['list'] = $list;
        else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Район не найден'));
        break;    
    case 'subways_titles':
        $search_string = Request::GetString('search_string',METHOD_POST);
        $list = $db->fetchall("SELECT *, title as subway_title
                                FROM
                                ".$sys_tables['subways']."
                                WHERE ".$sys_tables['subways'].".title LIKE '%".$search_string."%' AND parent_id = 34142
                                ORDER BY ".$sys_tables['subways'].".title
        ");
        $ajax_result['ok'] = true;
        if(!empty($list)) $ajax_result['list'] = $list;
        else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Метро не найдено'));
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
                        $list = Photos::getList('promotions',$id);
                        if(!empty($list)){
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                            $ajax_result['folder'] = Config::$values['img_folders']['basic'];
                        } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    //загрузка фотографий
                    //id текущей новости
                    Photos::$__folder_options=array(
                        'sm'=>array(360,270,'cut',85)
                    );// свойства папок для загрузки и формата фотографий
                    $id = Request::GetInteger('id', METHOD_POST);                
                    if(!empty($id)){
                        //default sizes removed 370x260
                        $res = Photos::Add('promotions', $id, false, false, false, false, false, true);
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
                case 'del':
                    //удаление фото
                    //id фотки
                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                    if(!empty($id_photo)){
                        $res = Photos::Delete('promotions',$id_photo);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;        
        /**********************\
        |*  объекты           *|
        \**********************/        
        case 'objects':
            // определяем запрошенный экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];

            switch($action){
                /**************************\
                |*  очистить все объекты  *|
                \**************************/        
                case 'clear':
                    $id_parent = Request::GetInteger('id_parent', METHOD_POST);
                    if(!empty($id_parent)){
                        $promotion = new Promotion();
                        $item = $promotion->getItem($sys_tables['promotions'].'id = '.$id_parent);
                        switch($item['id_estate_type']){
                            case 1: $table = 'live'; break;
                            case 2: $table = 'build';  break;
                            case 3: $table = 'commercial';   break;
                            case 4: $table = 'country';  break;
                        }
                        if(!empty($table) && !empty($id)){
                            $res = $db->querys("UPDATE ".$sys_tables[$table]." SET status = ?, status_date_end = '0000-00-00', id_promotion = 0 WHERE id_promotion = ?", 2, $id_parent);
                            $ajax_result['ok'] = $res;
                        }
                    }
                    break;
                /************************\
                |*  удалить объект       *|
                \************************/        
                case 'delete':
                    $id = Request::GetInteger('id', METHOD_POST);
                    $id_parent = Request::GetInteger('id_parent', METHOD_POST);
                    $promotion = new Promotion();
                    $item = $promotion->getItem($sys_tables['promotions'].'.id = '.$id_parent);
                    switch($item['id_estate_type']){
                        case 1: $table = 'live'; break;
                        case 2: $table = 'build';  break;
                        case 3: $table = 'commercial';   break;
                        case 4: $table = 'country';  break;
                    }
                    if(!empty($table) && !empty($id)){
                        $res = $db->querys("UPDATE ".$sys_tables[$table]." SET status = ?, status_date_end = '0000-00-00', id_promotion = 0 WHERE id = ?", 2, $id);
                        $ajax_result['ok'] = $res;
                    }
                    break;
                default:
                    $post_parameters = Request::GetParameters(METHOD_POST);
                    $id = Request::GetInteger('id', METHOD_POST);
                    $id_parent = Request::GetInteger('id_parent', METHOD_POST);
                    $id_object = Request::GetInteger('id_object', METHOD_POST);
                    $id_object_status = isset($post_parameters['id_object']);
                    if(!empty($id_parent) && (!empty($id_object_status))){
                        $item = $db->fetch("SELECT * FROM ".$sys_tables['promotions']." WHERE id = ?", $id_parent);
                        if(!empty($item)) { 
                            if(!empty($item['id_estate_type'])){
                                switch($item['id_estate_type']){
                                    case 1: $table = 'live';  $field = 'id_housing_estate'; break;
                                    case 2: $table = 'build';  $field = 'id_housing_estate'; break;
                                    case 3: $table = 'commercial';  $field = 'id_business_center'; break;
                                    case 4: $table = 'country';  $field = 'id_cottage'; break;
                                }
                            }
                            if(!empty($item['estate_complex_type']) && !empty($item['id_estate_complex'])){                            
                                //проверка на принадлежность объекта комплексу
                                $complex = $db->fetch("SELECT * FROM ".$sys_tables[$table]." WHERE id = ? AND ".$field."=?", $id_object, $item['id_estate_complex']);
                                //объект не прикреплен к комплексу
                                if( (empty($complex) || $complex['published'] == 2) && empty($complex)) $ajax_result['wrong_complex'] = true;
                                //прикреплен
                                else if(!empty($complex)) $db->querys("UPDATE ".$sys_tables[$table]." SET status = ?, status_date_end = CURDATE() + INTERVAL 30 DAY, date_change = CURDATE(), published = 1, id_promotion = ? WHERE id = ?", 7, $id_parent, $id_object);
                            //проверка на существование объекта    
                            } else {
                               $object = $db->fetch("SELECT * FROM ".$sys_tables[$table]." WHERE id = ?", $id_object); 
                                //объект не найден
                                if( empty($object)) $ajax_result['wrong_object'] = true;
                                //объект найден
                                else $db->querys("UPDATE ".$sys_tables[$table]." SET status = ?, status_date_end = CURDATE() + INTERVAL 30 DAY, date_change = CURDATE(), published = 1, id_promotion = ? WHERE id = ?", 7, $id_parent, $id_object);
                            }
                        } else $ajax_result['wrong_promotion'] = true;
                            
                        $ajax_result['ok'] = true;
                    }
                    break;       
            }
            break;        
    /****************************\
    |*  Работа с Акциями        *|
    \***************************/		
	case 'add':
	case 'edit':
        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
		$module_template = 'admin.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['promotions']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['promotions']." 
								WHERE id=?", $id) ;
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['promotions'][$key])) $mapping['promotions'][$key]['value'] = $info[$key];
		}
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
        $way_types = $db->fetchall("SELECT id, title FROM ".$sys_tables['way_types']." ORDER BY title");
        foreach($way_types as $key=>$val){
            $mapping['promotions']['id_way_type']['values'][$val['id']] = $val['title'];
        }
        $estate_types = $db->fetchall("SELECT id, title FROM ".$sys_tables['estate_types']." WHERE id <=4  ORDER BY id");
        foreach($estate_types as $key=>$val){
            $mapping['promotions']['id_estate_type']['values'][$val['id']] = $val['title'];
        }
        // формирование дополнительных данных для формы (не из основной таблицы)
       if( (!empty($mapping['promotions']['id_estate_complex']['value']) || !empty($post_parameters['id_estate_complex']) ) && (!empty($mapping['promotions']['estate_complex_type']['value']) || !empty($post_parameters['estate_complex_type']) ) ){
            $estate_complex_title = !empty($post_parameters['estate_complex_type']) ? $post_parameters['estate_complex_type'] : ( !empty($mapping['promotions']['estate_complex_type']['value']) ? $mapping['promotions']['estate_complex_type']['value'] : $info['estate_complex_type'] );
            $id_estate_complex = !empty($post_parameters['id_estate_complex']) ? $post_parameters['id_estate_complex'] : ( !empty($mapping['promotions']['id_estate_complex']['value']) ? $mapping['promotions']['id_estate_complex']['value'] : $info['id_estate_complex'] );
            switch($estate_complex_title){
                case 1: $table = 'housing_estates'; break;
                case 2: $table = 'cottages'; break;
                case 3: $table = 'business_centers'; break;
            }
            $complex = $db->fetch("SELECT title FROM ".$sys_tables[$table]." WHERE id = ?", $id_estate_complex);
            $post_parameters['estate_complex_title'] = $mapping['promotions']['estate_complex_title']['value'] = $complex['title'];
             if( count($post_parameters) > 10 && $post_parameters['id_estate_complex']!=$mapping['promotions']['id_estate_complex']['value']){
                 //удаление объектов при смене комплекса
                switch($info['id_estate_type']){
                    case 1: $table = 'live'; break;
                    case 2: $table = 'build';  break;
                    case 3: $table = 'commercial';   break;
                    case 4: $table = 'country';  break;
                }
                if(!empty($table) && !empty($info['id'])){
                    $res = $db->querys("UPDATE ".$sys_tables[$table]." SET status = ?, status_date_end = '0000-00-00', id_promotion = 0 WHERE id_promotion = ?", 2, $info['id']);
                    $ajax_result['ok'] = $res;
                }
             }
        }
        // формирование дополнительных данных для формы (не из основной таблицы)
        if( count($post_parameters) > 10 &&  !empty($post_parameters['id_estate_type']) && !empty($mapping['promotions']['id_estate_type']['value']) && $mapping['promotions']['id_estate_type']['value'] != $post_parameters['id_estate_type']) {
            $id_estate_type = !empty($post_parameters['id_estate_type']) ? $post_parameters['id_estate_type'] : ( !empty($mapping['promotions']['id_estate_type']['value']) ? $mapping['promotions']['id_estate_type']['value'] : $info['id_estate_type'] );
            //удаление объектов при смене типа недвижимости
            switch($info['id_estate_type']){
                case 1: $table = 'live'; break;
                case 2: $table = 'build';  break;
                case 3: $table = 'commercial';   break;
                case 4: $table = 'country';  break;
            }
            if(!empty($table) && !empty($info['id'])){
                $res = $db->querys("UPDATE ".$sys_tables[$table]." SET status = ?, status_date_end = '0000-00-00', id_promotion = 0 WHERE id_promotion = ?", 2, $info['id']);
                $ajax_result['ok'] = $res;
            }
        }
        
        if( (!empty($mapping['promotions']['id_user']['value']) || !empty($post_parameters['id_user']) ) ) { 
            $id_user = !empty($post_parameters['id_user']) ? $post_parameters['id_user'] : ( !empty($mapping['promotions']['id_user']['value']) ? $mapping['promotions']['id_user']['value'] : $info['id_user'] );
            $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".title 
                                    FROM ".$sys_tables['agencies']."
                                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    WHERE ".$sys_tables['users'].".id = ?
                                    GROUP BY ".$sys_tables['agencies'].".id
                                    ORDER BY ".$sys_tables['agencies'].".title
            ", $id_user);
            $post_parameters['agency_title'] = $mapping['promotions']['agency_title']['value'] = $agency['title'];
        }
        $sprav_list = array(
            'id_subway' => 'subway',
            'id_district' => 'district',
            'id_district_area' => 'district_area'
        );
        foreach($sprav_list as $field => $table){
            if( (!empty($mapping['promotions'][$field]['value']) || !empty($post_parameters[$field]) ) ) { 
                $value = !empty($post_parameters[$field]) ? $post_parameters[$field] : ( !empty($mapping['promotions'][$field]['value']) ? $mapping['promotions'][$field]['value'] : $info[$field] );
                $item = $db->fetch("SELECT * 
                                        FROM ".$sys_tables[$table.'s']."
                                        WHERE id = ?
                                        ORDER BY title
                ", $value);
                $post_parameters[$table.'_title'] = $mapping['promotions'][$table.'_title']['value'] = $item['title'];
            }
            
        }
        
        // если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['promotions'][$key])) $mapping['promotions'][$key]['value'] = $post_parameters[$key];
			}
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['promotions']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['promotions'][$key])) $mapping['promotions'][$key]['error'] = $value;
			}
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['promotions'][$key]['value'])) $info[$key] = $mapping['promotions'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
					$res = $db->updateFromArray($sys_tables['promotions'], $info, 'id') or die($db->error);
				} else {
					$res = $db->insertFromArray($sys_tables['promotions'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
                        
                        //Матвей:формирование ЧПУ-строки
                        $db->querys( "UPDATE ".$sys_tables['promotions']." SET `chpu_title` = ? WHERE `id` = ?", $new_id.'_'.createCHPUTitle($info['title']), $new_id);
                        //Матвей:end
                        
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/access/promotions/edit/'.$new_id.'/'));
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
        //объекты
        if(!empty($mapping['promotions']['id']['value']) && !empty($mapping['promotions']['id_estate_type']['value'])){
            $estate_type = $db->fetch("SELECT `type` FROM ".$sys_tables['estate_types']." WHERE id = ?", $mapping['promotions']['id_estate_type']['value']);
            $objects = $db->fetchall( "SELECT id FROM ".$sys_tables[$estate_type['type']]." WHERE published = 1 AND id_promotion = ?", false, $mapping['promotions']['id']['value']);
            Response::SetArray('objects', $objects);
        }
		// запись данных для отображения на странице
		Response::SetArray('data_mapping',$mapping['promotions']);
		break;
	case 'del':
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        
        //открепляем от акции прикрепленные объекты
        $promotion_estate_type = $db->fetch("SELECT 
                                                CASE
                                                   WHEN ".$sys_tables['promotions'].".id_estate_type = 1 THEN 'live'
                                                   WHEN ".$sys_tables['promotions'].".id_estate_type = 2 THEN 'build'
                                                   WHEN ".$sys_tables['promotions'].".id_estate_type = 3 THEN 'commercial'
                                                   WHEN ".$sys_tables['promotions'].".id_estate_type = 4 THEN 'country'
                                                END AS estate_type FROM ".$sys_tables['promotions']." WHERE id = ?",$id);
        if(empty($promotion_estate_type)){
            $ajax_result = array('ok' => false);
            break;
        }else $promotion_estate_type = $promotion_estate_type['estate_type'];
        $db->querys("UPDATE ".$sys_tables[$promotion_estate_type]." SET id_promotion = 0, status = 2, status_date_end = '0000-00-00' WHERE id_promotion = ?",$id);
        //похоже promotions_objects не используется, ее не чистим
        
		$res = $db->querys("DELETE FROM ".$sys_tables['promotions']." WHERE id=?", $id);
		$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
		if($ajax_mode){
			$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
			break;
		}
	default:
		$module_template = 'admin.list.html';
		// формирование списка
        $estate_complex_types = $mapping['promotions']['estate_complex_type']['values'];
        Response::SetArray('estate_complex_types',$estate_complex_types);
        $estate_types = $db->fetchall("SELECT id, title FROM ".$sys_tables['estate_types']." WHERE id <=4 ");
        Response::SetArray('estate_types',$estate_types);
		$conditions = array();
		if(!empty($filters)){
			if(!empty($filters['title'])) $conditions['title'] = $sys_tables['promotions'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['estate_complex_type'])) $conditions['estate_complex_type'] = "`estate_complex_type` = ".$db->real_escape_string($filters['estate_complex_type'])."";
            if(!empty($filters['estate_type'])) $conditions['estate_type'] = "`id_estate_type` = ".$db->real_escape_string($filters['estate_type'])."";
			if(!empty($filters['published'])) $conditions['published'] = $sys_tables['promotions'].".`published` = ".$db->real_escape_string($filters['published'])."";
		}
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
        if(empty($condition)) $condition = '1';
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['promotions'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = array();
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/access/promotions'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}
        $promotions = new Promotion();
        // формирование списка
		$list = $promotions->getList($paginator->getLimitString($page), $sys_tables['promotions'].".id DESC", $condition);
		Response::SetArray('list', $list);
		Response::SetArray('paginator', $paginator->Get($page));
		break;
}

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>