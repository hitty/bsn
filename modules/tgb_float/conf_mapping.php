<?php
return array(
    'banners' => array(
		'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
		)
        ,'id_user' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )       
        ,'agency_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/estate/business_centers/agencies/list/',
            'input'=>'id_user',
            'label' => 'Рекламодатель'
        )
		,'title' => array(
			'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
			'maxlength' => 40,
			'label' => 'Надпись на блоке',
			'tip' => 'Надпись на блоке'
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
        ,'top_color' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 6,
            'placeholder' => '6 символов, без #, например: 1E88E5',
            'label' => 'Цвет верхушки',
            'default' => '1E88E5'
        )
        ,'background_color' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 6,
            'placeholder' => '6 символов, без #, например: DFECF6',
            'label' => 'Цвет фона',
            'default' => 'DFECF6'
        )        
        ,'button_color' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 6,
            'placeholder' => '6 символов, без #, например: A9D71D',
            'label' => 'Цвет кнопки "Перезвоните"',
            'default' => 'A9D71D'
        )        
        ,'button_text_color' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 6,
            'placeholder' => '6 символов, без #, например: 000000',
            'label' => 'Цвет текста кнопки "Перезвоните"',
            'default' => '000000'
        )        
        ,'initial_bordercolor' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 6,
            'placeholder' => 'Нужен если нижний блок, например, белый и сливается',
            'label' => 'Цвет границы блока внизу"',
            'default' => ''
        )        
		,'enabled' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'value'=>2,
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'Статус баннера',
            'tip' => 'Показывать - Не показывать'
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
	)
);
?>