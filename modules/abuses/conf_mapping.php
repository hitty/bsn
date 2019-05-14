<?php
return array(
    'categories' => array(
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
            'label' => 'Название',
            'tip' => 'Заголовок для категории'
        )
        ,'position' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Приоритет',
            'tip' => 'Приоритет в списке (чем меньще число, тем выше в списке)'
        )
    ),
    'abuses' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
    
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Категория жалобы'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Не обработана',2=>'Обработана'),
            'label' => 'Статус жалобы',
            'tip' => 'Параметры отображения жалобы'
        )
        ,'abuse_date' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата жалобы',
            'tip' => ''
        )        
        ,'text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Содержание жалобы',
            'tip' => ''
        )
        ,'estate_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(1=>'Жилая', 2=>'Новостройки', 3=>'Коммерческая', 4=>'Загородная', 5=>'ЖК', 6=>'КП', 7=>'БЦ'),
            'label' => 'Тип недвижимости',
            'tip' => ''
        )
        ,'id_object' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'ID объекта',
            'tip' => ''
        )   
        
    )
);
?>