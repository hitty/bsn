#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;
$this->db = $this->db;

//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/new_manager_click_limit.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//----------------Статистика лимита для кликов для менеджеров--------
if(date('j')==1) {
    $res = $res && $this->db->query("UPDATE ".$sys_tables['managers']." SET naydidom_credit_limit = month_naydidom_credit_limit, pingola_credit_limit = month_pingola_credit_limit WHERE bsn_manager = 1");
    $log['managers_click_limit'] = "Статистика лимита для кликов для менеджеров: ".((!$res)?$this->db->error:"OK")."<br />";
    $res = true;
}
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