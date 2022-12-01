#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/crawlers.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// Переносим статистику роботов из суточной в общую
////////////////////////////////////////////////////////////////////////////////////////////////

$crawlers = Config::$values['crawlers_aliases'];
foreach($crawlers as $key=>$item){
    $res = $res && $this->db->querys("INSERT INTO ".$sys_tables['pages_visits_'.$item.'_full']." (`date`,visits_amount,links_shown,old_pages_visits,pages_added) VALUES
                               (CURDATE() - INTERVAL 1 DAY,
                               (SELECT COUNT(*) AS visits_amount FROM  ".$sys_tables['pages_visits_'.$item.'_day']."),
                               (SELECT SUM(shown_today) AS links_shown FROM ".$sys_tables['pages_not_indexed_'.$item]."),
                               (SELECT COUNT(*) AS old_pages_visits 
                                FROM ".$sys_tables['pages_visits_'.$item.'_day']."
                                LEFT JOIN ".$sys_tables['pages_not_indexed_'.$item]." ON ".$sys_tables['pages_visits_'.$item.'_day'].".id_page_in_stack = ".$sys_tables['pages_not_indexed_'.$item].".id
                                WHERE DATEDIFF(NOW(),date_out) = 1 AND bot_visits_total > 1),
                               (SELECT COUNT(*) AS pages_added FROM ".$sys_tables['pages_not_indexed_'.$item]." WHERE DATEDIFF(NOW(),date_out) = 1))");
    $res = $res && $this->db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET shown_today = 0");
    $res = $res && $this->db->querys("TRUNCATE ".$sys_tables['pages_visits_'.$item.'_day']);
    
    //раз в месяц чистим переходы с поиска и показанные страницы
    if(date('j') == 1){
        $this->db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET googletm = '0000-00-00 00:00:00' WHERE DATEDIFF(NOW(),googletm)>30");
        $this->db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET yandextm = '0000-00-00 00:00:00' WHERE DATEDIFF(NOW(),yandextm)>30");
        $this->db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET mailrutm = '0000-00-00 00:00:00' WHERE DATEDIFF(NOW(),mailrutm)>30");
        $this->db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET has_shown = 0");
    }
}
//$res = $res && $this->db->querys("INSERT INTO ".);
$log['apps_archive'] = "Статистика поисковых роботов: ".((!$res)?$this->db->error:"OK")."<br />";
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