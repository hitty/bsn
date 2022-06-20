#!/usr/bin/php
<?php
    // переход в корневую папку сайта
    define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);    
    $root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
    if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
    if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
    define( "ROOT_PATH", $root );
    chdir(ROOT_PATH);
    require_once('includes/class.estate.php');
    include_once('cron/robot/robot_functions.php');    // функции  из крона
    
    mb_internal_encoding('UTF-8');
    setlocale(LC_ALL, 'ru_RU.UTF-8');
    mb_regex_encoding('UTF-8');

    if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 
    include('includes/class.config.php');       // Config (конфигурация сайта)
    Config::Init();
    require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
    Host::Init();
    include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
    include('includes/class.storage.php');      // Session, Cookie, Responce, Request
    include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
    require_once('includes/class.template.php');
    require_once('includes/class.email.php');
    $db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
    $db->query("set names ".Config::$values['mysql']['charset']);
    
    $tables = Config::$sys_tables;
    $sql = "SELECT
    `s`.`id`,
    `s`.`id_user`,
    `s`.`url`,
    `s`.`title`,
    `g`.value,
    `s`.`new_objects`,
    `s`.`last_seen`,
    `u`.`email`,
    DATEDIFF(NOW(),`s`.`last_decountryry`) AS days_pass
    FROM ".$tables['objects_subscriptions']." s
        LEFT JOIN ".$tables['objects_subscriptions_periods']." g
            ON s.`period`=g.`id`
        LEFT JOIN ".$tables['users']." u
            ON s.`id_user`=u.`id`
        WHERE s.`confirmed`=1";
    $subscriptions_list = $db->fetchall($sql);             // список подтвержденных подписок
    
    
    
    
    foreach($subscriptions_list as $subscr_ind => $subscription){     // цикл по всем подпискам
        $url = $subscription['url'];  
        $parsed_url = parse_url($url);     
        $params = explode('&',$parsed_url['query']);          
        $clauses = array();
        $parameters = $work_params_data = array();
        foreach($params as $k => $v){
            $param = explode('=',$v);
            $parameters[$param[0]] = $param[1];
        }
      
        // определяем тип недвижимости
        $estate_type = "";
        $estate_types = array('country','build','commercial','country','inter');
        foreach ($estate_types as $k => $v){
            if (strpos($url,$v)) $estate_type = $v;  
        }
          
        switch($estate_type){
            case 'inter':
                $estate = new EstateListInter();
                break;
            case 'build':
                $estate = new EstateListBuild();
                break;
            case 'commercial':
                $estate = new EstateListCommercial();
                break;
            case 'country':
                $estate = new EstateListCountry();
                break;
            case 'country':
                $estate = new EstateListcountry();
                break;
        }  
          
        // определяем тип сделки
        $deal_type = '';
        $deal_types = array('rent','sell');
        foreach ($deal_types as $k => $v){
            if (strpos($url,$v)) $deal_type = $v;  
        }
      
        $clauses = array();
        $clauses['published'] = array('value'=> 1);
        $clauses['rent'] = array('value'=> $deal_type=='rent'?1:2);
        if(!empty($parameters['subways'])) {
            $clauses['id_subway'] = array('set'=> explode(',',$parameters['subways']));
        }
        if(!empty($parameters['countries'])) {
            $clauses['id_country'] = array('set'=> explode(',',$parameters['countries']));  
        }
        if(!empty($parameters['streets'])) {
            $clauses['id_street'] = array('value'=>$parameters['streets']);
            $clauses['id_region'] = array('value'=>78);
            $clauses['id_area'] = array('value'=>0);         
        }
        if(!empty($parameters['rooms'])) {
            $clauses['rooms_sale'] = array('set'=> explode(',',$parameters['rooms'])); 
            $work_params_data['rooms_checked'] = array();
            $arr = array();  
            foreach($clauses['rooms_sale']['set'] as $val) {
                $work_params_data['rooms_checked'][$val] = 1;
                $arr[] = ($val>3 ? "4 и более" : $val); 
            }
        }
        if(!empty($parameters['obj_type'])) {
            $clauses['id_type_object'] = array('value'=> $parameters['obj_type']);
        }
        $suffix = "";
        if(!empty($parameters['currency'])) {
            switch($parameters['currency']){
                case 'rur': $suffix = "_rubles"; break;
                case 'eur': $suffix = "_euros"; break;
                default: $suffix = "_dollars";
            }
        }
        if(!empty($parameters['title'])) {
            $clauses['title'] = array('value'=> $parameters['title']);
        }
        if(!empty($parameters['developer'])) {
            $clauses['id_developer'] = array('value'=> $parameters['developer']);
        }

        $max_cost = Convert::ToInt(empty($parameters['max_cost'])?0:$parameters['max_cost']);
        $min_cost = Convert::ToInt(empty($parameters['min_cost'])?0:$parameters['min_cost']);
        if($max_cost || $min_cost) { 
            $clauses['cost'.$suffix] = array();
            if($min_cost) {         
                $clauses['cost'.$suffix]['from'] = $min_cost*1000;
            }
            if($max_cost) {            
                $clauses['cost'.$suffix]['to'] = $max_cost*1000; 
            }
        }
        if(!empty($parameters['with_photo'])) {
            $clauses['id_main_photo'] = array('from'=> 1);
        }
        if(!empty($parameters['agency'])) {
            $clauses['id_user'] = array('value'=> $parameters['agency']);
        }
        //ЖК 
        if(!empty($parameters['housing_estate'])) {
            $clauses['id_housing_estate'] = array('value'=> $parameters['housing_estate']);
        }
        //БЦ
        if(!empty($parameters['business_center'])) {
            $clauses['id_business_center'] = array('value'=> $parameters['business_center']);
        }
        //КП
        if(!empty($parameters['cottage'])) {
            $clauses['id_cottage'] = array('value'=> $parameters['cottage']);
        }
        if(!empty($elite)) {
            $clauses['elite'] = array('value'=> 1);
            $work_params_data['elite']  = 1;
        }
        if(!empty($parameters['by_the_day'])) {
            $clauses['by_the_day'] = array('value'=> 1);
        }

        // "прямые" условия
        $where = $estate->makeWhereClause($clauses);
        // добавление "особых" условий
        $reg_where = array();
        if(!empty($parameters['districts'])) {
            $districts_array = explode(',',$parameters['districts']);
            foreach($districts_array as $da_key=>$da_val) if(!Validate::isDigit($da_val)) unset($districts_array[$da_key]);
            if(!empty($districts_array)) {
                $reg_where[] = $estate->work_table.".`id_district` IN (".implode(',', $districts_array).")";
            }
        }
        if(!empty($parameters['district_areas'])) {
            $districts_array = explode(',',$parameters['district_areas']);
            foreach($districts_array as $da_key=>$da_val) if(!Validate::isDigit($da_val)) unset($districts_array[$da_key]);
            if(!empty($districts_array)) {
                $regions = $db->fetchall("SELECT id_region, id_area FROM ".$tables['geodata']." WHERE id IN (".implode(',', $districts_array).")");
                foreach($regions as $reg){
                    $reg_where[] = "(".$estate->work_table.".`id_region`=".$reg['id_region']." AND ".$estate->work_table.".`id_area`=".$reg['id_area'].")";
                }
            }
        }
        if(!empty($reg_where)) $where .= " AND (".implode(" OR ", $reg_where).") AND ".$estate->work_table.".`date_in`>'".$subscription['last_seen']."'";
        $list = $estate->Search($where);
        $sql = "UPDATE ".$tables['objects_subscriptions']." SET `new_objects`=? WHERE `id`=?";
        $db->query($sql,count($list),$subscription['id']); // Обновление поля с количеством новых объектов
        if (Convert::ToInt($subscription['days_pass'])>=Convert::ToInt($subscription['objects_subscriptions_periods'])){
            $mailer = new EMailer('mail'); // возможно, следует вынести за пределы цикла (до него)
            $estate_type_name = Config::$values['object_types'][$estate_type]['name']; 
            $estate_type = Config::$values['object_types'][$estate_type]['key'];
            if (in_array($estate_type,array(1,3,4))) $estate_type_name .= ' недвижимость';     // добавление слова "Недвижимость" в случае необходимости
            $deal_type_name = (strcmp($deal_type,'sell')==0)?"Покупка":"Аренда";
            
            Response::SetString('title', $subscription['title']);
            Response::SetString('url', $subscription['url']);
            $amount =  count($list);     // склонения
            $new_objects_text = $amount;
            if ($amount>=5 && $amount<=20)
                $new_objects_text .= " новых предложений";
            else {
                if ($amount>10)
                    $last_digit = $amount%10;
                else
                    $last_digit = $amount;
                if ($last_digit==1)
                    $new_objects_text .= " новое предложение";
                if ($last_digit>=2 && $last_digit<=4)
                    $new_objects_text .= " новые предложения"; 
                if ($last_digit>4 || $last_digit==0)
                    $new_objects_text .= " новых предложений";       
            }      
            Response::SetString('new_objects_text', $new_objects_text);
            Response::SetString('deal_type',$deal_type_name);
            Response::SetString('estate_type_name',$estate_type_name);
            Response::SetString('host',Host::$host);
            Response::SetString('url',Host::GetWebPath('/'));
            // формирование html-кода письма по шаблону
            $eml_tpl = new Template('/modules/objects_subscriptions/templates/subscription_mail.html');
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet, $html);
            // параметры письма
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Подписка на обновления '.Host::$host);
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags($html);
            $mailer->IsHTML(true);
            
            
            $mailer->AddAddress($subscription['email'], iconv('UTF-8',$mailer->CharSet, ""));
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = 'bsn.ru';                                                                
            if ($mailer->Send()) $sql = "UPDATE ".$tables['objects_subscriptions']." SET `last_decountryry`=NOW()";
        }
    }
?>
