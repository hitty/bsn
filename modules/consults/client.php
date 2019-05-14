<?php
require_once( 'includes/class.paginator.php' );
require_once( 'includes/class.opinions.php' );
require_once( 'includes/class.consults.php' );

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php' );

$GLOBALS['css_set'][] = $this_page->module_path.'/style.css';
//если это лк, подключаем
if(preg_match( '/^members/',$this_page->requested_url)){
    $GLOBALS['js_set'][] = '/modules/consults/cabinet.script.js';
    Response::SetBoolean( 'not_show_top_banner',true);
} 
else{
    $GLOBALS['js_set'][] = '/modules/consults/script.js';
    $GLOBALS['css_set'][] = '/css/form.css';
} 
$GLOBALS['js_set'][] = '/modules/estate/list_options.js';
$GLOBALS['css_set'][] = '/css/estate_search.css';
$GLOBALS['js_set'][]="/modules/estate/interface.js";
//для фильтра справа
$GLOBALS['js_set'][] = '/js/jquery.ajax.filter.js';

//записей на страницу
$strings_per_page = 10;

if(!$ajax_mode){
    //для datetimepicker
    $GLOBALS['js_set'][] = '/js/datetimepicker/jquery.datetimepicker.js';
    $GLOBALS['css_set'][] = '/js/datetimepicker/jquery.datetimepicker.css';
}

$from=0;
$action = empty( $this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
Response::SetArray( 'question_categories_list', ConsultQFunctions::getCategoriesList());

switch(true){
    case $ajax_mode && $action == "popup":
    
        $template = "popup.form.wide.html";
        $initiator_selector = Request::GetString( "initiator_selector",METHOD_POST);
        Response::SetString( "initiator_selector",$initiator_selector);
        $tpl = new Template( "/modules/consults/templates/".$template,$this_page->module_path);
        
        $ajax_result['html'] = $tpl->Processing();
        $ajax_result['ok'] = true;
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // блоки
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'block':  
        if(!$this_page->first_instance) {
            $action = empty( $this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
            switch( $action){
               ////////////////////////////////////////////////////////////////////////////////////////////////
               // //список ответов для карточки вопроса
               ////////////////////////////////////////////////////////////////////////////////////////////////
                case 'form':
                
                    $id = Convert::ToInt( $this_page->page_parameters[3]);
                    
                    $sortby = false;
                    
                    switch( $sortby){
                        case 1: $sortby = " ".$sys_tables['consults_answers'].".date_in ASC"; break;
                        case 2: $sortby = " ".$sys_tables['consults_answers'].".date_in DESC"; break;
                        case 3: $sortby = " ".$sys_tables['consults_answers'].".rating ASC"; break;
                        case 4: $sortby = " ".$sys_tables['consults_answers'].".rating DESC"; break;
                        default: $sortby = " date_in ASC";
                    }
                    
                    $question = new ConsultQuestion( $id);
                    if(empty( $question)){
                        $ajax_result['ok'] = false;
                        break;
                    }
                    $item = $question->getTemplateInfo();
                    Response::SetArray( 'item',$item);
                    
                    //если это наш вопрос, отмечаем это
                    $my_question = ( $item['id_initiating_user'] == $auth->id);
                    Response::SetBoolean( 'my_question',$my_question);
                    
                    $answers_list = $question->getAnswersList(false,$sortby);
                    foreach( $answers_list as $k=>$item){
                        $answers_list[$k]['answer'] = Convert::stripUnwantedTagsAndAttrs( $item['answer']);
                    }
                    Response::SetArray( 'answers_list',$answers_list);
                    //просмотр ответа задающим вопрос
                    if(!empty( $my_question) && !empty( $answers_list)){
                        $ids = [];
                        foreach( $answers_list as $k => $item) $ids[] = $item['id'];
                        Notifications::setRead( 'consults', implode( ", ", $ids));
                    }
                    $ajax_result['ok'] = true;
                    
                    $module_template = "list.ajax.answers.html";
                    break;   
               ////////////////////////////////////////////////////////////////////////////////////////////////
               // форма на главную страницу
               ////////////////////////////////////////////////////////////////////////////////////////////////
                default:
                    require_once( 'includes/pseudo_form/pseudo_form.php' );
                    //список категорий
                    Response::SetArray( 'categories',ConsultQFunctions::getCategoriesList());
                    
                    //вставка формы отпраки через формоанализатор 
                    Response::SetArray( 'form_vars',array(
                                             'title'=>'',
                                             'name'=>''
                                            ,'email'=>''
                                            ,'category'=>''
                                            ,'text'=>''
                                            ,'url'=>'service/consultant/add'
                                       ));
                    $tpl = new Template( "block.html",$this_page->module_path);
                    $formContent = $tpl->Processing();
                                                        
                    $botFormContent = new Botobor_Form( $formContent);
                    Response::SetString( 'ask_form',$botFormContent->getCode());
                    $module_template = "ask.html";
                    if( $ajax_mode) $ajax_result['ok']=true;
                    break;
            }
        } else $this_page->http_code=404;
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // рубрикатор
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case empty( $action) && !preg_match( '/members/',$_SERVER['REQUEST_URI']): // 
        require_once( 'includes/pseudo_form/pseudo_form.php' );
        $GLOBALS['js_set'][] = 'modules/consults/pageform.js';
        $list = $db->fetchall( "SELECT ".$sys_tables['consults_categories'].".*, a.amount
                               FROM ".$sys_tables['consults_categories']."
                               LEFT JOIN (SELECT ".$sys_tables['consults'].".id_category, COUNT(*) AS amount FROM ".$sys_tables['consults']." WHERE status = 1 GROUP BY id_category) a ON ".$sys_tables['consults_categories'].".id = a.id_category
                               ",'id' );
        Response::SetArray( 'list',$list);
        
        $h1 = empty( $this_page->page_seo_h1) ? 'Консультации по недвижимости' : $this_page->page_seo_h1;
        Response::SetString( 'h1', $h1);    
        $new_meta = array( 'title'=>'Консультации по недвижимости. Задать вопрос онлайн','keywords'=>$h1, 'description'=>$h1);
        
        Response::SetArray( 'form_vars',array(
                                     'title'=>"",
                                     'name'=>(empty( $auth)?"":$auth->name)
                                    ,'email'=>(empty( $auth)?"":$auth->email)
                                    ,'category'=>''
                                    ,'category_title'=>''
                                    ,'text'=>''
                                    ,'url'=>'service/consultant/add'
                               ));
        
        Response::SetBoolean( 'payed_format', true );
        $module_template = "mainpage.html";
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // отправка вопроса
   ////////////////////////////////////////////////////////////////////////////////////////////////        
    case $action == 'success':
        if (!empty( $this_page->page_parameters[2])){
            $this_page->http_code=404;
            break;
        }
        $obj_type = (!empty( $this_page->page_parameters[1]))?( $this_page->page_parameters[1]):'others';
        if (!in_array( $obj_type,array( 'others','build','live','commercial','country' ))){$this_page->http_code=404;break;}
        Response::SetString( 'title', 'Спасибо за ваш вопрос!' );
        Response::SetString( 'object_type',$obj_type);     // тип объектов для рекомендации к просмотру
        $GLOBALS['js_set'][]="/modules/estate/interface.js";
        $module_template="success.html";
    break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // добавление вопроса
   ////////////////////////////////////////////////////////////////////////////////////////////////      
    case $ajax_mode && $action == 'add':
        $ip = Host::getUserIp();
        $authorized = $auth->checkAuth();
        $field['mail'] = true;
        Response::SetString( 'email_text','E-mail' );
        require_once( 'includes/class.email.php' );
        require_once( 'includes/pseudo_form/pseudo_form.php' );
        //список категорий
        Response::SetArray( 'categories', ConsultQFunctions::getCategoriesList() );
        $question_title = $question_name = $question_email = $question_text = $question_category = "";
        $errors = [];
        $post_parameters = Request::GetParameters(METHOD_POST);
        // если была отправка формы
        if(!empty( $post_parameters ) ) {
            
            if( $authorized ){
                $post_parameters['name'] = $auth->name;
                $post_parameters['email'] = $auth->email;
                $post_parameters['id_user'] = $auth->id;
            }
            $errors = ConsultQFunctions::validateAddFormParams( $post_parameters );
            //если все в порядке, создаем новый вопрос, он идет на модерацию
            if(empty( $errors)){
                $result = new ConsultQuestion( false, $post_parameters );
            }else{
                $result = false;
                $ajax_result['errors'] = implode( ',', $errors );
            } 
            if( !empty( $result ) ) {
                Response::SetString( 'text', '' );
                Response::SetString( 'title', $post_parameters['name'] . ', спасибо за ваш вопрос!<br/><dr/>Когда специалист на него ответит, вы получите письмо на указанный Email!' );
                $ajax_result['ok'] = true;
                $module_template = "/templates/popup.success.html";
            }
        }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // добавление ответа
   ////////////////////////////////////////////////////////////////////////////////////////////////      
    case $ajax_mode && $action == 'add-answer':
        $ip = Host::getUserIp();
        $authorized = $auth->checkAuth();
        
        $question_id = Request::GetInteger( 'question_id',METHOD_POST);
        
        $answer_text = Request::GetString( 'text',METHOD_POST);
        
        if(!$authorized || empty( $question_id) || empty( $answer_text)){
            $ajax_result['ok'] = false;
            break;
        }
        
        //если все в порядке, создаем новый ответ, он идет на модерацию
        $this_question = new ConsultQuestion( $question_id);
        //проверяем что такой вопрос есть, он открыт или задан этому пользователю, и пользователь может ответить
        if(empty( $this_question) || ( $this_question->visible_to_all == 2 && $this_question->id_respondent_user != $auth->id) || (!$this_question->checkIfCanAnswerThis( $auth->id))){
            $ajax_result['ok'] = true;
            break;
        }
        
        $ajax_result['ok'] = $this_question->addAnswer(array( 'answer'=>$answer_text,'id_user'=>$auth->id));
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // форма ответа
   ////////////////////////////////////////////////////////////////////////////////////////////////      
    case $ajax_mode && $action == 'answer-form':
        $id = !empty( $this_page->page_parameters[1] ) ? $this_page->page_parameters[1] : false;
        if( !empty( $id ) ) {
            $question = $db->fetch( "SELECT * FROM " . $sys_tables['consults'] . " WHERE id = ?", $id );
            Response::SetArray( 'item', $question );
        }
        $module_template = "answer.form.html";
        $ajax_result['ok'] = true;
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // форма ответа
   ////////////////////////////////////////////////////////////////////////////////////////////////      
    case $ajax_mode && $action == 'answer':
        $id = !empty( $this_page->page_parameters[1] ) ? $this_page->page_parameters[1] : false;
        $parameters = Request::GetParameters( METHOD_POST );
        if( !empty( $id ) && !empty( $parameters['answer'] ) ) {
            $res = $db->query( "INSERT INTO " . $sys_tables['consults_answers'] . " 
                               SET status = ?, answer = ?, id_parent = ?, date_in = NOW(), id_user = ?",
                               1, $parameters['answer'], $id, $auth->id
            );
            $ajax_result['ok'] = $res;
            $ajax_result['answer'] = $parameters['answer'];
            $ajax_result['author'] = ( !empty( $auth->name ) ? $auth->name : '' ) . ' ' . ( !empty( $auth->lastname ) ? $auth->lastname : '' ) . ' - ' . date( 'd.m.Y' );
            $ajax_result['id'] = $id;
            Response::SetString( 'title', 'Спасибо за ваш ответ' );
            Response::SetString( 'text', '' );
            $question = $db->fetch( " SELECT * FROM " . $sys_tables['consults'] . ' WHERE id = ?', $id );
            if( !empty( $question ) ) {
                $consult = new ConsultQuestion();
                $consult->sendAskedUserNotification( $id, false, array_merge( $parameters, $question ) );
            }
            $module_template = "/templates/popup.success.html";
        }
        
        
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // голосование
   ////////////////////////////////////////////////////////////////////////////////////////////////      
    case $action == 'vote_for':
        //голосуют только залогиненные пользователи
        $answer_id = Request::GetInteger( 'id',METHOD_POST);
        if(!$ajax_mode || empty( $auth->id) || empty( $answer_id)){
            $this_page->http_code=404;
            break;
        }
        $class = Request::GetString( 'class',METHOD_POST);
        if( $class == 'vote_for' ){
            $vote_for = 1;
            $vote_against = 0;
        }else{
            $vote_for = 0;
            $vote_against = 1;
        }
        $res = $db->query( "INSERT INTO ".$sys_tables['consults_answers_votings']." SET id_parent = ?, vote_for = ?, vote_against = ?, id_user = ?",$answer_id, $vote_for, $vote_against, $auth->id);
        $res = $res && $db->query( "UPDATE ".$sys_tables['consults_answers']." SET rating = rating ".(!empty( $vote_for)?"+":"-" )." 1"." WHERE id = ?",$answer_id);
        $ajax_result['ok'] = true;
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // лучший ответ
   ////////////////////////////////////////////////////////////////////////////////////////////////      
    case $action == 'make_best':
        $answer_id = Request::GetInteger( 'answer_id',METHOD_POST);
        $question_id = Request::GetInteger( 'question_id',METHOD_POST);
        if(empty( $answer_id) || !Validate::isDigit( $answer_id) || empty( $question_id) || !Validate::isDigit( $question_id)){
            $ajax_result['ok'] = false;
            break;
        }
        $question = new ConsultQuestion( $question_id);
        
        if( $question->id_initiating_user != $auth->id){
            $ajax_result['ok'] = false;
            break;
        }
        $ajax_result['ok'] = $question->makeBestAnswer( $answer_id);
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // сохраняем черновик ответа или отправляем на модерацию
   ////////////////////////////////////////////////////////////////////////////////////////////////      
    case $action == "add_answer" && preg_match( '/members/',$_SERVER['REQUEST_URI']):
        if(empty( $auth->id) || !ConsultQFunctions::checkIfCanAnswer( $auth->id)) {
            $this_page->http_code = 403;
            break;
        }
        if( $ajax_mode){
            $id = Request::GetInteger( 'id',METHOD_POST);
            $answer = Request::GetString( 'answer',METHOD_POST);
            $answer_id = Request::GetInteger( 'answer_id',METHOD_POST);
            $id_parent = Request::GetInteger( 'id_parent',METHOD_POST);
            $is_draft = Request::GetString( 'is_draft',METHOD_POST);
            $question = new ConsultQuestion( $id);
            if( !empty( $answer_id ) ) $res = $question->updateAnswer( [ 'answer'=>$answer,'id'=>$answer_id,'id_parent'=>$id_parent,'id_user'=>$auth->id,'status'=>((( $is_draft == "false" ))?2:5) ]);
            else $res = $question->addAnswer(array( 'answer'=>$answer,'id_user'=>$auth->id,'is_draft'=>( $is_draft == "true" )));
            $ajax_result['ok'] = $res;
        }
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // заглавная страница консультаций в лк и содержимое вкладок по типам
   ////////////////////////////////////////////////////////////////////////////////////////////////      
    case empty( $action) && preg_match( '/members/',$_SERVER['REQUEST_URI']):
        if(empty( $auth->id)) {
            $this_page->http_code = 403;
            break;
        }
        $consultant_user =  ConsultQFunctions::checkIfCanAnswer( $auth->id); $consultant_user = true;
        Response::SetBoolean( 'consultant_user',$consultant_user);
        if(!$ajax_mode){
            $GLOBALS['js_set'][]="/modules/consults/script.js";
            Response::SetString( 'cabinet_page',"consults" );
            Response::SetBoolean( 'conversions',true);
            ///
            //чтобы был фильтр и данные для фильтра
            ///
            Response::SetBoolean( 'filter',true);
            
            //список категорий
            $filter_consults_categories = $db->fetchall( "SELECT * FROM ".$sys_tables['consults_categories']." ORDER BY priority ASC" );
            Response::SetArray( 'filter_consults_categories',$filter_consults_categories);
            
            //список сотрудников для агентства
            if(!empty( $auth->id_agency) && $auth->agency_admin == 1){
                $filter_agents = $db->fetchall( "SELECT id, CONCAT(name,' ',lastname) as title,(user_activity = 2) AS lawyer FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
                Response::SetArray( 'filter_agents', $filter_agents);
            }
            
            //если это уже агентство, то просто дублируем список сотрудников в "Кто ответил"
            if( !empty( $filter_agents ) ) Response::SetArray( 'filter_respondents',$filter_agents);
            else Response::SetArray(
                'filter_respondents',
                [ 
                    'id'        =>  $auth->id,
                    'title'     =>  'Я',
                    'lawyer'    =>  $auth->user_activity == "2" 
                ]
            );
            
            //период времени
            Response::SetBoolean( 'filter_time_periods', true);
            //группировка по категории вопроса
            Response::SetBoolean( 'group_by_consult_category', true);
            //группировка по типу заявок (для менеджеров или выше)
            Response::SetBoolean( 'group_by_respondent', true);
            ///
            //конец штук для фильтра
            ///
            
            //данные для левой панели
            if( $auth->id_group == 101 || $auth->id_group == 10 || $auth->id_group == 3 ) {
                Response::SetBoolean( 'show_agency', true);
                $user_id = "";
            }
            elseif(!empty( $auth->id_agency)) $user_id = $db->fetch( "SELECT id FROM ".$sys_tables['users']." WHERE id_agency = ? AND agency_admin = 1",$auth->id_agency)['id'];
            else $user_id = $auth->id;
            $calls_total = $db->fetch( "SELECT COUNT(*) AS cnt FROM ".$sys_tables['calls'].(empty( $user_id) ? "" : " WHERE id_user = ".$user_id))['cnt'];
            Response::SetInteger( 'calls_total',$calls_total);
            if(!empty( $auth->id_agency) && $auth->agency_admin == 1) { 
                    $users_list = $db->fetchall( "SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
                    $ids = [];
                    foreach( $users_list as $k=>$item) $ids[] = $item['id'];
                    $users_id = implode( ", ", $ids);
                
            } else $users_id = $auth->id;
            if( $auth->id_group>=3 || !empty( $auth->id_agency)) $where = "(visible_to_all = 1 && status = 2) XOR (id_user IN ( ".$users_id." ) AND status < 4)";
            else $where = "id_initiator IN ( ".$users_id." )";
            $where .= " AND id_parent_app = 0";
            $apps_total = $db->fetch( "SELECT COUNT(*) AS amount
                                      FROM ".$sys_tables['applications']."
                                      WHERE ".$where)['amount'];
            if( $consultant_user){
                $consults_total = $db->fetch( "SELECT COUNT(*) AS amount
                                              FROM ".$sys_tables['consults']."
                                              WHERE visible_to_all = 1 OR (visible_to_all = 2 AND id_respondent_user = ".$auth->id." ) AND status = 1" )['amount'];
            }else{
                $consults_total = $db->fetch( "SELECT COUNT(*) AS amount
                                              FROM ".$sys_tables['consults']."
                                              WHERE id_initiating_user = ".$auth->id." AND status IN (1,2)" )['amount'];
            }
            Response::SetInteger( 'apps_total',$apps_total);
            Response::SetInteger( 'consults_total',$consults_total);
            
            $module_template = "cabinet.html";
        }
            
            $apps_io = Request::GetString( 'app_io',METHOD_GET);
            $user_info = $db->fetch( "SELECT id_tarif,foreign_application_notification FROM ".$sys_tables['users']." WHERE id = ".$auth->id);
            $has_tarif = (empty( $user_info['id_tarif'])?false:true);
            //если фильтра еще нет, т.е. это первый вызов, берем по умолчанию для этого типа
            $common_user = !ConsultQFunctions::checkIfCanAnswer( $auth->id);
            if(empty( $apps_io)) $apps_io = "in";
            
            
            //
            Response::SetBoolean( 'common_user',$common_user);
            $status = trim( Request::GetString( 'status',METHOD_GET), '/' ) ;
            $status = (empty( $status)?"all":$status);
            $where = $tabs_where = $where_status = [];
            $clauses = [];
            $clauses['status'] = (empty( $common_user)?array( 'tablename'=>'consults','value'=>1):array( 'tablename'=>'consults','set'=>"1,2" ));
            Response::SetString( 'app_io',$apps_io);
            
            //список агентов для агентства
            if(!empty( $auth->id_agency) && $auth->agency_admin == 1) {
                $user = Request::GetInteger( 'user',METHOD_GET);
                if(!empty( $user)) {
                    $users_id = $user; //поиск по определенном пользователю
                    $only_user = true; // вопросы только для пользователя
                } else {
                    $users_list = $db->fetchall( "SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
                    $ids = [];
                    foreach( $users_list as $k=>$item) $ids[] = $item['id'];
                    $users_id = implode( ", ", $ids);
                }
            } else $users_id = $auth->id;

            
            ///
            //читаем переданные из фильтра параметры
            //дата размещения
            $date_start = Request::GetString( 'filter_date_start',METHOD_GET);
            $date_end = Request::GetString( 'filter_date_end',METHOD_GET);
            if(!empty( $date_start)){
                $clauses['question_datetime#from'] = array( 'tablename'=>'consults',"from"=>"20".implode( '-',array_reverse(explode( '.',$date_start)))." 99" );
                $tabs_where[] = $sys_tables['consults']."`question_datetime` >= '"."20".implode( '-',array_reverse(explode( '.',$date_start)))." 99"."'";
            } 
            if(!empty( $date_end)){
                $clauses['question_datetime#to'] = array( 'tablename'=>'consults',"to"=>"20".implode( '-',array_reverse(explode( '.',$date_end)))." 99" );
                $tabs_where[] = $sys_tables['consults']."`question_datetime` <= '"."20".implode( '-',array_reverse(explode( '.',$date_end)))." 99"."'";
            } 
            //категория
            $category_id = Request::GetInteger( 'consults_category',METHOD_GET);
            if(!empty( $category_id)){
                $clauses['id_category'] = array( 'tablename'=>'consults','value'=>$category_id);
                $tabs_where[] = $sys_tables['consults'].".id_category = ".$category_id;
            } 
            //пользователь - хозяин вопроса
            $id_respondent_user = Request::GetInteger( 'filter_user',METHOD_GET);
            if(!empty( $id_respondent_user)){
                $clauses['id_respondent_user'] = array( 'tablename'=>'consults','value'=>$id_respondent_user);
                $tabs_where[] = $sys_tables['consults'].".id_respondent_user = ".$id_respondent_user;
            } 
            //выбираем только вопросы, на которые дал ответ выбранный пользователь
            $user_answered = Request::GetInteger( 'users_answered',METHOD_GET);
            if(!empty( $user_answered)){
                $clauses['id_user'] = array( 'tablename'=>'consults_answers','value'=>(int)$user_answered);
                $tabs_where[] = $sys_tables['consults_answers'].".id_user = ".$user_answered;
            }
            
            $tabs_where = (!empty( $tabs_where)?" AND ".implode( $tabs_where):"" );
            
            if( $apps_io == 'in' && empty( $common_user)){
                $where_status['all'] = "((visible_to_all=1 AND ".$sys_tables['consults'].".status=1 ) OR ( ".$sys_tables['consults'].".id_respondent_user IN ( ".$users_id." ) AND visible_to_all=2))";
                $where_status['new'] = "(( ".$sys_tables['consults'].".visible_to_all = 1 OR ".$sys_tables['consults'].".id_respondent_user IN ( ".$users_id." )) AND
                                                                ".$sys_tables['consults'].".status=1 AND 
                                                                ".$sys_tables['consults'].".answers_amount = 0)";
                $where_status['answered'] = "( ((visible_to_all=1 AND ".$sys_tables['consults'].".status=1 ) OR ( ".$sys_tables['consults'].".id_respondent_user IN ( ".$users_id." ) AND visible_to_all=2)) AND
                                             ".$sys_tables['consults'].".answers_amount > 0)";
                $where_status['personal'] = "( ".$sys_tables['consults'].".id_respondent_user > 0 AND (visible_to_all = 1 OR ".$sys_tables['consults'].".id_respondent_user IN ( ".$users_id." )) )";
            } else {
                    $where_status['all'] = "id_initiating_user IN ( ".( $auth->id)." )";
                    $where_status['new'] = $where_status['all']." AND ".$sys_tables['consults'].".answers_amount = 0";
                    $where_status['answered'] = $where_status['all']." AND ".$sys_tables['consults'].".answers_amount > 0";
                    $where_status['personal'] = $where_status['all']." AND id_respondent_user > 0";
                    $where_status['my'] = $sys_tables['consults'].".id_initiating_user = ".$auth->id;
            }
            
            //добиваем условия из фильтра
            foreach( $where_status as $k=>$i){
                $where_status[$k] .= $tabs_where;
            }
            
            //формируем условие для списка
            $where = ConsultQFunctions::makeWhereClause( $clauses);
            $where = "( ".(!empty( $where)?$where." AND ":"" ).$where_status[$status]." )";
            ///
            
            //подсчитываем количество во вкладках
            if( $common_user){
                //для обычного пользователя - только его
                $objects = $db->fetchall( "SELECT IFNULL(COUNT(DISTINCT content.consults.id),0) as cnt, 'my' as type
                                          FROM ".$sys_tables['consults']."
                                          LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                                          WHERE ".$sys_tables['consults'].".id_initiating_user IN ( ".$users_id." ) AND ".$sys_tables['consults'].".status IN (1,2)" );
            }else{
                //для специалиста - куча всего
                $objects = $db->fetchall( "SELECT IFNULL(COUNT(*),0) as cnt, 'all' as type
                                          FROM ".$sys_tables['consults']."
                                          LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                                          LEFT JOIN ".$sys_tables['consults_answers']." ON ".$sys_tables['consults'].".id = ".$sys_tables['consults_answers'].".id_parent
                                          WHERE ((visible_to_all=1 AND ".$sys_tables['consults'].".status=1 ) OR ( ".$sys_tables['consults'].".id_respondent_user IN ( ".$users_id." ) AND visible_to_all=2)) ".$tabs_where."
                                          UNION ALL
                                          SELECT IFNULL(COUNT(*),0) as cnt, 'new' as type
                                          FROM ".$sys_tables['consults']."
                                          LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                                          LEFT JOIN ".$sys_tables['consults_answers']." ON ".$sys_tables['consults'].".id = ".$sys_tables['consults_answers'].".id_parent
                                          WHERE (( ".$sys_tables['consults'].".visible_to_all = 1 OR ".$sys_tables['consults'].".id_respondent_user IN ( ".$users_id." )) AND
                                                 ".$sys_tables['consults'].".status=1 AND 
                                                 ".$sys_tables['consults'].".answers_amount = 0 )".$tabs_where."
                                          UNION ALL
                                          SELECT IFNULL(COUNT(*),0) as cnt, 'answered' as type
                                          FROM ".$sys_tables['consults']."
                                          LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                                          LEFT JOIN ".$sys_tables['consults_answers']." ON ".$sys_tables['consults'].".id = ".$sys_tables['consults_answers'].".id_parent
                                          WHERE ( ((visible_to_all=1 AND ".$sys_tables['consults'].".status=1 ) OR ( ".$sys_tables['consults'].".id_respondent_user IN ( ".$users_id." ) AND visible_to_all=2)) AND
                                                 ".$sys_tables['consults'].".answers_amount > 0 )".$tabs_where."
                                          UNION ALL
                                          SELECT IFNULL(COUNT(*),0) as cnt, 'personal' as type
                                          FROM ".$sys_tables['consults']."
                                          LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                                          LEFT JOIN ".$sys_tables['consults_answers']." ON ".$sys_tables['consults'].".id = ".$sys_tables['consults_answers'].".id_parent
                                          WHERE ( ( ".$sys_tables['consults'].".id_respondent_user > 0 AND (visible_to_all = 1 OR ".$sys_tables['consults'].".id_respondent_user IN ( ".$users_id." )) ) )".$tabs_where);
            }
            Response::SetArray( 'objects', $objects );
            Response::SetInteger( 'total_objects', !empty( $objects[0]['cnt'] ) ? $objects[0]['cnt'] : 0 );
            if( $ajax_mode ){
                $ajax_result['types']['published'] = $objects;
                $ajax_result['count'] = $objects[0]['cnt'];
                $ajax_result['page'] = 'consults';
                
                //количество элементов на странице
                $count = Request::GetInteger( 'count', METHOD_GET);            
                if(empty( $count)) $count = Cookie::GetInteger( 'View_count_estate' );
                if(empty( $count)) {
                    $count = Config::$values['view_settings']['strings_per_page'];
                    Cookie::SetCookie( 'View_count_estate', Convert::ToString( $count), 60*60*24*30, '/' );
                }
                
                // страница списка
                $page = Request::GetInteger( 'page', METHOD_GET);
                if(empty( $page)) $page = 1;
                
                $paginator = new Paginator( $sys_tables['consults']." LEFT JOIN ".$sys_tables['consults_answers']." ON ".$sys_tables['consults'].".id = ".$sys_tables['consults_answers'].".id_parent", $count, $where);
                $paginator->link_prefix = '/members/conversions/consults/?status='.$status.'&page=';
                if( $paginator->pages_count>1){
                    Response::SetArray( 'paginator', $paginator->Get( $page));
                }
                if( $page > $paginator->pages_count) $page = $paginator->pages_count;
                if( empty( $page ) ) $page = 1;
                
                $list= $db->fetchall( "SELECT ".$sys_tables['consults'].".*,
                                             CONCAT( '/service/consultant/',".$sys_tables['consults_categories'].".code,'/',".$sys_tables['consults'].".id,'/' ) AS question_url,
                                             DATE_FORMAT( ".$sys_tables['consults'].".`question_datetime`,'%e %M %Y<br /> %k:%i' ) AS question_datetime_formatted,
                                             IF( ".$sys_tables['consults'].".id_respondent_user > 0,true,false) AS personal_question,
                                             ".$sys_tables['consults_categories'].".title AS category_title,
                                             IF( ".$sys_tables['consults_answers'].".id IS NOT NULL,".$sys_tables['consults_answers'].".id,'' ) AS your_answer_id,
                                             IF( ".$sys_tables['consults_answers'].".status IS NOT NULL,".$sys_tables['consults_answers'].".status,0) AS your_answer_status,
                                             IF( ".$sys_tables['consults_answers'].".answer IS NOT NULL,".$sys_tables['consults_answers'].".answer,'' ) AS your_answer
                                      FROM ".$sys_tables['consults']."
                                      LEFT JOIN ".$sys_tables['consults_answers']." ON ".$sys_tables['consults_answers'].".id_parent = ".$sys_tables['consults'].".id AND ".$sys_tables['consults_answers'].".id_user = ?
                                      LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults_categories'].".id = ".$sys_tables['consults'].".id_category
                                      LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['consults'].".id_respondent_user
                                      WHERE ".$where."
                                      ORDER BY ".$sys_tables['consults'].".id DESC
                                      LIMIT ".$paginator->getLimitString( $page),'id',$auth->id);
                
                $consults_ids = array_keys( $list);

                
                //ответы на вопросы
                $question_keys = implode( ',',array_keys( $list));
                $answers = $db->fetchall( "SELECT *,
                                                 DATE_FORMAT( ".$sys_tables['consults_answers'].".date_in,'%d.%m.%Y' ) as normal_date,
                                                 CONCAT( ".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname) AS user_info
                                          FROM ".$sys_tables['consults_answers']." 
                                          LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['consults_answers'].".id_user = ".$sys_tables['users'].".id
                                          WHERE " . ( !empty( $question_keys) ? "id_parent IN ( ".$question_keys." ) AND " : "" ) . $sys_tables['consults_answers'].".status = 1 ORDER BY date_in DESC" );
                $answers_list = array_fill_keys(array_keys( $list),[]);
                foreach( $answers as $key=>$item){
                    $item['answer'] = Convert::stripUnwantedTagsAndAttrs(strip_tags( $item['answer']));
                    if( $item['normal_date'] == "00.00.0000" ) $item['normal_date'] = false;
                    $answers_list[$item['id_parent']] = array( $item);
                }
                
                Response::SetArray( 'list', $list);
                Response::SetArray( 'answers_list', $answers_list);
                Response::SetInteger( 'list_length',$paginator->items_count);
                $module_template = "cabinet.list.html";
                $ajax_result['ok'] = true;
            }
        break;
    ///////////////////////////////////////////////////
    //список вопросов в категорию аяксом               
    ///////////////////////////////////////////////////
    case !empty( $action ):
        $category = $db->fetch( "SELECT * FROM ".$sys_tables['consults_categories']." WHERE code = ?", $action );
        if( empty( $category ) ) $question_category = '';
        else $question_category = $category['title'];
        
        $parameters = Request::GetParameters( METHOD_GET );
        $page = !empty( $parameters['page'] ) ? $parameters['page'] : 0;
        if(empty( $page) || $page < 1) $page = 1;
        // создаем пагинатор для списка
        $where = ( !empty( $category['id'] ) ? 'id_category = ' . $category['id'] . " AND " : "" ) . " status = 1";
        
        Response::SetInteger( 'this_category',$category['id']);
        
        $count = Request::GetInteger( 'count', METHOD_POST);            
        if(!empty( $count)) $get_parameters['count'] = $count;
        else $count = Cookie::GetInteger( 'View_count_estate' );
        if(empty( $count)) {
            $count = Config::$values['view_settings']['strings_per_page'];
            Cookie::SetCookie( 'View_count_estate', Convert::ToString( $count), 60*60*24*30, '/' );
        }
        
        //сортировка
        $sortby = Request::GetInteger( 'sortby', METHOD_POST);
        $sort_list = ConsultQFunctions::getSortList();
        Response::SetArray( 'sort_list',$sort_list);
        if(!empty( $sortby)){
            Response::SetString( 'sortby_title',$sort_list[$sortby]['sort_title']);
            Response::SetString( 'sortby_num',$sortby);
        }else{
            Response::SetString( 'sortby_title',"" );
            Response::SetString( 'sortby_num',0);
        }
        
        $paginator = new Paginator( $sys_tables['consults'], $count, $where);
        //редирект с несуществующих пейджей
        if( $paginator->pages_count>0 && $paginator->pages_count<$page){
            Host::Redirect( '/' . $this_page->requested_path . '/?page='.$paginator->pages_count);
            exit(0);
        }
        //формирование url для пагинатора
        if( $this_page->real_url!=$this_page->requested_url && empty( $url_params['query'])) $paginator_link_base = '/'.$this_page->requested_url.'/?';
        elseif( $this_page->real_url!=$this_page->requested_url)  $paginator_link_base = '/'.$this_page->requested_path.'/?'.(!empty( $url_params['query'])?''.$url_params['query'].'&':'' );
        else $paginator_link_base = '/'.$this_page->requested_path.'/?'.(empty( $get_parameters)?"":Convert::ArrayToStringGet( $get_parameters).'&' );
        $paginator->link_prefix = $paginator_link_base . ( !empty( $sortby ) ? 'sortby=' . $sortby . '&' : '' ) . 'page=';
        if( $paginator->pages_count>1){
            Response::SetArray( 'paginator', $paginator->Get( $page));
        }
        $orderby = ConsultQFunctions::makeSort( $sortby);
        
        Response::SetInteger( 'sortby',$sortby);
        Response::SetString( 'sorting_url', $paginator_link_base.'page='.$page.'&sortby=' );
        
        $strings_per_page = $count;
        
        $list = $db->fetchall( "SELECT ".$sys_tables['consults'].".*,
                                      CONCAT( '/service/consultant/',".$sys_tables['consults_categories'].".code,'/',".$sys_tables['consults'].".id,'/' ) AS question_url,
                                      IF( ".$sys_tables['consults'].".title != '',".$sys_tables['consults'].".title,".$sys_tables['consults'].".question) AS question_title,
                                      DATE_FORMAT( ".$sys_tables['consults'].".question_datetime,'%e %b %Y, %H:%i' ) as question_normal_date,
                                      ".$sys_tables['consults'].".name AS question_author_info,
                                      ".$sys_tables['consults'].".answers_amount,
                                      ".$sys_tables['consults_categories'].".code AS category_url
                               FROM  ".$sys_tables['consults']."
                               LEFT JOIN  ".$sys_tables['consults_categories']." ON ".$sys_tables['consults_categories'].".id=".$sys_tables['consults'].".id_category
                               WHERE $where
                               ".(!empty( $orderby)?"ORDER BY ".$orderby:"" )."
                               LIMIT ".$paginator->getFromString( $page).",".$strings_per_page,"id" );
        
        $question_keys = implode( ',',array_keys( $list));
        $answers = $db->fetchall( "SELECT *,
                                         DATE_FORMAT( ".$sys_tables['consults_answers'].".date_in,'%d.%m.%Y' ) as normal_date,
                                         CONCAT( ".$sys_tables['users'].".name,' ',".$sys_tables['users'].".lastname) AS user_info
                                  FROM ".$sys_tables['consults_answers']." 
                                  LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['consults_answers'].".id_user = ".$sys_tables['users'].".id
                                  WHERE " . ( !empty( $question_keys) ? "id_parent IN ( ".$question_keys." ) AND " : "" ) . $sys_tables['consults_answers'].".status = 1 ORDER BY date_in DESC" );
        $answers_list = array_fill_keys(array_keys( $list),[]);
        foreach( $answers as $key=>$item){
            $item['answer'] = Convert::stripUnwantedTagsAndAttrs(strip_tags( $item['answer']));
            if( $item['normal_date'] == "00.00.0000" ) $item['normal_date'] = false;
            $answers_list[$item['id_parent']] = array( $item);
        }
        
        Response::SetString( 'full_count',count( $list));
        Response::SetString( 'full_answers_count',count( $answers));
        Response::SetArray( 'answers_list',$answers_list);
        Response::SetArray( 'list',$list);
        
        $module_template = "list.html";

        //добавление title
        $h1 = empty( $this_page->page_seo_h1) ? 'Консультации по '.$category['title_genitive'] : $this_page->page_seo_h1;
        Response::SetString( 'h1', $h1);            
        $new_meta = array( 'title'=>$h1.' - Консультации', 'description' =>$h1, true);
        $this_page->manageMetadata( $new_meta, true);            

        break;
}
//хлебные крошки
$this_page->clearBreadcrumbs();
$this_page->addBreadcrumbs( 'Сервисы', 'service', 0);
$this_page->addBreadcrumbs( 'Консультации по недвижимости', 'consultant', 1, Config::Get( 'services_breadcrumbs' ));
if(!empty( $this_page->page_parameters[0]) && !empty( $item)) {
    $category_list = $db->fetchall( "SELECT CONCAT( 'service/consultant/', code) as url, title as title FROM ".$sys_tables['consults_categories']." WHERE id != ?", 'url', $this_page->page_parameters[0]);
    $this_page->addBreadcrumbs(
        !empty( $category ) ? $category['title'] : $item['category_title'], 
        $this_page->page_parameters[0], 
        2, 
        $category_list
    );
} 
?>