#!/usr/bin/php
<?php


error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/textline.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
// Статистика для TextLine
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['textline_stats_full_shows']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['textline_stats_day_shows']."  GROUP BY  id_parent ");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['textline_stats_full_clicks']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['textline_stats_day_clicks']." GROUP BY  id_parent ");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['textline_stats_day_shows']."");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['textline_stats_day_clicks']."");
$log['textline_stats'] = "Статистика для TextLine: ".((!$res)?$this->db->error:"OK")."<br />";
//снятие актуальности с TextLine просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['textline_campaigns']." SET `enabled`=2, `clicks_limit` = 0 WHERE `date_end` <= CURDATE() and enabled=1");
$log['textline_arch'] = "Снятие актуальности с РК TextLine просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
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