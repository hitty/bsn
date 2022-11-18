<?php
// таблицы модуля
$sys_tables = Config::$sys_tables;
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
switch(true){
    case Validate::isDigit($action):
        if($ajax_mode){
            //получение баннера: спонсор района
            $item = $db->fetch("SELECT * FROM ".$sys_tables['district_banners']." WHERE `published` = ? AND `enabled` = ? AND `date_start` <= CURDATE() AND `date_end` > CURDATE() AND id_district = ? ",
                                1, 1,  $action
            );
            //Спонсор района (баннер)
            if(!empty($item)){
                $ajax_result['ok'] = true;
                if(!Host::$is_bot) $db->querys("INSERT INTO ".$sys_tables['district_banners_stats_day_shows']." SET id_parent = ?", $item['id']);
                $item['img_folder'] = Config::$values['img_folders']['district_banners'];
                Response::SetArray('item', $item);
                $module_template = 'block.html';
            }  else $module_template = '/templates/clearcontent.html';
        } else $this_page->http_code = 404;
        break;

    case $action=='click': // запись статистики клика
        if($ajax_mode){
            $id = Request::GetInteger('id',METHOD_POST);
            if($id>0){
                if(!Host::$is_bot) $res=$db->querys("INSERT INTO ".$sys_tables['district_banners_stats_day_clicks']." SET `id_parent`=".$id);
                $ajax_result['ok'] = $res;
            }
        } else $this_page->http_code=404;
        break;
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}
?>