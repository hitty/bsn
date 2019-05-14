<?php
return array(
    'agencies' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Полное название агентства'
        )
        ,'phone_1' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон',
            'tip' => ''
        )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Описание',
            'tip' => 'Полное описание акции'
        )
        ,'title_row_hours' => array(
            'fieldtype' => 'title_row',
            'tip' => 'График работы'
        )        

    )
    ,'campaigns' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'id_agency' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите компанию -'),
            'label' => 'Компания',
            'tip' => ''
        )
        ,'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Полное название'
        )
        ,'content_short' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Аннотация',
            'tip' => 'Короткое описание акции'
        )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Описание',
            'tip' => 'Полное описание акции'
        )
        ,'id_type_object' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(1=>'квартира'),
            'label' => 'Тип объекта'
        )
        ,'id_building_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип -'),
            'label' => 'Тип здания/дома'
        )
        ,'id_build_complete' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите срок -'),
            'label' => 'Срок сдачи'
        )
        ,'floors' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Этажность',
            'tip' => ''
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(1=>'активное', 3=>'на модерации', 2=>'в архиве'),
            'label' => 'Статус',
            'tip' => ''
        )

        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Адрес'
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
            'allow_null' => false,
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
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Номер дома',
            'tip' => 'Номер дома'
        )
        ,'corp' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 10,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Корпус',
            'tip' => 'Корпус'
        )
        ,'txt_addr' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Адрес',
            'tip' => 'Адрес (текстовый варант)'
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
        
        ,'title_row_1' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Условия акции'
        )
        ,'date_start' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата начала',
            'tip' => ''
        )
        ,'date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата окончания',
            'tip' => ''
        )          

        ,'id_offer_type' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(1=>'Акция',2=>'Скидка',3=>'Рассрочка'),
            'label' => 'Тип предложений',
            'tip' => ''
        )
        ,'old_cost' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Старая цена',
            'tip' => ''
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Новая цена',
            'tip' => ''
        )
        ,'discount' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => '%',
            'tip' => ''
        )
        ,'installment' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => '%',
            'tip' => ''
        )
        ,'action_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'maxlength' => 25,
            'fieldtype' => 'text',
            'label' => 'Описание акции',
            'tip' => ''
        )
        ,'title_row_2' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Геоположение'
        )
        ,'lat' => array(
            'type' => TYPE_FLOAT,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Широта'
         )
         ,'lng' => array(
            'type' => TYPE_FLOAT,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Долгота'
         )
        
    )          
    ,'offers' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'id_campaign' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите акцию -'),
            'label' => 'Акция',
            'tip' => ''
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(1=>'активное', 3=>'на модерации', 2=>'в архиве'),
            'label' => 'Статус объекта',
            'tip' => ''
        )
        ,'rooms_total' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Кол-во комнат (для студии = 0)',
            'tip' => ''
        )
        ,'square_live' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'values' => 0.00,
            'label' => 'Площадь жилая'
        )
        ,'square_kitchen' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'values' => 0.00,
            'label' => 'Площадь кухни'
        )
        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'values' => 0.00,
            'label' => 'Площадь общая'
        )
        ,'id_facing' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип отделка/ремонт -'),
            'label' => 'Отделка / Ремонт',
            'tip' => ''
        )
        ,'id_balcon' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите балкон -'),
            'label' => 'Балкон',
            'tip' => ''
        )
        ,'id_toilet' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип санузла -'),
            'label' => 'Санузел',
            'tip' => ''
        )
        
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Базовая цена',
            'tip' => ''
        )
        ,'cost_w_discount' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )
        ,'discount' => array(
            'type' => TYPE_FLOAT,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 99,
            'min' => 1,
            'inrow' => true,
            'class' => 'Скидка в %',
            'label' => 'Скидка в %',
            'tip' => ''
        )
        ,'discount_in_rubles' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Скидка в рублях',
            'tip' => ''
        )
         
         
    )
    ,'tarifs' => array(
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
                'tip' => 'Полное название'
            )        
            ,'connection_limit' => array(
                'type' => TYPE_INTEGER,
                'allow_empty' => true, 
                'allow_null' => true,
                'fieldtype' => 'text',
                'label' => 'Соединений'
            )        
            ,'connection_cost' => array(
                'type' => TYPE_INTEGER,
                'allow_empty' => true, 
                'allow_null' => true,
                'fieldtype' => 'text',
                'label' => 'Стоимость соединений',
                'tip' => ''
            )        
            ,'month_cost' => array(
                'type' => TYPE_INTEGER,
                'allow_empty' => true, 
                'allow_null' => true,
                'fieldtype' => 'text',
                'label' => 'Стоимость тарифа',
                'tip' => ''
            )        
            ,'prepay' => array(
                'type' => TYPE_INTEGER,
                'allow_empty' => true, 
                'allow_null' => true,
                'fieldtype' => 'text',
                'label' => 'Предоплата',
                'tip' => ''
            )        
            ,'over_limit_cost' => array(
                'type' => TYPE_INTEGER,
                'allow_empty' => true, 
                'allow_null' => true,
                'fieldtype' => 'text',
                'label' => 'Стоимость соединения сверх лимита',
                'tip' => ''
            ) 
    )                                                                                                           
);
?>