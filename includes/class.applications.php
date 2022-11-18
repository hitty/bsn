<?php
require_once('includes/class.common.php');
if( !class_exists( 'Emailer') ) require_once('includes/class.email.php');
/**    
* Класс для заявок
*/
class Application {
    
    public $id;                             //id заявки
    public $id_user;                        //id пользователя-хозяина (или того кто взял)
    public $id_agency;                      //id агентства-хозяина (или того кто взял)
    public $id_parent;                      //id объекта на который оставили
    public $status;                         //статус заявки
    public $visible_to_all;                 //видимость для всех (1-да, 2-нет)
    
    protected $data_array = [];        // данные заявки
    protected $owners_array = [];      // данные хозяев заявки
    
    public $target_object_status;           //статус объекта - цели заявки
    
    private $tables = [];              //все таблицы из Config
    
    /**
    * конструктор класса - создаем экземпляр либо для той что уже есть, либо по параметрам из формы
    * 
    * @param mixed $id - если создаем по существующей, ее id
    * @param mixed $create_params - если создаем из формы, параметры оттуда
    * @param Application $parent_app - если создаем копию, ее оригинал
    * @return Application
    */
    function __construct($id,$create_params = null,Application $parent_app = null,$no_notify = null){
        $this->tables = Config::$sys_tables;
        switch(true){
            case !empty($id): $this->Init($id);break;
            case !empty($create_params): 
                $this->Create($create_params);
                if(empty($no_notify) && $create_params['estate_type']!='inter') $this->sendModeratorNotification();
                break;
            case !empty($parent_app):
                $this->CreateFrom($parent_app);
                if(empty($no_notify)) $this->sendModeratorNotification();
                break;
            default: $this->id = 0;
        }
    }
    
    /**
    * создаем заявку при отправке формы (создаем экземпляр и пишем в базу)
    * 
    * @param mixed $parameters - параметры переданные из формы оставления заявки
    */
    private function Create($parameters){
        global $db;
        global $ajax_result;
        //читаем переданные параметры
        $id = ( !empty( $parameters['id'] ) ? $parameters['id'] : 0 );
        $agency_id = ( !empty( $parameters['agency_id'] ) ? $parameters['agency_id'] : 0 );
        $agent_id = (!empty($parameters['agent_id'])?$parameters['agent_id']:0);;
        $rent = (!empty($parameters['deal_type'])?$parameters['deal_type']:0);
        if(empty($rent)) $rent = (!empty($parameters['rent'])?$parameters['rent']:0);
        $estate_type = (!empty($parameters['type'])?$parameters['type']:0);
        if(empty($estate_type)) $estate_type = (!empty($parameters['estate_type'])?$parameters['estate_type']:0);
        
        //если это было со страницы акции, отмечаем это:
        if(preg_match('/promotion/',$estate_type)){
            $estate_type = 8;
        }
        $estate_url = $estate_type;
        $name = !empty( $parameters['name'] ) ? $parameters['name'] : ( !empty($parameters['fio']) ? $parameters['fio'] : '') ;
        $phone = $parameters['phone'];
        $email = $parameters['email'];
        $user_name = trim( $name );
        $user_type = !empty($parameters['user_type']) ? (int)$parameters['user_type'] : 0;
        $realtor_help_type = !empty($parameters['realtor_help_type']) ? (int) $parameters['realtor_help_type'] : 0;
        $work_status = (int)(!empty($parameters['id_work_status']) ? $parameters['id_work_status'] : 0);
        $user_comment = trim($parameters['user_comment']);
        
        //заявка идет с характеристик офиса в выдаче БЦ
        if($estate_type == 'offices'){
            $rent = 1;
            $estate_type = 'commercial';
        }
        
        $universal_app = false;
        switch(true){
                case preg_match('/^cott/',$estate_type): 
                     $estate_type = 'cottages';
                     $rent = 2;
                     break;
                case preg_match('/^busin/',$estate_type): 
                     $estate_type = 'business_centers';
                     $rent = 1;
                     break; 
                case $estate_type == 'housing_estates' || $estate_type == 'zhiloy_kompleks': 
                     $estate_type = 'housing_estates';
                     $rent = 2;
                     break; 
        }
        //если пусто, значит это ЖК, КП, или БЦ
        if(empty($this->tables[$estate_type]) && Validate::Digit($estate_type)){
            $universal_app = true;
            switch($estate_type){
                case 1: $estate_type_title = "Жилая";$estate_type = "live";break;
                case 2: $estate_type_title = "Новостройки";$estate_type = "build";$rent = 2;break;
                case 3: $estate_type_title = "Коммерческая";$estate_type = "commercial";break;
                case 4: $estate_type_title = "Загородная";$estate_type = "country";break;
            }
        }
        $agent = false;
        
        if(empty($rent))
            if($estate_type == 'build' || $estate_type == 'housing_estates' || $estate_type == 'cottages') $rent = 2;
        
        $arch_prefix = "";
        $object_is_moderating = false;
        if(!empty(Config::$values['object_types'][$estate_type])){
            $estate_type_key = Config::$values['object_types'][$estate_type]['key'];
            //проверяем наличие объекта (если архивный, отмечаем что это объект из )
            $object_info = $db->fetch("SELECT id,published FROM ".$this->tables[$estate_type]." WHERE id = ".$id);
            if(empty($object_info) && !empty($this->tables[$estate_type."_archive"])){
                $arch_prefix = "_archive";
                $object_info = $db->fetch("SELECT id FROM ".$this->tables[$estate_type.$arch_prefix]." WHERE id = ".$id);
            }else{
                if($object_info['published'] == 3){
                    unset($object_info);
                    $object_is_moderating = true;
                }
            }
        }        
        
        //если это общая заявка (какая-то из)
        if(empty($id) || $estate_type == 8){
            switch(true){
                case ($estate_type == 8):
                    list($user_id,$user_email,$agency_title,$is_agregator,$payed_page) = array_values($db->fetch("SELECT ".$this->tables['users'].".id,".$this->tables['users'].".email,
                                                                                                                         ".$this->tables['agencies'].".title,".$this->tables['agencies'].".is_agregator,
                                                                                                                         ".$this->tables['agencies'].".payed_page
                                                                                                                  FROM ".$this->tables['users']."
                                                                                                                  LEFT JOIN ".$this->tables['promotions']." ON ".$this->tables['users'].".id=  ".$this->tables['promotions'].".id_user
                                                                                                                  LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id=  ".$this->tables['users'].".id_agency
                                                                                                                  WHERE ".$this->tables['promotions'].".id = ".$id));
                    $rent = 0;
                    $estate_type_key = 8;
                    $this->data_array['estate_type_title'] = 8;
                    break;
                case (!empty($agency_id) || !empty($agent_id)):
                    if(!empty($agency_id)) $agency = true;
                    else $agent = true;
                    
                    list($user_id,$user_email,$agency_title,$is_agregator,$payed_page) = array_values($db->fetch("SELECT ".$this->tables['users'].".id,
                                                                                                     ".$this->tables['users'].".email,".
                                                                                                     (!empty($agency_id)?
                                                                                                       $this->tables['agencies'].".title, 
                                                                                                       (".$this->tables['agencies'].".is_agregator = 1) AS is_agregator,
                                                                                                       ".$this->tables['agencies'].".payed_page":
                                                                                                        (!empty($agent_id)?"CONCAT(".$this->tables['users'].".name,' ',".$this->tables['users'].".lastname) AS title,
                                                                                                        0 AS is_agregator,
                                                                                                                            ".$this->tables['users'].".payed_page":"0 AS title")
                                                                                                      )."
                                                                                                      FROM ".$this->tables['users']."
                                                                                                      ".(!empty($agency_id)?" LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id":"")." 
                                                                                                      WHERE ".(!empty($agency_id)?"id_agency = ".$agency_id." AND agency_admin = 1":
                                                                                                                                   $this->tables['users'].".id=".$agent_id)));
                    break;
                default:
                    $user_id = 0;
                    break;
            }
            $universal_app = true;
        }
        //заявка на объект
        else list($user_id,$rent,$object_url,$object_type,$advanced_item) = array_values($db->fetch(" SELECT 
                                                                                                             " . ( $estate_type == 'housing_estates' ? "IF(".$this->tables[$estate_type.$arch_prefix].".id_seller > 0 , ".$this->tables[$estate_type.$arch_prefix].".id_seller, ".$this->tables[$estate_type.$arch_prefix].".id_user) " :  $this->tables[$estate_type.$arch_prefix].".id_user" ) . " AS user_id,
                                                                                                             " . $this->tables[$estate_type.$arch_prefix].".rent, 
                                                                                                             CONCAT('/','".$estate_url."','/',".
                                                                                                                  (in_array($estate_type,array('build','live','commercial','country'))?"
                                                                                                                  CASE 
                                                                                                                    WHEN ".$this->tables[$estate_type.$arch_prefix].".rent=1 THEN 'rent'
                                                                                                                    WHEN ".$this->tables[$estate_type.$arch_prefix].".rent=2 THEN 'sell'
                                                                                                                    WHEN ".$this->tables[$estate_type.$arch_prefix].".rent=3 THEN 'rent'
                                                                                                                    WHEN ".$this->tables[$estate_type.$arch_prefix].".rent=4 THEN 'sell'
                                                                                                                  END,'/',".$this->tables[$estate_type.$arch_prefix].".id,'/'":
                                                                                                                  $this->tables[$estate_type.$arch_prefix].".chpu_title,'/'")
                                                                                                                  ."
                                                                                                              ) AS object_url,
                                                                                                              ".(in_array($estate_type,array("live","commercial","country"))?$this->tables['application_objects'].".id AS object_type":"0 AS object_type").",
                                                                                                              ".($estate_type == 'housing_estates' ? $this->tables[$estate_type.$arch_prefix].".advanced ":"0 AS advanced ")."
                                                                                                      FROM ".$this->tables[$estate_type.$arch_prefix].
                                                                                                      (!in_array($estate_type,array("live","commercial","country"))?"":" 
                                                                                                      LEFT JOIN ".$this->tables['application_objects']." ON ".$this->tables[$estate_type.$arch_prefix].".id_type_object = ".$this->tables['application_objects'].".id_parent ")." 
                                                                                                      WHERE ".$this->tables[$estate_type.$arch_prefix].".id = ?",$id));
        
        if(empty($advanced_item)) $advanced_item = false;
        $app_type = $db->fetch("SELECT id ,lifetime
                                FROM ".$this->tables['application_types']." 
                                WHERE rent = ? AND estate_type = ?",$rent,$estate_type_key);
        $app_lifetime = (int)$app_type['lifetime'];
        $app_type = $app_type['id'];
        
        //время закрытости заявки
        if($app_lifetime % 60 == 0) $app_lifetime = (floor($app_lifetime/60))." ч.";
        else $app_lifetime = (($app_lifetime/60 > 0)?(floor($app_lifetime/60)." ч."):("")).(($app_lifetime % 60 > 0)?(($app_lifetime % 60)." мин."):(""));
        
        global $auth;
        //если все хорошо, пихаем в базу
        if( (!empty($id) || !empty($universal_app)) && !empty($phone) && Validate::isPhone($phone) && !empty($name) && (empty($email) || Validate::isEmail($email)) ){
            
            $this->data_array['status'] = 4;
            $this->data_array['estate_type'] = (empty($estate_type_key)?$estate_type:$estate_type_key);
            $this->data_array['is_archive_object'] = (empty($arch_prefix)?2:1);
            $this->data_array['object_type'] = (empty($object_type)?0:$object_type);
            $this->data_array['application_type'] = !empty($app_type) ? $app_type : 1;
            $this->data_array['id_parent'] = (empty($id)?0:$id);
            $this->data_array['id_user'] = $user_id;
            $this->id_user = $user_id;
            $this->data_array['id_owner'] = $user_id;
            $this->data_array['id_initiator'] = (empty($auth->id)?0:$auth->id);
            $this->data_array['name'] = $name;
            $this->data_array['phone'] = Convert::ToPhone($phone,false,8)[0];
            $this->data_array['email'] = $email;
            $this->data_array['id_user_type'] = $user_type;
            $this->data_array['id_realtor_help_type'] = $realtor_help_type;
            $this->data_array['id_work_status'] = $work_status;
            $this->data_array['user_comment'] = (!empty($user_comment) ? $user_comment : "");
            
            //читаем информацию по хозяевам заявки
            if(!empty($this->id_user))
            $user_info = [];
            $user_info = $db->fetch("SELECT id AS id_user,
                                            id_agency,email AS user_email,
                                            phone AS user_phone,
                                            CONCAT(name,' ',lastname) AS user_full_name,
                                            id_tarif AS user_tarif,
                                            login,
                                            application_notification,
                                            id_agency,
                                            (id_tarif>0) AS user_is_specialist,
                                            payed_page AS user_payed_page
                                     FROM ".$this->tables['users']."
                                     WHERE id = ?", $this->id_user);
            $agency_info = [];
            $payed_page = !empty($user_info) ? ($user_info['user_payed_page'] == 1) : false;
            if(!empty($user_info['id_agency'])){
                $this->id_agency = $user_info['id_agency'];
                $agency_info = $db->fetch("SELECT ".$this->tables['agencies'].".email AS agency_email,
                                                  ".$this->tables['agencies'].".title AS agency_title,
                                                  ".$this->tables['agencies'].".email_service AS agency_email_service,
                                                  IF(advert_phone!='',advert_phone,phones) AS agency_phone, 
                                                  ".$this->tables['managers'].".name AS  manager_name,
                                                  ".$this->tables['managers'].".email AS manager_email,
                                                  id_tarif AS agency_tarif,
                                                  (is_agregator = 1) AS is_agregator,
                                                  payed_page AS agency_payed_page
                                           FROM ".$this->tables['agencies']." 
                                           LEFT JOIN ".$this->tables['managers']." ON ".$this->tables['agencies'].".id_manager = ".$this->tables['managers'].".id
                                           WHERE ".$this->tables['agencies']." .id = ?", $user_info['id_agency']);
                //пробуем прочитать цену с SALE
                $sale_app_cost = $db->fetch("SELECT ".$this->tables['sale_agencies'].".application_cost
                                             FROM ".$this->tables['application_agencies_sale']."
                                             LEFT JOIN ".$this->tables['sale_agencies']." ON ".$this->tables['sale_agencies'].".id = ".$this->tables['application_agencies_sale'].".agency_id_sale
                                             LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id_agency = ".$this->tables['application_agencies_sale'].".agency_id_bsn
                                             WHERE ".$this->tables['users'].".id = ? ", $user_info['id_user'])['application_cost'];
                if(!empty($sale_app_cost)) $this->data_array['sale_cost'] = $sale_app_cost;
                if(!empty($agency_info)) $payed_page = ($payed_page || $agency_info['agency_payed_page'] == 1);
            }
            
            if(!empty($user_info)) $this->owners_array = array_merge($user_info,$agency_info);
            
            $this->data_array['visible_to_all'] = ((empty($user_id) || ($advanced_item == 2 && !$payed_page)) || ( !empty($user_info['id_agency']) && empty($agency_info['agency_tarif']) ) ? 1 : 2);
            //акции по умолчанию не в общем пуле
            if($estate_type == 8) $this->data_array['visible_to_all'] = 2;
            
            
            
            $res =  $this->saveToDB();
            if(empty($res)) $ajax_result['ok'] = false;
			else{                
                
	            $ajax_result['ok'] = true;
	            
	            $this->data_array = $db->fetch("SELECT ".$this->tables['applications'].".*,
	                                                   ".$this->tables['application_types'].".*,
	                                                   ".$this->tables['application_objects'].".title,
	                                                   ".$this->tables['application_objects'].".id_parent AS information_id_type_object
	                                            FROM ".$this->tables['applications']." 
	                                            LEFT JOIN ".$this->tables['application_types']." ON ".$this->tables['applications'].".application_type = ".$this->tables['application_types'].".id
	                                            LEFT JOIN ".$this->tables['application_objects']." ON ".$this->tables['applications'].".application_type = ".$this->tables['application_objects'].".id
	                                            WHERE ".$this->tables['applications'].".id = ".$this->id);
	            $this->data_array['object_url'] = (!empty($object_url)?$object_url:false);
	            $this->data_array['id'] = $this->id;
	        }
            
        }
        else return false;
    }
    
    /**
    * создаем копию заявки для взятия в работу
    * 
    * @param Application $parent_app
    */
    private function CreateFrom(Application $parent_app){
        global $db;
        
        $this->data_array = $parent_app->data_array;
        $this->owners_array = $parent_app->owners_array;
        $this->id_user = 0;
        $this->status = 3;
        $this->data_array['datetime'] = date("Y-m-d H:i:s");
        $this->visible_to_all = 2;
        $this->data_array['id_parent_app'] = $parent_app->id;
        global $db;
        $this->saveToDB();
        //отмечаем старую, что ее уже брали в работу
        $db->querys("UPDATE ".$this->tables['applications']." SET in_work_amount = in_work_amount + 1 WHERE id = ".$parent_app->id);
    }
    
    /**
    * инициализируем заявку по уже существующей
    * 
    * @param mixed $id - непустой id заявки которая уже есть
    */
    private function Init($id){
        global $db;
        $this->tables = Config::$sys_tables;
        $user_info = [];$agency_info = [];
        
        if(empty($id)) return false;
            
        //читаем информацию по самой заявке
        $app_info = $db->fetch("SELECT ".$this->tables['applications'].".*,
                                       ".$this->tables['application_types'].".*,
                                       ".$this->tables['application_objects'].".title,
                                       ".$this->tables['application_objects'].".id_parent AS information_id_type_object,
                                       ".$this->tables['application_realtor_help_types'].".title AS realtor_help_type_title,
                                       CASE
                                           WHEN ".$this->tables['applications'].".estate_type = 1 THEN 'live'
                                           WHEN ".$this->tables['applications'].".estate_type = 2 THEN 'build'
                                           WHEN ".$this->tables['applications'].".estate_type = 3 THEN 'commercial'
                                           WHEN ".$this->tables['applications'].".estate_type = 4 THEN 'country'
                                           WHEN ".$this->tables['applications'].".estate_type = 5 THEN 'housing_estates'
                                           WHEN ".$this->tables['applications'].".estate_type = 6 THEN 'cottages'
                                           WHEN ".$this->tables['applications'].".estate_type = 7 THEN 'business_centers'
                                           WHEN ".$this->tables['applications'].".estate_type = 8 THEN 8
                                       END AS estate_type_title,
                                       CASE
                                           WHEN ".$this->tables['applications'].".estate_type = 1 THEN 'Жилая'
                                           WHEN ".$this->tables['applications'].".estate_type = 2 THEN 'Новостройки'
                                           WHEN ".$this->tables['applications'].".estate_type = 3 THEN 'Коммерческая'
                                           WHEN ".$this->tables['applications'].".estate_type = 4 THEN 'Загородная'
                                           WHEN ".$this->tables['applications'].".estate_type = 5 THEN 'Жилой комплекс'
                                           WHEN ".$this->tables['applications'].".estate_type = 6 THEN 'Коттеджный поселок'
                                           WHEN ".$this->tables['applications'].".estate_type = 7 THEN 'Бизнес-центр'
                                           WHEN ".$this->tables['applications'].".estate_type = 8 THEN 'Акция'
                                       END AS estate_type_title_ru,
                                       CONCAT(CASE
                                                 WHEN ".$this->tables['application_types'].".rent=1 THEN 'Аренда, '
                                                 WHEN ".$this->tables['application_types'].".rent=2 THEN 'Покупка, '
                                                 WHEN ".$this->tables['application_types'].".rent=3 THEN 'Сдам, '
                                                 WHEN ".$this->tables['application_types'].".rent=4 THEN 'Продам, '
                                               END,
                                               CASE
                                                 WHEN ".$this->tables['application_types'].".estate_type=1 THEN 'Жилая'
                                                 WHEN ".$this->tables['application_types'].".estate_type=2 THEN 'Новостройки'
                                                 WHEN ".$this->tables['application_types'].".estate_type=3 THEN 'Коммерческая'
                                                 WHEN ".$this->tables['application_types'].".estate_type=4 THEN 'Загородная'
                                                 WHEN ".$this->tables['application_types'].".estate_type=5 THEN 'ЖК'
                                                 WHEN ".$this->tables['application_types'].".estate_type=6 THEN 'КП'
                                                 WHEN ".$this->tables['application_types'].".estate_type=7 THEN 'БЦ'
                                                 WHEN ".$this->tables['application_types'].".estate_type=8 THEN 'Акция'
                                               END) AS universal_app_title
                                FROM ".$this->tables['applications']." 
                                LEFT JOIN ".$this->tables['application_types']." ON ".$this->tables['applications'].".application_type = ".$this->tables['application_types'].".id
                                LEFT JOIN ".$this->tables['application_realtor_help_types']." ON ".$this->tables['applications'].".id_realtor_help_type = ".$this->tables['application_realtor_help_types'].".id
                                LEFT JOIN ".$this->tables['application_objects']." ON ".$this->tables['applications'].".application_type = ".$this->tables['application_objects'].".id
                                WHERE ".$this->tables['applications'].".id = ".$id);
        if(!empty($app_info)){
            $this->data_array = $app_info;
            $arch_prefix = ($this->data_array['is_archive_object'] == 1?"_archive":"");
            $this->id = $id;
            $this->data_array['id'] = $this->id;
            $this->id_user = $app_info['id_user'];
            $this->id_parent = $app_info['id_parent'];
            $this->status = $app_info['status'];
            $this->visible_to_all = $app_info['visible_to_all'];
            $estate_type = $this->data_array['estate_type_title'];
            switch($estate_type){
                case "housing_estates": $estate_type_prefix = "build";break;
                case "business_centers": $estate_type_prefix = "commercial";break;
                case "cottages": $estate_type_prefix = "country";break;
                default: $estate_type_prefix = "";
            }
            $this->target_object_status = 1;
            if(!empty($this->id_parent))
                if($estate_type == 8){
                    $this->data_array['object_url'] = '/promotions/'.$this->id_parent;
                    $this->target_object_status = $db->fetch("SELECT published FROM ".$this->tables['promotions']." WHERE id = ".$this->id_parent);
                    $this->target_object_status = (!empty($this->target_object_status)?$this->target_object_status['published']:0);
                }
                else{
                    $temp = $db->fetch("SELECT  CONCAT('/',".(!empty($estate_type_prefix)?"'".$estate_type_prefix."'".",'/',":"")."'".$this->data_array['estate_type_title']."','/',".
                                                      (in_array($estate_type,array('build','live','commercial','country'))?"
                                                      CASE 
                                                        WHEN ".$this->tables[$estate_type.$arch_prefix].".rent=1 THEN 'rent'
                                                        WHEN ".$this->tables[$estate_type.$arch_prefix].".rent=2 THEN 'sell'
                                                        WHEN ".$this->tables[$estate_type.$arch_prefix].".rent=3 THEN 'rent'
                                                        WHEN ".$this->tables[$estate_type.$arch_prefix].".rent=4 THEN 'sell'
                                                      END,'/',".$this->tables[$estate_type.$arch_prefix].".id,'/'":
                                                      $this->tables[$estate_type.$arch_prefix].".chpu_title,'/'")
                                                      ."
                                                ) AS object_url,
                                                ".$this->tables[$estate_type.$arch_prefix].".published AS object_published
                                        FROM ".$this->tables[$estate_type.$arch_prefix]."
                                        WHERE ".$this->tables[$estate_type.$arch_prefix].".id = ?",$this->id_parent);
                    $this->data_array['object_url'] = $temp['object_url'];
                    $this->target_object_status = $temp['object_published'];
                }
                //list($this->data_array['object_url'],$this->target_object_status)
                
        }else return false;
        
        //читаем информацию по хозяевам заявки
        $user_info = $db->fetch("SELECT id AS id_user,
                                        id_agency,email AS user_email,
                                        phone AS user_phone,
                                        CONCAT(name,' ',lastname) AS user_full_name,
                                        id_tarif AS user_tarif,
                                        (id_tarif>0) AS user_is_specialist,
                                        login,
                                        application_notification,
                                        id_agency
                                 FROM ".$this->tables['users']."
                                 WHERE id = ".$this->id_user);
        
        if(!empty($user_info['id_agency'])){
            $this->id_agency = $user_info['id_agency'];
            $agency_info = $db->fetch("SELECT ".$this->tables['agencies'].".email AS agency_email,
                                              ".$this->tables['agencies'].".title AS agency_title,
                                              ".$this->tables['agencies'].".email_service AS agency_email_service,
                                              IF(advert_phone!='',advert_phone,phones) AS agency_phone, 
                                              ".$this->tables['managers'].".name AS  manager_name,
                                              ".$this->tables['managers'].".email AS manager_email,
                                              id_tarif AS agency_tarif,
                                              (is_agregator = 1) AS is_agregator
                                       FROM ".$this->tables['agencies']." 
                                       LEFT JOIN ".$this->tables['managers']." ON ".$this->tables['agencies'].".id_manager = ".$this->tables['managers'].".id
                                       WHERE ".$this->tables['agencies'].".id = ".$user_info['id_agency']);
            //пробуем прочитать цену с SALE
            $sale_app_cost = $db->fetch("SELECT ".$this->tables['sale_agencies'].".application_cost
                                         FROM ".$this->tables['application_agencies_sale']."
                                         LEFT JOIN ".$this->tables['sale_agencies']." ON ".$this->tables['sale_agencies'].".id = ".$this->tables['application_agencies_sale'].".agency_id_sale
                                         LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id_agency = ".$this->tables['application_agencies_sale'].".agency_id_bsn
                                         WHERE ".$this->tables['users'].".id = ".$user_info['id_user'])['application_cost'];
        }
        if(!empty($user_info)) $this->owners_array = array_merge($user_info,$agency_info);
        
        if(!empty($sale_app_cost)) $this->data_array['sale_cost'] = $sale_app_cost;
        
        return true;
    }
    
    /**
    * стоимость заявки для пользователя или чистая
    * 
    * @param mixed $id_user - id пользователя для которого считаем
    */
    public function getCost($id_user = false){
        global $db;
        
        $cost = 0;
        if($this->visible_to_all == 1 && $this->status == 2){
            
            //если считаем для конкретного пользователя, проверяем, вдруг там цена с sale
            if(!empty($id_user)){
                $sale_app_cost = $db->fetch("SELECT ".$this->tables['sale_agencies'].".application_cost
                                             FROM ".$this->tables['application_agencies_sale']."
                                             LEFT JOIN ".$this->tables['sale_agencies']." ON ".$this->tables['sale_agencies'].".id = ".$this->tables['application_agencies_sale'].".agency_id_sale
                                             LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id_agency = ".$this->tables['application_agencies_sale'].".agency_id_bsn
                                             WHERE ".$this->tables['users'].".id = ".$id_user)['application_cost'];
                if(!empty($sale_app_cost)) return $sale_app_cost;
            }
            elseif(!empty($this->owners_array['sale_app_cost'])) return $this->owners_array['sale_app_cost'];
            
            //вычисляем цену, не учитываем 12 часов если заявка общая или это агрегатор
            
            $moder_passed_time = new DateTime($this->data_array['datetime']);
            if($this->id_parent == 0 || !empty($this->owners_array['is_agregator'])) $days_passed = $moder_passed_time->diff(new \DateTime());
            else $days_passed = $moder_passed_time->diff(new \DateTime(date('Y-m-d H:i:s',strtotime('-12 hour'))));
            $cost = $this->data_array['cost'] - $days_passed->d*$this->data_array['day_discount']*0.01*$this->data_array['cost'] - $this->data_array['in_work_amount']*$this->data_array['client_discount']*0.01*$this->data_array['cost'];
        }
        return $cost;
    }
    
    public function Delete(){
        global $db;
        unset($this->data_array);
        unset($this->owners_array);
        $this->status = "deleted";
        return $db->querys("DELETE FROM ".$this->tables['applications']." WHERE id = ".$this->id);
    }
    
    public function Called(){
        global $db;
        return $db->querys("UPDATE ".$this->tables['applications']." SET status = 10 WHERE id = ".$this->id);
    }
    
    public function Remoderate($send_notifications = true){
        global $db;
        $db->querys("UPDATE ".$this->tables['applications']." SET visible_to_all = 2, status = 2 WHERE id = ".$this->id);
        if(!empty($send_notifications)) $this->sendNewAppNotifications();
    }
    
    public function InWork($user_id){
        global $db;
        $res = $db->querys("UPDATE ".$this->tables['applications']." SET status = 3, start_datetime = NOW(), visible_to_all = 2, id_user = ? WHERE id = ?",$user_id,$this->id);
        return $res;
    }
    
    private function getContacts(){
        return array('app_name' => $this->data_array['name'],'app_phone' => $this->data_array['phone'], 'app_email' => $this->data_array['email'], 'app_comment' => $this->data_array['user_comment']);
    }
    
    /**
    * шлем уведомления о поступлении заявки с модерации
    * 
    */
    public function sendNewAppNotifications(){
        global $db;
        //для отправки системных сообщений
        $messages = new Messages();
        //если это частный объект, делаем другой запрос (и смотрим галочку оповещений)
        switch(true){
            ////////////////////////////////////////////////////////////////////////////////
            //оповещения заявок Помощи реэлтора
            ////////////////////////////////////////////////////////////////////////////////
            case !empty($this->data_array['id_realtor_help_type']):
                $recievers = $db->fetchall("
                                            SELECT 
                                                   'user' AS type,
                                                   CONCAT(".$this->tables['users'].".name, ' ', ".$this->tables['users'].".lastname) AS name,
                                                   email_application_realtor_help as email
                                            FROM ".$this->tables['agencies']."
                                            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency AND ".$this->tables['users'].".agency_admin = 1
                                            WHERE email_application_realtor_help != ''
                ");
                $notify_info['recievers'] = $recievers;
                break;
            ////////////////////////////////////////////////////////////////////////////////
            //оповещения общего пула
            ////////////////////////////////////////////////////////////////////////////////
            case ($this->visible_to_all == 1):
                $recievers = $db->fetchall("SELECT 'user' AS type,TRIM(name) AS name,email
                                            FROM ".$this->tables['users']." 
                                            WHERE id_tarif > 0 AND id_agency = 0 AND foreign_application_notification = 1
                                            UNION
                                            SELECT 'agency' AS type,
                                                   IF(".$this->tables['agencies'].".email_applications = '',TRIM(".$this->tables['users'].".name),'') AS name,
                                                   IF(".$this->tables['agencies'].".email_applications = '',".$this->tables['agencies'].".email,".$this->tables['agencies'].".email_applications) AS email
                                            FROM ".$this->tables['agencies']."
                                            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                                            WHERE ".$this->tables['agencies'].".id_tarif > 0
                                            HAVING email <> ''");
                $notify_info['recievers'] = $recievers;
            break;
            ////////////////////////////////////////////////////////////////////////////////
            //объект частного лица
            ////////////////////////////////////////////////////////////////////////////////
            case (!empty($this->owners_array['id_user']) && empty($this->owners_array['id_agency'])):
                $notify_info = array('user_email'=>$this->owners_array['user_email'],
                                     'application_notification'=>$this->owners_array['application_notification'],
                                     'user_title'=>$this->owners_array['user_full_name'],
                                     'user_id'=>$this->owners_array['id_user']
                                    );
                //с 190916 в письмо частникам добавляем контакты заявки
                if(empty($this->owners_array['user_is_specialist'])) $notify_info = array_merge($notify_info,$this->getContacts());
            break;
            ////////////////////////////////////////////////////////////////////////////////
            //определяем агентство (не агрегатор) и ответственного менеджера
            ////////////////////////////////////////////////////////////////////////////////
            case (!empty($this->owners_array['id_agency']) && empty($this->owners_array['is_agregator'])):
                $notify_info = array('agency_id'=>$this->owners_array['id_agency'],
                                     'agency_title'=>$this->owners_array['agency_title'],
                                     'is_agregator'=>$this->owners_array['is_agregator'],
                                     'application_notification'=>$this->owners_array['application_notification'],
                                     'agency_email'=>$this->owners_array['user_email'],
                                     'user_title'=>$this->owners_array['user_full_name'],
                                     'is_specialist'=>$this->owners_array['user_is_specialist'],
                                     'agency_apps_email'=>$this->owners_array['agency_email_service'],
                                     'manager_name'=>$this->owners_array['manager_name'],
                                     'manager_email'=>$this->owners_array['manager_email'],
                                     'user_id'=>$this->owners_array['id_user']
                                     );
                //при необходимости переносим заявку продавцу-админу
                if(!empty($app_to_admin)) $db->querys("UPDATE ".$this->tables['applications']." SET id_user = ?,id_owner = ?  WHERE id = ?",$notify_info['user_id'],$notify_info['user_id'],$app_info['id']);
            break;
        }
        //читаем информацию по объекту:
        switch($this->data_array['estate_type']){
            case 1:
                $estateItem = new EstateItemLive($this->id_parent);
                break;
            case 2:
                $estateItem = new EstateItemBuild($this->id_parent);
                break;
            case 3:
                $estateItem = new EstateItemCommercial($this->id_parent);
                break;
            case 4:
                $estateItem = new EstateItemCountry($this->id_parent);
                break;
            case 5:
                $estateItem = new HousingEstates($this->id_parent);
                break;
            case 6:
                $estateItem = new Cottages($this->id_parent);
                break;        
            case 7:
                $estateItem = new BusinessCenters($this->id_parent);
                break;
            default:
                if($this->data_array['estate_type_title'] == 8){
                    $this->data_array['estate_type'] = 8;
                    $this->data_array['rent'] = 8;
                    break;
                } 
                $estateItem = null;
                return false;
                break;
        }
        switch($this->data_array['estate_type']){
            case 1: $estate_type_title = "Жилая";break;
            case 2: $estate_type_title = "Новостройки";break;
            case 3: $estate_type_title = "Коммерческая";break;
            case 4: $estate_type_title = "Загородная";break;
            case 8: $estate_type_title = "Акция";break;
        }
        if(empty($this->id_parent) || $this->data_array['estate_type_title'] == 8){
            switch($this->data_array['rent']){
                case 1: $notify_info['campaign_title'] = "Аренда, ".$estate_type_title;break;
                case 2: $notify_info['campaign_title'] = "Покупка, ".$estate_type_title;break;
                case 3: $notify_info['campaign_title'] = "Сдам, ".$estate_type_title;break;
                case 4: $notify_info['campaign_title'] = "Продам, ".$estate_type_title;break;
                case 8:
                    $promotion_title = $db->fetch("SELECT id, title FROM ".$this->tables['promotions']." WHERE id = ".$this->id_parent);
                    $notify_info['campaign_title'] = "Акция #".$promotion_title['id']." &laquo;".$promotion_title['title']."&raquo;";
                    break;
            }
            $notify_info['universal_app'] = true;
            //если это заявка со страницы компании или специалиста, то она универсальная, но пользователь есть
            if(!empty($this->owners_array['id_user'])){
                $notify_info['email'] = $this->owners_array['user_email'];
                if(!empty($this->id_agency)) $notify_info['agency_title'] = $this->owners_array['agency_title'];
            }
            $notify_info['user_type_title'] = $db->fetch("SELECT title FROM ".$this->tables['owners_user_types']." WHERE id = ?",$this->data_array['id_user_type']);
            $notify_info['user_type_title'] = (!empty($notify_info['user_type_title']['title']) ? $notify_info['user_type_title']['title'] : false);
        }
        else{
            $notify_info['campaign_title'] = $estateItem->getTitles($this->id_parent);
            $notify_info['campaign_title'] = $notify_info['campaign_title']['header'];
            $notify_info['universal_app'] = false;
        }
        
        //если email неправильный, сразу выходим
        if( (empty($notify_info['recievers']) || !is_array($notify_info['recievers'])) &&
            (empty($notify_info['agency_email']) || !Validate::isEmail($notify_info['agency_email'])) && 
            (empty($notify_info['agency_apps_email']) || !Validate::isEmail($notify_info['agency_apps_email'])) && 
            (empty($notify_info['manager_email']) || !Validate::isEmail($notify_info['manager_email'])) &&
            (empty($notify_info['user_email']) || !Validate::isEmail($notify_info['user_email'])) ) return false;
        else{
            $app_lifetime = (int)$this->data_array['lifetime'];
            
            //время закрытости заявки
            if($app_lifetime % 60 == 0) $app_lifetime = (floor($app_lifetime/60))." ч.";
            else $app_lifetime = (($app_lifetime/60 > 0)?(floor($app_lifetime/60)." ч."):("")).(($app_lifetime % 60 > 0)?(($app_lifetime % 60)." мин."):(""));
            //готовим данные для письма
            $data['campaign_title'] = (empty($notify_info['campaign_title'])?"":$notify_info['campaign_title']);
            $data['inserted_id'] = $this->id;
            $data['user_name'] = $this->data_array['name'];
            $data['user_comment'] = $this->data_array['user_comment'];
            $data['realtor_help_type_title'] = $this->data_array['realtor_help_type_title'];
            $data['app_lifetime'] = $app_lifetime;
            $data['is_specialist'] = (!empty($notify_info['is_specialist'])?$notify_info['is_specialist']:"");
            $data['user_title'] = (!empty($notify_info['user_title'])?$notify_info['user_title']:"");
            $data['manager_name'] = (!empty($notify_info['manager_name'])?explode(' ',$notify_info['manager_name'])[0]:"");
            $data['agency_title'] = (!empty($notify_info['agency_title'])?$notify_info['agency_title']:"");
            $data['universal_app'] = empty($this->data_array['id_parent']);
            $data['universal_app_title'] = $this->data_array['universal_app_title'];
            if(empty($this->data_array['object_url'])) $data['object_info'] = "";
            else $data['object_url'] = $_SERVER['HTTP_HOST'].$this->data_array['object_url'];
            $data['host'] = "bsn.ru";
            
            //добавляем контакты для частников
            if(!empty($notify_info['app_phone'])) {
                $data['user_phone'] = $notify_info['app_phone'];
                $data['user_email'] = $notify_info['app_email'];
            }
            
            //данные для письма
            Response::SetArray('data',$data);
            if( !empty($notify_info) ){
                
                if((!empty($notify_info['agency_email']) && Validate::isEmail($notify_info['agency_email'])) ||
                   (!empty($notify_info['agency_apps_email']) && Validate::isEmail($notify_info['agency_apps_email']))){
                    
                    $mailer = new EMailer('mail');
                    $mailer->sendEmail(array(($notify_info['application_notification'] == 1 ? $notify_info['agency_email'] : ""),$notify_info['agency_apps_email']),
                                       array('',''),
                                       "Новая заявка на bsn.ru - ID ".$data['inserted_id'].", ".date('Y-m-d H:i:s'),
                                       '/modules/applications/templates/mail.agency.html',
                                       '',
                                       $data,false,false,true);
                    $sended = true;
                    //дублируем системным сообщением
                    $messages->send(45523,$notify_info['user_id'],'Вам поступила новая заявка #'.$data['inserted_id'],0,1);
                }
                if(!empty($notify_info['manager_email']) && Validate::isEmail($notify_info['manager_email'])){
                    $mailer = new EMailer('mail');
                    $mailer->sendEmail(
                                        array($notify_info['manager_email'],"web@bsn.ru"),
                                        array($notify_info['manager_name'],"Миша"),
                                        "Новая заявка на bsn.ru - ID ".$data['inserted_id'].", ".date('Y-m-d H:i:s'),
                                        '/modules/applications/templates/mail.manager.html',
                                        '',
                                        $data,false,false,true
                    );
                    
                    $sended = true;
                }
                if(!empty($notify_info['user_email']) && empty($sended) && Validate::isEmail($notify_info['user_email'])){
                    $mailer = new EMailer('mail');
                    $mailer->sendEmail(array($notify_info['user_email'],"web@bsn.ru"),
                                       array($notify_info['user_title'],"Миша"),
                                       "Новая заявка на bsn.ru - ID ".$data['inserted_id'].", ".date('Y-m-d H:i:s'),
                                       '/modules/applications/templates/mail.user.html',
                                       '',
                                       $data,false,false,true);
                    //дублируем системным сообщением
                    $messages->send(45523,$notify_info['user_id'],'Вам поступила новая заявка #'.$data['inserted_id'],0,1);
                }
                if(!empty($notify_info['recievers']) && is_array($notify_info['recievers'])){
                    //print_r($notify_info);
                    foreach($notify_info['recievers'] as $key=>$item){
                        $mailer = new EMailer('mail');
                        if(!empty($this->data_array['id_realtor_help_type'])){
                            $mailer->sendEmail(
                                                array( $item['email'], 'kya1982@gmail.com' ),
                                                array( $item['name'], "" ),
                                                "У вас появилась поступила новая заявка от пользователя на риэлторские услуги. ",
                                                '/modules/applications/templates/mail.realtor.html',
                                                '',
                                                $data, false, false, true
                            );
                            
                        } else{
                            
                        $mailer->sendEmail(array($item['email'],'web@bsn.ru'),
                                           array($item['name'],""),
                                           $item['name'].", в общем доступе на BSN.ru появилась новая заявка",
                                           '/modules/applications/templates/mail.shared.html',
                                           '',
                                           $data,false,false,true);
                        }
                    }
                }
            }
        }
    }
    
    /**
    * шлем уведомление модератору о новой заявке
    * 
    */
    private function sendModeratorNotification(){
        global $db;
        $notify_info = $db->fetch("SELECT name,email
                                           FROM ".$this->tables['managers']."
                                           WHERE content_manager = 1");
        $host = (strstr($_SERVER['HTTP_HOST'],'test') ? "https://test.bsn.ru" : "https://www.bsn.ru");
        $notify_info['edit_url'] = $host."/admin/service/applications/edit/".$this->id."/";
        //читаем информацию по объекту:
        if(!empty($this->id_parent))
            switch($this->data_array['estate_type']){
                case 1:
                    $estateItem = new EstateItemLive($this->id_parent);
                    break;
                case 2:
                    $estateItem = new EstateItemBuild($this->id_parent);
                    break;
                case 3:
                    $estateItem = new EstateItemCommercial($this->id_parent);
                    break;
                case 4:
                    $estateItem = new EstateItemCountry($this->id_parent);
                    break;
                case 5:
                    $estateItem = new HousingEstates($this->id_parent);
                    break;
                case 6:
                    $estateItem = new Cottages($this->id_parent);
                    break;        
                case 7:
                    $estateItem = new BusinessCenters($this->id_parent);
                    break;
                default:
                    if($this->data_array['estate_type'] == 8){
                        $this->data_array['estate_type_title'] = 8;
                        $this->data_array['rent'] = 8;
                        break;
                    } 
                    $estateItem = null;
                    return false;
                    break;
            }
        
        switch($this->data_array['estate_type']){
            case 1: $estate_type_title = "Жилая";break;
            case 2: $estate_type_title = "Новостройки";break;
            case 3: $estate_type_title = "Коммерческая";break;
            case 4: $estate_type_title = "Загородная";break;
            case 5: $estate_type_title = "ЖК";break;
            case 8: $estate_type_title = "Акция";break;
        }
        
        if(empty($this->id_parent) || $this->data_array['estate_type'] == 8){
            switch($this->data_array['rent']){
                case 1: $notify_info['campaign_title'] = "Аренда, ".$estate_type_title;break;
                case 2: $notify_info['campaign_title'] = "Покупка, ".$estate_type_title;break;
                case 3: $notify_info['campaign_title'] = "Сдам, ".$estate_type_title;break;
                case 4: $notify_info['campaign_title'] = "Продам, ".$estate_type_title;break;
                case 8:
                    $promotion_title = $db->fetch("SELECT id, title FROM ".$this->tables['promotions']." WHERE id = ".$this->id_parent);
                    $notify_info['campaign_title'] = "Акция #".$promotion_title['id']." &laquo;".$promotion_title['title']."&raquo;";
                    break;
            }
            $notify_info['universal_app'] = true;
            //если это заявка со страницы компании, то она универсальная, но пользователь есть
            if(!empty($this->id_user)){
                //флаг, специалист или компания
                if(empty($this->id_agency)) $notify_info['agent'] = true;
                $notify_info['agency_title'] = (empty($this->owners_array['agency_title'])?$this->owners_array['user_full_name']:$this->owners_array['agency_title']);
            }
        }
        else{
            $notify_info['campaign_title'] = $estateItem->getTitles($this->id_parent);
            $notify_info['campaign_title'] = $notify_info['campaign_title']['header'];
            $notify_info['universal_app'] = false;
        }
        $notify_info['host'] = "bsn.ru";
        $notify_info['user_name'] = $this->data_array['name'];
        $notify_info['user_comment'] = $this->data_array['user_comment'];
        $notify_info['realtor_help_type'] = $this->data_array['id_realtor_help_type'];
        
        if(!empty($notify_info['email'])){
            $mailer = new EMailer('mail');
            //$notify_info['agent'] = (!empty($this->owners_array['user_tarif']) && $this->owners_array['user_tarif']>0 && $this->id_parent == 0);
            // формирование html-кода письма по шаблону
            Response::SetArray('data',$notify_info);
            
            // параметры письма
            $site = preg_replace('/^www\./','',$_SERVER['HTTP_HOST']);
            $mailer->sendEmail(array("web@bsn.ru","marina@bsn.ru","d.salova@bsn.ru"),
                               array("Тех.поддержка","Марина","Дария"),
                               "Необходимо проверить заявку на ".$site." - ID ".$this->id.", ".date('Y-m-d H:i:s'),
                               "/modules/applications/templates/mail.content_manager.html",
                               "",
                               $notify_info,
                               false,
                               false,
                               true);
        }
        return true;
    }
    
    /**
    * покупаем заявку
    * 
    * @param mixed $id_user - id покупателя
    * @param mixed $action - тип покупки
    */
    public function Buy($id_user,$buy_type = ""){
        global $db;
        global $auth;
        //свои объекты и бесплатные-для-платных-заявки бесплатны
        $free_app = $id_user!=3991 && (
                     ($this->data_array['visible_to_all'] == 2 && $this->data_array['id_owner'] == $auth->id || $this->data_array['visible_to_all'] == 3 && 
                     (!empty($auth) && ($auth->id_tarif > 0 || $auth->id_agency > 0 && $auth->agency_id_tarif > 0) ) )
        );
        //свои объекты и бесплатные-для-платных-заявки покупаются эксклюзивно
        if($this->data_array['id_owner'] == $auth->id || $this->data_array['visible_to_all'] == 3 &&
           (!empty($auth) && ($auth->id_tarif > 0 || $auth->id_agency > 0 && $auth->agency_id_tarif > 0) ) ) $buy_type = "in_work_exclusive";
        //если не бесплатны, читаем цену заявки 
        if(!empty($this->data_array['id_realtor_help_type'])) $cost_app = 1000;
        else $cost_app = (!empty($free_app) ? 0 : ( ($buy_type=='in_work_exclusive') ? $this->data_array['exclusive_cost'] : $this->getCost($id_user)) );
        
        
        ///костыли
        //td nevsk
        if($id_user == 43235){
            $cost_app = 1090;
            $free_app = false;
        }
        //centr-kvart
        elseif($id_user == 49525){
            $cost_app = 890;
            $free_app = false;
        }
        //arin-nov
        elseif($id_user == 48677){
            $cost_app = 890;
            $free_app = false;
        }
        //lime
        elseif($id_user == 27051){
            $cost_app = 1090;
            $free_app = false;
        }
        //ruskol
        elseif($id_user == 236){
            $cost_app = 800;
            $free_app = false;
        }
        //agm
        elseif($id_user == 46878){
            $cost_app = 872;
            $free_app = false;
        }
        //advecs
        elseif($id_user == 3991 ){
            $cost_app = 1090;
            $free_app = false;
        }
        
        //$cost_app = $this->getCost($id_user);
        $return_result['cost'] = $cost_app;
        //читаем баланс пользователя  может ли он вообще покупать
        $user_info = $db->fetch("SELECT id_agency,id_tarif,balance
                                 FROM ".$this->tables['users']."
                                 WHERE id = ?",$id_user);
        $user_balance = $user_info['balance'];
        //если пользователь не может купить, выходим
        if(empty($user_info['id_agency']) && empty($user_info['id_tarif']) && !$free_app){
            $return_result['ok'] = true;
            $return_result['cannot_buy'] = true;
        }
        
        if($db->error){
            $return_result['ok'] = false;
        }
        else $return_result['ok'] = true;
        
        //если баланс прочитался (или стоимость заявки нулевая), и его хватает на оплату заявки, списываем и пишем в таблицу финансов
        if(empty($cost_app) || (!empty($user_balance) && $user_balance >= $cost_app)){
            
            //перемещаем заявку в работу, записываем в финансы, корректируем баланс
            $user_balance -= $cost_app;
            
            $res = true;
            
            //если заявка уже взята в работу, значит мы чуть-чтуь опоздали, возвращаемся
            if($this->status != 2){
                $return_result['late'] = true;
                $return_result['pay_result'] = false;
                $return_result['ok'] = true;
            }else{
                //заявка не-эксклюзивно берется в работу - то копируем ее, нет - изменяем оригинал
                if($buy_type != 'in_work_exclusive' && !$free_app){
                    $new_app = new Application(0,false,$this,true);
                    $new_app->InWork($id_user);
                }else $this->InWork($id_user);
                
                $res = $res && $db->querys("INSERT INTO ".$this->tables['users_finances']."
                                           (`datetime`,id_user,obj_type,id_parent,expenditure,income,paygate)
                                           VALUES (NOW(),?,'application',?,?,0,1) ",$id_user,(!empty($new_app)?$this->id:$this->id),$cost_app);
                $res = $res && $db->querys("UPDATE ".$this->tables['users']." SET balance = ? WHERE id = ?",$user_balance,$id_user);
                
                $return_result['pay_result'] = $res;
            }

            if(!$res) $return_result['ok'] = false;
        }
        return $return_result;
    }
    
    public function toShared(){
        global $db;
        $res = $db->querys("UPDATE ".$this->tables['applications']." SET visible_to_all = 1 WHERE id = ?",$this->id);
        if(!empty($res)){
            $this->data_array['visible_to_all'] = 1;
            $this->visible_to_all = 1;
        }
        return $res;
    }
    
    public function fromShared(){
        global $db;
        if($this->status == 2) $res = $db->querys("UPDATE ".$this->tables['applications']." SET visible_to_all = 2 WHERE id = ?",$this->id);
        if($res) $this->data_array['visible_to_all'] = 2;
        return $res;
    }
    
    public function toArchive(){
        global $db;
        $res = $db->querys("UPDATE ".$this->tables['applications']." SET status = 5 WHERE id = ?",$this->id);
        if($res) $this->data_array['status'] = 5;
        return $res;
    }
    
    public function toModer(){
        global $db;
        if($this->status == 2) $res =  $db->querys("UPDATE ".$this->tables['applications']." SET status = 4, `datetime` = '0000-00-00 00:00:00', visible_to_all = 2 WHERE id = ".$this->id);
        if($res){
            $this->data_array['status'] = 4;
            $this->data_array['datetime'] = '0000-00-00 00:00:00';
            $this->data_array['visible_to_all'] = 2;
        }
        return $res;
    }
    
    public function toFinished(){
        global $db;
        if($this->status == 3) $res =  $db->querys("UPDATE ".$this->tables['applications']." SET status = 1, visible_to_all = 2, finish_datetime = NOW() WHERE id = ".$this->id);
        if($res) $this->data_array['status'] = 1;
        return $res;
    }
    
    public function toPublished(){
        global $db;
        return $db->querys("UPDATE ".$this->tables['applications']." SET status = 2, `datetime` = NOW() WHERE id = ".$this->id);
    }
    
    public function toUser($to_user_id){
        global $db;
        $res = $db->querys("UPDATE ".$this->tables['applications']." SET id_user = ?,id_owner = ?,visible_to_all = ? WHERE id = ?",$to_user_id,$to_user_id,2,$this->id);
        if($res){
            $this->data_array['id_user'] = $to_user_id;
            $this->data_array['id_owner'] = $to_user_id;
            $this->id_user = $to_user_id;
            $this->data_array['visible_to_all'] = 2;
            //читаем информацию по новому пользователю
            $user_info = [];
            $user_info = $db->fetch("SELECT id AS id_user,
                                            id_agency,email AS user_email,
                                            phone AS user_phone,
                                            CONCAT(name,' ',lastname) AS user_full_name,
                                            id_tarif AS user_tarif,
                                            login,
                                            application_notification,
                                            id_agency,
                                            (id_tarif>0) AS user_is_specialist,
                                            payed_page AS user_payed_page
                                     FROM ".$this->tables['users']."
                                     WHERE id = ?", $this->id_user);
            $agency_info = [];
            $payed_page = !empty($user_info) ? ($user_info['user_payed_page'] == 1) : false;
            if(!empty($user_info['id_agency'])){
                $this->id_agency = $user_info['id_agency'];
                $agency_info = $db->fetch("SELECT ".$this->tables['agencies'].".email AS agency_email,
                                                  ".$this->tables['agencies'].".title AS agency_title,
                                                  ".$this->tables['agencies'].".email_service AS agency_email_service,
                                                  IF(advert_phone!='',advert_phone,phones) AS agency_phone, 
                                                  ".$this->tables['managers'].".name AS  manager_name,
                                                  ".$this->tables['managers'].".email AS manager_email,
                                                  id_tarif AS agency_tarif,
                                                  (is_agregator = 1) AS is_agregator,
                                                  payed_page AS agency_payed_page
                                           FROM ".$this->tables['agencies']." 
                                           LEFT JOIN ".$this->tables['managers']." ON ".$this->tables['agencies'].".id_manager = ".$this->tables['managers'].".id
                                           WHERE ".$this->tables['agencies']." .id = ?", $user_info['id_agency']);
                //пробуем прочитать цену с SALE
                $sale_app_cost = $db->fetch("SELECT ".$this->tables['sale_agencies'].".application_cost
                                             FROM ".$this->tables['application_agencies_sale']."
                                             LEFT JOIN ".$this->tables['sale_agencies']." ON ".$this->tables['sale_agencies'].".id = ".$this->tables['application_agencies_sale'].".agency_id_sale
                                             LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id_agency = ".$this->tables['application_agencies_sale'].".agency_id_bsn
                                             WHERE ".$this->tables['users'].".id = ? ", $user_info['id_user'])['application_cost'];
                if(!empty($sale_app_cost)) $this->data_array['sale_cost'] = $sale_app_cost;
                if(!empty($agency_info)) $payed_page = ($payed_page || $agency_info['agency_payed_page'] == 1);
            }
            
            if(!empty($user_info)) $this->owners_array = array_merge($user_info,$agency_info);
        }
        return $res;
    }
    
    /**
    * переносим заявку на SALE определенному пользователю (не указано - тому же)
    * 
    */
    public function toSALE($sale_id_user = 0){
        global $db;
    }
    
    public function updateFromMapping($mapping){
        foreach($mapping as $key=>$item){
            $this->data_array[$key] = $mapping[$key]['value'];
        }
    }
    
    public function returnToMapping(&$mapping){
        foreach($this->data_array as $key=>$field){
            if(!empty($mapping[$key])) $mapping[$key]['value'] = $field;
        }
    }
    
    /**
    * апдейтим заявку в базе
    * 
    * @param mixed $refresh_date - обновлять или не обновлять datetime
    */
    public function saveToDB($refresh_date = false){
        global $db;
        
        $new_app = empty($this->id);
        
        $insert_app_info = array('id'=>$this->id,
                                 'status'=>$this->data_array['status'],
                                 'estate_type'=>$this->data_array['estate_type'],
                                 'id_user_type'=>$this->data_array['id_user_type'],
                                 'id_realtor_help_type'=>$this->data_array['id_realtor_help_type'],
                                 'id_work_status'=>$this->data_array['id_work_status'],
                                 'is_archive_object'=>$this->data_array['is_archive_object'],
                                 'object_type'=>$this->data_array['object_type'],
                                 'application_type'=>$this->data_array['application_type'],
                                 'id_parent_app'=>(!empty($this->data_array['id_parent_app'])?$this->data_array['id_parent_app']:0),
                                 'id_parent'=>$this->data_array['id_parent'],
                                 'id_user'=>$this->data_array['id_user'],
                                 'id_owner'=>$this->data_array['id_owner'],
                                 'id_initiator'=>$this->data_array['id_initiator'],
                                 'name'=>$this->data_array['name'],
                                 'phone'=>$this->data_array['phone'],
                                 'email'=>$this->data_array['email'],
                                 'user_comment'=>$this->data_array['user_comment'],
                                 'agency_comment'=>(!empty($this->data_array['agency_comment'])?$this->data_array['agency_comment']:""),
                                 'visible_to_all'=>$this->data_array['visible_to_all'],
                                 'comment'=>(!empty($this->data_array['comment'])?$this->data_array['comment']:""));
        if(empty($new_app)) $res = $db->updateFromArray($this->tables['applications'],$insert_app_info,'id');
        else $res = $db->insertFromArray($this->tables['applications'],$insert_app_info,'id');
        
        if($res){
            if(!empty($new_app)){
                $this->id = $db->insert_id;
                $this->data_array['id'] = $this->id;
                $this->id_user = $this->data_array['id_user'];
                $db->querys("UPDATE ".$this->tables['applications']." SET `creation_datetime` = NOW() WHERE id = ?",$this->id);
            }
            if(!empty($refresh_date)) $db->querys("UPDATE ".$this->tables['applications']." SET `datetime` = NOW() WHERE id = ?",$this->id);
            $this->id_parent = $this->data_array['id_parent'];
            $this->status = $this->data_array['status'];
            $this->visible_to_all = $this->data_array['visible_to_all'];
        } 
        return $res;
    }
    
    /**
    * собираем строку описания заявки
    * 
    * @return mixed
    */
    public function getDescription(){
        global $db;
        if($this->id_parent == 0){
            $description['text'] = "Общая заявка ";
            switch($this->data_array['rent']){
                case 1: $app_deal_type = 'Аренда';break;
                case 2: default: $app_deal_type = 'Покупка';break;
                case 3: $app_deal_type = 'Сдам';break;
                case 4: $app_deal_type = 'Продам';break;
            }
            if(!empty($this->data_array['id_user'])){
                $owner_info = $db->fetch("SELECT ".$this->tables['users'].".id,
                                                 ".$this->tables['users'].".id_tarif,
                                                 CONCAT(".$this->tables['users'].".name,
                                                 ".$this->tables['users'].".lastname) AS user_title,
                                                 ".$this->tables['agencies'].".title
                                          FROM ".$this->tables['users']." 
                                          LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id
                                          WHERE ".$this->tables['users'].".id = ".$this->data_array['id_user']);
                switch(true){
                    case (!empty($owner_info['id_tarif'])): 
                        $description['text'] .= $app_deal_type.", ".$this->data_array['estate_type_title_ru']." для специалиста ".
                                               (empty($owner_info['user_title'])?$owner_info['id']:$owner_info['user_title']).(!empty($owner_info['title'])?" компании ".$owner_info['title']:"");
                        break;
                    case (!empty($owner_info['title'])):
                        $description['text'] .= $app_deal_type.", ".$this->data_array['estate_type_title_ru']." для компании ".$owner_info['title'];
                        break;
                    default:
                        $description['text'] .= $app_deal_type.", ".$this->data_array['estate_type_title_ru']." для пользователя #".$owner_info['id'];
                        break;
                    
                }
            }
            else $description['text'] .= $app_deal_type.", ".$this->data_array['estate_type_title_ru'];
        }
        else{
            $id = $this->id_parent;
            $estate_type = $this->data_array['estate_type_title'];
            if(!empty($estate_type)){
                $estate_url = $estate_type;
                
                $description['url'] = $this->data_array['object_url'];
                $description['type'] = ($this->data_array['rent'] == 1?"Аренда":"Покупка").", ".$this->data_array['estate_type_title_ru'];
                $description['text'] = $this->id_parent;
            }
            else $description['text'] = "Заявка на объект #".$id." раздела ".($this->data_array['rent'] == 1?"Аренда":"Продажа").", ".$this->data_array['estate_type_title_ru'];
        }
        return $description;
    }
    
    /**
    * дергаем поле из данных заявки
    * 
    * @param mixed $attr_name - название поля
    * @return mixed
    */
    public function getAttr($attr_name){
        return (empty($this->data_array[$attr_name])?"":$this->data_array[$attr_name]);
    }
    
    /**
    * дергаем поле из данных по хозяевам заявки
    * 
    * @param mixed $attr_name
    * @return mixed
    */
    public function getOwnersAttr($attr_name){
        return (empty($this->owners_array[$attr_name])?"":$this->owners_array[$attr_name]);
    }
    
    /**
    * проверяем что заявка пришла клиенту в рабочее время. если нет - перемещаем в ожидающие запуска
    * 
    */
    public function checkWorkTime(){
        return true;
        //если не в агентство или в агрегатор - считаем время рабочим
        if(!empty($this->id_agency) && empty($this->owners_array['is_agregator'])){
            global $db;
            //проверяем что для агентства вообще указано время, если не указано, считаем 24/7
            $agency_has_time = $db->fetch("SELECT id FROM ".$this->tables['agencies_opening_hours']." WHERE id_agency = ".$this->id_agency);
            if(!empty($agency_has_time)){
                $agency_in_time = $db->fetch("SELECT ".$this->tables['agencies'].".id
                                              FROM ".$this->tables['agencies']."
                                              LEFT JOIN ".$this->tables['agencies_opening_hours']." ON ".$this->tables['agencies'].".id = ".$this->tables['agencies_opening_hours'].".id_agency
                                              WHERE ".$this->tables['agencies'].".id = ".$this->id_agency." AND
                                                    ".$this->tables['agencies_opening_hours'].".day_num = WEEKDAY(NOW())+1 AND
                                                    ".$this->tables['agencies_opening_hours'].".applications_processing = 1 AND 
                                                    ".$this->tables['agencies_opening_hours'].".`begin`<TIME_FORMAT(NOW(),'%H:%i:%s') AND
                                                    ".$this->tables['agencies_opening_hours'].".`end`>TIME_FORMAT(NOW(),'%H:%i:%s')")['id'];
                //если это не агрегатор и мы не попали во время работы, отправляем заявку в "ждущие опубликования"
                if(!empty($agency_in_time)){
                    
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
    * проверяем что заявка пришла клиенту в рабочие сутки. если нет - перемещаем в ожидающие запуска
    * 
    */
    public function checkWorkDay(){
        //если не в агентство или в агрегатор - считаем время рабочим
        if(!empty($this->id_agency) && empty($this->owners_array['is_agregator'])){
            global $db;
            //проверяем что для агентства вообще указано время, если не указано, считаем 24/7
            $agency_has_time = $db->fetch("SELECT id FROM ".$this->tables['agencies_opening_hours']." WHERE id_agency = ".$this->id_agency);
            if(!empty($agency_has_time)){
                $agency_in_time = $db->fetch("SELECT ".$this->tables['agencies'].".id
                                              FROM ".$this->tables['agencies']."
                                              LEFT JOIN ".$this->tables['agencies_opening_hours']." ON ".$this->tables['agencies'].".id = ".$this->tables['agencies_opening_hours'].".id_agency
                                              WHERE ".$this->tables['agencies'].".id = ".$this->id_agency." AND
                                                    ".$this->tables['agencies_opening_hours'].".applications_processing = 1 AND
                                                    ".$this->tables['agencies_opening_hours'].".day_num = WEEKDAY(NOW())+1")['id'];
                //если это не агрегатор и мы не попали во время работы, отправляем заявку в "ждущие опубликования"
                if(empty($agency_in_time)){
                    $this->status = 6;
                    $this->data_array['status'] = 6;
                    return false;
                }
            }
        }
        return true;
    }
    
    //получаем ближайшее время когда заявкам может быть взята в работу
    public function getNextWorkDayTime(){
        global $db;
        //если в агентство-не-агрегатор, смотрим время
        switch(true){
            case (!empty($this->id_agency) && empty($this->owners_array['is_agregator'])):
                $nearest_workday = Common::getAgencyNextWorkDay($this->id_agency,"a");
                if(!empty($nearest_workday)){
                    $week_day_title = $db->fetch("SELECT title_accusative FROM ".$this->tables['week_days']." WHERE id = ?",$nearest_workday['day_num'])['title_accusative'];
                    return "Заявка будет обработана в ".$week_day_title." после ".$nearest_workday['begin'];
                }
                break;
            case $this->owners_array['is_agregator'] == 1 || $this->id_parent == 0:
                return "";
                break;
        }
        return false;
    }
    
    private function getOwnersInfo(){
        return false;
    }

private function sendToInter($parameters){
        global $db;
        $info = $db->fetch("
                                SELECT 
                                    ".$this->tables['inter_estate'].".*
                                    , ".$this->tables['inter_regions'].".title as region_title
                                    , ".$this->tables['inter_currencies'].".title as currency_title
                                    , ".$this->tables['inter_countries'].".title as country_title                    
                                    , ".$this->tables['inter_countries'].".title_genitive as country_title_genitive                    
                                    , ".$this->tables['inter_type_objects'].".title as type_object_title                    
                                    , ".$this->tables['inter_cost_types'].".title as cost_type_title                    
                                    , ".$this->tables['inter_managers'].".email as manager_mail                    
                                FROM 
                                    ".$this->tables['inter_estate']."
                                LEFT JOIN ".$this->tables['inter_countries']." ON ".$this->tables['inter_countries'].".id = ".$this->tables['inter_estate'].".id_country
                                LEFT JOIN ".$this->tables['inter_countries_flags_photos']." ON ".$this->tables['inter_countries_flags_photos'].".id_parent = ".$this->tables['inter_countries'].".id
                                LEFT JOIN ".$this->tables['inter_regions']." ON ".$this->tables['inter_regions'].".id = ".$this->tables['inter_estate'].".id_region
                                LEFT JOIN ".$this->tables['inter_cost_types']." ON ".$this->tables['inter_cost_types'].".id = ".$this->tables['inter_estate'].".id_cost_type
                                LEFT JOIN ".$this->tables['inter_type_objects']." ON ".$this->tables['inter_type_objects'].".id = ".$this->tables['inter_estate'].".id_type_object
                                LEFT JOIN ".$this->tables['inter_currencies']." ON ".$this->tables['inter_currencies'].".id = ".$this->tables['inter_estate'].".id_currency
                                LEFT JOIN ".$this->tables['inter_managers']." ON ".$this->tables['inter_managers'].".id = ".$this->tables['inter_estate'].".id_manager
                                WHERE ".$this->tables['inter_estate'].".id = ?", $parameters['id']
        );
        Response::SetArray('info', $info);
        Response::SetArray('parameters', $parameters);
        ///если флаг установлен, отправляем письмо компании
        $mailer = new EMailer('mail');
        // формирование html-кода письма по шаблону
        $eml_tpl = new Template('/modules/applications/templates/mail.inter.html');
        $html = $eml_tpl->Processing();
        // перевод письма в кодировку мейлера
        $mail_text = iconv('UTF-8', $mailer->CharSet, $html);
        // параметры письма
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Новая заявка на зарубежную недвижимость от bsn.ru - ID ".$info['id'].", ".date('Y-m-d H:i:s'));
        $mailer->Body = $mail_text;
        $mailer->AltBody = $mail_text;
        $mailer->IsHTML(true);
        $mailer->AddAddress($info['manager_mail']);
        if($info['manager_mail']!='val@interestate.ru') $mailer->AddAddress('val@interestate.ru');
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
        // попытка отправить
        $mailer->Send();  
        return true;
    }    
}



/**
*   Класс для списка заявок
*/
class ApplicationList {
    private $tables;            //таблицы из Config
    private $where;             //условие по которому был сформирован список
    private $joined_tables;     //таблицы которые надо джойнить при запросе из фрагментов вида ('tablename','on field maintable','on field tablename','on condition')
    function __construct(){
        $this->tables = Config::$sys_tables;
        $this->where = "";
        $this->joined_tables = [];
    }
    /**
    * Формирование строки WHERE для sql запроса по массиву параметров
    * 
    * @param array массив условий (array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val),поле=>...)
    * @return string
    */
    public function makeWhereClause($clauses){
        $result = [];
        if(!is_array($clauses)) return '';
        foreach($clauses as $field=>$values){
            if(empty($clauses[$field]['checked'])){
                if(strpos($field,'#')) $field = substr($field,0,strpos($field,'#'));
                $result[] = $this->getClause($field, $values, $clauses);
            }
        }
        
        
        $this->where = $result;
        return implode(' AND ', $result);
    }
    
    /**
    * создаем условие для набора во вкладки
    * 
    * @param mixed $parameters
    */
    public function makeLkTabClause($parameters){
        $is_agregator = !empty($parameters['is_agregator'])?$parameters['is_agregator']:"";
        $only_user = !empty($parameters['only_user'])?$parameters['only_user']:"";
        $has_tarif = !empty($parameters['has_tarif'])?$parameters['has_tarif']:"";
        $apps_io = !empty($parameters['apps_io'])?$parameters['apps_io']:"";
        $common_user = !empty($parameters['common_user'])?$parameters['common_user']:"";
        $status = !empty($parameters['status'])?$parameters['status']:"";
        $users_id = !empty($parameters['users_id'])?$parameters['users_id']:"";
        if($apps_io == 'in'){
            if(!$common_user){
                switch($status){
                    case 'all':
                        if(!empty($only_user))
                            $result = $this->tables['applications'].".id_user IN (".($users_id).")";
                        else if($is_agregator)
                            $result = "((visible_to_all=1 AND ".$this->tables['applications'].".status=2 ) OR 
                                                     (".$this->tables['applications'].".id_user IN (".($users_id).") AND visible_to_all=2 AND ".$this->tables['applications'].".status!=2))";
                        else $result = "((visible_to_all IN (1,3) AND ".$this->tables['applications'].".status=2 ) OR (".$this->tables['applications'].".id_user IN (".($users_id).") AND visible_to_all=2))";
                        break;
                    case 'new':
                        if(!empty($only_user)) $result = "( ".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=2 )";
                        else if($is_agregator) $result = "(visible_to_all=1 AND ".$this->tables['applications'].".status=2)";
                        else $result = "((".$this->tables['applications'].".id_user IN (".($users_id).") OR visible_to_all IN (1,3)) AND ".$this->tables['applications'].".status=2)";
                        break;
                    case 'performing':
                        if(!empty($only_user)) $result = "( ".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=3 )";
                        else $result = "(".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=3)";
                        break;
                    case 'finished':
                        if(!empty($only_user)) $result = "( ".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=1 )";
                        else $result = "(".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=1)";
                        break;
                }
            } else{
                switch($status){
                    case 'all':
                        if($has_tarif) $result = $this->tables['applications'].".id_user IN (".($users_id).") OR (".$this->tables['applications'].".status = 2 AND ".$this->tables['applications'].".visible_to_all = 1)";
                        else $result = $this->tables['applications'].".id_user IN (".($users_id).")";
                        break;
                    case 'new':
                        if($has_tarif) 
                            $result = "(".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=2) OR 
                                       (".$this->tables['applications'].".status = 2 AND ".$this->tables['applications'].".visible_to_all = 1)";
                        else $result = "(".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=2)";
                        break;
                    case 'performing':
                        $result = "(".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=3)";
                        break;
                    case 'finished':
                        $result = "(".$this->tables['applications'].".id_user IN (".($users_id).") AND ".$this->tables['applications'].".status=1)";
                        break;
                }
            }
        }else{
            global $auth;
            switch($status){
                case 'all': $result = "id_initiator IN (".($auth->id).")";break;
                case 'new': $result = "(".$this->tables['applications'].".status=2 AND id_initiator IN (".($auth->id)."))";break;
                case 'performing': $result = "(id_initiator IN (".($auth->id).") AND ".$this->tables['applications'].".status=3)";break;
                case 'finished': $result = "(id_initiator IN (".($auth->id).") AND ".$this->tables['applications'].".status=1)";break;
            }
        }
        $this->where[] = $result;
        return $result;
    }
    
    private function getClause($field, $values, &$clauses){
        global $db;
        $fld_table = (!empty($values['tablename'])?$values['tablename']:$this->tables['applications']);
        $fld = empty($fld_table) ? $field : $fld_table.".`".$field."`";
        $result = $or_resullt = "";
        if(empty($values['checked'])){
            if(isset($values['value'])) $result = $fld." = ".$db->quoted($values['value']);
            elseif(isset($values['not_value'])) $result = $fld." != ".$db->quoted($values['not_value']);
            elseif(isset($values['set']) || isset($values['not_set'])) {
                $arr = [];  
                if(isset($values['not_set'])) $values['set'] = $values['not_set'];
                if(!is_array($values['set'])) $values['set'] = explode(',',$values['set']);
                foreach($values['set'] as $item) 
                    $arr[] = $db->quoted($item);
                $result = !empty($arr)?$fld.(isset($values['not_set'])?" NOT ":"")." IN (" . implode(',',$arr) . ')':"";
            }
            else {
                if(isset($values['from'])) $result = $fld." >= ".$db->quoted($values['from']);
                if(isset($values['to'])) $result = (empty($result)? "" : $result ." AND ") . $fld." <= ".$db->quoted($values['to']);
            }
            $clauses[$field]['checked'] = true;
            if(!empty($result) && !empty($values['or']) && !empty($clauses[$values['or']])){
                $or_resullt = $this->getClause($values['or'], $clauses[$values['or']], $clauses, $from_new);
            }
        }
        if(!empty($result) && !empty($or_resullt)) $result = "(".$result . (empty($or_resullt) ? "" : " OR ".$or_resullt).")";
        return $result;
    }
    
    /**
    * добавляем таблицы, которые надо будет джойнить при запросе
    * 
    * @param mixed $tablename
    * @param mixed $on_field
    * @param mixed $join_field
    */
    public function joinTable($tablename, $on_field = false, $join_field = false, $join_condition = false){
        if( empty($this->tables[$tablename]) || ((empty($on_field) || empty($join_field)) && empty($join_condition)) ) return false;
        array_push($this->joined_tables,array('tablename'=>$this->tables[$tablename],'on_field'=>$on_field,'join_field'=>$join_field,'join_condition'=>$join_condition));
        return true;
    }
    
    /**
    * читаем переданные поля для заявок этого списка, по флагу скрываем контакты
    * 
    * @param mixed $fields - набор полей, которые читаем (строка или массив с перечислением, включая имена таблиц)
    * @param mixed $orderby - сортировка, строка или массив, включая имена таблиц
    * @param mixed $limit  - лимиты
    * @param mixed $hide_phones - скрывать телефоны (true/false)
    * @param mixed $id_user - id пользователя (для показа в списке уже открытых телефонов)
    */
    public function getList($fields = false,$orderby = false, $limit = false, $hide_phones = true, $id_user = false, $check_objects = false){
        global $db;
        $joinings = "";
        if(count($this->joined_tables) > 0){
            $fields = (empty($fields)?"*":(is_array($fields)?implode(", ",$fields):$fields))." ";
            $orderby = (empty($orderby)?"":"ORDER BY ".(is_array($orderby)?implode(", ",$orderby):$orderby))." ";
            $limit = (empty($limit)?"":(" LIMIT ".$limit));
            foreach($this->joined_tables as $key=>$item){
                $joinings .= " LEFT JOIN ".$item['tablename']." ON ".(empty($item['join_condition'])?($this->tables['applications'].".".$item['on_field']." = ".$item['tablename'].".".$item['join_field']):$item['join_condition'])." ";
            }
        }
        
        $where = implode(' AND ',$this->where);
        //допоплнительное условие только для ЛК: убираем оригиналы скопированных заявок
        if(!empty($hide_phones)) $where = $where." AND ".$this->tables['applications'].".id NOT IN (SELECT id_parent_app FROM ".$this->tables['applications']." WHERE ".$where.")";
        
        $list = $db->fetchall("SELECT ".$fields." FROM ".$this->tables['applications']." ".$joinings." ".(empty($this->where)?"":" WHERE ".$where." ")." GROUP BY ".$this->tables['applications'].".id ".$orderby." ".$limit);
        //если установлен флаг, скрываем телефоны
        if(!empty($hide_phones)){
            //если указан пользователь, то некоторые телефолны могут быть открыты
            $opened_phones = [];$opened_phones_list = [];
            $opened_phones = AppsFunctions::getUserOpenedPhones($id_user);
            if(!empty($opened_phones)) $opened_phones_list = array_keys($opened_phones);
            
        
            foreach($list as $key=>$item){
                //собираем список телефонов с открытых заявок
                if($item['status'] == '3') $opened_phones[] = $list[$key]['phone'];
                if($item['status'] == '2')
                    if(!(in_array(trim($item['phone']),$opened_phones_list) && preg_match('/'.$item['estate_type'].'/',$opened_phones[trim($item['phone'])]['estate_types']))){
                        if(preg_match('/@/',$item['phone'])) $list[$key]['phone'] = 'XXX@'.explode('@',$item['phone'])[1];
                        else $list[$key]['phone'] = substr($item['phone'],0,11)." XX XX";
                        //скрываем почту, если есть
                        if(!empty($item['email']))
                            if(count(explode('@',$item['email'])) > 1) $list[$key]['email'] = 'XXX@'.explode('@',$item['email'])[1];
                            else $list[$key]['email'] = "";
                        
                    }
                //если проставлен флаг, проверяем есть ли объекты-цели заявок
                if(!empty($check_objects)){
                    if(!empty($item['id_parent'])){
                        //определяем или читаем тип недвижимости
                        if(empty($item['estate_alias'])){
                            switch(true){
                                case strstr($item['url'],'zhiloy_kompleks'): $item['estate_alias'] = "zhiloy_kompleks";break;
                                case strstr($item['url'],'cottages'): $item['estate_alias'] = "cottages";break;
                                case strstr($item['url'],'business_centers'): $item['estate_alias'] = "business_centers";break;
                                case strstr($item['url'],'build'): $item['estate_alias'] = "build";break;
                                case strstr($item['url'],'live'): $item['estate_alias'] = "live";break;
                                case strstr($item['url'],'country'): $item['estate_alias'] = "country";break;
                                case strstr($item['url'],'commercial'): $item['estate_alias'] = "commercial";break;
                                case strstr($item['url'],'promotions'): $item['estate_alias'] = "promotions";break;
                            }
                        }
                        if(!empty($item['estate_alias'])){
                            $list[$key]['target_object_status'] = $db->fetch("SELECT published FROM ".$this->tables[$item['estate_alias']]." WHERE id = ".$item['id_parent']);
                            //при отсутствии смотрим в архивной базе
                            if(!empty($this->tables[$item['estate_alias']."_archive"]) && empty($list[$key]['target_object_status']))
                                $list[$key]['target_object_status'] = $db->fetch("SELECT published FROM ".$this->tables[$item['estate_alias']."_archive"]." WHERE id = ".$item['id_parent']);
                            $list[$key]['target_object_status'] = (!empty($list[$key]['target_object_status'])?$list[$key]['target_object_status']['published']:0);
                        }
                    }else $item['target_object_status'] = 1;
                }
            }
        }
        
        return $list;
    }
    
    public function getPublicList($orderby = false, $limit = false, $hide_phones = true, $id_user = false, $check_objects = false ){
        $fields = $this->tables['applications'].".id,
                  ".$this->tables['applications'].".`datetime` AS date_normal,
                  ".$this->tables['applications'].".viewed,
                  ".$this->tables['applications'].".id_parent,
                  ".$this->tables['applications'].".user_comment,
                  ".$this->tables['owners_user_types'].".title AS user_type_title,
                  ".$this->tables['work_statuses'].".title AS work_status_title,
                  (".$this->tables['applications'].".visible_to_all = 3) AS free_for_payed,
                  (".$this->tables['applications'].".in_work_amount = 0 AND ".$this->tables['applications'].".visible_to_all != 3) AS can_be_exclusive,
                  DATE_FORMAT(".$this->tables['applications'].".`datetime`,'%e %M  %k:%i') AS date,
                  CONCAT(
                      '/',  
                      CASE
                        WHEN ".$this->tables['application_types'].".estate_type=1 THEN 'live'
                        WHEN ".$this->tables['application_types'].".estate_type=2 THEN 'build'
                        WHEN ".$this->tables['application_types'].".estate_type=3 THEN 'commercial'
                        WHEN ".$this->tables['application_types'].".estate_type=4 THEN 'country'
                        WHEN ".$this->tables['application_types'].".estate_type=5 THEN 'zhiloy_kompleks'
                        WHEN ".$this->tables['application_types'].".estate_type=6 THEN 'cottedzhnye_poselki'
                        WHEN ".$this->tables['application_types'].".estate_type=7 THEN 'business_centers'
                        WHEN ".$this->tables['application_types'].".estate_type=7 THEN 'offices'
                      END,
                      '/',
                      IF(".$this->tables['application_types'].".estate_type<5,
                          CONCAT(
                                  CASE 
                                    WHEN ".$this->tables['application_types'].".rent=1 THEN 'rent'
                                    WHEN ".$this->tables['application_types'].".rent=2 THEN 'sell'
                                  END,'/'
                                 ),
                      ''),
                      CASE                                                                                                         
                        WHEN ".$this->tables['application_types'].".estate_type<5 THEN ".$this->tables['applications'].".id_parent
                        WHEN ".$this->tables['application_types'].".estate_type=5 THEN ".$this->tables['housing_estates'].".chpu_title
                        WHEN ".$this->tables['application_types'].".estate_type=6 THEN ".$this->tables['cottages'].".chpu_title
                        WHEN ".$this->tables['application_types'].".estate_type=7 THEN ".$this->tables['business_centers'].".chpu_title
                      END,
                      '/'
                    ) AS url,
                    CASE
                        WHEN ".$this->tables['application_types'].".estate_type=1 THEN 'Жилая недвижимость'
                        WHEN ".$this->tables['application_types'].".estate_type=2 THEN 'Новостройки'
                        WHEN ".$this->tables['application_types'].".estate_type=3 THEN 'Коммерческая недвижимость'
                        WHEN ".$this->tables['application_types'].".estate_type=4 THEN 'Загородная недвижимость'
                        WHEN ".$this->tables['application_types'].".estate_type=5 THEN 'Жилые комплексы'
                        WHEN ".$this->tables['application_types'].".estate_type=6 THEN 'Коттеджные поселки'
                        WHEN ".$this->tables['application_types'].".estate_type=7 THEN 'Бизнес-центры'
                    END AS estate_type_title,
                  ".$this->tables['applications'].".name,
                  ".$this->tables['applications'].".phone,
                  ".$this->tables['applications'].".email,
                  IF(".$this->tables['application_objects'].".title IS NULL,'',".$this->tables['application_objects'].".title) AS object_type_title,
                  ".$this->tables['applications'].".status,
                  CASE
                        WHEN ".$this->tables['application_types'].".rent=1 THEN 'Аренда'
                        WHEN ".$this->tables['application_types'].".rent=2 THEN 'Покупка'
                        WHEN ".$this->tables['application_types'].".rent=3 THEN 'Сдам'
                        WHEN ".$this->tables['application_types'].".rent=4 THEN 'Продажа'
                  END AS rent,
                  CASE
                        WHEN ".$this->tables['application_types'].".rent=1 THEN 'rent'
                        WHEN ".$this->tables['application_types'].".rent=2 THEN 'buy'
                        WHEN ".$this->tables['application_types'].".rent=3 THEN 'hire'
                        WHEN ".$this->tables['application_types'].".rent=4 THEN 'sell'
                  END AS rent_title,
                  IF(visible_to_all = 1,
                     CAST(".$this->tables['application_types'].".cost AS SIGNED) - 
                     FLOOR(
                        IF(".$this->tables['applications'].".id_parent = 0,
                     CAST(TIMESTAMPDIFF(DAY,".$this->tables['applications'].".`datetime`,NOW()) AS SIGNED),
                     CAST(TIMESTAMPDIFF(DAY,".$this->tables['applications'].".`datetime`,DATE_SUB(NOW(),INTERVAL 12 HOUR)) AS SIGNED))*
                        ".$this->tables['application_types'].".day_discount*0.01*".$this->tables['application_types'].".cost +
                        CAST(".$this->tables['applications'].".in_work_amount*
                     ".$this->tables['application_types'].".client_discount*0.01*".$this->tables['application_types'].".cost AS SIGNED)
                     ),
                     0
                  )AS cost,
                  ".$this->tables['housing_estates'].".build_complete,
                  IF(visible_to_all = 1,
                     ".$this->tables['application_types'].".exclusive_cost,
                     0) AS exclusive_cost,
                  
                  IF(".$this->tables['applications'].".id_owner = ".$id_user." AND ".$this->tables['applications'].".id_owner > 0,1,0) AS user_object,
                  ".$this->tables['applications'].".estate_type";      
                  
        $this->joinTable('work_statuses','id_work_status','id');
        $this->joinTable('owners_user_types','id_user_type','id');
        $this->joinTable('application_types','application_type','id');
        $this->joinTable('application_objects',false,false,"(".$this->tables['application_objects'].".id = ".$this->tables['applications'].".object_type OR 
                                                                      ".$this->tables['applications'].".object_type = 0 AND ".$this->tables['applications'].".id_parent!=0) AND 
                                                                      ".$this->tables['application_objects'].".estate_type = ".$this->tables['applications'].".estate_type");
        $this->joinTable('housing_estates',false,false,$this->tables['applications'].".id_parent = ".$this->tables['housing_estates'].".id AND ".$this->tables['applications'].".estate_type = 5");
        $this->joinTable('cottages',false,false,$this->tables['applications'].".id_parent = ".$this->tables['cottages'].".id AND ".$this->tables['applications'].".estate_type = 6");
        $this->joinTable('business_centers',false,false,$this->tables['applications'].".id_parent = ".$this->tables['business_centers'].".id AND ".$this->tables['applications'].".estate_type = 7");
                    
        
        return $this->getList($fields, $orderby, $limit, $hide_phones, $id_user, $check_objects);
    }
    
    public function getPaginatorCondition(){
        global $db;
        $joinings = "";
        if(count($this->joined_tables) > 0)
            foreach($this->joined_tables as $key=>$item){
                if(!empty($item['on_field']) && !empty($item['join_field']))
                    $joinings .= " LEFT JOIN ".$item['tablename']." ON ".$this->tables['applications'].".".$item['on_field']." = ".$item['tablename'].".".$item['join_field']." ";
                elseif(!empty($item['join_condition']))
                    $joinings .= " LEFT JOIN ".$item['tablename']." ON ".$item['join_condition'];
            }
        return $this->tables['applications']." ".$joinings;
    }
    
}

/**
* класс для всяких нужных методов для заявок
*/
abstract class AppsFunctions{
    
    /**
    * по id пользователя получаем список открытых телефонов
    * 
    * @param mixed $id_user
    */
    public static function getUserOpenedPhones($id_user){
        global $db;
        if(empty($id_user)) return false;
        return $db->fetchall("SELECT phone, GROUP_CONCAT(estate_type) AS estate_types
                              FROM ".Config::$sys_tables['applications']."
                              WHERE id_user = ".$id_user." AND ( status = 3 OR status = 1 )
                              GROUP BY phone",'phone');
    }
    
    /**
    * (теперь не убираем в архив, а переносим в "видимые только платным") заявки, у которых цена стала <= 0 из-за скидок
    * 
    */
    public static function removeDepreciated(){
        global $db;
        $sys_tables = Config::$sys_tables;
        $where = "(CAST(".$sys_tables['application_types'].".cost AS SIGNED) - 
                                                               FLOOR(
                                                               IF(".$sys_tables['applications'].".id_parent = 0,
                                                                          CAST(TIMESTAMPDIFF(DAY,".$sys_tables['applications'].".`datetime`,NOW()) AS SIGNED),
                                                                          CAST(TIMESTAMPDIFF(DAY,".$sys_tables['applications'].".`datetime`,DATE_SUB(NOW(),INTERVAL 12 HOUR)) AS SIGNED))*
                                                               ".$sys_tables['application_types'].".day_discount*0.01*".$sys_tables['application_types'].".cost + 
                                                               CAST(".$sys_tables['applications'].".in_work_amount*
                                                               ".$sys_tables['application_types'].".client_discount*0.01*".$sys_tables['application_types'].".cost AS SIGNED)
                                                               ))<=0 AND 
                  ".$sys_tables['applications'].".status = 2 AND ".$sys_tables['applications'].".visible_to_all = 1";
        //делаем видимыми только платным клиентам те, которые от 5 до 10 дней
        $res = $db->querys("UPDATE ".$sys_tables['applications']." 
                           LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['application_types'].".id = ".$sys_tables['applications'].".application_type
                           SET visible_to_all = 3
                           WHERE ".$where);
        return $res;
    }
    
    /**
    * читаем стоимость заявки на SALE (если это возможно) для агентства по id_user
    * 
    * @param mixed $id_user
    */
    public static function getSaleAppCost($id_user){
        if(empty($id_user)) return false;
        global $db;
        $sys_tables = Config::$sys_tables;
        $res =  $db->fetch("SELECT ".$sys_tables['sale_agencies'].".application_cost
                            FROM ".$sys_tables['application_agencies_sale']."
                            LEFT JOIN ".$sys_tables['sale_agencies']." ON ".$sys_tables['sale_agencies'].".id = ".$sys_tables['application_agencies_sale'].".agency_id_sale
                            LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['application_agencies_sale'].".agency_id_bsn
                            WHERE ".$sys_tables['users'].".id = ".$id_user)['application_cost'];
        return (!empty($res)?$res:0);
    }
    
    /**
    * просматриваем ожидающие публикации, достаем нужные
    * 
    * @param mixed $days - флаг того как смотрим - с учетом времени работы(false) или только с учетом суток(true)
    */
    public static function publishWaiting($days = false){
        global $db;
        $sys_tables = Config::$sys_tables;
        //читаем ожидающие публикации у которых подошло время
        $waiting_list = $db->fetchall("SELECT ".$sys_tables['applications'].".id
                                       FROM ".$sys_tables['applications']."
                                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['applications'].".id_owner = ".$sys_tables['users'].".id
                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                       LEFT JOIN ".$sys_tables['agencies_opening_hours']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['agencies_opening_hours'].".id_agency
                                       WHERE ".$sys_tables['applications'].".status = 6 AND
                                             ".$sys_tables['agencies_opening_hours'].".day_num = WEEKDAY(NOW())+1 AND
                                             applications_processing = 1
                                             ".(empty($days)?" AND ".$sys_tables['agencies_opening_hours'].".`begin`<TIME_FORMAT(NOW(),'%H:%i:%s') AND
                                             ".$sys_tables['agencies_opening_hours'].".`end`>TIME_FORMAT(NOW(),'%H:%i:%s')":"")
                                     );
        $res = true;
        //публикуем готовые
        foreach($waiting_list as $key=>$item){
            $new_app = new Application($item['id'],null,null);
            $res = $res && $new_app->toPublished();
            $new_app->sendNewAppNotifications();
            unset($new_app);
        }
        return $res;
    }
    
    public static function refreshSaleAgencies(){
        global $db;
    }
   
}
?>
