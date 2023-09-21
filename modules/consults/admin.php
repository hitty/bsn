<?php

$GLOBALS['js_set'][] = '/modules/consults/ajax_actions.js';

require_once('includes/class.paginator.php');
require_once('includes/class.email.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.consults.php');
require_once('includes/class.messages.php');
require_once('includes/class.common.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Консультации'));

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['id'] = Request::GetInteger('f_id',METHOD_GET);
$filters['id_answer'] = Request::GetInteger('f_id_answer',METHOD_GET);
$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
$filters['status'] = Request::GetString('f_status',METHOD_GET);
$filters['member'] = Request::GetString('f_member',METHOD_GET);
if(empty($filters['id'])) $filters['id'] = 0;
else $get_parameters['f_id'] = $filters['id'];
if(empty($filters['id_answer'])) $filters['id_answer'] = 0;
else $get_parameters['f_id_answer'] = $filters['id_answer'];
if(empty($filters['category'])) $filters['category'] = 0;
    $get_parameters['f_category'] = $filters['category'];
if(empty($filters['category'])) $filters['category'] = 0;
    $get_parameters['f_category'] = $filters['category'];
if(empty($filters['status'])) $filters['status'] = 0;
     $get_parameters['f_status'] = $filters['status'];
if(!empty($filters['member'])) {
    $get_parameters['f_member'] = $filters['member'];
}
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
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
                            $list = Photos::getList('consults_member_agencies',$id);
                            if(!empty($list)){
                                $ajax_result['ok'] = true;
                                $ajax_result['list'] = $list;
                                $ajax_result['folder'] = Config::$values['img_folders']['consults_member_profiles'];
                            } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                    case 'add':
                        //загрузка фотографий
                        //id текущей новости
                        $id = Request::GetInteger('id', METHOD_POST);                
                        if(!empty($id)){
                            $res = Photos::Add('consults_member_agencies',$id,false,false,false,false,false,true);
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
                            $res = Photos::setTitle('consults_member_agencies',$id, $title);
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
                            $res = Photos::Delete('consults_member_agencies',$id_photo);
                            
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
					$info = $db->prepareNewRecord($sys_tables['agencies']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['agencies']." 
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
				$res = $db->querys("DELETE FROM ".$sys_tables['agencies']." WHERE id=?", $id);
                //удаление фото агентства
                $del_photos = Photos::DeleteAll('consults_member_agencies',$id);    
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
				$paginator = new Paginator($sys_tables['agencies'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/content/consults/agencies'                  // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
        
				$sql = "SELECT ".$sys_tables['agencies'].".*,
                            CONCAT_WS('/','".Config::$values['img_folders']['consults_member_profiles']."','sm',LEFT(photos.name,2)) as agency_photo_folder,
                            photos.name as agency_photo
                        FROM ".$sys_tables['agencies']."
                        LEFT JOIN  ".$sys_tables['agencies_photos']." photos ON photos.id_parent=".$sys_tables['agencies'].".id";
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
    case 'categories':
        // переопределяем экшн
       $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
			case 'add':
			case 'edit':
				$module_template = 'admin.categories.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['consults_categories']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['consults_categories']." 
										WHERE id=?", $id) ;
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $info[$key];
				}
				
				$post_parameters = Request::GetParameters(METHOD_POST);
                
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true);
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $post_parameters[$key];
					}
                    
					$errors = Validate::validateParams($post_parameters,$mapping['categories']);
					foreach($errors as $key=>$value){
						if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['error'] = $value;
					}
					
					if(empty($errors)) {
						
						foreach($info as $key=>$field){
							if(isset($mapping['categories'][$key]['value'])) $info[$key] = $mapping['categories'][$key]['value'];
						}
						// сохранение в БД
                        //если в итоге ответ не опубликован, убираем его из перечня вопросов
						if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['consults_categories'], $info, 'id') or die($db->error);
                            
						} else {
							$res = $db->insertFromArray($sys_tables['consults_categories'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/content/consults/categories/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['categories']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$res = $db->querys("DELETE FROM ".$sys_tables['consults_categories']." WHERE id=?", $id);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			default:
				$module_template = 'admin.categories.list.html';
				// формирование фильтра по названию
				$conditions = [];
				if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['consults_categories'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/content/consults/categories'                  // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
		
				$sql = "SELECT id,title FROM ".$sys_tables['consults_categories'];
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
     case 'members':
	    // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		// дефолтное значение папки выгрузки и свойств фото
        switch($action){
			case 'add':
			case 'edit':
				// переопределяем экшн
				$action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];    
				$module_template = 'admin.members.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['consults_members']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['consults_members']." 
										WHERE id=?", $id) ;
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['members'][$key])) $mapping['members'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
				// формирование дополнительных данных для формы (не из основной таблицы)
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['members'][$key])) $mapping['members'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['members']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['members'][$key])) $mapping['members'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['members'][$key]['value'])) $info[$key] = $mapping['members'][$key]['value'];
						}
						// сохранение в БД
						if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['consults_members'], $info, 'id') or die($db->error);
						} else {
							$res = $db->insertFromArray($sys_tables['consults_members'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/content/consults/members/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['members']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$res = $db->querys("DELETE FROM ".$sys_tables['consults_members']." WHERE id=?", $id);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
            //регистрируем пользователя у нас и отправляем ему письмо с паролем
            case 'reg':
                if($ajax_mode){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    if(empty($id)){
                        $ajax_result['ok'] = false;
                        break;
                    }
                    $spec_info = $db->fetch("SELECT id,bsn_id,name,login,IF(phone1!='',phone1,IF(phone2!='',phone2,IF(phone3!='',phone3,''))) AS phone,email FROM ".$sys_tables['consults_members']." WHERE id = ?",$id);
                    $spec_info['user_activity'] = 2;
                    if($spec_info['bsn_id'] > 0){
                        $ajax_result['error'] = "Уже зарегистрирован";
                        $ajax_result['ok'] = true;
                        break;
                    }
                    unset($spec_info['bsn_id']);
                    //$ajax_result['func'] = is_callable("Common::createUser");
                    //$ajax_result['data'] = $spec_info;
                    $new_user_data = Common::createUser($spec_info);
                    if(empty($new_user_data)){
                        $existing_id = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE email = ?",$spec_info['email'])['id'];
                        $res = $db->querys("UPDATE ".$sys_tables['consults_members']." SET bsn_id = ? WHERE id = ?",$existing_id,$id);
                        if(!empty($existing_id)){
                            $ajax_result['ids'] = array($spec_info['id']);
                            $ajax_result['alert'] = "Не удалось добавить: уже есть пользователь #".$existing_id." с такой почтой. Аккаунт был привязан";
                        }elseif(empty($spec_info['name']) || empty($spec_info['email']) || !Validate::isEmail($spec_info['email'])){
                            $ajax_result['error'] = "Неверные параметры пользователя";
                        }
                        $ajax_result['ok'] = true;
                        break;
                    }
                    $res = $db->querys("UPDATE ".$sys_tables['consults_members']." SET bsn_id = ? WHERE id = ?",$new_user_data['id'],$id);
                    $res = $db->querys("UPDATE ".$sys_tables['consults_answers']." SET id_user = ? WHERE id_member = ?",$new_user_data['id'],$id);
                    $ajax_result['alert'] = "Пользователь успешно добавлен, оптравлено письмо";
                    $ajax_result['ids'] = array($spec_info['id']);
                    $ajax_result['ok'] = $res;
                    //шлем специалисту письмо-оповещение
                    $mailer = new EMailer('mail');
                    $env = array(
                        'url' => Host::GetWebPath(),
                        'host' => Host::$host,
                        'name' => $spec_info['name']
                    );
                    Response::SetArray('env', $env);
                    Response::SetArray('reg_data',$new_user_data);
                    $eml_tpl = new Template('/modules/consults/templates/mail_new_spec.html');
                    $html = $eml_tpl->Processing();
                    $html = iconv('UTF-8', $mailer->CharSet, $html);
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Регистрация на портале BSN.ru '.Host::$host);
                    $mailer->Body = $html;
                    $mailer->AltBody = strip_tags($html);
                    $mailer->IsHTML(true);
                    $mailer->AddAddress($new_user_data['email'], iconv('UTF-8',$mailer->CharSet, $spec_info['name']));
                    $mailer->AddAddress("hitty@bsn.ru",iconv('UTF-8',$mailer->CharSet, ""));
                    $mailer->From = 'no-reply@bsn.ru';
                    $mailer->FromName = 'bsn.ru';
                    $mailer->Send();
                }
                
                break;
			default:
				$module_template = 'admin.members.list.html';
				// формирование списка
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['consults_members'], 30);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = [];
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/content/consults/members'                // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
				
				$sql = "SELECT * FROM ".$sys_tables['consults_members'];
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
	case 'edit':
		$module_template = 'admin.consults.edit.html';
        $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
        $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
        $GLOBALS['js_set'][] = '/modules/consults/ajax_actions.js';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		
        $this_question = new ConsultQuestion($id);
        
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		$this_question->returnToMapping($mapping['consults']);
        
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
        
        // формирование дополнительных данных для формы (не из основной таблицы)
        $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['consults_categories']." ORDER BY id");
        foreach($categories as $key=>$val){
            $mapping['consults']['id_category']['values'][$val['id']] = $val['title'];
        }
        
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
            
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(isset($mapping['consults'][$key])){
                    $mapping['consults'][$key]['value'] = $post_parameters[$key];
                }
            }
            
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            $old_status = $this_question->status;
            $this_question->updateFromMapping($mapping['consults']);
			
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['consults']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['consults'][$key])) $mapping['consults'][$key]['error'] = $value;
			}
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
			    
                //если это опубликование, шлем оповещения клиенту, который оставил и специалистам
                if (Convert::ToInt($post_parameters['status'])==1 && $old_status != 1){
                    $res = $this_question->publishQuestion();
                    $mapping['consults']['status']['value'] = $this_question->status;
                }else $res = $this_question->saveToDB(true);
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
        
        //если это персональный вопрос, подтягиваем данные по пользователю
        if(!empty($mapping['consults']['id_respondent_user']['value'])) Response::SetArray('respondent_info',$this_question->getRespondentInfo());
        
		// запись данных для отображения на странице
		Response::SetArray('data_mapping',$mapping['consults']);
		break;
	case 'del':
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$res = $db->querys("DELETE FROM ".$sys_tables['consults']." WHERE id=?", $id);
		$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
		if($ajax_mode){
			$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
			break;
		}
    //просматриваем вопрос и список ответов
    case 'view':
        $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
        $question_id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $this_question = new ConsultQuestion($question_id);
        Response::SetArray('question_info',array('title' => $this_question->getAttr('question_title'),
                                                 'text' => $this_question->getAttr('question'),
                                                 'category_title' => $this_question->getAttr('category_title'),
                                                 'id' => $this_question->id,
                                                 'datetime' => $this_question->getAttr('question_datetime'),
                                                 'name'=>$this_question->getAttr('question_name')) );
        $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
        switch($action){
            case 'add':
            case 'edit':
                $module_template = 'admin.consults_answers.edit.html';
                if($action=='add') $info = $db->prepareNewRecord($sys_tables['consults_answers']);
                else{
                    $info = $db->fetch("SELECT * FROM ".$sys_tables['consults_answers']." WHERE id = ?",$id);
                    $author = $db->fetch("SELECT id,CONCAT(name,lastname) AS title FROM ".$sys_tables['users']." WHERE id = ?",$info['id_user']);
                    Response::SetArray('author',$author);
                }
                
                Response::SetBoolean('is_first',($this_question->getAttr('id_first_answer') == $id));
                Response::SetBoolean('is_best',($this_question->getAttr('id_best_answer') == $id));
                
                foreach($info as $key=>$field){
                    if(!empty($mapping['answers'][$key])) $mapping['answers'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
        
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['answers'][$key])) $mapping['answers'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['answers']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['answers'][$key])) $mapping['answers'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['answers'][$key]['value'])) $info[$key] = $mapping['answers'][$key]['value'];
                        }
                        $info['id_parent'] = $this_question->id;
                        // сохранение в БД
                        if($action=='edit') $res = $this_question->updateAnswer($info);
                        else{
                            $info['date_in'] = date('Y-m-d h:i:s',time());
                            $new_id = $this_question->addAnswer($info);
                            $res = !empty($new_id);
                            // редирект на редактирование свеженькой страницы
                            if(!empty($res)) {
                                header('Location: '.Host::getWebPath('/admin/content/consults/view/'.$this_question->id.'/edit/'.$new_id.'/'));
                                exit(0);
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
                Response::SetArray('data_mapping',$mapping['answers']);
                break;
            case 'del':
                if($ajax_mode){
                    if(empty($id)){
                        $ajax_result['ok'] = false;
                        break;
                    }
                    $this_question->deleteAnswer($id);
                    $ajax_result['ids'] = array($id);
                    $ajax_result['ok'] = true;
                }
                break;
            default:
                $module_template = 'admin.consults.view.html';
                if(empty($this_question)){
                    Host::Redirect("/admin/content/consults/");
                    break;
                }
                $list = $this_question->getAnswersList(true);
                if(!empty($list)) Response::SetArray('list',$list);
                break;
        }
        break;
	default:
		$module_template = 'admin.consults.list.html';
		// формирование списка
        $members = $db->fetchall("SELECT id,name FROM ".$sys_tables['consults_members']." WHERE id>1 ORDER BY name, id");
        
        $consults_category = $db->fetchall("SELECT 0 AS id,'все' AS title UNION ALL SELECT id,title FROM ".$sys_tables['consults_categories']." ORDER BY id");
        Response::SetArray('categories',$consults_category);
        
        Response::SetArray('members',$members);
		$conditions = [];
		if(!empty($filters)){
            if(!empty($filters['id'])) $conditions['id'] = $sys_tables['consults'].".`id` = ".$db->real_escape_string($filters['id'])."";
            if(!empty($filters['id_answer'])) $conditions['id_answer'] = $sys_tables['consults_answers'].".`id` = ".$db->real_escape_string($filters['id_answer'])."";
            if(!empty($filters['category']) && $filters['category']!=0) $conditions['category'] = $sys_tables['consults'].".`id_category` = ".$db->real_escape_string($filters['category'])."";
            if(!empty($filters['status'])) $conditions['status'] = $sys_tables['consults'].".`status` = ".$db->real_escape_string($filters['status'])."";
		}
        
        //читаем все вопросы, где есть ответы на модерации
        $moder_list = $db->fetchall("SELECT id_parent FROM ".$sys_tables['consults_answers']." WHERE status = 2",'id_parent');
        $moder_list = (empty($moder_list)?"":implode(',',array_keys($moder_list)));
        
        switch(true){
            case (!empty($filters['member'])):
                $conditions = [];
                if(!empty($filters)){
                    if(!empty($filters['id'])) $conditions['id'] = $sys_tables['consults'].".`id` = ".$db->real_escape_string($filters['id'])."";
                    if(!empty($filters['id_answer'])) $conditions['id_answer'] = $sys_tables['consults_answers'].".`id` = ".$db->real_escape_string($filters['id_answer'])."";
                    if(!empty($filters['member'])) $conditions['member'] = $sys_tables['consults_answers'].".`id_member` = ".$db->real_escape_string($filters['member'])."";
                    if(!empty($filters['category'])) $conditions['category'] = $sys_tables['consults'].".`id_category` = ".$db->real_escape_string($filters['category'])."";
                    if(!empty($filters['status'])) $conditions['status'] = $sys_tables['consults'].".`status` = ".$db->real_escape_string($filters['status'])."";
                }
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);        
                
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['consults_answers'], 30, $sys_tables['consults_answers'].".`id_member` = ".$db->real_escape_string($filters['member'])."");
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/consults'                // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
                
                $sql = "SELECT ".$sys_tables['consults'].".*, 
                               CASE
                                   WHEN ".$sys_tables['consults'].".status = 1 THEN 'Опубликован'
                                   WHEN ".$sys_tables['consults'].".status = 2 THEN 'На модерации'
                                   WHEN ".$sys_tables['consults'].".status = 3 THEN 'Не прошел модерацию'
                                   WHEN ".$sys_tables['consults'].".status = 4 THEN 'В архиве'
                               END AS status_title,
                               IF(".$sys_tables['consults'].".status = 1 || ".$sys_tables['consults'].".status = 4,true,false) AS show_item, 
                               CASE
                                   WHEN ".$sys_tables['consults'].".visible_to_all = 1 THEN 'в общем пуле'
                                   WHEN ".$sys_tables['consults'].".visible_to_all = 2 THEN 'в закрытом доступе'
                               END AS visibility_title,
                               ".$sys_tables['consults_categories'].".code as category_code,
                               ".$sys_tables['consults_categories'].".title as category_name,
                               DATE_FORMAT(".$sys_tables['consults'].".question_datetime,'%e %M, %k:%i') as normal_question_date,
                               b.answer AS best_answer,
                               a.answer AS first_answer,
                               ".(empty($moder_list)?"0 AS answers_need_moderation":"IF(".$sys_tables['consults'].".id IN (".$moder_list."),1,0) AS answers_need_moderation")."
                        FROM ".$sys_tables['consults_answers']."
                        RIGHT JOIN ".$sys_tables['consults']." ON ".$sys_tables['consults_answers'].".id_parent = ".$sys_tables['consults'].".id
                        LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                        LEFT JOIN ".$sys_tables['consults_answers']." a ON ".$sys_tables['consults'].".id_first_answer = a.id
                        LEFT JOIN ".$sys_tables['consults_answers']." b ON ".$sys_tables['consults'].".id_best_answer = b.id
                        WHERE ".$condition;
                $sql .= " ORDER BY  (".$sys_tables['consults'].".status = 2) DESC, answers_need_moderation DESC, ".$sys_tables['consults'].".id DESC";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                if($paginator->pages_count>1) Response::SetArray('paginator', $paginator->Get($page));
                break;
            case (!empty($filters['id_answer'])):
                $question_id = $db->fetch("SELECT id_parent AS id FROM ".$sys_tables['consults_answers']." WHERE id = ?",$filters['id_answer']);
                $question_id =  ((empty($question_id) || empty($question_id['id']))? 0 : $question_id['id']);
                $sql = "SELECT ".$sys_tables['consults'].".*, 
                               CASE
                                   WHEN ".$sys_tables['consults'].".status = 1 THEN 'Опубликован'
                                   WHEN ".$sys_tables['consults'].".status = 2 THEN 'На модерации'
                                   WHEN ".$sys_tables['consults'].".status = 3 THEN 'Не прошел модерацию'
                                   WHEN ".$sys_tables['consults'].".status = 4 THEN 'В архиве'
                               END AS status_title,
                               IF(".$sys_tables['consults'].".status = 1 || ".$sys_tables['consults'].".status = 4,true,false) AS show_item, 
                               CASE
                                   WHEN ".$sys_tables['consults'].".visible_to_all = 1 THEN 'в общем пуле'
                                   WHEN ".$sys_tables['consults'].".visible_to_all = 2 THEN 'в закрытом доступе'
                               END AS visibility_title,
                               ".$sys_tables['consults_categories'].".code as category_code,
                               ".$sys_tables['consults_categories'].".title as category_name,
                               DATE_FORMAT(".$sys_tables['consults'].".question_datetime,'%e %M, %k:%i') as normal_question_date,
                               b.answer AS best_answer,
                               a.answer AS first_answer,
                               ".(empty($moder_list)?"0 AS answers_need_moderation":"IF(".$sys_tables['consults'].".id IN (".$moder_list."),1,0) AS answers_need_moderation")."
                        FROM ".$sys_tables['consults']."
                        LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                        LEFT JOIN ".$sys_tables['consults_answers']." a ON ".$sys_tables['consults'].".id_first_answer = a.id
                        LEFT JOIN ".$sys_tables['consults_answers']." b ON ".$sys_tables['consults'].".id_best_answer = b.id
                        WHERE ".$sys_tables['consults'].".id = ?";
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " ORDER BY (".$sys_tables['consults'].".status = 2) DESC, answers_need_moderation DESC,".$sys_tables['consults'].".id DESC";
                $sql .= " LIMIT 1"; 
                $list = $db->fetchall($sql,false,$question_id);
                break;
            default:
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);        
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['consults'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/consults'                // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
                $sql = "SELECT ".$sys_tables['consults'].".*, 
                               CASE
                                   WHEN ".$sys_tables['consults'].".status = 1 THEN 'Опубликован'
                                   WHEN ".$sys_tables['consults'].".status = 2 THEN 'На модерации'
                                   WHEN ".$sys_tables['consults'].".status = 3 THEN 'Не прошел модерацию'
                                   WHEN ".$sys_tables['consults'].".status = 4 THEN 'В архиве'
                               END AS status_title,
                               IF(".$sys_tables['consults'].".status = 1 || ".$sys_tables['consults'].".status = 4,true,false) AS show_item, 
                               CASE
                                   WHEN ".$sys_tables['consults'].".visible_to_all = 1 THEN 'в общем пуле'
                                   WHEN ".$sys_tables['consults'].".visible_to_all = 2 THEN 'в закрытом доступе'
                               END AS visibility_title,
                               ".$sys_tables['consults_categories'].".code as category_code,
                               ".$sys_tables['consults_categories'].".title as category_name,
                               DATE_FORMAT(".$sys_tables['consults'].".question_datetime,'%e %M, %k:%i') as normal_question_date,
                               b.answer AS best_answer,
                               a.answer AS first_answer,
                               ".(empty($moder_list)?"0 AS answers_need_moderation":"IF(".$sys_tables['consults'].".id IN (".$moder_list."),1,0) AS answers_need_moderation")."
                        FROM ".$sys_tables['consults']."
                        LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                        LEFT JOIN ".$sys_tables['consults_answers']." a ON ".$sys_tables['consults'].".id_first_answer = a.id
                        LEFT JOIN ".$sys_tables['consults_answers']." b ON ".$sys_tables['consults'].".id_best_answer = b.id";
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " ORDER BY (".$sys_tables['consults'].".status = 2) DESC, answers_need_moderation DESC,".$sys_tables['consults'].".id DESC";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                if($paginator->pages_count>1) Response::SetArray('paginator', $paginator->Get($page));
        }
        
        //для выборки по юристам запрос отдельный
        
		
		// формирование списка
		Response::SetArray('list', $list);
		break;
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>