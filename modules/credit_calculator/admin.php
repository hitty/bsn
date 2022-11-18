<?php
$GLOBALS['js_set'][] = '/modules/credit_calculator/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Кредитный калькулятор'));

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

//условие по которому выбираем банки для калькуляторов
$banks_condition = "activity & ".pow(2,4)." AND estate_types & ".pow(2,8)." AND mortgage_applications_accepting < 5";

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
		$GLOBALS['js_set'][] = '/modules/credit_calculator/datepick_actions.js';
		//получение данных по объекту из базы
		$info = $db->fetch("SELECT 
								`id`,
								`title`,
								IF(img_link='',
									  CONCAT_WS('/','".Config::$values['img_folders']['credit_calculator']."',`img_src`),
								`img_link`) as photo
							FROM ".$sys_tables['credit_calculator']."
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
                    SELECT 
                        a.date,  
                        IFNULL(a.show_amount,0) as show_amount_card, 
                        IFNULL(b.show_amount,0) as show_amount_search,
                        IFNULL(c.click_amount,0) as click_amount_card,
                        IFNULL(d.click_amount,0) as click_amount_search,
                        IFNULL(e.click_amount,0) as click_amount_fb
                    FROM 
                    (
                      (
                          SELECT 
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date,
                                SUM(IFNULL(`amount`,0)) as show_amount 
                          FROM ".$sys_tables['credit_calculator_stats_show_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND 
                              `type` = 1 AND 
                              id_parent = ".$id."
                          GROUP BY `date`
                        ) a
                        LEFT JOIN 
                      (
                          SELECT 
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date,
                                SUM(IFNULL(`amount`,0)) as show_amount 
                          FROM ".$sys_tables['credit_calculator_stats_show_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')   AND 
                              `type` = 2 AND 
                              id_parent = ".$id."
                          GROUP BY `date`
                        ) b   ON a.date = b.date
                        LEFT JOIN 
                        (
                          SELECT  
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date,
                              SUM(IFNULL(`amount`,0)) as click_amount
                          FROM ".$sys_tables['credit_calculator_stats_click_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND 
                              `type` = 1 AND 
                              id_parent = ".$id."
                          GROUP BY `date`
                         ) c ON a.date = c.date
                        LEFT JOIN
                        (
                          SELECT
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date, 
                              SUM(IFNULL(`amount`,0)) as click_amount 
                          FROM ".$sys_tables['credit_calculator_stats_click_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND 
                              `type` = 2 AND 
                              id_parent = ".$id."
                          GROUP BY `date`
                         ) d ON a.date = d.date
                        LEFT JOIN
                        (
                          SELECT
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date, 
                              SUM(IFNULL(`amount`,0)) as click_amount 
                          FROM ".$sys_tables['credit_calculator_stats_click_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND 
                              `type` = 3 AND 
                              id_parent = ".$id."
                          GROUP BY `date`
                         ) e ON a.date = e.date
                    ) UNION (
                        SELECT 
                            aa.date,  
                            IFNULL(aa.show_amount,0) as show_amount_card, 
                            IFNULL(bb.show_amount,0) as show_amount_search,
                            IFNULL(cc.click_amount,0) as click_amount_card,
                            IFNULL(dd.click_amount,0) as click_amount_search,
                            IFNULL(ee.click_amount,0) as click_amount_fb
                        FROM 
                       (   SELECT 
                              'сегодня' as date,
                              IFNULL(COUNT(*),0) as show_amount,
                              id_parent
                          FROM ".$sys_tables['credit_calculator_stats_show_day']."
                          WHERE `type` = 1 AND 
                                 id_parent = ".$id."
                        ) aa
                        LEFT JOIN 
                       (   SELECT 
                              'сегодня' as date,
                              IFNULL(COUNT(*),0) as show_amount,
                              id_parent
                          FROM ".$sys_tables['credit_calculator_stats_show_day']."
                          WHERE `type` = 2  AND 
                                 id_parent = ".$id."
                        ) bb ON aa.date = bb.date
                        LEFT JOIN 
                        (
                          SELECT
                              'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,
                              id_parent
                          FROM ".$sys_tables['credit_calculator_stats_click_day']."
                          WHERE `type` = 1  AND 
                                 id_parent = ".$id."
                         ) cc ON aa.date = cc.date
                        LEFT JOIN
                        (
                          SELECT 
                              'сегодня' as date,
                              IFNULL(COUNT(*),0) as click_amount,
                              id_parent
                          FROM ".$sys_tables['credit_calculator_stats_click_day']."
                          WHERE `type` = 2 AND 
                                 id_parent = ".$id."
                         ) dd ON aa.date = dd.date
                        LEFT JOIN
                        (
                          SELECT 
                              'сегодня' as date,
                              IFNULL(COUNT(*),0) as click_amount,
                              id_parent
                          FROM ".$sys_tables['credit_calculator_stats_click_day']."
                          WHERE `type` = 3 AND 
                                 id_parent = ".$id."
                         ) ee ON aa.date = ee.date
                    
                    ) 
				");
			Response::SetArray('stats',$stats); // статистика объекта	
			// общее количество показов/кликов/
		}
		Response::SetArray('info',$info); // информация об объекте										
		break;
	/****************************\
    |*  Работа с баннерами Кредитный калькулятор  *|
    \****************************/		
	case 'add':
	case 'edit':
		$module_template = 'admin.calculator.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['credit_calculator']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['credit_calculator']." 
								WHERE id=?", $id) ;
			//предустановка ссылки на картинки на главной и в шапке
			$cnt = count($_POST);
			Response::SetString('img_link_double', $cnt==0?$info['img_link']:(!empty($_POST['img_link_double'])?$_POST['img_link_double']:false)); 					
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['calculator'][$key])) $mapping['calculator'][$key]['value'] = $info[$key];
		}
        
        $active = $db->fetch("SELECT *
                                FROM ".$sys_tables['credit_calculator']." 
                                WHERE enabled=? AND published=? AND id!=?", 1,1, $id) ;

        //список банков
        $banks = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']." WHERE ".$banks_condition,'id');
        foreach($banks as $key=>$val) $banks[$key] = $val['title'];
        $mapping['calculator']['id_agency']['values'] = $banks;
        
        //список правил при необходимости
        $rules = $db->fetchall("SELECT * FROM ".$sys_tables['credit_calculator_percent_rules']." WHERE id_calculator = ?",false,$id);
        Response::SetArray('rules',$rules);        

        if(!empty($active) && count($mapping['calculator']['type']['values'])==1) {
            unset($mapping['calculator']['enabled']);
            unset($info['enabled']);
        }

        
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
		
		Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
		//папки для картинок спецпредложений
		Response::SetString('img_folder', Config::$values['img_folders']['credit_calculator']); // папка для Кредитный калькулятор
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['calculator']);
			// замена фотографий Кредитный калькулятор
			if(!empty($_FILES)){
				foreach ($_FILES as $fname => $data){
					if ($data['error']==0) {
                        $_folder = Host::$root_path.'/'.Config::$values['img_folders']['credit_calculator'].'/'; // папка для файлов  Кредитный калькулятор
						$_temp_folder = Host::$root_path.'/img/uploads/'; // папка для файлов  Кредитный калькулятор
						$fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
						$fileParts = pathinfo($data['name']);
						$targetExt = $fileParts['extension'];
						$_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
						if (in_array(strtolower($targetExt),$fileTypes)) {
							move_uploaded_file($data['tmp_name'],$_temp_folder.$_targetFile);
                            Photos::imageResize($_temp_folder.$_targetFile,$_folder.$_targetFile,100,25,'not_cut',90);
                            if(file_exists($_temp_folder.$_targetFile) && is_file($_temp_folder.$_targetFile)) unlink($_temp_folder.$_targetFile);
							if(file_exists($_folder.$mapping['calculator'][$fname]['value']) && is_file($_folder.$mapping['calculator'][$fname]['value'])) unlink($_folder.$mapping['calculator'][$fname]['value']);
							$post_parameters[$fname] = $_targetFile;
						}
					}
				}
			}
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['calculator'][$key])) $mapping['calculator'][$key]['value'] = $post_parameters[$key];
			}

			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['calculator'][$key])) $mapping['calculator'][$key]['error'] = $value;
			}
		
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['calculator'][$key]['value'])) $info[$key] = $mapping['calculator'][$key]['value'];
				}
                $info['percent_live'] = $mapping['calculator']['percent']['value'];
                $info['percent_build'] = $mapping['calculator']['percent']['value'];
                $info['percent_commercial'] = $mapping['calculator']['percent']['value'];
                $info['percent_country'] = $mapping['calculator']['percent']['value'];

				//переопределение ссылок на картинку на главной и на картинку в шапке
				if(!empty($post_parameters['img_link_double'])) $info['img_link'] = $post_parameters['img_link_double'];
				// сохранение в БД
				if($action=='edit'){
					if( date('Y-m-d') >= date($info['date_start']) && date('Y-m-d') < date($info['date_end']) && $info['published']==2) $info['published']=1;
                    //статус - отредактирован объект
					$res = $db->updateFromArray($sys_tables['credit_calculator'], $info, 'id') or die($db->error);
				} else {
					//дата дообавления объекта
					$res = $db->insertFromArray($sys_tables['credit_calculator'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/advert_objects/credit_calculator/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['calculator']);
		break;
	case 'restore':
	case 'archive':
		$id =  empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		//значение чекбокса
		$value = Request::GetString('value',METHOD_POST);
		$status = $action=='restore'?1:3;
		if($id>0){
			$res = $db->querys("UPDATE ".$sys_tables['credit_calculator']." SET `published` = ? WHERE id=?", $status, $id) or die($db->error);
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
            $res = $db->querys("UPDATE ".$sys_tables['credit_calculator']." SET `enabled` = ? WHERE id=?", $status, $id);
            $results['setStatus'] = $db->affected_rows>0 ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
                break;
            }
        } else $ajax_result = false;
        break;
	default:
		$module_template = 'admin.calculator.list.html';
		//кол-во эл-ов в каждом блоке размещения
		 $sql = "
			SELECT  
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['credit_calculator']." WHERE `published` !=3) AS alls,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['credit_calculator']." WHERE `published` = 1 and enabled = 1) AS active,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['credit_calculator']." WHERE `published` = 3) AS archive
			FROM dual";
		$counts = $db->fetch($sql) or die($sql.$db->error);
		Response::SetArray('statuses',array(
											'active'	=>	'Активные - '.$counts['active'],
											'alls'		=>	'Все - '.$counts['alls'],
											'archive'	=>	'В архиве - '.$counts['archive']
											));
        $types = $db->fetchall("SELECT *
                                FROM ".$sys_tables['credit_calculator']." 
                                WHERE enabled=1 AND published=1") ;
		Response::SetInteger('active_banners',count($types));
        $conditions = [];
        if(!empty($filters)){
            switch($filters['status']){
                case  'active'    : $conditions['status'] = $sys_tables['credit_calculator'].'.`published` = 1 and enabled = 1';    break;
                case  'alls'    : $conditions['status'] = $sys_tables['credit_calculator'].'.`published` !=3';     break;
                case  'archive'    : $conditions['status'] = $sys_tables['credit_calculator'].'.`published` = 3';     break;
            }
        } 		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['credit_calculator'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = [];
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/advert_objects/credit_calculator'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

		$sql = "SELECT 
                        ".$sys_tables['credit_calculator'].".*, 
                        IF(".$sys_tables['credit_calculator'].".type=1,'Жилая',
                            IF(".$sys_tables['credit_calculator'].".type=2,'Стройка',
                                IF(".$sys_tables['credit_calculator'].".type=3,'Коммерческая','Загородная')
                            )
                        ) as type,
                        ".$sys_tables['agencies'].".title AS agency_title,
                        ".$sys_tables['agencies'].".id AS agency_id,
                        IF(".$sys_tables['agencies'].".id_main_photo=0,
                           '',
                           CONCAT_WS('/','".Config::$values['img_folders']['agencies']."','sm',LEFT(".$sys_tables['agencies_photos'].".name,2),".$sys_tables['agencies_photos'].".name)) as photo,
                        DATE_FORMAT(".$sys_tables['credit_calculator'].".`date_start`,'%d.%m.%Y') as `date_start`,
                        DATE_FORMAT(".$sys_tables['credit_calculator'].".`date_end`,'%d.%m.%Y') as `date_end`,
                        IF(".$sys_tables['credit_calculator'].".`date_start`<=NOW() AND ".$sys_tables['credit_calculator'].".`date_end`>=NOW(), 'true', 'false') as `compare`,


                        DATE_FORMAT(".$sys_tables['credit_calculator'].".`date_start`,'%d.%m.%Y') as `date_start`,
                        DATE_FORMAT(".$sys_tables['credit_calculator'].".`date_end`,'%d.%m.%Y') as `date_end`,
                        IF(".$sys_tables['credit_calculator'].".`date_start`<=NOW() AND ".$sys_tables['credit_calculator'].".`date_end`>=NOW(), 'true', 'false') as `compare`,                            
                        IFNULL(a.cnt_day,0) as cnt_day,
                        IFNULL(b.cnt_full,0) as cnt_full,
                        IFNULL(c.cnt_click_day,0) as cnt_click_day,
                        IFNULL(d.cnt_click_full,0) as cnt_click_full
                FROM ".$sys_tables['credit_calculator']."
                LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$sys_tables['credit_calculator_stats_show_day']." GROUP BY id_parent) a ON a.id_parent = ".$sys_tables['credit_calculator'].".id    
                LEFT JOIN (SELECT SUM(amount) as cnt_full, id_parent FROM ".$sys_tables['credit_calculator_stats_show_full']." GROUP BY id_parent) b ON b.id_parent = ".$sys_tables['credit_calculator'].".id    
                LEFT JOIN (SELECT COUNT(*) as cnt_click_day, id_parent FROM ".$sys_tables['credit_calculator_stats_click_day']." GROUP BY id_parent) c ON c.id_parent = ".$sys_tables['credit_calculator'].".id        
                LEFT JOIN (SELECT SUM(amount) as cnt_click_full, id_parent FROM ".$sys_tables['credit_calculator_stats_click_full']." GROUP BY id_parent) d ON d.id_parent = ".$sys_tables['credit_calculator'].".id       
                LEFT JOIN ".$sys_tables['agencies']."  ON ".$sys_tables['agencies'].".id = ".$sys_tables['credit_calculator'].".id_agency
                LEFT JOIN ".$sys_tables['agencies_photos']."  ON ".$sys_tables['agencies'].".id = ".$sys_tables['agencies_photos'].".id_parent";
		if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY ".$sys_tables['credit_calculator'].".id";
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