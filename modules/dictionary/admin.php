<?php
/**
INSERT INTO `content`.`partners_articles` ( `id`, `id_category`, `published`, `title`, `content_short`, `content`, `code`) 
SELECT  `id`, `cat`, IF(`actual`='Y',1,2) as `published`,`title`, `anons`, `content`, `url`
FROM bsnweb.artpay

INSERT INTO `content`.`partners_articles_categories` ( `id`, `position`, `title`,`code`) 
SELECT  `id`, `priority`, `title`,`code`
FROM bsnweb.artpay_category
*/
$GLOBALS['js_set'][] = '/modules/dictionary/ajax_actions.js';
require_once('includes/class.paginator.php');

// таблицы модуля
$sys_tables = Config::$sys_tables; 
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'Словарь'));
$strings_per_page=Config::Get('view_settings/strings_per_page');
// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['word'] = Request::GetString('f_word',METHOD_GET);
if(!empty($filters['word'])) {
    $filters['word'] = urldecode($filters['word']);
    $get_parameters['f_word'] = $filters['word'];
}
$page = Request::GetInteger('page',METHOD_GET);
if ((isset($page))&&($page==0)) Host::Redirect("admin/content/dictionary/"); 
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// обработка action-ов
switch($action){
    /************************\
    |*  Работа с новостями  *|
    \************************/
    //###########################################################################
    // добавление записи
    //###########################################################################
    case 'add':
    //###########################################################################
    // редактирование записи
    //###########################################################################
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
		
        $module_template = 'admin.dictionary.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['dictionary']);
            $info['datetime'] = date('Y-m-d H:i:s');
            $info['content'] = $info['content_short'] = "";
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *
                                FROM ".$sys_tables['dictionary']." 
                                WHERE id=?", $id);
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['dictionary'][$key])) $mapping['dictionary'][$key]['value'] = $info[$key];
        }
        
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);

        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['dictionary'][$key])) $mapping['dictionary'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['dictionary']);
            
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['dictionary'][$key])) $mapping['dictionary'][$key]['error'] = $value;
            }
            
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if(isset($mapping['dictionary'][$key]['value'])) $info[$key] = $mapping['dictionary'][$key]['value'];
                }
                
                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables['dictionary'], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables['dictionary'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        // редирект на редактирование свеженькой страницы
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/content/dictionary/edit/'.$new_id.'/'));
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
        Response::SetArray('data_mapping',$mapping['dictionary']);
        break;
    //###########################################################################
    // удаление записи
    //###########################################################################
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $res = $db->querys("DELETE FROM ".$sys_tables['dictionary']." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    default:
        $module_template = 'admin.dictionary.list.html';
        $where = false;
        if(!empty($filters['word'])) $where = " `word` LIKE '%".$filters['word']."%'";
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['dictionary'], 30, $where);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/content/dictionary'                  // модуль
                                  ."/?"                                       // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)             // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }
        //выбираем страницы для отображения
        $sql = "SELECT id,word,meaning FROM ".$sys_tables['dictionary'];
        if(!empty($where)) $sql.=" WHERE ".$where;
        $sql .= " ORDER BY `word`";
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