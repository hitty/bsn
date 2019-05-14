<?php
/**
* Файл с описанием структуры меню админки
*/
return array(
    'pages' => array(
        'module' => 'pages',
        'title' => 'Страницы',
        'menu' => true,
        'childs' => array(
            'add' => array(
                'title' => 'Новая страница',
                'menu' => true
            )
        )
    ),
    'seo' => array(
        'title' => 'SEO данные',
        'module' => 'seo',
        'menu' => true,
        'childs' => array(
            'add' => array(
                'title' => 'Новая SEO запись',
                'menu' => true
            ),
            'not_indexed' => array(
                'title' => 'Ловец ботов',
                'menu' => true,
                'childs' => array(
                    'google' => array(
                        'title' => 'GoogleBot',
                        'menu' => true,
                        'childs' => array(
                            'stats' => array(
                                'title' => 'Статистика',
                                'menu' => true
                            )
                        )
                    ),
                    'yandex' => array(
                        'title' => 'YandexBot',
                        'menu' => true,
                        'childs' => array(
                            'stats' => array(
                                'title' => 'Статистика',
                                'menu' => true
                            ),
                            'index_checked/indexed' => array(
                                'title' => 'Индексированные',
                                'menu' => true
                            ),
                            'index_checked/backed' => array(
                                'title' => 'Неиндексированные',
                                'menu' => true
                            )
                        )
                    ),
                    'mailru' => array(
                        'title' => 'MailruBot',
                        'menu' => true,
                        'childs' => array(
                            'stats' => array(
                                'title' => 'Статистика',
                                'menu' => true
                            )
                        )
                    ),
                    'from_search' => array(
                        'title' => 'Переходы с поиска',
                        'menu' => true
                    )
                )
            )
        )
    ),
    'access' => array(
        'shift_url' => empty($this_page->page_parameters[1]) || $this_page->page_parameters[1]!='promotions' ? false : true,
        'module' => 'access',
        'title' => 'Пользователи, агентства',
        'menu' => true,
        'childs' => array(
            'users' => array(
                'title' => 'Пользователи',
                'menu' => true,
                'childs' => array(
                    'add' => array(
                        'title' => 'Добавить пользователя',
                        'menu' => true
                    )
                    ,'users_subsribed_stats' => array(
                        'title' => 'Статистика подписчиков',
                        'menu' => true
                    )
                    ,'users_stats' => array(
                        'title' => 'Статистика всех пользователей',
                        'menu' => true
                    )
                    ,'system_messages' => array(
                        'title' => 'Системные сообщения',
                        'menu' => true
                    )
                    ,'transactions' => array(
                        'title' => 'Подтверждение транзакций',
                        'menu' => true
                    )
                    ,'promocodes' => array(
                        'title' => 'Промокоды',
                        'menu' => true,
                        /*
                        'childs' => array(
                            'used' => array(
                                'title' => 'Список использованных',
                                'menu' => true
                            )
                        )
                        */
                    )
                )
            ),
            'users_groups' => array(
                'title' => 'Группы',
                'menu' => true,
                'childs' => array(
                    'add' => array(
                        'title' => 'Добавить группу',
                        'menu' => true
                    )
                )
            ),
            'agencies' => array(
                'title' => 'Агентства',
                'menu' => true,
                'childs' => array(
                    'add' => array(
                        'title' => 'Добавить агентство',
                        'menu' => true
                    ),
                    'tarifs' => array(
                        'title' => 'Тарифы',
                        'menu' => true
                    ),
                    'transactions' => array(
                        'title' => 'Подтверждение транзакций',
                        'menu' => true
                    ),
                    'agencies_operations' => array(
                        'title' => 'Операции с агентствами',
                        'menu' => true
                    ),
                    'xml_stats' => array(
                        'title' => 'Статистика обработки XML',
                        'menu' => true
                    )
                )
            ) 
            , 'promotions' => array(
                'shift_url' => true,
                'module' => 'promotions',
                'title' => 'Акции',
                'menu' => true
            )
            
            ,'managers' => array(
                'title' => 'Менеджеры БСН',
                'menu' => true
            )
        )
    ),
    'content' => array(
        'title' => 'Контент (новости, статьи, мнения...)',
        'template' => 'admin/templates/admin.content.html',
        'shift_url' => true,
        'menu' => true,
        'childs' => array(
            'news' => array(
                'module' => 'content',
                'title' => 'Новости',
                'menu' => true,
                'childs' => array(
                    'regions' => array(
                        'title' => 'Список регионов',
                        'menu' => true
                    ),
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    )
                    ,'check_time' => array(
                        'title' => 'Время рассылки',
                        'menu' => true
                    )
                    ,'mailer_banners' => array(
                        'title' => 'Баннеры для рассылки',
                        'menu' => true
                    )
                    ,'news_sources' => array(
                        'title' => 'Источники новостей',
                        'menu' => true
                    )
                    ,'news_from_sources' => array(
                        'title' => 'Новости из источников',
                        'menu' => true
                    )
                )
            ),
            'articles' => array(
                'module' => 'content',
                'title' => 'Статьи',
                'menu' => true,
                'childs' => array(
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    ),
                    'partners' => array(
                        'title' => 'Партнеры',
                        'menu' => true
                    )
                )
            ),
            'doverie' => array(
                'module' => 'content',
                'title' => 'Доверие потребителя',
                'menu' => true,
                'childs' => array(
                    
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    )
                )
            ),                   
            'bsntv' => array(
                'module' => 'content',
                'title' => 'БСН-ТВ',
                'menu' => true,
                'childs' => array(
                    
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    )
                )
            ),            
            'blog' => array(
                'module' => 'content',
                'title' => 'Блог',
                'menu' => true,
                'childs' => array(
                    
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    )
                )
            ),            
            
            'tags' => array(
                'module' => 'tags',
                'title' => 'Теги',
                'menu' => true,
                'childs' => array(
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    ),
                    'similar' => array(
                        'title' => 'Похожие теги',
                        'menu' => true
                    )
                )
            ),
            'comments' => array(
                'module' => 'comments',
                'title' => 'Модерация комментариев',
                'menu' => true
            ),
            'calendar_events' => array(
                'module' => 'calendar_events',
                'title' => 'Календарь событий',
                'menu' => true
            ),
            'galleries' => array(
                'module' => 'galleries',
                'title' => 'Фотогалереи',
                'menu' => true
			),
			'opinions_predictions' => array(
				'module' => 'opinions_predictions',
				'title' => 'Мнения / прогнозы / интервью',
				'menu' => true,
				'childs' => array(
					'experts' => array(
						'title' => 'Профили экспертов',
						'menu' => true
					),
					'agencies' => array(
						'title' => 'Каталог агентств',
						'menu' => true
					),
					'estate_types' => array(
						'title' => 'Типы недвижимости',
						'menu' => true
					)
				)
            ),
            'consults' => array(
                'module' => 'consults',
                'title' => 'Консультации',
                'menu' => true,
                'childs' => array(
                    'categories' => array(
                        'title' => 'Категории',
                        'menu' => true
                    ),
                    'members' => array(
                        'title' => 'Юристы',
                        'menu' => true
                    )
                )
            ),            
            'partners_articles' => array(
                'module' => 'partners_articles',
                'title' => 'Статьи от партнеров',
                'menu' => true,
                'childs' => array(
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    )
                )
            ),
            'dictionary' => array(
                'module' => 'dictionary',
                'title' => 'Словарь',
                'menu' => true
            ),
            'guestbook' => array(
                'module' => 'guestbook',
                'title' => 'Гостевая книга',
                'menu' => true
            ),
            'help' => array(
                'module' => 'help',
                'title' => 'Раздел помощи',
                'menu' => true,
                'childs' => array(
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    )
                )
            ),
            'information' => array(
                'module' => 'information',
                'title' => 'Справочные материалы',
                'menu' => true,
                'childs' => array(
                    'categories' => array(
                        'title' => 'Разделы',
                        'menu' => true
                    ),
                    'types' => array(
                        'title' => 'Категории',
                        'menu' => true
                    ),
                    'offices' => array(
                        'title' => 'Учреждения',
                        'menu' => true
                    ),
                )
            ),
            'changes' => array(
                'module' => 'changes',
                'title' => 'Список изменений в проектах',
                'menu' => true
            ),
            'invest' => array(
                'module' => 'invest',
                'title' => 'Презентация',
                'menu' => true
            )
		)		
    ),
    /*
    'moderation' => array(
        'title' => 'Модерация',
        'template' => 'admin/templates/admin.moderation.html',
        'shift_url' => true,
        'menu' => true,
        'childs' => array(
            'streets' => array(
                'module' => 'streets',
                'title' => 'Модерация улиц',
                'menu' => true
            )
        )
    ), 
    */   
    'estate' => array(
        'title' => 'Объекты недвижимости',
        'template' => 'admin/templates/admin.estate.html',
        'shift_url' => true,
        'menu' => true,
        'childs' => array(
            'live' => array(
                'module' => 'estate',
                'title' => 'Жилая недвижимость',
                'menu' => true,
                'childs' => array(
                    'new' => array(
                        'module' => 'estate',
                        'title' => 'На модерации',
                        'menu' => true
                    ),
                    'addr_problems' => array(
                        'module' => 'estate',
                        'title' => 'Проблемы с адресами',
                        'menu' => true
                    )
                )
            ),
            'build' => array(
                'module' => 'estate',
                'title' => 'Новостройки',
                'menu' => true,
                'childs' => array(
                    'new' => array(
                        'module' => 'estate',
                        'title' => 'На модерации',
                        'menu' => true
                    ),
                    'addr_problems' => array(
                        'module' => 'estate',
                        'title' => 'Проблемы с адресами',
                        'menu' => true
                    )
                )
            ),
            'commercial' => array(
                'module' => 'estate',
                'title' => 'Коммерческая недвижимость',
                'menu' => true,
                'childs' => array(
                    'new' => array(
                        'module' => 'estate',
                        'title' => 'На модерации',
                        'menu' => true
                    ),
                    'addr_problems' => array(
                        'module' => 'estate',
                        'title' => 'Проблемы с адресами',
                        'menu' => true
                    )
                )
            ),
            'country' => array(
                'module' => 'estate',
                'title' => 'Загородная недвижимость',
                'menu' => true,
                'childs' => array(
                    'new' => array(
                        'module' => 'estate',
                        'title' => 'На модерации',
                        'menu' => true
                    ),
                    'addr_problems' => array(
                        'module' => 'estate',
                        'title' => 'Проблемы с адресами',
                        'menu' => true
                    )
                )
            ),
            'housing_estates' => array(
                'module' => 'estate',
                'title' => 'Жилые комплексы',
                'menu' => true
            ),
            
            'cottages' => array(
                'module' => 'cottages',
                'title' => 'Коттеджные поселки',
                'menu' => true,
                'childs' => array(
                    'developers' => array(
                        'module' => 'cottages',
                        'title' => 'Каталог девелоперов',
                        'menu' => true
                    )
                    ,'settlements' => array(
                        'module' => 'cottages',
                        'title' => 'Анализ спроса',
                        'menu' => true,
                        'childs' => array(
                            'country_demand' => array(
                                'module' => 'cottages',
                                'title' => 'Тексты',
                                'menu' => true,
                            )
                            ,'country_demand_members' => array(
                                'module' => 'cottages',
                                'title' => 'Участники',
                                'menu' => true,
                            )

                        )
                    )
                )
            ),
            'business_centers' => array(
                'module' => 'business_centers',
                'title' => 'Бизнес-центры',
                'menu' => true,
                'childs' => array(
                    'levels' => array(
                        'module' => 'business_centers',
                        'title' => 'Этажи БЦ',
                        'menu' => true
                    ),
                    'corps' => array(
                        'module' => 'business_centers',
                        'title' => 'Корпуса БЦ',
                        'menu' => true
                    )
                )
            )      
            ,'estate_complexes_external' => array(
                'module' => 'estate_complex',
                'title' => 'Прикрепление комплексов от агентств',
                'menu' => true
            )            
                  
        )
    ),
    'advert_objects' => array(
        'title' => 'Объектная реклама',
        'template' => 'admin/templates/admin.advert_objects.html',
        'shift_url' => true,
        'menu' => true,
        'childs' => array(
			'tgb' => array(
		        'module' => 'tgb',
				'title' => 'ТГБ',
				'menu' => true,
				'childs' => array(
                    'banners' => array(
                        'title' => 'Баннеры',
                        'menu' => true
                    )
                    ,'total_stats' => array(
                        'title' => 'Статистика всех ТГБ',
                        'menu' => true
                    )
				)
				
			),
			
            'banners' => array(
                'module' => 'html_banners',
                'title' => 'Баннеры Adriver',
                'menu' => true
            ),
            'district_banners' => array(
                'module' => 'district_banners',
                'title' => 'Спонсоры районов (баннеры)',
                'menu' => true
            ),
            'credit_calculator' => array(
                'module' => 'credit_calculator',
                'title' => 'Кредитный калькулятор',
                'menu' => true
            ),
            'tgb_vertical' => array(
                'module' => 'tgb_vertical',
                'title' => 'Вертикальный баннер',
                'menu' => true
            ),
            'tgb_overlay' => array(
                'module' => 'tgb_overlay',
                'title' => 'Overlay баннер',
                'menu' => true,
                'childs' => array(
                    'phones' => array(
                        'module' => 'tgb_overlay',
                        'title' => 'Заявки (телефоны)',
                        'menu' => true,
                    )
                )
            ),
            'tgb_float' => array(
                'module' => 'tgb_float',
                'title' => 'Float-баннер',
                'menu' => true,
                'childs' => array(
                    'phones' => array(
                        'module' => 'tgb_float',
                        'title' => 'Телефоны',
                        'menu' => true,
                    )
                )
            ),
            'context_campaigns' => array(
                'module' => 'context_campaigns',
                'title' => 'БСН Таргет',
                'menu' => true,
                'childs' => array(
                    'stats' => array(
                        'module' => 'context_campaigns',
                        'title' => 'Статистика таргетинга',
                        'menu' => true,
                    )
                )
            ),
           
		)
	),
    'service' => array(
        'title' => 'Служебные',
        'template' => 'admin/templates/admin.service.html',
        'shift_url' => true,
        'menu' => true,
        'childs' => array(
            'webinars' => array(
                'module' => 'webinars',
                'title' => 'Вебинары',
                'menu' => true,
                'childs' => array(
                    'users' => array(
                        'module' => 'webinars',
                        'title' => 'Пользователи',
                        'menu' => true,
                    ),
                    'users_mails' => array(
                        'module' => 'webinars',
                        'title' => 'Пользователи списком',
                        'menu' => true,
                    )
                )
            ),
            'events_registration' => array(
                'module' => 'events_registration',
                'title' => 'Регистрации на форуме',
                'menu' => true,
            ),
            'stats' => array(
                'module' => 'stats',
                'title' => 'Статистика',
                'menu' => true,
                'childs' => array(
                    'billing' => array(
                        'module' => 'stats',
                        'title' => 'Биллинг рекламных агентств',
                        'menu' => true,
                    ),
                    'billing/2' => array(
                        'module' => 'stats',
                        'title' => 'Биллинг агентств',
                        'menu' => true,
                    ),
                    'varcount' => array(
                        'module' => 'stats',
                        'title' => 'Количество вариантов в БД',
                        'menu' => true,
                    ),
                    'newsletters' => array(
                        'module' => 'stats',
                        'title' => 'Статистика рассылок',
                        'menu' => true,
                    ),
                    'phones' => array(
                        'module' => 'stats',
                        'title' => 'Клики по телефонам',
                        'menu' => true,
                    ),
                    'favorites' => array(
                        'module' => 'stats',
                        'title' => 'Количество объектов в избранном',
                        'menu' => true,
                    ),
                    'cabinet_stats' => array(
                        'module' => 'stats',
                        'title' => 'Статистика личного кабинета',
                        'menu' => true,
                    ),
                    'finances_stats' => array(
                        'module' => 'stats',
                        'title' => 'Финансовая статистика',
                        'menu' => true,
                    )
                )
            ),
            'spam' => array(
                'module' => 'spam',
                'title' => 'Рассылка',
                'menu' => true,
                'childs' => array(
                    'normal'=>array(
                        'title' => 'Рассылка',
                        'menu' => true
                    ),
                    'spec'=>array(
                        'title' => 'Спец-рассылка',
                        'menu' => true,
                        'childs' => array(
                            'emails'=>array(
                                'title' => 'Адреса спец-рассылки',
                                'menu' => true
                            )
                        )
                        
                    )
                )
            ),
            'diploms' => array(
                'module' => 'diploms',
                'title' => 'Дипломы',
                'menu' => true
            ),
            'konkurs' => array(
                'module' => 'konkurs',
                'title' => 'Конкурсы',
                'menu' => true,
                'childs' => array(
                )
            ),
            /*
            'markers' => array(
                'module' => 'markers',
                'title' => 'Метки',
                'menu' => true,
                'childs' => array(
                )
            ),
            */
            'abuses' => array(
                'module' => 'abuses',
                'title' => 'Жалобы в карточках',
                'menu' => true,
                'childs' => array(
                    'categories' => array(
                        'title' => 'Список категорий',
                        'menu' => true
                    )
                )
            )            
            ,'mailers' => array(
                'module' => 'mailers',
                'title' => 'Список авторассылок',
                'menu' => true,
            )
            ,'housing_estates_rating' => array(
                'module' => 'housing_estates_rating',
                'title' => 'Рейтинг ЖК',
                'menu' => true,
                'childs' => array(
                    'districts'=>array(
                        'title' => 'Районы',
                        'menu' => true
                    ),
                    'experts'=>array(
                        'title' => 'Эксперты',
                        'menu' => true
                    ),
                    'rating'=>array(
                        'title' => 'Рейтинг',
                        'menu' => true
                    ),
                )
            )            
            ,'applications' => array(
                'module' => 'applications',
                'title' => 'Заявки',
                'menu' => true,
                'childs' => array(
                    'sale'=>array(
                        'title' => 'Компании с SALE',
                        'menu' => true
                    ),
                )
            )
            ,'mortgage_applications' => array(
                'module' => 'mortgage',
                'title' => 'Заявки на ипотеку',
                'menu' => true,
            ),
            'partners_landings' => array(
                'module' => 'partners_landings',
                'title' => 'Лендинги партнеров',
                'menu' => true
            )
        )
    ),
    'system' => array(
        'title' => 'Системные',
        'template' => 'admin/templates/admin.system.html',
        'shift_url' => true,
        'menu' => true,
        'childs' => array(
            'mysql' => array(
                'module' => 'system',
                'title' => 'Ошибки Mysql',
                'menu' => true,
            ),
            'mysql_cron' => array(
                'module' => 'system',
                'title' => 'Ошибки Mysql скриптов cron',
                'menu' => true,
            ),
            'members_errors' => array(
                'module' => 'system',
                'title' => 'Ошибки ЛК',
                'menu' => true,
            ),
            'ip_visits' => array(
                'module' => 'system',
                'title' => 'IP посетителей',
                'menu' => true,
                'childs' => array(
                    'full'=>array(
                        'title' => 'Общая статистика IP',
                        'menu' => true
                    ),
                )
            )
        )
    )
); 
?>