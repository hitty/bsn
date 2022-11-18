<?php

$GLOBALS['js_set'][] = '/modules/changes/ajax_actions.js';
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
/*$GLOBALS['css_set'][] = '/modules/help/styles.css'; */
require_once('includes/class.paginator.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Раздел помощи'));
$strings_per_page=Config::Get('view_settings/strings_per_page');
// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['date'] = Request::GetString('f_date',METHOD_GET);
$filters['project'] = Request::GetString('f_project',METHOD_GET);

if(!empty($filters['title']))$get_parameters['f_title'] = $filters['title'];
if(!empty($filters['date']))$get_parameters['f_date'] = $filters['date'];
if(!empty($filters['project']))$get_parameters['f_project'] = $filters['project'];

$page = Request::GetInteger('page',METHOD_GET);
if ((isset($page))&&($page==0)) Host::Redirect("admin/content/changes/"); 
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
$id_user = $auth->id;
// обработка action-ов
switch($action){
    case 'add':
    case 'edit':
        $module_template = 'admin.changes.edit.html';
        $sql = "SELECT * FROM ".$sys_tables['projects'];
        $sql .= " ORDER BY `id` DESC";
        $projects_list = $db->fetchall($sql,'id');
        foreach($projects_list as $key=>$field){
            $mapping['changes']['id_project']['values'][$field['id']] = $field['title'];
        }
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['projects_changes']);
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *
                                FROM ".$sys_tables['projects_changes']." 
                                WHERE id=?", $id);
            if(empty($info)) Host::Redirect('/admin/content/changes/add/');
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field) if(!empty($mapping['changes'][$key])) $mapping['changes'][$key]['value'] = $info[$key];
        //это не новая статья, то указываем проект
        if ($action=='edit'){
            $project_title = $projects_list[$mapping['changes']['id_project']['value']]['title'];
            Response::SetString('project_title',$project_title);
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            //записываем id пользователя, который работает со статьей
            $mapping['changes']['id_user']['value'] = $id_user;
            if ($action=='edit'){
                $mapping['changes']['datetime_modify']['value']=date('Y-m-d H:i:s');
            }else{
                $mapping['changes']['datetime_create']['value']=date('Y-m-d H:i:s');
                $mapping['changes']['datetime_modify']['value']=$mapping['changes']['datetime_create']['value'];
            }
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['changes'][$key])) $mapping['changes'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['changes']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['changes'][$key])) $mapping['changes'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['changes'][$key]['value'])) $info[$key] = strip_tags($mapping['changes'][$key]['value'],'<a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                }
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['projects_changes'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['projects_changes'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/content/changes/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['changes']);
    break;
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $sql = "DELETE FROM ".$sys_tables['projects_changes']." WHERE `id`=?";
        $res = $db->querys($sql,$id);
        $results['delete'] = ($res && $db->affected_rows)? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    break;
    default:
        $conditions=array($sys_tables['users'].".id=".$id_user);
        if(!empty($filters['title'])) $conditions[] = " ".$sys_tables['projects_changes'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
        if(!empty($filters['date'])) $conditions[] = " ".$sys_tables['projects_changes'].".`datetime_create` LIKE '%".$db->real_escape_string($filters['date'])."%'";
        if(!empty($filters['project'])) $conditions[] = " ".$sys_tables['projects_changes'].".`id_project`=".$db->real_escape_string($filters['project']);
        $condition = implode(' AND ',$conditions);
        $changes_list = $db->fetchall("SELECT ".$sys_tables['projects_changes'].".*,
                                              ".$sys_tables['users'].".name AS author_name,
                                              ".$sys_tables['users'].".email AS author_email,
                                              ".$sys_tables['projects'].".title AS project_title
                                       FROM ".$sys_tables['projects_changes']." 
                                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id=".$sys_tables['projects_changes'].".id_user
                                       LEFT JOIN ".$sys_tables['projects']." ON ".$sys_tables['projects'].".id=".$sys_tables['projects_changes'].".id_project
                                       WHERE ".$condition);
        Response::SetArray('list',$changes_list);
        $projects_list = $db->fetchall("SELECT * FROM ".$sys_tables['projects']);
        Response::SetArray('projects_list',$projects_list);
        $module_template = 'admin.changes.list.html';
    break;
}

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>