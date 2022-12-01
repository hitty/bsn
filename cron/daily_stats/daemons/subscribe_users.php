#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/subscribe_users.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
//----------- СТАТИСТИКА ПОДПИСАВШИХСЯ И ОТПИСАВШИХСЯ ПОЛЬЗОВАТЕЛЕЙ

$res = $res && $this->db->querys("INSERT INTO ".$sys_tables['subscribed_users_stats']." (subscribed, date, unsubscribed)
SELECT subscribed, s.date, unsubscribed FROM ( 
           SELECT SUM(ss.cnt) as subscribed, ss.date FROM (
               (SELECT COUNT(*) as cnt, CURDATE() - INTERVAL 1 DAY AS date FROM ".$sys_tables['subscribed_users']." WHERE published=1) 
               UNION 
               (SELECT COUNT(*) as cnt, CURDATE() - INTERVAL 1 DAY AS date FROM ".$sys_tables['users']." WHERE subscribe_news = 1) 
           )  ss
) s 
LEFT JOIN (
           SELECT SUM(kk.cnt) as unsubscribed, kk.date FROM (
               (SELECT COUNT(*) as cnt, CURDATE() - INTERVAL 1 DAY AS date FROM ".$sys_tables['subscribed_users']." WHERE published = 2) 
               UNION 
               (SELECT COUNT(*) as cnt, CURDATE() - INTERVAL 1 DAY AS date FROM ".$sys_tables['users']." WHERE subscribe_news = 2) 
           ) kk
) k ON s.date = k.date" );
$log['subs_unsubs_stats'] = "Статистика подписавшихся и отписавшихся пользователей: ".((!$res)?$this->db->error:"OK")."<br />";
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