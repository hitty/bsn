<?php
require_once( 'includes/class.photos.php' );

$GLOBALS['css_set'][] = '/modules/partners_landings/style.css';
$GLOBALS['js_set'][] = '/modules/partners_landings/script.js';

$GLOBALS['css_set'][] = '/js/carousel/carousel.css';
$GLOBALS['js_set'][] =  '/js/carousel/carousel.js';

switch(true){
    case !empty( $this_page->real_url ):
        $params = explode( '/', trim( str_replace( 'partners_landings/', '', $this_page->real_url ), '/') );
        if( empty( $params ) ) Host::RedirectLevelUp();
        $id = $params[0];
        
        $item = $db->fetch( " SELECT * FROM " . $sys_tables['partners_landings'] . " WHERE id = ?", $id );
        if( empty( $item ) ) Host::RedirectLevelUp();
        Response::SetArray( 'item', $item );
        
        if( !empty( $params[1] ) ) $query_params = Convert::StringGetToArray(trim( $params[1], '?'));
        switch(true){
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            // отправка заявки
            ////////////////////////////////////////////////////////////////////////////////////////////////////////    
            case !empty( $query_params['action'] ) &&  $query_params['action'] == 'add':
                $parameters = Request::GetParameters( METHOD_POST );
                
                if( !empty( $parameters['phone'] ) ){
                    Response::SetArray( 'parameters', $parameters );
                    
                    $data = array(
                        'name' => $parameters['name'],
                        'phone' => $parameters['phone'],
                        'email' => $parameters['email'],
                        'user_comment' => $parameters['user_comment']
                    );
                    
                    $db->insertFromArray( $sys_tables['partners_landings_applications'], $data );
                    $id = $db->insert_id;
                    Response::SetInteger( 'id', $id );
                    $mailer_title = 'Заявка с сайта BSN.ru - '.date('d.m.Y');
                    Response::SetString( 'mailer_title', $mailer_title );
                    
                    $mailer = new EMailer('mail');
                    // инициализация шаблонизатора
                    $eml_tpl = new Template('send.email.html', 'modules/partners_landings/');
                    $html = $eml_tpl->Processing();
                    $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                    // параметры письма
                    $mailer->Body = $html;
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $mailer_title);
                    $mailer->IsHTML(true);
                    if( !empty( $item['email'] ) || !empty( $item['email_2'] ) ) {
                        if( !empty( $item['email']   ) ) $mailer->AddAddress( $item['email'] );
                        if( !empty( $item['email_2'] ) ) $mailer->AddAddress( $item['email_2'] );
                        $mailer->From = 'no-reply@' . Host::$host;
                        $mailer->FromName = iconv('UTF-8', $mailer->CharSet,  Host::$host );
                        // попытка отправить
                        $mailer->Send();
                    }
                    
                    $mailer = new EMailer('mail');
                    $mailer->Body = $html;
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $mailer_title);
                    $mailer->IsHTML(true);
                    $mailer->AddAddress("kya1982@gmail.com");
                    $mailer->From = 'no-reply@' . Host::$host;
                    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,  Host::$host );
                    // попытка отправить
                    $mailer->Send();

                    $call_value = $parameters['sessionId'];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded;charset=utf-8"));
                     
                    curl_setopt($ch, CURLOPT_URL,"http://api-node3.calltouch.ru/calls-service/RestAPI/19066/requests/orders/register/");
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,
                    "fio=".urlencode($parameters['name'])."&email=".$parameters['email']
                    ."&phoneNumber=".$parameters['phone']."&orderComment=".$parameters['user_comment']
                    ."&subject=".urlencode('Универсальное коммерческое помещение')."".($call_value != 'undefined' ? "&sessionId=".$call_value : ""));
                     
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $calltouch = curl_exec ($ch);
                    curl_close ($ch);
                    
                    $ajax_result['ok'] = true;
                    $module_template = "/templates/popup.success.html";
                }
                break;
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            //попапы
            ////////////////////////////////////////////////////////////////////////////////////////////////////////        
            case !empty( $query_params['action'] ) &&  $query_params['action'] == 'popup':
                $ajax_result['ok'] = true;
                $module_template = 'popup.html';
                break;
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            //карточка лендинга
            ////////////////////////////////////////////////////////////////////////////////////////////////////////        
            default:
                if( !empty( $item['cost'] && !empty( $item['square'] ) ) && empty( $item['cost2meter'] ) ) $item['cost2meter'] = (int) $item['cost'] / $item['square'];
                Response::SetArray( 'item', $item );
                $photos = Photos::getList( 'partners_landings', $id );
                Response::SetArray( 'photos', $photos );
                 
                $module_template = 'item.html';        
                break;
        
        }
        break;
    default:
        Host::RedirectLevelUp();
}