<?php
if(empty($this_page->page_parameters[0])){
    Response::SetBoolean('wide_format', true);
    $GLOBALS['css_set'][] = '/modules/advertising/style.css';
    $module_template = 'mainpage.html';

} else Host::RedirectLevelUp();
 
?>