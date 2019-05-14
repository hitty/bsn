<?php
  return array(
    'articles' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
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
        'text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Статья',
            'tip' => 'Текст статьи'
        ),
        'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => ''
        ),
        'position' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Приоритет',
            'tip' => 'Приоритет в списке (чем меньще число, тем выше в списке)'
        ), 
        'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Опубликована',2=>'Не Опубликована'),
            'label' => 'Статус статьи',
            'tip' => 'Статус статьи'
        )
    ),
    'categories' => array(
        'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        ),
        'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок категории'
        ),
        'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Описание категории',
            'tip' => 'Описание категории'
        ),
        'position' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Приоритет',
            'tip' => 'Приоритет в списке (чем меньще число, тем выше в списке)'
        ), 
        'published' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Опубликована',2=>'Не Опубликована'),
            'min' => 1,
            'max' => 2,
            'label' => 'Статус статьи',
            'tip' => 'Статус статьи'
        )   
    )
);
?>
