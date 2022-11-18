<?php
DEFINE('system_group_number', 102);
class Messages {
    private $user_online_timeout = 5;
    private $users_table = "";
    private $users_photos_table = "";
    private $messages_table = "";
    private $error = [];
    
    public function __construct(){
        Config::Init();
        //интервал (в секундах), в течение которого пользователь считается пребывающим на сайте
        //с момента последнего входа.
        $this->user_online_timeout = 300; 
        $this->agencies_table = Config::$sys_tables['agencies'];
        $this->users_table = Config::$sys_tables['users'];
        $this->users_photos_table = Config::$sys_tables['users_photos'];
        $this->messages_table = Config::$sys_tables['messages'];
    }
    /* GetParentRoot
    *  Возвращает id корневого сообщения диалога по переданному идентификатору $id_parent_message 
    *  сообщения родителя рассматриваемого дочернего сообщения 
    */    
    //метод временно не используется
    private function GetParentRoot($id_parent_message){
        global $db;
        
        $sql = "SELECT ".$this->messages_table.".`id`,".$this->messages_table.".`id_parent`
                FROM ".$this->messages_table."        
                WHERE ".$this->messages_table.".`id` = ".$id_parent_message."
        ";
        
        $item = $db->fetchall($sql);
        if(!empty($item)){
            if($item[0]['id_parent'] == 0)
                $id = $item[0]['id'];
            else
                $id = $this->GetParentRoot($item[0]['id_parent']);
        } 
        
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
                
        return $id;
    }
    /* DetermineChildrens
    *  Возвращает массив дочерних элементов сообщений идентификаторы которых переданы в $ids_array
    */    
    private function DetermineChildrens($ids_array){
        global $db;
        $result = []; 
        
        $sql = "SELECT ".$this->messages_table.".`id`
                FROM ".$this->messages_table."        
                WHERE ".$this->messages_table.".`id_parent` IN (".implode(",",$ids_array).")
        ";
        
        $childrens = $db->fetchall($sql);
        if(!empty($childrens)){              
            foreach ($childrens as $key => $value)
                $result[] = $value['id'];
        }
                
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return $result;        
    }
    /*  GetObject
    *   Возвращает массив информации объекта недвижимости по его URL
    */    
    public function GetObject($url){
        global $db;
        
        $object = false;
        
        $url_data = preg_split("/\//",$url);

        if($url_data[3] == 'estate'){
            $estate_type = $url_data[4];
            $obj_id      = $url_data[6];
            
            $sql = "SELECT ".Config::$sys_tables[$estate_type].".*,
                    CONCAT(MID(".Config::$sys_tables[$estate_type.'_photos'].".`name`,1,2),'/',".Config::$sys_tables[$estate_type.'_photos'].".`name`) as `photo_url`,
                    ".Config::$sys_tables['subways'].".`title` as `subway_name`,
                    ".Config::$sys_tables['way_types'].".`title` as way_type_name
                    FROM ".Config::$sys_tables[$estate_type]." 
                    LEFT JOIN ".Config::$sys_tables[$estate_type.'_photos']." 
                    ON (".Config::$sys_tables[$estate_type].".`id_main_photo` = ".Config::$sys_tables[$estate_type.'_photos'].".`id`)
                    LEFT JOIN ".Config::$sys_tables['subways']."
                    ON (".Config::$sys_tables[$estate_type].".`id_subway` = ".Config::$sys_tables['subways'].".`id`)
                    LEFT JOIN ".Config::$sys_tables['way_types']." 
                    ON (".Config::$sys_tables[$estate_type].".`id_way_type` = ".Config::$sys_tables['way_types'].".`id`)
                    WHERE ".Config::$sys_tables[$estate_type].".`id` = ".$obj_id;
            
            $result = $db->fetch($sql);
                
            if(!empty($result)){
                //получаем имя района (если присутствует) 78- Петербург 47- ЛО
                if ($result['id_region'] == 78)
                    $district_title = $db->fetch("SELECT title FROM ".Config::$sys_tables['districts']." WHERE id= ?",$result['id_district']);
                if ($result['id_region'] == 47)
                    $district_title = $db->fetch("SELECT offname as `title` FROM ".Config::$sys_tables['geodata']." WHERE a_level = 2 AND id_region = 47 AND id_area = ?",$result['id_area']);
                $result['district_title'] = ($district_title) ? ($district_title['title']) : ('');
                $object = $result;
                $object['estate_type'] = $estate_type;
            }
        } 
        
        return $object;
    }
    /*  GetRecipient
    *   Возвращает массив запрошенного по $id_user пользователя 
    */    
    public function GetRecipient($id_user){
        global $db;
                
        $sql = "SELECT *,
                IF((NOW() - ".$this->users_table.".`last_enter`) < ".$this->user_online_timeout.",'true','false') as `useronline`,
                ".$this->users_table.".`id` as `id`,
                TRIM(CONCAT(TRIM(".$this->users_table.".name),   ' ', TRIM(".$this->users_table.".lastname))) as name,
                ".$this->users_photos_table.".`name` as `photo`,
                MID(".$this->users_photos_table.".`name`,1,2) as `photo_subfolder`
                FROM ".$this->users_table." 
                LEFT JOIN ".$this->users_photos_table." on (".$this->users_photos_table.".`id` = ".$this->users_table.".`id_main_photo`)   
                WHERE ".$this->users_table.".`id` = ".$id_user." 
        ";
        
        $recipient = $db->fetch($sql);
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        
        if (empty($recipient)) return false;
        return $recipient;        
    }
    /*  GetMessage
    *   Возвращает массив запрошенного по $id_message сообщения 
    */    
    public function GetMessage($id_message){
        global $db, $auth;

        $sql = "SELECT
                ".$this->messages_table.".*,
                ".$this->users_table.".*,
                IF(
                    YEAR(". $this->messages_table.".`datetime_create`) < Year(CURDATE()),
                        DATE_FORMAT(". $this->messages_table.".`datetime_create`,'%e %M %Y'),
                        IF( 
                            DATE(". $this->messages_table.".`datetime_create`) != CURDATE(),
                            DATE_FORMAT(". $this->messages_table.".`datetime_create`,'%e %M, %k:%i'),
                            DATE_FORMAT(". $this->messages_table.".`datetime_create`,'%k:%i')
                        )
                            
                ) as normal_datetime_create,                                     
                TRIM(CONCAT(TRIM(".$this->users_table.".name),   ' ', TRIM(".$this->users_table.".lastname))) as name,
                ".$this->users_photos_table.".`name` as `photo`,
                MID(".$this->users_photos_table.".`name`,1,2) as `photo_subfolder`,
                ".$this->messages_table.".`id` as `msg_id`,
                ".$this->messages_table.".`id_parent` as `msg_id_parent`,
                ".$this->messages_table.".`id_user_from` as `msg_id_user_from`,
                IF(".$this->messages_table.".`id_user_from` = '".$auth->id."','from','to') as `msg_direction`,
                ".$this->agencies_table.".title as agency_title,
                IF((NOW() - ".$this->users_table.".`last_enter`) < ".$this->user_online_timeout.",'true','false') as `useronline`
                FROM ".$this->messages_table."
                LEFT JOIN ".$this->users_table." on (IF(".$this->messages_table.".`id_user_from` = '".$auth->id."',".$this->messages_table.".`id_user_to`,".$this->messages_table.".`id_user_from`)  = ".$this->users_table.".`id`)
                LEFT JOIN ".$this->agencies_table." on ".$this->users_table.".`id_agency`  = ".$this->agencies_table.".`id`
                LEFT JOIN ".$this->users_photos_table." on (".$this->users_photos_table.".`id` = ".$this->users_table.".`id_main_photo`)  
                WHERE ".$this->messages_table.".`id` = '".$id_message."'
        ";
        
        $message = $db->fetch($sql);
        
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return $message; 
    }
    /*  GetLastUnreadMessage
    *   Возвращает идентификатор первого найденного непрочитанного сообщения, рекурсивно по дочерним элементам.
    *   Если таковой не обнаружен - возвращает последний найденный идентификатор сообщения.
    */    
    public function GetLastUnreadMessage($id_message=false, $id_user_to=false, $last = false){
        global $db;
        //последнее сообщение в последние 2 минуты
        if(!empty($id_user_to)) $where = $this->messages_table.".datetime_read IS NULL 
                       AND ".$this->messages_table.".popup_notification = 2
                       AND id_user_to = ".$id_user_to;
        //поиск последнего сообщения в чате
        else $where = $this->messages_table.".`id_parent` = '".$id_message."'";
        $where .= " AND ( ".$this->messages_table.".message != '' OR ".$this->messages_table.".id_parent = 0) ";
        $sql = "SELECT id,id_parent,is_unread FROM ".$this->messages_table."
                WHERE $where  
                ORDER BY ".$this->messages_table.".`datetime_create` ".(!empty($id_user_to) || !empty($last)?" DESC":"")."
        ";
        
        $ids = $db->fetchall($sql);
        
        if (empty($ids)){
           $searched_id = $id_message;
        } else {
            foreach ($ids as $key => $value){
                if($value['is_unread'] == 2){
                    $searched_id = $this->GetLastUnreadMessage($value['id']);
                    if (empty($searched_id)){
                        $searched_id = $value['id'];
                    }
                } else {
                    $searched_id = $value['id'];
                }
            }
        }
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return $searched_id; 
    } 
    /*  GetLastUnreadSystemMessage
    *   Возвращает последенее непрочитанное системное сообщение
    */    
    public function GetLastUnreadSystemMessage(){
        global $db, $auth;
        $recipient = $db->fetch("SELECT id
                                 FROM ".$this->users_table." 
                                 WHERE id_group = ?", system_group_number);
        if(!empty($recipient)) {
        
            $item = $db->fetch("SELECT *,
                                       IF(id_parent=0,id,id_parent) as id_parent
                                FROM ".$this->messages_table."
                                WHERE ".$this->messages_table.".is_unread = 1
                                       AND is_system = 1
                                       AND id_user_to = ".$auth->id."
                                ORDER BY ".$this->messages_table.".`datetime_create` DESC");
            return $item; 
        }
    }
        
    /*  GetSameMessage
    *   Возвращает дубляж сообщения за последнюю минуту
    */    
    public function GetSameMessage($recipient_id, $parent_message_id, $message_text){
        global $db, $auth;
        //последнее сообщение за последнюю минуту
        $item = $db->fetch(
                            "SELECT * FROM ".$this->messages_table."
                             WHERE 
                                  id_user_to = ? AND 
                                  id_user_from = ? AND 
                                  id_parent = ? AND 
                                  message = ? AND 
                                  NOW() - INTERVAL 1 MINUTE < datetime_create",
                                  $recipient_id, $auth->id, $parent_message_id, $message_text);
        return $item; 
    }     
    /*  GetLastUnreadMessages
    *   Возвращает список всех непрочитанных сообщений
    */    
    public function GetLastUnreadMessages($id_message=false, $id_user_to=false, $popup_notification = false, $is_system = false){
        global $db, $auth;
        //последнее сообщение в последние 2 минуты
        if(!empty($popup_notification)) 
            $where = $this->messages_table.".is_unread = 1
                       AND ".$this->messages_table.".popup_notification = 2
                       AND id_user_to = ".$id_user_to;
        //поиск последнего сообщения в чате
        else 
            $where = $this->messages_table.".id_user_to = ".$id_user_to." AND ".$this->messages_table.".is_unread = 1
                      ".(!empty($id_message) ? "AND ".$this->messages_table.".`id_parent` = '".$id_message."'" : "" )
                      .(!empty($is_system) ? "AND ".$this->messages_table.".`is_system` = '".$is_system."'" : "" );
                      
        $where .= " AND ".$this->messages_table.".message != ''";        
        $sql = "SELECT  
                    ".(empty($is_system) || $is_system == 2 ? "COUNT(*) as cnt, " : "")."
                    ".$this->messages_table.".*, 
                    TRIM(CONCAT(TRIM(".$this->users_table.".name),   ' ', TRIM(".$this->users_table.".lastname))) as name,
                    ".$this->users_photos_table.".`name` as `photo`,
                    MID(".$this->users_photos_table.".`name`,1,2) as `photo_subfolder`,
                    IF(".$this->messages_table.".`id_user_from` = '".$auth->id."','from','to') as `msg_direction`,
                    IF((NOW() - ".$this->users_table.".`last_enter`) < ".$this->user_online_timeout.",'true','false') as `useronline`,
                    ".$this->users_table.".avatar_color,
                    ".$this->users_table.".sex
                FROM  ".$this->messages_table."
                LEFT JOIN ".$this->users_table." on (IF(".$this->messages_table.".`id_user_from` = '".$auth->id."',".$this->messages_table.".`id_user_to`,".$this->messages_table.".`id_user_from`)  = ".$this->users_table.".`id`)
                LEFT JOIN ".$this->users_photos_table." on (".$this->users_photos_table.".`id` = ".$this->users_table.".`id_main_photo`)  
                WHERE $where  
                ".(empty($is_system) || $is_system == 2 ? "GROUP BY ".$this->messages_table.".id_user_from" : "")."
                ORDER BY ".$this->messages_table.".`datetime_create` ".(!empty($id_user_to)?" DESC":"")."
        ";
        
        $list = $db->fetchall($sql);
       
        return $list;
    }       
    /*  GetList
     *  Получает список сообщений по заданному паренту рекурсивно или по одному уровню
     */    
    public function GetList($id_user, $id_parent = 0, $recursive = false, $get_deleted = false){
        global $db, $auth;
        
        $sql = "SELECT
                    ".$this->users_table.".*,
                    ".$this->messages_table.".*,
                    IF(
                        YEAR(". $this->messages_table.".`datetime_create`) < Year(CURDATE()),
                            DATE_FORMAT(". $this->messages_table.".`datetime_create`,'%e %M %Y'),
                            IF( 
                                DATE(". $this->messages_table.".`datetime_create`) != CURDATE(),
                                DATE_FORMAT(". $this->messages_table.".`datetime_create`,'%e %M, %k:%i'),
                                DATE_FORMAT(". $this->messages_table.".`datetime_create`,'%k:%i')
                            )
                                
                    ) as normal_datetime_create,                     
                    TRIM(CONCAT(TRIM(".$this->users_table.".name),   ' ', TRIM(".$this->users_table.".lastname))) as name,
                    ".$this->users_photos_table.".`name` as `photo`,
                    MID(".$this->users_photos_table.".`name`,1,2) as `photo_subfolder`,
                    ".$this->messages_table.".`id` as `msg_id`,
                    ".$this->messages_table.".`id_parent` as `msg_id_parent`,
                    ".$this->messages_table.".`id_user_from` as `msg_id_user_from`,
                    IF(".$this->messages_table.".`id_user_from` = '".$auth->id."','from','to') as `msg_direction`,
                    IF((NOW() - ".$this->users_table.".`last_enter`) < ".$this->user_online_timeout.",'true','false') as `useronline`
                FROM ".$this->messages_table."
                LEFT JOIN ".$this->users_table." on (".$this->messages_table.".`id_user_from` = ".$this->users_table.".`id`)
                LEFT JOIN ".$this->users_photos_table." on (".$this->users_photos_table.".`id` = ".$this->users_table.".`id_main_photo`)  
                WHERE (".$this->messages_table.".`id_user_from` = '".$id_user."' OR ".$this->messages_table.".`id_user_to` = '".$id_user."')
                    
                    ".( empty($get_deleted ) ? ('') : " AND IF(".$this->messages_table.".`id_user_from` = '".$auth->id."',".$this->messages_table.".`is_deleted_from`,".$this->messages_table.".`is_deleted_to`)  = 2 " )."
                " . (
                        empty($recursive) && $id_parent==0 && !empty($get_deleted) ? 
                            "ORDER BY ".$this->messages_table.".`datetime_create` DESC"
                        :
                            " AND ".$this->messages_table.".`id_parent` = '".$id_parent."' ORDER BY ".$this->messages_table.".`datetime_create`"
                    )."
        ";
        $list1 = $db->fetchall($sql);
        
        $list = $list1;
        $prev_time = false;
        if (!empty($list) && $recursive){
            $innerlist = [];
            foreach ($list as $key => $value) {
                $deeper = $this->GetList($id_user, $value['msg_id'], true);
                $current_time = new DateTime($value['datetime_create']);
                if(!empty($prev_time)) {
                    $interval = $prev_time->diff($current_time);
                    $list[$key]['time_diff'] = ((int)$interval->format('%i')) + ((int)$interval->format('%h') * 60) + ((int)$interval->format('%d') * 60 * 24) + ((int)$interval->format('%m') * 60 * 24 * 30) + ((int)$interval->format('%y') * 60 * 24 * 365);
                }
                $prev_time = $current_time;            

                if (!empty($deeper))
                    $innerlist = array_merge($innerlist, $deeper);
            }
            $list = array_merge($list, $innerlist);
        }
        
        return $list; 
    }
    /*  GetDialogs
    *   Формирует список последних непрочитанных сообщений пользователя $id_user для главной страницы диалогов,
    *   если нет непрочитанных сообщений - выбирает последнее сообщение диалога,
    *   рекурсивно обрабатывая ветки дочерних сообщений.
    *   Полученный массив отсортирован по timestamp в порядке убывания дат.
    */    
    public function GetDialogs($id_user){
        global $db, $auth;
        $list = [];
        
        $dialogs = $this->GetList($id_user, 0, false, true);
       
        $prev_time = new DateTime();
        if (!empty($dialogs)){
            foreach($dialogs as $key => $value){

                $key = !empty($value['msg_id_parent']) ? $value['msg_id_parent'] : $value['msg_id'];
                if( empty( $list[$key] ) ) {
                    
                    $last_message = $this->GetMessage($this->GetLastUnreadMessage(!empty($value['msg_id_parent'])?$value['msg_id_parent']:$value['msg_id']), false, true);
                    if($last_message['is_deleted_'.$last_message['msg_direction']] == 2){
                        $list[$key] = $last_message;
                        $list[$key]['msg_id'] = $value['msg_id'];
                        //подсчет непрочитанных сообщений
                        $list[$key]['msg_unread_total'] = $this->GetUnreadAmount($value['id_user_from']);
                        if (!empty($value['related_obj_url'])){
                            $list[$key]['related_obj_url'] = $value['related_obj_url'];
                            $list[$key]['object'] = $this->GetObject($list[$key]['related_obj_url']);
                        }
                        
                    }
                    
                    $current_time = new DateTime($value['datetime_create']);
                    if(!empty($prev_time)) {
                        $interval = $prev_time->diff($current_time);
                        $list[$key]['time_diff'] = $interval->format('%R%a дней');

                    }
                    $prev_time = $current_time;
                }
                
            }
        }
        
        $list = (empty($list)) ? (false): (($list));
        
        return $list; 
    }
    /* GetUnreadAmount
    *  Возвращает сумму всех непрочитанных сообщений
    *  $id_user_to - id получателя 
    */    
    public function GetUnreadAmount($id_user_from=false, $is_system = false){
        global $db, $auth;
        
        $sql = "SELECT
                count(".$this->messages_table.".`id`) as `amount`
                FROM ".$this->messages_table."
                WHERE ".(!empty($id_user_from)?$this->messages_table.".`id_user_from` = ".$id_user_from." AND ".$this->messages_table.".`id_user_to` = ".$auth->id : $this->messages_table.".`id_user_to` = ".$auth->id)."
                AND ".$this->messages_table.".`is_unread` = 1 
                AND ".$this->messages_table.".`is_deleted_from` = 2
                AND ".$this->messages_table.".`is_deleted_to` = 2
        ";
        
        $amount = $db->fetch($sql);
        return !empty($amount) ? $amount['amount'] : 0;
    }
    /*  Send
    *  "Отправляет сообщение" - запись сообщения БД.
    *   Аргументы:  (int)       $id_from - ID пользователя - автора сообщения
    *               (int)       $id_to - ID пользователя адресата
    *               (string)    $message - тело сообщения
    *               (int)       $id_parent - id сообщения родителя. id=0 => сообщение является корневым
    *               (enum(1,2)) $is_system - признак системного сообщения отправленого автоматически, 
    *                                       1=системное,
    *                                       2=обычное
    *               (string)    $related_url - URL объекта со страницы которого было отправлено 
    *                                       сообщение-комментарий
    *  Возвращает:
    *  в случае успеха - id отосланного сообщения
    *  в случае ошибки - false.
    */
    public function Send($id_from, $id_to, $message, $id_parent = 0, $is_system = 2, $related_url = '', $strip_tags = true){
        global $db;
        
        if (empty($message)){
            $this->error[]="Message is empty;";
            return false;
        }
        
        if(!empty($strip_tags)){
            $message = strip_tags(trim($message));
            $message = preg_replace("/\n/","<br>",$message);
            $message = preg_replace("~(http|https|ftp|ftps)://(.*?)(\s|\n|[,.?!](\s|\n)|$)~","<a href='$1://$2' target='_blank'>$1://$2</a>$3",$message);
        }
         
        $info = $db->prepareNewRecord($this->messages_table);
        // Валидацию еще не написал
        $info['datetime_read'] = NULL;
        $info['id_parent'] = $id_parent; 
        $info['id_user_from'] = $id_from;
        $info['id_user_to']  = $id_to;
        $info['message'] = $message;
        $info['is_system'] = $is_system;
        $info['related_obj_url'] = $related_url;
        
        //определение ветки сообщений с пользователем
        if(empty($id_parent)) $parent = $db->fetch('SELECT id_parent FROM '.$this->messages_table.' WHERE (id_user_from = ?  AND id_user_to = ?) OR (id_user_from = ?  AND id_user_to = ?) ORDER BY id DESC LIMIT 1', $info['id_user_from'], $info['id_user_to'], $info['id_user_to'], $info['id_user_from']); 
        if(!empty($parent)) $info['id_parent'] = $parent['id_parent']; 
        $db->insertFromArray($this->messages_table,$info,'id');
        
        $inserted_id = $db->insert_id;
        
        /*
        if (empty($db->error) && ($id_parent != 0))
            $this->ResumeDialog($inserted_id);
        */
            
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return ((empty($this->error)) ? ($inserted_id):(false)); 
    }
    /*  Remove permanently
    *   Удаляет сообщения из БД по массиву переданных идентификаторов сообщений
    *   Аргументы:  (array) $ids_array - массив ID сообщений.    
    *               (bool)  $recursive - признак рекурсивной обработки (удаляются все дочерние сообщения)
    */        
    public function Remove($ids_array, $recursive = false){
        global $db;

        switch (true){
            case (!is_array($ids_array)):
                $this->error[] = "Params: First parameter is not an array.";
            case (count($ids_array) == 0):
                $this->error[] = "Params: Given array is empty.";
                break;
            default:
                foreach ($ids_array as $key => $value){
                    if (!is_int((int) $value )) {
                        $this->error[] = "Params: not integer value at array[".$key."] => ".$value;
                        break;
                    }
                }
        }
        
        if (empty($this->error)){
            $db->querys("DELETE FROM ".$this->messages_table." WHERE id IN(".implode(",",$ids_array).")");
            
            if($recursive){
                $ids_childrens = $this->DetermineChildrens($ids_array);
                if(count($ids_childrens)>0)
                    $this->Remove($ids_childrens, true);
            }
        }
        
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return ((empty($this->error)) ? (true):(false));
    }
    /* SetField
    *  Устанавливает значение заданного поля по массиву переданных идентификаторов,
    *  а также, рекурсивно по дочерним элементам если установлен признак рекурсивной обработки $recursive
    *  Аргументы:   (array)     $ids_array - массив ID сообщений.
    *               (string)    $field - имя поля в таблице messages
    *               (mixed)     $value - значение устанавливаемого поля
    *               (bool)      $recursive - признак рекурсивной обработки
    *               (string)    $where - дополнительный параметр для вставки в SQL запрос (WHERE)
    */   
    public function SetField($ids_array, $field, $value, $recursive = false, $where = ''){
        global $db;
        
        switch (true){
            case (!is_array($ids_array)):
                $this->error[] = "Params: First parametr must be an array.";
            case (count($ids_array) == 0):
                $this->error[] = "Params: First parameter is empty.";
            case (empty($field)):
                $this->error[] = "Params: Field name is empty.";
            case (empty($value)):
                $this->error[] = "Params: Value is empty.";
        }
        
        if (empty($this->error) && !(count($ids_array) == 1 && $ids_array[0] == 0)){
            $db->querys("UPDATE ".$this->messages_table." SET ".$field." = ? WHERE id IN(".implode(",",$ids_array).") ".$where, $value);
            
            if($recursive && !(count($ids_array) == 1 && $ids_array[0] == 0)){
                $ids_childrens = $this->DetermineChildrens($ids_array);
                if(count($ids_childrens)>0)
                    $this->SetField($ids_childrens, $field, $value, true, $where);
            }
        }    
            
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return ((empty($this->error)) ? (true):(false));
    }
    /* SetRead
    *   Устанавливает атрибут is_unread в '2' - сообщения получают статус "прочитано",
    *   Аргументы:  (int)   $id_message - ID сообщения.    
    *               (bool)  $recursive - признак рекурсивной обработки (обрабатываются все дочерние сообщения)
    */   
    public function SetRead($id_message, $recursive = false){
        global $auth;
        
        $where = 'AND '.$this->messages_table.'.`id_user_to` = '.$auth->id;
        if(!empty($id_message)) $where .= ' AND ('.$this->messages_table.'.`id` = '.$id_message.' OR '.$this->messages_table.'.`id_parent` = '.$id_message.') ';
        $this->SetField(array($id_message), 'is_unread', 2, $recursive, $where);
        $this->SetField(array($id_message), 'datetime_read', date('Y-m-d H:i:s'), $recursive, $where);
        $this->SetField(array($id_message), 'popup_notification', 1, $recursive, $where);
            
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return ((empty($this->error)) ? (true):(false));
    }
    /* SetDeleted
    *   Устанавливает атрибут is_deleted_(from|to) в '1' - сообщения получают статус "удалено",
    *   у инициировавшего удаление участника диалога.
    *   Аргументы:  (int)   $id_message - ID сообщения. 
    *               (bool)  $recursive - признак рекурсивной обработки (обрабатываются все дочерние сообщения)
    */   
    public function SetDeleted($id_message, $system, $recursive = false){
        global $db, $auth;
        if(empty($system) || $system == 2){
            if(!empty($id_message)){
                $item = $this->GetMessage($id_message);
                if(!empty($item['id_parent'])) {
                    $list = $this->GetList($auth->id,$item['id_parent'], true, true);
                    $ids_list = [];
                    foreach($list as $k=>$item) $ids_list[] = $item['id'];
                } else $ids_list = array($id_message);
                
                $this->SetField($ids_list, 'is_deleted_from', 1, $recursive,'AND `id_user_from` = '.$auth->id);
                $this->SetField($ids_list, 'is_deleted_to', 1, $recursive,'AND `id_user_to` = '.$auth->id);
            }
        } else {
            $db->querys("DELETE FROM ".$this->messages_table." WHERE is_system = ? AND id_user_to = ?", 1, $auth->id);
        }
            
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return ((empty($this->error)) ? (true):(false));
    }
    /* ResumeDialog
    *   Устанавливает атрибут is_deleted в '2' - сообщения получают статус "не удаленное",
    *   Аргументы:  (int)   $id_message - ID сообщения. 
    */
    //метод временно не используется
    public function ResumeDialog($id_message){
        $id_message = $this->GetParentRoot($id_message);
        $this->SetField(array($id_message), 'is_deleted_from', 2, true);
        $this->SetField(array($id_message), 'is_deleted_to', 2, true);
            
        $this->error = (!empty($db->error)) ? ($db->error) : ($this->error);
        return ((empty($this->error)) ? (true):(false));
    }
}
?>
