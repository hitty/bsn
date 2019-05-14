<?php
return array(
    'cottages' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'lat' => array(
            'type' => TYPE_FLOAT,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'lng' => array(
            'type' => TYPE_FLOAT,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'id_user' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )        
        ,'id_seller' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'exclusive_seller' => array(
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
            'label' => 'Название поселка',
            'tip' => 'Полное название поселка'
        )
        ,'yandex_id' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ID в базе яндекса',
            'tip' => 'ID в базе яндекса'
        )
        ,'id_developer' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите девелопера -'),
            'label' => 'Девелопер',
            'tip' => '',
            'nodisplay' => true
        )
        ,'site' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Сайт',
            'tip' => ''
        )         
		,'contacts' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
			'editor' => 'small',
            'label' => 'Контакты ',
            'tip' => 'Контактная информация поселка'
        )	
        ,'txt_addr' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Адрес поселка',
            'tip' => 'Текстовый адрес поселка'
        )
        ,'id_district_area' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
			'values' => array(0=>'- выберите район -'),
            'label' => 'Район области',
            'tip' => 'Район Ленинградской области'
        )
        ,'cad_length' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Расстояние до КАДа',
            'tip' => 'Расстояние до КАДа, км'
        )
        ,'id_direction' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
			'values' => array(0=>'- выберите направление -'),
            'label' => 'Направление',
            'tip' => 'Географическое направление поселка'
        )
        ,'id_stady' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
			'values' => array(0=>'- выберите статус -'),
            'label' => 'Статус объекта',
            'tip' => ''
        )
        ,'id_build_complete' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите срок сдачи -'),
            'label' => 'Срок сдачи'
        )
        
        ,'get_pixel' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на пиксель',
            'tip' => ''
        )                                           
        ,'advert_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Рекламный блок',
            'tip' => 'Например код вставка Онлайн консультанта'
        )        
        ,'advanced' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(2=>'нет', 1=>'да'),
            'label' => 'Выделенный объект',
            'tip' => 'Карточка без рекламы, доп.условия показов'
        )
        ,'date_start' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Старт показа',
            'tip' => ''
        )
        ,'date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Окончание показа',
            'tip' => ''
        )          
        ,'title_row_1' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Участки'
        )
         ,'id_u_status' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите статус -'),
            'label' => 'Статус участка',
            'tip' => ''
        )
        ,'u_count' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Всего участков',
            'tip' => 'Общее количество участков'
        )
        ,'u_sb' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь участка ОТ',
            'tip' => 'Площадь участка ОТ (сотка)'
        )
        ,'u_se' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь участка ДО',
            'tip' => 'Площадь участка ДО (сотка)'
        )
        ,'u_cost_sb' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость  ОТ (р/с)',
            'tip' => 'Стоимость  ОТ, руб./сотка'
        )
        ,'u_cost_se' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость  ДО (р/с)',
            'tip' => 'Стоимость  ДО, руб./сотка'
        )
        ,'u_cost_ub' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость участка ОТ',
            'tip' => 'Стоимость  ОТ (руб.)'
        )
        ,'u_cost_ue' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость участка ДО',
            'tip' => 'Стоимость  ДО (руб.)'
        )
        ,'u_cost_scharge' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Доплата за коммуникации',
            'tip' => 'Доплата за коммуникации (руб.)'
        )
        ,'u_state' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стадия строительства',
            'tip' => ''
        )				
		
        ,'title_row_3' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Коттеджи'
        )
        ,'c_count' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Всего котт.',
            'tip' => 'Общее количество котт.'
        )
        ,'c_csb' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь котт. ОТ',
            'tip' => 'Площадь котт. ОТ (кв.м.)'
        )
        ,'c_cse' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь котт. ДО',
            'tip' => 'Площадь котт. ДО (кв.м.)'
        )
        ,'c_usb' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь участка ОТ',
            'tip' => 'Площадь участка. ОТ (сотка)'
        )
        ,'c_use' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь участка ДО',
            'tip' => 'Площадь участка ДО (сотка)'
        )		
        ,'c_cost_sb' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость кв.м  ОТ (р/м)',
            'tip' => 'Стоимость кв.м  ОТ, руб./кв.м.'
        )
        ,'c_cost_se' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость кв.м  ДО (р/м)',
            'tip' => 'Стоимость кв.м  ДО, руб./кв.м.'
        )
        ,'c_cost_cb' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость домовладения ОТ',
            'tip' => 'Стоимость  ОТ (руб.)'
        )
        ,'c_cost_ce' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость домовладения ДО',
            'tip' => 'Стоимость  ДО (руб.)'
        )
        ,'c_cost_scharge' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Доплата за коммуникации',
            'tip' => 'Доплата за коммуникации (руб.)'
        )
        ,'c_constr' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Конструктив',
            'tip' => ''
        )	
        ,'c_state' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стадия строительства',
            'tip' => ''
        )				
		,'title_row_4' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Таун-хаусы'
        )
        ,'t_count' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Всего ТХ.',
            'tip' => 'Общее количество ТХ.'
        )
        ,'t_tsb' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь ТХ. ОТ',
            'tip' => 'Площадь ТХ. ОТ (кв.м.)'
        )
        ,'t_tse' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь ТХ. ДО',
            'tip' => 'Площадь ТХ. ДО (кв.м.)'
        )
        ,'t_usb' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь участка ОТ',
            'tip' => 'Площадь участка. ОТ (сотка)'
        )
        ,'t_use' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь участка ДО',
            'tip' => 'Площадь участка ДО (сотка)'
        )		
        ,'t_cost_sb' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость кв.м  ОТ (р/м)',
            'tip' => 'Стоимость кв.м  ОТ, руб./кв.м.'
        )
        ,'t_cost_se' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость кв.м  ДО (р/м)',
            'tip' => 'Стоимость кв.м  ДО, руб./кв.м.'
        )
        ,'t_cost_b' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость ТХ ОТ',
            'tip' => 'Стоимость  ОТ (руб.)'
        )
        ,'t_cost_e' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость ТХ ДО',
            'tip' => 'Стоимость  ДО (руб.)'
        )
        ,'t_state' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стадия строительства',
            'tip' => ''
        )			
		
        ,'title_row_5' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Квартиры'
        )
        ,'k_count' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Всего кварт.',
            'tip' => 'Общее количество кварт.'
        )
        ,'k_sb' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь кварт. ОТ',
            'tip' => 'Площадь кварт. ОТ (кв.м.)'
        )
        ,'k_se' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Площадь кварт. ДО',
            'tip' => 'Площадь кварт. ДО (кв.м.)'
        )
        ,'k_cost_b' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость кварт. ОТ',
            'tip' => 'Стоимость  ОТ (руб.)'
        )
        ,'k_cost_e' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость кварт. ДО',
            'tip' => 'Стоимость  ДО (руб.)'
        )
        ,'k_state' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стадия строительства',
            'tip' => ''
        )			
		
        ,'title_row_2' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Общая информация'
        )
        ,'start_advert' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Старт рекламы',
            'tip' => ''
        )		
        ,'start_sale' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Старт продаж',
            'tip' => ''
        )		
        ,'communications' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Коммуникации',
            'tip' => ''
        )		
        ,'nature' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Природа',
            'tip' => ''
        )		
        ,'land_status' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Статус земли',
            'tip' => ''
        )		        
		,'infrastructure' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Инфрастуктура',
            'tip' => ''
        )	
		,'notes' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
			'editor' => 'small',
            'label' => 'Информация',
            'tip' => 'Дополнительная информация'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
			'values' => array(1=>'открытый',2=>'закрытый'),
            'label' => 'Доступ поселка',
            'tip' => 'Выводить или нет в октрытый доступ'
        )		   
        ,'title_row_6' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Информация о менеджере и девелопере'
        )        
        ,'id_manager' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Менеджер',
            'tip' => 'Ответственный менеджер ОП'
        )        
    ),
    'developers' => array(
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
            'tip' => 'Название девелопера'
        )    
        ,'email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Контактный Email',
            'tip' => 'Email для отправки запросов на просмотр'
        )    
     ),

   'country_demand' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'month' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array('- выберите месяц -', 'январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'),
            'label' => 'Месяц отчета',
            'tip' => 'по который делается включительно'
        ) 
        ,'year' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Год',
            'tip' => ''
        )
        ,'tblock_1' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Развернутый текст',
            'tip' => ''
        )              
        ,'tblock_2' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Баннеры для рекламы',
            'tip' => ''
        )              
        ,'tblock_3' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Развернутый текст',
            'tip' => ''
        )              
        ,'tblock_4' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Развернутый текст',
            'tip' => ''
        )              
        ,'tblock_5' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Развернутый текст',
            'tip' => ''
        )              
        ,'tblock_6' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Развернутый текст',
            'tip' => ''
        )              
        ,'tblock_7' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Развернутый текст',
            'tip' => ''
        )
     ) 
    ,'country_demand_members' => array(
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
            'label' => 'Название',
            'tip' => ''
        )       
        ,'text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Проекты',
            'tip' => ''
        )
    )                           
    ,'settlements' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'month' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array('- выберите месяц -', 'январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'),
            'label' => 'Месяц',
            'tip' => ''
        ) 
        ,'year' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Год',
            'default_value' => 0,
            'tip' => ''
        ) 
        ,'title_row_1' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Общая информация (кол-во)'
        )
        ,'project_cnt' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Кол-во проектов',
            'tip' => ''
        )                                 
        ,'cnt_deals' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Всего сделок',
            'tip' => ''
        )                                 
        ,'cnt_uch_bi' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'N уч. без инж (нарезка)',
            'tip' => ''
        )                                 
        ,'cnt_uch_si' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'N уч. с инж (участки)',
            'tip' => ''
        )                                 
        ,'cnt_cott' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'N коттеджей (коттеджи)',
            'tip' => ''
        )                                 
        ,'cnt_th' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'N тх (таун-хаузы)',
            'tip' => ''
        ) 
        ,'title_row_2' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Выручка'
        )                                        
        ,'cost_all' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Выручка',
            'tip' => ''
        )                                
        ,'cost_bi' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => '$ уч. БИ (нарезка)',
            'tip' => ''
        )                                
        ,'cost_si' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => '$ уч. СИ (участки)',
            'tip' => ''
        )                                
        ,'cost_cott' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => '$ Котт. (коттеджи)',
            'tip' => ''
        )                                
        ,'cost_th' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => '$ тх (таун-хаузы)',
            'tip' => ''
        )                                
        ,'title_row_3' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Площади'
        )                                        
        ,'s_bi' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'S уч. БИ (нарезка)',
            'tip' => ''
        )                                
        ,'s_si' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'S уч. СИ (участки)',
            'tip' => ''
        )                                
        ,'s_cott' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'S Котт. (коттеджи)',
            'tip' => ''
        )                                
        ,'s_cott_uch' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'S уч. Котт.',
            'tip' => ''
        )                                
        ,'s_th' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'S тх (таун-хаузы)',
            'tip' => ''
        ) 
        ,'title_row_4' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Средние'
        )                                          
        ,'avg_cost' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср. цена договора',
            'tip' => ''
        )                                
        ,'avg_cost_bi' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср. цена уч. БИ (нарезка)',
            'tip' => ''
        )                                
        ,'avg_cost_si' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср. цена уч. СИ (участки)*',
            'tip' => ''
        )                                
        ,'avg_cost_cott' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср. цена Котт. (коттеджи)*',
            'tip' => ''
        )                                
        ,'avg_cost_th' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср. цена тх (таун-хаузы)*',
            'tip' => ''
        ) 
        ,'title_row_5' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Усредненное'
        )                                          
        ,'deals_pp' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Сделок на проект',
            'tip' => ''
        )                                
        ,'cost_pp' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Выручка на проект',
            'tip' => ''
        )                                
        ,'avg_cost100_bi' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср. - цена сотки БИ (нарезка)',
            'tip' => ''
        )                                
        ,'avg_cost100_si' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср цена сотки СИ (участки)*',
            'tip' => ''
        )                                
        ,'avg_costm_cott' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср цена кв.м. коттеджа (коттеджи)*',
            'tip' => ''
        )                                
        ,'avg_costm_th' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ср цена кв.м. тх (таун-хаузы)*',
            'tip' => ''
        ) 
    )
);
?>