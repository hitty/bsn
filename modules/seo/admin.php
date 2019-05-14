<?php
 /**
INSERT INTO `common`.`pages_seo` (`id`, url, pretty_url, title, h1_title, description,  keywords, seo_text) 
SELECT `id`, url, url, title, h1_title, description,  keywords,  CONCAT_WS(  '',  `text` ,  `seo_text_estate_top` ,  `seo_text_estate_bottom` ) as seo_text
FROM `bsnweb`.`text_down`;

UPDATE `pages_seo` SET url = REPLACE(url,'/*',''), pretty_url = REPLACE(pretty_url,'/*','');
UPDATE pages_seo SET url=LEFT(url, LENGTH(url) - 1), pretty_url=LEFT(pretty_url, LENGTH(pretty_url) - 1) WHERE RIGHT(url,1) = '/';
*/
require_once('includes/class.paginator.php');

// добавление title
$this_page->manageMetadata(array('title'=>'СЕО для страниц'));
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/admin.mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['url'] = Request::GetString('f_url',METHOD_GET);
$filters['pretty_url'] = Request::GetString('f_pretty_url',METHOD_GET);
$filters['match'] = Request::GetString('f_match',METHOD_GET);
if(!empty($filters['url'])) {
    $filters['url'] = str_replace('https://www.bsn.ru','',$filters['url']); $filters['url'] = str_replace('http://','',$filters['url']); $filters['url'] = str_replace('www.bsn.ru','',$filters['url']); $filters['url'] = str_replace('bsn.ru','',$filters['url']);
    $filters['url'] = trim($filters['url'],'/');
    $filters['url'] = urldecode($filters['url']);
    $get_parameters['f_url'] = $filters['url'];
}
if(!empty($filters['pretty_url'])) {
    $filters['pretty_url'] = str_replace('https://www.bsn.ru','',$filters['pretty_url']); $filters['pretty_url'] = str_replace('http://','',$filters['pretty_url']); $filters['pretty_url'] = str_replace('www.bsn.ru','',$filters['pretty_url']); $filters['pretty_url'] = str_replace('bsn.ru','',$filters['pretty_url']);
    $filters['pretty_url'] = trim($filters['pretty_url'],'/');
    $filters['pretty_url'] = urldecode($filters['pretty_url']);
    $get_parameters['f_pretty_url'] = $filters['pretty_url'];
}
//фильтр по статусу страницы - для ловца
$filter_status = Request::GetString('f_status',METHOD_GET);
if(!empty($filter_status)) {
    $filter_status = urldecode($filter_status);
    $get_parameters['f_status'] = $filter_status;
}
if(!empty($filters['match'])) $get_parameters['f_match'] = $filters['match'];
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

//для графиков ловца
$GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
$GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
$GLOBALS['js_set'][] = '/js/main.js';
$GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
$GLOBALS['js_set'][] = '/js/google.chart.api.js';
$GLOBALS['js_set'][] = '/admin/js/statistics-chart.js';


// обработка action-ов
switch($action){
    case 'autocomplete':
        if($ajax_mode){
            $ajax_result['error'] = '';
            // переопределяем экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            switch($action){
                case 'list':
                    $search_str = Request::GetString('search_string', METHOD_POST);
                    $list = $db->fetchall("SELECT url FROM ".$sys_tables['pages']." WHERE block_page!=1 AND url LIKE ? ORDER BY url LIMIT 10", false, $search_str.'%');
                    $ajax_result['ok'] = true;
                    $ajax_result['list'] = $list;
                    break;
            }
        }
        break;
    case 'add':
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/modules/seo/url_autocomplette.js';
        $module_template = 'admin.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
	        
		if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['pages_seo']);
            $info['keywords'] = $info['description'] = $info['seo_text_top'] = $info['seo_text_bottom'] = "";
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *
                                FROM ".$sys_tables['pages_seo']." 
                                WHERE id=?", $id);
            if(empty($info)) Host::Redirect('/admin/seo/add/');
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['seo'][$key])) $mapping['seo'][$key]['value'] = $info[$key];
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);
	
        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['seo'][$key])) $mapping['seo'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['seo']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['seo'][$key])) $mapping['seo'][$key]['error'] = $value;
            }
            // пустой pretty_url может быть только у пустого url (заполняем копией url)
            if(!empty($mapping['seo']['url']) && empty($mapping['seo']['pretty_url'])) $mapping['seo']['pretty_url'] = $mapping['seo']['url'];
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['seo'][$key]['value'])) $info[$key] = $mapping['seo'][$key]['value'];
                }
                if(!empty($info['pretty_url'])) {
                    $info['pretty_url'] = str_replace('https://www.bsn.ru','',$info['pretty_url']); $info['pretty_url'] = str_replace('http://','',$info['pretty_url']); $info['pretty_url'] = str_replace('www.bsn.ru','',$info['pretty_url']); $info['pretty_url'] = str_replace('bsn.ru','',$info['pretty_url']);
                    $info['pretty_url'] = trim($info['pretty_url'],'/');
                }
                if(!empty($info['url'])) {
                    $info['url'] = str_replace('https://www.bsn.ru','',$info['url']); $info['url'] = str_replace('http://','',$info['url']); $info['url'] = str_replace('www.bsn.ru','',$info['url']); $info['url'] = str_replace('bsn.ru','',$info['url']);
                    $info['url'] = trim($info['url'],'/');
                }
                if(empty($info['pretty_url']) && !empty($info['url'])) $info['pretty_url'] = 404;
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['pages_seo'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['pages_seo'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/seo/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['seo']);
        break;
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $res = $db->query("DELETE FROM ".$sys_tables['pages_seo']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    //неиндексированные страницы
    case 'not_indexed':
        $action = (!empty($this_page->page_parameters[2])?$this_page->page_parameters[2]:"");
        require_once('includes/class.crawler_catcher.php');
        switch(true){
            case in_array($action,Config::$values['crawlers_aliases']):
                $bot_alias = $action;
                $action = (!empty($this_page->page_parameters[3])?$this_page->page_parameters[3]:"");
                switch(true){
                    case empty($action):    
                        $GLOBALS['js_set'][] = '/modules/pages/ajax_actions.js';
                        if(!empty($_FILES)){
                            foreach ($_FILES as $fname => $data){
                                if ($data['error']==0) {
                                    $_temp_folder = Host::$root_path.'/'.Config::$values['crawlers_folders']['tmp'].'/'; // папка для файлов поисковиков
                                    $fileTypes = array('txt','xml'); // допустимые расширения файлов
                                    $fileParts = pathinfo($data['name']);
                                    $targetExt = $fileParts['extension'];
                                    $_targetFile = md5(microtime()).'_'.$bot_alias.'.' . $targetExt; // конечное имя файла
                                    if (in_array(strtolower($targetExt),$fileTypes)) {
                                        move_uploaded_file($data['tmp_name'],$_temp_folder.$_targetFile);
                                        if(file_exists($_temp_folder.$_targetFile) && is_file($_temp_folder.$_targetFile)){
                                            
                                            $errors = CrawlerCatcher::importLinksFromFile($bot_alias,$_temp_folder.$_targetFile);
                                            
                                            unlink($_temp_folder.$_targetFile);
                                            
                                            $rows_amount = count($errors);
                                            $errors_amount = 0;
                                            foreach($errors as $key=>$item){
                                                if(!empty($item)) ++$errors_amount;
                                            }
                                            Response::SetInteger('total_rows',$rows_amount);
                                            Response::SetInteger('total_added',($rows_amount - $errors_amount));
                                            if(!empty($errors_amount)){
                                                Response::SetArray('importerrors_list',$errors);
                                                Response::SetInteger('total_errors',$errors_amount);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        //для autocomplete и выпадайки
                        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                        $GLOBALS['js_set'][] = '/modules/estate/admin.autocomplette.js';
                        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                        $GLOBALS['css_set'][] = '/css/autocomplete.css';
                        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                        $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
                        
                        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                        
                        require_once('includes/class.paginator.php');
                        
                        $condition = "";
                        if(!empty($filter_status)) $condition = "bot_visits_total ".(($filter_status==1?">":"="))." 0";
                        // создаем пагинатор для списка
                        $paginator = new Paginator($sys_tables['pages_not_indexed_'.$bot_alias], 30, $condition);
                        // get-параметры для ссылок пагинатора
                        $get_in_paginator = [];
                        foreach($get_parameters as $gk=>$gv){
                            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                        }
                        // ссылка пагинатора
                        $paginator->link_prefix = '/admin/seo/not_indexed/'.$bot_alias                // модуль
                                                  ."/?"                                       // конечный слеш и начало GET-строки
                                                  .implode('&',$get_in_paginator)             // GET-строка
                                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                        if($paginator->pages_count>0 && $paginator->pages_count<$page){
                            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                            exit(0);
                        }
                        
                        $pages_list = $db->fetchall("SELECT id,url,title,
                                                        CONCAT('/',url,'/') AS link_url,
                                                        DATE_FORMAT(date_in,'%d.%m.%Y %H:%i:%s') as date_in,
                                                        IF(date_out!='0000-00-00 00:00:00',DATE_FORMAT(date_out,'%d.%m.%Y %H:%i:%s'),'') as date_out,
                                                        (bad_page=1) AS bad_page 
                                                 FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]." ".(!empty($condition)?"WHERE ".$condition:"")." LIMIT ".$paginator->getLimitString($page));
                        Response::SetArray('bot_pages',$pages_list);
                        if($paginator->pages_count>1){
                            Response::SetArray('paginator', $paginator->Get($page));
                        }
                        Response::SetString('bot_alias',$bot_alias);
                        
                        Response::SetInteger('total_found',$paginator->items_count);
                        $module_template = "not_indexed_bot.html";
                        break;
                    //добавляем страницу вручную
                    case $action == "add":
                        $page_to_add = Request::GetInteger('page_id',METHOD_POST);
                        $page_info = $db->fetch("SELECT url,title,description FROM ".$sys_tables['pages_seo']." WHERE id = ".$page_to_add);
                        $res = $db->query("INSERT INTO ".$sys_tables['pages_not_indexed_'.$bot_alias]." (url,title,description,date_in) VALUES (?,?,?,NOW())",$page_info['url'],$page_info['title'],$page_info['description']);
                        if(!$res) $ajax_result['alert'] = $db->errors;
                        else{
                            $page_info['date_in'] = date("d.m.Y H:i:s");
                            $ajax_result['data'] = $page_info;
                        } 
                        $ajax_result['ok'] = true;
                        break;
                    case $action == "del":
                        $page_id = (!empty($this_page->page_parameters[4])?$this_page->page_parameters[4]:0);
                        $page_id= Convert::ToInt($page_id);
                        if(empty($page_id)) $ajax_result['ok'] = false;
                        $ajax_result['ids'] = array($page_id);
                        $res = $db->query("DELETE FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]." WHERE id = ?",$page_id);
                        $ajax_result = array('ok' => $res, 'ids'=>array($page_id));
                        break;
                    //статистика
                    case $action == 'stats':
                        
                        $fields = array(
                                      array('string','Дата')
                                      ,array('number','Заходы на сайт')
                                      ,array('number','Показано ссылок')
                                      ,array('number','Посещено страниц из ранее показанных')
                                      ,array('number','Проиндексировано'));
                        
                        if($ajax_mode){
                            $get_parameters = Request::GetParameters(METHOD_GET);
                            $date_start = (!empty($get_parameters['date_start'])?$get_parameters['date_start']:"");
                            $date_end = (!empty($get_parameters['date_end'])?$get_parameters['date_end']:"");
                            
                            $date_conditions = [];
                            if(!empty($date_start)) $date_conditions[] = "`date` >= '".date('Y-m-d',strtotime($date_start))."'";
                            if(!empty($date_end)) $date_conditions[] = "`date` <= '".date('Y-m-d',strtotime($date_end))."'";
                            
                            //если статистика включает сегодня, читаем данные
                            if(empty($date_end) || date("Y-m-d",strtotime($date_end)) == date("Y-m-d")){
                                $today_stats = $db->fetch("SELECT 'сегодня' AS date_formatted,
                                                                  (SELECT COUNT(*) AS visits_amount FROM  ".$sys_tables['pages_visits_'.$bot_alias.'_day'].") AS visits_amount,
                                                                  (SELECT SUM(shown_today) AS links_shown FROM ".$sys_tables['pages_not_indexed_'.$bot_alias].") AS links_shown,
                                                                  (SELECT COUNT(*) AS old_pages_visits 
                                                                   FROM ".$sys_tables['pages_visits_'.$bot_alias.'_day']."
                                                                   LEFT JOIN ".$sys_tables['pages_not_indexed_'.$bot_alias]." 
                                                                   ON ".$sys_tables['pages_visits_'.$bot_alias.'_day'].".id_page_in_stack = ".$sys_tables['pages_not_indexed_'.$bot_alias].".id
                                                                   WHERE DATEDIFF(NOW(),date_out) = 0 AND bot_visits_total>1) AS old_pages_visits,
                                                                  (SELECT COUNT(*) AS pages_added FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]." WHERE DATEDIFF(NOW(),date_out) = 0) AS pages_added");
                            }
                            
                            $stats = $db->fetchall("SELECT DATE_FORMAT(`date`,'%d.%m.%Y') AS date_formatted,
                                                           visits_amount,
                                                           links_shown,
                                                           old_pages_visits,
                                                           pages_added
                                                    FROM ".$sys_tables['pages_visits_'.$bot_alias.'_full'].(!empty($date_conditions)?" WHERE ".implode(" AND ",$date_conditions):"")." ORDER BY `date` ASC");
                            if(!empty($today_stats)) $stats[] = $today_stats;
                            
                            if($stats) {
                                foreach($stats as $ind=>$item) {   // Преобразование массива
                                    $arr = [];
                                    unset($item['date']);
                                    unset($item['id']);
                                    foreach($item as $key=>$val){
                                        if ($key!='date_formatted')
                                            $arr[] = array(Convert::ToString($key),Convert::ToInt($val));
                                        else $arr[] = array(Convert::ToString($key),Convert::ToString($val));
                                    }
                                    $data[] = $arr;
                                }
                            }
                            
                            $colors = array('#3366CC','#DC3912','#FF9900','#109618');
                            
                            $ajax_result = array(
                                                'ok' => true,
                                                'data' => $data,
                                                'count' => count($data),
                                                'height'=>300,
                                                'width'=>725,
                                                'fields' => $fields,
                                                'colors' => $colors
                                            );
                            Response::SetArray('stats',$stats);
                            $module_template = 'bot_stats_table.html';
                            break;
                        }
                        
                        Response::SetArray('data_titles',$fields);
                    
                        Response::SetString('bot_alias',$bot_alias);
                        $stats_list = $db->fetchall("SELECT *,DATE_FORMAT(`date`,'%d.%m.%Y') AS date_formatted FROM ".$sys_tables['pages_visits_'.$bot_alias.'_full']."");
                        Response::SetArray('bot_stats',$stats_list);
                        $stats_list_today = $db->fetchall("SELECT *,DATE_FORMAT(visit_date,'%d.%m.%Y %H:%i:%s') AS visit_date_formatted FROM ".$sys_tables['pages_visits_'.$bot_alias.'_day']." ORDER BY visit_date DESC LIMIT 30");
                        Response::SetArray('bot_stats_today',$stats_list_today);
                        $module_template = "bot_stats.html";
                        break;
                    case $action == "stats-chart":
                        $get_parameters = Request::GetParameters(METHOD_GET);
                        $date_start = (!empty($get_parameters['date_start'])?$get_parameters['date_start']:"");
                        $date_end = (!empty($get_parameters['date_end'])?$get_parameters['date_end']:"");
                        $ajax_result = CrawlerCatcher::getStatsChartData($bot_alias,$date_start,$date_end);
                        $fields = array(
                                  array('string','Дата')
                                  ,array('number','Заходы на сайт')
                                  ,array('number','Показано ссылок')
                                  ,array('number','Посещено страниц из ранее показанных')
                                  ,array('number','Проиндексировано'));
                        Response::SetArray('data_titles',$fields);
                        break;
                    //страницы проверенные на индекс
                    case $action == "index_checked" && empty($this_page->page_parameters[5]):
                        Response::SetString('bot_alias',$bot_alias);
                        $action = empty($this_page->page_parameters[4]) ? "" : $this_page->page_parameters[4];
                        $action = ($action == 'indexed'?1:2);
                        Response::SetInteger('action',$action);
                        $list = $db->fetchall("SELECT CONCAT('http://".$_SERVER['HTTP_HOST']."','/',url) AS full_url, url, DATE_FORMAT(index_checked,'%d.%m.%Y %H:%i:%s') AS index_checked ".($action == 1?",DATE_FORMAT(date_out,'%d.%m.%Y %H:%i:%s') as date_out":"").",shown_total,bot_visits_total
                                               FROM ".$sys_tables['pages_not_indexed_yandex']."
                                               WHERE index_checked NOT LIKE '%0000%' AND in_index = ".$action." ORDER BY shown_total DESC");
                        Response::SetInteger('total_count',count($list));
                        Response::SetArray('bot_checked',$list);
                        $module_template = "bot_checked.html";
                        break;
                    //экспорт в CSV
                    case $action == "index_checked" && !empty($this_page->page_parameters[5]):
                        $action = empty($this_page->page_parameters[4]) ? "" : $this_page->page_parameters[4];
                        $action = ($action == 'indexed'?1:2);
                        header("Content-type: text/csv");
                        header("Content-Disposition: attachment; filename=file.csv");
                        header("Pragma: no-cache");
                        header("Expires: 0");

                        $list = $db->fetchall("SELECT CONCAT('http://".$_SERVER['HTTP_HOST']."','/',url) AS full_url, 
                                                      url, 
                                                      DATE_FORMAT(index_checked,'%d.%m.%Y %H:%i:%s') AS index_checked ".($action == 1?",DATE_FORMAT(date_out,'%d.%m.%Y %H:%i:%s') as date_out":"").",
                                                      shown_total,bot_visits_total
                                               FROM ".$sys_tables['pages_not_indexed_yandex']."
                                               WHERE index_checked NOT LIKE '%0000%' AND in_index = ".$action." ORDER BY shown_total DESC");
                        foreach($list as $key=>$item){
                            //$output[] = 
                        }
                        /*
                        $links_box = new Template("bot_checked.html","/modules/seo/");
                        Response::SetBoolean('only_table',true);
                        Response::SetArray('bot_checked',$list);
                        $links_html = $links_box->Processing();
                        echo ''.htmlentities(iconv("utf-8", "windows-1251", $links_html),ENT_QUOTES,"cp1251").'';*/
                        $module_template = "/templates/clearcontent.html";
                        break;
                }
                break;
            //список страниц которые можно добавить в таблицу робота
            case $action == 'pages_to_add':
                $url = Request::GetString('search_string',METHOD_POST);
                $ajax_result['list'] = $db->fetchall("SELECT id,url AS title FROM ".$sys_tables['pages_seo']." WHERE url LIKE '".trim($url,'/')."%'");
                if(count($ajax_result['list'])>200) $ajax_result['list'] =array('0'=>array('id'=>'0','title'=>'слишком много результатов, ('.count($ajax_result['list']).')'));
                $ajax_result['ok'] = true;
                break;
            //проверяем в индексе google ли страница
            case $action == 'check':
                $page_id = Request::GetInteger('page_id',METHOD_POST);
                $page_url = $db->fetch("SELECT CONCAT('".$_SERVER['HTTP_HOST']."','/',url,'/') AS url FROM ".$sys_tables['pages_seo']." WHERE id = ".$page_id)['url'];
                $res = CrawlerCatcher::checkGoogleIndex($page_url);
                $ajax_result['in_index'] = $res;
                $ajax_result['ok'] = true;
                break;
            //переходы с поиска
            case $action == "from_search":
                $crawlers = Config::$values['crawlers_aliases'];
                $item_result = [];
                $from_search = [];
                
                foreach($crawlers as $key=>$item){
                    $item_result = $db->fetchall("SELECT '".$item."' AS stack,url,IF(date_out = '0000-00-00 00:00:00','в стеке','убрано из стека') AS stack_status,googletm,yandextm,mailrutm
                                                  FROM ".$sys_tables['pages_not_indexed_'.$item],'url');
                    foreach($item_result as $k=>$i){
                        if(empty($from_search[$k])){
                            $from_search[$k] = array('url'=>"",'google_stack'=>"",'yandex_stack'=>"",'mailru_stack'=>"",'googletm'=>"",'yandextm'=>"",'mailrutm'=>"",);
                            $from_search[$k]['url'] = $k;
                        } 
                        $from_search[$k][$item.'_stack'] = $i['stack_status'];
                        if($i['googletm'] != '0000-00-00 00:00:00') $from_search[$k]['googletm'] = $i['googletm'];
                        if($i['yandextm'] != '0000-00-00 00:00:00') $from_search[$k]['yandextm'] = $i['yandextm'];
                        if($i['mailrutm'] != '0000-00-00 00:00:00') $from_search[$k]['mailrutm'] = $i['mailrutm'];
                    }
                }
                
                $totals['total'] = 0;
                $totals['googletm'] = 0;
                $totals['yandextm'] = 0;
                $totals['mailrutm'] = 0;
                foreach($from_search as $key=>$item){
                    if(empty($item['googletm']) && empty($item['yandextm']) && empty($item['mailrutm'])) unset($from_search[$key]);
                    else{
                        if(!empty($item['googletm'])){
                            ++$totals['googletm']; ++$totals['total'];
                        }
                        if(!empty($item['yandextm'])){
                            ++$totals['yandextm']; ++$totals['total'];
                        }
                        if(!empty($item['mailrutm'])){
                            ++$totals['mailrutm']; ++$totals['total'];
                        }
                    }
                }
                Response::SetArray('crawlers',$crawlers);
                Response::SetArray('totals',$totals);
                Response::SetArray('from_search',$from_search);
                $module_template = "from_search.html";
                break;
            //главная
            default:
                $crawlers = Config::$values['crawlers_aliases'];
                foreach($crawlers as $key=>$item){
                    $item_pages = $db->fetchall("SELECT IF(date_out != '0000-00-00 00:00:00','out_of_stack','in_stack') AS stack_status,count(*) AS amount
                                                 FROM ".$sys_tables['pages_not_indexed_'.$item]."
                                                 GROUP BY date_out!='0000-00-00 00:00:00'",'stack_status');
                    $item_pages_outed = $db->fetchall("SELECT IF(in_index = 1,'indexed','back_to_stack') AS index_status, COUNT(*) AS amount 
                                                       FROM ".$sys_tables['pages_not_indexed_'.$item]." 
                                                       WHERE index_checked NOT LIKE '%0000%' 
                                                       GROUP BY in_index = 1",'index_status');
                                                 
                    $links_shown = $db->fetch("SELECT SUM(shown_today) AS links_shown FROM ".$sys_tables['pages_not_indexed_'.$item]." WHERE shown_today>0");
                    
                    $crawlers[$key] = [];
                    $crawlers[$key]['alias'] = $item;
                    $crawlers[$key]['title'] = $item;
                    if(!empty($item_pages)){
                        $crawlers[$key]['in_stack'] = (empty($item_pages['in_stack'])?0:$item_pages['in_stack']['amount']);
                        $crawlers[$key]['out_of_stack'] = (empty($item_pages['out_of_stack'])?0:$item_pages['out_of_stack']['amount']);
                        $crawlers[$key]['indexed'] = (empty($item_pages_outed['indexed'])?0:$item_pages_outed['indexed']['amount']);
                        $crawlers[$key]['back_to_stack'] = (empty($item_pages_outed['back_to_stack'])?0:$item_pages_outed['back_to_stack']['amount']);
                        $crawlers[$key]['links_shown'] = (!empty($links_shown)?$links_shown['links_shown']:0);
                    }else{
                        $crawlers[$key]['not_indexed'] = 0;
                        $crawlers[$key]['indexed'] = 0;
                        $crawlers[$key]['indexed'] = 0;
                        $crawlers[$key]['back_to_stack'] = 0;
                        $crawlers[$key]['links_shown'] = (!empty($links_shown)?$links_shown['links_shown']:0);
                    }
                }
                Response::SetArray('crawlers',$crawlers);
                //$google_pages = $db->fetchall("SELECT COUNT(*) AS indexed_amount FROM ".$sys_tables[]." WHERE ")
                $module_template = "not_indexed_main.html";
                break;
        }
        break;
    default:
        $module_template = 'admin.list.html';
        // формирование списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['url'])) $conditions[] = "`url` LIKE '%".$db->real_escape_string($filters['url'])."%'";
            if(!empty($filters['pretty_url'])) {
                if(empty($filters['match']) || $filters['match'] == 1) $conditions[] = "`pretty_url` LIKE '%".$db->real_escape_string($filters['pretty_url'])."%'";
                else $conditions[] = "`pretty_url` = '".$db->real_escape_string($filters['pretty_url'])."'";
            }
        }
        if(!empty($conditions)) $condition = implode(' AND ', $conditions);
        else $condition = '';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['pages_seo'], 30, $condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/seo'                                  // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)               // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page=";   // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        $sql = "SELECT id,url,pretty_url,title,h1_title FROM ".$sys_tables['pages_seo'];
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY `url` ASC";
        $sql .= " LIMIT ".$paginator->getLimitString($page); 
        $list = $db->fetchall($sql);
        Response::SetArray('list', $list);
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk.'='.$gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>