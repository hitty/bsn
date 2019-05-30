#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/services.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// Накапливаем статистику карточек консультанта
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $this->db->query("UPDATE ".$sys_tables['consults']." SET views_count = views_count + views");
$res = $res && $this->db->query("UPDATE ".$sys_tables['consults']." SET views = 0");
$log['consult_items'] = "Статистика карточек консультанта: ".((!$res)?$this->db->error:"OK")."<br />";
////////////////////////////////////////////////////////////////////////////////////////////////
// Накапливаем статистику карточек вебинаров
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $this->db->query("UPDATE ".$sys_tables['webinars']." SET views_count = views_count + views");
$res = $res && $this->db->query("UPDATE ".$sys_tables['webinars']." SET views = 0");
$log['webinar_items'] = "Статистика карточек вебинаров: ".((!$res)?$this->db->error:"OK")."<br />";
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