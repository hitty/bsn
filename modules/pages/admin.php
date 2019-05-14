<?php
$GLOBALS['js_set'][] = '/modules/pages/ajax_actions.js';
// добавление title
$this_page->manageMetadata(array('title'=>'Страницы'));
// основной шаблон модуля (шаблон по умолчанию)
$module_template = 'admin.list.html';
// подключение дополнительных функций
include(dirname(__FILE__).'/admin.functions.php');
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/admin.mapping.php');

// инициализация состояния дерева-списка страниц (
if(empty($module_settings['pages_list_state'])) $module_settings['pages_list_state'] = [];



$ajax_mode = $ajax_mode && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';


// собираем GET-параметры
$get_parameters = [];


$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;


// обработка action-ов
if(!empty($this_page->page_parameters[1])){
    // ID из url
    $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
    switch($this_page->page_parameters[1]){
        case 'add':
        case 'edit':
            $module_template = 'admin.edit.html';
            if($this_page->page_parameters[1]=='add'){
                // создание болванки новой записи
                $info = $db->prepareNewRecord($sys_tables['pages']);
                $info['map_id'] = 0;
                $info['map_parent_id'] = 0;
                $info['map_path'] = '';
            } else {
                // получение данных из БД
                $info = $db->fetch("SELECT a.*,b.id as map_id, b.parent_id as map_parent_id, b.path as map_path
                                    FROM ".$sys_tables['pages']." a 
                                    LEFT JOIN ".$sys_tables['pages']."_map b ON a.id=b.object_id 
                                    WHERE a.id=?", $id);
                if(empty($info)) Host::Redirect('/admin/pages/add/');
            }
            // перенос дефолтных (считанных) значений в мэппинг
            foreach($info as $key=>$field){
                if(!empty($mapping['pages'][$key])) $mapping['pages'][$key]['value'] = $info[$key];
            }
            // получение данных, отправленных из формы
            $post_parameters = Request::GetParameters(METHOD_POST);
            
            // формирование списка структуры страниц, визуализированного в виде дерева (для выбора предка страницы)
            $full_pages_list = make_pages_tree(0,'all',$id);
            $stack=[];
            foreach($full_pages_list as $key=>$val){
                $stack[$val['level']] = !empty($val['last']);
                $tree_prefix = "";
                for($i=0;$i<$val['level'];$i++){
                    if(empty($stack[$i])) $tree_prefix .= "&nbsp;&#9475;";
                    else $tree_prefix .= "&nbsp;&nbsp;";
                }
                $mapping['pages']['map_position']['values'][$val['map_id']] = $tree_prefix."&nbsp;".(!empty($val['last'])?"&#9495;&nbsp;":"&#9507;&nbsp;").$val['title'];
            }
            $mapping['pages']['map_position']['value'] = $info['map_parent_id']; // текущее значение предка
            
            // если была отправка формы - начинаем обработку
            if(!empty($post_parameters['submit'])){
                Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                // перенос значений в мэппинг для последующего отображения
                foreach($post_parameters as $key=>$field){
                    if(!empty($mapping['pages'][$key])) $mapping['pages'][$key]['value'] = $post_parameters[$key];
                }
                // корректировка значений
                if($this_page->page_parameters[1]=='edit'){
                    $old_url = $mapping['pages']['url']['value'];
                    $parent_url = get_parent_url($mapping['pages']['map_position']['value']);
                    $mapping['pages']['url']['value'] = (empty($parent_url) ? '' : $parent_url.'/').$mapping['pages']['alias']['value'];
                    // если изменился url, то пересчитать его для всех других страниц
                    if($mapping['pages']['url']['value']!==$old_url){
                        $db->query("
                            UPDATE ".$sys_tables['pages']." SET url = REPLACE(url, ?, ?)
                            WHERE url LIKE ?"
                            , $old_url
                            , $mapping['pages']['url']['value']
                            , $old_url.'/%');
                    }
                    // если изменилось расположение страницы - перемещаем страницу (только для режима редактирования)
                    if($info['map_parent_id'] != $mapping['pages']['map_position']['value']){
                        if(!move_page($id,$mapping['pages']['map_position']['value'])) $errors['map_position'] = 'Не возможно переместить страницу';
                    }
                } else {
                    $parent_url = get_parent_url($mapping['pages']['map_position']['value']);
                    $mapping['pages']['url']['value'] = (empty($parent_url) ? '' : $parent_url.'/').$mapping['pages']['alias']['value'];
                }                
                // проверка значений из формы
                $errors = Validate::validateParams($post_parameters,$mapping['pages']);
                // выписывание ошибок в форму
                foreach($errors as $key=>$value){
                    if(!empty($mapping['pages'][$key])) $mapping['pages'][$key]['error'] = $value;
                }
                if(empty($errors)) {
                    // подготовка значений для сохранения
                    foreach($info as $key=>$field){
                        if(isset($mapping['pages'][$key]['value'])) $info[$key] = $mapping['pages'][$key]['value'];
                    }
                    // сохранение в БД
                    if($this_page->page_parameters[1]=='edit'){
                        $res = $db->updateFromArray($sys_tables['pages'], $info, 'id');
                    } else {
                        $res = $db->insertFromArray($sys_tables['pages'], $info, 'id');
                        if(!empty($res)){
                            $new_id = $db->insert_id;
                            // добавить страницу в карту 
                            $res = add_page_in_map($new_id, $mapping['pages']['map_position']['value']);
                            // редирект на редактирование свеженькой страницы
                            if(!empty($res)) {
                                header('Location: '.Host::getWebPath('/admin/pages/edit/'.$new_id.'/'));
                                exit(0);
                            }
                        }
                    }
                    Response::SetBoolean('saved', $res); // результат сохранения
                } else Response::SetBoolean('errors', true); // признак наличия ошибок
            }
            // запись данных для отображения на странице
            Response::SetArray('data_mapping',$mapping['pages']);
            break;
        case 'del':
            // если элемент был среди открытых, убираем его состояние
            $key = array_search($id,$module_settings['pages_list_state']);
            if($key!==false) unset($module_settings['pages_list_state'][$key]);
            $deleted_ids = delete_page($id);
            if($ajax_mode){
                $ajax_result = array('ok' => !empty($deleted_ids), 'ids'=>$deleted_ids);
            }
            break;
        case 'expand':
            // поиск состояния и его инверсия
            $key = array_search($id,$module_settings['pages_list_state']);
            if($key===false) $module_settings['pages_list_state'][] = $id;
            else unset($module_settings['pages_list_state'][$key]);
            break;
    }
}


// формирование дерева
$list = make_pages_tree(0, $module_settings['pages_list_state']);
Response::SetArray('list', $list);

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));

?>