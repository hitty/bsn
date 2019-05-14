<?php
require_once('includes/class.videos.php');
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
switch(true){
    case !empty($action) && $action == 'block':
            $estate_type = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
            $id = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            if(!empty($id) && !empty($estate_type)){
                $videos = Videos::getList($estate_type, $id);
                Response::SetArray('videos', $videos);
                $module_template = 'block.html';
                if(!empty($videos)) $ajax_result['ok'] = true;
        }
        break;
    
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}




?>