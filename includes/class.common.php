<?php
    class Common {
         public $tables = [];
         public function __construct(){
            $sys_tables = Config::$sys_tables;
        }    
        /**
        * получение списка пользователей
        * @param integer $count - кол-во элементов (если 0 - то без ограничения)
        * @param integer $from - начиная с этого элемента
        * @param string $order - набор полей сортировки, как для SQL (напр. "datetime DESC, title ASC")
        * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
        * @return array of arrays
        */
        public function getUsersList($count=0, $from=0, $order = "", $where = ""){
            global $db;
            $sys_tables = Config::$sys_tables;
            $list = $db->fetchall("SELECT 
                                          ".$sys_tables['users'].".*,
                                          (".$sys_tables['users'].".active_build + ".$sys_tables['users'].".active_live + ".$sys_tables['users'].".active_commercial + ".$sys_tables['users'].".active_country) AS amount,
                                          IFNULL(".$sys_tables['agencies'].".title,'') AS agency_title,
                                          IF( ".$sys_tables['agencies'].".title IS NOT NULL, CONCAT('/organizations/company/',".$sys_tables['agencies'].".chpu_title,'/'), '') AS agency_chpu_title,
                                          ".$sys_tables['users'].".id AS user_id,
                                          LEFT(photos.name,2) as user_photo_folder,
                                          photos.name as user_photo,
                                          LEFT(agencies_photos.name,2) as agency_photo_folder,
                                          agencies_photos.name as agency_photo,
                                          TRIM( CONCAT(".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname) ) as title,
                                          ".$sys_tables['tarifs'].".title AS tarif_title
                                   FROM ".$sys_tables['users']."
                                   LEFT JOIN ".$sys_tables['users_photos']." photos ON photos.id_parent=".$sys_tables['users'].".id
                                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                   LEFT JOIN ".$sys_tables['agencies_photos']." agencies_photos ON agencies_photos.id_parent=".$sys_tables['agencies'].".id
                                   LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['users'].".id_tarif = ".$sys_tables['tarifs'].".id
                                   ".( !empty($where) ? "WHERE ".$where : "" )."
                                   ".( !empty($order) ? "ORDER BY ".$order : "" )."
                                   LIMIT ".$from.", ".$count);
            return $list;
            
        }
        /**
        * получение списка пользователей
        * @param integer $count - кол-во элементов (если 0 - то без ограничения)
        * @param integer $from - начиная с этого элемента
        * @param string $order - набор полей сортировки, как для SQL (напр. "datetime DESC, title ASC")
        * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
        * @return array of arrays
        */
        public function getAgenciesList($count=0, $from=0, $order = "", $where = ""){
            global $db;
            $sys_tables = Config::$sys_tables;
            $list = $db->fetchall("SELECT
                                            ".$sys_tables['agencies'].".*,
                                            IF(".$sys_tables['agencies'].".xml_status = 1,1,0) AS loading_permitted,
                                            IF(".$sys_tables['agencies'].".xml_status = 1 AND ".$sys_tables['agencies'].".`xml_time` != '00:00:00',DATE_FORMAT(".$sys_tables['agencies'].".`xml_time`,'%k:%i'),'') as xml_time_formatted,
                                            IF(".$sys_tables['agencies'].".id_tarif = 1,".$sys_tables['agencies'].".promo,".$sys_tables['tarifs_agencies'].".promo) AS promo_limit,
                                            IF(".$sys_tables['agencies'].".id_tarif = 1,".$sys_tables['agencies'].".premium,".$sys_tables['tarifs_agencies'].".premium) AS premium_limit,
                                            IF(".$sys_tables['agencies'].".id_tarif = 1,".$sys_tables['agencies'].".vip,".$sys_tables['tarifs_agencies'].".vip) AS vip_limit,
                                            IF(".$sys_tables['agencies'].".id_tarif = 1,".$sys_tables['agencies'].".video,".$sys_tables['tarifs_agencies'].".video) AS video_limit,
                                            IF(".$sys_tables['tarifs_agencies'].".unpaid_apps = 1,1,0) AS no_free_apps,
                                            ".$sys_tables['tarifs_agencies'].".title AS tarif_title,
                                            IF(".$sys_tables['tarifs_agencies'].".staff_number = -1,'&infin;',".$sys_tables['tarifs_agencies'].".staff_number) AS staff_limit,
                                            staff.staff_amount,
                                            ".$sys_tables['managers'].".`name` as `manager_name`,
                                            ".$sys_tables['users'].".`id` as `id_user`,
                                            ".$sys_tables['users'].".`id_group` as `id_group`,
                                            ".$sys_tables['users'].".`balance` as `balance`,
                                            LEFT(photos.name,2) as agency_photo_folder,
                                            photos.name as agency_photo,
                                            IF(".$sys_tables['processes'].".id IS NOT NULL,1,0) AS is_loading
                                    FROM ".$sys_tables['agencies']." 
                                    LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".`id` = ".$sys_tables['agencies'].".id_manager
                                    LEFT JOIN ".$sys_tables['tarifs_agencies']." ON ".$sys_tables['tarifs_agencies'].".`id` = ".$sys_tables['agencies'].".id_tarif
                                    LEFT JOIN  ".$sys_tables['agencies_photos']." photos ON photos.id_parent=".$sys_tables['agencies'].".id
                                    LEFT JOIN ".$sys_tables['processes']." ON ".$sys_tables['processes'].".id_agency = ".$sys_tables['agencies'].".id AND ".$sys_tables['processes'].".status = 1
                                    LEFT JOIN (SELECT COUNT(*) as staff_amount,id_agency FROM ".$sys_tables['users']." GROUP BY id_agency) staff ON staff.`id_agency` = ".$sys_tables['agencies'].".id
                                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".`id_agency` = ".$sys_tables['agencies'].".id AND ".$sys_tables['users'].".agency_admin = 1
                                   ".( !empty($where) ? "WHERE ".$where : "" )."
                                   ".( !empty($order) ? "ORDER BY ".$order : "" )."
                                   LIMIT ".$from.", ".$count);
            return $list;                                                                         
            
        }
        
        public static function getSpecialistsList($paginator,$page=1,$strings_per_page=10,$where=false,$sort_condition=false){
            global $db;
            $sys_tables = Config::$sys_tables;
            $users_specs = Config::Get('users_specializations');
            foreach($users_specs as $key=>$value){
                $alias = Convert::ToTranslit($value);
                $users_specs[$key] = array('title'=>$value,'url'=>$alias);
                $users_specs_aliases[] = $alias;
            }
            //выбираем страницы для отображения
            $list = $db->fetchall("SELECT ".$sys_tables['users'].".id,
                                          ".$sys_tables['users'].".sex,
                                          ".$sys_tables['users'].".avatar_color,
                                          (".$sys_tables['users'].".active_build +
                                           ".$sys_tables['users'].".active_live + 
                                           ".$sys_tables['users'].".active_commercial + 
                                           ".$sys_tables['users'].".active_country) AS amount,
                                          COUNT(DISTINCT ".$sys_tables['consults_answers'].".id) as answers_amount,
                                          IFNULL(".$sys_tables['agencies'].".title,'') AS parent_agency_title,
                                          IF( ".$sys_tables['agencies'].".title IS NOT NULL, CONCAT('/organizations/company/',".$sys_tables['agencies'].".chpu_title,'/'),'') AS parent_agency_url,
                                          ".$sys_tables['users'].".id AS user_id,
                                          CONCAT_WS('/','".Config::$values['img_folders']['users']."','big',LEFT(photos.name,2)) as user_photo_folder,
                                          photos.name as user_photo,
                                          TRIM(CONCAT(".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname)) as title, 
                                          ".$sys_tables['users'].".specializations,
                                          ".$sys_tables['users'].".phone
                                   FROM ".$sys_tables['users']."
                                   LEFT JOIN ".$sys_tables['users_photos']." photos ON photos.id_parent=".$sys_tables['users'].".id
                                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                   LEFT JOIN ".$sys_tables['consults_answers']." ON ".$sys_tables['consults_answers'].".id_user = ".$sys_tables['users'].".id AND ".$sys_tables['consults_answers'].".status = 1
                                   ".(!empty($where)?"WHERE ".$where:"")."
                                   GROUP BY ".$sys_tables['users'].".id
                                   ORDER BY ".(!empty($sort_condition)?$sort_condition.",":"")." title LIMIT ".$paginator->getFromString($page).",".$strings_per_page);
            foreach($list as $key=>$item){
                //вычисление видов деятельности по битовой маске
                $specs = [];
                $list[$key]['amounts_string'] = implode(', ',array($item['amount'],$item['answers_amount']));
                foreach($users_specs as $k=>$val){
                    if($item['specializations']%(pow(2,$k))>=pow(2,$k-1)) $specs[] = $val['title'];
                }
                $list[$key]['specializations'] = implode(',',$specs);
            }
            return $list;
        }
        
        /**
        * запись лога операции с агентством
        * 
        * @param mixed $id_user - id пользователя который проводит операцию
        * @param mixed $id_agency - id агентства, которое меняем
        * @param mixed $operation - тип операции
        * @param mixed $operation_info - дополнительные данные по операции
        */
        public static function LogAgencyOperation($id_user,$id_agency,$operation,$operation_info = false){
            global $db;
            $sys_tables = Config::$sys_tables;
            $res = $db->query("INSERT INTO ".$sys_tables['agencies_operations']." (id_agency,id_user,id_operation,operation_info)
                               VALUES (?,?,?,?)",$id_agency,$id_user,$operation,(empty($operation_info)?"":$operation_info));
            return $res;
        }
        
        /**
        * создание пользователя по параметрам
        * 
        * @param mixed $user_params - обязательно должна быть почта('email') и имя('name')
        * @return mixed - в случае успеха возвращает id и пароль новой записи
        */
        public static function createUser($user_params){
            global $db;
            $sys_tables = Config::$sys_tables;
            
            if(empty($user_params['email']) || !Validate::isEmail($user_params['email']) || empty($user_params['name'])) return false;
            $exists = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE email = ?",$user_params['email']);
            if(!empty($exists)) return false;
            
            unset($user_params['id']);
            $reg_password =  substr(md5(time()),-6);
            $user_params['passwd'] = sha1(sha1($reg_password));
            $colors = Config::Get('users_avatar_colors');
            $new_color = $colors[mt_rand(0,11)];
            $user_params['avatar_color'] = $new_color;
            
            $res = $db->insertFromArray($sys_tables['users'],$user_params);
            $new_id = $db->insert_id;
            if(!empty($res)) $db->query("UPDATE ".$sys_tables['users']." SET `datetime` = NOW() WHERE id = ?",$new_id);
            
            $user_params['user_activity'] = (!empty($user_params['user_activity'])?$user_params['user_activity']:1);
            
            return (empty($res)?false:array('id'=>$new_id,'email'=>$user_params['email'],'password'=>$reg_password,'user_activity'=>$user_params['user_activity']));
        }
        
        /**
        * * проверяем наличие пользователя с такими параметрами
        * 
        * @param mixed $user_info - Массив вида array('id'=>x), где первое - название поле поиска
        * @param mixed $fields_to_return - массив полей, которые нужны на выходе
        * 
        * @return mixed - false в случае неверных параметров или если поиск не дал результатов
        */
        public static function searchUser(array $search_info, $fields_to_return = false){
            global $db;
            $sys_tables = Config::$sys_tables;
            if( empty($user_info) || !is_array($search_info) || (!empty($fields_to_return) || !is_array($fields_to_return)) ) return false;
            $condition = [];
            $search_params = [];
            foreach($search_info as $key=>$value){
                $condition[] = $key." = ?";
                $search_params[] = $value;
            }
            if(empty($condition) || empty($search_params)) return false;
            $condition = implode(' AND ',$condition);
            if(!empty($fields_to_return)) $fields_to_return = array_map('$db->real_escape_string',$fields_to_return);
            
            $res = $db->fetch("SELECT ".(!empty($fields_to_return)?$fields_to_return:"*")." FROM ".$sys_tables['users']." WHERE ".$condition, $search_params);
            return ((!empty($res) && !empty($res['id']))?$res['id']:false);
        }
        
        /**
        * читаем информацию по пользователю по его ID
        * 
        * @param mixed $user_id - id пользователя
        * @param mixed $fields - массив полей, которые нужны на выходе
        */
        public static function getUserById($user_id, $fields_to_return = false){
            global $db;
            $sys_tables = Config::$sys_tables;
            if(empty($user_id) || !Validate::isDigit($user_id)) return false;
            
            if(!empty($fields_to_return) && !is_array($fields_to_return)) return false;
            if(!empty($fields_to_return)) $fields_to_return = array_map('$db->real_escape_string',$fields_to_return);
            
            $res = $db->fetch("SELECT ".(!empty($fields_to_return)?$fields_to_return:"*")." FROM ".$sys_tables['users']." WHERE id = ?",$user_id);
            return ((empty($res) || empty($res['id']))?false:$res);
        }
        
        /**
        * читаем информацию по агентству по его ID
        * 
        * @param mixed $user_id - id агентства
        * @param mixed $fields - массив полей, которые нужны на выходе
        */
        public static function getAgencyById($agency_id, $fields_to_return = false){
            global $db;
            $sys_tables = Config::$sys_tables;
            if(empty($agency_id) || !Validate::isDigit($agency_id)) return false;
            
            if(!empty($fields_to_return) && !is_array($fields_to_return)) return false;
            if(!empty($fields_to_return)) $fields_to_return = array_map('$db->real_escape_string',$fields_to_return);
            
            $res = $db->fetch("SELECT ".(!empty($fields_to_return)?$fields_to_return:"*")." FROM ".$sys_tables['agencies']." WHERE id = ?",$agency_id);
            return ((empty($res) || empty($res['id']))?false:$res);
        }
        
        /**
        * * читаем следующий рабочий день агентства
        * 
        * @param mixed $id_agency
        * @param mixed $activity_alias - тип активности: "","applications","questions"
        * @return mixed - массив 'day_num','begin','end' - ближайший день и рамки рабочего дня
        */
        public static function getAgencyNextWorkDay($id_agency,$activity_alias){
            global $db;
            $sys_tables = Config::$sys_tables;
            if(empty($id_agency)) return false;
            $where = "";
            switch(true){
                case $activity_alias == "a": $where = " AND applications_processing = 1";
                case $activity_alias == "q": $where = "AND questions_processing = 1";
            }
            //считаем ближайший рабочий день:
            $nearest_workday = $db->fetch("SELECT IF((CONVERT(day_num,SIGNED) - (WEEKDAY(NOW())+1))>0,
                                                     CONVERT(day_num,SIGNED) - (WEEKDAY(NOW())+1),
                                                     day_num+2) AS day_interval,
                                                     day_num,
                                                     SUBSTRING(begin,1,5) AS begin,
                                                     SUBSTRING(end,1,5) AS end
                                           FROM ".$sys_tables['agencies']."
                                           LEFT JOIN ".$sys_tables['agencies_opening_hours']." ON agencies_opening_hours.id_agency = ".$sys_tables['agencies'].".id
                                           WHERE id_agency = ? AND day_num != WEEKDAY(NOW())+1 ".$where."
                                           ORDER BY day_interval ASC
                                           LIMIT 1",$id_agency);
            return (!empty($nearest_workday)?$nearest_workday:false);
        }
        
        
        public static function generateUser($login = false, $email = false, $password = false, $expert = false){
            global $db;
            if( empty( $password ) ) $password = randomstring(6); 
            $hash_password = sha1(sha1($password)); 
            if( empty( $login ) ) $login = randomstring(6);
            $res = $db->query("INSERT INTO ".Config::$sys_tables['users']."
                                (login, email, name, passwd, datetime)
                               VALUES
                                ( ?, ?, '', ?, NOW() )"
                               , $login
                               , !empty( $email ) ? $email : ''
                               , $hash_password
            ); 
            $id_user = $db->insert_id;     // Получение id пользователя
            //запись эксперта в БД
            if( !empty( $expert ) ) {
                $login = 'expert_' . $id_user;
                $db->query(" UPDATE " . Config::$sys_tables['users'] . " SET login = ?, id_group = ?, expert = ? WHERE id = ?",
                             $login, 14, 1, $id_user
                );
            }
            return array( $login, $email, $password );
        }
        
        /*
        * Определение пользователя БСН по группе
        */
        
        public static function bsnMember(){
            global $auth;
            return !empty( $auth ) && !empty( $auth->id_group ) && in_array( $auth->id_group, Config::Get('bsn_groups' ) );
        }
    }  
?>
