<?php
require_once('includes/class.paginator.php');
require_once('includes/class.estate.statistics.php');

$exclude_agencies=$sys_tables['agencies'].".title NOT LIKE '%частн%' AND id_main_office = 0 AND is_archive = 2";//агентства, исключаемые из результатов поиска (частные лица и офисы других)
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$action_letter = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
$subaction = '';
//виды деятельности агентств
$agencies_activities = Config::$values['agencies_activities'];

$agencies_activities_aliases = [];

foreach($agencies_activities as $key=>$value){
    $agencies_activities_aliases[] = $value['url'];
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
$strings_per_page = Cookie::GetInteger('View_count_estate');
if(empty($strings_per_page)) $strings_per_page = Config::Get( 'view_settings/strings_per_page_agencies' );
Response::SetInteger( 'count', $strings_per_page );
//выбираем первые буквы слов
$list = $db->fetchall("SELECT DISTINCT UCASE(LEFT(".$sys_tables['agencies'].".title,1)) AS letter FROM ".$sys_tables['agencies']." WHERE id!=1 AND ".$exclude_agencies." ORDER BY letter");

//задаем сортировку
$sortby = Request::GetInteger('sortby',METHOD_GET);
switch($sortby){
    case '1':
        $sort_condition = $sys_tables['agencies'].".rating DESC";
        break;
    case '2':
        $sort_condition = $sys_tables['agencies'].".rating ASC";
        break;
    case '3':
        $sort_condition = "(".$sys_tables['agencies'].".active_live + ".$sys_tables['agencies'].".active_build +
                            ".$sys_tables['agencies'].".active_country + ".$sys_tables['agencies'].".active_commercial)"." DESC";
        break;
    case '4':
        $sort_condition = "(".$sys_tables['agencies'].".active_live + ".$sys_tables['agencies'].".active_build +
                            ".$sys_tables['agencies'].".active_country + ".$sys_tables['agencies'].".active_commercial)"." ASC";
        break;
    default:
        $sortby = 3;
        $sort_condition = "(".$sys_tables['agencies'].".active_live + ".$sys_tables['agencies'].".active_build +
                            ".$sys_tables['agencies'].".active_country + ".$sys_tables['agencies'].".active_commercial)"." DESC";
        break;
}
$GLOBALS['css_set'][] = '/modules/organizations/styles.css';
// обработка общих action-ов
switch(true){
    case $action=='search':
        if($ajax_mode) {
            $search_str = Request::GetString('search_string', METHOD_POST);
            $list = $db->fetchall("SELECT `title`,id
                                   FROM ".$sys_tables['agencies']."
                                   WHERE `title` LIKE '%".$db->real_escape_string($search_str)."%' AND ".$exclude_agencies."
                                   ORDER BY `title` 
                                   LIMIT 10");
            $ajax_result['ok'] = true;
            $ajax_result['list'] = $list;
        } else $this_page->http_code=404;
        break;
    /////////////////////////////////////////////////////////////////////////////
    // блок партнеры на главную
    ////////////////////////////////////////////////////////////////////////////
    case $action=='mainpage':// счетчик видов деятельности
        if(!$this_page->first_instance || $ajax_mode) {
            switch(true){
                case $action == 'mainpage':
                    $action = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : '';
                    switch($action){
                        /////////////////////////////////////////////////////////////////////////////
                        // клик по баннеру
                        ////////////////////////////////////////////////////////////////////////////
                        case 'click':
                            $id = Request::GetInteger('id', METHOD_POST);
                            $db->querys("INSERT INTO ".$sys_tables['agencies_mainpage_stats_click_day']." SET id_parent = ?, ip = ?, ref = ?",
                                $id, Host::getUserIp(), Host::getRefererURL()
                            );
                        
                            break;
                        default:
                            //вывод агентств по деятельности
                            if( !empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'activity' && !empty($this_page->page_parameters[2]) && Validate::isDigit( $this_page->page_parameters[2] ) ){
                                $activity_pow_mask = pow( 2, $this_page->page_parameters[2] );
                                $where = " activity & " . $activity_pow_mask ;
                            }

                            $module_template = 'block.mainpage.html';
                            $total = 5;
                            $agencies = $db->fetchall(
                                    "SELECT ".$sys_tables['agencies'].".*,
                                            CONCAT_WS('/','".Config::$values['img_folders']['agencies']."','sm',LEFT(photos.name,2)) as agency_photo_folder,
                                            photos.name as agency_photo,
                                            ".$sys_tables['users'].".id as id_user
                                    FROM ".$sys_tables['agencies']." 
                                    LEFT JOIN  ".$sys_tables['agencies_photos']." photos ON photos.id_parent=".$sys_tables['agencies'].".id
                                    LEFT JOIN  ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency=".$sys_tables['agencies'].".id
                                    WHERE ".$sys_tables['agencies'].".payed_page = 1 AND ".$sys_tables['agencies'].".id_tarif > 0
                                    " . ( !empty( $where ) ? " AND " . $where : "" ) . "
                                    GROUP BY ".$sys_tables['agencies'].".id
                                    ORDER BY 
                                        (
                                            ".$sys_tables['agencies'].".active_build +
                                             ".$sys_tables['agencies'].".active_live + 
                                             ".$sys_tables['agencies'].".active_commercial + 
                                             ".$sys_tables['agencies'].".active_country
                                        ) DESC
                                    LIMIT 10 ");
                            if(!empty($agencies)){
                                $count_items = 0;
                                $list = [];
                                foreach($agencies as $k=>$item){
                                    $count_total = $item['active_build'] + $item['active_live'] + $item['active_commercial'] + $item['active_country'];
                                    if( $count_total > 0){
                                        ++$count_items;
                                        $item['count_total'] = $count_total;
                                        $list[] = $item;
                                        //показ
                                        $db->querys("INSERT INTO ".$sys_tables['agencies_mainpage_stats_show_day']." SET id_parent = ?, ip = ?, browser = ?, ref = ?",
                                            $item['id'], Host::getUserIp(), !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "", Host::getRefererURL()
                                        );
                                        if($count_items >= $total) break;
                                    }                                            
                                }                                            
                            }
                            if(!empty($list)) Response::SetArray('list', $list);
                            
                            $ajax_result['ok'] = true;
                            break;
                            
                    }
            }
        }

    case $action=='block':// счетчик видов деятельности
        $action = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : '';
        if(!$this_page->first_instance || $ajax_mode) {
        switch(true){
            /////////////////////////////////////////////////////////////////////////////   
            // алфавит
            ////////////////////////////////////////////////////////////////////////////
                case preg_match('/letters/',$action):
                    $module_template = 'block.letters.html';
                    //распределяем буквы по типу
                    $l_subst_ru = []; //транслитные варианты  русских букв
                    $letters_ru = []; //выбранные русские буквы
                    $letters_en = []; //выбранные английские буквы
                    foreach($list AS $key => $lt)
                    {
                        $index = array_search($lt['letter'],$arr_alph_ru);
                        if ($index!==false){
                            //если буква русская, то запоминаем ее и транслитный вариант
                            array_push($l_subst_ru,$subst_ru[$index]);
                            array_push($letters_ru,$arr_alph_ru[$index]);
                        }
                        if (in_array($lt['letter'],$arr_alph_en)){
                            //если буква английская, транслит не нужен, только сама буква
                            array_push($letters_en,$lt['letter']);
                        }               ;
                    }
                    
                    //опеределяем текущую букву, которая будет <span> в списке букв
                    if (!empty($this_page->page_parameters[1])){
                        $index=array_search($this_page->page_parameters[1],$subst_ru);
                        if ($index!==false){
                            $letter=$arr_alph_ru[$index];
                            Response::SetString('letter_current',$letter);
                        }
                        elseif(in_array($this_page->page_parameters[1],$arr_alph_en)){
                            $letter=$this_page->page_parameters[1];
                            Response::SetString('letter_current',$letter);
                        }
                    }
                    //response
                    Response::SetArray('letters_ru',$letters_ru);
                    Response::SetArray('l_subst_ru',$l_subst_ru);
                    Response::SetArray('letters_en',$letters_en);
                    break;
            /////////////////////////////////////////////////////////////////////////////
            // количество активных объектов
                case preg_match('/amounts/',$action):
                    $amount_current = explode('/',$_SERVER['REQUEST_URI'])[2];
                    Response::SetArray('amounts_list',$amounts_list);
                    Response::SetString('amount_current',$amount_current);
                    $module_template = "block.amounts.html";
                    break;
            /////////////////////////////////////////////////////////////////////////////
            // виды деятельности со счетчиками
                case preg_match('/activities/',$action):
                    $agency_activities_counter = [];
                    foreach($agencies_activities as $key=>$val){
                        $activity_pow_mask = pow(2,$key);
                        $act = $db->fetch("SELECT COUNT(*) as cnt 
                                           FROM ".$sys_tables['agencies']." 
                                           WHERE activity & ".$activity_pow_mask." AND ".$exclude_agencies);
                        $agency_activities_counter[] =  [ $key, $agencies_activities[$key], $act['cnt'], $agencies_activities[$key]['url'] ];
                    }
                    
                    $activity_type = explode( '/' , $_SERVER['REQUEST_URI'] );
                    Response::SetString('activity', !empty( $activity_type[2] ) ? $activity_type[2] : '' );
                    Response::SetArray('list',$agency_activities_counter);
                    $module_template = 'block.html';
                    break;
            }
        } else $this_page->http_code=404;
        break;
    /////////////////////////////////////////////////////////////////////////////
    // заглавная страница организаций
    /////////////////////////////////////////////////////////////////////////////     
    case empty($action) || in_array( $action, $agencies_activities_aliases ): 
        
        //подключение стилей и JS для автозаполнения
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/autocomplette.js';
        $GLOBALS['js_set'][] = '/modules/estate/list_options.js';

        $h1 = empty($this_page->page_seo_h1) ? 'Организации рынка недвижимости' : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);   

        //читаем список компаний
        $page = Request::GetInteger( 'page', METHOD_GET );
        if ( isset( $page ) && ( $page == 0 ) ) {
            Host::Redirect( '/' . $this_page->requested_path . '/?page=1' . ( !empty( $sortby ) ? "&sortby=" . $sortby : "" ) );
            exit(0);
        } 
        if (empty($page)) $page = 1;

        $where = array (  $exclude_agencies );
        
        //обработка параметров
        $parameters = Request::GetParameters( METHOD_GET );
        $new_params = [];
        foreach( $parameters as $k => $param ) {
            if( $param == '' ) $redirect = true;
            else if( $k != 'path' ) $new_params[$k] = $param;
        }
        if( !empty( $redirect ) ) {
            Host::Redirect( '/' . $this_page->requested_path . '/?' .Convert::ArrayToStringGet( $new_params ) );
        }
        if( !empty( $parameters['activity'] ) ) $where[] = " activity & " . pow( 2, $parameters['activity'] - 1 );
        if( !empty( $parameters['organization_title'] ) ) $where[] = $sys_tables['agencies'] . ".title LIKE '%" . $db->real_escape_string( $parameters['organization_title'] ) . "%'";
        Response::SetArray( 'form_data', $parameters );
                
        $where = implode( " AND ", $where );
        
        $paginator = new Paginator($sys_tables['agencies'], $strings_per_page, $where);
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
        $paginator->link_prefix = '/'.$this_page->requested_path.'/?'.(!empty($sortby)?"sortby=".$sortby."&":"").'page=';
        Response::SetInteger('total_found',$paginator->items_count);
        Response::SetString('found_shown',$paginator->getFromString($page));
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        //выбираем страницы для отображения
        $list = $db->fetchall("SELECT ".$sys_tables['agencies'].".id,
                                      (".$sys_tables['agencies'].".active_build +
                                       ".$sys_tables['agencies'].".active_live + 
                                       ".$sys_tables['agencies'].".active_commercial + 
                                       ".$sys_tables['agencies'].".active_country) AS amount,
                                      ".$sys_tables['users'].".id AS user_id,
                                      CONCAT_WS('/','".Config::$values['img_folders']['agencies']."','sm',LEFT(photos.name,2)) as agency_photo_folder,
                                      photos.name as agency_photo,
                                      TRIM(".$sys_tables['agencies'].".title) as title, 
                                      ".$sys_tables['agencies'].".chpu_title,
                                      ".$sys_tables['agencies'].".activity,
                                      ".$sys_tables['agencies'].".phone_1,
                                      ".$sys_tables['agencies'].".advert_phone,
                                      (".$sys_tables['agencies'].".payed_page = 1) AS payed_page
                               FROM ".$sys_tables['agencies']."
                               LEFT JOIN  ".$sys_tables['agencies_photos']." photos ON photos.id_parent=".$sys_tables['agencies'].".id
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                               WHERE ".$where."
                               GROUP BY ".$sys_tables['agencies'].".id
                               ORDER BY ".(!empty($sort_condition)?$sort_condition.",":"")." title LIMIT ".$paginator->getFromString($page).",".$strings_per_page);
        //вычисление видов деятельности по битовой маске
        foreach($list as $key=>$item){
            $activities = [];
            foreach($agencies_activities as $k=>$val){
                if($item['activity']%(pow(2,$k+1))>=pow(2,$k)) $activities[] = $val['title'];
            }
            $list[$key]['activity'] = implode(', ',$activities);
        }
        Response::SetArray('list',$list);
        
        //форма поиска
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        $eml_tpl = new Template('search.form.html', 'modules/organizations/');
        $html = $eml_tpl->Processing();
        Response::SetString( 'search_form_html', $html );

        $module_template = 'mainpage.html';
        break;
    /////////////////////////////////////////////////////////////////////////////
    // выводим список агенств по количеству объектов
    /////////////////////////////////////////////////////////////////////////////
    case preg_match('/[0-9]{1,2}?_[0-9]{0,2}?[^A-z]*$/',$action):
        
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/autocomplette.js';
        
        //редирект на GET паджинацию
        if(!empty($this_page->page_parameters[1]) && isPage($this_page->page_parameters[1])) 
            Host::Redirect("/organizations/".$action."/?page=".getPage($this_page->page_parameters[1]).(!empty($sortby)?"&sortby=".$sortby:"")); 
        
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

        //смотрим, какие агентства подходят
        $ids = $db->fetchall("SELECT id FROM ".$sys_tables['users']." WHERE id_agency!=0 AND agency_admin = 1",'id');
        $ids = implode(',',array_keys($ids));
        $where = [];
        $where[] = " ".$exclude_agencies." ";
        if(!empty($amount_borders[0])) $where[] = "(".$sys_tables['agencies'].".active_build +".$sys_tables['agencies'].".active_live + 
                                                    ".$sys_tables['agencies'].".active_commercial + ".$sys_tables['agencies'].".active_country) >= ".$amount_borders[0];
        if(!empty($amount_borders[1])) $where[] = "(".$sys_tables['agencies'].".active_build +".$sys_tables['agencies'].".active_live + 
                                                    ".$sys_tables['agencies'].".active_commercial + ".$sys_tables['agencies'].".active_country) <= ".$amount_borders[1];
        $where_paginator = implode(" AND ",$where);
        $paginator = new Paginator($sys_tables['agencies'],$strings_per_page,$where_paginator);
        //для паджинатора условие на то что это агентство не нужно, и так читаем с агентств
        $where[] = $sys_tables['users'].".id IN (".$ids.")";
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
                       
        //выбираем страницы для отображения
        $list = $db->fetchall("SELECT ".$sys_tables['agencies'].".id,
                                      (".$sys_tables['agencies'].".active_build +
                                       ".$sys_tables['agencies'].".active_live + 
                                       ".$sys_tables['agencies'].".active_commercial + 
                                       ".$sys_tables['agencies'].".active_country) AS amount,
                                      ".$sys_tables['users'].".id AS user_id,
                                      CONCAT_WS('/','".Config::$values['img_folders']['agencies']."','sm',LEFT(photos.name,2)) as agency_photo_folder,
                                      photos.name as agency_photo,
                                      TRIM(".$sys_tables['agencies'].".title) as title, 
                                      ".$sys_tables['agencies'].".chpu_title,
                                      ".$sys_tables['agencies'].".activity,
                                      ".$sys_tables['agencies'].".phone_1,
                                      ".$sys_tables['agencies'].".advert_phone,
                                      (".$sys_tables['agencies'].".payed_page = 1) AS payed_page
                               FROM ".$sys_tables['agencies']."
                               LEFT JOIN ".$sys_tables['agencies_photos']." photos ON photos.id_parent=".$sys_tables['agencies'].".id
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                               WHERE ".$where.
                               " ORDER BY ".(!empty($sort_condition)?$sort_condition.",":"")." title LIMIT ".$paginator->getFromString($page).",".$strings_per_page);
        foreach($list as $key=>$item){
            //вычисление видов деятельности по битовой маске
            $activities = [];
            foreach($agencies_activities as $k=>$val){
                if($item['activity']%(pow(2,$k+1))>=pow(2,$k)) $activities[] = $val['title'];
            }
            $list[$key]['activity'] = implode(',',$activities);
        }
        Response::SetArray('list',$list);
                
        // хлебные крошки для тегов
        $this_page->addBreadcrumbs( $amounts_list[$action], $action."/");
        //Response::SetString('h1', empty($this_page->page_seo_h1) ? $agencies_activities[$activity_mask]['title'] : $this_page->page_seo_h1);
        $h1 = empty($this_page->page_seo_h1) ? 'Организации рынка недвижимости' : $this_page->page_seo_h1;
        Response::SetString('h1',$h1);
        $module_template = 'list.html';
        break;
    
    /////////////////////////////////////////////////////////////////////////////
    // страница агентства
    /////////////////////////////////////////////////////////////////////////////
    case $action=='company':
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/autocomplette.js';
        if(empty($this_page->page_parameters[1])) Host::Redirect('/organizations/');
        if(Validate::isDigit($this_page->page_parameters[1])){
            $res = $db->fetch("SELECT chpu_title FROM ".$sys_tables['agencies']." WHERE id=?", $this_page->page_parameters[1]);
            if(!empty($res['chpu_title'])) Host::Redirect("organizations/company/".$res['chpu_title']);   
            else{
                $agency_id = preg_split("/\_/",$this_page->page_parameters[1],2);
                if(!Validate::isDigit($agency_id[0])){$this_page->http_code=404; break;}
                $agency_id = $agency_id[0];
            }
        }  else {
            $agency_id = preg_split("/\_/",$this_page->page_parameters[1],2);
            if(!Validate::isDigit($agency_id[0])){$this_page->http_code=404; break;}
            $agency_id = $agency_id[0];
        }
        //переопределяем action
        $action = (!empty($this_page->page_parameters[2])?$this_page->page_parameters[2]:"item");
        
        if($action == 'block'){
            //переопределяем action
            $action = $this_page->page_parameters[3];
            switch($action){
                //////////////////
                //вкладка "Офисы"
                case 'offices':
                    
                    $agency_id = explode('_',$this_page->page_parameters[1])[0];
                    //читаем информацию по офисам и время работы (головной офис идет первым засчет сортировки)
                    $list = $db->fetchall("SELECT ".$sys_tables['agencies'].".*,
                                                  GROUP_CONCAT('#',".$sys_tables['agencies_opening_hours'].".day_num,',',".$sys_tables['agencies_opening_hours'].".`begin`,',',".$sys_tables['agencies_opening_hours'].".`end`) AS workdays
                                           FROM ".$sys_tables['agencies']."
                                           LEFT JOIN ".$sys_tables['agencies_opening_hours']." ON ".$sys_tables['agencies_opening_hours'].".id_agency = ".$sys_tables['agencies'].".id
                                           WHERE id_main_office = ".$agency_id." OR ".$sys_tables['agencies'].".id = ".$agency_id." OR ".$sys_tables['agencies'].".id = id_main_office
                                           GROUP BY ".$sys_tables['agencies'].".id
                                           ORDER BY id_main_office ASC");
                    if(!empty($list)){
                        $head_office_id = $list[0]['id_main_office'];
                        
                        foreach($list as $key=>$item) $ids[] = $item['id'];
                        $ids = implode(',',$ids);
                        //если нужно, дозацепляем головной офис
                        if(!empty($head_office_id))
                            $head_office = $db->fetch("SELECT ".$sys_tables['agencies'].".*,
                                                       GROUP_CONCAT('#',".$sys_tables['agencies_opening_hours'].".day_num,',',".$sys_tables['agencies_opening_hours'].".`begin`,',',".$sys_tables['agencies_opening_hours'].".`end`) AS workdays
                                                       FROM ".$sys_tables['agencies']."
                                                       LEFT JOIN ".$sys_tables['agencies_opening_hours']." ON ".$sys_tables['agencies_opening_hours'].".id_agency = ".$sys_tables['agencies'].".id
                                                       WHERE ".$sys_tables['agencies'].".id = ".$head_office_id);
                        if(!empty($head_office)){
                            array_unshift($list,$head_office);
                            //теперь прицепляем другие подчиненные офисы
                            $list_ascendands = $db->fetchall("SELECT ".$sys_tables['agencies'].".*,
                                                                     GROUP_CONCAT('#',".$sys_tables['agencies_opening_hours'].".day_num,',',".$sys_tables['agencies_opening_hours'].".`begin`,',',".$sys_tables['agencies_opening_hours'].".`end`) AS workdays
                                                              FROM ".$sys_tables['agencies']."
                                                              LEFT JOIN ".$sys_tables['agencies_opening_hours']." ON ".$sys_tables['agencies_opening_hours'].".id_agency = ".$sys_tables['agencies'].".id
                                                              WHERE id_main_office = ".$head_office_id." AND ".$sys_tables['agencies'].".id NOT IN (".$ids.")
                                                              GROUP BY ".$sys_tables['agencies'].".id
                                                              ORDER BY id_main_office ASC");
                            $list = array_merge($list,$list_ascendands);
                        } 
                    }
                    
                    //дни недели
                    $weekdays = Convert::ru_week(false,false,true);
                    //формируем время работы
                        foreach($list as $key=>$office){
                            if(!empty($office['workdays'])){
                                $workdays = explode('#',preg_replace('/^#/','',$office['workdays']));
                                $list[$key]['workdays'] = [];
                                $times = [];
                                //флаг того, что время на буднях одинаковое
                                $cdays_equal = true;
                                foreach($workdays as $k=>$item){
                                    $item = explode(',',$item);
                                    
                                    if(!empty($item[0]) && $item[0]<=5 && $cdays_equal)
                                        if(empty($times)) $times[] = $item[1].$item[2];
                                        elseif($times[0]!=$item[1].$item[2] || empty($times[0])) $cdays_equal = false;
                                            else $times[] = $item[1].$item[2];
                                    if(!empty($item[1]) && !empty($item[2])) 
                                        $list[$key]['workdays'][$item[0]] = array('day_title'=>$weekdays[$item[0]-1],'worktime'=>preg_replace('/\:[0-9]{2}$/','',$item[1])." — ".preg_replace('/\:[0-9]{2}$/','',$item[2]));
                                }
                                foreach($weekdays as $k=>$item){
                                    if(empty($list[$key]['workdays'][$k+1])) $list[$key]['workdays'][$k+1] = array('day_title'=>$item,'worktime'=>'выходной');
                                }
                                if(count($times)<5) $cdays_equal = false;
                                //если на буднях расписание одинаковое, склеиваем с понедельника по пятницу
                                if($cdays_equal && !empty($times[0])){
                                    $list[$key]['workdays'][1]['day_title'] = 'понедельник-пятница';
                                    ksort($list[$key]['workdays']);
                                    unset($list[$key]['workdays'][2]);
                                    unset($list[$key]['workdays'][3]);
                                    unset($list[$key]['workdays'][4]);
                                    unset($list[$key]['workdays'][5]);
                                }
                                else ksort($list[$key]['workdays']);
                            }
                            if($office['id'] == $agency_id) $list[$key]['this'] = true;
                        }
                    
                    Response::SetArray('list',$list);
                    $module_template = 'block.offices.html';
                    break;
                ////////////////////////////////////////////////////
                // вкладка "Сотрудники" / Менеджер
                ////////////////////////////////////////////////////
                case 'staff':
                case 'manager':
                    //определение id агентства
                    $agency_id = explode('_',$this_page->page_parameters[1])[0];
                    $info = $db->fetch("SELECT (id_tarif > 0) AS has_tarif,id_main_office FROM ".$sys_tables['agencies']." WHERE id = ".$agency_id);
                    if(empty($info)){
                        $module_template = 'block.' . $action . '.html';
                        break;
                    }
                    $agency_has_tarif = $info['has_tarif'];
                    //выбираем все офисы компании
                    if(!empty($info['id_main_office'])) $offices_list = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['agencies']." WHERE id_main_office = ".$info['id_main_office']." OR id = ".$info['id_main_office'])['ids'];
                    else $offices_list = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['agencies']." WHERE id_main_office = ".$agency_id." OR id = ".$agency_id." ORDER BY id_main_office ASC")['ids'];
                    
                    $admin = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ? AND agency_admin = ?", $agency_id, 1);
                    if(empty($agency_has_tarif)) unset($admin['phone']);
                    if(empty($admin)){
                         //$this_page->http_code = 404;
                         Response::SetArray('list',[]);
                         $module_template = 'block.' . $action . '.html';
                         break;
                    }
                    Response::SetArray('admin_info', $admin);
                    //список сотрудников
                    $list = $db->fetchall("
                                SELECT * FROM (
                                    SELECT ".$sys_tables['users'].".*,
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
                                    ".$sys_tables['users_photos'].".`name` as `photo`, 
                                    LEFT (".$sys_tables['users_photos'].".`name`,2) as `subfolder`,
                                    'agent' as user_status
                                    FROM ".$sys_tables['users']."
                                    RIGHT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".bsn_id_user = ".$sys_tables['users'].".id
                                    RIGHT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                    LEFT JOIN ".$sys_tables['users_photos']." ON ".$sys_tables['users'].".id_main_photo = ".$sys_tables['users_photos'].".id
                                    WHERE ".$sys_tables['agencies'].".id = " . $agency_id . " 
                                    GROUP BY ".$sys_tables['users'].".id
                                ) a
                                ORDER BY a.agency_admin = 1 DESC"
                            );  
                    //количество объектов агента
                    foreach($list as $k=>$item){
                        //если это не специалист и у агентства нет тарифа, убираем телефоны
                        /*
                        if(empty($agency_has_tarif) || $item['show_phone'] == 2) unset($list[$k]['phone']);
                        if(empty($agency_has_tarif) || $item['show_email'] == 2) unset($list[$k]['email']);
                        */
                        if(!empty($list[$k]['phone']) && strlen($list[$k]['phone'])>7) $list[$k]['phone'] = Convert::ToPhone($item['phone'], false, 8)[0];
                        else $list[$k]['phone'] = "";
                        $objects = $db->fetch("
                                                SELECT SUM(cnt) as cnt FROM (
                                                    SELECT COUNT(*) as cnt FROM ".$sys_tables['live']." WHERE id_user = ? AND published = 1
                                                    UNION ALL
                                                    SELECT COUNT(*) as cnt FROM ".$sys_tables['build']." WHERE id_user = ? AND published = 1
                                                    UNION ALL
                                                    SELECT COUNT(*) as cnt FROM ".$sys_tables['commercial']." WHERE id_user = ? AND published = 1
                                                    UNION ALL
                                                    SELECT COUNT(*) as cnt FROM ".$sys_tables['country']." WHERE id_user = ? AND published = 1
                                                ) as a
                        
                        ", $item['id'], $item['id'], $item['id'], $item['id']);
                        $list[$k]['objects_count'] = $objects['cnt'];
                        //специализации
                        $specializations = [];
                        foreach(Config::Get('users_specializations') as $skey=>$val){
                            if($item['specializations']%(pow(2,$skey))>=pow(2,$skey-1)) $specializations[] = $val;
                        }
                        $list[$k]['specializations_row'] = implode('<br/>', $specializations);
                        //флаг, короткая будет карточка в в списке или нет
                        $list[$k]['short'] =  (empty($specializations) && empty($list[$k]['phone']) && empty($list[$k]['email']));
                    }
                    Response::SetArray('list', $list);
                    Response::SetString('img_folder', Config::$values['img_folders']['live']);
                    $module_template = 'block.' . $action . '.html';
                    break;
                //////////////////
                //вкладка "Фото"
                case 'photo':
                    $module_template = 'block.photo.html';
                    break;
                //////////////////
                //вкладка "Видео"
                case 'video':
                    $module_template = 'block.video.html';
                    break;
                //////////////////
                //вкладка "Объекты"
                case 'objects':
                    $objects_tab = true;
                    $module_template = 'block.objects.html';
                    break;
                case 'housing_estates':
                    /*
                    $agency_admin_id = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id_agency = ".$agency_id." AND agency_admin = 1")['id'];
                    Response::SetInteger('agency_id',$agency_admin_id);*/
                    $module_template = 'block.housing_estates.html';
                    break;
                case 'business_centers':
                    $module_template = 'block.business_centers.html';
                    break;
            }
        }
        //если это не блок, отдаем карточку
        else{
            
            $GLOBALS['css_set'][] = '/modules/applications/style.css';
            $GLOBALS['css_set'][] = '/css/estate_search.css';
            
            $GLOBALS['css_set'][] = '/modules/housing_estates/style.css';
            
            $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
            $GLOBALS['js_set'][] = '/js/form.validate.js';
            $GLOBALS['js_set'][] = '/modules/organizations/item_scripts.js';
            
            $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
            $GLOBALS['js_set'][] = '/modules/favorites/favorites.js';
            
            $info = $db->fetch(
                    "SELECT ".$sys_tables['agencies'].".*,
                            CONCAT_WS('/','".Config::$values['img_folders']['agencies']."','med',LEFT(photos.name,2)) as agency_photo_folder,
                            photos.name as agency_photo,
                            ".$sys_tables['users'].".id as id_user,
                            ".$sys_tables['users'].".balance AS balance
                    FROM ".$sys_tables['agencies']." 
                    LEFT JOIN  ".$sys_tables['agencies_photos']." photos ON photos.id_parent=".$sys_tables['agencies'].".id
                    LEFT JOIN  ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency=".$sys_tables['agencies'].".id AND ".$sys_tables['users'].".agency_admin = 1
                    WHERE ".$sys_tables['agencies'].".`id`=".$agency_id." AND is_archive = 2");
            //если ничего не нашли, переходим на заглавную страницу
            if(empty($info)) Host::Redirect('/organizations/');
            $previous = $db->fetch("SELECT chpu_title FROM ".$sys_tables['agencies']." WHERE id < ? AND is_archive = 2 ORDER BY id DESC", $agency_id);
            $next = $db->fetch("SELECT chpu_title  FROM ".$sys_tables['agencies']." WHERE id > ? AND is_archive = 2 ORDER BY id ASC", $agency_id);
            if(!empty($previous['chpu_title'])) Response::SetString('prev_url',"/organizations/company/".$previous['chpu_title']);
            if(!empty($next['chpu_title'])) Response::SetString('next_url',"/organizations/company/".$next['chpu_title']);
            //вычисление видов деятельности по битовой маске, формируем ссылки для них
            $activities = [];
            foreach($agencies_activities as $k=>$val){
                if($info['activity']%(pow(2,$k+1))>=pow(2,$k)) $activities[] = $val;
            }
            $info['activities'] = $activities;
            
            
            
            Response::SetBoolean('payed_format', true);
            Response::SetBoolean('not_show_top_banner', true);
            Response::SetInteger('id_agency',$info['id']);
            if(empty($info)){
                $this_page->http_code=404;
                break;
            }
            if(!empty($info['advert_phone'])) $info['advert_phone'] = Convert::ToPhone($info['advert_phone'], false, '8', true);
            if(!empty($info['phone_1'])) $info['phone_1'] = Convert::ToPhone($info['phone_1'], false, '8', true);
            if(!empty($info['phone_2'])) $info['phone_2'] = Convert::ToPhone($info['phone_2'], false, '8', true);
            if(!empty($info['phone_3'])) $info['phone_3'] = Convert::ToPhone($info['phone_3'], false, '8', true);
            
            $info['description'] = Convert::stripUnwantedTagsAndAttrs($info['description']);
            Response::SetArray('info', $info);
            //вычисление видов деятельности по битовой маске
            $activity = [];
            foreach($agencies_activities as $key=>$val){
                if($info['activity']%(pow(2,$key+1))>=pow(2,$key)) $activity[$agencies_activities[$key]['url']]=$val['title'];
            }
            Response::SetArray('activity',$activity);
            if(!empty($info['id_main_office'])) $offices_list = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['agencies']." WHERE id_main_office = ".$info['id_main_office']." OR id = ".$info['id_main_office'])['ids'];
            else $offices_list = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['agencies']." WHERE id_main_office = ".$agency_id." OR id = ".$agency_id." ORDER BY id_main_office ASC")['ids'];
            $users_ids = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']." WHERE id_agency IN (".$offices_list.")")['ids'];
            $this_users = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']." WHERE id_agency IN (".$agency_id.")")['ids'];
            if(!empty($users_ids)){
                //считаем количество пользователей
                $amounts['users'] = count(explode(',',$users_ids));
                //ищем объекты
                
                $count = $db->fetch("SELECT (build_cnt+live_cnt+commercial_cnt+country_cnt) AS total,
                                            he_cnt AS he_total,
                                            apartments_cnt AS apartments_total,
                                            bc_cnt AS bc_total
                                     FROM
                                     (SELECT
                                      (SELECT COUNT(*) FROM ".$sys_tables['build']." WHERE id_user IN(".$this_users.") AND published = 1) AS build_cnt,
                                      (SELECT COUNT(*) FROM ".$sys_tables['live']." WHERE id_user IN(".$this_users.") AND published = 1) AS live_cnt,
                                      (SELECT COUNT(*) FROM ".$sys_tables['commercial']." WHERE id_user IN(".$this_users.") AND published = 1) AS commercial_cnt,
                                      (SELECT COUNT(*) FROM ".$sys_tables['country']." WHERE id_user IN(".$this_users.") AND published = 1) AS country_cnt,
                                      ".(($info['payed_page'] == 1)?"(SELECT COUNT(*) FROM ".$sys_tables['housing_estates']." WHERE (id_user IN(".$this_users.") OR id_seller IN(".$this_users.")) AND published = 1 AND apartments = 2) AS he_cnt,":
                                                                    "(SELECT COUNT(*) FROM ".$sys_tables['housing_estates']." WHERE id_user IN(".$this_users.") AND published = 1 AND apartments = 2) AS he_cnt,")."
                                      ".(($info['payed_page'] == 1)?"(SELECT COUNT(*) FROM ".$sys_tables['housing_estates']." WHERE (id_user IN(".$this_users.") OR id_seller IN(".$this_users.")) AND published = 1 AND apartments = 1) AS apartments_cnt,":
                                                                    "(SELECT COUNT(*) FROM ".$sys_tables['housing_estates']." WHERE id_user IN(".$this_users.") AND published = 1 AND apartments = 1) AS apartments_cnt,")."
                                      ".(($info['payed_page'] == 1)?"(SELECT COUNT(*) FROM ".$sys_tables['business_centers']." WHERE (id_user IN(".$this_users.") OR id_seller IN(".$this_users.")) AND published = 1) AS bc_cnt":
                                                                    "(SELECT COUNT(*) FROM ".$sys_tables['business_centers']." WHERE id_user IN(".$this_users.") AND published = 1) AS bc_cnt")."
                                      ) AS a");    
                $amounts['housing_estates'] = $count['he_total'];
                $amounts['apartments'] = $count['apartments_total'];
                $amounts['business_centers'] = $count['bc_total'];
                Response::SetBoolean('is_objects', !empty($count['cnt']));
                $amounts['objects'] = $count['total'];
            }
            
            Response::SetArray('amounts',$amounts);
            //общее количество объектов
            if( !empty( $amounts['users'] ) ) unset( $amounts['users'] );
            Response::SetInteger( 'total_objects', array_sum( $amounts ) ) ;
            //добавление title,keywords,description
            $full_activities = implode(', ',$activity);
            $new_meta = array('title'=>$info['title'].' - Организации рынка недвижимости '.(!empty($full_activities)?': '.$full_activities:"").' Санкт-Петербурга - BSN.ru',
                              'keywords'=>$info['title'].' '.$full_activities,
                              'description'=>$info['title'].'. Справочник организаций рынка недвижимости Санкт-Петербурга от портала BSN.ru'.(!empty($full_activities)?': '.$full_activities.".":"."));
            $this_page->manageMetadata($new_meta,true);
            // хлебные крошки для тегов
            $this_page->addBreadcrumbs($info['title'], $action);
            
            ///то что достали из блока объектов
            $agency_admin_id = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id_agency = ".$agency_id." AND agency_admin = 1")['id'];
            Response::SetInteger('agency_admin_id', $agency_admin_id);
            require_once("includes/form.estate.php");
            $users_ids = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']." WHERE id_agency = ".$agency_id)['ids'];
            $where = (empty($users_ids)?"":"id_user IN (".$users_ids.") AND ")." published=1";
            require_once('includes/class.estate.statistics.php');
            $agency_amounts = EstateStat::getAgenciesCountSearch($where);
            
            $agency_amounts['sell_amount'] = 0;
            $agency_amounts['rent_amount'] = 0;
            foreach($agency_amounts as $key=>$item){
                if(strstr($key,'sell') && !empty($item['amount'])) ++$agency_amounts['sell_amount'];
                elseif(strstr($key,'rent') && !empty($item['amount'])) ++$agency_amounts['rent_amount'];
            }
            Response::SetBoolean('wide_form',!empty($agency_amounts['sell_amount']) && !empty($agency_amounts['rent_amount']));
            Response::SetArray( 'agency_amounts', $agency_amounts );
            
            $module_template = 'item.html';
        }
        break;
   default:
        $this_page->http_code=404;
        break;
}
//сортировку чтобы было видно
Response::SetInteger('sortby',$sortby);


?>