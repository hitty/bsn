<?php
// проверка наличия в кэше
//$form_filter = $memcache->get('bsn::form::estate');
if(empty($form_filter) || $form_filter === FALSE) {
    if(empty($deal_type)) $deal_type = 'sell';
    $form_filter = [];
    $form_filter['rooms'] = array(0=>array('id'=>0, 'title'=>'Студии') , 1=>array('id'=>1, 'title'=>'Однокомнатные'), 2=>array('id'=>2, 'title'=>'Двухкомнатные'), 3=>array('id'=>3, 'title'=>'Трехкомнатные'), 4=>array('id'=>4, 'title'=>'4 ккв и более'));
    //комнат в продажу для жилой
    if(!empty($estate_type) && $estate_type == 'live' && !empty($parameters['rooms']) &&!empty($parameters['obj_type']) && $parameters['obj_type'] == 2) {
        $rooms_sale = array(1=>array('id'=>1, 'title'=>'1 комната'), 2=>array('id'=>2, 'title'=>'2 комнаты'), 3=>array('id'=>3, 'title'=>'3 комнаты'), 4=>array('id'=>4, 'title'=>'4 комнаты и более'));    
        $form_filter['rooms_sale'] = [];
        $rooms_in_params = substr($parameters['rooms'],-1);
        foreach($rooms_sale as $k=>$item){
            if($item['id'] <= $rooms_in_params) $form_filter['rooms_sale'][] = $item;
        }
    }
    $form_filter['districts'] = $db->fetchall("SELECT id,title, LEFT(title,1) as first_letter FROM ".$sys_tables['districts']." ORDER BY title");
    if(!empty($parameters['districts'])) {
        $districts = explode(",", $parameters['districts']);
        foreach($form_filter['districts'] as $k=>$item){
            if( in_array($item['id'], $districts )  || $item['id'] == $parameters['districts']) $form_filter['districts'][$k]['on'] = true;
        }
    }
        
    $form_filter['district_areas'] = $db->fetchall("SELECT id, offname as title, LEFT(offname,1) as first_letter FROM ".$sys_tables['geodata']." WHERE a_level = 2 ORDER BY offname");
    if(!empty($parameters['district_areas'])) {
        $district_areas = explode(",", $parameters['district_areas']);
        foreach($form_filter['district_areas'] as $k=>$item){
            if( (in_array($item['id'], $district_areas ) ) || $item['id'] == $district_areas) $form_filter['district_areas'][$k]['on'] = true;
        }
    }
    $form_filter['subways'] = $db->fetchall("SELECT id,title, id_subway_line as line_id FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ORDER BY title");
    if(!empty($parameters['subways'])) {
        $subways = explode(",", $parameters['subways']);
        foreach($form_filter['subways'] as $k=>$item){
            if( (in_array($item['id'], $subways ) ) || $item['id'] == $subways) $form_filter['subways'][$k]['on'] = true;
        }
    }
    $form_filter['type_objects_live'] = $db->fetchall("SELECT id,title, sell_count, rent_count FROM " . $sys_tables['type_objects_live'] . " ORDER BY title");
    $form_filter['type_objects_commercial'] = $db->fetchall("SELECT id,title, sell_count, rent_count FROM " . $sys_tables['type_objects_commercial'] . " ORDER BY title");
    $form_filter['type_objects_inter'] = $db->fetchall("SELECT id,title, sell_count, rent_count FROM " . $sys_tables['type_objects_inter'] . " ORDER BY title");
    if(!empty($estate_type)) $form_filter['obj_type'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['object_type_groups']." WHERE `type` = ? ORDER BY id", false, $estate_type);
    $form_filter['build_complete'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['build_complete']." WHERE decade = 0 AND (year >= YEAR(CURDATE()) OR id=4) ORDER BY year, title");
    $form_filter['building_type'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['building_types']." ORDER BY title");
    $form_filter['elevator'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['elevators']." ORDER BY title");
    $form_filter['facing'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['facings']." ORDER BY title");
    $form_filter['decoration'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['decorations']." ORDER BY title");
    $form_filter['toilet'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['toilets']." WHERE id IN (3,4,5,10) ORDER BY title");
    $form_filter['balcon'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['balcons']." ORDER BY title");
    $form_filter['class'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['housing_estate_classes'] . ( !empty($estate_type) && $estate_type == 'apartments' ? " WHERE id != 1 " : "" ) . " ORDER BY id");
    $form_filter['way_type'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['way_types']." ORDER BY title");
    $form_filter['heating'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['heatings']." ORDER BY title");
    if(!empty($estate_type) && $estate_type == 'commercial') $form_filter['heating'] = array(0=>array('id'=>1, 'title'=>'Есть') , 1=>array('id'=>2, 'title'=>'Нет'));
    $form_filter['electricity_commercial'] = array(0=>array('id'=>1, 'title'=>'Есть') , 1=>array('id'=>2, 'title'=>'Нет'));
    $form_filter['parking'] = array(0=>array('id'=>1, 'title'=>'Есть') , 1=>array('id'=>2, 'title'=>'Нет'));
    $form_filter['user_objects'] = array(0=>array('id'=>3, 'title'=>'Застройщик'), 1=>array('id'=>2, 'title'=>'Агентство'), 2=>array('id'=>1, 'title'=>'Частные'));
    $form_filter['214_fz'] = array(0=>array('id'=>1, 'title'=>'Да') ) ;
    $form_filter['apartments'] = array(0=>array('id'=>1, 'title'=>'Да') ) ;
    $form_filter['low_rise'] = array(0=>array('id'=>1, 'title'=>'Да') ) ;
    $form_filter['security'] = array(0=>array('id'=>1, 'title'=>'Есть') , 1=>array('id'=>2, 'title'=>'Нет'));
    $form_filter['cottage_object_types'] = array(0=>array('id'=>1, 'title'=>'Участки') , 1=>array('id'=>2, 'title'=>'Коттеджи'), 2=>array('id'=>3, 'title'=>'Таунхаусы'), 3=>array('id'=>4, 'title'=>'Квартиры'));
    $form_filter['electricity'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['electricities']." ORDER BY title");
    $form_filter['water_supply'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['water_supplies']." ORDER BY title");
    $form_filter['enter'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['enters']." ORDER BY title");
    $form_filter['ownership'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['ownerships']." ORDER BY title");
    $form_filter['bathroom'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['bathrooms']." ORDER BY title");
    $form_filter['gas'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['gases']." ORDER BY title");
    $form_filter['housing_estate_classes'] = $db->fetchall("SELECT id,title FROM ".$sys_tables['housing_estate_classes'] . ( !empty($is_apartments) ? " WHERE id != 1 " : "" ) . " ORDER BY id");
    if(!empty($estate_type) && $estate_type == 'zhiloy_kompleks')
        $form_filter['developer'] = $db->fetchall("SELECT ".$sys_tables['agencies'].".title, ".$sys_tables['users'].".id 
                                 FROM ".$sys_tables['agencies']." 
                                 RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                 RIGHT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['housing_estates'].".id_user = ".$sys_tables['users'].".id
                                 WHERE   ".$sys_tables['users'].".id > 0  AND ".$sys_tables['agencies'].".title!='' AND ".$sys_tables['housing_estates'].".published = 1
                                 GROUP BY  ".$sys_tables['users'].".id
                                 ORDER BY ".$sys_tables['agencies'].".title");
    $form_filter['cottage_districts'] = $db->fetchall("SELECT * FROM ".$sys_tables['district_areas']." ORDER BY `title`");
    $form_filter['cottage_directions'] = $db->fetchall("SELECT * FROM ".$sys_tables['directions']." ORDER BY `title`");
    $form_filter['cottage_developers'] = $db->fetchall("SELECT ".$sys_tables['cottages'].".id_user as id,  
                                        ".$sys_tables['agencies'].".title
                                FROM ".$sys_tables['cottages']." 
                                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['cottages'].".id_user
                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                WHERE ".$sys_tables['cottages'].".id_stady = 2 AND ".$sys_tables['users'].".id_agency > 0  AND ".$sys_tables['agencies'].".title!=''
                                GROUP BY  ".$sys_tables['cottages'].".id_user
                                ORDER BY `title`");        
    
    $form_filter['regions'] = array(0=>array('id'=>78, 'title'=>'Санкт-Петербург') , 1=>array('id'=>47, 'title'=>'Ленинградская область'));
    
    //фильтры для зарубежной
    if(!empty($estate_type) && $estate_type == 'inter') {
        $form_filter['deals'] = array(0=>array('id'=>2, 'title'=>'Продажа') , 1=>array('id'=>1, 'title'=>'Аренда'));
        $form_filter['cost'] = array(0=>array('id'=>1, 'title'=>'До 100 000') , 1=>array('id'=>2, 'title'=>'100 000 - 500 000'), 2=>array('id'=>3, 'title'=>'500 000 - 1 млн.'), 3=>array('id'=>4, 'title'=>'Свыше 1 млн.'));
        $form_filter['country']  = $db->fetchall("SELECT * FROM ".$sys_tables['inter_countries']." WHERE published = 1 ORDER BY title");
    }

    //$memcache->set('bsn::form::estate', $form_filter, FALSE, Config::$values['blocks_cache_time']['form_filter']);
}
Response::SetArray('form_filter',$form_filter);

?>