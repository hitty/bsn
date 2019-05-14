<?php
return array(
    'events_registration' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'event_date' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Дата события',
            'tip' => 'Дата события'
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
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'URL',
            'tip' => 'URL'
        )
        ,'invited_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Текст пользователя',
            'tip' => 'Текст пользователя'
        )
        ,'manager_email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Email менеджера',
            'tip' => 'Email менеджера'
        )
        ,'id_calendar' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ID статьи Календаря',
            'tip' => 'Текст статьи будет располагаться выше формы регистрации'
        )
        ,'_title_row_1_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Поля вывода в форме регистрации'
        )
        ,'registration_fields' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'set',
            'values' => array(1=>'ФИО',2=>'Телефон',3=>'Email',4=>'Компания',5=>'Должность',6=>'Пожелания'),
            'label' => '',
            'tip' => ''
        )
    )
);
?>