<?php
$GLOBALS['css_set'][] = '/modules/objects_subscriptions/style.css';
require_once('includes/class.estate.php');
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$sys_tables = Config::$sys_tables;
//не показывать верхний баннер
Response::SetBoolean('not_show_top_banner',true); 
switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Подтверждение подписки
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='confirm':
        $key = Request::GetString('key',METHOD_GET);
        $id = empty( $ajax_mode ) ? Request::GetString('id',METHOD_GET) :  Request::GetString('id',METHOD_POST);
        if( !empty($id) && ( !empty($key) || !empty( $ajax_mode ) ) ) {
            $item = empty( $ajax_mode ) ? 
                        $db->fetch("SELECT * FROM ".$sys_tables['objects_subscriptions']." WHERE `confirmed` = ? AND `id` = ? AND `confirm_key` = ?", 2, $id, $key ) :
                        $db->fetch("SELECT * FROM ".$sys_tables['objects_subscriptions']." WHERE `confirmed` = ? AND `id` = ? AND `id_user` = ?", 2, $id, $auth->id );
            //подтверждение подписки
            if(!empty($item)) {
                //обнуление кода проверка и простановка статуса
                $db->query("UPDATE ".$sys_tables['objects_subscriptions']." SET `confirm_key` = '', `confirmed` = ? WHERE `id` = ?", 1, $id);
                //редирект на список подписок для авторизованных
                if( $auth->authorized ) {
                    if( empty( $ajax_mode ) ) Host::Redirect('/objects_subscriptions/');
                    else {
                        $ajax_result['ok'] = true;
                        break;
                    }
                } else{ //для неавторизованных
                    //нет записи в пользователях с таким email - регистрация нового пользователя
                    if(empty($item['id_user'])){
                        require_once('includes/class.email.php');
                        $password = randomstring(6);                                  // генерация пароля по умолчанию
                        $hash_password = sha1(sha1($password));                       // вычисление хэша пароля
                        $res = $db->query("INSERT INTO ".$sys_tables['users']."
                                (email,name,passwd,datetime,access)
                               VALUES
                                (?,'',?,NOW(),'')"
                               , $item['email']
                               , $hash_password); echo $db->error;
                        $env = array(   
                            'host' => Host::$host,
                            'email' => $item['email'],
                            'password' => $password
                        );
                        $id_user = $db->insert_id;     // Получение id пользователя
                        //обновление ID пользователя для подписки
                        $db->query("UPDATE ".$sys_tables['objects_subscriptions']." SET id_user = ? WHERE id = ?", $id_user ,$id);
                        $mailer = new EMailer('mail');
                        // данные пользователя для шаблона
                        Response::SetArray( "data", array('email'=>$item['email'], 'password'=>$password) );
                        // данные окружения для шаблона
                        $env = array(
                            'url' => Host::GetWebPath('/'),
                            'host' => Host::$host,
                            'ip' => Host::getUserIp(),
                            'datetime' => date('d.m.Y H:i:s')
                        );
                        Response::SetArray('env', $env);
                        // инициализация шаблонизатора
                        $eml_tpl = new Template('registration_email.html', "/modules/members/");
                        $html = $eml_tpl->Processing();
                        $html = iconv('UTF-8', $mailer->CharSet, $html);
                        // параметры письма
                        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Регистрация на сайте '.Host::$host);
                        $mailer->Body = $html;
                        $mailer->AltBody = strip_tags($html);
                        $mailer->IsHTML(true);                          
                        $mailer->AddAddress($item['email'], iconv('UTF-8',$mailer->CharSet, ""));
                        $mailer->From = 'no-reply@bsn.ru';
                        $mailer->FromName = 'bsn.ru'; 
                        if (!$mailer->Send()) Response::SetString('error','Подписка подтвеждена, но произошел сбой в отправке письма на Ваш email о регистрации нового аккаунта');                          
                        Response::SetBoolean('success_registration',true);
                    } else { //есть уже email - просто спасибо
                        Response::SetBoolean('success_subscribe',true);       
                    }
                    
                }
            } else  Response::SetString('error','Проверочный код не найден');
        } else Response::SetString('error','Не хватает параметров для проверки');
        $module_template = 'confirm.html';
        break;
    break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Подписаться
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='subscribe' && count($this_page->page_parameters) == 1:
        $period = 1;                   
        $url = Request::GetString('url',METHOD_POST); // URL подписки
        EstateSubscriptions::Init($url);
        $title = Request::GetString('title',METHOD_POST); // Заголовок подписки
        if(EstateSubscriptions::checkSubscribeOpportunity() || !empty($title)){
            $estate_type = Request::GetString('estate_type',METHOD_POST);
            $deal_type = Request::GetString('deal_type',METHOD_POST);
            $email = Request::GetString('email',METHOD_POST);
            $ajax_result['ok'] = !empty($url) && !empty($title) && !empty($estate_type) && !empty($deal_type) && EstateSubscriptions::Create($title,$url,Config::$values['object_types'][$estate_type]['key'],$deal_type, $email);
            $ajax_result['title'] = $title;
        }
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Отписаться
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='unsubscribe' && count($this_page->page_parameters) == 1:
        $id = Request::GetInteger('id',METHOD_POST);
        $ajax_result['ok'] = EstateSubscriptions::Remove($id);
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Получение списка подписок пользователя
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case    ( empty($action) && count($this_page->page_parameters) == 0 ) || 
            ( !empty( $action ) && count($this_page->page_parameters) == 2 ):
          
        if(!$auth->authorized) {
            $this_page->http_code = 404;
            break;
        }
        $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        $GLOBALS['css_set'][] = '/css/style-cabinet.css';
        $GLOBALS['js_set'][] = '/modules/objects_subscriptions/subscriptions.js';
        $GLOBALS['js_set'][] = '/js/main.js';
        $GLOBALS['css_set'][] = '/css/common.css';
        require_once('includes/class.paginator.php');
        //управление данными таблиц
        $object_type = Request::GetString('type',METHOD_GET); // тип объекта для автоматического выбора вкладки с объектами указанных типов
        $list = [];
        $first = true;    // автоматический выбор первой вкладки, если не передан тип объекта в GET
        
        $full_list = EstateSubscriptions::getList();

        if(!empty($full_list)){
            $list = [];
            foreach($full_list as $ind => $item){
                $estate_type = false;
                foreach (Config::$values['object_types'] as $alias => $obj){   // получение алиаса типа недвижимости
                    if ($obj['key']==$item['estate_type']){
                        $estate_type = $alias;
                        break;
                    }
                }
                if (empty($list[$estate_type])) $list[$estate_type] = [];
                $url = parse_url($item['url']);
                if(!empty($url['query'])) {
                    $url = $url['query'];
                    parse_str($url, $params);
                } else {
                    $params = $url;
                }
                EstateSubscriptions::Init($item['url']);
                $item['description'] = EstateSubscriptions::getTitle(false, $params);
                
                $list[$estate_type][] = $item;
            }
        }
        $amounts = [];
        foreach (Config::$values['object_types'] as $alias => $obj){
            $amount = 0;
            if (!empty($list[$alias])) $amount += count($list[$alias]);
            if ($amount!=0) $amounts[$alias] = $amount;
        }
        Response::SetArray('object_types',Config::$values['object_types']);
        Response::SetArray('full_list', $list);
        Response::SetArray('amounts',$amounts);
        $h1 = empty($this_page->page_seo_h1) ? 'Личный кабинет' : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);  // заголовок
        $this_page->page_seo_title = empty($this_page->page_seo_title) ? $h1 : $this_page->page_seo_title;
        $list = EstateSubscriptions::getList();
        Response::SetArray('period_list',EstateSubscriptions::getPeriodList());
        Response::SetString('page','objects_subscriptions');
        $module_template = '/modules/objects_subscriptions/templates/list.html';
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Смена периодичности отправки обновлений
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='period_change' && count($this_page->page_parameters) == 1:                   
        $id = Request::GetInteger('id',METHOD_POST); // ID подписки
        $id_period = Request::GetInteger('id_period',METHOD_POST); // Периодичность (гранулярность) подписки
        $ajax_result['ok'] = $db->query("UPDATE ".$sys_tables['objects_subscriptions']." SET id_period = ? WHERE id = ?",$id_period, $id);
        $ajax_result['lq'] = '';
        break;
    default:
        $this_page->http_code=404;
        break;
}
     
?>
