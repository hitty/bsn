#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/news_parsed_actualize.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// Переносим в архив спарсенные новости старше суток
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $this->db->query("UPDATE ".$sys_tables['news_parsing']." SET status = 4 WHERE TIMESTAMPDIFF(DAY, creation_datetime, NOW()) >=1 AND status = 1");
$log['news_parsed_archive'] = "Перенос в архив необработанных новостей старше суток: ".((!$res)?$this->db->error:"OK")."<br />";
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