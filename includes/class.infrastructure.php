<?php
  class Infrastructure{
      const R = 6371;
      private static $search_initial_distance = 3;
      private static $search_distance_step = 2;
      private static $search_max_distance = 11;
      private static $distance_decimals = 2;
      
      /**
      * рассчитываем границы окружности по координатам центра
      * 
      * @param mixed $lat      - широта центра
      * @param mixed $lng      - долгота центра
      * @param mixed $distance - радиус в км
      */
      public static function getCircleBorders($lat,$lng,$distance){
          if(empty($lat) || empty($lng) || empty($distance)) return false;
          return array('top_left_lat' => $lat + rad2deg($distance/self::R),
                       'top_left_lng' => $lng - rad2deg($distance/self::R/cos(deg2rad($lat))),
                       'bottom_right_lat' => $lat - rad2deg($distance/self::R),
                       'bottom_right_lng' => $lng + rad2deg($distance/self::R/cos(deg2rad($lat))) );
      }
      
      /**
      * расчет расстояния(км) между двумя точками по координатам
      * 
      * @param mixed $lat1
      * @param mixed $lng1
      * @param mixed $lat2
      * @param mixed $lng2
      */
      public static function getDistance($lat1,$lng1,$lat2,$lng2,$decimals = false){
          $pi_180 = PI()/180;
          $decimals = (!empty($decimals) ? $decimals : self::$distance_decimals);
          $distance = 2 * asin(sqrt( pow( sin(($lat1 - abs($lat2)) * $pi_180 / 2), 2) + 
                                     cos($lat1 * $pi_180) * cos(abs($lat2) * $pi_180) * pow( sin(($lng2-$lng1) * $pi_180/2), 2) ) ) * self::R;
          return number_format($distance,$decimals);
      }
      
      /**
      * ищем ближайшую инфрастуктуру для объекта, считаем расстояния
      * 
      * @param mixed $estate    - название таблицы
      * @param mixed $id        - id объекта
      * @param mixed $rent
      * @param mixed $lat
      * @param mixed $lng
      */
      public static function getNearestInfrastructureObjects($estate_type, $id = false, $rent = false, $lat = false, $lng = false){
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          //проверяем корректность, дочитываем данные при необходимости
          if( empty($estate_type) || empty($sys_tables[$estate_type]) || (empty($id) && (empty($lat) || empty($lng) || empty($rent))) ) return false;
          if(empty($rent) || empty($lat) || empty($lng)){
              $object_info = $db->fetch("SELECT rent,lat,lng FROM ".$sys_tables[$estate_type]." WHERE id = ?",$id);
              if(empty($object_info)) return false;
              else extract($object_info);
          } 
          
          //читаем подкатегории которые будем искать
          $subcategories_ids = $db->fetch("SELECT subcategories,search_parameters FROM ".$sys_tables['infrastructure_priorities']." WHERE estate_type = ? AND rent = ?",$estate_type,$rent);
          if(empty($subcategories_ids) || empty($subcategories_ids['subcategories'])) return false;
          else{
              if(!empty($subcategories_ids['search_parameters'])){
                  $search_parameters = explode(',',$subcategories_ids['search_parameters']);
                  if(!empty($search_parameters[0])) self::$search_initial_distance = $search_parameters[0];
                  if(!empty($search_parameters[1])) self::$search_distance_step = $search_parameters[1];
                  if(!empty($search_parameters[2])) self::$search_max_distance = $search_parameters[2];
              }
              $subcategories_ids = $subcategories_ids['subcategories'];
          } 

          //тут будем записывать данные ближайших
          $nearest_objects = array_fill_keys(explode(',',$subcategories_ids),[]);
          $searching_types_count = count(explode(',',$subcategories_ids));
          
          $distance = self::$search_initial_distance;
          
          //ищем в радиусе не больше 10км, или пока все не заполнится
          while($distance < self::$search_max_distance && count(array_filter($nearest_objects,"count")) < $searching_types_count ){
              //область в которой ищем
              
              $circle_borders = self::getCircleBorders($lat,$lng,$distance);
              
              $list = $db->fetchall("SELECT * FROM(
                                     SELECT ".$sys_tables['infrastructure'].".*, 
                                            ".$sys_tables['infrastructure_categories'].".title AS category_title,
                                            ".$sys_tables['infrastructure_categories'].".class AS category_class
                                     FROM ".$sys_tables['infrastructure']." 
                                     LEFT JOIN ".$sys_tables['infrastructure_categories']." ON ".$sys_tables['infrastructure'].".id_category = ".$sys_tables['infrastructure_categories'].".id
                                     LEFT JOIN ".$sys_tables['infrastructure_subcategories']." ON ".$sys_tables['infrastructure'].".id_subcategory = ".$sys_tables['infrastructure_subcategories'].".id
                                     WHERE ".$sys_tables['infrastructure'].".id_subcategory IN (".$subcategories_ids.") AND
                                           ".$sys_tables['infrastructure'].".lat >= ? AND
                                           ".$sys_tables['infrastructure'].".lat <= ? AND
                                           ".$sys_tables['infrastructure'].".lng >= ? AND
                                           ".$sys_tables['infrastructure'].".lng <= ?
                                     ORDER BY id_subcategory,ABS(infrastructure.lat - ?) + ABS(infrastructure.lng - ?) ASC
                                     LIMIT 1000) a
                                     GROUP BY id_subcategory",'id_subcategory', 
                                     $circle_borders['bottom_right_lat'], $circle_borders['top_left_lat'], $circle_borders['top_left_lng'], $circle_borders['bottom_right_lng'], $lat, $lng);
              foreach($list as $key=>$item){
                  if(empty($nearest_objects[$key])){
                      $nearest_objects[$key] = $item;
                      $nearest_objects[$key]['distance'] = self::getDistance($item['lat'],$item['lng'],$lat,$lng);
                      $subcategories_ids = preg_replace("/(^".$key."[^0-9]?)|([^0-9]?".$key."(?=[^0-9]|$))/",'',$subcategories_ids);
                  } 
              }
                
              $distance += self::$search_distance_step;
          }
          
          
          $result['lq'] = '';
          $result['result'] = $nearest_objects;
          $markers = [];
          foreach($nearest_objects as $k=>$item){
              $markers[] = array(
                           'lat'        => $item['lat'],
                           'lng'        => $item['lng'],
                           'name'       => $item['name'],
                           'category_title' => $item['category_title'],
                           'category_class' => $item['category_class'],
                           'address'    => $item['address'],
                           'distance'   => $item['distance']);
          }
          $result['ok'] = true;
          $result['markers'] = $markers;
          
          return $result;
      }
      
      /**
      * put your comment there...
      * 
      * @param mixed $estate_type
      * @param mixed $id
      * @param mixed $lat
      * @param mixed $lng
      */
      public static function getInfrastructure($titles,$top_left_lat,$top_left_lng,$bottom_right_lat,$bottom_right_lng){
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          $sf = 3.14159 / 180; // scaling factor
          $lat = ($top_left_lat + $bottom_right_lat)/2;
          $lng = ($bottom_right_lng + $top_left_lng)/2;
          if(empty($titles) || empty($top_left_lat) || empty($top_left_lng) || empty($bottom_right_lat) || empty($bottom_right_lng)) return false;
          
          if(!empty($titles)){
              $markers = [];
              foreach($titles as $k=>$title){
                  $list = $db->fetchall("SELECT * 
                               FROM ".$sys_tables['infrastructure']." 
                               LEFT JOIN ".$sys_tables['infrastructure_categories']." ON ".$sys_tables['infrastructure_categories'].".id = ".$sys_tables['infrastructure'].".id_category
                               WHERE ".$sys_tables['infrastructure_categories'].".title = ? AND
                                     ".$sys_tables['infrastructure'].".lat >= ? AND
                                     ".$sys_tables['infrastructure'].".lat <= ? AND
                                     ".$sys_tables['infrastructure'].".lng >= ? AND
                                     ".$sys_tables['infrastructure'].".lng <= ?
                               ORDER BY ACOS(SIN(lat*$sf)*SIN($lat*$sf) + COS(lat*$sf)*COS($lat*$sf)*COS((lng-$lng)*$sf))
                               LIMIT 15
                               ",false, 
                               $title, $bottom_right_lat, $top_left_lat, $top_left_lng, $bottom_right_lng 
                  );        
                  $result['lq'] = '';
                  $result['result'] = $list;
                  foreach($list as $k=>$item){
                      $markers[$title][] = array(
                                   'lat'        => $item['lat'],
                                   'lng'        => $item['lng'],
                                   'name'       => $item['name'],
                                   'address'    => $item['address']);
                  }  
            }
            $result['ok'] = true;
            $result['markers'] = $markers;
            return $result;
        }
      }
  }
?>
