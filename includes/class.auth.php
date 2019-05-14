<?php
/**    
* Authorization
*/
//require_once('includes/class.favorites.php');
if(!defined("COOKIE_SAVE_DAYS")) define("COOKIE_SAVE_DAYS", 30);

class Auth {
    private $users_table = "";
    private $photos_table = "";
    private $groups_table = "";
    private $agencies_table = "";
    private $users_restore_table = "";

    public $authorized = false;
    public $auth_trying = false;
    public $id = 0;
    public $passwd = "";
    public $name = ""; // user name
    public $login = ""; 
    public $lastname = "";
    public $id_agency = 0;
    public $id_group = 0;         
    public $activity = 0;       //activity агентства
    public $user_activity = 0;  //activity пользователя
    public $email = "";
    public $phone = "";
    public $user_photo = "";
    public $avatar_color = "";
    public $sex = "";
    public $photo = "";
    public $balance = 0;
    public $social_data = [];
    public $id_tarif = 0;
    public $tarif_title = "";
    public $packet_title = "";
    public $active_objects = 0;
    public $promo_left = 0;
    public $premium_left = 0;
    public $vip_left = 0;
    public $agency_title = "";
    public $agency_chpu_title = "";
    public $agency_admin = 2;
    public $agency_id_tarif = "";
    public $agency_promo = 0;
    public $agency_premium = 0;
    public $agency_vip = 0;
    public $agency_staff_number = 0;
    public $agency_phone = "";
    public $agency_business_center = false;
    public $subscribe_news = false;
    public $session_delay = 600; //session length, minutes
    public $session_hash = false;
    public $CookieSave = false;
    public $CookieName = 'sitecookie';
    public $user_rights = [];
    public $group_rights = [];
    public $comm_warnings = 0;
    public $live_sell_objects = 0;
    public $live_rent_objects = 0;
    public $build_objects = 0;
    public $commercial_sell_objects = 0;
    public $commercial_rent_objects = 0;
    public $country_sell_objects = 0;
    public $country_rent_objects = 0;
    public $expert = 2;
    public $sys_tables = [];

    public function __construct(){
        $host = getenv("HTTP_HOST") ? getenv("HTTP_HOST") : getenv("SERVER_NAME");
        $this->CookieName = 'au_'.sha1(DEBUG_MODE ? '.bsn.int' : '.bsn.ru');
        Config::Init();
        $this->users_table = Config::$sys_tables['users'];
        $this->groups_table = Config::$sys_tables['users_groups'];
        $this->photos_table = Config::$sys_tables['users_photos'];
        $this->agencies_table = Config::$sys_tables['agencies'];
        $this->tarifs_table = Config::$sys_tables['tarifs'];
        $this->packets_table = Config::$sys_tables['tarifs_agencies'];
        $this->users_restore_table = Config::$sys_tables['users_restore'];
        $this->sys_tables = Config::$sys_tables;
        
    }
        
    public function checkAuth($login='', $password='', $cookie_save=false, $logoff=false){
        return $this->AuthCheck($login, $password, $cookie_save, $logoff);
    }
    public function checkAuthSocial($social_data){
        $this->social_data = array('field'=>$social_data['field'],'value'=>$social_data['value']) ;
        return $this->AuthCheck(false, false, true, false, $this->social_data);
    }
    public function checkSuperAdminAuth($id){
        return $this->AuthCheck(false, false, false, false, false, $id);
    }
    
 
    public function isAuthorized(){
        return $this->authorized;
    }

    /**
    * @desc чистим куки
    */
    private function ClearCookiesData() {
        Cookie::SetCookie($this->CookieName, '', -3600, '/', DEBUG_MODE ? '.bsn.int' : '.bsn.ru');
    }
    /**
    * @desc запись юзера в куки
    */
    private function SetDataToCookies() {
        $auth = array(
            'user_email'=>$this->email,
            'user_phone'=>$this->phone,
            'user_login'=>$this->login,
            'hash_password'=>$this->passwd,
            'cookie_save'  => $this->CookieSave
        );
        Cookie::SetCookie($this->CookieName, $auth, 60*60*24*COOKIE_SAVE_DAYS, '/', DEBUG_MODE ? '.bsn.int' : '.bsn.ru');
    }
    
    /**
    * @desc запись юзера в сессию
    */
    private function SetDataToSession() {
        $auth = array(  
            'user_email' => $this->email,
            'user_phone' => $this->phone,
            'user_login' => $this->login,
            'hash_password' => $this->passwd,
            'social_field' => !empty($this->social_data['field'])?$this->social_data['field']:false,
            'social_value' => !empty($this->social_data['value'])?$this->social_data['value']:false,
            'cookie_save'  => $this->CookieSave
        );
        Session::SetArray('auth_data', $auth );
    }
        
    /**
    * @desc проверка авторизации
    */
    public function AuthCheck($login='', $password='', $cookie_save=false, $logoff=false, $social_data = false, $super_admin_id = false){
        // если просят разлогинить - разлогиниваем и всё
        $logoff = $logoff || Request::GetParameter('logoff',METHOD_POST);
        $logoff = $logoff || Request::GetParameter('logoff',METHOD_GET);
        if(!empty($logoff)){
			$this->Logoff();
        } else {
            // если есть ID суперадмина - берем информацию из базы и авторизуем
            if( !empty($super_admin_id) ){
                if($this->Login(false, false, false, false, true, false, $super_admin_id)) {
                    $this->SetDataToSession();
                    $this->SetDataToCookies();
                    return true;
                }
            // если есть куки - берем инфо из куков и на его основе авторизуем и создаем сессию
            } elseif($auth = Cookie::GetArray($this->CookieName)){
                 if($this->Login($auth['user_email'],$auth['user_phone'],$auth['user_login'],$auth['hash_password'],$auth['cookie_save'])) {
                    $this->SetDataToSession();
                    $this->SetDataToCookies();
                    return true;
                }
            
            // если есть сессия - берем информацию из сессии и на её основе авторизуем
            } else if($auth = Session::GetParameter('auth_data')){
                if(!empty($auth['social_field']) && !empty($auth['social_value'])) $this->social_data = array('field'=>$auth['social_field'],'value'=>$auth['social_value']) ;
                if($this->Login($auth['user_email'],$auth['user_phone'],$auth['user_login'],$auth['hash_password'],$auth['cookie_save'],$this->social_data)) {
					$this->SetDataToSession();
                    $this->SetDataToCookies();
                    return true;
                }
            }
            if(!$this->isAuthorized()){
                // если пустые данные - пытаемся получить их из формы
                if(empty($login)) $login = Request::GetString('auth_login', METHOD_POST);
                if(empty($password)) $password = Request::GetString('auth_passwd', METHOD_POST);
                if(empty($cookie_save)){
                    $cookie_save = Request::GetString('auth_cookie_save', METHOD_POST);
                    $cookie_save = !(empty($cookie_save) && $cookie_save == "false");
                }
                if(!empty($login) || !empty($password)) $this->auth_trying = true;
                $email = $phone = '';
                // проверка логина
                if(Validate::isEmail($login)){
                    $email = $login; // это почтовый адрес
                } else {
                    $phone_login = preg_replace('![^0-9]!','', $login);
                    if(strlen($phone_login)>=10){
                        $phone = substr($phone_login,-10); // это мобильный телефон
                    }
                }
                if((empty($password) || strlen($password)<3) && empty($social_data)) {
                    return false;
                }
                if($this->Login($email,$phone,$login,sha1($password),$cookie_save, $social_data, $super_admin_id)){
                    $this->SetDataToSession();
                    $this->SetDataToCookies();
                    return true;
                }
            }
        }
        return false;
    }

    private function SaveIpToBase($user_id){
        global $db;
        if(empty($user_id)) return false;
        //если с такого ip уже заходил этот пользователь, просто обновляем дату
        $res = true;
        $user_ip = Host::getUserIp();
        
        $userip_exists = $db->fetch("SELECT id FROM ".$this->sys_tables['users_ips']." WHERE ip = ? AND id_user = ?",$user_ip,$user_id);
        if(empty($userip_exists) && empty($userip_exists['id'])) $res = $res && $db->query("INSERT IGNORE INTO ".$this->sys_tables['users_ips']." (id_user,ip,date_enter) VALUES (?,?,NOW())",$user_id,$user_ip);
        else $res = $res && $db->query("UPDATE ".$this->sys_tables['users_ips']." SET date_enter = NOW() WHERE id = ?",$userip_exists['id']);
        
        
        $ip_exists = $db->fetch("SELECT id FROM ".$this->sys_tables['ip_geodata']." WHERE ip = ?",$user_ip);
        if(empty($ip_exists)) $res = $res && $db->query("INSERT IGNORE INTO ".$this->sys_tables['ip_geodata']." (ip) VALUES (?)",$user_ip);
        return $res;
    }
    
    /**
    * Процедура залогинивания
    * @param string $email
    * @param string $phone
    * @param string $hash_password - hach(password)
    * @param boolean $save_in_cookie
    * @return 
    */
    private function Login($email, $phone, $login, $hash_password, $save_in_cookie=false, $social_data = false, $super_admin_id = false){
        global $db;
        if( empty($email) && empty($phone) && empty($login) && empty($hash_password) && empty($social_data) && empty($super_admin_id) ) return false;
        $where_auth = $where = [];
        if(!empty($social_data)){
            $where = $social_data['field']." = ".$social_data['value']." AND ".$this->users_table.".`status` = 1 ";    
        } else {
            if(!empty($email)) $where_auth[] = $this->users_table.".`email` = '" . $db->real_escape_string($email). "'";
            if(!empty($phone)) $where_auth[] = $this->users_table.".`phone` = '" . $db->real_escape_string($phone). "'";
            if(!empty($login)) $where_auth[] = $this->users_table.".`login` = '" . $db->real_escape_string($login). "'";
            if(!empty($where_auth)) $where[] = " ( " . implode(" OR ", $where_auth) . " ) ";                                            
            if(!empty($hash_password)) $where[] = $this->users_table.".`passwd` = '" . sha1($db->real_escape_string($hash_password)) . "'";
            $where[] = $this->users_table.".`status` = 1";
            if(!empty($super_admin_id)) $where[] = $this->users_table.".`id` = ".$super_admin_id;
            $where = implode (" AND ", $where);
        }
        
        $sql = "SELECT
                    ".$this->agencies_table.".*,
                    ".$this->users_table.".*,
                    IF(YEAR(".$this->users_table.".`tarif_end`) > 0,DATE_FORMAT(".$this->users_table.".`tarif_end`,'%e.%m.%y'), '0') as tarif_end,
                    IF(YEAR(".$this->agencies_table.".`tarif_end`) > 0,DATE_FORMAT(".$this->agencies_table.".`tarif_end`,'%e.%m.%y'), '0') as agency_tarif_end,
                    ".$this->agencies_table.".`title` as `agency_title`,
                    ".$this->tarifs_table.".`active_objects` as `active_objects`,
                    ".$this->tarifs_table.".`title` as `tarif_title`,
                    ".$this->packets_table.".`title` as `packet_title`,
                    ".$this->agencies_table.".`phones` as `agency_phone`,
                    ".$this->agencies_table.".`id_tarif` as `agency_id_tarif`,
                    ".$this->groups_table.".`access` as `group_access`,
                    ".$this->photos_table.".`name` as `user_photo`, 
                    LEFT (".$this->photos_table.".`name`,2) as `photo_subfolder`,
                    ".$this->tarifs_table.".promo_available,
                    ".$this->tarifs_table.".premium_available,
                    ".$this->tarifs_table.".vip_available
                FROM ".$this->users_table."
                LEFT JOIN ".$this->tarifs_table." on ".$this->tarifs_table.".`id` = ".$this->users_table.".`id_tarif`
                LEFT JOIN ".$this->agencies_table." on ".$this->agencies_table.".`id` = ".$this->users_table.".`id_agency`
                LEFT JOIN ".$this->groups_table." on ".$this->groups_table.".`id` = ".$this->users_table.".`id_group`
                LEFT JOIN ".$this->photos_table." ON ".$this->photos_table.".id = ".$this->users_table.".id_main_photo
                LEFT JOIN ".$this->packets_table." on ".$this->packets_table.".`id` = ".$this->agencies_table.".`id_tarif` AND ".$this->agencies_table.".`id_tarif` > 0
                WHERE ".$where;

        $res = $db->fetch($sql);
        if(!empty($res)){
            if($res['is_blocked'] == 1) return false;
            $this->CookieSave = $save_in_cookie;
            $this->id = $res['id'];
            $this->passwd = $hash_password;
            $this->login = $res['login'];
            $this->name = $res['name'];
            $this->lastname = $res['lastname'];
            $this->email = $res['email'];
            $this->phone = (Validate::isPhone($res['phone'])?$res['phone']:"");
            $this->id_user_vk = $res['id_user_vk'];
            $this->id_user_facebook = $res['id_user_facebook'];
            $this->balance = $res['balance'];
            $this->subscribe_news = $res['subscribe_news'];
            $this->id_agency = $res['id_agency'];
            $this->id_group = $res['id_group'];
            $this->activity = $res['activity'];
            $this->user_activity = $res['user_activity'];
            $this->comm_warnings = $res['comm_warnings'];
            $this->comm_count = $res['comm_count'];
            $this->user_photo = !empty($res['user_photo']) ? $res['photo_subfolder'].'/'.$res['user_photo'] : '';
            $this->avatar_color = $res['avatar_color'];
            $this->sex = $res['sex'];
            $this->id_tarif = $res['id_tarif'];
            $this->tarif_title = $res['tarif_title'];
            $this->active_objects = $res['active_objects'];
            $this->promo_left = $res['promo_left'];
            $this->premium_left = $res['premium_left'];
            $this->vip_left = $res['vip_left'];
            $this->agency_chpu_title = $res['chpu_title'];
            $this->agency_admin = $res['agency_admin'];
            $this->agency_id_tarif = $res['agency_id_tarif'];
            $this->agency_promo = $res['promo'];
            $this->agency_premium = $res['premium'];
            $this->agency_vip = $res['vip'];
            $this->promo = $res['promo'];
            $this->agency_business_center = $res['business_center'] == 1 ? 1 : false;
            $this->agency_staff_number = $res['staff_number'];
            $this->agency_title = $res['agency_title'];
            $this->agency_phone = $res['agency_phone'];
            $this->live_sell_objects = $res['live_sell_objects'];
            $this->live_rent_objects = $res['live_rent_objects'];
            $this->build_objects = $res['build_objects'];
            $this->commercial_sell_objects = $res['commercial_sell_objects'];
            $this->commercial_rent_objects = $res['commercial_rent_objects'];
            $this->country_sell_objects = $res['country_sell_objects'];
            $this->country_rent_objects = $res['country_rent_objects'];
            $this->expert = $res['expert'];
            $res['user_photo_folder'] = Config::Get('img_folders/users');
            Response::SetArray('auth_data',$res);
            
            //пишем ip в базу
            $this->SaveIpToBase($this->id);
            // разбор пользовательских прав доступа
            $this->user_rights = [];
            if(!empty($res['access']) && preg_match_all('!([\S]+)\s+([\S]+)!m', $res['access'], $matches, PREG_SET_ORDER)){
                foreach($matches as $match) {
                    $this->user_rights[] = array(
                        'path'=>$match[1],
                        'rights'=>$match[2]
                    );
                }
            }
            // разбор групповых прав доступа
            $this->group_rights = [];
            if(!empty($res['group_access']) && preg_match_all('!([\S]+)\s+([\S]+)!m', $res['group_access'], $matches, PREG_SET_ORDER)){
                foreach($matches as $match) {
                    $this->group_rights[] = array(
                        'path'=>$match[1],
                        'rights'=>$match[2]
                    );
                }
            }
            
            $db->query("UPDATE ".$this->users_table." SET `last_enter` = NOW() WHERE `id` =".$this->id) or die($db->error);
            $this->authorized = true;
            
            Favorites::Init();
            
            
            
            $cook = Cookie::GetArray('favorites');
            if (!empty($cook))
                Favorites::FromCookieToBase();   
            return true;
        }
        return false;
    }    
    
    public function logout(){
		$this->Logoff();
	}

	public function Logoff(){
        $this->authorized = false;
        $this->id = 0;
        $this->passwd = "";
        $this->email = "";
        $this->phone = "";
        $this->group_rights = $this->user_rights = [];
		$this->SetDataToSession();
        $this->ClearCookiesData();
        Host::Redirect('/');
    }
    /**
        * Проверка промо-кода
        * @param string $promocode
        * @param string $summ
        * @return 
    */   
    public function checkPromocode($promocode = false, $id = false, $id_user = false){ 
        global $db;
        $item = $db->fetch(
            "SELECT ".$this->sys_tables['promocodes'].".*,
                    ".$this->sys_tables['promocodes_used'].".id_user,
                    ".$this->sys_tables['promocodes_used'].".datetime
             FROM ".$this->sys_tables['promocodes']."
             LEFT JOIN ".$this->sys_tables['promocodes_used']." ON ".$this->sys_tables['promocodes_used'].".id_parent = ".$this->sys_tables['promocodes'].".id AND ".$this->sys_tables['promocodes_used'].".id_user = ?
             WHERE  
                ".(!empty($promocode) ? $this->sys_tables['promocodes'].".code = '".$db->real_escape_string($promocode)."' AND ": "")."
                ".(!empty($id) ? $this->sys_tables['promocodes'].".id = ".$db->real_escape_string($id)." AND ": "")."
                ".$this->sys_tables['promocodes'].".date_end > CURDATE() AND 
                ".$this->sys_tables['promocodes'].".date_start <= CURDATE()
            ", !empty($id_user) ? $id_user : $this->id
        );
        return $item;
    }
}
?>