<?php
require_once('includes/class.content.php');
if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
$GLOBALS['css_set'][] = '/css/content.css';
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
switch(true){
    case !empty($action) && $action == 'block':
        $id = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        $item = $db->fetch("SELECT * FROM " . $sys_tables['articles_promo_blocks']." WHERE id = ?", $id);
        Response::SetArray('item', $item);
        $photos = Photos::getList('articles_promo_blocks', $id);
        Response::SetArray('photos', $photos);
        $ajax_result['ok'] = true;
        $module_template = 'item.html';
        break;
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}




?>