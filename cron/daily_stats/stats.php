#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);
echo $root;

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

/*
* Обработка новых объектов
*/ 
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.context_campaigns.php');
require_once('includes/class.template.php');
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;


//log для письма
$log = array();
$res = true;

$error_log = ROOT_PATH.'/cron/daily_stats/stats.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

//---------- СТАТИСТИКА СПЕЦПРЕДЛОЖЕНИЙ, ОБЩАЯ ----------------------
//подсчет статистики кликов по телефону
$res = $res && $db->query("INSERT INTO ".$sys_tables['phone_clicks_full']." ( id_parent,id_object,amount,date, type, status)  SELECT id_parent, id_object, count(*), CURDATE() - INTERVAL 1 DAY, type, status  FROM  ".$sys_tables['phone_clicks_day']." GROUP BY  id_object, status ");
$res = $res && $db->query("TRUNCATE ".$sys_tables['phone_clicks_day']."");
$res = $res && $db->query("TRUNCATE ".$sys_tables['phone_clicks_day_checker']."");

$log['phones_stats'] = "Статистика кликов по телефону: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

$ids = $db->fetch("SELECT GROUP_CONCAT(id) as ids FROM ".$sys_tables['tgb_banners']." WHERE published = 1 AND enabled = 1 AND credit_clicks = 1")['ids'];
if( !empty( $ids ) ) 
    $res = $res && $db->query("INSERT INTO ".$sys_tables['tgb_banners_credits_stats']."  ( id_parent,amount,clicks_amount,date)  
                           SELECT 
                                id_banner, 
                                day_limit,
                                (SELECT  IFNULL(COUNT(*),0) as cnt FROM ".$sys_tables['tgb_stats_day_clicks']." WHERE ".$sys_tables['tgb_banners_credits'].".id_banner = ".$sys_tables['tgb_stats_day_clicks'].".id_parent) as clicks_amount,
                                CURDATE() - INTERVAL 1 DAY  
                           FROM  ".$sys_tables['tgb_banners_credits']." 
                           WHERE id_banner IN (".$ids.") 
                           GROUP BY  id_banner ");
$res = $res && $db->query("INSERT INTO ".$sys_tables['tgb_stats_full_shows']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_stats_day_shows']."  GROUP BY  id_parent ");
$res = $res && $db->query("INSERT INTO ".$sys_tables['tgb_stats_full_clicks']." ( id_parent,amount,date,`from`, position)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `from`, position  FROM  ".$sys_tables['tgb_stats_day_clicks']." GROUP BY  id_parent, `from`, position ");
$res = $res && $db->query("TRUNCATE ".$sys_tables['tgb_stats_day_shows']."");
$res = $res && $db->query("TRUNCATE ".$sys_tables['tgb_stats_day_clicks']."");

$log['tgb_stats'] = "Статистика для тгб: ".((!$res)?$db->error:"OK")."<br />";
$res = true;


$res = true;
//обновление лимита для кликов для менеджеров
if(date('j')==1) {
    $res = $res && $db->query("UPDATE ".$sys_tables['managers']." SET naydidom_credit_limit = month_naydidom_credit_limit, pingola_credit_limit = month_pingola_credit_limit WHERE bsn_manager = 1");
    $log['managers_click_limit'] = "Статистика лимита для кликов для менеджеров: ".((!$res)?$db->error:"OK")."<br />";
    $res = true;
}

// Статистика для объекто недвижимости - ЖК, КП, БЦ
$res = $res  && $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_full_shows']."  ( id_parent,amount,date, type)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, type  FROM  ".$sys_tables['estate_complexes_stats_day_shows']."  GROUP BY  id_parent, type ");
$res = $res  && $db->query("INSERT INTO ".$sys_tables['estate_complexes_stats_full_clicks']." ( id_parent,amount,date, type)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, type  FROM  ".$sys_tables['estate_complexes_stats_day_clicks']." GROUP BY  id_parent, type ");
$res = $res  && $db->query("TRUNCATE ".$sys_tables['estate_complexes_stats_day_shows']."");
$res = $res  && $db->query("TRUNCATE ".$sys_tables['estate_complexes_stats_day_clicks']."");
$log['eo_stats'] = "Статистика для объектов недвижимости: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

// Статистика для баннеров - Спонсор района
$res = $res && $db->query("INSERT INTO ".$sys_tables['district_banners_stats_full_shows']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['district_banners_stats_day_shows']."  GROUP BY  id_parent ");
$res = $res && $db->query("INSERT INTO ".$sys_tables['district_banners_stats_full_clicks']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['district_banners_stats_day_clicks']." GROUP BY  id_parent ");
$res = $res && $db->query("TRUNCATE ".$sys_tables['district_banners_stats_day_shows']."");
$res = $res && $db->query("TRUNCATE ".$sys_tables['district_banners_stats_day_clicks']."");
$log['banner_stats_sponsor'] = "Статистика для баннеров - Спонсор района: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

// Статистика для баннеров - overlay
$res = $res && $db->query("INSERT INTO ".$sys_tables['tgb_overlay_stats_full_shows']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_overlay_stats_day_shows']."  GROUP BY  id_parent ");
$res = $res && $db->query("INSERT INTO ".$sys_tables['tgb_overlay_stats_full_clicks']." ( id_parent,amount,date,type)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type`  FROM  ".$sys_tables['tgb_overlay_stats_day_clicks']." GROUP BY  id_parent, type ");
$res = $res && $db->query("TRUNCATE ".$sys_tables['tgb_overlay_stats_day_shows']."");
$res = $res && $db->query("TRUNCATE ".$sys_tables['tgb_overlay_stats_day_clicks']."");

$log['tgb_overlay_stats'] = "Статистика для баннера overlay: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

// Статистика для баннеров - вертикальное
$res = $res && $db->query("INSERT INTO ".$sys_tables['tgb_vertical_stats_full_shows']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_vertical_stats_day_shows']."  GROUP BY  id_parent ");
$res = $res && $db->query("INSERT INTO ".$sys_tables['tgb_vertical_stats_full_clicks']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM  ".$sys_tables['tgb_vertical_stats_day_clicks']." GROUP BY  id_parent ");
$res = $res && $db->query("TRUNCATE ".$sys_tables['tgb_vertical_stats_day_shows']."");
$res = $res && $db->query("TRUNCATE ".$sys_tables['tgb_vertical_stats_day_clicks']."");
$log['banner_stats_vertical'] = "Статистика для баннеров - вертикальное: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

// Статистика для баннеров - Кредитный калькулятор
$res = $res && $db->query("INSERT INTO ".$sys_tables['credit_calculator_stats_show_full']."  ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['credit_calculator_stats_show_day']."  GROUP BY  id_parent, `type`  ");
$res = $res && $db->query("INSERT INTO ".$sys_tables['credit_calculator_stats_click_full']." ( id_parent,amount,date,`type`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `type` FROM  ".$sys_tables['credit_calculator_stats_click_day']." GROUP BY  id_parent, `type` ");
$res = $res && $db->query("TRUNCATE ".$sys_tables['credit_calculator_stats_show_day']."");
$res = $res && $db->query("TRUNCATE ".$sys_tables['credit_calculator_stats_click_day']."");
$log['banner_stats_cc'] = "Статистика для баннеров - Кредитный калькулятор: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

// Статистика для Баннеров Адривера
$res = $res && $db->query("INSERT INTO ".$sys_tables['banners_stats_click_full']." ( id_parent,amount,date,`from`)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `from` FROM  ".$sys_tables['banners_stats_click_day']." GROUP BY  id_parent, `from` ");
$res = $res && $db->query("TRUNCATE ".$sys_tables['banners_stats_click_day']."");
$log['banners_stats'] = "Статистика для Баннеров Адривера: ".((!$res)?$db->error:"OK")."<br />";
$res = true;



// Статистика для Метки
$res = $res && $db->query("INSERT INTO ".$sys_tables['markers_stats_show_full']."  ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY FROM  ".$sys_tables['markers_stats_show_day']."  GROUP BY  id_parent ");
$res = $res && $db->query("INSERT INTO ".$sys_tables['markers_stats_click_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY FROM  ".$sys_tables['markers_stats_click_day']." GROUP BY  id_parent ");
$res = $res && $db->query("TRUNCATE ".$sys_tables['markers_stats_show_day']."");
$res = $res && $db->query("TRUNCATE ".$sys_tables['markers_stats_click_day']."");
$log['mark_stats'] = "Статистика для Метки: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//Статистика для Спецпредложений
$res = $res && $db->query("INSERT INTO ".$sys_tables['spec_objects_stats_show_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['spec_objects_stats_show_day']." GROUP BY  id_parent");
$res = $res && $db->query("INSERT INTO ".$sys_tables['spec_objects_stats_click_full']." ( id_parent,amount,date,`from`) SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY, `from`  FROM ".$sys_tables['spec_objects_stats_click_day']." GROUP BY  id_parent, `from`");
$res = $res && $db->query("TRUNCATE ".$sys_tables['spec_objects_stats_show_day']);
$res = $res && $db->query("TRUNCATE ".$sys_tables['spec_objects_stats_click_day']);

$res = $res && $db->query("INSERT INTO ".$sys_tables['spec_packets_stats_show_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['spec_packets_stats_show_day']." GROUP BY  id_parent");
$res = $res && $db->query("INSERT INTO ".$sys_tables['spec_packets_stats_click_full']." ( id_parent,amount,date) SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['spec_packets_stats_click_day']." GROUP BY  id_parent");
$res = $res && $db->query("TRUNCATE ".$sys_tables['spec_packets_stats_click_day']);
$res = $res && $db->query("TRUNCATE ".$sys_tables['spec_packets_stats_show_day']);
$log['specoffers_stats'] = "Статистика для Спецпредложений: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//клики и просмотры по контекстным блокам
$res = $res && $db->query("INSERT INTO ".$sys_tables['context_stats_show_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['context_stats_show_day']." GROUP BY  id_parent");
$res = $res && $db->query("INSERT INTO ".$sys_tables['context_stats_click_full']." ( id_parent,amount,date) SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['context_stats_click_day']." GROUP BY  id_parent");
$res = $res && $db->query("TRUNCATE ".$sys_tables['context_stats_click_day']);
$res = $res && $db->query("TRUNCATE ".$sys_tables['context_stats_show_day']);
$log['context_clicks_shows'] = "Клики и просмотры по контекстным блокам: ".((!$res)?$db->error:"OK")."<br />";
$res = $db->query("UPDATE ".$sys_tables['context_campaigns']." SET published = 1 WHERE DATE(`date_start`) = CURDATE()");
$log['context_auto_start'] = "Старт кампаний по дате начала: ".((!$res)?$db->error:"OK")."<br />";
$res = true;
 //обновляем флаг редактирования для агентств
 $db->query("UPDATE ".$sys_tables['agencies']." SET can_change_time = 1");
 //клики и просмотры по агентствам на главной
$res = $db->query("INSERT INTO ".$sys_tables['agencies_mainpage_stats_show_full']." ( id_parent,amount,date)  SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['agencies_mainpage_stats_show_day']." GROUP BY  id_parent");
$res = $res && $db->query("INSERT INTO ".$sys_tables['agencies_mainpage_stats_click_full']." ( id_parent,amount,date) SELECT id_parent, count(*), CURDATE() - INTERVAL 1 DAY  FROM ".$sys_tables['agencies_mainpage_stats_click_day']." GROUP BY  id_parent");
$res = $res && $db->query("TRUNCATE ".$sys_tables['agencies_mainpage_stats_click_day']);
$res = $res && $db->query("TRUNCATE ".$sys_tables['agencies_mainpage_stats_show_day']);
$log['agencies_mainpage_clicks_shows'] = "Клики и просмотры по агентствам на главной ".((!$res)?$db->error:"OK")."<br />";
                                                     
//убираем в архив контекстные рекламные кампании (вместе со всеми объявлениями), срок действия которых закончился
//читаем список кампаний, которые будем убирать, чтобы оповестить их и менеджеров
$finishing_campaigns = $db->fetchall("SELECT ".$sys_tables['context_campaigns'].".title,
                                             ".$sys_tables['users'].".id AS user_id,
                                             ".$sys_tables['agencies'].".id AS agency_id,
                                             IF(".$sys_tables['agencies'].".email IS NULL,".$sys_tables['users'].".email,".$sys_tables['agencies'].".email) AS agency_email,
                                             IF(".$sys_tables['agencies'].".title IS NULL,".$sys_tables['users'].".name,".$sys_tables['agencies'].".title) AS agency_title,
                                             ".$sys_tables['managers'].".id AS manager_id,
                                             ".$sys_tables['managers'].".name AS manager_name,
                                             ".$sys_tables['managers'].".email AS manager_email
                                      FROM ".$sys_tables['context_campaigns']."
                                      LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['context_campaigns'].".id_user
                                      LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                      LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['agencies'].".id_manager = ".$sys_tables['managers'].".id
                                      WHERE ".$sys_tables['context_campaigns'].".date_end<=NOW() AND ".$sys_tables['context_campaigns'].".published = 1");
$res = $res && $db->query("UPDATE ".$sys_tables['context_advertisements']." SET published = 2 WHERE id_campaign IN (SELECT id FROM ".$sys_tables['context_campaigns']." WHERE date_end<NOW())");
$res = $res && $db->query("UPDATE ".$sys_tables['context_campaigns']." SET published = 2 WHERE date_end<NOW()");
$log['context_archivate'] = "Уход в архив контекстных штук: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//оповещаем менеджеров и компании
if(!empty($finishing_campaigns)){
    $managers_list = array();
    //сначала набираем списки для компаний
    foreach($finishing_campaigns as $item){
        $agencies_list[$item['user_id']]['campaigns_titles'][] = $item['title'];
        $agencies_list[$item['user_id']]['agency_title'] = $item['agency_title'];
        $agencies_list[$item['user_id']]['agency_email'] = $item['agency_email'];
        $agencies_list[$item['user_id']]['manager_id'] = $item['manager_id'];
        $agencies_list[$item['user_id']]['manager_name'] = $item['manager_name'];
        $agencies_list[$item['user_id']]['manager_email'] = $item['manager_email'];
    }
    
    //рассылаем уведомления компаниям и заполняем списки для менеджеров
    foreach($agencies_list as $item){
        //если заканчивается сразу несколько рекламных кампаний, запоминаем это
        if(count($item['campaigns_titles'])>1)$item['multiple'] = true;
        if(!empty($item['manager_email'])){
            $managers_list[$item['manager_id']]['cmp_list'][] = $item;
            $managers_list[$item['manager_id']]['manager_email'] = $item['manager_email'];
            $managers_list[$item['manager_id']]['manager_name'][] = $item['manager_name'];
        }
        unset($item['agency_title']);
        contextCampaigns::Notification(5,$item,true,false);
    }
    //рассылаем уведомления менеджерам
    foreach($managers_list as $item){
        if(!empty($item['manager_email'])) contextCampaigns::Notification(5,$item,false,true);
    }
}


//снятие актуальности с акций 
$active_promotions = $db->fetchall("SELECT * FROM ".$sys_tables['promotions']." WHERE ( `date_end` <= CURDATE() OR `date_start` > CURDATE() ) AND published = 1");
foreach($active_promotions as $k=>$promotion){
    $estate_type = $db->fetch("SELECT `type` FROM ".$sys_tables['estate_types']." WHERE id = ?", $promotion['id_estate_type']);
    $res = $res && $db->query("UPDATE ".$sys_tables[$estate_type['type']]." SET status = ?, status_date_end = '0000-00-00', id_promotion = 0 WHERE id_promotion = ?", 2, $promotion['id_estate_type']);
    $res = $res && $db->query("UPDATE ".$sys_tables['promotions']." SET `published` = 3 WHERE id = ?", $promotion['id']);
}
//простановка актуальности акциям
$res = $res && $db->query("UPDATE ".$sys_tables['promotions']." SET `published` = 1 WHERE `date_start` <= CURDATE() AND `date_end` > CURDATE() AND published = 3");
$log['promotion_arch'] = "Снятие актуальности с акций просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//снятие актуальности с баннеров - Спонсор района просрочивших дату показа
$res = $res && $db->query("UPDATE ".$sys_tables['district_banners']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['sponsor_arch'] = "Снятие актуальности с баннеров - Спонсор района просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//снятие актуальности с баннеров - вертикальные просрочивших дату показа
$res = $res && $db->query("UPDATE ".$sys_tables['tgb_vertical']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['vertical_arch'] = "Снятие актуальности с баннеров - вертикальные просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//снятие актуальности с б кредитных калькуляторов просрочивших дату показа
$res = $res && $db->query("UPDATE ".$sys_tables['credit_calculator']." SET `enabled`=2, `published`=2 WHERE `date_end` <= CURDATE() and enabled=1");
$log['cc_arch'] = "Снятие актуальности с кредитных калькуляторов просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//обновление времени кредитного клика для попандеровских кликов
$res = $res && $db->query("UPDATE ".$sys_tables['tgb_banners']." SET `credit_time` = '00:00:00'");
$log['tgb_credit_time'] = "Обновление времени кредитного клика для попандеровских кликов: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//снятие актуальности с ТГБ просрочивших дату показа
$res = $res && $db->query("UPDATE ".$sys_tables['tgb_banners']." SET `enabled`=2, `published`=2, `clicks_limit` = 0, `credit_clicks` = 2, clicks_limit_notification = 1 WHERE `date_end` <= CURDATE() and enabled=1");
$log['tgb_arch'] = "Снятие актуальности с ТГБ просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
$res = true;


////////////////////////////////////////////////////////////////////////////////////////////////
// Перенос просмотров, попаданий в результаты поиска и переходов с поиска в соответствующие таблицы
////////////////////////////////////////////////////////////////////////////////////////////////     
$estate_types = array('live','commercial','country','build');
foreach($estate_types as $key=>$item){
    //просмотры карточек
    $res = $res && $db->query("INSERT INTO ".$sys_tables[$item.'_stats_show_full']." (id_user, id_parent, amount, `date`)
                                SELECT id_user, id, views_count AS amount, (CURDATE() - INTERVAL 1 DAY) AS `date`
                                FROM ".$sys_tables[$item]."
                                WHERE published = 1 AND views_count>0
                                GROUP BY ".$sys_tables[$item].".id");
    //накапливаем недельные просмотры. если наступил понедельник - стираем их
    if(date('w') == 1) $res = $res && $db->query("UPDATE ".$sys_tables[$item]." SET views_count_week=0 WHERE published=1");
    else $res = $res && $db->query("UPDATE ".$sys_tables[$item]." SET views_count_week=views_count+views_count_week WHERE published=1");
    $res = $res && $db->query("UPDATE ".$sys_tables[$item]." SET views_count=0 WHERE published=1");
    
    //попаданий в поиск
    $res = $res && $db->query("INSERT INTO ".$sys_tables[$item.'_stats_search_full']." (id_user, id_parent, amount, `date`)
                                SELECT id_user, id, search_count AS amount, (CURDATE() - INTERVAL 1 DAY) AS `date`
                                FROM ".$sys_tables[$item]."
                                WHERE published = 1 AND search_count>0
                                GROUP BY ".$sys_tables[$item].".id");
    $res = $res && $db->query("UPDATE ".$sys_tables[$item]." SET search_count=0 WHERE published=1");
    
    //переходов с поиска
    $res = $res && $db->query("INSERT INTO ".$sys_tables[$item.'_stats_from_search_full']." (id_user, id_parent, amount, `date`)
                                SELECT id_user, id, from_search_count AS amount, (CURDATE() - INTERVAL 1 DAY) AS `date`
                                FROM ".$sys_tables[$item]."
                                WHERE published = 1 AND from_search_count>0
                                GROUP BY ".$sys_tables[$item].".id");
    $res = $res && $db->query("UPDATE ".$sys_tables[$item]." SET from_search_count=0 WHERE published=1");
}
$log['daily_views'] = "Запись просмотров, попаданий в результаты поиска и переходов с поиска: ".((!$res)?$db->error:"OK")."<br />";



//ТАРИФ пользователя
//автопродление
$tarif_renewal = $db->fetchall("SELECT ".$sys_tables['users'].".*,
                                        DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%e.%m.%y') as renewal_date,
                                        ".$sys_tables['tarifs'].".id AS id_tarif,
                                        ".$sys_tables['tarifs'].".title,
                                        ".$sys_tables['tarifs'].".cost,
                                        ".$sys_tables['tarifs'].".premium_available,
                                        ".$sys_tables['tarifs'].".promo_available,
                                        ".$sys_tables['tarifs'].".vip_available,
                                        ".$sys_tables['tarifs'].".payed_page
                                 FROM ".$sys_tables['users']."
                                 LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users'].".id_tarif
                                 WHERE ".$sys_tables['users'].".id_tarif > 0 AND 
                                        `tarif_end`<=CURDATE() AND 
                                       ".$sys_tables['users'].".tarif_renewal = 1 AND
                                       ".$sys_tables['users'].".balance >= ".$sys_tables['tarifs'].".cost 
                                        "
);
if(!empty($tarif_renewal)) {
    foreach($tarif_renewal as $k=>$item){
        
        //вписываем данные в финансы
        $db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ?, promo_left = ?, premium_left = ?, vip_left = ?, 
                                                    tarif_start = NOW(), tarif_end = CURDATE() + INTERVAL 1 MONTH, 
                                                    payed_page = ".$item['payed_page'].", id_user_type = 2 WHERE id = ?",
                    $item['cost'], $item['promo_available'], $item['premium_available'], $item['vip_available'], $item['id']);
        //запись в финансы
        $db->query("INSERT INTO ".$sys_tables['users_finances']." SET expenditure = ?, id_user = ?, obj_type = ?, id_parent = ?", 
                    $item['cost'], $item['id'], 'tarif', $item['id_tarif']);
        
        //отправка письма пользователю
        if(!empty($item['email']) && Validate::isEmail($item['email'])){
            Response::SetArray('item', $item);
            $eml_tpl = new Template('mail.tarif.renewal.html', 'cron/daily_stats/');
            $mailer = new EMailer('mail');
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Автопродление тарифа специалиста на BSN.ru");
            $mailer->IsHTML(true);
            $mailer->AddAddress($item['email']);
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
            // попытка отправить
            $mailer->Send();
        }
    }
}
//снятие актуальности с тарифа пользователя
//читаем список пользователей, у которых заканчивается тариф
$users_endtarif = $db->fetchall("SELECT ".$sys_tables['users'].".id
                                 FROM ".$sys_tables['users']."
                                 WHERE ".$sys_tables['users'].".id_tarif > 0 AND `tarif_end`<=CURDATE()",'id');
if(!empty($users_endtarif)){
    $users_endtarif = implode(',',array_keys($users_endtarif));
    //список пользователей для отчета
    $users_titles = $db->fetch("SELECT GROUP_CONCAT( IF(".$sys_tables['agencies'].".title IS NULL,
                                                        CONCAT('пользователь #',".$sys_tables['users'].".id),
                                                        CONCAT('компания ',".$sys_tables['agencies'].".title,' (#',".$sys_tables['users'].".id,')' )) 
                                                    ) AS titles
                                   FROM ".$sys_tables['users']."
                                   LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                   WHERE ".$sys_tables['users'].".id IN (".$users_endtarif.")")['titles'];
    //убираем тариф у пользователя, не возвращаем ему тип "пользователь", убираем флаг платной страницы
    $res = $res && $db->query("UPDATE ".$sys_tables['users']." SET `id_tarif`=0, `promo_left`=0, `premium_left`=0, `vip_left` = 0, tarif_start = '0000-00-00', tarif_end = '0000-00-00', payed_page = 2 WHERE id IN (".$users_endtarif.")");
    //все тарифные объекты в архив (если статус оплачен - не трогаем)                               
    $res = $res && $db->query("UPDATE ".$sys_tables['build']." SET published = 2, status = 2, status_date_end = '0000-00-00' WHERE id_user IN (".$users_endtarif.") AND payed_status = 2");
    $res = $res && $db->query("UPDATE ".$sys_tables['live']." SET published = 2, status = 2,status_date_end = '0000-00-00' WHERE id_user IN (".$users_endtarif.") AND payed_status = 2");
    $res = $res && $db->query("UPDATE ".$sys_tables['commercial']." SET published = 2, status = 2,status_date_end = '0000-00-00' WHERE id_user IN (".$users_endtarif.") AND payed_status = 2");
    $res = $res && $db->query("UPDATE ".$sys_tables['country']." SET published = 2, status = 2,status_date_end = '0000-00-00' WHERE id_user IN (".$users_endtarif.") AND payed_status = 2");
    $users_emails = $db->fetchall("SELECT ".$sys_tables['users'].".email,
                                            ".$sys_tables['tarifs'].".title
                                     FROM ".$sys_tables['users']."
                                     LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users'].".id_tarif
                                     WHERE ".$sys_tables['users'].".id IN (".$users_endtarif.")"
    );
    if(!empty($users_emails)){
        foreach($users_emails as $k=>$item){
            //отправка письма пользователю
            if(!empty($item['email']) && Validate::isEmail($item['email'])){
                Response::SetArray('item', $item);
                $eml_tpl = new Template('mail.tarif.end.html', 'cron/daily_stats/');
                $mailer = new EMailer('mail');
                $html = $eml_tpl->Processing();
                $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);                                                 
                // параметры письма
                $mailer->Body = $html;
                $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Действие тарифа BSN.ru приостановлено");
                $mailer->IsHTML(true);
                $mailer->AddAddress($item['email']);
                $mailer->From = 'no-reply@bsn.ru';
                $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
                // попытка отправить
                $mailer->Send();
            }
        }
    }

}
$log['tarif_arch'] = "Снятие актуальности с тарифа пользователя: ".((!$res)?$db->error:"OK")."<br />";
if(!empty($users_endtarif)) $log['tarif_arch_users'] = "Закончился тариф и ушли в архив объекты у пользователей: ".$users_titles.".<br />";
$res = true;

//---------- Окончания срока действия тарифа у агентств ----------------------
//читаем список агентств, у которых заканчивается тариф
$list = $db->fetchall("SELECT 
                        ".$sys_tables['agencies'].".id,
                        CONCAT('компания ',".$sys_tables['agencies'].".title,' (#',".$sys_tables['agencies'].".id,')' ) as titles,
                        ".$sys_tables['agencies'].".email,
                        ".$sys_tables['agencies'].".email_service,
                        ".$sys_tables['agencies'].".business_center,
                        ".$sys_tables['agencies'].".tarif_expenditures,
                        ".$sys_tables['users'].".email as user_email,
                        ".$sys_tables['users'].".id as id_user,
                        IF(".$sys_tables['agencies'].".id_tarif = 1,".$sys_tables['agencies'].".tarif_cost,".$sys_tables['tarifs_agencies'].".cost) AS tarif_cost
                     FROM ".$sys_tables['agencies']."
                     LEFT JOIN ".$sys_tables['tarifs_agencies']." ON ".$sys_tables['agencies'].".id_tarif = ".$sys_tables['tarifs_agencies'].".id
                     RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id AND ".$sys_tables['users'].".agency_admin = 1
                     LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                     WHERE ".$sys_tables['agencies'].".id_tarif > 0 AND ".$sys_tables['agencies'].".`tarif_end`<=CURDATE()
                     GROUP BY ".$sys_tables['agencies'].".id");
if(!empty($list)){
    foreach($list as $k=>$item){
        
        //снятие актуальности с офисов БЦ
        if($item['business_center'] == 1){
            //список всех офисов БЦ
              require_once('includes/class.business_centers.php');
              $business_center = new BusinessCenters();
              $bc = $business_center->getLevelsList(100, $sys_tables['business_centers'].".id_user = ".$item['id_user'], false, $sys_tables['business_centers_levels'].".id_parent");
              if(!empty($bc)){
                  $ids = array();
                  foreach($bc as $k=>$item) $ids[] = $item['id'];
                  $db->query("UPDATE ".$sys_tables['business_centers_offices']." SET id_renter = 0, status = 2, date_rent_start = '0000-00-00', date_rent_start = '0000-00-00' WHERE id_parent IN (".implode(", ", $ids).")");
              }
        }
    }

}

//снятие актуальности со спецух просрочивших дату показа
$res = $res && $db->query("UPDATE ".$sys_tables['spec_offers_objects']." SET `base_page_flag`=2, `first_page_flag`=2 , `first_page_head_flag`=2 , `inestate_flag`=2 WHERE `date_end` <= CURDATE()");
$res = $res && $db->query("UPDATE ".$sys_tables['spec_offers_packets']." SET `base_page_flag`=2, `first_page_flag`=2 , `first_page_head_flag`=2 , `inestate_flag`=2 WHERE `date_end` <= CURDATE()");
$log['specoffers_arch'] = "Снятие актуальности со спецпредложений, просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//перевод в обычный статус ЖК  просрочивших дату показа
$res = $res && $db->query("UPDATE ".$sys_tables['housing_estates']." SET `advanced`=2 WHERE (`date_end` <= CURDATE() OR `date_start` > CURDATE()) and advanced=1");
$log['he_normalize'] = "Перевод в обычный статус ЖК  просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
//перевод в расширенный если между дат 
$res = $res && $db->query("UPDATE ".$sys_tables['housing_estates']." SET `advanced`=1 WHERE (`date_end` > CURDATE() AND `date_start` <= CURDATE())");
$log['he_advanced'] = "Перевод в расширенный если между дат: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//перевод в обычный статус КП  просрочивших дату показа
$res = $res && $db->query("UPDATE ".$sys_tables['cottages']." SET `advanced`=2 WHERE (`date_end` <= CURDATE() OR `date_start` > CURDATE()) and advanced=1");
$log['cottages_normalize'] = "Перевод в обычный статус КП  просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
//перевод в расширенный если между дат 
$res = $res && $db->query("UPDATE ".$sys_tables['cottages']." SET `advanced`=1 WHERE (`date_end` > CURDATE() AND `date_start` <= CURDATE())");
$log['cottages_advanced'] = "Перевод в расширенный если между дат: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//перевод в обычный статус БЦ просрочивших дату показа
$res = $res && $db->query("UPDATE ".$sys_tables['business_centers']." SET `advanced`=2 WHERE (`date_end` <= CURDATE() OR `date_start` > CURDATE()) and advanced=1");
$log['bc_normalize'] = "Перевод в обычный статус БЦ просрочивших дату показа: ".((!$res)?$db->error:"OK")."<br />";
//перевод в расширенный если между дат 
$res = $res && $db->query("UPDATE ".$sys_tables['business_centers']." SET `advanced`=1 WHERE (`date_end` > CURDATE() AND `date_start` <= CURDATE())");
$log['bc_advanced'] = "Перевод в расширенный если между дат: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

// среднее кол-во показов для ТГБ в разделе
$types = array(1=>array(1,2,3),2=>array(6,7,14),3=>array(4,5));
foreach($types as $k=>$v){

    $res = $res && $db->query("INSERT INTO  ".$sys_tables['spec_offers_daily_show_stats']." (amount, date, type)
    SELECT AVG(amount) as amount,  date as date, ".$k." as type FROM ".$sys_tables['spec_objects_stats_show_full']." 
    WHERE id_parent IN ( SELECT id FROM ".$sys_tables['spec_offers_objects']." WHERE id_category IN (".implode(",",$v).") AND inestate_flag=1 )
    AND date = CURDATE() - INTERVAL 1 DAY");
    $res = $res && $db->query("INSERT INTO  ".$sys_tables['spec_offers_daily_click_stats']." (amount, date, type)
    SELECT AVG(amount) as amount,  date as date, ".$k." as type FROM ".$sys_tables['spec_objects_stats_click_full']." 
    WHERE id_parent IN ( SELECT id FROM ".$sys_tables['spec_offers_objects']." WHERE id_category IN (".implode(",",$v).") AND inestate_flag=1 )
    AND date = CURDATE() - INTERVAL 1 DAY");
}
$log['avg_tgb'] = "среднее кол-во показов для ТГБ в разделе: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

//запись кол-ва показов в месяц в начале каждого месяца
if(date('j')==1) {
    $res = $res && $db->query("INSERT INTO ".$sys_tables['spec_offers_monthly_show_stats']." (amount, date, `type`) SELECT SUM( amount ) AS amount, CURDATE() - INTERVAL 1 DAY AS date, `type` FROM ".$sys_tables['spec_offers_daily_show_stats']." WHERE date_format(date, '%Y%m') = date_format(date_add(now(), interval -1 month), '%Y%m') GROUP BY `type`");
    $res = $res && $db->query("INSERT INTO ".$sys_tables['spec_offers_monthly_click_stats']." (amount, date, `type`) SELECT SUM( amount ) AS amount, CURDATE() - INTERVAL 1 DAY AS date, `type` FROM ".$sys_tables['spec_offers_daily_click_stats']." WHERE date_format(date, '%Y%m') = date_format(date_add(now(), interval -1 month), '%Y%m') GROUP BY `type`");
    $log['avg_shows_month'] = "запись кол-ва показов в месяц в начале каждого месяца: ".((!$res)?$db->error:"OK")."<br />";
    $res = true;
}


//установка дежурного менеджера БСН по обработке жалоб (25052015 добавлено условие, исключающее из распределения контент-менеджера БСН)
$duty_manager = $db->fetch("(
                                SELECT id 
                                FROM ".$sys_tables['managers']." 
                                WHERE bsn_manager = 1 AND content_manager = 2 AND id >  ( SELECT id FROM ".$sys_tables['managers']." WHERE duty = 1 ) 
                                ORDER BY id ASC
                            ) UNION (
                                SELECT id 
                                FROM ".$sys_tables['managers']." 
                                WHERE bsn_manager = 1 AND content_manager = 2
                                ORDER BY id ASC
                            )
");                                  
$res = $res && $db->query("UPDATE ".$sys_tables['managers']." SET duty = 2 WHERE duty = 1");
$res = $res && $db->query("UPDATE ".$sys_tables['managers']." SET duty = 1 WHERE id = ?", $duty_manager['id']);
$log['working_manager'] = "установка дежурного менеджера БСН: ".((!$res)?$db->error:"OK")."<br />";
$res = true;


//---------- Обнуление поля случайной сортировки ----------------------
$estate_types = array('country','live','commercial','build');
foreach($estate_types as $estate_type) {
    $res = $res && $db->query("UPDATE ".$sys_tables[$estate_type]." SET rand_order=0 ");
}
$log['rand_order_nullify'] = "Обнуление поля случайной сортировки: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Подсчет ежедневной статистики объектов личного кабинета
////////////////////////////////////////////////////////////////////////////////////////////////     
//date_in >= CURDATE() - INTERVAL 1 DAY     DATE_ADD(CURDATE(), INTERVAL -2 day)
$estate_types = array('live','build','commercial','country');
foreach($estate_types as $key=>$estate_type){
    $res = $res && $db->query("INSERT INTO ".$sys_tables['cabinet_stats']." (`date`, estate_type, deal_type, status, amount)
                SELECT DATE_ADD(CURDATE(), INTERVAL -1 day) AS `date`, ".($key+1)." AS estate_type, deal_type, status, amount FROM
                (
                SELECT 1 AS deal_type, 2 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 1 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 2 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 1 AS deal_type, 3 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 1 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 3 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 1 AS deal_type, 4 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 1 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 4 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 1 AS deal_type, 5 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 1 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 5 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 2 AS deal_type, 2 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 2 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 2 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 2 AS deal_type, 3 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 2 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 3 AND 
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 2 AS deal_type, 4 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 2 AND 
                      ".$sys_tables[$estate_type].".published = 1 AND 
                      ".$sys_tables[$estate_type].".info_source = 1 AND 
                      ".$sys_tables[$estate_type].".status = 4 AND
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                UNION
                SELECT 2 AS deal_type, 5 AS status,  COUNT(*) AS amount
                FROM ".$sys_tables[$estate_type]."
                WHERE ".$sys_tables[$estate_type].".rent = 2 AND
                      ".$sys_tables[$estate_type].".published = 1 AND
                      ".$sys_tables[$estate_type].".info_source = 1 AND
                      ".$sys_tables[$estate_type].".status = 5 AND
                      date_in >= CURDATE() - INTERVAL 1 DAY AND
                      date_in < CURDATE()
                ) as a
        ");
}
$log['daily_stats'] = "Подсчет ежедневной статистики объектов личного кабинета: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Чистим статистику объектов, которых нет в основных таблицах
////////////////////////////////////////////////////////////////////////////////////////////////
$estate_types = array('live','build','commercial','country');
foreach($estate_types as $key=>$estate_type){
    //читаем все id_parent,которые будем убирать, чтобы не читать несколько раз в запросах
    $ids_to_clear = $db->fetchall("SELECT DISTINCT ".$sys_tables[$estate_type."_stats_show_full"].".id_parent
                                   FROM ".$sys_tables[$estate_type."_stats_show_full"]."
                                   LEFT JOIN ".$sys_tables[$estate_type]." ON ".$sys_tables[$estate_type."_stats_show_full"].".id_parent = ".$sys_tables[$estate_type].".id 
                                   WHERE ".$sys_tables[$estate_type].".id IS NULL",'id_parent');
    $ids_to_clear = implode(',',array_keys($ids_to_clear));
    if(empty($ids_to_clear)) continue;
    echo $res;
    $res = $res && $db->query("DELETE FROM ".$sys_tables[$estate_type."_stats_show_full"]." WHERE id_parent IN (".$ids_to_clear.")");
    $res = $res && $db->query("DELETE FROM ".$sys_tables[$estate_type."_stats_search_full"]." WHERE id_parent IN (".$ids_to_clear.")");
    $res = $res && $db->query("DELETE FROM ".$sys_tables[$estate_type."_stats_from_search_full"]." WHERE id_parent IN (".$ids_to_clear.")");
    
}
$log['daily_stats'] = "Чистка статистики объектов которых нет в основных таблицах: ".((!$res)?$db->error:"OK")."<br />";
$res = true;


//----------- СТАТИСТИКА ПОДПИСАВШИХСЯ И ОТПИСАВШИХСЯ ПОЛЬЗОВАТЕЛЕЙ

$res = $res && $db->query("INSERT INTO ".$sys_tables['subscribed_users_stats']." (subscribed, date, unsubscribed)
SELECT subscribed, s.date, unsubscribed FROM ( 
           SELECT SUM(ss.cnt) as subscribed, ss.date FROM (
               (SELECT COUNT(*) as cnt, CURDATE() - INTERVAL 1 DAY AS date FROM ".$sys_tables['subscribed_users']." WHERE published=1) 
               UNION 
               (SELECT COUNT(*) as cnt, CURDATE() - INTERVAL 1 DAY AS date FROM ".$sys_tables['users']." WHERE subscribe_news = 1) 
           )  ss
) s 
LEFT JOIN (
           SELECT SUM(kk.cnt) as unsubscribed, kk.date FROM (
               (SELECT COUNT(*) as cnt, CURDATE() - INTERVAL 1 DAY AS date FROM ".$sys_tables['subscribed_users']." WHERE published = 2) 
               UNION 
               (SELECT COUNT(*) as cnt, CURDATE() - INTERVAL 1 DAY AS date FROM ".$sys_tables['users']." WHERE subscribe_news = 2) 
           ) kk
) k ON s.date = k.date" );
$log['subs_unsubs_stats'] = "Статистика подписавшихся и отписавшихся пользователей: ".((!$res)?$db->error:"OK")."<br />";
$res = true;
             
//---------- СТАТИСТИКА РЕКЛАМНЫХ АГЕНТСТВ  ----------------------
$adv_agencies = $db->fetchall("SELECT u.id as id_user FROM ".$sys_tables['users']." u
                      LEFT JOIN ".$sys_tables['agencies']." a ON a.id=u.id_agency 
                      WHERE a.activity & 2 AND a.`id`!=4472"); //выборка всех кроме недвижимости города
if(!empty($adv_agencies)){
  foreach($adv_agencies as $k => $agency){
    $res = $res && $db->query("INSERT INTO ".$sys_tables['billing']." (external_id, bsn_id, date, type, bsn_id_user, status, adv_agency)
                SELECT external_id, bsn_id, date, type, bsn_id_user, status, 1 FROM
                (
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'live' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['live']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']." AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'build' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['build']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']." AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'commercial' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['commercial']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']." AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'country' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['country']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']." AND info_source > 1 AND info_source != 4
                 ) as a
        ");
    }
    $log['aa_stats'] = "Статистика рекламных агентств: ".((!$res)?$db->error:"OK")."<br />";
    $res = true;
}

//---------- СТАТИСТИКА РЕКЛАМНЫХ АГЕНТСТВ  ----------------------
$adv_agencies = $db->fetchall("SELECT u.id as id_user, a.id as id_agency FROM ".$sys_tables['users']." u
                      LEFT JOIN ".$sys_tables['agencies']." a ON a.id=u.id_agency 
                      WHERE a.activity & 2 AND a.`id`!=4472"); //выборка всех кроме недвижимости города
                      
//---------- СТАТИСТИКА ОСТАЛЬНЫХ АГЕНТСТВ У КОТОРЫХ ЕСТЬ ВЫДЕЛЕННЫЕ СТРОКИ   (кроме Н-Маркета и Индустрии)----------------------
$ids = array();
foreach($adv_agencies as $k=>$value) {
    $ids[] = $value['id_agency'];
}
$agencies = $db->fetchall("SELECT u.id as id_user FROM ".$sys_tables['users']." u
                      LEFT JOIN ".$sys_tables['agencies']." a ON a.id=u.id_agency 
                      WHERE a.id NOT IN(".implode(',',$ids).") AND a.`id`!=4472  AND a.id>1"); //выборка всех кроме недвижимости города
if(!empty($agencies)){
  foreach($agencies as $k => $agency){
    $res = $res && $db->query("INSERT INTO ".$sys_tables['billing']." (external_id, bsn_id, date, type, bsn_id_user, status, adv_agency)
                SELECT external_id, bsn_id, date, type, bsn_id_user, status, 2  FROM
                (
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'live' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['live']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND (status > 2 OR elite=1)  AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'live' as type, `id_user` as bsn_id_user, 99 FROM ".$sys_tables['live']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND status = 2 AND elite = 2  AND info_source > 1 AND info_source != 4 AND rent = 1
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'build' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['build']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND (status > 2 OR elite=1)  AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'commercial' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['commercial']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND (status > 2 OR elite=1)  AND info_source > 1 AND info_source != 4
                    UNION
                    SELECT external_id, id as bsn_id, CURDATE() - INTERVAL 1 DAY AS date, 'country' as type, `id_user` as bsn_id_user, IF(elite=1,5,status) as status FROM ".$sys_tables['country']." WHERE external_id>0 AND published=1 AND id_user=".$agency['id_user']."  AND (status > 2 OR elite=1)  AND info_source > 1 AND info_source != 4
                 ) as a
        ");
    }
    $log['aa_stats'] = "Статистика остальных рекламных агентств, у которых есть выделенные строки: ".((!$res)?$db->error:"OK")."<br />";
    $res = true;
}

//---------- КОРРЕКТИРОВКА СТАТИСТИКИ JCAT  ----------------------
//теперь отдельно плюсуем значения, потому что считается максимум за сутки, а не то что к вечеру как у всех
$jcat_max_values = $db->fetch("SELECT MAX(live_rent) + MAX(live_sell) AS live,
                                      MAX(live_rent_promo) + MAX(live_sell_promo) AS live_promo,
                                      MAX(live_rent_premium) + MAX(live_sell_premium) AS live_premium,
                                      MAX(live_rent_vip) + MAX(live_sell_vip) AS live_vip,
                                      MAX(build) AS build_sell,
                                      MAX(build_promo) AS build_sell_promo,
                                      MAX(build_premium) AS build_sell_premium,
                                      MAX(build_vip) AS build_sell_vip,
                                      MAX(commercial_rent) + MAX(commercial_sell) AS commercial,
                                      MAX(commercial_rent_promo) + MAX(commercial_sell_promo) AS commercial_promo,
                                      MAX(commercial_rent_premium) + MAX(commercial_sell_premium) AS commercial_premium,
                                      MAX(commercial_rent_vip) + MAX(commercial_sell_vip) AS commercial_vip,
                                      MAX(country_rent) + MAX(country_sell) AS country,
                                      MAX(country_rent_promo) + MAX(country_sell_promo) AS country_promo,
                                      MAX(country_rent_premium) + MAX(country_sell_premium) AS country_premium,
                                      MAX(country_rent_vip) + MAX(country_sell_vip) AS country_vip
                              FROM ".$sys_tables['processes']."
                              WHERE id_agency = 4467 AND DATEDIFF(NOW(),datetime_start) = 1
                              GROUP BY id_agency");
//смотрим биллинг по JCAT и по необходимости добиваем значения
$jcat_id_user = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE id_agency = 4467 AND agency_admin = 1");
$jcat_id_user = (empty($jcat_id_user)?0:$jcat_id_user['id']);
$estate_types = array('live','commercial','country','build');
if(!empty($jcat_id_user)){
    //читаем то что в биллинге
    $jcat_billing = $db->fetchall("SELECT CONCAT(type,'_',status) AS type,COUNT(*) AS amount
                                   FROM ".$sys_tables['billing']."
                                   WHERE DATEDIFF(NOW(),`date`) = 1 AND bsn_id_user = ?
                                   GROUP BY type,status",'type',$jcat_id_user);
    foreach($estate_types as $key=>$estate_type){
        //смотрим максимальные значения по процессам
        $jcat_max_sum = array();
        $jcat_max_sum[$estate_type.'_2'] = $jcat_max_values[$estate_type];
        $jcat_max_sum[$estate_type.'_3'] = $jcat_max_values[$estate_type."_promo"];
        $jcat_max_sum[$estate_type.'_4'] = $jcat_max_values[$estate_type."_premium"];
        $jcat_max_sum[$estate_type.'_6'] = $jcat_max_values[$estate_type."_vip"];
        
        //добавляем в биллинг строчки
        foreach($jcat_max_sum as $key=>$value){
            $jcat_billing[$key] = (empty($jcat_billing[$key]['amount']) ? 0 : $jcat_billing[$key]['amount']);
            $date = new DateTime();
            $date->sub(new DateInterval('P1D'));
            while($jcat_billing[$key] < $value){
                if(isnertLineIntoBilling($jcat_id_user, $date->format('Y-m-d')." 00:00:00",$estate_type, preg_replace('/[^0-9]/','',$key)))++$jcat_billing[$key];
            }
        }
    }
}
$log['jcat_billing'] = "Корректировка биллинга JCAT: ".((!$res)?$db->error:"OK")."<br />";
unset($jcat_max_values);
unset($jcat_max_sum);
unset($jcat_billing);

$estate_types = array('live'=>30,'commercial'=>30,'country'=>30,'build'=>60);

////////////////////////////////////////////////////////////////////////////////////////////////
// Теперь сумма со счета объекта не снимается, только проверяется время 
// раньше было (Снятие суммы со счета   и простановка  объектов в архив)
////////////////////////////////////////////////////////////////////////////////////////////////     

//отправляем оповещения об окончании действия услуги
require_once('cron/mailers/send_ending_stats.php');

foreach($estate_types as $table=>$days) {
    //удаление всех болванок
    //$res = $res && $db->query("DELETE FROM  ".$sys_tables[$table]." WHERE published = 5");
    
    //снимаем закончившееся выделение с объектов
    $res = $res && $db->query("UPDATE ".$sys_tables[$table]." SET status = 2, status_date_end = '0000-00-00' WHERE status>2 AND status_date_end < CURDATE()");
    
    //убираем в архив объекты у которых истекло 30 дней
    $res = $res && $db->query("UPDATE ".$sys_tables[$table]." SET published = 2, status = 2, status_date_end = '0000-00-00' WHERE published = 1 AND `date_change` < (CURDATE() - INTERVAL ".$days." day)");
}
$log['finances'] = "Снятие суммы со счета и простановка  объектов в архив: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Очитска дневной статистики для объектов, оказавшихся в архиве
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $db->query("UPDATE ".$sys_tables['build']." SET views_count = 0, views_count_week = 0, search_count = 0, from_search_count = 0 WHERE published=2");
$res = $res && $db->query("UPDATE ".$sys_tables['live']." SET views_count = 0, views_count_week = 0, search_count = 0, from_search_count = 0 WHERE published=2");
$res = $res && $db->query("UPDATE ".$sys_tables['commercial']." SET views_count = 0, views_count_week = 0, search_count = 0, from_search_count = 0 WHERE published=2");
$res = $res && $db->query("UPDATE ".$sys_tables['country']." SET views_count = 0, views_count_week = 0, search_count = 0, from_search_count = 0 WHERE published=2");
$log['clear_archive_stats'] = "Очистка дневной статистики для архивных: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Убираем в архив заявки, которые старше 5 дней
////////////////////////////////////////////////////////////////////////////////////////////////     
$res = $res && $db->query("UPDATE ".$sys_tables['applications']." SET status = 8 WHERE DATEDIFF(NOW(),".$sys_tables['applications'].".`datetime`) >= 5 AND visible_to_all = 1 AND status = 2");
$log['apps_archive'] = "Убирание в архив заявок старше 5 дней: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Переносим статистику роботов из суточной в общую
////////////////////////////////////////////////////////////////////////////////////////////////

$crawlers = Config::$values['crawlers_aliases'];
foreach($crawlers as $key=>$item){
    $res = $res && $db->query("INSERT INTO ".$sys_tables['pages_visits_'.$item.'_full']." (`date`,visits_amount,links_shown,old_pages_visits,pages_added) VALUES
                               (CURDATE() - INTERVAL 1 DAY,
                               (SELECT COUNT(*) AS visits_amount FROM  ".$sys_tables['pages_visits_'.$item.'_day']."),
                               (SELECT SUM(shown_today) AS links_shown FROM ".$sys_tables['pages_not_indexed_'.$item]."),
                               (SELECT COUNT(*) AS old_pages_visits 
                                FROM ".$sys_tables['pages_visits_'.$item.'_day']."
                                LEFT JOIN ".$sys_tables['pages_not_indexed_'.$item]." ON ".$sys_tables['pages_visits_'.$item.'_day'].".id_page_in_stack = ".$sys_tables['pages_not_indexed_'.$item].".id
                                WHERE DATEDIFF(NOW(),date_out) = 1 AND bot_visits_total > 1),
                               (SELECT COUNT(*) AS pages_added FROM ".$sys_tables['pages_not_indexed_'.$item]." WHERE DATEDIFF(NOW(),date_out) = 1))");
    $res = $res && $db->query("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET shown_today = 0");
    $res = $res && $db->query("TRUNCATE ".$sys_tables['pages_visits_'.$item.'_day']);
    
    //раз в месяц чистим переходы с поиска и показанные страницы
    if(date('j') == 1){
        $db->query("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET googletm = '0000-00-00 00:00:00' WHERE DATEDIFF(NOW(),googletm)>30");
        $db->query("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET yandextm = '0000-00-00 00:00:00' WHERE DATEDIFF(NOW(),yandextm)>30");
        $db->query("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET mailrutm = '0000-00-00 00:00:00' WHERE DATEDIFF(NOW(),mailrutm)>30");
        $db->query("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET has_shown = 0");
    }
}
//$res = $res && $db->query("INSERT INTO ".);
$log['apps_archive'] = "Статистика поисковых роботов: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Накапливаем статистику карточек консультанта
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $db->query("UPDATE ".$sys_tables['consults']." SET views_count = views_count + views");
$res = $res && $db->query("UPDATE ".$sys_tables['consults']." SET views = 0");
$log['consult_items'] = "Статистика карточек консультанта: ".((!$res)?$db->error:"OK")."<br />";
$res = true;
////////////////////////////////////////////////////////////////////////////////////////////////
// Накапливаем статистику карточек вебинаров
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $db->query("UPDATE ".$sys_tables['webinars']." SET views_count = views_count + views");
$res = $res && $db->query("UPDATE ".$sys_tables['webinars']." SET views = 0");
$log['webinar_items'] = "Статистика карточек вебинаров: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// за 3 дня до окончания тарифа агентства оповещаем компанию и ответственного менеджера, 
// круглое число месяцев с назначения тарифа - списание с баланса
////////////////////////////////////////////////////////////////////////////////////////////////
$agencies_list = $db->fetchall("SELECT ".$sys_tables['agencies'].".id_tarif,
                                       ".$sys_tables['agencies'].".tarif_start,
                                       ".$sys_tables['agencies'].".tarif_end,
                                        CONCAT(DATE_FORMAT(".$sys_tables['agencies'].".tarif_end,'%d'),'.',DATE_FORMAT(NOW(),'%m'),'.',DATE_FORMAT(NOW(),'%Y')) AS formatted_actualized_tarif_end,
                                       ".$sys_tables['agencies'].".tarif_cost,
                                       ".$sys_tables['tarifs_agencies'].".title AS tarif_title,
                                       ".$sys_tables['agencies'].".title AS agency_title,
                                       ".$sys_tables['agencies'].".id AS agency_id,
                                       ".$sys_tables['agencies'].".email AS agency_email,
                                       ".$sys_tables['agencies'].".tarif_expenditures,
                                       ".$sys_tables['managers'].".name AS manager_name,
                                       ".$sys_tables['managers'].".email AS manager_email,
                                       ".$sys_tables['users'].".id as id_user,
                                       ".$sys_tables['users'].".name as user_name,
                                       ".$sys_tables['users'].".lastname as user_lastname, 
                                       ".$sys_tables['users'].".email as user_email,
                                       IF(DATEDIFF(str_to_date( CONCAT(DATE_FORMAT(NOW(),'%Y-%m-'),DATE_FORMAT(str_to_date(".$sys_tables['agencies'].".`tarif_start`,'%Y-%m-%d'),'%d')),'%Y-%m-%d' ),NOW()) = 3,1,0) AS 3_before_end,
                                       IF(DATEDIFF(str_to_date( CONCAT(DATE_FORMAT(NOW(),'%Y-%m-'),DATE_FORMAT(str_to_date(".$sys_tables['agencies'].".`tarif_start`,'%Y-%m-%d'),'%d')),'%Y-%m-%d' ),NOW()) = 0
                                          AND ABS(DATEDIFF(".$sys_tables['agencies'].".tarif_start,DATE_FORMAT(NOW(),'%Y-%m-%d'))) >= 30,1,0) AS tarif_ends
                                FROM ".$sys_tables['agencies']."
                                LEFT JOIN ".$sys_tables['tarifs_agencies']." ON ".$sys_tables['agencies'].".id_tarif = ".$sys_tables['tarifs_agencies'].".id
                                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                                RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id AND ".$sys_tables['users'].".agency_admin = 1
                                WHERE ".$sys_tables['agencies'].".id_tarif > 0 AND  ".$sys_tables['agencies'].".tarif_expenditures = 1 AND
                                      ".$sys_tables['agencies'].".tarif_start NOT LIKE '%000%' AND 
                                      DATEDIFF(str_to_date( CONCAT(DATE_FORMAT(NOW(),'%Y-%m-'),DATE_FORMAT(str_to_date(".$sys_tables['agencies'].".`tarif_start`,'%Y-%m-%d'),'%d')),'%Y-%m-%d' ),NOW()) IN (0,3)");
foreach($agencies_list as $k=>$item){
    //оповещаем или делаем списание
    if(empty($item['tarif_ends']) && !empty($item['3_before_end'])){
        //оповещаем менеджера
        Response::SetArray('item', $item);
        $eml_tpl = new Template('mail.tarif.manager_near_ending.html', 'cron/daily_stats/');
        $mailer = new EMailer('mail');
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        $mailer->Body = $html;
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Приближается срок окончания тарифа агентства ".$item['agency_title']." на BSN.ru");
        $mailer->IsHTML(true);
        $mailer->AddAddress($item['manager_email']);
        $mailer->AddAddress("web@bsn.ru");
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
        $res = $res && $mailer->Send();
        
        //опопвещаем агентство
        Response::SetArray('item', $item);
        $eml_tpl = new Template('mail.tarif.agency_near_ending.html', 'cron/daily_stats/');
        $mailer = new EMailer('mail');
        $html = $eml_tpl->Processing();
        $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        $mailer->Body = $html;
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $item['user_name'].", приближается срок окончания тарифа агентства ".$item['agency_title']." на BSN.ru");
        $mailer->IsHTML(true);
        $mailer->AddAddress($item['user_email']);
        $mailer->AddAddress("web@bsn.ru");
        $mailer->From = 'no-reply@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
        $res = $res && $mailer->Send();
    }elseif(!empty($item['tarif_ends'])){
        //делаем списание за тариф
        $res = $res && $db->query("INSERT INTO ".$sys_tables['users_finances']." (id_user,obj_type,estate_type,id_parent,expenditure,income) VALUES (?,'tarif','',1,?,0)",$item['id_user'],$item['tarif_cost']);
        $res = $res && $db->query("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?",$item['tarif_cost'],$item['id_user']);
    }
}
$log['agencies_ending_notifications'] = "Оповещения за 3 дня о приближении окончания тарифов агентств, списания по тарифам агентств: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Переносим в архив спарсенные новости старше суток
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $db->query("UPDATE ".$sys_tables['news_parsing']." SET status = 4 WHERE TIMESTAMPDIFF(DAY, creation_datetime, NOW()) >=1 AND status = 1");
$log['news_parsed_archive'] = "Перенос в архив необработанных новостей старше суток: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

////////////////////////////////////////////////////////////////////////////////////////////////
// Переносим в общую таблицу статистику по ip
////////////////////////////////////////////////////////////////////////////////////////////////
$res = $res && $db->query("INSERT INTO ".$sys_tables['visitors_ips_stats_full']." (ip,date,visits,avg_interval,min_avg_interval,bot_id) 
                           SELECT ip,NOW() AS `date`,pages_visited AS visits,pages_avg_interval AS avg_interval,pages_min_avg_interval AS min_avg_interval,bot_id 
                            FROM ".$sys_tables['visitors_ips_stats_day']);
$res = $res && $db->query("TRUNCATE TABLE ".$sys_tables['visitors_ips_stats_day']);
$res = $res && $db->query("TRUNCATE TABLE ".$sys_tables['visitors_ips_day']);
$log['ips_stats'] = "Перенос в общую статистику суточной статистики по IP: ".((!$res)?$db->error:"OK")."<br />";
$res = true;

$log = implode('<br />',$log);

$mailer = new EMailer('mail');
$mail_text = iconv('UTF-8', $mailer->CharSet, "Ежедневная статистика на bsn.ru:<br />".$log);
if(!empty($data['subject'])) $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Ежедневная статистика bsn.ru");
$mailer->Body = $mail_text;
$mailer->AltBody = strip_tags($mail_text);
$mailer->IsHTML(true);
$mailer->AddAddress('web@bsn.ru');
$mailer->From = 'no-reply@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
// попытка отправить
$mailer->Send();

?>