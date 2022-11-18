<?php
DEFINE('support_group_number',101);
DEFINE('support_email',"kya1982@gmail.com");
require_once('includes/class.messages.php');
/*
// Удалить рекваэры
require_once('includes/class.paginator.php');
require_once('includes/class.estate.php');
require_once('includes/class.estate.statistics.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
*/
//не показывать верхний баннер
Response::SetBoolean('not_show_top_banner',true);
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$sys_tables = Config::$sys_tables;

$GLOBALS['css_set'][] = '/modules/messages/style.css';
switch(true){
    //установка чекбокса
    case $action == 'send_message_change' && $ajax_mode && !empty($auth->id):
        $value = Request::GetInteger('value', METHOD_POST);
        if(!empty($value)) $db->querys("UPDATE ".$sys_tables['users']." SET message_send = ? WHERE id = ?", $value, $auth->id);
        echo '';
        break;
    //Страница Диалогов
    case $action=='' && count($this_page->page_parameters) == 0:
        $module_template = 'messages.html';
        $GLOBALS['js_set'][] = '/modules/messages/jquery.timeago.js';
        $GLOBALS['js_set'][] = '/modules/messages/jquery.timeago.ru.js';
        $GLOBALS['js_set'][] = '/modules/messages/messages.js';
        
        $messages = new Messages();
        $list = $messages->GetDialogs($auth->id);

        Response::SetString('h1','Личный кабинет');
        Response::SetString('h2','Сообщения');
        if( !empty( $list ) ) Response::SetArray( 'msg_list', $list );
        Response::SetString('host',Host::$host); 
        Response::SetString('support_group',support_group_number);
        Response::SetString('system_group',system_group_number);
        break;
    //добавление нового пустого сообщения:
        case $action == 'add' && !empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1]):
                $from = $auth->id;
                $to = $this_page->page_parameters[1];
                $item = $db->fetch("SELECT * FROM ".$sys_tables['messages']." WHERE id_parent = 0 AND ((id_user_from = ? AND id_user_to = ? ) OR (id_user_from = ? AND id_user_to = ? ))"
                    ,$from, $to, $to, $from
                );
                if(!empty($item)) {
                    $db->querys("UPDATE ".$sys_tables['messages']." SET datetime_create = NOW(), is_deleted_from = 2, is_deleted_to = 2 WHERE id = ?", $item['id'] );
                    Host::Redirect('/members/messages/#' . $item['id'], 301, false);
                }
                $db->querys("INSERT INTO ".$sys_tables['messages']." SET id_user_from = ?, id_user_to = ?, datetime_create = NOW()", $from, $to);
                Host::Redirect('/members/messages/#' . $db->insert_id, 301, false);
            break;
        
    //Диалог по сообщению (саппорта)
    case $action=='support':
        $h1 = 'Вопрос в поддержку';
        $list = [];
        
        $messages = new Messages();
        //Выбираем костыльного пользователя по саппорту
        $parent_id = 0;
        $recipient = $db->fetch("SELECT id
                                 FROM ".$sys_tables['users']." 
                                 WHERE id_group = ? AND email = ? LIMIT 0,1",
                                 support_group_number, support_email
        );
                                 
        if(empty($recipient)) {$this_page->http_code=404; break;}
        
        $recipient = $messages->GetRecipient($recipient['id']);
        $recipient_id = $recipient['id'];
        
        
        //Определяем наличие предыдущего диалога по саппорту
        $support_msg_id = $db->fetch("SELECT  id
                FROM ".$sys_tables['messages']." 
                WHERE 
                    `id_user_from` = ".$auth->id." 
                AND `id_user_to` = ".$recipient['id']."
                AND `id_parent` = 0
                AND `is_deleted_from` = 2
        ");
        
        if(!empty($support_msg_id)){
            //Берем сообщение-иннициатор
            $message = $messages->GetMessage($support_msg_id['id']);
            $parent_id = $message['msg_id'];
            //Берем список сообщений рекурсивно вниз по ветке добавляя в начало сообщение-иннициатор
            $list = $messages->GetList($auth->id,$support_msg_id['id'], true, true);
            if($message['is_deleted_'.$message['msg_direction']] == 2)
                array_unshift ($list, $message);
        }
        $ajax_result['support_parent_id'] = !empty($parent_id) ? $parent_id : 0;
        $ajax_result['support_recipient_id'] = $recipient_id;
        $ajax_result['ok'] = true;
        break;
    //Диалог по сообщению (общий)
    case $action=='view':
        if($action=='view' && count($this_page->page_parameters)>3) Host::Redirect('/members/messages/');
        if ($action == 'view' && !empty($auth->id)){
            $messages = new Messages();
            $h1 = 'Личный кабинет';
            //добавление нового диалога
            if(!empty($this_page->page_parameters[2]) && Validate::isDigit($this_page->page_parameters[2]) && !empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]=='add' ){
                $message = [];
                $recipient_id = $message['id_user_to'] = $this_page->page_parameters[2];   
                $parent_id = 0; 
                $message['id_user_from'] = $auth->id;
                $message['is_deleted_from'] = 2;
                $message['is_deleted_to'] = 2;
                $message['msg_direction'] = 'to';
                $list = [];
                $user = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE id = ?", $this_page->page_parameters[2]);
                $message['id_tarif'] = $user['id_tarif'];
                $message['id_agency'] = $user['id_agency'];
                $message['name'] = $user['name'].' '.$user['lastname'];
            }
            else if(!empty($this_page->page_parameters[1])){
                if(!is_int((int)$this_page->page_parameters[1]))  {$this_page->http_code=404; break;}  
                if( !empty( $this->first_instance ) && !$ajax_mode ) Host::Redirect( '/members/messages/#' . $this_page->page_parameters[1], 301, false );
                //Берем сообщение-иннициатор
                $message = $messages->GetMessage($this_page->page_parameters[1]);
                //Определяем получателя и парент сообщения
                $recipient_id = ($message['id_user_from'] == $auth->id) ? ($message['id_user_to']):($message['id_user_from']);
                $parent_id = $message['msg_id'];
                //просмотр только своих диалогов
                if( !in_array($auth->id, array($message['id_user_from'], $message['id_user_to']))){
                    {$this_page->http_code=403; break;}
                }
                //Берем список сообщений рекурсивно вниз по ветке добавляя в начало сообщение-иннициатор
                $list = $messages->GetList($auth->id,$this_page->page_parameters[1], true, true);
                if($message['is_deleted_'.$message['msg_direction']] == 2)
                    array_unshift ($list, $message);
            }
            
            $recipient = $message;
        }
    // Диалог по сообщению -> вывод     
    case in_array($action,array('view','support','add')):
        //поиск предыдущего разговора с тех.поддержкой
        if(in_array($action,array('support','add'))){
            $item = $db->fetch("SELECT * FROM ".$sys_tables['messages']." WHERE id_parent = 0 AND (( id_user_from = ? AND id_user_to = ? ) OR ( id_user_to = ? AND id_user_from = ? ))", 3, $auth->id, 3, $auth->id);
            $id_parent = !empty($item['id_parent']) ? $item['id_parent'] : 0;
        }
        
        $module_template = 'message.html';

        Response::SetArray('recipient',$recipient);
        Response::SetArray('msg_list',$list);
        Response::SetString('author_avatar',$auth->user_photo);
        //информация о пользователе
        $user = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE id = ?", $auth->id);
        Response::SetArray('user', $user);
        Response::SetInteger('recipient_id',$recipient_id);
        Response::SetInteger('parent_id',$parent_id);
        Response::SetString('support_group',support_group_number);
        Response::SetString('host',Host::$host);        
        if( $ajax_mode && !empty( $list ) ) $ajax_result['ok'] = true;
        break;
    // отправка сообщения через ajax    
    case $action=='send' && ( count($this_page->page_parameters) == 1 || (count($this_page->page_parameters) == 2 && $this_page->page_parameters[1] == 'support' ) ) :
        if (!$ajax_mode) {$this_page->http_code=404; break;}
        $messages = new Messages();
        $recipient_id       = Request::GetInteger('id',METHOD_POST);
        if( empty( $recipient_id ) && count($this_page->page_parameters) == 2 && $this_page->page_parameters[1] == 'support' ) {
            $recipient = $db->fetch("SELECT id
                                     FROM ".$sys_tables['users']." 
                                     WHERE id_group = ? AND email = ? LIMIT 0,1",
                                     support_group_number, support_email
            );
                                     
            if(empty($recipient)) {$this_page->http_code=404; break;}

            $recipient = $messages->GetRecipient($recipient['id']);
            $recipient_id = $recipient['id'];            
        }
        $parent_message_id  = Request::GetInteger('pid',METHOD_POST);
        $message_text       = Request::GetString('msgtext',METHOD_POST);
        
        if ( $recipient_id == 0 ) {
            $parent_message = $messages->GetMessage($parent_message_id);
            $recipient_id = $parent_message['id_user_from']; 
        } 
        //поиск дубля сообщения
        $unique_message = $messages->GetSameMessage($recipient_id, $parent_message_id, $message_text);
        if(empty($unique_message)){
            $sent_id = $messages->Send($auth->id, $recipient_id, $message_text, $parent_message_id);
            $stored_message = $messages->GetMessage($sent_id);
        }
        
        if ($sent_id){
            $ajax_result['ok'] = true;
            $ajax_result['msgid'] = $sent_id;
            $ajax_result['msgtxt'] = $stored_message['message'];
            $ajax_result['msgtime'] = 'Только что';
            $ajax_result['msgdirection'] = 'from';
            $ajax_result['popup_redirect'] = true;
            $ajax_result['parentid'] = ($stored_message['msg_id_parent'] == 0) ? ($stored_message['msg_id']):($stored_message['msg_id_parent']);
        } else {
            $ajax_result['ok'] = false;
        }
        break;
    //Добавление комментария к объекту через ajax
    case $action=='comment' && count($this_page->page_parameters) == 1:
        if (!$ajax_mode) {$this_page->http_code=404; break;}
        
        $error = [];
        
        //Определяем получателя по id обекта и URL
        $url = Request::GetString('url',METHOD_POST);
        $url_data = preg_split("/\//",$url);

        if($url_data[3] == 'estate' ){
            //ЖК
            $estate_type = $url_data[4];
            $obj_id      = $url_data[6];
            
            if($url_data[5] == 'zhiloy_kompleks') {
                $estate_type = 'zhiloy_kompleks';
                $sql = "SELECT * FROM ".$sys_tables['housing_estates']." WHERE chpu_title = '".$db->real_escape_string($obj_id)."'";
            } else $sql = "SELECT id_user FROM ".$sys_tables[$estate_type]." WHERE id = ".$obj_id;
            
            $result = $db->fetch($sql);
            
            if(!empty($result)){
                $recipient_id = $result['id_user'];
            } else {
                $error[] = 'Object ID = '.$obj_id.' at '.$estate_type.' not found';
            }
        } else {$this_page->http_code=404; break;}
        
        //Определяем велся ли диалог по данному объекту
        $parent_message_id  = 0;
        
        $sql = "SELECT id 
                FROM ".$sys_tables['messages']." 
                WHERE `related_obj_url` = ?
                AND `id_user_from` = ?
                AND `id_parent` = 0
        ";
        
        $result = $db->fetch($sql,$url,$auth->id);
        
        if(!empty($result))
            $parent_message_id = $result['id'];
        
        $message_text = Request::GetString('msgtext',METHOD_POST);
        
        if(empty($message_text))
            $error[] = 'Message body can\'t be empty';

        if(empty($error))
            $sent_id = (new Messages())->Send($auth->id, $recipient_id, $message_text, $parent_message_id, 2, $url);
        
        if ($sent_id){
            $ajax_result['ok'] = true;
        } else {
            $ajax_result['ok'] = false;
            $ajax_result['error'] = implode(' && ',$error);
        }
        break;
    // рекурсивная установка атрибута 'прочитано' через ajax после загрузки страницы        
    case $action=='setread' && count($this_page->page_parameters) == 1:
        //if (!$ajax_mode) {$this_page->http_code=404; break;}
        $messages = new Messages();
        $id = Request::GetInteger('id',METHOD_POST);
        $messages->SetRead($id, true);
        break;
    // маркирование сообщения как удаленного через ajax
    case $action=='delete' && count($this_page->page_parameters) == 1:
        if (!$ajax_mode) {$this_page->http_code=404; break;}
        $messages = new Messages();
        $messages->SetDeleted(Request::GetInteger('id',METHOD_POST), Request::GetInteger('system',METHOD_POST), true);
        $ajax_result['ok'] = (empty($messages->error)) ? (true) : (false);
        break;
    // проверка новых сообщений через ajax (страница отдельного диалога)
    case $action=='checknew' && count($this_page->page_parameters) == 1:
        if (!$ajax_mode) {$this_page->http_code=404; break;}
        $messages = new Messages();
        //проверка сообщения на странице диалогов
        $id = Request::GetInteger('id',METHOD_POST);
        if(!empty($id)) {
            $list = $messages->GetLastUnreadMessages($id, $auth->id);
            $module_template = 'block.list.html';
        }
        else { // проверка нового сообщения по всему сайту - всплывающее окно
            //не показывать на странице с сообщениями
            if(strstr(Host::getRefererURL(), 'members/messages')!='') break;
            $type = Request::GetString('type',METHOD_POST);
            if(!empty($type) && $type == 'popup'){
                $list = $messages->GetLastUnreadMessages(false, $auth->id, true);    
                $module_template = 'block.popup.html';
            }
        }
        if(!empty($list)){
            $ajax_result['ok'] = true;
            $ajax_result['direction'] = count($list);
            Response::SetArray('list',$list);
            if(!empty($type) && $type == 'popup'){ //всплывающее сообщение
                
                //пометить все дерево разговора прочитанным
                foreach($list as $k=>$item){
                    $db->querys("UPDATE ".$sys_tables['messages']." SET popup_notification = 1 WHERE 
                            id_user_from = ? AND id_user_to = ?
                            ", $item['id_user_from'], $item['id_user_to']);
                }
            }  else {
                foreach($list as $k=>$item) $messages->SetRead($item['id'], true);
            }
        }
        $ajax_result['unread_count'] = (new Messages)->GetUnreadAmount();
        break;
    // Тест классов (Удалить)
    case $action=='test' && count($this_page->page_parameters) == 1:
        /*
        $module_template = 'message.html';
        $test = new Messages();
        $message = 'Тест 5555555555';
        $id = $test->Send(42378 , 42004, $message, 2);
        echo  $id;
        */
        break;
    default:
        $this_page->http_code=404;
        break;
}
?>
