<?php
require_once('includes/pseudo_form/pseudo_form.php');
require_once('includes/class.comments.php');
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$user_ip =  Host::getUserIp();    
$moderator = false;
switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // список последних
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'block':
        $limit = 10;
        require_once('includes/class.housing_estates.php');
        $type = $this_page->page_parameters[1];
        Comments::Init($type, false);    
        //получение списка последних комментариев
        $comments_list = Comments::getLastComments(null, false, $sys_tables['comments'].".comments_datetime DESC", 'all', $sys_tables['comments'].".id", "0," . $limit );
        if(!empty($comments_list)){
            //получение 4-х последних
            $id_parent = $count = 0;
            $list = [];
            $housing_estates = new HousingEstates();
            if( !empty( $limit ) )  $comments_list = array_slice( $comments_list, 0, $limit );
            foreach($comments_list as $k=>$item){
                if($id_parent != $item['id_parent'] ){
                    $count++;
                    $id_parent = $item['id_parent'];
                    $housing_estate = $housing_estates->getItem($item['id_parent']);
                    if(!empty($housing_estate)){
                        $item = array_merge($housing_estate, $item);
                        //получение информации о ЖК
                        $list[] = $item;
                    }
                }
                
            }
            Response::SetArray( 'list', $list );
        }
        if( $ajax_mode ) $ajax_result['ok'] = true;
        $module_template = 'block.last.list.html';
        break;    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // голосованине
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='vote_for':
        $action = Request::GetString('action',METHOD_POST);
        $id_parent = Request::GetString('id_parent',METHOD_POST);
        //формирование html-вида комментариев
        if(!empty($action) && !empty($id_parent) && !empty($auth->id)){
            if($action == 'minus') {
                $vote_for = 0;
                $vote_against = 1;
            }  else {
                $vote_for = 1;
                $vote_against = 0;
            }
            $res = $db->query("INSERT INTO ".$sys_tables['comments_votings']." SET id_parent = ?, vote_for = ?, vote_against = ?, id_user = ?",
                               $id_parent, $vote_for, $vote_against, $auth->id);
            
            $ajax_result['ok'] = true;
        }
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // получение списка
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'list':
        $type = Request::GetString('type', 'METHOD_POST');
        $id_parent = Request::GetString('id_parent', 'METHOD_POST');
        Comments::Init($type, $id_parent);    
        $url = Request::GetString('url', 'METHOD_POST');
        //сортировка
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        Response::SetInteger('sortby', $sortby);
        $only_comments = Request::GetString('only_comments', 'METHOD_POST');
        Response::SetString('only_comments', $only_comments);
        
        switch($sortby){            
            case 4:
                $orderby = "all_votes ASC";
                break;
            case 3:
                $orderby = "all_votes DESC";
                break;
            case 1:
                $orderby = $sys_tables['comments'].".comments_datetime ASC";
                break;
            case 2:
            default:
                $orderby = $sys_tables['comments'].".comments_datetime DESC";
                break;
        }
        $ajax_result['o'] = $orderby;
        $ajax_result['ao'] = $sortby;
        //получение списка последних комментариев
        $comments_list = Comments::getLastComments(null, !$moderator, $orderby);      
        //формирование html-вида комментариев
        if(!empty($comments_list)){
            Response::SetArray('comments_list',$comments_list);
            if(!empty($auth)) Response::SetArray('auth',$auth);
            Response::SetInteger('total',count($comments_list));
        }
        $module_template = 'form.html';
        $ajax_result['ok'] = true;
        //все сообщения просмотрены
        if(!empty($auth->id)) {
            $unread_comments = Comments::getUserAnswers($auth->id, $id_parent, 2);
            if(!empty($unread_comments)){
                $ids = [];
                foreach($unread_comments as $k=>$item) $ids[] = $item['id'];
                Notifications::Init();
                Notifications::setRead('comments', implode(",", $ids));
            }
        }
        break;    
    case $action == 'add':
            $type = Request::GetString('type', 'METHOD_POST');
            $id_parent = Request::GetString('id_parent', 'METHOD_POST');
            Comments::Init($type, $id_parent);    
            $text = Request::GetString('text', METHOD_POST);
            if(empty($text)) $text = Request::GetString('comment_text', METHOD_POST);
            $author_email = Request::GetString('author_email', METHOD_POST);
            $author_name = Request::GetString('author_name', METHOD_POST);
            $id_comment_parent = Request::GetInteger('id_comment_parent', 'METHOD_POST');
            $id_comment_answer = Request::GetInteger('id_comment_answer', 'METHOD_POST');
            if(empty($author_name) && $auth->checkAuth()) $author_name = $auth->name;
            if(empty($author_email) && $auth->checkAuth()) $author_email = $auth->email;
            if(!empty($text) && !empty($author_name)) {
                $result = Comments::addComment($text, $author_name, Validate::isEmail($author_email) ? $author_email : false, false, $id_comment_parent, !empty($auth->id), $id_comment_answer);
                //Отправка уведомлений о новом комментарии менеджерам
                $id = $db->insert_id;
                Response::SetInteger('id', $id);
                $eml_tpl = new Template('/modules/comments/templates/mail.html');
                $mailer = new EMailer('mail');
                $html = $eml_tpl->Processing();
                $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                // параметры письма
                $mailer->Body = $html;
                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Новый комментарий на BSN.ru");
                $mailer->IsHTML(true);
                $mailer->AddAddress(Config::Get('emails/content_manager2'));
                $mailer->AddAddress(Config::Get('emails/manager'));
                $mailer->From = 'no-reply@bsn.ru';
                $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
                // попытка отправить
                $mailer->Send();
                //отправить уведомление пользователю о новом комментарии
                if(!empty($id_comment_parent) || !empty($id_comment_answer)){
                    $item = $db->fetch("SELECT 
                                            ".$sys_tables['comments'].".*,
                                            ".$sys_tables['users'].".id as id_user,
                                            ".$sys_tables['users'].".email,
                                            ".$sys_tables['users'].".name,
                                            ".$sys_tables['users'].".lastname,
                                            ".$sys_tables['comments_types'].".title as comments_types_title
                                        FROM ".$sys_tables['comments']."    
                                        RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['comments'].".id_user
                                        RIGHT JOIN ".$sys_tables['comments_types']." ON ".$sys_tables['comments_types'].".id = ".$sys_tables['comments'].".parent_type
                                        WHERE 
                                            ".$sys_tables['comments'].".id = ? AND
                                            ".$sys_tables['users'].".email != '' AND
                                            ".$sys_tables['comments_types'].".url = ?
                                            
                    ", !empty($id_comment_answer) ? $id_comment_answer : $id_comment_parent, $type);
                    if(!empty($item) && Validate::isEmail($item['email']) && !empty($auth->id) && $auth->id!=$item['id_user']){
                        list($item['title'], $item['link']) = Comments::getInfo($item);
                        Response::SetArray('item', $item);
                        Response::SetString('author_name', $author_name);
                        $eml_tpl = new Template('/modules/comments/templates/mail.user.html');
                        $mailer = new EMailer('mail');
                        $html = $eml_tpl->Processing();
                        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                        // параметры письма
                        $mailer->Body = $html;
                        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Новый ответ на ваш комментарий на BSN.ru");
                        $mailer->IsHTML(true);
                        $mailer->AddAddress($item['email']);
                        $mailer->From = 'no-reply@bsn.ru';
                        $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
                        $mailer->Send();
                    }
                }
                $ajax_result['ok'] = true;
                $ajax_result['text'] = strip_tags($text);
                $item = Comments::getComment($id);
                Response::SetArray('item', $item);
                $module_template = "comment.html";
                $feedback = Request::GetString('feedback', 'METHOD_POST');
                Response::SetBoolean('feedback', !empty($feedback));
            }
        break;
    default:
        $this_page->http_code=404;
        break;
}  