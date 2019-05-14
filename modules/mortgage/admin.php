<?php
  $GLOBALS['js_set'][] = '/modules/business_centers/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.messages.php');
require_once("includes/class.credit_calculator.php");

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$messages = new Messages();

$this_page->manageMetadata(array('title'=>'Заявки на ипотеку'));
        
// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
$filters['estate_type'] = Request::GetInteger('f_estate_type',METHOD_GET);
$filters['rent'] = Request::GetInteger('f_rent',METHOD_GET);
$filters['app_id'] = Request::GetInteger('f_app_id',METHOD_GET);
$filters['apper_id'] = Request::GetInteger('f_apper_id',METHOD_GET);
$filters['object_id'] = Request::GetInteger('f_object_id',METHOD_GET);
$filters['agency'] = Request::GetInteger('f_agency',METHOD_GET);
$filters['date_start'] = Request::GetString('f_date_start',METHOD_GET);
$filters['date_end'] = Request::GetString('f_date_end',METHOD_GET);
$filters['moder_date_start'] = Request::GetString('f_moder_date_start',METHOD_GET);
$filters['moder_date_end'] = Request::GetString('f_moder_date_end',METHOD_GET);

if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status'];
if(!empty($filters['estate_type'])) $get_parameters['f_estate_type'] = $filters['estate_type'];
if(!empty($filters['rent'])) $get_parameters['f_rent'] = $filters['rent'];
if(!empty($filters['app_id'])) $get_parameters['f_app_id'] = $filters['app_id'];
if(!empty($filters['apper_id'])) $get_parameters['f_apper_id'] = $filters['apper_id'];
if(!empty($filters['object_id'])) $get_parameters['f_object_id'] = $filters['object_id'];
if(!empty($filters['agency'])) $get_parameters['f_agency'] = $filters['agency'];
if(!empty($filters['date_start'])) $get_parameters['f_date_start'] = $filters['date_start'];
if(!empty($filters['date_end'])) $get_parameters['f_date_end'] = $filters['date_end'];
if(!empty($filters['moder_date_start'])) $get_parameters['f_moder_date_start'] = $filters['moder_date_start'];
if(!empty($filters['moder_date_end'])) $get_parameters['f_moder_date_end'] = $filters['moder_date_end'];

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;

// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
$ajax_action = Request::GetString('action', METHOD_POST);
if(!empty($ajax_action)) $action  = $ajax_action;
$ajax_action = Request::GetString('action', METHOD_POST);

$banks_condition = "activity & ".pow(2,4)." AND estate_types & ".pow(2,8)." AND mortgage_applications_accepting < 5";
// обработка action-ов
switch(true){
    //просмотр ответов банков
    case $action == 'show_recieve_info':
        $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
        switch(true){
            //изменение статуса ответа банка
            case $action == 'save-mortgage_app-status' && $ajax_mode:
                $id = Request::GetInteger('id',METHOD_POST);
                $id_bank = Request::GetInteger('id_bank',METHOD_POST);
                $status = Request::GetInteger('status',METHOD_POST);
                
                //обновляем статус:
                $res = $db->query("UPDATE ".$sys_tables['mortgage_applications_recievers']." SET status = ? WHERE id_bank = ? AND id_application = ?",$status,$id_bank,$id);
                $id_admin = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE agency_admin = 1 AND id_agency = ?",$id_bank);
                $id_admin = (empty($id_admin) || empty($id_admin['id']) ? 0 : $id_admin['id']);
                
                //если банк отклонил заявку, делаем пополнение
                if($status == 3){
                    $income = $db->fetch("SELECT cost FROM ".$sys_tables['mortgage_applications']."
                                          LEFT JOIN ".$sys_tables['mortgage_application_types']." ON ".$sys_tables['mortgage_application_types'].".id = ".$sys_tables['mortgage_applications'].".id_type
                                          WHERE ".$sys_tables['mortgage_applications'].".id = ?",$id);
                    $income = (empty($income) || empty($income['cost']) ? 0 : $income['cost']);
                    if(!empty($id_admin)){
                        $db->query("INSERT INTO ".$sys_tables['users_finances']." (`datetime`,id_user,obj_type,id_parent,income,id_initiator,action_source) VALUES (NOW(),?,?,?,?,?,?)",$id_admin,'mortgage_application',$id,$income,$auth->id,1);
                        $db->query("UPDATE ".$sys_tables['users']." SET balance = balance + ? WHERE id = ?",$income,$id_admin);
                    }
                }
                
                $ajax_result['ok'] = $res;
                
                break;
            //читаем ответы банков
            default:
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $banks_info = $db->fetchall("SELECT ".$sys_tables['agencies'].".id, ".$sys_tables['agencies'].".title, ".$sys_tables['mortgage_applications_recievers'].".status,
                                                    CASE
                                                        WHEN ".$sys_tables['mortgage_applications_recievers'].".status = 1 THEN 'ответ не получен'
                                                        WHEN ".$sys_tables['mortgage_applications_recievers'].".status = 2 THEN 'принята'
                                                        WHEN ".$sys_tables['mortgage_applications_recievers'].".status = 3 THEN 'отклонена банком'
                                                    END AS status_info
                                             FROM ".$sys_tables['mortgage_applications_recievers']."
                                             LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['mortgage_applications_recievers'].".id_bank
                                             WHERE id_application = ?",false,$id);
                Response::SetArray('list',$banks_info);
                Response::SetInteger('app_id',$id);
                $module_template = "admin.mortgage_apps.show.html";
        }
        break;
    
    //редактирование заявки
    case $action == 'add':
    case $action == 'edit':
        
        $GLOBALS['js_set'][] = 'admin/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/modules/mortgage/form.js';
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
    
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        if($action=='add'){
            $info = $db->prepareNewRecord($sys_tables['mortgage_applications']);
            Response::SetString('form_parameter', 'add');
        } else {
            $info = $db->fetch("SELECT *,
                                       IF(birthdate LIKE '0000%','',DATE_FORMAT(birthdate,'%d.%m.%Y')) AS birthdate,
                                       CASE
                                         WHEN registration_general = 1 THEN 'Санкт-Петербург'
                                         WHEN registration_general = 2 THEN 'Ленинградская область'
                                         WHEN registration_general = 3 THEN 'другое'
                                         WHEN registration_general = 0 THEN 'не указана'
                                       END AS registration_general_title
                                FROM ".$sys_tables['mortgage_applications']." WHERE id = ?",$id);
            Response::SetString('form_parameter', 'edit/'.$id);
            if(empty($info)) Host::Redirect('/admin/service/mortgage_apps/add/');
            $old_status = $info['status'];
        }
        
        //готовим данные для формы
        $types = $db->fetchall("SELECT ".$sys_tables['mortgage_application_types'].".id, 
                                       ".$sys_tables['mortgage_application_types'].".title
                                FROM ".$sys_tables['mortgage_application_types']."",'id');
        foreach($types as $key=>$type){
            $mapping['mortgage_applications']['id_type']['values'][$key] = $type['title'];
        }
        $mapping['mortgage_applications']['banks_selected']['values'] = $db->fetchall("SELECT ".$sys_tables['agencies'].".id, 
                                                                                              ".$sys_tables['agencies'].".title,
                                                                                              LEFT(".$sys_tables['agencies_photos'].".name,2) AS subfolder,
                                                                                              ".$sys_tables['agencies_photos'].".name as photo_name,
                                                                                              CONCAT('organizations/company',".$sys_tables['agencies'].".chpu_title,'/') AS url
                                                                                       FROM ".$sys_tables['agencies']." 
                                                                                       LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies'].".id_main_photo = ".$sys_tables['agencies_photos'].".id
                                                                                       WHERE ".$banks_condition,'id');
        foreach($mapping['mortgage_applications']['banks_selected']['values'] as $key=>$values){
            $mapping['mortgage_applications']['banks_selected']['values'][$key]['img'] = Config::$values['img_folders']['agencies']."/sm/".$values['subfolder']."/".$values['photo_name'];
            $bank_urls[$key] = $values['url'];
            unset($mapping['mortgage_applications']['banks_selected']['values']['subfolder']);
            unset($mapping['mortgage_applications']['banks_selected']['values']['name']);
        }
        $banks_selected = $db->fetch("SELECT GROUP_CONCAT(id_bank) as ids FROM ".$sys_tables['mortgage_applications_recievers']." WHERE id_application = ?",$id);
        if(!empty($banks_selected)) $mapping['mortgage_applications']['banks_selected']['value'] = $banks_selected['ids'];
        $mapping['mortgage_applications']['mortgage_years']['values'] = array_combine(range(5,20),range(5,20));
        //
        
        foreach($info as $key=>$field){
            if(!empty($mapping['mortgage_applications'][$key])) $mapping['mortgage_applications'][$key]['value'] = $info[$key];
        }
        
        if(!empty($info['id_geodata'])){
            $mapping['mortgage_applications']['registration']['value'] = $db->fetch("SELECT CONCAT(shortname,' ',offname) AS title FROM ".$sys_tables['geodata']." WHERE id = ?",$info['id_geodata']);
            $mapping['mortgage_applications']['registration']['value'] = (empty($mapping['mortgage_applications']['registration']['value'])?
                                                                          "":
                                                                          $mapping['mortgage_applications']['registration']['value']['title']);
        }
        
        $post_parameters = Request::GetParameters(METHOD_POST);
                
        
        if(!empty($post_parameters['submit'])){
            //считаем все заявки полными
            $post_parameters['id_type'] = 2;
            
            Response::SetBoolean('form_submit', true);
           
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['mortgage_applications'][$key])) $mapping['mortgage_applications'][$key]['value'] = trim($post_parameters[$key]);
            }
            
            $errors = Validate::validateParams($post_parameters,$mapping['mortgage_applications']);
            foreach($errors as $key=>$value){
                if(!empty($mapping['mortgage_applications'][$key])) $mapping['mortgage_applications'][$key]['error'] = $value;
            }
            
            if(empty($errors)) {
                
                foreach($info as $key=>$field){
                    if(isset($mapping['mortgage_applications'][$key]['value'])) $info[$key] = $mapping['mortgage_applications'][$key]['value'];
                }
                
                $info['notes'] = preg_replace("/\n/","<br>",$info['notes']);
                $info['notes'] = preg_replace("/<br><br>/","<br>",$info['notes']);

                $formatted_birthdate = $info['birthdate'];
                $info['birthdate'] = datetime::createFromFormat('d.m.y',$info['birthdate']);
                if(!empty($info['birthdate'])) $info['birthdate'] = $info['birthdate']->format("Y-m-d");
                
                if($action=='edit'){
                    
                    //если статус изменился на опубликованный - отправляем письма
                    if($old_status != 1 && $info['status'] == 1){
                        //читаем цену за заявку этого типа
                        $expenditure = $db->fetch("SELECT cost FROM ".$sys_tables['mortgage_application_types']." WHERE id = ?",$info['id_type']);
                        $expenditure = (empty($expenditure) ? 0 : $expenditure['cost']);
                        //читаем почты банков
                        $banks_ids = $db->fetch("SELECT GROUP_CONCAT(id_bank) AS ids FROM ".$sys_tables['mortgage_applications_recievers']." WHERE id_application = ?",$info['id']);
                        if(!empty($banks_ids) && !empty($banks_ids['ids'])){
                            $banks_email = $db->fetchall("SELECT ".$sys_tables['agencies'].".email AS agency_main_email,
                                                                 ".$sys_tables['agencies'].".email_applications AS agency_apps_email,
                                                                 IF(".$sys_tables['users'].".name = '',".$sys_tables['users'].".login,".$sys_tables['users'].".name) AS name,
                                                                 ".$sys_tables['agencies'].".title,
                                                                 ".$sys_tables['agencies'].".id,
                                                                 ".$sys_tables['agencies'].".id_manager,
                                                                 ".$sys_tables['users'].".id AS admin_id
                                                          FROM ".$sys_tables['agencies']."
                                                          LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id AND ".$sys_tables['users'].".agency_admin = 1
                                                          WHERE ".$sys_tables['agencies'].".id IN (".$banks_ids['ids'].")",'id');
                            $notifying_log = [];
                            $mail_info = $info;
                            $mail_info['registration'] = $mapping['mortgage_applications']['registration']['value'];
                            $mail_info['registration_general'] = $info['registration_general_title'];
                            $mail_info['is_married'] = $mapping['mortgage_applications']['is_married']['values'][$mapping['mortgage_applications']['is_married']['value']];
                            $mail_info['birthdate'] = $formatted_birthdate;
                            $mail_info['estate_type'] = CreditCalculator::getEstateTypeTitle($info['estate_type']);
                            if(!empty($info['estate_id'])){
                                $estate_alias = CreditCalculator::getEstateTypeTitle($info['estate_type'],true);
                                $mail_info['object_link'] = "https://www.bsn.ru/".$estate_alias."/sell/".$info['estate_id']."/";
                            }
                            //отправляем письма и делаем списания
                            foreach($banks_email as $key=>$bank_info){
                                
                                $notifying_log[$key] = array("title"=>$bank_info['title'],'result'=>"",'id_manager'=>$bank_info['id_manager']);
                                
                                $mailer = new EMailer('mail');
                                $eml_tpl = new Template('modules/mortgage/templates/mail.bank.new_app.html');
                                // перевод письма в кодировку мейлера
                                Response::SetArray('data',$mail_info);
                                $html = $eml_tpl->Processing();
                                $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                                // параметры письма
                                $mailer->Body = $html;
                                $mailer->IsHTML(true);
                                //отчет
                                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, (!empty($bank_info['name']) ? $bank_info['name'].", н" : "Н")."овая заявка на BSN.ru");
                                
                                if(!Validate::isEmail($bank_info['agency_main_email']) && !Validate::isEmail($bank_info['agency_apps_email']) )
                                    $notifying_log[$key]['result'] = "некорректный email: '".$bank_info['agency_apps_email']."'";
                                else{
                                    
                                    if(Validate::isEmail($bank_info['agency_main_email'])) $mailer->AddAddress($bank_info['agency_main_email']);
                                    if(Validate::isEmail($bank_info['agency_apps_email'])) $mailer->AddAddress($bank_info['agency_apps_email']);
                                    $mailer->AddAddress('web@bsn.ru');
                                    $mailer->AddAddress("d.salova@bsn.ru");
                                    $mailer->AddAddress("olga.v.volkova@bspb.ru");
                                    $mailer->From = 'no-reply@bsn.ru';
                                    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'BSN.ru');
                                    // попытка отправить
                                    $notifying_log[$key]['result'] = $mailer->Send();
                                }
                                
                                //списание
                                if(!empty($bank_info['admin_id'])){
                                    $db->query("INSERT INTO ".$sys_tables['users_finances']." (`datetime`,id_user,obj_type,id_parent,expenditure,action_source) VALUES (NOW(),?,?,?,?,?)",$bank_info['admin_id'],'mortgage_application',$id,$expenditure,1);
                                    $db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?",$expenditure,$bank_info['admin_id']);
                                }
                                
                            }
                            //оповещаем менеджеров
                            $managers = $db->fetchall("SELECT id,email,name,'' AS mail_text FROM ".$sys_tables['managers'],'id');
                            foreach($notifying_log as $bank_id=>$send_info){
                                $managers[$send_info['id_manager']]['mail_text'] .= "#".$bank_id.": ".$send_info['title']."<br />";
                            }
                            foreach($managers as $key=>$manager_info){
                                if(empty($manager_info['mail_text'])) continue;
                                $mailer = new EMailer('mail');
                                $eml_tpl = new Template('modules/mortgage/templates/mail.manager.new_app.html');
                                Response::SetString('banks_list',$manager_info['mail_text']);
                                // перевод письма в кодировку мейлера
                                $html = $eml_tpl->Processing();
                                $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                                // параметры письма
                                $mailer->Body = $html;
                                $mailer->IsHTML(true);
                                //отчет
                                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, (!empty($bank_info['name']) ? $bank_info['name'].", н" : "Н")."овая заявка на ипотеку на BSN.ru");
                                
                                $mailer->AddAddress($manager_info['email']);
                                $mailer->AddAddress('web@bsn.ru');
                                $mailer->From = 'no-reply@bsn.ru';
                                $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'BSN.ru');
                                // попытка отправить
                                $notifying_log[$key]['result'] = $mailer->Send();
                            }
                            
                        }
                    }
                    $res = $db->updateFromArray($sys_tables['mortgage_applications'], $info, 'id');
                    
                    //обновляем таблицу получателей
                    $banks_selected = Request::GetString('banks_selected',METHOD_POST);
                    $db->query("DELETE FROM ".$sys_tables['mortgage_applications_recievers']." WHERE id_application = ?",$id);
                    if(!empty($banks_selected)){
                        $banks_selected = array_map("Convert::toInt",explode(',',$banks_selected));
                        $line_to_insert = array('id_application'=>$id,'id_bank'=>0,'status'=>$info['status']);
                        foreach($banks_selected as $key=>$item){
                            $line_to_insert['id_bank'] = $item;
                            $db->insertFromArray($sys_tables['mortgage_applications_recievers'],$line_to_insert,false,false,true);
                        }
                    }
                } else {
                    $res = $db->insertFromArray($sys_tables['mortgage_applications'], $info, 'id');
                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        if(!empty($res)) {
                            header('Location: '.Host::getWebPath('/admin/service/mortgage_applications/edit/'.$new_id.'/'));
                            exit(0);
                        }
                    }
                }
                Response::SetBoolean('saved', $res);
            } else Response::SetBoolean('errors', true);
        }
        
        $referer = Host::getRefererURL();
        if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
            Response::SetBoolean('form_submit', true);
            Response::SetBoolean('saved', true);
        }
        Response::SetArray('data_mapping',$mapping['mortgage_applications']);
        Response::SetBoolean('not_show_submit_button',true);
        $module_template = "admin.mortgage_apps.edit.html";
        break;
    //убираем заявку, не прошедшую модерацию
    case ($action == 'del' && $ajax_mode == true):
        $id = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        if(empty($id)){
            $ajax_result['ok'] = false;
            break;
        }else{
            $res = $db->query("DELETE FROM ".$sys_tables['mortgage_applications']." WHERE id = ?",$id);
            $ajax_result['type'] = 'del';
            $ajax_result['ids'] = array($id);
            $ajax_result['ok'] = $res;
        }
        break;
    
    //общий список заявок
    default:
        
        $where = [];
        
        if(!empty($filters['date_start'])) $where[] = $sys_tables['applications'].".`creation_datetime` >= STR_TO_DATE('".$filters['date_start']."','%d.%m.%Y')";
        if(!empty($filters['date_end'])) $where[] = $sys_tables['applications'].".`creation_datetime` <= CONCAT(STR_TO_DATE('".$filters['date_end']."','%d.%m.%Y'),' 99')";
        if(!empty($filters['moder_date_start'])) $where[] = $sys_tables['applications'].".`datetime` >= STR_TO_DATE('".$filters['moder_date_start']."','%d.%m.%Y')";
        if(!empty($filters['moder_date_end'])) $where[] = $sys_tables['applications'].".`datetime` <= CONCAT(STR_TO_DATE('".$filters['moder_date_end']."','%d.%m.%Y'),' 99')";
        
        if(count($where)>0) $where = implode(" AND ",$where);
        else $where = "";
        
        $paginator = new Paginator($sys_tables['mortgage_applications'],30,$where);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/service/mortgage_applications'
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }
        
        $apps_list = $db->fetchall("SELECT ".$sys_tables['mortgage_applications'].".*,
                                           DATE_FORMAT(".$sys_tables['mortgage_applications'].".date_in,'%d.%m.%Y %H:%i:%s') AS date_in_formatted,
                                           DATE_FORMAT(".$sys_tables['mortgage_applications'].".date_moderated,'%d.%m.%Y %H:%i:%s') AS date_moderated_formatted,
                                           CASE
                                                WHEN ".$sys_tables['mortgage_applications'].".status = 1 THEN 'прошла модерацию'
                                                WHEN ".$sys_tables['mortgage_applications'].".status = 2 THEN 'не прошла модерацию'
                                                WHEN ".$sys_tables['mortgage_applications'].".status = 3 THEN 'на модерации'
                                                WHEN ".$sys_tables['mortgage_applications'].".status = 4 THEN 'в архиве'
                                                WHEN ".$sys_tables['mortgage_applications'].".status = 5 THEN 'нигде'
                                           END AS status_info,
                                           CONCAT(".$sys_tables['geodata'].".offname,' ',".$sys_tables['geodata'].".shortname) AS registration_title,
                                           CONCAT(".$sys_tables['mortgage_applications'].".lastname,' ',
                                                  ".$sys_tables['mortgage_applications'].".name,' ',
                                                  ".$sys_tables['mortgage_applications'].".patronymic) AS fio,
                                           DATE_FORMAT(".$sys_tables['mortgage_applications'].".birthdate,'%d.%m.%Y') AS birthdate_formatted,
                                           LEFT(".$sys_tables['mortgage_applications'].".notes,40) AS notes_short,
                                           ".$sys_tables['mortgage_applications'].".notes
                                    FROM ".$sys_tables['mortgage_applications']."
                                    LEFT JOIN ".$sys_tables['geodata']." ON ".$sys_tables['geodata'].".id = ".$sys_tables['mortgage_applications'].".id_geodata
                                    ORDER BY ".$sys_tables['mortgage_applications'].".`date_in` DESC
                                    LIMIT ".$paginator->getLimitString($page,30));
        
        Response::SetArray('list',$apps_list);
        
        
        //читаем список всех id пользователей которые присутствуют в качестве id_owner
        $owners_list = $db->fetchall("SELECT DISTINCT id_owner FROM ".$sys_tables['applications']);
        $user_ids = [];
        foreach($owners_list as $key=>$item)
            $user_ids[] = $item['id_owner'];
        $user_ids = implode(',',array_unique($user_ids));
        //читаем список агентств, у которых есть заявки
        $banks_selected = $db->fetchall("SELECT ".$sys_tables['agencies'].".id,
                                                ".$sys_tables['agencies'].".title,
                                                LEFT(".$sys_tables['agencies_photos'].".name,2) AS subfolder,
                                                ".$sys_tables['agencies_photos'].".name as photo_name,
                                                CONCAT('organizations/company',".$sys_tables['agencies'].".chpu_title,'/') AS url
                                         FROM ".$sys_tables['agencies']." 
                                         LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies'].".id_main_photo = ".$sys_tables['agencies_photos'].".id
                                         WHERE ".$banks_condition,'id');
        Response::SetArray('banks_selected',$banks_selected);
        if($paginator->pages_count>1) Response::SetArray('paginator', $paginator->Get($page));
        
        $total_found = $db->fetchall("SELECT * 
                                      FROM ".$sys_tables['mortgage_applications']."
                                      ".(!empty($where)?" WHERE ".$where:"")."
                                      GROUP BY ".$sys_tables['mortgage_applications'].".id");
        Response::SetInteger('total_found',count($total_found));
        
        
        Response::SetBoolean('can_edit',in_array($auth->id_group,array(10,101)));
        
        $module_template = "admin.mortgage_apps.list.html";
        break;
}

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));

?>