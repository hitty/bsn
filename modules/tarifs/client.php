<?php  
//не показывать верхний баннер
Response::SetBoolean('not_show_top_banner',true);
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$GLOBALS['css_set'][] = '/modules/members/style.css'; 
$GLOBALS['css_set'][]='/modules/members/pay.css';
// обработка общих action-ов
switch(true){
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // таблица тарифов
   ////////////////////////////////////////////////////////////////////////////////////////////////
    default:
        $GLOBALS['css_set'][]='/css/controls.css';
        $GLOBALS['js_set'][]='/modules/tarifs/script.js';
        $this_page->addBreadcrumbs('Таблица тарифов', '');
        $page = Request::GetInteger('page',METHOD_GET);
        //редирект с несуществующих пейджей
        if(empty($page)) $page = 1;
        else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
        //читаем цены на объекты
        //$statuses = Config::Get('object_statuses/');
        $statuses = $db->fetchall("SELECT id,title,cost,alias AS prefix FROM ".$sys_tables['objects_statuses'],'id');
        
        //читаем тарифы
        $list = $db->fetchall("SELECT * FROM ".$sys_tables['tarifs']." WHERE activity = 1");
        Response::SetArray('tarifs',$list);
        Response::SetInteger('list_length',count($list));
        Response::SetBoolean('no_right_banner',true);
        //считаем полную стоимость тарифов за месяц
        foreach($list as $key=>$item){
            $month_costs[$key] = ($item['active_objects'] - 1 - $item['promo_available'] - $item['premium_available'] - $item['vip_available']) *$statuses[5]['cost'] +
                                        $item['promo_available']*$statuses[3]['cost'] + $item['premium_available']*$statuses[4]['cost'] + $item['vip_available']*$statuses[5]['cost'];
            if($item['is_popular'] == 1) $popular_tarif_num = $key;
        }
        if(isset($popular_tarif_num)){
            Response::SetInteger('popular_tarif_num',$popular_tarif_num);
            Response::SetBoolean('popular_first', $popular_tarif_num == 0);
            Response::SetBoolean('popular_last', $popular_tarif_num == $key);
        } 
        else Response::SetInteger('popular_tarif_num',-1);
        //вычисляем экономию
        foreach($list as $key=>$item){
            $savings[$key] = $month_costs[$key] - $list[$key]['cost'];
        }
        //читаем скидки по месяцам
        $discounts_list = $db->fetchall("SELECT * FROM ".$sys_tables['tarifs_discounts']." WHERE id IN (1,4) ORDER BY id ASC");
        foreach($discounts_list as $key=>$item){
            switch($item['months']){
                case 1:$discounts_list[$key]['period_text'] = "Месяц подписки.";$discounts_list[$key]['discount_text'] = $item['discount']."%";break;
                case 2:$discounts_list[$key]['period_text'] = "Два месяца подписки.";$discounts_list[$key]['discount_text'] = $item['discount']."%";break;
                case 3:$discounts_list[$key]['period_text'] = "Три месяца подписки.";$discounts_list[$key]['discount_text'] = $item['discount']."%";break;
                case 6:$discounts_list[$key]['period_text'] = "Шесть месяцев подписки.";$discounts_list[$key]['discount_text'] = $item['discount']."%";break;
            }
        }
        //переписываем значения строк результата в столбцы будущей таблицы
        $counter=1;
        foreach($list[0] as $key=>$item){
            $tarifs_table[$key][]=$item;
        }
        while ($counter<count($list)){
            foreach($tarifs_table as $key=>$item){
                $tarifs_table[$key][] = $list[$counter][$key];
            }
            ++$counter;
        }
        unset($list);
        //расставляем строчки в нужном порядке
        unset($tarifs_table['id']);
        $tarifs_table[0] = array('title'=>"",'values'=>$tarifs_table['title']);unset($tarifs_table['title']);
        $tarifs_table[1] = array('title'=>"Активных объектов",'values'=>$tarifs_table['active_objects']);unset($tarifs_table['active_objects']);
        $tarifs_table[2] = array('title'=>"Услуга «VIP»",'values'=>$tarifs_table['vip_available']);unset($tarifs_table['vip_available']);
        $tarifs_table[2] = array('title'=>"Услуга «Премиум»",'values'=>$tarifs_table['premium_available']);unset($tarifs_table['premium_available']);
        $tarifs_table[3] = array('title'=>"Услуга «Промо»",'values'=>$tarifs_table['promo_available']);unset($tarifs_table['promo_available']);
        $tarifs_table[4] = array('title'=>"Аукцион заявок",'values'=>array(1,1,1));
        //данные таблицы
        Response::SetArray('list', $tarifs_table);
        //цена за месяц
        Response::SetArray('costs',$month_costs);
        //данные по скидкам
        Response::SetArray('discounts',$discounts_list);
        //экономия
        Response::SetArray('savings',$savings);
        $GLOBALS['css_set'][] = '/modules/tarifs/tarifs.css';
        $module_template = "client.html";
        break;        
}
        

?>