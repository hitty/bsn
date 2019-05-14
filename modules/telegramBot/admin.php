<?php

// добавление title
$this_page->manageMetadata(array('title'=>'Телеграмный бот'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['campaign'] = $db->real_escape_string(Request::GetInteger('f_campaign',METHOD_GET));
$filters['manager'] = $db->real_escape_string(Request::GetInteger('f_manager',METHOD_GET));
$filters['credit_clicks'] = Request::GetInteger('f_credit_clicks',METHOD_GET);
$filters['status'] = Request::GetString('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['campaign'])) $get_parameters['f_campaign'] = $filters['campaign']; else $filters['campaign'] = false;
if(!empty($filters['manager'])) $get_parameters['f_manager'] = $filters['manager']; else $filters['manager'] = false;
if(!empty($filters['credit_clicks'])) $get_parameters['f_credit_clicks'] = $filters['credit_clicks']; else $filters['credit_clicks'] = false;
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; else $filters['status'] = 'active';

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
// обработка action-ов
switch($action){
    case 'bot_stats_chart':
        $partner = !empty($auth->id_group)&&$auth->id_group==11;
        $fields = array(
                array('string','Дата')
                ,array('number','Пользователи')
                ,array('number','Поисковые запросы'));
        
        if  (!$ajax_mode){
            // переопределяем экшн 
            $module_template = 'admin.bot_stats.chart.html';
            //$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
            $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
            $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
            $GLOBALS['js_set'][] = '/js/main.js';
            $GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
            $GLOBALS['js_set'][] = '/js/google.chart.api.js';
            $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js'; 
            
            Response::SetArray('data_titles',$fields);
            //получение группы пользователя "Партнер"            
            
        }
        $get_parameters = Request::GetParameters(METHOD_GET);
        
        // если была отправка формы - выводим данные 
        if(!empty($get_parameters['submit']) || $ajax_mode){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана

            //передача данных в шаблон
            $date_start = $get_parameters['date_start'];
            $date_end = $get_parameters['date_end'];
            $info['date_start'] = $date_start;
            $info['date_end'] = $date_end;
            
            //определение выбранного временного интервала
            $datetime1 = new DateTime($date_start);
            $datetime2 = new DateTime($date_end);
            $date_now = new DateTime();
            $today_included = ($date_now->diff($datetime2)->d == 0 || $date_now < $datetime2);
            
            $interval = $datetime1->diff($datetime2);
            $info['interval'] = $interval->format('%a')+1;
            
            
            $condition = (!empty($date_start) ? "`datetime`>='".date('Y-m-d 00:00:00',strtotime($date_start))."'" : "").
                         (!empty($date_end) ? (!empty($date_start) ? " AND " : "")."`datetime`<='".date('Y-m-d 99:99:99',strtotime($date_end))."'" : "");
            
            $stats = $db->fetchall("SELECT DATE_FORMAT(`datetime`,'%d.%m.%Y') as `date`,
                                           COUNT(DISTINCT ".$sys_tables['telegram_dialogs'].".id_chat) AS users_amount,
                                           SUM(IF(".$sys_tables['telegram_dialogs'].".message = '/search' || ".$sys_tables['telegram_dialogs'].".message = 'Поиск',1,0)) AS searches_amount
                                    FROM ".$sys_tables['telegram_dialogs']."
                                    LEFT JOIN ".$sys_tables['telegram_contacts']." ON ".$sys_tables['telegram_dialogs'].".id_chat = ".$sys_tables['telegram_contacts'].".id_chat
                                    WHERE ".$sys_tables['telegram_contacts'].".id IS NOT NULL ".(!empty($condition) ? "AND ".$condition : "")."
                                    GROUP BY DATE_FORMAT(`datetime`,'%d.%m.%Y')
                                    ORDER BY `datetime` ASC");
                
            Response::SetArray('stats',$stats);
                
                //Подсчет суммарной статистики по ТГБ за период с прогнозом
                //вычисление лимитов - среднее кол-во в день
                $ids_list  = $db->fetchall("
                    SELECT 
                            a.id_parent as id
                    FROM ".$sys_tables['tgb_banners']."
                    LEFT JOIN (
                        SELECT 
                                id_parent
                        FROM 
                                ".$sys_tables['tgb_stats_full_clicks']."
                        WHERE  
                                `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')     
                    ) a ON a.id_parent = ".$sys_tables['tgb_banners'].".id
                    WHERE 
                        ".$sys_tables['tgb_banners'].".enabled = 1 AND 
                        ".$sys_tables['tgb_banners'].".published = 1 AND
                        ".$sys_tables['tgb_banners'].".clicks_limit > 0
                    GROUP BY id
                ");
                
                $ids = [];
                foreach($ids_list as $k=>$item) if(!empty($item['id'])) $ids[] = $item['id'];
               
                
                
            if (!$ajax_mode) Response::SetArray('info',$info); // информация об объекте 
            else {
                $module_template = 'admin.bot_stats.html';
                $graphic_colors = array('#3366CC','#DC3912','#FF9900','#109618','#990099','#3cff00', '#808000', '#800000','#FF0000','#F2BB20','#0011EE');       // Цвета графиков
                $data = [];
                
                if(!empty($stats)) {
                    foreach($stats as $ind=>$item) {   // Преобразование массива
                        $arr = [];
                        foreach($item as $key=>$val){
                            if ($key!='date')
                                $arr[] = array(Convert::ToString($key),Convert::ToInt($val));
                            else
                                $arr[] = array(Convert::ToString($key),Convert::ToString($val));
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
    //статистика по пользователям
    case 'users_info':
        $module_template = 'admin.bot_users.list.html';
        $sql = "SELECT ".$sys_tables['telegram_contacts'].".*,
                       CONCAT(".$sys_tables['telegram_contacts'].".firstname,
                              IF(".$sys_tables['telegram_contacts'].".lastname <> '',
                                 CONCAT(' ',".$sys_tables['telegram_contacts'].".lastname),
                                 '')
                              ) AS full_name,
                       SUM(IF(".$sys_tables['telegram_dialogs'].".message = '/search',1,0)) AS searches,
                       DATE_FORMAT(".$sys_tables['telegram_dialogs'].".`datetime`,'%d.%m.%Y') AS last_seen
                FROM ".$sys_tables['telegram_contacts']."
                LEFT JOIN ".$sys_tables['telegram_dialogs']." ON ".$sys_tables['telegram_dialogs'].".id_chat = ".$sys_tables['telegram_contacts'].".id_chat
                GROUP BY ".$sys_tables['telegram_contacts'].".`id_chat`";
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $list_totals = $db->fetchall($sql,'id_chat');
        
        //отдельно смотрим по пользователям среднее число запросов в день
        $sql = "SELECT id_chat,
                       FORMAT(AVG(amount),1) AS total_avg_day, 
                       FORMAT(AVG(amount_searches),1) AS searches_avg_day 
                FROM (
                SELECT ".$sys_tables['telegram_contacts'].".`id_chat`,
                       DATE_FORMAT(".$sys_tables['telegram_dialogs'].".`datetime`,'%d.%m.%Y') AS `date`,
                       COUNT(*) AS amount,
                       SUM(IF(".$sys_tables['telegram_dialogs'].".message = '/search',1,0)) AS amount_searches
                FROM ".$sys_tables['telegram_contacts']."
                LEFT JOIN ".$sys_tables['telegram_dialogs']." ON ".$sys_tables['telegram_dialogs'].".id_chat = ".$sys_tables['telegram_contacts'].".id_chat
                GROUP BY DATE_FORMAT(".$sys_tables['telegram_dialogs'].".`datetime`,'%d.%m.%Y'),".$sys_tables['telegram_contacts'].".`id_chat`) a";
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $list_avgs = $db->fetchall($sql,'id_chat');
        $list = [];
        foreach($list_totals as $key=>$item){
            $list[$key] = array_merge($item,(!empty($list_avgs[$key]) ? $list_avgs[$key] : []));
        } 
        // формирование списка
        Response::SetArray('list', $list);
    break;
    //статистика по боту
    case 'bot_info':
    default:
        $module_template = 'admin.bot_info.html';
        
        $users_total = $db->fetch("SELECT COUNT(*) AS amount FROM ".$sys_tables['telegram_contacts'])['amount'];
        
        //отдельно смотрим по пользователям среднее число запросов в день
        $sql = "SELECT FORMAT(AVG(amount_commands),1) AS commands_avg_day,
                       FORMAT(AVG(amount_searches),1) AS searches_avg_day,
                       FORMAT(AVG(amount_users),1) AS users_avg_day,
                       last_usage
                FROM (
                SELECT COUNT(*) AS amount_commands,
                       COUNT(DISTINCT id_chat) AS amount_users,
                       SUM(IF(".$sys_tables['telegram_dialogs'].".message = '/search',1,0)) AS amount_searches,
                       DATE_FORMAT(".$sys_tables['telegram_dialogs'].".`datetime`,'%d.%m.%Y') AS last_usage
                FROM ".$sys_tables['telegram_dialogs']."
                GROUP BY DATE_FORMAT(".$sys_tables['telegram_dialogs'].".`datetime`,'%d.%m.%Y')
                ORDER BY `datetime` DESC) a";
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $info = $db->fetch($sql);
        $info['users_total'] = $users_total;
        
        // формирование списка
        Response::SetArray('info', $info);
        break;
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>