<?php
/**    
* Классы для работы с объектами недвижимости
*/
if(!defined("TYPE_ESTATE_LIVE") ) define("TYPE_ESTATE_LIVE", 'live');
if(!defined("TYPE_ESTATE_BUILD") ) define("TYPE_ESTATE_BUILD", 'build');
if(!defined("TYPE_ESTATE_HOUSING_ESTATES") ) define("TYPE_ESTATE_HOUSING_ESTATES", 'housing_estates');
if(!defined("TYPE_ESTATE_COMMERCIAL") ) define("TYPE_ESTATE_COMMERCIAL", 'commercial');
if(!defined("TYPE_ESTATE_COUNTRY") ) define("TYPE_ESTATE_COUNTRY", 'country');
if(!defined('TYPE_ESTATE_INTER') ) define('TYPE_ESTATE_INTER', 'inter');
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php' );
/**
* Обобщенный описательный класс по недвижимости
*/
class Estate {

    protected $tables = [];
    public $work_table = '';
    public $work_table_new = '';
    public $work_tag_table = '';
    public $work_photos_table = '';
    public $id_agency = 0;
    private $map_results = 0;
    public function __construct($type){
        $this->tables = Config::$sys_tables;
        // привязка рабочих таблиц к рынку
        switch($type){
            case TYPE_ESTATE_LIVE:
                $this->work_table = $this->tables['live'];
                $this->work_table_archive = $this->tables['live_archive'];
                $this->work_table_new = $this->tables['live_new'];
                $this->work_tag_table = $this->tables['tags_live'];
                $this->work_photos_table = $this->tables['live_photos'];
                $this->work_videos_table = $this->tables['live_videos'];
                $this->work_table_stats_shows = $this->tables['live_stats_show_full'];
                $this->work_table_stats_search = $this->tables['live_stats_search_full'];
                $this->work_table_from_search = $this->tables['live_stats_from_search_full'];
                break;
            case TYPE_ESTATE_COMMERCIAL:
                $this->work_table = $this->tables['commercial'];
                $this->work_table_archive = $this->tables['commercial_archive'];
                $this->work_table_new = $this->tables['commercial_new'];
                $this->work_tag_table = $this->tables['tags_commercial'];
                $this->work_photos_table = $this->tables['commercial_photos'];
                $this->work_videos_table = $this->tables['commercial_videos'];
                $this->work_table_stats_shows = $this->tables['commercial_stats_show_full'];
                $this->work_table_stats_search = $this->tables['commercial_stats_search_full'];
                $this->work_table_from_search = $this->tables['commercial_stats_from_search_full'];
                break;
            case TYPE_ESTATE_BUILD:
                $this->work_table = $this->tables['build'];
                $this->work_table_archive = $this->tables['build_archive'];
                $this->work_table_new = $this->tables['build_new'];
                $this->work_tag_table = $this->tables['tags_build'];
                $this->work_photos_table = $this->tables['build_photos'];
                $this->work_videos_table = $this->tables['build_videos'];
                $this->work_table_stats_shows = $this->tables['build_stats_show_full'];
                $this->work_table_stats_search = $this->tables['build_stats_search_full'];
                $this->work_table_from_search = $this->tables['build_stats_from_search_full'];
                break;
            case TYPE_ESTATE_COUNTRY:
                $this->work_table = $this->tables['country'];
                $this->work_table_archive = $this->tables['country_archive'];
                $this->work_table_new = $this->tables['country_new'];
                $this->work_tag_table = $this->tables['tags_country'];
                $this->work_photos_table = $this->tables['country_photos'];
                $this->work_videos_table = $this->tables['country_videos'];
                $this->work_table_stats_shows = $this->tables['country_stats_show_full'];
                $this->work_table_stats_search = $this->tables['country_stats_search_full'];
                $this->work_table_from_search = $this->tables['country_stats_from_search_full'];
                break;
            case TYPE_ESTATE_INTER:
                $this->work_table = $this->tables['inter_estate'];
                $this->work_table_new = null;
                $this->work_tag_table = null;
                $this->work_photos_table = $this->tables['inter_estate_photos'];
                break;
            case TYPE_ESTATE_HOUSING_ESTATES:
                $this->work_table = $this->tables['housing_estates'];
                $this->work_table_archive = null;
                $this->work_table_new = null;
                $this->work_tag_table = null;
                $this->work_photos_table = $this->tables['housing_estates_photos'];
                break;
            case Validate::isDigit($type):
                $this->work_table = "";
                $this->work_table_archive = null;
                $this->work_table_new = null;
                $this->work_tag_table = null;
                $this->work_photos_table = "";
                $this->id_agency = $type;
                break;
        }
        $this->results_on_map = 100;
    }
    
    /**
    * чтение списка весов полей в зависимости от типа недвижимости
    * 
    * @param mixed $db
    * @param mixed $estate_type - тип недвижимости(live,commercial,country,build)
    * @returns array $weights (для не-новостроек массив разбит на аренду(rent) и продажу(sell) )
    */
    public function getWeightsList($estate = false){
        global $db;
        $list_weights = [];
        if (empty($estate) ) $estate_types = array('live','build','commercial','country');
        else $estate_types = array($estate);
            
        foreach($estate_types as $estate_type){
            $weights = $db->fetchall("SELECT * FROM ".Config::$sys_tables['weights_'.$estate_type]);
            foreach($weights as $key=>$item) $list_weights[$estate_type][($item['deal_type']==2)?'sell':'rent'][$item['field_title']] = $weights[$key];
        }
        return $list_weights;
    }
    /**
    * считаем вес объекта
    * 
    * @param integer $id - id объекта
    * @param string $estate_type - тип недвижимости
    * @param mixed $mapping - меппинг формы (для передачи в форму добавления)
    * @return mixed $weight / $steps_weight_array
    */
    public function getItemWeight($id = false, $estate_type = '', $mapping = false, $step = false){
        global $db;
        $item_weight = 0;
        $prefix = "";
        if(empty($mapping) ){
            //читаем данные по объекту с переданным id из таблицы estate_type или estate_type_new
            $item_data = $db->fetch("SELECT * FROM ".Config::$sys_tables[$estate_type]." WHERE id=".$id);
            if (empty($item_data) ){
                $item_data = $db->fetch("SELECT * FROM ".Config::$sys_tables[$estate_type."_new"]." WHERE id=".$id);
                if (empty($item_data) ) return false;
                $prefix = "_new";
            } 
        } else {
            $steps_weight = array('this'=>0, 'total'=>0);
            foreach($mapping as $k=>$item)  {
                if(isset($item['value']) ) $item_data[$k] = $item['value'];
            }
            $id = $mapping['id']['value'];
        }
        //читаем веса для данного типа недвижимости
        $weights = $this->getWeightsList($estate_type)[$estate_type];
        //считаем вес фотографий для объекта
        $db->fetch("SELECT COUNT(*) FROM ".Config::$sys_tables[$estate_type.'_photos']." WHERE id_parent".$prefix." = ".$id);
        $photos_weights = $this->getItemPhotosWeight($db->fetch("SELECT COUNT(*) FROM ".Config::$sys_tables[$estate_type.'_photos']." WHERE id_parent".$prefix." = ".$id)['COUNT(*)']);
        //оставляем только нужную часть таблицы (с арендой или продажей)
        $weights = (empty($item_data['rent']) || $item_data['rent'] == 2)?$weights['sell']:$weights['rent'];
        //суммируем вес по полям объекта
        foreach($weights as $key=>$item){
            //условие изменено, чтобы учесть студии (rooms_total == rooms_sale == 0)
            if(!empty($item_data[$item['field_title']]) || 
               ( ($item['field_title'] == 'rooms_total' || $item['field_title'] == 'rooms_sale') && !empty($item_data[$item['field_title']]) && Validate::isDigit($item_data[$item['field_title']]) && $item_data[$item['field_title']] >= 0 )
              ){
                $item_weight += $item['weight'];
                unset($weights[$key]);
                if(!empty($mapping) )
                    if(!empty($mapping[$item['field_title']]['step']) && $mapping[$item['field_title']]['step'] == $step || 
                        empty($mapping[$item['field_title']]['step']) && $step == 2) $steps_weight['this'] = $steps_weight['this'] + $item['weight'];
            }
            //корректируем значения для участков в загородной и коммерческой
            elseif($estate_type == 'country' && $item_data['id_type_object'] == 13){
                if(in_array($item['field_title'],array('rooms','house','corp','level_total','year_build','id_roof_material','id_consruct_material','id_heating','id_toilet','id_bathroom','phone','id_building_progress','square_live','square_full','id_district') )){
                    $item_weight += $item['weight'];
                    if(!empty($mapping) )
                        if(!empty($mapping[$item['field_title']]['step']) && $mapping[$item['field_title']]['step'] == $step || 
                        empty($mapping[$item['field_title']]['step']) && $step == 2) $steps_weight['this'] += $item['weight'];
                }
            }
            elseif($estate_type == 'commercial' && $item_data['id_type_object'] == 21){
                if(in_array($item['field_title'],array('house','corp','cost2meter','txt_level','phones_count','ceiling_height','parking','security','canalization','hot_water','id_facing','id_decoration','id_enter','heating','id_business_center','square_full','square_usefull') )){
                    $item_weight += $item['weight'];
                    unset($weights[$key]);
                    if(!empty($mapping) )
                        if(!empty($mapping[$item['field_title']]['step']) && $mapping[$item['field_title']]['step'] == $step || 
                        empty($mapping[$item['field_title']]['step']) && $step == 2) $steps_weight['this'] += $item['weight'];
                }
            }
        }
        
        /*
        if ($estate_type!='build'){
            if ($item_data['rent']==1) $weights = $weights['rent'];
            else $weights = $weights['sell'];
            foreach($weights as $key=>$info){
                if(isset($item_data[$info['field_title']]) && !empty($item_data[$info['field_title']]) )
                    $item_weight += $info['weight'];
            }
        }
        else{
            
        }
        */
        
        //если указан район области, записываем вес за поля, которые не нужны
        if (!empty($item_data['geolocation']) && $estate_type!='country' && empty($item_data['id_district']) ){
            $item_weight += !empty($weights['id_district'])?$weights['id_district']['weight']:0;
            if($step == 2) $steps_weight['this'] += !empty($weights['id_district'])?$weights['id_district']['weight']:0;
            //если метро не указано, прибавляем вес за его поля(метро,способ добраться,время в пути)
            if (empty($item_data['id_subway']) )
                $item_weight += (!empty($weights['id_subway'])?$weights['id_subway']['weight']:0) + 
                                (!empty($weights['id_way_type'])?$weights['id_way_type']['weight']:0) + 
                                (!empty($weights['way_time'])?$weights['way_time']['weight']:0);//id_subway,id_way_type,
        }
        
        //коррекции - для не-участков:
        if(!($estate_type == 'commercial' && $item_data['id_type_object'] == 21) && !($estate_type == 'country' && $item_data['id_type_object'] == 13) ){
            //если высота потолков была указана как 0.00, убираем назначенный вес
            if (isset($item_data['ceiling_height']) && $item_data['ceiling_height'] == 0.00 && !empty($weights['ceiling_height']) ) {
                $item_weight -= $weights['ceiling_height']['weight'];
                if(!empty($mapping) )
                    if(!empty($mapping[$item['field_title']]['step']) && $mapping[$item['field_title']]['step'] == $step) $steps_weight['this'] = $steps_weight['this'] - $weights['ceiling_height']['weight'];
            }
            
            //если не указан номер дома и корпус, смотрим их наличие в поле txt_addr и при необходимости прибавляем вес
            if(empty($item_data['house']) && !empty($item_data['txt_addr']) ) $item_weight += (preg_match('/(д(\.?|ом)\s?\d+)/',$item_data['txt_addr']) )?$weights['house']['weight']:0;
            if(empty($item_data['corp']) && !empty($item_data['txt_addr']) ) $item_weight += (preg_match('/(к(\.?|орп\.?)\s?\d+)/',$item_data['txt_addr']) )?$weights['corp']['weight']:0;
        }
        
        
        
        //если id_street==0, то проверяем txt_addr на наличие улицы
        if(empty($item_data['id_street']) && !empty($item_data['txt_addr']) ) $item_weight += (preg_match('/((\W+|\d+)\sшос\.?(се)?[^а-я])|(шос\.?(се)?\s(\W+|\d+) )|((\W+|\d+)\sул\.?(ица)?[^а-я])|(ул\.?(ица)?\s(\W+|\d+) )|((\W+|\d+)\sпр\.?(осп\.?(ект)?)?[^а-я])|(пр\.?(осп\.?(ект)?)?\s(\W+|\d+) )|((\W+|\d+)\sпр\.?((-д\.?)|(оезд) )?[^а-я])|(пр\.?((-д\.?)|(оезд) )?\s(\W+|\d+) )|((\W+|\d+)\sнаб\.?(ережная)?[^а-я])|(наб\.?(ережная)?\s(\W+|\d+) )|((\W+|\d+)\sпер\.?(еулок)?[^а-я])|(пер\.?(еулок)?\s(\W+|\d+) )|((\W+|\d+)\sпл\.?(ощадь)?[^а-я])|(пл\.?(ощадь)?\s(\W+|\d+) )|((\W+|\d+)\sалл\.?(ея)?[^а-я])|(алл\.?(ея)?\s(\W+|\d+) )|((\W+|\d+)\sл\.?((ин\.?)|(иния) )?[^а-я])|(л\.?((ин\.?)|(иния) )?\s(\W+|\d+) )|((\W+|\d+)\sб\.?((-р\.?)|(ульв(ар)?\.?) )?[^а-я])|(б\.?((-р\.?)|(ульв(ар)?\.?) )?\s(\W+|\d+) )|((\W+|\d+)\sдор\.?(ога)?[^а-я])|(дор\.?(ога)?\s(\W+|\d+) )|((\W+|\d+)\sп\.?((-д\.?)|(одъезд) )?[^а-я])|(п\.?((-д\.?)|(одъезд) )?\s(\W+|\d+) )/',$item_data['txt_addr']) )?$weights['id_street']['weight']:0;
        $item_weight += $photos_weights;
        
        if(!empty($mapping) ) {
            $steps_weight['total'] = $item_weight;
            $steps_weight['photos'] = $photos_weights;
            return  $steps_weight;
        }
        return $item_weight;
    }
    /**
    * рассчитываем суммарный вес фотографий объекта
    * 
    * @param mixed $photos_count - количество фотографий
    * @param mixed $photos_weights - список весов фотографий
    * @return int $weight - вес фотографий
    */
    public function getItemPhotosWeight($photos_count){
        global $db;
        if ( $photos_count == 0 ) return 0;
        if ( $photos_count <= 2 ) { return $photos_count; }
        else if( $photos_count >= 3 ){
            if( $photos_count > 20 ) $photos_count = 20;
            return ( 2 + $photos_count );
        }
    }
}

/**
* Обобщенный класс для работы с единичным объектом недвижимости
*/
class EstateItem extends Estate{
    protected $fields = [];            // информация (мэпинг) по полям БД - дефолтным и в привязке к рынку, а так же их дефолтные значения
    protected $hash_fields = [];       // набор полей, по которым вычисляется хэш для объекта
    protected $data_array = [];        // основные данные объекта
    protected $info_array = [];        // информационные данные объекта (из справочников)
    protected $titles_array = [];      // вычисленные заголовки для объекта (header и title)
    
    public $data_changed = true;         // флаг того, что произошли изменения в данных объекта с момента последней загрузки из БД
    public $data_loaded = false;         // флаг того, что данные загружены из БД
    
    public function __construct($type, $id = false, $from_new=false){
        // родительский конструктор производит привязку рабочих таблиц к рынку
        parent::__construct($type);
        // подключение информации (мэпинга) по основным таблицам
        if(empty($this->fields) ) $this->fields = include dirname(__FILE__).'/conf_estate_cols.php';            
        // заполнение данных объекта дефолтными значениями
        $this->data_array = array_merge($this->fields['data_array'], $this->fields[$type]['custom_data_array'], !empty($from_new)?$this->fields['from_new_data_array']:[]);
        // заполнение массива хеш-полей
        $this->hash_fields = array_merge($this->fields['hash_fields'], $this->fields[$type]['custom_hash_fields']);
        // если при создании был указан ID, то загружаем из БД соответствующий объект
        if(!empty($id) ) $this->Load($id,$from_new);
    }
    
    
    /**
    * получение основных данных объекта
    * @return array
    */
    public function getData(){
        return $this->data_array;
    }
    
    public function getGeoNames(){
        global $db;
        $result = [];
        if(!empty($this->data_array['id_area']) && $this->data_array['id_region'] == 78) $this->data_array['id_area'] = 0;
        
        if(!empty($this->data_array['id_city']) ){   
            $city = $db->fetch("SELECT id,CONCAT(shortname, ' ', offname) as title  FROM ".$this->tables['geodata']."  
                                WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? ",
                                3,
                                !empty( $this->data_array['id_region'] ) ? $this->data_array['id_region'] : 0,
                                !empty( $this->data_array['id_area'] ) ? $this->data_array['id_area'] : 0,
                                !empty( $this->data_array['id_city'] ) ? $this->data_array['id_city'] : 0
            );
            $result['city'] = $city;
        }
        if(!empty($this->data_array['id_place']) ){   
            $place = $db->fetch("SELECT id,CONCAT(shortname, ' ',offname) as title  FROM ".$this->tables['geodata']."  
                                WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                4,
                                !empty( $this->data_array['id_region'] ) ? $this->data_array['id_region'] : 0,
                                !empty( $this->data_array['id_area'] ) ? $this->data_array['id_area'] : 0,
                                !empty( $this->data_array['id_city'] ) ? $this->data_array['id_city'] : 0,
                                !empty( $this->data_array['id_place'] ) ? $this->data_array['id_place'] : 0
                                
            );
            $result['place'] = $place;
        }

        if(!empty($this->data_array['id_street']) ){
            $street = $db->fetch("SELECT id,CONCAT(offname, ' ',shortname) as title  FROM ".$this->tables['geodata']."  
                                WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                5,
                                !empty( $this->data_array['id_region'] ) ? $this->data_array['id_region'] : 0,
                                !empty( $this->data_array['id_area'] ) ? $this->data_array['id_area'] : 0,
                                !empty( $this->data_array['id_city'] ) ? $this->data_array['id_city'] : 0,
                                !empty( $this->data_array['id_place'] ) ? $this->data_array['id_place'] : 0,
                                !empty( $this->data_array['id_street'] ) ? $this->data_array['id_street'] : 0
                                
            );
            $addr = !empty($street) ? $street['title'] : '';
            $addr .= !empty($this->data_array['house']) ? ', д.'.$this->data_array['house']: ''; 
            $addr .= !empty($this->data_array['corp']) ? ( Validate::isDigit($this->data_array['corp']) ?  ', к.'.$this->data_array['corp'] : strtoupper($this->data_array['corp']) ) : '';
            $street['title'] = $addr;
            $result['street'] = $street;
        }else{
            if(empty($this->data_array['txt_addr']) ) $this->data_array['txt_addr'] = $this->data_array['address'];
            $result['street'] = array('title' => $this->data_array['txt_addr'], 'id'=>0);
        }
        return $result;
    }
    
    /**
    * получение адреса объекта
    * 
    * @param mixed $id          id объекта
    * @param mixed $return_addr вернуть то как распозналось
    */
    public function getAddress($id,$return_addr = false){
        global $db;
            if(!empty($this->data_array['id_area']) && $this->data_array['id_region'] == 78) $this->data_array['id_area'] = 0;
            if(!empty($this->data_array['id_city']) ){   
                $city = $db->fetch("SELECT CONCAT(shortname, ' ', offname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? ",
                                    3,
                                    !empty( $this->data_array['id_region'] ) ? $this->data_array['id_region'] : 0,
                                    !empty( $this->data_array['id_area'] ) ? $this->data_array['id_area'] : 0,
                                    !empty( $this->data_array['id_city'] ) ? $this->data_array['id_city'] : 0
                );
            }
            if(!empty($this->data_array['id_place']) ){   
                $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                    4,
                                    !empty( $this->data_array['id_region'] ) ? $this->data_array['id_region'] : 0,
                                    !empty( $this->data_array['id_area'] ) ? $this->data_array['id_area'] : 0,
                                    !empty( $this->data_array['id_city'] ) ? $this->data_array['id_city'] : 0,
                                    !empty( $this->data_array['id_place'] ) ? $this->data_array['id_place'] : 0
                );
            }
            $addr = !empty($city) ? $city['title'].', ' : '';
            $addr .= !empty($place) ? $place['title'].', ' : '';

            if(!empty($this->data_array['id_street']) ){
                $street = $db->fetch("SELECT CONCAT(offname, ' ',shortname) as title  FROM ".$this->tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                    5,
                                    !empty( $this->data_array['id_region'] ) ? $this->data_array['id_region'] : 0,
                                    !empty( $this->data_array['id_area'] ) ? $this->data_array['id_area'] : 0,
                                    !empty( $this->data_array['id_city'] ) ? $this->data_array['id_city'] : 0,
                                    !empty( $this->data_array['id_place'] ) ? $this->data_array['id_place'] : 0,
                                    !empty( $this->data_array['id_street'] ) ? $this->data_array['id_street'] : 0
                );
                $addr .= !empty($street) ? $street['title'] : '';
                $addr .= !empty($this->data_array['house']) ? ', д.'.$this->data_array['house']: ''; 
                $addr .= !empty($this->data_array['corp']) ? ', к.'.$this->data_array['corp']: '';
                return $addr; 
            }
            
            if($return_addr) return strlen($addr) > 10 ? $addr : $this->data_array['txt_addr'];
            
            return !empty( $this->data_array['txt_addr'] ) ? $this->data_array['txt_addr'] : '';            
    }
    
    
    /**
    * получение набора фотографий объекта
    * @param boolean объект из тыблицы новых объектов
    */
    public function getPicturesList($from_new=false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        $pics = $db->fetchall("SELECT *, LEFT (`name`,2) as `subfolder`
                               FROM ".$this->work_photos_table."
                               WHERE id_parent".($from_new ? '_new' : '')."=?", 'id', $this->getField('id') );
        return $pics;
    }
    
    /**
    * получение значения поля объекта
    * @param string поле(ключ)
    * @return mixed значение
    */
    public function getField($fieldname){
        return isset($this->data_array[$fieldname]) ? $this->data_array[$fieldname] : 0;
    }
    
    /**
    * установка значения поля в объекте
    * @param string поле(ключ)
    * @param mixed значение
    */
    public function setField($fieldname, $value){
        if(isset($this->data_array[$fieldname]) ) {
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
    public function Load($id, $from_new = false){
        global $db;
        $is_archive = false;
        $row = $db->fetch("SELECT * FROM ".($from_new?$this->work_table_new:$this->work_table)." WHERE ".($from_new?$this->work_table_new:$this->work_table).".id=?",$id);
        //выборка из архивной базы
        if(empty($row) && !empty($this->work_table_archive) ){
            $row = $db->fetch("SELECT *, 'archive' as location FROM ".$this->work_table_archive." WHERE id=?",$id);
            $is_archive = true;
        }
        if(!empty($row) ) {
            $this->data_array = $row;
            $this->data_array['address'] = empty($row['address']) ? $this->getAddress($id) : $row['address'];
            $this->data_array['archive_object'] = $is_archive;
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
    public function Save($to_new = false){
        global $db;
        $result = true;
        if(!empty($this->data_changed) ){
            $this->makeHash();
            $row = false;
            if(!empty($this->data_array['id']) ) $row = $db->fetch("SELECT id FROM ".($to_new?$this->work_table_new:$this->work_table)." WHERE id=?",$this->data_array['id']);
            if(empty($row) ) {
                $result = $db->insertFromArray( $to_new ? $this->work_table_new : $this->work_table, $this->data_array, 'id' );
                if(!empty($result) ) $this->data_array['id'] = $db->insert_id;
            } else {
                $result = $db->updateFromArray( $to_new ? $this->work_table_new : $this->work_table, $this->data_array, 'id' );
            }
            if(!empty($result) ) $this->data_changed = false;
        }
        return $result;   
    }
    
    /**
    * удаление объекта из БД
    * @param boolean из таблицы новых объектов
    * @param mixed ID объекта (по дефолту не передается, а берется id загруженного объекта
    * @return boolean
    */
    public function Delete($from_new = false, $id=false){
        global $db;
        $res = false;
        if(!empty($this->data_array['id']) || !empty($id) ){
            $res = $db->querys("DELETE FROM ".($from_new?$this->work_table_new:$this->work_table)." WHERE id=?",$this->data_array['id']);
            if(!empty($res) ) $this->data_changed = true;
        }
        return $res;
    }
    
    /**
    * расчет хеша объекта
    * 
    */
    public function makeHash(){
        $str = '';
        foreach($this->hash_fields as $fieldname) $str .= Convert::ToString($this->data_array[$fieldname]);
        $this->data_array['hash'] = md5($str);
    }
    
    /**
    * записываем переход из поиска в карточку
    */
    public function updateFromSearch(){
        //проверяем что у объекта есть id
        $res = false;
        if(!empty($this->data_array['id']) ){
            global $db;
            $res = $db->querys("UPDATE ".$this->work_table." SET from_search_count = from_search_count + 1 WHERE id = ?",$this->data_array['id']);
        }
        return $res;
    }
    
    public function getCoordFromComplex($estate_type){
        if(empty($estate_type) ) return false;
        global $db;
        switch(true){
            case $estate_type == "build" && (!empty($this->data_array['id_housing_estate']) ): $field = "id_housing_estate"; $table = "housing_estates";break;
            case $estate_type == "live" && (!empty($this->data_array['id_housing_estate']) ): $field = "id_housing_estate"; $table = "housing_estates";break;
            case $estate_type == "commercial" && (!empty($this->data_array['id_business_center']) ): $field = "id_business_center"; $table = "business_centers";break;
            //у КП нет координат
            /*case $estate_type == "country" && (!empty($this->data_array['id_cottage']) ): $field = "id_cottage"; $table = "cottages";break;*/
            default:return false;
        }
        $complex_info = $db->fetch("SELECT id,lat,lng FROM ".Config::$values['sys_tables'][$table]." WHERE id = ?",$this->data_array[$field]);
        if(!empty($complex_info) && !empty($complex_info['id']) ){
            $this->data_array['lat'] = $complex_info['lat'];
            $this->data_array['lng'] = $complex_info['lng'];
        }
        return true;
    }
}




/**
* Обобщенный класс для работы со списками объектов недвижимости
*/
class EstateList extends Estate{
    public function __construct($type){
        parent::__construct($type);
    }
        
    /**
    * Формирование строки WHERE для sql запроса по массиву параметров
    * @param array массив условий (array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val),поле=>...)
    * @param boolean из новых (или из опубликованных)
    * @param array меппинг для формированиия условий по типам недвижимости
    * @return string
    */
    public function makeWhereClause($clauses, $from_new = false, $mapping = false){
        $result = [];
        if(!is_array($clauses) ) return '';
        foreach($clauses as $field=>$values){
            if(empty($clauses[$field]['checked']) ) $result[] = $this->getClause($field, $values, $clauses, $from_new);
        }
        
        return implode(' AND ', $result);
    }
    
    /**
    * список применимых сортировок для шаблона
    * 
    * @param mixed $estate_type
    */
    public function getSortList($estate_type){
        global $db;
        $list = $db->fetchall("SELECT sort_num, sort_title FROM ".$this->tables['estate_sort']." WHERE estate_type = ?", 'id', $estate_type);
        return $list;
    }
    
    /**
    * Формирование строки sort by - для сортировки
    * @param integer 
    * @return string
    */    
    public function makeSort($sortby, $implode = true,$estate_type = false){
            global $db;
            $sortby = Convert::ToInt($sortby);
            $res = false;
            if(!empty($sortby) && !empty($estate_type) ) $res = $db->fetch("SELECT id FROM ".$this->tables['estate_sort']." WHERE estate_type = ? AND sort_num = ?",$estate_type,$sortby);
            if(!empty($sortby) && empty($res) ) return false;
            switch($sortby){
                case 25: 
                    // по району города по убыванию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".id_district > 1 DESC, ".$this->work_table.".id_region = 78 DESC, district DESC, ".$this->work_table.".id_area > 0 DESC, ".$this->work_table.".id_region = 47 DESC, district_area DESC";
                case 24: 
                    // по району города по возрастанию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".id_district > 1 DESC, ".$this->work_table.".id_region = 78 DESC, district ASC, ".$this->work_table.".id_area > 0 DESC, ".$this->work_table.".id_region = 47 DESC, district_area ASC";
                case 23: 
                    return $this->work_table.".raising_status = 1 DESC, ".$this->work_table.".has_photo DESC, ".$this->work_table.".has_advert_phone DESC,".$this->work_table.".date_in DESC, ".$this->work_table.".date_change DESC,".$this->work_table.".weight DESC";                 
                case 22: 
                    // по типу объекта по убыванию (для жилой)
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".id_type_object DESC, ".$this->work_table.".rooms_sale DESC, ".$this->work_table.".rooms_total DESC";
                case 21: 
                    // по типу объекта по возрастанию (для жилой)
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".id_type_object ASC, ".$this->work_table.".rooms_sale ASC, ".$this->work_table.".rooms_total ASC";
                case 20: 
                    // по площади участка по убыванию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".square_ground > 0 DESC, ".$this->work_table.".square_ground DESC";
                case 19: 
                    // по площади участка по возрастанию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".square_ground > 0 DESC, ".$this->work_table.".square_ground ASC";
                case 18: 
                    // по цене по убыванию (для коммерческих)
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".cost DESC, ".$this->work_table.".cost2meter DESC"; 
                case 17: 
                    // по цене по возрастанию (для коммерческих)
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".cost ASC, ".$this->work_table.".cost2meter ASC"; 
                case 16: 
                    // по типу объекта по убыванию (для загородной и коммерческой)
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".id_type_object DESC";
                case 15: 
                    // по типу объекта по возрастанию (для загородной и коммерческой)
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".id_type_object ASC";
                case 14: 
                    // по метро по убыванию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".id_subway > 1 DESC, subway DESC";
                case 13: 
                    // по метро по возрастанию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".id_subway > 1 DESC, subway ASC";
                case 12: 
                    // по площади кухни по убыванию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".square_kitchen > 0 DESC, ".$this->work_table.".square_kitchen DESC";
                case 11: 
                    // по площади кухни по возрастанию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".square_kitchen > 0 DESC, ".$this->work_table.".square_kitchen ASC";
                case 10: 
                    // по жилой площади по убыванию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".square_live > 0 DESC, ".$this->work_table.".square_live DESC";
                case 9: 
                    // по жилой площади по возрастанию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".square_live > 0 DESC, ".$this->work_table.".square_live ASC";
                case 8: 
                    // по общей площади по убыванию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".square_full > 0 DESC, ".$this->work_table.".square_full DESC";
                case 7: 
                    // по общей площади по возрастанию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".square_full > 0 DESC, ".$this->work_table.".square_full ASC";
                case 6: 
                    // по этажу по убыванию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".level > 0 DESC, ".$this->work_table.".level DESC";
                case 5: 
                    // по этажу по возрастанию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".level > 0 DESC, ".$this->work_table.".level ASC";
                case 4: 
                    // по типу объекта по убыванию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".rooms_sale DESC";
                case 3: 
                    // по типу объекта по возрастанию 
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".rooms_sale ASC";
                case 2: 
                    // по цене по возрастанию
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".cost DESC,".$this->work_table.".has_advert_phone DESC";  
                case 1: 
                    // по цене по возрастанию
                    return $this->work_table.".has_photo DESC, ".$this->work_table.".cost ASC,".$this->work_table.".has_advert_phone DESC"; 
                default:
                    return $this->work_table.".raising_status = 1 DESC, ".$this->work_table.".has_video DESC, ".$this->work_table.".has_photo DESC, ".$this->work_table.".weight DESC,".$this->work_table.".has_advert_phone DESC,".$this->work_table.".date_in DESC, ".$this->work_table.".date_change DESC";                 
            }    
    }

    private function getClause($field, $values, &$clauses, $from_new = false){
        global $db;
        $fld_table = empty($values['table']) ? ($from_new ? $this->work_table_new : $this->work_table)."." : "";
        $fld = empty($fld_table) ? $field : $fld_table."`".$field."`";
        $result = $or_resullt = "";
        if(empty($values['checked']) ){
            if(isset($values['value']) ) $result = $fld." = ".$db->quoted($values['value']);
            elseif(isset($values['not_value']) ) $result = $fld." != ".$db->quoted($values['not_value']);
            elseif(isset($values['set']) || isset($values['not_set']) ) {
                $arr = [];  
                if(isset($values['not_set']) ) $values['set'] = $values['not_set'];
                foreach($values['set'] as $item) {
                    if($field=='rooms_sale' && $item==4) $or_resullt = $fld.">=4";
                    $arr[] = $db->quoted($item);
                }
                $result = !empty($arr)?$fld.(isset($values['not_set'])?" NOT ":"")." IN (" . implode(',',$arr) . ')':"";
            } elseif(isset($values['to_level_total']) ) $result = " level > 1 AND level_total > level + 1";
            else {
                if(isset($values['from']) ) $result = $fld." >= ".$db->quoted($values['from']);
                if(isset($values['to']) ) $result = (empty($result)? "" : $result ." AND ") . $fld." <= ".$db->quoted($values['to']);
            }
            $clauses[$field]['checked'] = true;
            if(!empty($result) && !empty($values['or']) && !empty($clauses[$values['or']]) ){
                $or_resullt = $this->getClause($values['or'], $clauses[$values['or']], $clauses, $from_new);
            }
        }
        if(!empty($result) && !empty($or_resullt) ) $result = "(".$result . (empty($or_resullt) ? "" : " OR ".$or_resullt).")";
        return $result;
    }
   /**
    * получение адреса объекта
    * @param $data    data  - данные объекта
    * @param $short_title    string  - тип адреса (короткий, длинный)
    * @return string
    */
    public function getAddress($data,$short_title=false){
          
        global $db;
        if(!empty($data['id_city']) ){   
            $city = $db->fetch("SELECT CONCAT(shortname_cut, '. ', offname) as title  FROM ".$this->tables['geodata']."  
                                WHERE a_level=? AND id_city=? AND id_area=? AND id_region=? ",
                                3,
                                $data['id_city'],
                                $data['id_area'],
                                $data['id_region']
                                
            );
        }
        if(!empty($data['id_place']) ){   
            $place = $db->fetch("SELECT CONCAT(shortname_cut, '. ',offname) as title  FROM ".$this->tables['geodata']."  
                                WHERE a_level=? AND id_place=? AND id_city=? AND id_area=? AND id_region=?",
                                4,
                                $data['id_place'],
                                $data['id_city'],
                                $data['id_area'],
                                $data['id_region']
            );
        }
        $addr = !empty($city) ? $city['title'].', ' : '';
        $addr .= !empty($place) ? $place['title'].', ' : '';
        
        if(!empty($data['id_street']) && empty($short_title) ){
            $street = $db->fetch("SELECT CONCAT(offname, ' ',shortname) as title  FROM ".$this->tables['geodata']."  
                                WHERE  a_level=? AND id_street=? AND id_place=? AND id_city=? AND id_area=? AND id_region=?",
                                5, 
                                $data['id_street'],
                                $data['id_place'],
                                $data['id_city'],
                                $data['id_area'],
                                $data['id_region']
            );
            $addr .= !empty($street) ? $street['title'] : '';
            $addr .= !empty($data['house']) ? ', д.'.$data['house']: ''; 
            $addr .= !empty($data['corp']) ? ', к.'.$data['corp']: '';
            return strlen($addr) > 10 ? $addr : $data['txt_addr']; 
        } elseif(!empty($short_title) ) return strlen($addr) > 10 ? $addr : $data['txt_addr']; 
        return $data['txt_addr'];            
    }    
    
    /**
    * Загрузка списка id объектов по списку тегов
    * @param array|integer $tags_ids один или список ID тегов
    * @param int deal_type 1-аренда, 2-продажа
    */
    public function getIdsFromTags($tags_ids,$deal_type=1){
        global $db;
        if(!is_array($tags_ids) ) return false;
        //поиск по тегам - формируем условие для пагинатора
        foreach($tags_ids as $key=>$item){
            $item = $db->real_escape_string($item);
            if(!empty($item) ) $tags_ids[$key]=" ".$this->work_table.".id IN (SELECT id_object FROM ".$this->work_tag_table." WHERE ".$this->work_tag_table.".id_tag=".$item.") ";
        }
        
        $condition="(".implode(' AND ',$tags_ids).") AND rent=".$deal_type." AND published=1";
        
        $list=$db->fetchall("SELECT id 
                             FROM ".$this->work_table." 
                             WHERE ".$condition);
        return $list;
    }

    /**
    * Получение групп запросов
    * @param array
    */    
    public function searchExploded($parameters, $estate_type = ''){
        global $db;
        //4 типа поисковых параметров : улица, районы-метро, основные (аренда, статус), поисковые
        $geo_new_clauses = $radius_new_clauses = $new_clauses = $street_clauses = $geo_clauses = $main_clauses = $search_clauses = [];
        $new_parameters = $parameters;
        //поиск геопараметров в запросе
        foreach($new_parameters as $k=>$parameter){
            if(in_array($k, array('geodata', 'district_areas', 'districts', 'subways') )) {
                $geo_clauses[$k] = $parameter;
                unset($new_parameters[$k]);
                if($k == 'geodata') $geodata = true;
            } 
            //разбор оставшихся параметров на поисковые и обязательные
            elseif(in_array($k, array('published', 'rent', 'obj_type') )) $main_clauses[$k] = $parameter;
            else {
                //интервальные значения оставляем интервальными
                if(strstr($k, 'max_')!=''){
                    preg_match("#max_([a-z]{1,})#msi", $k, $match);
                    if(!empty($match[1]) && !empty($new_parameters['min_'.$match[1]]) ) $search_clauses[] = array ($k => $new_parameters[$k], 'min_'.$match[1] => $new_parameters['min_'.$match[1]]);
                }
                elseif(strstr($k, '_from')!=''){
                    preg_match("#([a-z\_]{1,})_from#msi", $k, $match);
                    if(!empty($match[1]) && !empty($new_parameters[$match[1] . '_to']) ) $search_clauses[] = array ($k => $new_parameters[$k], $match[1] . '_to' => $new_parameters[$match[1] . '_to']);
                }
                else if(empty($match[1]) || ('min_'.$match[1] != $k && $match[1].'_to' != $k) ) $search_clauses[$k] = $parameter;
            }
        }
        //получение всевозможных комбинаций необязательных параметров
        // инициализируем пустым множеством
        $results = array([]);
        $results_clauses = $search_clauses;
        foreach ($results_clauses as $k => $element){
            foreach ($results as $combination){
                $results_clauses = array_merge( $combination, array($k=>$element) );
                array_push($results, array_merge($results_clauses) );
            }
        }
        unset($results[0]);
        $search_clauses = $results;
        //разбиение на улицу и поисковые
        if(!empty($street_clauses) ){
            foreach($search_clauses as $k=>$search_clause) {
                $new_array_clauses = array_merge(array('geodata' => $street_clauses), $main_clauses);
                foreach($search_clause as $c=>$clause){
                    $new_array_clauses[$c] = $clause;
                }
                $geo_new_clauses[] = $new_array_clauses;
            }
        } else if(!empty($geo_clauses) ){
            //на район/область/метро и поисковые
            foreach($geo_clauses as $g=>$geo_clause){
                if(count($search_clauses) > 0){
                    foreach($search_clauses as $k=>$search_clause) {
                        $new_array_clauses = array_merge(array($g => $geo_clause), $main_clauses);    
                        foreach($search_clause as $c=>$clause){
                            $new_array_clauses[$c] = $clause;
                        }
                        $geo_new_clauses[] = $new_array_clauses;
                    }
                    if(!empty($geodata) && $g == 'geodata'){
                        foreach($search_clauses as $k=>$search_clause) {
                            $new_array_clauses = array_merge(array($g => $geo_clause), array('radius_geo_id' => 1), $main_clauses);    
                            foreach($search_clause as $c=>$clause){
                                $new_array_clauses[$c] = $clause;
                            }
                            $geo_new_clauses[] = $new_array_clauses;
                        }
                        
                    }
                } else $geo_new_clauses[] = array_merge(array($g => $geo_clause), $main_clauses);
            }
        } 
        if(!empty($search_clauses) ){
            foreach($search_clauses as $k=>$search_clause) {
                $new_array_clauses = [];
                foreach($search_clause as $c=>$clause) $new_array_clauses[$c] = $clause;
                $new_clauses[] = array_merge($new_array_clauses, $main_clauses);
            }
        }
        array_multisort(array_map('count', $radius_new_clauses), SORT_DESC, $radius_new_clauses);
        array_multisort(array_map('count', $new_clauses), SORT_DESC, $new_clauses);
        $new_array = array_merge($geo_new_clauses, $radius_new_clauses, $new_clauses);
        //массив исключенных значений
        /*
        $new_array_excluded = [];
        foreach($new_array as $k => $item){
            //в жилой комнатность без типа объекта исключаем
            if(!empty($item['rooms']) && !(!empty($item['obj_type']) || $estate_type == 'build') ) unset($new_array[$k]);
            else {
                $new_array_excluded[$k] = array_diff_key ( $parameters , $item );        
            }
        } 
        */
        return array($new_array);
    }

/**
    * получение списка объектов результатов поиска с группировкой по адресу
    * @param boolean объект из тыблицы новых объектов
    */
    public function getIdsList($where, $count=20, $from=0, $order='', $groupby='', $for_search = false, $housing_estate_objects = false){
        global $db;
        //для выдачи стройки отдельный кейс - c чтением ЖК
        if(!empty($for_search) && ($this->work_table == 'estate.build' || $this->work_table == 'estate.live') ){
            //совершенно дурацкий костыль для группировки и сортировке по поднятию
            if(strstr($order,'raising_datetime') || strstr($order,'raising_status') ) {
                $raising_list = $db->fetchall("SELECT id,id_housing_estate,id_user FROM ".$this->work_table." ".(empty($where)?"":"WHERE ".$this->work_table.".raising_status = 1 AND ".$where),'id');
                $raising_ids = (!empty($raising_list)?array_keys($raising_list):[]);
                $raising_he_ids = $raising_users_ids = [];
                foreach($raising_list as $k=>$i){
                    if(!empty($i['id_housing_estate']) ) array_push($raising_he_ids, $i['id_housing_estate']);
                    array_push($raising_users_ids,$i['id_user']);
                } 
                $raising_he_ids = array_unique($raising_he_ids);
                $raising_users_ids = array_unique($raising_users_ids);
                $where = $where." AND ".$this->work_table.".raising_status = 2";
            }
            $list = $db->fetchall("SELECT id, id_housing_estate, id_user FROM ".$this->work_table." 
                                      ".(empty($where)?"":"WHERE ".$where)."
                                      ".(empty($groupby)?"":"GROUP BY ".$this->work_table.".".$groupby.", id_housing_estate".(!empty($housing_estate_objects)?", rooms_sale ":" ") )
                                      ,'id');             
            $ids = $housing_estate_ids = $users_ids = [];
            $ids = (!empty($list)?array_keys($list):[]);
            foreach($list as $k=>$i){
                if(!empty($i['id_housing_estate']) ) array_push($housing_estate_ids, $i['id_housing_estate']);
                array_push($users_ids,$i['id_user']);
            } 
            $housing_estate_ids = array_unique($housing_estate_ids);
            $users_ids = array_unique($users_ids);
            unset($list);
            if(!empty($raising_ids) ) $ids = array_merge($raising_ids, $ids);
            if(!empty($raising_he_ids) ) $housing_estate_ids = array_merge($raising_he_ids, $housing_estate_ids);
            if(!empty($raising_users_ids) ) $users_ids = array_merge($raising_users_ids, $users_ids);
        }else{
            //совершенно дурацкий костыль для группировки и сортировке по поднятию
            if(strstr($order,'raising_datetime') || strstr($order,'raising_status') ) {
                $raising_list = $db->fetchall("SELECT id FROM ".$this->work_table." ".(empty($where)?"":"WHERE ".$this->work_table.".raising_status = 1 AND ".$where) );
                $raising_ids = [];
                foreach($raising_list as $k => $item) $raising_ids[] = $item['id'];
                $where = $this->work_table.".raising_status = 2 AND ".$where;
            }
            $list = $db->fetchall("SELECT id FROM ".$this->work_table." 
                                      ".(empty($where)?"":"WHERE ".$where)."
                                      ".(empty($groupby)?"":"GROUP BY ".$this->work_table.".".$groupby)
                                      ,'id');
            $ids = $housing_estate_ids = [];
            $ids = (!empty($list)?array_keys($list):[]);
            unset($list);
            if(!empty($raising_ids) ) $ids = array_merge($raising_ids, $ids);
        }
        if(!empty($for_search) && ($this->work_table == 'estate.build' || $this->work_table == 'estate.live') ) return array($ids,$housing_estate_ids,$users_ids);
        if(empty($ids) ) return [];
        if(!empty($for_search) && ($this->work_table == 'estate.build' || $this->work_table == 'estate.live') ) return array($ids,$housing_estate_ids,$users_ids);
        else return $ids;
    }    
}


/*******************************************************************************************************************
* Класс для работы с единичным объектом рынка жилых объектов
*******************************************************************************************************************/
class EstateItemLive extends EstateItem{
    private $custom_data_array = [];
    private $custom_hash_fields  =  [];
    public function __construct($id=null, $from_new=false){
        parent::__construct(TYPE_ESTATE_LIVE, $id, $from_new);
    }

    
    /**
    * получение информации из необходимых справочников
    * @param boolean принудительно получать данные из БД
    * @return array информация или FALSE если ошибка
    */
    public function getInfo($force_load = false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->info_array) ) return $this->info_array;
        $row = $db->fetch("
            SELECT 
                  ".$this->tables['users'].".name as user_name
                , ".$this->tables['users'].".lastname as user_lastname
                , ".$this->tables['users'].".phone as user_phone
                , ".$this->tables['users'].".email as user_email
                , ".$this->tables['users'].".balance as user_balance
                , ".$this->tables['users'].".id_tarif as user_tarif
                , ".$this->tables['agencies'].".title as agency_title
                , ".$this->tables['agencies'].".chpu_title as agency_chpu_title
                , ".$this->tables['agencies'].".activity & 2 as agency_advert
                , ".$this->tables['agencies'].".phone_1 as agency_phone_1
                , ".$this->tables['agencies'].".phone_2 as agency_phone_2
                , ".$this->tables['agencies'].".phone_3 as agency_phone_3
                , ".$this->tables['agencies'].".advert_phone as agency_advert_phone
                , ".$this->tables['agencies'].".advert_phone_objects as agency_advert_phone_objects
                , ".$this->tables['agencies'].".call_cost as agency_call_cost
                , ".$this->tables['agencies'].".advert_text as agency_advert_text
                , ".$this->tables['agencies'].".activity as agency_activity
                , ".$this->tables['agencies'].".email as agency_email
                 , ".$this->tables['agencies'].".url as agency_url , ".$this->tables['agencies'].".advert as agency_advert
                , ".$this->tables['agencies'].".doverie_years as doverie_years
                , ".$this->tables['agencies_photos'].".name as agency_photo
                , LEFT ( ".$this->tables['agencies_photos'].".name, 2) as agency_subfolder_photo
                , ".$this->tables['building_types'].".title as building_type
                , ".$this->tables['type_objects_live'].".title as type_object
                , ".$this->tables['type_objects_live'].".id_group as type_id_group
                , IF(maintable.id_region != 78,'',".$this->tables['districts'].".title) as district
                , IF(maintable.id_region != 47,'',".$this->tables['geodata'].".offname) as `district_area`
                , ".$this->tables['subways'].".title as subway
                , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                , ".$this->tables['subway_lines'].".color as `subway_color`
                , ".$this->tables['way_types'].".title as way_type
                , ".$this->tables['enters'].".title as enter
                , ".$this->tables['toilets'].".title as toilet
                , ".$this->tables['balcons'].".title as balcon
                , ".$this->tables['elevators'].".title as elevator
                , ".$this->tables['windows'].".title as `window`
                , ".$this->tables['floors'].".title as floor
                , ".$this->tables['hot_waters'].".title as hot_water
                , ".$this->tables['facings'].".title as facing
                , ".$this->tables['housing_estates'].".title as housing_estate
                , ".$this->tables['housing_estates'].".chpu_title as housing_estate_chpu
                , ".$this->tables['housing_estates'].".apartments
                , ".$this->tables['promotions'].".discount
                , ".$this->tables['promotions'].".discount_type, ".$this->tables['agencies'].".advert as `agency_advert`
                , ".$this->tables['promotions'].".title AS promotion_title
                , ".$this->tables['promotions'].".chpu_title AS promotion_chpu
                , ".$this->tables['owners_user_types'].".title AS user_type_title
                , ".$this->tables['work_statuses'].".title AS work_status_title
                , DATE_FORMAT(".$this->tables['promotions'].".date_end,'%d.%m.%y') AS promotion_date_end
            FROM ( SELECT
                  ".$this->getField('id_user')." as id_user
                , ".$this->getField('id_building_type')." as id_building_type
                , ".$this->getField('id_type_object')." as id_type_object
                , ".$this->getField('id_district')." as id_district
                , IFNULL(".$this->getField('id_promotion').",0) as id_promotion
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_way_type')." as id_way_type
                , ".$this->getField('id_toilet')." as id_toilet
                , ".$this->getField('id_balcon')." as id_balcon
                , ".$this->getField('id_elevator')." as id_elevator
                , ".$this->getField('id_enter')." as id_enter
                , ".$this->getField('id_window')." as id_window
                , ".$this->getField('id_floor')." as id_floor
                , ".$this->getField('id_hot_water')." as id_hot_water
                , ".$this->getField('id_facing')." as id_facing
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
                , ".$this->getField('id_housing_estate')." as id_housing_estate
                , ".$this->getField('id_user_type')." as id_user_type
                , ".$this->getField('id_work_status')." as id_work_status
            ) maintable
            LEFT JOIN ".$this->tables['owners_user_types']." ON ".$this->tables['owners_user_types'].".id = maintable.id_user_type
            LEFT JOIN ".$this->tables['work_statuses']." ON ".$this->tables['work_statuses'].".id = maintable.id_work_status
            LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = maintable.id_building_type
            LEFT JOIN ".$this->tables['type_objects_live']." ON ".$this->tables['type_objects_live'].".id = maintable.id_type_object
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['toilets']." ON ".$this->tables['toilets'].".id = maintable.id_toilet
            LEFT JOIN ".$this->tables['balcons']." ON ".$this->tables['balcons'].".id = maintable.id_balcon
            LEFT JOIN ".$this->tables['elevators']." ON ".$this->tables['elevators'].".id = maintable.id_elevator
            LEFT JOIN ".$this->tables['enters']." ON ".$this->tables['enters'].".id = maintable.id_enter
            LEFT JOIN ".$this->tables['windows']." ON ".$this->tables['windows'].".id = maintable.id_window
            LEFT JOIN ".$this->tables['floors']." ON ".$this->tables['floors'].".id = maintable.id_floor
            LEFT JOIN ".$this->tables['hot_waters']." ON ".$this->tables['hot_waters'].".id = maintable.id_hot_water
            LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = maintable.id_facing
            LEFT JOIN ".$this->tables['promotions']." ON ".$this->tables['promotions'].".id = maintable.id_promotion
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
            LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->tables['housing_estates'].".id = maintable.id_housing_estate
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies'].".id_main_photo = ".$this->tables['agencies_photos'].".id
        ");
        
        // build/live/commercial/country - статистика
        if(!empty($row) && !empty($this->work_table_stats_shows) ){
            $row_stats = $db->fetch("SELECT IF(sh.amount IS NULL,0,sh.amount) AS sh_amount, IF(se.amount IS NULL,0,se.amount) AS se_amount, IF(fs.amount IS NULL,0,fs.amount) AS fs_amount
                                     FROM ".$this->work_table."
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount 
                                                FROM ".$this->work_table_stats_shows." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) sh ON sh.id_parent=".$this->work_table.".id
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount
                                                FROM ".$this->work_table_stats_search." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) se ON se.id_parent=".$this->work_table.".id
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount 
                                                FROM ".$this->work_table_from_search." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) fs ON fs.id_parent=".$this->work_table.".id
                                     WHERE ".$this->work_table.".id = ".$this->data_array['id']);
            $row['search_full'] = $row_stats['se_amount'];
            $row['shows_full'] = $row_stats['sh_amount'];
            $row['from_search_full'] = $row_stats['fs_amount'];
        }
        if(!empty($row) ) return $this->info_array = $row;
        return false;
    }
    
    /**
    * получение ЧПУ-заголовков для объекта
    * @param boolean принудительно получать данные из БД
    * @return array массив заголовков
    */
    public function getTitles($force_load=false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->titles_array) ) return $this->titles_array;
        $row = $db->fetch("
            SELECT 
                   CONCAT(
                        IF(maintable.rent=1,'Аренда ','Продажа '),
                        IF(maintable.id_type_object=1,
                            CONCAT(
                                IF(maintable.rooms_total=0,'квартиры-студии',
                                    IF(maintable.rooms_total=1,'однокомнатной квартиры',
                                        IF(maintable.rooms_total=2,'двухкомнатной квартиры',
                                            IF(maintable.rooms_total=3,'трехкомнатной квартиры',
                                                IF(maintable.rooms_total=4,'четырехкомнатной квартиры',
                                                    IF(maintable.rooms_total=5,'пятикомнатной квартиры',
                                                        IF(maintable.rooms_total=6,'шестикомнатной квартиры','многокомнатной квартиры')
                                                        ) ))
                                        )
                                    )
                                )
                            ),
                            IF(maintable.id_type_object=2,
                                CONCAT(
                                    IF(maintable.rooms_sale>1, CONCAT(maintable.rooms_sale, ' комнат'), 'комнаты'),
                                    ' в ',
                                    IF(maintable.rooms_total=1,'одно',
                                        IF(maintable.rooms_total=2,'двух',
                                            IF(maintable.rooms_total=3,'трех',
                                                IF(maintable.rooms_total=4,'четырех',
                                                    IF(maintable.rooms_total=5,'пяти',
                                                        IF(maintable.rooms_total=6,'шести','много')
                                                        ) ))
                                        )
                                    ), 
                                    'комнатной квартире'
                                ),
                                ".$this->tables['type_objects_live'].".title_genitive
                            )    
                        ),
                        IF(maintable.txt_addr<>'', CONCAT(' - ', TRIM(BOTH ',' FROM maintable.txt_addr), ' '), '')
                   ) as `header`
                   , CONCAT(
                        IF(maintable.rent=1,'Аренда ','Продажа '),
                        IF(maintable.id_type_object=1,
                            CONCAT(
                                IF(maintable.rooms_total=0,'квартиры-студии',
                                    IF(maintable.rooms_total=1,'однокомнатной квартиры',
                                        IF(maintable.rooms_total=2,'двухкомнатной квартиры',
                                            IF(maintable.rooms_total=3,'трехкомнатной квартиры',
                                                IF(maintable.rooms_total=4,'четырехкомнатной квартиры',
                                                    IF(maintable.rooms_total=5,'пятикомнатной квартиры',
                                                        IF(maintable.rooms_total=6,'шестикомнатной квартиры','многокомнатной квартиры')
                                                        ) ))
                                        )
                                    )
                                )
                            ),
                            IF(maintable.id_type_object=2,
                                CONCAT(
                                    IF(maintable.rooms_sale>1, CONCAT(maintable.rooms_sale, ' комнат'), 'комнаты'),' в ',
                                    IF(maintable.rooms_total=1,'одно',
                                        IF(maintable.rooms_total=2,'двух',
                                            IF(maintable.rooms_total=3,'трех','много')
                                        )    
                                    ), 
                                    'комнатной квартире'
                                ),
                                ".$this->tables['type_objects_live'].".title_genitive
                            )    
                        )
                   ) as `object_type`
                   , CONCAT(
                        IF(maintable.id_type_object=1,
                            
                            IF(maintable.rooms_total=0, 'квартира-студия',CONCAT(maintable.rooms_total,'-к. квартира') ),
                            IF(maintable.id_type_object=2,
                                CONCAT(IF(maintable.rooms_sale>1, CONCAT(maintable.rooms_sale, ' комнат'), 'комната'),' в ',maintable.rooms_total,'-ккв'),
                                ".$this->tables['type_objects_live'].".title
                            )    
                        )
                   ) as `short_object_type`
                   ,
                   CONCAT(
                        IF(maintable.rent=2, 'Купить ', 'Снять '),
                        IF(maintable.id_type_object=1,
                            CONCAT(
                                IF(maintable.rooms_total=0,'квартиру-студию',
                                    IF(maintable.rooms_total=1,'однокомнатную квартиру',
                                        IF(maintable.rooms_total=2,'двухкомнатную квартиру',
                                            IF(maintable.rooms_total=3,'трехкомнатную квартиру',
                                                IF(maintable.rooms_total=4,'четырехкомнатную квартиру',
                                                    IF(maintable.rooms_total=5,'пятикомнатную квартиру',
                                                        IF(maintable.rooms_total=6,'шестикомнатную квартиру','многокомнатную квартиру')
                                                        ) ))
                                        )
                                    )
                                ) 
                            ),
                            IF(maintable.id_type_object=2,
                                CONCAT(
                                    IF(maintable.rooms_sale>1, CONCAT(maintable.rooms_sale, ' комнаты'), 'комнату'),
                                    ' в ',
                                    IF(maintable.rooms_total=1,'одно',
                                        IF(maintable.rooms_total=2,'двух',
                                            IF(maintable.rooms_total=3,'трех',
                                                IF(maintable.rooms_total=4,'четырех',
                                                    IF(maintable.rooms_total=5,'пяти',
                                                        IF(maintable.rooms_total=6,'шести','много')
                                        ) )) )
                                    ), 
                                    'комнатной квартире'
                                ),
                                ".$this->tables['type_objects_live'].".title_accusative
                            )    
                        ),
                        IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr, ' '), ''),
                        IF(maintable.square_full > 0, CONCAT(', площадь ', maintable.square_full, ' м2 '), ''),
                        IF(maintable.level > 0, CONCAT(', ', maintable.level , ' этаж'), ''),
                        IF(maintable.cost > 0, CONCAT(', ', maintable.cost , ' руб.'), '')
                   
                     ) AS title
                      
                     , CONCAT(
                             IF(maintable.id_type_object=1,
                                CONCAT(
                                IF(maintable.rooms_total=0,'Квартира-студия',
                                    IF(maintable.rooms_total=1,'Однокомнатная квартира',
                                        IF(maintable.rooms_total=2,'Двухкомнатная квартира',
                                            IF(maintable.rooms_total=3,'Трехкомнатная квартира',
                                                IF(maintable.rooms_total=4,'Четырехкомнатная+ квартира',
                                                    ''
                                                )
                                            )
                                        )
                                    )
                                ) 
                                ),
                                IF(maintable.id_type_object=2,
                                    CONCAT(
                                        IF(maintable.rooms_sale>1, CONCAT(maintable.rooms_sale, ' комнат'), 'Комната'),' в ',
                                        IF(maintable.rooms_total=1,'одно',
                                            IF(maintable.rooms_total=2,'двух',
                                                IF(maintable.rooms_total=3,'трех','много')
                                            )    
                                        ), 
                                        'комнатной квартире'
                                    ),
                                    ".$this->tables['type_objects_live'].".title_genitive
                                )    
                             ),
                             IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr, '.'),''),

                             ' Информация об объекте: ',
                             IF(maintable.id_type_object=2,
                                IF(maintable.square_live > 0, CONCAT(maintable.square_live, ' м2'), ''),
                                IF(maintable.square_full > 0, CONCAT(maintable.square_full, ' м2'), '')
                             ),
                             IF(maintable.level> 0,
                                CONCAT(
                                    ', этаж ', maintable.level,
                                    IF(maintable.level_total > 0, CONCAT(' из ', maintable.level_total), '')
                                )
                                ,''
                             ),
                             IF(".$this->tables['building_types'].".title<>'', CONCAT(', ', lower(".$this->tables['building_types'].".title), ' дом'), ''),
                             IF(".$this->tables['subways'].".title<>'', CONCAT(', метро ', ".$this->tables['subways'].".title), ''),
                             IF(".$this->tables['geodata'].".offname<>'', CONCAT(', ', ".$this->tables['geodata'].".offname, ' район ЛО'), ''),
                             IF(".$this->tables['districts'].".title<>'', CONCAT(', ',".$this->tables['districts'].".title, ' район'), ''),
                             '. Полные характеристики, фотогалерея и описание инфраструктуры есть на сайте.'
                         ) as `description`
            FROM ( SELECT
                ".$this->getField('id')." as object_id
                , ".$this->getField('rent')." as rent
                , ".$this->getField('cost')." as object_cost
                , ".$this->getField('published')." as published
                , ".$this->getField('id_type_object')." as id_type_object
                , ".$this->getField('rooms_total')." as rooms_total
                , ".$this->getField('rooms_sale')." as rooms_sale
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_district')." as id_district
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
                , ".$this->getField('id_building_type')." as id_building_type
                , ".$this->getField('by_the_day')." as by_the_day
                , ".$this->getField('level')." as level
                , ".$this->getField('level_total')." as level_total
                , ".$this->getField('cost')." as cost
                , ROUND(".$this->getField('square_full').") as square_full
                , ROUND(".$this->getField('square_live').") as square_live
                , '".$db->real_escape_string($this->getField('txt_addr') )."' as txt_addr
            ) maintable
            LEFT JOIN ".$this->tables['type_objects_live']." ON ".$this->tables['type_objects_live'].".id = maintable.id_type_object
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = maintable.id_building_type
        ");
        
        $row['description'] = preg_replace('/\.\./','.',$row['description']);
        
        if(empty($row) ) return false;
        return $this->titles_array = array('title'=>$row['title'], 'description'=>$row['description'], 'header'=>$row['header'], 'object_type'=>$row['object_type'], 'short_object_type'=>$row['short_object_type']);
    }
    
    /**
    * Генерация текстового описания объекта
    * 
    */
    public function getTextDescription(){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->titles_array) ) return $this->titles_array;
        $row = $db->fetch("SELECT CONCAT(
                                'Объявление о',
                                (IF(maintable.rent = 1,'б аренде ', ' продаже ') ),
                                IF(maintable.id_type_object=1,
                                    CONCAT(
                                        IF(maintable.rooms_total=0,'квартиры-студии',
                                            IF(maintable.rooms_total=1,'однокомнатной квартиры',
                                                IF(maintable.rooms_total=2,'двухкомнатной квартиры',
                                                    IF(maintable.rooms_total=3,'трехкомнатной квартиры',
                                                        IF(maintable.rooms_total=4,'четырехкомнатной квартиры',
                                                            IF(maintable.rooms_total=5,'пятикомнатной квартиры',
                                                                IF(maintable.rooms_total=6,'шестикомнатной квартиры','многокомнатной квартиры')
                                                                ) ))
                                                )
                                            )
                                        )
                                    ),
                                    IF(maintable.id_type_object=2,
                                        CONCAT(
                                            IF(maintable.rooms_sale>1, CONCAT(maintable.rooms_sale, ' комнат'), 'комнаты'),
                                            ' в ',
                                            IF(maintable.rooms_total=1,'одно',
                                                IF(maintable.rooms_total=2,'двух',
                                                    IF(maintable.rooms_total=3,'трех',
                                                        IF(maintable.rooms_total=4,'четырех',
                                                            IF(maintable.rooms_total=5,'пяти',
                                                                IF(maintable.rooms_total=6,'шести','много')
                                                                ) ))
                                                )
                                            ), 
                                            'комнатной квартире'
                                        ),
                                        ".$this->tables['type_objects_live'].".title_genitive
                                    )    
                                ),
                                ', ',
                                IF(".$this->tables['users'].".id_agency = 0,'от собственника, ',''),
                                IF(maintable.id_type_object = 2,'расположенной ',''),
                                'по адресу ',
                                IF(maintable.txt_addr<>'', CONCAT(maintable.txt_addr, ' '), ''),
                                IF(maintable.id_type_object = 2,
                                    CONCAT(
                                        IF(".$this->tables['districts'].".title IS NOT NULL, CONCAT('в ',".$this->tables['districts'].".title_prepositional, ' районе Санкт-Петербурга'), ''),
                                        '. <br/>По цене',
                                        IF(maintable.rent = 1,' ',' в '),
                                        REPLACE(FORMAT(maintable.object_cost,0),',',' '),
                                        IF(maintable.rent = 1,IF(maintable.by_the_day = 1,' рублей в сутки', ' рублей в месяц'),' рублей'),
                                        ' вы получаете уютн',
                                        IF(maintable.id_type_object=1,
                                            CONCAT(
                                                'ую ',
                                                IF(maintable.rooms_total=0,
                                                    'квартиру-студию',
                                                    CONCAT(
                                                        IF(maintable.rooms_total=1,'однокомнатную',
                                                            IF(maintable.rooms_total=2,'двухкомнатную',
                                                                IF(maintable.rooms_total=3,'трехкомнатную',
                                                                    IF(maintable.rooms_total=4,'четырехкомнатную',
                                                                        IF(maintable.rooms_total=5,'пятикомнатную',
                                                                            IF(maintable.rooms_total=6,'шестикомнатной','многокомнатную')
                                                                            ) ))
                                                            )
                                                        ),
                                                        ' квартиру')
                                                )
                                            ),
                                            IF(maintable.id_type_object=2,
                                                CONCAT(
                                                    IF(maintable.rooms_sale = 1, 'ую комнату', 'ые комнаты'),
                                                    ' в ',
                                                    IF(maintable.rooms_total=1,'одно',
                                                        IF(maintable.rooms_total=2,'двух',
                                                            IF(maintable.rooms_total=3,'трех',
                                                                IF(maintable.rooms_total=4,'четырех',
                                                                    IF(maintable.rooms_total=5,'пяти',
                                                                        IF(maintable.rooms_total=6,'шести','много')
                                                                        ) ))
                                                        )
                                                    ), 
                                                    'комнатной квартире'
                                                ),
                                                ".$this->tables['type_objects_live'].".title_accusative
                                            )
                                        )
                                    ),
                                    CONCAT(
                                        '<br/>Стоимость данной квартиры ',
                                        '(',
                                        maintable.square_full,
                                        ' кв.м.)',
                                        IF(maintable.level>0,
                                                CONCAT(' на ',maintable.level,' этаже ',
                                                       IF(maintable.level_total>0,CONCAT(maintable.level_total,'-этажного дома'),'') ),
                                                ''),
                                        IF(".$this->tables['districts'].".title IS NOT NULL, CONCAT(' в ',".$this->tables['districts'].".title_prepositional, ' районе Санкт-Петербурга'), ''),
                                        ' составляет ',
                                        REPLACE(FORMAT(maintable.object_cost,0),',',' '),
                                        ' рублей.'
                                    )
                                ),
                                IF(maintable.id_type_object = 2,
                                   CONCAT(IF(maintable.level>0,
                                                CONCAT(' на ',maintable.level,' этаже ',
                                                       IF(maintable.level_total>0,CONCAT(maintable.level_total,'-этажного дома'),'') ),
                                                ''),
                                          IF(".$this->tables['subways'].".title<>'', CONCAT(' недалеко от метро ', ".$this->tables['subways'].".title,'.'), '.'),
                                         IF(".$this->tables['windows'].".id IS NOT NULL,CONCAT(' Окна выходят ',".$this->tables['windows'].".title,'.'),'')
                                         ),
                                   CONCAT(
                                        IF(".$this->tables['windows'].".id IS NOT NULL,
                                           CONCAT(' Окна выходят ',".$this->tables['windows'].".title,
                                                  IF(".$this->tables['balcons'].".id IS NOT NULL,
                                                   CONCAT(', имеется ',".$this->tables['balcons'].".title,'.'),
                                                   '')
                                           ),
                                           IF(".$this->tables['balcons'].".id IS NOT NULL,
                                                   CONCAT(' Имеется ',".$this->tables['balcons'].".title,'.'),
                                                   '')
                                        ),
                                        IF(".$this->tables['subways'].".title<>'', CONCAT(' Удобное расположение: недалеко от метро ', ".$this->tables['subways'].".title,'.'), '')
                                   )
                                ),
                                IF(maintable.wash_mash = 1 || maintable.phone = 1 || maintable.furniture = 1 || maintable.refrigerator = 1,
                                    CONCAT(
                                           IF(maintable.id_type_object = 2,'<br/>Сама же квартира обладает всем необходимым для комфортной жизни','<br/>В квартире имеется'),
                                           IF((maintable.wash_mash % 2 + maintable.phone % 2 + maintable.furniture % 2 + maintable.refrigerator % 2) >= 2 OR maintable.id_type_object = 1,
                                            CONCAT(': ',
                                               CONCAT_WS(', ',
                                               IF(maintable.wash_mash = 1 , 'стиральная машина',NULL),
                                               IF(maintable.phone = 1 , 'телефон',NULL),
                                               IF(maintable.furniture = 1 , 'мебель',NULL),
                                               IF(maintable.refrigerator = 1 , 'холодильник',NULL) )
                                            ),
                                            ''),
                                            '.'
                                           ),
                                    ''
                                ),
                                ''
                           ) AS text_description
                           FROM ( SELECT
                            ".$this->getField('id')." as object_id
                            , ".$this->getField('rent')." as rent
                            , ".$this->getField('id_user')." as id_user
                            , ".$this->getField('cost')." as object_cost
                            , ".$this->getField('published')." as published
                            , ".$this->getField('id_type_object')." as id_type_object
                            , ".$this->getField('rooms_total')." as rooms_total
                            , ".$this->getField('rooms_sale')." as rooms_sale
                            , ".$this->getField('id_subway')." as id_subway
                            , ".$this->getField('id_district')." as id_district
                            , ".$this->getField('by_the_day')." as by_the_day
                            , ".$this->getField('level')." as level
                            , ".$this->getField('level_total')." as level_total
                            , ".$this->getField('id_window')." as id_window
                            , ".$this->getField('id_balcon')." as id_balcon
                            , ".$this->getField('wash_mash')." as wash_mash
                            , '".$this->getField('phone')."' as phone
                            , ".$this->getField('square_live')." as square_live
                            , ".$this->getField('square_full')." as square_full
                            , ".$this->getField('furniture')." as furniture
                            , ".$this->getField('refrigerator')." as refrigerator
                            , '".$db->real_escape_string($this->getField('txt_addr') )."' as txt_addr
                           ) maintable
                           LEFT JOIN ".$this->tables['type_objects_live']." ON ".$this->tables['type_objects_live'].".id = maintable.id_type_object
                           LEFT JOIN ".$this->tables['windows']." ON ".$this->tables['windows'].".id = maintable.id_window
                           LEFT JOIN ".$this->tables['balcons']." ON ".$this->tables['balcons'].".id = maintable.id_balcon
                           LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
                           LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
                           LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
                           ");
        if(empty($row) ) return false;
        return $row['text_description'];
    }
    
    public function getComplexCoord(){
        return parent::getCoordFromComplex("live");
    }
}




/*******************************************************************************************************************
* Класс для работы с единичным объектом рынка коммерческих объектов
*******************************************************************************************************************/
class EstateItemCommercial extends EstateItem{
    private $custom_data_array = [];
    private $custom_hash_fields  =  [];
    public function __construct($id=null, $from_new=false){
        parent::__construct(TYPE_ESTATE_COMMERCIAL, $id, $from_new);
    }

    /**
    * получение информации из необходимых справочников
    * @param boolean принудительно получать данные из БД
    * @return array информация или FALSE если ошибка
    */
    public function getInfo($force_load = false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->info_array) ) return $this->info_array;
        $row = $db->fetch("
            SELECT 
                  ".$this->tables['users'].".name as user_name
                , ".$this->tables['users'].".lastname as user_lastname
                , ".$this->tables['users'].".phone as user_phone
                , ".$this->tables['users'].".email as user_email
                , ".$this->tables['users'].".balance as user_balance
                , ".$this->tables['users'].".id_tarif as user_tarif
                , ".$this->tables['agencies'].".title as agency_title
                , ".$this->tables['agencies'].".chpu_title as agency_chpu_title
                , ".$this->tables['agencies'].".activity & 2 as agency_advert
                , ".$this->tables['agencies'].".phone_1 as agency_phone_1
                , ".$this->tables['agencies'].".phone_2 as agency_phone_2
                , ".$this->tables['agencies'].".phone_3 as agency_phone_3
                , ".$this->tables['agencies'].".advert_phone as agency_advert_phone
                , ".$this->tables['agencies'].".advert_phone_objects as agency_advert_phone_objects
                , ".$this->tables['agencies'].".call_cost as agency_call_cost
                , ".$this->tables['agencies'].".activity as agency_activity
                , ".$this->tables['agencies'].".email as agency_email
                , ".$this->tables['agencies_photos'].".name as agency_photo
                , LEFT ( ".$this->tables['agencies_photos'].".name, 2) as agency_subfolder_photo
                 , ".$this->tables['agencies'].".url as agency_url , ".$this->tables['agencies'].".advert as agency_advert
                , ".$this->tables['agencies'].".doverie_years as doverie_years
                , ".$this->tables['business_centers'].".title as business_center
                , ".$this->tables['business_centers'].".chpu_title as business_center_chpu
                , ".$this->tables['type_objects_commercial'].".title as type_object
                , ".$this->tables['type_objects_commercial'].".id_group as type_id_group
                , ".$this->tables['districts'].".title as district
                , ".$this->tables['geodata'].".offname as `district_area`
                , ".$this->tables['subways'].".title as subway
                 , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                 , ".$this->tables['subway_lines'].".color as `subway_color`
                , ".$this->tables['way_types'].".title as way_type
                , ".$this->tables['facings'].".title as facing
                , ".$this->tables['enters'].".title as enter
                , ".$this->tables['promotions'].".discount
                , ".$this->tables['promotions'].".discount_type, ".$this->tables['agencies'].".advert as `agency_advert`
                , ".$this->tables['promotions'].".title AS promotion_title
                , ".$this->tables['promotions'].".chpu_title AS promotion_chpu
                , ".$this->tables['promotions'].".date_end AS promotion_date_end
                , ".$this->tables['owners_user_types'].".title AS user_type_title
                , ".$this->tables['work_statuses'].".title AS work_status_title
                , DATE_FORMAT(".$this->tables['promotions'].".date_end,'%d.%m.%y') AS promotion_date_end
           FROM ( SELECT
                  ".$this->getField('id_user')." as id_user
                , IFNULL(".$this->getField('id_promotion').",0) as id_promotion
                , ".$this->getField('id_type_object')." as id_type_object
                , ".$this->getField('id_district')." as id_district
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_way_type')." as id_way_type
                , ".$this->getField('id_enter')." as id_enter
                , ".$this->getField('id_facing')." as id_facing
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
                , ".$this->getField('id_business_center')." as id_business_center
                , ".$this->getField('id_user_type')." as id_user_type
                , ".$this->getField('id_work_status')." as id_work_status
            ) maintable
            LEFT JOIN ".$this->tables['owners_user_types']." ON ".$this->tables['owners_user_types'].".id = maintable.id_user_type
            LEFT JOIN ".$this->tables['work_statuses']." ON ".$this->tables['work_statuses'].".id = maintable.id_work_status
            LEFT JOIN ".$this->tables['type_objects_commercial']." ON ".$this->tables['type_objects_commercial'].".id = maintable.id_type_object
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['enters']." ON ".$this->tables['enters'].".id = maintable.id_enter
            LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = maintable.id_facing
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
            LEFT JOIN ".$this->tables['promotions']." ON ".$this->tables['promotions'].".id = maintable.id_promotion
            LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = maintable.id_business_center
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies'].".id_main_photo = ".$this->tables['agencies_photos'].".id
        ");
        
        // build/live/commercial/country - статистика
        if(!empty($row) && !empty($this->work_table_stats_shows) ){
            $row_stats = $db->fetch("SELECT IF(sh.amount IS NULL,0,sh.amount) AS sh_amount, IF(se.amount IS NULL,0,se.amount) AS se_amount, IF(fs.amount IS NULL,0,fs.amount) AS fs_amount
                                     FROM ".$this->work_table."
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount 
                                                FROM ".$this->work_table_stats_shows." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) sh ON sh.id_parent=".$this->work_table.".id
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount
                                                FROM ".$this->work_table_stats_search." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) se ON se.id_parent=".$this->work_table.".id
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount 
                                                FROM ".$this->work_table_from_search." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) fs ON fs.id_parent=".$this->work_table.".id
                                     WHERE ".$this->work_table.".id = ".$this->data_array['id']);
            $row['search_full'] = $row_stats['se_amount'];
            $row['shows_full'] = $row_stats['sh_amount'];
            $row['from_search_full'] = $row_stats['fs_amount'];
        }
        
        if(!empty($row) ) return $this->info_array = $row;
        return false;
    }
    
    /**
    * получение ЧПУ-заголовков для объекта
    * @param boolean принудительно получать данные из БД
    * @return array массив заголовков
    */
    public function getTitles($force_load=false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->titles_array) ) return $this->titles_array;
        $row = $db->fetch("
            SELECT 
                   CONCAT(
                        IF(maintable.rent=1,'Аренда ','Продажа '),
                        ".$this->tables['type_objects_commercial'].".`title_genitive`,
                        IF(maintable.txt_addr<>'', CONCAT(' - ', maintable.txt_addr, ' '), '')
                   ) as `header`
                 , CONCAT(              
                        IF(maintable.rent=1,'Аренда ','Продажа '),
                        ".$this->tables['type_objects_commercial'].".`title_genitive`
                   ) as `object_type`
                 , CONCAT(              
                        ".$this->tables['type_objects_commercial'].".`title`
                   ) as `short_object_type`
                 , CONCAT(
                        IF(maintable.rent=1,
                           CONCAT('Снять ',
                                  ".$this->tables['type_objects_commercial'].".`title`,
                                  IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr), '')
                                 ),
                               CONCAT('Купить ',
                                      ".$this->tables['type_objects_commercial'].".`title`,
                                      IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr), '')
                               )
                          )
                   ) as title,
                 
                    CONCAT(
                           ".$this->tables['type_objects_commercial'].".`title`,
                           IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr, '.'),''),
                           ' Информация об объекте:',
                           IF(maintable.square_full<>0,CONCAT(' ',maintable.square_full,' м2'),''),
                           IF(".$this->tables['subways'].".title<>'', CONCAT(', метро ', ".$this->tables['subways'].".title), ''),
                           IF(".$this->tables['geodata'].".offname<>'', CONCAT(', ', ".$this->tables['geodata'].".offname, ' район ЛО'), ''),
                           IF(".$this->tables['districts'].".title<>'', CONCAT(', ',".$this->tables['districts'].".title, ' район'), ''),
                           '. Полные характеристики, фотогалерея и описание инфраструктуры есть на сайте.'
                 ) as `description`
            FROM ( SELECT
                  ".$this->getField('id')." as object_id
                , ".$this->getField('rent')." as rent
                , ".$this->getField('published')." as published
                , ".$this->getField('cost')." as object_cost
                , ".$this->getField('square_full')." as square_full
                , ".$this->getField('id_type_object')." as id_type_object
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_district')." as id_district
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
                
                , '".$db->real_escape_string($this->getField('txt_addr') )."' as txt_addr
            ) maintable
            LEFT JOIN ".$this->tables['type_objects_commercial']." ON ".$this->tables['type_objects_commercial'].".id = maintable.id_type_object
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
        ");      
        /*
        
        */
        
        $row['description'] = preg_replace('/\.\./','.',$row['description']);
        
        if(empty($row) ) return false;
        return $this->titles_array = array('title'=>$row['title'], 'description'=>$row['description'], 'header'=>$row['header'], 'object_type'=>$row['object_type'], 'short_object_type'=>$row['short_object_type']);
    }
    
    public function getComplexCoord(){
        return parent::getCoordFromComplex("commercial");
    }
}




/*******************************************************************************************************************
* Класс для работы с единичным объектом рынка строящихся объектов
*******************************************************************************************************************/
class EstateItemBuild extends EstateItem{
    private $custom_data_array = [];
    private $custom_hash_fields  =  [];
    public function __construct($id=null, $from_new=false){
        parent::__construct(TYPE_ESTATE_BUILD, $id, $from_new);
    }

    /**
    * получение информации из необходимых справочников
    * @param boolean принудительно получать данные из БД
    * @return array информация или FALSE если ошибка
    */
    public function getInfo($force_load = false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->info_array) ) return $this->info_array;
        $row = $db->fetch("
            SELECT 
                  ".$this->tables['users'].".name as user_name
                , ".$this->tables['users'].".lastname as user_lastname
                , ".$this->tables['users'].".phone as user_phone
                , ".$this->tables['users'].".email as user_email
                , ".$this->tables['users'].".balance as user_balance
                , ".$this->tables['users'].".id_tarif as user_tarif
                , ".$this->tables['agencies'].".title as agency_title
                , ".$this->tables['agencies'].".chpu_title as agency_chpu_title
                , ".$this->tables['agencies'].".activity & 2 as agency_advert
                , ".$this->tables['agencies'].".phone_1 as agency_phone_1
                , ".$this->tables['agencies'].".phone_2 as agency_phone_2
                , ".$this->tables['agencies'].".phone_3 as agency_phone_3
                , ".$this->tables['agencies'].".advert_phone as agency_advert_phone
                , ".$this->tables['agencies'].".advert_phone_objects as agency_advert_phone_objects
                , ".$this->tables['agencies'].".call_cost as agency_call_cost
                , ".$this->tables['agencies'].".activity as agency_activity
                , ".$this->tables['agencies'].".email as agency_email
                , ".$this->tables['agencies_photos'].".name as agency_photo
                , LEFT ( ".$this->tables['agencies_photos'].".name, 2) as agency_subfolder_photo
                 , ".$this->tables['agencies'].".url as agency_url , ".$this->tables['agencies'].".advert as agency_advert
                , ".$this->tables['agencies'].".doverie_years as doverie_years
                , ".$this->tables['building_types'].".title as building_type
                , IF(maintable.id_region != 78,'',".$this->tables['districts'].".title) as district
                , IF(maintable.id_region != 47,'',".$this->tables['geodata'].".offname) as `district_area`
                , ".$this->tables['subways'].".title as subway
                , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                , ".$this->tables['subway_lines'].".color as `subway_color`
                , ".$this->tables['way_types'].".title as way_type
                , ".$this->tables['housing_estates'].".title as housing_estate
                , ".$this->tables['housing_estates'].".chpu_title as housing_estate_chpu
                , ".$this->tables['housing_estates'].".apartments
                , ".$this->tables['build_complete'].".title as build_complete
                , ".$this->tables['housing_estates'].".title as housing_estate
                , ".$this->tables['developer_statuses'].".title as developer_status
                , ".$this->tables['toilets'].".title as toilet
                , ".$this->tables['balcons'].".title as balcon
                , ".$this->tables['elevators'].".title as elevator
                , ".$this->tables['facings'].".title as facing
                , ".$this->tables['windows'].".title as `window`
                , ".$this->tables['promotions'].".discount
                , ".$this->tables['promotions'].".discount_type, ".$this->tables['agencies'].".advert as `agency_advert`
                , ".$this->tables['promotions'].".title AS promotion_title
                , ".$this->tables['promotions'].".chpu_title AS promotion_chpu
                , ".$this->tables['owners_user_types'].".title AS user_type_title
                , ".$this->tables['work_statuses'].".title AS work_status_title
                , DATE_FORMAT(".$this->tables['promotions'].".date_end,'%d.%m.%y') AS promotion_date_end
            FROM ( SELECT
                  ".$this->getField('id_user')." as id_user
                , ".$this->getField('id_building_type')." as id_building_type
                , ".$this->getField('id_district')." as id_district
                , IFNULL(".$this->getField('id_promotion').",0) as id_promotion
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_way_type')." as id_way_type
                , ".$this->getField('id_build_complete')." as id_build_complete
                , ".$this->getField('id_housing_estate')." as id_housing_estate
                , ".$this->getField('id_developer_status')." as id_developer_status
                , ".$this->getField('id_toilet')." as id_toilet
                , ".$this->getField('id_balcon')." as id_balcon
                , ".$this->getField('id_elevator')." as id_elevator
                , ".$this->getField('id_facing')." as id_facing
                , ".$this->getField('id_decoration')." as id_decoration
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_window')." as id_window
                , ".$this->getField('id_area')." as id_area
                , ".$this->getField('id_user_type')." as id_user_type
                , ".$this->getField('id_work_status')." as id_work_status
            ) maintable
            LEFT JOIN ".$this->tables['owners_user_types']." ON ".$this->tables['owners_user_types'].".id = maintable.id_user_type
            LEFT JOIN ".$this->tables['work_statuses']." ON ".$this->tables['work_statuses'].".id = maintable.id_work_status
            LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = maintable.id_building_type
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['build_complete']." ON ".$this->tables['build_complete'].".id = maintable.id_build_complete
            LEFT JOIN ".$this->tables['developer_statuses']." ON ".$this->tables['developer_statuses'].".id = maintable.id_developer_status
            LEFT JOIN ".$this->tables['toilets']." ON ".$this->tables['toilets'].".id = maintable.id_toilet
            LEFT JOIN ".$this->tables['balcons']." ON ".$this->tables['balcons'].".id = maintable.id_balcon
            LEFT JOIN ".$this->tables['elevators']." ON ".$this->tables['elevators'].".id = maintable.id_elevator
            LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = maintable.id_facing
            LEFT JOIN ".$this->tables['decorations']." ON ".$this->tables['decorations'].".id = maintable.id_decoration
            LEFT JOIN ".$this->tables['windows']." ON ".$this->tables['windows'].".id = maintable.id_window
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
            LEFT JOIN ".$this->tables['promotions']." ON ".$this->tables['promotions'].".id = maintable.id_promotion
            LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->tables['housing_estates'].".id = maintable.id_housing_estate
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies'].".id_main_photo = ".$this->tables['agencies_photos'].".id
        "); 
        
        // build/live/commercial/country - статистика
        if(!empty($row) && !empty($this->work_table_stats_shows) ){
            $row_stats = $db->fetch("SELECT IF(sh.amount IS NULL,0,sh.amount) AS sh_amount, IF(se.amount IS NULL,0,se.amount) AS se_amount, IF(fs.amount IS NULL,0,fs.amount) AS fs_amount
                                     FROM ".$this->work_table."
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount 
                                                FROM ".$this->work_table_stats_shows." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) sh ON sh.id_parent=".$this->work_table.".id
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount
                                                FROM ".$this->work_table_stats_search." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) se ON se.id_parent=".$this->work_table.".id
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount 
                                                FROM ".$this->work_table_from_search." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) fs ON fs.id_parent=".$this->work_table.".id
                                     WHERE ".$this->work_table.".id = ".$this->data_array['id']);
            $row['search_full'] = $row_stats['se_amount'];
            $row['shows_full'] = $row_stats['sh_amount'];
            $row['from_search_full'] = $row_stats['fs_amount'];
        }
        
        if(!empty($row) ){
            $this->info_array['address']=$this->data_array['address'];
            return $this->info_array = $row;
        } 
        
        return false;
    }

    /**
    * получение ЧПУ-заголовков для объекта
    * @param boolean принудительно получать данные из БД
    * @return array массив заголовков
    */
    public function getTitles($force_load=false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->titles_array) ) return $this->titles_array;
        $row = $db->fetch("
            SELECT 
                   CONCAT(
                        'Продажа ',
                        IF(maintable.rooms_sale=0,'квартиры студии',
                            IF(maintable.rooms_sale=1,'однокомнатной квартиры',
                                IF(maintable.rooms_sale=2,'двухкомнатной квартиры',
                                    IF(maintable.rooms_sale=3,'трехкомнатной квартиры',
                                        IF(maintable.rooms_sale=4,'четырехкомнатной квартиры',
                                            IF(maintable.rooms_sale=5,'пятикомнатной квартиры',
                                                IF(maintable.rooms_sale=6,'шестикомнатной квартиры','многокомнатной квартиры')
                                                ) ))
                                )
                            )
                        ), ' в новостройке',
                        IF(maintable.txt_addr<>'', CONCAT(' - ', maintable.txt_addr, ' '), '')
                   ) as `header`
                 , CONCAT(
                        'Продажа ',
                        IF(maintable.rooms_sale=0,'квартиры студии',
                            IF(maintable.rooms_sale=1,'однокомнатной квартиры',
                                IF(maintable.rooms_sale=2,'двухкомнатной квартиры',
                                    IF(maintable.rooms_sale=3,'трехкомнатной квартиры',
                                        IF(maintable.rooms_sale=4,'четырехкомнатной квартиры',
                                            IF(maintable.rooms_sale=5,'пятикомнатной квартиры',
                                                IF(maintable.rooms_sale=6,'шестикомнатной квартиры','многокомнатной квартиры')
                                                ) ))
                                )
                            )
                        ), ' в новостройке'
                   ) as `object_type`
                 , CONCAT(
                        IF(maintable.rooms_sale=1,'одно',
                            IF(maintable.rooms_sale=2,'двух',
                                IF(maintable.rooms_sale=3,'трех','много')
                            )    
                        ), 
                        'комнатная квартира'
                   ) as `short_object_type`
                 , CONCAT(
                         'Купить ',
                        IF(maintable.rooms_sale=0,'квартиру студию',
                            IF(maintable.rooms_sale=1,'однокомнатную квартиру',
                                IF(maintable.rooms_sale=2,'двухкомнатную квартиру',
                                    IF(maintable.rooms_sale=3,'трехкомнатную квартиру',
                                        IF(maintable.rooms_sale=4,'четырехкомнатную квартиру',
                                            IF(maintable.rooms_sale=5,'пятикомнатную квартиру',
                                                IF(maintable.rooms_sale=6,'шестикомнатную квартиру','многокомнатную квартиру')
                                                ) ))
                                )
                            )
                        ),
                        IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr), ''),
                        IF(".$this->tables['building_types'].".title<>'', CONCAT(', ', lower(".$this->tables['building_types'].".title), ' дом'), ''),
                        IF(maintable.square_full > 0, CONCAT(', площадь ', maintable.square_full, ' м2 '), ''),
                        IF(maintable.level > 0, CONCAT(', ', maintable.level , ' этаж'), ''),
                        IF(maintable.object_cost > 0, CONCAT(', ', maintable.object_cost , ' руб.'), ''),
                        CONCAT(' - BSN.ru')
                        
                   ) as `title`
                 , CONCAT(
                         IF(maintable.rooms_sale > 0,
                             CONCAT(
                                 IF(maintable.rooms_sale=1,'Одно',
                                    IF(maintable.rooms_sale=2,'Двух',
                                        IF(maintable.rooms_sale=3,'Трех','Четырех')
                                    )
                                ), 'комнатная квартира'
                             ), 'Квартира-студия'
                         ),
                        ' в новостройке ',
                        IF(maintable.txt_addr<>'', CONCAT('по адресу ', maintable.txt_addr, '.'),''),
                        ' Информация об объекте: ',
                        IF(maintable.square_full > 0, CONCAT(maintable.square_full, ' м2'), ''),
                        IF(maintable.level> 0,
                                CONCAT(
                                    ', этаж ', maintable.level,
                                    IF(maintable.level_total > 0, CONCAT(' из ', maintable.level_total), '')
                                )
                                ,''
                             ),
                             IF(".$this->tables['building_types'].".title<>'', CONCAT(', ', lower(".$this->tables['building_types'].".title), ' дом'), ''),
                             IF(".$this->tables['subways'].".title<>'', CONCAT(', метро ', ".$this->tables['subways'].".title), ''),
                             IF(".$this->tables['geodata'].".offname<>'', CONCAT(', ', ".$this->tables['geodata'].".offname, ' район ЛО'), ''),
                             IF(".$this->tables['districts'].".title<>'', CONCAT(', ',".$this->tables['districts'].".title, ' район'), ''),
                             '. Полные характеристики, фотогалерея и описание инфраструктуры есть на сайте.'
                         ) as `description`
            FROM ( SELECT
                  ".$this->getField('id')." as object_id
                , ".$this->getField('published')." as published
                ,  ".$this->getField('cost')." as object_cost
                , ".$this->getField('rooms_sale')." as rooms_sale
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_district')." as id_district
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
                , '".$db->real_escape_string($this->getField('txt_addr') )."' as txt_addr
                , ".$this->getField('id_building_type')." as id_building_type
                , ".$this->getField('level')." as level
                , ".$this->getField('level_total')." as level_total
                , ROUND(".$this->getField('square_full').") as square_full
                , ROUND(".$this->getField('square_live').") as square_live                
            ) maintable
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = maintable.id_building_type
        ");
        if(empty($row) ) return false;
        return $this->titles_array = array('title'=>$row['title'], 'description'=>$row['description'], 'header'=>$row['header'], 'object_type'=>$row['object_type'], 'short_object_type'=>$row['short_object_type']);
    }
    
    /**
    * Генерация текстового описания объекта
    * 
    */
    public function getTextDescription(){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->titles_array) ) return $this->titles_array;
        $row = $db->fetch("SELECT CONCAT(
                                    'Отличный вариант для тех, кто хочет купить ',
                                    CONCAT(
                                        IF(maintable.rooms_sale=0,'квартиру-студию',
                                            CONCAT(
                                                IF(maintable.rooms_sale=1,'одно',
                                                    IF(maintable.rooms_sale=2,'двух',
                                                        IF(maintable.rooms_sale=3,'трех',
                                                            IF(maintable.rooms_sale=4,'четырех',
                                                                IF(maintable.rooms_sale=5,'пяти',
                                                                    IF(maintable.rooms_sale=6,'шести','много')
                                                                    ) ))
                                                    )
                                                ),'комнатную квартиру'
                                            )
                                        )
                                    ),
                                    ' в новостройке Санкт-Петербурга.',
                                    IF(".$this->tables['building_types'].".id IS NOT NULL,CONCAT(' ',".$this->tables['building_types'].".title),''),
                                    IF(maintable.level<>0,CONCAT(' ',maintable.level,'-этажный '),''),
                                    IF(".$this->tables['building_types'].".id IS NOT NULL OR maintable.level<>0 OR maintable.id_housing_estate!=0 OR maintable.id_build_complete!=0,'дом ',''),
                                    IF(".$this->tables['developer_statuses'].".id = 9, CONCAT(' от застройщика ',".$this->tables['agencies'].".title,' '),''),
                                    IF(maintable.id_housing_estate!=0,CONCAT('носит название ЖК ',".$this->tables['housing_estates'].".title),''),
                                    IF(maintable.id_build_complete!=0,CONCAT(', срок сдачи ',".$this->tables['build_complete'].".title),IF(maintable.build_completed=1,', дом сдан','') ),
                                    IF(maintable.id_area!=0 OR maintable.id_district!=0 OR maintable.id_subway!=0,' и располагается ',''),
                                    IF(maintable.id_area!=0,
                                       IF(".$this->tables['geodata'].".title_prepositional IS NULL,'',CONCAT('в ',".$this->tables['geodata'].".title_prepositional, ' районе ЛО ') ),
                                       IF(".$this->tables['districts'].".title IS NOT NULL, CONCAT('в ',".$this->tables['districts'].".title_prepositional, ' районе Санкт-Петербурга '), 
                                       '')
                                    ),
                                    IF(".$this->tables['subways'].".title<>'', 
                                       CONCAT('поблизости от станции метро ', ".$this->tables['subways'].".title,'.'), 
                                       '.'),
                                    ' Квартира',
                                    IF(maintable.square_full!=0,CONCAT(' площадью ',maintable.square_full,' кв.м.'),''),
                                    IF(maintable.ceiling_height!=0,CONCAT(' и высотой потолков ',maintable.ceiling_height,' м'),''),
                                    IF(maintable.level!=0,CONCAT(' на ',maintable.level,' этаже этого '),''),
                                    IF(maintable.id_housing_estate!=0,'жилого комплекса','жилого дома'),
                                    IF(maintable.id_toilet!=0 OR maintable.id_balcon!=0 OR maintable.id_window!=0,
                                        CONCAT(' имеет ',
                                                CONCAT_WS(',',
                                                          IF(maintable.id_toilet!=0,
                                                                IF(maintable.id_toilet IN (5,6,7,10,11,12,13),
                                                                   CONCAT(".$this->tables['toilets'].".title),
                                                                   IF(maintable.id_toilet IN (3,4),CONCAT(".$this->tables['toilets'].".title,' санузел'),'') ),
                                                                NULL),
                                                          IF(maintable.id_balcon!=0,CONCAT(' ',".$this->tables['balcons'].".title_genitive),NULL),
                                                          IF(maintable.id_window!=0,CONCAT(' а окна выходят ',".$this->tables['windows'].".title,''),NULL)
                                                         ),'.'
                                        ),''
                                    ),
                                    ''
                           ) AS text_description
                           FROM ( SELECT
                            ".$this->getField('id')." as object_id
                            , ".$this->getField('rent')." as rent
                            , ".$this->getField('id_user')." as id_user
                            , ".$this->getField('cost')." as object_cost
                            , ".$this->getField('published')." as published
                            , ".$this->getField('id_type_object')." as id_type_object
                            , ".$this->getField('rooms_sale')." as rooms_sale
                            , ".$this->getField('id_subway')." as id_subway
                            , ".$this->getField('id_district')." as id_district
                            , ".$this->getField('level')." as level
                            , ".$this->getField('level_total')." as level_total
                            , ".$this->getField('id_window')." as id_window
                            , ".$this->getField('id_balcon')." as id_balcon
                            , ".$this->getField('square_live')." as square_live
                            , ".$this->getField('square_full')." as square_full
                            , ".$this->getField('id_housing_estate')." as id_housing_estate
                            , ".$this->getField('id_build_complete')." as id_build_complete
                            , ".$this->getField('id_building_type')." as id_building_type
                            , ".$this->getField('build_completed')." as build_completed
                            , ".$this->getField('id_area')." as id_area
                            , ".$this->getField('id_region')." as id_region
                            , ".$this->getField('ceiling_height')." as ceiling_height
                            , ".$this->getField('id_toilet')." as id_toilet
                            , ".$this->getField('id_developer_status')." as id_developer_status
                            , '".$db->real_escape_string($this->getField('txt_addr') )."' as txt_addr
                           ) maintable
                           LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = maintable.id_building_type
                           LEFT JOIN ".$this->tables['build_complete']." ON ".$this->tables['build_complete'].".id = maintable.id_build_complete
                           LEFT JOIN ".$this->tables['developer_statuses']." ON ".$this->tables['developer_statuses'].".id = maintable.id_developer_status
                           LEFT JOIN ".$this->tables['windows']." ON ".$this->tables['windows'].".id = maintable.id_window
                           LEFT JOIN ".$this->tables['toilets']." ON ".$this->tables['toilets'].".id = maintable.id_toilet
                           LEFT JOIN ".$this->tables['balcons']." ON ".$this->tables['balcons'].".id = maintable.id_balcon
                           LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
                           LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
                           LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->tables['housing_estates'].".id = maintable.id_housing_estate
                           LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND 
                                                                     ".$this->tables['geodata'].".id_region = maintable.id_region AND 
                                                                     ".$this->tables['geodata'].".id_area = maintable.id_area
                           LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
                           LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id");
        if(empty($row) ) return false;
        return $row['text_description'];
    }
    
    public function getComplexCoord(){
        return parent::getCoordFromComplex("build");
    }
}




/*******************************************************************************************************************
* Класс для работы с единичным объектом рынка загородных объектов
*******************************************************************************************************************/
class EstateItemCountry extends EstateItem{
    private $custom_data_array = [];
    private $custom_hash_fields  =  [];
    public function __construct($id=null, $from_new=false){
        parent::__construct(TYPE_ESTATE_COUNTRY, $id, $from_new);
    }

    /**
    * получение информации из необходимых справочников
    * @param boolean принудительно получать данные из БД
    * @return array информация или FALSE если ошибка
    */
    public function getInfo($force_load = false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->info_array) ) return $this->info_array;
        $row = $db->fetch("
            SELECT maintable.id
                , ".$this->tables['users'].".name as user_name
                , ".$this->tables['users'].".lastname as user_lastname
                , ".$this->tables['users'].".phone as user_phone
                , ".$this->tables['users'].".email as user_email
                , ".$this->tables['users'].".balance as user_balance
                , ".$this->tables['users'].".id_tarif as user_tarif
                , ".$this->tables['agencies'].".title as agency_title
                , ".$this->tables['agencies'].".chpu_title as agency_chpu_title
                , ".$this->tables['agencies'].".activity & 2 as agency_advert
                , ".$this->tables['agencies'].".phone_1 as agency_phone_1
                , ".$this->tables['agencies'].".phone_2 as agency_phone_2
                , ".$this->tables['agencies'].".phone_3 as agency_phone_3
                , ".$this->tables['agencies'].".advert_phone as agency_advert_phone
                , ".$this->tables['agencies'].".advert_phone_objects as agency_advert_phone_objects
                , ".$this->tables['agencies'].".call_cost as agency_call_cost
                , ".$this->tables['agencies'].".activity as agency_activity
                , ".$this->tables['agencies'].".email as agency_email
                , ".$this->tables['agencies_photos'].".name as agency_photo
                , LEFT ( ".$this->tables['agencies_photos'].".name, 2) as agency_subfolder_photo
                 , ".$this->tables['agencies'].".url as agency_url , ".$this->tables['agencies'].".advert as agency_advert
                , ".$this->tables['agencies'].".doverie_years as doverie_years
                , ".$this->tables['cottages'].".title as cottage   
                , ".$this->tables['cottages'].".chpu_title as cottage_chpu
                , ".$this->tables['type_objects_country'].".title as type_object
                , ".$this->tables['type_objects_country'].".id_group as type_id_group
                , ".$this->tables['geodata'].".offname as district_area
                , ".$this->tables['subways'].".title as subway
                , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                , ".$this->tables['subway_lines'].".color as `subway_color`
                , ".$this->tables['way_types'].".title as way_type
                , ".$this->tables['ownerships'].".title as ownership
                , ".$this->tables['construct_materials'].".title as construct_material
                , ".$this->tables['heatings'].".title as heating
                , ".$this->tables['roof_materials'].".title as roof_material
                , ".$this->tables['electricities'].".title as electricity
                , ".$this->tables['water_supplies'].".title as water_supply
                , ".$this->tables['toilets_country'].".title as toilet
                , ".$this->tables['rivers'].".title as river
                , ".$this->tables['gases'].".title as gas
                , ".$this->tables['building_progresses'].".title as building_progress
                , ".$this->tables['gardens'].".title as garden
                , ".$this->tables['bathrooms'].".title as bathroom
                , ".$this->tables['promotions'].".title AS promotion_title
                , ".$this->tables['promotions'].".chpu_title AS promotion_chpu
                , ".$this->tables['promotions'].".date_end AS promotion_date_end
                , ".$this->tables['promotions'].".discount_type, ".$this->tables['agencies'].".advert as `agency_advert`
                , ".$this->tables['promotions'].".discount
                , ".$this->tables['owners_user_types'].".title AS user_type_title
                , ".$this->tables['work_statuses'].".title AS work_status_title
                , DATE_FORMAT(".$this->tables['promotions'].".date_end,'%d.%m.%y') AS promotion_date_end
            FROM ( SELECT
                  ".$this->getField('id')." as id
                , ".$this->getField('id_user')." as id_user
                , ".$this->getField('id_type_object')." as id_type_object
                , IFNULL(".$this->getField('id_promotion').",0) as id_promotion
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_way_type')." as id_way_type
                , ".$this->getField('id_ownership')." as id_ownership
                , ".$this->getField('id_construct_material')." as id_construct_material
                , ".$this->getField('id_heating')." as id_heating
                , ".$this->getField('id_roof_material')." as id_roof_material
                , ".$this->getField('id_electricity')." as id_electricity
                , ".$this->getField('id_water_supply')." as id_water_supply
                , ".$this->getField('id_toilet')." as id_toilet
                , ".$this->getField('id_river')." as id_river
                , ".$this->getField('id_gas')." as id_gas
                , ".$this->getField('id_building_progress')." as id_building_progress
                , ".$this->getField('id_garden')." as id_garden
                , ".$this->getField('id_bathroom')." as id_bathroom
                , ".$this->getField('id_cottage')." as id_cottage
                , ".$this->getField('id_user_type')." as id_user_type
                , ".$this->getField('id_work_status')." as id_work_status
            ) maintable
            LEFT JOIN ".$this->tables['owners_user_types']." ON ".$this->tables['owners_user_types'].".id = maintable.id_user_type
            LEFT JOIN ".$this->tables['work_statuses']." ON ".$this->tables['work_statuses'].".id = maintable.id_work_status
            LEFT JOIN ".$this->tables['type_objects_country']." ON ".$this->tables['type_objects_country'].".id = maintable.id_type_object
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['ownerships']." ON ".$this->tables['ownerships'].".id = maintable.id_ownership
            LEFT JOIN ".$this->tables['construct_materials']." ON ".$this->tables['construct_materials'].".id = maintable.id_construct_material
            LEFT JOIN ".$this->tables['heatings']." ON ".$this->tables['heatings'].".id = maintable.id_heating
            LEFT JOIN ".$this->tables['roof_materials']." ON ".$this->tables['roof_materials'].".id = maintable.id_roof_material
            LEFT JOIN ".$this->tables['electricities']." ON ".$this->tables['electricities'].".id = maintable.id_electricity
            LEFT JOIN ".$this->tables['water_supplies']." ON ".$this->tables['water_supplies'].".id = maintable.id_water_supply
            LEFT JOIN ".$this->tables['toilets_country']." ON ".$this->tables['toilets_country'].".id = maintable.id_toilet
            LEFT JOIN ".$this->tables['rivers']." ON ".$this->tables['rivers'].".id = maintable.id_river
            LEFT JOIN ".$this->tables['gases']." ON ".$this->tables['gases'].".id = maintable.id_gas
            LEFT JOIN ".$this->tables['gardens']." ON ".$this->tables['gardens'].".id = maintable.id_garden
            LEFT JOIN ".$this->tables['bathrooms']." ON ".$this->tables['bathrooms'].".id = maintable.id_bathroom
            LEFT JOIN ".$this->tables['cottages']." ON ".$this->tables['cottages'].".id = maintable.id_cottage
            LEFT JOIN ".$this->tables['building_progresses']." ON ".$this->tables['building_progresses'].".id = maintable.id_building_progress
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
            LEFT JOIN ".$this->tables['promotions']." ON ".$this->tables['promotions'].".id = maintable.id_promotion
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies'].".id_main_photo = ".$this->tables['agencies_photos'].".id
        ");
        
        // build/live/commercial/country - статистика
        if(!empty($row) && !empty($this->work_table_stats_shows) ){
            $row_stats = $db->fetch("SELECT IF(sh.amount IS NULL,0,sh.amount) AS sh_amount, IF(se.amount IS NULL,0,se.amount) AS se_amount, IF(fs.amount IS NULL,0,fs.amount) AS fs_amount
                                     FROM ".$this->work_table."
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount 
                                                FROM ".$this->work_table_stats_shows." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) sh ON sh.id_parent=".$this->work_table.".id
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount
                                                FROM ".$this->work_table_stats_search." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) se ON se.id_parent=".$this->work_table.".id
                                     LEFT JOIN (SELECT id_parent,SUM(amount) AS amount 
                                                FROM ".$this->work_table_from_search." 
                                                WHERE id_parent=".$this->data_array['id']." 
                                                GROUP BY id_parent) fs ON fs.id_parent=".$this->work_table.".id
                                     WHERE ".$this->work_table.".id = ".$this->data_array['id']);
            $row['search_full'] = $row_stats['se_amount'];
            $row['shows_full'] = $row_stats['sh_amount'];
            $row['from_search_full'] = $row_stats['fs_amount'];
        }
        
        if(!empty($row) ) return $this->info_array = $row;
        return false;
    }
    
    /**
    * получение ЧПУ-заголовков для объекта
    * @param boolean принудительно получать данные из БД
    * @return array массив заголовков
    */
    public function getTitles($force_load=false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->titles_array) ) return $this->titles_array;
        //TODO:Мише: убрать DISTRICT_AREAS:DONE
        $row = $db->fetch("
            SELECT 
                   CONCAT(
                        IF(maintable.rent=1,'Аренда ','Продажа '),
                        ".$this->tables['type_objects_country'].".`title_genitive`,
                        IF(maintable.txt_addr<>'', CONCAT(' - ', maintable.txt_addr), '')
                   ) as `header`
                 , CONCAT(
                        IF(maintable.rent=1,'Аренда ','Продажа '),
                        ".$this->tables['type_objects_country'].".`title_genitive`
                   ) as `object_type`
                 , CONCAT(
                        ".$this->tables['type_objects_country'].".`title`
                   ) as `short_object_type`
                 , IF(maintable.rent=2,
                        CONCAT(
                            'Купить ',
                            ".$this->tables['type_objects_country'].".`title`,
                            IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr, ' '), '')
                        ),

                        CONCAT('Снять ',
                                  ".$this->tables['type_objects_country'].".`title`,
                                  IF(maintable.txt_addr<>'', CONCAT(' по адресу ',maintable.txt_addr,' '), '')
                         )
                    ) as `title`
                 , 
                 CONCAT(
                   ".$this->tables['type_objects_country'].".`title`,
                   IF(maintable.txt_addr<>'', CONCAT(' по адресу ', maintable.txt_addr, '.'), ''),
                   '. Информация об объекте: ',
                 IF(maintable.square_live > 0, CONCAT( 'дом ', maintable.square_live, ' м2'), '' ) ,
                 IF(maintable.square_ground > 0, CONCAT( 
                        IF(maintable.square_live = 0, '', ', '),
                        'участок ', maintable.square_ground, ' сот.'
                    ), '' 
                 ) ,
                 IF(".$this->tables['subways'].".title<>'', CONCAT(', метро ', ".$this->tables['subways'].".title), ''),
                 IF(".$this->tables['geodata'].".offname<>'', CONCAT(', ', ".$this->tables['geodata'].".offname, ' район ЛО'), ''),
                 IF(".$this->tables['districts'].".title<>'', CONCAT(', ',".$this->tables['districts'].".title, ' район'), ''),
                 '. Полные характеристики, фотогалерея и описание инфраструктуры есть на сайте.'
                   
                 ) as `description`
            FROM ( SELECT
                ".$this->getField('id')." as object_id
                , ".$this->getField('rent')." as rent
                , ".$this->getField('published')." as published
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_district')." as id_district
                , ROUND(".$this->getField('square_live').") as square_live
                , ROUND(".$this->getField('square_ground').") as square_ground
                , ".$this->getField('id_type_object')." as id_type_object
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
                , ".$this->getField('cost')." as object_cost   
                , '".$db->real_escape_string($this->getField('txt_addr') )."' as txt_addr
            ) maintable
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['type_objects_country']." ON ".$this->tables['type_objects_country'].".id = maintable.id_type_object
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
        "); 
        
        $row['description'] = preg_replace('/\.\./','.',$row['description']);
        
        if(empty($row) ) return false;
        return $this->titles_array = array('title'=>$row['title'],'description'=>$row['description'], 'header'=>$row['header'], 'object_type'=>$row['object_type'], 'short_object_type'=>$row['short_object_type']);
    }
    
    public function getComplexCoord(){
        return parent::getCoordFromComplex("country");
    }
}




/*******************************************************************************************************************
* Класс для работы с единичным объектом рынка загородных объектов
*******************************************************************************************************************/
class EstateItemInter extends EstateItem{
    private $custom_data_array = [];
    private $custom_hash_fields  =  [];
    public function __construct($id=null, $from_new=false){
        parent::__construct(TYPE_ESTATE_INTER, $id, $from_new);
    }

    /**
    * получение информации из необходимых справочников
    * @param boolean принудительно получать данные из БД
    * @return array информация или FALSE если ошибка
    */
    public function getInfo($force_load = false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->info_array) ) return $this->info_array;

        $sql = "SELECT ".$this->work_table.".* 
                        , ".$this->tables['inter_countries_flags_photos'].".`name` as `country_photo`, LEFT (".$this->tables['inter_countries_flags_photos'].".`name`,2) as `country_subfolder`
                        , ".$this->tables['inter_regions'].".title as region_title
                        , ".$this->tables['inter_currencies'].".title as currency_title
                        , ".$this->tables['inter_countries'].".title as country_title                    
                        , ".$this->tables['inter_countries'].".title_genitive as country_title_genitive                    
                        , ".$this->tables['inter_type_objects'].".title as type_object                   
                        , ".$this->tables['inter_type_objects'].".title_genitive as type_object_genitive                    
                        , ".$this->tables['inter_cost_types'].".title as cost_type_title                    
                        , ".$this->tables['inter_cost_types'].".title_short as cost_type_title_short
                        , ".$this->tables['inter_managers'].".title as seller_name                    
                        , ".$this->tables['inter_managers'].".phone as seller_phone                    
                        , ".$this->tables['inter_managers'].".email as seller_mail                    
                        , ".$this->tables['inter_managers'].".skype as manager_skype                    
                FROM ".$this->work_table."
                LEFT JOIN ".$this->tables['inter_countries']." ON ".$this->tables['inter_countries'].".id = ".$this->work_table.".id_country
                LEFT JOIN ".$this->tables['inter_countries_flags_photos']." ON ".$this->tables['inter_countries_flags_photos'].".id_parent = ".$this->tables['inter_countries'].".id
                LEFT JOIN ".$this->tables['inter_regions']." ON ".$this->tables['inter_regions'].".id = ".$this->work_table.".id_region
                LEFT JOIN ".$this->tables['inter_cost_types']." ON ".$this->tables['inter_cost_types'].".id = ".$this->work_table.".id_cost_type
                LEFT JOIN ".$this->tables['inter_type_objects']." ON ".$this->tables['inter_type_objects'].".id = ".$this->work_table.".id_type_object
                LEFT JOIN ".$this->tables['inter_currencies']." ON ".$this->tables['inter_currencies'].".id = ".$this->work_table.".id_currency
                LEFT JOIN ".$this->tables['inter_managers']." ON ".$this->tables['inter_managers'].".id = ".$this->work_table.".id_manager
                WHERE ".$this->work_table.".id = ?
                GROUP BY ".$this->work_table.".id ";
        $item = $db->fetch($sql, $this->data_array['id']);  
        return $item;
    }
    
    /**
    * получение ЧПУ-заголовков для объекта
    * @param boolean принудительно получать данные из БД
    * @return array массив заголовков
    */
    public function getTitles($item){
        $deal_type = $item['rent'] == 1 ? 'Аренда' : 'Продажа';
        $title = $deal_type." ".$item['type_object_genitive']." в ".$item['country_title_genitive'].", ".$item['address']." ";
        $description = $item['type_object']." в ".$item['country_title_genitive'].", ".$item['address'] . '. Полные характеристики, фотогалерея и описание инфраструктуры есть на сайте.';
        $keywords = $deal_type.", ".$item['type_object'].", ".$item['country_title'].", ".$item['address']."";
        $h1 = $deal_type." ".$item['type_object_genitive']." - ".$item['country_title'].", ".$item['address'];
        return $this->titles_array = array('title'=>$title, 'description'=>$description, 'keywords'=>$keywords, 'h1'=>$h1, 'header'=>$h1);
    }
    
}


/*******************************************************************************************************************
* Класс для работы с единичным объектом рынка - жилые комплексы
*******************************************************************************************************************/
class EstateItemHousingEstates extends EstateItem{
    private $custom_data_array = [];
    private $custom_hash_fields  =  [];
    public function __construct($id=null, $from_new=false){
        parent::__construct(TYPE_ESTATE_HOUSING_ESTATES, $id, $from_new);
    }

    /**
    * получение информации из необходимых справочников
    * @param boolean принудительно получать данные из БД
    * @return array информация или FALSE если ошибка
    */
    public function getInfo($force_load = false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->info_array) ) return $this->info_array;
        $row = $db->fetch("
            SELECT 
                ".$this->tables['districts'].".title as district
                , ".$this->tables['housing_estate_developers'].".title as developer
                , ".$this->tables['geodata'].".offname as `district_area`
                , ".$this->tables['subways'].".title as subway
                , ".$this->tables['way_types'].".title as way_type
            FROM ( SELECT
                ".$this->getField('id_district')." as id_district
                ,".$this->getField('id_developer')." as id_developer
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_way_type')." as id_way_type
                , ".$this->getField('id_region')." as id_region
                , ".$this->getField('id_area')." as id_area
            ) maintable
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['housing_estate_developers']." ON ".$this->tables['housing_estate_developers'].".id = maintable.id_developer
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id = maintable.id_way_type
            LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = maintable.id_region AND ".$this->tables['geodata'].".id_area = maintable.id_area
        ");
        if(!empty($row) ){
            $this->info_array['address']=$this->data_array['address'];
            return $this->info_array = $row;
        } 
        return false;
    }

    /**
    * получение ЧПУ-заголовков для объекта
    * @param boolean принудительно получать данные из БД
    * @return array массив заголовков
    */
    public function getTitles($force_load=false){
        global $db;
        if(empty($this->data_loaded) ) return false;
        if(empty($force_load) && !empty($this->titles_array) ) return $this->titles_array;
        $row = $db->fetch("
            SELECT 
                   CONCAT(
                        'Жилой комплекс ',
                        maintable.title
                   ) as `header`
                 , CONCAT(
                        'ЖК ',
                        '«',maintable.title,'»',
                        IF(".$this->tables['subways'].".title<>'', CONCAT(' у метро ', ".$this->tables['subways'].".title,' '), ''),
                        IF(".$this->tables['agencies'].".title<>'', CONCAT('от застройщика ',".$this->tables['agencies'].".title,'.'),''),
                        'Жилой комплекс ',
                        '«',maintable.title,'» на карте Санкт-Петербурга'
                   ) as `title`
                 , CONCAT(
                        'ЖК ',
                        '«',maintable.title,'»',
                        IF(".$this->tables['agencies'].".title<>'', CONCAT(' - проект застройщика ',".$this->tables['agencies'].".title,' '),''),
                        IF(".$this->tables['districts'].".title<>'', CONCAT('в ',".$this->tables['districts'].".title_prepositional, ' районе Санкт-Петербурга. '), ''),
                        IF(".$this->tables['class'].".name<>'',
                           CONCAT('«',maintable.title,'»',
                                  ".$this->tables['class'].".name,'-класса',
                                  IF(".$this->tables['subways'].".title<>'', CONCAT(' у метро ', ".$this->tables['subways'].".title,'. '), '. ')
                           ),
                           IF(".$this->tables['subways'].".title<>'', CONCAT('«',maintable.title,'»',' у метро ', ".$this->tables['subways'].".title,'. '), '')
                        )
                   ) as `description`
                   
            FROM ( SELECT
                  '".$this->getField('title')."' as title
                , '".$this->getField('developer')."' as developer
                , '".$this->getField('class')."' as class
                , ".$this->getField('id_subway')." as id_subway
                , ".$this->getField('id_district')." as id_district
                , '".$db->real_escape_string($this->getField('txt_addr') )."' as txt_addr
            ) maintable
            LEFT JOIN ".$this->tables['housing_estate_classes']." ON ".$this->tables['housing_estate_classes'].".id = maintable.class
            LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = maintable.id_district
            LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = maintable.id_subway
            LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = maintable.id_user
            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
            LEFT JOIN ".$this->tables['agencies_photos']." ON ".$this->tables['agencies'].".id_main_photo = ".$this->tables['agencies_photos'].".id
        ");
        if(empty($row) ) return false;
        return $this->titles_array = array('title'=>$row['title'],'description'=>$row['description'], 'header'=>$row['header'], 'object_type'=>$row['header']);
    }
}

/*******************************************************************************************************************
* Класс для работы со списками объектов рынка жилой недвижимости
*******************************************************************************************************************/
class EstateListLive extends EstateList{
    public function __construct(){
        parent::__construct(TYPE_ESTATE_LIVE);        
    }
    
    /**
    * Поиск объектов по заданным параметрам
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    */
    public function Search($clauses, $count=20, $from=0, $orderby='', $groupby='',$housing_estate=false){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = (!empty($orderby)?$orderby:"");
        
        $res = [];
        if(!empty($groupby) ){
            list($ids, $housing_estate_ids) = $this->getIdsList($where, $count, $from, $order, $groupby, true, $housing_estate);
        }
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT STRAIGHT_JOIN ".$this->work_table.".*
                             , DATE_FORMAT(". $this->work_table .".`date_change`,'%e.%m.%Y') as date_change_normal
                             , DATE_FORMAT(". $this->work_table .".`date_in`,'%e.%m.%Y') as date_in_normal
                             , DATEDIFF(". $this->work_table .".`date_change`, NOW() - INTERVAL 30 DAY) as days_left
                             , IF( ".$this->work_table.".date_change + INTERVAL 30 day >= ".$this->work_table.".status_date_end, 
                                    DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 30 day,'%d.%m.%y'), 
                                    DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y')
                               ) AS `formatted_date_end`
                             , ".$this->work_photos_table.".`name` as `photo` 
                             , LEFT (".$this->work_photos_table.".`name`,2) as `subfolder` 
                             , ".$this->tables['housing_estates_photos'].".`name` as `complex_photo` 
                             , LEFT (".$this->tables['housing_estates_photos'].".`name`,2) as `complex_subfolder` 
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                             , ".$this->tables['subway_lines'].".color as `subway_color`
                             , ".$this->tables['districts'].".title as `district`
                             , ".$this->tables['users'].".id_agency as `id_agency`
                             , ".$this->tables['geodata'].".offname as `district_area`
                             , ".$this->tables['facings'].".title as `facing_title`
                             , ".$this->tables['balcons'].".title as `balcon_title`
                             , ".$this->tables['toilets'].".title as `toilet_title`
                             , ".$this->tables['elevators'].".title as `elevator_title`
                             , ".$this->tables['building_types'].".title as `building_type_title`
                             , ".$this->tables['type_objects_live'].".title as `type_object`
                             , ".$this->tables['type_objects_live'].".short_title as `type_object_short`
                             , ".$this->tables['way_types'].".title as `way_type_title`
                             , CONCAT(
                                    IF(".$this->work_table.".rent=1,'Аренда ','Продажа '),
                                    IF(".$this->tables['type_objects_live'].".id=1,
                                        CONCAT(
                                            IF(".$this->work_table.".rooms_total=1,'одно',
                                                IF(".$this->work_table.".rooms_total=2,'двух',
                                                    IF(".$this->work_table.".rooms_total=3,'трех','много')
                                                )    
                                            ), 
                                            'комнатной '
                                        ),' '
                                    ),
                                    ".$this->tables['type_objects_live'].".`title_genitive`,
                                    ', '
                               ) as `header`
                             , 
                             IF(".$this->tables['type_objects_live'].".id=1,
                                IF(".$this->work_table.".rooms_total=0, 'квартира-студия',CONCAT(".$this->work_table.".rooms_total,'-к. квартира') ),
                                IF(".$this->work_table.".id_type_object=2,
                                    CONCAT(IF(".$this->work_table.".rooms_sale>1, CONCAT(".$this->work_table.".rooms_sale, ' комнат'), 'комната'),' в ',".$this->work_table.".rooms_total,'-ккв'),
                                    ".$this->tables['type_objects_live'].".title
                                )    
                             )  as `obj_type`
                             , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent=".$this->work_table.".id) AS photos_count
                             , (SELECT COUNT(*) FROM ".$this->work_videos_table." WHERE id_parent=".$this->work_table.".id AND status = 3) AS videos_count
                             , ".$this->tables['promotions'].".discount_type, ".$this->tables['agencies'].".advert as `agency_advert`
                             , ".$this->tables['promotions'].".discount
                             , ".$this->tables['housing_estates'].".title as `housing_estate_title`
                             , ".$this->tables['housing_estates'].".chpu_title as `housing_estate_chpu_title`
                             
                      FROM ".$this->work_table."
                      LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->work_table.".id_housing_estate = ".$this->tables['housing_estates'].".id
                      LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->work_table.".id_facing
                      LEFT JOIN ".$this->tables['balcons']." ON ".$this->tables['balcons'].".id = ".$this->work_table.".id_balcon
                      LEFT JOIN ".$this->tables['toilets']." ON ".$this->tables['toilets'].".id = ".$this->work_table.".id_toilet
                      LEFT JOIN ".$this->tables['elevators']." ON ".$this->tables['elevators'].".id = ".$this->work_table.".id_elevator
                      LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->work_table.".id_way_type
                      LEFT JOIN ".$this->tables['objects_statuses']." ON ".$this->tables['objects_statuses'].".id = ".$this->work_table.".status
                      LEFT JOIN ".$this->tables['type_objects_live']." ON ".$this->tables['type_objects_live'].".id = ".$this->work_table.".id_type_object
                      LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = ".$this->work_table.".id_building_type
                      LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->work_table.".id_district
                      LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                      LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
                      LEFT JOIN ".$this->tables['promotions']." ON ".$this->tables['promotions'].".id = ".$this->work_table.".id_promotion
                      LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area
                      LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->work_table.".id_user
                             LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id
                      
                      LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                      LEFT JOIN ".$this->tables['housing_estates_photos']." ON ".$this->tables['housing_estates'].".id_main_photo = ".$this->tables['housing_estates_photos'].".id
                      ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" 
                                          : (empty($where)?"":"WHERE ".$where) )."
                      ".(!empty($order) ? "ORDER BY ".$order : "" )."
                      LIMIT ".$from.",".$count;
                      
            $res = $db->fetchall($sql);
            //читаем информацию по ЖК
            if(!empty($housing_estate_ids) ){
                $he_info = $db->fetchall("SELECT ".$this->tables['housing_estates'].".id,
                                                 ".$this->tables['housing_estates_photos'].".name as `photo`,
                                                 LEFT(".$this->tables['housing_estates_photos'].".`name`,2) as `subfolder`,
                                                 ".$this->tables['housing_estates'].".title as `housing_estate_title`, 
                                                 ".$this->tables['housing_estates'].".chpu_title as `housing_estate_chpu_title`
                                          FROM ".$this->tables['housing_estates']." 
                                          LEFT JOIN ".$this->tables['housing_estates_photos']." ON ".$this->tables['housing_estates'].".id_main_photo = ".$this->tables['housing_estates_photos'].".id
                                          WHERE ".$this->tables['housing_estates'].".id IN (".implode(',',$housing_estate_ids).")","id");
            }
            foreach($res as $k=>$item) {
                $res[$k]['photos'] = Photos::getList( 'live', $item['id'], false, false, 5 );
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                if(strstr($res[$k]['txt_addr'],'Санкт-Петербург')!='' || strstr($res[$k]['txt_addr'],'Ленинградская область')!='') $res[$k]['full_address'] = $res[$k]['txt_addr'];
                else  $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];
                
                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district'])? ', '.$item['district'].' район':'').(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
                if(!empty($groupby) && !empty($item['group_id']) && $item['group_id']>0 &&  $item['raising_status'] != 1 ){
                    $objects = $db->fetch("SELECT 
                                                COUNT(*) as total_variants, 
                                                MIN(cost) as min_cost_objects, 
                                                MAX(cost) as max_cost_objects,
                                                MIN(square_full) as square_full_min
                                           FROM ".$this->work_table." 
                                           WHERE ".$where." AND group_id = ? AND rooms_sale = ? AND id != ?".(!empty($item['id_housing_estate']) ? " AND id_housing_estate = ".$item['id_housing_estate'] : ""), $item['group_id'], $item['rooms_sale'], $item['id']
                    );
                    if(!empty($objects) && $objects['total_variants'] > 1) {
                        $res[$k] = array_merge($res[$k], $objects);
                    }
                }
                $res[$k]['txt_addr_shortened'] = mb_substr($res[$k]['txt_addr'],0,81,'UTF-8');
            }
        }
        return $res;
    }
    
    /**
    * Поиск объектов по заданным параметрам для личного кабинета
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    */
    public function SearchLK($clauses, $count=20, $from=0, $orderby='', $groupby=''){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = (!empty($orderby)?$orderby:"");
        $res = [];
        if(!empty($groupby) ){
            $ids = $this->getIdsList($where, $count, $from, $order, $groupby);
        }
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT  ".$this->work_table.".*
                             , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['districts'].".title as `district`
                             , ".$this->tables['agencies'].".id as `id_agency`
                             , ".$this->tables['geodata'].".offname as `district_area`
                             , ".$this->tables['facings'].".title as `facing_title`
                             , ".$this->tables['balcons'].".title as `balcon_title`
                             , ".$this->tables['toilets'].".title as `toilet_title`
                             , ".$this->tables['elevators'].".title as `elevator_title`
                             , ".$this->tables['building_types'].".title as `building_type_title`
                             , ".$this->tables['type_objects_live'].".title as `type_object`
                             , ".$this->tables['type_objects_live'].".short_title as `type_object_short`
                             , ".$this->tables['way_types'].".title as `way_type_title`
                             , DATE_FORMAT(".$this->work_table.".date_change,'%d.%m.%y') AS `formatted_date_in`
                             , DATE_FORMAT(".$this->work_table.".date_change,'%d.%m.%y') as `normal_date_begin`
                             , DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y') as `normal_status_date_end`
                             , ".$this->work_table.".date_change + INTERVAL 30 day AS `date_end`
                             , IF( ".$this->work_table.".date_change + INTERVAL 30 day >= ".$this->work_table.".status_date_end, DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 30 day,'%d.%m.%y'), DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y') ) AS `formatted_date_end`
                             , DATE_FORMAT(".$this->work_table.".status_date_end,'%d %M')  as `status_end`
                             , CONCAT(
                                    IF(".$this->work_table.".rent=1,'Аренда ','Продажа '),
                                    IF(".$this->tables['type_objects_live'].".id=1,
                                        CONCAT(
                                            IF(".$this->work_table.".rooms_total=1,'одно',
                                                IF(".$this->work_table.".rooms_total=2,'двух',
                                                    IF(".$this->work_table.".rooms_total=3,'трех','много')
                                                )    
                                            ), 
                                            'комнатной '
                                        ),' '
                                    ),
                                    ".$this->tables['type_objects_live'].".`title_genitive`,
                                    ', '
                               ) as `header`
                             , 
                             IF(".$this->tables['type_objects_live'].".id=1,
                                IF(".$this->work_table.".rooms_total=0, 'квартира-студия',CONCAT(".$this->work_table.".rooms_total,'-к. квартира') ),
                                IF(".$this->work_table.".id_type_object=2,
                                    CONCAT(IF(".$this->work_table.".rooms_sale>1, CONCAT(".$this->work_table.".rooms_sale, ' комнат'), 'комната'),' в ',".$this->work_table.".rooms_total,'-ккв'),
                                    ".$this->tables['type_objects_live'].".title
                                )    
                             )  as `obj_type`
                             , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent=".$this->work_table.".id) AS photos_count
                             , (SELECT COUNT(*) FROM ".$this->work_videos_table." WHERE id_parent=".$this->work_table.".id AND status = 3) AS videos_count
                      FROM ".$this->work_table."
                      LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->work_table.".id_facing
                      LEFT JOIN ".$this->tables['balcons']." ON ".$this->tables['balcons'].".id = ".$this->work_table.".id_balcon
                      LEFT JOIN ".$this->tables['toilets']." ON ".$this->tables['toilets'].".id = ".$this->work_table.".id_toilet
                      LEFT JOIN ".$this->tables['elevators']." ON ".$this->tables['elevators'].".id = ".$this->work_table.".id_elevator
                      LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->work_table.".id_way_type
                      LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = ".$this->work_table.".id_building_type
                      LEFT JOIN ".$this->tables['objects_statuses']." ON ".$this->tables['objects_statuses'].".id = ".$this->work_table.".status
                      LEFT JOIN ".$this->tables['type_objects_live']." ON ".$this->tables['type_objects_live'].".id = ".$this->work_table.".id_type_object
                      LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->work_table.".id_district
                      LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                      LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->work_table.".id_user
                      LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area
                      LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                      LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                      ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" 
                                          : (empty($where)?"":"WHERE ".$where) )."
                      ".(!empty($order) ? "ORDER BY ".$order : "" )."
                      LIMIT ".$from.",".$count;
                      
            $res = $db->fetchall($sql);
            foreach($res as $k=>$item) {
                $res[$k]['photos'] = Photos::getList( 'live', $item['id'], false, false, 5 );
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                if(strstr($res[$k]['txt_addr'],'Санкт-Петербург')!='' || strstr($res[$k]['txt_addr'],'Ленинградская область')!='') $res[$k]['full_address'] = $res[$k]['txt_addr'];
                else  $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];

                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district'])? ', '.$item['district'].' район':'').(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
                $res[$k]['txt_addr_shortened'] = mb_substr($res[$k]['txt_addr'],0,81,'UTF-8');
            }
        }
        return $res;
    }
    /**
    * Поиск объектов по карте
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * return array of array
    */
    public function SearchMap($clauses){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $sql = "SELECT   
                                COUNT(".$this->work_table.".id) as total_objects
                                , ".$this->work_table.".id
                                , ".$this->work_table.".lat
                                , ".$this->work_table.".lng
                                , ".$this->work_table.".txt_addr
                                , ".$this->work_table.".group_id
                                , ".$this->tables['housing_estates'].".title as `housing_estate_title`
                                , ".$this->tables['housing_estates'].".chpu_title as `housing_estate_chpu_title`
                             FROM ".$this->work_table." USE INDEX(map_search)
                             LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->work_table.".id_housing_estate = ".$this->tables['housing_estates'].".id
                             WHERE " . $where ." AND ".$this->work_table.".group_id > 0 
                             GROUP BY ".$this->work_table.".group_id
                             LIMIT 0, " . $this->results_on_map;
        $res = $db->fetchall($sql);  
        return $res;
    }    
    
    /**
    * Поиск объектов по заданным параметрам для бота Telegram
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    */
    public function SearchTelegram($clauses, $count=20, $from=0, $orderby='', $groupby='',$housing_estate=false){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = (!empty($orderby)?$orderby:"");
        
        $res = [];
        if(!empty($groupby) ){
            list($ids, $housing_estate_ids) = $this->getIdsList($where, $count, $from, $order, $groupby, true, $housing_estate);
        }
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT STRAIGHT_JOIN ".$this->work_table.".id
                             , (".$this->work_table.".by_the_day = 1) AS by_day
                             , ".$this->work_table.".rent
                             , ".$this->work_table.".cost
                             , ".$this->work_table.".seller_phone
                             , ".$this->work_table.".seller_name
                             , ".$this->tables['agencies'].".title AS `agency_title`
                             , ".$this->work_table.".id_region
                             , ".$this->work_table.".id_area
                             , ".$this->work_table.".id_city
                             , ".$this->work_table.".id_place
                             , ".$this->work_table.".id_street
                             , ".$this->work_table.".house
                             , ".$this->work_table.".corp
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['users'].".id_agency as `id_agency`
                             , ".$this->tables['geodata'].".offname as `district_area`
                             , ".$this->tables['type_objects_live'].".title as `type_object`
                             , ".$this->tables['way_types'].".title as `way_type_title`
                             , CONCAT(
                                    IF(".$this->work_table.".rent=1,'Аренда ','Продажа '),
                                    IF(".$this->tables['type_objects_live'].".id=1,
                                        CONCAT(
                                            IF(".$this->work_table.".rooms_total=1,'одно',
                                                IF(".$this->work_table.".rooms_total=2,'двух',
                                                    IF(".$this->work_table.".rooms_total=3,'трех','много')
                                                )    
                                            ), 
                                            'комнатной '
                                        ),' '
                                    ),
                                    ".$this->tables['type_objects_live'].".`title_genitive`,
                                    ', '
                               ) as `header`
                             , 
                             CONCAT('https://www.bsn.ru/live/',IF(".$this->work_table.".rent = 1,'rent','sell'),'/',".$this->work_table.".id) AS url,
                             IF(".$this->tables['type_objects_live'].".id=1,
                                IF(".$this->work_table.".rooms_total=0, 'квартира-студия',CONCAT(".$this->work_table.".rooms_total,'-к. квартира') ),
                                IF(".$this->work_table.".id_type_object=2,
                                    CONCAT(IF(".$this->work_table.".rooms_sale>1, CONCAT(".$this->work_table.".rooms_sale, ' комнат'), 'комната'),' в ',".$this->work_table.".rooms_total,'-ккв'),
                                    ".$this->tables['type_objects_live'].".title
                                )    
                             )  as `obj_type`
                             
                      FROM ".$this->work_table."
                      LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->work_table.".id_way_type
                      LEFT JOIN ".$this->tables['type_objects_live']." ON ".$this->tables['type_objects_live'].".id = ".$this->work_table.".id_type_object
                      LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = ".$this->work_table.".id_building_type
                      LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->work_table.".id_district
                      LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                      LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area
                      LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->work_table.".id_user
                      LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                      ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" 
                                          : (empty($where)?"":"WHERE ".$where) )."
                      ".(!empty($order) ? "ORDER BY ".$order : "" )."
                      LIMIT ".$from.",".$count;
                      
            $res = $db->fetchall($sql);
            foreach($res as $k=>$item) {
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                if(strstr($res[$k]['txt_addr'],'Санкт-Петербург')!='' || strstr($res[$k]['txt_addr'],'Ленинградская область')!='') $res[$k]['full_address'] = $res[$k]['txt_addr'];
                else  $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];
                
                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district'])? ', '.$item['district'].' район':'').(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
                if(!empty($groupby) && !empty($item['group_id']) && $item['group_id']>0 &&  $item['raising_status'] != 1 ){
                    $objects = $db->fetch("SELECT 
                                                COUNT(*) as total_variants, 
                                                MIN(cost) as min_cost_objects, 
                                                MAX(cost) as max_cost_objects,
                                                MIN(square_full) as square_full_min
                                           FROM ".$this->work_table." 
                                           WHERE ".$where." AND group_id = ? AND rooms_sale = ? AND id != ? ".(!empty($item['id_housing_estate']) ? " AND id_housing_estate = ".$item['id_housing_estate'] : ""), $item['group_id'], $item['rooms_sale'], $item['id']
                    );
                    if(!empty($objects) && $objects['total_variants'] > 1) {
                        $res[$k] = array_merge($res[$k], $objects);
                    }
                }
                $res[$k]['txt_addr_shortened'] = mb_substr($res[$k]['txt_addr'],0,81,'UTF-8');
            }
        }
        return $res;
    }
}




/*******************************************************************************************************************
* Класс для работы со списками объектов рынка коммерческой недвижимости
*******************************************************************************************************************/
class EstateListCommercial extends EstateList{
    public function __construct(){
        parent::__construct(TYPE_ESTATE_COMMERCIAL);        
    }

    
    /**
    * Поиск объектов по заданным параметрам
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    */
    public function Search($clauses, $count=20, $from=0, $orderby='',$groupby=''){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = (!empty($orderby)?$orderby:"");
        $res = [];
        if(!empty($groupby) ){
            $ids = $this->getIdsList($where, $count, $from, $order, $groupby);
        } 
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT STRAIGHT_JOIN  ".$this->work_table.".*
                             , DATE_FORMAT(". $this->work_table .".`date_change`,'%e.%m.%Y') as date_change_normal
                             , DATE_FORMAT(". $this->work_table .".`date_in`,'%e.%m.%Y') as date_in_normal
                             , DATEDIFF(". $this->work_table .".`date_change`, NOW() - INTERVAL 30 DAY) as days_left
                             , IF( ".$this->work_table.".date_change + INTERVAL 30 day >= ".$this->work_table.".status_date_end, 
                                    DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 30 day,'%d.%m.%y'), 
                                    DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y')
                               ) AS `formatted_date_end`            
                             , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                             , ".$this->tables['business_centers_photos'].".`name` as `complex_photo` 
                             , LEFT (".$this->tables['business_centers_photos'].".`name`,2) as `complex_subfolder` 
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                             , ".$this->tables['subway_lines'].".color as `subway_color`
                             , ".$this->tables['districts'].".title as `district`
                             , ".$this->tables['users'].".id_agency as `id_agency`
                             , ".$this->tables['geodata'].".offname as `district_area`
                             , ".$this->tables['enters'].".title as `enter_title`
                             , ".$this->tables['facings'].".title as `facing_title`
                             , ".$this->tables['type_objects_commercial'].".title as `type_object`
                             , ".$this->tables['type_objects_commercial'].".short_title as `type_object_short` 
                             , ".$this->tables['business_centers'].".title as `business_center_title` 
                             , ".$this->tables['business_centers'].".chpu_title as `business_center_chpu_title` 
                             , ".$this->tables['way_types'].".title as `way_type_title`
                             , CONCAT(
                                    IF(".$this->work_table.".rent=1,'Аренда ','Продажа '),
                                    ".$this->tables['type_objects_commercial'].".`title_genitive`,
                                    ' , '
                               ) as `header`
                             , CONCAT(
                                    ".$this->tables['type_objects_commercial'].".`title`
                               ) as `obj_type`
                             , CONCAT(
                                    ".$this->tables['type_objects_commercial'].".`short_title`
                               ) as `obj_type_short_title`
                             , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent=".$this->work_table.".id) AS photos_count
                             , (SELECT COUNT(*) FROM ".$this->work_videos_table." WHERE id_parent=".$this->work_table.".id AND status = 3) AS videos_count
                             , ".$this->tables['promotions'].".discount_type, ".$this->tables['agencies'].".advert as `agency_advert`
                             , ".$this->tables['promotions'].".discount
                      FROM ".$this->work_table."
                      
                      LEFT JOIN ".$this->tables['type_objects_commercial']." ON ".$this->tables['type_objects_commercial'].".id = ".$this->work_table.".id_type_object
                      LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                      LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
                      LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->work_table.".id_district
                      LEFT JOIN ".$this->tables['enters']." ON ".$this->tables['enters'].".id = ".$this->work_table.".id_enter
                      LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->work_table.".id_facing
                      LEFT JOIN ".$this->tables['objects_statuses']." ON ".$this->tables['objects_statuses'].".id = ".$this->work_table.".status
                      LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->work_table.".id_way_type
                      LEFT JOIN ".$this->tables['promotions']." ON ".$this->tables['promotions'].".id = ".$this->work_table.".id_promotion
                      LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = ".$this->work_table.".id_business_center
                      LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area
                      LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->work_table.".id_user
                             LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id
                      LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                      LEFT JOIN ".$this->tables['business_centers_photos']." ON ".$this->tables['business_centers'].".id_main_photo = ".$this->tables['business_centers_photos'].".id
                      ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" : (empty($where)?"":"WHERE ".$where) )."
                      ".(!empty($order) ? "ORDER BY ".$order : "" )."
                      LIMIT ".$from.",".$count;
                      
            $res = $db->fetchall($sql);
            
            foreach($res as $k=>$item) {
                $res[$k]['photos'] = Photos::getList( 'commercial', $item['id'], false, false, 5 );
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];

                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district'])? ', '.$item['district'].' район':'').(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
                if(!empty($groupby) && !empty($item['group_id']) && $item['group_id']>0 &&   $item['raising_status'] != 1 ){
                    $objects = $db->fetch("SELECT COUNT(*) as total_variants, 
                                                MIN(cost) as min_cost_objects, 
                                                MAX(cost) as max_cost_objects,
                                                MIN(square_full) as square_full_min
                                                ".( empty($housing_estate)? " , GROUP_CONCAT(DISTINCT IF(rooms_sale=0,'студия',rooms_sale) ORDER BY rooms_sale) as rooms_group" : "")."
                                           FROM ".$this->work_table." 
                                           
                                           WHERE id !=? AND group_id = ? AND rooms_sale = ? AND  id != ? AND ".$where." " . 
                                           (!empty($item['id_housing_estate']) ? " AND id_housing_estate = ".$item['id_housing_estate'] : " AND id_housing_estate = 0") .
                                           ( $item['rooms_sale'] == 4 ? " AND ( rooms_sale = ".$item['rooms_sale'] . " OR rooms_sale > " . $item['rooms_sale'] . " ) " : " ")
                                           , $item['id'], $item['group_id'], $item['rooms_sale'], $item['id']
                    );
                    if(!empty($objects) && $objects['total_variants'] > 1) {
                        $res[$k] = array_merge($res[$k], $objects);
                    }
                }
            }
        }
        return $res;
    }
    
    /**
    * Поиск объектов по заданным параметрам для личного кабинета (+ куча дат)
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    */
    public function SearchLK($clauses, $count=20, $from=0, $orderby='',$groupby=''){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = (!empty($orderby)?$orderby:"");
        
        $res = [];
        if(!empty($groupby) ){
            $ids = $this->getIdsList($where, $count, $from, $order, $groupby);
        }
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT  ".$this->work_table.".*
                             , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['districts'].".title as `district`
                             , ".$this->tables['agencies'].".id as `id_agency`
                             , ".$this->tables['geodata'].".offname as `district_area`
                             , ".$this->tables['enters'].".title as `enter_title`
                             , ".$this->tables['facings'].".title as `facing_title`
                             , ".$this->tables['type_objects_commercial'].".title as `type_object`
                             , ".$this->tables['type_objects_commercial'].".short_title as `type_object_short` 
                             , ".$this->tables['business_centers'].".title as `business_center_title` 
                             , ".$this->tables['business_centers'].".chpu_title as `business_center_chpu_title` 
                             , ".$this->tables['way_types'].".title as `way_type_title`
                             , IF(".$this->tables['objects_statuses'].".cost>0,
                                DATE_FORMAT(".$this->work_table.".status_date_end,'%d %M'),
                                '')  as `status_end`
                             , CONCAT(
                                    IF(".$this->work_table.".rent=1,'Аренда ','Продажа '),
                                    ".$this->tables['type_objects_commercial'].".`title_genitive`,
                                    ' , '
                               ) as `header`
                             , CONCAT(
                                    ".$this->tables['type_objects_commercial'].".`title`
                               ) as `obj_type`
                             , CONCAT(
                                    ".$this->tables['type_objects_commercial'].".`short_title`
                               ) as `obj_type_short_title`
                             , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent=".$this->work_table.".id) AS photos_count
                             , (SELECT COUNT(*) FROM ".$this->work_videos_table." WHERE id_parent=".$this->work_table.".id AND status = 3) AS videos_count
                             , DATE_FORMAT(".$this->work_table.".date_change,'%d.%m.%y') AS `formatted_date_in`
                             , DATE_FORMAT(".$this->work_table.".date_change,'%d.%m.%y') as `normal_date_begin`
                             , DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y') as `normal_status_date_end`
                             , ".$this->work_table.".date_change + INTERVAL 30 day AS `date_end`
                             , IF( ".$this->work_table.".date_change + INTERVAL 30 day >= ".$this->work_table.".status_date_end, DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 30 day,'%d.%m.%y'), DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y') ) AS `formatted_date_end`
                             , DATE_FORMAT(".$this->work_table.".status_date_end,'%d %M')  as `status_end`
                      FROM ".$this->work_table."
                      LEFT JOIN ".$this->tables['enters']." ON ".$this->tables['enters'].".id = ".$this->work_table.".id_enter
                      LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->work_table.".id_facing
                      LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->work_table.".id_way_type
                      LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->work_table.".id_district
                      LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                      LEFT JOIN ".$this->tables['objects_statuses']." ON ".$this->tables['objects_statuses'].".id = ".$this->work_table.".status
                      LEFT JOIN ".$this->tables['type_objects_commercial']." ON ".$this->tables['type_objects_commercial'].".id = ".$this->work_table.".id_type_object
                      LEFT JOIN ".$this->tables['business_centers']." ON ".$this->tables['business_centers'].".id = ".$this->work_table.".id_business_center
                      LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area
                      LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->work_table.".id_user
                      LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                      LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                      ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" 
                                          : (empty($where)?"":"WHERE ".$where) )."
                      ".(!empty($order) ? "ORDER BY ".$order : "" )."
                      LIMIT ".$from.",".$count;
                      
            $res = $db->fetchall($sql);
            
            foreach($res as $k=>$item) {
                $res[$k]['photos'] = Photos::getList( 'commercial', $item['id'], false, false, 5 );
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];

                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district'])? ', '.$item['district'].' район':'').(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
            }
        }
        return $res;
    }
    /**
    * Поиск объектов по карте
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * return array of array
    */
    public function SearchMap($clauses){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $sql = "SELECT   
                                COUNT(".$this->work_table.".id) as total_objects
                                , ".$this->work_table.".id
                                , ".$this->work_table.".lat
                                , ".$this->work_table.".lng
                                , ".$this->work_table.".txt_addr
                                , ".$this->work_table.".group_id
                             FROM ".$this->work_table." USE INDEX(map_search)
                             WHERE " . $where ." AND ".$this->work_table.".group_id > 0 
                             GROUP BY ".$this->work_table.".group_id
                             LIMIT 0, " . $this->results_on_map;
        $res = $db->fetchall($sql);  
        return $res;
    }    
}




/*******************************************************************************************************************
* Класс для работы со списками объектов рынка строящейся недвижимости
*******************************************************************************************************************/
class EstateListBuild extends EstateList{
    public function __construct(){
        parent::__construct(TYPE_ESTATE_BUILD);        
    }

    
    /**
    * Поиск объектов по заданным параметрам
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    * @param string $groupby
    * @param boolean $housing_estate - объекты в ЖК
    */
    public function Search($clauses, $count=20, $from=0, $orderby='', $groupby='', $housing_estate=false){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = ( !empty( $orderby ) ? $orderby.", id_housing_estate DESC" : " id_housing_estate DESC" );
        $res = [];
        if(!empty($groupby) ) list($ids, $housing_estate_ids) = $this->getIdsList($where, $count, $from, $order, $groupby, true, $housing_estate);
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT STRAIGHT_JOIN  ".$this->work_table.".*
                             , DATE_FORMAT(". $this->work_table .".`date_change`,'%e.%m.%Y') as date_change_normal
                             , DATE_FORMAT(". $this->work_table .".`date_in`,'%e.%m.%Y') as date_in_normal
                             , DATEDIFF(". $this->work_table .".`date_change`, NOW() - INTERVAL 30 DAY) as days_left
                             , IF( ".$this->work_table.".date_change + INTERVAL 30 day >= ".$this->work_table.".status_date_end, 
                                    DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 30 day,'%d.%m.%y'), 
                                    DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y')
                               ) AS `formatted_date_end`
                             , ".$this->work_photos_table.".`name` as `photo` 
                             , LEFT (".$this->work_photos_table.".`name`,2) as `subfolder` 
                             , ".$this->tables['housing_estates_photos'].".`name` as `complex_photo` 
                             , LEFT (".$this->tables['housing_estates_photos'].".`name`,2) as `complex_subfolder` 
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                             , ".$this->tables['subway_lines'].".color as `subway_color`
                             , ".$this->tables['districts'].".title as `district`
                             , ".$this->tables['districts_areas'].".title as `district_area` 
                             , ".$this->tables['build_complete'].".title as `build_complete_title`
                             , ".$this->tables['facings'].".title as `facing_title`
                             , ".$this->tables['balcons'].".title as `balcon_title`
                             , ".$this->tables['toilets'].".title as `toilet_title`
                             , ".$this->tables['elevators'].".title as `elevator_title`
                             , ".$this->tables['building_types'].".title as `building_type_title`
                             , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent=".$this->work_table.".id) AS photos_count
                             , (SELECT COUNT(*) FROM ".$this->work_videos_table." WHERE id_parent=".$this->work_table.".id AND status = 3) AS videos_count
                             , (SELECT COUNT(*) FROM ".$this->work_table." b WHERE b.group_id=".$this->work_table.".group_id) AS total_objects
                             , ".$this->tables['promotions'].".discount_type, ".$this->tables['agencies'].".advert as `agency_advert`
                             , ".$this->tables['promotions'].".discount
                             , ".$this->tables['users'].".id_agency        
                             , ".$this->tables['housing_estates'].".title as `housing_estate_title`
                             , ".$this->tables['housing_estates'].".chpu_title as `housing_estate_chpu_title`
                             
                             FROM ".$this->work_table."
                             LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->work_table.".id_housing_estate = ".$this->tables['housing_estates'].".id
                             LEFT JOIN ".$this->tables['toilets']." ON ".$this->work_table.".id_toilet = ".$this->tables['toilets'].".id
                             LEFT JOIN ".$this->tables['balcons']." ON ".$this->work_table.".id_balcon = ".$this->tables['balcons'].".id
                             LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->work_table.".id_facing
                             LEFT JOIN ".$this->tables['elevators']." ON ".$this->tables['elevators'].".id = ".$this->work_table.".id_elevator
                             LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = ".$this->work_table.".id_building_type
                             LEFT JOIN ".$this->tables['objects_statuses']." ON ".$this->tables['objects_statuses'].".id = ".$this->work_table.".status
                             LEFT JOIN ".$this->tables['build_complete']." ON ".$this->tables['build_complete'].".id = ".$this->work_table.".id_build_complete
                             LEFT JOIN ".$this->tables['subways']." ON ".$this->work_table.".id_subway = ".$this->tables['subways'].".id
                             LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
                             LEFT JOIN ".$this->tables['promotions']." ON ".$this->work_table.".id_promotion = ".$this->tables['promotions'].".id
                             LEFT JOIN ".$this->tables['districts']." ON ".$this->work_table.".id_district = ".$this->tables['districts'].".id
                             LEFT JOIN ".$this->tables['districts_areas']." ON ".$this->work_table.".id_area = ".$this->tables['districts_areas'].".id
                             LEFT JOIN ".$this->tables['users']." ON ".$this->work_table.".id_user = ".$this->tables['users'].".id
                             LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id
                             LEFT JOIN ".$this->work_photos_table." ON ".$this->work_table.".id_main_photo = ".$this->work_photos_table.".id
                             LEFT JOIN ".$this->tables['housing_estates_photos']." ON ".$this->tables['housing_estates'].".id_main_photo = ".$this->tables['housing_estates_photos'].".id
                             ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" 
                                          : (empty($where)?"":"WHERE ".$where) )."
                             ".(!empty($order) ? "ORDER BY ".$order : "" )."
                             LIMIT ".$from.",".$count;
            $res = $db->fetchall($sql);     
            
            foreach($res as $k=>$item) {
                $res[$k]['photos'] = Photos::getList( 'build', $item['id'], false, false, 5 );
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];
                
                //формируем поля
                $type_alias = ($item['rooms_sale'] == 1?'одно':($item['rooms_sale'] == 2?'двух':($item['rooms_sale'] == 3?'трех':'много') ));
                $res[$k]['header'] = $type_alias."комнатной квартиры в новостройке";
                $res[$k]['obj_type'] = $type_alias."комнатная квартира ";
                
                
                if(!empty($users_ids) ) $res[$k]['id_agency'] = $users_ids[$res[$k]['id_user']];
                
                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district'])? ', '.$item['district'].' район':'').(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
                //removed $item['group_id']>0 from condition
                if(!empty($groupby) && !empty($item['group_id']) &&   $item['raising_status'] != 1){
                    $objects = $db->fetch("SELECT COUNT(*) as total_variants, 
                                                MIN(cost) as min_cost_objects, 
                                                MAX(cost) as max_cost_objects,
                                                MIN(square_full) as square_full_min
                                                ".( empty($housing_estate)? " , GROUP_CONCAT(DISTINCT IF(rooms_sale=0,'студия',rooms_sale) ORDER BY rooms_sale) as rooms_group" : "")."
                                           FROM ".$this->work_table." 
                                           
                                           WHERE id !=? AND group_id = ? AND ".$where." " . 
                                           (!empty($item['id_housing_estate']) ? " AND id_housing_estate = ".$item['id_housing_estate'] : " AND id_housing_estate = 0") .
                                           ( $item['rooms_sale'] == 4 ? " AND ( rooms_sale = ".$item['rooms_sale'] . " OR rooms_sale > " . $item['rooms_sale'] . " ) " : " AND rooms_sale = ".$item['rooms_sale'] )
                                           , $item['id'], $item['group_id']
                    );
                    //if($item['house'] == 58) 
                        //echo "Query:<br />".$db->last_query."!@#<br />\r\n";
                    if(!empty($objects) && $objects['total_variants'] > 1) {
                        $res[$k] = array_merge($res[$k], $objects);
                    }
                }
            }
        }
        return $res;
    }
    
    /**
    * Поиск объектов по заданным параметрам
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    * @param string $groupby
    * @param boolean $housing_estate - объекты в ЖК
    */
    public function SearchLK($clauses, $count=20, $from=0, $orderby='',$groupby='', $housing_estate=false){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = (!empty($orderby)?$orderby:"");
        $res = [];
        if(!empty($groupby) ){
            $ids = $this->getIdsList($where, $count, $from, $order, $groupby);
        }
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT  ".$this->work_table.".*
                             , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['districts'].".title as `district`
                             , ".$this->tables['agencies'].".id as `id_agency`
                             , ".$this->tables['geodata'].".offname as `district_area` 
                             , ".$this->tables['way_types'].".title as `way_type_title`
                             , ".$this->tables['housing_estates'].".title as `housing_estate_title`
                             , ".$this->tables['housing_estates'].".chpu_title as `housing_estate_chpu_title`
                             , ".$this->tables['build_complete'].".title as `build_complete_title`
                             , ".$this->tables['facings'].".title as `facing_title`
                             , ".$this->tables['balcons'].".title as `balcon_title`
                             , ".$this->tables['toilets'].".title as `toilet_title`
                             , ".$this->tables['elevators'].".title as `elevator_title`
                             , ".$this->tables['building_types'].".title as `building_type_title`
                             , DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 60 day,'%d %M') as `date_end`
                             , CONCAT(
                                    'Продажа ',
                                    IF(".$this->work_table.".rooms_sale=1,'одно',
                                        IF(".$this->work_table.".rooms_sale=2,'двух',
                                            IF(".$this->work_table.".rooms_sale=3,'трех','много')
                                        )    
                                    ), 
                                    'комнатной квартиры в новостройке ',
                                    ', '
                             ) as `header`
                             , CONCAT(
                                    IF(".$this->work_table.".rooms_sale=1,'одно',
                                        IF(".$this->work_table.".rooms_sale=2,'двух',
                                            IF(".$this->work_table.".rooms_sale=3,'трех','много')
                                        )    
                                    ), 
                                    'комнатная квартира '
                             ) as `obj_type`
                      , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent=".$this->work_table.".id) AS photos_count
                      , (SELECT COUNT(*) FROM ".$this->work_videos_table." WHERE id_parent=".$this->work_table.".id AND status = 3) AS videos_count
                      , DATE_FORMAT(".$this->work_table.".date_change,'%d.%m.%y') AS `formatted_date_in`
                      , DATE_FORMAT(".$this->work_table.".date_change,'%d.%m.%y') as `normal_date_begin`
                      , DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y') as `normal_status_date_end`
                      , ".$this->work_table.".date_change + INTERVAL 30 day AS `date_end`
                      , IF( ".$this->work_table.".date_change + INTERVAL 60 day >= ".$this->work_table.".status_date_end, DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 60 day,'%d.%m.%y'), DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y') ) AS `formatted_date_end`
                      , DATE_FORMAT(".$this->work_table.".status_date_end,'%d %M')  as `status_end`
                      FROM ".$this->work_table."
                      LEFT JOIN ".$this->tables['build_complete']." ON ".$this->tables['build_complete'].".id = ".$this->work_table.".id_build_complete
                      LEFT JOIN ".$this->tables['facings']." ON ".$this->tables['facings'].".id = ".$this->work_table.".id_facing
                      LEFT JOIN ".$this->tables['balcons']." ON ".$this->tables['balcons'].".id = ".$this->work_table.".id_balcon
                      LEFT JOIN ".$this->tables['toilets']." ON ".$this->tables['toilets'].".id = ".$this->work_table.".id_toilet
                      LEFT JOIN ".$this->tables['elevators']." ON ".$this->tables['elevators'].".id = ".$this->work_table.".id_elevator
                      LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->work_table.".id_way_type
                      LEFT JOIN ".$this->tables['building_types']." ON ".$this->tables['building_types'].".id = ".$this->work_table.".id_building_type
                      LEFT JOIN ".$this->tables['objects_statuses']." ON ".$this->tables['objects_statuses'].".id = ".$this->work_table.".status
                      LEFT JOIN ".$this->tables['districts']." ON ".$this->tables['districts'].".id = ".$this->work_table.".id_district
                      LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                      LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->tables['housing_estates'].".id = ".$this->work_table.".id_housing_estate
                      LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area
                      LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->work_table.".id_user
                      LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                      LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                      ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" 
                                          : (empty($where)?"":"WHERE ".$where) )."
                      ".(!empty($order) ? "ORDER BY ".$order : "" )."
                      LIMIT ".$from.",".$count;
                      
            $res = $db->fetchall($sql);
            foreach($res as $k=>$item) {
                $res[$k]['photos'] = Photos::getList( 'build', $item['id'], false, false, 5 );
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];

                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district'])? ', '.$item['district'].' район':'').(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
            }
        }
        
        return $res;
    }
    /**
    * Поиск объектов по карте
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * return array of array
    */
    public function SearchMap($clauses){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $sql = "SELECT   
                                COUNT(".$this->work_table.".id) as total_objects
                                , ".$this->work_table.".id
                                , ".$this->work_table.".lat
                                , ".$this->work_table.".lng
                                , ".$this->work_table.".txt_addr
                                , ".$this->work_table.".group_id
                                , ".$this->tables['housing_estates'].".title as `housing_estate_title`
                                , ".$this->tables['housing_estates'].".chpu_title as `housing_estate_chpu_title`
                             FROM ".$this->work_table." USE INDEX(map_search)
                             LEFT JOIN ".$this->tables['housing_estates']." ON ".$this->work_table.".id_housing_estate = ".$this->tables['housing_estates'].".id
                             WHERE " . $where ." AND ".$this->work_table.".group_id > 0 
                             GROUP BY ".$this->work_table.".group_id
                             LIMIT 0, " . $this->results_on_map;
        $res = $db->fetchall($sql);  
        return $res;
    }
    
}




/*******************************************************************************************************************
* Класс для работы со списками объектов рынка загородной недвижимости
*******************************************************************************************************************/
class EstateListCountry extends EstateList{
    public function __construct(){ 
        parent::__construct(TYPE_ESTATE_COUNTRY);        
    }

   
    /**
    * Поиск объектов по заданным параметрам
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    */
    public function Search($clauses, $count=20, $from=0, $orderby='',$groupby=''){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = (!empty($orderby)?$orderby:"");
        $res = [];
        if(!empty($groupby) ){
            $ids = $this->getIdsList($where, $count, $from, $order, $groupby);
        }
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT STRAIGHT_JOIN  ".$this->work_table.".*
                             , DATE_FORMAT(". $this->work_table .".`date_change`,'%e.%m.%Y') as date_change_normal
                             , DATE_FORMAT(". $this->work_table .".`date_in`,'%e.%m.%Y') as date_in_normal
                             , DATEDIFF(". $this->work_table .".`date_change`, NOW() - INTERVAL 30 DAY) as days_left
                             , IF( ".$this->work_table.".date_change + INTERVAL 30 day >= ".$this->work_table.".status_date_end, 
                                    DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 30 day,'%d.%m.%y'), 
                                    DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y')
                               ) AS `formatted_date_end`
                             , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                             , ".$this->tables['cottages_photos'].".`name` as `complex_photo` 
                             , LEFT (".$this->tables['cottages_photos'].".`name`,2) as `complex_subfolder` 
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['subway_lines'].".line_color as `subway_line_color`
                             , ".$this->tables['subway_lines'].".color as `subway_color`
                             , ".$this->tables['geodata'].".offname as district_area
                             , ".$this->tables['users'].".id_agency as id_agency
                             , ".$this->tables['type_objects_country'].".title as `type_object`    
                             , ".$this->tables['way_types'].".title as `way_type_title`
                             , ".$this->tables['ownerships'].".title as `ownership_title`
                             , ".$this->tables['electricities'].".title as `electricity_title`
                             , ".$this->tables['water_supplies'].".title as `water_supply_title`
                             , ".$this->tables['gases'].".title as `gas_title`
                             , ".$this->tables['construct_materials'].".title as `construct_material_title`
                             , ".$this->tables['heatings'].".title as `heating_title`
                             , ".$this->tables['cottages'].".title as `cottage_title`
                             , ".$this->tables['cottages'].".chpu_title as `cottage_chpu_title`
                             , CONCAT(
                                    IF(".$this->work_table.".rent=1,'Аренда ','Продажа '),
                                    ".$this->tables['type_objects_country'].".`title_genitive`,
                                    ', '
                             ) as `header`
                             , CONCAT(
                                    ".$this->tables['type_objects_country'].".`title`
                             ) as `obj_type`
                      , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent=".$this->work_table.".id) AS photos_count
                      , (SELECT COUNT(*) FROM ".$this->work_videos_table." WHERE id_parent=".$this->work_table.".id AND status = 3) AS videos_count
                      , ".$this->tables['promotions'].".discount_type, ".$this->tables['agencies'].".advert as `agency_advert`
                      , ".$this->tables['promotions'].".discount
                      FROM ".$this->work_table."
                      LEFT JOIN ".$this->tables['ownerships']." ON ".$this->tables['ownerships'].".id=".$this->work_table.".id_ownership
                      LEFT JOIN ".$this->tables['electricities']." ON ".$this->tables['electricities'].".id=".$this->work_table.".id_electricity
                      LEFT JOIN ".$this->tables['water_supplies']." ON ".$this->tables['water_supplies'].".id=".$this->work_table.".id_water_supply
                      LEFT JOIN ".$this->tables['gases']." ON ".$this->tables['gases'].".id=".$this->work_table.".id_gas
                      LEFT JOIN ".$this->tables['construct_materials']." ON ".$this->tables['construct_materials'].".id=".$this->work_table.".id_construct_material
                      LEFT JOIN ".$this->tables['heatings']." ON ".$this->tables['heatings'].".id=".$this->work_table.".id_heating
                      LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->work_table.".id_way_type
                      LEFT JOIN ".$this->tables['type_objects_country']." ON ".$this->tables['type_objects_country'].".id = ".$this->work_table.".id_type_object
                      LEFT JOIN ".$this->tables['objects_statuses']." ON ".$this->tables['objects_statuses'].".id = ".$this->work_table.".status
                      LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                      LEFT JOIN ".$this->tables['subway_lines']." ON ".$this->tables['subways'].".id_subway_line = ".$this->tables['subway_lines'].".id
                      LEFT JOIN ".$this->tables['promotions']." ON ".$this->tables['promotions'].".id = ".$this->work_table.".id_promotion
                      LEFT JOIN ".$this->tables['cottages']." ON ".$this->tables['cottages'].".id = ".$this->work_table.".id_cottage
                      LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area
                      LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->work_table.".id_user
                             LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id
                      LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                      LEFT JOIN ".$this->tables['cottages_photos']." ON ".$this->tables['cottages'].".id_main_photo = ".$this->tables['cottages_photos'].".id
                      ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" 
                                          : (empty($where)?"":"WHERE ".$where) )."
                      ".(!empty($order) ? "ORDER BY ".$order : "" )."
                      LIMIT ".$from.",".$count;
                      
            $res = $db->fetchall($sql);
            
            foreach($res as $k=>$item) {
                $res[$k]['photos'] = Photos::getList( 'country', $item['id'], false, false, 5 );
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];

                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
            }
            return $res;
        }
    }
    
    /**
    * Поиск объектов по заданным параметрам
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * @param integer $count
    * @param integer $from
    * @param string $sort
    */
    public function SearchLK($clauses, $count=20, $from=0, $orderby='',$groupby=''){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $order = (!empty($orderby)?$orderby:"");
        $res = [];
        if(!empty($groupby) ){
            $ids = $this->getIdsList($where, $count, $from, $order, $groupby);
        }
        if(empty($groupby) || (!empty($groupby) && !empty($ids) )){
            $sql = "SELECT  ".$this->work_table.".*
                             , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                             , ".$this->tables['subways'].".title as `subway`
                             , ".$this->tables['geodata'].".offname as district_area
                             , ".$this->tables['agencies'].".id as id_agency
                             , ".$this->tables['type_objects_country'].".title as `type_object`
                             , ".$this->tables['way_types'].".title as `way_type_title`
                             , ".$this->tables['ownerships'].".title as `ownership_title`
                             , ".$this->tables['electricities'].".title as `electricity_title`
                             , ".$this->tables['water_supplies'].".title as `water_supply_title`
                             , ".$this->tables['gases'].".title as `gas_title`
                             , ".$this->tables['construct_materials'].".title as `construct_material_title`
                             , ".$this->tables['heatings'].".title as `heating_title`
                             , ".$this->tables['cottages'].".title as `cottage_title`
                             , ".$this->tables['cottages'].".chpu_title as `cottage_chpu_title`
                             , DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 30 day,'%d %M') as `date_end`
                             , CONCAT(
                                    IF(".$this->work_table.".rent=1,'Аренда ','Продажа '),
                                    ".$this->tables['type_objects_country'].".`title_genitive`,
                                    ', '
                             ) as `header`
                             , CONCAT(
                                    ".$this->tables['type_objects_country'].".`title`
                             ) as `obj_type`
                      , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent=".$this->work_table.".id) AS photos_count
                      , (SELECT COUNT(*) FROM ".$this->work_videos_table." WHERE id_parent=".$this->work_table.".id AND status = 3) AS videos_count
                      , DATE_FORMAT(".$this->work_table.".date_change,'%d.%m.%y') AS `formatted_date_in`
                      , DATE_FORMAT(".$this->work_table.".date_change,'%d.%m.%y') as `normal_date_begin`
                      , DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y') as `normal_status_date_end`
                      , ".$this->work_table.".date_change + INTERVAL 30 day AS `date_end`
                      , IF( ".$this->work_table.".date_change + INTERVAL 30 day >= ".$this->work_table.".status_date_end, DATE_FORMAT(".$this->work_table.".date_change + INTERVAL 30 day,'%d.%m.%y'), DATE_FORMAT(".$this->work_table.".status_date_end,'%d.%m.%y') ) AS `formatted_date_end`
                      , DATE_FORMAT(".$this->work_table.".status_date_end,'%d %M')  as `status_end`
                      FROM ".$this->work_table."
                      LEFT JOIN ".$this->tables['ownerships']." ON ".$this->tables['ownerships'].".id=".$this->work_table.".id_ownership
                      LEFT JOIN ".$this->tables['electricities']." ON ".$this->tables['electricities'].".id=".$this->work_table.".id_electricity
                      LEFT JOIN ".$this->tables['water_supplies']." ON ".$this->tables['water_supplies'].".id=".$this->work_table.".id_water_supply
                      LEFT JOIN ".$this->tables['gases']." ON ".$this->tables['gases'].".id=".$this->work_table.".id_gas
                      LEFT JOIN ".$this->tables['construct_materials']." ON ".$this->tables['construct_materials'].".id=".$this->work_table.".id_construct_material
                      LEFT JOIN ".$this->tables['heatings']." ON ".$this->tables['heatings'].".id=".$this->work_table.".id_heating
                      LEFT JOIN ".$this->tables['way_types']." ON ".$this->tables['way_types'].".id=".$this->work_table.".id_way_type
                      LEFT JOIN ".$this->tables['type_objects_country']." ON ".$this->tables['type_objects_country'].".id = ".$this->work_table.".id_type_object
                      LEFT JOIN ".$this->tables['objects_statuses']." ON ".$this->tables['objects_statuses'].".id = ".$this->work_table.".status
                      LEFT JOIN ".$this->tables['subways']." ON ".$this->tables['subways'].".id = ".$this->work_table.".id_subway
                      LEFT JOIN ".$this->tables['cottages']." ON ".$this->tables['cottages'].".id = ".$this->work_table.".id_cottage
                      LEFT JOIN ".$this->tables['geodata']." ON ".$this->tables['geodata'].".a_level=2 AND ".$this->tables['geodata'].".id_region = ".$this->work_table.".id_region AND ".$this->tables['geodata'].".id_area = ".$this->work_table.".id_area
                      LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->work_table.".id_user
                      LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                      LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                      ".(!empty($groupby) ? "WHERE ".$this->work_table.".id IN (".implode(',',$ids).")" 
                                          : (empty($where)?"":"WHERE ".$where) )."
                      ".(!empty($order) ? "ORDER BY ".$order : "" )."
                      LIMIT ".$from.",".$count;
                      
            $res = $db->fetchall($sql);
            
            foreach($res as $k=>$item) {
                $res[$k]['photos'] = Photos::getList( 'country', $item['id'], false, false, 5 );
                //определение адреса
                $res[$k]['txt_addr'] = $this->getAddress($item);
                $res[$k]['full_address'] = ($item['id_region'] == 47 ? 'Ленинградская область, ' : 'Санкт-Петербург, ').
                                           (!empty($item['district'])? $item['district'].' р-н ':'').
                                           (!empty($item['district_area'])? $item['district_area'].' р-н ':'').$res[$k]['txt_addr'];

                $res[$k]['header'] .= $res[$k]['txt_addr'].(!empty($item['district_area'])? ', '.$item['district_area'].' район ЛО':'');
            }
            return $res;
        }
    }
    /**
    * Поиск объектов по карте
    * @param mixed (string) Условие или (array) Параметры поиска array(поле=>array('value'=>val|'set'=>array(val,val,..)|'from'=>val,'to'=>val)[,поле=>...])
    * return array of array
    */
    public function SearchMap($clauses){
        global $db;
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;
        else return false;
        $sql = "SELECT   
                                COUNT(".$this->work_table.".id) as total_objects
                                , ".$this->work_table.".id
                                , ".$this->work_table.".lat
                                , ".$this->work_table.".lng
                                , ".$this->work_table.".txt_addr
                                , ".$this->work_table.".group_id
                             FROM ".$this->work_table." USE INDEX(map_search)
                             WHERE " . $where ." AND ".$this->work_table.".group_id > 0 
                             GROUP BY ".$this->work_table.".group_id
                             LIMIT 0, " . $this->results_on_map;
        $res = $db->fetchall($sql);  
        return $res;
    }     
}




/*******************************************************************************************************************
* Класс для работы со списками объектов рынка зарубежной недвижимости
*******************************************************************************************************************/
class EstateListInter extends EstateList{
    public function __construct(){
        parent::__construct(TYPE_ESTATE_INTER);        
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
        if(is_array($clauses) ) $where = parent::makeWhereClause($clauses);
        elseif(is_string($clauses) ) $where = $clauses;

        if(empty($order) ) $order = $this->work_table.".date_in DESC";
        if(empty($where) ) $where = $this->work_table.".published = 1";
        global $db;
        $sql = "SELECT ".$this->work_table.".* 
                        , IF(YEAR(".$this->work_table.".`date_in`) < Year(CURDATE() ),DATE_FORMAT(".$this->work_table.".`date_in`,'%e %M %Y'),DATE_FORMAT(".$this->work_table.".`date_in`,'%e %M') ) as normal_date
                        , ".$this->work_photos_table.".`name` as `photo`, LEFT (".$this->work_photos_table.".`name`,2) as `subfolder`
                        , ".$this->tables['inter_countries_flags_photos'].".`name` as `country_photo`, LEFT (".$this->tables['inter_countries_flags_photos'].".`name`,2) as `country_subfolder`
                        , ".$this->tables['inter_regions'].".title as region_title
                        , ".$this->tables['inter_currencies'].".title as currency_title
                        , ".$this->tables['inter_countries'].".title as country_title                    
                        , ".$this->tables['inter_type_objects'].".title as type_object                    
                        , ".$this->tables['inter_cost_types'].".title as cost_type_title                    
                        , ".$this->tables['inter_managers'].".title as manager_title                    
                        , ".$this->tables['inter_partners'].".title as partner_title                    
                        , (SELECT COUNT(*) FROM ".$this->work_photos_table." WHERE id_parent = ".$this->work_table.".id) as photos_count
                        
                FROM ".$this->work_table."
                LEFT JOIN ".$this->tables['inter_countries']." ON ".$this->tables['inter_countries'].".id = ".$this->work_table.".id_country
                LEFT JOIN ".$this->tables['inter_countries_flags_photos']." ON ".$this->tables['inter_countries_flags_photos'].".id_parent = ".$this->tables['inter_countries'].".id
                LEFT JOIN ".$this->tables['inter_regions']." ON ".$this->tables['inter_regions'].".id = ".$this->work_table.".id_region
                LEFT JOIN ".$this->tables['inter_type_objects']." ON ".$this->tables['inter_type_objects'].".id = ".$this->work_table.".id_type_object
                LEFT JOIN ".$this->tables['inter_cost_types']." ON ".$this->tables['inter_cost_types'].".id = ".$this->work_table.".id_cost_type
                LEFT JOIN ".$this->tables['inter_currencies']." ON ".$this->tables['inter_currencies'].".id = ".$this->work_table.".id_currency
                LEFT JOIN ".$this->tables['inter_managers']." ON ".$this->tables['inter_managers'].".id = ".$this->work_table.".id_manager
                LEFT JOIN ".$this->tables['inter_partners']." ON ".$this->tables['inter_partners'].".id = ".$this->work_table.".id_partner
                LEFT JOIN ".$this->work_photos_table." ON ".$this->work_photos_table.".id = ".$this->work_table.".id_main_photo
                WHERE ".$where."
                GROUP BY ".$this->work_table.".id ";
        if(!empty($order) ) $sql .= " ORDER BY ".$order;
        if(!empty($count) ) $sql .= " LIMIT ".$from.",".$count;
        $list = $db->fetchall($sql);  
        foreach($list as $k=>$item) {
            $photos = Photos::getList( 'inter', $item['id'], false, false, 5 );  
            if( empty( $photos ) ) $photos = Photos::getList( 'inter', false, false, false, false, $item['id_main_photo'] );
            $list[$k]['photos'] = $photos;  
        }
        if(empty($list) ) return [];
        return $list;    
    }
    
} 

/**
* Клас формирования левого сайдбара и параметров для поиска
*/
class EstateSearch {
    protected $tables = [];
    
    public function __construct(){
        $this->tables = Config::$sys_tables;
    }
    /**
    * Формирование условий из параметров строки
    * @param string $requestes_path запрошенная страница
    * @param array $new_parameters список подзапросов 
    */    
    public function paramsFromUrl($page_parameters, $real_url){
        if(!empty($page_parameters[1]) && $page_parameters[1] == 'block'){
            $url = parse_url($real_url);
            $parsed_url = parse_url($url['query']);
            $parsed_url['path'] = str_replace('get_parameters='.Host::$protocol.'://'.Host::$host.'/', '', $parsed_url['path']);
        } else {
            $post_parameters = Request::GetParameters(METHOD_POST);
            $post_parameters = $post_parameters['data'];
            $parsed_url = parse_url($post_parameters);
            
        }
        $page_parameters = explode('/', trim($parsed_url['path'],'/') );
        if(!empty($parsed_url['query']) ){
            parse_str($parsed_url['query'], $parameters);
            $parameters['path'] = '/' . $parsed_url['path'];
        } else $parameters['path'] = $parsed_url['path'];
        
        if($page_parameters[0] == 'new') array_shift($page_parameters);
        $estate_type = $page_parameters[0];
        if(in_array($estate_type, array('zhiloy_kompleks', 'cottage', 'business_centers') )) $deal_type = 'sell';
        else $deal_type = !empty($page_parameters[1]) ? $page_parameters[1] : 'sell';    
        if(!empty($parameters['new_groups']) ) unset($parameters['new_groups']);
        return array($parameters, $estate_type, $deal_type);
    }
    /**
    * Формирование условий из параметров строки
    * @param string $requestes_path запрошенная страница
    * @param array $new_parameters список подзапросов 
    */
    
    public function formParams($new_parameters = false, $only_params = false, $estate_type = false, $deal_type = '' ){
       global $db, $sys_tables, $requested_page;    
        
        $sys_tables = !empty($sys_tables) ? $sys_tables : $this->tables;
        //передана строка параметров
        if(is_string($new_parameters)){
            $parse_url = parse_url($new_parameters);
            $qry = explode('&', !empty($parse_url['query']) ? $parse_url['query'] : '');
            $new_parameters = array();
            foreach($qry as $q) {
                list($key,$val) = explode('=',$q.'=');
                $new_parameters[$key] = $val;
                
            }
        }
        //параметры могут быть разбиты на части             
        $parameters = !empty($new_parameters) ? $new_parameters : Request::GetParameters(METHOD_GET);
        $params = array();
        if(!empty($_SERVER['REQUEST_URI'])){
            $params = explode('/', $_SERVER['REQUEST_URI']);
            array_shift($params);
            if( !empty($params[0]) && $params[0] == 'estate') array_shift($params);
            if( !empty($params[0]) && $params[0] == 'similar') array_shift($params);
        }
        $estate_type = empty($estate_type) ? ( !empty($params[0]) ? $params[0] : false ) : $estate_type;
        //для апартаментов та же база, что и для ЖК
        if($estate_type == 'apartments') $estate_type = 'zhiloy_kompleks';
        if( empty( $sys_tables[$estate_type] ) ) return false;
        $deal_type = $deal_type ?: ( $params[1] ?: '' );
        //флаг принадлежности к объекту недвижимости, не комплексу
        $estate_object = empty($estate_type) || in_array($estate_type, array('live','build','commercial','country', 'inter'));
        Response::SetBoolean('estate_object', $estate_object);

        $clauses = $get_parameters = array();
        $clauses['published'] = array('value'=> 1);
        // добавление гео условий условий
        $reg_where = array();
        //добавление интервальных условий для сайдбара
        $range_where = array();
        
        if(!empty($parameters['rent'])) $clauses['rent'] = array('value' => Convert::ToInt($parameters['rent']));
        
        
        //выкусываем из параметров исключающие друг друга параметры
        if(!empty($parameters['regions'])){
            if(!empty($parameters['districts']) && $parameters['regions'] == 47) unset($parameters['districts']);
            elseif(!empty($parameters['district_areas']) && $parameters['regions'] == 78) unset($parameters['district_areas']);
        }
        
        if(!empty($estate_object) && !empty($deal_type)) $clauses['rent'] = array('value'=> $deal_type=='rent'?1:2);
        
        
        if(!empty($parameters['id_promotion'])) $clauses['id_promotion'] = array('value' => Convert::ToInt($parameters['id_promotion']));
        
        if(!empty($parameters['company_page'])) $get_parameters['company_page'] = true;
        
        if(!empty($parameters['agent_page'])) $get_parameters['agent_page'] = true;
        
        if(!empty($parameters['housing_estate_page'])) $get_parameters['housing_estate_page'] = true;
        
        if(!empty($parameters['notgb'])) $get_parameters['notgb'] = true;
        
        if(!empty($parameters['agent'])) $clauses['id_user'] = array('value' => Convert::ToInt($parameters['agent']));
        
        if(!empty($parameters['subways'])) {
            $subways_array = array_map("Convert::toInt",explode(',', $parameters['subways']));
            $reg_where[] = $sys_tables[$estate_type].".`id_subway` IN (".implode(', ', $subways_array).")";
            $get_parameters['subways'] = implode(",", $subways_array);
            $subways = $db->fetchall("SELECT id, title FROM ".$sys_tables['subways']." WHERE id IN (".$get_parameters['subways'].")");
            Response::SetArray('subways',$subways);
        }
        if(!empty($parameters['districts'])) {
            $districts_array = array_map("Convert::toInt", explode(',', $parameters['districts']));
            if(!empty($districts_array)){
                $reg_where[] = $sys_tables[$estate_type].".`id_district` IN (".implode(', ', $districts_array).")";
                $districts_array = $districts_array;
                $get_parameters['districts'] = implode(',', $districts_array);
                $districts = $db->fetchall("SELECT id, title FROM ".$sys_tables['districts']." WHERE id IN (".implode(',', $districts_array).")");
                Response::SetArray('districts',$districts);
            }
        }
        if(!empty($parameters['district_areas'])) {
            $district_areas_array = array_map("Convert::toInt",explode(',',$parameters['district_areas']));
            foreach($district_areas_array as $da_key=>$da_val) if(!Validate::isDigit($da_val)) unset($district_areas_array[$da_key]);
            if(!empty($district_areas_array)) {
                $get_parameters['district_areas'] = implode(',', $district_areas_array);
                $district_areas = $db->fetchall("SELECT id, id_region, id_area, offname as title FROM ".$sys_tables['geodata']." WHERE id IN (".implode(',', $district_areas_array).")");
                Response::SetArray('district_areas',$district_areas);
                if(!empty($estate_type)){
                    foreach($district_areas as $reg){
                        $reg_where[] = "(".$sys_tables[$estate_type].".`id_region`=".$reg['id_region']." AND ".$sys_tables[$estate_type].".`id_area`=".$reg['id_area'].")";
                    }
                }
            }
        }
        if(!empty($parameters['countries'])) {
            $countries = array_map("Convert::toInt",explode(',',$parameters['countries']));
            $clauses['id_country'] = array('set'=> $countries);
            $get_parameters['countries'] = implode(",", $countries);
        }
        if(!empty($parameters['streets']) && Validate::isDigit($parameters['streets'])) {
            $clauses['id_street'] = array('value'=>$parameters['streets']);
            $clauses['id_region'] = array('value'=>78);
            $clauses['id_area'] = array('value'=>0);
            $get_parameters['streets'] = $parameters['streets'];
        }
        if(isset($parameters['rooms'])) {
            if(!empty($parameters['obj_type']) &&  $estate_type == 'live') $clauses['rooms_total'] = array('set'=> explode(',',$parameters['rooms']));
            else $clauses['rooms_sale'] = array('set'=> explode(',',$parameters['rooms']));
            $get_parameters['rooms'] = $parameters['rooms'];
            $get_parameters['rooms_checked'] = array();
            $arr = array();  
            foreach(explode(',',$parameters['rooms']) as $val) {
                $get_parameters['rooms_checked'][$val] = 1;
                $arr[] = ($val>3 ? "4 и более" : $val); 
            }
        }
        if(isset($parameters['rooms_sale'])) {
            $clauses['rooms_sale'] = array('set'=> explode(',',$parameters['rooms_sale']));
            $get_parameters['rooms_sale'] = !empty($parameters['rooms_sale']) ? $parameters['rooms_sale'] : $parameters['rooms'];
            $get_parameters['rooms_sale_checked'] = array();
            $arr = array();  
            foreach(explode(',',$parameters['rooms_sale']) as $val) {
                $get_parameters['rooms_sale_checked'][$val] = 1;
                $arr[] = ($val>3 ? "4 и более" : $val); 
            }
        }
        if(!empty($estate_object) && $estate_type!='build'){
            $requested = $params;
            if(!empty($requested[1]) && $requested[1]==$estate_type && !empty($deal_type) && !empty($requested[3]) && $requested[3]!='search')  $type_object = $requested[3];  // URL = '/live/rent/rooms/
            if(!empty($type_object) || !empty($parameters['obj_type'])){
                if( !empty($parameters['obj_type']) ) $types_group['id'] = $parameters['obj_type'];
                else $types_group = $db->fetch("SELECT * FROM ".$sys_tables['object_type_groups']." WHERE alias=? AND type=?", $type_object, $estate_type);
                if(!empty($types_group)){
                    if($estate_type != 'build'){
                        $types = $db->fetchall("SELECT id FROM ".$sys_tables['type_objects_'.$estate_type]." WHERE id_group=?", 'id', $types_group['id']);
                        if(!empty($types)) $clauses['id_type_object'] = array('set'=> array_keys($types));
                        
                    }
                    $get_parameters['objects_group'] =  $types_group['id'].'-'.$estate_type.'-' .$deal_type;
                    
                } 
            } else if(!empty($parameters['obj_type'])) {
                $clauses['id_type_object'] = array('value'=> $parameters['obj_type']);
                $get_parameters['obj_type'] = $parameters['obj_type'];
            }
        }
        if( empty( $get_parameters['objects_group'] ) ) {
            $type = $db->fetch("SELECT id FROM ".$sys_tables['object_type_groups']." WHERE type=? ORDER BY id ASC", $estate_type);
            $get_parameters['objects_group'] =  !empty( $type['id'] ) ? $type['id'].'-'.$estate_type.'-'.$deal_type : $estate_type;
        }
        $suffix = "";
        if(!empty($parameters['currency'])) {
            switch($parameters['currency']){
                case 'rur': $suffix = "_rubles"; break;
                case 'eur': $suffix = "_euros"; break;
                default: $suffix = "_dollars";
            }
        }
        if(!empty($parameters['title'])) {
            $clauses['title'] = array('value'=> $parameters['title']);
            $get_parameters['title'] = $parameters['title'];
        }
        if(!empty($parameters['cottage_districts'])) {
            $clauses['id_district_area'] = array('value'=> $parameters['cottage_districts']);
            $get_parameters['cottage_districts'] = $parameters['cottage_districts'];
        }

      
        if(!empty($parameters['square_live_to']) || !empty($parameters['square_live_from'])) { 
            $clauses['square_live'] = array();
            if(!empty($parameters['square_live_from'])) {
                $clauses['square_live']['from'] = $parameters['square_live_from'];
                $get_parameters['square_live_from'] = $parameters['square_live_from'];
            }
            if(!empty($parameters['square_live_to'])) {
                $clauses['square_live']['to'] = $parameters['square_live_to'];
                $get_parameters['square_live_to'] = $parameters['square_live_to'];
            }
        }
        if(!empty($parameters['square_full_to']) || !empty($parameters['square_full_from'])) { 
            $clauses['square_full'] = array();
            if(!empty($parameters['square_full_from'])) {
                $clauses['square_full']['from'] = $parameters['square_full_from'];
                $get_parameters['square_full_from'] = $parameters['square_full_from'];
            }
            if(!empty($parameters['square_full_to'])) {
                $clauses['square_full']['to'] = $parameters['square_full_to'];
                $get_parameters['square_full_to'] = $parameters['square_full_to'];
            }
        }
        if(!empty($parameters['square_kitchen_to']) || !empty($parameters['square_kitchen_from'])) { 
            $clauses['square_kitchen'] = array();
            if(!empty($parameters['square_kitchen_from'])) {
                $clauses['square_kitchen']['from'] = $parameters['square_kitchen_from'];
                $get_parameters['square_kitchen_from'] = $parameters['square_kitchen_from'];
            }
            if(!empty($parameters['square_kitchen_to'])) {
                $clauses['square_kitchen']['to'] = $parameters['square_kitchen_to'];
                $get_parameters['square_kitchen_to'] = $parameters['square_kitchen_to'];
            }
        }
        if(!empty($parameters['square_ground_to']) || !empty($parameters['square_ground_from'])) { 
            $clauses['square_ground'] = array();
            if(!empty($parameters['square_ground_from'])) {
                $clauses['square_ground']['from'] = $parameters['square_ground_from'];
                $get_parameters['square_ground_from'] = $parameters['square_ground_from'];
            }
            if(!empty($parameters['square_ground_to'])) {
                $clauses['square_ground']['to'] = $parameters['square_ground_to'];
                $get_parameters['square_ground_to'] = $parameters['square_ground_to'];
            }
        }
        if(!empty($parameters['square_usefull_to']) || !empty($parameters['square_usefull_from'])) { 
            $clauses['square_usefull'] = array();
            if(!empty($parameters['square_usefull_from'])) {
                $clauses['square_usefull']['from'] = $parameters['square_usefull_from'];
                $get_parameters['square_usefull_from'] = $parameters['square_usefull_from'];
            }
            if(!empty($parameters['square_usefull_to'])) {
                $clauses['square_usefull']['to'] = $parameters['square_usefull_to'];
                $get_parameters['square_usefull_to'] = $parameters['square_usefull_to'];
            }
        }
        if(!empty($parameters['level'])) {
            $clauses['level'] = array('value'=> $parameters['level']);
            $get_parameters['level'] = $parameters['level'];
        }
        if(!empty($parameters['level_to']) || !empty($parameters['level_from'])) { 
            $clauses['level'] = array();
            if(!empty($parameters['level_from'])) {
                $clauses['level']['from'] = $parameters['level_from'];
                $get_parameters['level_from'] = $parameters['level_from'];
            }
            if(!empty($parameters['level_to'])) {
                $clauses['level']['to'] = $parameters['level_to'];
                $get_parameters['level_to'] = $parameters['level_to'];
            }
        }
        if(!empty($parameters['not_first_level'])) {
            $clauses['level']['from'] = 2;
            $get_parameters['not_first_level'] = $parameters['not_first_level'];
        }
        if(!empty($parameters['not_last_level'])) {
            $clauses['not_last_level']['to_level_total'] = 2;
            $get_parameters['not_last_level'] = $parameters['not_last_level'];
        }
        
        if($estate_type != 'cottage'){
            $max_cost = Convert::ToInt(empty($parameters['max_cost'])?0:$parameters['max_cost']);
            $min_cost = Convert::ToInt(empty($parameters['min_cost'])?0:$parameters['min_cost']);
            if($max_cost || $min_cost) { 
                $clauses['cost'.$suffix] = array();
                if($min_cost) {
                    
                    $clauses['cost'.$suffix]['from'] = $min_cost;
                    $get_parameters['min_cost'] = $min_cost;
                }
                if($max_cost) {
                    
                    $clauses['cost'.$suffix]['to'] = $max_cost;
                    $get_parameters['max_cost'] = $max_cost;
                }
            }
        }
        if(!empty($parameters['with_photo'])) {
            $clauses['id_main_photo'] = array('value'=> 1);
            $get_parameters['with_photo'] = $parameters['with_photo'];
        }
        if(!empty($parameters['seller'])) {
            $clauses['id_seller'] = array('value'=> $parameters['seller']);
            $get_parameters['seller'] = $parameters['seller'];
        }
        if(!empty($parameters['agency'])) {
            if($estate_type != 'zhiloy_kompleks'){
                //админ агентства
                $admin = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE id = ? AND agency_admin = ? AND id_agency > 0", $parameters['agency'], 1);
                if(!empty($admin)) { //список сотрудников данного агентства
                    $users = $db->fetchall("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $admin['id_agency']);
                    $ids = array();
                    foreach($users as $k=>$user) $ids[] = $user['id'];
                    $clauses['id_user'] = array('set'=> $ids);
                } else $clauses['id_user'] = array('value'=> $parameters['agency']);
                $get_parameters['agency'] = $parameters['agency'];
                //лимит объектов на странице для агентств (убираем лимит 5 на странице)
                $premium_count = 99999;
            } else {
                // ЖК агентства
                $agency = $db->fetch("SELECT 
                                        ".$sys_tables['agencies'].".payed_page
                                        , ".$sys_tables['agencies'].".id
                                      FROM  ".$sys_tables['agencies']."
                                      LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                      WHERE ".$sys_tables['users'].".id = ?
                ", $parameters['agency']);
                if($agency['payed_page'] == 2){ // обычная карточка - выводим только застройщиков
                   $parameters['developer'] = $parameters['agency'];
                   unset($parameters['agency']);
                } else { // для платной выводим и застройщиков и продавцов
                    $users = $db->fetchall("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $agency['id']);
                    $ids = array();
                    foreach($users as $k=>$user) $ids[] = $user['id'];
                    $clauses['id_user-id_seller'] = array('concate'=> 
                                                        array(
                                                            'id_user' => $ids,
                                                            'id_seller' => $ids
                                                        )
                    );
                    
                }
            }
        }
        if(!empty($parameters['exclude_agency'])) {
            $clauses['id_user'] = array('not_value'=> $parameters['exclude_agency']);
        }
        if(!empty($parameters['exclude_id'])) {
            $clauses['id'] = array('not_value'=> $parameters['exclude_id']);
        }
        if(!empty($parameters['build_complete'])) {
            $build_complete_values = explode(',', $parameters['build_complete']);
            //получение списка кварталов при выборе года
            $build_complete = $db->fetchall("SELECT * FROM ".$sys_tables['build_complete']." WHERE id IN (".$parameters['build_complete'].")");
            //выбран ГОД


            if(!empty($build_complete)){
                $decades = array();
                foreach($build_complete as $bc => $bi){
                    if(empty($bi['decade'])){
                        $complete_descades = $db->fetchall("SELECT * FROM ".$sys_tables['build_complete']." WHERE year = ?", false, $bi['year']);
                        if(!empty($complete_descades)){
                            foreach($complete_descades as $k=>$item) $decades[] = $item['id'];
                        }
                    } else $decades[] = $bi['id'];
                }
            }
            if($estate_type == 'build'){ //новостройка
                $clauses['id_build_complete'] = !empty($decades) ? array('set'=> $decades) : array('set'=> $build_complete_values);
            } else if($estate_type == 'zhiloy_kompleks') {  // ЖК
                if(!empty($decades)) $queries = $db->fetchall("SELECT * FROM ".$sys_tables['housing_estates_queries']." WHERE id_build_complete IN (".implode(',', $decades).")");
                else $queries = $db->fetchall("SELECT * FROM ".$sys_tables['housing_estates_queries']." WHERE id_build_complete IN (".$parameters['build_complete'].")");
                if(!empty($queries)){
                    $ids = array();
                    foreach($queries as $k=>$item) $ids[] = $item['id_parent'];
                    $clauses['id'] = array('set'=> $ids);
                } else  $clauses['id'] = array('value'=> 0);
            }
            $get_parameters['build_complete'] = $parameters['build_complete'];
        }
        
        if(!empty($parameters['geodata_selected'])) {
            if($parameters['geodata_selected'] == 'districts') $clauses['id_district']['from'] = 0;
            elseif($parameters['geodata_selected'] == 'district_areas') {
                $clauses['id_region'] = array('value'=> 47);
                $clauses['id_area']['from'] = 0;
            }
            elseif($parameters['geodata_selected'] == 'subways') $clauses['id_subway']['from'] = 0;
            $get_parameters['geodata_selected'] = $parameters['geodata_selected'];
        }            
        
        if(!empty($parameters['regions'])) {
            $clauses['id_region'] = array('value'=> $parameters['regions']);
            $get_parameters['regions'] = $parameters['regions'];
        }
        if(!empty($parameters['way_time'])) {
            $clauses['way_time'] = array('value'=> $parameters['way_time']);
            $get_parameters['way_time'] = $parameters['way_time'];
        }
        if(!empty($parameters['way_type'])) {
            $clauses['id_way_type'] = array('value'=> $parameters['way_type']);
            $get_parameters['way_type'] = $parameters['way_type'];
        }
        if(!empty($parameters['building_type'])) {
            $clauses['id_building_type'] = array('value'=> $parameters['building_type']);
            $get_parameters['building_type'] = $parameters['building_type'];
        }
        if(!empty($parameters['elevator'])) {
            $clauses['id_elevator'] = array('value'=> $parameters['elevator']);
            $get_parameters['elevator'] = $parameters['elevator'];
        }
        if(!empty($parameters['facing'])) {
            $clauses['id_facing'] = array('value'=> $parameters['facing']);
            $get_parameters['facing'] = $parameters['facing'];
        }
        if(!empty($parameters['decoration'])) {
            $clauses['id_decoration'] = array('value'=> $parameters['decoration']);
            $get_parameters['decoration'] = $parameters['decoration'];
        }
        if(!empty($parameters['toilet'])) {
            $clauses['id_toilet'] = array('value'=> $parameters['toilet']);
            $get_parameters['toilet'] = $parameters['toilet'];
        }
        if(!empty($parameters['balcon'])) {
            $clauses['id_balcon'] = array('value'=> $parameters['balcon']);
            $get_parameters['balcon'] = $parameters['balcon'];
        }
        if(!empty($parameters['class'])) {
            $clauses['class'] = array('value'=> $parameters['class']);
            $get_parameters['class'] = $parameters['class'];
        }
        if(!empty($parameters['user_objects'])) {
            //1-частные, 2-агентства
            if($parameters['user_objects'] <= 2) $list = $db->fetchall("SELECT id FROM ".$sys_tables['users']." WHERE id_agency > 0");
            //3-от застройщика
            else if($parameters['user_objects'] == 3) $list = $db->fetchall("SELECT DISTINCT(id_user) as id FROM ".$sys_tables['housing_estates']." WHERE published = 1 AND id_user > 0");    
            $ids = array();
            foreach($list as $k=>$item) $ids[] = $item['id'];
            $clauses['id_user'] = array( ( $parameters['user_objects'] == 1 ? 'not_set' : 'set' ) => $ids);
            $get_parameters['user_objects'] = $parameters['user_objects'];
        }
        if(!empty($parameters['heating'])) {
            if($estate_type == 'commercial') $clauses['heating'] = array('value'=> $parameters['heating']);
            else $clauses['id_heating'] = array('value'=> $parameters['heating']);
            $get_parameters['heating'] = $parameters['heating'];
        }
        if(!empty($parameters['id_heating'])) {
            if($estate_type == 'commercial') $clauses['heating'] = array('value'=> $parameters['id_heating']);
            else $clauses['id_heating'] = array('value'=> $parameters['id_heating']);
            $get_parameters['id_heating'] = $parameters['id_heating'];
        }
        if(!empty($parameters['id_electricity'])) {
            if($estate_type == 'commercial') $clauses['electricity'] = array('value'=> $parameters['id_electricity']);
            else $clauses['id_electricity'] = array('value'=> $parameters['id_electricity']);
            $get_parameters['id_electricity'] = $parameters['id_electricity'];
        }
        if(!empty($parameters['electricity'])) {
            if($estate_type == 'commercial') $clauses['electricity'] = array('value'=> $parameters['electricity']);
            else $clauses['id_electricity'] = array('value'=> $parameters['electricity']);
            $get_parameters['electricity'] = $parameters['electricity'];
        }            
        if(!empty($parameters['enter'])) {
            $clauses['id_enter'] = array('value'=> $parameters['enter']);
            $get_parameters['enter'] = $parameters['enter'];
        }
        if(!empty($parameters['water_supply'])) {
            $clauses['id_water_supply'] = array('value'=> $parameters['water_supply']);
            $get_parameters['water_supply'] = $parameters['water_supply'];
        }
        if(!empty($parameters['ceiling_height_to']) || !empty($parameters['ceiling_height_from'])) { 
            $clauses['ceiling_height'] = array();
            if(!empty($parameters['ceiling_height_from'])) {
                $clauses['ceiling_height']['from'] = $parameters['ceiling_height_from'];
                $get_parameters['ceiling_height_from'] = $parameters['ceiling_height_from'];
            }
            if(!empty($parameters['ceiling_height_to'])) {
                $clauses['ceiling_height']['to'] = $parameters['ceiling_height_to'];
                $get_parameters['ceiling_height_to'] = $parameters['ceiling_height_to'];
            }
        }
        if(!empty($parameters['phones_count_to']) || !empty($parameters['phones_count_from'])) { 
            $clauses['phones_count'] = array();
            if(!empty($parameters['phones_count_from'])) {
                $clauses['phones_count']['from'] = $parameters['phones_count_from'];
                $get_parameters['phones_count_from'] = $parameters['phones_count_from'];
            }
            if(!empty($parameters['phones_count_to'])) {
                $clauses['phones_count']['to'] = $parameters['phones_count_to'];
                $get_parameters['phones_count_to'] = $parameters['phones_count_to'];
            }
        }
        if(!empty($parameters['parking'])) {
            $clauses['parking'] = array('value'=> $parameters['parking']);
            $get_parameters['parking'] = $parameters['parking'];
        }
        if(!empty($parameters['security'])) {
            $clauses['security'] = array('value'=> $parameters['security']);
            $get_parameters['security'] = $parameters['security'];
        }                                                            
        
        if(!empty($parameters['bathroom'])) {
            $clauses['id_bathroom'] = array('value'=> $parameters['bathroom']);
            $get_parameters['bathroom'] = $parameters['bathroom'];
        }
        if(!empty($parameters['ownership'])) {
            $clauses['id_ownership'] = array('value'=> $parameters['ownership']);
            $get_parameters['ownership'] = $parameters['ownership'];
        }
        if(!empty($parameters['gas'])) {
            $clauses['id_gas'] = array('value'=> $parameters['gas']);
            $get_parameters['gas'] = $parameters['gas'];
        }                                    
        if(!empty($parameters['group_id'])) {
            $clauses['group_id'] = array('value'=> $parameters['group_id']);
            $get_parameters['group_id'] = $parameters['group_id'];
        }                                    
        //ЖК 
        if(!empty($parameters['housing_estate'])) {
            if(!empty($estate_object)) $clauses['id_housing_estate'] = array('value'=> $parameters['housing_estate']);
            else  $clauses['id'] = array('value'=> $parameters['housing_estate']);
            $get_parameters['housing_estate'] = $parameters['housing_estate'];
            $housing_estate = $db->fetch("SELECT `title` FROM ".$sys_tables['housing_estates']." WHERE id = ?", $get_parameters['housing_estate']);
            $get_parameters['housing_estate_title'] = $housing_estate['title'];
        }
        //БЦ
        if(!empty($parameters['business_center'])) {
            if(!empty($estate_object)) $clauses['id_business_center'] = array('value'=> $parameters['business_center']);
            else $clauses['id'] = array('value'=> $parameters['business_center']);
            $get_parameters['business_center'] = $parameters['business_center'];
            $business_center = $db->fetch("SELECT `title` FROM ".$sys_tables['business_centers']." WHERE id = ?", $get_parameters['business_center']);
            $get_parameters['business_center_title'] = $business_center['title'];
        }
        //КП
        if(!empty($parameters['cottage'])) {
            if(!empty($estate_object)) $clauses['id_cottage'] = array('value'=> $parameters['cottage']);
            else $clauses['id'] = array('value'=> $parameters['cottage']);
            $get_parameters['cottage'] = $parameters['cottage'];
            $cottage = $db->fetch("SELECT `title` FROM ".$sys_tables['cottages']." WHERE id = ?", $get_parameters['cottage']);
            $get_parameters['cottage_title'] = $cottage['title'];
        }
        //address
        if(!empty($parameters['geodata'])) {
            $geodata = $db->fetch("SELECT * FROM ".$sys_tables['geodata']." WHERE id = ?",$parameters['geodata']);
            //поиск в радиусе от улицы
            if(!empty($parameters['radius_geo_id'])){
                
                if(!empty($geodata) && $geodata['lat_center'] > 0 && $geodata['lng_center'] > 0){
                    $lat = $geodata['lat_center'];
                    $lng = $geodata['lng_center'];
                    //поиск в радиусе 1 км
                    $R = 6371;  // earth's radius, km 
                    $max_distance = 1;
                    // first-cut bounding box (in degrees) 
                    $parameters = array();
                    $parameters['top_left_lat'] = $lat + rad2deg($max_distance/$R); 
                    $parameters['bottom_right_lat'] = $lat - rad2deg($max_distance/$R); 
                    // compensate for degrees longitude getting smaller with increasing latitude 
                    $parameters['bottom_right_lng'] = $lng + rad2deg($max_distance/$R/cos(deg2rad($lat))); 
                    $parameters['top_left_lng'] = $lng - rad2deg($max_distance/$R/cos(deg2rad($lat)));  
                }                
            } else {
                $clauses['id_region'] = array('value'=> $geodata['id_region']);
                $clauses['id_area'] = array('value'=> $geodata['id_area']);
                //город - 3 уровень
                if($geodata['a_level']>=3) $clauses['id_city'] = array('value'=> $geodata['id_city']);
                //местность - 4 уровень
                if($geodata['a_level']>=4) $clauses['id_place'] = array('value'=> $geodata['id_place']);
                //улицы - 5 уровень
                if($geodata['a_level']>=5) $clauses['id_street'] = array('value'=> $geodata['id_street']);
                $get_parameters['geodata'] = $parameters['geodata'];
                $get_parameters['address'] = $geodata['shortname_cut'].' .'.$geodata['offname'];
            }
        }
        
        if(empty($parameters['housing_estate']) && empty($parameters['cottage']) && empty($parameters['business_center'])){
            if(!empty($parameters['top_left_lat']) || !empty($parameters['bottom_right_lat'])) { 
                $clauses['lat'] = array();
                if(!empty($parameters['top_left_lat'])) {
                    $clauses['lat']['to'] = $parameters['top_left_lat'];
                    $get_parameters['top_left_lat'] = $parameters['top_left_lat'];
                }
                if(!empty($parameters['bottom_right_lat'])) {
                    $clauses['lat']['from'] = $parameters['bottom_right_lat'];
                    $get_parameters['bottom_right_lat'] = $parameters['bottom_right_lat'];
                }
            }
            if(!empty($parameters['top_left_lng']) || !empty($parameters['bottom_right_lng'])) { 
                $clauses['lng'] = array();
                if(!empty($parameters['bottom_right_lng'])) {
                    $clauses['lng']['from'] = $parameters['top_left_lng'];
                    $get_parameters['top_left_lng'] = $parameters['top_left_lng'];
                }
                if(!empty($parameters['bottom_right_lng'])) {
                    $clauses['lng']['to'] = $parameters['bottom_right_lng'];
                    $get_parameters['bottom_right_lng'] = $parameters['bottom_right_lng'];
                }
            }
        }
        if(!empty($parameters['lat'])) {
            $clauses['lat'] = array('value'=> $parameters['lat']);
            $get_parameters['lat'] = $parameters['lat'];
        }
        if(!empty($parameters['lng'])) {
            $clauses['lng'] = array('value'=> $parameters['lng']);
            $get_parameters['lng'] = $parameters['lng'];
        }        
                
        if(!empty($elite)) {
            $clauses['elite'] = array('value'=> 1);
            $get_parameters['elite']  = 1;
        }
        if(!empty($parameters['by_the_day'])) {
            $clauses['by_the_day'] = array('value'=> 1);
            $get_parameters['by_the_day'] = $parameters['by_the_day'];
        }
        if(!empty($parameters['only_users'])) {
            $clauses['only_users'] = array('value'=> 1);
            $get_parameters['only_users'] = $parameters['only_users'];
        }

        if(!empty($parameters['only_photo'])) {
            $clauses['id_main_photo']['from'] = array('value'=> 1);
            $get_parameters['only_photo'] = $parameters['only_photo'];
        }
        
        if(!empty($parameters['contractor'])) {
            $clauses['contractor'] = array('value'=> 1);
            $get_parameters['contractor'] = 1;
        } 
        
        if(!empty($parameters['asignment'])) {
            $clauses['asignment'] = array('value'=> 1);
            $get_parameters['asignment'] = 1;
        } 
        
        if(!empty($parameters['published'])) {
            $clauses['published'] = array('value'=> $parameters['published']);
            $get_parameters['published'] = $parameters['published'];
        } 
        
        //ЖК

        if(!empty($parameters['low_rise'])) {
            $clauses['low_rise'] = array('value'=> 1);
            $get_parameters['low_rise'] = 1;
        } 
        if(!empty($parameters['214_fz'])) {
            $clauses['214_fz'] = array('value'=> 1);
            $get_parameters['214_fz'] = 1;
        } 

        if(!empty($parameters['apartments'])) {
            $clauses['apartments'] = array('value'=> 1);
            $get_parameters['apartments'] = 1;
        } 

        if(!empty($parameters['developer'])) {
            $clauses['id_user-id_seller'] = array('concate'=> 
                                                array(
                                                    'id_user' => $parameters['developer'],
                                                    'id_seller' => $parameters['developer']
                                                )
            );

            
            $get_parameters['developer'] = $parameters['developer'];
            $developer = $db->fetch("SELECT 
                                        ".$sys_tables['agencies'].".`title` 
                                     FROM ".$sys_tables['agencies']." 
                                     RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                     WHERE ".$sys_tables['users'].".id = ?", 
                                     $get_parameters['developer']
            );
            $get_parameters['developer_title'] = $developer['title'];
        }

        if(!empty($parameters['class'])) {
            if($estate_type == 'zhiloy_kompleks'){ 
                $clauses['class'] = array('value'=> $parameters['class']);
                $get_parameters['class'] = $parameters['class'];
            } else {
                $get_parameters['class'] = $parameters['class'];
                $parameters['class'] = str_replace('bplus','b+',$parameters['class']);
                $clauses['class'] = array('set'=> explode(',',$parameters['class']));
                foreach($clauses['class']['set'] as $val) {
                    $get_parameters['class_checked'][$val] = true; 
                }
            }
        }
        if($estate_type == 'zhiloy_kompleks'){
            if(!empty($parameters['advanced'])){
                $clauses['advanced'] = array('value'=> $parameters['advanced']);
                $get_parameters['advanced'] = $parameters['advanced'];
            }                           
        }
        if($estate_type == 'cottage' || $estate_type == 'cottedzhnye_poselki'){
            if(!empty($parameters['direction'])) {
                $clauses['id_direction'] = array('value'=> $parameters['direction']);
                $get_parameters['direction'] = $parameters['direction'];
            }
            if(!empty($parameters['min_range']) || !empty($parameters['max_range'])) { 
                $clauses['cad_length'] = array();
                if(!empty($parameters['min_range'])) {
                    $clauses['cad_length']['from'] = $parameters['min_range'];
                    $get_parameters['min_range'] = $parameters['min_range'];
                }
                if(!empty($parameters['max_range'])) {
                    $clauses['cad_length']['to'] = $parameters['max_range'];
                    $get_parameters['max_range'] = $parameters['max_range'];
                }
            }
            
            if(!empty($parameters['min_sqear']) || !empty($parameters['min_sqear'])) { 
                $clauses['u_sb'] = array();
                if(!empty($parameters['min_sqear'])) {
                    $clauses['u_sb']['from'] = $parameters['min_sqear'];
                    $get_parameters['min_sqear'] = $parameters['min_sqear'];
                }
                if(!empty($parameters['max_sqear'])) {
                    $clauses['u_sb']['to'] = $parameters['max_sqear'];
                    $get_parameters['max_sqear'] = $parameters['max_sqear'];
                }
            }
            
            if(!empty($parameters['object_type'])){
                $get_parameters['object_type'] = $parameters['object_type'];
                switch($parameters['object_type']){
                    case 1: // участки
                    case 0: 
                        if(!empty($parameters['object_type'])){
                            $clauses['u_count']['from'] = 1;
                            $clauses['u_cost_ub'] = $clauses['u_cost_ue'] = array();
                        }
                        if(!empty($parameters['min_cost']) && !empty($parameters['max_cost'])) { 
                            if($parameters['min_cost']==$parameters['max_cost']){
                                $clauses['u_cost_ue']['from'] = $parameters['min_cost'];    
                                $clauses['u_cost_ub']['to'] = $parameters['min_cost'];    
                            } else {
                                $clauses['u_cost_ue']['from'] = $parameters['max_cost'];    
                                $clauses['u_cost_ub']['to'] = $parameters['min_cost'];    
                            } 
                            $get_parameters['min_cost'] = $parameters['min_cost'];
                            $get_parameters['max_cost'] = $parameters['max_cost'];
                        } else {
                            if(!empty($parameters['min_cost'])) {
                                $clauses['u_cost_ue']['from'] = $parameters['min_cost'];
                                $get_parameters['min_cost'] = $parameters['min_cost'];
                            }
                            if(!empty($parameters['max_cost'])) {
                                $clauses['u_cost_ub']['to'] = $parameters['max_cost'];
                                $get_parameters['max_cost'] = $parameters['max_cost'];
                            }
                        }
                        //определение типа вывода цены в списке
                        Response::SetString('price_from','u_cost_ub');
                        Response::SetString('price_to','u_cost_ue');
                        if(!empty($parameters['object_type'])) break;
                    case 2: // коттеджи
                    case 0: // коттеджи
                        if(!empty($parameters['object_type'])){
                            $clauses['c_count']['from'] = 1;
                            $clauses['c_cost_cb'] = $clauses['c_cost_ce'] = array();
                        }
                        if(!empty($parameters['min_cost']) && !empty($parameters['max_cost'])) { 
                            if($parameters['min_cost']==$parameters['max_cost']){
                                $clauses['c_cost_ce']['from'] = $parameters['min_cost'];    
                                $clauses['c_cost_cb']['to'] = $parameters['min_cost'];    
                            } else {
                                $clauses['c_cost_ce']['from'] = $parameters['max_cost'];    
                                $clauses['c_cost_cb']['to'] = $parameters['min_cost'];    
                            } 
                            $get_parameters['min_cost'] = $parameters['min_cost'];
                            $get_parameters['max_cost'] = $parameters['max_cost'];
                        } else {
                            if(!empty($parameters['min_cost'])) {
                                $clauses['c_cost_ce']['from'] = $parameters['min_cost'];
                                $get_parameters['min_cost'] = $parameters['min_cost'];
                            }
                            if(!empty($parameters['max_cost'])) {
                                $clauses['c_cost_cb']['to'] = $parameters['max_cost'];
                                $get_parameters['max_cost'] = $parameters['max_cost'];
                            }
                        }
                        if(!empty($parameters['min_sqear']) || !empty($parameters['min_sqear'])) { 
                            $clauses['c_csb'] = array();
                            if(!empty($parameters['min_sqear'])) {
                                $clauses['c_csb']['from'] = $parameters['min_sqear'];
                                $get_parameters['min_sqear'] = $parameters['min_sqear'];
                            }
                            if(!empty($parameters['max_sqear'])) {
                                $clauses['c_csb']['to'] = $parameters['max_sqear'];
                                $get_parameters['max_sqear'] = $parameters['max_sqear'];
                            }
                        }
                        Response::SetString('price_from','c_cost_cb');
                        Response::SetString('price_to','c_cost_ce');
                        if(!empty($parameters['object_type'])) break;
                    case 3: // таунхаусы
                    case 0: // таунхаусы
                        if(!empty($parameters['object_type'])){
                            $clauses['t_count']['from'] = 1;
                            $clauses['t_cost_b'] = $clauses['t_cost_e'] = array();
                        }
                        if(!empty($parameters['min_cost']) && !empty($parameters['max_cost'])) { 
                            if($parameters['min_cost']==$parameters['max_cost']){
                                $clauses['t_cost_e']['from'] = $parameters['min_cost'];    
                                $clauses['t_cost_b']['to'] = $parameters['min_cost'];    
                            } else {
                                $clauses['t_cost_e']['from'] = $parameters['max_cost'];    
                                $clauses['t_cost_b']['to'] = $parameters['min_cost'];    
                            } 
                            $get_parameters['min_cost'] = $parameters['min_cost'];
                            $get_parameters['max_cost'] = $parameters['max_cost'];
                        } else {
                            if(!empty($parameters['min_cost'])) {
                                $clauses['t_cost_e']['from'] = $parameters['min_cost'];
                                $get_parameters['min_cost'] = $parameters['min_cost'];
                            }
                            if(!empty($parameters['max_cost'])) {
                                $clauses['t_cost_b']['to'] = $parameters['max_cost'];
                                $get_parameters['max_cost'] = $parameters['max_cost'];
                            }
                        }
                        if(!empty($parameters['min_sqear']) || !empty($parameters['min_sqear'])) { 
                            $clauses['t_tsb'] = array();
                            if(!empty($parameters['min_sqear'])) {
                                $clauses['t_tsb']['from'] = $parameters['min_sqear'];
                                $get_parameters['min_sqear'] = $parameters['min_sqear'];
                            }
                            if(!empty($parameters['max_sqear'])) {
                                $clauses['t_tsb']['to'] = $parameters['max_sqear'];
                                $get_parameters['max_sqear'] = $parameters['max_sqear'];
                            }
                        }
                        Response::SetString('price_from','t_cost_b');
                        Response::SetString('price_to','t_cost_e');
                        if(!empty($parameters['object_type'])) break;
                    case 4: // квартиры
                    case 0: // квартиры
                        if(!empty($parameters['object_type'])){
                            $clauses['k_count']['from'] = 1;
                            $clauses['k_cost_b'] = $clauses['k_cost_e'] = array();
                        }
                        if(!empty($parameters['min_cost']) && !empty($parameters['max_cost'])) { 
                            if($parameters['min_cost']==$parameters['max_cost']){
                                $clauses['k_cost_e']['from'] = $parameters['min_cost'];    
                                $clauses['k_cost_b']['to'] = $parameters['min_cost'];    
                            } else {
                                $clauses['k_cost_e']['from'] = $parameters['max_cost'];    
                                $clauses['k_cost_b']['to'] = $parameters['min_cost'];    
                            } 
                            $get_parameters['min_cost'] = $parameters['min_cost'];
                            $get_parameters['max_cost'] = $parameters['max_cost'];
                        } else {
                            if(!empty($parameters['min_cost'])) {
                                $clauses['k_cost_e']['from'] = $parameters['min_cost'];
                                $get_parameters['min_cost'] = $parameters['min_cost'];
                            }
                            if(!empty($parameters['max_cost'])) {
                                $clauses['k_cost_b']['to'] = $parameters['max_cost'];
                                $get_parameters['max_cost'] = $parameters['max_cost'];
                            }
                        }
                        if(!empty($parameters['min_sqear']) || !empty($parameters['min_sqear'])) { 
                            $clauses['k_sb'] = array();
                            if(!empty($parameters['min_sqear'])) {
                                $clauses['k_sb']['from'] = $parameters['min_sqear'];
                                $get_parameters['min_sqear'] = $parameters['min_sqear'];
                            }
                            if(!empty($parameters['max_sqear'])) {
                                $clauses['k_sb']['to'] = $parameters['max_sqear'];
                                $get_parameters['max_sqear'] = $parameters['max_sqear'];
                            }
                        }
                        Response::SetString('price_from','k_cost_b');
                        Response::SetString('price_to','k_cost_e');
                        break;
                    }                
                }                
        }
        //зарубежка
        if(!empty($parameters['inter_type_groups'])) {
            $type_groups  = $db->fetchall("SELECT * FROM ".$sys_tables['inter_type_objects']." WHERE id_group = ?", false, $parameters['inter_type_groups']);
            $type_groups_ids = array();
            foreach($type_groups as $k=>$type_group) $type_groups_ids[] = $type_group['id'];
            $clauses['id_type_object'] = array('set'=> $type_groups_ids);
            $get_parameters['inter_type_groups'] = $parameters['inter_type_groups'];
        }

        if(!empty($parameters['inter_type_objects'])) {
            $clauses['id_type_object'] = array('set'=> explode(',', $parameters['inter_type_objects']));
            $get_parameters['inter_type_objects'] = $parameters['inter_type_objects'];
        }
        
        if(!empty($parameters['country'])) {
            $clauses['id_country'] = array('set'=> explode(',',$parameters['country']));
            $get_parameters['country'] = $parameters['country'];

            $regions_list = $db->fetchall("SELECT * FROM ".$sys_tables['inter_regions']." WHERE id_country IN (".$get_parameters['country'].") ORDER BY id_country, title");
            Response::SetArray('regions_list', $regions_list);
        }
        
        if(!empty($parameters['inter_regions'])) {
            $clauses['id_region'] = array('set'=> explode(',', $parameters['inter_regions']));
            $get_parameters['inter_regions'] = $parameters['inter_regions'];
            $region_string  = $db->fetchall("SELECT * FROM ".$sys_tables['inter_regions']." WHERE id IN (".$get_parameters['inter_regions'].") ORDER BY title");
        }
        if(!empty($parameters['inter_cost'])) {
            switch($parameters['inter_cost']){
                case 1: 
                    $clauses['inter_cost']['inter_to'] = 100000;
                    break;
                case 2: 
                    $clauses['inter_cost']['inter_from'] = 100000;
                    $clauses['inter_cost']['inter_to'] = 500000;
                    break;
                case 3: 
                    $clauses['inter_cost']['inter_from'] = 500000;
                    $clauses['inter_cost']['inter_to'] = 1000000;
                    break;
                case 4: 
                    $clauses['inter_cost']['inter_from'] = 1000000;
                    break;
            }
            $get_parameters['inter_cost'] = $parameters['inter_cost'];
        }                
        if(!empty($parameters['company_page'])){
            //если у агентства брендированная страница, убираем таргет
            if( !empty( $parameters['agency'] ) )
                $notarget = $db->fetch( "SELECT (".$sys_tables['agencies'].".payed_page = 1) AS notarget 
                                        FROM ".$sys_tables['agencies']." 
                                        LEFT JOIN " . $sys_tables['users'] . " ON " . $sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                        WHERE " . $sys_tables['users'] . ".id = " . Convert::ToInt( $parameters['agency'] )
                );
            Response::SetBoolean('company_page',$parameters['company_page']);
        }
        elseif(!empty($parameters['agent_page'])){
            //если у агента брендированная страница, убираем таргет
            if(!empty($parameters['agent']))
                $notarget = $db->fetch("SELECT (".$sys_tables['users'].".payed_page = 1) AS notarget 
                                        FROM ".$sys_tables['users']." 
                                        WHERE ".$sys_tables['users'].".id = ".Convert::ToInt($parameters['agent']));
            Response::SetBoolean('company_page',$parameters['agent_page']);
        }
        
        //новые фильтры для сайдбара
        if(!empty($parameters['object_type_range']))    $range_where[] = $this->formRangeParams($estate_type, $parameters, 'object_type_range', 'rooms_sale');
        foreach($range_where as $k=>$item) if(empty($item)) unset($range_where[$k]);
        //для вывода группировки не учитывать премиум, они отдельно выводятся
        if( !empty( $parameters['group_id'] ) ) $clauses['status']['not_value'] = 4;
        ksort($get_parameters); 
        if( empty( $only_params ) ) Response::SetArray( 'form_data', $get_parameters );
        if(!empty($get_parameters['objects_group'])) unset($get_parameters['objects_group']);
        if(!empty($get_parameters['rooms_checked'])) unset($get_parameters['rooms_checked']);
        if(!empty($get_parameters['rooms_sale_checked'])) unset($get_parameters['rooms_sale_checked']);
        if(!empty($get_parameters['class_checked'])) unset($get_parameters['class_checked']);
        if(!empty($get_parameters['new_groups'])) unset($get_parameters['new_groups']);
        if(!empty($parameters['search'])) $get_parameters['search'] = $parameters['search'];
        if(empty($form_filter)) if(!empty($get_parameters['address'])) unset($get_parameters['address']);
        
        if(!empty($form_filter)){
            //формирование урлов и подсчет кол-ва объектов
            foreach($form_filter as $form_filter_key => $form_filter_list){
                foreach($form_filter_list as $k => $item){
                    list($form_filter[$form_filter_key][$k]['active'], $form_filter[$form_filter_key][$k]['url'], $form_filter[$form_filter_key]['reset_url']) = $this->makeSearchUrl($parameters, $form_filter_key, $item['id']);
                }
            }
        }
        return array($parameters, $clauses, $get_parameters, $reg_where, $range_where, !empty($form_filter) ? $form_filter : '');
    }         

    /**
    * Формирование URL'ов для левого сайдбара
    * @param array
    */       
    public function makeSearchUrl($parameters, $key, $value){
        global $requested_page;
        if(!empty($parameters['path']) ) {
            $path = $parameters['path'];
            unset($parameters['path']);
        }
        $in_params = false;
        $reset_params = $parameters;     

        if(isset($parameters[$key]) ){
            
            if( array_key_exists($key, $parameters) ){
                $array_values = is_array($parameters[$key]) ? $parameters[$key] : explode(',', $parameters[$key]);
                //поиск по выбранным значениям параметра
                foreach($array_values as $ak => $ai){
                    
                    //значение найдено - выкусываем из поиска
                    if($value == $ai) {
                        unset($array_values[$ak]);    
                        $in_params = true;
                        break;
                        
                    }
                }
                if(empty($in_params) ) {
                    if($key == 'regions') $array_values = [];
                    $array_values[] = $value;
                }
                asort($array_values);
                if(count($array_values) == 0) unset($parameters[$key]);
                else $parameters[$key] = implode(",", $array_values);
                //url без парамета $key
                if(isset($reset_params[$key]) ) {
                    unset($reset_params[$key]);
                }                
            }
        } else {
            $parameters[$key] = $value;
        }
        ksort($parameters);
        
        
        return array(
            $in_params, 
            Host::$protocol . '://'.Host::$host.'/' . (!empty($path) ? trim($path, '/') . '/?' : "" ) . (empty($parameters) ? "" : Convert::ArrayToStringGet($parameters) ),
            Host::$protocol . '://'.Host::$host.'/' . (!empty($path) ? trim($path, '/') . '/?' : "" ) . (empty($reset_params)? "": Convert::ArrayToStringGet($reset_params) )
        );
    }
    /* 
        Формирование условий поиска для интервальных (справочных) значений
        * @param string $estate_type тип недвижимости
        * @param array $parameters - get параметры
        * @param string $key - параметр
        * @param string $value - значение
        * @param int $koef - коэффициент увеличения
        * @param array $new_parameters список подзапросов         
    */
    public function formRangeParams($estate_type, $parameters, $key, $field, $koef = 1){
        global $db;
        $range_where = [];
        $ranges_list = $db->fetchall("SELECT * FROM ".$this->tables['estate_search_params']." WHERE id IN (".$parameters[$key].") ORDER BY from_value, to_value, value");
        foreach($ranges_list as $k=>$item){
            if($item['prefix'] == 'Га') $koef = 100;
            if(!empty($item['value']) ) $range_where[] = $this->tables[$estate_type].".`".$field."` = ".$item['value'] * $koef; // фиксированное значение
            else {
                if(empty($item['from_value']) || Convert::ToInt($item['from_value']) == 0){ //нет значения ОТ
                    if(!empty($item['to_value']) && Convert::ToInt($item['to_value']) > 0) $range_where[] = $this->tables[$estate_type].".`".$field."` <= ".$item['to_value'] * $koef; 
                } else {
                    if(!empty($item['to_value']) && Convert::ToInt($item['to_value']) > 0) $range_where[] = "( " . $this->tables[$estate_type].".`".$field."` >= ".$item['from_value'] * $koef . " AND " . $this->tables[$estate_type].".`".$field."` <= ".$item['to_value'] * $koef ." ) "; 
                    else $range_where[] = $this->tables[$estate_type].".`".$field."` >= ".$item['from_value'] * $koef; 
                }
            }
        }
        if(!empty($range_where) ) return " ( " . implode( " OR ", $range_where) . " ) ";
        return false;
    }     
}
?>
