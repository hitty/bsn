<?php

$GLOBALS['js_set'][] = '/modules/help/ajax_actions.js';
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
$filters['state'] = Request::GetString('f_state',METHOD_GET);
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['category'] = Request::GetString('f_category',METHOD_GET);

if(!empty($filters['state']))$get_parameters['f_state'] = $filters['state'];
if(!empty($filters['title']))$get_parameters['f_title'] = $filters['title'];
if(!empty($filters['category']))$get_parameters['f_category'] = $filters['category'];

$page = Request::GetInteger('page',METHOD_GET);
if ((isset($page))&&($page==0)) Host::Redirect("admin/content/help/"); 
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
    case 'add':
    case 'edit':
        //добавление/редактирование статей
        $module_template = 'admin.help.articles.edit.html';      
        $sql = "SELECT id,title,description,published FROM ".$sys_tables['help_categories'];
        $sql .= " ORDER BY `id` DESC";
        $list = $db->fetchall($sql);
        foreach($list as $key=>$field){
            $mapping['articles']['id_category']['values'][$field['id']] = $field['title'];
        }
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['help_articles']);
       //     $info['published'] = 1;
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *
                                FROM ".$sys_tables['help_articles']." 
                                WHERE id=?", $id);
            if(empty($info)) Host::Redirect('/admin/content/help/edit/');
        }
        
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field) if(!empty($mapping['articles'][$key])) $mapping['articles'][$key]['value'] = $info[$key];
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['articles'][$key])) $mapping['articles'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['articles']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['articles'][$key])) $mapping['articles'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['articles'][$key]['value'])) $info[$key] = strip_tags($mapping['articles'][$key]['value'],'<a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                }
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['help_articles'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['help_articles'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        $db->querys("UPDATE ".$sys_tables['help_articles']." SET chpu_title = '".$new_id."_".createCHPUTitle($info['title'])."' WHERE id=".$new_id);
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/content/help/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['articles']); 
    break;
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $res = $db->querys("DELETE FROM ".$sys_tables['help_articles']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    break;
    case 'up':
        if($action == 'up'){
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            $item = $db->fetch("SELECT position FROM ".$sys_tables['help_articles']." WHERE id=?",$id);
            if(empty($item)) $results['move'] = -1;
            else {
                $sql = "UPDATE ".$sys_tables['help_articles']."
                        SET `position` = `position` + 2
                        WHERE `id` <> ?  AND `position` >= ?";
                $res = $db->querys($sql, $id, $item['position']);
                if(empty($res)) $results['move'] = -1;
                else {
                    $sql = "UPDATE ".$sys_tables['help_articles']."
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
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            $item = $db->fetch("SELECT position FROM ".$sys_tables['help_articles']." WHERE id=?",$id);
            if(empty($item)) $results['move'] = -1;
            else {
                $sql = "UPDATE ".$sys_tables['help_articles']."
                        SET `position` = `position` - 2
                        WHERE `id` <> ?  AND `position` <= ?";
                $res = $db->querys($sql, $id, $item['position']);
                if(empty($res)) $results['move'] = -1;
                else {
                    $sql = "UPDATE ".$sys_tables['help_articles']."
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
        $module_template = 'admin.articles.list.html';
        
        if (!empty($this_page->page_parameters[2]) && $this_page->page_parameters[2]=='del'){
            $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
            $sql = "DELETE FROM ".$sys_tables['help_articles']." WHERE `id`=?";
            $res = $db->querys($sql,$id);
            $results['delete'] = ($res && $db->affected_rows)? $id : -1;              
            Response::SetArray('results',$results);
        }
        
        $where = [];
        
        if(!empty($filters['title'])){
             $where[] = "`ha`.title LIKE '".$filters['title']."%'";        
        }    
        
        if(!empty($filters['category']) && ($filters['category']!=0))
             $where[] = "`hc`.id = ".$filters['category'];
             
        if(!empty($filters['state']) && ($filters['state']!=0))
             $where[] = "`ha`.published = ".$filters['state'];        
             
             
        $sql = "SELECT `ha`.`id`,ha.title,MID(ha.text,1,150) as text,ha.published, ha.useful, ha.useless,hc.id AS category_id,hc.title AS category_name 
            FROM ".$sys_tables['help_articles']." ha LEFT JOIN ".$sys_tables['help_categories']." hc
            ON hc.`id`=ha.id_category";
        if(!empty($where)) $sql.=" WHERE ".implode(' AND ',$where);
        $sql .= " ORDER BY ha.position";
        $list = $db->fetchall($sql);
        
        // Отображение в фильтре по категориям только тех категорий, в которых есть статьи
        $allcategories = $categories = [];
        $sql = "SELECT  ".$sys_tables['help_categories'].".id,
                        ".$sys_tables['help_categories'].".title,
                        ".$sys_tables['help_categories'].".description,
                        ".$sys_tables['help_categories'].".published,
                        count(".$sys_tables['help_articles'].".id) as articles_cnt 
                        FROM ".$sys_tables['help_articles']." 
                        LEFT JOIN ".$sys_tables['help_categories']." 
                        ON (".$sys_tables['help_articles'].".id_category = ".$sys_tables['help_categories'].".id) 
                        GROUP BY id_category";                        
        $allcategories = $db->fetchall($sql);
        foreach ($allcategories as $key => $value)
            if ($value['articles_cnt']>0) $categories[] = $value;
            
        Response::SetArray('categories', $categories);
        
        $states = array('1' => 'опубликованные','2' => 'не опубликованные');
        // формирование списка
        $h1='Статьи помощи';
        Response::SetArray('states', $states);
        Response::SetString('h1', empty($this_page->page_seo_h1) ? $h1 : $this_page->page_seo_h1);
        Response::SetArray('list', $list);
    break;
    case 'categories':
        $action_categories = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch ($action_categories){
            case 'photo':
                if($ajax_mode){
                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $photo_action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                    Photos::$__folder_options = array('sm'=>array(102,102,'cut',80));
                    switch($photo_action){
                        case 'list':
                            //получение списка фотографий
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            if(!empty($id)){
                                $list = Photos::getList('help_categories',$id);
                                if(!empty($list)){
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $list;
                                    $ajax_result['folder'] = Config::$values['img_folders']['help_categories'];
                                } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'add':
                            $id = Request::GetInteger('id', METHOD_POST);                
                            if(!empty($id)){
                                $res = Photos::Add('help_categories',$id,false,false,false,30,30,true);
                                if(!empty($res)){
                                    if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                    else {
                                        if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                        else {
                                            $ajax_result['ok'] = true;
                                            $ajax_result['list'] = $res;
                                        }
                                    }
                                } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                        case 'del':
                            //удаление фото
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::Delete('help_categories',$id_photo);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                    }
                }
            break;
            case 'add':
            case 'edit':
                $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                $module_template = 'admin.help.categories.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action_categories=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['help_categories']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['help_categories']." 
                                        WHERE id=?", $id);
                    if(empty($info)) Host::Redirect('/admin/content/help/categories/add/');
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field) if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $info[$key];
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
                            if(isset($mapping['categories'][$key]['value'])) $info[$key] = strip_tags($mapping['categories'][$key]['value'],'<a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                        }
                        // сохранение в БД
                        if($action_categories=='edit'){
                            $res = $db->updateFromArray($sys_tables['help_categories'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['help_categories'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                $db->querys("UPDATE ".$sys_tables['help_categories']." SET chpu_title = '".$new_id."_".createCHPUTitle($info['title'])."' WHERE id=".$new_id);
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/help/categories/edit/'.$new_id.'/'));
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
                if($action_categories=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['categories']);
            break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $del_photos = Photos::DeleteAll('help_categories',$id);
                $res = $db->querys("DELETE FROM ".$sys_tables['help_articles']." WHERE id_category=?", $id);
                $res = $db->querys("DELETE FROM ".$sys_tables['help_categories']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            break;
            case 'up':
                if($action_categories == 'up'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables['help_categories']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables['help_categories']."
                                SET `position` = `position` + 2
                                WHERE `id` <> ?  AND `position` >= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables['help_categories']."
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
                if($action_categories == 'down'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables['help_categories']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables['help_categories']."
                                SET `position` = `position` - 2
                                WHERE `id` <> ?  AND `position` <= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables['help_categories']."
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
                
                $where = false;
                if(!empty($filters['state'])) $where = " `published`=".Convert::ToInt($filters['state']);
                if (!empty($this_page->page_parameters[2]) && $this_page->page_parameters[2]=='del'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $sql = "SELECT * FROM ".$sys_tables['help_articles']." WHERE `category_id`=".$id;
                    $res = $db->fetchall($sql);
                    if (count($res)>0){
                        $results['delete'] = -2;
                    } else {
                        $sql = "DELETE FROM ".$sys_tables['help_categories']." WHERE `id`=?";
                        $res = $db->querys($sql,$id);
                        $results['delete'] = ($res && $db->affected_rows)? $id : -1;              
                    }
                    Response::SetArray('results',$results);
                }
                
                
                $sql = "SELECT id,title,description,published FROM ".$sys_tables['help_categories'];
                if(!empty($where)) $sql.=" WHERE ".$where;
                $sql .= " ORDER BY position";
                $list = $db->fetchall($sql);
                $states = array('1' => 'опубликованные','2' => 'не опубликованные');
                
                $help_categories_photo_folder = Config::$values['img_folders']['help_categories'];
                foreach($list as $key=>$value){
                    $photo = Photos::getMainPhoto('help_categories',$value['id']);
                    if(!empty($photo)) {
                        $list[$key]['photo'] = $help_categories_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
                    }
                }
                
                // формирование списка
                $h1='Категории статей помощи';
                Response::SetString('h1', empty($this_page->page_seo_h1) ? $h1 : $this_page->page_seo_h1);
                Response::SetArray('list', $list);
                Response::SetArray('states', $states);
            break;
        }      
    break;
}

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
//foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk."=".$gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>