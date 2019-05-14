<?php
require_once('includes/class.paginator.php');
require_once('includes/class.email.php');
require_once('includes/pseudo_form/pseudo_form.php');

//выбирается параметр 1, так как есть редирект
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
//записей на страницу
$strings_per_page = Config::Get('view_settings/strings_per_page');

$GLOBALS['css_set'][] = '/css/content.css';
$GLOBALS['css_set'][] = '/modules/webinars/styles.css';
$GLOBALS['css_set'][] = '/css/form.css';

Response::SetString('img_folder',Config::$values['img_folders']['webinars']);
switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // показ комментариев (ajax-вызов)
    ////////////////////////////////////////////////////////////////////////////////////////////////////////                  
    case $action == 'send_message':
        if($ajax_mode){            
            //проверяем на коррректность введенные значения
            $text="";
            $fio = trim(Request::GetString('fio', METHOD_POST));
            $email = Request::GetString('user_email', METHOD_POST);
            $id = Request::GetInteger('id', METHOD_POST);
            if(empty($id)) break;
            $item = $db->fetch("SELECT *, 
                                    DATE_FORMAT (`datetime`, '%d.%m.%y') as `date_w`,
                                    DATE_FORMAT (`datetime`, '%k:%i') as `time_w`,
                                    TIMESTAMPDIFF(DAY,NOW(),`datetime`) AS days_left
                                FROM ".$sys_tables['webinars']." WHERE id = ?", $id);
            if(empty($item)) break;
            
            if(!empty($email) && !empty($fio)) $user = $db->fetch("SELECT TRIM(CONCAT(".$sys_tables['users'].".name, ' ', ".$sys_tables['users'].".lastname)) as user_name, ".$sys_tables['users'].".*, ".$sys_tables['users_photos'].".`name` as `photo`, LEFT (".$sys_tables['users_photos'].".`name`,2) as `subfolder` FROM ".$sys_tables['users']." LEFT JOIN ".$sys_tables['users_photos']." ON ".$sys_tables['users_photos'].".id_parent = ".$sys_tables['users'].".id WHERE ".$sys_tables['users'].".email = ?", $email);
            elseif(!empty($auth->id)) $user = $db->fetch("SELECT TRIM(CONCAT(".$sys_tables['users'].".name, ' ', ".$sys_tables['users'].".lastname)) as user_name, ".$sys_tables['users'].".*, ".$sys_tables['users_photos'].".`name` as `photo`, LEFT (".$sys_tables['users_photos'].".`name`,2) as `subfolder` FROM ".$sys_tables['users']." LEFT JOIN ".$sys_tables['users_photos']." ON ".$sys_tables['users_photos'].".id_parent = ".$sys_tables['users'].".id WHERE ".$sys_tables['users'].".id = ?", $auth->id);
            else break;
            //запись пользователя
            if(empty($user) && empty($auth->id) && !empty($email) && !empty($fio)){                                                // если пользователь с указанным имейлом или логином не зарегистрирован
                $password = randomstring(6);                                  // генерация пароля по умолчанию
                $hash_password = sha1(sha1($password));                       // вычисление хэша пароля
                $res = $db->query("INSERT INTO ".$sys_tables['users']."
                        (email,name,passwd,datetime,access)
                       VALUES
                        (?,?,?,NOW(),'')"
                       , $email
                       , $fio
                       , $hash_password);
                 $id_user = $db->insert_id;
                 $item['user_name'] = $item['user_name'] = $fio;
                 $user = $item;
                 $item['password'] = $password;
                 $item['user_email'] = $email;
                 Response::SetArray('item', $item);
                 //письмо оповещение регистрации пользователя в нашей базе
                 $mailer = new EMailer('mail');
                 $eml_tpl = new Template('mail.registration.html', $this_page->module_path);
                 $html = $eml_tpl->Processing();
                 $html = iconv('UTF-8', $mailer->CharSet, $html);
                 $mailer->ClearAddresses();
                 $mailer->AddAddress($email);
                 $mailer->Subject = iconv('UTF-8', $mailer->CharSet,'Регистрация на сайте BSN.ru ');
                 $mailer->Body = $html;
                 $mailer->IsHTML(true);
                 $mailer->From = 'no-reply@bsn.ru';
                 $mailer->FromName = 'bsn.ru';
                 $item['link'] = 'https://go.myownconference.ru/ru/bsnru/'.urlencode($fio).'/'.md5($hash_password).'/';
                 if($mailer->Send()) $ajax_result['ok'] = true;
            } else {
                //обновление имен пользователя, если его нет
                if(empty($user['name']) && empty($user['lastname']) && !empty($fio)) $db->query("UPDATE ".$sys_tables['users']." SET name = ? WHERE id = ?", $fio, $user['id']);
                //проверка на уже зарегенного 
                $webinar_user = $db->fetch("SELECT * FROM ".$sys_tables['webinars_users']." WHERE id_user = ? AND id_parent = ?", $user['id'], $id);
                if(!empty($webinar_user)){
                    $ajax_result['text'] = 'Вы уже зарегистрированы на вебинар.';
                } else {
                    $email = $user['email'];
                    $fio = trim($user['name'].' '.$user['lastname']);
                    $password = $hash_password = $user['passwd'];
                    $id_user = $user['id']  ;
                    $item['user_email'] = $email;
                    $item['user_name'] = $fio;
                    $item['password'] = $password;
                    $item['link'] = 'https://go.myownconference.ru/ru/bsnru/'.urlencode($fio).'/'.md5($hash_password).'/';
                }
            }

            if(empty($webinar_user)){
                $ajax_result['user'] = $user;
                //записываем в БД вебинарских пользователей
                $res = $db->query("INSERT INTO ".$sys_tables['webinars_users']." 
                             (id_parent,id_user)
                             VALUES
                             (?,?)",
                             $item['id'],
                             $id_user);
                 if(!empty($res)){
                     //определяем, сколько дней осталось до вебинара
                     //если осталось меньше суток, ссылку на вебинар шлем сразу
                     if($item['days_left']<1){
                         Response::SetBoolean('late_registration',true);
                         $fio = trim($user['name'].' '.$user['lastname']);
                         $password = $hash_password = $user['passwd'];
                         $item['link'] = 'https://go.myownconference.ru/ru/bsnru/'.urlencode($fio).'/'.md5($hash_password).'/';
                     }
                     Response::SetArray('item', $item);
                     Response::SetString('mailer_title', 'Вебинары');
                     //письмо оповещение регистрации на вебинар
                     $mailer = new EMailer('mail');
                     $eml_tpl = new Template('mail.webinar.new.html', $this_page->module_path);
                     $html = $eml_tpl->Processing();
                     $html = iconv('UTF-8', $mailer->CharSet, $html);
                     $mailer->ClearAddresses();
                     $mailer->AddAddress($email);
                     $mailer->Subject = iconv('UTF-8', $mailer->CharSet,'Регистрация на вебинар '.$item['title']);
                     $mailer->Body = $html;
                     $mailer->IsHTML(true);
                     $mailer->From = 'no-reply@bsn.ru';
                     $mailer->FromName = 'bsn.ru';
                     if($mailer->Send()) $ajax_result['ok'] = true;
                 }                
            }

        }
        break;
    //###########################################################################
    // выводим список событий
    //###########################################################################
    case (empty($action)) && (empty($this_page->page_parameters[1])):
            
            $GLOBALS['js_set'][] = '/modules/webinars/mainpage.js';
            
            $sql = " SELECT ".$sys_tables['webinars'].".*,
                            DATE_FORMAT(".$sys_tables['webinars'].".datetime,'%e %M, %k:%i') AS e_date,
                            CONCAT(DATE_FORMAT(".$sys_tables['webinars'].".datetime,'%e'),' ',SUBSTRING(DATE_FORMAT(".$sys_tables['webinars'].".datetime,'%M'),1,3)) AS e_date_only,
                            DATE_FORMAT(".$sys_tables['webinars'].".datetime,'%k:%i') AS e_time_only,
                            IF(
                                YEAR(". $sys_tables['webinars'] .".`datetime`) < Year(CURDATE()),
                                    DATE_FORMAT(". $sys_tables['webinars'] .".`datetime`,'%e %M %Y'),
                                    DATE_FORMAT(". $sys_tables['webinars'] .".`datetime`,'%e %M, %k:%i')
                            ) as normal_date, 
                            
                            IF(webinars_users.amount IS NOT NULL,webinars_users.amount,0) AS webinars_users_amount,
                            IF(registration_limits > webinars_users.amount OR webinars_users.amount IS NULL,
                               registration_limits - IF(webinars_users.amount IS NOT NULL,webinars_users.amount,0),
                               0) AS places_left,
                            IF(comments.amount IS NOT NULL,comments.amount,0) AS comments_amount,
                            ".$sys_tables['webinars_photos'].".`name` as `photo`, 
                            LEFT (".$sys_tables['webinars_photos'].".`name`,2) as `subfolder`,
                            IF(`datetime`<NOW(),'1','0') AS finished
                     FROM ".$sys_tables['webinars']."
                     LEFT JOIN (SELECT COUNT(*) as amount, id_parent FROM ".$sys_tables['webinars_users']." GROUP BY id_parent) webinars_users ON webinars_users.id_parent = ".$sys_tables['webinars'].".id
                     LEFT JOIN (SELECT COUNT(*) as amount, id_parent FROM ".$sys_tables['comments']."  WHERE parent_type = 5 GROUP BY id_parent) comments ON comments.id_parent = ".$sys_tables['webinars'].".id
                     LEFT JOIN ".$sys_tables['webinars_photos']." ON ".$sys_tables['webinars_photos'].".id = ".$sys_tables['webinars'].".id_main_photo";
            
            //вебинар который идет сейчас
            $online_webinar = $db->fetch($sql . " WHERE (NOW() >= ".$sys_tables['webinars'].".`datetime` - INTERVAL 5 MINUTE AND NOW() < ".$sys_tables['webinars'].".`datetime` + INTERVAL 1 HOUR)");
            if(!empty($online_webinar)) Response::SetArray('online_webinar',$online_webinar);
            
            //будущие вебинары этого месяца
            $month_list = $db->fetchall($sql." WHERE `datetime` > NOW() AND status = 1
                                               ORDER BY `datetime` DESC");
            Response::SetArray('month_list',$month_list);
            
            //популярные вебинары (4)
            $popular_list = $db->fetchall($sql." ORDER BY views_count DESC,`datetime` DESC
                                                 LIMIT 3");
            Response::SetArray('popular_list',$popular_list);
            
            $page = Request::GetInteger('page',METHOD_GET);
            if(empty($page)) $page = 1;
            Response::SetString('page', ( $page + 1 ) );
            $where = " `datetime` < NOW() AND `status` = 2 ";
            $paginator = new Paginator($sys_tables['webinars'], 10, $where);

            //прошедшие вебинары
            $last_list = $db->fetchall($sql." WHERE " . $where . " ORDER BY `datetime` DESC LIMIT " . $paginator->getFromString($page) . ", 10");
            Response::SetArray('last_list', $last_list);
            
            $months = Config::Get('months_prepositional');
            Response::SetString('current_month_title',$months[date('n',time())]);
            
            //устанавливаем breadcrumbs и title
            Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Вебинары' : $this_page->page_seo_h1);
            $new_meta = array('title' =>'Вебинары', 'keywords' =>'Вебинары');
            $this_page->manageMetadata($new_meta, true);
            if($ajax_mode) {
                $ajax_result['ok'] = true;           
                Response::SetArray('list', $last_list );
                //показать еще  
                if($paginator->pages_count!=$page) Response::SetBoolean('ajax_pagination', true);
            }
            $module_template = empty($ajax_mode) ? 'mainpage.html' : 'block.html';
        break;
    //###########################################################################
    // карточка вебинара
    //###########################################################################
    case (!empty($action)&&(empty($this_page->page_parameters[2]))):

        $GLOBALS['js_set'][] = '/modules/webinars/item.js';
        $GLOBALS['css_set'][] = '/modules/webinars/styles.css';
        $GLOBALS['js_set'][] = '/js/video-player/script.js';
        $GLOBALS['css_set'][] = '/js/video-player/style.css';
        
        $item = $db->fetch("SELECT *,
                                DATE_FORMAT (`datetime`, '%d.%m.%y') as `date_w`,
                                DATE_FORMAT (`datetime`, '%k:%i') as `time_w`,
                                IF(NOW() >= `datetime` - INTERVAL 5 MINUTE,'true','false') as `begin`,
                                IF(NOW() >= `datetime` + INTERVAL 1 HOUR,'true','false') as `end`,
                                (SELECT COUNT(*) FROM ".$sys_tables['comments']." WHERE comments_active = 1 AND id_parent = ".$sys_tables['webinars'].".id) as comments_count
                            FROM ".$sys_tables['webinars']." WHERE url='".$db->real_escape_string($action)."'");
        if (empty($item)){
            $this_page->http_code = 404;
            break;
        }
        
        $db->query("UPDATE ".$sys_tables['webinars']." SET `views_count`=`views_count`+1 WHERE `id`=?",$item['id']);
        
        $today = new DateTime();  //сейчас
        $date_end = new DateTime(date('Y-m-d H:i:s', strtotime($item['datetime']))); //дата окончания показа
        Response::SetArray('date_interval',date_diff($date_end, $today));

        $item['text'] = (preg_replace('/\s{2,}/', ' ', $item['text']));
        $item['text'] = (preg_replace('#\<p\>(?-i:\s++|&nbsp;)*\<\/p\>#sui', ' ', $item['text']));
        
        Response::SetArray('item',$item);
        
        //список пользователей
        $users = $db->fetchall("SELECT ".$sys_tables['webinars_users'].".*,
                                       ".$sys_tables['users_photos'].".`name` as `photo`, 
                                       LEFT (".$sys_tables['users_photos'].".`name`,2) as `subfolder`,
                                       ".$sys_tables['users'].".name,
                                       ".$sys_tables['users'].".lastname
                                FROM ".$sys_tables['webinars_users']." 
                                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['webinars_users'].".id_user
                                LEFT JOIN ".$sys_tables['users_photos']." ON ".$sys_tables['users_photos'].".id_parent = ".$sys_tables['webinars_users'].".id_user
                                WHERE ".$sys_tables['webinars_users'].".id_parent = ?", false, $item['id']);
        Response::SetArray('users', $users);
        Response::SetInteger('users_left', $item['registration_limits'] - count($users));
        //поиск авторизованного пользователя в зарегистрировавшихся
        if(!empty($auth->id)){
            foreach($users as $k=>$user) if($user['id_user'] == $auth->id){
                Response::SetBoolean('registered', true);
                break;
            }
        }

        $post_parameters = Request::GetParameters(METHOD_POST);
        $module_template = 'item.html';
        
        //добавляем breadcrumbs и title
        Response::SetString('h1', empty($this_page->page_seo_h1) ? $item['title'] : $this_page->page_seo_h1);
        $this_page->addBreadcrumbs($item['title'],'forum');
        $new_meta = array('title' =>$item['title'], 'keywords' =>'недвижимость, санкт-петербурге, петербург, спб, продажа, аренда, питер');
        $this_page->manageMetadata($new_meta, true);
        
        //комментирование вебинара
        $GLOBALS['js_set'][] = '/modules/comments/script.js';
        $GLOBALS['css_set'][] = '/modules/comments/style.css';
        $comments_data = array('page_url'    =>  '/'.$this_page->real_url.'/',
                               'id_parent'   =>  $item['id'],
                               'parent_type' =>  'webinars'
        );
        Response::SetArray('comments_data', $comments_data);  
        break;
    default:
        $this_page->http_code = 404;
        break;
        
}
Response::SetBoolean('show_overlay', true);
?>