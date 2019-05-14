<?php
if(empty($this_page->page_parameters[0])){
    Response::SetBoolean('wide_format', true);
    $GLOBALS['js_set'][] = '/modules/infrastructure/yandex.map.js';
    $GLOBALS['css_set'][] = '/modules/infrastructure/styles.css';
    $GLOBALS['css_set'][] = '/modules/contacts/style.css';
    $module_template = 'contacts.html';

} else Host::RedirectLevelUp();
?>
