<?php
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
// добавление title
$this_page->manageMetadata(array('title'=>'Заявки'));
// собираем GET-параметры
$get_parameters = [];
$filters = [];
if(!empty($auth->id_agency)) $filters['agency'] = $auth->id_agency;
else $filters['agency'] = Request::GetInteger('f_agency',METHOD_GET);
$filters['date_start'] = Request::GetString('f_date_start',METHOD_GET);
$filters['date_end'] = Request::GetString('f_date_end',METHOD_GET);
$filters['period'] = Request::GetString('f_period',METHOD_GET);

if(!empty($filters['agency'])) $get_parameters['f_agency'] = $filters['agency'];
if(!empty($filters['date_start'])) $get_parameters['f_date_start'] = $filters['date_start'];
if(!empty($filters['date_end'])) $get_parameters['f_date_end'] = $filters['date_end'];
if(!empty($filters['period'])) $get_parameters['f_period'] = $filters['period'];

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
Response::SetString('img_folder',Config::$values['img_folders']['campaigns']);
// обработка action-ов
switch($action){

    /*********************************\
    |*  Работа с заявкими        *|
    \*********************************/
    case 'finances':
        $GLOBALS['js_set'][] = '/js/main.js';
        $GLOBALS['js_set'][] = '/modules/finances/ajax_actions.js';
        // переопределяем экшн
        $ajax_action = Request::GetString('action', METHOD_POST);
        $action = empty($this_page->page_parameters[1]) ? "" : (empty($ajax_action) ? $this_page->page_parameters[1]: $ajax_action);
        switch($action){
            default:
                $module_template = 'admin.finances.list.html';
                // формирование списка для фильтра
                $agencies = $db->fetchall("SELECT id,title FROM ".$sys_tables['agencies']." ORDER BY title");
                Response::SetArray('agencies',$agencies);
                // формирование фильтра
                $conditions = array('1');
                if(!empty($filters['agency'])) $conditions['agency'] = $sys_tables['finances'].".`id_agency` = ".$db->real_escape_string($filters['agency']);
                if(!empty($filters['date_start'])) $conditions['date_start'] = "DATE(".$sys_tables['finances'].".`datetime`) >= '".date("Y-m-d", strtotime($filters['date_start']))."'";
                if(!empty($filters['date_end'])) $conditions['date_end'] = "DATE(".$sys_tables['finances'].".`datetime`) <= '".date("Y-m-d", strtotime($filters['date_end']))."'";
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);        
                Response::SetString('img_folder',Config::$values['img_folders']['campaigns']);
                $list = $db->fetchall("SELECT ".$sys_tables['finances'].".*,
                                              IF(YEAR(".$sys_tables['finances'].".datetime) < Year(CURDATE()),DATE_FORMAT(".$sys_tables['finances'].".datetime,'%e %M %Y'),DATE_FORMAT(".$sys_tables['finances'].".datetime,'%e %M, %k:%i')) as normal_date, 
                                              ".$sys_tables['agencies'].".title as agency_title,
                                              ".$sys_tables['campaigns'].".title as campaign_title,
                                              ".$sys_tables['finances_operations'].".title as operation_title,
                                              ".$sys_tables['finances_operations'].".`balance_type` 
                                       FROM ".$sys_tables['finances']."
                                       LEFT JOIN  ".$sys_tables['finances_operations']." ON ".$sys_tables['finances_operations'].".id = ".$sys_tables['finances'].".id_operation
                                       LEFT JOIN  ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['finances'].".id_agency
                                       LEFT JOIN  ".$sys_tables['campaigns']." ON ".$sys_tables['campaigns'].".id = ".$sys_tables['finances'].".id_campaign
                                       WHERE ".(implode(" AND ",$conditions))."
                                       GROUP BY ".$sys_tables['finances'].".id
                                       ORDER BY ".$sys_tables['finances'].".id DESC
                ");                                            
                $total = array('income'=>0,'expenditure'=>0);
                foreach($list as $k=>$item) {
                    $total['income'] += $item['income'];
                    $total['expenditure'] += $item['expenditure'];
                }
                Response::SetArray('total',$total);
                Response::SetArray('list',$list);
                break;
        }
        break;               
}
// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));
?>