#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/tgb_avg.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
// среднее кол-во показов для ТГБ в разделе

    $res = $res && $this->db->query("INSERT INTO  ".$sys_tables['tgb_daily_show_stats']." (amount, date, type)
    SELECT AVG(amount) as amount,  date as date, in_estate as type FROM ".$sys_tables['tgb_stats_full_shows']." 
    WHERE in_estate > 0 AND date = CURDATE() - INTERVAL 1 DAY");
    $res = $res && $this->db->query("INSERT INTO  ".$sys_tables['tgb_daily_click_stats']." (amount, date, type)
    SELECT AVG(amount) as amount,  date as date, in_estate as type FROM ".$sys_tables['tgb_stats_full_clicks']."
    WHERE in_estate > 0 AND date = CURDATE() - INTERVAL 1 DAY");

$log['avg_tgb'] = "среднее кол-во показов для ТГБ в разделе: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//запись кол-ва показов в месяц в начале каждого месяца
if(date('j')==1) {
    $res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_monthly_show_stats']." (amount, date, `type`) SELECT SUM( amount ) AS amount, CURDATE() - INTERVAL 1 DAY AS date, `type` FROM ".$sys_tables['tgb_daily_show_stats']." WHERE date_format(date, '%Y%m') = date_format(date_add(now(), interval -1 month), '%Y%m') GROUP BY `type`");
    $res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_monthly_click_stats']." (amount, date, `type`) SELECT SUM( amount ) AS amount, CURDATE() - INTERVAL 1 DAY AS date, `type` FROM ".$sys_tables['tgb_daily_click_stats']." WHERE date_format(date, '%Y%m') = date_format(date_add(now(), interval -1 month), '%Y%m') GROUP BY `type`");
    $log['avg_shows_month'] = "запись кол-ва показов в месяц в начале каждого месяца: ".((!$res)?$this->db->error:"OK")."<br />";
    $res = true;
}
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