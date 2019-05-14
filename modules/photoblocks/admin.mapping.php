<?php
return array(
    'photoblocks' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'Название фотоблока',
            'tip' => 'Полное название фотоблока'
        )
        ,'published' => array(
            'min' => 1,
			'max' => 2,
			'type' => TYPE_INTEGER,
            'fieldtype' => 'radio',
			'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'Статус фотоблока',
            'tip' => 'Будет ли показываться фотоблок'
        )	
		,'txt_addr' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'textarea',
            'label' => 'Полный адрес фотоблока',
            'tip' => ''
        )		
		,'txt_cost' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'Полная стоимость',
            'tip' => ''
        )
		,'object_info' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'textarea',
			'editor' => 'small',
            'label' => 'Полное описание фотоблока',
            'tip' => ''
        )	
		,'company' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'Название компании ',
            'tip' => ''
        )	
		,'contact_name' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,			
            'fieldtype' => 'text',
            'label' => 'Контактное лицо компании ',
            'tip' => ''
        )	
        ,'phone' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Телефон компании',
            'tip' => ''
        )
        ,'email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Email компании',
            'tip' => ''
        )	
		,'url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,			
            'fieldtype' => 'text',
            'label' => 'URL компании',
            'tip' => ''
        )												
        ,'title_row_1' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Страницы вывода фотоблока'
        )
        ,'r_live_rent' => array(
            'min' => 1,
			'max' => 2,
			'type' => TYPE_INTEGER,
            'fieldtype' => 'radio',
			'values' => array(1=>'да',2=>'нет'),
            'label' => 'Квартиры/комнаты - аренда',
            'tip' => ''
        )
        ,'r_live_sell' => array(
            'min' => 1,
			'max' => 2,
			'type' => TYPE_INTEGER,
            'fieldtype' => 'radio',
			'values' => array(1=>'да',2=>'нет'),
            'label' => 'Квартиры/комнаты - продажа',
            'tip' => ''
        )
        ,'r_build' => array(
            'min' => 1,
			'max' => 2,
			'type' => TYPE_INTEGER,
            'fieldtype' => 'radio',
			'values' => array(1=>'да',2=>'нет'),
            'label' => 'Новостройки',
            'tip' => ''
        )
		,'r_commercial_rent' => array(
            'min' => 1,
			'max' => 2,
			'type' => TYPE_INTEGER,
            'fieldtype' => 'radio',
			'values' => array(1=>'да',2=>'нет'),
            'label' => 'Коммерческие - аренда',
            'tip' => ''
        )
        ,'r_commercial_sell' => array(
            'min' => 1,
			'max' => 2,
			'type' => TYPE_INTEGER,
            'fieldtype' => 'radio',
			'values' => array(1=>'да',2=>'нет'),
            'label' => 'Коммерческие - продажа',
            'tip' => ''
        )        
		,'r_country_rent' => array(
            'min' => 1,
			'max' => 2,
			'type' => TYPE_INTEGER,
            'fieldtype' => 'radio',
			'values' => array(1=>'да',2=>'нет'),
            'label' => 'Дома и участки - аренда',
            'tip' => ''
        )
        ,'r_country_sell' => array(
            'min' => 1,
			'max' => 2,
			'type' => TYPE_INTEGER,
            'fieldtype' => 'radio',
			'values' => array(1=>'да',2=>'нет'),
            'label' => 'Дома и участки - продажа',
            'tip' => ''
        )		
	)
);
?>