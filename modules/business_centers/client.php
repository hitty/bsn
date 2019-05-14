<?php
require_once('includes/class.paginator.php');
require_once('includes/class.estate.php');
require_once('includes/class.estate.statistics.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// мэппинги модуля

//хлебные крошки по умолчанию
$page_url = $this_page->page_url;
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
require_once('includes/class.business_centers.php');
$estate_type = 'business_centers';
Response::SetString('estate_type',$estate_type); 
Response::SetString('img_folder',Config::Get('img_folders/business_centers'));                
//Бизнес-центры
Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Бизнес-центры' : $this_page->page_seo_h1);
//для ТГБ со всплывашками
$GLOBALS['css_set'][] = '/modules/applications/style.css';
$map_mode = Request::GetString('map',METHOD_POST);
// обработка общих action-ов
switch(true){
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Бизнес-центры
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $page_url == 'business_centers' 
    ||   $page_url == 'business_centers':
 
        switch(true){
           
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Бизнес-центры - Карта
           ////////////////////////////////////////////////////////////////////////////////////////////////
            case $action == 'map':
            if($ajax_mode) {
                $this_page->page_cache_time = Config::$values['blocks_cache_time']['business_centers_map'];        

                // формирование набора условий для поиска
                $parameters = Request::GetParameters(METHOD_GET);
                $clauses = [];
                $clauses['published'] = array('value'=> 1);
                $clauses['lat']['from'] = 0;
                $clauses['lng']['from'] = 0;

                $business_centers = new BusinessCenters();
                $where = $business_centers->makeWhereClause($clauses);
                
                $list = $business_centers->getList(false,false,$where);
                $points = []; $count = 0;
                //отправляем в карту только название, текст и координаты
                    //отправляем в карту только название, текст и координаты
                   $ids = [];
                   foreach($list as $key=>$item){
                        if(!empty($item['lat']) && !empty($item['lng']) && $item['lng']>0 && $item['lat']>0){
                            $points[$index]['lat'] =  $item['lat'];
                            $points[$index]['lng'] =  $item['lng'];
                            $points[$index]['link'] = "/business_centers/".$item['id']."/";
                            $points[$index]['title'] =  'Бизнес-центр '.$item['title'];
                            $points[$index]['icon_url'] = $item['advanced']==1?'/img/map_icons/icon_map_commercial_'.strtolower($item['class']).'_payed.png':'/img/map_icons/icon_map_commercial_'.strtolower($item['class']).'.png';
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
    // блоки (последних и похожих)
    /////////////////////////////////////////////////////////////////////////
    case $action=='block':
        if(!$this_page->first_instance || $ajax_mode) {
            // блок последних предложений
            $module_template = 'list.block.html';
            $count = 3;
            if(!empty($this_page->page_parameters[2]) && Validate::isDigit($this_page->page_parameters[2])) $count = Convert::ToInteger($this_page->page_parameters[2]);
            $clauses = [];
            $business_centers = new BusinessCenters();
            $where = $business_centers->makeWhereClause($clauses);
            $order = $sys_tables['business_centers'].".advanced = 1 DESC, ".$sys_tables['business_centers'].".random_sorting, ".$sys_tables['business_centers'].".id_main_photo > 0 DESC, RAND()";
            $list = $business_centers->getList($count,0,$where,$order);
            $list = Favorites::ToList($list,7);
            //увеличение счетчика показов
            foreach($list as $key=>$item) $ids[] = '('.$item['id'].', 3, "'.Host::getUserIp().'", "'.$db->real_escape_string($_SERVER['HTTP_USER_AGENT']).'", "'.Host::getRefererURL().'")';
            $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_day_shows']." (id_parent, type, ip, browser, ref) VALUES ".implode(",",$ids)."");
            
            $this_page->page_cache_time = Config::$values['blocks_cache_time']['last_offers_block'];
            Response::SetString('view_type', 'block');
            Response::SetString('img_folder',Config::$values['img_folders']['cottages']);
            if(!empty($list)) Response::SetArray('list', $list);
        } else Host::RedirectLevelUp();
        break;                
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // Бизнес-центры  - Поиск по названию
       ////////////////////////////////////////////////////////////////////////////////////////////////
        case !empty($action) && count($this_page->page_parameters)==1 && $action == 'title':
                if($ajax_mode) {
                    $search_string = Request::GetString('search_string', METHOD_POST);
                    $sql = "SELECT id, `title`
                            FROM ".$sys_tables['business_centers']."
                            WHERE title LIKE '%$search_string%'
                            ORDER BY title 
                            LIMIT 10";
                    $list = $db->fetchall($sql);
                    $ajax_result['ok'] = !empty($list);
                    $ajax_result['list'] = $list;
                } else Host::RedirectLevelUp(); 
            break;
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // Бизнес-центры  - Карточка
       ////////////////////////////////////////////////////////////////////////////////////////////////
       case (!empty($action) && count($this_page->page_parameters)==1) || (count($this_page->page_parameters)==2 && $this_page->page_parameters[1]=='print'):
            $print = !empty($this_page->page_parameters[1]);
            if ($print){
                $GLOBALS['css_set'][]='/css/print.css';
                $GLOBALS['js_set'][]='/modules/estate/print.js';
                $this_page->page_template='/templates/print.html';
                Response::SetBoolean('print',true);
            }
            $GLOBALS['css_set'][]='/css/estate_search.css';
            $GLOBALS['js_set'][] = '/js/form.validate.js';
            $GLOBALS['js_set'][] = '/modules/infrastructure/yandex.map.js';
            $GLOBALS['css_set'][] = '/modules/infrastructure/styles.css';
            $GLOBALS['css_set'][] = '/modules/business_centers/style.css';
            $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
            $GLOBALS['css_set'][] = '/css/estate_search.css';
            $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
            $GLOBALS['js_set'][] ="/modules/favorites/favorites.js";
            $GLOBALS['js_set'][] = '/js/phones.click.js';
            $GLOBALS['css_set'][] = '/css/phones.click.css';            
            
            
            $GLOBALS['js_set'][] = '/modules/business_centers/item.js';
            $GLOBALS['js_set'][] = '/modules/business_centers/jquery.offices.js';
            if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]!='print') {Host::Redirect('/business_centers/'); break;}

            $title = Convert::ToString($action);
            $id =  (preg_match('/^[0-9]+$/',$action)?Convert::ToInteger($action):0);
            
            $business_centers = new BusinessCenters();
            $item = $business_centers->getItem($title, $id,true);
            
            if(empty($item)){
                $this_page->http_code = 404;
                break;
            }   else{
                if(!empty($id) && $id>0 && !empty($item['chpu_title'])) Host::Redirect('/business_centers/'.$item['chpu_title'].'/');
            }
            //редирект архивной карточки на аналогичный объект
            if($item['published'] == 2){
                $published_item = $db->fetch("SELECT * FROM ".$sys_tables['business_centers']." WHERE published = 1 AND title = ?", $item['title']);
                if(!empty($published_item)) Host::Redirect('/business_centers/'.$published_item['chpu_title'].'/');
            }
            
            //увеличиваем счетчик просмотров если это не печать карточки
            if (!$print){
                $db->query("UPDATE ".$sys_tables['business_centers']." SET views_count = views_count + 1 WHERE id = ?",$item['id']);
            }
            
            //телефон в БЦ показывается согласно задаче: https://bsn.bitrix24.ru/company/personal/user/12/tasks/task/view/3924/?current_fieldset=SOCSERV
            //редактирование телефонов
            if(!empty($item['agency_phone_1'])) {
                if(strlen($item['agency_phone_1'])<7) $item['agency_phone_1'] = '';
                $agency_phone_1 =  Convert::ToPhone($item['agency_phone_1']);
                if(!empty($agency_phone_1[0])) $item['seller_phone'] = $agency_phone_1[0];
            }
           
            $item = Favorites::ToItem($item,7);
            //приведение примечаний в общий вид
            $item['fulldescr'] = preg_replace('~(</?\\w+)(?:\\s(?:[^<>/]|/[^<>])*)?(/?>)~ui', '$1$2', strip_tags($item['fulldescr'],'<div><p>'));
            Response::SetArray('item',$item);
            
            //фотогалерея
            Response::SetString('img_folder',Config::$values['img_folders']['business_centers']);
            $photos = Photos::getList('business_centers',$item['id']);
            Response::SetArray('photos',$photos);
            $titles = $business_centers->getTitles($item['id']);
            Response::SetArray('titles',$titles);

            //метаданные
            $h1 = empty($this_page->page_seo_h1) ? $titles['header'] : $this_page->page_seo_h1;
            $this_page->addBreadcrumbs($item['title'], $item['chpu_title']);
            $new_meta = array('title'=>$titles['title'].'', 'keywords'=>$h1, 'description'=>$titles['description']);
            $this_page->manageMetadata($new_meta, true);
            Response::SetString('h1', $h1);                
            
            Response::SetBoolean('general_info',true);
            $module_template = 'item.html';
            if($item['advanced']==1 && empty($print)){
                Response::SetBoolean('payed_format',true);
                Response::SetBoolean('no_target',true);
            } 
            //корпуса офисов
            $business_centers_corpuses = $business_centers->getCorpusesList(false, $sys_tables['business_centers_levels'].'.id_parent = '.$item['id'], $sys_tables['business_centers_corps'].'.title ASC' );
            Response::SetArray('business_centers_corpuses', $business_centers_corpuses);
            //этажи офисов
            $business_centers_levels = $business_centers->getLevelsList(false, $sys_tables['business_centers_levels'].'.id_parent = '.$item['id'], $sys_tables['business_centers_corps'].'.title ASC, '.$sys_tables['business_centers_levels'].'.level ASC' );
            foreach($business_centers_levels as $k => $business_centers_level) if(empty($business_centers_level['offices_count'])) unset($business_centers_levels[$k]);
            Response::SetArray('business_centers_levels', $business_centers_levels);
            //счетчик показов карточки
            $ref = Host::getRefererURL();
            $agent = Host::$user_agent;
            $ip = Host::getUserIp();
            if(!Host::isBot() && !Host::isBsn("estate_complexes_stats_day_clicks",$id) && isset($ref) && $ref!= '' && isset($agent) && $agent!='' && isset($ip) && $ip!='')
                $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_day_clicks']." SET id_parent = ?, type = ?, ip = ?, browser = ?, ref = ?, server = ?, `module` = ?",
                    $id, 3, $ip, $agent, $ref, print_r($_SERVER, true), 'housing_estate'
                );
            //владелец карточки           
            if(!empty($auth->id) && in_array($auth->id,array($item['id_user'], $item['id_seller'], $item['id_advert_agency']))) Response::SetBoolean('object_owner',true);

            //формирование ссылок из карточки на поиск по району/метро/району ЛО
            if(!empty($item['id_district'])) {
                $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",'business_centers/?districts='.$item['id_district'],0);
                if(!empty($page)) Response::SetArray('district_link',$page);
                
            }
            if(!empty($item['id_area']) && !empty($item['id_region']) && $item['id_region']==47) {
                $geo = $db->fetch("SELECT id FROM ".$sys_tables['geodata']." WHERE a_level = ? AND id_region = ? AND id_area = ?", 2, 47, $item['id_area']);
                if(!empty($geo)){
                    $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",'business_centers/?district_areas='.$geo['id'],0);
                    if(!empty($page)) Response::SetArray('district_area_link',$page);
                }
                
            }                
            if(!empty($item['id_subway'])) {
                $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",'business_centers/?subways='.$item['id_subway'],0);
                if(!empty($page)) Response::SetArray('subway_link',$page);
                
            }            

            //Все карточки имеют платный вид
            Response::SetBoolean('payed_format',true);

            //предыдущий-следующий объекты
            $previous = $db->fetch("SELECT chpu_title FROM ".$sys_tables['business_centers']." WHERE published = ? AND id < ? ", 1, $item['id']);
            $next = $db->fetch("SELECT chpu_title  FROM ".$sys_tables['business_centers']." WHERE published = ? AND id > ? ", 1, $item['id']);
            Response::SetArray('previous',(empty($previous)?[]:$previous));
            Response::SetArray('next',(empty($next)?[]:$next));
            //продажа-аренда
            $business_center_objects_rent = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['commercial']." WHERE published = ? AND id_business_center=? AND rent = ? ",1,$item['id'],1);
            $business_center_objects_sell = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['commercial']." WHERE published = ? AND id_business_center=? AND rent = ? ",1,$item['id'],2);
            Response::SetArray('business_center_objects_rent',$business_center_objects_rent);
            Response::SetArray('business_center_objects_sell',$business_center_objects_sell);
            Response::SetInteger('business_center_objects',$business_center_objects_rent['cnt'] + $business_center_objects_sell['cnt']);
            
            
            //поиск БЦ в радиусе 2 км
            $R = 6371;  // earth's radius, km 
            $max_distance = 1;
            // first-cut bounding box (in degrees) 
            $max_latitude = $item['lat'] + rad2deg($max_distance/$R); 
            $min_latitude = $item['lat'] - rad2deg($max_distance/$R); 
            // compensate for degrees longitude getting smaller with increasing latitude 
            $max_longitude = $item['lng'] + rad2deg($max_distance/$R/cos(deg2rad($item['lat']))); 
            $min_longitude = $item['lng'] - rad2deg($max_distance/$R/cos(deg2rad($item['lat'])));  
            $nearest_business_centers = $business_centers->getList(5,0, $sys_tables['business_centers'].'.published = 1 AND '.$sys_tables['business_centers'].'.lat <= '.$max_latitude.' AND '.$sys_tables['business_centers'].'.lat >= '.$min_latitude.' AND '.$sys_tables['business_centers'].'.lng <= '.$max_longitude.' AND '.$sys_tables['business_centers'].'.lng >= '.$min_longitude." AND ".$sys_tables['business_centers'].".id != ".$item['id']." AND ".$sys_tables['business_centers'].".class != 'no'");
            Response::SetArray('nearest_business_centers', $nearest_business_centers);
            //поиск похожих БЦ того же класса
            $similar_business_centers = $business_centers->getList(5,0, $sys_tables['business_centers'].".published = 1 AND ".$sys_tables['business_centers'].".class = '".$item['class']."' AND ".$sys_tables['business_centers'].".id != ".$item['id']);
            Response::SetArray('similar_business_centers', $similar_business_centers);
                        
            Response::SetString('estate_type','business_centers');   
            //форма поиска объектов
            $objects = $db->fetchall("
                SELECT 
                tg.title, 
                tg.title_short, 
                tg.id,
                ".$sys_tables['commercial'].".rent, 
                COUNT(".$sys_tables['commercial'].".id) 
                FROM ".$sys_tables['commercial']." 
                RIGHT JOIN ".$sys_tables['type_objects_commercial']." tc ON tc.id = ".$sys_tables['commercial'].".id_type_object 
                RIGHT JOIN ".$sys_tables['object_type_groups']." tg ON tg.id = tc.id_group
                WHERE 
                ".$sys_tables['commercial'].".id_business_center = ? AND 
                ".$sys_tables['commercial'].".published = 1 
                GROUP BY 
                ".$sys_tables['commercial'].".rent, 
                tg.id 
                ORDER BY ".$sys_tables['commercial'].".rent            
            ", false, $item['id']);       
            Response::SetArray('objects', $objects);
             
            break;        
       
       ////////////////////////////////////////////////////////////////////////////////////////////////
       // Бизнес-центры - Заглавная страница
       ////////////////////////////////////////////////////////////////////////////////////////////////
        default:
            if(!empty($this_page->page_parameters[1])) {Host::RedirectLevelUp(); break;}
            //вид отображения списка
            $view_type = Request::GetInteger('view_type', METHOD_GET);
            if(empty($view_type)) $view_type = Cookie::GetString('View_type');
            if(empty($view_type)){
                $view_type = 'list';
                Cookie::SetCookie('View_type', $view_type, 60*60*24*30, '/');
            }
            Response::SetString('view_type', $view_type);         
            // формирование набора условий для поиска
            $estate_search = new EstateSearch();
            list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams();
            // подключение поисковой формы 
            require_once("includes/form.estate.php");
            Response::SetBoolean('search_form', true);  
            if(empty($ajax_mode)){
                $GLOBALS['js_set'][] = '/js/form.validate.js';
                $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
                $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
                $GLOBALS['css_set'][] = '/css/estate_search.css';
                $GLOBALS['css_set'][] = '/css/autocomplete.css';

                $GLOBALS['js_set'][]='/modules/credit_calculator/mortgage-application.js';
                $GLOBALS['js_set'][]='/modules/credit_calculator/block.js';
                $GLOBALS['css_set'][]='/modules/credit_calculator/block.css';
                
                $GLOBALS['css_set'][] = '/modules/business_centers/style.css';
                $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
                $GLOBALS['js_set'][] ="/modules/favorites/favorites.js";
                
                //форма поиска
                Response::SetBoolean('search_form', true);
            }                
            
            $business_centers = new BusinessCenters();
            $where = $business_centers->makeWhereClause($clauses);
                
            if(empty($map_mode)){

                // кол-во элементов в списке
                $count = Request::GetInteger('count', METHOD_GET);
                if(!empty($count))
                    $get_parameters['count'] = $count;
                else
                    $count = Cookie::GetInteger('View_count_estate');
                if(empty($count)) {
                    $count = 20;
                    Cookie::SetCookie('View_count_estate', Convert::ToString($count), 60*60*24*30, '/');
                }

                // сортировка
                $sortby = Request::GetInteger('sortby', METHOD_GET);
                if(empty($sortby)) $sortby = 1;
                else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
                $orderby = $sys_tables['business_centers'].".advanced = 1 DESC, ".$sys_tables['business_centers'].".id_main_photo > 0 DESC, ";
                switch($sortby){
                    case 4: 
                        // по метро по убыванию
                        $orderby .= $sys_tables['business_centers'].".id_subway > 0 DESC, subway DESC"; 
                        break;
                    case 3: 
                        // по метро по возрастанию
                        $orderby .= $sys_tables['business_centers'].".id_subway > 0 DESC, subway ASC"; 
                        break;
                    case 2: 
                        // по району по возрастанию
                        $orderby .= $sys_tables['business_centers'].".id_region DESC, ".$sys_tables['business_centers'].".id_district > 0 DESC, district DESC, district_area DESC"; 
                        break;
                    case 1: 
                    default: 
                        // по району по убыванию
                        $orderby .= $sys_tables['business_centers'].".id_region DESC, ".$sys_tables['business_centers'].".id_district > 0 DESC, district ASC, district_area ASC"; 
                        break;
                }
                
                
                Response::SetString('sorting_url', '/'.$this_page->requested_path.'/?sortby=');
                Response::SetInteger('sortby', $sortby);
                
                // страница списка
                $page = Request::GetInteger('page', METHOD_GET);
                if ((isset($page))&&($page==0)){
                    //чтобы не потерялись фильтры, надо включить их в redirect
                    $parameters=Request::GetParameters(METHOD_GET);
                    //здесь будем накапливать строку с get-параметрами
                    $url=[];
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
                if(!empty($reg_where)){
                    $where .= " AND (".implode(" OR ", $reg_where).")";
                }
                $paginator = new Paginator($sys_tables['business_centers'], $count, $where);
                
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
                    //формирование url для пагинатора
                    $paginator->link_prefix = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page=';
                    if($paginator->pages_count>1){
                        Response::SetArray('paginator', $paginator->Get($page));
                    }

                }
                $list = $business_centers->getList($count,$count*($page-1),$where, $orderby);
                $list = Favorites::ToList($list,7);

                //увеличение счетчика показов
                if(!empty($list)){
                    $ids = [];
                    foreach($list as $k=>$item) $ids[] = '('.$item['id'].', 3, "'.$db->real_escape_string(Host::getUserIp()).'", "'.$db->real_escape_string($_SERVER['HTTP_USER_AGENT']).'", "'.$db->real_escape_string(Host::getRefererURL()).'")';
                    $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_day_shows']." (id_parent, type, ip, browser, ref) VALUES ".implode(",",$ids)."");
                }
                Response::SetArray('list', $list);
                Response::SetInteger('full_count', $paginator->items_count);                
                Response::SetString('requested_url', $this_page->requested_url);  
                Response::SetString('img_folder',Config::Get('img_folders/business_centers'));                
                if(!empty($parameters['search_form']) ) {
                    Response::SetBoolean('only_objects', true);
                    $ajax_result['ok'] = true;
                    $module_template = 'list.block.html';
                }
                else $module_template = 'list.html';
                if($page<2) Response::SetString('url',$this_page->requested_url);
                
                list($subscription_title, $description) = EstateSubscriptions::getTitle(false, $parameters, true, false, true);
                $h1 = !empty($this_page->page_seo_h1) && ( ( !empty($ajax_mode) && $this_page->requested_path != $this_page->page_pretty_url ) || ( empty($ajax_mode) && $this_page->requested_url == $this_page->requested_path ) ) ? $this_page->page_seo_h1 : $subscription_title;
                Response::SetString('h1', $h1);

                //сео параметры
                if( ( ( !empty($ajax_mode) && $this_page->requested_path != $this_page->page_pretty_url ) || ( empty($ajax_mode) && $this_page->requested_url == $this_page->requested_path ) ) ) {
                    $h1 = !empty($this_page->page_seo_h1) ? $this_page->page_seo_h1 : $subscription_title;
                    $page_title = !empty($this_page->page_seo_title) ? $this_page->page_seo_title : $h1;
                    $pretty_url = $this_page->page_pretty_url;
                } else {
                    $page_title = $h1 = $subscription_title;
                }
                Response::SetString('h1', $h1);
                $new_meta = array(
                        'h1'=>$h1, 
                        'title' => ( !empty($paginator->items_count) ? Convert::ToNumber($paginator->items_count) . makeSuffix($paginator->items_count, ' объявлени', array('е', 'я', 'й')) . ' - ' : '' ) .  $h1,
                        'description' => $description . ' ☆ Уникальные предложения, которых не найти на других сайтах. ☆ Мы постоянно отслеживаем актуальность и достоверность объявлений'
                    );
                    $this_page->manageMetadata($new_meta, true);              
                
            } else {
                $where .= ' AND ' . $sys_tables['business_centers']. ".lat > 0 AND " . $sys_tables['business_centers']. ".lng > 0";
                $map_list = $business_centers->getList(100, 0, $where);
                $index = 0;
                //отправляем в карту
                $points = [];
                foreach($map_list as $key=>$item){
                    if(!empty($item['lat']) && !empty($item['lng']) && $item['lng']>0 && $item['lat']>0){
                        $points[$key]['lat'] =  $item['lat'];
                        $points[$key]['lng'] =  $item['lng'];
                        $points[$key]['title'] =  'БЦ «' . $item['title'] . '»';
                        //шаблон
                        Response::SetArray('item', $item);
                        $estate_template = new Template('list.block.item.html',$this_page->module_path);
                        $points[$key]['html'] = $estate_template->Processing();
                        $points[$key]['id'] = $item['id'];
                    }
                }
                //группировка маркеров
                $ajax_result['total'] = count($map_list);
                $ajax_result['ok'] = true;
                if(!empty($points)) $ajax_result['points'] = $points;
            }
    }
    break;
    case $this_page->page_alias == 'members/office/business_centers':
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Управление офисами БЦ
   ////////////////////////////////////////////////////////////////////////////////////////////////
        if( $auth->agency_business_center != 1) Host::Redirect('/members/office/');
        //правая колонка неактивная
        Response::SetArray('right_column_inactive', true);
        if(empty($auth->id_agency) || $auth->agency_admin!=1){
                $this_page->http_code = 403;
                break;
        }
        $GLOBALS['css_set'][] = '/modules/business_centers/style.members.css';
        $GLOBALS['css_set'][] = '/css/style-cabinet.css';
        $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][]='/js/form.validate.js';
        Response::SetString('page_type','business_centers');
        Response::SetBoolean('left_menu_office', true);
        
        $action = !empty($this_page->page_parameters[0]) ? $this_page->page_parameters[0] : false; 
        switch(true)    {
            ////////////////////////////////////////////////////////////////////////////////////////////////
           // Редиктирование офисов
           ////////////////////////////////////////////////////////////////////////////////////////////////
            case $action == 'edit':
                $id = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false; 
                $GLOBALS['js_set'][] = '/js/form.validate.js';
                $GLOBALS['css_set'][] = '/modules/business_centers/style.css';
                $GLOBALS['css_set'][] = '/modules/business_centers/style.members.css';
                $GLOBALS['js_set'][] = '/modules/business_centers/item.js';
                $GLOBALS['js_set'][] = '/modules/business_centers/jquery.offices.js';
                $GLOBALS['js_set'][] = '/js/datetimepicker/jquery.datetimepicker.js';
                $GLOBALS['css_set'][] = '/js/datetimepicker/jquery.datetimepicker.css';
                

                $business_centers = new BusinessCenters();
                $item = $business_centers->getItem(false, $id,true);
                
                if(empty($item)){
                    $this_page->http_code = 404;
                    break;
                }  
                //редирект архивной карточки на аналогичный объект
                if($item['published'] == 2){
                    $published_item = $db->fetch("SELECT * FROM ".$sys_tables['business_centers']." WHERE published = 1 AND title = ?", $item['title']);
                    if(!empty($published_item)) Host::Redirect('/business_centers/'.$published_item['chpu_title'].'/');
                }
                
                $item['fulldescr'] = preg_replace('~(</?\\w+)(?:\\s(?:[^<>/]|/[^<>])*)?(/?>)~ui', '$1$2', strip_tags($item['fulldescr'],'<div><p>'));
                Response::SetArray('item',$item);
                
                //фотогалерея
                Response::SetString('img_folder',Config::$values['img_folders']['business_centers']);
                $photos = Photos::getList('business_centers',$item['id']);
                Response::SetArray('photos',$photos);
                $titles = $business_centers->getTitles($item['id']);
                Response::SetArray('titles',$titles);

                //метаданные
                $h1 = empty($this_page->page_seo_h1) ? $titles['header'] : $this_page->page_seo_h1;
                $this_page->addBreadcrumbs($item['title'], $item['chpu_title']);
                $new_meta = array(
                    'h1'=>$h1, 
                    'title' => ( !empty($paginator->items_count) ? Convert::ToNumber($paginator->items_count) . makeSuffix($paginator->items_count, ' объявлени', array('е', 'я', 'й')) . ' - ' : '' ) .  $h1,
                    'description' => ( !empty($description) ? $description : '' ) . ' ☆ Уникальные предложения, которых не найти на других сайтах. ☆ Мы постоянно отслеживаем актуальность и достоверность объявлений'
                );
                $this_page->manageMetadata($new_meta, true);
                Response::SetString('h1', $h1);                
                
                Response::SetBoolean('general_info',true);
                $module_template = 'item.html';
                if($item['advanced']==1 && empty($print))  Response::SetBoolean('payed_format',true);
                //корпуса офисов
                $business_centers_corpuses = $business_centers->getCorpusesList(false, $sys_tables['business_centers_levels'].'.id_parent = '.$item['id'], $sys_tables['business_centers_corps'].'.title ASC' );
                Response::SetArray('business_centers_corpuses', $business_centers_corpuses);
                //этажи офисов
                $business_centers_levels = $business_centers->getLevelsList(false, $sys_tables['business_centers_levels'].'.id_parent = '.$item['id'], $sys_tables['business_centers_corps'].'.title ASC, '.$sys_tables['business_centers_levels'].'.level ASC' );
                foreach($business_centers_levels as $k => $business_centers_level) if(empty($business_centers_level['offices_count'])) unset($business_centers_levels[$k]);
                Response::SetArray('business_centers_levels', $business_centers_levels);
                //список арендаторов
                $renters = $db->fetchall("SELECT * FROM ".$sys_tables['business_centers_offices_renters']." WHERE id_user = ? ORDER BY title", false, $auth->id);
                Response::SetArray('renters', $renters);
                $module_template = 'members.edit.html';
                break;
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Управление арендаторами офисов БЦ
           ////////////////////////////////////////////////////////////////////////////////////////////////
                case $action == 'renters':
                    $GLOBALS['js_set'][] = '/modules/business_centers/renters.item.js';
                    $GLOBALS['css_set'][] = '/modules/business_centers/renters.style.css';
                    $action = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false; 
                    Response::SetString('page_type','business_centers_renters');
                    switch(true)    {
                        case $action == 'edit':
                        case $action == 'add':
                            $id = Request::GetInteger('id', METHOD_POST);
                            $title = Request::GetString('title', METHOD_POST);
                            $name = Request::GetString('name', METHOD_POST);
                            $phone = Request::GetString('phone', METHOD_POST);
                            $sortby = Request::GetString('sortby', METHOD_POST);
                            if( ( $action == 'edit' && empty($id) ) || empty($title)) return false;
                                if($action == 'add') $db->query("INSERT INTO ".$sys_tables['business_centers_offices_renters']." SET title = ?, name = ?, phone = ?, id_user = ?", $title, $name, $phone, $auth->id);
                                else $db->query("UPDATE ".$sys_tables['business_centers_offices_renters']." SET title = ?, name = ?, phone = ?, id_user = ? WHERE id = ?", $title, $name, $phone, $auth->id, $id);
                            break;
                        
                        case $action == 'del':
                            $id = Request::GetInteger('id', METHOD_POST);
                            if( empty($id)) return false;
                            $res = $db->query("DELETE FROM ".$sys_tables['business_centers_offices_renters']."  WHERE id = ? AND id_user = ?", $id, $auth->id);
                            if($res) $db->query("UPDATE ".$sys_tables['business_centers_offices']."  SET id_renter = 0, status = 2, date_rent_start = '0000-00-00', date_rent_end = '0000-00-00' WHERE id_renter = ?", $id);
                            break;
                        
                        case $action == 'list':
                            $sortby = Request::GetInteger('sortby', METHOD_POST);
                            if(empty($sortby)) $sortby = 1;
                            switch($sortby){
                                case 6: 
                                    $orderby = "sum_square DESC"; 
                                    break;
                                case 5: 
                                    $orderby = "sum_square ASC"; 
                                    break;
                                case 4: 
                                    $orderby = $sys_tables['business_centers_offices_renters'].".name DESC"; 
                                    break;
                                case 3: 
                                    $orderby = $sys_tables['business_centers_offices_renters'].".name ASC"; 
                                    break;
                                case 2: 
                                    $orderby = $sys_tables['business_centers_offices_renters'].".title DESC"; 
                                    break;
                                case 1: 
                                default: 
                                    $orderby = $sys_tables['business_centers_offices_renters'].".title ASC"; 
                                    break;
                            }

                            $list = $db->fetchall("SELECT 
                                                        ".$sys_tables['business_centers_offices_renters'].".*,
                                                        a.sum_square,
                                                        a.cnt
                                                    FROM ".$sys_tables['business_centers_offices_renters']." 
                                                    LEFT JOIN (
                                                        SELECT SUM(square) as sum_square, COUNT(*) as cnt, id_renter FROM ".$sys_tables['business_centers_offices']." GROUP BY id_renter
                                                    ) a ON a.id_renter = ".$sys_tables['business_centers_offices_renters'].".id
                                                    WHERE ".$sys_tables['business_centers_offices_renters'].".id_user = ?
                                                    ORDER BY ".$orderby, 
                                                    false, $auth->id
                            );
                            Response::SetArray('list', $list);
                            $module_template = 'renters.members.block.list.html';
                            $ajax_result['lq'] = $db->last_query;
                            $ajax_result['ok'] = true;
                            break;
                        case empty($action):
                            $module_template = 'renters.members.list.html';
                            break;
                    }
                    break;
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Главная страница
           ////////////////////////////////////////////////////////////////////////////////////////////////
            case empty($action):
                $business_centers = new BusinessCenters();
                $list = $business_centers->getList(false, false, $sys_tables['business_centers'].".id_user = ".$auth->id);
                Response::SetArray('list', $list);

                $module_template = 'members.mainpage.html';
                break;
            default:
                $this_page->http_code = 404;
                break;
        }
        break;
        default:
            Host::RedirectLevelUp();
            break;        
}

?>