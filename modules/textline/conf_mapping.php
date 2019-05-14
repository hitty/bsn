<?php
return array(
    'campaigns' => array(
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
            'label' => 'Название',
            'tip' => 'Название (подпись TextLine)'
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
        ,'id_manager' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите менеджера -'),
            'label' => 'Менеджер',
            'tip' => ''
        )           
        ,'enabled' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'архив'),
            'label' => 'Статус кампании',
            'tip' => 'Показывать - Не показывать'
        )        
        ,'type' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'обычный',2=>'спецбаннер пет.неда'),
            'label' => 'Тип баннера',
            'tip' => 'Показывается только на главной и в каталоге новостроек и жилой'
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
        ,'clicks_limit' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Лимит кликов',
            'tip' => 'Ссылка от рекламных агентств'
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
            'tip' => 'Сайт-источник перехода где размещается объявление'
        )    
        ,'utm_medium' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_medium',
            'tip' => 'Тип рекламного места (textline, banner, calculator, ...), откуда идет переход на сайт рекламодателя'
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
            'tip' => 'Название / Обозначение объявления, латиницей'
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
	)
    ,'banners' => array(
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
            'maxlength' => 50,
            'label' => 'Название (макс 50 знаков)',
            'tip' => 'Текст объявления, рекламный посыл, призыв к действию.'
        )
        ,'enabled' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'в архив'),
            'label' => 'Статус баннера',
            'tip' => 'Показывать - Не показывать'
        )        
        ,'id_campaign' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите кампанию -'),
            'label' => 'Кампания для размещения',
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
            'tip' => 'Сайт-источник перехода где размещается объявление'
        )    
        ,'utm_medium' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_medium',
            'tip' => 'Тип рекламного места (textline, banner, calculator, ...), откуда идет переход на сайт рекламодателя'
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
            'tip' => 'Название / Обозначение объявления, латиницей'
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
        ,'img_src' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )            
        ,'type' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )            
	)
);
?>