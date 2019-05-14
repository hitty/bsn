<?php
$GLOBALS['js_set'][] = '/modules/access/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.common.php');
 require_once('includes/class.estate.statistics.php');
$this_page->manageMetadata(array('title'=>'Доступ'));

// основной шаблон модуля (шаблон по умолчанию)
$module_template = 'admin.info.html';
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// собираем GET-параметры
$get_parameters = [];
//фильтр по логину или названию агентства
$filter = Request::GetString('filter',METHOD_GET);
if($filter!==null) {
    $filter = urldecode($filter);
    $get_parameters['filter'] = $filter;
}
//фильтр по id пользователя
$filter_id = Request::GetInteger('filter_id',METHOD_GET);
if($filter_id!==null) {
    $filter_id = urldecode($filter_id);
    $get_parameters['filter_id'] = $filter_id;
}
//фильтр по id агентства
$filter_id_agency = Request::GetInteger('filter_id_agency',METHOD_GET);
if($filter_id_agency!==null) {
    $filter_id_agency = urldecode($filter_id_agency);
    $get_parameters['filter_id_agency'] = $filter_id_agency;
}
//фильтр по группе
$filter_group = Request::GetString('filter_group',METHOD_GET);
if(!empty($filter_group)) {
    $filter_group = urldecode($filter_group);
    $get_parameters['filter_group'] = $filter_group;
}
//фильтр по блокировке
$filter_blocked = Request::GetString('filter_blocked',METHOD_GET);
if(!empty($filter_blocked)) {
    $filter_blocked = urldecode($filter_blocked);
    $get_parameters['filter_blocked'] = $filter_blocked;
}
//фильтр по тарифу
$filter_tarif = Request::GetString('filter_tarif',METHOD_GET);
if(!empty($filter_tarif)) {
    $filter_tarif = urldecode($filter_tarif);
    $get_parameters['filter_tarif'] = $filter_tarif;
}


//фильтр по телефону
$filter_phone = Request::GetString('filter_phone',METHOD_GET);
if(!empty($filter_phone)) {
    $filter_phone = urldecode($filter_phone);
    $get_parameters['filter_phone'] = $filter_phone;
}
//фильтр по почте
$filter_email = Request::GetString('filter_email',METHOD_GET);
if(!empty($filter_email)) {
    $filter_email = urldecode($filter_email);
    $get_parameters['filter_email'] = $filter_email;
}
//фильтр по "подставному" телефону
$filter_advert_phone = Request::GetString('filter_advert_phone',METHOD_GET);
if($filter_advert_phone!==null) {
    $filter_advert_phone = urldecode($filter_advert_phone);
    $get_parameters['filter_advert_phone'] = $filter_advert_phone;
}
//фильтр по содержимому
$filter_content = Request::GetString('f_content',METHOD_GET);
if(!empty($filter_content)) {
    $filter_content = urldecode($filter_content);
    $get_parameters['f_content'] = $filter_content;
}
//фильтр по содержимому
$filter_published = Request::GetString('f_published',METHOD_GET);
if(!empty($filter_published)) {
    $filter_published = urldecode($filter_published);
    $get_parameters['f_published'] = $filter_published;
}  

//фильтр по менеджеру
$filter_manager = Request::GetString('f_manager',METHOD_GET);
if(!empty($filter_manager)) {
    $filter_manager = urldecode($filter_manager);
    $get_parameters['f_manager'] = $filter_manager;
}  
//сортировка по алфавиту
$filter_sortby = Request::GetString('f_sortby',METHOD_GET);
if(!empty($filter_sortby)) {
    $filter_sortby = urldecode($filter_sortby);
    $get_parameters['f_sortby'] = $filter_sortby;
}  
//фильтр по статусу
$filter_status = Request::GetString('f_status',METHOD_GET);
if(!empty($filter_status)) {
    $filter_status = urldecode($filter_status);
    $get_parameters['f_status'] = $filter_status;
}  

//фильтр по виду деятельности(для агентств)
$filter_activity = Request::GetString('f_activity',METHOD_GET);
if(!empty($filter_activity)) {
    $filter_activity = urldecode($filter_activity);
    $get_parameters['f_activity'] = $filter_activity;
}  

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
Response::SetString('img_folder', Config::$values['img_folders']['agencies']);
// все action-ы выполняются только для users, agencies и groups
if(!empty($this_page->page_parameters[1])){
    if(in_array($this_page->page_parameters[1],array('users','agencies','users_groups'))){
        // рабочая таблица
        $worktable = $this_page->page_parameters[1];
        // добавление заголовков в страницу
        if($worktable=='users') $this_page->manageMetadata(array('title'=>'Пользователи'));
        elseif($worktable=='agencies') $this_page->manageMetadata(array('title'=>'Агентства'));
        else $this_page->manageMetadata(array('title'=>'Группы'));
        // флажки результатов
        $results = [];
        // обработка action-ов
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            //системные сообщения для пользователей
            case 'system_messages':
                $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                switch($action){
                case 'add':
                case 'edit':
                    $module_template = 'admin.system_messages.edit.html';
                    $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                    if($action=='add'){
                        // создание болванки новой записи
                        $info = $db->prepareNewRecord($sys_tables['system_messages']);
                        // установка action для формы
                        Response::SetString('form_parameter', 'add');
                    } else {
                        // получение данных из БД
                        $info = $db->fetch("SELECT *
                                            FROM ".$sys_tables['system_messages']." 
                                            WHERE id=?",$id);
                        // установка action для формы
                        Response::SetString('form_parameter', 'edit/'.$id);
                        if(empty($info)) Host::Redirect('/admin/access/users/system_messages/add/');
                    }
                    // перенос дефолтных (считанных из базы) значений в мэппинг формы
                    foreach($info as $key=>$field){
                        if(!empty($mapping['system_messages'][$key])) $mapping['system_messages'][$key]['value'] = $info[$key];
                    }
                    // получение данных, отправленных из формы
                    $post_parameters = Request::GetParameters(METHOD_POST);
                    
                    
                    // если была отправка формы - начинаем обработку
                    if(!empty($post_parameters['submit'])){
                        Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                       
                        // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                        foreach($post_parameters as $key=>$field){
                            if(!empty($mapping['system_messages'][$key])) $mapping['system_messages'][$key]['value'] = trim($post_parameters[$key]);
                        }
                        // проверка значений из формы
                        $errors = Validate::validateParams($post_parameters,$mapping['system_messages']);
                        // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                        foreach($errors as $key=>$value){
                            if(!empty($mapping['system_messages'][$key])) $mapping['system_messages'][$key]['error'] = $value;
                        }
                        // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                        if(empty($errors)) {
                            // подготовка всех значений для сохранения
                            foreach($info as $key=>$field){
                                if(isset($mapping['system_messages'][$key]['value'])) $info[$key] = $mapping['system_messages'][$key]['value'];
                            }
                            $info['content'] = preg_replace("/\n/","<br>",$info['content']);
                            $info['content'] = preg_replace("/<br><br>/","<br>",$info['content']);

                            // сохранение в БД
                            if($action=='edit'){
                                $res = $db->updateFromArray($sys_tables['system_messages'], $info, 'id');
                            } else {
                                $res = $db->insertFromArray($sys_tables['system_messages'], $info, 'id');
                                if(!empty($res)){
                                    $new_id = $db->insert_id;
                                    // редирект на редактирование свеженькой страницы
                                    if(!empty($res)) {
                                        header('Location: '.Host::getWebPath('/admin/access/users/system_messages/edit/'.$new_id.'/'));
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
                    Response::SetArray('data_mapping',$mapping['system_messages']);
                    Response::SetBoolean('not_show_submit_button',true);//чтобы после form_default не было кнопки
                    $module_template = 'admin.system_messages.edit.html';
                    break;
                case 'test':
                    $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                    $item=$db->fetch('SELECT *  FROM '.$sys_tables['system_messages'].' WHERE id=?',$id);
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
                            // отправка тестового сообщения
                            
                        }
                    }
                    Response::SetArray('data_mapping',$mapping['system_messages_test']);
                    Response::SetString('title',$item['title']);
                    $module_template = 'admin.system_messages.test.html';
                    Response::SetBoolean('errors', $errors); // результат сохранения
                    Response::SetBoolean('saved', $res); // результат сохранения
                    break;
                case 'del':
                    $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                    $res = $db->query('DELETE FROM '.$sys_tables['system_messages'].' WHERE id=?',$id);
                    $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                    if($ajax_mode){
                        $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                        break;
                    }
                    break;
                default:
                    //формирование списка
                    $conditions = [];
                    if(!empty($get_parameters)){
                        if(!empty($filters['id'])) $conditions['id'] = "`id` = ".$db->real_escape_string($filters['id']);
                        if(!empty($filter_published)) $conditions['f_published'] = "`published` = '".$db->real_escape_string($filter_published)."'";
                        if(!empty($filter_content)) $conditions['f_content'] = "`content` LIKE '%".$db->real_escape_string($filter_content)."%'";
                    }
                    $condition = implode(" AND ",$conditions);
                    
                    // создаем пагинатор для списка
                    $paginator = new Paginator($sys_tables['system_messages'], 30, $condition);
                    
                    // get-параметры для ссылок пагинатора
                    $get_in_paginator = [];
                    foreach($get_parameters as $gk=>$gv){
                        if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                    }
                    // ссылка пагинатора
                    $paginator->link_prefix = '/admin/access/users/system_messages/'               // модуль
                                              ."/?"                                       // конечный слеш и начало GET-строки
                                              .implode('&',$get_in_paginator)             // GET-строка
                                              .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                    if($paginator->pages_count>0 && $paginator->pages_count<$page){
                        Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                        exit(0);
                    }    
                    
                    $sql = "SELECT *, 
                        DATE_FORMAT(datetime,'%d.%m %k:%i') as  date
                        FROM ".$sys_tables['system_messages'];
                    if(!empty($condition)) $sql .= " WHERE ".$condition;
                    $sql .= " ORDER BY id DESC, date DESC LIMIT ".$paginator->getLimitString($page); 
                    $list = $db->fetchall($sql);
                    Response::SetArray('list', $list);
                    if($paginator->pages_count>1){
                        Response::SetArray('paginator', $paginator->Get($page));
                    }
                    $this_page->manageMetadata(array('title'=>'Системные сообщения'));
                    $module_template = 'admin.system_messages.list.html'; 
                    break;
                        
                }
                
                break;
            //статистика пользователей  всего и подписавшихся
            case 'users_stats':
                $module_template = 'admin.users.stats.html';
                $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
                $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
                $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js';
                $post_parameters = Request::GetParameters(METHOD_POST);
                // если была отправка формы - выводим данные 
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    //передача данных в шаблон
                    $date_start = $post_parameters['date_start'];
                    $date_end = $post_parameters['date_end'];
                    $info['date_start'] = $date_start;
                    $info['date_end'] = $date_end;
                    
                    $stats = $db->fetchall("
                            SELECT COUNT(*) as amount FROM ".$sys_tables['users']." WHERE date(`datetime`) BETWEEN STR_TO_DATE('".$date_start."','%d.%m.%Y') AND STR_TO_DATE('".$date_end."','%d.%m.%Y') AND id_agency = 0
                            UNION
                            SELECT COUNT(*) as amount FROM ".$sys_tables['users']." WHERE id_agency = 0
                        ");
                        
                        
                    $db->select_db('estate');
                    $list = $db->fetchall("
                    
                    SELECT 
                        u.id as id_user,
                    IFNULL(l.clive,0) + IFNULL(b.cbuild,0) + IFNULL(com.ccommercial,0) + IFNULL(cou.ccountrty,0) as summ
                    FROM common.users u
                    LEFT JOIN (SELECT IFNULL(COUNT(*),0) as clive, id_user FROM estate.live WHERE published = 1 AND info_source = 1 GROUP BY id_user) l ON l.id_user = u.id
                    LEFT JOIN (SELECT IFNULL(COUNT(*),0) as cbuild, id_user FROM estate.build WHERE published = 1 AND info_source = 1 GROUP BY id_user) b ON b.id_user = u.id
                    LEFT JOIN (SELECT IFNULL(COUNT(*),0) as ccommercial, id_user FROM estate.commercial WHERE published = 1 AND info_source = 1 GROUP BY id_user) com ON com.id_user = u.id
                    LEFT JOIN (SELECT IFNULL(COUNT(*),0) as ccountrty, id_user FROM estate.country WHERE published = 1 AND info_source = 1 GROUP BY id_user) cou ON cou.id_user = u.id
                    WHERE u.id_agency = 0 AND IFNULL(l.clive,0) + IFNULL(b.cbuild,0) + IFNULL(com.ccommercial,0) + IFNULL(cou.ccountrty,0)  > 1
                    GROUP BY u.id

                    ");
                                         if(!empty($db->error)) die($db->error);
                    $count = 0;
                    foreach ($list as $k => $item) if($item['summ']>=2 and $item['summ']<=3) $count++;
    
                    Response::SetInteger('usrcount',$count); // статистика объекта 
                    Response::SetArray('stats',$stats); // статистика объекта 
                    Response::SetArray('info',$info); // статистика объекта 
                }                
            break;
            //статистика подписавшихся пользователей
            case 'users_subsribed_stats':

                $fields = array(
                        array('string','Дата')
                        ,array('number','Подписчики')
                        ,array('number','Отписалось')
                        ,array('number','Подписалось')
                );

                
                Response::SetArray('data_titles',$fields);                
                
                if  (!$ajax_mode){
                    $module_template = 'admin.user.stats.subscribed.chart.html';
                    
                    $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
                    $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
                    $GLOBALS['js_set'][] = '/js/google.chart.api.js';
                    $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js'; 
                }
                                                       
                $get_parameters = Request::GetParameters(METHOD_GET);
                
                // если была отправка формы - выводим данные 
                if( !empty($get_parameters['submit'])  || $ajax_mode ){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    $date_start = $get_parameters['date_start'];
                    $date_end = $get_parameters['date_end'];
                    $info['date_start'] = $date_start;
                    $info['date_end'] = $date_end;
                                
                    $stats = $db->fetchall("
                            SELECT *, DATE_FORMAT(`date`,'%d.%m.%Y') as idate FROM ".$sys_tables['subscribed_users_stats']."
                              WHERE
                                  `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') - INTERVAL 1 DAY AND 
                                  `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') 
                              GROUP BY `date`
                              ORDER BY `date` DESC
                    ");           
                        
                    $max_days = count($stats) - 1;
                    
                    for( $i = 0; $i<$max_days; $i++ ) {
                        $stats[$i]['date'] = $stats[$i]['idate'];
                        $stats[$i]['unsubscribed'] -= $stats[$i+1]['unsubscribed'];  //считаем сколько подписчиков отписалось за день
                        $stats[$i]['changes'] = $stats[$i]['subscribed'] - $stats[$i+1]['subscribed'] + $stats[$i]['unsubscribed']; //считаем действия подписки/отписки за день
                    }
                    
                    array_pop ($stats); //убираем из массива последний (предыдущий) день
                    krsort ($stats);
                    
                    Response::SetArray('stats',$stats); // статистика объекта 

                    if (!$ajax_mode) Response::SetArray('info',$info); // информация об объекте 
                    else {
                        $module_template = 'admin.user.stats.subscribed.table.html';
                        $graphic_colors = array('#3366CC','#DC3912','#FF9900');       // Цвета графиков
                        $data = [];
                        
                        $chartstats = array ('date','subscribed','unsubscribed','changes');
                       
                        if($stats) {
                            foreach($stats as $ind=>$item) {   // Преобразование массива
                                $arr = [];
                                foreach($item as $key=>$val){
                                    if(in_array($key,$chartstats)){
                                        if ($key!='date')
                                            $arr[] = array(Convert::ToString($key),Convert::ToInt($val));
                                        else
                                            $arr[] = array(Convert::ToString($key),Convert::ToString($val));
                                    }
                                }     
                                $data[] = $arr;
                            }
                        }
     
                        $ajax_result = array(
                            'ok' => true,
                            'data' => $data,
                            'count' => count($data),
                            'height'=>300,
                            'width'=>725,
                            'fields' => $fields,
                            'colors' => $graphic_colors
                        );
                    }                 
                }                
            break;
            case 'photos':
                if($worktable=='agencies'){
                // дефолтное значение папки выгрузки и свойств фото
                Photos::$__folder_options = array('sm'=>array(170,50,'',65),
                                                  'med'=>array(310,90,'',65),
                                                  'big'=>array(270,270,'',55));
                /**************************\
                |*  Работа с фотографиями  *|
                \**************************/                
                if($ajax_mode){
                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                    switch($action){
                        case 'list':
                            //получение списка фотографий
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            if(!empty($id)){
                                $list = Photos::getList('agencies',$id);
                                if(!empty($list)){
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $list;
                                    $ajax_result['folder'] = Config::$values['img_folders']['agencies'];
                                } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'add':
                            //загрузка фотографий
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);                
                            if(!empty($id)){
                                //$res = Photos::Add('agencies',$id,false,false,false,170,50,true);
                                $res = Photos::Add('agencies',$id,false,false,false,false,false,true);
                                if(!empty($res)){
                                        if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                        else {
                                            $ajax_result['ok'] = true;
                                            $ajax_result['list'] = $res;
                                        }
                                } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'setTitle':
                            //добавление названия
                            $id = Request::GetInteger('id_photo', METHOD_POST);                
                            $title = Request::GetString('title', METHOD_POST);                
                            if(!empty($id)){
                                $res = Photos::setTitle('agencies',$id, $title);
                                $ajax_result['last_query'] = '';
                                if(!empty($res)) $ajax_result['ok'] = true;
                                else $ajax_result['error'] = 'Невозможно выполнить обновление названия фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
        
                        case 'del':
                            //удаление фото
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::Delete('agencies',$id_photo);
                                
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                    }
                }
                }
                break;
                          
            case 'add':
            case 'edit':
                $module_template = 'admin.'.$worktable.'.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables[$worktable]);
                    if($worktable=='users'){
                        $info['datetime'] = $info['last_enter'] = date('Y-m-d H:i:s');
                        $info['access'] = '';
                        $mapping[$worktable]['passwd']['allow_empty'] = false;
                        $mapping[$worktable]['passwd']['allow_null'] = false;
                    } elseif($worktable=='agencies'){ //добавляем данные прикрепленного к агентству пользователя
                        if(empty($mapping['agencies']['users_id']['value'])){
                            $users = $db->prepareNewRecord($sys_tables['users']);    
                            foreach($users as $k=>$value) $info_users['users_'.$k]=$value;
                        }
                    }
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables[$worktable]."
                                        WHERE id=?", $id);
                    if(empty($info)) Host::Redirect('/admin/access/'.$worktable.'/add/');
                    if($worktable=='agencies'){
                        
                        //добавляем данные прикрепленного к агентству пользователя
                        $users = $db->fetch("SELECT id
                                            FROM ".$sys_tables['users']."
                                            WHERE id_agency = ? AND agency_admin = 1", $id);
                        if(!empty($users)) foreach($users as $k=>$value) $info_users['users_'.$k]=$value;
                        //autocomplete для заполнения головного офиса
                        //карта
                        $GLOBALS['js_set'][] =  '/js/jquery.addrselector.js';
                        
                        //время работы: (datetime)
                        $GLOBALS['css_set'][] = '/modules/content/admin.css';
                        $GLOBALS['js_set'][] = '/js/datetimepicker/jquery.datetimepicker.js';
                        $GLOBALS['css_set'][] = '/js/datetimepicker/jquery.datetimepicker.css';
                        
                        $working_days = $db->fetchall("SELECT * FROM ".$sys_tables['agencies_opening_hours']." WHERE id_agency = ".$info['id'],'day_num');
                        $weekdays = array('понедельник','вторник','среда','четверг','пятница','суббота','воскресенье');
                        foreach($weekdays as $key=>$item){
                            $weekdays[$key] = array('ru_title'=>$item,'begin'=>(!empty($working_days[$key+1])?$working_days[$key+1]['begin']:0),'end'=>(!empty($working_days[$key+1])?$working_days[$key+1]['end']:0),'applications_processing'=>(!empty($working_days[$key+1]))?$working_days[$key+1]['applications_processing']:2);
                        }

                        // получение данных, отправленных из формы
                        $post_parameters = Request::GetParameters(METHOD_POST);
                        
                        if(empty($post_parameters)) Response::SetArray('weekday_hours',$weekdays);
                        
                        //добавляем название головного офиса
                        if((empty($post_parameters) && !empty($info['id_main_office'])) || !empty($post_parameters['id_main_office'])){
                            $head_office = $db->fetch("SELECT CONCAT(title,' - ',addr) AS title 
                                                       FROM ".$sys_tables['agencies']." 
                                                       WHERE id = ".(!empty($info['id_main_office'])?$info['id_main_office']:$post_parameters['id_main_office']))['title'];
                            $head_office = preg_replace('/"/','\'',$head_office);
                            Response::SetString('head_office_title',$head_office);
                        }
                        
                    //для не-админов убираем редактирование галочки "отчет о выгрузке XML"
                    }elseif($worktable == 'users' && $info['agency_admin'] != 1) unset($mapping[$worktable]['xml_notification']);
                }
                if($action=='edit'){
                    if(!empty($users['passwd'])) unset($users['passwd']);
                    if(!empty($info['passwd'])) unset($info['passwd']);
                    if(!empty($mapping[$worktable]['passwd'])) unset($mapping[$worktable]['passwd']);
                    
                }
                if(!strtotime($info['tarif_start'])) $info['tarif_start'] = date("Y-m-d"); 
                if(!strtotime($info['tarif_end'])) $info['tarif_end'] = date('Y-m-d', strtotime("+1 months"));
                if(!strtotime($info['advert_phone_date_end'])) $info['advert_phone_date_end'] = date("Y-m-d"); 
                
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field) if(!empty($mapping[$worktable][$key]) && $key!='passwd') $mapping[$worktable][$key]['value'] = $info[$key];
                    
                if($worktable=='agencies'){ //добавление в мепинг данных пользователя агентства
                    if(!empty($info_users)){
                        foreach($info_users as $key=>$field){
                            if($key == 'users_id') $mapping[$worktable][$key]['value'] = $info_users[$key];
                        }                
                    } else {
                        foreach($info as $key=>$field){
                            if(isset($mapping[$worktable][$key]) && $key == 'users_id') $mapping[$worktable][$key]['value'] = $info_users[$key];
                        }   
                        
                    }               
                    //если это не банк, убираем поле "прием ипотечных заявок"
                    if(!($info['activity'] & pow(2,4) && $info['estate_types'] & pow(2,8))) unset($mapping[$worktable]['mortgage_applications_accepting']);
                }
                // формирование дополнительных данных для формы (не из основной таблицы)
                if($worktable=='users'){
                    $GLOBALS['js_set'][]='/js/form.validate.js';
                    $groups = $db->fetchall("SELECT id,name FROM ".$sys_tables['users_groups']." ORDER BY name");
                    foreach($groups as $key=>$val){
                        $mapping['users']['id_group']['values'][$val['id']] = $val['name'];
                    }
                    $agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']." ORDER BY title");
                    foreach($agencies as $key=>$val){
                        $mapping['users']['id_agency']['values'][$val['id']] = $val['title'];
                    }
                    $types = $db->fetchall("SELECT id,title FROM ".$sys_tables['users_types']." ORDER BY id ASC");
                    foreach($types as $key=>$val){
                        $mapping['users']['id_user_type']['values'][$val['id']] = $val['title'];
                    }
                } elseif($worktable=='agencies'){
                    
                    $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                    $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
                    $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." ORDER BY name");
                    foreach($managers as $key=>$val){
                        $mapping['agencies']['id_manager']['values'][$val['id']] = $val['name'];
                    }
                    $tarifs_agencies = $db->fetchall("SELECT id,title as name,cost FROM ".$sys_tables['tarifs_agencies']." ORDER BY name");
                    foreach($tarifs_agencies as $key=>$val){
                        $mapping['agencies']['id_tarif']['values'][$val['id']] = $val['name'];
                        $mapping['agencies']['id_tarif']['attributes']['data-cost'][$val['id']] = $val['cost'];
                    }
                    
                    
                    //кол-во актуальных объектов для агентства (если выбрано пакетное размещение)
                    if($action=='edit'){
                        $sql = "SELECT IFNULL(cnt,0) as cnt, type FROM(
                                (
                                    SELECT COUNT(*) AS cnt, 'live_sell_objects' as type 
                                    FROM  `estate`.`live` l 
                                    LEFT JOIN  ".$sys_tables['users']." us ON  `l`.`id_user` =  `us`.`id` 
                                    WHERE us.id_agency =".$id." AND l.published =1  AND l.info_source!=4 AND l.info_source!=6 AND l.rent=2
                                )
                                UNION
                                (
                                    SELECT COUNT(*) AS cnt, 'live_rent_objects' as type 
                                    FROM  `estate`.`live` l 
                                    LEFT JOIN  ".$sys_tables['users']." us ON  `l`.`id_user` =  `us`.`id` 
                                    WHERE us.id_agency =".$id." AND l.published =1  AND l.info_source!=4 AND l.info_source!=6 AND l.rent=1
                                )
                                UNION
                                (
                                    SELECT COUNT(*) AS cnt, 'build_objects' as type 
                                    FROM  `estate`.`build` l 
                                    LEFT JOIN  ".$sys_tables['users']." us ON  `l`.`id_user` =  `us`.`id` 
                                    WHERE us.id_agency =".$id." AND l.published =1   AND l.info_source!=4 AND l.info_source!=6
                                )
                                UNION
                                (
                                    SELECT COUNT(*) AS cnt, 'commercial_sell_objects' as type 
                                    FROM  `estate`.`commercial` l 
                                    LEFT JOIN  ".$sys_tables['users']." us ON  `l`.`id_user` =  `us`.`id` 
                                    WHERE us.id_agency =".$id." AND l.published =1   AND l.info_source!=4 AND l.info_source!=6 AND l.rent=2
                                )
                                UNION
                                (
                                    SELECT COUNT(*) AS cnt, 'commercial_rent_objects' as type 
                                    FROM  `estate`.`commercial` l 
                                    LEFT JOIN  ".$sys_tables['users']." us ON  `l`.`id_user` =  `us`.`id` 
                                    WHERE us.id_agency =".$id." AND l.published =1   AND l.info_source!=4 AND l.info_source!=6 AND l.rent=1
                                )
                                UNION
                                (
                                    SELECT COUNT(*) AS cnt, 'country_sell_objects' as type 
                                    FROM  `estate`.`country` l 
                                    LEFT JOIN  ".$sys_tables['users']." us ON  `l`.`id_user` =  `us`.`id` 
                                    WHERE us.id_agency =".$id." AND l.published =1   AND l.info_source!=4 AND l.info_source!=6 AND l.rent=2
                                )
                                UNION
                                (
                                    SELECT COUNT(*) AS cnt, 'country_rent_objects' as type 
                                    FROM  `estate`.`country` l 
                                    LEFT JOIN  ".$sys_tables['users']." us ON  `l`.`id_user` =  `us`.`id` 
                                    WHERE us.id_agency =".$id." AND l.published =1   AND l.info_source!=4 AND l.info_source!=6 AND l.rent=1
                                )
                            ) a";
                        $published_objects=$db->fetchall($sql);
                        Response::SetArray('published_objects',$published_objects);
                        
                    }                    
                    
                }
                
                if($worktable=='agencies'){
                    //не показывать кнопку "сохранить" в темплейте формы
                    Response::SetBoolean('not_show_submit_button', true); 
                    //чтобы привязалось название головного агентства
                } 
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);

                if( empty( $post_parameters['advert_phone'] ) || strlen($post_parameters['advert_phone']) < 17 || ( strlen( $post_parameters['advert_phone'] ) == 17 && empty( $post_parameters['advert_phone_date_end'] ) ) ) {
                    $post_parameters['advert_phone'] = $info['advert_phone'] = '';
                    $post_parameters['advert_phone_date_end'] = $info['advert_phone_date_end'] = '0000-00-00';
                }

                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    
                    
                    if(empty($post_parameters['id_tarif'])){
                        $mapping['agencies']['tarif_cost']['required'] = false;
                        $mapping['agencies']['tarif_cost']['allow_empty'] = true;
                        $mapping['agencies']['tarif_cost']['value'] = 0;
                    }
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping[$worktable][$key]) && $mapping[$worktable][$key]['fieldtype']=='set') {
                            if(!empty($post_parameters[$key.'_set'])){
                                $mapping[$worktable][$key]['value'] = 0;
                                foreach($post_parameters[$key.'_set'] as $pkey=>$pval){
                                    $mapping[$worktable][$key]['value'] += pow(2,$pkey-1);
                                }
                                $post_parameters[$key] = trim($mapping[$worktable][$key]['value']);
                            }
                        }
                        elseif(!empty($mapping[$worktable][$key])) $mapping[$worktable][$key]['value'] = trim($post_parameters[$key]);
                    }
                    //если тариф установлен, заполняем значение поля tarif_cost и делаем его обязательным
                    if(!empty($mapping['agencies']['id_tarif']['value']) && $mapping['agencies']['id_tarif']['value']!=8){
                        $mapping['agencies']['tarif_cost']['required'] = true;
                        $mapping['agencies']['tarif_cost']['allow_empty'] = false;
                        if(empty($mapping['agencies']['tarif_cost']['value']))$mapping['agencies']['tarif_cost']['value'] = $mapping['agencies']['id_tarif']['attributes']['data-cost'][$mapping['agencies']['id_tarif']['value']];
                    }else{
                        $mapping['agencies']['tarif_cost']['required'] = false;
                        $mapping['agencies']['tarif_cost']['allow_empty'] = true;
                        $mapping['agencies']['tarif_cost']['value'] = 0;
                    } 
                    
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping[$worktable]);
                    // дополнительные проверки данных
                    if($worktable=='users'){
                        
                        //если в поле 'телефон' только 8, значит телефон пуст
                        if(preg_replace('/[^0-9]/','',$mapping[$worktable]['phone']['value'])=='8') $mapping[$worktable]['phone']['value']='';
                        
                        //если телефон непуст, проверяем его на корректность
                        if(!empty($mapping[$worktable]['phone']['value']) && empty($errors['phone']) && !Validate::isPhone($mapping[$worktable]['phone']['value'])){
                            $errors['phone'] = 'Некорректный телефон';
                            $mapping['phone']['value'] = "";    
                        }
                        
                        //если email не пуст, проверяем его корректность
                        if((!empty($mapping[$worktable]['email']['value']))&&(empty($errors['email']))){
                            if (!Validate::isEmail($mapping[$worktable]['email']['value'])) $errors['email'] = 'Некорректный email';
                            else{
                                // дубликаты мейла
                                $res = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE email=? AND id<>?", $mapping[$worktable]['email']['value'], $id);
                                if(!empty($res)) $errors['email'] = $mapping[$worktable]['email']['error'] = 'Такой email уже есть в базе данных пользователей';
                            }
                        }
                        
                        //если логин не пуст, проверяем его корректность
                        if((!empty($mapping[$worktable]['login']['value']))&&(empty($errors['login']))){
                            if(!Validate::isEmail($mapping[$worktable]['login']['value']) && !Validate::isLogin($mapping[$worktable]['login']['value'])) $errors['login'] = $mapping[$worktable]['login']['error'] = 'Некорректный логин. Логин должен быть не короче 4-х символов и может содержать латинские буквы, цифры, знаки - + . , _ ( ) { } [ ] < >';
                            else {
                                // проверка на дубликаты логина
                                $res = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE login=? AND id<>?", $mapping[$worktable]['login']['value'], $id);
                                if(!empty($res)) $errors['login'] = $mapping[$worktable]['login']['error'] = 'Такой логин уже есть в базе данных пользователей';
                            }
                        }
                        
                        //если телефон не пуст, проверяем его корректность
                        if((!empty($mapping[$worktable]['phone']['value']))&&(empty($errors['phone']))){
                            // дубликаты телефона
                            $res = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE phone=? AND id<>?", $mapping[$worktable]['phone']['value'], $id);
                            if(!empty($res)) $errors['phone'] = $mapping[$worktable]['phone']['error'] = 'Такой телефон уже есть в базе данных пользователей';
                        }
                        
                        //проверяем, что одно из полей (логин, email, телефон) непусто
                        if ((empty($mapping[$worktable]['email']['value']))&&(empty($mapping[$worktable]['login']['value']))&&(empty($mapping[$worktable]['phone']['value']))){
                            $errors['email']='логин, email, или телефон должны быть заполнены';
                            $errors['login']='логин, email, или телефон должны быть заполнены';
                            $errors['phone']='логин, email, или телефон должны быть заполнены';
                        }
                        // проверка на корректность пароля
                        if(empty($errors['passwd']) && !empty($mapping[$worktable]['passwd']['value'])){
                            if(!Validate::isPassword($mapping[$worktable]['passwd']['value'])) $errors['passwd'] = $mapping[$worktable]['passwd']['error'] = 'Некорректный пароль. Должен быть не короче 4-х символов и может содержать латинские буквы, цифры, знаки - + . , _ ( ) { } [ ] < >';
                        }
                    }elseif($worktable=='agencies')    { //проверка пользователя для агентства
                        
                        //если в поле 'телефон' только 8, значит телефон пуст
                        if(preg_replace('/[^0-9]/','',$mapping[$worktable]['phone_1']['value'])=='8') $mapping[$worktable]['phone_1']['value']='';
                        if(preg_replace('/[^0-9]/','',$mapping[$worktable]['phone_2']['value'])=='8')  $mapping[$worktable]['phone_2']['value']='';
                        if(preg_replace('/[^0-9]/','',$mapping[$worktable]['phone_3']['value'])=='8')  $mapping[$worktable]['phone_3']['value']='';
                        if(preg_replace('/[^0-9]/','',$mapping[$worktable]['advert_phone']['value'])=='8') $mapping[$worktable]['advert_phone']['value']='';
                        if(preg_replace('/[^0-9]/','',$mapping[$worktable]['advert_phone_objects']['value'])=='8') $mapping[$worktable]['advert_phone_objects']['value']='';
                        
                        //если телефоны непусты, проверяем корректность
                        if(!empty($mapping[$worktable]['phone_1']['value']) && empty($errors['phone_1']) && !Validate::isPhone($mapping[$worktable]['phone_1']['value'])){
                            $errors['phone_1'] = 'Некорректный телефон';
                            $mapping[$worktable]['phone_1']['value'] = "";    
                        }
                        if(!empty($mapping[$worktable]['phone_2']['value']) && empty($errors['phone_1']) && !Validate::isPhone($mapping[$worktable]['phone_2']['value'])){
                            $errors['phone_2'] = 'Некорректный телефон';
                            $mapping[$worktable]['phone_2']['value'] = "";    
                        }
                        if(!empty($mapping[$worktable]['phone_3']['value']) && empty($errors['phone_3']) && !Validate::isPhone($mapping[$worktable]['phone_3']['value'])){
                            $errors['phone_3'] = 'Некорректный телефон';
                            $mapping[$worktable]['phone_3']['value'] = "";    
                        }
                        if(!empty($mapping[$worktable]['advert_phone']['value']) && empty($errors['advert_phone']) && !Validate::isPhone($mapping[$worktable]['advert_phone']['value'])){
                            $errors['advert_phone'] = 'Некорректный телефон';
                            $mapping[$worktable]['advert_phone']['value'] = "";    
                        }
                        
                        //чистим ссылку
                        $mapping[$worktable]['xml_link']['value'] = trim($mapping[$worktable]['xml_link']['value']);
                        
                        //если выгрузка активна, назначаем время
                        if($mapping[$worktable]['xml_status']['value'] == 1){
                            if(empty($mapping[$worktable]['xml_link']['value'])) $errors['xml_link'] = 'При активной выгрузке нужно указать ссылку';
                            else{
                                $attached_times = $db->fetchall("SELECT xml_time FROM ".$sys_tables['agencies']." WHERE xml_status = 1 ORDER BY xml_time",'xml_time');
                                if(!empty($attached_times)){
                                    $attached_times = array_keys($attached_times);
                                    $time_start = DateTime::createFromFormat("H:i:s","00:10:00");
                                    while(in_array($time_start->format("H:i:s"),$attached_times)){
                                        $time_start->add(new DateInterval('PT10M'));
                                    }
                                    $mapping[$worktable]['xml_time']['value'] = $time_start->format("H:i:s");
                                } 
                                else $mapping[$worktable]['xml_time']['value'] = '00:10:00';
                            }
                        }
                        
                        
                        //если контактный email не пуст, проверяем его корректность
                        if((!empty($mapping[$worktable]['email']['value']))&&(empty($errors['email']))){
                            if (!Validate::isEmail($mapping[$worktable]['email']['value'])) $errors['email'] = 'Некорректный контактный email';
                        }
                        
                        //если email для отчетов не пуст, проверяем его корректность
                        if((!empty($mapping[$worktable]['email_service']['value']))&&(empty($errors['email_service']))){
                            if (!Validate::isEmail($mapping[$worktable]['email_service']['value'])) $errors['email_service'] = 'Некорректный email для отчетов';
                        }
                        
                        
                        if (empty($mapping[$worktable]['users_id']['value'])) $errors['users_id'] = 'Поле не должно быть пустым';
                        else {
                            $is_user = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id = ?", $mapping[$worktable]['users_id']['value']);
                            //существование пользователя
                            if(empty($is_user)) $errors['users_id'] = 'Пользователя с таким ID не существует';
                            else {
                                //если пользователь уже админ другой компании
                                $is_admin = $db->fetch("SELECT 
                                                                ".$sys_tables['users'].".id,
                                                                TRIM(".$sys_tables['agencies'].".title) as title 
                                                        FROM 
                                                            ".$sys_tables['users']." 
                                                        LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                                        WHERE 
                                                            ".$sys_tables['users'].".agency_admin = ? AND 
                                                            ".$sys_tables['users'].".id = ?
                                                            ". ( $action == 'edit' ? " AND ".$sys_tables['users'].".id_agency != ".$mapping[$worktable]['id']['value'] : "" )
                                                        , 1, $mapping[$worktable]['users_id']['value']
                                );
                                if(!empty($is_admin)) $errors['users_id'] = 'Данный пользователь уже администратор компании «'.$is_admin['title'].'»';
                            }
                        }
                            
                    }
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping[$worktable][$key])) $mapping[$worktable][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        //при необходимости, корректируем расписание
                        if($worktable == 'agencies' && !empty($mapping[$worktable]['working_times']['value'])){
                            //убираем из mapping поле "working_times", пишем время работы по дням
                            $working_times = json_decode($mapping[$worktable]['working_times']['value']);
                            //если что-то есть, записываем
                            if(count($working_times)>0){
                                $working_days = [];
                                foreach($working_times as $key=>$item){
                                    $item = explode(',',$item);
                                    if(!empty($item[1]) && !empty($item[2]) || !empty($item[3])){
                                        
                                        if(!empty($item[1])) $item[1] = $item[1].":00";
                                        if(!empty($item[2])) $item[2] = $item[2].":00";
                                        
                                        $res = $db->fetch("SELECT * FROM ".$sys_tables['agencies_opening_hours']." WHERE day_num = ".($item[0]+1)." AND id_agency = ".$mapping[$worktable]['id']['value']);
                                        if(!empty($res)) 
                                            $db->query("UPDATE ".$sys_tables['agencies_opening_hours']." SET begin = ?, end = ?, applications_processing = ? 
                                                        WHERE day_num = ? AND id_agency = ?",$item[1],$item[2],$item[3],($item[0]+1),$mapping[$worktable]['id']['value']);
                                        else 
                                            $db->query("INSERT INTO ".$sys_tables['agencies_opening_hours']." (day_num,id_agency,begin,end,applications_processing) 
                                                        VALUES (?,?,?,?,?)",($item[0]+1),$mapping[$worktable]['id']['value'],$item[1],$item[2],$item[3]);
                                        if(!empty($db->error)) Response::SetString('db_error',$db->error);
                                        $working_days[$item[0]] = array('day_num'=>$item[0],'begin'=>$item[1],'end'=>$item[2]);
                                    }
                                    else $db->query("DELETE FROM ".$sys_tables['agencies_opening_hours']." WHERE day_num = ? AND id_agency = ?",($item[0]+1),$mapping[$worktable]['id']['value']);
                                }
                            }
                        }
                        
                        if($worktable == 'agencies' && $action == 'edit'){
                            
                            //если есть тариф, делаем страницу платной и обратно
                            $mapping['agencies']['payed_page']['value'] = (!empty($mapping['agencies']['id_tarif']['value'])?1:2);
                            
                            //если тарифа нет, сбрасываем лимиты
                            if(empty($mapping['agencies']['id_tarif']['value'])){
                                $mapping['agencies']['build_objects']['value'] = 0;
                                $mapping['agencies']['live_rent_objects']['value'] = 0;
                                $mapping['agencies']['live_sell_objects']['value'] = 0;
                                $mapping['agencies']['country_rent_objects']['value'] = 0;
                                $mapping['agencies']['country_sell_objects']['value'] = 0;
                                $mapping['agencies']['commercial_rent_objects']['value'] = 0;
                                $mapping['agencies']['commercial_sell_objects']['value'] = 0;
                            }
                            
                            //отдаем обратно в шаблон чтобы сразу было видно:
                            $working_days = $db->fetchall("SELECT * FROM ".$sys_tables['agencies_opening_hours']." WHERE id_agency = ".$info['id'],'day_num');
                            $weekdays = array('понедельник','вторник','среда','четверг','пятница','суббота','воскресенье');
                            foreach($weekdays as $key=>$item){
                                $weekdays[$key] = array('ru_title'=>$item,'begin'=>(!empty($working_days[$key+1])?$working_days[$key+1]['begin']:0),'end'=>(!empty($working_days[$key+1])?$working_days[$key+1]['end']:0),'applications_processing'=>(!empty($working_days[$key+1])?$working_days[$key+1]['applications_processing']:""));
                            }
                            Response::SetArray('weekday_hours',$weekdays);
                        }
                        if($worktable == 'agencies'){
                            $working_times = $mapping[$worktable]['working_times'];
                            unset($mapping[$worktable]['working_times']);
                        }
                        
                        // корректировка пароля перед сохранением
                        if($worktable=='users' && !empty($mapping[$worktable]['passwd']['value'])){
                            // если пароль изменился, то готовим к записи его двойной хэш
                            $backup_passwd = $mapping[$worktable]['passwd']['value'];
                            if($mapping[$worktable]['passwd']['value'] !==$info['passwd']) $mapping[$worktable]['passwd']['value'] = sha1(sha1($mapping[$worktable]['passwd']['value']));
                        }
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping[$worktable][$key]['value'])) $info[$key] = $mapping[$worktable][$key]['value'];
                        }
                        
                        if($worktable=='agencies'){ //добавление в мепинг данных пользователя агентства
                            $new_tarif_title = "";
                            if(!empty($info_users)){
                                foreach($info_users as $key=>$field){
                                    if(isset($mapping[$worktable][$key])) $info_users[str_replace('users_','',$key)] = $mapping[$worktable][$key]['value'];
                                }   
                            } else {
                                foreach($post_parameters as $key=>$field){
                                    if(isset($mapping[$worktable][$key]) && $key == 'users_id') $info_users[str_replace('users_','',$key)] = $mapping[$worktable][$key]['value'];
                                }   
                            }
                            $info['agency_admin'] = 1;
                            //если сменяется тариф
                            if(!empty($info['id_tarif']) && $post_parameters['change_tarif'] == 1) {
                                  $tarif = $db->fetch("SELECT * FROM ".$sys_tables['tarifs_agencies']." WHERE id = ?", $info['id_tarif']);
                                  //обнуление занятости офисов БЦ если не совпадают флаги тарифов БЦ
                                  if($info['business_center'] != $tarif['business_center']) {
                                      $bc = $db->fetchall("SELECT 
                                                                ".$sys_tables['business_centers_levels'].".id 
                                                           FROM ".$sys_tables['business_centers']." 
                                                           LEFT JOIN ".$sys_tables['business_centers_levels']." ON ".$sys_tables['business_centers_levels'].".id_parent = ".$sys_tables['business_centers'].".id
                                                           WHERE ".$sys_tables['business_centers'].".id_user = ?
                                                           GROUP BY ".$sys_tables['business_centers_levels'].".id", 
                                                           false, $info_users['id']
                                      );
                                      if(!empty($bc)){
                                          $ids = [];
                                          foreach($bc as $k=>$item) $ids[] = $item['id'];
                                          $db->query("UPDATE ".$sys_tables['business_centers_offices']." SET id_renter = 0, status = 2, date_rent_start = '0000-00-00', date_rent_start = '0000-00-00' WHERE id_parent IN (".implode(", ", $ids).")");
                                      }
                                  }
                                  $info['promo'] = $tarif['promo'];
                                  $info['premium'] = $tarif['premium'];
                                  $info['vip'] = $tarif['vip'];
                                  $info['staff_number'] = $tarif['staff_number'];
                                  $info['action'] = $tarif['action'];
                                  $info['video'] = $tarif['video'];
                                  $info['business_center'] = $tarif['business_center'];
                                  $info['change_tarif'] = $mapping['agencies']['change_tarif']['value'] = 2;
                                  //логирование
                                  $new_tarif_title = $tarif['title'];
                            }
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            if(isset($info['passwd']) && empty($info['passwd'])) unset($info['passwd']);
                            //обновление флага "подставной телефон" для объектов недвижимости
                            if($worktable=='agencies'){
                                $user = $db->fetch("SELECT `id` FROM ".$sys_tables['users']." WHERE id_agency=".Convert::ToInt($info['id']));
                                $advert_phone =  Validate::isPhone($info['advert_phone'])?2:1;
                                $db->query("UPDATE ".$sys_tables['live']." SET `has_advert_phone`= ? WHERE `id_user` = ?", $advert_phone, $user['id']);
                                $db->query("UPDATE ".$sys_tables['build']." SET `has_advert_phone`= ? WHERE `id_user` = ?", $advert_phone, $user['id']);
                                $db->query("UPDATE ".$sys_tables['commercial']." SET `has_advert_phone`= ? WHERE `id_user` = ?", $advert_phone, $user['id']);
                                $db->query("UPDATE ".$sys_tables['country']." SET `has_advert_phone`= ? WHERE `id_user` = ?", $advert_phone, $user['id']);
                                //если назначен тариф агентству, делаем страницу брендированной
                                $info['payed_page'] = (!empty($info['id_tarif'])?1:2);
                            } 
                            //если назначен тариф пользователю, смотрим, нужна ли брендированная страница и проставляем ему тип "специалист"
                            elseif(!empty($info['id_tarif'])){
                                $info['payed_page'] = $db->fetch("SELECT payed_page FROM ".$sys_tables['tarifs']." WHERE id = ".$info['id_tarif'])['payed_page'];
                                $info['id_user_type'] = 2;
                            }
                            
                            $res = $db->updateFromArray($sys_tables[$worktable], $info, 'id');
                            
                            if($worktable=='agencies'){
                                //записываем пользователя который провел операцию
                                Common::LogAgencyOperation($auth->id,$info['id'],2,"");
                                if(!empty($new_tarif_title)) Common::LogAgencyOperation($auth->id,$info['id'],3,(($new_tarif_title == "empty")?"Тариф снят":$new_tarif_title));
                                
                                //убираем брендированную страницу при снятии тарифа
                                if($new_tarif_title == "empty") $db->query("UPDATE ".$sys_tables['agencies']." SET payed_page = 2 WHERE id = ".$info['id']);
                                
                                //обновление пользователя для агентства
                                $db->query("UPDATE ".$sys_tables['users']." SET agency_admin = 2 WHERE id_agency = ?", $info['id']);
                                $db->query("UPDATE ".$sys_tables['users']." SET agency_admin = 1, id_agency = ? WHERE id = ?",$info['id'], $info_users['id']);
                            } 
                            //простановка ответственного агентству
                            if(($worktable == 'users' && $info['id_agency'] > 0)){
                                $agency_admin = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE agency_admin = 1 AND id_agency = ?", $info['id_agency']);
                                if(empty($agency_admin)){
                                    $db->query("UPDATE ".$sys_tables['users']." SET agency_admin = 2 WHERE id_agency = ?", $info['id_agency']);
                                    $db->query("UPDATE ".$sys_tables['users']." SET agency_admin = 1 WHERE id = ?", $info['id']);
                                }
                            }
                        } else {
                            
                            //при необходимости, задаем цвет аватарки
                            if($worktable == 'users' && empty($info['avatar_color'])){
                                $colors = Config::Get('users_avatar_colors');
                                $info['avatar_color'] = $colors[mt_rand(0,11)];
                            }
                            
                            $res = $db->insertFromArray($sys_tables[$worktable], $info, 'id');
                            //обновление пользователя для агентства
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                //простановка ответственного агентству

                                if($worktable=='agencies') {
                                    $db->query( "UPDATE ".$sys_tables['agencies']." SET `chpu_title` = ? WHERE `id` = ?", $new_id.'_'.Convert::ToTranslit($info['title']), $new_id);
                                    //обновляем админа агентства
                                    $db->query("UPDATE ".$sys_tables['users']." SET agency_admin = 2 WHERE id_agency = ?", $new_id);
                                    $db->query("UPDATE ".$sys_tables['users']." SET agency_admin = 1, id_agency = ? WHERE id = ?",$new_id, $mapping['agencies']['users_id']['value']);
                                    //записываем пользователя который провел операцию
                                    Common::LogAgencyOperation($auth->id,$new_id,1,(!empty($old_user_id))?("с привязкой к пользователю #".$old_user_id):("с созданием пользователя #".$user_id));
                                }
                                if(($worktable == 'users' && $info['id_agency'] > 0) || $worktable == 'agencies'){
                                    $db->query("UPDATE ".$sys_tables['users']." SET agency_admin = 2 WHERE id_agency = ? AND id != ?", $worktable == 'users' ? $info['id_agency'] : $new_id, $worktable == 'users' ? $new_id : $user_id);
                                    $db->query("UPDATE ".$sys_tables['users']." SET agency_admin = 1 WHERE id = ?", $worktable == 'users' ? $new_id : $user_id);
                                }
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/access/'.$worktable.'/edit/'.$new_id.'/'));
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
                if(isset($mapping[$worktable]['passwd'])) $mapping[$worktable]['passwd']['value']='';

                //возвращаем working_times
                if(!empty($working_times))$mapping[$worktable]['working_times'] = $working_times;
                
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping[$worktable]);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                
                if($worktable=='agencies'){
                    //чистим пользователей агентства
                    $db->query("UPDATE ".$sys_tables['users']." SET id_agency = 0,agency_admin = 2 WHERE id_agency = ?",$id);
                    //удаление фото агентства
                    $del_photos = Photos::DeleteAll('agencies',$id);
                }
                $res = $db->query("DELETE FROM ".$sys_tables[$worktable]." WHERE id = ?", $id);
                
                //записываем пользователя который провел операцию
                Common::LogAgencyOperation($auth->id,$id,7,"");
                
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            //обновление информации по количеству объектов
            case 'refresh':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($worktable == 'agencies'){
                    $user_ids = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']." WHERE id_agency = ".$id)['ids'];
                    $id_agency = $id;
                }else $user_ids = $id;
                $amounts = $db->fetchall("SELECT 'build' AS type, id_user, COUNT(*) AS amount 
                                          FROM ".$sys_tables['build']." WHERE id_user IN (".$user_ids.") AND published=1 GROUP BY id_user
                                          UNION SELECT 'live' AS type, id_user, COUNT(*) AS amount 
                                          FROM ".$sys_tables['live']." WHERE id_user IN (".$user_ids.") AND published=1 GROUP BY id_user
                                          UNION SELECT 'commercial' AS type, id_user, COUNT(*) AS amount 
                                          FROM ".$sys_tables['commercial']." WHERE id_user IN (".$user_ids.") AND published=1 GROUP BY id_user
                                          UNION SELECT 'country' AS type, id_user, COUNT(*) AS amount 
                                          FROM ".$sys_tables['country']." WHERE id_user IN (".$user_ids.") AND published=1 GROUP BY id_user");
                //записываем то что прочитали по пользователям
                foreach($amounts as $key=>$item){
                    $user_info[$item['type']] = $item['amount'];
                    $user_info['has_active'] = true;
                }
                //обновляем поля
                $counter = array('build'=>0,'live'=>0,'commercial'=>0,'country'=>0);
                
                if(!empty($user_info['has_active'])){
                    $update_query = [];
                    
                    $update_query[] = "active_build = ".(empty($user_info['build'])?0:$user_info['build']);
                    $counter['build'] += (!empty($user_info['build'])?$user_info['build']:0);
                    
                    $update_query[] = "active_live = ".(empty($user_info['live'])?0:$user_info['live']);
                    $counter['live'] += (!empty($user_info['live'])?$user_info['live']:0);
                    
                    $update_query[] = "active_country = ".(empty($user_info['country'])?0:$user_info['country']);
                    $counter['country'] += (!empty($user_info['country'])?$user_info['country']:0);
                    
                    $update_query[] = "active_commercial = ".(empty($user_info['commercial'])?0:$user_info['commercial']);
                    $counter['commercial'] += (!empty($user_info['commercial'])?$user_info['commercial']:0);
                    
                    if(!empty($update_query)){
                        $res = $db->query("UPDATE ".$sys_tables[$worktable]." 
                                           SET ".implode(',',$update_query)." 
                                           WHERE id = ".(($worktable=='users')?$id:$id_agency));
                    } 
                    else $res = true;
                    $results['refresh'] = ($res || $db->affected_rows) ? $id : -1;
                }
                else{
                    $results['refresh'] = $id;
                    $res = $db->query("UPDATE ".$sys_tables[$worktable]." 
                                       SET active_build = 0,active_live = 0,active_commercial = 0, active_country = 0
                                       WHERE id = ".(($worktable=='users')?$id:$id_agency));
                }
                
                if($ajax_mode){
                    //field_num - номер столбца, который будем обновлять
                    //field_update - новая информация для столбца
                    $ajax_result = array('ok' => $results['refresh'], 'ids'=>array($id),'field_num'=>3,'field_update'=>(!empty($user_info)?$user_info:""));
                    break;
                }
            break;
            //изменение баланса пользователей и агентств
            case 'replenish_balance':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                //агентства
                if($this_page->page_parameters[1] == 'agencies'){
                    $agency_info = $db->fetch("SELECT ".$sys_tables['agencies'].".id,
                                                      ".$sys_tables['agencies'].".id_manager,
                                                      ".$sys_tables['agencies'].".title,
                                                      ".$sys_tables['users'].".id AS id_user,
                                                      ".$sys_tables['users'].".balance
                                               FROM ".$sys_tables['agencies']."
                                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency AND ".$sys_tables['users'].".agency_admin = 1
                                               WHERE ".$sys_tables['agencies'].".id = ".$id);
                    Response::SetArray('agency_info',$agency_info);
                    $mapping['replenish_balance']['id_user']['value'] = $auth->id;
                    $mapping['replenish_balance']['id_target_user']['value'] = $agency_info['id_user'];
                    
                    // получение данных, отправленных из формы
                    $post_parameters = Request::GetParameters(METHOD_POST);
                    
                    // если была отправка формы - начинаем обработку
                    if(!empty($post_parameters['submit'])){
                        Response::SetBoolean('form_submit', true);
                       
                        // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                        foreach($post_parameters as $key=>$field){
                            if(!empty($mapping['replenish_balance'][$key])) $mapping['replenish_balance'][$key]['value'] = trim($post_parameters[$key]);
                        }
                        // проверка значений из формы
                        $errors = Validate::validateParams($post_parameters,$mapping['system_messages']);
                        // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                        foreach($errors as $key=>$value){
                            if(!empty($mapping['replenish_balance'][$key])) $mapping['replenish_balance'][$key]['error'] = $value;
                        }
                        // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                        if(empty($errors)) {
                            $info = [];
                            // подготовка всех значений для сохранения
                            foreach($mapping['replenish_balance'] as $key=>$fields){
                                if(isset($mapping['replenish_balance'][$key]['value'])) $info[$key] = $mapping['replenish_balance'][$key]['value'];
                            }
                            //ксли это менеджер, отсылаем на подтверждение, если это не менеджер - пополняем сразу
                            if($auth->id_group == 3){
                                //записываем пользователя который провел операцию
                                Common::LogAgencyOperation($auth->id,$id,6,$mapping['replenish_balance']['sum']['value']);
                                $res = $db->insertFromArray($sys_tables['admin_finances'], $info, 'id');
                                Response::SetBoolean('admin_transaction',false);
                            } 
                            elseif($auth->id_group == 10 || $auth->id_group = 101){
                                    if($info['sum'] > 0)
                                        $res = $db->query("INSERT INTO ".$sys_tables['users_finances']." (`id_user`,`obj_type`,`income`,`paygate`,`id_initiator`)
                                                           VALUES (?,'balance',?,1,?)",$info['id_target_user'],$info['sum'],$info['id_user']);
                                    else 
                                        $res = $db->query("INSERT INTO ".$sys_tables['users_finances']." (`id_user`,`obj_type`,`expenditure`,`paygate`,`id_initiator`)
                                                           VALUES (?,'balance',?,1,?)",$info['id_target_user'],abs($info['sum']),$info['id_user']);
                                    //записываем пользователя который провел операцию
                                    Common::LogAgencyOperation($auth->id,$id,5,$mapping['replenish_balance']['sum']['value']);
                                    //если все хорошо, пополняем баланс
                                    if($res) $res = $res && $db->query("UPDATE ".$sys_tables['users']." SET balance = balance + ".$info['sum']." WHERE id = ?",$info['id_target_user']);
                                    Response::SetBoolean('admin_transaction',true);
                            }
                            Response::SetBoolean('saved', $res); // результат сохранения
                        } else Response::SetBoolean('errors', true); // признак наличия ошибок
                    }
                    // запись данных для отображения на странице
                    Response::SetArray('data_mapping',$mapping['replenish_balance']);
                    Response::SetBoolean('not_show_submit_button',true);//чтобы после form_default не было кнопки
                    $module_template = 'admin.agencies.balance.html';
                }else{
                    $user_info = $db->fetch("SELECT ".$sys_tables['users'].".id,
                                                    IF(".$sys_tables['users'].".email = '',".$sys_tables['users'].".login,".$sys_tables['users'].".email) AS title,
                                                    ".$sys_tables['users'].".balance
                                               FROM ".$sys_tables['users']."
                                               LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                               WHERE ".$sys_tables['users'].".id = ".$id);
                    Response::SetArray('user_info',$user_info);
                    $mapping['replenish_balance']['id_user']['value'] = $auth->id;
                    $mapping['replenish_balance']['id_target_user']['value'] = $user_info['id'];
                    
                    // получение данных, отправленных из формы
                    $post_parameters = Request::GetParameters(METHOD_POST);
                    
                    // если была отправка формы - начинаем обработку
                    if(!empty($post_parameters['submit'])){
                        Response::SetBoolean('form_submit', true);
                       
                        // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                        foreach($post_parameters as $key=>$field){
                            if(!empty($mapping['replenish_balance'][$key])) $mapping['replenish_balance'][$key]['value'] = trim($post_parameters[$key]);
                        }
                        // проверка значений из формы
                        $errors = Validate::validateParams($post_parameters,$mapping['system_messages']);
                        // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                        foreach($errors as $key=>$value){
                            if(!empty($mapping['replenish_balance'][$key])) $mapping['replenish_balance'][$key]['error'] = $value;
                        }
                        // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                        if(empty($errors)) {
                            $info = [];
                            // подготовка всех значений для сохранения
                            foreach($mapping['replenish_balance'] as $key=>$fields){
                                if(isset($mapping['replenish_balance'][$key]['value'])) $info[$key] = $mapping['replenish_balance'][$key]['value'];
                            }
                            //ксли это менеджер, отсылаем на подтверждение, если это не менеджер - пополняем сразу
                            if($auth->id_group == 3){
                                $res = $db->insertFromArray($sys_tables['admin_finances'], $info, 'id');
                                Response::SetBoolean('admin_transaction',false);
                            } 
                            elseif($auth->id_group == 10 || $auth->id_group = 101){
                                    $res = $db->query("INSERT INTO ".$sys_tables['users_finances']." (`id_user`,`obj_type`,`income`,`paygate`,`id_initiator`)
                                                    VALUES (?,'balance',?,1,?)",$info['id_target_user'],$info['sum'],$info['id_user']);
                                    //если все хорошо, пополняем баланс
                                    if($res) $res = $res && $db->query("UPDATE ".$sys_tables['users']." SET balance = balance + ".$info['sum']." WHERE id = ?",$info['id_target_user']);
                                    Response::SetBoolean('admin_transaction',true);
                            }
                            Response::SetBoolean('saved', $res); // результат сохранения
                        } else Response::SetBoolean('errors', true); // признак наличия ошибок
                    }
                    // запись данных для отображения на странице
                    Response::SetArray('data_mapping',$mapping['replenish_balance']);
                    Response::SetBoolean('not_show_submit_button',true);//чтобы после form_default не было кнопки
                    $module_template = 'admin.users.balance.html';
                }
                break;
            //убираем все в архив и снимаем с выгрузки
            case 'turn_off':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $id = Convert::ToInt($id);
                if(empty($id)){
                    $ajax_result['ok'] = false;
                    break;
                }
                $res = true;
                
                //агентства - еще снимаем флаг выгрузки
                if($this_page->page_parameters[1] == 'agencies'){
                    //записываем пользователя который провел операцию
                    Common::LogAgencyOperation($auth->id,$id,4,"");
                    $res = $db->query("UPDATE ".$sys_tables['agencies']." SET xml_status = 2 WHERE id = ?",$id);
                    $ids = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']." WHERE id_agency = ".$id)['ids'];
                } 
                
                $res = $res && $db->query("UPDATE ".$sys_tables['build']." SET published = 2 WHERE id_user IN (".$ids.") AND published = 1;");
                $res = $res && $db->query("UPDATE ".$sys_tables['live']." SET published = 2 WHERE id_user IN (".$ids.") AND published = 1;");
                $res = $res && $db->query("UPDATE ".$sys_tables['commercial']." SET published = 2 WHERE id_user IN (".$ids.") AND published = 1;");
                $res = $res && $db->query("UPDATE ".$sys_tables['country']." SET published = 2 WHERE id_user IN (".$ids.") AND published = 1;");
                
                $ajax_result['ok'] = $res;
                break;
            //подробная информация по количеству объектов в базе (разбиение по типам сделок, лимиты)
            case 'details':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $id = Convert::ToInt($id);
                if(empty($id)){
                    $ajax_result['ok'] = false;
                    break;
                }
                if($worktable == 'agencies'){
                    $user_ids = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']." WHERE id_agency = ".$id)['ids'];
                    $limits = $db->fetch("SELECT * FROM ".$sys_tables['agencies']." WHERE id = ".$id);
                    $id_agency = $id;
                }else $user_ids = $id;
                $amounts = $db->fetchall("SELECT 'build' AS type, 'sell' AS rent, id_user, COUNT(*) AS amount 
                                          FROM ".$sys_tables['build']." WHERE id_user IN (".$user_ids.") AND published=1 GROUP BY id_user,rent
                                          UNION SELECT 'live' AS type, IF(rent = 1,'rent','sell') AS rent, id_user, COUNT(*) AS amount 
                                          FROM ".$sys_tables['live']." WHERE id_user IN (".$user_ids.") AND published=1 GROUP BY id_user,rent
                                          UNION SELECT 'commercial' AS type, IF(rent = 1,'rent','sell') AS rent, id_user, COUNT(*) AS amount 
                                          FROM ".$sys_tables['commercial']." WHERE id_user IN (".$user_ids.") AND published=1 GROUP BY id_user,rent
                                          UNION SELECT 'country' AS type, IF(rent = 1,'rent','sell') AS rent, id_user, COUNT(*) AS amount 
                                          FROM ".$sys_tables['country']." WHERE id_user IN (".$user_ids.") AND published=1 GROUP BY id_user,rent");
                
                //записываем то что прочитали по пользователям
                foreach($amounts as $key=>$item){
                    $user_info[$item['type']."_".$item['rent']] = $item['amount'];
                    $user_info[$item['type']."_".$item['rent']."_limit"] = $limits[$item['type'].($item['type']=='build'?"":"_".$item['rent'])."_objects"];
                    if(empty($user_info[$item['type']."_".$item['rent']."_limit"]) && $limits['id_tarif'] == 7) $user_info[$item['type']."_".$item['rent']."_limit"] = '&infin;';
                    $user_info['has_active'] = true;
                }
                if($ajax_mode){
                    //field_num - номер столбца, который будем обновлять
                    //field_update - новая информация для столбца
                    $ajax_result = array('ok' => true, 'ids'=>array($id),'field_num'=>3,'field_update'=>(!empty($user_info)?$user_info:""));
                    break;
                }
                $res = true;
                break;
            //выгрузка XML по нажатию кнопки
            case 'load':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $id = Convert::ToInt($id);
                $agency_id = $db->fetch("SELECT id FROM ".$sys_tables['agencies']." WHERE id = ?",$id);
                $id = (!empty($agency_id) && !empty($agency_id['id']) ? $agency_id['id'] : 0);
                if(empty($id) || !$ajax_mode || $worktable != 'agencies'){
                    $ajax_result['ok'] = false;
                    break;
                }
                
                //асинхронно запускали скрипт выгрузки раньше
                //$exec_result = shell_exec("php ".ROOT_PATH."/cron/robot/xml_parser.php ".$id." > /dev/null &");
                //теперь - просто ставим галочку в таблице
                $exec_result = $db->query("UPDATE ".$sys_tables['agencies']." SET can_download = 1 WHERE id = ?",$id);
                //$ajax_result['exec_result'] = !empty($exec_result);
                $ajax_result['ok'] = true;
                break;
            //страница администратора для модерирования транзакций
            case 'transactions':
                $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                if($ajax_mode){
                    $tr_id = empty($this_page->page_parameters[4]) ? "" : $this_page->page_parameters[4];
                    switch($action){
                        case 'approve':
                            $tr_info = $db->fetch("SELECT * FROM ".$sys_tables['admin_finances']." WHERE id = ".$tr_id);
                            if($tr_info['sum'] < 0)
                                $res = $db->query("INSERT INTO ".$sys_tables['users_finances']." (`id_user`,`obj_type`,`expenditure`,`paygate`,`id_initiator`)
                                                                 VALUES (?,'balance',?,1,?)",$tr_info['id_target_user'],abs($tr_info['sum']),$tr_info['id_user']);
                            else 
                                $res = $db->query("INSERT INTO ".$sys_tables['users_finances']." (`id_user`,`obj_type`,`income`,`paygate`,`id_initiator`)
                                                                 VALUES (?,'balance',?,1,?)",$tr_info['id_target_user'],$tr_info['sum'],$tr_info['id_user']);
                            //если все хорошо, пополняем баланс
                            if($res) $res = $res && $db->query("UPDATE ".$sys_tables['users']." SET balance = balance + ".$tr_info['sum']." WHERE id = ?",$tr_info['id_target_user']);
                            if($res){
                                $ajax_result['ok'] = $res && $db->query("DELETE FROM ".$sys_tables['admin_finances']." WHERE id = ?",$tr_id);
                                $ajax_result['ids'] = array($tr_id);
                            } 
                            break;
                        case 'delete':
                            $ajax_result['ok'] = $db->query("DELETE FROM ".$sys_tables['admin_finances']." WHERE id = ?",$tr_id);
                            $ajax_result['ids'] = array($tr_id);
                            break;
                    }
                }
                else{
                    $list = $db->fetchall("SELECT ".$sys_tables['admin_finances'].".*,
                                                  ".$sys_tables['agencies'].".title AS agency_title,
                                                  IF(".$sys_tables['users'].".id IS NULL, 0,
                                                     CONCAT('#',".$sys_tables['users'].".id,', ',IF(".$sys_tables['users'].".email = '',".$sys_tables['users'].".login,".$sys_tables['users'].".email))) AS user_title,
                                                  IF(".$sys_tables['managers'].".name IS NULL,CONCAT('пользователь #',".$sys_tables['admin_finances'].".id_user),".$sys_tables['managers'].".name) AS manager_name
                                           FROM ".$sys_tables['admin_finances']."
                                           LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['admin_finances'].".id_target_user
                                           LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                           LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['admin_finances'].".id_user = ".$sys_tables['managers'].".bsn_id_user");
                    Response::SetArray('list',$list);
                    $module_template = "admin.transactions.list.html";
                }
                break;
            /***************\
            |*  Промокоды  *|
            \**************/
            case 'promocodes':
                $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                switch($action){
                    /*****************************\
                    |*  Использовнные промокоды  *|
                    \*****************************/
                    case 'used':
                        $module_template = 'admin.promocodes_used.list.html';
                        // формирование списка
                        $conditions = [];
                        if(!empty($filters)){
                            if(!empty($filters['title'])) $conditions[] = "`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                            if(!empty($filters['date'])) $conditions[] = "(`datetime` LIKE '".$db->real_escape_string($filters['date'])."%' OR DATE_FORMAT(`datetime`, '%d.%m.%Y %H:%i:%s') LIKE '".$db->real_escape_string($filters['date'])."%')";
                        }
                        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
                        else $condition = '';
                        // создаем пагинатор для списка
                        $paginator = new Paginator($sys_tables['promocodes_used'], 30);
                        // get-параметры для ссылок пагинатора
                        $get_in_paginator = [];
                        foreach($get_parameters as $gk=>$gv){
                            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                        }
                        // ссылка пагинатора
                        $paginator->link_prefix = '/admin/access/users/promocodes/promocodes_used'                  // модуль
                                                  ."/?"                                       // конечный слеш и начало GET-строки
                                                  .implode('&',$get_in_paginator)             // GET-строка
                                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                        if($paginator->pages_count>0 && $paginator->pages_count<$page){
                            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                            exit(0);
                        }

                        $sql = "SELECT * FROM ".$sys_tables['promocodes_used'];
                        if(!empty($condition)) $sql .= " WHERE ".$condition;
                        $sql .= " LIMIT ".$paginator->getLimitString($page); 
                        $list = $db->fetchall($sql);
                        // формирование списка
                        Response::SetArray('list', $list);
                        Response::SetArray('paginator', $paginator->Get($page));
                        break;
                    /****************\
                    |*  Промокоды   *|
                    \****************/
                    case 'add':
                    case 'edit':
                        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
                        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
                        $GLOBALS['js_set'][] = '/js/datetimepicker/jquery.datetimepicker.js';
                        $GLOBALS['css_set'][] = '/js/datetimepicker/jquery.datetimepicker.css';

                        $module_template = 'admin.promocodes.edit.html';
                        $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                        if($action=='add'){
                            // создание болванки новой записи
                            $info = $db->prepareNewRecord($sys_tables['promocodes']);
                        } else {
                            // получение данных из БД
                            $info = $db->fetch("SELECT *
                                                FROM ".$sys_tables['promocodes']." 
                                                WHERE id=?", $id);
                        }
                        // перенос дефолтных (считанных из базы) значений в мэппинг формы
                        foreach($info as $key=>$field){
                            if(!empty($mapping['promocodes'][$key])) $mapping['promocodes'][$key]['value'] = $info[$key];
                        }
                        if($info['type'] == 1){
                           $mapping['promocodes']['percent']['hidden'] = true;
                           $mapping['promocodes']['min_summ']['hidden'] = true;
                           $mapping['promocodes']['summ']['hidden'] = false;
                        } else {
                            $mapping['promocodes']['percent']['hidden'] = false;
                            $mapping['promocodes']['min_summ']['hidden'] = false;
                            $mapping['promocodes']['summ']['hidden'] = true;
                        }
                        
                        // получение данных, отправленных из формы
                        $post_parameters = Request::GetParameters(METHOD_POST);

                        // если была отправка формы - начинаем обработку
                        if(!empty($post_parameters['submit'])){
                            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                            foreach($post_parameters as $key=>$field){
                                if(!empty($mapping['promocodes'][$key])) $mapping['promocodes'][$key]['value'] = $post_parameters[$key];
                            }
                            // проверка значений из формы
                            $errors = Validate::validateParams($post_parameters,$mapping['promocodes']);
                            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                            foreach($errors as $key=>$value){
                                if(!empty($mapping['promocodes'][$key])) $mapping['promocodes'][$key]['error'] = $value;
                            }
                            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                            if(empty($errors)) {
                                // подготовка всех значений для сохранения
                                foreach($info as $key=>$field){
                                    if(isset($mapping['promocodes'][$key]['value'])) $info[$key] = $mapping['promocodes'][$key]['value'];
                                }
                                // сохранение в БД
                                if($action=='edit'){
                                    $res = $db->updateFromArray($sys_tables['promocodes'], $info, 'id');
                                } else {
                                    $res = $db->insertFromArray($sys_tables['promocodes'], $info, 'id');
                                    if(!empty($res)){
                                        $new_id = $db->insert_id;
                                        
                                        // редирект на редактирование свеженькой страницы
                                        if(!empty($res)) {
                                            header('Location: '.Host::getWebPath('/admin/access/users/promocodes/edit/'.$new_id.'/'));
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
                        Response::SetArray('data_mapping',$mapping['promocodes']);
                        break;

                    default:
                        $module_template = 'admin.promocodes.list.html';
                        // создаем пагинатор для списка
                        $paginator = new Paginator($sys_tables['promocodes'], 30);
                        // get-параметры для ссылок пагинатора
                        $get_in_paginator = [];
                        foreach($get_parameters as $gk=>$gv){
                            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                        }
                        // ссылка пагинатора
                        $paginator->link_prefix = '/admin/access/users/promocodes'                           // модуль
                                                  ."/?"                                         // конечный слеш и начало GET-строки
                                                  .implode('&',$get_in_paginator)           // GET-строка
                                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                        if($paginator->pages_count>0 && $paginator->pages_count<$page){
                            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                            exit(0);
                        }

                        $sortby = Request::GetString('sortby');
                        if(empty($sortby)) $sortby = "id>desc";
                        $sortby = explode('>',$sortby);
                        if(count($sortby) != 2) $sortby = "";
                        else{
                            $sortby[0] = preg_replace('/[^A-z\_]/','',$sortby[0]);
                            $sort_parameters = array('field'=>$sortby[0],'sort'=>$sortby[1]);
                            switch(true){
                                case $sortby[1] == 'desc': $sortby = $sortby[0]." DESC";break;
                                case $sortby[1] == 'asc': $sortby = $sortby[0]." ASC";break;
                            }
                            Response::SetArray('sort_parameters',$sort_parameters);
                            $get_parameters['sort'] = $sortby;
                        }
                        
                        
                        $sql = "SELECT * FROM ".$sys_tables['promocodes'];
                        $sql .= " ORDER BY ".$sortby;
                        $sql .= " LIMIT ".$paginator->getLimitString($page); 
                        
                        $table_head_titles = array(array('field'=>'id','text'=>"id"),
                                                   array('field'=>'title','text'=>"заголовок"),
                                                   array('field'=>'code','text'=>"код"),
                                                   array('field'=>'date_start','text'=>"дата начала"),
                                                   array('field'=>'date_end','text'=>"дата окончания")
                                                   );
                        Response::SetArray('table_head_titles',$table_head_titles);
                        
                        $list = $db->fetchall($sql);
                        // формирование списка
                        Response::SetArray('list', $list);
                        if($paginator->pages_count>1){
                            Response::SetArray('paginator', $paginator->Get($page));
                        }

                    break;   
                }       
                break;     
            /***************\
            |*  Тарифы     *|
            \**************/
            case 'tarifs':
                $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                switch($action){
                    case 'edit':
                        $module_template = 'admin.tarifs.agencies.edit.html';
                        $id = empty($this_page->page_parameters[4]) ? 0 : $this_page->page_parameters[4];
                        //редактирование только для Custom тарифа
                        if($id!=1) Host::Redirect('/admin/access/agencies/tarifs/');
                        // получение данных из БД
                        $info = $db->fetch("SELECT *
                                            FROM ".$sys_tables['tarifs_agencies']." 
                                            WHERE id=?", $id);
                        // перенос дефолтных (считанных из базы) значений в мэппинг формы
                        foreach($info as $key=>$field){
                            if(!empty($mapping['tarifs_agencies'][$key])) $mapping['tarifs_agencies'][$key]['value'] = $info[$key];
                        }
                        
                        // получение данных, отправленных из формы
                        $post_parameters = Request::GetParameters(METHOD_POST);

                        // если была отправка формы - начинаем обработку
                        if(!empty($post_parameters['submit'])){
                            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                            foreach($post_parameters as $key=>$field){
                                if(!empty($mapping['tarifs_agencies'][$key])) $mapping['tarifs_agencies'][$key]['value'] = $post_parameters[$key];
                            }
                            // проверка значений из формы
                            $errors = Validate::validateParams($post_parameters,$mapping['tarifs_agencies']);
                            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                            foreach($errors as $key=>$value){
                                if(!empty($mapping['tarifs_agencies'][$key])) $mapping['tarifs_agencies'][$key]['error'] = $value;
                            }
                            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                            if(empty($errors)) {
                                // подготовка всех значений для сохранения
                                foreach($info as $key=>$field){
                                    if(isset($mapping['tarifs_agencies'][$key]['value'])) $info[$key] = $mapping['tarifs_agencies'][$key]['value'];
                                }
                                // сохранение в БД
                                $res = $db->updateFromArray($sys_tables['tarifs_agencies'], $info, 'id');
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
                        Response::SetArray('data_mapping',$mapping['tarifs_agencies']);
                        break;

                    default:
                        $module_template = 'admin.tarifs.agencies.list.html';
                        // создаем пагинатор для списка
                        $paginator = new Paginator($sys_tables['tarifs_agencies'], 30);
                        // get-параметры для ссылок пагинатора
                        $get_in_paginator = [];
                        foreach($get_parameters as $gk=>$gv){
                            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                        }
                        // ссылка пагинатора
                        $paginator->link_prefix = '/admin/access/users/tarifs_agencies'                           // модуль
                                                  ."/?"                                         // конечный слеш и начало GET-строки
                                                  .implode('&',$get_in_paginator)           // GET-строка
                                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                        if($paginator->pages_count>0 && $paginator->pages_count<$page){
                            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                            exit(0);
                        }

                        $sql = "SELECT * FROM ".$sys_tables['tarifs_agencies'];
                        $sql .= " ORDER BY id DESC";
                        $sql .= " LIMIT ".$paginator->getLimitString($page); 
                        $list = $db->fetchall($sql);
                        // формирование списка
                        Response::SetArray('list', $list);
                        if($paginator->pages_count>1){
                            Response::SetArray('paginator', $paginator->Get($page));
                        }

                    break;   
                }       
                break;                      
            //список агентств - головных офисов 
            case 'heads':
                if(!$ajax_mode){
                    $this_page->http_code = 404;
                    break;
                }else{
                    $this_id = (int)$this_page->page_parameters[3];
                    $search_string = Request::GetString('search_string',METHOD_POST);
                    $list = $db->fetchall("SELECT id, CONCAT(title,' - ',addr) AS title
                                           FROM ".$sys_tables['agencies']."
                                           WHERE title LIKE '%".$search_string."%' AND id_main_office = 0 AND id != ".$this_id);
                    $ajax_result['list'] = $list;
                    $ajax_result['ok'] = true;
                }
                break;
            //транзакции    
            case 'agencies_operations':
                $list = $db->fetchall("SELECT ".$sys_tables['agencies_operations'].".*,
                                              ".$sys_tables['agencies_operation_types'].".title AS operation_title,
                                              ".$sys_tables['users'].".id AS user_id,
                                              ".$sys_tables['agencies'].".id AS agency_id,
                                              CONCAT(".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname) AS user_title,
                                              ".$sys_tables['agencies'].".title AS agency_title
                                       FROM ".$sys_tables['agencies_operations']."
                                       LEFT JOIN ".$sys_tables['agencies_operation_types']." ON ".$sys_tables['agencies_operations'].".id_operation = ".$sys_tables['agencies_operation_types'].".id
                                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies_operations'].".id_user = ".$sys_tables['users'].".id
                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies_operations'].".id_agency = ".$sys_tables['agencies'].".id
                                       ORDER BY ".$sys_tables['agencies_operations'].".`datetime` DESC");
                Response::SetArray('list',$list);
                $module_template = "admin.agencies.operations_list.html";
                break;
            //статистика XML выгрузок
            case 'xml_stats':
                $managers = $db->fetchall("SELECT id,name as title FROM ".$sys_tables['managers']." WHERE bsn_id_user > 0 ORDER BY name");
                Response::SetArray('managers', $managers);
                $conditions = [];
                if(!empty($get_parameters)){
                    if(!empty($filter_manager)) $conditions['f_published'] =  $sys_tables['managers'].".`id` = ".$db->real_escape_string($filter_manager);
                    if(!empty($filter_status)) $conditions['f_status'] = $sys_tables['processes'].".`status` ".($filter_status == 3 ? " IS NULL " : "= ".$db->real_escape_string($filter_status));
                }
                $condition = implode(" AND ",$conditions);

                $list = $db->fetchall("SELECT 
                                              ".$sys_tables['processes'].".*,
                                              ".$sys_tables['agencies'].".*,
                                              ".$sys_tables['managers'].".name as manager_name,
                                              DATE_FORMAT(".$sys_tables['agencies'].".`xml_time`,'%k:%i') as xml_time,
                                              DATE_FORMAT(".$sys_tables['processes'].".`datetime_start`,'%k:%i') as datetime_start,
                                              DATE_FORMAT(".$sys_tables['processes'].".`datetime_end`,'%k:%i') as datetime_end
                                       FROM ".$sys_tables['agencies']."
                                       LEFT JOIN ".$sys_tables['processes']." ON ".$sys_tables['processes'].".id_agency = ".$sys_tables['agencies'].".id AND DATE(".$sys_tables['processes'].".datetime_start) = CURDATE() AND ".$sys_tables['processes'].".type = 2
                                       LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                                       WHERE ".$sys_tables['agencies'].".xml_status = 1 ".(!empty($condition) ? " AND ".$condition : "")."
                                       GROUP BY ".$sys_tables['agencies'].".id
                                       ORDER BY ".$sys_tables['agencies'].".xml_time");      
                Response::SetArray('list',$list);
                Response::SetArray('paginator',array('items_count'=>count($list)));
                $module_template = "admin.agencies.xml_stats.html";
                break;
            default:
                // формирование списка
                $condition = [];
                
                //фильтр с быстрыми ссылками для агентств
                $agencies_activities = Config::$values['agencies_activities'];
                //агентства, исключаемые из результатов поиска при поиске по виду деятельности (частные лица и офисы других)
                $exclude_agencies=$sys_tables['agencies'].".title NOT LIKE '%частн%' AND id_main_office = 0";
                
                if(!empty($filter)){
                    //нужно, так как в LIKE '_' обозначает любой один символ
                    $filter=preg_replace('/_/','\\_',$filter);
                    if($worktable=='users') $condition[]=" login LIKE '%".$db->real_escape_string($filter)."%'";
                    elseif($worktable=='agencies') $condition[] = " ".$sys_tables[$worktable].".title LIKE '%".$db->real_escape_string($filter)."%'";
                    else $condition[] = " name LIKE '%".$db->real_escape_string($filter)."%'";
                }
                //нужно, так как в LIKE '_' обозначает любой один символ
                $filter_email=preg_replace('/_/','\\_',$filter_email);
                if (!empty($filter_phone)) $condition[]= "  REPLACE( REPLACE( REPLACE( REPLACE(phone, '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%" . $db->real_escape_string( preg_replace( '/[^0-9]/','', $filter_phone ) ) ."%'";
                if (!empty($filter_advert_phone)) {
                    if($filter_advert_phone==1) $condition[]= " advert_phone !=''";
                    else $condition[]= " advert_phone =''";
                }
                
                if (!empty($filter_tarif)) {
                    if($worktable == 'agencies'){
                        switch(true){
                            case $filter_tarif == 'yes': $condition[]= $sys_tables['agencies'].".id_tarif > 0 ";break;
                            case $filter_tarif == 'no': $condition[]= $sys_tables['agencies'].".id_tarif = 0 "; break;
                            case Validate::isDigit($filter_tarif): $condition[]= $sys_tables['agencies'].".id_tarif = ".Convert::ToInt($filter_tarif)." "; break;
                        }
                    }else $condition[]= $sys_tables['users'].".id_tarif = ".$filter_tarif;
                }
                if(!empty($filter_manager)) $condition[] =  $sys_tables['managers'].".`id` = ".$db->real_escape_string($filter_manager);    
                if (!empty($filter_email)) $condition[]= $sys_tables[$worktable].".email LIKE '%".$db->real_escape_string($filter_email)."%'";
                if (!empty($filter_group)) $condition[]= " id_group=".$db->real_escape_string($filter_group);
                if (!empty($filter_blocked)) $condition[]= " is_blocked=".$db->real_escape_string($filter_blocked);
                $paginator_tablename = $sys_tables[$worktable];
                //для агентств смысл filter_id другой
                if($worktable == 'agencies'){
                    $paginator_tablename = $sys_tables[$worktable]." 
                                           LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                           LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                    ";
                    
                    if (!empty($filter_id)) $condition[] = " ".$sys_tables['users'].".id = ".$db->real_escape_string($filter_id);
                    if (!empty($filter_id_agency)) $condition[] = " ".$sys_tables['agencies'].".id = ".$db->real_escape_string($filter_id_agency);
                    $condition[] = " (".$sys_tables['users'].".agency_admin = 1 OR ".$sys_tables['users'].".id IS NULL) ";
                }
                elseif (!empty($filter_id)) $condition[] = " ".$sys_tables['users'].".id = ".$db->real_escape_string($filter_id);
                
                //фильтр по виду деятельности для агентств
                if (!empty($filter_activity)){
                    foreach($agencies_activities as $activity_key=>$activity){
                        if($filter_activity == $activity['url']){
                            $condition[]= $sys_tables[$worktable].".activity & ".pow(2,$activity_key);;
                            break;
                        } 
                    }
                        
                } 
                                
                $condition=(!empty($condition))?implode(' AND ',$condition):'';
                // создаем пагинатор для списка
                $paginator = new Paginator($paginator_tablename, 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/access/'                              // модуль
                                          .$worktable                                   // подраздел
                                          ."/?"                                         // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)           // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT * FROM ".$sys_tables[$worktable];
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                if($worktable=='users') {
                    //дополнительные данные для фильтра:
                    $tarifs_list = $db->fetchall("SELECT id,title,activity FROM ".$sys_tables['tarifs']);
                    Response::SetArray('tarifs_list',$tarifs_list);
                    
                    $users = new Common();
                    $list = $users -> getUsersList(30, $paginator->getFromString($page), false, $condition );
                    foreach($list as $k=>$i) $list[$k]['can_replenish_balance'] = ($i['id_agency'] == 0 || $i['agency_admin'] == 2) ;
                    Response::SetBoolean( 'can_replenish', in_array($auth->id_group,array(3,10,101,105)) );
                    $module_template = 'admin.users.list.html';
                } elseif($worktable=='agencies') {
                    $agencies = new Common();
                    foreach($agencies_activities as $key=>$val){
                        $activity_pow_mask = pow(2,$key);
                        $act = $db->fetch("SELECT COUNT(*) as cnt 
                                           FROM ".$sys_tables['agencies']." 
                                           WHERE activity & ".$activity_pow_mask." AND ".$exclude_agencies);
                        $agency_activities_counter[] =  array($agencies_activities[$key], $act['cnt'], $agencies_activities[$key]['url']);
                    }
                    
                    $activity_type = explode('/',$_SERVER['REQUEST_URI'])[2];
                    Response::SetString('activity',$activity_type);
                    Response::SetArray('activities_list',$agency_activities_counter);
                    //сортировка
                    switch($filter_sortby){
                        case 1:  $sortby = $sys_tables['agencies'].".title ASC"; break;
                        case 2:  $sortby = $sys_tables['agencies'].".title DESC"; break;
                        default: $sortby = '';
                    }
                    $list = $agencies -> getAgenciesList(30, $paginator->getFromString($page), $sortby, $condition );
                    $tarifs_agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['tarifs_agencies']);
                    Response::SetArray('tarifs_agencies',$tarifs_agencies);
                    $managers = $db->fetchall("SELECT id,name as title FROM ".$sys_tables['managers']." WHERE bsn_id_user > 0 ORDER BY name");
                    Response::SetArray('managers', $managers);
                    
                    //для не-ОП, суперадмина и поддержки убираем кнопки с балансом
                    Response::SetBoolean( 'can_replenish',(in_array($auth->id_group,array(3,10,101,105))) );
                    $module_template = 'admin.agencies.list.html';
                } else {
                    $sql .= " ORDER BY `name`";
                    $module_template = 'admin.users_groups.list.html';
                }
                if($worktable=='agencies') {
                    foreach($list as $k=>$item){
                        $list[$k]['active_objects'] = EstateStat::getAgenciesCount($item['id_user']);
                        $users_list = $db->fetchall("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ? ORDER BY agency_admin = 1 DESC", false, $item['id']);
                        $list[$k]['users'] = $users_list;
                    }
                }
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
        }
    } elseif($this_page->page_parameters[1] == 'managers'){
        /****************************\
        |*  Работа со менеджерами   *|
        \***************************/
            // переопределяем экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            switch($action){
                case 'add':
                case 'edit':
                    $GLOBALS['js_set'][] = '/js/form.validate.js';
                    $module_template = 'admin.managers.edit.html';
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    if($action=='add'){
                        // создание болванки новой записи
                        $info = $db->prepareNewRecord($sys_tables['managers']);
                        // определяем позицию
                        $row = $db->fetch("SELECT max(position) as position FROM ".$sys_tables['managers']);
                        if(!empty($row)) $info['position'] = $row['position']+1;
                    } else {
                        // получение данных из БД
                        $info = $db->fetch("SELECT *
                                            FROM ".$sys_tables['managers']." 
                                            WHERE id=?", $id);
                        if(empty($info)) Host::Redirect('/admin/access/managers/add/');
                    }
                    // перенос дефолтных (считанных из базы) значений в мэппинг формы
                    foreach($info as $key=>$field){
                        if(!empty($mapping['managers'][$key])) $mapping['managers'][$key]['value'] = $info[$key];
                    }
                    // получение данных, отправленных из формы
                    $post_parameters = Request::GetParameters(METHOD_POST);

                    // если была отправка формы - начинаем обработку
                    if(!empty($post_parameters['submit'])){
                        Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                        // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                        foreach($post_parameters as $key=>$field){
                            if(!empty($mapping['managers'][$key])) $mapping['managers'][$key]['value'] = trim($post_parameters[$key]);
                        }
                        // проверка значений из формы
                        $errors = Validate::validateParams($post_parameters,$mapping['managers']);
                        // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                        foreach($errors as $key=>$value){
                            if(!empty($mapping['managers'][$key])) $mapping['managers'][$key]['error'] = $value;
                        }
                        // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                        if(empty($errors)) {
                            // подготовка всех значений для сохранения
                            foreach($info as $key=>$field){
                                if(isset($mapping['managers'][$key]['value'])) $info[$key] = strip_tags($mapping['managers'][$key]['value'],'<a><strong><b><a><i><img><ul><li><em><p><div><span><br><h2><h3>');
                            }
                            $info['bsn_manager'] = 1;
                            // сохранение в БД
                            if($action=='edit'){
                                $res = $db->updateFromArray($sys_tables['managers'], $info, 'id');
                               
                            } else {
                                $res = $db->insertFromArray($sys_tables['managers'], $info, 'id');
                                
                                if(!empty($res)){
                                    $new_id = $db->insert_id;
                                    // редирект на редактирование свеженькой страницы
                                    if(!empty($res)) {
                                        header('Location: '.Host::getWebPath('/admin/access/managers/edit/'.$new_id.'/'));
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
                    Response::SetArray('data_mapping',$mapping['managers']);
                    break;
                case 'del':
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $res = $db->query("DELETE FROM ".$sys_tables['managers']." WHERE id=?", $id);
                    $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                    if($ajax_mode){
                        $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                        break;
                    }
                 default:
                    $module_template = 'admin.managers.list.html';
                    // создаем пагинатор для списка
                    $paginator = new Paginator($sys_tables['managers'], 30);
                    // get-параметры для ссылок пагинатора
                    $get_in_paginator = [];
                    foreach($get_parameters as $gk=>$gv){
                        if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                    }
                    // ссылка пагинатора
                    $paginator->link_prefix = '/admin/access/managers'                  // модуль
                                              ."/?"                                       // конечный слеш и начало GET-строки
                                              .implode('&',$get_in_paginator)             // GET-строка
                                              .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                    if($paginator->pages_count>0 && $paginator->pages_count<$page){
                        Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                        exit(0);
                    }

                    $results = $list = $db->fetchall(" SELECT * FROM ".$sys_tables['managers']."
                                            WHERE bsn_manager = 1
                                            ORDER BY `name`
                                            LIMIT ".$paginator->getLimitString($page));
                    // формирование списка
                    Response::SetArray('list', $list);
                    if($paginator->pages_count>1){
                        Response::SetArray('paginator', $paginator->Get($page));
                    }
            }
        } else if($this_page->page_parameters[1] == 'superadmin'){
            $id = Convert::ToInteger($this_page->page_parameters[2]);
            if(!empty($id)){
                $user = $db->fetch('SELECT * FROM '.$sys_tables['users']." WHERE id = ? " . ( empty(DEBUG_MODE) ? " AND id_group IN (1,104,8,105) " : "" ) , $id);
                if(!empty($user)){
                    $auth->checkSuperAdminAuth($id);
                    if(!empty($auth->authorized)) Host::Redirect( Host::$root_url . '/members/' );
                }
            }
        } else {
            // если не users и не groups - идем в начало
            Header('Location: '.Host::getWebPath('/admin/access/'));
            exit(0);
    }
    // отдаем в шаблоны результаты отработки экшнов
    if(!empty($results)) Response::SetArray('results',$results);
}
// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));
?>