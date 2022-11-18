<?php

/*
//запрос для импорта статей из старой базы в новую
INSERT INTO `content`.`comments` ( `id`, `id_parent`, `comments_active`, `comments_isnew`, `comments_datetime`, `author_name`, `objects_type`, `comments_text`) 
SELECT `comments_id`, `object_id`, `comments_active`, `comments_isnew`, `comments_datetime`, `author_name`, `parent_type`, `comments_text`
FROM bsnweb.comments
*/
require_once('includes/class.content.php');
require_once('includes/class.opinions.php');
$GLOBALS['js_set'][] = '/modules/comments/ajax_actions.js';

require_once('includes/class.paginator.php');

$sys_tables = Config::$sys_tables;
// добавление title
$this_page->manageMetadata(array('title'=>'Модерация комментариев'));
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/admin.mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['status'] = Request::GetString('f_status',METHOD_GET);
$filters['parent_type'] = Request::GetString('f_parent_type',METHOD_GET);
if(!empty($filters['status'])) {
    $get_parameters['f_status'] = $filters['status'];
} else $filters['status'] = 'no-modereated';
if(!empty($filters['parent_type'])) {
    $get_parameters['f_parent_type'] = $filters['parent_type'];
} else $filters['parent_type'] = '';
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];







// обработка action-ов
switch($action){
    case 'publish':
        $id = Request::GetInteger('id', METHOD_POST);
        if(!empty($id)) {
            $db->querys("UPDATE ".$sys_tables['comments']." SET comments_active=1, comments_isnew=2 WHERE id=?",$id);
            $ajax_result['ok'] = true;
        }
        break;
    case 'edit':
        $module_template = 'admin.comments.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        // получение данных из БД
        $info = $db->fetch("SELECT *
                            FROM ".$sys_tables['comments']." 
                            WHERE id=?", $id) ;
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['comments'][$key])) $mapping['comments'][$key]['value'] = $info[$key];
        }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);
        $parent_info = Comments::getInfo($info,true);
        Response::SetArray('parent_object',$parent_info);
        
        //комментарии
        Comments::Init($parent_info['type'], $info['id_parent']);
        //сортировка
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        Response::SetInteger('sortby', $sortby);
        Response::SetString('only_comments', true);
        $orderby = $sys_tables['comments'].".comments_datetime ASC";
        //получение списка последних комментариев
        $comments_list = Comments::getLastComments(null, false, $orderby);
        //формирование html-вида комментариев
        if(!empty($comments_list)){
            Response::SetArray('comments_list',$comments_list);
            Response::SetInteger('total',count($comments_list));
        }
        
        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['comments'][$key])) $mapping['comments'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['comments']);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['comments'][$key])) $mapping['comments'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['comments'][$key]['value'])) $info[$key] = $mapping['comments'][$key]['value'];
                }
                // сохранение в БД
                $res = $db->updateFromArray($sys_tables['comments'], $info, 'id') or die($db->error);
                Response::SetBoolean('saved', $res); // результат сохранения
            } else Response::SetBoolean('errors', true); // признак наличия ошибок
        }
        // запись данных для отображения на странице
        Response::SetArray('data_mapping',$mapping['comments']);
        break;
    case 'del':
        
        $selected_ids = Request::GetArray('selected_ids',METHOD_POST);
        $id = !empty($selected_ids) ? implode(',',$selected_ids) : (empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2]);
        $res = $db->querys("DELETE FROM ".$sys_tables['comments']." WHERE id IN ($id)");
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>!empty($selected_ids)?$selected_ids:array($id));
            break;
        }
    default:
        $module_template = 'admin.comments.list.html';
        $statuses = array('no-modereated'=>'Немодерированные','all'=>'Все комментарии','moderated'=>'Модерированные');
        Response::SetArray('statuses',$statuses);
        Response::SetArray('parent_types',$mapping['comments']['parent_type']['values']);
        // формирование списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['parent_type'])) $conditions['parent_type'] = "`parent_type` = '".$db->real_escape_string($filters['parent_type'])."'";
            switch($filters['status']){
            case 'no-modereated':
                    $conditions['status'] = "`comments_isnew` = 1";
                    break;
                case 'all':
                    $conditions['status'] = "`comments_isnew` IS NOT NULL";
                    break;
                case 'moderated':
                    $conditions['status'] = "`comments_isnew` = 2";
                    break;
            
            }
        }
        $condition = implode(" AND ",$conditions);        
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['comments'], 30, $condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/content/comments'                  // модуль
                                  ."/?"                                       // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)             // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        $sql = "SELECT *,
                        CONCAT(
                            IF(DATE(comments_datetime) = CURDATE(), 'сегодня',
                                IF(DATE(comments_datetime) = CURDATE() - INTERVAL 1 day , 'вчера',
                                    IF(DATE(comments_datetime) = CURDATE() - INTERVAL 2 day , '2 дня назад', DATE_FORMAT(comments_datetime,'%e %M'))
                                )
                            ), 
                            ' в ',
                            DATE_FORMAT(comments_datetime,'%k:%i') 
                        ) as normal_datetime,

                          IF(parent_type=1,'Новости',
                            IF(parent_type=4,'Мнения',
                                IF(parent_type=6,'Прогнозы',
                                    IF(parent_type=7,'Интервью',
                                        IF(parent_type=8,'ЖК',
                                            IF(parent_type=9,'БСН-ТВ',
                                                IF(parent_type=5,'Вебинары',
                                                    IF(parent_type=3,'Календарь',
                                                        IF(parent_type=2,'Статьи','')
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                          ) as `parent_type` 
                FROM ".$sys_tables['comments'];
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY id DESC";
        $sql .= " LIMIT ".$paginator->getLimitString($page); 
        $list = $db->fetchall($sql);
        foreach($list as $k=>$item){
            switch($item['parent_type']){
                case 'Новости':
                    $news_item = $db->fetch("SELECT chpu_title FROM ".$sys_tables['news']." WHERE id = ?", $item['id_parent']);
                    $news = new Content('news');
                    $link = $news->getItem($news_item['chpu_title']);
                    $list[$k]['link'] = '/news/'.$link['category_code'].'/'.$link['region_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 'Статьи':
                    $analytic_item = $db->fetch("SELECT chpu_title FROM ".$sys_tables['articles']." WHERE id = ?", $item['id_parent']);
                    $articles = new Content('articles');
                    $link = $articles->getItem($analytic_item['chpu_title']);
                    $list[$k]['link'] = '/articles/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 'Календарь':
                    $calendar_item = $db->fetch("SELECT chpu_title FROM ".$sys_tables['calendar_events']." WHERE id = ?", $item['id_parent']);
                    $list[$k]['link'] = '/calendar/'.$calendar_item['chpu_title'].'/';
                    break;
                case 'ЖК':
                    $calendar_item = $db->fetch("SELECT chpu_title FROM ".$sys_tables['housing_estates']." WHERE id = ?", $item['id_parent']);
                    $list[$k]['link'] = '/zhiloy_kompleks/'.$calendar_item['chpu_title'].'/';
                    break;
                case 'Вебинары':
                    $webinar_item = $db->fetch("SELECT url FROM ".$sys_tables['webinars']." WHERE id = ?", $item['id_parent']);
                    $list[$k]['link'] = '/webinars/'.$webinar_item['url'].'/';
                    break;
                case 'Мнения':
                case 'Прогнозы':
                case 'Интервью':
                    $opinions = new Opinions($item['parent_type']=='Интервью'?'interview':($item['parent_type']=='Прогнозы'?'predictions':'opinions'));
                    $opinion_item = $opinions->getItem($item['id_parent']);
                    $list[$k]['link'] = '/'.$opinion_item['type_url'].'/'.$opinion_item['estate_url'].'/'.$opinion_item['chpu_title'].'/';
                    break;
                case 'Новости':
                    $news_item = $db->fetch("SELECT chpu_title FROM ".$sys_tables['news']." WHERE id = ?", $item['id_parent']);
                    $news = new Content('news');
                    $link = $news->getItem($news_item['chpu_title']);
                    $list[$k]['link'] = '/news/'.$link['category_code'].'/'.$link['region_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 'БСН-ТВ':
                    $bsntv_item = $db->fetch("SELECT chpu_title FROM ".$sys_tables['bsntv']." WHERE id = ?", $item['id_parent']);
                    $bsntv = new Content('bsntv');
                    $link = $bsntv->getItem($bsntv_item['chpu_title']);
                    $list[$k]['link'] = '/bsntv/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 'Доверие потребителя':
                    $doverie_item = $db->fetch("SELECT chpu_title FROM ".$sys_tables['doverie']." WHERE id = ?", $item['id_parent']);
                    $doverie = new Content('doverie');
                    $link = $doverie->getItem($doverie_item['chpu_title']);
                    $list[$k]['link'] = '/doverie/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 'Блог':
                    $blog_item = $db->fetch("SELECT chpu_title FROM ".$sys_tables['blog']." WHERE id = ?", $item['id_parent']);
                    $blog = new Content('blog');
                    $link = $blog->getItem($blog_item['chpu_title']);
                    $list[$k]['link'] = '/blog/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                    
            }
        }
        // формирование списка
        Response::SetArray('list', $list);
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        break;
}


// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>