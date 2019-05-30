#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/estate_complexes.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
// Статистика для объектов недвижимости - ЖК, КП, БЦ
$res = $res  && $this->db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_full_shows']."  ( id_parent,amount,date, type)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, type  FROM  ".$sys_tables['estate_complexes_stats_day_shows']."  GROUP BY  id_parent, type ");
$res = $res  && $this->db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_full_clicks']." ( id_parent,amount,date, type)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, type  FROM  ".$sys_tables['estate_complexes_stats_day_clicks']." GROUP BY  id_parent, type ");
$res = $res  && $this->db->query("TRUNCATE ".$sys_tables['estate_complexes_stats_day_shows']."");
$res = $res  && $this->db->query("TRUNCATE ".$sys_tables['estate_complexes_stats_day_clicks']."");
$log['eo_stats'] = "Статистика для объектов недвижимости: ".((!$res)?$this->db->error:"OK")."<br />";
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