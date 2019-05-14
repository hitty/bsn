<?php
/*
update `pages_seo` set url = REPLACE(url, 'estate/build/zhiloy_kompleks/search', 'zhiloy_kompleks')
update `pages_seo` set url = REPLACE(url, 'estate/build/zhiloy_kompleks', 'zhiloy_kompleks')
update `pages_seo` set url = REPLACE(url, 'build/zhiloy_kompleks', 'zhiloy_kompleks')
*/
 //редирект с /estate/build на без /estate/build
if(strstr($this_page->real_url, 'estate/build/') != '' && $this_page->first_instance) {
    Host::Redirect( '/' . str_replace('estate/build/', '',  trim($this_page->real_url,'/' )) . '/' );
}
require_once('includes/class.paginator.php');
require_once('includes/class.estate.php');
require_once('includes/class.estate.statistics.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.common.php');
require_once('includes/class.housing_estates.rating.php');

//хлебные крошки по умолчанию
$page_url = $this_page->page_url;
//для карточки с ЧПУ редиректом основные параметры из ЧПУ
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
require_once('includes/class.housing_estates.php');
Response::SetString('img_folder',Config::Get('img_folders/housing_estates'));                
$estate_type = 'zhiloy_kompleks';
Response::SetString('estate_type',$page_url);                    
//малоэтажные комплексы
if(!empty($this_page->module_parameters) && $this_page->module_parameters['low_rise']==1) {
    $low_rise = true;
    Response::SetBoolean('low_rise',$low_rise);
    Response::SetString('mainpage_h1', empty($this_page->page_seo_h1) ? 'Малоэтажное строительство' : $this_page->page_seo_h1);
} else Response::SetString('mainpage_h1', empty($this_page->page_seo_h1) ? 'Жилые комплексы' : $this_page->page_seo_h1);

//Апартаменты
$is_apartments = $page_url == 'zhiloy_kompleks' ? false : true; 
// обработка общих action-ов
switch(true){
    case (
        $page_url == 'housing_estates'
    ):
        Host::Redirect('/zhiloy_kompleks/'.(!empty($this_page->page_parameters[0])?$this_page->page_parameters[0].'/'.(!empty($this_page->page_parameters[1])?$this_page->page_parameters[1].'/':''):''));
        break;
    case (
        $page_url == 'service/rating_zhylyh_kompleksov'
    ):
        Host::Redirect('/service/ratings');
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Жилые комплексы | Апартаменты
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $page_url == 'zhiloy_kompleks': 
   case $page_url == 'apartments': 
 
        switch(true){
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Жилые комплексы  - Результаты поиска + главная страница
           ////////////////////////////////////////////////////////////////////////////////////////////////
           case empty($action):
                $estate_search = new EstateSearch();
                list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams();
                // подключение поисковой формы 
                require_once("includes/form.estate.php");
                
                if(empty($ajax_mode)){
                    $GLOBALS['js_set'][] = '/js/form.validate.js';
                    $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
                    $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
                    $GLOBALS['css_set'][] = '/css/estate_search.css';
                    $GLOBALS['css_set'][] = '/css/autocomplete.css';

                    $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
                    $GLOBALS['js_set'][] ="/modules/favorites/favorites.js";
                    $GLOBALS['css_set'][] = '/modules/housing_estates/style.css';
                    
                    $GLOBALS['css_set'][]='/css/jquery-ui.css';
                    $GLOBALS['js_set'][]='/js/jquery-ui.min.js';
                    $GLOBALS['js_set'][]='/modules/credit_calculator/mortgage-application.js';
                    $GLOBALS['js_set'][]='/modules/credit_calculator/block' . ( DEBUG_MODE ? '' : '.min' ) . '.js';
                    $GLOBALS['css_set'][]='/modules/credit_calculator/block.css';
                    
                }

                switch(true){
                   ////////////////////////////////////////////////////////////////////////////////////////////////
                   // Жилые комплексы  -  главная страница
                   ////////////////////////////////////////////////////////////////////////////////////////////////
                   case empty($ajax_mode) && empty( $get_parameters ) && $page_url != 'apartments':
                        $module_template = 'mainpage.html';
                        Response::SetBoolean( 'payed_format', true );
                        Response::SetBoolean( 'show_topline', true );
                        $GLOBALS['js_set'][] = '/modules/housing_estates/rating.script.js';
                        $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
                        $GLOBALS['css_set'][] = '/modules/housing_estates/rating.style.css';
                        $GLOBALS['css_set'][] = '/modules/comments/style.css';
                        
                        break;
                   ////////////////////////////////////////////////////////////////////////////////////////////////
                   // Жилые комплексы  - Результаты поиска
                   ////////////////////////////////////////////////////////////////////////////////////////////////
                   default:
                        
                        // "прямые" условия
                        $housing_estates = new HousingEstates();
                        $where = $housing_estates->makeWhereClause($clauses, $is_apartments);
                        if(!empty($reg_where)){
                            $where .= " AND (".implode(" OR ", $reg_where).")";
                        }        
                        //поиск по рейтингу района
                        if(!empty($parameters['id_rating'])){
                            $rating_ids = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates_districts_old']." WHERE id = ?", $parameters['id_rating']);
                            if(!empty($rating_ids)) $where = $sys_tables['housing_estates'].".id IN (".$rating_ids['housing_estates_ids'].") ";
                        }
                        if(!empty($parameters['ids'])) $where = $sys_tables['housing_estates'].".id IN (".$parameters['ids'].") ";

                        //подписка на поиск
                        EstateSubscriptions::Init($this_page->real_url);
                        list($subscription_title, $description) = EstateSubscriptions::getTitle(false, $parameters, true, false, true);

                        
                        //поиск по карте
                        if(!empty($parameters['top_left_lat']) || !empty($parameters['bottom_right_lng']) || !empty($parameters['map_mode'])) $map_mode = true;
                        Response::SetBoolean('map_mode', !empty($map_mode) ? $map_mode : false);
                        if(empty($map_mode)){
                            $view_type = Request::GetInteger('view_type', METHOD_GET);
                            if(empty($view_type)) $view_type = Cookie::GetString('View_type');
                            if(empty($view_type)){
                                $view_type = 'list';
                                Cookie::SetCookie('View_type', $view_type, 60*60*24*30, '/');
                            }
                            Response::SetString('view_type', $view_type);                            
                            
                            // кол-во элементов в списке
                            if(empty($count)) $count = Request::GetInteger('count', METHOD_GET);
                            if(!empty($count))
                                $get_parameters['count'] = $count;
                            else
                                $count = Cookie::GetInteger('View_count_estate');
                            if(empty($count)) {
                                $count = 20;
                                Cookie::SetCookie('View_count_estate', Convert::ToString($count), 60*60*24*30, '/');
                            }
                            //страница рейтинга ЖК
                            if(!empty($parameters['advanced']) && $parameters['advanced'] == 1){
                                Response::SetBoolean('only_objects', true);
                                Response::SetBoolean('not_show_finded', true);
                                $rating = true;
                            }
                            // сортировка
                            $sortby = Request::GetInteger('sortby', METHOD_GET);
                            if(!empty($sortby)) Response::SetBoolean('noindex',true); //meta-тег robots = noindex
                            $orderby = $sys_tables['housing_estates'].".advanced = 1 DESC, ". ( !empty($rating) ? $sys_tables['housing_estates'].".random_sorting, " : "" ) .$sys_tables['housing_estates'].".id_main_photo > 0 DESC, ";
                            switch($sortby){
                                case 11:
                                    //по рейтингу по возрастанию
                                    $orderby .= $sys_tables['housing_estates'].".rating !=0  ASC, rating ASC";
                                    break;
                                case 10:
                                    //по рейтингу по убыванию
                                    $orderby .= $sys_tables['housing_estates'].".rating !=0  DESC, rating DESC";
                                    break;
                                case 9: 
                                    // по застройщику по убыванию
                                    $orderby .= $sys_tables['housing_estates'].".class  ASC";
                                    break;
                                case 8: 
                                    // по застройщику по возрастанию
                                    $orderby .= $sys_tables['housing_estates'].".class  DESC";
                                    break;
                                case 7: 
                                    // по застройщику по убыванию
                                    $orderby .= $sys_tables['housing_estates'].".id_user > 0  DESC, developer_title DESC";
                                    break;
                                case 6: 
                                    // по застройщику по возрастанию
                                    $orderby .= $sys_tables['housing_estates'].".id_user > 0  DESC, developer_title ASC";
                                    break;
                                case 5: 
                                    // по метро по убыванию
                                    $orderby .= $sys_tables['housing_estates'].".id_subway > 0 DESC, subway DESC"; 
                                    break;
                                case 4: 
                                    // по метро по возрастанию
                                    $orderby .= $sys_tables['housing_estates'].".id_subway > 0 DESC, subway ASC"; 
                                    break;
                                case 3: 
                                    // по району по возрастанию
                                    $orderby .= $sys_tables['housing_estates'].".id_region DESC, ".$sys_tables['housing_estates'].".id_district > 0 DESC, district DESC, district_area DESC"; 
                                    break;
                                case 2: 
                                    // по району по убыванию
                                    $orderby .= $sys_tables['housing_estates'].".id_region DESC, ".$sys_tables['housing_estates'].".id_district > 0 DESC, district ASC, district_area ASC"; 
                                    break;
                                case 1: 
                                default: 
                                    // по району по убыванию
                                    $orderby .= "build_total_objects DESC";
                                    break;
                            }
                            
                            // страница списка
                            $page = Request::GetInteger('page', METHOD_GET);
                            if ((isset($page))&&($page==0)){
                                //чтобы не потерялись фильтры, надо включить их в redirect
                                $parameters = Request::GetParameters(METHOD_GET);
                                //здесь будем накапливать строку с get-параметрами
                                $url = [];
                                foreach($parameters as $key=>$item){
                                    if ($key!='path'){
                                        if ($key!='page') $url[]=$key.'='.$item;
                                        else $url[]=$key.'=1';//заменяем page на 1 страницу
                                    } 
                                }
                                $url='?'.implode('&',$url);
                                //url не может быть пуст - там будет хотя бы page
                                Host::Redirect('/'.$this_page->requested_path.'/'.$url);
                                exit(0);
                            }
                            if(empty($page)) $page = 1;
                            else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
                            $paginator = new Paginator($sys_tables['housing_estates'], $count, $where);
                            //только подсчет кол-ва объектов через ajax
                            if( !empty( $parameters['ajax_count']) ){
                                $ajax_result['ok'] = true;
                                $total_items = !empty($paginator->total_items_count) ? $paginator->total_items_count : $paginator->items_count;
                                $ajax_result['count'] = Convert::ToNumber( $total_items ) . ' ' . makeSuffix($total_items, 'объект', array('','а','ов') );
                                break;
                            }

                            $url_params = $parameters;
                            unset($url_params['path']);
                            if(!empty($url_params['page'])) unset($url_params['page']);
                            if(!empty($url_params['sortby'])) unset($url_params['sortby']);
                            $paginator_link_base = '/'.$this_page->real_path.'/?'.(!empty($this_page->query_params) ? '' . implode('&', $this_page->query_params) . '&' : '');
                            
                            Response::SetString('sorting_url', $paginator_link_base.'page='.$page.'&sortby=');
                            Response::SetInteger('sortby', $sortby);

                            if( !empty( $ajax_mode ) ) {
                                Response::SetBoolean('only_objects', !empty($parameters['only_objects']));
                                Response::SetBoolean('ajax_pagination', true);
                            }
                            if(empty($ajax_mode)){
                                //редирект с несуществующих пейджей
                                if($page<0){
                                    Host::Redirect('/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page=1');
                                    exit(0);
                                }
                                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                                    Host::Redirect('/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page='.$paginator->pages_count);
                                    exit(0);
                                }
                            }
                            //формирование url для пагинатора
                            $paginator->link_prefix = $paginator_link_base.(!empty($sortby)?'sortby='.$sortby.'&':'').'page=';
                            if($paginator->pages_count>1){
                                Response::SetArray('paginator', $paginator->Get($page));
                            }
                            
                            $list = $housing_estates->Search($where, $count, $count*($page-1), $orderby);
                            $list = Favorites::ToList($list, 5);
                            
                            //увеличение счетчика показов
                            if(!empty($list)){
                                $ids = [];
                                $clear_ids = [];
                                foreach($list as $key=>$item){
                                    $ids[] = '('.$item['id'].', 1, "'.Host::getUserIp().'", "'.$db->real_escape_string($_SERVER['HTTP_USER_AGENT']).'", "'.Host::getRefererURL().'")';
                                    $clear_ids[] = $item['id'];
                                } 
                                $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_day_shows']." (id_parent, type, ip, browser, ref) VALUES ".implode(",",$ids)."");
                                $db->query("UPDATE ".$sys_tables['housing_estates']." SET search_count = search_count + 1 WHERE id IN (".implode(',',$clear_ids).")");
                            }
                            Response::SetArray('list', $list);
                            Response::SetInteger('full_count', $paginator->items_count);                
                            Response::SetString('requested_url', $this_page->requested_url);  
                            
                            if(!empty($parameters['search_form']) ) {
                                $ajax_result['ok'] = true;
                                $module_template = 'list.block.html';
                            }
                            else $module_template = 'list.html';
                        } else { // поиск по карте
                            $index = 0;
                            $count = 100;
                            //вывод рейтинговых ЖК
                            if(!empty($parameters['rating'])) {
                                $where .= " AND ( ".$sys_tables['housing_estates'].".id_district > 0 OR ( ".$sys_tables['housing_estates'].".id_area > 0 AND ".$sys_tables['housing_estates'].".id_region = 47 ))  AND r.expert_rating > 0 ";
                                $orderby = "id_district, id_area, r.expert_rating DESC";
                                $count = 9999;
                            } else $orderby = $sys_tables['housing_estates'].".advanced=1 DESC, ".$sys_tables['housing_estates'].".id_main_photo>0 DESC";
                            $map_list = $housing_estates->Search($where, $count, 0, $orderby, $map_mode);
                            $ajax_result['count'] = count($map_list);
                            $indexes = [];
                            //выдача по рейтингу
                            if(!empty($parameters['rating'])) {
                                //вывод 5 ЖК по каждому району
                                $district_counter = 0;
                                $list = [];
                                $district = 0;
                                foreach($map_list as $key=>$item){
                                    $current_district = !empty($item['id_district']) ? $item['id_district'] : $item['id_area'];
                                    if($current_district == $district){
                                        if($district_counter <= 4) {
                                            $list[$key] = $item;
                                            ++$district_counter;
                                        }
                                    } else {
                                       $district_counter = 1;
                                       $list[$key] = $item;
                                       $district = $current_district;
                                    } 
                                }
                                $map_list = $list;
                            }
                            //поиск статьи по району
                            if( ( !empty($parameters['districts']) ) || ( !empty($parameters['district_areas']) ) ) {
                                require_once('includes/class.content.php');
                                require_once('includes/class.tags.php');
                                $articles = new articlesContent();
                                $articles_item = [];
                                //название тега
                                if( !empty($parameters['districts']) ) {
                                    $district_title = $db->fetch("SELECT title FROM ".$sys_tables['districts']." WHERE id = ?", $parameters['districts'][0])['title'];
                                    $articles_item = $articles->getarticlesListByTag(1, 0, $district_title.' район', false, false, true);
                                } else {
                                    $district_area = is_array($parameters['district_areas']) ? $parameters['district_areas'][0] : $parameters['district_areas'];
                                    $district_title = $db->fetch("SELECT offname FROM ".$sys_tables['geodata']." WHERE id = ?", $district_area)['offname'];
                                    $articles_item = $articles->getarticlesListByTag(1, 0, $district_title.' район ЛО', false, false, true);
                                }
                                if(!empty($articles_item)) {
                                    //вырезаем теги
                                    $articles_item[0]['content'] = preg_replace("'<table[^>]*?>.*?</table>'si","",$articles_item[0]['content']);
                                    $ajax_result['articles'] = $articles_item;
                                }
                                $linkedTags = Tags::getLinkedTags($articles_item[0]['id'], $sys_tables['articles_tags']);
                                $tags = [];
                                foreach($linkedTags as $k=>$tag) if(strstr($tag['title'], 'район') != '') $tags[] = $tag['title'];
                                if(!empty($tags)) $ajax_result['districts'] = implode(', ', $tags);
                                
                            }
                            //отправляем в карту только название, текст и координаты
                            foreach($map_list as $key=>$item){
                                $points[$key]['title'] =  $item['title'];
                                if(!empty($item['lat']) && !empty($item['lng']) && $item['lng']>0 && $item['lat']>0){
                                    $points[$key]['lat'] =  $item['lat'];
                                    $points[$key]['lng'] =  $item['lng'];
                                } else {
                                    $coords = file_get_contents('https://geocode-maps.yandex.ru/1.x/?format=json&geocode='.$item['full_address']);
                                    $json = json_decode($coords,true); 
                                    $data = explode(" ",$json['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']);                      
                                    if(!empty($data[0]) && !empty($data[1])) {
                                        $db->query("UPDATE ".$sys_tables[$estate_type]." SET lat=?, lng=? WHERE id=?",$data[1],$data[0],$item['id']);
                                        $points[$key]['lat'] =  $data[1];
                                        $points[$key]['lng'] =  $data[0];

                                    }
                                }
                                //сроки сдачи
                                $query_complete = false;
                                $queries = $housing_estates->getQueries($item['id']);
                                foreach($queries as $k=>$query_item) if($query_item['id_build_complete'] == 4) $query_complete = true;
                                Response::SetArray('queries', array(
                                        'query_from' => !empty($queries['0']['build_complete_title']) ? $queries['0']['build_complete_title'] : false, 
                                        'query_to' => !empty($queries[count($queries)-1]['build_complete_title']) ? $queries[count($queries)-1]['build_complete_title'] : false, 
                                        'query_complete' => $query_complete
                                    )
                                );
                                if(!empty($item['expert_rating'])) $points[$key]['rating'] =  Convert::ToSquare($item['expert_rating']);
                                if(!empty($item['map_status'])) $points[$key]['map_status'] =  $item['map_status'];

                                //добавление в избранное
                                $item = Favorites::ToItem($item,5);
                                //платная карточка на карте
                                if($item['map_status'] == 1){
                                    $info = $housing_estates->getItem($item['id']);
                                    $seller_phone = !empty($info['agency_seller_advert_phone']) ? $info['agency_seller_advert_phone'] : ( !empty($info['agency_seller_phone_1']) ? $info['agency_seller_phone_1'] : ( !empty($info['agency_developer_advert_phone']) ? $info['agency_developer_advert_phone'] : $info['agency_developer_phone_1']) );
                                    Response::SetString('seller_phone', $seller_phone);
                                    $item = array_merge($item, $info);
                                }
                                Response::SetArray('item', $item);
                                //шаблон
                                $estate_template = new Template('list.block.item.html',$this_page->module_path);
                                $points[$key]['html'] = $estate_template->Processing();
                                $points[$key]['id'] = $item['id'];
                                
                            }
                            //группировка маркеров
                            $ajax_result['total'] = count($map_list);
                            $ajax_result['ok'] = true;
                            if(!empty($points)) $ajax_result['points'] = $points;
                        }
                        if($ajax_mode) {
                            $ajax_result['ok'] = true;
                            if(!empty($parameters['agency']) || !empty($parameters['developer'])|| !empty($parameters['only_objects'])) Response::SetBoolean('only_objects', true);
                        }
                        $h1 = !empty($this_page->page_seo_h1) && ( ( !empty($ajax_mode) && $this_page->requested_path != $this_page->page_pretty_url ) || ( empty($ajax_mode) && $this_page->requested_url == $this_page->requested_path ) ) ? $this_page->page_seo_h1 : $subscription_title;
                        Response::SetString('mainpage_h1', $h1);

                        if(!empty($ajax_mode)) {
                            $ajax_result['h1'] = $h1;
                            if($this_page->page_pretty_url != $this_page->requested_path) {
                                $ajax_result['pretty_url'] = $this_page->page_pretty_url;
                                $ajax_result['title'] = $this_page->page_seo_title;
                            } else {
                                $ajax_result['title'] = $h1;
                                
                            }
                            $ajax_result['seo_text'] = ( !empty($parameters['page']) || !empty($parameters['sortby']) ) || $this_page->page_pretty_url == $this_page->requested_path ? '' : $this_page->page_seo_text;  
                        }
                        $new_meta = array(
                            'h1'=>$h1, 
                            'title' => $h1 . ( !empty($paginator->items_count) ? ' - ' . Convert::ToNumber($paginator->items_count) . makeSuffix($paginator->items_count, ' объявлени', array('е', 'я', 'й'))  : '' ),
                            'description' => $description . ' ☆ Уникальные предложения, которых не найти на других сайтах. ☆ Мы постоянно отслеживаем актуальность и достоверность объявлений'
                        );
                        $this_page->manageMetadata($new_meta, true);
                        Response::SetString( 'h1', $h1 );
                        Response::SetBoolean( 'search_form', true );
                        
                        break;                   
                   break;
                }
                 break;

           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Жилые комплексы - Карта
           ////////////////////////////////////////////////////////////////////////////////////////////////
           case $action == 'map':
           if($ajax_mode) {
                $this_page->page_cache_time = Config::$values['blocks_cache_time']['housing_estates_map'];        

                // формирование набора условий для поиска
                $estate_search = new EstateSearch();
                list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams();
                // подключение поисковой формы 
                require_once("includes/form.estate.php");

                $housing_estates = new HousingEstates();
                $where = $housing_estates->makeWhereClause($clauses, $is_apartments);
                
                $list = $housing_estates->Search($where,false,false);
                $points = []; 
                $count = $index = 0;
                //отправляем в карту только название, текст и координаты
               $ids = [];
               foreach($list as $key=>$item){
                    if(!empty( $item ) && !empty($item['lat']) && !empty($item['lng']) && $item['lng']>0 && $item['lat']>0){
                        $points[$index]['lat'] =  $item['lat'];
                        $points[$index]['lng'] =  $item['lng'];
                        $points[$index]['link'] = "/zhiloy_kompleks/".$item['id']."/";
                        $points[$index]['title'] =  (!empty($low_rise)?'Малоэтажный комплекс ':'ЖК ').$item['title'];
                        $points[$index]['icon_url'] = $item['advanced']==1?'/img/map_icons/icon_map_housing_complex_payed.png':'/img/map_icons/icon_map_housing_complex.png';
                        $points[$index]['advanced'] = $item['advanced'];
                        ++$index;
                        
                    }
                }
                    
                //группировка маркеров
                $ajax_result['cluster'] = true;
                $ajax_result['ok'] = true;
                $ajax_result['points'] = $points;
            }
            break;    
    //////////////////////////////////////////////////////////////////////////
    // блоки
    /////////////////////////////////////////////////////////////////////////
    case $action=='block':
        if(!$this_page->first_instance || $ajax_mode) {
            $action = $this_page->page_parameters[1];
                
            switch($action){
                //////////////////////////////////////////////////////////////////////////
                // Ход строительства
                /////////////////////////////////////////////////////////////////////////
                case 'gallery':
                    $id_parent = $this_page->page_parameters[2];
                    $get_parameters = Request::GetParameters(METHOD_GET);
                    $id = !empty($get_parameters['id']) ? $get_parameters['id'] : 0;
                    $module_template = 'block.gallery.html';
                    $housing_estates = new HousingEstates();
                    $item = $housing_estates->getItem($id_parent, false);
                    if($item['advanced']==1)  Response::SetBoolean('payed_format',true);
                    if(empty($id)) {
                        $photos = Photos::getList('housing_estates',$id_parent);
                        Response::SetString('type','main-gallery');
                    } else {
                        $photos = Photos::getList('housing_estates_progresses',$id);
                        Response::SetString('type','progress-gallery');
                        $progress_item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates_progresses']." WHERE id = ?", $id);
                        $months = Config::Get('months');
                        Response::SetArray('months', $months);
                        Response::SetArray('progress', $progress_item);
                        Response::SetArray('titles', array('header' => 'Ход строительства ЖК «' . $item['title'] . '», ' . $months[$progress_item['month']] . ', ' . $progress_item['year']));
                        
                    }
                    Response::SetArray('photos',$photos);     
                    Response::SetBoolean('only_objects',true);     
                    
                    $ajax_result['photos'] = $photos;
                    $ajax_result['ok'] = true;
                    break;
                //////////////////////////////////////////////////////////////////////////
                // рейтинг на главную страницу
                /////////////////////////////////////////////////////////////////////////
                case 'rating':
                case 'rating_mainpage':
                    /*
                    $item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates_districts']." ORDER BY RAND()");
                    Response::SetArray('item', $item);
                    */
                    $get_parameters = Request::GetParameters(METHOD_GET);
                    //рейтиг по классам
                    $count = $action != 'rating_mainpage' || !empty($get_parameters['mainpage'])? 10 : 20;
                    $where = array($sys_tables['housing_estates_voting'].".is_expert = 1");
                    $where[] = $sys_tables['housing_estates'].'.apartments = ' . ( empty( $is_apartments ) ? 2 : 1);
                    //поиск по региону + классу
                    $region = !empty( $get_parameters['region'] ) ?  $get_parameters['region']  : false;
                    if( !empty($region) ) $where[] = $sys_tables['housing_estates'].".id_region = ".$region;
                    
                    //поиск по апартаментам
                    if( $page_url == 'apartments') $get_parameters['district'] = 16;
                    
                    //список районов
                    if(!empty($get_parameters['district'])){
                        $item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates_districts_old']." WHERE id = ?", $get_parameters['district']);
                        if(!empty($item['housing_estates_ids'])) $where[] = $sys_tables['housing_estates'].".id IN (".$item['housing_estates_ids'].")";
                        
                        if( !empty( $item['id_articles'] ) ) {
                            //аналитическая статья
                            require_once('includes/class.content.php');
                            $analytcs = new Content('articles');
                            $articles_list = $analytcs->getList(false, false, false, false, $sys_tables['articles'] . ".id = " . $item['id_articles'] );
                            if( !empty($articles_list) ) Response::SetArray('articles_list', $articles_list);
                            Response::SetString('content_type', 'articles');
                        }
                    }
                    //класс
                    if(!empty($get_parameters['class'])) $class = $get_parameters['class'];

                    if( empty($class) ) $class = !empty($this_page->page_parameters[2]) && $this_page->page_parameters[2] == 'class' && !empty($this_page->page_parameters[3]) && Validate::isDigit($this_page->page_parameters[3]) ? $this_page->page_parameters[3] : false;
                    if(!empty($class)) {
                        $where[] = $sys_tables['housing_estates'].".class = ".$class;
                        Response::SetInteger('class', $class);
                    } 

                    if(!empty($get_parameters['mainpage'])) $action = 'rating';
                    
                    Response::SetString('action', $action);
                    //блок рейтинга
                    $housing_estates_rating = new HousingEstatesRating();
                    if(!empty($where)){
                        $where = implode(" AND ", $where);
                        $rating_list = $housing_estates_rating->getRatingList(false, true, $count, $sys_tables['housing_estates'].".rating DESC", false, $where);
                        Response::SetArray('rating_list',$rating_list);
                    }
                    $ajax_result['ok'] = true;
                    $module_template = 'block.rating.html';     
                    break;    
                //////////////////////////////////////////////////////////////////////////
                // Последние / похожие / Блок для вкладки ЖК страницы компании
                /////////////////////////////////////////////////////////////////////////
                default:
                    if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'mainpage') $module_template = 'block.html';  // блок последних предложений на главной странице
                    else $module_template = 'list.block.html'; // блок последних предложений на главной раздела
                    Response::SetBoolean('only_objects', true);
                    Response::SetBoolean('not_show_finded', true);
                    if(!empty($this_page->page_parameters[2]) && Validate::isDigit($this_page->page_parameters[2])) $count = Convert::ToInteger($this_page->page_parameters[2]);
                    elseif(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'mainpage') $count = 2;
                    else $count = 3;
                    
                    $clauses = [];
                    
                    if($this_page->page_parameters[1] == 'company'){
                        $count = 0;
                        $sortby = Request::GetString('sortby',METHOD_GET);
                        if(!empty($sortby)) $sortby = "";
                        $agency_admin_id = Request::GetInteger('agency_id',METHOD_GET);
                        if(!empty($agency_admin_id)) $users_ids = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']."
                                                                        WHERE id_agency IN (SELECT id_agency FROM ".$sys_tables['users']." WHERE id = ".$agency_admin_id.") ")['ids'];
                        if(!empty($users_ids)){
                            $get_parameters = array('agency_id'=>$agency_admin_id);
                            $where = " ".$sys_tables['housing_estates'].".id_user IN (".$users_ids.") AND ".$sys_tables['housing_estates'].".published = 1";
                            $count = 10;
                            $paginator = new Paginator($sys_tables['housing_estates'], $count, $where);
                            $paginator->link_prefix = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page=';
                            Response::SetInteger('full_count', $paginator->items_count);
                            
                            $page = Request::GetInteger('page', METHOD_GET);
                            if(empty($page)) $page = 1;
                            $from = $count*($page-1);
                            if($paginator->pages_count>1){
                                Response::SetArray('paginator', $paginator->Get($page));
                            }
                            
                            Response::SetString('sorting_url', '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'sortby=');
                            Response::SetInteger('sortby', $sortby);
                        }
                    }
                    
                    $housing_estates = new HousingEstates();
                    
                    if($this_page->page_parameters[1] != 'company'){
                        $from = 0;
                        //малоэтажное строительство
                        if($this_page->page_parameters[1] != 'mainpage'){
                            if(!empty($low_rise)) {
                                $clauses['low_rise'] = array('value'=> 1);
                                $get_parameters['low_rise'] = 1;
                            }  else $clauses['low_rise'] = array('value'=> 2);  
                        }
                        $clauses['advanced'] = array('value'=> 1);           
                        $where = $housing_estates->makeWhereClause($clauses, 'not' );
                    }
                    $ajax_result['where'] = $where;
                    $order = $this_page->page_parameters[1] != 'mainpage' ? 
                                $sys_tables['housing_estates'].".advanced = 1 DESC, ".$sys_tables['housing_estates'].".random_sorting, ".$sys_tables['housing_estates'].".id_main_photo > 0 DESC, RAND()" :
                                $sys_tables['housing_estates'].".advanced = 1 DESC, ".$sys_tables['housing_estates'].".id_main_photo > 0 DESC, RAND()";
                    
                    $list = $housing_estates->Search($where, $count, $from, $order);
                    if(!empty($list)) {
                        if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'mainpage'){
                            if(count($list)<$count) $count = ((int) (count($list)/2))*2;
                        } else {
                            $list = Favorites::ToList($list, 5);
                            $this_page->page_cache_time = Config::$values['blocks_cache_time']['last_offers_block'];
                        }
                        //увеличение счетчика показов
                        $ids = [];
                        foreach($list as $key=>$item) $ids[] = '('.$item['id'].', 1, "'.$db->real_escape_string(Host::getUserIp()).'", "'.$db->real_escape_string($_SERVER['HTTP_USER_AGENT']).'", "'.$db->real_escape_string(Host::getRefererURL()).'")';
                        $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_day_shows']." (id_parent, type, ip, browser, ref) VALUES ".implode(",",$ids)."");
                        
                        if($ajax_mode) $ajax_result['ok'] = true;
                        Response::SetString('view_type', 'block');
                        Response::SetArray('list', $list); 
                    }
                    break;
            }
        } else Host::Redirect('/zhiloy_kompleks/');
        break;        
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // Жилые комплексы  - Поиск по названию
       ////////////////////////////////////////////////////////////////////////////////////////////////
        case !empty($action) && count($this_page->page_parameters)==1 && $action == 'title':
                if($ajax_mode) {
                    $search_string = Request::GetString('search_string', METHOD_POST);
                    $search_string = addslashes($search_string);
                    $sql = "SELECT id, `title`
                            FROM ".$sys_tables['housing_estates']."
                            WHERE (title LIKE '%".$search_string."%' OR reverse_title LIKE '%".$search_string."%') AND published = 1 AND apartments = " . ( empty( $is_apartments ) ? "2" : 1 ) . " 
                            ORDER BY title 
                            LIMIT 10";
                    $list = $db->fetchall($sql);
                    $ajax_result['ok'] = !empty($list);
                    $ajax_result['list'] = $list;
                } else Host::RedirectLevelUp();
            break;       

       ////////////////////////////////////////////////////////////////////////////////////////////////
       // Жилые комплексы  - Поиск по девелоперу
       ////////////////////////////////////////////////////////////////////////////////////////////////
        case !empty($action) && count($this_page->page_parameters)==1 && $action == 'developer_title':
                if($ajax_mode) {
                    $search_string = Request::GetString('search_string', METHOD_POST);
                    $search_string = addslashes($search_string);
                    $sql = "SELECT 
                                ".$sys_tables['users'].".id, 
                                ".$sys_tables['agencies'].".`title`
                            FROM ".$sys_tables['housing_estates']."
                            RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['housing_estates'].".id_user 
                            RIGHT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                            WHERE 
                                ".$sys_tables['users'].".agency_admin = 1 AND 
                                ".$sys_tables['agencies'].".title LIKE '%".$search_string."%' AND 
                                ".$sys_tables['housing_estates'].".published = 1  AND 
                                ".$sys_tables['housing_estates'].".apartments = " . ( empty( $is_apartments ) ? "2" : 1 ) . " 
                            GROUP BY ".$sys_tables['users'].".id
                            ORDER BY ".$sys_tables['agencies'].".title 
                            LIMIT 10";
                    $list = $db->fetchall($sql);
                    $ajax_result['ok'] = !empty($list);
                    $ajax_result['list'] = $list;
                } else Host::RedirectLevelUp();
            break;

       ////////////////////////////////////////////////////////////////////////////////////////////////
       // Жилые комплексы  - Карточка
       ////////////////////////////////////////////////////////////////////////////////////////////////
        case (!empty($action) && count($this_page->page_parameters)==1) || (count($this_page->page_parameters)==2 && $this_page->page_parameters[1]=='print'):
            $print=!empty($this_page->page_parameters[1]);
            if ($print){
                $GLOBALS['css_set'][]='/css/print.css';
                $GLOBALS['js_set'][]='/modules/estate/print.js';
                $this_page->page_template='/templates/print.html';
                Response::SetBoolean('print',true);
            }
            if(empty($ajax_mode)){
                $GLOBALS['js_set'][] = "/js/jquery.fullscreen.gallery.js";
                $GLOBALS['js_set'][] = "/modules/favorites/favorites.js";
                $GLOBALS['js_set'][] = "/modules/estate/list_options.js";
                $GLOBALS['js_set'][] = '/modules/infrastructure/yandex.map.js';
                $GLOBALS['css_set'][] = '/modules/infrastructure/styles.css';
                $GLOBALS['css_set'][] = '/modules/popups/style.css';

                $GLOBALS['js_set'][] = '/modules/housing_estates/item.js';
                $GLOBALS['js_set'][] = '/js/phones.click.js';
                $GLOBALS['css_set'][] = '/css/phones.click.css';
                $GLOBALS['css_set'][] = '/modules/housing_estates/style.css';
                $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
                $GLOBALS['css_set'][] = '/css/estate_search.css';
                $GLOBALS['js_set'][] = '/js/form.validate.js';
                //отзывы о ЖК
                $GLOBALS['js_set'][] = '/modules/comments/script.js';
                $GLOBALS['css_set'][] = '/modules/comments/style.css';
                
                
            }
            if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]!='print') {Host::Redirect('/zhiloy_kompleks/'); break;}
            $id = $title = false;
            $id =  (Validate::isDigit($action)?Convert::ToInteger($action):0);
            $title = Convert::ToString($action);
            // получение информации об объекте
            $housing_estates = new HousingEstates();
            $item = $housing_estates->getItem($id, $title,true);
            if(empty($item)){
                Host::Redirect('/zhiloy_kompleks/');
                break;
            } else{
                if($id>0 && !empty($item['chpu_title'])) Host::Redirect('/zhiloy_kompleks/'.$item['chpu_title'].'/');
                if($page_url == 'apartments' && $item['apartments'] == 2) Host::Redirect('/zhiloy_kompleks/'.$item['chpu_title'].'/');
                else if($page_url == 'zhiloy_kompleks' && $item['apartments'] == 1) Host::Redirect('/apartments/'.$item['chpu_title'].'/');
            }
            //редирект архивной карточки на аналогичный объект
            if($item['published'] == 2){
                $published_item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates']." WHERE published = 1 AND title = ?", $item['title']);
                if(!empty($published_item)) Host::Redirect('/zhiloy_kompleks/'.$published_item['chpu_title'].'/');
            }
            $housing_estates_id = $item['id'];
            
            //форматируем рейтинг чтобы было два знака после запятой
            $item['rating'] = ( !empty($item['rating'] ) ? number_format( (float) $item['rating'], 2, '.', '' ) : 0 );
            
            $housing_estates_rating = new HousingEstatesRating();
            //рейтинг экспертов
            $expert_rating = $housing_estates_rating->getRatingList( false, 1, false, false, false, $sys_tables['housing_estates'] . ".id = " . $item['id'] );
            if( !empty( $expert_rating ) ) {
                $expert_rating = $expert_rating[0];
                $item['rating'] = $expert_rating['rating'];
                Response::SetArray('expert_rating', $expert_rating);
            }

            //рейтинг пользователей
            $user_rating = $housing_estates_rating->getRatingList( false, 2, false, false, false, $sys_tables['housing_estates'] . ".id = " . $item['id'] );
            if( !empty( $user_rating ) ) {
                $user_rating = $user_rating[0];
                $item['user_rating'] = $user_rating['rating'];
                Response::SetInteger('votes_total', !empty( $user_rating ) ? $user_rating['voters'] : 0 ) ;
            }


            //ранее отданный голос
            $voted_already = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates_voting']." WHERE ip = ? AND id_parent = ? AND is_expert = ?",Host::getUserIp(),$item['id'],$auth->expert);
            if( !empty( $voted_already ) ) Response::SetArray('voted_already', $voted_already);

            //увеличиваем счетчик просмотров если это не печать карточки
            if (!$print){
                $db->query("UPDATE ".$sys_tables['housing_estates']." SET views_count = views_count + 1 WHERE id = ?",$item['id']);
            }
            // проверка на изранное
            $item = Favorites::ToItem($item,5);
            //теперь телефоны показываются только если у соотв. компании расширенная страница или это расширенная карточка ЖК
            $item['seller_phone'] = '';
            
            //экспертное голослование
            $rating = new HousingEstatesRating();
            if( !empty( $auth->id ) ) {
                $housing_estate_expert = $rating->userInfo( false, !empty( $auth->id ) ? $auth->id : false, false );
                if( !empty( $housing_estate_expert ) ){
                    Response::SetArray( 'housing_estate_expert', $housing_estate_expert );
                    Response::SetBoolean( 'can_vote', $rating->canVote( $item['id'] ) );
                    //платный вид карточки для эксперта с его ЖК
                    if( in_array( $item['id'], $rating->housing_estates_ids ) ) {
                        $GLOBALS['js_set'][] = '/modules/housing_estates_rating/script.js';
                        $GLOBALS['css_set'][] = '/modules/housing_estates_rating/style.css';

                        $this_page->page_template = '/templates/client.only.logo.html';
                        $item['advanced'] == 1;
                        Response::SetBoolean('payed_format',true);
                    }
                }
            }
            
            //приведение телефона в удобный вид
            if($item['advanced'] == 1 ) {
                if(!empty($item['agency_seller_advert_phone'])) $seller_phone = $item['agency_seller_advert_phone'];
                elseif(!empty($item['agency_seller_phone_1'])) $seller_phone = $item['agency_seller_phone_1'];
                elseif(!empty($item['agency_developer_advert_phone'])) $seller_phone = $item['agency_developer_advert_phone'];
                elseif(!empty($item['agency_developer_phone_1'])) $seller_phone = $item['agency_developer_phone_1'];
            } elseif($item['agency_seller_payed_page'] == 1) {
                $seller_phone = !empty($item['agency_seller_advert_phone']) ? $item['agency_seller_advert_phone'] : $item['agency_seller_phone_1'];
            } else if($item['developer_payed_page'] == 1) {
                $seller_phone = !empty($item['agency_developer_advert_phone']) ? $item['agency_developer_advert_phone'] : $item['agency_developer_phone_1'];
            }
            if(!empty($seller_phone)){
                $seller_phone = Convert::ToPhone($seller_phone);
                if(!empty($seller_phone[0])) $item['seller_phone'] = $seller_phone[0];
            }
            if(empty($id)) $id = $item['id'];
            Response::SetInteger('id', $item['id']);
            ///
            if(empty($ajax_mode)){
                //приведение примечаний в общий вид
                $item['notes'] = Convert::StripText($item['notes']);
                $item['notes_default'] = Convert::StripText($item['notes_default']);
                if(empty($item['notes_default']) && $item['advanced'] == 2) $item['notes_default'] = $item['notes'];
                
                Response::SetArray('item',$item);
                //фотогалерея
                $photos = Photos::getList('housing_estates',$item['id']);
                Response::SetArray( 'photos', $photos );
                Response::SetBoolean( 'fullscreen_gallery', !empty( $photos ) );
                //заголовки страницы
                $titles = $housing_estates->getTitles($item['id']);
                Response::SetArray('titles',$titles);
                //очереди
                $queries = $housing_estates->getQueries($item['id']);
                if(!empty($queries)){
                    $query_complete = false;
                    foreach($queries as $k=>$query_item){ 
                        if($query_item['id_build_complete'] == 4) {
                            $query_complete = true;
                            unset($queries[$k]);
                        }
                    }
                    $queries = array_values($queries);
                    Response::SetArray('queries', array(
                            'query_from' => !empty($queries['0']['build_complete_title']) ? $queries['0']['build_complete_title'] : false, 
                            'query_to' => !empty($queries[count($queries)-1]['build_complete_title']) ? $queries[count($queries)-1]['build_complete_title'] : false, 
                            'query_complete' => $query_complete
                        )
                    );
                }
                //хлебные крошки
                $estate_types_breadcrumbs = array(
                                                    'live' => 'Жилая',
                                                    'build' => 'Новостройки',
                                                    'commercial' => 'Коммерческая',
                                                    'country' => 'Загородная',
                                                    'business_centers' => 'Бизнес-центры',
                                                    'cottedzhnye_poselki' => 'Коттеджные поселки'
                );
                $this_page->clearBreadcrumbs();
                $this_page->addBreadcrumbs( $page_url == 'zhiloy_kompleks' ? 'Жилые комплексы' : 'Апартаменты', $page_url, 0, $estate_types_breadcrumbs);
                //метаданные
                $h1 = empty($this_page->page_seo_h1) ? $titles['header'] : $this_page->page_seo_h1;
                $this_page->addBreadcrumbs($item['title'], $item['chpu_title']);
                $new_meta = array(
                    'title'=>$titles['title'],  
                    'keywords'=>$h1, 
                    'description'=>$titles['description']
                );
                $this_page->manageMetadata($new_meta, true);
                
                if (!empty($item['notes'])||!empty($item['nature'])||!empty($item['infrastructure'])||!empty($item['communications'])||!empty($item['land_status'])||!empty($item['start_advert'])||!empty($item['start_sale']))  Response::SetBoolean('general_info',true);
                $module_template = 'item.html';
                //Все карточки имеют платный вид
                Response::SetBoolean('payed_format',true);
  
                //счетчик показов карточки
                $ref = Host::getRefererURL();
                $agent = Host::$user_agent;
                $ip = Host::getUserIp();
                if(!Host::isBot() && !Host::isBsn("estate_complexes_stats_day_clicks",$id) && isset($ref) && $ref!= '' && isset($agent) && $agent!='' && isset($ip) && $ip!='')
                    $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_day_clicks']." SET id_parent = ?, type = ?, ip = ?, browser = ?, ref = ?, server = ?, `module` = ?",
                        $id, 1, $ip, $agent, $ref, print_r($_SERVER, true), 'housing_estate'
                    );
                //владелец карточки           
                if(!empty($auth->id) && in_array($auth->id,array($item['id_user'], $item['id_seller'], $item['id_advert_agency']))) Response::SetBoolean('object_owner',true);

                //формирвоание ссылок из карточки на поиск по району/метро/району ЛО
                if(!empty($item['id_district'])) {
                    $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",'zhiloy_kompleks/?districts='.$item['id_district'],0);
                    if(!empty($page)) Response::SetArray('district_link',$page);
                    
                }
                if(!empty($item['id_area']) && !empty($item['id_region']) && $item['id_region']==47) {
                    $geo = $db->fetch("SELECT id FROM ".$sys_tables['geodata']." WHERE a_level = ? AND id_region = ? AND id_area = ?", 2, 47, $item['id_area']);
                    if(!empty($geo)){
                        $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",'zhiloy_kompleks/?district_areas='.$geo['id'],0);
                        if(!empty($page)) Response::SetArray('district_area_link',$page);
                    }
                    
                }                
                if(!empty($item['id_subway'])) {
                    $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",'zhiloy_kompleks/?subways='.$item['id_subway'],0);
                    if(!empty($page)) Response::SetArray('subway_link',$page);
                    
                }
                
                $comments_data = array('page_url'    =>  '/'.$this_page->real_url.'/',
                                  'id_parent'   =>  $housing_estates_id,
                                  'parent_type' =>  'housing_estates',
                                  'feedback' =>  'true'
                                );
                Response::SetArray('comments_data', $comments_data);  
                                
                //ход строительства
                $progresses = $db->fetchall("SELECT *   
                                             FROM ".$sys_tables['housing_estates_progresses']."
                                             WHERE id_parent = ?
                                             ORDER BY year DESC, month
                                             LIMIT 12"
                                             ,false, $item['id']
                );                       
                Response::SetArray('progresses',$progresses);
                Response::SetArray('months',Config::Get('months_short'));
                
                //предыдущий-следующий объекты по классу
                $prev_next_list_class = $housing_estates->getPrevNext($item['id'], $sys_tables['housing_estates'].".class = ".$item['class']);
                Response::SetArray('prev_next_list_class',$prev_next_list_class);
                //объекты ЖК
                $housing_estate_objects_list = $housing_estates->getObjectsList($id);
                Response::SetArray('housing_estate_objects',$housing_estate_objects_list);
                //объекты ЖК от застройщика / компании для платной карточки
                if( $item['advanced'] == 1 ){
                    if(!empty($item['id_seller'])) {
                        $seller_list = $db->fetch("SELECT COUNT(*) as cnt FROM " . $sys_tables['build'] ." WHERE published = 1 AND id_user = ? AND id_housing_estate = ?", $item['id_seller'], $item['id']);
                        Response::SetBoolean('seller_objects', !empty($seller_list['cnt']) && $seller_list['cnt'] > 0);
                    }
                    if(!empty($item['id_user']) && empty($seller_list)) {
                        $company_list = $db->fetch("SELECT COUNT(*) as cnt FROM " . $sys_tables['build'] ." WHERE published = 1 AND id_user = ? AND id_housing_estate = ?", $item['id_user'], $item['id']);
                        Response::SetBoolean('company_objects', !empty($company_list['cnt']) && $company_list['cnt'] > 0);
                    }
                }
                //остальные ЖК застройщика 
                if($item['advanced'] == 1){
                    $where = (!empty($item['id_seller']) ? $sys_tables['housing_estates'].".id_user = ".$item['id_user'] : $sys_tables['housing_estates'].".id_user = ".$item['id_user'])." AND ".$sys_tables['housing_estates'].".published = 1 AND ".$sys_tables['housing_estates'].".id != ".$item['id'];
                    $full_list_advanced = $housing_estates->Search($where." AND ".$sys_tables['housing_estates'].".advanced = 1");
                    $full_list = $housing_estates->Search($where);
                    Response::SetInteger("agency_housing_estates_total", count($full_list) + 1);
                    $agency_housing_estates = [];
                    shuffle($full_list_advanced);
                    $agency_housing_estates = array_slice($full_list_advanced, 0, 3);
                    Response::SetArray('agency_housing_estates', $agency_housing_estates);
                }
                //информация об агентстве
                $agency = new Common();
            }
            break;                
        ////////////////////////////////////////////////////////////////////////////////////////////////
       // Жилые комплексы  - голосование
       ////////////////////////////////////////////////////////////////////////////////////////////////
        case (!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'vote'):
            if(!empty($ajax_mode)){
                //по chpu определяем ЖК:
                $chpu = $this_page->page_parameters[0];
                $rating = Request::getString('rating',METHOD_POST);
                //читаем значения полей и удаляем все лишнее
                $rating_field = $rating == 10 ? 'A' : $rating;
                $rating_fields = str_repeat($rating_field, 5);
                
                $he_id = $db->fetch("SELECT id FROM ".$sys_tables['housing_estates']." WHERE chpu_title = ?",$chpu)['id'];
                
                //проверяем, голосовали ли уже с данного ip в данном статусе за данный жк, если уже голосовали, выходим
                $voted_already = $db->fetch("SELECT id FROM ".$sys_tables['housing_estates_voting']." WHERE ip = ? AND id_parent = ? AND is_expert = ?",Host::getUserIp(),$he_id,$auth->expert);
                if(!empty($voted_already)){
                    $ajax_result['voted_already'] = true;
                    $ajax_result['ok'] = true;
                    break;
                }
                //если это эксперт, добавляем префикс
                //голосование отключено с 28.12
                $prefix = ($auth->expert == 1)?"expert_":"";
                //$prefix = "";
                //записываем голос
                $db->query("INSERT INTO ".$sys_tables['housing_estates_voting']." 
                            SET id_parent = ?, 
                                rating = ?, 
                                rating_fields = ?, 
                                ip = ?, 
                                browser = ?, 
                                ref = ?, 
                                is_expert = ?,
                                id_user = ?", $he_id, $rating, $rating_fields, Host::getUserIp(), $_SERVER['HTTP_USER_AGENT'], Host::getRefererURL(),(!empty($prefix)?1:2),(!empty($auth->id)?$auth->id:0));
                //обновляем рейтинг ЖК
                $new_rating = $db->fetch("SELECT AVG(rating) AS new_rating FROM ".$sys_tables['housing_estates_voting']." WHERE id_parent = ?",$he_id)['new_rating'];
                $db->query("UPDATE ".$sys_tables['housing_estates']." SET rating = '".$new_rating."' WHERE id = ?",$he_id);
                $ajax_result['new_value'] = number_format($new_rating,2);
                $ajax_result['ok'] = true;
            }
            break;
          
    }
    break;
    //////////////////////////////////////////////////////////////////////////
    // Рейтинг ЖК
    /////////////////////////////////////////////////////////////////////////
    case $page_url == 'service/ratings':
        $action = !empty( $this_page->page_parameters[0] ) ? $this_page->page_parameters[0] : false;
        $GLOBALS['js_set'][] = '/js/yandex.map.js';
        $GLOBALS['css_set'][] = '/css/yandex.map.css';
        $GLOBALS['js_set'][] = '/modules/housing_estates/rating.script.js';
        $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
        $GLOBALS['css_set'][] = '/modules/housing_estates/rating.style.css';
        $this_page->addBreadcrumbs('Сервисы', 'service',0); // перезапись 1-го уровня х.крошек
        $this_page->addBreadcrumbs('Рейтинг жилых комплексов', 'ratings',1, Config::Get('services_breadcrumbs'));
        
        switch(true){
            //////////////////////////////////////////////////////////////////////////
            // Информационный попап
            /////////////////////////////////////////////////////////////////////////
            case !empty($action) && $action == 'popup':
                $module_template = 'rating.popup.html';
                $ajax_result['ok'] = true;
                break;
            //////////////////////////////////////////////////////////////////////////
            // Карточка рейтинга
            /////////////////////////////////////////////////////////////////////////
            case !empty($action):
                Host::RedirectLevelUp();
                break;
            //////////////////////////////////////////////////////////////////////////
            // Главная страница рейтинга
            /////////////////////////////////////////////////////////////////////////
            default:
                $GLOBALS['css_set'][] = '/modules/housing_estates/style.css';
                $list = $db->fetchall("SELECT * FROM ".$sys_tables['housing_estates_districts_old']." ORDER BY title");
                Response::SetArray('list', $list);
                $module_template = 'rating.mainpage.html';
                Response::SetString('h1_page', empty($this_page->page_seo_h1) ? 'Рейтинг жилых комплексов СПб и Ленобласти' : $this_page->page_seo_h1);
                Response::SetBoolean( 'payed_format', true );
                break;
        }
        break;
        default:
            Host::RedirectLevelUp();
            break;
}

?>
