<?php
return array(
    'receipt' => array(
         'name' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ФИО',
        )
        ,'id_estate_type' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
             'values' => array(''=>'- выберите тип недвижимости -',1=>'Жилая',2=>'Новостройки',3=>'Коммерческая',4=>'Загородная'),
            'label' => 'Тип недвижимости',
        )
        ,'id_object' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ID Варианта',
        )
        ,'phone' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'phone',
            'label' => 'Контактный телефон',
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Сумма, руб',
        )
    )
);
?>