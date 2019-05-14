<?php
/**    
* Класс работы со спецредложениями
*/
require_once('includes/class.storage.php');
require_once('includes/class.messages.php');     // Сообщения
require_once('includes/class.comments.php');     // Комментарии
require_once('includes/class.applications.php'); // Заявки
require_once('includes/class.consults.php');     // Консультации
class Notifications {
	 
    private static $tables = [];
	public static $count = 0;
    
						
    public static function Init(){
        self::$tables = Config::Get('sys_tables');
    }
    
    /**
    * Список уведомлений пользователя
    * @param integer $id_user - id пользователя
    * @return array (результат выборки из базы)
    */
    public static function getList($id_user){
        global $auth, $db;
        //подписки на объекты
        $list['estate_subscriptions'] = EstateSubscriptions::getAmount();
        //непрочитанные сообщения от пользователей
        $list['messages'] = (new Messages)->GetLastUnreadMessages(false, $auth->id, false, 2);
        $messages_count = 0;
        foreach($list['messages'] as $k => $item) $messages_count = $messages_count + $item['cnt'];
        //непрочитанные системные сообщения
        $list['system_messages'] = (new Messages)->GetLastUnreadMessages(false, $auth->id, false, 1);
        //костыль не показывать системные сообщения о новых заявках
        foreach($list['system_messages'] as $k => $item) if( strstr($item['message'], 'новая заявка #')!='' || strstr($item['message'], 'новый вопрос')!='' ) unset($list['system_messages'][$k]);
        //непрочитанные ответы
        $list['comments'] = Comments::getUserAnswers($auth->id, false, 1);
        //новые заявки
        $list['applications'] = $db->fetchall("SELECT * FROM ".self::$tables['applications']." WHERE id_user = ".$auth->id." AND status = 2 AND viewed_by_owner = 2");
        //новые ответы на вопросы (консультант)
        $consults = new ConsultQuestion();
        $list['consults'] = $consults->getAnswersList(false, false, $auth->id, 1);
        //счетчик уведомлений
        self::$count = $list['estate_subscriptions'] + $messages_count + count($list['system_messages']) + count($list['comments']) + count($list['applications']) + count($list['consults']);
        return $list;
    }
    /**
    * Отметка о прочтении
    * @param string $type - тип объекта
    * @param string $ids - список id
    */
    public static function setRead($type, $ids){
        global $auth, $db;
        self::$tables = Config::Get('sys_tables');
        switch($type){
            case 'messages':
                $messages = (new Messages)->GetList($auth->id, $ids);
                foreach($messages as $k => $message) 
                    if($auth->id == $message['id_user_to']) (new Messages)->SetRead($message['id'], true);
                    $db->query("UPDATE ".self::$tables['messages']." SET is_unread = 2, datetime_read = NOW() WHERE id_user_to = ? AND id = ?", $auth->id, $ids);
                break;
            case 'system_messages':
                $db->query("UPDATE ".self::$tables['messages']." SET is_unread = 2, datetime_read = NOW() WHERE id_user_to = ? AND id = ?", $auth->id, $ids);
                break;
            case 'estate_subscriptions':
                $db->query("UPDATE ".self::$tables['objects_subscriptions']." SET new_objects = 0, last_seen = NOW() WHERE id_user = ?", $auth->id);
                break;
            case 'comments':
                $value = 1;                 //значение флага прочтения
                $table = 'comments';        //таблица
                $field = 'comments_viewed'; //поле флага прочтения
                break;
            case 'applications':
                $value = 1;                 //значение флага прочтения
                $table = 'applications';    //таблица
                $field = 'viewed_by_owner'; //поле флага прочтения
                break;
            case 'consults':
                $value = 1;                 //значение флага прочтения
                $table = 'consults_answers';    //таблица
                $field = 'viewed_by_owner'; //поле флага прочтения
                break;
                
        }
        if(!empty($table)){
            $db->query("UPDATE ".self::$tables[$table]." SET `".$field."` = ? WHERE id IN (".$ids.")", $value);
        }
    }
    
    
}

?>