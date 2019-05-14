<?php
/**    
* Статистическая информация по объектам недвижимости
*/

if(!defined('TYPE_ESTATE_LIVE')) define('TYPE_ESTATE_LIVE', 'live');
if(!defined('TYPE_ESTATE_LIVE_SELL')) define('TYPE_ESTATE_LIVE_SELL', 'live_sell');
if(!defined('TYPE_ESTATE_LIVE_RENT')) define('TYPE_ESTATE_LIVE_RENT', 'live_rent');
if(!defined('TYPE_ESTATE_BUILD')) define('TYPE_ESTATE_BUILD', 'build');
if(!defined('TYPE_ESTATE_COMMERCIAL')) define('TYPE_ESTATE_COMMERCIAL', 'commercial');
if(!defined('TYPE_ESTATE_COMMERCIAL_SELL')) define('TYPE_ESTATE_COMMERCIAL_SELL', 'commercial_sell');
if(!defined('TYPE_ESTATE_COMMERCIAL_RENT')) define('TYPE_ESTATE_COMMERCIAL_RENT', 'commercial_rent');
if(!defined('TYPE_ESTATE_COUNTRY')) define('TYPE_ESTATE_COUNTRY', 'country');
if(!defined('TYPE_ESTATE_COUNTRY_SELL')) define('TYPE_ESTATE_COUNTRY_SELL', 'country_sell');
if(!defined('TYPE_ESTATE_COUNTRY_RENT')) define('TYPE_ESTATE_COUNTRY_RENT', 'country_rent');
if(!defined('TYPE_ESTATE_LIVE_MODERATION')) define('TYPE_ESTATE_LIVE_MODERATION', 'live_new');
if(!defined('TYPE_ESTATE_BUILD_MODERATION')) define('TYPE_ESTATE_BUILD_MODERATION', 'build_new');
if(!defined('TYPE_ESTATE_COMMERCIAL_MODERATION')) define('TYPE_ESTATE_COMMERCIAL_MODERATION', 'commercial_new');
if(!defined('TYPE_ESTATE_COUNTRY_MODERATION')) define('TYPE_ESTATE_COUNTRY_MODERATION', 'country_new');
if(!defined('TYPE_ESTATE_LIVE_DRAFT')) define('TYPE_ESTATE_LIVE_DRAFT', 'live_draft');
if(!defined('TYPE_ESTATE_BUILD_DRAFT')) define('TYPE_ESTATE_BUILD_DRAFT', 'build_draft');
if(!defined('TYPE_ESTATE_COMMERCIAL_DRAFT')) define('TYPE_ESTATE_COMMERCIAL_DRAFT', 'commercial_draft');
if(!defined('TYPE_ESTATE_COUNTRY_DRAFT')) define('TYPE_ESTATE_COUNTRY_DRAFT', 'country_draft');

class EstateStat {
    private static $cache_result_time = 600; //sec
        
    /**
    * Кол-во объектов
    * @param mixed ID пользователя
    * @param mixed ID агентства
    * @param mixed тип объектов
    * @param mixed выводить из memcache
    */
    public static function getCount($user_id=NULL, $agency_id=NULL, $estate_type=NULL, $memcache_data=true, $deal_type=false, $only_count = false){
        global $db, $auth, $memcache;
        $sig = 'EstateStat::getCount('.(empty($user_id)?"NULL":$user_id).",".(empty($agency_id)?"NULL":$agency_id).",".(empty($estate_type)?"NULL":$estate_type).')';
        if($memcache_data){
            $ret = $memcache->get($sig);
        }
        if(empty($ret) || empty($memcache_data)){
            if($memcache_data){
                $memcache->lock($sig,3);
            }
            $ret = array(
                'published'     => array('live','build','commercial','country'),
                'archive'       => array('live','build','commercial','country'),
                'moderation'    => array('live','build','commercial','country'),
                'draft'         => array('live','build','commercial','country'),
                'payed'         => 0,
                'balance'       => 0
            );
            if(!empty($agency_id) && ($auth->agency_admin == 1 || !empty($only_count))){
                //2 типа аккаунтов - пакет и тариф
                $objects_type = array('packet', 'object');
                foreach($objects_type as $type){
                    $where = [];
                    if(!empty($agency_id)) {
                        $row = $db->fetchall("SELECT id FROM ".Config::$sys_tables['users']." WHERE id_agency = ? AND id_tarif ".($type == 'packet'?"=0 ":">0 "), 'id', $agency_id);
                        if(!empty($row)) $where[] = "id_user IN (".implode(',',array_keys($row)).")";
                    }
                    if(empty($agency_id)){
                        switch($deal_type){
                            case 'rent':
                                $where[] = "rent = 1";
                                break;
                            case 'sell':
                                $where[] = "rent = 2";
                                break;
                        }
                    }
                    $where_str = empty($where) ? "" : " WHERE ".implode(" AND ",$where);
                    if(!empty($where_str)){
                        $selects = array(
                            TYPE_ESTATE_LIVE => "(SELECT IFNULL(COUNT(*),0) as cnt, 'live' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['live'].$where_str." GROUP BY published, status)",
                            TYPE_ESTATE_COMMERCIAL => "(SELECT IFNULL(COUNT(*),0) as cnt, 'commercial' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['commercial'].$where_str." GROUP BY published, status)",
                            TYPE_ESTATE_BUILD => "(SELECT IFNULL(COUNT(*),0) as cnt, 'build' as type, published, status, payed_status FROM ".Config::$sys_tables['build'].$where_str." GROUP BY published, status, payed_status)",
                            TYPE_ESTATE_COUNTRY => "(SELECT IFNULL(COUNT(*),0) as cnt, 'country' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['country'].$where_str." GROUP BY published, status)",
                            
                            TYPE_ESTATE_LIVE_SELL => "(SELECT IFNULL(COUNT(*),0) as cnt, 'live_sell' as type, published, status, payed_status FROM ".Config::$sys_tables['live'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 2 GROUP BY published, status, payed_status)",
                            TYPE_ESTATE_COMMERCIAL_SELL => "(SELECT IFNULL(COUNT(*),0) as cnt, 'commercial_sell' as type, published, status, payed_status FROM ".Config::$sys_tables['commercial'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 2 GROUP BY published, status, payed_status)",
                            TYPE_ESTATE_COUNTRY_SELL => "(SELECT IFNULL(COUNT(*),0) as cnt, 'country_sell' as type, published, status, payed_status FROM ".Config::$sys_tables['country'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 2 GROUP BY published, status, payed_status)",
                            
                            TYPE_ESTATE_LIVE_RENT => "(SELECT IFNULL(COUNT(*),0) as cnt, 'live_rent' as type, published, status, payed_status FROM ".Config::$sys_tables['live'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 1 GROUP BY published, status, payed_status)",
                            TYPE_ESTATE_COMMERCIAL_RENT => "(SELECT IFNULL(COUNT(*),0) as cnt, 'commercial_rent' as type, published, status, payed_status FROM ".Config::$sys_tables['commercial'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 1 GROUP BY published, status, payed_status)",
                            TYPE_ESTATE_COUNTRY_RENT => "(SELECT IFNULL(COUNT(*),0) as cnt, 'country_rent' as type, published, status, payed_status FROM ".Config::$sys_tables['country'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 1 GROUP BY published, status, payed_status)",
                        );
                        if(empty($estate_type)) {
                            $result = [];
                            $big_sql = implode('UNION',$selects);
                            $result = $db->fetchall($big_sql);
                        } else {
                            $sql = $selects[$estate_type];
                            $result = $db->fetchall($sql);
                        }
                        
                        if(!empty($result)){
                            foreach($result as $row){
                                $index = (strstr($row['type'],'_sell') != '' ? "_sell" : (strstr($row['type'],'_rent') != '' ? "_rent" : ""));
                                
                                if(!($type == 'tarif' && !empty($index)) || $type == 'packet'){
                                    if($row['published'] == 1) {
                                        $ret['published'.$index][$row['type']] = $row['cnt'] + (!empty($ret['published'.$index][$row['type']])?$ret['published'.$index][$row['type']]:0);
                                        if($row['payed_status'] == 1){
                                            $ret['payed'] += $row['cnt'];
                                            $ret['published'.$index][$row['type']."_payed"] = $row['cnt'] + (!empty($ret['published'.$index][$row['type']."_payed"]) ? $ret['published'.$index][$row['type']."_payed"] : 0);
                                        }elseif($row['payed_status'] == 2) 
                                            $ret['published'.$index][$row['type']."_free"] = $row['cnt'] + (!empty($ret['published'.$index][$row['type']."_free"]) ? $ret['published'.$index][$row['type']."_free"] : 0);
                                    }
                                    elseif($row['published'] == 2) $ret['archive'.$index][$row['type']] = $row['cnt'] +  (!empty($ret['archive'.$index][$row['type']])?$ret['archive'.$index][$row['type']]:0);  
                                    elseif($row['published'] == 3) $ret['moderation'.$index][$row['type']] = $row['cnt'] + (!empty($ret['moderation'.$index][$row['type']])?$ret['moderation'.$index][$row['type']]:0);
                                    elseif($row['published'] == 4) $ret['draft'.$index][$row['type']] = $row['cnt'] + (!empty($ret['draft'.$index][$row['type']])?$ret['draft'.$index][$row['type']]:0);
                                }
                            }
                        }
                    }
                }
            } else {  // для частников
                $where = [];
                if(!empty($user_id)) $where[] = "id_user = ".$db->real_escape_string($user_id);
                switch($deal_type){
                    case 'rent':
                        $where[] = "rent = 1";
                        break;
                    case 'sell':
                        $where[] = "rent = 2";
                        break;
                }
                $where_str = empty($where) ? "" : " WHERE ".implode(" AND ",$where);
                $selects = array(
                    TYPE_ESTATE_LIVE => "(SELECT IFNULL(COUNT(*),0) as cnt, 'live' as type, published, status, payed_status FROM ".Config::$sys_tables['live'].$where_str." GROUP BY published, status, payed_status)",
                    TYPE_ESTATE_COMMERCIAL => "(SELECT IFNULL(COUNT(*),0) as cnt, 'commercial' as type, published, status, payed_status FROM ".Config::$sys_tables['commercial'].$where_str." GROUP BY published, status, payed_status)",
                    TYPE_ESTATE_BUILD => "(SELECT IFNULL(COUNT(*),0) as cnt, 'build' as type, published, status, payed_status FROM ".Config::$sys_tables['build'].$where_str." GROUP BY published, status, payed_status)",
                    TYPE_ESTATE_COUNTRY => "(SELECT IFNULL(COUNT(*),0) as cnt, 'country' as type, published, status, payed_status FROM ".Config::$sys_tables['country'].$where_str." GROUP BY published, status, payed_status)",
                    
                    TYPE_ESTATE_LIVE_SELL => "(SELECT IFNULL(COUNT(*),0) as cnt, 'live_sell' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['live'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 2 GROUP BY published, status)",
                    TYPE_ESTATE_COMMERCIAL_SELL => "(SELECT IFNULL(COUNT(*),0) as cnt, 'commercial_sell' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['commercial'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 2 GROUP BY published, status)",
                    TYPE_ESTATE_COUNTRY_SELL => "(SELECT IFNULL(COUNT(*),0) as cnt, 'country_sell' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['country'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 2 GROUP BY published, status)",
                    
                    TYPE_ESTATE_LIVE_RENT => "(SELECT IFNULL(COUNT(*),0) as cnt, 'live_rent' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['live'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 1 GROUP BY published, status)",
                    TYPE_ESTATE_COMMERCIAL_RENT => "(SELECT IFNULL(COUNT(*),0) as cnt, 'commercial_rent' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['commercial'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 1 GROUP BY published, status)",
                    TYPE_ESTATE_COUNTRY_RENT => "(SELECT IFNULL(COUNT(*),0) as cnt, 'country_rent' as type, published, status, 0 AS payed_status FROM ".Config::$sys_tables['country'].$where_str.($where_str!=''?" AND ":" WHERE ")."rent = 1 GROUP BY published, status)",
                );
                if(empty($estate_type)) {
                    $result = [];
                    $big_sql = implode('UNION',$selects);
                    $result = $db->fetchall($big_sql);
                } else {
                    $sql = $selects[$estate_type];
                    $result = $db->fetchall($sql);
                }
                if(!empty($result)){
                    foreach($result as $row){
                        if(strstr($row['type'],'_sell')!='') $index = "_sell";
                        elseif(strstr($row['type'],'_rent')!='') $index = "_rent";
                        else  $index = '';
                        if($row['published'] == 1) {
                            $ret['published'.$index][$row['type']] = $row['cnt'] + (!empty($ret['published'.$index][$row['type']])?$ret['published'.$index][$row['type']]:0);
                            if($row['payed_status'] == 1) $ret['payed'] += $row['cnt'];
                        }
                        elseif($row['published']==2) $ret['archive'.$index][$row['type']] = $row['cnt'] +  (!empty($ret['archive'.$index][$row['type']])?$ret['archive'.$index][$row['type']]:0);  
                        elseif($row['published']==3) $ret['moderation'.$index][$row['type']] = $row['cnt'] + (!empty($ret['moderation'.$index][$row['type']])?$ret['moderation'.$index][$row['type']]:0);
                        elseif($row['published']==4) $ret['draft'.$index][$row['type']] = $row['cnt'] + (!empty($ret['draft'.$index][$row['type']])?$ret['draft'.$index][$row['type']]:0);
                    }
                }                    
            }
                    
            if(!isset($ret['published'][TYPE_ESTATE_LIVE])) $ret['published'][TYPE_ESTATE_LIVE] = 0;
            if(!isset($ret['published_sell'][TYPE_ESTATE_LIVE_SELL])) $ret['published_sell'][TYPE_ESTATE_LIVE_SELL] = 0;
            if(!isset($ret['published_rent'][TYPE_ESTATE_LIVE_RENT])) $ret['published_rent'][TYPE_ESTATE_LIVE_RENT] = 0;
            if(!isset($ret['archive'][TYPE_ESTATE_LIVE])) $ret['archive'][TYPE_ESTATE_LIVE] = 0;
            if(!isset($ret['moderation'][TYPE_ESTATE_LIVE_MODERATION])) $ret['moderation'][TYPE_ESTATE_LIVE_MODERATION] = 0;
            if(!isset($ret['draft'][TYPE_ESTATE_LIVE_DRAFT])) $ret['draft'][TYPE_ESTATE_LIVE_DRAFT] = 0;
            if(!isset($ret['published'][TYPE_ESTATE_BUILD])) $ret['published'][TYPE_ESTATE_BUILD] = 0;
            if(!isset($ret['archive'][TYPE_ESTATE_BUILD])) $ret['archive'][TYPE_ESTATE_BUILD] = 0;
            if(!isset($ret['moderation'][TYPE_ESTATE_BUILD_MODERATION])) $ret['moderation'][TYPE_ESTATE_BUILD_MODERATION] = 0;
            if(!isset($ret['draft'][TYPE_ESTATE_BUILD_DRAFT])) $ret['draft'][TYPE_ESTATE_BUILD_DRAFT] = 0;
            if(!isset($ret['published'][TYPE_ESTATE_COMMERCIAL])) $ret['published'][TYPE_ESTATE_COMMERCIAL] = 0;
            if(!isset($ret['published_sell'][TYPE_ESTATE_COMMERCIAL_SELL])) $ret['published_sell'][TYPE_ESTATE_COMMERCIAL_SELL] = 0;
            if(!isset($ret['published_rent'][TYPE_ESTATE_COMMERCIAL_RENT])) $ret['published_rent'][TYPE_ESTATE_COMMERCIAL_RENT] = 0;
            if(!isset($ret['archive'][TYPE_ESTATE_COMMERCIAL])) $ret['archive'][TYPE_ESTATE_COMMERCIAL] = 0;
            if(!isset($ret['moderation'][TYPE_ESTATE_COMMERCIAL_MODERATION])) $ret['moderation'][TYPE_ESTATE_COMMERCIAL_MODERATION] = 0;
            if(!isset($ret['draft'][TYPE_ESTATE_COMMERCIAL_DRAFT])) $ret['draft'][TYPE_ESTATE_COMMERCIAL_DRAFT] = 0;
            if(!isset($ret['published'][TYPE_ESTATE_COUNTRY])) $ret['published'][TYPE_ESTATE_COUNTRY] = 0;
            if(!isset($ret['published_sell'][TYPE_ESTATE_COUNTRY_SELL])) $ret['published_sell'][TYPE_ESTATE_COUNTRY_SELL] = 0;
            if(!isset($ret['published_rent'][TYPE_ESTATE_COUNTRY_RENT])) $ret['published_rent'][TYPE_ESTATE_COUNTRY_RENT] = 0;
            if(!isset($ret['archive'][TYPE_ESTATE_COUNTRY])) $ret['archive'][TYPE_ESTATE_COUNTRY] = 0;
            if(!isset($ret['moderation'][TYPE_ESTATE_COUNTRY_MODERATION])) $ret['moderation'][TYPE_ESTATE_COUNTRY_MODERATION] = 0;
            if(!isset($ret['draft'][TYPE_ESTATE_COUNTRY_DRAFT])) $ret['draft'][TYPE_ESTATE_COUNTRY_DRAFT] = 0;
            $memcache->set($sig, $ret, FALSE, self::$cache_result_time);
            if($memcache_data){
                $memcache->unlock($sig);
            }
        }
        return $ret;
    } 
    /**
    * Кол-во опубликованных объектов
    */
    public static function getCountPublished($deal_type = false){
        global $db;
        $where = "1";
        switch($deal_type){
            case 'rent':
                $where = "rent = 1";
                break;
            case 'sell':
                $where = "rent = 2";
                break;
        }
        
        $objects = $db->fetchall("
                                    SELECT IFNULL(COUNT(*),0) as cnt, 'live' as type FROM ".Config::$sys_tables['live']." WHERE published = 1 AND ".$where." 
                                    UNION ALL
                                    SELECT IFNULL(COUNT(*),0) as cnt, 'build' as type FROM ".Config::$sys_tables['build']." WHERE published = 1 AND ".$where." 
                                    UNION ALL
                                    SELECT IFNULL(COUNT(*),0) as cnt, 'commercial' as type FROM ".Config::$sys_tables['commercial']." WHERE published = 1 AND ".$where." 
                                    UNION ALL
                                    SELECT IFNULL(COUNT(*),0) as cnt, 'country' as type FROM ".Config::$sys_tables['country']." WHERE published = 1 AND ".$where." 
        
        ");
        return $objects;
        
    }
    /**
    * Кол-во жилых объектов
    * @param mixed ID пользователя
    * @param mixed ID агентства
    */
    public static function getCountLive($user_id=NULL, $agency_id=NULL){
        return self::getCount($user_id, $agency_id, TYPE_ESTATE_LIVE);
    } 

    /**
    * Кол-во строящихся объектов
    * @param mixed ID пользователя
    * @param mixed ID агентства
    */
    public static function getCountBuild($user_id=NULL, $agency_id=NULL){
        return self::getCount($user_id, $agency_id, TYPE_ESTATE_BUILD);
    } 

    /**
    * Кол-во коммерческих объектов
    * @param mixed ID пользователя
    * @param mixed ID агентства
    */
    public static function getCountCommercial($user_id=NULL, $agency_id=NULL){
        return self::getCount($user_id, $agency_id, TYPE_ESTATE_COMMERCIAL);
    } 

    /**
    * Кол-во загородных объектов
    * @param mixed ID пользователя
    * @param mixed ID агентства
    */
    public static function getCountCountry($user_id=NULL, $agency_id=NULL){
        return self::getCount($user_id, $agency_id, TYPE_ESTATE_COUNTRY);
    } 

    /**
    * Максимальное кол-во объектов для агентства
    * @param mixed ID агентства
    */
    public static function getCountPacketAgencies($agency_id){
        global $db;
        $result = $db->fetch("SELECT *,
                                     IF(".Config::$sys_tables['tarifs_agencies'].".id = 1,
                                     (".Config::$sys_tables['agencies'].".build_objects + 
                                      ".Config::$sys_tables['agencies'].".live_sell_objects + 
                                      ".Config::$sys_tables['agencies'].".live_rent_objects + 
                                      ".Config::$sys_tables['agencies'].".country_sell_objects + 
                                      ".Config::$sys_tables['agencies'].".country_rent_objects + 
                                      ".Config::$sys_tables['agencies'].".commercial_sell_objects + 
                                      ".Config::$sys_tables['agencies'].".commercial_rent_objects 
                                     ),
                                     ".Config::$sys_tables['tarifs_agencies'].".cnt) AS cnt 
                              FROM ".Config::$sys_tables['tarifs_agencies']."
                              LEFT JOIN ".Config::$sys_tables['agencies']." ON ".Config::$sys_tables['agencies'].".id_tarif = ".Config::$sys_tables['tarifs_agencies'].".id
                              WHERE ".Config::$sys_tables['agencies'].".id = ? AND   ".Config::$sys_tables['agencies'].".id_tarif!=?",$agency_id,6);
        return $result['cnt'];
    } 
    /**
    * Количество подключенных услуг
    * @param mixed ID агентства
    */
    public static function getAgenciesCount($user_id){
        global $db, $auth; 
        
        if(empty($user_id)) return false;
        //кол-во платных объектов
         $list = [];
         $payed_list = $db->fetchall(
              "SELECT IFNULL(SUM(cnt),0) as cnt, 'promo' as type FROM (
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['live']." WHERE published = 1 AND status = 3 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['build']." WHERE published = 1 AND status = 3 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['commercial']." WHERE published = 1 AND status = 3 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['country']." WHERE published = 1 AND status = 3 AND id_user = ".$user_id."
              ) a
              UNION ALL
              SELECT IFNULL(SUM(cnt),0) as cnt, 'premium' as type FROM (
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['live']." WHERE published = 1 AND status = 4 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['build']." WHERE published = 1 AND status = 4 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['commercial']." WHERE published = 1 AND status = 4 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['country']." WHERE published = 1 AND status = 4 AND id_user = ".$user_id."
              ) b
              UNION ALL
              SELECT IFNULL(SUM(cnt),0) as cnt, 'vip' as type FROM (
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['live']." WHERE published = 1 AND status = 6 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['build']." WHERE published = 1 AND status = 6 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['commercial']." WHERE published = 1 AND status = 6 AND id_user = ".$user_id."
                    UNION ALL
                    SELECT COUNT(*) as  cnt FROM ".Config::$sys_tables['country']." WHERE published = 1 AND status = 6 AND id_user = ".$user_id."
              ) c
         "
         );
         //количество сотрудников
         $staff_list = $db->fetch("SELECT COUNT(*) as cnt                              
                                   FROM ".Config::$sys_tables['users']."
                                   WHERE id_agency = ?
         ", $auth->id_agency);
         return array(
            'promo' => $payed_list[0]['cnt'],
            'premium' => $payed_list[1]['cnt'],
            'vip' => $payed_list[2]['cnt'],
            'staff_number' => $staff_list['cnt']
         );
    }     

    /**
    * Получение префикса доп.набора для колл-центра
    * @param string phone телефон
    * @param int id_user id пользователя
    * @param string estate_type тип недвижимости
    * @return integer
    */
    public static function getPhone($phone, $id_user, $estate_type){    
        global $db;
        $phone = Convert::toPhone($phone);
        
        $item = $db->fetch("SELECT id FROM ".Config::$sys_tables['phone_prefixes']." WHERE type = ? AND phone_number = ? AND id_user = ?",
            Config::$values['call_center']['estate'][$estate_type], $phone[0], $id_user
        );
        if(!empty($item)){
            return Config::$values['call_center']['phone'].' доб. '.$item['id'];
        } else return $phone[0];
    }

    /**
    * Кол-во объектов
    * @param mixed ID пользователя
    * @param mixed ID агентства
    * @param mixed тип объектов
    * @param mixed выводить из memcache
    */
    public static function getCountPopular($count = true){
        global $db, $sys_tables;
        $data = array(
            'live' => array(
                '/live/sell/?obj_type=1&rooms=0' =>   array('query'=>'id_type_object = 1 AND rent = 2 AND rooms_total = 0', 'title'=>'Студии', 'filled'=>0, 'type'=>'sell'),
                '/live/sell/flats/rooms-1/' =>               array('query'=>'id_type_object = 1 AND rent = 2 AND rooms_total = 1', 'title'=>'1ккв', 'filled'=>0, 'type'=>'sell'),
                '/live/sell/flats/rooms-2/' =>               array('query'=>'id_type_object = 1 AND rent = 2 AND rooms_total = 2', 'title'=>'2ккв', 'filled'=>0, 'type'=>'sell'),
                '/live/sell/flats/rooms-3/' =>               array('query'=>'id_type_object = 1 AND rent = 2 AND rooms_total = 3', 'title'=>'3ккв', 'filled'=>0, 'type'=>'sell'),
                '/live/sell/flats/rooms-4/' =>               array('query'=>'id_type_object = 1 AND rent = 2 AND rooms_total >= 4','title'=>'4ккв и более', 'filled'=>0, 'type'=>'sell'),

                '/live/rent/?obj_type=1&rooms=0' =>   array('query'=>'id_type_object = 1 AND rent = 1 AND rooms_total = 0', 'title'=>'Студии', 'filled'=>0, 'type'=>'rent'),
                '/live/rent/flats/rooms-1/' =>               array('query'=>'id_type_object = 1 AND rent = 1 AND rooms_total = 1', 'title'=>'1ккв', 'filled'=>0, 'type'=>'rent'),
                '/live/rent/flats/rooms-2/' =>               array('query'=>'id_type_object = 1 AND rent = 1 AND rooms_total = 2', 'title'=>'2ккв', 'filled'=>0, 'type'=>'rent'),
                '/live/rent/flats/rooms-3/' =>               array('query'=>'id_type_object = 1 AND rent = 1 AND rooms_total = 3', 'title'=>'3ккв', 'filled'=>0, 'type'=>'rent'),
                '/live/rent/rooms/' =>                       array('query'=>'id_type_object = 2 AND rent = 1',                     'title'=>'Комнаты', 'filled'=>0, 'type'=>'rent'),
            ),
            'build' => array(
                '/build/sell/rooms-0/' =>   array('query'=>'rent = 2 AND rooms_sale = 0', 'title'=>'Студии', 'filled'=>0, 'type'=>'sell'),
                '/build/sell/rooms-1/' =>               array('query'=>'rent = 2 AND rooms_sale = 1', 'title'=>'1ккв', 'filled'=>0, 'type'=>'sell'),
                '/build/sell/rooms-2/' =>               array('query'=>'rent = 2 AND rooms_sale = 2', 'title'=>'2ккв', 'filled'=>0, 'type'=>'sell'),
                '/build/sell/rooms-3/' =>               array('query'=>'rent = 2 AND rooms_sale = 3', 'title'=>'3ккв', 'filled'=>0, 'type'=>'sell'),
                '/build/sell/rooms-4/' =>               array('query'=>'rent = 2 AND rooms_sale >=4', 'title'=>'4ккв и более', 'filled'=>0, 'type'=>'sell'),
            ),
            'commercial' => array(
                '/commercial/sell/offices/?districts=12,3,2,16' =>   array('query'=>'id_region = 78 AND id_district IN (12,3,2,16) AND id_type_object = 6 AND rent = 2', 'title'=>'Офисы в центре', 'filled'=>0, 'type'=>'sell'),
                '/commercial/sell/offices/?square_full_to=100' =>   array('query'=>'square_full <= 100 AND id_type_object = 6 AND rent = 2', 'title'=>'Офисы до 100 м', 'filled'=>0, 'type'=>'sell'),
                '/commercial/sell/premises/' =>       array('query'=>' `id_type_object` IN (14,15,17,20,23,24,25,11,26,27) AND rent=2 ', 'title'=>'Помещения', 'filled'=>0, 'type'=>'sell'),
                
                '/commercial/rent/offices/?districts=12,3,2,16' =>   array('query'=>'id_region = 78 AND id_district IN (12,3,2,16) AND id_type_object = 6 AND rent = 1', 'title'=>'Офисы в центре', 'filled'=>0, 'type'=>'rent'),
                '/commercial/rent/offices/?square_full_to=50' =>   array('query'=>'square_full <= 50 AND id_type_object = 6 AND rent = 1', 'title'=>'Офисы до 50 м', 'filled'=>0, 'type'=>'rent'),
                '/commercial/rent/premises/' =>       array('query'=>' `id_type_object` IN (14,15,17,20,23,24,25,11,26,27) AND rent=1 ', 'title'=>'Помещения', 'filled'=>0, 'type'=>'rent'),
            ),
            'country' => array(
                '/country/sell/buildings/?district_areas=33632,26129,6996' =>   array('query'=>'`id_type_object` IN (2,9,11,14) AND rent=2 AND id_type_object IN (2,9,11,14) AND  ((`id_region`=47 AND `id_area`=6) OR (`id_region`=47 AND `id_area`=15) OR (`id_region`=47 AND `id_area`=5))', 'title'=>'Дома на севере', 'filled'=>0, 'type'=>'sell'),
                '/country/sell/buildings/?square_full_to=60' =>   array('query'=>'`rent` = 2 AND `id_type_object` IN (2,9,11,14) AND `square_full` <= 60', 'title'=>'Дом до 60 м', 'filled'=>0, 'type'=>'sell'),
                '/country/sell/land/' =>   array('query' => ' `id_type_object` IN (12,13) AND rent=2 ', 'title'=>'Земельные участки', 'filled'=>0, 'type'=>'sell'),

                '/country/rent/buildings/?district_areas=33632,26129,6996' =>   array('query'=>'`id_type_object` IN (2,9,11,14) AND rent=1 AND id_type_object IN (2,9,11,14) AND  ((`id_region`=47 AND `id_area`=6) OR (`id_region`=47 AND `id_area`=15) OR (`id_region`=47 AND `id_area`=5))', 'title'=>'Дома на севере', 'filled'=>0, 'type'=>'rent'),
                '/country/rent/buildings/?square_full_to=60' =>   array('query'=>'`rent` = 1 AND `id_type_object` IN (2,9,11,14) AND `square_full` <= 60', 'title'=>'Дом до 60 м', 'filled'=>0, 'type'=>'rent'),
                '/country/rent/buildings/' =>   array('query'=>'`rent` = 1 AND `id_type_object` IN (2,9,11,14)', 'title'=>'Дома, коттеджи', 'filled'=>0, 'type'=>'rent'),
            )            
        );
        if(!empty($count)){
            $agencies = $db->fetchall("SELECT id FROM ".Config::$sys_tables['users']." WHERE id_agency>0");
            $ids = [];
            foreach($agencies as $k=>$item) $ids[] = $item['id'];
            $ids = implode(",",$ids);
            foreach($data as $estate_type=>$values){
                foreach($values as $key=>$item){
                    $cnt = $db->fetch("SELECT COUNT(*) as cnt FROM ".Config::$sys_tables[$estate_type]." WHERE published = 1 AND ".$item['query'].(!empty($item['users'])?" AND id_user NOT IN(".$ids.")":""));
                    $data[$estate_type][$key]['filled'] = $cnt['cnt'];
                }
            }
        }
        
        return $data;
    }     
    
    /**
    * Кол-во объектов по кнопкам поисковой формы
    * 
    * @param mixed $where условие поиска
    */
    public static function getAgenciesCountSearch($where){
        global $db;
        $agency_amounts = $db->fetchall("SELECT CONCAT(id_group,
                                                       '-live-',
                                                       IF(".Config::$sys_tables['live'].".rent =1,'rent','sell'),
                                                       IF(".Config::$sys_tables['live'].".by_the_day = 1,'-by-the-day','')) AS data_value,
                                                COUNT(*) AS amount
                                         FROM ".Config::$sys_tables['live']." 
                                         LEFT JOIN ".Config::$sys_tables['type_objects_live']." ON ".Config::$sys_tables['live'].".id_type_object = ".Config::$sys_tables['type_objects_live'].".id
                                         WHERE ".Config::$sys_tables['type_objects_live'].".id_group>0 ".(!empty($where)?" AND ".$where:"")." 
                                         GROUP BY id_group,rent,by_the_day
                                         UNION
                                         SELECT '1-build-sell' AS data_value,
                                                COUNT(*) AS amount
                                         FROM ".Config::$sys_tables['build']." 
                                         ".(!empty($where)?" WHERE ".$where:"")."
                                         UNION
                                         SELECT CONCAT(id_group,
                                                       '-country-',
                                                       IF(".Config::$sys_tables['country'].".rent =1,'rent','sell'),
                                                       IF(".Config::$sys_tables['country'].".by_the_day = 1,'-by-the-day','')) AS data_value,
                                                COUNT(*) AS amount
                                         FROM ".Config::$sys_tables['country']."
                                         LEFT JOIN ".Config::$sys_tables['type_objects_country']." ON ".Config::$sys_tables['country'].".id_type_object = ".Config::$sys_tables['type_objects_country'].".id
                                         WHERE ".Config::$sys_tables['type_objects_country'].".id_group>0 ".(!empty($where)?" AND ".$where:"")."
                                         GROUP BY id_group,rent,by_the_day
                                         UNION
                                         SELECT CONCAT(id_group,
                                                       '-commercial-',
                                                       IF(".Config::$sys_tables['commercial'].".rent =1,'rent','sell')) AS data_value,
                                                COUNT(*) AS amount
                                         FROM ".Config::$sys_tables['commercial']."
                                         LEFT JOIN ".Config::$sys_tables['type_objects_commercial']." ON ".Config::$sys_tables['commercial'].".id_type_object = ".Config::$sys_tables['type_objects_commercial'].".id
                                         WHERE ".Config::$sys_tables['type_objects_commercial'].".id_group>0 ".(!empty($where)?" AND ".$where:"")."
                                         GROUP BY id_group,rent",'data_value');
        return $agency_amounts;
    }   
    
    /**
    * Кол-во объектов для комнатности 
    * @param mixed выводить из memcache
    */
    public static function GetReLinks($estate_type, $rooms = false, $count = true){
        global $db, $sys_tables;
        $agencies = $db->fetchall("SELECT id FROM ".Config::$sys_tables['users']." WHERE id_agency>0");
        $agencies_ids = [];
        foreach($agencies as $k=>$item) $agencies_ids[] = $item['id'];        
        $data = array(
            'build' => array(
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'class=1' =>                array('query'=>'rent = 2 AND class = 1',                                    'title'=>'Эконом',      'filled'=>0, 'type'=>'sell',                        'url'=>'/build/sell/?class=1'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? '&rooms='.$rooms : '')),
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'class=2' =>                array('query'=>'rent = 2 AND class = 2',                                    'title'=>'Бизнес',      'filled'=>0, 'type'=>'sell',                        'url'=>'/build/sell/?class=2'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? '&rooms='.$rooms : '')),
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'class=3' =>                array('query'=>'rent = 2 AND class = 3',                                    'title'=>'Премиум',     'filled'=>0, 'type'=>'sell',                        'url'=>'/build/sell/?class=3'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? '&rooms='.$rooms : '')),
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'class=4' =>                array('query'=>'rent = 2 AND class = 4',                                    'title'=>'Комфорт',     'filled'=>0, 'type'=>'sell',                        'url'=>'/build/sell/?class=4'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? '&rooms='.$rooms : '')),
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'build_complete=4' =>       array('query'=>'rent = 2 AND id_build_complete = 4',                        'title'=>'Готовые',     'filled'=>0, 'type'=>'sell' ,                       'url'=>'/build/sell/?build_complete=4'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? '&rooms='.$rooms : '')),
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'build_complete=90' =>       array('query'=>'rent = 2 AND id_build_complete IN (78,79,80,81,90)',       'title'=>'Сдача 2016',  'filled'=>0, 'type'=>'sell',                        'url'=>'/build/sell/?build_complete=90'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? '&rooms='.$rooms : '')),
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'build_complete=91' =>       array('query'=>'rent = 2 AND id_build_complete IN (82,83,84,85,91)',       'title'=>'Сдача 2017',  'filled'=>0, 'type'=>'sell',                        'url'=>'/build/sell/?build_complete=91'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? '&rooms='.$rooms : '')),
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'id_facing IN (4,5,6,7,10)' =>    array('query'=>'rent = 2 AND id_facing IN (4,5,6,7,10)',                   'title'=>'С отделкой',     'filled'=>0, 'type'=>'sell',                     'url'=>'/build/sell/?id_facing=4,5,6,7,10'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? '&rooms='.$rooms : '')),
                '/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'user_objects=1' =>          array('query'=>'rent = 2 AND id_user NOT IN ('.implode(',', $agencies_ids).')', 'title'=>'Частные объявления', 'filled'=>0, 'type'=>'sell',            'url'=>'/build/sell/?'.(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? 'rooms='.$rooms.'&' : '').'user_objects=1')
            )           
        );
        if(!empty($count)){
            foreach($data as $estate_type=>$values){
                foreach($values as $key=>$item){
                    if($rooms != 'all'){
                        $cnt = $db->fetch("SELECT COUNT(*) as cnt FROM ".Config::$sys_tables[$estate_type]." WHERE published = 1 ".(!empty($rooms) || ( isset($rooms) && $rooms !='' ) ? " AND rooms_sale IN (".$rooms.")" : "" )." AND ".$item['query']);
                        $data[$estate_type][$key]['filled'] = $cnt['cnt'];
                    }
                }
            }
        }
        
        return $data;
    }       
    
    public static function getSimpleCount(){
        global $auth, $db;
        if(!empty($auth->id_agency) && $auth->agency_admin == 1 ) {
            $users = $db->fetchall("SELECT * FROM ".Config::$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
            $ids = [];
            foreach($users as $k=>$user) $ids[] = $user['id'];
            $ids = implode(",", $ids);
        } else $ids = $auth->id;
        
        $counts = $db->fetch("
            SELECT 
                published_build_sell,
                published_live_sell,
                published_country_sell,
                published_commercial_sell,

                published_live_rent,
                published_country_rent,
                published_commercial_rent,
                
                (published_build_sell + published_live_sell + published_country_sell + published_commercial_sell) as published_sell,
                (published_live_rent + published_country_rent + published_commercial_rent) as published_rent,
                ( published_build_sell + published_live_sell + published_country_sell + published_commercial_sell + published_live_rent + published_country_rent + published_commercial_rent ) as published,
                 
                archive_build_sell,
                archive_live_sell,
                archive_country_sell,
                archive_commercial_sell,

                archive_live_rent,
                archive_country_rent,
                archive_commercial_rent,
                
                (archive_build_sell + archive_live_sell + archive_country_sell + archive_commercial_sell) as archive_sell,
                (archive_live_rent + archive_country_rent + archive_commercial_rent) as archive_rent,
                ( archive_build_sell + archive_live_sell + archive_country_sell + archive_commercial_sell + archive_live_rent + archive_country_rent + archive_commercial_rent ) as archive,
                
                draft_build_sell,
                draft_live_sell,
                draft_country_sell,
                draft_commercial_sell,

                draft_live_rent,
                draft_country_rent,
                draft_commercial_rent,
                
                (draft_build_sell + draft_live_sell + draft_country_sell + draft_commercial_sell) as draft_sell,
                (draft_live_rent + draft_country_rent + draft_commercial_rent) as draft_rent,
                ( draft_build_sell + draft_live_sell + draft_country_sell + draft_commercial_sell + draft_live_rent + draft_country_rent + draft_commercial_rent ) as draft,
                
                moderation_build_sell,
                moderation_live_sell,
                moderation_country_sell,
                moderation_commercial_sell,

                moderation_live_rent,
                moderation_country_rent,
                moderation_commercial_rent,
                
                (moderation_build_sell + moderation_live_sell + moderation_country_sell + moderation_commercial_sell) as moderation_sell,
                (moderation_live_rent + moderation_country_rent + moderation_commercial_rent) as moderation_rent,
                ( moderation_build_sell + moderation_live_sell + moderation_country_sell + moderation_commercial_sell + moderation_live_rent + moderation_country_rent + moderation_commercial_rent ) as moderation
                
            FROM
                (SELECT
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['build'] . "      WHERE id_user IN ( ". $ids ." ) AND published = 1 AND rent = 2) AS published_build_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['live'] . "       WHERE id_user IN ( ". $ids ." ) AND published = 1 AND rent = 2) AS published_live_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['country'] . "    WHERE id_user IN ( ". $ids ." ) AND published = 1 AND rent = 2) AS published_country_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['commercial'] . " WHERE id_user IN ( ". $ids ." ) AND published = 1 AND rent = 2) AS published_commercial_sell,

                    (SELECT COUNT(*) FROM " . Config::$sys_tables['live'] . "       WHERE id_user IN ( ". $ids ." ) AND published = 1 AND rent = 1) AS published_live_rent,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['country'] . "    WHERE id_user IN ( ". $ids ." ) AND published = 1 AND rent = 1) AS published_country_rent,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['commercial'] . " WHERE id_user IN ( ". $ids ." ) AND published = 1 AND rent = 1) AS published_commercial_rent,

                    (SELECT COUNT(*) FROM " . Config::$sys_tables['build'] . "      WHERE id_user IN ( ". $ids ." ) AND published = 2 AND rent = 2) AS archive_build_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['live'] . "       WHERE id_user IN ( ". $ids ." ) AND published = 2 AND rent = 2) AS archive_live_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['country'] . "    WHERE id_user IN ( ". $ids ." ) AND published = 2 AND rent = 2) AS archive_country_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['commercial'] . " WHERE id_user IN ( ". $ids ." ) AND published = 2 AND rent = 2) AS archive_commercial_sell,

                    (SELECT COUNT(*) FROM " . Config::$sys_tables['live'] . "       WHERE id_user IN ( ". $ids ." ) AND published = 2 AND rent = 1) AS archive_live_rent,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['country'] . "    WHERE id_user IN ( ". $ids ." ) AND published = 2 AND rent = 1) AS archive_country_rent,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['commercial'] . " WHERE id_user IN ( ". $ids ." ) AND published = 2 AND rent = 1) AS archive_commercial_rent,
       
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['build'] . "      WHERE id_user IN ( ". $ids ." ) AND published = 4 AND rent = 2) AS draft_build_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['live'] . "       WHERE id_user IN ( ". $ids ." ) AND published = 4 AND rent = 2) AS draft_live_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['country'] . "    WHERE id_user IN ( ". $ids ." ) AND published = 4 AND rent = 2) AS draft_country_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['commercial'] . " WHERE id_user IN ( ". $ids ." ) AND published = 4 AND rent = 2) AS draft_commercial_sell,

                    (SELECT COUNT(*) FROM " . Config::$sys_tables['live'] . "       WHERE id_user IN ( ". $ids ." ) AND published = 4 AND rent = 1) AS draft_live_rent,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['country'] . "    WHERE id_user IN ( ". $ids ." ) AND published = 4 AND rent = 1) AS draft_country_rent,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['commercial'] . " WHERE id_user IN ( ". $ids ." ) AND published = 4 AND rent = 1) AS draft_commercial_rent,
       
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['build'] . "      WHERE id_user IN ( ". $ids ." ) AND published = 3 AND rent = 2) AS moderation_build_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['live'] . "       WHERE id_user IN ( ". $ids ." ) AND published = 3 AND rent = 2) AS moderation_live_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['country'] . "    WHERE id_user IN ( ". $ids ." ) AND published = 3 AND rent = 2) AS moderation_country_sell,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['commercial'] . " WHERE id_user IN ( ". $ids ." ) AND published = 3 AND rent = 2) AS moderation_commercial_sell,

                    (SELECT COUNT(*) FROM " . Config::$sys_tables['live'] . "       WHERE id_user IN ( ". $ids ." ) AND published = 3 AND rent = 1) AS moderation_live_rent,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['country'] . "    WHERE id_user IN ( ". $ids ." ) AND published = 3 AND rent = 1) AS moderation_country_rent,
                    (SELECT COUNT(*) FROM " . Config::$sys_tables['commercial'] . " WHERE id_user IN ( ". $ids ." ) AND published = 3 AND rent = 1) AS moderation_commercial_rent
       
                ) a
        ");
        return $counts;
    }
}
?>