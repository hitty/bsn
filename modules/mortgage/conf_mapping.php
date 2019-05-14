<?php
return array(
    'mortgage_applications' => array(
        'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
         ,'title_row_terms' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Условия кредитования'
         )
         ,'id_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'min'=>1,
            'max'=>5,
            'values' => [],
            'label' => 'Тип заявки',
            'tip' => 'Тип заявки'
         )
         ,'status' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'radio',
            'min'=>1,
            'max'=>5,
            'values' => array(1=>'прошла модерацию',2=>'не прошла модерацию',3=>'на модерации',4=>'в архиве'),
            'label' => 'Статус заявки',
            'tip' => 'Статус заявки'
         )
         ,'banks_selected' => array(
            'type' => TYPE_STRING,
            'false_value' => 2, 'true_value' => 1,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'rich_checkbox_set',
            'label' => 'Желаемая кредитная организация'
         )
         ,'mortgage_years' => array(
            'type' => TYPE_INTEGER,
            'min' => 0,
            'max' => 99,
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Срок кредитования, лет'
         )
         ,'estate_price' => array(
             'type' => TYPE_INTEGER,
             'allow_empty' => false, 
             'allow_null' => false,
             'fieldtype' => 'text',
             'class' => 'cost',
             'label' => 'Стоимость жилья, руб'
         )         
         ,'first_payment' => array(
             'type' => TYPE_INTEGER,
             'allow_empty' => false, 
             'allow_null' => false,
             'fieldtype' => 'text',
             'class' => 'cost',
             'label' => 'Первоначальный взнос, руб'
         )
         
         ,'estate_id' => array(
             'type' => TYPE_INTEGER,
             'allow_empty' => true, 
             'allow_null' => false,
             'fieldtype' => 'text',
             'label' => 'ID объявления'
         )
         
         ,'estate_type' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => false,
            'allow_null' => false,
            'fieldtype' => 'select',
            'min'=>1,
            'max'=>5,
            'values' => array(1 => 'Жилая',2 => 'Стройка',3 => 'Коммерческая',4 => 'Загородная'),
            'label' => 'Тип недвижимости',
            'tip' => 'Тип недвижимости'
         )
         
         ,'income_value' => array(
             'type' => TYPE_INTEGER,
             'allow_empty' => false, 
             'allow_null' => false,
             'fieldtype' => 'text',
             'class' => 'cost',
             'label' => 'Уровень дохода в месяц, руб'
         )
         
        
         ,'title_row_personal' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Персональные данные'
         )
                           
         ,'id_geodata' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'fieldtype' => 'hidden',
            'step'=>2
         )
         ,'registration' => array(
            'type' => TYPE_STRING,
            'allow_null' => false, 
            'allow_empty' => true, 
            'fieldtype' => 'text',
            'autocomplete'=>'off',
            'class' => 'typewatch registration',
            'tip' => 'Начните вводить адрес объекта',
            'label' => 'Место регистрации'
         )
         ,'patronymic' => array(
            'type' => TYPE_STRING,
            'max' => 32,
            'allow_empty' => false,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Отчество'
         )         
         ,'lastname' => array(
            'type' => TYPE_STRING,
            'max' => 32,
            'allow_empty' => false,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Фамилия'
         )
       
         
         ,'birthdate' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Дата рождения ДД.ММ.ГГ'
         )
         
         ,'name' => array(
            'type' => TYPE_STRING,
            'max' => 32,
            'allow_empty' => false,
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Имя'
         )
                  
         ,'registration_general' => array(
            'type' => TYPE_INTEGER,
            'values' => array(1 => 'Санкт-Петербург', 2 => 'Ленинградская область',3 => 'другое'),
            'allow_empty' => true,
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Постоянная регистрация'
         )
         
         ,'is_married' => array(
            'type' => TYPE_INTEGER,
            'values' => array(0=>'не указано',1=>'Женат/Замужем',2=>'Холост/Не замужем'),
            'allow_empty' => true, 
            'allow_null' => false,
            'fieldtype' => 'select',
            'label' => 'Семейное положение'
         )

         ,'title_row_contacts' => array(
            'fieldtype' => 'title_row',
            'tip' => 'Контактные данные'
         )         
         ,'phone' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'phone',
            'label' => 'Телефон',
         )
         ,'email' => array(
            'type' => TYPE_STRING,
            'max' => 255,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'email',
            'label' => 'Электронная почта',
         )
         ,'notes' => array(
            'type' => TYPE_STRING,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'textarea',
            'label' => 'Дополнительная информация'
         )
    )
);
?>