#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/promotions.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
//снятие актуальности с акций 
$active_promotions = $this->db->fetchall("SELECT * FROM ".$sys_tables['promotions']." WHERE ( `date_end` <= CURDATE() OR `date_start` > CURDATE() ) AND published = 1");
foreach($active_promotions as $k=>$promotion){
    $estate_type = $this->db->fetch("SELECT `type` FROM ".$sys_tables['estate_types']." WHERE id = ?", $promotion['id_estate_type']);
    $res = $res && $this->db->querys("UPDATE ".$sys_tables[$estate_type['type']]." SET status = ?, status_date_end = '0000-00-00', id_promotion = 0 WHERE id_promotion = ?", 2, $promotion['id_estate_type']);
    $res = $res && $this->db->querys("UPDATE ".$sys_tables['promotions']." SET `published` = 3 WHERE id = ?", $promotion['id']);
}
//простановка актуальности акциям
$res = $res && $this->db->querys("UPDATE ".$sys_tables['promotions']." SET `published` = 1 WHERE `date_start` <= CURDATE() AND `date_end` > CURDATE() AND published = 3");
$log['promotion_arch'] = "Снятие актуальности с акций просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;
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