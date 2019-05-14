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
        ,'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'maxlength' => 100,
            'label' => 'Название (макс 100 знаков)',
            'tip' => 'Название (подпись объекта)'
        )
        ,'annotation' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 100,
            'label' => 'Описание (макс 100 знаков)',
            'tip' => 'Описание под названием'
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
        ,'id_manager' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите менеджера -'),
            'label' => 'Менеджер',
            'tip' => ''
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
        ,'id_position' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите расположение -'),
            'label' => 'Расположение на сайте',
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
        ,'shows_limit' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Макс.кол-во показов',
            'tip' => 'При достижения лимита баннер в архиве'
        )       
        
		,'priority' => array(
			'type' => TYPE_INTEGER,
			'fieldtype' => 'text',
            'allow_empty' => false, 
            'allow_null' => false,
            'max' => 100,
			'label' => 'Приоритет, % ',
			'tip' => 'Приоритет показов баннеров в кампании'
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
        ,'utm' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'utm метки'
        )
        
        ,'utm_source' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_source',
            'tip' => 'Сайт-источник перехода где размещается баннер'
        )    
        ,'utm_medium' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_medium',
            'tip' => 'Тип рекламного места (banners, banner, calculator, ...), откуда идет переход на сайт рекламодателя'
        )    
        ,'utm_campaign' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_campaign',
            'tip' => 'Название рекламодателя или рекламной кампании, латиницей'
        )    
        
        ,'utm_content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_content',
            'tip' => 'Название / Обозначение баннера, латиницей'
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
        
        ,'_title_row_spec_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Раздел'
        )
      
        ,'zones' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'set',
            'false_value' => 2, 'true_value' => 1,
            'values' => array(1=>'Жилая',2=>'Стройка',3=>'Коммерческая',4=>'Загородная',5=>"ЖК",6=>"КП",7=>"БЦ"),
            'label' => 'Разделы для показа',
            'tip' => ''
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