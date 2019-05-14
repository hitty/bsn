<?php
return array(
    'categories' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
         'code' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Алиас',
            'tip' => 'Алиас (для использования в url)'
        ),
        'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Заголовок для категории'
        )
    ),
    'tags' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
         'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Выберите категорию тегов'
        ),   
        'tag_count' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'plaintext',
            'label' => 'Количество'
        ),              
        'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Содержимое тега (сам тег)'
        )
    )
);
?>