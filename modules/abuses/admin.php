<?php

$GLOBALS['js_set'][] = '/modules/abuses/ajax_actions.js';
require_once('includes/class.paginator.php');
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Жалобы'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['date'] = Request::GetString('f_date',METHOD_GET);
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
$filters['user'] = Request::GetInteger('f_user',METHOD_GET);
if(!empty($filters['date'])) {
    $filters['date'] = urldecode($filters['date']);
    $get_parameters['f_date'] = $filters['date'];
}
if(!empty($filters['status'])) {
    $get_parameters['f_status'] = $filters['status'];
}
if(!empty($filters['category'])) {
    $get_parameters['f_category'] = $filters['category'];
}
if(!empty($filters['user'])) {
    $get_parameters['f_user'] = $filters['user'];
}
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];






// обработка action-ов
switch($action){
    case 'categories':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            case 'add':
            case 'edit':
                $module_template = 'admin.categories.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['abuses_categories']);
                    // определяем позицию
                    $row = $db->fetch("SELECT max(position) as position FROM ".$sys_tables['abuses_categories']);
                    if(!empty($row)) $info['position'] = $row['position']+1;
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['abuses_categories']." 
                                        WHERE id=?", $id);
                }
                
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);

                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['categories']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['categories'][$key]['value'])) $info[$key] = $mapping['categories'][$key]['value'];
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['abuses_categories'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['abuses_categories'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/service/abuses/categories/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления, 
                // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['categories']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->query("DELETE FROM ".$sys_tables['abuses_categories']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            case 'up':
                if($action == 'up'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables['abuses_categories']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables['abuses_categories']."
                                SET `position` = `position` + 2
                                WHERE `id` <> ?  AND `position` >= ?";
                        $res = $db->query($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables['abuses_categories']."
                                    SET `position` = ? + 1
                                    WHERE `position` < ?
                                    ORDER BY `position` DESC LIMIT 1";
                            $res = $db->query($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            case 'down':
                if($action == 'down'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables['abuses_categories']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables['abuses_categories']."
                                SET `position` = `position` - 2
                                WHERE `id` <> ?  AND `position` <= ?";
                        $res = $db->query($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables['abuses_categories']."
                                    SET `position` = ? - 1
                                    WHERE `position` > ?
                                    ORDER BY `position` ASC LIMIT 1";
                            $res = $db->query($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            default:
                $module_template = 'admin.categories.list.html';
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['abuses_categories'], 30);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/service/abuses/categories'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT id,title,position FROM ".$sys_tables['abuses_categories'];
                $sql .= " ORDER BY `position`";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
        }
        break;
    /************************\
    |*  Работа с новостями  *|
    \************************/
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $module_template = 'admin.abuses.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['abuses']);
            $info['abuse_date'] = date('d.m.Y H:i');
            $info['content'] = $info['content_short'] = "";
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *, DATE_FORMAT(`abuse_date`,'%d.%m.%Y %H:%i') as abuse_date
                                FROM ".$sys_tables['abuses']." 
                                WHERE id=?", $id);
            // начальное получение списка прилинкованных тегов
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['abuses'][$key])) $mapping['abuses'][$key]['value'] = $info[$key];
        }
        // формирование дополнительных данных для формы (не из основной таблицы)
        $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['abuses_categories']." ORDER BY position");
        foreach($categories as $key=>$val){
            $mapping['abuses']['id_category']['values'][$val['id']] = $val['title'];
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['abuses'][$key])) $mapping['abuses'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['abuses']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['abuses'][$key])) $mapping['abuses'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['abuses'][$key]['value'])) $info[$key] = strip_tags($mapping['abuses'][$key]['value'],'<a><strong><b><a><i><img><ul><li><em><p><div><span><br><h2><h3>');
                }
                //преобразование даты в Mysql-формат
                $info['abuse_date'] = date("Y-m-d H:i:s", strtotime($info['abuse_date']));                
                
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['abuses'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['abuses'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/service/abuses/edit/'.$new_id.'/'));
                            exit(0);
                        }
                    }
                }
                Response::SetBoolean('saved', $res); // результат сохранения
            } else Response::SetBoolean('errors', true); // признак наличия ошибок
        }
        // если мы попали на страницу редактирования путем редиректа с добавления, 
        // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
        $referer = Host::getRefererURL();
        if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
            Response::SetBoolean('form_submit', true);
            Response::SetBoolean('saved', true);
        }
        
        // запись данных для отображения на странице
        Response::SetArray('data_mapping',$mapping['abuses']);
        break;
    default:
        $module_template = 'admin.abuses.list.html';
        //получение списка агентств
        $users = $db->fetchall("SELECT DISTINCT(id_user) as id FROM ".$sys_tables['abuses']);
        foreach($users as $k=>$user) $users_array[] = $user['id'];
        $agencies = $db->fetchall("SELECT 
                                IF(".$sys_tables['users'].".id_agency<2,'Частник', ".$sys_tables['agencies'].".title) as title,
                                IF(".$sys_tables['users'].".id_agency<2,1, ".$sys_tables['users'].".id) as id
                                FROM ".$sys_tables['users']."
                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                WHERE ".$sys_tables['users'].".id IN (".implode(",",$users_array).")
                                GROUP BY id
                                ORDER BY title 
        ");
        Response::SetArray('agencies',$agencies);
        // формирование спискоф для фильтров
        $categories = $db->fetchall("SELECT id, title FROM ".$sys_tables['abuses_categories']." ORDER BY position");
        Response::SetArray('categories',$categories);
        Response::SetArray('statuses',$mapping['abuses']['status']['values']);
        // формирование списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['date'])) $conditions[] = "(`abuse_date` LIKE '".$db->real_escape_string($filters['date'])."%' OR DATE_FORMAT(`abuse_date`, '%d.%m.%Y %H:%i:%s') LIKE '".$db->real_escape_string($filters['date'])."%')";
            if(!empty($filters['status'])) $conditions[] = "`status` = ".$db->real_escape_string($filters['status']);
            if(!empty($filters['category'])) $conditions[] = "`id_category` = ".$db->real_escape_string($filters['category']);
            if($filters['user']==1) { //частники
                $agencies_ids = [];
                foreach($agencies as $k=>$item){
                    if($item['id']!=1) $agencies_ids[] = $item['id'];
                }
                $where = ' AND id_user NOT IN ('.implode(',',$agencies_ids).')';
            } else $where = ' AND id_user = '.$filters['user'];  //статистика агентств        
        }
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['abuses'], 30, $condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/service/abuses'                           // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        $list = $db->fetchall("SELECT 
                                ".$sys_tables['abuses'].".*,
                                IFNULL(".$sys_tables['agencies'].".title,'Частное') as agency_title,
                                ".$sys_tables['abuses_categories'].".title as category_title,
                                ".$sys_tables['managers'].".name as manager,
                                IF(estate_type=1,'Жилая',
                                    IF(estate_type=2,'Новостройки', 
                                        IF(estate_type=3,'Коммерческая', 
                                            IF(estate_type=4,'Загородная', 
                                                IF(estate_type=5, 'ЖК', 
                                                    IF(estate_type=6, 'КП', 
                                                        IF(estate_type=7,'БЦ','')
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ) as estate_type,
                                IF(estate_type=1,'live',
                                    IF(estate_type=2,'build', 
                                        IF(estate_type=3,'commercial', 
                                            IF(estate_type=4,'country', 
                                                IF(estate_type=5, 'zhiloy_kompleks', 
                                                    IF(estate_type=6, 'cottedzhnye_poselki', 
                                                        IF(estate_type=7,'business_centers','')
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ) as estate_url
                                FROM ".$sys_tables['abuses']."
                                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['abuses'].".id_user
                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                LEFT JOIN ".$sys_tables['abuses_categories']." ON ".$sys_tables['abuses_categories'].".id = ".$sys_tables['abuses'].".id_category
                                WHERE ".(!empty($condition)?$condition:" 1 ")."
                                GROUP BY ".$sys_tables['abuses'].".id
                                ORDER BY ".$sys_tables['abuses'].".abuse_date DESC
                                LIMIT ".$paginator->getLimitString($page)
        );

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