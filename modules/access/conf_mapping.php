<?php
return array(
    'users' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'is_blocked' => array(
            'type' => TYPE_INTEGER,
            'max' => 2, 'min' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'label' => 'Заблокирован',
            'tip' => 'Пользователь не может авторизоваться на сайте',
            'values' => array(1=>'Да', 2=>'Нет')
        )

        ,'login' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'autocomplete' => true,
            'label' => 'Логин',
            'tip' => 'Логин пользователя, имя, под которым он авторизуется'
        )
        ,'passwd' => array(
            'type' => TYPE_STRING,
            'max' => 255, 
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'password',
            'autocomplete' => true,
            'label' => 'Пароль',
            'tip' => 'Пароль пользователя (скрыт в целях безопасности, можно только заменить)'
        )
        ,'id_group' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Внутренняя группа',
            'tip' => 'Группа, к которой принадлежит пользователь',
            'values' => array(0 => '- не выбрана -')
        )
        ,'name' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Имя пользователя',
            'tip' => 'Имя пользователя'
        )
        ,'lastname' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Фамилия пользователя',
            'tip' => 'Фамилия пользователя'
        )
        ,'email' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'E-mail пользователя',
            'tip' => 'E-mail пользователя'
        )
        ,'show_email' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Показывать email в профиле'
        )
        ,'id_user_type'=>array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Тип пользователя',
            'tip' => 'Тип, к которому принадлежит пользователь',
            'values' => array(0 => '- не выбран -')
        )
        ,'payed_page' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'расширенная карточка'
        )
        ,'phone' => array(
            'type' => TYPE_STRING,
            'max' => 18,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'phone',
            'label' => 'Телефон пользователя',
            'tip' => 'Телефон пользователя'
        )
        ,'show_phone' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Показывать телефон в профиле'
        )
        ,'skype' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Skype пользователя',
            'tip' => 'Skype пользователя (если есть)'
        )
        ,'id_agency' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Агентство',
            'tip' => 'Пользователь представляет агентство',
            'values' => array(0 => '- не выбрано -')
        )
        ,'user_activity' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'label' => 'Вид деятельности',
            'tip' => 'Вид деятельности пользователя',
            'values' => array(1 => 'недвижимость', 2 => 'юриспруденция')
        )
        ,'subscribe_news' => array(
            'type' => TYPE_INTEGER,
            'max' => 2, 'min' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'label' => 'Подписка на новости',
            'tip' => 'Пользователь подписан на новости',
            'values' => array(1=>'Подписан', 2=>'Не подписан')
        )
        ,'xml_notification' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Отчеты загрузки фида XML',
            'tip'=>'Отчеты загрузки фида XML'
        )
        ,'access' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Права доступа',
            'tip' => 'Особые права доступа для пользователя'
        )
        ,'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'О себе (только для специалистов)',
            'tip' => 'О себе (только для специалистов)'
        )
    ),
    'users_groups' => array(
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
    'system_messages' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Содержимое',
            'tip' => 'Содержимое'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Опубиковано(начать рассылку)',2=>'Не опубликовано'),
            'label' => 'Статус',
            'tip' => 'Состояние рассылки'
        )
    ),    
    'agencies' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'id_main_office' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'working_times' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )
        ,'_title_row_1_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Данные пользователя'
        )
        ,'users_id' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'autocomplete' => true,
            'label' => 'ID администратора',
            'tip' => ''
        )
        ,'_title_row_2_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Данные агентства'
        )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Полное название агентства'
        )
        ,'description' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Описание агентства'
        )
        ,'addr' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Адрес',
            'tip' => 'Город, улица, дом и т.д.'
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
            'label' => 'Телефон 2(используется во всплывашке обратного звонка)',
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
        ,'advert_phone' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => '"Подставной" телефон (ЖК, карточка компании)',
            'tip' => 'Наш номер для отслеживания рекламы'
        )
        ,'advert_phone_date_end' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата окончания подставного телефона'
        )
        
        ,'advert_phone_objects' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => '"Подставной" телефон для выдачи (строчки)',
            'tip' => 'Наш номер для отслеживания рекламы в карточках объектов'
        )
        ,'call_cost' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость звонка',
            'tip' => 'Стоимость звонка для всех объектов находящихся'
        )
        ,'show_call_link' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'value' => 1,
            'label' => 'Показывать запись звонка',
            'tip' => 'Показывать запись звонка в ЛК агентства'
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
        ,'email_service' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'E-mail для оповещений',
            'tip' => ''
        )
        ,'email_applications' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'E-mail для заявок',
            'tip' => ''
        )
        ,'email_consults' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'E-mail для оповещений сервиса Консультант',
            'tip' => ''
        )
        ,'email_application_realtor_help' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'E-mail для оповещений сервиса Помощь риэлтора',
            'tip' => ''
        )        
        ,'url' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на сайта',
            'tip' => 'Формат: www.site.ru'
        )
        ,'url_title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Текст ссылки',
            'tip' => 'Текст будет показан как название ссылки. Если будет пустым, ссылка не покажется.'
        )        
        ,'social_link' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Группа в соцсетях',
            'tip' => 'Формат: http://vk.com/group'
        )
        ,'doverie_years' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Доверие потребителя - призер по годам',
            'tip' => 'Призер/участник в конкурсе Доверие потребителя по годам, например: 2015, 2016'
        )    

        ,'doverie_participant' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'value' => 1,
            'label' => 'Доверие потребителя - участник',
            'tip' => 'Участник конкурса в текущего конкурса'
        )                	
        ,'main_color' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 6,
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Основной цвет'
        )        
        ,'second_color' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 6,
            'placeholder' => '6 символов, без #, например: 1e88e5',
            'label' => 'Второй цвет'
        )        
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'вкл',2=>'выкл'),
            'value' => 1,
            'label' => 'Статус',
            'tip' => 'Доступ в ЛК'
        )		
        ,'is_archive' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(2=>'Да',1=>'Нет'),
            'label' => 'Показывать в списке агентств',
            'tip' => 'Показывать на сайте в списке агентств'
        )
        ,'advert' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(2=>'Нет',1=>'Да'),
            'label' => 'Показывать баннер "Реклама"',
            'tip' => 'Платное агентство'
        ),
        'advert_url' => [
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Ссылка на рекламодателя',
            'tip' => 'обязательно наличие http'
        ]
    ,'token' => [
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Токен',
        ]
        ,'id_manager' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите менеджера -'),
            'label' => 'Менеджер',
            'tip' => 'Менеджер БСНа отвечающий за данное агентство'
        )            														
        ,'_title_row_8_' => array(
            'fieldtype' => 'title_row',
            'class' => 'worktimes',
            'tip' => 'График работы и обработки заявок (галочка - обработка заявок в этот день)'
        )
        ,'_title_row_3_' => array(
			'fieldtype' => 'title_row',
			'tip' => 'Вид деятельности'
        )
        ,'activity' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'set',
            'values' => array(1=>'Агентства',2=>'Рекламное агентство',3=>'Застройщик',4=>'УК',5=>'Банки',6=>'Девелоперы',7=>'Инвестиции',8=>'Другой профиль'),
            'label' => '',
            'tip' => ''
        )
        ,'is_agregator' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'value' => 2,
            'label' => 'Агрегатор',
            'tip' => 'Является ли компания агрегатором'
        )
        ,'mortgage_applications_accepting' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 5,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'короткие',2=>'длинные',4=>'все',5=>'не принимаются'),
            'value' => 2,
            'label' => 'Принимаемые заявки на ипотеку',
            'tip' => ''
        )
        ,'_title_row_4_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Вывод в реестре агентств недвижимости'
        )
        ,'estate_types' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'set',
            'values' => array(1=>'Жилая',2=>'Новостройки',3=>'Коммерческая',4=>'Загородная',6=>'Зарубежная',7=>'Коттеджи',8=>'БЦ',9=>'Ипотека',10=>'Страхование'),
            'label' => '',
            'tip' => ''
        )
        ,'_title_row_5_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Пакетное размещение объектов недвижимости (если выбран максимальный пакет и стоит кредит 0 - то выгрузка безлимитная)'
        )
        ,'id_tarif' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите тариф -'),
            'label' => 'Тариф',
            'tip' => 'Выбор тарифного размещения'
        )    
        ,'payed_page' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'hidden',
            'label' => 'расширенная карточка'
        )
        ,'change_tarif' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'да', 2=>'нет'),
            'value' => 2,
            'label' => 'Смена тарифа',
            'tip' => 'Для смена тарифа выберите ДА'
        )    
        ,'tarif_start' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата начала тарифа'
        )        
        ,'tarif_end' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата окончания тарифа'
        )
        ,'tarif_cost' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Стоимость тарифа',
            'tip' => 'Стоимость тарифа (заполнять для тарифа Custom)'
        )
        ,'tarif_expenditures' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'value' => 1,
            'label' => 'Списания за тариф',
            'tip' => 'Списания с баланса за тариф'
        )
        ,'total_objects' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'кредит: Всего'."<br />".'(ОТМЕНЯЕТ ОСТАЛЬНЫЕ СЧЕТЧИКИ). ТОЛЬКО ДЛЯ ВЫГРУЗКИ!',
            'class' => 'object_packets_vars',
            'tip' => 'Кол-во вариантов всего'
        )    
        ,'live_sell_objects' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'кредит: Жилая (продажа)',
            'class' => 'object_packets_vars',
            'tip' => 'Кол-во вариантов для жилой (продажа) недвижимости'
        )    
        ,'build_objects' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'кредит: Новостройки',
            'class' => 'object_packets_vars',
            'tip' => 'Кол-во вариантов для новостроек'
        )    
        ,'commercial_sell_objects' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'кредит: Коммерческая (продажа)',
            'class' => 'object_packets_vars',
            'tip' => 'Кол-во вариантов для коммерческой недвижимости'
        )    
        ,'commercial_rent_objects' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'кредит: Коммерческая (аренда)',
            'class' => 'object_packets_vars',
            'tip' => 'Кол-во вариантов для коммерческой недвижимости'
        )    
        ,'country_sell_objects' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'кредит: Загородная (продажа)',
            'class' => 'object_packets_vars',
            'tip' => 'Кол-во вариантов для загородной недвижимости'
        )  
        ,'country_rent_objects' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'кредит: Загородная (аренда)',
            'class' => 'object_packets_vars',
            'tip' => 'Кол-во вариантов для загородной недвижимости'
        )  
        ,'_title_row_7_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Размещение жилой аренды'
        )
        ,'live_rent_objects' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'кредит: Жилая (Аренда)',
            'tip' => 'Кол-во вариантов для жилой (аренда) недвижимости'
        )           
        ,'_title_row_loading_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Данные по выгрузке'
        )
        ,'xml_status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'value' => 2,
            'label' => 'Выгрузка активна',
            'tip' => 'Ведется ли выгрузка по ссылке'
        )
        ,'xml_link' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка для выгрузки',
            'tip' => 'Ссылка по которой ведется выгрузка'
        )                
        ,'xml_time' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'hidden',
            'label' => 'Время выгрузки',
            'tip' => 'Время в которое будет выгружаться'
        )                
        
        ,'_title_row_advert_text_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Рекламные поля'
        )
        ,'advert_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Вставка кода в head',
            'tip' => ''
        )    
    ),	
    'replenish_balance' => array(
        'id_user' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
        'id_target_user' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
        'sum' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => false,
            'allow_null' => false,
            'max' => 50000,
            'label' => 'Пополнить баланс на сумму',
            'tip' => 'Пополнить баланс выбранной компании на сумму'
         )
    ),
    'managers' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'name' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'allow_empty' => false, 
            'allow_null' => false,
            'label' => 'ФИО'
        )
        ,'phone' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон'
        )
        ,'email' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'email',
            'allow_empty' => false, 
            'allow_null' => false,
            'label' => 'Email'
        )
        ,'naydidom_credit_limit' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'max' => 10000,
            'label' => 'Оставшийся кредит (Найдидом)'
        )
        ,'month_naydidom_credit_limit' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'max' => 10000,
            'label' => 'Кредит на месяц (Найдидом)',
            'tip' => 'Добавляется к основному кредиту 1-го числа каждого месяца'
        )
        ,'pingola_credit_limit' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'max' => 10000,
            'label' => 'Оставшийся кредит (Пингола)'
        )
        ,'month_pingola_credit_limit' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'max' => 10000,
            'label' => 'Кредит на месяц (Пингола)',
            'tip' => 'Добавляется к основному кредиту 1-го числа каждого месяца'
        )
    )  
    , 'promocodes' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'allow_empty' => false, 
            'allow_null' => false,
            'label' => 'Название акции'
        )
        ,'code' => array(
            'type' => TYPE_STRING,
            'min' => 4,
            'fieldtype' => 'text',
            'allow_empty' => false, 
            'allow_null' => false,
            'label' => 'Промокод (минимум 4 символа)'
        )
        ,'type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Тип кода',
            'values' => array(0 => '- не выбран -', 1 => 'фиксированная сумма', 2=>'процент от пополненной суммы')
        )
        ,'summ' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Сумма пополнения баланса'
        )
        ,'percent' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'max' => 100,
            'label' => 'Процент от пополненной суммы'
        )
        ,'min_summ' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Минимальная сумма оплаты'
        )
        ,'date_start' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'date',
            'label' => 'Дата начала акции'
        )        
        ,'date_end' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'date',
            'label' => 'Дата окончания акции'
        )        
    ), 
    'tarifs_agencies' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'text',
            'allow_empty' => false, 
            'allow_null' => false,
            'label' => 'Название тарифа'
        )
        ,'cost' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => false, 
            'allow_null' => true,
            'label' => 'Стоимость тарифа'
        )
        ,'cnt' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Объектов в сутки'
        )
        ,'staff_number' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество сотрудников'
        )        
        ,'promo' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Услуга ПРОМО'
        )        
        ,'premium' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Услуга ПРЕМИУМ'
        )        
        ,'vip' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Услуга VIP'
        )        
        ,'action' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Услуга "Акция"'
        )        
        ,'video' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Промо видео'
        )        
        ,'business_center' => array(
            'type' => TYPE_INTEGER,
            'max' => 2, 'min' => 1,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'label' => 'Управление БЦ',
            'tip' => 'Админ агетства может управлять офисами в БЦ',
            'values' => array(1=>'Может управлять', 2=>'Не может управлять')
        )
        
    )    
);
?>