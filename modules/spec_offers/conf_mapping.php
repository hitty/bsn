<?php
return array(
    'objects' => array(
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
            'maxlength' => 42,
            'label' => 'Название (макс 42 знака)',
            'tip' => 'Название (подпись объекта)'
        )
        ,'annotation' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'maxlength' => 40,
            'label' => 'Описание (макс 40 знаков)',
            'tip' => 'Описание под названием'
        )
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
			'values' => array(0=>'- категория -'),
            'label' => 'Тип недвижимости',
            'tip' => ''
        )
        ,'id_packet' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
			'values' => array(0=>'- выберите пакет -'),
            'label' => 'Пакетное размещение',
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
		,'agent_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название агента/компании',
            'tip' => 'Название агента/компании которому принадлежит объект'
        )
		,'agent_coords' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
			'editor' => 'big',
            'label' => 'Координаты агента/компании',
            'tip' => 'Полная контактная информация агентства'
        )		
		,'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
			'editor' => 'big',
            'label' => 'Описание ',
            'tip' => 'Описание объекта на сайте БСН'
        )			
		,'cost' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость ',
            'tip' => 'Стоимость объекта'
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
				
        ,'_title_row_1_' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Управление спецпр. в основном разделе, на главной и в разделе нед-сти'
        )
        ,'base_page_flag' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'Основной раздел',
            'tip' => 'Объекты в разделах сайта - СПЕЦПРЕДЛОЖЕНИЯ'
        )		
        ,'first_page_flag' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'На главной, по центру',
            'tip' => ''
        )		
        ,'inestate_flag' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'В разделе нед-сти(ТГБ):',
            'tip' => ''
        )		
        ,'half_show' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => '50% от трафика',
            'tip' => 'показ блока каждые 1 раз за 2 просмотра страницы'
        )
        ,'position_on_main' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Рейтинг объекта',
            'tip' => 'Определяет позицию на странице. Чем больше значение, тем выше объект'
        )	
        ,'_title_row_2_' => array(
			'fieldtype' => 'title_row',
			'tip' => 'На главной в шапке'
        )
        ,'first_page_head_flag' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'Статус',
            'tip' => 'Объект в шапке (только на главной странице)'
        )
        ,'position_in_head' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Позиция/место на главной в шапке',
            'tip' => 'Чем меньше значение, тем левее объект'
        )	
         ,'main_img_link' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )					
         ,'head_img_link' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )					
         ,'main_img_src' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )					
         ,'head_img_src' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )					
    )
	,'packets' => array(
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
            'tip' => 'Название (подпись объекта)'
        )
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'select',
            'allow_empty' => false, 
            'allow_null' => false,
			'values' => array(0=>'- категория -'),
            'label' => 'Тип недвижимости',
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
		,'agent_coords' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
			'editor' => 'big',
            'label' => 'Координаты агента/компании',
            'tip' => 'Полная контактная информация агентства'
        )		
		,'code' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'URL для пакета ',
            'tip' => 'только латиница (например: packet_name )'
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
				
        ,'_title_row_1_' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Управление спецпр. в основном разделе, на главной и в разделе нед-сти'
        )
        ,'base_page_flag' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'Основной раздел',
            'tip' => 'Объекты в разделах сайта - СПЕЦПРЕДЛОЖЕНИЯ'
        )		
        ,'first_page_flag' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'На главной, по центру',
            'tip' => ''
        )		
        ,'inestate_flag' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'В разделе нед-сти(ТГБ):',
            'tip' => ''
        )		
        ,'position_on_main' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Рейтинг объекта',
            'tip' => 'Определяет позицию на странице. Чем больше значение, тем выше объект'
        )	
        ,'_title_row_2_' => array(
			'fieldtype' => 'title_row',
			'tip' => 'На главной в шапке'
        )
        ,'first_page_head_flag' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'Статус',
            'tip' => 'Объект в шапке (только на главной странице)'
        )
        ,'position_in_head' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Позиция/место на главной в шапке',
            'tip' => 'Чем меньше значение, тем левее объект'
        )	
         ,'main_img_link' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )					
         ,'head_img_link' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )					
         ,'main_img_src' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )					
         ,'head_img_src' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
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
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => ''
        )	
        ,'url' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'URL',
            'tip' => 'только латиница (например: packet_name )'
        )	
        ,'ttype' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'label' => 'Спецобозначение',
            'tip' => 'только латиница (уникальное поле для идентификации)'
        )	
	)
);
?>