<?php
return array(
    'seo' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'url' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'URL страницы',
            'tip' => 'URL страницы'
        )
        ,'only_params' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Только СЕО-параметры'
        )
        
        ,'pretty_url' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Подменный URL',
            'tip' => 'Подменный (красивый) URL страницы'
        )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'TITLE',
            'tip' => 'Заголовок TITLE для страницы'
        )
        ,'h1_title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'H1',
            'tip' => 'Заголовок H1 для страницы'
        )
        ,'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'DESCRIPTION',
            'tip' => 'Описание для страницы'
        )
        ,'keywords' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'KEYWORDS',
            'tip' => 'Ключевики для страницы'
        )
        ,'seo_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'SEO текст',
            'tip' => 'SEO текст'
        )
    )
);
?>