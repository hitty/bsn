<?php
$GLOBALS['js_set'][] = '/modules/business_centers/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Бизнес-центры'));

$business_centers_photo_folder = Config::$values['img_folders']['business_centers'];
$GLOBALS['js_set'][] = '/modules/estate/form_estate.js';

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['published'] = Request::GetInteger('f_published',METHOD_GET);
$filters['manager'] = Request::GetInteger('f_manager',METHOD_GET);
$filters['agency_check'] = Request::GetInteger('f_agency_check',METHOD_GET);
$filters['business_center'] = Request::GetInteger('f_business_center',METHOD_GET);
if(!empty($filters['manager'])) $get_parameters['f_manager'] = $filters['manager'];
if(!empty($filters['published'])) $get_parameters['f_published'] = $filters['published'];
if(!empty($filters['agency_check'])) $get_parameters['f_agency_check'] = $filters['agency_check'];
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
$GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
$GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
$GLOBALS['js_set'][] = '/modules/estate/streets_autocomplette.js';


$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else {
    $get_parameters['page'] = $page;
}

// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
$ajax_action = Request::GetString('action', METHOD_POST);
if(!empty($ajax_action)) $action  = $ajax_action;
$ajax_action = Request::GetString('action', METHOD_POST);
// обработка action-ов
switch($action){
    case 'save_manager':
        $id = Request::GetInteger('id', METHOD_POST);
        $id_manager = Request::GetInteger('id_manager', METHOD_POST);
        if(!empty($id_manager) && !empty($id)) {
            $res = $db->querys("UPDATE ".$sys_tables['business_centers']." SET id_manager = ? WHERE id = ?", $id_manager, $id);
            $ajax_result['ok'] = $res;
        }
        break;
    case 'geoitems':
        // addrselector:: иерархический список геопозиций от нулевого ID до указанного + районы и метро
        $item_id = Request::GetInteger('item_id', METHOD_POST);
        $district_id = Request::GetString('district_id', METHOD_POST);
        $subway_id = Request::GetString('subway_id', METHOD_POST);
        $multiselect = Request::GetBoolean('multiselect', METHOD_POST);
        if($item_id==0){
            $info = array('id'=>0, 'aoguid'=>'', 'id_region'=>0, 'id_area'=>0, 'id_city'=>0, 'id_place'=>0, 'id_street'=>0);
        } else {
            $info = $db->fetch("SELECT * FROM ".$sys_tables['geodata']."
                                WHERE id=?", $item_id);
        }
        if(empty($info)){
            $ajax_result['error'] = 'can not find element by item_id';
            break;
        }
        // определение геоданных объекта
        $geodata = $db->fetchall("
            SELECT * FROM ".$sys_tables['geodata']."
            WHERE a_level < 5 AND (
                      (id_region=? AND id_area=? AND id_city=? AND id_place=?)
                   OR (id_region=? AND id_area=? AND id_city=? AND id_place=0)
                   OR (id_region=? AND id_area=? AND id_city=0 AND id_place=0)
                   OR (id_region=? AND id_area=0 AND id_city=0 AND id_place=0)
               )
            ORDER BY a_level"
            , false
            , $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place']
            , $info['id_region'], $info['id_area'], $info['id_city']
            , $info['id_region'], $info['id_area']
            , $info['id_region']
        );
        $geolocation = array(array('id'=>0, 'title'=>'Россия'));
        while(!empty($geodata)){
            $location = array_shift($geodata);
            $geolocation[] = array(
                'id'=>$location['id'],
                'title'=>$location['offname'].' '.$location['shortname'],
                'id_region'=>$location['id_region'],
                'id_area'=>$location['id_area'],
                'id_city'=>$location['id_city']
            );
        }
        $ajax_result['ok'] = true;
        $ajax_result['geoitems_query'] = '';
        $ajax_result['items'] = $geolocation;
        // определение района
        $districts = $db->fetchall("SELECT * FROM ".$sys_tables['districts']."
                                    WHERE parent_id=? ORDER BY title", false, $item_id);
        if(!empty($districts)){
            $ajax_result['district'] = array('items'=>[],'selected'=>[]);
            foreach($districts as $distr){
                $item = array('id'=>$distr['id'], 'title'=>$distr['title']);
                if(!empty($multiselect) && in_array($distr['id'],explode(',',$district_id))){
                    $item['selected'] = true;
                    $ajax_result['district']['selected'][] = $item;
                }
                $ajax_result['district']['items'][] = $item;
            }
        }
        // определение метро
        $subways = $db->fetchall("SELECT * FROM ".$sys_tables['subways']."
                                  WHERE parent_id=? ORDER BY title", false, $item_id);
        if(!empty($subways)){
            $ajax_result['subway'] = array('items'=>[]);
            foreach($subways as $metro){
                $item = array('id'=>$metro['id'], 'title'=>$metro['title']);
                if(!empty($multiselect) && in_array($metro['id'],explode(',',$subway_id))){
                    $item['selected'] = true;
                    $ajax_result['subway']['selected'][] = $item;
                }
                $ajax_result['subway']['items'][] = $item;
            }
        }
        break;
    case 'geolist':
        // addrselector:: список геопозиций - потомков геопозиции с указанным ID
        $item_id = Request::GetInteger('item_id', METHOD_POST);
        if($item_id==0){
            $info = array('id'=>0, 'aoguid'=>'');
        } else {
            $info = $db->fetch("SELECT * FROM ".$sys_tables['geodata']."
                                WHERE id=?", $item_id);
        }
        if(empty($info)){
            $ajax_result['error'] = 'can not find element by item_id';
            break;
        }
        $geoitems = $db->fetchall("
            SELECT * FROM ".$sys_tables['geodata']."
            WHERE parentguid = ? AND a_level<5
            ORDER BY offname, shortname"
            , false
            , $info['aoguid']

        );
        $geolist = [];
        foreach($geoitems as $location){
            $geolist[] = array('id'=>$location['id'], 'title'=>$location['offname'].' '.$location['shortname']);
        }
        $ajax_result['ok'] = true;
        $ajax_result['geolist_query'] = '';

        $ajax_result['items'] = $geolist;
        break;
    case 'streets_list':
        // список улиц для автокомплита
        $geo_id = Request::GetInteger('geo_id', METHOD_POST);
        if($geo_id==0) $ajax_result['ok'] = false;
        else {
            $info = $db->fetch("SELECT `aoguid` FROM ".$sys_tables['geodata']."
                                WHERE id=?", $geo_id);
            $search_str = Request::GetString('search_string', METHOD_POST);
            $list = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']." WHERE parentguid=? AND a_level=5 AND offname LIKE ? ORDER BY offname LIMIT ?", false, $info['aoguid'], "%".$search_str."%", 10);
            $ajax_result['ok'] = true;
            $ajax_result['list'] = $list;
        }
        break;
     /**************************\
    |*  Работа с фотографиями  *|
    \**************************/
    case 'agencies':
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            switch($action){
                case 'list':
                    $search_string = Request::GetString('search_string',METHOD_POST);
                    $list = $db->fetchall("SELECT ".$sys_tables['users'].".id, ".$sys_tables['agencies'].".title FROM
                                            ".$sys_tables['users']."
                                            LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                            WHERE ".$sys_tables['agencies'].".title LIKE '%".$search_string."%' AND ".$sys_tables['users'].".agency_admin = 1
                                            GROUP BY ".$sys_tables['agencies'].".id
                                            ORDER BY  ".$sys_tables['agencies'].".title
                                            LIMIT 10
                    ");
                    $ajax_result['ok'] = true;
                    if(!empty($list)) $ajax_result['list'] = $list;
                    else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Такое агентство не найдено'));

                break;
            }
        break;
    /**************************\
    |*  Работа с фотографиями  *|
    \**************************/
    case 'bc_titles':
        $search_string = Request::GetString('search_string',METHOD_POST);
        $list = $db->fetchall("SELECT id, title FROM
                                ".$sys_tables['business_centers']."
                                WHERE ".$sys_tables['business_centers'].".title LIKE '%".$search_string."%' AND published = 1
                                ORDER BY  ".$sys_tables['business_centers'].".title
                                LIMIT 10
        ");
        $ajax_result['ok'] = true;
        if(!empty($list)) $ajax_result['list'] = $list;
        else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Такой БЦ найден'));
        break;
    case 'photos':
        if($ajax_mode){
            $ajax_result['error'] = '';
            // переопределяем экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            //id текущей новости
            $id = Request::GetInteger('id', METHOD_POST);
			switch($action){
                case 'list':
                    //получение списка фотографий
                    if(!empty($id)){
						$list = Photos::getList('business_centers',$id);
						if(!empty($list)){
							$ajax_result['ok'] = true;
							$ajax_result['list'] = $list;
							$ajax_result['folder'] = Config::$values['img_folders']['business_centers'];
						} else $ajax_result['error'] = 'Невозможно построить список фотографий';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
					//загрузка фотографий
					//id текущей новости

					if(!empty($id)){
						$res = Photos::Add('business_centers',$id,false,false,false,Config::Get('images/min_width')*0.8,Config::Get('images/min_height')*0.8,true);
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
                        $res = Photos::setTitle('business_centers',$id, $title);
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
						$res = Photos::Delete('business_centers',$id_photo);
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
						$res = Photos::setMain('business_centers', $id, $id_photo);
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
						$res = Photos::Sort('business_centers', $order);
						if(!empty($res)){
							$ajax_result['ok'] = true;
						} else $ajax_result['error'] = 'Невозможно отсортировать';
					} else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;
    /*************************\
    |*  Работа со статитикой *|
    \*************************/
    case 'stats':
        // переопределяем экшн
        $module_template = 'admin.stats.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
        //получение данных по объекту из базы
        $info = $db->fetch("SELECT 
                                `id`,
                                `title`
                            FROM ".$sys_tables['business_centers']."
                            WHERE `id` = ?",$id);
        $photo = Photos::getMainPhoto('business_centers',$id);
        Response::SetString('photo',$business_centers_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name']);
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
                            FROM ".$sys_tables['estate_complexes_stats_full_shows']." 
                            WHERE
                                `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND type = 3
                            GROUP BY `date`
                        ) a
                        LEFT JOIN 
                        (
                            SELECT 
                                SUM(IFNULL(`amount`,0)) as click_amount, 
                                DATE_FORMAT(`date`,'%d.%m.%Y') as date
                            FROM ".$sys_tables['estate_complexes_stats_full_clicks']."
                            WHERE
                                `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."  AND type = 3
                            GROUP BY `date`                                                       
                        ) b ON a.date = b.date
                    ) UNION (
                        SELECT IFNULL(c.show_amount,0) as show_amount, IFNULL(d.click_amount,0) as click_amount, c.date FROM 
                        (
                            SELECT 
                                IFNULL(COUNT(*),0) as show_amount, 
                                'сегодня' as date,
                                id_parent
                            FROM ".$sys_tables['estate_complexes_stats_day_shows']."  
                            WHERE `id_parent` = ".$id." AND type = 3
                        ) c
                        LEFT JOIN 
                        (
                            SELECT 
                                IFNULL(COUNT(*),0) as click_amount, 
                                'сегодня' as date,
                                id_parent
                            FROM ".$sys_tables['estate_complexes_stats_day_clicks']."  
                            WHERE `id_parent` = ".$id." AND type = 3
                        ) d ON c.id_parent = d.id_parent

                    )
                ");
            Response::SetArray('stats',$stats); // статистика объекта
            // общее количество показов/кликов/
        }
        Response::SetArray('info',$info); // информация об объекте
        break;
    /************************************\
    |*  Корпуса Бизнес-центров          *|
    \************************************/
    case 'corps':
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){

            /****************\
            |*  Удаление   *|
            \***************/
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                if(!empty($id)){
                    $res = $db->querys("UPDATE ".$sys_tables['business_centers_levels']." SET id_corp = 0 WHERE id_corp = ?", $id);
                    $res = $db->querys("DELETE FROM ".$sys_tables['business_centers_corps']." WHERE id=?", $id);
                    $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                    if($ajax_mode){
                        $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                        break;
                    }
                }
                break;
            /*********************\
            |*  Редактирование    *|
            \*********************/
            case 'add':
            case 'edit':
                $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                $module_template = 'admin.corps.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['business_centers_corps']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['business_centers_corps']." 
                                        WHERE id=?", $id) ;
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['corps'][$key])) $mapping['corps'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                // формирование дополнительных данных для формы (не из основной таблицы)
                if(!empty($info['id_parent']) || !empty($mapping['corps']['id_parent']['value']) || !empty($post_parameters['id_parent'])){
                    $business_center = $db->fetch("SELECT title FROM ".$sys_tables['business_centers']." WHERE id = ?", !empty($post_parameters['id_parent']) ? $post_parameters['id_parent'] : ( !empty($mapping['corps']['id_parent']['value']) ? $mapping['corps']['id_parent']['value'] : $info['id_parent'] ));
                    $post_parameters['business_center_title'] = $mapping['corps']['business_center_title']['value'] = $business_center['title'];

                }
                Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['corps']);
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['corps'][$key])) $mapping['corps'][$key]['value'] = $post_parameters[$key];
                    }

                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['corps'][$key])) $mapping['corps'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['corps'][$key]['value'])) $info[$key] = $mapping['corps'][$key]['value'];
                        }
                        //переопределение ссылок на картинку на главной и на картинку в шапке
                        $info['img_link'] = !empty($post_parameters['img_link']) ? $post_parameters['img_link'] : false;
                        // сохранение в БД
                        if($action=='edit'){
                            //статус - отредактирован объект
                            $res = $db->updateFromArray($sys_tables['business_centers_corps'], $info, 'id') or die($db->error);
                        } else {
                            //дата дообавления объекта
                            $res = $db->insertFromArray($sys_tables['business_centers_corps'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/estate/business_centers/corps/edit/'.$new_id.'/'));
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
                if(!empty($mapping['corps']['id']['value'])){
                    $offices = $db->fetchall("SELECT * FROM ".$sys_tables['business_centers_offices']." WHERE id_parent = ? ORDER BY id", false, $mapping['corps']['id']['value']);
                    Response::SetArray('offices', $offices);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['corps']);
                //определение владельца БЦ
                $bc_owner = $db->fetch("
                                        SELECT 
                                            ".$sys_tables['agencies'].".business_center
                                        FROM ".$sys_tables['agencies']."
                                        RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                        RIGHT JOIN ".$sys_tables['business_centers']." ON ".$sys_tables['business_centers'].".id_user = ".$sys_tables['users'].".id
                                        RIGHT JOIN ".$sys_tables['business_centers_corps']." ON ".$sys_tables['business_centers_corps'].".id_parent = ".$sys_tables['business_centers'].".id
                                        WHERE ".$sys_tables['business_centers_corps'].".id = ?
                ", $id);
                Response::SetBoolean('bc_owner', !empty($bc_owner) && $bc_owner['business_center'] == 1);
                break;
        /**********************************\
        |*  Список корпусов БЦ              *|
        \**********************************/
            default:
                $conditions = [];
                if(!empty($filters)){
                    if(!empty($filters['title'])) $conditions['title'] = $sys_tables['business_centers'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                    if(!empty($filters['business_center'])) $conditions['business_center'] = $sys_tables['business_centers_corps'].".`id_parent` = ".$filters['business_center'];
                }
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);
                // создаем пагинатор для списка
                $sql = "SELECT  COUNT(*) as items_count
                          FROM ".$sys_tables['business_centers_corps']."
                          LEFT JOIN ".$sys_tables['business_centers']." ON ".$sys_tables['business_centers'].".id = ".$sys_tables['business_centers_corps'].".id_parent
                          ".(empty($condition)?"":"WHERE ".$condition)."
                          GROUP BY ".$sys_tables['business_centers_corps'].".id";

                $paginator = new Paginator(false, 30, false, $sql);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/estate/business_centers/corps'                // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
                $list = $db->fetchall(
                                     "SELECT  
                                            ".$sys_tables['business_centers_corps'].".*,
                                            ".$sys_tables['business_centers'].".title as business_center_title
                                      FROM ".$sys_tables['business_centers_corps']."
                                      LEFT JOIN ".$sys_tables['business_centers']." ON ".$sys_tables['business_centers'].".id = ".$sys_tables['business_centers_corps'].".id_parent
                                      ".(empty($condition)?"":"WHERE ".$condition)."
                                      GROUP BY ".$sys_tables['business_centers_corps'].".id"
                );
                Response::SetArray('list', $list);
                $module_template = 'admin.corps.list.html';
                break;
        }

        break;
    /************************************\
    |*  Этажи Бизнес-центров            *|
    \************************************/
    case 'levels':
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            case 'photos':
                if($ajax_mode){
                    Photos::$__folder_options = array(
                                                'sm'=>array(206,152,'cut',65),
                                                'big'=>array(800,600,'',70)
                    );
                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                    //id текущей новости
                    $id = Request::GetInteger('id', METHOD_POST);
                    switch($action){
                        case 'list':
                            //получение списка фотографий
                            if(!empty($id)){
                                $list = Photos::getList('business_centers_offices',$id);
                                if(!empty($list)){
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $list;
                                    $ajax_result['folder'] = Config::$values['img_folders']['business_centers'];
                                } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'add':
                            //загрузка фотографий
                            //id текущей новости

                            if(!empty($id)){
                                $res = Photos::Add('business_centers_offices',$id,false,false,false,Config::Get('images/min_width')*0.8,Config::Get('images/min_height')*0.8,true);
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
                                $res = Photos::setTitle('business_centers_offices',$id, $title);
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
                                $res = Photos::Delete('business_centers_offices',$id_photo);
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
                                $res = Photos::setMain('business_centers_offices', $id, $id_photo);
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
                                $res = Photos::Sort('business_centers_offices', $order);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно отсортировать';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                    }
                }
                break;
            /****************\
            |*  Удаление   *|
            \***************/
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                if(!empty($id)){
                    $res = $db->querys("DELETE FROM ".$sys_tables['business_centers_levels']." WHERE id=?", $id);
                    $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                    if($ajax_mode){
                        $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                        break;
                    }
                }
                break;
            /*********************\
            |*  Редактирование    *|
            \*********************/
            case 'add':
            case 'edit':
                $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                $module_template = 'admin.levels.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['business_centers_levels']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['business_centers_levels']." 
                                        WHERE id=?", $id) ;
                    //предустановка ссылки на картинки на главной и в шапке
                    $cnt = count($_POST);
                    Response::SetString('img_link', $cnt==0?$info['img_link']:(!empty($_POST['img_link'])?$_POST['img_link']:false));
                    $business_centers_offices = $db->fetch("SELECT * FROM ".$sys_tables['business_centers_offices']." WHERE id_parent = ?",$id);
                    Response::SetArray('business_centers_offices',$business_centers_offices);
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['levels'][$key])) $mapping['levels'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                $corp = $db->fetchall("SELECT id,title FROM ".$sys_tables['business_centers_corps']." ORDER BY id_parent, title");
                foreach($corp as $key=>$val){
                    $mapping['levels']['id_corp']['values'][$val['id']] = $val['title'];
                }
                // формирование дополнительных данных для формы (не из основной таблицы)
                if(!empty($info['id_parent']) || !empty($mapping['levels']['id_parent']['value']) || !empty($post_parameters['id_parent'])){
                    $business_center = $db->fetch("SELECT title FROM ".$sys_tables['business_centers']." WHERE id = ?", !empty($post_parameters['id_parent']) ? $post_parameters['id_parent'] : ( !empty($mapping['levels']['id_parent']['value']) ? $mapping['levels']['id_parent']['value'] : $info['id_parent'] ));
                    $post_parameters['business_center_title'] = $mapping['levels']['business_center_title']['value'] = $business_center['title'];

                }
                Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
                //папки для картинок спецпредложений
                Response::SetString('img_folder', Config::$values['img_folders']['business_centers_levels']); // папка для ТГБ
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['levels']);
                    // замена фотографий ТГБ
                    if(!empty($_FILES)){
                        foreach ($_FILES as $fname => $data){
                            if ($data['error']==0) {
                                $_folder = Host::$root_path.'/'.Config::$values['img_folders']['business_centers_levels'].'/'; // папка для файлов  тгб
                                $_temp_folder = Host::$root_path.'/img/uploads/'; // папка для файлов  тгб
                                $fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
                                $fileParts = pathinfo($data['name']);
                                $targetExt = $fileParts['extension'];
                                $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                                if (in_array(strtolower($targetExt),$fileTypes)) {
                                    move_uploaded_file($data['tmp_name'],$_temp_folder.$_targetFile);
                                    Photos::imageResize($_temp_folder.$_targetFile,$_folder.$_targetFile,800,800,'',90);
                                    if(file_exists($_temp_folder.$_targetFile) && is_file($_temp_folder.$_targetFile)) unlink($_temp_folder.$_targetFile);
                                    if(file_exists($_folder.$mapping['levels'][$fname]['value']) && is_file($_folder.$mapping['levels'][$fname]['value'])) unlink($_folder.$mapping['levels'][$fname]['value']);
                                    $post_parameters[$fname] = $_targetFile;
                                }
                            }
                        }
                    }
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['levels'][$key])) $mapping['levels'][$key]['value'] = $post_parameters[$key];
                    }

                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['levels'][$key])) $mapping['levels'][$key]['error'] = $value;
                    }
                    //проверка на существование плана
                    if(empty($errors) && !empty($mapping['levels']['level']['value'])){
                        if(empty($info['id'])){
                            $level = $db->fetch("SELECT * FROM ".$sys_tables['business_centers_levels']." WHERE level = ? AND id_parent = ? AND corp = ?",
                                $mapping['levels']['level']['value'], $mapping['levels']['id_parent']['value'], $mapping['levels']['corp']['value']
                            );
                        } else {
                            $level = $db->fetch("SELECT * FROM ".$sys_tables['business_centers_levels']." WHERE level = ? AND id_parent = ? AND corp = ? AND id!=?",
                                $mapping['levels']['level']['value'], $mapping['levels']['id_parent']['value'], $mapping['levels']['corp']['value'], $info['id']
                            );

                        }
                        if(!empty($level)) $errors = $mapping['levels']['level']['error'] = 'Данный этаж-корпус уже существует, блин!';
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['levels'][$key]['value'])) $info[$key] = $mapping['levels'][$key]['value'];
                        }
                        //переопределение ссылок на картинку на главной и на картинку в шапке
                        $info['img_link'] = !empty($post_parameters['img_link']) ? $post_parameters['img_link'] : false;
                        // сохранение в БД
                        if($action=='edit'){
                            //статус - отредактирован объект
                            $res = $db->updateFromArray($sys_tables['business_centers_levels'], $info, 'id') or die($db->error);
                        } else {
                            //дата дообавления объекта
                            $res = $db->insertFromArray($sys_tables['business_centers_levels'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/estate/business_centers/levels/edit/'.$new_id.'/'));
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
                if(!empty($mapping['levels']['id']['value'])){
                    $offices = $db->fetchall("SELECT * FROM ".$sys_tables['business_centers_offices']." WHERE id_parent = ? ORDER BY id", false, $mapping['levels']['id']['value']);
                    Response::SetArray('offices', $offices);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['levels']);
                if($action == 'edit' && !empty($mapping['levels']['img_link']['value'])){
                    $GLOBALS['css_set'][] = '/modules/business_centers/svg.drawing.css';
                }
                //получение размеров фотографии
                if(!empty($mapping['levels']['img_link']['value'])){
                    $imginfo = getimagesize(Host::$root_path.'/'.Config::$values['img_folders']['business_centers_levels'].'/'.$mapping['levels']['img_link']['value']);
                    Response::SetArray('imginfo', $imginfo);
                }
                //определение владельца БЦ
                $bc_owner = $db->fetch("
                                        SELECT 
                                            ".$sys_tables['agencies'].".business_center
                                        FROM ".$sys_tables['agencies']."
                                        RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                        RIGHT JOIN ".$sys_tables['business_centers']." ON ".$sys_tables['business_centers'].".id_user = ".$sys_tables['users'].".id
                                        RIGHT JOIN ".$sys_tables['business_centers_levels']." ON ".$sys_tables['business_centers_levels'].".id_parent = ".$sys_tables['business_centers'].".id
                                        WHERE ".$sys_tables['business_centers_levels'].".id = ?
                ", $id);
                Response::SetBoolean('bc_owner', !empty($bc_owner) && $bc_owner['business_center'] == 1);
                break;
        /**********************************\
        |*  Список этажей БЦ              *|
        \**********************************/
            default:
                $conditions = [];
                if(!empty($filters)){
                    if(!empty($filters['title'])) $conditions['title'] = $sys_tables['business_centers'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                    if(!empty($filters['business_center'])) $conditions['business_center'] = $sys_tables['business_centers_levels'].".`id_parent` = ".$filters['business_center'];
                }
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);
                // создаем пагинатор для списка
                $sql = "SELECT  COUNT(*) as items_count
                          FROM ".$sys_tables['business_centers_levels']."
                          LEFT JOIN ".$sys_tables['business_centers']." ON ".$sys_tables['business_centers'].".id = ".$sys_tables['business_centers_levels'].".id_parent
                          ".(empty($condition)?"":"WHERE ".$condition)."
                          GROUP BY ".$sys_tables['business_centers_levels'].".id";

                $paginator = new Paginator(false, 30, false, $sql);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/estate/business_centers/levels'                // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
                $bc = new BusinessCenters();
                $list = $bc->getLevelsList($paginator->getLimitString($page), $condition, $sys_tables['business_centers_levels'].".level, ".$sys_tables['business_centers'].".title");
                Response::SetString('img_folder', Config::Get('img_folders/business_centers_levels'));
                Response::SetArray('list', $list);
                $module_template = 'admin.levels.list.html';
                break;
        }

        break;
    /************************************\
    |*  Офисы Бизнес-центров            *|
    \************************************/
    case 'offices':
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            /**********************\
            |*  Координаты  *|
            \**********************/
            case 'coords':
                $id = Request::GetInteger('id', METHOD_POST);
                $values = Request::GetArray('values', METHOD_POST);
                $ids = $ids_to_delete = [];
                if(!empty($id) && !empty($values['areas'])){
                    $list = $db->fetchall("SELECT id FROM ".$sys_tables['business_centers_offices']." WHERE id_parent = ?", false, $id);
                    if(!empty($list)) foreach($list as $k=>$item) $ids_to_delete[] = $item['id'];

                    foreach($values['areas'] as $k=>$value){
                        $area = $coords = []    ;
                        foreach($value['coords'] as $kc=>$coord) $coords[] = $coord;
                        $value['coords'] = implode(', ', $coords);
                        switch($value['type']){
                            case 'polygon': $value['type'] = 'poly'; break;
                            case 'rect': $value['type'] = 'rect'; break;
                        }
                        if(!empty($value['id'])){
                            $ids[] = $value['id'];
                            $db->querys("UPDATE ".$sys_tables['business_centers_offices']." SET id_parent = ?, coords = ?, draw_type = ? WHERE id = ?",
                                $id, $value['coords'], $value['type'], $value['id']
                            );
                        } else {
                            $db->querys("INSERT INTO ".$sys_tables['business_centers_offices']." SET id_parent = ?, coords = ?, draw_type = ?",
                                $id, $value['coords'], $value['type']
                            );
                            $value['id'] = $db->insert_id;
                        }
                        $office = $db->fetch("SELECT * FROM ".$sys_tables['business_centers_offices']." WHERE id = ?", $value['id']);
                        $ajax_result['values'][] = $office;

                    }
                    $array_difference = array_diff($ids_to_delete, $ids);
                    if(count($array_difference) > 0) $db->querys("DELETE FROM ".$sys_tables['business_centers_offices']." WHERE id IN (".implode(",", $array_difference).")");
                    $ajax_result['ok'] = true;
                }
                break;
            /**********************\
            |*  данные по офису   *|
            \**********************/
            case 'data':
                $post_parameters = Request::GetParameters(METHOD_POST);
                $id = Request::GetInteger('id', METHOD_POST);
                $cost = Request::GetInteger('cost', METHOD_POST);
                $square = Request::GetFloat('square', METHOD_POST);
                $number = Request::GetString('number', METHOD_POST);
                $cost_meter = Request::GetInteger('cost_meter', METHOD_POST);
                $id_object = Request::GetInteger('id_object', METHOD_POST);
                $id_object_status = isset($post_parameters['id_object']);
                $object_type = Request::GetInteger('object_type', METHOD_POST);
                $floor = Request::GetFloat('floor', METHOD_POST);
                $id_facing = Request::GetInteger('id_facing', METHOD_POST);
                $post_status = Request::GetBoolean('status', METHOD_POST);
                $status = Request::GetString('status', METHOD_POST);
                if(!empty($id) && (!empty($cost) || !empty($square) || !empty($number) || !empty($id_object_status) || !empty($object_type) || !empty($cost_meter) || !empty($post_status)  || !empty($floor)  || !empty($id_facing) )){
                    switch(true){
                        case !empty($cost): $field = 'cost'; $value = $cost; break;
                        case !empty($square): $field = 'square'; $value = $square; break;
                        case !empty($floor): $field = 'floor'; $value = $floor; break;
                        case !empty($cost_meter): $field = 'cost_meter'; $value = $cost_meter; break;
                        case !empty($id_object_status): $field = 'id_object'; $value = !empty($id_object) ? $id_object : 0; break;
                        case !empty($post_status): $field = 'status'; $value = $status == 'false' ? 2 : 1; break;
                        case !empty($object_type): $field = 'object_type'; $value = $object_type; break;
                        case !empty($id_facing): $field = 'id_facing'; $value = $id_facing; break;
                        case !empty($number): $field = 'number'; $value = $number; break;
                    }
                    $res = $db->querys("UPDATE ".$sys_tables['business_centers_offices']." SET ".$field." = ? WHERE id = ?", $value, $id);
                    if(!empty($object_type) && $object_type == 2) $res = $db->querys("UPDATE ".$sys_tables['business_centers_offices']." SET status = 2 WHERE id = ?", $id);
                    elseif(!empty($post_status) && $value == 1) $res = $db->querys("UPDATE ".$sys_tables['business_centers_offices']." SET object_type = 1 WHERE id = ?", $id);
                    elseif(!empty($id_object_status) && !empty($id_object)){
                        $item = $db->fetch("SELECT * FROM ".$sys_tables['commercial']." WHERE id = ?", $id_object);
                        if(!empty($item)) {
                            $business_center  = $db->fetch("
                                SELECT ".$sys_tables['business_centers'].".id 
                                FROM ".$sys_tables['business_centers']."
                                LEFT JOIN ".$sys_tables['business_centers_levels']." ON  ".$sys_tables['business_centers_levels'].".id_parent = ".$sys_tables['business_centers'].".id
                                LEFT JOIN ".$sys_tables['business_centers_offices']." ON  ".$sys_tables['business_centers_offices'].".id_parent = ".$sys_tables['business_centers_levels'].".id
                                WHERE ".$sys_tables['business_centers_offices'].".id = ?", $id
                            );
                            if(!empty($business_center) ) $db->querys("UPDATE ".$sys_tables['commercial']." SET id_business_center = ? WHERE id = ?", $business_center['id'], $id_object);
                        }
                    }
                    $ajax_result['ok'] = $res;
                }
                break;


        }
        break;
    /************************************\
    |*  Работа с Бизнес-центрами  *|
    \************************************/
	case 'add':
	case 'edit':
	    $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
        $GLOBALS['js_set'][] = '/js/jquery.addrselector.js';
        $GLOBALS['css_set'][] = '/css/jquery.addrselector.css';

		$module_template = 'admin.business_centers.edit.html';
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['business_centers']);
            $info['date_in'] = $info['date_change'] = date('Y-m-d H:i:s');
            $info['district_title'] = $info['metro_title'] = '';
        } else {
			// получение данных из БД
            $info = $db->fetch("SELECT main.*,
                                    IFNULL(distr.title,'') as district_title,
                                    IFNULL(subway.title,'') as subway_title
                                FROM ".$sys_tables['business_centers']." main
                                LEFT JOIN ".$sys_tables['districts']." distr ON distr.id=main.id_district
                                LEFT JOIN ".$sys_tables['subways']." subway ON subway.id=main.id_subway
                                WHERE main.id=?", $id);
		}
        // определение геоданных объекта
        $geodata = $db->fetchall("
            SELECT * FROM ".$sys_tables['geodata']."
            WHERE a_level < 5 AND (
                      (id_region=? AND id_area=? AND id_city=? AND id_place=?)
                   OR (id_region=? AND id_area=? AND id_city=? AND id_place=0)
                   OR (id_region=? AND id_area=? AND id_city=0 AND id_place=0)
                   OR (id_region=? AND id_area=0 AND id_city=0 AND id_place=0)
               )
            ORDER BY a_level"
            , false
            , $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place']
            , $info['id_region'], $info['id_area'], $info['id_city']
            , $info['id_region'], $info['id_area']
            , $info['id_region']
        );
        $geolocation = $location = [];
        while(!empty($geodata)){
            $location = array_shift($geodata);
            if(empty($geodata)) {
                $mapping['business_centers']['geolocation_id']['value'] = $location['id'];
            }
            $geolocation[] = $location['offname'].' '.$location['shortname'];
        }
        $mapping['business_centers']['geolocation']['value'] = implode(' / ',$geolocation);
        //определение улицы
        if(!empty($info['id_street'])) {
            $street = $db->fetch("
                SELECT `offname`, `shortname` FROM ".$sys_tables['geodata']."
                WHERE a_level = 5 AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place'], $info['id_street']
            );
            $info['txt_street'] = $street['offname'].' '.$street['shortname'];
        }
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['business_centers'][$key])) $mapping['business_centers'][$key]['value'] = $info[$key];
		}
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
        // формирование дополнительных данных для формы (не из основной таблицы)
        $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
        foreach($managers as $key=>$val){
            $mapping['business_centers']['id_manager']['values'][$val['id']] = $val['name'];
        }
        $way_types = $db->fetchall("SELECT id,title FROM ".$sys_tables['way_types']." ORDER BY title");
        foreach($way_types as $key=>$val){
            $mapping['business_centers']['id_way_type']['values'][$val['id']] = $val['title'];
        }
        if(!empty($info['id_user'])){
            $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".title FROM
                                    ".$sys_tables['agencies']."
                                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    WHERE ".$sys_tables['users'].".id = ?", $info['id_user']);
            if(!empty($agency)) Response::SetString('agency_title',$agency['title']);
            $agency_advert = $db->fetch("SELECT ".$sys_tables['agencies'].".title FROM
                                    ".$sys_tables['agencies']."
                                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    WHERE ".$sys_tables['users'].".id = ?", $info['id_advert_agency']);
            if(!empty($agency_advert)) Response::SetString('advert_agency_title',$agency_advert['title']);
        }
		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            if(!empty($_FILES)){
                foreach ($_FILES as $fname => $data){
                    if ($data['error']==0) {
                        $_folder = Host::$root_path.'/'.Config::Get('docs_folders').'/'; // папка для файлов документов
                        $fileTypes = array('pdf','doc','docx'); // допустимые расширения файлов
                        $fileParts = pathinfo($data['name']);
                        $targetExt = $fileParts['extension'];
                        $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                        if (in_array(strtolower($targetExt),$fileTypes)) {
                            move_uploaded_file($data['tmp_name'],$_folder.$_targetFile);
                            $post_parameters[$fname] = $_targetFile;
                        }
                    }
                }
            }
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(isset($mapping['business_centers'][$key])) $mapping['business_centers'][$key]['value'] = $post_parameters[$key];
			}
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['business_centers']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['business_centers'][$key])) $mapping['business_centers'][$key]['error'] = $value;
			}
            //проверка на похожее название
            if($action == 'add') $cottage_item = $db->fetch("SELECT * FROM ".$sys_tables['business_centers']." WHERE title = ?", $mapping['business_centers']['title']['value']);
            else if($action == 'edit') $cottage_item = $db->fetch("SELECT * FROM ".$sys_tables['business_centers']." WHERE title = ? AND id != ?", $mapping['business_centers']['title']['value'], $info['id']);
            if(!empty($cottage_item)) $errors['title'] = $mapping['business_centers']['title']['error'] = 'Такое название КП уже существует';
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['business_centers'][$key]['value'])) $info[$key] = $mapping['business_centers'][$key]['value'];
				}
                //определение гео данных для полученного geo_id
                $geolocation = $db->fetch("SELECT id_region,id_area,id_city,id_place FROM ".$sys_tables['geodata']." WHERE `id` = ".$mapping['business_centers']['geolocation_id']['value']);
                $info['id_region'] = $geolocation['id_region'];
                $info['id_area']   = $geolocation['id_area'];
                $info['id_city']   = $geolocation['id_city'];
                $info['id_place']  = $geolocation['id_place'];;
                if(!empty($info['site']) && strstr($info['site'],'http://')=='') $info['site'] = 'http://'.$info['site'];
				// сохранение в БД
				if($action=='edit'){
					//статус - отредактирован объект
					$info['object_status'] = 2;
					$res = $db->updateFromArray($sys_tables['business_centers'], $info, 'id') or die($db->error);
                    //редирект по нажатию на сохранить+перейти в список поселков
                    $redirect =  Request::GetString('redirect',METHOD_GET);
                    if(!empty($redirect)) {
                        $cookie_page = Cookie::GetString('admin_business_centers_page');
                        $cookie_admin_params = Cookie::GetArray('admin_business_centers_params');
                        if(!empty($cookie_admin_params)){
                            $params  = [];
                            foreach($cookie_admin_params as $k=>$val) $params[] = $k.'='.$val;
                            Host::Redirect("/admin/estate/business_centers/?".implode('&',$params));
                        }
                        elseif(empty($cookie_page)) $cookie_page = 1;
                        Host::Redirect("/admin/estate/business_centers/?page=".$cookie_page);
                    }
				} else {
					//дата дообавления объекта
					$info['idate'] = date('Y-m-d');
                    //создаем CHPU
					$res = $db->insertFromArray($sys_tables['business_centers'], $info, 'id');
                    $new_id = $db->insert_id;
                    //обновление ЧПУ
                    $chpu_title = createCHPUTitle($info['title']);
                    $chpu_item = $db->fetch("SELECT * FROM ".$sys_tables['business_centers']." WHERE chpu_title = ?", $chpu_title);
                    $db->querys("UPDATE ".$sys_tables['business_centers']." SET chpu_title = ? WHERE id = ?", $chpu_title.(!empty($chpu_item)?"_".$new_id:""), $new_id);
					if(!empty($res)){
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/estate/business_centers/edit/'.$new_id.'/'));
							exit(0);
						}
					}
				}
				Response::SetBoolean('saved', $res); // результат сохранения
			} else Response::SetBoolean('errors', true); // признак наличия ошибок
		}
		if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
			Response::SetBoolean('form_submit', true);
			Response::SetBoolean('saved', true);
		}
		// запись данных для отображения на странице
		Response::SetArray('data_mapping',$mapping['business_centers']);
        Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
		break;
	case 'del':
		$id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$del_photos = Photos::DeleteAll('business_centers',$id);
		$res = $db->querys("DELETE FROM ".$sys_tables['business_centers']." WHERE id=?", $id);
		$results['delete'] = ($res && $db->affected_rows) ? $id : -1;
		if($ajax_mode){
			$ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
			break;
		}
	default:
        $cookie_page = Cookie::GetString('admin_business_centers_page');
        $cookie_admin_params = Cookie::GetArray('admin_business_centers_params');
        if($page>1) {
            Cookie::SetCookie('admin_business_centers_page',$page);
            Cookie::SetCookie('admin_business_centers_params',$get_parameters);
        } elseif($cookie_page!=$page && !empty($cookie_admin_params)) {
            if(!empty($get_parameters)) Cookie::SetCookie('admin_business_centers_params',$get_parameters);
            else {
                $params  = [];
                foreach($cookie_admin_params as $k=>$val) $params[] = $k.'='.$val;
                Host::Redirect("/admin/estate/business_centers/?".implode('&',$params));
            }
        }


		$module_template = 'admin.business_centers.list.html';
		// формирование списка
        $managers_list = $db->fetchall("SELECT id, name as title FROM ".$sys_tables['managers']." WHERE bsn_manager=1 UNION SELECT 99 as id, 'не проставлен' as name");
        $managers = [];
        foreach($managers_list as  $k=>$item) $managers[$item['id']] = $item['title'];
        Response::SetArray('managers',$managers);
		$conditions = [];
		if(!empty($filters)){
            if(!empty($filters['title'])) $conditions['title'] = $sys_tables['business_centers'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['manager'])) $conditions[] = "`id_manager` = ".($filters['manager']!=99 ? $filters['manager'] : 0);
            if(!empty($filters['agency_check'])){
              if($filters['agency_check']==1)  $conditions[] = " id_user > 0";
              else  $conditions[] = " id_user = 0";
            }
            if(!empty($filters['published'])) $conditions[] = "`published` = ".$filters['published'];
		}
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['business_centers'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = [];
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/estate/business_centers'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

        $sql = "SELECT 
                        business_centers.*,   
                        IFNULL(a.cnt_day,0) as cnt_day,
                        IFNULL(e.cnt_full_last_days,0) as cnt_full_last_days,
                        IFNULL(c.cnt_click_day,0) as cnt_click_day,
                        IFNULL(f.cnt_click_full_last_days,0) as cnt_click_full_last_days
                  FROM ".$sys_tables['business_centers']." business_centers";
        $sql .= " LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$sys_tables['estate_complexes_stats_day_shows']." WHERE type = 3 GROUP BY id_parent) a ON a.id_parent = business_centers.id";
        $sql .= " LEFT JOIN (SELECT COUNT(*) as cnt_click_day, id_parent FROM ".$sys_tables['estate_complexes_stats_day_clicks']." WHERE type = 3 GROUP BY id_parent) c ON c.id_parent = business_centers.id";
        $sql .= " LEFT JOIN (SELECT AVG(amount) as cnt_full_last_days, id_parent FROM ".$sys_tables['estate_complexes_stats_full_shows']." WHERE type = 3 AND date > CURDATE() - INTERVAL 30  DAY AND date <= CURDATE() - INTERVAL 1 DAY GROUP BY id_parent) e ON e.id_parent = business_centers.id";
        $sql .= " LEFT JOIN (SELECT AVG(amount) as cnt_click_full_last_days, id_parent FROM ".$sys_tables['estate_complexes_stats_full_clicks']." WHERE type = 3 AND date > CURDATE() - INTERVAL 30  DAY AND date <= CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) f ON f.id_parent = business_centers.id";
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY advanced, business_centers.title";
        $sql .= " LIMIT ".$paginator->getLimitString($page);

		$list = $db->fetchall($sql);
		// определение главной фотки для поселка
		foreach($list as $key=>$value){
			$photo = Photos::getMainPhoto('business_centers',$value['id']);
			if(!empty($photo)) {
				$list[$key]['photo'] = $business_centers_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
			}
		}
		// формирование списка
		Response::SetArray('list', $list);
		//print_r($list);
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