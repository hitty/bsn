#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/phone_clicks.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//---------- СТАТИСТИКА СПЕЦПРЕДЛОЖЕНИЙ, ОБЩАЯ ----------------------
//подсчет статистики кликов по телефону
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['phone_clicks_full']." ( id_parent,id_object,amount,date, type, status)  SELECT id_parent, id_object, count(*), CURDATE() - INTERVAL 1 DAY, type, status  FROM  ".$sys_tables['phone_clicks_day']." GROUP BY  id_object, status ");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['phone_clicks_day']."");
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