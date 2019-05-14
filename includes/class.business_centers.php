<?php
class BusinessCenters {
    private $tables = [];
    public function __construct(){
        $this->tables = Config::$sys_tables;    
    }
    /**
    * получение списка объектов
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param integer $from - начиная с этого элемента
    * @param string $order - набор полей сортировки, как для SQL (напр. "datetime DESC, title ASC")
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @return array of arrays
    */
    public function getList($count=0, $from=0, $where="", $order=false){
        global $db;
        if(empty($order)) $order = $this->tables['business_centers'].".advanced = 1 DESC, ".$this->tables['business_centers'].".id_main_photo > 0 DESC, ".$this->tables['business_centers'].".title";
        $sql = "SELECT  ".$this->tables['business_centers'].".*
                         , ".$this->tables['business_centers_photos'].".`name` as `photo`, LEFT (".$this->tables['business_centers_photos'].".`name`,2) as `subfolder`
                         , ".$this->tables['subways'].".title as `subway`
                         , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                         , ".$this->tables['subway_lines'].".color as `subway_color`                         
                         , ".$this->tables['way_types'].".title as way_type
                         , ".$this->tables['districts'].".title as `district`
                         , ".$this->tables['geodata'].".offname as `district_area` 
                         , ".$this->tables['agencies'].".title as agency_title
                         , CONCAT(
                                'Бизнес-центр ',
                                ".$this->tables['business_centers'].".title
                           ) as `header`
                         , ".$this->tables['way_types'].".title as `way_type_title`
                         , CONCAT(SUBSTRING(".$this->tables['business_centers'].".date_in FROM 9 FOR 2),'.',
                               SUBSTRING(".$this->tables['business_centers'].".date_in FROM 6 FOR 2),'.',
                               SUBSTRING(".$this->tables['business_centers'].".date_in FROM 1 FOR 4)) AS `formatted_date_in`
                         , DATE_FORMAT(".$this->tables['business_centers'].".date_change,'%d.%m.%y') as `formatted_date_end`
                         , (SELECT COUNT(*) FROM ".$this->tables['business_centers_photos']." WHERE id_parent=".$this->tables['business_centers'].".id) AS photos_count
                         , (SELECT COUNT(*) FROM ".$this->tables['commercial']." WHERE published = 1 AND id_business_center=".$this->tables['business_centers'].".id) AS total_objects
                  FROM ".$this->tables['business_centers']."
                  LEFT JOIN ".$this->tables['business_centers_photos']." ON ".$this->tables['business_centers_photos'].".id = ".$this->tables['business_centers'].".id_main_photo
                  LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->tables['business_centers'].".id_subway
                  LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
                  LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->tables['business_centers'].".id_way_type
                  LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->tables['business_centers'].".id_user
                  LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                  LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->tables['business_centers'].".id_district
                  LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->tables['business_centers'].".id_region AND ".$this->tables['geodata'].".id_area = ".$this->tables['business_centers'].".id_area
                  ".(empty($where)?"":"WHERE ".$where)."
                  ORDER BY ".$order;
        if(!empty($count)) $sql .= " LIMIT ".$from.",".$count;
        
        
        $rows = $db->fetchall($sql);
        if(empty($rows)) return [];
        foreach($rows as $k=>$item){
            $rows[$k]['photos'] = Photos::getList( 'business_centers', $item['id'], false, false, 5 );
            $rows[$k]['address'] = $this->getAddress($item);
            if( !empty($item['total_objects']) ){
                //поиск объектов                
                $objects = $this->getObjectsParams($item['id']);
                if(!empty($objects)) $rows[$k] = array_merge($rows[$k], $objects);
            } 
        } 
        return $rows;
    }
    
    /**
    * Формирование строки WHERE для sql запроса по массиву параметров
    * @param array массив условий (array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val|'like'=>'LIKE %val%'))
    * @param boolean из новых (или из опубликованных)
    * @return string
    */
    public function makeWhereClause($clauses){
        global $db;
        $result = [];
        if(!is_array($clauses)) return '';
        foreach($clauses as $field=>$values){
            if(isset($values['value'])) $result[] = $this->tables['business_centers'].".`".$field."` = ".$db->quoted($values['value']);
            elseif(isset($values['set'])) {
                $arr = [];
                foreach($values['set'] as $item) $arr[] = $db->quoted($item);
                $result[] = $this->tables['business_centers'].".`".$field."` IN (" . implode(',',$arr) . ')';
            } elseif(isset($values['concate'])) {
                $set_arr = [];
                foreach($values['concate'] as $set_field => $set_values){
                    if(empty($set_values)) continue;
                    if(!is_array($set_values)) $set_arr[] = $this->tables['business_centers'].".`".$set_field."` = ".$db->quoted($set_values);
                    else {
                        $arr = [];
                        foreach($set_values as $item) $arr[] = $db->quoted($item);
                        if(!empty($arr)) $set_arr[] = $this->tables['business_centers'].".`".$set_field."` IN (" . implode(',',$arr) . ')';
                    }
                }
                if(!empty($set_arr)) $result[] = "(".implode(" OR ", $set_arr).")";
            } elseif(isset($values['like'])) $result[] = $this->tables['business_centers'].".`".$field."` LIKE '%".$db->real_escape_string($values['like'])."%'";
            else {
                if(isset($values['from'])) $result[] = $this->tables['business_centers'].".`".$field."` >= ".$db->quoted($values['from']);
                if(isset($values['to'])) $result[] = $this->tables['business_centers'].".`".$field."` <= ".$db->quoted($values['to']);
            }
        }
        $result[] = $this->tables['business_centers'].".published = 1";
        return implode(' AND ', $result);
    }
    /**
    * получение данных картоки
    * @param integer $id - id объекта
    * @return array 
    */
    public function getItem($title = false, $id = false, $for_item = false){
        global $db;
        if(!empty($id)) $where = " maintable.id =  '".$id."'";
        elseif(!empty($title)) $where = " maintable.chpu_title =  '".$title."'";
        else return false;
        $row = $db->fetch("
            SELECT 
                maintable.*
                , ".$this->tables['districts'].".title as district
                , ".$this->tables['agencies'].".id_tarif as agency_tarif
                , ".$this->tables['agencies'].".title as agency_title
                , ".$this->tables['agencies'].".chpu_title as agency_chpu_title
                ".(!empty($for_item)?
                ", IF(".$this->tables['agencies'].".phone_1!='' AND (".$this->tables['agencies'].".payed_page = 1 OR maintable.advanced = 1), ".$this->tables['agencies'].".phone_1,'') as agency_phone_1
                 , IF(".$this->tables['agencies'].".phone_2!='' AND (".$this->tables['agencies'].".payed_page = 1 OR maintable.advanced = 1), ".$this->tables['agencies'].".phone_2,'') as agency_phone_2
                 , IF(".$this->tables['agencies'].".phone_3!='' AND (".$this->tables['agencies'].".payed_page = 1 OR maintable.advanced = 1), ".$this->tables['agencies'].".phone_3,'') as agency_phone_3
                   ":
                ", ".$this->tables['agencies'].".phone_1 AS agency_phone_1
                 , ".$this->tables['agencies'].".phone_2 AS agency_phone_2
                 , ".$this->tables['agencies'].".phone_3 AS agency_phone_3
                 ")."
                , ".$this->tables['agencies'].".advert_phone as agency_advert_phone
                , ".$this->tables['agencies'].".doverie_years
                , IF(".$this->tables['agencies'].".email_service!='', ".$this->tables['agencies'].".email_service,
                   IF(".$this->tables['agencies'].".email!='', ".$this->tables['agencies'].".email, ".$this->tables['users'].".email)
                ) as email
                , ".$this->tables['geodata'].".offname as `district_area`
                , ".$this->tables['subways'].".title as subway
                , ".$this->tables['subways'].".id_subway_line as subway_line
                , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                , ".$this->tables['subway_lines'].".color as `subway_color`
                , ".$this->tables['way_types'].".title as way_type
                , ".$this->tables['agencies_photos'].".name as agency_photo_name
                , LEFT(".$this->tables['agencies_photos'].".name, 2) as agency_subfolder
                , (SELECT COUNT(*) FROM ".$this->tables['business_centers_offices']." WHERE id_parent=".$this->tables['business_centers_levels'].".id) AS offices_count
            FROM ".$this->tables['business_centers']."  maintable
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies_photos'].".id = ".$this->tables['agencies'].".id_main_photo
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['business_centers_levels']." ON maintable.id = ".$this->tables['business_centers_levels'].".id_parent
            WHERE ".$where);    
        
        if(!empty($row)) {
            unset($row['seller_phone']);
            $row['address'] = $this->getAddress($row);
            //информация по офисам
            $offices_info = $this->getOfficesParams($row['id']);
            if(!empty($offices_info)) $row = array_merge($row, $offices_info);
            return $row;
        }
        return false;
    }  
    
    public function getTitles($id){
        global $db;
        $row = $db->fetch("
            SELECT 
                   maintable.id_user,
                   maintable.class,
                   CONCAT(
                        'Бизнес-центр ',
                        '«', UPPER(LEFT(maintable.title,1)), (RIGHT(maintable.title,CHAR_LENGTH(maintable.title)-1)), '»'
                   ) as `header`
                 , CONCAT(
                        'Бизнес-центр ',
                        '«', UPPER(LEFT(maintable.title,1)), (RIGHT(maintable.title,CHAR_LENGTH(maintable.title)-1)), '»',
                        IF(".$this->tables['subways'].".title<>'', CONCAT(' метро ', ".$this->tables['subways'].".title), ''),
                        '.'
                   ) as `title`
                 , CONCAT(
                         'Бизнес-центр ',
                         '«', UPPER(LEFT(maintable.title,1)), (RIGHT(maintable.title,CHAR_LENGTH(maintable.title)-1)), '»',
                         IF(".$this->tables['subways'].".title<>'', CONCAT(' метро ', ".$this->tables['subways'].".title), ''),
                         '. Информация об объекте:',

                         IF(maintable.class<>'no', CONCAT(' класс ', maintable.class),''),
                         IF(".$this->tables['geodata'].".offname<>'', CONCAT(', ', ".$this->tables['geodata'].".offname, ' район ЛО'), ''),
                         IF(".$this->tables['districts'].".title<>'', CONCAT(', ',".$this->tables['districts'].".title, ' район'), ''),
                         IF(maintable.m2monthcostmin<>0 OR maintable.m2monthcostmax<>0,
                            CONCAT(', ', IF(maintable.m2monthcostmin<>0,maintable.m2monthcostmin,maintable.m2monthcostmax),' руб / м2 в месяц' ),
                            ''),
                         '. Полные характеристики, фотогалерея и описание инфраструктуры есть на сайте.'
                   ) as `description`
            FROM ".$this->tables['business_centers']."  maintable
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            
            WHERE maintable.id = ?
        ", $id);                  
        if(empty($row)) return false;
        return array('title'=>$row['title'], 'description'=>$row['description'], 'header'=>$row['header']);
    }

    public function getAddress($row){
        global $db;
            if(!empty($row['id_city'])){   
                $city = $db->fetch("SELECT CONCAT(shortname, '. ', offname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? ",
                                    3,
                                    $row['id_region'],
                                    $row['id_area'],
                                    $row['id_city']
                );
            }
            if(!empty($row['id_place'])){   
                $place = $db->fetch("SELECT CONCAT(shortname, '. ',offname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                    4,
                                    $row['id_region'],
                                    $row['id_area'],
                                    $row['id_city'],
                                    $row['id_place']
                                    
                );
            }
            $addr = !empty($city) ? $city['title'].', ' : '';
            $addr .= !empty($place) ? $place['title'].', ' : '';

            if(!empty($row['id_street'])){
                $street = $db->fetch("SELECT CONCAT(offname, ' ',shortname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                    5,
                                    $row['id_region'],
                                    $row['id_area'],
                                    $row['id_city'],
                                    $row['id_place'],
                                    $row['id_street']
                                    
                );
                $addr .= !empty($street) ? $street['title'] : '';
                $addr .= !empty($row['house']) ? ' , д.'.$row['house']: ''; 
                $addr .= !empty($row['corp']) ? ' , к.'.$row['corp']: '';
                return $addr; 
            }
            return $addr.$row['txt_addr'];            
    }    
    /**
    * получение списка корпусов объектов
    * @param integer $limit - кол-во элементов (если 0 - то без ограничения) ,начиная с этого элемента
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @param string $order - набор полей сортировки, как для SQL (напр. "datetime DESC, title ASC")
    * @param string $group - группировка
    * @return array of arrays
    */
    public function getCorpusesList($limit, $where = "", $order = false){    
        global $db;
        $sql = "SELECT  ".$this->tables['business_centers_levels'].".*
                         , ".$this->tables['business_centers_corps'].".`title` as corp_title
                         , ".$this->tables['business_centers'].".`title` as business_center_title
                         , GROUP_CONCAT(".$this->tables['business_centers_levels'].".id) as ids
                  FROM ".$this->tables['business_centers_corps']."
                  LEFT JOIN ".$this->tables['business_centers_levels']." ON ".$this->tables['business_centers_levels'].".id_corp = ".$this->tables['business_centers_corps'].".id
                  LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = ".$this->tables['business_centers_levels'].".id_parent
                  ".(empty($where)?"":"WHERE ".$where)." 
                  GROUP BY ".$this->tables['business_centers_levels'].".id_corp";
        if(!empty($order)) $sql .= " ORDER BY ".$order;
        if(!empty($limit)) $sql .= " LIMIT ".$limit;
        $rows = $db->fetchall($sql);
        if(empty($rows)) return $rows;
        foreach($rows as $k=>$row){
            $rows[$k]['offices_count'] = $db->fetch("SELECT COUNT(*) as offices_count FROM ".$this->tables['business_centers_offices']." WHERE id_parent IN (".$row['ids'].") AND status = 2 AND object_type = 1")['offices_count'];
        }
        return $rows;
        
    }
    /**
    * получение списка этажей объектов
    * @param integer $limit - кол-во элементов (если 0 - то без ограничения) ,начиная с этого элемента
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @param string $order - набор полей сортировки, как для SQL (напр. "datetime DESC, title ASC")
    * @param string $group - группировка
    * @return array of arrays
    */
    public function getLevelsList($limit, $where = "", $order = false, $group = false, $db = false){    
        if(empty($db)){
            global $db;
            if(empty($db)) return false;
        }
        $sql = "SELECT  ".$this->tables['business_centers_levels'].".*
                         , ".$this->tables['business_centers'].".`title` as business_center_title
                         , ".$this->tables['business_centers_corps'].".`title` as corp_title
                         , (SELECT COUNT(*) FROM ".$this->tables['business_centers_offices']." WHERE id_parent=".$this->tables['business_centers_levels'].".id AND status = 2 AND object_type = 1) AS offices_count
                  FROM ".$this->tables['business_centers_levels']."
                  LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = ".$this->tables['business_centers_levels'].".id_parent
                  LEFT JOIN ".$this->tables['business_centers_corps']." ON ".$this->tables['business_centers_levels'].".id_corp = ".$this->tables['business_centers_corps'].".id
                  ".(empty($where)?"":"WHERE ".$where);
        if(!empty($group)) $sql .= " GROUP BY ".$group;
        else $sql .= " GROUP BY ".$this->tables['business_centers_levels'].".id";
        if(!empty($order)) $sql .= " ORDER BY ".$order;
        if(!empty($limit)) $sql .= " LIMIT ".$limit;
        $rows = $db->fetchall($sql);
        return $rows;
        
    }
    /**
    * получение списка офисов
    * @param integer $limit - кол-во элементов (если 0 - то без ограничения) ,начиная с этого элемента
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @param string $order - набор полей сортировки, как для SQL (напр. "datetime DESC, title ASC")
    * @return array of arrays
    */
    public function getOfficesList($limit, $where="", $order=false){    
        global $db;
        $sql = "SELECT   ".$this->tables['business_centers'].".*
                         , ".$this->tables['business_centers_offices'].".*
                         , ".$this->tables['business_centers_levels'].".level
                         , ".$this->tables['business_centers_levels'].".img_link
                         , ".$this->tables['business_centers_levels'].".show_img
                         , ".$this->tables['business_centers_levels'].".id_parent as id_business_center
                         , ".$this->tables['business_centers'].".`title` as business_center_title
                         , ".$this->tables['facings'].".`title` as facing_title
                         , DATEDIFF(date_rent_end, CURDATE()) as datediff
                         , DATE_FORMAT(".$this->tables['business_centers_offices'].".`date_rent_end` ,'%e.%m.%Y') as date_rent_end_normal
                         , DATE_FORMAT(".$this->tables['business_centers_offices'].".`date_rent_start` ,'%e.%m.%Y') as date_rent_start_normal
                         , ".$this->tables['business_centers_offices_renters'].".`title` as renter_title
                  FROM ".$this->tables['business_centers_offices']."
                  LEFT JOIN ".$this->tables['business_centers_levels']." ON ".$this->tables['business_centers_levels'].".id = ".$this->tables['business_centers_offices'].".id_parent
                  LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = ".$this->tables['business_centers_levels'].".id_parent
                  LEFT JOIN ".$this->tables['business_centers_offices_renters']." ON ".$this->tables['business_centers_offices'].".id_renter = ".$this->tables['business_centers_offices_renters'].".id
                  LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->tables['business_centers_offices'].".id_facing
                  ".(empty($where)?"":"WHERE ".$where)."
                  GROUP BY ".$this->tables['business_centers_offices'].".id";
        if(!empty($order)) $sql .= " ORDER BY ".$order;
        if(!empty($limit)) $sql .= " LIMIT ".$limit;
        $rows = $db->fetchall($sql);
        return $rows;
        
    }
    /**
    * получение информации об офисе
    * @param integer $id - ID офиса
    * @return array
    */
    public function getOfficesItem($id){    
        global $db;
        $sql = "SELECT  ".$this->tables['business_centers_offices'].".*
                         , ".$this->tables['facings'].".`title` as facing_title
                         , ".$this->tables['business_centers_levels'].".`level` 
                         , ".$this->tables['business_centers'].".`title` as business_center_title
                         , DATEDIFF(date_rent_end, CURDATE()) as datediff
                         , DATE_FORMAT(".$this->tables['business_centers_offices'].".`date_rent_end` ,'%e.%m.%Y') as date_rent_end_normal
                         , ".$this->tables['business_centers_photos'].".`name` as `photo`, LEFT (".$this->tables['business_centers_photos'].".`name`,2) as `subfolder`
                         , (SELECT COUNT(*) FROM ".$this->tables['business_centers_offices']." WHERE id_parent=".$this->tables['business_centers_levels'].".id) AS offices_count
                         , ".$this->tables['business_centers_offices_renters'].".`title` as renter_title
                  FROM ".$this->tables['business_centers_offices']."
                  LEFT JOIN ".$this->tables['business_centers_levels']." ON ".$this->tables['business_centers_levels'].".id = ".$this->tables['business_centers_offices'].".id_parent
                  LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = ".$this->tables['business_centers_levels'].".id_parent
                  LEFT JOIN ".$this->tables['commercial']." ON ".$this->tables['commercial'].".id = ".$this->tables['business_centers_offices'].".id_object
                  LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->tables['business_centers_offices'].".id_facing
                  LEFT JOIN ".$this->tables['business_centers_photos']." ON ".$this->tables['business_centers_photos'].".id = ".$this->tables['business_centers'].".id_main_photo
                  LEFT JOIN ".$this->tables['business_centers_offices_renters']." ON ".$this->tables['business_centers_offices'].".id_renter = ".$this->tables['business_centers_offices_renters'].".id
                  WHERE ".$this->tables['business_centers_offices'].".id = ?
                  GROUP BY ".$this->tables['business_centers_offices'].".id";
        $rows = $db->fetch($sql, $id);
        return $rows;
        
    }  
    /**
    * получение основных параметров офисов для БЦ
    * @param integer $id - ID БЦ
    * @return array
    */
    public function getOfficesParams($id){    
        global $db;
        $sql = "SELECT  
                        COUNT(".$this->tables['business_centers_offices'].".id) as `count`
                        , MIN(CASE ".$this->tables['business_centers_offices'].".cost WHEN '' THEN 'DID NOT PARTICIPATE' ELSE ".$this->tables['business_centers_offices'].".cost END)  as min_cost
                        , MAX(".$this->tables['business_centers_offices'].".cost) as max_cost
                        , MIN(".$this->tables['business_centers_offices'].".cost_meter) as min_cost_meter
                        , MAX(".$this->tables['business_centers_offices'].".cost_meter) as max_cost_meter
                        , MIN(".$this->tables['business_centers_offices'].".square) as min_square
                        , MAX(".$this->tables['business_centers_offices'].".square) as max_square
                  FROM ".$this->tables['business_centers_offices']."
                  LEFT JOIN ".$this->tables['business_centers_levels']." ON ".$this->tables['business_centers_levels'].".id = ".$this->tables['business_centers_offices'].".id_parent
                  LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = ".$this->tables['business_centers_levels'].".id_parent
                  WHERE ".$this->tables['business_centers'].".id = ? AND ".$this->tables['business_centers_offices'].".status = 2 AND ".$this->tables['business_centers_offices'].".object_type = 1
                  GROUP BY ".$this->tables['business_centers'].".id";
        $rows = $db->fetch($sql, $id);
        return $rows;
        
    }    
    public function getObjectsParams($id){
        global $db;
        return $db->fetch("
                           SELECT 
                                MIN(cost) as min_cost_objects, 
                                MAX(cost) as max_cost_objects,
                                GROUP_CONCAT(DISTINCT(rent)) as rent_types
                           FROM ".$this->tables['commercial']." 
                           WHERE ".$this->tables['commercial'].".published = 1 AND ".$this->tables['commercial'].".id_business_center = ?
                           ORDER BY rent 
                       ", $id
        );        
    }    
}      

?>