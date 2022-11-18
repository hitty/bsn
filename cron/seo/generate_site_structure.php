#!/usr/bin/php
<?php
ini_set("memory_limit", "1024M");
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
$error_log = ROOT_PATH.'/cron/seo/error.log';
$test_performance = ROOT_PATH.'/cron/seo/test_performance.log';
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
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
include('includes/class.sitemap.php');       // подключение класса генератора xml   
require_once('includes/class.email.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");
$GLOBALS['db']=$db;
$url=DEBUG_MODE?'https://www.bsn.int':'https://www.bsn.ru';

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//логи для почты
$log = array();
        
$sitemap = new sitemap();


//игнорировать ссылки с расширениями:
//$sitemap->set_ignore(array("javascript:", ".css", ".js", ".ico", ".jpg", ".png", ".jpeg", ".swf", ".gif"));
$site_struct = array();
//сегодняшняя дата в формате W3C
$today = date('Y-m-d\\TH:i:sP',time());
  
file_put_contents($root.'/site_struct.txt',"");

$total_counter = 0;


//#################################################################
// КАЛЕНДАРЬ СОБЫТИЙ
//главная
$lastmod = getLastItem('calendar_events','date_begin',' YEAR(date_begin)<YEAR(CURDATE())'); //последняя статья
array_push($site_struct,$url.'/calendar/');
//года
$now_year = date('Y', time());
for ($i=2009;$i<=$now_year;$i++){
    switch(true){
        case $i < ($now_year - 1):      $changefreq = 'yearly';     $priority = '0.4';  break;
        case $i == ($now_year - 1):     $changefreq = 'monthly';    $priority = '0.4';  break;
        case $i == $now_year:           $changefreq = 'daily';      $priority = '0.5';  break;
    }
    $lastmod = getLastItem('calendar_events','date_begin',' YEAR(date_begin)= '.$now_year);
    array_push($site_struct,$url.'/calendar/y/'.$i.'/');
}
//пишем в файл после каждого блока
if(count($site_struct) > 5000){
    $total_counter += count($site_struct);
    $site_struct = implode("\r\n",$site_struct);
    file_put_contents($root.'/site_struct.txt',$site_struct,FILE_APPEND);
    unset($site_struct);
    $site_struct = array();
}



//пишем в файл после каждого блока
if(count($site_struct) > 5000){
    $total_counter += count($site_struct);
    $site_struct = implode("\r\n",$site_struct);
    file_put_contents($root.'/site_struct.txt',$site_struct,FILE_APPEND);
    unset($site_struct);
    $site_struct = array();
}

//#################################################################
//ОДИНОЧНЫЕ ССЫЛКИ + МНЕНИЯ + РЕГИСТРАЦИИ НА МЕРОПРИЯТИЯ
array_push($site_struct,$url);
array_push($site_struct,$url.'/about/');
array_push($site_struct,$url.'/contacts/');
array_push($site_struct,$url.'/advertising/');
array_push($site_struct,$url.'/guestbook/');
array_push($site_struct,$url.'/guestbook/add/');
array_push($site_struct,$url.'/help/');

//МНЕНИЯ, ПРОГНОЗЫ, ИНТЕРВЬЮ
$array = array(1=>'opinions',2=>'predictions',3=>'interview');
foreach($array as $type=>$opi){
    $lastmod = getLastItem('opinions_predictions','date','type='.$type); //последнее мнение
    array_push($site_struct,$url.'/'.$opi.'/');
    //тип недвижимости
    $list_cat=$db->fetchall('SELECT '.$sys_tables['opinions_estate_types'].'.url, '.$sys_tables['opinions_estate_types'].'.id FROM '.$sys_tables['opinions_estate_types']);
    foreach ($list_cat as $item_cat){
        $lastmod = getLastItem('opinions_predictions','date','type = '.$type.' AND id_estate_type = '.$item_cat['id']); //последнее мнение для типа недвижимости
        if(!empty($lastmod)){
            switch(true){
                case $lastmod['date_diff'] <= 7: $changefreq = 'daily'; break;
                case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
                case $lastmod['date_diff'] <= 350: $changefreq = 'monthly'; break;
                default: $changefreq = 'daily'; break;
            }
            array_push($site_struct,$url.'/'.$opi.'/'.$item_cat['url'].'/');
        }
    }
    //не вписываем url карточек
}
//РЕГИСТРАЦИИ НА МЕРОПРИЯТИЯ
$lastmod = getLastItem('events_registration','event_date'); //последнее мнение
array_push($site_struct,$url.'/events_registration/');
//список всеъ регистраций
$list=$db->fetchall('SELECT 
                        DATE_FORMAT(event_date,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod,
                        url 
                     FROM '.$sys_tables['events_registration']."  ");
foreach($list as $item) array_push($site_struct,$url.'/events_registration/'.$item['url'].'/');

//пишем в файл после каждого блока
if(count($site_struct) > 5000){
    $total_counter += count($site_struct);
    $site_struct = implode("\r\n",$site_struct);
    file_put_contents($root.'/site_struct.txt',$site_struct,FILE_APPEND);
    unset($site_struct);
    $site_struct = array();
}

//#################################################################
//ОРГАНИЗАЦИИ
$item = $db->fetch('SELECT DATE_FORMAT(MAX('.$sys_tables['users'].'.datetime),"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                    FROM '.$sys_tables['users'].' 
                    RIGHT JOIN '.$sys_tables['agencies'].' ON '.$sys_tables['users'].'.id_agency = '.$sys_tables['agencies'].'.id
                    WHERE  '.$sys_tables['agencies'].'.id > 1');
array_push($site_struct,$url.'/organizations/');
//список по категориям
$activities = array('agencies','adv_agencies','zastr','upr','bank','devel','invest','other');
foreach($activities as $k=>$activity)  {
    $item = $db->fetch('SELECT DATE_FORMAT(MAX('.$sys_tables['users'].'.datetime),"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                        FROM '.$sys_tables['users'].' 
                        RIGHT JOIN '.$sys_tables['agencies'].' ON '.$sys_tables['users'].'.id_agency = '.$sys_tables['agencies'].'.id
                        WHERE '.$sys_tables['agencies'].'.activity&'.pow(2,$k).' AND  '.$sys_tables['agencies'].'.id > 1');    
    array_push($site_struct,$url.'/organizations/'.$activity.'/');
}
//список компаний не пишем

//транслитные варианты русских букв для каталога компаний
$subst_ru = array('rA', 'rB', 'rV', 'rG', 'rD', 'rJe', 'rZh', 'rZ', 'rI', 'rK', 'rL', 'rM', 'rN', 'rO', 'rP', 'rR', 'rS', 'rT', 'rU', 'rF', 'rH', 'rC', 'rCh', 'rSh', 'rE', 'rJu', 'rJa');
$arr_alph_ru = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Э', 'Ю', 'Я');
$arr_alph_en = range('A', 'Z');
foreach($subst_ru as $item) array_push($site_struct,$url.'/organizations/company/'.$item.'/');
foreach($arr_alph_en as $item) array_push($site_struct,$url.'/organizations/company/'.$item.'/');

//пишем в файл после каждого блока
if(count($site_struct) > 5000){
    $total_counter += count($site_struct);
    $site_struct = implode("\r\n",$site_struct);
    file_put_contents($root.'/site_struct.txt',$site_struct,FILE_APPEND);
    unset($site_struct);
    $site_struct = array();
}




//#################################################################
//СПРАВОЧНЫЕ ДОКУМЕНТЫ И КОНСУЛЬТАЦИИ (service)
//справочные
array_push($site_struct,$url.'/service/');
array_push($site_struct,$url.'/service/information/');

//строим url для перечень всех типов справочных документов
 $list = $db->fetchall("SELECT ".$sys_tables['references_docs_categories'].".title as category_title,
                                      IF(".$sys_tables['references_docs_categories'].".id IN (3,5,7,9),
                                        IF(".$sys_tables['references_docs_categories'].".id=9,
                                            CONCAT_WS('/','offices',".$sys_tables['references_docs_types'].".code), 
                                            ".$sys_tables['references_docs_types'].".code
                                        ), 
                                        CONCAT_WS('/',".$sys_tables['references_docs_types'].".code, ".$sys_tables['references_docs'].".id)
                                      )  as code,
                                      IF(".$sys_tables['references_docs_categories'].".id IN (3,5,7,9), ".$sys_tables['references_docs_types'].".title, ".$sys_tables['references_docs'].".title) as docs_title
                               FROM ".$sys_tables['references_docs_categories']."
                               LEFT JOIN ".$sys_tables['references_docs_types']."  ON ".$sys_tables['references_docs_types'].".id_category=".$sys_tables['references_docs_categories'].".id
                               LEFT JOIN ".$sys_tables['references_docs']."  ON ".$sys_tables['references_docs'].".id_type=".$sys_tables['references_docs_types'].".id
                               GROUP BY  docs_title
                               ORDER BY ".$sys_tables['references_docs_categories'].".id, ".$sys_tables['references_docs_types'].".title  ");
//перебираем страницы внутри типов справочных документов
foreach($list as $item){
    array_push($site_struct,$url.'/service/information/'.$item['code'].'/');
    $list_cat = $db->fetchall("SELECT CONCAT_WS('/',".$sys_tables['references_docs_types'].".code, ".$sys_tables['references_docs'].".id)  as code,
                                      ".$sys_tables['references_docs_types'].".title as category_title,
                                      ".$sys_tables['references_docs_types'].".code as category_code,  
                                      ".$sys_tables['references_docs'].".title as docs_title
                               FROM ".$sys_tables['references_docs_types']."
                               LEFT JOIN ".$sys_tables['references_docs']."  ON ".$sys_tables['references_docs'].".id_type=".$sys_tables['references_docs_types'].".id
                               WHERE ".$sys_tables['references_docs_types'].".code='".$db->real_escape_string($item['code'])."'  ");
    foreach($list_cat as $item_cat) array_push($site_struct,$url.'/service/information/'.$item_cat['code'].'/');
}  
//консультант
$lastmod = getLastItem('consults','question_datetime'); //последняя дата консультации
array_push($site_struct,$url.'/service/consultant/');
array_push($site_struct,$url.'/service/consultant/add/');
//категории
$list = $db->fetchall('SELECT code, id FROM '.$sys_tables['consults_categories']);
foreach($list as $item){
    $lastmod = getLastItem('consults','question_datetime','id_category ='.$item['id']); //последняя дата категории консультации
    switch(true){
        case $lastmod['date_diff'] <= 7: $changefreq = 'daily'; break;
        case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
        case $lastmod['date_diff'] <= 350: $changefreq = 'monthly'; break;
        default: $changefreq = 'yearly'; break;
    }
    array_push($site_struct,$url.'/service/consultant/'.$item['code'].'/');
}
//не карточки

//#################################################################
//ADVERTISING
array_push($site_struct,$url.'/advertising/');
array_push($site_struct,$url.'/advertising/line_ads/');
array_push($site_struct,$url.'/advertising/media_ads/');
array_push($site_struct,$url.'/advertising/bsntarget/');
array_push($site_struct,$url.'/advertising/misc/');

//#################################################################
//APPLICATIONS
array_push($site_struct,$url.'/applications/');

//$sitemap->finish_maps_manually();    
$total_counter += count($site_struct);
$site_struct = implode("\r\n",$site_struct);
file_put_contents($root.'/site_struct.txt',$site_struct,FILE_APPEND);




$mail_text = 'Генерация файла закончена.<br /><br />Всего ссылок - <b>'.$total_counter.'</b>';

//если были ошибки выполнения скрипта
if(filesize($error_log)>10){
    $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
    $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
    $error_log_text .= '</font><br>';
} else $error_log_text = "";
if(filesize($test_performance)>10){
    $error_log_text = '<br><br>Загрука памяти<br><font size="1">';
    $error_log_text .= fread(fopen($test_performance, "r"), filesize($error_log));
    $error_log_text .= '</font><br>';
} else $test_performance = "";

$html = $mail_text.$error_log_text.$test_performance."<br /><br />Время генерации ".round(microtime(true) - $overall_time_counter, 4)."<br/>";
echo $html;

$mailer = new EMailer('mail');
$html = iconv('UTF-8', $mailer->CharSet, $html);
// параметры письма
$mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Генерация структутры сайта. '.date('Y-m-d H:i:s'));
$mailer->Body = $html;
$mailer->AltBody = strip_tags($html);
$mailer->IsHTML(true);
$mailer->AddAddress('web@bsn.ru');
$mailer->From = 'sitemap@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Карта сайта BSN');
// попытка отправить
$mailer->Send();

$querylog = Convert::ArrayKeySort($db->querylog, 'time', true);
file_put_contents(ROOT_PATH.'/cron/seo/query.log',print_r($querylog,true));


function memoryUsage($usage, $base_memory_usage) {
    return "Bytes diff: ".($usage - $base_memory_usage);
}

function getLastItem($table, $date_field, $where=''){
    global $db, $sys_tables;
    $item =  $db->fetch("SELECT 
                            DATE_FORMAT(MAX(`".$date_field."`),'%Y-%m-%dT%H:%i:%s+04:00') as last_change, 
                            DATEDIFF( CURDATE( ), MAX(`".$date_field."`)) as date_diff
                       FROM ".$sys_tables[$table]
                       .(!empty($where)?" WHERE ".$where:" ")
    );
    if(strstr($item['last_change'],'0000-00-00T00:00:00+04:')) {
        echo 'qweqweqwe';
    }
    if(!empty($item['last_change'])) return $item;
    else return $db->fetch("SELECT 
                                        DATE_FORMAT(CURDATE() - INTERVAL 370 DAY,'%Y-%m-%dT%H:%i:%s+04:00') as last_change, 
                                        370 as date_diff");
    
}
?>