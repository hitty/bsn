<?php
require_once('includes/class.paginator.php');
require_once('includes/class.sale.php');
if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
echo $GLOBALS['js_set'][] = '/js/form.validate.js';
// мэппинги модуля

//от какой записи вести отчет
$from=0;
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
Response::SetString('campaigns_img_folder',Config::Get('img_folders/campaigns'));
      
// обработка общих action-ов
switch(true){
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // форма на главную страницу
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'ad':  
        $partners = array('pingola','naydidom');
        $this_page->page_template = '/templates/sale_clear.html';
        $module_template = 'block.html';
        if(empty($this_page->page_parameters[1]) || empty($this_page->page_parameters[2]) || empty($this_page->page_parameters[3]) || !in_array($this_page->page_parameters[1], $partners)){
            break;            
        }
        $partner = $this_page->page_parameters[1]; //pingola, naydidom
        $count = $this_page->page_parameters[2];   //кол-во в ряд
        Response::SetString('position',$this_page->page_parameters[3]); //тип размещения
        
        $GLOBALS['css_set'][] = '/modules/sale/style_'.$partner.'.css';
        
        $campaigns = new SaleListCampaigns();
        $list = $campaigns->Search($count,0, false, "RAND()", $partner);
        Response::SetArray('list', $list);

        if($partner=='naydidom' && $this_page->page_parameters[3]=='horizontal') Response::SetString('h1','Каталог выгодных предложений по недвижимости Петебурга и области. Акции. Скидки');

        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // форма на главную страницу
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='block':  
            $count = 3;
            $module_template = 'block.html';    
            $campaigns = new SaleListCampaigns();
            $list = $campaigns->Search($count,0, false, "RAND()");
            Response::SetArray('list', $list);
       
        break;
    //////////////////////////////////////////////////////////////////////////
    // Поиск объектов
    //////////////////////////////////////////////////////////////////////////
    case $this_page->real_path == 'search' && empty($this->page_parameters[0]):
        // формирование набора условий для поиска
        $parameters = Request::GetParameters(METHOD_GET);
       
        $campaigns = new SaleListCampaigns();
        if(!$ajax_mode){
            $GLOBALS['js_set'][] = '/js/interface.js';
            $this_page->addBreadcrumbs('Спецпредложения', 'search');
            //заголовки h1
            $h1 = empty($this_page->page_seo_h1) ? 'Спецпредложения' : $this_page->page_seo_h1;
            Response::SetString('h1', $h1);
            $this_page->page_seo_title = empty($this_page->page_seo_title) ? $h1 : $this_page->page_seo_title;
        }
        $get_parameters = $work_params_data = array();
        // кол-во элементов в списке
        $count = Request::GetInteger('count', METHOD_GET);
        if(!empty($count)) $get_parameters[] = 'count='.$count;
        else $count = Cookie::GetInteger('View_count');
        if(empty($count)) {
            $count = 15;
            Cookie::SetCookie('View_count', Convert::ToString($count), 60*60*24*30, '/');
        }
        $clauses = array();

        $reg_where = array();
        if(!empty($parameters['subways'])) {
            $subways_array = explode(',',$parameters['subways']);
            foreach($subways_array as $da_key=>$da_val) if(!Validate::isDigit($da_val)) unset($subways_array[$da_key]);
            if(!empty($subways_array)) {
                $get_parameters['subways'] = implode(',', $subways_array);
                $reg_where[] = $campaigns->work_table.".`id_subway` IN (".implode(',', $subways_array).")";
                $subways = $db->fetchall("SELECT id, title FROM ".$sys_tables['subways']." WHERE id IN (".implode(',', $subways_array).")");
                Response::SetArray('subways',$subways);
            }
        }

        if(isset($parameters['rooms'])) {
            $clauses['rooms_total'] = array('set'=> explode(',',$parameters['rooms']), 'table' => $sys_tables['offers']);
            $get_parameters['rooms'] = $parameters['rooms'];
            $work_params_data['rooms_checked'] = array();
            $arr = array();
            foreach($clauses['rooms_total']['set'] as $val) {
                $work_params_data['rooms_checked'][$val] = 1;
                $arr[] = $val; 
            }
        }
 
        $max_cost = Convert::ToInt(empty($parameters['max_cost'])?0:$parameters['max_cost']);
        $min_cost = Convert::ToInt(empty($parameters['min_cost'])?0:$parameters['min_cost']);
        if($max_cost || $min_cost) { 
            $clauses['cost'] = array();
            if($min_cost) {
                $clauses['cost']['from'] = $min_cost;
                $clauses['cost']['table'] = $sys_tables['offers'];
                $get_parameters['min_cost'] = $min_cost;
            }
            if($max_cost) {
                $clauses['cost']['to'] = $max_cost;
                $clauses['cost']['table'] = $sys_tables['offers'];
                $get_parameters['max_cost'] = $max_cost;
            }
        }
        // "прямые" условия
        $where = $campaigns->makeWhereClause($clauses);
        // добавление "особых" условий
        $reg_where = array();
        if(!empty($parameters['districts'])) {
            $districts_array = explode(',',$parameters['districts']);
            foreach($districts_array as $da_key=>$da_val) if(!Validate::isDigit($da_val)) unset($districts_array[$da_key]);
            if(!empty($districts_array)) {
                $get_parameters['districts'] = implode(',', $districts_array);
                $reg_where[] = $campaigns->work_table.".`id_district` IN (".implode(',', $districts_array).")";
                $districts = $db->fetchall("SELECT id, title FROM ".$sys_tables['districts']." WHERE id IN (".implode(',', $districts_array).")");
                Response::SetArray('districts',$districts);
            }
        }
        if(!empty($parameters['districts_areas'])) {
            $districts_areas_array = explode(',',$parameters['districts_areas']);
            foreach($districts_areas_array as $da_key=>$da_val) if(!Validate::isDigit($da_val)) unset($districts_areas_array[$da_key]);
            if(!empty($districts_areas_array)) {
                $get_parameters['districts_areas'] = implode(',', $districts_areas_array);
                $districts_areas = $db->fetchall("SELECT id, id_region, id_area, offname as title FROM ".$sys_tables['geodata']." WHERE id IN (".implode(',', $districts_areas_array).")");
                Response::SetArray('districts_areas',$districts_areas);
                foreach($districts_areas as $reg){
                    $reg_where[] = "(".$sys_tables['campaigns'].".`id_region`=".$reg['id_region']." AND ".$sys_tables['campaigns'].".`id_area`=".$reg['id_area'].")";
                }
            }
        }
        if(!empty($reg_where)){
            $where .= " AND (".implode(" OR ", $reg_where).")";
        }
        $where.= " AND ".$sys_tables['campaigns'].".date_end > NOW()  AND ".$sys_tables['campaigns'].".date_start <= NOW() ";
        // сортировка GET-параметров в алфавитном порядке
        ksort($get_parameters);
        
        // страница списка
        $page = Request::GetInteger('page', METHOD_GET);
        if ((isset($page))&&($page==0)){
            //чтобы не потерялись фильтры, надо включить их в redirect
            $parameters=Request::GetParameters(METHOD_GET);
            //здесь будем накапливать строку с get-параметрами
            $url=array();
            foreach($parameters as $key=>$item){
                if ($key!='path'){
                    if ($key!='page') $url[]=$key.'='.$item;
                    else $url[]=$key.'=1';//заменяем page на 1
                } 
            }
            $url='?'.implode('&',$url);
            Host::Redirect('/'.$this_page->requested_path.'/'.$url);
            exit(0);
        } 
        if(empty($page)) $page = 1;
        else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
        $sql = "SELECT count(*) as items_count 
                FROM ".$sys_tables['offers']." 
                LEFT JOIN ".$sys_tables['campaigns']." ON  ".$sys_tables['offers'].".id_campaign = ".$sys_tables['campaigns'].".id
                WHERE ".$where;
        $paginator = new Paginator($campaigns->work_table, $count, $where, $sql);
        if($ajax_mode){ // вывод кол-ва найденных предложений 
            $ajax_result['last_query'] = '';
            $ajax_result['ok'] = true;
            $ajax_result['html'] = "<span class='found-offers-text'>Найдено <b>".$paginator->items_count."</b> ".makeSuffix(($paginator->items_count),'предложени',array('е','я','й')).'</span><span class="arrow"></span>';
        }  else {
            //если у нас ЧПУ страница то "выкусываем" GET параметры
            $url_params = parse_url($this_page->requested_url);
           
            if($this_page->real_url!=$this_page->requested_url && empty($url_params['query'])) $paginator_link_base = '/'.$this_page->requested_url.'/?';
            elseif($this_page->real_url!=$this_page->requested_url)  $paginator_link_base = '/'.$this_page->requested_path.'/?'.(!empty($url_params['query'])?''.$url_params['query'].'&':'');
            else $paginator_link_base = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&');
            //редирект с несуществующих пейджей
            if($page<0){
                $sortby = Request::GetInteger('sortby', METHOD_GET);
                if(empty($sortby)){
                    Host::Redirect('/'.$paginator_link_base.'page=1');
                }
                else{
                    Host::Redirect('/'.$paginator_link_base.'sortby='.$sortby.'&page=1');
                }
                exit(0);
            }
            if($paginator->pages_count>0 && $paginator->pages_count<$page){
                $sortby = Request::GetInteger('sortby', METHOD_GET);
                if(empty($sortby)){
                    Host::Redirect('/'.$paginator_link_base.'page='.$paginator->pages_count);
                }
                else{
                    Host::Redirect('/'.$paginator_link_base.'sortby='.$sortby.'&page='.$paginator->pages_count);
                }
                exit(0);
            }
            //сортировка
            $sortby = Request::GetInteger('sortby', METHOD_GET);
            if(!empty($sortby)) Response::SetBoolean('noindex',true);
            else  $sortby = 1;

            $orderby = $campaigns->makeSort($sortby);

            Response::SetString('sorting_url', $paginator_link_base.'page='.$page.'&sortby=');
            Response::SetInteger('sortby', $sortby);

            //формирование url для пагинатора
            $paginator->link_prefix = $paginator_link_base.(!empty($sortby)?'sortby='.$sortby.'&':'').'page=';
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }
            
            //поиск объектов 
            $list = $campaigns->Search($count,$count*($page-1), $where, $orderby);
            
            // папка для картинок
            Response::SetString('img_folder',Config::Get('img_folders/campaigns'));
            //массив акций
            Response::SetArray('list', $list);
            // общее кол-во предложений
            Response::SetArray('full_count', array('value'=>$paginator->items_count,'title'=>makeSuffix(($paginator->items_count),'предложени',array('е','я','й'))));
            // общее кол-во предложений по акциям
            Response::SetArray('offer_type_counter', $campaigns->offer_type_counter);

            Response::SetString('requested_url', $this_page->requested_url);
            Response::SetString('sortby', $sortby);

            $module_template = 'list.html';
            $this_page->addBreadcrumbs('Поиск', 'search');
            
            Response::SetArray('form_data', $get_parameters + $work_params_data);            
            //сохранение поискового запроса в сессию
            Session::SetString('search_query',$this_page->real_url);
        }
        if(!empty($get_parameters)) $this_page->page_seo_text = '';
        break;    
    //////////////////////////////////////////////////////////////////////////
    // Карточка объекта
    //////////////////////////////////////////////////////////////////////////
    case $this_page->page_url == 'campaign' && !empty($action) && count($this_page->page_parameters)==1:
        $campaign = $db->fetch("SELECT * FROM ".$sys_tables['campaigns']." WHERE chpu_title = ?", $action);
        if(empty($campaign)){
            $this_page->http_code=404;
            break;
        }
        $id = $campaign['id'];
        //отправка заявки с карточки объекта
        if(!empty($ajax_mode)){
            $name = Request::GetString('name', METHOD_POST);
            $phone = Request::GetString('phone', METHOD_POST);
            $email = Request::GetString('email', METHOD_POST);
            $db->querys("INSERT INTO ".$sys_tables['application_forms']." SET `name` = ?,  `phone` = ?,  `email` = ?,  `id_campaign` = ?", $name, $phone, $email, $id);
        }
        $campaignItem = new SaleItemCampaigns($id);
        if(empty($campaignItem->data_loaded)){
            $this_page->http_code=404;
            break;
        }
        
        $GLOBALS['js_set'][] = '/js/interface.js';
        $GLOBALS['js_set'][] = '/js/validate.js';
        $GLOBALS['js_set'][] = '/modules/sale/item.js';

        //АКЦИЯ
        
        //увеличиваем счетчик просмотров
        $campaignItem->setField('views_count', $campaignItem->getField('views_count')+1);
        $campaignItem->Save();

        //данные объекта
        $item = $campaignItem->getData();
        //приведение 2 нулей после точки к красивому виду
        if(!empty($item['discount']) )    $item['discount'] = rtrim(rtrim($item['discount'], '0'), '.');
        if(!empty($item['installment']) ) $item['installment'] = rtrim(rtrim($item['installment'], '0'), '.');
        //формирование окончания для ЭТАЖ
        if(!empty($item['floors']) ) $item['floors_title'] = makeSuffix(($item['floors']),'этаж',array('','а','ей'));
        //получение кол-ва дней, часов и минут до окончания 
        
        $today = new DateTime();  //сейчас
        $date_end = new DateTime(date('Y-m-d H:i:s', strtotime($item['date_end']))); //дата окончания показа
        Response::SetArray('date_interval',date_diff($date_end, $today));
        Response::SetArray('item', $item); 

        //вывод справочных данных
        $info = $campaignItem->getInfo();
        Response::SetArray('info', $info);

        // тайтл
        $this_page->manageMetadata(array('title'=>$item['title'].' - sale.BSN.ru - [ID: '.$item['id'].']','description'=>$item['title'],'keywords'=>$item['title']), true);

        //список фото
        $photos = Photos::getList('campaigns',$id);
        Response::SetArray('photos', $photos);
        Response::SetString('img_folder',Config::$values['img_folders']['campaigns']);
        
        //ПРЕДЛОЖЕНИЯ ПО АКЦИИ
        $offers = new SaleListOffers();
        //условия выборки
        $clauses = array();
        $clauses['id_campaign'] = array('value'=> $item['id']);
        $where = $offers->makeWhereClause($clauses);
        //поиск объектов 
        $list =  $offers->search($clauses, 1000, 0, $sys_tables['offers'].'.rooms_total');
        Response::SetArray('offers_list',$list);
        
        //вывод поискового запроса из сессии
        $search_query = Session::GetString('search_query');
        Response::SetString('search_query',empty($search_query)?'search/':$search_query);
        $module_template = "item.html";
        break;

    case empty($action): // рубрикатор
        Host::Redirect('/');
        break;
    default: // рубрикатор
        $this_page->http_code = 404;
        break;
}
?>