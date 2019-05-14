<?php
$GLOBALS['js_set'][] = '/modules/moderation/ajax_actions.js';

require_once('includes/class.paginator.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Модерация улиц'));
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/admin.mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['city'] = Request::GetString('f_city',METHOD_GET);
$filters['title'] = Request::GetString('f_title',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['city'])) {
    $filters['city'] = urldecode($filters['city']);
    $get_parameters['f_city'] = $filters['city'];
}
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];



$ajax_mode = $ajax_mode && (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');


// обработка action-ов
switch($action){
     /*********************\
    |*  Работа с улицами  *|
    \*********************/
    default:
        $module_template = 'admin.streets.list.html';
        // формирование списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['title'])) $conditions[] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['city'])) $conditions[] = "`id_city` IN (SELECT id FROM ".$sys_tables['cities']." WHERE name_ru LIKE '%".$db->real_escape_string($filters['city'])."%' OR name_en LIKE '%".$db->real_escape_string($filters['city'])."%')";
        }
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['streets_variants'], 30, $condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/moderation/streets'                   // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)               // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page=";   // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }
        $sql = "SELECT id,title
                FROM ".$sys_tables['streets_variants'];
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY `title` DESC";
        $sql .= " LIMIT ".$paginator->getLimitString($page); 
        $list = $db->fetchall($sql);
        // формирование списка
        Response::SetArray('list', $list);
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>