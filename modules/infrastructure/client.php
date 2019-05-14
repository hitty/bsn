<?php
$action = empty($this_page->page_parameters[0]) ? "" : (empty($ajax_action) ? $this_page->page_parameters[0]: $ajax_action);
require_once('includes/class.infrastructure.php');
switch(true){
    //////////////////////////////////////////////////////////////////////////////
    // получение списка объектов инфраструктуры по координатам
    //////////////////////////////////////////////////////////////////////////////
    case empty($action) && !empty($ajax_mode):
        
        $titles = array('Магазины', 'Образование', 'Парки', 'Спорт', 'Кафе', 'Медицина', 'Музеи', 'Кинотеатры');
        
        $top_left_lat = Request::GetString('top_left_lat', METHOD_POST);
        $top_left_lng = Request::GetString('top_left_lng', METHOD_POST);
        $bottom_right_lat = Request::GetString('bottom_right_lat', METHOD_POST);
        $bottom_right_lng = Request::GetString('bottom_right_lng', METHOD_POST);
        
        $ajax_result = Infrastructure::getInfrastructure($titles, $top_left_lat, $top_left_lng, $bottom_right_lat, $bottom_right_lng);

        break;
    //////////////////////////////////////////////////////////////////////////////
    // получение ближайших объектов инфраструктуры по координатам
    //////////////////////////////////////////////////////////////////////////////
    case $action == 'nearest' && !empty($ajax_mode):
        
        $estate_type = Request::GetString('estate_type',METHOD_POST);
        $estate_type = ( in_array( $estate_type, array_keys(Config::$values['object_types']) ) ? $estate_type : " ");
        
        $deal_type = Request::GetString('deal_type',METHOD_POST);
        $deal_type = (in_array($deal_type,array('rent','sell')) ? ($deal_type == "rent" ? 1 : 2) : "");
        
        $lat = Request::GetString('lat',METHOD_POST);
        $lng = Request::GetString('lng',METHOD_POST);
        
        $ajax_result = Infrastructure::getNearestInfrastructureObjects($estate_type, false, $deal_type, $lat, $lng);
        Response::SetArray('list', $ajax_result['markers']);
        $module_template = "nearest.html";
        break;
    
    //////////////////////////////////////////////////////////////////////////////
    // формирование списка объектов инфраструктуры
    //////////////////////////////////////////////////////////////////////////////
    case $action == 'list':
        $module_template = 'list.html';
        $list = $db->fetchall("SELECT * FROM ".$sys_tables['infrastructure_categories']." ORDER BY title");
        Response::SetArray('list', $list);
        break;
    default:
        break;
}
?>