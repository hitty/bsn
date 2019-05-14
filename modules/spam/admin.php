<?php
$GLOBALS['js_set'][] = '/modules/pages/ajax_actions.js';
require_once('includes/class.paginator.php');
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// собираем GET-параметры
$get_parameters = [];
$filters = [];
//фильтр для id новости продублирован в get_parameters, чтобы
//он сохранился в поле фильтра на новой странице
$filters['id'] = Request::GetInteger('f_id',METHOD_GET);
if (!empty($filters['id'])) $get_parameters['f_id']=$filters['id'];
//фильтр для названия
$filters['title'] = Request::GetString('f_title',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
//фильтр для состояния
$filters['published'] = Request::GetString('f_published',METHOD_GET);
if(!empty($filters['published'])) {
    $filters['published'] = urldecode($filters['published']);
    $get_parameters['f_published'] = $filters['published'];
}
//фильтр для типа рассылки (БСН или Дизбук)
$filters['type'] = Request::GetString('f_type',METHOD_GET);
if(!empty($filters['type'])) {
    $get_parameters['f_type'] = $filters['type'];
}

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;

// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
    
// обработка общих action-ов 
Response::SetString('spam_type',$action);
switch($action){
    /*********************************\
    |*  Рассылка                     *|
    \********************************/
    case 'normal':
        Response::SetString('spam_title','Рассылка');
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            case 'photo_delete':
                if($ajax_mode){
                    //присвоение id значения идентификатора записи и конвертирование в int 
                    $id = Convert::ToInt($this_page->page_parameters[3]);
                    //запрос переменной banner_type, и, если она равна, присвоение ее значиния 'top_banner'? иначе 'down_banner'
                    $banner = Request::GetString('banner_type', METHOD_POST); 
                    if(!empty($id) && !empty($banner)) {
                        //запрос в базу, в результате которого мы имеем: имя файла, имя папки, в которой он лежит
                        $item = $db->fetch("SELECT `".$banner."` as filename, LEFT (`".$banner."`,2) as `subfolder` FROM ".$sys_tables['normalspam']." WHERE id = ".$id);
                        if(!empty($item)){
                            //сборка полного пути к файлу+имя файла
                            $banner_img = Host::$root_path.'/'.Config::$values['img_folders']['spam_banners'].'/'.$item['subfolder'].'/'.$item['filename'];
                            //если такой файл есть - удаляем его
                            if(file_exists($banner_img)) unlink($banner_img);
                            //заносим в базу пустые значения имени удаленного файла
                            $res = $db->query("UPDATE ".$sys_tables['normalspam']." SET ".$banner." = '' WHERE id = $id");
                            //определяем выполнился запрос или провалился
                            $ajax_result['ok'] = $res;
                        } else $this_page->http_code = 404;
                    } else $this_page->http_code = 404;
                } else $this_page->http_code = 404;
                break;
            case 'add':
            case 'edit':
                $GLOBALS['js_set'][] = '/modules/spam/ajax_action.js';    
                $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                $module_template = 'admin.spam.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['normalspam']);
                    // установка action для формы
                    Response::SetString('form_parameter', 'add');
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['normalspam']." 
                                        WHERE id=?",$id);
                    // установка action для формы
                    Response::SetString('form_parameter', 'edit/'.$id);
                    if(empty($info)) Host::Redirect('/admin/service/spam/normal/add/');
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['normalspam'][$key])) $mapping['normalspam'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                
                
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    //если были указаны файлы, загружаем их
                    if(!empty($_FILES)){
                        foreach ($_FILES as $fname => $data){
                            if ($data['error']==0) {
                                //определение размера загруженного фото
                                $_folder = Host::$root_path.'/'.Config::$values['img_folders']['spam_banners'].'/';
                                $fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
                                $fileParts = pathinfo($data['name']);
                                $targetExt = $fileParts['extension'];
                                $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                                if (in_array(strtolower($targetExt),$fileTypes)) {
                                    $_subfolder = substr($_targetFile,0,2);
                                    //при отсутствии каталога он создается
                                    if (!file_exists($_folder.$_subfolder)) {
                                        mkdir($_folder.$_subfolder);
                                    }
                                    move_uploaded_file($data['tmp_name'],$_folder.$_subfolder.'/'.$_targetFile);
                                    if(!empty($mapping['normalspam'][$fname]['value']) && is_file($_folder.$mapping['normalspam'][$fname]['value'])) unlink($_folder.$mapping['normalspam'][$fname]['value']);
                                    $post_parameters[$fname] = $_targetFile;
                                    Response::SetString('img_folder', Config::$values['img_folders']['spam_banners'].'/'.$_subfolder);
                                }
                            }
                        }
                    }
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['normalspam'][$key])) $mapping['normalspam'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['normalspam']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['normalspam'][$key])) $mapping['normalspam'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['normalspam'][$key]['value'])) $info[$key] = $mapping['normalspam'][$key]['value'];
                        }
                        
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['normalspam'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['normalspam'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/service/spam/normal/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления, 
                // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetString('img_folder',Config::$values['img_folders']['spam_banners']);
                Response::SetArray('data_mapping',$mapping['normalspam']);
                //указываем подпапки, в которых лежат верхний и нижний баннеры
                if (!empty($mapping['normalspam']['up_banner']['value'])) 
                    Response::SetString('up_banner_folder',substr($mapping['normalspam']['up_banner']['value'],0,2));
                if (!empty($mapping['normalspam']['down_banner']['value'])) 
                    Response::SetString('down_banner_folder',substr($mapping['normalspam']['down_banner']['value'],0,2));
                Response::SetBoolean('not_show_submit_button',true);//чтобы после form_default не было кнопки
                
                $module_template = 'admin.spam.edit.html';
                break;
            case 'test':
                require_once('includes/class.email.php');
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $item=$db->fetch('SELECT *,
                                         LEFT (`up_banner`,2) as `subfolder_up`,
                                         LEFT (`down_banner`,2) as `subfolder_down` 
                                  FROM '.$sys_tables['normalspam'].' WHERE id=?',$id);
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                $errors=[];$res=true;
                //если была отправка формы, начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    if (!Validate::isEmail($post_parameters['email'])){
                        $errors['email']=TRUE;
                        $res=false;
                    }
                    else{
                        // отправка письма выбранной рассылки
                        // отправка кода на мыло
                        $mailer = new EMailer('mail');
                        // параметры письма
                        Response::SetString('subject',$item['subject']);
                        Response::SetString('type',$item['type']);
                        if (!empty($item['up_banner']) && strlen($item['up_banner'])>10)
                            Response::SetString('up_banner',Host::GetWebPath('/').Config::$values['img_folders']['spam_banners'].'/'.$item['subfolder_up'].'/'.$item['up_banner']);
                        if (!empty($item['down_banner']) && strlen($item['down_banner'])>10)
                            Response::SetString('down_banner',Host::GetWebPath('/').Config::$values['img_folders']['spam_banners'].'/'.$item['subfolder_down'].'/'.$item['down_banner']);
                        Response::SetString('content',$item['content']);
                        Response::SetArray('env',array('url'=>Host::GetWebPath('/'),'host'=>Host::$host));
                        // инициализация шаблонизатора
                        $eml_tpl = new Template('spam.email.html', 'cron/mailers/');
                        // формирование html-кода письма по шаблону
                        $html = $eml_tpl->Processing();
                        // перевод письма в кодировку мейлера
                        $html =  @iconv("UTF-8",  $mailer->CharSet . "//TRANSLIT", $html);
                        $mailer->Subject = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT", $item['subject']);
                        $mailer->Body = $html;
                        //
                        //file_put_contents('letter_normalspam.html',$html);
                        //
                        $mailer->AltBody = strip_tags($html);
                        $mailer->IsHTML(true);
                        $mailer->AddAddress($post_parameters['email']);
                        switch($item['type']){
                            case 2:
                                $mailer->From = 'no-reply@dizbook.com';
                                $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'Dizbook Weekly');
                                break;
                            case 3:
                                $mailer->From = 'newsletter@interestate.ru';
                                $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'INTERESTATE Зарубежная недвижимость');
                                break;
                            default: 
                                $mailer->From = 'no-reply@bsn.ru';
                                $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'BSN.ru');
                        }
                        $mailer->Send();   // попытка отправить
                    }
                }
                Response::SetArray('data_mapping',$mapping['normalspam_test']);
                Response::SetString('title',$item['title']);
                $module_template = 'admin.spam.test.html';
                Response::SetBoolean('errors', $errors); // результат сохранения
                Response::SetBoolean('saved', $res); // результат сохранения
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->query('DELETE FROM '.$sys_tables['normalspam'].' WHERE id=?',$id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
                break;
            default:
                //формирование списка
                $conditions = [];
                if(!empty($filters)){
                    if(!empty($filters['id'])) $conditions['id'] = "`id` = ".$db->real_escape_string($filters['id']);
                    if(!empty($filters['published'])) $conditions['published'] = "`published` = '".$db->real_escape_string($filters['published'])."'";
                    if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                    if(!empty($filters['type'])) $conditions['type'] = "`type` = '".$db->real_escape_string($filters['type'])."'";
                }
                $condition = implode(" AND ",$conditions);
                
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['normalspam'], 30, $condition);
                
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/service/spam/normal/'               // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }    
                
                $sql = "SELECT id, title, 
                    DATE_FORMAT(datetime,'%d.%m %k:%i') as  date,  
                    DATE_FORMAT(begin_datetime,'%d.%m %k:%i') as begin_date, 
                    DATE_FORMAT(end_datetime,'%d.%m %k:%i') as end_date,  
                    published FROM ".$sys_tables['normalspam'];
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " ORDER BY id DESC, date DESC LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
                $this_page->manageMetadata(array('title'=>'Рассылка'));
                $module_template = 'admin.spam.list.html'; 
                break;
            }
        break;
    /*********************************\
    |*  Спец-Рассылка                *|
    \********************************/    
    case 'spec':
        Response::SetString('spam_title','Спецрассылка');
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            /*********************************\
            |*  Адреса спец-рассылки         *|
            \********************************/     
            case 'photo_delete':
                if($ajax_mode){
                    //присвоение id значения идентификатора записи и конвертирование в int 
                    $id = Convert::ToInt($this_page->page_parameters[3]);
                    //запрос переменной banner_type, и, если она равна, присвоение ее значиния 'top_banner'? иначе 'down_banner'
                    $banner = Request::GetString('banner_type', METHOD_POST); 
                    if(!empty($id) && !empty($banner)) {
                        //запрос в базу, в результате которого мы имеем: имя файла, имя папки, в которой он лежит
                        $item = $db->fetch("SELECT `".$banner."` as filename, LEFT (`".$banner."`,2) as `subfolder` FROM ".$sys_tables['specspam']." WHERE id = ".$id);
                        if(!empty($item)){
                            //сборка полного пути к файлу+имя файла
                            $banner_img = Host::$root_path.'/'.Config::$values['img_folders']['spam_banners'].'/'.$item['subfolder'].'/'.$item['filename'];
                            //если такой файл есть - удаляем его
                            if(file_exists($banner_img)) unlink($banner_img);
                            //заносим в базу пустые значения имени удаленного файла
                            $res = $db->query("UPDATE ".$sys_tables['specspam']." SET ".$banner." = '' WHERE id = $id");
                            //определяем выполнился запрос или провалился
                            $ajax_result['ok'] = $res;
                        } else $this_page->http_code = 404;
                    } else $this_page->http_code = 404;
                } else $this_page->http_code = 404;
                break;    
            case 'emails':
                $GLOBALS['css_set'][]='/modules/spam/spam.css';    
                $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                $list=$db->fetchall('SELECT email FROM '.$sys_tables['specspam_users']);
                $mapping['spec_emails']['emails']['value']='';
                foreach($list as $key=>$field){
                    $mapping['spec_emails']['emails']['value'].= $field['email']."\r\n";
                }        
                
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['spec_emails']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['spec_emails'][$key])) $mapping['spec_emails'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        $emails = str_replace(" ","\r\n",$post_parameters['emails']);
                        $list=explode("\r\n",preg_replace('/(\r\n\s)$/','',preg_replace('/^(\r\n\s)/','',$emails)));
                        // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                        
                        // сохранение в БД
                        $db->query('TRUNCATE '.$sys_tables['specspam_users']);
                        $query='INSERT INTO '.$sys_tables['specspam_users'].' (email) VALUES ';
                        $emails = array('valid'=>[],'invalid'=>[]);//список email
                        $emails_string = ''; //email списком
                        foreach($list as $item){
                            $item = trim($item,"., ,\,,?");
                            if (Validate::isEmail($item)){
                                $emails['valid'][]="('".$db->real_escape_string($item)."')";
                                $emails_string .= $item."\r\n";
                            }
                            else{
                                //собираем неправильные email
                                if (!empty($item)) $emails['invalid'][]=$item;
                            }
                        }
                        $res = $db->query("INSERT INTO ".$sys_tables['specspam_users']." (email) VALUES ".implode(', ',$emails['valid']));
                        
                        //если есть неправильные email, будем выводить их, если нет, список сохраненных email
                        if (!empty($emails['invalid']))  Response::SetArray('emails_invalid',$emails['invalid']);
                        $mapping['spec_emails']['emails']['value']=$emails_string;
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                Response::SetArray('data_mapping',$mapping['spec_emails']);
                $module_template='admin.spec_email.list.html';
                break;
            case 'add':
            case 'edit': 
                $GLOBALS['js_set'][] = '/modules/spam/ajax_action.js';   
                $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
                $module_template = 'admin.spam.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['specspam']);
                    // установка action для формы
                    Response::SetString('form_parameter', 'add');
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['specspam']." 
                                        WHERE id=?",$id);
                    // установка action для формы
                    Response::SetString('form_parameter', 'edit/'.$id);
                    if(empty($info)) Host::Redirect('/admin/service/spam/spec/add/');    
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['normalspam'][$key])) $mapping['normalspam'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                
                
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    //если были указаны файлы, загружаем их
                    if(!empty($_FILES)){
                        foreach ($_FILES as $fname => $data){
                            if ($data['error']==0) {
                                //определение размера загруженного фото
                                $_folder = Host::$root_path.'/'.Config::$values['img_folders']['spam_banners'].'/';
                                $fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
                                $fileParts = pathinfo($data['name']);
                                $targetExt = $fileParts['extension'];
                                $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                                if (in_array(strtolower($targetExt),$fileTypes)) {
                                    $_subfolder = substr($_targetFile,0,2);
                                    //при отсутствии каталога он создается
                                    if (!file_exists($_folder.$_subfolder)) {
                                        mkdir($_folder.$_subfolder);
                                    }
                                    move_uploaded_file($data['tmp_name'],$_folder.$_subfolder.'/'.$_targetFile);
                                    if(!empty($mapping['normalspam'][$fname]['value']) && is_file($_folder.$mapping['normalspam'][$fname]['value'])) unlink($_folder.$mapping['normalspam'][$fname]['value']);
                                    $post_parameters[$fname] = $_targetFile;
                                    Response::SetString('img_folder', Config::$values['img_folders']['spam_banners'].'/'.$_subfolder);
                                }
                            }
                        }
                    }
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['normalspam'][$key])) $mapping['normalspam'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['normalspam']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['normalspam'][$key])) $mapping['normalspam'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['normalspam'][$key]['value'])) $info[$key] = $mapping['normalspam'][$key]['value'];
                        }
                        
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['specspam'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['specspam'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/service/spam/spec/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления, 
                // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetString('img_folder',Config::$values['img_folders']['spam_banners']);
                Response::SetArray('data_mapping',$mapping['normalspam']);
                //указываем подпапки, в которых лежат верхний и нижний баннеры
                if (!empty($mapping['normalspam']['up_banner']['value'])) 
                    Response::SetString('up_banner_folder',substr($mapping['normalspam']['up_banner']['value'],0,2));
                if (!empty($mapping['normalspam']['down_banner']['value'])) 
                    Response::SetString('down_banner_folder',substr($mapping['normalspam']['down_banner']['value'],0,2));
                Response::SetBoolean('not_show_submit_button',true);//чтобы после form_default не было кнопки
                
                $module_template = 'admin.spam.edit.html';
                break;
            case 'test':
                require_once('includes/class.email.php');
                $id=empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $item=$db->fetch('SELECT *,
                                         LEFT (`up_banner`,2) as `subfolder_up`,
                                         LEFT (`down_banner`,2) as `subfolder_down` 
                                  FROM '.$sys_tables['specspam'].' WHERE id=?',$id);
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                $errors=[];$res=true;
                
                //если была отправка формы, начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    if (!Validate::isEmail($post_parameters['email'])){
                        $errors['email']=TRUE;
                        $res=false;
                    }
                    else{
                        // отправка письма выбранной рассылки
                        // отправка кода на мыло
                        $mailer = new EMailer('mail');
                        // параметры письма
                        Response::SetString('subject',$item['subject']);
                        Response::SetString('type',$item['type']);
                        if (!empty($item['up_banner']) && strlen($item['up_banner'])>10)
                            Response::SetString('up_banner',Host::GetWebPath('/').Config::$values['img_folders']['spam_banners'].'/'.$item['subfolder_up'].'/'.$item['up_banner']);
                        if (!empty($item['down_banner'])  && strlen($item['down_banner'])>10)
                            Response::SetString('down_banner',Host::GetWebPath('/').Config::$values['img_folders']['spam_banners'].'/'.$item['subfolder_down'].'/'.$item['down_banner']);
                        Response::SetString('content',$item['content']);
                        Response::SetArray('env',array('url'=>Host::GetWebPath('/'),'host'=>Host::$host));
                        // инициализация шаблонизатора
                        $eml_tpl = new Template('spam.email.html', 'cron/mailers/');
                        // формирование html-кода письма по шаблону
                        $html = $eml_tpl->Processing();
                        // перевод письма в кодировку мейлера
                        $html = iconv('UTF-8', $mailer->CharSet. "//TRANSLIT", $html);
                        $mailer->Subject = iconv('UTF-8', $mailer->CharSet. "//TRANSLIT", $item['subject']);
                        $mailer->Body = $html;
                        //
                        //file_put_contents('letter_specspam.html',$html);
                        //
                        $mailer->AltBody = strip_tags($html);
                        $mailer->IsHTML(true);
                        $mailer->AddAddress($post_parameters['email']);
                        switch($item['type']){
                            case 2:
                                $mailer->From = 'no-reply@dizbook.com';
                                $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'Dizbook Weekly');
                                break;
                            case 3:
                                $mailer->From = 'newsletter@interestate.ru';
                                $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'INTERESTATE Зарубежная недвижимость');
                                break;
                            default: 
                                $mailer->From = 'no-reply@bsn.ru';
                                $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'BSN.ru');
                        }
                        $mailer->Send();   // попытка отправить
                    }
                }
                Response::SetArray('data_mapping',$mapping['normalspam_test']);
                Response::SetString('title',$item['title']);
                $module_template = 'admin.spam.test.html';
                Response::SetBoolean('errors', $errors); // результат сохранения
                Response::SetBoolean('saved', $res); // результат сохранения
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->query('DELETE FROM '.$sys_tables['specspam'].' WHERE id=?',$id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
                break;
            default:
                //формирование списка
                $conditions = [];
                if(!empty($filters)){
                    if(!empty($filters['id'])) $conditions['id'] = "`id` = ".$db->real_escape_string($filters['id']);
                    if(!empty($filters['published'])) $conditions['published'] = "`published` = '".$db->real_escape_string($filters['published'])."'";
                    if(!empty($filters['title'])) $conditions['title'] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                    if(!empty($filters['type'])) $conditions['type'] = "`type` = '".$db->real_escape_string($filters['type'])."'";
                }
                $condition = implode(" AND ",$conditions);
                
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['normalspam'], 30, $condition);
                
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/service/spam/spec/'                 // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }    
                
                $sql = "SELECT id, title, 
                        DATE_FORMAT(datetime,'%d.%m %k:%i') as  date,  
                        DATE_FORMAT(begin_datetime,'%d.%m %k:%i') as begin_date, 
                        DATE_FORMAT(end_datetime,'%d.%m %k:%i') as end_date,  
                        published FROM ".$sys_tables['specspam'];
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " ORDER BY id DESC LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
                $this_page->manageMetadata(array('title'=>'Рассылка'));
                $module_template = 'admin.spam.list.html'; 
                break;
        }
        break;
    default:
        $module_template = 'admin.spam.html';
        break;
}
// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk.'='.$gv;
Response::SetString('get_string', implode('&',$get_parameters));
?>