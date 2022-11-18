<?php
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.paginator.php');
require_once('includes/class.estate.php');
require_once('includes/class.estate.statistics.php');

if (!in_array('/modules/estate/list_options.js',$GLOBALS['js_set']))
    $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
// мэппинги модуля

$page_url = $this_page->page_url;

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$estate_type = 'cottage';
Response::SetString('estate_type',$estate_type); 
//для ТГБ со всплывашками
$GLOBALS['css_set'][] = '/modules/applications/style.css';
$map_mode = Request::GetString('map',METHOD_POST);
// обработка общих action-ов
switch(true){
   
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Коттеджные поселки
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $page_url == 'cottedzhnye_poselki' 
    ||  $page_url == 'cottedzhnye_poselki' 
    ||   $page_url == 'country_complex':
 
        require_once('includes/class.cottages.php');
        Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Коттеджные поселки' : $this_page->page_seo_h1);
        Response::SetString('img_folder',Config::Get('img_folders/cottages'));                
        switch(true){
           
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Коттеджные поселки - Карта
           ////////////////////////////////////////////////////////////////////////////////////////////////
            case $action == 'map':
            if($ajax_mode) {
                $this_page->page_cache_time = Config::$values['blocks_cache_time']['cottages_map'];        

                // формирование набора условий для поиска
                $estate_search = new EstateSearch();
                list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams();
                // подключение поисковой формы 
                require_once("includes/form.estate.php");
            
                $cottages = new Cottages();
                $where = $cottages->makeWhereClause($clauses);
                $list = $cottages->getList(false,false,$where);
                $points = []; $count = 0;
                //отправляем в карту только название, текст и координаты
                foreach($list as $key=>$item){
                    if(!empty($item['lat']) && !empty($item['lng']) && $item['lng']>0 && $item['lat']>0){
                        $points[$count]['lat'] =  $item['lat'];
                        $points[$count]['lng'] =  $item['lng'];
                        $points[$count]['link'] = "/cottedzhnye_poselki/".$item['id']."/";
                        $points[$count]['title'] = 'Коттеджный поселок '.$item['title'];
                        $points[$count]['advanced'] = $item['advanced'];
                        
                        //тип маркера
                        $points[$index]['icon_url'] = $item['advanced']==1?'/img/map_icons/icon_map_cottage_payed.png':'/img/map_icons/icon_map.png';
                        ++$count;
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
                    $clauses['published'] = array('value'=> 1);
                
                    $cottages = new Cottages();
                    $where = $cottages->makeWhereClause($clauses);
                    $order = $sys_tables['cottages'].".advanced = 1 DESC, ".$sys_tables['cottages'].".random_sorting, ".$sys_tables['cottages'].".id_main_photo > 0 DESC, RAND()";
                    $list = $cottages->getList($count,0,$where, $order);
                    $list = Favorites::ToList($list,6);
                    $ids = [];
                    $clear_ids = [];
                    foreach($list as $k=>$item){
                        $types = [];
                        //$ids[] = '('.$item['id'].', 2)';
                        $ids[] = '('.$item['id'].', 2, "'.Host::getUserIp().'", "'.$db->real_escape_string($_SERVER['HTTP_USER_AGENT']).'", "'.Host::getRefererURL().'")';
                        $clear_ids[] = $item['id'];
                        if($item['u_count']>0) $types[] = 'участки';
                        if($item['c_count']>0) $types[] = 'коттеджи';
                        if($item['t_count']>0) $types[] = 'таунхаусы';
                        if($item['k_count']>0) $types[] = 'квартиры';
                        $list[$k]['types'] = implode(', ',$types);
                    }
                    $db->querys("INSERT INTO ".$sys_tables['estate_complexes_stats_day_shows']." (id_parent, type, ip, browser, ref) VALUES ".implode(",",$ids)."");
                    $db->querys("UPDATE ".$sys_tables['cottages']." SET search_count = search_count + 1 WHERE id IN (".implode(',',$clear_ids).")");
                    Response::SetString('view_type', 'block');
                    if(!empty($list)) Response::SetArray('list', $list);
                } else Host::RedirectLevelUp();
                break;    
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Коттеджные поселки  - Поиск по названию
           ////////////////////////////////////////////////////////////////////////////////////////////////
            case !empty($action) && count($this_page->page_parameters)==1 && $action == 'title':
                    if($ajax_mode) {
                        $search_string = Request::GetString('search_string', METHOD_POST);
                        $sql = "SELECT id, `title`
                                FROM ".$sys_tables['cottages']."
                                WHERE title LIKE '%$search_string%' AND id_stady = 2
                                ORDER BY title 
                                LIMIT 10";
                        $list = $db->fetchall($sql);
                        $ajax_result['ok'] = !empty($list);
                        $ajax_result['list'] = $list;
                    } else Host::RedirectLevelUp(); 
                break;
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Коттеджные поселки  - Поиск по девелоперу
           ////////////////////////////////////////////////////////////////////////////////////////////////
            case !empty($action) && count($this_page->page_parameters)==1 && $action == 'developer_title':
                    if($ajax_mode) {
                        $search_string = Request::GetString('search_string', METHOD_POST);
                        $search_string = addslashes($search_string);
                        $sql = "SELECT 
                                    ".$sys_tables['users'].".id, 
                                    ".$sys_tables['agencies'].".`title`
                                FROM ".$sys_tables['cottages']."
                                RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['cottages'].".id_user 
                                RIGHT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                WHERE 
                                    ".$sys_tables['users'].".agency_admin = 1 AND 
                                    ".$sys_tables['agencies'].".title LIKE '%".$search_string."%' AND 
                                    ".$sys_tables['cottages'].".published = 1
                                GROUP BY ".$sys_tables['users'].".id
                                ORDER BY ".$sys_tables['agencies'].".title 
                                LIMIT 10";
                        $list = $db->fetchall($sql);
                        $ajax_result['ok'] = !empty($list);
                        $ajax_result['list'] = $list;
                    } else Host::RedirectLevelUp();
                break;                
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Коттеджные поселки  - Карточка
           ////////////////////////////////////////////////////////////////////////////////////////////////
           case (!empty($action) && count($this_page->page_parameters)==1) || (count($this_page->page_parameters)==2 && $this_page->page_parameters[1]=='print'):
           $print=!empty($this_page->page_parameters[1]);
                if ($print){
                    $GLOBALS['css_set'][]='/css/print.css';
                    $GLOBALS['js_set'][]='/modules/estate/print.js';
                    $this_page->page_template='/templates/print.html';
                    Response::SetBoolean('print',true);
                }
                $GLOBALS['css_set'][] = '/modules/cottages/style.css';
                $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
                $GLOBALS['css_set'][] = '/css/estate_search.css';
                $GLOBALS['css_set'][] = '/modules/housing_estates/style.css';
                
                $GLOBALS['js_set'][] = "/modules/favorites/favorites.js";
                $GLOBALS['js_set'][] = '/js/phones.click.js';
                $GLOBALS['css_set'][] = '/css/phones.click.css';
                $GLOBALS['js_set'][] = '/modules/infrastructure/yandex.map.js';
                $GLOBALS['css_set'][] = '/modules/infrastructure/styles.css';
                $GLOBALS['js_set'][] = '/js/form.validate.js';
                $GLOBALS['js_set'][] = '/modules/cottages/item.js';
                
                if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]!='print') {Host::Redirect('/cottedzhnye_poselki/'); break;}
                
                $id = $title = false;
                $id =  Convert::ToInteger($action);
                $title = Convert::ToString($action);
                
                $cottages = new Cottages();
                $item = $cottages->getItem($id, $title,true);
                if(empty($item) || $item['id_stady'] != 2){
                    $this_page->http_code = 404;
                    break;
                }   else{
                    if($id>0 && !empty($item['chpu_title'])) Host::Redirect('/cottedzhnye_poselki/'.$item['chpu_title'].'/');
                }
                //редирект архивной карточки на аналогичный объект
                if($item['published'] == 2){
                    $published_item = $db->fetch("SELECT * FROM ".$sys_tables['cottages']." WHERE published = 1 AND title = ?", $item['title']);
                    if(!empty($published_item)) Host::Redirect('/cottedzhnye_poselki/'.$published_item['chpu_title'].'/');
                }
                
                //увеличиваем счетчик просмотров если это не печать карточки
                if (!$print){
                    $db->querys("UPDATE ".$sys_tables['cottages']." SET views_count = views_count + 1 WHERE id = ?",$item['id']);
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
                Response::SetInteger('id',$id);

                //убираем пустые значения
                if (!empty($item['start_advert']) && $item['start_advert']=='0000-00-00') unset($item['start_advert']);
                if (!empty($item['start_sale']) && $item['start_sale']=='0000-00-00') unset($item['start_sale']);
                
                //приведение примечаний в общий вид
                $item['notes'] = preg_replace('~(</?\\w+)(?:\\s(?:[^<>/]|/[^<>])*)?(/?>)~ui', '$1$2', strip_tags($item['notes'],'<div><p>'));
                
                $item = Favorites::ToItem($item,6);
                Response::SetArray('item',$item);
                //фотогалерея
                $photos = Photos::getList('cottages',$item['id']);
                Response::SetArray('photos',$photos);
                if(!empty($item['videos_count'])){
                    $videos = $db->fetchall("SELECT * FROM ".$sys_tables['video_konkurs']." WHERE id_estate_complex = ? AND external_link!='' AND status = 1 AND complex_type = 2", false, $item['id']);
                    Response::SetArray('videos', $videos);
                }
                
                $titles = $cottages->getTitles($item['id']);
                Response::SetArray('titles',$titles);                
                //метаданные
                $h1 = empty($this_page->page_seo_h1) ? $titles['header'] : $this_page->page_seo_h1;
                $this_page->addBreadcrumbs($item['title'], $item['chpu_title']);
                $new_meta = array('title'=>$titles['seo_title'],
                                  'description'=>$titles['seo_description'],
                                  'keywords'=>$h1);
                $this_page->manageMetadata($new_meta, true);
                Response::SetString('h1', $h1);                
                
                if (!empty($item['notes'])||!empty($item['nature'])||!empty($item['infrastructure'])||!empty($item['communications'])||!empty($item['land_status'])||!empty($item['start_advert'])||!empty($item['start_sale']))  Response::SetBoolean('general_info',true);
                $module_template = 'item.html';
                if($item['advanced']==1 && empty($print))  Response::SetBoolean('payed_format',true);
                //счетчик показов карточки
                $ref = Host::getRefererURL();
                $agent = Host::$user_agent;
                $ip = Host::getUserIp();
                if(!Host::isBot() && !Host::isBsn("estate_complexes_stats_day_clicks",$id) && isset($ref) && $ref!= '' && isset($agent) && $agent!='' && isset($ip) && $ip!='')
                    $db->querys("INSERT INTO ".$sys_tables['estate_complexes_stats_day_clicks']." SET id_parent = ?, type = ?, ip = ?, browser = ?, ref = ?, server = ?, `module` = ?",
                        $id, 2, $ip, $agent, $ref, print_r($_SERVER, true), 'housing_estate'
                    );
                //владелец карточки           
                if(!empty($auth->id) && in_array($auth->id,array($item['id_user'], $item['id_seller']))) Response::SetBoolean('object_owner',true);
                
                //формирвоание ссылок из карточки на поиск по району/метро/району ЛО
                if(!empty($item['id_district_area'])) {
                    $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",'cottedzhnye_poselki/?districts='.$item['id_district_area'],0);
                    if(!empty($page)) Response::SetArray('district_link',$page);
                    
                }         

                //предыдущий-следующий объекты
                $previous = $db->fetch("SELECT chpu_title FROM ".$sys_tables['cottages']." WHERE published = ? AND id < ? ", 1, $id);
                $next = $db->fetch("SELECT chpu_title  FROM ".$sys_tables['cottages']." WHERE published = ? AND id > ? ", 1, $id);
                Response::SetArray('previous',$previous);
                Response::SetArray('next',$next);
                //объекты ЖК
                $cottage_objects = $cottages->getObjectsList($id);
                $cottage_objects_count = 0;
                foreach($cottage_objects as $k=>$cottage_objects_item) $cottage_objects_count += $cottage_objects_item['cnt'];
                Response::SetInteger( 'cottage_objects_count', $cottage_objects_count );
                Response::SetArray( 'cottage_objects', $cottage_objects ?? [] );

                Response::SetString('estate_type','cottedzhnye_poselki');                
                
                //Все карточки имеют платный вид
                Response::SetBoolean('payed_format',true);

                //определение координат
                if(empty($item['lat']) || empty($item['lng']) || $item['lat'] == 0 || $item['lng'] == 0){
                    $address = (!empty($info['district'])?'Санкт-Петербург ': (!empty($info['district_area']) ? 'Ленинградская область, '.$info['district_area'].' район' : 'Санкт-Петербург, ').(!empty($item['address'])?$item['address']:''));
                    $geo = curlThis("http://geocode-maps.yandex.ru/1.x/?format=json&kind=street&geocode=".$address);
                    $geo = json_decode($geo);
                    if(!empty($geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos)){
                        $point = explode(" ",$geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
                        $item['lng'] = $point[0];
                        $item['lat'] = $point[1];
                        $db->querys("UPDATE ".$sys_tables['cottages']." SET lat=?, lng=? WHERE id=?",$item['lat'], $item['lng'],$item['id']);
                        Response::SetArray('item', $item);
                    }
                }
                break;                              
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // Коттеджные поселки - Заглавная страница
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

                $search_type = Cookie::GetString('Search_type');
                if(empty($search_type)) $search_type = 'by-title';
                Cookie::SetCookie('Search_type', $search_type, 60*60*24*30, '/');
                Response::SetString('search_type',$search_type);

                $estate_search = new EstateSearch();
                list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams();
                // подключение поисковой формы 
                require_once("includes/form.estate.php");
                Response::SetBoolean('search_form', true);  

                if(empty($ajax_mode)){
                    // формирование набора условий для поиска
                    $GLOBALS['js_set'][] = '/js/form.validate.js';
                    $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
                    $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
                    $GLOBALS['css_set'][] = '/css/estate_search.css';
                    $GLOBALS['css_set'][] = '/css/autocomplete.css';
                    
                    $GLOBALS['js_set'][]='/modules/credit_calculator/mortgage-application.js';
                    $GLOBALS['js_set'][]='/modules/credit_calculator/block.js';
                    $GLOBALS['css_set'][]='/modules/credit_calculator/block.css';
                    
                    //карта коттеджных поселков
                    $GLOBALS['js_set'][]  = '/modules/estate/list_options.js';
                    $GLOBALS['js_set'][] = '/modules/favorites/favorites.js';
                    
                    Response::SetBoolean('search_form', true);
                }

                // кол-во элементов в списке
                $count = Request::GetInteger('count', METHOD_GET);
                if(!empty($count))
                    $get_parameters['count'] = $count;
                else
                    $count = Cookie::GetInteger('View_count_estate');
                if( empty( $count ) ) {
                    $count = 20;
                    Cookie::SetCookie('View_count_estate', Convert::ToString($count), 60*60*24*30, '/');
                }

                // страница списка
                $page = Request::GetInteger('page', METHOD_GET);
                $parameters = Request::GetParameters( METHOD_GET );
                if ((isset($page))&&($page==0)){
                    //чтобы не потерялись фильтры, надо включить их в redirect
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

                $clauses['published'] = array('value'=> 1);

                // сортировка
                $sortby = Request::GetInteger('sortby', METHOD_GET);
                if(empty($sortby)) $sortby = 1;
                else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
                $orderby = $sys_tables['cottages'].".advanced = 1 DESC, ".$sys_tables['cottages'].".random_sorting, ".$sys_tables['cottages'].".id_main_photo > 0 DESC, ";
                switch($sortby){
                    case 4: 
                        // по девелоперу по убыванию
                        $orderby .="developer_title!='' DESC,  developer_title DESC";
                        break;
                    case 3: 
                        // по девелоперу по возрастанию
                        $orderby .="developer_title!='' DESC,  developer_title ASC";
                        break;
                    case 2: 
                        // по району по возрастанию
                        $orderby .= $sys_tables['cottages'].".id_district_area > 0 DESC, district_title DESC"; 
                        break;
                    case 1: 
                    default: 
                        // по району по убыванию
                        $orderby .= $sys_tables['cottages'].".id_district_area > 0 DESC, district_title ASC"; 
                        break;
                }
                Response::SetString('sorting_url', '/'.$this_page->requested_path.'/?sortby=');
                Response::SetInteger('sortby', $sortby);
            
                $cottages = new Cottages();
                $where = $cottages->makeWhereClause($clauses);
                if(!empty($reg_where)){
                    $where .= " AND (".implode(" OR ", $reg_where).")";
                }
                
                if(empty($map_mode)){
                    $paginator = new Paginator($sys_tables['cottages'], $count, $where);
                    //только подсчет кол-ва объектов через ajax
                    if( !empty( $parameters['ajax_count']) ){
                        $ajax_result['ok'] = true;
                        $total_items = !empty($paginator->total_items_count) ? $paginator->total_items_count : $paginator->items_count;
                        $ajax_result['count'] = Convert::ToNumber( $total_items ) . ' ' . makeSuffix($total_items, 'объект', array('','а','ов') );
                        break;
                    }
                    $sortby = Request::GetInteger('sortby', METHOD_GET);
                    //редирект с несуществующих пейджей
                    if($page<0){
                        if (empty($sortby))
                            Host::Redirect('/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page=1');
                        else
                            Host::Redirect('/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'sortby='.$sortby.'&page=1');
                        exit(0);
                    }
                    if($paginator->pages_count>0 && $paginator->pages_count<$page){
                        if (empty($sortby))
                            Host::Redirect('/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page='.$paginator->pages_count);
                        else
                            Host::Redirect('/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'sortby='.$sortby.'&page='.$paginator->pages_count);
                        exit(0);
                    }
                    //формирование url для пагинатора
                    $paginator->link_prefix = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'&sortby='.$sortby.'&page=';
                    if($paginator->pages_count>1){
                        Response::SetArray('paginator', $paginator->Get($page));
                    }
                    $list = $cottages->getList($count,$count*($page-1),$where, $orderby);
                    $list = Favorites::ToList($list,6);

                    //увеличение счетчика показов
                    if(!empty($list)){
                        $ids = [];
                        $clear_ids = [];
                        foreach($list as $k=>$item){
                            $types = [];
                            //$ids[] = '('.$item['id'].', 2)';
                            $ids[] = '('.$item['id'].', 2, "'.Host::getUserIp().'", "'.$db->real_escape_string($_SERVER['HTTP_USER_AGENT']).'", "'.Host::getRefererURL().'")';
                            $clear_ids[] = $item['id'];
                            if($item['u_count']>0) $types[] = 'участки';
                            if($item['c_count']>0) $types[] = 'коттеджи';
                            if($item['t_count']>0) $types[] = 'таунхаусы';
                            if($item['k_count']>0) $types[] = 'квартиры';
                            $list[$k]['types'] = implode(', ',$types);
                        }
                        //увеличение счетчика показов
                        $db->querys("INSERT INTO ".$sys_tables['estate_complexes_stats_day_shows']." (id_parent, type, ip, browser, ref) VALUES ".implode(",",$ids)."");
                        $db->querys("UPDATE ".$sys_tables['cottages']." SET search_count = search_count + 1 WHERE id IN (".implode(',',$clear_ids).")");
                    }
                    Response::SetArray('list', $list);
                    Response::SetInteger('full_count', $paginator->items_count);                
                    Response::SetString('requested_url', $this_page->requested_url);                
                    $module_template = 'list.html';
                    
                    list($subscription_title, $description) = EstateSubscriptions::getTitle(false, $parameters, true, false, true);
                    $h1 = !empty($this_page->page_seo_h1) && ( ( !empty($ajax_mode) && $this_page->requested_path != $this_page->page_pretty_url ) || ( empty($ajax_mode) && $this_page->requested_url == $this_page->requested_path ) ) ? $this_page->page_seo_h1 : $subscription_title;
                    Response::SetString('h1', $h1);

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
                        'title' => ( !empty($paginator->items_count) ? Convert::ToNumber($paginator->items_count) . makeSuffix($paginator->items_count, ' объявлени', array('е', 'я', 'й')) . ' - ' : '' ) .  $h1,
                        'description' => $description . ' ☆ Уникальные предложения, которых не найти на других сайтах. ☆ Мы постоянно отслеживаем актуальность и достоверность объявлений'
                    );
                    $this_page->manageMetadata($new_meta, true);
                    
                } else {
                    $index = 0;
                    $where .= ' AND ' . $sys_tables['cottages']. ".lat > 0 AND " . $sys_tables['cottages']. ".lng > 0";
                    $map_list = $cottages->getList(100, 0, $where);
                    //отправляем в карту
                    $points = [];
                    foreach($map_list as $key=>$item){
                        if(!empty($item['lat']) && !empty($item['lng']) && $item['lng']>0 && $item['lat']>0){
                            $points[$key]['lat'] =  $item['lat'];
                            $points[$key]['lng'] =  $item['lng'];
                            $points[$key]['title'] =  'КП «' . $item['title'] . '»';
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
                break;
                
        }
        break;
        default:
            Host::RedirectLevelUp();
            break;        
}
Response::SetBoolean('show_overlay', true);

?>