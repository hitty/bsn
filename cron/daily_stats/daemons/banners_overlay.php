#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/banners_overlay.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

 // Статистика для баннеров - Adriver
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['banners_stats_show_full']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY FROM  ".$sys_tables['banners_stats_show_day']."  GROUP BY  id_parent ");
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['banners_stats_click_full']." ( id_parent,amount,date,`from`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY,`from` FROM  ".$sys_tables['banners_stats_click_day']." GROUP BY  id_parent,`from` ");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['banners_stats_show_day']."");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['banners_stats_click_day']."");
$res = $res && $this->db->querys("UPDATE ".$sys_tables['banners']." SET days_views = 0");
$log['banner_stats_adriver'] = "Статистика для баннеров - ADRIVER баннер: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

// Статистика для баннеров - Кредитный калькулятор
/*
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['tgb_overlay_stats_full_shows']."  ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['tgb_overlay_stats_day_shows']."  GROUP BY  id_parent, `type` ");
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['tgb_overlay_stats_full_clicks']." ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['tgb_overlay_stats_day_clicks']." GROUP BY  id_parent, `type` ");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['tgb_overlay_stats_day_shows']."");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['tgb_overlay_stats_day_clicks']."");
$log['banner_stats_cc'] = "Статистика для баннеров - Overlay баннер: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;
*/
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['content_stats_full_shows']."  ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['content_stats_day_shows']."  GROUP BY  id_parent, `type`  ");
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['content_stats_full_clicks']." ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['content_stats_day_clicks']." GROUP BY  id_parent, `type` ");
$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['content_stats_full_finish']." ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['content_stats_day_finish']." GROUP BY  id_parent, `type` ");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['content_stats_day_shows']."");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['content_stats_day_clicks']."");
$res = $res && $this->db->querys("TRUNCATE ".$sys_tables['content_stats_day_finish']."");
$log['content_stats_cc'] = "Статистика для контента: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;



//-------------------------------------------------------------------
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