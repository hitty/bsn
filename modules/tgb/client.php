<?php
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
//тестовый ТГБ
// обработка общих action-ов    
require_once('includes/class.tgb.php');
$advp_user = Session::GetBoolean( 'advp_user' );
Response::SetBoolean( 'advp_user', $advp_user );
Tgb::Init();
switch(true){
    case $action =="popup":
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch(true){
            //информация по компании для всплывашки
            case $action == "owner-info":
                $id = Request::GetInteger( "id", METHOD_POST );
                if(empty($id)) $ajax_result = Tgb::getOwnerAgencyInfo(false,false,1287,true);
                else $ajax_result = Tgb::getOwnerAgencyInfo($id,false,false,true);
                if(!empty($ajax_result['agency_photo'])){
                    $ajax_result['logo_url'] = "/" . Config::$values['img_folders']['agencies'] . "/sm/" . $ajax_result['agency_photo_folder']."/".$ajax_result['agency_photo'];
                    unset($ajax_result['agency_photo_folder']);
                    unset($ajax_result['agency_photo']);
                }
                $ajax_result['ok'] = true;
                Tgb::popupStatisitics("",$id);
                break;
            //клик по "перезвоните мне" или "оставить заявку"
            case in_array($action,array("callback-click", "application")):
                $id = Request::GetInteger( "id", METHOD_POST );
                $ajax_result['ok'] = Tgb::popupStatisitics($action,$id);
                break;
        }
        break;
    
    case $action=='last': 
    case $action=='lastnd': 
    case $action=='bzgnr': 
    case $action=='bzgnrlast': 
    case $action=='dizbook': 
    case $action=='dizbooklast': 
        $position = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];  
        Response::SetString('action', $action);
        Response::SetString('position', $position);
        if(empty($position)) $count = 4;
        elseif($action == 'dizbooklast') $count = 2;
        else $count = 3;
        Response::SetInteger( 'count', $count );
        if(date('G')>8 && $action!='last' && $action!='dizbook' && $action!='bzgnr'){
            $list = $db->fetchall("
                SELECT `id` ,  `title` ,  `direct_link` ,  'external' as `link_type`, `photo` ,  `get_pixel` ,  `img_src` ,  `id_campaign`, `priority`, `day_limit`, `cnt`
                FROM (
                    SELECT 
                            ".$sys_tables['tgb_banners'].".`id`,  
                            ".$sys_tables['tgb_banners'].".`title`,  
                            ".$sys_tables['tgb_banners'].".`slogan_1`,  
                            ".$sys_tables['tgb_banners'].".`slogan_2`,  
                            IF(".$sys_tables['tgb_banners'].".utm = 2, ".$sys_tables['tgb_banners'].".direct_link, 
                                CONCAT( 
                                    ".$sys_tables['tgb_banners'].".direct_link,
                                    '?',
                                    CONCAT('utm_source=', ".$sys_tables['tgb_banners'].".utm_source), 
                                    CONCAT('&', 'utm_medium=', ".$sys_tables['tgb_banners'].".utm_medium),
                                    IF(utm_campaign!='', CONCAT('&', 'utm_campaign=', ".$sys_tables['tgb_banners'].".utm_campaign), ''),
                                    IF(utm_content!='', CONCAT('&', 'utm_content=', ".$sys_tables['tgb_banners'].".utm_content), '')
                                )
                           )
                           as `direct_link` , 
                            'external' as `link_type`, 
                            ".$sys_tables['tgb_banners'].".`img_link` as photo,  
                            ".$sys_tables['tgb_banners'].".`get_pixel`, 
                            ".$sys_tables['tgb_banners'].".`img_src`,   
                            ".$sys_tables['tgb_banners'].".`id_campaign`, 
                            ".$sys_tables['tgb_banners'].".`priority`, 
                            ".$sys_tables['tgb_banners_credits'].".day_limit, 
                            COUNT(".$sys_tables['tgb_stats_day_clicks'].".id) as cnt
                    FROM  ".$sys_tables['tgb_banners']." 
                    RIGHT JOIN ".$sys_tables['tgb_banners_credits']." ON ".$sys_tables['tgb_banners_credits'].".id_banner = ".$sys_tables['tgb_banners'].".id 
                    LEFT JOIN ".$sys_tables['tgb_stats_day_clicks']." ON ".$sys_tables['tgb_stats_day_clicks'].".id_parent = ".$sys_tables['tgb_banners'].".id AND `from` = 2
                    WHERE  ".$sys_tables['tgb_banners'].".`published` = 1
                    AND  ".$sys_tables['tgb_banners'].".`enabled` = 1
                    AND  ".$sys_tables['tgb_banners'].".`date_start` <= CURDATE() 
                    AND  ".$sys_tables['tgb_banners'].".`date_end` > CURDATE() 
                    AND  `day_limit` > (SELECT IFNULL(COUNT(".$sys_tables['tgb_stats_day_clicks'].".id),0) as ct FROM ".$sys_tables['tgb_stats_day_clicks']." WHERE `from` > 1 AND ".$sys_tables['tgb_stats_day_clicks'].".id_parent = ".$sys_tables['tgb_banners'].".id)
                    AND  ".$sys_tables['tgb_banners'].".`only_popunder_clicks` > 1
                    
                    GROUP BY ".$sys_tables['tgb_banners'].".`id`
                )  as a 
                    GROUP BY  a.`id` 
                    ORDER BY 
                        " . ( $action=='bzgnrlast' ? "a.id = 539 DESC," : "" ) . "
                        a.cnt / a.day_limit, 
                        RAND()
                    LIMIT ".$count."
            ");    
        } else $list = false;
        if(!empty($list) && count($list)>1){      
            shuffle($list);
            $tgb_list = array_splice($list,0,$count);
            if(count($tgb_list) < $count){ //добавление баннеров с минимальными показателями
                $where = "";
                if(!empty($tgb_list)){
                    foreach($tgb_list as $k=>$item) $ids[] = $item['id'];
                    $where = " AND ".$sys_tables['tgb_banners'].".id NOT IN (".implode(",",$ids).")";
                }
                $list = $db->fetchall("
                    SELECT 
                            ".$sys_tables['tgb_banners'].".`id`,  
                            ".$sys_tables['tgb_banners'].".`title`,  
                            ".$sys_tables['tgb_banners'].".`slogan_1`,  
                            ".$sys_tables['tgb_banners'].".`slogan_2`,  
                            IF(".$sys_tables['tgb_banners'].".utm = 2, ".$sys_tables['tgb_banners'].".direct_link, 
                                CONCAT( 
                                    ".$sys_tables['tgb_banners'].".direct_link,
                                    '?',
                                    CONCAT('utm_source=', ".$sys_tables['tgb_banners'].".utm_source), 
                                    CONCAT('&', 'utm_medium=', ".$sys_tables['tgb_banners'].".utm_medium),
                                    IF(utm_campaign!='', CONCAT('&', 'utm_campaign=', ".$sys_tables['tgb_banners'].".utm_campaign), ''),
                                    IF(utm_content!='', CONCAT('&', 'utm_content=', ".$sys_tables['tgb_banners'].".utm_content), '')
                                )
                           )
                           as `direct_link` ,
 
                            'external' as `link_type`, 
                            ".$sys_tables['tgb_banners'].".`img_link` as photo,  
                            ".$sys_tables['tgb_banners'].".`get_pixel`, 
                            ".$sys_tables['tgb_banners'].".`img_src`,   
                            ".$sys_tables['tgb_banners'].".`id_campaign`, 
                            ".$sys_tables['tgb_banners'].".`priority`, 
                            IFNULL(COUNT(".$sys_tables['tgb_stats_day_clicks'].".id),0) as clickamount
                    FROM  ".$sys_tables['tgb_banners']." 
                    LEFT JOIN ".$sys_tables['tgb_stats_day_clicks']." ON ".$sys_tables['tgb_stats_day_clicks'].".id_parent = ".$sys_tables['tgb_banners'].".id
                    WHERE  ".$sys_tables['tgb_banners'].".`published` =1
                    AND  ".$sys_tables['tgb_banners'].".`enabled` =1
                    AND  ".$sys_tables['tgb_banners'].".`date_start` <= CURDATE() 
                    AND  ".$sys_tables['tgb_banners'].".`date_end` > CURDATE()
                    AND  ".$sys_tables['tgb_banners'].".`only_popunder_clicks` > 1
                    ".$where."
                    GROUP BY ".$sys_tables['tgb_banners'].".id
                    ORDER BY COUNT(".$sys_tables['tgb_stats_day_clicks'].".id), RAND()
                    LIMIT 0, ".(8-count($tgb_list)*2));    
                    shuffle($list);
                    $list = array_splice($list,0,$count-count($tgb_list));
                    foreach($list as $k=>$item) $tgb_list[] = $list[$k];
            }
            shuffle($tgb_list);
            Response::SetArray('list',$tgb_list);
        }
        $this_page->page_template = '/templates/tgb_clear.html';
        Response::SetString('action', $action);
        $module_template = 'block.html';
        $this_page->manageMetadata(array('title'=>'Спепредложения от компании BSN','description'=>'Спепредложения от компании BSN','keywords'=>'Спепредложения от компании BSN'), true);
        break;
    case $action=='left':
    case $action=='right':
    case $action=='top':
    case $action=='bottom':
    case $action=='estate_list':
            //ТГБ по левую сторону
            //все ТГБ справа
            $page_type = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false;
            $commercial =  !empty($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'],'commercial')!='' ? true : false;
            switch(true){
                case $action == 'top' && empty($page_type): 
                case $action == 'bottom' : 
                    $prefix = 'top'; 
                    break;
                case $action == 'estate_list' : 
                    $prefix = 'estate-list'; 
                    break;
                default: 
                    $prefix = 'right'; 
                    
            }  
            $_items_in_session = Session::GetArray('items_' . $prefix);
            if( !empty( $this_page->page_parameters[2] ) && Validate::isDigit( $this_page->page_parameters[2] ) ) {
                $_number_items_left = $this_page->page_parameters[2];
                Response::SetInteger( 'count', $_number_items_left ); 
            }
            else {
                Response::SetInteger( 'count', 4 );
                switch(true){
                    case $action == 'top' : 
                    case $action == 'bottom' : 
                    case $action == 'middle' : 
                        $_number_items_left = 4; 
                        break;
                    case $action == 'estate_list' : 
                        $_number_items_left = 1; 
                        break;
                    default: 
                        $_number_items_left = 2;
                }
            }

            
            
            if($action=='left' || $action == 'top' || ( ($action == 'estate_list') && empty( $this_page->page_parameters[2] ) ) ){ 
                $estate_type = '';  
                //поиск в разделе
                if( $action == 'estate_list' && !empty($this_page->page_parameters[1]) && in_array($this_page->page_parameters[1], array('live', 'build', 'commercial', 'country', 'zhiloy_kompleks','cottages','business_centers','inter') ) ) $estate_type = $this_page->page_parameters[1];
                $preList = Tgb::getClientList( );
                shuffle($preList);
                
                $_counts=0; $_items_left = $_items_in_session = $values = [];
                foreach($preList as $key=>$val){
                    $preList[$key]['photo'] = htmlspecialchars($val['photo']);
                    $preList[$key]['direct_link'] = htmlspecialchars($val['direct_link']);
                    $preList[$key]['get_pixel'] = htmlspecialchars($val['get_pixel']);
                    $_counts++;
                    //список id для сохранения статистики показов
                    if($_counts <= $_number_items_left) $_items_left[]=$val;
                    else $_items_in_session[]=$val;    
                }
                if( $action == 'top' || $action == 'estate_list' ){
                    $_items_left = array_slice($_items_left, 0, $_number_items_left);
                }
                Session::SetArray( 'items_' . $prefix, $_items_in_session );
                $list = $_items_left;
                Response::SetString( 'side', 'left' );
                //метки для А/В тестирования
                $referer = Host::getRefererURL();
                $url = parse_url( $referer )['path'];
                Response::SetBoolean( 'mainpage', $url == '/' );
                Response::SetBoolean( 'mainpage_new', $url == '/new/' );
            } else if( ($action=='right' || $action=='bottom' || $action == 'estate_list') && !empty($_items_in_session)){ 
                if($commercial!==false){
                    Response::SetBoolean('tgb', true);
                }
                $list = $_items_in_session; 
                Response::SetString('side', 'right'); 
                Session::SetArray('items_' . $prefix, '');
            }
            else $list = [];
            Response::SetArray('list', $list);
            // кол-во дополнительных ТГБ
            Response::SetArray( 'partner_count', range( $action == 'bottom' ? 10 : ( in_array( $action, array( 'left', 'top' ) ) && $_number_items_left > count($list) ? $_number_items_left - count($list) - 1 : abs( count($list) - $_number_items_left) - 1 ) , 0 ) );
            if(!empty($list) && count($list)>0){
                Response::SetString('action', $action);
                $ajax_result['ok'] = true;
            }
            $values = [];
            foreach($list as $key=>$val){
                 if(!empty($val['id'])) $values[] = "(".$val['id'].", '".Host::getUserIp()."','".$db->real_escape_string($_SERVER['HTTP_USER_AGENT'])."','".Host::getRefererURL()."')";
            }
            //сохранение статистики показов для не роботов
            if(!Host::$is_bot && !empty($values)) $db->query("INSERT INTO ".$sys_tables['tgb_stats_day_shows']." (id_parent, ip, browser, ref) VALUES ".implode(',',$values));
            
            $module_template = 'list.html';

        break;
    case $action=='click': // запись статистики клика
        if($ajax_mode && !$advp_user ) {
            $id = Request::GetInteger('id',METHOD_POST);
            $from = Request::GetString('from',METHOD_POST);
            $ref = Request::GetString('ref',METHOD_POST);
            $position = Request::GetString('position',METHOD_POST);
            $estate_type = Request::GetString('estate_type',METHOD_POST);
            $ajax_result['ok'] = Tgb::Statistics("click", $id, !empty($estate_type) ? true : false, $from,$position,$ref);
        } else $this_page->http_code=404;
        break;
        //клик с 
        case $action=='adv01': //facebook        
        case $action=='adv01-r':         
        case $action=='adv03': //google adwords        
        case $action=='adv03-r':         
        case $action=='adv04': // yandex direct        
        case $action=='adv04-r':         
            $id = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false;
            if(!empty($id) ){

                if( in_array($action, array( 'adv01', 'adv03', 'adv04' ) ) )  {
                    $index = str_replace('adv', '', $action);
                    $module_template = 'redirect.html';
                    $this_page->page_template = 'modules/tgb/templates/redirect.html';
                    $ref = Host::getRefererURL();
                    $parse_url = parse_url($ref);
                    if(!empty($parse_url['host'])) $ref = $parse_url['host'];
                    else $ref = '';
                    
                    Response::SetArray('item', array('direct_link' => '/tgb/adv' . $index . '-r/'.$id.'/?ref='.$ref));
                }
                else {
                    $item = Tgb::getItem($id);
                    
                    if(!empty($item)){
                        if(strstr($item['direct_link'], 'http:') == '' && strstr($item['direct_link'], 'https:') == '') $item['direct_link'] = 'http://'.trim( $item['direct_link'], '//' );
                        Response::SetArray('item', $item);
                        $params = Request::GetParameters(METHOD_GET);
                        $ref = !empty($params['ref']) ? $params['ref'] : '';
                        $real_ref = Host::getRefererURL();
                        if(empty($real_ref)) $real_ref = '';
                        $ip = Host::getUserIp();
                        if( !Host::$is_bot ) {
                            //2 клика в сутки с 1 IP
                            $click = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['tgb_stats_day_clicks']." WHERE ip = ? AND id_parent = ?", $ip, $id)['cnt'];
                            if( $click > 2 ) Host::Redirect( 'https://www.bsn.ru/' );

                            //пересчет времени кредитного клика для попандера
                            Tgb::setCreditTime();
                            switch($action){
                                case 'adv01-r' : $from = 4; break; // facebook
                                case 'adv03-r' : $from = 6; break; // google adwords
                                case 'adv04-r' : $from = 7; break; // yandex direct
                            }
                            $db->query("INSERT INTO ".$sys_tables['tgb_stats_day_clicks']." SET `id_parent`=?, `from` = ?, real_ref=?, ref=?, ip=?, agent = ?", $id, $from, $ref, $real_ref, $ip, $_SERVER['HTTP_USER_AGENT']); 
                            Host::Redirect(!empty($item['direct_link']) ? trim($item['direct_link']) : 'https://www.bsn.ru/');
                        }
                    }
                }
            }
            break;
        //клик с попандера
        case $action=='adv02':    
        case $action=='adv02-r':     
                $module_template = 'redirect.html';
                $this_page->page_template = 'modules/tgb/templates/redirect.html';

                $item = [];
                $ref = Host::getRefererURL();
                if(!empty($ref)){
                    $item = Tgb::setCreditTime();
                    if(!empty($item)){
                        if(strstr($item['direct_link'], 'http:') == '') $item['direct_link'] = 'http://'.$item['direct_link'];
                    } 
                    Response::SetArray('item', !empty($item) ? $item : array('direct_link' => '/?from=advp'));
                }            
                $from = 5;
                if(!empty($item)) {
                    $res=$db->query("INSERT INTO ".$sys_tables['tgb_stats_day_shows']." SET `id_parent`=?, ref=?, ip=?, browser = ?", $item['id'], $ref, Host::getUserIp(), $_SERVER['HTTP_USER_AGENT']); 
                    
                    $real_ref = Host::getRefererURL();
                    $ip = Host::getUserIp();
                    //1 клик в 10 минут
                    $click = $db->fetch("SELECT * FROM ".$sys_tables['tgb_stats_day_clicks']." WHERE ip = ?", $ip);
                    if(empty($click) && !empty($real_ref)) $res=$db->query("INSERT INTO ".$sys_tables['tgb_stats_day_clicks']." SET `id_parent`=?, `from` = ?, real_ref=?, ip=?, agent = ?", $item['id'], $from, $real_ref, $ip, $_SERVER['HTTP_USER_AGENT']); 
                }
                $res=$db->query("INSERT INTO ".$sys_tables['popunder_clicks']." SET ref=?, real_ref=?, ip=?, browser = ?", $ref, !empty($item) ? implode(" :: ", $item) : $db->last_query, Host::getUserIp(), $_SERVER['HTTP_USER_AGENT']); 
            break;
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}
if(!empty($action)) Response::SetString('action', $action);



?>