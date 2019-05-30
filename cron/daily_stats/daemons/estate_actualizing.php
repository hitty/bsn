#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/estate_actualizing.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// Теперь сумма со счета объекта не снимается, только проверяется время 
// раньше было (Снятие суммы со счета   и простановка  объектов в архив)
////////////////////////////////////////////////////////////////////////////////////////////////     

$estate_types = array('live'=>30,'commercial'=>30,'country'=>30,'build'=>60);

foreach($estate_types as $table=>$days) {
    //удаление всех болванок
    //$res = $res && $this->db->query("DELETE FROM  ".$sys_tables[$table]." WHERE published = 5");
    
    //убираем в архив закончившуюся платную аренду
    $res = $res && $this->db->query("UPDATE ".$sys_tables[$table]." SET published = 2, status = 2, status_date_end = '0000-00-00' WHERE published = 1 AND status = 8 AND status_date_end < CURDATE()");
    
    //снимаем закончившееся выделение с объектов
    $res = $res && $this->db->query("UPDATE ".$sys_tables[$table]." SET status = 2, status_date_end = '0000-00-00' WHERE status > 2 AND status_date_end < CURDATE()");
    
    //убираем в архив ОБЫЧНЫЕ объекты у которых истекло 30 дней
    $res = $res && $this->db->query("UPDATE ".$sys_tables[$table]." SET published = 2, status = 2, status_date_end = '0000-00-00' WHERE status = 2 AND published = 1 AND `date_change` < (CURDATE() - INTERVAL ".$days." day)");
    
    //убираем в архив "даленные" из ЛК
    $res = $res && $this->db->query("UPDATE ".$sys_tables[$table]." SET published = 2 WHERE published = 9 AND `date_change` < (CURDATE() - INTERVAL ".$days." day)");
}
$log['finances'] = "Снятие суммы со счета и простановка  объектов в архив: " . ( ( !$res ) ? $this->db->error : "OK" ) . "<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Очистка дневной статистики для объектов, оказавшихся в архиве
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $this->db->query( "UPDATE ".$sys_tables['build']." SET views_count = 0, views_count_week = 0, search_count = 0, from_search_count = 0 WHERE published=2");
$res = $res && $this->db->query( "UPDATE ".$sys_tables['live']." SET views_count = 0, views_count_week = 0, search_count = 0, from_search_count = 0 WHERE published=2");
$res = $res && $this->db->query( "UPDATE ".$sys_tables['commercial']." SET views_count = 0, views_count_week = 0, search_count = 0, from_search_count = 0 WHERE published=2");
$res = $res && $this->db->query( "UPDATE ".$sys_tables['country']." SET views_count = 0, views_count_week = 0, search_count = 0, from_search_count = 0 WHERE published=2");
$log['clear_archive_stats'] = "Очистка дневной статистики для архивных: " . ( ( !$res ) ? $this->db->error : "OK" ) . "<br />";
$res = true;
//-------------------------------------------------------------------
$log = implode( '<br />', $log );


$full_log = ob_clean();
return array(
     'id_action' => $this->getActionInfo( "id" ),
     'datetime_finished' => date( 'Y-m-d H:i:s' ),
     'log' => $log,
     'full_log' => $full_log,
     'db_error' => $this->db->error,
     'result_status' => $this->getStatus( $res )
);

?>