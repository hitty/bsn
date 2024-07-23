<?php
return array(
// жилые комплексы
    'housing_estates' => array(
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
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
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
            'type' => TYPE_INTEGER,
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
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
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
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Адрес',
            'tip' => 'Адрес (текстовый варант)'
        )
        ,'phases' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Очереди',
            'tip' => ''
        )
        ,'korpuses' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Корпуса',
            'tip' => ''
        )
        ,'id_building_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Типы домов',
            'tip' => 'Типы домов/корпусов'
        )
        
        ,'building_type' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Типы домов (старое поле)',
            'tip' => 'Типы домов/корпусов'
        )
        ,'build_complete' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Сроки сдачи очередей',
            'tip' => ''
        )
        ,'yandex_house_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'fieldtype' => 'text',
            'label' => 'ID очереди в базе яндекса',
            'tip' => 'Сначала ищется по очереди, потом по ЖК'
        )
        ,'yandex_building_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'fieldtype' => 'text',
            'label' => 'ID ЖК в базе яндекса',
            'tip' => 'ID ЖК в базе яндекса'
        )
        ,'214_fz' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => '214 ФЗ',
            'tip' => 'Соответствует 214 ФЗ'
        )        
        ,'apartments' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Апартаменты'
        )        
        ,'declaration' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Проектная декларация',
            'tip' => ''
        )
        
        ,'levels' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Этажность',
            'tip' => 'Этажность'
        )
        ,'low_rise' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Малоэтажный',
            'tip' => 'Тип ЖК - малоэтажный'
        )
        ,'elite_building' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Элитная застройка',
            'tip' => 'Элитный ЖК/элитные квартиры'
        )
        ,'playground' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Детская площадка',
            'tip' => ''
        )        
        ,'parking' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Паркинг',
            'tip' => ''
        )        
        ,'security' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Охрана (видео, консьерж)',
            'tip' => ''
        )        
        ,'lifts' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Количество лифтов',
            'tip' => ''
        )
        ,'service_lifts' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Из них(лифтов) грузовых',
            'tip' => ''
        )                
        ,'forum' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Форум дольщиков',
            'tip' => ''
        )        
        ,'site' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Сайт',
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
        ,'seller_name' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Контактное лицо',
            'tip' => 'Контактное лицо'
        )
        ,'seller_phone' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'phone',
            'label' => 'Телефон контактного лица',
            'tip' => 'Телефон контактного лица'
        )
        ,'notes_default' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Примечания по умолчанию',
            'tip' => 'Примечания для бесплатной карточки'
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
    )  
    
);
?>