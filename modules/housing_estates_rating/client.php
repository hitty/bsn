<?php
 //редирект с /estate/build на без /estate/build
if(strstr($this_page->real_url, 'estate/build/') != '' && $this_page->first_instance) {
    Host::Redirect( '/' . str_replace('estate/build/', '',  trim($this_page->real_url,'/' )) . '/' );
}
require_once('includes/class.housing_estates.php');
require_once('includes/class.housing_estates.rating.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.common.php');

//хлебные крошки по умолчанию
$page_url = $this_page->page_url;
//для карточки с ЧПУ редиректом основные параметры из ЧПУ
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

Response::SetString( 'img_folder', Config::Get( 'img_folders/housing_estates' ) );                
$estate_type = 'zhiloy_kompleks';
Response::SetString( 'estate_type', $estate_type );  
                  
//проверка на отображение страницы
$rating = new HousingEstatesRating();
$get_parameters = Request::GetParameters( METHOD_GET );
$user = $rating->userInfo( false, !empty( $auth->id ) ? $auth->id : false, !empty( $get_parameters['invite_code'] ) ? $get_parameters['invite_code'] : false );
if( !empty( $user ) ) Response::SetArray( 'housing_estate_expert', $user );


//авторизация и редирект на страницу без параметров для пользователя по коду
if( !empty( $get_parameters['invite_code'] ) && !empty( $user ) ) {
    //флаг - клиент согласен
    $db->querys(" UPDATE " . $sys_tables['housing_estates_experts'] . " SET agreed = ? WHERE id = ?", 1, $user['id'] ) ;
    //залогинивание клиента
    $auth->checkSuperAdminAuth( $user['id_user'] );
    if(!empty($auth->authorized)) Host::Redirect( Host::$root_url . '/zhiloy_kompleks/votes/' );    
}
if( empty( $auth->id ) || empty( $user ) ) Host::RedirectLevelUp();
//Апартаменты
$is_apartments = $page_url == 'zhiloy_kompleks' ? false : true; 
// обработка общих action-ов
switch(true){
    /**************************\
    |*  Работа с фотографиями  *|
    \**************************/
    case $action == 'photos':
        if($ajax_mode){
            // свойства папок для загрузки и формата фотографий
            Photos::$__folder_options =  array(
                'med'   =>  array(800,800,'',9),
                'big'   =>  array(2000,2000,'',90),
                'sm'    =>  array(60,60,'cut',80)
            );                 

            $ajax_result['error'] = '';
            // переопределяем экшн
            $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
            $id = $user['id'];
            switch($action){
                case 'list':
                    //получение списка фотографий
                    if(!empty($id)){
                        $list = Photos::getList('housing_estates_experts',$id);
                        if(!empty($list)){
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                            $ajax_result['folder'] = Config::$values['img_folders']['housing_estates_experts'];
                        } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    //загрузка фотографий
                      
                    if(!empty($id)){
                        $res = Photos::Add('housing_estates_experts',$id,false,false,false,false,false,true);
                        if(!empty($res)){
                            if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                            else {
                                if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                else {
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $res;
                                }
                            }
                        } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'del':
                    //удаление фото
                    if(!empty($id_photo)){
                        $res = Photos::Delete('housing_estates_experts',$id_photo);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                
                
            }
        }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Попап голосования
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $action == 'popup_voting':
        //для карточки с ЧПУ редиректом основные параметры из ЧПУ
        $id = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        if( empty( $id ) || !$rating->canVote( $id ) ) break;
        Response::SetInteger( 'id', $id );
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch(true){
            case $action == 'add':
                //отправка заявки с карточки объекта
                if(!empty($ajax_mode)){
                    $parameters = Request::GetParameters( METHOD_POST );
                    
                    //подсчет рейтинга
                    $string_rating = $parameters['transport'] . '-' . $parameters['infrastructure'] . '-' . $parameters['safety'] . '-' . $parameters['ecology'] . '-' .  $parameters['quality'];
                    $avg_rating = ( $parameters['transport'] + $parameters['infrastructure'] + $parameters['safety'] + $parameters['ecology'] + $parameters['quality'] ) / 5;
                    //определение района ЖК
                    $district = $db->fetch(" SELECT * FROM " . $sys_tables['housing_estates_districts'] ." WHERE FIND_IN_SET( " . $id . ",  housing_estates_ids) ") ;
                    //запись
                    $db->querys(" INSERT INTO " . $sys_tables['housing_estates_voting'] . " SET 
                                    id_parent = ?, id_user = ?, id_district = ?, rating = ?, rating_fields = ?, rating_transport = ?, rating_infrastructure = ?, rating_safety = ?, rating_ecology = ?, rating_quality = ?, is_expert = 1, ip = ?, browser = ?, ref = ?",
                                    $id, $auth->id, $district['id'], $avg_rating, $string_rating, 
                                    $parameters['transport'], $parameters['infrastructure'], $parameters['safety'],$parameters['ecology'], $parameters['quality'],
                                    Host::getUserIp(), $db->real_escape_string($_SERVER['HTTP_USER_AGENT']), Host::getRefererURL()
                    );
                    Response::SetString( 'title', 'Спасибо за ваш голос!' );
                    $ajax_result['rating'] = Convert::ToSquare( $avg_rating );
                    if( empty( $user['resume'] ) && !empty( $rating->voteComplete() )  ) $ajax_result['resume_popup'] = true;
                    $ajax_result['id'] = $id;
                    $ajax_result['ok'] = true;
                    $module_template = "/templates/popup.success.html";
                }            
                break;
            case empty( $action ):
                $housing_estates = new HousingEstates();
                $item = $housing_estates->getItem( $id );
                Response::SetString( 'housing_estate_title', $item['title'] );
                $ajax_result['ok'] = true;
                $module_template = "popup.voting.html";
                break;
        }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Попап отправки резюме по районам
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $action == 'popup_resume':
        
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch(true){
            case $action == 'add':
                //отправка заявки с карточки объекта
                if(!empty($ajax_mode)){
                    $parameters = Request::GetParameters( METHOD_POST );
                    if( !empty( $parameters['resume'] ) ){
                        $db->querys(" UPDATE " . $sys_tables['housing_estates_experts'] . " set resume = ? WHERE id = ?", $parameters['resume'], $user['id'] );
                        $ajax_result['ok'] = true;
                        Response::SetString( 'title', 'Спасибо за ваш отзыв!' );
                        $module_template = "/templates/popup.success.html";
                    }
                }            
                break;
            case empty( $action ):
                
                $ajax_result['ok'] = true;
                $module_template = "popup.resume.html";
                break;
        }
        break;        
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Попап голосования
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case $action == 'personal_info':
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch(true){
            case empty($action):
                //отправка заявки с карточки объекта
                if(!empty($ajax_mode)){
                    $parameters = Request::GetParameters( METHOD_POST );
                    
                    if( !empty( $parameters['fio'] )  && !empty( $parameters['job'] ) ){
                        $db->querys(" UPDATE " . $sys_tables['housing_estates_experts'] . " set job = ? WHERE id = ?", $parameters['job'], $user['id'] );
                        $db->querys(" UPDATE " . $sys_tables['users'] . " set name = ?, lastname = ? WHERE id = ?", $parameters['fio'], '', $user['id_user'] );
                        $ajax_result['ok'] = true;
                    }

                }            
                break;
        }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Жилые комплексы  - список
   ////////////////////////////////////////////////////////////////////////////////////////////////
   case empty($action):            
        
        // "прямые" условия
        $housing_estates = new HousingEstates();
        $where = !empty( $user ) ? $sys_tables['housing_estates'] . ".id IN (" . $user['housing_estates_ids'] . ")" : "1";
        $page_parameters = Request::GetParameters( METHOD_GET );
        if( !empty( $page_parameters['class'] ) ) $where .= " AND " . $sys_tables['housing_estates'] . ".class=" . $db->real_escape_string( $page_parameters['class'] );
        Response::SetArray( 'parameters', $page_parameters );
        $housing_estate_classes = $db->fetchall("SELECT id,title FROM ".$sys_tables['housing_estate_classes'] . ( !empty($is_apartments) ? " WHERE id != 1 " : "" ) . " ORDER BY id");
        Response::SetArray( 'housing_estate_classes', $housing_estate_classes );
        if(empty($ajax_mode)){
            $GLOBALS['js_set'][] = '/js/form.validate.js';
            $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
            $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
            $GLOBALS['css_set'][] = '/css/estate_search.css';
            $GLOBALS['css_set'][] = '/css/autocomplete.css';

            $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
            $GLOBALS['js_set'][] ="/modules/favorites/favorites.js";
            $GLOBALS['js_set'][] = '/modules/housing_estates/item.js';
            $GLOBALS['css_set'][] = '/modules/housing_estates/style.css';
            
            $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
            $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';            

            
            $GLOBALS['js_set'][] = '/modules/housing_estates_rating/script.js';
            $GLOBALS['css_set'][] = '/modules/housing_estates_rating/style.css';

            
        }
        
        // сортировка
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        if(!empty($sortby)) Response::SetBoolean('noindex',true); //meta-тег robots = noindex
        $orderby = $sys_tables['housing_estates'].".advanced = 1 DESC, ". ( !empty($rating) ? $sys_tables['housing_estates'].".random_sorting, " : "" ) .$sys_tables['housing_estates'].".id_main_photo > 0 DESC, ";
        switch($sortby){
            case 11:
                //по рейтингу по возрастанию
                $orderby .= $sys_tables['housing_estates'].".rating !=0  ASC, rating ASC";
                break;
            case 10:
                //по рейтингу по убыванию
                $orderby .= $sys_tables['housing_estates'].".rating !=0  DESC, rating DESC";
                break;
            case 9: 
                // по застройщику по убыванию
                $orderby .= $sys_tables['housing_estates'].".status  ASC";
                break;
            case 8: 
                // по застройщику по возрастанию
                $orderby .= $sys_tables['housing_estates'].".status  DESC";
                break;
            case 7: 
                // по застройщику по убыванию
                $orderby .= $sys_tables['housing_estates'].".id_user > 0  DESC, developer_title DESC";
                break;
            case 6: 
                // по застройщику по возрастанию
                $orderby .= $sys_tables['housing_estates'].".id_user > 0  DESC, developer_title ASC";
                break;
            case 5: 
                // по метро по убыванию
                $orderby .= $sys_tables['housing_estates'].".id_subway > 0 DESC, subway DESC"; 
                break;
            case 4: 
                // по метро по возрастанию
                $orderby .= $sys_tables['housing_estates'].".id_subway > 0 DESC, subway ASC"; 
                break;
            case 3: 
                // по району по возрастанию
                $orderby .= $sys_tables['housing_estates'].".id_region DESC, ".$sys_tables['housing_estates'].".id_district > 0 DESC, district DESC, district_area DESC"; 
                break;
            case 2: 
                // по району по убыванию
                $orderby .= $sys_tables['housing_estates'].".id_region DESC, ".$sys_tables['housing_estates'].".id_district > 0 DESC, district ASC, district_area ASC"; 
                break;
            case 1: 
            default: 
                // по району по убыванию
                $orderby .= "build_total_objects DESC";
                break;
        }
        
        
        $list = $housing_estates->Search($where, 1000, 0, $orderby, $user['id_user'] );         
        foreach( $list as $k => $item ) $list[$k]['can_vote'] = $rating->canVote( $item['id'] );
        
        Response::SetArray('list', $list);
        Response::SetString('requested_url', $this_page->requested_url);  
        
        Response::SetInteger( 'not_show_tgb', true );
        
        $h1 = ' Голосование за ЖК ' . $user['district_title'];
        Response::SetString('h1', $h1 );  
        $new_meta = array(
            'h1' => $h1, 
            'title' => $h1 . ( !empty($paginator->items_count) ? ' - ' . Convert::ToNumber($paginator->items_count) . makeSuffix($paginator->items_count, ' объявлени', array('е', 'я', 'й'))  : '' ),
            'description' => ' Уникальные предложения, которых не найти на других сайтах. ☆ Мы постоянно отслеживаем актуальность и достоверность объявлений'
        );
        $this_page->manageMetadata( $new_meta, true ); 
        $module_template = '/modules/housing_estates/templates/list.html';
        
        //аякс вывод рейтинга
        if( $ajax_mode ){
            //вывод попапа
            if( empty( $user['resume'] ) ) $ajax_result['resume_popup'] = true;
            $ajax_list = [];
            foreach( $list as $k => $item){
                if( empty( $item['can_vote'] ) ) {
                    $ajax_list[$k] = array(
                         'id'               => $item['id']
                        ,'expert_rating'    => Convert::ToFloat( $item['expert_rating'] )
                    );
                } else $ajax_result['resume_popup'] = false;
            }
            $ajax_result['list'] = $ajax_list;
            $ajax_result['ok'] = true;
        }
        break;       
}
?>
