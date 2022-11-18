<?php
/**    
* Класс оценки объекта недвижимости
*/
require_once('includes/class.email.php');
require_once('includes/class.host.php');
class EstateEstimate {
    private static $tables = [];   
    public static $data = [];              // входящие данные
    public static $calculate = [];         // вычисленные данные
    public static $costs = [];             // рассчитанные данные стоимостей
    public static $passwd = '';                 // пароль пользователя

    /**
    * вычисление всех необходимых системных переменных
    */
    public static function Init($data = false){
        if(!empty($data)) self::$data = $data;
        self::$tables = Config::$sys_tables;
    }

    /**
    * Получение данных из таблицы
    * 
    */
    public static function getData( $hash ) {
        global $db;
        self::$data = $db->fetch("
             SELECT 
                ".self::$tables['estate_estimate'].".*,
                ".self::$tables['estate_estimate_purposes'].".title as purpose_title,
                ".self::$tables['building_types'].".title as building_type_title
             FROM ".self::$tables['estate_estimate']."
             LEFT JOIN ".self::$tables['estate_estimate_purposes']." ON ".self::$tables['estate_estimate_purposes'].".id = ".self::$tables['estate_estimate'].".id_purpose
             LEFT JOIN ".self::$tables['building_types']." ON ".self::$tables['building_types'].".id = ".self::$tables['estate_estimate'].".id_building_type
             WHERE ".self::$tables['estate_estimate'].".`hash` = ?
        ", $hash);
        if(self::$data['lat'] < 10 || self::$data['lng'] < 10) {
            $address =  trim(self::$data['geodata_title'].", дом ".self::$data['house'].( !empty(self::$data['corp']) ? ", корпус ".self::$data['corp'] : ""));
            list(self::$data['lng'], self::$data['lat']) = self::getCoordsByAddress($address);
        }
    }

    /**
    * Вычисление оценочных данных
    * @return array
    */
    public static function Calculate() {
        global $db;
        $geodata = $db->fetch("SELECT * FROM ".self::$tables['geodata']." WHERE id = ?", self::$data['id_geodata']);
        //поиск конкретного дома у нас в базе
        self::$calculate = $db->fetchall("
            SELECT 
                ".self::$tables['live'].".* 
            FROM 
                ".self::$tables['live']."
            WHERE 
                ".self::$tables['live'].".id_region = ? AND
                ".self::$tables['live'].".id_area = ? AND
                ".self::$tables['live'].".id_city = ? AND
                ".self::$tables['live'].".id_place = ? AND
                ".self::$tables['live'].".id_street = ? AND 
                ".self::$tables['live'].".rent = ? AND
                ".self::$tables['live'].".rooms_total = ? AND
                ".self::$tables['live'].".id_type_object = ? AND
                ".self::$tables['live'].".house = ?
        ", false, $geodata['id_region'], $geodata['id_area'], $geodata['id_city'], $geodata['id_place'], $geodata['id_street'], 2, self::$data['rooms_total'], 1, self::$data['house']);

        //если адрес не найден ищем в округе такой же тип дома с такой же этажностью (если есть)
        if(empty(self::$calculate) || self::$calculate < 3 ) {
            //определяем координаты дома
            if(empty(self::$data['lat']) || empty(self::$data['lng'])) {
                $address =  trim(self::$data['geodata_title'].", дом ".self::$data['house'].( !empty(self::$data['corp']) ? ", корпус ".self::$data['corp'] : ""));
                list(self::$data['lng'], self::$data['lat']) = self::getCoordsByAddress($address);

            }
            //поиск дома в радиусе 1 
            $R = 6371;  // earth's radius, km 
            $max_distance = 1;
            // first-cut bounding box (in degrees) 
            $max_latitude = self::$data['lat'] + rad2deg($max_distance/$R); 
            $min_latitude = self::$data['lat'] - rad2deg($max_distance/$R); 
            // compensate for degrees longitude getting smaller with increasing latitude 
            $max_longitude = self::$data['lng'] + rad2deg($max_distance/$R/cos(deg2rad(self::$data['lat']))); 
            $min_longitude = self::$data['lng'] - rad2deg($max_distance/$R/cos(deg2rad(self::$data['lat'])));  
            
            self::$calculate = $db->fetchall("
                SELECT 
                    ".self::$tables['live'].".* 
                FROM 
                    ".self::$tables['live']."
                WHERE 
                    ".self::$tables['live'].".id_building_type  = ? AND 
                    ".self::$tables['live'].".rent = ? AND
                    ".self::$tables['live'].".id_region = ? AND
                    ".self::$tables['live'].".rooms_total = ? AND
                    ".self::$tables['live'].".id_type_object = ? AND
                    ".self::$tables['live'].".lat >= ? AND
                    ".self::$tables['live'].".lat <= ? AND
                    ".self::$tables['live'].".lng >= ? AND
                    ".self::$tables['live'].".lng <= ?
            ", false, self::$data['id_building_type'], 2, $geodata['id_region'], self::$data['rooms_total'], 1,
               $min_latitude, $max_latitude, $min_longitude, $max_longitude
            ); 
            
                       
        }
        if(!empty(self::$calculate)) {
            $slice_count = count(self::$calculate) > 4 ? 2 : 1;
            self::$calculate = array_slice(self::$calculate, $slice_count, -1*$slice_count);    
        }
        //получение среднего, мин и макс значения стоимости
        if(!empty(self::$calculate)) {
            $list = [];
            foreach(self::$calculate as $k=>$calculate) $list[(int) $calculate['cost']/$calculate['square_full']] = $calculate;
            ksort($list);
            $cost2meter = 0;
            foreach($list as $k=>$item) {
                if(empty(self::$costs['min_cost2meter'])) self::$costs['min_cost2meter'] = $item['cost']/$item['square_full'];
                self::$costs['max_cost2meter'] = $item['cost']/$item['square_full'];
            }
            self::$costs['avg_cost2meter'] = (int) ( self::$costs['max_cost2meter'] + self::$costs['min_cost2meter'] ) / 2;
            self::$costs['min_cost'] = (int) self::$costs['min_cost2meter'] * self::$data['square'];
            self::$costs['max_cost'] = (int) self::$costs['max_cost2meter'] * self::$data['square'];
            self::$costs['avg_cost'] = (int) (self::$costs['min_cost'] + self::$costs['max_cost']) / 2;
        }
    }
    /**
    * Запись данных в таблицу
    * array $data - вновьвычесленные данные
    * @return array
    */
    public static function Write($data = false) {
        global $db;
        if(empty($data)){
            self::$data['hash'] = sha1(time());
            $res = $db->insertFromArray(self::$tables['estate_estimate'], self::$data, 'id');    
            self::$data['id'] = $db->insert_id;
        } else if(!empty(self::$data['id'])){
            $data['id'] = self::$data['id'] ;
            $res = $db->updateFromArray(self::$tables['estate_estimate'], $data, 'id'); 
        }
    }
    public static function getCoordsByAddress($address){
        $lng = $lat = '';
        $geo = curlThis("http://geocode-maps.yandex.ru/1.x/?format=json&kind=street&geocode=".$address);
        $geo = json_decode($geo);
        if(!empty($geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos)){
            $point = explode(" ",$geo->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
            if( $point[0] != 59.939095 && $point[1] != 30.315868 ){
                $lng = $point[0];
                $lat = $point[1];
            }
        }
        return array($lng, $lat);        
    }
    /**
    * Отправка письма
    * @return array
    */
    public static function sendMail() {
        //получение полных данных о запросе
        self::getData(self::$data['hash']);
        $item = self::$data;
        $item['host'] = Host::$host;
        $item['passwd'] = self::$passwd;
        Response::SetArray('item', $item);
        $mailer = new EMailer('mail');
        $eml_tpl = new Template('mail.html','modules/estate_estimate/');
        // перевод письма в кодировку мейлера   
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        // параметры письма
        $mailer->Body = $html;
        $mailer->IsHTML(true);

        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "BSN.ru Оценка вашей квартиры");
        $mailer->AddAddress($item['email']);     //отправка письма ответственному менеджеру
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = 'BSN.ru';
        // попытка отправить
        $mailer->Send();  
    }
    /**
    * Запись данных в таблицу
    * array $data - вновьвычесленные данные
    * @return array
    */
    public static function Registration() {
        global $db;
        $user = $db->fetch(" SELECT * FROM ".self::$tables['users']." WHERE email = ?", 
                self::$data['email'] 
        );
        if(empty($user)){
            self::$passwd = substr(md5(time()),-6);
            $db->querys(" INSERT INTO ".self::$tables['users']." SET email = ?, login = ?, passwd = ?", 
                    self::$data['email'], self::$data['email'], sha1(sha1(self::$passwd)) 
            );
        }
    }        
    
}
?>