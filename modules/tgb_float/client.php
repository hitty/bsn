<?php
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
switch(true){
    //раскрываем форму
    case $ajax_mode && $action =="show-phone":
        $id_parent = Request::GetInteger('id',METHOD_POST);
        if(empty($id_parent)){
            $ajax_result['ok'] = false;
            break;
        }
        $referer = Host::getRefererURL();
        $ajax_result['ok'] = $db->query("INSERT INTO ".$sys_tables['tgb_float_stats_day_clicks']." SET id_parent = ?, referer = ?",$id_parent,$referer);
        break;
    //принимаем телефон пользователя
    case $ajax_mode && $action =="accept-phone":
        $phone = Request::GetString('phone',METHOD_POST);
        $phone = Convert::ToPhone($phone);
        $id_parent = Request::GetInteger('id',METHOD_POST);
        if(empty($phone) || empty($id_parent)){
            $ajax_result['ok'] = false;
            break;
        }else $phone = "8 ".array_pop($phone);
        
        $referer = Host::getRefererURL();
        $res = $db->query("INSERT INTO ".$sys_tables['tgb_float_phones']." SET id_parent = ?, phone = ?, referer = ?",$id_parent,$phone,$referer);
        
        //читаем id агентства
        $id_agency = $db->fetch("SELECT ".$sys_tables['users'].".id_agency
                                 FROM ".$sys_tables['tgb_float']."
                                 LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['tgb_float'].".id_user = ".$sys_tables['users'].".id
                                 WHERE ".$sys_tables['tgb_float'].".id = ?",$id_parent);
        if(empty($res) || empty($id_agency) || empty($id_agency['id_agency'])){
            $ajax_result['ok'] = false;
            break;
        }else $id_agency = $id_agency['id_agency'];
        
        //читаем информацию по агентству
        $agency_info = $db->fetch("SELECT ".$sys_tables['agencies'].".id AS agency_id, 
                                          ".$sys_tables['agencies'].".title AS agency_title, 
                                          ".$sys_tables['managers'].".email AS manager_email,
                                          ".$sys_tables['managers'].".name AS amanger_name
                                   FROM ".$sys_tables['agencies']."
                                   LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                   WHERE ".$sys_tables['agencies'].".id = ?",$id_agency);
        //оповещаем web@bsn.ru и менеджера компании
        $mailer = new EMailer('mail');
        $mail_text = "Обратный звонок для компании #".$agency_info['agency_id']." ".$agency_info['agency_title'].": <b>".$phone."</b> со страницы <a href='".$referer."' target='_blank'>".$referer."</a>";
        $html = iconv('UTF-8', $mailer->CharSet, $mail_text);
        // параметры письма
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Обратный звонок для компании #".$agency_info['agency_id']." ".$agency_info['agency_title']);
        $mailer->Body = nl2br($html);
        $mailer->AltBody = nl2br($html);
        $mailer->IsHTML(true);
        if(!empty($agency_info['manager_email']) && Validate::isEmail($agency_info['manager_email'])) $mailer->AddAddress($agency_info['manager_email']);
        $mailer->AddAddress('web@bsn.ru');
        $mailer->From = 'bsn_recall@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Обратные звонки на BSN.ru');
        // попытка отправить
        $mailer->Send();        
        $ajax_result['ok'] = $res;
        break;
    //получение баннера: Вертикальный баннер
    case $ajax_mode && empty($action):
    case $ajax_mode && $action == 'left-float-banner':
        $item = $db->fetch("SELECT ".$sys_tables['tgb_float'].".*,
                                   ".$sys_tables['agencies'].".id AS agency_id,
                                   ".$sys_tables['agencies'].".title AS agency_title,
                                   IF(".$sys_tables['agencies'].".phone_2 != '',".$sys_tables['agencies'].".phone_2, ".$sys_tables['agencies'].".phone_1) AS agency_phone
                            FROM ".$sys_tables['tgb_float']."
                            LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['tgb_float'].".id_user = ".$sys_tables['users'].".id
                            LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                            WHERE ".$sys_tables['tgb_float'].".`published` = 1 AND ".$sys_tables['tgb_float'].".`enabled` = 1 AND `date_start` <= CURDATE() AND `date_end` > CURDATE() 
                            ORDER BY RAND()");
        //Вертикальный баннер (баннер)
        if(!empty($item)){
            $ajax_result['ok'] = true;
            if(!Host::$is_bot) $db->query("INSERT INTO ".$sys_tables['tgb_float_stats_day_shows']." SET id_parent = ?", $item['id']);
            
            //подставляем цвета по умолчанию
            if(empty($item['top_color'])) $item['top_color'] = $mapping['banners']['top_color']['default'];
            if(empty($item['background_color'])) $item['background_color'] = $mapping['banners']['background_color']['default'];
            if(empty($item['button_color'])) $item['button_color'] = $mapping['banners']['button_color']['default'];
            
            Response::SetArray('item', $item);
            $module_template = 'block.html';
        }  else $module_template = '/templates/clearcontent.html';
        break;
    // запись статистики клика
    case $ajax_mode && $action=='click': 
        $id = Request::GetInteger('id',METHOD_POST);
        if($id>0){
            if(!Host::$is_bot && !Host::isBsn('tgb_float_stats_day_clicks',$id)) $res=$db->query("INSERT INTO ".$sys_tables['tgb_float_stats_day_clicks']." SET `id_parent`=".$id);
            $ajax_result['ok'] = $res;
        }
        break;
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}
?>