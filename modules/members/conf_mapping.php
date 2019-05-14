<?php
return array(
    'users' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'message_notification' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'hidden'
         )
         ,'application_notification' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'hidden'
         )
         ,'foreign_application_notification' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'hidden'
         )
         ,'consults_notification' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'hidden'
         )
         ,'xml_notification' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'hidden'
         )
         ,'login' => array(
            'type' => TYPE_STRING,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )         
        ,'_title_block_main_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Личные данные'
        )        
        ,'email' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'email',
            'label' => 'E-mail',
        )
        ,'show_email' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Показывать в профиле'
        )
        ,'email_service' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'email',
            'hidden' => true,
            'label' => 'E-mail',
        )
        ,'name' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Имя',
        )
        ,'lastname' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Фамилия',
        )
        ,'phone' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'fieldtype' => 'phone',
            'label' => 'Телефон',
        )
        ,'show_phone' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Показывать в профиле'
        )
        ,'sex' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите пол -',    '1' => 'мужской',   '2'=>'женский'),
            'label' => 'Пол'
        )    
        
        ,'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'О себе',
            'tip' => 'О себе'
        )       
        
       
        ,'career' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'class' => 'career',
            'title' => 'Род деятельности',
            'fieldtype' => 'checkbox_set',
            'values' => Config::Get('users_carrer')
        )     

        ,'specializations' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'class' => 'specializations',
            'title' => 'Специализация',
            'fieldtype' => 'checkbox_set',
            'values' => Config::Get('users_specializations') 
        )
        
        ,'newsletters' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'class' => 'newsletters',
            'title' => 'Подписки',
            'fieldtype' => 'checkbox_set',
            'values' => Config::Get('users_newsletters')
        )
        
        ,'subscribe_news' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Новости БСН'
        )        

        ,'_title_block_notifications_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Уведомления'
        )        
                
        ,'notifications_list' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox_list',
            'title' => 'Будь в курсе',
            'values' => array(
                'message_notification' => 'Новое сообщение'
                , 'application_notification' => 'Новая заявка'
                , 'foreign_application_notification' => 'Новая заявка в общем пуле'
                , 'consults_notification' => 'Уведомления сервиса Консультант'
                , 'xml_notification' => 'Отчеты загрузки фида XML'
            )
        )                
     
        /*
        ,'_title_block_subscribe_social_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'class' => 'social',
            'tip' => 'Социальные сети'
        )        
        ,'id_user_vk' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'social_text',
            'link' => 'https://oauth.vk.com/authorize?client_id='.Config::Get('social/vk/app_id').'&amp;redirect_uri='.Host::$root_url.'%2Fauthorization%2Fvklogin%2F?r=personalinfo&amp;response_type=code',
            'title' => 'Вконтакте',
            'label' => '',
        )
        ,'id_user_facebook' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'social_text',     
            'link' => 'https://www.facebook.com/dialog/oauth?client_id='.Config::Get('social/fb/app_id').'&amp;redirect_uri='.Host::$root_url.'%2Fauthorization%2Ffblogin%2F',
            'title' => 'Facebook',
            'label' => '',
        )
        ,'id_user_ok' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'social_text',
            'link' => 'http://www.odnoklassniki.ru/oauth/authorize?client_id='.Config::Get('social/ok/app_id').'&amp;redirect_uri='.Host::$root_url.'%2Fauthorization%2Foklogin%2F?r=personalinfo&amp;response_type=code',
            'title' => 'Одноклассники',
            'label' => '',
        )
        */
        ,'_title_block_1_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Сменить пароль'
        )
        ,'old_passwd' => array(
            'type' => TYPE_STRING,
            'max' => 255, 
            'allow_empty' => true, 
            'fieldtype' => 'password',
            'label' => 'Текущий пароль',
        )
        ,'new_passwd' => array(
            'type' => TYPE_STRING,
            'max' => 255, 
            'allow_empty' => true, 
            'fieldtype' => 'password',
            'label' => 'Новый пароль',
        )
    ),
    'groups' => array(
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
            'tip' => 'Название группы'
        )
        ,'access' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Права доступа',
            'tip' => 'Особые права доступа для группы'
        )
    ),
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
        ,'addr' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Текстовый адрес',
            'tip' => 'Город, улица, дом и т.д.'
        )
        ,'phone_1' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон 1',
            'tip' => 'Контактный телефон'
        )        
        ,'phone_2' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон 2',
            'tip' => 'Дополнительный контактный телефон'
        )        
        ,'phone_3' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон 3',
            'tip' => 'Дополнительный контактный телефон'
        )
        ,'fax' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Факс',
            'tip' => ''
        )    
        ,'email' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Контактный E-mail',
            'tip' => ''
        )    
        ,'url' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Web-сайт',
            'tip' => 'Формат: www.site.ru'
        )    
        ,'skype' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Skype',
            'tip' => 'Skype менеджера агентства'
        )            
        ,'license_number' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Номер лицензии',
            'tip' => ''
        )    
        ,'license_date' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата выдачи лицензии',
            'tip' => 'Формат: ГГГГ-ММ-ДД'
        )    
        ,'notes' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            
            'label' => 'Описание',
            'tip' => ''
        )    
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(1=>'вкл',2=>'выкл'),
            'label' => 'Статус',
            'tip' => 'Показывать на сайте в списке агентств'
        )                                                                
        ,'id_manager' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите менеджера -'),
            'label' => 'Менеджер',
            'tip' => 'Менеджер БСНа отвечающий за данное агентство'
        )    
        ,'_title_block_1_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Вид деятельности'
        )
        ,'activity' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'set',
            'values' => array(1=>'Агентства',2=>'Застройщик',3=>'УК',4=>'Банки',5=>'Девелоперы',6=>'Инвестиции',7=>'Другой профиль'),
            'label' => '',
            'tip' => ''
        )            
        ,'_title_block_2_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Вывод в реестре агентств недвижимости'
        )
        ,'estate_types' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'set',
            'values' => array(1=>'Жилая',2=>'Новостройки',3=>'Коммерческая',4=>'Загородная',5=>'Элитная',6=>'Зарубежная',7=>'Коттеджи',8=>'БЦ',9=>'Ипотека',10=>'Страхование'),
            'label' => '',
            'tip' => ''
        )                                                                                
    ),
// квартиры и комнаты
    'live' => array(
        '_title_block_0_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Местоположение'
        )    
        ,'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'text'
         )
         ,'weight' => array(
            'type' => TYPE_INTEGER,
            'hidden' => true,
            'fieldtype' => 'text'
         )
         ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'hidden' => true,
            'fieldtype' => 'text'
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

        ,'_title_row_address_' => array(
            'type' => TYPE_STRING,
            'class' => 'squares',
            'fieldtype' => 'title_row',
            'tip' => 'Адрес объекта'
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
            'label' => 'Район области',
            'tip' => '',
            'step'=>2
        )
        ,'txt_subway' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Метро',
            'tip' => 'Метро',
            'class' => 'typewatch',
            'tip' => 'Начните вводить метро',
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
            'label' => '№ дома',
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
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_region' => array(
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
            'label' => 'Текстовый адрес',
            'tip' => 'Введите адрес. (текстовый вариант в произвольной форме)',
            'step'=>2
        )      
                
        ,'div_' => array(
            'fieldtype' => 'div',
            'class' => 'clearfix'
        )
        ,'map' => array(
            'fieldtype' => 'map'
        )
       
        ,'_title_block_build_params_' => array( 
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Параметры дома'
        )        

                
        ,'id_building_type' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'select',
            'allow_empty' => true, 
            'values' => array(0=>'- выберите -'),
            'label' => 'Тип дома'
        )
        ,'housing_estate' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'url'   =>  '/zhiloy_kompleks/title/', 
            'input'=>'id_housing_estate',

            'label' => 'Прикрепить квартиру к ЖК',
            'tip' => 'Прикрепить квартиру к ЖК',
            'class' => 'typewatch',
            'tip' => 'Начните вводить название ЖК'
        )
        ,'id_housing_estate' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )        
        
       
        ,'_title_block_flat_params_' => array( 
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Параметры квартиры'
        )
        ,'id_type_object' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Тип объекта'
        )
        ,'studio' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Студия'
        )
        ,'rooms_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 7,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(0=>'Студия',1=>'1',2=>'2',3=>'3',4=>'4',5=>'5',6=>'6',7=>'7+'),
            'label' => 'Комнат'
        )
                
         ,'rooms_sale' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 7,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(1=>'1',2=>'2',3=>'3',4=>'4',5=>'5',6=>'6',7=>'7+'),
            'label' => 'Комнат на продажу'
        )                                                                        
        
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'digit',
            'label' => 'Стоимость, руб.'
        )
        ,'cost2meter' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Стоимость за кв.м.',
            'class' => 'digit',
            'placeholder' => 'руб/кв.м'
        )
        ,'is_apartments' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Апартаменты'
        )
        ,'is_penthouse' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Пентхаус'
        )                
        ,'rent_duration' => array(
            'type' => TYPE_STRING,
            'max' => 20,
            'allow_empty' => true,
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'text',
            'label' => 'Срок аренды'
        )
        ,'by_the_day' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(1=>'посуточно',2=>'помесячно'),
            'label' => 'Способ аренды'
        )        
        ,'_title_row_squares_' => array(
            'type' => TYPE_STRING,
            'class' => 'squares',
            'fieldtype' => 'title_row',
            'tip' => 'Площадь'
        )
        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => false,
            'fieldtype' => 'text',
            'label' => 'Общая'
        )
        ,'square_live' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'value' => 0,
            'allow_empty' => false,
            'fieldtype' => 'text',
            'label' => 'Жилая'
        )
        ,'square_kitchen' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true,
            'fieldtype' => 'text',
            'label' => 'Кухня'
        )
        ,'square_rooms' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 120,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Комнат',
            'placeholder' => 'пример: 14+5'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 8,
            'allow_empty' => true,
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(4=>'да',2=>'нет'),
            'label' => '+ статус премиум'
        )       
        ,'_title_row_levels_' => array(
            'type' => TYPE_STRING,
            'class' => 'levels',
            'fieldtype' => 'title_row',
            'tip' => 'Этаж'
        )           
        ,'level' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => ''
        )
        ,'level_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => ''
        )
        ,'ceiling_height' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Высота потолков'
        )     
        ,'neighbors' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Соседи (количество)'
        )        
        ,'id_toilet' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Санузел'
        )
        ,'id_balcon' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Балкон'
        )
        ,'id_elevator' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Лифт'
        )
        ,'id_enter' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Вход'
        )
        ,'id_window' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Окна'
        )
        ,'id_floor' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Пол'
        )        
        ,'id_hot_water' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Горячая вода'
        )        
        ,'id_facing' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Ремонт'
        )           
        ,'refrigerator' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Холодильник'
        )
        ,'furniture' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Мебель'
        )
        ,'phone' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Телефон'
        )
        ,'seller_name' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => false, 
            'allow_null' => false,
            'nodisplay' => true,
            'fieldtype' => 'text'
        )
        ,'seller_phone' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => false, 
            'allow_null' => false,
            'nodisplay' => true,
            'fieldtype' => 'phone'
        )
        ,'_title_block_notes_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Описание'
        )  
        ,'notes' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            
            'label' => ''
        )
        ,'id_user_type' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'select'
        )
        ,'id_work_status' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'radio'
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
        ,'id_main_video' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )
    ),
// строящиеся объекты
    'build' => array(
        '_title_block_0_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Местоположение'
        )    
        ,'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'text'
         )
         ,'weight' => array(
            'type' => TYPE_INTEGER,
            'hidden' => true,
            'fieldtype' => 'text'
         )
         ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'hidden' => true,
            'fieldtype' => 'text'
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
        ,'_title_row_address_' => array(
            'type' => TYPE_STRING,
            'class' => 'squares',
            'fieldtype' => 'title_row',
            'tip' => 'Адрес объекта'
        )

        ,'txt_region' => array(
            'type' => TYPE_STRING,
            'allow_null' => false, 
            'allow_empty' => false, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Город / Населенный пункт',
            'class' => 'typewatch ',
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
            
            'label' => 'Район области',
            'tip' => '',
            'step'=>2
        )
        ,'txt_subway' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Метро',
            'tip' => 'Метро',
            'class' => 'typewatch',
            'tip' => 'Начните вводить метро',
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
            'label' => '№ дома',
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
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_region' => array(
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
            'label' => 'Текстовый адрес',
            'tip' => 'Введите адрес. (текстовый вариант в произвольной форме)',
            'step'=>2
        )              
        ,'div_' => array(
            'fieldtype' => 'div',
            'class' => 'clearfix'
        )

        ,'map' => array(
            'fieldtype' => 'map'
        )
       
       
        ,'_title_block_build_params_' => array( 
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Параметры дома'
        )        

                
        ,'id_building_type' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'select',
            'allow_empty' => true, 
            'values' => array(0=>'- выберите -'),
            'label' => 'Тип дома'
        )
        ,'housing_estate' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'url'   =>  '/zhiloy_kompleks/title/', 
            'input'=>'id_housing_estate',

            'label' => 'Прикрепить квартиру к ЖК',
            'tip' => 'Прикрепить квартиру к ЖК',
            'class' => 'typewatch',
            'tip' => 'Начните вводить название ЖК'
        )
        ,'id_housing_estate' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )        
        
        ,'id_developer_status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Статус застройщика'
        )
        ,'id_build_complete' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Срок сдачи'
        )
        
        ,'_title_block_flat_params_' => array( 
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Параметры квартиры'
        )
        ,'studio' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Студия'
        )
        ,'rooms_sale' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'class' => 'digit',
            'fieldtype' => 'text',
            'label' => 'Комнат в сделке'
        )
        ,'rooms_sale' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 7,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(0=>'Студия',1=>'1',2=>'2',3=>'3',4=>'4',5=>'5',6=>'6',7=>'7+'),
            'label' => 'Комнат в квартире'
        )                                                                        
        ,'rooms_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 99,
            'hidden' => true,  
            'class' => 'digit',   
            'fieldtype' => 'text',
            'label' => 'Комнат всего'
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'digit',
            'label' => 'Стоимость, руб.'
        )
        ,'cost2meter' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Стоимость за кв.м.',
            'class' => 'digit',
            'placeholder' => 'руб/кв.м'
        )
        ,'is_apartments' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Апартаменты'
        )
        ,'is_penthouse' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Пентхаус'
        )                
        
        ,'_title_row_squares_' => array(
            'type' => TYPE_STRING,
            'class' => 'squares',
            'fieldtype' => 'title_row',
            'tip' => 'Площадь'
        )
        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => false,
            'fieldtype' => 'text',
            'label' => 'Общая'
        )
        ,'square_live' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'value' => 0,
            'allow_empty' => false,
            'fieldtype' => 'text',
            'label' => 'Жилая'
        )
        ,'square_kitchen' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true,
            'fieldtype' => 'text',
            'label' => 'Кухня'
        )
        ,'square_rooms' => array(
            'type' => TYPE_STRING,
            'min' => 0,
            'max' => 120,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Комнат',
            'placeholder' => 'пример: 14+5'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 8,
            'allow_empty' => true,
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(4=>'да',2=>'нет'),
            'label' => '+ статус премиум'
        )       
        ,'_title_row_levels_' => array(
            'type' => TYPE_STRING,
            'class' => 'levels',
            'fieldtype' => 'title_row',
            'tip' => 'Этаж'
        )           
        ,'level' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => ''
        )
        ,'level_total' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => ''
        )
        ,'ceiling_height' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Высота потолков'
        )     
        
        ,'id_toilet' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Санузел'
        )
        ,'id_balcon' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Балкон'
        )
        ,'id_elevator' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Лифт'
        )
        ,'id_enter' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Вход'
        )
        ,'id_window' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Окна'
        )
        ,'id_facing' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Ремонт'
        )           
        ,'installment' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Да',2=>'Нет'),
            'label' => 'Продажа в рассрочку'
        )
        ,'first_payment' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Первый взнос, %'
        )
        ,'installment_months' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Рассрочка до, мес.'
        )
        ,'installment_years' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Рассрочка до, год'
        )
        ,'seller_name' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => false, 
            'allow_null' => false,
            'nodisplay' => true,
            'fieldtype' => 'text'
        )
        ,'seller_phone' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => false, 
            'allow_null' => false,
            'nodisplay' => true,
            'fieldtype' => 'phone'
        )
        ,'_title_block_notes_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Описание'
        )  
        ,'notes' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            
            'label' => ''
        )
         ,'id_user_type' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'select'
        )
        ,'id_work_status' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'radio'
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
        ,'id_main_video' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )        
        
    ),
//коммерческие объекты
    'commercial' => array(
        '_title_block_0_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Местоположение'
        )    
        ,'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'text'
         )
         ,'weight' => array(
            'type' => TYPE_INTEGER,
            'hidden' => true,
            'fieldtype' => 'text'
         )
         ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'hidden' => true,
            'fieldtype' => 'text'
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
        ,'_title_row_address_' => array(
            'type' => TYPE_STRING,
            'class' => 'squares',
            'fieldtype' => 'title_row',
            'tip' => 'Адрес объекта'
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
            
            'label' => 'Район области',
            'tip' => '',
            'step'=>2
        )
        ,'txt_subway' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Метро',
            'tip' => 'Метро',
            'class' => 'typewatch',
            'tip' => 'Начните вводить метро',
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
            'label' => '№ дома',
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
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_region' => array(
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
            'label' => 'Текстовый адрес',
            'tip' => 'Введите адрес. (текстовый вариант в произвольной форме)',
            'step'=>2
        )             
        ,'div_' => array(
            'fieldtype' => 'div',
            'class' => 'clearfix'
        )
        ,'map' => array(
            'fieldtype' => 'map'
        )
       
        ,'_title_block_build_params_' => array( 
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Параметры объекта'
        )                 

        ,'id_type_object' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Тип объекта'
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'digit',
            'label' => 'Стоимость, руб.'
        )
        ,'cost2meter' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'class' => 'digit',
            'label' => 'Стоимость за кв.м.'
        )
        ,'rent_duration' => array(
            'type' => TYPE_STRING,
            'max' => 20,
            'allow_empty' => true,
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'text',
            'label' => 'Срок аренды'
        )
        
        ,'_title_row_squares_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_row',
            'tip' => 'Площади'
        )

        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => false,
            'fieldtype' => 'text',
            'label' => 'Общая'
        )
        ,'square_usefull' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => false,
            'fieldtype' => 'text',
            'label' => 'Полезная'
        )
        ,'square_ground' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Участок, сот',
            'tip' => 'Площадь земельного участка, сот'
        )
        
        ,'txt_level' => array(
            'type' => TYPE_STRING,
            'max' => 20,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'label' => 'Этаж / этажность'
        )
        ,'phones_count' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Кол-во телефонных линий'
        )
        ,'ceiling_height' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Высота потолков'
        )
        
        ,'id_facing' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тип ремонта -'),
            'label' => 'Тип ремонта'
        )                
        ,'_title_block_3_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Общие характеристики'
        )
        ,'transport_entrance' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Транспортные подъезды'
        )
        
        ,'parking' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Паркинг'
        )
        ,'security' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Охрана'
        )
        ,'service_line' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Коммуникации'
        )
        ,'canalization' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Канализация'
        )
        ,'hot_water' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Горячая вода'
        )
        ,'electricity' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Электричество'
        )
        ,'heating' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Отопление'
        )         
                  
        
        
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 8,
            'allow_empty' => true,
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(4=>'да',2=>'нет'),
            'label' => '+ статус премиум'
        )  
       
        
        ,'id_enter' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите вид входа -'),
            'label' => 'Вход'
        )
        ,'seller_name' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => false, 
            'allow_null' => false,
            'nodisplay' => true,
            'fieldtype' => 'text',
            'label' => 'Контактное лицо'
        )
        ,'seller_phone' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => false, 
            'allow_null' => false,
            'nodisplay' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон контактного лица'
        )
        ,'_title_block_notes_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Описание'
        )  
        ,'notes' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            
            'label' => ''
        )
         ,'id_user_type' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'select'
        )
        ,'id_work_status' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => false,
            'allow_empty' => false, 
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'radio'
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
       
    ),    
    // дома и участки
    'country' => array(
        '_title_block_0_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Местоположение'
        )    
        ,'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'text'
         )
         ,'weight' => array(
            'type' => TYPE_INTEGER,
            'hidden' => true,
            'fieldtype' => 'text'
         )
         ,'id_subway' => array(
            'type' => TYPE_INTEGER,
            'hidden' => true,
            'fieldtype' => 'text'
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
        ,'_title_row_address_' => array(
            'type' => TYPE_STRING,
            'class' => 'squares',
            'fieldtype' => 'title_row',
            'tip' => 'Адрес объекта'
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
            
            'label' => 'Район области',
            'tip' => '',
            'step'=>2
        )
        ,'txt_subway' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Метро',
            'tip' => 'Метро',
            'class' => 'typewatch',
            'tip' => 'Начните вводить метро',
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
            'label' => '№ дома',
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
        ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'id_region' => array(
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
            'label' => 'Текстовый адрес',
            'tip' => 'Введите адрес. (текстовый вариант в произвольной форме)',
            'step'=>2
        )              
        ,'div_' => array(
            'fieldtype' => 'div',
            'class' => 'clearfix'
        )
        ,'map' => array(
            'fieldtype' => 'map'
        )
                        
        ,'_title_block_21_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Параметры объекта'
        )         
        ,'id_type_object' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 99,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Тип объекта'
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'digit',
            'label' => 'Стоимость, руб.'
        )

        ,'rooms' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 99,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'digit',
            'label' => 'Количество комнат'    
        )
        
        ,'_title_row_squares_' => array(
            'type' => TYPE_STRING,
            'class' => 'squares',
            'fieldtype' => 'title_row',
            'tip' => 'Площадь'
        )

        ,'square_full' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true,
            'fieldtype' => 'text',
            'label' => 'Общая'
        )
        ,'square_live' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true,
            'fieldtype' => 'text',
            'label' => 'Жилая'
        )
        ,'square_ground' => array(
            'type' => TYPE_FLOAT,
            'min' => 0,
            'allow_empty' => true,
            'fieldtype' => 'text',
            'label' => 'Участка, сот'
        )
        
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 8,
            'allow_empty' => true,
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'radio',
            'class' => 'radio-group',
            'values' => array(4=>'да',2=>'нет'),
            'label' => '+ статус премиум'
        )          
        ,'_title_block_bc_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'hidden' => true,
            'tip' => 'Коттеджный поселок'
        )        
        ,'cottage' => array(
            'type' => TYPE_STRING,
            'allow_null' => true, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'url'   =>  '/cottedzhnye_poselki/title/', 
            'input'=>'id_cottage',

            'label' => 'Прикрепить объект к КП',
            'tip' => 'Прикрепить объект к КП',
            'class' => 'typewatch',
            'tip' => 'Начните вводить название КП'
        )
        ,'id_cottage' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )        
        ,'_title_block_3_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Общие характеристики'
        )
        ,'year_build' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Год постройки'
        )
        
        ,'id_ownership' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Вид собственности'
        )
        ,'id_roof_material' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Материал крыши'
        )
        ,'id_construct_material' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Материал стен'
        )
        ,'id_electricity' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Электричество'
        )
        ,'id_river' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Водоем'
        )
        ,'id_water_supply' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Водоснабжение'
        )
        ,'id_heating' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Отопление'
        )
        ,'id_toilet' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Санузел'
        )
        ,'id_gas' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Газ'
        )
        ,'id_garden' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Сад/огород'
        )
        ,'id_bathroom' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Баня'
        )
        ,'id_building_progress' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -'),
            'label' => 'Готовность постройки'
        )
        ,'phone' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите -', 1=>'Есть',2=>'Нет'),
            'label' => 'Телефон'
        )
        ,'seller_name' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => false, 
            'allow_null' => false,
            'nodisplay' => true,
            'fieldtype' => 'text',
            'label' => 'Контактное лицо'
        )
        ,'seller_phone' => array(
            'type' => TYPE_STRING,
            'max' => 60,
            'allow_empty' => false, 
            'allow_null' => false,
            'nodisplay' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон контактного лица'
        )
        ,'_title_block_notes_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Описание'
        )  
        ,'notes' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            
            'label' => ''
        )
         ,'id_user_type' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden' => true,
            'fieldtype' => 'select'
        )
        ,'id_work_status' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => false,
            'allow_empty' => false, 
            'allow_null' => false,
            'hidden' => true,
            'fieldtype' => 'radio'
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
        ,'id_main_video' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden'
        )        
        
    ),
    
//контекстные штуки
    'context_campaigns' => array(
        'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'maxlength' => 45,
            'label' => 'Название рекламной кампании',
            'tip' => 'Название рекламной кампании'
        )
        ,'date_start' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'datetime',
            'label' => 'Начало кампании',
            'tip' => ''
        )
        ,'date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'datetime',
            'label' => 'Окончание кампании',
            'tip' => ''
        )
        ,'balance' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Баланс кампании',
            'tip' => 'Баланс кампании'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'активна',2=>'не активна'),
            'label' => 'Статус рекламной кампании',
            'tip' => 'Активна - Не активна'
        )
        ,'description' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Описание рекламной кампании',
            'tip' => 'Описание рекламной кампании'
        )
    ),
    'context_advertisements' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true,
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'maxlength' => 30,
            'label' => 'Название рекламного блока',
            'tip' => 'Название рекламного блока. Нажмите чтобы редактировать.'
        )
        ,'block_type' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Изображение',2=>'Изображение и текст',3=>'Текст'),
            'label' => 'Вид блока',
            'tip' => ''
        )
        ,'id_place' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [],
            'label' => 'Размещение',
            'tip' => ''
        )
        ,'estate_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'Недвижимость',
            'tip' => ''
        )
        ,'deal_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'Тип сделки',
            'tip' => 'Продажа-Аренда'
        )
        ,'banner_title' => array(
            'type' => TYPE_STRING,
            'max' => 32,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Заголовок *',
            'tip' => 'Заголовок объявления'
        )
        ,'banner_text' => array(
            'type' => TYPE_STRING,
            'max' => 80,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Текст *',
            'tip' => 'Текст объявления'
        )
        ,'url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'placeholder' => 'http://',
            'label' => 'Ссылка',
            'tip' => 'Ссылка для перехода по клику'
        )
        ,'get_pixel' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => 'http://',
            'label' => 'Ссылка на счётчик',
            'tip' => 'Ссылка на счетчик (получение пикселя)'
        )
        ,'price_floor' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'Нижняя граница цены для выборки',
            'tip' => 'Нижняя граница цены для выборки'
        )
        ,'price_top' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'Верхняя граница цены для выборки',
            'tip' => 'Верхняя граница цены для выборки'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'активно',2=>'не активно',3=>'на модерации'),
            'label' => 'Вкл./Выкл.',
            'tip' => 'Активна - Не активна'
        )
    )
    ,'staff' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
         'sex' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'status' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
         ),
         'email' => array(
            'type' => TYPE_STRING,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'login' => array(
            'type' => TYPE_STRING,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )         
        ,'name' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Имя',
        ) 
        ,'lastname' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Фамилия',
        )
        ,'phone' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'fieldtype' => 'phone',
            'label' => 'Телефон',
        )
        ,'_title_block_profile_' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'title_block',
            'tip' => 'Специализация'
        )        
        ,'specializations' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'specializations_set',
            'values' => Config::Get('users_specializations'),
            'label' => '',
            'tip' => ''
        )
    )   
);
?>