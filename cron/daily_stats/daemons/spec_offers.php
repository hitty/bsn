#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/spec_offers.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
//Статистика для Спецпредложений
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['spec_objects_stats_show_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['spec_objects_stats_show_day']." GROUP BY  id_parent");
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['spec_objects_stats_click_full']." ( id_parent,amount,date,`from`) SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `from`  FROM ".$sys_tables['spec_objects_stats_click_day']." GROUP BY  id_parent, `from`");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['spec_objects_stats_show_day']);
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['spec_objects_stats_click_day']);

$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['spec_packets_stats_show_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['spec_packets_stats_show_day']." GROUP BY  id_parent");
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['spec_packets_stats_click_full']." ( id_parent,amount,date) SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['spec_packets_stats_click_day']." GROUP BY  id_parent");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['spec_packets_stats_click_day']);
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['spec_packets_stats_show_day']);
$log['specoffers_stats'] = "Статистика для Спецпредложений: ".((!$res)?$this->db->error:"OK")."<br />";
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