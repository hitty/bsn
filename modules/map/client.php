<?php
// определяем возможный запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
//редирект с несуществующих пейджей
$page = Request::GetInteger('page', METHOD_GET);
if ( isset( $page ) && $page < 1 ) {
    //чтобы не потерялись фильтры, надо включить их в redirect
    $parameters=Request::GetParameters(METHOD_GET);
    //здесь будем накапливать строку с get-параметрами
    $url=[];
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
// определяем тип недвижимости
$estate_type = "";
$estate_types = [ 'live','build','commercial','country','inter' ];
if(!empty($this_page->page_parameters[0]) && in_array($this_page->page_parameters[0], $estate_types))  $estate_type = $this_page->page_parameters[0];
else $estate_type = 'build';
Response::SetString( 'estate_type', $estate_type );
// определяем тип сделки
$deal_type = '';
$deal_types = ['rent','sell'];
if(!empty($this_page->page_parameters[1]) && in_array( $this_page->page_parameters[1], $deal_types ) ) $deal_type = $this_page->page_parameters[1];
else $deal_type = 'sell'; 
Response::SetString( 'deal_type', $deal_type );

//формирование условия поиска
$parameters = Request::GetParameters( METHOD_GET );
$estate_search = new EstateSearch();
list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams();
// обработка общих action-ов
switch(true){
    //////////////////////////////////////////////////////////////////////////////
    // главная страница 
    //////////////////////////////////////////////////////////////////////////////
    case (empty($action) && count($this_page->page_parameters) == 0) // объекты недвижимости
         || (count($this_page->page_parameters) > 0 && in_array( $action, [ 'live', 'build', 'commercial', 'country', 'zhiloy_kompleks', 'apartments', 'cottedzhnye_poselki', 'business_centers', 'cottage' ] ) ): // ЖК, КП, БЦ
        // подключение поисковой формы            
        Response::SetBoolean('payed_format', true);  
        Response::SetBoolean('search_form', true);  
        Response::SetString('estate_type', $estate_type);  
        Response::SetBoolean('map_search', true);  
        require_once("includes/form.estate.php");
        //избранное
        $GLOBALS['js_set'][] = '/modules/favorites/favorites.js';
        $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
        //стили инфраструктуры
        $GLOBALS['css_set'][] = '/modules/infrastructure/styles.css';
        //стили карты
        $GLOBALS['css_set'][] = '/css/yandex.map.css';
        $GLOBALS['css_set'][] = '/modules/map/style.css';
        //скрипты формы поиска
        $GLOBALS['js_set'][] = '/js/form.validate.js';
        $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
        $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        Response::SetBoolean('show_topline', false);
        $module_template = 'mainpage.html';
        break;
        
}

?>