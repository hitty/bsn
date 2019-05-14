<?php

$GLOBALS['js_set'][] = '/modules/guestbook/ajax_actions.js';
$GLOBALS['css_set'][] = '/modules/guestbook/styles.css';

require_once('includes/class.paginator.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Гостевая книга'));
$strings_per_page=Config::Get('view_settings/strings_per_page');
// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['question'] = Request::GetString('f_question',METHOD_GET);
$filters['status'] = Request::GetString('f_status',METHOD_GET);
if(!empty($filters['question'])) {
    $filters['question'] = urldecode($filters['question']);
    $get_parameters['f_question'] = $filters['question'];
}
if(!empty($filters['status'])) {
    $get_parameters['f_status'] = $filters['status'];
}
$page = Request::GetInteger('page',METHOD_GET);
if ((isset($page))&&($page==0)) Host::Redirect("admin/content/guestbook/"); 
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
    //###########################################################################
    // редактирование записи
    //###########################################################################
    case 'del':
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            $res = $db->query("DELETE FROM ".$sys_tables['guestbook']." WHERE id=?", $id);
            $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                break;
            }
        break;
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
		
        $module_template = 'admin.guestbook.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        // получение данных из БД
        $info = $db->fetch("SELECT *
                            FROM ".$sys_tables['guestbook']." 
                            WHERE id=?", $id);
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['guestbook'][$key])) $mapping['guestbook'][$key]['value'] = $info[$key];
        }
        
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['guestbook'][$key])) $mapping['guestbook'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['guestbook']);
            
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['guestbook'][$key])) $mapping['guestbook'][$key]['error'] = $value;
            }
            
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['guestbook'][$key]['value'])) $info[$key] = $mapping['guestbook'][$key]['value'];
                }
                $info['answer_datetime']=date("Y-m-d H:i:s");
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['guestbook'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['guestbook'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/content/guestbook/edit/'.$new_id.'/'));
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
        
        $h1='Гостевая книга';
        Response::SetString('h1', empty($this_page->page_seo_h1) ? $h1 : $this_page->page_seo_h1);
        // запись данных для отображения на странице
        Response::SetArray('data_mapping',$mapping['guestbook']);
        break;
    default:
        $module_template = 'admin.guestbook.list.html';
        $where = false;
        if(!empty($filters['question'])) $where = " `question` LIKE '%".$db->real_escape_string($filters['question'])."%'";
        if(!empty($filters['status'])){
            if (!empty($where)) $where .= " AND published=".$db->real_escape_string($filters['status'])." ";
            else $where = " published=".$db->real_escape_string($filters['status'])." ";
        } 
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['guestbook'], 30, $where);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/content/guestbook'                  // модуль
                                  ."/?"                                       // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)             // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }
        //выбираем страницы для отображения
        $sql = "SELECT id,question,answer,name,published FROM ".$sys_tables['guestbook'];
        if(!empty($where)) $sql.=" WHERE ".$where;
        $sql .= " ORDER BY `published` DESC,`id` DESC";
        $sql .= " LIMIT ".$paginator->getLimitString($page);
        $list = $db->fetchall($sql);
        
        // формирование списка
        $h1='Гостевая книга';
        Response::SetString('h1', empty($this_page->page_seo_h1) ? $h1 : $this_page->page_seo_h1);
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