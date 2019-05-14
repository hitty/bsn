<?php
return array(
    'video_konkurs' => array(
		'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
		)
		,'title' => array(
			'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
			'label' => 'Название',
			'tip' => ''
		)
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'label' => 'Модерация'
        )
		,'sms_status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'label' => 'Пополнение баланса'
		)
        ,'name' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'disabled' => 'disabled',
            'label' => 'ФИО'
        )            
        ,'phone' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'disabled' => 'disabled',
            'label' => 'Телефон'
        )            
        ,'email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'disabled' => 'disabled',
            'label' => 'Email'
        )			
						
	)
);
?>