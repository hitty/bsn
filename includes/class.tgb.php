<?php
abstract class Tgb {
    public static $tables = [];
    public static function Init(){
        self::$tables = Config::Get('sys_tables');
    }

    
    /**
    * запись статистики
    * 
    * @param mixed $action
    * @param mixed $objects
    * @param mixed $packets
    * @param mixed $from
    * @param mixed $ref
    * @param mixed $ip
    * @param mixed $user_agent
    */
    public static function Statistics($action, $id, $estate_type = false, $from = false, $position = false, $ref = false, $real_ref = false, $ip=false, $user_agent=false){    
        global $db;
        
        //если вызов для группы, запускаем отдельно для каждого
        if(is_array($id)){
            $res = true;
            //массив id
            if(Validate::isDigit($id[0])){
                $id = array_filter($id, Validate::isDigit);
                foreach($id as $key=>$tgb_id) $res *= Tgb::Statistics($action, $tgb_id, $estate_type, $from, $position, $ref, $real_ref, $ip, $user_agent);
                return $res;
            }else{
                foreach($id as $key=>$tgb) $res *= Tgb::Statistics($action, $tgb['id'], $estate_type, $from, $position, $ref, $real_ref, $ip, $user_agent);
                return $res;
            }
        }
        
        self::$tables = Config::$values['sys_tables'];
        //1 клик в минуту
        $time = $db->fetch("SELECT TIMESTAMPDIFF(MINUTE, `datetime`, NOW()) as `time` 
                            FROM ".self::$tables['tgb_stats_day_clicks']." 
                            WHERE id_parent = ? AND ip = ? ORDER BY id DESC", $id, Host::getUserIp());
        if(!empty($time) && $time['time'] < 2) return false;
        if(empty($position)) $position = 1;
        if(empty($ref)) $ref = Host::getRefererURL();
        if(empty($ip)) $ip = Host::getUserIp();
        switch($from){
            case 'lastnd': $from = 2; break;
            case 'dizbooklast': $from = 3; break;
            default: $from = 1; break;
        }
        switch($position){
            case 'top': $position = 1; break;
            case 'center': $position = 2; break;
            case 'right': $position = 3; break;    
            case 'in_estate': $position = 4;break;
            default: $position = 0; break;
        }      
        $estate_type_code = !empty($estate_type) ? 1 : 0;
        switch($action){
            case "click": 
                $table = self::$tables['tgb_stats_day_clicks'];
                if(!Host::isBsn("tgb_stats_day_clicks",$id)) $res = $db->querys("INSERT INTO ".$table." SET `id_parent`=?, `in_estate` = ?, `from` = ?, `position` = ?, ref=?, real_ref=?, ip=?", $id, $estate_type_code, $from, $position, $ref, Host::getRefererURL(), $ip);
                break;
            case "show": 
                $table = self::$tables['tgb_stats_day_shows'];
                if(!Host::isBsn("tgb_stats_day_shows",$id)) $res = $db->querys("INSERT INTO ".$table." SET `id_parent`=?, in_estate = ?, ip = ?, browser = ?, ref = ?", $id, $estate_type_code, $ip, $_SERVER['HTTP_USER_AGENT'],Host::getRefererURL());
                break;
            default:
                return false;
        }
        //пересчет времени кредитного клика для попандера
        if($from == 2 || $from == 3) tgb::setCreditTime();
        //сохранение статистики показов для метки
        $session_marker = Session::GetString('marker');
        if(!empty($session_marker) && !Host::isBsn("markers_stats_day_clicks",$session_marker) ) $db->querys("INSERT INTO ".self::$tables['markers_stats_day_clicks']." SET id_parent=?",$session_marker);
         
        return $res;
    }
    
    /**
    * получение списка объектов
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param integer $from - начиная с этого элемента
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @param mixed $tgb_type - алиас типа недвижимости
    * @return array of arrays
    */
    public static function getList($count = 0, $from = 0, $where = "", $tgb_type = "", $get_rnd_slice = false){
        global $db;
        self::$tables = Config::$values['sys_tables'];
        
        if(!empty($tgb_type)){
            $additional_fields = ",IF(".self::$tables['tgb_banners'].".direct_link LIKE '%https://www.bsn.ru%','internal','external') AS link_type,
                                  1 AS is_tgb_banner";
            $join_block = "LEFT JOIN ".self::$tables['tgb_campaigns']." ON ".self::$tables['tgb_campaigns'].".id = ".self::$tables['tgb_banners'].".id_campaign";
            switch($tgb_type){
                case "build": 
                    $tgb_type = 2;
                    break;
                case "live": 
                    $tgb_type = 1;
                    break;
                case "commercial": 
                    $tgb_type = 3;
                    break;
                case "country": 
                    $tgb_type = 4;
                    break;
                case "zhiloy_kompleks": 
                    $tgb_type = 5;
                    break;
                case "country": 
                    $tgb_type = 6;
                    break;
                case "cottages": 
                    $tgb_type = 7;
                    break;
                default: return false;
            }
            $where_block = "WHERE ".self::$tables['tgb_banners'].".in_estate_section & ".pow(2,$tgb_type)." AND
                           ".self::$tables['tgb_banners'].".enabled = 1 AND 
                           ".self::$tables['tgb_banners'].".published = 1 AND 
                           ".self::$tables['tgb_banners'].".in_estate_section > 0 AND
                           ".self::$tables['tgb_banners'].".date_end > CURDATE() AND 
                           ".self::$tables['tgb_banners'].".date_start <= CURDATE()";
            if(!empty($get_rnd_slice)){
                $from = 0;
                $count = 0;
            }
        }else{
            $where_block = !empty($where) ? "WHERE ".$where : "";
            $join_block = "";
        }
        $order_by = !empty($order_by) ? $order_by : self::$tables['tgb_banners'].".id_campaign,  ".self::$tables['tgb_banners'].".id";
        $sql = "SELECT 
                    ".self::$tables['tgb_banners'].".*, 
                       IF(".self::$tables['tgb_banners'].".utm = 2, ".self::$tables['tgb_banners'].".direct_link, 
                            CONCAT( 
                                ".self::$tables['tgb_banners'].".direct_link,
                                '?',
                                CONCAT('utm_source=', ".self::$tables['tgb_banners'].".utm_source), 
                                CONCAT('&', 'utm_medium=', ".self::$tables['tgb_banners'].".utm_medium),
                                IF(utm_campaign!='', CONCAT('&', 'utm_campaign=', ".self::$tables['tgb_banners'].".utm_campaign), ''),
                                IF(utm_content!='', CONCAT('&', 'utm_content=', ".self::$tables['tgb_banners'].".utm_content), '')
                            )
                       )
                       as `direct_link` , 

                    IF(".self::$tables['tgb_banners'].".img_link='',
                          CONCAT('/','".Config::$values['img_folders']['tgb']."','/',".self::$tables['tgb_banners'].".`img_src`),
                    ".self::$tables['tgb_banners'].".`img_link`) as photo,
                    DATE_FORMAT(".self::$tables['tgb_banners'].".`date_start`,'%d.%m.%Y') as `normal_date_start`,
                    DATE_FORMAT(".self::$tables['tgb_banners'].".`date_end`,'%d.%m.%Y') as `normal_date_end`,
                    ".self::$tables['tgb_banners_credits'].".id as credit_banner_id,
                    ".self::$tables['tgb_banners_credits'].".day_limit,
                    ".self::$tables['managers'].".name as manager_name,
                    ".self::$tables['managers'].".email as manager_email
                    ".(!empty($additional_fields) ? $additional_fields : "")."
                FROM ".self::$tables['tgb_banners']."
                LEFT JOIN ".self::$tables['tgb_banners_credits']." ON ".self::$tables['tgb_banners_credits'].".id_banner = ".self::$tables['tgb_banners'].".id
                LEFT JOIN ".self::$tables['managers']." ON ".self::$tables['managers'].".id = ".self::$tables['tgb_banners'].".id_manager
                ".$join_block."
                ".$where_block."
                GROUP BY ".self::$tables['tgb_banners'].".id
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
            $item = $db->fetch("SELECT * FROM ".self::$tables['tgb_banners']." WHERE id = ?", $id);
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
                                fffff.id_parent as id_cooooooooo,
                                IFNULL(a.cnt_day,0) + IFNULL(b.cnt_full,0) as cnt_full,
                                IFNULL(a.cnt_day,0) + IFNULL(pb.cnt_period,0) as cnt_period,
                                IFNULL(e.cnt_full_yesterday,0) as cnt_full_yesterday,
                                IFNULL(n.cnt_naydidom_click_day,0) as cnt_naydidom_click_day,
                                IFNULL(cc.cnt_click_day,0) + IFNULL(ccccc.cnt_context_click_day,0) as cnt_click_day,
                                IFNULL(cc.cnt_click_day,0), IFNULL(ccccc.cnt_context_click_day,0), IFNULL(dd.cnt_click_full,0) , IFNULL(dd.cnt_click_in_estate,0) , IFNULL(ddddd.cnt_context_click_full,0),
                                IFNULL(cc.cnt_click_day,0) + IFNULL(ccccc.cnt_context_click_day,0) + IFNULL(dd.cnt_click_full,0) + IFNULL(ddddd.cnt_context_click_full,0) as cnt_click_full,
                                IFNULL(cc.cnt_click_day,0) + IFNULL(ccccc.cnt_context_click_day,0) + IFNULL(pd.cnt_click_period,0) + IFNULL(ddddd.cnt_context_click_full,0) as cnt_click_period,
                                IFNULL(cc.cnt_click_day,0), 
                                IFNULL(ccccc.cnt_context_click_day,0), 
                                IFNULL(nd.cnt_naydidom_click_full,0) as bsn_naydidom_click_full, 
                                IFNULL(ndnd.cnt_naydidom_click_day,0) + IFNULL(fbfb.cnt_facebook_click_day,0) + IFNULL(pupu.cnt_popunder_click_day,0) + IFNULL(dzdz.cnt_dizbook_click_day,0) + IFNULL(nd.cnt_naydidom_click_full,0) + IFNULL(fb.cnt_facebook_click_full,0) + IFNULL(pu.cnt_popunder_click_full,0) + IFNULL(dz.cnt_dizbook_click_full,0) as cnt_credit_click_full, 
                                IFNULL(ndnd.cnt_naydidom_click_day,0) + IFNULL(fbfb.cnt_facebook_click_day,0) + IFNULL(pupu.cnt_popunder_click_day,0) + IFNULL(dzdz.cnt_dizbook_click_day,0) as cnt_credit_click_day, 
                                IFNULL(ddddd.cnt_context_click_full,0) as cnt_context_click_full,
                                IFNULL(ff.cnt_click_full_yesterday,0) + IFNULL(fffff.cnt_context_click_full_yesterday,0) as cnt_click_full_yesterday,
                                IFNULL(bs.cnt_bsn_click_full,0) as cnt_bsn_click_full, 
                                IFNULL(bs.cnt_bsn_click_full,0) + IFNULL(ddddd.cnt_context_click_full,0) as bsn_click_full,
                                ".self::$tables['tgb_banners_credits'].".id as credit_banner_id,
                                ".self::$tables['tgb_banners_credits'].".day_limit
                        FROM ".self::$tables['tgb_banners']." main
                        LEFT JOIN ".self::$tables['tgb_banners_credits']." ON ".self::$tables['tgb_banners_credits'].".id_banner = main.id
                        LEFT JOIN (SELECT 
                                        COUNT(*) as cnt_day, id_parent 
                                        FROM ".self::$tables['tgb_stats_day_shows']." 
                                        WHERE
                                            id_parent = ".$item['id']."
                                        GROUP BY id_parent
                        ) a ON a.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_full, id_parent                        
                                   FROM ".self::$tables['tgb_stats_full_shows']." 
                                   WHERE
                                        id_parent = ".$item['id']."
                                   GROUP BY id_parent
                        ) b ON b.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_period, id_parent                        
                                   FROM ".self::$tables['tgb_stats_full_shows']." 
                                   WHERE
                                        id_parent = ".$item['id']." AND `date` >= '" . $item['date_start'] ."'
                                   GROUP BY id_parent
                        ) pb ON pb.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        amount as cnt_full_yesterday, id_parent                   
                                   FROM ".self::$tables['tgb_stats_full_shows']."    
                                   WHERE 
                                        date = CURDATE() - INTERVAL 1 DAY  AND
                                        id_parent = ".$item['id']."
                                   GROUP BY id_parent
                        ) e ON e.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        IFNULL(COUNT(*),0) as cnt_click_day, id_parent            
                                   FROM ".self::$tables['tgb_stats_day_clicks']."   
                                   WHERE 
                                        id_parent = ".$item['id']." 
                                   GROUP BY id_parent
                        ) cc ON cc.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        IFNULL(COUNT(*),0) as cnt_naydidom_click_day, id_parent   
                                   FROM ".self::$tables['tgb_stats_day_clicks']."    
                                   WHERE 
                                        `from` = 2  AND
                                        id_parent = ".$item['id']."
                                   GROUP BY id_parent
                        ) n ON n.id_parent = main.id
                       
                        LEFT JOIN (SELECT 
                                        IFNULL(COUNT(*),0) as cnt_context_click_day, id_parent, '".$item['id_context']."' as id_join_parent     
                                    FROM ".self::$tables['context_stats_click_day']." 
                                    WHERE 
                                        id_parent IN (".(empty($item['id_context']) ? "0" : $item['id_context']).")
                        ) ccccc ON ccccc.id_join_parent = main.id_context
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_click_full, id_parent,
                                        SUM(IF(in_estate > 0,IFNULL(`amount`,0),0)) as cnt_click_in_estate
                                    FROM ".self::$tables['tgb_stats_full_clicks']." 
                                    WHERE 
                                        
                                        ".self::$tables['tgb_stats_full_clicks'].".id_parent = ".$item['id']." AND
                                        id_parent = ".$item['id']." 
                                    GROUP BY id_parent
                        ) dd ON dd.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_click_period, id_parent
                                    FROM ".self::$tables['tgb_stats_full_clicks']." 
                                    WHERE 
                                        
                                        ".self::$tables['tgb_stats_full_clicks'].".date >= '".$item['date_start']."' AND
                                        ".self::$tables['tgb_stats_full_clicks'].".id_parent = ".$item['id']." AND
                                        id_parent = ".$item['id']." 
                                    GROUP BY id_parent
                        ) pd ON pd.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_naydidom_click_full, 
                                        id_parent                  
                                    FROM ".self::$tables['tgb_stats_full_clicks']." 
                                    WHERE 
                                        ".self::$tables['tgb_stats_full_clicks'].".date >= '".$item['date_start']."' AND  
                                        ".self::$tables['tgb_stats_full_clicks'].".id_parent = ".$item['id']." AND
                                        id_parent = ".$item['id']." AND 
                                        `from` = 2
                                    GROUP BY id_parent
                        ) nd ON nd.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_facebook_click_full, 
                                        id_parent                  
                                    FROM ".self::$tables['tgb_stats_full_clicks']." 
                                    WHERE 
                                        ".self::$tables['tgb_stats_full_clicks'].".date >= '".$item['date_start']."' AND  
                                        ".self::$tables['tgb_stats_full_clicks'].".id_parent = ".$item['id']." AND
                                        id_parent = ".$item['id']." AND 
                                        `from` = 4
                                    GROUP BY id_parent
                        ) fb ON fb.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_popunder_click_full, 
                                        id_parent                  
                                    FROM ".self::$tables['tgb_stats_full_clicks']." 
                                    WHERE 
                                        ".self::$tables['tgb_stats_full_clicks'].".date >= '".$item['date_start']."' AND  
                                        ".self::$tables['tgb_stats_full_clicks'].".id_parent = ".$item['id']." AND
                                        id_parent = ".$item['id']." AND 
                                        `from` = 5
                                    GROUP BY id_parent
                        ) pu ON pu.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_dizbook_click_full, 
                                        id_parent                  
                                    FROM ".self::$tables['tgb_stats_full_clicks']." 
                                    WHERE 
                                        ".self::$tables['tgb_stats_full_clicks'].".date >= '".$item['date_start']."' AND  
                                        ".self::$tables['tgb_stats_full_clicks'].".id_parent = ".$item['id']." AND
                                        id_parent = ".$item['id']." AND 
                                        `from` = 3
                                    GROUP BY id_parent
                        ) dz ON dz.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        IFNULL(COUNT(*),0) as cnt_naydidom_click_day, id_parent            
                                   FROM ".self::$tables['tgb_stats_day_clicks']."   
                                   WHERE 
                                        id_parent = ".$item['id']." AND `from` = 2
                                   GROUP BY id_parent
                        ) ndnd ON ndnd.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        IFNULL(COUNT(*),0) as cnt_facebook_click_day, id_parent            
                                   FROM ".self::$tables['tgb_stats_day_clicks']."   
                                   WHERE 
                                        id_parent = ".$item['id']." AND `from` = 4
                                   GROUP BY id_parent
                        ) fbfb ON fbfb.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        IFNULL(COUNT(*),0) as cnt_popunder_click_day, id_parent            
                                   FROM ".self::$tables['tgb_stats_day_clicks']."   
                                   WHERE 
                                        id_parent = ".$item['id']." AND `from` = 5
                                   GROUP BY id_parent
                        ) pupu ON pupu.id_parent = main.id
                        LEFT JOIN (SELECT 
                                        IFNULL(COUNT(*),0) as cnt_dizbook_click_day, id_parent            
                                   FROM ".self::$tables['tgb_stats_day_clicks']."   
                                   WHERE 
                                        id_parent = ".$item['id']." AND `from` = 3
                                   GROUP BY id_parent
                        ) dzdz ON dzdz.id_parent = main.id
                        
                        LEFT JOIN (SELECT 
                                        SUM(amount) as cnt_bsn_click_full, 
                                        id_parent       
                                   FROM ".self::$tables['tgb_stats_full_clicks']." 
                                   WHERE ".self::$tables['tgb_stats_full_clicks'].".`from` = 1 AND ".self::$tables['tgb_stats_full_clicks'].".date >= '".$item['date_start']."'
                                   GROUP BY id_parent
                        ) bs ON bs.id_parent = main.id
                        LEFT JOIN (  SELECT 
                                            SUM(".self::$tables['context_stats_click_full'].".amount) as cnt_context_click_full, 
                                            '".$item['id_context']."' as id_join_parent,
                                            ".self::$tables['context_stats_click_full'].".id_parent           
                                       FROM ".self::$tables['context_stats_click_full']." 
                                       WHERE 
                                            ".self::$tables['context_stats_click_full'].".`date` >= '".$item['context_date_start']."' AND 
                                            ".self::$tables['context_stats_click_full'].".`date` >= '".$item['date_start']."' AND 
                                            ".self::$tables['context_stats_click_full'].".id_parent IN (".(empty($item['id_context']) ? "0" : $item['id_context']).")
                        ) ddddd ON ddddd.id_join_parent = main.id_context 
                        LEFT JOIN (SELECT SUM(amount) as cnt_click_full_yesterday, id_parent        FROM ".self::$tables['tgb_stats_full_clicks']."   WHERE id_parent = ".$item['id']." AND date = CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) ff ON ff.id_parent = main.id
                        LEFT JOIN (SELECT SUM(amount) as cnt_context_click_full_yesterday, '".(empty($item['id_context']) ? "0" : $item['id_context'])."' as id_parent_context, id_parent FROM ".self::$tables['context_stats_click_full']." WHERE id_parent IN (".(empty($item['id_context']) ? "0" : $item['id_context']).") AND date = CURDATE() - INTERVAL 1 DAY) fffff ON fffff.id_parent_context IN ( main.id_context )
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
                              LEFT JOIN ".self::$tables['tgb_campaigns']." ON ".self::$tables['tgb_campaigns'].".id_user = ".self::$tables['users'].".id
                              WHERE ".$where 
                              
        );
        return $item;

    }
    public static function getClientList($estate_type = false){ 
       global $db;
       $where = [];
       $where[] = self::$tables['tgb_banners'].".enabled = 1";
       $where[] = self::$tables['tgb_banners'].".published = 1";
       $where[] = self::$tables['tgb_banners'].".date_end > CURDATE()";
       $where[] = self::$tables['tgb_banners'].".date_start <= CURDATE()";
        
        if(!empty($estate_type)){
            $estate_type_code = Config::Get('object_types')[$estate_type]['key'];
            $where[] = self::$tables['tgb_banners'] . ".in_estate_section & " . pow( 2, $estate_type_code );
            $where[] = self::$tables['tgb_banners'] . ".in_estate_section > 0";
        }
        $list = $db->fetchall("
            SELECT `id` ,  `slogan_1`,  `slogan_2`, `title` , `annotation` , `with_popup` , `direct_link` ,  'external' as `link_type`, `photo` ,  `get_pixel` ,  `img_src` ,  `id_campaign`, `priority`, `cnt`
            FROM (
                SELECT `id` ,  `slogan_1`,  `slogan_2`,  `title` , `annotation` , `with_popup` ,  `direct_link` ,  'external' as `link_type`, `photo` ,  `get_pixel` ,  `img_src` ,  `id_campaign`, `priority`, COUNT(*) as `cnt`
                FROM (
                    SELECT 
                           `id` ,  
                           `title` ,
                           `slogan_1`,  
                           `slogan_2`,
                           `annotation` ,  
                           (tgb_type = 2) AS with_popup,
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
                           'external' as `link_type`, 
                           `img_link` as photo,  
                           `get_pixel` , 
                           `img_src`,   
                           `id_campaign`, 
                           IF(`priority` = 100, 100, `priority`*(RAND()*100/`priority`)) as `priority`
                    FROM  ".self::$tables['tgb_banners']."  
                    WHERE  " . implode(" AND ", $where) . "
                    ORDER BY `priority`*RAND()
                ) as a

                GROUP BY  a.`id_campaign`
            ) b 
            WHERE b.cnt>1 OR b.priority>50
        ");  
        //для тех, у кого with_popup, читаем информацию по агентству-хозяину
        if(!empty($list)){
            $popup_ids = array_filter($list,function($e){return !empty($e['with_popup']);});
            if(!empty($popup_ids)){
                $popup_ids = implode(',',array_map(function($e){return $e['id'];},$popup_ids));
                $agency_info = $db->fetchall("SELECT ".self::$tables['tgb_banners'].".id AS banner_id, 
                                                     ".self::$tables['agencies'].".title AS agency_title,
                                                     IF(phone_2 <> '',phone_2,'') AS agency_phone
                                              FROM ".self::$tables['users']."
                                              LEFT JOIN ".self::$tables['tgb_campaigns']." ON ".self::$tables['users'].".id = ".self::$tables['tgb_campaigns'].".id_user
                                              LEFT JOIN ".self::$tables['tgb_banners']." ON ".self::$tables['tgb_campaigns'].".id = ".self::$tables['tgb_banners'].".id_campaign
                                              LEFT JOIN ".self::$tables['agencies']." ON ".self::$tables['users'].".id_agency = ".self::$tables['agencies'].".id
                                              WHERE ".self::$tables['tgb_banners'].".id IN (".$popup_ids.")",'banner_id');
                foreach($list as $k=>$item){
                    if(in_array($item['id'],explode(',',$popup_ids))){
                        $list[$k] = array_merge($list[$k],$agency_info[$item['id']]);
                    }
                }
            }
        }
        
        return $list;     
    } 
    public static function getItem($id){   
        global $db;
        $item = $db->fetch("SELECT *, 
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
                                   as `direct_link`

                            FROM ".self::$tables['tgb_banners']." 
                            WHERE id = " . $id 
        );
        return $item;
        
    }
    /**
    * получение кол-ва кликов в день
    * @param string $id - id баннер
    * @return integer
    */
    public static function getClicksPerDay($id){
        global $db;
        self::$tables = Config::$values['sys_tables'];
        $item = Tgb::getItemStats($id);
        if(empty($item['cnt_click_full']) || empty($item['bsn_click_full'])) return 5;
        else {
            //кол-во кликов в день для открутки
            $click_per_day = ($item['clicks_limit'] - $item['cnt_click_full']) / $item['date_end_diff'];
            //среднее кол-во кликов бсн
            $click_bsn_per_day = $item['bsn_click_full'] / $item['date_start_diff'];
            $item['cnt_credit_click_full'] = $item['cnt_click_full'] - $item['bsn_click_full'];
            //кол-во кредитных кликов
            $click_credits_per_day = (int) ($click_per_day - $click_bsn_per_day) + 1;
            if($click_credits_per_day <=0 ) $click_credits_per_day = 0;
            return $click_credits_per_day;
        }
    }
    /**
    * рассчет времени клика с попандера
    * @param string $id - id баннера
    * @return integer
    */   
    public static function setCreditTime($id = false){
        global $db;
        self::$tables = Config::$values['sys_tables'];
        $time_start = new DateTime("9:00:00"); //начало открутки
        $time_end = new DateTime("20:00:00");  // окончание открутки
        $time_total = $time_end->diff($time_start)->h * 60; // общее кол-во минут открутки
        $time_now = new DateTime(date('H:i:s'));
        if($time_now > $time_start && $time_now < $time_end ){
            $time_from_begin_difference = $time_now->diff($time_start)->h * 60 + $time_now->diff($time_start)->i; // кол-во прошедших минут со старта
            $time_to_end_difference = $time_end->diff($time_now)->h * 60 + $time_end->diff($time_now)->i; // кол-во прошедших минут оставшихся до окончания
            // формула рассчета : кол-во прошедших минут со старта > (кол-во кликов + rand) * ( общее время открутки / требуемое кол-во кликов)
            $item = $db->fetch("SELECT 
                        main.*, 
                           IF(main.utm = 2, main.direct_link, 
                                CONCAT( 
                                    main.direct_link,
                                    '?',
                                    CONCAT('utm_source=', main.utm_source), 
                                    CONCAT('&', 'utm_medium=', main.utm_medium),
                                    IF(utm_campaign!='', CONCAT('&', 'utm_campaign=', main.utm_campaign), ''),
                                    IF(utm_content!='', CONCAT('&', 'utm_content=', main.utm_content), '')
                                )
                           )
                           as `direct_link` , 

                        IF(main.img_link='',
                              CONCAT('/','".Config::$values['img_folders']['tgb']."','/',main.`img_src`),
                        main.`img_link`) as photo,
                        DATE_FORMAT(main.`date_start`,'%d.%m.%Y') as `normal_date_start`,
                        DATE_FORMAT(main.`date_end`,'%d.%m.%Y') as `normal_date_end`,
                        bc.id as credit_banner_id,
                        bc.day_limit,
                        IFNULL(a.cnt_clicks,0) as cnt_clicks
                FROM ".self::$tables['tgb_banners']." main
                LEFT JOIN ".self::$tables['tgb_banners_credits']." bc ON bc.id_banner = main.id
                LEFT JOIN (SELECT 
                                COUNT(*) as cnt_clicks, id_parent 
                                FROM ".self::$tables['tgb_stats_day_clicks']." 
                                WHERE
                                    `from` IN (2,3,4,5)
                                GROUP BY id_parent
                ) a ON a.id_parent = main.id                        
                WHERE 
                    main.credit_time <= NOW() AND 
                    main.credit_clicks = 1 AND 
                    main.only_popunder_clicks != 2 AND 
                    ".(!empty($id) ? " main.id = ".$id : 
                    "main.published = 1 AND
                    main.clicks_limit > 0 AND
                    main.date_start <= NOW() AND
                    main.date_end > CURDATE() AND
                    bc.day_limit > IFNULL(a.cnt_clicks,0) 
                    "
                    )."
                    
                GROUP BY main.id
                ORDER BY RAND()
            ");
            //расчет нового интервала для клика
            $total_clicks = $item['cnt_clicks'] + 1;
            if($total_clicks < $item['day_limit']) {
                $new_credit_time_rand = (int) ( ( $time_to_end_difference / ($item['day_limit'] - $total_clicks ) + $time_to_end_difference / ($item['day_limit'] - $total_clicks ) * mt_rand(-0.2, 0.8) ) * 60 );
                $db->querys("UPDATE ".self::$tables['tgb_banners']." SET credit_time = NOW() + INTERVAL ".$new_credit_time_rand." SECOND WHERE id = ?", $item['id']);
            }
            return $item;    
        }        
        return false;
        
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
                                 FROM ".$sys_tables['tgb_campaigns']." 
                                 LEFT JOIN ".$sys_tables['tgb_banners']." ON ".$sys_tables['tgb_campaigns'].".id = ".$sys_tables['tgb_banners'].".id_campaign
                                 LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['tgb_campaigns'].".id_user = ".$sys_tables['users'].".id
                                 WHERE ".$sys_tables['tgb_banners'].".id = ?",$id);
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
    * запись статистики всплывашки ТГБ
    * 
    * @param mixed $action - ("","callback","application")
    * @param mixed $id
    */
    public static function popupStatisitics($action,$id){
        if(empty($id)) return false;
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        
        if(Host::isBsn("tgb_stats_popup_day",$id)) return false;
        
        switch($action){
            case "": $action = 0; break;
            case "callback-click": $action = 1; break;
            case "application": $action = 2; break;
            default:return false;
        }
        //выбираем раздел из referer:
        $referer = Host::getRefererURL();
        @$in_estate = explode('/',$referer)[3];
        if(empty($in_estate)) return false;
        switch($in_estate){
            case "build": 
                $in_estate = 2;
                break;
            case "live": 
                $in_estate = 1;
                break;
            case "commercial": 
                $in_estate = 3;
                break;
            case "country": 
                $in_estate = 4;
                break;
            case "zhiloy_kompleks": 
                $in_estate = 5;
                break;
            case "country": 
                $in_estate = 6;
                break;
            case "cottags": 
                $in_estate = 7;
                break;
            default: return false;
        }
        
        $insert_info = array("id_parent" => $id,"action"=>$action,"in_estate"=>$in_estate,"ref"=>$referer,"ip"=>Host::getUserIp());
        return $db->insertFromArray($sys_tables["tgb_stats_popup_day"],$insert_info);
    }
}      

?>