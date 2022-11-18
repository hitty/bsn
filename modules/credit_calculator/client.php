<?php
require_once('includes/class.credit_calculator.php');
// таблицы модуля
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
//переопределение типаа недвижимость (для ЖК - стройка)
if( !empty( $this_page->page_parameters[1] ) && $this_page->page_parameters[1] == 'zhiloy_kompleks' ) $this_page->page_parameters[1] = 'build';
$second_action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
// обработка общих action-ов
switch(true){
    /////////////////////////////////////////////////////////////////
    // Список все банков
    /////////////////////////////////////////////////////////////////
    case empty( $action ):
            $second_action = explode( '/', $_SERVER['HTTP_REFERER'] );
            $second_action = !empty( $second_action[3] ) && in_array($second_action[3], array('live','build','country','commercial')) ? $second_action[3] : '';
            if( !empty( $second_action ) ) {
                $banks_list = CreditCalculator::getBanksList(false,$second_action);
                
                if(!empty($banks_list)){
                    foreach($banks_list as $key=>$item) {
                        if( !Host::$is_bot ) CreditCalculator::calculatorShow( $item['calculator_id'], 1 );
                        $ajax_result['type'] = $second_action;
                        $ajax_result['id'] = $item['calculator_id'];
                    }
                }
            }
            $list = $db->fetchall(
                "SELECT * FROM ".$sys_tables['credit_calculator']." WHERE `published` = ? AND `enabled` = ? AND `date_start` <= CURDATE() AND `date_end` > CURDATE()",
                false, 1, 1
            ); 
            if( !empty( $list ) ) {
                $data = [];
                foreach( $list as $k => $item ) $data[$item['type']] = $item;
                $ajax_result['list'] = $data;
                $ajax_result['ok'] = true;
            }
        break;
        
    /////////////////////////////////////////////////////////////////
    // кредитный калькулятор в карточке: читаем список активных банков
    /////////////////////////////////////////////////////////////////
    case Validate::isDigit($action) && !empty($second_action) && in_array($second_action, array('live','build','country','commercial')):

            $banks_list = CreditCalculator::getBanksList(false,$second_action);
            
            $referer = Host::getRefererURL();
            preg_match("/[0-9]+(?=\/$)/si",$referer,$estate_id);
            if(empty($estate_id)){
                $ajax_result['ok'] = false;
                break;
            }
            $estate_id = array_pop($estate_id);
            $object_cost = $db->fetch("SELECT cost FROM ".$sys_tables[$second_action]." WHERE id = ?",$estate_id);
            if(empty($object_cost) || empty($object_cost['cost'])){
                $ajax_result['ok'] = false;
                break;
            }
            $object_cost = Convert::ToNumber($object_cost['cost']);
            Response::SetString('object_cost',$object_cost);
            if(!empty($banks_list)){
                $ajax_result['ok'] = true;
                foreach($banks_list as $key=>$item){
                    $banks_list[$key]['min_first_payment'] = ( Validate::isDigit($object_cost) ? $object_cost : 0 ) * 0.01 * $item['first_payment'];
                    $banks_list[$key]['max_years_to_pay'] = $item['months'] / 12;
                    CreditCalculator::calculatorShow($item['calculator_id'],1);
                }
                Response::SetArray('banks_list', $banks_list);
                $module_template = 'block.html';
            } else $module_template = '/templates/clearcontent.html';
            //$item = CreditCalculator::getBanner($this_page->page_parameters[1],false,$action,true);
        break;
    /////////////////////////////////////////////////////////////////
    //кредитный калькулятор в результатах поиска
    /////////////////////////////////////////////////////////////////
    case !empty($action) && $action=='search' && !empty($this_page->page_parameters[1]) && in_array($this_page->page_parameters[1], array('live','build','country','commercial')):
            switch($this_page->page_parameters[1]){
                case 'live':
                    $type = 1;
                    break;
                case 'build': 
                    $type = 2;
                    break;
                case 'commercial': 
                    $type = 3;
                    break;
                case 'country': 
                    $type = 4;
                    break;
            }

            //получение баннера: Кредитный калькулятор
            $item = $db->fetch("SELECT *
                                FROM ".$sys_tables['credit_calculator']." WHERE `published` = ? AND `enabled` = ? AND `date_start` <= CURDATE() AND `date_end` > CURDATE() AND `type`=?",
                                1, 1, $type ); 
            $banks_list = CreditCalculator::getBanksList( false, $this_page->page_parameters[1] );
            $object_cost = !empty( $this_page->page_parameters[2] ) ? $this_page->page_parameters[2] : 4000000;
            Response::SetString('object_cost',$object_cost);
            if(!empty($banks_list)){
                $ajax_result['ok'] = true;
                foreach($banks_list as $key=>$item){
                    $banks_list[$key]['min_first_payment'] = ( Validate::isDigit($object_cost) ? $object_cost : 0 ) * 0.01 * $item['first_payment'];
                    $banks_list[$key]['max_years_to_pay'] = $item['months'] / 12;
                    CreditCalculator::calculatorShow($item['calculator_id'],1);
                }
                Response::SetArray('banks_list', $banks_list);
            }      
            $module_template = 'block.html';
            $ajax_result['ok'] = true;
        break;
    case $action=='click': // запись статистики клика
        if($ajax_mode){
            $id = Request::GetInteger('id',METHOD_POST);
            
            if(!empty($id)){
                $type = Request::GetString('type',METHOD_POST);
                switch($type){
                    case 'search': 
                        $type = 2; 
                        break;
                    case 'card': 
                    default:
                        $type = 1; 
                        break;
                }
            } 
            else{
                $bank_id = Request::GetInteger('bank_id',METHOD_POST);
                $estate_type = Request::GetString('estate_type',METHOD_POST);
                switch($estate_type){
                    case "build":
                    case "live":
                        $type = 1;break;
                    case "commercial":
                        $type = 2;break;
                    case "country":
                        $type = 3;break;
                }
                $calculator_id = $db->fetch("SELECT id FROM ".$sys_tables['credit_calculator']." WHERE id_agency = ? AND type = ? AND enabled = 1  AND published = 1",$bank_id,$type);
                if(empty($calculator_id) || empty($calculator_id['id'])){
                    $ajax_result['ok'] = false;
                    break;
                }
                else $id = $calculator_id['id'];
                $type = 1;
            }
            
            if($id>0){
                if(!Host::$is_bot) $res = $db->querys("INSERT INTO ".$sys_tables['credit_calculator_stats_click_day']." SET `id_parent`= ? , `type` = ?", $id, $type);
                $ajax_result['ok'] = $res;
            }
        } else $this_page->http_code=404;
        break;
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}
?>