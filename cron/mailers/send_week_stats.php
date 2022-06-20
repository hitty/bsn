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
require_once('includes/jpgraph/jpgraph.php');
require_once('includes/jpgraph/jpgraph_line.php');
require_once('includes/jpgraph/jpgraph_date.php');
require_once('includes/jpgraph/jpgraph_utils.inc.php');
require_once('includes/jpgraph/jpgraph_plotline.php');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$bsn_url = "https://www.bsn.ru/";
$estate_types = array('live', 'commercial', 'country', 'build');
//статусы для письма
$statuses_titles = array(1 => 'Поднятие',3 => 'Промо',4 => 'Премиум',5 => 'Платный объект',6 => 'VIP');
//перечень статистик для графика
$stats_types = array(
                     'stats_search'=>array(
                        'title' => "Показы в поиске",
                        'color' => "#696969"
                     ),
                     'stats_shows'=>array(
                        'title' => "Просмотры карточки",
                        'color' => "#1874cd"
                     ));
$letters = array();

$users_objects = array();
$objects_stats = array_fill_keys($estate_types,array());
foreach($estate_types as $key=>$estate_type){
    //DATEDIFF(NOW(),".$sys_tables[$estate_type].".date_change) AS days_ago_published,
    $active_objects = $db->fetchall("SELECT ".$sys_tables[$estate_type].".id,
                                            id_user,
                                            ".$sys_tables[$estate_type].".status,
                                            raising_status,
                                            rent,
                                            IF(DATE_FORMAT(date_change,'%Y-%m-%d') > DATE_SUB(NOW(),INTERVAL 30 DAY),
                                            DATE_FORMAT(date_change,'%Y-%m-%d'),
                                            DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 30 DAY),'%Y-%m-%d'))  AS stats_from_date,
                                            ".$sys_tables['objects_statuses'].".title AS status_title,
                                            IF(DATE_FORMAT(date_change,'%Y-%m-%d') > DATE_SUB(NOW(),INTERVAL 30 DAY),
                                               DATEDIFF(NOW(),".$sys_tables[$estate_type].".date_change),
                                               30) AS days_ago_published,
                                            FLOOR(DATEDIFF(NOW(),".$sys_tables[$estate_type].".date_change)/7) AS weeks_published
                                     FROM ".$sys_tables[$estate_type]."
                                     LEFT JOIN ".$sys_tables['objects_statuses']." ON ".$sys_tables[$estate_type].".status = ".$sys_tables['objects_statuses'].".id
                                     LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables[$estate_type].".id_user = ".$sys_tables['users'].".id
                                     WHERE DATEDIFF(NOW(),date_change) mod 7 = 0 AND DATEDIFF(NOW(),date_change) > 0 AND ".$sys_tables['users'].".id_agency = 0 AND published = 1", 'id' );
    
    if(empty($active_objects)) continue;
    $users_ids = array();
    foreach($active_objects as $key=>$object){
        if(empty($users_objects[$object['id_user']])) $users_objects[$object['id_user']]['objects_info'] = array_fill_keys( $estate_types, array() );
        $users_ids[] = $object['id_user'];
        $users_objects[$object['id_user']]['objects_info'][$estate_type][] = $object['id'];
        $users_objects[$object['id_user']]['objects_data'][$estate_type][$object['id']] = $object;
    }
    $users_ids = array_values(array_unique($users_ids));
    
    //либо дата размещения, либо последние 30 дней
    $min_stats_date = $db->fetch("SELECT IF(DATE_FORMAT(MIN(date_change),'%Y-%m-%d') > DATE_SUB(NOW(),INTERVAL 30 DAY),
                                            DATE_FORMAT(MIN(date_change),'%Y-%m-%d'),
                                            DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 30 DAY),'%Y-%m-%d'))  AS stats_from_date
                                  FROM ".$sys_tables[$estate_type]."
                                  WHERE DATEDIFF(NOW(),date_change) mod 7 = 0 AND id_user IN (".implode(',',$users_ids).") AND published = 1")['stats_from_date'];
    
    ///читаем статистику и раскидываем по пользователям
    //просмотры:
    $objects_stats_shows[$estate_type] = $db->fetchall("SELECT * FROM ".$sys_tables[$estate_type."_stats_show_full"]."
                                                        WHERE id_parent IN (".implode(',',array_keys($active_objects)).") AND `date` >= ? 
                                                        ORDER BY `date` ASC", false, $min_stats_date);
    foreach($objects_stats_shows[$estate_type] as $key=>$stats_line){
        if(!empty($users_objects[$stats_line['id_user']]) && 
           in_array($stats_line['id_parent'], $users_objects[ $stats_line['id_user'] ]['objects_info'][$estate_type]) ){
               
            if(empty($users_objects[$stats_line['id_user']]['stats_shows'][$estate_type][$stats_line['id_parent']]))
                $users_objects[$stats_line['id_user']]['stats_shows'][$estate_type][$stats_line['id_parent']] = array($stats_line['date']=>$stats_line['amount']);
             else
                $users_objects[$stats_line['id_user']]['stats_shows'][$estate_type][$stats_line['id_parent']][$stats_line['date']] = $stats_line['amount'];
        }
    }
    
    unset($objects_stats_shows);
    //показы в поиске
    $objects_stats_search[$estate_type] = $db->fetchall("SELECT * 
                                                         FROM ".$sys_tables[$estate_type."_stats_search_full"]." 
                                                         WHERE id_parent IN (".implode(',',array_keys($active_objects)).") AND `date` > ? 
                                                         ORDER BY `date` ASC",false,$min_stats_date);
                                                         
    foreach($objects_stats_search[$estate_type] as $key=>$stats_line){
        if(!empty($users_objects[$stats_line['id_user']]) && in_array($stats_line['id_parent'], $users_objects[$stats_line['id_user']]['objects_info'][$estate_type]) ){
            
            if(empty($users_objects[$stats_line['id_user']]['stats_search'][$estate_type][$stats_line['id_parent']]))
                $users_objects[$stats_line['id_user']]['stats_search'][$estate_type][$stats_line['id_parent']] = array( $stats_line['date'] => $stats_line['amount'] );
             else
                $users_objects[$stats_line['id_user']]['stats_search'][$estate_type][$stats_line['id_parent']][$stats_line['date']] = $stats_line['amount'];
        }
    }
    
    unset($objects_stats_search);
    //открытия телефонов
    switch($estate_type){
        case "live":
            $estate_code = 1;
            break;
        case "build":
            $estate_code = 2;
            break;
        case "commercial":
            $estate_code = 3;
            break;
        case "country":
            $estate_code = 4;
            break;
        default:
            $estate_code = 0;
    }
    
    if( empty($estate_code) ) continue;
    
    $objects_stats_phones[$estate_type] = $db->fetchall("SELECT *
                                                         FROM ".$sys_tables["phone_clicks_full"]." 
                                                         WHERE id_parent IN (".implode(',',array_keys($active_objects)).") AND `date` > ? AND type = ? 
                                                         ORDER BY `date` ASC", false, $min_stats_date, $estate_code);
    foreach($objects_stats_phones[$estate_type] as $key=>$stats_line){
        $id_user = $active_objects[$stats_line['id_parent']]['id_user'];
        
        if(empty($users_objects[$id_user]['stats_phones'][$estate_type][$stats_line['id_parent']]))
            $users_objects[$id_user]['stats_phones'][$estate_type][$stats_line['id_parent']] = array($stats_line['date']=>$stats_line['amount']);
         else
            $users_objects[$id_user]['stats_phones'][$estate_type][$stats_line['id_parent']][$stats_line['date']] = $stats_line['amount'];
    }
    unset($objects_stats_phones);
}
//читаем данные пользователей
$users_ids = array_keys($users_objects);
if(!empty($users_ids)){
    $users_info = $db->fetchall("SELECT id,email,name,lastname,last_enter,login
                                 FROM ".$sys_tables['users']." 
                                 WHERE id IN(".implode(',',$users_ids).")", 'id');
}

foreach($users_objects as $user_id=>$user_info) $users_objects[$user_id]['user_info'] = $users_info[$user_id];

//идем по пользователям, составляем графики
if(!empty($users_objects)){
    foreach($users_objects as $user_id=>$user_info){
        if(!empty($user_info['stats_shows']) || !empty($user_info['stats_search']) || !empty($user_info['stats_phones'])){
            
            //идем по объектам пользователя(каждому объекту свое письмо)
            foreach($user_info['objects_info'] as $estate_type => $objects_info){
                foreach($objects_info as $key => $object_id){
                    
                    $xdata = array();
                    $ydata_all = array();
                    $dates_labels = array();
                    unset($stats_date_start);
                    unset($stats_date_end);
                    //перебираем типы статистики
                    foreach($stats_types as $stats_type => $stats_type_info){
                        //накапливаем диапазон дат
                        if(empty($user_info[$stats_type][$estate_type][$object_id])) continue;
                        $xdata = array_merge($user_info[$stats_type][$estate_type][$object_id],$xdata);
                        $ydata_all[$stats_type] = $user_info[$stats_type][$estate_type][$object_id];
                        //выделяем начальную и конечную даты
                        $date_start = date_create_from_format('Y-m-d',key($xdata));
                        if(empty($stats_date_start) || $stats_date_start > $date_start) $stats_date_start = $date_start;
                        end($xdata);
                        $date_end = date_create_from_format('Y-m-d',key($xdata));
                        if(empty($stats_date_end) || $stats_date_end < $date_end) $stats_date_end = $date_end;
                    }
                    if(empty($stats_date_end) || empty($stats_date_start)) continue;
                    
                    //создаем массив из timestamp для графиков (OY)
                    $xdata = $dates = array();
                    $interval = 90000;
                    $labels_counter = 0;
                    while($stats_date_start <= $stats_date_end){
                        $dates[$stats_date_start->format("Y-m-d")] = 0;
                        $dates_labels[] = ($labels_counter%7 == 0?$stats_date_start->format("d.m"):"");
                        $stats_date_start->add(new DateInterval('P1D'));
                        ++$labels_counter;
                    }
                    
                    $angle = 0;$graph_height = 220;
                    //смотрим разницу между графиками, если большая - будут дополнительные оси
                    $multi_Yaxis = max($ydata_all['stats_search']) - max($ydata_all['stats_shows']) >= 100;
                    switch(true){
                        case (count($dates_labels) > 10 && count($dates_labels) < 15):
                            $angle = 30;
                            $graph_height = 250;
                        break;
                        default:
                            $angle = 60;
                            $graph_height = 280;
                        break;
                    }
                    
                    $graph = new Graph(600, $graph_height, 'auto', 10, true);
                    $graph->SetScale( 'textlin' );
                    $graph->xaxis->SetLabelAngle($angle);
                    $graph->xaxis->SetTickLabels($dates_labels);
                    $graph->xaxis->scale->ticks->Set(1);
                    $graph->xaxis->SetLabelAlign('center');
                    
                    $graph->img->SetAntiAliasing(false);
                    $graph->ygrid->Show(true, false);
                    $graph->SetTitleBackgroundFillStyle(TITLEBKG_FILLSTYLE_SOLID,'#FFFFFF');
                    
                    
                    $ydata_counter = 0;
                    //накапливаем линии графиков
                    foreach($ydata_all as $stats_type => $ydata){
                        //заполняем пустые даты из диапазона
                        $ydata = array_merge($dates,$ydata);
                        ksort($ydata);
                        $lineplot = new LinePlot(array_values($ydata), range(0,count($ydata) - 1));
                        $lineplot->SetCenter();
                        if($multi_Yaxis){
                            if($ydata_counter > 0){
                                $graph->SetYScale($ydata_counter - 1,'lin');
                                $graph->AddY($ydata_counter - 1,$lineplot);
                                $graph->ynaxis[$ydata_counter - 1]->SetColor($stats_types[$stats_type]['color']);
                            }
                            else $graph->Add($lineplot);
                            ++$ydata_counter;
                        }
                        else $graph->Add($lineplot);
                        $lineplot->mark->SetType(MARK_FILLEDCIRCLE,'',1.0);
                        $lineplot->SetLegend($stats_types[$stats_type]['title']." (".array_sum($ydata).")");
                        $lineplot->SetColor($stats_types[$stats_type]['color']);
                        $lineplot->mark->SetColor($stats_types[$stats_type]['color']);
                        $lineplot->mark->SetFillColor($stats_types[$stats_type]['color']);
                        $lineplot->SetWeight(1);
                        
                        unset($lineplot);
                    }
                                   
                    $graph->SetY2OrderBack();
                    //легенду пониже
                    $graph->legend->SetMarkAbsSize(8);
                    $graph->legend->SetPos(0.25,0.9);
                    //чтобы белый фон был
                    $graph->ygrid->SetFill(false);
                    $graph->xgrid->Show(false,false);
                    //прозрачная OY
                    
                    //чтобы не было черты справа
                    $graph->SetAxisStyle(AXSTYLE_SIMPLE);
                    $graph->SetBox(false);
                    
                    //пишем картинку в файл
                    $fileName = ROOT_PATH."/img/uploads/mail_charts/".$object_id."_week_".$stats_type."_".date('Y-m-d').".png";
                    $bottom_margin = 40;
                    $margins = array_fill(0,3,0);
                    $margins[3] = (count($dates_labels) > 10 && count($dates_labels) < 15 ? 50 : (count($dates_labels) >= 15 ? 70 : 40 ));
                    
                    //если есть дополнительные OY, прибавляем к margin
                    $margins[0] = ($multi_Yaxis ? 50 : 30);
                    $margins[1] = ($multi_Yaxis ? 40 : 30);
                    
                    $graph->img->SetMargin($margins[0],$margins[1],20,$bottom_margin);
                    $graph->img->Stream($fileName);
                    @unlink($fileName);
                    $res = $graph->Stroke($fileName);
                    
                    //отправляем письмо по объекту
                    $object_info = $user_info['objects_data'][$estate_type][$object_id];
                    $object_info['object_title'] = ($object_info['status'] > 2 ? " с услугой '".$object_info['status_title']."'".($object_info['raising_status'] == 1 ? " и поднятие" : "") : "");
                    $eml_tpl = new Template('mail.user.week_stats.html', 'cron/mailers/');
                    $object_info['link'] = "https://www.bsn.ru/".$estate_type."/".($object_info['rent'] == 1 ? 'rent' : 'sell')."/".$object_id."/";
                    $object_info['pay_link'] = "https://www.bsn.ru/members/estate/edit/".$estate_type."/".($object_info['rent'] == 1 ? 'rent' : 'sell')."/".$object_id."/?step=3";
                    Response::SetArray('object_info',$object_info);
                    //Response::SetString('chart_src',"/img/uploads/mail_charts/".$object_id."_week_".$stats_type."_".date('Y-m-d').".png");
                    Response::SetString('chart_src',"//st1.bsn.ru/img/uploads/mail_charts/".$object_id."_week_".$stats_type."_".date('Y-m-d').".png?v=". rand(0,10000));
                    Response::SetArray('info',$user_info['user_info']);

                    $html = $eml_tpl->Processing();
                    $time_published = (empty($object_info['weeks_published']) ? $object_info['days_ago_published']." ".makeSuffix($object_info['days_ago_published'], 'д', array('ень', 'ня', 'ней'))  : 
                                                                                $object_info['weeks_published']." ".makeSuffix($object_info['weeks_published'], 'недел', array('ю','и','ь')) );
                    $user_title = (empty($user_info['user_info']['name']) ? (empty($user_info['user_info']['lastname']) ? $user_info['user_info']['lastname'] : $user_info['user_info']['login']) : $user_info['user_info']['name']);
                    $user_title = (!empty($user_title) ? $user_title.", О" : "О");
                    
                    $res = false;
                    
                    if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
                    $sender_title = 'Статистика ваших объектов на BSN.ru';         
                    $emails = array(
                        array(
                            'name' => '',
                            'email'=> 'web@bsn.ru'
                        )
                    );
                    if(!empty( $user_info['user_info']['email'] ) ) $emails[] = array( 'name' => '', 'email'=> $user_info['user_info']['email'] );
                    //отправка письма
                    $sendpulse = new Sendpulse( );
                    $res = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );
                    
                    
                    $log[] = "#".$user_info['user_info']['id']." ".$object_info['link'].' , email:'.(!empty($res) ? "OK" : "ошибка отправки").", ".$user_info['user_info']['email'];
                }
            }
        }
    }
}

//общий отчет на web@bsn
$mailer = new EMailer('mail');
$html = "Оптравленные письма:<br />".implode("<br />",$log);

$res = false;

if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
$sender_title = 'BSN.ru';         
$subject = 'Отправка недельной статистики по объектам частных лиц';
$emails = array(
    array(
        'name' => '',
        'email'=> 'web@bsn.ru'
    )
);
//отправка письма
$sendpulse = new Sendpulse( );
$result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );

?>