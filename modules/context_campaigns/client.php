<?php
require_once('includes/class.context_campaigns.php');
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos' ) )  require_once('includes/class.photos.php');;
$GLOBALS['css_set'][] = '/modules/context_campaigns/style.css';
$GLOBALS['css_set'][] = '/css/style-cabinet.css';
$GLOBALS['js_set'][] = '/js/form.validate.js';

//для фильтра справа
$GLOBALS['js_set'][] = '/js/jquery.ajax.filter.js';
//автозаполнение
$GLOBALS['js_set'][] = '/js/jquery.typewatch.js';

if(!$ajax_mode){
    //для datetimepicker
    $GLOBALS['js_set'][] = '/js/datetimepicker/jquery.datetimepicker.js';
    $GLOBALS['css_set'][] = '/js/datetimepicker/jquery.datetimepicker.css';
}

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

//если это кто-то не тот, возвращаем 404
if( !in_array($action,array('block','click','show')) && 
   !(!empty($auth->id_agency) || $auth->id_group == 101 || $auth->id_group == 10 || $auth->id_group == 2 || $auth->id_group == 3 || (empty($auth->id_agency) && $auth->id_tarif>0))) $this_page->http_code=404;
$allowed_users = $auth->id;
//если это менеджер, читаем его id, дочитываем id кампаний его компаний
if($auth->id_group == 3){
    $manager_id = $db->fetch("SELECT id FROM ".$sys_tables['managers']." WHERE email = '".$auth->email."'")['id'];
    if(!empty($manager_id)){
        $agencies_ids = $db->fetch("SELECT GROUP_CONCAT(".$sys_tables['users'].".id) AS ids
                                   FROM ".$sys_tables['users']." 
                                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                   LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['users'].".email = ".$sys_tables['managers'].".email
                                   WHERE ".$sys_tables['agencies'].".id_manager = ".$manager_id." AND ".$sys_tables['users'].".id_agency > 0")['ids'];
        if(!empty($agencies_ids)) $allowed_users = trim($auth->id.",".$agencies_ids,',');
    }
    
}
   
// мэппинги модуля

//не показывать верхний баннер
Response::SetBoolean('not_show_top_banner',true);
Response::SetString('page','context_campaigns');
// обработка общих action-ов
switch(true){
    //карточка
    case $action=='block':
        $source = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        //определяем где нужно будет рисовать баннер
        switch($source){
            case 'search-right':
                //читаем параметры поиска, по которым нужно будет выдать баннер
                $search_parameters = Request::GetArray('search_parameters',METHOD_POST);
                if(empty($search_parameters)){
                    $ajax_result['ok'] = false;
                    return 0;
                }
                unset($search_parameters['page']);
                $campaign_info = contextCampaigns::findItem("search-right",$search_parameters);
                //$ajax_result['res'] = contextCampaigns::findItem2("search-right",$search_parameters);
                //$campaign_data = contextCampaigns::getItem($campaign_info['id_context']);
                if(empty($campaign_info)) break;
                if(empty($campaign_info['txt_blocks'])){
                    $campaign_data = contextCampaigns::getItems($campaign_info);
                    $campaign_data['txt_blocks'] = false;
                } 
                else $campaign_data = contextCampaigns::getItems($campaign_info['ids']);
            break;
            case 'search-center':
                //читаем параметры поиска, по которым нужно будет выдать баннер
                $search_parameters = Request::GetArray('search_parameters',METHOD_POST);
                if(empty($search_parameters)){
                    $ajax_result['ok'] = false;
                    return 0;
                }
                unset($search_parameters['page']);
                $campaign_info = contextCampaigns::findItem("search-center",$search_parameters);
                if(empty($campaign_info)) break;
                if(empty($campaign_info['txt_blocks'])){
                    $campaign_data = contextCampaigns::getItems($campaign_info);
                    $campaign_data['txt_blocks'] = false;
                } 
                else $campaign_data = contextCampaigns::getItems($campaign_info['ids']);
            break;
            case 'item':
                //читаем тип недвижимости и id объекта, на карточке которого будет баннер
                $estate_type = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                
                $object_id = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                //если указано, отмечаем что это не просто карточка, а фотогалерея карточки
                if(!empty($this_page->page_parameters[4])) $place_alias = "item-pg";
                else $place_alias="item";
                switch($estate_type){
                    case 'live':
                        $estateItem = new EstateItemLive($object_id);
                        break;
                    case 'build':
                        $estateItem = new EstateItemBuild($object_id);
                        break;
                    case 'commercial':
                        $estateItem = new EstateItemCommercial($object_id);
                        break;
                    case 'country':
                        $estateItem = new EstateItemCountry($object_id);
                        break;
                    case 'inter':
                        $estateItem = new EstateItemInter($object_id);
                        break;
                    default:
                        exit(0);
                        break;
                }
                $info = $estateItem->getInfo();
                $item = $estateItem->getData();
                $info['estate_type'] = $estate_type;
                $info['deal_type'] = $item['rent'];
                $info['price'] = $item['cost'];
                $info['rooms'] = (!empty($item['rooms_sale']))?$item['rooms_sale']:0;
                /*
                $campaign_info = contextCampaigns::findItem($place_alias,false,$info);
                $campaign_data = contextCampaigns::getItems($campaign_info['id_context']);
                */
                $campaign_info = contextCampaigns::findItem($place_alias,false,$info);
                if(empty($campaign_info)) break;
                if(empty($campaign_info['txt_blocks'])){
                    $campaign_data = contextCampaigns::getItems($campaign_info);
                    $campaign_data['txt_blocks'] = false;
                } 
                else $campaign_data = contextCampaigns::getItems($campaign_info['ids']);
            break;
        }
        if(!empty($campaign_data)){
            $ajax_result['ok'] = true;
            if(!empty($place_alias)) Response::SetBoolean('place_alias',$place_alias);
            Response::SetArray('campaign_data',$campaign_data);
            //для не-роботов записываем показ
            if(!Host::$is_bot){
                //в зависимости от типа и количества блоков встатвляем одну или много строчек статистики
                
                if(!empty($campaign_data['txt_blocks'])){
                    $sql_values = array();
                    unset($campaign_data['txt_blocks']);
                    foreach($campaign_data as $key=>$item){
                        $sql_values[] = " (".$item['id'].", '".Host::getUserIp()."','".$db->real_escape_string($_SERVER['HTTP_USER_AGENT'])."','".Host::getRefererURL()."')";
                    }
                    $sql_values = implode(',',$sql_values);
                    $campaign_data['txt_blocks'] = true;
                }
                else $sql_values = " (".$campaign_data['id'].", '".Host::getUserIp()."','".$db->real_escape_string($_SERVER['HTTP_USER_AGENT'])."','".Host::getRefererURL()."')";
                
                $db->querys("INSERT INTO ".$sys_tables['context_stats_show_day']." (id_parent, ip, browser, ref) VALUES ".$sql_values);
            }
            
            $ajax_result['release_ydirect'] = empty($this_page->page_parameters[4]);
            $ajax_result['place'] = (empty($place_alias)?"":$place_alias);
        }
        else{
            $ajax_result['ok'] = false;
            $ajax_result['release_ydirect'] = empty($this_page->page_parameters[4]);
        }
        $module_template = 'item.html';
        break;
    // запись статистики клика
    case $action=='click': 
        if($ajax_mode){
            $id = Request::GetInteger('id',METHOD_POST);
            $from = Request::GetString('from',METHOD_POST);
            $ref = Request::GetString('ref',METHOD_POST);
            
            //по списку классов в $from определяем, откуда был клик
            switch(true){
                case preg_match('/search/',$from): $from = 1;break;
                case preg_match('/item/',$from): $from = 2;break;
                case preg_match('/help/',$from): $from = 3;break;
            }
            
            //1 клик в час (поменено 09042015, раньше был в сутки)
            $time = $db->fetch("SELECT TIMESTAMPDIFF(HOUR, `datetime`, NOW()) as `time` FROM ".$sys_tables['context_stats_click_day']." WHERE id_parent = ? AND ip = ? ORDER BY id DESC",$id, Host::getUserIp());
            if(empty($ref)) $ref = '';
            if($id>0 && !Host::$is_bot && (empty($time) || $time['time']>=1)){
                $res=$db->querys("INSERT INTO ".$sys_tables['context_stats_click_day']." SET `id_parent`=?, `from` = ?, ref=?, real_ref=?, ip=?",$id,$from,$ref,Host::getRefererURL(),Host::getUserIp());
                $ajax_result['ok'] = $res;
                //если все хорошо, осуществляем списание
                if($res){
                    //читаем id пользователя, чей баннер щелкнули и стоимость за клик (определяем по месторасположению)
                    $banner_data = $db->fetch("SELECT ".$sys_tables['context_advertisements'].".id_user,
                                                      ".$sys_tables['context_advertisements'].".id_campaign,
                                                      ".$sys_tables['context_advertisements'].".url,
                                                      ".$sys_tables['context_campaigns'].".balance,
                                                      ".$sys_tables['context_campaigns'].".title AS campaign_title,
                                                      ".$sys_tables['context_places'].".click_cost,
                                                      ".$sys_tables['context_places'].".outer_click_cost
                                               FROM ".$sys_tables['context_advertisements']."
                                               LEFT JOIN ".$sys_tables['context_campaigns']." ON ".$sys_tables['context_campaigns'].".id = ".$sys_tables['context_advertisements'].".id_campaign
                                               LEFT JOIN ".$sys_tables['context_places']." ON ".$sys_tables['context_places'].".id = ".$sys_tables['context_advertisements'].".id_place
                                               WHERE ".$sys_tables['context_advertisements'].".id = ".$id);
                                               
                    //проверяем куда ссылку(внешняя или внутренняя)
                    if(!preg_match('/^\/\/www\.bsn\.ru/',$banner_data['url'])) $banner_data['click_cost'] = $banner_data['outer_click_cost'];
                    
                    //читаем id кампании, к которой относится объявление
                    $id_campaign = $banner_data['id_campaign'];
                    
                    //списание с баланса кампании
                    $banner_data['balance'] -= $banner_data['click_cost'];
                    $db->querys("UPDATE ".$sys_tables['context_campaigns']." SET balance = ? WHERE id = ?",$banner_data['balance'],$id_campaign);
                    
                    //читаем максимальную стоимость клика для данной кампании
                    $max_click = $db->fetch("SELECT MAX(click_cost) AS max_click
                                             FROM ".$sys_tables['context_advertisements']."
                                             LEFT JOIN ".$sys_tables['context_places']." ON ".$sys_tables['context_places'].".id = ".$sys_tables['context_advertisements'].".id_place
                                             WHERE id_campaign = 1")['max_click'];
                    //читаем данные по компании, чье объявление
                    $owner_info = $db->fetch("SELECT ".$sys_tables['agencies'].".email,
                                                      ".$sys_tables['agencies'].".title,
                                                      ".$sys_tables['managers'].".email AS manager_email,
                                                      ".$sys_tables['managers'].".name AS manager_name,
                                                      ".$sys_tables['users'].".email AS user_email,
                                                      ".$sys_tables['users'].".balance AS user_balance,
                                                      ".$sys_tables['users'].".name AS user_name
                                                FROM ".$sys_tables['users']."
                                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                                                WHERE ".$sys_tables['users'].".id = ".$banner_data['id_user']);
                    //если баланса кампании не хватает на 10 самых больших кликов, уведомляем клиента о низком балансе
                    if((($banner_data['balance'] - $banner_data['click_cost'])<10*$max_click) && (($banner_data['balance'] - $banner_data['click_cost'])>0)){
                        $notification_data['cmp_id'] = $banner_data['id_campaign'];
                        $notification_data['cmp_title'] = $banner_data['campaign_title'];
                        $notification_data['agency_email'] = ((empty($owner_info['email']))?$owner_info['user_email']:$owner_info['email']);
                        $notification_data['agency_title'] = ((empty($owner_info['title']))?$owner_info['user_name']:$owner_info['title']);
                        if(!empty($owner_info['manager_email'])){
                            $notification_data['manager_email'] = $owner_info['manager_email'];
                            $notification_data['manager_name'] = explode(' ',$owner_info['manager_name'])[0];
                        }
                        $notification_data['balance'] = $banner_data['balance'] - $banner_data['click_cost'];
                        //уведомляем компанию и менеджера
                        contextCampaigns::Notification(6,$notification_data,false,false);
                    }
                    
                    $owner_info['user_balance'] -= $banner_data['click_cost'];
                    //списание с баланса пользователя
                    $db->querys("UPDATE ".$sys_tables['users']." SET balance = ".$owner_info['user_balance']." WHERE id = ?",$banner_data['id_user']);
                    
                    //делаем запись в context_finances
                    $db->querys("INSERT INTO ".$sys_tables['context_finances']." SET id_parent = ?, id_user = ?, expenditure = ?, income = 0",$id,$banner_data['id_user'],$banner_data['click_cost']);
                    //делаем запись в users_finances
                    $db->querys("INSERT INTO ".$sys_tables['users_finances']." SET id_user = ?, id_parent = ?, obj_type = 'context_banner', expenditure = ?, income = 0",$banner_data['id_user'],$id,$banner_data['click_cost']);
                    
                    //если баланс кампании оказался <=0, убираем ее и все ее объявления в архив и оповещаем клиента и менеджера
                    if(($banner_data['balance'] < $banner_data['click_cost'])){
                        $notification_data['cmp_id'] = $banner_data['id_campaign'];
                        $notification_data['cmp_title'] = $banner_data['campaign_title'];
                        $notification_data['agency_email'] = $owner_info['email'];
                        $notification_data['agency_title'] = $owner_info['title'];
                        $notification_data['manager_email'] = $owner_info['manager_email'];
                        $notification_data['manager_name'] = explode(' ',$owner_info['manager_name'])[0];
                        $notification_data['balance'] = $banner_data['balance'] - $banner_data['click_cost'];
                        //все объявления кампании идут в архив
                        $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET published = 2 WHERE id_campaign = ?",$id_campaign);
                        //убираем кампанию в архив и ставим ей нулевой баланс
                        $db->querys("UPDATE ".$sys_tables['context_campaigns']." SET published = 2, balance = 0 WHERE id = ?",$id_campaign);
                        //уведомляем компанию и менеджера
                        contextCampaigns::Notification(7,$notification_data,false,false);
                    }
                    //если баланс пользователя <=0, убираем все его кампании в архив
                    $user_balance = $db->fetch("SELECT balance FROM ".$sys_tables['users']." WHERE id = ".$banner_data['id_user'])['balance'];
                    if($user_balance<=0){
                        //устанавливаем пользователю нулевой баланс
                        $db->querys("UPDATE ".$sys_tables['users']." SET balance = 0 WHERE id = ?",$banner_data['id_user']);
                        //убираем все его кампании в архив и устанавливаем им нулевой баланс, убираем в архив все объявления
                        $db->querys("UPDATE ".$sys_tables['context_campaigns']." SET published = 2 AND balance = 0 WHERE id_user = ?",$banner_data['id_user']);
                        $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET published = 2 WHERE id_user = ?",$banner_data['id_user']);
                    }
                }
                //сохранение статистики показов для метки
                $session_marker = Session::GetString('marker');
                if(!empty($session_marker)) $db->querys("INSERT INTO ".$sys_tables['markers_stats_day_clicks']." SET id_parent=?",$session_marker);
            }
        } else $this_page->http_code=404;
        break;
    //запись статистики показов
    case $action == 'show': 
        if($ajax_mode && empty(Host::$is_bot)){
            $id_campaign = Request::GetArray('id_campaign',METHOD_POST);
            if(!empty($offers)){
                if(!empty($objects) || !empty($packets)) contextCampaigns::Statistics('show',$objects,$packets);
                $ajax_result['ok'] = true;
            }
        } else $this_page->http_code=404;
        break;
    case $action == 'subways':
        if($ajax_mode) {
            $selected_items = Request::GetArray('selected', METHOD_POST);
            $sql = "SELECT id, `txt_value` AS title
                    FROM ".$sys_tables['context_tags']."
                    WHERE txt_field = 'subways'
                    ORDER BY `title`";
            $list = $db->fetchall($sql, false);
            $ajax_result['ok'] = !empty($list);
            if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['title'] = 'Станции Санкт-Петербургского метрополитена';
        } else $this_page->http_code=404; 
        break;
    case $action == 'districts':
        if($ajax_mode) {
            $selected_items = Request::GetArray('selected', METHOD_POST);
            $sql = "SELECT id, `txt_value` AS title
                    FROM ".$sys_tables['context_tags']."
                    WHERE txt_field = 'districts'
                    ORDER BY `title`";
            $list = $db->fetchall($sql, false);
            $ajax_result['ok'] = !empty($list);
            if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['title'] = 'Административные районы Санкт-Петербурга';
        } else $this_page->http_code=404; 
        break;
    case $action == 'district_areas':
        if($ajax_mode) {
            $selected_items = Request::GetArray('selected', METHOD_POST);
            $sql = "SELECT id, `txt_value` AS title
                    FROM ".$sys_tables['context_tags']."
                    WHERE txt_field = 'district_areas'
                    ORDER BY `title`";
            $list = $db->fetchall($sql, false);
            $ajax_result['ok'] = !empty($list);
            if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['title'] = 'Районы Ленинградской области';
        } else $this_page->http_code=404; 
        break;
    case $action == 'object_types':
        if($ajax_mode) {
            $selected_items = Request::GetArray('selected', METHOD_POST);
            $sql = "SELECT id,txt_field,`txt_value` AS title, '' AS estate_type
                    FROM ".$sys_tables['context_tags']."
                    WHERE txt_field LIKE '%type_object%'
                    ORDER BY txt_field";
            $list = $db->fetchall($sql, false);
            //читаем список тегов, общих для нескольких типов недвижимости
            $sql = "SELECT `txt_value` AS title, COUNT(*) AS amount, GROUP_CONCAT(id) AS ids
                    FROM ".$sys_tables['context_tags']."
                    WHERE txt_field LIKE '%type_object%'
                    GROUP BY title";
            $list_equal = $db->fetchall($sql,false);
            
            //для тегов, которые общие, делаем дополнительное поле - там будут скобки с типом
            foreach($list_equal as $key=>$item){
                if($item['amount']>1){
                    $ids = explode(',',$item['ids']);
                    foreach($list as $list_key=>$list_item){
                        //если тег в списке общих, пишем ему дополнительное поле
                        if(in_array($list_item['id'],$ids)){
                            switch(TRUE){
                                case preg_match('/live/',$list_item['txt_field']): $list[$list_key]['estate_type'] = "(Жилая)";break;
                                case preg_match('/commercial/',$list_item['txt_field']): $list[$list_key]['estate_type'] = "(Коммерческая)";break;
                                case preg_match('/country/',$list_item['txt_field']): $list[$list_key]['estate_type'] = "(Загородная)";break;
                            }
                        }
                    }
                }
            }
            $ajax_result['ok'] = !empty($list);
            if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['title'] = 'Типы объектов недвижимости';
            $ajax_result['split_on'] = "txt_field";
        } else $this_page->http_code=404;
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////
   // список рекламных кампаний
   ////////////////////////////////////////////////////////////////////////////////////////////////
    //список объявлений рекламной кампании
    case (!empty($action) && Validate::isDigit($action)):
        
        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        $id_campaign = Convert::ToInt($action);
        if(!empty($id_campaign))
            $campaign_info = $db->fetch("SELECT * FROM ".$sys_tables['context_campaigns']." WHERE id = ".$id_campaign);
        Response::SetInteger('campaign_id',$id_campaign);
        Response::SetArray('campaign_info',$campaign_info);
        //переопределяем $action
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        //запоминаем основное действие над рекламным блоком
        $main_action = $action;
        //////////////
        //работа с рекламными блоками кампании (отрисовка формы блоком или прием submit)
        switch(true){
            //добавление/создание рекламного блока
            case $action == 'add':
            case $action == 'edit':
                if($ajax_mode){
                    $id_block = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                    //переопределяем $action
                    $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                    switch($action){
                        //редактирование данных рекламного блока (не таргетинга)
                        default:
                            // мэппинги модуля
                            $mapping = include('modules/members/conf_mapping.php');
                            //получаем id объекта
                            $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                            if($main_action=='add'){
                                // создание болванки новой записи
                                $info = $db->prepareNewRecord($sys_tables['context_advertisements']);
                            } else {
                                // получение данных из БД
                                $info = $db->fetch("SELECT ".$sys_tables['context_advertisements'].".*,
                                                    ".$sys_tables['context_advertisements_photos'].".name as photo,
                                                    SUBSTRING(".$sys_tables['context_advertisements_photos'].".name,1,2) AS folder
                                                    FROM ".$sys_tables['context_advertisements']."
                                                    LEFT JOIN ".$sys_tables['context_advertisements_photos']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_advertisements_photos'].".id_parent
                                                    WHERE ".$sys_tables['context_advertisements'].".id=?", $id) ;
                            }
                            Response::SetString('img_folder', Config::$values['img_folders']['context_advertisements']); // папка для картинок кампаний
                            
                            // перенос дефолтных (считанных из базы) значений в мэппинг формы, Response данных картинки
                            if(!empty($info)){
                                if(!empty($info['folder'])){
                                    Response::SetString('folder',$info['folder']);
                                    Response::SetString('img_name',$info['photo']);
                                }
                                foreach($info as $key=>$field){
                                    if(!empty($mapping['context_advertisements'][$key])) $mapping['context_advertisements'][$key]['value'] = $info[$key];
                                }
                            }
                            //формируем псевдо-тип "Текст" - Изображение + текст справа или в карточке
                            if($mapping['context_advertisements']['block_type']['value'] == 2 && in_array($mapping['context_advertisements']['id_place']['value'],array(1,2)))
                                $mapping['context_advertisements']['block_type']['value'] = 3;
                            
                            //// формирование дополнительных данных для формы (не из основной таблицы)
                            //типы недвижимости
                            $mapping['context_advertisements']['estate_type']['values'][1] = 'Жилая';
                            $mapping['context_advertisements']['estate_type']['values'][2] = 'Стройка';
                            $mapping['context_advertisements']['estate_type']['values'][3] = 'Коммерческая';
                            $mapping['context_advertisements']['estate_type']['values'][4] = 'Загородная';
                            
                            ////////
                            /////таргетинг для этой кампании
                            //список выбранных условий для первоначального отображения в форме (группируем выбранные теги по разделам (комнатность, метро,...))
                            $targeting_list = $db->fetchall("SELECT ".$sys_tables['context_tags'].".txt_field,
                                                                    GROUP_CONCAT(".$sys_tables['context_tags'].".id) AS field_ids,
                                                                    GROUP_CONCAT(".$sys_tables['context_tags'].".source_id) AS sources_ids,
                                                                    GROUP_CONCAT(".$sys_tables['context_tags'].".txt_value SEPARATOR '~') AS field_values,
                                                                    GROUP_CONCAT(".$sys_tables['context_tags'].".txt_field) AS field_titles
                                                             FROM ".$sys_tables['context_tags_conformity']."
                                                             LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['context_tags_conformity'].".id_tag = ".$sys_tables['context_tags'].".id
                                                             WHERE ".$sys_tables['context_tags_conformity'].".id_context = ".$id."
                                                             GROUP BY txt_field",'txt_field');
                            ////сразу набираем данные для всплывашки
                            ///типы объектов
                            
                            $sql = "SELECT id,txt_field,`txt_value` AS title, '' AS estate_type
                                    FROM ".$sys_tables['context_tags']."
                                    WHERE txt_field LIKE '%type_object%'
                                    ORDER BY txt_field";
                            $types_all = $db->fetchall($sql, false);
                            //читаем список тегов, общих для нескольких типов недвижимости
                            $sql = "SELECT `txt_value` AS title, COUNT(*) AS amount, GROUP_CONCAT(id) AS ids
                                    FROM ".$sys_tables['context_tags']."
                                    WHERE txt_field LIKE '%type_object%'
                                    GROUP BY title";
                            $types_equal = $db->fetchall($sql,false);
                            
                            //для тегов, которые общие, делаем дополнительное поле - там будут скобки с типом
                            foreach($types_equal as $key=>$item){
                                if($item['amount']>1){
                                    $ids = explode(',',$item['ids']);
                                    foreach($types_all as $list_key=>$list_item){
                                        if(in_array($list_item['id'],$ids))
                                            switch(TRUE){
                                                case preg_match('/live/',$list_item['txt_field']): $types_all[$list_key]['estate_type'] = "(Жилая)";break;
                                                case preg_match('/commercial/',$list_item['txt_field']): $types_all[$list_key]['estate_type'] = "(Коммерческая)";break;
                                                case preg_match('/country/',$list_item['txt_field']): $types_all[$list_key]['estate_type'] = "(Загородная)";break;
                                            }
                                    }
                                }
                            }
                            $tags_filter['type-objects'] = $types_all;
                            $selected_items = array();
                            if(!empty($targeting_list['type_objects_live'])) $selected_items[] = $targeting_list['type_objects_live']['field_ids'];
                            if(!empty($targeting_list['type_objects_commercial'])) $selected_items[] = $targeting_list['type_objects_commercial']['field_ids'];
                            if(!empty($targeting_list['type_objects_country'])) $selected_items[] = $targeting_list['type_objects_country']['field_ids'];
                            $selected_items = explode(',',implode(',',$selected_items));
                            if(!empty($selected_items)) foreach($tags_filter['type-objects'] as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $tags_filter['type-objects'][$lkey]['selected'] = true;
                            $tags_filter['type-objects']['selected'] = implode(',',$selected_items);
                            ///районы
                            $tags_filter['districts'] = $db->fetchall("SELECT id AS id_tag,txt_value AS title,source_id AS id FROM ".$sys_tables['context_tags']." WHERE txt_field = 'districts' ORDER BY title");
                            if(!empty($targeting_list['districts'])) {
                                foreach($tags_filter['districts'] as $k=>$item){
                                    if(in_array($item['id'], explode(',',$targeting_list['districts']['sources_ids']))){
                                        $tags_filter['districts'][$k]['on'] = true;
                                        $tags_filter['districts']['selected'][] = $tags_filter['districts'][$k]['id'];
                                    } 
                                }
                                $tags_filter['districts']['selected'] = implode(',',$tags_filter['districts']['selected']);
                            }
                            //районы области
                            //$tags_filter['district_areas'] = $db->fetchall("SELECT id, offname as title FROM ".$sys_tables['geodata']." WHERE a_level = 2 ORDER BY offname");
                            $tags_filter['district_areas'] = $db->fetchall("SELECT id AS id_tag,txt_value AS title,source_id AS id FROM ".$sys_tables['context_tags']." WHERE txt_field = 'district_areas' ORDER BY title");
                            if(!empty($targeting_list['district_areas'])) {
                                foreach($tags_filter['district_areas'] as $k=>$item){
                                    if(in_array($item['id'], explode(',',$targeting_list['district_areas']['sources_ids']))){
                                        $tags_filter['district_areas'][$k]['on'] = true;
                                        $tags_filter['district_areas']['selected'][] = $tags_filter['district_areas'][$k]['id'];
                                    }
                                }
                                $tags_filter['district_areas']['selected'] = implode(',',$tags_filter['district_areas']['selected']);
                            }
                            //метро
                            //$tags_filter['subways'] = $db->fetchall("SELECT id,title, id_subway_line as line_id FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ORDER BY title");
                            $tags_filter['subways'] = $db->fetchall("SELECT ".$sys_tables['context_tags'].".id AS id_tag,
                                                                            ".$sys_tables['context_tags'].".txt_value AS title,
                                                                            ".$sys_tables['context_tags'].".source_id AS id
                                                                     FROM ".$sys_tables['context_tags']." 
                                                                     WHERE txt_field = 'subways' ORDER BY title");
                            if(!empty($targeting_list['subways'])) {
                                foreach($tags_filter['subways'] as $k=>$item){
                                    if(in_array($item['id'], explode(',',$targeting_list['subways']['sources_ids']))){
                                        $tags_filter['subways'][$k]['on'] = true;
                                        $tags_filter['subways']['selected'][] = $tags_filter['subways'][$k]['id'];
                                    } 
                                }
                                $tags_filter['subways']['selected'] = implode(',',$tags_filter['subways']['selected']);
                            }
                            Response::SetArray('tags_filter',$tags_filter);
                            if(!empty($targeting_list)){
                                
                                //здесь будем накапливать данные по типам недвижимости, чтобы потом запихнуть в один массив
                                $types_field_ids = "";$types_source_ids = "";$types_field_values = "";$types_field_titles = "";
                                //перебираем категории тегов, формируем список
                                foreach($targeting_list as $key=>$values){
                                    //для длинных - читаем отдельно, потому что group_concat не лезет в лимит
                                    if(strlen($values['field_values']) > 1020){
                                        $field_data = $db->fetchall("SELECT id,source_id,txt_value,txt_field
                                                                     FROM ".$sys_tables['context_tags']."
                                                                     WHERE id IN (".$values['field_ids'].")");
                                        $fields_list = array();
                                        $field_values = array();
                                        $field_titles = array();
                                        $tags_ids = array();
                                        $sources_ids = array();
                                        foreach($field_data as $k=>$item){
                                            $field_values[] = $item['txt_value'];
                                            $field_titles[] = $item['txt_field'];
                                            $tags_ids[] = $item['id'];
                                            $sources_ids[] = $item['source_id'];
                                        }
                                    }
                                    else{
                                        $field_values = explode('~',$values['field_values']);
                                        $field_titles = explode(',',$values['field_titles']);
                                        $tags_ids = explode(',',$values['field_ids']);
                                        $sources_ids = explode(',',$values['sources_ids']);
                                        $sources_check = preg_replace('/,0/sui','',$values['sources_ids']);
                                    }
                                    
                                    $fields_list = array();
                                    
                                    //в зависимости от того, есть ли у тега привязка к таблице, записываем данные
                                    if(!empty($tags_ids[0])){
                                        foreach($tags_ids as $k=>$field_id){
                                            if(empty($sources_ids[$k])){
                                                $fields_list[$field_id] = array('value'=>$field_values[$k]);
                                            }
                                            else{
                                                $fields_list[$sources_ids[$k]] = array('tag_id'=>((!empty($tags_ids[$k]))?$tags_ids[$k]:""),
                                                                                       'value'=>(!empty($field_values[$k]))?$field_values[$k]:"",
                                                                                       'field_title'=>(!empty($field_titles[$k]))?$field_titles[$k]:"");
                                            }
                                        }
                                        
                                        if(count($fields_list)>0) $targeting_list[$targeting_list[$key]['txt_field']] = $fields_list;
                                        //unset($targeting_list[$key]);
                                    }
                                    
                                    
                                    //собираем типы объектов в один набор
                                    if(preg_match('/type_objects/',$values['txt_field'])){
                                        $types_field_ids .= ((empty($types_field_ids))?$values['field_ids']:','.$values['field_ids']);
                                        $types_source_ids .= ((empty($types_source_ids))?$values['sources_ids']:','.$values['sources_ids']);
                                        $types_field_values .= ((empty($types_field_values))?$values['field_values']:'~'.$values['field_values']);
                                        $types_field_titles .= ((empty($types_field_titles))?$values['field_titles']:','.$values['field_titles']);
                                    }
                                }
                            }
                            
                            //читаем набор тегов для комнатности вне зависимости от того, подходит она под набор недвижимости или нет
                            //чтобы довыбрать теги, которых нет в таргетинге (комнаты нужно показывать все)
                            $condition =((!empty($targeting_list['rooms']))?",IF(id IN(".implode(',',array_keys($targeting_list['rooms']))."),'selected','') AS status ":"");
                            $rooms = $db->fetchall("SELECT ".$sys_tables['context_tags'].".id,".$sys_tables['context_tags'].".txt_value AS value
                                                           ".$condition."
                                                    FROM ".$sys_tables['context_tags']."
                                                    WHERE ".$sys_tables['context_tags'].".txt_field='rooms'",'id');
                            $targeting_list['rooms'] = $rooms;
                            
                            //заполняем информацию по тегам "тип объекта"
                            if(!empty($types_field_ids)){
                                $types_field_ids = explode(',',$types_field_ids);
                                $types_source_ids = explode(',',$types_source_ids);
                                $types_field_values = explode('~',$types_field_values);
                                $types_field_titles = explode(',',$types_field_titles);
                                Response::SetString('type_objects_tags',implode(',',$types_field_ids));
                                $type_objects = array();
                                //заполняем массив типов объектов из строчек
                                foreach($types_field_ids as $key=>$item){
                                    $type_objects[] = array('id'=>$types_field_ids[$key],'source_id'=>$types_source_ids[$key],'value'=>$types_field_values[$key],'txt_field'=>$types_field_titles[$key],'estate_type'=>"");
                                }
                                //читаем список тегов, общих для нескольких типов недвижимости
                                $sql = "SELECT `txt_value` AS title, COUNT(*) AS amount, GROUP_CONCAT(id) AS ids
                                        FROM ".$sys_tables['context_tags']."
                                        WHERE txt_field LIKE '%type_object%'
                                        GROUP BY title";
                                $list_equal = $db->fetchall($sql,false);
                                //для тегов, которые общие, заполняем дополнительное поле - там будут скобки с типом
                                foreach($list_equal as $key=>$item){
                                    if($item['amount']>1){
                                        $ids = explode(',',$item['ids']);
                                        foreach($type_objects as $to_key=>$list_item){
                                            //если тег в списке общих, пишем ему дополнительное поле
                                            if(in_array($list_item['id'],$ids)){
                                                switch(TRUE){
                                                    case preg_match('/live/',$list_item['txt_field']): $type_objects[$to_key]['estate_type'] = "(Жилая)";break;
                                                    case preg_match('/commercial/',$list_item['txt_field']): $type_objects[$to_key]['estate_type'] = "(Коммерческая)";break;
                                                    case preg_match('/country/',$list_item['txt_field']): $type_objects[$to_key]['estate_type'] = "(Загородная)";break;
                                                }
                                            }
                                        }
                                    }
                                }
                                $targeting_list['type_objects'] = $type_objects;
                            }
                            
                            
                            
                            Response::SetArray('targeting_list',$targeting_list);
                            
                            // получение данных, отправленных из формы
                            $post_parameters = Request::GetParameters(METHOD_POST);
                            // если была отправка формы - начинаем обработку
                            if(!empty($post_parameters['submit'])){
                                //отдельно записываем данные по таргетингу
                                $targeting_data = $post_parameters['targeting_data'];
                                //оставляем только данные из формы, чтобы обработка осталась стандартной
                                $post_parameters = $post_parameters['form_data'];
                                $post_parameters['submit'] = true;
                                
                                //если это редактирование
                                if(!empty($id)){
                                    //переносим ограничения по цене из таргетинга в form_data
                                    $post_parameters['price_floor'] = (!empty($targeting_data['price_floor']))?$targeting_data['price_floor']:0;
                                    $post_parameters['price_top'] = (!empty($targeting_data['price_top']))?$targeting_data['price_top']:0;
                                    //убираем эти поля из таргетинга, так как это не теги
                                    unset($targeting_data['price_floor']);
                                    unset($targeting_data['price_top']);
                                    
                                    $post_parameters['submit'] = true;
                                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                                    
                                    //если тип недвижимости изменился, отвяжем все теги, которые не подходят к новому типу недвижимости
                                    //всегда отвязываем
                                    //$clear_tags = ($post_parameters['estate_type'] != $mapping['context_advertisements']['estate_type']['value']);
                                    
                                    //если изменился тип блока, или если изменилось место размещения, проверяем, изменились ли размеры картинок
                                    if($post_parameters['id_place'] != $mapping['context_advertisements']['id_place']['value'] || 
                                       $post_parameters['block_type'] != $mapping['context_advertisements']['block_type']['value'] ||
                                       $post_parameters['block_type'] == 3){
                                        $sizes = $db->fetchall("SELECT CONCAT(width,height) AS wh FROM ".$sys_tables['context_places']." WHERE id = ".$post_parameters['id_place']." OR id = ".$mapping['context_advertisements']['id_place']['value']);
                                        //если размеры изменились или изменился тип блока, отвязываем картинки, так как они уже не подходят
                                        if(count($sizes) != 1 || $post_parameters['block_type'] != $mapping['context_advertisements']['block_type']['value'] || $post_parameters['block_type'] == 3){
                                            ///удаляем картнки:
                                            //читаем список картинок, которые нужно удалить
                                            $img_list = $db->fetchall("SELECT id FROM ".$sys_tables['context_advertisements_photos']." WHERE id_parent = ".$id_block);
                                            //удаляем файлы картинок
                                            foreach($img_list as $key=>$item)
                                                Photos::Delete($sys_tables['context_advertisements_photos'],$item['id']);
                                            //удаляем картинки из таблицы
                                            $db->querys("DELETE FROM ".$sys_tables['context_advertisements_photos']." WHERE id_parent = ?",$id_block);
                                            //устанавливаем id_main_photo = 0 для данной кампании
                                            $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET id_main_photo = 0 WHERE id = ".$id_block);
                                        }
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
                                //если это редактирование и хотим опубликовать, проверяем, есть ли фотография и указан ли тип объекта (кроме стройки)
                                if(!empty($id) && (int)$mapping['context_advertisements']['published']['value'] == 1){
                                    if($mapping['context_advertisements']['block_type']['value'] == 1){
                                        $has_photo = $db->fetch("SELECT id_main_photo FROM ".$sys_tables['context_advertisements']." WHERE id = ?",$id_block);
                                        if(empty($has_photo['id_main_photo'])){
                                            $response_errors['image'] = " для публикации добавьте картинки";
                                            $errors['published'] = "Для публикации добавьте картинки.";
                                            $mapping['context_advertisements']['published']['value'] = 2;
                                        }else{
                                            $has_photo = $db->fetch("SELECT id FROM ".$sys_tables['context_advertisements_photos']." WHERE id = ?",$has_photo['id_main_photo']);
                                            if(empty($has_photo['id'])){
                                                $response_errors['image'] = " для публикации добавьте картинки";
                                                $errors['published'] = "Для публикации добавьте картинки.";
                                                $mapping['context_advertisements']['published']['value'] = 2;
                                            }
                                        }
                                    }

                                    //флаг, указан ли тип объекта (если только стройка, то он не нужен)
                                    $post_parameters['estate_type'] = preg_replace('/[^1234]/','',$post_parameters['estate_type']);
                                    $has_type = ($post_parameters['estate_type'] == 2)||(!empty($targeting_data['object_types']));
                                    
                                    //флаг, указаны ли цена или метро или район
                                    $has_targeting_data = (!empty($targeting_data['subways'])||!empty($targeting_data['districts'])||!empty($targeting_data['district_areas']));
                                    
                                    //если что-то не указано, пишем ошибку
                                    if(!$has_targeting_data){
                                        $response_errors['targeting_area'] = " для публикации добавьте таргетинг цене, метро, или району(району ЛО)";
                                        $mapping['context_advertisements']['published']['value'] = 2;
                                        $errors['published'] = " для публикации добавьте таргетинг цене, метро, или району(району ЛО)";
                                    }
                                    if(!$has_type){
                                        $response_errors['targeting_type'] = " для публикации добавьте таргетинг по типу объекта";
                                        $errors['published'] = " для публикации добавьте таргетинг по типу объекта";
                                        $mapping['context_advertisements']['published']['value'] = 2;
                                    }
                                }
                                
                                if($mapping['context_advertisements']['block_type']['value'] != 1 && 
                                   (empty($mapping['context_advertisements']['banner_title']['value']) || empty($mapping['context_advertisements']['banner_text']['value'])) ){
                                       $error_value = "Значение не может быть пустым для этого типа объявления";
                                       $response_errors['banner_title'] = $error_value;
                                       $response_errors['banner_text'] = $error_value;
                                       $errors['banner_title'] = $error_value;
                                }
                                
                                // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                                foreach($errors as $key=>$value){
                                    if(!empty($mapping['context_advertisements'][$key])){
                                        $mapping['context_advertisements'][$key]['error'] = $value;
                                        $response_errors[$key] = $value;
                                    } 
                                    
                                }
                                
                                //если публикование и фотка нужна, проверяем ее наличие
                                if($mapping['context_advertisements']['published']['value'] == 1){
                                    
                                }
                                
                                // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                                if(empty($errors)) {
                                    // подготовка всех значений для сохранения
                                    foreach($info as $key=>$field){
                                        if (isset($mapping['context_advertisements'][$key]['value'])) $info[$key] = strip_tags($mapping['context_advertisements'][$key]['value'],'<table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                                    }
                                    
                                    //добавляем id пользователя, создавшего этот блок
                                    if(empty($info['id_user'])) $info['id_user'] = $auth->id;
                                    if(empty($info['id_creator'])) $info['id_creator'] = $auth->id;
                                    //добавляем id рекламной кампании
                                    $info['id_campaign'] = $id_campaign;
                                    
                                    //работаем с таргетингом: 
                                    if(!empty($id) && !empty($targeting_data)){
                                        //удаляем старые теги и добавляем новые
                                        $tags_sql = "INSERT IGNORE INTO ".$sys_tables['context_tags_conformity']." (".$sys_tables['context_tags_conformity'].".id_context,".$sys_tables['context_tags_conformity'].".id_tag) VALUES ";
                                        $tags_values_sql = array();
                                        foreach($targeting_data as $key=>$item){
                                            //разбиваем список тегов в категории и набираем строку запроса
                                            if(!empty($item)){
                                                $tags = explode(',',$item);
                                                foreach($tags as $tag_key=>$tags_item){
                                                    $tags_values_sql []= " (".Convert::ToInt($info['id']).",".Convert::ToInt($tags_item).") ";
                                                }
                                            }
                                        }
                                        if(!empty($tags_values_sql))
                                            $tags_sql .= implode(',',$tags_values_sql);
                                        else $tags_sql = "";
                                    }
                                    // сохранение в БД
                                    if($main_action=='edit'){
                                        //если опубликование, отправляем на модерацию, уведомляем менеджера и компанию
                                        if($info['published'] == 1){
                                            $info['published'] = 3;
                                            $ajax_result['moderation'] = true;
                                            //собираем данные и шлем уведомление
                                            $notification_data['adv_title'] = ((!empty($info['title']))?($info['title']):"#".$info['id']);
                                            $notification_data['adv_id'] = $info['id'];
                                            $notification_data['cmp_title'] = $campaign_info['title'];
                                            //читаем информацию по агентству, чье объявление
                                            $agency_info = $db->fetch("SELECT ".$sys_tables['agencies'].".id,
                                                                              ".$sys_tables['agencies'].".title, 
                                                                              ".$sys_tables['agencies'].".email AS agency_email, 
                                                                              ".$sys_tables['managers'].".name AS manager_name,
                                                                              ".$sys_tables['managers'].".email AS manager_email,
                                                                              ".$sys_tables['users'].".name AS user_name,
                                                                              ".$sys_tables['users'].".email AS user_email
                                                                       FROM ".$sys_tables['users']."
                                                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                                                       LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                                                       WHERE ".$sys_tables['users'].".id = ".$campaign_info['id_user']);
                                            //если информации по агентству нет, то это специалист
                                            if(empty($agency_info['id'])){
                                                $user_info = $db->fetch("SELECT ".$sys_tables['users'].".id,
                                                                                ".$sys_tables['users'].".email,
                                                                                CONCAT(".$sys_tables['users'].".name,".$sys_tables['users'].".lastname) AS name
                                                                         FROM ".$sys_tables['users']."
                                                                         WHERE ".$sys_tables['users'].".id = ".$auth->id);
                                                //для специалистов вместо менеджера Стас
                                                $notification_data['agency_email'] = $user_info['email'];
                                                $notification_data['agency_title'] = $user_info['name'];
                                                $notification_data['manager_href'] = "//www.bsn.ru/admin/advert_objects/context_campaigns/".$campaign_info['id']."/edit/".$info['id'];
                                                $notification_data['manager_email'] = "pm@bsn.ru";
                                                $notification_data['manager_name'] = 'Стас';
                                                //отмечаем, что это не компания, а именно специалист
                                                $notification_data['is_specialist'] = true;
                                                contextCampaigns::Notification(1,$notification_data,false,true);
                                            }
                                            else{
                                                //уведомляем компанию
                                                
                                                if($agency_info['id'] == 4966 || empty($agency_info['email'])){
                                                    $notification_data['agency_email'] = $agency_info['user_email'];
                                                    $notification_data['agency_title'] = $agency_info['user_name'];
                                                }else{
                                                    $notification_data['agency_email'] = $agency_info['agency_email'];
                                                    $notification_data['agency_title'] = $agency_info['title'];
                                                }
                                                
                                                contextCampaigns::Notification(3,$notification_data,true,false);
                                                $notification_data['agency_title'] = $agency_info['title'];
                                                $notification_data['manager_href'] = "//www.bsn.ru/admin/advert_objects/context_campaigns/".$campaign_info['id']."/edit/".$info['id'];
                                                $notification_data['manager_email'] = $agency_info['manager_email'];
                                                $notification_data['manager_name'] = explode(' ',$agency_info['manager_name'])[0];
                                                //уведомили менеджера
                                                contextCampaigns::Notification(1,$notification_data,false,true);
                                            }
                                            
                                        }
                                        else $ajax_result['archivation'] = '1';
                                        $res = $db->updateFromArray($sys_tables['context_advertisements'], $info, 'id');
                                        //если необходимо, добавляем теги, удалив предварительно предыдущие
                                        $db->querys("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ?",$info['id']);
                                        //убеждаемся что удалили все
                                        $still_exists = $db->fetchall("SELECT id FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ".$info['id']);
                                        $q_counter = 0;
                                        while(count($still_exists)>0 || $q_counter>20){
                                            $db->querys("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ?",$info['id']);
                                            $still_exists = $db->fetchall("SELECT id FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ".$info['id']);
                                            ++$q_counter;
                                        }
                                        if(!empty($tags_sql)){
                                            $res = $res && $db->querys($tags_sql);
                                        }
                                    }else{
                                        $res = $db->insertFromArray($sys_tables['context_advertisements'], $info, 'id');
                                        if(!empty($res)){
                                            $new_id = $db->insert_id;
                                            if(empty($info['id'])) $info['id'] = $new_id;
                                            //если все хорошо, добавляем объявлению теги, удалив предыдущие
                                            $db->querys("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ?",$info['id']);
                                            //убеждаемся что удалили все
                                            $still_exists = $db->fetchall("SELECT id FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ".$info['id']);
                                            $q_counter = 0;
                                            while(count($still_exists)>0 || $q_counter>20){
                                                $db->querys("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ?",$info['id']);
                                                $still_exists = $db->fetchall("SELECT id FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ".$info['id']);
                                                ++$q_counter;
                                            }
                                            if($q_counter > 20) $res = false;
                                            if(!empty($tags_sql)){
                                                $res = $res && $db->querys($tags_sql);
                                            }
                                        }
                                    }
                                    //всегда отвязываем теги, которые перестали подходить по типу недвижимости
                                    //if(!empty($clear_tags)){
                                    if(true){
                                        //готовим ограничения по типу недвижимости
                                        $estate_restrictions = str_split($post_parameters['estate_type'],1);
                                        foreach($estate_restrictions as $key=>$item)
                                            $estate_restrictions[$key] = " estate_type NOT LIKE '%".$estate_restrictions[$key]."%'";
                                        $estate_restrictions = implode(" AND ",$estate_restrictions);
                                        //строчки в таблице соответствия, которые не подходят к новому набору типов недвижимости
                                        $tags_deleted = $db->fetch("SELECT GROUP_CONCAT(id) AS ids, GROUP_CONCAT(id_tag) AS id_tags
                                                                    FROM ".$sys_tables['context_tags_conformity']."
                                                                    WHERE id_context = ".Convert::ToInt($id_block)." AND
                                                                    id_tag IN (SELECT id FROM ".$sys_tables['context_tags']." WHERE ".$estate_restrictions.")");
                                        if(!empty($tags_deleted['ids'])){
                                            //удаляем их из таблицы соответствия
                                            $res = $db->querys("DELETE
                                                               FROM ".$sys_tables['context_tags_conformity']."
                                                               WHERE id_context = ".Convert::ToInt($id_block)." AND 
                                                               id IN (".$tags_deleted['ids'].")");
                                            $tags_deleted = explode(',',$tags_deleted['id_tags']);
                                        }
                                    }
                                    Response::SetBoolean('saved', $res); // результат сохранения
                                    $ajax_result['saved'] = $res;
                                    $ajax_result['ok'] = $res;
                                    $ajax_result['active'] = $info['published'];
                                    $ajax_result['estate_type'] = $info['estate_type'];
                                    $ajax_result['errors'] = (!empty($response_errors))?$response_errors:"";
                                    
                                    
                                    //если все хорошо, отдаем свежие данные для плашки объявления в общем списке
                                    if($ajax_result['ok']){
                                        
                                        //читаем таргетинг (тип объекта и ограничения по цене):
                                        if(!empty($targeting_data['object_types'])){
                                            $tags_object_types = $db->fetchall("SELECT id,txt_value,txt_field FROM ".$sys_tables['context_tags']." WHERE id IN (".$targeting_data['object_types'].")");
                                            //если тег присутствует в списке удаленных, убираем его
                                            if(!empty($tags_deleted))
                                                foreach($tags_object_types as $key=>$item){
                                                    if(in_array($item['id'],$tags_deleted)){
                                                        unset($tags_object_types[$to_key]);
                                                        continue;
                                                    }
                                                }
                                            $sql = "SELECT `txt_value` AS title, COUNT(*) AS amount, GROUP_CONCAT(id) AS ids
                                                    FROM ".$sys_tables['context_tags']."
                                                    WHERE txt_field LIKE '%type_object%'
                                                    GROUP BY title";
                                            $list_equal = $db->fetchall($sql,false);
                                            $ajax_result['adv_info']['tags_info'] = array();
                                            //для тегов, которые общие, заполняем дополнительное поле - там будут скобки с типом
                                            foreach($list_equal as $key=>$item){
                                                if($item['amount']>1){
                                                    $ids = explode(',',$item['ids']);
                                                    foreach($tags_object_types as $to_key=>$list_item){
                                                        if(empty($tags_object_types[$to_key]['estate_type'])) $tags_object_types[$to_key]['estate_type'] = "";
                                                        //если тег в списке общих, пишем ему дополнительное поле
                                                        if(in_array($list_item['id'],$ids)){
                                                            switch(TRUE){
                                                                case preg_match('/live/',$list_item['txt_field']): $tags_object_types[$to_key]['estate_type'] = "(Жилая)";break;
                                                                case preg_match('/commercial/',$list_item['txt_field']): $tags_object_types[$to_key]['estate_type'] = "(Коммерческая)";break;
                                                                case preg_match('/country/',$list_item['txt_field']): $tags_object_types[$to_key]['estate_type'] = "(Загородная)";break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            foreach($tags_object_types as $key=>$value){
                                                $ajax_result['adv_info']['tags_info'][] = $value['txt_value'].$value['estate_type'];
                                            }
                                            $ajax_result['adv_info']['tags_info'] = ((!empty($ajax_result['adv_info']['tags_info']))?'<i>'.implode(',</i><i>',$ajax_result['adv_info']['tags_info']).'</i>':"");
                                        }
                                        
                                        //разбираем данные по типу сделки
                                        $info['deal_text'] = array();
                                        switch(true){
                                            case preg_match('/1/',$info['deal_type']): $info['deal_text'][] = '<i>Аренда</i>';
                                            case preg_match('/2/',$info['deal_type']): $info['deal_text'][] = (empty($info['deal_text']))?'<i>Продажа</i>':'<i>продажа</i>';
                                        }
                                        if(!empty($info['deal_text'])) $info['deal_text'] = implode(' и ',$info['deal_text']);
                                        //разбираем данные по типу недвижимости
                                        $info['estate_text'] = array();
                                        if(preg_match('/2/',$info['estate_type'])) $info['estate_text'][] = '<i>новостроек</i>';
                                        if(preg_match('/1/',$info['estate_type'])) $info['estate_text'][] = '<i>жилой</i>';
                                        if(preg_match('/3/',$info['estate_type'])) $info['estate_text'][] = '<i>коммерческой</i>';
                                        if(preg_match('/4/',$info['estate_type'])) $info['estate_text'][] = '<i>загородной</i>';
                                        if(!empty($info['estate_text'])) $info['estate_text'] = implode(', ',$info['estate_text']);
                                        if(strpos(',',$info['estate_text']) || !strpos('новостро',$info['estate_text'])) $info['deal_text'] = $info['deal_text']." ".$info['estate_text']." недвижимости";
                                        $ajax_result['adv_info']['deal_text'] = $info['deal_text'];
                                        $ajax_result['adv_info']['title'] = $info['title'];
                                        
                                        //проверяем, есть ли картинка, если есть указываем размеры
                                        $sizes = $db->fetch("SELECT width,height FROM ".$sys_tables['context_places']." WHERE id = ".$info['id_place']);
                                        $ajax_result['adv_info']['photo_info'] = ((!empty($info['id_main_photo']))?$sizes['width']."x".$sizes['height']:"Нет картинки");
                                    }
                                }
                                else Response::SetBoolean('errors', true); // признак наличия ошибок
                            }
                            
                            //места размещения
                            $places = $db->fetchall("SELECT id,title AS place_text,height,width,height_txtimage,width_txtimage FROM ".$sys_tables['context_places'],'id');
                            foreach($places as $key=>$val){
                                $mapping['context_advertisements']['id_place']['values'][$val['id']] = array('height'=>$val['height'],
                                                                                                             'width'=>$val['width'],
                                                                                                             'height_intxt'=>$val['height_txtimage'],
                                                                                                             'width_intxt'=>$val['width_txtimage'],
                                                                                                             'text'=>$val['place_text']);
                            }
                            
                            //высота и ширина картинки для исходного выбора
                            if(!empty($mapping['context_advertisements']['id_place']['value'])){
                                Response::SetInteger('item_height',$places[$mapping['context_advertisements']['id_place']['value']]['height']);
                                Response::SetInteger('item_width',$places[$mapping['context_advertisements']['id_place']['value']]['width']);
                            }
                            Response::SetInteger('campaign_id',$id_campaign);
                            
                            // запись данных для отображения на странице
                            Response::SetArray('data_mapping',$mapping['context_advertisements']);
                            //делаем Response, чтобы можно было по изменению места размещения скрывать/показывать поле "Подпись к баннеру"
                            Response::SetString('places_data',json_encode($places));
                            //флаг, что не надо рисовать кнопку "сохранить"
                            Response::SetBoolean('not_show_submit_button',true);
                            
                            //если не было отправки формы, отдаем шаблон
                            if(empty($post_parameters['submit'])){
                                Response::SetBoolean('context_addition',true);
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
                                    Response::SetBoolean('show_subways',true);
                                }
                                if (preg_match('/4/',$mapping['context_advertisements']['estate_type']['value'])){
                                    Response::SetBoolean('estate_country',true);
                                    Response::SetBoolean('show_subway',true);
                                    Response::SetBoolean('show_district_areas',true);
                                }
                                if(preg_match('/3/',$mapping['context_advertisements']['estate_type']['value'])){
                                    Response::SetBoolean('estate_commercial',true);
                                    Response::SetBoolean('show_district_areas',true);
                                }
                                //заполняем данные что показывать по типу сделки
                                if(preg_match('/1/',$mapping['context_advertisements']['deal_type']['value'])) Response::SetBoolean('deal_rent',true);
                                if(preg_match('/2/',$mapping['context_advertisements']['deal_type']['value'])) Response::SetBoolean('deal_sell',true);
                                ///
                                
                                
                                $module_template = 'form.context_advertisement.html';
                            }
                            else{
                                if(!empty($response_errors)){
                                    $ajax_result['errors'] = $response_errors;
                                    $ajax_result['error_msg'] = implode(', ',array_values($response_errors));
                                }
                            }
                            //если объявление новое, возвращаем его id
                            if(!empty($new_id)){
                                $ajax_result['id'] = $new_id;
                                $ajax_result['form_data'] = $info;
                            } 
                            $ajax_result['ok'] = true;
                        break;
                    }
                }
                break;
            //работа с фотографиями 
            case $action == 'photos':
                if($ajax_mode){
                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                    switch($action){
                        //изменение места размещения
                        case 'reqts':
                            $id = Request::GetInteger('id',METHOD_POST);
                            $id_place = Request::GetInteger('id_place',METHOD_POST);
                            $res = $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET id_place = ? WHERE id = ?",$id_place,$id);
                            $ajax_result['ok'] = $res;
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
                            //при попытке изменения фотографии сразу убираем в архив
                            $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET published = 2 WHERE id = ? AND published = 1",$id);
                            //читаем высоту и ширину фотографии
                            $campaign_data = $db->fetch("SELECT IF(".$sys_tables['context_advertisements'].".block_type = 2 AND ".$sys_tables['context_advertisements'].".id_place = 4,
                                                                ".$sys_tables['context_places'].".width_txtimage,
                                                                ".$sys_tables['context_places'].".width
                                                                ) AS width,
                                                                IF(".$sys_tables['context_advertisements'].".block_type = 2 AND ".$sys_tables['context_advertisements'].".id_place = 4,
                                                                ".$sys_tables['context_places'].".height_txtimage,
                                                                ".$sys_tables['context_places'].".height
                                                                ) AS height,
                                                                ".$sys_tables['context_advertisements'].".block_type
                                                         FROM ".$sys_tables['context_advertisements']."
                                                         LEFT JOIN ".$sys_tables['context_places']." ON ".$sys_tables['context_advertisements'].".id_place = ".$sys_tables['context_places'].".id
                                                         WHERE ".$sys_tables['context_advertisements'].".id = ".$id);
                            if($campaign_data['block_type'] == 2){
                                $height = 80;
                                $width = 80;
                            }else{
                                $height = $campaign_data['height'];
                                $width = $campaign_data['width'];
                            }
                            if(!empty($id)){
                                // свойства папок для загрузки и формата фотографий
                                Photos::$__folder_options=array('big'=>array($width,$height,'',95),'sm'=>array($width,$height,'',95));
                                $res = Photos::Add('context_advertisements',$id,false,false,false,$width,$height,true,false,false,$width,$height,true);
                                if(!empty($res)){
                                    if(gettype($res) == 'string') $ajax_result['error'] = $res;
                                    else {
                                        if(gettype($res) == 'string') $ajax_result['error'] = $res;
                                        else {
                                            $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET id_main_photo = ".$res['photo_id']." WHERE id = ".$id);
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
                            //при попытке изменения фотографии сразу убираем в архив
                            $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET published = 2 WHERE id_main_photo = ? AND published = 1",$id_photo);
                            
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
            //коипрование рекламного блока
            case $action == 'copy':
                if($ajax_mode){
                    //получаем id объекта
                    $id = Request::GetInteger('id',METHOD_POST);
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
                    
                    $res = $db->querys("INSERT INTO ".$sys_tables['context_advertisements']." (".implode(',',array_keys($temp_info)).") VALUES (\"".implode('","',array_values(array_map("addSlashes",$temp_info)))."\")");
                    
                    $new_id = $db->insert_id;
                    
                    //копируем пары этот_блок-тег из таблицы соответствия
                    $res = $db->querys("INSERT INTO ".$sys_tables['context_tags_conformity']." (id_context,id_tag) 
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
                        $res = Photos::Add('context_advertisements',$new_id,false,"//www.bsn.int/".Config::$values['img_folders']['context_advertisements']."/big/".$img['folder']."/".$img['name'],false,$width,$height,true,false,false,$width,$height,true);
                        else
                        $res = Photos::Add('context_advertisements',$new_id,false,"//st1.bsn.ru/".Config::$values['img_folders']['context_advertisements']."/big/".$img['folder']."/".$img['name'],false,$width,$height,true,false,false,$width,$height,true);
                        //обновляем id_main_photo
                        if(!empty($res)) $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET id_main_photo = ".$res['photo_id']." WHERE id = ".$new_id);
                    }
                    
                    
                    $ajax_result['id'] = $new_id;
                    $ajax_result['new_photo'] = $res;
                    $ajax_result['ok'] = !empty($res);
                    break;
                }
            //удаление рекламного блока
            case $action == 'del':
                if($ajax_mode){
                    //получаем id объекта
                    $id = Request::GetInteger('id',METHOD_POST);
                    //удаляем пары этот_блок-тег из таблицы соответствия
                    $db->querys("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context = ?",$id);
                    ///удаляем все фотографии этого блока:
                    //читаем список картинок, которые нужно удалить
                    $img_list = $db->fetchall("SELECT id FROM ".$sys_tables['context_advertisements_photos']." WHERE id_parent = ".$id);
                    //удаляем файлы картинок
                    foreach($img_list as $key=>$item)
                        Photos::Delete($sys_tables['context_advertisements_photos'],$item['id']);
                    //удаляем картинки из таблицы
                    $db->querys("DELETE FROM ".$sys_tables['context_advertisements_photos']." WHERE id_parent = ?",$id);
                    //удаляем сам рекламный блок
                    $res = $db->querys("DELETE FROM ".$sys_tables['context_advertisements']." WHERE id = ?",$id);
                    $ajax_result['ok'] = $res;
                    break;
                }
            //обновляемая статистика по показам, кликам, ctr для списка рекламных объявлений
            case $action == 'advlist_stats':
                if($ajax_mode){
                    //читаем список id объявлений, для которых нужны данные
                    $ids_list = Request::GetArray('ids_list',METHOD_POST);
                    if(!empty($ids_list)) $ids_list = " AND ".implode(',',$ids_list);
                    $sql = "SELECT ".$sys_tables['context_advertisements'].".id,
                            (IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount)) AS shows,
                            (IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount)) AS clicks,
                            CAST(CAST( ((IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount))/(IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) AS ctr
                            FROM ".$sys_tables['context_advertisements'];
                    
                    $condition = " id_campaign = ".$id_campaign.((!empty($ids_list))?$ids_list:"");
                    if(!empty($condition)) $sql .= " WHERE ".$condition;
                    $sql .= " GROUP BY ".$sys_tables['context_advertisements'].".id";
                    $sql .= " ORDER BY ".$sys_tables['context_advertisements'].".`id` DESC";
                    $stats_list = $db->fetchall($sql,'id');
                    
                    if(!empty($stats_list)){
                        $advertisements_ids = implode(',',array_keys($stats_list));
                        $s_full = $db->fetchall("SELECT ".$sys_tables['context_stats_show_full'].".id_parent, SUM(amount) AS amount 
                                                 FROM ".$sys_tables['context_stats_show_full']."
                                                 WHERE id_parent IN(".$advertisements_ids.")
                                                 GROUP BY ".$sys_tables['context_stats_show_full'].".id_parent",'id_parent');
                        $c_full = $db->fetchall("SELECT ".$sys_tables['context_stats_click_full'].".id_parent, SUM(amount) AS amount 
                                                 FROM ".$sys_tables['context_stats_click_full']."
                                                 WHERE id_parent IN(".$advertisements_ids.")
                                                 GROUP BY ".$sys_tables['context_stats_click_full'].".id_parent",'id_parent');
                        $s_day = $db->fetchall("SELECT ".$sys_tables['context_stats_show_day'].".id_parent, COUNT(*) AS amount
                                                FROM ".$sys_tables['context_stats_show_day']."
                                                WHERE id_parent IN(".$advertisements_ids.")
                                                GROUP BY ".$sys_tables['context_stats_show_day'].".id_parent",'id_parent');
                        $c_day = $db->fetchall("SELECT ".$sys_tables['context_stats_click_day'].".id_parent, COUNT(*) AS amount
                                                FROM ".$sys_tables['context_stats_click_day']."
                                                WHERE id_parent IN(".$advertisements_ids.")
                                                GROUP BY ".$sys_tables['context_stats_click_day'].".id_parent",'id_parent');
                        foreach($stats_list as $key=>$values){
                            $stats_list[$key]['shows'] = (!empty($s_day[$key]) ? $s_day[$key] : 0) + (!empty($s_full[$key]) ? $s_full[$key] : 0);
                            $stats_list[$key]['clicks'] = (!empty($c_day[$key]) ? $c_day[$key] : 0) + (!empty($c_full[$key]) ? $c_full[$key] : 0);
                            // AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) AS ctr
                            $stats_list[$key]['ctr'] = $stats_list[$key]['clicks'] / ($stats_list[$key]['shows']*1.0000) * 100.0;
                            $stats_list[$key]['ctr'] = number_format($stats_list[$key]['ctr'],2);
                        }
                    }
                    
                    if(!empty($stats_list)){
                        $ajax_result['data'] = $stats_list;
                        $ajax_result['ok'] = true;
                    } 
                }
                break;
             //блок со статистикой рекламных блоков кампании (таблица)
            case $action == 'stats' && empty($this_page->page_parameters[2]) && $ajax_mode:
                $GLOBALS['js_set'][] = '/modules/context_campaigns/stats_functions.js';
                // мэппинги модуля
                $mapping = include('conf_mapping.php');
                $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
                $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
                $GLOBALS['js_set'][] = '/modules/stats/ajax_actions.js';
                $GLOBALS['js_set'][] = '/js/jquery.datatables.min.js';
                
                //читаем переданные из фильтра параметры
                $date_start = Request::GetString('filter_date_start',METHOD_GET);
                $date_end = Request::GetString('filter_date_end',METHOD_GET);
                
                //id кампании
                $id_campaign = Request::GetInteger('cmp',METHOD_GET);
                //список нажатых строчек
                $advs_selected = Request::GetString('advs_selected',METHOD_GET);
                
                //если ничего не передано, значит это первый вызов
                $first_call = empty($advs_selected);
                if(!empty($advs_selected)){
                    $total_values = strstr('total',$advs_selected);
                    //'total' не нужно в списке id, убираем его
                    $advs_selected = preg_replace('/(\,[^0-9\,]+)|([^0-9\,]+\,)|^[^0-9\,]+$/','',$advs_selected);
                    $advs_selected = explode(',',$advs_selected);
                }else $total_values = true;
                
                //если ограничений по дате нет, верхнее ограничение ставим вчера
                if(empty($date_start)) $date_start = '01.01.15';
                if(empty($date_end)){
                    $date = new DateTime();
                    $date->sub(new DateInterval('P1D'));
                    $date_end = $date->format('d.m.y');
                }
                $date_start_formatted = implode('-',array_reverse(explode('.',$date_start)));
                $date_end_formatted = implode('-',array_reverse(explode('.',$date_end)));
                $GLOBALS['js_set'][] = '/js/jquery.datatables.min.js';
                
                $condition = " id_campaign = ".$id_campaign;
                if(!empty($date_start_formatted) && !empty($date_end_formatted)){
                    $local_conditions['sf'] = "";$local_conditions['cf'] = "";$local_conditions['uf'] = "";
                    $local_conditions['sf'] = "WHERE ".$sys_tables['context_stats_show_full'].".`date`>='".$date_start_formatted."' AND
                                                     ".$sys_tables['context_stats_show_full'].".`date`<='".$date_end_formatted."'";
                    $local_conditions['cf'] = "WHERE ".$sys_tables['context_stats_click_full'].".`date`>='".$date_start_formatted."' AND 
                                                     ".$sys_tables['context_stats_click_full'].".`date`<='".$date_end_formatted."'";
                    $local_conditions['uf'] = "WHERE ".$sys_tables['users_finances'].".obj_type = 'context_banner' AND
                                                     ".$sys_tables['users_finances'].".`datetime`>='20".$date_start_formatted." 99' AND 
                                                     ".$sys_tables['users_finances'].".`datetime`<='20".$date_end_formatted." 99'";
                }
                
                //читаем список контекстных блоков, клики, показы, ctr
                $advertisements_list = $db->fetchall("SELECT ".$sys_tables['context_advertisements'].".id AS id_context,
                                                        ".$sys_tables['context_advertisements'].".title,
                                                        (IF(s_full.amount IS NULL,0,s_full.amount)) AS shows,
                                                        (IF(c_full.amount IS NULL,0,c_full.amount)) AS clicks,
                                                        CAST(( ((IF(c_full.amount IS NULL,0,c_full.amount))/(IF(s_full.amount IS NULL,0,s_full.amount))))*100 AS DECIMAL(4,2)) AS ctr,
                                                        uf.amount AS fin
                                                     FROM ".$sys_tables['context_advertisements']."
                                                     LEFT JOIN (SELECT ".$sys_tables['context_stats_show_full'].".id_parent, SUM(amount) AS amount 
                                                               FROM ".$sys_tables['context_stats_show_full']."
                                                               ".$local_conditions['sf']."
                                                               GROUP BY ".$sys_tables['context_stats_show_full'].".id_parent) s_full
                                                     ON s_full.id_parent = ".$sys_tables['context_advertisements'].".id
                                                     LEFT JOIN (SELECT ".$sys_tables['context_stats_click_full'].".id_parent, SUM(amount) AS amount
                                                               FROM ".$sys_tables['context_stats_click_full']."
                                                               ".$local_conditions['cf']."
                                                               GROUP BY ".$sys_tables['context_stats_click_full'].".id_parent) c_full
                                                     ON c_full.id_parent = ".$sys_tables['context_advertisements'].".id
                                                     LEFT JOIN (SELECT id_parent,SUM(expenditure) AS amount
                                                                FROM ".$sys_tables['users_finances']." 
                                                                ".$local_conditions['uf']."
                                                                GROUP BY ".$sys_tables['users_finances'].".id_parent) AS uf ON uf.id_parent = ".$sys_tables['context_advertisements'].".id
                                                     WHERE ".$condition."
                                                     ORDER BY shows DESC",'id_context');
                
                //верхние 5 по просмотрам
                $top_shows = array_keys(array_slice($advertisements_list,0,5,true));
                //читаем полный список объявлений
                $adv_list = $db->fetchall("SELECT id,title,url
                                           FROM ".$sys_tables['context_advertisements']."
                                           WHERE id_campaign = ".$id_campaign."
                                           ORDER BY id ASC");
                $adv_total = array('id_context'=>1,'title'=>"Суммарно по РК",'visible'=>true,'color'=>'#000000','shows'=>0,'clicks'=>0,'ctr'=>0,'fin'=>0,'active'=>true);
                $total_ctred = 0;
                //для всех создаем цвета
                foreach($adv_list as $key=>$adv){
                    //назначаем объявлению цвет
                    $hashcolor = (($adv['id']%2)?md5($adv['id']):sha1($adv['id']));
                    $hashcolor = preg_replace('/[^2-9][^a-f]/','',$hashcolor);
                    //switch($key%3){
                    switch(($adv['id']*($key)+strlen($adv['url']))%1000%3){
                        case 0:
                            $color = '#'.substr($hashcolor,2,4).substr($hashcolor,0,2);
                            break;
                        case 1:
                            $color = '#'.(substr($hashcolor,3,3))."".(substr($hashcolor,0,3));
                            break;
                        case 2:
                            $color = '#'.(substr($hashcolor,-2,2)).(substr($hashcolor,0,4));
                            break;
                    }
                    //если эта строчка подходит под условия, отмечаем ее как видимую
                    if(!empty($advertisements_list[$adv['id']])){
                        $adv_list[$key] = $advertisements_list[$adv['id']];
                        $adv_list[$key]['visible'] = true;
                        $adv_list[$key]['color'] = $color;
                        //если в верхней 5 или была выделена, выделяем
                        if($first_call && in_array($adv['id'],$top_shows)) $adv_list[$key]['active'] = true;
                        elseif(!empty($advs_selected) && in_array($adv['id'],$advs_selected)) $adv_list[$key]['active'] = true;
                    }
                    
                    //накапливаем суммарные значения
                    $adv_total['shows'] += $advertisements_list[$adv['id']]['shows'];
                    $adv_total['clicks'] += $advertisements_list[$adv['id']]['clicks'];
                    $adv_total['ctr'] += $advertisements_list[$adv['id']]['ctr'];
                    $adv_total['fin'] += $advertisements_list[$adv['id']]['fin'];
                    
                    if($advertisements_list[$adv['id']]['shows'] > 0) ++$total_ctred;
                    
                    //если нет просмотров, прячем строчку
                    if(empty($adv_list[$key]['shows'])) $adv_list[$key]['visible'] = false;
                }
                if(empty($total_ctred)) $adv_total['ctr'] = 0;
                else $adv_total['ctr'] /= $total_ctred;
                $adv_total['ctr'] = number_format($adv_total['ctr'],2);
                $adv_total['is_total'] = true;
                array_unshift($adv_list,$adv_total);
                
                
                //читаем список кампаний для select
                //$cmp_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['context_campaigns']." WHERE id_user = ".$auth->id);
                $cmp_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['context_campaigns']." WHERE id_user IN (".$allowed_users.") OR id_creator = ".$auth->id);
                Response::SetArray('cmp_list',$cmp_list);
                
                Response::SetArray('list',$adv_list);
                $ajax_result['ok'] = true;
                
                $module_template = "client_campaign_stats_block.html";
                break;
            //статистика рекламных блоков кампаний
            case $action == 'stats' && empty($this_page->page_parameters[2]):
                Response::SetBoolean('campaigns_stats_page',true);
                
                //штуки для графиков
                $GLOBALS['js_set'][] = '/js/graphics.init.js';
                $GLOBALS['js_set'][] = '/js/google.chart.api.js';
                $GLOBALS['css_set'][] = '/modules/cottages/graphics.css';
                //для фильтра справа
                // мэппинги модуля
                $mapping = include('conf_mapping.php');
                $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
                $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
                $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js'; 
                $GLOBALS['js_set'][] = '/js/google.chart.api.js';
                $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js';
                $GLOBALS['js_set'][] = '/modules/stats/ajax_actions.js';
                $GLOBALS['js_set'][] = '/js/jquery.datatables.min.js';
                
                //читаем список кампаний для select
                //$cmp_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['context_campaigns']." WHERE id_user = ".$auth->id);
                $cmp_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['context_campaigns']." WHERE id_user = IN (".$allowed_users.") OR id_creator = ".$auth->id);
                Response::SetArray('cmp_list',$cmp_list);
                
                $module_template = 'client_campaign_stats.html';
                break;
            //сразу ВСЕ графики для статистики
            case $action=='stats'&&$this_page->page_parameters[2] == 'gr-all'&&$ajax_mode:
                $GLOBALS['js_set'][] = '/modules/context_campaigns/stats_functions.js';
                //читаем переданные параметры
                $date_start = Request::GetString('date_start',METHOD_POST);
                $date_end = Request::GetString('date_end',METHOD_POST);
                $group_by = Request::GetString('group_by',METHOD_POST);
                $ids = Request::GetString('ids',METHOD_POST);
                //флаг, нужны ли общие значения
                $total_values = strstr($ids,'total');
                //'total' не нужно в списке id, убираем его
                $ids = preg_replace('/(\,[^0-9\,]+)|([^0-9\,]+\,)|^[^0-9\,]+$/','',$ids);
                //если это первый вызов, то выбираем только первые пять
                $first_call = Request::GetBoolean('first_call',METHOD_POST);
                switch($group_by){
                    case "day": 
                        $sql_date_formatting = "'%d.%m.%y'";
                        $list_date_formatting = "d.m.y";
                        $date_modifier = "+1 day";
                        break;
                    case "week": 
                        $sql_date_formatting = "'%u.%Y'";
                        $list_date_formatting = "W.Y";
                        $date_modifier = "+1 week";
                        break;
                    case "month": 
                        $sql_date_formatting = "'%m.%Y'";
                        $list_date_formatting = "m.Y";
                        $date_modifier = "+1 month";
                        break;
                    default:
                        $group_by = 'day';
                        $sql_date_formatting = "'%d.%m.%y'";
                        $list_date_formatting = "d.m.y";
                        $date_modifier = "+1 day";
                        break;
                }
                
                //если набор цветов не передан, будем генерировать
                $colors = Request::GetString('colors',METHOD_POST);
                if(!empty($colors)){
                    $colors = explode(',',$colors);
                    if(!empty($total_values)){
                        unset($colors[0]);
                        $colors = array_values($colors);
                    } 
                    $colors_responsed = (!empty($colors));
                } 
                else{
                    $colors_responsed = false;
                    $colors = array();
                } 
                
                
                
                //если ограничений по дате нет, верхнее ограничение ставим вчера
                if(empty($date_start)) $date_start = '01.01.15';
                if(empty($date_end)){
                    $date = new DateTime();
                    $date->sub(new DateInterval('P1D'));
                    $date_end = $date->format('d.m.y');
                }
                $date_start_formatted = implode('-',array_reverse(explode('.',$date_start)));
                $date_end_formatted = implode('-',array_reverse(explode('.',$date_end)));
                
                //здесь будем накапливать общий результат
                $global_result = array();
                //типы выборок и графиков
                $gr_selections = array('show','click','ctr','fin');
                $gr_types = array('line','column','pie');
                
                //собираем данные для графиков всех выборок и типов
                foreach($gr_selections as $gr_selection){
                    foreach($gr_types as $gr_type){
                        //задаем цвета
                        if($colors_responsed){
                            $colors = Request::GetString('colors',METHOD_POST);
                            $colors = explode(',',$colors);
                            if(!empty($total_values)){
                                unset($colors[0]);
                                $colors = array_values($colors);
                            } 
                            
                            //цвета необходимо отсортировать по возрастанию id их объявлений (если больше 1)
                            $sorted_colors = array();
                            $ids_list = explode(',',$ids);
                            if(gettype($colors) == 'array'){
                                foreach($ids_list as $key=>$adv_key){
                                    $sorted_colors[$colors[$key]] = $adv_key;
                                }
                                asort($sorted_colors);
                            }
                            if(!empty($sorted_colors)) $colors = array_keys($sorted_colors);
                        }
                        else{
                            $colors = array();
                        }
                        //создаем набор дат в заданном интервале
                        $dates_list = array();
                        //создаем даты по переданным параметрам
                        $start = DateTime::createFromFormat('d.m.y', $date_start);
                        $end =  DateTime::createFromFormat('d.m.y',$date_end);
                        
                        //преобразуем даты в нужную группировку
                        $start_formatted = $start->format($list_date_formatting);
                        $end_formatted = $end->format($list_date_formatting);
                        //если в рамках группировки даты совпадают
                        if($start_formatted == $end_formatted){
                            $end = $start;
                            $dates_list[] = array($start->format($list_date_formatting));
                        }
                        else{
                            while($end >= $start){
                                $dates_list[] = array($start->format($list_date_formatting));
                                $start->modify($date_modifier);
                            }
                            //если группировка по неделям, нужно учесть часть текущей недели
                            if($group_by == 'week'){
                                //$end->modify("-1 day");
                                $last_week = explode('.',$start->format($list_date_formatting));
                                $last_week_start = $start->setISODate($last_week[1],$last_week[0],'1');
                                //если последняя из записанных недель окончилась раньше, чем вчера, добавляем еще одну неделю
                                if($last_week_start<=$end){
                                    $dates_list[] = array($start->format($list_date_formatting));
                                    $start->modify($date_modifier);
                                    //$dates_list[] = array($start->format($list_date_formatting));
                                }
                            }
                        }
                        
                        //в зависимости от длины dates_list, корректируем количество отображаемых подписей
                        $display_every = (count($dates_list)>15)?(int)(count($dates_list)/15):1;
                        
                        switch($gr_type){
                            case 'line':
                                //читаем список объявлений
                                if(empty($ids))
                                    if(empty($total_values)) $adv_list = $db->fetchall("SELECT id,title,url
                                                                                        FROM ".$sys_tables['context_advertisements']."
                                                                                        WHERE id_campaign = ".$id_campaign."
                                                                                        ORDER BY id ASC".(!empty($first_call)?" LIMIT 5":""));
                                    else $adv_list = array();
                                else $adv_list = $db->fetchall("SELECT id,title,url
                                                                FROM ".$sys_tables['context_advertisements']." 
                                                                WHERE id_campaign = ".$id_campaign." AND id IN(".$ids.")
                                                                ORDER BY id ASC ");
                                //список id для суммарных значений
                                $adv_ids_list = $db->fetch("SELECT GROUP_CONCAT(id) AS ids
                                                            FROM ".$sys_tables['context_advertisements']."
                                                            WHERE id_campaign = ".$id_campaign);
                                $adv_ids_list = (!empty($adv_ids_list)?$adv_ids_list['ids']:"0");
                                $adv_data = array();
                                //массив полей
                                $fields = array(array('string','дата'));
                                
                                ///////
                                //при необходимости собираем данные для суммарных значений
                                //
                                if(!empty($total_values)){
                                    array_unshift($colors,'#000000');
                                    if($gr_selection == 'ctr'){
                                        $adv_shows =  $db->fetchall("SELECT AVG(CAST(CAST( ((IF(cf.amount IS NULL,0,cf.amount))/(IF(sf.amount IS NULL,0,sf.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2))) as amount,
                                                                             DATE_FORMAT(sf.`date`,".$sql_date_formatting.") AS `date`
                                                                     FROM ".$sys_tables['context_stats_show_full']." AS sf
                                                                     LEFT JOIN ".$sys_tables['context_stats_click_full']." AS cf ON sf.`date` = cf.`date`
                                                                     WHERE sf.id_parent IN(".$adv_ids_list.") AND 
                                                                           cf.id_parent IN(".$adv_ids_list.") AND
                                                                           sf.`date`>='".$date_start_formatted."' AND
                                                                           sf.`date`<='".$date_end_formatted."'
                                                                     GROUP BY sf.`date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (float)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    elseif($gr_selection == 'fin'){
                                        $adv_shows = $db->fetchall("SELECT SUM(uf.expenditure) AS amount,
                                                                           DATE_FORMAT(uf.`datetime`,".$sql_date_formatting.") AS `date`
                                                                       FROM ".$sys_tables['users_finances']." AS uf
                                                                       LEFT JOIN ".$sys_tables['context_advertisements']." AS c_adv
                                                                       ON uf.id_parent = c_adv.id
                                                                       WHERE uf.obj_type = 'context_banner' AND 
                                                                             uf.id_parent IN (".$adv_ids_list.") AND
                                                                             uf.`datetime` >= '".$date_start_formatted."' AND
                                                                             uf.`datetime` <= '20".$date_end_formatted." 99'
                                                                       GROUP BY `date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (float)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }else{
                                        if($group_by != 'day')
                                            $adv_shows =  $db->fetchall("SELECT SUM(amount) AS amount, `date`
                                                                         FROM (
                                                                         SELECT SUM(amount) AS amount,DATE_FORMAT(`date`,".$sql_date_formatting.") AS `date`
                                                                         FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                         WHERE id_parent IN(".$adv_ids_list.") ".((!empty($date_start_formatted))?" AND `date`>='".$date_start_formatted."' ":"").
                                                                         ((!empty($date_end_formatted))?" AND `date`<='".$date_end_formatted."' ":"")."
                                                                         GROUP BY `date`
                                                                         ) a
                                                                         GROUP BY `date`",'date');
                                        else
                                            $adv_shows =  $db->fetchall("SELECT SUM(amount) as amount,DATE_FORMAT(`date`,".$sql_date_formatting.") AS `date`
                                                                     FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                     WHERE id_parent IN(".$adv_ids_list.") ".((!empty($date_start_formatted))?" AND `date`>='".$date_start_formatted."' ":"").
                                                                     ((!empty($date_end_formatted))?" AND `date`<='".$date_end_formatted."' ":"")."
                                                                     GROUP BY ".$sys_tables['context_stats_'.$gr_selection.'_full'].".`date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (int)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    $fields[] = array('number',"Суммарно по РК");
                                }
                                //
                                ///////
                                
                                foreach($adv_list as $key=>$adv){
                                    if(!$colors_responsed){
                                        //назначаем объявлению цвет
                                        $hashcolor = (($adv['id']%2)?md5($adv['id']):sha1($adv['id']));
                                        $hashcolor = preg_replace('/[^2-9][^a-f]/','',$hashcolor);
                                        //switch($key%3){
                                        switch(($adv['id']*($key)+strlen($adv['url']) )%1000%3){
                                            case 0:
                                                $colors[] = '#'.substr($hashcolor,2,4).substr($hashcolor,0,2);
                                                break;
                                            case 1:
                                                $colors[] = '#'.(substr($hashcolor,3,3))."".(substr($hashcolor,0,3));
                                                break;
                                            case 2:
                                                $colors[] = '#'.(substr($hashcolor,-2,2)).(substr($hashcolor,0,4));
                                                break;
                                        }
                                    }
                                    
                                    //в зависимости от выборки читаем данные
                                    if($gr_selection == 'ctr'){
                                        $adv_shows =  $db->fetchall("SELECT CAST(CAST( ((IF(cf.amount IS NULL,0,cf.amount))/(IF(sf.amount IS NULL,0,sf.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) as amount,
                                                                             DATE_FORMAT(sf.`date`,".$sql_date_formatting.") AS `date`
                                                                     FROM ".$sys_tables['context_stats_show_full']." AS sf
                                                                     LEFT JOIN ".$sys_tables['context_stats_click_full']." AS cf ON sf.`date` = cf.`date`
                                                                     WHERE sf.id_parent = ".$adv['id']." AND 
                                                                           cf.id_parent = ".$adv['id']." AND
                                                                           sf.`date`>='".$date_start_formatted."' AND
                                                                           sf.`date`<='".$date_end_formatted."'
                                                                     GROUP BY sf.`date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (float)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    elseif($gr_selection == 'fin'){
                                        $adv_shows = $db->fetchall("SELECT SUM(uf.expenditure) AS amount,
                                                                           DATE_FORMAT(uf.`datetime`,".$sql_date_formatting.") AS `date`
                                                                       FROM ".$sys_tables['users_finances']." AS uf
                                                                       LEFT JOIN ".$sys_tables['context_advertisements']." AS c_adv
                                                                       ON uf.id_parent = c_adv.id
                                                                       WHERE uf.obj_type = 'context_banner' AND 
                                                                             uf.id_parent = ".$adv['id']." AND
                                                                             uf.`datetime` >= '".$date_start_formatted."' AND
                                                                             uf.`datetime` <= '20".$date_end_formatted." 99'
                                                                       GROUP BY `date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (float)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    else{
                                        if($group_by != 'day')
                                            $adv_shows =  $db->fetchall("SELECT SUM(amount) AS amount, `date`
                                                                         FROM (
                                                                         SELECT SUM(amount) AS amount,DATE_FORMAT(`date`,".$sql_date_formatting.") AS `date`
                                                                         FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                         WHERE id_parent = ".$adv['id'].((!empty($date_start_formatted))?" AND `date`>='".$date_start_formatted."' ":"").
                                                                         ((!empty($date_end_formatted))?" AND `date`<='".$date_end_formatted."' ":"")."
                                                                         GROUP BY `date`
                                                                         ) a
                                                                         GROUP BY `date`",'date');
                                        else
                                            $adv_shows =  $db->fetchall("SELECT amount,DATE_FORMAT(`date`,".$sql_date_formatting.") AS `date`
                                                                     FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                     WHERE id_parent = ".$adv['id'].((!empty($date_start_formatted))?" AND `date`>='".$date_start_formatted."' ":"").
                                                                     ((!empty($date_end_formatted))?" AND `date`<='".$date_end_formatted."' ":"")."
                                                                     GROUP BY ".$sys_tables['context_stats_'.$gr_selection.'_full'].".`date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (int)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    
                                    //пополняем список полей
                                    $fields[] = array('number',$adv['title']);
                                }
                                
                                
                                $data = array();
                                $date_today = new DateTime();
                                $period_end =  DateTime::createFromFormat('d.m.y',$date_end);
                                $was_not_empty = false;
                                //собираем строчки для графика
                                foreach($dates_list as $date=>$items){
                                    //в случае недели, заменям номер недели+год на границы недели
                                    if($group_by == 'week'){
                                        $date_info = explode('.',$items[0]);
                                        $gen_weekstart = new DateTime();$gen_weekstart->setISODate($date_info[1],$date_info[0],1);
                                        $gen_weekend = new DateTime();$gen_weekend->setISODate($date_info[1],$date_info[0],7);
                                        if($gen_weekend>$period_end){
                                            $week_borders = $gen_weekstart->format('d-m')."--".$gen_weekend->format('d-m');
                                            $period_end =  DateTime::createFromFormat('d.m.y',$date_end);
                                        }
                                        else
                                            $week_borders = $gen_weekstart->format('d-m')."--".$gen_weekend->format('d-m');
                                        $items[0] = $week_borders;
                                    }else{
                                        //просто убираем год
                                        $items[0] = preg_replace('/\.[0-9]{2}$/','',$items[0]);
                                    }
                                    ///отсекаем нулевые значения слева
                                    //определяем, есть ли какие-нибудь значения, кроме нулевых
                                    $items_values = implode('',array_slice($items,1));
                                    $is_not_empty = preg_replace('/0/','',$items_values);
                                    //если есть или уже было ненулевое, записываем для отображения на графике, отмечаем, что ненулевое значение уже было
                                    if($is_not_empty || $was_not_empty){
                                        $was_not_empty = true;
                                        $data[] = $items;
                                    }
                                }
                                //переворачиваем $data чтобы отсечь нулевые справа
                                $data = array_reverse($data,true);
                                foreach($data as $d_key=>$d_item){
                                    //определяем, есть ли какие-нибудь значения, кроме нулевых
                                    $data_value = implode('',array_slice($d_item,1));
                                    $is_not_empty = preg_replace('/0/','',$data_value);
                                    //если попалось ненулевое, сразу выходим
                                    if($is_not_empty){
                                        break;
                                    }
                                    else unset($data[$d_key]);
                                }
                                //переворачиваем обратно
                                $data = array_reverse($data,true);
                                //в зависимости от длины data, корректируем количество отображаемых подписей
                                $display_every = (count($data)>25)?(int)(count($data)/5):1;
                                $global_result[] = array(
                                                             'ok' => true
                                                            ,'type' => 'Line'
                                                            ,'data' => $data
                                                            ,'fields' => $fields
                                                            ,'graphic_number' => 3
                                                            ,'width'=>770
                                                            ,'height'=>380
                                                            ,'show_every'=>$display_every
                                                            ,'legend_position'=>'none'
                                                            ,'colors'=>$colors
                                                            ,'X_title'=>$group_by
                                                            ,'selection'=>$gr_selection);
                                break;
                            case 'column':    
                                //читаем список объявлений
                                //$adv_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['context_advertisements']." WHERE id_campaign = ".$id_campaign." ORDER BY id ASC");
                                if(empty($ids))
                                    if(empty($total_values))  $adv_list = $db->fetchall("SELECT id,title,url
                                                                           FROM ".$sys_tables['context_advertisements']."
                                                                           WHERE id_campaign = ".$id_campaign."
                                                                           ORDER BY id ASC".(!empty($first_call)?" LIMIT 5":""));
                                    else $adv_list = array();
                                else $adv_list = $db->fetchall("SELECT id,title,url
                                                                FROM ".$sys_tables['context_advertisements']." 
                                                                WHERE id_campaign = ".$id_campaign." AND id IN(".$ids.")
                                                                ORDER BY id ASC");
                                $adv_data = array();
                                //массив полей
                                $fields = array(array('string','дата'));
                                
                                ///////
                                //при необходимости собираем данные для суммарных значений
                                //
                                if(!empty($total_values)){
                                    array_unshift($colors,'#000000');
                                    if($gr_selection == 'ctr'){
                                        $adv_shows =  $db->fetchall("SELECT  AVG(CAST(CAST( ((IF(cf.amount IS NULL,0,cf.amount))/(IF(sf.amount IS NULL,0,sf.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2))) as amount,
                                                                                 DATE_FORMAT(sf.`date`,".$sql_date_formatting.") AS `date`
                                                                     FROM ".$sys_tables['context_stats_show_full']." AS sf
                                                                     LEFT JOIN ".$sys_tables['context_stats_click_full']." AS cf ON sf.`date` = cf.`date`
                                                                     WHERE sf.id_parent IN(".$adv_ids_list.") AND 
                                                                           cf.id_parent IN(".$adv_ids_list.") AND 
                                                                           sf.`date`>='".$date_start_formatted."' AND 
                                                                           sf.`date`<='".$date_end_formatted."'
                                                                     GROUP BY sf.`date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (float)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    elseif($gr_selection == 'fin'){
                                        $adv_shows = $db->fetchall("SELECT SUM(uf.expenditure) AS amount,
                                                                               DATE_FORMAT(uf.`datetime`,".$sql_date_formatting.") AS `date` 
                                                                           FROM ".$sys_tables['users_finances']." AS uf
                                                                           LEFT JOIN ".$sys_tables['context_advertisements']." AS c_adv
                                                                           ON uf.id_parent = c_adv.id
                                                                           WHERE uf.obj_type = 'context_banner' AND 
                                                                                 uf.id_parent IN(".$adv_ids_list.") AND
                                                                                 uf.`datetime` >= '".$date_start_formatted."' AND
                                                                                 uf.`datetime` <= '20".$date_end_formatted." 99'
                                                                           GROUP BY `date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (float)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }else{
                                        if($group_by != 'day')
                                            $adv_shows =  $db->fetchall("SELECT SUM(amount) AS amount, `date`
                                                                         FROM (
                                                                         SELECT SUM(amount) AS amount,DATE_FORMAT(`date`,".$sql_date_formatting.") AS `date`
                                                                         FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                         WHERE id_parent IN(".$adv_ids_list.") ".((!empty($date_start_formatted))?" AND `date`>='".$date_start_formatted."' ":"").
                                                                         ((!empty($date_end_formatted))?" AND `date`<='".$date_end_formatted."' ":"")."
                                                                         GROUP BY `date`
                                                                         ) a
                                                                         GROUP BY `date`",'date');
                                            else
                                                $adv_shows =  $db->fetchall("SELECT SUM(amount) AS amount,DATE_FORMAT(`date`,".$sql_date_formatting.") AS `date`
                                                                             FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                             WHERE id_parent IN(".$adv_ids_list.")".((!empty($date_start_formatted))?" AND `date`>='".$date_start_formatted."' ":"").
                                                                             ((!empty($date_end_formatted))?" AND `date`<='".$date_end_formatted."' ":"")."
                                                                             GROUP BY ".$sys_tables['context_stats_'.$gr_selection.'_full'].".`date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (int)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    $fields[] = array('number',"Суммарно по РК");
                                }
                                //
                                ///////
                                
                                //для каждого объявления читаем показы по дням
                                foreach($adv_list as $key=>$adv){
                                    
                                    //есди цвета не переданы,
                                    if(!$colors_responsed){
                                        //назначаем объявлению цвет
                                        $hashcolor = (($adv['id']%2)?md5($adv['id']):sha1($adv['id']));
                                        $hashcolor = preg_replace('/[^2-9][^a-f]/','',$hashcolor);
                                        //switch($key%3){
                                        switch(($adv['id']*($key)+strlen($adv['url']) )%1000%3){
                                            case 0:
                                                $colors[] = '#'.substr($hashcolor,2,4).substr($hashcolor,0,2);
                                                break;
                                            case 1:
                                                $colors[] = '#'.(substr($hashcolor,3,3))."".(substr($hashcolor,0,3));
                                                break;
                                            case 2:
                                                $colors[] = '#'.(substr($hashcolor,-2,2)).(substr($hashcolor,0,4));
                                                break;
                                        }
                                    }
                                    if($gr_selection == 'ctr'){
                                        $adv_shows =  $db->fetchall("SELECT  CAST(CAST( ((IF(cf.amount IS NULL,0,cf.amount))/(IF(sf.amount IS NULL,0,sf.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) as amount,
                                                                             DATE_FORMAT(sf.`date`,".$sql_date_formatting.") AS `date`
                                                                     FROM ".$sys_tables['context_stats_show_full']." AS sf
                                                                     LEFT JOIN ".$sys_tables['context_stats_click_full']." AS cf ON sf.`date` = cf.`date`
                                                                     WHERE sf.id_parent = ".$adv['id']." AND 
                                                                           cf.id_parent = ".$adv['id']." AND 
                                                                           sf.`date`>='".$date_start_formatted."' AND 
                                                                           sf.`date`<='".$date_end_formatted."'
                                                                     GROUP BY sf.`date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (float)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    elseif($gr_selection == 'fin'){
                                        $adv_shows = $db->fetchall("SELECT SUM(uf.expenditure) AS amount,
                                                                           DATE_FORMAT(uf.`datetime`,".$sql_date_formatting.") AS `date` 
                                                                       FROM ".$sys_tables['users_finances']." AS uf
                                                                       LEFT JOIN ".$sys_tables['context_advertisements']." AS c_adv
                                                                       ON uf.id_parent = c_adv.id
                                                                       WHERE uf.obj_type = 'context_banner' AND 
                                                                             uf.id_parent = ".$adv['id']." AND
                                                                             uf.`datetime` >= '".$date_start_formatted."' AND
                                                                             uf.`datetime` <= '20".$date_end_formatted." 99'
                                                                       GROUP BY `date`",'date');
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (float)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    else{
                                        if($group_by != 'day'){
                                            $adv_shows =  $db->fetchall("SELECT SUM(amount) AS amount, `date`
                                                                         FROM (
                                                                         SELECT SUM(amount) AS amount,DATE_FORMAT(`date`,".$sql_date_formatting.") AS `date`
                                                                         FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                         WHERE id_parent = ".$adv['id'].((!empty($date_start_formatted))?" AND `date`>='".$date_start_formatted."' ":"").
                                                                         ((!empty($date_end_formatted))?" AND `date`<='".$date_end_formatted."' ":"")."
                                                                         GROUP BY `date`
                                                                         ) a
                                                                         GROUP BY `date`",'date');
                                        }
                                        else{
                                            $adv_shows =  $db->fetchall("SELECT amount,DATE_FORMAT(`date`,".$sql_date_formatting.") AS `date`
                                                                         FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                         WHERE id_parent = ".$adv['id'].((!empty($date_start_formatted))?" AND `date`>='".$date_start_formatted."' ":"").
                                                                         ((!empty($date_end_formatted))?" AND `date`<='".$date_end_formatted."' ":"")."
                                                                         GROUP BY ".$sys_tables['context_stats_'.$gr_selection.'_full'].".`date`",'date');
                                        }
                                        
                                        foreach($dates_list as $k=>$item){
                                            if(!empty($adv_shows[$item[0]])) $dates_list[$k][] = (int)$adv_shows[$item[0]]['amount'];
                                            else $dates_list[$k][] = (int)0;
                                        }
                                    }
                                    
                                    //пополняем список полей
                                    $fields[] = array('number',$adv['title']);
                                }
                                
                                $data = array();
                                $was_not_empty = false;
                                //собираем строчки для графика
                                foreach($dates_list as $date=>$items){
                                    if($group_by == 'week'){
                                        $date_info = explode('.',$items[0]);
                                        $gen_weekstart = new DateTime();$gen_weekstart->setISODate($date_info[1],$date_info[0],1);
                                        $gen_weekend = new DateTime();$gen_weekend->setISODate($date_info[1],$date_info[0],7);
                                        $week_borders = $gen_weekstart->format('d-m')."--".$gen_weekend->format('d-m');
                                        $items[0] = $week_borders;
                                    }
                                    else{
                                        $items[0] = preg_replace('/\.[0-9]{2}$/','',$items[0]);
                                    }
                                    
                                    ///отсекаем нулевые значения слева
                                    //определяем, есть ли какие-нибудь значения, кроме нулевых
                                    $items_values = implode('',array_slice($items,1));
                                    $is_not_empty = preg_replace('/0/','',$items_values);
                                    //если есть или уже было ненулевое, записываем для отображения на графике, отмечаем, что ненулевое значение уже было
                                    if($is_not_empty || $was_not_empty){
                                        $was_not_empty = true;
                                        $data[] = $items;
                                    }
                                }
                                //переворачиваем $data чтобы отсечь нулевые справа
                                $data = array_reverse($data,true);
                                foreach($data as $d_key=>$d_item){
                                    //определяем, есть ли какие-нибудь значения, кроме нулевых
                                    $data_value = implode('',array_slice($d_item,1));
                                    $is_not_empty = preg_replace('/0/','',$data_value);
                                    //если попалось ненулевое, сразу выходим
                                    if($is_not_empty){
                                        break;
                                    }
                                    else unset($data[$d_key]);
                                }
                                //переворачиваем обратно
                                $data = array_reverse($data,true);
                                //в зависимости от длины data, корректируем количество отображаемых подписей
                                $display_every = (count($data)>25)?(int)(count($data)/5):1;
                                $global_result[] = array(
                                                             'ok' => true
                                                            ,'type' => 'Column'
                                                            ,'data' => $data
                                                            ,'fields' => $fields
                                                            ,'graphic_number' => 3
                                                            ,'width'=>685
                                                            ,'height'=>380
                                                            ,'show_every'=>$display_every
                                                            ,'legend_position'=>'none'
                                                            ,'colors'=>$colors
                                                            ,'X_title'=>$group_by
                                                            ,'selection'=>$gr_selection);
                                break;
                            case 'pie':
                                $data=array();
                                $fields = array(array('string','title'),array('number','amount'));
                                //читаем список объявлений
                                //$adv_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['context_advertisements']." WHERE id_campaign = ".$id_campaign." ORDER BY id ASC");
                                if(empty($ids)) $adv_list = $db->fetchall("SELECT id,title,url
                                                                           FROM ".$sys_tables['context_advertisements']."
                                                                           WHERE id_campaign = ".$id_campaign."
                                                                           ORDER BY id ASC".(!empty($first_call)?" LIMIT 5":""));
                                else $adv_list = $db->fetchall("SELECT id,title,url
                                                                FROM ".$sys_tables['context_advertisements']." 
                                                                WHERE id_campaign = ".$id_campaign." AND id IN(".$ids.")
                                                                ORDER BY id ASC");
                                //назначаем объявлениям цвета
                                if(!$colors_responsed){
                                    foreach($adv_list as $key=>$adv){
                                        $hashcolor = (($adv['id']%2)?md5($adv['id']):sha1($adv['id']));
                                        $hashcolor = preg_replace('/[^2-9][^a-f]/','',$hashcolor);
                                        //switch($key%3){
                                        switch(($adv['id']*($key)+strlen($adv['url']) )%1000%3){
                                            case 0:
                                                $colors[] = '#'.substr($hashcolor,2,4).substr($hashcolor,0,2);
                                                break;
                                            case 1:
                                                $colors[] = '#'.(substr($hashcolor,3,3))."".(substr($hashcolor,0,3));
                                                break;
                                            case 2:
                                                $colors[] = '#'.(substr($hashcolor,-2,2)).(substr($hashcolor,0,4));
                                                break;
                                        }
                                    }
                                }
                                
                                $adv_data = array();
                                
                                //если нужен ctr, запрос другой
                                if($gr_selection == 'ctr'){
                                    
                                    //формируем условия для выборки кликов и показов
                                    $local_conditions['sf'] = "";$local_conditions['cf'] = "";
                                    if(!empty($date_start_formatted)){
                                        $local_conditions['sf'][] = $sys_tables['context_stats_show_full'].".`date`>'".$date_start_formatted."'";
                                        $local_conditions['cf'][] = $sys_tables['context_stats_click_full'].".`date`>'".$date_start_formatted."'";
                                    }
                                    
                                    if(!empty($date_end_formatted)){
                                        $local_conditions['sf'][] = $sys_tables['context_stats_show_full'].".`date`<'".$date_end_formatted."'";
                                        $local_conditions['cf'][] = $sys_tables['context_stats_click_full'].".`date`<'".$date_end_formatted."'";
                                    }
                                    
                                    if(!empty($local_conditions['sf'])) $local_conditions['sf'] = "WHERE ".implode(' AND ',$local_conditions['sf']);
                                    if(!empty($local_conditions['cf'])) $local_conditions['cf'] = "WHERE ".implode(' AND ',$local_conditions['cf']);
                                    
                                    if(!empty($ids)){
                                        $additional_cond = " AND ".$sys_tables['context_advertisements'].".id IN (".$ids.")";
                                    }
                                    
                                    $list_data = $db->fetchall("SELECT ".$sys_tables['context_advertisements'].".id, 
                                                                       title,
                                                                       CAST(CAST( ((IF(c.full_clicks IS NULL,0,c.full_clicks))/(IF(s.full_shows IS NULL,0,s.full_shows))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) AS amount
                                                                FROM ".$sys_tables['context_advertisements']."
                                                                LEFT JOIN (SELECT id_parent, SUM(amount) as full_clicks 
                                                                           FROM ".$sys_tables['context_stats_click_full']."
                                                                           ".$local_conditions['cf']."
                                                                           GROUP BY id_parent) AS c ON c.id_parent = ".$sys_tables['context_advertisements'].".id
                                                                LEFT JOIN (SELECT id_parent, SUM(amount) as full_shows
                                                                           FROM ".$sys_tables['context_stats_show_full']."
                                                                           ".$local_conditions['sf']."
                                                                           GROUP BY id_parent) AS s ON 
                                                                           s.id_parent = ".$sys_tables['context_advertisements'].".id
                                                                WHERE ".$sys_tables['context_advertisements'].".id_campaign = ".$id_campaign.((!empty($ids))?$additional_cond:"")."
                                                                GROUP BY ".$sys_tables['context_advertisements'].".id
                                                                ORDER BY ".$sys_tables['context_advertisements'].".id ASC");
                                    //заполняем массивы для данных и полей
                                    $ids_result = array();
                                    foreach($list_data as $value){
                                        if(!empty($value['amount'])){
                                            $data[] = array((string)$value['title'],(float)$value['amount']);
                                            $ids_result[] = $value['id'];
                                        }
                                    }
                                    $colors = array_reverse($colors,true);
                                    //убираем цвета тех, кто не попал в результат
                                    if(!empty($ids_list))
                                        foreach($ids_list as $ids_key=>$ids_item){
                                            if(empty($ids_result) || !in_array($ids_item,$ids_result)) unset($colors[$ids_key]);
                                        }
                                    $colors = array_reverse($colors,true);
                                }
                                elseif($gr_selection == 'fin'){
                                    if(!empty($ids)){
                                        $additional_cond = " AND uf.id_parent IN (".$ids.")";
                                    }
                                    $list_data = $db->fetchall("SELECT IF(uf.expenditure IS NULL,0,SUM(uf.expenditure)) AS amount,
                                                                       c_adv.id,
                                                                       c_adv.title
                                                                   FROM ".$sys_tables['users_finances']." AS uf
                                                                   LEFT JOIN ".$sys_tables['context_advertisements']." AS c_adv
                                                                   ON uf.id_parent = c_adv.id
                                                                   WHERE uf.obj_type = 'context_banner' AND 
                                                                         uf.`datetime` >= '".$date_start_formatted."' AND
                                                                         uf.`datetime` <= '20".$date_end_formatted." 99'
                                                                         ".((!empty($ids))?$additional_cond:"")."
                                                                   GROUP BY c_adv.id
                                                                   ORDER BY c_adv.id ASC",'date');
                                    //заполняем массивы для данных и полей
                                    $ids_result = array();
                                    foreach($list_data as $value){
                                        if(!empty($value['amount'])){
                                            $data[] = array((string)$value['title'],(float)$value['amount']);
                                            $ids_result[] = $value['id'];
                                        }
                                    }
                                    $colors = array_reverse($colors,true);
                                    //убираем цвета тех, кто не попал в результат
                                    if(!empty($ids_list))
                                        foreach($ids_list as $ids_key=>$ids_item){
                                            if(empty($ids_result) || !in_array($ids_item,$ids_result)) unset($colors[$ids_key]);
                                        }
                                    $colors = array_reverse($colors,true);
                                    //чтобы индексы шли по порядку, без пропусков
                                    $colors = explode(',',implode(',',$colors));
                                }
                                else{
                                    $where = array();
                                    if(!empty($date_start_formatted))
                                        $where[] = "`date`>='".$date_start_formatted."'";
                                    if(!empty($date_end_formatted))
                                        $where[] = "`date`<='".$date_end_formatted."'";
                                    $where[] = $sys_tables['context_advertisements'].".id_campaign = ".$id_campaign;
                                    if(!empty($ids))
                                        $where[] = $sys_tables['context_advertisements'].".id IN (".$ids.")";
                                    if(!empty($where)) $where = "WHERE ".implode(" AND ",$where);
                                    
                                    $list_data = $db->fetchall("SELECT ".$sys_tables['context_advertisements'].".id,title,IF(amount IS NULL,0,SUM(amount)) AS amount
                                                                FROM ".$sys_tables['context_stats_'.$gr_selection.'_full']."
                                                                LEFT JOIN ".$sys_tables['context_advertisements']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_'.$gr_selection.'_full'].".id_parent
                                                                ".$where."
                                                                GROUP BY ".$sys_tables['context_advertisements'].".id
                                                                ORDER BY ".$sys_tables['context_advertisements'].".id ASC");
                                    //заполняем массивы для данных и полей
                                    foreach($list_data as $value){
                                        if(!empty($value['amount'])){
                                            $data[] = array((string)$value['title'],(int)$value['amount']);
                                            $ids_result[] = $value['id'];
                                        }
                                    }
                                    //$colors = array_reverse($colors,true);
                                    //убираем цвета тех, кто не попал в результат
                                    if(!empty($ids_list))
                                        foreach($ids_list as $ids_key=>$ids_item){
                                            if(empty($ids_result) || !in_array($ids_item,$ids_result)) unset($colors[$ids_key]);
                                        }
                                    //$colors = array_reverse($colors,true);
                                    //чтобы индексы шли по порядку, без пропусков
                                    $colors = explode(',',implode(',',$colors));
                                }
                                
                                $global_result[] = array(
                                                             'ok' => true
                                                            ,'type' => 'Pie'
                                                            ,'data' => $data
                                                            ,'fields' => $fields
                                                            ,'graphic_number' => 4
                                                            ,'width'=>760
                                                            ,'height'=>380
                                                            ,'is3D'=>'false'
                                                            ,'colors'=>$colors
                                                            ,'selection'=>$gr_selection);
                                break;
                        }
                    }
                }
                
                $ajax_result['global_result'] = $global_result;
                $ajax_result['group_by'] = $group_by;
                $ajax_result['ok'] = true;
                break;
            //общий список рекламных блоков
            default:
                $GLOBALS['js_set'][] = '/modules/context_campaigns/ajax_actions_advlist.js';
                $module_template = 'context_adv_list.html'; 
                $id_campaign = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
                Response::SetString('h1',"Личный кабинет");
                
                ///работаем с формой редактирования кампании
                
                //читаем данные по кампании
                $campaign_info = $db->fetch("SELECT *,
                                                    DATE_FORMAT(date_start,'%d.%m.%Y') AS date_start_formatted,
                                                    DATE_FORMAT(date_end,'%d.%m.%Y') AS date_end_formatted
                                             FROM ".$sys_tables['context_campaigns']."
                                             WHERE id = ".$id_campaign);
                
                
                ///читаем данные для общей всплывашки выбора геотаргетинга
                $targeting_list = $db->fetchall("SELECT ".$sys_tables['context_tags'].".txt_field,
                                                        GROUP_CONCAT(".$sys_tables['context_tags'].".id) AS field_ids,
                                                        GROUP_CONCAT(".$sys_tables['context_tags'].".source_id) AS sources_ids,
                                                        GROUP_CONCAT(".$sys_tables['context_tags'].".txt_value SEPARATOR '~') AS field_values,
                                                        GROUP_CONCAT(".$sys_tables['context_tags'].".txt_field) AS field_titles
                                                 FROM ".$sys_tables['context_tags_conformity']."
                                                 LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['context_tags_conformity'].".id_tag = ".$sys_tables['context_tags'].".id
                                                 GROUP BY txt_field",'txt_field');
                ////сразу набираем данные для всплывашки
                ///типы объектов
                
                $sql = "SELECT id,txt_field,`txt_value` AS title, '' AS estate_type
                        FROM ".$sys_tables['context_tags']."
                        WHERE txt_field LIKE '%type_object%'
                        ORDER BY txt_field";
                $types_all = $db->fetchall($sql, false);
                //читаем список тегов, общих для нескольких типов недвижимости
                $sql = "SELECT `txt_value` AS title, COUNT(*) AS amount, GROUP_CONCAT(id) AS ids
                        FROM ".$sys_tables['context_tags']."
                        WHERE txt_field LIKE '%type_object%'
                        GROUP BY title";
                $types_equal = $db->fetchall($sql,false);
                
                //для тегов, которые общие, делаем дополнительное поле - там будут скобки с типом
                foreach($types_equal as $key=>$item){
                    if($item['amount']>1){
                        $ids = explode(',',$item['ids']);
                        foreach($types_all as $list_key=>$list_item){
                            if(in_array($list_item['id'],$ids))
                                switch(TRUE){
                                    case preg_match('/live/',$list_item['txt_field']): $types_all[$list_key]['estate_type'] = "(Жилая)";break;
                                    case preg_match('/commercial/',$list_item['txt_field']): $types_all[$list_key]['estate_type'] = "(Коммерческая)";break;
                                    case preg_match('/country/',$list_item['txt_field']): $types_all[$list_key]['estate_type'] = "(Загородная)";break;
                                }
                        }
                    }
                }
                $tags_filter['type-objects'] = $types_all;
                $selected_items = array();
                if(!empty($targeting_list['type_objects_live'])) $selected_items[] = $targeting_list['type_objects_live']['field_ids'];
                if(!empty($targeting_list['type_objects_commercial'])) $selected_items[] = $targeting_list['type_objects_commercial']['field_ids'];
                if(!empty($targeting_list['type_objects_country'])) $selected_items[] = $targeting_list['type_objects_country']['field_ids'];
                $selected_items = explode(',',implode(',',$selected_items));
                $tags_filter['type-objects']['selected'] = implode(',',$selected_items);
                ///районы
                $tags_filter['districts'] = $db->fetchall("SELECT id AS id_tag,txt_value AS title,source_id AS id FROM ".$sys_tables['context_tags']." WHERE txt_field = 'districts' ORDER BY title");
                if(!empty($targeting_list['districts'])) {
                    foreach($tags_filter['districts'] as $k=>$item){
                        if(in_array($item['id'], explode(',',$targeting_list['districts']['sources_ids']))){
                            $tags_filter['districts'][$k]['on'] = true;
                            $tags_filter['districts']['selected'][] = $tags_filter['districts'][$k]['id'];
                        } 
                    }
                }
                //районы области
                //$tags_filter['district_areas'] = $db->fetchall("SELECT id, offname as title FROM ".$sys_tables['geodata']." WHERE a_level = 2 ORDER BY offname");
                $tags_filter['district_areas'] = $db->fetchall("SELECT id AS id_tag,txt_value AS title,source_id AS id FROM ".$sys_tables['context_tags']." WHERE txt_field = 'district_areas' ORDER BY title");
                if(!empty($targeting_list['district_areas'])) {
                    foreach($tags_filter['district_areas'] as $k=>$item){
                        if(in_array($item['id'], explode(',',$targeting_list['district_areas']['sources_ids']))){
                            $tags_filter['district_areas'][$k]['on'] = true;
                            $tags_filter['district_areas']['selected'][] = $tags_filter['district_areas'][$k]['id'];
                        }
                    }
                }
                //метро
                //$tags_filter['subways'] = $db->fetchall("SELECT id,title, id_subway_line as line_id FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ORDER BY title");
                //$tags_filter['subways'] = $db->fetchall("SELECT id AS id_tag,txt_value AS title,source_id AS id FROM ".$sys_tables['context_tags']." WHERE txt_field = 'subways' ORDER BY title");
                $tags_filter['subways'] = $db->fetchall("SELECT ".$sys_tables['context_tags'].".id AS id_tag,
                                                                            ".$sys_tables['context_tags'].".txt_value AS title,
                                                                            ".$sys_tables['context_tags'].".source_id AS id,
                                                                            ".$sys_tables['subways'].".id_subway_line as line_id
                                                                     FROM ".$sys_tables['context_tags']." 
                                                                     LEFT JOIN ".$sys_tables['subways']." ON ".$sys_tables['context_tags'].".source_id = ".$sys_tables['subways'].".id AND ".$sys_tables['context_tags'].".txt_field = 'subways'
                                                                     WHERE txt_field = 'subways' ORDER BY title");
                if(!empty($targeting_list['subways'])) {
                    foreach($tags_filter['subways'] as $k=>$item){
                        if(in_array($item['id'], explode(',',$targeting_list['subways']['sources_ids']))){
                            $tags_filter['subways'][$k]['on'] = true;
                            $tags_filter['subways']['selected'][] = $tags_filter['subways'][$k]['id'];
                        } 
                    }
                }
                Response::SetArray('tags_filter',$tags_filter);
                ///
                
                
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                if(!empty($campaign_info))
                    //если это кампания пользователя, или его клиента(если пользователь - менеджер)
                    $allowed_users_list = explode(',',$allowed_users);
                    if(in_array($campaign_info['id_user'],$allowed_users_list) || $campaign_info['id_creator'] == $auth->id)
                        foreach($campaign_info as $key=>$field){
                            if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['value'] = $campaign_info[$key];
                        }
                    else{
                        //если кампания не наша, отдаем 404
                        $this_page->http_code=404;
                        break;
                    }
                //при наличии, заменяем дату на форматированную в дд.мм.гггг
                if(!empty($mapping['context_campaigns']['date_start']['value'])) $mapping['context_campaigns']['date_start']['value'] = $campaign_info['date_start_formatted'];
                if(!empty($mapping['context_campaigns']['date_end']['value'])) $mapping['context_campaigns']['date_end']['value'] = $campaign_info['date_end_formatted'];
                
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                if(!empty($post_parameters)) $post_parameters = $post_parameters['campaign_data'];
                // если была отправка формы - начинаем обработку
                if($ajax_mode && !empty($post_parameters['submit'])){
                    //Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    unset($campaign_info['date_start_formatted']);
                    unset($campaign_info['date_end_formatted']);
                    
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['value'] = $post_parameters[$key];
                    }
                    //переформатируем дату для записи в базу
                    if(!empty($post_parameters['date_start'])){
                        $post_parameters['date_start'] = implode('-',array_reverse(explode('.',$post_parameters['date_start'])));
                        $mapping['context_campaigns']['date_start']['value'] = $post_parameters['date_start'];
                        $campaign_info['date_start'] = $post_parameters['date_start'];
                    }                        
                    if(!empty($post_parameters['date_end'])){
                        $post_parameters['date_end'] = implode('-',array_reverse(explode('.',$post_parameters['date_end'])));
                        $mapping['context_campaigns']['date_end']['value'] = $post_parameters['date_end'];
                        $campaign_info['date_end'] = $post_parameters['date_end'];
                    }
                    
                    //если дата старта позже сегодняшней или дата окончания раньше сегодняшней, убираем в архив
                    if(!empty($post_parameters['date_start']) && (strtotime($post_parameters['date_start'])>strtotime(date("d.m.Y")))||
                       (!empty($post_parameters['date_end']) && (strtotime($post_parameters['date_end'])<strtotime(date("d.m.Y")))) ){
                           $post_parameters['published'] = 2;
                    }
                    
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['context_campaigns']);
                    
                    //проверяем, что баланс пользователя >= бюджета кампании
                    $user_balance = $db->fetch("SELECT balance FROM ".$sys_tables['users']." WHERE id = ".$auth->id)['balance'];
                    $post_parameters['balance'] = preg_replace('/[^0-9]/','',$post_parameters['balance']);
                    if($user_balance<$post_parameters['balance'])
                        $errors['balance'] = "Бюджет не должен превышать баланс вашего аккаунта";
                    
                    //если хотим опубликовать, проверяем, есть ли фотография и указан ли тип объекта (кроме стройки)
                    if($mapping['context_campaigns']['published']['value'] == 1){
                        //если баланс нулевой, сразу пишем ошибку
                        if(!$post_parameters['balance'] > 0) $errors['balance'] = "Для публикации установите положительный бюджет";
                    }
                    elseif(!empty($campaign_info['id']) && ($campaign_info['published'] == 1) && empty($errors)){
                        //если кампания не архивная и не новая, убираем в архив все объявления кампании и оповещаем менеджера, что кампания идет в архив
                        $db->querys("UPDATE ".$sys_tables['context_advertisements']." SET published = 2 WHERE id_campaign = ".$campaign_info['id']);
                        //читаем информацию по компании, чья это кампания
                        $agency_info = $db->fetch("SELECT ".$sys_tables['agencies'].".id,
                                                          ".$sys_tables['agencies'].".title AS agency_title,
                                                          ".$sys_tables['agencies'].".email,
                                                          ".$sys_tables['managers'].".email AS manager_email,
                                                          ".$sys_tables['managers'].".name AS manager_name
                                                   FROM ".$sys_tables['users']."
                                                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                                   LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                                   WHERE ".$sys_tables['users'].".id = ".$campaign_info['id_user']);
                        $notification_data['manager_email'] = $agency_info['manager_email'];
                        $notification_data['manager_name'] = explode(' ',$agency_info['manager_name'])[0];
                        $notification_data['agency_title'] = $agency_info['agency_title'];
                        $notification_data['cmp_title'] = $campaign_info['title'];
                        contextCampaigns::Notification(4,$notification_data,false,true);
                    }
                    
                    if(!empty($errors)){
                        // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                        foreach($errors as $key=>$value){
                            if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['error'] = $value;
                        }
                        $ajax_result['errors'] = json_encode($errors);
                    }
                    
                    
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($campaign_info as $key=>$field){
                            if (isset($mapping['context_campaigns'][$key]['value'])) 
                                $campaign_info[$key] = strip_tags($mapping['context_campaigns'][$key]['value'],'<table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                        }
                        
                        // сохранение в БД
                        if(!empty($campaign_info['id'])){
                            $res = $db->updateFromArray($sys_tables['context_campaigns'], $campaign_info,'id');
                        }else{
                            //добавляем id пользователя, создавшего эту кампанию
                            if(empty($campaign_info['id_user'])) $campaign_info['id_user'] = $auth->id;
                            if(empty($campaign_info['id_creator'])) $campaign_info['id_creator'] = $auth->id;
                            $res = $db->insertFromArray($sys_tables['context_campaigns'], $campaign_info);
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                header('Location: '.Host::getWebPath('/members/context_campaigns/'.$new_id.'/'));
                                exit(0);
                            }
                        }
                        $ajax_result['ok'] = $res;// результат сохранения
                    }
                    else $ajax_result['errors'] = json_encode($errors); // ошибки
                    break;
                }
                // если мы попали на страницу редактирования путем редиректа с добавления, 
                // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                ///
                
                ///далее все, что отностится к списку объявлений
                // создаем пагинатор для списка
                $condition = "";
                $sql_select = "SELECT ".$sys_tables['context_advertisements'].".id,
                                      ".$sys_tables['context_advertisements'].".title,
                                      ".$sys_tables['context_advertisements'].".id_main_photo,
                                      ".$sys_tables['context_advertisements'].".published";
                $sql_condition = "FROM ".$sys_tables['context_advertisements']."
                                  LEFT JOIN ".$sys_tables['context_advertisements_photos']." ON ".$sys_tables['context_advertisements'].".id_main_photo = ".$sys_tables['context_advertisements_photos'].".id
                                  WHERE id_campaign = ".$id_campaign;
                $paginator = new Paginator(false, 10, false,"SELECT COUNT(*) as items_count ".$sql_condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = array();
                // ссылка пагинатора
                
                $paginator->link_prefix = '/members/context_campaigns/'.$id_campaign    // модуль
                                          ."/?"                                         // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)           // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера */
                $page = Request::GetInteger('page',METHOD_GET);
                if(empty($page)) $page  = 1;
                //формирование url для пагинатора
                $paginator->link_prefix = '/'.$this_page->requested_path.'/?page=';
                if($paginator->pages_count>1){
                    Response::SetArray('paginator', $paginator->Get($page));
                }
                Response::SetString('img_folder', Config::$values['img_folders']['context_advertisements']); // папка для картинок кампаний
                
                $sql = "SELECT ".$sys_tables['context_advertisements'].".id,
                               ".$sys_tables['context_advertisements'].".title,
                               ".$sys_tables['context_advertisements'].".id_main_photo,
                               ".$sys_tables['context_advertisements'].".price_floor,
                               ".$sys_tables['context_advertisements'].".price_top,
                               ".$sys_tables['context_advertisements'].".estate_type,
                               ".$sys_tables['context_advertisements'].".deal_type,
                               ".$sys_tables['context_advertisements'].".published,
                               IF((".$sys_tables['context_advertisements'].".price_floor>0 OR ".$sys_tables['context_advertisements'].".price_top>0),1,0) AS has_price,
                               ".$sys_tables['context_places'].".width,
                               ".$sys_tables['context_places'].".height,
                               ".$sys_tables['context_advertisements'].".published,
                               SUM(".$sys_tables['context_finances'].".expenditure) AS total_expenditure,
                               ".$sys_tables['context_advertisements_photos'].".name as photo,
                               SUBSTRING(".$sys_tables['context_advertisements_photos'].".name,1,2) AS folder,
                               s_full.amount AS shows_full,
                               c_full.amount AS clicks_full,
                               s_day.amount AS shows_day,
                               c_day.amount AS clicks_day,
                               CAST(CAST( ((IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount))/(IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) AS CTR
                        FROM ".$sys_tables['context_advertisements']."
                        LEFT JOIN ".$sys_tables['context_places']." ON ".$sys_tables['context_places'].".id = ".$sys_tables['context_advertisements'].".id_place
                        LEFT JOIN (SELECT ".$sys_tables['context_stats_show_full'].".id_parent, SUM(amount) AS amount FROM ".$sys_tables['context_stats_show_full']." GROUP BY ".$sys_tables[
                        'context_stats_show_full'].".id_parent) s_full 
                        ON s_full.id_parent = ".$sys_tables['context_advertisements'].".id
                        LEFT JOIN (SELECT ".$sys_tables['context_stats_click_full'].".id_parent, SUM(amount) AS amount FROM ".$sys_tables['context_stats_click_full']." GROUP BY ".$sys_tables['context_stats_click_full'].".id_parent) c_full
                        ON c_full.id_parent = ".$sys_tables['context_advertisements'].".id
                        LEFT JOIN (SELECT ".$sys_tables['context_stats_show_day'].".id_parent, COUNT(*) AS amount FROM ".$sys_tables['context_stats_show_day']." GROUP BY ".$sys_tables['context_stats_show_day'].".id_parent) s_day
                        ON s_day.id_parent = ".$sys_tables['context_advertisements'].".id
                        LEFT JOIN (SELECT ".$sys_tables['context_stats_click_day'].".id_parent, COUNT(*) AS amount FROM ".$sys_tables['context_stats_click_day']." GROUP BY ".$sys_tables['context_stats_click_day'].".id_parent) c_day
                        ON c_day.id_parent = ".$sys_tables['context_advertisements'].".id
                        LEFT JOIN ".$sys_tables['context_finances']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_finances'].".id_parent
                        LEFT JOIN ".$sys_tables['context_advertisements_photos']." ON ".$sys_tables['context_advertisements'].".id_main_photo = ".$sys_tables['context_advertisements_photos'].".id";
                $condition = " id_campaign = ".$id_campaign;
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " GROUP BY ".$sys_tables['context_advertisements'].".id";
                $sql .= " ORDER BY ".$sys_tables['context_advertisements'].".`id` DESC";
                $sql .= " LIMIT ".$paginator->getLimitString($page);
                $list = $db->fetchall($sql);
                $adv_photo_folder=Config::$values['img_folders']['articles'];
                foreach($list as $list_key=>$value){
                    
                    //определяем статус
                    switch($value['published']){
                        case '1': $list[$list_key]['published'] = 'active';break;
                        case '2': $list[$list_key]['published'] = 'unactive';break;
                        case '3': $list[$list_key]['published'] = 'moderation';break;
                    }
                    
                    //считаем общие клики и показы
                    $list[$list_key]['shows'] = $list[$list_key]['shows_full'] + $list[$list_key]['shows_day'];
                    $list[$list_key]['clicks'] = $list[$list_key]['clicks_full'] + $list[$list_key]['clicks_day'];
                    
                    //читаем таргетинг по типу недвижимости и цене для всплывашки
                    $targeting_list = $db->fetchall("SELECT ".$sys_tables['context_tags'].".txt_field,
                                                                    GROUP_CONCAT(".$sys_tables['context_tags'].".id) AS field_ids,
                                                                    GROUP_CONCAT(".$sys_tables['context_tags'].".source_id) AS sources_ids,
                                                                    GROUP_CONCAT(".$sys_tables['context_tags'].".txt_value SEPARATOR '~') AS field_values,
                                                                    GROUP_CONCAT(".$sys_tables['context_tags'].".txt_field) AS field_titles
                                                             FROM ".$sys_tables['context_tags_conformity']."
                                                             LEFT JOIN ".$sys_tables['context_tags']." ON ".$sys_tables['context_tags_conformity'].".id_tag = ".$sys_tables['context_tags'].".id
                                                             WHERE ".$sys_tables['context_tags_conformity'].".id_context = ".$value['id']." AND txt_field LIKE '%type_objects%'
                                                             GROUP BY txt_field",'id');
                    if(!empty($targeting_list)){
                        //здесь будем накапливать данные по типам недвижимости, чтобы потом запихнуть в один массив
                        $types_field_ids = "";$types_source_ids = "";$types_field_values = "";$types_field_titles = "";
                        //перебираем категории тегов, формируем список
                        foreach($targeting_list as $key=>$values){
                            $sources_ids = explode(',',$values['sources_ids']);
                            $tags_ids = explode(',',$values['field_ids']);
                            $field_values = explode('~',$values['field_values']);
                            $field_titles = explode(',',$values['field_titles']);
                            $fields_list = array();
                            $sources_check = preg_replace('/,0/sui','',$values['sources_ids']);
                            //если источник не указан, значит список тегов задан вручную
                            if(empty($sources_check))
                                foreach($tags_ids as $k=>$field_id)
                                    $fields_list[$field_id] = array('value'=>$field_values[$k]);
                            else
                                foreach($sources_ids as $k=>$source_id)
                                    $fields_list[$source_id] = array('tag_id'=>$tags_ids[$k],'value'=>$field_values[$k],'field_title'=>$field_titles[$k]);
                            $targeting_list[$targeting_list[$key]['txt_field']] = $fields_list;
                            unset($targeting_list[$key]);
                            
                            //собираем типы объектов в один набор
                            if(preg_match('/type_objects/',$values['txt_field'])){
                                $types_field_ids .= ((empty($types_field_ids))?$values['field_ids']:','.$values['field_ids']);
                                $types_source_ids .= ((empty($types_source_ids))?$values['sources_ids']:','.$values['sources_ids']);
                                $types_field_values .= ((empty($types_field_values))?$values['field_values']:'~'.$values['field_values']);
                                $types_field_titles .= ((empty($types_field_titles))?$values['field_titles']:','.$values['field_titles']);
                            }
                        }
                        unset($targetings_list);
                        //заполняем информацию по тегам "тип объекта"
                        if(!empty($types_field_ids)){
                            $types_field_ids = explode(',',$types_field_ids);
                            $types_source_ids = explode(',',$types_source_ids);
                            $types_field_values = explode('~',$types_field_values);
                            $types_field_titles = explode(',',$types_field_titles);
                            Response::SetString('type_objects_tags',implode(',',$types_field_ids));
                            $type_objects = array();
                            //заполняем массив типов объектов из строчек
                            foreach($types_field_ids as $key=>$item){
                                $type_objects[] = array('id'=>$types_field_ids[$key],'source_id'=>$types_source_ids[$key],'value'=>$types_field_values[$key],'txt_field'=>$types_field_titles[$key],'estate_type'=>"");
                            }
                            //читаем список тегов, общих для нескольких типов недвижимости
                            $sql = "SELECT `txt_value` AS title, COUNT(*) AS amount, GROUP_CONCAT(id) AS ids
                                    FROM ".$sys_tables['context_tags']."
                                    WHERE txt_field LIKE '%type_object%'
                                    GROUP BY title";
                            $list_equal = $db->fetchall($sql,false);
                            //для тегов, которые общие, заполняем дополнительное поле - там будут скобки с типом
                            foreach($list_equal as $key=>$item){
                                if($item['amount']>1){
                                    $ids = explode(',',$item['ids']);
                                    foreach($type_objects as $to_key=>$list_item){
                                        //если тег в списке общих, пишем ему дополнительное поле
                                        if(in_array($list_item['id'],$ids)){
                                            switch(TRUE){
                                                case preg_match('/live/',$list_item['txt_field']): $type_objects[$to_key]['estate_type'] = "(Жилая)";break;
                                                case preg_match('/commercial/',$list_item['txt_field']): $type_objects[$to_key]['estate_type'] = "(Коммерческая)";break;
                                                case preg_match('/country/',$list_item['txt_field']): $type_objects[$to_key]['estate_type'] = "(Загородная)";break;
                                            }
                                        }
                                    }
                                }
                            }
                            $res_str = array();
                            foreach($type_objects as $key=>$item){
                                $res_str[] = $type_objects[$key]['value'].$type_objects[$key]['estate_type'];
                            }
                            $list[$list_key]['type_objects'] = ((!empty($res_str))?'<i>'.implode(',</i><i>',$res_str).'</i>':"");
                            $list[$list_key]['has_targeting'] = (!empty($list[$list_key]['type_objects'])||!empty($list[$list_key]['has_price']));
                        }
                    }
                    
                    //разбираем данные по типу сделки
                    $list[$list_key]['deal_text'] = array();
                    switch(true){
                        case preg_match('/1/',$list[$list_key]['deal_type']): $list[$list_key]['deal_text'][] = '<i>Аренда</i>';
                        case preg_match('/2/',$list[$list_key]['deal_type']): $list[$list_key]['deal_text'][] = (empty($list[$list_key]['deal_text']))?'<i>Продажа</i>':'<i>продажа</i>';
                    }
                    if(!empty($list[$list_key]['deal_text'])) $list[$list_key]['deal_text'] = implode(' и ',$list[$list_key]['deal_text']);
                    else $list[$list_key]['deal_text'] = "";
                    
                    //разбираем данные по типу недвижимости
                    $list[$list_key]['estate_text'] = array();
                    if(preg_match('/2/',$list[$list_key]['estate_type'])) $list[$list_key]['estate_text'][] = '<i>новостроек</i>';
                    if(preg_match('/1/',$list[$list_key]['estate_type'])) $list[$list_key]['estate_text'][] = '<i>жилой</i>';
                    if(preg_match('/3/',$list[$list_key]['estate_type'])) $list[$list_key]['estate_text'][] = '<i>коммерческой</i>';
                    if(preg_match('/4/',$list[$list_key]['estate_type'])) $list[$list_key]['estate_text'][] = '<i>загородной</i>';
                    if(!empty($list[$list_key]['estate_text'])) $list[$list_key]['estate_text'] = implode(', ',$list[$list_key]['estate_text']);
                    if(strpos(',',$list[$list_key]['estate_text']) || !strpos('новостро',$list[$list_key]['estate_text'])) 
                        $list[$list_key]['deal_text'] = $list[$list_key]['deal_text']." ".$list[$list_key]['estate_text']." недвижимости";
                }
                
                // формирование списка
                Response::SetArray('list', $list);
                //общее количество объявлений для счетчика
                Response::SetString('items_count',$paginator->items_count);
                Response::SetInteger('id_campaign',$id_campaign);
                Response::SetArray('data_mapping_campaign',$mapping['context_campaigns']);
            break;
        }
        break;
    //обновляемая статистика для списка рекламных кампаний
    case $action == 'campaigns_stats':
        if($ajax_mode){
            //$user_id = $auth->id;
            $sql = "SELECT ".$sys_tables['context_campaigns'].".id,
                            COUNT(DISTINCT ".$sys_tables['context_advertisements'].".id) AS adv_amount,
                            ".$sys_tables['context_campaigns'].".balance,
                            IF(s_day.amount IS NULL,0,s_day.amount) AS shows_day,
                            IF(c_day.amount IS NULL,0,c_day.amount) AS clicks_day,
                            (IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount)) AS shows,
                            (IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount)) AS clicks,
                            CAST(CAST( ((IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount))/(IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) AS ctr
                    FROM ".$sys_tables['context_campaigns']."
                    LEFT JOIN ".$sys_tables['context_advertisements']." ON ".$sys_tables['context_campaigns'].".id = ".$sys_tables['context_advertisements'].".id_campaign
                    LEFT JOIN (SELECT ".$sys_tables['context_advertisements'].".id_campaign, SUM(amount) AS amount 
                       FROM ".$sys_tables['context_advertisements']."
                       LEFT JOIN ".$sys_tables['context_stats_show_full']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_show_full'].".id_parent
                       GROUP BY ".$sys_tables['context_advertisements'].".id_campaign) s_full
                    ON s_full.id_campaign = ".$sys_tables['context_campaigns'].".id
                    LEFT JOIN (SELECT ".$sys_tables['context_advertisements'].".id_campaign, SUM(amount) AS amount 
                               FROM ".$sys_tables['context_advertisements']."
                               LEFT JOIN ".$sys_tables['context_stats_click_full']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_click_full'].".id_parent
                               GROUP BY ".$sys_tables['context_advertisements'].".id_campaign) c_full
                    ON c_full.id_campaign = ".$sys_tables['context_campaigns'].".id
                    LEFT JOIN (SELECT ".$sys_tables['context_advertisements'].".id_campaign, COUNT(*) AS amount 
                               FROM ".$sys_tables['context_advertisements']."
                               RIGHT JOIN ".$sys_tables['context_stats_show_day']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_show_day'].".id_parent
                               WHERE ".$sys_tables['context_advertisements'].".published = 1
                               GROUP BY ".$sys_tables['context_advertisements'].".id_campaign) s_day
                    ON s_day.id_campaign = ".$sys_tables['context_campaigns'].".id
                    LEFT JOIN (SELECT ".$sys_tables['context_advertisements'].".id_campaign, COUNT(*) AS amount 
                               FROM ".$sys_tables['context_advertisements']."
                               RIGHT JOIN ".$sys_tables['context_stats_click_day']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_click_day'].".id_parent
                               WHERE ".$sys_tables['context_advertisements'].".published = 1
                               GROUP BY ".$sys_tables['context_advertisements'].".id_campaign) c_day
                    ON c_day.id_campaign = ".$sys_tables['context_campaigns'].".id
                    WHERE ".$sys_tables['context_campaigns'].".id_user IN (".$allowed_users.") OR ".$sys_tables['context_campaigns'].".id_creator = ".$auth->id."
                    GROUP BY ".$sys_tables['context_campaigns'].".id";
            $campaigns_list = $db->fetchall($sql);
            $ajax_result['data'] = $campaigns_list;
            $ajax_result['ok'] = true;
        }
        break;
    ///////////
    //добавление рекламной кампании
    case $action == 'add':
        $module_template = 'context_adv_list.html'; 
        $GLOBALS['js_set'][] = '/modules/context_campaigns/ajax_actions_advlist.js';
        //$id_campaign = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
        Response::SetString('h1',"Личный кабинет");
        ///работаем с формой редактирования кампании
        //читаем данные по кампании
        $campaign_info = $db->prepareNewRecord($sys_tables['context_campaigns']);
        //перестраиваем дату для отображения
        $campaign_info['date_start'] = implode('.',array_reverse(explode('-',$campaign_info['date_start'])));
        $campaign_info['date_end'] = implode('.',array_reverse(explode('-',$campaign_info['date_end'])));
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        if(!empty($campaign_info))
            foreach($campaign_info as $key=>$field){
                if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['value'] = $campaign_info[$key];
            }
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);
        if(!empty($post_parameters)) $post_parameters = $post_parameters['campaign_data'];
        // если была отправка формы - начинаем обработку
        if($ajax_mode && !empty($post_parameters['submit'])){
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['value'] = $post_parameters[$key];
            }
                
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['context_campaigns']);
            
            //проверяем, что указанные даты позже, чем сегодня
            if(empty($post_parameters['date_start']) || (strtotime($post_parameters['date_start'])<strtotime(date("d.m.Y"))))
                $errors['date_start'] = "Дата должна быть позже чем сегодня";
            if(empty($post_parameters['date_end']) || (strtotime($post_parameters['date_start'])<strtotime(date("d.m.Y"))))
                $errors['date_start'] = "Дата должна быть позже чем сегодня";
            
            //проверяем, что баланс пользователя >= бюджета кампании
            $user_balance = $db->fetch("SELECT balance FROM ".$sys_tables['users']." WHERE id = ".$auth->id)['balance'];
            $post_parameters['balance'] = preg_replace('/[^0-9]/','',$post_parameters['balance']);
            if($user_balance<$post_parameters['balance'])
                $errors['balance'] = "Бюджет не должен превышать баланс вашего аккаунта";
            
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping['context_campaigns'][$key])) $mapping['context_campaigns'][$key]['error'] = $value;
            }
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($campaign_info as $key=>$field){
                    if (isset($mapping['context_campaigns'][$key]['value'])) 
                        $campaign_info[$key] = strip_tags($mapping['context_campaigns'][$key]['value'],'<table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3>');
                }
                
                //добавляем id пользователя, создавшего эту кампанию
                $campaign_info['id_user'] = $auth->id;
                $campaign_info['id_creator'] = $auth->id;
                //переформатируем дату для записи в базу
                if(!empty($post_parameters['date_start']))
                    $campaign_info['date_start'] = implode('-',array_reverse(explode('.',$post_parameters['date_start'])));
                if(!empty($post_parameters['date_end']))
                    $campaign_info['date_end'] = implode('-',array_reverse(explode('.',$post_parameters['date_end'])));
                // сохранение в БД
                if(!empty($campaign_info['id'])){
                    $res = $db->updateFromArray($sys_tables['context_campaigns'], $campaign_info, 'id');
                }else{
                    $res = $db->insertFromArray($sys_tables['context_campaigns'], $campaign_info, 'id');
                    $new_id = $db->insert_id;
                }
                $ajax_result['ok'] = $res;// результат сохранения
                $ajax_result['id'] = $new_id;
            }
            else $ajax_result['errors'] = json_encode($errors); // ошибки
        }
        Response::SetArray('data_mapping_campaign',$mapping['context_campaigns']);
        ///
        break;
    //удаление рекламной кампании
    case $action == 'del':
        if($ajax_mode){
            $res = true;
            //читаем id кампании, которую будем удалять
            $campaign_id = Convert::ToInt($this_page->page_parameters[1]);
            //если что-то не так, сразу выходим
            if(empty($campaign_id)) break;
            //читаем id объявлений этой кампании и id их фотографий
            $adv_list = $db->fetch("SELECT GROUP_CONCAT(context_advertisements.id) AS adv_ids,GROUP_CONCAT(context_advertisements_photos.id) AS photos_ids
                                    FROM ".$sys_tables['context_advertisements']." 
                                    LEFT JOIN ".$sys_tables['context_advertisements_photos']." ON ".$sys_tables['context_advertisements_photos'].".id_parent = ".$sys_tables['context_advertisements'].".id
                                    WHERE id_campaign = ".$campaign_id);
            //если есть картинки, удаляем
            if(!empty($adv_list['photos_ids'])){
                //собираем id картинок
                $img_list = explode(',',$adv_list['photos_ids']);
                //удаляем файлы картинок
                foreach($img_list as $key=>$picture_id)
                    Photos::Delete('context_advertisements',$picture_id);
                //удаляем картинки из таблицы
                $db->querys("DELETE FROM ".$sys_tables['context_advertisements_photos']." WHERE id IN (".$adv_list['photos_ids'].")");
            }
            
            //если есть объявления, удаляем
            if(!empty($adv_list['adv_ids'])){
                //удаляем пары объявление-тег из таблицы соответствия
                $db->querys("DELETE FROM ".$sys_tables['context_tags_conformity']." WHERE id_context IN (".$adv_list['adv_ids'].")");
                //удаляем объявления
                $res = $db->querys("DELETE FROM ".$sys_tables['context_advertisements']." WHERE id IN (".$adv_list['adv_ids'].")");
            }
            
            //удаляем саму кампанию
            $res = $res && $db->querys("DELETE FROM ".$sys_tables['context_campaigns']." WHERE id = ?",$campaign_id);
            $ajax_result['ok'] = $res;
            if(empty($res)) $ajax_result['errors'] = $db->error;
            break;
        }
    //статистика рекламных кампаний
    case $action == 'stats':
        Response::SetBoolean('campaigns_stats_page',true);
        
        //для универсального фильтра
        //$GLOBALS['js_set'][] = '/js/jquery.ajax.filter.js';
        
        //читаем id кампании, с карточки которой перешли на страницу и информацию по этой кампании
        //$campaign_id = Request::GetInteger('campaign_id',METHOD_GET);
        //$this_page->requested_url = $this_page->requested_url;
        //$campaign_info = $db->fetch("SELECT * FROM ".$sys_tables['context_campaigns']." WHERE id = ".$id_campaign);
        //Response::SetInteger('campaign_id',$id_campaign);
        //Response::SetArray('campaign_info',$campaign_info);
        
        
        //штуки для графиков
        $GLOBALS['js_set'][] = '/js/graphics.init.js';
        $GLOBALS['js_set'][] = '/js/google.chart.api.js';
        $GLOBALS['css_set'][] = '/modules/cottages/graphics.css';
        
        //для фильтра справа
        
        // мэппинги модуля
        $mapping = include('conf_mapping.php');
        
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js'; 
        $GLOBALS['js_set'][] = '/js/google.chart.api.js';
        $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js';
        $GLOBALS['js_set'][] = '/modules/stats/ajax_actions.js';
        $GLOBALS['js_set'][] = '/js/jquery.datatables.min.js';
        
        $GLOBALS['js_set'][] = '/modules/context_campaigns/ajax_actions_stats.js';
        
        //читаем список кампаний для select
        //$cmp_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['context_campaigns']." WHERE id_user = ".$auth->id);
        $cmp_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['context_campaigns']." WHERE id_user IN (".$allowed_users.") OR id_creator = ".$auth->id);
        Response::SetArray('cmp_list',$cmp_list);
        
        if(!$ajax_mode){
            //формирование фильтра
            Response::SetBoolean('filter', true);
            //период времени
            Response::SetBoolean('filter_time_periods', true);
            //группировка по периодам
            Response::SetBoolean('group_by_periods', true);
            //поле для списка выделенных
            Response::SetBoolean('selected_ids', true);
        }
        
        $module_template = 'client_campaign_stats.html';
        break;
    //список рекламных кампаний
    default:
        
        $sql = "SELECT ".$sys_tables['context_campaigns'].".id,
                        ".$sys_tables['context_campaigns'].".title AS campaign_title,
                        ".$sys_tables['context_campaigns'].".date_end,
                        DATEDIFF(".$sys_tables['context_campaigns'].".date_end,NOW()) AS days_left,
                        ".$sys_tables['context_campaigns'].".title,
                        ".$sys_tables['context_campaigns'].".published,
                        ".$sys_tables['context_campaigns'].".balance,
                        COUNT(DISTINCT ".$sys_tables['context_advertisements'].".id) AS adv_amount,
                        IF(s_day.amount IS NULL,0,s_day.amount) AS shows_day,
                        IF(c_day.amount IS NULL,0,c_day.amount) AS clicks_day,
                        (IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount)) AS shows,
                        (IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount)) AS clicks,
                        CAST(CAST( ((IF(c_day.amount IS NULL,0,c_day.amount)+IF(c_full.amount IS NULL,0,c_full.amount))/(IF(s_day.amount IS NULL,0,s_day.amount)+IF(s_full.amount IS NULL,0,s_full.amount))) AS DECIMAL(5,4))*100 AS DECIMAL(4,2)) AS CTR
                FROM ".$sys_tables['context_campaigns']."
                LEFT JOIN ".$sys_tables['context_advertisements']." ON ".$sys_tables['context_campaigns'].".id = ".$sys_tables['context_advertisements'].".id_campaign
                LEFT JOIN (SELECT ".$sys_tables['context_advertisements'].".id_campaign, SUM(amount) AS amount 
                   FROM ".$sys_tables['context_advertisements']."
                   LEFT JOIN ".$sys_tables['context_stats_show_full']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_show_full'].".id_parent
                   GROUP BY ".$sys_tables['context_advertisements'].".id_campaign) s_full
                ON s_full.id_campaign = ".$sys_tables['context_campaigns'].".id
                LEFT JOIN (SELECT ".$sys_tables['context_advertisements'].".id_campaign, SUM(amount) AS amount 
                           FROM ".$sys_tables['context_advertisements']."
                           LEFT JOIN ".$sys_tables['context_stats_click_full']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_click_full'].".id_parent
                           GROUP BY ".$sys_tables['context_advertisements'].".id_campaign) c_full
                ON c_full.id_campaign = ".$sys_tables['context_campaigns'].".id
                LEFT JOIN (SELECT ".$sys_tables['context_advertisements'].".id_campaign, COUNT(*) AS amount 
                           FROM ".$sys_tables['context_advertisements']."
                           RIGHT JOIN ".$sys_tables['context_stats_show_day']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_show_day'].".id_parent
                           WHERE ".$sys_tables['context_advertisements'].".published = 1
                           GROUP BY ".$sys_tables['context_advertisements'].".id_campaign) s_day
                ON s_day.id_campaign = ".$sys_tables['context_campaigns'].".id
                LEFT JOIN (SELECT ".$sys_tables['context_advertisements'].".id_campaign, COUNT(*) AS amount 
                           FROM ".$sys_tables['context_advertisements']."
                           RIGHT JOIN ".$sys_tables['context_stats_click_day']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['context_stats_click_day'].".id_parent
                           WHERE ".$sys_tables['context_advertisements'].".published = 1
                           GROUP BY ".$sys_tables['context_advertisements'].".id_campaign) c_day
                ON c_day.id_campaign = ".$sys_tables['context_campaigns'].".id
                WHERE ".$sys_tables['context_campaigns'].".id_user IN (".$allowed_users.") OR ".$sys_tables['context_campaigns'].".id_creator = ".$auth->id."
                GROUP BY ".$sys_tables['context_campaigns'].".id";
        $campaigns_list = $db->fetchall($sql);
        $amount_list = array('all'=>0,'active'=>0,'moderation'=>0,'archive'=>0);
        $amount_list['all'] = count($campaigns_list);
        $campaigns_active = array();$campaigns_moder = array();$campaigns_arch = array();
        //разбиваем все прочитанные кампании по вкладкам
        foreach($campaigns_list as $key=>$item){
            switch($item['published']){
                case 1:
                    $campaigns_list[$key]['status_alias'] = "active";
                    $campaigns_active[] = $item;++$amount_list['active'];
                    if($item['days_left']<3) $campaigns_list[$key]['status_alias'] .= "-finishing";
                    break;
                case 2:
                    $campaigns_list[$key]['status_alias'] = "archive";
                    $campaigns_list[$key]['status_title'] = "в архиве";
                    $campaigns_arch[] = $item;++$amount_list['archive'];
                    break;
                case 3:
                    $campaigns_list[$key]['status_alias'] = "moderation";
                    $campaigns_moder[] = $item;++$amount_list['moderation'];
                    break;
            }
        }
        Response::SetString('h1',"Личный кабинет");
        Response::SetString('h2',"BSN.Target");
        Response::SetArray('amount_list',$amount_list);
        Response::SetArray('list_all',$campaigns_list);
        Response::SetArray('list_active',$campaigns_active);
        Response::SetArray('list_arch',$campaigns_arch);
        Response::SetArray('list_moder',$campaigns_moder);
        Response::SetBoolean('campaigns_list_page',true);
        
        $module_template = "cabinet_context.html";
        break;
}
?>