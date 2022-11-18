<?php
/**
INSERT INTO `content`.`partners_articles` ( `id`, `id_category`, `published`, `title`, `content_short`, `content`, `code`) 
SELECT  `id`, `cat`, IF(`actual`='Y',1,2) as `published`,`title`, `anons`, `content`, `url`
FROM bsnweb.artpay

INSERT INTO `content`.`partners_articles_categories` ( `id`, `position`, `title`,`code`) 
SELECT  `id`, `priority`, `title`,`code`
FROM bsnweb.artpay_category
*/

$GLOBALS['js_set'][] = '/modules/diploms/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

// таблицы модуля
$sys_tables = Config::$sys_tables;
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Дипломы'));
$strings_per_page=Config::Get('view_settings/strings_per_page');
// собираем GET-параметры
$get_parameters = [];
$page = Request::GetInteger('page',METHOD_GET);
if ((isset($page))&&($page==0)) Host::Redirect("admin/service/diploms/"); 
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];              
                    
// обработка action-ов
switch($action){
    
    /**************************\
    |*  Работа с фотографиями  *|
    \**************************/
    case 'photos':
        if($ajax_mode){
            $ajax_result['error'] = '';
            // переопределяем экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            
            switch($action){
                case 'list':
                    //получение списка фотографий
                    //id текущего года
                    $id = Request::GetInteger('id', METHOD_POST);
                    if(!empty($id)){
                        $item = Photos::getList('diploms',$id);
                        if(!empty($item)){
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $item;
                            $ajax_result['folder'] = Config::$values['img_folders']['diploms'];
                        } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    //загрузка фотографий
                    //id текущего диплома
                    $id = Request::GetInteger('id', METHOD_POST);                
                    //задаем опции для маленькой и большой картинки
                    Photos::$__folder_options=array(
                            'sm'=>array(100,150,'q',65),
                            'big'=>array(560,800,'q',50)
                            );                 
                    if(!empty($id)){
                        //default sizes removed
                        $res = Photos::Add('diploms',$id,false,false,false,false,false,true);
                        if(!empty($res)){
                            if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                            else {
                                $ajax_result['ok'] = true;
                                $ajax_result['list'] = $res;
                            }
                        } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'setTitle':
                    //добавление названия
                    $id = Request::GetInteger('id_photo', METHOD_POST);                
                    $title = Request::GetString('title', METHOD_POST);                
                    if(!empty($id)){
                        $res = Photos::setTitle('diploms',$id, $title);
                        $ajax_result['last_query'] = '';
                        if(!empty($res)) $ajax_result['ok'] = true;
                        else $ajax_result['error'] = 'Невозможно выполнить обновление названия фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'del':
                    //удаление фото
                    //id фотки
                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                    if(!empty($id_photo)){
                        $res = Photos::Delete('diploms',$id_photo);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;
    //###########################################################################
    // добавление записи
    //###########################################################################
    case 'add':
    //###########################################################################
    // редактирование записи
    //###########################################################################
    case 'edit':
        //$GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
        
        $module_template = 'admin.diploms.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['diploms']);
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *, ".$sys_tables['diploms'].".id
                                FROM ".$sys_tables['diploms']."
                                LEFT JOIN ".$sys_tables['diploms_photos']." 
                                ON ".$sys_tables['diploms'].".id=".$sys_tables['diploms_photos'].".id_parent
                                WHERE ".$sys_tables['diploms'].".id=?", $id);
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['diploms'][$key])) $mapping['diploms'][$key]['value'] = $info[$key];
        }
        
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //если были указаны файлы, загружаем их
            if(!empty($_FILES)){
                foreach ($_FILES as $fname => $data){
                    if ($data['error']==0) {
                        //определение размера загруженного фото
                        $_folder = Host::$root_path.'/'.Config::$values['img_folders']['diploms'].'/';
                        $fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
                        $fileParts = pathinfo($data['name']);
                        $targetExt = $fileParts['extension'];
                        $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                        if (in_array(strtolower($targetExt),$fileTypes)) {
                            $_subfolder = substr($_targetFile,0,2);
                            //при отсутствии каталога он создается
                            if (!file_exists($_folder.$_subfolder)) {
                                mkdir($_folder.$_subfolder);
                            }
                            move_uploaded_file($data['tmp_name'],$_folder.$_subfolder.'/'.$_targetFile);
                            if(file_exists($_folder.$mapping['diploms'][$fname]['value'])) unlink($_folder.$mapping['diploms'][$fname]['value']);
                            $post_parameters[$fname] = $_targetFile;
                            Response::SetString('img_folder', Config::$values['img_folders']['diploms'].'/'.$_subfolder);
                        }
                    }
                }
            }
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['diploms'][$key])) $mapping['diploms'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['diploms']);
            
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['diploms'][$key])) $mapping['diploms'][$key]['error'] = $value;
            }
            
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['diploms'][$key]['value'])) $info[$key] = $mapping['diploms'][$key]['value'];
                }
                
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['diploms'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['diploms'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/service/diploms/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['diploms']);
        break;
    //###########################################################################
    // удаление записи
    //###########################################################################
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $res = $db->querys("DELETE FROM ".$sys_tables['diploms']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    
    default:
        $module_template = 'admin.diploms.list.html';
        $where = false;
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['diploms'], 30, $where);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/content/diploms'                  // модуль
                                  ."/?"                                       // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)             // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }
        //выбираем страницы для отображения
        $sql = "SELECT id,year FROM ".$sys_tables['diploms'];
        if(!empty($where)) $sql.=" WHERE ".$where;
        $sql .= " ORDER BY year DESC";
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