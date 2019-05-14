<?php
return array(
    'calendar_events' => array(
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
            'tip' => 'Заголовок для события'
        )
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Категория мероприятия'
        )        
        ,'place' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Место проведения',
            'tip' => 'Выставочный зал, форум, площадка и т.д.'
        )
        ,'city' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Город проведения',
            'tip' => 'Город, область, район, страна'
        )
        ,'time' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Время проведения',
            'tip' => 'Пример: 10:30 - 20:00'
        )
        ,'date_begin' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'date',
            'label' => 'Дата начала проведения',
            'tip' => ''
        )
        ,'date_end' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата окончания проведения',
            'tip' => ''
        )
        ,'paid' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'Да'),
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        )
        ,'show_comments' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        )
        ,'text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст события',
            'tip' => 'Полное описание события'
        )		
        ,'site' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'URL события',
            'tip' => 'Сайт компании'
        )
        ,'registration' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Можно регистрироваться',
            'tip' => 'На странице события есть форма регистрации'
        )
        ,'manager_email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Email менеджера',
            'tip' => 'Email менеджера'
        )
        ,'_title_row_1_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Форма регистрации'
        )
        ,'registration_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Название'
        )
        ,'registration_url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'URL',
            'tip' => 'URL'
        )
        ,'registration_invited_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Текст пользователя',
            'tip' => 'Текст пользователя'
        )
    )
);
?>