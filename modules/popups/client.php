<?php

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Информационная страница рейтинга
    ////////////////////////////////////////////////////////////////////////////////////////////////////////                  
    case $action == 'zhiloy_kompleks_rating':
        $module_template = 'popup.zhiloy.kompleks.rating.html';
        break;
    case $action == 'apartments_rating':
        $module_template = 'popup.apartments.rating.html';
        break;
    default:
        $this_page->http_code = 404;
        break;
}
$ajax_result['ok'] = true;
?>