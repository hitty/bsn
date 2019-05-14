<?php
require_once('includes/class.textline.php');
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
$textline = new TextLine();
if(Host::isBot()) die();
//Host::checkUser(Host::$remote_user_ip, false, true);
switch(true){
    case empty($action) || Validate::isDigit($action):
        if($ajax_mode ){
            $item = $textline->getRandomItem(!empty($action) ? $action : false);
            Response::SetString('type', !empty($action) ? $action : false);
            Response::SetArray('item', $item) ;
            $ajax_result['ok'] = !empty($item);
            $ajax_result['fi'] = $this_page->first_instance;
            $ajax_result['aj'] = $ajax_mode;
            $ajax_result['ip'] = Host::$remote_user_ip;
            $module_template = 'item.html';
            $ref_url = Request::GetString('ref_url', METHOD_POST);
            $ajax_result['ref'] = $ref_url;
            $ref = parse_url(Host::getRefererURL());
            if(!empty($ref['query']) && $ref['query'] == 'from=advp') break;
            $textline->show($item, $ref_url);
        }
        break;
    case $action=='click': // запись статистики клика
        if($ajax_mode){
            $id = Request::GetInteger('id',METHOD_POST);
            $ref = Request::GetString('ref',METHOD_POST);
            $res = $textline->click($id, $ref);
            $ajax_result['ok'] = $res;
        } else $this_page->http_code=404;
        break;
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}




?>