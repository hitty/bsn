<?php
/**    
* Authorization
*/
require_once('includes/class.common.php');
require_once('includes/class.estate.statistics.php');

class Member {
    private $objects_stats;
    private $published_limit;
    private $object_cost_statuses;
    
    public function __construct(){
        global $auth;
        global $db;
        $this->object_cost_statuses = $db->fetchall("SELECT * FROM ".Config::$values['sys_tables']['objects_statuses']." WHERE id NOT IN (2,7)",'id');
        if(!$auth->checkAuth()) return false;
        else{
            $this->objects_stats = EstateStat::getCount( empty($auth->id_agency) || !$auth->agency_admin ? $auth->id : false, !empty($auth->id_agency) ? $auth->id_agency : false, false, false);
            $this->objects_stats['published_total'] = !empty($this->objects_stats['published']) ? array_sum($this->objects_stats['published']) : 0;
            $this->objects_stats['payed_total'] = !empty($this->objects_stats['payed']) ? $this->objects_stats['payed'] : 0;
            $this->objects_stats['moderation_total'] = !empty($this->objects_stats['moderation']) ? array_sum($this->objects_stats['moderation']) : 0;
            $this->objects_stats['archive_total'] = !empty($this->objects_stats['archive']) ? array_sum($this->objects_stats['archive']) : 0;
            $this->objects_stats['draft_total'] = !empty($this->objects_stats['draft']) ? array_sum($this->objects_stats['draft']) : 0;
        }
    }
    
    private function ActualizeObjectsStats(){
        global $auth;
        $this->objects_stats = EstateStat::getCount( empty($auth->id_agency) || !$auth->agency_admin ? $auth->id : false, !empty($auth->id_agency) ? $auth->id_agency : false, false, false);
        $this->objects_stats['published_total'] = !empty($this->objects_stats['published']) ? array_sum($this->objects_stats['published']) : 0;
        $this->objects_stats['payed_total'] = !empty($this->objects_stats['payed']) ? $this->objects_stats['payed'] : 0;
        $this->objects_stats['moderation_total'] = !empty($this->objects_stats['moderation']) ? array_sum($this->objects_stats['moderation']) : 0;
        $this->objects_stats['archive_total'] = !empty($this->objects_stats['archive']) ? array_sum($this->objects_stats['archive']) : 0;
        $this->objects_stats['draft_total'] = !empty($this->objects_stats['draft']) ? array_sum($this->objects_stats['draft']) : 0;
        return true;
    }
    
    private function combineBuyList( $affected_objects, $status, $agency_object_long, $free_left, $object_cost_statuses, $sum_only = false ){
        global $db;
        global $auth;
        $sys_tables = Config::$values['sys_tables'];
        $objects_list = [];
        $objects_links = [];
        $total_sum = 0;
        $total_objects = 0;
        $free_objects = 0;
        $affected_ids = [];
        
        //поднятие на 5 дней
        if($status == 1 && $agency_object_long == 5) $object_cost_statuses[$status]['cost'] = $object_cost_statuses[$status]['cost']*4;
        
        foreach($affected_objects as $key=>$item){
            if(empty($item)) continue;
            //убираем все лишнее
            $item = preg_replace('/[^0-9\,]/','',$item);
            $item = trim(preg_replace('/((\,)(?<!(^\,)|(\d\,(?!$))))|(^\,)|(\,$)/sui','',$item),',');
            $affected_ids[] = $item;
            if($key == 'build')
                $objects_list[$key] = $db->fetchall("SELECT ".$sys_tables[$key].".*,'flats' AS type_object FROM ".$sys_tables[$key]."
                                                     WHERE ".$sys_tables[$key].".id IN (".$item.") AND id_user = ".$auth->id);
            else
                $objects_list[$key] = $db->fetchall("SELECT ".$sys_tables[$key].".*,".$sys_tables['type_objects_'.$key].".new_alias AS type_object FROM ".$sys_tables[$key]."
                                                     LEFT JOIN ".$sys_tables['type_objects_'.$key]." ON ".$sys_tables[$key].".id_type_object = ".$sys_tables['type_objects_'.$key].".id
                                                     WHERE ".$sys_tables[$key].".id IN (".$item.") AND id_user = ".$auth->id);
            
            $type_amount = count($objects_list[$key]);
            
            //ссылки на страницы с объявлениями (для письма)
            foreach($objects_list[$key] as $k=>$i){
                //если делаем промо/премиум/вип/акцию, и при этом объект уже промо/премиум/вип/акция, просто убираем
                if($status > 2 && in_array($i['status'],array(3,4,6,7))){
                    unset($objects_list[$key][$k]);
                    --$type_amount;
                }
                else $objects_links[$key][] = Host::$host."/".$key."/".(($i['rent'] == 1)?"rent":"sell")."/".$i['id'].'/';
            }
            
            
            $total_objects += $type_amount;
            $free_objects += min($free_left,$type_amount);
            if(!empty($auth->id_tarif)){
                
                if($free_left>$type_amount) $free_left -= $type_amount;
                else{
                    $total_sum += $object_cost_statuses[$status]['cost']*($type_amount - $free_left);
                    $free_left = 0;
                } 
            } elseif ($auth->id_agency>0 && $auth->agency_admin == 1 && !empty($auth->agency_id_tarif)){ 
                if($free_left>$type_amount) $free_left -= $type_amount;
                else{
                    $total_sum += $object_cost_statuses[$status]['cost']*($type_amount - $free_left);
                    $free_left = 0;
                } 
            } else $total_sum += $object_cost_statuses[$status]['cost']*$type_amount;
        }
        if($sum_only) return array('total_sum'=>$total_sum,'total_objects'=>$total_objects,'free_objects'=>$free_objects);
        else return array('objects_list'=>$objects_list,'objects_links'=>$objects_links,'affected_ids'=>$affected_ids,'total_sum'=>$total_sum,'total_objects'=>$total_objects,'free_objects'=>$free_objects);
    }
    
    
    /**
    * put your comment there...
    * 
    * @param mixed $estate_type - alias раздела
    * @param mixed $id_object - id объекта
    * @param mixed $action - "publish" или значение status (1,3,4,5,6,8)
    * @param mixed $days_long - длительность в днях
    * @param mixed $deal - тип сделки (1/2)
    * Последнее поле нужно для расчета стоимости исходя из раздела и типа сделки
    */
    private function getObjectOperationInfo($estate_type, $id_object, $action, $days_long = false, $deal = false){
        global $db;
        global $sys_tables;
        global $auth;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        if( (!Validate::isDigit($id_object) && empty($deal)) || empty($sys_tables[$estate_type]) || !in_array($action,array_merge(array("publish",1,3,4,5,6,8))) ) return false;
        
        //проверяем что объект есть и принадлежит этому пользователю
        if(empty($id_object)) $object_info = array('id' => 0, 'rent' => $deal);
        else{
            $object_info = $db->fetch("SELECT id,rent,id_type_object,published FROM ".$sys_tables[$estate_type]." WHERE id = ?",$id_object);
            if(empty($object_info) || empty($object_info['id'])) return false;
        }
        
        
        ///сколько можем опубликовать
        $this->ActualizeObjectsStats();
        
        //////////////////////
        ////теперь идем по трем ситуациям - специалист, компания(админ), частное лицо
        //
        //специалист
        if($auth->id_tarif > 0){
            $tarif = $db->fetch("SELECT * FROM ".$sys_tables['tarifs']." WHERE id = ?",$auth->id_tarif);
            $this->published_limit = $tarif['active_objects'] + $this->objects_stats['payed_total'];
            //можно ли добавлять(бесплатная публикация)
            $can_add_amount = $this->published_limit - $this->objects_stats['published_total'];
            $can_add = $can_add_amount > 0;
            //можно ли бесплатно выделять
            $can_promo = $auth->promo_left;
            $can_premium = $auth->premium_left;
            $can_vip = $auth->vip_left;
            
        }
        //компания (админ или сотрудник)
        elseif($auth->id_agency > 0){
            $agency_limit = EstateStat::getAgenciesCount($auth->id);
            $this->published_limit = EstateStat::getCountPacketAgencies($auth->id_agency);
            //можно ли добавлять(бесплатная публикация)
            $deal_type = $estate_type != 'build' ? '_'.( $object_info['rent'] == 1 ? 'rent' : 'sell' ) : "";
            switch($estate_type){
                case 'live': 
                    $packet_limit =  $deal_type == '_sell' ? $auth->live_sell_objects - ( !empty( $this->objects_stats['published_sell']["live_sell_free"] ) ? $this->objects_stats['published_sell']["live_sell_free"] : 0 ) : $auth->live_rent_objects - ( !empty( $this->objects_stats['published_rent']["live_rent_free"] ) ? $this->objects_stats['published_rent']["live_rent_free"] : 0 ); 
                    break;
                case 'build': 
                    $published = EstateStat::getCount(false,$auth->id_agency,'build',false,false,false,true);
                    $packet_limit =  $auth->build_objects - ( !empty( $this->objects_stats['published']["build_free"] ) ? $this->objects_stats['published']["build_free"] : 0 );
                    break;
                case 'commercial': 
                    $published = EstateStat::getCount(false,$auth->id_agency,'commercial',false,false,false,true);
                    $packet_limit =  $deal_type == '_sell' ? $auth->commercial_sell_objects - ( !empty( $this->objects_stats['published_sell']["commercial_rent_free"] ) ? $this->objects_stats['published_sell']["commercial_sell_free"] : 0 ) : $auth->commercial_rent_objects - ( !empty( $this->objects_stats['published_rent']["commercial_rent_free"] ) ? $this->objects_stats['published_rent']["commercial_rent_free"] : 0 ); 
                    break;
                case 'country': 
                    $published = EstateStat::getCount(false,$auth->id_agency,'country',false,false,false,true);
                    $packet_limit =  $deal_type == '_sell' ? $auth->country_sell_objects - ( !empty( $this->objects_stats['published_sell']["country_rent_free"] ) ? $this->objects_stats['published_sell']["country_sell_free"] : 0 ) : $auth->country_rent_objects - ( !empty( $this->objects_stats['published_rent']["country_rent_free"] ) ? $this->objects_stats['published_rent']["country_rent_free"] : 0 ); 
                    break;
            }
            
            //публикует бесплатно только администратор?
            if(!empty($auth->id_agency) && 
               $auth->agency_admin == 1
              ){
                  //можно ли бесплатно выделять
                //$can_add = ($this->objects_stats['published'.$deal_type][$estate_type.$deal_type] < $packet_limit || ($auth->agency_id_tarif == 7 && empty($packet_limit)) );
                $can_add = ($packet_limit > 0 || ($auth->agency_id_tarif == 7 && empty($packet_limit)) );
                $can_promo = $auth->agency_promo - $agency_limit['promo'] > 0;
                $can_premium = $auth->agency_premium - $agency_limit['premium'] > 0;
                $can_vip = $auth->agency_vip - $agency_limit['vip'] > 0;
              } 
            else{
                
                $can_promo = false;
                $can_premium = false;
                $can_vip = false;
                $can_add = false;
            }
        }
        //частное лицо
        else{
            $this->published_limit = 1;
            //можно ли добавлять(бесплатная публикация, аренду отсекаем)
            $can_add_amount = $this->published_limit - $this->objects_stats['published_total'] + $this->objects_stats['payed_total'];
            $can_add = ($can_add_amount > 0 && $object_info['rent'] == 2);
            //можно ли бесплатно выделять
            $can_promo = false;
            $can_premium = false;
            $can_vip = false;
        }
        //
        //////////////////////
        
        $res = true;
        $k = 0;
        
        $result['rent'] = $object_info['rent'];
        if($estate_type == 'build') $result['type_object'] = 'flats';
        elseif(!empty($object_info['id_type_object'])){
            $result['type_object'] = $db->fetch("SELECT new_alias FROM ".$sys_tables['type_objects_'.$estate_type]." WHERE id = ?", $object_info['id_type_object']);
            $result['type_object'] = $result['type_object']['new_alias'];
        }
        
        //закрываем возможность бесплатно публиковать с помощью выделения при превышении лимита
        if(empty($can_add) && !empty($object_info['published']) && $object_info['published'] == 2){
            $can_promo = false;
            $can_premium = false;
            $can_vip = false;
        }
        
        //теперь, исходя из того кто за пультом, рассчитываем стоимость объекта
        //
        $result['free'] = $can_add;
        switch(true){
            //публикация продажи
            case $action == 5 && $object_info['rent'] == 2:
                $result['days_long'] = $this->object_cost_statuses[$action]['days_last'];
                $result['cost'] = (!empty($can_add) ? 0 : $this->object_cost_statuses[$action]['cost']);
                $result['can_add'] = $can_add;
                break;
            //публикация аренды
            case $action == 5 && $object_info['rent'] == 1:
            case $action == 8:
                $result['days_long'] = ( !in_array( $days_long, array(7,14,30) ) || empty($days_long) ? 7 : $days_long );
                $result['cost'] = (!empty($can_add) ? 0 : $this->object_cost_statuses[$action]['cost']*$result['days_long']);
                break;
            //платные выделения
            case in_array($action,array(3,4,6)):
                $result['days_long'] = (!empty($days_long) && $days_long != $this->object_cost_statuses[$action]['days_last'] ? $days_long : $this->object_cost_statuses[$action]['days_last']);
                $can_add_this_highlighted = ($action == 3 ? $can_promo : ($action == 4 ? $can_premium : $can_vip));
                $result['cost'] = (!empty($can_add_this_highlighted) ? 0 : $this->object_cost_statuses[$action]['cost']);
                break;
            //поднятие
            case $action == 1:
                $result['days_long'] = (!in_array($days_long,array(1,5)) ? 1 : $days_long);
                //для поднятия кастомная цена
                $result['cost'] = ($days_long == 5 ? 120 : $this->object_cost_statuses['1']['cost']);
                break;
            default:
                $result = false;
        }
        //
        /////
        
        return $result;
    }                        
    
    /**
    * оплата объекта
    * 
    * @param mixed $estate_type
    * @param mixed $id_object
    * @param mixed $action
    * @param mixed $days_long
    */
    public function doObjectOperation($estate_type, $id_object, $status, $days_long){
        global $db;
        global $sys_tables;
        global $auth;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        $obj_types = array('live'=>'Жилая', 'build'=>'Новостройки', 'commercial'=>'Коммерческая', 'country'=>'Загородная'); 
        if( !Validate::isDigit($id_object) || 
            empty($sys_tables[$estate_type]) || 
            !in_array($status,array_merge(array("publish",1,3,4,5,6,8))) ||
            empty($obj_types[$estate_type])
          ) return false;
        
        $pay_params = $this->getObjectOperationInfo($estate_type, $id_object, $status, $days_long);
        $result = array('response'=>[], 'errors'=>[]);
        //ошибка - выходим
        if(empty($pay_params)) return false;
        //если публикация - просто публикуем
        if(!empty($pay_params) && !empty($pay_params['can_add']) ){
            $result['object_status_set'] = $db->query("UPDATE ".$sys_tables[$estate_type]."
                        SET published = 1, 
                            date_change = NOW()
                        WHERE id = ?", $id_object);
        }
        //платное размещение
        else{
            //не хватает баланса - отмечаем это и выходим
            if($auth->balance < $pay_params['cost']){
                $result['response']['not_enough_balance'] = true;
                return $result;
            }
            //баланса хватает
            else{
                //операция с объектом
                $status_date_end = new DateTime("+".$pay_params['days_long']." day");
                $status_date_end = $status_date_end->format("Y-m-d H:i:s");
                $auth->checkAuth($auth->email, $auth->passwd, 1);
                if(!empty($pay_params['cost']) && $auth->balance >= $pay_params['cost'] || empty($pay_params['cost'])){
                    //все кроме поднятия - меняем поля status и status_date_end. И публикуем, если не опубликовано
                    if($status != 1)
                        $result['object_status_set'] = $db->query("UPDATE ".$sys_tables[$estate_type]."
                                                                   SET status = ?, 
                                                                       status_date_end = ?, 
                                                                       published = 1, 
                                                                       date_change = NOW(),
                                                                       payed_status = ?
                                                                   WHERE id = ?", $status, $status_date_end, (empty($pay_params['cost']) ? 2 : 1), $id_object);
                    //поднятие - меняем поля raising_status, raising_datetime, raising_days_left
                    elseif($status == 1)
                        $result['object_status_set'] = $db->query("UPDATE ".$sys_tables[$estate_type]."
                                                                    SET raising_datetime = NOW() + INTERVAL 1 DAY, 
                                                                        raising_status = 1,
                                                                        raising_days_left = ?, 
                                                                        date_change = NOW(),
                                                                        payed_status = ?
                                                                    WHERE id = ?", $pay_params['days_long'], (empty($pay_params['cost']) ? 2 : 1), $id_object);
                }
                
                //если это специалист или компания
                if((!empty($auth->id_tarif)) || (!empty($auth->agency_id_tarif))){
                    //если объект бесплтаный(это только промо-премиум-вип) - списываем с аккаунта выделение
                    if(empty($pay_params['cost']) && in_array($status,array(3,4,6)) && $auth->id_tarif > 0)
                        $result['status_left_updated'] = $db->query("UPDATE ".$sys_tables['users']." 
                                                                     SET ".$this->object_cost_statuses[$status]['alias']."_left = ".$this->object_cost_statuses[$status]['alias']."_left - 1 
                                                                     WHERE id = ?", $auth->id);
                    //снимаем деньги с баланса
                    elseif(!empty($pay_params['cost'])){
                        //если в этот момент уже не хватает, уведомляем web@bsn.ru
                        if($auth->balance < $pay_params['cost']){
                            
                        }
                        $result['payment'] = $db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?",$pay_params['cost'], $auth->id);
                        //отмечаем что объект оплачен с баланса
                        $db->query("UPDATE ".$sys_tables[$estate_type]." SET payed_status = 1 WHERE id = ?",$id_object);
                    }
                }
                //частное лицо - просто снимаем деньги с баланса
                else $result['payment'] = $db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?",$pay_params['cost'], $auth->id);
                
                //запись в финансах
                if(!empty($result['payment']))
                    $db->query("INSERT INTO ".$sys_tables['users_finances']." 
                                SET expenditure = ?, id_user = ?, obj_type = ?, id_parent=?, estate_type = ?", 
                                $pay_params['cost'], $auth->id, $this->object_cost_statuses[$status]['alias'], $id_object, $estate_type);
            }
        }
        $result['object_link'] = Host::$host."/".$estate_type."/".(($pay_params['rent'] == 1)?"rent":"sell")."/".$id_object;
        $result['type_object'] = $pay_params['type_object'];
        $result['summ'] = $pay_params['cost'];
        $result['days_long'] = $pay_params['days_long'];
        $auth->checkAuth($auth->email, $auth->passwd, 1);
        $result['response']['item'] =  $pay_params;
        $result['response']['id_object'] = $id_object;
        $result['response']['summ'] = $pay_params['cost'];
        $result['response']['obj_type_title'] = $obj_types[$estate_type];
        $result['response']['status_title'] = $this->object_cost_statuses[$status]['title'];
        return $result;
    }
    
    /**
    * читаем значение длительности
    * 
    * @param mixed $agency_object_long
    * @param mixed $status
    * @return mixed
    */
    public function getDaysLong($agency_object_long,$status){
        global $auth;
        if($auth->id_agency > 0 && $auth->agency_admin == 1 && (empty($agency_object_long) || $agency_object_long == "false") && in_array($status,array(3,4))) $agency_object_long = 1;
        else $agency_object_long = Convert::ToInt($agency_object_long);
        
        return (empty($agency_object_long)) ? (in_array($status,array(1,8,6)) ? false : 30) : $agency_object_long;
    }
    
    /**
    * проверка параметров оплаты
    * 
    * @param mixed $id_object
    * @param mixed $obj_type
    * @param mixed $status
    * @param mixed $agency_object_long
    * 
    * массив в случае ошибки, true если все хорошо
    */
    public function checkObjectPaymentParams($id_object, $obj_type, $status){
        global $db;
        global $auth;
        
        if(empty($id_object) || empty($obj_type) || empty(Config::$values['sys_tables'][$obj_type]) || empty($status)) return array('wrong params' => true);
        
        $item = $db->fetch("SELECT * FROM ".Config::$values['sys_tables'][$obj_type]." WHERE id = ? AND id_user = ?", $id_object, $auth->id);
        if(empty($item)) return array('wrong object' => true);
        
        if($item['status'] == $status && $status != 2) return array('alredy_payed' => true);
        
        return true;
    }
    
    /**
    * читаем всякие геоштуки для формы (2 шаг)
    * 
    * @param mixed $mapping - передается $mapping[$estate]
    * @param mixed $info
    */
    public function getObjectGeoInfo($mapping,$info){
        global $db;
        global $sys_tables;
        global $auth;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $geodata = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                  WHERE ( (a_level > 1 AND a_level < 5 AND id_region = 47 ) OR (a_level < 5 AND id_region = 78) ) AND 
                                          (
                                              (id_region=? AND id_area=? AND id_city=? AND id_place=?)
                                              OR (id_region=? AND id_area=? AND id_city=? AND id_place=0)
                                              OR (id_region=? AND id_area=? AND id_city=0 AND id_place=0)
                                              OR (id_region=? AND id_area=0 AND id_city=0 AND id_place=0)
                                          )
                                  ORDER BY a_level"
            , false
            , $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place']
            , $info['id_region'], $info['id_area'], $info['id_city']
            , $info['id_region'], $info['id_area']
            , $info['id_region']
        );
        $geolocation = $location = [];
        while(!empty($geodata)){
            $location = array_shift($geodata);
            if(empty($geodata)) {
                $mapping['geo_id']['value'] = $location['id'];
                $mapping['txt_region']['value'] = $location['shortname_cut'].'. '.$location['offname'];
            }  else  $geolocation[] = $location['offname'].' '.$location['shortname'];
        }
        $mapping['geolocation']['value'] = implode(', ',$geolocation);
        //определение улицы
        if(!empty($info['id_street'])) {
            $street = $db->fetch("
                SELECT `offname`, `shortname` FROM ".$sys_tables['geodata']."
                WHERE a_level = 5 AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place'], $info['id_street']
            );
            $mapping['txt_street']['value'] = $info['txt_street'] = $street['offname'].' '.$street['shortname'];
        }
        //определение района
        if(!empty($info['id_district'])) {
            $district = $db->fetch("SELECT title FROM ".$sys_tables['districts']." WHERE id=?",$info['id_district']);
            $info['txt_district'] = $district['title'];
            $mapping['txt_district']['value'] = $info['txt_district'];
        } elseif($info['id_region']==47){
            $mapping['txt_district']['value'] = $info['txt_district'] = '-';
            $mapping['txt_district']['disabled'] = true;
        }
        //определение метро
        if(!empty($info['id_subway'])) {
            $subway = $db->fetch("SELECT title FROM ".$sys_tables['subways']." WHERE id=?",$info['id_subway']);
            $mapping['txt_subway']['value'] = $info['txt_subway'] = $subway['title'];
        }
        //определение ЖК
        if(!empty($info['id_housing_estate'])) {
            $housing_estate = $db->fetch("SELECT title FROM ".$sys_tables['housing_estates']." WHERE id=?",$info['id_housing_estate']);
            $mapping['housing_estate']['value'] = $info['housing_estate'] = $housing_estate['title'];
        }
        //определение КП
        if(!empty($info['id_cottage'])) {
            $cottage = $db->fetch("SELECT title FROM ".$sys_tables['cottages']." WHERE id=?",$info['id_cottage']);
            $mapping['cottage']['value'] = $info['cottage'] = $cottage['title'];
        }
        //определение БЦ
        if(!empty($info['id_business_center'])) {
            $business_center = $db->fetch("SELECT title FROM ".$sys_tables['business_centers']." WHERE id=?",$info['id_business_center']);
            $mapping['business_center']['value'] = $info['business_center'] = $business_center['title'];
        }
        return array($mapping,$info);
    }
    
    /**
    * кастомизация маппинга под типы недвижимости
    * 
    * @param mixed $mapping - передается $mapping[$estate]
    * @param mixed $estate
    * @param mixed $deal
    * @param mixed $post_parameters
    */
    public function setEstateFields($mapping,$estate,$deal,$post_parameters){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        switch($estate){
            case 'live':
                // квартира/комната
                if($deal=='sell'){
                    $mapping['rent_duration']['nodisplay'] = true;
                    $mapping['by_the_day']['nodisplay'] = true;
                }
                
                if((!empty($mapping['id_type_object']['value']) && $mapping['id_type_object']['value']==2) || (!empty($post_parameters['id_type_object']) && $post_parameters['id_type_object']==2)){
                    $mapping['rooms_sale']['hidden'] = false;
                    $mapping['is_apartments']['class'] = '';
                    $mapping['is_penthouse']['class'] = '';
                    
                } else {
                    $mapping['rooms_sale']['hidden'] = true;
                    $mapping['is_apartments']['class'] = 'active';
                    $mapping['is_penthouse']['class'] = 'active';
                }
                //студия
                if((!empty($mapping['id_type_object']['value']) && $mapping['id_type_object']['value']==1) || (!empty($post_parameters['id_type_object']) && $post_parameters['id_type_object']==1)){
                    $mapping['studio']['class'] = 'active';
                    if($mapping['rooms_sale']['value']==0 && empty($post_parameters['rooms_total'])) {
                        $mapping['studio']['value']  = 1;
                        $mapping['rooms_sale']['allow_empty'] = true;
                        $mapping['rooms_sale']['allow_null'] = true;                                            
                        $mapping['rooms_sale']['disabled'] = true;  
                        $post_parameters['rooms_total'] = 0;  
                        $mapping['square_kitchen']['hidden'] = true;                                        
                    }
                    $mapping['is_apartments']['class'] = 'active';
                    $mapping['is_penthouse']['class'] = 'active';
                } else {
                    $mapping['studio']['class'] = true;
                    $mapping['square_kitchen']['hidden'] = false;   
                    
                                                         
                }
                // продажа/аренда
                if($deal=='rent'){
                    $mapping['rent_duration']['hidden'] = false;
                    $mapping['by_the_day']['hidden'] = false;
                } else {
                    $mapping['rent_duration']['hidden'] = true;
                    $mapping['by_the_day']['hidden'] = true;
                }
                break;
            case 'build':
                $mapping['is_apartments']['class'] = 'active';
                $mapping['is_penthouse']['class'] = 'active';
                
                //студия
                $mapping['studio']['class'] = 'active';
                if((empty($post_parameters['rooms_total']) && $mapping['rooms_sale']['value']==0) || (!empty($post_parameters['studio']) && $post_parameters['studio']==1) ) {
                    $post_parameters['rooms_sale'] = 0;
                    $mapping['studio']['value']  = 1;
                    $mapping['rooms_sale']['allow_empty'] = true;
                    $mapping['rooms_sale']['allow_null'] = true;                                            
                    $mapping['rooms_sale']['disabled'] = true; 
                    $mapping['square_kitchen']['hidden'] = true;   
                }
                if(!empty($post_parameters['rooms_total'])) $post_parameters['rooms_total'] = $post_parameters['rooms_sale'];
                else  $mapping['rooms_total']['value'] =  $mapping['rooms_sale']['value'];
                // рассрочка
                if($mapping['installment']['value']==1  || (!empty($post_parameters['installment']) && $post_parameters['installment']==1)){
                    $mapping['installment_months']['hidden'] = false;
                    $mapping['installment_years']['hidden'] = false;
                    $mapping['first_payment']['hidden'] = false;
                } else {
                    $mapping['installment_months']['hidden'] = true;
                    $mapping['installment_years']['hidden'] = true;
                    $mapping['first_payment']['hidden'] = true;
                }
                break;
            case 'commercial':
                // продажа/аренда
                if($deal == 'rent'){
                    $mapping['rent_duration']['hidden'] = false;
                } else {
                    $mapping['rent_duration']['hidden'] = true;
                }
                //ограничения для участка
                if((empty($post_parameters) && $mapping['id_type_object']['value'] == 21) ||
                   (!empty($post_parameters['id_type_object']) && $post_parameters['id_type_object'] == 21) || 
                   (empty($post_parameters['id_type_object']) && $mapping['id_type_object']['value'] == 21)){
                    //поля 2 шага
                    $mapping['house']['value'] = "";
                    $mapping['house']['unactive'] = true;
                    $mapping['corp']['value'] = "";
                    $mapping['corp']['unactive'] = true;
                    $mapping['cost2meter']['value'] = "";
                    $mapping['cost2meter']['unactive'] = true;
                    $mapping['square_usefull']['value'] = "";
                    $mapping['square_usefull']['unactive'] = true;
                    $mapping['square_usefull']['allow_empty'] = true;
                    $mapping['square_full']['value'] = "";
                    $mapping['square_full']['unactive'] = true;
                    $mapping['square_full']['allow_empty'] = true;
                    $mapping['square_full']['value'] = "";
                    $mapping['id_business_center']['unactive'] = true;
                    $mapping['id_business_center']['allow_empty'] = true;
                    $mapping['id_business_center']['value'] = "";
                    
                    $mapping['square_ground']['allow_empty'] = false;
                    //поля 3 шага
                    $mapping['txt_level']['value'] = "";
                    $mapping['txt_level']['unactive'] = true;
                    $mapping['phones_count']['value'] = "";
                    $mapping['phones_count']['unactive'] = true;
                    $mapping['ceiling_height']['value'] = "";
                    $mapping['ceiling_height']['unactive'] = true;
                    $mapping['parking']['value'] = "";
                    $mapping['parking']['unactive'] = true;
                    $mapping['security']['value'] = "";
                    $mapping['security']['unactive'] = true;
                    $mapping['canalization']['value'] = "";
                    $mapping['canalization']['unactive'] = true;
                    $mapping['hot_water']['value'] = "";
                    $mapping['hot_water']['unactive'] = true;
                    $mapping['id_facing']['value'] = "";
                    $mapping['id_facing']['unactive'] = true;
                    $mapping['id_enter']['value'] = "";
                    $mapping['id_enter']['unactive'] = true;
                    $mapping['heating']['value'] = "";
                    $mapping['heating']['unactive'] = true;
                    $mapping['business_center']['value'] = "";
                    $mapping['business_center']['unactive'] = true;
                }else $mapping['square_ground']['allow_empty'] = true;
                break;
            case 'country':
                $mapping['txt_street']['allow_null'] = true;
                $mapping['txt_street']['allow_empty'] = true;
                $mapping['txt_district']['value'] = "";
                $mapping['txt_district']['unactive'] = true;
                //для участка блокируем поля в загородной:
                if((empty($post_parameters) && $mapping['id_type_object']['value'] == 13) ||
                   (!empty($post_parameters['id_type_object']) && $post_parameters['id_type_object'] == 13) || 
                   (empty($post_parameters['id_type_object']) && $mapping['id_type_object']['value'] == 13)){
                    //поля 2 шага
                    $mapping['rooms']['value'] = "";
                    $mapping['rooms']['unactive'] = true;
                    $mapping['house']['value'] = "";
                    $mapping['house']['unactive'] = true;
                    $mapping['corp']['value'] = "";
                    $mapping['corp']['unactive'] = true;
                    $mapping['square_live']['value'] = "";
                    $mapping['square_live']['unactive'] = true;
                    $mapping['square_live']['allow_empty'] = true;
                    $mapping['square_full']['value'] = "";
                    $mapping['square_full']['unactive'] = false;
                    $mapping['square_full']['allow_empty'] = true;
                    
                    $mapping['square_ground']['allow_empty'] = false;
                    //поля 3 шага
                    $mapping['level_total']['value'] = "";
                    $mapping['level_total']['unactive'] = true;
                    $mapping['year_build']['value'] = "";
                    $mapping['year_build']['unactive'] = true;
                    $mapping['id_roof_material']['value'] = "";
                    $mapping['id_roof_material']['unactive'] = true;
                    $mapping['id_construct_material']['value'] = "";
                    $mapping['id_construct_material']['unactive'] = true;
                    $mapping['id_heating']['value'] = "";
                    $mapping['id_heating']['unactive'] = true;
                    $mapping['id_toilet']['value'] = "";
                    $mapping['id_toilet']['unactive'] = true;
                    $mapping['id_bathroom']['value'] = "";
                    $mapping['id_bathroom']['unactive'] = true;
                    $mapping['phone']['value'] = "";
                    $mapping['phone']['unactive'] = true;
                    $mapping['id_building_progress']['value'] = "";
                    $mapping['id_building_progress']['unactive'] = true;
                }else $mapping['square_ground']['allow_empty'] = true;
                break;
        }
        
        return $mapping;
    }
    
    /**
    * дополнительные поля для формы ЛК
    * 
    * @param mixed $mapping - передается $mapping[$estate]
    * @param mixed $estate
    */
    public function setAdditionalFields($mapping,$estate){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $sprav_list = array(
            'id_building_type' => 'building_types',
            'id_toilet' => 'toilets',
            'id_balcon' => 'balcons',
            'id_elevator' => 'elevators',
            'id_enter' => 'enters',
            'id_window' => 'windows',
            'id_floor' => 'floors',
            'id_hot_water' => 'hot_waters',
            'id_facing' => 'facings',
            'id_heating' => 'heatings',
            'id_river' => 'rivers',
            'id_gas' => 'gases',
            'id_garden' => 'gardens',
            'id_bathroom' => 'bathrooms',
            'id_building_progress' => 'building_progresses',
            'id_electricity' => 'electricities',
            'id_way_type' => 'way_types',
            'id_build_complete' => 'build_complete',
            'id_cottage' => 'cottages',
            'id_housing_estate' => 'housing_estates',
            'id_business_center' => 'business_centers',
            'id_developer_status' => 'developer_statuses',
            'id_ownership' => 'ownerships',
            'id_construct_material' => 'construct_materials',
            'id_water_supply' => 'water_supplies',
            'id_roof_material' => 'roof_materials'
        );
        if($estate == 'country') $sprav_list['id_toilet'] = 'toilets_country';
        foreach($sprav_list as $sprav_field=>$sprav_table){
            if(isset($mapping[$sprav_field])){
                $sprav_rows = $db->fetchall("SELECT id,title FROM ".$sys_tables[$sprav_table]." ORDER BY ".($sprav_table!='build_complete'?' title ': ' year, title'));
                foreach($sprav_rows as $key=>$val){
                    $mapping[$sprav_field]['values'][$val['id']] = $val['title'];
                }
            }
        }
        
        if($estate != 'build'){
            $type_objects = $db->fetchall("SELECT id,title FROM ".$sys_tables['type_objects_'.$estate]." ORDER BY title");
            foreach($type_objects as $key=>$val){
                $mapping['id_type_object']['values'][$val['id']] = $val['title'];
            }
        }
        return $mapping;
    }
    
    /**
    * рассчитываем стоимости для 3 шага ЛК 
    * 
    * @param mixed $estate
    * @param mixed $deal - тип сделки, 1/2
    * @param mixed $id
    * @param mixed $current_status
    */
    public function getStatusesCosts($estate, $deal, $id, $current_status, $target_status = false){
        $result = [];
        if( in_array( $current_status, array(1,3,4,6) ) ) $statuses_available = array(1);
        elseif(!empty($target_status)) $statuses_available = array($target_status);
        elseif( is_array( $this->object_cost_statuses ) ) $statuses_available = array_keys( $this->object_cost_statuses );
        
        if( !in_array( $target_status, array(5) ) ) $statuses_available = array_diff( $statuses_available,($deal == 1 ? array(5) : array(8)));
        
        foreach($statuses_available as $key=>$status){
            $result[$status] = $this->getObjectOperationInfo($estate, $id, $status, false, $deal);
            $result[$status]['info'] = $this->object_cost_statuses[$status];
            //убираем описание для бесплатной публикации платного объекта за 150
            if($status == 5 && empty($result[$status]['cost']) && empty($target_status)) $result[$status]['info']['description'] = "";
            $result[$status]['info']['alias'] = strtr($result[$status]['info']['alias'],'_','-');
        }
        return $result;
    }
    
    /**
    * смотрим сколько осталось бесплатных выделенных(для групповых операций)
    * 
    * @param mixed $status
    */
    function getFreeLeft($status){
        global $auth;
        global $agency_limit;
        $is_agency = $auth->id_agency>0 && $auth->agency_admin == 1 && !empty($auth->agency_id_tarif);
        switch(true){
            case (!empty($auth->id_tarif) && $status==3): $free_left = $auth->promo_left;break;
            case (!empty($auth->id_tarif) && $status==4): $free_left = $auth->premium_left;break;
            case (!empty($auth->id_tarif) && $status==6): $free_left = $auth->vip_left;break;
            case ($is_agency && $status==3): $free_left = $auth->agency_promo - $agency_limit['promo'];break;
            case ($is_agency && $status==4): $free_left = $auth->agency_premium - $agency_limit['premium'];break;
            case ($is_agency && $status==6): $free_left = $auth->agency_vip - $agency_limit['vip'];break;
            default: $free_left = 0;
        }
        return ($free_left<0?0:$free_left);
    }
}
?>