#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/banners_actualizing.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
//снятие актуальности с баннеров просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['banners']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['banners_arch'] = "Снятие актуальности с баннеров - Баннеры адривера просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//-------------------------------------------------------------------
//снятие актуальности с баннеров - Спонсор района просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['district_banners']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['sponsor_arch'] = "Снятие актуальности с баннеров - Спонсор района просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//снятие актуальности с баннеров - вертикальные просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['tgb_vertical']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['vertical_arch'] = "Снятие актуальности с баннеров - вертикальные просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//снятие актуальности с баннеров - float с обратным звонком просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['tgb_float']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['vertical_arch'] = "Снятие актуальности с баннеров - float с обратным звонком просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//снятие актуальности с б кредитных калькуляторов просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['credit_calculator']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['cc_arch'] = "Снятие актуальности с кредитных калькуляторов просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//снятие актуальности с б кредитных калькуляторов просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['tgb_overlay']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['overlay_arch'] = "Снятие актуальности с overlay баннеров просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//обновление времени кредитного клика для попандеровских кликов
$res = $res && $this->db->query("UPDATE ".$sys_tables['tgb_banners']." SET `credit_time` = '00:00:00'");
$log['tgb_credit_time'] = "Обновление времени кредитного клика для попандеровских кликов: ".((!$res)?$this->db->error:"OK")."<br />";
$res = true;

//снятие актуальности с ТГБ просрочивших дату показа
$res = $res && $this->db->query("UPDATE ".$sys_tables['tgb_banners']." SET `enabled`=2, `published`=2, `clicks_limit` = 0, `credit_clicks` = 2, clicks_limit_notification = 1 WHERE `date_end` <= CURDATE() and enabled=1");
$log['tgb_arch'] = "Снятие актуальности с ТГБ просрочивших дату показа: ".((!$res)?$this->db->error:"OK")."<br />";
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