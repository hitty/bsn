<?php
return array(
    'applications' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'min'=>1,
            'max'=>8,
            'values' => array(2=>'новая',4=>'на модерации',5=>'отклонена',1=>'обработана',3=>'в работе',6=>'ожидает публикации',8=>'в архиве'),
            'label' => 'Статус заявки',
            'tip' => 'Статус заявки'
         )
         ,'visible_to_all' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'min'=>1,
            'max'=>2,
            'values' => array(1=>'всем',2=>'только клиенту',3=>'только платным клиентам'),
            'label' => 'Видимость заявки',
            'tip' => 'Видимость заявки'
         )
         ,'name' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Имя заявщика',
            'tip' => 'Имя заявщика'
         )
         ,'phone' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Телефон',
            'tip' => 'Телефон'
         )
         ,'email' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Email',
            'tip' => 'Email'
         )
         ,'id_user_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'min'=>1,
            'max'=>8,
            'label' => 'Кто вы',
            'tip' => ''
         )
         ,'id_work_status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'min'=>1,
            'max'=>5,
            'label' => 'Кто вы',
            'tip' => ''
         )
         ,'user_comment' => array(
            'type' => TYPE_STRING,
            'max' => 1024,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Комментарий',
            'tip' => 'Комментарий'
         )      
    )
);
?>