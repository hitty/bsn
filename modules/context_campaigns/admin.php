<?php
//сигнатуры аналитики
$analytics_signatures = array(
    'block::bsn::analytics/block/country/graphics',
    'block::bsn::analytics/block/5',
    'block::bsn::analytics/block',
);

        
$GLOBALS['js_set'][] = '/modules/context_campaigns/ajax_actions.js';
$GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
$GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';

require_once('includes/class.paginator.php');
require_once('includes/class.tags.php');
if( !class_exists( 'Photos' ) )  require_once('includes/class.photos.php');;

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$this_page->manageMetadata(array('title'=>'БСН Таргет'));

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['date'] = Request::GetString('f_date',METHOD_GET);
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
$filters['adv_status'] = Request::GetString('f_status',METHOD_GET);
$filters['region'] = Request::GetInteger('f_region',METHOD_GET);
$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
$filters['user'] = Request::GetInteger('f_user',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['date'])) {
    $filters['date'] = urldecode($filters['date']);
    $get_parameters['f_date'] = $filters['date'];
}
if(!empty($filters['status'])) {
    $get_parameters['f_status'] = $filters['status'];
}
if(!empty($filters['region'])) {
    $get_parameters['f_region'] = $filters['region'];
}
if(!empty($filters['category'])) {
    $get_parameters['f_category'] = $filters['category'];
}
///фильтры для статистики
if(!empty($filters['agency'])){
    $get_parameters['f_agency'] = $filters['agency'];
}
if(!empty($filters['place'])){
    $get_parameters['f_place'] = $filters['place'];
}
if(!empty($filters['agency_title'])){
    $get_parameters['f_agency_title'] = $filters['agency_title'];
}
///
///фильтры для списка рекламных объявлений
if(!empty($filters['adv_status'])){
    $get_parameters['f_status'] = $filters['adv_status'];
}
///
///фильтры для списка рекламных кампаний
if(!empty($filters['user'])){
    $get_parameters['f_user'] = $filters['user'];
}
///
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
//запоминаем основное действие над РК
$main_action = $action;
// обработка action-ов
switch(TRUE){
    //создание/редактирование кампании
    case $action == 'add':
    case $action == 'edit':
        $GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
        // мэппинги модуля
        $mapping = include(dirname(__FILE__).'/conf_mapping.php');
        //получаем id объекта
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($main_action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables['context_campaigns']);
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT ".$sys_tables['context_campaigns'].".*
                                FROM ".$sys_tables['context_campaigns']."
                                WHERE id=?", $id) ;
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        if(!empty($info))
        foreach($info as $key=>$field){
            if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['value'] = $info[$key];
        }
        
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);
        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['context_campaigns']);
            
            //если это текстовый блок, то картинка не обязательна
            
            
            //если хотим опубликовать и это графический блок, проверяем, есть ли фотография и указан ли тип объекта (кроме стройки)
            if($mapping['context_campaigns']['published']['value'] == 1){
                //если баланс нулевой, сразу пишем ошибку
                if(!$post_parameters['balance'] > 0) $errors['published'] = "Для публикации установите положительный баланс";
            }
            elseif($main_action == 'edit'){
                //если кампания не новая, убираем в архив все объявления кампании
                $db->query("UPDATE ".$sys_tables['context_advertisements']." SET published = 2 WHERE id_campaign = ".$info['id']);
            }
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if (isset($mapping['context_campaigns'][$key]['value'])) $info[$key] = strip_tags($mapping['context_campaigns'][$key]['value'],'<table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                }
                
                //добавляем id пользователя, создавшего эту кампанию
                if(empty($info['id_creator'])) $info['id_creator'] = $auth->id;
                //если id владельца не указан, также записываем туда id пользователя
                if(empty($info['id_user'])) $info['id_user'] = $auth->id;
                
                // сохранение в БД
                if($main_action=='edit'){
                    $res = $db->updateFromArray($sys_tables['context_campaigns'], $info, 'id');
                }else{
                    $res = $db->insertFromArray($sys_tables['context_campaigns'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        header('Location: '.Host::getWebPath('/admin/advert_objects/context_campaigns/edit/'.$new_id.'/'));
                        exit(0);
                    }
                }
                Response::SetBoolean('saved', $res); // результат сохранения
            }
            else Response::SetBoolean('errors', true); // признак наличия ошибок
        }
        // если мы попали на страницу редактирования путем редиректа с добавления, 
        // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
        $referer = Host::getRefererURL();
        
        if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
            Response::SetBoolean('form_submit', true);
            Response::SetBoolean('saved', true);
        }
        // запись данных для отображения на странице
        Response::SetArray('data_mapping',$mapping['context_campaigns']);
        
        $module_template = "admin_campaigns_edit.html";
        break;
    //статистика кампании
    case $action == 'stats':
        $id_campaign = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $campaign_info = $db->fetch("SELECT title FROM ".$sys_tables['context_campaigns']." WHERE id = ".$id_campaign);
        Response::SetArray('campaign_info',$campaign_info);
        $sql = "SELECT ".$sys_tables['context_advertisements'].".id,
                        ".$sys_tables['context_advertisements'].".title,
                        ".$sys_tables['context_places'].".title AS place_title,
                        ".$sys_tables['context_advertisements'].".published,
                        SUM(".$sys_tables['context_finances'].".expenditure) AS money_spent,
                        (IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount)) AS shows_full,
                        (IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount)) AS clicks_full,
                        IF(s_day.amount IS NULL,0,s_day.amount) AS shows_day,
                        IF(c_day.amount IS NULL,0,c_day.amount) AS clicks_day,
                        CAST(CAST( ((IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount))/(IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) AS CTR
                FROM ".$sys_tables['context_advertisements']."
                LEFT JOIN ".$sys_tables['context_campaigns']." ON ".$sys_tables['context_campaigns'].".id = ".$sys_tables['context_advertisements'].".id_campaign
                LEFT JOIN ".$sys_tables['context_finances']." ON ".$sys_tables['context_finances'].".id_parent = ".$sys_tables['context_advertisements'].".id
                LEFT JOIN ".$sys_tables['context_places']." ON ".$sys_tables['context_places'].".id = ".$sys_tables['context_advertisements'].".id_place
                LEFT JOIN (SELECT ".$sys_tables['context_stats_show_full'].".id_parent, SUM(amount) AS amount, COUNT(*) AS days_in, `date` FROM ".$sys_tables['context_stats_show_full']." GROUP BY ".$sys_tables['context_stats_show_full'].".id_parent) s_full
                ON s_full.id_parent = ".$sys_tables['context_advertisements'].".id
                LEFT JOIN (SELECT ".$sys_tables['context_stats_click_full'].".id_parent, SUM(amount) AS amount FROM ".$sys_tables['context_stats_click_full']." GROUP BY ".$sys_tables['context_stats_click_full'].".id_parent) c_full
                ON c_full.id_parent = ".$sys_tables['context_advertisements'].".id
                LEFT JOIN (SELECT ".$sys_tables['context_stats_show_day'].".id_parent, COUNT(*) AS amount FROM ".$sys_tables['context_stats_show_day']." GROUP BY ".$sys_tables['context_stats_show_day'].".id_parent) s_day
                ON s_day.id_parent = ".$sys_tables['context_advertisements'].".id
                LEFT JOIN (SELECT ".$sys_tables['context_stats_click_day'].".id_parent, COUNT(*) AS amount FROM ".$sys_tables['context_stats_click_day']." GROUP BY ".$sys_tables['context_stats_click_day'].".id_parent) c_day
                ON c_day.id_parent = ".$sys_tables['context_advertisements'].".id
                WHERE id_campaign = ".$id_campaign."
                GROUP BY ".$sys_tables['context_advertisements'].".id";
        $adv_list = $db->fetchall($sql);
        Response::SetArray('list',$adv_list);
        
        //строчка "Всего"
        $total = array('c_day'=>0,'c_full'=>0,'s_day'=>0,'s_full'=>0,'CTR'=>0,'expenditure'=>0);
        foreach($adv_list as $key=>$item){
            $total['c_day'] += $item['clicks_day'];
            $total['c_full'] += $item['clicks_full'];
            $total['s_day'] += $item['shows_day'];
            $total['s_full'] += $item['shows_full'];
            $total['CTR'] += $item['CTR'];
            $total['expenditure'] += $item['money_spent'];
        }
        $total['CTR'] = number_format($total['CTR']/count($adv_list),2) ;
        Response::SetArray('total',$total);
        
        $module_template = "admin_campaigns_stats.html";
        break;
    //содержимое кампании (по кнопке "Просмотр")
    case (!empty($action) && Validate::isDigit($action)):
        $id_campaign = Convert::ToInt($action);
        if(!empty($id_campaign))
            $campaign_info = $db->fetch("SELECT * FROM ".$sys_tables['context_campaigns']." WHERE id = ".$id_campaign);
        Response::SetInteger('campaign_id',$id_campaign);
        Response::SetArray('campaign_info',$campaign_info);
        //переопределяем $action
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        //запоминаем основное действие над рекламным блоком
        $main_action = $action;
        //работа с рекламными блоками кампании
        switch($action){
            //добавление/создание рекламного блока
            case 'add':
            case 'edit':
                $id_block = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                //переопределяем $action
                $action = empty($this_page->page_parameters[4]) ? "" : $this_page->page_parameters[4];
                switch($action){
                    case 'targeting':
                        //переопределяем $action
                        $action = empty($this_page->page_parameters[5]) ? "" : $this_page->page_parameters[5];
                        switch($action){
                            //добавляем тег таргетинга (комнатность, метро, район, район ЛО)
                            case 'add':
                                //читаем группу, к которой относится тег (комнатность,метро, район, район ЛО)
                                $group = Request::GetString('tag_group',METHOD_POST);
                                //читаем значение тега
                                $value = Request::GetString('tag_info',METHOD_POST);
                                //если значение тега - текстовое, значит это новый тег
                                if(Convert::ToInt($value) == 0){
                                    //читаем id источника
                                    $source_id = Request::GetInteger('source_id',METHOD_POST);
                                    //читаем ограничения (если пусто - ограничений нет)
                                    $restrictions = Request::GetString('tag_restrictions',METHOD_POST);
                                    if(empty($restrictions)) $restrictions = "1234";
                                    $value = urldecode($value);
                                    $exists = false;
                                }
                                else
                                    $exists = $db->fetch("SELECT id FROM ".$sys_tables['context_tags']." WHERE id = ".$value);
                                
                                if(!empty($exists)){
                                    //если есть, просто добавляем запись в таблицу соответствия (дубль не вставится, так как там unique (id_context,id_tag))
                                    $res = $db->query("INSERT IGNORE INTO ".$sys_tables['context_tags_conformity']." (id_context,id_tag) VALUES (?,?)",$id_block,$exists['id']);
                                    //увеличиваем счетчик для соответствующей кампании
                                    if($res) $res = $db->query("UPDATE ".$sys_tables['context_advertisements']." SET tags_amount = tags_amount + 1 WHERE id = ?",$id_block);
                                    else $res = true;
                                }
                                else{
                                    //если нет, добавляем сначала тег
                                    $res = $db->query("INSERT INTO ".$sys_tables['context_tags']." (txt_field,txt_value,source_id,estate_type) VALUES (?,?,?,?)",$group,$value,$source_id,$restrictions);
                                    //а потом, если все хорошо, запись в таблицу соответствия
                                    if($res){
                                        //если это не новая кампания, добавляем запись, если новая, добавляем запрос в список отложенных
                                        if(!empty($id_block)){
                                            $res = $db->query("INSERT IGNORE INTO ".$sys_tables['context_tags_conformity']." (id_context,id_tag) VALUES (?,?)",$id_block,$db->insert_id);
                                            //увеличиваем счетчик для соответствующей кампании
                                            $res = $db->query("UPDATE ".$sys_tables['context_advertisements']." SET tags_amount = tags_amount + 1 WHERE id = ?",$id_block);
                                        }
                                        else $ajax_result['delayed_sql'] = "INSERT IGNORE INTO ".$sys_tables['context_tags_conformity']." (id_context,id_tag) VALUES (?,".$db->insert_id.")";
                                    }
                                }
                                $ajax_result['ok'] = $res;
                                break;
                            //редактируем (нижняя цена, верхняя цена)
                            case 'edit':
                                //читаем тип цены (1=floor/2=top)
                                $price_type = empty($this_page->page_parameters[6]) ? "" : $this_page->page_parameters[6];$price_type = Convert::ToInt($price_type);
                                //читаем значение цены
                                $price_value = empty($this_page->page_parameters[7])?"":$this_page->page_parameters[7];$price_value = Convert::ToInt($price_value);
                                if($price_value>0) $res = $db->query("UPDATE ".$sys_tables['context_advertisements']." SET ".(($price_type == 1)?"price_floor=":"price_top=").$price_value." WHERE id =".$id_block);
                                $ajax_result['ok'] = $res;
                                break;
                            //удаляем тег таргетинга (комнатность, метро, район, район ЛО)
                            case 'delete':
                                //читаем id тега
                                //$id_tag = empty($this_page->page_parameters[5]) ? "" : $this_page->page_parameters[5];
                                $id_tag = Request::GetInteger('tag_id',METHOD_POST);
                                //удаляем запись из таблицы соответствия
                                $res = $db->query("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ".$id_block." AND id_tag = ".$id_tag);
                                $ajax_result['ok'] = $res;
                                //уменьшаем tags_amount для соответствующей кампании
                                $db->query("UPDATE ".$sys_tables['context_advertisements']." SET ".$sys_tables['context_advertisements'].".tags_amount = ".$sys_tables['context_advertisements'].".tags_amount - 1 WHERE id = ".$id_block);
                                break;
                        }
                        break;
                    break;
                    //редактирование данных рекламного блока (не таргетинга)
                    default:
                        //для оповещения о смене статуса рекламного блока или кампании, подключаем класс
                        require_once('includes/class.context_campaigns.php');
                        // мэппинги модуля
                        $mapping = include(dirname(__FILE__).'/conf_mapping.php');
                        //получаем id объекта
                        $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                        if($main_action=='add'){
                            // создание болванки новой записи
                            $info = $db->prepareNewRecord($sys_tables['context_advertisements']);
                        } else {
                            // получение данных из БД
                            $info = $db->fetch("SELECT ".$sys_tables['context_advertisements'].".*
                                                FROM ".$sys_tables['context_advertisements']."
                                                WHERE id=?", $id) ;
                        }
                        //запоминаем исходный статус объявления
                        $initial_status = $info['published'];
                        // перенос дефолтных (считанных из базы) значений в мэппинг формы
                        if(!empty($info))
                        foreach($info as $key=>$field){
                            if(!empty($mapping['context_advertisements'][$key])) $mapping['context_advertisements'][$key]['value'] = $info[$key];
                        }
                        //если "изображение+текст" справа в поиске или в карточке - это просто текст
                        if($mapping['context_advertisements']['block_type']['value'] == 2 && in_array($mapping['context_advertisements']['id_place']['value'],array(1,2)))
                            $mapping['context_advertisements']['block_type']['value'] = 3;
                        
                        //// формирование дополнительных данных для формы (не из основной таблицы)
                        ///таргетинг для этого объявления
                        //список выбранных условий (группируем выбранные теги по разделам (комнатность, метро,...))
                        $targeting_list = $db->fetchall("SELECT ".$sys_tables['context_tags'].".txt_field,
                                                                ".$sys_tables['context_tags'].".id,
                                                                ".$sys_tables['context_tags'].".source_id,
                                                                ".$sys_tables['context_tags'].".txt_value AS value
                                                         FROM ".$sys_tables['context_tags_conformity']."
                                                         LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['context_tags_conformity'].".id_tag = ".$sys_tables['context_tags'].".id
                                                         WHERE ".$sys_tables['context_tags_conformity'].".id_context = ".$id,'id');
                        $targeting_set = array();
                        if(!empty($targeting_list)){
                            //$targeting_set = array();
                            foreach($targeting_list as $key=>$values){
                                if(empty($targeting_set[$values['txt_field']])) $targeting_set[$values['txt_field']] = array();
                                $targeting_set[$values['txt_field']][$key] = $values;
                            }
                        }
                        Response::SetArray('targeting_list',$targeting_set);
                        $targeting_list = $targeting_set;
                        //читаем списки районов, районов области, станций метро, различных типов объектов
                        $targeting_data = array();
                        $targeting_data['rooms'] = array(1=>'1',2=>'2',3=>'3',4=>'4');
                        //районы СПБ
                        $targeting_data['districts'] = $db->fetchall("SELECT ".$sys_tables['districts'].".id,".$sys_tables['districts'].".title,".$sys_tables['context_tags'].".id AS id_tag
                                                                      FROM ".$sys_tables['districts']."
                                                                      LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['districts'].".id = ".$sys_tables['context_tags'].".source_id
                                                                      AND ".$sys_tables['context_tags'].".txt_field = 'districts' ORDER BY ".$sys_tables['districts'].".title ASC",'id');
                        //районы ЛО из geodata
                        $targeting_data['district_areas'] = $db->fetchall("SELECT ".$sys_tables['geodata'].".id, ".$sys_tables['geodata'].".offname AS title, ".$sys_tables['context_tags'].".id AS id_tag
                                                                           FROM ".$sys_tables['geodata']."
                                                                           LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['geodata'].".id = ".$sys_tables['context_tags'].".source_id
                                                                           AND ".$sys_tables['context_tags'].".txt_field = 'district_areas'
                                                                           WHERE ".$sys_tables['geodata'].".id_region = 47 AND ".$sys_tables['geodata'].".a_level = 2
                                                                           ORDER BY ".$sys_tables['geodata'].".offname ASC ",'id');
                        //метро
                        $targeting_data['subways'] = $db->fetchall("SELECT ".$sys_tables['subways'].".id,".$sys_tables['subways'].".title,".$sys_tables['context_tags'].".id AS id_tag
                                                                    FROM ".$sys_tables['subways']."
                                                                    LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['subways'].".id = ".$sys_tables['context_tags'].".source_id
                                                                    AND ".$sys_tables['context_tags'].".txt_field = 'subways' ORDER BY ".$sys_tables['subways'].".title ASC",'id');
                        //'type_objects_live'
                        $targeting_data['type_objects_live'] = $db->fetchall("SELECT ".$sys_tables['type_objects_live'].".id,".$sys_tables['type_objects_live'].".title,".$sys_tables['context_tags'].".id AS id_tag
                                                                       FROM ".$sys_tables['type_objects_live']."
                                                                       LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['type_objects_live'].".id = ".$sys_tables['context_tags'].".source_id
                                                                       AND ".$sys_tables['context_tags'].".txt_field = 'type_objects_live' ORDER BY ".$sys_tables['type_objects_live'].".title ASC",'id');
                        //'type_objects_commercial'
                        $targeting_data['type_objects_commercial'] = $db->fetchall("SELECT ".$sys_tables['type_objects_commercial'].".id,".$sys_tables['type_objects_commercial'].".title,".$sys_tables['context_tags'].".id AS id_tag
                                                                             FROM ".$sys_tables['type_objects_commercial']."
                                                                             LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['type_objects_commercial'].".id = ".$sys_tables['context_tags'].".source_id
                                                                             AND ".$sys_tables['context_tags'].".txt_field = 'type_objects_commercial' ORDER BY ".$sys_tables['type_objects_commercial'].".title ASC",'id');
                        //'type_objects_country'
                        $targeting_data['type_objects_country'] = $db->fetchall("SELECT ".$sys_tables['type_objects_country'].".id,".$sys_tables['type_objects_country'].".title,".$sys_tables['context_tags'].".id AS id_tag
                                                                          FROM ".$sys_tables['type_objects_country']."
                                                                          LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['type_objects_country'].".id = ".$sys_tables['context_tags'].".source_id
                                                                          AND ".$sys_tables['context_tags'].".txt_field = 'type_objects_country' ORDER BY ".$sys_tables['type_objects_country'].".title ASC",'id');
                        Response::SetArray('targeting_data',$targeting_data);
                        ///
                        ////
                        
                        // получение данных, отправленных из формы
                        $post_parameters = Request::GetParameters(METHOD_POST);
                        // если была отправка формы - начинаем обработку
                        if(!empty($post_parameters['submit'])){
                            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                            
                            //если набор типов недвижимости изменился, отвязываем все теги, которые не подходят к новому набору
                            if($post_parameters['estate_type'] != $mapping['context_advertisements']['estate_type']['value']){
                                //выбираем теги, ограничения которых не подходят под наш набор и убираем их
                                $condition = array();$estate_type_splitted = str_split($post_parameters['estate_type']);
                                foreach($estate_type_splitted as $value)
                                    $condition[] = " estate_type NOT LIKE '%".$value."%'";
                                $condition = implode(' AND ',$condition);
                                $db->query("DELETE
                                            FROM ".$sys_tables['context_tags_conformity']."
                                            WHERE id_context = ? AND 
                                                  id_tag IN (SELECT id FROM ".$sys_tables['context_tags']." WHERE ".$condition.")",$id_block);
                            }
                            
                            //если изменилось место размещения или тип блока, проверяем подойдут ли старые картинки
                            if($post_parameters['id_place'] != $mapping['context_advertisements']['id_place']['value'] ||
                               $post_parameters['block_type'] != $mapping['context_advertisements']['block_type']['value'] ||
                               $post_parameters['block_type'] == 3){
                                $sizes = $db->fetchall("SELECT ".($post_parameters['block_type'] == 2?"CONCAT(width,height) AS wh ":"CONCAT(width_txtimage,height_txtimage) AS wh ").
                                                       "FROM ".$sys_tables['context_places']." 
                                                        WHERE id = ".$post_parameters['id_place']." OR id = ".$mapping['context_advertisements']['id_place']['value']);
                                //если размеры изменились, отвязываем картинки, так как они уже не подходят
                                if(count($sizes) != 1){
                                    //удаляем картнки
                                    $db->query("DELETE FROM ".$sys_tables['context_advertisements_photos']." WHERE id_parent = ?",$id_block);
                                    //устанавливаем id_main_photo = 0 для данной кампании
                                    $db->query("UPDATE ".$sys_tables['context_advertisements']." SET id_main_photo = 0 WHERE id = ".$id_block);
                                }
                            }
                            
                            //убираем псевдо-тип "Текст" - он будет складываться из места размещения и типа "Изображение + текст"
                            if($post_parameters['block_type'] == 3) $post_parameters['block_type'] = 2;
                            
                            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                            foreach($post_parameters as $key=>$field){
                                if(!empty($mapping['context_advertisements'][$key])) $mapping['context_advertisements'][$key]['value'] = $post_parameters[$key];
                            }
                            // проверка значений из формы
                            $errors = Validate::validateParams($post_parameters,$mapping['context_advertisements']);
                            //если хотим опубликовать, проверяем, есть ли фотография и указан ли тип объекта (кроме стройки)
                            if($mapping['context_advertisements']['published']['value'] == 1){
                                $has_photo = $db->fetch("SELECT id_main_photo FROM ".$sys_tables['context_advertisements']." WHERE id = ".$id_block);
                                if(empty($has_photo['id_main_photo']) && $mapping['context_advertisements']['block_type']['value'] != 2){
                                    $errors['published'] = "Для публикации добавьте картинки.";
                                    $mapping['context_advertisements']['published']['value'] = 2;
                                }
                                
                                //флаг, указан ли тип объекта (для стройки не надо)
                                if ($post_parameters['estate_type'] == 2) $has_type = true;
                                else $has_type = false;
                                //флаг, указаны ли цена или метро или район
                                $has_targeting_data = false;
                                foreach($targeting_list as $key=>$item){
                                    if(preg_match('/type_objects/',$key))
                                        $has_type = true;
                                    if(preg_match('/districts/',$key)||preg_match('/district_areas/',$key)||preg_match('/subways/',$key))
                                        $has_targeting_data = true;
                                }
                                if($info['price_floor']||$info['price_top']) $has_targeting_data = true;
                                //если что-то не указано, пишем ошибку
                                if(!$has_targeting_data){
                                    $errors['published'] = "Для публикации добавьте таргетинг цене, метро, или району(району ЛО)";
                                    $mapping['context_advertisements']['published']['value'] = 2;
                                }
                                if(!$has_type){
                                    $errors['published'] = "Для публикации добавьте таргетинг по типу объекта";
                                    $mapping['context_advertisements']['published']['value'] = 2;
                                }
                            }
                            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                            foreach($errors as $key=>$value){
                                if(!empty($mapping['context_advertisements'][$key])) $mapping['context_advertisements'][$key]['error'] = $value;
                            }
                            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                            if(empty($errors)) {
                                // подготовка всех значений для сохранения
                                foreach($info as $key=>$field){
                                    if (isset($mapping['context_advertisements'][$key]['value'])) $info[$key] = strip_tags($mapping['context_advertisements'][$key]['value'],'<table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                                }
                                
                                //добавляем id пользователя, создавшего этот блок
                                if(empty($info['id_creator'])) $info['id_creator'] = $auth->id;
                                //во владельцы записываем владельца рекламной кампании
                                $info['id_user'] = $campaign_info['id_user'];
                                //добавляем id рекламной кампании
                                $info['id_campaign'] = $id_campaign;
                                
                                //если статус объявления изменился с "на модерацию" на "опубликовано", оповещаем соответствующую кампанию
                                if($initial_status == 3 && $info['published'] == 1){
                                    //собираем данные и шлем уведомление
                                    $notification_data['cmp_title'] = $campaign_info['title'];
                                    $notification_data['adv_title'] = $info['title'];
                                    //читаем информацию по агентству, чье объявление
                                    $agency_info = $db->fetch("SELECT ".$sys_tables['agencies'].".id AS agency_id,
                                                                      ".$sys_tables['agencies'].".email AS agency_email,
                                                                      ".$sys_tables['managers'].".email AS manager_email,
                                                                      ".$sys_tables['users'].".id,
                                                                      ".$sys_tables['users'].".email AS user_email
                                                               FROM ".$sys_tables['users']."
                                                               LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                                               LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                                               WHERE ".$sys_tables['users'].".id = ".$campaign_info['id_user']);
                                    if($agency_info['agency_id'] == 4966){
                                        $notification_data['agency_email'] = $agency_info['user_email'];
                                    }else $notification_data['agency_email'] = (empty($agency_info['agency_email'])?$agency_info['user_email']:$agency_info['agency_email']);
                                    
                                    //читаем информацию по отв менеджеру
                                    
                                    
                                    contextCampaigns::Notification(2,$notification_data,true,false);
                                }
                                
                                // сохранение в БД
                                if($main_action=='edit'){
                                    $res = $db->updateFromArray($sys_tables['context_advertisements'], $info, 'id');
                                }else{
                                    $res = $db->insertFromArray($sys_tables['context_advertisements'], $info, 'id');
                                    if(!empty($res)){
                                        $new_id = $db->insert_id;
                                        header('Location: '.Host::getWebPath('/admin/advert_objects/context_campaigns/'.$id_campaign.'/edit/'.$new_id.'/'));
                                        exit(0);
                                    }
                                }
                                Response::SetBoolean('saved', $res); // результат сохранения
                            }
                            else Response::SetBoolean('errors', true); // признак наличия ошибок
                        }
                        // если мы попали на страницу редактирования путем редиректа с добавления, 
                        // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                        $referer = Host::getRefererURL();
                        if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                            Response::SetBoolean('form_submit', true);
                            Response::SetBoolean('saved', true);
                        }
                        //места размещения
                        $places = $db->fetchall("SELECT id,
                                                        CONCAT(title,' (',width,'x',height,')') AS place_text,
                                                        ".($mapping['context_advertisements']['block_type']['value'] == 2?"height_txtimage AS height,width_txtimage AS width":"height,width")."
                                                 FROM ".$sys_tables['context_places'],'id');
                        foreach($places as $key=>$val){
                            $mapping['context_advertisements']['id_place']['values'][$val['id']] = $val['place_text'];
                        }
                        //высота и ширина картинки для исходного выбора
                        if(!empty($mapping['context_advertisements']['id_place']['value'])){
                            Response::SetInteger('item_height',$places[$mapping['context_advertisements']['id_place']['value']]['height']);
                            Response::SetInteger('item_width',$places[$mapping['context_advertisements']['id_place']['value']]['width']);
                        }
                        ///заполняем галочки невидимых полей (тип недвижимости и тип сделки)
                        //заполняем данные что показывать по типу недвижимости
                        if(preg_match('/1/',$mapping['context_advertisements']['estate_type']['value'])){
                            Response::SetBoolean('estate_live',true);
                            Response::SetBoolean('show_district_areas',true);
                            Response::SetBoolean('show_rooms',true);
                            Response::SetBoolean('show_districts',true);
                            Response::SetBoolean('show_subways',true);
                        }
                        if (preg_match('/2/',$mapping['context_advertisements']['estate_type']['value'])){
                            Response::SetBoolean('estate_build',true);
                            Response::SetBoolean('show_rooms',true);
                            Response::SetBoolean('show_districts',true);
                            Response::SetBoolean('show_district_areas',true);
                            Response::SetBoolean('show_subways',true);
                        }
                        if (preg_match('/4/',$mapping['context_advertisements']['estate_type']['value'])){
                            Response::SetBoolean('estate_country',true);
                            Response::SetBoolean('show_subways',true);
                            Response::SetBoolean('show_district_areas',true);
                        }
                        if(preg_match('/3/',$mapping['context_advertisements']['estate_type']['value'])){
                            Response::SetBoolean('estate_commercial',true);
                            Response::SetBoolean('show_district_areas',true);
                            Response::SetBoolean('show_districts',true);
                            Response::SetBoolean('show_subways',true);
                        }
                        //заполняем данные что показывать по типу сделки
                        if(preg_match('/1/',$mapping['context_advertisements']['deal_type']['value'])) Response::SetBoolean('deal_rent',true);
                        if(preg_match('/2/',$mapping['context_advertisements']['deal_type']['value'])) Response::SetBoolean('deal_sell',true);
                        ///
                        
                        //обрабатываем псевдо-тип "Текст"
                        if($mapping['context_advertisements']['block_type']['value'] == 2 && in_array($mapping['context_advertisements']['id_place']['value'],array(1,2)))
                            $mapping['context_advertisements']['block_type']['value'] = 3;
                        // запись данных для отображения на странице
                        Response::SetArray('data_mapping',$mapping['context_advertisements']);
                        //делаем Response, чтобы можно было по изменению места размещения скрывать/показывать поле "Подпись к баннеру"
                        Response::SetString('places_data',json_encode($places));
                        //
                        $module_template = 'admin_adv_edit.html';
                    break;
                }
                break;
            //работа с фотографиями 
            case 'photos':
                if($ajax_mode){
                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                    switch($action){
                        case 'list':
                            //получение списка фотографий
                            //id текущего рекламного блока
                            $id = Request::GetInteger('id', METHOD_POST);
                            if(!empty($id)){
                                $list = Photos::getList('context_advertisements',$id);
                                if(!empty($list)){
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $list;
                                    $ajax_result['folder'] = Config::$values['img_folders']['context_advertisements'];
                                } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'add':
                            //загрузка фотографий
                            //id текущего рекламного блока
                            $id = Request::GetInteger('id', METHOD_POST);
                            //читаем высоту и ширину фотографии
                            
                            if(!empty($id)){
                                $campaign_data = $db->fetch("SELECT ".$sys_tables['context_places'].".width AS width,
                                                                    ".$sys_tables['context_places'].".height AS height
                                                         FROM ".$sys_tables['context_advertisements']."
                                                         LEFT JOIN ".$sys_tables['context_places']." ON ".$sys_tables['context_advertisements'].".id_place = ".$sys_tables['context_places'].".id
                                                         WHERE ".$sys_tables['context_advertisements'].".id = ".$id);
                                $height = $campaign_data['height'];
                                $width = $campaign_data['width'];
                                //параметр fixed_sizes = true - чтобы картинки не ресайзились
                                $errors_log = array();
                                // свойства папок для загрузки и формата фотографий
                                Photos::$__folder_options=array('big'=>array($width,$height,'',95),'sm'=>array($width,$height,'',95));
                                $res = Photos::Add('context_advertisements',$id,false,false,false,$width,$height,true,false,false,$width,$height,true);
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
                                $res = Photos::Delete('context_advertisements',$id_photo);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        
                    }
                }
                break;
            //копирование рекламного блока
            case 'copy':
                if($ajax_mode){
                    //получаем id объекта
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    //копируем само объявление:
                    $temp_info = $db->fetch("SELECT * FROM ".$sys_tables['context_advertisements']." WHERE id = ".$id);
                    if(empty($temp_info)){
                        $ajax_result['ok'] = false; break;
                    }
                    unset($temp_info['id']);
                    unset($temp_info['id_main_photo']);
                    //убираем копию в архив
                    $temp_info['published'] = 2;
                    //смотрим сколько уже есть копий
                    $similar_advs = $db->fetch("SELECT COUNT(*) as amount 
                                                FROM ".$sys_tables['context_advertisements']." 
                                                WHERE id_user = ".$auth->id." AND title REGEXP '".str_replace(array('(',')'),array('[(]','[)]'),$temp_info['title'])." [(]копия( [0-9]+)?[)]'")['amount'];
                    $similar_advs = (empty($similar_advs)?"":" ".$similar_advs);
                    $temp_info['title'] = $temp_info['title']." (копия".$similar_advs.")";
                    $ajax_result['title'] = $temp_info['title'];
                    $ajax_result['status'] = $temp_info['published'];
                    
                    $res = $db->query("INSERT INTO ".$sys_tables['context_advertisements']." (".implode(',',array_keys($temp_info)).") VALUES (\"".implode('","',array_values(array_map("addSlashes",$temp_info)))."\")");
                    
                    $new_id = $db->insert_id;
                    
                    //копируем пары этот_блок-тег из таблицы соответствия
                    $res = $db->query("INSERT INTO ".$sys_tables['context_tags_conformity']." (id_context,id_tag) 
                                       SELECT ".$new_id." AS id_context,id_tag FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ".$id);
                    
                    ///копируем картинку этого блока, если есть:
                    $img = $db->fetch("SELECT id,name,SUBSTRING(".$sys_tables['context_advertisements_photos'].".name,1,2) AS folder
                                       FROM ".$sys_tables['context_advertisements_photos']."
                                       WHERE id_parent = ".$id);
                    if(!empty($img)){
                        //читаем информацию по размерам картинки
                        list($height,$width) = array_values($db->fetch("SELECT IF(block_type = 1,height,80) AS height,IF(block_type = 1,width,80) AS width
                                                                        FROM ".$sys_tables['context_advertisements']."
                                                                        LEFT JOIN ".$sys_tables['context_places']." ON ".$sys_tables['context_advertisements'].".id_place = ".$sys_tables['context_places'].".id
                                                                        WHERE ".$sys_tables['context_advertisements'].".id = ".$new_id));
                        
                        Photos::$__folder_options=array('big'=>array($width,$height,'',95),'sm'=>array($width,$height,'',95));
                        if(strpos(Host::$host,'.int') !== false)
                        $res = Photos::Add('context_advertisements',$new_id,false,"https://www.bsn.int/".Config::$values['img_folders']['context_advertisements']."/big/".$img['folder']."/".$img['name'],false,$width,$height,true,false,false,$width,$height,true);
                        else
                        $res = Photos::Add('context_advertisements',$new_id,false,"http://st1.bsn.ru/".Config::$values['img_folders']['context_advertisements']."/big/".$img['folder']."/".$img['name'],false,$width,$height,true,false,false,$width,$height,true);
                        //обновляем id_main_photo
                        if(!empty($res)) $db->query("UPDATE ".$sys_tables['context_advertisements']." SET id_main_photo = ".$res['photo_id']." WHERE id = ".$new_id);
                    }
                    
                    
                    $ajax_result['id'] = $new_id;
                    $ajax_result['new_photo'] = $res;
                    $ajax_result['ok'] = !empty($res);
                    break;
                }
                break;
            //удаление рекламного блока
            case 'del':
                if($ajax_mode){
                    //получаем id объекта
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    //удаляем пары этот_блок-тег из таблицы соответствия
                    $db->query("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ?",$id);
                    //удаляем все фотографии этого блока
                    $db->query("DELETE FROM ".$sys_tables['context_advertisements_photos']." WHERE id_parent = ?",$id);
                    //удаляем сам рекламный блок
                    $res = $db->query("DELETE FROM ".$sys_tables['context_advertisements']." WHERE id = ?",$id);
                    $ajax_result['ok'] = $res;
                }
                break;
            //список рекламных блоков
            case 'stats':
                $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
                $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
                $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js'; 
                $GLOBALS['js_set'][] = '/js/google.chart.api.js';
                $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js';
                $GLOBALS['js_set'][] = '/modules/stats/ajax_actions.js';
                $module_template = 'admin_campaigns_stats.html';
                $condition = "";
                $conditions = array();
                if(!empty($filters['place']))
                    $conditions[] = $sys_tables['context_advertisements'].".id_place = ".$filters['place'];
                if(!empty($filters['campaign_title']))
                    $conditions[] = $sys_tables['context_advertisements'].".title LIKE '%".$filters['campaign_title']."%'";
                $condition = implode(' AND ',$conditions);
                $condition = $sys_tables['context_advertisements'].".published = 1 ".$condition;
                //читаем список контекстных блоков (+соответствующие агентства, общая статистика по кликам и просмотрам)
                $advertisements_list = $db->fetchall("SELECT ".$sys_tables['context_advertisements'].".id AS id_context,
                                                        ".$sys_tables['context_places'].".title AS place_title,
                                                        ".$sys_tables['agencies'].".id AS agency_id,
                                                        ".$sys_tables['agencies'].".title AS agency_title,
                                                        ".$sys_tables['context_stats_click_full'].".amount AS clicks_amount,
                                                        ".$sys_tables['context_stats_show_full'].".amount AS shows_amount
                                                 FROM ".$sys_tables['context_advertisements']."
                                                 LEFT JOIN ".$sys_tables['context_places']." ON ".$sys_tables['context_places'].".id = ".$sys_tables['context_advertisements'].".id_place
                                                 LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['context_advertisements'].".id_user
                                                 LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                                 LEFT JOIN ".$sys_tables['context_stats_click_full']." ON ".$sys_tables['context_stats_click_full'].".id_parent = ".$sys_tables['context_advertisements'].".id
                                                 LEFT JOIN ".$sys_tables['context_stats_show_full']." ON ".$sys_tables['context_stats_show_full'].".id_parent = ".$sys_tables['context_advertisements'].".id
                                                 WHERE ".$condition);
                ///заполняем данные для фильтров 
                //список агентств, у которых есть кампании
                $agencies_list = array();
                foreach($advertisements_list as $key=>$item)
                    $agencies_list[$item['agency_id']] = $item['agency_title'];
                if(!empty($agencies_list))
                    Response::SetArray('agencies_list',$agencies_list);
                //список мест размещения
                $context_places = $db->fetchall("SELECT ".$sys_tables['context_places'].".* FROM ".$sys_tables['context_places']);
                Response::SetArray('context_places',$context_places);
                ///
                Response::SetArray('list',$advertisements_list);
                break;
            //общий список рекламных блоков
            default:
                $module_template = 'admin_adv_list.html';    
                //читаем данные по кампании
                $campaign_info = $db->fetch("SELECT id,title,balance FROM ".$sys_tables['context_campaigns']." WHERE id = ".$id_campaign);
                Response::SetArray('campaign_info',$campaign_info);
                // создаем пагинатор для списка
                $condition = "";
                $sql_select = "SELECT ".$sys_tables['context_advertisements'].".id,
                                      ".$sys_tables['context_advertisements'].".title,
                                      ".$sys_tables['context_advertisements'].".id_main_photo,
                                      ".$sys_tables['context_advertisements'].".published";
                $sql_condition = "FROM ".$sys_tables['context_advertisements']."
                                  LEFT JOIN ".$sys_tables['context_advertisements_photos']." ON ".$sys_tables['context_advertisements'].".id_main_photo = ".$sys_tables['context_advertisements_photos'].".id
                                  WHERE id_campaign = ".$id_campaign;
                $paginator = new Paginator(false, 30, false,"SELECT COUNT(*) as items_count ".$sql_condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = array();
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/advert_objects/context_campaigns/'.$campaign_info['id']                           // модуль
                                          ."/?"                                         // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)           // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
                
                Response::SetString('img_folder', Config::$values['img_folders']['context_advertisements']); // папка для картинок кампаний
                
                $sql = "SELECT ".$sys_tables['context_advertisements'].".id,
                               IF(CHAR_LENGTH(".$sys_tables['context_advertisements'].".title)>32,
                               CONCAT(SUBSTR(".$sys_tables['context_advertisements'].".title,1,30),'...'),
                               ".$sys_tables['context_advertisements'].".title) AS title,
                               ".$sys_tables['context_advertisements_photos'].".name as photo,
                               SUBSTRING(".$sys_tables['context_advertisements_photos'].".name,1,2) AS folder,
                               ".$sys_tables['context_advertisements'].".published,
                               (IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount)) AS shows,
                               (IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount)) AS clicks,
                               s_day.amount AS shows_day,
                               c_day.amount AS clicks_day,
                               CAST(CAST( ((IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount))/(IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) AS CTR
                        FROM ".$sys_tables['context_advertisements']."
                        LEFT JOIN ".$sys_tables['context_advertisements_photos']." ON ".$sys_tables['context_advertisements'].".id_main_photo = ".$sys_tables['context_advertisements_photos'].".id
                        LEFT JOIN (SELECT ".$sys_tables['context_stats_show_full'].".id_parent, SUM(amount) AS amount FROM ".$sys_tables['context_stats_show_full']." GROUP BY ".$sys_tables['context_stats_show_full'].".id_parent) s_full
                        ON s_full.id_parent = ".$sys_tables['context_advertisements'].".id
                        LEFT JOIN (SELECT ".$sys_tables['context_stats_click_full'].".id_parent, SUM(amount) AS amount FROM ".$sys_tables['context_stats_click_full']." GROUP BY ".$sys_tables['context_stats_click_full'].".id_parent) c_full
                        ON c_full.id_parent = ".$sys_tables['context_advertisements'].".id
                        LEFT JOIN (SELECT ".$sys_tables['context_stats_show_day'].".id_parent, COUNT(*) AS amount FROM ".$sys_tables['context_stats_show_day']." GROUP BY ".$sys_tables['context_stats_show_day'].".id_parent) s_day
                        ON s_day.id_parent = ".$sys_tables['context_advertisements'].".id
                        LEFT JOIN (SELECT ".$sys_tables['context_stats_click_day'].".id_parent, COUNT(*) AS amount FROM ".$sys_tables['context_stats_click_day']." GROUP BY ".$sys_tables['context_stats_click_day'].".id_parent) c_day
                        ON c_day.id_parent = ".$sys_tables['context_advertisements'].".id
                        ";
                $condition = array();
                $condition[] = " id_campaign = ".$id_campaign;
                if(!empty($filters['title'])) $condition[] = $sys_tables['context_advertisements'].".title LIKE '%".Convert::ToString($filters['title'])."%'";
                if(!empty($filters['adv_status'])) $condition[] = "published = ".$filters['adv_status'];
                if(!empty($condition)) $sql .= " WHERE ".implode(' AND ',$condition);
                $sql .= " GROUP BY ".$sys_tables['context_advertisements'].".id";
                $sql .= " ORDER BY ".$sys_tables['context_advertisements'].".`id` DESC";
                $sql .= " LIMIT ".$paginator->getLimitString($page);
                $list = $db->fetchall($sql);
                
                // определение главной фотки, считаем общие клики и показы(суммируем общее и по дням)
                $adv_photo_folder=Config::$values['img_folders']['analytics'];
                foreach($list as $key=>$value){
                    //чтобы в шаблоне сразу запихивать в класс
                    switch($list[$key]['published']){
                        case 1: $list[$key]['published'] = "active";break;
                        case 2: $list[$key]['published'] = "unactive";break;
                        case 3: $list[$key]['published'] = "moderation";break;
                    }
                    
                    $photo = Photos::getMainPhoto('analytics',$value['id']);
                    if(!empty($photo)) $list[$key]['photo'] = $adv_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
                }
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
            break;
        }
        break;
    //удаление рекламной кампании
    case $action == 'del':
        if($ajax_mode){
            $res = true;
            //получаем id объекта
            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            //удаляем все рекламные блоки:
            $advertising_list = $db->fetchall("SELECT id FROM ".$sys_tables['context_advertisements']." WHERE id_campaign = ".$id);
            if(!empty($advertising_list))
                foreach($advertising_list as $key=>$item){
                    //удаляем пары этот_блок-тег из таблицы соответствия
                    $res = $res && $db->query("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ?",$item['id']);
                    //удаляем все фотографии этого блока
                    $res = $res && $db->query("DELETE FROM ".$sys_tables['context_advertisements_photos']." WHERE id_parent = ?",$item['id']);
                    //удаляем сам рекламный блок
                    $res = $res && $db->query("DELETE FROM ".$sys_tables['context_advertisements']." WHERE id = ?",$item['id']);
                }
            //удаляем саму кампанию
            $res = $res && $db->query("DELETE FROM ".$sys_tables['context_campaigns']." WHERE id = ".$id);
            $ajax_result['ok'] = $res;
        }
    //список рекламных кампаний
    default:
        //фильтр по пользователю
        $where = ((!empty($filters['user']))?" WHERE ".$sys_tables['context_campaigns'].".id_user = ".$filters['user']:"");
        $sql = "SELECT ".$sys_tables['context_campaigns'].".id,
                       ".$sys_tables['context_campaigns'].".id_user,
                       ".$sys_tables['context_campaigns'].".published,
                       DATE_FORMAT(".$sys_tables['context_campaigns'].".date_start,'%d.%m.%Y') AS date_start_formatted,
                       DATE_FORMAT(".$sys_tables['context_campaigns'].".date_end,'%d.%m.%Y') AS date_end_formatted,
                        IF(CHAR_LENGTH(".$sys_tables['context_campaigns'].".title)>32,
                           CONCAT(SUBSTR(".$sys_tables['context_campaigns'].".title,1,30),'...'),
                           ".$sys_tables['context_campaigns'].".title) AS title,
                        ".$sys_tables['context_campaigns'].".balance,
                        ".$sys_tables['context_campaigns'].".date_end
                FROM ".$sys_tables['context_campaigns']."
                ".$where;
        
        $campaigns_list = $db->fetchall($sql,'id');
                    
        if(!empty($campaigns_list)){
            $campaigns_ids = implode(',',array_keys($campaigns_list));
            $advertisements_ids = $db->fetchall("SELECT GROUP_CONCAT(id) AS ids,id_campaign
                                                 FROM ".$sys_tables['context_advertisements']."
                                                 WHERE id_campaign IN (".$campaigns_ids.")
                                                 GROUP BY id_campaign",'id_campaign');
            $users_ids = $db->fetch("SELECT GROUP_CONCAT(id_user) AS ids FROM ".$sys_tables['context_campaigns']." WHERE ".$sys_tables['context_campaigns'].".id IN (".$campaigns_ids.")");
            $users_ids = (empty($users_ids) ? array() : $users_ids['ids']);
            $moder_advertisements = $db->fetchall("SELECT id_campaign,COUNT(*) AS amount FROM ".$sys_tables['context_advertisements']." WHERE published = 3 GROUP BY id_campaign",'id_campaign');
            $users_info = $db->fetchall("SELECT ".$sys_tables['users'].".id,
                                                ".$sys_tables['users'].".login,
                                                ".$sys_tables['users'].".email,
                                                IF(".$sys_tables['users'].".name!='' OR ".$sys_tables['users'].".lastname!='',
                                                  CONCAT(".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname),'') AS name,
                                                ".$sys_tables['users_groups'].".name AS group_name,
                                                ".$sys_tables['agencies'].".title AS agency_title
                                         FROM ".$sys_tables['users']."
                                         LEFT JOIN ".$sys_tables['users_groups']." ON ".$sys_tables['users_groups'].".id = ".$sys_tables['users'].".id_group
                                         LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                         WHERE ".$sys_tables['users'].".id IN (".$users_ids.")",'id');
            foreach($campaigns_list as $key=>$values){
                if(empty($advertisements_ids[$key]['ids'])) continue;
                $s_full = $db->fetch("SELECT SUM(amount) AS amount 
                                      FROM ".$sys_tables['context_stats_show_full']."
                                      WHERE id_parent IN(".$advertisements_ids[$key]['ids'].")");
                $c_full = $db->fetch("SELECT SUM(amount) AS amount 
                                      FROM ".$sys_tables['context_stats_click_full']."
                                      WHERE id_parent IN(".$advertisements_ids[$key]['ids'].")");
                $s_day = $db->fetchall("SELECT ".$sys_tables['context_stats_show_day'].".id_parent, COUNT(*) AS amount
                                        FROM ".$sys_tables['context_stats_show_day']."
                                        WHERE id_parent IN(".$advertisements_ids[$key]['ids'].")
                                        GROUP BY ".$sys_tables['context_stats_show_day'].".id_parent",'id_parent');
                $s_day_sum = 0;
                if(!empty($s_day)) foreach($s_day as $k=>$i) $s_day_sum += $i['amount'];
                $c_day = $db->fetchall("SELECT ".$sys_tables['context_stats_click_day'].".id_parent, COUNT(*) AS amount
                                        FROM ".$sys_tables['context_stats_click_day']."
                                        WHERE id_parent IN(".$advertisements_ids[$key]['ids'].")
                                        GROUP BY ".$sys_tables['context_stats_click_day'].".id_parent",'id_parent');
                $c_day_sum = 0;
                if(!empty($c_day)) foreach($c_day as $k=>$i) $c_day_sum += $i['amount'];
                $campaigns_list[$key]['shows'] = (!empty($s_day_sum) ? $s_day_sum : 0) + (!empty($s_full) ? $s_full['amount'] : 0);
                $campaigns_list[$key]['clicks'] = (!empty($c_day_sum) ? $c_day_sum : 0) + (!empty($c_full) ? $c_full['amount'] : 0);
                $campaigns_list[$key]['CTR'] = (empty($campaigns_list[$key]['shows']) ? 0 : $campaigns_list[$key]['clicks'] / ($campaigns_list[$key]['shows']*1.0) * 100.0);
                $campaigns_list[$key]['CTR'] = number_format($campaigns_list[$key]['CTR'],2);
                $campaigns_list[$key]['moder'] = (empty($moder_advertisements[$key]) ? 0 : $moder_advertisements[$key]);
                if(!empty($users_info[$campaigns_list[$key]['id_user']])){
                    $user_info = $users_info[$campaigns_list[$key]['id_user']];
                    $campaigns_list[$key]['user_id'] = $user_info['id'];
                    $campaigns_list[$key]['user_email'] = $user_info['email'];
                    $campaigns_list[$key]['user_login'] = $user_info['login'];
                    $campaigns_list[$key]['user_name'] = $user_info['name'];
                    $campaigns_list[$key]['user_group_name'] = $user_info['group_name'];
                    $campaigns_list[$key]['user_agency_title'] = $user_info['agency_title'];
                }
            }
        }
        
        Response::SetArray('list',$campaigns_list);
        
        //список пользователей для фильтра
        $f_users_list = $db->fetchall("SELECT ".$sys_tables['context_campaigns'].".id_user AS id,
                                              CONCAT(".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname) AS name,
                                              ".$sys_tables['agencies'].".title AS agency_title
                                       FROM ".$sys_tables['context_campaigns']."
                                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['context_campaigns'].".id_user
                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                       GROUP BY ".$sys_tables['context_campaigns'].".id_user");
        Response::SetArray('f_users_list',$f_users_list);
        
        $module_template = 'admin_campaigns_list.html';
        break;
}
// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>