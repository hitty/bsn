<?php

$GLOBALS['js_set'][] = '/modules/events_registration/ajax_actions.js';
$GLOBALS['css_set'][] = '/modules/events_registration/styles.css';

require_once('includes/class.paginator.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Регистрации на форуме'));
// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
//страница для паджинатора
$page = Request::GetInteger('page',METHOD_GET);
if ((isset($page))&&($page==0)) Host::Redirect("admin/service/events_registration/?page=1"); 
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
    
    //###########################################################################
    // удаление записи
    //###########################################################################
    case 'del':
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            $res = $db->querys("DELETE FROM ".$sys_tables['events_registration']." WHERE id=?", $id);
            $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
            if($ajax_mode){
                $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                break;
            }
        break;
    //###########################################################################
    // добавление и редактирование записи
    //###########################################################################
    case 'add':
    case 'edit':
		
        if($action=='add'){
            // создание болванки новой записи
            $item = $db->prepareNewRecord($sys_tables['events_registration']);
        }
        else{
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            // получение данных из БД
            $item = $db->fetch("SELECT *
                                FROM ".$sys_tables['events_registration']." 
                                WHERE id=?", $id);
        }
        
        $module_template = 'admin.events_registration.edit.html';
        
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($item as $key=>$field){
            if(!empty($mapping['events_registration'][$key])) $mapping['events_registration'][$key]['value'] = $item[$key];
        }
        
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                //если поле - степень двойки
                if(!empty($mapping['events_registration'][$key]) && $mapping['events_registration'][$key]['fieldtype']=='set') {
                    if(!empty($post_parameters[$key.'_set'])){
                        $mapping['events_registration'][$key]['value'] = 0;
                        foreach($post_parameters[$key.'_set'] as $pkey=>$pval){
                            $mapping['events_registration'][$key]['value'] += pow(2,$pkey-1);
                        }
                        //эта строчка исправляет ошибку, когда при неправильном наборе надо было 
                        //отправить данные два раза
                        $post_parameters['fields'] = $mapping['events_registration'][$key]['value'];
                    }
                    else{
                        //эти строчки исправляют ошибку, когда при неправильно наборе не выдавалось сообщение об ошибке
                        $post_parameters['fields'] = 0;
                        $mapping['events_registration'][$key]['value']=0;
                    }
                }
                //если это обычное поле
                elseif(!empty($mapping['events_registration'][$key])) $mapping['events_registration'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['events_registration']);
            //проверяем, что url правильный
            if (!Validate::isUrl($post_parameters['url'])) $errors['url']='Недопустимое значение URL';
            $post_parameters['url']=strtolower($post_parameters['url']);
            //проверяем, что email правильный
            if (!Validate::isEmail($post_parameters['manager_email'])) $errors['manager_email']='Недопустимое значение Email';
            
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['events_registration'][$key])) $mapping['events_registration'][$key]['error'] = $value;
            }
            
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($item as $key=>$field){
                    if(isset($mapping['events_registration'][$key]['value'])) $item[$key] = $mapping['events_registration'][$key]['value'];
                }
                //$item['answer_datetime']=date("Y-m-d H:i:s");
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['events_registration'], $item, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['events_registration'], $item, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/service/events_registration/edit/'.$new_id.'/'));
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
        
        Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Регистрация на форумы' : $this_page->page_seo_h1);
        // запись данных для отображения на странице
        Response::SetArray('data_mapping',$mapping['events_registration']);
        break;
    //###########################################################################
    // список записей
    //###########################################################################
    default:
        $module_template = 'admin.events_registration.list.html';
        $where = false;
        //фильтр по названию события
        if(!empty($filters['title'])) $where = " `title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['events_registration'], 30, $where);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/service/events_registration'                  // модуль
                                  ."/?"                                       // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)             // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }
        //выбираем страницы для отображения
        $sql = "SELECT 
                        ".$sys_tables['events_registration'].".*,
                        COUNT(".$sys_tables['events_request'].".id) as cnt_requests
                FROM ".$sys_tables['events_registration']."
                LEFT JOIN ".$sys_tables['events_request']." ON ".$sys_tables['events_request'].".id_event = ".$sys_tables['events_registration'].".id";
        if(!empty($where)) $sql.=" WHERE ".$where;
        $sql .= " 
                GROUP BY ".$sys_tables['events_registration'].".id
                ORDER BY ".$sys_tables['events_registration'].".`event_date` DESC, ".$sys_tables['events_registration'].".`id` DESC
                  
        ";
        $sql .= " LIMIT ".$paginator->getLimitString($page);
        $list = $db->fetchall($sql);
        
        // формирование списка
        Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Регистрации на форуме' : $this_page->page_seo_h1);
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