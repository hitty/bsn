<?php
abstract class Banners {
    public static $tables = array();
    public static function Init(){
        self::$tables = Config::Get('sys_tables');
    }

    
    /**
    * запись статистики
    * 
    * @param mixed $action
    * @param mixed $objects
    * @param mixed $packets
    * @param mixed $ref
    * @param mixed $ip
    * @param mixed $user_agent
    */
    public static function Statistics($action, $id, $estate_type = false, $position = false, $ref = false, $real_ref = false, $ip=false, $user_agent=false){    
        global $db;
        
        //если вызов для группы, запускаем отдельно для каждого
        if(is_array($id)){
            $res = true;
            //массив id
            if(Validate::isDigit($id[0])){
                $id = array_filter($id, Validate::isDigit);
                foreach($id as $key=>$banner_id) $res *= Banners::Statistics($action, $banner_id, $estate_type, $position, $ref, $real_ref, $ip, $user_agent);
                return $res;
            }else{
                foreach($id as $key=>$banner) $res *= Banners::Statistics($action, $banner['id'], $estate_type, $position, $ref, $real_ref, $ip, $user_agent);
                return $res;
            }
        }
        
        self::$tables = Config::$values['sys_tables'];
        //1 клик в минуту
        $time = $db->fetch("SELECT TIMESTAMPDIFF(MINUTE, `datetime`, NOW()) as `time` 
                            FROM ".self::$tables['banners_stats_click_day']." 
                            WHERE id_parent = ? AND ip = ? ORDER BY id DESC", $id, Host::getUserIp());
        if(!empty($time) && ( ( $time['time'] < 2 && $action == 'click')  ) ) return false;
        if(empty($ref)) $ref = Host::getRefererURL();
        if(empty($ip)) $ip = Host::getUserIp();
        
        $info = array(
             'id_parent' => $id
            ,'position' => $position 
            ,'ref' => $ref
            ,'real_ref' => Host::getRefererURL()
            ,'ip' => $ip 
            ,'browser' => $_SERVER['HTTP_USER_AGENT']
        );
        
        switch($action){
            case "click": 
                if( !Host::isBsn( "banners_stats_click_day", $id ) ) $res = $db->insertFromArray( self::$tables['banners_stats_click_day'], $info);
                break;
            case "show": 
                $res = $db->insertFromArray( self::$tables['banners_stats_show_day'], $info) ;
                $db->querys("UPDATE " . self::$tables['banners'] . " SET days_views = days_views + 1 WHERE id = ?", $id );
                break;
            default:
                return false;
        }
        return $res;
    }
    
    /**
    * получение списка объектов
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @param mixed $banners_type - алиас типа недвижимости
    * @return array of arrays
    */
    public static function getList($count = 0, $from = 0, $where = "", $banners_type = "", $get_rnd_slice = false){
        global $db;
        self::$tables = Config::$values['sys_tables'];
        
        if(!empty($banners_type)){
            $additional_fields = ",IF(".self::$tables['banners'].".direct_link LIKE '%https://www.bsn.int%','internal','external') AS link_type,
                                  1 AS is_banners_banner";
            switch($banners_type){
                case "build": 
                    $banners_type = 2;
                    break;
                case "live": 
                    $banners_type = 1;
                    break;
                case "commercial": 
                    $banners_type = 3;
                    break;
                case "country": 
                    $banners_type = 4;
                    break;
                case "zhiloy_kompleks": 
                    $banners_type = 5;
                    break;
                case "country": 
                    $banners_type = 6;
                    break;
                case "cottages": 
                    $banners_type = 7;
                    break;
                default: return false;
            }
            $where_block = "WHERE ".self::$tables['banners'].".zones & " . pow( 2, $banners_type ) . " AND
                           ".self::$tables['banners'].".enabled = 1 AND 
                           ".self::$tables['banners'].".published = 1 AND 
                           ".self::$tables['banners'].".zones > 0 AND
                           ".self::$tables['banners'].".date_end > CURDATE() AND 
                           ".self::$tables['banners'].".date_start <= CURDATE()";
            if(!empty($get_rnd_slice)){
                $from = 0;
                $count = 0;
            }
        }else{
            $where_block = !empty($where) ? "WHERE ".$where : "";
            $join_block = "";
        }
        $order_by = !empty($order_by) ? $order_by : self::$tables['banners'].".id";
        $sql = "SELECT 
                    ".self::$tables['banners'].".*, 
                       IF(".self::$tables['banners'].".utm = 2, ".self::$tables['banners'].".direct_link, 
                            CONCAT( 
                                ".self::$tables['banners'].".direct_link,
                                '?',
                                CONCAT('utm_source=', ".self::$tables['banners'].".utm_source), 
                                CONCAT('&', 'utm_medium=', ".self::$tables['banners'].".utm_medium),
                                IF(utm_campaign!='', CONCAT('&', 'utm_campaign=', ".self::$tables['banners'].".utm_campaign), ''),
                                IF(utm_content!='', CONCAT('&', 'utm_content=', ".self::$tables['banners'].".utm_content), '')
                            )
                       )
                       as `direct_link` , 

                    CONCAT('/','".Config::$values['img_folders']['banners']."','/',".self::$tables['banners'].".`img_src`) as photo,
                    DATE_FORMAT(".self::$tables['banners'].".`date_start`,'%d.%m.%Y') as `normal_date_start`,
                    DATE_FORMAT(".self::$tables['banners'].".`date_end`,'%d.%m.%Y') as `normal_date_end`,
                    ".self::$tables['banners_positions'].".title as position_title,
                    ".self::$tables['agencies'].".title as agency_title,
                    ".self::$tables['managers'].".name as manager_name,
                    ".self::$tables['managers'].".email as manager_email
                    ".(!empty($additional_fields) ? $additional_fields : "")."
                FROM ".self::$tables['banners']."
                LEFT JOIN ".self::$tables['managers']." ON ".self::$tables['managers'].".id = ".self::$tables['banners'].".id_manager
                LEFT JOIN ".self::$tables['banners_positions']." ON ".self::$tables['banners_positions'].".id = ".self::$tables['banners'].".id_position
                LEFT JOIN ".self::$tables['users']." ON ".self::$tables['users'].".id = ".self::$tables['banners'].".id_user
                LEFT JOIN ".self::$tables['agencies']." ON ".self::$tables['agencies'].".id = ".self::$tables['users'].".id_agency 
                ".$where_block."
                GROUP BY ".self::$tables['banners'].".id
                ORDER BY " .$order_by . " 
                ". ( !empty($count) ? " LIMIT ".$from.",".$count : "" );
        
        $list = $db->fetchall($sql);
        
        if(!empty($get_rnd_slice)){
            shuffle($list);
            $list = array_slice($list, 0, $get_rnd_slice);
        } 
        
        return $list;        
    }
    /**
    * получение списка объектов с общей статистикой
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param integer $from - начиная с этого элемента
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @return array of arrays
    */
    public static function getItemStats($id = false, $item = false){
        global $db;
        self::$tables = Config::$values['sys_tables'];
        if(empty($item)) {
            $item = $db->fetch("SELECT * FROM ".self::$tables['banners']." WHERE id = ?", $id);
            $id = $item['id'];      
        }
        
        $stats = $db->fetch("SELECT 
                                main.*,
                                DATEDIFF(main.date_end, CURDATE()) as date_end_diff,
                                DATEDIFF(CURDATE(), main.date_start) as date_start_diff,
                                DATE_FORMAT(main.`date_start`,'%d.%m.%Y') as `normal_date_start`,
                                DATE_FORMAT(main.`date_end`,'%d.%m.%Y') as `normal_date_end`,
                                IF(main.`date_start`<=NOW() AND main.`date_end`>=NOW(), 'true', 'false') as `compare`,                            
                                IFNULL(a.cnt_day,0) as cnt_day,
                                IFNULL(a.cnt_day,0) + IFNULL(b.cnt_full,0) as cnt_full,
                                IFNULL(a.cnt_day,0) + IFNULL(pb.cnt_period,0) as cnt_period,
                                IFNULL(e.cnt_full_yesterday,0) as cnt_full_yesterday,
                                IFNULL(aa.cnt_click_day,0) as cnt_click_day,
                                IFNULL(aa.cnt_click_day,0) + IFNULL(bb.cnt_click_full,0) as cnt_click_full
                                
                        FROM ".self::$tables['banners']." main
                        LEFT JOIN (SELECT 
                                        COUNT(*) as cnt_day, id_parent 
                                        FROM ".self::$tables['banners_stats_show_day']." 
                                        WHERE
                                            id_parent = ".$item['id']."
                                        GROUP BY id_parent
                        ) a ON a.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_full, id_parent                        
                                   FROM ".self::$tables['banners_stats_show_full']." 
                                   WHERE
                                        id_parent = ".$item['id']."
                                   GROUP BY id_parent
                        ) b ON b.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_period, id_parent                        
                                   FROM ".self::$tables['banners_stats_show_full']." 
                                   WHERE
                                        id_parent = ".$item['id']." AND `date` >= '" . $item['date_start'] ."'
                                   GROUP BY id_parent
                        ) pb ON pb.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        amount as cnt_full_yesterday, id_parent                   
                                   FROM ".self::$tables['banners_stats_show_full']."    
                                   WHERE 
                                        date = CURDATE() - INTERVAL 1 DAY  AND
                                        id_parent = ".$item['id']."
                                   GROUP BY id_parent
                        ) e ON e.id_parent = main.id
                        
                        LEFT JOIN (SELECT 
                                        IFNULL(COUNT(*),0) as cnt_click_day, id_parent 
                                        FROM ".self::$tables['banners_stats_click_day']." 
                                        WHERE
                                            id_parent = ".$item['id']."
                                        GROUP BY id_parent
                        ) aa ON a.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_click_full, id_parent                        
                                   FROM ".self::$tables['banners_stats_click_full']." 
                                   WHERE
                                        id_parent = ".$item['id']."
                                   GROUP BY id_parent
                        ) bb ON b.id_parent = main.id

                        
                        LEFT JOIN (SELECT SUM(amount) as cnt_click_full_yesterday, id_parent        FROM ".self::$tables['banners_stats_click_full']."   WHERE id_parent = ".$item['id']." AND date = CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) ff ON ff.id_parent = main.id
                        WHERE main.id = ?
                        GROUP BY main.id", $id
        );   
        return $stats;
    }
    
    /**
    * получение рекламодателя баннера / места
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @return array of arrays
    */
    public static function getAgency($where){
        global $db;    
        self::$tables = Config::$values['sys_tables'];
        
        $item = $db->fetch("SELECT ".self::$tables['agencies'].".title 
                              FROM ".self::$tables['agencies']." 
                              LEFT JOIN ".self::$tables['users']." ON ".self::$tables['agencies'].".id = ".self::$tables['users'].".id_agency
                              WHERE ".$where 
                              
        );
        return $item;

    }
    public static function getItem( $position = false, $action = false, $id = false ){ 
       global $db;
       $where = array();
       if( !empty( $id ) ) {
            $where[] = self::$tables['banners'].".id = " . $id;
       } else {
           $where[] = self::$tables['banners'].".enabled = 1";
           $where[] = self::$tables['banners'].".published = 1";
           $where[] = self::$tables['banners'].".date_end > CURDATE()";
           $where[] = self::$tables['banners'].".date_start <= CURDATE()";
           $where[] = " ( " . self::$tables['banners'] . ".shows_limit = 0 OR " . self::$tables['banners'] . ".shows_limit > " . self::$tables['banners'] . ".days_views ) ";
           
           if( !empty( $action ) ) $estate_type = !empty( Config::Get('object_types')[$action] ) && !empty( Config::Get('object_types')[$action]['key'] ) ? Config::Get('object_types')[$action]['key'] : 0;                                                                                                                                                 
           if( !empty( $estate_type ) ) $where[] = " ( " . self::$tables['banners'] . ".zones & " . pow( 2, $estate_type ) . " OR " . self::$tables['banners'] . ".zones = 0 ) ";                      
           else $where[] = self::$tables['banners'] . ".zones = 0";                      
           
           if( !empty( $position ) ) $where[] = self::$tables['banners_positions'].".url = '" . $position . "'";
            
       }
        
       $item = $db->fetch("
                SELECT 
                       ".self::$tables['banners']." .* ,  
                       IF(utm = 2, direct_link, 
                            CONCAT( 
                                direct_link,
                                '?',
                                CONCAT('utm_source=',utm_source), 
                                CONCAT('&', 'utm_medium=',utm_medium),
                                IF(utm_campaign!='', CONCAT('&', 'utm_campaign=',utm_campaign), ''),
                                IF(utm_content!='', CONCAT('&', 'utm_content=', utm_content), '')
                            )
                       )
                       as `direct_link` , 
                       IF(`priority` = 100, 100, `priority`*(RAND()*100/`priority`)) as `priority`,
                       ".self::$tables['agencies'].".title as agency_title
                FROM  ".self::$tables['banners']."  
                LEFT JOIN ".self::$tables['banners_positions']."  ON ".self::$tables['banners_positions'].".id = ".self::$tables['banners'].".id_position 
                LEFT JOIN ".self::$tables['users']." ON ".self::$tables['users'].".id = ".self::$tables['banners'].".id_user
                LEFT JOIN ".self::$tables['agencies']." ON ".self::$tables['agencies'].".id = ".self::$tables['users'].".id_agency 
                WHERE  " . implode(" AND ", $where ) . "
                GROUP BY ".self::$tables['banners'].".id
                ORDER BY `priority`*RAND() DESC
                LIMIT 1
        ");  
        return $item;     
    } 
    
   
    /**
    * читаем для баннера информацию по агентству-хозяину
    * 
    * @param mixed $id - id баннера
    * @param mixed $fields - дополнительные поля если нужны
    */
    public static function getOwnerAgencyInfo($id,$fields = false,$agency_id = false,$for_popup = false){
        if((empty($id) || !Validate::isDigit($id)) && empty($agency_id)) return false;
        if(!empty($fields) && is_array($fields)) $fields = implode(',',$fields);
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        if(empty($agency_id)){
            $agency_id = $db->fetch("SELECT ".$sys_tables['users'].".id_agency
                                 FROM ".$sys_tables['banners']." 
                                 LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['banners'].".id_user = ".$sys_tables['users'].".id
                                 WHERE ".$sys_tables['banners'].".id = ?",$id);
            if(empty($agency_id) || empty($agency_id['id_agency'])) return false;
            else $agency_id = $agency_id['id_agency'];
        }
        $info = $db->fetch("SELECT ".$sys_tables['agencies'].".id,".$sys_tables['agencies'].".title,
                                   CONCAT('/organizations/company/',chpu_title,'/') AS company_page_url,
                                   LEFT(".$sys_tables['agencies_photos'].".name,2) as agency_photo_folder,
                                   ".$sys_tables['agencies_photos'].".name as agency_photo,
                                   ".(!empty($for_popup) ? "IF(phone_2 <> '',phone_2,advert_phone) AS phone" : "advert_phone").",
                                   main_color,second_color
                            ".(!empty($fields) ? ", ".$fields : "")." 
                            FROM ".$sys_tables['agencies']."
                            LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies'].".id_main_photo = ".$sys_tables['agencies_photos'].".id
                            WHERE ".$sys_tables['agencies'].".id = ?",$agency_id);
        return $info;
    }

    /**
    * статистика баннера по дням
    * 
    * @param mixed $id - id баннера
    * @param mixed $fields - дополнительные поля если нужны
    */    
    public static function getTotalStats( $id, $date_start, $date_end, $today_included = true ){
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        
        return $db->fetchall("
                SELECT 
                    IFNULL(a.show_amount,0) as show_amount, 
                    IFNULL(b.click_amount,0) as click_amount,
                    IFNULL(fb.click_amount,0) as click_facebook_amount,
                    IFNULL(bsn.click_amount,0) as click_bsn_amount,
                    a.date 
                FROM 
                (
                    (
                      SELECT 
                          IFNULL(`amount`,0) as show_amount, 
                          DATE_FORMAT(`date`,'%d.%m.%Y') as date
                      FROM ".$sys_tables['banners_stats_show_full']."
                      WHERE
                          `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                          `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
                      GROUP BY `date`
                    ) a
                    LEFT JOIN 
                    (
                      SELECT 
                          SUM(IFNULL(`amount`,0)) as click_amount, 
                          DATE_FORMAT(`date`,'%d.%m.%Y') as date
                      FROM ".$sys_tables['banners_stats_click_full']."
                      WHERE
                          `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                          `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
                      GROUP BY `date`
                     ) b ON a.date = b.date
                    LEFT JOIN 
                    (
                      SELECT 
                          SUM(IFNULL(`amount`,0)) as click_amount, 
                          DATE_FORMAT(`date`,'%d.%m.%Y') as date
                      FROM ".$sys_tables['banners_stats_click_full']."
                      WHERE
                          `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                          `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND 
                          `id_parent` = ".$id." AND
                          `from` = 2
                      GROUP BY `date`
                     ) fb ON a.date = fb.date
                    LEFT JOIN 
                    (
                      SELECT 
                          SUM(IFNULL(`amount`,0)) as click_amount, 
                          DATE_FORMAT(`date`,'%d.%m.%Y') as date
                      FROM ".$sys_tables['banners_stats_click_full']."
                      WHERE
                          `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                          `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND 
                          `id_parent` = ".$id." AND
                          `from` = 1
                      GROUP BY `date`
                     ) bsn ON a.date = bsn.date
                )"
                .(!empty($today_included) ? 
                  " UNION (
                        SELECT 
                            IFNULL(aa.show_amount,0) as show_amount, 
                            IFNULL(bb.click_amount,0) as click_amount,
                            IFNULL(fbfb.click_amount,0) as click_facebook_amount,
                            IFNULL(bsnbsn.click_amount,0) as click_bsn_amount,
                            aa.date 
                        FROM 
                        (   SELECT
                              IFNULL(COUNT(*),0) as show_amount,
                              'сегодня' as date,
                              id_parent
                          FROM ".$sys_tables['banners_stats_show_day']."
                          WHERE `id_parent` = ".$id."
                        ) aa
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as click_amount,
                              'сегодня' as date,
                              id_parent
                          FROM ".$sys_tables['banners_stats_click_day']."
                          WHERE  `id_parent` = ".$id."
                         ) bb ON aa.id_parent = bb.id_parent
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as click_amount,
                              'сегодня' as date,
                              id_parent
                          FROM ".$sys_tables['banners_stats_click_day']."
                          WHERE  
                            `id_parent` = ".$id."
                            AND `from` = 1
                         ) bsnbsn ON aa.id_parent = bsnbsn.id_parent
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as click_amount,
                              'сегодня' as date,
                              id_parent
                          FROM ".$sys_tables['banners_stats_click_day']."
                          WHERE  
                            `id_parent` = ".$id."
                            AND `from` = 2
                         ) fbfb ON aa.id_parent = fbfb.id_parent
                    )"
                  : ""
                 )."
                ");   
    } 
   
}      

?>