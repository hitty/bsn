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
 
//-------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////
// за 3 дня до окончания тарифа агентства оповещаем компанию и ответственного менеджера, 
// круглое число месяцев с назначения тарифа - списание с баланса
////////////////////////////////////////////////////////////////////////////////////////////////
$agencies_list = $this->db->fetchall("SELECT ".$sys_tables['agencies'].".id_tarif,
                                       ".$sys_tables['agencies'].".tarif_start,
                                       ".$sys_tables['agencies'].".tarif_end,
                                        DATE_FORMAT(".$sys_tables['agencies'].".tarif_end,'%e %M %Y') AS formatted_actualized_tarif_end,
                                       ".$sys_tables['agencies'].".tarif_cost,
                                       ".$sys_tables['tarifs_agencies'].".title AS tarif_title,
                                       ".$sys_tables['agencies'].".title AS agency_title,
                                       ".$sys_tables['agencies'].".id AS agency_id,
                                       ".$sys_tables['agencies'].".email AS agency_email,
                                       ".$sys_tables['agencies'].".tarif_expenditures,
                                       ".$sys_tables['managers'].".name AS manager_name,
                                       ".$sys_tables['managers'].".email AS manager_email,
                                       ".$sys_tables['users'].".id as id_user,
                                       ".$sys_tables['users'].".name as user_name,
                                       ".$sys_tables['users'].".lastname as user_lastname, 
                                       ".$sys_tables['users'].".email as user_email,
                                       IF(DATEDIFF(str_to_date( CONCAT(DATE_FORMAT(NOW(),'%Y-%m-'),DATE_FORMAT(str_to_date(".$sys_tables['agencies'].".`tarif_start`,'%Y-%m-%d'),'%d')),'%Y-%m-%d' ),NOW()) = 3,1,0) AS 3_before_end,
                                       IF(DATEDIFF(str_to_date( CONCAT(DATE_FORMAT(NOW(),'%Y-%m-'),DATE_FORMAT(str_to_date(".$sys_tables['agencies'].".`tarif_start`,'%Y-%m-%d'),'%d')),'%Y-%m-%d' ),NOW()) = 0
                                          AND ABS(DATEDIFF(".$sys_tables['agencies'].".tarif_start,DATE_FORMAT(NOW(),'%Y-%m-%d'))) >= 30,1,0) AS tarif_ends
                                FROM ".$sys_tables['agencies']."
                                LEFT JOIN ".$sys_tables['tarifs_agencies']." ON ".$sys_tables['agencies'].".id_tarif = ".$sys_tables['tarifs_agencies'].".id
                                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                                RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id AND ".$sys_tables['users'].".agency_admin = 1
                                WHERE ".$sys_tables['agencies'].".id_tarif > 0 AND  ".$sys_tables['agencies'].".tarif_expenditures = 1 AND
                                      ".$sys_tables['agencies'].".tarif_start NOT LIKE '%000%' AND 
                                      common.agencies.`tarif_end`<=  CURDATE() AND common.agencies.`tarif_end`+ INTERVAL 3 DAY >= CURDATE()
                                      ");
foreach($agencies_list as $k=>$item){
    //оповещаем или делаем списание
    if(empty($item['tarif_ends']) && !empty($item['3_before_end'])){
        //оповещаем менеджера
        Response::SetArray('item', $item);
        require_once('includes/class.template.php');
        $eml_tpl = new Template('mail.tarif.manager_near_ending.html', 'cron/daily_stats/');
        $mailer = new EMailer('mail');
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        $mailer->Body = $html;
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Приближается срок окончания тарифа агентства ".$item['agency_title']." на BSN.ru");
        $mailer->IsHTML(true);
        $mailer->AddAddress($item['manager_email']);
        $mailer->AddAddress("web@bsn.ru");
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
        $res = $res && $mailer->Send();
        
        //опопвещаем агентство
        Response::SetArray('item', $item);
        $eml_tpl = new Template('mail.tarif.agency_near_ending.html', 'cron/daily_stats/');
        $mailer = new EMailer('mail');
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        $mailer->Body = $html;
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $item['user_name'].", приближается срок окончания тарифа агентства ".$item['agency_title']." на BSN.ru");
        $mailer->IsHTML(true);
        if(Validate::isEmail($item['user_email'])) $mailer->AddAddress($item['user_email']);
        $mailer->AddAddress("web@bsn.ru");
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
        $res = $res && $mailer->Send();
    }elseif(!empty($item['tarif_ends'])){
        //делаем списание за тариф
        $res = $res && $this->db->query("INSERT INTO ".$sys_tables['users_finances']." (id_user,obj_type,estate_type,id_parent,expenditure,income) VALUES (?,'tarif','',1,?,0)",$item['id_user'],$item['tarif_cost']);
        $res = $res && $this->db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?",$item['tarif_cost'],$item['id_user']);
    }
}
$log['agencies_ending_notifications'] = "Оповещения за 3 дня о приближении окончания тарифов агентств, списания по тарифам агентств: ".((!$res)?$this->db->error:"OK")."<br />";
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