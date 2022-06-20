#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;

//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/tgb_general.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//---------- СТАТИСТИКА СПЕЦПРЕДЛОЖЕНИЙ, ОБЩАЯ ----------------------
//подсчет статистики кликов по телефону
$ids = $this->db->fetch("SELECT GROUP_CONCAT(id) as ids FROM ".$sys_tables['tgb_banners']." WHERE published = 1 AND enabled = 1 AND credit_clicks = 1")['ids'];
if( !empty( $ids ) )
    $res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_banners_credits_stats']."  ( id_parent,amount,clicks_amount,date)  
                           SELECT 
                                id_banner, 
                                day_limit,
                                (SELECT  IFNULL(COUNT(*),0) as cnt FROM ".$sys_tables['tgb_stats_day_clicks']." WHERE ".$sys_tables['tgb_banners_credits'].".id_banner = ".$sys_tables['tgb_stats_day_clicks'].".id_parent) as clicks_amount,
                                CURDATE() - INTERVAL 1 DAY  
                           FROM  ".$sys_tables['tgb_banners_credits']." 
                           WHERE id_banner IN (".$ids.") 
                           GROUP BY  id_banner ");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_stats_full_shows']."  ( id_parent,in_estate,amount,date)  SELECT id_parent,in_estate, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_stats_day_shows']."  GROUP BY  id_parent,in_estate ");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_stats_full_clicks']." ( id_parent,in_estate,amount,date,`from`, position)  SELECT id_parent, in_estate, count(*), CURDATE() - INTERVAL 1 DAY, `from`, position  FROM  ".$sys_tables['tgb_stats_day_clicks']." GROUP BY  id_parent, `from`, position,in_estate ");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['tgb_stats_day_shows']."");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['tgb_stats_day_clicks']."");

$res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_stats_popup_full']." ( id_parent,type,amount,date)  SELECT id_parent, action AS type, count(*) AS amount, CURDATE() - INTERVAL 1 DAY FROM  ".$sys_tables['tgb_stats_popup_day']." GROUP BY  id_parent, action");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['tgb_stats_popup_day']."");

$log['tgb_stats'] = "Статистика для тгб: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;
//-------------------------------------------------------------------

$log['phones_stats'] = "Статистика кликов по телефону: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;
$log = implode('<br />',$log);

$full_log = ob_clean();
return array('id_action' => $this->getActionInfo("id"),
             'datetime_finished' => date('Y-m-d H:i:s'),
             'log' => $log,
             'full_log' => $full_log,
             'db_error' => $this->db->error,
             'result_status' => $this->getStatus($res));
?>