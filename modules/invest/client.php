<?php
require_once('includes/class.content.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

if(empty($ajax_mode)){
    $GLOBALS['css_set'][] = '/modules/invest/styles.css';
    $GLOBALS['js_set'][] = '/js/jquery.min.js';
    $GLOBALS['js_set'][] = '/js/main.js';
    $GLOBALS['js_set'][] = '/js/interface.js';
    $GLOBALS['js_set'][] = '/js/jquery.form.expand.js';
    
    $GLOBALS['css_set'][] = '/modules/invest/gallery/style.css';
    $GLOBALS['js_set'][] = '/modules/invest/gallery/script.js';

    $GLOBALS['css_set'][] = '/modules/invest/slimscroll/style.css';
    $GLOBALS['js_set'][] = '/modules/invest/slimscroll/jquery.slimscroll.js';

    if(!empty($this_page->page_parameters[1])){
        $GLOBALS['css_set'][] = '/css/common.css';
        $GLOBALS['css_set'][] = '/css/central.css';
        $this_page->http_code = 404;
    } 
}
$search_sql = "SELECT ".$sys_tables['invest'].".*,
                      ".$sys_tables['invest_categories'].".title_eng AS category_title,
                      ".$sys_tables['invest_categories'].".alias,
                       CASE
                            WHEN ".$sys_tables['invest'].".status=1 THEN 'Success Stories'
                            WHEN ".$sys_tables['invest'].".status=2 THEN 'Ongoing'
                            WHEN ".$sys_tables['invest'].".status=3 THEN 'Future projects'
                       END AS status_title
                      
               FROM ".$sys_tables['invest']."
               LEFT JOIN ".$sys_tables['invest_categories']." ON ".$sys_tables['invest'].".id_category = ".$sys_tables['invest_categories'].".id";
               
$photos_sql = "SELECT *, LEFT(name,2) as subfolder FROM ".$sys_tables['invest_photos'];

$this_page->setPageTemplate('modules/invest/templates/client.html');

switch(true){
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // список проектов
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case !empty($_POST) && $action == "list":
        
        $category = Request::GetString('category',METHOD_POST);
        $category = (!empty($category) ? $category : "all");
        $status = Request::GetInteger('status',METHOD_POST);
        
        if(!Validate::isDigit($category)){
            $category = $db->fetch("SELECT id FROM ".$sys_tables['invest_categories']." WHERE alias = ?",$category);
            $category = (!empty($category) && !empty($category['id']) ? $category['id'] : 0);
        }
        
        $list = array();
        
        if(!empty($category) && $category != 'all') $where[] = "id_category = ".$category;
        $where[] = (!empty($status) ? "status = ".$status : "status > 0");
        $where = " WHERE ".implode(" AND ",$where);
        
        $list = $db->fetchall($search_sql.$where);
        
        $ajax_result['markers_list'] = $list;
        $ajax_result['ok'] = true;
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // карточка
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case ($ajax_mode && !empty($action)) || (Validate::isDigit($action) && !$ajax_mode):
        
        $search_field = (Validate::isDigit($action) ? "id" : "alias");
        
        $item = $db->fetch($search_sql." WHERE ".$sys_tables['invest'].".`".$search_field."` = ?",$action);
        if(empty($item)){
            $this_page->http_code = 404;
            break;
        }
        $photos = $db->fetchall($photos_sql." WHERE id_parent = ".$item['id']);
        
        Response::SetArray('item',$item);
        Response::SetArray('photos',$photos);
        $ajax_result['ok'] = true;
        $module_template = "item.html";
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // список карточек
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == "list":
        if($this_page->first_instance){
            $statuses_list = getStatusesList();
            Response::SetArray('status',$statuses_list);
        }
        
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // главная страница
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case empty($action):
    
        $statuses_list = getStatusesList();
        Response::SetArray('statuses_list',$statuses_list);
        $GLOBALS['js_set'][] = '/modules/invest/script.js';
        $GLOBALS['js_set'][] = '/modules/invest/map.js';
        
        $module_template = "mainpage.html";
        break;
    default:
        $GLOBALS['css_set'][] = '/css/common.css';
        $GLOBALS['css_set'][] = '/css/central.css';
        $this_page->http_code = 404;
        break;
}
//список статусов с количествами
function getStatusesList(){
    global $sys_tables;
    global $db;
    $statuses_list = $db->fetchall("SELECT ".Config::Get('sys_tables/invest').".status, 
                                           CASE
                                                WHEN ".Config::Get('sys_tables/invest').".status=1 THEN 'Success stories'
                                                WHEN ".Config::Get('sys_tables/invest').".status=2 THEN 'Ongoing'
                                                WHEN ".Config::Get('sys_tables/invest').".status=3 THEN 'Future projects'
                                           END AS title,
                                           COUNT(*) AS amount
                                   FROM ".Config::Get('sys_tables/invest')."
                                   WHERE ".Config::Get('sys_tables/invest').".status > 0
                                   GROUP BY ".Config::Get('sys_tables/invest').".status",false);
    $total_amount = 0;
    foreach($statuses_list as $key=>$item) $total_amount += $item['amount'];
    array_unshift($statuses_list,  array('title' => 'All projects', 'status' => 'all', 'amount' => $total_amount));
    return $statuses_list;
}
?>