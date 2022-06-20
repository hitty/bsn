#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/clean_archive.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// Чистим статистику объектов, которых нет в основных таблицах
////////////////////////////////////////////////////////////////////////////////////////////////
$estate_types = array('live','build','commercial','country');
foreach($estate_types as $key=>$estate_type){
    //читаем все id_parent,которые будем убирать, чтобы не читать несколько раз в запросах
    $ids_to_clear = $this->db->fetchall("SELECT DISTINCT ".$sys_tables[$estate_type."_stats_show_full"].".id_parent
                                   FROM ".$sys_tables[$estate_type."_stats_show_full"]."
                                   LEFT JOIN ".$sys_tables[$estate_type]." ON ".$sys_tables[$estate_type."_stats_show_full"].".id_parent = ".$sys_tables[$estate_type].".id 
                                   WHERE ".$sys_tables[$estate_type].".id IS NULL",'id_parent');
    $ids_to_clear = implode(',',array_keys($ids_to_clear));
    if(empty($ids_to_clear)) continue;
    echo $res;
    $res = $res && $this->db->query("DELETE FROM ".$sys_tables[$estate_type."_stats_show_full"]." WHERE id_parent IN (".$ids_to_clear.")");
    $res = $res && $this->db->query("DELETE FROM ".$sys_tables[$estate_type."_stats_search_full"]." WHERE id_parent IN (".$ids_to_clear.")");
    $res = $res && $this->db->query("DELETE FROM ".$sys_tables[$estate_type."_stats_from_search_full"]." WHERE id_parent IN (".$ids_to_clear.")");
    
}
$log['daily_stats'] = "Чистка статистики объектов которых нет в основных таблицах: ".((!$res)?$this->db->error:"OK")."<br />";
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