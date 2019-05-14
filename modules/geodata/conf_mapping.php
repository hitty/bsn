<?php
return array(
    'geodata' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
        ,'offname' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Название'
        )
        ,'shortname' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Краткое наименование типа объекта',
            'tip' => 'Краткое наименование типа объекта (обл,г,рег и т.д.)'
        )
        ,'levels' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'fieldtype' => 'select',
            'values' => [],
            'label' => 'Тип объекта',
            'tip' => 'Тип объекта (г,респ,рег,обл и т.д.)'
        )
    ),'districts' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
        ,'parent_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [],
            'label' => 'Город',
            'tip' => 'Субъект с городскими районами'
		)
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Название района'
        )
    ),'subways' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
        ,'parent_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [],
            'label' => 'Город',
            'tip' => 'Субъект с городскими районами'
		)
        ,'id_subway_line' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [],
            'label' => 'Ветка метро',
            'tip' => 'Список веток метрополитена в городе'
		)
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название',
            'tip' => 'Название метро'
        )
    ),'subway_lines' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
        ,'parent_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'values' => [],
            'label' => 'Город',
            'tip' => 'Субъект с городскими районами'
		)
        ,'color' => array(
            'type' => TYPE_STRING,
            'max' => 7,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Цвет линии метро',
            'tip' => 'Выберите цвет'
        )
        ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название линии',
            'tip' => 'Название линии метро'
        )
	),'wrong_streets' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
         ,'id_street' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
         ,'title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название линии',
            'tip' => 'Название линии метро'
        )
         ,'true_title' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Название линии',
            'tip' => 'Название линии метро'
        )
    ),'geo_object' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => false, 
            'allow_null' => false
         )
         ,'addr_source' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'textarea',
            'disabled' => 'true',
            'label' => 'Источник',
            'tip' => 'Источник'
        )
         ,'id_region' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Регион',
            'tip' => '78 - СПБ, 47 - ЛО',
            'values' => array(0 => '- не выбрана -',78=>'СПБ',47=>'ЛО')
        )
        ,'id_area' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )
         ,'txt_area' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'район области',
            'tip' => 'район области',
            'class' => 'typewatch area'
        )
         ,'id_city' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )
        ,'txt_city' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Город',
            'tip' => 'Город',
            'class' => 'typewatch city'
        )
         ,'id_place' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )
        ,'txt_place' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Деревня/поселок',
            'tip' => 'Деревня/поселок',
            'class' => 'typewatch place'
        )
         ,'id_district' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )
        ,'txt_district' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => 'Район города',
            'tip' => 'Район города',
            'class' => 'typewatch district'
        )
         ,'offname' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Распознанное название',
            'tip' => 'Название'
        )
        ,'shortname' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Распознанный тип',
            'tip' => 'Тип'
        )
        ,'id_geodata' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden'
        )
        ,'txt_street' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => '<b>Улица из нашей базы</b> <br> (объект прикрепится к указанной улице)',
            'tip' => 'Улица из нашей базы',
            'class' => 'typewatch street'
        )
    )
)
?>