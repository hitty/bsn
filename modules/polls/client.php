<?php
/*
ALTER TABLE  `users` ADD  `career` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  'Род деятельности, степень двойки из config.php' AFTER  `user_activity` ,
ADD INDEX (  `career` );
ALTER TABLE  `users` ADD  `id_region` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `user_activity` ,
ADD  `id_area` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `id_region` ,
ADD  `id_city` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `id_area` ,
ADD  `id_place` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `id_city` ,
ADD  `id_street` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `id_place` ,
ADD INDEX (  `id_region` ,  `id_area` ,  `id_city` ,  `id_place` ,  `id_street` );
ALTER TABLE  `users` ADD  `newsletters` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  'интересующие рассылки, степень 2-ки из конфига' AFTER  `career` ,
ADD INDEX (  `newsletters` );
*/
require_once('includes/class.paginator.php');
// мэппинги модуля

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

switch(true){
    //###########################################################################
    // опрос
    //###########################################################################
    case empty($action):
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['css_set'][] = '/modules/polls/style.css';
        $GLOBALS['js_set'][] = '/modules/polls/script.js';
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);        
        //регистрация/поиск пользователя
        if(!empty($post_parameters['email'])){
            $email = $post_parameters['email'];
            $user = $db->fetch("SELECT * FROM ".$sys_tables['users']." WHERE email = ?", $email);
            if(!empty($user)) {
                $auth->id = $user['id'];
                
                //восстановление пароля
                if(!empty($post_parameters['lostpassword'])){
                    // отправка кода на мыло
                    $mailer = new EMailer('mail');
                    $confirm_code = substr(md5(time()),-6);// данные пользователя для шаблона
                    Response::SetArray( "data", array('email'=>$email, 'code'=>$confirm_code) );
                    // данные окружения для шаблона
                    $env = array(
                        'url' => Host::GetWebPath('/'),
                        'host' => Host::$host,   
                        'ip' => Host::getUserIp(),
                        'datetime' => date('d.m.Y H:i:s')
                    );
                    Response::SetArray('env', $env);
                    // инициализация шаблонизатора
                    $eml_tpl = new Template('lostpassword_email.html', 'modules/members');
                    // формирование html-кода письма по шаблону
                    $html = $eml_tpl->Processing();
                    // перевод письма в кодировку мейлера
                    $html = iconv('UTF-8', $mailer->CharSet, $html);
                    // параметры письма
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Восстановление пароля на сайте '.Host::$host);
                    $mailer->Body = $html;
                    $mailer->AltBody = strip_tags($html);
                    $mailer->IsHTML(true);
                    $mailer->AddAddress($email, iconv('UTF-8',$mailer->CharSet, $user_row['name']));
                    $mailer->From = 'no-reply@bsn.ru';
                    $mailer->FromName = 'bsn.ru';
                    // попытка отправить
                    if($mailer->Send()) Response::SetString('lostpawword_success','email');  
                }
            } else {
                //регистрация пользователя
                $reg_passwd = substr(md5(time()),-6);
                $res = $db->querys("INSERT INTO ".$sys_tables['users']."
                                    (email,passwd,datetime,access)
                                   VALUES
                                    (?,?,NOW(),'')"
                                   , $email
                                   , sha1(sha1($reg_passwd))
                );
                $auth->id = $db->insert_id;
                $auth->AuthCheck($email, $reg_passwd);
                // отправка кода на мыло
                $mailer = new EMailer('mail');
                // данные пользователя для шаблона
                Response::SetArray( "data", array('email'=>$email, 'password'=>$reg_passwd) );
                // данные окружения для шаблона
                $env = array(
                    'url' => Host::GetWebPath('/'),
                    'host' => Host::$host,
                    'ip' => Host::getUserIp(),
                    'datetime' => date('d.m.Y H:i:s')
                );
                Response::SetArray('env', $env);
                // инициализация шаблонизатора
                $eml_tpl = new Template('/registration_email.html', 'modules/members');
                // формирование html-кода письма по шаблону
                $html = $eml_tpl->Processing();
                // перевод письма в кодировку мейлера
                $html = iconv('UTF-8', $mailer->CharSet, $html);
                // параметры письма
                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Регистрация на сайте '.Host::$host);
                $mailer->Body = $html;
                $mailer->AltBody = strip_tags($html);
                $mailer->IsHTML(true);
                 $mailer->AddAddress($email);
                $mailer->From = 'no-reply@bsn.ru';
                $mailer->FromName = 'bsn.ru';
                if($mailer->Send()) Response::SetString('registration_success','email');                
            }
            
            unset($post_parameters['email']);
        }
        if(empty($auth->id)) {
            Response::SetBoolean('registration', true);
            $info = $db->prepareNewRecord($sys_tables['users']);
        } else {
            $common = new Common();
            $info = $common->getUserById($auth->id);
        }

         // определение геоданных объекта
        $geodata = $db->fetchall("
            SELECT * FROM ".$sys_tables['geodata']."
            WHERE ( (a_level > 1 AND a_level < 5 AND id_region = 47 ) OR (a_level < 5 AND id_region = 78) ) AND (
                      (id_region=? AND id_area=? AND id_city=? AND id_place=?)
                   OR (id_region=? AND id_area=? AND id_city=? AND id_place=0)
                   OR (id_region=? AND id_area=? AND id_city=0 AND id_place=0)
                   OR (id_region=? AND id_area=0 AND id_city=0 AND id_place=0)
               )
            ORDER BY a_level"
            , false
            , $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place']
            , $info['id_region'], $info['id_area'], $info['id_city']
            , $info['id_region'], $info['id_area']
            , $info['id_region']
        );
        $geolocation = $location = array();
        while(!empty($geodata)){
            $location = array_shift($geodata);
            if(empty($geodata)) {
                $mapping['polls']['geo_id']['value'] = $location['id'];
                $mapping['polls']['txt_region']['value'] = $location['shortname_cut'].'. '.$location['offname'];
            }  else  $geolocation[] = $location['offname'].' '.$location['shortname'];
        }
        $mapping['polls']['geolocation']['value'] = implode(', ',$geolocation);
        //определение улицы
        if(!empty($info['id_street'])) {
            $street = $db->fetch("
                SELECT `offname`, `shortname` FROM ".$sys_tables['geodata']."
                WHERE a_level = 5 AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                $info['id_region'], $info['id_area'], $info['id_city'], $info['id_place'], $info['id_street']
            );
            $info['txt_street'] = $street['offname'].' '.$street['shortname'];
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field){
            if(!empty($mapping['polls'][$key])) $mapping['polls'][$key]['value'] = $info[$key];
        }
        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit_form'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //поиск адреса
            if(!empty($post_parameters['geo_id'])){
                $geo = $db->fetch("SELECT * FROM ".$sys_tables['geodata']." WHERE id = ?", $post_parameters['geo_id']);
                if(!empty($geo)){
                    $post_parameters['id_region'] = $geo['id_region'];
                    $post_parameters['id_area'] = $geo['id_area'];
                    $post_parameters['id_place'] = $geo['id_place'];
                    $post_parameters['id_city'] = $geo['id_city'];
                }
            }

            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)                         
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping['polls'][$key]) && !empty($mapping['polls'][$key]['fieldtype']) && $mapping['polls'][$key]['fieldtype']=='checkbox_set') {
                    if(!empty($post_parameters[$key.'_set'])){
                        $mapping['polls'][$key]['value'] = 0;
                        foreach($post_parameters[$key.'_set'] as $pkey=>$pval){
                            $mapping['polls'][$key]['value'] += pow(2,$pkey-1);
                        }
                        $post_parameters[$key] = trim($mapping['polls'][$key]['value']);
                    }
                }
                $mapping['polls'][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping['polls']);
            //если ошибок нет, объединяем с предыдущей формой
            if(empty($errors)){
                foreach($info as $key=>$field){
                    if(isset($mapping['polls'][$key]['value'])) $info[$key] = $mapping['polls'][$key]['value'];
                }
                $res = $db->updateFromArray($sys_tables['users'], $info, 'id');
                if(!empty($res)){
                    $promocode = $db->fetch("SELECT * FROM ".$sys_tables['promocodes']." WHERE `id` = ?", 17);
                    Response::SetArray('promocode', $promocode);
                }
                Response::SetBoolean('form_submit', true);
                Response::SetBoolean('saved', true);
            }
        }
        // запись данных для отображения на странице
        Response::SetArray('data_mapping', $mapping['polls']);
        //
        Response::SetArray('info',$info);
        
        //заглавная страница
        $h1 = empty($this_page->page_seo_h1) ? "Опрос" : $this_page->page_seo_h1;
        Response::SetString('h1',$h1);
        $module_template = 'item.html';
        break;
}
?>