<?php
$action = !empty($this_page->page_parameters[0]) ? $this_page->page_parameters[0] : false;
if(!empty($action) && count($this_page->page_parameters) == 1 && Validate::isDigit($action) && !Host::isBot()){
    $item = $db->fetch("SELECT * FROM ".$sys_tables['adriver_banners']." WHERE id = ?", $action);
    if(!empty($item)){
        $this_page->page_title = $item['title'];
        Response::SetArray('item', $item);
        $module_template = 'item.html';
        if(!Host::isBsn("adriver_banners_stats_click_day",$item['id'])) $db->querys("INSERT INTO ".$sys_tables['adriver_banners_stats_click_day']." SET id_parent = ?, ref=?, ip=?, agent=?", $item['id'], Host::getRefererURL(), Host::getUserIp(), $_SERVER['HTTP_USER_AGENT']);
    } else Host::Redirect('/');
} else {
    Host::Redirect('/');
}
?>