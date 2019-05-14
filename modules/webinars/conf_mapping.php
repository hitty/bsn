<?php
return array(
    'webinars' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Дата и время события',
            'tip' => ''
        )
        ,'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Название'
        )
        ,'url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'disable',
            'label' => 'URL',
            'tip' => 'URL'
        )
        ,'text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'editor' => 'big',
            'fieldtype' => 'textarea',
            'label' => 'Описание',
            'tip' => 'Текст'
        ),
        'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'не начат',2=>'окончен'),
            'label' => 'Статус вебинара',
            'tip' => 'Статус вебинара'
        ),        
        'registration_limits' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Кол-во регистраций',
            'tip' => 'Лимит регистраций пользователей'
        )        
        ,'file_link' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на файл с видео',
            'tip' => 'Ссылка на файл с видео'
        )
    )
);
?>