<?php
require_once('includes/class.paginator.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Видео конкурс'));

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['status'] = Request::GetString('f_status',METHOD_GET);
$filters['sms_status'] = Request::GetString('f_sms_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}                                                                                                                        
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; else $filters['status'] = '';
if(!empty($filters['sms_status'])) $get_parameters['f_sms_status'] = $filters['sms_status']; else $filters['sms_status'] = '';

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];



// обработка action-ов
switch($action){
    case 'del':
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            if(!empty($id))  {
                $res = $db->query("DELETE FROM ".$sys_tables['video_konkurs']." WHERE id = ?", $id);
                $ajax_result = array('ok' => $res, 'ids'=>array($id));
                $url = "http://ficus-n2.cloud4video.ru:8089/rest-api/file?login=pm%40bsn.ru&password=4d651eb627&gen_int_id=true";
                $body = '<?xml version="1.0" encoding="utf-8"?>
                        <root>
                        <file id="konkurs_bsn_id_'.$id.'" />
                        </root>';
                $result = curlThis($url, 'DELETE', false, true, $body); 
                
            }
        break;
	case 'add':
	case 'edit':
		$module_template = 'admin.video_konkurs.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['video_konkurs']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT ".$sys_tables['video_konkurs'].".*, 
                                IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates'].".title,".$sys_tables['cottages'].".title) as title,
                                IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates'].".chpu_title,".$sys_tables['cottages'].".chpu_title) as chpu_title,
                                IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates_photos'].".name,".$sys_tables['cottages_photos'].".name) as photo_name,
                                IF(".$sys_tables['video_konkurs'].".complex_type=1,LEFT (".$sys_tables['housing_estates_photos'].".`name`,2),LEFT (".$sys_tables['cottages_photos'].".`name`,2)) as subfolder
								FROM ".$sys_tables['video_konkurs']." 
                                LEFT JOIN ".$sys_tables['cottages']." ON ".$sys_tables['cottages'].".id = ".$sys_tables['video_konkurs'].".id_estate_complex AND complex_type = 2
                                LEFT JOIN ".$sys_tables['cottages_photos']." ON ".$sys_tables['cottages'].".id_main_photo = ".$sys_tables['cottages_photos'].".id
                                LEFT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['housing_estates'].".id = ".$sys_tables['video_konkurs'].".id_estate_complex
                                LEFT JOIN ".$sys_tables['housing_estates_photos']." ON ".$sys_tables['housing_estates'].".id_main_photo = ".$sys_tables['housing_estates_photos'].".id
								WHERE ".$sys_tables['video_konkurs'].".id=?", $id) ;
			//предустановка ссылки на картинки на главной и в шапке
			$cnt = count($_POST);
            $item = $info;
            Response::SetArray('info', $info);
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['video_konkurs'][$key])) $mapping['video_konkurs'][$key]['value'] = $info[$key];
		}
        
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
		
		Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['video_konkurs']);
			
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['video_konkurs'][$key])) $mapping['video_konkurs'][$key]['value'] = $post_parameters[$key];
			}

			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['video_konkurs'][$key])) $mapping['video_konkurs'][$key]['error'] = $value;
			}
		
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['video_konkurs'][$key]['value'])) $info[$key] = $mapping['video_konkurs'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
                    //статус - отредактирован объект
					$res = $db->updateFromArray($sys_tables['video_konkurs'], $info, 'id') or die($db->error);
                    //видео прошло модерацию
                    if($item['status'] == 2 && $info['status'] == 1){
                        //письмо менеджеру
                        require_once('includes/class.email.php');
                        $mailer = new EMailer('mail');
                        Response::SetArray('item', $item);
                        Response::SetString('id', $id);
                        $eml_tpl = new Template('/modules/video_konkurs/templates/mail.user.html', $this_page->module_path);
                        $html = $eml_tpl->Processing();
                        $html = iconv('UTF-8', $mailer->CharSet, $html);
                        $mailer->ClearAddresses();
                        $mailer->AddAddress($item['email']);
                        $mailer->Subject = iconv('UTF-8', $mailer->CharSet,'Ваше видео опубликовано. "Видео конкурс ЖК" на сайте BSN.ru');
                        $mailer->Body = $html;
                        $mailer->IsHTML(true);
                        $mailer->From = 'no-reply@bsn.ru';
                        $mailer->FromName = 'bsn.ru';
                        $mailer->Send();
                        
                    }
				} else {
					//дата дообавления объекта
					$res = $db->insertFromArray($sys_tables['video_konkurs'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/service/video_konkurs/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['video_konkurs']);
		break;
	default:
		$module_template = 'admin.video_konkurs.list.html';
		 $sql = "
			SELECT  
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['video_konkurs']." WHERE `status` = 1) AS active,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['video_konkurs']." WHERE `status` = 2) AS noneactive
			FROM dual";
		$counts = $db->fetch($sql) or die($sql.$db->error);
		Response::SetArray('statuses',array(
                                            ''          =>  'не выбран',
											'active'	=>	'Да - '.$counts['active'],
											'noneactive'=>	'Нет - '.$counts['noneactive']
											));
         $sql = "
            SELECT  
                (SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['video_konkurs']." WHERE `sms_status` = 1) AS active,
                (SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['video_konkurs']." WHERE `sms_status` = 2) AS noneactive
            FROM dual";
        $counts = $db->fetch($sql) or die($sql.$db->error);
        Response::SetArray('sms_statuses',array(
                                            ''          =>  'не выбрано',
                                            'active'    =>    'Пополнен - '.$counts['active'],
                                            'noneactive'=>    'Не нополнен - '.$counts['noneactive']
                                            ));
        $conditions = array();
		if(!empty($filters)){
            switch($filters['status']){
                case  'active'    : $conditions['status'] = $sys_tables['video_konkurs'].'.status = 1';    break;
                case  'noneactive'    : $conditions['sms_status'] = $sys_tables['video_konkurs'].'.status=2';     break;
            }
			switch($filters['sms_status']){
				case  'active'	: $conditions['sms_status'] = $sys_tables['video_konkurs'].'.sms_status = 1';    break;
				case  'noneactive'	: $conditions['sms_status'] = $sys_tables['video_konkurs'].'.sms_status=2'; 	break;
			}
		} 
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['video_konkurs'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = array();
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/service/video_konkurs'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}
        $sql = "SELECT ".$sys_tables['video_konkurs'].".*, 
                        IF(YEAR(".$sys_tables['video_konkurs'].".`datetime`) < Year(CURDATE()),DATE_FORMAT(".$sys_tables['video_konkurs'].".`datetime`,'%e %M %Y'),DATE_FORMAT(".$sys_tables['video_konkurs'].".`datetime`,'%e %M, %k:%i')) as normal_date,
                            IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates'].".title,".$sys_tables['cottages'].".title) as title,
                            IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates'].".chpu_title,".$sys_tables['cottages'].".chpu_title) as chpu_title,
                            IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates_photos'].".name,".$sys_tables['cottages_photos'].".name) as photo_name,
                            IF(".$sys_tables['video_konkurs'].".complex_type=1,LEFT (".$sys_tables['housing_estates_photos'].".`name`,2),LEFT (".$sys_tables['cottages_photos'].".`name`,2)) as subfolder
                FROM ".$sys_tables['video_konkurs']." 
                LEFT JOIN ".$sys_tables['cottages']." ON ".$sys_tables['cottages'].".id = ".$sys_tables['video_konkurs'].".id_estate_complex AND complex_type = 2
                LEFT JOIN ".$sys_tables['cottages_photos']." ON ".$sys_tables['cottages'].".id_main_photo = ".$sys_tables['cottages_photos'].".id
                LEFT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['housing_estates'].".id = ".$sys_tables['video_konkurs'].".id_estate_complex
                LEFT JOIN ".$sys_tables['housing_estates_photos']." ON ".$sys_tables['housing_estates'].".id_main_photo = ".$sys_tables['housing_estates_photos'].".id";
		if(!empty($condition)) $sql .= " WHERE ".$condition;
		$sql .= " ORDER BY ".$sys_tables['video_konkurs'].".id DESC";
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