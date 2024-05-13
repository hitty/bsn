<?php
require_once('includes/class.email.php');
class EstateSubscriptions{
    private static $tables = array();   
    private static $data = array();           // Порядок следования данных в заголовку;    
    private static $search_params = array();  // "Чистые" поисковые данные
    private static $search_uri = '';          // "Чистая" поисковая строка
    private static $estate_type = '';          // "тип недвижимости
    private static $deal_type = '';          // "тип недвижимости

    
    public static function Init($real_url, $object_type_title = false, $deal_type = false){ 
        preg_match("#([live|build|country|commercial|inter|zhiloy_kompleks|apartments|business_centers|cottedzhnye_poselki]{1,})\/#msi",$real_url,$match);
        if( empty( $match ) && !strstr( $real_url, 'objects_subscriptions' ) ) return false;
        self::$tables = Config::$sys_tables;    
        
        if(!empty($match)){
            if( strstr($match[1], 'zhiloy_kompleks') != '' || strstr($match[1], 'apartments') != ''){
                 self::$deal_type = '';
                 self::$estate_type = $match[1];
            } elseif(strstr($match[1], 'business_centers') != ''){
                 self::$deal_type = '';
                 self::$estate_type = 'business_centers';
            } elseif(strstr($match[1], 'cottedzhnye_poselki') != ''){
                 self::$deal_type = '';
                 self::$estate_type = 'cottedzhnye_poselki';
            } else {
                self::$estate_type = $match[1];    
                preg_match("#\/([rent|sell]{1,})\/#msi",$real_url,$match);
                self::$deal_type = !empty( $deal_type ) ? $deal_type : ( !empty( $match[1] ) ? $match[1] : '' );
            }
            
            
            self::$data = array('deal_type', 'country', 'elite','rooms_sale', 'rooms', 'obj_type', 'obj_type_title', 'by_the_day','districts','district_areas','cottage_districts','subways','streets', 'group_id', 'way_time','way_type','min_cost','max_cost','build_complete','square_full_from','square_full_to','square_kitchen_from','square_kitchen_to','square_live_from','square_live_to', 'with_photo','radius_geo_id', 'geodata',
                                'level','not_first_level','not_last_level','housing_estate','business_center','cottage','geodata_selected','elevator','facing','decoration','toilet','balcon','heating','ceiling_height_from','ceiling_height_to','enter','water_supply','security','parking','building_type','electricity','id_heating','id_electricity','bathroom','ownership','gas','class',
                                'user_objects', 'id_subscription',
                                'contractor', 'asignment', 'low_rise', '214_fz', 'apartments',
                                'object_type', 'developer', 'seller', 'agency', 'direction'
            );   
            $parsed_url = parse_url($real_url);
            $gets = $string = array();
            if(!empty($parsed_url['query'])){
                $qry = explode('&', $parsed_url['query']);
                foreach($qry as $q) {
                    list($key,$val) = explode('=',$q.'=');
                    if(in_array($key,self::$data)) {
                        $gets[$key] = $val;
                        $string[] = $q;
                    }
                }
            }
            //исключаем ID подписки
            foreach($string as $k=>$item) if(strstr($item,'id_subscription')) unset($string[$k]);
            self::$search_uri = $parsed_url['path'].(!empty($string)?'?'.implode('&',$string):'');
            if(!empty($match[2])) $gets['deal_type'] = $match[2];
            elseif(self::$estate_type == 'build') $gets['deal_type'] = 'sell';
            if(!empty($object_type_title) && empty(self::$search_params['obj_type'])) $gets['obj_type_title'] = $object_type_title;
            self::$search_params = $gets;          
        }
    }                
    /**
    * Получение списка возможной периодичности подписок на обновления объектов
    * @return array список возможных вариантов периодичности
    */
    public static function getPeriodList(){
        global $db;
        return $db->fetchall("SELECT `id`,`value` FROM ".self::$tables['objects_subscriptions_periods']);
    }
    /**
    * Получение суммарного количества новых объектов по подпискам пользователя
    * @return string количество новых объектов в подписках
    */  
    public static function getAmount(){
        global $auth, $db;
        self::$tables = Config::$sys_tables;
        $amount = '';        
        if ($auth->isAuthorized()) {
            $sum_new_objects = $db->fetch("SELECT SUM(new_objects) as summ FROM ".self::$tables['objects_subscriptions']." WHERE id_user = ?", $auth->id);
            if ($sum_new_objects ['summ'] > 0) $amount = $sum_new_objects ['summ'];
            else $amount = 0;
        }
        return $amount;
    }
    /**
    * Отправка письма об успешной подписке
    * @param int $estate_type               название недвижимости
    * @param string $deal_type_name         название типа сделки
    * @param string $url                    URL поискового запроса
    * @param string $title                  Полное название поискового запроса
    * @param integer $id                    ID подписки
    * @param string $email                  Email для отправки
    * @param string $confirm_key            проверочный код
    * @return bool результат выполнения функции
    */
    private static function successSubscribe($estate_type_name, $deal_type_name, $url, $title, $id, $email, $confirm_key = ''){
        $mailer = new EMailer('mail');
        $info = array(
                        'estate_type_name' => $estate_type_name
                        ,'deal_type_name' => $deal_type_name
                        ,'url' => $url
                        ,'title' => $title
                        ,'id' => $id
                        ,'email' => $email
                        ,'confirm_key' => $confirm_key
                        ,'bsn_url' => Host::GetWebPath('/')
                        ,'host' => Host::$host
        );          
        Response::SetArray('info', $info);
        // формирование html-кода письма по шаблону
        $eml_tpl = new Template('/modules/objects_subscriptions/templates/success_subscription_mail.html');
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet, $html);
        // параметры письма
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Создана новая подписка на поисковый запрос  '.Host::$host);
        $mailer->Body = $html;
        $mailer->AltBody = strip_tags($html);
        $mailer->IsHTML(true);
        $mailer->AddAddress($email, iconv('UTF-8',$mailer->CharSet, ""));
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = 'bsn.ru';                                                                
        return $mailer->Send();
    }
    /**
    * Обнуление счетчика новых объектов подписки
    * @param int $id               id подписки
    * @return bool результат выполнения функции
    */
    public static function resetCounter($id){
        global $db;
        return $db->querys("UPDATE ".self::$tables['objects_subscriptions']." SET `new_objects` = 0 WHERE `id` = ?, `last_seen` = NOW()",$id);
    }
    /**
    * Получение списка подписок на обновления (для текущего пользователя)
    * @return array список подписок
    */
    public static function getList(){
        global $db,$auth;
        if (!$auth->isAuthorized()) return false;
        $rows = $db->fetchall("SELECT 
                                    ".self::$tables['objects_subscriptions'].".*,
                                    DATE_FORMAT(".self::$tables['objects_subscriptions'].".`create_datetime`,'%d.%m.%y') as `create_date`, 
                                    DATE_FORMAT(".self::$tables['objects_subscriptions'].".`last_delivery`,'%e %M') as `last_delivery_date`, 
                                    DATE_FORMAT(".self::$tables['objects_subscriptions'].".`last_delivery`,'%d.%m.%y') as `last_delivery_date_dmyformatted`, 
                                    ".self::$tables['objects_subscriptions_periods'].".`value` AS `period`
                               FROM ".self::$tables['objects_subscriptions']."
                               LEFT JOIN ".self::$tables['objects_subscriptions_periods']." ON ".self::$tables['objects_subscriptions_periods'].".`id` = ".self::$tables['objects_subscriptions'].".`id_period` 
                               WHERE ".self::$tables['objects_subscriptions'].".`confirmed` = ? 
                                     AND ".self::$tables['objects_subscriptions'].".`id_user` = ? 
                               ORDER BY ".self::$tables['objects_subscriptions'].".`estate_type`, 
                                     ".self::$tables['objects_subscriptions'].".`deal_type`, 
                                     ".self::$tables['objects_subscriptions'].".`create_datetime` DESC",
        false, 1, $auth->id);      
        foreach($rows as $k=>$row) $rows[$k]['title'] = str_replace('подписка на объекты','', $row['title']);
        if(empty($rows)) return array();
        return $rows;
    }
    /**
    * Удаление подписки пользователя
    * @param int $id   id подписки
    * @return bool результат        выполнения функции
    */
    public static function Remove($id){
        global $db,$auth;
        self::$tables = Config::$sys_tables;
        if (!$auth->isAuthorized()) return false;
        $sql = "DELETE FROM ".self::$tables['objects_subscriptions']." WHERE `id`=? AND `id_user`=?";
        $result = $db->querys($sql,$id,$auth->id);
        return !empty($result);
    }
    /**
    * Создание подписки
    * @param string $title       заголовок подписки
    * @param int    $period периодичность отсылки письма об обновлении
    * @param string $url         ссылка на страницу с объектами подписки
    * @param int    $estate_type тип недвижимости
    * @param string $deal_type   тип сделки (sell/rent)
    * @param string $email       почтовый адрес подписываемого пользователя (нужно, если человек не авторизован)
    * @return bool результат выполнения функции
    */
    public static function Create($title, $url, $estate_type, $deal_type, $email = false){
        global $db, $auth;
         
        // Получение название типа недвижимости
        $estate_type_name = ''; 
        foreach(Config::$values['object_types'] as $k=>$value){ 
            if ($value['key'] == $estate_type){
                $estate_type_name = $value['name'];
                break;    
            }
        }  
        if (in_array($estate_type,array(1,3,4))) $estate_type_name .= ' недвижимость';
        $deal_type_name = $deal_type == 'sell' ? "Покупка" : "Аренда";
        //запись для авторизованного пользователя
        if ($auth->isAuthorized()){
            $result = $db->querys("INSERT INTO ".self::$tables['objects_subscriptions']."(id_user,email,create_datetime,last_delivery,last_seen,url,title,estate_type,deal_type,confirmed) 
                                          VALUES (?,?,NOW(),NOW(),NOW(),?,?,?,?,?)",
                                          $auth->id, $auth->email, $url, $title, $estate_type, $deal_type, 1);
            //отправка письма о подписке (без проверочного кода)
            return self::successSubscribe($estate_type_name, $deal_type_name, $url, $title, $db->insert_id, $auth->email); 
        } else {
            //пользователь не авторизован
            $item = $db->fetch("SELECT * FROM ".self::$tables['users']." WHERE email=?",$email); 
            $id_user = !empty($item['id']) ? $item['id'] : 0;
            $confirm_key = md5(microtime(true));
            $result = $db->querys("INSERT INTO ".self::$tables['objects_subscriptions']."
                                                (id_user, email, create_datetime, last_delivery, last_seen, url, title, estate_type, deal_type, confirm_key) 
                                         VALUES (?,?,NOW(),NOW(),NOW(),?,?,?,?,?)", 
                                         $id_user, $email, $url, $title, $estate_type, $deal_type, $confirm_key); 
            //отправка письма о подписке (c проверочным кодом)
            return self::successSubscribe($estate_type_name, $deal_type_name, $url, $title, $db->insert_id, $email, $confirm_key); 
        }
        return false;
    }

    private static function getNameByType(){
        global $db;
        $table = "";
        switch(self::$estate_type){
            case 'country':
            case 'live':
            case 'commercial':
                $table = 'object_type_groups';
            break;
            case 'inter':
                $table = 'type_objects_inter';
            break;
            case 'cottedzhnye_poselki':
            case 'business_centers':
            case 'zhiloy_kompleks':
            case 'apartments':
                return array('', '');
            case 'build':
                return array('квартир в новостройках', 'Квартиры в новостройке');
            break;
        }
        if(empty(self::$search_params['obj_type'])) return false;
        $title = self::getObjectsString('title_genitive_plural',$table, self::$search_params['obj_type'],', ');
        $description = self::getObjectsString('title',$table, self::$search_params['obj_type'],', ');
        if(!empty($title) || !empty($description)) return array($title, $description);
        return array(
                        self::getObjectsString('title_genitive_plural', 'object_type_groups', self::$search_params['obj_type'],', '),
                        self::getObjectsString('title', 'object_type_groups', self::$search_params['obj_type'],', ')
        );
    }
    
    public static function getObjectsString($fieldname, $tablename, $ids, $glue){
        global $db;   
        if(empty($tablename)) return '';
        $ids = implode(",", array_map("Convert::toInt",explode(',',$ids))); 
        $tablename = !empty(self::$tables[$tablename]) ? $tablename : $tablename.'s';
        $sql = "SELECT ".$fieldname." FROM ".self::$tables[$tablename]." WHERE `id` IN (".$ids.")";
        $arr = $db->fetchall($sql);  
        $list = array();
        for ($i=0;$i<count($arr);$i++){
            $list[] = $arr[$i][$fieldname];
        }
        return implode($glue,$list);  
    }      

    public static function getRangeObjectsString($ids, $glue){
        global $db;   
        $ids = implode(",", array_map("Convert::toInt",explode(',',$ids))); 
        $sql = "SELECT * FROM ".self::$tables['estate_search_params']." WHERE `id` IN (".$ids.") ORDER BY `id`";
        $arr = $db->fetchall($sql);
        if(empty($arr)) return false;
        $list = $array = array();
        $from_value = $to_value = $count = 0;
        foreach($arr as $k=>$item){
            if(!empty($item['from_value']) && $to_value == $item['from_value']){
                
            } else {
                ++$count;
                $list[$count]['from_value'] = squareformat($item['from_value']);
            }
            $to_value = $list[$count]['to_value'] = squareformat($item['to_value']);            
            $from_value = squareformat($item['from_value']);            
        }
        foreach($list as $item){
            $array[] = empty($item['from_value']) ? 'до ' . $item['to_value'] : ( empty($item['to_value']) ? 'от ' . $item['from_value'] : ' от ' . $item['from_value'] .' до ' . $item['to_value']);
        }
        return implode($glue, $array) . ' ' . $arr[0]['prefix'];  
    }      

    public static function checkSubscribeOpportunity(){ // Проверка необходимости отображения кнопки "Подписаться на обновления"
        global $auth, $db, $this_page;
        if (!$auth->isAuthorized()) return true;                                         // для неавторизованных пользователей кнопка отображается всегда
        
        //$params = $this_page->real_url;
        if(empty(self::$search_params)) return false; // если фильтры не заданы 
        
        // если уже подписан
        $item = $db->fetch("SELECT * FROM ".self::$tables['objects_subscriptions']." WHERE `id_user` = ? AND ? = `url`", $auth->id, self::$search_uri);
        if (!empty($item)) return false;
        if (empty(self::$search_params['obj_type_title']) && !empty(self::$search_params['obj_type']) && self::$search_params['obj_type']==2 && !empty(self::$search_params['rooms'])) return false;   // если тип объекта "Комната" + указано количество комнат
        return true;    
    }
    
    public static function getTelegramTitle($search_params){
        
        if(empty($search_params)) return false;
          
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $description = array();
        
        if(!empty($search_params['rent'])) $description[] = ($search_params['rent']['value'] == 1 ? "Снять" : "Купить");
        
        if(!empty($search_params['id_type_object'])){
            $type_object = $search_params['id_type_object'];
            if(!empty($type_object['value'])) $type_object_id = $type_object['value'];
            
            $type_object_title = $db->fetch("SELECT title_accusative FROM ".$sys_tables['type_objects_live']." WHERE id = ".$type_object_id."");
            $type_object_title = (!empty($type_object_title['title_accusative']) ? $type_object_title['title_accusative'] : "");
            
            if(!empty($search_params['rooms_sale'])){
                $rooms_sale = $search_params['rooms_sale']['value'];
                if($type_object_id == 1){
                    switch(true){
                        case $rooms_sale == 1: $rooms_title = "однокомнатную";break;
                        case $rooms_sale == 2: $rooms_title = "двухкомнатную";break;
                        case $rooms_sale == 3: $rooms_title = "трехкомнатную";break;
                        case $rooms_sale == 4: $rooms_title = "4+комнатную";break;
                    }
                    $type_object_title = ($search_params['rooms_sale']['value'] == 0 ? $type_object_title."-студию" : $rooms_title." ".$type_object_title);
                }
            }
            
            $description[] = $type_object_title;
        }
        
        if(!empty($search_params['by_the_day']) && $search_params['rent']['value'] == 1){
            $description[] = ($search_params['by_the_day']['value'] == 1 ? "посуточно" : "на длительный срок");
        }
        
        if(!empty($search_params['cost'])){
            $cost_description = "";
            $cost = $search_params['cost'];
            
            if(!empty($cost['from'])) $cost_description =" от ".$cost['from']."р";
            if(!empty($cost['to'])) $cost_description .=" до ".$cost['to']."р";
            if(!empty($cost['value'])) $cost_description .= " ровно за ".$cost['value']."р";
            
            $description[] = $cost_description;
        }
        
        if(!empty($search_params['id_district']) && (!empty($search_params['id_district']['value']) || !empty($search_params['id_district']['set'])) ){
            $districts_description = "";
            $districts = $search_params['id_district'];
            
            if(!empty($districts['value'])) $district_ids = $districts['value'];
            elseif(!empty($districts['set'])) $district_ids = implode(',',$districts['set']);
            $district_titles = $db->fetchall("SELECT title_prepositional FROM ".$sys_tables['districts']." WHERE id IN (".$district_ids.")",'title_prepositional');
            $districts_description = "в ".implode('/',array_keys($district_titles))." район".(count($district_titles) > 1 ? "ах" : "е");
            
            $description[] = $districts_description;
        }
        
        if(!empty($search_params['id_subway']) && (!empty($search_params['id_subway']['value']) || !empty($search_params['id_subway']['set'])) ){
            $subways_description = "";
            $subways = $search_params['id_subway'];
            
            if(!empty($subways['value'])) $subways_ids = $subways['value'];
            elseif(!empty($subways['set'])) $subways_ids = implode(',',$subways['set']);
            $subway_titles = $db->fetchall("SELECT title FROM ".$sys_tables['subways']." WHERE id IN (".$subways_ids.")",'title');
            $subways_description = "у метро ".implode('/',array_keys($subway_titles));
            
            $description[] = $subways_description;
        }
        
        return implode(' ',$description);
    }
    
    public static function getTitle($search_params = false, $params = false, $only_title = false, $excluded_params = false, $with_description = false){ // Получение заголовка подписки
        global $db;
        $title = $description = array();
        if(!empty($search_params)) self::$search_params = $search_params;
        if(self::$estate_type != 'build' && self::$estate_type != 'live' && !empty($params['obj_type']))  self::$search_params['obj_type'] = $params['obj_type'];
        if( self::$estate_type == 'live' && empty($params['obj_type'] ) && !empty($params['rooms'] ) )  self::$search_params['obj_type'] = 1;
        if(!empty(self::$search_params['obj_type_title'])) return self::$search_params['obj_type_title'];
        if(empty($excluded_params)){
            $title['title'][] = self::$deal_type == 'sell' ? 'Продажа' : ( self::$deal_type == 'rent' ? 'Аренда' : '' );
            if(empty(self::$search_params['obj_type'])) {
                switch(self::$estate_type){
                    case 'build' : 
                        self::$search_params['obj_type'] = 1;
                        break;
                    case 'live' : 
                        if( empty( $params['rooms'] ) && empty( $params['obj_type'] ) ) $title['title'][] = ' жилой недвижимости'; 
                        $description[] = 'Жилая недвижимость';
                        break;
                    case 'country' : 
                        $title['title'][] = ' загородной недвижимости'; 
                        $description[] = 'Загородная недвижимость'; 
                        break;
                    case 'commercial' : 
                        $title['title'][] = ' коммерческой недвижимости'; 
                        $description[] = 'Коммерческая недвижимость'; 
                        break;
                    case 'inter' : 
                        $title['title'][] = ' зарубежной недвижимости'; 
                        $description[] = 'Зарубежная недвижимость'; 
                        break;
                    case 'zhiloy_kompleks' : 
                        $title['title'][] = empty(self::$search_params['housing_estate']) || (!empty(self::$search_params['housing_estate']) && count(self::$search_params) > 1 ) ? 'Жилые комплексы' : ''; 
                        $description[] = empty(self::$search_params['housing_estate']) || (!empty(self::$search_params['housing_estate']) && count(self::$search_params) > 1 ) ? 'Жилые комплексы' : ''; 
                        break;
                    case 'apartments' : 
                        $title['title'][] = empty(self::$search_params['housing_estate']) || (!empty(self::$search_params['housing_estate']) && count(self::$search_params) > 1 ) ? 'Апартаменты' : ''; 
                        $description[] = empty(self::$search_params['housing_estate']) || (!empty(self::$search_params['housing_estate']) && count(self::$search_params) > 1 ) ? 'Апартаменты' : ''; 
                        break;
                    case 'business_centers' : 
                        $title['title'][] = empty(self::$search_params['business_center']) || (!empty(self::$search_params['business_center']) && count(self::$search_params) > 1 ) ? 'Бизнес-центры' : ''; 
                        $description[] = empty(self::$search_params['business_center']) || (!empty(self::$search_params['business_center']) && count(self::$search_params) > 1 ) ? 'Бизнес-центры' : ''; 
                        break;
                    case 'cottedzhnye_poselki' : 
                        $title['title'][] = empty(self::$search_params['cottage']) || (!empty(self::$search_params['cottage']) && count(self::$search_params) > 1 ) ? 'Коттеджные поселки' : ''; 
                        $description[] = empty(self::$search_params['cottage']) || (!empty(self::$search_params['cottage']) && count(self::$search_params) > 1 ) ? 'Коттеджные поселки' : ''; 
                        break;
                }
            }
        }
        foreach (self::$data as $k=>$v){
            if (!empty(self::$search_params[$v]) || isset(self::$search_params[$v]) || $v == 'obj_type'){

                if ($v == 'rooms_sale'){
                    //команты в студиях, в квартирах, в квартирах с комнатностью меньше кол-ва продаваемых комнат
                    
                    if(!empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 2){
                        
                            $rooms_numbers = mb_split(',',self::$search_params[$v]);
                            for ($i=0;$i<count($rooms_numbers);$i++){
                                $rooms_numbers[$i] = Convert::ToInt($rooms_numbers[$i]);
                                if ($rooms_numbers[$i] == 0) return false;
                                    
                                if ($rooms_numbers[$i]>0)
                                    switch($rooms_numbers[$i]){
                                        case 1: $rooms_numbers[$i] = 'одной'; break;
                                        case 2: $rooms_numbers[$i] = 'двух'; break;
                                        case 3: $rooms_numbers[$i] = 'трех'; break;
                                        case 4: $rooms_numbers[$i] = 'четырех'; break;
                                    }
                            }
                            $title['title'][] = implode(', ',$rooms_numbers). ( count($rooms_numbers) == 1 && $rooms_numbers[0] == 0 ? "" : "" );
                    }
                }
                if ($v == 'rooms'){
                    
                    $rooms_numbers = $description_rooms_numbers = mb_split(',',self::$search_params[$v]);
                    for ($i=0;$i<count($rooms_numbers);$i++){
                        $rooms_numbers[$i] = Convert::ToInt($rooms_numbers[$i]);
                        if ($rooms_numbers[$i] == 0){
                            if( ! ( !empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 2 ) )$rooms_numbers[$i] = "квартиры-студии";
                            else return false;
                        }    
                        
                        if ($rooms_numbers[$i]>0)
                            switch($rooms_numbers[$i]){
                                case 1: 
                                    $rooms_numbers[$i] = 'однокомнатных'; 
                                    $description_rooms_numbers[$i] = !empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 2 ? 'однокомнатных' : 'Однокомнатные'; 
                                    break;
                                case 2: 
                                    $rooms_numbers[$i] = 'двухкомнатных'; 
                                    $description_rooms_numbers[$i] = !empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 2 ? 'двухкомнатных': 'Двухкомнатные'; 
                                    break;
                                case 3: 
                                    $rooms_numbers[$i] = 'трехкомнатных'; 
                                    $description_rooms_numbers[$i] = !empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 2 ? 'трехкомнатных' : 'Трехкомнатные'; 
                                    break;
                                default:
                                case 4: 
                                    $rooms_numbers[$i] = 'четырехкомнатных+'; 
                                    $description_rooms_numbers[$i] = !empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 2 ? 'четырехкомнатных' : 'Четырехкомнатные+'; 
                                    break;
                            }
                    }
                    if(!empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 2) {
                        if(!empty(self::$search_params['rooms_sale']) && count(self::$search_params['rooms_sale']) == 1 && self::$search_params['rooms_sale'][0] == 1) {
                            $title['title'][] = 'комнаты в';
                            $description[] = 'комнаты в';
                        } else {
                            $title['title'][] = 'комнат в';
                            $description[] = 'Комнаты в';
                        }
                    }
                    $title['title'][] = implode(', ',$rooms_numbers). ( count($rooms_numbers) == 1 && $rooms_numbers[0] == 0 ? "" : "" );
                    $description[] = implode(', ',$description_rooms_numbers). ( count($rooms_numbers) == 1 && $rooms_numbers[0] == 0 ? "" : "" );
                    if(!empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 2) $title['title'][] = $description[] = 'квартирах';
                }
                if ($v == 'obj_type_title' && !empty(self::$search_params['obj_type_title'])){
                        $title['title'][] = self::$search_params['obj_type_title'];
                    
                }    
                
                if ($v == 'min_cost'){
                    $min_cost = self::$search_params[$v];
                    if ($min_cost>=1000000) $min_cost = rtrim( rtrim( number_format( $min_cost/1000000, 2, ".", "" ), "0" ), "." ) . " млн.";
                    else if ($min_cost>=1000) $min_cost = rtrim( rtrim( number_format( $min_cost/1000, 2, ".", "" ), "0" ), "." ) . " тыс.";
                    else $min_cost = rtrim( rtrim( number_format( $min_cost, 2, ".", "" ), "0" ), "." );
                    $description[] = $title['title'][] = "от " . $min_cost . " руб.";
                    
                }
                if ($v == 'max_cost'){
                    $max = self::$search_params[$v];
                    if ($max>=1000000) $max = rtrim( rtrim( number_format( $max/1000000, 2, ".", "" ), "0" ), "." ) . " млн.";
                    else if ($max>=1000) $max = rtrim( rtrim( number_format( $max/1000, 2, ".", "" ), "0" ), "." ) . " тыс.";
                    else $max = rtrim( rtrim( number_format( $max, 2, ".", "" ), "0" ), "." );
                    $description[] = $title['title'][] = "до " . $max . " руб.";
                }                           
                if(empty($excluded_params)){
                    if ( $v == 'obj_type' && ( empty($rooms_numbers) || (!empty(self::$search_params['obj_type']) && self::$search_params['obj_type'] == 1) ) ){
                        if( !( !empty($rooms_numbers) && $rooms_numbers[0] == 'квартиры-студии' && count($rooms_numbers) == 1) ) {
                            list( $title['title'][], $description[] ) = self::getNameByType();
                        }
                    }
                }
                
                if ($v == 'district_areas'){
                    $districts = self::getObjectsString('title_prepositional','geodata',self::$search_params[$v],', ');
                    $districts = explode(', ',$districts);
                    for ($i=0;$i<count($districts);$i++){               // удаление предлогов
                        $districts_words = explode(' ',$districts[$i]); // отделение предлога от названия района
                        $districts[$i] = $districts_words[count($districts_words)-1];
                    }                
                    $title['district_areas'] = "в ".implode(', ',$districts).(count($districts)==1?" районе":" районах")." ЛО ";
                }
                if ($v == 'cottage_districts'){
                    $districts = self::getObjectsString('title_prepositional','district_areas',self::$search_params[$v],', ');
                    $districts = explode(', ',$districts);
                    for ($i=0;$i<count($districts);$i++){               // удаление предлогов
                        $districts_words = explode(' ',$districts[$i]); // отделение предлога от названия района
                        $districts[$i] = $districts_words[count($districts_words)-1];
                    }                
                    $title['cottage_districts'] = "в ".implode(', ',$districts).(count($districts)==1?" районе":" районах")." ЛО ";
                }
                if ($v == 'districts'){
                    $districts = self::getObjectsString('title_prepositional','districts',self::$search_params[$v],', ');
                    $districts = explode(',',$districts);         
                    $title['districts'] = "в ".implode(', ',$districts).(count($districts)==1?" районе":" районах");
                }
                if ($v == 'subways'){
                    $title['subways'] = (!empty($only_title) ? "у метро " : "").self::getObjectsString('title','subways',self::$search_params[$v],', ');    
                }
                if ($v == 'country'){
                    $countries = self::getObjectsString('title_genitive','inter_countries',self::$search_params[$v],', ');
                    $countries = explode(',',$countries);         
                    $title['districts'] = "в ".implode(', ',$countries);
                }

                if ($v == 'build_complete'){
                    $build_completes = self::getObjectsString('title','build_complete',self::$search_params[$v],', ');
                    if(!empty($build_completes)){
                        switch(self::$search_params[$v]){
                            case 4: $title['other_params'][] = 'в сданном доме'; break;
                            default: $title['other_params'][] = "со сдачей в ".$build_completes.' году'; break;
                        }
                    }
                    
                }
                
                if($v == 'user_objects') {
                    switch(self::$search_params[$v]){
                        case '1': $title['other_params'][] = 'от частных лиц '; break;
                        case '2': $title['other_params'][] = 'от агентств '; break;
                        case '3': $title['other_params'][] = 'от застройщика '; break;
                    }
                }
                if ($v == 'by_the_day' && !empty(self::$search_params['obj_type']) && self::$deal_type == 'rent' && self::$search_params['by_the_day']==1) $title['other_params'][] = 'только посуточно ';
                if ($v == 'with_photo') $title['other_params'][] = 'только с фото ';
                
                if ($v == 'square_full_from') $title['other_params'][] = "общая площадь от ".Convert::ToSquare(self::$search_params[$v])." кв.м.";
                if ($v == 'square_full_to') {
                    $title['other_params'][] = (empty(self::$search_params['square_full_from']) ? "общая площадь до " : " до " ) .Convert::ToSquare(self::$search_params[$v])." кв.м.";
                }

                if ($v == 'square_live_from') $title['other_params'][] = "жилая площадь от ".Convert::ToSquare(self::$search_params[$v])." кв.м.";
                if ($v == 'square_live_to') $title['other_params'][] = (empty(self::$search_params['square_live_from']) ? "жилая площадь до " : " до " ) .Convert::ToSquare(self::$search_params[$v])." кв.м.";

                if ($v == 'square_kitchen_from') $title['other_params'][] = "площадь кухни от ".Convert::ToSquare(self::$search_params[$v])." кв.м.";
                if ($v == 'square_kitchen_to') $title['other_params'][] .= (empty(self::$search_params['square_kitchen_from']) ? "площадь кухни до " : " до " ) .Convert::ToSquare(self::$search_params[$v])." кв.м.";

                if($v == 'level') $title['other_params'][] = 'на '.self::$search_params[$v].' этаже';
                if($v == 'not_first_level') $title['other_params'][] = 'этаж не первый';
                if($v == 'not_last_level') $title['other_params'][] = 'этаж не последний';
                if($v == 'housing_estate') $title['other_params'][] = ( count(self::$search_params) > 1 ? 'в ' : '' ) . 'ЖК «'.self::getObjectsString('title','housing_estates',self::$search_params[$v],', ').'»'; 
                if($v == 'business_center') $title['other_params'][] = ( count(self::$search_params) > 1 ? 'в ' : '' ) . 'БЦ «'.self::getObjectsString('title','business_centers',self::$search_params[$v],', ').'»'; 
                if($v == 'cottage') $title['other_params'][] = ( count(self::$search_params) > 1 ? 'в ' : '' ) . 'КП «'.self::getObjectsString('title','cottages',self::$search_params[$v],', ').'»'; 
                if($v == 'geodata_selected') {
                    switch(self::$search_params[$v]){
                        case 'districts': $title['other_params'][] = 'в районах СПб'; break;
                        case 'district_areas': $title['other_params'][] = 'в районах ЛО '; break;
                        case 'subways': $title['other_params'][] = 'около метро '; break;
                    }
                }
                if($v == 'way_time') $title['other_params'][] = self::$search_params[$v];
                if($v == 'way_type') $title['other_params'][] = self::getObjectsString('title','way_types',self::$search_params[$v],', ');
                if($v == 'building_type') {
                    $building_type = self::getObjectsString('title_prepositional','building_types',self::$search_params[$v],', ');
                    if(!empty($building_type)) $title['other_params'][] = 'в ' . $building_type . ( $building_type != 'старом фонде' ? ' доме ' : '' );
                }
                if($v == 'elevator') {
                    $elevator = self::getObjectsString('title_prepositional', 'elevators', self::$search_params[$v],', ');
                    if(!empty($elevator)) $title['other_params'][] = $elevator;
                }
                if($v == 'direction') {
                    $direction = self::getObjectsString('title_prepositional', 'directions', self::$search_params[$v],', ');
                    if(!empty($direction)) $title['other_params'][] = $direction;
                }
                if($v == 'facing') {
                    $facing = self::getObjectsString('title_prepositional', 'facings', self::$search_params[$v],', ');
                    if(!empty($facing)) $title['other_params'][] = $facing;
                }
                if($v == 'decoration') {
                    $decoration = self::getObjectsString('title_prepositional', 'decorations', self::$search_params[$v],', ');
                    if(!empty($decoration)) $title['other_params'][] = $decoration;
                }
                if($v == '214_fz' && self::$search_params[$v] == 1) $title['other_params'][] = 'с ФЗ 214';
                if($v == 'apartments' && self::$search_params[$v] == 1) $title['other_params'][] = 'апартаментами';
                if($v == 'low_rise' && self::$search_params[$v] == 1) $title['other_params'][] = 'малоэтажной застройки';
                if($v == 'contractor' && self::$search_params[$v] == 1) $title['other_params'][] = ( self::$estate_type == 'zhiloy_kompleks' ? 'с квартирами ' : '' ) . 'в подряде';
                if($v == 'asignment' && self::$search_params[$v] == 1) $title['other_params'][] = ( self::$estate_type == 'zhiloy_kompleks' ? 'с квартирами ' : '' ) . 'по переуступке';
                if($v == 'toilet') {
                    $toilet = self::getObjectsString('title_prepositional','toilets',self::$search_params[$v],', ');
                    if(!empty($toilet)) $title['other_params'][] = $toilet;
                }

                if($v == 'balcon') { 
                    $balcon = self::getObjectsString('title_prepositional','balcons',self::$search_params[$v],', ');
                    if(!empty($balcon)) $title['other_params'][] = $balcon;
                }
                if($v == 'heating') { 
                    if(self::$estate_type == 'commercial'){
                        switch(self::$search_params[$v]){
                            case '1': $title['other_params'][] = 'с отоплением'; break;
                            case '2': $title['other_params'][] = 'без отопления'; break;
                        }
                        
                    } else {
                        $heating = self::getObjectsString('title_prepositional','heatings',self::$search_params[$v],', ');
                        if(!empty($heating)) $title['other_params'][] = $heating;
                    }
                }
                if($v == 'enter') { 
                    $enter = self::getObjectsString('title_prepositional','enters',self::$search_params[$v],', ');
                    if(!empty($enter)) $title['other_params'][] = $enter;
                }
                if($v == 'water_supply') { 
                    $water_supply = self::getObjectsString('title_prepositional','water_supplies',self::$search_params[$v],', ');
                    if(!empty($water_supply)) $title['other_params'][] = $water_supply;
                }
                if($v == 'security') { 
                    $title['other_params'][] = 'с охраной';
                }
                if($v == 'parking') { 
                    switch(self::$search_params[$v]){
                        case '1': $title['other_params'][] = 'с паркингом '; break;
                        case '2': $title['other_params'][] = 'без паркинга '; break;
                    }
                }
                if($v == 'electricity') { 
                    $electricity = self::getObjectsString('title_prepositional','electricities',self::$search_params[$v],', ');
                    if(!empty($electricity)) $title['other_params'][] = $electricity;
                }
                if($v == 'id_electricity') { 
                    $id_electricity = self::getObjectsString('title_prepositional','electricities',self::$search_params[$v],', ');
                    if(!empty($id_electricity)) $title['other_params'][] = $id_electricity;
                }
                if($v == 'id_heating') { 
                    $id_heating = self::getObjectsString('title_prepositional','heatings',self::$search_params[$v],', ');
                    if(!empty($id_heating)) $title['other_params'][] = $id_heating;
                }
                if($v == 'ownership') { 
                    $ownership = self::getObjectsString('title_prepositional','ownerships',self::$search_params[$v],', ');
                    if(!empty($ownership)) $title['other_params'][] = $ownership;
                }
                if($v == 'bathroom') { 
                    $bathroom = self::getObjectsString('title_prepositional','bathrooms',self::$search_params[$v],', ');
                    if(!empty($bathroom)) $title['other_params'][] = $bathroom;
                }
                if($v == 'gas') { 
                    $gas = self::getObjectsString('title_prepositional','gases',self::$search_params[$v],', ');
                    if(!empty($gas)) $title['other_params'][] = $gas;
                }
                                
                
                if($v == 'class') {
                    if(!empty(self::$estate_type)){
                        if(self::$estate_type == 'zhiloy_kompleks' || self::$estate_type == 'apartments') $title['other_params'][] = self::getObjectsString('title','housing_estate_classes',self::$search_params[$v],', ') . ' класса';
                        else {
                            switch(self::$search_params[$v]){
                                case 1: case 'a': $title['other_params'][] = 'класса А'; break;
                                case 2: case 'b':$title['other_params'][] = 'класса В'; break;
                                case 3: case 'bplus':$title['other_params'][] = 'класса В+'; break;
                                case 4: case 'c':$title['other_params'][] = 'класса С'; break;
                                case 5: case 'no':$title['other_params'][] = 'без класса'; break;
                            }
                        }
                    }
                }
                if(!empty(self::$estate_type) && (self::$estate_type == 'zhiloy_kompleks' || self::$estate_type == 'apartments') ) {
                    if ($v == 'developer'){
                        $developer = $db->fetch("SELECT  
                                            ".self::$tables['agencies'].".title
                                    FROM ".self::$tables['agencies']." 
                                    LEFT JOIN ".self::$tables['users']." ON ".self::$tables['users'].".id_agency = ".self::$tables['agencies'].".id
                                    WHERE ".self::$tables['users'].".id = ?
                                    GROUP BY  ".self::$tables['agencies'].".id", self::$search_params[$v]);
                        $title['other_params'][] = "от застройщика «" . trim($developer['title']) . "»"; 
                    }
                }
                
                if ($v == 'agency'){
                    $developer = $db->fetch("SELECT  
                                        ".self::$tables['agencies'].".title
                                FROM ".self::$tables['agencies']." 
                                LEFT JOIN ".self::$tables['users']." ON ".self::$tables['users'].".id_agency = ".self::$tables['agencies'].".id
                                WHERE ".self::$tables['users'].".id = ?
                                GROUP BY  ".self::$tables['agencies'].".id", self::$search_params[$v]);
                    $title['other_params'][] = "от компании «" . trim($developer['title']) . "»"; 
                }
                
                
                if ($v == 'ceiling_height_from') $title['other_params'][] = "с высотой потолков от".Convert::ToSquare(self::$search_params[$v])." м ";
                if ($v == 'ceiling_height_to') $title['other_params'][] = (empty(self::$search_params['ceiling_height_from']) ? "с высотой потолков до " : "до " ) .Convert::ToSquare(self::$search_params[$v])." м";
                
                
                if(!empty(self::$estate_type) && self::$estate_type == 'cottedzhnye_poselki'){
                    if ($v == 'developer'){
                        $developer = $db->fetch("SELECT  
                                            ".self::$tables['agencies'].".title
                                    FROM ".self::$tables['agencies']." 
                                    LEFT JOIN ".self::$tables['users']." ON ".self::$tables['users'].".id_agency = ".self::$tables['agencies'].".id
                                    WHERE ".self::$tables['users'].".id = ?
                                    GROUP BY  ".self::$tables['agencies'].".id", self::$search_params[$v]);
                        $title['other_params'][] = "от застройщика «" . trim($developer['title']) . "»"; 
                    }
                     if ($v == 'object_type'){
                         switch(self::$search_params[$v]){
                            case 1: $title['other_params'][] = 'с участками'; break;
                            case 2: $title['other_params'][] = 'с коттеджами'; break;
                            case 3: $title['other_params'][] = 'с таунхаусами'; break;
                            case 4: $title['other_params'][] = 'с квартирами'; break;
                        }
                     }
                    
                }
                    
                if ($v == 'group_id'){
                    $addr = $db->fetch("SELECT txt_addr FROM ".self::$tables[self::$estate_type]." WHERE `group_id` = ?", self::$search_params[$v])['txt_addr'];
                    $title['geodata'] = " по адресу: " . $addr;
                }
                if ($v == 'geodata'){
                    $geodata_id = self::$search_params[$v];
                    $geo_offname = self::getObjectsString('offname','geodata',$geodata_id,"");
                    //поиск по радиусу
                    if (!empty(self::$search_params['radius_geo_id'])) {
                        $geo_shortname = self::getObjectsString('shortname_ablative','geodata',$geodata_id,"");
                        $title['geodata'][] = "рядом с ".$geo_shortname." ".$geo_offname;
                    } else {    
                        $geo_shortname = self::getObjectsString('shortname_prepositional','geodata',$geodata_id,"");
                        switch($geo_shortname){
                            case 'переулке':
                            case 'городе':
                            case 'городке':
                            case 'деревне':
                            case 'поселке':
                                $b = 'в'; break;
                            default:
                                $b = 'на'; break;
                        }
                        $title['geodata'] = $b." ".$geo_shortname." ".$geo_offname;
                    }

                }
                
            }
        }
        
        if(!empty($only_title)) {
            $array_title = array();
            foreach($title as $k=>$val) {
                if(is_array($val) && $k == 'other_params'){
                    $other_params = array();
                    foreach($val as $v=>$item) $other_params[] = ( $v > 0 ? ( $v + 1 != count($val) ? ', ' : '  и  ' ) . ( !empty($excluded_params) ?  'не ': '' ) : ' ') . trim($item);
                    $array_title[] = $description[] = implode('', array_map("rtrim", $other_params));
                } else {
                    $array_title[] = $title = str_replace(' , ', ', ', is_array($val) ? implode(' ',  $val) : $val);
                    if($k!='title') $description[] = $title;
                }
            }
            $title = preg_replace('/ {2,}/',' ',implode(' ', $array_title));
        } 
        $description = trim(preg_replace('/ {2,}/',' ',implode(' ', $description)));
        if(empty($with_description)) return $title;
        else return array($title, Convert::firstLetterUpperCase($description, true));
    }    
}
?>
