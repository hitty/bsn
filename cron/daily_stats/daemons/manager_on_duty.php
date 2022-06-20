#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/manager_on_duty.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
$duty_manager = $this->db->fetch("(
                                SELECT id 
                                FROM ".$sys_tables['managers']." 
                                WHERE bsn_manager = 1 AND content_manager = 2 AND id >  ( SELECT id FROM ".$sys_tables['managers']." WHERE duty = 1 ) 
                                ORDER BY id ASC
                            ) UNION (
                                SELECT id 
                                FROM ".$sys_tables['managers']." 
                                WHERE bsn_manager = 1 AND content_manager = 2
                                ORDER BY id ASC
                            )
");                                  
$res = $res && $this->db->query("UPDATE ".$sys_tables['managers']." SET duty = 2 WHERE duty = 1");
$res = $res && $this->db->query("UPDATE ".$sys_tables['managers']." SET duty = 1 WHERE id = ?", $duty_manager['id']);
$log['working_manager'] = "установка дежурного менеджера БСН: ".((!$res)?$this->db->error:"OK")."<br />";
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