<?php
return array(
    'campaigns' => array(
        'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true,
            'allow_null' => true
        )
    , 'id_user' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'hidden',
            'allow_empty' => true,
            'allow_null' => true
        )
    , 'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Название (подпись ТГБ)'
        )
    , 'agency_title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'class' => 'autocomplete_input',
            'url' => '/admin/estate/business_centers/agencies/list/',
            'input' => 'id_user',
            'label' => 'Рекламодатель'
        )

    )
, 'banners' => array(
        'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true,
            'allow_null' => true
        )
    , 'title' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'maxlength' => 45,
            'label' => 'Название (макс 45 знаков)',
            'tip' => 'Название (подпись объекта)'
        )
    , 'annotation' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'maxlength' => 45,
            'label' => 'Описание (макс 45 знаков)',
            'tip' => 'Описание под названием'
        )
    , 'slogan_1' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 45,
            'label' => 'Слоган сверху (макс 30 знаков)',
            'tip' => 'Текстовый блок на самой картинке'
        )
    , 'slogan_2' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'maxlength' => 30,
            'label' => 'Слоган снизу (макс 30 знаков)',
            'tip' => 'Текстовый блок на самой картинке'
        )

    , 'advert_url' => [
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Ссылка на рекламодателя',
            'tip' => 'обязательно наличие http'
        ]
    , 'token' => [
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Токен',
        ]
    , 'enabled' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1 => 'вкл', 2 => 'выкл'),
            'label' => 'Статус баннера',
            'tip' => 'Показывать - Не показывать'
        )
    , 'tgb_type' => array(
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1 => 'обычно', 2 => 'со всплывашкой'),
            'label' => 'Тип баннера',
            'tip' => ''
        )
    , 'id_campaign' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0 => '- выберите кампанию -'),
            'label' => 'Кампания для размещения',
            'tip' => ''
        )
    , 'id_manager' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0 => '- выберите менеджера -'),
            'label' => 'Менеджер',
            'tip' => ''
        )
    , 'date_start' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'date',
            'label' => 'Старт показа',
            'tip' => ''
        )
    , 'date_end' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'date',
            'label' => 'Окончание показа',
            'tip' => ''
        )
    , 'clicks_limit' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => true,
            'allow_null' => true,
            'label' => 'Лимит кликов',
            'tip' => 'При достижения лимита баннер в архиве'
        )
    , 'credit_clicks' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'Использовать кредитные клики'
        )
    , 'only_popunder_clicks' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1 => 'Только Реклам. Сеть', 2 => 'Без Реклам. Сети', 3 => 'Все источники'),
            'allow_empty' => true,
            'allow_null' => false,
            'label' => 'Источники'
        )

    , 'priority' => array(
            'type' => TYPE_INTEGER,
            'fieldtype' => 'text',
            'allow_empty' => false,
            'allow_null' => false,
            'max' => 100,
            'label' => 'Приоритет, % ',
            'tip' => 'Приоритет показов баннеров в кампании'
        )
    , 'direct_link' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => 'http://',
            'label' => 'Ссылка на переход ',
            'tip' => 'Ссылка от рекламных агентств'
        )
    , 'utm' => array(
            'type' => TYPE_INTEGER,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'checkbox',
            'label' => 'utm метки'
        )

    , 'utm_source' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_source',
            'tip' => 'Сайт-источник перехода где размещается баннер'
        )
    , 'utm_medium' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_medium',
            'tip' => 'Тип рекламного места (tgb, banner, calculator, ...), откуда идет переход на сайт рекламодателя'
        )
    , 'utm_campaign' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_campaign',
            'tip' => 'Название рекламодателя или рекламной кампании, латиницей'
        )

    , 'utm_content' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => '',
            'label' => 'utm_content',
            'tip' => 'Название / Обозначение баннера, латиницей'
        )

    , 'get_pixel' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => 'http://',
            'label' => 'Ссылка на счётчик (получение пикселя)',
            'tip' => 'Ссылка от рекламных агентств для снятия ими статистики'
        )

    , '_title_row_spec_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'ТГБ в разделе'
        )
    , 'show_in_estate_section' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'radio',
            'values' => array(1 => 'да', 2 => 'нет'),
            'label' => 'Показывать ТГБ в разделе',
            'tip' => 'Дублируем это ТГБ в разделы'
        )
    , 'in_estate_section' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'set',
            'false_value' => 2, 'true_value' => 1,
            'values' => array(1 => 'Жилая', 2 => 'Стройка', 3 => 'Коммерческая', 4 => 'Загородная', 5 => "ЖК", 6 => "КП", 7 => "БЦ"),
            'label' => 'Разделы для показа',
            'tip' => ''
        )
    , '_title_row_context_' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Прикрепление БСН Таргет'
        )
    , 'id_context' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'placeholder' => 'ID баннера из Таргета',
            'label' => 'Баннер(ы) из Таргета для статистики',
            'tip' => 'ID баннера(ов) из Таргета для присоединения статистики (через запятую)'
        )
    , 'context_date_start' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'date',
            'label' => 'Дата с которой присоединим статистику Таргета',
            'tip' => 'Дата в формате ГГГГ-ММ-ДД с которой добавится статистика'
        )


    , 'img_link' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true,
            'allow_null' => true
        )
    , 'img_src' => array(
            'type' => TYPE_STRING,
            'fieldtype' => 'hidden',
            'allow_empty' => true,
            'allow_null' => true
        )
    )

);
?>