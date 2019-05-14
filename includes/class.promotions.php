<?php
class Promotion {
    private $tables = [];
    public function __construct(){
        $this->tables = Config::$sys_tables;    
    }
    /**
    * получение списка объектов
    * @param integer $limit - начиная с этого элемента
    * @param string $order - набор полей сортировки, как для SQL (напр. "date_end DESC, title ASC")
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(date_end)=2012 AND MONTH(date_end)=3")
    * @return array of arrays
    */
    public function getList($limit, $order="", $where="", $estate_where = false, $with_cut_phone = false){
        global $db, $sys_tables;
        if(empty($list)) return [];
        return $list;
    }
    
    /**
    * получение объекта по его ID
    * @param string $this->tables['promotions'] - таблица, содержащая объекты
    * @param integer $chpu_title - ЧПУ объекта
    * @return array
    */
    public function getItem($where){
        global $db, $sys_tables;
        $sql = "SELECT ".$this->tables['promotions'].".*,
                        IF(YEAR(".$this->tables['promotions'].".`date_end`) != Year(CURDATE()),DATE_FORMAT(".$this->tables['promotions'].".`date_end`,'%e.%m.%Y'),DATE_FORMAT(".$this->tables['promotions'].".`date_end`,'%e %M')) as normal_date_end, 
                        IF(YEAR(".$this->tables['promotions'].".`date_start`) != Year(CURDATE()),DATE_FORMAT(".$this->tables['promotions'].".`date_start`,'%e.%m.%Y'),DATE_FORMAT(".$this->tables['promotions'].".`date_start`,'%e %M')) as normal_date_start, 
                        ".$this->tables['agencies'].".id as agency_id,
                        ".$this->tables['agencies'].".title as agency_title,
                        ".$this->tables['agencies'].".chpu_title as agency_chpu_title,
                        ".$this->tables['agencies'].".id_tarif ,
                        ".$this->tables['promotions_photos'].".`name` as `photo`, LEFT (".$this->tables['promotions_photos'].".`name`,2) as `subfolder`,
                        ".$this->tables['agencies_photos'].".`name` as `agency_photo`, LEFT (".$this->tables['agencies_photos'].".`name`,2) as `agency_subfolder`,
                        IF ( ".$this->tables['promotions'].".estate_complex_type = 1 , ".$this->tables['housing_estates'].".title,
                            IF ( ".$this->tables['promotions'].".estate_complex_type = 2 , ".$this->tables['cottages'].".title,
                                IF ( ".$this->tables['promotions'].".estate_complex_type = 3 , ".$this->tables['business_centers'].".title, '')    
                            )
                        ) as complex_title,
                        IF ( ".$this->tables['promotions'].".estate_complex_type = 1 , 'ЖК',
                            IF ( ".$this->tables['promotions'].".estate_complex_type = 2 , 'КП',
                                IF ( ".$this->tables['promotions'].".estate_complex_type = 3 , 'БЦ', '')    
                            )
                        ) as complex_type_title,
                        IF(LENGTH(".$this->tables['agencies'].".advert_phone) > 5, ".$this->tables['agencies'].".advert_phone,
                            IF(LENGTH(".$this->tables['agencies'].".phone_1) > 5, ".$this->tables['agencies'].".phone_1, 
                                IF(LENGTH(".$this->tables['agencies'].".phone_2) > 5, ".$this->tables['agencies'].".phone_2, 
                                    IF(LENGTH(".$this->tables['agencies'].".phone_3) > 5, ".$this->tables['agencies'].".phone_3, '') 
                                )
                            )
                        ) as agency_phone,
                        ".$this->tables['districts'].".title as district,
                        ".$this->tables['districts_areas'].".title as district_area,
                        ".$this->tables['subways'].".title as subway,
                        ".$this->tables['way_types'].".title as way_type,
                        ".$this->tables['estate_types'].".type as estate_type
                FROM ".$this->tables['promotions']."
                LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->tables['promotions'].".id_user
                LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies_photos'].".id = ".$this->tables['agencies'].".id_main_photo
                LEFT JOIN ".$this->tables['estate_types']." ON ".$this->tables['estate_types'].".id = ".$this->tables['promotions'].".id_estate_type
                LEFT JOIN ".$this->tables['promotions_photos']." ON ".$this->tables['promotions_photos'].".id = ".$this->tables['promotions'].".id_main_photo
                LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->tables['housing_estates'].".id = ".$this->tables['promotions'].".id_estate_complex AND ".$this->tables['promotions'].".estate_complex_type = 1
                LEFT JOIN ".$this->tables['cottages']." ON ".$this->tables['cottages'].".id = ".$this->tables['promotions'].".id_estate_complex AND ".$this->tables['promotions'].".estate_complex_type = 2
                LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = ".$this->tables['promotions'].".id_estate_complex AND ".$this->tables['promotions'].".estate_complex_type = 3
                LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->tables['promotions'].".id_district
                LEFT JOIN ".$this->tables['districts_areas']." ON ".$this->tables['districts_areas'].".id = ".$this->tables['promotions'].".id_district_area
                LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->tables['promotions'].".id_subway
                LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = ".$this->tables['promotions'].".id_way_type";
        $sql .= " WHERE ".$where;
        $row = $db->fetch($sql); 
        $objects = [];
        if(!empty($row)) $objects = $this->getObjectsInfo($row);
        if(!empty($objects)) $row = array_merge($row, $objects);
        return $row;
    }  
    /**
    * Формирование строки sort by - для сортировки
    * @param integer 
    * @return string
    */    
    public function makeSort($sortby){
            switch($sortby){
                // по району города по убыванию 
                case 7: 
                    return "district DESC, district_area DESC";
                // по району города по возрастанию 
                case 6: 
                    return "district ASC, district_area ASC";
                // по метро по убыванию 
                case 5: 
                    return "subway DESC";
                // по метро по возрастанию 
                case 4: 
                    return "subway ASC";
                // по району города по убыванию 
                case 3: 
                    return "date_end DESC";
                // по окончанию скидки - 
                case 2: 
                    return "date_end ASC";
                case 1:
                default:
                    return "RAND()";
            }    
    }    
    /**
    * получение объекта по его ID
    * @param string $this->tables['promotions'] - таблица, содержащая объекты
    * @param integer $chpu_title - ЧПУ объекта
    * @return array
    */
    public function getPromotionsIds($estate_type, $where, $id_estate_type, $only_promotions = false){
        global $db, $sys_tables;    
        if(empty($only_promotions)) {
            $list = $db->fetchall("SELECT ".$this->tables[$estate_type].".* 
                                   FROM ".$this->tables[$estate_type]." 
                                   RIGHT JOIN ".$this->tables['promotions']." ON ".$this->tables['promotions'].".id = ".$this->tables[$estate_type].".id_promotion  AND ".$this->tables['promotions'].".id_estate_type = ?
                                   WHERE ".$where." 
                                   GROUP BY id_promotion
            ", false, $id_estate_type );
        } else {
            $list = $db->fetchall("SELECT id as id_promotion
                                   FROM ".$this->tables['promotions']." 
                                   WHERE id_estate_type = ? ".(!empty($where) ? " AND ".$where : "")."
                                   GROUP BY id_promotion
            ", false, $id_estate_type );
        }
        if(!empty($list)) {
            $ids = [];
            foreach($list as $k=>$item) $ids[] = $item['id_promotion'];
            return $ids;
        }
        return false;
    }
    /**
    * получение информации об акционных объектах
    * @param array данные акции
    * @param string доп.условие
    * @return array
    */
    public function getObjectsInfo($item, $estate_where=false){
        global $db, $sys_tables;    
        if ( $item['estate_type'] == 'live' || $item['estate_type'] == 'build')
            $info = $db->fetch( "SELECT 
                                        COUNT(*) as objects_count,
                                        MIN(cost) as min_cost,
                                        GROUP_CONCAT(DISTINCT IF(rooms_sale=0,'студия',rooms_sale) ORDER BY rooms_sale ASC SEPARATOR ',') as object_types,
                                        GROUP_CONCAT(DISTINCT rooms_sale ORDER BY rooms_sale ASC SEPARATOR ',') as object_id_types
                                   FROM ".$this->tables[$item['estate_type']]." 
                                   WHERE 
                                        ".(!empty($estate_where) && $estate_where!=1 ? $estate_where : "published = 1 AND id_promotion = ".$item['id'])
            ); 
        else        
            $info = $db->fetch( "SELECT 
                                        COUNT(*) as objects_count,
                                        MIN(cost) as min_cost,
                                        GROUP_CONCAT(DISTINCT ".$this->tables['type_objects_'.$item['estate_type']].".title ORDER BY ".$this->tables['type_objects_'.$item['estate_type']].".title ASC SEPARATOR ',') as object_types,
                                        GROUP_CONCAT(DISTINCT ".$this->tables['type_objects_'.$item['estate_type']].".title_plural ORDER BY ".$this->tables['type_objects_'.$item['estate_type']].".title ASC SEPARATOR ',') as object_title_plurals,
                                        GROUP_CONCAT(DISTINCT ".$this->tables[$item['estate_type']].".id_type_object ORDER BY ".$this->tables['type_objects_'.$item['estate_type']].".title ASC SEPARATOR ',') as object_id_types
                                   FROM ".$this->tables[$item['estate_type']]." 
                                   LEFT JOIN ".$this->tables['type_objects_'.$item['estate_type']]." ON ".$this->tables['type_objects_'.$item['estate_type']].".id = ".$this->tables[$item['estate_type']].".id_type_object
                                   WHERE 
                                        ".(!empty($estate_where) && $estate_where!=1 ? $estate_where : "published = 1 AND id_promotion = ".$item['id'])
            ); 
        if(!empty($info)) {
            //определение скидочных цен
            if(!empty($info['min_cost'])){
                if(!empty($item['discount']) && !empty($item['discount_type'])){
                    $info['old_cost'] = $info['min_cost'];
                    $info['new_cost'] = $item['discount_type'] == 1 ? $info['min_cost'] - $item['discount'] : $info['min_cost'] * ( (100 - $item['discount']) / 100 );    
                } else $info['new_cost'] = $info['min_cost'];
            }     
        }
        return $info;
    }
}
?>