#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/agencies_tariffs.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

$db = $this->db;

//*******************************************************************
//    Отключение подставных телефонов
//*******************************************************************
$list = $this->db->query( " UPDATE " . $sys_tables['agencies'] . " SET advert_phone = '' WHERE advert_phone_date_end < CURDATE() AND advert_phone_date_end != '0000-00-00' " );

//-------------------------------------------------------------------
//---------- Окончания срока действия тарифа у агентств ----------------------
//читаем список агентств, у которых заканчивается тариф
$list = $this->db->fetchall("SELECT 
                        ".$sys_tables['agencies'].".id,
                        CONCAT('компания ',".$sys_tables['agencies'].".title,' (#',".$sys_tables['agencies'].".id,')' ) as titles,
                        ".$sys_tables['agencies'].".email,
                        ".$sys_tables['agencies'].".email_service,
                        ".$sys_tables['agencies'].".business_center,
                        ".$sys_tables['agencies'].".tarif_expenditures,
                        ".$sys_tables['users'].".email as user_email,
                        ".$sys_tables['users'].".id as id_user,
                        IF(".$sys_tables['agencies'].".id_tarif = 1,".$sys_tables['agencies'].".tarif_cost,".$sys_tables['tarifs_agencies'].".cost) AS tarif_cost
                     FROM ".$sys_tables['agencies']."
                     LEFT JOIN ".$sys_tables['tarifs_agencies']." ON ".$sys_tables['agencies'].".id_tarif = ".$sys_tables['tarifs_agencies'].".id
                     RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id AND ".$sys_tables['users'].".agency_admin = 1
                     LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                     WHERE ".$sys_tables['agencies'].".id_tarif > 0 AND ".$sys_tables['agencies'].".`tarif_end`<=CURDATE()
                     GROUP BY ".$sys_tables['agencies'].".id");
if(!empty($list))
    foreach($list as $k=>$item){
        //снятие актуальности с офисов БЦ
        if($item['business_center'] == 1){
            //список всех офисов БЦ
              require_once('includes/class.business_centers.php');
              $business_center = new BusinessCenters();
              $bc = $business_center->getLevelsList(100, $sys_tables['business_centers'].".id_user = ".$item['id_user'], false, $sys_tables['business_centers_levels'].".id_parent",$this->db);
              if(!empty($bc)){
                  $ids = array();
                  foreach($bc as $k=>$item) $ids[] = $item['id'];
                  $this->db->query("UPDATE ".$sys_tables['business_centers_offices']." SET id_renter = 0, status = 2, date_rent_start = '0000-00-00', date_rent_start = '0000-00-00' WHERE id_parent IN (".implode(", ", $ids).")");
              }
        }
    }
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