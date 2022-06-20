#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/esate_views_shows_search.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// Перенос просмотров, попаданий в результаты поиска и переходов с поиска в соответствующие таблицы
////////////////////////////////////////////////////////////////////////////////////////////////     
$estate_types = array('live','commercial','country','build');
foreach($estate_types as $key=>$item){
    //просмотры карточек
    $res = $res && $this->db->query("INSERT INTO ".$sys_tables[$item.'_stats_show_full']." (id_user, id_parent, amount, `date`)
                                SELECT id_user, id, views_count AS amount, (CURDATE() - INTERVAL 1 DAY) AS `date`
                                FROM ".$sys_tables[$item]."
                                WHERE published = 1 AND views_count>0
                                GROUP BY ".$sys_tables[$item].".id");
    //накапливаем недельные просмотры. если наступил понедельник - стираем их
    if(date('w') == 1) $res = $res && $this->db->query("UPDATE ".$sys_tables[$item]." SET views_count_week=0 WHERE published=1");
    else $res = $res && $this->db->query("UPDATE ".$sys_tables[$item]." SET views_count_week=views_count+views_count_week WHERE published=1");
    $res = $res && $this->db->query("UPDATE ".$sys_tables[$item]." SET views_count=0 WHERE published=1");
    
    //попаданий в поиск
    $res = $res && $this->db->query("INSERT INTO ".$sys_tables[$item.'_stats_search_full']." (id_user, id_parent, amount, `date`)
                                SELECT id_user, id, search_count AS amount, (CURDATE() - INTERVAL 1 DAY) AS `date`
                                FROM ".$sys_tables[$item]."
                                WHERE published = 1 AND search_count>0
                                GROUP BY ".$sys_tables[$item].".id");
    $res = $res && $this->db->query("UPDATE ".$sys_tables[$item]." SET search_count=0 WHERE published=1");
    
    //переходов с поиска
    $res = $res && $this->db->query("INSERT INTO ".$sys_tables[$item.'_stats_from_search_full']." (id_user, id_parent, amount, `date`)
                                SELECT id_user, id, from_search_count AS amount, (CURDATE() - INTERVAL 1 DAY) AS `date`
                                FROM ".$sys_tables[$item]."
                                WHERE published = 1 AND from_search_count>0
                                GROUP BY ".$sys_tables[$item].".id");
    $res = $res && $this->db->query("UPDATE ".$sys_tables[$item]." SET from_search_count=0 WHERE published=1");
}
$log['daily_views'] = "Запись просмотров, попаданий в результаты поиска и переходов с поиска: ".((!$res)?$this->db->error:"OK")."<br />";
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