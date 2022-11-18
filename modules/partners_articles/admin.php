<?php

$GLOBALS['js_set'][] = '/modules/partners_articles/ajax_actions.js';

require_once('includes/class.paginator.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Статьи от партнеров'));

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['category'])) {
    $get_parameters['f_category'] = $filters['category'];
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
                    $info = $db->prepareNewRecord($sys_tables['partners_articles_categories']);
                    // определяем позицию
                    $row = $db->fetch("SELECT max(position) as position FROM ".$sys_tables['partners_articles_categories']);
                    if(!empty($row)) $info['position'] = $row['position']+1;
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['partners_articles_categories']." 
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
                            $res = $db->updateFromArray($sys_tables['partners_articles_categories'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['partners_articles_categories'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/partners_articles/categories/edit/'.$new_id.'/'));
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
                $res = $db->querys("DELETE FROM ".$sys_tables['partners_articles_categories']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            case 'up':
                if($action == 'up'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables['partners_articles_categories']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables['partners_articles_categories']."
                                SET `position` = `position` + 2
                                WHERE `id` <> ?  AND `position` >= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables['partners_articles_categories']."
                                    SET `position` = ? + 1
                                    WHERE `position` < ?
                                    ORDER BY `position` DESC LIMIT 1";
                            $res = $db->querys($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            case 'down':
                if($action == 'down'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables['partners_articles_categories']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables['partners_articles_categories']."
                                SET `position` = `position` - 2
                                WHERE `id` <> ?  AND `position` <= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables['partners_articles_categories']."
                                    SET `position` = ? - 1
                                    WHERE `position` > ?
                                    ORDER BY `position` ASC LIMIT 1";
                            $res = $db->querys($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            default:
                $module_template = 'admin.categories.list.html';
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['partners_articles_categories'], 30);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = array();
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/partners_articles/categories'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT id,title,position FROM ".$sys_tables['partners_articles_categories'];
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
    case 'add':
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
		
        $module_template = 'admin.partners_articles.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['partners_articles']);
            $info['datetime'] = date('Y-m-d H:i:s');
            $info['content'] = $info['content_short'] = "";
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *
                                FROM ".$sys_tables['partners_articles']." 
                                WHERE id=?", $id);
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['partners_articles'][$key])) $mapping['partners_articles'][$key]['value'] = $info[$key];
        }
        // формирование дополнительных данных для формы (не из основной таблицы)
        $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['partners_articles_categories']." ORDER BY position");
        foreach($categories as $key=>$val){
            $mapping['partners_articles']['id_category']['values'][$val['id']] = $val['title'];
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['partners_articles'][$key])) $mapping['partners_articles'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['partners_articles']);
            //проверка поля url на валидность
            if(empty($errors['code']) && !preg_match('|^[a-zA-Z0-9_\-]+$|s', $mapping['partners_articles']['code']['value']))
                  $errors['code'] = $mapping['partners_articles']['code']['error'] = 'Поле может содержать только латинские буквы, цифры и символы: -_';
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['partners_articles'][$key])) $mapping['partners_articles'][$key]['error'] = $value;
            }
            //проверка поля url на валидность
            if(!preg_match("/^[a-zA-Z\-\_0-9]{1,}$/",$info['code'])) $mapping['partners_articles']['code']['error'] = 'Поле может содержать только латинские буквы, цифры и символы: - _';
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['partners_articles'][$key]['value'])) $info[$key] = $mapping['partners_articles'][$key]['value'];
                }
                
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['partners_articles'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['partners_articles'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/content/partners_articles/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['partners_articles']);
        break;
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $res = $db->querys("DELETE FROM ".$sys_tables['partners_articles']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    default:
        $module_template = 'admin.partners_articles.list.html';
        // формирование спискоф для фильтров
        $categories = $db->fetchall("SELECT id, title FROM ".$sys_tables['partners_articles_categories']." ORDER BY position");
        Response::SetArray('categories',$categories);
        // формирование списка
        $conditions = array();
        if(!empty($filters)){
            if(!empty($filters['title'])) $conditions[] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['category'])) $conditions[] = "`id_category` = ".$db->real_escape_string($filters['category']);
        }
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['partners_articles'], 30, $condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = array();
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/content/partners_articles'                           // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        $sql = "SELECT id,title,datetime FROM ".$sys_tables['partners_articles'];
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY `datetime` DESC, id DESC";
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