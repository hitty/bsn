<?php
return [
    'regions' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'title' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Заголовок для региона'
        ]
        ,'code' => [
            'type' => TYPE_STRING,
            'max' => 50,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Алиас',
            'tip' => 'Алиас (для использования в url]'
        ]
        ,'position' => [
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Приоритет',
            'tip' => 'Приоритет в списке (чем меньще число, тем выше в списке]'
        ]
    ],
    'categories' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'title' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Заголовок для категории'
        ]
        ,'code' => [
            'type' => TYPE_STRING,
            'max' => 50,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Алиас',
            'tip' => 'Алиас (для использования в url]'
        ]
        ,'position' => [
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Приоритет',
            'tip' => 'Приоритет в списке (чем меньще число, тем выше в списке]'
        ]
    ],
    'news' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'advert' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [2=>'Нет',1=>'Да'],
            'label' => 'Показывать баннер "Реклама"',
            'tip' => 'Платное агентство'
        ]
        ,'advert_url' => [
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на рекламодателя',
            'tip' => 'обязательно наличие http'
        ]
        ,'token' => [
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Токен',
        ]
        ,'title' => [
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        ]
        ,'author' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор новости'
        ]
        ,'id_category' => [
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [0=>'- выберите категорию -'],
            'label' => 'Категория',
            'tip' => 'Категория новости'
        ]
        ,'id_region' => [
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [0=>'- выберите регион -'],
            'label' => 'Регион',
            'tip' => 'Региональная принадлежность новости'
        ]

        ,'newsletter_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Еженедельная рассылка',
            'tip' => 'Разрешение на включение новости в ежедневную рассылку'
        ]
        ,'newsletter_title' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'да',2=>'нет'],
            'label' => 'Заголовок ежедневной рассылки',
            'tip' => 'Использовать заголовок данной новости для ежедневной рассылки'
        ]
        ,'status' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто', 5=>"Черновик"],
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        ]
        
        ,'datetime' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        ]
        ,'paid' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        ]
        ,'show_comments' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        ]
        ,'views_count' => [
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        ]        
        ,'content_short' => [
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Краткий анонс (максимум 230 символов]',
            'tip' => 'Краткое содержание новости'
        ]
        ,'content' => [
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        ]
        ,'photo_source' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Источник фото',
            'tip' => 'Сайт-источник откуда взята фотография (при ее наличии]'
        ]            
        ,'tip_row_1' => [
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        ]
    ],
    'articles' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ],
         'id_partner' => [
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        ]       
        ,'title' => [
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        ]
    ,'advert' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [2=>'Нет',1=>'Да'],
            'label' => 'Показывать баннер "Реклама"',
            'tip' => 'Платное агентство'
        ]
        ,'advert_url' => [
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
        ,'author' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор новости'
        ]
        ,'author_url' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Ссылка на источник автора',
            'tip' => 'Ссылка на источник (ссылка на автора]'
        ]
        ,'link' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на переход',
            'tip' => 'Есkи заполнена, то переход не в новость, а по ссылке'
        ]        
        ,'id_category' => [
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [0=>'- выберите категорию -'],
            'label' => 'Категория',
            'tip' => 'Категория новости'
        ]
      
        ,'yandex_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Экспорт в Яндекс',
            'tip' => 'Разрешение на экспорт новости в Яндекс'
        ]
        ,'push_status' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Push уведомление',
            'tip' => 'Разрешение на всплывающее оповещение'
        ]
        ,'newsletter_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Еженедельная рассылка',
            'tip' => 'Разрешение на включение новости в ежедневную рассылку'
        ]    
     
        ,'status' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто'],
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        ]
        ,'datetime' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        ]        
        ,'paid' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [2=>'нет',1=>'Да'],
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        ]
        ,'show_comments' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        ]     
        ,'photo_source' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Источник фото',
            'tip' => 'Сайт-источник откуда взята фотография (при ее наличии]'
        ]                    
        ,'partner_title' => [
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/content/articles/partners/list/',
            'input'=>'id_partner',
            'label' => 'Партнер'
        ]                              
        ,'promo' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [2=>'Обычная статья',1=>'Нативная статья',3=>'Тест'],
            'label' => 'Тип статьи',
            'tip' => 'Нативная реклама'
        ]
        ,'test_partner_text' => [
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'editor' => 'Promo',
            'fieldtype' => 'textarea',
            'label' => 'Партнерский текст'
        ]     
        ,'test_gradient' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [2=>'нет',1=>'да'],
            'label' => 'С градиентом сверху основной картинки'
        ]           
        ,'test_blur' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [2=>'нет',1=>'да'],
            'label' => 'Размытие основной картинки'
        ]           
        ,'test_steps' => [
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 13,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [0=>'не выбрано',1=>'елочки',2=>'монетки',3=>'всадник(Питер]',4=>'риэлтор(муж]]'],
            'label' => 'Тип пагинатора'
        ]           
        ,'views_count' => [
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        ]        
        ,'content_short' => [
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Краткий анонс',
            'tip' => 'Краткое содержание новости'
        ]
        ,'content' => [
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        ]
        
        
        ,'title_promo_blocks' => [
            'fieldtype' => 'title_row',
            'class' => 'title_promo_blocks',
            'tip' => 'Нативные карточки'
        ]        
        
        ,'id_longread' => [
            'type' => TYPE_INTEGER,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'id лонгрида',
            'tip' => ''
        ]
        ,'title_test' => [
            'fieldtype' => 'title_row',
            'class' => 'title_test',
            'tip' => 'Вопросы теста'
        ]        
        
        ,'promo-add-button' => [
            'fieldtype' => 'text+button',
            'allow_empty' => true,
            'label' => 'Добавить блок',
            'tip' => 'Добавить блок'            
        ]
        
        ,'title_test_results' => [
            'fieldtype' => 'title_row',
            'class' => 'title_test_results',
            'tip' => 'Варианты результатов теста'
        ]        
        

        
        ,'title_bottom_link' => [
            'fieldtype' => 'title_row',
            'class' => 'title_promo_link',
            'tip' => 'Последний блок',
            'step'=>2
        ]        
        
        ,'title_promo_link' => [
            'fieldtype' => 'title_row',
            'class' => 'title_promo_link',
            'tip' => 'Ссылка',
            'step'=>2
        ]        
        ,'promo_link_undertext' => [
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Текст перед ссылкой'
        ]      
        ,'promo_link' => [
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Ссылка на переход',
            'tip' => 'Синия кнопка внизу промо-статьи'            
        ]
        
        ,'promo_link_text' => [
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Текст ссылки на переход',
            'tip' => 'Текст кнопка внизу промо статьи'            
        ]      
        ,'title_promo_mail_link' => [
            'fieldtype' => 'title_row',
            'class' => 'title_promo_mail_link',
            'tip' => 'Подписка на рассылку',
            'step'=>2
        ]        
        ,'promo_subscribe' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'да',2=>'нет'],
            'label' => 'Подписка на рассылку'       
        ]
          
        ,'tip_row_1' => [
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        ]
    ],
    'bsntv' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'title' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        ]
        ,'author' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор новости'
        ]
        ,'id_category' => [
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [0=>'- выберите категорию -'],
            'label' => 'Категория',
            'tip' => 'Категория новости'
        ]
        ,'video_link' => [
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'placeholder' => 'Например: jmtcH76YpOo - обозначение видео в ссылке https://youtu.be/jmtcH76YpOo',
            'label' => 'Ссылка на видео youtube',
            'tip' => 'Вставляется только текстовый идентификатор'
        ]
        ,'yandex_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Экспорт в Яндекс',
            'tip' => 'Разрешение на экспорт новости в Яндекс'
        ]
        ,'vkontakte_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен',3=>'уже произведен'],
            'label' => 'Экспорт во VK',
            'tip' => 'Разрешение на экспорт новости в соцсеть vk.com'
        ]
        ,'push_status' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Push уведомление',
            'tip' => 'Разрешение на всплывающее оповещение'
        ]
        ,'newsletter_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Еженедельная рассылка',
            'tip' => 'Разрешение на включение новости в ежедневную рассылку'
        ]
        ,'status' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто', 5=>"Черновик"],
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        ]
        ,'exclusive' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Эксклюзив',
            'tip' => 'Является ли новость эксклюзивной'
        ]
        ,'comment' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Комментарий',
            'tip' => 'Является ли новость комментарием'
        ]
        ,'report' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Репортаж',
            'tip' => 'Является ли новость репортажем'
        ]
        ,'datetime' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        ]
        ,'paid' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        ]
        ,'show_comments' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        ]
        ,'views_count' => [
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        ]        
        ,'content_short' => [
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Краткий анонс (максимум 230 символов]',
            'tip' => 'Краткое содержание новости'
        ]
        ,'content' => [
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        ]
        ,'tip_row_1' => [
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        ]
    ],
    'doverie' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'title' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        ]
    ,'advert' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [2=>'Нет',1=>'Да'],
            'label' => 'Показывать баннер "Реклама"',
            'tip' => 'Платное агентство'
        ]
        ,'advert_url' => [
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
        ,'author' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор новости'
        ]
        ,'id_category' => [
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [0=>'- выберите категорию -'],
            'label' => 'Категория',
            'tip' => 'Категория новости'
        ]
        ,'video_link' => [
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'placeholder' => 'Например: jmtcH76YpOo - обозначение видео в ссылке https://youtu.be/jmtcH76YpOo',
            'label' => 'Ссылка на видео youtube',
            'tip' => 'Вставляется только текстовый идентификатор'
        ]
        ,'yandex_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Экспорт в Яндекс',
            'tip' => 'Разрешение на экспорт новости в Яндекс'
        ]
        ,'vkontakte_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен',3=>'уже произведен'],
            'label' => 'Экспорт во VK',
            'tip' => 'Разрешение на экспорт новости в соцсеть vk.com'
        ]
        ,'push_status' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Push уведомление',
            'tip' => 'Разрешение на всплывающее оповещение'
        ]
        ,'newsletter_feed' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'разрешен',2=>'запрещен'],
            'label' => 'Еженедельная рассылка',
            'tip' => 'Разрешение на включение новости в ежедневную рассылку'
        ]
        ,'status' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто', 5=>"Черновик"],
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        ]
        ,'exclusive' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Эксклюзив',
            'tip' => 'Является ли новость эксклюзивной'
        ]
        ,'comment' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Комментарий',
            'tip' => 'Является ли новость комментарием'
        ]
        ,'report' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Репортаж',
            'tip' => 'Является ли новость репортажем'
        ]
        ,'datetime' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        ]
        ,'paid' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        ]
        ,'show_comments' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        ]
        ,'views_count' => [
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        ]        
        ,'content_short' => [
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Краткий анонс (максимум 230 символов]',
            'tip' => 'Краткое содержание новости'
        ]
        ,'content' => [
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        ]
        ,'tip_row_1' => [
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        ]
    ],
    'blog' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'title' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        ]
    ,'advert' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [2=>'Нет',1=>'Да'],
            'label' => 'Показывать баннер "Реклама"',
            'tip' => 'Платное агентство'
        ]
        ,'advert_url' => [
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
        ,'id_category' => [
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [0=>'- выберите категорию -'],
            'label' => 'Категория',
            'tip' => 'Категория новости'
        ]
        ,'status' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто', 5=>"Черновик"],
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        ]
        
        ,'comment' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Комментарий',
            'tip' => 'Является ли новость комментарием'
        ]
        
        ,'datetime' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        ]
        ,'paid' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        ]
        ,'show_comments' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => [1=>'Да',2=>'Нет'],
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        ]
        ,'views_count' => [
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        ]        
        ,'content_short' => [
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Краткий анонс (максимум 230 символов]',
            'tip' => 'Краткое содержание новости'
        ]
        ,'content' => [
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        ]
        ,'tip_row_1' => [
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        ]
    ]    ,        
    'check_news_time' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'sent_time' => [
            'type' => TYPE_STRING,
            'fieldtype' => 'time',
            'label' => 'Старт показа',
            'tip' => ''
        ]
    ],
    'mailer_banners' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'link' => [
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Ссылка на рекламодателя',
            'tip' => 'Ссылка на рекламодателя'
        ]
        ,'published' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [1=>'показывать',2=>'не показывать'],
            'label' => 'Статус',
            'tip' => 'Отображение баннера в рассылке'
        ]
        ,'name' => [
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        ]            
        
    ]
    ,'partners' => [
         'id' => [
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ]
        ,'title' => [
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название'
        ]
        ,'site' => [
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Сайт (без https://, например www.bsn.ru]'
        ]
    ]        
];