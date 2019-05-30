#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/estate_complexes_actualizing.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
//перевод в обычный статус ЖК  просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['housing_estates']." SET `advanced`=2 WHERE (`date_end` <= CURDATE() OR `date_start` > CURDATE()) and advanced=1");
$log['he_normalize'] = "Перевод в обычный статус ЖК  просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
//перевод в расширенный если между дат 
$res = $res && $this->db->query("UPDATE ".$sys_tables['housing_estates']." SET `advanced`=1 WHERE (`date_end` > CURDATE() AND `date_start` <= CURDATE())");
$log['he_advanced'] = "Перевод в расширенный если между дат: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//перевод в обычный статус КП  просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['cottages']." SET `advanced`=2 WHERE (`date_end` <= CURDATE() OR `date_start` > CURDATE()) and advanced=1");
$log['cottages_normalize'] = "Перевод в обычный статус КП  просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
//перевод в расширенный если между дат 
$res = $res && $this->db->query("UPDATE ".$sys_tables['cottages']." SET `advanced`=1 WHERE (`date_end` > CURDATE() AND `date_start` <= CURDATE())");
$log['cottages_advanced'] = "Перевод в расширенный если между дат: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//перевод в обычный статус БЦ просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['business_centers']." SET `advanced`=2 WHERE (`date_end` <= CURDATE() OR `date_start` > CURDATE()) and advanced=1");
$log['bc_normalize'] = "Перевод в обычный статус БЦ просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
//перевод в расширенный если между дат 
$res = $res && $this->db->query("UPDATE ".$sys_tables['business_centers']." SET `advanced`=1 WHERE (`date_end` > CURDATE() AND `date_start` <= CURDATE())");
$log['bc_advanced'] = "Перевод в расширенный если между дат: ".((!$res)?$this->db->error:"OK")."<br />";
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