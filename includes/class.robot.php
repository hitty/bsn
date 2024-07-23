<?php
class SuggestClient {
    private $url,
            $token,
            $tokens = array( 
                '51e04b88cbd036cb9ee29562354c1ca811d471f5',
                '4c5c11b2c5d3fd1f381b84e5af84031cf03d4acd',
                '07f2be1c441dae812b47dba057509f0625c2ca77'
            );
    
    public function __construct( $url = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/') {
        $index = mt_rand( 0, count( $this->tokens ) - 1 );
        $this->token = $this->tokens[ $index ];
        $this->url = $url;
    }
    
    public function suggest($resource, $data) {
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => array(
                    'Content-type: application/json',
                    'Authorization: Token ' . $this->token,
                    ),
                'content' => json_encode($data),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($this->url . $resource, false, $context);
        return json_decode($result);
    }    
}
/**
* Конвертация обработанных строк/нодов файлов в поля объектов недвижимости
*/
class Robot {
    // таблицы для списков типов недвижимости
    public $estate_type = '';          // тип недвижимости
    public $fields = [];          // поля объекта 
    public $sys_tables = [];      // таблицы 
    public $object_statuses = []; //типы выделений
    const photos_limit = 20;
    
    public function __construct($id_user=0){
        global $db;
        // подключение таблицы
        $this->sys_tables = Config::Get('sys_tables');
        //дата изменения
        $this->fields['date_change'] = date('Y-m-d H:i:s');
        $this->fields['id_user'] =  $id_user;
        $this->fields['id_street'] = 0;
        $this->fields['id_city'] = 0;
        $this->fields['id_place'] = 0;
        $this->fields['id_district'] = 0;
        $this->fields['id_area'] = 0;
        $this->fields['house'] = 0;
        $this->fields['corp'] = 0;
        
        $this->object_statuses = $db->fetchall("SELECT * FROM ".$this->sys_tables['objects_statuses']);
        //читаем типы локаций и их a_level
        $this->exploders = [];
        $this->exploders_cut = [];
        $this->exploders_with_levels = $db->fetchall("SELECT shortname,shortname_cut,MIN(a_level) as level FROM ".$this->sys_tables['geodata']." GROUP BY shortname");
        //составляем списки типов локаций и сокращенных типов локаций
        foreach($this->exploders_with_levels as $object){
            $this->exploders[] = $object['shortname'];
            $this->exploders_cut[] = $object['shortname_cut'];
        }
    }
    /**
    * получение типа недвижимости и типа объекта 
    * @param integer $id_type - тип объекта в xml-файле
    * @return string
    */
    public function getEstateType($id_type){
        global $db;
        if(is_string($id_type)) $id_type = str_replace(array(".",",","/"),"",$id_type);
        $type_objects = array(
                            'live'=>'information.type_objects_live',    
                            'commercial'=>'information.type_objects_commercial',
                            'country'=>'information.type_objects_country'
                            );       
        foreach($type_objects as $type=>$table){
            //поиск по всем типам  объектов
            $item = $db->fetch("SELECT `id` FROM ".$table." WHERE ".$this->file_format."_value = ?", $id_type);
            if(!empty($item)) {
                //тип недвижимости
                $this->estate_type = $type;
                //тип объекта
                return $item['id'];
            }
        }
        //для коммерческой тип указан в отдельном поле, его прочтем потом
        if($id_type == 'коммерческая' || $id_type == 'commercial') $this->estate_type = "commercial";
        
        return false;
    }
    /**
    * получение значения из базы по значению из xml 
    * @param string $table - таблица
    * @param mixed $value - значение
    * @param string $field - поле поиска
    * @param boolean $complex - флаг, что в поле таблицы может быть несколько значений (v1#v2#v3...)
    * @return string
    */    
    
    public function getInfoFromTable($table,$values,$fields = false,$complex = false,$field_to_return = false,$unstrict = false){
       global $db;
       /*
       if(!is_array($values)){
           if( empty( $fields)) $fields = $this->file_format."_value";
           if( empty( $complex)) $res = $db->fetch("SELECT * FROM ".$table." WHERE ".$fields." = ?",$values);
           else $res = $db->fetch("SELECT * FROM ".$table." WHERE ".$fields." REGEXP '^.*#?(".$values.")(#.*)*'");
           return $res;
       }else{
           $condition = [];
           foreach($values as $key=>$item){
               if( empty( $fields[$key])) return false;
               $condition = $fields[$key]." = ?";
           }
       }
       */
       if(!empty($unstrict) && is_string($values)) $values = ( empty( $complex) ? "%".$values."%" : ".*".$values.".*");
       if( empty( $fields)) $fields = $this->file_format."_value";
       if( empty( $complex)) $res = $db->fetch("SELECT * FROM ".$table." WHERE ".$fields." ".(!empty($unstrict) ? "LIKE" : "=")." ?",$values);
       else $res = $db->fetch("SELECT * FROM ".$table." WHERE ".$fields." REGEXP '^.*#?(".$values.")(#.*)*'");
       
       if(!empty($field_to_return)) $res = ( empty( $res[$field_to_return]) ? false : $res[$field_to_return]);
       return $res;
    }
    /**
    * преобразование цены в зависимости от типа
    * @param integer $value - тип цены
    * @return integer
    */    
    public function convertCost($value){
        global $values;
        if(is_array($value)) $value = array_shift($value);
        $value = str_replace(array(" ",".",",","/"),"",$value);
        $cost_type = $this->getInfoFromTable($this->sys_tables['cost_types'],$value);
        if($cost_type['id']==3) $this->fields['by_the_day'] =  1; //тыс.руб
        elseif($cost_type['id']==5 || $cost_type['id']==10) $this->fields['cost'] =  $this->fields['cost']*1000; //тыс.руб
        elseif(($cost_type['id']==6 || $cost_type['id']==7) && ($this->estate_type=='build'||$this->estate_type=='commercial')) {  //стоимость за м2 (только для стройки и коммерческой)
            if($cost_type['id']==10) $this->fields['cost'] = $this->fields['cost']*1000;
            $this->fields['cost2meter'] = $this->fields['cost'];
            if(!empty($this->fields['square_full'])) $this->fields['cost'] = $this->fields['square_full']*$this->fields['cost2meter'];
            elseif(!empty($values['so'])) $this->fields['cost'] = $values['so']*$this->fields['cost2meter'];
            elseif(!empty($values['su'])) $this->fields['cost'] = $values['su']*$this->fields['cost2meter'];
        }
        elseif(($cost_type['id']==8 || $cost_type['id']==9) && ($this->estate_type=='country' || $this->estate_type=='commercial')) {  //стоимость за сот (загородка и коммерческая)
            if(!empty($this->fields['square_ground'])) {
                $this->fields['cost'] = $this->fields['square_ground']*$this->fields['cost'];
                if($cost_type['id']==9) $this->fields['cost'] = $this->fields['cost'] / 100 ; //стоимость р/Га
            }
        }
        
    }
    /**
    * проверка загружаемых фотографий для объекта
    * @param array $photos - список фотграфий
    * @param integer $id - id объекта
    * @return array of array
    */
    public function getPhotoList($photos, $id,$suffix=null){
        global $db;
        //Фотографии которрых НЕТ в базе
        $photos_out = $photos_in = [];
        if($id < 1) return array([],$photos);
        $counter = 0;
        foreach($photos as $key=>$name){
            if($counter>=$this::photos_limit) break;
            $photo = $db->fetch("SELECT * FROM ".$this->sys_tables[$this->estate_type.'_photos']." WHERE `id_parent".$suffix."` = ? AND `external_img_src` = ?",$id,$name);
            if( empty( $photo)) {
                $photo_similar = $db->fetch("SELECT * FROM ".$this->sys_tables[$this->estate_type.'_photos']." WHERE `external_img_src` = ?",$name);
                if(!empty($photo_similar)) {
                    $db->querys("INSERT INTO ".$this->sys_tables[$this->estate_type.'_photos']." SET `id_parent".$suffix."` = ?, `external_img_src` = ?, name=?",$id, $name, $photo_similar['name']);
                    $photos_in[] = "'".$photo_similar['external_img_src']."'";
                }
                else $photos_out[] = $name;
            } else $photos_in[] = "'".$name."'";
            $counter++;
        }
        return array($photos_in, $photos_out);
    }   
    
    public function checkPhoto($name){
        if(is_array($name)) return false;
        else $fileinfo = pathinfo($name);
        $is_image = !empty($fileinfo['extension']) && preg_match( '#[jpg|jpeg|gif|png]{1,4}#msui', strtolower($fileinfo['extension']) );
        if( !empty( $is_image ) ) return true;
        $image_size = getimagesize($name);
        return !empty($image_size['mime']) && preg_match( '#[jpg|jpeg|gif|png]{1,4}#msui', strtolower($image_size['mime']) );
        
    }
    public function getClearPhotoList($photos_urls,$id,$suffix=false){
        global $db;
        $photos_urls = array_values($photos_urls);
        $photos_to_delete = $photos_to_add = [];
        $photos_to_delete = $db->fetchall("SELECT id,external_img_src FROM ".$this->sys_tables[$this->estate_type.'_photos']." WHERE id_parent".$suffix." = ?",'id',$id);
        $k = 0;
        //фотки уже в базе - претенденты на удаление
        $photos_in_base_amount = count($photos_to_delete);
        //перебираем, смотрим совпадающие с фотками из xml, их не трогаем
        foreach($photos_to_delete as $key=>$item){
            if( empty( $photos_urls) || $k >= $this::photos_limit) break;
            //убираем фотки которые уже есть
            $photos_urls_key = array_search($item['external_img_src'],$photos_urls);
            if($photos_urls_key !== false){
                unset($photos_urls[$photos_urls_key]);
                unset($photos_to_delete[$key]);
            }
            ++$k;
        }
        //убираем из их количества те которые будем удалять
        $photos_in_base_amount -= count($photos_to_delete);
        //заведомо добавляем не больше 20 фоток
        $photos_urls = array_slice(array_values($photos_urls),0, $this::photos_limit - 1);
        $photos_to_add = array_values($photos_urls);
        //добавлять будем не больше чем 20 - те что уже есть - 1(потому что нумерация с нуля)
        if($this::photos_limit - $photos_in_base_amount - 1 <= 0) $photos_to_add = [];
        else $photos_to_add = array_slice($photos_to_add,0, $this::photos_limit - $photos_in_base_amount - 1);
        return array($photos_to_add, $photos_to_delete);
    }
    
    private function parseUnstructuredTxtBlock($addr_block,$from_a_level,$max_a_level = false){
        global $db;
        
        if( empty( $max_a_level)) $max_a_level = 6;
        if(!is_array($addr_block)) $addr_block = explode(' ',$addr_block);
        $current_address = "";
        $geodata_variants = [];
        
        $k = 0;
        
        $shortname = false;
        
        $geodata = false;
        
        while(!empty($addr_block)){
            $something_found = false;
            if( count( $addr_block ) > 1 ) {
                array_shift( $addr_block );
            }
            //выходим когда что-то нашли, а дальше - ничего не нашли
            while( !($something_found && empty($found_geo)) && $k<count($addr_block) ){
                $a_level = ( empty( $from_a_level)?1:$from_a_level);
                
                if(!(in_array(trim(preg_replace('/\./sui','',$addr_block[$k])),$this->exploders)||in_array(trim(preg_replace('/\./sui','',$addr_block[$k])),$this->exploders_cut))){
                    $current_address = implode(' ',array_slice($addr_block,0,$k+1));
                    $current_address = preg_replace('/\sво\.?$/sui',' В%О%',$current_address);
                }
                if((in_array(trim(preg_replace('/\./sui','',$addr_block[$k])),$this->exploders)||in_array(trim(preg_replace('/\./sui','',$addr_block[$k])),$this->exploders_cut))){
                    $shortname = $addr_block[$k];
                    unset($addr_block[$k]);
                    $addr_block = explode(',',implode($addr_block));
                }
                $found_geo = false;
                while(!empty($current_address) && empty($found_geo) && $a_level <= $max_a_level){
                    $found_geo = $this->parseGeoTxtBlock($current_address,$shortname,$a_level);
                    if(!empty($found_geo)){
                        $something_found = true;
                        $geodata = $found_geo;
                        $shortname = false;
                    }
                    ++$a_level;
                }
                ++$k;
            }
            
            $key = 0;
            while($key<$k){
                unset($addr_block[$key]);
                ++$key;
            }
            $k = 0;
            $addr_block = explode(',',implode(',',$addr_block));
            //если остался блок дом/корпус и улицу нашли
            if( empty( $addr_block[0])) $addr_block = [];
            elseif(preg_match('/^[0-9дкДК\.\_\s]+$/sui',trim($addr_block[0])) && !empty($this->fields['id_street']) && count($addr_block) == 1){
                $this->getHouseCorpFromString($addr_block[0]);
                unset($addr_block[0]);
                break;
            } 
        }
        
        return $geodata;
    }    
    
    private function parseGeoTxtBlock($addr_block,$shortname,$a_level){
        global $db;
        if(strlen($addr_block) < 8 || Validate::isDigit($addr_block)) return false;
        $_geo = [];
                              
        //коррекция для адресов с "В.О."
        if( (preg_match('/((?<=[^А-я])п\.?с\.?(?!=[А-я]))|((?!=[А-я])в\.?о\.?(?!=[А-я]))/sui',$addr_block) || $this->fields['id_district'] == 3 ) && (strstr($addr_block,'большой') || strstr($addr_block,'малый') || strstr($addr_block,'средний')) && $shortname == 'проспект') 
            if(!preg_match('/в\.?о\.?/sui',$addr_block)) $addr_block = trim($addr_block)." В.О.";
            else $addr_block = trim(preg_replace('/(?<=[^А-я]|^)(в|В)\.?(о|О)\.?/','',$addr_block))." В.О.";
        $addr_block = $db->real_escape_string(trim($addr_block));
        $addr_block_condition = preg_replace('/\s|\./sui','%',$addr_block);
        
        //районы города читаем из другой таблицы
        if($a_level == 2 && $this->fields['id_region'] == 78){
            $objects_like_this = $db->fetchall("SELECT '78' AS id_region, id AS id_district FROM ".$this->sys_tables['districts']." WHERE title = ?",false,$addr_block_condition);
        }
        else
            $objects_like_this = $db->fetchall("SELECT offname,shortname,id_region,id_area,id_city,id_place,id_street,id_district 
                                                FROM ".$this->sys_tables['geodata']." 
                                                WHERE offname LIKE '%".$addr_block_condition."%' AND ".( empty( $this->fields['id_region'])?" id_region IN (47,78)":"id_region = ".$this->fields['id_region'])." AND ".(in_array($shortname,array('г','город'))?" a_level IN (1,3,4) ":"a_level = ".$a_level." ")."
                                                ORDER BY offname LIKE '".$addr_block_condition."%' DESC,
                                                         id_city = ? DESC, 
                                                         id_place = ? DESC",false,$this->fields['id_city'],$this->fields['id_place']);
        $max_match = 0;
        //смотрим, насколько совпадает с тем что есть
        foreach($objects_like_this as $key=>$object){
            $city_info = [];
            $objects_like_this[$key]['matching'] = 0;
            
            if($a_level >= 3 && $this->fields['id_region'] == 47 && !empty($this->fields['id_area']) && $this->fields['id_area'] != $object['id_area']){
                unset($objects_like_this[$key]);
                continue;
            }
            
            if($a_level > 3){
                if($this->fields['id_city'] == $object['id_city']) $objects_like_this[$key]['matching'] += 2;
                elseif(!empty($this->fields['id_city']) && (!empty($this->fields['id_area']) || !empty($this->fields['id_district'])) ){
                    //если указан город, проверяем подходит ли он под район
                    $city_info = $db->fetch("SELECT id_area,id_district,id_place
                                             FROM ".$this->sys_tables['geodata']." 
                                             WHERE id_region = ? ".(!empty($object['id_area'])?"AND id_area = ".$object['id_area']:"")." 
                                                                 ".(!empty($object['id_district'])?"AND id_district = ".$object['id_district']:"")." AND id_city = ? AND (a_level = 3 OR a_level = 4)",$object['id_region'],$object['id_city']);
                    if(!empty($this->fields['id_area']) && !empty( $city_info['id_area'] ) && $city_info['id_area'] != $this->fields['id_area'] ||
                      (!empty($this->fields['id_city']) && !empty( $city_info['id_city'] ) && $city_info['id_city'] != $this->fields['id_city']) ||
                      (!empty($this->fields['id_place']) && !empty( $city_info['id_place'] ) && $city_info['id_place'] != $this->fields['id_place'])){
                        unset($objects_like_this[$key]);
                        continue;
                    }
                    elseif(!empty($this->fields['id_district']) && !empty($city_info['id_district']) && $city_info['id_district'] != $this->fields['id_district']){
                        unset($objects_like_this[$key]);
                        continue;
                    }
                }
                if($this->fields['id_place'] == $object['id_place']) $objects_like_this[$key]['matching'] += 2;
                elseif(!empty($object['id_place']) && (!empty($this->fields['id_area']) || !empty($this->fields['id_district'])) ){
                    //если указано место, проверяем подходит ли оно под район
                    $city_info = $db->fetch("SELECT id_area,id_district,id_city 
                                             FROM ".$this->sys_tables['geodata']." 
                                             WHERE id_region = ? ".(!empty($object['id_area'])?"AND id_area = ".$object['id_area']:"")." 
                                                                 ".(!empty($object['id_district'])?"AND id_district = ".$object['id_district']:"")." AND id_place = ? AND a_level = 4",$object['id_region'],$object['id_place']);
                    if(($this->fields['id_region'] == 47 && !empty($this->fields['id_area']) && $city_info['id_area'] != $this->fields['id_area']) || 
                       ($this->fields['id_region'] == 78 && !empty($this->fields['id_district']) && $city_info['id_district'] != $this->fields['id_district']) ){
                        unset($objects_like_this[$key]);
                        continue;
                    }
                }
            }
            
            if($this->fields['id_district'] == $object['id_district']) $objects_like_this[$key]['matching'] += 2;
            //если район один из пяти, он обязательно должен совпадать
            elseif(!empty($this->fields['id_district']) && in_array($this->fields['id_district'],array(3,27,29,38,43,53)) && !empty($object['id_district'])){
                unset($objects_like_this[$key]);
                continue;
            } 
            if($addr_block == mb_strtolower($object['offname'])) ++$objects_like_this[$key]['matching'];
            if($shortname == $object['shortname'] && $objects_like_this[$key]['matching'] > 0) ++$objects_like_this[$key]['matching'];
            if($max_match < $objects_like_this[$key]['matching']) $max_match = $objects_like_this[$key]['matching'];
        }
        
        foreach($objects_like_this as $key=>$object){
            if($object['matching'] < $max_match || 
               ($this->fields['id_region'] == 47 && !empty($this->fields['id_area']) && ($object['id_area'] != $this->fields['id_area']) ) ||
               ($this->fields['id_region'] == 47 && !empty($this->fields['id_city']) && !empty($object['id_city']) && ($object['id_city'] != $this->fields['id_city']) ) ||
               ($this->fields['id_region'] == 47 && !empty($this->fields['id_place']) && !empty($object['id_place']) && ($object['id_place'] != $this->fields['id_place']) ) )
               unset($objects_like_this[$key]);
            //костыль для линий ВО
            if($this->fields['id_district'] == 3 && preg_match("/[^А-я]В([^А-я])?О([^А-я])?([^А-я]|$)/sui",$addr_block) && !preg_match("/[^А-я]В\.?О\.?([^А-я]|$)/sui",$object['offname'])) unset($objects_like_this[$key]);
        }
          
        //если что-то нашли,  новые данные и дальше этот блок не заполняем
        if(!empty($objects_like_this)){
            $_geo = array_shift($objects_like_this);
            $this->fields['id_street'] = $_geo['id_street'];
            $this->fields['id_place'] = $_geo['id_place'];
            $this->fields['id_city'] = $_geo['id_city'];
            $this->fields['id_area'] = $_geo['id_area'];
            $this->fields['id_region'] = $_geo['id_region'];
            //район города корректируем только если он пуст и есть улица
            if(($a_level == 5 && empty($this->fields['id_district']))) $this->fields['id_district'] = (!empty($_geo['id_district']))?$_geo['id_district']:0;
            $this->fields['id_region'] = $_geo['id_region'];
            $addr_blocks[] = $addr_block.' '.(!empty($shortname)?$shortname:"");
        }else
        //если это улица, ее не нашли, добавляем в таблицу
        if( empty( $this->fields['id_street']) && $a_level == 5){
            //смотрим нет ли уже такого в таблице
            $exists_already = $db->fetch("SELECT id,id_geodata
                                          FROM ".$this->sys_tables['addresses_to_add']." 
                                          WHERE (id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_district = ? AND offname = ? AND shortname = ?) OR addr_source = ?",
                                          $this->fields['id_region'],
                                          ( empty( $this->fields['id_area'])?0:$this->fields['id_area']),
                                          ( empty( $this->fields['id_city'])?0:$this->fields['id_city']),
                                          ( empty( $this->fields['id_place'])?0:$this->fields['id_place']),
                                          ( empty( $this->fields['id_district'])?0:$this->fields['id_district']),
                                          $addr_block,$shortname,
                                          (!empty($this->fields['addr_source'])?$this->fields['addr_source']:""));
            if( empty( $exists_already)){
                if(!empty($addr_block))
                    $db->querys("INSERT INTO ".$this->sys_tables['addresses_to_add']." (id_user,file_format,addr_source,id_region,id_area,id_city,id_place,id_district,offname,shortname,shortname_cut,date_in) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)",
                                $this->fields['id_user'],
                                $this->file_format,
                                (!empty($this->fields['addr_source'])?$this->fields['addr_source']:""),
                                $this->fields['id_region'],
                                ( empty( $this->fields['id_area'])?0:$this->fields['id_area']),
                                ( empty( $this->fields['id_city'])?0:$this->fields['id_city']),
                                ( empty( $this->fields['id_place'])?0:$this->fields['id_place']),
                                ( empty( $this->fields['id_district'])?0:$this->fields['id_district']),
                                $addr_block,
                                ( empty( $shortname)?"":$shortname),
                                ( empty( $shortname_cut)?"":$shortname_cut));
            }
            //смотрим, есть ли id_geodata - id объекта, соотв такому адресу в нашей базе
            else{
                if(!empty($exists_already['id_geodata'])){
                    $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']." WHERE id = ?",$exists_already['id_geodata']);
                    if(!empty($_geo)){
                        $this->fields['id_street'] = (!empty($_geo['id_street']))?$_geo['id_street']:0;
                        $this->fields['id_place'] = (!empty($_geo['id_place']))?$_geo['id_place']:0;
                        $this->fields['id_city'] = (!empty($_geo['id_city']))?$_geo['id_city']:0;
                        $this->fields['id_area'] = (!empty($_geo['id_area']))?$_geo['id_area']:0;
                        $this->fields['id_district'] = (!empty($_geo['id_district']))?$_geo['id_district']:0;
                        $this->fields['id_region'] = (!empty($_geo['id_region']))?$_geo['id_region']:0;
                        $addr_blocks[] = $addr_block.' '.$shortname;
                    }
                }
            }
        }
        return $_geo;
    }
    
    /*
    предназначен для получения геоданных из строки (не для объекта, используется в геоданных для IP). Статический метож не сделан, чтобы не затрагивать то что есть
    */
    public function getGeoIdFromString($addr_block,$from_a_level){
        global $db;
        $geodata = $this->parseUnstructuredTxtBlock($addr_block,$from_a_level);
        $geo_id = false;
        if(!empty($geodata))
            $geo_id = $db->fetch(
                                    "SELECT id,CONCAT(offname,' ',shortname) AS txt_addr
                                    FROM " . $this->sys_tables['geodata'] . " 
                                    WHERE id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_street = ? AND id_district = ?
                                    ",
                                    $geodata['id_region'], $geodata['id_area'], $geodata['id_city'], $geodata['id_place'], $geodata['id_street'], $geodata['id_district']
            );
        return ( empty( $geo_id)?array(0,""):$geo_id);
    }
    
    private function getHouseCorpFromString($txt_block){
        $txt_block = preg_replace('/\//','к',$txt_block);
        //д1-3 => д1
        $txt_block = preg_replace('/\-[0-9]+$/si','',$txt_block);
        //если конструкция вида "18к2" и блок один, добавляем "д" в начало
        if(!preg_match('/дом|д\.?/',$txt_block)&&preg_match('/корп|к\.?/',$txt_block)&&count($txt_block) == 1){
            $txt_block = "д".$txt_block;
        }
        $is_house = $is_corp = false;
        //если и дом и корпус в одном блоке, то между ними нет пробелов
        if(preg_match('/дом|д\.?/sui',$txt_block)&&preg_match('/корп|к\.?/sui',$txt_block)){
            $txt_block = preg_split('//u', $txt_block, -1, PREG_SPLIT_NO_EMPTY);
            $num = '';
            foreach($txt_block as $pos=>$char){
                if($char == 'д') $is_house = true;
                if($char == 'к') $is_corp = true;
                if(Validate::isDigit($char) || ( !Validate::isDigit($char) && !empty($txt_block[$pos-1]) && $txt_block[$pos-1] == 'к') ) $num .= $char;
                else{
                    if($is_house && !empty($num)){
                        $this->fields['house'] = Convert::ToInt($num);
                        $num = "";
                        $is_house = false;
                    }
                    if($is_corp && !empty($num)){
                        $this->fields['corp'] = Convert::ToInt($num);
                        $num = "";
                        $is_corp = false;
                    }
                }
            }
            if(!empty($num) && empty($this->fields['house'])) $this->fields['house'] = $num;
            elseif(!empty($num) && empty($this->fields['corp'])) $this->fields['corp'] = $num;
        }
        else{
            if(preg_match('/дом|д\.?/',$txt_block)){//конструкция вида "д12"
                if(Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block))!= 0)
                    $this->fields['house'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                else
                    $is_house = true;
            }
            elseif(preg_match('/корп|к\.?/',$txt_block)){//конструкция вида "к3"
                if(Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block))!= 0)
                    $this->fields['corp'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                else
                    $is_corp = true;
            }
            else{//конструкция вида "12"
                if(Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block))!= 0){
                    if($is_house){
                        $is_house = false;
                        $this->fields['house'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                    }
                    elseif($is_corp){
                        $is_corp = false;
                        $this->fields['corp'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                    }
                    else{
                        //если это просто номер и номера дома или корпуса нет, заполняем их
                        if( empty( $this->fields['house']))
                            $this->fields['house'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                        else
                            $this->fields['corp'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                    }
                }
            }
        }
    }
    /**
    * получение всех геоданных из сложного текстового адреса
    * @param string $txt_addr - текстовый адрес
    * @return array of array
    */    
    public function getTxtGeodata($txt_addr=false,$max_a_level = false){
        global $db;
        $this->fields['txt_addr'] = $txt_addr;
        if( empty( $max_a_level)) $max_a_level = 5;
        $txt_addr = " ".$txt_addr." ";
        //не убираем все, что в скобках (20.08.2015)
        //$txt_addr = preg_replace('/\(.*\)/sui','',$txt_addr);
        //убираем все кроме букв, точек, запятых, пробелов, скобок и все в нижний регистр
        $txt_addr = trim( preg_replace('/Санкт-Петербург,?/sui','',mb_strtolower($txt_addr,"UTF-8")) );
        $txt_addr = preg_replace('/[^А-я-,0-9\(\)\-\.\s\/]/sui','',mb_strtolower($txt_addr,"UTF-8"));
        // д. НОМЕР на ,д НОМЕР
        if( preg_match( "|\s(д\.)\s?[0-9]{1,}|msiU", $txt_addr ) ) $txt_addr = preg_replace( '|\s(д\.)\s?+|msiU', ', д.', $txt_addr );
        //заменяем обозначения улицы на корректные
        $txt_addr=str_replace(array('территория','пр.','просп.','проспект','пр-кт','бульвар','б-р','аллея','ал.','ул.', 'улица','линия В.О.','линия В.О','шоссе','пер.','переулок','дорога','литера ', ' корп'),array(' тер ',' пр ',' пр ',' пр ',' пр ',' бул ',' бул ',' алл ',' алл ',' ул ',' ул ',' В.О. линия ','В.О. линия',' шос ',' пер ',' пер ',' дор ','к','к'),$txt_addr);
        
        //заменяем обозначения локаций на корректные
        $txt_addr=str_replace(array('поселок городского типа','область','район','р-он','поселок','микрорайон','мик-н','мкр-н'),array('пгт','обл','р-н','р-н','пос','мкр','мкр','мкр'),$txt_addr);
        //заменяем "дер." на " деревня "
        $txt_addr = preg_replace('/дер\.?\s?[^евня]/sui',' деревня ',$txt_addr);
        //заменяем "пос." на " п "
        $txt_addr = trim(preg_replace('/пос(?=(\s|\.[^А-я]+))/sui',' п ',$txt_addr));
        //заменяем "п." на " п "
        //preg_replace('/(?<=[^А-я])п(?=(\s|\.[А-я]+))/sui',' п ',"п.Сертолово")
        $txt_addr = trim(preg_replace('/(?<=[^А-я])п(?=(\s|\.[А-я]+))/sui',' п ',$txt_addr));
        //заменяем "село" на "пос" - убрали. слишком много проблем из-за "Красное село". Мурино село - найдется без "cело"
        //$txt_addr = " ".$txt_addr." ";
        //$txt_addr = trim(preg_replace('/(?<=([^А-я]{1}))село(?=([^А-я]{1}))/sui',' пос ',$txt_addr));
        //заменяем "пл." на "площадь"
        $txt_addr = trim(preg_replace('/пл\.?(?=([^А-я]))/sui',' площадь ',$txt_addr));
        //заменяем -ого, -ая -я, ...
        $txt_addr = preg_replace('/(?(?<=[0-9])-[а-я]+)/sui','',$txt_addr);
        //заменяем &nbsp на пробелы
        $txt_addr = preg_replace('/&nbsp;/sui',' ',$txt_addr);
        //несколько пробелов подряд заменяем на один пробел
        $txt_addr = preg_replace('/[\s]+/',' ',$txt_addr);
        //разбиваем текст по запятым
        $comma_blocks = explode(',',str_replace('.','',$txt_addr));
        
        foreach ($comma_blocks as $cb_key=>$comma_block){
            $txt_addr = trim($comma_block);
            //разбиваем блок по пробелам
            //$txt_blocks = explode(' ',$txt_addr);
            
            //заменяем одиночные числа на \d+-я
            //$txt_addr = preg_replace('/(^[0-9]+(?=\s))|((?<=\s)[0-9]+(?=\s))|((?<=\s)[0-9]+$)/sui','$0-я',$txt_addr);
            //теперь разбиваем так, чтобы проглотить &nbsp;
            
            $txt_blocks = preg_split('/\s/sui',$txt_addr);
            /*
            if(count($txt_blocks) > 2){
                $this->parseUnstructuredTxtBlock($txt_blocks,false,$max_a_level);
                continue;
            }
            */
            
            $txt_blocks = array_filter($txt_blocks);
            $txt_blocks = array_values($txt_blocks);
            
            //если уже прочитали улицу, или пытались прочитать, выходим
            if(!empty($this->fields['id_street']) || (!empty($a_level) && $a_level == 5)) break;
            
            foreach($txt_blocks as $key=>$value){
                //ищем в разделителях из базы не учитывая точки, пробелы и регистр
                $value = mb_strtolower(trim(preg_replace('/\./sui','', $value)),"UTF-8");
                if (in_array(trim(preg_replace('/\./sui','',$value)),$this->exploders)||in_array(trim(preg_replace('/\./sui','',$value)),$this->exploders_cut)){
                    
                    //если предыдущий элемент не пуст, значит конструкция вида "... Мурино село"
                    if(!empty($txt_blocks[$key-1])){
                        $k = $key-1;
                        //убираем из строки разделитель, который мы нашли
                        unset($txt_blocks[$key]);
                        $addr_block = "";
                        //берем все, что было до("... Мурино"), соединяем с разделяющим блоком("село")
                        while (!empty($txt_blocks[$k])){
                            $addr_block = $txt_blocks[$k].' '.$addr_block;
                            //убираем подобранные блоки из строки
                            unset($txt_blocks[$k]);
                            --$k;
                        }
                        //получаем ключ разделителя, по которому найдем a_level этого типа объекта
                        $exploder_key = array_search($value,$this->exploders);
                        if( empty( $exploder_key)) $exploder_key = array_search($value,$this->exploders_cut);
                        
                        //читаем a_level
                        $a_level = $this->exploders_with_levels[$exploder_key]['level'];
                        
                        //правка для линий В.о.
                        if($exploder_key == 23 && ($this->fields['id_district'] == 3 && $a_level == 5)){
                            if(!preg_match('/в\.?о\.?/sui',$addr_block)) $addr_block = trim($addr_block)." В.О.";
                            else $addr_block = preg_replace('/во/sui','В.О.',trim($addr_block));
                        }elseif($this->fields['id_district'] == 3) $addr_block = preg_replace('/(?<=\s|^)в\.?о(?=\s|\.|$)/sui','В.О.',trim($addr_block));
                        
                        $addr_block = trim($addr_block);
                        
                        $_geo = [];
                        
                        $_geo = $this->parseGeoTxtBlock($addr_block,$this->exploders_with_levels[$exploder_key]['shortname'],$a_level);
                        
                        if(!empty($this->fields['id_street']) || empty($txt_blocks)) break;
                    }
                    else{
                        //если предыдущий элемент пуст, значит конструкция вида "село Мурино ..."
                        $k = $key+1;
                        //убираем из строки разделитель, который мы нашли
                        unset($txt_blocks[$key]);
                        $addr_block = "";
                        //здесь будем хранить адрес, который распознался до конца блока
                        $saved_geo = [];
                        //подбираем блоки после разделителя, после каждого проверяя по базе, не нашли ли что-нибудь + фикс для территорий снт
                        while((!empty($txt_blocks[$k])) && (( empty( $txt_blocks[$k]) || !in_array($txt_blocks[$k],$this->exploders)) || ($txt_blocks[$k] == 'снт' && $value == 'тер')) ){
                            //подбираем блок, проверяем в базе, если не нашли, идем дальше пока не наткнемся на следующий разделитель
                            $addr_block = trim( $addr_block . ' ' . $txt_blocks[$k]);
                            //если адрес из 2-х слов
                            //if( !empty( $txt_blocks[$k+1] ) && !preg_match( '#[^a-zа-я]+#siu',  $txt_blocks[$k+1] ) ) $addr_block .= ' ' . $txt_blocks[$k+1];
                            //убираем подобранный блок из строки
                            unset($txt_blocks[$k]);
                            //получаем ключ разделителя, по которому найдем a_level этого типа объекта
                            $exploder_key = array_search($value,$this->exploders);
                            if( empty( $exploder_key)) $exploder_key = array_search($value,$this->exploders_cut);
                            //читаем a_level
                            $a_level = $this->exploders_with_levels[$exploder_key]['level'];
                            
                            //правка для линий В.о.
                            if($exploder_key == 23 && ($this->fields['id_district'] == 3 && $a_level == 5)){
                                if(!preg_match('/в\.?о\.?/sui',$addr_block)) $addr_block = trim($addr_block)." В.О.";
                                else $addr_block = preg_replace('/во/sui','В.О.',trim($addr_block));
                            }elseif($this->fields['id_district'] == 3) $addr_block = preg_replace('/(?<=\s|^)в\.?о(?=\s|\.|$)/sui','В.О.',trim($addr_block));
                            
                            $_geo = $this->parseGeoTxtBlock( $addr_block, $this->exploders_with_levels[$exploder_key]['shortname'], $a_level );
                            
                            //если что-то нашли,  новые данные и дальше этот блок не заполняем
                            if(!empty($_geo)){
                                //если дальше еще что-то есть, лезем дальше. если нет - выходим
                                if( 
                                    ( !empty( $txt_blocks[$k+1] ) ) && !in_array( $txt_blocks[$k+1], $this->exploders ) 
                                ) {
                                        if( empty( $saved_geo['matching'] ) || $_geo['matching'] > $saved_geo['matching'] ) $saved_geo = $_geo;
                                        unset($_geo);
                                    }
                                elseif(!empty($this->fields['id_district']) && $this->fields['id_district'] != $_geo['id_district'] && !empty($saved_geo)){
                                    unset($this->fields['id_street']);
                                    unset($this->fields['id_place']);
                                    unset($this->fields['id_city']);
                                    unset($this->fields['id_area']);
                                    unset($_geo);
                                }
                                else{
                                    $addr_blocks[] = $addr_block.' '.$value;
                                    break;
                                }
                            }
                            ++$k;
                        }
                        //если что-то находили в процессе, дописываем (например пр. Авиаторов бла-бла-бла, отсюда вычленили проспект Авиаторов. а если бы был пр. Авиаторов Балтики, то распознали бы все)
                        if( empty( $_geo) && !empty($saved_geo)){
                            $_geo = $saved_geo;
                            if( empty( $this->fields['id_street'])) $this->fields['id_street'] = (!empty($_geo['id_street']))?$_geo['id_street']:0;
                            if( empty( $this->fields['id_place'])) $this->fields['id_place'] = (!empty($_geo['id_place']))?$_geo['id_place']:0;
                            if( empty( $this->fields['id_city'])) $this->fields['id_city'] = (!empty($_geo['id_city']))?$_geo['id_city']:0;
                            if( empty( $this->fields['id_area'])) $this->fields['id_area'] = (!empty($_geo['id_area']))?$_geo['id_area']:0;
                            //район города корректируем только если он пуст и есть улица
                            if(($a_level == 5 && empty($this->fields['id_district'])) || $a_level == 2) $this->fields['id_district'] = (!empty($_geo['id_district']))?$_geo['id_district']:0;
                            if( empty( $this->fields['id_region'])) $this->fields['id_region'] = (!empty($_geo['id_region']))?$_geo['id_region']:0;
                            $addr_blocks[] = $addr_block.' '.$value;
                        } 
                        if( empty( $this->fields['id_street']) && $a_level == 5){
                            //смотрим нет ли уже такого в таблице
                            $exists_already = $db->fetch("SELECT id,id_geodata
                                                          FROM ".$this->sys_tables['addresses_to_add']." 
                                                          WHERE (id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_district = ? AND offname = ? AND shortname = ?) OR addr_source = ?",
                                                          $this->fields['id_region'],
                                                          ( empty( $this->fields['id_area'])?0:$this->fields['id_area']),
                                                          ( empty( $this->fields['id_city'])?0:$this->fields['id_city']),
                                                          ( empty( $this->fields['id_place'])?0:$this->fields['id_place']),
                                                          ( empty( $this->fields['id_district'])?0:$this->fields['id_district']),
                                                          $addr_block,$this->exploders_with_levels[$exploder_key]['shortname'],
                                                          (!empty($this->fields['addr_source'])?$this->fields['addr_source']:""));
                            if( empty( $exists_already))
                                if( empty( $exists_already)){
                                    if(!empty($addr_block))
                                        $db->querys("INSERT INTO ".$this->sys_tables['addresses_to_add']." (id_user,file_format,addr_source,id_region,id_area,id_city,id_place,id_district,offname,shortname,shortname_cut,date_in) 
                                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)",
                                                    $this->fields['id_user'],
                                                    $this->file_format,
                                                    ( !empty( $this->fields['addr_source'] ) ? $this->fields['addr_source'] : "" ),
                                                    $this->fields['id_region'],
                                                    ( empty( $this->fields['id_area'])?0:$this->fields['id_area']),
                                                    ( empty( $this->fields['id_city'])?0:$this->fields['id_city']),
                                                    ( empty( $this->fields['id_place'])?0:$this->fields['id_place']),
                                                    ( empty( $this->fields['id_district'])?0:$this->fields['id_district']),
                                                    $addr_block,
                                                    $this->exploders_with_levels[$exploder_key]['shortname'],
                                                    $this->exploders_with_levels[$exploder_key]['shortname_cut']);
                                }
                            //смотрим, есть ли id_geodata - id объекта, соотв такому адресу в нашей базе
                            else{
                                if(!empty($exists_already['id_geodata'])){
                                    $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']." WHERE id = ?",$exists_already['id_geodata']);
                                    if(!empty($_geo)){
                                        $this->fields['id_street'] = (!empty($_geo['id_street']))?$_geo['id_street']:0;
                                        $this->fields['id_place'] = (!empty($_geo['id_place']))?$_geo['id_place']:0;
                                        $this->fields['id_city'] = (!empty($_geo['id_city']))?$_geo['id_city']:0;
                                        $this->fields['id_area'] = (!empty($_geo['id_area']))?$_geo['id_area']:0;
                                        $this->fields['id_district'] = (!empty($_geo['id_district']))?$_geo['id_district']:0;
                                        $this->fields['id_region'] = (!empty($_geo['id_region']))?$_geo['id_region']:0;
                                        $addr_blocks[] = $addr_block.' '.$value;
                                    }
                                }
                            }
                        }
                        //если прочитали улицу, выходим (ситуации когда деревня, город или район указаны после улицы не разбираются)
                        if(!empty($this->fields['id_street']) || empty($txt_blocks)) break;
                    }
                }elseif( count($txt_blocks) == 1 || 
                         (count($txt_blocks) >= 2 && 
                          (!empty($txt_blocks[$key+1]) && 
                          !(in_array(trim(preg_replace('/\./sui','',$txt_blocks[$key+1])),$this->exploders)||in_array(trim(preg_replace('/\./sui','',$txt_blocks[$key+1])),$this->exploders_cut)) ||
                          empty($txt_blocks[$key+1])
                          ) ) ){
                    //1)если в текстовом адресе например "Пискаревский" или "Комендантский" и все, пробуем подобрать только по offname
                    //2)адреса вида "Кудрово Европейский пр-кт 14" - без разделителей, аналогично, подбираем по тому что есть
                    //ищем  в базе соответствие найденному куску с 1)a_level = 5 2)a_level<5, отмечаем что есть проблемы
                    
                    if(count($txt_blocks) > 1){
                        $a_level = 3;
                        $k = 0;
                        $offname = [];
                        //читаем все до разделителя
                        while(isset($txt_blocks[$k]) && !(in_array(trim(preg_replace('/\./sui','',$txt_blocks[$k])),$this->exploders)||in_array(trim(preg_replace('/\./sui','',$txt_blocks[$k])),$this->exploders_cut)) ){
                            $offname[]= $txt_blocks[$k];
                            unset($txt_blocks[$k]);
                            ++$k;
                        }
                        $min_a_level = 1;
                        
                    }else{
                        $a_level = 3;
                        $min_a_level = (!empty($this->fields['id_area']) || !empty($this->fields['id_district']) ? 3 : 1);
                        $txt_blocks = array_values($txt_blocks);
                        if(!empty($txt_blocks[0])){
                            $offname = $txt_blocks[0];
                            unset($txt_blocks[0]);
                        }
                    }
                    
                    $found_geo = $this->parseUnstructuredTxtBlock($offname,$min_a_level,$max_a_level);
                    $a_level = (!empty($this->fields['id_street'])?5:3);
                }
            }
        }//foreach end
        
        
        if( empty ($a_level) || $a_level < 5 ) return;
        //в последнем из оставшихся блоков ищем номер дома и корпус
        $num = "";$is_house = false;$is_corp = false;
        if( count($txt_blocks) == 1 && trim( $txt_blocks[0] ) != trim( $comma_blocks[ count($comma_blocks) - 1 ] )){
            $txt_blocks[0] .= $comma_blocks[ count($comma_blocks) - 1 ];
        }
        foreach($txt_blocks as $key=>$txt_block){
            if( empty( $txt_block)) unset($txt_blocks[$key]);
            $txt_block = preg_replace('/\//','[к|лит|литера]',$txt_block);
            $this->getHouseCorpFromString($txt_block);
        }
    }
    public function fullAddress( $item ){
        global $db;
        $address = $db->fetch("
            SELECT GROUP_CONCAT(address,' ') as address FROM 
            (
            SELECT CONCAT(shortname, ' ', offname) as address FROM " . $this->sys_tables['geodata'] . " WHERE id_region = " . $item['id_region'] . " AND a_level = 1
            UNION ALL
            SELECT CONCAT(shortname, ' ', offname) as address FROM " . $this->sys_tables['geodata'] . " WHERE id_region = " . $item['id_region'] . " AND id_area = " . $item['id_area'] . " AND a_level = 2
            UNION ALL
            SELECT CONCAT(shortname, ' ', offname) as address FROM " . $this->sys_tables['geodata'] . " WHERE id_region = " . $item['id_region'] . " AND id_area = " . $item['id_area'] . " AND id_city = " . $item['id_city'] . " AND a_level = 3
            UNION ALL
            SELECT CONCAT(shortname, ' ', offname) as address FROM " . $this->sys_tables['geodata'] . " WHERE id_region = " . $item['id_region'] . " AND id_area = " . $item['id_area'] . " AND id_city = " . $item['id_city'] . " AND id_place = " . $item['id_place'] . " AND a_level = 4    
            UNION ALL
            SELECT CONCAT(shortname, ' ', offname) as address FROM " . $this->sys_tables['geodata'] . " WHERE id_region = " . $item['id_region'] . " AND id_area = " . $item['id_area'] . " AND id_city = " . $item['id_city'] . " AND id_place = " . $item['id_place'] . " AND id_street = " . $item['id_street'] . " AND a_level = 5
            ) a
        ");
        return $address['address'] . ( !empty( $item['house'] ) ? ', д. ' . $item['house'] : '' )  . ( !empty( $item['corp'] ) ? ', корпус ' . $item['corp'] : '' ); 
    }
    
    /**
    * получение улицы, дома и корпуса из обычного текстового адреса
    * @param string $txt_addr - текстовый адрес
    * @param string $street - текстовый адрес
    * @param string $house - текстовый адрес
    * @param string $corp - текстовый адрес
    * @return array of array
    */    
    public function getAddress($txt_addr=false, $street=false, $house=false, $korp=false, $parsing=false){
        global $db;
        if( empty( $txt_addr) && !empty($street)) $txt_addr = $street;
        if ($parsing)
            $txt_addr=str_replace(array('проспект','пр-кт','бульвар','аллея','улица','линия В.О.','линия В.О','шоссе','пер.','переулок'),array('пр','пр','бул','алл','ул','линия','линия','шос','пер','пер'),$txt_addr);
        else
             $txt_addr=str_replace(array('.',',','проспект','пр-кт','бульвар','аллея','улица','линия В.О.','линия В.О','шоссе','пер.','переулок'),array('','','пр','пр','бул','алл','ул','линия','линия','шос','пер','пер'),$txt_addr);
        $shortname = $offname = '';
        
        //разбиваем адрес на блок(и) по запятым
        $addr_blocks = explode(',',$txt_addr);
        //ищем кусок с улицей
        foreach($addr_blocks as $key=>$value){
            //если в блоке присутствует что-то из набора, значит это улица
            if (preg_match('/ул|пер|бул|наб|пр|шос|линия|проезд/sui',$value)){
                $txt_street = $value;
            }
        }
        //если ничего не нашли, берем первый
        if( empty( $txt_street)) $txt_street = $addr_blocks[0];
        //теперь не убираем то, что в скобках (20.08.2015)
        //$txt_street = trim(preg_replace('/\s?\(.*\)/sui','',$txt_street));
        //убираем корректно указанные дом и корпус ("1 красноармейская д.3 к.34")
        $txt_street = trim(preg_replace('/(\sд|\sк|\sкорп|\sстр)\.?\s?[0-9]+/sui','',$txt_street));
        //убираем дом и корпус указанные просто цифрами ("1 красноармейская 3/34", "1 красноармейская 3 34 3",)
        $txt_street = trim(preg_replace('/(?(?<=[а-я])\s[0-9\/]+)/sui','',$txt_street));
        //разбираем всеми способами
        preg_match_all("/([0-9]{0,3}\s?[а-я0-9-\(\)\.\s]+[а-я-\(\)]\s?[0-9]{0,3})[\s?]([ул|пер|бул|наб|пр|шос|линия|проезд]{2,})?.?/sui",$txt_street,$addresses[0]);
        preg_match_all("/([0-9]{0,2}\s?[а-я-\(\)\.\s]{3,}) ([ул|пер|бул|наб|пр|шос|линия|проезд]{1,})?.?/sui",$txt_street,$addresses[1]);
        preg_match_all("/([ул|пер|бул|наб|пр|шос|линия|проезд]{1,}).? ([0-9]{0,2}\s?[а-я0-9-\(\)\.\s]+[а-я-\.])/sui",$txt_street,$addresses[2]);
        //если распознанные улица и тип улицы сложились в первоначальное, значит в этом случае все распознано правильно
        foreach($addresses as $key=>$value){
            unset($value[0]);
            foreach($value as $k=>$v){
                $value[$k] = (!empty($v[0]))?$v[0]:"";
            }
            //сравниваем убрав все точки и пробелы
            if(preg_replace('/\.|\s/','',implode(' ',$value)) == preg_replace('/\.|\s/','',$txt_street)) $addr = $addresses[$key];
        }
        if(!empty($addr[0])) unset($addr[0]);
        $offname = "";
        //разбиваем на offname и shortname
        if(!empty($addr))
            foreach($addr as $key=>$value){
                //если в блоке попалось что-то из набора, значит это shortname
                if (preg_match('/ул|пер|бул|наб|пр|шос|линия|проезд/sui',$value[0])) $shortname = $value[0];
                else $offname .= $value[0];
            }
        $addr[1][0] = $offname;
        $addr[2][0] = $shortname;
        
        $txt_addr = str_replace(array('дом','корпус','корп','кор'),array('д.','к.','к.','к.'),$txt_addr);
        
        //если ничего не получилось, делаем как раньше
        if(( empty( $addr[2][0]) ||  empty($addr[1][0])) || (mb_strlen($addr[1][0],"UTF-8")+mb_strlen($addr[2][0],"UTF-8"))<=4) {
            preg_match_all("/([0-9]{0,2}\s?[а-я-\.\s]{3,}) ([ул|пер|бул|наб|пр|шос|линия|проезд]{1,})?.?/sui",$txt_addr,$addr);
            
            $addr[1][0] = (!empty($addr[1][0]))?trim($addr[1][0]):"";
            $addr[2][0] = (!empty($addr[2][0]))?trim($addr[2][0]):"";
            
            if(!empty($addr[1][0]) && mb_strlen($addr[1][0],"UTF-8") > 3 && empty($addr[2][0])){
                $shortname = 'ул';
                $offname = $addr[1][0];
            } else {
                
                preg_match_all("/([ул|пер|бул|наб|пр|шос|линия|проезд]{1,}).? ([0-9]{0,2}\s?[а-я0-9-\.\s]+[а-я-\.])/sui",$txt_addr,$addr);
                
                $addr[1][0] = (!empty($addr[1][0]))?trim($addr[1][0]):"";
                $addr[2][0] = (!empty($addr[2][0]))?trim($addr[2][0]):"";
                
                if(!empty($addr[1][0]) && !empty($addr[2][0]) && strlen($addr[1][0])>3){
                    $shortname = $addr[1][0];
                    $offname = $addr[2][0];
                }
            }
            
        } else {
            $shortname = $addr[2][0];
            $offname = $addr[1][0];
        }
        if($shortname!=''  && $offname!=''){ 
            //определяем id улицы
            $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                WHERE `id_region`=? AND 
                                      `id_area`=? AND  
                                       `offname` = ? AND 
                                       `shortname_cut` = ?
                                       ORDER BY ".($this->fields['id_region']==78?" id_place, id_city ":" id_city DESC, id_place DESC  ")." 
                                       LIMIT 1",
                                       $this->fields['id_region'],
                                       $this->fields['id_area'],
                                       $offname,
                                       $shortname
                                 ) ;
            if(!empty($_geo)) {
                $this->fields['id_street'] = $_geo['id_street'];
                $this->fields['id_city'] = $_geo['id_city'];
                $this->fields['id_place'] = $_geo['id_place'];
                if( empty( $this->fields['id_district']) && !empty($_geo['id_district'])) $this->fields['id_district'] = $_geo['id_district'];
                //определяем адрес дома
                if( empty( $house) && empty($korp)){
                    $house = 0; $korp = '';
                    $txt_addr = str_replace(array($shortname,$offname,'д','дом',',','к','корп','.','литера','лит'),' ', $txt_addr);
                    $txt_addr = str_replace('/',' ', $txt_addr);
                    $txt_addr = trim($txt_addr);
                
                    preg_match_all("/([0-9]{1,3})(\s{1,})?([0-9]{1,3})?/",$txt_addr,$_addr);
                    if(!empty($_addr[1][0])) $this->fields['house'] = $_addr[1][0];
                    if(!empty($_addr[3][0])) $this->fields['corp'] = $_addr[3][0];

                } else {
                      $this->fields['house'] = $house;
                      $this->fields['corp'] = $korp;
                }
            }  else{
                $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                    WHERE `id_region`=? AND 
                                          `id_area`=? AND  
                                           `offname` = ? 
                                           ORDER BY ".($this->fields['id_region']==78?" id_place, id_city ":" id_city DESC, id_place DESC  ")."
                                           LIMIT 1",
                                           $this->fields['id_region'],
                                           $this->fields['id_area'],
                                           $offname
                                     ) ;
                if(!empty($_geo)) {
                    $this->fields['id_street'] = $_geo['id_street'];
                    $this->fields['id_place'] = $_geo['id_place'];
                    if( empty( $this->fields['id_district']) && !empty($_geo['id_district'])) $this->fields['id_district'] = $_geo['id_district'];
                    //определяем адрес дома
                    if( empty( $house) && empty($korp)){
                        $house = 0; $korp = '';
                        $txt_addr = str_replace(array($shortname,$offname,'д','дом',',','к','корп','.','литера','лит'),' ', $txt_addr);
                        $txt_addr = str_replace('/',' ', $txt_addr);
                        $txt_addr = trim($txt_addr);
                    
                        preg_match_all("/([0-9]{1,3})(\s{1,})?([0-9]{1,3})?/",$txt_addr,$_addr);
                        if(!empty($_addr[1][0])) $this->fields['house'] = $_addr[1][0];
                        if(!empty($_addr[3][0])) $this->fields['corp'] = $_addr[3][0];

                    } else {
                          $this->fields['house'] = $house;
                          $this->fields['corp'] = $korp;
                    }
                }                         
            }                                      
                
        }
    }               
    public function getGeodataDdata( $addr ){
        global $db, $sys_tables;
        $dadata = new SuggestClient( );
        $data = array(
            'query' => $addr,
            'count' => 2
        );
        $fields = array( 'id_area', 'id_street', 'id_city', 'id_place', 'house', 'corp', 'lat', 'lng' );
        if( !empty( $this->fields['id_user'] ) && !empty( $this->fields['external_id'] ) ) $item = $db->fetch( " SELECT * FROM " . $sys_tables['xml_address_parse'] . " WHERE id_user = ? AND external_id = ? AND id_geodata > 0 ", $this->fields['id_user'], $this->fields['external_id'] );
        if( !empty( $item ) ) {
            foreach( $fields as $field )  if( !empty( $item[ $field ] ) ) $this->fields[ $field ] = $item[ $field ];    
        } else {        
            $resp = $dadata->suggest( "address", $data );
            if( count( $resp->suggestions ) > 1 ) $resp->suggestions = array_slice( $resp->suggestions, 0, 1 );
            foreach ($resp->suggestions as $suggestion) {
                if( !empty( $suggestion->data->house ) ) $this->fields['house'] = $suggestion->data->house;
                if( !empty( $suggestion->data->block ) ) $this->fields['corp'] = $suggestion->data->block;
                if( !empty( $suggestion->data->street_fias_id ) ) {
                    $geodata = $db->fetch( " SELECT *, lng_center as lng, lat_center as lat FROM " . $sys_tables['geodata'] . " WHERE aoguid = ? ", $suggestion->data->street_fias_id );
                    if( !empty( $geodata ) ) {
                        foreach( $fields as $field )  if( !empty( $geodata[ $field ] ) ) $this->fields[ $field ] = $geodata[ $field ];
                    } else if (
                        !empty( $suggestion->data->settlement_fias_id ) || (
                            !empty( $suggestion->data->city_fias_id ) && empty( $suggestion->data->settlement_fias_id )
                        )
                    ){
                        $parentguid = !empty( $suggestion->data->settlement_fias_id ) ? $suggestion->data->settlement_fias_id : $suggestion->data->city_fias_id;
                        
                        $similar_street = $db->fetch( " SELECT *, MAX(id_street) as max_id_street, lng_center as lng, lat_center as lat FROM " . $sys_tables['geodata'] . " WHERE `parentguid` LIKE ? AND a_level = 5" , $parentguid );
                        if( !empty( $similar_street['id'] ) ) {
                            $db->querys( " INSERT INTO " . $sys_tables['geodata'] . " 
                                          SET offname = ?, shortname = ?, shortname_cut = ?, aoguid = ?, parentguid = ?, id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ?, lng_center = ?, lat_center = ?, a_level = 5 ",
                                          $suggestion->data->street, $suggestion->data->street_type_full, $suggestion->data->street_type, $suggestion->data->street_fias_id, $parentguid, $similar_street['id_region'], $similar_street['id_area'], $similar_street['id_city'], $similar_street['id_place'], $similar_street['max_id_street'] + 1, !empty( $suggestion->data->geo_lon ) ? $suggestion->data->geo_lon : '0.00', !empty( $suggestion->data->geo_lat ) ? $suggestion->data->geo_lat : '0.00' 
                            );
                            foreach( $fields as $field )  if( !empty( $similar_street[ $field ] ) ) $this->fields[ $field ] = $similar_street[ $field ];
                            $this->fields[ 'id_street' ] = $similar_street['max_id_street'] + 1;
                            $geodata['id'] = $db->insert_id;
                        }  else if ( !empty( $suggestion->data->settlement_fias_id ) && empty( $suggestion->data->city_fias_id ) && !empty( $suggestion->data->area_fias_id ) ) {
                            $parentguid = $suggestion->data->area_fias_id;
                            $place = $db->fetch( " SELECT *, lng_center as lng, lat_center as lat FROM " . $sys_tables['geodata'] . " WHERE `aoguid` LIKE ? AND a_level = 4" , $suggestion->data->settlement_fias_id );
                            if( !empty( $place ) ) {
                                //есть нас.пункт, нет улицы - добавляем улицу
                                $db->querys( " INSERT INTO " . $sys_tables['geodata'] . " 
                                          SET offname = ?, shortname = ?, shortname_cut = ?, aoguid = ?, parentguid = ?, id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ?, lng_center = ?, lat_center = ?, a_level = 5 ",
                                          $suggestion->data->street, $suggestion->data->street_type_full, $suggestion->data->street_type, $suggestion->data->street_fias_id, $suggestion->data->settlement_fias_id, $place['id_region'], $place['id_area'], $place['id_city'], $place['id_place'], 1, !empty( $suggestion->data->geo_lon ) ? $suggestion->data->geo_lon : '0.00', !empty( $suggestion->data->geo_lat ) ? $suggestion->data->geo_lat : '0.00' 
                                );
                                foreach( $fields as $field )  if( !empty( $place[ $field ] ) ) $this->fields[ $field ] = $place[ $field ];
                                $this->fields[ 'id_street' ] = 1;
                                $geodata['id'] = $db->insert_id;

                            } else {
                                //добавление нас.пункта
                                $similar_place = $db->fetch( " SELECT *, MAX(id_place) as max_id_place, lng_center as lng, lat_center as lat FROM " . $sys_tables['geodata'] . " WHERE `parentguid` LIKE ? AND a_level = 4" , $parentguid );
                                if( !empty( $similar_place['id'] ) ) {
                                    $db->querys( " INSERT INTO " . $sys_tables['geodata'] . " 
                                              SET offname = ?, shortname = ?, shortname_cut = ?, aoguid = ?, parentguid = ?, id_region = ?, id_area = ?, id_city = ?, id_place = ?, lng_center = ?, lat_center = ?, a_level = 4 ",
                                              $suggestion->data->settlement, $suggestion->data->settlement_type_full, $suggestion->data->settlement_type, $suggestion->data->settlement_fias_id, $parentguid, $similar_place['id_region'], $similar_place['id_area'], $similar_place['id_city'], $similar_place['max_id_place'] + 1, !empty( $suggestion->data->geo_lon ) ? $suggestion->data->geo_lon : '0.00', !empty( $suggestion->data->geo_lat ) ? $suggestion->data->geo_lat : '0.00' 
                                    );
                                    foreach( $fields as $field )  if( !empty( $similar_place[ $field ] ) ) $this->fields[ $field ] = $similar_place[ $field ];
                                    $this->fields[ 'id_place' ] = $similar_place['max_id_place'] + 1;
                                    $geodata['id'] = $db->insert_id;
                                }
                            }
                            
                        }
                    }    
                }
                else if( !empty( $suggestion->data->settlement_fias_id ) ) {
                    $geodata = $db->fetch( " SELECT *, lng_center as lng, lat_center as lat FROM " . $sys_tables['geodata'] . " WHERE aoguid = ? ", $suggestion->data->settlement_fias_id );
                    if( !empty( $geodata ) ) {
                        foreach( $fields as $field )  if( !empty( $geodata[ $field ] ) ) $this->fields[ $field ] = $geodata[ $field ];
                    } else if ( !empty( $suggestion->data->city_fias_id ) ){
                        $parentguid = $suggestion->data->city_fias_id;
                        
                        $similar_place = $db->fetch( " SELECT *, MAX(id_place) as max_id_place, lng_center as lng, lat_center as lat FROM " . $sys_tables['geodata'] . " WHERE `parentguid` LIKE ? AND a_level = 4" , $parentguid );
                        if( !empty( $similar_place['id'] ) ) {
                            $db->querys( " INSERT INTO " . $sys_tables['geodata'] . " 
                                          SET offname = ?, shortname = ?, shortname_cut = ?, aoguid = ?, parentguid = ?, id_region = ?, id_area = ?, id_city = ?, id_place = ?, lng_center = ?, lat_center = ?, a_level = 4 ",
                                          $suggestion->data->settlement, $suggestion->data->settlement_type_full, $suggestion->data->settlement_type, $suggestion->data->settlement_fias_id, $parentguid, $similar_place['id_region'], $similar_place['id_area'], $similar_place['id_city'], $similar_place['max_id_place'] + 1, !empty( $suggestion->data->geo_lon ) ? $suggestion->data->geo_lon : '0.00', !empty( $suggestion->data->geo_lat ) ? $suggestion->data->geo_lat : '0.00' 
                            );
                            foreach( $fields as $field )  if( !empty( $similar_place[ $field ] ) ) $this->fields[ $field ] = $similar_place[ $field ];
                            $this->fields[ 'id_place' ] = $similar_place['max_id_place'] + 1;
                            $geodata['id'] = $db->insert_id;
                        }
                    } else if ( !empty( $suggestion->data->area_fias_id ) ){
                        $parentguid = $suggestion->data->area_fias_id;
                        
                        $similar_place = $db->fetch( " SELECT *, MAX(id_place) as max_id_place, lng_center as lng, lat_center as lat FROM " . $sys_tables['geodata'] . " WHERE `parentguid` LIKE ? AND a_level = 4" , $parentguid );
                        if( !empty( $similar_place['id'] ) ) {
                            $db->querys( " INSERT INTO " . $sys_tables['geodata'] . " 
                                          SET offname = ?, shortname = ?, shortname_cut = ?, aoguid = ?, parentguid = ?, id_region = ?, id_area = ?, id_city = ?, id_place = ?, lng_center = ?, lat_center = ?, a_level = 4 ",
                                          $suggestion->data->settlement, $suggestion->data->settlement_type_full, $suggestion->data->settlement_type, $suggestion->data->settlement_fias_id, $parentguid, $similar_place['id_region'], $similar_place['id_area'], $similar_place['id_city'], $similar_place['max_id_place'] + 1, !empty( $suggestion->data->geo_lon ) ? $suggestion->data->geo_lon : '0.00', !empty( $suggestion->data->geo_lat ) ? $suggestion->data->geo_lat : '0.00' 
                            );
                            foreach( $fields as $field )  if( !empty( $similar_place[ $field ] ) ) $this->fields[ $field ] = $similar_place[ $field ];
                            $this->fields[ 'id_place' ] = $similar_place['max_id_place'] + 1;
                            $geodata['id'] = $db->insert_id;
                        }
                    }    
                } else if( !empty( $suggestion->data->city_fias_id ) ) {
                    $geodata = $db->fetch( " SELECT *, lng_center as lng, lat_center as lat FROM " . $sys_tables['geodata'] . " WHERE aoguid = ? ", $suggestion->data->city_fias_id );
                    if( !empty( $geodata ) ) {
                        foreach( $fields as $field )  if( !empty( $geodata[ $field ] ) ) $this->fields[ $field ] = $geodata[ $field ];
                    }  
                }
            }             
            $db->querys(" 
                INSERT INTO " . $sys_tables['xml_address_parse'] . " SET id_user = ?, external_id = ?, id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ?, house = ?, corp = ?, lat = ?, lng = ?, response = ?, address = ?, id_geodata = ?
                ON DUPLICATE KEY UPDATE id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ?, house = ?, corp = ?, lat = ?, lng = ?, response = ?, address = ?, id_geodata = ?
            ", $this->fields['id_user'], $this->fields['external_id'], $this->fields['id_region'], $this->fields['id_area'], $this->fields['id_city'], $this->fields['id_place'], $this->fields['id_street'], $this->fields['house'], $this->fields['corp'], $this->fields['lat'], $this->fields['lng'], print_r( $suggestion, 1 ), $addr, !empty( $geodata ) ? $geodata['id'] : 0,
                                                                       $this->fields['id_region'], $this->fields['id_area'], $this->fields['id_city'], $this->fields['id_place'], $this->fields['id_street'], $this->fields['house'], $this->fields['corp'], $this->fields['lat'], $this->fields['lng'], print_r( $suggestion, 1 ), $addr, !empty( $geodata ) ? $geodata['id'] : 0
            );
            
            if( empty( $geodata['id'] ) ) {
                echo '123123';
            }
            if( !empty( $geodata ) ) return $geodata;
            
        }
    }
    
    /**
    * получение адреса и координат по полному соответствию, включая номер дома
    * 
    */
    public function getSpbAddress( $item ){
        global $db, $sys_tables;
        $data = $db->fetch(" SELECT * FROM " . $sys_tables['geodata_spb_addresses'] ." WHERE  
                     id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_street = ? AND house = ? AND corp IN (".(!empty($item['corp']) ? "'".$item['corp']."'" : '"0",""').") ",
                     $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street'], $item['house']
        );
        return $data;
    }
    /**
    * добавление адреса и координат по полному соответствию, включая номер дома
    * 
    */
    public function addSpbAddress( $item ){
        if( !in_array( $item['id_region'], array( 78, 47 ) ) ) return false;
        global $db, $sys_tables;                          
        $db->querys(" INSERT IGNORE INTO " . $sys_tables['geodata_spb_addresses'] ." SET 
                     id_region = ?, id_area = ?, id_city = ?, id_place = ?, id_street = ?, house = ?, corp = ?, lat = ?, lng = ?, address = ? ",
                     $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street'], $item['house'], $item['corp'], $item['lat'], $item['lng'], $item['txt_addr']
        );
    }
    /**
    * получение координат по адресу
    * 
    */
    public function getCoords($item){
        if( empty( $this->fields['2gis_response'] ) ) $this->getAddrResponse( $item['txt_addr'] );
        
        if( !empty( $this->fields['2gis_response'] ) && !empty( $this->fields['2gis_response']->result->items[0]->geometry->centroid ) ) {
            $coords = explode( " ", preg_replace('#[^0-9\s\.]#msiU','', $this->fields['2gis_response']->result->items[0]->geometry->centroid ) );
            $lng = $coords[0];
            $lat = $coords[1];
        }
        if( !empty( $lng ) && !empty( $lat ) ) return array( $lat, $lng );
        return array( '', '' );
    }
    public function getYandexCoords( $address ){
        $geo = curlThis("http://geocode-maps.yandex.ru/1.x/?format=json&kind=street&geocode=".$address);
        $geo = json_decode($geo);
        print_r( $geo );
        if(!empty($geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos)){
            $point = explode(" ",$geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
            if( $point[0] != 59.939095 && $point[1] != 30.315868 ){
                $this->fields['lng'] = $lng = $point[0];
                $this->fields['lat'] = $lat = $point[1];       
                return array($lat, $lng);
            }
        }
        
        
    }
    /**
    * получение метро и района по адресу
    * 
    */
    public function getAddrResponse( $addr = '' ){
        $url = "https://catalog.api.2gis.ru/3.0/items?type=street%2Cadm_div.city%2Ccrossroad%2Cadm_div.settlement%2Cstation%2Cbuilding%2Cadm_div.district%2Cstation.metro&page=1&page_size=12&locale=ru_RU&fields=request_type%2Citems.adm_div%2Citems.attribute_groups%2Citems.contact_groups%2Citems.flags%2Citems.address%2Citems.rubrics%2Citems.name_ex%2Citems.point%2Citems.geometry.centroid%2Citems.region_id%2Citems.segment_id%2Citems.external_content%2Citems.org%2Citems.group%2Citems.schedule%2Citems.timezone_offset%2Citems.ads.options%2Citems.station%2Citems.station.metro%2Citems.stat%2Citems.reviews%2Citems.purpose%2Csearch_type%2Ccontext_rubrics%2Csearch_attributes%2Cwidgets%2Cfilters&stat%5Bsid%5D=0f01fbfe-13d0-4b63-bf83-723e9ce1ae0b&stat%5Buser%5D=3e70592f-1f0b-482b-bec9-ab53f3931232&key=rulikm8232&q=";
        if( !empty( $this->fields['txt_addr'] ) ) $district = curlThis( $url . 'Санкт-Петербург,' . $this->fields['txt_addr'] );
        else if( !empty( $addr ) ) $district = curlThis( $url . 'Санкт-Петербург,' . $addr );
        $this->fields['2gis_response'] = json_decode( $district );

    }
    public function getDistrict( $values ){
        global $db, $sys_tables;
        if( empty( $this->fields['2gis_response'] ) ) $this->getAddrResponse();
        if( !empty ( $this->fields['2gis_response'] ) ) {
            $district = $this->fields['2gis_response'];
            if( !empty( $district->result->items[0]->adm_div ) ) 
            {
                foreach( $district->result->items[0]->adm_div as $k => $info ){
                    $district_name = str_replace( ' район', '', $info->name );
                    $id_district = $db->fetch( "SELECT id FROM " . $sys_tables['districts'] ." WHERE title = ?", $district_name )['id'];
                    if( !empty ( $id_district ) ) {
                        $this->fields['id_district'] = $id_district;
                        break;
                    }
                }
            } else $this->fields['id_district'] = 0;
        }
    }

    /**
     *  Рассчитываем расстояние между двумя точками на карте
     *
     *  @param float $longitude1     Долгота первой точки
     *  @param float $latitude1      Широта первой точки
     *  @param float $longitude2     Долгота второй точки
     *  @param float $latitude2      Широта второй точки
     */
    public function distance($longitude1, $latitude1, $longitude2, $latitude2){
     
        // Средний радиус Земли в метрах
        $earthRadius = 6372797;
     
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);
     
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $distance = ceil($earthRadius * $c);
     
        return $distance;
    }
    /**
     *  Нахождение координат точки по координатам другой точки и известным длине и дирекционному углу данного направления, соединяющей эти точки
     *
     *  @param float $longitude1     Долгота первой точки
     *  @param float $latitude1      Широта первой точки
     *  @param int $distance         Максимальное расстояние до метро в км
     *  @param int $angle            Дирекционный угол
     *  @param string $type          Если нужно только одна координата широта или высота, то указываем ее
     */
    public function getPoint($lng, $lat, $distance=2, $angle=0, $type=null){
        
        // Переводим км в метры
        $distance = $distance * 1000;
        // Длина дуги параллели в 1° на экваторе в метрах
        $latMeter = 111321;
        $angle = deg2rad($angle);
     
        $dx = $distance / ($latMeter * cos(deg2rad($lat))) * cos($angle);
        $dy = $distance / $latMeter * sin($angle);
     
        $result = array(
            'lng' => $lng + $dx,
            'lat' => $lat + $dy
        );
     
        // Если нам нужна только одна координата (широта или высота), то возвращаем только ее
        if($type){
     
            return str_replace( ',', '.', $result[$type] );
        }
     
        return str_replace( ',', '.', $result );
    }
    /**
    * получение метро и района по адресу
    * 
    */
    public function getSubway( $distance=10, $limit=null ){
        global $db, $sys_tables;
        if( empty( $this->fields['2gis_response'] ) && ( $this->fields['lng'] < 1  || $this->fields['lat'] < 1 ) ) $this->getAddrResponse();
        
        $sLng = $this->getPoint($this->fields['lng'], $this->fields['lat'], $distance, 180, 'lng');
        $eLng = $this->getPoint($this->fields['lng'], $this->fields['lat'], $distance, 0,   'lng');
        $sLat = $this->getPoint($this->fields['lng'], $this->fields['lat'], $distance, 270, 'lat');
        $eLat = $this->getPoint($this->fields['lng'], $this->fields['lat'], $distance, 90,  'lat');
     
        /** Выбираем информацию о станции, а также цвет ветки метро */
        $data = $db->fetchall( " SELECT * FROM " . $sys_tables['subways'] . " WHERE lat BETWEEN " . $sLat . " AND " . $eLat . " AND lng BETWEEN " . $sLng . " AND " . $eLng . " " );
     
        if($data){
            $list = [];
            foreach($data as $v){
                // Рассчитываем расстояние от объекта до метро в метрах
                $v['distance'] = $this->distance($this->fields['lng'], $this->fields['lat'],$v['lng'],$v['lat']);
                // Если расстояние более 1000 метров то переводим результат в км. Можно вынести в отдельный метод
                $v['distance_format'] = ($v['distance'] >= 1000 ? round($v['distance']/1000,1).' км' : $v['distance'].' м');
                
                $list[$v['distance']] = $v;
            }
            // Сортируем по расстоянию метро
            ksort($list);
            $this->fields['id_subway'] = array_shift( array_values( $list ) )['id'];
            if( empty( $this->fields['id_subway'] ) ) {
                echo '123';
            }
            if($limit){
                // Если задан лимит то выводим только первые N станций
                return array_slice($list, 0, $limit);
            }else{
     
                return $list;
            }
        }
        return false;
    }
    
    /**
    * группировка объектов по адресу
    * @param string $estate_type - тип недвижимости
    * @param array $values  - исходные данные
    * @param boolean $robot - откуда пришел объект
    */
    public function groupByAddress($estate_type, $values, $robot = true){
        global $db;
        
        $this->sys_tables = Config::Get('sys_tables');
        
        //поиск по параметризованному адресу
        if(!empty($values['id_region']) && !empty($values['id_street'])){
            $item = $db->fetch("SELECT group_id FROM ".$this->sys_tables[$estate_type]." WHERE id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=? AND house=? AND corp IN (".(!empty($values['corp']) ? "'".$values['corp']."'" : '"0",""').") AND group_id>0 LIMIT 1",
                $values['id_region'], $values['id_area'], $values['id_city'], $values['id_place'], $values['id_street'], $values['house']
            );
        }
        if( empty( $item)){
            if(!empty($values['lat']) && !empty($values['lng']) && empty( $values['id_region'] ) && empty( $values['id_street'] ) && empty( $values['house'] ) ) $this->groupCoords($estate_type, $values, $robot);
            else {
                //пролчуение макс. group_id
                $id = $db->fetch("SELECT MAX(group_id) as max_id FROM ".$this->sys_tables[$estate_type]);
                $id = $id['max_id'] + 1;
                if(!empty($robot)) $this->fields['group_id'] = $id;
                else $db->querys("UPDATE ".$this->sys_tables[$estate_type]." SET group_id=? WHERE id=?", $id, $values['id']);
            }
        }  else {
            if(!empty($robot)) $this->fields['group_id'] = $item['group_id'];
            else $db->querys("UPDATE ".$this->sys_tables[$estate_type]." SET group_id=? WHERE id=?", $item['group_id'], $values['id']);
        }
    }
    /**
    * группировка объектов по адресу
    * @param string $estate_type - тип недвижимости
    * @param array $values  - исходные данные
    * @param boolean $robot - откуда пришел объект
    */
    public function groupCoords($estate_type, $values, $robot = true){
        global $db;
        
        if(!empty($values['id'])){
            $this->sys_tables = Config::Get('sys_tables');
            $coords = $db->fetch("SELECT * FROM ".$this->sys_tables[$estate_type]." WHERE id = ?", $values['id']);
            $item = $db->fetch("SELECT * FROM ".$this->sys_tables[$estate_type]." WHERE  lat = ".$coords['lat']." AND lng = ".$coords['lng']." AND group_id > 0");
            if( empty( $item)){
                //пролчуение макс. group_id
                $id = $db->fetch("SELECT MAX(group_id) as max_id FROM ".$this->sys_tables[$estate_type]);
                $id = $id['max_id'] + 1;
                if(!empty($robot)) $this->fields['group_id'] = $id;
                else $db->querys("UPDATE ".$this->sys_tables[$estate_type]." SET group_id=? WHERE id=?", $id, $values['id']);
            }  else {
                if(!empty($robot)) $this->fields['group_id'] = $item['group_id'];
                else $db->querys("UPDATE ".$this->sys_tables[$estate_type]." SET group_id=? WHERE id=?", $item['group_id'], $values['id']);
                
            }
        }
    }         
    public function getYandexComplexById($yandex_house_id = 0, $yandex_building_id = 0){
        global $db;
        if( !empty( $yandex_house_id)) {
            $where = ' yandex_house_id = '.$yandex_house_id;
            $item = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE $where");
            if(!empty($item)) return $item;
        }
        if( empty( $yandex_building_id)) return false;
        $where = ' yandex_building_id = '.$yandex_building_id;
        $item = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE $where");
        if( empty( $item) || empty($item['id'])) return false;
        return $item;
    } 
        
    /**
    * получение значения комплексаулицы, дома и корпуса из текстового адреса
    * @param int $type - 1-ЖК, 2-КП, 3-БЦ
    * @param int external_id - внешний ID
    * @param string external_title - название комплекса
    * @return int
    */
    public function getComplexId($type, $external_id = false, $external_title = false, $id_region = false, $id_area = false, $id_street = false){
        global $db, $estate_complexes_log;
        $external_title = addslashes($external_title);
        $where = (!empty($external_id) ? ' external_id = '.$external_id :  " external_title = '".$external_title."'");
        
        $item = $db->fetch("SELECT id_complex FROM ".$this->sys_tables['estate_complexes_external']." WHERE id_user = ? AND type = ? AND $where",$this->fields['id_user'], $type);
        
        //если не нашли, пихаем строчку в estate_complexes_external
        if(!empty($item) && !empty($external_id)) return $item['id_complex'];
        elseif(!empty($item)){
            $tablename = ($type == 1 ? "housing_estates" : ($type == 2 ? "cottages" : "business_centers"));
            $complexes = $db->fetchall("SELECT id FROM ".$this->sys_tables[$tablename]." WHERE title LIKE '".$external_title."%'",false);
            if(count($complexes) > 1){
                $complex_where = [];
                if(!empty($id_region)) $complex_where[] = " id_region = ".Convert::ToInt($id_region);
                if(!empty($id_area)) $complex_where[] = " id_area = ".Convert::ToInt($id_area);
                $complex_where = implode(" AND ",$complex_where);
                if(!empty($complex_where))
                    $complex = $db->fetch("SELECT id 
                                           FROM ".$this->sys_tables[$tablename]." 
                                           WHERE title LIKE '".$external_title."%'".(!empty($complex_where) ? " AND ".$complex_where : ""));
                $item = (!empty($complex) ? array('id_complex' => $complex['id']) : array('id_complex' => $item['id_complex']));
            }
            return $item['id_complex'];
        } 
        elseif(!empty($external_title)) {
            switch($type){
                case 1: $type_title = 'ЖК'; break;
                case 2: $type_title = 'КП'; break;
                case 3: $type_title = 'БЦ'; break;
            }
            $estate_complexes_log[] = $type_title.': '.$external_title;
            $db->querys("INSERT INTO ".$this->sys_tables['estate_complexes_external']." (id_user,external_id,external_title,type) VALUES (?,?,?,?)",$this->fields['id_user'],empty($external_id)?0:$external_id,$external_title,$type);
        }
    }
    /**
    * получение статуса платного объекта 
    * @param mixed $value - значение
    * @param string $field - поле поиска
    * @return string
    */    
    public function getStatus($value){
       
       $status_info = $this->object_statuses[$value];
       if(!empty($status_info) && !empty($status_info['alias'])){
           $this->fields['status'] = $status_info['id'];
           $date_end = new DateTime("+".$status_info['days_last']." day");
           $this->fields['status_date_end'] = $date_end->format("Y-m-d");
       } else{
           $this->fields['status'] = 2;
           $this->fields['status_date_end'] = '0000-00-00';
       } 
    }
    public function checkLimits(){
        global $process,$counter, $deal_type;
        //проверка лимита
        
        //общее количество
        if(!empty($process['total_objects'])) $check_limit = $counter['live_sell'] + $counter['live_rent'] +
                                                             $counter['build'] + 
                                                             $counter['country_sell'] + $counter['commercial_sell'] +
                                                             $counter['country_rent'] + $counter['commercial_rent'] < $process['total_objects'];
        else
        //как раньше, по разделам
        $check_limit = ($this->estate_type.$deal_type == 'live_rent' && $counter[$this->estate_type.$deal_type] < $process[$this->estate_type.$deal_type.'_objects']) || 
                        ($this->estate_type.$deal_type != 'live_rent' && 
                                  ( 
                                    ($process['id_tarif']==7 && $process[$this->estate_type.$deal_type.'_objects'] == 0) || 
                                    ($process['id_tarif']==8 && $this->estate_type != 'build' ) || 
                                    ($counter[$this->estate_type.$deal_type] < $process[$this->estate_type.$deal_type.'_objects'])
                                   )
                        ); 
        //отдельно лимит выделенных
        if($check_limit && $this->fields['status'] > 2){
            
            switch($this->fields['status']){
                case 3: $status_alias = "promo";
                        break;
                case 4: $status_alias = "premium";
                        break;
                case 6: $status_alias = "vip";
                        break;
            }
            ++$counter[$this->estate_type.( empty( $deal_type) ? "" : $deal_type)."_".$status_alias];
            
            $check_limit = ($counter['live_sell_'.$status_alias] + $counter['live_rent_'.$status_alias] + $counter['build_'.$status_alias] + 
                            $counter['country_sell_'.$status_alias] + $counter['country_rent_'.$status_alias] + $counter['commercial_sell_'.$status_alias] + 
                            $counter['commercial_rent_'.$status_alias] ) <= $process[$status_alias];
            
            
            if( empty( $check_limit)){
                --$counter[$this->estate_type.( empty( $deal_type) ? "" : "_".$deal_type)."_".$status_alias];
                $check_limit = true;
                $this->fields['status_date_end'] = '0000-00-00';
                $this->fields['status'] = 2;
            }
        }
        return $check_limit;
    }
    
    
}

/**
* Обработка полей из bn.xml
*/
class BNXmlRobot extends Robot{
    public $file_format = 'bnxml';
    public $mapping = array(
                            'xml'         => array('external_id', 'action_id',  'metro_id',   'so',             'sg',          'su',                'sk',               'sg_str',        'address',     'type_id',          'region_id',        'price_str',    'description',  'phone',            'level', 'deadline',            'build_complex_id',     'house_id',         'heat',      'electr',          'water',            'action_id',    'kkv',          'igs',          'undist',     'undist_id',      'has_phone',    'has_refrigerator',     'has_furniture',    'has_washing_machine',  'san_id',    'entrance',          'protection','parking','sewerage',    'entry', 'contractor', 'asignment')
                            ,'live'       => array('external_id', 'rent',       'id_subway',  'square_full',    'square_live',  '',                 'square_kitchen',   'square_rooms',  'txt_addr',    'id_type_object',   'id_district',      'cost',         'notes',        'seller_phone',     'level',  '',                   '',                     'id_building_type', '',          '',                '',                 'rent',         'rooms_total',  '',             'way_time',   'id_way_type',    'phone',        'refrigerator',         'furniture',        'wash_mash',            'id_toilet', '',                  '',          '',       '',            '',      '')
                            ,'build'      => array('external_id', 'rent',       'id_subway',  'square_full',    'square_live',  '',                 'square_kitchen',   'square_rooms',  'txt_addr',    '',                 'id_district',      'cost',         'notes',        'seller_phone',     'level', 'id_build_complete',   'id_housing_estate',    'id_building_type', '',          '',                '',                 'rent',         'rooms_total',  '',             'way_time',   'id_way_type',    '',             '',                     '',                 '',                     'id_toilet', '',                  '',          '',       '',            '',      'contractor', 'asignment')
                            ,'country'    => array('external_id', 'rent',       '',           'square_full',    'square_live',  'square_ground',    '',                 '',              'txt_addr',    'id_type_object',   'id_district_area', 'cost',         'notes',        'seller_phone',     'level',  '',                   '',                     '',                 'id_heating','id_electricity',  'id_water_supply',  'rent',         'rooms',        'id_ownership', 'way_time',   'id_way_type',    '',             '',                     '',                 '',                      '',         '',                  '',          '',       '', '')
                            ,'commercial' => array('external_id', 'rent',       'id_subway',  'square_full',    '',             '',                 '',                 '',              'txt_addr',    'id_type_object',   'id_district',      'cost',         'notes',        'seller_phone',     'level',  '',                   '',                     '',                 '',          '',                '',                 'rent',         '',             '',             'way_time',   'id_way_type',    '',             '',                     '',                 '',                      '',         'transport_entrance','security',  'parking','canalization','id_enter', '')
    );
    /**
    * обработка полученных из bn.xml значений
    * @return array of arrays
    */
    public function getConvertedFields($values, $agency,$photos_limit = false,$return_deal_type = false){
        foreach($values as $k=>$val) {
            $values[strtolower($k)] = !is_array($val) ? $val : (!empty($val) ? $val : false);
        }
        
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        
        $values['external_id'] = preg_replace("|\D|", "", $values['external_id'] );
        if( empty( $values['external_id'])){
            $errors_log['external_id'][] = $values['external_id'];
            return false;
        }
        if( empty( $values['type_id'])){
            $errors_log['estate_type'][$values['external_id']] = 5;
            return false;
        }
        //получение типа недвижимости и типа объекта 
        if($values['type_id']==14) $this->estate_type='build';
        elseif($values['type_id']==31){   //элитная квартира
            $values['type_id'] =  $this->getEstateType(16);
        }
        elseif($values['type_id']==29){   //участок в котт -> земельный участок в загородке
            $values['type_id'] =  $this->getEstateType(11);
        }
        elseif($values['type_id']==33){   //элитный дом
            $values['type_id'] =  $this->getEstateType(9);
        }
        elseif($values['type_id']==32){   //элитная строящаяся квартира
            $this->estate_type='build';
        }
        else $values['type_id'] =  $this->getEstateType($values['type_id']);
        if( empty( $this->mapping[$this->estate_type])){
            $errors_log['estate_type'][$values['external_id']] = 5;
            return false;
        }           
        //введено дополнительное условие для обработки студий (rooms_total = 0)
        foreach ($this->mapping[$this->estate_type] as $key=>$column){
            if($column!='' && 
               (!empty($values[$this->mapping['xml'][$key]]) || 
                ($column == 'rooms_total' && Validate::isDigit($values[$this->mapping['xml'][$key]]) && $values[$this->mapping['xml'][$key]] >= 0)
                )){
                $this->fields[$column] = $values[$this->mapping['xml'][$key]];
            }
        }
        //аренда/продажа
        $this->fields['rent'] = $values['action_id'] == 1 ? 2 : 1;
        //если почему-то в стройке аренда, ставим продажу
        if($this->estate_type == 'build') $this->fields['rent'] = 2;
        //только тип недвижимости + сделка
        if(!empty($return_deal_type)) return $this->fields;
        
        //если тип объекта указан "квартира", rooms_sale = rooms_total
        if($values['type_id'] == 16){
            if(!empty($this->fields['rooms_total'])) $this->fields['rooms_sale'] = $this->fields['rooms_total'];
            else $this->fields['rooms_total'] = $this->fields['rooms_sale'];
        } 
        
        //записываем кусок XML из которого достают адрес
        $this->fields['addr_source'] = "<area_id>".( empty( $values['id_area'])?"":$values['id_area'])."</area_id><region>".( empty( $values['region'])?"":$values['region'])."</region><region_id>".( empty( $values['region_id'])?"":$values['region_id'])."</region_id><metro_id>".( empty( $values['metro_id'])?"":$values['metro_id'])."</metro_id><address>".( empty( $values['address'])?"":$values['address'])."</address>";
        
        //переопределение значений некоторых полей
        //район
        $this->fields['id_area'] = $this->fields['id_street'] = $this->fields['id_city'] = $this->fields['id_place'] = 0;
        
        if(!empty($values['region_id'])){
            if($values['region_id']==14) $values['region_id'] = 24; //Всеволожский район почему-то попал в городские
            elseif($values['region_id']==18) $values['region_id'] = 20; //Павловский суем в Пушкинский
            $region_id = Convert::ToInteger($values['region_id']);
        }else $region_id = 0;
        if($region_id>20){ //район ЛО
            $this->fields['id_region']  = 47; //регион ЛО
            $this->fields['id_district'] = 0;
            //получение названия района
            $district_title = $this->getInfoFromTable($this->sys_tables['district_areas'],$region_id,false,false,'title');
            //получение id района
            $area = $db->fetch("SELECT `id_area` FROM ".$this->sys_tables['geodata']." WHERE a_level=2 AND id_region=47 AND offname=?",$district_title);
            $this->fields['id_area'] = $area['id_area'];
        } else { //СПб
            $this->fields['id_region']  = 78; //регион СПб 
            if(!empty($values['region_id'])){
                $this->fields['id_district'] = $this->getInfoFromTable($this->sys_tables['districts'],$values['region_id'],false,false,'id');
                //$this->fields['id_district'] = $district['id'];  //район города 
            }
        }
        
        //улица (получение так же id_area, id_place) 
        if(!empty($values['address'])){
            //$this->getAddress($values['address']);
            //если не получилось так, задействуем более мощный метод
            $fulladdr = ( empty( $values['region']) ? ( $this->fields['id_region'] == '78' ? 'Санкт-Петербург, ' : 'Ленинградская область, ' ) : $values['region'] . ", " ) . ( empty( $values['district'] ) ? "" : $values['district'] . ", "  ) . ( empty( $values['address'] ) ? "" : $values['address'] );
            if( empty( $this->fields['id_street'] ) && empty( $this->fields['id_place'] ) ) $this->getGeodataDdata( $fulladdr );
            if( empty( $this->fields['id_street'] ) && empty( $this->fields['id_place'] ) ) $this->getTxtGeodata($values['address']);
        } 
        else $this->fields['txt_addr'] = '';
        
        
        //при отсутствии полей, утсанавливаем в 0, чтобы они затерлись
        if( empty( $this->fields['id_street'])) $this->fields['id_street'] = 0;
        if( empty( $this->fields['id_city'])) $this->fields['id_city'] = 0;
        if( empty( $this->fields['id_place'])) $this->fields['id_place'] = 0;
        if( empty( $this->fields['id_district'])) $this->fields['id_district'] = 0;
        if( empty( $this->fields['id_area'])) $this->fields['id_area'] = 0;
        if( empty( $this->fields['house'])) $this->fields['house'] = 0;
        if( empty( $this->fields['corp'])) $this->fields['corp'] = 0;
        
        //группировка объектов по адресу
        $this->groupByAddress($this->estate_type, $this->fields, true);
        //метро
        if(!empty($values['metro_id'])) {
            $this->fields['id_subway'] = $this->getInfoFromTable($this->sys_tables['subways'],$values['metro_id'],false,false,'id');
            //$this->fields['id_subway'] = $subway['id'];
        }
        else $this->fields['id_subway']=0; 

        //префикс для жилой аренды/продажи
        $this->fields['rent_prefix'] = $this->estate_type != 'build' ? ($this->fields['rent'] == 1 ? '_rent' : '_sell') : '';
        
        //аренда посуточно (для жилой)
        if(!empty($values['lease_period']) && $values['lease_period']==2 && $this->estate_type=='live') $this->fields['by_the_day']=1; 
        //цена
        if(!empty($values['price'])) $this->fields['cost'] = Convert::ToInteger($values['price']);
        //преобразование цены по типу 
        if(!empty($values['price_type_id'])) $this->convertCost($values['price_type_id']);
        //телефон продавца
        if(!empty($values['phone']))  $this->fields['seller_phone'] = $values['phone'];
        else $this->fields['seller_phone'] = (!empty($values['phone_kod'])?$values['phone_kod'].' ':'').(!empty($values['phone_'])?$values['phone_']:'');
        
        //email продавца (для агрегаторов)
        if(!empty($values['email']) && Validate::isEmail($values['email']))  $this->fields['seller_email'] = trim($values['email']);

        //название (имя) продавца
        if(!empty($values['firmname'])) $this->fields['seller_name'] = $values['firmname'];          
        elseif(!empty($values['firm'])) $this->fields['seller_name'] = $values['firm'];          
        //комнатность (для жилой и стройки)
        if(isset($values['kkv']) && Validate::isDigit($values['kkv']) ){
            if(isset($values['k_sales']) && !Validate::isDigit($values['k_sales'])){
                if($values['type_id'] == 17) { $this->fields['rooms_sale'] = 1;} //для новостроек кол-во комнат выставляем 1
                else $this->fields['rooms_sale'] = $values['kkv'];
            } else $this->fields['rooms_sale'] = $values['kkv'];
        }
        //для комнат с указанной комнатностью
        if(isset($values['kkv_total']) && Validate::isDigit($values['kkv_total'])) {
            $this->fields['rooms_total'] = $values['kkv_total'];
            
            //комнатность указана неверно
            if( $this->estate_type == 'live' && $this->fields['id_type_object']==2 && 
                    (
                        empty($this->fields['rooms_sale']) || 
                        $this->fields['rooms_sale'] > $this->fields['rooms_total'] || 
                        ( $this->fields['rooms_sale'] == $this->fields['rooms_total'] && $this->fields['rooms_total'] == 1 ) 
                    )
            )
            {
                $errors_log['rooms'][$values['num_rec']] = 'комнат: ' . ( !empty($this->fields['rooms_sale']) ? $this->fields['rooms_sale'] : 0) . ', комнатность: ' . ( !empty($this->fields['rooms_total']) ? $this->fields['rooms_total'] : 0);
                return false;
            }

            if($this->fields['rooms_total'] == 0){
                $this->fields['rooms_total'] = ( empty($this->fields['rooms_sale']) ? 0 : $this->fields['rooms_sale'] );
                $this->fields['rooms_sale'] = ( empty($this->fields['rooms_sale']) ? 0 : $this->fields['rooms_sale'] );
            }
        }
        //этаж/этажность
        if(!empty($this->fields['level'])){
            $level = explode('/',$this->fields['level']);
            $this->fields['level'] = $level[0];
            if(!empty($level[1])) $this->fields['level_total'] = $level[1];
        }

        //источник добавления BNXML
        $this->fields['info_source'] = 2; 
        //тип дома
        if(!empty($this->fields['id_building_type'])) {
            $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],$values['house_id'],false,false,'id');
            //$this->fields['id_building_type'] = $building_type['id'];
        }
        
        //тип входа
        if(!empty($this->fields['id_enter'])) {
            $this->fields['id_enter'] = $this->getInfoFromTable($this->sys_tables['enters'],$this->fields['id_enter'],false,false,'id');
            //$this->fields['id_enter'] = $entrance_type['id'];
        }
        
        //срок сдачи для стройки
        if($this->estate_type=='build' && !empty($values['deadline'])) {
            if($this->fields['id_build_complete']=='сдан') {
                $this->fields['build_completed'] = 1;
                $this->fields['id_build_complete'] = 4;
            } elseif($this->fields['id_build_complete']=='госком.') $this->fields['id_build_complete'] = 5; 
            else {
                $deadline = explode('кв', str_replace(array('.','г'),' ',$this->fields['id_build_complete']));
                if(!empty($deadline[0])&&!empty($deadline[1])) {
                    $decade = trim(Convert::ToString($deadline[0]));
                    $year = Convert::ToInteger(preg_replace('/[^0-9]/sui','',$deadline[1]));
                    if($year<2000) $year = $year+2000;
                    $decades = array('I'=>1,'II'=>2,'III'=>'3','IV'=>4, '1'=>1,'2'=>2,'3'=>'3','4'=>4);
                    if(!empty($decades[$decade])){
                        $deadline_res = $db->fetch("SELECT `id` FROM ".$this->sys_tables['build_complete']." WHERE `year`=? AND `decade`=?",$year,$decades[$decade]);
                        if(!empty($deadline_res)) $this->fields['id_build_complete'] =  $deadline_res['id'];
                    } else $this->fields['id_build_complete']=0;
                } else $this->fields['id_build_complete']=0;
            }    
        }
/////////////////////////////////////////////////////////////////////////////////
// НОВЫЕ ПОЛЯ
/////////////////////////////////////////////////////////////////////////////////
        //способ добраться до метро
        if(!empty($this->fields['id_way_type']) && !empty($this->fields['way_time'])) {
            $this->fields['id_way_type'] = $this->getInfoFromTable($this->sys_tables['way_types'],$this->fields['id_way_type'],false,false,'id');
            //$this->fields['id_way_type'] = $way_type['id'];
        }

        if($this->estate_type=='live'){ //удобства для жилой
            //телефон
            if(!empty($this->fields['phone'])) $this->fields['phone'] = $this->fields['phone']=='+'?1:2;
            //холодильник
            if(!empty($this->fields['refrigerator'])) $this->fields['refrigerator'] = $this->fields['refrigerator']=='+'?1:2;
            //мебель
            if(!empty($this->fields['furniture'])) $this->fields['furniture'] = $this->fields['furniture']=='+'?1:2;
            //стиралка
            if(!empty($this->fields['wash_mash'])) $this->fields['wash_mash'] = $this->fields['wash_mash']=='+'?1:2;
        }
        if(!empty($this->fields['id_ownership'])){
            $this->fields['id_ownership'] = $this->getInfoFromTable($this->sys_tables['ownerships'],$this->fields['id_ownership'],false,false,'id');
            //$this->fields['id_ownership'] = $ownerships['id'];
        }
        if(!empty($this->fields['id_toilet'])){
            $this->fields['id_toilet'] = $this->getInfoFromTable($this->sys_tables['toilets'],$this->fields['id_toilet'],false,false,'id');
            //$this->fields['id_toilet'] = $toilets['id'];
        }
        
/////////////////////////////////////////////////////////////////////////////
        
        if($this->estate_type=='country'){ //снабжение для загородки
            //отопление
            if(!empty($values['heat'])) $this->fields['id_heating'] = $values['heat']=='+'?2:3;
            //электричество
            if(!empty($values['electr'])) $this->fields['id_electricity'] = $values['electr']=='+'?2:3;
            
            //водоснабжение - для JCAT расширенные значения
            if(!empty($values['water'])){
                
                if($agency['id'] == 4467){
                    $this->fields['id_water_supply'] = $this->getInfoFromTable($this->sys_tables['water_supplies'],trim($values['water']),'title',false,'id');
                    //if(!empty($this->fields['id_water_supply'])) $this->fields['id_water_supply'] = $this->fields['id_water_supply']['id'];
                } 
                else $this->fields['id_water_supply'] = $values['water']=='+'?2:3;
            }
            
            //газ - для JCAT расширенные значения
            if(!empty($values['gas'])){
                if($agency['id'] == 4467){
                    $this->fields['id_gas'] = $this->getInfoFromTable($this->sys_tables['gases'],trim($values['gas']),'title',false,'id');
                    //if(!empty($this->fields['id_gas'])) $this->fields['id_gas'] = $this->fields['id_gas']['id'];
                } 
                else $this->fields['id_gas'] = $values['gas']=='+'?2:3;
            } 
        }
        //статус объекта
        if(!empty($values['viewtype'])) $this->getStatus($values['viewtype']);
        else{
            $this->fields['status'] = 2;
            $this->fields['status_date_end'] = '0000-00-00';
        }
                
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) || $this->fields['id_user'] == 29298 ) {
            if( !empty( $values['gmap_attr']['lat'] ) && !empty( $values['gmap_attr']['lng'] ) ) {
                $this->fields['lat'] = Convert::ToValue( $values['gmap_attr']['lat'] );
                $this->fields['lng'] = Convert::ToValue( $values['gmap_attr']['lng'] );
            } else {
                if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                    list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                    //добавление адреса в таблицу адресов с коорлинатами 
                    if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
                }
            }
        }
        
        //общая площадь почему-то массив
        if(!empty($values['so_attr'][0])) $this->fields['square_full'] = $values['so_attr'][0];

        //картинки
        if(!empty($values['images']['image'])){
            if( empty( $values['images']['image'][0])){
                $img = is_array($values['images']) ?  $values['images']['image']['file_name_big'] : $values['images'];
                if($this->checkPhoto($img)){
                    $this->fields['images'][] = $img;
                    $this->fields['main_photo'] = $img;
                }
            }else{
                $count_photos_limit = 0;
                foreach($values['images']['image'] as $key=>$img){
                    if( empty( $photos_limit) || $photos_limit>$count_photos_limit){
                        if($this->checkPhoto(trim( $img['file_name_big'] ))){
                            $this->fields['images'][] = trim( $img['file_name_big'] );
                            if(isset($img['sort']) && $img['sort'] == 0) $this->fields['main_photo'] = $img['file_name_big'];
                            ++$count_photos_limit;
                        }
                    }
                }
            }
        }
        //обрезка всех ненужных тегов в примечании
        if(!empty($this->fields['notes'])) {
            //для Итаки - оставляем ID, помеченный как #[0-9\-]+
            if($agency['id'] == 19){
                preg_match("/\#[0-9\-]+/sui",$this->fields['notes'],$itaka_external_id);
                $this->fields['notes'] = preg_replace("/\#[0-9\-]+/sui",'#itaka-id',$this->fields['notes']);
            } 
            else $itaka_external_id = "";
            $this->fields['notes'] = str_replace(array('<![CDATA[',']]>'),'',$this->fields['notes']);
            $this->fields['notes'] = strip_tags($this->fields['notes'],"<div><p><a><span><b><strong><u><i><em>");
            $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
            if(!empty($itaka_external_id)) $this->fields['notes'] = str_replace('#itaka-id',array_pop($itaka_external_id),$this->fields['notes']);
        }

        //Принадлежность к комплексам
        if($this->estate_type=='build') {
            if(!empty($values['build_complex_id'])) 
                $this->fields['id_housing_estate']  = $this->getComplexId(1,$values['build_complex_id'],false,$this->fields['id_region'],$this->fields['id_area'],$this->fields['id_street']);
            elseif(!empty($values['build_complex_title']))  
                $this->fields['id_housing_estate']  = $this->getComplexId(1,false,$values['build_complex_title'],$this->fields['id_region'],$this->fields['id_area'],$this->fields['id_street']);
            elseif(!empty($values['building_name']))  
                $this->fields['id_housing_estate']  = $this->getComplexId(1,false,$values['building_name'],$this->fields['id_region'],$this->fields['id_area'],$this->fields['id_street']);
            if( !empty( $this->fields['id_housing_estate'] ) ) $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$this->fields['id_housing_estate']);
            if(!empty($complex_info)){
                $this->fields['lat'] = $complex_info['lat'];
                $this->fields['lng'] = $complex_info['lng'];
            }
        } elseif($this->estate_type=='commercial') {
            if(!empty($values['business_center_id'])) 
                $this->fields['id_business_center']  = $this->getComplexId(3,$values['business_center_id'],false,$this->fields['id_region'],$this->fields['id_area'],$this->fields['id_street']);
            elseif(!empty($values['business_center_title'])) 
                $this->fields['id_business_center']  = $this->getComplexId(3,false,$values['business_center_title'],$this->fields['id_region'],$this->fields['id_area'],$this->fields['id_street']);
            if(!empty($this->fields['id_business_center']) ) $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['business_centers']." WHERE id = ?", $this->fields['id_business_center']);
            if(!empty($complex_info)){
                $this->fields['lat'] = $complex_info['lat'];
                $this->fields['lng'] = $complex_info['lng'];
            }
        } elseif($this->estate_type=='country') {
            if(!empty($values['cottage_id'])) $this->fields['id_cottage']  = $this->getComplexId(2,$values['cottage_id'],false);
            elseif(!empty($values['cottage_title']))  $this->fields['id_cottage']  = $this->getComplexId(2,false,$values['cottage_title']);
        }

        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 
        
        return $this->fields;        
    }
}                                                   

class EMLSXmlRobot extends Robot{
    public $file_format = 'emlsxml';
    
    /**
    * обработка полученных из emls.xml значений
    * @return array of arrays
    */
    public function getConvertedFields($values, $agency,$photos_limit = false,$return_deal_type = false){
        foreach($values as $k=>$val) {
            $values[strtolower($k)] = !is_array($val) ? $val : (!empty($val) ? $val : false);
        }
        
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        
        if( empty( $values['building']) || $values['status'] != 'в продаже') return false;
        
        //получение типа недвижимости и типа объекта 
        $this->estate_type = 'build';
        
        if($this->estate_type != 'build'){
            $errors_log['estate_type'][$values['external_id']] = 5;
            return false;
        }else $this->fields['id_type_object'] = 1;
        
        //аренда/продажа
        $this->fields['rent'] = 2;
        
        //только тип недвижимости + сделка
        if(!empty($return_deal_type)) return $this->fields;
        
        //записываем кусок XML из которого достают адрес
        $this->fields['addr_source'] = Convert::ToString($values['building']);
        
        //при отсутствии полей, утсанавливаем в 0, чтобы они затерлись
        if( empty( $this->fields['id_street'])) $this->fields['id_street'] = 0;
        if( empty( $this->fields['id_city'])) $this->fields['id_city'] = 0;
        if( empty( $this->fields['id_place'])) $this->fields['id_place'] = 0;
        if( empty( $this->fields['id_district'])) $this->fields['id_district'] = 0;
        if( empty( $this->fields['id_area'])) $this->fields['id_area'] = 0;
        if( empty( $this->fields['house'])) $this->fields['house'] = 0;
        if( empty( $this->fields['corp'])) $this->fields['corp'] = 0;
        $this->fields['id_subway'] = 0;
        
        //адрес читаем из ЖК
        $values['building'] = preg_replace('/[^А-я\s0-9]/sui','',$values['building']);
        preg_match("/(?<=ЖК\s)[^\s]+/sui",$values['building'],$housing_estate_title);
        
        if( !empty( $housing_estate_title ) ) {
            $this->fields['id_housing_estate']  = $this->getInfoFromTable($this->sys_tables['housing_estates'],$housing_estate_title[0],'title',false,'id');
            if( !empty( $this->fields['id_housing_estate'])) $complex_info = $db->fetch("SELECT * FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$this->fields['id_housing_estate']);
            if( !empty($complex_info)){
                $this->fields['id_region'] = $complex_info['id_region'];
                $this->fields['id_area'] = $complex_info['id_area'];
                $this->fields['id_district'] = $complex_info['id_district'];
                $this->fields['id_city'] = $complex_info['id_city'];
                $this->fields['id_place'] = $complex_info['id_place'];
                $this->fields['id_street'] = $complex_info['id_street'];
                $this->fields['lat'] = $complex_info['lat'];
                $this->fields['lng'] = $complex_info['lng'];
                $this->fields['txt_addr'] = $complex_info['txt_addr'];
                $this->fields['id_building_type'] = $complex_info['id_building_type'];
                $complex_info['floors'] = explode('-',$complex_info['floors']);
                $this->fields['level_total'] = $complex_info['floors'][0];
            }
        }

        if( !empty( $housing_estate_title ) ) $this->fields['notes'] = str_replace("ЖК ".$housing_estate_title,'',$values['building']);
        
        //группировка объектов по адресу
        $this->groupByAddress($this->estate_type, $this->fields, true);
        
        if(!empty($values['price'])) $this->fields['cost'] = Convert::ToInteger($values['price']);
        
        if(!Validate::isDigit($values['kkv'])){
            $this->fields['rooms_sale'] = 0;
        } else $this->fields['rooms_sale'] = Convert::ToInt($values['kkv']);
        
        //этаж/этажность
        if(!empty($this->fields['level'])){
            $level = explode('/',$this->fields['level']);
            $this->fields['level'] = $level[0];
            if(!empty($level[1])) $this->fields['level_total'] = $level[1];
        }
        
        //источник добавления EMLSXML
        $this->fields['info_source'] = 9;
        
        $this->fields['seller_phone'] = $agency['phone_1'];
        
        $this->fields['cost'] = Convert::ToInt($values['price']);
        
        $this->fields['external_id'] = Convert::ToInt($values['external_id']);
        
        if(!empty($values['so'])) $this->fields['square_full'] = $values['so'];
        if(!empty($values['sl'])) $this->fields['square_rooms'] = $values['sl'];
        if(!empty($values['sl'])) $this->fields['square_live'] = array_sum(explode('+',$values['sl']));
        if(!empty($values['sk'])) $this->fields['square_kitchen'] = $values['sl'];
        
                //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                //добавление адреса в таблицу адресов с коорлинатами 
                if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 

        
        return $this->fields;        
    }
}



/**
* Обработка полей из eip.xml
*/
class EIPXmlRobot extends Robot{
    public $file_format = 'eipxml';

    public $mapping = array(
            'xml'           => array('num_rec',     'address',  'room',         'n_room',       'metro',        'what',             'oper', 'price','metres',       'area',         'landarea',     'live',         'kitch',            'tip',              'floor',    'n_floor',      'phone',        'name',         't',    'l',            'b',        'water',        'vanna',        'note',     'dt_fin',               'build_complex_id',     'sosed',        'cond',         'podezd',               'potolok',          'kanaliz',      'communic',     'parking',  'ohrana',   'mebel',        'holod',        'stirka',       'latitude', 'longitude',    'heating_supply',   'water_supply',     'electricity_supply',   'gas_supply', 'contractor', 'asignment')
            ,'live'         => array('external_id', 'txt_addr', 'rooms_sale',   'rooms_total',  'id_subway',    'id_type_object',   'rent', 'cost', 'square_rooms', 'square_full',  '',             'square_live',  'square_kitchen',   'id_building_type', 'level',    'level_total',  'seller_phone', 'seller_name',  'phone','id_elevator',  'id_balcon','',             'id_toilet',    'notes',    '',                     '',                     'neighbors',    'id_facing',    '',                     'ceiling_height',   '',             '',             '',         '',         'furniture',    'refrigerator', 'wash_mash',    'lat',      'lng',          '',                 '',                 '',                     '',           ''          )
            ,'build'        => array('external_id', 'txt_addr', '',             'rooms_sale',   'id_subway',    '',                 'rent', 'cost', 'square_rooms', 'square_full',  '',             'square_live',  'square_kitchen',   'id_building_type', 'level',    'level_total',  'seller_phone', 'seller_name',  '',     'id_elevator',  'id_balcon','',             'id_toilet',    'notes',    'id_build_complete',    'id_housing_estate',    '',             'id_facing',    '',                     'ceiling_height',   '',             '',             '',         '',         '',             '',             '',             'lat',      'lng',          '',                 '',                 '',                     '',           'contractor', 'asignment'          )
            ,'country'      => array('external_id', 'txt_addr', '',             'rooms',        'id_subway',    'id_type_object',   'rent', 'cost', '',             'square_full',  'square_ground','square_live',  '',                 '',                 'level',    'level_total',  'seller_phone', 'seller_name',  'phone','',             '',         '',             'id_toilet',    'notes',    '',                     '',                     '',             '',             '',                     '',                 '',             '',             '',         '',         '',             '',             '',             'lat',      'lng',          'id_heating',       'id_water_supply',  'id_electricity',       'id_gas',     ''    )
            ,'commercial'   => array('external_id', 'txt_addr', '',             '',             'id_subway',    'id_type_object',   'rent', 'cost', '',             'square_full',  '',             '',             '',                 '',                 'level',    'level_total',  'seller_phone', 'seller_name',  '',     '',             '',         'hot_water',    '',             'notes',    '',                     '',                     '',             'id_facing',    'transport_entrance',   'ceiling_height',   'canalization', 'service_line', 'parking',  'security', '',             '',             '',             'lat',      'lng',          '',                 '',                 'electicity',           '',           ''         )
    );
    /**
    * обработка полученных из bn.xml значений
    * @return array of arrays
    */
    public function getConvertedFields($values, $agency,$photos_limit = false,$return_deal_type = false){
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        //получение типа недвижимости и типа объекта 
        if($values['what']=='ксд') $this->estate_type='build';
        else {
            if($values['what']=='гар') $values['what'] = 'скл';
            $values['what'] =  $this->getEstateType($values['what']);
        }
        if( empty( $this->mapping[$this->estate_type])){
            $errors_log['estate_type'][$values['num_rec']] = $values['type_id'];
            return false;
        }
        foreach ($this->mapping[$this->estate_type] as $key=>$column){
            if(($column!='' && !empty($values[$this->mapping['xml'][$key]]) && !is_array($values[$this->mapping['xml'][$key]])) || ($column == 'n_room' && $values[$this->mapping['xml'][$key]])){
                $this->fields[strtolower($column)] = $values[$this->mapping['xml'][$key]];
            }
        }
        $values['num_rec'] = preg_replace("|\D|", "", $values['num_rec'] );
        if( empty( $values['num_rec'] ) ) {
            $errors_log['external_id'][] = $values['num_rec'];
            return false;
        }
        //аренда/продажа
        $this->fields['rent'] = !empty($this->fields['rent']) && $this->fields['rent']==5?1:2; 
        //только тип недвижимости + сделка
        if(!empty($return_deal_type)) return $this->fields;

        //записываем кусок XML из которого достают адрес
        $this->fields['addr_source'] = "<region>".( empty( $values['region'])?"":$values['region'])."</region><district>".( empty( $values['district'])?"":$values['district'])."</district><metro>".( empty( $values['metro'])?"":$values['metro'])."</metro><address>".( empty( $values['address'])?"":$values['address'])."</address>";
        
        //переопределение значений некоторых полей
        //география
        //город
        $this->fields['id_area'] = $this->fields['id_street'] = $this->fields['id_city'] = $this->fields['id_place'] = 0;
        $district = mb_strtolower($values['district'],'UTF-8');
        if(preg_match("/(петербург|спб|санкт)/i",$district)) $this->fields['id_region'] = 78;
        else $this->fields['id_region'] = 47;        

        //район (области или города)
        if(!empty($values['region'])){
            
            if($this->fields['id_region'] == 78) { //район города
                $this->fields['id_district'] = $this->getInfoFromTable($this->sys_tables['districts'],$values['region'],'title',false,'id');    
                //$this->fields['id_district'] = $district['id'];
            }
            elseif($this->fields['id_region'] == 47) { //район области
                //получение id района
                $area = $db->fetch("SELECT `id_area` FROM ".$this->sys_tables['geodata']." WHERE a_level=2 AND id_region=47 AND offname=?",$values['region']);
                $this->fields['id_area'] = $area['id_area'];
            }
        }
        $original_txt_addr = $this->fields['txt_addr'];
        //улица (получение так же id_area, id_place) 
        if(!empty($values['address'])) {
            $street = $values['address'];
            $this->fields['house'] = !empty($values['address_attr']['home'])?$values['address_attr']['home']:false;
            $this->fields['corp'] = !empty($values['address_attr']['corp'])?$values['address_attr']['corp']:false;
            $this->fields['txt_addr'] .=   $this->fields['house']?', д.'. $this->fields['house']:'';
            $this->fields['txt_addr'] .=   $this->fields['corp']?', корп.'. $this->fields['corp']:'';
            //$this->getAddress(false,$street,$this->fields['house'],$this->fields['corp']);
            $this->fields['txt_addr'] = $original_txt_addr;
            if( empty( $this->fields['id_street'] ) && empty( $this->fields['id_place'] ) ) $this->getGeodataDdata( ( empty( $values['region']) ? "" : $values['region'] . ", " ) . ( empty( $values['district'] ) ? "" : $values['district'] . ", "  ) . ( empty( $values['address'] ) ? "" : $values['address'] ) );
            if( empty( $this->fields['id_street'] ) && empty( $this->fields['id_place'] ) ) $this->getTxtGeodata( $original_txt_addr );
            else $this->fields['txt_addr'] = $original_txt_addr;
        } else $this->fields['txt_addr'] = '';
        
        //при отсутствии полей, утсанавливаем в 0, чтобы они затерлись
        if( empty( $this->fields['id_street'])) $this->fields['id_street'] = 0;
        if( empty( $this->fields['id_city'])) $this->fields['id_city'] = 0;
        if( empty( $this->fields['id_place'])) $this->fields['id_place'] = 0;
        if( empty( $this->fields['id_district'])) $this->fields['id_district'] = 0;
        if( empty( $this->fields['id_area'])) $this->fields['id_area'] = 0;
        if( empty( $this->fields['house'])) $this->fields['house'] = 0;
        if( empty( $this->fields['corp'])) $this->fields['corp'] = 0;
        
        //группировка объектов по адресу
        $this->groupByAddress($this->estate_type, $this->fields, true);
        //метро
        if(!empty($values['metro'])) {
            $this->fields['id_subway'] = $this->getInfoFromTable($this->sys_tables['subways'],$values['metro'],'title',false,'id');
            //$this->fields['id_subway'] = $subway['id'];
            //способ добраться до метро
            if(!empty($values['metro_attr']['dist']) && !empty($values['metro_attr']['dist_type'])) {
                $this->fields['way_time'] = $values['metro_attr']['dist'];
                $this->fields['id_way_type'] = $this->getInfoFromTable($this->sys_tables['way_types'],$values['metro_attr']['dist_type'],false,false,'id');
                //$this->fields['id_way_type'] = $way_type['id'];
            }
        }
        else $this->fields['id_subway']=0; 
        
        //префикс для жилой аренды/продажи
        //$this->fields['rent_prefix'] = $this->estate_type != 'live' ? ($this->fields['rent'] == 1 ? '_rent' : '_sell') : '';
        //для всех
        $this->fields['rent_prefix'] = $this->fields['rent'] == 1 ? '_rent' : '_sell';
        
        //комнатность  для квартир и комнат в жилой
        if($this->estate_type=='live' && ($this->fields['id_type_object']==1 || $this->fields['id_type_object']==2) ) {
            //комнатность указана неверно
            if( $this->fields['id_type_object']==2 && 
                    (
                        empty($this->fields['rooms_sale']) || 
                        $this->fields['rooms_sale'] > $this->fields['rooms_total'] || 
                        ( $this->fields['rooms_sale'] == $this->fields['rooms_total'] && $this->fields['rooms_total'] == 1 ) 
                    )
            )
            {
                $errors_log['rooms'][$values['num_rec']] = 'комнат: ' . ( !empty($this->fields['rooms_sale']) ? $this->fields['rooms_sale'] : 0) . ', комнатность: ' . ( !empty($this->fields['rooms_total']) ? $this->fields['rooms_total'] : 0);
                return false;
            }


            if( empty( $this->fields['rooms_total']) && !empty($this->fields['rooms_sale']))   $this->fields['rooms_total'] = $this->fields['rooms_sale'];
            elseif( empty( $this->fields['rooms_sale']) && !empty($this->fields['rooms_total'])) $this->fields['rooms_sale'] = $this->fields['rooms_total'];
        }
        $this->fields['cost'] = 0;
        //цена
        if(!empty($values['price'])){
             $this->fields['cost'] = $values['price'];
            //преобразование цены по типу 
            if(!empty($values['price_attr']['condition'])) {
                $condition = $values['price_attr']['condition'];
                //в сутки, для жилой
                if($condition=='с' && $this->estate_type=='live') $this->fields['by_the_day'] = 1;
                elseif($condition=='м') { // р/м
                    if($this->estate_type=='commercial') { //для коммерческой
                        $this->fields['cost2meter'] = $this->fields['cost']; 
                        if(!empty($this->fields['square_full'])) $this->fields['cost'] = $this->fields['cost2meter']*$this->fields['square_full'];
                    } elseif(!empty($this->fields['square_full']))  $this->fields['cost'] = $this->fields['cost']*$this->fields['square_full'];
                }
            }
        }
        //тип дома
        if(!empty($this->fields['id_building_type'])) {
            $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],$this->fields['id_building_type'],false,false,'id');
            //$this->fields['id_building_type'] = $building_type['id'];
        }
        
        //телефон продавца
        if(!empty($values['phone']))  {
            $phone = '';
            //if(!empty($values['phone_attr']['country_code']) && !empty($values['phone_attr']['city_code'])) $phone .= $values['phone_attr']['country_code'].' ('.$values['phone_attr']['city_code'].') ';
            if(!empty($values['phone_attr']['country_code']))
                if($values['phone_attr']['country_code'] != '7' && $values['phone_attr']['country_code'] != '8') $phone .= $values['phone_attr']['country_code'];
            if(!empty($values['phone_attr']['city_code'])) $phone .= $values['phone_attr']['country_code'].' ('.$values['phone_attr']['city_code'].') ';
            $phone .= $values['phone'];
            $this->fields['seller_phone']  = $phone;
        }
                                                                        
        //источник добавления EIPXML
        $this->fields['info_source'] = 3; 

        //наличие лифта
        if(!empty($this->fields['id_elevator']) && $this->fields['id_elevator']==1) $this->fields['id_elevator']=2;
        
        //наличие балкона
        if(!empty($this->fields['id_balcon']) && $this->fields['id_balcon']==1) $this->fields['id_balcon']=2;
        
        //санузел
        if(!empty($this->fields['id_toilet']) && $this->fields['id_toilet']==1) $this->fields['id_toilet']=2;
        
        //наличие отопления
        if(!empty($this->fields['id_heating']) && $this->fields['id_heating']==1) $this->fields['id_heating']=2;
        
        //наличие водопровода
        if(!empty($this->fields['id_water_supply']) && $this->fields['id_water_supply']==1) $this->fields['id_water_supply']=2;
        
        //наличие электричества
        if(!empty($this->fields['id_electricity']) && $this->fields['id_electricity']==1) $this->fields['id_electricity']=2;
        
        //наличие газоснабжения
        if(!empty($this->fields['id_gas']) && $this->fields['id_gas']==1) $this->fields['id_gas']=2;
        
        //примечание
        if(!empty($values['full_description'])) $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($values['full_description']));

        //срок сдачи для стройки
        if(!empty($this->fields['id_build_complete'])){ 
            if($this->fields['id_build_complete']=='сдан') {
                $this->fields['build_completed'] = 1;
                $this->fields['id_build_complete'] = 4;
            }
            else {
                $deadline = explode('кв', str_replace(array('.','г'),' ',$this->fields['id_build_complete']));
                if(!empty($deadline[0])&&!empty($deadline[1])) {
                    $decade = trim(Convert::ToString($deadline[0]));
                    $year = Convert::ToInteger(preg_replace('/[^0-9]/sui','',$deadline[1]));
                    if($year<2000) $year = $year+2000;
                    $decades = array('I'=>1,'II'=>2,'III'=>'3','IV'=>4, '1'=>1,'2'=>2,'3'=>'3','4'=>4);
                    if(!empty($decades[$decade])){
                        $deadline_res = $db->fetch("SELECT `id` FROM ".$this->sys_tables['build_complete']." WHERE `year`=? AND `decade`=?",$year,$decades[$decade]);
                        if(!empty($deadline_res)) $this->fields['id_build_complete'] =  $deadline_res['id'];
                    } else $this->fields['id_build_complete']=0;
                } else $this->fields['id_build_complete']=0;
            }
        }

        //ремонт
        if(!empty($this->fields['id_facing'])){
            $this->fields['id_facing'] = $this->getInfoFromTable($this->sys_tables['facings'],$this->fields['id_facing'],false,false,'id');
            //$this->fields['id_facing'] = $facing['id'];
        }
        
        //статус объекта
        if(!empty($values['viewtype'])) $this->getStatus($values['viewtype']);
        else{
            $this->fields['status'] = 2;    
            $this->fields['status_date_end'] = '0000-00-00';
        }

        //элитный
        if(!empty($values['elite']) && $values['elite']==1 && !empty($agency['elite_objects']) && $counter[$this->estate_type.$this->fields['rent_prefix'].'_elite']<$agency['elite_objects']) {
            $counter[$this->estate_type.$this->fields['rent_prefix'].'_elite']++;
            $this->fields['elite'] = 1;
        } else  $this->fields['elite'] = 2;

        //обрезка всех ненужных тегов в примечании
        if(!empty($this->fields['notes'])) {
            $this->fields['notes'] = strip_tags($this->fields['notes'],"<div><p><a><span><b><strong><u><i><em>");
            $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
        }
        
        //Принадлежность к комплексам
        if($this->estate_type=='build')        {
            
            if(!empty($values['build_complex_title']))  
                $this->fields['id_housing_estate']  = $this->getComplexId(1,false,$values['build_complex_title'],$this->fields['id_region'],$this->fields['id_area'],$this->fields['id_street']);
            elseif(!empty($values['build_complex_id'])) 
                $this->fields['id_housing_estate']  = $this->getComplexId(1,$values['build_complex_id'],false,$this->fields['id_region'],$this->fields['id_area'],$this->fields['id_street']);                
            $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$this->fields['id_housing_estate']);
            if(!empty($complex_info)){
                $this->fields['lat'] = $complex_info['lat'];
                $this->fields['lng'] = $complex_info['lng'];
            }
        } elseif($this->estate_type=='commercial')        {
            if(!empty($values['business_center_id'])) $this->fields['id_business_center']  = $this->getComplexId(3,$values['business_center_id'],false);
            elseif(!empty($values['business_center_title']))  $this->fields['id_business_center']  = $this->getComplexId(3,false,$values['business_center_title']);
            $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['business_centers']." WHERE id = ?",$this->fields['id_business_center']);
            if(!empty($complex_info)){
                $this->fields['lat'] = $complex_info['lat'];
                $this->fields['lng'] = $complex_info['lng'];
            }
        } elseif($this->estate_type=='country')        {
            if(!empty($values['cottage_id'])) $this->fields['id_cottage']  = $this->getComplexId(2,$values['cottage_id'],false);
            elseif(!empty($values['cottage_title']))  $this->fields['id_cottage']  = $this->getComplexId(2,false,$values['cottage_title']);
        }

        //изображения объекта
        if(!empty($values['image'])){
            if(is_array($values['image'])){
                foreach($values['image'] as $key=>$img){
                    if(!empty($img['link']) && strlen($img['link'])>10 && $this->checkPhoto( $img['link'] ) ) {
                        $this->fields['images'][] = $img['link'];
                        if(!empty($img['titul']) && $img['titul']==1) $this->fields['main_photo'] = $img['link'];
                    }
                }
            } elseif(is_string($values['image']) && strlen($values['image'])>10 && $this->checkPhoto($values['image']) ) $this->fields['images'][] = $values['image'];
            else if( $this->checkPhoto($values['image']['image']) )  $this->fields['images'][] = $values['image']['image'];
        } else if(!empty($values['image_attr']['link']) && $this->checkPhoto($values['image_attr']['link']) ) $this->fields['images'][] = $values['image_attr']['link'];
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        //координаты
        if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) list($this->fields['lat'], $this->fields['lng']) = $this->getCoords( $this->fields );
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 

        
        return $this->fields;        
    }
}
/**
* Обработка полей из bn.txt
*/
class BNTxtRobot extends Robot{
    public $file_format = 'bntxt';
    public $mapping = array(
                             'all_flats_spb'   => array('', 'rooms_sale',     'district',               'txt_addr',   'level', 'square_full', 'square_rooms', 'square_kitchen', 'subway', 'phone', 'building_type', 'toilet', '', 'seller_phone', 'cost', 'cost_type', '', 'notes','status')
                            ,'all_rooms_spb'   => array('', '',               'district', 'rooms_sale', 'txt_addr',   'level', 'square_rooms','square_kitchen','flat_params', 'subway', 'phone', 'building_type', 'toilet', '', 'seller_phone', 'cost', 'cost_type', '', 'notes','status')
                            ,'all_flats_lenobl'=> array('', 'rooms_sale', '', 'area',                   'txt_addr',   'level', 'square_full', 'square_rooms', 'square_kitchen', 'subway', 'phone', 'building_type', 'toilet', '', 'seller_phone', 'cost', 'cost_type', '', 'notes','status')
                            ,'all_rooms_lenobl'=> array('', '',           '', 'area',     'rooms_sale', 'txt_addr',   'level', 'square_rooms','square_kitchen','flat_params', 'subway', 'phone', 'building_type', 'toilet', '', 'seller_phone', 'cost', 'cost_type', '', 'notes','status')
                            ,'ned'             => array('district', 'rooms_sale',  'txt_addr', 'level', 'square_full',         'square_rooms','square_kitchen','subway', 'building_type', 'toilet', '', 'seller_phone', 'cost', 'cost_type', 'installment', 'build_complete','notes','status')
                            ,'ard'             => array('', 'estate_type', 'type_object', 'district_area', 'txt_addr', 'level', 'square_full', 'square_rooms','square_kitchen','phone', 'furniture', 'refrigerator', 'wash_mash', 'subway', 'cost', 'cost_type','','','seller_phone','notes','status')
                            ,'zd_uch'          => array('', 'area', 'txt_addr', 'square_ground', 'ownerships', 'notes', 'cost', 'cost_type', '', 'seller_phone','status')
                            ,'zd_zd'           => array('', 'area', 'type_object', 'txt_addr', 'ownerships', 'square_ground', 'square_full', 'level_total', 'construct_materials', 'heating', 'electricity', 'water_supply', 'notes', 'cost', 'cost_type', '', 'seller_phone','status')
                            ,'kn_lands'  => array('type_object', 'district','txt_addr','square_ground','','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_sell_buildings'=> array('type_object', 'district','txt_addr','square_full','level_total','facing','service_line','square_ground','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_rent_buildings'=> array('type_object', 'district','txt_addr','square_full','level_total','facing','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_sell_warehouses'=> array('type_object', 'district','txt_addr','square_full','transport_entrance','ceiling_height','facing','electricity','','heating','canalization','square_ground','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_rent_warehouses'=> array('type_object', 'district','txt_addr','square_full','transport_entrance','ceiling_height','facing','electricity','','heating','canalization','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_sell_universal'=> array('type_object', 'district','txt_addr','square_full','level','transport_entrance','facing','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_rent_universal'=> array('type_object', 'district','txt_addr','square_full','level_total','transport_entrance','facing','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_sell_trade'=> array('type_object', 'district','txt_addr','square_full','level','transport_entrance','facing','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_rent_trade'=> array('type_object', 'district','txt_addr','square_full','level_total','transport_entrance','facing','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_sell_offices'=> array('type_object', 'district','txt_addr','square_full','level','transport_entrance','facing','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_rent_offices'=> array('type_object', 'district','txt_addr','square_full','level_total','transport_entrance','facing','phones_count','parking','security','notes','cost','cost_type','','seller_phone','status')
                            ,'kn_new_buildings'=> array('type_object', 'district','txt_addr','square_full','level','transport_entrance','facing','notes','','cost','cost_type','','seller_phone','status')
    );
    /**
    * обработка полученных из bn.xml значений
    * @param array $values - значения полей
    * @param string $file_type - тип файла
    * @return array of arrays
    */
    public function getConvertedFields($values, $file_type, $estate_type, $agency,$parsing=false){    
        $this->estate_type = $estate_type;
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        //запоминание строки для логирования
        $values_to_log = $values; 
        //получение массива из строки
        if (!$parsing)
            $values = explode(";",$values);
        //определение типа недвижимости по типу файла
        $format = array($file_type);
        switch($file_type){
            case 'all': //продажа вторички в СПБ и ЛО
                if(preg_match("#ккв#is",$values[1])) {
                    $format[] = "flats";
                    $this->fields['id_type_object'] = 1;
                }
                else {
                    $format[] = "rooms"; 
                    $this->fields['id_type_object'] = 2;
                }
                if(preg_match("#ласть#is",$values[2])) {
                    $format[] = "lenobl";
                    $this->fields['id_region']=47;
                }
                else {
                    $format[] = "spb";    
                    $this->fields['id_region']=78;
                    $this->fields['id_area']=0;
                } 
                //тип сделки
                $this->fields['rent'] = 2;
                //тип недвижимости
                $this->estate_type = 'live';
                break;
            case 'ard': //аренда вторички в СПБ и ЛО
                if($values[0]=='сниму') {
                    $errors_log['moderation'][$values_to_log] = 'Не принимаем объявления в виде "сниму", только "сдам":';
                    return false; //не принимаени объявления о съеме
                }
                //тип сделки
                $this->fields['rent'] = 1;
                break;
            case 'kn':
                switch($values[0]){
                    case 'ку': //продажа земельных участков
                        $format[] = "lands"; 
                        $square_ground_multiplicator = 100; //перевод Га в сот
                        $this->fields['rent'] = 2; //продажа
                        break;
                    case 'КУ': //аренда земельных участков
                        $format[] = "lands"; 
                        $square_ground_multiplicator = 100; //перевод Га в сот
                        $this->fields['rent'] = 1; //аренда
                        break;
                    case 'кз': //продажа отдельно стоящих зданий
                        $format[] = "sell"; 
                        $format[] = "buildings"; 
                        $square_ground_multiplicator = 100; //перевод Га в сот
                        $this->fields['rent'] = 2; //продажа
                        break;
                    case 'КЗ': //аренда земельных участков
                        $format[] = "rent"; 
                        $format[] = "buildings"; 
                        $square_ground_multiplicator = 100; //перевод Га в сот
                        $this->fields['rent'] = 1; //аренда
                        break;
                    case 'кс': //продажа отдельно стоящих зданий
                        $format[] = "sell"; 
                        $format[] = "warehouses"; 
                        $square_ground_multiplicator = 100; //перевод Га в сот
                        $this->fields['rent'] = 2; //продажа
                        break;
                    case 'КС': //аренда земельных участков
                        $format[] = "rent"; 
                        $format[] = "warehouses"; 
                        $this->fields['rent'] = 1; //аренда
                        break;
                    case 'кр': //продажа помещений различного назначения
                        $format[] = "sell"; 
                        $format[] = "universal"; 
                        $this->fields['rent'] = 2; //продажа
                        break;
                    case 'КР': //аренда помещений различного назначения
                        $format[] = "rent"; 
                        $format[] = "universal"; 
                        $this->fields['rent'] = 1; //аренда
                        break;
                    case 'км': //продажа помещений для сферы услуг
                        $format[] = "sell"; 
                        $format[] = "trade"; 
                        $this->fields['rent'] = 2; //продажа
                        break;
                    case 'КМ': //аренда помещений для сферы услуг
                        $format[] = "rent"; 
                        $format[] = "trade"; 
                        $this->fields['rent'] = 1; //аренда
                        break;
                    case 'ко': //продажа помещений для сферы услуг
                        $format[] = "sell"; 
                        $format[] = "offices"; 
                        $this->fields['rent'] = 2; //продажа
                        break;
                    case 'КО': //аренда помещений для сферы услуг
                        $format[] = "rent"; 
                        $format[] = "offices"; 
                        $this->fields['rent'] = 1; //аренда
                        break;
                    case 'кн': //продажа помещений для сферы услуг
                        $format[] = "new_buildings"; 
                        $this->fields['rent'] = 2; //продажа
                        break;
                    default:
                        $errors_log['moderation'][$values_to_log] = 'Тип объекта не распознан';
                        return false;
                        break;
                        
                }
                $this->estate_type = 'commercial';
                break;
            case 'ned': //продажа первички
                if(preg_match("#ласть#is",$values[2])) $this->fields['id_region']=47;
                else $this->fields['id_region']=78;
                $this->fields['id_area']=0; 
                $this->estate_type = 'build';
                break;
            case 'zd': //продажа загородной недвижимости
                if($values[0]=='уч') {
                    $this->fields['type_object'] = 'участок';
                    $format[] = "uch"; 
                } else $format[] = "zd"; 
                $this->estate_type = 'country';
                //тип сделки
                $this->fields['rent'] = 2;
                $this->fields['id_region'] = 47;
                break;
        }    
        
        //получение полей для обработки
        $key = 0;
        if (!$parsing){
            foreach ($this->mapping[(implode("_",$format))] as $column){
                if($column!='' && !empty($values[$key])) {
                    $this->fields[$column] = $values[$key];
                }
                ++$key;
            } 
        }

        //соседи/комнат
        if(!empty($this->fields['flat_params'])){
            $flat_params = explode("/",$this->fields['flat_params']);
            if(!empty($flat_params[0])) $this->fields['rooms_total'] = $flat_params[0]; //кол-во комнат всего
            if(!empty($flat_params[1]) || !empty($flat_params[2])) $this->fields['neighbors'] = !empty($flat_params[2])?$flat_params[2]:$flat_params[1]; //соседей
        }
        //комнатность
        if(!empty($this->fields['rooms_sale']))  {
            $this->fields['rooms_sale'] = Convert::ToInteger($this->fields['rooms_sale']);
            //комнатность указана неверно
            if( $this->estate_type == 'live' && $this->fields['id_type_object']==2 && 
                    (
                        empty($this->fields['rooms_sale']) || 
                        $this->fields['rooms_sale'] > $this->fields['rooms_total'] || 
                        ( $this->fields['rooms_sale'] == $this->fields['rooms_total'] && $this->fields['rooms_total'] == 1 ) 
                    )
            )
            {
                $errors_log['rooms'][$values['num_rec']] = 'комнат: ' . ( !empty($this->fields['rooms_sale']) ? $this->fields['rooms_sale'] : 0) . ', комнатность: ' . ( !empty($this->fields['rooms_total']) ? $this->fields['rooms_total'] : 0);
                return false;
            }
            //для жилой при пустом значении "всего комнат" проставляем "комнат на сдачу"
            if($this->estate_type == 'live' && empty($this->fields['rooms_total'])) $this->fields['rooms_total'] = $this->fields['rooms_sale'];
        }

        //для аренды (определение типа объекта, типа недвижимости, региона, района области или города)
        if($file_type=='ard'){
            //определяем тип недвижимости
            if(!empty($this->fields['estate_type'])){
                if($this->fields['estate_type'] !='З') {
                    $this->estate_type = 'live';
                    $this->fields['id_region'] = 78;
                    $this->fields['id_area'] = 0;
                    $this->fields['district'] = $this->fields['district_area'];
                } else {
                    $this->estate_type = 'country';
                    $this->fields['id_region'] = 47;
                    $this->fields['area'] = $this->fields['district_area'];
                }
            } 
            
            //тип объекта и комнатность
            if(!empty($this->fields['type_object']))  {
                if(preg_match("#(\d+)ккв#is",$this->fields['type_object'],$matches)) {//квартира
                    $this->fields['id_type_object'] = 1;
                    $this->fields['rooms_sale'] = $this->fields['rooms_total'] = $matches[1];
                }
                elseif(preg_match("#(\d+)к\/(\d+)#is",$this->fields['type_object'],$matches)) {//комнат в ккв
                    $this->fields['id_type_object'] = 2;
                    $this->fields['rooms_sale'] = $matches[1];
                    $this->fields['rooms_total'] = $matches[2];
                }
                elseif(preg_match("#(\d+)комн#is",$this->fields['type_object'],$matches)) {//комнат
                    $this->fields['id_type_object'] = 2;
                    $this->fields['rooms_sale'] = $this->fields['rooms_total'] = $matches[0];
                }
            } else {
                $errors_log['moderation'][$values_to_log] = 'Не указан тип объекта';
                return false;
            }
                
        }
            //комнатность указана неверно
            if( $this->estate_type == 'live' && $this->fields['id_type_object']==2 && 
                    (
                        empty($this->fields['rooms_sale']) || 
                        $this->fields['rooms_sale'] > $this->fields['rooms_total'] || 
                        ( $this->fields['rooms_sale'] == $this->fields['rooms_total'] && $this->fields['rooms_total'] == 1 ) 
                    )
            )
            {
                $errors_log['rooms'][$values['num_rec']] = 'комнат: ' . ( !empty($this->fields['rooms_sale']) ? $this->fields['rooms_sale'] : 0) . ', комнатность: ' . ( !empty($this->fields['rooms_total']) ? $this->fields['rooms_total'] : 0);
                return false;
            }
        
        
        $this->fields['id_area'] = $this->fields['id_street'] = $this->fields['id_city'] = $this->fields['id_place'] = 0;

        //тип объекта
        if(!empty($this->fields['type_object']) && empty($this->fields['id_type_object']))  {
            //для загородки ищем по title
            if($file_type=='zd') {
                $object_type = $this->getInfoFromTable($this->sys_tables['type_objects_'.$this->estate_type],$this->fields['type_object'],'title');                
                // 1/2 1/3 1/4 - склеиваем в часть дома
                if(!empty($object_type) && $object_type['id']>=4 && $object_type['id']<=6) $object_type['id'] = 18;
            }
            //для остальных - по соотв-му полю
            else $object_type = $this->getInfoFromTable($this->sys_tables['type_objects_'.$this->estate_type],$this->fields['type_object'],false,false,'id');
            if(!empty($object_type)) $this->fields['id_type_object'] = $object_type;
            else{
                $errors_log['moderation'][$values_to_log] = 'Неверно указан тип объекта';
                return false;
            }
        }
        //преобразование адреса для коммерческой недвижимости
        if($file_type=='kn'){
            //преобразование адреса
            if(preg_match("#ласть#is",$this->fields['txt_addr'])) {
                $this->fields['id_region']=47;
                $this->fields['txt_addr'] = str_replace("р-н","",$this->fields['txt_addr']);
                $areas = explode(",",$this->fields['txt_addr']);
                if(!empty($areas[0])) {
                    $this->fields['area'] = $areas[0];
                    array_shift($areas);
                    $this->fields['txt_addr'] = implode(",",$areas);
                }
                $this->fields['district']=false;
            }
            else {
                $this->fields['id_region']=78;
                $this->fields['id_area']=0;
            } 
            
        }
        
        //район города
        if(!empty($this->fields['district'])){
            $this->fields['id_district'] = $this->getInfoFromTable($this->sys_tables['districts'],$this->fields['district'],'title',false,'id');    
            //$this->fields['id_district'] = $district['id'];
        }
        elseif(!empty($this->fields['area'])){ //район области
                $area = $db->fetch("SELECT `id_area` FROM ".$this->sys_tables['geodata']." WHERE a_level=2 AND id_region=47 AND offname=?",$this->fields['area']);
                $this->fields['id_area'] = $area['id_area'];
        }

        
        //улица (получение так же area, place) 
        if(!empty($this->fields['txt_addr'])) {
            $this->getAddress($this->fields['txt_addr'],"","","",$parsing);
        } else $this->fields['txt_addr']='';
        //группировка объектов по адресу
        $this->groupByAddress($this->estate_type, $this->fields, true);

        
        //этаж/этажность    
        if(!empty($this->fields['level'])){
            $level = explode("/",$this->fields['level']);
            if(!empty($level[0])) $this->fields['level'] = $level[0];//этаж
            if(!empty($level[1])) $this->fields['level_total'] = $level[1];//этажей
        }
        
        //площади
        //преобразование Га в сот
        if(!empty($square_ground_multiplicator) && !empty($this->fields['square_ground']))  $this->fields['square_ground'] = $this->fields['square_ground']*$square_ground_multiplicator;
        //жилая
        if(!empty($this->fields['square_rooms'])){
            preg_match_all("#([0-9\.\,]{1,5})#uis",$this->fields['square_rooms'],$square_live);
            if( empty( $square_live[0])) $this->fields['square_live'] = $this->fields['square_rooms'];
            elseif(count($square_live[0])==1) $this->fields['square_live'] = $square_live[0][0];
            else $this->fields['square_live'] = array_sum($square_live[0]);
        }
        //метро
        if(!empty($this->fields['subway'])) {
            //получение текстового значения метро
            preg_match_all("# ([\d+]{0,2}) ([ост|пеш|тр]{1,3})?#uis",$this->fields['subway'],$matches);
            if(!empty($matches[1][0])) {
                $this->fields['way_time'] = $matches[1][0];
                $this->fields['subway'] = str_replace(" ".$this->fields['way_time'],"",$this->fields['subway']);
            }
            if(!empty($matches[2][0])) {
                $this->fields['subway'] = str_replace(" ".$matches[2][0],"",$this->fields['subway']);
                $this->fields['id_way_type'] = $this->getInfoFromTable($this->sys_tables['way_types'],$matches[2][0],false,false,'id');
                //$this->fields['id_way_type'] = $way_type['id'];
            }            
            $this->fields['subway'] = str_replace(array(',','.'),'',$this->fields['subway']);
            $this->fields['id_subway'] = $this->getInfoFromTable($this->sys_tables['subways'],trim($this->fields['subway']),false,false,'id');
            //$this->fields['id_subway'] = $subway['id'];
        }  else $this->fields['id_subway']=0; 
       
        
        //тип дома
        if(!empty($this->fields['building_type'])) {
            $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],$this->fields['building_type'],false,false,'id');
            //$this->fields['id_building_type'] = $building_type['id'];
        }  else $this->fields['id_building_type'] = 0;
        
        //тип санузла
        if(!empty($this->fields['toilet'])) {
            $this->fields['id_toilet'] = $this->getInfoFromTable($this->sys_tables['toilets'],$this->fields['toilet'],false,false,'id');
            //$this->fields['id_toilet'] = $toilet['id'];
        } else $this->fields['id_toilet'] = 0;
        
        //стоимости
        if(!empty($this->fields['cost_type'])) {
            //преобразование типа от дураков
            $this->fields['cost_type'] = str_replace(array('руб/мес'),array('р/м'),$this->fields['cost_type']);
            $this->convertCost($this->fields['cost_type']);
        }
        //телефон
        if(!empty($this->fields['phone'])) $this->fields['phone'] = $this->fields['phone']=='+'?1:2;
        //мебель
        if(!empty($this->fields['furniture'])) $this->fields['furniture'] = $this->fields['furniture']=='+'?1:2;
        //холодильник
        if(!empty($this->fields['refrigerator'])) $this->fields['refrigerator'] = $this->fields['refrigerator']=='+'?1:2;
        //стиральная машина
        if(!empty($this->fields['wash_mash'])) $this->fields['wash_mash'] = $this->fields['wash_mash']=='+'?1:2;
        
        //рассрочка
        if(!empty($this->fields['installment']) && $this->fields['installment']=='Р') $this->fields['installment'] = 1;

        //срок сдачи для стройки
        if($this->estate_type=='build' && !empty($this->fields['build_complete'])) {
            if($this->fields['build_complete']=='сдан') {
                $this->fields['build_completed'] = 1;
                $this->fields['id_build_complete'] = 4;
            } elseif($this->fields['build_complete']=='госком.') $this->fields['id_build_complete'] = 5; 
            else {
                $deadline = explode('кв', str_replace(array('.','г'),' ',$this->fields['build_complete']));
                if(!empty($deadline[0])&&!empty($deadline[1])) {
                    $decade = trim(Convert::ToString($deadline[0]));
                    $year = Convert::ToInteger(preg_replace('/[^0-9]/sui','',$deadline[1]));
                    if($year<2000) $year = $year+2000;
                    $decades = array('I'=>1,'II'=>2,'III'=>'3','IV'=>4, '1'=>1,'2'=>2,'3'=>'3','4'=>4);
                    if(!empty($decades[$decade])){
                        $deadline_res = $db->fetch("SELECT `id` FROM ".$this->sys_tables['build_complete']." WHERE `year`=? AND `decade`=?",$year,$decades[$decade]);
                        if(!empty($deadline_res)) $this->fields['id_build_complete'] =  $deadline_res['id'];
                    } else $this->fields['id_build_complete']=0;
                } else $this->fields['id_build_complete']=0;
            }
        }
        
        //поля для загородки
        if($file_type=='zd'){
            //статус участка        
            if(!empty($this->fields['ownerships'])){
                $this->fields['id_ownership'] = $this->getInfoFromTable($this->sys_tables['ownerships'],$this->fields['ownerships'],false,false,'id');
                //$this->fields['id_ownership'] = $ownerships['id'];
            }
            //материал постройки     
            if(!empty($this->fields['construct_materials'])){
                $this->fields['id_construct_material'] = $this->getInfoFromTable($this->sys_tables['construct_materials'],$this->fields['construct_materials'],false,false,'id');
                //$this->fields['id_construct_material'] = $construct_materials['id'];
            }
            //отопление
            if(!empty($this->fields['heating'])) $this->fields['id_heating'] = $this->fields['heating']=='+'?2:3;
            //электричество
            if(!empty($this->fields['electricity'])) $this->fields['id_electricity'] = $this->fields['electricity']=='+'?2:3;
            //водоснабжение
            if(!empty($this->fields['water_supply'])) $this->fields['id_water_supply'] = $this->fields['water_supply']=='+'?2:3;
            
            //поиск района, если он неверно опознан
            if( empty( $this->fields['id_area'])){
                $area = $db->fetch("SELECT `id_area` FROM ".$this->sys_tables['geodata']." WHERE (a_level=3 OR a_level=4) AND id_region=47 AND offname=? ORDER BY a_level ASC",$this->fields['txt_addr']);
                $this->fields['id_area'] = $area['id_area'];
            }
            //переделка стоимости, если площадь за гектар
            
        }

        //поля для коммерческой
        if($file_type=='kn'){
            
            //отопление
            if(!empty($this->fields['heating'])) $this->fields['heating'] = $this->fields['heating']=='+'?1:2;
            //электричество
            if(!empty($this->fields['electricity'])) $this->fields['electricity'] = $this->fields['electricity']=='+'?1:2;
            //водоснабжение
            if(!empty($this->fields['canalization'])) $this->fields['canalization'] = $this->fields['canalization']=='+'?1:2;
            //состояние  
            if(!empty($this->fields['facing'])){
                $this->fields['id_facing'] = $this->getInfoFromTable($this->sys_tables['facings'],$this->fields['facing'],false,false,'id');
                //$this->fields['id_facing'] = $facing['id'];
            }
            
        }    
        //обрезка всех ненужных тегов в примечании
        if(!empty($this->fields['notes'])) {
            $this->fields['notes'] = strip_tags($this->fields['notes'],"<div><p><a><span><b><strong><u><i><em>");
            $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
        }
        
        //статус объекта
        if(!empty($this->fields['status']) ) {
            if($this->fields['status']=='Э' && !empty($agency['elite_objects']) && $counter[$this->estate_type.'_elite']<$agency['elite_objects']){
                $counter[$this->estate_type.'_elite']++;
                $this->fields['elite'] = 1;
            }
        }

        //источник добавления - bn.txt
        $this->fields['info_source'] = 5;
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                //добавление адреса в таблицу адресов с коорлинатами 
                if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 

        return $this->fields;
    }
}
/**
* Обработка полей из eip.xml
*/
class ExcelRobot extends Robot{
    public $file_format = 'excel';

    public $mapping = [];
    /**
    * обработка полученных из bn.xml значений
    * @return array of arrays
    */
    public function getConvertedFields($values,$fields_types, $estate_type, $id_type_object){
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        //получение типа недвижимости и типа объекта 
        $this->estate_type = $estate_type;
        
        foreach ($fields_types as $key=>$column){
            if(!empty($column) && !empty($values[$key]) && !is_array($values[$key])){
                $this->fields[strtolower($column)] = $values[$key];
            }
            elseif(!empty($column) && preg_match('/rooms/',$column) && Validate::isDigit($values[$key+1]) && $values[$key+1]>=0) $this->fields[strtolower($column)] = $values[$key+1];
        }
        //переопределение значений некоторых полей
        
        $this->fields['addr_source'] = $values['txt_addr'];
        //география
        //город
        $this->fields['id_area'] = $this->fields['id_street'] = $this->fields['id_city'] = $this->fields['id_place'] = 0;
        $district = mb_strtolower($this->fields['district_region'],'UTF-8');
        if(preg_match("/(петербург|спб|санкт)/i",$district) || empty($district)) $this->fields['id_region'] = 78;
        else $this->fields['id_region'] = 47;        

        //район (области или города)
        if(!empty($this->fields['district'])){
            $this->fields['district'] = trim(str_replace('р-н','',$this->fields['district']));
            if($this->fields['id_region'] == 78) { //район города
                $this->fields['id_district'] = $this->getInfoFromTable($this->sys_tables['districts'],$this->fields['district'],'title',false,'id');    
                //$this->fields['id_district'] = $district['id'];
            }
            elseif($this->fields['id_region'] == 47) { //район области
                //получение id района
                $area = $db->fetch("SELECT `id_area` FROM ".$this->sys_tables['geodata']." WHERE a_level=2 AND id_region=47 AND offname=?",$this->fields['district']);
                $this->fields['id_area'] = $area['id_area'];
            }
        }
        
        //улица (получение так же id_area, id_place) 
        if(!empty($this->fields['address'])) {
            $street = $this->fields['address'];
            //$this->getAddress(false,$street);
            $this->fields['txt_addr'] = $street;
            $this->getTxtGeodata($street);
        } 
        //группировка объектов по адресу
        $this->groupByAddress($this->estate_type, $this->fields, true);
        if( empty( $this->fields['id_street'])) $this->fields['txt_addr'] = $this->fields['address'];
        //метро
        if(!empty($this->fields['subway'])) {
            $this->fields['id_subway'] = $this->getInfoFromTable($this->sys_tables['subways'],trim(preg_replace('/пр\.?|пл\.?/sui','',$this->fields['subway'])),'emls_excel_value',false,'id',true);
            //способ добраться до метро
            if(!empty($this->fields['way_time'])) {
                preg_match_all("#([ост|пеш|тр]{1,3}) ([\d+]{0,2})#uis",$this->fields['way_time'],$matches);
                if(!empty($matches[2][0])) $this->fields['way_time'] = $matches[2][0];
                if(!empty($matches[1][0])) {
                    $this->fields['id_way_type'] = $this->getInfoFromTable($this->sys_tables['way_types'],$matches[1][0],false,false,'id');
                }                  
            }
        }
        else $this->fields['id_subway']=0; 
        
        if(!empty($id_type_object)) $this->fields['id_type_object'] = $id_type_object;
        else {
            if($this->estate_type){
                if($this->estate_type!='build'){
                    $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_'.$this->estate_type],$this->fields['type_object'],'excel_value',true,'id');      
                }
            }
        } 
        if( empty( $this->fields['id_type_object']) && $this->estate_type!='build') {
            $errors_log['moderation'][$this->fields] = 'Неверно указан тип объекта';
            return false;
        }        
        //комнатность  для квартир и комнат в жилой
        if($this->estate_type=='live' && empty($this->fields['rooms_total']) && Validate::isDigit($this->fields['rooms_sale']) && ($this->fields['id_type_object']==1 || $this->fields['id_type_object']==2) ) {
            $this->fields['rooms_total'] = $this->fields['rooms_sale'];
        }
        //цена
        if(!empty($this->fields['cost'])){
            if(strstr($this->fields['cost'],'пр')!='' || strstr($this->fields['cost'],'ар')!='') {
                if(strstr($this->fields['cost'],'пр')!='') $this->fields['rent'] = 2;
                else $this->fields['rent'] = 1;
                $this->fields['cost'] = trim(str_replace(array('ар.-','пр.-'),'',$this->fields['cost']));
                preg_match_all("#([0-9]{1,8}) ?([а-я\.\-\/\s]{1,})#uis",$this->fields['cost'],$matches);
                if(!empty($matches[2][0])) {
                    $this->fields['cost'] = $matches[1][0];
                    $this->convertCost($matches[2][0]);
                }
            } else {
                $this->fields['cost'] = $this->fields['cost']*1000;
                $this->fields['rent'] = 2;
            }
        }
        
        //юр.тип
        if(!empty($this->fields['ownerships'])) { //район города
            $this->fields['id_ownership'] = $this->getInfoFromTable($this->sys_tables['ownerships'],str_replace(array(" ",".",",","/"),"",$this->fields['ownerships']),false,false,'id');    
        }
        
        //источник добавления Excel
        $this->fields['info_source'] = 7; 
  
        //тип дома. в таблице нету excel_value, поэтому берем LIKE title
        if(!empty($this->fields['building_type'])) {
            //$building_type = $this->getInfoFromTable($this->sys_tables['building_types'],$this->fields['building_type']);
            $building_type = $db->fetch("SELECT id FROM ".$this->sys_tables['building_types']." WHERE ".$this->sys_tables['building_types'].".title LIKE '".$this->fields['building_type']."%'");
            $this->fields['id_building_type'] = $building_type['id'];
        }
  
        //тип дома. в таблице нету excel_value, поэтому берем title LIKE 
        if(!empty($this->fields['toilet'])) {
            //$toilet = $this->getInfoFromTable($this->sys_tables['toilets'],$this->fields['toilet']);
            $toilet = $db->fetch("SELECT id FROM ".$this->sys_tables['toilets']." WHERE ".$this->sys_tables['toilets'].".title LIKE '".$this->fields['toilet']."'");
            $this->fields['id_toilet'] = $toilet['id'];
        }
        
        //обрезка всех ненужных тегов в примечании
        if(!empty($this->fields['notes'])) {
            $this->fields['notes'] = strip_tags($this->fields['notes'],"<div><p><a><span><b><strong><u><i><em>");
            $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
        }
        
        //чтобы сохранить форматирование в пояснениях
        //разбиваем по блокам
        $this->fields['notes'] = "<p>".preg_replace('/\n/sui','</p><p>',$this->fields['notes'])."</p>";
        //делаем отступы для пунктов перечисления
        $this->fields['notes'] = preg_replace('/<p>(?=[0-9]+\.\s)/sui','<p class="padded">',$this->fields['notes']);
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                //добавление адреса в таблицу адресов с коорлинатами 
                if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 
        
        return $this->fields;        
    }
} 

class CustomExcelRobot extends Robot{
    public $file_format = 'excel';

    public $mapping = [];
    /**
    * обработка полученных из xlsx в свободной форме
    * @return array of arrays
    */
    public function getConvertedFields($values,$fields_types, $estate_type, $id_type_object){
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        //получение типа недвижимости и типа объекта 
        $this->estate_type = $estate_type;
        
        //источник добавления Excel
        $this->fields['info_source'] = 7; 
        $this->fields['addr_source'] = ""; 
        
        foreach ($fields_types as $key=>$column){
            if(!empty($column) && !empty($values[$key]) && !is_array($values[$key])){
                $this->fields[strtolower($column)] = $values[$key];
            }
            elseif(!empty($column) && preg_match('/rooms/',$column) && Validate::isDigit($values[$key+1]) && $values[$key+1]>=0) $this->fields[strtolower($column)] = $values[$key+1];
        }
        //переопределение значений некоторых полей
        
        //получаем адрес
        $address = $this->fields['outer_address'].", ".$this->fields['inner_address'];
        unset($this->fields['outer_address']);
        unset($this->fields['inner_address']);
        $this->getTxtGeodata($address);
        //группировка объектов по адресу
        $this->groupByAddress($this->estate_type, $this->fields, true);
        
        //корректируем площади
        if(!empty($this->fields['square_full'])) $this->fields['square_full'] = str_replace(',','.',$this->fields['square_full']);
        if(!empty($this->fields['square_live'])) $this->fields['square_live'] = str_replace(',','.',$this->fields['square_live']);
        if(!empty($this->fields['square_kitchen'])) $this->fields['square_kitchen'] = str_replace(',','.',$this->fields['square_kitchen']);
        
        //срок сдачи
        if(!empty($this->fields['id_build_complete']))
            $this->fields['id_build_complete'] = $this->getInfoFromTable($this->sys_tables['build_complete'],$this->fields['id_build_complete'],'title',false,'id');
        
        //этажность
        if(!empty($this->fields['level'])){
            list($this->fields['level'],$this->fields['level_total']) = explode('/',$this->fields['level']);
        }
        
        //комнатность для квартир и комнат в жилой. если не жилая - ничего не делаем
        if($this->estate_type=='live'){
            $this->fields['rooms_total'] = $this->fields['rooms_sale'];
            unset($this->fields['rooms_sale']);
        }
          
        //прикрепляем к ЖК:
        if(!empty($this->fields['id_housing_estate'])){
            $housing_estate_data = $this->fields['id_housing_estate'];
            //ЖК Южная поляна, дом №4
            preg_match('/[^ЖК][А-яA-z\s]+/sui',$housing_estate_data,$housing_estate_title);
            if(!empty($housing_estate_title) && !empty($housing_estate_title[0])) $housing_estate_title = trim($housing_estate_title[0]);
            if(Validate::isDigit($housing_estate_data)) $this->fields['id_housing_estate']  = $this->getComplexId(1,$housing_estate_title,false);
            elseif(!empty($housing_estate_data))  $this->fields['id_housing_estate']  = $this->getComplexId(1,false,$housing_estate_title);
            if(!empty($this->fields['id_housing_estate'])){
                $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$this->fields['id_housing_estate']);
                if(!empty($complex_info)){
                    $this->fields['lat'] = $complex_info['lat'];
                    $this->fields['lng'] = $complex_info['lng'];
                }
            }
        }

        if(!empty($this->fields['image'])){
            //$this->fields['image'] = ROOT_PATH."/img/uploads/mail_objects_images/".$this->fields['id_user']."_images/".$this->fields['image'];
            $count_photos_limit = 0;
            if(!is_array($this->fields['image'])) $this->fields['image'] = array($this->fields['image']);
            foreach($this->fields['image'] as $key=>$img){
                if( empty( $photos_limit) || $photos_limit>$count_photos_limit && !empty($img) && strlen($img)>10 && $this->checkPhoto($img) ){
                    //убираем ?... справа от расширения. оно не дает сохранить файл
                    $this->fields['images'][]=preg_replace("/\?.*/",'',$img);
                    ++$count_photos_limit;
                }
            }
        }
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                //добавление адреса в таблицу адресов с коорлинатами 
                if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 

        
        return $this->fields;        
    }
}
/**
* Обработка полей из gdeetotdom.xml
*/
class GdeetotXmlRobot extends Robot{
    public $file_format = 'eipxml';

    public $mapping = array(
            'xml'           => array('LOTNUM',     '',          'OPER_ROON_QTY','ROOM_QTY',     'METRO',        'TYPE_OBJECT',      'TYPE_OPER', 'COST', 'BLDKIND',         'metres',       'area',         'landarea',     'live',         'kitch',            'tip',              'floor',    'n_floor',      'phone',        'name',         't',    'l',            'b',        'water',        'vanna',        'note',     'dt_fin',               'build_complex_id',     'sosed',        'cond',         'podezd',               'potolok',          'kanaliz',      'communic',     'parking',  'ohrana',   'mebel',        'holod',        'stirka',       'latitude', 'longitude',    'heating_supply',   'water_supply',     'electricity_supply',   'gas_supply', 'contractor', 'asignment')
            ,'live'         => array('external_id', 'txt_addr', 'rooms_sale',   'rooms_total',  'id_subway',    'id_type_object',   'rent',      'cost', 'id_building_type','square_rooms', 'square_full',  '',             'square_live',  'square_kitchen',   'id_building_type', 'level',    'level_total',  'seller_phone', 'seller_name',  'phone','id_elevator',  'id_balcon','',             'id_toilet',    'notes',    '',                     '',                     'neighbors',    'id_facing',    '',                     'ceiling_height',   '',             '',             '',         '',         'furniture',    'refrigerator', 'wash_mash',    'lat',      'lng',          '',                 '',                 '',                     '',            ''   )
            ,'build'        => array('external_id', 'txt_addr', '',             'rooms_sale',   'id_subway',    '',                 'rent',      'cost', 'square_rooms', 'square_full',  '',             'square_live',  'square_kitchen',   'id_building_type', 'level',    'level_total',  'seller_phone', 'seller_name',  '',     'id_elevator',  'id_balcon','',             'id_toilet',    'notes',    'id_build_complete',    'id_housing_estate',    '',             'id_facing',    '',                     'ceiling_height',   '',             '',             '',         '',         '',             '',             '',             'lat',      'lng',          '',                 '',                 '',                     '',                              'contractor', 'asignment'          )
            ,'country'      => array('external_id', 'txt_addr', '',             'rooms',        'id_subway',    'id_type_object',   'rent',      'cost', '',             'square_full',  'square_ground','square_live',  '',                 '',                 'level',    'level_total',  'seller_phone', 'seller_name',  'phone','',             '',         '',             'id_toilet',    'notes',    '','',                  '',                     '',             '',                     '',                 '',             '',             '',         '',         '',             '',             '',             'lat',      'lng',          'id_heating',       'id_water_supply',  'id_electricity',       'id_gas',''    )
            ,'commercial'   => array('external_id', 'txt_addr', '',             '',             'id_subway',    'id_type_object',   'rent',      'cost', '',             'square_full',  '',             '',             '',                 '',                 'level',    'level_total',  'seller_phone', 'seller_name',  '',     '',             '',         'hot_water',    '',             'notes',    '', '',                 '',                     'id_facing',    'transport_entrance',   'ceiling_height',   'canalization', 'service_line', 'parking',  'security', '',             '',             '',             'lat',      'lng',          '',                 '',                 'electicity',           '' ,''         )
    );
    /**
    * функция переводит название здания, в котором расположен объект в падеж "где?"
    * @param mixed $title
    * @return string
    */
    private function gdeetotxml_bldkind_transform($title){
        $title=mb_strtolower($title);
        switch($title){
           case 'офисное здание': return "в офисном здании" ;
           case 'комплекс офисных зданий': return "в комплексе офисных зданий" ;
           case 'старинный реконструированный особняк':return "в старинном реконструированном особняке";
           case 'щсобняк':return "в особняке";
           case 'современный особняк':return "в современном особняке";
           case 'банковское помещение':return "в банковском помещении";
           case 'банковское здание':return "в банковском здании";
           case 'офисно-жилой комплекс':return "в офисно-жилом комплексе";
           case 'административное здание':return "в административном здании";
           case 'торгово-развлекательный центр':return "в торгово-развлекательном центре";
           case 'бизнес-парк':return "бизнес-парке";
           case 'офисно-складской комплекс':return "в офисно-складском комплексе";
           case 'многофункциональный комплекс':return "в многофункциональном комплексе";
           case 'торгово-офисный комплекс':return "в торгово-офисном комплексе";
           case 'дискаунт центр':return "в дискаунт центре";
           case 'торговый центр':return "в торговом центре";
           case 'развлекательный центр':return "в развлекательном центре";
           case 'специализированный торговый центр':return "в специализированном торговом центре";
           case 'пауэр-центр':return "в пауэр-центре";
           case 'ритейл-парк':return "в ритейл-парке";
           case 'фестивал-центр':return "в фестивал-центре";
           case 'универмаг':return "в универмаге";
           case 'торговый центр моды':return "в торговом центре моды";
           case 'торгово-офисный центр':return "в торгово-офисном центре";
           case 'торгово-общественный центр':return "в торгово-общественном центре";
           case 'street retail':return "в комплексе Street retail";
           case 'производственно-складской комплекс':return "в производственно-складском комплексе";
           case 'складской комплекс':return "в складском комплексе";
           case 'логистический центр':return "в логистическом центре";
           case 'производственный комплекс':return "в производственном комплексе";
           case 'действующее производство':return "в здании с действующим производством";
           case 'завод':return "на заводе";
           case 'производственный цех':return "в производственном цехе";
           case 'промплощадка':return "в промплощадке";
           case 'имущественный комплекс':return "в имущественном комплексе";
           case 'офисно-производственно-складской комплекс':return "в Офисно-производственно-складском комплексе";
           case 'офисно-производственный комплекс':return "в офисно-производственном комплексе";
           case 'ресторан':return "в ресторане";
           case 'банкетный зал':return "в банкетном зале";
           case 'гостиница':return "в гостинице";
           case 'конференц-зал':return "в конференц-зале";
           case 'бизнес-центр':return "в бизнес-центре";
           default: return FALSE;
        }
    }
    public function getConvertedFields($values, $agency, $photos_limit = false,$return_deal_type = false){
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        //определяем тип недвижимости
        switch(true){
            case $values['type_realty'] == 'жилая'||$values['type_realty'] == 'новостройки':
                $this->estate_type='live';
                //источник - gdeetotdom.xml
                $this->fields['info_source'] = 7; 
                //  номер объекта
                $this->fields['external_id']=$values['ad_attr']['lotnum']?$values['ad_attr']['lotnum']:$values['ad_attr']['advnum'];
                //тип сделки (2-продажа/1-аренда)
                $this->fields['rent']=preg_match('/прода/',$values['type_oper'])?2:1;
                //цена
                if ($values['currency']=="RUR")
                    $this->fields['cost']=$values['cost'];
                    if ($values['period']!=null){
                        if (preg_match('/\w?день|\w?сут\w?/',$values['period'])) $this->fields['by_the_day']=1;
                        else $this->fields['by_the_day']=2;
                    }
                //тип объекта
                $this->fields['id_type_object']=$this->getInfoFromTable($this->sys_tables['type_objects_live'],$values['type_object'],"title",false,'id');
                //обрабатываем информацию о документах и условиях продажи (полей нет, вносим в примечание)
                $notes='';
                if ($values['is_hypothec']) $notes .= " Возможна ипотека.";
                if ($values['is_assignation']) $notes .= " Есть возможность переуступки прав при продаже.";
                if($values['sell_type']) $notes .= " Тип продажи - ".$values['sell_type'];
                //условия аренды
                if ($values['rent_attr']){
                    //сроки аренды
                    if ($values['rent_attr']['rent_term']){
                        $notes .= " Объект сдается на ".$values['rent_attr']['rent_term'];
                        switch($values['period']){
                            case 'день': $notes.=" дней.";break;
                            case 'месяц': $notes.=" месяцев.";break;
                            case 'год': $notes.=" лет.";break;
                        }
                    }
                    //залог при аренде, сумма залога
                    if ($values['rent_attr']['is_pledge']){
                        $notes .= " При аренде требуется залог";
                        $notes.=(!empty($values['rent_attr']['pledge_summ']))?" в ".$values['rent_attr']['pledge_summ']."%.":".";
                    }
                    //процент комиссии для клиента
                    if ($values['rent_attr']['commis_client']) $notes .= " ".$values['rent_attr']['commis_client']."% комиссия для клиента от стоимости аренды.";
                    //процент комиссии для риэлтора
                    if ($values['rent_attr']['commis_an']) $notes .= " ".$values['rent_attr']['commis_an']."% комиссия для риэлтора от стоимости аренды.";
                }
                ///обрабатываем adrinf
                //определяем город
                $this->fields['id_city']=$this->getInfoFromTable($this->sys_tables['cities'],$values['adrinf']['city'],"title",false,'id');
                //определяем регион
                $this->fields['id_region']=$this->getInfoFromTable($this->sys_tables['region'],$values['adrinf']['region'],false,false,'id');
                //на случай, если в разделе <REGION> указан не регион, а город(СПБ или М)
                if ( empty( $this->fields['id_region'])) $this->fields['id_city']=$this->getInfoFromTable($this->sys_tables['cities'],$values['adrinf']['region'],"title",false,'id');
                //определяем район города
                //adm1
                $values['adrinf']['adm1']=preg_replace('/район|р-он|р-н|р\./','',$values['adrinf']['adm1']);
                $this->fields['id_district']=$this->getInfoFromTable($this->sys_tables['districts'],$values['adrinf']['adm1'],"title",false,'id');
                if ($this->fields['id_district']){
                    $this->fields['id_region'] = '78';
                    $this->fields['id_area'] = 0;
                } 
                //если не нашли в районах города, значит это район области
                if ( empty( $this->fields['id_district'])){
                    $this->fields['id_area'] = $this->getInfoFromTable($this->sys_tables['district_areas'],$values['adrinf']['adm1'],"title",false,'id');
                    if ($this->fields['id_area']){
                        $this->fields['id_district'] = 0;
                        $this->fields['id_region'] = '47';
                    } 
                } 
                //улица, дом, корпус
                $house_korp = preg_split('/корп[\.]?|к[\.]?/',$values['adrinf']['num'],-1,PREG_SPLIT_NO_EMPTY);
                $this->getAddress($values['adrinf']['street'],'',preg_replace('/[^\d]/','',$house_korp[0]),preg_replace('/[^\d]/','',$house_korp[1]));
                //группировка объектов по адресу
                $this->groupByAddress($this->estate_type, $this->fields, true);
                //метро
                $this->fields['id_subway'] = $this->getInfoFromTable($this->sys_tables['subways'],$values['adrinf']['subway']['metro'],"title",false,'id');
                //путь до метро
                $this->fields['id_way_type']=$this->getInfoFromTable($this->sys_tables['way_types'],$values['adrinf']['subway']['typetransp'],"gdeetotdomxml",false,'id');
                //время до метро
                preg_match('/\d{1,2}[\s]?(минут|мин)/',$values['adrinf']['subway']['metro_time'],$matches);
                preg_match('/\d{1,2}/',$matches[0],$way_time_m);
                $way_time_m=$way_time_m[0];
                preg_match('/\d{1,2}[\s]?(часов|час|ч)/',$values['adrinf']['subway']['metro_time'],$matches);
                preg_match('/\d{1,2}/',$matches[0],$way_time_h);
                $way_time_h=$way_time_h[0];
                $this->fields['way_time']=abs($way_time_h*60-$way_time_m);
                //координаты объекта
                $this->fields['lat'] = $values['adrinf']['coord']['x'];
                $this->fields['lng'] = $values['adrinf']['coord']['y'];
                //заполняем txt_addr
                $this->fields['txt_addr'] = $this->fields['address'];
                ///обрабатываем flatinf
                //дополнительная информация
                $this->fields['notes'] = $values['flatinf']['addition'];
                $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
                //всего комнат
                $this->fields['rooms_total'] = $values['flatinf']['room_qty'];
                //количество комнат для аренды/продажи
                $this->fields['rooms_sale'] = $values['flatinf']['oper_room_qty'];
                if ($this->fields['id_type_object'] == 1) $this->fields['rooms_sale'] = $this->fields['rooms_total'];
                //общая площадь
                $this->fields['square_full'] = $values['flatinf']['fullsquare'];
                //площадь комнат
                $this->fields['square_rooms'] = $values['flatinf']['allroomsquare'];
                //планировка (поля нет, вносим в примечания)
                if ($values['flatinf']['rooms_style'])$this->fields['notes'] .= " Квартира спланирована как ".$values['flatinf']['rooms_style'].".";
                //перекрытия (поля нет, вносим в примечания)
                switch($values['flatinf']['overlap_mat']){
                    case 'ЖБ': $this->fields['notes'] .= "Железобетонные перекрытия.";break;
                    case 'дерево': $this->fields['notes'] .= "Деревянные перекрытия.";break;
                    case 'смешанные': $this->fields['notes'] .= "Смешанные перекрытия.";break;
                }
                //тип окон (поля нет, вносим в примечания)
                switch($values['flatinf']['window']){
                    case 'деревянные': $this->fields['notes'] .= " Деревянные окна.";break;
                    case 'стеклопакет': $this->fields['notes'] .= " Стеклопакеты.";break;
                }
                //жилая площадь
                $this->fields['square_live'] = $values['flatinf']['livesquare'];
                //площадь кухни
                $this->fields['square_kitchen'] = $values['flatinf']['kitchensquare'];
                //этаж
                $this->fields['level'] = $values['flatinf']['floor'];
                //санузел
                $this->fields['id_toilet'] = $this->getInfoFromTable($this->sys_tables['toilets'],$values['flatinf']['bathroom'],"title",false,'id');
                //ремонт
                $this->fields['id_facing'] = $this->getInfoFromTable($this->sys_tables['facings'],$values['flatinf']['repair'],"gdeetotdomxml",false,'id');
                //материал пола
                $this_fields['id_floor'] = $this->getInfoFromTable($this->sys_tables['floors'],$values['flatinf']['floor_mat'],"title",false,'id');
                //высота потолков
                $this_fields['ceiling_height'] = $values['flatinf']['headroom'];
                
                ///обрабатываем flatinf/auxinfo_attr
                //вид из окон
                switch(true){
                    case $values['flatinf']['view'] == 'улица': 
                        $this->fields['id_window'] =  $this->getInfoFromTable($this->sys_tables['windows'],"на улицу","title",false,'id');
                        break;
                    case $values['flatinf']['view'] == 'двор': 
                        $this->fields['id_window'] =  $this->getInfoFromTable($this->sys_tables['windows'],"во двор","title",false,'id');
                        break;
                    case ($values['flatinf']['view'] == 'двор,улица')||($values['flatinf']['view'] == 'двор, улица'): 
                        $this->fields['id_window'] = $this->getInfoFromTable($this->sys_tables['windows'],"на обе стороны","title",false,'id');
                        break;
                    default: 
                        $this->fields['id_window'] = $this->getInfoFromTable($this->sys_tables['windows'],"на три стороны","title",false,'id');
                        break;
                }
                //лифт
                switch(true){
                    case $values['flatinf']['auxinfo_attr']['elevator'] == 0:  
                        $this->fields['id_elevator'] = $this->getInfoFromTable($this->sys_tables['elevators'],"-","short_title",false,'id');
                        break;
                    case $values['flatinf']['auxinfo_attr']['elevator'] == 1:  
                        $this->fields['id_elevator'] = $this->getInfoFromTable($this->sys_tables['elevators'],"+","short_title",false,'id');
                        break;
                    case $values['flatinf']['auxinfo_attr']['elevator'] == 2:  
                        $this->fields['id_elevator'] = $this->getInfoFromTable($this->sys_tables['elevators'],"2","short_title",false,'id');
                        break;
                    case !empty($values['flatinf']['auxinfo']['elevator'])&&!empty($values['auxinfo_attr']['fr_elevator']): 
                        $this->fields['id_elevator'] = $this->getInfoFromTable($this->sys_tables['elevators'],"гп","short_title",false,'id');
                        break;
                }
                //мебель
                $this->fields['furniture'] = $values['flatinf']['auxinfo_attr']['is_furniture']?1:2;
                //телефон
                $this->fields['phone']=($values['flatinf']['auxinfo_attr']['phone']!='без телефона')?1:2;
                //балкон
                $this->fields['id_balcon'] = $values['flatinf']['auxinfo_attr']['is_balcony']?2:0;
                if ($this->fields['id_balcon']){
                    //тип балкона
                    switch($values['flatinf']['auxinfo_attr']['balcon']){
                        case 'балкон': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],",балкон","title");break;
                        case 'лоджия': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"лоджия","title");break;
                        case 'эркер': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"эркер","title");break;
                        case 'балкон и лоджия': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"балкон и лоджия","title");break;
                        case 'балкон и эркер': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"балкон и эркер","title");break;
                        case '2 балкона и более': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"2 балкона","title");break;
                        case '2 лод и более': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"2 ","title");break;
                    }
                }
                //парковка (поля нет, вносим в примечания)
                switch($values['flatinf']['auxinfo_attr']['parking']){
                    case 'гараж': $this->fields['notes'] .= ' Гараж.';break;
                    case 'машиноместо': $this->fields['notes'] .= ' Машиноместо.';break;
                    case 'охраняемая парковка': $this->fields['notes'] .= ' Охраняемая парковка.';break;
                    case 'неохраняемая парковка': $this->fields['notes'] .= ' Неохраняемая парковка.';break;
                    case 'подземная парковка': $this->fields['notes'] .= ' Подземная парковка.';break;
                    case 'стихийная парковка': $this->fields['notes'] .= ' Стихийная  парковка.';break;
                }
                //кондиционирование (поля нет, вносим в примечания)
                switch($values['flatinf']['auxinfo_attr']['aircond']){
                    case 'сплит-система': $this->fields['notes'] .= ' Установлена сплит-система.';break;
                    case 'обычная': $this->fields['notes'] .= ' Установлен кондиционер.';break;
                    case 'центральное кондиционирование': $this->fields['notes'] .= ' Центральное кондиционирование.';break;
                }
                //телевизор (поля нет, вносим в примечания)
                switch($values['flatinf']['auxinfo_attr']['tv']){
                    case 'кабельное': $this->fields['notes'] .= ' Кабельное ТВ.';break;
                    case 'спутниковое': $this->fields['notes'] .= ' Спутниковое ТВ.';break;
                    case 'iptv': $this->fields['notes'] .= ' iptv';break;
                }
                //мусоропровод (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_trashchute']) $this->fields['notes'] .= ' Мусоропровод.';
                //встроенная бытовая техника (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_conseqpm']) $this->fields['notes'] .= ' Есть встроенная бытовая техника.';
                //остекление балкона (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_glazing']) $this->fields['notes'] .= ' Балкон остеклен.';
                //интернет (поля нет, вносим в примечания)
                switch($values['flatinf']['auxinfo_attr']['inet']){
                    case 'есть': " Проведен интернет.";break;
                    case 'есть возможность проведения': " Есть возможность подключения интернета.";break;
                }
                //возможность перепланировки (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_replanning']) $this->fields['notes'] .= ' Есть возможность перепланировки.';
                //коммунальность квартиры (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_communal']) $this->fields['notes'] .= ' Квартира коммунальная.';
                ///обрабатываем buildinginf
                //тип дома
                $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],$values['buildinginf']['bldkind'],"title",false,'id');
                //год постройки (поля нет, вносим в примечания)
                if ($values['buildinginf']['bldyear']) $this->fields['notes'] = "Здание ".$values['buildinginf']['bldyear']." года постройки. ".$this->fields['notes'];
                //этажей всего
                $this->fields['level_total'] = $values['buildinginf']['floor_qty'];
                
                
                //для новостроек
                if(!empty($values['buildinginf']['new_building_attr'])){
                    $values['type_realty']='новостройки';
                    $this->estate_type='build';
                    //статус дома(сдан/не сдан)
                    if($values['buildinginf']['new_building']['obj_status'] == 'сдан')
                        $this->fields['build_completed']=1;
                    $this->fields['id_build_complete']=$this->getInfoFromTable($this->sys_tables['build_complete'],$values['buildinginf']['new_building']['DL_ENDING_CONSTR']);
                }
                
                //картинки
                if(!empty($values['files']['file_attr'])){
                    $count_photos_limit = 0;
                    foreach($values['files']['file_attr'] as $key=>$img){
                        if( ( empty($photos_limit) || $photos_limit>$count_photos_limit ) && $this->checkPhoto($img['FILEPATH']) ){
                            $this->fields['images'][] = $img['FILEPATH'];
                            ++$count_photos_limit;
                        }
                    }
                }
                $this->fields['notes'].=$notes;
                $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
                break;
            case $values['type_realty'] == 'коммерческая':
                $this->estate_type='commercial';
                //источник - gdeetotdom.xml
                $this->fields['info_source'] = 7; 
                //  номер объекта
                $this->fields['external_id']=$values['ad_attr']['lotnum']?$values['ad_attr']['lotnum']:$values['ad_attr']['advnum'];
                //тип сделки (2-продажа/1-аренда)
                $this->fields['rent']=preg_match('/прода/',$values['type_oper'])?2:1;
                //цена
                if ($values['currency']=="RUR")
                    $this->fields['cost']=$values['cost'];
                if ($values['period']!=null){
                    if (preg_match('/\w?день|\w?сут\w?/',$values['period'])) $this->fields['by_the_day']=1;
                    else $this->fields['by_the_day']=2;
                }
                //тип объекта (гараж идет вместе с производственно-складскими помещениями)
                if ($values['type_object']=='гараж') $this->fields['id_type_object'];
                else $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_commercial'],$values['type_object'],"gdeetotdomxml_value",false,'id');
                //обрабатываем информацию о документах и условиях продажи (полей нет, вносим в примечание)
                $notes='';
                if ($values['is_hypothec']) $notes .= " Возможна ипотека.";
                if ($values['is_assignation']) $notes .= " Есть возможность переуступки прав при продаже.";
                if($values['sell_type']) $notes .= " Тип продажи - ".$values['sell_type'];
                //условия аренды
                if ($values['rent_attr']){
                    //сроки аренды
                    if ($values['rent_attr']['rent_term']){
                        $notes .= " Объект сдается на ".$values['rent_attr']['rent_term'];
                        switch($values['period']){
                            case 'день': $notes.=" дней.";break;
                            case 'месяц': $notes.=" месяцев.";break;
                            case 'год': $notes.=" лет.";break;
                        }
                    }
                    //залог при аренде, сумма залога
                    if ($values['rent_attr']['is_pledge']){
                        $notes .= " При аренде требуется залог";
                        $notes.=(!empty($values['rent_attr']['pledge_summ']))?" в ".$values['rent_attr']['pledge_summ']."%.":".";
                    }
                    //процент комиссии для клиента
                    if ($values['rent_attr']['commis_client']) $notes .= " ".$values['rent_attr']['commis_client']."% комиссия для клиента от стоимости аренды.";
                    //процент комиссии для риэлтора
                    if ($values['rent_attr']['commis_an']) $notes .= " ".$values['rent_attr']['commis_an']."% комиссия для риэлтора от стоимости аренды.";
                }
                //дополнительные условия аренды
                if ($values['commercial_attr']){
                    //субаренда
                    if ($values['commercial_attr']['is_subrent']) $notes .= " Объект будет передан в субаренду.";
                    //собственнник
                    if ($values['commercial_attr']['owner']) $notes .= " Собственник объекта - ".$values['commercial_attr']['owner'].".";
                    //форма сделки при аренде объекта
                    if ($values['commercial_attr']['oper_form']&&($values['commercial_attr']['oper_form']!='иное')) $notes .= " Форма сделки при аренде объекта - ".$values['commercial_attr']['oper_form'].".";
                }
                ///обрабатываем adrinf
                //определяем город
                $this->fields['id_city']=$this->getInfoFromTable($this->sys_tables['cities'],$values['adrinf']['city'],"title",false,'id');
                //определяем регион
                $this->fields['id_region']=$this->getInfoFromTable($this->sys_tables['region'],$values['adrinf']['region'],false,false,'id');
                //на случай, если в разделе <REGION> указан не регион, а город(СПБ или М)
                if ( empty( $this->fields['id_region'])) $this->fields['id_city']=$this->getInfoFromTable($this->sys_tables['cities'],$values['adrinf']['region'],"title",false,'id');
                //определяем район города
                //adm1
                $values['adrinf']['adm1']=preg_replace('/район|р-он|р-н|р\./','',$values['adrinf']['adm1']);
                $this->fields['id_district']=$this->getInfoFromTable($this->sys_tables['districts'],$values['adrinf']['adm1'],"title",false,'id');
                if ($this->fields['id_district']){
                    $this->fields['id_region'] = '78';
                    $this->fields['id_area'] = 0;
                } 
                //если не нашли в районах города, значит это район области
                if ( empty( $this->fields['id_district'])){
                    $this->fields['id_area'] = $this->getInfoFromTable($this->sys_tables['district_areas'],$values['adrinf']['adm1'],"title",false,'id');
                    if ($this->fields['id_area']){
                        $this->fields['id_district'] = 0;
                        $this->fields['id_region'] = '47';
                    } 
                } 
                //улица, дом, корпус
                $house_korp = preg_split('/корп[\.]?|к[\.]?/',$values['adrinf']['num'],-1,PREG_SPLIT_NO_EMPTY);
                $this->getAddress($values['adrinf']['street'],'',preg_replace('/[^\d]/','',$house_korp[0]),preg_replace('/[^\d]/','',$house_korp[1]));
                //группировка объектов по адресу
                $this->groupByAddress($this->estate_type, $this->fields, true);
                //метро
                $this->fields['id_subway'] = $this->getInfoFromTable($this->sys_tables['subways'],$values['adrinf']['subway']['metro'],"title",false,'id');
                //путь до метро
                $this->fields['id_way_type']=$this->getInfoFromTable($this->sys_tables['way_types'],$values['adrinf']['subway']['typetransp'],"gdeetotdomxml",false,'id');
                //время до метро
                preg_match('/\d{1,2}[\s]?(минут|мин)/',$values['adrinf']['subway']['metro_time'],$matches);
                preg_match('/\d{1,2}/',$matches[0],$way_time_m);
                $way_time_m=$way_time_m[0];
                preg_match('/\d{1,2}[\s]?(часов|час|ч)/',$values['adrinf']['subway']['metro_time'],$matches);
                preg_match('/\d{1,2}/',$matches[0],$way_time_h);
                $way_time_h=$way_time_h[0];
                $this->fields['way_time'] = abs($way_time_h*60-$way_time_m);
                //координаты объекта
                $this->fields['lat'] = $values['adrinf']['coord']['x'];
                $this->fields['lng'] = $values['adrinf']['coord']['y'];
                //заполняем txt_addr
                $this->fields['txt_addr'] = $this->fields['address'];
                ///обрабатываем flatinf
                //общая площадь
                $this->fields['square_full'] = $values['flatinf']['fullsquare'];
                //этаж
                $this->fields['level'] = $values['flatinf']['floor'];
                //высота потолков
                $this->fields['ceiling_height'] = $values['flatinf']['headroom'];
                //ремонт
                $this->fields['id_facing'] = $this->getInfoFromTable($this->sys_tables['facings'],$values['flatinf']['repair'],"gdeetotdomxml",false,'id');
                //охрана
                $this->fields['security'] = $values['flatinf']['security']?1:2;
                //дополнительная информация
                $this->fields['notes'] = $values['flatinf']['addition'];
                $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
                //паркинг
                $this->fields['parking'] = (!empty($values['flatinf']['auxinfo_attr']['parking'])&&$values['flatinf']['auxinfo']['parking']!='стихийная парковка'&&$values['flatinf']['auxinfo_attr']['parking']!='отсутствует')?1:2;
                ///обрабатываем buildinginf
                $building_notes='';
                //тип здания, год постройки (полей нет, вносим в примечания)
                if($values['buildinginf']['bldkind']){
                    $bldkind=$this->gdeetotxml_bldkind_transform($values['buildinginf']['bldkind']);
                    $building_notes .= " Объект представляет собой ".$values['type_object']." ".$bldkind.((!empty($values['buildinginf']['bldyear']))?" ".$values['buildinginf']['bldyear']." года постройки.":".");
                }
                //этажей всего
                $this->fields['level_total'] = $values['buildinginf']['floor_qty'];
                //если это коммерческая новостройка
                if ($values['buildinginf']['new_building_attr']){
                    $this->fields['id_type_object']=27;
                    //статус объекта (стадия сдачи)
                    if ($values['buildinginf']['new_building_attr']['obj_status'])$building_notes .= $values['buildinginf']['new_building_attr']['obj_status'];
                    //год сдачи объекта
                    if ($values['buildinginf']['new_building_attr']['dl_endning_constr'])$building_notes .= " Год сдачи объекта - ".$values['buildinginf']['new_building_attr']['dl_ending_constr'];
                }
                //информация о назначении, классе и планировке  объекта
                if ($values['buildinginf']['commercial_attr']){
                    if($values['buildinginf']['commercial_attr']['obj_purpose']&&($values['buildinginf']['commercial_attr']['obj_purpose']!='иное')){
                        $building_notes .= " Объект предназначен для размещения ";
                        switch($values['buildinginf']['commercial_attr']['obj_purpose']){
                            case 'аптека':$building_notes .= "аптеки.";
                            case 'салон красоты':$building_notes .= "салона красоты.";
                            case 'ресторан':$building_notes .= "ресторана.";
                            case 'ночной клуб':$building_notes .= "ночного клуба.";
                            case 'бар':$building_notes .= "бара.";
                            case 'АЗС':$building_notes .= "АЗС.";
                            case 'мойка':$building_notes .= "мойки.";
                            case 'дом отдыха':$building_notes .= "дома отдыха.";
                            case 'гостиница':$building_notes .= "гостиницы.";
                            case 'автостоянка':$building_notes .= "автостоянки.";
                        }
                    }
                    if ($values['buildinginf']['commercial_attr']['obj_planning']&&($values['buildinginf']['commercial_attr']['obj_planning']!='иное'))
                        $building_notes .= "Планировка объекта - ".$values['buildinginf']['commercial_attr']['obj_planning'].".";
                    if ($values['buildinginf']['commercial_attr']['obj_class']) $building_notes .= " Объект принадлежит к классу ".$values['buildinginf']['commercial_attr']['obj_class'].".";
                }
                $this->fields['notes'] = $building_notes." ".$this->field['notes'];
                ///обрабатываем infrastructure
                //канализация
                if ($values['infrastructure_attr']['seweradge']){
                    if ($values['infrastructure_attr']['seweradge'] != 'есть возможность подведения'&&$values['infrastructure_attr']['seweradge'] != 'нет')
                        $this->fields['canalization'] = 1;
                    //если есть возможность подведения, указываем это в примечаниях
                    elseif($values['infrastructure_attr']['seweradge']!='нет'){
                        $this->fields['notes'] .= ' Есть возможность подведения канализации.';
                    }
                    //нет канализации
                    else $this->fields['canalization'] = 2;
                }
                //водоснабжение (если центральное - значит есть горячая вода)
                if ($values['infrastructure_attr']['water']){
                    if ($values['infrastructure_attr']['water'] != 'нет')
                        $this->fields['hot_water'] = 1;
                    //если какая-то вода есть, указываем это в примечаниях
                    elseif($values['infrastructure']['water']!='нет'){
                        $this->fields['notes'] .= $values['infrastructure_attr']['water'];
                    }
                    else $this->fields['hot_water'] = 2;
                }
                //электричество
                if ($values['infrastructure_attr']['electricity']){
                    if ($values['infrastructure_attr']['electricity'] != 'есть возможность подключения'&&$values['infrastructure_attr']['electricity'] != 'нет')
                        $this->fields['electricity'] = 1;
                    //если есть возможность подключения, указываем это в примечаниях
                    elseif($values['infrastructure_attr']['electricity']!='нет'){
                        $this->fields['notes'] .= ' Есть возможность подключения электричества.';
                    }
                    //нет электричества
                    else $this->fields['electricity'] = 2;
                }
                //отопление
                if ($values['infrastructure_attr']['heating']){
                    if ($values['infrastructure_attr']['heating'] != 'без отопления')
                        $this->fields['heating'] = 1;
                    //если какая-то вода есть, указываем это в примечаниях
                    elseif($values['infrastructure']['heating']!='без отопления'){
                        $this->fields['notes'] .= $values['infrastructure_attr']['heating'];
                    }
                    else $this->fields['heating'] = 2;
                }
                //газ (поля нет, вносим в примечания)
                if ($values['infrastructure_attr']['gas']){
                    $this->fields['notes'] .= $values['infrastructure_attr']['gas'];
                }
                //дополнительные данные по объекту (полей нет, вносим в примечание)
                if ($values['infrastructure_attr']['commercial_attr']){
                    //максимально возможное потребление электроэнергии (в кВт)
                    if($values['infrastructure']['commercial_attr']['max_energy']) 
                        $this->fields['notes'] .= " Максимально возможное потребление энергии - ".$values['infrastructure']['commercial_attr']['max_energy']." кВт.";
                    //количество подведенных телефонных линий на объекте
                    if($values['infrastructure']['commercial_attr']['phone_lines']) 
                        $this->fields['notes'] .= $values['infrastructure']['commercial_attr']['phone_lines']." телефонных линий.";
                    //возможность подключения дополнительных телефонных линий
                    if($values['infrastructure']['commercial_attr']['is_add_phone']) 
                        $this->fields['notes'] .= " Возможно подключение ".$values['infrastructure']['commercial_attr']['is_add_phone']." дополнительных телефонных линий.";
                    //перечень инфраструктуры на объекте
                    if($values['infrastructure']['commercial_attr']['infra_objects']){
                        $this->fields['notes'] .= " На объекте присутствует инфраструктура: ".$values['infrastructure']['commercial_attr']['infra_objects'].".";
                    }
                }
                //количество машиномест, входящее в стоимость объекта
                if ($values['infrastructure_attr']['parking_attr']['parking_qty']) 
                    $this->fields['notes'] .= ' В стоимость объекта входит '.$values['infrastructure_attr']['parking_attr']['parking_qty']." машиномест.";
                //картинки
                if(!empty($values['files']['file_attr'])){
                    $count_photos_limit = 0;
                    foreach($values['files']['file_attr'] as $key=>$img){
                        if( ( empty($photos_limit) || $photos_limit>$count_photos_limit ) && $this->checkPhoto($img['FILEPATH']) ){
                            $this->fields['images'][] = $img['FILEPATH'];
                            ++$count_photos_limit;
                        }
                    }
                }
                break;
            case 'загородная':$this->estate_type='country';
                $this->estate_type='country';
                //источник - gdeetotdom.xml
                $this->fields['info_source'] = 7; 
                //  номер объекта
                $this->fields['external_id']=$values['ad_attr']['lotnum']?$values['ad_attr']['lotnum']:$values['ad_attr']['advnum'];
                //тип сделки (2-продажа/1-аренда)
                $this->fields['rent']=preg_match('/прода/',$values['type_oper'])?2:1;
                //цена
                if ($values['currency']=="RUR")
                    $this->fields['cost']=$values['cost'];
                    if ($values['period']!=null){
                        if (preg_match('/\w?день|\w?сут\w?/',$values['period'])) $this->fields['by_the_day']=1;
                        else $this->fields['by_the_day']=2;
                    }
                //тип объекта
                $this->fields['id_type_object']=$this->getInfoFromTable($this->sys_tables['type_objects_country'],$values['type_object'],"title",false,'id');
                //обрабатываем информацию о документах и условиях продажи (полей нет, вносим в примечание)
                $notes='';
                if ($values['is_hypothec']) $notes .= " Возможна ипотека.";
                if ($values['is_assignation']) $notes .= " Есть возможность переуступки прав при продаже.";
                if($values['sell_type']) $notes .= " Тип продажи - ".$values['sell_type'];
                //условия аренды
                if ($values['rent_attr']){
                    //сроки аренды
                    if ($values['rent_attr']['rent_term']){
                        $notes .= " Объект сдается на ".$values['rent_attr']['rent_term'];
                        switch($values['period']){
                            case 'день': $notes.=" дней.";break;
                            case 'месяц': $notes.=" месяцев.";break;
                            case 'год': $notes.=" лет.";break;
                        }
                    }
                    //залог при аренде, сумма залога
                    if ($values['rent_attr']['is_pledge']){
                        $notes .= " При аренде требуется залог";
                        $notes.=(!empty($values['rent_attr']['pledge_summ']))?" в ".$values['rent_attr']['pledge_summ']."%.":".";
                    }
                    //процент комиссии для клиента
                    if ($values['rent_attr']['commis_client']) $notes .= " ".$values['rent_attr']['commis_client']."% комиссия для клиента от стоимости аренды.";
                    //процент комиссии для риэлтора
                    if ($values['rent_attr']['commis_an']) $notes .= " ".$values['rent_attr']['commis_an']."% комиссия для риэлтора от стоимости аренды.";
                }
                ///обрабатываем groundinf
                if ($values['docs']){
                    //текущий правовой статус
                    if ($values['docs']['doc']) $this->fields['notes'] .= " Текущий правовой статус - ".$values['docs']['doc'].".";
                    //год последенй сделки
                    if ($values['docs']['last_deal']) $this->fields['notes'] .= " Год последенй сделки - ".$values['docs']['last_deal'].".";
                    if ($values['ground_attr']){
                        //права текущих владельцев участка
                        if ($values['docs']['ground_attr']['legal_status']) $this->fields['notes'] .= " Права текущих владельцев участка - ".$values['docs']['ground_attr']['legal_status'].".";
                        //текущее назначение участка
                        if ($values['docs']['ground_attr']['ground_goal']) $this->fields['notes'] .= " Текущее назначение участка - ".$values['docs']['ground_attr']['ground_goal'].".";
                    }
                }
                ///обрабатываем adrinf
                //определяем город
                $this->fields['id_city']=$this->getInfoFromTable($this->sys_tables['cities'],$values['adrinf']['city'],"title",false,'id');
                //определяем регион
                $this->fields['id_region']=$this->getInfoFromTable($this->sys_tables['region'],$values['adrinf']['region'],false,false,'id');
                //на случай, если в разделе <REGION> указан не регион, а город(СПБ или М)
                if ( empty( $this->fields['id_region'])) $this->fields['id_city']=$this->getInfoFromTable($this->sys_tables['cities'],$values['adrinf']['region'],"title",false,'id');
                //определяем район города
                //adm1
                $values['adrinf']['adm1']=preg_replace('/район|р-он|р-н|р\./','',$values['adrinf']['adm1']);
                $this->fields['id_district']=$this->getInfoFromTable($this->sys_tables['districts'],$values['adrinf']['adm1'],"title",false,'id');
                if ($this->fields['id_district']){
                    $this->fields['id_region'] = '78';
                    $this->fields['id_area'] = 0;
                } 
                //если не нашли в районах города, значит это район области
                if ( empty( $this->fields['id_district'])){
                    $this->fields['id_area'] = $this->getInfoFromTable($this->sys_tables['district_areas'],$values['adrinf']['adm1'],"title",false,'id');
                    if ($this->fields['id_area']){
                        $this->fields['id_district'] = 0;
                        $this->fields['id_region'] = '47';
                    } 
                } 
                //улица, дом, корпус
                $house_korp = preg_split('/корп[\.]?|к[\.]?/',$values['adrinf']['num'],-1,PREG_SPLIT_NO_EMPTY);
                $this->getAddress($values['adrinf']['street'],'',preg_replace('/[^\d]/','',$house_korp[0]),preg_replace('/[^\d]/','',$house_korp[1]));
                //группировка объектов по адресу
                $this->groupByAddress($this->estate_type, $this->fields, true);
                //метро
                $this->fields['id_subway'] = $this->getInfoFromTable($this->sys_tables['subways'],$values['adrinf']['subway']['metro'],"title",false,'id');
                //путь до метро
                $this->fields['id_way_type'] = $this->getInfoFromTable($this->sys_tables['way_types'],$values['adrinf']['subway']['typetransp'],"gdeetotdomxml",false,'id');
                //время до метро
                preg_match('/\d{1,2}[\s]?(минут|мин)/',$values['adrinf']['subway']['metro_time'],$matches);
                preg_match('/\d{1,2}/',$matches[0],$way_time_m);
                $way_time_m = $way_time_m[0];
                preg_match('/\d{1,2}[\s]?(часов|час|ч)/',$values['adrinf']['subway']['metro_time'],$matches);
                preg_match('/\d{1,2}/',$matches[0],$way_time_h);
                $way_time_h=$way_time_h[0];
                $this->fields['way_time'] = abs($way_time_h*60-$way_time_m);
                //железнодорожная станция
                if ($values['adrinf']['railroad_attr']){
                    $rr_notes="";
                    if ($values['adrinf']['railroad_attr']['rr_station']) $this->fields['railstation'] = $values['adrinf']['railroad_attr']['rr_station'];
                    if ($values['adrinf']['railroad_attr']['rr_line']) $rr_notes .= $this->fields['railstation'].' ж/д ветки "'.$values['adrinf']['railroad_attr']['rr_line'].'".';
                    if ($values['adrinf']['railroad_attr']['rr_time']) $rr_notes .= " Расстояние до ж/д станции - ".$values['adrinf']['railroad_attr']['rr_time'];
                    if ($values['adrinf']['railroad_attr']['rr_typetransp']) $rr_notes .= " ".$values['adrinf']['railroad_attr']['rr_typetransp'].".";
                }
                //координаты объекта
                $this->fields['lat'] = $values['adrinf']['coord']['x'];
                $this->fields['lng'] = $values['adrinf']['coord']['y'];
                //заполняем txt_addr
                $this->fields['txt_addr'] = $this->fields['address'];
                ///обрабатываем flatinf
                //дополнительная информация
                $this->fields['notes'] = $values['flatinf']['addition'];
                $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
                //этаж
                $this->fields['level'] = $values['flatinf']['floor'];
                //всего комнат
                $this->fields['rooms_total'] = $values['flatinf']['room_qty'];
                //количество комнат для аренды/продажи
                $this->fields['rooms_sale'] = $values['flatinf']['oper_room_qty'];
                if ($this->fields['id_type_object'] == 1) $this->fields['rooms_sale'] = $this->fields[rooms_total];
                //общая площадь
                $this->fields['square_full'] = $values['flatinf']['fullsquare'];
                //жилая площадь
                $this->fields['square_live'] = $values['flatinf']['livesquare'];
                //площадь кухни
                $this->fields['square_kitchen'] = $values['flatinf']['kitchensquare'];
                //площадь комнат
                $this->fields['square_rooms'] = $values['flatinf']['allroomsquare'];
                //планировка (поля нет, вносим в примечания)
                if ($values['flatinf']['rooms_style'])$this->fields['notes'] .= " Квартира спланирована как ".$values['flatinf']['rooms_style'].".";
                //санузел
                $this->fields['id_toilet'] = $this->getInfoFromTable($this->sys_tables['toilets'],$values['flatinf']['bathroom'],"title",false,'id');
                //высота потолков
                $this_fields['ceiling_height'] = $values['flatinf']['headroom'];
                //ремонт
                $this->fields['id_facing'] = $this->getInfoFromTable($this->sys_tables['facings'],$values['flatinf']['repair'],"gdeetotdomxml",false,'id');
                //материал пола
                $this_fields['id_floor'] = $this->getInfoFromTable($this->sys_tables['floors'],$values['flatinf']['floor_mat'],"title");
                //перекрытия (поля нет, вносим в примечания)
                switch($values['flatinf']['overlap_mat']){
                    case 'ЖБ': $this->fields['notes'] .= "Железобетонные перекрытия.";break;
                    case 'дерево': $this->fields['notes'] .= "Деревянные перекрытия.";break;
                    case 'смешанные': $this->fields['notes'] .= "Смешанные перекрытия.";break;
                }
                //тип окон (поля нет, вносим в примечания)
                switch($values['flatinf']['window']){
                    case 'деревянные': $this->fields['notes'] .= " Деревянные окна.";break;
                    case 'стеклопакет': $this->fields['notes'] .= " Стеклопакеты.";break;
                }
                //вид из окон
                switch(true){
                    case $values['flatinf']['view'] == 'улица': 
                        $this->fields['id_window'] =  $this->getInfoFromTable($this->sys_tables['windows'],"на улицу","title")['id'];
                        break;
                    case $values['flatinf']['view'] == 'двор': 
                        $this->fields['id_window'] =  $this->getInfoFromTable($this->sys_tables['windows'],"во двор","title")['id'];
                        break;
                    case ($values['flatinf']['view'] == 'двор,улица')||($values['flatinf']['view'] == 'двор, улица'): 
                        $this->fields['id_window'] = $this->getInfoFromTable($this->sys_tables['windows'],"на обе стороны","title")['id'];
                        break;
                    default: 
                        $this->fields['id_window'] = $this->getInfoFromTable($this->sys_tables['windows'],"на три стороны","title")['id'];
                        break;
                }
                ///обрабатываем flatinf/auxinfo_attr
                //лифт
                switch(true){
                    case $values['flatinf']['auxinfo_attr']['elevator'] == 0:  
                        $this->fields['id_elevator'] = $this->getInfoFromTable($this->sys_tables['elevators'],"-","short_title")['id'];
                        break;
                    case $values['flatinf']['auxinfo_attr']['elevator'] == 1:  
                        $this->fields['id_elevator'] = $this->getInfoFromTable($this->sys_tables['elevators'],"+","short_title")['id'];
                        break;
                    case $values['flatinf']['auxinfo_attr']['elevator'] == 2:  
                        $this->fields['id_elevator'] = $this->getInfoFromTable($this->sys_tables['elevators'],"2","short_title")['id'];
                        break;
                    case !empty($values['flatinf']['auxinfo']['elevator'])&&!empty($values['auxinfo_attr']['fr_elevator']): 
                        $this->fields['id_elevator'] = $this->getInfoFromTable($this->sys_tables['elevators'],"гп","short_title")['id'];
                        break;
                }
                //парковка (поля нет, вносим в примечания)
                switch($values['flatinf']['auxinfo_attr']['parking']){
                    case 'гараж': $this->fields['notes'] .= ' Гараж.';break;
                    case 'машиноместо': $this->fields['notes'] .= ' Машиноместо.';break;
                    case 'охраняемая парковка': $this->fields['notes'] .= ' Охраняемая парковка.';break;
                    case 'неохраняемая парковка': $this->fields['notes'] .= ' Неохраняемая парковка.';break;
                    case 'подземная парковка': $this->fields['notes'] .= ' Подземная парковка.';break;
                    case 'стихийная парковка': $this->fields['notes'] .= ' Стихийная  парковка.';break;
                }
                //кондиционирование (поля нет, вносим в примечания)
                switch($values['flatinf']['auxinfo_attr']['aircond']){
                    case 'сплит-система': $this->fields['notes'] .= ' Установлена сплит-система.';break;
                    case 'обычная': $this->fields['notes'] .= ' Установлен кондиционер.';break;
                    case 'центральное кондиционирование': $this->fields['notes'] .= ' Центральное кондиционирование.';break;
                }
                //телевизор (поля нет, вносим в примечания)
                switch($values['flatinf']['auxinfo_attr']['tv']){
                    case 'кабельное': $this->fields['notes'] .= ' Кабельное ТВ.';break;
                    case 'спутниковое': $this->fields['notes'] .= ' Спутниковое ТВ.';break;
                    case 'iptv': $this->fields['notes'] .= ' iptv';break;
                }
                //мебель
                $this->fields['furniture'] = $values['flatinf']['auxinfo_attr']['is_furniture']?1:2;
                //встроенная бытовая техника (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_conseqpm']) $this->fields['notes'] .= ' Есть встроенная бытовая техника.';
                //балкон
                $this->fields['id_balcon'] = $values['flatinf']['auxinfo_attr']['is_balcony']?2:0;
                if ($this->fields['id_balcon']){
                    //тип балкона
                    switch($values['flatinf']['auxinfo_attr']['balcon']){
                        case 'балкон': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],",балкон","title");break;
                        case 'лоджия': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"лоджия","title");break;
                        case 'эркер': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"эркер","title");break;
                        case 'балкон и лоджия': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"балкон и лоджия","title");break;
                        case 'балкон и эркер': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"балкон и эркер","title");break;
                        case '2 балкона и более': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"2 балкона","title");break;
                        case '2 лод и более': $this->fields['balcons'] = $this->getInfoFromTable($this->sys_tables['balcons'],"2 ","title");break;
                    }
                }
                //остекление балкона (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_glazing']) $this->fields['notes'] .= ' Балкон остеклен.';
                //телефон
                $this->fields['phone']=($values['flatinf']['auxinfo_attr']['phone']!='без телефона')?1:2;
                //интернет (поля нет, вносим в примечания)
                switch($values['flatinf']['auxinfo_attr']['inet']){
                    case 'есть': " Проведен интернет.";break;
                    case 'есть возможность проведения': " Есть возможность подключения интернета.";break;
                }
                //информация о дополнительных удобствах
                if($values['flatinf']['auxinfo_attr']['add_improv']){
                        $this->fields['notes'] .= " На объекте присутствует дополнительные удобства: ".$values['flatinf']['auxinfo_attr']['add_improv'].".";
                }
                //возможность перепланировки (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_replanning']) $this->fields['notes'] .= ' Есть возможность перепланировки.';
                //мусоропровод (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_trashchute']) $this->fields['notes'] .= ' Мусоропровод.';
                //коммунальность квартиры (поля нет, вносим в примечания)
                if($values['flatinf']['auxinfo_attr']['is_communal']) $this->fields['notes'] .= ' Квартира коммунальная.';
                ///обрабатываем buildinginf
                //тип дома
                $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],$values['buildinginf']['bldkind'],"title")['id'];
                //год постройки (поля нет, вносим в примечания)
                if ($values['buildinginf']['bldyear']) $this->fields['notes'] = "Здание ".$values['buildinginf']['bldyear']." года постройки. ".$this->fields['notes'];
                $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
                //этажей всего
                $this->fields['level_total'] = $values['buildinginf']['floor_qty'];
                //для новостроек
                if(!empty($values['buildinginf']['new_building_attr'])){
                    $values['type_realty']='новостройки';
                    $this->estate_type='build';
                    //статус дома(сдан/не сдан)
                    if($values['buildinginf']['new_building']['obj_status'] == 'сдан')
                        $this->fields['build_completed']=1;
                    $this->fields['id_build_complete']=$this->getInfoFromTable($this->sys_tables['build_complete'],$values['buildinginf']['new_building']['DL_ENDING_CONSTR']);
                }
                ///обрабатываем infrastructure
                //канализация
                if ($values['infrastructure_attr']['seweradge']){
                    if ($values['infrastructure_attr']['seweradge'] != 'есть возможность подведения'&&$values['infrastructure_attr']['seweradge'] != 'нет')
                        $this->fields['canalization'] = 1;
                    //если есть возможность подведения, указываем это в примечаниях
                    elseif($values['infrastructure_attr']['seweradge']!='нет'){
                        $this->fields['notes'] .= ' Есть возможность подведения канализации.';
                    }
                    //нет канализации
                    else $this->fields['canalization'] = 2;
                }
                //водоснабжение (если центральное - значит есть горячая вода)
                if ($values['infrastructure_attr']['water']){
                    if ($values['infrastructure_attr']['water'] != 'нет')
                        $this->fields['hot_water'] = 1;
                    //если какая-то вода есть, указываем это в примечаниях
                    elseif($values['infrastructure']['water']!='нет'){
                        $this->fields['notes'] .= $values['infrastructure_attr']['water'];
                    }
                    else $this->fields['hot_water'] = 2;
                }
                //электричество
                if ($values['infrastructure_attr']['electricity']){
                    if ($values['infrastructure_attr']['electricity'] != 'есть возможность подключения'&&$values['infrastructure_attr']['electricity'] != 'нет')
                        $this->fields['electricity'] = 1;
                    //если есть возможность подключения, указываем это в примечаниях
                    elseif($values['infrastructure_attr']['electricity']!='нет'){
                        $this->fields['notes'] .= ' Есть возможность подключения электричества.';
                    }
                    //нет электричества
                    else $this->fields['electricity'] = 2;
                }
                //отопление
                if ($values['infrastructure_attr']['heating']){
                    if ($values['infrastructure_attr']['heating'] != 'без отопления')
                        $this->fields['heating'] = 1;
                    //если какая-то вода есть, указываем это в примечаниях
                    elseif($values['infrastructure']['heating']!='без отопления'){
                        $this->fields['notes'] .= $values['infrastructure_attr']['heating'];
                    }
                    else $this->fields['heating'] = 2;
                }
                //газ (поля нет, вносим в примечания)
                if ($values['infrastructure_attr']['gas']){
                    $this->fields['notes'] .= $values['infrastructure_attr']['gas'];
                }
                //картинки
                if(!empty($values['files']['file_attr'])){
                    $count_photos_limit = 0;
                    foreach($values['files']['file_attr'] as $key=>$img){
                        if( ( empty($photos_limit) || $photos_limit>$count_photos_limit ) && $this->checkPhoto( $img['FILEPATH'] ) ){
                            $this->fields['images'][] = $img['FILEPATH'];
                            ++$count_photos_limit;
                        }
                    }
                }
                $this->fields['notes'].=$notes;
            break;
        }
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                //добавление адреса в таблицу адресов с коорлинатами 
                if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 

        
        return $this->fields;
    }
}

/**
* Обработка полей из yandex-realty
*/
class YandexRXmlRobot extends Robot{
    public $file_format = 'yrxml';
    public $mapping = [];                                                                  
    public function getConvertedFields($values, $agency, $photos_limit = false,$return_deal_type = false){
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        //запоминание строки для логирования
        $values_to_log = $values; 
        $values['internal-id'] = $this->fields['real_internal_id'] = ( empty( $values['internal-id'])?0:$values['internal-id']);
        //если в id присутствуют не-цифры, заменяем их
        if(preg_match('/[^0-9]/sui',$values['internal-id'])){
            preg_match_all('/[^0-9]/sui',$values['internal-id'],$non_digits);
            preg_match_all('/[0-9]/sui',$values['internal-id'],$digits);
            //собираем id: время,тип,цифровой id
            $values['internal-id'] = substr( preg_replace( '/[^0-9]/sui', '', implode('', $digits[0]) . md5(implode('', $non_digits[0])) ) , 0, 16);
        }
        if(!empty($values['category'])) $values['category'] = mb_strtolower($values['category'], 'UTF-8');
        if(!empty($values['new-flat']) && in_array($values['new-flat'], array('да', 'true', '1', '+'))) $this->estate_type = 'build';
        else if(!empty($values['category'])) {
            $values['category'] = str_replace(
                array('земельный участок',  'flat',     'room',         'cottage', 'townhouse', 'house with lot',   'house', 'дом с участком',   'lot'),
                array('участок',            'квартира', 'комната',      'коттедж', 'таунхаус',  'дом',              'дом',   'дом',              'участок'),
                $values['category']
            );
            $this->fields['id_type_object'] = $this->getEstateType($values['category']);
            //для коммерческой тип объекта дочитываем отдельно
            if( empty( $this->fields['id_type_object']) && (!empty($values['commercial-type']) || !empty($values['commercial-building-type']))){
                if( !empty( $values['commercial-type'] ) ) $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_commercial'],$values['commercial-type'],'yrxml_value',false,'id');
                if( empty( $this->fields['id_type_object']) && !empty($this->fields['commercial-building-type'])) 
                    $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_commercial'],$values['commercial-building-type'],'yrxml_value',false,'id');
                if( !empty($this->fields['id_type_object']) )  $this->estate_type = 'commercial';
            }
            if( empty( $this->fields['id_type_object'])) {
                $errors_log['estate_type'][ $this->fields['real_internal_id'] ] = $values['category'];
                return false;
            }
        } else {
            $errors_log['estate_type'][ $this->fields['real_internal_id'] ] = '';
            return false;
        }
        
        //тип сделки (2-продажа/1-аренда)
        $this->fields['rent'] = stristr('продажа',mb_strtolower($values['type'],'UTF-8'))!='' ? 2 : 1;

        //только тип недвижимости + сделка
        if(!empty($return_deal_type)) return $this->fields;

        // источник
        $this->fields['info_source'] = 8;
        
        //  номер объекта
        if(!empty($values['internal-id'])){
            if(preg_match("/[^0-9]/sui",$values['internal-id'])){
                $i = rand(0,9);
                $this->fields['external_id'] = preg_replace('/[^0-9]/',$i,md5($values['internal-id'])).rand(0,1000);
            }else $this->fields['external_id'] = $values['internal-id'];
        }else{
            $this->fields['external_id'] = substr(preg_replace('/[^0-9]/','',md5(time())).round(rand(0,100000)),rand(0,10),10);
        } 
        if( empty( $this->fields['external_id'])){
            $errors_log['external_id'][] = $this->fields['external_id'];
            return false;
        }
        //поля коммерческой пока не пишем:
        //purpose - назначение
        //period
        //cleaning-included
        //utilities-included
        //electricity-included
        //taxation-form - упрощеная или нет форма налогообложения
        
        
        //создаем пустой текстовый адрес, чтобы не было ошибок в классе moderation
        $this->fields['txt_addr'] = "";

        $this->fields['addr_source'] = "<region>".( empty( $values['location']['region'])?"":$values['location']['region'])."</region><locality-name>".( empty( $values['location']['locality-name'])?"":$values['location']['locality-name'])."</locality-name><sub-locality-name>".( empty( $values['location']['sub-locality-name'])?"":$values['location']['sub-locality-name'])."</sub-locality-name><address>".( empty( $values['location']['address'])?"":$values['location']['address'])."</address>";
        
        ///отделяем городские от загородных, определяем тип объекта и его адрес
        if( empty($values['location']['region']) || ( !empty($values['location']['region']) && preg_match('/етербург/',$values['location']['region']) ) || (!empty($values['location']['locality-name']) && preg_match('/етербург/',$values['location']['locality-name']))){
             if( !empty($values['location']['region']) && !preg_match('/етербург/',$values['location']['region'] ) && !empty($values['location']['locality-name']) && !preg_match('/етербург/',$values['location']['locality-name'] ) ) {
                $errors_log['address'][$this->fields['external_id'] ] = 'адрес: ' . $this->fields['addr_source'];
                return false;
            }
            //объект находится в городе
            $this->fields['id_region'] = 78;
            //получаем район города
            //так как все названия районов из одного слова, разбиваем по пробелам и пробуем обе части
            if(!empty($values['location']['sub-locality-name'])){
                $sub_locality_name = explode(' ',$values['location']['sub-locality-name']);
                foreach($sub_locality_name as $key=>$item){
                     $district = $this->getInfoFromTable($this->sys_tables['districts'],$item,'title')['id'];
                     if(!empty($district)){
                         $this->fields['id_district'] = $district;
                         break;
                     }
                }
            }
            else $district = "";
            
            $this->fields['id_area'] = 0;
            //разбираем адрес
            
            if(!empty($values['location']['locality-name'])){
                //читаем город в области
                $this->getTxtGeodata($values['location']['locality-name'],4);
            }
            
            if( empty( $this->fields['id_street'])){
                $this->fields['txt_addr'] = $values['location']['address'];
                //если в адрес воткнуто название ЖК, убираем его
                if(is_string($values['location']['address']) && strstr($values['location']['address'],'ЖК')){
                    $values['location']['address'] = "";
                } 
                $full_addr = [];
                foreach( $values['location'] as $k => $value ) $full_addr[] = is_string( $value ) && preg_match( '#[а-я]{1,}#msiU', $value ) ? $value : '';     
                $full_addr = implode( ', ', $full_addr );
                $this->getGeodataDdata( $full_addr  );
                if( empty( $this->fields['id_street'] ) && empty( $this->fields['id_place'] ) ) $this->getTxtGeodata($values['location']['address']);
            } else {
                echo ';';
            }
            //при отсутствии полей, утсанавливаем в 0, чтобы они затерлись
            if( empty( $this->fields['id_street'])) $this->fields['id_street'] = 0;
            if( empty( $this->fields['id_city'])) $this->fields['id_city'] = 0;
            if( empty( $this->fields['id_place'])) $this->fields['id_place'] = 0;
            if( empty( $this->fields['id_district'])) $this->fields['id_district'] = 0;
            if( empty( $this->fields['id_area'])) $this->fields['id_area'] = 0;
            if( empty( $this->fields['house'])) $this->fields['house'] = 0;
            if( empty( $this->fields['corp'])) $this->fields['corp'] = 0;
            $this->fields['txt_addr'] = $values['location']['address'];
            //группировка объектов по адресу
            $this->groupByAddress($this->estate_type, $this->fields, true);
            //метро
            if(!empty($values['location']['metro']['name'])) {
                if( is_array( $values['location']['metro']['name'] ) && !empty( $values['location']['metro']['name'][0] ) ) $subway_title = $values['location']['metro']['name'][0];
                else $subway_title = $values['location']['metro']['name'];
                $subway = $db->fetch("SELECT id FROM ".$this->sys_tables['subways']." WHERE map_title LIKE ? OR title LIKE ?","%".trim( $subway_title )."%","%".trim( $subway_title )."%")['id'];
                if(!empty($subway)) $this->fields['id_subway'] = $subway;
            }
            //время до метро
            if(!empty($values['location']['metro']['time-on-transport'])){
                $this->fields['id_way_type'] = 3;
                $this->fields['way_time'] = Convert::ToInt($values['location']['metro']['time-on-transport']);
            }
            elseif(!empty($values['location']['metro']['time-on-foot'])){
                $this->fields['id_way_type'] = 2;
                $this->fields['way_time'] = Convert::ToInt($values['location']['metro']['time-on-foot']);
            }
        }
        elseif(!empty($values['location']['region']) && preg_match('/енинградская/',$values['location']['region'])){
            ///объект находится в области
            $this->fields['id_region'] = 47;
            //тип объекта
            if( $this->estate_type == 'country') $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_country'], $values['category'], 'title')['id'];
            //если комната, указываем 1 в количестве комнат в сделке
            if(!empty($this->fields['id_type_object']) && $this->fields['id_type_object'] == 2) $this->fields['rooms_sale'] = 1;
            //читаем район области
            $this->getTxtGeodata($values['location']['district'],2);
            //читаем город в области
            if(!empty($values['location']['locality-name'])) $this->getTxtGeodata($values['location']['locality-name'],4);
            
            //разбираем адрес
            //теперь если текстовый адрес пуст, подставляем туда при наличии название поселка и деревни
            if(!empty($values['location']['address'])){
                //если в адрес воткнуто название ЖК, убираем его и записываем
                if(strstr($values['location']['address'],'ЖК')){
                    $values['location']['address'] = "";
                }
                $this->fields['txt_addr'] = $values['location']['address'];
                
                $fulladdr = ( empty( $values['location']['region']) ? "" : $values['location']['region'] . ", " ) . ( empty( $values['location']['district'] ) ? "" : $values['location']['district'] . ", "  ) . ( empty( $values['location']['address'] ) ? "" : $values['location']['address'] ) . ( empty( $this->fields['house'] ) ? "" : ", д." . $this->fields['house'] ) . ( empty( $this->fields['corp'] ) ? "" : ", к." . $this->fields['corp'] );
                
                if( empty( $this->fields['id_street'] ) && empty( $this->fields['id_place'] ) ) $this->getGeodataDdata( $fulladdr );
                if( empty( $this->fields['id_street'] ) && empty( $this->fields['id_place'] ) ) $this->getTxtGeodata( $values['location']['address'] );

                //при отсутствии полей, утсанавливаем в 0, чтобы они затерлись
                if( empty( $this->fields['id_street'])) $this->fields['id_street'] = 0;
                if( empty( $this->fields['id_city'])) $this->fields['id_city'] = 0;
                if( empty( $this->fields['id_place'])) $this->fields['id_place'] = 0;
                if( empty( $this->fields['id_district'])) $this->fields['id_district'] = 0;
                if( empty( $this->fields['id_area'])) $this->fields['id_area'] = 0;
                if( empty( $this->fields['house'])) $this->fields['house'] = 0;
                if( empty( $this->fields['corp'])) $this->fields['corp'] = 0;
                $this->fields['txt_addr'] = $values['location']['address'];
            }elseif(!empty($values['location']['locality-name'])) $this->fields['txt_addr'] = $values['location']['locality-name'];
            //группировка объектов по адресу
            $this->groupByAddress($this->estate_type, $this->fields, true);
            //ближайшая ж/д станция
            if(!empty($values['location']['railway-station'])) $this->fields['railstation'] = $values['location']['railway-station'];
        }
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 

        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( !empty($values['location']['latitude']) && !empty($values['location']['longitude'])) {
                $this->fields['lat'] = Convert::ToValue($values['location']['latitude']);
                $this->fields['lng'] = Convert::ToValue($values['location']['longitude']);
            } else {
                if( empty( $this->fields['lat'] ) || $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                    list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                    //добавление адреса в таблицу адресов с коорлинатами 
                    if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
                }
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 
        
        
        ///информация о продавце
        //имя продавца
        if( !empty( $values['sales-agent'] ) ) {
            if(!empty($values['sales-agent']['name'])) $this->fields['seller_name'] = $values['sales-agent']['name'];
            //телефон (может быть указано несколько, нам нужен первый)
            if(!empty($values['sales-agent']['phone']))
                $this->fields['seller_phone'] = is_array($values['sales-agent']['phone'])?$values['sales-agent']['phone'][0]:$values['sales-agent']['phone'];
            //тип продавца (владелец/агентство)
            if(!empty($values['sales-agent']['category']) && ($values['sales-agent']['category'] == 'агентство' || $values['sales-agent']['category'] == 'agency')){
                //если это агентство, пробуем найти его у нас
                if(!empty($values['sales-agent']['organization'])){
                    $values['sales-agent']['organization'] = trim(preg_replace('/(АН\s)|(Агентство недвижимости)/sui','',$values['sales-agent']['organization']));
                    if(!empty($values['sales-agent']['email'])){
                        $agency_id = $db->fetch("SELECT id 
                                                 FROM ".$this->sys_tables['agencies']." 
                                                 WHERE title LIKE ? AND email = ?","%".$values['sales-agent']['organization']."%",$values['sales-agent']['email'])['id'];
                    }
                    else{
                        $agency_id = $db->fetch("SELECT id 
                                                 FROM ".$this->sys_tables['agencies']." 
                                                 WHERE title LIKE ? AND email = ?","%".$values['sales-agent']['organization']."%")['id'];
                    }
                    $this->fields['id_agency'] = (!empty($agency_id))?$agency_id:0;
                }
            }
        }
        
        
        ///информация об условиях сделки
        //стоимость
        if(!empty($values['price'][0])){
            foreach($values['price'] as $key=>$item){
                if(!empty($values['price'][$key]['unit']) && $values['price'][$key]['unit']=='кв.м')
                    $this->fields['cost2meter'] =  preg_replace('/[^0-9\.]{1,}/', '',  $values['price'][$key]['value'] );
                else{
                    $this->fields['cost'] = ( !empty( $item['value'] ) ) ? preg_replace('/[^0-9\.]{1,}/', '',  $item['value'] ) : 0;
                    if( empty( $this->fields['cost']))
                        $errors_log['moderation'][$item['internal-id']][] = 'Не указана цена.';
                    //валюта если не рубли, ошибка
                    if(!empty($item['currency']) && $item['currency'] != 'RUR' && $item['currency'] != 'RUB'){
                        $errors_log['moderation'][$item['internal-id']][] = 'Обрабатываются только цены, указанные в рублях.';
                    }
                    //аренда посуточно
                    if(!empty($values['period']) && ($item['period'] == 'день' || $item['period'] == 'day')) $this->fields['by_the_day'] = 1;
                    //единица площади измерения: если указаны гектары, ставим флаг
                    if(!empty($item['unit']) && $item['unit'] == 'гектар') $from_hectars_multiplyier = true;
                }
            }
        }
        else{
            $this->fields['cost'] = ( !empty( $values['price']['value'])) ?  preg_replace('/[^0-9\.]{1,}/', '',  $values['price']['value'] ) : 0 ;
            if( empty( $this->fields['cost']))
                $errors_log['moderation'][ $this->fields['external_id'] ][] = 'Не указана цена.';
            //валюта если не рубли, ошибка
            if(!empty($values['price']['currency']) && $values['price']['currency'] != 'RUR' && $values['price']['currency'] != 'RUB'){
                $errors_log['moderation'][ $this->fields['external_id'] ][] = 'Обрабатываются только цены, указанные в рублях.';
            }
            //аренда посуточно
            if(!empty($values['period']) && ($values['period'] == 'день' || $values['period'] == 'day')) $this->fields['by_the_day'] = 1;
            //единица площади измерения: если указаны гектары, ставим флаг
            if(!empty($values['unit']) && $values['unit'] == 'гектар') $from_hectars_multiplyier = true;
        }
        
        
        ///информация об объекте
        //фотография
        //ремонт
        if(!empty($values['renovation'])){
            $id_facing = $db->fetch('SELECT id FROM '.$this->sys_tables['facings']." WHERE title=?",$values['renovation'])['id'];
            if(!empty($id_facing)) $this->fields['id_facing'] = $id_facing;
        }
        //описание
        if(!empty($values['description'])) $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($values['description']));
        //общая площадь
        if(!empty($values['area'])){
            $this->fields['square_full'] = $values['area']['value'];
        }
        //жилая площадь
        if(!empty($values['living-space'])){
            $this->fields['square_live'] = $values['living-space']['value'];
        }
        //кухни площадь
        if(!empty($values['kitchen-space'])){
            $this->fields['square_kitchen'] = $values['kitchen-space']['value'];
        }
        //площадь участка
        if(!empty($values['lot-area'])){
            $this->fields['square_ground'] = $values['lot-area']['value'];
            if(!empty($from_hectars_multiplyier)) $this->fields['square_ground'] *= 100;
        }
        
        ///описание жилого помещения
        
        //*общее количество комнат
        //студии отдельно
        if(!empty($values['studio']) && in_array(strtolower($values['studio']),array('1',"да","+","true"))){
            $this->fields['rooms_sale'] = 0;
            if($this->estate_type == 'live') $this->fields['rooms_total'] = 0;
        }else{
            //остальные - 
            if(!empty($values['rooms'])) $this->fields['rooms_total'] = $values['rooms'];
            elseif($this->estate_type == 'live' || $this->estate_type == 'build') $errors_log['moderation'][ $this->fields['external_id'] ][] = 'Не указано обязательное поле "общее количество комнат"';
            //*для жилой смотрим сколько комнат в сделке
            if($this->estate_type == 'live'){
                if(isset($values['rooms']) && isset($values['rooms-offered']) && empty($values['rooms']) && empty($values['rooms-offered'])){
                        $this->fields['rooms_sale'] = 0;
                        $this->fields['rooms_total'] = 0;
                }
                if(!empty($values['rooms-offered'])) $this->fields['rooms_sale'] = $values['rooms-offered'];
                elseif(!preg_match('/комн/sui',$values['category']) && !empty( $this->fields['rooms_total'] ) )  $this->fields['rooms_sale'] = $this->fields['rooms_total'];
                    else {
                        $errors_log['moderation'][ $this->fields['external_id'] ][] = 'Не указано обязательное поле "количество комнат для продажи/аренды"';
                    }
            }
            if($this->estate_type == 'build') {
                if(!empty($values['rooms'])) $this->fields['rooms_sale'] = $values['rooms'];
                elseif(isset($values['rooms']) && isset($values['rooms-offered']) && empty($values['rooms']) && empty($values['rooms-offered'])){
                    $this->fields['rooms_sale'] = $values['rooms'];
                }
            }
            if(!empty($errors_log['moderation'][ $this->fields['external_id'] ])) {
                return false;    
            }
        }
        
        
        //апартаменты
        if(!empty($values['apartments']) && $values['apartments'] != 'нет' && $values['apartments'] != '-' && $values['apartments'] != 'false') $this->fields['is_apartments'] = 1;
        //телефон
        if(!empty($values['phone']) && $values['phone'] != 'нет' && $values['phone'] != '-' && $values['phone'] != 'false') $this->fields['phone'] = 1;
        //мебель
        if(!empty($values['room-furniture']) && $values['room-furniture'] != 'нет' && $values['room-furniture'] != '-' && $values['room-furniture'] != 'false') $this->fields['furniture'] = 1;
        //стиральная машина
        if(!empty($values['washing-mashine']) && $values['washing-mashine'] != 'нет' && $values['washing-mashine'] != '-' && $values['washing-mashine'] != 'false') $this->fields['wash_mash'] = 1;
        //холодильник
        if(!empty($values['refrigerator']) && $values['refrigerator'] != 'нет' && $values['refrigerator'] != '-' && $values['refrigerator'] != 'false') $this->fields['refrigerator'] = 1;
        //балкон
        if(!empty($values['balcony'])){
            $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],$values['balcony'],'title',false,'id');
            //$this->fields['id_balcon'] = ($id_balcon)?$id_balcon:0;
        }
        //санузел
        if(!empty($values['bathroom-unit'])){
            if($values['bathroom-unit'] == '2') $values['bathroom-unit'] = '2 санузла';
            $this->fields['id_toilet'] = $this->getInfoFromTable($this->sys_tables['toilets'],$values['bathroom-unit'],'title',false,'id');
        }
        //покрытие пола
        if(!empty($values['floor-covering'])){
            $this->fields['id_floor'] = $this->getInfoFromTable($this->sys_tables['floors'],$values['floor-covering'],'title',false,'id');
        }
        //вид из окон
        if(!empty($values['window-view'])){
            $this->fields['id_window'] = $this->getInfoFromTable($this->sys_tables['floors'],$values['window-view'],'title',false,'id');
        }
        //этаж
        if(!empty($values['floor'])) $this->fields['level'] = Convert::ToInt($values['floor']);
        
        ///описание здания
        //этажей всего
        if(!empty($values['floors-total'])) $this->fields['level_total'] = Convert::ToInt($values['floors-total']);
        
        //название жк
        if(!empty($values['yandex-building-id'])){
            $values['yandex-building-id'] = Convert::ToInt($values['yandex-building-id']);
            $he_info = $this->getYandexComplexById($values['yandex-house-id'] ?? 0, $values['yandex-building-id']);
            if(!empty($he_info)){
                $this->fields['id_housing_estate'] = $he_info['id'];
                if( empty( $this->fields['id_street'])){
                    $this->fields['lat'] = $he_info['lat'];
                    $this->fields['lng'] = $he_info['lng'];
                }
            }
        }                            

        if( empty( $this->fields['id_housing_estate']) && !empty($values['building-name'])){
            $he_title = trim(preg_replace('/жк|[^А-я0-9A-z\s\-]/sui','',$values['building-name']));
            
            $id_he  = $this->getComplexId(1,false,$he_title);
            
            //если не получилось по названию, пробуем искать по chpu
            if( empty( $id_he)){
                $values['building-name'] = Convert::ToTranslit(preg_replace('/[^а-я0-9A-z\s]/sui','%',trim(preg_replace('/жк/sui','',$values['building-name']))));
                $id_he = $db->fetch("SELECT id FROM ".$this->sys_tables['housing_estates']." WHERE chpu_title LIKE '%".$values['building-name']."%'");
            }else{
                $he_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$id_he);
                if(!empty($he_info)){
                    $this->fields['lat'] = $he_info['lat'];
                    $this->fields['lng'] = $he_info['lng'];
                }
            }
            
            $this->fields['id_housing_estate'] = ($id_he) ? $id_he : 0;
        }
        
        //костыль: открепляем ЖК Времена года от Адвекса
        if( !empty( $this->fields['id_housing_estate'] ) && $this->fields['id_housing_estate'] == 191 && $this->fields['id_user'] == 3991) $this->fields['id_housing_estate'] = 0;
        //тип дома
        if(!empty($values['building-type'])){
            $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],$values['building-type'],'title',false,'id');
        }else{
            //серия дома
            if(!empty($values['building-series'])){
                $id_building_series = $db->fetch("SELECT id FROM ".$this->sys_tables['building_types']." WHERE ? REGEXP short_title",$values['building-series']);
                $this->fields['id_building_type'] = ($id_building_series)?$id_building_series:0;
            }
        }
        //для новостроек год и квартал сдачи
        if($this->estate_type == 'build' && !empty($values['built-year']) && !empty($values['ready-quarter'])){
            $id_build_complete = $db->fetch("SELECT id FROM ".$this->sys_tables['build_complete']." WHERE year=? AND decade=?",$values['built-year'],$values['ready-quarter'])['id'];
            $this->fields['id_build_complete'] = ($id_build_complete)?$id_build_complete:0;
        }
        //наличие лифта
        if(isset($values['lift'])){
            if(!empty($values['lift']) && $values['lift'] != 'нет' && $values['lift'] != 'false' && $values['lift'] != '-') $this->fields['id_elevator'] = 2;
            else $this->fields['id_elevator'] = 5;
        }
        //элитность
        if(!empty($values['is-elite'])){
            if($values['is-elite'] != 'нет' && $values['is-elite'] != 'false' && $values['is-elite'] != '-') $this->fields['is-elite'] = 1;
            else $this->fields['is-elite'] = 2;
        }
        //высота потолков
        if(!empty($values['contractor']) && $values['contractor'] == 1){
            $this->fields['contractor'] = $values['contractor'];
        }
        if(!empty($values['asignment']) && $values['asignment'] == 1){
            $this->fields['asignment'] = $values['asignment'];
        }
        //высота потолков
        if(!empty($values['ceiling-height'])){
            $this->fields['ceiling_height'] = $values['ceiling-height'];
        }
        
        ///для загородной
        //санузел
        if(!empty($values['toilet'])){
            $id_toilet = $db->fetch("SELECT id FROM ".$this->sys_tables['toilets_country']." WHERE title REGEXP ? ",$values['toilet'])['id'];
            $this->fields['id_toilet'] = ($id_toilet)?$id_toilet:0;
        }
        //отопление
        if(isset($values['heating-supply'])){
            if(!empty($values['heating-supply']) && $values['heating-supply'] != 'нет' && $values['heating-supply'] != 'false' && $values['heating-supply'] != '-') $this->fields['id_heating'] = 2;
            else $this->fields['id_heating'] = 3;
        }
        //баня
        if(isset($values['sauna'])){
            if(!empty($values['heating-supply']) && $values['heating-supply'] != 'нет' && $values['heating-supply'] != 'false' && $values['heating-supply'] != '-') $this->fields['id_heating'] = 3;
            else $this->fields['id_heating'] = 2;
        }
        //водоснабжение
        if(isset($values['water-supply'])){
            if(!empty($values['water-supply']) && $values['water-supply'] != 'нет' && $values['water-supply'] != 'false' && $values['water-supply'] != '-') $this->fields['id_water_supply'] = 2;
            else $this->fields['id_water_supply'] = 3;
        }
        //электроснабжение
        if(isset($values['electricity-supply'])){
            if(!empty($values['electricity-supply']) && $values['electricity-supply'] != 'нет' && $values['electricity-supply'] != 'false' && $values['electricity-supply'] != '-') $this->fields['id_electricity'] = 2;
            else $this->fields['id_electricity'] = 3;
        }
        //газоснабжение
        if(isset($values['gas-supply'])){
            if(!empty($values['gas-supply']) && $values['gas-supply'] != 'нет' && $values['gas-supply'] != 'false' && $values['gas-supply'] != '-') $this->fields['id_gas'] = 2;
            else $this->fields['id_gas'] = 3;
        }
        
        //статус объекта
        
        if(!empty($values['viewtype'])){
            if($this->fields['id_user'] == 50647){
                switch($values['viewtype']){
                    case 3: $values['viewtype'] = 1;break;
                    case 4: $values['viewtype'] = 2;break;
                    case 6: $values['viewtype'] = 3;break;
                }
            } else if( $this->fields['id_user'] == 48136 && $values['viewtype'] == 6 ) $values['viewtype'] = 3;
            $this->getStatus( $values['viewtype'] );
        } 
        else{
            $this->fields['status'] = 2;
            $this->fields['status_date_end'] = '0000-00-00';
        }
        
        
        ///костыль для картинок Домплюсофис
        if($agency['id'] == 4790){
            if(!is_array($values['image'])) $values['image'] = "http://kn.domplusoffice.ru/import".substr($values['image'],strrpos($values['image'],'/'));
            else
                foreach($values['image'] as $key=>$img){
                    $values['image'][$key] = "http://kn.domplusoffice.ru/import".substr($img,strrpos($img,'/'));
                }
        }
        ///
        
        //картинки
        if(!empty($values['image'])){
            $count_photos_limit = 0;
            if(!is_array($values['image'])) $values['image'] = array($values['image']);
            foreach($values['image'] as $key=>$img){
                $img = str_replace(array("\a","\b","\f","\r","\t","\v","\n"),"", html_entity_decode( $img ) );
                if( empty( $photos_limit) || $photos_limit>$count_photos_limit && !empty($img) && strlen($img)>10){
                    if( $this->fields['id_user'] != 55686) {
                        if($this->checkPhoto($img)) {
                            //убираем ?... справа от расширения. оно не дает сохранить файл (кроме НМаркета)
                             $this->fields['images'][] = preg_replace("/\?.*/",'',$img);
                            ++$count_photos_limit;
                        }
                    } else $this->fields['images'][] = $img;
                }
            }
        }
        
        return $this->fields;
    }
}

/**
* Обработка полей из avito-realty
*/
class AvitoRXmlRobot extends Robot{
    public $file_format = 'avitoxml';
    public $mapping = [];
    public function getConvertedFields($values, $agency, $photos_limit = false,$return_deal_type = false){
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        //запоминание строки для логирования
        $values_to_log = $values; 
        
        //тип сделки (2-продажа/1-аренда)
        $this->fields['rent']=preg_match('/прода/sui',$values['OperationType'])?2:1;
        //регион 
        $development = $db->fetch("SELECT * FROM ".$this->sys_tables['avito_developments']." WHERE housing_id = " . $values['newdevelopmentid'] );
        $address = $values['Address'] = $development['address'] ?? '';
        if( !empty( $address ) ) {
            if( preg_match( '/етербург/',$address) )  $this->fields['id_region'] = 78; ///объект находится в городе
            elseif( preg_match( '/енинградская/', $address ) ) $this->fields['id_region'] = 47; ///объект находится в области
        }
            
        ///определяем тип недвижимости и тип объекта
        if(!empty($values['category'])){
            switch(true){
                case (!empty($values['MarketType']) && preg_match('/овостройк/sui',$values['MarketType'])): 
                    $this->estate_type = 'build';
                    $this->fields['id_type_object'] = 1;
                    break;
                case preg_match('/квартиры/sui',$values['category']): 
                    $this->estate_type = 'live'; 
                    $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_'.$this->estate_type],"квартира",'title',false,'id');
                    break;
                case preg_match('/комнаты/sui',$values['category']):
                    $this->estate_type = 'live';
                    $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_'.$this->estate_type],"комната",'title',false,'id');
                    break;
                case preg_match('/дом|дача|таунхаус/sui',$values['category']):
                    if($this->fields['id_region'] == 47){
                        $this->estate_type = 'country';
                        $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_'.$this->estate_type],$values['ObjectType'],'avitoxml_value',false,'id');
                    } 
                    else $this->estate_type = 'live';
                    break;
                case preg_match('/участки/sui',$values['category']): 
                    $this->estate_type = 'country'; 
                    $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_'.$this->estate_type],$values['category'],'avitoxml_value',false,'id'); 
                    break;
                case preg_match('/гараж/sui',$values['category']): 
                    $this->estate_type = 'commercial'; 
                    $this->fields['id_type_object'] = $db->fetch("SELECT id FROM ".$this->sys_tables['type_objects_commercial']." WHERE title LIKE '%гараж%'");
                    break;
                case preg_match('/коммерческ/sui',$values['category']):
                    $this->estate_type = 'commercial';
                    $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_'.$this->estate_type],mb_strtolower($values['ObjectType'],"utf-8"),'avitoxml_value',true,'id');
                    break;
                default: $object_type = "";
            }
        }
        if( empty( $this->estate_type) || empty($this->fields['id_type_object'])){
            $errors_log['estate_type'][$values['id']] = $values['objecttype'];
            return false;
        }
        
        //только тип недвижимости + сделка
        if(!empty($return_deal_type)) return $this->fields;
        
        // источник
        $this->fields['info_source'] = 9;
        
        //  номер объекта
        if( !empty( $values['Id'] ) ) $this->fields['external_id'] = preg_replace("/[^0-9]/sui", "", $values['Id'] );;
        
        //создаем пустой текстовый адрес, чтобы не было ошибок в классе moderation
        $this->fields['txt_addr'] = "";

        if(!empty($values['Address']) && preg_match('/етербург/',$values['Address'])) $this->fields['id_region'] = 78;
        else $this->fields['id_region'] = 47;
        if( !empty( $values['Address'] ) ) $this->getGeodataDdata( $values['Address'] );
        
        //группировка объектов по адресу
        $this->groupByAddress($this->estate_type, $this->fields, true);
        
        ///информация о продавце
        //имя продавца
        if(!empty($values['ManagerName'])) $this->fields['seller_name'] = $values['ManagerName'];
        //телефон
        if(!empty($values['ContactPhone'])) $this->fields['seller_phone'] = $values['ContactPhone'];
        //название компании
        if(!empty($values['CompanyName'])){
            //пробуем найти его у нас
            $agency_id = $db->fetch("SELECT id 
                                     FROM ".$this->sys_tables['agencies']." 
                                     WHERE title LIKE ? AND email = ?","%".$values['sales-agent']['organization']."%",(!empty($values['sales-agent'])?$values['sales-agent']['email']:""))['id'];
            $this->fields['id_agency'] = (!empty($agency_id))?$agency_id:0;
        }
        //статус объявления
        if(!empty($values['AdStatus']))
            switch($values['AdStatus']){
                case 'Highlight':   $this->getStatus(1);    break;
                case 'Premium':     $this->getStatus(2);    break;
                case 'VIP':         $this->getStatus(3);    break;
                default:            $this->getStatus(6);    break;
            }
        
        ///информация об условиях сделки
        //стоимость
        if(!empty($values['Price'])) $this->fields['cost'] = $values['Price'];
        //посуточно/не посуточно
        $this->fields['by_the_day'] = ( ( !empty($values['LeaseType'] ) && preg_match( '/посуточн/sui', $values['LeaseType'] ) )? 1 : 2 );
        
        ///информация об объекте
        switch($this->estate_type){
            case 'build':
            case 'live':
                $this->fields['rooms_sale'] = (!empty($values['SaleRooms'])?$values['SaleRooms']:0);
                if( !empty( $values['Rooms'] ) ) $this->fields['rooms_total'] = $this->fields['rooms_sale'] = $values['Rooms'];
                if( !empty( $values['Square'] ) ) $this->fields['square_full'] = $values['Square'];
                if( !empty( $values['LivingSpace'] ) ) $this->fields['square_live'] = $values['LivingSpace'];
                if( !empty( $values['KitchenSpace'] ) ) $this->fields['square_kitchen'] = $values['KitchenSpace'];
                if( !empty( $values['Floor'] ) ) $this->fields['level'] = $values['Floor'];
                if( !empty( $values['Floors'] ) ) $this->fields['level_total'] = $values['Floors'];
                if( !empty( $values['HouseType'] ) ) $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],$values['HouseType'],'avitoxml_value',false,'id');
                if( !empty( $values['Decoration'] ) ) $this->fields['id_facing'] = $this->getInfoFromTable($this->sys_tables['facings'],$values['Decoration'],'avitoxml_value',false,'id');
            break;
            case 'country':
                if(!empty($values['Floors'])) $this->fields['level_total'] = $values['Floors'];
                //материал стен
                if(!empty($values['WallsType'])) $this->fields['id_construct_material'] = $this->getInfoFromTable($this->sys_tables['building_types'],$values['WallsType'],'avitoxml_value',false,'id');
                //площадь прилегающего участка или самого участка
                if(!empty($values['LandArea'])) $this->fields['square_ground'] = $values['LandArea'];
                //для земельных учатсков определяем их тип
                if(preg_match('/земельные участки/sui',$values['category'])){
                    $this->fields['id_ownership'] = $this->getInfoFromTable($this->sys_tables['ownerships'],$values['ObjectType'],'avitoxml_value',false,'id');
                    if( empty( $this->fields['id_ownership'])) $this->fields['id_ownership'] = $this->getInfoFromTable($this->sys_tables['ownerships'],$values['ObjectType'],'title',false,'id');
                }
            break;
            case 'commercial':
                if(!empty($values['Square'])) $this->fields['square_full'] = $values['Square'];
                if(!empty($values['Floor'])) $this->fields['txt_level'] = (!empty($values['Floors'])?($values['Floor']."/".$values['Floors']):$values['Floor']);
            break;
        }
        if( !empty($values['Latitude'] ) ) $this->fields['lat'] = $values['Latitude'];
        if( !empty($values['Longitude'] ) ) $this->fields['lng'] = $values['Longitude'];
        //общая информация
        if(!empty($values['Description'])){
            $this->fields['notes'] = $values['Description'];
            //обрезка всех ненужных тегов в примечании
            $this->fields['notes'] = str_replace(array('<![CDATA[',']]>'),'',$this->fields['notes']);
            $this->fields['notes'] = strip_tags($this->fields['notes'],"<div><p><a><span><b><strong><u><i><em>");
            $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
        }
        
        //картинки
        if(!empty($values['Images']['Image'])){
            if(count($values['Images']['Image']) == 1){
                if(!empty($values['Images']['Image']['url']) && $this->checkPhoto($values['Images']['Image']['url']) ) $this->fields['images'][] = $values['Images']['Image']['url'];
            }else{
                $count_photos_limit = 0;
                foreach($values['Images']['Image'] as $key=>$img){
                    if(( empty( $photos_limit) || $photos_limit>$count_photos_limit) && !empty($img['url']) && strlen($img['url'])>10 && $this->checkPhoto($img['url']) ){
                        $this->fields['images'][]=$img['url'];
                        ++$count_photos_limit;
                    }
                }
            }
        } elseif( !empty($values['Images']['Image_attr']) && !empty($values['Images']['Image_attr']['url']) && $this->checkPhoto($values['Images']['Image_attr']['url']) ) $this->fields['images'][] = $values['Images']['Image_attr']['url'];
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        
        //координаты широта + долгота
        /*
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                //добавление адреса в таблицу адресов с коорлинатами 
                if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 
        */
        return $this->fields;
    }
}

class BNNEWXmlRobot extends Robot{
    public $file_format = 'bnnewxml';
    public $mapping = array(
                            'xml'         => array('id',          'action',  'metro',     'total',          'living',      'lot',            'kitchen',        'value-rooms',   'address',     'type',           'region',           'price',    'description',  'phone',            'floor', 'deadline',            'building-name',     'house_id',         'heating',      'electricity',          'water',            'action_id',    'kkv',          'igs',          'undist',     'undist_id',      'has_phone',    'has_refrigerator',     'has_furniture',    'has_washing_machine',  'san_id',    'entrance',          'protection','parking','sewerage',    'entry', 'contractor',    'asignment')
                            ,'live'       => array('external_id', 'rent',    'id_subway', 'square_full',    'square_live',  '',              'square_kitchen', 'square_rooms',  'txt_addr',    'id_type_object', 'id_district',      'cost',     'notes',        'seller_phone',     'level',  '',                   '',                  'id_building_type', '',          '',                '',                 'rent',         'rooms_total',  '',             'way_time',   'id_way_type',    'phone',        'refrigerator',         'furniture',        'wash_mash',            'id_toilet', '',                  '',          '',       '',            '',   '')
                            ,'build'      => array('external_id', 'rent',    'id_subway', 'square_full',    'square_live',  '',              'square_kitchen', 'square_rooms',  'txt_addr',    '',               'id_district',      'cost',     'notes',        'seller_phone',     'level', 'id_build_complete',   'id_housing_estate', 'id_building_type', '',          '',                '',                 'rent',         'rooms_total',  '',             'way_time',   'id_way_type',    '',             '',                     '',                 '',                     'id_toilet', '',                  '',          '',       '',            '',              'contractor',    'asignment')
                            ,'country'    => array('external_id', 'rent',    '',          'square_full',    'square_live',  'square_ground', '',               '',              'txt_addr',    'id_type_object', 'id_district_area', 'cost',     'notes',        'seller_phone',     'level',  '',                   '',                  '',                 'id_heating','id_electricity',  'id_water_supply',  'rent',         'rooms',        'id_ownership', 'way_time',   'id_way_type',    '',             '',                     '',                 '',                      '',         '',                  '',          '',       '', '')
                            ,'commercial' => array('external_id', 'rent',    'id_subway', 'square_full',    '',             '',              '',               '',              'txt_addr',    'id_type_object', 'id_district',      'cost',     'notes',        'seller_phone',     'level',  '',                   '',                  '',                 '',          '',                '',                 'rent',         '',             '',             'way_time',   'id_way_type',    '',             '',                     '',                 '',                      '',         'transport_entrance','security',  'parking','canalization','id_enter', '')
    );
    /**
    * обработка полученных из bn.xml значений
    * @return array of arrays
    */
    public function getConvertedFields($values, $agency,$photos_limit = false,$return_deal_type = false){
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;

        foreach($values as $k=>$val) {
            $values[strtolower($k)] = !is_array($val) ? $val : (!empty($val) ? $val : false);
        }
        
        if( empty( $values['type'])){
            $errors_log['estate_type'][$values['id']] = 5;
            return false;
        }
        
        //получение типа недвижимости и типа объекта 
        if(!empty($values['new-building']) && $values['new-building'] == '1'){
            $this->estate_type = 'build';
            $this->fields['id_type_object'] = 1;                            
        } else if( in_array( $values['type'], array( '2 дома','1/2 дома','1/3 дома','1/4 дома','2/3 дома','3/4 дома' ) ) ){
            $this->estate_type = 'country';
            $this->fields['id_type_object'] = 2;                            
        } else $this->fields['id_type_object'] = $this->getEstateType($values['type']);

        $this->fields['rent'] = ( $values['action'] == 'аренда' ? 1 : 2 );
        
        /*
        <country>Россия</country>
        <region>Санкт-Петербург</region>
        <area></area>
        <city></city>
        <district>Московский район</district>
        <place></place>
        <street>Московский проспект</street>
        <house>115</house>
        
        <country>Россия</country>
        <region>Новгородская область</region>
        <area>Новгородский район</area>
        <city></city>
        <district></district>
        <place>Панковка</place>
        <street>Строительная ул</street>
        <house></house>
        */
        
        ///работа с адресом
        $location = $values['location'];
        
        if( ( !empty($location['region']) && preg_match('/етербург/',$location['region'])) ){
            ///объект находится в городе
            $this->fields['id_region'] = 78;
            
            //получаем район города
            //так как все названия районов из одного слова, разбиваем по пробелам и пробуем обе части
            if(!empty($location['district'])){
                $sub_locality_name = explode(' ',$location['district']);
                foreach($sub_locality_name as $key=>$item){
                     $district = $this->getInfoFromTable($this->sys_tables['districts'],$item,'title',false,'id');
                     if(!empty($district)){
                         $this->fields['id_district'] = $district;
                         break;
                     }
                }
            }
            else $district = "";
            
            $this->fields['id_area'] = 0;
            //разбираем адрес
            if( empty( $this->fields['id_street'])){
                $this->fields['txt_addr'] = $location['street'];
                if( empty( $this->fields['id_street'] ) && empty( $this->fields['id_place'] ) ) $this->getGeodataDdata( ( empty( $values['region']) ? "" : $values['region'] . ", " ) . ( empty( $values['district'] ) ? "" : $values['district'] . ", "  ) . ( empty( $values['address'] ) ? "" : $values['address'] ) );
                $this->getTxtGeodata($location['street']);
            }
            $this->fields['txt_addr'] = $location['street'];
            
            if(!empty($location['house'])) $this->fields['house'] = Convert::ToInt($location['house']);
            
            $this->groupByAddress($this->estate_type, $this->fields, true);
            //метро
            if(!empty($location['metro']['name']))$subway = $db->fetch("SELECT id FROM ".$this->sys_tables['subways']." WHERE map_title LIKE ?","%".$location['metro']['name']."%")['id'];
            if(!empty($subway)) $this->fields['id_subway'] = $subway;
            //время до метро
            if(!empty($location['metro']['time-transport'])){
                $this->fields['id_way_type'] = 3;
                $this->fields['way_time'] = Convert::ToInt($location['metro']['time-transport']);
            }
            elseif(!empty($location['metro']['time-foot'])){
                $this->fields['id_way_type'] = 2;
                $this->fields['way_time'] = Convert::ToInt($location['metro']['time-on-foot']);
            }
        }
        elseif(preg_match('/енинградская/',$location['region'])){
            ///объект находится в области
            $this->fields['id_region'] = 47;
            
            //читаем район области
            $this->fields['id_area'] = $db->fetch("SELECT `id_area` FROM ".$this->sys_tables['geodata']." WHERE a_level=2 AND id_region=47 AND ? REGEXP offname",$location['district'])['id_area'];
            if(!empty($values['location']['locality-name'])){
                //читаем город в области
                $this->fields['id_city'] = $db->fetch("SELECT `id_city` 
                                                       FROM ".$this->sys_tables['geodata']." 
                                                       WHERE a_level=3 AND id_region=47 AND ? REGEXP offname",$location['locality-name'])['id_city'];
                //если ничего не нашли, пробуем найти деревню или поселок
                if( empty( $this->fields['id_city']))
                    $this->fields['id_place'] = $db->fetch("SELECT `id_place`
                                                           FROM ".$this->sys_tables['geodata']."
                                                           WHERE a_level=4 AND id_region=47 AND ? REGEXP offname",$location['locality-name'])['id_place'];
            }
            
            //разбираем адрес
            //теперь если текстовый адрес пуст, подставляем туда при наличии название поселка и деревни
            if(!empty($location['address'])){
                $this->fields['txt_addr'] = $location['address'];
                $this->getTxtGeodata($location['address']);
                $this->fields['txt_addr'] = $location['address'];
            }elseif(!empty($location['locality-name'])) $this->fields['txt_addr'] = $location['locality-name'];
            
            $this->groupByAddress($this->estate_type, $this->fields, true);
            //для загородной - ближайшая ж/д станция
            if($this->estate_type == 'country' && !empty($location['railway-station'])) $this->fields['railstation'] = $location['railway-station']['name'];
        }
        //широта
        if(!empty($values['location']['latitude'])) $this->fields['lat'] = Convert::ToValue($values['location']['latitude']);
        //долгота
        if(!empty($values['location']['longitude'])) $this->fields['lng'] = Convert::ToValue($values['location']['longitude']);
        
        ///информация о сделке
        $price = $values['price'];
        if(!empty($price)){
            if(!empty($price['currency']) && $price['currency'] != 'RUR' && $price['currency'] != 'RUB'){
                $errors_log['moderation'][$values['id']][] = 'Обрабатываются только цены, указанные в рублях.';
            }
            //если цена указана за единицу площади, запишем ее позже вместе с площадью
            if( empty( $price['unit'])) $this->fields['cost'] = (!empty($price['value']))?$price['value']:0;
            else{
                switch(true){
                    case ($price['unit']== 'м' || $price['unit'] == 'м2'): $this->fields['cost2meter'] = $price['value'];break;
                    case ($price['unit']== 'гектар' || $price['unit'] == 'га'): $from_hectars_multiplyier = true;break;
                    //цены за сотку пока не обрабатываются
                }
            }
            
            if( !empty($price['period']) && ($price['period'] == 'день' || $price['period'] == 'сутки') ) $this->fields['by_the_day'] = 1;
        } else $errors_log['moderation'][$values['id']][] = 'Не указана цена.';
        
        
        //поля ипотека и кредит пока не обрабатываются
        
        ///продавец
        $agent = $values['agent'];
        if(!empty($agent)){
            if(!empty($agent['phone'])) $this->fields['seller_phone'] = (is_array($agent['phone'])?$agent['phone'][0]:$agent['phone']);
            if(!empty($agent['email']) && Validate::isEmail($agent['email']))  $this->fields['seller_email'] = trim($agent['email']);
            if(!empty($agent['name'])) $this->fields['seller_name'] = $agent['name'];
            elseif(!empty($agent['firmname'])) $this->fields['seller_name'] = $agent['organization'];
        }
        
        ///описание
        $description = !empty($values['description']) ? $values['description'] : false;
        if(!empty($description)){
            if(!empty($description['full'])) $this->fields['notes'] = $description['full'];
            elseif(!empty($description['print'])) $this->fields['notes'] = $description['print'];
            elseif(!empty($description['short'])) $this->fields['notes'] = $description['short'];
            //сразу вырезаем лишнее
            if(!empty($this->fields['notes'])){
                $this->fields['notes'] = str_replace(array('<![CDATA[',']]>'),'',$this->fields['notes']);
                $this->fields['notes'] = strip_tags($this->fields['notes'],"<div><p><a><span><b><strong><u><i><em>");
                $this->fields['notes'] = Validate::stripEmail(Validate::stripPhone($this->fields['notes']));
            }
        }
        
        ///информация по зданию
        $building = $values['building'];
        if(!empty($building)){
            //ЖК
            if(!empty($building['name']) && $this->estate_type == 'build' || $this->estate_type == 'live') $he_id = $this->getComplexId(1,false,$values['build_complex_title']);
            if(!empty($he_id)){
                $this->fields['id_housing_estate'] = $he_id;
                $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$this->fields['id_housing_estate']);
                if(!empty($complex_info)){
                    $this->fields['lat'] = $complex_info['lat'];
                    $this->fields['lng'] = $complex_info['lng'];
                }
            }
            
            //год постройки дома пока только для стройки
            if($this->estate_type == 'build'){
                $building['quarter'] = Convert::ToInteger($building['quarter']);
                $building['year'] = Convert::ToInteger($building['year']);
                if($building['year']<2000 && $building['year']<100) $building['year'] += 2000;
                if(!empty($building['quarter'])){
                    $deadline_res = $db->fetch("SELECT `id` FROM ".$this->sys_tables['build_complete']." WHERE `year`=? AND `decade`=?",$year,$decade);
                    if(!empty($deadline_res)) $this->fields['id_build_complete'] =  $deadline_res['id'];
                } else $this->fields['id_build_complete']=0;
            }
            if(!empty($building['status']))
                if(strstr('сдан',$building['status']) > -1) $this->fields['id_build_complete'] = $db->fetch("SELECT `id` FROM ".$this->sys_tables['build_complete']." WHERE title = 'сдан'")['id'];
                elseif(strstr('госком',$building['status']) > -1) $this->fields['id_build_complete'] = $db->fetch("SELECT `id` FROM ".$this->sys_tables['build_complete']." WHERE title LIKE '%госком%'")['id'];
            //тип дома
            if(!empty($building['type'])){
                $b_type = $building['type'];
                switch(true){
                    case (preg_match('/(старый).*(фонд)/sui',$b_type)): $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],"Старый фонд",'title',false,'id');break;
                    case (preg_match('/(кирпич)(?!(.*монолит))/sui',$b_type)): $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],"Кирпичный",'title',false,'id');break;
                    case (preg_match('/(монолит)/sui',$b_type)): $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],"Монолитный",'title',false,'id');break;
                    case (preg_match('/(панель)/sui',$b_type)): $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],"Панельный",'title',false,'id');break;
                    case (!empty($building['series']) && (preg_match('/(К\/М)|((кирп).*(монол))/sui',$building['series'])) ): $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],"Кирпично-монолитный",'title',false,'id'); break;
                }
            }
            
        }
        
        //информация по объекту
        switch($this->estate_type){
            case 'live':
                $total = $values['total'];
                //если цена не подсчитана, рассчитываем ее по площади
                if(!empty($total)){
                    if(!empty($total['value'])){
                        if( empty( $this->fields['cost'])){
                            $this->fields['cost'] = $this->fields['cost2meter'] * $total['value'];
                        } 
                        $this->fields['square_full'] = $total['value'];
                    }
                }

                $living = $values['living'];
                if(!empty($living)){
                    if(!empty($living['value'])) $this->fields['square_live'] = $living['value'];
                    if(!empty($living['value-rooms'])) $this->fields['square_rooms'] = $living['value'];
                }
                if(!empty($values['living']) && !empty($values['living']['value'])) $this->fields['square_live'] = $values['living']['value'];

                $kitchen = $values['kitchen'];
                if(!empty($kitchen)){
                    if(!empty($kitchen['value'])) $this->fields['square_live'] = $kitchen['value'];
                    if(!empty($kitchen['value-rooms'])) $this->fields['square_rooms'] = $kitchen['value'];
                }
                if(!empty($values['kitchen']) && !empty($values['kitchen']['value'])) $this->fields['square_live'] = $values['kitchen']['value'];

                if(!empty($values['balcony'])){
                    $b_type = $values['balcony'];
                    switch(true){
                        case (preg_match('/(2 балкона)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"2 балкона",'title',false,'id');break;
                        case (preg_match('/(3 балкона)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"3 балкона",'title',false,'id');break;
                        case (preg_match('/(2 лоджии)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"2 лоджии",'title',false,'id');break;
                        case (preg_match('/(балкон).*(лоджи)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"балкон+лоджия",'title',false,'id');break;
                        case (preg_match('/(балкон)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"балкон",'title',false,'id');break;
                        case (preg_match('/(лоджия)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"лоджия",'title',false,'id');break;
                    }
                }
                if(!empty($values['is-elite']) && $values['is-elite'] = 1) $this->fields['elite'] = 1;
                if(!empty($values['rooms-total'])){
                    if($this->estate_type == 'build') $this->fields['rooms_sale'] = $values['rooms-total'];
                    $this->fields['rooms-total'] = $values['rooms-total'];
                }
                if($this->estate_type != 'build' && !empty($values['rooms-offer'])) $this->fields['rooms_sale'] = $values['rooms-offer'];
                //количество съемщиков у нас не учитывается - holders
                if(!empty($values['neighbourhoods'])) $this->fields['neighbors'] = $values['neighbourhoods'];
                if(!empty($values['phone'])) $this->fields['phone'] = 1;
                //поле интернет пока не заполняется
                if(!empty($values['floor'])) $this->fields['level'] = $values['floor'];
                if(!empty($values['floors'])) $this->fields['level_total'] = $values['floors'];
                if(!empty($values['furniture'])) $this->fields['furniture'] = 1;
                if(!empty($values['refrigerator'])) $this->fields['refrigerator'] = 1;
                if(!empty($values['bathroom'])){
                    $b_type = $values['bathroom'];
                    switch(true){
                        case (preg_match('/(Б\/В)|(без ванны)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"нет ванной",'title',false,'id');break;
                        case (preg_match('/(В\/К)|(ванна на кухне)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"ванна на кухне",'title',false,'id');break;
                        case (preg_match('/(Д\/К)|(душ на кухне)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"душ на кухне",'title',false,'id');break;
                        case (preg_match('/(Д)|(душ)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"душ",'title',false,'id');break;
                        case (preg_match('/(Р)|(раздельный санузел)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"раздельный",'title',false,'id');break;
                        case (preg_match('/(2)|(2 санузла)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"2 санузла",'title',false,'id');break;
                        case (preg_match('/(3)|(3 санузла)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"3 санузла",'title',false,'id');break;
                    }
                }
                if(!empty($values['washing-machine'])) $this->fields['washing-machine'] = 1;
                if(!empty($values['quality'])){
                    $b_type = $values['quality'];
                    switch(true){
                        case (preg_match('/(хорош)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['facings'],"хороший",'title',false,'id');break;
                        case (preg_match('/(треб).*(рем)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['facings'],"требуется ремонт",'title',false,'id');break;
                        case (preg_match('/(удовл)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['facings'],"обычный",'title',false,'id');break;
                        case (preg_match('/(косметич)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['facings'],"косметический",'title',false,'id');break;
                        case (preg_match('/(евро)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['facings'],"евро",'title',false,'id');break;
                        case (preg_match('/(б\о)|(без)/sui',$b_type)): $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['facings'],"евро",'title',false,'id');break;
                    }
                }
                break;
            case 'country':
                //площадь участка 
                $lot = $values['lot'];
                if(!empty($lot)){
                    $this->fields['square_ground'] = $lot;
                    
                    //если это участок и цена не подсчитана, рассчитываем ее по площади
                    if($this->fields['id_type_object'] == '13' && empty($this->fields['cost'])){
                        if(strstr('сот',$lot['unit']) >= 0 && strstr('сот',$values['price']['unit'])) $this->fields['cost'] = $lot['value'] * $values['price']['value'];
                    }
                }
                if(!empty($values['lot-status'])) $this->fields['id_ownership'] = $this->getInfoFromTable($this->sys_tables['ownerships'],$values['lot-status'],'bntxt_value',false,'id');
                if(!empty($values['countryside-type'])) $this->fields['id_ownership'] = $this->getInfoFromTable($this->sys_tables['construct_materials'],$values['countryside-type'],'bntxt_value',false,'id');
                break;
            case 'commercial':
                $total = $values['total'];
                
                //если цена не подсчитана, рассчитываем ее по площади
                if(!empty($total)){
                    if(!empty($total['value'])){
                        if( empty( $this->fields['cost'])){
                            $this->fields['cost'] = $this->fields['cost2meter'] * $total['value'];
                        } 
                        $this->fields['square_full'] = $total['value'];
                    }
                }
                
                if(!empty($values['ceiling-height'])) $this->fields['ceiling_height'] = $values['ceiling-height'];
                if(!empty($values['entrance'])) $this->fields['transport_entrance'] = $values['entrance'];
                if(!empty($values['entry'])) $this->fields['id_enter'] = $this->getInfoFromTable($this->sys_tables['enters'],$values['entry'],'bnxml_value',false,'id');
                if(!empty($values['parking'])) $this->fields['parking'] = $values['parking'];
                if(!empty($values['protection'])) $this->fields['security'] = $values['protection'];
                if(!empty($values['heating'])) $this->fields['heating'] = $values['heating'];
                if(!empty($values['sewerage'])) $this->fields['canalization'] = $values['sewerage'];
                if(!empty($values['electricity'])) $this->fields['electricity'] = $values['electricity'];
                break;
        }
                                                                    
        ///картинки
        $images = $values['files'];
        if(!empty($images['image'])){
            $image = $images['image'];
            $count_photos_limit = 0;
            if(!is_array($image)) $image = array($image);
            foreach($image as $key=>$img){
                if( empty( $photos_limit) || $photos_limit>$count_photos_limit && !empty($img) && strlen($img)>10 && $this->checkPhoto($img) ){
                    $this->fields['images'][]=preg_replace("/\?[0-9]+/",'',$img);
                    ++$count_photos_limit;
                }
            }
        }
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                //добавление адреса в таблицу адресов с коорлинатами 
                if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
            }
        }        
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 
        
        
        return $this->fields;        
    }
}

class CianXmlRobot extends Robot{
    public $file_format = 'cianxml';
    private $multi_roomed_flat_alias = "99";
    public $mapping = array(
                             'xml'         => array('id',         'action',  'metro',     'total',          'living',      'lot',            'kitchen',        'value-rooms',   'address',     'type',           'region',           'price',    'description',  'phone',            'floor', 'deadline',            'building-name',     'house_id',         'heating',      'electricity',          'water',            'action_id',    'kkv',          'igs',          'undist',     'undist_id',      'has_phone',    'has_refrigerator',     'has_furniture',    'has_washing_machine',  'san_id',    'entrance',          'protection','parking','sewerage',    'entry', 'contractor', 'asignment')
                            ,'live'       => array('external_id', 'rent',    'id_subway', 'square_full',    'square_live',  '',              'square_kitchen', 'square_rooms',  'txt_addr',    'id_type_object', 'id_district',      'cost',     'notes',        'seller_phone',     'level',  '',                   '',                  'id_building_type', '',          '',                '',                 'rent',         'rooms_total',  '',             'way_time',   'id_way_type',    'phone',        'refrigerator',         'furniture',        'wash_mash',            'id_toilet', '',                  '',          '',       '',            '',       '')
                            ,'build'      => array('external_id', 'rent',    'id_subway', 'square_full',    'square_live',  '',              'square_kitchen', 'square_rooms',  'txt_addr',    '',               'id_district',      'cost',     'notes',        'seller_phone',     'level', 'id_build_complete',   'id_housing_estate', 'id_building_type', '',          '',                '',                 'rent',         'rooms_total',  '',             'way_time',   'id_way_type',    '',             '',                     '',                 '',                     'id_toilet', '',                  '',          '',       '',            '',               'contractor', 'asignment')
                            ,'country'    => array('external_id', 'rent',    '',          'square_full',    'square_live',  'square_ground', '',               '',              'txt_addr',    'id_type_object', 'id_district_area', 'cost',     'notes',        'seller_phone',     'level',  '',                   '',                  '',                 'id_heating','id_electricity',  'id_water_supply',  'rent',         'rooms',        'id_ownership', 'way_time',   'id_way_type',    '',             '',                     '',                 '',                      '',         '',                  '',          '',       '', '')
                            ,'commercial' => array('external_id', 'rent',    'id_subway', 'square_full',    '',             '',              '',               '',              'txt_addr',    'id_type_object', 'id_district',      'cost',     'notes',        'seller_phone',     'level',  '',                   '',                  '',                 '',          '',                '',                 'rent',         '',             '',             'way_time',   'id_way_type',    '',             '',                     '',                 '',                      '',         'transport_entrance','security',  'parking','canalization','id_enter', '')
    );
    /**
    * обработка полученных из cian.xml значений
    * @return array of arrays
    */
    public function getConvertedFields($values, $agency,$photos_limit = false,$return_deal_type = false){
        foreach($values as $k=>$val) {
            $values[strtolower($k)] = !is_array($val) ? $val : (!empty($val) ? $val : false);
        }
        
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;
        if( empty( $values['type'])){
            $errors_log['estate_type'][$values['id']] = 5;
            return false;
        }
        $values['id'] = preg_replace("|\D|", "", $values['id'] );
        if( empty( $values['id'] ) ){
            $errors_log['external_id'][] = $values['id'];
            return false;
        }
        //получение типа недвижимости, типа сделки
        switch(true){
            case $values['type'] == 'flats_rent':
                $this->estate_type = 'live';
                $this->fields['rent'] = 1;
                break;
            case $values['type'] == 'flats_for_sale':
                if( empty( $values['options']['attr']['object_type']) || $values['options']['attr']['object_type'] != 2) $this->estate_type = 'live';
                else $this->estate_type = 'build';
                $this->fields['rent'] = 2;
                break;
            case $values['type'] == 'commerce':
                $this->estate_type = 'commercial';
                if(in_array($values['contract_type'],array(1,2,3))){
                    $this->fields['rent'] = 1;
                    switch($values['contract_type']){
                        case 1: $this->fields['special_notes']['deal_type'] = 'прямая аренда';break;
                        case 2: $this->fields['special_notes']['deal_type'] = 'субаренда';break;
                        case 3: $this->fields['special_notes']['deal_type'] = 'продажа права аренды (ППА)';break;
                    }
                } 
                elseif(in_array($values['contract_type'],array(4))) $this->fields['rent'] = 2;
                break;
            case $values['type'] == 'suburbian':
                $this->estate_type = 'country';
                $this->fields['rent'] = ($values['deal_type'] == 'r' || $values['deal_type'] == 'R'?1:2);
                if(!empty($values['realty_type'])) $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_country'],$values['realty_type'],'cianxml_value',false,'id');
                break;
        }
        //если почему-то в стройке аренда, ставим продажу
        if($this->estate_type == 'build') $this->fields['rent'] = 2;
        //только тип недвижимости + сделка
        if(!empty($return_deal_type)) return $this->fields;
        
        //поле для данных, к которым нет полей
        if( empty( $this->fields['special_notes'])) $this->fields['special_notes'] = [];
        
        /////////////////
        //общие для всех данные по стоимости и местоположению
        //стоимость, принимаем только в рублях
        if(!empty($values['price'])){
            $this->fields['cost'] = $values['price'];
            $price_attr = $values['price_attr'];
            if(!empty($price_attr)){
                if(( empty( $price_attr['currency']) || $price_attr['currency'] == 'RUB')){
                    //<price for_day="0" prepay="2" deposit="0" currency="USD">3000</price>
                    $this->fields['by_the_day'] = ($price_attr['for_day'] == 1?1:2);
                    //если цена указана за м2 в год, рассчитываем за месяц - для коммерческой
                    if(!empty($price_attr['period']) && $price_attr['period'] == 'year'){
                        $this->fields['cost'] = $values['area_attr']['total']*$values['price']/12.0;
                    }
                    if($this->estate_type == 'commercial') $this->fields['special_notes']['rent_duration'] = ($price_attr['for_day'] == 0?"на длительный срок":"на срок до 11 месяцев");
                    else $this->fields['special_notes']['rent_duration'] = ($price_attr['for_day'] == 0?"на срок до 11 месяцев":"на длительный срок");
                    
                    $this->fields['special_notes']['rent_prepay_value'] = ( empty( $price_attr['prepay'])?"без предоплаты":"с предоплатой ".$price_attr['prepay']);
                    $this->fields['special_notes']['rent_has_deposit'] = (!empty($price_attr['deposit'])?"со страховым депозитом":"");
                }else $this->fields['cost'] = 0;
            }
            unset($price_attr);
        }
        //адрес
        if(!empty($values['address_attr'])){
            $address_attr = $values['address_attr'];
            if($address_attr['admin_area'] == 10) $this->fields['id_region'] = 78;
            elseif($address_attr['admin_area'] == 11) $this->fields['id_region'] = 47;
            
            $addr = (!empty($address_attr['locality'])?$address_attr['locality'].",":"").$address_attr['street'].", ".$address_attr['house_str'];
            
            $this->fields['addr_source'] = "<admin_area>".$address_attr['admin_area']."</admin_area><locality>".$address_attr['locality']."</locality><street>".$address_attr['street']."</street><house_str>".$address_attr['house_str']."</house_str>";
            
            $this->getTxtGeodata($addr);
            
            if(!empty($address_attr['name_corp'])) $this->fields['id_housing_estate']  = $this->getComplexId(1,false,$address_attr['name_corp']);
            if(!empty($this->fields['id_housing_estates'])){
                $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$this->fields['id_housing_estate']);
                if(!empty($complex_info)){
                    $this->fields['lat'] = $complex_info['lat'];
                    $this->fields['lng'] = $complex_info['lng'];
                }
            }
            
            
            //КП для загородной
            if(!empty($address_attr['name_pos'])) $this->fields['id_cottage'] = $this->getComplexId(2,false,$address_attr['name_pos']);
            
            //при отсутствии полей, утсанавливаем в 0, чтобы они затерлись
            if( empty( $this->fields['id_street'])) $this->fields['id_street'] = 0;
            if( empty( $this->fields['id_city'])) $this->fields['id_city'] = 0;
            if( empty( $this->fields['id_place'])) $this->fields['id_place'] = 0;
            if( empty( $this->fields['id_district'])) $this->fields['id_district'] = 0;
            if( empty( $this->fields['id_area'])) $this->fields['id_area'] = 0;
            if( empty( $this->fields['house'])) $this->fields['house'] = 0;
            if( empty( $this->fields['corp'])) $this->fields['corp'] = 0;
            
            //группировка объектов по адресу
            $this->groupByAddress($this->estate_type, $this->fields, true);
            
            unset($address_attr);
        }
        //метро
        if(!empty($values['metro_attr'])){
            $metro_attr = $values['metro_attr'];
            
            $this->fields['id_subway'] = ((!empty($metro_attr['id']))?$this->getInfoFromTable($this->sys_tables['subways'],$metro_attr['id'],'cianxml_value',false,'id'):0) ;
            
            if(!empty($metro_attr['wtime'])){
                $this->fields['id_way_type'] = $this->getInfoFromTable($this->sys_tables['way_types'],"минут пешком",'title',false,'id');
                $this->fields['way_time'] = $metro_attr['wtime'];
            }elseif(!empty($metro_attr['ttime'])){
                $this->fields['id_way_type'] = $this->getInfoFromTable($this->sys_tables['way_types'],"минут на транспорте",'title',false,'id');
                $this->fields['way_time'] = $metro_attr['ttime'];
            }
        }
        //примечания
        $this->fields['notes'] = (!empty($values['note'])?$values['note']:"");
        //картинки
        if(!empty($values['photo'])){
            $count_photos_limit = 0;
            if(!is_array($values['photo'])) $values['photo'] = array($values['photo']);
            foreach($values['photo'] as $key=>$img){
                if( empty( $photos_limit) || $photos_limit>$count_photos_limit && !empty($img) && strlen($img)>10 && $this->checkPhoto($img) ){
                    //убираем ?[0-9]+ справа от расширения. оно не дает сохранить файл
                    $this->fields['images'][] = preg_replace("/\?[0-9]+/",'',$img);
                    ++$count_photos_limit;
                }
            }
        }
        //выделение
        if(!empty($values['promotions'])){
            switch($values['promotions']){
                case 'premium': $this->getStatus(4);break;
                case 'highlight': $this->getStatus(3);break;
                case 'top': $this->getStatus(6);break;
            }
        }
        //id
        $this->fields['external_id'] = $values['id'];
        //может быть указано два телефона, берем первый
        if(!empty($values['phone'])){
            if(preg_match('/\;/',$values['phone'])) $values['phone'] = explode(';',$values['phone'])[0];
            $this->fields['seller_phone'] = $values['phone'];
        }
        /////////////////
        
        /////////////////
        //данные о площадях, доме
        switch($values['type']){
            case 'flats_for_sale':
            case 'flats_rent':
                //количество комнат и разбиение квартира/комната
                switch(true){
                    //комната - м.б. только одна (нету 2 комнаты в 3комнатной квартире)
                    case $values['rooms_num'] == 0:
                        $this->fields['id_type_object'] = 2;
                        $this->fields['rooms_sale'] = 1;
                        $this->fields['rooms_total'] = $this->multi_roomed_flat_alias;
                        break;
                    //1-5 комнатная квартира
                    case ($values['rooms_num'] > 0 && $values['rooms_num'] < 6):
                        $this->fields['id_type_object'] = 1;
                        $this->fields['rooms_sale'] = $values['rooms_num'];
                        $this->fields['rooms_total'] = $values['rooms_num'];
                        break;
                    //многокомнатная квартира?
                    case ($values['rooms_num'] == 6):
                        $this->fields['id_type_object'] = 1;
                        $this->fields['rooms_total'] = $this->multi_roomed_flat_alias;
                        break;
                    //студия
                    case ($values['rooms_num'] == 9):
                        $this->fields['id_type_object'] = 1;
                        $this->fields['rooms_total'] = 0;
                        break;
                    //койко-места, свободная планировка и доли в квартире у нас нет
                }
                //площади
                if(!empty($values['area_attr'])){
                    $area_attr = $values['area_attr'];
                    if(!empty($area_attr['rooms'])) $this->fields['square_rooms'] = preg_replace('/[^0-9\,\.]+/','+',trim($area_attr['rooms']));
                    $this->fields['square_live'] = ( empty( $area_attr['living'])?0:$area_attr['living']);
                    $this->fields['square_kitchen'] = ( empty( $area_attr['living'])?0:$area_attr['kitchen']);
                    $this->fields['square_full'] = ( empty( $area_attr['total'])?0:$area_attr['total']);
                    unset($area_attr);
                }
                //этаж, информация о доме
                if(!empty($values['floor'])){
                    $this->fields['level'] = $values['floor'];
                    $floor_attr = $values['floor_attr'];
                    if(!empty($floor_attr)){
                        $this->fields['is_apartments'] = ($floor_attr['apart'] == 'yes' ? 1 : 2);
                        $this->fields['is_penthouse'] = ($floor_attr['pent'] == 'yes' ? 1 : 2);
                        $this->fields['level_total'] = $floor_attr['total'];
                        $this->fields['id_building_type'] = $this->getInfoFromTable($this->sys_tables['building_types'],$floor_attr['type'],'cianxml_value',false,'id');
                        //если тип есть, но его нет у нас, пишем в special_notes
                        if( empty( $this->fields['id_building_type']) && !empty($floor_attr['type'])){
                            switch($floor_attr['type']){
                                case 5: $this->fields['special_notes']['building_type'] = "блочный";break;
                                case 6: $this->fields['special_notes']['building_type'] = "деревянный";break;
                                case 7: $this->fields['special_notes']['building_type'] = "сталинский";break;
                            }
                        }
                        if(!empty($floor_attr['seria'])) $this->fields['special_notes']['building_serial'] = $floor_attr['seria'];
                        if(!empty($floor_attr['ceiling'])) $this->fields['ceiling_height'] = $floor_attr['ceiling'];
                        if(!empty($floor_attr['pent']) && $floor_attr['pent'] == 'yes') $this->fields['special_notes']['building_pent'] = "пентхаус";
                        if(!empty($floor_attr['apart']) && $floor_attr['apart'] == 'yes') $this->fields['special_notes']['building_apart'] = "апартаменты";
                    }
                    unset($floor_attr);
                }
                
                //special_offer_id - id акции, не берем
                
                break;
            case 'commerce':
                //тип объекта
                if(!empty($values['commerce_type'])){
                    $this->fields['id_type_object'] = $this->getInfoFromTable($this->sys_tables['type_objects_commercial'],$values['commerce_type'],'cianxml_value',false,'id');
                    switch($values['commerce_type']){
                        case 'W': $this->fields['special_notes']['object_type'] = "склад";break;
                        case 'F': $this->fields['special_notes']['object_type'] = "для общепита";break;
                        case 'G': $this->fields['special_notes']['object_type'] = "гараж";break;
                        case 'AU': $this->fields['special_notes']['object_type'] = "автосервис";break;
                        case 'WP': $this->fields['special_notes']['object_type'] = "производственное помещение";break;
                        case 'BU': $this->fields['special_notes']['object_type'] = "под бытовые услуги (салон красоты и т.д.)";break;
                        case 'UA': $this->fields['special_notes']['object_type'] = "юридический адрес";break;//пихнется в нежилой фонд, если в нежилом доме
                        case 'SB': $this->fields['special_notes']['object_type'] = "продажа бизнеса";break;//пихнется в нежилой фонж, если в нежилом доме
                    }
                    //если отдельно отмечено что нежилое и тип неопрделен, ставим нежилой фонд
                    if(!empty($values['building_attr']['type']) && $values['building_attr']['type'] == 1) $this->fields['id_type_object'] = 26;
                }
                //площади
                if(!empty($values['area_attr'])){
                    $area_attr = $values['area_attr'];
                    if(!empty($area_attr['total'])) $this->fields['square_full'] = ( empty( $area_attr['total'])?0:$area_attr['total']);
                    //комнаты пишем в комментарий
                    if(!empty($area_attr['rooms_count'])) $this->fields['special_notes']['object_rooms'] = $area_attr['rooms_count'];
                    if(!empty($area_attr['rooms'])) $this->fields['special_notes']['object_rooms_square'] = "площадь внутренних помещений ".$area_attr['rooms_'];
                    if(!empty($area_attr['min'])) $this->fields['special_notes']['object_min_rent'] = "минимальная арендуемая площадь ".$area_attr['min']."м2";
                    unset($area_attr);
                }
                
                //информация о здании
                if(!empty($values['building_attr'])){
                    $building_attr = $values['building_attr'];
                    
                    if(!empty($building_attr['floor'])) $this->fields['level'] = $building_attr['floor'];
                    if(!empty($building_attr['floor_total'])) $this->fields['level_total'] = $building_attr['floor_total'];
                    if(!empty($building_attr['enter']) && $building_attr['enter'] == 2) $this->fields['id_enter'] = 7;
                    if(!empty($building_attr['name_bc'])) $this->fields['id_busingess_center'] = $this->getComplexId(3,false,$building_attr['name_bc']);
                    if(!empty($this->fields['id_business_center'])){
                        $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['business_centers']." WHERE id = ?",$this->fields['id_busingess_center']);
                        if(!empty($complex_info)){
                            $this->fields['lat'] = $complex_info['lat'];
                            $this->fields['lng'] = $complex_info['lng'];
                        }
                    }
                    if(!empty($building_attr['status_b'])){
                        switch($building_attr['status_b']){
                            case 1: $this->fields['special_notes']['building_status'] = "удовлетворительное";break;
                            case 2: $this->fields['special_notes']['building_status'] = "хорошее";break;
                            case 3: $this->fields['special_notes']['building_status'] = "отличное";break;
                        }
                    }
                    //класс строения не пишем, он в БЦ
                    //площадь здания и тип жилого дома не пишем
                    if(!empty($building_attr['ceiling'])) $this->fields['ceiling_height'] = $building_attr['ceiling'];
                    unset($building_attr);
                }
                break;
            case 'suburbian':
                if(!empty($values['floor'])) $this->fields['level'] = $values['floor'];
                if(!empty($values['area_attr'])){
                    $area_attr = $values['area_attr'];
                    if(!empty($area_attr['region'])) $this->fields['square_ground'] = $area_attr['region'];
                    if(!empty($area_attr['living'])) $this->fields['square_live'] = $area_attr['living'];
                    unset($area_attr);
                }
                if(!empty($values['land_type'])) $this->fields['id_ownership'] = $this->getInfoFromTable($this->sys_tables['ownerships'],$values['land_type'],'cianxml_value',false,'id');
                
                break;
        }
        /////////////////
        
        //дочитываем опции
        switch($values['type']){
            case 'flats_rent':
                if(!empty($values['options_attr'])){
                    $options_attr = $values['options_attr'];
                    //телефон
                    if(!empty($options_attr['phone'])){
                        if($options_attr['phone'] == "yes") $this->fields['phone'] = 1;
                        else $this->fields['phone'] = 2;
                    }
                    //кухонная мебель
                    if(!empty($options_attr['mebel_kitchen']) && $options_attr['mebel_kitchen'] == "yes") $this->fields['special_notes']['kitchen_furniture'];
                    //мебель
                    if(!empty($options_attr['mebel'])){
                        if($options_attr['mebel'] == "no") $this->fields['furniture'] = 2;
                        else $this->fields['furniture'] = 1;
                    }
                    //балкон
                    if(!empty($options_attr['balcon'])){
                        if($options_attr['balcon'] == "yes"){
                            $this->fields['id_balcon'] = 2;
                            switch($options_attr['balcon']){
                                case 2:$this->fields['id_balcon'] = 4;break;
                                case 3:$this->fields['id_balcon'] = 13;break;
                            }
                        }
                        else $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"нет",'title',false,'id');
                    }
                    //лоджия (x лоджий + y балконов считаем за "балкон + лоджия")
                    if(!empty($options_attr['lodgia'])){
                        if($options_attr['lodgia'] == "yes"){
                            if(!empty($options['balkon'])) $this->fields['id_balkon'] = 6;
                            else{
                                $this->fields['id_balcon'] = 3;
                                if(!empty($values['balkon']) && $options['balkon'] == 2) $this->fields['id_balcon'] = 5;break;
                            }
                        }
                        elseif( empty( $this->fields['id_balcon'])) $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"нет",'title',false,'id');
                    }
                    
                    //холодильник
                    if(!empty($options_attr['rfgr'])){
                        if($options_attr['rfgr'] == "no") $this->fields['refrigerator'] = 2;
                        else $this->fields['refrigerator'] = 1;
                    }
                    
                    //посудомоечная машина
                    if(!empty($options_attr['dishwasher']) && $options_attr['dishwasher'] == "yes") $this->fields['special_notes']['kitchen_dishwasher'] = "посудомоечная машина";
                    
                    //посудомоечная машина
                    if(!empty($options_attr['conditioner']) && $options_attr['conditioner'] == "yes") $this->fields['special_notes']['flat_condition'] = "кондиционер";
                    
                    //интернет
                    if(!empty($options_attr['internet']) && $options_attr['internet'] == "yes") $this->fields['special_notes']['flat_internet'] = "интернет";
                    
                    //телевизор
                    if(!empty($options_attr['tv']) && $options_attr['tv'] == "yes") $this->fields['special_notes']['flat_tv'] = "телевизор";
                    
                    //душ
                    if(!empty($options_attr['shower']) && $options_attr['shower'] == "yes") $this->fields['id_toilet'] = 6;
                    
                    //ванная
                    if(!empty($options_attr['bath']) && $options_attr['bath'] == "yes"){
                        if(!empty($this->fields['id_toilet'])) $this->fields['special_notes']['bathroom_shower'] = "душ";
                        $this->fields['id_toilet'] = 2;
                    }
                    
                    //мусоропровод
                    if(!empty($options_attr['chute']) && $options_attr['chute'] == "yes") $this->fields['special_notes']['house_chute'] = "мусоропровод";
                    
                    //совмещенные санузлы
                    if(!empty($options_attr['su_s'])){
                        if($options_attr['su_s'] == 2){
                            $this->fields['special_notes']['bathroom_toilet'] = "совмещенный";
                            $this->fields['id_toilet'] = 5;
                        }
                        elseif($options_attr['su_s'] == 3){
                            $this->fields['special_notes']['bathroom_toilet'] = "совмещенный";
                            $this->fields['id_toilet'] = 10;
                        } 
                        else $this->fields['id_toilet'] = 3;
                    }
                    
                    //совмещенные санузлы
                    if(!empty($options_attr['su_r'])){
                        if($options_attr['su_r'] == 2){
                            $this->fields['special_notes']['bathroom_toilet'] = "раздельный";
                            $this->fields['id_toilet'] = 5;
                        }
                        elseif($options_attr['su_r'] == 3){
                            $this->fields['special_notes']['bathroom_toilet'] = "раздельный";
                            $this->fields['id_toilet'] = 10;
                        }
                        else $this->fields['id_toilet'] = 3;
                    }
                    
                    //пассажирские лифты
                    if(!empty($options_attr['lift_p'])){
                        if($options_attr['lift_p'] > 1) $this->fields['id_elevator'] = 3;
                        else $this->fields['id_elevator'] = 2;
                    }
                    
                    //грузовые лифты - пишем только если пассажирский тоже есть
                    if(!empty($options_attr['lift_g'])){
                        if(!empty($this->fields['id_elevator'])) $this->fields['id_elevator'] = 4;
                    }
                    
                    
                    //окна
                    if(!empty($options_attr['windows'])){
                        $this->fields['id_window'] = $this->getInfoFromTable($this->sys_tables['windows'],$options_attr['windows'],'cianxml_value',false,'id');
                    }
                    
                    //ремонт
                    if(!empty($options_attr['repair'])){
                        $this->fields['id_facing'] = $this->getInfoFromTable($this->sys_tables['facings'],$options_attr['facings'],'cianxml_value',false,'id');
                        if($options_attr['repair'] == 3) $this->fields['special_notes']['flat_facing'] = "дизайнерский";
                    }
                    
                    //возьмут с детьми
                    if(!empty($options_attr['kids'])) $this->fields['special_notes']['flat_kids'] = ($options_attr['chute'] == "yes"?"":"не ")."возьмут с детьми";
                        
                    //возьмут с животными
                    if(!empty($options_attr['pets'])) $this->fields['special_notes']['flat_pets'] = ($options_attr['pets'] == "yes"?"":"не ")."возьмут с животными";
                    
                    unset($options_attr);
                }
                
                //состав проживающих
                if(!empty($value['composition'])){
                    switch($values['composition']){
                        case 2: $this->fields['special_notes']['flat_composition'] = " семье";break;
                        case 3: $this->fields['special_notes']['flat_composition'] = " женщине";break;
                        case 4: $this->fields['special_notes']['flat_composition'] = " мужчине";break;
                    }
                }
                
                //комиссия
                if(!empty($values['com_attr'])){
                    $com_attr = $values['comn_attr'];
                    if(!empty($com_attr['agent'])) $this->fields['special_notes']['deal_com_agent'] = $com_attr['agent']."%";
                    if(!empty($com_attr['client'])) $this->fields['special_notes']['deal_com_client'] = $com_attr['client']."%";
                    unset($com_attr);
                }
                break;
            case 'flats_for_sale':
                if(!empty($values['options_attr'])){
                    $options_attr = $values['options_attr'];
                    //тип продажи
                    if(!empty($options_attr['sale_type'])){
                        if($this->estate_type == 'live'){
                            if($options_attr['sale_type'] == 'F') $this->fields['special_notes']['deal_type'] = "свободная продажа";
                            elseif($options_attr['sale_type'] == 'A') $this->fields['special_notes']['deal_type'] = "альтернативная продажа";
                        }else{
                            switch($options_attr['sale_type']){
                                case 'ddu': $this->fields['special_notes']['deal_type'] = "договор долевого участия";
                                case 'zhsk': $this->fields['special_notes']['deal_type'] = "договор ЖСК";
                                case 'pereustupka': $this->fields['special_notes']['deal_type'] = "договор уступки прав требования";
                                case 'pdkp': $this->fields['special_notes']['deal_type'] = "предварительный договор купли-продажи";
                                case 'invest': $this->fields['special_notes']['deal_type'] = "договор инвестирования";
                                case 'free': $this->fields['special_notes']['deal_type'] = "свободная продажа";
                                case 'alt': $this->fields['special_notes']['deal_type'] = "альтернативная продажа";
                            }
                        }
                    }
                    
                    //телефон
                    if(!empty($options_attr['phone'])){
                        if($options_attr['phone'] == "yes") $this->fields['phone'] = 1;
                        else $this->fields['phone'] = 2;
                    }
                    
                    //балкон
                    if(!empty($options_attr['balcon'])){
                        if($options_attr['balcon'] == "yes"){
                            $this->fields['id_balcon'] = 2;
                            switch($options_attr['balcon']){
                                case 2:$this->fields['id_balcon'] = 4;break;
                                case 3:$this->fields['id_balcon'] = 13;break;
                            }
                        }
                        else $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"нет",'title',false,'id');
                    }
                    //лоджия (x лоджий + y балконов считаем за "балкон + лоджия")
                    if(!empty($options_attr['lodgia'])){
                        if($options_attr['lodgia'] == "yes"){
                            if(!empty($options['balkon'])) $this->fields['id_balkon'] = 6;
                            else{
                                $this->fields['id_balcon'] = 3;
                                if(!empty($values['balkon']) && $options['balkon'] == 2) $this->fields['id_balcon'] = 5;break;
                            }
                        }
                        elseif( empty( $this->fields['id_balcon'])) $this->fields['id_balcon'] = $this->getInfoFromTable($this->sys_tables['balcons'],"нет",'title',false,'id');
                    }
                    
                    //совмещенные санузлы
                    if(!empty($options_attr['su_s'])){
                        if($options_attr['su_s'] == 2){
                            $this->fields['special_notes']['bathroom_toilet'] = "совмещенный";
                            $this->fields['id_toilet'] = 5;
                        }
                        elseif($options_attr['su_s'] == 3){
                            $this->fields['special_notes']['bathroom_toilet'] = "совмещенный";
                            $this->fields['id_toilet'] = 10;
                        } 
                        else $this->fields['id_toilet'] = 3;
                    }
                    
                    //совмещенные санузлы
                    if(!empty($options_attr['su_r'])){
                        if($options_attr['su_r'] == 2){
                            $this->fields['special_notes']['bathroom_toilet'] = "раздельный";
                            $this->fields['id_toilet'] = 5;
                        }
                        elseif($options_attr['su_r'] == 3){
                            $this->fields['special_notes']['bathroom_toilet'] = "раздельный";
                            $this->fields['id_toilet'] = 10;
                        }
                        else $this->fields['id_toilet'] = 3;
                    }
                    
                    //пассажирские лифты
                    if(!empty($options_attr['lift_p'])){
                        if($options_attr['lift_p'] > 1) $this->fields['id_elevator'] = 3;
                        else $this->fields['id_elevator'] = 2;
                    }
                    
                    //грузовые лифты - пишем только если пассажирский тоже есть
                    if(!empty($options_attr['lift_g'])){
                        if(!empty($this->fields['id_elevator'])) $this->fields['id_elevator'] = 4;
                    }
                    
                    //ремонт
                    if(!empty($options_attr['repair'])){
                        $this->fields['id_facing'] = $this->getInfoFromTable($this->sys_tables['facings'],$options_attr['facings'],'cianxml_value',false,'id');
                        if($options_attr['repair'] == 3) $this->fields['special_notes']['flat_facing'] = "дизайнерский";
                    }
                    
                    //мусоропровод
                    if(!empty($options_attr['chute']) && $options_attr['chute'] == "yes") $this->fields['special_notes']['house_chute'] = "мусоропровод";
                    
                    //отделка
                    if(!empty($options_attr['chute']))
                        if($options_attr['chute'] == "yes") $this->fields['special_notes']['flat_finishing'] = "есть отделка";
                        else $this->fields['special_notes']['flat_finishing'] = "нет отделки";
                    
                    //окна
                    if(!empty($options_attr['windows'])){
                        $this->fields['id_window'] = $this->getInfoFromTable($this->sys_tables['windows'],$options_attr['windows'],'cianxml_value',false,'id');
                    }
                    
                    unset($options_attr);
                }
                
                //информация о ЖК
                if(!empty($values['residental_complex_attr']) && !empty($values['residental_complex_attr']['name']))
                    $this->fields['id_housing_estate']  = $this->getComplexId(1,false,$values['residental_complex_attr']['name']);
                    if(!empty($this->fields['id_housing_estate'])){
                        $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$this->fields['id_housing_estate']);
                        if(!empty($complex_info)){
                            $this->fields['lat'] = $complex_info['lat'];
                            $this->fields['lng'] = $complex_info['lng'];
                        }
                    }
                
                break;
            case 'commerce':
                if(!empty($values['options_attr'])){
                    $options_attr = $values['options_attr'];
                    
                    if(!empty($options_attr['phones'])) $this->fields['phones_count'] = $options_attr['phones'];
                    
                    if(!empty($options_attr['add_phones']) && $options_attr['add_phones'] == 1) $this->fields['special_notes']['object_add_phones'] = "дополнительные телефонные линии";
                    
                    if(!empty($options_attr['mebel']) && $options_attr['mebel'] == 1) $this->fields['special_notes']['object_furniture'] = "мебель";
                    
                    if(!empty($options_attr['status']))
                        switch($options_attr['status']){
                            case 1: $this->fields['id_facing'] = 7;
                            case 2: $this->fields['id_facing'] = 5;
                            case 3: $this->fields['id_facing'] = 6;
                        }
                    
                    if(!empty($options_attr['elect'])) $this->fields['electricity'] = ($options_attr['elect'] == "yes"?1:2);
                    
                    if(!empty($options_attr['heat'])) $this->fields['heating'] = ($options_attr['heat'] == "yes"?1:2);
                    
                    if(!empty($options_attr['canal'])) $this->fields['canalization'] = ($options_attr['canal'] == "yes"?1:2);
                    
                    if(!empty($options_attr['gas']) && $options_attr['gas'] == "yes") $this->fields['special_notes']['object_gas'] = "газ";
                    
                    if(!empty($options_attr['water']) && $options_attr['water'] == "yes") $this->fields['special_notes']['object_water'] = "водоснабжение";
                    
                    if(!empty($options_attr['lift']) && $options_attr['lift'] == "yes") $this->fields['special_notes']['object_lift'] = "лифт";
                    
                    if(!empty($options_attr['parking'])) $this->fields['parking'] = ($options_attr['parking'] == "yes"?1:2);
                    
                    if(!empty($options_attr['security'])) $this->fields['special_notes']['object_security'] = ($options_attr['security'] == "yes"?1:2);
                    
                    if(!empty($options_attr['internet']) && $options_attr['internet'] == "yes") $this->fields['special_notes']['object_internet'] = "интернет";
                    
                    unset($options_attr);
                }
                break;
            case 'suburbian':
                if(!empty($values['options_attr'])){
                    $options_attr = $values['options_attr'];
                    
                    if(!empty($options_attr['year'])) $this->fields['year_build'] = $options_attr['year'];
                    
                    //телефон
                    if(!empty($options_attr['phone'])){
                        if($options_attr['phone'] == "yes") $this->fields['phone'] = 1;
                        else $this->fields['phone'] = 2;
                    }
                    //кухонная мебель
                    if(!empty($options_attr['mebel_kitchen']) && $options_attr['mebel_kitchen'] == "yes") $this->fields['special_notes']['kitchen_furniture'];
                    //мебель
                    if(!empty($options_attr['mebel'])){
                        if($options_attr['mebel'] == "no") $this->fields['furniture'] = 2;
                        else $this->fields['furniture'] = 1;
                    }
                    //балкон
                    if(!empty($options_attr['balcon']) && $options_attr['balcon'] == "yes") $this->fields['special_notes']['object_balcon'] = "балкон";
                    
                    if(!empty($options_attr['lodgia']) && $options_attr['lodgia'] == "yes") $this->fields['special_notes']['object_lodgia'] = "лоджия";
                    
                    //холодильник
                    if(!empty($options_attr['rfgr']) && $options_attr['rfgr'] == "yes") $this->fields['special_notes']['object_rfgr'] = "холодильник";
                    
                    //посудомоечная машина
                    if(!empty($options_attr['dishwasher']) && $options_attr['dishwasher'] == "yes") $this->fields['special_notes']['object_dishwasher'] = "посудомоечная машина";
                    
                    //кондиционер
                    if(!empty($options_attr['conditioner']) && $options_attr['conditioner'] == "yes") $this->fields['special_notes']['object_condition'] = "кондиционер";
                    
                    //интернет
                    if(!empty($options_attr['internet']) && $options_attr['internet'] == "yes") $this->fields['special_notes']['object_internet'] = "интернет";
                    
                    //телевизор
                    if(!empty($options_attr['tv']) && $options_attr['tv'] == "yes") $this->fields['special_notes']['object_tv'] = "телевизор";
                    
                    //туалет
                    if(!empty($options_attr['toilet'])) $this->fields['id_toilet'] = ($options_attr['toilet'] == 1)?8:7;
                    
                    //возьмут с детьми
                    if(!empty($options_attr['kids'])) $this->fields['special_notes']['object_kids'] = ($options_attr['chute'] == "yes"?"":"не ")."возьмут с детьми";
                        
                    //возьмут с животными
                    if(!empty($options_attr['pets'])) $this->fields['special_notes']['object_pets'] = ($options_attr['pets'] == "yes"?"":"не ")."возьмут с животными";
                    
                    if(!empty($options_attr['elect'])) $this->fields['electricity'] = ($options_attr['elect'] == "yes"?2:3);
                    
                    if(!empty($options_attr['heat'])) $this->fields['heating'] = ($options_attr['heat'] == "yes"?2:3);
                    
                    if(!empty($options_attr['canal']) && $options_attr['canal'] == "yes") $this->fields['special_notes']['object_canalization'] = "канализация";
                    
                    if(!empty($options_attr['gas'])) $this->fields['heating'] = ($options_attr['gas'] == "yes"?2:3);
                    
                    if(!empty($options_attr['water'])) $this->fields['heating'] = ($options_attr['water'] == "yes"?2:3);
                    
                    if(!empty($options_attr['garage']) && $options_attr['garage'] == "yes") $this->fields['special_notes']['object_garage'] = "гараж";
                    
                    if(!empty($options_attr['pool']) && $options_attr['pool'] == "yes") $this->fields['special_notes']['object_pool'] = "бассейн";
                    
                    if(!empty($options_attr['security'])) $this->fields['special_notes']['object_security'] = ($options_attr['security'] == "yes"?1:2);
                    
                    if(!empty($options_attr['material'])) $this->fields['id_construct_material'] = $this->getInfoFromTable($this->sys_tables['construct_materials'],$options_attr['material'],'cianxml_value',false,'id');
                                        
                    if(!empty($options_attr['repair'])){
                        switch($options_attr['repair']){
                            case 1: $this->fields['special_notes']['object_facing'] = 'косметический';
                            case 2: $this->fields['special_notes']['object_facing'] = 'евро';
                            case 3: $this->fields['special_notes']['object_facing'] = 'дизайнерский';
                            case 4: $this->fields['special_notes']['object_facing'] = 'отсутствует';
                        }
                    }
                    
                    unset($options_attr);
                }
                
                if(!empty($values['bedroom_total']) && $values['bedroom_total'] == "yes") $this->fields['special_notes']['object_bedroom_total'] = $values['bedroom_total'];
                break;
        }
        
        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                //добавление адреса в таблицу адресов с коорлинатами 
                if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 
        
        return $this->fields;
    }
}

class CianNewXmlRobot extends Robot{
    public $file_format = 'ciannewxml';
    public $mapping = array(
                            'xml'         => array('ExternalId',  'metro',     'TotalArea',      'LivingArea',   'lot',           'KitchenArea',    'AllRoomsArea',  'Address',   'IsApartments',  'IsPenthouse',   'FloorNumber',  'RoomsForSaleCount',    'Description',  '',  '',  '',  '',  '',  '',  '')
                            ,'live'       => array('external_id', 'id_subway', 'square_full',    'square_live',  '',              'square_kitchen', 'square_rooms',  'txt_addr',   '',              '',             'level',        'rooms_sale',           'notes',  '',  '',  '',  '',  '',  '',  '') 
                            ,'build'      => array('external_id', 'id_subway', 'square_full',    'square_live',  '',              'square_kitchen', 'square_rooms',  'txt_addr',   'is_apartments', 'is_penthouse', 'level',        '',                     'notes',  '',  '',  '',  '',  '',  '',  '') 
                            ,'country'    => array('external_id', '',          'square_full',    'square_live',  'square_ground', '',               '',              'txt_addr',   '',              '',             'level',        '',                     'notes',  '',  '',  '',  '',  '',  '',  '') 
                            ,'commercial' => array('external_id', 'id_subway', 'square_full',    '',             '',              '',               '',              'txt_addr',   '',              '',             'level',        '',                     'notes',  '',  '',  '',  '',  '',  '',  '') 
    );
    /**
    * обработка полученных из bn.xml значений
    * @return array of arrays
    */
    public function getConvertedFields($values, $agency,$photos_limit = false,$return_deal_type = false){
        global $db, $counter,$errors_log, $agency, $estate_complexes_log;

        foreach($values as $k=>$val) {
            $values[strtolower($k)] = !is_array($val) ? strtolower( $val ) : (!empty($val) ? $val : false);
        }
        
        if( empty($values['category'])){
            $errors_log['estate_type'][$this->fields['external_id']] = 5;
            return false;
        }

        //тип объекта и сделки
        $type_object = preg_replace('#rent|sale#sui', '', $values['category']);
        if( $type_object == 'newbuildingflat' )  {
            $this->estate_type = 'build';
            $this->fields['id_type_object'] = 1;
            $this->fields['rent'] = 2;
        } else {
            $estate_types = array('live', 'commercial', 'country');
            foreach($estate_types as $estate_type){
                $this->fields['id_type_object'] = $this->getInfoFromTable( $this->sys_tables['type_objects_' . $estate_type], $type_object, 'ciannew_xml', true, 'id' );         
                if( !empty( $this->fields['id_type_object'] ) ) {
                    $this->estate_type = $estate_type;
                    break;
                }
            }
            if( empty( $this->fields['id_type_object'] ) ) {
                $errors_log['estate_type'][$this->fields['external_id']] = 5;
                return false;
            }
            $this->fields['rent'] = preg_match('#rent#sui', $values['category']) ? 1 : 2;
        }
        
        foreach ($this->mapping[$this->estate_type] as $key=>$column){
            if(($column!='' && !empty($values[$this->mapping['xml'][$key]]) && !is_array($values[$this->mapping['xml'][$key]])) ){
                $this->fields[strtolower($column)] = $values[$this->mapping['xml'][$key]];
            }
        }        
        //количество комнат всего ( в квартирах )
        if( !empty( $values['FlatRoomsCount'] )){
            $rooms = $values['FlatRoomsCount'] == 9 ? 0 : $values['FlatRoomsCount'];
            if( $this->fields['id_type_object'] == 1) $this->fields['rooms_sale'] = $this->fields['rooms_total'] = $rooms;
        }
        //количество комнат всего (в комнатах)
        if( !empty( $values['RoomsCount'] )) $this->fields['rooms_total'] = $values['RoomsCount'];

        // минут до метро
        if(!empty( $values['Underground'] ) && !empty( $values['Underground']['Time'] ) ) $this->fields['way_time'] = $values['Underground']['Time'];
        
        //ЖК
        if($this->estate_type=='build') {
            if(!empty( $values['JKSchema']['Id'] ) || !empty( $values['JKSchema']['Name'] ) ) $this->fields['id_housing_estate']  = $this->getComplexId(1, !empty($values['JKSchema']['Id']) ? $values['JKSchema']['Id'] : false, !empty($values['JKSchema']['Name']) ? $values['JKSchema']['Name'] : false);
            if( !empty( $this->fields['id_housing_estate'] ) ) $complex_info = $db->fetch("SELECT id,lat,lng FROM ".$this->sys_tables['housing_estates']." WHERE id = ?",$this->fields['id_housing_estate']);
            if( !empty($complex_info) ){
                $this->fields['lat'] = $complex_info['lat'];
                $this->fields['lng'] = $complex_info['lng'];
            }
        } 
        
        //данные из таблиц с информацией
        $sprav_array = array(
             'facing' =>        array( !empty($values['RepairType']) ? $values['RepairType'] : '',        'facings' )    //ремонт
            ,'decoration' =>        array( !empty($values['Decoration']) ? $values['Decoration'] : '',        'decorations' )    //отделка
            ,'window' =>            array( !empty($values['WindowsViewType']) ? $values['WindowsViewType'] : '',   'windows' )        //окна
            ,'building_type' =>     array( !empty($values['Building']['MaterialType']) ? $values['Building']['MaterialType'] : '',   'building_types' )        //тип дома
            ,'subway' =>            array( !empty($values['Underground']['Id']) ? $values['Underground']['Id'] : '',   'subways' )        //метро
            ,'way_type' =>          array( !empty($values['Underground']['TransportType']) ? $values['Underground']['TransportType'] : '',   'way_types' )        //способ добраться до метро
        );
        foreach($sprav_array as $type => $items) if(!empty( $items[0] ) ) $this->fields['id_' . $type ] = $this->getInfoFromTable( $this->sys_tables[ $items[1] ], $items[0], 'ciannew_xml', false, 'id');       
        
        //информация о здании
        if(!empty( $values['Building'] ) ){
            $building = $values['Building'];
            //кол-во этажей
            if(!empty( $building['FloorsCount'] ) ) $this->fields['level_total'] = $building['FloorsCount'];
            //Высота потолков
            if(!empty( $building['CeilingHeight'] ) ) $this->fields['ceiling_height'] = $building['CeilingHeight'];
            //сроки сдачи
            if(!empty( $building['Deadline'] ) ){
                if(!empty( $building['Deadline']['IsComplete'] ) ) $this->fields['id_build_complete'] = 4;
                else {
                    $year = $building['Deadline']['Year'] ;
                    $decade = $building['Deadline']['Quarter'] ;
                    $this->fields['id_build_complete'] = $db->fetch("SELECT `id` FROM ".$this->sys_tables['build_complete']." WHERE `year`=? AND `decade`=?",$year, $decade)['id'];
                }
            }
            //
        } 
        
        //условия сделки
        if(!empty( $values['BargainTerms'] ) && !empty( $values['BargainTerms']['Price'] ) ) $this->fields['cost'] = $values['BargainTerms']['Price'];

        //телефон
        if(!empty( $values['PhoneSchema'] ) && !empty( $values['PhoneSchema']['Number'] ) ) $this->fields['seller_phone'] = '8' . $values['PhoneSchema']['Number'];

        
        //информация о продавце
        if( !empty( $values['SubAgent'] ) && !empty( $values['SubAgent']['Phone'])) {
            $phone = Convert::ToPhone( $values['SubAgent']['Phone'] );
            $this->fields['seller_phone'] = $phone[0];
        }
        
        //изображения
        if( !empty( $values['Photos'] ) && !empty( $values['Photos']['PhotoSchema'])) {
            $photos = $values['Photos']['PhotoSchema'];
            foreach($photos as $photo){
                $this->fields['images'][] = $photo['FullUrl'];
                if(!empty($photo['IsDefault']) && $photo['IsDefault'] == 'true') $this->fields['main_photo'] = $photo['FullUrl'];
                
            }
        }
        
        //изображения
        if( !empty( $values['LayoutPhoto'] ) && !empty( $values['LayoutPhoto']['FullUrl'])) {
            $this->fields['images'][] = $values['LayoutPhoto']['FullUrl'];
            if(!empty($values['LayoutPhoto']['IsDefault']) && $values['LayoutPhoto']['IsDefault'] == 'true') $this->fields['main_photo'] = $values['LayoutPhoto']['FullUrl'];
        }

        //район        
        if( empty( $this->fields['id_district'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78) $this->getDistrict( $this->fields ); 
        
        //координаты широта + долгота
        if( !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) {
            $spb_address = $this->getSpbAddress( $this->fields );
            if( !empty( $spb_address ) ) list( $this->fields['lat'], $this->fields['lng'] ) = array( $spb_address['lat'], $spb_address['lng'] );
        } 
        if( empty( $spb_address ) ) {
            if( !empty( $values['Coordinates']['Lat'] ) && !empty( $values['Coordinates']['Lng'] ) ) {
                $this->fields['lat'] = Convert::ToValue( $values['Coordinates']['Lat'] );
                $this->fields['lng'] = Convert::ToValue( $values['Coordinates']['Lng'] );
            } else {
                if( $this->fields['lat'] < 1 || $this->fields['lng'] < 1  ) {
                    list($this->fields['lat'], $this->fields['lng']) = $this->getCoords($this->fields);
                    //добавление адреса в таблицу адресов с коорлинатами 
                    if( $this->fields['lat'] > 1 && $this->fields['lng'] > 1 && !empty( $this->fields['house'] ) && !empty( $this->fields['id_street'] ) ) $this->addSpbAddress( $this->fields );
                }
            }
        }
        //метро        
        if( empty( $this->fields['id_subway'] ) && !empty( $this->fields['id_region'] ) && $this->fields['id_region'] == 78 )   $this->getSubway( ); 

        return $this->fields;        
    }
}
?>