#!/usr/bin/php
<?php
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 
//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/mailers/spam_error.log';
$test_performance = ROOT_PATH.'/cron/gen_sitemap/test_performance.log';
file_put_contents($error_log,'');
file_put_contents($test_performance,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.email.php');
require_once('includes/functions.php');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$bsn_url = "https://www.bsn.ru/";
$estate_types = array('live'=>30,'commercial'=>30,'country'=>30,'build'=>60);
$statuses_titles = array(1=>'Поднятие',3=>'Промо',4=>'Премиум',5=>'Платный объект',6=>'VIP');
$letters = array();
foreach($estate_types as $table=>$days) {
    $sql = "SELECT GROUP_CONCAT(CONCAT(?,'".$table."/',IF(".$sys_tables[$table].".rent = 1,'rent','sell'),'/',".$sys_tables[$table].".id,'/')) AS urls,
                  ".$sys_tables[$table].".status,
                  IF(".$sys_tables[$table].".raising_days_left != 0,
                     IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables[$table].".status,'+1'),'1'),
                     ".$sys_tables[$table].".status
                  ) AS status_full,
                  IF(".$sys_tables[$table].".raising_days_left != 0,
                     IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables['objects_statuses'].".title,'+Поднятие'),'Поднятие'),
                     ".$sys_tables['objects_statuses'].".title
                  ) AS status_title,
                  id_user,
                  (SUM(IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,1,0)*".$sys_tables['objects_statuses'].".cost) + 
                   SUM(IF(".$sys_tables[$table].".raising_days_left != 0,1,0)*150)) AS full_cost,
                   SUM(IF(".$sys_tables[$table].".raising_days_left != 0,1,0)*150) AS raising_cost,
                  ".$sys_tables['users'].".id AS user_id,
                  ".$sys_tables['users'].".`datetime`,
                  ".$sys_tables['users'].".name AS user_name,
                  ".$sys_tables['users'].".lastname AS user_lastname,
                  ".$sys_tables['users'].".balance,
                  ".$sys_tables['users'].".email AS user_email
           FROM ".$sys_tables[$table]."
           LEFT JOIN ".$sys_tables['objects_statuses']." ON ".$sys_tables[$table].".status = ".$sys_tables['objects_statuses'].".id
           LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables[$table].".id_user = ".$sys_tables['users'].".id
           WHERE ( (DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2) OR 
                   (".$sys_tables[$table].".raising_datetime != '0000-00-00' AND DATEDIFF(NOW(),".$sys_tables[$table].".raising_datetime) > 1 AND ".$sys_tables[$table].".raising_days_left = 1)
                 ) AND ".$sys_tables[$table].".published = 1 
           GROUP BY CONCAT(id_user,IF(".$sys_tables[$table].".raising_days_left != 0,
                     IF(DATEDIFF(status_date_end,NOW()) = 1 AND ".$sys_tables[$table].".status > 2,CONCAT(".$sys_tables[$table].".status,'+1'),'1'),
                     ".$sys_tables[$table].".status
                  ))
           ORDER BY ".$sys_tables[$table].".status ASC ";
    $list = $db->fetchall($sql,false,$bsn_url);
    if(empty($list)) continue;
    
    //накапливаем по пользователям
    foreach($list as $key=>$item){
        $item['objects_info'] = array($item['status_full'] => $item['urls']);
        $item['statuses'] = array($item['status_full'] => $item['status_title']);
        if(empty($users_objects[$item['id_user']])) $users_objects[$item['id_user']] = $item;
        else{
            if(empty($users_objects[$item['id_user']]['objects_info'][$item['status_full']])){
                $users_objects[$item['id_user']]['objects_info'][$item['status_full']] = $item['urls'];
                $users_objects[$item['id_user']]['statuses'][$item['status_full']] = $item['status_title'];
            }
            else{
                $users_objects[$item['id_user']]['objects_info'][$item['status_full']] .= ",".$item['urls'];
                $users_objects[$item['id_user']]['statuses'][$item['status_full']] = $item['status_title'];
            }
            $users_objects[$item['id_user']]["full_cost"] += $item['full_cost'];
        }
    }
}

//разбиваем двойные статусы
foreach($users_objects as $user_id=>$user_info){
    foreach($user_info['objects_info'] as $status=>$urls){
        if(strstr($status,'+')){
            $statuses = explode('+',$status);
            $status_titles = explode('+',$user_info['statuses'][$status]);
            foreach($statuses as $key=>$status_item){
                if(empty($users_objects[$user_id]['objects_info'][$status_item])){
                    $users_objects[$user_id]['objects_info'][$status_item] = $user_info['objects_info'][$status];
                    $users_objects[$user_id]['statuses'][$status_item] = $status_titles[$key];
                }else{
                    $users_objects[$user_id]['objects_info'][$status_item] .= ",".$user_info['objects_info'][$status];
                }
            }
            unset($users_objects[$user_id]['objects_info'][$status]);
            unset($users_objects[$user_id]['statuses'][$status]);
        }
    }
}

$discount = 30;

//отправляем письма не-агентствам
if(!empty($users_objects)){
    foreach($users_objects as $user_id=>$user_info){
        $eml_tpl = new Template('mail.user.objects_status_near_ending.html', 'cron/mailers/');
        foreach($user_info['objects_info'] as $status => $urls){
            $user_info['objects_info'][$status] = explode(',',$user_info['objects_info'][$status]);
            sort($user_info['objects_info'][$status]);
            
            if(empty($user_info['objects_ids'][$status])) $user_info['objects_ids'][$status] = array();
            foreach($user_info['objects_info'][$status] as $key=>$url){
                preg_match('/[0-9]+(?=\/$)/si',$url,$object_id);
                if(!empty($object_id) && !empty($object_id[0])) $user_info['objects_ids'][$status][] = $object_id[0];
            }
        } 
        
        Response::SetBoolean('can_prolongate',($user_info['balance'] >= $user_info['full_cost']));
        
        ///коды безопасности:
        $prolongate_link = "https://www.bsn.ru/estate_prolongate/?id=".$user_info['user_id']."&mail=".$user_info['user_email'];
        $user_code = "&user_code=".sha1(sha1($user_info['user_id'].$user_info['datetime'].date("dmY")));
        $objects_code = "&objects_code=".sha1(sha1(json_encode($user_info['objects_info']).date("dmY")));
        $prolongate_link .= $user_code.$objects_code;
        //скидка
        Response::SetString('discount',$discount);
        //значение скидки
        $discount_value = round($user_info['full_cost']*($discount/100.0));
        Response::SetString('discount_value',$discount_value);
        //ссылка:
        Response::SetString('prolongate_link',$prolongate_link);
        
        Response::SetInteger('prolongate_cost',$user_info['full_cost'] - $discount_value);
        Response::SetArray('statuses_titles',$statuses_titles);
        
        if(!empty($user_info['objects_ids'][1])){
            $user_info['objects_ids'][11] = $user_info['objects_ids'][1];
            unset($user_info['objects_ids'][1]);
            $user_info['objects_info'][11] = $user_info['objects_info'][1];
            $user_info['statuses'][11] = $user_info['statuses'][1];
            unset($user_info['objects_info'][1]);
        }
        ksort($user_info['objects_ids']);
        ksort($user_info['objects_info']);
        
        Response::SetArray('info',$user_info);
        $mailer = new EMailer('mail');
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        $mailer->Body = $html;
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $user_info['user_name'].', до окончания действия платных услуг на BSN.ru остался 1 день');
        $mailer->IsHTML(true);
        $mailer->AddAddress($user_info['user_email']);
        $mailer->AddAddress("web@bsn.ru");
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
        $mailer->Send();
    }
}
unset($users_objects);
?>
