<?php

/*
//запрос для импорта тегов из старой базы в новую
insert into content.tags (id, id_category, tag_count, title) select tag_id, tag_category, tag_count, tag_title FROM bsnweb.tags_list
// теги новостей
INSERT INTO content.news_tags (id_tag, id_object)
SELECT bsnweb.tags_links.tag_id as id_tag, bsnweb.tags_links.object_id as id_object FROM bsnweb.`tags_links` 
RIGHT JOIN bsnweb.tags_list ON bsnweb.tags_list.tag_id=bsnweb.tags_links.tag_id
WHERE bsnweb.tags_list.tag_category =1
AND bsnweb.tags_links.tag_id >0 AND bsnweb.tags_links.object_id >0;

// теги статей
INSERT INTO content.articles_tags (id_tag, id_object)
SELECT bsnweb.tags_links.tag_id as id_tag, bsnweb.tags_links.object_id as id_object FROM bsnweb.`tags_links` 
RIGHT JOIN bsnweb.tags_list ON bsnweb.tags_list.tag_id=bsnweb.tags_links.tag_id
WHERE bsnweb.tags_list.tag_category =2
AND bsnweb.tags_links.tag_id >0 AND bsnweb.tags_links.object_id >0;

//теги аналитики
INSERT INTO content.articles_tags (id_tag, id_object)
SELECT bsnweb.tags_links.tag_id as id_tag, bsnweb.tags_links.object_id as id_object FROM bsnweb.`tags_links` 
RIGHT JOIN bsnweb.tags_list ON bsnweb.tags_list.tag_id=bsnweb.tags_links.tag_id
WHERE bsnweb.tags_list.tag_category =3
AND bsnweb.tags_links.tag_id >0 AND bsnweb.tags_links.object_id >0;
  
*/
require_once('includes/class.paginator.php');

// добавление title
$this_page->manageMetadata(array('title'=>'Теги'));
// подключение дополнительных функций
include(dirname(__FILE__).'/admin.functions.php');
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/admin.mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
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
    /*********************************\
    |*  Работа со списком категорий  *|
    \*********************************/
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
                    $info = $db->prepareNewRecord($sys_tables['content_tags_categories']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['content_tags_categories']." 
                                        WHERE id=?", $id);
                    if(empty($info)) Host::Redirect('/admin/content/tags/categories/add/');
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
                            $res = $db->updateFromArray($sys_tables['content_tags_categories'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['content_tags_categories'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/tags/categories/edit/'.$new_id.'/'));
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
                $res = $db->querys("DELETE FROM ".$sys_tables['content_tags_categories']." WHERE id=?", $id);
                if($res && $db->affected_rows) {
                    $result = $db->querys("DELETE FROM ".$sys_tables['content_tags']." WHERE id_category=?", $id);
                }
                $results['delete'] = ($res && !empty($result)) ? $id : -1;
            default:
                $module_template = 'admin.categories.list.html';
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['content_tags_categories'], 30);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/tags/categories'            // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT id,title,code FROM ".$sys_tables['content_tags_categories'];
                $sql .= " ORDER BY `title`";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
        }
        break;
    case 'similar':
        //id выбранного пользователем тега
        $action = empty($this_page->page_parameters[2]) ? 0 : Convert::ToInt($this_page->page_parameters[2]);
        $target_id = Convert::ToInt($action);
        switch(true){
            //если передан id
            case ($ajax_mode == true && $target_id>0):
                if(!empty($target_id)){
                    $notselected_ids = explode(',',preg_replace('/,$/','',Request::GetString('notselected_ids',METHOD_POST)));
                    //удаляем duplicate_entry
                    //ищем объекты, в которых присутствуют как целевой, так и удалямый(е) теги
                    $duplicate_list = $db->fetchall("SELECT id_object FROM ".$sys_tables['news_tags']." WHERE id_tag = ".implode(' OR id_tag = ',$notselected_ids));
                    $where = [];
                    foreach($duplicate_list as $value) $where[] = $value['id_object'];
                    $where = implode(',',$where);
                    //при необходимости удаляем строчки, где у таких объектов стоит целевой тег
                    if(!empty($where))
                        $db->querys("DELETE FROM ".$sys_tables['news_tags']." 
                                    WHERE ".$sys_tables['news_tags'].".id_object IN(".$where.") AND ".$sys_tables['news_tags'].".id_tag =". $target_id);
                    //переназначаем вместо убираемых тегов целевой
                    $db->querys('UPDATE '.$sys_tables['news_tags'].' 
                                SET '.$sys_tables['news_tags'].'.id_tag = '.$target_id.' 
                                WHERE '.$sys_tables['news_tags'].'.id_tag = '.implode(' OR '.$sys_tables['news_tags'].'.id_tag = ',$notselected_ids));
                    //сколько прибавить к tag_count целевого тега
                    $add_to_count = $db->affected_rows;
                    //для убираемых тегов ставим tag_count = 0
                    $db->querys('DELETE FROM '.$sys_tables['content_tags'].' 
                                WHERE '.$sys_tables['content_tags'].'.id = '.implode(' OR '.$sys_tables['content_tags'].'.id = ',$notselected_ids));
                    //целевому тегу устанавливаем корректное значение tag_count
                    $db->querys('UPDATE '.$sys_tables['content_tags'].'
                                SET '.$sys_tables['content_tags'].'.tag_count = '.$sys_tables['content_tags'].'.tag_count + ? WHERE id = ?',$add_to_count,$target_id);
                }
                $ajax_result['ok'] = true;
                break;
            case ($ajax_mode == true && $action == 'remove'):
                $target_id = empty($this_page->page_parameters[3]) ? 0 : Convert::ToInt($this_page->page_parameters[3]);
                //для тега, который удалили из группы похожих, удаляем для него id_similar и для тех, кто похож на него тоже (так как группа похожести может быть только одна, это корректно)
                $res = $db->querys("UPDATE ".$sys_tables['content_tags']." SET id_similar = 0 AND difference_level = 0 WHERE id = ? OR id_similar = ?",$target_id,$target_id);
                $ajax_result['ok'] = $res;
                break;
            case ($ajax_mode == false):
                //читаем группы похожих тегов (группа - список id, список имен, список tag_count)
                // формирование списка
                $conditions = [];
                if(!empty($filters)){
                    if(!empty($filters['title'])) $conditions[] = $sys_tables['content_tags'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                    if(!empty($filters['category'])) $conditions[] = "`id_category` = ".$db->real_escape_string($filters['category']);
                }
                if(!empty($conditions)) $condition = implode(' AND ',$conditions);
                else $condition = '';
                $sql_select = "SELECT CONCAT(example_id,',',GROUP_CONCAT(tags_id)) AS similar_ids,
                               CONCAT(similar_title,',',GROUP_CONCAT(tags_title)) AS similars,
                               CONCAT(similars_count,',',GROUP_CONCAT(tags_count)) AS counts";
                $sql_condition = "
                        FROM
                        (SELECT ".$sys_tables['content_tags'].".id AS tags_id,
                                ".$sys_tables['content_tags'].".title AS tags_title,
                                ".$sys_tables['content_tags'].".tag_count AS tags_count,
                                similar.id AS example_id,
                                similar.title AS similar_title,
                                similar.tag_count AS similars_count
                        FROM ".$sys_tables['content_tags']."
                        INNER JOIN ".$sys_tables['content_tags']." as similar ON similar.id = ".$sys_tables['content_tags'].".id_similar
                        WHERE ".$sys_tables['content_tags'].".tag_count > 0 AND similar.tag_count>0
                        GROUP BY ".$sys_tables['content_tags'].".id) as a";
                // создаем пагинатор для списка
                $paginator = new Paginator(false, 30, false,"SELECT COUNT(*) as items_count ".$sql_condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/tags/similar'                // модуль
                                          ."/?"                                         // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)               // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page=";   // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
                $sql = $sql_select . $sql_condition . "  GROUP BY example_id LIMIT ".$paginator->getLimitString($page);
                $list_similars = $db->fetchall($sql);
                
                foreach($list_similars as $key=>$similars_group){
                    $ids = explode(',',$similars_group['similar_ids']);
                    $titles = explode(',',$similars_group['similars']);
                    $counts = explode(',',$similars_group['counts']);
                    foreach($ids as $group_counter=>$tag_id){
                        $list[$key][] = array('id'=>$tag_id,'title'=>$titles[$group_counter],'count'=>$counts[$group_counter]);
                    }
                }
                unset($list_similars);
                if(!empty($list)) Response::SetArray('list',$list);
                Response::SetArray('paginator', $paginator->Get($page));
                $module_template = 'admin.similars.list.html';
            break;
        }
        break;
    /************************\
    |*  Работа с тегами     *|
    \************************/
    case 'add':
    case 'edit':
        $module_template = 'admin.tags.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['content_tags']);
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *
                                FROM ".$sys_tables['content_tags']." 
                                WHERE id=?", $id);
            if(empty($info)) Host::Redirect('/admin/content/tags/add/');
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['tags'][$key])) $mapping['tags'][$key]['value'] = $info[$key];
        }
        // формирование дополнительных данных для формы (не из основной таблицы)
        $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['content_tags_categories']." ORDER BY title");
        foreach($categories as $key=>$val){
            $mapping['tags']['id_category']['values'][$val['id']] = $val['title'];
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['tags'][$key])) $mapping['tags'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['tags']);
             // дубликаты категорий для одинаковых названий тегов
            if(empty($errors)) {
                $res = $db->fetch("SELECT id FROM ".$sys_tables['content_tags']." WHERE title=? AND id_category=? AND id<>?", $mapping['tags']['title']['value'], $mapping['tags']['id_category']['value'],$id);  
                if(!empty($res)) $errors['title'] = $mapping['tags']['title']['error'] = 'Такой тег уже есть в списке тегов для выбранной категории'; 
            }
            
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['tags'][$key])) $mapping['tags'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['tags'][$key]['value'])) $info[$key] = $mapping['tags'][$key]['value'];
                }
                
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['content_tags'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['content_tags'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/content/tags/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['tags']);
        break;
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $res = $db->querys("DELETE FROM ".$sys_tables['content_tags']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if(!empty($res)){
            deleteTagLinks($id);
        }
    default:
        $module_template = 'admin.tags.list.html';
        // формирование списков для фильтров
        $categories = $db->fetchall("SELECT id, title FROM ".$sys_tables['content_tags_categories']." ORDER BY title");
        Response::SetArray('categories',$categories);
        // формирование списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['title'])) $conditions[] = $sys_tables['content_tags'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['category'])) $conditions[] = "`id_category` = ".$db->real_escape_string($filters['category']);
        }
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['content_tags'], 30, $condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/content/tags'                           // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        $sql = "SELECT 
                    ".$sys_tables['content_tags'].".id,
                    ".$sys_tables['content_tags'].".title,
                    ".$sys_tables['content_tags'].".tag_count, 
                    ".$sys_tables['content_tags_categories'].".title as category_title 
                FROM ".$sys_tables['content_tags']." 
                LEFT JOIN ".$sys_tables['content_tags_categories']." ON ".$sys_tables['content_tags_categories'].".id = ".$sys_tables['content_tags'].".id_category";
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY `id` DESC";
        $sql .= " LIMIT ".$paginator->getLimitString($page); 
        $list = $db->fetchall($sql,'id');
        /*
        //читаем группы похожих тегов (группа - список id и список имен)
        $sql = "SELECT CONCAT(example_id,',',GROUP_CONCAT(tags_id)) AS similar_ids,
                       CONCAT(similar_title,',',GROUP_CONCAT(tags_title)) AS similars,
                       CONCAT(similars_count,',',GROUP_CONCAT(tags_count)) AS counts
                FROM
                (SELECT ".$sys_tables['content_tags'].".id AS tags_id,
                        ".$sys_tables['content_tags'].".title AS tags_title,
                        ".$sys_tables['content_tags'].".tag_count AS tags_count,
                        similar.id AS example_id,
                        similar.title AS similar_title,
                        similar.tag_count AS similars_count
                FROM ".$sys_tables['content_tags']."
                INNER JOIN ".$sys_tables['content_tags']." as similar ON similar.id = ".$sys_tables['content_tags'].".id_similar
                GROUP BY ".$sys_tables['content_tags'].".id) as a GROUP BY example_id";
        $list_similars = $db->fetchall($sql);
        foreach($list as $tag_id=>$value){
            //если тегу не назначена группа похожих, ищем ее (такая группа может быть только одна)
            if(empty($list[$tag_id]['similars'])){
                foreach($list_similars as $similars_group){
                    $ids = explode(',',$similars_group['similar_ids']);
                    //если нашли его id в группе похожих
                    if(in_array($tag_id,$ids)){
                        //список имен тегов группы
                        $titles = explode(',',$similars_group['similars']);
                        //список tag_count тегов группы
                        $counts = explode(',',$similars_group['counts']);
                        //для всех тегов из этой группы (в.т.ч. исходного) формируем список похожих (название и tag_count)
                        foreach($ids as $similar_key){
                            foreach($ids as $group_key=>$k){
                                if($similar_key != $k && !empty($list[$similar_key]))
                                    $list[$similar_key]['similars'][] = array($k,$titles[$group_key],$counts[$group_key]);
                            }
                        }
                        break;
                    }
                }
            }
        }
        */
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