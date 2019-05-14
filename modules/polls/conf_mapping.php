<?php
return array(
    'polls' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'id_region' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        
        ,'geo_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
        )
        ,'txt_region' => array(
            'type' => TYPE_STRING,
            'allow_null' => false, 
            'allow_empty' => false, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'label' => '1) В каком населённом пункте вы проживаете?',
            'tip' => 'Начните вводить адрес объекта',
            'class' => 'autocomplete_input',
            'url' => '/geodata/regions_list/',
            'input'=>'id_region',
            'step'=>2
        )           
        ,'career' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'class' => 'career',
            'fieldtype' => 'checkbox_set',
            'values' => Config::Get('users_carrer'),
            'label' => '2) Ваш род деятельности или статус',
            'tip' => ''
        )
        ,'newsletters' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true,
            'allow_null' => true,
            'class' => 'newsletters',
            'fieldtype' => 'checkbox_set',
            'values' => Config::Get('users_newsletters'),
            'label' => '3) Какие темы почтовой рассылки интересны Вам больше всего?',
            'tip' => ''
        )

    )   
);
?>