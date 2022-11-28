<?php
require_once('includes/class.paginator.php');
require_once('includes/class.calendar.php');
$calendar = new Calendar( );

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

//формирование меню 2 уровня
// проверка наличия в кэше
$menu = $memcache->get('bsn::menu::calendar-years-list');
if($menu === FALSE) {
    $menu = $db->fetchall("SELECT DISTINCT(YEAR(`date_begin`)) as `year` FROM ".$sys_tables['calendar_events']." WHERE date_begin IS NOT NULL AND YEAR(`date_begin`) > 2000 ORDER BY `date_begin` DESC");
    $memcache->set('bsn::menu::calendar-years-list', $menu, FALSE, Config::$values['blocks_cache_time']['menu']);
}
foreach($menu as $key=>$item){
    $this->menuAdd($item['year']." год", 'calendar/y/'.$item['year'], 2);
}

$GLOBALS['js_set'][] = '/modules/calendar_events/item.js';

//записей на страницу
$strings_per_page = 15;
//от какой записи вести отчет
$from=0;
// обработка общих action-ов
switch(true){
    /////////////////////////////////////////////////////
    // Попап с формой
    /////////////////////////////////////////////////////
    case $action == 'popup':
        $id = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        Response::SetInteger( 'id', $id );
        $module_template = 'popup.html';
        $ajax_result['ok'] = true;
        break;                              
    /////////////////////////////////////////////////////
    // Попап с формой
    /////////////////////////////////////////////////////
    case $action == 'registration':

        $form_data = Request::GetParameters( METHOD_POST );
        
        // если была отправка формы
        if(!empty($form_data)){
            $item = $db->fetch(
                "SELECT *,
                       DATE_FORMAT(`date_begin`,'%e') as `daybegin`, 
                       DATE_FORMAT(`date_begin`,'%M') as `monthbegin` 
                FROM ".$sys_tables['calendar_events']." 
                WHERE id = ?", $form_data['id'] 
            );
            
            //проверяем на коррректность введенные значения
            //$text будет накапливать параметры, введенные пользователем
            $text="";
            $errors = [];
            if (empty($form_data['name'])) $errors['name'] = 'Не допускается пустое значение';
            
            if (empty($form_data['email']) || !Validate::isEmail($form_data['email'])) $errors['email'] = 'Недопустимое значение';
            
            if (empty($form_data['phone']) || !Validate::isPhone($form_data['phone'])) $errors['phone'] = 'Недопустимое значение';
            
            $ajax_result['error'] = $errors;
            if(empty($errors)){
                //записываем в БД
                $form_data['id_parent'] = $item['id'];
                $res = $db->insertFromArray($sys_tables['calendar_events_registrations'],$form_data,false,false,true);
                if(empty($res)){
                    $ajax_result['error'] = "db error";
                    $ajax_result['ok'] = true;
                }
                else{
                     $ajax_result['ok'] = true;
                     
                     //отправка письма ответственному менеджеру
                     if(!empty($item['manager_email'])){
                        $mailer = new EMailer('mail');
                        $data = array("info" => $form_data,"item" => $item);
                        $mailer->sendEmail(array("web@bsn.ru",$item['manager_email']),
                                           array("Миша",""),
                                           "Новая регистрация на мероприятие на BSN.ru",
                                           '/modules/calendar_events/templates/mail.html',
                                           "",
                                           $data,
                                           false,
                                           false,
                                           true);
                     }
                     
                     $mailer = new EMailer('mail');
                     $data = array("info" => $form_data, "item" => $item);
                     $mailer->sendEmail($form_data['email'],
                                        "",
                                        "Регистрация на мероприятие «".$item['title']."»",
                                        '/modules/calendar_events/templates/mail.user.html',
                                        "",
                                        $data,
                                        false,
                                        false);
                                        
                    Response::SetString( 'title', 'Спасибо за регистрацию!' );
                    $module_template = '/templates/popup.success.html';
                    $ajax_result['ok'] = true;
                                        
                 }
            }
        }
        break;    
        
    /////////////////////////////////////////////////////
    // список новостей на главную страницу
    /////////////////////////////////////////////////////
    case $action=='block': 
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1]; 
        switch(true){
                case $action == 'mainpage':
                    $list = $db->fetchall("SELECT 
                                                *,
                                                DATE_FORMAT(`date_begin`,'%e') as `daybegin`, DATE_FORMAT(`date_end`,'%e') as `dayend`
                                           FROM ".$sys_tables['calendar_events']." 
                                           WHERE 
                                                `date_end` >= CURDATE()
                                           ORDER BY `date_begin` ASC
                                           LIMIT 3"
                    );   
                    Response::SetArray('list',$list);
                    $module_template = "block.mainpage.html";
                    $ajax_result['ok'] = true;
                    //время жизни memcache
                    $this_page->page_cache_time = Config::$values['blocks_cache_time']['calendar_block'];                
                    break;
                default:
                    if(!$this_page->first_instance || $ajax_mode) {
                        $page_parameters = Request::GetParameters();
                        $current_year = $page_parameters['year'];
                        $current_month = $page_parameters['month'];
                        if(!empty($current_year)){
                            $where = "
                                  
                                `date_begin` < CURDATE() AND 
                                (
                                    (
                                        (`date_begin` <= DATE('".$current_year."-".$current_month."-01' + INTERVAL 1 MONTH) ) 
                                        AND 
                                        (`date_end` >= '".$current_year."-".$current_month."-01')
                                        AND `date_begin` <= `date_end`
                                    )
                                    OR
                                    (
                                        `date_end`='0000-00-00' AND `date_begin` BETWEEN  DATE('".$current_year."-".$current_month."-01') AND DATE('".$current_year."-".$current_month."-01' + INTERVAL 1 MONTH)
                                    )
                                )
                            ";
                            $list = $calendar->getList(false, false, $where );
                            Response::SetArray('list', $list);
                            $module_template = "list.html";
                            $ajax_result['ok'] = true;
                            //время жизни memcache
                            $this_page->page_cache_time = Config::$values['blocks_cache_time']['calendar_block'];
                        }
                    } else $this_page->http_code=404;
                    break;
        }
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // главная календаря
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case empty($action): 
            if(!empty($this_page->page_parameters[2])) { $this_page->http_code = 404;}
            else{
                $GLOBALS['css_set'][] = '/css/content.css';
                $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
                $GLOBALS['css_set'][] = '/modules/calendar_events/style.css';
                $GLOBALS['js_set'][] = '/modules/calendar_events/script.js';
                $GLOBALS['js_set'][] = '/modules/search/script.js';
                $GLOBALS['css_set'][] = '/modules/search/style.css';
                
                //определение первоначальных дат
                $current_year = date('Y');
                if( $current_year == date( 'Y' ) ) $current_month = date('m');
                else $current_month = 1;
                if( empty( $current_year ) || $current_year > date( 'Y' ) +1 ) { $this_page->http_code=404; break; }
                //предстояшие события по месяцам
                $dates = $db->fetchall("SELECT   
                                            MONTH(`date_begin`) as `month`, 
                                            YEAR(`date_begin`) as `year`,
                                            DATE_FORMAT(`date_begin`,'%M %Y') as `month_year`
                                         FROM ".$sys_tables['calendar_events']." 
                                         WHERE `date_begin` >= CURDATE()
                                         GROUP BY `month_year`
                                         ORDER BY `year`, `month`
                ");
                Response::SetArray( 'dates', $dates );
                $list = [];
                foreach( $dates as $m => $date ) {
                    $where = "
                        (
                            (`date_begin` >= CURDATE() OR `date_end` >= CURDATE() )
                            AND 
                            (
                                (`date_begin` < DATE('" .$date['year']. "-" .$date['month']. "-01' + INTERVAL 1 MONTH) ) 
                                AND 
                                (`date_end` >= '" .$date['year']. "-" .$date['month']. "-01')
                                AND `date_begin` <= `date_end`
                            )
                        )
                        OR
                        (
                        `date_end`='0000-00-00' AND `date_begin` BETWEEN  DATE('" .$date['year']. "-" .$date['month']. "-01') AND DATE('" .$date['year']. "-" .$date['month']. "-01' + INTERVAL 1 MONTH)
                        )

                    ";
                    $events = $calendar->getList(false, false, $where );
                    $list[ $date['month_year'] ] = $events;
                }
                
                Response::SetArray( 'list', $list );

                $module_template = 'mainpage.html';

                //список фильтров (месяц-год)
                $date_list = $calendar->getMonthsList();
                Response::SetArray('date_list', $date_list);

                //расширенный формат
                Response::SetBoolean( 'payed_format', true );
                //тип контента для поиска
                Response::SetString( 'content_type', 'calendar_events' );
                //добавление meta и h1 title
                $h1 = empty($this_page->page_seo_h1) ? 'Календарь событий рынка недвижимости' : $this_page->page_seo_h1;
                Response::SetString('h1_title',$h1);
                $new_meta = array('title'=>'События рынка недвижимости  '.$current_year.' года', 'keywords'=>'События рынка недвижимости  '.$current_year.' года');
                $this_page->manageMetadata($new_meta, true);
            }
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // архив
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'archive':
        $GLOBALS['css_set'][] = '/modules/calendar_events/style.css';
        $date_list = $calendar->getMonthsList();
        Response::SetArray('date_list', $date_list);
        $page_parameters = Request::GetParameters();
        Response::SetInteger( 'current_year', !empty( $page_parameters['year'] ) ? $page_parameters['year'] : date('Y') );
        Response::SetInteger( 'current_month', !empty( $page_parameters['month'] ) ? $page_parameters['month'] : date('m') );
        Response::SetBoolean( 'payed_format', true );
        
        $module_template = "archive.html";
        break;        
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // карточка
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case empty($ajax_mode) && !empty($action):

             if(count($this_page->page_parameters)>1) {$this_page->http_code=404; break;}

             if(Validate::isDigit($this_page->page_parameters[0])){
                 $res = $db->fetch("SELECT chpu_title FROM ".$sys_tables['calendar_events']." WHERE id=?", $this_page->page_parameters[0]);
                 if(empty($res)){$this_page->http_code=404; break;}
                 Host::Redirect("calendar/".$res['chpu_title']);   
             }  else {
                 $calendar_events_id = preg_split("/\_/",$this_page->page_parameters[0],2);
                 if(!Validate::isDigit($calendar_events_id[0])){$this_page->http_code=404; break;}
                 $calendar_events_id = $calendar_events_id[0];
             }
             if($calendar_events_id && empty($this_page->page_parameters[1])){
                 $item = $db->fetch("SELECT 
                                    *, 
                                    YEAR(`date_begin`) as `year`, 
                                    IF(  MONTH(`date_end`) = 0 OR MONTH(`date_begin`) !=  MONTH(`date_end`),
                                        DATE_FORMAT(`date_begin`,'%e %M'),
                                        IF( MONTH(`date_begin`) =  MONTH(`date_end`) AND `date_end` = `date_begin`,
                                            DATE_FORMAT(`date_begin`,'%e %M'),
                                            DATE_FORMAT(`date_begin`,'%e')
                                        )
                                    ) as `datebegin`, 
                                    MONTH(`date_end`),
                                    IF(`date_end`>`date_begin`, DATE_FORMAT(`date_end`,'%e %M'),'') as `dateend` ,
                                    IF(`date_end`<NOW(),1,0) as past_event,
                                    IF(registration = 1,true,false) AS registration_opened,
                                    (SELECT COUNT(*) FROM ".$sys_tables['comments']." WHERE comments_active = 1 AND parent_type = 3 AND id_parent = ".$sys_tables['calendar_events'].".id) as comments_count
                                 FROM ".$sys_tables['calendar_events']." WHERE `id` = ".$calendar_events_id);
                 if(empty($item)) { $this_page->http_code=404; break; }
                
                //комментарии новости
                $GLOBALS['js_set'][] = '/modules/comments/script.js';
                
                $GLOBALS['js_set'][] = '/js/form.validate.js';
                $GLOBALS['css_set'][] = '/css/content.css';
                $GLOBALS['css_set'][] = '/modules/comments/style.css';
                $GLOBALS['css_set'][] = '/modules/calendar_events/style.css';
                $comments_data = array('page_url'    =>  '/'.$this_page->real_url.'/',
                                  'id_parent'   =>  $calendar_events_id,
                                  'parent_type' =>  'calendar_events'
                                );
                Response::SetArray('comments_data', $comments_data);             
                 
                 Response::SetArray('item', $item);
                 //фотогалерея
                 Response::SetString('img_folder',Config::$values['img_folders']['calendar_events']);
                 $photos = Photos::getList('calendar_events',$calendar_events_id);
                 Response::SetArray('photos',$photos);
                 //добавление meta и h1 title
                 $h1 = empty($this_page->page_seo_h1) ? $item['title'] : $this_page->page_seo_h1;
                 Response::SetString('h1_title',$h1);
                 $new_meta = array('title'=>$item['title'].' - События рынка недвижимости', 'keywords'=>$item['title']);
                 $this_page->manageMetadata($new_meta, true);            
                 $module_template = 'item.html';
                 //кол-во просмотров
                 $db->querys("UPDATE ".$sys_tables['calendar_events']." SET views_count = views_count + 1 WHERE id = ?", $calendar_events_id);
                
                 $where = $sys_tables['calendar_events'] . '.id != ' . $calendar_events_id . ' 
                                AND ' . $sys_tables['calendar_events'] . '.id_category = ' . $item['id_category'] . '
                                AND ( `date_begin` >= CURDATE() OR `date_end` >= CURDATE() )';
                 $other_events = $calendar->getList(6, 0, $where );                         
                 Response::SetArray('other_events',$other_events);
             } else  { $this_page->http_code=404; break; }
             
        break;
    //блок "ближайшее событие"
    case $ajax_mode && $action == 'nearest_event' && empty($this_page->page_parameters[1]):
        Response::SetString('img_folder',Config::$values['img_folders']['calendar_events']);
        $nearest = $db->fetch("SELECT ".$sys_tables['calendar_events'].".id,".$sys_tables['calendar_events'].".title,chpu_title,place,
                                      DATE_FORMAT(`date_begin`,'%e') as `daybegin`, DATE_FORMAT(`date_end`,'%e') as `dayend`,
                                      DATE_FORMAT(`date_begin`,'%M') as `monthbegin`, DATE_FORMAT(`date_end`,'%M') as `monthend`,
                                      IF(registration = 1,1,0) AS registration_opened,
                                      ".$sys_tables['calendar_events_photos'].".`name` as `photo`, 
                                      LEFT (".$sys_tables['calendar_events_photos'].".`name`,2) as `photo_subfolder`,
                                      CONCAT(LEFT (".$sys_tables['calendar_events'].".`text`,160),'...') as `description_short`,
                                      IF(date_end < NOW(),1,0) as past_event,
                                      registration_url
                               FROM ".$sys_tables['calendar_events']."
                               LEFT JOIN ".$sys_tables['calendar_events_photos']." ON ".$sys_tables['calendar_events'].".id_main_photo = ".$sys_tables['calendar_events_photos'].".id
                               ORDER BY date_begin ASC");
        $nearest['description_short'] = strip_tags($nearest['description_short']);
        Response::SetArray('event',$nearest);
        $module_template = "block.nearest.html";
        $ajax_result['ok'] = true;
        break;
    default:
        $this_page->http_code=404;
        break;
}
?>