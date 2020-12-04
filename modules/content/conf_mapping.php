<?php
return array(
    'regions' => array(
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
            'label' => 'Название',
            'tip' => 'Заголовок для региона'
        )
        ,'code' => array(
            'type' => TYPE_STRING,
            'max' => 50,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Алиас',
            'tip' => 'Алиас (для использования в url)'
        )
        ,'position' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Приоритет',
            'tip' => 'Приоритет в списке (чем меньще число, тем выше в списке)'
        )
    ),
    'categories' => array(
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
            'label' => 'Название',
            'tip' => 'Заголовок для категории'
        )
        ,'code' => array(
            'type' => TYPE_STRING,
            'max' => 50,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Алиас',
            'tip' => 'Алиас (для использования в url)'
        )
        ,'position' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Приоритет',
            'tip' => 'Приоритет в списке (чем меньще число, тем выше в списке)'
        )
    ),
    'news' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        )
        ,'author' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор новости'
        )
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Категория новости'
        )
        ,'id_region' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите регион -'),
            'label' => 'Регион',
            'tip' => 'Региональная принадлежность новости'
        )

        ,'newsletter_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Еженедельная рассылка',
            'tip' => 'Разрешение на включение новости в ежедневную рассылку'
        )
        ,'newsletter_title' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'label' => 'Заголовок ежедневной рассылки',
            'tip' => 'Использовать заголовок данной новости для ежедневной рассылки'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто', 5=>"Черновик"),
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        )
        
        ,'datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        )
        ,'paid' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        )
        ,'show_comments' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        )
        ,'views_count' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        )        
        ,'content_short' => array(
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Краткий анонс (максимум 230 символов)',
            'tip' => 'Краткое содержание новости'
        )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        )
        ,'photo_source' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Источник фото',
            'tip' => 'Сайт-источник откуда взята фотография (при ее наличии)'
        )            
        ,'tip_row_1' => array(
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        )
    ),
    'articles' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         ),
         'id_partner' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )       
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        )
        ,'author' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор новости'
        )
        ,'author_url' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Ссылка на источник автора',
            'tip' => 'Ссылка на источник (ссылка на автора)'
        )
        ,'link' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на переход',
            'tip' => 'Есkи заполнена, то переход не в новость, а по ссылке'
        )        
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Категория новости'
        )
      
        ,'yandex_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Экспорт в Яндекс',
            'tip' => 'Разрешение на экспорт новости в Яндекс'
        )
        ,'push_status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Push уведомление',
            'tip' => 'Разрешение на всплывающее оповещение'
        )
        ,'newsletter_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Еженедельная рассылка',
            'tip' => 'Разрешение на включение новости в ежедневную рассылку'
        )    
     
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто'),
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        )
        ,'datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        )        
        ,'paid' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'Да'),
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        )
        ,'show_comments' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        )     
        ,'photo_source' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Источник фото',
            'tip' => 'Сайт-источник откуда взята фотография (при ее наличии)'
        )                    
        ,'partner_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/content/articles/partners/list/',
            'input'=>'id_partner',
            'label' => 'Партнер'
        )                              
        ,'promo' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'Обычная статья',1=>'Нативная статья',3=>'Тест'),
            'label' => 'Тип статьи',
            'tip' => 'Нативная реклама'
        )
        ,'test_partner_text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'editor' => 'Promo',
            'fieldtype' => 'textarea',
            'label' => 'Партнерский текст'
        )     
        ,'test_gradient' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'С градиентом сверху основной картинки'
        )           
        ,'test_blur' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(2=>'нет',1=>'да'),
            'label' => 'Размытие основной картинки'
        )           
        ,'test_steps' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 13,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(0=>'не выбрано',1=>'елочки',2=>'монетки',3=>'всадник(Питер)',4=>'риэлтор(муж))'),
            'label' => 'Тип пагинатора'
        )           
        ,'views_count' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        )        
        ,'content_short' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Краткий анонс',
            'tip' => 'Краткое содержание новости'
        )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        )
        
        
        ,'title_promo_blocks' => array(
            'fieldtype' => 'title_row',
            'class' => 'title_promo_blocks',
            'tip' => 'Нативные карточки'
        )        
        
        ,'id_longread' => array(
            'type' => TYPE_INTEGER,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'id лонгрида',
            'tip' => ''
        )
        ,'title_test' => array(
            'fieldtype' => 'title_row',
            'class' => 'title_test',
            'tip' => 'Вопросы теста'
        )        
        
        ,'promo-add-button' => array(
            'fieldtype' => 'text+button',
            'allow_empty' => true,
            'label' => 'Добавить блок',
            'tip' => 'Добавить блок'            
        )
        
        ,'title_test_results' => array(
            'fieldtype' => 'title_row',
            'class' => 'title_test_results',
            'tip' => 'Варианты результатов теста'
        )        
        

        
        ,'title_bottom_link' => array(
            'fieldtype' => 'title_row',
            'class' => 'title_promo_link',
            'tip' => 'Последний блок',
            'step'=>2
        )        
        
        ,'title_promo_link' => array(
            'fieldtype' => 'title_row',
            'class' => 'title_promo_link',
            'tip' => 'Ссылка',
            'step'=>2
        )        
        ,'promo_link_undertext' => array(
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Текст перед ссылкой'
        )      
        ,'promo_link' => array(
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Ссылка на переход',
            'tip' => 'Синия кнопка внизу промо-статьи'            
        )
        
        ,'promo_link_text' => array(
            'fieldtype' => 'text',
            'allow_empty' => true,
            'label' => 'Текст ссылки на переход',
            'tip' => 'Текст кнопка внизу промо статьи'            
        )      
        ,'title_promo_mail_link' => array(
            'fieldtype' => 'title_row',
            'class' => 'title_promo_mail_link',
            'tip' => 'Подписка на рассылку',
            'step'=>2
        )        
        ,'promo_subscribe' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'да',2=>'нет'),
            'label' => 'Подписка на рассылку'       
        )
          
        ,'tip_row_1' => array(
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        )
    ),
    'bsntv' => array(
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
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        )
        ,'author' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор новости'
        )
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Категория новости'
        )
        ,'video_link' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'placeholder' => 'Например: jmtcH76YpOo - обозначение видео в ссылке http://youtu.be/jmtcH76YpOo',
            'label' => 'Ссылка на видео youtube',
            'tip' => 'Вставляется только текстовый идентификатор'
        )
        ,'yandex_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Экспорт в Яндекс',
            'tip' => 'Разрешение на экспорт новости в Яндекс'
        )
        ,'vkontakte_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен',3=>'уже произведен'),
            'label' => 'Экспорт во VK',
            'tip' => 'Разрешение на экспорт новости в соцсеть vk.com'
        )
        ,'push_status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Push уведомление',
            'tip' => 'Разрешение на всплывающее оповещение'
        )
        ,'newsletter_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Еженедельная рассылка',
            'tip' => 'Разрешение на включение новости в ежедневную рассылку'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто', 5=>"Черновик"),
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        )
        ,'exclusive' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Эксклюзив',
            'tip' => 'Является ли новость эксклюзивной'
        )
        ,'comment' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Комментарий',
            'tip' => 'Является ли новость комментарием'
        )
        ,'report' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Репортаж',
            'tip' => 'Является ли новость репортажем'
        )
        ,'datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        )
        ,'paid' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        )
        ,'show_comments' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        )
        ,'views_count' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        )        
        ,'content_short' => array(
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Краткий анонс (максимум 230 символов)',
            'tip' => 'Краткое содержание новости'
        )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        )
        ,'tip_row_1' => array(
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        )
    ),
    'doverie' => array(
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
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        )
        ,'author' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор новости'
        )
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Категория новости'
        )
        ,'video_link' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'placeholder' => 'Например: jmtcH76YpOo - обозначение видео в ссылке http://youtu.be/jmtcH76YpOo',
            'label' => 'Ссылка на видео youtube',
            'tip' => 'Вставляется только текстовый идентификатор'
        )
        ,'yandex_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Экспорт в Яндекс',
            'tip' => 'Разрешение на экспорт новости в Яндекс'
        )
        ,'vkontakte_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 3,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен',3=>'уже произведен'),
            'label' => 'Экспорт во VK',
            'tip' => 'Разрешение на экспорт новости в соцсеть vk.com'
        )
        ,'push_status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Push уведомление',
            'tip' => 'Разрешение на всплывающее оповещение'
        )
        ,'newsletter_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Еженедельная рассылка',
            'tip' => 'Разрешение на включение новости в ежедневную рассылку'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто', 5=>"Черновик"),
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        )
        ,'exclusive' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Эксклюзив',
            'tip' => 'Является ли новость эксклюзивной'
        )
        ,'comment' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Комментарий',
            'tip' => 'Является ли новость комментарием'
        )
        ,'report' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Репортаж',
            'tip' => 'Является ли новость репортажем'
        )
        ,'datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        )
        ,'paid' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        )
        ,'show_comments' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        )
        ,'views_count' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        )        
        ,'content_short' => array(
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Краткий анонс (максимум 230 символов)',
            'tip' => 'Краткое содержание новости'
        )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        )
        ,'tip_row_1' => array(
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        )
    ),
    'blog' => array(
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
            'label' => 'Заголовок',
            'tip' => 'Заголовок новости'
        )
        ,'id_category' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0=>'- выберите категорию -'),
            'label' => 'Категория',
            'tip' => 'Категория новости'
        )
        ,'status' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 4,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Обычно',2=>'Всегда на главной',3=>'Никогда на главной', 4=>'Скрыто', 5=>"Черновик"),
            'label' => 'Статус новости',
            'tip' => 'Параметры отображения новости'
        )
        
        ,'comment' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Комментарий',
            'tip' => 'Является ли новость комментарием'
        )
        
        ,'datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'datetime',
            'label' => 'Время и дата публикации',
            'tip' => 'Время и дата создания новости раньше которой новость не будет опубликована'
        )
        ,'paid' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Статья оплачена',
            'tip' => 'Принадлежность статьи компании, которая их размещает на спец.условиях'
        )
        ,'show_comments' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true, 
            'allow_null' => true,
            'hidden'=>true,
            'fieldtype' => 'radio',
            'values' => array(1=>'Да',2=>'Нет'),
            'label' => 'Показывать комментарии',
            'tip' => 'Показывать комментарии в новости'
        )
        ,'views_count' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true, 
            'allow_null' => true,
            'label' => 'Количество просмотров',
            'tip' => 'Количество просмотров новости'
        )        
        ,'content_short' => array(
            'type' => TYPE_STRING,
            'maxlength' => 230,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Краткий анонс (максимум 230 символов)',
            'tip' => 'Краткое содержание новости'
        )
        ,'content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Новость',
            'tip' => 'Полное содержимое новости'
        )
        ,'tip_row_1' => array(
            'fieldtype' => 'tip_row',
            'tip' => 'Для оформления таблицы добавьте для нее класс news-table в свойствах таблицы'
        )
    )    ,        
    'check_news_time' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'sent_time' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'time',
            'label' => 'Старт показа',
            'tip' => ''
        )
    ),
    'mailer_banners' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'link' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Ссылка на рекламодателя',
            'tip' => 'Ссылка на рекламодателя'
        )
        ,'published' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'показывать',2=>'не показывать'),
            'label' => 'Статус',
            'tip' => 'Отображение баннера в рассылке'
        )
        ,'name' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true, 
            'allow_null' => true
        )            
        
    )
    ,'partners' => array(
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
            'label' => 'Название'
        )
        ,'site' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Сайт (без http://, например www.bsn.ru)'
        )
    )        
);
?>