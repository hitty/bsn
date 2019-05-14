<?php
// таблицы модуля
$sys_tables = Config::$sys_tables;

if (!empty($this_page->page_parameters[0])){
    $this_page->http_code=404;
}
else{
    //подключаем скрипт для отображения картинок
    
    $GLOBALS['css_set'][]='/modules/diploms/styles.css';
    Response::SetString('img_folder',Config::$values['img_folders']['diploms']);
    $list=$db->fetchall('SELECT '.$sys_tables['diploms'].'.year, '.$sys_tables['diploms_photos'].'.id, '.$sys_tables['diploms_photos'].'.name, LEFT('.$sys_tables['diploms_photos'].'.name,2) AS subfolder
                         FROM '.$sys_tables['diploms'].' 
                         LEFT JOIN '.$sys_tables['diploms_photos'].' ON '.$sys_tables['diploms'].'.id='.$sys_tables['diploms_photos'].'.id_parent 
                         ORDER BY year DESC, '.$sys_tables['diploms_photos'].'.id ASC');
    Response::SetArray('list',$list);
    Response::SetString('h1','Дипломы');
    $module_template='client.html';
}
?>