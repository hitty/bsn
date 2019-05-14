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
        ,'author_url' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Ссылка на источник',
            'tip' => 'Ссылка на источник (ссылка на автора)'
        )
        ,'redirect' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'редирект',2=>'напрямую'),
            'label' => 'Прямая ссылка',
            'tip' => 'Прямая ссылка или редирект через БСН'
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
        ,'datetime' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Дата/время',
            'tip' => 'Время и время создания новости'
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
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Экспорт в vk',
            'tip' => 'Разрешение на экспорт новости в соцсеть vk.com'
        )
        ,'newsletter_feed' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => array(1=>'разрешен',2=>'запрещен'),
            'label' => 'Ежедневная рассылка',
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
            'label' => 'Статус',
            'tip' => 'Где отображается новости'
        )
        ,'content_short' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'small',
            'label' => 'Краткий анонс',
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
    )
);
?>