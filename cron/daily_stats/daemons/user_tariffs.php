#!/usr/bin/php
<?php

error_reporting(E_ALL);
$sys_tables = $this->sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/daemons/user_tariffs.log';
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//-------------------------------------------------------------------
//ТАРИФ пользователя
//автопродление
$tarif_renewal = $this->db->fetchall("SELECT ".$sys_tables['users'].".*,
                                        DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%e.%m.%y') as renewal_date,
                                        ".$sys_tables['tarifs'].".id AS id_tarif,
                                        ".$sys_tables['tarifs'].".title,
                                        ".$sys_tables['tarifs'].".cost,
                                        ".$sys_tables['tarifs'].".premium_available,
                                        ".$sys_tables['tarifs'].".promo_available,
                                        ".$sys_tables['tarifs'].".vip_available,
                                        ".$sys_tables['tarifs'].".payed_page
                                 FROM ".$sys_tables['users']."
                                 LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users'].".id_tarif
                                 WHERE ".$sys_tables['users'].".id_tarif > 0 AND 
                                        `tarif_end`<=CURDATE() AND 
                                       ".$sys_tables['users'].".tarif_renewal = 1 AND
                                       ".$sys_tables['users'].".balance >= ".$sys_tables['tarifs'].".cost 
                                        "
);
if(!empty($tarif_renewal)) {
    foreach($tarif_renewal as $k=>$item){
        
        //вписываем данные в финансы
        $this->db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ?, promo_left = ?, premium_left = ?, vip_left = ?, 
                                                    tarif_start = NOW(), tarif_end = CURDATE() + INTERVAL 1 MONTH, 
                                                    payed_page = ".$item['payed_page'].", id_user_type = 2 WHERE id = ?",
                    $item['cost'], $item['promo_available'], $item['premium_available'], $item['vip_available'], $item['id']);
        //запись в финансы
        $this->db->query("INSERT INTO ".$sys_tables['users_finances']." SET expenditure = ?, id_user = ?, obj_type = ?, id_parent = ?", 
                    $item['cost'], $item['id'], 'tarif', $item['id_tarif']);
        
        //отправка письма пользователю
        if(!empty($item['email']) && Validate::isEmail($item['email'])){
            Response::SetArray('item', $item);
            $eml_tpl = new Template('mail.tarif.renewal.html', 'cron/daily_stats/');
            $mailer = new EMailer('mail');
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Автопродление тарифа специалиста на BSN.ru");
            $mailer->IsHTML(true);
            $mailer->AddAddress($item['email']);
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
            // попытка отправить
            $mailer->Send();
        }
    }
}
//снятие актуальности с тарифа пользователя
//читаем список пользователей, у которых заканчивается тариф
$users_endtarif = $this->db->fetchall("SELECT ".$sys_tables['users'].".id
                                 FROM ".$sys_tables['users']."
                                 WHERE ".$sys_tables['users'].".id_tarif > 0 AND `tarif_end`<=CURDATE()",'id');
if(!empty($users_endtarif)){
    $users_endtarif = implode(',',array_keys($users_endtarif));
    //список пользователей для отчета
    $users_titles = $this->db->fetch("SELECT GROUP_CONCAT( IF(".$sys_tables['agencies'].".title IS NULL,
                                                        CONCAT('пользователь #',".$sys_tables['users'].".id),
                                                        CONCAT('компания ',".$sys_tables['agencies'].".title,' (#',".$sys_tables['users'].".id,')' )) 
                                                    ) AS titles
                                   FROM ".$sys_tables['users']."
                                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                   WHERE ".$sys_tables['users'].".id IN (".$users_endtarif.")")['titles'];
    //убираем тариф у пользователя, не возвращаем ему тип "пользователь", убираем флаг платной страницы
    $res = $res && $this->db->query("UPDATE ".$sys_tables['users']." SET `id_tarif`=0, `promo_left`=0, `premium_left`=0, `vip_left` = 0, tarif_start = '0000-00-00', tarif_end = '0000-00-00', payed_page = 2 WHERE id IN (".$users_endtarif.")");
    //все тарифные объекты в архив (если статус оплачен - не трогаем)                               
    $res = $res && $this->db->query("UPDATE ".$sys_tables['build']." SET published = 2, status = 2, status_date_end = '0000-00-00' WHERE id_user IN (".$users_endtarif.") AND payed_status = 2");
    $res = $res && $this->db->query("UPDATE ".$sys_tables['live']." SET published = 2, status = 2,status_date_end = '0000-00-00' WHERE id_user IN (".$users_endtarif.") AND payed_status = 2");
    $res = $res && $this->db->query("UPDATE ".$sys_tables['commercial']." SET published = 2, status = 2,status_date_end = '0000-00-00' WHERE id_user IN (".$users_endtarif.") AND payed_status = 2");
    $res = $res && $this->db->query("UPDATE ".$sys_tables['country']." SET published = 2, status = 2,status_date_end = '0000-00-00' WHERE id_user IN (".$users_endtarif.") AND payed_status = 2");
    $users_emails = $this->db->fetchall("SELECT ".$sys_tables['users'].".email,
                                            ".$sys_tables['tarifs'].".title
                                     FROM ".$sys_tables['users']."
                                     LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users'].".id_tarif
                                     WHERE ".$sys_tables['users'].".id IN (".$users_endtarif.")"
    );
    if(!empty($users_emails)){
        foreach($users_emails as $k=>$item){
            //отправка письма пользователю
            if(!empty($item['email']) && Validate::isEmail($item['email'])){
                Response::SetArray('item', $item);
                $eml_tpl = new Template('mail.tarif.end.html', 'cron/daily_stats/');
                $mailer = new EMailer('mail');
                $html = $eml_tpl->Processing();
                $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);                                                 
                // параметры письма
                $mailer->Body = $html;
                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Действие тарифа BSN.ru приостановлено");
                $mailer->IsHTML(true);
                $mailer->AddAddress($item['email']);
                $mailer->From = 'no-reply@bsn.ru';
                $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
                // попытка отправить
                $mailer->Send();
            }
        }
    }

}
$log['tarif_arch'] = "Снятие актуальности с тарифа пользователя: ".((!$res)?$this->db->error:"OK")."<br />";
if(!empty($users_endtarif)) $log['tarif_arch_users'] = "Закончился тариф и ушли в архив объекты у пользователей: ".$users_titles.".<br />";
$res = true;
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