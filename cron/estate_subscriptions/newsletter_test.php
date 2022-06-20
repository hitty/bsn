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
$db->query("set names ".Config::$values['mysql']['charset']);

$sys_tables = Config::$sys_tables;

/* TEST ONLY */$db->query("UPDATE ".$sys_tables['estate_subscriptions']." SET `last_delivery` = '2014-10-01 00:00:00', `last_seen` = '2014-10-01 00:00:00',`new_objects` = 10 WHERE `id_user` = 42378");

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
                               ".$sys_tables['estate_subscriptions'].".id_user,
                               ".$sys_tables['estate_subscriptions'].".email
                        FROM   ".$sys_tables['estate_subscriptions']."
                                LEFT JOIN  ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['estate_subscriptions'].".id_user
                        WHERE  ".$sys_tables['estate_subscriptions'].".confirmed = 1
                               AND ".$sys_tables['estate_subscriptions'].".id_user = 42378
                               AND ".$sys_tables['estate_subscriptions'].".new_objects > 0
                        GROUP BY id_user
");

 /* TEST ONLY USER ID=42378*/ 

if(!empty($users)){
    foreach($users as $u=>$user){
        // выборка подписок
        $list = $db->fetchall("SELECT 
                                       ".$sys_tables['estate_subscriptions'].".*
                                FROM   ".$sys_tables['estate_subscriptions']."
                                WHERE  ".$sys_tables['estate_subscriptions'].".confirmed = 1
                                       AND ".$sys_tables['estate_subscriptions'].".id_user = ".$user['id_user']."
                                       AND ".$sys_tables['estate_subscriptions'].".new_objects  > 0
        ");

        foreach($list as $k=>$item){
            preg_match("#estate\/([live|build|country|commercial|inter]{1,})\/#msi",$item['url'],$match);
            $estate_type = $match[1];
            switch($estate_type){
                case 'live': $estate_type_name = 'Жилая недвижимость'; break;
                case 'country': $estate_type_name = 'Загородная недвижимость'; break;
                case 'commercial': $estate_type_name = 'Коммерческая недвижимость'; break;
                case 'build': $estate_type_name = 'Строящаяся недвижимость'; break;
                case 'inter': $estate_type_name = 'Зарубежная недвижимость'; break;
            }
            $full_title = $estate_type_name.', '.($item['deal_type'] == 'sell' ? "Покупка" : "Аренда").". ".$item['title'];

            //выборка объектов
            $clauses = '';

            $deal_type = array ("rent" => '1', "sell" => 2);
            
            if (preg_match("/districts=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses = " AND `id_district` IN ( ".$matches[1]." )";
            if (preg_match("/subways=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses .= " AND `id_subway` IN ( ".$matches[1]." )";
            if (preg_match("/district_areas=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses .= " AND `id_area` IN ( ".$matches[1]." )";
            if (preg_match("/rooms=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses .= " AND `rooms_sale` IN ( ".$matches[1]." )";
            if (preg_match("/obj_type=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses .= " AND `id_type_object` = ".$matches[1];
            if (preg_match("/min_cost=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses .= " AND `cost` >= ".$matches[1];
            if (preg_match("/max_cost=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses .= " AND `cost` <= ".$matches[1];
            if (preg_match("/with_photo=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses .= " AND `id_main_photo` <> 0";
            if (preg_match("/by_the_day=(.*)(&|$)/U",$list[$k]['url'],$matches))
                $clauses .= " AND `by_the_day` = ".$matches[1];
            if (preg_match("/\/elite\//",$list[$k]['url']))
                $clauses .= " AND `elite` = 1";

            $objects_list = $db->fetchall("SELECT *,t1.id as `oid`,
                                t2.`name` as `photo_url`,
                                t3.`bntxt_value` as `subway_name`,
                                t4.`title` as `way_type`
                                FROM ".$sys_tables[$estate_type]." as t1 
                                LEFT JOIN ".$sys_tables[$estate_type.'_photos']." as t2 ON t1.id_main_photo = t2.id
                                LEFT JOIN ".$sys_tables['subways']." as t3 ON t1.id_subway = t3.id
                                LEFT JOIN ".$sys_tables['way_types']." as t4 ON t1.id_way_type = t4.id
                                WHERE t1.`published` = 1".$clauses." 
                                AND   t1.`rent` = ".$deal_type[$list[$k]['deal_type']]." 
                                AND   t1.`date_in`> '".$list[$k]['last_delivery']."'");
            
            if (!empty($objects_list)){
                
                $objects_list_count = count($objects_list);
                
                $new_objects = $objects_list_count.makeSuffix($objects_list_count, ' объект', array('','а','ов'));
                
                //обрезаем массив до 5 элементов
                $all_variant_link = 0;
                
                if ($objects_list_count > 5){
                    $objects_list = array_chunk($objects_list,5);
                    $objects_list = $objects_list[0];
                    $all_variant_link = 1;
                }
                
                array_walk($objects_list,'set_obj_adds');
                
                Response::SetArray('user',$user);
                Response::SetString('host','bsn.ru');
                Response::SetString('list_url',$list[$k]['url']);
                Response::SetString('subscription_id', $list[$k]['id']);
                Response::SetString('full_title', $full_title);
                Response::SetArray('list',$objects_list);
                Response::SetString('new_objects',$new_objects);
                Response::SetString('estate_type_str',$estate_type);
                Response::SetString('deal_type',$item['deal_type']);
                Response::SetInteger('all_variant_link',$all_variant_link);
                Response::SetInteger('lines_show',count($objects_list)-1);
                
                $mailer = new EMailer('mail');
                // формирование html-кода письма по шаблону
                $eml_tpl = new Template('/cron/estate_subscriptions/templates/subscription_mail_test.html');
                $html = $eml_tpl->Processing();
                $html = iconv('UTF-8', $mailer->CharSet, $html);
                // параметры письма
                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Новые предложения по вашим подпискам на BSN.ru');
                $mailer->Body = $html;
                $mailer->AltBody = strip_tags($html);
                $mailer->IsHTML(true);
                //$mailer->AddAddress($user['email'], iconv('UTF-8',$mailer->CharSet, ""));
                $mailer->AddAddress('matarm@bk.ru', iconv('UTF-8',$mailer->CharSet, ""));
                $mailer->AddAddress('web2@bsn.ru', iconv('UTF-8',$mailer->CharSet, ""));
                $mailer->AddAddress('matarmid@gmail.com', iconv('UTF-8',$mailer->CharSet, ""));
                echo "\r\nmail sent\r\n";
                $mailer->From = 'no-reply@bsn.ru';
                $mailer->FromName = 'bsn.ru';                                                                
                $mailer->Send();
                //if ($mailer->Send()) $db->query("UPDATE ".$sys_tables['estate_subscriptions']." SET `last_delivery` = NOW() WHERE id_user = ?",$user['id_user']);
            }
        }
    }
}
?>
