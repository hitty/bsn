<?php
// мэппинги модуля

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
switch(true){
    case $action=='print':
        $id = !empty($this_page->page_parameters[1])?Convert::ToInteger($this_page->page_parameters[1]):false;
        if($id){
            $item = $db->fetch("SELECT * FROM ".$sys_tables['service_receipt']." WHERE `id` = ?",$id);
            if(!empty($item)){
                Response::SetArray('item',$item);
            }  else $this_page->http_code = 404;  
        } else $this_page->http_code = 404;
        $module_template = 'print.html';
        $this_page->page_template = 'templates/clearcontent.html';
        break;
    
    default:
        if(empty($action)){
            $GLOBALS['css_set'][] = '/css/form.css';

            // получение данных, отправленных из формы
            $post_parameters = Request::GetParameters(METHOD_POST);
            // если была отправка формы - начинаем обработку
            if(!empty($post_parameters['submit'])){
                Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                foreach($post_parameters as $key=>$field){
                    if(!empty($mapping['receipt'][$key])) $mapping['receipt'][$key]['value'] = $post_parameters[$key];
                }
                // проверка значений из формы
                $errors = Validate::validateParams($post_parameters,$mapping['receipt']);
                // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                foreach($errors as $key=>$value){
                    if(!empty($mapping['receipt'][$key])) $mapping['receipt'][$key]['error'] = $value;
                }
                // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                if(empty($errors)) {
                    // подготовка всех значений для сохранения
                    foreach($mapping['receipt'] as $key=>$field){
                        if(!empty($mapping['receipt'][$key]['value'])) $info[$key] = $mapping['receipt'][$key]['value'];
                    }
                    $res = $db->insertFromArray($sys_tables['service_receipt'], $info, 'id');
                    header('Location: '.Host::getWebPath('/receipt/print/'.$db->insert_id.'/'));                
                } else Response::SetBoolean('errors', true); // признак наличия ошибок            
            }
            // запись данных для отображения на странице
            Response::SetArray('data_mapping',$mapping['receipt']);
            $module_template = 'mainpage.html';
        } else $this_page->http_code = 404;
        break;
}
?>