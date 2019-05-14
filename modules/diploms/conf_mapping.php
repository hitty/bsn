<?php
return array(
    'diploms' => array(
         'id' => array(
            'type' => TYPE_INTEGER,
            'nodisplay' => true,
            'allow_empty' => true, 
            'allow_null' => true
         )
        ,'year' => array(
            'type' => TYPE_INTEGER,
            'min' => 1999,
            'allow_empty' => false, 
            'allow_null' => false,
            'fieldtype' => 'text',
            'label' => 'Год',
            'tip' => 'Год получения диплома'
        )
    )
);
?>