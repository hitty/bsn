<?php
return array(
    'comments' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'id_parent' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ID новости',
            'tip' => 'ID новостного события'
        )
        ,'comments_active' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'label' => 'Показывать комментарий',
            'tip' => 'Статус показа комментария'
        )
		,'comments_isnew' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'radio',
            'values' => array(1=>'не модерировано',2=>'отмодерировано'),
            'label' => 'Статус модерации комментария',
            'tip' => ''
        )
       ,'comments_datetime' => array(
            'type' => TYPE_STRING,
            'max' => 20,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Дата комментария',
            'tip' => 'Дата, когда был записан комментарий'
        )
        ,'author_name' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Имя пользователя',
            'tip' => 'Имя пользователя владельца комментария'
        )
        ,'comments_datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Дата',
            'tip' => 'Дата опубликования комментария'
        )
        ,'parent_type' => array(
            'type' => TYPE_INTEGER,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
			'values' => array(1=>'новости', 2=>'статьи', 3=>'календарь', 4=>'мнения', 5=>'вебинары', 6=>'прогнозы', 7=>'интервью', 8=>'ЖК'),
            'label' => 'Тип контента',
            'tip' => '1-новости, 2-статьи, 3-календарь событий, 4-мнения/прогнозы'
        )
        ,'comments_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст события',
            'tip' => 'Полное описание события'
        )		
    )
);
?>