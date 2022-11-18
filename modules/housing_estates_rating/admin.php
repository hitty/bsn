<?php
$GLOBALS['js_set'][] = '/modules/housing_estates_rating/ajax_actions.js';
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.housing_estates.rating.php');

$housing_estates_rating = new HousingEstatesRating();

// основной шаблон модуля (шаблон по умолчанию)
$module_template = 'admin.housing_estates_rating.html';

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Рейтинг ЖК'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
$filters['agreed'] = Request::GetString('f_agreed',METHOD_GET);
if(!empty($filters['agreed'])) {
    $get_parameters['f_agreed'] = $filters['agreed'];
}
$filters['district'] = Request::GetString('f_district',METHOD_GET);
if(!empty($filters['district'])) {
    $get_parameters['f_district'] = $filters['district'];
}


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
    |*  Районы
    \*************************************************/
    case 'districts':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
			case 'add':
			case 'edit':
				$module_template = 'admin.districts.edit.html';
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
					if(!empty($mapping['districts'][$key])) $mapping['districts'][$key]['value'] = $info[$key];
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
					if(isset($mapping['districts']['description']['value'])) $mapping['districts']['description']['value'] = strip_tags($mapping['districts']['description']['value'],'<a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3><blockquote>');
                    if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['districts'][$key]['value'])) $info[$key] = $mapping['districts'][$key]['value'];
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
									header('Location: '.Host::getWebPath('/admin/service/housing_estates_rating/districts/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['districts']);
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
				$module_template = 'admin.districts.list.html';
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
				$paginator->link_prefix = '/admin/service/housing_estates_rating/districts'                  // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
		
				$sql = "SELECT * FROM ".$sys_tables['housing_estates_districts'];
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
    |*  Эксперты
    \*************************************************/
    case 'experts':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            /*************************************************\
            |*  Отправка приглашения
            \*************************************************/
            case 'invite':                          
                $id = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                if( !empty( $id ) ) $housing_estates_rating->sentInvite( $id );
            break;
            case 'add':
            case 'edit':
                $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
                $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
                $module_template = 'admin.experts.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['housing_estates_experts']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT 
                                            ".$sys_tables['housing_estates_experts'].".*,
                                            ".$sys_tables['users'].".name,
                                            ".$sys_tables['users'].".lastname
                                        FROM ".$sys_tables['housing_estates_experts']." 
                                        RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['housing_estates_experts'].".id_user = ".$sys_tables['users'].".id
                                        WHERE 
                                            ".$sys_tables['housing_estates_experts'].".id=?
                                        GROUP BY ".$sys_tables['housing_estates_experts'].".id
                                        ", 
                                        $id
                    ) ;
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if( !empty($mapping['experts'][$key]) && $key!='passwd' ) $mapping['experts'][$key]['value'] = $info[$key];
                }
                if($action=='edit'){
                    if(!empty($mapping['experts']['passwd'])) unset($mapping['experts']['passwd']);
                    
                }
                
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                
                $mapping['experts']['districts']['values'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['housing_estates_districts']." ORDER BY title ASC", 'id');
                
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['experts'][$key])) $mapping['experts'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['experts']);

                    //если email не пуст, проверяем его корректность
                    if((!empty($mapping['experts']['email']['value']))&&(empty($errors['email']))){
                        $mapping['experts']['email']['value'] = trim( $mapping['experts']['email']['value'] );
                        if (!Validate::isEmail($mapping['experts']['email']['value'])) $errors['email'] = 'Некорректный email';
                    }
                    // проверка на корректность пароля
                    if(empty($errors['passwd']) && !empty($mapping['experts']['passwd']['value'])){
                        if(!Validate::isPassword($mapping['experts']['passwd']['value'])) $errors['passwd'] = $mapping['experts']['passwd']['error'] = 'Некорректный пароль. Должен быть не короче 4-х символов и может содержать латинские буквы, цифры, знаки - + . , _ ( ) { } [ ] < >';
                    }
                    
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['experts'][$key])) $mapping['experts'][$key]['error'] = $value;
                    }
                    if(empty($errors)) {
                        // корректировка пароля перед сохранением
                        if(!empty($mapping['experts']['passwd']['value'])){
                            // если пароль изменился, то готовим к записи его двойной хэш
                            $backup_passwd = $mapping['experts']['passwd']['value'];
                            if( !empty( $post_parameters['passwd'] ) ) {
                                $original_passwd = $post_parameters['passwd'];
                                $post_parameters['passwd'] = sha1(sha1($post_parameters['passwd']));
                            }
                        }
                        //флаг отправки приглашения пользователю
                        if( ( empty( $info['sent_mail'] ) && $info['sent_mail'] != 1 ) && (!empty( $post_parameters['sent_mail'] ) && $post_parameters['sent_mail'] == 1) ) $sent_invite = true;
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['experts'][$key]['value'])) $info[$key] = $mapping['experts'][$key]['value'];
                        }
                        $info['date'] = date( "Y-m-d", strtotime( $info['date'] ) ); 
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['housing_estates_experts'], $info, 'id') or die($db->error);
                            
                            $db->querys(" UPDATE " . $sys_tables['users'] . " SET name = ?, lastname = ? WHERE id = ?",
                                       $post_parameters['name'], $post_parameters['lastname'], $info['id_user']
                            );
                            if( !empty(  $sent_invite ) ) $housing_estates_rating->sentInvite( $info['id'] );
                        } else {
                            $info['token'] = sha1( sha1( time() ) );
                            $res = $db->insertFromArray($sys_tables['housing_estates_experts'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                $db->querys(" INSERT INTO " . $sys_tables['users'] . " SET login = ?, name = ?, lastname = ?, id_group = 14, expert = 1",
                                           'he_expert_' . $new_id, $post_parameters['name'], $post_parameters['lastname']
                                );
                                $user_id = $db->insert_id;
                                $db->querys(" UPDATE " . $sys_tables['housing_estates_experts'] . " SET id_user = ?, original_passwd = ? WHERE id = ?", $user_id, $original_passwd, $new_id );
                                if( !empty(  $sent_invite ) ) $housing_estates_rating->sentInvite( $new_id );
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/service/housing_estates_rating/experts/edit/'.$new_id.'/'));
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
                Response::SetArray('data_mapping', $mapping['experts']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->querys("DELETE FROM ".$sys_tables['housing_estates_experts']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            default:
                $module_template = 'admin.experts.list.html';
                Response::SetArray('agreeds', array(1=>'принял',2=>'не принял') );
                
                // формирование фильтра по названию
                $conditions = [];
                if(!empty($filters['title'])) $conditions['title'] = $sys_tables['housing_estates_experts'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                if(!empty($filters['agreed'])) $conditions['agreed'] = $sys_tables['housing_estates_experts'].".`agreed` = ".$db->real_escape_string($filters['agreed']);
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);        
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['housing_estates_experts'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/service/housing_estates_rating/experts'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
        
                $list = $db->fetchall(
                                    "SELECT 
                                        ".$sys_tables['housing_estates_experts'].".*,
                                        GROUP_CONCAT(dd.housing_estates_ids) as housing_estates_ids,
                                        GROUP_CONCAT(dd.title) as district_title,
                                        ".$sys_tables['users'].".login,
                                        ".$sys_tables['users'].".name,
                                        ".$sys_tables['users'].".lastname,
                                        ".$sys_tables['agencies'].".title as agency_title,
                                        ".$sys_tables['housing_estates_experts_photos'].".name as photo_name,
                                        LEFT(".$sys_tables['housing_estates_experts_photos'].".name,2) as photo_subfolder
                                    FROM ".$sys_tables['housing_estates_experts']." 
                                    RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['housing_estates_experts'].".id_user = ".$sys_tables['users'].".id
                                    LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    LEFT JOIN ".$sys_tables['housing_estates_experts_photos']." ON ".$sys_tables['housing_estates_experts_photos'].".id = ".$sys_tables['housing_estates_experts'].".id_main_photo
                                    LEFT JOIN (
                                        SELECT 
                                            id,
                                            GROUP_CONCAT( ' ', d.title) as title,
                                            GROUP_CONCAT(d.housing_estates_ids) as housing_estates_ids
                                        FROM ".$sys_tables['housing_estates_districts']." d 
                                        GROUP BY d.id
                                    ) dd ON FIND_IN_SET( dd.id, ".$sys_tables['housing_estates_experts'].".districts ) 
                                    WHERE 
                                        ".$sys_tables['housing_estates_experts'].".sent_mail IS NOT NULL " . ( !empty( $condition ) ? "  AND ".$condition : "" ) . "
                                    GROUP BY ".$sys_tables['housing_estates_experts'].".id
                                    LIMIT ".$paginator->getLimitString($page)
                ) ;
                // формирование списка
                foreach( $list as $k=>$item ){
                    if( !empty( $item['housing_estates_ids'] ) ){
                        $votings = $db->fetch(" SELECT COUNT(*) as cnt FROM ".$sys_tables['housing_estates_voting']." WHERE id_parent IN (" . $item['housing_estates_ids'] . ") AND id_user = ?", $item['id_user'] );
                        $list[$k]['votings'] = $votings['cnt'];
                        $list[$k]['total'] = count( explode( ",", $item['housing_estates_ids'] ) );
                    }
                }
                Response::SetArray('list', $list);
                if($paginator->pages_count>1){
                    Response::SetArray('paginator', $paginator->Get($page));
                }
                break;
        }
        break;    
        
    /*************************************************\
    |*  Рейтинг по районам
    \*************************************************/
    case 'rating':
        $districts = $db->fetchall(" SELECT id,title FROM " . $sys_tables['housing_estates_districts'] . " ORDER BY title");
        Response::SetArray('districts', $districts );
        
        $module_template = 'admin.rating.list.html';
        
        // формирование фильтра по названию
        $conditions = [];
        if(!empty( $filters['district'] ) ) $conditions['district'] = $sys_tables['housing_estates_districts'].".`id` = ".$db->real_escape_string($filters['district']);
        if( !empty( $filters['district'] ) ) {  
            $ids = $db->fetch(" SELECT housing_estates_ids FROM " . $sys_tables['housing_estates_districts'] . " WHERE id = ?", $filters['district'])['housing_estates_ids'];
            $list = $housing_estates_rating->getRatingList( false, 1, 1000, 'rating', false, $sys_tables['housing_estates_voting'] . ".datetime > '2017-10-01' AND " . $sys_tables['housing_estates_voting'] . ".id_parent IN (" . $ids. ")" );
            Response::SetArray('list', $list);
        }
        
        break;
    default:
        // основной шаблон модуля (шаблон по умолчанию)
        $module_template = '/admin/templates/admin.housing_estates_rating.html';
        break;
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>