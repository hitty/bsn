<?php           
    switch(true){
        ////////////////////////////////////////////////////////////////////////////////////////////////////////
        // проверка ip по аяксу
        ////////////////////////////////////////////////////////////////////////////////////////////////////////
        case !empty($ajax_mode):
            $action = !empty($this_page->page_parameters[0]) ? $this_page->page_parameters[0] : false;
            switch($action){
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            // проверка на наличие JS
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
                case 'js':
                    $db->query("UPDATE ".$sys_tables['ips_list_js']." SET status = 1 WHERE ip = ? ", !empty(Host::$forwarded_user_ip) ? Host::$forwarded_user_ip : Host::$remote_user_ip);
                    break;
            }
            break;
        ////////////////////////////////////////////////////////////////////////////////////////////////////////
        // валидация капчи
        ////////////////////////////////////////////////////////////////////////////////////////////////////////
        default:
            //нахождение пользователя в ЧС       
            $user = Host::checkBlacklist();
            if(empty($user)){
                $refer = Session::GetString('referer');
                Session::SetString('referer', false);
                Host::Redirect(!empty($refer) ? $refer : '/');
                
            }
            $GLOBALS['css_set'][] = '/css/ip.validate.css';
            $GLOBALS['css_set'][] = '/css/controls.css';
            $module_template = 'templates/ip.validate.html';
            //проверка recaptcha
            $post_parameters = Request::GetParameters(METHOD_POST);
            $recaptcha = !empty($post_parameters['g-recaptcha-response']) ? $post_parameters['g-recaptcha-response'] : false;

            if(!empty($recaptcha)) {
                $url = "https://www.google.com/recaptcha/api/siteverify";
                $secret = '6LfjtwcTAAAAAGve0eyMYj6suJJW3fOKnfLEKiQ0';
                $ip = $_SERVER['REMOTE_ADDR'];
                $data = array(
                    'secret' => $secret,
                    'response' => $recaptcha
                );
                $res = curlThis($url, 'POST', $data);
                $res= json_decode($res, true);
                
                if($res['success'])
                {
                    $db->query("DELETE 
                                FROM 
                                    ".$sys_tables['blacklist_ips']." 
                                WHERE 
                                    ( `range` = 1 AND ? LIKE CONCAT(ip, '%') ) OR 
                                    (  `range` = 2 AND ip = ? )", 
                                Host::getUserIp(), Host::getUserIp()
                    );
                    $db->query("DELETE FROM ".$sys_tables['visitors_ips_day']." WHERE ip = ? ", !empty(Host::$forwarded_user_ip) ? Host::$forwarded_user_ip : Host::$remote_user_ip);
                    $db->query("DELETE FROM ".$sys_tables['ips_list_js']." WHERE ip = ? ", !empty(Host::$forwarded_user_ip) ? Host::$forwarded_user_ip : Host::$remote_user_ip);
                    $refer = Session::GetString('referer');
                    Session::SetString('referer', false);
                    Host::Redirect(!empty($refer) ? $refer : '/');
                }
                else $error = 'Введите еще раз капчу';

            } else $error = 'Введите еще раз капчу';
            if(!empty($error)) Response::SetString('error', $error);
            break;
            
    }
    Response::SetBoolean( 'test_mode', true );
?>
