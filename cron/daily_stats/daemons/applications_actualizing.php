#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/applications_actualizing.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// Делаем видимыми только платным клиентам заявки от 5 до 10 дней
////////////////////////////////////////////////////////////////////////////////////////////////     
$res = $res && $this->db->querys("UPDATE ".$sys_tables['applications']." SET visible_to_all = 3 WHERE (DATEDIFF(NOW(),".$sys_tables['applications'].".`datetime`) BETWEEN 5 AND 10) AND visible_to_all = 1 AND status = 2");
$log['apps_free_for_payed'] = "Бесплатные заявки старше 5 дней для платных клиентов: ".((!$res)?$this->db->error:"OK")."<br />";
////////////////////////////////////////////////////////////////////////////////////////////////
// Убираем в архив заявки, которые старше 10 дней
////////////////////////////////////////////////////////////////////////////////////////////////     
$res = $res && $this->db->querys("UPDATE ".$sys_tables['applications']." SET status = 8 WHERE DATEDIFF(NOW(),".$sys_tables['applications'].".`datetime`) >= 10 AND visible_to_all IN (1,3) AND status = 2");
$log['apps_archive'] = "Убирание в архив заявок старше 10 дней: ".((!$res)?$this->db->error:"OK")."<br />";
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