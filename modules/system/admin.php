<?php

require_once('includes/class.paginator.php');
require_once('includes/class.log.php');

// мэппинги модуля
$this_page->manageMetadata(array('title'=>'Системные ошибки'));
// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['date'] = Request::GetString('f_date',METHOD_GET);
$filters['project'] = Request::GetString('f_project',METHOD_GET);

if(!empty($filters['title']))$get_parameters['f_title'] = $filters['title'];
if(!empty($filters['date']))$get_parameters['f_date'] = $filters['date'];
if(!empty($filters['project']))$get_parameters['f_project'] = $filters['project'];

$page = Request::GetInteger('page',METHOD_GET);
if ((isset($page))&&($page==0)) Host::Redirect("admin/system/"); 
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$id_user = $auth->id;

//условие для подстановки алиасов роботов
$crawlers_condition = array("WHEN 0 THEN ''","WHEN -1 THEN 'mixed'");
foreach(Config::$values['crawlers_aliases'] as $k=>$i){
    $crawlers_condition[] = "WHEN ".$k." THEN '".$i."'";
}
$crawlers_condition = implode("\r\n",$crawlers_condition);

// обработка action-ов
switch(true){
    case $action == 'mysql_cron' || $action == 'mysql':
        $cron_errors = ($action == 'mysql_cron');
        Response::SetBoolean('cron_errors',$cron_errors);
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch($action){
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $list = $db->loadErrorsData(false,$cron_errors);
                $count = 0;
                foreach($list as $k=>$item){
                    if($count == $id){
                        unset($list[$k]);
                        break;
                    }
                    $count++;
                }
                if(count($list) == 0) $db->clearErrorsData();
                else $db->saveErrorsData(false, false, $list,$cron_errors);
                $ajax_result = array('ok' => true, 'ids'=>array($id));
                break;
            default:
                $list = $db->loadErrorsData(false,$cron_errors);
                if(!empty($list)) Response::SetArray('list', $list);
                $module_template = (!empty($cron_errors) ? 'admin.cron_mysql.html' : 'admin.mysql.html');
                break;
        }
        break;
    case $action == 'members_errors' :
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch($action){
            case 'del':
                Log::clearData( 'members_errors' );
                $ajax_result = array( 'ok' => true, 'ids'=>[] );
                break;
            default:
                $list = Log::loadData( 'members_errors' );
                if(!empty($list)) Response::SetArray('list', $list);
                $module_template = 'admin.log.list.html';
                break;
        }
        break;        
    case $action == 'ip_visits':
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch(true){
            //добавляем ip в заблокированные
            case $action == 'to_blacklist' && $ajax_mode:
                $id = Request::GetString('id',METHOD_POST);
                $ip = Request::GetString('ip',METHOD_POST);
                $block_type = Request::GetInteger('block_type',METHOD_POST);
                if(empty($ip) || !preg_match('/^[0-9\.]+$/',$ip)){
                    $ajax_result['ok'] = false;
                    break;
                }
                $exists = $db->fetch("SELECT id FROM ".$sys_tables['blacklist_ips']." WHERE ip = ?",$ip);
                if(empty($exists)) $res = $db->query("INSERT INTO ".$sys_tables['blacklist_ips']." (ip,block_start,block_type,place_found,block_initiator) VALUES (?,NOW(),?,9,?)", $ip, $block_type, $auth->id);
                else $res = $db->query("UPDATE ".$sys_tables['blacklist_ips']." SET ".($block_type > 0 ? " block_start = NOW(), " : "")."block_type = ?, block_initiator = ? WHERE ip = ?",$block_type, $auth->id, $ip);
                //
                $ajax_result = array('ok' => true,'result' => $res, 'ids'=>array($id));
                break;
            case $action == 'full':
                
                $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
                
                switch(true){
                    //определенные сутки
                    case (preg_match('/^[0-9]+$/',$action)):
                        $condition = "";
                
                        $paginator = new Paginator($sys_tables['visitors_ips_stats_day'], 30, $condition);
                        $get_in_paginator = [];
                        foreach($get_parameters as $gk=>$gv){
                            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                        }
                        $paginator->link_prefix = '/admin/system/ip_visits/full'                  
                                                  ."/?"                                       
                                                  .implode('&',$get_in_paginator)             
                                                  .(empty($get_in_paginator)?"":'&')."page=";
                        if($paginator->pages_count>0 && $paginator->pages_count<$page){
                            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                            exit(0);
                        }
                        
                        $ip_visits_full = $db->fetchall("SELECT *,
                                                           DATE_FORMAT(".$sys_tables['visitors_ips_stats_full'].".date,'%d.%m.%Y') AS date_formatted,
                                                           CASE ".$sys_tables['visitors_ips_stats_full'].".bot_id 
                                                                ".$crawlers_condition."
                                                                ELSE IF(".$sys_tables['visitors_ips_stats_full'].".bot_id > 3,'какой-то','') 
                                                           END AS user_type
                                                    FROM ".$sys_tables['visitors_ips_stats_full']."
                                                    WHERE UNIX_TIMESTAMP(".$sys_tables['visitors_ips_stats_full'].".date) = ?
                                                    ORDER BY visits DESC"." 
                                                    LIMIT ".$paginator->getLimitString($page),false,$action);
                        Response::SetArray('ip_visits_full',$ip_visits_full);
                        
                        if($paginator->pages_count>1) Response::SetArray('paginator', $paginator->Get($page));
                        
                        $module_template = 'admin.ip.visits.day.html';
                        break;
                    //общий список
                    default:
                        $condition = "";
                
                        $paginator = new Paginator($sys_tables['visitors_ips_stats_day'], 30, $condition);
                        $get_in_paginator = [];
                        foreach($get_parameters as $gk=>$gv){
                            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                        }
                        $paginator->link_prefix = '/admin/system/ip_visits/full'                  
                                                  ."/?"                                       
                                                  .implode('&',$get_in_paginator)             
                                                  .(empty($get_in_paginator)?"":'&')."page=";
                        if($paginator->pages_count>0 && $paginator->pages_count<$page){
                            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                            exit(0);
                        }
                        
                        $ip_visits_full = $db->fetchall("SELECT DATE_FORMAT(".$sys_tables['visitors_ips_stats_full'].".date,'%d.%m.%Y') AS date_formatted,
                                                           SUM(".$sys_tables['visitors_ips_stats_full'].".visits) AS visits,
                                                           SUM(IF(".$sys_tables['visitors_ips_stats_full'].".bot_id = 1,1,0)) AS yandex_bots,
                                                           SUM(IF(".$sys_tables['visitors_ips_stats_full'].".bot_id = 2,1,0)) AS google_bots,
                                                           SUM(IF(".$sys_tables['visitors_ips_stats_full'].".bot_id = 3,1,0)) AS mailru_bots,
                                                           SUM(IF(".$sys_tables['visitors_ips_stats_full'].".bot_id = -1,1,0)) AS mixed_bots,
                                                           SUM(IF(".$sys_tables['visitors_ips_stats_full'].".bot_id = 0,1,0)) AS users,
                                                           UNIX_TIMESTAMP(".$sys_tables['visitors_ips_stats_full'].".date) AS date_timestamp
                                                    FROM ".$sys_tables['visitors_ips_stats_full']."
                                                    GROUP BY ".$sys_tables['visitors_ips_stats_full'].".date
                                                    ORDER BY ".$sys_tables['visitors_ips_stats_full'].".date DESC");
                        Response::SetArray('ip_visits_full',$ip_visits_full);
                        
                        //if($paginator->pages_count>1) Response::SetArray('paginator', $paginator->Get($page));
                        
                        $module_template = 'admin.ip.visits.full.html';
                }
                
                break;
            default:
                
                
                $condition = "";
                
                $paginator = new Paginator($sys_tables['visitors_ips_stats_day'], 30, $condition);
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                $paginator->link_prefix = '/admin/system/ip_visits'                  
                                          ."/?"                                       
                                          .implode('&',$get_in_paginator)             
                                          .(empty($get_in_paginator)?"":'&')."page=";
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
                
                $ip_visits = $db->fetchall("SELECT ".$sys_tables['visitors_ips_stats_day'].".*,
                                                   DATE_FORMAT(".$sys_tables['visitors_ips_stats_day'].".last_visit,'%H:%i:%s') AS last_visit_formatted,
                                                   CASE ".$sys_tables['visitors_ips_stats_day'].".bot_id 
                                                        ".$crawlers_condition."
                                                        ELSE IF(".$sys_tables['visitors_ips_stats_day'].".bot_id > 3,'какой-то','')
                                                   END AS user_type,
                                                   (".$sys_tables['blacklist_ips'].".id IS NOT NULL) AS is_blocked,
                                                   DATE_FORMAT(".$sys_tables['blacklist_ips'].".block_start,'%d.%m.%Y %H:%i:%s') AS block_start_time
                                            FROM ".$sys_tables['visitors_ips_stats_day']."
                                            LEFT JOIN ".$sys_tables['blacklist_ips']." ON ".$sys_tables['blacklist_ips'].".ip = ".$sys_tables['visitors_ips_stats_day'].".ip AND 
                                                      ".$sys_tables['blacklist_ips'].".block_type > 0
                                            ORDER BY pages_visited DESC"." LIMIT ".$paginator->getLimitString($page),'ip');
                $ips = "'".implode("','",array_keys($ip_visits))."'";
                $ips_geodata = $db->fetchall("SELECT * FROM ".$sys_tables['ip_geodata']." WHERE ip IN (".$ips.")",'ip');
                foreach($ip_visits as $key=>$item){
                    if(!empty($ips_geodata[$key])){
                        $ip_visits[$key]['id_geodata'] = $ips_geodata[$key]['id_geodata'];
                        $ip_visits[$key]['txt_addr'] = $ips_geodata[$key]['txt_addr'];
                    }else{
                        $ip_visits[$key]['id_geodata'] = 0;
                        $ip_visits[$key]['txt_addr'] = "";
                    }
                }
                Response::SetArray('ip_visits',$ip_visits);
                
                if($paginator->pages_count>1) Response::SetArray('paginator', $paginator->Get($page));
                
                $module_template = 'admin.ip.visits.html';
                break;
        }
        break;
}

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>