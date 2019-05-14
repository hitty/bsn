<?php
require_once('includes/class.paginator.php');
$advp_user = Session::GetBoolean( 'advp_user' );
Response::SetBoolean( 'advp_user', $advp_user );
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
Response::SetString( 'page', 'banners' );
switch(true){
    /////////////////////////////////////////////////////////
    // Вывод баннеров
    /////////////////////////////////////////////////////////    
    case $action=='right':
    case $action=='top':
    case $action=='bottom':
            $params = explode( Host::$host . '/', Host::getRefererURL() );
            if( !empty( $params[1] ) ) $page_params = explode( "/", $params[1] )[0];

            $item = Banners::getItem( $action, !empty( $page_params ) ? $page_params : false );

            $values = array();
            if(!empty($item['id'])) {
                //внешняя ссылка
                if( !empty( $item['direct_link'] ) ) if( parse_url( $item['direct_link'])['host'] != Host::$host ) $item['link_type'] = 'external';
                Response::SetArray( 'item', $item );
                
                //сохранение статистики показов для не роботов
                if( !Host::$is_bot ) Banners::Statistics( "show", $item['id'] );
                
            }
            $ajax_result['ok'] = true;
            $module_template = 'item.html';

        break;
    /////////////////////////////////////////////////////////
    // Запись клика
    /////////////////////////////////////////////////////////         
    case $action=='click': // 
        if($ajax_mode && !$advp_user ){
            $id = Request::GetInteger('id',METHOD_POST);
            $ref = Request::GetString('ref',METHOD_POST);
            if( !empty( $id ) ) {
                $item = Banners::getItem( false, false, $id );
                $ajax_result['ok'] = Banners::Statistics("click", $id, !empty($estate_type) ? true : false, $item['id_position'], $ref);
            }
        } else $this_page->http_code=404;
        break;
        
    /////////////////////////////////////////////////////////
    // Рекламный клик
    /////////////////////////////////////////////////////////         
    case $action=='adv01': //facebook        
    case $action=='adv01-r':         
    case $action=='adv02': //google adwords        
    case $action=='adv02-r':         
    case $action=='adv03': // yandex direct        
    case $action=='adv03-r':         
        $id = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false;
        if(!empty($id) ){

            if( in_array($action, array( 'adv01', 'adv03', 'adv04' ) ) )  {
                $index = str_replace('adv', '', $action);
                $module_template = 'redirect.html';
                $this_page->page_template = 'modules/html_banners/templates/redirect.html';
                $ref = Host::getRefererURL();
                $parse_url = parse_url($ref);
                if(!empty($parse_url['host'])) $ref = $parse_url['host'];
                else $ref = '';
                
                Response::SetArray('item', array('direct_link' => '/ab/adv' . $index . '-r/'.$id.'/?ref='.$ref));
            }
            else {
                $item = Banners::getItem( false, false, $id );
                
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
                        $click = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['banners_stats_click_day']." WHERE ip = ? AND id_parent = ?", $ip, $id)['cnt'];
                        if( $click > 2 ) Host::Redirect( 'https://www.bsn.ru/' );

                        switch($action){
                            case 'adv01-r' : $from = 2; break; // facebook
                            case 'adv03-r' : $from = 3; break; // google adwords
                            case 'adv04-r' : $from = 4; break; // yandex direct
                        }
                        $db->query("INSERT INTO ".$sys_tables['banners_stats_click_day']." SET `id_parent`=?, `from` = ?, real_ref=?, ref=?, ip=?, browser = ?", $id, $from, $ref, $real_ref, $ip, $_SERVER['HTTP_USER_AGENT']); 
                        Host::Redirect(!empty($item['direct_link']) ? trim($item['direct_link']) : 'https://www.bsn.ru/');
                    }
                }
            }
        }
        break;
                    
    /////////////////////////////////////////////////////////
    // ЛК
    /////////////////////////////////////////////////////////                 
    case !empty( $this_page->module_parameters ) && !empty( $this_page->module_parameters['page'] ) && $this_page->module_parameters['page'] == 'members'    :
        switch(true){
        /////////////////////////////////////////////////////////
        // Статистика
        /////////////////////////////////////////////////////////                 
            case !empty( $action ) && $action == 'stats':
                $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
                $id = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                switch( true ){
                    /////////////////////////////////////////////////////////
                    // Статистика за период
                    /////////////////////////////////////////////////////////                 
                    case $action == 'period':
                        $date_start = Request::GetParameter( 'date_start', METHOD_POST );
                        $date_end = Request::GetParameter( 'date_end', METHOD_POST );
                        if( !empty( $date_start ) && !empty( $date_end ) ){
                            $stats = Banners::getTotalStats( $id, $date_start, $date_end );
                            Response::SetArray( 'stats', $stats );
                            $ajax_result['ok'] = true;
                            $ajax_result['popup_redirect'] = false;
                            $module_template = 'stats.popup.list.html';                            
                        }
                        break;
                    /////////////////////////////////////////////////////////
                    // Попап
                    /////////////////////////////////////////////////////////                 
                    case $action == 'popup':
                        
                        if( !empty( $id ) ) {
                            $item = Banners::getItem( false, false, $id );
                            $stats = Banners::getItemStats( $id );
                            $item = array_merge( $item, $stats );
                            Response::SetArray( 'item', $item );
                            $ajax_result['ok'] = true;
                            $module_template = 'stats.popup.html';
                        }
                        break;
                }
                break;
        /////////////////////////////////////////////////////////
        // Список
        /////////////////////////////////////////////////////////                 
            default:            
                $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
                $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
                $GLOBALS['css_set'][] = '/modules/html_banners/style.css';
                $GLOBALS['js_set'][] = '/modules/html_banners/stats.js';
                // формирование списка для фильтра
                $conditions = array(
                    $sys_tables['banners'] . '.published = 1 ',
                    $sys_tables['banners'] . '.enabled = 1 ',
                    $sys_tables['banners'] . '.date_end > CURDATE() '
                );
                if( !Common::bsnMember() ) $conditions[] = $sys_tables['banners'] . '.id_user = ' . $auth->id;
                $condition = implode(" AND ",$conditions);    
                
                // страница списка
                $count = Request::GetInteger('count', METHOD_GET);            
                if(empty($count)) $count = Cookie::GetInteger('View_count_cabinet');
                if(empty($count)) {
                    $count = Config::$values['view_settings']['strings_per_page'];
                    Cookie::SetCookie('View_count_cabinet', Convert::ToString($count), 60*60*24*30, '/');
                }  
                
                $page = Request::GetInteger('page', METHOD_GET);
                if(empty($page)) $page = 1;
                $paginator = new Paginator($sys_tables['banners'], $count, $condition);
                if($paginator->pages_count>1){
                    $paginator->link_prefix = '/members/conversions/banners/?page=';
                    Response::SetArray('paginator', $paginator->Get($page));
                }                

                
                $list = Banners::getList( $count, $count*($page-1) , $condition, false, false, $sys_tables['banners'].".id ");
                foreach($list as $k =>$item){
                    $stats = Banners::getItemStats($item['id']);
                    $list[$k] = array_merge($item, $stats);
                }
                Response::SetArray( 'list', $list );
                $module_template = 'list.html';
                
                
                break;
            }
            break;
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}
if(!empty($action)) Response::SetString('action', $action);



?>