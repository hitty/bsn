<?php
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    //установка статуса - прочитано
    ////////////////////////////////////////////////////////////////////////////////////////////////////////        
    case $action == 'setread':
        $id = Request::GetInteger('id', METHOD_POST);
        $type = Request::GetString('type', METHOD_POST);
        Notifications::setRead($type, $id);
        break;
}
?>