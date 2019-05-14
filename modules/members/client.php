<?php
require_once('includes/class.sms.php');
require_once('includes/class.email.php');
require_once('includes/class.paginator.php');
require_once('includes/class.moderation.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.robot.php');
require_once('includes/class.messages.php');
require_once('includes/class.log.php');
if( !class_exists( 'EstateStat' ) ) require_once('includes/class.estate.statistics.php');
$GLOBALS['js_set'][] = '/modules/members/cabinet.js';
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php'); 

$strings_per_page = 10;
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$subaction = '';
 //типы недвижимости
$estate_types = array('live'=>'Жилая','build'=>'Новостройки','commercial'=>'Коммерческая','country'=>'Загородная'); 

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
if(!empty($this_page->page_alias) && $this_page->page_alias != $action){
    $alias_parts = explode('/',$this_page->page_alias);
    if(count($alias_parts) > 1){
        $alias_parts = array_reverse($alias_parts);
        foreach($alias_parts as $part) array_unshift($this_page->page_parameters, $part);
        $action = $this_page->page_parameters[0];
    }elseif(count($alias_parts) == 1 && !empty($alias_parts[0]) && $alias_parts[0] == 'estate_prolongate') $action = $alias_parts[0];
}
//по возможности читаем страницу списка
$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;

Response::SetString('page',$action);
$ajax_action = Request::GetString('action', METHOD_POST); //оставил пока старый вызов, нехватка времени в переделках
if(!empty($ajax_action)) $action = $ajax_action;

$GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
$GLOBALS['js_set'][] = '/js/form.validate.js';
$GLOBALS['css_set'][] = '/css/style-cabinet.css';

    //определяем количество объектов всего для пользователя
if(!$ajax_mode) $objects_stats = EstateStat::getCount(empty($auth->id_agency) || $auth->agency_admin == 2 ? $auth->id:false, !empty($auth->id_agency)?$auth->id_agency:false, false, false);
    
require_once('includes/class.member.php');

$GLOBALS['css_set'][] = '/modules/members/style.css'; 
$GLOBALS['css_set'][]='/modules/members/pay.css';
Response::SetBoolean('show_topline', false);
if(!empty($auth->id_agency)){
    $agency_limit = EstateStat::getAgenciesCount($auth->id);
    Response::SetArray( 'agency_limit', $agency_limit );
}
//не показывать верхний баннер
Response::SetString('img_folder', Config::$values['img_folders']['live']);
if(!$ajax_mode){
    //определяем количество объектов всего для пользователя
    $objects_stats = EstateStat::getCount(empty($auth->id_agency) || $auth->agency_admin == 2 ? $auth->id:false, !empty($auth->id_agency)?$auth->id_agency:false, false, false);
    $objects_stats['published_total'] = !empty($objects_stats['published'])?array_sum($objects_stats['published']):0;
    $objects_stats['payed_total'] = !empty($objects_stats['payed'])?$objects_stats['payed']:0;
    $objects_stats['moderation_total'] = !empty($objects_stats['moderation'])?array_sum($objects_stats['moderation']):0;
    $objects_stats['archive_total'] = !empty($objects_stats['archive'])?array_sum($objects_stats['archive']):0;
    $objects_stats['draft_total'] = !empty($objects_stats['draft'])?array_sum($objects_stats['draft']):0;
    //кол-во максимально возможных объектов 
    if($auth->id_agency>0 && $auth->agency_admin == 1) {
        $objects_limit = EstateStat::getCountPacketAgencies($auth->id_agency); //для админа агентства
        //для агентств ограничение на кол-во опубликованных
        if($auth->agency_id_tarif > 0){
            Response::SetBoolean( 'free_promo', $auth->agency_promo > $agency_limit['promo'] );
            Response::SetBoolean( 'free_premium', $auth->agency_premium > $agency_limit['premium'] );
            Response::SetBoolean( 'free_vip', $auth->agency_vip > $agency_limit['vip'] );
        }
    } else $objects_limit = 1;  //для пользователя
    $total_published = $objects_stats['published_total']+$objects_stats['moderation_total']-$objects_stats['payed_total'];
    //для специалистов ограничение на кол-во опубликованных
    if($auth->id_tarif > 0){
        $tarif = $db->fetch("SELECT * FROM ".$sys_tables['tarifs']." WHERE id = ?",$auth->id_tarif);
        $objects_limit = $tarif['active_objects'];
        $total_published = $objects_stats['published_total'];
        Response::SetBoolean('free_promo',!empty($auth->promo_left));
        Response::SetBoolean('free_premium',!empty($auth->premium_left));
        Response::SetBoolean('free_vip',!empty($auth->vip_left));
    }

    $objects_stats['added_total'] = $objects_limit - $total_published;
    
    Response::SetInteger( 'objects_limit', $objects_limit );
    Response::SetInteger( 'user_total', $objects_stats['published_total'] + $objects_stats['moderation_total'] );
    //меню слева
    if( !empty( $objects_stats ) ) Response::SetArray( 'count_list', $objects_stats );
}

$object_statuses = array( 'published'=>1, 'archive'=>2, 'moderation'=>3, 'draft'=>4 );

$object_cost_statuses = $db->fetchall( "SELECT * FROM ".$sys_tables['objects_statuses'],'id' );

// обработка общих action-ов
switch(true){
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // авторизация
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $this_page->page_alias == 'authorization'
        || $this_page->requested_url == 'authorization'
        || $action == 'authorization':
            if(!empty($this_page->requested_url) && $this_page->requested_url == 'members/authorization') Host::Redirect('/authorization/');
            $h1 = empty($this_page->page_seo_h1) ? 'Войти на сайт' : $this_page->page_seo_h1;
            Response::SetString('h1', $h1);

            $parameters = Request::GetParameters(METHOD_GET);
            switch(true){
               ////////////////////////////////////////////////////////////////////////////////////////////////
               // форма авторизации
               ////////////////////////////////////////////////////////////////////////////////////////////////
               case $action == 'popup':
                    $ajax_result['ok'] = true;
                    $module_template = 'authorization.popup.html';
                break;
               ////////////////////////////////////////////////////////////////////////////////////////////////
               // авторизация  через vk.com
               ////////////////////////////////////////////////////////////////////////////////////////////////
               case $action == 'vklogin':
                    if(!empty($parameters['code'])){
                        //успешная авторизация через вконтакте
                        $access = file_get_contents('https://oauth.vk.com/access_token?'.http_build_query(array(
                            'client_id'     => Config::Get('social/vk/app_id'),
                            'client_secret' => Config::Get('social/vk/secret'),
                            'code'          => $parameters['code'],
                            'redirect_uri' => Host::$root_url.'/authorization/vklogin/'.(!empty($parameters['r'])?'?r='.$parameters['r']:'')
                        )));     
                        $access = json_decode($access);
                        $id_user_vk = Convert::ToInt($access->user_id);
                        if(!empty($id_user_vk)){
                            $social_data = array('field'=>'id_user_vk','value'=>$id_user_vk);
                            $auth->checkAuthSocial($social_data);
                            //если нет такого аккаунта - выводим сообщение о том что надо прикрепить акк или создать новый
                            if(empty($auth->id_user_vk)) {
                                Response::SetBoolean('attach_account',true);
                                Session::SetString('account_attach',$social_data);    
                            }
                        }
                        Host::Redirect('/authorization/vklogin/'.(!empty($parameters['r'])?'?r='.$parameters['r']:''));
                    } else { //перешли после редирека - показать привязку к аккаунту
                        Response::SetBoolean('attach_account',true);    
                    }
                    break;
               ////////////////////////////////////////////////////////////////////////////////////////////////
               // авторизация  через facebook.com
               ////////////////////////////////////////////////////////////////////////////////////////////////
                case $action == 'fblogin':
                    if(!empty($parameters['code'])){
                        //успешная авторизация через ФБ
                        $url = 'https://graph.facebook.com/oauth/access_token?client_id='.Config::Get('social/fb/app_id').'&'.http_build_query(array(
                            'client_secret' => Config::Get('social/fb/secret'),
                            'code'          => $parameters['code'],
                            'redirect_uri' => Host::$root_url.'/authorization/fblogin/'
                        ));
                        $response = @file_get_contents($url);   
                        parse_str($response, $result); 
                        
                        $access = @file_get_contents('https://graph.facebook.com/me?'.http_build_query(array(
                            'fields' => 'id,name',
                            'access_token' => $result['access_token']
                        )));
                        $access = json_decode($access);
                        $id_user_facebook = Convert::ToInt($access->id);
                        if(!empty($id_user_facebook)){
                            $social_data = array('field'=>'id_user_facebook','value'=>$id_user_facebook);
                            $auth->checkAuthSocial($social_data);
                            //если нет такого аккаунта - выводим сообщение о том что надо прикрепить акк или создать новый
                            if(empty($auth->id_user_facebook)) {
                                Response::SetBoolean('attach_account',true);
                                Session::SetString('account_attach',$social_data);    
                            }
                        }
                        Host::Redirect('/authorization/fblogin/');
                    } else { //перешли после редирека - показать привязку к аккаунту
                        Response::SetBoolean('attach_account',true);    
                    }
                    break;
               ////////////////////////////////////////////////////////////////////////////////////////////////
               // авторизация  через odnoklassniki.ru
               ////////////////////////////////////////////////////////////////////////////////////////////////
                case $action == 'oklogin':
                    if(!empty($parameters['code'])){
                        //успешная авторизация через ОК
                        $params = array(
                            'client_id' =>  Config::Get('social/ok/app_id'),
                            'client_secret' => Config::Get('social/ok/secret'),
                            'code'          => $parameters['code'],
                            'grant_type' => 'authorization_code',
                            'redirect_uri' => Host::$root_url.'/authorization/oklogin/'
                        );
                        $url = 'http://api.odnoklassniki.ru/oauth/token.do';
                        $result = curlThis($url, 'POST', $params);
                        $tokenInfo = json_decode($result, true);
                        if($tokenInfo){
                            //Получение информации о пользователе
                            if (!empty($tokenInfo['access_token'])) {
                                $curl = curl_init('http://api.odnoklassniki.ru/fb.do?access_token=' . $tokenInfo['access_token'] . '&application_key=' . Config::Get('social/ok/public') . '&method=users.getCurrentUser&sig=' . md5('application_key=' . Config::Get('social/ok/public') . 'method=users.getCurrentUser' . md5($tokenInfo['access_token'] . Config::Get('social/ok/secret'))));
                                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                                $s = curl_exec($curl);
                                curl_close($curl);
                                $userInfo = json_decode($s, true);

                                $id_user_ok = Convert::ToInt($userInfo['uid']);
                                if(!empty($id_user_ok)){
                                    $social_data = array('field'=>'id_user_ok','value'=>$id_user_ok);
                                    $auth->checkAuthSocial($social_data);
                                    //если нет такого аккаунта - выводим сообщение о том что надо прикрепить акк или создать новый
                                    if(empty($auth->id_user_ok)) {
                                        Response::SetBoolean('attach_account',true);
                                        Session::SetString('account_attach',$social_data);    
                                    }
                                }
                                Host::Redirect('/authorization/oklogin/');
                            } 
                        } 
                    }else { //перешли после редирека - показать привязку к аккаунту
                        Response::SetBoolean('attach_account',true);    
                    }
                    break;
            }                 
                
                         
            if($ajax_mode){ 
                $ajax_result['ok'] = !empty($ajax_result['ok']) || ( $auth->auth_trying && $auth->authorized ) ;
                $ajax_result['auth_trying'] = $auth->auth_trying;
                
                if( !empty($ajax_result['ok']) ) {
                    $ajax_result['success'] = 'Ок. Осуществляется вход!';
                    $ajax_result['popup_redirect'] = true;
                }
                else $ajax_result['error'] = 'Пара логин-пароль неверная!'; 
            } else {
                if($this_page->real_url == 'authorization'){
                    Response::SetBoolean('with_header',true);
                    $GLOBALS['js_set'][] = '/modules/members/account_forms.js';
                    //футер
                    Response::SetBoolean('small_footer',true);
                }
                //прикрепление аккаунта без социальных кнопок
                if($action == 'attach_account') Response::SetBoolean('not_show_social_authorization',true);
                if($auth->authorized) {
                    //если есть аккаунт для прикрепления - обновляем данные
                    $account_attach = unserialize(Session::GetString('account_attach'));
                    if(!empty($account_attach)) {
                        if(!empty($account_attach['field']) && !empty($account_attach['value'])) $db->query("UPDATE ".$sys_tables['users']." SET ".$account_attach['field']."=? WHERE id=?",$account_attach['value'],$auth->id);
                        Session::SetString('account_attach','');
                    }
                    //если авторизовались с помощью соцсети - закрываем окно
                    $auth_data = Session::GetArray('auth_data');
                    
                    if( (!empty($auth_data) && !empty($auth_data['social_field']) && !empty($auth_data['social_value'])) || !empty($account_attach) ){
                        Response::SetBoolean('close_window',true);
                    } elseif($auth->user_activity == 2) Host::Redirect('/members/conversions/consults/');
                    else Host::Redirect('/members/objects/list/');
                }
                if($auth->auth_trying) {
                    $auth_login = Request::GetString('auth_login',METHOD_POST);
                    $errors = array('auth_login'=>"",'auth_password'=>"");
                    if(empty($auth_login) || !Validate::isEmail($auth_login)) $errors['auth_login'] = "Некорректный email";
                    else $ajax_result['error'] = 'Пара логин-пароль неверная!';
                }
                
                Response::SetString('class_for_clear_template',"account authorization");
                Response::SetBoolean('social_only_buttons',true);
                
                $module_template = 'authorization.html';                
            }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // регистрация нового пользователя
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $this_page->requested_url == 'registration' || $this_page->requested_url == 'registration/attach_account' || $this_page->requested_url == 'registration/popup': 
        if(!empty($this_page->requested_url) && $this_page->requested_url == 'members/registration') Host::Redirect('/registration/');
        
        $post_parameters = Request::GetParameters(METHOD_POST);
        $errors = [];
        $reg_email = $reg_name = '';
        switch(true){
           ////////////////////////////////////////////////////////////////////////////////////////////////
           // форма регистрации
           ////////////////////////////////////////////////////////////////////////////////////////////////
           case $action == 'popup':
                $ajax_result['ok'] = true;
                $module_template = 'registration.popup.html';
            break;
            default:
            
            if($ajax_mode){ 
                //ajax-регистрация
                $post_parameters['submit'] = true;
                $ajax_result['ok'] = true;
            } else {
                Response::SetBoolean('with_header',true);
                $GLOBALS['js_set'][] = '/modules/members/account_forms.js';
                $module_template = 'registration.html';

                $h1 = empty($this_page->page_seo_h1) ? 'Регистрация нового пользователя ' : $this_page->page_seo_h1;
                Response::SetString('h1', $h1);
                
                if($action == 'attach_account') Response::SetBoolean('not_show_social_authorization',true);
            }
            // если была отправка формы
            if(!empty($post_parameters['submit'])){
                
                //чтобы данные в форме не пропали
                Response::SetArray('form_data',array('email'=>$post_parameters['login_email'],'name'=>$post_parameters['login_name']));
                
                //если email непуст, проверяем его корректность
                if(!empty($post_parameters['login_email'])){
                    if(Validate::isEmail($post_parameters['login_email'])) $reg_email = $post_parameters['login_email'];
                    else{
                        $reg_email = $post_parameters['login_email'];
                        $errors['login_email'] = $ajax_result['error'] = 'Некорректный email';
                    } 
                }else $errors['login_email'] = $ajax_result['error'] = 'Некорректный email';
                // получение имени
                if(!empty($post_parameters['login_name'])) $reg_name = $post_parameters['login_name'];
                else $errors['login_name'] = $ajax_result['error'] = "Пожалуйста, введите имя";
                
                if(empty($errors)){
                    // проверка на существование такого пользователя
                    if(!empty($reg_email)){
                        $where = "email='".$db->real_escape_string($reg_email)."' OR login='".$db->real_escape_string($reg_email)."'";
                        $row = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE ".$where);
                        if(!empty($row)) $errors['login_email'] = $ajax_result['error'] = 'Пользователь с такими данными уже существует';
                    }
                }
                
                // если все проверки пройдены - отсылаем сообщение пользователю с логином и паролем
                if(!empty($errors)){
                    $ajax_result['ok'] = false;
                    $ajax_result['errors'] = $errors;
                    Response::SetArray('errors',$errors);
                } 
                else {                
                    $ajax_result['ok'] = true;
                    if($ajax_mode){
                        // генерируем пароль
                        $reg_passwd = substr(md5(time()),-6);
                        //проверка на домен в черном списке
                        if(!empty($reg_email)){
                            $domain_parts = explode('@', $reg_email);
                            $domain = $domain_parts[1];
                        } else $domain = false;
                        if(!empty($domain) && !Validate::emailBlackList($domain)){
                        // создание нового пользователя в БД
                        $res = $db->query("INSERT INTO ".$sys_tables['users']."
                                            (email,name,passwd,datetime,access)
                                           VALUES
                                            (?,?,?,NOW(),'')"
                                           , $reg_email
                                           , $reg_name
                                           , sha1(sha1($reg_passwd)));
                                           
                        } else $res = true; //псеводзапись email в черном списке
                        if(empty($res)){
                            $errors['error'] = true;
                            $ajax_result['ok'] = false;
                        } else {
                            //если есть аккаунт для прикрепления - обновляем данные
                            $account_attach = unserialize(Session::GetString('account_attach'));
                            if(!empty($account_attach)) {
                                //проверка на существование такой учетной записи
                                $item = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE ".$account_attach['field']."=?",$account_attach['value']);
                                if(!empty($item)) $db->query("UPDATE ".$sys_tables['users']." SET ".$account_attach['field']."=? WHERE id=?",$account_attach['value'],$db->insert_id);
                                Session::SetString('account_attach','');
                            }

                            if(!empty($reg_email) && Validate::isEmail($reg_email)) {
                                // отправка кода на мыло
                                $mailer = new EMailer('mail');
                                // данные пользователя для шаблона
                                Response::SetArray( "data", array('email'=>$reg_email, 'name'=>$reg_name, 'password'=>$reg_passwd) );
                                // данные окружения для шаблона
                                $env = array(
                                    'url' => Host::GetWebPath('/'),
                                    'host' => Host::$host,
                                    'ip' => Host::getUserIp(),
                                    'datetime' => date('d.m.Y H:i:s')
                                );
                                Response::SetArray('env', $env);
                                // инициализация шаблонизатора
                                $eml_tpl = new Template('registration_email.html', $this_page->module_path);
                                // формирование html-кода письма по шаблону
                                $html = $eml_tpl->Processing();         
                                if( !class_exists('Sendpulse') ) require("includes/class.sendpulse.php");
                                //отправка письма
                                $sendpulse = new Sendpulse( 'subscriberes' );
                                $result = $sendpulse->sendMail( 'Регистрация на сайте ' . Host::$host, $html, $reg_name, $reg_email );
                                //добавление подписчика
                                $email = array(
                                    array(
                                        'email' => $reg_email,
                                        'variables' => array(
                                            'name' => $reg_name,
                                        )
                                    )
                                );
                                $sendpulse->addEmails( false, $email );
                                
                                /*
                                // перевод письма в кодировку мейлера
                                $html = iconv('UTF-8', $mailer->CharSet, $html);
                                // параметры письма
                                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Регистрация на сайте ' . Host::$host);
                                $mailer->Body = $html;
                                $mailer->AltBody = strip_tags($html);
                                $mailer->IsHTML(true);
                                $mailer->AddAddress($reg_email, iconv('UTF-8',$mailer->CharSet, $reg_name));
                                $mailer->From = 'no-reply@bsn.ru';
                                $mailer->FromName = 'bsn.ru';
                                if($mailer->Send()) 
                                */
                                if( !empty( $result ) ) Response::SetString('success','email');

                                // инициализация шаблонизатора
                                $eml_tpl = new Template('registration_email.manager.html', $this_page->module_path);
                                // формирование html-кода письма по шаблону
                                $html = $eml_tpl->Processing();         
                                $result = $sendpulse->sendMail( 'Регистрация на сайте ' . Host::$host, $html, 'Менеджеру БСН', 'scald@bsn.ru' );
                                
                                Session::SetArray('fields',array('email'=>$reg_email,'name'=>$reg_name));
                                Session::SetArray('fields',array('email'=>$reg_email,'name'=>$reg_name));
                                if($ajax_mode){
                                    $_authorized = $auth->checkAuth($reg_email, $reg_passwd);
                                    $ajax_result['ok'] = $auth->auth_trying && $auth->authorized;
                                    $ajax_result['auth_trying'] = $auth->auth_trying;
                                    $ajax_result['popup_redirect'] = true;
                                    $ajax_result['lq'] = '';
                                    
                                } else {
                                    
                                    if(!empty($account_attach)) Response::SetBoolean('close_window',true);
                                    else Host::Redirect('/registration/success/');
                                }

                            }
                            
                        }
                    } else $errors['bot']='Поробуйте ввести свои данные еще раз';
                }
            }
            
            
            
            Response::SetBoolean('social_only_buttons',true);
            Response::SetBoolean('social_reverse_buttons',true);
          
            Response::SetArray('errors',$errors);
            Response::SetArray('fields',$post_parameters);    
            break;
        }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // редирект на успешную регистрацию нового пользователя
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $this_page->requested_url=='registration/success': 
        $fields = Session::GetArray('fields');
        if(empty($fields) || count($fields)!=2){
            $this_page->http_code = 404;
            break;
        }
        $module_template = 'registration.success.html';
        Response::SetArray('fields',$fields);
        break;
        
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // форма восстановление утраченного пароля
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $this_page->requested_url=='lostpassword/popup':
        $ajax_result['ok'] = true;
        $module_template = 'lostpassword.popup.html';
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // восстановление - отправка пароля
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $this_page->requested_url=='lostpassword/sent':
        $post_parameters = Request::GetParameters(METHOD_POST);
        
        if(Validate::isEmail($post_parameters['login_email'])){
            $reg_email = $post_parameters['login_email'];
            // проверка на существование такого пользователя
            if(!empty($reg_email)) $where = "email='".$db->real_escape_string($reg_email)."'";
            $user_row = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE ".$where);
            if(empty($user_row)) $ajax_result['error'] = 'Пользователя с такими данными не существует';
        }
        else $ajax_result['error'] = 'Некорректный email';
        
        if(empty($ajax_result['error'])) {
            // генерация нового кода подтверждения
            $confirm_code = substr(md5(time()),-6);
            // создание нового запроса
            $res = $db->query("INSERT IGNORE INTO ".$sys_tables['users_restore']."
                                    SET id_users = ?, users_email = ?, confirm_code = ?
                                    ON DUPLICATE KEY UPDATE id_users = ?, confirm_code = ?"     
                               , $user_row['id']
                               , (empty($reg_email) ? '' : $user_row['email'])
                               , $confirm_code
                               , $user_row['id']
                               , $confirm_code
            );
            
            if(empty($res)){
                // если не нашли запись и не создали новую (ошибка доступа к БД например)
                $ajax_result['error'] = 'Технические неполадки';
            } else {
                // если всё успешно (найден или создан код подтверждения) - пытаемся отправить код на почту или на мобилу
                if(!empty($reg_email) && Validate::isEmail($reg_email)) {
                    // отправка кода на мыло
                    $mailer = new EMailer('mail');
                    // данные пользователя для шаблона
                    Response::SetArray( "data", array('email'=>$reg_email, 'name'=>$user_row['name'], 'code'=>$confirm_code) );
                    // данные окружения для шаблона
                    $env = array(
                        'url' => Host::GetWebPath('/'),
                        'host' => Host::$host,
                        'ip' => Host::getUserIp(),
                        'datetime' => date('d.m.Y H:i:s')
                    );
                    Response::SetArray('env', $env);
                    // инициализация шаблонизатора
                    $eml_tpl = new Template('lostpassword_email.html', $this_page->module_path);
                    // формирование html-кода письма по шаблону
                    $html = $eml_tpl->Processing();
                    // перевод письма в кодировку мейлера
                    $html = iconv('UTF-8', $mailer->CharSet, $html);
                    // параметры письма
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Восстановление пароля на сайте '.Host::$host);
                    $mailer->Body = $html;
                    $mailer->AltBody = strip_tags($html);
                    $mailer->IsHTML(true);
                    $mailer->AddAddress($reg_email, iconv('UTF-8',$mailer->CharSet, $user_row['name']));
                    $mailer->From = 'no-reply@bsn.ru';
                    $mailer->FromName = 'bsn.ru';
                    // попытка отправить
                    if($mailer->Send()) Response::SetString('success','email');
                }
            }
            if( empty( $ajax_result['error'] )){
                $ajax_result['ok'] = true;
                $module_template = '/templates/popup.success.html';
                Response::SetString('text', 'На указанный вами email отправлено письмо с дальнейшими инструкциями');
                Response::SetString('title', 'Спасибо за обращение.');
            }
        }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // восстановление утраченного пароля
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $this_page->requested_url=='lostpassword' || $this_page->requested_path=='lostpassword':

        $module_template = 'lostpassword.html';

        //проверка на существование записи восстановления
        $get_parameters = Request::GetParameters(METHOD_GET);
        Response::SetArray( 'get_parameters', $get_parameters );
        if( !empty( $auth->id ) || ( empty( $get_parameters['email'] ) || empty( $get_parameters['code'] ) ) )  Host::Redirect('/');
        $user = $db->fetch("SELECT * FROM ".$sys_tables['users_restore']." WHERE users_email = ? AND confirm_code = ?",
           $get_parameters['email'] , $get_parameters['code']
        );
        if(empty($user)) Response::SetBoolean('wrong_email_code', true);
        else {
            $this_page->metadata['title'] = 'Восстановление пароля';
            // скрипт управления формой
            // получение переданных из формы данных
            $post_parameters = Request::GetParameters(METHOD_POST);
            $errors = [];
            $reg_email = '';
            // если была отправка формы (запрос кода или подтверждение кода с новым паролем)
            if( !empty($post_parameters['submit']) ){

                // проверка новых паролей
                if(isset($post_parameters['newpass1'])){
                    if(strlen($post_parameters['newpass1'])<3) $errors['newpass1'] = 'Пароль слишком короткий';
                    if(strlen($post_parameters['newpass1'])>64) $errors['newpass1'] = 'Пароль слишком длинный';
                    if($post_parameters['newpass1']!=$post_parameters['newpass2']){
                        $errors['newpass1'] = ' ';
                        $errors['newpass2'] = 'Пароли не совпадают';
                    }
                }
                
                // получение ранее отправленного запроса
                if(!empty($user))
                    $restore_row = $db->fetch("SELECT * FROM ".$sys_tables['users_restore']." WHERE id_users=?", $user['id_users']);
                else $restore_row = [];

                
                // если ошибок нет и такой пользователь найден
                if(empty($errors)){
                    
                    Response::SetString('success','step2');
                    // обработка формы подтверждения смены пароля
                    $result = $db->query("UPDATE ".$sys_tables['users']."
                                          SET passwd=?
                                          WHERE id=?"
                                          , sha1(sha1($post_parameters['newpass1']))
                                          , $user['id_users']);
                    if($result){
                        $res = $db->query("DELETE FROM ".$sys_tables['users_restore']." WHERE id=?", $restore_row['id']);
                        Response::SetBoolean('completed',true);
                        $res = $auth->AuthCheck($get_parameters['email'], $post_parameters['newpass1']);
                        if($res) Host::Redirect('/');
                    } else $errors['error'] = 'Технические неполадки';
                }
            }
            //чтобы был футер
            Response::SetBoolean('small_footer',true);
            
            Response::SetArray('errors',$errors);
            Response::SetArray('fields',$post_parameters);
            //чтобы не уходить со второй формы, если ошибка
            if ((!empty($post_parameters['submit']))&&($post_parameters['submit']=='commit')){
                if ($errors){
                    Response::SetString('success','step2_error');
                }
                else{
                    Response::SetString('success','step2');
                }
            }
        }
        break; 
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // разлогинивание
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $this_page->requested_url=='logout':
        $auth->logout();
        Host::Redirect('/authorization/');
        break;
  
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // список объектов и статистика объектов
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $action=='objects':
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        
        if(empty($action)) Host::Redirect('/members/objects/list/');
        
        if( $action == 'objects_subscriptions') {
            require_once('modules/objects_subscriptions/client.php'); 
           break;
        } else if( $action == 'favorites') {
            require_once('modules/favorites/client.php'); 
            break;
        }
        
        //типы сделок
        $deal_types = array('rent'=>'Аренда','sell'=>'Продажа'); 
        switch(true){
           ////////////////////////////////////////////////////////////////////////////////////////////////
           //продление объекта на 30-60 дней
           ////////////////////////////////////////////////////////////////////////////////////////////////
           case $action == 'extension':
                if($ajax_mode) {
                    $estate =  $this_page->page_parameters[2]; 
                    $id =  Convert::ToInt($this_page->page_parameters[3]);
                    if(array_key_exists($estate, $estate_types) && Validate::isDigit($id)){
                        //продлять только status = 2;
                        $item = $db->fetch("SELECT * FROM ".$sys_tables[$estate]." WHERE id=? AND id_user=? AND status=2",$id,$auth->id); 
                        if(!empty($item)) {
                            //для "тарифных" не может быть бесплатных объектов
                            if(!empty($auth->id_tarif) && $item['status'] == 2) {
                                $status = 5;
                                $date_now = new DateTime("+".$object_cost_statuses[$status]['days_last']." day");
                                $status_date_end = $date_now->format("Y-m-d H:i:s");
                            } else {
                                $status = 2;
                                $date_now = new DateTime("+".$object_cost_statuses[$status]['days_last']." day");
                                $status_date_end = "0000-00-00 00:00:00";
                            }
                            
                            $res = $db->query("UPDATE ".$sys_tables[$estate]." SET date_change= NOW(), status = ?, status_date_end = ? WHERE id=? AND id_user=?",$status, $status_date_end, $id, $auth->id); 
                            if($res) {
                                $item = $db->fetch("SELECT ".($status!=2?"
                                                           DATE_FORMAT(status_date_end,'%d %M') as `date_end`,
                                                           DATE_FORMAT(status_date_end,'%d.%m.%y') as `date_end_formatted`":"
                                                           DATE_ADD(date_change,INTERVAL ".($estate == 'build'?"60":"30")." day) as `date_end`,
                                                           DATE_FORMAT(DATE_ADD(date_change,INTERVAL ".($estate == 'build'?"60":"30")." day),'%d.%m.%y') as `date_end_formatted`")."
                                                    FROM ".$sys_tables[$estate]." WHERE id=? AND id_user=?",$id,$auth->id);
                                $ajax_result['date_end'] =  $item['date_end'];
                                $ajax_result['date_end_formatted'] = $item['date_end_formatted'];
                                $ajax_result['ok'] = true;
                            }
                        }                        
                    }
                }
                break;
           ////////////////////////////////////////////////////////////////////////////////////////////////
           //перемещение объекта в архив и из архива в открытую базу
           ////////////////////////////////////////////////////////////////////////////////////////////////
           case $action == 'archive':
           case $action == 'published':
                if($ajax_mode) {
                    $from_archive = true;
                    $estate =  (empty($this_page->page_parameters[2])?"":$this_page->page_parameters[2]);
                    $deal = (empty($this_page->page_parameters[3])?"":$this_page->page_parameters[3]);
                    $id = (empty($this_page->page_parameters[4])?"":Convert::ToInt($this_page->page_parameters[4]));
                    //в случае опреации над группой, список id по типам недвижимости
                    $selected = Request::GetArray('selected',METHOD_POST);
                    //если операция для одного и все в порядке, редирект на третий шаг редактирования объекта
                    if(array_key_exists($estate, $estate_types) && Validate::isDigit($id)){
                        if($from_archive){
                            //если объект промо/премиум из тарифа, то обнуляем все и добавляем в тариф +1 опцию
                            $item = $db->fetch("SELECT payed_status, status, id_user FROM ".$sys_tables[$estate]." WHERE id = ?", $id);
                            $res = $db->query("UPDATE ".$sys_tables[$estate]." 
                                               SET published=".($action == 'archive'?2:1).", 
                                                   date_change=CURDATE() ".( (!empty($auth->id_tarif) && !empty($item) && $item['payed_status']==2) || 
                                                                             ( !empty( $auth->agency_admin ) && $auth->agency_admin == 1 )?
                                                                             " ,status=2, status_date_end='0000-00-00 00:00:00'":
                                                                             ""
                                                                            )."
                                               WHERE id=? AND id_user=?",$id,$auth->id);
                            $ajax_result['ok'] = $res;
                        }else{
                            $ajax_result['href'] = '/members/objects/edit/'.$estate.'/'.$deal.'/'.$id.'/';
                            $ajax_result['ok'] = TRUE;
                        }
                    }
                    elseif(!empty($selected)){
                        
                        if($action == 'archive'){
                            $res = true;
                            //просто убираем в архив переданные
                            foreach($selected as $estate_type=>$items){
                                if(!empty($items)){
                                    $items = preg_replace('/[^0-9\,]/','',$items);
                                    $items = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$items),',');
                                    $res = $db->query("UPDATE ".$sys_tables[$estate_type]." SET published = 2, date_change=CURDATE(), status = 2, status_date_end = '0000-00-00 00:00:00' WHERE id IN (".$items.") AND id_user = ".$auth->id);
                                }
                            }
                        }else{
                            $amount = Request::GetInteger('amount',METHOD_POST);
                            ///читаем опубликованные по агентству
                            require_once('includes/class.estate.statistics.php');
                            $objects_stats = EstateStat::getCount(empty($auth->id_agency) || $auth->agency_admin == 2?$auth->id:false, !empty($auth->id_agency)?$auth->id_agency:false, false, false);
                            //определяем количество опубликованных и сколько можно всего
                            $objects_stats['published_total'] = !empty($objects_stats['published'])?array_sum($objects_stats['published']):0;
                            $objects_stats['payed_total'] = !empty($objects_stats['payed'])?$objects_stats['payed']:0;
                            $objects_stats['moderation_total'] = !empty($objects_stats['moderation'])?array_sum($objects_stats['moderation']):0;
                            $objects_stats['archive_total'] = !empty($objects_stats['archive'])?array_sum($objects_stats['archive']):0;
                            $objects_stats['draft_total'] = !empty($objects_stats['draft'])?array_sum($objects_stats['draft']):0;
                            
                            ///кол-во максимально возможных объектов 
                            //для специалистов ограничение на кол-во опубликованных
                            if($auth->id_tarif>0){
                                $tarif = $db->fetch("SELECT * FROM ".$sys_tables['tarifs']." WHERE id = ?",$auth->id_tarif);
                                $objects_limit = $tarif['active_objects'];
                                $total_published = $objects_stats['published_total'];
                            }
                            elseif($auth->id_agency>0){
                                    //для сотрудников агентства у которых нет тарифа применяем общие ограничения агентства
                                    $objects_limit = EstateStat::getCountPacketAgencies($auth->id_agency); 
                                    //для агентств ограничение на кол-во опубликованных
                                    if($auth->agency_id_tarif > 0){
                                        Response::SetBoolean('free_promo',$auth->agency_promo > $agency_limit['promo']);
                                        Response::SetBoolean('free_premium',$auth->agency_premium > $agency_limit['premium']);
                                        Response::SetBoolean('free_vip',$auth->agency_vip > $agency_limit['vip']);
                                    }else $objects_limit = 0;
                                    $total_published = $objects_stats['published_total']+$objects_stats['moderation_total']-$objects_stats['payed_total'];
                            } else{
                                $objects_limit = 1;  //для обычного пользователя без всего
                                $total_published = $objects_stats['published_total']+$objects_stats['moderation_total']-$objects_stats['payed_total'];
                            } 
                            
                            $can_add_amount = $objects_limit - $total_published;
                            $can_add = $can_add_amount>0;
                            
                            ///смотрим, можно ли что-то опубликовать
                            if( ( empty($auth->id_agency) || 
                                  (!empty($auth->id_agency) && $auth->agency_admin == 2) || 
                                  !empty($auth->id_tarif)) && 
                                  ((!empty($objects_limit) && $objects_limit > $total_published && $total_published >= 0) || (empty($objects_limit) && !is_null($objects_limit))) 
                              ){
                                  $can_add_amount = $objects_limit - $total_published;
                                  $can_add = true;
                            } 
                            elseif( empty($auth->id_tarif) && !empty($auth->id_agency) && !empty($this_page->page_parameters[3])) {
                                $estate_type = $this_page->page_parameters[2];
                                $index_type = $estate_type!='build' ? '_'.$this_page->page_parameters[3] : "";
                                switch($estate_type){
                                    case 'live': $packet_limit =  $index_type == '_sell' ? $auth->live_sell_objects : $auth->live_rent_objects; break;
                                    case 'build': $packet_limit =  $auth->build_objects; break;
                                    case 'commercial': $packet_limit =  $index_type == '_sell' ? $auth->commercial_sell_objects : $auth->commercial_rent_objects; break;
                                    case 'country': $packet_limit =  $index_type == '_sell' ? $auth->country_sell_objects : $auth->country_rent_objects; break;
                                }
                                if(!empty($auth->id_agency) && $auth->agency_admin == 1 && ($objects_stats['published'.$index_type][$estate_type.$index_type] < $packet_limit || ($auth->agency_id_tarif == 7 && empty($packet_limit)) ) ) $can_add = true;    
                            } 
                            else $can_add = false;
                            
                            $res= true;
                            $k = 0;
                            
                            //для частных лиц, если аренда, делаем платно
                            //теперь вся аренда по единому тарифу - 70р/неделю
                            
                            $selected = Request::GetArray('selected',METHOD_POST);
                            foreach($selected as $alias => $ids){
                                $ids = array_map("Convert::ToInt",explode(',',$ids));
                                $rent_count = $db->fetch("SELECT SUM(rent = 1) AS rent_count FROM ".$sys_tables[$alias]." WHERE id IN (".implode(',',$ids).")");
                                if(!empty($rent_count) && $rent_count['rent_count'] > 0){
                                    $payed_rent = true;
                                    break;
                                }
                            }
                            
                            if($can_add_amount>=$amount && empty($payed_rent)){
                                $ajax_result['ids'] = [];
                                foreach($selected as $estate_type=>$items){
                                    if(!empty($items)){
                                        $items = preg_replace('/[^0-9\,]/','',$items);
                                        $items = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$items),',');
                                        $res = $db->query("UPDATE ".$sys_tables[$estate_type]." SET published = 1, status = 2, date_change = NOW(), status_date_end = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id IN (".$items.") AND id_user = ".$auth->id);
                                        ++$k;
                                        if($k>$can_add_amount) break;
                                    }
                                }
                            }else{
                                if(!empty($payed_rent)) $ajax_result['error_text'] = "Вы не можете бесплатно публиковать объявления раздела 'Аренда'";
                                else $ajax_result['error_text'] = ($can_add_amount>0?"Вы можете опубликовать не более ".$can_add_amount:"Вы не можете опубликовать еще объектов.");
                                $res = false;
                            }
                        }                        
                        
                        $ajax_result['group_operation'] = true;
                        $ajax_result['res'] = $res;
                        $ajax_result['ok'] = true;
                    }
                }
                break;

            
           ////////////////////////////////////////////////////////////////////////////////////////////////
           //модерация объекта по стоимости
           ////////////////////////////////////////////////////////////////////////////////////////////////
           case $action == 'moderate':
                if($ajax_mode) {
                    $estate =  $this_page->page_parameters[2]; 
                    $deal_type =  $this_page->page_parameters[3]; 
                    $cost =  Request::GetString('cost',METHOD_POST);
                    $cost = Convert::ToInt(str_replace(' ','', $cost));
                    $by_the_day = Request::GetInteger('by_the_day',METHOD_POST);
                    if(array_key_exists($estate, $estate_types) && Validate::isDigit($cost)){
                        $moderate = new Moderation($estate);
                        $status = $moderate->moderateCost(array('rent'=>$deal_type=='sell'?2:1, 'cost'=>$cost, 'by_the_day'=>$by_the_day));
                        if($status == 1) $ajax_result['ok'] = true;
                        else $ajax_result['ok'] = false;
                        $ajax_result['status'] = $status;
                    }
                }
                break;
            
           ////////////////////////////////////////////////////////////////////////////////////////////////
           //обновление статуса объекта
           ////////////////////////////////////////////////////////////////////////////////////////////////
           case $action == 'status':
                if($ajax_mode) {
                    $estate =  $this_page->page_parameters[2]; 
                    $id =  Convert::ToInt($this_page->page_parameters[3]);
                    $status =  Convert::ToInt(Request::GetInteger('status',METHOD_POST));
                    if(array_key_exists($estate, $estate_types) && Validate::isDigit($id) && Validate::isDigit($status)){
                        $info = $db->fetch("SELECT * FROM ".$sys_tables[$estate]." WHERE `id` = ? AND id_user=?",$id,$auth->id);
                        if(($status==3 || $status==4) && $info['status_date_end'] < date("Y-m-d H:i:s")) $ajax_result['ok'] = false;
                        else{
                            $res = $db->query("UPDATE ".$sys_tables[$estate]." SET status=? WHERE id=? AND id_user=?",$status,$id,$auth->id); 
                            $ajax_result['ok'] = $res;
                        }
                    }
                }
                break;
           case $action == 'delete':
                if($ajax_mode) {
                    $estate =  (empty($this_page->page_parameters[2])?0:$this_page->page_parameters[2]);
                    if(!empty($this_page->page_parameters[4])) $id =  Convert::ToInt($this_page->page_parameters[4]);
                    //в случае опреации над группой, список id по типам недвижимости
                    $selected = Request::GetArray('selected',METHOD_POST);
                    if(array_key_exists($estate, $estate_types) && Validate::isDigit($id)){
                        //удаление из основной таблицы
                        $db->query("UPDATE ".$sys_tables[$estate]." SET date_change = NOW(), published = 9, status = 2, status_date_end = '0000-00-00' WHERE id=? AND id_user=?",$id,$auth->id); 
                        //удаление фоток для id_parent
                        //$db->query("DELETE FROM ".$sys_tables[$estate.'_photos']." WHERE id_parent=? ",$id); 
                        //удаление из таблицы new
                        $list_new=$db->fetch("SELECT id FROM ".$sys_tables[$estate.'_new']." WHERE id_object=? AND id_user=?",$id,$auth->id);
                        if(!empty($list_new)){
                            $db->query("DELETE FROM ".$sys_tables[$estate.'_new']." WHERE id=? AND id_user=?",$list_new['id'],$auth->id);     
                            $db->query("DELETE FROM ".$sys_tables[$estate.'_photos']." WHERE  WHERE id_parent_new=? ",$list_new['id']); 
                        }
                        $ajax_result['ok'] = true;
                    }
                    //удаляем группу объектов
                    elseif(!empty($selected)){
                        $res = true;
                        //удаляем переданные
                        foreach($selected as $estate_type=>$items){
                            if(!empty($items)){
                                $items = preg_replace('/[^0-9\,]/','',$items);
                                $items = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$items),',');
                                $res = $db->query("DELETE FROM ".$sys_tables[$estate_type]." WHERE id IN (".$items.") AND id_user = ".$auth->id);
                            }
                        }
                        $ajax_result['group_operation'] = true;
                        $ajax_result['res'] = $res;
                        $ajax_result['ok'] = true;
                        break;
                    }
                }
                break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // добавление объекта
   ////////////////////////////////////////////////////////////////////////////////////////////////
           case $action == 'add':
           case $action == 'edit':

                //определяем черновик для пользователя
                if( $action == 'add' ){
                    if( !empty( $this_page->page_parameters[2] ) && !empty( $estate_types[ $this_page->page_parameters[2] ] )  ) $estate = $this_page->page_parameters[2];
                    if( !empty( $this_page->page_parameters[3] ) && in_array( $this_page->page_parameters[3], array( 'rent', 'sell' ) ) ) $deal_type = $this_page->page_parameters[3];
                    if( !empty( $estate ) && !empty( $deal_type ) ) {
                        $session = Session::GetString( $auth->id . $estate . $deal_type );
                        //поиск установленной ссессии для черновика
                        if( !empty( $session ) ) {
                            $draft_session_id = $db->fetch(" SELECT id FROM " . $sys_tables[ $estate ] ." WHERE `draft_session` = ?", $auth->id . $estate . $deal_type)['id'];
                            if( empty( $draft_session_id ) ) $session = false;
                        }
                        //установка новой ссессии для черновика
                        if( empty( $session ) ) {
                           Session::SetString( $auth->id . $estate . $deal_type, true ) ;
                           if( empty( $draft_session ) ) 
                                $db->query("INSERT INTO " . $sys_tables[ $estate ] ." SET 
                                                id_user = ?, date_in = NOW(), date_change = NOW(), published = ?, rent = ?, seller_name = ?, seller_phone = ?, draft_session = ?
                                                ", $auth->id, 
                                                   4, 
                                                   $deal_type == 'sell' ? 2 : 1, 
                                                   !empty($auth->name) ? $auth->name.(!empty($auth->lastname) ? " ".$auth->lastname : "") : "",
                                                   !empty($auth->phone) ? $auth->phone : "",
                                                   $auth->id . $estate . $deal_type
                                );
                                $draft_session_id = $db->insert_id;
                        } 
                    }
                }
                $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                switch($action){
                    
                    /////////////////////////////////////////////////////////////////////////////////////////////
                    //обработка фотографий
                    ////////////////////////////////////////////////////////////////////////////////////////////////
                    case 'photos':
                        
                        if($ajax_mode) {
                            $prefix = '';
                            $estate =  $this_page->page_parameters[3];
                            if(!empty($this_page->page_parameters[5]) && $this_page->page_parameters[5] == 'new'){
                                array_splice($this_page->page_parameters, 5, 1);
                                //находим id  в основной базе
                                $main_table = $db->fetch("SELECT `id_object`, `id` FROM ".$sys_tables[$estate.'_new']." WHERE `id` = ".$db->real_escape_string($this_page->page_parameters[5]));
                                if(!empty($main_table['id_object'])) $prefix = '';
                                else $prefix = "_new"; 
                                
                            }                    
                            $id =  !empty($main_table['id_object'])?$main_table['id_object']:$this_page->page_parameters[4];
                            $action = empty($this_page->page_parameters[5]) ? "" : $this_page->page_parameters[5];
                            switch($action){
                                case 'list':
                                    //получение списка фотографий
                                    if(!empty($id)){
                                        $list = Photos::getList($estate, $id, $prefix);
                                        if(!empty($list)){
                                            $ajax_result['ok'] = true;
                                            $ajax_result['list'] = $list;
                                            $ajax_result['folder'] = Config::$values['img_folders'][$estate];
                                        } else $ajax_result['error'] = 'Список фотографий пуст';
                                    } else $ajax_result['error'] = 'Неверные входные параметры';
                                    break;
                                case 'add':
                                    //загрузка фотографий
                                    if(!empty($id)){
                                        //default sizes 800x600 removed
                                        Photos::$__folder_options['sm'] = array(90,90,'cut',95);
                                        Photos::$__folder_options['med'] = array(560,415,'',85);
                                        $errors_log = [];
                                        $res = Photos::Add($estate,$id, $prefix,false,false,false,false, true, Config::Get('watermark_src'));
                                        if(!empty($res)){
                                            if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                            else {
                                                $ajax_result['ok'] = true;
                                                $ajax_result['list'] = $res;
                                            }
                                        } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                                    } else $ajax_result['error'] = 'Неверные входные параметры';
                                    $ajax_result['errors'] = $errors_log;
                                    break;
                
                                case 'setTitle':
                                    //добавление названия
                                    $id = Request::GetInteger('id_photo', METHOD_POST);                
                                    $title = Request::GetString('title', METHOD_POST);                
                                    if(!empty($id)){
                                        $res = Photos::setTitle($estate,$id, $title);
                                        if(!empty($res)) $ajax_result['ok'] = true;
                                        else $ajax_result['error'] = 'Невозможно выполнить обновление названия фото';
                                    } else $ajax_result['error'] = 'Неверные входные параметры';
                                    break;
                                case 'del':
                                    //удаление фото
                                    //id фотки
                                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                                    if(!empty($id_photo)){
                                        $res = Photos::Delete($estate, $id_photo, $prefix);
                                        if(!empty($res)){
                                            $ajax_result['ok'] = true;
                                        } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                                    } else $ajax_result['error'] = 'Неверные входные параметры';
                                    break;
                                case 'setMain':
                                    // установка флага "главное фото" для объекта
                                    //id текущей новости
                                    $id = Request::GetInteger('id', METHOD_POST);
                                    //id фотки
                                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                                    if(!empty($id_photo)){
                                        $res = Photos::setMain($estate, $id, $id_photo, $prefix);
                                        if(!empty($res)){
                                            $ajax_result['ok'] = true;
                                        } else $ajax_result['error'] = 'Невозможно установить статус';
                                    } else $ajax_result['error'] = 'Неверные входные параметры';
                                    break;
                                case 'rotate':
                                    //поворачиваем на 90 по часовой стрелке
                                    $id = Request::GetInteger('id', METHOD_POST);
                                    //id фотки
                                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                                    if(!empty($id_photo)){
                                        $res = Photos::rotatePhoto($estate,$id_photo);
                                        if(!empty($res)){
                                            $ajax_result['ok'] = true;
                                        } else $ajax_result['error'] = 'Невозможно повернуть картинку';
                                    } else $ajax_result['error'] = 'Неверные входные параметры';
                                    break;
                                case 'sort':
                                    // сортировка фото 
                                    //порядок следования фотографий
                                    $order = Request::GetArray('order', METHOD_POST);
                                    if(!empty($order)){
                                        $res = Photos::Sort($estate, $order);
                                        if(!empty($res)){
                                            $ajax_result['ok'] = true;
                                        } else $ajax_result['error'] = 'Невозможно отсортировать';
                                    } else $ajax_result['error'] = 'Неверные входные параметры';
                                    break;
                            }
                        }
                        break;
                    default:
                        //признак возможности добавления объектов
                        // пользователь с/без тарифа или агент с тарифом 
                        if( ( empty($auth->id_agency) || (!empty($auth->id_agency) && $auth->agency_admin == 2) || !empty($auth->id_tarif)) && ((!empty($objects_limit) && $objects_limit > $total_published && $total_published >= 0) || (empty($objects_limit) && !is_null($objects_limit))) ) $can_add = true;
                        elseif( empty($auth->id_tarif) && !empty($auth->id_agency) && !empty($this_page->page_parameters[3])) {
                            $estate_type = $this_page->page_parameters[2];
                            $index_type = $estate_type!='build' ? '_'.$this_page->page_parameters[3] : "";
                            switch($estate_type){
                                case 'live': $packet_limit =  $index_type == '_sell' ? $auth->live_sell_objects : $auth->live_rent_objects; break;
                                case 'build': $packet_limit =  $auth->build_objects; break;
                                case 'commercial': $packet_limit =  $index_type == '_sell' ? $auth->commercial_sell_objects : $auth->commercial_rent_objects; break;
                                case 'country': $packet_limit =  $index_type == '_sell' ? $auth->country_sell_objects : $auth->country_rent_objects; break;
                            }
                            if(!empty($auth->id_agency) && ($objects_stats['published'.$index_type][$estate_type.$index_type] < $packet_limit || ($auth->agency_id_tarif == 7 && empty($packet_limit)) ) ) $can_add = true;    
                        } 
                        else $can_add = false;
                        //Response::SetBoolean('payed_rent',(!empty($auth->id_agency) && !empty($auth->id_agency) && $this_page->page_parameters[3] == 'rent'));
                        
                        if(!empty($can_add)) Response::SetBoolean('can_add',$can_add);

                        $action = $this_page->page_parameters[1];
                        $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
                        $GLOBALS['js_set'][] = '/js/autocomplette.js';
                        $GLOBALS['css_set'][] = '/css/autocomplete.css';
                        $GLOBALS['js_set'][] = '/modules/members/form_estate.js';
                        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';            

                        $member = new Member();
                    
                        if(empty($this_page->page_parameters[2])){ // шаг 1
                            $module_template = 'edit.html';  
                        } else {
                            
                            $estate_suffix = '';
                            $estate = !empty($this_page->page_parameters[2]) && array_key_exists($this_page->page_parameters[2], $estate_types)?$this_page->page_parameters[2]:'';
                            $deal = !empty($this_page->page_parameters[3]) && array_key_exists($this_page->page_parameters[3], $deal_types)?$this_page->page_parameters[3]:'';
                            
                            $mapping = $mapping[$estate];
                            
                            //если редактировние объекта на модерации, то сдвигаем url 
                            if($action == "edit" && !empty($this_page->page_parameters[4]) && $this_page->page_parameters[4] == 'new'){
                                $estate_suffix = '_new';
                                array_splice($this_page->page_parameters, 4, 1);
                                //проверяем на id (если пришли со страницы списков, то переопределяем id и id_object)
                                $new_id = $db->fetch("SELECT `id` FROM ".$sys_tables[$estate.$estate_suffix]." WHERE id_object=? AND id_user=?", $id, $auth->id);
                                if(!empty($new_id['id'])) Host::Redirect('/members/objects/edit/'.$estate.'/'.$deal.'/new'.'/'.$new_id['id'].'/');
                            }
                          
                            //для агентства убираем поле "Кто вы"
                            if($auth->id_agency != 0) unset($mapping['id_user_type']);
                            
                            if(!empty($estate) && !empty($deal)){
                                
                                $module_template = 'edit.html';
                                $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                                if($action == 'add' && empty( $draft_session_id ) ){
                                    // создание болванки новой записи
                                    $info = $db->prepareNewRecord($sys_tables[$estate.$estate_suffix]);
                                    $info['date_in'] = $info['date_change'] = date('Y-m-d H:i:s');
                                    $info['seller_name'] = (!empty($auth->name) ? $auth->name.(!empty($auth->lastname) ? " ".$auth->lastname : "") : "");
                                    $info['seller_phone'] = (!empty($auth->phone) ? $auth->phone : "");
                                    
                                } else {
                                    $id = !empty( $draft_session_id ) ? $draft_session_id : $id;
                                    // получение данных из БД
                                    $info = $db->fetch("SELECT main.*
                                                        FROM ".$sys_tables[$estate.$estate_suffix]." main
                                                        WHERE main.id=? AND main.id_user=?", $id, $auth->id);
                                }
                                
                                //флаг аренды для частников - она стала платной
                                //теперь платная для всех
                                Response::SetBoolean('payed_rent', $info['rent'] == 1);
                                
                                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                                foreach($info as $key=>$field){
                                    if(!empty($mapping[$key])) $mapping[$key]['value'] = $info[$key];
                                }
                                
                                //читаем в зависимости от шага
                                // определение геоданных объекта
                                list( $mapping, $info ) = $member->getObjectGeoInfo($mapping,$info);
                                //типы пользователей и типы "готов работать" - не для агентств
                                if($auth->id_agency == 0){
                                    $user_types = $db->fetchall("SELECT id,title FROM ".$sys_tables['owners_user_types']." ORDER BY id ASC LIMIT 2",'id');
                                    $user_types = array_map(function($e){return $e['title'];},$user_types);
                                    $mapping['id_user_type']['values'] = $user_types;
                                     
                                    $work_statuses = $db->fetchall("SELECT id,id_user_type,title FROM ".$sys_tables['work_statuses']." ORDER BY id ASC",'id');
                                    $mapping['id_work_status']['values'] = $work_statuses;
                                }else{
                                    unset($mapping['id_user_type']);
                                    $mapping['id_work_status']['allow_empty'] = true;
                                    $mapping['id_work_status']['allow_null'] = true;
                                }
                                
                                //рассчитываем стоимость
                                $statuses_costs = $member->getStatusesCosts( $estate,  $deal == 'rent' ? 1 : 2 ,  empty($mapping['id']['value']) ? false : $mapping['id']['value'], $mapping['status']['value'] );
                                if ($auth->id_tarif > 0 || ($auth->id_agency > 0 && $auth->agency_id_tarif > 0)) Response::SetString('free_object_cost','входит в тариф');
                                else Response::SetString('free_object_cost','уже оплачено');
                                Response::SetArray( 'statuses_costs', $statuses_costs );
                                
                                // блок контактов
                                if( !$auth->authorized || !empty( $attach ) ){
                                    $mapping['seller_name']['allow_empty'] = true;
                                    $mapping['seller_name']['allow_null'] = true;
                                    $mapping['seller_phone']['allow_empty'] = true;
                                    $mapping['seller_phone']['allow_null'] = true;
                                } elseif($auth->id_tarif > 0) {
                                    if(!empty($auth->name)) $mapping['seller_name']['value'] = $auth->name.' ';
                                    if(!empty($auth->lastname)) $mapping['seller_name']['value'] .= $auth->lastname;
                                    if(!empty($auth->phone)) $mapping['seller_phone']['value'] = $auth->phone;
                                    
                                } else if( !empty( $mapping['seller_phone']['value'] ) && strlen( $mapping['seller_phone']['value'] ) >=5 ) $mapping['seller_phone']['value'] = Convert::ToPhone( $mapping['seller_phone']['value'], '812' )[0];
                               
                                // получение данных, отправленных из формы
                                $post_parameters = Request::GetParameters(METHOD_POST);
                               
                                //задаем параметры основных полей в зависимости от типа недвижимости 
                                $mapping = $member->setEstateFields($mapping, $estate, $deal, $post_parameters);
                                
                                // формирование дополнительных данных для формы (не из основной таблицы) и типы объекта
                                $mapping = $member->setAdditionalFields($mapping, $estate);
                                
                                //получение статуса модерации (если таблица new)
                                if(!empty($estate_suffix) && $info['id_moderate_status']>1) {
                                     $moderate_status = $db->fetch("SELECT * FROM ".$sys_tables['moderate_statuses']." WHERE `id` = ".$info['id_moderate_status']);
                                     Response::SetArray('moderate_status',$moderate_status);
                                }
                                if( !empty( $mapping['id_region']['value'] ) ){
                                    if( $mapping['id_region']['value'] == 78 ) $mapping['geolocation']['hidden'] = true;
                                    else $mapping['txt_district']['hidden'] = true;
                                }                                
                                // если была отправка формы - начинаем обработку
                                if(!empty($post_parameters['submit_form'])){
                                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                                    
                                    //проверка пустых INTEGER значений
                                    foreach($mapping as $key=>$field){
                                        if(!empty($mapping[$key]['type']) && ($mapping[$key]['type']==TYPE_INTEGER || $mapping[$key]['type']==TYPE_FLOAT) && isset($post_parameters[$key]) && $post_parameters[$key]=='') 
                                            $post_parameters[$key] = 0;
                                    }
                                    
                                    // тип сделки
                                    $mapping['rent']['value'] = ($deal == 'sell' ? 2 : 1);
                                    //студии
                                    if(!empty($post_parameters['studio']) && $post_parameters['studio']==1) {
                                        $post_parameters['rooms_sale'] = 0;
                                        $mapping['rooms_sale']['allow_empty'] = true;
                                        $mapping['rooms_sale']['allow_false'] = true;
                                    }
                                    if( empty( $post_parameters['id_subway']) && $post_parameters['lat'] > 0 && $post_parameters['lng'] > 0 ){
                                        $subway = curlThis("http://geocode-maps.yandex.ru/1.x/?format=json&kind=metro&geocode=" . $post_parameters['lng'] . "," . $post_parameters['lat'] );
                                        $subway = json_decode( $subway );
                                        if( !empty( $subway->response->GeoObjectCollection->featureMember[0]->GeoObject->metaDataProperty->GeocoderMetaData->AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->Premise->PremiseName) ) 
                                        {
                                            $subway_name = str_replace('метро ', '', $subway->response->GeoObjectCollection->featureMember[0]->GeoObject->metaDataProperty->GeocoderMetaData->AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->Premise->PremiseName ) ;
                                            $post_parameters['id_subway'] = $db->fetch("SELECT id FROM " . $sys_tables['subways'] ." WHERE title = ?", $subway_name)['id'];
                                        } else $post_parameters['id_subway'] = 0;         
                                    }                           
                                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                                    foreach($post_parameters as $key=>$field)
                                        if(!empty($mapping[$key])) $mapping[$key]['value'] = $post_parameters[$key];

                                        
                                   
                                    // проверка значений из формы
                                    $errors = Validate::validateParams($post_parameters,$mapping);
                                    
                                    //проверка кол-ва продаваемых комнат только если выбрана комната или стройка
                                    if( ($estate == 'live' && !empty($post_parameters['id_type_object']) && $post_parameters['id_type_object'] ==1)){
                                        $mapping['rooms_sale']['value'] =  $mapping['rooms_total']['value']; 
                                    } else if( $estate=='build' ){
                                        if(!empty($errors['rooms_total'])) unset($errors['rooms_total']);
                                        $mapping['rooms_total']['value'] =  $mapping['rooms_sale']['value']; 
                                    }
                                    
                                    //если телефон некорректен, ставим ошибку
                                    if(!empty($attaching)){
                                        if(!empty($info['seller_phone'])){
                                            $mapping['seller_phone']['value'] = $info['seller_phone'];
                                            $post_parameters['seller_phone'] = $info['seller_phone'];
                                        }
                                        if(!empty($info['seller_name'])){
                                            $mapping['seller_name']['value'] = $info['seller_name'];
                                            $post_parameters['seller_name'] = $info['seller_name'];
                                        }
                                    }
                                    
                                    if(isset($post_parameters['seller_phone']) && !Validate::isPhone($post_parameters['seller_phone'])){
                                        $mapping['seller_phone']['error'] = 'Некорректный телефон';
                                        $errors['seller_phone'] = 'Некорректный телефон';
                                    } 
                                    
                                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                                    foreach($errors as $key=>$value){
                                        if(!empty($mapping[$key])){
                                            $mapping[$key]['error'] = $value;
                                        } 
                                    }
                               
                                    if( !empty( $errors ) ) {
                                        if( DEBUG_MODE || $auth->id_group == 101 || !empty( Request::GetString('debug', METHOD_GET ) ) ) print_r( $errors );
                                        // нет ошибок
                                        Log::Write( 'members_errors', array(
                                                'id_user'       => $auth->id,
                                                'estate_type'   => $estate,
                                                'action'        => $action,
                                                'id_object'     => $info['id'],
                                                'errors'        => print_r( $errors, true ) 
                                            )
                                        );                                        
                                    }
                                    else if(empty( $errors ) ) {
                                        
                                        //определение гео данных для полученного geo_id
                                        if(!empty($mapping['geo_id']['value'])){
                                            $geolocation = $db->fetch("SELECT id_region,id_area,id_city,id_place FROM ".$sys_tables['geodata']." WHERE `id` = ".$mapping['geo_id']['value']);
                                            $mapping['id_region']['value']     = $geolocation['id_region'];
                                            $mapping['id_area']['value']       = $geolocation['id_area'];
                                            $mapping['id_city']['value']       = $geolocation['id_city'];
                                            $mapping['id_place']['value']      = $geolocation['id_place'];
                                           
                                        }                        
                                        
                                        foreach($info as $key=>$field) if(isset($mapping[$key]['value'])) $info[$key] = $mapping[$key]['value'];

                                        //чистим описание
                                        $info['notes'] = Validate::stripEmail($info['notes']);
                                        $info['notes'] = Validate::stripPhone($info['notes'],true);
                                        $info['notes'] = strip_tags($info['notes'],'<strong><b><u><i><em><ul><ol><li><p><div><span><br>');
                                        
                                        ///специалист не может редактировать контактные данные
                                        if(!empty($auth->id_tarif)){
                                            $info['seller_name'] = (!empty($auth->name) ? $auth->name.(!empty($auth->lastname) ? " ".$auth->lastname : "") : "");
                                            if(!empty($auth->phone)) $info['seller_phone'] = $auth->phone;
                                        }
                                        if(!empty($info['seller_phone']) && strlen($info['seller_phone'])>=5) $info['seller_phone'] = Convert::ToPhone($info['seller_phone'], '812')[0];
                                        //редактируем текстовый адрес
                                        $info['txt_addr'] = trim( str_replace( array( 'Санкт-Петербург,', 'Ленинградская область,' ), '', $info['txt_addr'] ) );
                                        //обнуление сессии для черновика
                                        $info['draft_session'] = '';
                                        
                                        // сохранение в БД
                                        // публикация бесплатного объекта
                                        $status = Request::GetInteger('status', METHOD_GET);
                                        if($action == 'edit'){
                                            $date_today = new DateTime();
                                            
                                            //запоминание статуса
                                            $published = $info['published'];
                                            if(!$auth->authorized) $info['published'] = 5;
                                            elseif( ($status == 2 && $objects_stats['added_total'] > 0) || 
                                                    (!empty($status) && $info['status'] == $status && 
                                                     ($info['published']==2 || $info['published']==4) && 
                                                     $status>2)) 
                                                        $info['published'] = 1;
                                            //для "тарифных" не может быть бесплатных объектов
                                            /*
                                            if(!empty($auth->id_tarif) && $status == 2) {
                                                $info['status'] = 5;
                                                $date_now = new DateTime("+".$object_cost_statuses[$status]['days_last']." day");
                                                $info['status_date_end'] = $date_now->format("Y-m-d H:i:s");
                                            }
                                            $info['date_change'] = date('Y-m-d H:i:s');
                                            */
                                            
                                            $res = $db->updateFromArray($sys_tables[$estate], $info, 'id');
                                            //если менялся порядок фотографий, обновляем
                                            if(!empty($post_parameters['photos_order'])) Photos::setListOrder($estate,$info['id'],$post_parameters['photos_order']);
                                            
                                            //группировка объектов по адресу
                                            if(!empty($info['txt_addr'])){
                                                $robot = new Robot($info['id_user']);
                                                $robot->groupByAddress($estate, $info, false);
                                            }
                                            
                                            $pay_action = Request::GetString('action',METHOD_GET);
                                            //флаг, нужна ли будет объекту дополнительная оплата
                                            $need_to_pay = true;
                                            //если выделение еще активно
                                            //если возвращаем старый объект, платить не надо
                                            if($info['status_date_end']>date('Y-m-d H:i:s') && $status == $info['status']) $need_to_pay = false;
                                                
                                            switch($estate){
                                                case 'live':$item_weight = new Estate(TYPE_ESTATE_LIVE);break;
                                                case 'build':$item_weight = new Estate(TYPE_ESTATE_BUILD);break;
                                                case 'country':$item_weight = new Estate(TYPE_ESTATE_COUNTRY);break;
                                                case 'commercial':$item_weight = new Estate(TYPE_ESTATE_COMMERCIAL);break;
                                            }
                                            $item_weight = $item_weight->getItemWeight( $info['id'], $estate);
                                            $res_weight = $db->query("UPDATE ".$sys_tables[$estate]." SET weight=? WHERE id=?",$item_weight, $info['id']);
                                            
                                            //если был опубликован бесплатный объект, оповещаем пользователя
                                            if($published > 1 && $info['published'] == 1 && !empty($auth->email) && Validate::isEmail($auth->email)){
                                                
                                                $object_link = Host::$host."/".$estate."/".$deal."/".$info['id'];
                                                if($estate == 'build') $type_object = 'flats';
                                                else{
                                                    $type_object = $db->fetch("SELECT new_alias FROM ".$sys_tables['type_objects_'.$estate]." WHERE id = ?", $info['id_type_object']);
                                                    $type_object = $type_object['new_alias'];
                                                }
                                                $env = array(
                                                    'url' => Host::GetWebPath('/'),
                                                    'host' => Host::$host,
                                                    'ip' => Host::getUserIp(),
                                                    'datetime' => date('d.m.Y H:i:s')
                                                );
                                                $letter_data = array('env' => $env, 
                                                                     'object_link' => $object_link, 
                                                                     'object_data' => array('link' => $object_link, 'type_object'=>$type_object, 'estate_type'=>$estate));
                                                $mailer = new EMailer('mail');
                                                if($mailer->sendEmail($auth->email,$auth->name,"Ваш объект опубликован на ".Host::$host,'object_published_email.html',$this_page->module_path,$letter_data))
                                                    Response::SetString('success','email');
                                            }
                                                
                                        } else {
                                            $info['blocking_id_user'] = $info['id_user'] = $auth->id; 
                                            if(!$auth->authorized) $info['published'] = 5;
                                            else $info['published'] = 4;
                                            if( !empty( $draft_session_id ) )  $res = $db->updateFromArray($sys_tables[$estate], $info, 'id');
                                            else $res = $db->insertFromArray($sys_tables[$estate], $info, 'id');
                                            if(!empty($res)){
                                                $new_id = !empty( $draft_session_id ) ? $draft_session_id : $db->insert_id;
                                                ///считаем и записываем вес объекта (в таблицу estate_type или estate_type_new):
                                                if(!empty($info['txt_addr'])){
                                                    $robot = new Robot($info['id_user']);
                                                    $info['id'] = $new_id;
                                                    $robot->groupByAddress($estate, $info, false);
                                                }
                                                
                                                switch($estate){
                                                    case 'live':$item_weight = new Estate(TYPE_ESTATE_LIVE);break;
                                                    case 'build':$item_weight = new Estate(TYPE_ESTATE_BUILD);break;
                                                    case 'country':$item_weight = new Estate(TYPE_ESTATE_COUNTRY);break;
                                                    case 'commercial':$item_weight = new Estate(TYPE_ESTATE_COMMERCIAL);break;
                                                }
                                                $item_weight = $item_weight->getItemWeight($new_id,$estate);
                                                $res_weight = $db->query("UPDATE ".$sys_tables[$estate]." SET weight=? WHERE id=?",$item_weight,$new_id);
                                                ///
                                                header('Location: '.Host::getWebPath('/members/objects/edit/'.$estate.'/'.$deal.'/'.$new_id.'/'));
                                            } else 
                                                Response::SetBoolean('errors', true);
                                        }
                                        Response::SetBoolean('saved', $res); // результат сохранения

                                    } else{
                                        Response::SetBoolean('errors', true); // признак наличия ошибок
                                    } 
                                } 
                                
                                $referer = Host::getRefererURL();
                                if($action=='edit' && !empty($referer) && (strstr($referer,'/add/')!='' || strstr($referer,'/new/')!='')) {
                                    Response::SetBoolean('form_submit', true);
                                    Response::SetBoolean('saved', true);
                                }
                                //передача веса полям
                                $estate_weight = new Estate($estate);
                                $weight_list = $estate_weight->getWeightsList($estate);
                                foreach($weight_list[$estate][$deal] as $k=>$item) {
                                    if(!empty($item['weight'])) $mapping[$item['field_title']]['weight'] = $item['weight'];
                                }

                                if( empty( $errors ) ){
                                    $pay_action = Request::GetString('action',METHOD_GET);
                                    $status = Request::GetInteger('status', METHOD_GET);
                                    //редирект на общий список, если понимаем оплаченное
                                    if($info['published'] == 1 && $status == $info['status']) Host::Redirect("/members/objects/list/");
                                    // оплату варианта или общий список если бесплатный объект
                                    elseif(!empty($pay_action) && $pay_action == 'pay_object')  
                                        Host::Redirect( "/members/objects/list/" . array_search($info['published'], $object_statuses) . "/" . ( !( $status == 2 && $info['published'] == 1 ) ? "?pay_object=true&estate=" . $estate . "&id=" . $id . "&status=" . $status : "" ) );
                                    //скролл на выбор статуса если привязка объекта
                                    elseif(!empty($attaching)) header('Location: '.Host::getWebPath('/members/objects/edit/'.$estate.'/'.$deal.'/'.$id.'/#object-statuses'));
                                }

                                //массив весов для каждого шага
                                Response::SetArray('data_mapping', $mapping);
                                Response::SetArray('info',$info);
                                
                                Response::SetBoolean('show_statuses', $info['status'] == 2);
                                //запись action для формы
                                Response::SetString('form_action',$action . '/' . $estate . '/' . $deal . ( $estate_suffix != '' ? '/new' : '' ) . ( !empty( $id ) && empty( $draft_session_id ) ? '/' . $id : '' ) );
                                //определение и запись мета-данных
                                $title = $estate_types[$estate].'. ';
                                if(!empty($deal)) $title .= $deal_types[$deal].'.'; 
                                $this_page->manageMetadata(array('title'=>($action=='add'?'Добавление ':'Редактирование ').'объекта. '.$title));
                                
                                Response::SetString('estate', $estate);
                                Response::SetString('deal', $deal);
                                Response::SetString('action', $action);
                                Response::SetArray('main_photo', Photos::getMainPhoto($estate.$estate_suffix, $id) );
                                
                                //хлебные крошки
                                if(!empty($action)) {
                                    if($action == 'add') $this_page->addBreadcrumbs('Добавление объекта', 'objects/add');
                                    else  $this_page->addBreadcrumbs('Редактирование объекта', 'estate/cab');
                                    if(!empty($estate) && !empty($deal)) $this_page->addBreadcrumbs($estate_types[$estate].'. '.$deal_types[$deal].'.', $estate.'/'.$deal);
                                }
                                
                                Response::SetString('estate_type', $estate);
                                Response::SetString('deal_type', $deal);
                                
                            } else $this_page->http_code = 404;
                            break;
                    }

                }
                break;
           case $action == 'cab':
           case $action == 'cabinet':
                if($auth->user_activity == 2) Host::Redirect('/members/conversions/consults/');
                else Host::Redirect('/members/objects/list/');
                break;
           default:

                //статусы модерации
                $statuses = array('published'=>1, 'archive'=>2, 'moderation'=>3, 'draft'=>4);
                
                // получение типа для списков
                $status = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                if( !empty( $status ) ) {
                    Response::SetString('status', $status);
                    Response::SetInteger('status_value', $statuses[ $status ]);
                }
                $objects_simple_count = EstateStat::getSimpleCount();
                Response::SetArray('counts', $objects_simple_count);
               
                if( empty( $ajax_mode ) ) {
                    $GLOBALS['css_set'][] = '/css/estate_search.css';
                    $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
                    $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
                    $GLOBALS['css_set'][] = '/modules/members/pay.css';
                    
                    $GLOBALS['css_set'][] = '/modules/housing_estates/style.css';

                    $module_template = 'cabinet.html';
                    
                    Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Личный кабинет' : $this_page->page_seo_h1);
                    $this_page->manageMetadata(array('title'=>'Список объектов'));
                    $this_page->addBreadcrumbs('Список объектов', 'cabinet');   
                } else {
                    $ajax_result['ok'] = true;
                    $module_template = 'cabinet.block.html';
                }
                
                //псевдоэлемент с попапом оплаты
                $get_parameters = Request::GetParameters(METHOD_GET);
                if( !empty( $get_parameters['pay_object'] ) && !empty( $get_parameters['id'] ) && !empty( $get_parameters['estate'] ) && !empty( $get_parameters['pay_object'] ) ){
                    Response::SetString('popup_element_link', '/members/pay_object/' . $get_parameters['estate'] . '/' . $get_parameters['id'] . '/?status=' . $get_parameters['status'] );
                }
                if( !empty( $this_page->page_parameters[2] ) ){
                    Response::SetString('active_status', $this_page->page_parameters[2] );
                }
                break;
        }
        
        
        break;

   ////////////////////////////////////////////////////////////////////////////////////////////////
   // персональные данные
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='personalinfo':

        // мэппинги модуля (объекты)
        $action = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false;    
        switch($action){
            /**************************\
            |*  Работа с фотографиями  *|
            \**************************/
            case 'photos':
                if($ajax_mode){
                    // свойства папок для загрузки и формата фотографий
                    Photos::$__folder_options =  array(
                                            'med'=>array(26,26,'cut',70),
                                            'big'=>array(52,52,'cut',75),
                                            'sm'=>array(214,214,'cut',80)
                    );                 

                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                    
                    switch($action){
                        case 'list':
                            //получение списка фотографий
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            if(!empty($id)){
                                $list = Photos::getList('users',$id);
                                if(!empty($list)){
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $list;
                                    $ajax_result['folder'] = Config::$values['img_folders']['users'];
                                } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'add':
                            //загрузка фотографий
                            $id = Request::GetInteger('id', METHOD_POST);                
                            if(!empty($id)){
                                //default sizes 236x236 removed
                                
                                $res = Photos::Add('users',$id,false,false,false,false,false,true);
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
                                $res = Photos::Delete('users',$id_photo);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'setMain':
                            // установка флага "главное фото" для объекта
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::setMain('users', $id, $id_photo);
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно установить статус';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                    }
                }
                break;
            default:
            
                if(empty($action) && $auth->id > 0){
                    $GLOBALS['js_set'][] = '/modules/members/social_auth.js';
                    $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                    $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                    $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                    $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                    $GLOBALS['js_set'][] = '/modules/members/personalinfo.js';
                    $GLOBALS['css_set'][] = '/modules/members/personalinfo.css';
                    $info = $db->fetch("SELECT ".$sys_tables['users'].".*,
                                               ".$sys_tables['agencies'].".email_service,
                                               ".$sys_tables['agencies'].".title AS agency_title,
                                               ".$sys_tables['agencies'].".chpu_title
                                        FROM ".$sys_tables['users']."
                                        LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                        WHERE ".$sys_tables['users'].".`id` = ".$auth->id);
                    if(!empty($info)){
                        // перенос дефолтных (считанных из базы) значений в мэппинг формы
                        
                        //для не-специалистов и тех кто не прикреплен к компаниям убираем поле "О себе" и уведомления об общем пуле заявок
                        if($info['id_user_type'] == 1 && empty($info['id_agency'])){
                            unset($mapping['users']['description']);
                            unset($mapping['users']['foreign_application_notification']);
                        }
                            
                        
                        //галочка уведомлений о выгрузке только для администраторов агентств
                        if(empty($info['id_agency']) || $info['agency_admin'] != 1) unset($mapping['users']['xml_notification']);
                        
                        foreach($info as $key=>$field){
                            if(!empty($mapping['users'][$key])) $mapping['users'][$key]['value'] = $info[$key];
                        }
                        //специализцаия только для агентов и специалистов, а про чужие заявки - только для ч.лиц
                        if(empty($info['id_agency']) && empty($info['id_tarif'])) {
                            $mapping['users']['specializations']['nodisplay'] = 'true';
                            $mapping['users']['_title_block_profile_']['nodisplay'] = 'true';
                        }
                        // получение данных, отправленных из формы
                        $post_parameters = Request::GetParameters(METHOD_POST);
                        //убираем email для заявок - чтобы он не менялся, он только для показа, меняется из админки
                        unset($post_parameters['email_service']);
                        // если была отправка формы - начинаем обработку
                        if(!empty($post_parameters['submit_form'])){
                            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана

                            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)                         
                            foreach($post_parameters as $key=>$field){
                                if(!empty($mapping['users'][$key]) && !empty($mapping['users'][$key]['fieldtype']) && $mapping['users'][$key]['fieldtype']=='checkbox_set') {
                                    if(!empty($post_parameters[$key.'_set'])){
                                        $mapping['users'][$key]['value'] = 0;
                                        foreach($post_parameters[$key.'_set'] as $pkey=>$pval){
                                            $mapping['users'][$key]['value'] += pow(2,$pkey-1);
                                        }
                                        $post_parameters[$key] = trim($mapping['users'][$key]['value']);
                                    }
                                }
                                $mapping['users'][$key]['value'] = $post_parameters[$key];
                            }
                            // проверка значений из формы
                            $errors = Validate::validateParams($post_parameters,$mapping['users']);
                            
                            //если почта непуста, проверяем ее
                            if(!empty($post_parameters['email'])){
                                if(empty($errors['email']) && !Validate::isEmail($mapping['users']['email']['value'])) $errors['email'] = 'Некорректный email';
                                else{
                                    // дубликаты мейла
                                    $res = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE email=? AND id<>?", $mapping['users']['email']['value'], $auth->id);
                                    if(!empty($res)) $errors['email'] = $mapping['users']['email']['error'] = 'Такой email уже есть в базе данных пользователей';
                                }
                            }
                            
                            //если почта пустая, выводим ошибку
                            if (empty($post_parameters['email'])) $errors['email']='email должен быть заполнен';
                            
                            //если телефон непуст, проверяем его
                            if(!empty($post_parameters['phone'])){
                                if($post_parameters['phone'] == 8) $post_parameters['phone'] = '';
                                elseif(empty($errors['phone']) && !Validate::isPhone($mapping['users']['phone']['value'])) $errors['phone'] = 'Некорректный телефон';
                                else{
                                    // дубликаты мейла
                                    $res = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE phone=? AND id<>?", $mapping['users']['phone']['value'], $auth->id);
                                    if(!empty($res)) $errors['phone'] = $mapping['users']['phone']['error'] = 'Такой телефон уже есть в базе данных пользователей';
                                }
                            }
                            // проверка на корректность пароля
                            $old_passwd = $post_parameters['old_passwd'];
                            $new_passwd = $post_parameters['new_passwd'];
                            if(!empty($old_passwd) && empty($new_passwd)) $errors['new_passwd'] = $mapping['users']['new_passwd']['error'] = 'Введите новый пароль';
                            elseif(empty($old_passwd) && !empty($new_passwd)) $errors['new_passwd'] = $mapping['users']['new_passwd']['error'] = 'Введите текущий пароль';
                            elseif(!empty($old_passwd) && !empty($new_passwd)){
                                 if(!preg_match("/^[a-zA-Z\-\+\.\,\_\(\)\{\}\[\]\<\>\~0-9\ ]{4,24}$/", $mapping['users']['old_passwd']['value'])) $errors['old_passwd'] = $mapping['users']['old_passwd']['error'] = 'Некорректный пароль. Должен быть не короче 4-х символов и может содержать латинские буквы, цифры, знаки - + . , _ ( ) { } [ ] < >';
                                 elseif(!preg_match("/^[a-zA-Z\-\+\.\,\_\(\)\{\}\[\]\<\>\~0-9\ ]{4,24}$/", $mapping['users']['new_passwd']['value'])) $errors['new_passwd'] = $mapping['users']['new_passwd']['error'] = 'Некорректный пароль. Должен быть не короче 4-х символов и может содержать латинские буквы, цифры, знаки - + . , _ ( ) { } [ ] < >';
                                 elseif(sha1($auth->passwd)!=sha1(sha1($mapping['users']['old_passwd']['value']))) $errors['old_passwd'] = $mapping['users']['old_passwd']['error'] = 'Неправильный текущий пароль';
                                 else $mapping['users']['passwd']['value'] = sha1(sha1($new_passwd)); // запоминаем новый пароль  (2-ой хеш)
                            }

                            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                            foreach($errors as $key=>$value){
                                if(!empty($mapping['users'][$key])) $mapping['users'][$key]['error'] = $value;
                            }
                            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                            if(empty($errors)) {
                                // подготовка всех значений для сохранения
                                foreach($info as $key=>$field){
                                    if(isset($mapping['users'][$key]['value'])) $info[$key] = $mapping['users'][$key]['value'];
                                }
                                
                                //если цвета нет, назначаем
                                if(empty($auth->avatar_color)){
                                    //случайный цвет аватары
                                    $colors = Config::Get('users_avatar_colors');
                                    $info['avatar_color'] = $colors[mt_rand(0,11)];
                                } 
                                $res = $db->updateFromArray($sys_tables['users'], $info, 'id');
                                
                                //если пароль не менялся, берем старый из сессии
                                if(empty($new_passwd)){
                                    $auth_data = Session::GetParameter('auth_data');
                                    $new_passwd=$auth_data['hash_password'];
                                }
                                else $new_passwd=sha1($new_passwd);
                                
                                
                                //перелогиниваемся
                                Session::SetParameter('auth_data',array(
                                                                    'user_email'    => $info['email'],
                                                                    'user_phone'    => $info['phone'],
                                                                    'user_login'    => $info['login'],
                                                                    'hash_password' => $new_passwd,
                                                                    'cookie_save'   => true
                                                                    )
                                );
                                if(!empty($old_passwd) && !empty($new_passwd)) $auth->AuthCheck($info['login'], $new_passwd); 
                                
                                $auth_array['name'] = $info['name'];
                                $auth_array['lastname'] = $info['lastname'];
                                Response::SetArray('auth',$auth_array);
                                Response::SetArray('form_auth_data',$auth_data);
                                Response::SetArray('auth_data',array('id'=>$auth->id,
                                                                     'name'=>$auth->name, 
                                                                     'lastname'=>$auth->lastname, 
                                                                     'balance'=>$auth->balance, 
                                                                     'avatar_color'=>$auth->avatar_color, 
                                                                     'tarif_title'=>$auth->tarif_title,
                                                                     'id_agency'=>$auth->id_agency,
                                                                     'agency_title'=>$auth->agency_title));
                                Response::SetBoolean('saved', $res); // результат сохранения
                            } else Response::SetBoolean('errors', true); // признак наличия ошибок
                        }
                        // запись данных для отображения на странице
                        Response::SetArray('data_mapping',$mapping['users']);
                                                
                        $module_template = "personalinfo.html";
                        //СЕО-шняжки
                        Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Персональная информация' : $this_page->page_seo_h1);
                        $this_page->manageMetadata(array('title'=>'Персональная информация'));
                        $this_page->addBreadcrumbs('Персональная информация', 'personalinfo');
                    } else $this_page->http_code = 404;
                    
                } else $this_page->http_code = 404;
                break;
            }
            break;

   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Пополнение баланса
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $this_page->requested_url=='pay/result':
        $this_page->page_parameters[1] = 'result';
   case $action=='pay':
        if(Host::getUserIp()!='109.167.249.172' && !DEBUG_MODE) Response::SetBoolean('cant_pay',true);
        $mrh_login = "bsn_roboexchange";
        $mrh_pass1 = "FRXBWpWL1JGREM6oQpSuxufoZsxV1QNt";
        $mrh_pass2 = "AOesZ5A3fSSycoa7Pfrrx3SWK690EB7m";        
        //обработка результата оплаты
        if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]=='result'){
            $inv_id = Request::GetString('InvId',METHOD_POST);
            $inv_summ = Request::GetString('OutSum',METHOD_POST);
            $crc = Request::GetString('SignatureValue',METHOD_POST);
            $shp_object = 0;
            $shp_user = Request::GetString('shp_user',METHOD_POST);
            $promocode = Request::GetString('shp_promocode',METHOD_POST);
            //log request
            $sql = "UPDATE ".$sys_tables['users_pay']."
                    SET `log` = CONCAT_WS(' \n',`log`,?) WHERE id=?";
            $result = $db->query($sql,
                                 date('Y-m-d H:i:s').": ResultRequest: InvId=".$inv_id."; OutSum=".$inv_summ."; SignatureValue=".$crc.";shp_object=0; shp_user=".$shp_user,
                                 $inv_id);

            // nOutSum:nInvId:sMerchantPass2[:пользовательские параметры, в отсортированном порядке]
            $crc = strtoupper($crc); // 6A9A1221DF6B57B46AF935793BA52CD8
            $my_crc = strtoupper(md5("$inv_summ:$inv_id:$mrh_pass2:shp_object=0:shp_user=$shp_user"));
            if(empty($inv_id)) echo "bad INV_ID\n";
            elseif(empty($inv_summ)) echo "bad SUMM\n";
            elseif($my_crc != $crc) echo "bad SIGN\n";
            else { 
                $item_history = $db->fetch("SELECT * FROM ".$sys_tables['users_pay']." WHERE id=".$inv_id." AND id_status=2 AND TIMESTAMPDIFF(MINUTE,NOW(),create_datetime)<30");
                if(!empty($item_history)) {
                    //проверка куки промокода
                    if(!empty($item_history['id_promocode'])){
                        $check_promocode = $auth->checkPromocode(false, $item_history['id_promocode'], $item_history['id_user']);    
                        if(!empty($check_promocode) && empty($check_promocode['id_user'])){
                            $db->query("INSERT INTO ".$sys_tables['promocodes_used']." SET id_parent=?, id_user=?", $check_promocode['id'], $item_history['id_user']);
                            //15022016 изменено: теперь paygate = 1, отеляем промокод от робокассы
                            $db->query("INSERT INTO ".$sys_tables['users_finances']." SET income = ?, id_user = ?, obj_type = ?, paygate = 1, id_parent = ?", $inv_summ*($check_promocode['percent']/100), $item_history['id_user'], 'promocode', $check_promocode['id']);
                        }
                    }
                    //финансы - пополнение баланса paygate = 2, чтобы выделить пополнение робокассой
                    $db->query("INSERT INTO ".$sys_tables['users_finances']." SET income = ?, id_user = ?, obj_type = ?, paygate = 2", $inv_summ, $item_history['id_user'], 'balance');
                    $sql = "UPDATE ".$sys_tables['users']."
                            SET balance = balance + ?
                            WHERE id = ?";
                    if(!empty($check_promocode) && empty($check_promocode['id_user']))  $inv_summ = $inv_summ * (1 + ($check_promocode['percent']/100));
                    $result = $db->query($sql, $inv_summ, $item_history['id_user']);
                    if($result) {
                        $id_status = 4;
                        $log_text = date('Y-m-d H:i:s').': Оплата принята. Ваш баланс пополнен на '.$inv_summ.'руб.';
                    } else {
                        $id_status = 3;
                        $log_text = date('Y-m-d H:i:s').': ВНИМАНИЕ!!! Оплата произведена, но баланс не изменен!';
                    }
                    $sql = "UPDATE ".$sys_tables['users_pay']."
                            SET `id_status`=?,
                                `log`=CONCAT_WS(' \n',`log`,?) 
                            WHERE `id`=?";
                    $result = $db->query($sql ,$id_status, $log_text, $inv_id);
                    echo "OK$inv_id\n";
                } else echo "bad CHECK info";
            }
            exit();            
        } elseif($auth->id>0){
            $module_template = "pay.balance.html";
            if(!empty($this_page->page_parameters[1]) && ($this_page->page_parameters[1]=='success' || $this_page->page_parameters[1]=='fail')){ // логирование ответа от робокассы (удачная или неудачная оплата)
                    Response::SetString('complete',$this_page->page_parameters[1]);
                    // редирект на оплату объекта с которого произошла инициирование пополнения баланса
                    $redirect = Cookie::GetString('redirect_to_pay');
                    if(!empty($redirect)) {
                        Cookie::SetCookie('redirect_to_pay', "", -3600);
                        Host::Redirect($redirect.'&redirect=1');
                    }
            } else  { //вывод формы на оплату
                $obj_type = !empty($this_page->page_parameters[1])?$this_page->page_parameters[1]:'';
                //id объекта
                if($obj_type == 'balance' && $auth->id>0){
                    //промокод
                    $code = Request::GetString('promocode',METHOD_POST);
                    if(!empty($code)){
                        $check_promocode = $auth->checkPromocode($code);    
                        if(!empty($check_promocode) && empty($check_promocode['id_user']) && $check_promocode['type'] == 1 && $check_promocode['summ'] > 0){ //промокод на фиксированную сумму
                            $db->query("INSERT INTO ".$sys_tables['promocodes_used']." SET id_parent=?, id_user=?", $check_promocode['id'], $auth->id);
                            //15022016 изменено: теперь paygate = 1, отеляем промокод от робокассы
                            $db->query("INSERT INTO ".$sys_tables['users_finances']." SET income = ?, id_user = ?, obj_type = ?, paygate = 1, id_parent=?", $check_promocode['summ'], $auth->id, 'promocode', $check_promocode['id']);
                            $db->query("UPDATE ".$sys_tables['users']." SET balance = balance + ? WHERE id = ?", $check_promocode['summ'], $auth->id);
                            $auth->balance = $auth->balance + $check_promocode['summ'];
                            Response::SetArray('auth', $auth);
                            Response::SetArray('item',$check_promocode);
                            Response::SetString('complete','promocode_pay');
                            break;
                        }  
                    }
                    $GLOBALS['css_set'][] = '/modules/members/pay.css';
                    $GLOBALS['js_set'][] = '/modules/members/pay.js';
                    $stady = Request::GetString('paying', METHOD_POST);    
                    if(empty($stady)){ //стартовая форма оплаты
                        Response::SetString('complete','start');
                        $sql = "INSERT INTO ".$sys_tables['users_pay']."
                                    (`create_datetime`,`id_status`,`id_user`, `log`)
                                VALUES
                                    (NOW(),1,?,?)";
                        $result = $db->query($sql, $auth->id, date('Y-m-d H:i:s').': Инициация оплаты...');
                        $inv_id = $db->insert_id;
                        $summ = Request::GetInteger('summ',METHOD_GET);
                        if(!empty($summ)) Response::SetInteger('inv_summ',$summ);
                        Response::SetString('query','ok');
                        Response::SetString('inv_id', $inv_id);
                        Response::SetString('protect', substr($obj_type,0,1));  
                        Response::SetInteger( 'balance', $auth->balance );
                        // если пополнение со страниц оплата объекта или пакета, то запоминаем в сессию
                        $ref = Host::getRefererURL();
                        if(!empty($ref) && (strstr($ref,'members/pay_object')!='' || strstr($ref,'members/pay_tarif')!='')) {
                            Cookie::SetCookie('redirect_to_pay', $ref, 60*15, '/');
                        }
                    } else { //продолжение оплаты
                        Response::SetString('complete','continue');
                        $db->query("DELETE FROM ".$sys_tables['users_pay']." WHERE `id_status`=1 AND DATEDIFF(NOW(),`create_datetime`)>2");
                        $inv_id = Request::GetInteger('inv_id', METHOD_POST);
                        $inv_desc = Request::GetString('inv_desc', METHOD_POST);
                        $inv_summ = preg_replace('~[^0-9]+~','',$_POST['inv_summ']);
                        if(empty($inv_summ) || $inv_summ <=0 ) Host::Redirect('/members/pay/balance/');

                        if(!empty($inv_id) && !empty($inv_summ)){
                            $sql = "SELECT * FROM ".$sys_tables['users_pay']." WHERE id=? AND id_user=? AND id_status=1 AND TIMESTAMPDIFF(MINUTE,NOW(),create_datetime)<30";
                            $result = $db->query($sql,$inv_id,$auth->id);
                            if($result && $result->num_rows > 0) {
                                $sql = "UPDATE ".$sys_tables['users_pay']."
                                        SET `id_status`=2, 
                                            `pay_sum`=?,
                                            `id_promocode` = ?,
                                            `log`=CONCAT_WS(' \n',`log`,?)
                                        WHERE id=?";
                                $result = $db->query($sql,
                                                     $inv_summ,
                                                     !empty($check_promocode) && empty($check_promocode['id_user']) && $check_promocode['type'] == 2 ? $check_promocode['id'] : 0,
                                                     date('Y-m-d H:i:s').': Переход к платежному сервису...',
                                                     $inv_id);  
                                $crc = md5("$mrh_login:$inv_summ:$inv_id:$mrh_pass1:shp_object=0:shp_user=$auth->id");
                                $url = "https://auth.robokassa.ru/Merchant/Index.aspx" //""
                                        ."?MrchLogin=".$mrh_login
                                        ."&OutSum=".$inv_summ
                                        ."&InvId=".$inv_id
                                        ."&Desc=".urlencode($inv_desc)
                                        ."&SignatureValue=".$crc
                                        ."&shp_user=".$auth->id
                                        ."&shp_object=0";
                                Host::Redirect($url, false);
                            }
                        }                   
                    }
                    Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Пополнение баланса' : $this_page->page_seo_h1);
                    $this_page->manageMetadata(array('title'=>'Пополнение баланса'));
                    $this_page->addBreadcrumbs('Пополнение баланса', 'pay');
                    
                    } else $this_page->http_code = 404;
            } 
        } else $this_page->http_code = 404;
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Обновление цены при изменении списка оплачиваемых объектов
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'refresh_sum' && $ajax_mode:
        $affected_objects = Request::GetString('id_object',METHOD_POST);
        $affected_objects = json_decode($affected_objects);
        $status = Request::GetString('status',METHOD_POST);
        $agency_object_long = Request::GetInteger('agency_object_long', METHOD_POST);
        
        if( empty($affected_objects) || (empty($summ) && empty($auth->id_tarif) && empty($summ) && $auth->id_agency==0 && $auth->agency_admin == 1 && empty($auth->agency_id_tarif)) || empty($status)){
            Response::SetString('wrong_params', true);
            break;
        }          
        
        $free_left = getFreeLeft($status);
        
        extract(combineBuyList($affected_objects,$status,$agency_object_long,$free_left,$object_cost_statuses,true));
        
        $ajax_result['payed'] = $total_objects - $free_objects;
        $ajax_result['free'] = $free_objects;
        $ajax_result['total'] = $total_objects;
        $ajax_result['summ'] = $total_sum;
        $ajax_result['ok'] = true;
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Оплата группы объектов
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='pay_objects':
        $GLOBALS['js_set'][] = '/modules/members/pay.js';
        $module_template = "pay.objects.html";
        $agency_object_long = 1;
        Response::SetBoolean('pay_page', true);
        //обработка результата оплаты
        if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]=='success'){
            Response::SetString('complete',$this_page->page_parameters[1]);

            $affected_objects = Request::GetString('id_object',METHOD_POST);
            $affected_objects = json_decode($affected_objects);
            $summ = Request::GetInteger('summ',METHOD_POST);
            $status = Request::GetString('status',METHOD_POST);
            //выделение админом агентства с флагом на 30 дней
            $agency_object_long = Request::GetString('agency_object_long', METHOD_POST);
            $agency_object_long = ($status == 8 || $status == 1) ? $agency_object_long : ($agency_object_long == "true" || empty($agency_object_long));
            
            if( empty($affected_objects) || (empty($summ) && empty($auth->id_tarif) && empty($summ) && $auth->id_agency==0 && $auth->agency_admin == 1 && empty($auth->agency_id_tarif)) || empty($status)){
                Response::SetString('wrong_params', true);
                break;
            }          
            
            $total_sum = 0;
            $free_left = getFreeLeft($status);
            
            extract(combineBuyList($affected_objects,$status,$agency_object_long,$free_left,$object_cost_statuses));
            
            $summ = $total_sum;
                
            if(empty($affected_objects)) Response::SetString('wrong_object', true);
            else {
                
                //проверка баланса для оплаты
                $user = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE balance >= ? AND id = ?",$total_sum, $auth->id);
                if(empty($user)){
                    Response::SetString('not_enough_balance', true);
                } else {
                    
                    $affected_ids = implode(', ',$affected_ids);
                    Response::SetString('affected_ids',$affected_ids);
                    //if(!empty($obj_types[$obj_type])) Response::SetString('obj_type_title',$obj_types[$obj_type]);
                    if(!empty($object_cost_statuses[$status])) Response::SetString('status_title',$object_cost_statuses[$status]['title']);
                    
                    if(!empty($object_cost_statuses[$status])) Response::SetString('status_title',$object_cost_statuses[$status]['title']);

                    if(!empty($auth->id_tarif) && $status!=1) $summ = $object_cost_statuses[$status]['cost'];
                    
                    Response::SetString('summ',$summ);
                    
                    if($status!=1){ //продлеваем объект
                        // Для объектов из XML файла применяется по умолчанию на 1 сутки
                        if(!empty($auth->agency_admin) && $auth->agency_admin == 1 && $summ == 0 && empty($agency_object_long)) $date_now = new DateTime("+1 day");
                        else $date_now = new DateTime("+".$object_cost_statuses[$status]['days_last']." day");
                        $status_date_end = $date_now->format("Y-m-d H:i:s");
                        
                        foreach($affected_objects as $key=>$item){
                            if(empty($item)) continue;
                            $item = preg_replace('[^0-9\,]','',$item);
                            $item = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$item),',');
                            $db->query("UPDATE ".$sys_tables[$key]."
                                        SET `status` = ?, status_date_end = ?, published = 1, date_change = NOW()
                                        WHERE id IN (".$item.")", $status, $status_date_end);
                        }
                        $status_date_end = $date_now->format("d.m.Y");
                    }
                    else if($status == 1){
                        //поднятие объекта
                        $raise_period = round($summ/$total_objects);
                        $raise_period = ($raise_period == 120?5:1);
                        $status_date_end = new Datetime('+'.$raise_period.' day');
                        $status_date_end = $status_date_end->format("d.m.Y");
                        foreach($affected_objects as $key=>$item){
                            if(empty($item)) continue;
                            $item = preg_replace('[^0-9\,]','',$item);
                            $item = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$item),',');
                            $db->query("UPDATE ".$sys_tables[$key]."
                                        SET `raising_datetime` = NOW() + INTERVAL 1 DAY, raising_status = 1, raising_days_left = ".$raise_period.", date_change = NOW()
                                        WHERE id IN (".$item.")");
                        }
                    }   
                    
                    if(!empty($auth->id_agency)){
                        //для сотрудников агентства у которых нет тарифа применяем общие ограничения агентства
                        $objects_limit = EstateStat::getCountPacketAgencies($auth->id_agency); 
                        //для агентств ограничение на кол-во опубликованных
                        if($auth->agency_id_tarif > 0){
                            $agency_free_promo = $auth->agency_promo > $agency_limit['promo'];
                            $agency_free_premium = $auth->agency_premium > $agency_limit['premium'];
                            $agency_free_vip = $auth->agency_vip > $agency_limit['vip'];
                        }
                    }
                    
                    //if(!empty($auth->id_tarif) && ( ($status == 3 && $user['promo_left']>0) || ($status == 4 && $user['premium_left']>0) || ($status == 6 && $user['vip_left']>0))){
                    //добавили условие для агентств
                    if( (!empty($auth->id_tarif) && ( ($status == 3 && $user['promo_left']>0) || ($status == 4 && $user['premium_left']>0) || ($status == 6 && $user['vip_left']>0))) ||
                        (!empty($auth->agency_id_tarif) && ( ($status == 3 && !empty($agency_free_promo)) || ($status == 4 && !empty($agency_free_premium)) || ($status == 6 && !empty($agency_free_vip)))) ){
                        
                        //снятие промо-премиум с баланса пользователя
                        $db->query("UPDATE ".$sys_tables['users']." SET ".$object_cost_statuses[$status]['alias']."_left = ".$object_cost_statuses[$status]['alias']."_left - ".$free_objects." 
                                    WHERE id = ?", $auth->id);
                        
                        //делаем записи в финансы
                        foreach($affected_objects as $key=>$item){
                            if(empty($item)) continue;
                            $item = preg_replace('[^0-9\,]','',$item);
                            $item = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$item),',');
                            $item = explode(',',$item);
                            //учитываем бесплатные объекты
                            $free_counter = $free_objects;
                            foreach($item as $k=>$i){
                                $db->query("INSERT INTO ".$sys_tables['users_finances']." SET expenditure = ?, id_user = ?, obj_type = ?, id_parent=?, estate_type = ?", 
                                    ($free_counter>0?0:$object_cost_statuses[$status]['cost']), $auth->id, $object_cost_statuses[$status]['alias'], $i, $key);
                                if($free_counter > 0) --$free_counter;
                            }
                        }
                        $auth->checkAuth($auth->email, $auth->passwd, 1);
                    } else {
                        //снятие денег с баланса пользователя
                        $db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?",$summ, $auth->id);
                        
                        //учитываем бесплатные объекты
                        $free_counter = $free_objects;
                        //делаем записи в финансы
                        foreach($affected_objects as $key=>$item){
                            if(empty($item)) continue;
                            $item = preg_replace('[^0-9\,]','',$item);
                            $item = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$item),',');
                            $item = explode(',',$item);
                            foreach($item as $k=>$i){
                                $db->query("INSERT INTO ".$sys_tables['users_finances']." SET expenditure = ?, id_user = ?, obj_type = ?, id_parent=?, estate_type = ?", 
                                    ($free_counter>0?0:$object_cost_statuses[$status]['cost']), $auth->id, $object_cost_statuses[$status]['alias'], $i, $key);
                                if($free_counter > 0) --$free_counter;
                            }
                        }
                        
                        $free_counter = $free_objects;
                        //объекты оплачены с баланса
                        if($status!=1)
                            foreach($affected_objects as $key=>$item){
                                if(empty($item)) continue;
                                $item = preg_replace('[^0-9\,]','',$item);
                                $item = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$item),',');
                                $item = explode(',',$item);
                                //учитываем бесплатные объекты
                                foreach($item as $k=>$i){
                                    if($free_counter > 0) --$free_counter;
                                    else $db->query("UPDATE ".$sys_tables[$key]." SET payed_status=1 WHERE id=?",$i);
                                }
                            }
                    }
                    
                    //шлем уведомление пользователю об опубликовании объекта платно или с услугой
                    $mailer = new EMailer('mail');
                    //тип опубликования                                            
                    switch($status){
                        case 1: $status_text = "с услугой «Поднятие»";break;
                        case 3: $status_text = "с услугой «Промо»";break;
                        case 4: $status_text = "с услугой «Премиум»";break;
                        case 6: $status_text = "с услугой «VIP»";break;
                        default:  $status_text = "";break;
                    }
                    
                    
                    
                    //ссылка на опубликованное объявление    
                    Response::SetInteger('total_objects',$total_objects);
                    Response::SetArray('object_data',array('status_text' => $status_text, 'status'=>$status, 'status_date_end'=>$status_date_end));
                    Response::SetArray('objects_links',$objects_links);
                    Response::SetArray('objects_list',$objects_list);
                    if($status == 1) Response::SetString('raising_datetime', date('d.m.y H:i', strtotime("+".($summ == 30 ? 1 : 5)." days")));
                    if(!empty($user['email']) && Validate::isEmail($user['email'])){
                        //данные окружения для шаблона
                        $env = array(
                            'url' => Host::GetWebPath('/'),
                            'host' => Host::$host,
                            'ip' => Host::getUserIp(),
                            'datetime' => date('d.m.Y H:i:s')
                        );
                        Response::SetArray('env', $env);
                        // инициализация шаблонизатора
                        $eml_tpl = new Template('objects_published_email.html', $this_page->module_path);
                        // формирование html-кода письма по шаблону
                        $html = $eml_tpl->Processing();
                        // перевод письма в кодировку мейлера
                        $html = iconv('UTF-8', $mailer->CharSet, $html);
                        // параметры письма
                        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Ваши '.$total_objects .' объектов опубликованы на '.Host::$host);
                        $mailer->Body = $html;
                        $mailer->AltBody = strip_tags($html);
                        $mailer->IsHTML(true);
                        $mailer->AddAddress($user['email'], iconv('UTF-8',$mailer->CharSet, $user['name']));
                        $mailer->From = 'no-reply@bsn.ru';
                        $mailer->FromName = 'bsn.ru';
                        //попытка отправить
                        if($mailer->Send()) Response::SetString('success','email');
                    }
                    
                }
            }
        }
        elseif($auth->id>0){
            $affected_objects = Request::GetString('selected',METHOD_GET);
            $affected_objects = json_decode($affected_objects);
            if((!$auth->id>0) || empty($affected_objects)){
                $this_page->http_code = 404;
                break;
            }
            
            $status = Request::GetInteger('status',METHOD_GET);
            if(!in_array($status,array(1,3,4,5,6))) Response::SetString('query','wrong_params');
            
            $total_sum = 0;
            $free_left = getFreeLeft($status);
            
            $objects_list = [];
            $total_objects = 0;
            $free_objects = 0;
            
            extract(combineBuyList($affected_objects,$status,$agency_object_long,$free_left,$object_cost_statuses));
            
            if(empty($total_objects)) $this_page->http_code = 404;
            Response::SetArray('objects_list',$objects_list);
            $affected_objects = Request::GetString('selected',METHOD_GET);
            Response::SetString('affected_objects',$affected_objects);
            Response::SetInteger('payed_objects',$total_objects-$free_objects);
            Response::SetInteger('total_objects',$total_objects);
            Response::SetInteger('free_objects',$free_objects);
            Response::SetInteger('summ_difference', $total_sum - $auth->balance);    
            Response::SetString('summ', $total_sum);    
            Response::SetString('status', $status); 
            Response::SetString('complete','start');
            $this_page->manageMetadata(array('title'=>'Оплата варианта'));
            $this_page->addBreadcrumbs('Оплата варианта', 'pay_object');
        }
        
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Оплата объекта
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='pay_object':
        Response::SetBoolean('pay_page', true);
        $GLOBALS['js_set'][] = '/modules/members/pay.js';
        //типы недвижимости
        $obj_types = array('live'=>'Жилая','build'=>'Новостройки','commercial'=>'Коммерческая','country'=>'Загородная'); 
        $module_template = "pay.object.html";
        $ajax_result['ok'] = true;
        //обработка результата оплаты
        if(!empty($this_page->page_parameters[3]) && $this_page->page_parameters[3]=='success'){
            Response::SetString('complete',$this_page->page_parameters[3]);
            
            $member = new Member();
            
            $id_object = Request::GetInteger('id_object',METHOD_POST);
            $obj_type = Request::GetString('obj_type',METHOD_POST);
            $status = Request::GetString('status',METHOD_POST);
            $agency_object_long = Request::GetString('agency_object_long', METHOD_POST);
            $agency_object_long = $member->getDaysLong($agency_object_long,$status);
            
            
            $object_params_valid = $member->checkObjectPaymentParams($id_object, $obj_type, $status);
            
            if(is_array($object_params_valid)){
                $response_key = array_keys($object_params_valid);
                $response_key = array_pop($response_key);
                $response_value = array_values($object_params_valid);
                $response_value = array_pop($response_value);
                Response::SetString($response_key, $response_value);
            }
            else {
                $result = $member->doObjectOperation($obj_type, $id_object, $status, $agency_object_long);
                if(!empty($result['response']))
                    foreach($result['response'] as $key=>$value){
                        Response::SetString($key,$value);
                    }
                
                //шлем уведомление пользователю об опубликовании объекта платно или с услугой
                if(!empty($result['object_status_set']) && $result['object_status_set'] && !empty($auth->email) && Validate::isEmail($auth->email)){
                    $mailer = new EMailer('mail');
                    //тип опубликования                                            
                    $status_text = "с услугой «";
                    switch($status){
                        case 1: $status_text .= "Поднятие»";break;
                        case 3: $status_text .= "Промо»";break;
                        case 4: $status_text .= "Премиум»";break;
                        case 6: $status_text .= "VIP»";break;
                        default:  $status_text = "";break;
                    }
                    
                    
                    if( $ajax_mode ){
                        $ajax_result['id'] = $id_object;
                        $ajax_result['obj_type'] = $obj_type;
                        $ajax_result['status'] = $status;
                        $ajax_result['cost'] = $result['summ'];
                    }
                    
                    //ссылка на опубликованное объявление    
                    Response::SetArray('object_data',array('status_text' => $status_text,'link' => $result['object_link'], 'status'=>$status, 'type_object'=>$result['type_object'], 'estate_type'=>$obj_type));
                    //Response::SetArray('item',$item);
                    if($status == 1) Response::SetString('raising_datetime', date('d.m.y H:i', strtotime("+".$result['days_long']." days")));
                    if(!empty($auth->email) && Validate::isEmail($auth->email)){
                        //данные окружения для шаблона
                        $env = array(
                            'url' => Host::GetWebPath('/'),
                            'host' => Host::$host,
                            'ip' => Host::getUserIp(),
                            'datetime' => date('d.m.Y H:i:s')
                        );
                        Response::SetArray('env', $env);
                        // инициализация шаблонизатора
                        $eml_tpl = new Template('object_published_email.html', $this_page->module_path);
                        // формирование html-кода письма по шаблону
                        $html = $eml_tpl->Processing();
                        // перевод письма в кодировку мейлера
                        $html = iconv('UTF-8', $mailer->CharSet, $html);
                        // параметры письма
                        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Ваш объект опубликован на '.Host::$host);
                        $mailer->Body = $html;
                        $mailer->AltBody = strip_tags($html);
                        $mailer->IsHTML(true);
                        $mailer->AddAddress($auth->email, iconv('UTF-8',$mailer->CharSet, $auth->name));
                        $mailer->From = 'no-reply@bsn.ru';
                        $mailer->FromName = 'bsn.ru';
                        //попытка отправить
                        if($mailer->Send()) Response::SetString('success','email');
                    }
                }
                                
            }
        } elseif($auth->id>0){
            Response::SetString('complete','start');
            
            $id_object = Convert::ToInteger(!empty($this_page->page_parameters[2])?$this_page->page_parameters[2]:0);
            $estate = !empty($this_page->page_parameters[1])?$this_page->page_parameters[1]:'';
            if(empty($sys_tables[$estate])){
                $this_page->http_code = 404;
                break;
            }
            $object_info = $db->fetch("SELECT rent,status FROM ".$sys_tables[$estate]." WHERE id = ? AND id_user = ?",$id_object,$auth->id);
            if(empty($object_info)){
                $this_page->http_code = 404;
                break;
            }
            $target_status = Request::GetString('status',METHOD_GET);
            $agency_object_long = false;
            $member = new Member();
            $payment_params = $member->getStatusesCosts($estate, $object_info['rent'], $id_object,$object_info['status'], $target_status);
            $payment_params = array_pop($payment_params);
            Response::SetBoolean('agency_free_object',!empty($auth->id_agency) && $auth->agency_admin == 1 && empty($payment_params['cost']));
            Response::SetArray('payment_params',$payment_params);
            Response::SetString('status',$target_status);
            Response::SetString('single_object_cost',$payment_params['cost']);
            Response::SetString('summ', $payment_params['cost']);
            Response::SetInteger('summ_difference', $payment_params['cost'] - $auth->balance);    
            if(!empty($id_object)){
                Response::SetString('id_object',$id_object);
                Response::SetString('status', $target_status); 
                Response::SetString('obj_type',$estate);
                
                switch($estate){
                    case 'live':
                        $EstateItem = new EstateItemLive($id_object);
                        break;
                    case 'build':
                        $EstateItem = new EstateItemBuild($id_object);
                        break;
                    case 'commercial':
                        $EstateItem = new EstateItemCommercial($id_object);
                        break;
                    case 'country':
                        $EstateItem = new EstateItemCountry($id_object);
                        break;
                    case 'inter':
                        $EstateItem = new EstateItemInter($id_object);
                        break;
                    default:
                        $EstateItem = null;
                        $this_page->http_code=404;
                        break;
                }
                $item = $EstateItem->getData();
                $titles = $EstateItem->getTitles();
                Response::SetArray('item',$item);
                Response::SetArray('photo',Photos::getMainPhoto($estate,$id_object));
                Response::SetArray('titles',$titles);
                
                $statuses_costs = $member->getStatusesCosts( $estate,  $item['rent'] ,  $item['id'], $item['status'] );
                Response::SetArray( 'statuses_costs', $statuses_costs );

                Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Оплата варианта' : $this_page->page_seo_h1);
                $this_page->manageMetadata(array('title'=>'Оплата варианта'));
                $this_page->addBreadcrumbs('Оплата варианта', 'pay_object');
                $module_template = "pay.object.html";
                $ajax_result['ok'] = true;
            } else $this_page->http_code = 404;
        } else $this_page->http_code = 404;
        break;        
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Оплата тарифа
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='pay_tarif':
        //обработка результата оплаты
        $module_template = "pay.tarif.html";
        if(!empty($auth->tarif_title)){ // если тариф установлен
            Response::SetBoolean('tarif_alredy_set', true);
        } elseif(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]=='success'){//установка тарифа 
            Response::SetString('complete','success');

            $id_tarif = Request::GetInteger('id_tarif',METHOD_POST);
            $summ = Request::GetInteger('summ',METHOD_POST);
            $period = Request::GetInteger('period',METHOD_POST);
            if(empty($id_tarif) || empty($summ) || empty($period)) { Response::SetString('wrong_params', true);   break;  }

            $discount = $db->fetch("SELECT * FROM ".$sys_tables['tarifs_discounts']." WHERE months = ?", $period);
            if(empty($discount)) { Response::SetString('wrong_params', true);   break;  }
            $tarif = $db->fetch("SELECT * FROM ".$sys_tables['tarifs']." WHERE id = ?", $id_tarif);
            if(empty($tarif)) { Response::SetString('wrong_params', true);   break;  }
            
            //проверка баланса для оплаты
            $balance = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE balance >= ? AND id = ?",$summ, $auth->id);
            if(empty($balance)){
                Response::SetString('not_enough_balance', true);
            } else {
                Response::SetArray('tarif',$tarif['title']);
                Response::SetString('summ',$summ);
                Response::SetString('period',$period);

                //снятие денег с баланса пользователя и обновление тарифа, простановка брендированной страницы, типа пользователя
                $db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ?, id_tarif = ?, promo_left = ?, premium_left = ?, vip_left = ?, tarif_start = NOW(), tarif_end = CURDATE() + INTERVAL ".$period." MONTH, payed_page = ".$tarif['payed_page'].", id_user_type = 2 WHERE id = ?",
                            $summ, $id_tarif, $period*$tarif['promo_available'], $period*$tarif['premium_available'], $period*$tarif['vip_available'], $auth->id);
                $auth->AuthCheck();
                //запись в финансы
                $db->query("INSERT INTO ".$sys_tables['users_finances']." SET expenditure = ?, id_user = ?, obj_type = ?, id_parent=?", 
                            $summ, $auth->id, 'tarif', $id_tarif
                );
                if(!empty($auth->id_agency)){
                    $db->query("UPDATE ".$sys_tables['build']." SET published = 2, status = 2, status_date_end = '0000-00-00 00:00:00', date_change = NOW() WHERE id_user = ?", $auth->id);
                    $db->query("UPDATE ".$sys_tables['live']." SET published = 2, status = 2, status_date_end = '0000-00-00 00:00:00', date_change = NOW() WHERE id_user = ?", $auth->id);
                    $db->query("UPDATE ".$sys_tables['commercial']." SET published = 2, status = 2, status_date_end = '0000-00-00 00:00:00', date_change = NOW() WHERE id_user = ?", $auth->id);
                    $db->query("UPDATE ".$sys_tables['country']." SET published = 2, status = 2, status_date_end = '0000-00-00 00:00:00', date_change = NOW() WHERE id_user = ?", $auth->id);
                }
                
            }
        } elseif($auth->id>0){
            Response::SetString('complete','start');
            $id_tarif = Request::GetInteger('id_tarif',METHOD_GET);  
            $period = Request::GetInteger('period',METHOD_GET);  
            if(!empty($id_tarif) && !empty($id_tarif)){
                
                //кол-во месяцев и соот-но скидка
                $discount = $db->fetch("SELECT * FROM ".$sys_tables['tarifs_discounts']." WHERE months = ?", $period);
                if(empty($discount)) { Response::SetString('wrong_params', true);   break;  }
                //поиск тарифа
                $tarif = $db->fetch("SELECT * FROM ".$sys_tables['tarifs']." WHERE id = ?", $id_tarif);
                if(empty($tarif)) { Response::SetString('wrong_params', true);   break;  }
                $full_summ = $period * (
                                        ($tarif['active_objects'] - 1 - $tarif['promo_available'] - $tarif['premium_available'] - $tarif['vip_available']) * $object_cost_statuses[5]['cost'] +
                                        $tarif['promo_available'] *   $object_cost_statuses[3]['cost'] + 
                                        $tarif['premium_available'] * $object_cost_statuses[4]['cost'] +
                                        $tarif['vip_available'] * $object_cost_statuses[5]['cost']
                                        );
                
                $summ = $period * $tarif['cost']* (1 - $discount['discount']/100);
                $discount_summ = $full_summ - $summ;
                Response::SetString('complete','start');
                Response::SetArray('tarif', $tarif);  
                Response::SetInteger('period', $period); 
                Response::SetInteger('summ_difference', $summ - $auth->balance);    
                Response::SetInteger('summ', $summ);    
                Response::SetInteger('full_summ', $full_summ);    
                Response::SetInteger('discount_summ', $discount_summ);    
                //для агентств перевод всех объектов в архив
                Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Оплата тарифа' : $this_page->page_seo_h1);
                $this_page->manageMetadata(array('title'=>'Оплата тарифа'));
                $this_page->addBreadcrumbs('Оплата тарифа', 'pay_tarif');
            } else $this_page->http_code = 404;
        } else $this_page->http_code = 404;
        $module_template = "pay.tarif.html";
        $ajax_result['ok'] = true;
        break;         
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // результат приглашения сотрудника в агентство
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'invite_result':
        if(!$ajax_mode) exit(0);
        $id = Request::GetString('id', METHOD_POST);
        $id_agency = Request::GetString('id_agency', METHOD_POST);
        $type = Request::GetString('type', METHOD_POST);
        //проверка на лимит сотрудников
        $staff_list = $db->fetch("SELECT COUNT(*) as cnt                              
                                   FROM ".Config::$sys_tables['users']."
                                   WHERE id_agency = ?
        ", $id_agency);        
        $agency_info = $db->fetch("
            SELECT ".$sys_tables['agencies'].".*,
                    CONCAT(".$sys_tables['users'].".name, ' ', ".$sys_tables['users'].".lastname) as user_name,
                    ".$sys_tables['users'].".id as id_user
            FROM ".$sys_tables['agencies']."
            LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id 
            WHERE ".$sys_tables['users'].".agency_admin = 1 AND ".$sys_tables['agencies'].".id = ?
        ", $id_agency);
        $can_add_staff = ( !empty($staff_list['cnt']) && $staff_list['cnt'] < $agency_info['staff_number'] )|| $agency_info['staff_number'] == -1;
        $from_invites = $db->query("DELETE FROM ".$sys_tables['users_invites_agencies']." WHERE id_user = ? AND id_agency = ?", $auth->id, $id_agency);
        if($type == 'accept' && empty($can_add_staff)) {
            $ajax_result['error_text'] = 'Не удается добавить вас к компании '.$agency_info['title'].', <a href="/members/messages/add/'.$agency_info['id_user'].'/">обратитесь</a> к администратору аккаунта <b>'.$agency_info['user_name'].'</b>';
        } else {
            if($from_invites && $type == 'accept') $from_users = $db->query("UPDATE ".$sys_tables['users']." SET id_agency = ?, agency_admin = 2 WHERE id = ?", $id_agency, $auth->id);
            $ajax_result['ok'] = ($type == 'accept' && $from_users) || ($type == 'reject' && $from_invites);
        }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Промо-код - проверка
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='promocode':
    if($ajax_mode){
        $module_template = "promocode.check.html";
        $value = Request::GetString('value', METHOD_POST);
        $summ = Request::GetInteger('summ', METHOD_POST);
        $item = $auth->checkPromocode($value);
        Response::SetArray('item', $item);
        Response::SetInteger('summ', $summ);
        Response::SetString('value', $value);
        if(empty($item) || (!empty($item['id_user']))) $ajax_result['error'] = true;
        else if(!empty($item)){
            if(!empty($item['min_summ']) && $item['type'] == 2 && $item['min_summ']>$summ) $ajax_result['error'] = true;
            else{
                $ajax_result['msg'] = $item;
                $ajax_result['ok'] = true;
            }
        }
    }
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // офис
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'office':
        Response::SetBoolean('left_menu_office', true);
        if(empty($auth->id_agency)) {
            $this_page->http_code = 403;
            break;
        }
        $GLOBALS['css_set'][] = '/modules/members/style.office.css';
        $GLOBALS['js_set'][] = '/modules/members/script.office.js';
        //флаг возможности добавления сотрудников
        $can_add_staff = ( !empty($agency_limit['staff_number']) && $agency_limit['staff_number'] < $auth->agency_staff_number )|| $auth->agency_staff_number == -1;
        Response::SetBoolean('can_add_staff', $can_add_staff);
        $action = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false;
            switch(true){
               ////////////////////////////////////////////////////////////////////////////////////////////////
               // сотрудники
               ////////////////////////////////////////////////////////////////////////////////////////////////
                case $action == 'staff':
                    Response::SetString('page_type','office_staff');
                    $action = !empty($this_page->page_parameters[2]) ? $this_page->page_parameters[2] : false;
                    switch(true){
                       ////////////////////////////////////////////////////////////////////////////////////////////////
                       // управление балансом
                       ////////////////////////////////////////////////////////////////////////////////////////////////
                        case $action == 'balance_manage' && count($this_page->page_parameters) == 3:
                            $id = Request::GetInteger('id', METHOD_POST);
                            $type = Request::GetString('type', METHOD_POST);
                            $summ = Request::GetInteger('summ', METHOD_POST);
                            if(!empty($id) && !empty($action) && !empty($summ)){
                                $db->query("UPDATE ".$sys_tables['users']." SET balance = balance ".($type=='increase'?'+':'-')." ? WHERE id = ?", $summ, $id);
                                $db->query("UPDATE ".$sys_tables['users']." SET balance = balance ".($type=='increase'?'-':'+')." ? WHERE id = ?", $summ, $auth->id);
                                $db->query("INSERT ".$sys_tables['users_finances']." SET ".($type=='increase'?'income':'expenditure')."=?, id_user=?, obj_type = 'admin_balance', id_initiator = ?", $summ, $id, $auth->id);
                                $db->query("INSERT ".$sys_tables['users_finances']." SET ".($type=='increase'?'expenditure':'income')."=?, id_user=?, obj_type = 'admin_balance', id_initiator = ?", $summ, $auth->id, $auth->id);
                                
                            }
                            
                            break;
                       ////////////////////////////////////////////////////////////////////////////////////////////////
                       // отвязать существующий аккаунт
                       ////////////////////////////////////////////////////////////////////////////////////////////////
                        case $action == 'hire':
                            if(!$ajax_mode || $auth->agency_admin == 2) exit(0);
                            $id = Request::GetString('id', METHOD_POST);
                            $item = $db->fetch("SELECT ".$sys_tables['users'].".*,
                                                       ".$sys_tables['agencies'].".title as agency_title
                                                FROM ".$sys_tables['users']." 
                                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                                WHERE ".$sys_tables['users'].".id = ?", $id
                            );
                            $from_invites = $db->query("DELETE FROM ".$sys_tables['users_invites_agencies']." WHERE id_user = ? AND id_agency = ?", $id, $auth->id_agency);
                            $from_users = $db->query("UPDATE ".$sys_tables['users']." SET id_agency = 0, status = 1 WHERE id = ? AND id_agency = ?", $id, $auth->id_agency);
                            Response::SetArray('item', $item);
                            $ajax_result['ok'] = $from_invites || $from_users;
                            $mailer = new EMailer('mail');
                            // инициализация шаблонизатора
                            $eml_tpl = new Template('office.staff.mail.hire.html', $this_page->module_path);
                            // формирование html-кода письма по шаблону
                            $html = $eml_tpl->Processing();
                            // перевод письма в кодировку мейлера
                            $html = iconv('UTF-8', $mailer->CharSet, $html);
                            // параметры письма
                            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Уведомление от компании «'.$auth->agency_title.'» на bsn.ru');
                            $mailer->Body = $html;
                            $mailer->AltBody = strip_tags($html);
                            $mailer->IsHTML(true);
                            $mailer->AddAddress($item['email'], iconv('UTF-8',$mailer->CharSet, trim($item['name'].' '.$item['lastname'])));
                            $mailer->From = 'no-reply@bsn.ru';
                            $mailer->FromName = 'bsn.ru';
                            if($mailer->Send()) $ajax_result['ok'] = true;
                            break;
                        case $action == 'invite':
                            if(!$ajax_mode || $auth->agency_admin == 2) exit(0);
                            $action = !empty($this_page->page_parameters[3]) ? $this_page->page_parameters[3] : false;
                            switch(true){
                               ////////////////////////////////////////////////////////////////////////////////////////////////
                               // привязать существующий аккаунт
                               ////////////////////////////////////////////////////////////////////////////////////////////////
                                case $action == 'add':
                                    $id = Request::GetString('id', METHOD_POST);
                                    $item = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE id = ?", $id);
                                    if(!empty($item)){
                                        $res = $db->query("INSERT INTO ".$sys_tables['users_invites_agencies']." SET id_user = ?, id_agency = ?, can_edit = 2", $id, $auth->id_agency);
                                        if($res && Validate::isEmail($item['email'])){
                                            Response::SetArray('item', $item);
                                            Response::SetArray('auth', $auth);
                                            $mailer = new EMailer('mail');
                                            // инициализация шаблонизатора
                                            $eml_tpl = new Template('office.staff.mail.html', $this_page->module_path);
                                            // формирование html-кода письма по шаблону
                                            $html = $eml_tpl->Processing();
                                            // перевод письма в кодировку мейлера
                                            $html = iconv('UTF-8', $mailer->CharSet, $html);
                                            // параметры письма
                                            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Компания «'.$auth->agency_title.'» просит подтвердить Ваш аккаунт на bsn.ru');
                                            $mailer->Body = $html;
                                            $mailer->AltBody = strip_tags($html);
                                            $mailer->IsHTML(true);
                                            $mailer->AddAddress($item['email'], iconv('UTF-8',$mailer->CharSet, trim($item['name'].' '.$item['lastname'])));
                                            $mailer->From = 'no-reply@bsn.ru';
                                            $mailer->FromName = 'bsn.ru';
                                            if($mailer->Send()) $ajax_result['ok'] = true;
                                        }
                                    }
                                    break;
                               ////////////////////////////////////////////////////////////////////////////////////////////////
                               // поиск пользователей по email
                               ////////////////////////////////////////////////////////////////////////////////////////////////
                                default:
                                    $email = Request::GetString('email', METHOD_POST);
                                    if(!empty($email)){

                                        $item = $db->fetch("SELECT 
                                                                ".$sys_tables['users'].".*, 
                                                                ".$sys_tables['users_photos'].".`name` as `photo`, 
                                                                LEFT (".$sys_tables['users_photos'].".`name`,2) as `subfolder`
                                                            FROM ".$sys_tables['users']."
                                                            LEFT JOIN ".$sys_tables['users_photos']." ON ".$sys_tables['users'].".id_main_photo = ".$sys_tables['users_photos'].".id
                                                            WHERE ".$sys_tables['users'].".email = ?
                                                            GROUP BY ".$sys_tables['users'].".id
                                                            ", 
                                        $email);
                                        if(empty($item)) $ajax_result['action'] = 'user_new'; // нет пользователя с таким email
                                        else if($auth->id_agency == $item['id_agency']) $ajax_result['action'] = 'user_alredy_in_agency'; // уже в агентстве
                                        else { // приглашение существующего пользователя
                                            $ajax_result['action'] = 'user_exists'; 
                                            $ajax_result['name'] = $item['name'].' '.$item['lastname'];
                                            $ajax_result['id'] = $item['id'];
                                            $ajax_result['photo'] = !empty($item['photo']) ? Config::$values['img_folders']['live'].'/sm/'.$item['subfolder'].'/'.$item['photo'] : "img/layout/no-avatar-staff.gif";
                                        }
                                        $ajax_result['ok'] = true;
                                    }
                                    break;
                            }
                            break;
                        /**************************\
                        |*  Работа с фотографиями  *|
                        \**************************/
                        case $action == 'photos':
                            if($ajax_mode){
                                // свойства папок для загрузки и формата фотографий
                                Photos::$__folder_options =  array(
                                                        'med'=>array(26,26,'cut',70),
                                                        'big'=>array(52,52,'cut',75),
                                                        'sm'=>array(214,214,'cut',80)
                                );                 

                                $ajax_result['error'] = '';
                                // переопределяем экшн
                                $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                                
                                switch($action){
                                    case 'list':
                                        //получение списка фотографий
                                        //id текущей новости
                                        $id = Request::GetInteger('id', METHOD_POST);
                                        if(!empty($id)){
                                            $list = Photos::getList('users',$id);
                                            if(!empty($list)){
                                                $ajax_result['ok'] = true;
                                                $ajax_result['list'] = $list;
                                                $ajax_result['folder'] = Config::$values['img_folders']['users'];
                                            } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                                        } else $ajax_result['error'] = 'Неверные входные параметры';
                                        break;
                                    case 'add':
                                        //загрузка фотографий
                                        $id = Request::GetInteger('id', METHOD_POST);                
                                        if(!empty($id)){
                                            //default sizes 236x236 removed
                                            $res = Photos::Add('users',$id,false,false,false,false,false,true);
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
                                            $res = Photos::Delete('users',$id_photo);
                                            if(!empty($res)){
                                                $ajax_result['ok'] = true;
                                            } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                                        } else $ajax_result['error'] = 'Неверные входные параметры';
                                        break;
                                    case 'setMain':
                                        // установка флага "главное фото" для объекта
                                        //id текущей новости
                                        $id = Request::GetInteger('id', METHOD_POST);
                                        //id фотки
                                        $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                                        if(!empty($id_photo)){
                                            $res = Photos::setMain('users', $id, $id_photo);
                                            if(!empty($res)){
                                                $ajax_result['ok'] = true;
                                            } else $ajax_result['error'] = 'Невозможно установить статус';
                                        } else $ajax_result['error'] = 'Неверные входные параметры';
                                        break;
                                }
                            }
                            break;
                        case $action == 'add' && count($this_page->page_parameters) == 3:
                        case $action == 'edit' && count($this_page->page_parameters) == 4:
                            if($auth->agency_admin == 2){
                                $this_page->http_code = 403;
                                break;
                            }
                            Response::SetString('action',$action);
                            
                            $module_template = 'office.staff.edit.html';
                        
                            $GLOBALS['js_set'][] = '/modules/members/social_auth.js';
                            $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                            $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                            $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                            $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';       
                            $GLOBALS['js_set'][] = '/modules/members/personalinfo.js';     
                            if($action == 'add') $info = $db->prepareNewRecord($sys_tables['users']);
                            else {
                                //возможность редактировать агента
                                $id = !empty($this_page->page_parameters[3]) ? Convert::ToInt($this_page->page_parameters[3]) : 0;
                                if(empty($id)){
                                    $this_page->http_code = 403;
                                    break;
                                }
                                $user = $db->fetch("
                                                    SELECT id, can_edit FROM ".$sys_tables['users_invites_agencies']." WHERE `id_user` = ? AND id_agency = ?
                                                    UNION
                                                    SELECT id, '1' as can_edit FROM ".$sys_tables['users']." WHERE `id` = ? AND id_agency = ?
                                ", $id, $auth->id_agency, $id, $auth->id_agency);
                                if(empty($user)){
                                    $this_page->http_code = 403;
                                    break;
                                }
                                Response::SetArray('full_info', $user);
                                $info = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE `id` = ?", $id);
                            }
                            if(!empty($info)){
                                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                                
                                foreach($info as $key=>$field){
                                    if(!empty($mapping['staff'][$key])) $mapping['staff'][$key]['value'] = $info[$key];
                                }
                                // получение данных, отправленных из формы
                                $post_parameters = Request::GetParameters(METHOD_POST);
                                if(!empty($post_parameters['find-email'])) $post_parameters['email'] = $post_parameters['find-email'];
                                // если была отправка формы - начинаем обработку
                                if(!empty($post_parameters['submit_form'])){
                                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)                         
                                    foreach($post_parameters as $key=>$field){
                                        if(!empty($mapping['staff'][$key]) && !empty($mapping['staff'][$key]['fieldtype']) && $mapping['staff'][$key]['fieldtype']=='specializations_set') {
                                            if(!empty($post_parameters[$key.'_set'])){
                                                $mapping['staff'][$key]['value'] = 0;
                                                foreach($post_parameters[$key.'_set'] as $pkey=>$pval){
                                                    $mapping['staff'][$key]['value'] += pow(2,$pkey-1);
                                                }
                                                $post_parameters[$key] = trim($mapping['staff'][$key]['value']);
                                            }
                                        }
                                        $mapping['staff'][$key]['value'] = $post_parameters[$key];
                                    }
                                    // проверка значений из формы
                                    $errors = Validate::validateParams($post_parameters,$mapping['staff']);
                                    //поиск на существующий email
                                    if($action == 'add'){
                                        $user = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE email = ?", $mapping['staff']['email']['value']);
                                        if(!empty($user)) $errors['find-email'] = 'Пользователь с таким email уже существует';
                                    }
                                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                                    foreach($errors as $key=>$value){
                                        if(!empty($mapping['staff'][$key])) $mapping['staff'][$key]['error'] = $value;
                                    }
                                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                                    if(empty($errors)) {
                                        // подготовка всех значений для сохранения
                                        foreach($info as $key=>$field){
                                            if(isset($mapping['staff'][$key]['value'])) $info[$key] = $mapping['staff'][$key]['value'];
                                        }
                                        if(strlen($info['phone'])<7) $info['phone'] = '' ;
                                        if($action == 'edit') $res = $db->updateFromArray($sys_tables['users'], $info, 'id');
                                        else {
                                            $info['original_passwd'] = substr(md5(time()),-6);
                                            $info['passwd'] = sha1(sha1($info['original_passwd']));
                                            $res = $db->insertFromArray($sys_tables['users'], $info, 'id');
                                            
                                            Response::SetArray('auth', $auth);

                                            //письмо с новыми логин-пароль и инфой о приглашении
                                            $mailer = new EMailer('mail');
                                            // инициализация шаблонизатора
                                            $eml_tpl = new Template('office.staff.mail.registration.html', $this_page->module_path);
                                            // формирование html-кода письма по шаблону
                                            $html = $eml_tpl->Processing();
                                            // перевод письма в кодировку мейлера
                                            $html = iconv('UTF-8', $mailer->CharSet, $html);
                                            // параметры письма
                                            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Компания «'.$auth->agency_title.'» просит подтвердить Ваш аккаунт на bsn.ru');
                                            $mailer->Body = $html;
                                            $mailer->AltBody = strip_tags($html);
                                            $mailer->IsHTML(true);
                                            $mailer->AddAddress($info['email'], iconv('UTF-8',$mailer->CharSet, trim($info['name'].' '.$info['lastname'])));
                                            $mailer->From = 'no-reply@bsn.ru';
                                            $mailer->FromName = 'bsn.ru';
                                            $mailer->Send();
                                            
                                            
                                            if(!empty($res)){
                                                $new_id = $db->insert_id;
                                                //список ожадния приглашения
                                                $res = $db->query("INSERT INTO ".$sys_tables['users_invites_agencies']." SET id_user = ?, id_agency = ?, can_edit = 1", $new_id, $auth->id_agency);
                                                // редирект на редактирование свеженькой страницы
                                                if(!empty($res)) {
                                                    header('Location: '.Host::getWebPath('/members/office/staff/edit/'.$new_id.'/'));
                                                    exit(0);
                                                }
                                            }

                                        }
                                        
                                        Response::SetBoolean('saved', $res); // результат сохранения
                                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                                }
                                Response::SetArray('info', $info);
                                // запись данных для отображения на странице
                                Response::SetArray('data_mapping',$mapping['staff']);
                                //СЕО-шняжки
                                Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Анкета сотрудника' : $this_page->page_seo_h1);
                                $this_page->manageMetadata(array('title'=>'Анкета сотрудника'));
                            } else $this_page->http_code = 404;
                            Response::SetString('page','office_edit');    
                            break;
                       ////////////////////////////////////////////////////////////////////////////////////////////////
                       // список сотрудников
                       ////////////////////////////////////////////////////////////////////////////////////////////////
                       case empty($action) && count($this_page->page_parameters) == 2:
                            Response::SetString('page','office_list');    
                            //определение id администратора
                            $admin = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ? AND agency_admin = ?", $auth->id_agency, 1);
                            if(empty($admin)){
                                 $this_page->http_code = 403;
                                 break;
                            }
                            Response::SetArray('admin_info', $admin);
                            //список сотрудников
                            $list = $db->fetchall("
                                SELECT * FROM (
                                    SELECT ".$sys_tables['users'].".*,
                                    IF(
                                        TIMESTAMPDIFF(MINUTE, `last_enter`, NOW())< 10, 'online',
                                        IF(
                                            DATE(`last_enter`) = CURDATE(), CONCAT('сегодня в ',DATE_FORMAT(`last_enter`,'%k:%i')),
                                            IF(
                                                DATE(`last_enter`) = CURDATE() - 1, CONCAT('вчера в ',DATE_FORMAT(`last_enter`,'%k:%i')),
                                                DATE_FORMAT(`last_enter`,'%e %M в %k:%i')
                                            )
                                        )
                                    ) as last_activity,
                                    ".$sys_tables['users_photos'].".`name` as `photo`, 
                                    LEFT (".$sys_tables['users_photos'].".`name`,2) as `subfolder`,
                                    IFNULL(".$sys_tables['messages'].".`id`, m.id) as `message_id`,
                                    '1' as can_edit,
                                    'agent' as user_status
                                    FROM ".$sys_tables['users']."
                                    LEFT JOIN ".$sys_tables['users_photos']." ON ".$sys_tables['users'].".id_main_photo = ".$sys_tables['users_photos'].".id
                                    LEFT JOIN ".$sys_tables['messages']." ON ".$sys_tables['messages'].".id_parent = 0 AND (".$sys_tables['messages'].".id_user_from = ? AND ".$sys_tables['messages'].".id_user_to = ".$sys_tables['users'].".id)
                                    LEFT JOIN ".$sys_tables['messages']." m ON m.id_parent = 0 AND (m.id_user_to = ? AND m.id_user_from = ".$sys_tables['users'].".id)
                                    WHERE (".$sys_tables['users'].".id_agency = ? AND agency_admin = ?) OR ".$sys_tables['users'].".id = ?
                                    GROUP BY ".$sys_tables['users'].".id

                                    UNION 
                                    
                                    SELECT ".$sys_tables['users'].".*,
                                    IF(
                                        TIMESTAMPDIFF(MINUTE, `last_enter`, NOW())< 10, 'online',
                                        IF(
                                            DATE(`last_enter`) = CURDATE(), CONCAT('сегодня в ',DATE_FORMAT(`last_enter`,'%k:%i')),
                                            IF(
                                                DATE(`last_enter`) = CURDATE() - 1, CONCAT('вчера в ',DATE_FORMAT(`last_enter`,'%k:%i')),
                                                DATE_FORMAT(`last_enter`,'%e %M в %k:%i')
                                            )
                                        )
                                    ) as last_activity,
                                    ".$sys_tables['users_photos'].".`name` as `photo`, 
                                    LEFT (".$sys_tables['users_photos'].".`name`,2) as `subfolder`,
                                    ".$sys_tables['messages'].".`id` as `message_id`,
                                    ".$sys_tables['users_invites_agencies'].".can_edit,
                                    'waiting_agent' as user_status
                                    FROM ".$sys_tables['users_invites_agencies']."
                                    RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['users_invites_agencies'].".id_user
                                    LEFT JOIN ".$sys_tables['users_photos']." ON ".$sys_tables['users'].".id_main_photo = ".$sys_tables['users_photos'].".id
                                    LEFT JOIN ".$sys_tables['messages']." ON ".$sys_tables['messages'].".id_parent = 0 AND ((".$sys_tables['messages'].".id_user_from = ? AND ".$sys_tables['messages'].".id_user_to = ".$sys_tables['users'].".id) OR (".$sys_tables['messages'].".id_user_from = ".$sys_tables['users'].".id AND ".$sys_tables['messages'].".id_user_to = ?))
                                    WHERE ".$sys_tables['users_invites_agencies'].".id_agency = ?
                                    GROUP BY ".$sys_tables['users'].".id
                                ) a
                                ORDER BY a.agency_admin = 1 DESC
                                
                                ", false, $auth->id, $auth->id, $auth->id_agency, 2, $admin['id'], $auth->id, $auth->id, $auth->id_agency
                            );
                            //количество объектов агента
                            foreach($list as $k=>$item){
                                if(strlen($item['phone'])>7) $list[$k]['phone'] = Convert::ToPhone($item['phone'], false, 8)[0];
                                $objects = $db->fetch("
                                                        SELECT SUM(cnt) as cnt FROM (
                                                            SELECT COUNT(*) as cnt FROM ".$sys_tables['live']." WHERE id_user = ? AND published = 1
                                                            UNION ALL
                                                            SELECT COUNT(*) as cnt FROM ".$sys_tables['build']." WHERE id_user = ? AND published = 1
                                                            UNION ALL
                                                            SELECT COUNT(*) as cnt FROM ".$sys_tables['commercial']." WHERE id_user = ? AND published = 1
                                                            UNION ALL
                                                            SELECT COUNT(*) as cnt FROM ".$sys_tables['country']." WHERE id_user = ? AND published = 1
                                                        ) as a
                                
                                ", $item['id'], $item['id'], $item['id'], $item['id']);
                                $list[$k]['objects_count'] = $objects['cnt'];
                                //специализации
                                $specializations = [];
                                foreach(Config::Get('users_specializations') as $skey=>$val){
                                    if($item['specializations']%(pow(2,$skey))>=pow(2,$skey-1)) $specializations[] = $val;
                                }
                                $list[$k]['specializations_row'] = implode(', ', $specializations);
                                
                            }
                            Response::SetArray('list', $list);
                            $module_template = 'office.staff.list.html';
                            break;
                    }
                    break;
                case empty($action) && count($this_page->page_parameters) == 1:
                    Host::Redirect('/members/office/staff/');
                    break;
            }
        //отображать меню с сотрудниками
        Response::SetBoolean('office', true);
        break;
    case $action == 'estate_prolongate' && empty($this_page->page_parameters[0]):
        //проверяем данные ссылки
        //сверяем код пользователя
        $user_email = Request::GetString('mail',METHOD_GET);
        $letter_user_code = Request::GetString('user_code',METHOD_GET);
        $user_info = $db->fetch("SELECT id,`datetime` FROM ".$sys_tables['users']." WHERE email = ?",$user_email);
        $readed_user_code = sha1(sha1($user_info['id'].$user_info['datetime'].date("dmY")));
        if($readed_user_code != $letter_user_code){
            $this->http_code = 404;
            break;
        }
        
        //сверяем код набора объектов
        $objects_code = Request::GetString('objects_code',METHOD_GET);
        //читаем объекты для данного пользователя
        $bsn_url = "https://www.bsn.ru/";
        $estate_types = array('live'=>30,'commercial'=>30,'country'=>30,'build'=>60);
        $user_objects = [];
        $letters = [];
        foreach($estate_types as $table=>$days) {
            $sql = "SELECT GROUP_CONCAT(CONCAT(?,'".$table."/',IF(".$sys_tables[$table].".rent = 1,'rent','sell'),'/',".$sys_tables[$table].".id,'/')) AS urls,
                          ".$sys_tables[$table].".status,
                          IF(".$sys_tables[$table].".raising_days_left != 0,
                             IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables[$table].".status,'+1'),'1'),
                             ".$sys_tables[$table].".status
                          ) AS status_full,
                          LOWER(IF(".$sys_tables[$table].".raising_days_left != 0,
                             IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables['objects_statuses'].".title,'+Поднятие'),'Поднятие'),
                             ".$sys_tables['objects_statuses'].".title
                          )) AS status_title,
                          IF(".$sys_tables[$table].".raising_days_left != 0,
                              IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables['objects_statuses'].".alias,'+raising'),'raising'),
                              ".$sys_tables['objects_statuses'].".alias
                           ) AS status_alias,
                          id_user,
                          (SUM(IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,1,0)*".$sys_tables['objects_statuses'].".cost) + 
                           SUM(IF(".$sys_tables[$table].".raising_days_left != 0,1,0)*150)) AS full_cost,
                          SUM(IF(".$sys_tables[$table].".raising_days_left != 0,1,0)*150) AS raising_cost,
                          ".$sys_tables['users'].".id AS user_id,
                          ".$sys_tables['users'].".`datetime`,
                          ".$sys_tables['users'].".id_agency,
                          ".$sys_tables['users'].".name AS user_name,
                          ".$sys_tables['users'].".lastname AS user_lastname,
                          ".$sys_tables['users'].".balance,
                          ".$sys_tables['users'].".email AS user_email,
                          (".$sys_tables['users'].".agency_admin = 1) AS is_admin_user
                   FROM ".$sys_tables[$table]."
                   LEFT JOIN ".$sys_tables['objects_statuses']." ON ".$sys_tables[$table].".status = ".$sys_tables['objects_statuses'].".id
                   LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables[$table].".id_user = ".$sys_tables['users'].".id
                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                   WHERE ".$sys_tables[$table].".id_user = ? AND 
                         ( (DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2) OR 
                           (".$sys_tables[$table].".raising_datetime != '0000-00-00' AND DATEDIFF(NOW(),".$sys_tables[$table].".raising_datetime) > 1 AND ".$sys_tables[$table].".raising_days_left = 1)
                         ) AND ".$sys_tables[$table].".published = 1 
                   GROUP BY CONCAT(id_user,IF(".$sys_tables[$table].".raising_days_left != 0,
                             IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables[$table].".status,'+1'),'1'),
                             ".$sys_tables[$table].".status
                          ))
                   ORDER BY ".$sys_tables[$table].".status ASC ";
            $list = $db->fetchall($sql,false,$bsn_url,$user_info['id']);
            if(empty($list)) continue;
            
            //группируем
            foreach($list as $key=>$item){
                $item['objects_info'] = array($item['status_full'] => $item['urls']);
                $item['statuses'] = array($item['status_full'] => $item['status_title']);
                $item['cost'] = array($item['status_full'] => $item['full_cost']);
                if(empty($user_objects)){
                    $item['status_alias'] = array($item['status_full'] => $item['status_alias']);
                    $user_objects = $item;
                } 
                else{
                    if(empty($user_objects['objects_info'][$item['status_full']])) $user_objects['objects_info'][$item['status_full']] = $item['urls'];
                    else $user_objects['objects_info'][$item['status_full']] .= ",".$item['urls'];
                    $user_objects['statuses'][$item['status_full']] = $item['status_title'];
                    $user_objects['status_alias'][$item['status_full']] = $item['status_alias'];
                    $user_objects['cost'][$item['status_full']] = $item['full_cost'];
                    $user_objects["full_cost"] += $item['full_cost'];
                    if(empty($user_objects['raising_cost'])) $user_objects['raising_cost'] = $item['raising_cost'];
                }
            }
        }
        //echo json_encode($user_objects);die();
        //разбиваем двойные статусы
        $user_objects['cost'][1] = (!empty($user_objects['raising_cost'])?$user_objects['raising_cost']:0);
        if(empty($user_objects['objects_info'])){
            $this->http_code = 404;
            break;
        }
        foreach($user_objects['objects_info'] as $status=>$urls){
            if(strstr($status,'+')){
                $statuses = explode('+',$status);
                $status_titles = explode('+',$user_objects['statuses'][$status]);
                $status_alias = explode('+',$user_objects['status_alias'][$status]);
                foreach($statuses as $key=>$status_item){
                    if(empty($user_objects['objects_info'][$status_item])){
                        $user_objects['objects_info'][$status_item] = $user_objects['objects_info'][$status];
                        $user_objects['statuses'][$status_item] = $status_titles[$key];
                        $user_objects['status_alias'][$status_item] = $status_alias[$key];
                        if($status_item != 1) $user_objects['cost'][$status_item] = $user_objects['cost'][$status] - $user_objects['raising_cost'];
                    }else{
                        $user_objects['objects_info'][$status_item] .= ",".$user_objects['objects_info'][$status];
                    }
                    
                }
                unset($user_objects['objects_info'][$status]);
                unset($user_objects['statuses'][$status]);
                unset($user_objects['cost'][$status]);
                unset($user_objects['status_alias'][$status]);
            }
        }
        
        foreach($user_objects['objects_info'] as $status => $urls){
            $user_objects['objects_info'][$status] = explode(',',$user_objects['objects_info'][$status]);
            sort($user_objects['objects_info'][$status]);
        } 
        unset($user_objects['url']);
        unset($user_objects['status']);
        $readed_objects_code = sha1(sha1(json_encode($user_objects['objects_info']).date("dmY")));
        if(empty($user_objects) || $readed_objects_code != $objects_code){
            $this->http_code = 404;
            break;
        }
        
        $discount = 30;//скидка в процентах
        Response::SetInteger('discount',$discount);
        $discount = round($user_objects['full_cost']*$discount/100.0);
        $full_cost = $user_objects['full_cost'] - $discount;
        Response::SetInteger('full_cost_w_discount',$full_cost);
        
        //создаем ссылку для оплаты или пополнения
        if($user_objects['balance'] > $full_cost){
            $objects_pay_code = sha1(sha1($user_objects['full_cost'].$objects_code.date("dmY")));
            $user_pay_code = sha1(sha1($user_info['id'].$user_email.$user_info['datetime'].date("dmY")));
            Response::SetString('pay_url',"http://".Host::$host."/estate_prolongate/pay/?objects_code=".$objects_pay_code."&user_code=".$user_pay_code."&mail=".$user_email);
        }else Response::SetString('balance_url',"http://".Host::$host."/members/pay/balance/");
        
        
        //готовим данные - составляем адреса для объектов:
        foreach($user_objects['objects_info'] as $status=>$urls){
            foreach($urls as $key=>$object_link){
                preg_match('/[0-9]+(?=\/$)/si',$object_link,$object_id);
                if(empty($object_id) || empty($object_id[0])) continue;
                else $object_id = array_pop($object_id);
                
                preg_match('/(?<=estate\/)[A-z]+(?=\/)/si',$object_link,$object_estate_type);
                if(empty($object_estate_type) || empty($object_estate_type[0])) continue;
                else $object_estate_type = array_pop($object_estate_type);
                
                switch($object_estate_type){
                    case 'build': $estateList = new EstateListBuild();$estateItem = new EstateItemBuild($object_id);break;
                    case 'live': $estateList = new EstateListLive();$estateItem = new EstateItemLive($object_id);break;
                    case 'commercial': $estateList = new EstateListCommercial();$estateItem = new EstateItemCommercial($object_id);break;
                    case 'country': $estateList = new EstateListCountry();$estateItem = new EstateItemCountry($object_id);break;
                }
                $address = $estateItem->getAddress($object_id);unset($estateItem);
                if(empty($estateList)){
                    $this->http_code = 404;
                    break;
                }
                $item_info = $estateList->Search($sys_tables[$object_estate_type].".id = ".$object_id);
                $item_info = $item_info[0];
                
                switch($object_estate_type){
                    case 'build':
                    case 'live': $tags_data = (!empty($item_info['type_object'])?$item_info['type_object']:"").
                                              (!empty($item_info['square_full'])?", ".$item_info['square_full']." м<sup>2</sup>":"").
                                              (!empty($item_info['level'])?", ".$item_info['level']." эт.":"");break;
                    case 'commercial': $tags_data = (!empty($item_info['type_object'])?$item_info['type_object']:"").
                                              (!empty($item_info['square_full'])?", ".$item_info['square_full']." м<sup>2</sup>":
                                                (!empty($item_info['square_ground'])?", ".$item_info['square_ground']." сот.":"")
                                              );break;
                    case 'country': $tags_data = (!empty($item_info['type_object'])?$item_info['type_object']:"").
                                              (!empty($item_info['square_full'])?", ".$item_info['square_full']." м<sup>2</sup>":
                                               (!empty($item_info['square_ground'])?", ".$item_info['square_ground']." сот.":"")
                                              );break;
                }
                
                $user_objects['objects_info'][$status][$key] = array('id'=>$object_id,'link'=>$object_link,'tags_data'=>$tags_data,'address'=>$address);
            }
        }
        
        Response::SetArray('user_info',$user_objects);
        
        //шаблон
        Page::setPageTemplate('templates/client_clear_on_gray.html');
        //Page::setPageTemplate('templates/clearcontent_w_head.html');
        $GLOBALS['css_set'][] = '/modules/members/prolongate.css';
        $module_template = "prolongate_objects.html";
        break;
    case $action == 'estate_prolongate' && !empty($this_page->page_parameters[0]) && $this_page->page_parameters[0] == 'pay':
        $objects_code = Request::GetString('objects_code',METHOD_GET);
        $user_code = Request::GetString('user_code',METHOD_GET);
        $user_email = Request::GetString('mail',METHOD_GET);
        
        //сверяем код пользователя:
        $user_info = $db->fetch("SELECT id,`datetime`,email FROM ".$sys_tables['users']." WHERE email = ?",$user_email);
        $user_pay_code = sha1(sha1($user_info['id'].$user_info['email'].$user_info['datetime'].date("dmY")));
        if($user_pay_code != $user_code){
            $this->http_code = 404;
            break;
        }
        
        //сверяем код объектов
        //читаем объекты для данного пользователя
        $bsn_url = "https://www.bsn.ru/";
        $estate_types = array('live'=>30,'commercial'=>30,'country'=>30,'build'=>60);
        $user_objects = [];
        $letters = [];
        foreach($estate_types as $table=>$days) {
            $sql = "SELECT GROUP_CONCAT(CONCAT(?,'".$table."/',IF(".$sys_tables[$table].".rent = 1,'rent','sell'),'/',".$sys_tables[$table].".id,'/')) AS urls,
                           ".$sys_tables[$table].".status,
                           IF(".$sys_tables[$table].".raising_days_left != 0,
                              IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables[$table].".status,'+1'),'1'),
                              ".$sys_tables[$table].".status
                           ) AS status_full,
                           IF(".$sys_tables[$table].".raising_days_left != 0,
                              IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables['objects_statuses'].".title,'+Поднятие'),'Поднятие'),
                              ".$sys_tables['objects_statuses'].".title
                           ) AS status_title,
                           IF(".$sys_tables[$table].".raising_days_left != 0,
                              IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables['objects_statuses'].".alias,'+raising'),'raising'),
                              ".$sys_tables['objects_statuses'].".alias
                           ) AS status_alias,
                           id_user,
                           (SUM(IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,1,0)*".$sys_tables['objects_statuses'].".cost) + 
                            SUM(IF(".$sys_tables[$table].".raising_days_left != 0,1,0)*150)) AS full_cost,
                            SUM(IF(".$sys_tables[$table].".raising_days_left != 0,1,0)*150) AS raising_cost,
                           ".$sys_tables['users'].".id AS user_id,
                           ".$sys_tables['users'].".`datetime`,
                           ".$sys_tables['users'].".id_agency,
                           ".$sys_tables['users'].".name AS user_name,
                           ".$sys_tables['users'].".lastname AS user_lastname,
                           ".$sys_tables['users'].".balance,
                           ".$sys_tables['users'].".email AS user_email,
                           (".$sys_tables['users'].".agency_admin = 1) AS is_admin_user
                    FROM ".$sys_tables[$table]."
                    LEFT JOIN ".$sys_tables['objects_statuses']." ON ".$sys_tables[$table].".status = ".$sys_tables['objects_statuses'].".id
                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables[$table].".id_user = ".$sys_tables['users'].".id
                    LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                    WHERE ".$sys_tables[$table].".id_user = ? AND 
                          ( (DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2) OR 
                            (".$sys_tables[$table].".raising_datetime != '0000-00-00' AND DATEDIFF(NOW(),".$sys_tables[$table].".raising_datetime) > 1 AND ".$sys_tables[$table].".raising_days_left = 1)
                          ) AND ".$sys_tables[$table].".published = 1 
                    GROUP BY CONCAT(id_user,IF(".$sys_tables[$table].".raising_days_left != 0,
                             IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables[$table].".status,'+1'),'1'),
                             ".$sys_tables[$table].".status
                          ))
                    ORDER BY ".$sys_tables[$table].".status ASC ";
            $list = $db->fetchall($sql,false,$bsn_url,$user_info['id']);
            if(empty($list)) continue;
            //группируем
            foreach($list as $key=>$item){
                $item['objects_info'] = array($item['status_full'] => $item['urls']);
                if(empty($user_objects)){
                    $item['status_alias'] = array($item['status_full'] => $item['status_alias']);
                    $user_objects = $item;
                } 
                else{
                    if(empty($user_objects['objects_info'][$item['status_full']])) $user_objects['objects_info'][$item['status_full']] = $item['urls'];
                    else $user_objects['objects_info'][$item['status_full']] .= ",".$item['urls'];
                    $user_objects["full_cost"] += $item['full_cost'];
                    $user_objects['status_alias'][$item['status_full']] = $item['status_alias'];
                }
                $user_objects['statuses'][$item['status_full']] = $item['status_title'];
                $user_objects['cost'][$item['status_full']] = $item['full_cost'];
            }
        }
        
        //разбиваем двойные статусы
        $user_objects['cost'][1] = $user_objects['raising_cost'];
        foreach($user_objects['objects_info'] as $status=>$urls){
            if(strstr($status,'+')){
                $statuses = explode('+',$status);
                $status_titles = explode('+',$user_objects['statuses'][$status]);
                $status_alias = explode('+',$user_objects['status_alias'][$status]);
                foreach($statuses as $key=>$status_item){
                    if(empty($user_objects['objects_info'][$status_item])){
                        $user_objects['objects_info'][$status_item] = $user_objects['objects_info'][$status];
                        $user_objects['statuses'][$status_item] = $status_titles[$key];
                        $user_objects['status_alias'][$status_item] = $status_alias[$key];
                        if($status_item != 1) $user_objects['cost'][$status_item] = $user_objects['cost'][$status] - $user_objects['raising_cost'];
                    }else{
                        $user_objects['objects_info'][$status_item] .= ",".$user_objects['objects_info'][$status];
                        if($status_item != 1) $user_objects['cost'][$status_item] = $user_objects['cost'][$status] - $user_objects['raising_cost'];
                    }
                    
                }
                unset($user_objects['objects_info'][$status]);
                unset($user_objects['statuses'][$status]);
                unset($user_objects['cost'][$status]);
                unset($user_objects['status_alias'][$status]);
            }
        }
        
        foreach($user_objects['objects_info'] as $status => $urls){
            $user_objects['objects_info'][$status] = explode(',',$user_objects['objects_info'][$status]);
            sort($user_objects['objects_info'][$status]);
        } 
        unset($user_objects['url']);
        unset($user_objects['status']);
        $readed_objects_code = sha1(sha1(json_encode($user_objects['objects_info']).date("dmY")));
        $objects_pay_code = sha1(sha1($user_objects['full_cost'].$readed_objects_code.date("dmY")));
        
        if(empty($user_objects) || $objects_pay_code != $objects_code){
            $this->http_code = 404;
            break;
        }
        
        $discount = 30;//скидка в процентах
        
        //если все проверки прошли, оплачиваем объекты
        foreach($user_objects['objects_info'] as $status=>$objects){
            //если к объекту применено несколько услуг(н-р Промо+Поднятие), будем по очереди применять все
            
            $status_aliases = (preg_match('/\+/si',$user_objects['status_alias'][$status]) ? explode('+',$user_objects['status_alias'][$status]) : array($user_objects['status_alias'][$status]));
            $statuses_list = (preg_match('/\+/si',$status) ? explode('+',$status) : array($status));
            $res = true;
            foreach($statuses_list as $key=>$status){
                switch(true){
                    //перебираем объекты, обновляем даты окончания услуг
                    case $status == 2:
                        foreach($objects as $k=>$object_link){
                            preg_match('/[0-9]+(?=\/$)/si',$object_link,$object_id);
                            if(empty($object_id) || empty($object_id[0])) continue;
                            else $object_id = array_pop($object_id);
                            
                            preg_match('/(?<=estate\/)[A-z]+(?=\/)/si',$object_link,$object_estate_type);
                            if(empty($object_estate_type) || empty($object_estate_type[0])) continue;
                            else $object_estate_type = array_pop($object_estate_type);
                            
                            $res *= $db->query("UPDATE ".$sys_tables[$object_estate_type]." SET date_change = NOW() WHERE id = ?",$object_id);
                            
                        }
                        break;
                    case $status == 1:
                        foreach($objects as $k=>$object_link){
                            preg_match('/[0-9]+(?=\/$)/si',$object_link,$object_id);
                            if(empty($object_id) || empty($object_id[0])) continue;
                            else $object_id = array_pop($object_id);
                            
                            preg_match('/(?<=estate\/)[A-z]+(?=\/)/si',$object_link,$object_estate_type);
                            if(empty($object_estate_type) || empty($object_estate_type[0])) continue;
                            else $object_estate_type = array_pop($object_estate_type);
                            
                            
                            $res *= $db->query("UPDATE ".$sys_tables[$object_estate_type]." SET raising_datetime = DATE_ADD(NOW(),INTERVAL 1 DAY), raising_days_left = 5, raising_status = 1,date_change = NOW() WHERE id = ?",$object_id);
                            $res *= $db->query("INSERT INTO ".$sys_tables['users_finances']." (`datetime`,id_user,obj_type,estate_type,id_parent,expenditure,income,paygate,id_initiator,action_source)
                                                VALUES (NOW(),?,?,?,?,?,0,1,?,3)",$user_info['id'],$status_aliases[$key],$object_estate_type,$object_id,round($user_objects['cost'][$status] *(1-$discount/100.0)),$user_info['id']);
                            
                        }
                        break;
                    case in_array($status,array(3,4,5,6)):
                        foreach($objects as $k=>$object_link){
                            preg_match('/[0-9]+(?=\/$)/si',$object_link,$object_id);
                            if(empty($object_id) || empty($object_id[0])) continue;
                            else $object_id = array_pop($object_id);
                            
                            preg_match('/(?<=estate\/)[A-z]+(?=\/)/si',$object_link,$object_estate_type);
                            if(empty($object_estate_type) || empty($object_estate_type[0])) continue;
                            else $object_estate_type = array_pop($object_estate_type);
                            
                            $interval = ($status == 6?"1 WEEK":"1 MONTH");
                            
                            $res *= $db->query("UPDATE ".$sys_tables[$object_estate_type]." SET status_date_end = DATE_ADD(status_date_end, INTERVAL ".$interval."), date_change = NOW() WHERE id = ?",$object_id);
                            $res *= $db->query("INSERT INTO ".$sys_tables['users_finances']." (`datetime`,id_user,obj_type,estate_type,id_parent,expenditure,income,paygate,id_initiator,action_source)
                                                VALUES (NOW(),?,?,?,?,?,0,1,?,3)",$user_info['id'],$status_aliases[$key],$object_estate_type,$object_id,round($user_objects['cost'][$status]/count($objects)*(1-$discount/100.0)),$user_info['id']);
                            
                        }
                        break;
                }
            }
            
            if(empty($res)){
                $error = "Заполните все обязательные поля";
            }
        }
        
        $expenditure = $user_objects['full_cost'] - round($user_objects['full_cost']*$discount/100.0);
        
        //отдельно обновляем баланс пользователя
        $res *= $db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?",$expenditure,$user_info['id']);
        
        Response::SetInteger('expenditures',$expenditure);
        Response::SetString('home_link',"http://".Host::$host);
        Response::SetBoolean('success',$res);
        
        //шаблон
        Page::setPageTemplate('templates/client_clear_on_gray.html');
        $GLOBALS['css_set'][] = '/modules/objects/prolongate.css';
        $module_template = "prolongate_objects_result.html";
        
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////
   // форма авторизации - редирект на личный кабинет
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case empty($action):
        //юристов перенаправляем в консультации
        if($auth->user_activity == 2) Host::Redirect('/members/conversions/consults/');
        else Host::Redirect('/members/objects/list/');
        break;
    case $action == 'subways_list':
        // список улиц для автокомплита
        $geo_id = Request::GetInteger('geo_id', METHOD_POST);
        if($geo_id==0) $ajax_result['ok'] = false;
        else {
            $info = $db->fetch("SELECT `aoguid` FROM ".$sys_tables['geodata']."
                                WHERE id=?", $geo_id);
            $search_str = Request::GetString('search_string', METHOD_POST);
            $list = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']." WHERE parentguid=? AND a_level=5 AND offname LIKE ? ORDER BY offname LIMIT ?", false, $info['aoguid'], "%".$search_str."%", 10);
            $ajax_result['ok'] = true;
            $ajax_result['list'] = $list;
        }
        break;
    case $action == 'cab':
    case $action == 'cabinet':
        if($auth->user_activity == 2) Host::Redirect('/members/conversions/consults/');
        else Host::Redirect('/members/objects/list/');
        break;
    case 'estate'    :
        Host::Redirect( '/' . str_replace( '/estate/', '/objects/', $this_page->requested_url ) . '/' );
        break;
    default:
        $this_page->http_code=404;
        break;
}
function getFreeLeft($status){
    global $auth;
    global $agency_limit;
    $is_agency = $auth->id_agency>0 && $auth->agency_admin == 1 && !empty($auth->agency_id_tarif);
    switch(true){
        case (!empty($auth->id_tarif) && $status==3): $free_left = $auth->promo_left;break;
        case (!empty($auth->id_tarif) && $status==4): $free_left = $auth->premium_left;break;
        case (!empty($auth->id_tarif) && $status==6): $free_left = $auth->vip_left;break;
        case ($is_agency && $status==3): $free_left = $auth->agency_promo - $agency_limit['promo'];break;
        case ($is_agency && $status==4): $free_left = $auth->agency_premium - $agency_limit['premium'];break;
        case ($is_agency && $status==6): $free_left = $auth->agency_vip - $agency_limit['vip'];break;
        default: $free_left = 0;
    }
    return ($free_left<0?0:$free_left);
}

/**
* собираем список объектов для оплаты
* 
* @param mixed $affected_objects
* @param mixed $status
* @param mixed $free_left
* @param mixed $object_cost_statuses
* @param mixed $sum_only - флаг что на выходе нужны только данные по сумме
*/
function combineBuyList($affected_objects,$status,$agency_object_long,$free_left,$object_cost_statuses,$sum_only=false){
    global $db;
    global $auth;
    $sys_tables = Config::$values['sys_tables'];
    $objects_list = [];
    $objects_links = [];
    $total_sum = 0;
    $total_objects = 0;
    $free_objects = 0;
    $affected_ids = [];
    
    //поднятие на 5 дней
    if($status == 1 && $agency_object_long == 5) $object_cost_statuses[$status]['cost'] = $object_cost_statuses[$status]['cost']*4;
    
    foreach($affected_objects as $key=>$item){
        if(empty($item)) continue;
        //убираем все лишнее
        $item = preg_replace('/[^0-9\,]/','',$item);
        $item = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$item),',');
        $affected_ids[] = $item;
        if($key == 'build')
            $objects_list[$key] = $db->fetchall("SELECT ".$sys_tables[$key].".*,'flats' AS type_object FROM ".$sys_tables[$key]."
                                                 WHERE ".$sys_tables[$key].".id IN (".$item.") AND id_user = ".$auth->id);
        else
            $objects_list[$key] = $db->fetchall("SELECT ".$sys_tables[$key].".*,".$sys_tables['type_objects_'.$key].".new_alias AS type_object FROM ".$sys_tables[$key]."
                                                 LEFT JOIN ".$sys_tables['type_objects_'.$key]." ON ".$sys_tables[$key].".id_type_object = ".$sys_tables['type_objects_'.$key].".id
                                                 WHERE ".$sys_tables[$key].".id IN (".$item.") AND id_user = ".$auth->id);
        
        $type_amount = count($objects_list[$key]);
        
        //ссылки на страницы с объявлениями (для письма)
        foreach($objects_list[$key] as $k=>$i){
            //если делаем промо/премиум/вип/акцию, и при этом объект уже промо/премиум/вип/акция, просто убираем
            if($status > 2 && in_array($i['status'],array(3,4,6,7))){
                unset($objects_list[$key][$k]);
                --$type_amount;
            }
            else $objects_links[$key][] = Host::$host."/".$key."/".(($i['rent'] == 1)?"rent":"sell")."/".$i['id'].'/';
        }
        
        
        $total_objects += $type_amount;
        $free_objects += min($free_left,$type_amount);
        if(!empty($auth->id_tarif)){
            
            if($free_left>$type_amount) $free_left -= $type_amount;
            else{
                $total_sum += $object_cost_statuses[$status]['cost']*($type_amount - $free_left);
                $free_left = 0;
            } 
        } elseif ($auth->id_agency>0 && $auth->agency_admin == 1 && !empty($auth->agency_id_tarif)){ 
            if($free_left>$type_amount) $free_left -= $type_amount;
            else{
                $total_sum += $object_cost_statuses[$status]['cost']*($type_amount - $free_left);
                $free_left = 0;
            } 
        } else $total_sum += $object_cost_statuses[$status]['cost']*$type_amount;
    }
    if($sum_only) return array('total_sum'=>$total_sum,'total_objects'=>$total_objects,'free_objects'=>$free_objects);
    else return array('objects_list'=>$objects_list,'objects_links'=>$objects_links,'affected_ids'=>$affected_ids,'total_sum'=>$total_sum,'total_objects'=>$total_objects,'free_objects'=>$free_objects);
}
function sum_index($array){
    
}

?>