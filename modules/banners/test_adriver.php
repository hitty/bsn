<?php
$module_template = 'test_adriver.html';

// получение данных, отправленных из формы
$post_parameters = Request::GetParameters(METHOD_POST);
$errors = [];
$GLOBALS['js_set'][] = '/modules/banners/adriver.js';
// если была отправка формы - начинаем обработку
if(!empty($post_parameters['submit'])){
    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
    // замена фотографий на главной и/или в шапке
    if(!empty($_FILES)){
        foreach ($_FILES as $fname => $data){
            if(strstr($data['type'],'jpeg')=='' && strstr($data['type'],'jpg')=='') $errors[] = 'Тип файла не JPG'; 
            if($data['size']>350000) $errors[] = 'Размер файла больше 350КБ'; 
            if(empty($errors)){
                $filename = '/'.Config::Get('img_folders/live').'/'.md5(microtime()).'.'.str_replace('image/','',$data['type']);
                move_uploaded_file($data['tmp_name'],ROOT_PATH.$filename);
                $size = getimagesize(ROOT_PATH.$filename);                                    
                if($size[0]!=1920) $errors[] = 'Ширина файла - '.$size[0].'px, а должна быть 1920px'; 
                if($size[1]>1200) $errors[] = 'Высота файла - '.$size[1].'px, а должна быть не более 1200px';
                if(!empty($errors)) unlink(ROOT_PATH.$filename);
                else Response::SetString('filename',$filename);
            }
        }
    } else $errors[] = 'Не выбран файл';
    Response::SetArray('errors',$errors);
} 
                 
?>
