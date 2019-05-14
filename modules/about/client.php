<?php
require_once('includes/class.content.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

$GLOBALS['css_set'][] = '/modules/about/styles.css';
$GLOBALS['js_set'][] = '/modules/about/script.js';

switch(true){
    case empty($action):
        $module_template = 'mainpage.html';
        break;
    
    default:
        $this_page->http_code = 404;
        break;
}
?>