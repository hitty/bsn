<?php
return array(
    'pages' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'alias' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Алиас',
            'tip' => 'Алиас страницы (используется как часть URL)'
        )
        ,'url' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'plaintext',
            'label' => 'URL'
        )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Название страницы (title)'
        )
        ,'map_position' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'нет родителя (страница в корне)'),
            'label' => 'Страница-родитель',
            'tip' => 'Страница-предок по иерархии сайта'
        )
        ,'template' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Шаблон страницы',
            'tip' => 'Путь к файлу шаблона страницы'
        )
        ,'module' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Модуль/скрипт',
            'tip' => 'Название модуля (имя папки), или адрес скрипта, подключаемого к странице'
        )
        ,'no_require_params' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'запрещены',2=>'разрешены'),
            'label' => 'Параметры в странице',
            'tip' => 'Разрешение на передачу параметров в страницу через УРЛ'
        )
        ,'parameters' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Параметры для модуля',
            'tip' => 'Дополнительные параметры для PHP-модуля в форме строки GET-запроса'
        )
        ,'access' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Права доступа',
            'tip' => 'Набор прав доступа к странице по умолчанию'
        )
        ,'cache_time' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Время кэширования',
            'tip' => 'Время статичного кэширования страницы (сек)'
        )
        ,'block_page' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Блок',2=>'Страница'),
            'label' => 'Тип страницы',
            'tip' => 'Страница может быть вызвана по Url извне, а блок - только из шаблона'
        )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'содержимое шаблона',
            'tip' => ''
        )      
    )
);
?>