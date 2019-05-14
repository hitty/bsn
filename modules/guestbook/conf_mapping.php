<?php
return array(
    'guestbook' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'question' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'label',
            'label' => 'Вопрос',
            'tip' => 'Вопрос'
        )
        ,'answer' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Ответ',
            'tip' => 'Ответ'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Опубликован',2=>'Не Опубликован'),
            'label' => 'Статус вопроса',
            'tip' => 'Статус вопроса'
        )
    )
);
?>