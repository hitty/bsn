<?php
/**    
* Класс для заявок
*/
abstract class CreditCalculator {
    public static $mortgage_url = "/mortgage/";
    private static $activity_condition = "enabled = 1 AND published = 1 AND `date_start` <= CURDATE() AND `date_end` > CURDATE()";
    public static $banks_condition = "activity & 16 AND estate_types & 256 AND mortgage_applications_accepting < 5";
    /**
    * по типу недвижимости определяем `estate_type` строчки в базе
    * 
    * @param mixed $estate_type
    */
    private static function getCalculatorType($estate_type){
        switch($estate_type){
            case "live":
                $calculator_type = 1;
                break;
            case "commercial":
                $calculator_type = 3;
                break;
            case "country":
                $calculator_type = 4;
                break;
            case "build":
                $calculator_type = 2;
                break;
            default:
                return false;
        }
        return $calculator_type;
    }
    /**
    * по типу калькулятора возвращаем тип недвижимости
    * 
    * @param mixed $calculator_type
    */
    private static function getEstateType($calculator_type){
        switch($calculator_type){
            case 1:
                $estate_type = "live";
                break;
            case 2:
                $estate_type = "build";
                break;
            case 3:
                $estate_type = "commercial";
                break;
            case 4:
                $estate_type = "country";
                break;
            default:
                return false;
        }
        return $estate_type;
    }
    /**
    * проверяем входящие параметры карточки, возвращаем цену
    * 
    * @param mixed $estate_type
    * @param mixed $id
    * @param mixed $cost
    */
    private static function validateParams($estate_type,$id,$cost){
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        if(!in_array($estate_type,array('live','build','commercial','country')) && !Validate::isDigit($id) && !Validate::isDigit($cost)) return false;
        if(!empty($id)){
            $object_cost = $db->fetch("SELECT cost FROM ".$sys_tables[$estate_type]." WHERE id = ?",$id);
            if(!empty($object_cost) && !empty($object_cost['cost'])) $object_cost = $object_cost['cost'];
        }else $object_cost = (int)$cost;
        if(empty($object_cost)) return false;
        
        return $object_cost;
    }
    
    /**
    * расчет ежемесячного платежа для фиксированного калькулятора
    * 
    * @param mixed $cost
    * @param mixed $calculator_info
    * @param mixed $months
    * @param mixed $first_payment
    */
    private static function getMonthlyPay($cost,$calculator_info,$months = false,$first_payment = false){
        $months_to_pay = (!empty($months) ? $months :  $calculator_info['months']);
        $first_payment_value = (!empty($first_payment) ? $first_payment : $cost*$calculator_info['first_payment']/100);
        $percent = $calculator_info['percent'];
        
        //($object_cost - $first_payment) * (0.145/12 * pow((1+0.145/12),320) )/(pow((1+0.145/12),320) - 1)
        //новая формула
        $month_percent = $percent*0.01/12;
        $monthly_pay = ($cost - $first_payment_value) * ($month_percent * pow((1+$month_percent),$months_to_pay) )/(pow((1+$month_percent),$months_to_pay) - 1);
        if($monthly_pay < 0) return 0;
        return $monthly_pay;
    }

    /**
     * расчет первого взноса для фиксированного калькулятора
     *
     * @param mixed $cost
     * @param mixed $calculator_info
     * @param mixed $months
     * @param mixed $monthly_pay
     */
    private static function getFirstPayment($cost,$calculator_info,$months = false,$monthly_pay = false){
        $months_to_pay = (!empty($months) ? $months :  $calculator_info['months']);
        //$first_payment_value = (!empty($first_payment) ? $first_payment : $cost*$calculator_info['first_payment']/100);
        $percent = $calculator_info['percent'];

        //новая формула
        $month_percent = $percent*0.01/12;
        //$monthly_pay = ($cost - $first_payment_value) * ($month_percent * pow((1+$month_percent),$months_to_pay) )/(pow((1+$month_percent),$months_to_pay) - 1);
        //echo $cost.";".$month_percent.";".$months_to_pay.";".$monthly_pay;die();
        $first_payment = ($cost * $month_percent * pow((1+$month_percent),$months_to_pay) - $monthly_pay * (pow((1+$month_percent),$months_to_pay) - 1)) / (1.0*($month_percent * pow((1+$month_percent),$months_to_pay)));
        /*
        echo $cost."\r\n";
        echo $month_percent."\r\n";
        echo $monthly_pay."\r\n";
        echo pow((1+$month_percent),$months_to_pay)."\r\n";
        echo ($cost * $month_percent * pow((1+$month_percent),$months_to_pay) - $monthly_pay * (pow((1+$month_percent),$months_to_pay) - 1))."\r\n";
        echo (1.0*($month_percent * pow((1+$month_percent),$months_to_pay)))."\r\n";
        echo $first_payment;die();
        */
        if($first_payment < 0) return 0;
        return $first_payment;
    }

    /**
    * расчет процентов по правилам из отдельной таблицы
    * 
    * @param mixed $id_calculator
    * @param mixed $first_payment
    * @param mixed $months
    */
    private static function getComplexPercent($id_calculator,$first_payment_percent,$months){
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        $result = $db->fetch("SELECT percent FROM ".$sys_tables['credit_calculator_percent_rules']." 
                              WHERE id_calculator = ? AND first_payment_from <= ? AND range_start <= ?
                              ORDER BY first_payment_from,range_start DESC",$id_calculator,$first_payment_percent,$months);
        return (empty($result) ? false : $result['percent']);
    }
    
    public static function getEstateTypeTitle($type,$return_alias = false){
        $estate_type = CreditCalculator::getEstateType($type);
        if($return_alias) return $estate_type;
        else return (empty(Config::$values['object_types'][$estate_type]) ? false : Config::$values['object_types'][$estate_type]['name']);
    }
    
    public static function getBanksList($credit_type,$estate_type = false){
        if(empty($credit_type)) $credit_type = CreditCalculator::getCalculatorType($estate_type);
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        $condition = str_replace('published',$sys_tables['credit_calculator'].".published",CreditCalculator::$activity_condition);
        $banks = $db->fetchall("SELECT ".$sys_tables['agencies'].".id,
                                       ".$sys_tables['agencies'].".title,
                                       ".$sys_tables['credit_calculator'].".id AS calculator_id,
                                       ".$sys_tables['credit_calculator'].".months,
                                       ".$sys_tables['credit_calculator'].".first_payment,
                                       LEFT(".$sys_tables['agencies_photos'].".name,2) AS subfolder,
                                       ".$sys_tables['agencies_photos'].".name as photo_name,
                                       CONCAT('/organizations/company/',".$sys_tables['agencies'].".chpu_title,'/') AS url,
                                       ".$sys_tables['credit_calculator'].".id AS calculator_id
                                FROM ".$sys_tables['agencies']." 
                                LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['agencies_photos'].".id_parent
                                LEFT JOIN ".$sys_tables['credit_calculator']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['credit_calculator'].".id_agency AND 
                                                                                  ".(!empty($credit_type) ? "type = ".$credit_type." AND" : "")."
                                                                                  ".$sys_tables['credit_calculator'].".enabled = 1 AND
                                                                                  ".$sys_tables['credit_calculator'].".published = 1
                                WHERE ".$condition." AND ".$sys_tables['credit_calculator'].".id IS NOT NULL",'id');
        $bank_urls = [];
        if(empty($banks)) return false;
        foreach($banks as $key=>$values){
            $banks[$key]['img'] = Config::$values['img_folders']['agencies']."/sm/".$values['subfolder']."/".$values['photo_name'];
            unset($banks['subfolder']);
            unset($banks['name']);
        }
        return $banks;
    }
    public static function getAvailableEstateTypes(){
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        $result = $db->fetch("SELECT GROUP_CONCAT(DISTINCT type) AS types_available FROM ".$sys_tables['credit_calculator']." WHERE ".CreditCalculator::$activity_condition);
        if(empty($result) || empty($result['types_available'])) return false;
        return array_map("CreditCalculator::getEstateType",explode(',',$result['types_available']));
    }
    
    /**
    * подбираем калькулятор для поиска или карточки
    * 
    * @param mixed $estate_type
    * @param mixed $id
    */
    public static function getBanner($estate_type,$id = false,$cost = false,$random_one = false){
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        
        $type = CreditCalculator::getCalculatorType($estate_type);
        $object_cost = CreditCalculator::validateParams($estate_type,$id,$cost);
        if(empty($object_cost) || empty($type)) return false;
        
        //выбираем минимальный
        if(!$random_one){
            $calculators = $db->fetchall("SELECT *,percent_".$estate_type." AS percent FROM ".$sys_tables['credit_calculator']."
                                          WHERE `published` = ? AND `enabled` = ? AND 
                                                `date_start` <= CURDATE() AND `date_end` > CURDATE() AND `type` = ? 
                                                ".(empty($object_cost) ? " AND in_search = 1 " : "")."
                                          ORDER BY percent_".$estate_type." ASC",'id', 1, 1, $type);
            $result = [];
            foreach($calculators as $calculator_id=>$calculator){
                if($calculator['percent_complex'] == 1) $calculator['percent'] = CreditCalculator::getComplexPercent($calculator_id,$calculator['first_payment'],$calculator['months']);
                $monthly_pay = CreditCalculator::getMonthlyPay($object_cost,$calculator,false);
                if(empty($result) || $result['monthly_pay'] > $monthly_pay){
                    $result = $calculator;
                    $result['monthly_pay'] = number_format($monthly_pay,0,'.',' ');
                }
            }
        }else{
            //выбираем случайный
            $item = $db->fetch("SELECT id, IF(`priority` = 100, 100, `priority`*(RAND()*100/`priority`)) as `priority` FROM ".$sys_tables['credit_calculator']." WHERE `published` = ? AND `enabled` = ? AND `date_start` <= CURDATE() AND `date_end` > CURDATE() AND `type`=? ORDER BY `priority`*RAND()",1, 1, $type); 
            if(empty($item) || empty($item['id'])) return false;
            $result = $db->fetch("SELECT * FROM ".$sys_tables['credit_calculator']." WHERE id = ?",$item['id']);
            $result['monthly_pay'] = CreditCalculator::getMonthlyPay($object_cost,$result,false);
            $result['monthly_pay'] = number_format($result['monthly_pay'],0,'.',' ');
        }
        
        $result['img_folder'] = Config::$values['img_folders']['credit_calculator'];
        return $result;
        
    }
    
    /**
    * подбираем минимальную стоимость - для заявки на ипотеку
    * 
    * @param mixed $estate_type
    * @param mixed $id
    * @param mixed $cost
    */
    public static function getMinMonthPayment($estate_type, $id = false, $cost = false){
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        $type = CreditCalculator::getCalculatorType($estate_type);
        $object_cost = CreditCalculator::validateParams($estate_type,$id,$cost);
        if(empty($object_cost)) return false;
        
        $calculator_info = $db->fetch("SELECT id,percent_".$estate_type." AS percent, months AS months, first_payment
                                        FROM ".$sys_tables['credit_calculator']." 
                                        WHERE type = ? AND ".CreditCalculator::$activity_condition."
                                        ORDER BY percent_".$estate_type." ASC",$type);
        if(empty($calculator_info) || empty($calculator_info['id'])) return false;
        else{
            $min_month_payment = CreditCalculator::getMonthlyPay($object_cost,$calculator_info,false,false);
            return number_format($min_month_payment,0,'.',' ');
        }
    }
    
    /**
    * условия платежей банка для конкретного объекта или цены
    * 
    * @param mixed $id_agency
    * @param mixed $estate_type
    * @param mixed $id
    * @param mixed $cost
    * @param mixed $monthly_pay
    */
    public static function getBankPaymentInfo($id_agency, $estate_type, $id = false, $cost = false, $months = false, $first_payment = false, $monthly_pay = false, $count_overpay = false, $fix_unavailable = false){
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        $fix_unavailable = (!empty($fix_unavailable) ? $fix_unavailable : false);
        $object_cost = CreditCalculator::validateParams($estate_type,$id,$cost);
        $type = CreditCalculator::getCalculatorType($estate_type);
        if(empty($id_agency) || empty($type)) return false;
        
        $bank_payment_info = $db->fetch("SELECT id,percent_".$estate_type." AS percent, 
                                                months_min AS months_min, 
                                                months AS months, 
                                                first_payment, 
                                                percent_complex,
                                                direct_link
                                         FROM ".$sys_tables['credit_calculator']." 
                                         WHERE id_agency = ? AND type = ? AND ".CreditCalculator::$activity_condition."
                                         ORDER BY percent_".$estate_type." ASC",$id_agency,$type);
        
        //если есть конкретный объект, считаем платеж, проверяем что он больше минимально возможного
        $first_payment_required = $bank_payment_info['first_payment']*0.01*$object_cost;
        if(!empty($first_payment) && $first_payment < $first_payment_required){
            if($fix_unavailable) $first_payment = $first_payment_required;
            else $bank_payment_info['min_first_payment'] = number_format($first_payment_required,0,'.',' ');
        }
        if(!empty($months) && $months < $bank_payment_info['months_min']){
            if($fix_unavailable) $months = $bank_payment_info['months_min'];
            else$bank_payment_info['min_months_required'] = $bank_payment_info['months_min'];
        }
        if(!empty($months) && $months > $bank_payment_info['months']){
            if($fix_unavailable) $months = $bank_payment_info['months'];
            else $bank_payment_info['max_months'] = $bank_payment_info['months'];
        }
        if(!empty($object_cost)){
            //если мы считаем ежемесячный платеж, назначаем минимаьный
            if(empty($first_payment) && empty($monthly_pay))  $first_payment = $first_payment_required;
            $bank_payment_info['first_payment'] = $first_payment;

            $months_to_pay = (!empty($months) ? (int)$months : $bank_payment_info['months'] );
            
            //при необходимости корректируем процент
            if($bank_payment_info['percent_complex'] == 1 && !empty($months_to_pay) && !empty($first_payment)){
                $first_payment_percent = round($first_payment/$object_cost*100);
                $bank_payment_info['percent'] = CreditCalculator::getComplexPercent($bank_payment_info['id'],$first_payment_percent,round($months_to_pay/12));
            }

            $bank_payment_info['months'] = $months_to_pay;

            //считаем либо месячный платеж, либо, если он задан, первый взнос
            if(!empty($monthly_pay)){
                $bank_payment_info['first_payment'] = CreditCalculator::getFirstPayment($object_cost,$bank_payment_info,$months_to_pay,$monthly_pay);
                $counter = 0;
                while($bank_payment_info['first_payment'] < $first_payment_required){
                    if($months_to_pay > $bank_payment_info['months_min']) $months_to_pay -= 12;
                    else $monthly_pay -= 10000;
                    $bank_payment_info['first_payment'] = CreditCalculator::getFirstPayment($object_cost,$bank_payment_info,$months_to_pay,$monthly_pay);
                    $bank_payment_info['months'] = $months_to_pay;
                    $bank_payment_info['monthly_pay'] = $monthly_pay;
                }
            }
            else{
                $bank_payment_info['monthly_pay'] = CreditCalculator::getMonthlyPay($object_cost,$bank_payment_info,$months_to_pay,$first_payment);
            }
        }
        
        //считаем перплату, если нужно
        if(!empty($count_overpay))
            $bank_payment_info['overpay'] = number_format($bank_payment_info['months'] * (!empty($bank_payment_info['monthly_pay']) ? $bank_payment_info['monthly_pay'] : $monthly_pay) + $bank_payment_info['first_payment'] - $object_cost,0,'.','');
        
        $bank_payment_info['monthly_pay'] = number_format((empty($bank_payment_info['monthly_pay']) ? $monthly_pay : $bank_payment_info['monthly_pay']),0,'.','');
        $bank_payment_info['first_payment'] = number_format((empty($bank_payment_info['first_payment']) ? $first_payment : $bank_payment_info['first_payment']),0,'.','');
        
        if((isset($bank_payment_info['monthly_pay']) && empty($bank_payment_info['monthly_pay'])) ||
           (isset($bank_payment_info['first_payment']) && empty($bank_payment_info['first_payment'])) ) return false;
        
        return $bank_payment_info;
    }
    
    /**
    * пишем показ для баннера
    * 
    * @param mixed $type
    * @param mixed $id
    * @return mixed
    */
    public static function bannerShow($type,$id){
        if(empty($id) || empty($type)) return false;
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        return $db->query("INSERT INTO ".$sys_tables['credit_calculator_stats_show_day']." SET id_parent = ?, `type`=?", $id, $type);
    }
    
    /**
    * пишем показ для баннера
    * 
    * @param mixed $type
    * @param mixed $id
    * @return mixed
    */
    public static function calculatorShow($calculator_id,$source_type){
        if(empty($calculator_id) || empty($source_type)) return false;
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        return $db->query("INSERT INTO ".$sys_tables['credit_calculator_stats_show_day']." SET id_parent = ?, `type` = ?", $calculator_id,  ($source_type == 1 ? 1 : 2) );
    }
    
    /**
    * пишем показ для баннера
    * 
    * @param mixed $type
    * @param mixed $id
    * @return mixed
    */
    public static function calculatorClick($estate_type,$bank_id,$source_type){
        if(empty($id) || empty($type) || empty($source_type)) return false;
        global $db;
        $sys_tables = Config::$values['sys_tables'];
        $calculator_id = $db->fetch("SELECT id FROM ".$sys_tables['credit_calculator']." WHERE bank_id = ? AND type = ? AND enabled = 1 AND published = 1");
        if(empty($calculator_id) || empty($calculator_id['id'])) return false;
        else $calculator_id = $calculator_id['id'];
        return $db->query("INSERT INTO ".$sys_tables['credit_calculator_stats_click_day']." SET id_parent = ?, `type` = ?", $calculator_id, ($source_type == 1 ? 1 : 2) );
    }
}
?>
