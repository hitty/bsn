<?php
return array(
    'adriver_banners' => array(
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
			'label' => 'Название',
			'tip' => ''
		)
        ,'external_id' => array(
            'type' => TYPE_INTEGER,
            'allow_empty' => true, 
            'allow_null' => true,
            'fieldtype' => 'text',
            'label' => 'Внешний ID',
            'tip' => 'Внешний ID рекламной кампании в Adriverе'
        )
		
        ,'url' => array(
            'type' => TYPE_STRING,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Ссылка',
            'tip' => ''
        )			
						
	)
);
?>