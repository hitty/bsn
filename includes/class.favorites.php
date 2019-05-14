<?php
    require_once('includes/class.estate.php');
    require_once('includes/class.housing_estates.php');
    require_once('includes/class.cottages.php');
    require_once('includes/class.business_centers.php');
    if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
    class Favorites{
        public static $data_array = [];
        private static $count = 0;
        private static $tables = [];
        public static function Init(){ 
            self::$tables = Config::$sys_tables;
            self::$data_array = self::GetData(); 
        }
        
        /**
        * Получение количества объектов в избранном
        * @return int количество объектов в избранном
        */
        public static function getAmount(){
            $amount = 0;
            foreach (Config::$values['object_types'] as $alias => $obj){     // формирование списка с избранным
                if (!empty(Favorites::$data_array[$obj['key']])){
                    switch($alias){
                        case 'live':  // получение списка жилой недвижимости   
                            $estate = new EstateListLive();
                            $where = $estate->work_table.'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                            $orderby = $estate->work_table.".status = 4 DESC, ".$estate->work_table.".id_main_photo>0 DESC, ".$estate->work_table.".date_change DESC, ".$estate->work_table.".date_in DESC";
                            $list[$alias] = $estate->Search($where,count(Favorites::$data_array[$obj['key']]),0);
                            $amount += count($list[$alias]);  
                        break;
                        case 'build':  // получение списка новостроек
                            $estate = new EstateListBuild();
                            $where = $estate->work_table.'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                            $orderby = $estate->work_table.".status = 4 DESC, ".$estate->work_table.".id_main_photo>0 DESC, ".$estate->work_table.".date_change DESC, ".$estate->work_table.".date_in DESC";
                            $list[$alias] = $estate->Search($where,count(Favorites::$data_array[$obj['key']]),0);
                            $amount += count($list[$alias]);
                        break;
                        case 'commercial':  // получение списка коммерческой недвижимости
                            $estate = new EstateListCommercial();
                            $where = $estate->work_table.'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                            $orderby = $estate->work_table.".status = 4 DESC, ".$estate->work_table.".id_main_photo>0 DESC, ".$estate->work_table.".date_change DESC, ".$estate->work_table.".date_in DESC";
                            $list[$alias] = $estate->Search($where,count(Favorites::$data_array[$obj['key']]),0);
                            $amount += count($list[$alias]);
                        break;
                        case 'country':  // получение списка загородной недвижимости
                            $estate = new EstateListCountry();
                            $where = $estate->work_table.'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                            $orderby = $estate->work_table.".status = 4 DESC, ".$estate->work_table.".id_main_photo>0 DESC, ".$estate->work_table.".date_change DESC, ".$estate->work_table.".date_in DESC";
                            $list[$alias] = $estate->Search($where,count(Favorites::$data_array[$obj['key']]),0);
                            $amount += count($list[$alias]);
                        break;
                        case 'zhiloy_kompleks':  // получение списка жилых комплексов
                            $housing_estates = new HousingEstates();
                            $where = self::$tables['housing_estates'].'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                            $orderby = self::$tables['housing_estates'].".advanced = 1 DESC, ".self::$tables['housing_estates'].".id_main_photo > 0 DESC, ".
                                       self::$tables['housing_estates'].".id_region DESC, ".self::$tables['housing_estates'].".id_district > 0 DESC, district ASC, district_area ASC"; 
                            $list[$alias] = $housing_estates->Search($where,count(Favorites::$data_array[$obj['key']]),0,$orderby);
                            $amount += count($list[$alias]);
                        break;
                        case 'cottedzhnye_poselki':  // получение списка коттеджных поселков
                            $cottages = new Cottages();
                            $where = self::$tables['cottages'].'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                            $orderby = self::$tables['cottages'].".advanced = 1 DESC, ".self::$tables['cottages'].".id_main_photo > 0 DESC, ".
                                       self::$tables['cottages'].".id_district_area > 0 DESC, district_title ASC"; 
                            $list[$alias] = $cottages->getList(count(Favorites::$data_array[$obj['key']]),0,$where,$orderby);
                            $amount += count($list[$alias]);
                        break;
                        case 'business_centers':  // получение списка бизнес-центров
                            $bc = new BusinessCenters();
                            $where = self::$tables['business_centers'].'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                            $orderby = self::$tables['business_centers'].".advanced = 1 DESC, ".self::$tables['business_centers'].".id_main_photo > 0 DESC, ".
                                       self::$tables['business_centers'].".id_region DESC, ".self::$tables['business_centers'].".id_district > 0 DESC, district ASC, district_area ASC"; 
                            $list[$alias] = $bc->getList(count(Favorites::$data_array[$obj['key']]),0,$where,$orderby);
                            $amount += count($list[$alias]);
                        break;
                    }
                }                
            }
            
            return $amount;
        }
   
        /**
        * добавление поля для избранного к списку объектов одного типа(для отображения "звездочки").
        * @param array $list список объектов
        * @param int $object_type тип объекта
        * @return array список объектов
        */  
        public static function ToList($list,$object_type = 1){
            $fav = !empty(self::$data_array[$object_type])?self::$data_array[$object_type]:[];
            foreach($list AS $key=>$val){
                if (in_array($val['id'],$fav)) $list[$key]['in_favorites'] = 1;  
            }
            return $list;
        }
        
        /**
        *  добавление поля для избранного к объекту (для отображения "звездочки")
        * @param array $item объект
        * @param int $object_type тип объекта
        * @return array объект
        */
        public static function ToItem($item,$object_type){
            if (strlen($object_type)==0)  $object_type = 1;
            $fav = !empty(self::$data_array[$object_type])?self::$data_array[$object_type]:[];
            if (in_array($item['id'],$fav)) $item['in_favorites'] = 1;
            return $item;
        }
        
        /**
        * При авторизации переносит данные из куки в базу данных
        * @return bool результат выполнения функции
        */
        public static function FromCookieToBase(){
            global $db, $auth;  
            $favorites_indexes = self::GetData('favorites');
            if (count($favorites_indexes)==0) return false;
            self::Add($favorites_indexes);
            return Cookie::SetCookie ("favorites", "", time() - 3600,"/");    
        }
        
        /**
        * Добавление объектов в избранное
        * @param array $objects массив объектов для добавления
        * @return bool результат выполнения функции
        */
        public static function Add($objects){
            global $db, $auth;
            if ($auth->isAuthorized()){
                $sql = "INSERT IGNORE INTO ".self::$tables['favorites']." (id_user, id_object, type_object, create_datetime)
                                  VALUES ";
                $values = [];
                foreach($objects as $object_type=>$obj){
                    foreach($obj as $obj_id){
                        $values[] = implode(',',array("(".Convert::ToInt($auth->id),Convert::ToInt($obj_id),Convert::ToInt($object_type),"NOW())"));
                    }             
                }
                $result = $db->query($sql.implode(',',$values));
                return $result;   
            } else { 
                $favorites_indexes = self::GetData();
                foreach($objects as $object_type=>$obj){
                    foreach($obj as $obj_id){
                        if (empty($favorites_indexes[$object_type])) $favorites_indexes[$object_type] = [];
                        if (!in_array($obj_id,$favorites_indexes[$object_type])) $favorites_indexes[$object_type][] = $obj_id;
                    }
                }
                Cookie::SetCookie ("favorites",$favorites_indexes,null,"/");
                return true;             
            }
            return false;    
        }
        /**
        * Удаление из избранного
        * @param int $object_id   id объекта
        * @param int $object_type   тип объекта
        * @return bool результат выполнения функции
        */
        public static function Remove($object_id,$object_type){
            global $db, $auth;
            if ($auth->isAuthorized()){
                $result = $db->query("DELETE FROM ".self::$tables['favorites']." WHERE id_user = ? AND id_object = ? AND type_object = ?",
                                  $auth->id,
                                  $object_id,
                                  $object_type
                                  );
                return !empty($result);        
            } else {
                $favorites_indexes =  self::GetData();
                if (count($favorites_indexes)==0) return false;
                if (in_array($object_id,$favorites_indexes[$object_type])){
                    if(($key = array_search($object_id,$favorites_indexes[$object_type])) !== FALSE){
                         unset($favorites_indexes[$object_type][$key]);
                         if (count($favorites_indexes[$object_type])==0) unset($favorites_indexes[$object_type]);                                       
                    } 
                    Cookie::SetCookie ("favorites",$favorites_indexes,null,"/"); 
                    return true;
                }
                return false;
            }
        }
       
        /**
        * Получение списка избранного
        * $param int $par для случая, когда надо брать даннные из куки в тот момент, когда пользователь уже вошел в систему
        * для их последующего переноса в БД
        * @return array список избранного
        */
        public static function GetData($force_cookie_use = false){ 
            global $db, $auth;
            self::$data_array = [];
            if ($auth->isAuthorized() && $force_cookie_use == false){
                $objects = $db->fetchall("SELECT id_object, type_object  FROM ".self::$tables['favorites']." WHERE id_user = ".Convert::ToInt($auth->id));
                foreach($objects as $obj){
                    if (empty(self::$data_array[$obj['type_object']])) self::$data_array[$obj['type_object']] = [];
                    self::$data_array[$obj['type_object']][] = $obj['id_object']; 
                } 
            } else self::$data_array =  Cookie::GetArray('favorites');
            return self::$data_array;    
        }
    }
?>
