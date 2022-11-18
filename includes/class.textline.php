<?php
class TextLine {
    private $tables = [];
    public function __construct(){
        $this->tables = Config::$sys_tables;
    }
    /**
    * получение списка объектов
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param integer $from - начиная с этого элемента
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @return array of arrays
    */
    public function getList($count = 0, $from = 0, $where = ""){
        global $db;
        $where = !empty($where) ? $where : 1;
        $list = $db->fetchall("SELECT 
                                ".$this->tables['textline_banners'].".*, 
                                   IF(".$this->tables['textline_banners'].".direct_link='','',
                                       IF(".$this->tables['textline_banners'].".utm = 2, ".$this->tables['textline_banners'].".direct_link, 
                                            CONCAT( 
                                                ".$this->tables['textline_banners'].".direct_link,
                                                '?',
                                                CONCAT('utm_source=', ".$this->tables['textline_banners'].".utm_source), 
                                                CONCAT('&', 'utm_medium=', ".$this->tables['textline_banners'].".utm_medium),
                                                IF(utm_campaign!='', CONCAT('&', 'utm_campaign=', ".$this->tables['textline_banners'].".utm_campaign), ''),
                                                IF(utm_content!='', CONCAT('&', 'utm_content=', ".$this->tables['textline_banners'].".utm_content), '')
                                            )
                                       )
                                   )
                                   as `direct_link` , 

                        FROM ".$this->tables['textline_banners']."
                        WHERE ".$where."
                        GROUP BY ".$this->tables['textline_banners'].".id
                        ORDER BY ".$this->tables['textline_banners'].".id_campaign,  ".$this->tables['textline_banners'].".id
                        ". ( !empty($count) ? " LIMIT ".$from.",".$count : "" )
        );    
        return $list;        
    }
    /**
    * получение списка объектов с общей статистикой
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param integer $from - начиная с этого элемента
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @return array of arrays
    */
    public function getListFull($count=0, $from=0, $where=""){
        global $db;
        $where = !empty($where) ? $where : 1;
        $list = $db->fetchall("SELECT 
                                ".$this->tables['textline_banners'].".*, 
                                   IF(".$this->tables['textline_banners'].".direct_link='','',
                                       IF(".$this->tables['textline_banners'].".utm = 2, ".$this->tables['textline_banners'].".direct_link, 
                                            CONCAT( 
                                                ".$this->tables['textline_banners'].".direct_link,
                                                '?',
                                                CONCAT('utm_source=', ".$this->tables['textline_banners'].".utm_source), 
                                                CONCAT('&', 'utm_medium=', ".$this->tables['textline_banners'].".utm_medium),
                                                IF(utm_campaign!='', CONCAT('&', 'utm_campaign=', ".$this->tables['textline_banners'].".utm_campaign), ''),
                                                IF(utm_content!='', CONCAT('&', 'utm_content=', ".$this->tables['textline_banners'].".utm_content), '')
                                            )
                                       )
                                   )
                                   as `direct_link` , 
                                IFNULL(a.cnt_day,0) as cnt_day,
                                IFNULL(a.cnt_day,0) + IFNULL(b.cnt_full,0) as cnt_full,
                                IFNULL(e.cnt_full_yesterday,0) as cnt_full_yesterday,
                                IFNULL(cc.cnt_click_day,0) as cnt_click_day,
                                IFNULL(cc.cnt_click_day,0) + IFNULL(dd.cnt_click_full,0) as cnt_click_full,
                                IFNULL(ff.cnt_click_full_yesterday,0) as cnt_click_full_yesterday
                        FROM ".$this->tables['textline_banners']."
                        LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$this->tables['textline_stats_day_shows']." GROUP BY id_parent) a ON a.id_parent = ".$this->tables['textline_banners'].".id
                        LEFT JOIN (SELECT SUM(amount) as cnt_full, id_parent                        FROM ".$this->tables['textline_stats_full_shows']." GROUP BY id_parent) b ON b.id_parent = ".$this->tables['textline_banners'].".id
                        LEFT JOIN (SELECT amount as cnt_full_yesterday, id_parent                   FROM ".$this->tables['textline_stats_full_shows']."    WHERE date = CURDATE() - INTERVAL 1 DAY GROUP BY id_parent) e ON e.id_parent = ".$this->tables['textline_banners'].".id
                        LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cnt_click_day, id_parent            FROM ".$this->tables['textline_stats_day_clicks']."    GROUP BY id_parent) cc ON cc.id_parent = ".$this->tables['textline_banners'].".id
                        LEFT JOIN (SELECT SUM(amount) as cnt_click_full, id_parent                  FROM ".$this->tables['textline_stats_full_clicks']."   GROUP BY id_parent) dd ON dd.id_parent = ".$this->tables['textline_banners'].".id
                        LEFT JOIN (SELECT SUM(amount) as cnt_click_full_yesterday, id_parent        FROM ".$this->tables['textline_stats_full_clicks']."   WHERE date = CURDATE() - INTERVAL 1 DAY  GROUP BY id_parent) ff ON ff.id_parent = ".$this->tables['textline_banners'].".id
                        WHERE ".$where."  
                        GROUP BY ".$this->tables['textline_banners'].".id
                        ORDER BY ".$this->tables['textline_banners'].".id_campaign,  ".$this->tables['textline_banners'].".id
                        ". ( !empty($count) ? " LIMIT ".$from.",".$count : "" )
        );    
        echo $db->error;    
        return $list;
    }
    
    /**
    * получение рекламодателя объявления / места
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @return array of arrays
    */
    public function getAgency($where){
        global $db;    
        $item = $db->fetch("SELECT ".$this->tables['agencies'].".title 
                              FROM ".$this->tables['agencies']." 
                              LEFT JOIN ".$this->tables['users']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                              LEFT JOIN ".$this->tables['textline_campaigns']." ON ".$this->tables['textline_campaigns'].".id_user = ".$this->tables['users'].".id
                              WHERE ".$where 
                              
        );
        return $item;

    }
    
    /**
    * получение статистики объявления , среднее количество кликов 
    * @param string $id - id баннер
    * @return array of arrays
    */
    public function getItemStats($id){
        global $db;  
        $item = $db->fetch("SELECT 
                                ".$this->tables['textline_banners'].".*
                        FROM ".$this->tables['textline_banners']."
                        WHERE ".$this->tables['textline_banners'].".id = ".$id."
                        GROUP BY ".$this->tables['textline_banners'].".id
                        ORDER BY ".$this->tables['textline_banners'].".id_campaign,  ".$this->tables['textline_banners'].".id"
        );   
        return $item; 
    }

    /**
    * получение списка РК с общей статистикой
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param integer $from - начиная с этого элемента
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @return array of arrays
    */
    public function getCampaignsListFull($limit, $where=""){  
        global $db;
        $where = !empty($where) ? $where : 1; 
        $sql = "SELECT 
                        ".$this->tables['textline_campaigns'].".*,
                        DATE_FORMAT(".$this->tables['textline_campaigns'].".`date_start`,'%d.%m.%Y') as `normal_date_start`,
                        DATE_FORMAT(".$this->tables['textline_campaigns'].".`date_end`,'%d.%m.%Y') as `normal_date_end`,
                        IF(".$this->tables['textline_campaigns'].".direct_link='','',
                            IF(".$this->tables['textline_campaigns'].".utm = 2, ".$this->tables['textline_campaigns'].".direct_link, 
                                CONCAT( 
                                    ".$this->tables['textline_campaigns'].".direct_link,
                                    '?',
                                    CONCAT('utm_source=', ".$this->tables['textline_campaigns'].".utm_source), 
                                    CONCAT('&', 'utm_medium=', ".$this->tables['textline_campaigns'].".utm_medium),
                                    IF(utm_campaign!='', CONCAT('&', 'utm_campaign=', ".$this->tables['textline_campaigns'].".utm_campaign), ''),
                                    IF(utm_content!='', CONCAT('&', 'utm_content=', ".$this->tables['textline_campaigns'].".utm_content), '')
                                )
                            )
                        )
                        as `direct_link` , 
                        IF(".$this->tables['textline_campaigns'].".date_start > CURDATE(), 2,
                            IF(".$this->tables['textline_campaigns'].".date_end <= CURDATE(), 2, 1)
                        ) as published,
                        ".$this->tables['managers'].".name as manager_name,
                        ".$this->tables['managers'].".email as manager_email,
                        IFNULL(a.cnt_1,0) as cnt_1, 
                        IFNULL(b.cnt_2,0) as cnt_2 ,
                        IFNULL(d.cnt_day,0) as cnt_day,
                        IFNULL(d.cnt_full,0) + IFNULL(d.cnt_day,0) as cnt_full,
                        IFNULL(e.cnt_click_day,0) as cnt_click_day,
                        IFNULL(e.cnt_click_full,0) + IFNULL(e.cnt_click_day,0) as cnt_click_full,        
                        IFNULL(g.cnt_yesterday,0) as cnt_yesterday,
                        IFNULL(h.cnt_click_yesterday,0) as cnt_click_yesterday,
                        LEFT(".$this->tables['agencies_photos'].".name,2) as agency_photo_folder,
                        ".$this->tables['agencies_photos'].".name as agency_photo,
                        ".$this->tables['agencies'].".title as agency_title
                FROM ".$this->tables['textline_campaigns']."
                LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->tables['textline_campaigns'].".id_user
                LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies'].".id_main_photo = ".$this->tables['agencies_photos'].".id
                LEFT JOIN ".$this->tables['managers']." ON ".$this->tables['managers'].".id = ".$this->tables['textline_campaigns'].".id_manager
                LEFT JOIN (SELECT COUNT(*) as cnt_1, id_campaign FROM ".$this->tables['textline_banners']." WHERE  `enabled` = 1 GROUP BY `id_campaign`) a ON a.id_campaign = ".$this->tables['textline_campaigns'].".id
                LEFT JOIN (SELECT COUNT(*) as cnt_2, id_campaign FROM ".$this->tables['textline_banners']." GROUP BY `id_campaign`) b ON b.id_campaign = ".$this->tables['textline_campaigns'].".id
                LEFT JOIN (
                           SELECT aa.id, SUM(ab.cnt_day) as cnt_day, SUM(ac.cnt_full) as cnt_full, aa.id_campaign 
                           FROM ".$this->tables['textline_banners']."  aa 
                           LEFT JOIN (
                            SELECT COUNT(*) as cnt_day, id_parent FROM ".$this->tables['textline_stats_day_shows']." GROUP BY id_parent
                           ) ab ON ab.id_parent = aa.id 
                           LEFT JOIN (
                             SELECT SUM(amount) as cnt_full, id_parent FROM ".$this->tables['textline_stats_full_shows']." GROUP BY id_parent
                           ) ac ON ac.id_parent = aa.id GROUP BY aa.id_campaign
                ) d ON d.id_campaign = ".$this->tables['textline_campaigns'].".id
                LEFT JOIN (
                            SELECT ba.id, SUM(bb.cnt_click_day) as cnt_click_day, SUM(bc.cnt_click_full) as cnt_click_full, ba.id_campaign 
                            FROM ".$this->tables['textline_banners']."  ba 
                            LEFT JOIN (
                                SELECT COUNT(*) as cnt_click_day, id_parent FROM ".$this->tables['textline_stats_day_clicks']." GROUP BY id_parent
                            ) bb ON bb.id_parent = ba.id 
                            LEFT JOIN (
                                SELECT SUM(amount) as cnt_click_full, id_parent FROM ".$this->tables['textline_stats_full_clicks']." GROUP BY id_parent
                            ) bc ON bc.id_parent = ba.id GROUP BY ba.id_campaign
                ) e ON e.id_campaign = ".$this->tables['textline_campaigns'].".id
                LEFT JOIN (
                           SELECT gg.id, SUM(gc.cnt_yesterday) as cnt_yesterday, gg.id_campaign 
                           FROM ".$this->tables['textline_banners']."  gg 
                           LEFT JOIN (
                             SELECT SUM(amount) as cnt_yesterday, id_parent FROM ".$this->tables['textline_stats_full_shows']." WHERE date = CURDATE() - INTERVAL 1 DAY GROUP BY id_parent
                           ) gc ON gc.id_parent = gg.id GROUP BY gg.id_campaign
                ) g ON g.id_campaign = ".$this->tables['textline_campaigns'].".id
                LEFT JOIN (
                           SELECT hh.id, SUM(hc.cnt_click_yesterday) as cnt_click_yesterday, hh.id_campaign 
                           FROM ".$this->tables['textline_banners']."  hh 
                           LEFT JOIN (
                             SELECT SUM(amount) as cnt_click_yesterday, id_parent FROM ".$this->tables['textline_stats_full_clicks']." WHERE date = CURDATE() - INTERVAL 1 DAY GROUP BY id_parent
                           ) hc ON hc.id_parent = hh.id GROUP BY hh.id_campaign
                ) h ON h.id_campaign = ".$this->tables['textline_campaigns'].".id
                

        ";        
        if(!empty($where)) $sql .= " WHERE ".$where;
        $sql .= " ORDER BY ".$this->tables['textline_campaigns'].".id";
        if(!empty($limit)) $sql .= " LIMIT ".$limit; 
        $list = $db->fetchall($sql); 
        return $list;
    }       
    
    /**
    * получение случайноого объявления
    * @return array
    */    
    public function getRandomItem($type = false){
        global $db;
        $where = " AND " . $this->tables['textline_campaigns'] . ".`type` = " . ( !empty($type) ? $type : "1" );
        $sql = "SELECT 
                        ".$this->tables['textline_banners'].".*,
                        IF(".$this->tables['textline_campaigns'].".direct_link='', '',
                            IF(".$this->tables['textline_campaigns'].".utm = 2, ".$this->tables['textline_campaigns'].".direct_link, 
                                CONCAT( 
                                    ".$this->tables['textline_campaigns'].".direct_link,
                                    '?',
                                    CONCAT('utm_source=', ".$this->tables['textline_campaigns'].".utm_source), 
                                    CONCAT('&', 'utm_medium=', ".$this->tables['textline_campaigns'].".utm_medium),
                                    IF(".$this->tables['textline_campaigns'].".utm_campaign!='', CONCAT('&', 'utm_campaign=', ".$this->tables['textline_campaigns'].".utm_campaign), ''),
                                    IF(".$this->tables['textline_campaigns'].".utm_content!='', CONCAT('&', 'utm_content=', ".$this->tables['textline_campaigns'].".utm_content), '')
                                )
                            )
                        )
                        as `campaign_direct_link` , 
                        IF(".$this->tables['textline_banners'].".direct_link='','',
                            IF(".$this->tables['textline_banners'].".utm = 2, ".$this->tables['textline_banners'].".direct_link, 
                                CONCAT( 
                                    ".$this->tables['textline_banners'].".direct_link,
                                    '?',
                                    CONCAT('utm_source=', ".$this->tables['textline_banners'].".utm_source), 
                                    CONCAT('&', 'utm_medium=', ".$this->tables['textline_banners'].".utm_medium),
                                    IF(".$this->tables['textline_banners'].".utm_campaign!='', CONCAT('&', 'utm_campaign=', ".$this->tables['textline_banners'].".utm_campaign), ''),
                                    IF(".$this->tables['textline_banners'].".utm_content!='', CONCAT('&', 'utm_content=', ".$this->tables['textline_banners'].".utm_content), '')
                                )
                            )
                        )
                        as `banner_direct_link` , 
                        LEFT(".$this->tables['agencies_photos'].".name,2) as agency_photo_folder,
                        ".$this->tables['agencies_photos'].".name as agency_photo,
                        ".$this->tables['agencies'].".title as agency_title
                FROM ".$this->tables['textline_campaigns']."
                LEFT JOIN ".$this->tables['textline_banners']." ON ".$this->tables['textline_banners'].".id_campaign = ".$this->tables['textline_campaigns'].".id
                LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->tables['textline_campaigns'].".id_user
                LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies'].".id_main_photo = ".$this->tables['agencies_photos'].".id
                WHERE   ".$this->tables['textline_campaigns'].".date_start <= CURDATE() AND 
                        ".$this->tables['textline_campaigns'].".date_end > CURDATE() AND 
                        ".$this->tables['textline_campaigns'].".enabled = 1 AND
                        ".$this->tables['textline_banners'].".enabled = 1 
                        " . $where . "
                GROUP BY ".$this->tables['textline_banners'].".id
                ORDER BY RAND()";
        $item = $db->fetch($sql); 
        return $item;
    }     
    /**
    * клик по объявлению
    */       
    function click($id, $ref)      {
        global $db;
        //1 клик в минуту
        $time = $db->fetch("SELECT TIMESTAMPDIFF(MINUTE, `datetime`, NOW()) as `time` FROM ".$this->tables['textline_stats_day_clicks']." WHERE id_parent = ? AND ip = ? ORDER BY id DESC",$id, Host::getUserIp());
        if(empty($ref)) $ref = '';
        if($id>0 && !Host::$is_bot && (empty($time) || $time['time']>=2)){
            $res=$db->querys("INSERT INTO ".$this->tables['textline_stats_day_clicks']." SET `id_parent`=?, ref=?, real_ref=?, ip=?", $id, $ref, Host::getRefererURL(), Host::getUserIp());
            return true;
        }
        
    }    
    /**
    * показ объявления
    */       
    function show($item, $ref_url = '')      {
        global $db;
        if(!Host::$is_bot) $db->querys("INSERT INTO ".$this->tables['textline_stats_day_shows']." 
                                            (id_parent, ip, remote_ip, user_ip, browser, ref, `data`, `ref_url`, `cookie`) 
                                       VALUES 
                                            (".$item['id'].", '".Host::getUserIp(true)."', '".Host::$remote_user_ip."', '".Host::getUserIp()."' ,'".$db->real_escape_string($_SERVER['HTTP_USER_AGENT'])."' ,'".Host::getRefererURL()."' ,'".print_r($_SERVER, true)."' ,'".$ref_url."' ,'".print_r($_COOKIE, true)."')"
        );
       
    }
}      

?>