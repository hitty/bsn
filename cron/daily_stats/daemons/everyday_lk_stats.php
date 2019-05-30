#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/everyday_lk_stats.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
//---------- Обнуление поля случайной сортировки ----------------------
$estate_types = array('country','live','commercial','build');
foreach($estate_types as $estate_type) {
    $res = $res && $this->db->query("UPDATE ".$sys_tables[$estate_type]." SET rand_order=0 ");
}
$log['rand_order_nullify'] = "Обнуление поля случайной сортировки: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Подсчет ежедневной статистики объектов личного кабинета
////////////////////////////////////////////////////////////////////////////////////////////////     
//date_in >= CURDATE() - INTERVAL 1 DAY     DATE_ADD(CURDATE(), INTERVAL -2 day)
$estate_types = array('live','build','commercial','country');
foreach($estate_types as $key=>$estate_type){
    $res = $res && $this->db->query("INSERT INTO ".$sys_tables['cabinet_stats']." (`date`, estate_type, deal_type, status, amount)
                SELECT DATE_ADD(CURDATE(), INTERVAL -1 day) AS `date`, ".($key+1)." AS estate_type, deal_type, status, amount FROM
                (
                SELECT 1 AS deal_type, 2 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 1 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 2 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 1 AS deal_type, 3 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 1 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 3 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 1 AS deal_type, 4 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 1 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 4 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 1 AS deal_type, 5 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 1 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 5 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 2 AS deal_type, 2 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 2 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 2 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 2 AS deal_type, 3 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 2 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 3 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 2 AS deal_type, 4 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 2 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 4 AND
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 2 AS deal_type, 5 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 2 AND
                      ".$sys_tables[$estate_type].".published = 1 AND
                      ".$sys_tables[$estate_type].".info_source = 1 AND
                      ".$sys_tables[$estate_type].".status = 5 AND
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                ) as a
        ");
}
$log['daily_stats'] = "Подсчет ежедневной статистики объектов личного кабинета: ".((!$res)?$this->db->error:"OK")."<br />";
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