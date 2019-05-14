<?php
Response::SetBoolean('not_show_estimate_banner', true);
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.estate.php');
require_once('includes/class.estate.estimate.php');
$GLOBALS['js_set'][] = '/modules/estate_estimate/script.js';
$GLOBALS['css_set'][] = '/modules/estate_estimate/style.css';

// определяем экшн   
$action = empty($this_page->page_parameters[0]) ? false : $this_page->page_parameters[0];
switch(true){
    //////////////////////////////////////////
    // Страница оценки
    /////////////////////////////////////////
    case empty($action):
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/modules/infrastructure/yandex.map.js';
        //справочная информация
        $purposes = $db->fetchall("SELECT * FROM ".$sys_tables['estate_estimate_purposes']." ORDER BY id");
        Response::SetArray('purposes', $purposes);
        $building_types = $db->fetchall("SELECT * FROM ".$sys_tables['building_types']." ORDER BY title");
        Response::SetArray('building_types', $building_types);
        Response::SetBoolean( 'payed_format', true );

        $h1 = empty($this_page->page_seo_h1) ? 'Оценка стоимости недвижимости' : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);    
        
        $module_template = 'main.html';
        break;
    //////////////////////////////////////////
    // Расчет
    /////////////////////////////////////////
    case $action == 'result' && count($this_page->page_parameters) == 1:
        $post_parameters = Request::GetParameters(METHOD_POST);
        if(empty($post_parameters)) {
            $this_page->http_code = 404;
            break;
        }
        //инициализация класса
        EstateEstimate::Init($post_parameters);
        //вычисление данных квартир
        EstateEstimate::Calculate();
        //запись в таблицу
        if(!empty(EstateEstimate::$calculate) && EstateEstimate::$calculate >= 3)  {
            EstateEstimate::Write();
            if(!empty( EstateEstimate::$data['email'] ) && Validate::isEmail( EstateEstimate::$data['email'] )) {
                EstateEstimate::Registration();
                EstateEstimate::sendMail();
                $auth->AuthCheck(EstateEstimate::$data['email'], EstateEstimate::$passwd);
            }
            $ajax_result['ok'] = true;
            $ajax_result['hash'] = EstateEstimate::$data['hash'];
        } 
                                    
        break;
    //////////////////////////////////////////
    // Страница результата оценки
    /////////////////////////////////////////
    case !empty($action) && count($this_page->page_parameters) == 1:        
        //инициализация класса
        EstateEstimate::Init();        
        EstateEstimate::getData($action);        
        if(empty(EstateEstimate::$data)) {
            $this_page->http_code = 404;
            break;
        }        
        //вычисление данных квартир
        EstateEstimate::Calculate();
        
        Response::SetArray('item', EstateEstimate::$data);
        Response::SetArray('list', EstateEstimate::$calculate);
        Response::SetArray('costs', EstateEstimate::$costs);
        Response::SetString('estate_type','build');
        Response::SetString('deal_type','sell');
        $module_template = 'item.html';
        break;
}
Response::SetBoolean('show_overlay', true);
Response::SetBoolean( 'payed_format', true );

?>