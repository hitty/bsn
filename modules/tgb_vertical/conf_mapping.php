<?php
return array(
    'banners' => array(
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
			'maxlength' => 40,
			'label' => 'Название',
			'tip' => 'Подпись под блок'
		)
		,'enabled' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'Статус баннера',
            'tip' => 'Показывать - Не показывать'
		)
        ,'position' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Справа', 2=>'Слева'),
            'label' => 'Местоположение баннера',
            'tip' => ''
        )        
        
		,'date_start' => array(
			'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
			'fieldtype' => 'date',
			'label' => 'Старт показа',
			'tip' => ''
		)
		,'date_end' => array(
			'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
			'fieldtype' => 'date',
			'label' => 'Окончание показа',
			'tip' => ''
		)		
		,'direct_link' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
			'placeholder' => 'http://',
            'label' => 'Ссылка на переход ',
            'tip' => 'Ссылка от рекламных агентств'
        )	
		,'get_pixel' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
			'placeholder' => 'http://',
            'label' => 'Ссылка на счётчик (получение пикселя)',
            'tip' => 'Ссылка от рекламных агентств для снятия ими статистики'
        )		
        ,'img_link' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'img_src' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )			
						
	)
);
?>