<?php
return array(
    'promotions' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'id_estate_complex' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )   
        ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )   
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )   
        ,'id_district_area' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )   
        ,'id_user' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )              
        ,'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'maxlength' => 35,
            'fieldtype' => 'text',
            'label' => 'Название'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(1 => 'опубликован', 2 => 'не опубликован', 3 => 'архив'),
            'label' => 'Статус',
            'tip' => ''
        )

        ,'agency_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/access/promotions/agencies_titles/',
            'input'=>'id_user',
            'label' => 'Агентство'
        )                   
        ,'id_estate_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0 => 'выберите'),
            'label' => 'Тип недвижимости',
            'tip' => ''
        )
         
        ,'estate_complex_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
			'values' => array(0=>'- выберите -', 1 => 'ЖК', 2 => 'КП', 3 => 'БЦ'),
            'label' => 'ЖК, КП, БЦ',
            'tip' => ''
        )
        ,'estate_complex_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/access/promotions/housing_estates_titles/',
            'input'=>'id_estate_complex',
            'label' => 'Название комплекса'
        )        	
        ,'district_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/access/promotions/districts_titles/',
            'input'=>'id_district',
            'label' => 'Район'
        )            
        ,'district_area_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/access/promotions/district_areas_titles/',
            'input'=>'id_district_area',
            'label' => 'Район области'
        )            
        ,'subway_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/access/promotions/subways_titles/',
            'input'=>'id_subway',
            'label' => 'Метро'
        )   
        ,'id_way_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Способ добраться до метро',
            'tip' => ''
        )
        
        ,'way_time' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Время до метро'
        )        
                 
        ,'discount_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(1=>'рубли', 2 => 'проценты (0-99)'),
            'label' => 'Скидка: рубли или проценты'
        )        
        ,'discount' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'min' => 0,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Размер скидки'
        )        
        
        ,'url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'min' => 0,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на сайт'
        )
        ,'sale_url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'min' => 0,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на страницу SALE'
        )
		,'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст'
        )		
        ,'date_start' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата начала',
            'tip' => 'Дата начала акции'
        )        
        ,'date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата окончания',
            'tip' => 'Дата окончания акции'
        )        
    )
);
?>