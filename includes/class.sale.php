<?php
/**    
* Классы для работы с объектами недвижимости
*/
if(!defined("TYPE_CAMPAIGNS")) define("TYPE_CAMPAIGNS", 'campaigns');
if(!defined("TYPE_OFFERS")) define("TYPE_OFFERS", 'offers');

/**
* Обобщенный описательный класс по недвижимости
*/
class Sale {
    protected $tables = [];
    public $work_table = '';
    public $work_photos_table = '';
    public $work_offers_table = '';
    public $work_offers_photos_table = '';
    
    public function __construct($type){
        $this->tables = Config::$sys_tables;
        // привязка рабочих таблиц к типу выводимых объектов
        switch($type){
            case TYPE_CAMPAIGNS:
                $this->work_table = $this->tables['sale_campaigns'];
                $this->work_photos_table = $this->tables['sale_campaigns_photos'];
                $this->work_offers_table = $this->tables['sale_offers'];
                $this->work_offers_photos_table = $this->tables['sale_offers_photos'];
                break;
            case TYPE_OFFERS:
                $this->work_table = $this->tables['sale_offers'];
                $this->work_photos_table = $this->tables['sale_offers_photos'];
                break;
        }    
        $this->phones_table = $this->tables['sale_phones'];
        $this->tarifs_table = $this->tables['sale_tarifs'];
    }
    public function getTotalOffers($id_campaign){
        global $db;
        if(empty($id_campaign)) return false;
        $list = $db->fetchall("SELECT rooms_total FROM ".$this->tables['sale_offers']." WHERE id_campaign = ? AND published=1 ORDER BY rooms_total", false, $id_campaign);
        $this->total_offers +=  count($list);
    }
    /**
    * Определение типов предложений для акции
    * @param integer id акции
    * @return array список акций
    */
    public function getOffersTypes($id_campaign, $rooms = false){    
        global $db;
        if(empty($id_campaign)) return false;
        if(!empty($rooms) || (isset($rooms) && Validate::isDigit($rooms) && $rooms == 0)) {
            $rooms_array = explode(",",$rooms);
            $list = [];
            foreach($rooms_array as $k=>$item) {
                $list[$k]['rooms_total'] = $item;
            }
        }
        else $list = $db->fetchall("SELECT rooms_total FROM ".$this->tables['sale_offers']." WHERE id_campaign = ? AND published=1 GROUP BY rooms_total ORDER BY rooms_total", false, $id_campaign);
        if(empty($list)) return false;
        $offer_type = [];
        foreach($list as $k=>$item) {
            if($item['rooms_total'] == 0) $offer_type[] = 'студия';
            else $offer_type[] = $item['rooms_total'];
        }
        if(count($offer_type)==1 && $offer_type[0]=='студия') return array('студия','',count($list));
        else return array(implode(',',$offer_type),'-ккв',count($list));
    } 
    
}




/**
* Обобщенный класс для работы с единичным объектом недвижимости
*/
class SaleItem extends Sale{
    protected $fields = [];            // информация (мэпинг) по полям БД - дефолтным и в привзке к рынку, а так же их дефольтные значения
    protected $data_array = [];        // основные данные объекта
    protected $info_array = [];        // информационные данные объекта (из справочников)
    protected $titles_array = [];      // вычисленные заголовки для объекта (header и title)
    
    public $data_changed = true;         // флаг того, что произошли изменения в данных объекта с момента последней загрузки из БД
    public $data_loaded = false;         // флаг того, что данные загружены из БД
    
    public function __construct($type, $id=null){
        // родительский конструктор производит привязку рабочих таблиц к рынку
        parent::__construct($type);
        // если при создании был указан ID, то загружаем из БД соответствующий объект
        if(!empty($id)) $this->Load($id);
    }
    
    
    /**
    * получение основных данных объекта
    * @return array
    */
    public function getData(){
        return $this->data_array;
    }
    
    /**
    * получение адреса объекта
    * @param $id    id объекта
    * @return string
    */
    public function getAddress($id){
        global $db;
            if(!empty($this->data_array['id_city'])){   
                $city = $db->fetch("SELECT CONCAT(shortname, ' ', offname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=?",
                                    3,
                                    $this->data_array['id_region'],
                                    $this->data_array['id_area'],
                                    $this->data_array['id_city']
                );
            }
            if(!empty($this->data_array['id_place'])){   
                $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                    4,
                                    $this->data_array['id_region'],
                                    $this->data_array['id_area'],
                                    $this->data_array['id_city'],
                                    $this->data_array['id_place']
                                    
                );
            }
            $addr = !empty($city) ? $city['title'].', ' : '';
            $addr .= !empty($place) ? $place['title'].', ' : '';

            if(!empty($this->data_array['id_street'])){
                $street = $db->fetch("SELECT CONCAT(offname, ' ',shortname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                    5,
                                    $this->data_array['id_region'],
                                    $this->data_array['id_area'],
                                    $this->data_array['id_city'],
                                    $this->data_array['id_place'],
                                    $this->data_array['id_street']
                                    
                );
                $addr .= !empty($street) ? $street['title'] : '';
                $addr .= !empty($this->data_array['house']) ? ', д.'.$this->data_array['house']: ''; 
                $addr .= !empty($this->data_array['corp']) ? ', к.'.$this->data_array['corp']: '';
                return $addr; 
            }
            return $addr.$this->data_array['txt_addr'];            
    }
    
    /**
    * получение значения поля объекта
    * @param string поле(ключ)
    * @return mixed значение
    */
    public function getField($fieldname){
        return isset($this->data_array[$fieldname]) ? $this->data_array[$fieldname] : null;
    }
    
    /**
    * установка значения поля в объекте
    * @param string поле(ключ)
    * @param mixed значение
    */
    public function setField($fieldname, $value){
        if(isset($this->data_array[$fieldname])) {
            $this->data_array[$fieldname] = $value;
            $this->data_changed = true;
        } else return false;
        return true;
    }
    
    /**
    * загрузка данных объекта из БД
    * @param integer ID объекта в БД
    * @param boolean из таблицы с новыми объектами
    * @return boolean успех загрузки данных
    */
    public function Load($id){
        global $db;
        $row = $db->fetch("SELECT * FROM ".$this->work_table." WHERE id=?",$id);
        if(!empty($row)) {
            $this->data_array = $row;
            $this->data_array['address'] = $this->getAddress($id);
            $this->data_changed = false;
        }
        $this->data_loaded = !empty($row);
        return $this->data_loaded;
    }

    /**
    * сохранение объекта в БД
    * @param boolean в таблицу новых объектов
    * @return успех сохранения объекта в БД
    */
    public function Save(){
        global $db;
        $result = true;
        if(!empty($this->data_changed)){
            $row = false;
            if(!empty($this->data_array['id'])) $row = $db->fetch("SELECT id FROM ".$this->work_table." WHERE id=?",$this->data_array['id']);
            if(empty($row)) {
                $result = $db->insertFromArray($this->work_table, $this->data_array,'id');
                if(!empty($result)) $this->data_array['id'] = $db->insert_id;
            } else {
                $result = $db->updateFromArray($this->work_table, $this->data_array,'id');
            }
            if(!empty($result)) $this->data_changed = false;
        }
        return $result;
    }
    
    /**
    * удаление объекта из БД
    * @param boolean из таблицы новых объектов
    * @param mixed ID объекта (по дефолту не передается, а берется id загруженного объекта
    * @return boolean
    */
    public function Delete($id=false){
        global $db;
        $res = false;
        if(!empty($this->data_array['id']) || !empty($id)){
            $res = $db->query("DELETE FROM ".($this->work_table)." WHERE id=?",$this->data_array['id']);
            if(!empty($res)) $this->data_changed = true;
        }
        return $res;
    }
    
}




/**
* Обобщенный класс для работы со списками объектов недвижимости
*/
class SaleList extends Sale{
    
    public function __construct($type){
        parent::__construct($type);
    }
        
    /**
    * Формирование строки WHERE для sql запроса по массиву параметров
    * @param array массив условий (array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val),поле=>...)
    * @param string режим предварительного просмотра
    * @param boolean из новых (или из опубликованных)
    * @return string
    */
    public function makeWhereClause($clauses, $preview = false){
        $result = [];
        if(!is_array($clauses)) return '';
        foreach($clauses as $field=>$values){
            if(empty($clauses[$field]['checked'])) $result[] = $this->getClause($field, $values, $clauses);
        }
        if(empty($preview)) $result[] =  $this->tables['sale_campaigns'].'.published = 1';
        
        $result[] =  $this->tables['sale_campaigns'].'.date_end > NOW()';
        $result[] =  $this->tables['sale_campaigns'].'.date_start <= NOW()';
        return implode(' AND ', $result);
    }
    
    /**
    * Формирование строки sort by - для сортировки
    * @param integer 
    * @return string
    */    
    public function makeSort($sortby, $implode = true){
            switch($sortby){
                case 6: 
                    // по стоимости по убыванию 
                    return "max_cost DESC";
                case 5: 
                    // по стоимости по возрастанию
                    return "min_cost ASC";
                case 4: 
                    // по популярности по возрастанию
                    return $this->work_table.".views_count ASC";
                case 3: 
                    // по популярности по убыванию
                    return $this->work_table.".views_count DESC";
                case 2: 
                    // по дате по  убыванию
                    return $this->work_table.".date_end DESC, ".$this->work_table.".date_start DESC";
                case 1: 
                default:
                    // по дате по возрастанию
                    return $this->work_table.".date_end ASC, ".$this->work_table.".date_start ASC";                 
            }    
    }

    private function getClause($field, $values, &$clauses){
        global $db;
        $fld_table = empty($values['table']) ? ($this->work_table)."." : $values['table'].".";
        $fld = empty($fld_table) ? $field : $fld_table."`".$field."`";
        $result = $or_resullt = "";
        if(empty($values['checked'])){
            if(isset($values['value'])) $result = $fld." = ".$db->quoted($values['value']);
            elseif(isset($values['set'])) {
                $arr = [];  
                foreach($values['set'] as $item) {
                    $arr[] = $db->quoted($item);
                }
                $result = !empty($arr)?$fld." IN (" . implode(',',$arr) . ')':"";
                //условие добавлено для поиска объектов с количеством комнат 4+
                if(isset($values['from'])) $result = " (".$result." OR ".$fld." >= 4) ";
            } else {
                if(isset($values['from'])) $result = $fld." >= ".$db->quoted($values['from']);
                if(isset($values['to'])) $result = (empty($result)? "" : $result ." AND ") . $fld." <= ".$db->quoted($values['to']);
            }
            $clauses[$field]['checked'] = true;
            if(!empty($result) && !empty($values['or']) && !empty($clauses[$values['or']])){
                $or_resullt = $this->getClause($values['or'], $clauses[$values['or']], $clauses);
            }
        }
        if(!empty($result) && !empty($or_resullt)) $result = "(".$result . (empty($or_resullt) ? "" : " OR ".$or_resullt).")";
        return $result;
    }
   /**
    * получение адреса объекта
    * @param $data    data  - данные объекта
    * @param $short_title    string  - тип адреса (короткий, длинный)
    * @return string
    */
    public function getAddress($data){
        global $db;
        if(!empty($data['id_city'])){   
            $city = $db->fetch("SELECT CONCAT(shortname, ' ', offname) as title  FROM ".$this->tables['geodata']."  
                                WHERE a_level=? AND id_region=? AND id_area=? AND id_city=?",
                                3,
                                $data['id_region'],
                                $data['id_area'],
                                $data['id_city']
            );
        }
        if(!empty($data['id_place'])){   
            $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title  FROM ".$this->tables['geodata']."  
                                WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                4,
                                $data['id_region'],
                                $data['id_area'],
                                $data['id_city'],
                                $data['id_place']
            );
        }
        $addr = !empty($city) ? $city['title'].', ' : '';
        $addr .= !empty($place) ? $place['title'].', ' : '';
        
        if(!empty($data['id_street'])){
            $street = $db->fetch("SELECT CONCAT(offname, ' ',shortname, ' ') as title  FROM ".$this->tables['geodata']."  
                                WHERE  a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                5, 
                                $data['id_region'],
                                $data['id_area'],
                                $data['id_city'],
                                $data['id_place'],
                                $data['id_street']
            );
            $addr .= !empty($street) ? $street['title'] : '';
            $addr .= !empty($data['house']) ? ' , д.'.$data['house']: ''; 
            $addr .= !empty($data['corp']) ? ' , к.'.$data['corp']: '';
            return $addr; 
        } 
        return $addr.$data['txt_addr'];            
    }    

}




/*******************************************************************************************************************
* Класс для работы с единичным объектом рынка жилых объектов
*******************************************************************************************************************/
class SaleItemCampaigns extends SaleItem{
    private $custom_data_array = [];
    public function __construct($id=null){
        parent::__construct(TYPE_CAMPAIGNS, $id);
    }

    
    /**
    * получение информации из необходимых справочников
    * @param boolean принудительно получать данные из БД
    * @return array информация или FALSE если ошибка
    */
    public function getInfo($force_load = false){
        global $db, $requested_page;
        //определение откуда пришел пользователь
        if(empty($this->data_loaded)) return false;
        if(empty($force_load) && !empty($this->info_array)) return $this->info_array;
        $row = $db->fetch("
            SELECT 
                maintable.*
                , ".$this->tables['agencies'].".title as agency_title
                , ".$this->tables['agencies'].".id as id_agency
                , ".$this->tables['agencies'].".activity & 2 as agency_advert
                , ".$this->tables['agencies'].".activity as agency_activity 
                , ".$this->tables['agencies'].".advert_phone as agency_advert_phone 
                , ".$this->tables['building_types'].".title as building_type
                , ".$this->tables['build_complete'].".title as build_complete
                , ".$this->tables['districts'].".title as district
                , ".$this->tables['geodata'].".offname as `district_area`
                , ".$this->tables['subways'].".title as subway
                , ".$this->tables['subways'].".id_subway_line as id_subway_line
                , ".$this->tables['subway_lines'].".line_color as subway_line_color
                , ".$this->tables['way_types'].".title as way_type
                , IF(MIN(".$this->tables['sale_offers'].".cost_w_discount)>0, MIN(".$this->tables['sale_offers'].".cost_w_discount), maintable.cost) as min_cost
                , IF(MIN(".$this->tables['sale_offers'].".cost) != MIN(".$this->tables['sale_offers'].".cost_w_discount), MIN(".$this->tables['sale_offers'].".cost), maintable.old_cost) as min_cost_old

            FROM ( SELECT
                ".$this->getField('cost')." as cost
                , ".$this->getField('old_cost')." as old_cost
                , ".$this->getField('id_agency')." as id_agency
                ,".$this->getField('id_building_type')." as id_building_type
                , ".$this->getField('id_build_complete')." as id_build_complete
                , ".$this->getField('id_district')." as id_district
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_way_type')." as id_way_type
                , ".$this->getField('id_region')." as id_region
                , '".$this->getField('txt_addr')."' as txt_addr
                , ".$this->getField('id_area')." as id_area
                , ".$this->getField('id')." as id_campaign
            ) maintable
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = maintable.id_agency
            LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = maintable.id_building_type
            LEFT JOIN ".$this->tables['build_complete']." ON ".$this->tables['build_complete'].".id = maintable.id_build_complete
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subway_lines'].".id = ".$this->tables['subways'].".id_subway_line
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['sale_offers']." ON ".$this->tables['sale_offers'].".id_campaign = maintable.id_campaign AND ".$this->tables['sale_offers'].".published = 1
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            WHERE maintable.id_agency > 0
            ORDER BY min_cost 
        "); 
        if(!empty($row)) {
            list($row['offer_type'],$row['offer_type_suffix'],$count) = $this->getOffersTypes($this->getField('id'));
            return $this->info_array = $row;
        }
        return false;
    }
}




/*******************************************************************************************************************
* Класс для работы с единичным объектом рынка коммерческих объектов
*******************************************************************************************************************/
class SaleItemOffers extends SaleItem{
    private $custom_data_array = [];
    public function __construct($id=null){
        parent::__construct(TYPE_OFFERS, $id);
    }

    /**
    * получение информации из необходимых справочников
    * @param boolean принудительно получать данные из БД
    * @return array информация или FALSE если ошибка
    */
    public function getInfo($force_load = false){
        global $db;
        if(empty($this->data_loaded)) return false;
        if(empty($force_load) && !empty($this->info_array)) return $this->info_array;
        $row = $db->fetch("
            SELECT 
                  ".$this->tables['users'].".name as user_name
                , ".$this->tables['users'].".lastname as user_lastname
                , ".$this->tables['users'].".phone as user_phone
                , ".$this->tables['users'].".email as user_email
                , ".$this->tables['agencies'].".title as agency_title
                , ".$this->tables['agencies'].".activity & 2 as agency_advert
                , ".$this->tables['agencies'].".phone_1 as agency_phone_1
                , ".$this->tables['agencies'].".phone_2 as agency_phone_2
                , ".$this->tables['agencies'].".phone_3 as agency_phone_3
                , ".$this->tables['agencies'].".advert_phone as agency_advert_phone
                , ".$this->tables['agencies'].".activity as agency_activity
                , ".$this->tables['agencies'].".fax as agency_fax
                , ".$this->tables['agencies'].".email as agency_email
                , ".$this->tables['agencies'].".url as agency_url
                , ".$this->tables['type_objects_commercial'].".title as type_object
                , ".$this->tables['districts'].".title as district
                , ".$this->tables['geodata'].".offname as `district_area`
                , ".$this->tables['subways'].".title as subway
                , ".$this->tables['way_types'].".title as way_type
                , ".$this->tables['facings'].".title as facing
                , ".$this->tables['enters'].".title as enter
           FROM ( SELECT
                  ".$this->getField('id_user')." as id_user
                , ".$this->getField('id_type_object')." as id_type_object
                , ".$this->getField('id_district')." as id_district
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_way_type')." as id_way_type
                , ".$this->getField('id_enter')." as id_enter
                , ".$this->getField('id_facing')." as id_facing
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
            ) maintable
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['type_objects_commercial']." ON ".$this->tables['type_objects_commercial'].".id = maintable.id_type_object
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['enters']." ON ".$this->tables['enters'].".id = maintable.id_enter
            LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = maintable.id_facing
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
        ");
        if(!empty($row)) return $this->info_array = $row;
        return false;
    }
    
    /**
    * получение ЧПУ-заголовков для объекта
    * @param boolean принудительно получать данные из БД
    * @return array массив заголовков
    */
    public function getTitles($force_load=false){
        global $db;
        if(empty($this->data_loaded)) return false;
        if(empty($force_load) && !empty($this->titles_array)) return $this->titles_array;
        $row = $db->fetch("
            SELECT 
                   CONCAT(
                        IF(maintable.rent=1,'Аренда ','Продажа '),
                        IF(elite=1, CONCAT(".$this->tables['type_objects_commercial'].".title_elite_genitive,' '),''),
                        ".$this->tables['type_objects_commercial'].".`title_genitive`,
                        IF(maintable.txt_addr<>'', CONCAT(' - ', maintable.txt_addr, ' '), '')
                   ) as `header`
                 , CONCAT(              
                        IF(maintable.rent=1,'Аренда ','Продажа '),
                        IF(elite=1, CONCAT(".$this->tables['type_objects_commercial'].".title_elite_genitive,' '),''),
                        ".$this->tables['type_objects_commercial'].".`title_genitive`,
                        IF(maintable.txt_addr<>'', CONCAT(' - ', maintable.txt_addr, ' '), ''),
                        IF(".$this->tables['subways'].".title<>'', CONCAT(' - метро ', ".$this->tables['subways'].".title,' '), ''),
                        IF(".$this->tables['districts'].".title<>'', CONCAT(' - ',".$this->tables['districts'].".title, ' район СПб'), ''),
                        IF(maintable.rent=1,' - Аренда ',' - Продажа '),' коммерческой недвижимости'
                   ) as `title`
            FROM ( SELECT
                  ".$this->getField('id')." as object_id
                , ".$this->getField('rent')." as rent
                , ".$this->getField('elite')." as elite
                , ".$this->getField('id_type_object')." as id_type_object
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_district')." as id_district
                , '".$this->getField('txt_addr')."' as txt_addr
            ) maintable
            LEFT JOIN ".$this->tables['type_objects_commercial']." ON ".$this->tables['type_objects_commercial'].".id = maintable.id_type_object
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
        ");      
        if(empty($row)) return false;
        return $this->titles_array = array('title'=>$row['title'], 'header'=>$row['header']);
    }
}

/*******************************************************************************************************************
* Класс для работы со списками объектов рынка жилой недвижимости
*******************************************************************************************************************/
class SaleListCampaigns extends SaleList{
    public $total_offers = 0;   // суммарное кол-во предложений
    public $offer_type_counter = array(1=>0,2=>0,3=>0);   // счетчик кол-ва предложений по типам акций
    public $campaign_type_counter = array(1=>0,2=>0,3=>0);   // счетчик кол-ва акций по типам акций
    
    public function __construct(){
        parent::__construct(TYPE_CAMPAIGNS);        
    }
    
    /**
    * поиск акций
    * @param integer кол-во
    * @param integer начиная с...
    * @param string условие дополнительной фильтрации
    * @param string порядок сортировки
    * @param string рекламная площадка
    * @return array список акций
    */   
    public function Search($count=20, $from=0, $where="", $orderby = "", $partner_url = false, $rooms = false){
        global $db, $requested_page;
        //определение откуда пришел пользователь
        if(empty($where)) $where = $this->work_table.".published = 1 AND ".$this->work_table.".date_end > NOW() AND ".$this->work_table.".date_start <= NOW() AND ".$this->work_table.".id_agency !=8";
        if(!empty($orderby)) $order = $orderby;
        else $order = $this->work_table.".date_end ASC, ".$this->work_table.".date_start ASC";
        $res = $db->fetchall("SELECT 
                                       ".$this->work_table.".*
                                     , DATEDIFF(".$this->work_table.".date_end, NOW()) as time_left
                                     , DATE_FORMAT(".$this->work_table.".date_end,'%e %M %Y') as end_date 
                                     , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                                     , ".$this->tables['subways'].".title as `subway`
                                     , ".$this->tables['districts'].".title as `district`
                                     , ".$this->tables['geodata'].".offname as `district_area`
                                     , ".$this->tables['agencies'].".title as `agency_title`
                                     , ".$this->tables['agencies'].".advert_phone as `agency_advert_phone`
                                     , ".$this->tables['agencies'].".id as `id_agency`
                                     , IF(MIN(".$this->work_offers_table.".cost_w_discount)>0, MIN(".$this->work_offers_table.".cost_w_discount), ".$this->work_table.".cost) as min_cost
                                     , IF(MIN(".$this->work_offers_table.".cost) != MIN(".$this->work_offers_table.".cost_w_discount), MIN(".$this->work_offers_table.".cost), ".$this->work_table.".old_cost) as min_cost_old
                                     , MAX(".$this->work_offers_table.".cost) as max_cost
                              FROM ".$this->work_table."
                              LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                              LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->work_table.".id_agency
                              LEFT JOIN ".$this->work_offers_table." ON ".$this->work_offers_table.".id_campaign = ".$this->work_table.".id AND ".$this->work_offers_table.".published = 1
                              LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                              LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->work_table.".id_district
                              LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area 
                              WHERE ".$where."
                              GROUP BY ".$this->work_table.".id
                              ORDER BY ".$order."
                              LIMIT ".$from.",".$count
                              );        
        //определение адреса и типов объектов
        foreach($res as $k=>$item) {
            //обрезание нулей справа длязначений акции или рассрочки
            if(!empty($res[$k]['discount'])) $res[$k]['discount'] = rtrim(rtrim($res[$k]['discount'], '0'), '.');
            if(!empty($res[$k]['installment'])) $res[$k]['installment'] = rtrim(rtrim($res[$k]['installment'], '0'), '.');
            $res[$k]['header'] = $res[$k]['txt_addr'].(!empty($item['district'])? ' '.$item['district'].' р-н':'').(!empty($item['district_area'])? ' '.$item['district_area'].' р-н ЛО':'');
            //определение типов объектов для всех предложений данной акции
            if(!empty($rooms) || (isset($rooms) && Validate::isDigit($rooms) && $rooms == 0)) list($res[$k]['offer_type'],$res[$k]['offer_type_suffix'],$count) = $this->getOffersTypes($item['id'], $rooms);
            else list($res[$k]['offer_type'],$res[$k]['offer_type_suffix'],$count) = $this->getOffersTypes($item['id']);
            //определение ближайшего рабочего дня
            if(!empty($item['id_offer_type'])) $this->campaign_type_counter[$item['id_offer_type']]++;
        }
        $this->total_offers = $this->totalOffers($where);
        $this->offer_type_counter = $this->getOfferTypes($where);
        return $res;
    }

    public function totalOffers($where=false){
        global $db;
        if(empty($where)) $where = $this->work_table.".published = 1 AND ".$this->work_offers_table.".published = 1 AND ".$this->work_table.".date_end > NOW() AND ".$this->work_table.".date_start <= NOW()";
        $count = $db->fetch("SELECT count(*) as items_count 
                            FROM ".$this->work_offers_table." 
                            LEFT JOIN ".$this->work_table." ON  ".$this->work_offers_table.".id_campaign = ".$this->work_table.".id
                            WHERE ".$where);
        return $count['items_count'];
    }
                                

                
    /**
    * счетчик кол-ва предложений для каждого типа акции
    * @param string условие дополнительной фильтрации
    * @return array счетчик кол-ва предложений
    */   
    public function getOfferTypes($where=""){    
        global $db;
        $list = $db->fetchall(
                "SELECT count(*) as items_count, id_offer_type 
                FROM ".$this->work_offers_table." 
                LEFT JOIN ".$this->work_table." ON  ".$this->work_offers_table.".id_campaign = ".$this->work_table.".id
                WHERE ".$where."
                GROUP BY id_offer_type");
        $counter = [];
        foreach($list as $k=>$item) $counter[$item['id_offer_type']] = $item['items_count'];
        return $counter;

    }
    

}




/*******************************************************************************************************************
* Класс для работы со списками объектов рынка коммерческой недвижимости
*******************************************************************************************************************/
class SaleListOffers extends SaleList{
    public function __construct(){
        parent::__construct(TYPE_OFFERS);        
    }

    /**
    * Поиск объектов по заданным параметрам
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    */
    public function Search($clauses, $count=20, $from=0, $orderby=''){
        global $db;
        if(is_array($clauses)) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses)) $where = $clauses;
        else return false;
        $sql = "SELECT  ".$this->work_table.".*
                         , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                         , ".$this->tables['toilets'].".title as `toilet`
                         , ".$this->tables['facings'].".title as `facing`
                         , ".$this->tables['balcons'].".title as `balcon`
                  FROM ".$this->work_table."
                  LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                  LEFT JOIN ".$this->tables['toilets']." ON ".$this->tables['toilets'].".id = ".$this->work_table.".id_toilet
                  LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->work_table.".id_facing
                  LEFT JOIN ".$this->tables['balcons']." ON ".$this->tables['balcons'].".id = ".$this->work_table.".id_balcon
                  RIGHT JOIN ".$this->tables['sale_campaigns']." ON ".$this->tables['sale_campaigns'].".id = ".$this->work_table.".id_campaign
                  ".(empty($where)?"":"WHERE ".$where)."
                  GROUP BY ".$this->work_table.".id
                  ".(empty($orderby)?"":"ORDER BY ".$orderby)."
                  LIMIT ".$from.",".$count;
        $res = $db->fetchall($sql);
        //определение адреса
        foreach($res as $k=>$item) {
            if(!empty($res[$k]['discount']))  $res[$k]['discount'] = rtrim(rtrim($res[$k]['discount'], '0'), '.');
            if(!empty($res[$k]['discount_in_rubles']))  $res[$k]['discount_in_rubles'] = number_format($item['discount_in_rubles'],0,'.',' ');
            $res[$k]['cost'] = number_format($item['cost'],0,'.',' ');
            $res[$k]['cost_w_discount'] = number_format($item['cost_w_discount'],0,'.',' ');
        }
        return $res;
    }

}
?>