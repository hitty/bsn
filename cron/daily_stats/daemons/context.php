#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/context.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

require_once("includes/class.context_campaigns.php");

//-------------------------------------------------------------------
//клики и просмотры по контекстным блокам
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['context_stats_show_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['context_stats_show_day']." GROUP BY  id_parent");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['context_stats_click_full']." ( id_parent,amount,date) SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['context_stats_click_day']." GROUP BY  id_parent");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['context_stats_click_day']);
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['context_stats_show_day']);
$log['context_clicks_shows'] = "Клики и просмотры по контекстным блокам: ".((!$res)?$this->db->error:"OK")."<br />";
$res = $this->db->query("UPDATE ".$sys_tables['context_campaigns']." SET published = 1 WHERE DATE(`date_start`) = CURDATE()");
$log['context_auto_start'] = "Старт кампаний по дате начала: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;
 //обновляем флаг редактирования для агентств
 $this->db->query("UPDATE ".$sys_tables['agencies']." SET can_change_time = 1");
 //клики и просмотры по агентствам на главной
$res = $this->db->query("INSERT INTO ".$sys_tables['agencies_mainpage_stats_show_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['agencies_mainpage_stats_show_day']." GROUP BY  id_parent");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['agencies_mainpage_stats_click_full']." ( id_parent,amount,date) SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['agencies_mainpage_stats_click_day']." GROUP BY  id_parent");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['agencies_mainpage_stats_click_day']);
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['agencies_mainpage_stats_show_day']);
$log['agencies_mainpage_clicks_shows'] = "Клики и просмотры по агентствам на главной ".((!$res)?$this->db->error:"OK")."<br />";

//убираем в архив контекстные рекламные кампании (вместе со всеми объявлениями), срок действия которых закончился
//читаем список кампаний, которые будем убирать, чтобы оповестить их и менеджеров
$finishing_campaigns = $this->db->fetchall("SELECT ".$sys_tables['context_campaigns'].".title,
                                             ".$sys_tables['users'].".id AS user_id,
                                             ".$sys_tables['agencies'].".id AS agency_id,
                                             IF(".$sys_tables['agencies'].".email IS NULL,".$sys_tables['users'].".email,".$sys_tables['agencies'].".email) AS agency_email,
                                             IF(".$sys_tables['agencies'].".title IS NULL,".$sys_tables['users'].".name,".$sys_tables['agencies'].".title) AS agency_title,
                                             ".$sys_tables['managers'].".id AS manager_id,
                                             ".$sys_tables['managers'].".name AS manager_name,
                                             ".$sys_tables['managers'].".email AS manager_email
                                      FROM ".$sys_tables['context_campaigns']."
                                      LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['context_campaigns'].".id_user
                                      LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                      LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                      WHERE ".$sys_tables['context_campaigns'].".date_end<=NOW() AND ".$sys_tables['context_campaigns'].".published = 1");
$res = $res && $this->db->query("UPDATE ".$sys_tables['context_advertisements']." SET published = 2 WHERE id_campaign IN (SELECT id FROM ".$sys_tables['context_campaigns']." WHERE date_end<NOW())");
$res = $res && $this->db->query("UPDATE ".$sys_tables['context_campaigns']." SET published = 2 WHERE date_end<NOW()");
$log['context_archivate'] = "Уход в архив контекстных штук: ".((!$res)?$this->db->error:"OK")."<br />";

//оповещаем менеджеров и компании
if(!empty($finishing_campaigns)){
    $managers_list = array();
    //сначала набираем списки для компаний
    foreach($finishing_campaigns as $item){
        $agencies_list[$item['user_id']]['campaigns_titles'][] = $item['title'];
        $agencies_list[$item['user_id']]['agency_title'] = $item['agency_title'];
        $agencies_list[$item['user_id']]['agency_email'] = $item['agency_email'];
        $agencies_list[$item['user_id']]['manager_id'] = $item['manager_id'];
        $agencies_list[$item['user_id']]['manager_name'] = $item['manager_name'];
        $agencies_list[$item['user_id']]['manager_email'] = $item['manager_email'];
    }
    
    //рассылаем уведомления компаниям и заполняем списки для менеджеров
    foreach($agencies_list as $item){
        //если заканчивается сразу несколько рекламных кампаний, запоминаем это
        if(count($item['campaigns_titles'])>1)$item['multiple'] = true;
        if(!empty($item['manager_email'])){
            $managers_list[$item['manager_id']]['cmp_list'][] = $item;
            $managers_list[$item['manager_id']]['manager_email'] = $item['manager_email'];
            $managers_list[$item['manager_id']]['manager_name'][] = $item['manager_name'];
        }
        unset($item['agency_title']);
        contextCampaigns::Notification(5,$item,true,false);
    }
    //рассылаем уведомления менеджерам
    foreach($managers_list as $item){
        if(!empty($item['manager_email'])) contextCampaigns::Notification(5,$item,false,true);
    }
}
//-------------------------------------------------------------------

$log = implode('<br />',$log);


$full_log = ob_clean();
return array('id_action' => $this->getActionInfo("id"),
             'datetime_finished' => date('Y-m-d H:i:s'),
             'log' => $log,
             'full_log' => $full_log,
             'db_error' => $this->db->error,
             'result_status' => $this->getStatus($res));

?>