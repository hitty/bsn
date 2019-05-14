<?php
return array(
    'districts' => array(
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
            'label' => 'Название',
        )    
        ,'title_genitive' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',            
            'label' => 'Название - родительный падеж',
        )    
        ,'housing_estates_ids' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',            
            'label' => 'Список ID ЖК',
        )    
        ,'id_articles' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',            
            'label' => 'ID статьи',
        ) 
        ,'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Справочная информация'
        )        
    ),
    'experts' => array(
        'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'token' => array(
            'type' => TYPE_STRING,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'passwd' => array(
            'type' => TYPE_STRING,
            'max' => 255, 
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'password',
            'autocomplete' => true,
            'label' => 'Пароль',
            'tip' => 'Пароль эксперта (скрыт в целях безопасности, можно только заменить)'
        )
        ,'name' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Имя эксперта',
            'tip' => 'Имя эксперта'
        )
        ,'lastname' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Фамилия эксперта',
            'tip' => 'Фамилия эксперта'
        )
        ,'job' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Должность',
            'tip' => 'Должность эксперта'
        )
        ,'email' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'E-mail эксперта',
            'tip' => 'E-mail эксперта'
        )        
        ,'districts' => array(
            'type' => TYPE_STRING,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'rich_checkbox_set',
            'label' => 'Районы'
        )
        ,'date' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'date',
            'label' => 'Дед лайн',
            'tip' => ''
        )      
        ,'resume' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Резюме по району'
        )
        
        ,'sent_mail' => array(
            'type' => TYPE_INTEGER,
            'max' => 3, 'min' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'label' => 'Отправить приглашение на email',
            'values' => array(1=>'Да', 2=>'Нет', 3=>'Уже отослано')
        )
        
    )
);
?>