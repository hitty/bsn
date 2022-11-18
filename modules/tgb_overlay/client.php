<?php
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов

//1 - Количество расхлопываний 2 - Количество переходов по ссылке с картинки\текста  3 - Количество отправок контактной информации 
switch(true){
    case empty($action):
    case $action == 'left':
        if($ajax_mode){
            //получение баннера: Overlay баннер
            $item = $db->fetch("SELECT * FROM ".$sys_tables['tgb_overlay']." WHERE `published` = ? AND `enabled` = ? AND `date_start` <= CURDATE() AND `date_end` > CURDATE() ORDER BY RAND()",
                                1, 1
            );
            //Overlay баннер (баннер)
            if(!empty($item)){
                $ajax_result['ok'] = true;
                if(!Host::$is_bot) $db->querys("INSERT INTO ".$sys_tables['tgb_overlay_stats_day_shows']." SET id_parent = ?", $item['id']);
                $item['img_folder'] = Config::$values['img_folders']['tgb_overlay'];
                Response::SetArray('item', $item);
                $module_template = 'block.html';
            }  else $module_template = '/templates/clearcontent.html';
        } else $this_page->http_code = 404;
        break;

    case $action=='click': // запись статистики клика
        if($ajax_mode){
            $id = Request::GetInteger('id',METHOD_POST);
            $action = !empty($this_page->page_parameters[1]) ?  $this_page->page_parameters[1] : false;
            
            if($id>0){
                switch(true){
                    case  $action == 'expand':
                        $type = 1;
                        break;
                    case empty($action):
                        $type = 2;
                        break;
                    case $action == 'phone':
                        $type = 3;
                        break;
                }                
                if(!Host::$is_bot) $res = $db->querys("INSERT INTO ".$sys_tables['tgb_overlay_stats_day_clicks']." SET `id_parent` = ? , type = ?, ip = ?, browser = ?, ref = ?",
                                                        $id, $type, Host::getUserIp(),$db->real_escape_string($_SERVER['HTTP_USER_AGENT']),Host::getRefererURL()
                );
                $ajax_result['ok'] = $res;
            }
        } else $this_page->http_code=404;
        break;
    case $action=='add': // отправка телефона
            $id = !empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1])?  $this_page->page_parameters[1] : false;
            if(!empty($id)) {
                if(!Host::$is_bot) {
                    $res = $db->querys("INSERT INTO ".$sys_tables['tgb_overlay_stats_day_clicks']." SET `id_parent` = ? , type = ?", $id, 3);
                    
                    $phone = Request::GetString('tgb-overlay-phone',METHOD_POST);
                    if(!empty($phone)) {
                        $res = $db->querys("INSERT INTO ".$sys_tables['tgb_overlay_phones']." SET `id_parent` = ? , phone = ?, ip = ?, browser = ?, ref = ?",
                                    $id, $phone, Host::getUserIp(),$db->real_escape_string($_SERVER['HTTP_USER_AGENT']),Host::getRefererURL()
                        );    
                        
                    // отправка кода на мыло
                    $mailer = new EMailer('mail');
                    $confirm_code = substr(md5(time()),-6);// данные пользователя для шаблона
                    // данные окружения для шаблона
                    $item = $db->fetch("SELECT * FROM " . $sys_tables['tgb_overlay_phones']. " WHERE id = ?", $db->insert_id);
                    Response::SetArray('item', $item);
                    // инициализация шаблонизатора
                    $eml_tpl = new Template('mail.add.html', 'modules/tgb_overlay/');
                    // формирование html-кода письма по шаблону
                    $html = $eml_tpl->Processing();
                    // перевод письма в кодировку мейлера
                    $html = iconv('UTF-8', $mailer->CharSet, $html);
                    // параметры письма
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Новая заявка по формату Overlay');
                    $mailer->Body = $html;
                    $mailer->AltBody = strip_tags($html);
                    $mailer->IsHTML(true);
                    $mailer->AddAddress('d.salova@bsn.ru');
                    $mailer->AddAddress('marina@bsn.ru');
                    $mailer->From = 'no-reply@bsn.ru';
                    $mailer->FromName = 'bsn.ru';
                    // попытка отправить
                    $ajax_result['ok'] = $mailer->Send();
                    $module_template = "success.html";
                    }
                }
                
            }
        break;
        
    default:
        $module_template = '/templates/clearcontent.html';
        break;
}
?>