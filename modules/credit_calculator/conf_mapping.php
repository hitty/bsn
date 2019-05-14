<?php
return array(
    'calculator' => array(
		'id' => array(
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
			'maxlength' => 45,
			'label' => 'Название',
			'tip' => 'Подпись под блок (макс.45 символов)'
		)
        ,'id_agency' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Банк',
            'tip' => '',
            'values' => array(0 => '- не выбрано -')
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
        ,'type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array( 0 =>'- выберите тип недвижимости -', 1 => 'Жилая', 2 => 'Стройка', 3 => 'Коммерческая', 4 => 'Загородная'),
            'label' => 'Тип недвижимости',
            'tip' => 'Тип недвижимости в карточках которой будет отображен калькулятор'
        )
        ,'in_search' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'label' => 'Выводить в результатах поиска',
            'tip' => 'Маленький банер в результатах поиска по типу недвижимости'
        )
        
        ,'first_payment' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Первонач.взнос, %',
            'tip' => ''
        )
        
        ,'months' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Срок кредита (месяцы)',
            'tip' => ''
        )
        ,'priority' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'min' => 1,
            'max'=>100,
            'fieldtype' => 'text',
            'label' => 'Приоритет, % ',
            'tip' => 'Приоритет показов баннеров в кампании'
        )     
        ,'percent' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Годовой процент по кредиту, %',
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
            'allow_null' => false,
            'fieldtype' => 'text',
			'placeholder' => 'http(s)://',
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
        ,'_title_row_colors_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_row',
            'tip' => 'Цвета блока'
        )            
        ,'color_cost' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 6,
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Цвет стоимости'
        )        
        ,'color_border' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Цвет рамки'
        )        
        ,'color_mortgage_bg' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Цвет плашки "Ипотека"'
        )        
        ,'color_mortgage_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Цвет текста "Ипотека"'
        )       
        ,'color_button' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Цвет кнопки'
        )        
        ,'color_button_hover' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Цвет кнопки при наведении'
        )        
        ,'color_button_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Цвет текста кнопки'
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