<?php
/**    
* Класс работы со спецредложениями
*/

class specOffers {
	 /* Свойство - набор категорий для типов недвижимости (в раздел ТГБ) */
	 private static  $categories = array(
		'live'=>'1,2,3',
        'build'=>'1,2,3',
		'housing_estates'=>'1,2,3',
        'commercial'=>'6,7,14',
        'business'=>'6,7,14',
        'garage'=>'6,7,14',
		'country'=>'4,5',
        'country_complex'=>'5',
		'inter'=>'10',
        'mortgage'=>'12',
        'invest'=>'9',
        'elite'=>'8'
	);
	private static $tables = [];
    
						
    /* Статистика Спепредложений */
    public static function Init(){
        self::$tables = array(
            'objects'      => Config::$sys_tables['spec_offers_objects'],
            'packets'      => Config::$sys_tables['spec_offers_packets'],
            'photos'       => Config::$sys_tables['spec_offers_objects_photos'],
            'categories'   => Config::$sys_tables['spec_offers_categories'],
            'credits'      => Config::$sys_tables['spec_objects_credits'],
            'show'         => array (
                                'objects' => Config::$sys_tables['spec_objects_stats_show_day'],
                                'packets' => Config::$sys_tables['spec_packets_stats_show_day'],
                              ),
            'click'        => array (
                                'objects' => Config::$sys_tables['spec_objects_stats_click_day'],
                                'packets' => Config::$sys_tables['spec_packets_stats_click_day'],
                              ) 
            );
                          
    }
    
    private static function getTgbForEstateSection($tgb_type){
        global $db;
        require_once('includes/class.tgb.php');
        $list = Tgb::getListForEstate(false,false,$tgb_type);
        return $list;
    }
    
    /**
    * Объект-выборка из базы спецпредложений для ajax контейнеров (шапка, на главной, в разделах(ТГБ) )
    * @param string $flag - местоположение блока (название поля)
    * @param string $tgb_type - тип недвижимости  для блоков типа ТГБ ('live', 'build' ....)
    * @param integer $id_category - id тип недвижимости  для блоков в основных разделах спецпредложений
    * @param boolean $order - сортировка
    * @return array (результат выборки из базы)
    */
    public static function getList($flag, $tgb_type=null, $id_category=null, $order = false){
        if(empty(self::$categories[$tgb_type]) && $flag == 'inestate_flag') return false;
        global $db;
		$where_objects = $where_packets = " maintable.date_start <= CURDATE() AND maintable.date_end > CURDATE() AND  maintable.".$flag."=1";
        if($order) $order = ' ORDER BY `position_on_main` DESC, `type` DESC, id ';
        //else $order = ' RAND() ';
        if(!empty($tgb_type) && !empty(self::$categories[$tgb_type])){
            $where_objects = $where_packets .= " AND maintable.`id_category` IN (".self::$categories[$tgb_type].") ";
            $tgb_list = [];
            $tgb_list = specOffers::getTgbForEstateSection($tgb_type);
        }
        elseif(!empty($id_category)){
                $where_objects = $where_packets .= " AND maintable.`id_category` IN (".$id_category.")"; 
                $where_objects .= " AND maintable.`id_packet` = 0 ";
        }
		$sql = "
			SELECT `id`, `id_category`, `id_packet`, `title`, `annotation`, `agent_title`, `direct_link`, `link_type`, `get_pixel`, `photo`, `type`, `code`, `position_on_main`, `half_show` FROM (
				(SELECT 
						maintable.`id`, 
                        maintable.`id_category`, 
						maintable.`id_packet`, 
                        maintable.`title`, 
                        maintable.`annotation`, 
						maintable.`agent_title`, 
						IF(maintable.`direct_link`='',
							IF(maintable.id_packet>0,
								CONCAT_WS('/','',".self::$tables['categories'].".url,".self::$tables['packets'].".code,maintable.id,''), 
								CONCAT_WS('/','',".self::$tables['categories'].".url,maintable.id,'')
							),
							maintable.`direct_link`
						) as `direct_link`, 
                        IF(maintable.`direct_link`='', 'internal','external') as link_type,
						maintable.`get_pixel`, 
						IF(maintable.main_img_link='',
							CONCAT('".Host::getImgUrl()."' ,'".Config::$values['img_folders']['spec_offers']."/',maintable.main_img_src),
							maintable.main_img_link
						) as photo,
						'object' as `type`, 
						'' as `code`, 
						maintable.`position_on_main`, 
						IF(maintable.`half_show`=1,RAND(),1) as `half_show`,
                        0 AS is_tgb_banner
			        FROM ".self::$tables['objects']." maintable  
				    LEFT JOIN ".self::$tables['categories']." ON ".self::$tables['categories'].".id = maintable.id_category
				    LEFT JOIN ".self::$tables['packets']." ON ".self::$tables['packets'].".id = maintable.id_packet
				    WHERE ".$where_objects."  
                )
				UNION
				(SELECT 
						maintable.`id`, 
						`id_category`,
                        '' as `id_packet`, 
                        maintable.`title`, 
                        maintable.`annotation`, 
						maintable.`agent_title`, 
						IF(`direct_link`='',
							CONCAT_WS('/','',".self::$tables['categories'].".url,maintable.code), 
							`direct_link`
						) as `direct_link`, 
                        IF(`direct_link`='', 'internal','external') as link_type,
						`get_pixel`, 
						IF(main_img_link='',
							CONCAT('".Host::getImgUrl()."' ,'".Config::$values['img_folders']['spec_offers']."/',main_img_src),
							main_img_link
						) as photo,
						'packet' as `type`, 
						`code`, 
						`position_on_main`, 
						IF(`half_show`=1,RAND(),1) as `half_show`,
                        0 AS is_tgb_banner
					FROM ".self::$tables['packets']." maintable 
					LEFT JOIN ".self::$tables['categories']." ON ".self::$tables['categories'].".id = maintable.id_category  
					WHERE  ".$where_packets." 
                )
			) as `spec`
			WHERE `half_show`>0.5
			".$order;
		$list = $db->fetchall($sql);            
        $list = array_merge($list,$tgb_list);
		if(empty($order)) shuffle($list);
        foreach($list as $k=>$item){
            $list[$k]['photo'] = htmlspecialchars($item['photo']);
            $list[$k]['direct_link'] = htmlspecialchars($item['direct_link']);
            $list[$k]['get_pixel'] = htmlspecialchars($item['get_pixel']);
        }
        return $list;
    }
    /**
    * Список объектов для пакета
    * @param integer $id - id пакета
    * @return array 
    */
    public static function getPacketList($id){
        global $db;    
        $list = $db->fetchall("SELECT                         
                                    ".self::$tables['objects'].".`id`, 
                                    ".self::$tables['objects'].".`title`, 
                                    ".self::$tables['objects'].".`annotation`, 
                                    IF(".self::$tables['objects'].".`direct_link`='',
                                        CONCAT_WS('/','',".self::$tables['categories'].".url,".self::$tables['packets'].".code,".self::$tables['objects'].".id,''),  
                                        ".self::$tables['objects'].".`direct_link`
                                    ) as `direct_link`, 
                                    IF(".self::$tables['objects'].".`direct_link`='', 'internal','external') as link_type,
                                    ".self::$tables['objects'].".`get_pixel`, 
                                    IF(".self::$tables['objects'].".main_img_link='',
                                        CONCAT('".Host::getImgUrl()."' ,'".Config::$values['img_folders']['spec_offers']."/',".self::$tables['objects'].".main_img_src),
                                        ".self::$tables['objects'].".main_img_link
                                    ) as photo,
                                   'object' as `type`
                              FROM ".self::$tables['objects']."
                              LEFT JOIN ".self::$tables['packets']." ON ".self::$tables['packets'].".`id` = ".self::$tables['objects'].".`id_packet`
                              LEFT JOIN ".self::$tables['categories']." ON ".self::$tables['categories'].".id = ".self::$tables['objects'].".id_category
                              WHERE ".self::$tables['objects'].".`base_page_flag`=1 AND ".self::$tables['packets'].".`id`=".$id."
                              ORDER BY CASE WHEN  ".self::$tables['objects'].".`position_on_main` > 0 THEN ".self::$tables['objects'].".`position_on_main` ELSE ".self::$tables['objects'].".id END");
        if(!empty($list)){
            foreach($list as $k=>$item){
                $list[$k]['photo'] = htmlspecialchars($item['photo']);
                $list[$k]['direct_link'] = htmlspecialchars($item['direct_link']);
                $list[$k]['get_pixel'] = htmlspecialchars($item['get_pixel']);
            }
            return $list;
        } else return false;
    }
    /**
    * Карточка спецпредложения
    * @param integer $id - id объекта
    * @param integer $id_packet - id пакета для объекта
    * @return array 
    */
    public static function getItem($id, $id_packet=false){
        global $db;
        $list = $db->fetch("SELECT ".self::$tables['objects'].".*, 
                                  ".self::$tables['packets'].".`agent_coords` as `packet_coords` 
                           FROM ".self::$tables['objects']." 
                           LEFT JOIN ".self::$tables['packets']." ON ".self::$tables['packets'].".`id` = ".self::$tables['objects'].".`id_packet` 
                           WHERE ".self::$tables['objects'].".`date_end` > CURDATE() AND ".self::$tables['objects'].".`id` = ".$id.(!empty($id_packet)?" AND ".self::$tables['objects'].".`id_packet`=".$id_packet:""));
        return $list;
    }
         
     /**
    * Запись статистики спецпредложений
    * @param string $action - клик/показ
    * @param array $objects - id объектов
    * @param array $packets - id пакетов
    * @param array $marker - метка
    * @return array (результат выборки из базы)
    */
    public static function Statistics($action, $objects=false, $packets=false, $from=false, $ref=false, $ip=false, $user_agent=false){    
        global $db;
        if(!Host::isBot()){
            if(empty($ref)) $ref = Host::getRefererURL();
            if(empty($user_agent)) $user_agent = $_SERVER['HTTP_USER_AGENT'];
            if(empty($ref) || empty($user_agent)) return false;
            if(!empty($objects)) {
                $objects_sql = [];
                foreach($objects as $object) $objects_sql[] = "(".(is_array($object)?$object['id']:$object).",".(!empty($from)?3:1).",'".$ref."'".($action!=''?', "'.Host::getUserIp().'"':'').($action!=''?', "'.$db->real_escape_string($user_agent).'"':'').")";
                $db->query("INSERT INTO ".self::$tables[$action]['objects']." (id_parent, `from` , `ref`".($action!=''?', `ip`':'').($action!=''?', `user_agent`':'').") VALUES ".implode(",",$objects_sql).";");
                
            }
            if(!empty($packets)) {
                $packets_sql = [];
                foreach($packets as $packet) $packets_sql[] = "(".(is_array($packet)?$packet['id']:$packet)."".($action=='click'?', "'.Host::getUserIp().'"':'').")";
                $db->query("INSERT INTO ".self::$tables[$action]['packets']." (id_parent".($action=='click'?', `ip`':'').") VALUES ".implode(",",$packets_sql).";");
                
            }
        }
    }
}

?>