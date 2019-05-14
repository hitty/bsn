<?php
return array(
    'normalspam' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название рассылки',
            'tip' => 'Название рассылки'
        )
        ,'subject' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Тема рассылки в теме письма',
            'tip' => 'Тема рассылки в теме письма'
        )
        ,'type' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'БСН',2=>'Dizbook',3=>'Interestate'),
            'label' => 'Источник рассылки',
            'tip' => 'От чьего имени происходит рассылка'
        )        
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст рассылки',
            'tip' => 'Текст рассылки'
        )
        ,'up_banner' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )
        ,'down_banner' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Опубиковано(начать рассылку)',2=>'Не опубликовано'),
            'label' => 'Статус',
            'tip' => 'Состояние рассылки'
        )
    ),
    'specspam' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название рассылки',
            'tip' => 'Название рассылки'
        )
        ,'subject' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Тема рассылки в теме письма',
            'tip' => 'Тема рассылки в теме письма'
        )
        ,'type' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'БСН',2=>'Dizbook',3=>'Interestate'),
            'label' => 'Источник рассылки',
            'tip' => 'От чьего имени происходит рассылка'
        )        
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст рассылки',
            'tip' => 'Текст рассылки'
        )
        ,'up_banner' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )
        ,'down_banner' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Опубиковано(начать рассылку)',2=>'Не опубликовано'),
            'label' => 'Статус',
            'tip' => 'Состояние рассылки'
        )
    ),    
    'normalspam_test' => array(
         'email' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'E-mail',
            'tip' => 'E-mail, на который будет отправлено письмо с рассылкой'
        )
    ),
    'spec_emails' => array(
         'emails' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'class' => 'spec_emails_list',
            'label' => 'E-mail спец-рассылки',
            'tip' => 'E-mail (одна строка - один адрес), на которые будут отправлены письма со спец-рассылкой'
        ),
    )
)
?>