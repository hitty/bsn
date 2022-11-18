<?php  
Request::GetInteger('estate_type',METHOD_GET);
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
switch(true){
    ///////////////////////////////////////////////////////////
    // Попап формы
    ///////////////////////////////////////////////////////////
    case $ajax_mode && $action == 'popup':
        $module_template = "popup.html";
        $parameters = Request::GetParameters(METHOD_GET);
        Response::SetArray('item', $parameters);
        $list = $db->fetchall("SELECT * FROM ".$sys_tables['abuses_categories']." ORDER BY position");
        Response::SetArray('list',$list);
        $ajax_result['ok'] = true;
        break;
    ///////////////////////////////////////////////////////////
    // Добавление жалобы
    ///////////////////////////////////////////////////////////
    case $ajax_mode && $action == 'add':
            $parameters = Request::GetParameters( METHOD_POST );
            $category_id = !empty( $parameters['category'] ) && Validate::isDigit($parameters['category']) ? $parameters['category'] : false;
            $object_id = !empty($parameters['id']) && Validate::isDigit($parameters['id']) ? $parameters['id'] : false;
            $estate_type = !empty($parameters['estate_type']) ? $parameters['estate_type'] : false;
            
            if( empty($category_id) || empty($object_id) || empty($estate_type) ) {
                $ajax_result['ok'] = false;
                break;
            }
            
            $item = $db->fetch("SELECT id_user, rent FROM ".$sys_tables[$estate_type]." WHERE id = ?",$object_id);
            $id_user = (!empty($item) && !empty($item['id_user']) ? $item['id_user'] : 0);
            
            $res = $db->querys("INSERT INTO ".$sys_tables['abuses']." 
                               SET id_category = ?, estate_type = ?, id_object = ?, id_user = ?, abuse_date = NOW()",
                               $category_id, $estate_type, $object_id, $id_user
            );     
            
            $abuse_id = $db->insert_id;
                
            // определяем email менеджера ответственного за агенство
            $manager = $db->fetch(" SELECT ".$sys_tables['managers'].".name, ".$sys_tables['managers'].".email AS email 
                                    FROM ".$sys_tables['users']."
                                    LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id 
                                    LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id 
                                    WHERE ".$sys_tables['users'].".id = ?",$id_user);
                                        
            // нет ответственного менеджера? -> ищем держурного менеджера
            if(empty($manager['email'])) $manager = $db->fetch("SELECT name, email FROM ".$sys_tables['managers']." WHERE duty = 1 AND bsn_manager = 1");

            // отправляем email менеджеру
            if(!empty($manager['email'])){
                require_once('includes/class.email.php');
                $mailer = new EMailer('mail');

                $abuse_title = $db->fetch("SELECT title FROM ".$sys_tables['abuses_categories']." WHERE id = ? ",$category_id);
                $abuse_title = (!empty($abuse_title) && !empty($abuse_title['title']) ? $abuse_title['title'] : false);
                $manager_name = preg_split("/\s/",$manager['name']);
                $manager_name = array_shift($manager_name);
                
                $data = array( 'env' => array('url' => Host::GetWebPath(),
                                              'title' => "Новая жалоба на ",
                                              'host' => Host::$host,
                                              'managerName' => $manager_name, 
                                              'id' => $abuse_id,
                                              'abuse' => $abuse_title,
                                              'object' => $object_id,
                                              'estate_type' => $estate_type));
                
                $mailer->sendEmail(Config::Get('emails/content_manager'),
                                   $data['env']['managerName'],
                                   "Новая жалоба на объект",
                                   "sent_mail.html",
                                   $this_page->module_path,
                                   $data);
                $ajax_result['name'] = (!empty($auth->name) ? $auth->name : false);
                $ajax_result['ok'] = true;

            }  else {
                // не найден менеджер
                $ajax_result['ok'] = true;
                $mailer = new EMailer('mail');
                $mailer->sendEmail("web@bsn.ru",
                                   "Миша",
                                   "Поступила жалоба, некому обработать".date('Y-m-d H:i:s'),
                                   "",
                                   '',
                                   false,"жалоба #".$abuse_id,false,true);
            }
            $module_template = "/modules/abuses/templates/popup.success.html";
            $ajax_result['ok'] = $res;
        break;
        
    case $action == 'block': //
            
            if(!$this_page->first_instance){
                //определение рынка недвижимости для ТГБ
                if(count($this_page->page_parameters)==1){
                    $list = $db->fetchall("SELECT * FROM ".$sys_tables['abuses_categories']." ORDER BY position");
                    Response::SetArray('list',$list);
                    $url = parse_url($this_page->real_url);
                    Response::SetString('url_query', htmlentities($url['query']));
                    $module_template = "block.html";
                } else $this_page->http_code=404;
            } else $this_page->http_code=404;
        break;
    //принимаем запрос с жалобой
    case $ajax_mode:
        $text = Request::GetString('text',METHOD_POST);
        if(empty($text)) $text = '';
        $parameters = Request::GetArray('values',METHOD_POST);
        $category_id = (!empty($parameters['category']) ? (Validate::isDigit($parameters['category']) ? $parameters['category'] : false) : "");
        
        //по URL дочитываем остальные параметры:
        //"http://new.bsn.int/live/sell/19071364/?status=visible"
        $url = Host::getRefererURL();
        $url = explode('/',str_replace(Host::getWebPath(),'',$url));
        $estate_type = (in_array($url[0],array('live','build','commercial','country')) ? $url[0] : false);
        $deal_type = (in_array($url[1],array('rent','sell')) ? ($url[1] == 'rent' ? 1 : 2) : false);
        $object_id = (Validate::isDigit($url[2]) ? $url[2] : false);
        
        if(!($category_id && $estate_type && $deal_type && $object_id)){
            $ajax_result['ok'] = false;
            break;
        }
        
        $id_user = $db->fetch("SELECT id_user FROM ".$sys_tables[$estate_type]." WHERE id = ?",$object_id);
        $id_user = (!empty($id_user) && !empty($id_user['id_user']) ? $id_user['id_user'] : 0);
        
        $res = $db->querys("INSERT INTO ".$sys_tables['abuses']." 
                           SET id_category = ?, estate_type = ?, id_object = ?, id_user = ?, abuse_date = NOW()",
                           $category_id, $estate_type, $object_id, $id_user
        );     
        
        $abuse_id = $db->insert_id;
            
        // определяем email менеджера ответственного за агенство
        $manager = $db->fetch(" SELECT ".$sys_tables['managers'].".name, ".$sys_tables['managers'].".email AS email 
                                FROM ".$sys_tables['users']."
                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id 
                                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id 
                                WHERE ".$sys_tables['users'].".id = ?",$id_user);
                                    
        // нет ответственного менеджера? -> ищем держурного менеджера
        if(empty($manager['email'])) $manager = $db->fetch("SELECT name, email FROM ".$sys_tables['managers']." WHERE duty = 1 AND bsn_manager = 1");

        // отправляем email менеджеру
        if(!empty($manager['email'])){
            require_once('includes/class.email.php');
            $mailer = new EMailer('mail');

            $abuse_title = $db->fetch("SELECT title FROM ".$sys_tables['abuses_categories']." WHERE id = ? ",$category_id);
            $abuse_title = (!empty($abuse_title) && !empty($abuse_title['title']) ? $abuse_title['title'] : false);
            $manager_name = preg_split("/\s/",$manager['name']);
            $manager_name = array_shift($manager_name);
            
            $data = array( 'env' => array('url' => Host::GetWebPath(),
                                          'title' => "Новая жалоба на ",
                                          'host' => Host::$host,
                                          'managerName' => $manager_name, 
                                          'id' => $abuse_id,
                                          'abuse' => $abuse_title,
                                          'object' => $object_id,
                                          'estate_type' => $estate_type));
            
            $mailer->sendEmail(Config::Get('emails/content_manager'),
                               $data['env']['managerName'],
                               "Новая жалоба на объект",
                               "sent_mail.html",
                               $this_page->module_path,
                               $data);
            $ajax_result['name'] = (!empty($auth->name) ? $auth->name : false);
            $ajax_result['ok'] = true;

        }  else {
            // не найден менеджер
            $ajax_result['ok'] = true;
            $mailer = new EMailer('mail');
            $mailer->sendEmail("web@bsn.ru",
                               "Миша",
                               "Поступила жалоба, некому обработать".date('Y-m-d H:i:s'),
                               "",
                               '',
                               false,"жалоба #".$abuse_id,false,true);
        }

        $ajax_result['ok'] = $res;
        
        break;
    default:
        $this_page->http_code=404;
        break;
}
?>