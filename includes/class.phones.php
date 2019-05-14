<?php
/**    
* Класс работы со спецредложениями
*/
if( !class_exists('Convert') ) include('includes/class.convert.php');
if( !class_exists('EstateStat') )include('includes/class.estate.statistics.php');

class Phones {
    public static $bsn_phone = '(812) 606-77-50';
     
    public static function getPhone($estate, $item, $info){
        global $db;
        //теперь есть отдельный подставной телефон для выдачи
        if(!empty($info['agency_advert_phone_objects'])) $info['agency_advert_phone'] = $info['agency_advert_phone_objects'];
        if(!empty($info['agency_advert_phone'])) {
            //проверка на наличие баланса для открытия телефона
            $phone = Convert::ToPhone($info['agency_advert_phone']);
            return !empty( $phone ) ? $phone[0] : "";
        }

        //показ реального телефона агентства
        $advanced_item = self::advancedItem($estate, $item, $info);

        if( in_array($estate, array( 'zhiloy_kompleks', 'business_centers', 'cottedzhnye_poselki', 'build' ) ) && !$advanced_item) return self::$bsn_phone;
        
        $seller_phone = (!empty($item['seller_phone'])?Convert::ToPhone($item['seller_phone']):"");
        if(!empty($seller_phone[0]) && strlen($seller_phone[0])>=7 && $estate_type <5 ) $item['seller_phone'] = $seller_phone[0];
        elseif(!empty($info['agency_phone_1']) && strlen($info['agency_phone_1'])>=7) $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_1'], $item['id_user'], $estate);
        elseif(!empty($info['agency_phone_2']) && strlen($info['agency_phone_2'])>=7) $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_2'], $item['id_user'], $estate); 
        elseif(!empty($info['agency_phone_3']) && strlen($info['agency_phone_3'])>=7) $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_3'], $item['id_user'], $estate);
        elseif(!empty($seller_phone[0]) && strlen($seller_phone[0])>=7 && $estate_type > 4 ) $item['seller_phone'] = $seller_phone[0];
        return $item['seller_phone'];
    }
    
    public static function advancedItem($estate, $item, $info){
        global $db;
        if( in_array($estate, array( 'zhiloy_kompleks', 'business_centers', 'cottedzhnye_poselki' ) ) ){
            // комплексы: платная карточка или поле показать телефон
            return $item['advanced'] == 1 || $item['show_phone'] == 1;
        } else {
            if( $item['advanced'] == 1) return true; //карточка выделена
            else if(empty($item['id_housing_estate'])) return "";
            else{
                $housing_estate = $db->fetch("SELECT * FROM " . Config::Get('sys_tables/housing_estates') . " WHERE id = ?", $item['id_housing_estate']);
                return $housing_estate['advanced'] == 1 || $housing_estate['show_phone'] == 1;
            }
            
        }
    }
}

?>