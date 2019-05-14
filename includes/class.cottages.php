<?php
class Cottages {
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
    public function getList($count=0, $from=0, $where="", $order=''){
        global $db;
        if(empty($order)) $order = $this->tables['cottages'].".advanced = 1 DESC, ".$this->tables['cottages'].".id_main_photo > 0 DESC, ".$this->tables['cottages'].".title";
        $sql = "SELECT ".$this->tables['cottages'].".*,
                        IF(".$this->tables['cottages'].".id_user > 0,
                            ".$this->tables['agencies'].".title, 
                            ".$this->tables['cottages_developers'].".title
                        ) as developer_title,
                        
                        ".$this->tables['agencies'].".phone_1, 
                        ".$this->tables['agencies'].".phone_2, 
                        ".$this->tables['agencies'].".phone_3,                         
                        ".$this->tables['cottages_stadies'].".title as stady_title,
                        ".$this->tables['district_areas'].".title as district_title,
                        ".$this->tables['directions'].".title as directions_title,
                        ".$this->tables['cottages_photos'].".`name` as `photo`, 
                        LEFT (".$this->tables['cottages_photos'].".`name`,2) as `subfolder`,
                        CONCAT(SUBSTRING(".$this->tables['cottages'].".idate FROM 9 FOR 2),'.',
                               SUBSTRING(".$this->tables['cottages'].".idate FROM 6 FOR 2),'.',
                               SUBSTRING(".$this->tables['cottages'].".idate FROM 1 FOR 4)) AS `formatted_idate`,
                        DATE_FORMAT(".$this->tables['cottages'].".date_end,'%d.%m.%y') as `formatted_date_end`,
                        (SELECT COUNT(*) FROM ".$this->tables['cottages_photos']." WHERE id_parent=".$this->tables['cottages'].".id) AS photos_count,
                        (SELECT COUNT(*) FROM ".$this->tables['video_konkurs']." WHERE id_estate_complex=".$this->tables['cottages'].".id AND external_link!='' AND status = 1 AND complex_type = 2) AS videos_count,
                        (SELECT COUNT(*) FROM ".$this->tables['country']." WHERE published = 1 AND id_cottage=".$this->tables['cottages'].".id) AS total_objects
                FROM ".$this->tables['cottages']."
                LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->tables['cottages'].".id_user
                LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                LEFT JOIN ".$this->tables['cottages_developers']." ON ".$this->tables['cottages_developers'].".id = ".$this->tables['cottages'].".id_developer
                LEFT JOIN ".$this->tables['cottages_stadies']." ON ".$this->tables['cottages_stadies'].".id = ".$this->tables['cottages'].".id_stady
                LEFT JOIN ".$this->tables['district_areas']." ON ".$this->tables['district_areas'].".id = ".$this->tables['cottages'].".id_district_area
                LEFT JOIN ".$this->tables['directions']." ON ".$this->tables['directions'].".id = ".$this->tables['cottages'].".id_direction
                LEFT JOIN ".$this->tables['cottages_photos']." ON ".$this->tables['cottages_photos'].".id = ".$this->tables['cottages'].".id_main_photo 
                ";
        if(!empty($where)) $sql .= " WHERE ".$where;
        $sql .= " GROUP BY ".$this->tables['cottages'].".id ";
        $sql .= " ORDER BY ".$order;
        if(!empty($count)) $sql .= " LIMIT ".$from.",".$count;
        $rows = $db->fetchall($sql);  
        if(empty($rows)) return [];
        foreach($rows as $k=>$item){    
            $rows[$k]['photos'] = Photos::getList( 'cottages', $item['id'], false, false, 5 );
            if(($item['total_objects']) > 0){
                //поиск объектов                
                $objects = $db->fetch("
                                           SELECT 
                                                MIN(cost) as min_cost_objects, 
                                                MAX(cost) as max_cost_objects
                                           FROM ".$this->tables['country']." 
                                           WHERE ".$this->tables['country'].".published = 1 AND ".$this->tables['country'].".id_cottage = ?
                                       ", $item['id']
                );
                if(!empty($objects)) {
                    $rows[$k] = array_merge($rows[$k], $objects);
                }
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
            if(isset($values['value'])) $result[] = $this->tables['cottages'].".`".$field."` = ".$db->quoted($values['value']);
            elseif(isset($values['set'])) {
                $arr = [];
                foreach($values['set'] as $item) $arr[] = $db->quoted($item);
                $result[] = $this->tables['cottages'].".`".$field."` IN (" . implode(',',$arr) . ')';
            } elseif(isset($values['like'])) $result[] = $this->tables['cottages'].".`".$field."` LIKE '%".$db->real_escape_string($values['like'])."%'";
            else {
                if(isset($values['from'])) $result[] = $this->tables['cottages'].".`".$field."` >= ".$db->quoted($values['from']);
                if(isset($values['to'])) $result[] = $this->tables['cottages'].".`".$field."` <= ".$db->quoted($values['to']);
            }
        }
        $result[] = $this->tables['cottages'].".id_stady=2";
        return implode(' AND ', $result);
    }
    /**
    * получение данных картоки
    * @param integer $id - id объекта
    * @param string $title - chpu объекта
    * @param boolean $for_item - для карточки
    * @return array 
    */
    public function getItem($id=false, $title=false,$for_item=false){
        global $db;
        $sql = "SELECT ".$this->tables['cottages'].".*
                        , agency_developer.title as developer_title
                        , agency_developer.chpu_title as developer_chpu_title
                        , agency_developer.doverie_years as `developer_doverie_years`
                        , agency_developer.payed_page as `developer_payed_page`
                        , agency_seller.title as `seller_title`
                        , agency_seller.chpu_title as `seller_chpu_title`
                        , agency_seller.id_main_photo
                        , agency_seller.doverie_years as `agency_seller_doverie_years`
                        , agency_seller.payed_page as `agency_seller_payed_page`
                        , IF(agency_developer.email_service!='', agency_developer.email_service,
                           IF(agency_developer.email!='', agency_developer.email, user_developer.email)
                        ) as email
                        , IF(agency_seller.title!='', agency_seller.title,
                           IF(agency_developer.title!='', agency_developer.title, '')
                        ) as agency_title
                        , IF(agency_seller.title!='', agency_seller.chpu_title,
                           IF(agency_developer.title!='', agency_developer.chpu_title, '')
                        ) as agency_chpu_title
                        , agency_developer.advert_phone as agency_developer_advert_phone
                        , agency_seller.advert_phone as agency_seller_advert_phone
                        , agency_developer.advert_phone<>'' as developer_has_advert_phone
                        , agency_seller.advert_phone<>'' as seller_has_advert_phone
                        , IF(".$this->tables['cottages'].".exclusive_seller = 1,'true','') as exclusive_seller
                        , agency_seller.doverie_years as doverie_years
                        , DATE_FORMAT(".$this->tables['cottages'].".start_sale,'%e %M %Y') as `formated_start_sale`
                        , DATE_FORMAT(".$this->tables['cottages'].".start_advert,'%e %M %Y') as `formated_start_advert`
                        , ".$this->tables['cottages_stadies'].".title as stady_title
                        , ".$this->tables['build_complete'].".title as build_complete_title
                        , ".$this->tables['union_status'].".title as u_status
                        , ".$this->tables['district_areas'].".title as district_title
                        , ".$this->tables['directions'].".title as directions_title
                        , (SELECT COUNT(*) FROM ".$this->tables['video_konkurs']." WHERE id_estate_complex=".$this->tables['cottages'].".id AND external_link!='' AND status = 1 AND complex_type = 2) AS videos_count
                        ".(!empty($for_item)?
                        ", IF(agency_seller.phone_1!='' AND (agency_seller.payed_page = 1 OR ".$this->tables['cottages'].".advanced = 1), agency_seller.phone_1,
                           IF(agency_developer.phone_1!='' AND (agency_developer.payed_page = 1 OR ".$this->tables['cottages'].".advanced = 1), agency_developer.phone_1, '')
                        ) as agency_phone_1
                        , IF(agency_seller.phone_2!='' AND (agency_seller.payed_page = 1 OR ".$this->tables['cottages'].".advanced = 1), agency_seller.phone_2,
                           IF(agency_developer.phone_2!='' AND (agency_developer.payed_page = 1 OR ".$this->tables['cottages'].".advanced = 1), agency_developer.phone_2, '')
                        ) as agency_phone_2
                        , IF(agency_seller.phone_3!='' AND (agency_seller.payed_page = 1 OR ".$this->tables['cottages'].".advanced = 1), agency_seller.phone_3,
                           IF(agency_developer.phone_3!='' AND (agency_developer.payed_page = 1 OR ".$this->tables['cottages'].".advanced = 1), agency_developer.phone_3, '')
                        ) as agency_phone_3":
                        
                        ", IF(agency_seller.phone_1!='', agency_seller.phone_1,
                           IF(agency_developer.phone_1!='', agency_developer.phone_1, '')
                        ) as agency_phone_1
                        , IF(agency_seller.phone_2!='', agency_seller.phone_2,
                           IF(agency_developer.phone_2!='', agency_developer.phone_2, '')
                        ) as agency_phone_2
                        , IF(agency_seller.phone_3!='', agency_seller.phone_3,
                           IF(agency_developer.phone_3!='', agency_developer.phone_3, '')
                        ) as agency_phone_3")
                        ."
                        , IF(agency_developer_photos.name!='', agency_developer_photos.name,
                           IF(agency_seller_photos.name!='', agency_seller_photos.name, '')
                        ) as agency_photo_name
                        
                        , IF(agency_developer_photos.name!='', LEFT( agency_developer_photos.name, 2),
                           IF(agency_seller_photos.name!='', LEFT( agency_seller_photos.name, 2), '')
                        ) as agency_subfolder

                        FROM ".$this->tables['cottages']."
                        LEFT JOIN ".$this->tables['users']." user_developer ON user_developer.id = ".$this->tables['cottages'].".id_user
                        LEFT JOIN ".$this->tables['agencies']." agency_developer ON agency_developer.id = user_developer.id_agency
                        LEFT JOIN ".$this->tables['agencies_photos']." agency_developer_photos ON agency_developer_photos.id = agency_developer.id_main_photo
                        LEFT JOIN ".$this->tables['users']." user_seller ON user_seller.id = ".$this->tables['cottages'].".id_seller
                        LEFT JOIN ".$this->tables['agencies']." agency_seller ON agency_seller.id = user_seller.id_agency
                        LEFT JOIN ".$this->tables['agencies_photos']." agency_seller_photos ON agency_seller_photos.id = agency_seller.id_main_photo
                        LEFT JOIN ".$this->tables['build_complete']." ON ".$this->tables['build_complete'].".id = ".$this->tables['cottages'].".id_build_complete
                        LEFT JOIN ".$this->tables['cottages_developers']." ON ".$this->tables['cottages_developers'].".id = ".$this->tables['cottages'].".id_developer
                        LEFT JOIN ".$this->tables['cottages_stadies']." ON ".$this->tables['cottages_stadies'].".id = ".$this->tables['cottages'].".id_stady
                        LEFT JOIN ".$this->tables['union_status']." ON ".$this->tables['union_status'].".id = ".$this->tables['cottages'].".id_u_status
                        LEFT JOIN ".$this->tables['district_areas']." ON ".$this->tables['district_areas'].".id = ".$this->tables['cottages'].".id_district_area
                        LEFT JOIN ".$this->tables['directions']." ON ".$this->tables['directions'].".id = ".$this->tables['cottages'].".id_direction";
        if(!empty($id)) $sql .= " WHERE ".$this->tables['cottages'].".id = ".$id;                         
        elseif(!empty($title)) $sql .= " WHERE ".$this->tables['cottages'].".chpu_title = '".$db->real_escape_string($title)."'";                         
        $row = $db->fetch($sql);   
        unset($row['seller_phone']);
        if(!empty($row)) {
            //если это для карточки, применяем правило скрытия телефонов
            if(!empty($for_item) && $row['advanced'] == 2){
                //if(empty($row['developer_payed_page']) || $row['developer_payed_page'] == 2) $row['agency_developer_advert_phone'] = "";
                if(empty($row['developer_has_advert_phone'])) $row['agency_developer_advert_phone'] = "";
                //if(empty($row['agency_seller_payed_page']) || $row['agency_seller_payed_page'] == 2){
                if(empty($row['seller_has_advert_phone']))$row['agency_seller_advert_phone'] = "";
                
            }
            if(!empty($row['agency_seller_advert_phone']) || !empty($row['agency_developer_advert_phone'])){
                $row['agency_phone_1'] = "";
                $row['agency_phone_2'] = "";
                $row['agency_phone_3'] = "";
                $row['seller_phone'] = (!empty($row['agency_seller_advert_phone'])?$row['agency_seller_advert_phone']:$row['agency_developer_advert_phone']);
            }
            elseif(!empty($for_item) && $row['advanced'] == 2){
                $row['agency_phone_1'] = "";
                $row['agency_phone_2'] = "";
                $row['agency_phone_3'] = "";
                $row['seller_phone'] = "";
            }elseif(empty($row['seller_phone'])){
                switch(true){
                    case !empty($row['agency_phone_1']): $row['seller_phone'] = $row['agency_phone_1'];break;
                    case !empty($row['agency_phone_2']): $row['seller_phone'] = $row['agency_phone_2'];break;
                    case !empty($row['agency_phone_3']): $row['seller_phone'] = $row['agency_phone_3'];break;
                }
            }
            return $row;
        }
        if(empty($row)) return [];
    }  

   public function getTitles($id){
        global $db;
        $row = $db->fetch("
            SELECT 
                   maintable.id_user,
                   maintable.id_developer,
                   maintable.id_district_area,
                   LENGTH(maintable.title),
                   CONCAT(
                        'Коттеджный поселок ', '«', maintable.title, '»'
                   ) as `header`
                 , CONCAT(
                        'Коттеджный поселок ', '\"', maintable.title, '\"',
                        IF(".$this->tables['geodata'].".offname<>'', CONCAT(' - ',".$this->tables['geodata'].".offname, ' район ЛО'), ''),
                        IF( maintable.id_user>0 OR ".$this->tables['cottages_developers'].".title IS NOT NULL,
                        CONCAT(' - застройщик ', IF(maintable.id_user > 0, ".$this->tables['agencies'].".title, ".$this->tables['cottages_developers'].".title)),
                        ''),
                        ' - Коттеджные поселки'
                   ) as `title`
                 , CONCAT(
                      'Коттеджный поселок «',maintable.title, '»',
                      IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr), ''),
                      IF(".$this->tables['geodata'].".offname IS NOT NULL,
                         CONCAT(', ',".$this->tables['geodata'].".offname,' район ЛО'), ''
                      )
                      , '.'
                 ) AS `seo_title`
                 , CONCAT(
                      'Коттеджный поселок «',maintable.title, '»',
                      IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr), ''),
                      IF(".$this->tables['geodata'].".offname IS NOT NULL,
                         CONCAT(', ',".$this->tables['geodata'].".offname,' район ЛО'), ''
                      ),
                      '. Информация об объекте: ',
                      IF(".$this->tables['directions'].".title != '', CONCAT(".$this->tables['directions'].".title, ' шоссе, '), ''),
                      IF(maintable.cad_length > 0 , CONCAT(maintable.cad_length, ' км от СПб'), ''),
                      IF(".$this->tables['cottages_stadies'].".title != '' , CONCAT(', ', ".$this->tables['cottages_stadies'].".title), ''),
                      '. Полные характеристики, фотогалерея и описание инфраструктуры есть на сайте.'
                 ) AS `seo_description`
            FROM ".$this->tables['cottages']."  maintable
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['cottages_developers']." ON ".$this->tables['cottages_developers'].".id = maintable.id_developer
            LEFT JOIN ".$this->tables['cottages_stadies']." ON ".$this->tables['cottages_stadies'].".id = maintable.id_stady
            LEFT JOIN ".$this->tables['geodata']." ON (".$this->tables['geodata'].".id_area = maintable.id_district_area OR 
                                                       ".$this->tables['geodata'].".old_id_lenobl = maintable.id_district_area) AND ".$this->tables['geodata'].".a_level = 2
            LEFT JOIN ".$this->tables['directions']." ON ".$this->tables['directions'].".id = maintable.id_direction
            WHERE maintable.id = ?
        ", $id);             echo $db->error;
        //  "<название КП>" - в <название района ЛО в предложном падеже или города> - от застройщика "<название застройщика>"<суффикс>
        if(empty($row)) return false;
        return array('title'=>$row['title'], 'header'=>$row['header'],'seo_title'=>$row['seo_title'],'seo_description'=>$row['seo_description']);
    }
    /**
    * получение объектов КП
    * @param integer $id - id объекта
    * @return array 
    */
    public function getObjectsList($id){
        global $db;  
        //получение списка всех типов
        $types = $db->fetchall("SELECT 
                                    ".$this->tables['object_type_groups'].".id,
                                    ".$this->tables['object_type_groups'].".title_accusative as title,
                                    ".$this->tables['object_type_groups'].".alias,
                                    GROUP_CONCAT(".$this->tables['type_objects_country'].".id) as ids
                                FROM ".$this->tables['object_type_groups']."
                                LEFT JOIN ".$this->tables['type_objects_country']." ON  ".$this->tables['type_objects_country'].".id_group = ".$this->tables['object_type_groups'].".id
                                WHERE ".$this->tables['object_type_groups'].".type = 'country'
                                GROUP BY ".$this->tables['object_type_groups'].".id
                                ORDER BY ".$this->tables['object_type_groups'].".id
        ");
        $deal_types = array (2=>'sell', 1=>'rent');
        $list = [];
        foreach($deal_types as $value => $title){
            foreach($types as $k=>$item){
                $row = $db->fetch("SELECT 
                                            IFNULL(COUNT(*),0) as cnt, 
                                            '".$item['alias']."' as alias, 
                                            '".$title."' as deal_type, 
                                            '".$item['title']."' as title, 
                                            '".$item['id']."' as id_type_group 
                                   FROM ".$this->tables['country']." 
                                   WHERE published = 1 AND rent = ?  AND id_cottage = ? AND id_type_object IN (".$item['ids'].")",
                                   $value, $id
                );
                if(!empty($row['cnt'])) $list[] = $row;
            }
        }
        return $list;
    }            
}

class CottagesSubscriptions extends Cottages{
    private $tables = [];
    public function __construct(){
        $this->tables = Config::$this->tables;    
    }
    
    private function getValueByID($fieldname,$tablename,$id){
        global $db;
        $sql = "SELECT `".$fieldname."` FROM ".$this->tables[$tablename]." WHERE `id`=?";
        $res = $db->fetch($sql,$id);
        return $res[$fieldname];
    }   

    public function checkSubscribeOpportunity($params, $subscr_url){ // Проверка необходимости отображения кнопки "Подписаться на обновления"
        global $db, $auth;
        if (count($params)<2 || strcmp($params['search_type'],'by-parameters')!=0) return false;             // если фильтры не заданы или фильтр не "По параметрам"
        $sql = "SELECT * FROM ".$this->tables['objects_subscriptions']." WHERE `id_user`=".$auth->id." AND `url`='".Convert::ToString($subscr_url)."'";
        $subscr = $db->fetchall($sql);
        $n = count($subscr);
        if ($n>0 && $auth->isAuthorized()) return false;
        return true;    
    }
    
    public function getTitle($params){      // Получение заголовка подписки
        global $db;
        $title = "";
        $title_order = ['square','min_sqear','max_sqear','districts','directions','range','min_range','max_range','min_cost','max_cost'];      // Порядок следования данных в заголовке
        foreach($title_order as $k => $v){
            if (strcmp($v,'square')==0){
                if (!empty($params['min_sqear']) || !empty($params['max_sqear']))
                    $title .= "площадью "; 
            }
            if (!empty($params[$v]) && strcmp($v,'min_sqear')==0){
                $title .= "от ".$params[$v]." м<sup>2</sup> "; 
            }
            if (!empty($params[$v]) && strcmp($v,'max_sqear')==0){
                $title .= "до ".$params[$v]." м<sup>2</sup> ";
            }
            if (!empty($params[$v]) && strcmp($v,'districts')==0){
                $title .= $this->getValueByID('title','district_areas',$params[$v])." р-н ";
            }
            if (!empty($params[$v]) && strcmp($v,'directions')==0){
                $title .= $this->getValueByID('title','directions',$params[$v]);
                if (!in_array($params[$v],array(15,16))) $title .= " направление ";
            }
            if (strcmp($v,'range')==0){
                if (!empty($params['min_range']) || !empty($params['max_range']))
                    $title .= "в удаленности "; 
            }
            if (!empty($params[$v]) && strcmp($v,'min_range')==0){
                $title .= "от ".$params[$v]." км ";
                if (empty($params['max_range'])) $title .= "от СПб "; 
            }
            if (!empty($params[$v]) && strcmp($v,'max_range')==0){
                $title .= "до ".$params[$v]." км ";
                if (empty($params['max_range'])) $title .= "от СПб ";
            }
            if (!empty($params[$v]) && strcmp($v,'min_cost')==0){
                $min_cost = $params[$v];
                if ($min_cost>1000) $min_cost = number_format($min_cost/1000, 2, '.', '');
                $title .= " от ".$min_cost." ".($params[$v]>1000?"млн. ":" тыс. ")."руб.";
            }
            if (!empty($params[$v]) && strcmp($v,'max_cost')==0){
                $max_cost = $params[$v];
                if ($max_cost>1000) $max_cost = number_format($max_cost/1000, 2, '.', '');
                $title .= " до ".$max_cost." ".($params[$v]>1000?"млн. ":" тыс. ")."руб.";
            }
 
        }
        $first_char =  substr($title,0,2);      // Перевод первого символа (в кириллице) в верхний регистр
        $title = mb_substr($title,1,strlen($title)-1,'UTF-8');
        $first_char = mb_strtoupper($first_char,"UTF-8"); 
        $title = $first_char.$title;   
        return $title;      
    }
}
?>