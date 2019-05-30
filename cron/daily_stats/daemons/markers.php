#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/markers.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
// Статистика для Метки
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['markers_stats_show_full']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY FROM  ".$sys_tables['markers_stats_show_day']."  GROUP BY  id_parent ");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['markers_stats_click_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY FROM  ".$sys_tables['markers_stats_click_day']." GROUP BY  id_parent ");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['markers_stats_show_day']."");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['markers_stats_click_day']."");
$log['mark_stats'] = "Статистика для Метки: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;
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