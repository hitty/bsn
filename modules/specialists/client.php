<?php
require_once('includes/class.paginator.php');
require_once('includes/class.common.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$action_letter = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
$subaction = '';

//специализации пользователей
$users_specs = Config::Get('users_specializations');
$users_specs_aliases = [];

foreach($users_specs as $key=>$value){
    $alias = Convert::ToTranslit($value);
    $users_specs[$key] = array('title'=>$value,'url'=>$alias);
    $users_specs_aliases[] = $alias;
}

//рынки недвижимости
$agencies_estate_types = array('Жилая','Новостройки','Коммерческая','Загородная','Элитная','Зарубежная','Коттеджи','БЦ','Ипотека','Страхование');

//от какой записи вести отчет
$from=0;

//алфавиты: нужны для поиска по алфавиту
//транлитные варианты русских букв для адреса
$subst_ru = array('rA', 'rB', 'rV', 'rG', 'rD', 'rJe', 'rZh', 'rZ', 'rI', 'rK', 'rL', 'rM', 'rN', 'rO',
                      'rP', 'rR', 'rS', 'rT', 'rU', 'rF', 'rH', 'rC', 'rCh', 'rSh', 'rE', 'rJu', 'rJa');
//русские буквы
$arr_alph_ru = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'К', 'Л', 'М', 'Н', 'О',
                         'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Э', 'Ю', 'Я');
$arr_alph_en = range('A', 'Z');

//выборка по количеству активных объектов
$amounts_list = array('1_10'=>'1—10','11_20'=>'11—20','21_50'=>'21—50','50_'=>'больше 50');

//число строк-результатов поиска на странице
$strings_per_page = Cookie::GetInteger('View_count');
if(empty($strings_per_page)) $strings_per_page=Config::Get('view_settings/strings_per_page_agencies');

//условие специалиста
//$specialist_condition = " (".$sys_tables['users'].".id_tarif > 0 AND ".$sys_tables['users'].".name != '') ";
$specialist_condition = " (".$sys_tables['users'].".id_user_type = 2 AND ".$sys_tables['users'].".agency_admin != 1) ";

//выбираем первые буквы слов
$list = $db->fetchall("SELECT DISTINCT LEFT(".$sys_tables['users'].".name,1) AS letter FROM ".$sys_tables['users']." WHERE ".$specialist_condition." ORDER BY letter");

//добавляем breadcumbs
$this_page->addBreadcrumbs("Специалисты рынка недвижимости","specialists");

//сортировка для обычных страниц
if(empty($ajax_mode)){
    $sortby = Request::GetInteger('sortby',METHOD_GET);
    if(empty($sortby)) $sortby = 1;
    Response::SetInteger('sortby',$sortby);
    switch($sortby){
        case 1: $sortby = " amount DESC, answers_amount DESC "; break;
        case 2: $sortby = " amount ASC, answers_amount ASC"; break;
        case 3: $sortby = " answers_amount DESC, amount DESC "; break;
        case 4: $sortby = " answers_amount ASC, amount ASC "; break;
        default: $sortby = " amount DESC, answers_amount DESC ";
    }
}

// обработка общих action-ов
switch(true){
    case $action=='search' && empty($this_page->page_parameters[1]):
        if($ajax_mode) {
            $search_str = Request::GetString('search_string', METHOD_POST);
            $activity = Request::GetString('activity', METHOD_GET);
            $activity_mask = false;
            $where = '';
            foreach($users_specs as $key=>$value){
                if(in_array($activity, $value)) {$activity_mask = $key; break;}
            }
            $list = $db->fetchall("SELECT CONCAT(`name`,' ',`lastname`) AS title,
                                          id
                                   FROM ".$sys_tables['users']."
                                   WHERE ".$specialist_condition." AND CONCAT(`name`,' ',`lastname`) LIKE '%".$db->real_escape_string($search_str)."%' " . $where . " 
                                   ORDER BY `title` 
                                   LIMIT 10");
            $ajax_result['ok'] = true;
            $ajax_result['list'] = $list;
        } else $this_page->http_code=404;
        break;
    case $action=='block':// счетчик видов деятельности
        $filter = $this_page->page_parameters[1];
        if(!$this_page->first_instance) {
            switch(true){
            
            //###########################################################################
            // количество активных объектов
                case preg_match('/amounts/',$filter):
                    $amount_current = explode('/',$_SERVER['REQUEST_URI'])[2];
                    Response::SetArray('amounts_list',$amounts_list);
                    Response::SetString('amount_current',$amount_current);
                    $module_template = "block.amounts.html";
                    break;
            //###########################################################################
            // специализации со счетчиками
                case preg_match('/specializations/',$filter):
                    $users_specs_counter = [];
                    foreach($users_specs as $key=>$val){
                        $act = $db->fetch("SELECT COUNT(*) as cnt 
                                           FROM ".$sys_tables['users']." 
                                           WHERE ".$specialist_condition." AND MOD(specializations,POW(2,".($key)."))>=POW(2,".($key-1).")");
                        $users_specs_counter[] =  array($users_specs[$key], $act['cnt'], $users_specs[$key]['url']);
                    }
                    
                    $specialization = explode('/',$_SERVER['REQUEST_URI'])[2];
                    Response::SetString('specialization',$specialization);
                    Response::SetArray('list',$users_specs_counter);
                    $module_template = 'block.html';
                    break;
            }
        } else $this_page->http_code=404;
        break;
    //###########################################################################
    // заглавная страница специалистов
    //###########################################################################     
    case empty($action) || (in_array($action, $users_specs_aliases)): 
        $GLOBALS['css_set'][] = '/modules/specialists/styles.css';
        //подключение стилей и JS для автозаполнения
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/autocomplette.js';
        $GLOBALS['js_set'][] = '/modules/specialists/mainpage_scripts.js';

        $h1 = empty($this_page->page_seo_h1) ? 'Специалисты рынка недвижимости' : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);   

        $sortby = Request::GetInteger('sortby',METHOD_GET);
        $sortby = (empty($sortby)?1:$sortby);
        $sortby_digital = $sortby;
        switch($sortby){
            case 1: $sortby = " amount DESC, answers_amount DESC "; break;
            case 2: $sortby = " amount ASC, answers_amount ASC"; break;
            case 3: $sortby = " answers_amount DESC, amount DESC "; break;
            case 4: $sortby = " answers_amount ASC, amount ASC "; break;
            default: $sortby = " amount DESC, answers_amount DESC ";
        }
        
        //читаем список компаний
        $page = Request::GetInteger('page',METHOD_GET);
        if (isset($page)&&($page==0)){
            Host::Redirect('/'.$this_page->requested_path.'/?page=1'.(!empty($sortby)?"&sortby=".$sortby:""));
            exit(0);
        } 
        if (empty($page)) $page = 1;
        
        $where = array( $specialist_condition );

        //обработка параметров
        $parameters = Request::GetParameters( METHOD_GET );
        if( !empty( $parameters['specialist'] ) ) {
            $specialist = $db->fetch(" SELECT CONCAT(`name`,' ',`lastname`) AS title
                                       FROM ".$sys_tables['users']."
                                       WHERE id = ?", $parameters['specialist']
            );
            if( !empty( $specialist ) ) {
                $parameters['specialist_title'] =  $specialist['title'];
                $where[] = $sys_tables['users'] . ".id = " . $parameters['specialist'];
            }
        }
        Response::SetArray( 'form_data', $parameters );
        //список по специализации
        if( !empty( $action ) && in_array($action, $users_specs_aliases)) { 
            Response::SetString('activity',$action);
            $activity_mask = false;
            foreach($users_specs as $key=>$value){
                if(in_array($action, $value)) {$activity_mask = $key; break;}
            }
            $where[] = " MOD(specializations,POW(2,".($activity_mask).")) >= POW(2,".($activity_mask-1).") ";
            //добавление title
            $new_meta = array(
                'title' =>$users_specs[$activity_mask]['title'].' -  Специалисты рынка недвижимости',
                'description'=>$users_specs[$activity_mask]['title']
            );
            $this_page->manageMetadata($new_meta,true);
            // хлебные крошки для тегов
            $this_page->addBreadcrumbs( $users_specs[$activity_mask]['title'], $users_specs[$activity_mask]['url']);
            //Response::SetString('h1', empty($this_page->page_seo_h1) ? $users_specs[$activity_mask]['title'] : $this_page->page_seo_h1);
            $h1 = empty($this_page->page_seo_h1) ? 'Специалисты - ' . $users_specs[$activity_mask]['title'] : $this_page->page_seo_h1;
            Response::SetString('h1',$h1);
            
        }
        $where = implode( " AND ", $where );
        $paginator = new Paginator($sys_tables['users'], $strings_per_page, $where);
        //редирект с несуществующих пейджей
        if($page<0){
            Host::Redirect('/'.$this_page->requested_path.'/?page=1'.(!empty($sortby)?"&sortby=".$sortby:""));
            exit(0);
        }
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count.(!empty($sortby)?"&sortby=".$sortby:""));
            exit(0);
        }
        
        
        
        //формирование url для пагинатора
        $paginator->link_prefix = '/'.$this_page->requested_path.'/?'.(!empty($sortby)?"sortby=".$sortby_digital."&":"").'page=';
        
        Response::SetInteger('total_found',$paginator->items_count);
        Response::SetString('found_shown',$paginator->getFromString($page));
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        
        $list = Common::getSpecialistsList($paginator,$page,$strings_per_page,$where,$sortby);
        Response::SetArray('list',$list);
        
        //форма поиска
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        $eml_tpl = new Template('search.form.html', 'modules/specialists/');
        $html = $eml_tpl->Processing();
        Response::SetString( 'search_form_html', $html );

        $module_template = 'mainpage.html';
        break;
         
    //###########################################################################
    // выводим список специалистов по количеству объектов
    //###########################################################################
    case preg_match('/[0-9]{1,2}?_[0-9]{0,2}?[^A-z]*/',$action):
        $GLOBALS['css_set'][] = '/modules/specialists/styles.css';
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/autocomplette.js';
        $GLOBALS['js_set'][] = '/modules/specialists/mainpage_scripts.js';
        //редирект на GET паджинацию
        if(!empty($this_page->page_parameters[1]) && isPage($this_page->page_parameters[1])) 
            Host::Redirect("/specialists/".$action."/?page=".getPage($this_page->page_parameters[1]).(!empty($sortby)?"&sortby=".$sortby:"")); 
        
        //читаем ограничения на корличество
        $amount_borders = explode('_',$action);
        
        //поиск по битовой маске
        //для начала паджинатор
        $page = Request::GetInteger('page',METHOD_GET);
        if (isset($page)&&($page==0)){
            Host::Redirect('/'.$this_page->requested_path.'/?page=1'.(!empty($sortby)?"&sortby=".$sortby:""));
            exit(0);
        }
        if(empty($page)) $page = 1;
        else Response::SetBoolean('noindex',true); //meta-тег robots = noindex

        
        $where[] = " ".$specialist_condition." ";
        if(!empty($amount_borders[0])) $where[] = "(".$sys_tables['users'].".active_build +".$sys_tables['users'].".active_live + 
                                                    ".$sys_tables['users'].".active_commercial + ".$sys_tables['users'].".active_country) >= ".$amount_borders[0];
        if(!empty($amount_borders[1])) $where[] = "(".$sys_tables['users'].".active_build +".$sys_tables['users'].".active_live + 
                                                    ".$sys_tables['users'].".active_commercial + ".$sys_tables['users'].".active_country) <= ".$amount_borders[1];
        $where_paginator = implode(" AND ",$where);
        $paginator = new Paginator($sys_tables['users'],$strings_per_page,$where_paginator);
        //для паджинатора условие на то что это агентство не нужно, и так читаем с агентств
        $where = implode(" AND ",$where);
        //кол-во записей на страницу
        Response::SetInteger('total_found',$paginator->items_count);
        Response::SetString('found_shown',$paginator->getFromString($page));
        //редирект с несуществующих пейджей
        if($page<0){
            Host::Redirect('/'.$this_page->requested_path.'/?page=1'.(!empty($sortby)?"&sortby=".$sortby:""));
            exit(0);
        }
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count.(!empty($sortby)?"&sortby=".$sortby:""));
            exit(0);
        }
        //формирование url для пагинатора
        $paginator->link_prefix = '/'.$this_page->requested_path.'/?page=';
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        
        $list = Common::getSpecialistsList($paginator,$page,$strings_per_page,$where,$sortby);
        Response::SetArray('list',$list);
                
        // хлебные крошки для тегов
        $this_page->addBreadcrumbs( $amounts_list[$action], $action."/");
        //Response::SetString('h1', empty($this_page->page_seo_h1) ? $users_specs[$activity_mask]['title'] : $this_page->page_seo_h1);
        $h1 = empty($this_page->page_seo_h1) ? 'Организации рынка недвижимости' : $this_page->page_seo_h1;
        Response::SetString('h1',$h1);
        $module_template = 'list.html';
        break;
    ///////////////////////////////////////////////////            
    //список ответов для карточки юриста
    ///////////////////////////////////////////////////
    case Validate::isDigit($action) && !empty($ajax_mode) && !empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'answers_list':
        
        
        $spec_id = Convert::ToInt($action);
        
        if(!Common::getUserById($spec_id)){
            $ajax_result['ok'] = false;
            break;
        }
        
        require_once('includes/class.consults.php');
        
        $sortby = Request::GetInteger('sortby',METHOD_GET);
        
        switch($sortby){
            case 1: $sortby = " ".$sys_tables['consults_answers'].".date_in ASC"; break;
            case 2: $sortby = " ".$sys_tables['consults_answers'].".date_in DESC"; break;
            case 3: $sortby = " ".$sys_tables['consults_answers'].".rating ASC"; break;
            case 4: $sortby = " ".$sys_tables['consults_answers'].".rating DESC"; break;
            default: $sortby = " date_in ASC";
        }
        
        $page_size = 10;
        
        $page = Request::GetInteger('page', METHOD_GET);
        if(empty($page)) $page = 1;
        
        $paginator = new Paginator($sys_tables['consults_answers'], $page_size, " id_user = ".$spec_id." AND status = 1");
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        if($page > $paginator->pages_count) $page = $paginator->pages_count;
        
        $list = $db->fetchall("SELECT ".$sys_tables['consults'].".*,
                                      CONCAT('/service/consultant/',".$sys_tables['consults_categories'].".code,'/',".$sys_tables['consults'].".id,'/') AS question_url,
                                      IF(".$sys_tables['consults'].".title != '',".$sys_tables['consults'].".title,".$sys_tables['consults'].".question) AS question_title,
                                      DATE_FORMAT(".$sys_tables['consults'].".question_datetime,'%e %b %Y, %H:%i') as question_normal_date,
                                      ".$sys_tables['consults'].".name AS question_author_info,
                                      ".$sys_tables['consults'].".answers_amount,
                                      ".$sys_tables['consults_categories'].".code AS category_url
                               FROM  ".$sys_tables['consults']."
                               LEFT JOIN  ".$sys_tables['consults_categories']." ON ".$sys_tables['consults_categories'].".id=".$sys_tables['consults'].".id_category
                               LEFT JOIN  ".$sys_tables['consults_answers']." ON ".$sys_tables['consults_answers'].".id_parent=".$sys_tables['consults'].".id
                               WHERE  ".$sys_tables['consults_answers'].".id_user = ".$spec_id." AND ".$sys_tables['consults_answers'].".status = 1
                               ".(!empty($orderby)?"ORDER BY ".$orderby:"")."
                               GROUP BY ".$sys_tables['consults'].".id, ".$sys_tables['consults_answers'].".id
                               LIMIT ".$paginator->getFromString($page).",".$strings_per_page,
                               "id"
        );
        
        $question_keys = implode(',',array_keys($list));
        $answers = $db->fetchall("SELECT *,
                                         DATE_FORMAT(".$sys_tables['consults_answers'].".date_in,'%d.%m.%Y') as normal_date,
                                         CONCAT(".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname) AS user_info
                                  FROM ".$sys_tables['consults_answers']." 
                                  LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['consults_answers'].".id_user = ".$sys_tables['users'].".id
                                  WHERE id_parent IN (".$question_keys.") AND ".$sys_tables['consults_answers'].".status = 1 ORDER BY date_in DESC");
        $answers_list = array_fill_keys(array_keys($list),[]);
        foreach($answers as $key=>$item){
            $item['answer'] = Convert::stripUnwantedTagsAndAttrs(strip_tags($item['answer']));
            if($item['normal_date'] == "00.00.0000") $item['normal_date'] = false;
            $answers_list[$item['id_parent']] = array($item);
        }
        
        Response::SetString('full_count',count($list));
        Response::SetString('full_answers_count',count($answers));
        Response::SetArray('answers_list',$answers_list);
        Response::SetArray('list',$list);
        

        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        
        $ajax_result['ok'] = true;
        
        $GLOBALS['css_set'][] = '/modules/consults/styles.css';
        
        Response::SetInteger('full_count',$paginator->items_count);
        
        $module_template = "/modules/consults/templates/list.ajax.answers.html";
        break;
    
    //###########################################################################
    // страница специалиста
    //###########################################################################
    case Validate::isDigit($action) && empty($ajax_mode):
        $GLOBALS['css_set'][] = '/css/popup.window.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/autocomplette.js';
        $GLOBALS['js_set'][] = '/modules/consults/script.js';
        
        $user_id = $action;
        
        //переопределяем action
        $action = (!empty($this_page->page_parameters[2])?$this_page->page_parameters[2]:"item");
        
        if($action == 'block'){
            //переопределяем action
            $action = $this_page->page_parameters[3];
        }
        //если это не блок, отдаем карточку
        else{
            $GLOBALS['css_set'][] = '/modules/specialists/styles.css';
            $GLOBALS['js_set'][] = '/modules/specialists/item_scripts.js';
            
            $GLOBALS['css_set'][] = '/css/estate_search.css';
            
            $GLOBALS['css_set'][] = '/modules/housing_estates/style.css';
            
            $GLOBALS['css_set'][] = '/modules/infrastructure/styles.css';
            
            $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
            $GLOBALS['js_set'][] = '/js/form.validate.js';
            
            $GLOBALS['js_set'][] = '/modules/favorites/favorites.js';
            
            $info = $db->fetch(
                    "SELECT ".$sys_tables['users'].".*,
                            IF(
                                TIMESTAMPDIFF(MINUTE, `last_enter`, NOW())< 10, 'online',
                                IF(
                                    DATE(`last_enter`) = CURDATE(), CONCAT('сегодня в ',DATE_FORMAT(`last_enter`,'%k:%i')),
                                    IF(
                                        DATE(`last_enter`) = CURDATE() - 1, CONCAT('вчера в ',DATE_FORMAT(`last_enter`,'%k:%i')),
                                        DATE_FORMAT(`last_enter`,'%e %M в %k:%i')
                                    )
                                )
                            ) as last_activity,
                            CONCAT(".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname) AS title,
                            CONCAT_WS('/','".Config::$values['img_folders']['users']."','big',LEFT(photos.name,2)) as user_photo_folder,
                            photos.name as user_photo,
                            IFNULL(".$sys_tables['agencies'].".title,'') AS parent_agency_title,
                            IF( ".$sys_tables['agencies'].".title IS NOT NULL, CONCAT('/organizations/company/',".$sys_tables['agencies'].".chpu_title,'/'),'') AS parent_agency_url,
                            ".$sys_tables['users'].".user_activity
                    FROM ".$sys_tables['users']."
                    LEFT JOIN ".$sys_tables['users_photos']." photos ON photos.id_parent=".$sys_tables['users'].".id
                    LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                    WHERE ".$sys_tables['users'].".`id`=".$user_id." AND ".$specialist_condition);
            
            Response::SetInteger('agent_id',$user_id);
            Response::SetInteger('id_agent',$info['id']);
            $previous = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id < ? AND ".$specialist_condition." ORDER BY id DESC", $user_id)['id'];
            $next = $db->fetch("SELECT id  FROM ".$sys_tables['users']." WHERE id > ? AND ".$specialist_condition."", $user_id)['id'];
            if(!empty($previous)) Response::SetString('prev_url',"/specialists/".$previous."/");
            if(!empty($next)) Response::SetString('next_url',"/specialists/".$next."/");
            
            
            if(empty($info)){
                $this_page->http_code=404;
                break;
            }
            if(!empty($info['phone'])){
                $info['phone'] = Convert::ToPhone($info['phone'], false, '8', true);
                if(empty($info['phone'])) $info['phone'] = "";
            }
            
            //вычисление видов деятельности по битовой маске
            
            $specs = [];
            foreach($users_specs as $key=>$val){
                if($info['specializations']%(pow(2,$key))>=pow(2,$key-1)){
                    $specs[]=$val;
                    $specs_keywords[] = $val['title'];
                } 
            }
            $info['specializations'] = $specs;
            //количество объектов во вкладках
            $amounts = [];
            switch(true){
                case ($info['user_activity'] == 1):
                    $GLOBALS['css_set'][] = '/modules/applications/style.css';
                    Response::SetBoolean('estator',true);
                    $count = $db->fetch("SELECT (build_cnt+live_cnt+commercial_cnt+country_cnt) AS total,
                                        he_cnt AS he_total
                                 FROM
                                 (SELECT
                                  (SELECT COUNT(*) FROM ".$sys_tables['build']." WHERE id_user = ".$user_id." AND published = 1) AS build_cnt,
                                  (SELECT COUNT(*) FROM ".$sys_tables['live']." WHERE id_user = ".$user_id." AND published = 1) AS live_cnt,
                                  (SELECT COUNT(*) FROM ".$sys_tables['commercial']." WHERE id_user = ".$user_id." AND published = 1) AS commercial_cnt,
                                  (SELECT COUNT(*) FROM ".$sys_tables['country']." WHERE id_user = ".$user_id." AND published = 1) AS country_cnt,
                                  (SELECT COUNT(*) FROM ".$sys_tables['housing_estates']." WHERE id_user = ".$user_id." AND published = 1) AS he_cnt
                                 ) AS a");
                    $amounts['housing_estates'] = $count['he_total'];
                    $count = $count['total'];
                    Response::SetBoolean('is_objects', !empty($count));
                    $amounts['objects'] = $count;
                    break;
                case ($info['user_activity'] == 2):
                    require_once('includes/class.consults.php');
                    Response::SetArray('question_categories_list',ConsultQFunctions::getCategoriesList());
                    $GLOBALS['css_set'][] = '/modules/consults/style.css';
                    Response::SetArray('form_vars',array(
                                     'title'=>"",
                                     'name'=>(empty($auth)?"":$auth->name)
                                    ,'email'=>(empty($auth)?"":$auth->email)
                                    ,'category'=>''
                                    ,'category_title'=>''
                                    ,'text'=>''
                                    ,'url'=>'service/consultant/add'
                               ));
                    $count = $db->fetch("SELECT COUNT(DISTINCT ".$sys_tables['consults_answers'].".id) AS answers_amount
                                         FROM ".$sys_tables['consults_answers']." 
                                         LEFT JOIN ".$sys_tables['consults']." ON ".$sys_tables['consults'].".id = ".$sys_tables['consults_answers'].".id_parent
                                         WHERE ".$sys_tables['consults_answers'].".id_user = ? AND ".$sys_tables['consults_answers'].".status = 1",$user_id);
                    $amounts['answers'] = $count['answers_amount'];
                    Response::SetBoolean('lawyer',true);
                    break;
            }
            
            //скрываем телефон и почту если нету галочек "показывать"
            if($info['payed_page'] == 2){
                $info['phone'] = "";
                $info['email'] = "";
            } 
            
            Response::SetArray('info',$info);
            
            Response::SetArray('amounts',$amounts);
            

            //добавление title,keywords,description
            $full_activities = (!empty($specs_keywords)?implode(', ',array_values($specs_keywords)):"");
            
            $new_meta = array('title'=>$info['title'].' - Специалист рынка недвижимости '.(!empty($full_activities)?': '.$full_activities:"").' Санкт-Петербурга - BSN.ru',
                              'keywords'=>$info['title'].' '.$full_activities,
                              'description'=>$info['title'].'. Справочник специалистов рынка недвижимости Санкт-Петербурга от портала BSN.ru'.(!empty($full_activities)?': '.$full_activities.".":"."));
            $this_page->manageMetadata($new_meta,true);
            // хлебные крошки для тегов
            $this_page->addBreadcrumbs($info['title'], $action);
            
            ///то что достали из блока объектов
            require_once("includes/form.estate.php");
            $where = "id_user = ".$user_id." AND published=1";
            require_once('includes/class.estate.statistics.php');
            $agency_amounts = EstateStat::getAgenciesCountSearch($where);
            $deal_type_amount = array( 'sell'=>0, 'rent'=>0 );
            foreach($agency_amounts as $k=>$amount){
                $deal_type_amount[ ( strstr($k,'sell') != '' ? 'sell' : 'rent' ) ] += $amount['amount'];
            }
            Response::SetArray('deal_type_amount',$deal_type_amount);
            Response::SetArray('agency_amounts',$agency_amounts);
            ///
            
            $module_template = 'item.html';
        }
        break;
   default:
        $this_page->http_code=404;
        break;
}        
?>