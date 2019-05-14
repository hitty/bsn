<?php
require_once('includes/class.content.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

$GLOBALS['css_set'][] = '/modules/navigator/common.css';
$GLOBALS['css_set'][] = '/modules/navigator/styles.css';

$GLOBALS['js_set'][] = '/js/jquery.min.js';
$GLOBALS['js_set'][] = '/modules/navigator/jquery.popup.window.js';
$GLOBALS['js_set'][] = '/modules/navigator/script.js';

switch(true){
    case empty($action):
        $module_template = 'mainpage.html';
        break;
    case Validate::isDigit($action) && $ajax_mode:
        Response::SetInteger('action', $action);
        $ajax_result['ok'] = true;
        $module_template = 'block.' . $action . '.html';
        break;
    default:
        $GLOBALS['css_set'][] = '/css/common.css';
        $GLOBALS['css_set'][] = '/css/central.css';
        $this_page->http_code = 404;
        break;
}
?>