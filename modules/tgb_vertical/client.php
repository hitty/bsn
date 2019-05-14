<?php
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
switch(true){
    case empty($action):
    case $action == 'left':
        if($ajax_mode){
            //получение баннера: Вертикальный баннер
            $item = $db->fetch("SELECT * FROM ".$sys_tables['tgb_vertical']." WHERE `published` = ? AND `enabled` = ? AND position = ? AND `date_start` <= CURDATE() AND `date_end` > CURDATE() ORDER BY RAND()",
                                1, 1, empty($action) ? 1 : 2
            );
            //Вертикальный баннер (баннер)
            if(!empty($item)){
                $ajax_result['ok'] = true;
                if(!Host::$is_bot) $db->query("INSERT INTO ".$sys_tables['tgb_vertical_stats_day_shows']." SET id_parent = ?", $item['id']);
                $item['img_folder'] = Config::$values['img_folders']['tgb_vertical'];
                Response::SetArray('item', $item);
                $module_template = 'block.html';
            }  else $module_template = '/templates/clearcontent.html';
        } else $this_page->http_code = 404;
        break;

    case $action=='click': // запись статистики клика
        if($ajax_mode){
            $id = Request::GetInteger('id',METHOD_POST);
            if($id>0){
                if(!Host::$is_bot) $res=$db->query("INSERT INTO ".$sys_tables['tgb_vertical_stats_day_clicks']." SET `id_parent`=".$id);
                $ajax_result['ok'] = $res;
            }
        } else $this_page->http_code=404;
        break;
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}
?>