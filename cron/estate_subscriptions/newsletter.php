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
require_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');
require_once('includes/functions.php');
require_once('includes/class.email.php');
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);

$sys_tables = Config::$sys_tables;
Response::SetString('img_folder',Config::Get('img_folders/live'));

Response::SetString('mailer_title', 'Новые объекты по вашему запросу');                       
function set_obj_adds(&$item){ 
    //подсчет кол-ва фотографий объекта
    global $db, $sys_tables, $estate_type;
    $count = $db->fetch("SELECT COUNT('id_parent') as `cphotos`  FROM ".$sys_tables[$estate_type.'_photos']." WHERE id_parent = ? AND id_parent <> 0",$item['oid']);
    $item['photos_count'] = ($count['cphotos'] > 0) ? ($count['cphotos']-1) : ($count['cphotos']);
    //получаем имя района (если присутствует) 78- Петербург 47- ЛО
    if ($item['id_region'] == 78)
        $district_title = $db->fetch("SELECT title FROM ".$sys_tables['districts']." WHERE id= ?",$item['id_district']);
    if ($item['id_region'] == 47)
        $district_title = $db->fetch("SELECT offname as `title` FROM ".$sys_tables['geodata']." WHERE a_level = 2 AND id_region = 47 AND id_area = ?",$item['id_area']);
    $item['district_title'] = ($district_title) ? ($district_title['title']) : ('');
    //формируем url для картинки
    if (!empty($item['photo_url']))
        $item['photo_url'] = 'http://st.bsn.ru/img/uploads/med/'.substr($item['photo_url'],0 ,2 )."/".$item['photo_url'];
}
$users = $db->fetchall("SELECT 
                               CONCAT(".$sys_tables['users'].".lastname, ' ', ".$sys_tables['users'].".name) as name,
                               ".$sys_tables['objects_subscriptions'].".id_user,
                               ".$sys_tables['objects_subscriptions'].".email
                        FROM   ".$sys_tables['objects_subscriptions']."
                                LEFT JOIN  ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['objects_subscriptions'].".id_user
                        WHERE  ".$sys_tables['objects_subscriptions'].".confirmed = 1
                               AND ".$sys_tables['objects_subscriptions'].".id_user > 0
                               AND ".$sys_tables['objects_subscriptions'].".new_objects > 0
                        GROUP BY id_user
");   
if(!empty($users)){
    require_once('includes/class.estate.php');
    require_once('includes/class.estate.subscriptions.php');
    require_once('includes/class.paginator.php');
    foreach($users as $u=>$user){
        // выборка подписок
        $argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;
        $where = '';
        if(!empty($argc)) $where = " AND ".$sys_tables['objects_subscriptions'].".id = ".$argc; 
        $list = $db->fetchall("SELECT 
                                       ".$sys_tables['objects_subscriptions'].".*
                                FROM   ".$sys_tables['objects_subscriptions']."
                                WHERE  ".$sys_tables['objects_subscriptions'].".confirmed = 1
                                       AND ".$sys_tables['objects_subscriptions'].".id_user = ".$user['id_user']."
                                       AND ".$sys_tables['objects_subscriptions'].".new_objects  > 0
                                       ".$where."
        ");
        
        foreach($list as $k=>$item){

            preg_match("#(estate\/)?([live|build|country|commercial|inter]{1,})\/#msi",$item['url'],$match);
            $estate_type = end($match);
            if(empty($sys_tables[$estate_type])) continue;
            switch($estate_type){
                case 'live': $estate_type_name = 'Жилая недвижимость'; $estate = new EstateListLive(); break;
                case 'country': $estate_type_name = 'Загородная недвижимость'; $estate = new EstateListCountry(); break;
                case 'commercial': $estate_type_name = 'Коммерческая недвижимость'; $estate = new EstateListCommercial(); break;
                case 'build': $estate_type_name = 'Строящаяся недвижимость'; $estate = new EstateListBuild(); break;
                case 'inter': $estate_type_name = 'Зарубежная недвижимость'; $estate = new EstateListInter(); break;
            }
            //выборка объектов
            $clauses = '';

            $deal_type = array ("rent" => '1', "sell" => 2);
            
            $where = array();    
            $where[] = 
                     $sys_tables[$estate_type].".published = 1 AND
                     ".$sys_tables[$estate_type].".rent = ".$deal_type[$item['deal_type']]." AND
                     ".$sys_tables[$estate_type].".`date_in`> '".$item['last_delivery']."'";
                     
            $url = $item['url'];  
            $parsed_url = parse_url($url);     
            if(empty($parsed_url['query'])) continue;
            $params = explode('&',$parsed_url['query']);          
            $clauses = array();
            $parameters = $work_params_data = array();
            foreach($params as $kp => $vp){
                $param = explode('=',$vp);
                if(count($param) == 1) continue;
                $parameters[$param[0]] = $param[1];
            }
            
            $estate_search = new EstateSearch();
            list($parameters, $clauses, $get_parameters, $reg_where) = $estate_search->formParams($parameters, false, $estate_type, $item['deal_type']);

            $where_clauses = $estate->makeWhereClause($clauses);
            if(!empty($where_clauses)) $where[] = $where_clauses;
                     
            $where = implode(" AND ", $where);
            $paginator = new Paginator($sys_tables[$estate_type], 100, $where); 
            $objects_list = $estate->Search($where, 3, 0, $sys_tables[$estate_type].".date_in DESC ");

            if (!empty($objects_list)){
                //формирование заголовка подписки
                $parameters = array();
                $qry = explode('&', parse_url($item['url'])['query']);
                foreach($qry as $q) {
                    list($key,$val) = explode('=',$q.'=');
                    $parameters[$key] = $q;
                }
                EstateSubscriptions::Init('/'.$item['url']);
                
                $url = parse_url($item['url']);
                if(!empty($url['query'])) {
                    $url = $url['query'];
                    parse_str($url, $params);
                } else {
                    $params = $url;
                }

                Response::SetArray('description', EstateSubscriptions::getTitle(false, $params, false));

                Response::SetArray('list', $objects_list);
                
                Response::SetArray('user',$user);
                Response::SetString('host','bsn.ru');
                Response::SetString('list_url',$item['url']);
                Response::SetString('subscription_id', $item['id']);
                Response::SetString('new_objects',$paginator->items_count);
                Response::SetString('estate_type',$estate_type);
                Response::SetString('deal_type',$item['deal_type']);
                Response::SetInteger('lines_show',count($objects_list)-1);
                
                $mailer = new EMailer('mail');
                // формирование html-кода письма по шаблону
                $eml_tpl = new Template('/cron/estate_subscriptions/templates/subscription_mail.html');
                $html = $eml_tpl->Processing();
                $html = iconv('UTF-8', $mailer->CharSet, $html);
                // параметры письма
                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Новые объекты по вашему запросу на BSN.ru');
                $mailer->Body = $html;
                $mailer->AltBody = strip_tags($html);
                $mailer->IsHTML(true);
                $mailer->AddAddress($user['email'], iconv('UTF-8',$mailer->CharSet, ""));
                $mailer->From = 'no-reply@bsn.ru';
                $mailer->FromName = 'bsn.ru';                                                                
                if ($mailer->Send()) {
                    $db->querys("UPDATE ".$sys_tables['objects_subscriptions']." SET `last_delivery` = NOW() WHERE id_user = ?",$user['id_user']);
                    print_r($user['email']);
                }
            }
        }
    }
}
?>