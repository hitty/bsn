<?php
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
$strings_per_page = 10;
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

$GLOBALS['css_set'][] = '/css/style-cabinet.css';
//не показывать верхний баннер
Response::SetBoolean('not_show_top_banner',true);
Response::SetString('page','finances');
// обработка общих action-ов
switch(true){
   case empty($action):
        $GLOBALS['js_set'][] = '/js/jquery.ajax.filter.js';
        $GLOBALS['js_set'][] = '/js/datetimepicker/jquery.datetimepicker.js';
        $GLOBALS['css_set'][] = '/js/datetimepicker/jquery.datetimepicker.css';
        //формирование фильтра
        Response::SetBoolean('filter', true);
        //формирование фильтра
        Response::SetBoolean('filter', true);
        //период времени
        Response::SetBoolean('filter_time_periods', true);
        //список агентств
        if(empty($auth->id_agency) && ($auth->id_group == 101 || $auth->id_group == 10 || $auth->id_group == 3)){
            // формирование списка для фильтра
            $agencies = $db->fetchall("SELECT 
                                              ".$sys_tables['users'].".id,
                                              ".$sys_tables['agencies'].".title
                                              FROM ".$sys_tables['users']." 
                                              RIGHT JOIN ".$sys_tables['users_finances']." ON ".$sys_tables['users_finances'].".id_user = ".$sys_tables['users'].".id
                                              LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                              WHERE title IS NOT NULL
                                              GROUP BY title
                                              ORDER BY ".$sys_tables['users'].".id_agency, title");
            array_unshift($agencies, array('id'=>'1', 'title'=>'Частник'));
            Response::SetArray('agencies',$agencies);
        }
        //Транзакции
        $transactions = $db->fetchall("SELECT obj_type as id, title FROM ".$sys_tables['users_fnances_transactions']." ORDER BY title");
        Response::SetArray('transactions', $transactions);
        //список сотрудников для агентства
        if(!empty($auth->id_agency) && $auth->agency_admin == 1){
            $filter_agents = $db->fetchall("SELECT id, CONCAT(name,' ',lastname) as title FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
            Response::SetArray('filter_agents', $filter_agents);
        }
        
        if(empty($ajax_mode)) {
            $module_template = "main.html";
        }
        else {
            $module_template = "list.html";
            // кол-во элементов в списке
            $count = Request::GetInteger('count', METHOD_GET);            
            if(empty($count)) $count = Cookie::GetInteger('View_count_cabinet');
            if(empty($count)) {
                $count = Config::$values['view_settings']['strings_per_page'];
                Cookie::SetCookie('View_count_cabinet', Convert::ToString($count), 60*60*24*30, '/');
            }  
            
            // страница списка
            $page = Request::GetInteger('page', METHOD_GET);
            if(empty($page)) $page = 1;

            // формирование фильтров
            $conditions = [];
            $filters = [];
            if(!empty($auth->id_agency)) { // для агентства
                if($auth->agency_admin == 1){
                    $user = Request::GetInteger('user',METHOD_GET);
                    if(!empty($user)) $conditions['user'] = $sys_tables['users_finances'].".`id_user` = ".$user; //поиск по определенном пользователю
                    else {
                        $users_list = $db->fetchall("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
                        $ids = [];
                        foreach($users_list as $k=>$item) $ids[] = $item['id'];
                        $conditions['user'] = " (".$sys_tables['users_finances'].".`id_user` IN (".implode(", ", $ids).") OR ".$sys_tables['users_finances'].".`id_initiator` IN (".implode(", ", $ids).") )";
                    }
                }
                else $conditions['user'] = $sys_tables['users_finances'].".`id_user` = ".$auth->id; //агентам только свои транзакции
            }
            elseif($auth->id_group == 101-9 || $auth->id_group == 10 || $auth->id_group == 3) $filters['user'] = Request::GetInteger('filter_agency',METHOD_GET);
            else $filters['user'] = $auth->id;
            $filters['date_start'] = Request::GetString('filter_date_start',METHOD_GET);
            $filters['date_end'] = Request::GetString('filter_date_end',METHOD_GET);
            $filters['period'] = Request::GetString('filter_period',METHOD_GET);
            $filters['transaction'] = Request::GetString('filter_transaction',METHOD_GET);

            if(!empty($filters['user'])) {
                if($filters['user'] == 1) {    //для частников
                    $ids = [];
                    foreach($agencies as $k=>$item) $ids[] = $item['id'];
                    $conditions['user'] = $sys_tables['users_finances'].".`id_user` NOT IN (".implode(',',$ids).")";
                } else $conditions['user'] = " (".$sys_tables['users_finances'].".`id_user` = ".$db->real_escape_string($filters['user'])." OR ".$sys_tables['users_finances'].".`id_initiator` = ".$db->real_escape_string($filters['user']).")";
                
            }
            //фильтр по дате
            if(!empty($filters['date_start'])) {
                $filters['date_start'] = preg_replace('/([0-9]{2})\.([0-9]{2})\.([0-9]{2})/msiU', "20$3-$2-$1", $filters['date_start']);
                $conditions['date_start'] = "DATE(".$sys_tables['users_finances'].".`datetime`) >= '".date("Y-m-d", strtotime($filters['date_start']))."'";
            }
            if(!empty($filters['date_end'])) {
                $filters['date_end'] = preg_replace('/([0-9]{2})\.([0-9]{2})\.([0-9]{2})/msiU', "20$3-$2-$1", $filters['date_end']);
                $conditions['date_end'] = "DATE(".$sys_tables['users_finances'].".`datetime`) <= '".date("Y-m-d", strtotime($filters['date_end']))."'";
            }
            //фильт по транзакции
            if(!empty($filters['transaction'])) $conditions['transaction'] = $sys_tables['users_finances'].".`obj_type` = '".$filters['transaction']."'";
            
            $condition = implode(" AND ",$conditions);
            $paginator = new Paginator($sys_tables['users_finances'], $count, $condition);
            $paginator->link_prefix = '/members/finances/?ajax=true&count=20&page=';
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }                

            $list = $db->fetchall("SELECT ".$sys_tables['users_finances'].".*,
                                          ".$sys_tables['context_advertisements'].".id_campaign AS cc_id,
                                          IF(YEAR(".$sys_tables['users_finances'].".datetime) < Year(CURDATE()),DATE_FORMAT(".$sys_tables['users_finances'].".datetime,'%e %M %Y'),DATE_FORMAT(".$sys_tables['users_finances'].".datetime,'%e %M, %k:%i')) as normal_date, 
                                          ".$sys_tables['users_fnances_transactions'].".title as service_title,
                                          IF(".$sys_tables['tarifs'].".title!='',".$sys_tables['tarifs'].".title, 
                                            IF(".$sys_tables['users_finances'].".obj_type = 'balance','', 
                                              IF(".$sys_tables['users_finances'].".obj_type = 'call','',
                                                IF(".$sys_tables['users_finances'].".obj_type = 'application',CONCAT('ID ',id_parent),CONCAT_WS('/', ".$sys_tables['users_finances'].".obj_type, id_parent))
                                                )
                                              )
                                          ) as object_title,
                                          IFNULL(".$sys_tables['promocodes'].".title, '') as promocode_title,
                                          IFNULL(".$sys_tables['agencies'].".title, 'частное') as title,
                                          IF(app.object_url IS NOT NULL, app.object_url,'') AS object_url,
                                          IF(app.object_url IS NOT NULL, app.app_object_id,'') AS app_object_id,
                                          admin_agency.title as agency_title,
                                          CONCAT(initiator.lastname, ' ',initiator.name) as initiator_name,
                                          IF(".$sys_tables['users'].".name!='',CONCAT(".$sys_tables['users'].".lastname, ' ',".$sys_tables['users'].".name),'') as user_name
                                   FROM ".$sys_tables['users_finances']."
                                   LEFT JOIN  ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users_finances'].".id_parent AND obj_type = 'tarif'
                                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users_finances'].".id_user
                                   LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['users_finances'].".id_user
                                   LEFT JOIN ".$sys_tables['users']." initiator ON initiator.id = ".$sys_tables['users_finances'].".id_initiator 
                                   LEFT JOIN ".$sys_tables['promocodes']." ON ".$sys_tables['promocodes'].".id = ".$sys_tables['users_finances'].".id_parent
                                   LEFT JOIN ".$sys_tables['agencies']." admin_agency ON admin_agency.id = ".$sys_tables['users'].".id_agency
                                   LEFT JOIN ".$sys_tables['users_fnances_transactions']." ON ".$sys_tables['users_fnances_transactions'].".obj_type = ".$sys_tables['users_finances'].".obj_type
                                   LEFT JOIN ".$sys_tables['context_advertisements']." ON ".$sys_tables['context_advertisements'].".id = ".$sys_tables['users_finances'].".id_parent
                                                                                       AND ".$sys_tables['users_finances'].".obj_type = 'context_banner'
                                   LEFT JOIN (
                                        SELECT ".$sys_tables['applications'].".id,
                                               CONCAT(
                                                  '/',  
                                                  CASE
                                                    WHEN ".$sys_tables['application_types'].".estate_type=1 THEN 'live'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=2 THEN 'build'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=3 THEN 'commercial'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=4 THEN 'country'
                                                  END,
                                                  '/',
                                                  CASE 
                                                    WHEN ".$sys_tables['application_types'].".rent=1 THEN 'rent'
                                                    WHEN ".$sys_tables['application_types'].".rent=2 THEN 'sell'
                                                  END,
                                                  '/',
                                                  ".$sys_tables['applications'].".id_parent,'/'
                                               ) AS object_url,
                                               ".$sys_tables['applications'].".id_parent AS app_object_id
                                        FROM ".$sys_tables['applications']."
                                        LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['application_types'].".id = ".$sys_tables['applications'].".application_type
                                        LEFT JOIN ".$sys_tables['application_objects']." ON ".$sys_tables['application_objects'].".id = ".$sys_tables['applications'].".object_type
                                   ) app ON app.id = ".$sys_tables['users_finances'].".id_parent
                                   ".(!empty($conditions)?" WHERE ".implode(' AND ',$conditions):"")."
                                   GROUP BY ".$sys_tables['users_finances'].".id
                                   ORDER BY ".$sys_tables['users_finances'].".id DESC
                                   LIMIT ".$count*($page-1).", ".$count."
            ");                                            
            $total = $db->fetch("SELECT COUNT(*) as 'count', SUM(income) as income, SUM(expenditure) as expenditure FROM ".$sys_tables['users_finances'].(!empty($conditions)?" WHERE ".implode(' AND ',$conditions):""));
            Response::SetArray('total',$total);
            Response::SetArray('list',$list);          
            $ajax_result['ok'] = true;
        }
        break;
    default:
        $this_page->http_code=404;
        break;
}
?>