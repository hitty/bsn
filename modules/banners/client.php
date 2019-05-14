<?php
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];     
// обработка общих action-ов
    switch(true){
        case $action == 'top':
        case $action == 'right':
        case $action == 'bottom':
        case $action == 'middle':
        case $action == 'topmenu':
        case $action == 'body':
        case $action == 'fullscreen':
        case $action == 'mainpage':
            Response::SetString('type',$action.(!empty($this_page->page_parameters[1])?'_'.$this_page->page_parameters[1]:''));
            $ajax_result['ok'] = true;
            $module_template = 'list.html';            
            Response::SetInteger('index',!empty($this_page->page_parameters[2]) && Validate::isDigit($this_page->page_parameters[2]) ? $this_page->page_parameters[2] : false);
            break;
        case $action == 'footer_links': 
        case $action == 'social_buttons': 
        case $action == 'social_buttons_photogallery': 
            if($action == 'footer_links') $this_page->page_cache_time = Config::$values['blocks_cache_time']['news_block'];
            Response::SetString('type',$action);
            if(!empty($this_page->page_parameters[1])) Response::SetString('sub_type',$this_page->page_parameters[1]);
            $ajax_result['ok'] = true;
            $module_template = 'list.html';            
            break;
        case $action == 'reformal':
            Response::SetString('type',$action);
            $ajax_result['ok'] = true;
            $module_template = 'list.html';            
            break;
        case $action == 'topline':
            $action = Request::GetParameter('action', METHOD_POST);
            if(!empty($action) && $action == 'off') Session::SetBoolean('not_show_topline', true);
        default:
            $module_template = '/templates/clearcontent.html';
            break;
    }
?>