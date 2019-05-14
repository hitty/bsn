<?php
$get_parameters = Request::GetParameters(METHOD_GET);

//if( empty($get_parameters['search_form']) ) Host::Redirect('https://sale.bsn.ru');

require_once('includes/class.estate.php');
require_once('includes/class.paginator.php');
require_once('includes/class.promotions.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

//записей на страницу
$strings_per_page = 10;
Response::SetString('img_folder',Config::$values['img_folders']['opinions_expert_profiles']);
//от какой записи вести отчет
$from=0;
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$types = array (1=>'opinions',2=>'predictions',3=>'interview');
// обработка общих action-ов
switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // блок
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='block':
        if(!$this_page->first_instance || $ajax_mode) {
            $module_template='list.block.short.html';
            $type = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
            $id_user = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            if($type == 'agency'){
                $id = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                $count = 3;    
                $where = $sys_tables['promotions'].".id_user = ".$id_user." AND ".$sys_tables['promotions'].".id != ".$id;
            }  elseif($type == 'other_agencies') {
                $count = 5;
                $where = $sys_tables['promotions'].".id_user != ".$id_user;
            }
            $where .= " AND " . $sys_tables['promotions'].".published = 1";
            $promotion = new Promotion();
            $list = $promotion->getList('0,'.$count, "RAND()", $where,false,true);
            Response::SetArray('list', $list);
            Response::SetString('type', $type);
            //время жизни memcache
            $ajax_result['ok'] = true;
        } else $this_page->http_code=404;
        break;
     
    //////////////////////////////////////////////////////////////////////////////
    // список акций
    //////////////////////////////////////////////////////////////////////////////
    case empty($action):
        $module_template = 'list.html'; 
        $GLOBALS['js_set'][] = '/js/form.validate.js';
        $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
        $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] =  '/modules/estate/list_options.js';
        $GLOBALS['css_set'][] =  '/modules/promotions/style.css';
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        
        Response::SetBoolean('mainpage', true);
        //поиск акций
        $promotion = new Promotion();

        Response::SetBoolean('promotions_mainpage', true);
        // кол-во элементов в списке
        $count = 500;
        $page = 1;

        if(empty($ajax_mode)) $where = array($sys_tables['sale_campaigns'].".published = 1");
        else $where = array($sys_tables['sale_campaigns'].".published != 2");
        
        if(!empty($promotions_ids)) $where[] = $sys_tables['sale_campaigns'].".id IN (".implode(", ", $promotions_ids).")";
        if(!empty($get_parameters['agency'])) $where[] = $sys_tables['sale_agencies'].".bsn_id_agency = ".$db->real_escape_string($get_parameters['agency']);
        $where = implode(" AND ", $where);
        unset($get_parameters['path']);
        $paginator_link_base = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&');
        //сортировка
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        Response::SetString('sorting_url', $paginator_link_base.'&sortby=');
        Response::SetInteger('sortby', $sortby);

        $orderby = $promotion->makeSort($sortby);        

        $list = $promotion->getList(0, $orderby, $where, '',true);
        shuffle($list);
        $list = array_slice($list,0,3);
        if( !empty( $get_parameters['search_form'] ) ) Response::SetBoolean('only_objects', true);
        Response::SetArray('list', $list);
        $module_template = 'list.sale.html';  
        if(empty($ajax_mode)){
            //определение слогана 
            //папка изображениями 
            $dir = ROOT_PATH."/img/layout/promotions/slogans/";
            $dh = opendir($dir);
            $slogan_files = array();
            while($filename = readdir($dh)){        
                if(strlen($filename) > 5) $slogan_files[] = $filename; 
            }
            shuffle($slogan_files);
            Response::SetString('slogan_src', $slogan_files[0]);
            //подсчет кол-ва акций для объектов
            $amounts = $db->fetchall("
                                                SELECT 'live-sell' AS data_value,
                                                        IFNULL(COUNT(*),0) AS amount
                                                 FROM ".Config::$sys_tables['promotions']." 
                                                 WHERE published = 1 AND id_estate_type = 1
                                             
                                             UNION
                                            
                                                 SELECT 'build-sell' AS data_value,
                                                        IFNULL(COUNT(*),0) AS amount
                                                 FROM ".Config::$sys_tables['promotions']." 
                                                 WHERE published = 1 AND id_estate_type = 2 
                                             
                                             UNION
                                             
                                                 SELECT 'country-sell' AS data_value,
                                                        IFNULL(COUNT(*),0) AS amount
                                                 FROM ".Config::$sys_tables['promotions']."
                                                 WHERE published = 1 AND  id_estate_type = 4
                                            
                                             UNION
                                              
                                                 SELECT 'commercial-sell' AS data_value,
                                                        IFNULL(COUNT(*),0) AS amount
                                                 FROM ".Config::$sys_tables['promotions']."
                                                 WHERE published = 1 AND  id_estate_type = 3
                                             ",
            'data_value'); 
            $values = array();
            foreach($amounts as $k=>$amount){
                if($amount['amount'] > 0 && !empty($amount['data_value'])) $values[$amount['data_value']] = $amount['amount'];
            }
            Response::SetArray('promotions_amounts',$amounts);   
            $module_template = 'list.html'; 
        } else $ajax_result['ok'] = true;     
          
        break;
    //////////////////////////////////////////////////////////////////////////////
    // карточка акции
    //////////////////////////////////////////////////////////////////////////////
    case !empty($action) && count($this_page->page_parameters) == 1:
        $GLOBALS['css_set'][] = '/modules/applications/style.css';

        $GLOBALS['js_set'][] = "/modules/promotions/item.js";
        $GLOBALS['js_set'][] = "/js/form.validate.js";
        $GLOBALS['js_set'][] = "/js/jquery.estate.search.js";
        $GLOBALS['js_set'][] = "/js/jquery.ajax.filter.js";
        $GLOBALS['js_set'][] = "/modules/applications/form.js";
        
        $chpu = Convert::ToString($action);
        if(Validate::isDigit($chpu)) $where = $sys_tables['promotions'].".id = '".$chpu."'";
        else $where = $sys_tables['promotions'].".chpu_title = '".$chpu."'";
        
        $promotion = new Promotion();
        $item = $promotion->getItem($where);
        
        if(empty($item)) Host::Redirect('/promotions/');
        
        Response::SetArray('item',$item);
        //типы объектов
        if(!empty($item['object_types'])){
            $object_types_array = array(
                1=>'однокомнатные',
                2=>'двухкомнатные',
                3=>'трехкомнатные',
                4=>'многокомнатные',
                'студия'=>'студия',
            );
            Response::SetArray('object_types_array', $object_types_array);
            Response::SetArray('object_types', explode(',', $item['object_types'] ));
        }
        //хлебные крошки
        // категория
        $this_page->addBreadcrumbs($item['title'], $action);
        
        $new_meta = array();   
        $this_page->manageMetadata(array('title'=>$item['title'],
                                         'keywords'=>$item['title'],
                                         'description'=>$item['title']), true);
        
        $module_template='item.html';
        break;
    default:
        $this_page->http_code=404;
}
$GLOBALS['css_set'][] = '/modules/promotions/style.css';
?>