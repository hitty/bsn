<?php

//редирект на объекты, если ничего не выбрано
if(empty($this_page->page_parameters[1])) {
	header('Location: '.Host::getWebPath('/admin/advert_objects/spec_offers/objects/'));
	exit(0);
}
$GLOBALS['js_set'][] = '/modules/spec_offers/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Спецпредложения'));
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['category'] = $db->real_escape_string(Request::GetInteger('f_category',METHOD_GET));
$filters['credit_clicks'] = Request::GetInteger('f_credit_clicks',METHOD_GET);
$filters['status'] = Request::GetString('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['category'])) $get_parameters['f_category'] = $filters['category']; else $filters['category'] = 1;
if(!empty($filters['credit_clicks'])) $get_parameters['f_credit_clicks'] = $filters['credit_clicks']; else $filters['credit_clicks'] = false;
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; else $filters['status'] = 'active';

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
    // ежемесячная статистика ТГБ
    case 'monthly_stats':
        $module_template = 'admin.stats.monthly.html';
        $stats = $db->fetchall("
                                 
                                 (
                                    SELECT SUM( amount) as amount, type, 'Последние 30 дней' as month_date, '' as year_date
                                    FROM ".$sys_tables['tgb_daily_show_stats']."
                                    WHERE DATE(`date`) > CURDATE( ) - INTERVAL 31 DAY AND 
                                    DATE(  `date` ) < CURDATE( ) - INTERVAL 1 DAY 
                                    GROUP BY type
                                 )
                                 UNION 
                                 (
                                    SELECT amount, type, date_format(date, '%m.%y') as month_date, date_format(date, '%Y%m') as year_date
                                    FROM ".$sys_tables['tgb_monthly_show_stats']."
                                    GROUP BY year_date, type
                                    ORDER BY year_date DESC, type
                                    LIMIT 9
                                 )
                                ");
        Response::SetArray('stats',$stats); 
        break;
    /*************************\
    |*  Статистика всех ТГБ   *|
    \*************************/
    case 'total_stats':
        $partner = !empty($auth->id_group) && $auth->id_group==11;
        Response::SetBoolean('partner',$partner);
        
        if (!$partner){
            $fields = array(
                array('string','Дата')
                ,array('number','Показы')
                ,array('number','Переходы BSN')
                ,array('number','Переходы Пингола')
                ,array('number','Показы 1 ТГБ')
                ,array('number','Переходы 1 ТГБ')
                ,array('number','Кол-во ТГБ в день')
                ,array('number','CTR, %')
            );    

            
        } else {
            $fields = array(
                array('string','Дата')
                ,array('number','Переходы Пингола')
                );
        }
         if  (!$ajax_mode){ 
            // переопределяем экшн 
            $module_template = 'admin.stats.total.html';
            $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
            $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
            $GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
            $GLOBALS['js_set'][] = '/js/google.chart.api.js';
            $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js';
            
            Response::SetArray('data_titles',$fields);
            //получение группы пользователя "Партнер"            
            Response::SetBoolean('partner',!empty($auth->id_group)&& $auth->id_group==12);
         }
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
                    SELECT a.date, IFNULL(a.show_amount,0) as show_amount, IFNULL(b.click_amount,0) as click_bsn_amount, IFNULL(c.click_amount,0) as click_pingola_amount, IFNULL(a.show_amount,0)/a.tgb_per_day as show_per_day, IFNULL(b.click_amount,0)/a.tgb_per_day as click_per_day, a.tgb_per_day  as tgb_per_day
                    FROM 
                    (
                      (
                          SELECT 
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date,
                                COUNT(DISTINCT(id_parent)) as tgb_per_day,
                                SUM(IFNULL(`amount`,0)) as show_amount 
                          FROM ".$sys_tables['spec_objects_stats_show_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND 
                              amount > 500
                          GROUP BY `date`
                        ) a
                        LEFT JOIN 
                        (
                          SELECT  
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date,
                              SUM(IFNULL(`amount`,0)) as click_amount
                          FROM ".$sys_tables['spec_objects_stats_click_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 1 
                          GROUP BY `date`
                         ) b ON a.date = b.date
                        LEFT JOIN
                        (
                          SELECT
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date, 
                              SUM(IFNULL(`amount`,0)) as click_amount 
                          FROM ".$sys_tables['spec_objects_stats_click_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 3
                          GROUP BY `date`
                         ) c ON a.date = c.date
                    ) UNION (
                        SELECT aa.date, IFNULL(aa.show_amount,0) as show_amount, IFNULL(bb.click_amount,0) as click_bsn_amount, IFNULL(cc.click_amount,0) as click_pingola_amount, IFNULL(aa.show_amount,0)/aa.tgb_per_day as show_per_day , IFNULL(bb.click_amount,0)/aa.tgb_per_day as click_per_day, aa.tgb_per_day as   tgb_per_day
                        FROM 
                       (   SELECT 
                              'сегодня' as date,
                              IFNULL(COUNT(*),0) as show_amount,
                              COUNT(DISTINCT(id_parent)) as tgb_per_day,
                              id_parent
                          FROM ".$sys_tables['spec_objects_stats_show_day']."
                        ) aa
                        LEFT JOIN 
                        (
                          SELECT
                              'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,
                              id_parent
                          FROM ".$sys_tables['spec_objects_stats_click_day']."
                          WHERE `from` = 1
                         ) bb ON aa.date = bb.date
                        LEFT JOIN
                        (
                          SELECT 
                              'сегодня' as date,
                              IFNULL(COUNT(*),0) as click_amount,
                              id_parent
                          FROM ".$sys_tables['spec_objects_stats_click_day']."
                          WHERE `from` = 3
                         ) cc ON aa.date = cc.date
                    
                    )
                     
                "); 
                
             Response::SetArray('stats',$stats); // статистика объекта    
            // общее количество показов/кликов/
             if (!$ajax_mode) Response::SetArray('info',$info); // информация об объекте
             else {
                $module_template = 'admin.stats.table.html';
                if (!$partner)
                    $graphic_colors = array('#3366CC','#DC3912','#FF9900','#109618','#990099','#110099','#220099','#330099');       // Цвета графиков
                else
                    $graphic_colors = array('#FF9900'); 
                $data = array();
                if($stats) {
                    foreach($stats as $ind=>$item) {   // Преобразование массива
                        $arr = array();
                        foreach($item as $key=>$val){
                            if ($key!='date')
                                $arr[] = array(Convert::ToString($key),Convert::ToInt($val));
                            else
                                $arr[] = array(Convert::ToString($key),Convert::ToString($val));
                        }     
                        $clicks = Convert::ToInt($item['click_pingola_amount'])+Convert::ToInt($item['click_bsn_amount']);
                        $arr[] = array('clicks',$clicks);                                                                           // Переходы
                        $arr[] = array('ctr',Convert::ToFloat(number_format((Convert::ToInt($clicks)/Convert::ToInt($item['show_amount']))*100,2)));                 // CTR
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
	/*************************\
    |*  Работа со статитикой *|
    \*************************/
    case 'stats':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		$module_template = 'admin.stats.html';
		$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
		$GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
		$GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
		$GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js';
        switch($action){
			case 'objects':
				//получение данных по объекту из базы
				$info = $db->fetch("SELECT 
										`id`,
										`title`,
                                        `price`,
                                        DATEDIFF(`date_end`,`date_start`) as `date_diff`,
										'объект' as `ru_type`,
										'objects' as `url_type`,
										IF(main_img_link='',
										  IF(head_img_link='',
											IF(main_img_src='',
											  CONCAT_WS('/','/".Config::$values['img_folders']['spec_offers']."',head_img_src),
											  CONCAT_WS('/','/".Config::$values['img_folders']['spec_offers']."',main_img_src)),
										  head_img_link),
										main_img_link) as photo
									FROM ".$sys_tables['spec_offers_objects']."
									WHERE `id` = ?",$id);      echo $db->error;       
				$post_parameters = Request::GetParameters(METHOD_POST);
				// если была отправка формы - выводим данные 
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					//передача данных в шаблон
					$date_start = $post_parameters['date_start'];
					$date_end = $post_parameters['date_end'];
					$info['date_start'] = $date_start;
                    $info['date_end'] = $date_end;
                    $info['price_on_period'] =  !empty($post_parameters['price_on_period']) ? $post_parameters['price_on_period'] : '';
                    $info['wanted_price'] =  !empty($post_parameters['wanted_price']) ? $post_parameters['wanted_price'] : '';
                    //определение выбранного временного интервала
                    $datetime1 = new DateTime($date_start);
                    $datetime2 = new DateTime($date_end);
                    $interval = $datetime1->diff($datetime2);
                    $info['interval'] = $interval->format('%a')+1;

                    $stats = $db->fetchall("
                        SELECT IFNULL(a.show_amount,0) as show_amount, IFNULL(b.click_amount,0) as click_bsn_amount, IFNULL(c.click_amount,0) as click_pingola_amount, a.date FROM 
                        (
                            (
                              SELECT 
                                  SUM(IFNULL(`amount`,0)) as show_amount, 
                                  DATE_FORMAT(`date`,'%d.%m.%Y') as date
                              FROM ".$sys_tables['spec_objects_stats_show_full']."
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
                              FROM ".$sys_tables['spec_objects_stats_click_full']."
                              WHERE
                                  `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                  `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 1
                              GROUP BY `date`
                             ) b ON a.date = b.date
                            LEFT JOIN 
                            (
                              SELECT 
                                  SUM(IFNULL(`amount`,0)) as click_amount, 
                                  DATE_FORMAT(`date`,'%d.%m.%Y') as date
                              FROM ".$sys_tables['spec_objects_stats_click_full']."
                              WHERE
                                  `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                  `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 3
                              GROUP BY `date`
                             ) c ON a.date = c.date
                        ) UNION (
                            SELECT IFNULL(aa.show_amount,0) as show_amount, IFNULL(bb.click_amount,0) as click_bsn_amount, IFNULL(cc.click_amount,0) as click_pingola_amount, aa.date FROM 
                           (   SELECT 
                                  IFNULL(COUNT(*),0) as show_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['spec_objects_stats_show_day']."
                              WHERE `id_parent` = ".$id."
                            ) aa
                            LEFT JOIN 
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['spec_objects_stats_click_day']."
                              WHERE  `id_parent` = ".$id." AND `from` = 1
                             ) bb ON aa.id_parent = bb.id_parent
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['spec_objects_stats_click_day']."
                              WHERE  `id_parent` = ".$id." AND `from` = 3
                             ) cc ON aa.id_parent = cc.id_parent
                        
                        )
                    ");
					Response::SetArray('stats',$stats); // статистика объекта	
                    // расчет статистики докрутки кликов
                    //кол-во зарезервированных кликов найдидом
                    $advert_clicks = $db->fetch("SELECT * FROM ".$sys_tables['spec_objects_credits']." WHERE id_object = ?",$id);
                    Response::SetArray('advert_clicks', $advert_clicks);
                    //кол-во кликов за последние 30 дней
                    $average_clicks = $db->fetch("SELECT 30*AVG(amount) as sum_amount
                                                  FROM ".$sys_tables['spec_objects_stats_click_full']." 
                                                  WHERE `from` = ?  AND id_parent = ? AND `date` >= CURDATE() - INTERVAL 30 DAY GROUP BY id_parent",
                                                  1, $id); 
                    Response::SetArray('average_clicks', $average_clicks);

                }
				Response::SetArray('info',$info); // информация об объекте										
												
				break;
			case 'packets':
			//получение данных по объекту из базы
				$info = $db->fetch("SELECT 
										`id`,
										`title`,
										'пакет' as `ru_type`,
										'packets' as `url_type`,
										IF(main_img_link='',
										  IF(head_img_link='',
											IF(main_img_src='',
											  CONCAT_WS('/','/".Config::$values['img_folders']['spec_offers']."',head_img_src),
											  CONCAT_WS('/','/".Config::$values['img_folders']['spec_offers']."',main_img_src)),
										  head_img_link),
										main_img_link) as photo
									FROM ".$sys_tables['spec_offers_packets']."
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
                            (
                                SELECT 
                                    SUM(IFNULL(`amount`,0)) as show_amount, 
                                    DATE_FORMAT(`date`,'%d.%m.%Y') as date
                                FROM ".$sys_tables['spec_packets_stats_show_full']."
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
                                FROM ".$sys_tables['spec_packets_stats_click_full']."
                                WHERE
                                    `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                    `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
                                GROUP BY `date`
                            ) b ON a.date = b.date
                        ) UNION (
                            SELECT IFNULL(c.show_amount,0) as show_amount, IFNULL(d.click_amount,0) as click_amount, c.date FROM 
                            (
                                SELECT 
                                    IFNULL(COUNT(*),0) as show_amount, 
                                    'сегодня' as date,
                                    id_parent
                                FROM ".$sys_tables['spec_packets_stats_show_day']."
                                WHERE `id_parent` = ".$id."
                            ) c
                            LEFT JOIN 
                            (
                                SELECT 
                                    IFNULL(COUNT(*),0) as click_amount, 
                                    'сегодня' as date,
                                    id_parent
                                FROM ".$sys_tables['spec_packets_stats_click_day']."
                                WHERE `id_parent` = ".$id."
                            ) d ON c.id_parent = d.id_parent

                        )
                    ");
					Response::SetArray('stats',$stats); // статистика объекта	
					// общее количество показов/кликов/
				}
				Response::SetArray('info',$info); // информация об объекте				
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
					$info = $db->prepareNewRecord($sys_tables['spec_offers_categories']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['spec_offers_categories']." 
										WHERE id=?", $id) ;
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
		
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['categories']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['categories'][$key]['value'])) $info[$key] = $mapping['categories'][$key]['value'];
						}
						// сохранение в БД
						if($action=='edit'){
							$res = $db->updateFromArray($sys_tables['spec_offers_categories'], $info, 'id');
						} else {
							$res = $db->insertFromArray($sys_tables['spec_offers_categories'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/advert_objects/spec_offers/categories/edit/'.$new_id.'/'));
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
				$res = $db->query("DELETE FROM ".$sys_tables['spec_offers_categories']." WHERE id=?", $id);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			default:
				$module_template = 'admin.categories.list.html';
				// формирование фильтра по названию
				$conditions = array();
				if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['spec_offers_categories'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = array();
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/advert_objects/spec_offers/categories'                  // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
		
				$sql = "SELECT id,title FROM ".$sys_tables['spec_offers_categories'];
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
    /****************************************\
    |*  Работа с объектами спецпрежложений  *|
    \****************************************/		
	case 'objects':
		$action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		switch($action){
            /*********************************************\
            |*  управление лимитом кредитных баннеров   *|
            \*********************************************/        
            case 'banner_credit':
                if(!empty($ajax_mode)){
                    $id_object = Request::GetInteger('id_object',METHOD_POST);    
                    $date_start = Request::GetString('date_start',METHOD_POST);    
                    $date_end = Request::GetString('date_end',METHOD_POST);    
                    $limit = Request::GetInteger('limit',METHOD_POST);    
                    $day_limit = Request::GetInteger('day_limit',METHOD_POST);    
                    //Определение менеджера
                    if(!empty($auth->email)){
                        $manager = $db->fetch("SELECT * FROM ".$sys_tables['managers']." WHERE email = ? AND bsn_manager = ?",$auth->email,1);
                        $db->query("INSERT INTO ".$sys_tables['spec_objects_credits']." SET id_object=?, id_manager=?, date_start=?, date_end=?, `limit`=?, day_limit=?",
                                    $id_object, $manager['id'], date( 'Y-m-d H:i:s', strtotime($date_start)),  date( 'Y-m-d H:i:s', strtotime($date_end)), $limit, $day_limit
                        );
                        $ajax_result['lq_1'] = '';
                        $db->query("UPDATE ".$sys_tables['managers']." SET pingola_credit_limit = ? WHERE email = ? AND bsn_manager = ?", $manager['pingola_credit_limit']-$limit,$auth->email,1);
                        $ajax_result['lq_2'] = '';
                    }
                }
                break;
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
								$list = Photos::getList('spec_offers_objects',$id);
								if(!empty($list)){
									$ajax_result['ok'] = true;
									$ajax_result['list'] = $list;
									$ajax_result['folder'] = Config::$values['img_folders']['spec_offers_objects'];
								} else $ajax_result['error'] = 'Невозможно построить список фотографий';
							} else $ajax_result['error'] = 'Неверные входные параметры';
							break;
						case 'add':
							//загрузка фотографий
							//id текущей новости
							$id = Request::GetInteger('id', METHOD_POST);				
							if(!empty($id)){
                                //default sizes removed
								$res = Photos::Add('spec_offers_objects',$id,false,false,false,false,false,true);
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
                                $res = Photos::setTitle('spec_offers_objects',$id, $title);
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
								$res = Photos::Delete('spec_offers_objects',$id_photo);
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
								$res = Photos::setMain('spec_offers_objects', $id, $id_photo);
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
								$res = Photos::Sort('spec_offers_objects', $order);
								if(!empty($res)){
									$ajax_result['ok'] = true;
								} else $ajax_result['error'] = 'Невозможно отсортировать';
							} else $ajax_result['error'] = 'Неверные входные параметры';
							break;
					}
				}
				break;			
			case 'add':
			case 'edit':
				$GLOBALS['js_set'][] = '/js/main.js';
                $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
				$GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
				$GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
				$GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
                $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
		
				$module_template = 'admin.objects.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['spec_offers_objects']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['spec_offers_objects']." 
										WHERE id=?", $id) ;
					//предустановка ссылки на картинки на главной и в шапке
					$cnt = count($_POST);
					Response::SetString('main_img_link_double', $cnt==0?$info['main_img_link']:$_POST['main_img_link_double']); 					
					Response::SetString('head_img_link_double', $cnt==0?$info['head_img_link']:$_POST['head_img_link_double']); 					
                    if(!empty($auth->email)){
                        $manager = $db->fetch("SELECT * FROM ".$sys_tables['managers']." WHERE email = ? AND bsn_manager = ?",$auth->email,1);
                        if(!empty($manager)) Response::SetArray('manager',$manager);
                        else Response::SetBoolean('disabled',true);
                        //поиск установленных кредитов
                        $banner_credits = $db->fetch("SELECT *,
                                                            DATE_FORMAT(date_start, '%e %M %Y') as date_start, 
                                                            DATE_FORMAT(date_end, '%e %M %Y') as date_end
                                                            FROM ".$sys_tables['spec_objects_credits']." WHERE id_object = ?",$id);
                        Response::SetArray('banner_credits',$banner_credits);
                        
                    }

                }
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['objects'][$key])) $mapping['objects'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
				// формирование дополнительных данных для формы (не из основной таблицы)
				$categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['spec_offers_categories']." ORDER BY id");
				foreach($categories as $key=>$val){
					$mapping['objects']['id_category']['values'][$val['id']] = $val['title'];
				}
				$packets = $db->fetchall("SELECT id,title FROM ".$sys_tables['spec_offers_packets']." ORDER BY title ");
				foreach($packets as $key=>$val){
					$mapping['objects']['id_packet']['values'][$val['id']] = $val['title'];
				}
				Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
				//папки для картинок спецпредложений
				Response::SetString('main_img_folder', Config::$values['img_folders']['spec_offers']); // папка для фото 150x150
				Response::SetString('head_img_folder', Config::$values['img_folders']['spec_offers']); // папка для фото 180x90 (шапка)
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					// замена фотографий на главной и/или в шапке
					if(!empty($_FILES)){
						foreach ($_FILES as $fname => $data){
							if ($data['error']==0) $post_parameters[$fname] = replaceSpecFoto($mapping['objects'][$fname]['value'],$data,$fname);							
						}
					}
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['objects'][$key])) $mapping['objects'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['objects']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['objects'][$key])) $mapping['objects'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
                                if(isset($mapping['objects'][$key]['value']))$info[$key] = $mapping['objects'][$key]['value'];
						}
						//переопределение ссылок на картинку на главной и на картинку в шапке
						$info['main_img_link'] = !empty($post_parameters['main_img_link_double']) ? $post_parameters['main_img_link_double'] : '';
						$info['head_img_link'] = !empty($post_parameters['head_img_link_double']) ? $post_parameters['head_img_link_double'] : '';
						// сохранение в БД
						if($action=='edit'){
							if( date('Y-m-d') >= date($info['date_start']) && date('Y-m-d') < date($info['date_end']) && (empty($info['published']) || $info['published']==2)) $info['published']=1;
                            //статус - отредактирован объект
							$info['object_status'] = 2;
							$res = $db->updateFromArray($sys_tables['spec_offers_objects'], $info, 'id');
						} else {
							//дата дообавления объекта
							$res = $db->insertFromArray($sys_tables['spec_offers_objects'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/advert_objects/spec_offers/objects/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['objects']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$del_photos = Photos::DeleteAll('spec_offers_objects',$id);
				$res = $db->query("DELETE FROM ".$sys_tables['spec_offers_objects']." WHERE id=?", $id);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			case 'setStatus':
				//установка флагов для объектов
				$id = Request::GetInteger('id',METHOD_POST);
				//значение чекбокса
				$value = Request::GetString('value',METHOD_POST);
				$status = $value == 'checked'?1:2;
				//флаг
				$flag_name = Request::GetString('flag',METHOD_POST);
				if($id>0 && in_array($flag_name,array('base_page_flag','first_page_flag','first_page_head_flag','inestate_flag'))){
					$res = $db->query("UPDATE ".$sys_tables['spec_offers_objects']." SET `".$flag_name."` = ? WHERE id=?", $status, $id);
					$results['setStatus'] = ($res && $db->affected_rows) ? $id : -1;
					if($ajax_mode){
						$ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
						break;
					}
				} else $ajax_result = false;
			default:
				$module_template = 'admin.objects.list.html';
				// формирование списка
				$categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['spec_offers_categories']." ORDER BY title");
				Response::SetArray('categories',$categories);
				//кол-во эл-ов в каждом блоке размещения
				 $sql = "
				 SELECT  
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_objects']." WHERE `id_category` = ".$filters['category']." ) AS alls,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_objects']." WHERE `id_category` = ".$filters['category']." AND (`base_page_flag` = 1 OR `first_page_flag` = 1 OR `first_page_head_flag` = 1 OR `inestate_flag` = 1)) AS active,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_objects']." WHERE `id_category` = ".$filters['category']." AND `base_page_flag` = 1 ) AS base,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_objects']." WHERE `id_category` = ".$filters['category']." AND `first_page_flag` = 1 ) AS fp,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_objects']." WHERE `id_category` = ".$filters['category']." AND `first_page_head_flag` = 1 ) AS fp_head,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_objects']." WHERE `id_category` = ".$filters['category']." AND `inestate_flag` = 1 ) AS inest
				FROM dual";
				$counts = $db->fetch($sql);
				Response::SetArray('statuses',array(
													'active'	=>	'Активные - '.$counts['active'],
													'alls'		=>	'все - '.$counts['alls'],
													'base'		=>	'Основной блок - '.$counts['base'],
													'fp'		=>	'На главной - '.$counts['fp'],
													'fp_head'	=>	'В шапке - '.$counts['fp_head'],
													'inest'		=>	'ТГБ - '.$counts['inest']
													));
				$conditions = array();
				if(!empty($filters)){
					if(!empty($filters['title'])) $conditions['title'] = "obj.`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                    if(!empty($filters['credit_clicks'])) $conditions['credit_clicks'] = $sys_tables['spec_objects_credits'].".id".($filters['credit_clicks']==1?'>0':' IS NULL');
					$conditions['category'] = "obj.id_category = ".$filters['category'];
					if(!empty($filters['status'])) {
						switch($filters['status']){
							case 'active': 
								$conditions['status'] = '(obj.`base_page_flag` = 1 OR obj.`first_page_flag` = 1 OR obj.`first_page_head_flag` = 1  OR obj.`inestate_flag` = 1)';
								break;
							case  'base': $conditions['status'] = 'obj.`base_page_flag` = 1'; break;
							case  'fp': $conditions['status'] = 'obj.`first_page_flag` = 1 '; break;
							case  'fp_head': $conditions['status'] = 'obj.`first_page_head_flag` = 1'; break;
							case  'inest': $conditions['status'] = 'obj.`inestate_flag` = 1 '; break;
						}
					}
				} 
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
		
				$sql = "SELECT 
							obj.id, 
							obj.title,
							obj.agent_title,  
							obj.base_page_flag,  
							obj.first_page_flag,  
							obj.first_page_head_flag,  
                            obj.inestate_flag,  
                            DATE_FORMAT(obj.`date_start`,'%d.%m.%Y') as `date_start`,
                            DATE_FORMAT(obj.`date_end`,'%d.%m.%Y') as `date_end`,
							IF(obj.main_img_link='',
							  IF(obj.head_img_link='',
								IF(obj.main_img_src='',
								  CONCAT_WS('/','/".Config::$values['img_folders']['spec_offers']."',obj.head_img_src),
								  CONCAT_WS('/','/".Config::$values['img_folders']['spec_offers']."',obj.main_img_src)),
							  obj.head_img_link),
							obj.main_img_link) as photo,
							p.title as packet_title,
                            ".$sys_tables['spec_objects_credits'].".id as credit_banner_id,
                            ".$sys_tables['spec_objects_credits'].".day_limit,
                            IF(obj.`date_start`<=NOW() AND obj.`date_end`>=NOW(), 'true', 'false') as `compare`,
                            IFNULL(a.cnt_day,0) as cnt_day,
                            IFNULL(b.cnt_full,0) as cnt_full,
                            IFNULL(e.cnt_full_yesterday,0) as cnt_full_yesterday,
                            IFNULL(ccc.cnt_pingola_click_day,0) as cnt_pingola_click_day,
                            cc.cnt_bsn_click_day,
                            IFNULL(IFNULL(cc.cnt_bsn_click_day,0) + IFNULL(cnt_pingola_click_day,0),0)  as cnt_click_day,
                            IFNULL(IFNULL(ff.cnt_bsn_click_full_yesterday,0) + IFNULL(fff.cnt_pingola_click_full_yesterday,0),0) as cnt_click_full_yesterday                            
						FROM ".$sys_tables['spec_offers_objects']." obj";
                $sql .= " LEFT JOIN ".$sys_tables['spec_objects_credits']." ON ".$sys_tables['spec_objects_credits'].".id_object = obj.id";        
				$sql .= " LEFT JOIN ".$sys_tables['spec_offers_packets']." p ON p.id = obj.id_packet";
                $sql .= " LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent                          FROM ".$sys_tables['spec_objects_stats_show_day']." GROUP BY id_parent) a ON a.id_parent = obj.id";        
                $sql .= " LEFT JOIN (SELECT SUM(amount) as cnt_full, id_parent                      FROM ".$sys_tables['spec_objects_stats_show_full']." GROUP BY id_parent) b ON b.id_parent = obj.id";        
                $sql .= " LEFT JOIN (SELECT amount as cnt_full_yesterday, id_parent                 FROM ".$sys_tables['spec_objects_stats_show_full']."             WHERE date = CURDATE() - INTERVAL 1 DAY GROUP BY id_parent) e ON e.id_parent = obj.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cnt_bsn_click_day, id_parent                FROM ".$sys_tables['spec_objects_stats_click_day']."   WHERE `from` = 1   GROUP BY id_parent) cc ON cc.id_parent = obj.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cnt_pingola_click_day, id_parent            FROM ".$sys_tables['spec_objects_stats_click_day']."    WHERE `from` = 3 GROUP BY id_parent) ccc ON ccc.id_parent = obj.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(SUM(amount),0) as cnt_bsn_click_full, id_parent            FROM ".$sys_tables['spec_objects_stats_click_full']."  WHERE `from` = 1   GROUP BY id_parent) dd ON dd.id_parent = obj.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(SUM(amount),0) as cnt_pingola_click_full, id_parent        FROM ".$sys_tables['spec_objects_stats_click_full']."   WHERE `from` = 3   GROUP BY id_parent) ddd ON ddd.id_parent = obj.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(amount,0) as cnt_bsn_click_full_yesterday, id_parent       FROM ".$sys_tables['spec_objects_stats_click_full']."  WHERE `from` = 1  AND  date = CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) ff ON ff.id_parent = obj.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(amount,0) as cnt_pingola_click_full_yesterday, id_parent   FROM ".$sys_tables['spec_objects_stats_click_full']."   WHERE `from` = 3  AND  date = CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) fff ON fff.id_parent = obj.id";
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " GROUP BY obj.id";
				$sql .= " ORDER BY obj.id DESC";
				$list = $db->fetchall($sql);           
				// формирование списка
				Response::SetArray('list', $list);
				break;
			}
		break;

    /****************************************\
    |*  Работа с пакетами спецпрежложений  *|
    \****************************************/		
	case 'packets':
		$action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		switch($action){
			case 'add':
			case 'edit':
				$GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
				$GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
				$GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
				$GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
		
				$module_template = 'admin.packets.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['spec_offers_packets']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *
										FROM ".$sys_tables['spec_offers_packets']." 
										WHERE id=?", $id) ;
					//предустановка ссылки на картинки на главной и в шапке
					$cnt = count($_POST);
					Response::SetString('main_img_link_double', $cnt==0?$info['main_img_link']:$_POST['main_img_link_double']); 					
					Response::SetString('head_img_link_double', $cnt==0?$info['head_img_link']:$_POST['head_img_link_double']); 					
				}
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['packets'][$key])) $mapping['packets'][$key]['value'] = $info[$key];
				}
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
				// формирование дополнительных данных для формы (не из основной таблицы)
				$categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['spec_offers_categories']." ORDER BY id");
				foreach($categories as $key=>$val){
					$mapping['packets']['id_category']['values'][$val['id']] = $val['title'];
				}
				Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
				//папки для картинок спецпредложений
				Response::SetString('main_img_folder', Config::$values['img_folders']['spec_offers']); // папка для фото 150x150
				Response::SetString('head_img_folder', Config::$values['img_folders']['spec_offers']); // папка для фото 180x90 (шапка)
				// если была отправка формы - начинаем обработку
				if(!empty($post_parameters['submit'])){
					// замена фотографий на главной и/или в шапке
					if(!empty($_FILES)){
						foreach ($_FILES as $fname => $data){
							if ($data['error']==0) $post_parameters[$fname] = replaceSpecFoto($mapping['packets'][$fname]['value'],$data,$fname);							
						}
					}
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
					foreach($post_parameters as $key=>$field){
						if(!empty($mapping['packets'][$key])) $mapping['packets'][$key]['value'] = $post_parameters[$key];
					}
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['packets']);
					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['packets'][$key])) $mapping['packets'][$key]['error'] = $value;
					}
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
						// подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['packets'][$key]['value'])) $info[$key] = $mapping['packets'][$key]['value'];
						}
						//переопределение ссылок на картинку на главной и на картинку в шапке
						$info['main_img_link'] = $post_parameters['main_img_link_double'];
						$info['head_img_link'] = $post_parameters['head_img_link_double'];
						// сохранение в БД
						if($action=='edit'){
							//статус - отредактирован объект
							$info['object_status'] = 2;
							$res = $db->updateFromArray($sys_tables['spec_offers_packets'], $info, 'id');
						} else {
							//дата дообавления объекта
							$info['idate'] = date('Y-m-d');
							$res = $db->insertFromArray($sys_tables['spec_offers_packets'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/advert_objects/spec_offers/packets/edit/'.$new_id.'/'));
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
				Response::SetArray('data_mapping',$mapping['packets']);
				break;
			case 'del':
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				$del_photos = Photos::DeleteAll('spec_offers_packets',$id);
				$res = $db->query("DELETE FROM ".$sys_tables['spec_offers_packets']." WHERE id=?", $id);
				$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
				if($ajax_mode){
					$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
					break;
				}
			case 'setStatus':
				//установка флагов для объектов
				$id = Request::GetInteger('id',METHOD_POST);
				//значение чекбокса
				$value = Request::GetString('value',METHOD_POST);
				$status = $value == 'checked'?1:2;
				//флаг
				$flag_name = Request::GetString('flag',METHOD_POST);
				if($id>0 && in_array($flag_name,array('base_page_flag','first_page_flag','first_page_head_flag','inestate_flag'))){
					$res = $db->query("UPDATE ".$sys_tables['spec_offers_packets']." SET `".$flag_name."` = ? WHERE id=?", $status, $id);
					$results['setStatus'] = ($res && $db->affected_rows) ? $id : -1;
					if($ajax_mode){
						$ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
						break;
					}
				} else $ajax_result = false;
			default:
				$module_template = 'admin.packets.list.html';
				//кол-во эл-ов в каждом блоке размещения
				 $sql = "
				 SELECT  
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_packets']." ) AS alls,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_packets']." WHERE (`base_page_flag` = 1 OR `first_page_flag` = 1 OR `first_page_head_flag` = 1 OR `inestate_flag` = 1)) AS active,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_packets']." WHERE `base_page_flag` = 1 ) AS base,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_packets']." WHERE `first_page_flag` = 1 ) AS fp,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_packets']." WHERE `first_page_head_flag` = 1 ) AS fp_head,
					(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['spec_offers_packets']." WHERE `inestate_flag` = 1 ) AS inest
				FROM dual";
				$counts = $db->fetch($sql);
				Response::SetArray('statuses',array(
													'active'	=>	'Активные - '.$counts['active'],
													'alls'		=>	'все - '.$counts['alls'],
													'base'		=>	'Основной блок - '.$counts['base'],
													'fp'		=>	'На главной - '.$counts['fp'],
													'fp_head'	=>	'В шапке - '.$counts['fp_head'],
													'inest'		=>	'ТГБ - '.$counts['inest']
													));
				$conditions = array();
				if(!empty($filters)){
					if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
					if(!empty($filters['status'])) {
						switch($filters['status']){
							case 'active': 
								$conditions['status'] = '(`base_page_flag` = 1 OR `first_page_flag` = 1 OR `first_page_head_flag` = 1  OR `inestate_flag` = 1)';
								break;
							case  'base': $conditions['status'] = '`base_page_flag` = 1'; break;
							case  'fp': $conditions['status'] = '`first_page_flag` = 1 '; break;
							case  'fp_head': $conditions['status'] = '`first_page_head_flag` = 1'; break;
							case  'inest': $conditions['status'] = '`inestate_flag` = 1 '; break;
						}
					}
				} 
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				// создаем пагинатор для списка
				$paginator = new Paginator($sys_tables['spec_offers_packets'], 30, $condition);
				// get-параметры для ссылок пагинатора
				$get_in_paginator = array();
				foreach($get_parameters as $gk=>$gv){
					if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
				}
				// ссылка пагинатора
				$paginator->link_prefix = '/admin/advert_packets/spec_offers/packets'                // модуль
										  ."/?"                                       // конечный слеш и начало GET-строки
										  .implode('&',$get_in_paginator)             // GET-строка
										  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
				if($paginator->pages_count>0 && $paginator->pages_count<$page){
					Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
					exit(0);
				}
		
				$sql = "SELECT 
							id, 
							title,
							base_page_flag,  
							first_page_flag,  
							first_page_head_flag,  
                            inestate_flag,  
                            DATE_FORMAT(`date_start`,'%d.%m.%Y') as `date_start`,
                            DATE_FORMAT(`date_end`,'%d.%m.%Y') as `date_end`,
							IF(main_img_link='',
							  IF(head_img_link='',
								IF(main_img_src='',
								  CONCAT_WS('/','/".Config::$values['img_folders']['spec_offers']."',head_img_src),
								  CONCAT_WS('/','/".Config::$values['img_folders']['spec_offers']."',main_img_src)),
							  head_img_link),
							main_img_link) as photo,
                            IF(`date_start`<=NOW() AND `date_end`>=NOW(), 'true', 'false') as `compare`,
                            IFNULL(a.cnt_day,0) as cnt_day,
                                IFNULL(b.cnt_full,0) as cnt_full,
                                IFNULL(e.cnt_full_yesterday,0) as cnt_full_yesterday,
                                IFNULL(ccc.cnt_pingola_click_day,0) as cnt_pingola_click_day,
                                cc.cnt_bsn_click_day,
                                ccc.cnt_pingola_click_day,
                                IFNULL(IFNULL(cc.cnt_bsn_click_day,0) + IFNULL(ccc.cnt_pingola_click_day,0),0) as cnt_click_day,
                                IFNULL(IFNULL(dd.cnt_bsn_click_full,0) + IFNULL(ddd.cnt_pingola_click_full,0),0) as cnt_click_full,
                                IFNULL(IFNULL(ff.cnt_bsn_click_full_yesterday,0) + IFNULL(fff.cnt_pingola_click_full_yesterday,0),0) as cnt_click_full_yesterday                            
						FROM ".$sys_tables['spec_offers_packets']." pac";
                $sql .= " LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$sys_tables['spec_packets_stats_show_day']." GROUP BY id_parent) a ON a.id_parent = pac.id";        
                $sql .= " LEFT JOIN (SELECT SUM(amount) as cnt_full, id_parent                      FROM ".$sys_tables['spec_packets_stats_show_full']." GROUP BY id_parent) b ON b.id_parent = pac.id";        
                $sql .= " LEFT JOIN (SELECT amount as cnt_full_yesterday, id_parent                 FROM ".$sys_tables['spec_packets_stats_show_full']."    WHERE date = CURDATE() - INTERVAL 1 DAY GROUP BY id_parent) e ON e.id_parent = pac.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cnt_bsn_click_day, id_parent                FROM ".$sys_tables['spec_packets_stats_click_day']."   WHERE `from` = 1   GROUP BY id_parent) cc ON cc.id_parent = pac.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cnt_pingola_click_day, id_parent           FROM ".$sys_tables['spec_packets_stats_click_day']."   WHERE `from` = 3 GROUP BY id_parent) ccc ON ccc.id_parent = pac.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(SUM(amount),0) as cnt_bsn_click_full, id_parent            FROM ".$sys_tables['spec_packets_stats_click_full']."  WHERE `from` = 1   GROUP BY id_parent) dd ON dd.id_parent = pac.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(SUM(amount),0) as cnt_pingola_click_full, id_parent       FROM ".$sys_tables['spec_packets_stats_click_full']."  WHERE `from` = 3   GROUP BY id_parent) ddd ON ddd.id_parent = pac.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(amount,0) as cnt_bsn_click_full_yesterday, id_parent       FROM ".$sys_tables['spec_packets_stats_click_full']."  WHERE `from` = 1  AND  date = CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) ff ON ff.id_parent = pac.id";        
                $sql .= " LEFT JOIN (SELECT IFNULL(amount,0) as cnt_pingola_click_full_yesterday, id_parent  FROM ".$sys_tables['spec_packets_stats_click_full']."  WHERE `from` = 3  AND date = CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) fff ON fff.id_parent = pac.id";
				if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " GROUP BY id DESC";
				$sql .= " ORDER BY id DESC";
				$sql .= " LIMIT ".$paginator->getLimitString($page); 
                

				$list = $db->fetchall($sql);
				// формирование списка
				Response::SetArray('list', $list);
				//print_r($list);
				if($paginator->pages_count>1){
					Response::SetArray('paginator', $paginator->Get($page));
				}
				break;
			}
		break;
		
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>