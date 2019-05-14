<?php
/**
* Мнения  / прогнозы
*/
class Opinions{
    
    
    public $content_type = 1; // тип контента
    /** Создание объекта
    * put your comment there...
    */
    public function __construct($type=''){
        $this->tables = Config::$sys_tables;
        switch(TRUE) {
            case $type == 'opinions':  //список для мнений
                $this->content_type=1;
                break;
            case $type == 'predictions':   //список для прогнозов
                $this->content_type=2;
                break;
            case $type == 'interview':   //список для интервью
                $this->content_type=3;
                break;
            default:  //общий список (блок)
                $this->content_type=4;
                break;
        }
    }
    
    /**
    * получение смешанного списка из мнений и прогнозов
    * @param integer $count - кол-во новостей
    * @param integer $from - с какой по счету новости
    * @param string $where - условие поиска (без 'WHERE')
    * @return array of arrays
    */
    public function getList($count=20, $from=0, $where=false, $order=false){
        global $db;
        if(empty($order)) $order = $this->tables['opinions_predictions'].".date DESC ";
        if (empty($where) && $this->content_type!=4) $where=" WHERE ".$this->tables['opinions_predictions'].".type=".$this->content_type." ";
        $sql = "SELECT ".$this->tables['opinions_predictions'].".*,
                       IF(YEAR(".$this->tables['opinions_predictions'].".`date`)<Year(CURDATE()),DATE_FORMAT(".$this->tables['opinions_predictions'].".`date`,'%e %M %Y'),DATE_FORMAT(".$this->tables['opinions_predictions'].".`date`,'%e %M')) as normal_date, 
                       LEFT(".$this->tables['opinions_predictions'].".text,270) as `content_short`,
                       ".$this->tables['opinions_expert_profiles'].".title as expert_title,
                       ".$this->tables['opinions_expert_agencies'].".title as agency_title,
                       ".$this->tables['opinions_expert_profiles'].".company as expert_company,
                       ".$this->tables['opinions_expert_estate_types'].".title as estate_title,
                       ".$this->tables['opinions_expert_estate_types'].".title_genitive as estate_title_genitive,
                       ".$this->tables['opinions_expert_estate_types'].".url as estate_url,
                       ".$this->tables['opinions_predictions'].".type,
                       ".$this->tables['opinions_expert_profiles_photos'].".`name` as `experts_photo`, 
                       'opinions' as type,
                       LEFT (".$this->tables['opinions_expert_profiles_photos'].".`name`,2) as `experts_subfolder`,
                       IF(".$this->tables['opinions_predictions'].".type=1,'Мнения экспертов',
                            IF(".$this->tables['opinions_predictions'].".type=2,'Прогнозы','Интервью')
                       ) AS type_title,
                       IF(".$this->tables['opinions_predictions'].".type=1, 'opinions',
                            IF(".$this->tables['opinions_predictions'].".type=2,'predictions','interview')
                       ) AS type_url,
                       (SELECT COUNT(*) FROM ".$this->tables['comments']." WHERE comments_active = 1 AND id_parent = ".$this->tables['opinions_predictions'].".id AND parent_type=4) as comments_count
        FROM ".$this->tables['opinions_predictions']."
        LEFT JOIN ".$this->tables['opinions_expert_profiles']." ON ".$this->tables['opinions_expert_profiles'].".id = ".$this->tables['opinions_predictions'].".id_expert 
        LEFT JOIN ".$this->tables['opinions_expert_estate_types']." ON ".$this->tables['opinions_expert_estate_types'].".id = ".$this->tables['opinions_predictions'].".id_estate_type 
        LEFT JOIN ".$this->tables['opinions_expert_agencies']." ON ".$this->tables['opinions_expert_agencies'].".id = ".$this->tables['opinions_expert_profiles'].".id_agency
        LEFT JOIN ".$this->tables['opinions_expert_profiles_photos']." ON ".$this->tables['opinions_expert_profiles_photos'].".id_parent = ".$this->tables['opinions_expert_profiles'].".id 
        LEFT JOIN ".$this->tables['opinions_expert_agencies_photos']." ON ".$this->tables['opinions_expert_agencies_photos'].".id_parent = ".$this->tables['opinions_expert_agencies'].".id"
        .(!empty($where)?" WHERE ".$where:" ").
        "
        GROUP BY ".$this->tables['opinions_predictions'].".id 
        ORDER BY ".$order;
        if(!empty($count)) $sql .= " LIMIT ".$from.",".$count;
        $rows = $db->fetchall($sql);
        if(empty($rows)) return array();
        return $rows;    
    }
    
    /**
    * получение мнения/прогноза по его ID
    * @param integer $id - ID мнения/прогноза
    * @return array
    */
     /**
    * получение мнения/прогноза по его ID
    * @param integer $id - ID мнения/прогноза
    * @return array
    */
    public function getItem($id){
        global $db;
        $res=$db->fetch("SELECT ".$this->tables['opinions_predictions'].".*,
                                ".$this->tables['opinions_predictions'].".annotation AS annotation, 
                                ".$this->tables['opinions_predictions'].".text AS text,
                                IF(YEAR(".$this->tables['opinions_predictions'].".`date`)<Year(CURDATE()),DATE_FORMAT(".$this->tables['opinions_predictions'].".`date`,'%e %M %Y'),DATE_FORMAT(".$this->tables['opinions_predictions'].".`date`,'%e %M')) as normal_date, 
                                ".$this->tables['opinions_expert_profiles'].".title AS expert_title,
                                ".$this->tables['opinions_expert_profiles'].".company AS expert_company,
                                ".$this->tables['opinions_expert_estate_types'].".url AS estate_url,
                                ".$this->tables['opinions_expert_estate_types'].".title AS estate_title,
                                ".$this->tables['opinions_expert_estate_types'].".title_genitive AS estate_title_genitive,
                                ".$this->tables['opinions_expert_agencies'].".title AS agency_title,
                                ".$this->tables['opinions_expert_profiles_photos'].".`name` as `experts_photo`, 
                                LEFT (".$this->tables['opinions_expert_profiles_photos'].".`name`,2) as `experts_subfolder`,
                                IF(".$this->tables['opinions_predictions'].".type=1,'Мнения экспертов',
                                    IF(".$this->tables['opinions_predictions'].".type=2,'Прогнозы','Интервью')
                                ) AS type_title,
                                IF(".$this->tables['opinions_predictions'].".type=1, 'opinions',
                                    IF(".$this->tables['opinions_predictions'].".type=2,'predictions','interview')
                                ) AS type_url,
                                (SELECT COUNT(*) FROM ".$this->tables['comments']." WHERE comments_active = 1 AND id_parent = ".$this->tables['opinions_predictions'].".id AND parent_type=4) as comments_count
                         FROM ".$this->tables['opinions_predictions']." 
                         LEFT JOIN ".$this->tables['opinions_expert_profiles']." ON ".$this->tables['opinions_predictions'].".id_expert=".$this->tables['opinions_expert_profiles'].".id 
                         LEFT JOIN ".$this->tables['opinions_expert_estate_types']." ON ".$this->tables['opinions_predictions'].".id_estate_type=".$this->tables['opinions_expert_estate_types'].".id 
                         LEFT JOIN ".$this->tables['opinions_expert_agencies']." ON ".$this->tables['opinions_expert_profiles'].".id_agency=".$this->tables['opinions_expert_agencies'].".id
                         LEFT JOIN ".$this->tables['opinions_expert_profiles_photos']." ON ".$this->tables['opinions_expert_profiles_photos'].".id_parent = ".$this->tables['opinions_expert_profiles'].".id 
                         WHERE ".$this->tables['opinions_predictions'].".id=? AND ".$this->tables['opinions_predictions'].".type = ?
                         GROUP BY ".$this->tables['opinions_predictions'].".id
                         ",$id,$this->content_type);  
        return $res;
    }

    /**
    * получение 3 пред и 3 след статьи
    * @param mixed $category - код/id категории
    * @param mixed $region - код/id региона
    * @param string $id - id новости
    * @return array of arrays
    */
    public function getPrevNext($type, $estate_type, $id, $excluded_ids = false){
        global $db;
        $where = $this->tables['opinions_predictions'].".type = ".$type." AND ".$this->tables['opinions_predictions'].".id_estate_type = ".$estate_type;
        if(!empty($excluded_ids)) $where .= " AND " . $this->tables['opinions_predictions'] . ".`id` NOT IN (" . implode(",", $excluded_ids) . ")";
        $ids = [];
        $list = $db->fetchall("
                    SELECT * FROM (
                        (SELECT id FROM ".$this->tables['opinions_predictions']." WHERE id < ".$id." AND ".$where." ORDER BY id DESC LIMIT 3)
                        UNION
                        (SELECT id FROM ".$this->tables['opinions_predictions']." WHERE ".$where." ORDER BY id DESC LIMIT 3)
                        ORDER BY id LIMIT 3
                    ) a
                    UNION
                    SELECT * FROM (
                        (SELECT id FROM ".$this->tables['opinions_predictions']." WHERE id > ".$id." AND ".$where." ORDER BY id ASC LIMIT 3)
                        UNION
                        (SELECT id FROM ".$this->tables['opinions_predictions']." WHERE ".$where." ORDER BY id ASC LIMIT 3)
                        LIMIT 3
                    ) b "
        );     
        foreach($list as $k=>$item) $ids[] = $item['id'];
        return $this->getList(6, 0, $this->tables['opinions_predictions'].".id IN (".implode(",", $ids).")", $this->tables['opinions_predictions'].".id DESC");
    }
    /**
    * получение списка месяцев
    * @return array of array
    */
    public function getMonthsList( $category = null ){
        global $db;
        $where = [ $this->tables['opinions_predictions'] .".`date` <= NOW() " ];
        if( !empty( $category ) ) $where[] = $this->tables['opinions_expert_estate_types'] . ".url = '" . $category . "'";
        $list = $db->fetchall("
            SELECT 
                DATE_FORMAT(". $this->tables['opinions_predictions'] .".`date`, '%Y') as `year`,
                DATE_FORMAT(". $this->tables['opinions_predictions'] .".`date`, '%c') as `month`,
                DATE_FORMAT(". $this->tables['opinions_predictions'] .".`date`, '%m') as `month_number`
            FROM ". $this->tables['opinions_predictions'] ."
            LEFT JOIN " . $this->tables['opinions_expert_estate_types']." ON ".$this->tables['opinions_predictions'].".id_estate_type=".$this->tables['opinions_expert_estate_types'].".id 
            WHERE " . implode( " AND ", $where ) . "
            GROUP BY `year`, `month`
            ORDER BY `year` DESC, month_number
        ");
        $year = 0;
        $array = [];
        foreach($list as $k=>$item) {
            if($year == 0) $year = $item['year'];
            $array[$item['year']][] = array('month'=> Config::Get('months')[$item['month']], 'month_number'=>$item['month_number'], 'active'=>$year == $item['year'] ? true : false ) ;
        }
        return $array;
    }
    
}
?>