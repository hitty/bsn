<?php
return array(
    'invest' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 300,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок проекта'
        )
        ,'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Информация',
            'tip' => 'Дополнительная информация'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(0 => 'в архиве', 1 => 'Success stories', 2 => 'Ongoing', 3 => 'Future projects'),
            'label' => 'Статус',
            'tip' => 'Статус проекта'
        )
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Категория проекта'
        )
        ,'map' => array(
            'fieldtype' => 'map',
            'nodisplay' => true
        )
        ,'lat' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text'
        )        
        ,'lng' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text'
        )
    )
);
?>