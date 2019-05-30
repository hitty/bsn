#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/spec_offers_actualizing.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
//снятие актуальности со спецух просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['spec_offers_objects']." SET `base_page_flag`=2, `first_page_flag`=2 , `first_page_head_flag`=2 , `inestate_flag`=2 WHERE `date_end` <= CURDATE()");
$res = $res && $this->db->query("UPDATE ".$sys_tables['spec_offers_packets']." SET `base_page_flag`=2, `first_page_flag`=2 , `first_page_head_flag`=2 , `inestate_flag`=2 WHERE `date_end` <= CURDATE()");
$log['specoffers_arch'] = "Снятие актуальности со спецпредложений, просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
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