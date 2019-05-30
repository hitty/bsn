#!/usr/bin/php
<?php

error_reporting(E_ALL);


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/send_ending.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

$db = $this->db;
$sys_tables = $this->sys_tables;

//-------------------------------------------------------------------
require_once('cron/mailers/send_ending_stats.php');
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