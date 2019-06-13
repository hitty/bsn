#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/banners.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
// Статистика для баннеров - Спонсор района
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['district_banners_stats_full_shows']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['district_banners_stats_day_shows']."  GROUP BY  id_parent ");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['district_banners_stats_full_clicks']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['district_banners_stats_day_clicks']." GROUP BY  id_parent ");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['district_banners_stats_day_shows']."");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['district_banners_stats_day_clicks']."");
$log['banner_stats_sponsor'] = "Статистика для баннеров - Спонсор района: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

// Статистика для баннеров - вертикальное
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_vertical_stats_full_shows']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_vertical_stats_day_shows']."  GROUP BY  id_parent ");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_vertical_stats_full_clicks']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_vertical_stats_day_clicks']." GROUP BY  id_parent ");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['tgb_vertical_stats_day_shows']."");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['tgb_vertical_stats_day_clicks']."");
$log['banner_stats_vertical'] = "Статистика для баннеров - вертикальное: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

// Статистика для баннеров - float с обратным звонком
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_float_stats_full_shows']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_float_stats_day_shows']."  GROUP BY  id_parent ");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['tgb_float_stats_full_clicks']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_float_stats_day_clicks']." GROUP BY  id_parent ");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['tgb_float_stats_day_shows']."");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['tgb_float_stats_day_clicks']."");
$log['banner_stats_vertical'] = "Статистика для баннеров - float с обратным звонком: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

// Статистика для баннеров - Кредитный калькулятор
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['credit_calculator_stats_show_full']."  ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['credit_calculator_stats_show_day']."  GROUP BY  id_parent, `type`  ");
$res = $res && $this->db->query("INSERT INTO ".$sys_tables['credit_calculator_stats_click_full']." ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['credit_calculator_stats_click_day']." GROUP BY  id_parent, `type` ");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['credit_calculator_stats_show_day']."");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['credit_calculator_stats_click_day']."");
$log['banner_stats_cc'] = "Статистика для баннеров - Кредитный калькулятор: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

// Статистика для Баннеров 
$res = $res && $db->query("
        INSERT INTO ".$sys_tables['banners_stats_show_full']."  
            ( id_parent,amount,date)  
        SELECT 
            id_parent, count(*), CURDATE() - INTERVAL 1 DAY 
        FROM  ".$sys_tables['banners_stats_show_day']."  
        GROUP BY  id_parent 
    ");
    
$res = $res && $this->db->query("
        INSERT INTO ".$sys_tables['banners_stats_click_full']." 
            ( id_parent, amount, date, `from`, position)  
        SELECT 
            id_parent,  count(*), CURDATE() - INTERVAL 1 DAY , `from`, position  
        FROM  ".$sys_tables['banners_stats_click_day']." 
        WHERE DATE(`datetime`) = CURDATE() - INTERVAL 1 DAY
        GROUP BY  id_parent, `from`, position
");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['banners_stats_show_day']."");
$res = $res && $this->db->query("TRUNCATE ".$sys_tables['banners_stats_click_day']."");
$log['banners_stats'] = "Статистика для Баннеров : ".((!$res)?$this->db->error:"OK")."<br />";
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