<?php
return array(
    'business_centers' => array(
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
            'label' => 'Название БЦ',
            'tip' => ''
        )         
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'опубликован',2=>'в архиве'),
            'label' => 'Состояние объявления',
            'tip' => 'Состояние/статус объекта'
        )
        ,'geolocation_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',            
            'hidden' => true
        )
        ,'geolocation' => array(
            'fieldtype' => 'plaintext+button',
            'allow_empty' => true,
            'label' => 'Геолокация',
            'tip' => 'Геоданные объекта по справочнику'
        )
        ,'id_district' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'hidden',
            'hidden' => true
        )
        ,'district_title' => array(
            'fieldtype' => 'plaintext',
            'allow_empty' => true,
            'label' => 'Район города',
            'tip' => 'Район города'
        )
        ,'id_subway' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'hidden',
            'hidden' => true
        )
        ,'subway_title' => array(
            'fieldtype' => 'plaintext',
            'allow_empty' => true,
            'label' => 'Метро',
            'tip' => 'Станция метро'
        )
        ,'way_time' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Время в пути от метро',
            'tip' => 'Время в пути от метро'
        )
        ,'id_way_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- добраться от метро -'),
            'label' => 'добраться от метро',
            'tip' => 'добраться от метро (каким транспортом)'
        )
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )
        ,'txt_street' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Улица',
            'tip' => 'Улица',
            'class' => 'typewatch'
        )
        ,'house' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Номер дома',
            'tip' => 'Номер дома'
        )
        ,'corp' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Корпус',
            'tip' => 'Корпус'
        )
        ,'txt_addr' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Адрес',
            'tip' => 'Адрес (текстовый варант)'
        )
        ,'map' => array(
            'fieldtype' => 'map'
        )
        ,'lat' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )        
        ,'lng' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )         

        ,'class' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Класс БЦ'
        )  
        
        
        ,'shortdescr' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Описание (анонс)'
        )
        ,'fulldescr' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Полное описание'
        )
        ,'site' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Сайт',
            'tip' => ''
        )         
        ,'transport' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Транспорт.доступность'
        )
        ,'buildyear' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Год постройки'
        ) 
        ,'remontyear' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Год ремонта'
        ) 
        ,'startyear' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Год начала строительства'
        ) 
        ,'officeareasmin' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Мин. площадь офисов'
        ) 
        ,'officeareasmax' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Макс.площадь офисов'
        ) 
        ,'allarea' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Общая площадь БЦ'
        ) 
        ,'rentarea' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Суммарная площадь для аренды'
        ) 
        ,'lift' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Лифт'
        )
        ,'climatesystem' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Климат'
        )
        ,'m2monthcostmin' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Мин. цена за м2 / мес'
        ) 
        ,'m2monthcostmax' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Макс. цена за м2 / мес'
        ) 
        ,'m2yearcostmin' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Мин. цена за м2 / год'
        ) 
        ,'m2yearcostmax' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Макс. цена за м2 / год'
        )
        ,'otherrentcond' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Другие условия аренды'
        ) 
        ,'wareareasmin' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Мин. площадь складских помещений'
        )
        ,'wareareasmax' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Макс. площадь складских помещений'
        )
        ,'warecostm2min' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Мин. цена за м2 склад. помещений'
        )
        ,'warecostm2max' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Макс. цена за м2 склад. помещений'
        )
        ,'infra' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Инфраструктура'
        )
        ,'food' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Общепит'
        )
        ,'shops' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Магазины'
        )
        ,'service' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Сервисные линии'
        )
        ,'office' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Офисы'
        )
        ,'parking' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Парковка'
        )
        ,'securesystem' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Системы безопасности'
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
        ,'_title_row_1_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Выделенный БЦ'
        )
        ,'office_parking' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Паркинг'
        )
        ,'office_security' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Безопасность'
        )
        ,'office_sport' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Спорт'
        )
        ,'office_food' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Питание'
        )
        ,'office_access' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Доступ (24/7)'
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
        ,'dogovor' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'upload',
            'label' => 'Образец договора',
            'tip' => ''
        )            
        ,'dogovor_description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Договор, краткое описание',
            'tip' => ''
        )
        ,'how_pay' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Оплата',
            'tip' => ''
        )
        ,'internet' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Интернет',
            'tip' => ''
        )

        ,'notes' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Примечания',
            'tip' => 'Примечания / доп. информация'
        )
        ,'_title_row_2_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Расположение БЦ относительно сторон света'
        )
        ,'angle_from_north' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Угол направления относительно севера',
            'tip' => 'От -359 до 359 градусов'
        )
        ,'street_to_north' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Улица к северу'
        )
        ,'street_to_east' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Улица к востоку'
        )
        ,'street_to_south' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Улица к югу'
        )
        ,'street_to_west' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Улица к западу'
        )
        
    )
    ,'levels' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ) 
        ,'id_parent' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'business_center_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/estate/business_centers/bc_titles/',
            'input'=>'id_parent',
            'label' => 'Название БЦ'
        )
        ,'level' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Этаж'
        )     
        ,'id_corp' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Корпус'
        )
        ,'img_link' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'show_img' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null'=>true,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Показывать задний фон'
        )
    ),
    'corps' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ) 
        ,'id_parent' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'business_center_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/estate/business_centers/bc_titles/',
            'input'=>'id_parent',
            'label' => 'Название БЦ'
        )
        ,'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название корпуса'
        )
    )    
           
            
);
?>