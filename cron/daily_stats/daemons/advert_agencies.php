#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;
$db = $this->db;

//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/advert_agencies.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

require_once('cron/robot/robot_functions.php');    // функции  из крона

//-------------------------------------------------------------------
//---------- СТАТИСТИКА РЕКЛАМНЫХ АГЕНТСТВ  ----------------------
$adv_agencies = $this->db->fetchall("SELECT u.id as id_user FROM ".$sys_tables['users']." u
                      LEFT JOIN ".$sys_tables['agencies']." a ON a.id=u.id_agency 
                      WHERE a.activity & 2 AND a.`id`!=4472"); //выборка всех кроме недвижимости города
if(!empty($adv_agencies)){
  foreach($adv_agencies as $k => $agency){
    echo "processing agency with admin #".$agency['id_user']."\r\n";
    $res = $res && $this->db->query("INSERT IGNORE INTO ".$sys_tables['billing']." (external_id, bsn_id, date, type, bsn_id_user, status, adv_agency)
                SELECT external_id, bsn_id, date, type, bsn_id_user, status, 1 FROM
                (
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'live' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['live']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']." AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'build' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['build']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']." AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'commercial' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['commercial']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']." AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'country' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['country']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']." AND info_source > 1 AND info_source != 4
                 ) as a
        ");
    }
    $log['aa_stats'] = "Статистика рекламных агентств: ".((!$res)?$this->db->error:"OK")."<br />";
    $res = true;
}

//---------- СТАТИСТИКА РЕКЛАМНЫХ АГЕНТСТВ  ----------------------
$adv_agencies = $this->db->fetchall("SELECT u.id as id_user, a.id as id_agency FROM ".$sys_tables['users']." u
                      LEFT JOIN ".$sys_tables['agencies']." a ON a.id=u.id_agency 
                      WHERE a.activity & 2 AND a.`id`!=4472"); //выборка всех кроме недвижимости города
                      
//---------- СТАТИСТИКА ОСТАЛЬНЫХ АГЕНТСТВ У КОТОРЫХ ЕСТЬ ВЫДЕЛЕННЫЕ СТРОКИ   (кроме Н-Маркета и Индустрии)----------------------
$ids = array();
foreach($adv_agencies as $k=>$value) {
    $ids[] = $value['id_agency'];
}
$agencies = $this->db->fetchall("SELECT u.id as id_user FROM ".$sys_tables['users']." u
                      LEFT JOIN ".$sys_tables['agencies']." a ON a.id=u.id_agency 
                      WHERE a.id NOT IN(".implode(',',$ids).") AND a.`id`!=4472  AND a.id>1"); //выборка всех кроме недвижимости города
if(!empty($agencies)){
  foreach($agencies as $k => $agency){
    echo "processing agency with admin #".$agency['id_user']."\r\n";
    $res = $res && $this->db->query("INSERT IGNORE INTO ".$sys_tables['billing']." (external_id, bsn_id, date, type, bsn_id_user, status, adv_agency)
                SELECT external_id, bsn_id, date, type, bsn_id_user, status, 2  FROM
                (
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'live' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['live']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND (status > 2 OR elite=1)  AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'live' as type, `id_user` as bsn_id_user, 99 FROM ".$sys_tables['live']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND status = 2 AND elite = 2  AND info_source > 1 AND info_source != 4 AND rent = 1
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'build' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['build']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND (status > 2 OR elite=1)  AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'commercial' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['commercial']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND (status > 2 OR elite=1)  AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'country' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['country']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND (status > 2 OR elite=1)  AND info_source > 1 AND info_source != 4
                 ) as a
        ");
    }
    $log['aa_stats'] = "Статистика остальных рекламных агентств, у которых есть выделенные строки: ".((!$res)?$this->db->error:"OK")."<br />";
    $res = true;
}

//---------- КОРРЕКТИРОВКА СТАТИСТИКИ JCAT  ----------------------
//теперь отдельно плюсуем значения, потому что считается максимум за сутки, а не то что к вечеру как у всех
$jcat_max_values = $this->db->fetch("SELECT MAX(live_rent) + MAX(live_sell) AS live,
                                      MAX(live_rent_promo) + MAX(live_sell_promo) AS live_promo,
                                      MAX(live_rent_premium) + MAX(live_sell_premium) AS live_premium,
                                      MAX(live_rent_vip) + MAX(live_sell_vip) AS live_vip,
                                      MAX(build) AS build_sell,
                                      MAX(build_promo) AS build_sell_promo,
                                      MAX(build_premium) AS build_sell_premium,
                                      MAX(build_vip) AS build_sell_vip,
                                      MAX(commercial_rent) + MAX(commercial_sell) AS commercial,
                                      MAX(commercial_rent_promo) + MAX(commercial_sell_promo) AS commercial_promo,
                                      MAX(commercial_rent_premium) + MAX(commercial_sell_premium) AS commercial_premium,
                                      MAX(commercial_rent_vip) + MAX(commercial_sell_vip) AS commercial_vip,
                                      MAX(country_rent) + MAX(country_sell) AS country,
                                      MAX(country_rent_promo) + MAX(country_sell_promo) AS country_promo,
                                      MAX(country_rent_premium) + MAX(country_sell_premium) AS country_premium,
                                      MAX(country_rent_vip) + MAX(country_sell_vip) AS country_vip
                              FROM ".$sys_tables['processes']."
                              WHERE id_agency = 4467 AND DATEDIFF(NOW(),datetime_start) = 1
                              GROUP BY id_agency");
//смотрим биллинг по JCAT и по необходимости добиваем значения
$jcat_id_user = $this->db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id_agency = 4467 AND agency_admin = 1");
$jcat_id_user = (empty($jcat_id_user)?0:$jcat_id_user['id']);
$estate_types = array('live','commercial','country','build');
if(!empty($jcat_id_user)){
    echo "updating JCAT billing\r\n";
    //читаем то что в биллинге
    $jcat_billing = $this->db->fetchall("SELECT CONCAT(type,'_',status) AS type,COUNT(*) AS amount
                                   FROM ".$sys_tables['billing']."
                                   WHERE DATEDIFF(NOW(),`date`) = 1 AND bsn_id_user = ?
                                   GROUP BY type,status",'type',$jcat_id_user);
    foreach($estate_types as $key=>$estate_type){
        //смотрим максимальные значения по процессам
        $jcat_max_sum = array();
        $jcat_max_sum[$estate_type.'_2'] = $jcat_max_values[$estate_type];
        $jcat_max_sum[$estate_type.'_3'] = $jcat_max_values[$estate_type."_promo"];
        $jcat_max_sum[$estate_type.'_4'] = $jcat_max_values[$estate_type."_premium"];
        $jcat_max_sum[$estate_type.'_6'] = $jcat_max_values[$estate_type."_vip"];
        
        //добавляем в биллинг строчки
        foreach($jcat_max_sum as $key=>$value){
            $jcat_billing[$key] = (empty($jcat_billing[$key]['amount']) ? 0 : $jcat_billing[$key]['amount']);
            $date = new DateTime();
            $date->sub(new DateInterval('P1D'));
            $k = 0;
            //не более 20 неудачных попыток встваить строку, чтобы не было затыков
            while($jcat_billing[$key] < $value && $k < 20){
                if(isnertLineIntoBilling($jcat_id_user, $date->format('Y-m-d')." 00:00:00",$estate_type, preg_replace('/[^0-9]/','',$key),$this->db))
                    ++$jcat_billing[$key];
                else ++$k;
            }
        }
    }
}
$log['jcat_billing'] = "Корректировка биллинга JCAT: ".((!$res)?$this->db->error:"OK")."<br />";
unset($jcat_max_values);
unset($jcat_max_sum);
unset($jcat_billing);
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