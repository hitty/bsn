<?php
return array(
    'members' => array(
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
            'values' => array(1=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => ''
        ) 
        ,'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Название или имя участника'
        )
        ,'phone' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон',
            'tip' => ''
        )
        ,'email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Email',
            'tip' => ''
        )
        ,'text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Сопроводительный текст',
            'tip' => ''
        )
        ,'amount' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Количество голосов',
            'tip' => 'Общее количество проголосовавших за компанию'
        )
        ,'id_konkurs' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'id_main_photo' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        ),
        'link' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на проект',
        ),
        'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'активен',2=>'не активен'),
            'label' => 'Статус участника',
            'tip' => 'Статус участника'
        )
    )
    ,'categories' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
         'id_konkurs' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
        'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название категории',
            'tip' => 'Полное название категории'
        )    
    )
    ,'status' => array(
        'active' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(1=>'Запущен',2=>'Остановлен'),
            'label' => 'Статус конкурса',
            'tip' => 'Запущен - есть возможность голосовать, Остановлен - только текстовая информация на странице'
        )
    )
    ,'konkurs' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
        'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название кокнурса',
            'tip' => 'Полное название кокнурса'
        ),
        'type' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Название типа конкурса',
            'tip' => 'Название типа конкурса'
        ),
        'text_begin_top' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст активного конкурса - 1',
            'tip' => 'Текст активного конкурса ДО рубрик голосования'
        ),
        'text_begin_bottom' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст активного конкурса - 2',
            'tip' => 'Текст активного конкурса ПОСЛЕ рубрик голосования'
        ),
        'text_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст завершенного конкурса',
            'tip' => 'Текст завершенного конкурса'
        ),
        'url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'URL конкурса',
            'tip' => 'URL конкурса'
        ),
        'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'активен',2=>'не активен'),
            'label' => 'Статус конкурса',
            'tip' => 'Статус конкурса'
        )
    )
);
?>