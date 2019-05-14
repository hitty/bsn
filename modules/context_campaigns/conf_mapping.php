<?php
return array(
    'context_campaigns' => array(
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
            'maxlength' => 45,
            'label' => 'Название рекламной кампании',
            'tip' => 'Название рекламной кампании'
        )
        ,'id_user' => array(
            'type' => TYPE_STRING,
            'max' => 25,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'maxlength' => 30,
            'label' => 'ID пользователя, на которого будет записана кампания',
            'tip' => 'ID пользователя, на которого будет записана кампания'
        )
        ,'date_start' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'datetime',
            'label' => 'Начало кампании',
            'tip' => ''
        )
        ,'date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'datetime',
            'label' => 'Окончание кампании',
            'tip' => ''
        )
        ,'balance' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Баланс кампании',
            'tip' => 'Баланс кампании'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'активна',2=>'не активна'),
            'label' => 'Статус рекламной кампании',
            'tip' => 'Активна - Не активна'
        )
        ,'description' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Описание рекламной кампании',
            'tip' => 'Описание рекламной кампании'
        )
    ),
    'context_advertisements' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'maxlength' => 30,
            'label' => 'Название рекламного блока',
            'tip' => 'Название рекламного блока'
        )
        ,'description' => array(
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Описание рекламного блока',
            'tip' => 'Описание рекламного блока'
        )
        ,'url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'placeholder' => 'http://',
            'label' => 'Ссылка на переход ',
            'tip' => 'Ссылка для перехода по клику'
        )
        ,'get_pixel' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => 'http://',
            'label' => 'Ссылка на счётчик (получение пикселя)',
            'tip' => 'Ссылка от рекламных агентств для снятия ими статистики'
        )        
        ,'deal_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'Тип сделки',
            'tip' => 'Продажа-Аренда'
        )
        ,'estate_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'Тип недвижимости',
            'tip' => ''
        )
        ,'block_type' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Изображение',2=>'Изображение и текст',3=>'Текст'),
            'label' => 'Вид блока',
            'tip' => ''
        )
        ,'id_place' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- место размещения -'),
            'label' => 'Место размещения',
            'tip' => ''
        )
        ,'banner_title' => array(
            'type' => TYPE_STRING,
            'max' => 32,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок объявления. Максимальная длина - 32 символа'
        )
        ,'banner_text' => array(
            'type' => TYPE_STRING,
            'max' => 80,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Текст',
            'tip' => 'Текст объявления. Максимальная длина - 80 символов'
        )
        ,'price_floor' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'Нижняя граница цены для выборки',
            'tip' => 'Нижняя граница цены для выборки'
        )
        ,'price_top' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'Верхняя граница цены для выборки',
            'tip' => 'Верхняя граница цены для выборки'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'активна',2=>'не активна',3=>'на модерации'),
            'label' => 'Статус рекламного блока',
            'tip' => 'Активна - Не активна'
        )
        ,'delayed_sql' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'hidden',
            'label' => 'Отложенные sql-запросы',
            'tip' => 'Отложенные sql-запросы'
        )
    )
);
?>