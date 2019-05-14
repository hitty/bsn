<?php
return array(
    'consults' => array(
        'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [],
            'label' => 'Категория',
            'tip' => ''
        )
        ,'_title_row_1_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Вопрос'
        )
         ,'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'id_respondent_user' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок вопроса',
            'tip' => ''
        )
        ,'question' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Текст вопроса',
            'tip' => ''
        )
        ,'name' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ФИО',
            'tip' => 'ФИО эксперта'
        )
        ,'email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Email',
            'tip' => 'Email эксперта'
        )
        ,'question_datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true,   
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Дата задания вопроса',
            'tip' => ''
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 8,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'опубликован',2=>'на модерации',3=>'отклонен',4=>'ожидает публикации',5=>'в архиве',6=>'нигде'),
            'label' => 'Статус вопроса',
            'tip' => 'Статус вопроса'
        )
        ,'visible_to_all' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'виден всем',2=>'только адресату'),
            'label' => 'Видимость вопроса',
            'tip' => 'Видимость вопроса'
        )
    ),
    'answers' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'answer' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'Small',
            'label' => 'Текст ответа',
            'tip' => ''
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'опубликован',2=>'на модерации',3=>'не прошел модерацию',4=>'в архиве'),
            'label' => 'Статус ответа',
            'tip' => 'Статус ответа'
        )
    ),
    'members' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'name' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ФИО',
            'tip' => 'ФИО юриста'
        )        
        ,'login' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Логин',
            'tip' => 'Логин юриста'
        )        
        ,'passwd' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Пароль',
            'tip' => 'пароль юриста'
        )
        ,'company' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Должность, компания',
            'tip' => 'Должность эксперта'
        )        
        ,'phone1' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'phone',
            'label' => 'Телефон 1',
            'tip' => ''
        )            
        ,'phone2' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'phone',
            'label' => 'Телефон 2',
            'tip' => ''
        )            
        ,'phone3' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'phone',
            'label' => 'Телефон 3',
            'tip' => ''
        )                     
        ,'fax' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'Факс',
            'tip' => ''
        )                     
        ,'url' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'Сайт',
            'tip' => ''
        )                
        ,'email' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'Email',
            'tip' => ''
        )					 

    )	
    ,'categories' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название категории',
            'tip' => 'Полное название категории'
        )        
        ,'code' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'url',
            'tip' => 'Только латиница'
        )        
        ,'priority' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,            
            'fieldtype' => 'text',
            'label' => 'Позиция',
            'tip' => ''
        )	
	)

);
?>