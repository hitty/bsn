<?php
return array(
    'opinions_predictions' => array(
        'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true,
            'allow_null' => true
        )
    , 'type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(1 => 'мнения', 2 => 'прогнозы', 3 => 'интервью'),
            'label' => 'Тип записи',
            'tip' => 'Мнение или прогноз'
        )
    , 'id_expert' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0 => '- выберите эксперта -'),
            'label' => 'Имя эксперта',
            'tip' => ''
        )
    , 'id_estate_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0 => '- выберите тип -'),
            'label' => 'Тип недвижимости',
            'tip' => ''
        )
    , 'author' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Автор',
            'tip' => 'Автор'
        )
    , 'annotation' => array(
            'type' => TYPE_STRING,
            'max' => 100,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'label' => 'Аннотация',
            'tip' => 'Короткий текст, не более 100 знаков'
        )
    , 'text' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Текст',
            'tip' => 'Полное содержимое мнения/прогноза'
        )
    , 'date' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'date',
            'label' => 'Дата размещения',
            'tip' => 'Дата размещения мнения/прогноза'
        )
    , 'advert' => [
            'type' => TYPE_INTEGER,
            'min' => 1,
            'max' => 2,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'values' => [2 => 'Нет', 1 => 'Да'],
            'label' => 'Показывать баннер "Реклама"',
            'tip' => 'Платное агентство'
        ]
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
    ),
    'experts' => array(
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
            'label' => 'ФИО',
            'tip' => 'ФИО эксперта'
        )
    , 'company' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Должность',
            'tip' => 'Должность эксперта'
        )
    , 'id_agency' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => array(0 => '- выберите агентство -'),
            'label' => 'Агентство',
            'tip' => 'Выберите агентство для эксперта'
        )
    , 'email' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'E-mail',
            'tip' => ''
        )
    , 'bio' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'editor' => 'big',
            'label' => 'Биография',
            'tip' => 'Краткая биография'
        )
    )
, 'agencies' => array(
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
            'label' => 'Название агентства',
            'tip' => 'Полное название агентства'
        )
    )
, 'estate_types' => array(
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
            'label' => 'Название недвижимости',
            'tip' => 'Название типа недвижимости'
        )
    )
);
?>