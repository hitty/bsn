<?php
$GLOBALS['js_set'][] = '/modules/textline/ajax_actions.js';

$GLOBALS['js_set'][] = '/js/jquery.form.expand.js';
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.textline.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'TextLine'));

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['campaign'] = $db->real_escape_string(Request::GetInteger('f_campaign',METHOD_GET));
$filters['manager'] = $db->real_escape_string(Request::GetInteger('f_manager',METHOD_GET));
$filters['status'] = Request::GetString('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['campaign'])) $get_parameters['f_campaign'] = $filters['campaign']; else $filters['campaign'] = false;
if(!empty($filters['manager'])) $get_parameters['f_manager'] = $filters['manager']; else $filters['manager'] = false;
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; else $filters['status'] = 'active';

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
$textline = new TextLine();
// обработка action-ов
switch($action){
	case 'restore':
	case 'archive':
		$id =  empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		//значение чекбокса
        $status = ( $action == 'restore' ? 1 : 2 );
		$table = !empty($this_page->page_parameters[2]) && $this_page->page_parameters[2] == 'banners' ? 'textline_banners' : 'textline_campaigns' ;
		if($id>0){
			$res = $db->querys("UPDATE ".$sys_tables[$table]." SET `enabled` = ? WHERE id=?", $status, $id);
			$results['setStatus'] = ($res && $db->affected_rows) ? $id : -1;
			if($ajax_mode){
				$ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
				break;
			}
		} else $ajax_result = false;
		break;
	/*************************\
    |*  Работа со статитикой *|
    \*************************/
    case 'stats':
        // переопределяем экшн
		$module_template = 'admin.stats.html';
		$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
		$GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
		$GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
		$GLOBALS['js_set'][] = '/modules/textline/datepick_actions.js';
		//получение данных по объекту из базы
		$info = $db->fetch("SELECT 
								`id`,
                                `title`
							FROM ".$sys_tables['textline_banners']."
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
                        IFNULL(a.show_amount,0) as show_amount, 
                        IFNULL(b.click_amount,0) as click_amount, 
                        a.date 
                    FROM 
					(
                        (
					      SELECT 
						      SUM(IFNULL(`amount`,0)) as show_amount, 
						      DATE_FORMAT(`date`,'%d.%m.%Y') as date
					      FROM ".$sys_tables['textline_stats_full_shows']."
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
                          FROM ".$sys_tables['textline_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
                          GROUP BY `date`
                         ) b ON a.date = b.date
                    ) UNION (
                        SELECT 
                            IFNULL(aa.show_amount,0) as show_amount, 
                            IFNULL(bb.click_amount,0) as click_amount,
                            aa.date 
                        FROM 
                       (   SELECT 
                              IFNULL(COUNT(*),0) as show_amount, 
                              'сегодня' as date,
                              id_parent
                          FROM ".$sys_tables['textline_stats_day_shows']."
                          WHERE `id_parent` = ".$id."
                        ) aa
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as click_amount, 
                              'сегодня' as date,
                              id_parent
                          FROM ".$sys_tables['textline_stats_day_clicks']."
                          WHERE  `id_parent` = ".$id."
                         ) bb ON aa.id_parent = bb.id_parent
                         
                         
                    )
				");
			Response::SetArray('stats',$stats); // статистика объекта	
			// расчет статистики докрутки кликов
            $average_clicks = $db->fetch("SELECT 30*AVG(amount) as sum_amount
                                          FROM ".$sys_tables['textline_stats_full_clicks']." 
                                          WHERE `from` = ?  AND id_parent = ? AND `date` >= CURDATE() - INTERVAL 30 DAY GROUP BY id_parent",
                                          1, $id); 
            Response::SetArray('average_clicks', $average_clicks);
		}
		Response::SetArray('info',$info); // информация об объекте										
	break;
    
    /*************************\
    |*  Статистика всех TextLine  *|
    \*************************/
    case 'total_stats':
        $partner = !empty($auth->id_group) && $auth->id_group==11;
        if (!$partner){
            $fields = array(
                    array('string','Дата')
                    , array('number','Показы')
                    , array('number','Переходы')
                    , array('number','CTR, %')
             );
        } 
        if  (!$ajax_mode){
            // переопределяем экшн 
            $module_template = 'admin.stats.total.html';
            $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
            $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
            $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
            $GLOBALS['js_set'][] = '/js/main.js';
            $GLOBALS['js_set'][] = '/modules/textline/datepick_actions.js';
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
                        a.date,IFNULL(a.show_amount,0) as show_amount, 
                        IFNULL(b.click_amount,0) as click_amount
                    FROM 
                    (
                      (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as show_amount, 
                              COUNT(DISTINCT(id_parent)) as textline_per_day,
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['textline_stats_full_shows']."
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
                          FROM ".$sys_tables['textline_stats_full_clicks']."
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
                              COUNT(DISTINCT(id_parent)) as textline_per_day,
                              id_parent
                          FROM ".$sys_tables['textline_stats_day_shows']."
                        ) aa
                        LEFT JOIN 
                        (
                          SELECT 
                                'сегодня' as date,
                              IFNULL(COUNT(*),0) as click_amount,                              
                              id_parent
                          FROM ".$sys_tables['textline_stats_day_clicks']."
                         ) bb ON aa.date = bb.date
                    )
                     
                ");    
                echo $db->error;

                Response::SetArray('stats',$stats);
            if (!$ajax_mode) Response::SetArray('info',$info); // информация об объекте 
            else {
                $module_template = 'admin.stats.table.html';
                if (!$partner)
                    $graphic_colors = array('#3366CC','#DC3912','#FF9900','#109618','#990099','#3cff00', '#808000', '#800000','#FF0000','#F2BB20','#0011EE');       // Цвета графиков
                else
                    $graphic_colors = array('#FF9900');       // Цвета графиков
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
    |*  Работа с объявлениями TextLine *|
    \****************************/		
	case 'banners':
		$action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		switch($action){
			case 'add':
			case 'edit':
                $GLOBALS['js_set'][] = '/js/main.js';
                $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
                $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
				$module_template = 'admin.banners.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['textline_banners']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT 
                                            ".$sys_tables['textline_banners'].".*,
                                            ".$sys_tables['textline_campaigns'].".type
										FROM ".$sys_tables['textline_banners']." 
                                        LEFT JOIN ".$sys_tables['textline_campaigns']." ON ".$sys_tables['textline_campaigns'].".id = ".$sys_tables['textline_banners'].".id_campaign
										WHERE ".$sys_tables['textline_banners'].".id = ?
                                        GROUP BY ".$sys_tables['textline_banners'].".id
                                        ", $id
                    ) ;
					//предустановка ссылки на картинки на главной и в шапке
					$cnt = count($_POST);
                }
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['value'] = $info[$key];
				}

				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
                // формирование дополнительных данных для формы (не из основной таблицы)
				$campaigns = $db->fetchall("SELECT id,title FROM ".$sys_tables['textline_campaigns']." ORDER BY id");
				foreach($campaigns as $key=>$val){
					$mapping['banners']['id_campaign']['values'][$val['id']] = $val['title'];
				}				
				Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
				//папки для картинок спецпредложений
                
                $utm_campaign = !empty($post_parameters['utm_campaign']) ? $post_parameters['utm_campaign'] : ( !empty($mapping['banners']['utm_campaign']['value']) ? $mapping['banners']['utm_campaign']['value'] : false ); 
                $utm_content = !empty($post_parameters['utm_content']) ? $post_parameters['utm_content'] : ( !empty($mapping['banners']['utm_content']['value']) ? $mapping['banners']['utm_content']['value'] : false ); 
                $title = !empty($post_parameters['title']) ? $post_parameters['title'] : ( !empty($mapping['banners']['title']['value']) ? $mapping['banners']['title']['value'] : false ); 
                if(empty($utm_campaign) && !empty($mapping['banners']['id_campaign']['value'])) {
                    $agency = $db->fetch("SELECT 
                                              ".$sys_tables['agencies'].".title
                                          FROM ".$sys_tables['agencies']."
                                          LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id   
                                          LEFT JOIN ".$sys_tables['textline_campaigns']." ON ".$sys_tables['textline_campaigns'].".id_user = ".$sys_tables['users'].".id   
                                          WHERE ".$sys_tables['textline_campaigns'].".id = ?
                                          GROUP BY ".$sys_tables['agencies'].".id",
                                          $mapping['banners']['id_campaign']['value']
                    );
                    if(!empty($agency)) $mapping['banners']['utm_campaign']['value'] = $post_parameters['utm_campaign'] = Convert::chpuTitle($agency['title']);
                }
                if(empty($utm_content) && !empty($title)) $mapping['banners']['utm_content']['value'] = $post_parameters['utm_content'] = Convert::chpuTitle($title);
                
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['banners']);
					// замена фотографий ТГБ
					if(!empty($_FILES)){
						foreach ($_FILES as $fname => $data){
							if ($data['error']==0) {
                                $_folder = Host::$root_path.'/'.Config::$values['img_folders']['textline'].'/'; // папка для файлов  тгб
								$_temp_folder = Host::$root_path.'/img/uploads/'; // папка для файлов  тгб
								$fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
								$fileParts = pathinfo($data['name']);
								$targetExt = $fileParts['extension'];
								$_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
								if (in_array(strtolower($targetExt),$fileTypes)) {
									move_uploaded_file($data['tmp_name'],$_temp_folder.$_targetFile);
                                    Photos::imageResize($_temp_folder.$_targetFile,$_folder.$_targetFile,380,30,'cut',90);
                                    if(file_exists($_temp_folder.$_targetFile) && is_file($_temp_folder.$_targetFile)) unlink($_temp_folder.$_targetFile);
									if(file_exists($_folder.$mapping['banners'][$fname]['value']) && is_file($_folder.$mapping['banners'][$fname]['value'])) unlink($_folder.$mapping['banners'][$fname]['value']);
									$post_parameters[$fname] = $_targetFile;
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
						// сохранение в БД
						if($action=='edit'){
							//статус - отредактирован объект
							$res = $db->updateFromArray($sys_tables['textline_banners'], $info, 'id') or die($db->error);
						} else {
							//дата дообавления объекта
							$res = $db->insertFromArray($sys_tables['textline_banners'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/advert_objects/textline/banners/edit/'.$new_id.'/'));
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
                //скрывать utm при добавлении
                if($action == 'add') $mapping['banners']['utm']['fieldtype'] = 'hidden';
                //папки для картинок спецпредложений
                Response::SetString('img_folder', Config::$values['img_folders']['textline']); // папка для ТГБ
                
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
					$res = $db->querys("UPDATE ".$sys_tables['textline_banners']." SET `enabled` = ? WHERE id=?", $status, $id) or die($db->error);
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
                    $res = $db->querys("UPDATE ".$sys_tables['textline_banners']." SET `enabled` = ? WHERE id=?", $status, $id);
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
				 $sql_where  = !empty($filters['campaign'])?" AND `id_campaign` = ".$filters['campaign']." ":"";
				 $sql = "
					SELECT  
						(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['textline_banners']." WHERE `enabled` = 1 $sql_where) AS active,
						(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['textline_banners']." WHERE `enabled` = 2 $sql_where) AS archive
					FROM dual";
				$counts = $db->fetch($sql) or die($sql.$db->error);
				Response::SetArray('statuses',array(
													'active'	=>	'Активные - '.$counts['active'],
													'archive'	=>	'Не активные - '.$counts['archive']
													));
				// формирование дополнительных данных для формы (не из основной таблицы)
				$campaigns = $db->fetchall("SELECT id,title FROM ".$sys_tables['textline_campaigns']." ORDER BY id");
				Response::SetArray('campaigns',$campaigns);												
                $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
                Response::SetArray('managers', $managers);
				$conditions = array();
				if(!empty($filters)){
                    if(!empty($filters['manager'])) $conditions['manager'] = $sys_tables['textline_banners'].'.id_manager = '.$db->real_escape_string($filters['manager']);
                    if(!empty($filters['campaign'])) $conditions['campaign'] = 'id_campaign = '.$db->real_escape_string($filters['campaign']);
                        
					switch($filters['status']){
						case  'active'	: $conditions['status'] = '`enabled` = 1 ';    break;
						case  'alls'	: break;
						case  'archive'	: $conditions['status'] = '`enabled` = 2'; 	break;
					}
				} 
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				$list = $textline->getListFull(false, false, $condition);
				// формирование списка
				Response::SetArray('list', $list);

				break;
			}
		break;
    /*****************************\
    |*  Работа с кампаниями TextLine *|
    \*****************************/				
	case 'add':
	case 'edit':
        $GLOBALS['js_set'][] = '/modules/promotions/ajax_actions.js';
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/modules/promotions/admin.autocomplette.js';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';		
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$module_template = 'admin.campaigns.edit.html';
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['textline_campaigns']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['textline_campaigns']." 
								WHERE id=?", $id);
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['value'] = $info[$key];
		}
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
        // формирование дополнительных данных для формы (не из основной таблицы)
        if(!empty($info['id_user']) || !empty($mapping['campaigns']['id_user']['value']) || !empty($post_parameters['id_user'])){
            $agency = $textline->getAgency($sys_tables['users'].".id = " . ( !empty($post_parameters['id_user']) ? $post_parameters['id_user'] : ( !empty($mapping['campaigns']['id_user']['value']) ? $mapping['campaigns']['id_user']['value'] : $info['id_user'] ) ) );
            $post_parameters['agency_title'] = $mapping['campaigns']['agency_title']['value'] = $agency['title'];
        }
        $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
        foreach($managers as $key=>$val){
            $mapping['campaigns']['id_manager']['values'][$val['id']] = $val['name'];
        }                

        $utm_campaign = !empty($post_parameters['utm_campaign']) ? $post_parameters['utm_campaign'] : ( !empty($mapping['campaigns']['utm_campaign']['value']) ? $mapping['campaigns']['utm_campaign']['value'] : ( !empty($mapping['campaigns']['agency_title']['value']) ? $mapping['campaigns']['agency_title']['value'] : false ) ); 
        $utm_content = !empty($post_parameters['utm_content']) ? $post_parameters['utm_content'] : ( !empty($mapping['campaigns']['utm_content']['value']) ? $mapping['campaigns']['utm_content']['value'] : false ); 
        $title = !empty($post_parameters['title']) ? $post_parameters['title'] : ( !empty($mapping['campaigns']['title']['value']) ? $mapping['campaigns']['title']['value'] : false ); 
        if(!empty($utm_campaign)) {
            $mapping['campaigns']['utm_campaign']['value'] = $post_parameters['utm_campaign'] = Convert::chpuTitle($utm_campaign);
        }
        if(empty($utm_content) && !empty($title)) $mapping['campaigns']['utm_content']['value'] = $post_parameters['utm_content'] = Convert::chpuTitle($title);
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['value'] = $post_parameters[$key];
			}
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['campaigns']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['error'] = $value;
			}
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['campaigns'][$key]['value'])) $info[$key] = $mapping['campaigns'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
					$res = $db->updateFromArray($sys_tables['textline_campaigns'], $info, 'id') or die($db->error);
				} else {
					$res = $db->insertFromArray($sys_tables['textline_campaigns'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/advert_objects/textline/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['campaigns']);
        // список объявлений РК
        if($action == 'edit'){
            $banners_list = $textline->getListFull(false, false, $sys_tables['textline_banners'].".id_campaign = ".$info['id']." AND ".$sys_tables['textline_banners'].".enabled = 1");
            Response::SetArray('banners_list', $banners_list);
        }
		break;
	default:
        $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
        Response::SetArray('managers', $managers);
		$module_template = 'admin.campaigns.list.html';
		//кол-во эл-ов в каждом блоке размещения
		 $sql = "
			SELECT  
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['textline_campaigns']." WHERE `enabled` > 0) AS alls,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['textline_campaigns']." WHERE `enabled` = 1) AS active,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['textline_campaigns']." WHERE `enabled` = 2) AS archive
			FROM dual";
		$counts = $db->fetch($sql) or die($sql.$db->error);
		Response::SetArray('statuses',array(
											'active'	=>	'Активные - '.$counts['active'],
											'alls'		=>	'Все - '.$counts['alls'],
											'archive'	=>	'В архиве - '.$counts['archive']
											));
        $conditions = array();
        if(!empty($filters)){
            if(!empty($filters['manager'])) $conditions['manager'] = $sys_tables['textline_campaigns'].'.id_manager = '.$db->real_escape_string($filters['manager']);
        }
		if(!empty($filters)){
			switch($filters['status']){
				case  'active'	: $conditions['status'] = $sys_tables['textline_campaigns'].".`enabled` = 1"; 		break;
				case  'alls'	: $conditions['status'] = $sys_tables['textline_campaigns'].".`enabled` > 0"; 	break;
				case  'archive'	: $conditions['status'] = $sys_tables['textline_campaigns'].".`enabled` = 2"; 	break;
			}
		} 
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['textline_campaigns'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = array();
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/advert_objects/textline/campaigns'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

		$list = $textline->getCampaignsListFull($paginator->getLimitString($page), $condition);
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