<?php
  $GLOBALS['js_set'][] = '/modules/business_centers/ajax_actions.js';

require_once('includes/class.applications.php');
require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.messages.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

$messages = new Messages();

$this_page->manageMetadata(array('title'=>'Заявки'));
        
// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
$filters['estate_type'] = Request::GetInteger('f_estate_type',METHOD_GET);
$filters['realtor_help_type'] = Request::GetInteger('f_realtor_help_type',METHOD_GET);
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
if(!empty($filters['realtor_help_type'])) $get_parameters['f_realtor_help_type'] = $filters['realtor_help_type'];
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
// обработка action-ов
switch(true){
    //редактирование заявки
    case $action == 'edit':
        $id = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        $this_app = new Application($id,false,null);
        
        $estate_type = $this_app->getAttr('estate_type_title');
        Response::SetString('realtor_help_type_title', $this_app->getAttr('realtor_help_type_title'));
        
        
        $app_lifetime = $this_app->getAttr('lifetime');
        
        $old_status = $this_app->status;
        //unset($app_info['estate_type_title']);
        
        //дополнительные данные для формы - тип пользователя
        $user_types = $db->fetchall("SELECT * FROM ".$sys_tables['owners_user_types'],'id');
        $mapping['applications']['id_user_type']['values'] = array_combine(array_keys($user_types),array_map(function($e){return $e['title'];},$user_types));
        $mapping['applications']['id_user_type']['fieldtype'] = 'radio';
        
        $work_statuses = $db->fetchall("SELECT * FROM ".$sys_tables['work_statuses'],'id');
        $mapping['applications']['id_work_status']['values'] = array_combine(array_keys($work_statuses),array_map(function($e){return $e['title'];},$work_statuses));
        $mapping['applications']['id_work_status']['fieldtype'] = 'radio';
        
        $this_app->returnToMapping($mapping['applications']);
        
        $post_parameters = Request::GetParameters(METHOD_POST);
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(isset($mapping['applications'][$key])){
                    $mapping['applications'][$key]['value'] = $post_parameters[$key];
                }
            }
            
            //$this = $mapping['applications'][$key]['value'];
            $this_app->updateFromMapping($mapping['applications']);
            
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['applications']);
            $res = false;
            
            //если все хорошо, записываем и обновляем дату
            //if(empty($errors)) $res = $this_app->saveToDB(true);
            
            Response::SetBoolean('saved',$res);
            $ajax_result['ok'] = $res;
            //если заявка прошла модерацию, оповещаем ответственного менеджера и агентство
            if($old_status != 2 && $this_app->getAttr('status') == 2){
                
                //проверяем что это не общая заявка
                /*
                if(!empty($app_info['id_user'])){
                    $id_agency = $db->fetch("SELECT id_agency FROM ".$sys_tables['users']." WHERE id = ".$app_info['id_user'])['id_agency'];
                    //если это агентство, проверяем время работы
                    if(!empty($id_agency)){
                        $agency_in_time = $db->fetch("SELECT id
                                                      FROM ".$sys_tables['agencies']."
                                                      LEFT JOIN ".$sys_tables['agencies_opening_hours']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['agencies_opening_hours'].".id_agency
                                                      WHERE ".$sys_tables['agencies'].".id = ".$id_agency." AND
                                                            ".$sys_tables['agencies'].".is_agregator = 2 AND
                                                            ".$sys_tables['agencies_opening_hours'].".day_num = WEEKDAY(NOW())+1 AND
                                                            ".$sys_tables['agencies_opening_hours'].".`begin`<TIME_FORMAT(NOW(),'%H:%i:%s') AND
                                                            ".$sys_tables['agencies_opening_hours'].".`end`>TIME_FORMAT(NOW(),'%H:%i:%s')");
                        //если это не агрегатор и мы не попали во время работы, отправляем заявку в "ждущие опубликования"
                        if(empty($agency_in_time)){
                            $db->query("UPDATE ".$sys_tables['applications']." SET status = 6 WHERE id = ".$app_info['id']);
                        }
                    }
                }
                */
                
                //время закрытости заявки
                if($app_lifetime % 60 == 0)
                    $app_lifetime = (floor($app_lifetime/60))." ч.";
                else{
                    $app_lifetime = (($app_lifetime/60 > 0)?(floor($app_lifetime/60)." ч."):("")).(($app_lifetime % 60 > 0)?(($app_lifetime % 60)." мин."):(""));
                }
                //id объекта заявки
                $id = $this_app->id_parent;
                if($estate_type == 'housing_estates') $estate_url = 'build/'.$estate_type;
                elseif($estate_type == 'cottages') $estate_url = 'country/'.$estate_type;
                elseif($estate_type == 'business_centers') $estate_url = 'commercial/'.$estate_type;
                else $estate_url = $estate_type;
                //читаем информацию по объекту заявки
                //если это универсальная заявка,
                $app_to_admin = false;
                if(empty($this_app->id_parent)){
                    $object_info['id_agency'] = (empty($this_app->id_agency)?0:$this_app->id_agency);
                    $object_info['id_user'] = (empty($this_app->id_user)?0:$this_app->id_user);
                    
                }else{
                    $estate_type = $this_app->getAttr('estate_type_title');
                    $arch_prefix = ($this_app->getAttr('is_archive_object') == 1?"_archive":"");
                    $estate_table = (!empty($arch_prefix)?$sys_tables[$estate_type.$arch_prefix]:$sys_tables[$estate_type]);
                    switch(true){
                        //ЖК,КП,БЦ
                        case in_array($this_app->getAttr('estate_type'),array(5,6,7)):
                            $app_to_admin = false;
                            $object_info = $db->fetch("SELECT ".$estate_table.".id_user,
                                                              ".$estate_table.".id_seller,
                                                              ".$estate_table.".advanced,
                                                              user_seller.id AS seller_id,
                                                              user_developer.id AS developer_id,
                                                              user_seller.id_agency AS seller_agency,
                                                              user_developer.id_agency AS developer_agency,
                                                              CONCAT(
                                                                  '/',  
                                                                  '".$estate_url."',
                                                                  '/',
                                                                  ".$estate_table.".id,'/',
                                                                  ".$estate_table.".chpu_title,'/'
                                                              ) AS object_url,
                                                              (agency_owner.payed_page = 1) AS owner_payed_page
                                                       FROM ".$estate_table."
                                                       LEFT JOIN ".$sys_tables['users']." user_owner ON ".$estate_table.".id_user = user_owner.id
                                                       LEFT JOIN ".$sys_tables['agencies']." agency_owner ON user_owner.id_agency = agency_owner.id
                                                       LEFT JOIN ".$sys_tables['users']." user_seller ON ".$estate_table.".id_seller = user_seller.id
                                                       LEFT JOIN ".$sys_tables['users']." user_developer ON ".$estate_table.".id_user = user_developer.id
                                                       WHERE ".$estate_table.".id = ?",$this_app->id_parent);
                            
                            //если карточка брендированная, или у компании расширенная страница то шлем уведомления и заявка скрыта
                            if($object_info['advanced'] == 1 || $object_info['owner_payed_page']){
                                //в зависимости от того, кто есть, оставляем только нужное
                                if(!empty($object_info['seller_agency'])){
                                    $object_info['id_agency'] = $object_info['seller_agency'];
                                    $object_info['id_user'] = $object_info['id_seller'];
                                    $app_to_admin = true;
                                }
                                elseif(!empty($object_info['developer_agency'])) $object_info['id_agency'] = $object_info['developer_agency'];
                            }
                            //если нет - просто пихаем в общий пул
                            else{
                                $this_app->toShared();
                                $no_notify = false;
                                unset($object_info);
                            } 
                            break;
                        case $this_app->getAttr('estate_type_title') == 8:
                            $object_info = $db->fetch("SELECT ".$sys_tables['promotions'].".title AS promotion_title,
                                                              ".$sys_tables['promotions'].".id AS promotion_id,
                                                              ".$sys_tables['promotions'].".id_estate_type,
                                                              ".$sys_tables['users'].".email AS user_email,
                                                              ".$sys_tables['agencies'].".title AS agency_title
                                                       FROM ".$sys_tables['promotions']."
                                                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['promotions'].".id_user = ".$sys_tables['users'].".id
                                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                                       WHERE ".$sys_tables['promotions'].".id = ".$this_app->id_parent);
                            break;
                        //не ЖК-КП-БЦ
                        case $this_app->getAttr('estate_type') < 5:
                            $object_info = $db->fetch("SELECT ".$estate_table.".id,
                                                              ".$estate_table.".id_user,
                                                              ".$estate_table.".seller_email,
                                                              ".$estate_table.".seller_name,
                                                              ".$estate_table.".seller_phone,
                                                              ".$sys_tables['users'].".id_agency,
                                                              ".$sys_tables['users'].".id AS user_id,
                                                              (".$sys_tables['agencies'].".is_agregator = 1) AS is_agregator,
                                                              (".$sys_tables['agencies'].".id_tarif > 0) AS agency_has_tarif,
                                                              CONCAT(
                                                                  '/',  
                                                                  '".$estate_url."',
                                                                  '/',
                                                                  ".
                                                                  (in_array($estate_type,array('build','live','commercial','country'))?"CASE 
                                                                    WHEN ".$estate_table.".rent=1 THEN 'rent'
                                                                    WHEN ".$estate_table.".rent=2 THEN 'sell'
                                                                  END,'/',".$estate_table.".id,'/'":$estate_table.".chpu_title,'/'")
                                                                  ."
                                                              ) AS object_url
                                                       FROM ".$estate_table."
                                                       LEFT JOIN ".$sys_tables['users']." ON ".$estate_table.".id_user = ".$sys_tables['users'].".id
                                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                                       WHERE ".$estate_table.".id = ?",$this_app->id_parent);
                            break;
                    }
                    
                    
                }
                
                //читаем информацию по объекту
                switch($estate_type){
                    case 'live':
                        $estateItem = new EstateItemLive($this_app->id_parent);
                        break;
                    case 'build':
                        $estateItem = new EstateItemBuild($this_app->id_parent);
                        break;
                    case 'commercial':
                        $estateItem = new EstateItemCommercial($this_app->id_parent);
                        break;
                    case 'country':
                        $estateItem = new EstateItemCountry($this_app->id_parent);
                        break;
                    case 'housing_estates':
                    case 'zhiloy_komleks':
                        $estateItem = new HousingEstates($this_app->id_parent);
                        break;
                    case 'business_centers':
                        $estateItem = new BusinessCenters($this_app->id_parent);
                        break;        
                    case 'cottages':
                    case 'cottedzhnye_poselki':
                        $estateItem = new Cottages($this_app->id_parent);
                        break;
                    default:
                        $estateItem = null;
                        break;
                }
                
                //если не получилось, сразу выходим
                if(empty($estateItem) && $this_app->getAttr('estate_type_title') != 8){
                    break;
                }
                
                //проверка на домен в черном списке, если в объявлении указана почта
                if(!empty($object_info['seller_email'])){
                    $domain = explode('@', $object_info['seller_email'])[1];
                } else $domain = false;
                
                
                
                switch(true){
                    ////////////////////////////////////////////////////////////////////////////////
                    // уведомления агентствам о новой заявке на Помощь
                    ////////////////////////////////////////////////////////////////////////////////
                    case $this_app->getAttr('realtor_help_type_title'):
                        if( !DEBUG_MODE )$this_app->sendNewAppNotifications();
                        break;
                    ////////////////////////////////////////////////////////////////////////////////
                    //в случае агрегатора и прикрепленного email, создаем пользователя по этому email и шлем ему оповещение
                    ////////////////////////////////////////////////////////////////////////////////
                    case (!empty($object_info['is_agregator']) && !empty($object_info['seller_email']) && !Validate::emailBlackList($domain)):
                        
                        //сначала ищем такого пользователя у нас:
                        if(!empty($object_info['seller_phone']) && strlen($object_info['seller_phone']) > 1)
                            $object_info['seller_phone'] = array_pop(Convert::ToPhone($object_info['seller_phone'], '812'));
                            
                        else $object_info['seller_phone'] = "";
                        $user_info = $db->fetch("SELECT id,name,email,id_agency,application_notification
                                                 FROM ".$sys_tables['users']."
                                                 WHERE email = '".$object_info['seller_email']."'".(!empty($object_info['seller_phone'])?" OR phone = '".$object_info['seller_phone']."'":""));
                        
                        //флаг, что агрегатор обработан, и общий пул не нужен
                        $agregator_user_accepted = true;
                        
                        //создаем нового пользователя, и оповещаем его
                        if(empty($user_info)){
                            // генерируем пароль
                            $reg_passwd = substr(md5(time()),-6);
                            
                            $colors = Config::Get('users_avatar_colors');
                            $new_color = $colors[mt_rand(0,11)];
                            // создание нового пользователя в БД, записываем агрегатор-источник
                            $res = $db->query("INSERT INTO ".$sys_tables['users']."
                                                (email,name,phone,passwd,datetime,access,id_agregator,avatar_color)
                                               VALUES
                                                (?,?,?,?,NOW(),'',?,?)"
                                               , $object_info['seller_email']
                                               , $object_info['seller_name']
                                               , $object_info['seller_phone']
                                               , sha1(sha1($reg_passwd))
                                               , $object_info['id_agency']
                                               , $new_color);
                            $new_user_id = $db->insert_id;
                            //шлем ему письмо об успешной регситрации
                            $mailer = new EMailer('mail');
                            
                            //информация об объекте, на который пришла заявка
                            $object_description = $estateItem->getTitles($id)['header'];
                            $object_url = $object_info['object_url'];
                            
                            // данные пользователя для шаблона
                            Response::SetArray( "data", array('email'=>$object_info['seller_email'], 
                                                              'name'=>$object_info['seller_name'], 
                                                              'password'=>$reg_passwd, 
                                                              'object_info' => $object_description,
                                                              'object_url'=>$object_url) );
                            // данные окружения для шаблона
                            $env = array(
                                'url' => Host::GetWebPath('/'),
                                'host' => Host::$host,
                                'ip' => Host::getUserIp(),
                                'datetime' => date('d.m.Y H:i:s')
                            );
                            
                            Response::SetArray('env', $env);
                            // инициализация шаблонизатора
                            $eml_tpl = new Template('/modules/applications/templates/agregator_user.registration.email.html', "");
                            // формирование html-кода письма по шаблону
                            $html = $eml_tpl->Processing();
                            // перевод письма в кодировку мейлера
                            $html = iconv('UTF-8', $mailer->CharSet, $html);
                            // параметры письма
                            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Регистрация на сайте '.Host::$host);
                            $mailer->Body = $html;
                            $mailer->AltBody = strip_tags($html);
                            $mailer->IsHTML(true);
                            $mailer->AddAddress('web@bsn.ru');
                            $mailer->AddAddress($object_info['seller_email'], iconv('UTF-8',$mailer->CharSet, $object_info['seller_name']));
                            $mailer->From = 'no-reply@bsn.ru';
                            $mailer->FromName = 'bsn.ru';
                            if( !DEBUG_MODE ){
                                if($mailer->Send()){
                                    Response::SetString('success','email');
                                    //если все хорошо, переписываем объект на нового пользователя и заявку тоже ему
                                    $db->query("UPDATE ".$estate_table." SET id_user = ? WHERE id = ?",$new_user_id,$object_info['id']);
                                    $this_app->toUser($new_user_id);
                                } 
                            }
                            //дочитываем информацию по агрегатору
                            $agency_info = $db->fetch("SELECT ".$sys_tables['agencies'].".id,
                                                              ".$sys_tables['agencies'].".title,
                                                              ".$sys_tables['managers'].".name,
                                                              ".$sys_tables['managers'].".email
                                                       FROM ".$sys_tables['agencies']."
                                                       LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                                       WHERE ".$sys_tables['agencies'].".id = ".$object_info['id_agency']);
                            //если было добавление нового пользователя, оповещаем
                            //отправка письма в БСН
                            $mailer = new EMailer('mail');
                            // параметры письма
                            Response::SetArray( "data", array('email'=>$object_info['seller_email'],
                                                              'name'=>$object_info['seller_name'],
                                                              'manager_name'=>$agency_info['name'],
                                                              'object_info' => $object_description,
                                                              'object_url'=>$object_url,
                                                              'inserted_id'=>$new_user_id) );
                            // инициализация шаблонизатора
                            $eml_tpl = new Template('/modules/applications/templates/agregator_user.manager.email.html', "");
                            // формирование html-кода письма по шаблону
                            $html = $eml_tpl->Processing();
                            // перевод письма в кодировку мейлера
                            $html = iconv('UTF-8', $mailer->CharSet, $html);
                            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Регистрация на сайте '.Host::$host);
                            $mailer->Body = $html;
                            $mailer->AltBody = strip_tags($html);
                            $mailer->IsHTML(true);
                            $mailer->AddAddress('web@bsn.ru');
                            if(!empty($agency_info['email'])) $mailer->AddAddress($agency_info['email']);
                            $mailer->From = 'no-reply@bsn.ru';
                            $mailer->FromName = 'bsn.ru';
                            if( !DEBUG_MODE ) $mailer->Send();   // попытка отправить письмо менеджеру
                            
                            //флаг что дальше письмо не понадобится
                            $sended = true;
                            break;
                        }
                        else{
                            $object_info['id_user'] = $user_info['id'];
                            $object_info['id_agency'] = 0;
                            $object_info['is_agregator'] = 0;
                            
                            //если не нужно уведомлять, отмечаем
                            if($user_info['application_notification'] == 2) $no_notify = true;
                            
                            //если вдруг это оказалось агентство, смотрим, есть ли у него галочка
                            if(!empty($user_info['id_agency'])) $agr_agency_info = $db->fetch("SELECT id,id_tarif FROM ".$sys_tables['agencies']." WHERE id = ".$user_info['id_agency']);
                            
                            //переписываем объект на нового пользователя и заявку тоже ему
                            $db->query("UPDATE ".$estate_table." SET id_user = ? WHERE id = ?",$object_info['id_user'],$object_info['id']);
                            $this_app->toUser($object_info['id_user']);
                            
                            //если заявка оказалась агентству и у него нет тарифа, переносим сразу в общий пул и убираем уведомления
                            if(!empty($agr_agency_info) && empty($agr_agency_info['id_tarif']) ){
                                $this_app->toShared();
                                $mapping['applications']['visible_to_all']['value'] = 1;
                                $no_notify = false;
                            }
                            
                        }

                    ////////////////////////////////////////////////////////////////////////////////
                    //шлем уведомления о новой заявке
                    ////////////////////////////////////////////////////////////////////////////////
                    default:
                        //проверяем что заявка пришла в рабочее время
                        if( !DEBUG_MODE ) if($this_app->checkWorkTime() && empty($no_notify)) $this_app->sendNewAppNotifications();
                        break;
                    
                }
                
                //если это агрегатор, и email пользователя не указан, пихаем заявку в общий пул
                if(!empty($object_info) && !empty($object_info['is_agregator']) && $object_info['is_agregator'] == 1 && empty($agregator_user_accepted)) $this_app->toShared();
                
                //если это был агрегатор и заявка перенесена, выходим
                if(!empty($sended)){
                    Response::SetArray('data_mapping', $mapping['applications']);
                    Response::SetBoolean('saved',$res);
                    $module_template = "admin.applications.edit.html";
                    break;
                }
            }
            
            //если все хорошо, записываем и обновляем дату модерации
            if(empty($errors)) $res = $this_app->saveToDB(true);//3562973
            Response::SetBoolean('saved',$res);
        }
        //формируем пояснение для универсальных заявок
        $description = $this_app->getDescription();
        Response::SetBoolean('deep_archive',($this_app->getAttr('is_archive_object') == 1));
        Response::SetInteger('object_published',$this_app->target_object_status);
        Response::SetArray('description',$description);
        Response::SetArray('data_mapping', $mapping['applications']);
        $module_template = "admin.applications.edit.html";
        break;
    //убираем заявку, не прошедшую модерацию
    case ($action == 'del' && $ajax_mode == true):
        $id = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        if(empty($id)){
            $ajax_result['ok'] = false;
            break;
        }else{
            $this_app = new Application($id,false,null);
            $ajax_result['ok'] = $this_app->Delete();
            $ajax_result['ids'] = array($id);
        }
        break;
    //убираем заявку, которую прозвонили
    case ($action == 'called' && $ajax_mode == true):
        $id = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        if(empty($id)){
            $ajax_result['ok'] = false;
            break;
        }else{
            $this_app = new Application($id,false,null);
            $ajax_result['ok'] = $this_app->Called();
            $ajax_result['ids'] = array($id);
        }
        break;
    case ($action == 'remoderate' && $ajax_mode == true):
        $id = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        if(!empty($id)){
            $this_app = new Application($id);
            $this_app->toModer();
            $ajax_result['ok'] = true;
            $ajax_result['ids'] = array($id);
        } 
        break;
    //таблица компаний, для которых цены заявок с новостроек забираются с базы sale
    case $action == 'sale':
        $list = $db->fetchall("SELECT ".$sys_tables['application_agencies_sale'].".agency_id_bsn AS id,
                                      ".$sys_tables['agencies'].".title,
                                      ".$sys_tables['sale_agencies'].".application_cost AS cost
                               FROM ".$sys_tables['application_agencies_sale']."
                               LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['application_agencies_sale'].".agency_id_bsn
                               LEFT JOIN ".$sys_tables['sale_agencies']." ON ".$sys_tables['sale_agencies'].".id = ".$sys_tables['application_agencies_sale'].".agency_id_sale");
        Response::SetArray('list',$list);
        $module_template = "admin.sale_costs.list.html";
        break;
    //общий список заявок
    default:
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $where = [];
        if(!empty($filters['status'])){
            $adding = "";
            //для новых общего пула
            if($filters['status'] == -2){
                $filters['status'] = -$filters['status'];
                $adding = " AND ".$sys_tables['applications'].".visible_to_all = 1";
            }elseif($filters['status'] == -3){
                $filters['status'] = "1,3,10";
            }
            
            $where[] = $sys_tables['applications'].".status IN (".$filters['status'].")".(!empty($adding)?$adding:"");
        } 
        if(!empty($filters['estate_type'])) $where[] = $sys_tables['application_types'].".estate_type = ".$filters['estate_type'];
        if(!empty($filters['realtor_help_type'])) $where[] = $sys_tables['applications'].".id_realtor_help_type > 0";
        if(!empty($filters['rent'])) $where[] = $sys_tables['application_types'].".rent = ".$filters['rent'];
        if(!empty($filters['app_id'])) $where[] = $sys_tables['applications'].".id = ".$filters['app_id'];
        if(!empty($filters['apper_id'])) $where[] = $sys_tables['applications'].".id_initiator = ".$filters['apper_id'];
        if(!empty($filters['object_id'])) $where[] = $sys_tables['applications'].".id_parent = ".$filters['object_id'];
        if(!empty($filters['agency'])){
            if($filters['agency'] == -1){
                $not_agencies_user_ids = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']." WHERE id_agency = 0")['ids'];
                $where[] = $sys_tables['applications'].".id_owner IN (".$not_agencies_user_ids.")";
            }else $where[] = $sys_tables['applications'].".id_owner = ".$filters['agency'];
        }
        if(!empty($filters['date_start'])) $where[] = $sys_tables['applications'].".`creation_datetime` >= STR_TO_DATE('".$filters['date_start']."','%d.%m.%Y')";
        if(!empty($filters['date_end'])) $where[] = $sys_tables['applications'].".`creation_datetime` <= CONCAT(STR_TO_DATE('".$filters['date_end']."','%d.%m.%Y'),' 99')";
        if(!empty($filters['moder_date_start'])) $where[] = $sys_tables['applications'].".`datetime` >= STR_TO_DATE('".$filters['moder_date_start']."','%d.%m.%Y')";
        if(!empty($filters['moder_date_end'])) $where[] = $sys_tables['applications'].".`datetime` <= CONCAT(STR_TO_DATE('".$filters['moder_date_end']."','%d.%m.%Y'),' 99')";
        
        //читаем только головные заявки
        $where[] = $sys_tables['applications'].".id_parent_app = 0";
        
        if(count($where)>0) $where = implode(" AND ",$where);
        else $where = "";
        
        $paginator = new Paginator($sys_tables['applications']."
                                   LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                                   LEFT JOIN ".$sys_tables['application_objects']." ON (".$sys_tables['application_objects'].".id = ".$sys_tables['applications'].".object_type OR
                                   ".$sys_tables['applications'].".object_type = 0) AND ".$sys_tables['application_objects'].".estate_type = ".$sys_tables['applications'].".estate_type", 
                                   30,$where,false,$sys_tables['applications'].".id");
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/service/applications'
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }
        $apps_list = $db->fetchall("SELECT 
                                            ".$sys_tables['applications'].".id,
                                            ".$sys_tables['applications'].".id_parent,
                                            ".$sys_tables['applications'].".id_user,
                                            ".$sys_tables['applications'].".phone,
                                            ".$sys_tables['applications'].".email,
                                            ".$sys_tables['applications'].".id_realtor_help_type,
                                            IF(".$sys_tables['agencies'].".id IS NOT NULL,
                                                ".$sys_tables['agencies'].".title,
                                                IF(".$sys_tables['users'].".id IS NOT NULL,CONCAT(".$sys_tables['users'].".name,' #',".$sys_tables['users'].".id),'')
                                            ) AS owner_info,
                                            IF(".$sys_tables['agencies'].".id IS NOT NULL,
                                                CONCAT('/admin/access/agencies/edit/',".$sys_tables['agencies'].".id),
                                                IF(".$sys_tables['users'].".id IS NOT NULL,
                                                    CONCAT('/admin/access/users/edit/',".$sys_tables['users'].".id),'')
                                            ) AS owner_url,
                                            ".$sys_tables['applications'].".visible_to_all,
                                            COUNT(DISTINCT a.id) AS child_count,
                                            CASE
                                                WHEN ".$sys_tables['application_types'].".estate_type=1 THEN 'live'
                                                WHEN ".$sys_tables['application_types'].".estate_type=2 THEN 'build'
                                                WHEN ".$sys_tables['application_types'].".estate_type=3 THEN 'commercial'
                                                WHEN ".$sys_tables['application_types'].".estate_type=4 THEN 'country'
                                                WHEN ".$sys_tables['application_types'].".estate_type=5 THEN 'zhiloy_kompleks'
                                                WHEN ".$sys_tables['application_types'].".estate_type=6 THEN 'cottages'
                                                WHEN ".$sys_tables['application_types'].".estate_type=7 THEN 'business_centers'
                                                WHEN ".$sys_tables['application_types'].".estate_type=8 THEN 'promotions'
                                            END AS estate_alias,
                                            CONCAT(CASE
                                                     WHEN ".$sys_tables['application_types'].".rent=1 THEN 'Аренда, '
                                                     WHEN ".$sys_tables['application_types'].".rent=2 THEN 'Покупка, '
                                                     WHEN ".$sys_tables['application_types'].".rent=3 THEN 'Сдам, '
                                                     WHEN ".$sys_tables['application_types'].".rent=4 THEN 'Продам, '
                                                   END,
                                                   CASE
                                                     WHEN ".$sys_tables['application_types'].".estate_type=1 THEN 'Жилая'
                                                     WHEN ".$sys_tables['application_types'].".estate_type=2 THEN 'Новостройки'
                                                     WHEN ".$sys_tables['application_types'].".estate_type=3 THEN 'Коммерческая'
                                                     WHEN ".$sys_tables['application_types'].".estate_type=4 THEN 'Загородная'
                                                     WHEN ".$sys_tables['application_types'].".estate_type=5 THEN 'ЖК'
                                                     WHEN ".$sys_tables['application_types'].".estate_type=6 THEN 'КП'
                                                     WHEN ".$sys_tables['application_types'].".estate_type=7 THEN 'БЦ'
                                                     WHEN ".$sys_tables['application_types'].".estate_type=8 THEN 'Акция'
                                                   END) AS universal_app_title,
                                            IF(".$sys_tables['application_types'].".estate_type=8,
                                                '/promotions/',
                                                CONCAT(
                                                  '/',  
                                                  CASE
                                                    WHEN ".$sys_tables['application_types'].".estate_type=1 THEN 'live'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=2 THEN 'build'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=3 THEN 'commercial'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=4 THEN 'country'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=5 THEN 'zhiloy_kompleks'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=6 THEN 'cottedzhnye_poselki'
                                                    WHEN ".$sys_tables['application_types'].".estate_type=7 THEN 'business_centers'
                                                  END,
                                                  '/',
                                                  IF(".$sys_tables['application_types'].".estate_type<5,
                                                      CONCAT(
                                                              CASE 
                                                                WHEN ".$sys_tables['application_types'].".rent=1 THEN 'rent'
                                                                WHEN ".$sys_tables['application_types'].".rent=2 THEN 'sell'
                                                              END,'/'
                                                             ),
                                                  '')
                                                )
                                            ) AS object_url,
                                            CASE
                                                WHEN ".$sys_tables['applications'].".status=1 THEN 'Завершена'
                                                WHEN ".$sys_tables['applications'].".status=2 THEN 'Новая'
                                                WHEN ".$sys_tables['applications'].".status=3 THEN 'В работе'
                                                WHEN ".$sys_tables['applications'].".status=4 THEN 'На модерации'
                                                WHEN ".$sys_tables['applications'].".status=5 THEN 'Не прошла модерацию'
                                                WHEN ".$sys_tables['applications'].".status=6 THEN 'Ожидание старта'
                                                WHEN ".$sys_tables['applications'].".status=8 THEN 'В архиве'
                                                WHEN ".$sys_tables['applications'].".status=9 THEN 'Нигде'
                                                WHEN ".$sys_tables['applications'].".status=10 THEN 'Отработанная'
                                            END AS status_title,
                                            ".$sys_tables['owners_user_types'].".title AS user_type_title,
                                            ".$sys_tables['work_statuses'].".title AS work_status_title,
                                            ".$sys_tables['applications'].".`status`,
                                            ".$sys_tables['applications'].".creation_datetime,
                                            DATE_FORMAT(".$sys_tables['applications'].".`creation_datetime`,'%e %M %k:%i') AS cdatetime_formatted,
                                            ".$sys_tables['applications'].".`datetime` AS date_normal,
                                            DATE_FORMAT(".$sys_tables['applications'].".`datetime`,'%e %M %k:%i') AS date,
                                            IF(".$sys_tables['application_objects'].".title IS NULL,'',".$sys_tables['application_objects'].".title) AS object_type_title,
                                            ".$sys_tables['applications'].".status,
                                            ".$sys_tables['application_types'].".rent,
                                            IF(".$sys_tables['application_types'].".rent=1,'Аренда','Покупка') AS rent,
                                            ".$sys_tables['applications'].".name,
                                            ".$sys_tables['applications'].".user_comment,
                                            IF(".$sys_tables['housing_estates'].".chpu_title IS NOT NULL,
                                               ".$sys_tables['housing_estates'].".chpu_title,
                                               IF(".$sys_tables['cottages'].".chpu_title IS NOT NULL,
                                               ".$sys_tables['cottages'].".chpu_title,
                                               IF(".$sys_tables['business_centers'].".chpu_title IS NOT NULL,
                                               ".$sys_tables['business_centers'].".chpu_title,''))) AS chpu_title,
                                            IF(".$sys_tables['applications'].".visible_to_all = 1 AND ".$sys_tables['applications'].".status = 2,
                                                ".$sys_tables['application_types'].".cost - 
                                                                                  FLOOR(
                                                                                  IF(".$sys_tables['applications'].".id_parent = 0,
                                                                                    CAST(TIMESTAMPDIFF(DAY,".$sys_tables['applications'].".`datetime`,NOW()) AS SIGNED),
                                                                                    CAST(TIMESTAMPDIFF(DAY,".$sys_tables['applications'].".`datetime`,DATE_SUB(NOW(),INTERVAL 12 HOUR)) AS SIGNED))*
                                                                                  ".$sys_tables['application_types'].".day_discount*0.01*".$sys_tables['application_types'].".cost + 
                                                                                  ".$sys_tables['applications'].".in_work_amount*
                                                                                  ".$sys_tables['application_types'].".client_discount*0.01*".$sys_tables['application_types'].".cost),
                                                0
                                              ) AS now_costs
                                    FROM ".$sys_tables['applications']."
                                    LEFT JOIN ".$sys_tables['work_statuses']." ON ".$sys_tables['applications'].".id_work_status = ".$sys_tables['work_statuses'].".id
                                    LEFT JOIN ".$sys_tables['owners_user_types']." ON ".$sys_tables['applications'].".id_user_type = ".$sys_tables['owners_user_types'].".id
                                    LEFT JOIN ".$sys_tables['applications']." a ON a.id_parent_app = ".$sys_tables['applications'].".id
                                    LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                                    LEFT JOIN ".$sys_tables['application_objects']." ON (".$sys_tables['application_objects'].".id = ".$sys_tables['applications'].".object_type OR
                                                                                        ".$sys_tables['applications'].".object_type = 0 AND ".$sys_tables['applications'].".id_parent!=0) AND
                                                                                       ".$sys_tables['application_objects'].".estate_type = ".$sys_tables['applications'].".estate_type
                                    LEFT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['applications'].".estate_type = 5 AND 
                                                                                    ".$sys_tables['applications'].".id_parent = ".$sys_tables['housing_estates'].".id
                                    LEFT JOIN ".$sys_tables['cottages']." ON ".$sys_tables['applications'].".estate_type = 6 AND 
                                                                             ".$sys_tables['applications'].".id_parent = ".$sys_tables['cottages'].".id
                                    LEFT JOIN ".$sys_tables['business_centers']." ON ".$sys_tables['applications'].".estate_type = 7 AND 
                                                                                     ".$sys_tables['applications'].".id_parent = ".$sys_tables['business_centers'].".id
                                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['applications'].".id_owner
                                    LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    ".(!empty($where)?" WHERE ".$where:"")."
                                    GROUP BY ".$sys_tables['applications'].".id
                                    ORDER BY ".$sys_tables['applications'].".`creation_datetime` DESC
                                    LIMIT ".$paginator->getLimitString($page,30));
        
        foreach($apps_list as $key=>$item){
            if(empty($item['estate_alias'])) continue;
            $apps_list[$key]['target_object_status'] = $db->fetch("SELECT published FROM ".$sys_tables[$item['estate_alias']]." WHERE id = ".$item['id_parent']);
            if(empty($apps_list[$key]['target_object_status']) && !empty($sys_tables[$item['estate_alias']."_archive"]))
                $apps_list[$key]['target_object_status'] = $db->fetch("SELECT published FROM ".$sys_tables[$item['estate_alias']."_archive"]." WHERE id = ".$item['id_parent']);
            
            $apps_list[$key]['target_object_status'] = (empty($apps_list[$key]['target_object_status'])?0:$apps_list[$key]['target_object_status']['published']);
            
        }                            
        
        Response::SetArray('list',$apps_list);
        
        
        //читаем список всех id пользователей которые присутствуют в качестве id_owner
        $owners_list = $db->fetchall("SELECT DISTINCT id_owner FROM ".$sys_tables['applications']);
        $user_ids = [];
        foreach($owners_list as $key=>$item)
            $user_ids[] = $item['id_owner'];
        $user_ids = implode(',',array_unique($user_ids));
        //читаем список агентств, у которых есть заявки
        $agencies_list = $db->fetchall("SELECT ".$sys_tables['users'].".id,
                                               ".$sys_tables['agencies'].".title
                                        FROM ".$sys_tables['agencies']."
                                        LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                        WHERE ".$sys_tables['users'].".id IN (".$user_ids.")
                                        ORDER BY title ASC");
        Response::SetArray('agencies_list',$agencies_list);
        if($paginator->pages_count>1) Response::SetArray('paginator', $paginator->Get($page));
        
        $total_found = $db->fetchall("SELECT * 
                                      FROM ".$sys_tables['applications']."
                                      LEFT JOIN ".$sys_tables['application_types']." ON ".$sys_tables['applications'].".application_type = ".$sys_tables['application_types'].".id
                                      LEFT JOIN ".$sys_tables['application_objects']." ON (".$sys_tables['application_objects'].".id = ".$sys_tables['applications'].".object_type OR
                                                                                        ".$sys_tables['applications'].".object_type = 0) AND
                                                                                       ".$sys_tables['application_objects'].".estate_type = ".$sys_tables['applications'].".estate_type
                                      ".(!empty($where)?" WHERE ".$where:"")."
                                      GROUP BY ".$sys_tables['applications'].".id");
        Response::SetInteger('total_found',count($total_found));
        
        
        Response::SetBoolean('can_edit', in_array($auth->id_group,array(10,101,105)) || $auth->id == 24382);
        
        $module_template = "admin.applications.list.html";
        break;
}

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));

?>