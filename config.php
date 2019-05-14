<?php
return array(
    'win_os' => substr(strtolower(php_uname('s')),0,3)=='win',
    'site' => array(                // общие настройки сайта
        'title' => 'Недвижимость в Санкт-Петербурге (СПб): продажа и аренда недвижимости - БСН.ру',
        'description' => 'Размещение и поиск информации, охватывающей весь спектр операций с недвижимостью в Санкт-Петербурге (СПб): покупка недвижимости, продажа и аренда жилья, строительство. Жилая, коммерческая, элитная, загородная, зарубежная недвижимость',
        'keywords' => 'недвижимость, санкт-петербурге, петербург, спб, продажа, аренда, питер',
        'charset' => 'UTF-8',
        'root_path' => ROOT_PATH
    ),
    'memcache' => array(            // настройки memcache
        'host' => '127.0.0.1',       // хост сервера memcached
        'port' => 11211,            // порт сервера memcached
        'time' => array(            // настройки времени хранения различных компонентов
            'config' => 600         // время кэширования файла конфигураци
        )
         ,'enabled' => true         // состояние memcached (вкл/выкл)
    ),
    'mysql' => array(
        'host' => 'localhost',
        'user' =>  DEBUG_MODE ? 'root' : 'bsn' ,
        'pass' => DEBUG_MODE ? 'root' : 'ZBvlYSTLbRiXSOcEPCl5' ,
        'charset' => 'utf8',
        'lc_time_names' => 'ru_RU'
    ),
    'mysql_remote' => array(
        'host' =>   TEST_MODE ? '92.243.74.170' : 'localhost' ,
        'user' =>   TEST_MODE ? 'bsn' : 'root',
        'pass' =>   TEST_MODE ? 'ZBvlYSTLbRiXSOcEPCl5' : 'root',
        'charset' => 'utf8',
        'lc_time_names' => 'ru_RU'
    ),
    'nginx' => array(
        'url' => DEBUG_MODE ? array('','') : array('//st1.bsn.ru','//st.bsn.ru')
    ),
    'watermark_src' => '/img/layout/watermark-bsn.png',
    'video_folders' => array(
        'konkurs_2015' => 'img/video/konkurs_2015'
    ),
    'daemons_statuses'=>array(
        1 => 'выполняется',
        2 => 'успешно завершен',
        3 => 'ошибка',
        4 => 'успешно завершен, проверен',
        5 => 'успешно завершен, ошибка при проверке',
        6 => 'ожидание',
        7 => 'отложен, нет условий для запуска'
    ),
    'img_folders' => array(
        'tmp' =>  'tmp',
        'district_banners' =>  'img/uploads/district_banners',
        'tgb_vertical' =>  'img/uploads/tgb_vertical',
        'tgb_overlay' =>  'img/uploads/tgb_overlay',
        'tgb_float' =>  'img/uploads/tgb_float',
        'credit_calculator' =>  'img/uploads/credit_calculator',
        'tgb'            =>    'img/uploads/tgb',
        'banners'            =>    'img/uploads/banners',
        'partners_landings'            =>    'img/uploads/partners_landings',
        'basic'          =>    'img/uploads',
        'news'           =>    'img/uploads',
        'bsntv'           =>    'img/uploads',
        'blog'           =>    'img/uploads',
        'help_categories' =>  'img/uploads',
        'articles'       =>    'img/uploads',
        'doverie'       =>    'img/uploads',
        'galleries'        =>    'img/uploads',
        'live'            =>    'img/uploads',
        'build'            =>    'img/uploads',
        'commercial'    =>    'img/uploads',
        'country'        =>    'img/uploads',
        'inter'            =>    'img/uploads',
        'cottages'        =>    'img/uploads',
        'business_centers'        =>    'img/uploads',
        'business_centers_levels'        =>    'img/uploads/business_centers_levels',
        'business_centers_offices'        =>    'img/uploads',
        'housing_estates'        =>    'img/uploads',
        'housing_estates_progresses' =>    'img/uploads',
        'photoblocks'    =>    'img/uploads',
        'calendar_events' => 'img/uploads',
        'opinions_expert_profiles' => 'img/uploads',
        'opinions_expert_agencies' => 'img/uploads',
        'agencies'      =>    'img/uploads',
        'spec_offers'    =>      'img/uploads/spec_offers/150x150',
        'spec_offers_objects' => 'img/uploads',
        'news_mailer_banners' => 'img/uploads/sm',
        'spam_banners' => 'img/uploads/sm',
        'diploms'       =>  'img/uploads',
        'konkurs'       =>  'img/uploads',
        'konkurs_members'       =>  'img/uploads',
        'sale_campaigns' =>  'img/uploads',
        'sale_offers' =>  'img/uploads',
        'users' =>  'img/uploads',
        'context_advertisements' => 'img/uploads',
        'mailers' => 'img/uploads',
        'references_docs' => 'img/uploads',
        'webinars' => 'img/uploads',
        'housing_estates_experts' => 'img/uploads',
        'invest' => 'img/uploads'
    ),
    'video_folder' => 'img/videos',
    'crawlers_folders' => array(
        'tmp' => 'tmp',
        'google' => 'tmp',
        'yandex' => 'tmp',
        'mailru' => 'tmp'
    ),
    'images' => array(
        'min_width' => 800
        ,'min_height' => 600
    ),
    'docs_folders' => 'docs',
    'xml_file_folders' => array(
         'downloads' => 'xml/downloads'
         ,'downloads' => 'xml/downloads'
    ),
    'bsn_groups' => array( '3', '8', '101' ),//id групп пользователей БСН
    'view_settings' => array(
        'strings_per_page' => 20
        ,'strings_per_page_agencies' => 40
        ,'last_offers_estate' => 9
        ,'last_offers_estate_in_catalog' => 3
        ,'premium_estate_offers' => 5
        ,'opinions_mainpage' => 2
        ,'news_mainpage' => 4
        ,'articles_mainpage' => 4
        ,'longread_mainpage' => 4
        ,'search_result_estate' => 30       //количество объектов на странице выдачи
    ),  
    'users_avatar_colors' => array(
        '#4bbd44',
        '#d7632a',
        '#c73a57',
        '#a8bb47',
        '#cd8a35',
        '#6d46bb',
        '#4041c2',
        '#4097c2',
        '#cd4435',
        '#b94992',
        '#1e88e5',
        '#40c294'
    ),
    'blocks_cache_time' => array(
         'news_block' => 900                // блок новостей
        ,'content_block' => 900                // блок новостей
        ,'articles_block' => 900           // блок статей
        ,'longread_block' => 900           // блок статей
        ,'articles_graphics' => 3600       // блок графикованалитики
        ,'calendar_block' => 3600           // блок календаря событий
        ,'opinions_block' => 3600           // блок мнения экспертов
        ,'district_banners_block' => 3600   // блок мнения экспертов
        ,'organizations_block' => 7200      // блок организаций
        ,'last_offers_block' => 1800        // блок последних предложений по недвижимости
        ,'similar_offers_block' => 1800     // блок похожих объектов (1 день)
        ,'cottages_map' => 1800             // карта котт.поселков
        ,'housing_estates_map' => 2500      // карта ЖК
        ,'bc_map' => 259200                 // карта БЦ (3 суток)
        ,'form_filter' => 259200            // главное меню
        ,'vip_objects' => 1800              // VIP блок сквозной
        ,'menu' => 1800                     // главное меню
        ,'estate_popular_list' => 1800      // статистика объектов по срезам
    ),
    'fixed_prefix_urls' => array(           // фиксированный префикс для динамических урлов
        'partners_articles',                // статьи партнеров 
        'artpay',                           // статьи партнеров, редирект
        'pr_investment'                     //Регистрация на форумы, редирект
    ),
    'emails'=> array(                       // email сотрудников
        'pr' => 'editor@bsn.ru'
        ,'web' => 'scald@bsn.ru'
        ,'manager' => 'pm@bsn.ru'
        ,'web2' => 'web@bsn.ru'
        ,'content_manager' => 'marina@bsn.ru'
        ,'content_manager2' => 'cw@bsn.ru'
    ),
    'moderate_statuses' => array(       //статусы модерации    
          2 => 'маленькая стоимость'
        , 3 => 'большая стоимость'
        , 4 => 'нет адреса' 
        , 5 => 'нет типа объекта' 
        , 6 => 'нет кол-ва комнат' 
    ),
    'transactions' => array(       //статусы модерации    
          3 => array('title' => 'Промо',                'prefix'=>'promo')
        , 4 => array('title' => 'Премиум',              'prefix'=>'premium')
        , 5 => array('title' => 'Обычный оплаченный',   'prefix'=>'paid')
        , 1 => array('title' => 'Поднятие',             'prefix'=>'raising')
        , 6 => array('title' => 'VIP',                  'prefix'=>'vip')
        , 7 => array('title' => 'Пополнение баланса',   'prefix'=>'balance')
        , 8 => array('title' => 'BSN.Target',           'prefix'=>'context_banner')
        , 9 => array('title' => 'Звонок',               'prefix'=>'call')
    ),
    'agencies_activities' => array(
                                 array('title'=>'Агентства недвижимости', 'url'=>'agencies')
                                ,array('title'=>'Рекламные агентства',    'url'=>'adv_agencies') 
                                ,array('title'=>'Застройщики',            'url'=>'zastr') 
                                ,array('title'=>'Управляющие компании',   'url'=>'upr') 
                                ,array('title'=>'Банки',                  'url'=>'bank') 
                                ,array('title'=>'Девелоперы',             'url'=>'devel') 
                                ,array('title'=>'Инвестиции',             'url'=>'invest') 
                                ,array('title'=>'Другой профиль',         'url'=>'other')
    ),
    
    'object_types' => array(
        'live' => array('name' => 'Жилая',                              'short_name'=>'Жилая',          'key' => 1, 'list_template' => '/modules/estate/templates/list.block.live.html'),
        'build' => array('name' => 'Новостройки',                       'short_name'=>'Новостройки',    'key' => 2, 'list_template' => '/modules/estate/templates/list.block.build.html'),
        'commercial' => array('name' => 'Коммерческая',                 'short_name'=>'Коммерческая',   'key' => 3, 'list_template' => '/modules/estate/templates/list.block.commercial.html'),
        'country' => array('name' => 'Загородная',                      'short_name'=>'Загородная',     'key' => 4, 'list_template' => '/modules/estate/templates/list.block.country.html'),
        'zhiloy_kompleks' => array('name' => 'ЖК',                      'short_name'=>'ЖК',             'key' => 5, 'list_template' => '/modules/housing_estates/templates/list.block.html'),
        'housing_estates' => array('name' => 'ЖК',                      'short_name'=>'ЖК',             'key' => 5, 'list_template' => '/modules/housing_estates/templates/list.block.html'),
        'apartments' => array('name' => 'Апартаменты',                  'short_name'=>'Апартаменты',    'key' => 9, 'list_template' => '/modules/housing_estates/templates/list.block.html'),
        'cottedzhnye_poselki' => array('name' => 'Коттеджные поселки',  'short_name'=>'КП',             'key' => 6, 'list_template' => '/modules/cottages/templates/list.block.html'),
        'cottages' => array('name' => 'Коттеджные поселки',             'short_name'=>'КП',             'key' => 6, 'list_template' => '/modules/cottages/templates/list.block.html'),
        'business_centers' => array('name' => 'Бизнес-центры',          'short_name'=>'БЦ',             'key' => 7, 'list_template' => '/modules/business_centers/templates/list.block.html'),
        'inter' => array('name' => 'Зарубежная',                        'short_name'=>'Зарубежная',     'key' => 8, 'list_template' => '/modules/estate/templates/list.block.inter.html')
    ),
    'months' => array (
        1=>'январь', 2=>'февраль', 3=>'март', 4=>'апрель', 5=>'май', 6=>'июнь', 7=>'июль', 8=>'август', 9=>'сентябрь', 10=>'октябрь', 11=>'ноябрь', 12=>'декабрь'
    ),
    'months_short' => array (
        1=>'янв', 2=>'фев', 3=>'мар', 4=>'апр', 5=>'май', 6=>'июн', 7=>'июл', 8=>'авг', 9=>'сен', 10=>'окт', 11=>'ноя', 12=>'дек'
    ),
    'months_genitive' => array (
        1=>'января', 2=>'февраля', 3=>'марта', 4=>'апреля', 5=>'мая', 6=>'июня', 7=>'июля', 8=>'августа', 9=>'сентября', 10=>'октября', 11=>'ноября', 12=>'декабря'
    ),
    'months_prepositional' => array (
        1=>'январе', 2=>'феврале', 3=>'марте', 4=>'апреле', 5=>'мае', 6=>'июне', 7=>'июле', 8=>'августе', 9=>'сентябре', 10=>'октябре', 11=>'ноябре', 12=>'декабре'
    ),
    'social' => array(   // данные приложений для авторизации через соцсети
        'fb' => array(
            'secret' => 'e891ef8663b3f887ae0bdb4c61d4c520',
            'app_id' => '1652130908373571'
        )
        ,'vk' => array(
            'secret' => 'bdYiEqCP7PwvK64XS7WO',
            'app_id' => '4252034'
        )
        ,'ok' => array(
            'secret' => '645F6F24F87236544FD3D3B3',
            'public' => 'CBAHFQEICBABABABA',
            'app_id' => '659510784'
        )
    ),
    'call_center' => array(
        'phone' => '(812) 123-45-67',   // телефон коллцентра
        'estate' => array(              // типы по недвижимостям
            'live' => 1,
            'build' => 2,
            'commercial' => 3,
            'country' => 4,
            'zhiloy_kompleks' => 5,
            'business_centers' => 6,
            'cottages' => 7,
            'cottedzhnye_poselki' => 7,
            'apartments' => 8
        )
    ),
    'cloud4video' => array(
        'login'     => 'pm@bsn.ru',   //
        'password'  => '4d651eb627'    //
    ),
    'comagic' => array(
        'login'     => 'pm@bsn.ru',   //
        'password'  => 'Freelife0'    //
    ),
    'telegram' => array(
        'token' => '312438821:AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A',
        'request_form' => 'https://api.telegram.org/bot<token>/METHOD_NAME'
    ),
    'tgb_colors' => array(
        '4bbd44','d7632a','c73a57','a8bb47','cd8a35',
        '6d46bb','4041c2','4097c2','cd4435','b94992',
        '1e88e5','40c294'
    ),
    'users_specializations' => array(
        1=>'Продажа квартир в новостройках',
        2=>'Продажа квартир',
        3=>'Аренда квартир',
        4=>'Продажа коммерческой недвижимости',
        5=>'Аренда коммерческой недвижимости',
        6=>'Продажа загородной недвижимости',
        7=>'Аренда загородной недвижимости',
        8=>'Юрист'
    ),
    'users_carrer' => array(
        1=>'Частное лицо',
        2=>'Риэлтор',
        3=>'Представитель компании',
        4=>'Руководитель компании',
        5=>'Инвестор'
    ),
    
    'users_newsletters' => array(
        2=>'Акции и скидки от компаний',
        3=>'Новости рынка недвижимости',
        4=>'Мероприятия рынка недвижимости (выставки, семинары и т.п.)',
        5=>'Тематический видеоконтент (интервью, сюжеты, вебинары)',
        6=>'Аналитика рынка',
        1=>'Все темы по недвижимости'
    ),
    'crawlers_aliases' => array (
        1=>'google',
        2=>'yandex',
        3=>'mailru'
    ),
    'services_breadcrumbs' => array (
        "service/ratings"                     =>  'Рейтинги',
        "service/consultant"                  =>  "Консультант",
        "service/information"                 =>  "Справочная"
    ),
    'sys_tables' => array(             // таблицы БД
        //common
        'users' => 'common.users',
        'users_photos' => 'common.users_photos',
        'users_groups' => 'common.groups',
        'users_restore' => 'common.users_restore',
        'users_pay' => 'common.users_pay_history',
        'users_finances' => 'common.users_finances',
        'users_ips' => 'common.users_ips',
        'users_invites_agencies' => 'common.users_invites_agencies',
        'users_types' => 'common.user_types',
        'admin_finances' => 'common.admin_finances',
        'calls' => 'common.calls',
        'calls_tags' => 'common.calls_tags',
        'telegram_dialogs' => 'common.telegram_dialogs',
        'telegram_contacts' => 'common.telegram_contacts',
        'telegram_channels' => 'common.telegram_channels',
        'telegram_channels_stats_full' => 'common.telegram_channels_stats_full',
        
        'blacklist_ips' => 'common.blacklist_ips',
        'blacklist_urls' => 'common.blacklist_urls',
        'visitors_ips_day' => 'common.visitors_ips_day',
        'visitors_ips_full' => 'common.visitors_ips_full',
        'visitors_ips_stats_day' => 'common.visitors_ips_stats_day',
        'visitors_ips_stats_full' => 'common.visitors_ips_stats_full',
        'ips_list_js' => 'common.ips_list_js',
        'daemons' => 'service.daemons',
        'daemons_schedule' => 'service.daemons_schedule',
        'daemons_stats' => 'service.daemons_stats',
        
        
        'agencies' => 'common.agencies',
        'sale_agencies' => 'sale.agencies',
        'agencies_photos' => 'common.agencies_photos',
        'agencies_opening_hours' => 'common.agencies_opening_hours',
        'agencies_operations' => 'common.agencies_operations',
        'agencies_operation_types' => 'common.agencies_operation_types',

        'agencies_mainpage_stats_click_day'=>'service.agencies_mainpage_stats_click_day',
        'agencies_mainpage_stats_show_day'=>'service.agencies_mainpage_stats_show_day',
        'agencies_mainpage_stats_click_full'=>'service.agencies_mainpage_stats_click_full',
        'agencies_mainpage_stats_show_full'=>'service.agencies_mainpage_stats_show_full',
        'ip_geodata' => 'service.ip_geodata',
        'pages' => 'common.pages',
        'pages_seo' => 'common.pages_seo',
        'pages_map' => 'common.pages_map',

        'favorites' => 'common.favorites',
        'objects_subscriptions' => 'common.objects_subscriptions',
        'messages' => 'common.messages',
        //content
        'help_categories' => 'content.help_categories',
        'help_articles' => 'content.help_articles',
        'help_categories_photos' => 'content.help_categories_photos',
        
        'calendar_events' => 'content.calendar_events',
        'calendar_events_categories' => 'content.calendar_events_categories',
        'calendar_events_photos'=>'content.calendar_events_photos',
        'calendar_events_registrations'=>'content.calendar_events_registrations',

        'news'=>'content.news',
        'news_regions' => 'content.news_regions',
        'news_categories' => 'content.news_categories',
        'news_photos'=>'content.news_photos',
        'news_tags'=>'content.news_tags',
        'news_mailers'=>'content.news_mailers',

        'articles'=>'content.articles',
        'articles_photos'=>'content.articles_photos',
        'articles_categories' => 'content.articles_categories',
        'articles_tags' => 'content.articles_tags',

        'articles_promo' => 'content.articles_promo',
        'articles_promo_photos' => 'content.articles_promo_photos',
        'articles_test' => 'content.articles_test',
        'articles_test_questions' => 'content.articles_test_questions',
        'articles_test_answers' => 'content.articles_test_answers',
        'articles_test_results' => 'content.articles_test_results',

        'longread'=>'content.longread',
        'longread_photos'=>'content.longread_photos',
        'longread_categories' => 'content.longread_categories',
        'longread_tags' => 'content.longread_tags',
        'longread_advert' => 'content.longread_advert',
        'longread_advert_photos' => 'content.longread_advert_photos',

        'content_types' => 'information.content_types',
        
        'content_stats_day_clicks' => 'service.content_stats_click_day',
        'content_stats_full_clicks' => 'service.content_stats_click_full',
        'content_stats_day_shows' => 'service.content_stats_show_day',
        'content_stats_full_shows' => 'service.content_stats_show_full',
        'content_stats_day_finish' => 'service.content_stats_finish_day',
        'content_stats_full_finish' => 'service.content_stats_finish_full',
        
        'bsntv'=>'content.bsntv',
        'bsntv_categories' => 'content.bsntv_categories',
        'bsntv_photos'=>'content.bsntv_photos',
        'bsntv_tags' => 'content.bsntv_tags',

        'doverie'=>'content.doverie',
        'doverie_categories' => 'content.doverie_categories',
        'doverie_photos'=>'content.doverie_photos',
        'doverie_tags' => 'content.doverie_tags',
        
        'blog'              =>  'content.blog',
        'blog_categories'   =>  'content.blog_categories',
        'blog_photos'       =>  'content.blog_photos',
        'blog_tags'         =>  'content.blog_tags',
        
        'galleries'=>'content.galleries',
        'galleries_photos'=>'content.galleries_photos',

        'opinions_predictions' => 'content.opinions',
        'opinions_expert_profiles'=>'content.opinions_expert_profiles',
        'opinions_expert_profiles_photos'=>'content.opinions_expert_profiles_photos',
        'opinions_expert_agencies'=>'content.opinions_expert_agencies',
        'opinions_expert_agencies_photos'=>'content.opinions_expert_agencies_photos',
        'opinions_expert_estate_types' => 'content.opinions_estate_types',
        'opinions_estate_types' => 'content.opinions_estate_types',

        'consults' => 'content.consults',
        'consults_sort' => 'content.consults_sort',
        'consults_answers' => 'content.consults_answers',
        'consults_answers_votings' => 'content.consults_answers_votings',
        'consults_members' => 'content.consults_members',
        'consults_categories' => 'content.consults_categories',

        'content_tags' => 'content.tags',
        'content_tags_categories' => 'content.tags_category',

        'comments' => 'content.comments',
        'comments_types' => 'information.comments_types',
        'comments_votings' => 'content.comments_votings',

        'partners_articles' => 'content.partners_articles',
        'partners_articles_categories' => 'content.partners_articles_categories',

        'guestbook' => 'content.guestbook',

        'references_docs' => 'content.references_docs',
        'references_docs_photos' => 'content.references_docs_photos',
        'references_docs_types' => 'content.references_docs_types',
        'references_docs_categories' => 'content.references_docs_categories',
        'references_docs_offices' => 'content.references_docs_offices',

        'invest' => 'content.invest',
        'invest_photos' => 'content.invest_photos',
        'invest_categories' => 'content.invest_categories',
        
        //estate
        'live'=>'estate.live',
        'live_archive'=>'estate.live_archive',
        'live_new'=>'estate.live_new',
        'live_photos'=>'estate.live_photos',
        'live_videos'=>'estate.live_videos',
        'live_videos_photos'=>'estate.live_videos_photos',
        'live_stats_show_full'=>'estate.live_stats_show_full',
        'live_stats_search_full'=>'estate.live_stats_search_full',
        'live_stats_from_search_full'=>'estate.live_stats_from_search_full',
        'build'=>'estate.build',
        'build_archive'=>'estate.build_archive',
        'build_new'=>'estate.build_new',
        'build_photos'=>'estate.build_photos',
        'build_videos'=>'estate.build_videos',
        'build_videos_photos'=>'estate.build_videos_photos',
        'build_stats_show_full'=>'estate.build_stats_show_full',
        'build_stats_search_full'=>'estate.build_stats_search_full',
        'build_stats_from_search_full'=>'estate.build_stats_from_search_full',
        'commercial'=>'estate.commercial',
        'commercial_archive'=>'estate.commercial_archive',
        'commercial_new'=>'estate.commercial_new',
        'commercial_photos'=>'estate.commercial_photos',
        'commercial_videos'=>'estate.commercial_videos',
        'commercial_videos_photos'=>'estate.commercial_videos_photos',
        'commercial_stats_show_full'=>'estate.commercial_stats_show_full',
        'commercial_stats_search_full'=>'estate.commercial_stats_search_full',
        'commercial_stats_from_search_full'=>'estate.commercial_stats_from_search_full',
        'country'=>'estate.country',
        'country_archive'=>'estate.country_archive',
        'country_new'=>'estate.country_new',
        'country_photos'=>'estate.country_photos',
        'country_videos'=>'estate.country_videos',
        'country_videos_photos'=>'estate.country_videos_photos',
        'country_stats_show_full'=>'estate.country_stats_show_full',
        'country_stats_search_full'=>'estate.country_stats_search_full',
        'country_stats_from_search_full'=>'estate.country_stats_from_search_full',
        'cottedzhnye_poselki'=>'estate.cottage',
        'cottages'=>'estate.cottage',
        'cottages_photos'=>'estate.cottage_photos',
        'business_centers' => 'estate.business_centers',
        'business_centers_photos' => 'estate.business_centers_photos',
        'business_centers_corps' => 'estate.business_centers_corps',
        'business_centers_levels' => 'estate.business_centers_levels',
        'business_centers_offices' => 'estate.business_centers_offices',
        'business_centers_offices_photos' => 'estate.business_centers_offices_photos',
        'business_centers_offices_renters' => 'information.business_centers_offices_renters',
        'zhiloy_kompleks'=>'estate.housing_estates',
        'apartments'=>'estate.housing_estates',
        'housing_estates'=>'estate.housing_estates',
        'housing_estates_photos'=>'estate.housing_estates_photos',
        'housing_estates_progresses'=>'estate.housing_estates_progresses',
        'housing_estates_progresses_photos'=>'estate.housing_estates_progresses_photos',

        'estate_tags' => 'estate.tags',
        'estate_tag_types' => 'estate.tag_types',
        'tags_live' => 'estate.tag_live',
        'tags_commercial' => 'estate.tag_commercial',
        'tags_build' => 'estate.tag_build',
        'tags_country' => 'estate.tag_country',
        'tags_foreign' => 'estate.tag_foreign',

        //information   
        'estate_types' => 'information.estate_types',
        'estate_sort' => 'information.estate_sort',
        'objects_subscriptions_periods' => 'information.objects_subscriptions_periods',
        'balcons' => 'information.balcons',
        'building_types' => 'information.building_types',
        'cottages_developers' => 'information.cottage_developers',
        'cottages_stadies' => 'information.cottage_stady',
        'cottages_signups'=>'information.cottages_signups',
        'information_country' => 'information.country',
        'infrastructure' => 'information.infrastructure',
        'infrastructure_categories' => 'information.infrastructure_categories',
        'infrastructure_subcategories' => 'information.infrastructure_subcategories',
        'infrastructure_priorities' => 'information.infrastructure_priorities',
        'directions' => 'information.directions',
        'developers' => 'information.cottages_developers',
        'districts_areas' => 'information.districts_areas', //TODO:Мише: убрать
        'district_areas' => 'information.district_areas',
        'enters' => 'information.enters',
        'geoprefixes' => 'information.geoprefixes',
        'housing_estate_classes'=>'information.housing_estate_classes',
        'housing_estate_developers'=>'information.housing_estate_developers',
        'housing_estates_signups'=>'information.housing_estates_signups',
        'housing_estates_queries'=>'information.housing_estates_queries',
        'moderate_statuses' => 'information.moderate_statuses',
        'object_type_groups' => 'information.object_type_groups',
        'tarifs'=>'information.tarifs',
        'tarifs_discounts'=>'information.tarifs_discounts',
        'type_objects_live' => 'information.type_objects_live',
        'type_objects_country' => 'information.type_objects_country',
        'type_objects_commercial' => 'information.type_objects_commercial',
        'type_objects_inter' => 'information.type_objects_inter',
        'toilets' => 'information.toilets',
        'toilets_country' => 'information.toilets_country',
        'windows' => 'information.windows',
        'floors' => 'information.floors',
        'bathrooms' => 'information.bathrooms',
        'hot_waters' => 'information.hot_waters',
        'facings' => 'information.facings',
        'decorations' => 'information.decorations',
        'gases' => 'information.gases',
        'gardens' => 'information.gardens',
        'building_progresses' => 'information.building_progresses',
        'heatings' => 'information.heatings',
        'water_supplies' => 'information.water_supplies',
        'electricities' => 'information.electricities',
        'elevators' => 'information.elevators',
        'geodata' => 'information.geodata',
        'geodata_spb_addresses' => 'information.geodata_spb_addresses',
        'districts' => 'information.districts',
        'subways' => 'information.subways',
        'subway_lines' => 'information.subway_lines',
        'streets' => 'information.streets',
        'streets_variants' => 'information.streets_variants',
        'wrong_streets' => 'information.wrong_streets',
        'build_complete' => 'information.build_complete',
        'way_types' => 'information.way_types',
        'ownerships' => 'information.ownerships',
        'rivers' => 'information.rivers',
        'construct_materials' => 'information.construct_materials',
        'roof_materials' => 'information.roof_materials',
        'developer_statuses' => 'information.developer_statuses',
        'foreign_countries' => 'information.foreign_countries',
        'foreign_countries_inter' => 'inter_site.inter_country',
        'tarifs_agencies' => 'information.tarifs_agencies',
        'agencies_packets' => 'information.agencies_packets', 
        'managers' => 'information.managers',
        'foreign_managers' => 'inter_site.inter_owners',
        'objects_statuses' => 'information.objects_statuses',
        'union_status' => 'information.cottages_u_statuses',
        'dictionary' => 'information.dictionary',
        'cost_types'=>'information.cost_types',
        'estate_complexes_external'=>'information.estate_complexes_external',
        'estate_search_params'=>'information.estate_search_params',
        'estate_files_sources'=>'information.external_files_sources',
        'weights_live'=>'information.object_weights_live',
        'weights_commercial'=>'information.object_weights_commercial',
        'weights_country'=>'information.object_weights_country',
        'weights_build'=>'information.object_weights_build',
        'weights_photos'=>'information.object_weights_photos',
        'users_fnances_transactions' => 'information.users_fnances_transactions',
        'crawlers_user_agents' => 'information.crawlers_user_agents',
        'week_days' => 'information.week_days',
        'owners_user_types' => 'information.user_types',
        'members_user_types' => 'information.user_types',
        'work_statuses' => 'information.work_statuses',
        
        //advert_objects
        'spec_offers_objects'=>'advert_objects.spec_objects',
        'spec_offers_objects_photos'=>'advert_objects.spec_objects_photos',
        'spec_offers_packets' => 'advert_objects.spec_packets',
        'spec_offers_categories' => 'advert_objects.spec_categories',
        'tgb_monthly_show_stats'=>'advert_objects.avg_show_stats_by_month',
        'tgb_daily_show_stats'=>'advert_objects.avg_show_stats_by_day',
        'tgb_monthly_click_stats'=>'advert_objects.avg_click_stats_by_month',
        'tgb_daily_click_stats'=>'advert_objects.avg_click_stats_by_day',

        'banners' => 'advert_objects.banners',
        'banners_positions' => 'information.banners_positions',
        'banners_stats_click_day'=>'advert_objects.banners_stats_click_day',
        'banners_stats_click_full'=>'advert_objects.banners_stats_click_full',
        'banners_stats_show_day'=>'advert_objects.banners_stats_show_day',
        'banners_stats_show_full'=>'advert_objects.banners_stats_show_full',

        'tgb_campaigns' => 'advert_objects.tgb_campaigns',
        'tgb_banners' => 'advert_objects.tgb_banners',
        'tgb_stats_day_clicks'=>'advert_objects.tgb_stats_click_day',
        'tgb_stats_full_clicks'=>'advert_objects.tgb_stats_click_full',
        'tgb_stats_day_shows'=>'advert_objects.tgb_stats_show_day',
        'tgb_stats_full_shows'=>'advert_objects.tgb_stats_show_full',
        'tgb_stats_popup_day' => 'advert_objects.tgb_stats_popup_day',
        'tgb_stats_popup_full' => 'advert_objects.tgb_stats_popup_full',
        'tgb_banners_credits'=>'advert_objects.tgb_banners_credits',
        'tgb_banners_credits_stats'=>'advert_objects.tgb_banners_credits_stats',
        'tgb_colors'=>'advert_objects.tgb_colors',
        'markers_stats_day_clicks'=>'advert_objects.markers_stats_click_day',

        'district_banners' => 'advert_objects.district_banners',
        'district_banners_stats_day_clicks'=>'advert_objects.district_banners_stats_click_day',
        'district_banners_stats_full_clicks'=>'advert_objects.district_banners_stats_click_full',
        'district_banners_stats_day_shows'=>'advert_objects.district_banners_stats_show_day',
        'district_banners_stats_full_shows'=>'advert_objects.district_banners_stats_show_full',

        'tgb_overlay' => 'advert_objects.tgb_overlay',
        'tgb_overlay_phones' => 'advert_objects.tgb_overlay_phones',
        'tgb_overlay_stats_day_clicks'=>'advert_objects.tgb_overlay_stats_click_day',
        'tgb_overlay_stats_full_clicks'=>'advert_objects.tgb_overlay_stats_click_full',
        'tgb_overlay_stats_day_shows'=>'advert_objects.tgb_overlay_stats_show_day',
        'tgb_overlay_stats_full_shows'=>'advert_objects.tgb_overlay_stats_show_full',
        
        'tgb_vertical' => 'advert_objects.tgb_vertical',
        'tgb_vertical_stats_day_clicks'=>'advert_objects.tgb_vertical_stats_click_day',
        'tgb_vertical_stats_full_clicks'=>'advert_objects.tgb_vertical_stats_click_full',
        'tgb_vertical_stats_day_shows'=>'advert_objects.tgb_vertical_stats_show_day',
        'tgb_vertical_stats_full_shows'=>'advert_objects.tgb_vertical_stats_show_full',
        
        'tgb_float' => 'advert_objects.tgb_float',
        'tgb_float_phones' => 'advert_objects.tgb_float_phones',
        'tgb_float_stats_day_clicks'=>'advert_objects.tgb_float_stats_click_day',
        'tgb_float_stats_full_clicks'=>'advert_objects.tgb_float_stats_click_full',
        'tgb_float_stats_day_shows'=>'advert_objects.tgb_float_stats_show_day',
        'tgb_float_stats_full_shows'=>'advert_objects.tgb_float_stats_show_full',
        
        'photoblocks'=>'advert_objects.photoblocks',
        'photoblocks_photos'=>'advert_objects.photoblocks_photos',

        'markers' => 'advert_objects.markers',
        'markers_stats_show_day' => 'advert_objects.markers_stats_show_day',
        'markers_stats_click_day'=>'advert_objects.markers_stats_click_day',
        'markers_stats_click_full'=>'advert_objects.markers_stats_click_full',
        'markers_stats_show_full'=>'advert_objects.markers_stats_show_full',

        'credit_calculator' => 'advert_objects.credit_calculator',
        'credit_calculator_percent_rules' => 'advert_objects.credit_calculator_percent_rules',
        'credit_calculator_stats_click_day'=>'advert_objects.credit_calculator_stats_click_day',
        'credit_calculator_stats_click_full'=>'advert_objects.credit_calculator_stats_click_full',
        'credit_calculator_stats_show_day'=>'advert_objects.credit_calculator_stats_show_day',
        'credit_calculator_stats_show_full'=>'advert_objects.credit_calculator_stats_show_full',

        'context_campaigns'=>'advert_objects.context_campaigns',
        'context_advertisements'=>'advert_objects.context_advertisements',
        'context_advertisements_photos'=>'advert_objects.context_advertisements_photos',
        'context_places'=>'advert_objects.context_places',
        'context_tags'=>'advert_objects.context_tags',
        'context_tags_conformity'=>'advert_objects.context_tags_conformity',
        'context_stats_click_day'=>'advert_objects.context_stats_click_day',
        'context_stats_show_day'=>'advert_objects.context_stats_show_day',
        'context_stats_click_full'=>'advert_objects.context_stats_click_full',
        'context_stats_show_full'=>'advert_objects.context_stats_show_full',
        'context_finances'=>'advert_objects.context_finances',

        //service
        'applications'=>'service.applications',
        'application_types'=>'service.application_types',
        'application_realtor_help_types'=>'service.application_realtor_help_types',
        'application_objects'=>'service.application_objects',
        'application_agencies_sale'=>'service.application_agencies_sale',
        'mortgage_applications'=>'service.mortgage_applications',
        'mortgage_applications_recievers'=>'service.mortgage_applications_recievers',
        'mortgage_application_types'=>'service.mortgage_application_types',
        'diploms'=>'service.diploms',
        'diploms_photos'=>'service.diploms_photos',
        'konkurs' => 'service.konkurs',
        'konkurs_members'=>'service.konkurs_members',
        'konkurs_members_photos'=>'service.konkurs_members_photos',
        'konkurs_members_categories' => 'service.konkurs_members_categories',
        'konkurs_votings' => 'service.konkurs_votings',

        'subscribed_users' => 'information.subscribed_users',
        'subscribed_users_stats' => 'service.subscribed_users_stats',

        'articles_graphics' => 'service.analytics_graphics',
        'articles_indexes_build' => 'service.analytics_indexes_build',
        'articles_indexes_live' => 'service.analytics_indexes_live',
        'articles_indexes_flats_sizes' => 'service.analytics_indexes_flats_sizes',
        'articles_indexes_flats_districts_size' => 'service.analytics_indexes_flats_districts_size',
        'articles_cottage_outbound' => 'service.analytics_cottage_outbound',
        'articles_cottage_settlements' => 'service.analytics_cottage_settlements',

        'country_demand' => 'service.analytics_country_demand',
        'country_demand_members' => 'service.analytics_country_demand_members',
        'cottage_demand' => 'service.analytics_cottage_demand',
        'cottage_settlements' => 'service.analytics_cottage_settlements',

        'content_partners'         =>  'service.content_partners',
        'content_partners_photos'  =>  'service.content_partners_photos',
        
        'service_receipt' => 'service.receipt',
        'system_messages' => 'service.system_messages',

        'mailers' => 'service.mailers',
        'mailers_photos' => 'service.mailers_photos',

        'news_mailer_schedule'=>'content.news_mailer_schedule',
        'news_mailer_banners'=>'service.news_mailer_banners',

        'normalspam' => 'service.spam',
        'specspam' => 'service.spec_spam',
        'specspam_users' => 'service.spec_spam_users',

        'promocodes' => 'service.promocodes',
        'promocodes_used' => 'service.promocodes_used',

        'promotions' => 'service.promotions',
        'promotions_photos' => 'service.promotions_photos',

        'billing' => 'service.advert_agencies_stats',

        'events_registration' => 'service.events_registration',
        'events_request' => 'service.events_request',

        'webinars' => 'service.webinars',
        'webinars_users' => 'service.webinars_users',
        'webinars_photos' => 'service.webinars_photos',

        'check_news_time'=>'service.check_news_time',
        'mailer_banners'=>'service.news_mailer_banners',

        'webmaster_site_urls' => 'service.webmaster_site_urls',

        'ng_check_download'=>'service.ng_check_download',

        'estate_complexes_stats_day_clicks'=>'service.estate_complexes_stats_click_day',
        'estate_complexes_stats_full_clicks'=>'service.estate_complexes_stats_click_full',
        'estate_complexes_stats_day_shows'=>'service.estate_complexes_stats_show_day',
        'estate_complexes_stats_full_shows'=>'service.estate_complexes_stats_show_full',

        'phone_clicks_day'  => 'service.phone_clicks_day',
        'phone_clicks_day_checker'  => 'service.phone_clicks_day_checker',
        'phone_clicks_full' => 'service.phone_clicks_full',

        'popunder_clicks' => 'service.popunder_clicks',
                
        'phone_prefixes'=>'service.phone_prefixes',
        
        'abuses'=>'service.abuses',
        'abuses_categories'=>'service.abuses_categories',
        
        'projects_changes'=>'service.projects_changes',
        'projects'=>'service.projects',
        
        'cabinet_stats'=>'service.cabinet_stats',
        
        'video_konkurs'=>'service.video_konkurs',
        'video_konkurs_votings'=>'service.video_konkurs_votings',
        'housing_estates_experts' => 'service.housing_estates_experts',
        'housing_estates_experts_photos' => 'service.housing_estates_experts_photos',
        'housing_estates_voting' => 'service.housing_estates_voting',
        'housing_estates_voting_params' => 'service.housing_estates_voting_params',
        
        'xml_imported_hash'=>'service.xml_imported_hash',

        'xml_parse'=>'service.xml_parse',
        'processes' => 'service.xml_processes',
        'xml_address_parse' => 'service.xml_address_parse',
        
        'addresses_to_add'=>'service.new_addresses',

        'partners_landings' => 'service.partners_landings',
        'partners_landings_photos' => 'service.partners_landings_photos',
        'partners_landings_applications' => 'service.partners_landings_applications',
                
        'housing_estates_districts_old'=>'service.housing_estates_districts',
        'housing_estates_districts'=>'service.housing_estates_districts_new',
        'news_sources'=>'service.news_sources',
        'news_stopwords'=>'service.news_stopwords',
        'news_parsing_stats'=>'service.news_parsing_stats',
        'news_parsing'=>'service.news_parsing',
        'news_parsing_photos'=>'service.news_parsing_photos',
        
        'pages_not_indexed'=>'service.pages_not_indexed',
        'pages_not_indexed_google'=>'service.pages_not_indexed_google',
        'pages_not_indexed_yandex'=>'service.pages_not_indexed_yandex',
        'pages_not_indexed_mailru'=>'service.pages_not_indexed_mailru',
        'pages_visits_google_day'=>'service.pages_visits_google_day',
        'pages_visits_google_full'=>'service.pages_visits_google_full',
        'pages_visits_yandex_day'=>'service.pages_visits_yandex_day',
        'pages_visits_yandex_full'=>'service.pages_visits_yandex_full',
        'pages_visits_mailru_day'=>'service.pages_visits_mailru_day',
        'pages_visits_mailru_full'=>'service.pages_visits_mailru_full',
        
        'short_uri'=>'service.short_uri',
        
        'estate_estimate'=>'service.estate_estimate',
        'estate_estimate_purposes'=>'service.estate_estimate_purposes',
        
        'notifications'=>'service.notifications',
        'notifications_categories'=>'service.notifications_categories',
        
        'parsing_sources'=>'service.parsing_sources',
        'parsing_schemas'=>'service.parsing_schemas',
        'parsed_stubs'=>'service.parsed_stubs',

        'users_logs'=>'service.users_logs',
        
        'yandex_xml_limits' => 'service.yandex_xml_limits',
        
        'newsletters' => 'service.newsletters',
        'newsletters_campaigns' => 'service.newsletters_campaigns',
        
        //geo
        'cities' => 'geo.net_city',

        //bsn_ng
        'ng_agencies'=>'bsn_ng.agencies_list'
        
        //sale
        , 'sale_campaigns'=>'sale.campaigns'
        , 'sale_campaigns_photos'=>'sale.campaigns_photos'
        , 'sale_offers'=>'sale.offers'
        , 'sale_offers_photos'=>'sale.offers_photos'
        , 'sale_tarifs'=>'sale.tarifs'
        , 'sale_phones'=>'sale.phones'
        , 'type_objects_sale'=>'sale.type_objects_sale'
        , 'sale_campaigns_stats_day_clicks'=>'sale.campaigns_stats_click_day'
        , 'sale_campaigns_stats_full_clicks'=>'sale.campaigns_stats_click_full',
        
        //inter
        'inter_estate'                      => 'inter.estate',
        'inter'                             => 'inter.estate',
        'inter_photos'                      => 'inter.estate_photos',
        'inter_estate_photos'               => 'inter.estate_photos',
        'inter_videos_photos'               => 'inter.estate_videos_photos',
        'inter_videos'                      => 'inter.estate_videos',
        'inter_estate_types'                => 'inter.estate_types',
        'inter_estate_types_photos'         => 'inter.estate_types_photos',
        'inter_countries'                   => 'inter.countries',
        'inter_countries_photos'            => 'inter.countries_photos',
        'inter_countries_pics_photos'       => 'inter.countries_pics_photos',
        'inter_countries_plans_photos'      => 'inter.countries_plans_photos',
        'inter_countries_flags'             => 'inter.countries',
        'inter_countries_flags_photos'      => 'inter.countries_flags_photos',
        'inter_regions'                     => 'inter.countries_regions',
        'inter_cost_types'                  => 'inter.cost_types',
        'inter_type_objects'                => 'inter.type_objects',
        'inter_type_objects_groups'         => 'inter.type_objects_groups',
        'inter_favorites'                   => 'inter.favorites',
        'inter_managers'                    => 'inter.managers',
        'inter_partners'                    => 'inter.partners',
        'inter_currencies'                  => 'inter.currencies'
    )
);
?>
