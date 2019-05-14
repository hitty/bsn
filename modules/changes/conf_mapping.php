<?php
  return array(
    'changes' => array(
        'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        ),
        'id_user' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true,
            'allow_null' => true
        ),
        'datetime_create' => array(
            'type' => TYPE_DATETIME,
            'allow_empty' => false,
            'allow_null' => false,
            'nodisplay' => true
        ),
        'datetime_modify' => array(
            'type' => TYPE_DATETIME,
            'allow_empty' => false,
            'allow_null' => false,
            'nodisplay' => true
        ),
        'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок статьи'
        ),
        'content_short' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Анонс статьи',
            'tip' => 'Анонс статьи'
        ),
        'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст статьи',
            'tip' => 'Текст статьи'
        ),
        'id_project' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите проект -'),
            'label' => 'Проект',
            'tip' => 'Проект, к которому относится статья'
        ),
        'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Опубликовано',2=>'Не Опубликовано'),
            'label' => 'Статус статьи',
            'tip' => 'Статус статьи' 
        )
    )
);
?>
