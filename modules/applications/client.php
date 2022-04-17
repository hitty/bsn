<?php  
require_once('includes/class.paginator.php');
require_once('includes/class.convert.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.messages.php');
require_once('includes/class.applications.php');
$GLOBALS['css_set'][] = '/modules/applications/style.css';
$GLOBALS['js_set'][] = '/js/form.validate.js';

//для фильтра справа
$GLOBALS['js_set'][] = '/js/jquery.ajax.filter.js';
//автозаполнение
$GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
Request::GetInteger('estate_type',METHOD_GET);

//для отправки системных сообщений
$messages = new Messages();

if(!$ajax_mode){
    //для datetimepicker
    $GLOBALS['js_set'][] = '/js/datetimepicker/jquery.datetimepicker.js';
    $GLOBALS['css_set'][] = '/js/datetimepicker/jquery.datetimepicker.css';
}
Response::SetString('cabinet_page', 'applications');
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
if($auth->id == 1544) $auth->agency_admin = 1;
$only_user = false;
switch(true){
    
    /////////////////////////////////////////////////////
    // Всплывашки
    /////////////////////////////////////////////////////
    case $ajax_mode && $action == "popup":
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch(true){
            /////////////////////////////////////////////////////
            // Форма покупки заявки
            /////////////////////////////////////////////////////
            case !empty($action) && $action == 'buy':
                $id = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                if( empty( $id ) || !Validate::isDigit( $id ) ) break;

                //получение данных заявки
                $application = new ApplicationList();
                $clauses['id'] = array( 'tablename' => 'applications', 'value' => $id );
                $where = $application->makeWhereClause( $clauses );
                $item = $application->getPublicList($sys_tables['applications'].".`datetime` DESC", 0, true, $auth->id );
                if( empty( $item ) ) break;
                Response::SetArray('item', $item[0]);
                //определение стоимости и возможности купить для пользователя
                if(!empty($auth->id)) $user_info = $db->fetch("SELECT id,id_tarif,id_agency FROM ".$sys_tables['users']." WHERE id = ".$auth->id);
                Response::SetBoolean('can_buy_apps', !empty( $user_info ) && ( !empty( $user_info['id_tarif'] ) || !empty( $user_info['id_agency'] ) ) );
                
                
                $module_template = "popup.buy.html";
                $ajax_result['ok'] = true;
                break;
            /////////////////////////////////////////////////////
            // Форма заявок
            /////////////////////////////////////////////////////
            default:
                if( !empty($action) && $action == 'realtor' ) Response::SetBoolean('realtor_application', true);
                $type = Request::GetString("type",METHOD_POST);                               
                switch($type){
                    case "wide-app":
                        $template = "popup.app.wide.html";
                        break;
                        
                    case "small-app":
                    default:
                        $template = "popup.app.small.html";
                        break;
                        
                }
                $initiator_selector = Request::GetString( "initiator_selector", METHOD_POST );
                Response::SetString("initiator_selector", $initiator_selector);

                $tpl = new Template( "/modules/applications/templates/" . $template, $this_page->module_path );
                
                $params = Request::GetParameters( METHOD_GET );
                foreach($params as $k => $item) Response::SetString($k, $item);
                
                $ajax_result['html'] = $tpl->Processing();
                $ajax_result['ok'] = true;
                break;
        }
        
        break;
    
    //////////////////////////////////////////////////////////
    //отдаем код формы предложения
    //////////////////////////////////////////////////////////
    case $ajax_mode && $action == "add-offer":
        
        $tpl = new Template( "/modules/applications/templates/form.offer.html", $this_page->module_path );
        $abuse_form = $tpl->Processing();
        break;
    //////////////////////////////////////////////////////////
    case $action == 'add': // 
        //отправка заявки с карточки объекта
        if(!empty($ajax_mode)){

            $create_params = [];
            $create_params = Request::GetParameters(METHOD_POST);
            if(empty($create_params)) die();
            if( $create_params['estate_type'] === 'zhiloy_kompleks' && ( $create_params['id'] == 2891 || $create_params['id'] == 2865) ) {
                Response::SetArray('data', $create_params );
                $mailer = new EMailer('mail');
                $r = $mailer->sendEmail(
                    'kya1982@gmail.com',
                    'Юрий',
                    "Новая заявка ".date('Y-m-d H:i:s'),
                    '/modules/applications/templates/mail.simple.html',
                    '',
                    '', false, false, true
                );

            } else {
                if(!empty($create_params['type'])){
                    $create_params['estate_type'] = $create_params['type'];
                    unset($create_params['type']);
                }
                if(empty($create_params['estate_type'])) $create_params['estate_type'] = Request::GetInteger('estate_type', METHOD_POST);
                if(!empty($estate_type)) $create_params['estate_type_key'] = Config::$values['object_types'][$estate_type]['key'];

                $universal_app = false;

                 $new_app = new Application(0,$create_params,null);

                if(!$new_app->checkWorkTime()) $ajax_result['message'] = $new_app->getNextWorkDayTime();
                $user_tarif = $new_app->getOwnersAttr('user_tarif');
                $ajax_result['paused_application'] = false;
                $ajax_result['name'] = $new_app->getAttr('name');
            }

            
            Response::SetString( 'title', 'Спасибо за обращение!<br/><br/>Наш специалист свяжется с вами в течение нескольких рабочих дней!' );
            $ajax_result['ok'] = true;
            $module_template = "/templates/popup.success.html";
        }
        break;
    //////////////////////////////////////////////////////////
    //заглавная страница заявок и содержимое вкладок по типам
    //////////////////////////////////////////////////////////
    case empty($action) && preg_match('/members/',$_SERVER['REQUEST_URI']):
        //если это страница со вкладками
        if(!$ajax_mode){
            //если пользоваетль неавторизован, отдаем 404
            //и проверяем, что это не агрегатор
            $is_agregator = $db->fetch("SELECT IF(is_agregator = 1,true,false) AS is_agregator FROM ".$sys_tables['agencies']." WHERE id = ".$auth->id_agency)['is_agregator'];
            $is_agregator = (empty($is_agregator)?false:true);
            $has_tarif = $db->fetch("SELECT id_tarif FROM ".$sys_tables['users']." WHERE id = ".$auth->id)['id_tarif'];
            $has_tarif = (empty($has_tarif)?false:true);
            if(empty($auth->id)){
                $this_page->http_code = 404;
                break;
            }
            
            Response::SetBoolean('not_show_top_banner',true);
            //если это обычный пользователь, страница будет немного другая
            $common_user = (($auth->id_group <3) && empty($auth->id_agency) && $auth->id != 39106 && $auth->id != 1544 && empty($has_tarif));
            Response::SetBoolean('common_user',$common_user);
            $GLOBALS['css_set'][] = '/modules/applications/style.cabinet.css';
            $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
            $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
            $GLOBALS['js_set'][] = '/modules/applications/public.apps.js';
            
            //список сотрудников для агентства
            if( (!empty($auth->id_agency) && $auth->agency_admin == 1)){
                $filter_agents = $db->fetchall("SELECT id, CONCAT(name,' ',lastname) as title FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
                Response::SetArray('filter_agents', $filter_agents);
            }

            Response::SetString('h2',"Мои заявки");
            //чтобы был фильтр
            Response::SetBoolean('filter',true);
            //входящие/исходящие заявки
            Response::SetBoolean('selector_app_io',true);
            //период времени
            Response::SetBoolean('filter_time_periods', true);
            //группировка по типу сделки
            Response::SetBoolean('group_by_app_dealtype', true);
            //группировка по типу заявок (для менеджеров или выше)
            if(!$common_user) Response::SetBoolean('group_by_owner', true);
            
            //читаем тариф пользователя (если он есть и заявки включены, списаний не будет)
            $apps_included = $db->fetch("SELECT ".$sys_tables['tarifs'].".apps_included
                                         FROM ".$sys_tables['users']."
                                         LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['users'].".id_tarif = ".$sys_tables['tarifs'].".id
                                         WHERE ".$sys_tables['users'].".id = ".$auth->id);
            //если заявки включены в тариф, или это частник, то заявки бесплатны
            $apps_included = ((!empty($apps_included['apps_included']) && ($apps_included['apps_included'] == 1)) || (empty($auth->id_agency) && $auth->id_group == 1 && $auth->id_tarif == 0));
            
            
            //данные для левой панели
            if($auth->id_group == 101 || $auth->id_group == 10 || $auth->id_group == 3 ) {
                Response::SetBoolean('show_agency', true);
                $user_id = "";
            }
            elseif(!empty($auth->id_agency)) $user_id = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id_agency = ? AND agency_admin = 1",$auth->id_agency)['id'];
            else $user_id = $auth->id;
            $calls_total = $db->fetch("SELECT COUNT(*) AS cnt FROM ".$sys_tables['calls'].(empty($user_id) ? "" : " WHERE id_user = ".$user_id))['cnt'];
            Response::SetInteger('calls_total',$calls_total);
            if(!empty($auth->id_agency) && $auth->agency_admin == 1) { 
                    $users_list = $db->fetchall("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
                    $ids = [];
                    foreach($users_list as $k=>$item) $ids[] = $item['id'];
                    $users_id = implode(", ", $ids);
                
            } else $users_id = $auth->id;
            if($auth->id_group>=3 || !empty($auth->id_agency)) $where = "(visible_to_all IN (1,3) && status = 2) XOR (id_user IN (".$users_id.") AND status < 4)";
            else $where = "id_initiator IN (".$users_id.")";
            $where .= " AND id_parent_app = 0";
            $apps_total = $db->fetch("SELECT COUNT(*) AS amount
                                      FROM ".$sys_tables['applications']."
                                      WHERE ".$where)['amount'];
            if($auth->user_activity == 2 && $auth->id_tarif > 0){
                    $consults_total = $db->fetch("SELECT COUNT(*) AS amount
                                                  FROM ".$sys_tables['consults']."
                                                  WHERE visible_to_all = 1 OR (visible_to_all = 2 AND id_respondent_user = ?) AND status = 1",$auth->id)['amount'];
            }else{
                $consults_total = $db->fetch("SELECT COUNT(*) AS amount
                                              FROM ".$sys_tables['consults']."
                                              WHERE id_initiating_user = ? AND status IN (1,2)",$auth->id)['amount'];
            }
            Response::SetInteger('apps_total',$apps_total);
            Response::SetInteger('consults_total',$consults_total);
            
            //Cookie::SetCookie('apps_included',$apps_included);
            //чтобы раскрылось подменю Обращений слева
            Response::SetBoolean('conversions',true);
            $module_template = "cabinet_applications.html";
        }
        else{
            
            $apps_io = Request::GetString('app_io',METHOD_GET);
            $user_info = $db->fetch("SELECT id_tarif,foreign_application_notification FROM ".$sys_tables['users']." WHERE id = ".$auth->id);
            $has_tarif = (empty($user_info['id_tarif'])?false:true);
            $foreign_apps_buy = (empty($user_info['foreign_application_notification'])?false:true);
            //если фильтра еще нет, т.е. это первый вызов, берем по умолчанию для этого типа
            $common_user = ($auth->id_group <3) && empty($auth->id_agency) && $auth->id != 39106 && empty($has_tarif);
            if(empty($apps_io)) $apps_io = "in";
            
            //если это агентство, проверяем не считаются ли цены отдельно, по правилам sale
            if(!$common_user){
                $sale_app_cost = AppsFunctions::getSaleAppCost($auth->id);
                //и проверяем, что это не агрегатор
                $is_agregator = $db->fetch("SELECT IF(is_agregator = 1,true,false) AS is_agregator FROM ".$sys_tables['agencies']." WHERE id = ".$auth->id_agency)['is_agregator'];
                $is_agregator = (empty($is_agregator)?false:true);
            }
            
            //
            Response::SetBoolean('common_user',$common_user);
            $status = trim( Request::GetString('status',METHOD_GET), '/');
            $status = (empty($status)?"all":$status);
            $where = $where_status = [];
            $this_app_list = new ApplicationList();
            $clauses = [];
            $where[] = $sys_tables['applications'].".status<4";
            $clauses['status'] = array('tablename'=>'applications','to'=>3);
            Response::SetString('app_io',$apps_io);
            //список агентов для агентства
            if(!empty($auth->id_agency) && $auth->agency_admin == 1) {
                $user = Request::GetInteger('user',METHOD_GET);
                if(!empty($user)) {
                    $users_id = $user; //поиск по определенном пользователю
                    $only_user = true; // заявки только для пользователя
                } else {
                    $users_list = $db->fetchall("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
                    $ids = [];
                    foreach($users_list as $k=>$item) $ids[] = $item['id'];
                    $users_id = implode(", ", $ids);
                }
            } else $users_id = $auth->id;

            if($apps_io == 'in'){
                if(!$common_user){
                            if(!empty($only_user))
                                $where_status['all'] = $sys_tables['applications'].".id_user IN (".($users_id).")";
                            else if($is_agregator)
                                $where_status['all'] = "((visible_to_all=1 AND ".$sys_tables['applications'].".status=2 ) OR 
                                                         (".$sys_tables['applications'].".id_user IN (".($users_id).") AND visible_to_all=2 AND ".$sys_tables['applications'].".status!=2))";
                            else $where_status['all'] = "((visible_to_all IN (1,3) AND ".$sys_tables['applications'].".status=2 ) OR (".$sys_tables['applications'].".id_user IN (".($users_id).") AND visible_to_all=2))";

                            if(!empty($only_user)) $where_status['new'] = "( ".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=2 )";
                            else if($is_agregator) $where_status['new'] = "(visible_to_all=1 AND ".$sys_tables['applications'].".status=2)";
                            else $where_status['new'] = "((".$sys_tables['applications'].".id_user IN (".($users_id).") OR visible_to_all IN(1,3)) AND ".$sys_tables['applications'].".status=2)";

                            if(!empty($only_user)) $where_status['performing'] = "( ".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=3 )";
                            else $where_status['performing'] = "(".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=3)";

                            if(!empty($only_user)) $where_status['finished'] = "( ".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=1 )";
                            else $where_status['finished'] = "(".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=1)";
                } else{
                        if($has_tarif){
                            $where_status['all'] = $sys_tables['applications'].".id_user IN (".($users_id).") OR 
                                                   (".$sys_tables['applications'].".status = 2 AND ".$sys_tables['applications'].".visible_to_all IN(1,3))";
                            $where_status['new'] = "(".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=2) OR
                                                    (".$sys_tables['applications'].".status = 2 AND ".$sys_tables['applications'].".visible_to_all IN(1,3))";
                        }
                        else{
                            $where_status['all'] = $sys_tables['applications'].".id_user IN (".($users_id).")";
                            $where_status['new'] = "(".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=2)";
                        }
                        $where_status['performing'] = "(".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=3)";
                        $where_status['finished'] = "(".$sys_tables['applications'].".id_user IN (".($users_id).") AND ".$sys_tables['applications'].".status=1)";
                }
            } else {
                    $where_status['all'] = "id_initiator IN (".($auth->id).")";
                    $where_status['new'] = "(".$sys_tables['applications'].".status=2 AND id_initiator IN (".($auth->id)."))";
                    $where_status['performing'] = "(id_initiator IN (".($auth->id).") AND ".$sys_tables['applications'].".status=3)";
                    $where_status['finished'] = "(id_initiator IN (".($auth->id).") AND ".$sys_tables['applications'].".status=1)";
            }
            
            
            //подсчитываем количество во вкладках
            $objects = $db->fetchall("
                                        SELECT IFNULL(COUNT(*),0) as cnt, 'all' as type 
                                        FROM ".$sys_tables['applications']." 
                                        LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                                        WHERE ".$sys_tables['applications'].".status<4 AND ".$where_status['all']." AND ".$sys_tables['applications'].".id_parent_app = 0
                                        UNION ALL
                                        SELECT IFNULL(COUNT(*),0) as cnt, 'new' as type 
                                        FROM ".$sys_tables['applications']." 
                                        LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                                        WHERE ".$sys_tables['applications'].".status<4 AND 
                                              ".$where_status['new']." AND 
                                              ".$sys_tables['applications'].".id NOT IN (
                                                    SELECT ".$sys_tables['applications'].".id_parent_app
                                                    FROM ".$sys_tables['applications']." 
                                                    LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                                                    WHERE ".$sys_tables['applications'].".status<4 AND ".$where_status['performing'].")
                                        UNION ALL
                                        SELECT IFNULL(COUNT(*),0) as cnt, 'performing' as type 
                                        FROM ".$sys_tables['applications']." 
                                        LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                                        WHERE ".$sys_tables['applications'].".status<4 AND ".$where_status['performing']."
                                        UNION ALL
                                        SELECT IFNULL(COUNT(*),0) as cnt, 'finished' as type 
                                        FROM ".$sys_tables['applications']." 
                                        LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                                        WHERE ".$sys_tables['applications'].".status<4 AND ".$where_status['finished']."
            
            ");
            $ajax_result['lq'] = $db->last_query;
            $ajax_result['types']['published'] = $objects;
            $ajax_result['count'] = $objects[0]['cnt'];
            $ajax_result['page'] = 'applications';
            
            //читаем переданные из фильтра параметры
            //дата размещения
            $date_start = Request::GetString('filter_date_start',METHOD_GET);
            $date_end = Request::GetString('filter_date_end',METHOD_GET);
            if(!empty($date_start)) $clauses['datetime#from'] = array('tablename'=>'applications',"from"=>"20".implode('-',array_reverse(explode('.',$date_start)))." 99");
            if(!empty($date_end)) $clauses['datetime#to'] = array('tablename'=>'applications',"to"=>"20".implode('-',array_reverse(explode('.',$date_end)))." 99");
            //тип сделки
            $deal_type = Request::GetInteger('groupby_dealtype',METHOD_GET);
            //тип заявки (свои-чужие-все)
            $app_type = Request::GetInteger('groupby_owner',METHOD_GET);
            
            
            if(!empty($deal_type)) $clauses['rent'] = array('tablename'=>'application_types','value'=>$deal_type);
            
            if(!empty($app_type))
                if($app_type == '1') $clauses['id_owner'] = array('tablename'=>'applications','value'=>$auth->id);
                else $clauses['id_owner'] = array('tablename'=>'applications','not_set'=>$auth->id);

            
            
            //формируем условие для списка
            $tab_parameters = compact('is_agregator','only_user','has_tarif','apps_io','common_user','status','users_id');
            $where = $this_app_list->makeWhereClause($clauses)." AND ".$this_app_list->makeLkTabClause($tab_parameters);
            //присоединяем таблицы
            $this_app_list->joinTable('work_statuses','id_work_status','id');
            $this_app_list->joinTable('owners_user_types','id_user_type','id');
            $this_app_list->joinTable('application_realtor_help_types','id_realtor_help_type','id');
            $this_app_list->joinTable('application_types','application_type','id');
            $this_app_list->joinTable('application_objects',false,false,"(".$sys_tables['application_objects'].".id = ".$sys_tables['applications'].".object_type OR 
                                                                      ".$sys_tables['applications'].".object_type = 0 AND ".$sys_tables['applications'].".id_parent!=0) AND 
                                                                      ".$sys_tables['application_objects'].".estate_type = ".$sys_tables['applications'].".estate_type");
            $this_app_list->joinTable('housing_estates',false,false,$sys_tables['applications'].".id_parent = ".$sys_tables['housing_estates'].".id AND ".$sys_tables['applications'].".estate_type = 5");
            $this_app_list->joinTable('cottages',false,false,$sys_tables['applications'].".id_parent = ".$sys_tables['cottages'].".id AND ".$sys_tables['applications'].".estate_type = 6");
            $this_app_list->joinTable('business_centers',false,false,$sys_tables['applications'].".id_parent = ".$sys_tables['business_centers'].".id AND ".$sys_tables['applications'].".estate_type = 7");
            
            //количество элементов на странице
            $count = Request::GetInteger('count', METHOD_GET);            
            if(empty($count)) $count = Cookie::GetInteger('View_count_cabinet');
            if(empty($count)) {
                $count = Config::$values['view_settings']['strings_per_page'];
                Cookie::SetCookie('View_count_cabinet', Convert::ToString($count), 60*60*24*30, '/');
            }
            
            // страница списка
            $page = Request::GetInteger('page', METHOD_GET);
            if(empty($page)) $page = 1;
            //читаем переданные из фильтра параметры
            $deal_type = Request::GetInteger('deal_type',METHOD_POST);
            $estate_type = Request::GetInteger('estate_type',METHOD_POST);

            if(!empty($estate_type)) $where .= " AND " . $sys_tables['applications'].".estate_type IN (".($estate_type == 2?"2,5":$estate_type).")";
            if(!empty($deal_type)) $where .= " AND " . $sys_tables['application_types'].".rent = ".$deal_type;


            $paginator = new Paginator($this_app_list->getPaginatorCondition(), $count, $where." AND ".$where_status[$status]);
            $paginator->link_prefix = '/members/conversions/applications/?status='.$status.'&page=';
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }
            
            //список элементов
            $fields = "DISTINCT ".$sys_tables['applications'].".id,
                      ".$sys_tables['applications'].".`datetime` AS date_normal,
                      ".$sys_tables['applications'].".id_parent,
                      ".$sys_tables['applications'].".id_parent_app,
                      ".$sys_tables['applications'].".viewed,
                      ".$sys_tables['applications'].".email,
                      ".$sys_tables['applications'].".id_realtor_help_type,
                      ".$sys_tables['application_realtor_help_types'].".title AS realtor_help_type_title,
                      ".$sys_tables['owners_user_types'].".title AS user_type_title,
                      ".$sys_tables['work_statuses'].".title AS work_status_title,
                      (".$sys_tables['applications'].".visible_to_all = 3) AS free_for_payed,
                      (".$sys_tables['applications'].".status = 3 AND ".$sys_tables['applications'].".id_parent_app = 0) AS is_exclusive,
                      DATE_FORMAT(".$sys_tables['applications'].".`datetime`,'%e %b, %k:%i') AS date,
                      DATE_FORMAT(".$sys_tables['applications'].".`creation_datetime`,'%e %M<br />%k:%i') AS `normal_date`,
                      IF(".$sys_tables['applications'].".`start_datetime` LIKE '%0000%','',DATE_FORMAT(".$sys_tables['applications'].".`start_datetime`,'%e %M — %k:%i')) AS `start_date`,
                      IF(".$sys_tables['applications'].".`finish_datetime` LIKE '%0000%','',DATE_FORMAT(".$sys_tables['applications'].".`finish_datetime`,'%e %M — %k:%i')) AS `finish_date`,
                      IF(".$sys_tables['application_types'].".estate_type=8,'',
                          CASE
                            WHEN ".$sys_tables['application_types'].".estate_type=1 THEN 'live'
                            WHEN ".$sys_tables['application_types'].".estate_type=2 THEN 'build'
                            WHEN ".$sys_tables['application_types'].".estate_type=3 THEN 'commercial'
                            WHEN ".$sys_tables['application_types'].".estate_type=4 THEN 'country'
                            WHEN ".$sys_tables['application_types'].".estate_type=5 THEN 'zhiloy_kompleks'
                            WHEN ".$sys_tables['application_types'].".estate_type=6 THEN 'cottages'
                            WHEN ".$sys_tables['application_types'].".estate_type=7 THEN 'business_centers'
                            WHEN ".$sys_tables['application_types'].".estate_type=8 THEN 'promotions'
                          END ) AS estate_alias,
                      CONCAT(
                          IF(".$sys_tables['application_types'].".estate_type=8,'','/'),
                          CASE
                            WHEN ".$sys_tables['application_types'].".estate_type=1 THEN 'live'
                            WHEN ".$sys_tables['application_types'].".estate_type=2 THEN 'build'
                            WHEN ".$sys_tables['application_types'].".estate_type=3 THEN 'commercial'
                            WHEN ".$sys_tables['application_types'].".estate_type=4 THEN 'country'
                            WHEN ".$sys_tables['application_types'].".estate_type=5 THEN 'zhiloy_kompleks'
                            WHEN ".$sys_tables['application_types'].".estate_type=6 THEN 'cottedzhnye_poselki'
                            WHEN ".$sys_tables['application_types'].".estate_type=7 THEN 'business_centers'
                            WHEN ".$sys_tables['application_types'].".estate_type=8 THEN '/promotions'
                          END,
                          '/',
                          IF(".$sys_tables['application_types'].".estate_type<5,
                              CONCAT(
                                      CASE 
                                        WHEN ".$sys_tables['application_types'].".rent=1 THEN 'rent'
                                        WHEN ".$sys_tables['application_types'].".rent=2 THEN 'sell'
                                      END,'/'
                                     ),
                          ''),
                          CASE                                                                                                         
                            WHEN ".$sys_tables['application_types'].".estate_type<5 OR ".$sys_tables['application_types'].".estate_type=8 THEN ".$sys_tables['applications'].".id_parent
                            WHEN ".$sys_tables['application_types'].".estate_type=5 THEN ".$sys_tables['housing_estates'].".chpu_title
                            WHEN ".$sys_tables['application_types'].".estate_type=6 THEN ".$sys_tables['cottages'].".chpu_title
                            WHEN ".$sys_tables['application_types'].".estate_type=7 THEN ".$sys_tables['business_centers'].".chpu_title
                          END,
                          '/'
                        ) AS url,
                        CASE
                            WHEN ".$sys_tables['application_types'].".estate_type=1 THEN 'Жилая недвижимость'
                            WHEN ".$sys_tables['application_types'].".estate_type=2 THEN 'Новостройки'
                            WHEN ".$sys_tables['application_types'].".estate_type=3 THEN 'Коммерческая недвижимость'
                            WHEN ".$sys_tables['application_types'].".estate_type=4 THEN 'Загородная недвижимость'
                            WHEN ".$sys_tables['application_types'].".estate_type=5 THEN 'Жилые комплексы'
                            WHEN ".$sys_tables['application_types'].".estate_type=6 THEN 'Коттеджные поселки'
                            WHEN ".$sys_tables['application_types'].".estate_type=7 THEN 'Бизнес-центры'
                            WHEN ".$sys_tables['application_types'].".estate_type=8 THEN 'Акции'
                        END AS estate_type_title,
                      ".$sys_tables['applications'].".name,
                      ".$sys_tables['applications'].".phone,
                      IF(".$sys_tables['application_objects'].".title IS NULL,'',".$sys_tables['application_objects'].".title) AS object_type_title,
                      ".$sys_tables['applications'].".status,
                      IF(".$sys_tables['applications'].".status = 1,'finished',IF(".$sys_tables['applications'].".status = 2,'new','in-work')) AS status_alias,
                      IF(".$sys_tables['applications'].".status = 1,'Завершена',IF(".$sys_tables['applications'].".status = 2,'Новая','В работе')) AS status_title,
                      CASE
                            WHEN ".$sys_tables['application_types'].".rent=1 THEN 'Аренда'
                            WHEN ".$sys_tables['application_types'].".rent=2 THEN 'Покупка'
                            WHEN ".$sys_tables['application_types'].".rent=3 THEN 'Сдам'
                            WHEN ".$sys_tables['application_types'].".rent=4 THEN 'Продам'
                            WHEN ".$sys_tables['application_types'].".rent=0 AND ".$sys_tables['application_types'].".estate_type=8 THEN 'Акция'
                      END AS rent,
                      CASE
                            WHEN ".$sys_tables['application_types'].".rent=1 THEN 'rent'
                            WHEN ".$sys_tables['application_types'].".rent=2 THEN 'buy'
                            WHEN ".$sys_tables['application_types'].".rent=3 THEN 'hire'
                            WHEN ".$sys_tables['application_types'].".rent=4 THEN 'sell'
                            WHEN ".$sys_tables['application_types'].".rent=0 AND ".$sys_tables['application_types'].".estate_type=8 THEN 'promotion'
                      END AS rent_title,
                      ".$sys_tables['applications'].".comment,
                      ".$sys_tables['applications'].".user_comment,
                      IF(".$sys_tables['applications'].".comment<>'' OR ".$sys_tables['applications'].".user_comment<>'',1,0) AS has_comments, 
                      IF(".$sys_tables['applications'].".visible_to_all = 2 && ".$sys_tables['applications'].".id_owner = ".$auth->id.",
                         IF(".$sys_tables['applications'].".estate_type = 2,".(!empty($sale_app_cost)?$sale_app_cost:0).",0),
                            CAST(".$sys_tables['application_types'].".cost AS SIGNED) - 
                                                              FLOOR(
                                                              IF(".$sys_tables['applications'].".id_parent = 0,
                                                              CAST(TIMESTAMPDIFF(DAY,".$sys_tables['applications'].".`datetime`,NOW()) AS SIGNED),
                                                              CAST(TIMESTAMPDIFF(DAY,".$sys_tables['applications'].".`datetime`,DATE_SUB(NOW(),INTERVAL 12 HOUR)) AS SIGNED))*
                                                              ".$sys_tables['application_types'].".day_discount*0.01*".$sys_tables['application_types'].".cost + 
                                                              CAST(".$sys_tables['applications'].".in_work_amount*
                                                              ".$sys_tables['application_types'].".client_discount*0.01*".$sys_tables['application_types'].".cost AS SIGNED)
                                                              )
                         ) AS cost,
                      ".$sys_tables['application_types'].".exclusive_cost,
                      ".$sys_tables['housing_estates'].".build_complete,
                      IF(".$sys_tables['applications'].".id_owner = ".$auth->id.",1,0) AS user_object,
                      ".$sys_tables['applications'].".estate_type";
            $list = $this_app_list->getList($fields,$sys_tables['applications'].".`datetime` DESC",$paginator->getLimitString($page),true,$auth->id,true);
            $app_ids = array_keys($list);
            //тем новым, которые просмотрены, устанавливаем viewed = 1
            if($status == 'all' || $status == 'new'){
                $viewed_ids = [];
                foreach($list as $key=>$item)
                    $viewed_ids[] = $item['id'];
                $viewed_ids = implode(',',$viewed_ids);
                if(!empty($viewed_ids)) $db->query("UPDATE ".$sys_tables['applications']." SET viewed = 1 WHERE id IN (".$viewed_ids.")");
                Notifications::setRead('applications', $viewed_ids);
            }
            
            //флаг бесплатных заявок для платных клиентов
            Response::SetBoolean('free_apps_for_me',(!empty($auth->id_tarif) || (!empty($auth->id_agency) && !empty($auth->agency_id_tarif))) );
            Response::SetArray('list',$list);
            Response::SetInteger('list_length',$paginator->items_count);
            Response::SetBoolean('cabinet', true);
            $module_template = "list.html";
            $ajax_result['ok'] = true;
            Response::SetBoolean('can_buy_realtor_apps', (!empty($agency_info) && (!empty($agency_info['email_application_realtor_help']) )));
        }
        break;
    //////////////////////////////////////////////////////////
    //редактирование комментария заявки
    //////////////////////////////////////////////////////////
    case $action == 'comment' && !empty($ajax_mode):
        $id_app = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        if(empty($id_app)) return false;
        //действие с комментарием(сохранить/удалить)
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        $comment = Request::GetString('comment',METHOD_POST);
        switch($action){
            case 'save':
                $ajax_result['ok'] = $db->query("UPDATE ".$sys_tables['applications']." SET comment = ? WHERE id = ?",$comment,$id_app);
                break;
            case 'delete':
                $ajax_result['ok'] = $db->query("UPDATE ".$sys_tables['applications']." SET comment = '' WHERE id = ?",$id_app);
                break;
        }
        break;
    //////////////////////////////////////////////////////////
    //заявка в работу
    //////////////////////////////////////////////////////////
    case ($action == 'in_work' || $action == 'in_work_exclusive') && !empty($ajax_mode):
    
        $id_app = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        if(empty($id_app)) return false;
        
        $this_app = new Application($id_app,false,null);
        $ajax_result = $this_app->Buy($auth->id,$action);
        
        break;
    //////////////////////////////////////////////////////////
    //закрытие заявки
    //////////////////////////////////////////////////////////
    case $action == 'finish' && !empty($ajax_mode):
        $id_app = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        if(empty($id_app)) return false;
        
        $this_app = new Application($id_app,null,null,true);
        
        //устанавливаем статус "Завершена"
        $this_app->toFinished();
        
        //по возможности оповещаем создателя заявки о закрытии - пока без метода
        $initiator_email = $this_app->getAttr('email');
        if(!empty($initiator_email)){
            //читаем информацию по заявке и пользователю
            $app_info = array('email'=>$this_app->getAttr('email'),
                              'user_title'=>$this_app->getOwnersAttr('name'),
                              'id_parent'=>$this_app->getAttr('id_parent'),
                              'object_url'=>$this_app->getAttr('object_url'),
                              'estate_type'=>$this_app->getAttr('estate_type'));
            $notify_info['user_email'] = $app_info['email'];
            $notify_info['user_title'] = $app_info['name'];
            $id_object = $app_info['id_parent'];
            
            switch($app_info['estate_type']){
                    case 1:
                        $estateItem = new EstateItemLive($id_object);
                        break;
                    case 2:
                        $estateItem = new EstateItemBuild($id_object);
                        break;
                    case 3:
                        $estateItem = new EstateItemCommercial($id_object);
                        break;
                    case 4:
                        $estateItem = new EstateItemCountry($id_object);
                        break;
                    case 5:
                        $estateItem = new HousingEstates($id_object);
                        break;
                    case 6:
                        $estateItem = new Cottages($id_object);
                        break;        
                    case 7:
                        $estateItem = new BusinessCenters($id_object);
                        break;
                    default:
                        $estateItem = null;
                        $ajax_result['ok'] = false;
                        break;
                }
                
            $campaign_title = $estateItem->getTitles($id_object);
            
            $mailer = new EMailer('mail');
            $data['campaign_title'] = $campaign_title['header'];
            $data['inserted_id'] = $id_app;
            $data['host'] = "bsn.ru";
            $data['finishing'] = true;
            $data['user_title'] = (!empty($notify_info['user_title'])?$notify_info['user_title']:"");
            $data['object_url'] = $_SERVER['HTTP_HOST'].$app_info['object_url'];
            $data['object_id'] = $app_info['id_parent'];
            Response::SetArray('data',$data);
            $eml_tpl = new Template('/modules/applications/templates/mail.user.html');
            $html = $eml_tpl->Processing();
            // перевод письма в кодировку мейлера
            $mail_text = iconv('UTF-8', $mailer->CharSet, $html);
            // параметры письма
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Ваша заявка на bsn.ru - ID ".$id_app." закрыта ".date('Y-m-d H:i:s'));
            $mailer->Body = $mail_text;
            $mailer->AltBody = strip_tags($mail_text);
            $mailer->IsHTML(true);
            //если email корректный, отправляем письмо
            if(!empty($notify_info['user_email']) && Validate::isEmail($notify_info['user_email'])){
                $mailer->AddAddress($notify_info['user_email']);//отправка письма пользователю
                $mailer->From = 'no-reply@bsn.ru';
                $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
                // попытка отправить
                $mailer->Send();
            }
        }
        $ajax_result['ok'] = true;
        break;
    //////////////////////////////////////////////////////////
    //список для страницы публичных заявок
    //////////////////////////////////////////////////////////
    case $action == 'public_list':
        //читаем переданные из фильтра параметры
        $deal_type = Request::GetInteger('deal_type',METHOD_POST);
        $estate_type = Request::GetInteger('estate_type',METHOD_POST);
        
        //проверяем, авторизован ли пользователь и если да, может ли он покупать заявки
        if(!empty($auth->id)) $user_info = $db->fetch("SELECT id,id_tarif,id_agency FROM ".$sys_tables['users']." WHERE id = ".$auth->id);
        Response::SetBoolean('can_buy_apps',(!empty($user_info) && (!empty($user_info['id_tarif']) || !empty($user_info['id_agency']))));
        
        Response::SetBoolean('ajax_search',true);
        
        //по типу сделки и типу недвижимости определяем тип заявки:
        $where = [];
        if(!empty($estate_type)) $where[] = $sys_tables['application_types'].".estate_type IN (".($estate_type == 2?"2,5":$estate_type).")";
        if(!empty($deal_type)) $where[] = $sys_tables['application_types'].".rent = ".$deal_type;
        if(!empty($where)) $where = " WHERE ".implode(' AND ',$where);
        else $where = "";
        
        $app_types = $db->fetchall("SELECT id FROM ".$sys_tables['application_types'].$where,'id');
        $app_types = implode(',',array_keys($app_types));
        
        
        //если мы авторизованы, и если вдруг это агентство, проверяем не считаются ли цены отдельно, по правилам sale
        $sale_app_cost = AppsFunctions::getSaleAppCost($auth->id);
        
        //условие общего пула
        $clauses = [];
        $clauses['status'] = array('tablename'=>'applications','value'=>2);
        
        //для авторизованных клиентов с платными тарифами добавляем соотвтетсвующие заявки
        if($auth->id > 0) $clauses['visible_to_all'] = array('tablename'=>'applications','set'=>array(1,3));
        else $clauses['visible_to_all'] = array('tablename'=>'applications','value'=>1);
        
        Response::SetBoolean('free_apps_for_me', ($auth->id_tarif > 0 || $auth->id_agency > 0 && $auth->agency_id_tarif > 0));
        
        $clauses['application_type'] = array('tablename'=>'applications','set'=>$app_types);
        
        $this_app_list = new ApplicationList();
        
        $where = $this_app_list->makeWhereClause( $clauses );
        
        //количество элементов на странице
        $count = Request::GetInteger('count', METHOD_POST);
        
        //убираем в видимые только платным заявки, у которых цена стала <= 0 из-за скидок
        AppsFunctions::removeDepreciated();
        
        
        // страница списка
        $page = Request::GetInteger('page', METHOD_POST);
        if(empty($page)) $page = 1;
        $paginator = new Paginator($this_app_list->getPaginatorCondition(), $count, $where);
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        
        $list = $this_app_list->getPublicList($sys_tables['applications'].".`datetime` DESC", $paginator->getLimitString($page), true, $auth->id );
        //список элементов
        if($paginator->pages_count<=1){
            Response::SetInteger('items_count',count($list));
        }
        else{
            Response::SetInteger('items_count',$paginator->items_count);
            $limits = explode(',',$paginator->getLimitString($page));
        }
        
        
        Response::SetInteger("items_count",$paginator->items_count);
        
        $ajax_result['page'] = 'applications';     
        $ajax_result['results'] = 'Найдено: ' . $paginator->items_count . ( !empty($paginator->items_count) && !empty( $limits )? ' <br />Показаны: ' . ( $limits[0] + 1 ) . '-' . ( $limits[0] + count( $list ) ) : '' );
        $ajax_result['ok'] = true;
        Response::SetArray('list',$list);
        $module_template = "list.html";
        break;
    //////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////
    case empty($action):
        $GLOBALS['js_set'][] = '/modules/applications/public.apps.js';
        Response::SetBoolean( 'payed_format', true );
        Response::SetString( 'h1', !empty( $this_page->page_seo_h1 ) ? $this_page->page_seo_h1 : "Заявки" );
        $module_template = "public.apps.html";
        break;
    default:
        //$this_page->http_code=404; 
        Host::Redirect('/members/conversions/applications/');
        break;
}
?>