<?php
$GLOBALS['js_set'][] = '/modules/html_banners/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.banners.php');
Banners::Init();
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Баннеры'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['manager'] = $db->real_escape_string(Request::GetInteger('f_manager',METHOD_GET));
$filters['position'] = $db->real_escape_string(Request::GetInteger('f_position',METHOD_GET));
$filters['status'] = Request::GetString('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['manager'])) $get_parameters['f_manager'] = $filters['manager']; else $filters['manager'] = false;
if(!empty($filters['position'])) $get_parameters['f_position'] = $filters['position']; else $filters['position'] = false;
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
		$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
		$GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
		$GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
		$GLOBALS['js_set'][] = '/modules/html_banners/datepick_actions.js';
		//получение данных по объекту из базы
		$info = $db->fetch("SELECT 
								`id`,
                                `title`,
								`price`,
                                `zones`,
                                DATEDIFF(`date_end`,`date_start`) as `date_diff`,
  							    CONCAT('/','".Config::$values['img_folders']['banners']."','/',`img_src`) as photo
							FROM ".$sys_tables['banners']."
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
            			
            $stats = Banners::getTotalStats( $id, $date_start, $date_end );
            
			Response::SetArray('stats',$stats); // статистика объекта	
			// расчет статистики докрутки кликов
            //кол-во кликов за последние 30 дней
            $average_clicks = $db->fetch("SELECT 30*AVG(amount) as sum_amount
                                          FROM ".$sys_tables['banners_stats_click_full']." 
                                          WHERE id_parent = ? AND `date` >= CURDATE() - INTERVAL 30 DAY GROUP BY id_parent",
                                          $id); 
            Response::SetArray('average_clicks', $average_clicks);
		}
		Response::SetArray('info',$info); // информация об объекте										
	break;
    
    /*************************\
    |*  Статистика всех ТГБ   *|
    \*************************/
    case 'total_stats':
        $partner = !empty($auth->id_group)&&$auth->id_group==11;
        if (!$partner){
            $fields = array(
                    array('string','Дата')
                    ,array('number','Показы')
                    ,array('number','Переходы')
                    ,array('number','CTR, %')
             );
        } else {
             $fields = array(
                    array('string','Дата')
                    ,array('number','Переходы Найдидом')
             );
        } 
        if  (!$ajax_mode){
            // переопределяем экшн 
            $module_template = 'admin.stats.total.html';
            $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
            $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
            $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
            $GLOBALS['js_set'][] = '/js/main.js';
            $GLOBALS['js_set'][] = '/modules/html_banners/datepick_actions.js';
            $GLOBALS['js_set'][] = '/js/google.chart.api.js';
            $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js'; 
            
            Response::SetArray('data_titles',$fields);
            //получение группы пользователя "Партнер"            
            
        }
        Response::SetBoolean('partner',$partner);
        $get_parameters = Request::GetParameters(METHOD_GET);
        
        
        // если была отправка формы - выводим данные 
        if(!empty($get_parameters['submit']) || $ajax_mode){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //передача данных в шаблон
            $date_start = $get_parameters['date_start'];
            $date_end = $get_parameters['date_end'];
            $info['date_start'] = $date_start;
            $info['date_end'] = $date_end;
            
            
            $stats = $db->fetchall("
                    SELECT 
                        a.date,
                        IFNULL(a.show_amount,0) as show_amount, 
                        IFNULL(b.click_amount,0) as click_amount               
                    FROM 
                    (
                      (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as show_amount, 
                              COUNT(DISTINCT(id_parent)) as banners_per_day,
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['banners_stats_show_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') 
                          GROUP BY `date`
                        ) a
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['banners_stats_click_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') 
                          GROUP BY `date`
                         ) b ON a.date = b.date
                    ) UNION (
                        SELECT aa.date, 
                        IFNULL(aa.show_amount,0) as show_amount, 
                        IFNULL(bb.click_amount,0) as click_amount
                        FROM 
                       (   SELECT
                              'сегодня' as date, 
                              IFNULL(COUNT(*),0) as show_amount, 
                              COUNT(DISTINCT(id_parent)) as banners_per_day,
                              id_parent
                          FROM ".$sys_tables['banners_stats_show_day']."
                        ) aa
                        LEFT JOIN 
                        (
                          SELECT 
                                'сегодня' as date,
                              IFNULL(COUNT(*),0) as click_amount,                              
                              id_parent
                          FROM ".$sys_tables['banners_stats_click_day']."
                         ) bb ON aa.date = bb.date
                    )
                     
                ");    
               
                Response::SetArray('stats',$stats);
                
                //Подсчет суммарной статистики за период с прогнозом
                //вычисление лимитов - среднее кол-во в день
                $ids_list  = $db->fetchall("
                    SELECT 
                            a.id_parent as id
                    FROM ".$sys_tables['banners']."
                    LEFT JOIN (
                        SELECT 
                                id_parent
                        FROM 
                                ".$sys_tables['banners_stats_click_full']."
                        WHERE  
                                `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')     
                    ) a ON a.id_parent = ".$sys_tables['banners'].".id
                    WHERE 
                        ".$sys_tables['banners'].".enabled = 1 AND 
                        ".$sys_tables['banners'].".published = 1
                    GROUP BY id
                ");
                
                $ids = [];
                foreach($ids_list as $k=>$item) if(!empty($item['id'])) $ids[] = $item['id'];
               
                //сколько должен заказано кликов для данного промежутка времени 
                $limits = $db->fetch("
                    SELECT 
                        shows_limit,
                        DATEDIFF( STR_TO_DATE('".$date_end."', '%d.%m.%Y'), STR_TO_DATE('".$date_start."', '%d.%m.%Y')) as `datediff`,
                        ( AVG(shows_limit/DATEDIFF(date_end,date_start)) ) * ( DATEDIFF( STR_TO_DATE('".$date_end."', '%d.%m.%Y'), STR_TO_DATE('".$date_start."', '%d.%m.%Y')) ) as `limit`
                    FROM ".$sys_tables['banners']." 
                    WHERE id IN (".implode(",", $ids).")                    
                ");
                Response::SetArray('limit', $limits);
                $limit = (int) count($ids) * $limits['limit'];
                Response::SetInteger('banners_count', (int) count($ids));
                Response::SetInteger('shows_limit', (int) $limit);
                
            if (!$ajax_mode) Response::SetArray('info',$info); // информация об объекте 
            else {
                $module_template = 'admin.stats.table.html';
                if (!$partner)
                    $graphic_colors = array('#3366CC','#DC3912','#FF9900','#109618','#990099','#3cff00', '#808000', '#800000','#FF0000','#F2BB20','#0011EE');       // Цвета графиков
                else
                    $graphic_colors = array('#FF9900');       // Цвета графиков
                $data = [];
                if($stats) {
                    foreach($stats as $ind=>$item) {   // Преобразование массива
                        $arr = [];
                        foreach($item as $key=>$val){
                            if ($key!='date')
                                $arr[] = array(Convert::ToString($key),Convert::ToInt($val));
                            else
                                $arr[] = array(Convert::ToString($key),Convert::ToString($val));
                        }     
                        $s_amount = Convert::ToInt($item['show_amount']);
                        $arr[11] = $arr[12];
                        if(!empty($s_amount)) $arr[10] = array('ctr',Convert::ToFloat(number_format((Convert::ToInt($item['total_amount'])/Convert::ToInt($item['show_amount']))*100,2)));                 // CTR
                        $data[] = $arr;
                    }
                }
                $ajax_result = array(
                    'ok' => true,
                    'data' => $data,
                    'count' => count($data),
                    'height'=>300,
                    'width'=>725,
                    'fields' => $fields,
                    'colors' => $graphic_colors
                );
            }   
            
        }
        break;        
	/****************************\
    |*  Работа с баннерами   *|
    \****************************/		
	case 'add':
	case 'edit':
        $GLOBALS['js_set'][] = '/js/main.js';
        $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
        $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
		$module_template = 'admin.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['banners']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *, zones/2 AS zones
								FROM ".$sys_tables['banners']." 
								WHERE id=?", $id) ;
        }
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['value'] = $info[$key];
		}

		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
        array_walk($post_parameters,function($e){if(is_string($e)) return trim($e);});

        // формирование дополнительных данных для формы (не из основной таблицы)
        if(!empty($info['id_user']) || !empty($mapping['banners']['id_user']['value']) || !empty($post_parameters['id_user'])){
            $agency = Banners::getAgency($sys_tables['users'].".id = " . ( !empty($post_parameters['id_user']) ? $post_parameters['id_user'] : ( !empty($mapping['banners']['id_user']['value']) ? $mapping['banners']['id_user']['value'] : $info['id_user'] ) ) );
            $post_parameters['agency_title'] = $mapping['banners']['agency_title']['value'] = $agency['title'];
        }
                
        $managers = $db->fetchall("SELECT * FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
        foreach($managers as $key=>$val){
            $mapping['banners']['id_manager']['values'][$val['id']] = $val['name'];
        }				

        $banners_positions = $db->fetchall("SELECT id, CONCAT( title,' (', width, 'x', height, ')' ) as title FROM ".$sys_tables['banners_positions']." ORDER BY id");
        foreach($banners_positions as $key=>$val){
            $mapping['banners']['id_position']['values'][$val['id']] = $val['title'];
        }                

		Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
		//папки для картинок спецпредложений
		Response::SetString('img_folder', Config::$values['img_folders']['banners']); // папка для баннеров
		// если была отправка формы - начинаем обработку     
        
        $utm_campaign = !empty($post_parameters['utm_campaign']) ? $post_parameters['utm_campaign'] : ( !empty($mapping['banners']['utm_campaign']['value']) ? $mapping['banners']['utm_campaign']['value'] : false ); 
        $utm_content = !empty($post_parameters['utm_content']) ? $post_parameters['utm_content'] : ( !empty($mapping['banners']['utm_content']['value']) ? $mapping['banners']['utm_content']['value'] : false ); 
        $title = !empty($post_parameters['title']) ? $post_parameters['title'] : ( !empty($mapping['banners']['title']['value']) ? $mapping['banners']['title']['value'] : false ); 
        
        if(empty($utm_content) && !empty($title)) $mapping['banners']['utm_content']['value'] = $post_parameters['utm_content'] = Convert::chpuTitle($title);
        
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['banners']);
            
            //проверяем ссылку на переход (ссылки с кириллицей считаем рабочими)
            if(preg_match('/[А-я]/sui',$post_parameters['direct_link'])){
                $link_response = true;
            }elseif(preg_match('/^https/si',$post_parameters['direct_link'])){
                require_once('includes/functions.php');
                $link_response = get_http_response_code($post_parameters['direct_link']);
            }else $link_response = DEBUG_MODE ? true : curlThis( (!preg_match("/^https?/si",$post_parameters['direct_link']) ? "http:" : "").$post_parameters['direct_link'] );
            
            if(empty($link_response)) $errors['direct_link'] = "Ссылка не работает";
            
			// замена фотографий 
			if(!empty($_FILES)){
				foreach ($_FILES as $fname => $data){
                    if ($data['error']==0) {
                        $size = getimagesize($data['tmp_name']);
                        
                        $position = $db->fetch( " SELECT * FROM " . $sys_tables['banners_positions'] . " WHERE id = ?", $mapping['banners']['id_position']['value'] );
                        Response::SetInteger( 'img_width', $position['width'] );
                        if($size[0] != $position['width'] && $size[1] != $position['height'] ) $mapping['banners']['img_src']['error'] = 'Размер файла должен быть ' . $position['width'] . 'x' . $position['height'] . 'px. Размер вашего файла'.$size[0].'x'.$size[1].'px';
                        else{
                                
                            $_folder = Host::$root_path.'/'.Config::$values['img_folders']['banners'].'/'; // папка для файлов  Вертикальный баннер
                            $_temp_folder = Host::$root_path.'/img/uploads/'; // папка для файлов  Вертикальный баннер
                            $fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
                            $fileParts = pathinfo($data['name']);
                            $targetExt = $fileParts['extension'];
                            $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                            Photos::makeDir( $_temp_folder . $_targetFile );
                            Photos::makeDir( $_folder . $_targetFile );
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
                if(!empty($mapping['banners'][$key]) && $mapping['banners'][$key]['fieldtype']=='set') {
                    if(!empty($post_parameters[$key.'_set'])){
                        $mapping['banners'][$key]['value'] = 0;
                        foreach($post_parameters[$key.'_set'] as $pkey=>$pval){
                            $mapping['banners'][$key]['value'] += pow(2,$pkey);
                        }
                        $post_parameters[$key] = trim($mapping['banners'][$key]['value']);
                    }
                }
                elseif(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['value'] = trim($post_parameters[$key]);
            }

			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['error'] = $value;
			}
            //проверяем на дубляж метки
            if(!empty($mapping['banners']['utm']['value']) && $mapping['banners']['utm']['value'] == 1 && preg_match('#utm_#sui', $mapping['banners']['direct_link']['value'])) $errors['utm'] =  $mapping['banners']['utm']['error'] = "Нельзя добавить метки. Прямая ссылка уже содержит метки'";
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
                // подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['banners'][$key]['value'])) $info[$key] = $mapping['banners'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
                    if( date('Y-m-d') < date($info['date_end']) && $info['published']==2) $info['published']=1; 
					//статус - отредактирован объект
					$res = $db->updateFromArray($sys_tables['banners'], $info, 'id') or die($db->error);
				} else {
					//дата дообавления объекта
					$res = $db->insertFromArray($sys_tables['banners'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/advert_objects/banners/edit/'.$new_id.'/'));
							exit(0);
						}
					}
				}
               
                if(!empty($info['zones'])) $mapping['banners']['zones']['value'] /= 2;
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
        //скрывать utm при добавлении
        if($action == 'add') $mapping['banners']['utm']['fieldtype'] = 'hidden';
        
		// запись данных для отображения на странице
		Response::SetArray('data_mapping',$mapping['banners']);
		break;
	case 'restore':
	case 'archive':
		$id =  empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
		//значение чекбокса
		$value = Request::GetString('value',METHOD_POST);
		$status = $action=='restore'?1:3;
		if($id>0){
			$res = $db->query("UPDATE ".$sys_tables['banners']." SET `published` = ? WHERE id=?", $status, $id) or die($db->error);
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
            $res = $db->query("UPDATE ".$sys_tables['banners']." SET `enabled` = ? WHERE id=?", $status, $id);
            $results['setStatus'] = $db->affected_rows>0 ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
                break;
            }
        } else $ajax_result = false;
        break;
	default:
		$module_template = 'admin.list.html';
		//кол-во эл-ов в каждом блоке размещения
		 $sql = "
			SELECT  
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['banners']." WHERE `published` !=3 ) AS alls,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['banners']." WHERE `published` = 1 and enabled = 1 ) AS active,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['banners']." WHERE `published` = 3 ) AS archive
			FROM dual";
		$counts = $db->fetch($sql) or die($sql.$db->error);
		Response::SetArray('statuses',array(
											'active'	=>	'Активные - '.$counts['active'],
											'alls'		=>	'Все - '.$counts['alls'],
											'archive'	=>	'В архиве - '.$counts['archive']
											));
		// формирование дополнительных данных для формы (не из основной таблицы)
        $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
        Response::SetArray('managers', $managers);
        $positions = $db->fetchall("SELECT id,title FROM ".$sys_tables['banners_positions']." ORDER BY id");
        Response::SetArray( 'positions', $positions );
		$conditions = [];
		if(!empty($filters)){
            if(!empty($filters['manager'])) $conditions['manager'] = $sys_tables['banners'].'.id_manager = '.$db->real_escape_string($filters['manager']);
            if(!empty($filters['position'])) $conditions['position'] = $sys_tables['banners'].'.id_position = '.$db->real_escape_string($filters['position']);
                
			switch($filters['status']){
				case  'active'	: $conditions['status'] = $sys_tables['banners'] . '.`published` = 1 and enabled = 1';    break;
				case  'alls'	: $conditions['status'] = $sys_tables['banners'] . '.`published` !=3'; 	break;
				case  'archive'	: $conditions['status'] = $sys_tables['banners'] . '.`published` = 3'; 	break;
			}
		} 
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		$list = Banners::getList(false, false, $condition, false, false, $sys_tables['banners'].".id ");
        foreach($list as $k =>$item){
            $stats = Banners::getItemStats($item['id']);
            $list[$k] = array_merge($item, $stats);
        }
		// формирование списка
		Response::SetArray('list', $list);

		break;
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>