<?php
require_once('includes/class.estate.php');
require_once('includes/class.estate.statistics.php');
require_once('includes/class.housing_estates.php');
$module_template = 'templates/phones.box.html';
$bsn_phone = '(952) 245-94-26';
// определяем тип недвижимости
$estate = "";
$estate_types = array('live','build','commercial','country','inter','apartments','zhiloy_kompleks','business_centers','cottedzhnye_poselki');
$deal_types = array('rent','sell');
$type = Request::GetString('type',METHOD_POST);

Host::checkUser(false, false, true);
$blacklist = Host::checkBlacklist();
$blacklist = !empty($blacklist) ? true : false;
if(!empty($this_page->page_parameters[0]) && ( $this_page->page_parameters[0] == 'zhiloy_kompleks' || $this_page->page_parameters[0] == 'apartments' ) ){  //проверка для жилого комплекса
    $housing_estates = new HousingEstates();
    $item = $housing_estates->getItem(false, $this_page->page_parameters[1],true);
    //переопределение значений
    if(!empty($item)) {
        $this_page->page_parameters[0] = 'zhiloy_kompleks';
        $this_page->page_parameters[1] = 'sell';
        $this_page->page_parameters[2] = $item['id'];
    }
        
} elseif(!empty($this_page->page_parameters[0]) && $this_page->page_parameters[0] == 'business_centers'){ //проверка для БЦ
    $business_centers = new BusinessCenters();
    $item = $business_centers->getItem($this_page->page_parameters[1]);
    //переопределение значений
    if(!empty($item)) {
        $this_page->page_parameters[0] = 'business_centers';
        $this_page->page_parameters[1] = 'sell';
        $this_page->page_parameters[2] = $item['id'];
    }
}elseif(!empty($this_page->page_parameters[0]) && $this_page->page_parameters[0] == 'cottedzhnye_poselki'){ //проверка для КП
    $cottages = new Cottages();
    $item = $cottages->getItem(false, $this_page->page_parameters[1]);
    //переопределение значений
    if(!empty($item)) {
        $this_page->page_parameters[0] = 'cottedzhnye_poselki';
        $this_page->page_parameters[1] = 'sell';
        $this_page->page_parameters[2] = $item['id'];
    }
}
if(!empty($this_page->page_parameters[0]) && in_array($this_page->page_parameters[0], $estate_types) && 
   !empty($this_page->page_parameters[1]) && in_array($this_page->page_parameters[1], $deal_types) &&
   !empty($this_page->page_parameters[2]) && Validate::Digit($this_page->page_parameters[2]) &&
   $type == 'click' &&
   !Host::$is_bot
){
    $id = $this_page->page_parameters[2];
    $estate = $this_page->page_parameters[0];
    switch($estate){
        case 'live':
            $estateItem = new EstateItemLive($id);
            $estate_type = 1;
            break;
        case 'build':
            $estateItem = new EstateItemBuild($id);
            $estate_type = 2;
            break;
        case 'commercial':
            $estateItem = new EstateItemCommercial($id);
            $estate_type = 3;
            break;
        case 'country':
            $estateItem = new EstateItemCountry($id);
            $estate_type = 4;
            break;
        case 'zhiloy_kompleks':
            $info = $item;
            $estate_type = 5;
            break;
        case 'business_centers':
            $info = $item;
            $estate_type = 6;
            break;        
        case 'cottedzhnye_poselki':
            $info = $item;
            $estate_type = 7;
            break;
        default:
            $estateItem = null;
            $this_page->http_code=404;
            break;
    }
    //сразу проверяем, нет ли ip в blacklist, если есть сразу возвращаем левые телефоны и создаем видимость формы
    
    if(empty($info)){
        $item = $estateItem->getData();
        $info = $estateItem->getInfo();
    }
    if(!empty($item) && !empty($info)){
        //теперь есть отдельный подставной телефон для выдачи
        if(!empty($info['agency_advert_phone_objects'])) $info['agency_advert_phone'] = $info['agency_advert_phone_objects'];
        if(!empty($info['agency_advert_phone']) && $estate_type <= 4) {
            //проверка на наличие баланса для открытия телефона
            $agency_phone = Convert::ToPhone($info['agency_advert_phone']);
            $item['seller_phone'] = $agency_phone[0];
            $info['agency_phone_1'] = $info['agency_phone_2'] = $info['agency_phone_3'] = '';
        }
        else  {
            if( $estate_type == 5 && $info['advanced'] == 2 ){
                //Для ЖК инфа отсюда: https://trello.com/c/l3Qk6kKT/170-%D0%B7%D0%B0%D0%BC%D0%B5%D0%BD%D0%B0-%D0%BD%D0%BE%D0%BC%D0%B5%D1%80%D0%B0-%D1%82%D0%B5%D0%BB-%D0%B2-%D0%BA%D0%B0%D1%80%D1%82%D0%BE%D1%87%D0%BA%D0%B0%D1%85-%D0%B6%D0%BA    
                //просьба от 22 января 20 года от Марины: В жК - слетел телефон. Т.е. мы договаривались, что если карточка выделена- то показывается тел. клиента. Если не выделена - то мой тел. Сейчас везде мой тел
                // ну вот надо так и вернуть. Клиент платит за карточку- а там мой тел
                $item['seller_phone'] = $bsn_phone;
            } else if( $estate_type > 4 && !( ( !empty( $info['advanced'] ) && $info['advanced'] == 1 ) || ( !empty($item['show_phone']) && $item['show_phone'] == 1 ) ) ) $item['seller_phone'] = $bsn_phone;
            else {
                if( !empty( $info['advanced'] ) && $info['advanced'] == 1 ) {
                    if(!empty($info['agency_seller_advert_phone'])) $seller_phone = $info['agency_seller_advert_phone'];
                    elseif(!empty($info['agency_seller_phone_1'])) $seller_phone = $info['agency_seller_phone_1'];
                    elseif(!empty($info['agency_developer_advert_phone'])) $seller_phone = $info['agency_developer_advert_phone'];
                    elseif(!empty($info['agency_developer_phone_1'])) $seller_phone = $info['agency_developer_phone_1'];
                } elseif( !empty( $info['agency_seller_payed_page'] ) && $info['agency_seller_payed_page'] == 1) {
                    $seller_phone = !empty($info['agency_seller_advert_phone']) ? $info['agency_seller_advert_phone'] : $info['agency_seller_phone_1'];
                } else if( !empty( $info['developer_payed_page'] ) && $info['developer_payed_page'] == 1) {
                    $seller_phone = !empty($info['agency_developer_advert_phone']) ? $info['agency_developer_advert_phone'] : $info['agency_developer_phone_1'];
                }
                if(!empty($seller_phone)){
                    $seller_phone = Convert::ToPhone($seller_phone);
                    if(!empty($seller_phone[0])) $item['seller_phone'] = $seller_phone[0];
                } else {
                    $seller_phone = (!empty($item['seller_phone'])?Convert::ToPhone($item['seller_phone']):"");
                    if(!empty($seller_phone[0]) && strlen($seller_phone[0])>=7 && $estate_type <5 ) $item['seller_phone'] = $seller_phone[0];
                    elseif(!empty($info['agency_phone_1']) && strlen($info['agency_phone_1'])>=7) $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_1'], $item['id_user'], $estate);
                    elseif(!empty($info['agency_phone_2']) && strlen($info['agency_phone_2'])>=7) $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_2'], $item['id_user'], $estate); 
                    elseif(!empty($info['agency_phone_3']) && strlen($info['agency_phone_3'])>=7) $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_3'], $item['id_user'], $estate);
                    elseif(!empty($seller_phone[0]) && strlen($seller_phone[0])>=7 && $estate_type > 4 ) $item['seller_phone'] = $seller_phone[0];                    
                }
            }
        }
        /*
        //вносим в Blacklist пользователей без referer
        $referer = Host::getRefererURL();
        if(empty($referer)){
        */
        $referer = Host::getRefererURL();
        //проверяем если много запросов
        $clicks_amount_min = $db->fetch("SELECT COUNT(*) AS amount FROM ".$sys_tables['phone_clicks_day_checker']." WHERE ip = ? AND TIMESTAMPDIFF(MINUTE, `datetime`, NOW())=0",Host::getUserIp())['amount'];
        $clicks_amount_hour = $db->fetch("SELECT COUNT(*) AS amount FROM ".$sys_tables['phone_clicks_day_checker']." WHERE ip = ? AND TIMESTAMPDIFF(HOUR, `datetime`, NOW())=0",Host::getUserIp())['amount'];
        $clicks_amount_agent = $db->fetch("SELECT COUNT(*) AS amount FROM ".$sys_tables['phone_clicks_day_checker']." WHERE browser = ? AND datetime > NOW() - INTERVAL 2 SECOND", $_SERVER['HTTP_USER_AGENT'])['amount'];
        //$clicks_amount_agent = 1;
        $is_bot = ($clicks_amount_min >= 15 || $clicks_amount_hour >= 150 || !empty($clicks_amount_agent));
        /*
        //если что-то не так, возвращаем левые телефоны и запоминаем ip
        if(!empty($is_bot)){
            $is_bot = true;
            $user_ip = Host::getUserIp();
            if($user_ip != '109.167.249.172'){
                
                Host::blockIp($user_ip,8);
                
                $info['agency_phone_1'] = '(812) 655-02-13';
                $info['agency_phone_2'] = '(812) 655-02-13';
                $info['agency_phone_3'] = '(812) 655-02-13';
                $item['seller_phone'] = '(812) 655-02-13';
                $ajax_result['id_click'] = rand(10000,30000);
            }
        }
        */
        
        Response::SetArray('item',$item);
        Response::SetString('estate_type',$this_page->page_parameters[1]);
        Response::SetArray('info',$info);
        Response::SetInteger('estate_type',$estate_type);
        
        $abuses_categories_list = $db->fetchall("SELECT * FROM ".$sys_tables['abuses_categories']." ORDER BY position");
        Response::SetArray('list',$abuses_categories_list);
        Response::SetString('url_query','estate_type='.$estate_type.'&id_object='.$item['id'].'&id_user='.$item['id_user']);

        $tpl = new Template("/modules/abuses/templates/block.html",$this_page->module_path);
        $abuse_form = $tpl->Processing();
        Response::SetString('abuse_form',$abuse_form);
            
        //1 клик в минуту для пользователей у кого есть referer
        if(empty($is_bot)) {
            $time = $db->fetch("SELECT TIMESTAMPDIFF(MINUTE, `datetime`, NOW()) as `time` FROM ".$sys_tables['phone_clicks_day']." WHERE ip = ? ORDER BY id DESC",Host::getUserIp());
            if((empty($time) || $time['time']>=1)){
                $db->query("INSERT INTO ".$sys_tables['phone_clicks_day']." SET type = ?, id_parent = ?, id_object = ?, status = ?, ip = ?, browser = ?, ref = ?", $estate_type, $item['id_user'],$item['id'], 1,Host::getUserIp(),$_SERVER['HTTP_USER_AGENT'], $referer);
                Response::SetInteger('id_click', $db->insert_id);
            }
        }
        $db->query("INSERT INTO ".$sys_tables['phone_clicks_day_checker']." SET type = ?, id_parent = ?, id_object = ?, status = ?, ip = ?, browser = ?, ref = ?", $estate_type, $item['id_user'],$item['id'], 1,Host::getUserIp(),$_SERVER['HTTP_USER_AGENT'], $referer);
        
    }
} elseif($type == 'success_call' || $type == 'wrong_number' || $blacklist){
    if($blacklist){
        $id = $this_page->page_parameters[2];
        $estate = $this_page->page_parameters[0];
        switch($estate){
            case 'live':
                $estateItem = new EstateItemLive($id);
                $estate_type = 1;
                break;
            case 'build':
                $estateItem = new EstateItemBuild($id);
                $estate_type = 2;
                break;
            case 'commercial':
                $estateItem = new EstateItemCommercial($id);
                $estate_type = 3;
                break;
            case 'country':
                $estateItem = new EstateItemCountry($id);
                $estate_type = 4;
                break;
            case 'zhiloy_kompleks':
            case 'apartments':
                $info = $item;
                $estate_type = 5;
                break;
            case 'business_centers':
                $info = $item;
                $estate_type = 6;
                break;        
            case 'cottedzhnye_poselki':
                $info = $item;
                $estate_type = 7;
                break;
            default:
                $estateItem = null;
                $this_page->http_code=404;
                break;
        }
        //сразу проверяем, нет ли ip в blacklist, если есть сразу возвращаем левые телефоны и создаем видимость формы
        if(empty($estateItem)) return;
        if(empty($info)){
            $item = $estateItem->getData();
            $info = $estateItem->getInfo();
        }
        $info['agency_phone_1'] = '(812) 655-02-13';
        $info['agency_phone_2'] = '(812) 655-02-13';
        $info['agency_phone_3'] = '(812) 655-02-13';
        $item['seller_phone'] = '(812) 655-02-13';
        $ajax_result['id_click'] = rand(10000,30000);
        Response::SetArray('item',$item);
        Response::SetString('estate_type',1);
        Response::SetArray('info',$info);

        Response::SetString('url_query',"");
    }
    else{
        $id = Request::GetInteger('id',METHOD_POST) ;
        if($id > 0) $db->query("UPDATE ".$sys_tables['phone_clicks_day']." SET status = ? WHERE id = ?", $type == 'success_call' ? 2 : 1, $id); 
    }
} else $this_page->http_code=404;
$ajax_result['ok'] = true;
?>