#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/ip_stats.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// Переносим в общую таблицу статистику по ip
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['visitors_ips_stats_full']." (ip,date,visits,avg_interval,min_avg_interval,bot_id) 
                                 SELECT ip,
                                        visit_time AS date,
                                        COUNT(*) AS visits,
                                        0 AS avg_interval,
                                        0 AS min_avg_interval,
                                        GROUP_CONCAT(DISTINCT user_agent)
                                 FROM ".Config::$values['sys_tables']['visitors_ips_day']."
                                 GROUP BY ip");
$res = $res && $this->db->query("TRUNCATE TABLE ".$sys_tables['visitors_ips_day']);
$log['ips_stats'] = "Перенос в общую статистику суточной статистики по IP: ".((!$res)?$this->db->error:"OK")."<br />";
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