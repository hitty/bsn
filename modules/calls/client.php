<?php
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
$strings_per_page = 10;

//не показывать верхний баннер
Response::SetBoolean('not_show_top_banner',true);
Response::SetString('page','calls');
//редирект с главной на звонки
if(!empty($this_page->module_parameters['redirect']))  Host::Redirect('/members/conversions/calls/');
// определяем экшн
$action = empty($this_page->page_parameters[0]) ? "" : (empty($ajax_action) ? $this_page->page_parameters[0]: $ajax_action);
switch($action){
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Звонки
   ////////////////////////////////////////////////////////////////////////////////////////////////
    case 'calls':
    
    case 'inwork':
        //if(!empty($ajax_mode)){
            $id = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1]: false;
            if(!empty($id)) {
                //определение инфы по телефону
                $call = $db->fetch("SELECT * FROM ".$sys_tables['calls']." WHERE id = ?", $id);
                //определение имени и телефона заявщика
                $user = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE id = ?", $call['id_user']);
                $cost = $call['cost']/2;
                if($user['balance'] < $cost) {
                    $ajax_result['text'] = 'У вас недостаточно средств. Пополните баланс.';
                    $ajax_result['ok'] = true;
                } else {
                    $ajax_result['phone'] = $call['num_from'];
                    //список всех id с таким же телефоном
                    $ids = $db->fetchall("SELECT id FROM ".$sys_tables['calls']." WHERE num_from = ? AND status!=3", false, $call['num_from']);
                    //снятие финансов
                    if(!empty($user['id']) && !empty($ids)) {
                        $db->querys("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?", $cost, $user['id']);
                        $db->querys("UPDATE ".$sys_tables['calls']." SET status = 3 WHERE id_user = ? AND num_from = ?",$user['id'],$call['num_from']);
                        $ajax_result['ok'] = true;
                        foreach($ids as $k=>$id) $ajax_result['ids'][] = $id['id'];
                    }
                }
            }
        //}
        break;
    //скачивание записи звонка
    case 'download':
        $module_template = "download.html";
        $call_id = $this_page->page_parameters[1];
        $file_link = $db->fetch("SELECT file_link FROM ".$sys_tables['calls']." WHERE id=?",$call_id);;
        $file_link = $file_link['file_link'];
        $file_name = explode('/',$file_link);
        $file_name = $file_name[2];
        header('Content-type: application/mp3');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        readfile('audio/'.substr($file_name,0,2)."/".$file_name);
    break;
    //редактирование тегов звонка
    case 'edit_tags':
        $id = Request::GetInteger('id', METHOD_POST);
        $tag_id = Request::GetInteger('tag_id', METHOD_POST);
        $active = Request::GetString('active', METHOD_POST);
        //получение списка тегов                                     
        $list = $db->fetch("
                            SELECT ".$sys_tables['calls'].".tags 
                            FROM ".$sys_tables['calls']." 
                            LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$auth->id."
                            LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".bsn_id_user = ".$sys_tables['users'].".id AND ".$sys_tables['managers'].".bsn_manager = 1
                            LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                            WHERE ".$sys_tables['calls'].".id = ? AND (".$sys_tables['calls'].".id_user = ? OR ".$sys_tables['managers'].".bsn_id_user = ?)", 
                            $id, $auth->id, $auth->id
        );
        if(!empty($list)){
            if(!empty($list['tags'])) $tags = explode(',', $list['tags']);
            else $tags = [];
            //добавление тега
            if($active == 'true') $tags[] = $tag_id;
            else {
                if(($key = array_search($tag_id,$tags)) !== false) unset($tags[$key]);
            }
            $db->querys("UPDATE ".$sys_tables['calls']." SET tags=? WHERE id=?", $tag_id, $id);
            $ajax_result['ok'] = true;
        }
    break;
    //таблица со звонками
    default:
        Response::SetBoolean('not_show_top_banner',true);
        //для setCookie
        $GLOBALS['js_set'][] = '/js/main.js';
        $GLOBALS['js_set'][] = '/modules/calls/calls.js';
        $GLOBALS['css_set'][] = '/modules/calls/calls.css';
        $GLOBALS['js_set'][] = '/js/jquery.ajax.filter.js';
        $GLOBALS['js_set'][] = '/js/datetimepicker/jquery.datetimepicker.js';
        $GLOBALS['css_set'][] = '/js/datetimepicker/jquery.datetimepicker.css';

        if(empty($ajax_mode)) {
            $module_template = "main.html";
            //если это обычный пользователь, звонков у него нет, отправляем его в Заявки
            if(empty($auth->id_agency) && $auth->id_group < 3 && empty($auth->id_tarif) && $auth->id!=39106 && $auth->activity == 1){
                Response::SetBoolean('common_user',true);
                Host::Redirect("/members/conversions/applications/applications/");
            }else
            //юристов перенаправляем в консультации
            if( $auth->user_activity == 2 ){
                Response::SetBoolean('common_user',true);
                Host::Redirect("/members/conversions/consults/");
            }
            //формирование фильтра
            Response::SetBoolean('filter', true);
            //период времени
            Response::SetBoolean('filter_time_periods', true);
            //список агентств
            if($auth->id_group == 101 || $auth->id_group == 10 || $auth->id_group == 3){
                // формирование списка для фильтра
                $agencies = $db->fetchall("SELECT ".$sys_tables['users'].".id,
                                                  ".$sys_tables['agencies'].".title 
                                          FROM ".$sys_tables['agencies']." 
                                          RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                          RIGHT JOIN ".$sys_tables['calls']." ON ".$sys_tables['calls'].".id_user = ".$sys_tables['users'].".id
                                          GROUP BY id_user
                                          ORDER BY title");
                Response::SetArray('agencies',$agencies);
            }
            
            ///данные для левой панели
            
            if($auth->id_group == 101 || $auth->id_group == 10 || $auth->id_group == 3 ) {
                Response::SetBoolean('show_agency', true);
                $user_id = "";
            }
            elseif(!empty($auth->id_agency)) $user_id = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id_agency = ? AND agency_admin = 1",$auth->id_agency)['id'];
            else $user_id = $auth->id;
            $calls_total = $db->fetch("SELECT COUNT(*) AS cnt FROM ".$sys_tables['calls'].(empty($user_id) ? "" : " WHERE id_user = ".$user_id))['cnt'];
            Response::SetInteger('calls_total', $calls_total);
            if(!empty($auth->id_agency) && $auth->agency_admin == 1) { 
                    $users_list = $db->fetchall("SELECT * FROM ".$sys_tables['users']." WHERE id_agency = ?", false, $auth->id_agency);
                    $ids = [];
                    foreach($users_list as $k=>$item) $ids[] = $item['id'];
                    $users_id = implode(", ", $ids);
                
            } else $users_id = $auth->id;
            if($auth->id_group>=3 || !empty($auth->id_agency)) $where = "(visible_to_all = 1 && status = 2) XOR (id_user IN (".$users_id.") AND status < 4)";
            else $where = "id_initiator IN (".$users_id.")";
            $where .= " AND id_parent_app = 0";
            $apps_total = $db->fetch("SELECT COUNT(*) AS amount
                                      FROM ".$sys_tables['applications']."
                                      WHERE ".$where)['amount'];
            Response::SetInteger('apps_total',$apps_total);
            if($auth->user_activity == 2){
                $consults_total = $db->fetch("SELECT COUNT(*) AS amount
                                              FROM ".$sys_tables['consults']."
                                              WHERE visible_to_all = 1 OR (visible_to_all = 2 AND id_respondent_user = ".$auth->id.") AND status = 1")['amount'];
            }else{
                $consults_total = $db->fetch("SELECT COUNT(*) AS amount
                                              FROM ".$sys_tables['consults']."
                                              WHERE id_initiating_user = ? AND ".$sys_tables['consults'].".status IN (1,2)",$auth->id)['amount'];
            }
            Response::SetInteger('consults_total',$consults_total);
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

            // формирование списка для фильтра
            $agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']." ORDER BY title");
            Response::SetArray('agencies',$agencies);
            //строим фильтр
            $filters = [];
            if($auth->id_group == 101 || $auth->id_group == 10 || $auth->id_group == 3 ) {
                Response::SetBoolean('show_agency', true);
                $filters['user'] = Request::GetInteger('filter_agency',METHOD_GET);
            }
            elseif(!empty($auth->id_agency)) $filters['user'] = $auth->id;
            else $filters['user'] = $auth->id;
            $filters['date_start'] = Request::GetString('filter_date_start',METHOD_GET);
            $filters['date_end'] = Request::GetString('filter_date_end',METHOD_GET);
            $filters['period'] = Request::GetString('filter_period',METHOD_GET);

            // формирование фильтров
            $conditions = [];
            if(!empty($filters['user'])) {
                $conditions['user'] = $sys_tables['calls'].".`id_user` = ".$db->real_escape_string($filters['user']);
            }
            //фильтр по дате
            if(!empty($filters['date_start'])) {
                $filters['date_start'] = preg_replace('/([0-9]{2})\.([0-9]{2})\.([0-9]{2})/msiU', "20$3-$2-$1", $filters['date_start']);
                $conditions['date_start'] = "DATE(".$sys_tables['calls'].".`call_date`) >= '".date("Y-m-d", strtotime($filters['date_start']))."'";
            }
            if(!empty($filters['date_end'])) {
                $filters['date_end'] = preg_replace('/([0-9]{2})\.([0-9]{2})\.([0-9]{2})/msiU', "20$3-$2-$1", $filters['date_end']);
                $conditions['date_end'] = "DATE(".$sys_tables['calls'].".`call_date`) <= '".date("Y-m-d", strtotime($filters['date_end']))."'";
            }
            $condition = implode(" AND ",$conditions);
            $paginator = new Paginator($sys_tables['calls'], $count, $condition);
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }                
            $sortby = Request::GetString( 'sortby', METHOD_GET );
            switch($sortby){
                case 1: $orderby = " call_date ASC "; break;
                default: $orderby = " call_date DESC "; break;
            }
            //формируем запрос
            $sql = "SELECT ".$sys_tables['calls'].".*,
                                                    CONCAT(
                                                           LPAD(FLOOR(".$sys_tables['calls'].".duration/3600),2,'0'),':',
                                                           LPAD(FLOOR(".$sys_tables['calls'].".duration / 60),2,'0'),':',
                                                           LPAD(FLOOR(".$sys_tables['calls'].".duration % 60),2,'0')
                                                          ) as 'call_duration',
                                                   IF(YEAR(".$sys_tables['calls'].".call_date) < Year(CURDATE()),
                                                   DATE_FORMAT(".$sys_tables['calls'].".call_date,'%e %b %Y'),
                                                   DATE_FORMAT(".$sys_tables['calls'].".call_date,'%e %b, %k:%i')) as normal_date,
                                                   LEFT(num_from,11) as hidden_num_from,
                                                   ".$sys_tables['agencies'].".title as agency_title,
                                                   ".$sys_tables['agencies'].".show_call_link
                                                   FROM ".$sys_tables['calls']."
                                                   LEFT JOIN  ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['calls'].".id_user
                                                   LEFT JOIN  ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id";
            if(!empty($condition)) $sql .= " WHERE ".$condition;
            $sql .= " ORDER BY " . $orderby . "
                      LIMIT ".$count*($page-1).", ".$count;
            $list = $db->fetchall($sql);
            //читаем список возможных тегов для звонков
            $list_tags = $db->fetchall("SELECT * FROM ".$sys_tables['calls_tags']);
            //делаем Response для того, чтобы он появился в контекстном меню
            Response::SetArray('list_tags',$list_tags);
            //подготовка данных для отображения
            foreach ($list as $key=>$item){
                $duration_sec = explode(":",$list[$key]['call_duration']);
                $duration_sec = Convert::ToInt($duration_sec[0])*3600 + Convert::ToInt($duration_sec[1])*60 + Convert::ToInt($duration_sec[2]);
                //если продолжительность звонка меньше 20 секунд, номер скрываем для агентств
                if ($item['status'] != 3 && $duration_sec<20 && !empty($item['num_from']) && !empty($auth->id_agency)) {
                    $list[$key]['num_from'] = $item['hidden_num_from'].'-XX-XX';
                    $list[$key]['show_phone'] = true;
                } else $list[$key]['num_from'] = $list[$key]['num_from'];
                
                if(!empty($item['tags'])) {
                    $list[$key]['tags'] = explode(',',$item['tags']);
                }
            }
            Response::SetArray('list', $list);
            //флаг непросмотренных звонков
            if(!empty($auth->id)) $db->querys("UPDATE ".$sys_tables['calls']." SET viewed = 1 WHERE id = ?",$auth->id);
            $ajax_result['ok'] = true;
            //Список тегов
            $tags = $db->fetchall('SELECT * FROM '.$sys_tables['calls_tags']." ORDER BY title");
            Response::SetArray('tags', $tags);
        }
        //отображать меню со звонками
        Response::SetBoolean('conversions', true);

    break;
}
?>