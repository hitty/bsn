<?php
class HousingEstates {
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
    public function Search($where="", $count=0, $from=0, $order=false, $id_expert = false){
        global $db;
        if(empty($order)) $order = $this->tables['housing_estates'].".advanced = 1 DESC, ".$this->tables['housing_estates'].".id_main_photo > 0 DESC, ".$this->tables['housing_estates'].".title";
        $sql = "SELECT  ".$this->tables['housing_estates'].".*
                         , ".$this->tables['housing_estates_photos'].".`name` as `photo`, LEFT (".$this->tables['housing_estates_photos'].".`name`,2) as `subfolder`
                         , ".$this->tables['subways'].".title as `subway`
                         , ".$this->tables['districts'].".title as `district`
                         , ".$this->tables['geodata'].".offname as `district_area` 
                         , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                         , ".$this->tables['subway_lines'].".color as `subway_color`
                         , ".$this->tables['agencies'].".title as developer_title
                         , ".$this->tables['agencies'].".chpu_title as developer_chpu_title
                         , ".$this->tables['agencies'].".phone_1 as 'developer_phone_1' 
                         , ".$this->tables['agencies'].".phone_2 as 'developer_phone_2' 
                         , ".$this->tables['agencies'].".phone_3 as 'developer_phone_3'
                         , ".$this->tables['agencies'].".email as 'developer_email'
                         , ".$this->tables['agencies'].".url as 'developer_url'
                         , agency_seller.title as `seller_title`
                         , agency_seller.chpu_title as `seller_chpu_title`
                         , ".$this->tables['way_types'].".title as `way_type_title`
                         , IF(".$this->tables['housing_estates'].".class = 3, 'Премиум',
                            IF(".$this->tables['housing_estates'].".class = 2, 'Бизнес',
                            IF(".$this->tables['housing_estates'].".class = 4, 'Комфорт','Эконом'))
                         ) as class_title
                         , CONCAT(
                                'Жилой комплекс ',
                                ".$this->tables['housing_estates'].".title
                           ) as `header`
                         , CONCAT(SUBSTRING(".$this->tables['housing_estates'].".date_in FROM 9 FOR 2),'.',SUBSTRING(".$this->tables['housing_estates'].".date_in FROM 6 FOR 2),'.',SUBSTRING(".$this->tables['housing_estates'].".date_in FROM 1 FOR 4)) AS `formatted_date_in`
                         , DATE_FORMAT(".$this->tables['housing_estates'].".date_change + INTERVAL 30 day,'%d %M') as `date_end`
                         , DATE_FORMAT(".$this->tables['housing_estates'].".date_change + INTERVAL 30 day,'%d.%m.%y') as `formatted_date_end`
                         , r.expert_rating
                         , r.votes_total
                         , (SELECT COUNT(*) FROM ".$this->tables['housing_estates_photos']." WHERE id_parent=".$this->tables['housing_estates'].".id) AS photos_count
                         , (SELECT COUNT(*) FROM ".$this->tables['video_konkurs']." WHERE id_estate_complex=".$this->tables['housing_estates'].".id AND ( external_link!='' OR youtube_link!='' ) AND status = 1 AND complex_type = 1) AS videos_count
                         , (SELECT COUNT(*) FROM ".$this->tables['build']." WHERE published = 1 AND id_housing_estate=".$this->tables['housing_estates'].".id and rent = 2) AS build_total_objects
                         , (SELECT COUNT(*) FROM ".$this->tables['comments']." WHERE comments_active = 1 AND parent_type = 8 AND id_parent=".$this->tables['housing_estates'].".id) AS comments_objects
                  FROM ".$this->tables['housing_estates']."
                  LEFT JOIN ".$this->tables['housing_estates_photos']." ON ".$this->tables['housing_estates_photos'].".id = ".$this->tables['housing_estates'].".id_main_photo
                  LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->tables['housing_estates'].".id_subway
                  LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
                  LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->tables['housing_estates'].".id_user
                  LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                  LEFT JOIN ".$this->tables['users']." user_seller ON user_seller.id = ".$this->tables['housing_estates'].".id_seller
                  LEFT JOIN ".$this->tables['agencies']." agency_seller ON agency_seller.id = user_seller.id_agency
                  LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->tables['housing_estates'].".id_district
                  LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = ".$this->tables['housing_estates'].".id_way_type
                  LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->tables['housing_estates'].".id_region AND ".$this->tables['geodata'].".id_area = ".$this->tables['housing_estates'].".id_area
                  LEFT JOIN ( SELECT ROUND( AVG(rating), 2 ) as expert_rating, id_parent, COUNT(*) as votes_total FROM ".$this->tables['housing_estates_voting']." WHERE is_expert = 1 " . ( !empty( $id_expert ) ? " AND id_user = " . $id_expert : "" ) . " GROUP BY ".$this->tables['housing_estates_voting'].".id_parent) r ON r.id_parent = ".$this->tables['housing_estates'].".id
                  ".(empty($where)?"":"WHERE ".$where)."
                  GROUP BY ".$this->tables['housing_estates'].".id 
                  ORDER BY ".$order;
        if(!empty($count)) $sql .= " LIMIT ".$from.",".$count;
        
        $rows = $db->fetchall($sql);

        if(empty($rows)) return [];
        foreach($rows as $k=>$item){
            $rows[$k]['photos'] = Photos::getList( 'housing_estates', $item['id'], false, false, 5 );
            //определение адреса
            $rows[$k]['address'] = $addr = $this->getAddress($item);
            $rows[$k]['total_objects'] = $item['build_total_objects'];
            $rows[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                           (!empty($item['district'])? $item['district'].' р-н ':'').
                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$rows[$k]['txt_addr'];
            if(($item['build_total_objects']) > 0){
                //поиск объектов                
                $objects = $this->getObjectsParams($item['id']);
                if(!empty($objects)) {
                    $rows[$k] = array_merge($rows[$k], $objects);
                }
            }
            $rows[$k]['rating'] = ( !empty($rows[$k]['rating'] ) ? number_format( (float)$rows[$k]['rating'], 2, '.', '') : 0 );
        }
        return $rows;
    }
    
    /**
    * Формирование строки WHERE для sql запроса по массиву параметров
    * @param array массив условий (array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val|'like'=>'LIKE %val%'))
    * @param boolean из новых (или из опубликованных)
    * @return string
    */
    public function makeWhereClause($clauses, $is_apartments = false){
        global $db;
        $result = [];
        //апартаменты / ЖК
        if( empty( $is_apartments ) || $is_apartments === true ) $result[] = $this->tables['housing_estates'].".`apartments` = " . ( empty($is_apartments) ? 2 : 1);
        if(!is_array($clauses)) return '';
        foreach($clauses as $field=>$values){
            if(isset($values['value'])) $result[] = $this->tables['housing_estates'].".`".$field."` = ".$db->quoted($values['value']);
            elseif(isset($values['set'])) {
                $arr = [];
                foreach($values['set'] as $item) $arr[] = $db->quoted($item);
                if(!empty($arr)) $result[] = $this->tables['housing_estates'].".`".$field."` IN (" . implode(',',$arr) . ')';
            } elseif(isset($values['concate'])) {
                $set_arr = [];
                foreach($values['concate'] as $set_field => $set_values){
                    if(empty($set_values)) continue;
                    if(!is_array($set_values)) $set_arr[] = $this->tables['housing_estates'].".`".$set_field."` = ".$db->quoted($set_values);
                    else {
                        $arr = [];
                        foreach($set_values as $item) $arr[] = $db->quoted($item);
                        if(!empty($arr)) $set_arr[] = $this->tables['housing_estates'].".`".$set_field."` IN (" . implode(',',$arr) . ')';
                    }
                }
                if(!empty($set_arr)) $result[] = "(".implode(" OR ", $set_arr).")";
            } elseif(isset($values['like'])) $result[] = $this->tables['housing_estates'].".`".$field."` LIKE '%".$db->real_escape_string($values['like'])."%'";
            else {
                if(isset($values['from'])) $result[] = $this->tables['housing_estates'].".`".$field."` >= ".$db->quoted($values['from']);
                if(isset($values['to'])) $result[] = $this->tables['housing_estates'].".`".$field."` <= ".$db->quoted($values['to']);
            }
        }
        $result[] = $this->tables['housing_estates'].".published = 1";
        return implode(' AND ', $result);
    }
    
    /**
    * получение данных картоки
    * @param integer $id - id объекта
    * @param string $title - chpu объекта
    * @param boolean $for_item - для карточки - скрываем телефоны
    * @return array 
    */
    public function getItem($id=false, $title=false, $for_item = false){
        global $db;
        $sql = "
            SELECT 
                maintable.*
                ,IF(".$this->tables['building_types'].".title != '',".$this->tables['building_types'].".title,maintable.building_type)  AS building_type
                , ".$this->tables['districts'].".title as district
                , agency_developer.title as developer_title
                , agency_developer.chpu_title as developer_chpu_title
                , agency_developer.doverie_years as `developer_doverie_years`
                , agency_developer.payed_page as `developer_payed_page`
                , user_developer.id = maintable.id_user AS developer_owned
                , agency_seller.title as `seller_title`
                , agency_seller.chpu_title as `seller_chpu_title`
                , agency_seller.doverie_years as `agency_seller_doverie_years`
                , agency_seller.payed_page as `agency_seller_payed_page`
                , agency_advert.title as `advert_seller`
                , agency_advert.chpu_title as `advert_seller_chpu_title`
                , agency_seller.payed_page as `agency_advert_payed_page`
                , IF(agency_developer.email_service!='', agency_developer.email_service,
                   IF(agency_developer.email!='', agency_developer.email, user_developer.email)
                ) as email
                , IF(agency_seller.title!='', agency_seller.title,
                   IF(agency_developer.title!='', agency_developer.title, '')
                ) as agency_title
                , IF(agency_seller.title!='', agency_seller.title,
                   IF(agency_developer.title!='', agency_developer.title, '')
                ) as agency_title
                , IF(agency_seller.title!='', agency_seller_photos.name,
                   IF(agency_developer.title!='', agency_developer_photos.name, '')
                ) as agency_photo_name
                , IF(agency_seller.title!='', LEFT(agency_seller_photos.name, 2),
                   IF(agency_developer.title!='', LEFT(agency_developer_photos.name, 2), '')
                ) as agency_subfolder
                , IF(maintable.exclusive_seller = 1,'true','') as exclusive_seller
                , IF(maintable.class = 3, 'Премиум',
                    IF(maintable.class = 2, 'Бизнес',
                     IF(maintable.class = 4, 'Комфорт',
                     'Эконом')
                     )
                 ) as class_title
                , agency_seller.title as agency_seller_title
                , agency_developer.title as agency_developer_title
                , ".$this->tables['geodata'].".offname as `district_area`
                , ".$this->tables['subways'].".title as subway
                , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                , ".$this->tables['subway_lines'].".color as `subway_color`
                , ".$this->tables['way_types'].".title as way_type
                , agency_developer.advert_phone as agency_developer_advert_phone
                , agency_seller.advert_phone as agency_seller_advert_phone
                , IF(agency_seller.advert_phone!='', agency_seller.advert_phone,
                   IF(agency_developer.advert_phone!='', agency_developer.advert_phone, '')
                ) as agency_advert_phone
                , agency_developer.advert_phone<>'' as developer_has_advert_phone
                , agency_seller.advert_phone<>'' as seller_has_advert_phone
                , agency_seller.phone_1 as agency_seller_phone_1
                , agency_developer.phone_1 as agency_developer_phone_1
                , agency_seller.payed_page as agency_seller_payed_page
                , agency_developer.payed_page as agency_developer_payed_page
               
                , IF(agency_seller.chpu_title!='', agency_seller.chpu_title,
                   IF(agency_developer.chpu_title!='', agency_developer.chpu_title, '')
                ) as agency_chpu_title
                , IF(agency_seller.phone_1!='', agency_seller.phone_1,
                   IF(agency_developer.phone_1!='', agency_developer.phone_1, '')
                ) as agency_phone_1
                , IF(agency_seller.phone_2!='', agency_seller.phone_2,
                   IF(agency_developer.phone_2!='', agency_developer.phone_2, '')
                ) as agency_phone_2
                , IF(agency_seller.phone_3!='', agency_seller.phone_3,
                   IF(agency_developer.phone_3!='', agency_developer.phone_3, '')
                ) as agency_phone_3,
                er.expert_rating,
                ur.user_rating,
                (SELECT COUNT(*) FROM ".$this->tables['video_konkurs']." WHERE id_estate_complex = maintable.id AND ( external_link!='' OR youtube_link!='' ) AND status = 1 AND complex_type = 1) AS videos_count,
                (SELECT COUNT(*) FROM ".$this->tables['comments']." WHERE id_parent = maintable.id AND parent_type = 8 AND comments_active = 1) as comments_count
            FROM ".$this->tables['housing_estates']."  maintable
            LEFT JOIN ".$this->tables['building_types']." ON maintable.id_building_type = ".$this->tables['building_types'].".id
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['users']." user_developer ON user_developer.id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." agency_developer ON agency_developer.id = user_developer.id_agency
            LEFT JOIN ".$this->tables['agencies_photos']." agency_developer_photos ON agency_developer.id_main_photo = agency_developer_photos.id
            LEFT JOIN ".$this->tables['users']." user_seller ON user_seller.id = maintable.id_seller
            LEFT JOIN ".$this->tables['agencies']." agency_seller ON agency_seller.id = user_seller.id_agency
            LEFT JOIN ".$this->tables['agencies_photos']." agency_seller_photos ON agency_seller.id_main_photo = agency_seller_photos.id
            LEFT JOIN ".$this->tables['users']." user_advert ON user_advert.id = maintable.id_advert_agency
            LEFT JOIN ".$this->tables['agencies']." agency_advert ON agency_advert.id = user_advert.id_agency
            LEFT JOIN ( SELECT ROUND( AVG(rating), 2 )  as expert_rating, id_parent, COUNT(*) as votes_total FROM ".$this->tables['housing_estates_voting']." WHERE is_expert = 1 GROUP BY ".$this->tables['housing_estates_voting'].".id_parent) er ON er.id_parent = maintable.id
            LEFT JOIN ( SELECT ROUND( AVG(rating), 2 )  as user_rating, id_parent, COUNT(*) as votes_total FROM ".$this->tables['housing_estates_voting']." WHERE is_expert != 1 GROUP BY ".$this->tables['housing_estates_voting'].".id_parent) ur ON ur.id_parent = maintable.id
        ";    
        
        if(!empty($id)) $sql .= " WHERE maintable.id = ".$id;                         
        elseif(!empty($title)) $sql .= " WHERE maintable.chpu_title = '".$db->real_escape_string($title)."'";                         
        $row = $db->fetch($sql); 
        if(!empty($row)) {
            $row['address'] = $this->getAddress($row);
            return $row;
        }
        return false;
    }
    
    /**
    * получение списка видео с превьюшками
    * @param integer $id - id объекта
    * @return array 
    */    
    function getVideoList($id){
        global $db;
        $list = $db->fetchall(" SELECT 
                                    REPLACE(".$this->tables['video_konkurs'].".external_link,'http://','') as name,
                                    ".$this->tables['video_konkurs'].".youtube_link,
                                    ".$this->tables['housing_estates_photos'].".name as photo_name,
                                    LEFT(".$this->tables['housing_estates_photos'].".name,2) as photo_subfolder
        
                                FROM ".$this->tables['video_konkurs']." 
                                LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->tables['housing_estates'].".id = ".$this->tables['video_konkurs'].".id_estate_complex 
                                LEFT JOIN ".$this->tables['housing_estates_photos']." ON ".$this->tables['housing_estates_photos'].".id = ".$this->tables['housing_estates'].".id_main_photo
                                WHERE 
                                    ".$this->tables['video_konkurs'].".id_estate_complex = ? AND 
                                    ( ".$this->tables['video_konkurs'].".external_link!='' OR ".$this->tables['video_konkurs'].".youtube_link!='' ) AND 
                                    ".$this->tables['video_konkurs'].".status = 1 AND 
                                    ".$this->tables['video_konkurs'].".complex_type = 1
                                GROUP BY ".$this->tables['video_konkurs'].".id
                                ", 
                                false, $id
        );
        return $list;
        
    }
    
    public function getTitles($id){
        global $db;
        $row = $db->fetch("
            SELECT 
                   maintable.id_user,
                   maintable.id_region,
                   maintable.id_area,
                   CONCAT(
                        'Жилой комплекс ',
                       '«', maintable.title, '»'
                   ) as `header`
                 , CONCAT(
                        IF(maintable.apartments = 2, 'ЖК ', 'Апартаменты '),
                        '«', maintable.title, '»',
                        IF(".$this->tables['subways'].".title<>'', CONCAT(', метро ', ".$this->tables['subways'].".title), ''),
                        '.'
                   ) as `title`
                 , CONCAT(
                        'ЖК ',
                         UPPER(LEFT(maintable.title,1)), (RIGHT(maintable.title,CHAR_LENGTH(maintable.title)-1)),
                         IF(".$this->tables['subways'].".title<>'', CONCAT(', метро ', ".$this->tables['subways'].".title), ''),
                         '. Информация об объекте:',

                         IF(".$this->tables['agencies'].".title<>'', CONCAT(' застройщик ', ".$this->tables['agencies'].".title), ''),
                         IF(maintable.214_fz = 1, ', ФЗ 214', ''),
                         IF(maintable.floors > 0 , CONCAT (', этажность ', maintable.floors), '' ),
                         
                         IF(".$this->tables['housing_estate_classes'].".title != '', CONCAT(', класс ', ".$this->tables['housing_estate_classes'].".title),''),
                         IF(".$this->tables['geodata'].".offname<>'', CONCAT(', ', ".$this->tables['geodata'].".offname, ' район ЛО'), ''),
                         IF(".$this->tables['districts'].".title<>'', CONCAT(', ',".$this->tables['districts'].".title, ' район'), ''),
                         '. Полные характеристики, фотогалерея и описание инфраструктуры есть на сайте.'
                   ) as `description`
                   
                    
            FROM ".$this->tables['housing_estates']."  maintable
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['housing_estate_classes']." ON ".$this->tables['housing_estate_classes'].".id = maintable.class
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            
            WHERE maintable.id = ?
        ", $id);          
        if(empty($row)) return false;
        return $row;     
    }
    
    public function getQueries($id){
        global $db;
        $list = $db->fetchall("SELECT ".$this->tables['housing_estates_queries'].".*,
                                     ".$this->tables['build_complete'].".id as id_build_complete,
                                     ".$this->tables['build_complete'].".title as build_complete_title,
                                     ".$this->tables['build_complete'].".year
                               FROM ".$this->tables['housing_estates_queries']."
                               LEFT JOIN ".$this->tables['build_complete']." ON ".$this->tables['build_complete'].".id = ".$this->tables['housing_estates_queries'].".id_build_complete
                               WHERE id_parent = ? AND (".$this->tables['build_complete'].".decade > 0 OR ".$this->tables['build_complete'].".id = 4)
                               GROUP BY ".$this->tables['build_complete'].".id
                               ORDER BY ".$this->tables['build_complete'].".year, ".$this->tables['build_complete'].".decade",
                               false, $id);  
        return $list;
    }
    
    public function getAddress($row){
        global $db;
            if(!empty($row['id_city'])){   
                $city = $db->fetch("SELECT CONCAT(shortname, ' ', offname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? ",
                                    3,
                                    $row['id_region'],
                                    $row['id_area'],
                                    $row['id_city']
                );
            }
            if(!empty($row['id_place'])){   
                $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title  FROM ".$this->tables['geodata']."  
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
                $addr .= !empty($row['house']) ? ', д.'.$row['house']: ''; 
                $addr .= !empty($row['corp']) ? ', к.'.$row['corp']: '';
                return $addr; 
            }
            return $addr.$row['txt_addr'];            
    }    
    
    public function getPrevNext($id, $where){
        global $db;
        $ids = [];
        $list = $db->fetchall("
                    SELECT * FROM (
                        (SELECT id FROM ".$this->tables['housing_estates']." WHERE id < ".$id." " . ( !empty($where) ? " AND ".$where : "" ) ." ORDER BY id DESC LIMIT 3)
                        UNION
                        (SELECT id FROM ".$this->tables['housing_estates']." " . ( !empty($where) ? "WHERE ".$where : "" ) ." ORDER BY id DESC LIMIT 3)
                        ORDER BY id LIMIT 3
                    ) a
                    UNION
                    SELECT * FROM (
                        (SELECT id FROM ".$this->tables['housing_estates']." WHERE id > ".$id.( !empty($where) ? " AND ".$where : "" )." ORDER BY id ASC LIMIT 3)
                        UNION
                        (SELECT id FROM ".$this->tables['housing_estates'] . ( !empty($where) ? " WHERE ".$where : "" ) . " ORDER BY id ASC LIMIT 3)
                        LIMIT 3
                    ) b "
        );     
        foreach($list as $k=>$item) $ids[] = $item['id'];

        return $this->Search($this->tables['housing_estates'].".id IN (".implode(",", $ids).")", 6, 0, $this->tables['housing_estates'].".id DESC");
    }      
    
    public function getObjectsParams($id){
        global $db;
        return $db->fetch("
                            SELECT 
                                a.min_cost_objects, 
                                a.max_cost_objects, 
                                GROUP_CONCAT(a.rooms_group) as rooms_group
                            FROM (
                                   SELECT 
                                        MIN(t.min_cost_objects) as min_cost_objects,
                                        MAX(t.max_cost_objects) as max_cost_objects,
                                        t.rooms_group
                                   FROM (
                                       SELECT 
                                            MIN(cost) as min_cost_objects, 
                                            MAX(cost) as max_cost_objects,
                                            GROUP_CONCAT(DISTINCT IF(rooms_sale=0,'студия',rooms_sale) ORDER BY rooms_sale) as rooms_group
                                       FROM ".$this->tables['build']." 
                                       WHERE ".$this->tables['build'].".published = 1 AND ".$this->tables['build'].".id_housing_estate = ? AND rent = 2
                                       UNION ALL
                                       SELECT 
                                            MIN(cost) as min_cost_objects, 
                                            MAX(cost) as max_cost_objects,
                                            GROUP_CONCAT(DISTINCT IF(rooms_sale=0,'студия',rooms_sale) ORDER BY rooms_sale) as rooms_group
                                       FROM ".$this->tables['live']." 
                                       WHERE ".$this->tables['live'].".published = 1 AND ".$this->tables['live'].".id_housing_estate = ? AND rent = 2
                                   ) t
                            ) a
                               ", $id, $id
        );        
    }
    
    public function getObjectsList( $id ){
        global $db;  
        $list = $db->fetchall(
                            "
                                    SELECT 
                                        COUNT( * ) as cnt, 
                                        rooms_sale,
                                        MIN(cost) as cost,
                                        MAX(cost) as max_cost,
                                        CEIL(AVG(cost/square_full)) as avg_square
                                    FROM  " . $this->tables['build'] . " 
                                    WHERE published = 1
                                    AND id_housing_estate = ?
                                    AND rooms_sale <=4
                                    GROUP BY rooms_sale
                                ", false, $id
        );
        if(!empty($list)){
            $housing_estate_objects_count = 0;
            $avg_square_cost = 0;
            foreach($list as $k=>$item) {
                if(!empty($item['avg_square'])){
                    if($k == 0) $avg_square_cost = $item['avg_square'];
                    else if( $item['avg_square'] < $avg_square_cost) $avg_square_cost = $item['avg_square'];
                }
                $housing_estate_objects_count += $item['cnt'];
            }
            $list['avg_square_cost'] = $avg_square_cost;
            $list['count'] = $housing_estate_objects_count; 
        }       
        return $list;
    }
    
    public function ratingValues($item){
        global $db;

        $rating_form = $db->fetchall("SELECT title, '0' AS value, '0' AS num, '0' AS expert_value, '0' AS expert_num
                                      FROM ".$this->tables['housing_estates_voting_params']." WHERE num_in_form>0",'num_in_form');
        Response::SetBoolean('no_ydirect',(!empty($item['advanced']) && $item['advanced'] == 1)); 
        $expert_prefix = "";
        $rating_form_values = $db->fetchall("SELECT rating_fields,is_expert FROM ".$this->tables['housing_estates_voting']." WHERE id_parent = ".$item['id']."");
        foreach($rating_form_values as $key=>$it){
            if(!empty($it['rating_fields'])){
                $expert_prefix = $it['is_expert']==1 ? "expert_" : "";
                $avg_values = explode( '-', $it['rating_fields'] );
                foreach( $avg_values as $k=>$i ){
                    if( !empty( $rating_form[$k] ) ){
                        $rating_form[$k][$expert_prefix.'value'] += $i;
                        ++$rating_form[$k][$expert_prefix.'num'];
                    }
                }
            }
        }
        $users_voted = false;
        $experts_voted = false;

        foreach($rating_form as $k=>$i){
            if(!empty($rating_form[$k]['num'])){
                $rating_form[$k]['value'] = (float)$rating_form[$k]['value']/(float)$rating_form[$k]['num'];
                $rating_form[$k]['value'] = number_format((float)$rating_form[$k]['value'], 2, '.', '');
                $users_voted = true;
            }
            if(!empty($rating_form[$k]['expert_num'])){
                $rating_form[$k]['expert_value'] = (float)$rating_form[$k]['expert_value']/(float)$rating_form[$k]['expert_num'];
                $rating_form[$k]['expert_value'] = number_format((float)$rating_form[$k]['expert_value'], 2, '.', '');
                $experts_voted = true;
            }
            if(empty($rating_form[$k]['expert_num']) && empty($rating_form[$k]['num'])) unset($rating_form[$k]);
        }

        Response::SetBoolean('users_voted',$users_voted);
        Response::SetBoolean('experts_voted',$experts_voted); 
        return $rating_form;       
    }    
}

?>