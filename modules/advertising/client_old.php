<?php
require_once('includes/class.content.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

$GLOBALS['css_set'][] = '/modules/advertising/styles.css';

$GLOBALS['js_set'][] = '/js/jquery.min.js';
$GLOBALS['js_set'][] = '/js/main.js';
$GLOBALS['js_set'][] = '/js/adriver.core.2.js';
$GLOBALS['js_set'][] = '/js/interface.js';
$GLOBALS['js_set'][] = '/js/jquery.form.expand.js';

if(!empty($this_page->page_parameters[1])){
    $GLOBALS['css_set'][] = '/css/common.css';
    $GLOBALS['css_set'][] = '/css/central.css';
    $this_page->http_code = 404;
} 

switch(true){
    case $action == 'line_ads':
        $module_template = 'line.ads.html';
        break;
    case $action == 'media_ads':
        $module_template = 'media.ads.html';
        break;
    case $action == 'bsntarget':
        $module_template = 'target.ads.html';
        break;
    case $action == 'misc':
        $module_template = 'misc.ads.html';
        break;
    case empty($action):
        $module_template = 'mainpage.html';
        break;
    default:
        $GLOBALS['css_set'][] = '/css/common.css';
        $GLOBALS['css_set'][] = '/css/central.css';
        $this_page->http_code = 404;
        break;
}
?>