<?php
//хлебные крошки по умолчанию
$this_page->addBreadcrumbs('Ипотека', 'mortgage',0);
 Response::SetBoolean('wide_format', true);
//класс для отправки смс

$page_url = $this_page->page_url;
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$GLOBALS['css_set'][] = '/modules/mortgage/style.css';
require_once("includes/class.credit_calculator.php");

$mapping = include(dirname(__FILE__).'/conf_mapping.php');
//статус тут не нужен - это для админки
unset($mapping['mortgage_applications']['status']);
//тип тоже
unset($mapping['mortgage_applications']['id_type']);

//условие по которому выбираем банки из агентств
$banks_condition = "activity & ".pow(2,4)." AND estate_types & ".pow(2,8)." AND mortgage_applications_accepting < 5";

//ограничиваем отправку смс на один номер - раз в 5 минут
$time_limit_for_one_number = 300;
$from = Request::GetString("from",METHOD_GET);
switch(true){
   ///////////////////////////////////////////////////////////////
   //Редирект на банк с рекламы ФБ
   //////////////////////////////////////////////////////////////
   case !empty($from) && $from == 'fb':
        if(!Host::$is_bot) $db->query("INSERT INTO ".$sys_tables['credit_calculator_stats_click_day']." SET `id_parent`= ? , `type` = ?", 29, 3);
        $module_template = 'redirect.html';
        $this_page->page_template = 'modules/mortgage/templates/redirect.html';   
        break;
   case $action =='get-banks-list' && !empty($ajax_mode):
        require_once("includes/class.credit_calculator.php");
        $estate_type = Request::GetString("estate_type",METHOD_POST);
        if(empty($estate_type)) return false;
        $mapping['mortgage_applications']['banks_selected']['values'] = CreditCalculator::getBanksList(false,$estate_type);
        Response::SetArray('field',$mapping['mortgage_applications']['banks_selected']);
        $ajax_result['ok'] = true;
        $module_template = "ajax.form.banks.list.html";
        break;
   
   //предложения банка по заполненным данным
   case $action == "get-bank-info" && !empty($ajax_mode):
        $bank_id = Request::GetInteger("bank_id",METHOD_POST);
        $estate_cost = Request::GetInteger("estate_price",METHOD_POST);
        if(empty($estate_cost)){
            $referer = Host::getRefererURL();
            preg_match("/(live|build|country|commercial)/si",$referer,$estate_type);
            preg_match("/[0-9]+(?=\/$)/si",$referer,$estate_id);
            if( ! ( empty($estate_type) || empty($estate_id) ) ){
               
                $estate_type = array_pop($estate_type);
                $estate_id = array_pop($estate_id);
                $estate_cost = $db->fetch("SELECT cost FROM ".$sys_tables[$estate_type]." WHERE id = ?",$estate_id);
                if(empty($estate_cost) || empty($estate_cost['cost'])){
                    $ajax_result['ok'] = false;
                    break;
                }
                $estate_cost = $estate_cost['cost'];

            }
            if( empty( $estate_cost ) ) $estate_cost = 4000000;
        }
        else $estate_type = Request::GetString("estate_type",METHOD_POST);
        
        $first_payment = Request::GetInteger("first_payment",METHOD_POST);
        $monthly_payment = Request::GetInteger("month_payment",METHOD_POST);
        if( empty( $estate_type ) ) $estate_type = "live";
        $months = Request::GetInteger("months",METHOD_POST);
        $months = (!empty($months) ? $months*12 : false);
        
        require_once("includes/class.credit_calculator.php");
        $ajax_result['params'] = $months.";".$first_payment.";".$monthly_payment.";";
        $bank_info = CreditCalculator::getBankPaymentInfo( $bank_id, $estate_type, false, $estate_cost, $months, $first_payment, $monthly_payment, true, true );
        $ajax_result['estate_cost'] = $estate_cost;
        $ajax_result['bank_info'] = $bank_info;
        $ajax_result['ok'] = true;
        
        break;
   
   //по нажатию "Отправить" записываем данные, //отправляем смс с кодом - не отправляем пока
   case $action == 'add' && !empty($ajax_mode):
        $form_data = Request::GetParameters(METHOD_POST);
        
        $errors = Validate::validateParams($form_data,$mapping['mortgage_applications']);
        unset($errors['estate_type']);
        if(!Validate::isEmail($form_data['email'])) $errors['email'] = 'Некорректный email';
        if(!Validate::isPhone($form_data['phone'])) $errors['phone'] = 'Некорректный телефон';
        if(!empty($errors)){
            $ajax_result['errors'] = $errors;
            break;
        }
        
        unset($form_data['form_type']);
        unset($form_data['send_agree']);
        unset($form_data['ajax']);
        unset($form_data['id_type']);
        if(!empty($form_data['href'])){
            //Дария сказала чтобы считались как полные, теперь будет списываться по 1490 вместо 990 
            $form_data['id_type'] = 2;
            $input_name = $form_data['name'];
            @list($form_data['lastname'],$form_data['name'],$form_data['patronymic']) = explode(' ',$form_data['name']);
            if(empty($form_data['notes'])) $form_data['notes'] = "";
            $url_params = explode('/',$form_data['href']);
            if(empty($form_data['estate_type'])) $form_data['estate_type'] = $url_params[3];
            $estate_type_alias = preg_replace('/[^A-z]/si','',$form_data['estate_type']);
            $form_data['estate_id'] = (int)$url_params[5];
            $form_data['estate_price'] = $db->fetch("SELECT cost FROM ".$sys_tables[$estate_type_alias]." WHERE id = ?",$form_data['estate_id']);
            $form_data['estate_price'] = (!empty($form_data['estate_price']) && !empty($form_data['estate_price']['cost']) ? $form_data['estate_price']['cost'] : 0);
            $form_data['is_married'] = 0;
            unset($form_data['birthdate']);
            
        }else{
            //смотрим количество заполненных полей и определяем тип формы (только обязательные - короткая)
            $form_data['id_type'] = ( (count(array_filter($mapping['mortgage_applications'],function($e){return empty($e['allow_empty']);})) == 
                                       count(array_filter($form_data,function($e){return !empty($e);})) ) ? 1 : 2 );
            $input_name = $form_data['lastname']." ".$form_data['name']." ".$form_data['patronymic'];
            $estate_type_alias = preg_replace('/[^A-z]/si','',$form_data['estate_type']);
        } 
        switch($form_data['estate_type']){
            case "live": $form_data['estate_type'] = 1;break;
            case "build": $form_data['estate_type'] = 2;break;
            case "commercial": $form_data['estate_type'] = 3;break;
            case "country": $form_data['estate_type'] = 4;break;
            default:
        }
        
        $form_data['form_type'] = $db->fetch("SELECT alias FROM ".$sys_tables['mortgage_application_types']." WHERE id = ".$form_data['id_type'])['alias'];
        
        //проверяем корректность введенного (теперь банки при отсутствии идут все)
        if(!empty($form_data['calculator_id'])){
            $banks_selected = $db->fetch("SELECT id_agency FROM ".$sys_tables['credit_calculator']." WHERE id = ?",$form_data['calculator_id']);
            $form_data['banks_selected'] = (empty($banks_selected) || empty($banks_selected['id_agency']) ? "" : $banks_selected['id_agency']);
        }
        $banks_selected = (!empty($form_data['banks_selected']) ? $form_data['banks_selected'] : implode(',',array_keys(CreditCalculator::getBanksList(false,$estate_type_alias))));
        unset($form_data['banks_selected']);
        $banks_selected = preg_replace('/[^0-9\,]/','',$banks_selected);
        $banks_selected = $db->fetch("SELECT GROUP_CONCAT(id) AS ids,COUNT(*) AS amount FROM ".$sys_tables['agencies']." WHERE ".$banks_condition." AND id IN(".$banks_selected.")");
        $banks_selected = (empty($banks_selected) || empty($banks_selected['ids']) ? [] : explode(',',$banks_selected['ids']));
        unset($form_data['ajax']);
        
        $form_data['date_in'] = date('Y-m-d H:i:s');
        if(!empty($form_data['birthdate'])){
            $birthdate = DateTime::createFromFormat("d.m.y",$form_data['birthdate']);
            $form_data['birthdate'] = $birthdate->format("Y-m-d");
        }
        
        
        //$form_data['id_type'] = $db->fetch("SELECT id FROM ".$sys_tables['mortgage_application_types']." WHERE alias = ?",$form_data['form_type']);
        if(empty($form_data['id_type']) || !in_array($form_data['id_type'],array(1,2))){
            $ajax_result['ok'] = false;
            break;
        }
        
        $form_data['sms_confirm_code'] = substr(preg_replace('/[^0-9]+/si',rand(0,9),md5(time().uniqid(mt_rand(), true) )),0,4);
        
        $form_data['ip'] = Host::getUserIp();
        $form_data['id_user'] = $auth->id;
        
        
        $ajax_result['result'] = $db->insertFromArray($sys_tables['mortgage_applications'],$form_data);
        
        //отмечаем в таблице соответствия банки
        $insert_id = $db->insert_id;
        $res = true;
        foreach($banks_selected as $key=>$bank_id) 
            $res *=  $db->insertFromArray($sys_tables['mortgage_applications_recievers'],array('id_application' => $insert_id,'id_bank' => $bank_id, 'status' => 1));
        
        $ajax_result['result'] *= $res;
        
        //в случае неполадок оповещаем web@bsn, если все в порядке - менеджера
        if(!$ajax_result['result']){
            $mailer = new EMailer('mail');
            $html = "Неполадки при отправке заявки на ипотеку: <br />last query: ".$db->last_query." <br /> error: ".$db->error." <br /> form_data: ".json_encode($form_data);
            $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Неполадки при отправке заявки на ипотеку");
            $mailer->IsHTML(true);
            $mailer->AddAddress("web@bsn.ru");
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
            $res = false;
            $res = $mailer->Send();
        }else{
            $data = array('user_fio' => implode(' ',array($form_data['lastname'],$form_data['name'],$form_data['patronymic'])),
                          'user_phone' => $form_data['phone'],
                          'user_email' => $form_data['email'],
                          'edit_url' => "/admin/service/mortgage_applications/edit/".$insert_id."/");
            Response::SetArray('data',$data);
            $mailer = new EMailer('mail');
            $eml_tpl = new Template('/modules/mortgage/templates/mail.manager.tomoderate.html', "");
            // формирование html-кода письма по шаблону
            $html = $eml_tpl->Processing();
            // перевод письма в кодировку мейлера
            $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Необходимо проверить заявку на ипотеку на bsn.ru: #".$insert_id);
            $mailer->IsHTML(true);
            $mailer->AddAddress("d.salova@bsn.ru");
            $mailer->AddAddress("web@bsn.ru");
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
            $res = false;
            $res = $mailer->Send();
        }
        $ajax_result['name'] = $input_name;
        $ajax_result['ok'] = true;
        $module_template = "/templates/popup.success.html";
        break;
   ////////////////////////////////////////////////////////////////////////////////////////////////
   // Заглавная страница ипотеки
   ////////////////////////////////////////////////////////////////////////////////////////////////
     case empty($action):
        
        require_once("includes/class.credit_calculator.php");
        
        //убираем id объявления
        unset($mapping['mortgage_applications']['estate_id']);
        //читаем информацию по карточке, откуда перешли
        $referer = Host::getRefererURL();
        if(!empty($referer)){
            $referer = explode('/',preg_replace('/.+(?<=\.)bsn(?=\.)[^\/]+/','',$referer));
            $referer = array_values(array_filter($referer,function($e){ return !empty($e);}));
            if(count($referer) == 3){
                list($estate_type,$rent,$object_id) = $referer;
                if(empty($sys_tables[$estate_type]) || $rent == 'rent' || empty($object_id)) $object_cost = 0;
                else $object_cost = $db->fetch("SELECT cost FROM ".$sys_tables[$estate_type]." WHERE id = ? and published = 1",$object_id);
                if(empty($object_cost) || empty($object_cost['cost'])) $object_cost = 0;
                else $object_cost = $object_cost['cost'];
                switch($estate_type){
                    case 'live':
                        $credit_type = 1;
                        break;
                    case 'build': 
                        $credit_type = 4;
                        break;
                    case 'commercial': 
                        $credit_type = 2;
                        break;
                    case 'country': 
                        $credit_type = 3;
                        break;
                }
            }
        }
        
        //если не с карточки, добавляем блок выбора типа недвижимости
        if(empty($object_cost)){
            Response::SetString('mainpage_h1', empty($this_page->page_seo_h1) ? "Заявки на ипотеку" : $this_page->page_seo_h1);
            $types = CreditCalculator::getAvailableEstateTypes();
            $credit_type = false;
            Response::SetArray('types',$types);
            $module_template = 'mainpage.html';
        }
        
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/form.validate.js';
        $GLOBALS['js_set'][] = '/js/interface.js';
        $GLOBALS['js_set'][] = '/modules/mortgage/form.js';
     
        Response::SetString('mainpage_h1', empty($this_page->page_seo_h1) ? false : $this_page->page_seo_h1);
        if(empty($mapping['mortgage_applications'])){
            $this_page->http_code = 404;
            break;
        }
        $mapping = $mapping['mortgage_applications'];
        
        
        //убрана пока регистрация
        unset($mapping['id_geodata']);
        unset($mapping['registration']);
        
        //список банков
        $mapping['banks_selected']['values'] = CreditCalculator::getBanksList($credit_type);
        
        if(empty($mapping['banks_selected']['values'])){
            $this_page->http_code = 404;
            break;
        }
        
        //если заявка на конкретный объект, даем тип недвижимости и стоимость
        if(!empty($object_cost)){
            Response::SetString('estate_type',$estate_type);
            $mapping['estate_price']['value'] = $object_cost;
        }
        //срок кредитования
        //$mapping['mortgage_years']['values'] = array_combine(range(5,20),range(5,20));
        $h1 = empty($this_page->page_seo_h1) ? "Заявка на расчет ипотечного кредита" : $this_page->page_seo_h1;
        
        
        //$list = $smser->getDevicesList();
        
        Response::SetString('mainpage_h1', $h1);
        Response::SetArray('data_mapping',$mapping);
        $module_template = 'mainpage.html';
        break;
     default:
        $this_page->http_code = 404;
        break;
}


?>