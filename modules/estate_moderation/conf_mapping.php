<?php
return array(
// квартиры и комнаты
    'live' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'rent' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'аренда',2=>'продажа'),
            'label' => 'Тип сделки',
            'tip' => 'Тип сделки'
        )
        ,'rent_duration' => array(
            'type' => TYPE_STRING,
            'max' => 20,
            'allow_empty' => true,
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'text',
            'label' => 'Срок аренды',
            'tip' => 'Продолжительность аренды (мес)'
        )
        ,'by_the_day' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'посуточно',2=>'помесячно'),
            'label' => 'Способ аренды',
            'tip' => 'Способ аренды'
        )
        ,'id_type_object' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип объекта -'),
            'label' => 'Тип объекта',
            'tip' => 'Тип объекта недвижимости'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'опубликован',2=>'в архиве',3=>'на модерации',4=>'черновик'),
            'label' => 'Состояние объявления',
            'tip' => 'Состояние/статус объекта'
        )
        ,'rooms_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Комнат в квартире',
            'tip' => 'Всего комнат в квартире'
        )
        ,'rooms_sale' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 99,
            'hidden' => true,
            'fieldtype' => 'text',
            'label' => 'Комнат в сделке',
            'tip' => 'Кол-во комнат на продажу/аренду'
        )
        ,'id_building_type' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид строения -'),
            'label' => 'Вид строения/здания',
            'tip' => 'Тип здания / вид строения'
        )
        
        
        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Адрес',
            'step'=>2
        )

        ,'geo_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_region' => array(
            'type' => TYPE_STRING,
            'allow_null' => false, 
            'allow_empty' => false, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Город / Населенный пункт',
            'class' => 'typewatch',
            'tip' => 'Начните вводить адрес объекта',
            'step'=>2
        )
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_district' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Район города',
            'class' => 'typewatch',
            'tip' => 'Начните вводить район',
            'step'=>2
        )        
        ,'geolocation' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'disabled'=>'disabled',
            'class'=>'geolocation',
            'label' => 'Район области',
            'tip' => '',
            'step'=>2
        )
        ,'txt_street' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Улица',
            'tip' => 'Улица',
            'class' => 'typewatch',
            'tip' => 'Начните вводить улицу',
            'step'=>2
        )

        
        ,'house' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Номер дома',
            'tip' => 'Укажите номер дома',
            'step'=>2
        )
        ,'corp' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 10,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Корпус',
            'tip' => 'Укажите корпус',
            'step'=>2
        )
        ,'id_region' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_area' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_city' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_place' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_addr' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Адрес',
            'tip' => 'Введите адрес. (текстовый варант в произвольной форме)',
            'step'=>2
        )        
        ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_subway' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'inrow' => true,
            'label' => 'Метро',
            'class' => 'typewatch',
            'tip' => 'Укажите ближайшую станцию метро',
            'step'=>2
        )
        
        ,'way_time' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'inrow' => true,
            'label' => 'Расстояние до метро',
            'tip' => 'Введите числовое значение',
            'step'=>2
        )
        ,'id_way_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'fieldtype' => 'select',
            'class' => 'id_way_type',
            'values' => array(0=>'- добраться от метро -'),
            'label' => '',
            'tip' => 'Выберите способ, соответствующий введенному числовому значению',
            'step'=>2
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
 
        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Характеристики обеъекта',
            'step'=>2
        )                        
        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Общая площадь',
            'tip' => 'Общая площадь'
        )
        ,'square_rooms' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 120,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Площадь по комнатам',
            'tip' => 'Площадь по комнатам'
        )
        ,'square_live' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Жилая площадь',
            'tip' => 'Жилая площадь'
        )
        ,'square_kitchen' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Площадь кухни',
            'tip' => 'Площадь кухни'
        )
        ,'level' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Этаж',
            'tip' => 'Этаж'
        )
        ,'level_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Этажей всего',
            'tip' => 'Этажей всего в здании'
        )
        ,'id_toilet' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип санузла -'),
            'label' => 'Санузел',
            'tip' => 'Тип санузла'
        )
        ,'id_balcon' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип балкона -'),
            'label' => 'Балкон',
            'tip' => 'Тип балкона'
        )
        ,'id_elevator' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид лифта -'),
            'label' => 'Лифт',
            'tip' => 'Тип лифта'
        )
        ,'id_enter' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид входа в дом -'),
            'label' => 'Вход',
            'tip' => 'Тип входа в дом'
        )
        ,'id_window' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид окон -'),
            'label' => 'Окна',
            'tip' => 'Тип окон'
        )
        ,'id_floor' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид пола -'),
            'label' => 'Пол',
            'tip' => 'Тип напольного покрытия'
        )
        ,'id_hot_water' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите наличие горячей воды -'),
            'label' => 'Горячая вода',
            'tip' => 'Тип горячего водоснабжения'
        )
        ,'id_facing' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите состояние -'),
            'label' => 'Техническое состояние',
            'tip' => 'Техническое состояние объекта / тип ремонта'
        )
        ,'wash_mash' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Стиральная машина',
            'tip' => 'Наличие стиральной машины'
        )
        ,'refrigerator' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Холодильник',
            'tip' => 'Наличие холодильника'
        )
        ,'furniture' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Мебель',
            'tip' => 'Наличие мебели'
        )
        ,'phone' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Телефон',
            'tip' => 'Наличие телефона'
        )
        ,'neighbors' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 99,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Соседи',
            'tip' => 'Кол-во соседей'
        )
        ,'ceiling_height' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Высота потолков',
            'tip' => 'Высота потолков в квартире'
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Стоимость',
            'tip' => 'Стоимость (в рублях)'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',3=>'расширенная строка',4=>'расширенная строка + топ',5=>'оплачен, обычный статус',6=>'VIP'),
            'label' => 'Доп.режим показов',
            'tip' => 'Дополнительный режим показов объявления'
        )
        ,'status_date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Окончание улуги',
            'tip' => 'Дата окончания примененной услуги'
        )        
        ,'elite' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Элитный объект',
            'tip' => 'Показывать объект в разделе - элитная недвижимость'
        )
        ,'elite_status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',4=>'оплачено'),
            'label' => 'Статус элитного объекта',
            'tip' => 'Дополнительный режим показов элитного объявления'
        )        
        ,'id_user' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ID пользователя',
            'tip' => 'ID пользователя, создавшего объект'
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
        ,'external_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Внешний ID',
            'tip' => 'Внешний ID'
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
    
    ),
// строящиеся объекты
    'build' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'опубликован',2=>'в архиве',3=>'на модерации',4=>'черновик'),
            'label' => 'Состояние объявления',
            'tip' => 'Состояние/статус объекта'
        )
        ,'rooms_sale' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Комнат в квартире',
            'tip' => 'Всего комнат в квартире'
        )
        ,'id_building_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид строения -'),
            'label' => 'Вид строения/здания',
            'tip' => 'Тип здания / вид строения'
        )
         
        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Адрес',
            'step'=>2
        )

        ,'geo_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_region' => array(
            'type' => TYPE_STRING,
            'allow_null' => false, 
            'allow_empty' => false, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Город / Населенный пункт',
            'class' => 'typewatch',
            'tip' => 'Начните вводить адрес объекта',
            'step'=>2
        )
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_district' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Район города',
            'class' => 'typewatch',
            'tip' => 'Начните вводить район',
            'step'=>2
        )        
        ,'geolocation' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'disabled'=>'disabled',
            'class'=>'geolocation',
            'label' => 'Район области',
            'tip' => '',
            'step'=>2
        )
        ,'txt_street' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Улица',
            'tip' => 'Улица',
            'class' => 'typewatch',
            'tip' => 'Начните вводить улицу',
            'step'=>2
        )

        
        ,'house' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Номер дома',
            'tip' => 'Укажите номер дома',
            'step'=>2
        )
        ,'corp' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 10,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Корпус',
            'tip' => 'Укажите корпус',
            'step'=>2
        )
        ,'id_region' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_area' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_city' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_place' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_addr' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Адрес',
            'tip' => 'Введите адрес. (текстовый варант в произвольной форме)',
            'step'=>2
        )        
        ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_subway' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'inrow' => true,
            'label' => 'Метро',
            'class' => 'typewatch',
            'tip' => 'Укажите ближайшую станцию метро',
            'step'=>2
        )
        
        ,'way_time' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'inrow' => true,
            'label' => 'Расстояние до метро',
            'tip' => 'Введите числовое значение',
            'step'=>2
        )
        ,'id_way_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'fieldtype' => 'select',
            'class' => 'id_way_type',
            'values' => array(0=>'- добраться от метро -'),
            'label' => '',
            'tip' => 'Выберите способ, соответствующий введенному числовому значению',
            'step'=>2
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
 
        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Характеристики обеъекта',
            'step'=>2
        )          
        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Общая площадь',
            'tip' => 'Общая площадь'
        )
        ,'square_rooms' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 120,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Площадь по комнатам',
            'tip' => 'Площадь по комнатам'
        )
        ,'square_live' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Жилая площадь',
            'tip' => 'Жилая площадь'
        )
        ,'square_kitchen' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Площадь кухни',
            'tip' => 'Площадь кухни'
        )
        ,'level' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Этаж',
            'tip' => 'Этаж'
        )
        ,'level_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Этажей всего',
            'tip' => 'Этажей всего в здании'
        )
        ,'id_housing_estate' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите ЖК -'),
            'label' => 'Прикрепить квартиру к ЖК'
        )      
        ,'id_build_complete' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите срок сдачи дома -'),
            'label' => 'Срок сдачи',
            'tip' => 'Срок сдачи дома'
        )
        ,'id_toilet' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип санузла -'),
            'label' => 'Санузел',
            'tip' => 'Тип санузла'
        )
        ,'id_balcon' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип балкона -'),
            'label' => 'Балкон',
            'tip' => 'Тип балкона'
        )
        ,'id_elevator' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид лифта -'),
            'label' => 'Лифт',
            'tip' => 'Тип лифта'
        )
        ,'id_window' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид окон -'),
            'label' => 'Окна',
            'tip' => 'Тип окон'
        )
        ,'id_facing' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид отделки -'),
            'label' => 'Отделка квариры',
            'tip' => 'Вид отделки квартиры'
        )
        ,'id_developer_status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите статус застройщика -'),
            'label' => 'Статус застройщика',
            'tip' => 'Статус компании застройщика'
        )
        ,'ceiling_height' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Высота потолков',
            'tip' => 'Высота потолков в квартире'
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => false, 
            'fieldtype' => 'text',
            'label' => 'Стоимость',
            'tip' => 'Стоимость (в рублях)'
        )
        ,'cost2meter' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Стоимость за кв.м.',
            'tip' => 'Стоимость за кв.м. (в рублях)'
        )
        ,'txt_cost' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Стоимость (текстом)',
            'tip' => 'Стоимость (текстом)'
        )
        ,'installment' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'values' => array(0=>'не установлено', 1=>'да', 2=>'нет'),
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'label' => 'Продажа в рассрочку',
            'tip' => 'Возможность продажи в рассрочку'
        )
        ,'installment_months' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Срок рассрочки (мес)',
            'tip' => 'Срок рассрочки (мес)'
        )
        ,'first_payment' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Первый взнос',
            'tip' => 'Первый взнос (при покупке в рассрочку)'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',3=>'расширенная строка',4=>'расширенная строка + топ',5=>'оплачен, обычный статус',6=>'VIP'),
            'label' => 'Доп.режим показов',
            'tip' => 'Дополнительный режим показов объявления'
        )
        ,'status_date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Окончание улуги',
            'tip' => 'Дата окончания примененной услуги'
        )        
        ,'elite' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Элитный объект',
            'tip' => 'Показывать объект в разделе - элитная недвижимость'
        )
        ,'elite_status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',4=>'оплачено'),
            'label' => 'Статус элитного объекта',
            'tip' => 'Дополнительный режим показов элитного объявления'
        )            
        ,'id_user' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ID пользователя',
            'tip' => 'ID пользователя, создавшего объект'
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
        ,'external_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Внешний ID',
            'tip' => 'Внешний ID'
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
    ),
// Коммерческие объекты
    'commercial' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'rent' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'аренда',2=>'продажа'),
            'label' => 'Тип сделки',
            'tip' => 'Тип сделки'
        )
        ,'rent_duration' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Срок аренды',
            'tip' => 'Срок аренды / до какого срока сдается'
        )
        ,'id_type_object' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип объекта -'),
            'label' => 'Тип объекта',
            'tip' => 'Тип объекта недвижимости'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'опубликован',2=>'в архиве',3=>'на модерации',4=>'черновик'),
            'label' => 'Состояние объявления',
            'tip' => 'Состояние/статус объекта'
        )
         
        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Адрес',
            'step'=>2
        )

        ,'geo_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_region' => array(
            'type' => TYPE_STRING,
            'allow_null' => false, 
            'allow_empty' => false, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Город / Населенный пункт',
            'class' => 'typewatch',
            'tip' => 'Начните вводить адрес объекта',
            'step'=>2
        )
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_district' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Район города',
            'class' => 'typewatch',
            'tip' => 'Начните вводить район',
            'step'=>2
        )        
        ,'geolocation' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'disabled'=>'disabled',
            'class'=>'geolocation',
            'label' => 'Район области',
            'tip' => '',
            'step'=>2
        )
        ,'txt_street' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Улица',
            'tip' => 'Улица',
            'class' => 'typewatch',
            'tip' => 'Начните вводить улицу',
            'step'=>2
        )

        
        ,'house' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Номер дома',
            'tip' => 'Укажите номер дома',
            'step'=>2
        )
        ,'corp' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 10,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Корпус',
            'tip' => 'Укажите корпус',
            'step'=>2
        )
        ,'id_region' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_area' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_city' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_place' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_addr' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Адрес',
            'tip' => 'Введите адрес. (текстовый варант в произвольной форме)',
            'step'=>2
        )        
        ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_subway' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'inrow' => true,
            'label' => 'Метро',
            'class' => 'typewatch',
            'tip' => 'Укажите ближайшую станцию метро',
            'step'=>2
        )
        
        ,'way_time' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'inrow' => true,
            'label' => 'Расстояние до метро',
            'tip' => 'Введите числовое значение',
            'step'=>2
        )
        ,'id_way_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'fieldtype' => 'select',
            'class' => 'id_way_type',
            'values' => array(0=>'- добраться от метро -'),
            'label' => '',
            'tip' => 'Выберите способ, соответствующий введенному числовому значению',
            'step'=>2
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
 
        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Характеристики обеъекта',
            'step'=>2
        )          
        ,'id_business_center' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите БЦ -'),
            'label' => 'Прикрепить к бизнес-центру'
        )          
        ,'txt_level' => array(
            'type' => TYPE_STRING,
            'max' => 20,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Этаж',
            'tip' => 'Этаж (текстовое поле)'
        )
        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Общая площадь',
            'tip' => 'Общая площадь'
        )
        ,'square_usefull' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Полезная площадь',
            'tip' => 'Полезная площадь'
        )
        ,'square_ground' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Площадь земельного участка, сот',
            'tip' => 'Площадь земельного участка, сот'
        )
        ,'phones_count' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'allow_null' => true,
            'label' => 'Кол-во тел. линий',
            'tip' => 'Количество телефонных линий (номеров телефона)'
        )
        ,'ceiling_height' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Высота потолков',
            'tip' => 'Высота потолков в помещении'
        )
        ,'parking' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Паркинг',
            'tip' => 'Паркинг'
        )
        ,'security' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Охрана',
            'tip' => 'Охрана'
        )
        ,'service_line' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Коммуникации',
            'tip' => 'Коммуникации'
        )
        ,'canalization' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Канализация',
            'tip' => 'Канализация'
        )
        ,'hot_water' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Горячая вода',
            'tip' => 'Горячая вода'
        )
        ,'electricity' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Электричество',
            'tip' => 'Электричество'
        )
        ,'heating' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Есть',2=>'Нет'),
            'label' => 'Отопление',
            'tip' => 'Отопление'
        )
        ,'transport_entrance' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Транспортные подъезды',
            'tip' => 'Транспортная доступность / виды транспортных подъездов (д/д ветки, дороги и т.п.)'
        )
        ,'id_facing' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип ремонта -'),
            'label' => 'Тип ремонта',
            'tip' => 'Техническое состояние объекта / тип ремонта'
        )
        ,'id_enter' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид входа -'),
            'label' => 'Вход',
            'tip' => 'Тип входа'
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Стоимость',
            'tip' => 'Стоимость (в рублях)'
        )
        ,'cost2meter' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Стоимость за кв.м.',
            'tip' => 'Стоимость за кв.м. (в рублях)'
        )
        ,'txt_cost' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Стоимость (текстом)',
            'tip' => 'Стоимость (текстом)'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',3=>'расширенная строка',4=>'расширенная строка + топ',5=>'оплачен, обычный статус',6=>'VIP'),
            'label' => 'Доп.режим показов',
            'tip' => 'Дополнительный режим показов объявления'
        )
        ,'status_date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Окончание улуги',
            'tip' => 'Дата окончания примененной услуги'
        )        
        ,'elite' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Элитный объект',
            'tip' => 'Показывать объект в разделе - элитная недвижимость'
        )
        ,'elite_status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',4=>'оплачено'),
            'label' => 'Статус элитного объекта',
            'tip' => 'Дополнительный режим показов элитного объявления'
        )            
        ,'id_user' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ID пользователя',
            'tip' => 'ID пользователя, создавшего объект'
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
        ,'external_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Внешний ID',
            'tip' => 'Внешний ID'
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
    ),
// дома и участки
    'country' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'rent' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'аренда',2=>'продажа'),
            'label' => 'Тип сделки',
            'tip' => 'Тип сделки'
        )
        ,'id_type_object' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип объекта -'),
            'label' => 'Тип объекта',
            'tip' => 'Тип объекта недвижимости'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'опубликован',2=>'в архиве',3=>'на модерации',4=>'черновик'),
            'label' => 'Состояние объявления',
            'tip' => 'Состояние/статус объекта'
        )
        ,'rooms' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 99,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Кол-во комнат',
            'tip' => 'Кол-во комнат'
        )
         
        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Адрес',
            'step'=>2
        )

        ,'geo_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_region' => array(
            'type' => TYPE_STRING,
            'allow_null' => false, 
            'allow_empty' => false, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Город / Населенный пункт',
            'class' => 'typewatch',
            'tip' => 'Начните вводить адрес объекта',
            'step'=>2
        )
        ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_district' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Район города',
            'class' => 'typewatch',
            'tip' => 'Начните вводить район',
            'step'=>2
        )        
        ,'geolocation' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'disabled'=>'disabled',
            'class'=>'geolocation',
            'label' => 'Район области',
            'tip' => '',
            'step'=>2
        )
        ,'txt_street' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Улица',
            'tip' => 'Улица',
            'class' => 'typewatch',
            'tip' => 'Начните вводить улицу',
            'step'=>2
        )

        
        ,'house' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Номер дома',
            'tip' => 'Укажите номер дома',
            'step'=>2
        )
        ,'corp' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 10,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Корпус',
            'tip' => 'Укажите корпус',
            'step'=>2
        )
        ,'id_region' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_area' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_city' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_place' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_addr' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Адрес',
            'tip' => 'Введите адрес. (текстовый варант в произвольной форме)',
            'step'=>2
        )        
        ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_subway' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'inrow' => true,
            'label' => 'Метро',
            'class' => 'typewatch',
            'tip' => 'Укажите ближайшую станцию метро',
            'step'=>2
        )
        
        ,'way_time' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'inrow' => true,
            'label' => 'Расстояние до метро',
            'tip' => 'Введите числовое значение',
            'step'=>2
        )
        ,'id_way_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'fieldtype' => 'select',
            'class' => 'id_way_type',
            'values' => array(0=>'- добраться от метро -'),
            'label' => '',
            'tip' => 'Выберите способ, соответствующий введенному числовому значению',
            'step'=>2
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
 
        ,'title_row_address' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Характеристики обеъекта',
            'step'=>2
        )          
        ,'id_cottage' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите КП -'),
            'label' => 'Прикрепить к коттеджному поселку'
        )          
        ,'railstation' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Ж/д станция',
            'tip' => 'Ближайшая ж/д станция'
        )
        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Общая площадь',
            'tip' => 'Общая площадь'
        )
        ,'square_live' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Жилая площадь',
            'tip' => 'Жилая площадь'
        )
        ,'square_ground' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Площадь участка',
            'tip' => 'Площадь участка'
        )
        ,'year_build' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Год постройки',
            'tip' => 'Год постройки здания'
        )
        ,'level_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Этажей всего',
            'tip' => 'Этажей всего в здании'
        )
        ,'id_ownership' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид собственности -'),
            'label' => 'Вид собственности',
            'tip' => 'Вид права собственности'
        )
        ,'id_roof_material' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите материал крыши -'),
            'label' => 'Материал крыши',
            'tip' => 'Материал крыши'
        )
        ,'id_construct_material' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите материал стен -'),
            'label' => 'Материал стен',
            'tip' => 'Материал стен'
        )
        ,'id_electricity' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип электроснабжения -'),
            'label' => 'Электричество',
            'tip' => 'Тип электроснабжения'
        )
        ,'id_river' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип водоема -'),
            'label' => 'Водоем',
            'tip' => 'Тип водоема'
        )
        ,'id_water_supply' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип водоснабжения -'),
            'label' => 'Водоснабжение',
            'tip' => 'Тип водоснабжения'
        )
        ,'id_heating' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип отопления -'),
            'label' => 'Отопление',
            'tip' => 'Тип отопления'
        )
        ,'id_toilet' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип санузла -'),
            'label' => 'Санузел',
            'tip' => 'Тип санузла'
        )
        ,'id_gas' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип газоснабжения -'),
            'label' => 'Газ',
            'tip' => 'Тип газоснабжения'
        )
        ,'id_garden' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип газоснабжения -'),
            'label' => 'Сад/огород',
            'tip' => 'Тип сада/огорода'
        )
        ,'id_bathroom' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип бани -'),
            'label' => 'Баня',
            'tip' => 'Тип бани'
        )
        ,'id_building_progress' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите степень готовности постройки -'),
            'label' => 'Готовность постройки',
            'tip' => 'Степень готовности постройки'
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Стоимость',
            'tip' => 'Стоимость (в рублях)'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',3=>'расширенная строка',4=>'расширенная строка + топ',5=>'оплачен, обычный статус',6=>'VIP'),
            'label' => 'Доп.режим показов',
            'tip' => 'Дополнительный режим показов объявления'
        )
        ,'status_date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Окончание улуги',
            'tip' => 'Дата окончания примененной услуги'
        )        
        ,'elite' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Элитный объект',
            'tip' => 'Показывать объект в разделе - элитная недвижимость'
        )
        ,'elite_status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(2=>'нет',4=>'оплачено'),
            'label' => 'Статус элитного объекта',
            'tip' => 'Дополнительный режим показов элитного объявления'
        )            
        ,'id_user' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'ID пользователя',
            'tip' => 'ID пользователя, создавшего объект'
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
        ,'external_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Внешний ID',
            'tip' => 'Внешний ID'
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