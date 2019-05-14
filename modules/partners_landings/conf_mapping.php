<?php
return array(
    'partners_landings' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок лендинга'
        )
        
        ,'pretty_url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'URL',
        )        

        
        ,'square' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь'
        )
        
        ,'floor' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Высота потолков'
        )
        ,'level' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Этаж',
        )
        ,'level_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Этажность',
        )        
        ,'power' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Мощность',
        )        
        ,'address' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Адрес'
        )
         ,'map' => array(
            'fieldtype' => 'map',
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'lat' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )        
        ,'lng' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )                 
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Описание'
        )        
        ,'params' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Параметры'
        )
        ,'housing_estate_description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Описание ЖК'
        )
        
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость',
        )
        
        ,'cost2meter' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость за м2',
        )
        ,'advert_phone' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Телефон',
        )
        ,'email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Email',
        )        
        ,'email_2' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Email 2',
        )        
    )

);
?>