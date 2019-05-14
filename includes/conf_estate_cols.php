<?php
return array(
    'hash_fields'=>[]
    ,'data_array' => array(
        'id' => null,
        'hash' => '',
        'rent' => 1,
        'balance' => 0,
        'date_in' => '0000-00-00 00:00:00',
        'date_change' => '0000-00-00 00:00:00',
        'blocking_time' => '0000-00-00 00:00:00',
        'blocking_id_user' => 0,
        'published' => 2,
        'search_count' => 0,
        'views_count' => 0,
        'views_count_week' => 0,
        'id_user' => 0,
		'id_tarif' => 0,
        'seller_name' => '',
        'seller_phone' => '',
        'external_id' => 0,        
        'info_source' => 1,
        'status' => 2,
        'txt_addr' => '',
        'lat' => 0.000000,
        'lng' => 0.000000,
        'elite' => 2,
        'notes' => '',
        'id_main_photo' => 0,
        'tag_date' => '0000-00-00 00:00:00'
    )
    ,'from_new_data_array'=>array(
        'id_object'=>0
    )
    ,'live' => array(
        'custom_data_array' => array(
            'id_building_type' => 1,        'cost' => 0,
            'id_type_object' => 1,          'id_district' => 1,
            'id_subway' => 1,               'id_way_type' => 1,
            'way_time' => 0,                
            'house' => 0,                   'corp' => '',
            'rooms_total' => 0,             'neighbors' => 0,
            'square_full' => 0,             'square_rooms' => 0,
            'square_kitchen' => 0,          'square_live' => 0,
            'level' => 0,                   'level_total' => 0,
            'id_toilet' => 1,               'phone' => 1, 
            'furniture' => 2,               'refrigerator' => 2,
            'wash_mash' => 2,               'id_balcon' => 1,
            'id_elevator' => 1,             'id_enter' => 1,
            'id_window' =>1,                'id_floor' => 1,
            'id_hot_water' => 1,            'id_facing' => 1,
            'rent_length' => '',            'ceiling_height' => 0,
            'rooms_sale' => 0,              'by_the_day' => 2,
            'id_region' => 0,               'id_area' => 0,
            'id_city' => 0,                 'id_place' => 0
        ),
        'custom_hash_fields'  =>  array(
            'rent',          'id_building_type', 'id_type_object',
            'id_district',   'id_street',        'house',
            'corp',          'rooms_total',      'square_full',
            'square_rooms',  'square_kitchen',   'square_live',
            'level',         'level_total',      'id_toilet',
            'id_balcon',     'id_elevator',      'id_enter',
            'id_window',     'id_hot_water',     'ceiling_height',
            'rooms_sale',    'id_region',        'id_area', 
            'id_city',       'id_place',         'id_street'
       )
    )
    ,'commercial'=>array(
        'custom_data_array' => array(
            'cost' => 0,                'id_district' => 1,
            'id_type_object' => 1,      'id_street' => 1,
            'house' => 0,               'corp' => 0,
            'txt_level' => '',          'ceiling_height' => 0,
            'phones_count' => 0,        'cost2meter' => 0,
            'txt_cost' => '',           'square_full' => 0,
            'square_usefull' => 0,      'square_ground' => 0,
            'parking' => 1,             'security' => 1, 
            'service_line' => 1,        'canalization' => 1,        
            'hot_water' => 1,           'electricity' => 1,         
            'heating' => 1,             'transport_entrance' => '', 
            'rent_duration' => '',      'id_facing' => 1,  
            'id_enter' => 1,            'id_region' => 0,    
            'id_area' => 0,             'id_city' => 0,
            'id_place' => 0,            'id_business_center'=>0,
            'type_id_group' => 0,            
        ),
        'custom_hash_fields'  =>  array(
            'rent',             'id_district',      'id_type_object',
            'id_street',        'house',            'corp',
            'txt_level',        'ceiling_height',   'square_full',
            'square_usefull',   'canalization',     'hot_water',
            'electricity',      'heating',
            'id_region',        'id_area',          'id_street', 
            'id_city',          'id_place'
        ) 
    )
    ,'build'=>array(
        'custom_data_array' => array(
            'rooms_sale' => 0,              'rooms_total' => 0,
            'id_district' => 1,             'cost' => 0,
            'id_building_type' => 1,        'id_subway' => 1,
            'id_way_type' => 1,             'way_time' => 0,
            'id_street' => 1,               'house' => 0,
            'corp' => 0,                    'cost2meter' => 0,
            'square_full' => 0,             'square_rooms' => 0,
            'square_kitchen' => 0,          'square_live' => 0,
            'level' => 0,                   'level_total' => 0,
            'id_build_complete' => 1,       'build_completed' => 2,
            'build_in_operation' => 2,      'id_toilet' => 1,
            'id_facing' => 1,               'id_elevator' => 1,
            'id_balcon' => 1,               'installment' => 1,
            'first_payment' => 0,           'id_developer_status' => 1,
            'installment_months' => '',     'installment_years' => '',
            'ceiling_height' => 0,          'txt_cost' => '',
            'id_region' => 0,               'id_area' => 0,
            'id_city' => 0,                 'id_place' => 0,
            'id_housing_estate' => 0,       'id_window' => 0,
            'id_floor' => 0,                'id_hot_water' => 0,
            'id_enter' => 0
        ),
        'custom_hash_fields'  =>  array(
            'id_district',          'id_building_type',
            'id_street',            'house',                'corp',
            'square_full',          'square_rooms',         'square_kitchen',
            'square_live',          'level',                'level_total',
            'id_toilet',            'id_balcon',            'ceiling_height',
            'id_region',            'id_area',              'id_street', 
            'id_city',              'id_place'
        ) 
    )
    ,'country'=>array(
        'custom_data_array' => array(
            'cost' => 0,                    'id_type_object' => 1,
            'id_district_area' => 1,        'id_ownership' => 1,
            'square_ground' => 0,           'year_build' => '',
            'level_total' => 0,             'rooms' => 0,
            'square_full' => 0,             'square_live' => 0,
            'id_consruct_material' => 1,    'id_roof_material' => 1,
            'id_heating' => 1,              'id_electricity' => 1,
            'id_water_supply' => 1,         'id_toilet' => 1,
            'id_river' => 1,                'way_time' => 0,
            'id_way_type' => 1,             'id_gas' => 1,
            'id_building_progress' => 1,    'railstation' => '',
            'phone' => 1,                   'id_garden' => 1,
            'id_bathroom' => 1,
            'id_region' => 0,               'id_area' => 0,
            'id_city' => 0,                 'id_place' => 0,
            'id_cottage'=>0
        ),
        'custom_hash_fields'  =>  array(
            'rent',             'id_type_object',       'id_district_area',
            'square_ground',    'year_build',           'level_total',
            'rooms',            'square_full',          'square_live',
            'id_heating',       'id_electricity',       'id_water_supply',
            'id_toilet',        'id_river',             'id_gas',
            'id_garden',        'id_bathroom',
            'id_region',        'id_area',              'id_street', 
            'id_city',          'id_place'
        ) 
    )
    ,'inter'=>array(
        'custom_data_array' => array(
            'id_foreign_agency' => 0,       'cost_rubles' => 0,
            'cost_euros' => 0,              'cost_dollars' => 0,
            'id_country' => 0,              'id_type_object' => 1
        ),
        'custom_hash_fields'  =>  array(
            'id_country',       'id_type_object',       'external_id'
        ) 
    )
);

?>