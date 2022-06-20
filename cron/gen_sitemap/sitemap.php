#!/usr/bin/php
<?php
ini_set("memory_limit", "9024M");
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
$error_log = ROOT_PATH.'/cron/gen_sitemap/error.log';
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
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
include('includes/class.sitemap.php');       // подключение класса генератора xml   
require_once('includes/class.email.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");
$GLOBALS['db']=$db;
$url=DEBUG_MODE?'https://www.bsnnew.int':'https://www.bsn.ru';
$links_per_query = 39500;
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$base_memory_usage = memory_get_usage();
memoryUsage(memory_get_usage(), $base_memory_usage);//логи для почты
$log = array();
        
$sitemap = new sitemap(); 
$sitemap_folder = $root.'/'.$sitemap->folder_tmp;
$dh = opendir($sitemap_folder);
while($filename = readdir($dh)){
        if($filename!='.' && $filename!='..') unlink($sitemap_folder.$filename);
}
 closedir($dh); 

//игнорировать ссылки с расширениями:
$sitemap->set_ignore(array("javascript:", ".css", ".js", ".ico", ".jpg", ".png", ".jpeg", ".swf", ".gif"));
//сегодняшняя дата в формате W3C
$today = date('Y-m-d\\TH:i:sP',time());
  

//#################################################################
// ЧПУ урлы
$db->query("UPDATE ".$sys_tables['pages_seo']." SET lastmod_date = CURDATE() - INTERVAL 30 DAY WHERE lastmod_date = '0000-00-00 00:00:00'");
//floor,top - от какого и сколько объектов выбираем
$floor = 0; $top = $links_per_query;
do{
    if(!empty($list)) unset($list);
    $list = $db->fetchall("SELECT *,  
                                  DATE_FORMAT(lastmod_date,'%Y-%m-%dT%H:%i:%s+04:00') as lastmod_date,
                                  DATEDIFF( CURDATE( ), MAX(lastmod_date)) as date_diff 
                           FROM ".$sys_tables['pages_seo']." WHERE 
                                id > 1000
                                AND 
                                ROUND (   
                                    (
                                        LENGTH(pretty_url)
                                        - LENGTH( REPLACE ( pretty_url, '/', '') ) 
                                    ) / LENGTH('/')        
                                ) <= 3  
                           GROUP BY id                   
                           LIMIT ".$floor.", ".$links_per_query);
    $floor=$top;$top+=$links_per_query;                           
    foreach($list as $k=>$item){
        switch(true){
            case $item['date_diff'] <= 7: $changefreq = 'daily'; break;
            case $item['date_diff'] <= 21: $changefreq = 'weekly'; break;
            case $item['date_diff'] <= 350: $changefreq = 'monthly'; break;
            default: $changefreq = 'yearly'; break;
        } 
        $sitemap->add_sitemap_url($url.'/'.$item['pretty_url'].'/', $item['lastmod_date'], $changefreq, $changefreq == 'daily'?'0.8':'0.7');         
        $sitemap->create_xmls_manually('sitemap_pretty');
    }
    unset($item);
    
    echo $links_per_query.' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";
        
} while(!empty($list));        
$sitemap->create_xmls_manually('sitemap_pretty', true);
$sitemap->finish_maps_manually();
echo $links_per_query.' ; memory: '.memoryUsage(memory_get_usage(), $base_memory_usage)."\n";

//#################################################################
// БСН-ТВ
//главная новостей бсн-тв
$lastmod = getLastItem('bsntv','datetime',''); //последняя новость
$sitemap->add_sitemap_url($url.'/bsntv/',$lastmod['last_change'],'weekly','0.5');

//списки категорий
$list_cat=$db->fetchall('SELECT '.$sys_tables['bsntv_categories'].'.code, '.$sys_tables['bsntv_categories'].'.id FROM '.$sys_tables['bsntv_categories']);
//категория
foreach ($list_cat as $item_cat){
    $lastmod = getLastItem('bsntv','datetime','id_category = '.$item_cat['id']); //последняя новость для категории
    switch(true){
        case $lastmod['date_diff'] <= 3: $changefreq = 'daily'; break;
        case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
        case $lastmod['date_diff'] <= 180: $changefreq = 'monthly'; break;
        default: $changefreq = 'daily'; break;
    }
    $sitemap->add_sitemap_url($url.'/bsntv/'.$item_cat['code'].'/',$lastmod['last_change'],$changefreq,'0.4');
    
}
$sitemap->create_xmls_manually('sitemap_bsntv');
//вписываем url карточек
$list = $db->fetchall('SELECT 
                            '.$sys_tables['bsntv'].'.chpu_title,
                            '.$sys_tables['bsntv'].'.id,
                            '.$sys_tables['bsntv_categories'].'.code AS category_code,
                            DATE_FORMAT('.$sys_tables['bsntv'].'.datetime,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                        FROM '.$sys_tables['bsntv'].' 
                        LEFT JOIN '.$sys_tables['bsntv_categories'].' ON '.$sys_tables['bsntv'].'.id_category='.$sys_tables['bsntv_categories'].'.id 
                        ORDER BY '.$sys_tables['bsntv'].'.id DESC');
foreach($list as $item){
    $sitemap_url=$url.'/bsntv/'.$item['category_code'].'/'.$item['chpu_title'].'/';
    $sitemap->add_sitemap_url($sitemap_url,$item['lastmod'],'monthly','0.2');
}
//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually('sitemap_bsntv');
$sitemap->finish_maps_manually();

//#################################################################
// Доверие
//главная новостей Доверие
$lastmod = getLastItem('doverie','datetime',''); //последняя новость
$sitemap->add_sitemap_url($url.'/doverie/',$lastmod['last_change'],'weekly','0.5');

//списки категорий
$list_cat=$db->fetchall('SELECT '.$sys_tables['doverie_categories'].'.code, '.$sys_tables['doverie_categories'].'.id FROM '.$sys_tables['doverie_categories']);
//категория
foreach ($list_cat as $item_cat){
    $lastmod = getLastItem('doverie','datetime','id_category = '.$item_cat['id']); //последняя новость для категории
    switch(true){
        case $lastmod['date_diff'] <= 3: $changefreq = 'daily'; break;
        case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
        case $lastmod['date_diff'] <= 180: $changefreq = 'monthly'; break;
        default: $changefreq = 'daily'; break;
    }
    $sitemap->add_sitemap_url($url.'/doverie/'.$item_cat['code'].'/',$lastmod['last_change'],$changefreq,'0.4');
    
}
$sitemap->create_xmls_manually('sitemap_doverie');
//вписываем url карточек
$list = $db->fetchall('SELECT 
                            '.$sys_tables['doverie'].'.chpu_title,
                            '.$sys_tables['doverie'].'.id,
                            '.$sys_tables['doverie_categories'].'.code AS category_code,
                            DATE_FORMAT('.$sys_tables['doverie'].'.datetime,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                        FROM '.$sys_tables['doverie'].' 
                        LEFT JOIN '.$sys_tables['doverie_categories'].' ON '.$sys_tables['doverie'].'.id_category = '.$sys_tables['doverie_categories'].'.id 
                        ORDER BY '.$sys_tables['doverie'].'.id DESC');
foreach($list as $item){
    $sitemap_url = $url . '/doverie/' . $item['category_code'] . '/' . $item['chpu_title'] . '/';
    $sitemap->add_sitemap_url( $sitemap_url, $item['lastmod'], 'monthly', '0.2' );
}
//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually( 'sitemap_doverie' );
$sitemap->finish_maps_manually();
//#################################################################
// Блог
//главная новостей бсн-тв
$lastmod = getLastItem('blog','datetime',''); //последняя новость
$sitemap->add_sitemap_url($url.'/blog/',$lastmod['last_change'],'weekly','0.5');

//списки категорий
$list_cat=$db->fetchall('SELECT '.$sys_tables['blog_categories'].'.code, '.$sys_tables['blog_categories'].'.id FROM '.$sys_tables['blog_categories']);
//категория
foreach ($list_cat as $item_cat){
    $lastmod = getLastItem('blog','datetime','id_category = '.$item_cat['id']); //последняя новость для категории
    switch(true){
        case $lastmod['date_diff'] <= 3: $changefreq = 'daily'; break;
        case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
        case $lastmod['date_diff'] <= 180: $changefreq = 'monthly'; break;
        default: $changefreq = 'daily'; break;
    }
    $sitemap->add_sitemap_url($url.'/blog/'.$item_cat['code'].'/',$lastmod['last_change'],$changefreq,'0.4');
    
}
$sitemap->create_xmls_manually('sitemap_blog');
//вписываем url карточек
$list = $db->fetchall('SELECT 
                            '.$sys_tables['blog'].'.chpu_title,
                            '.$sys_tables['blog'].'.id,
                            '.$sys_tables['blog_categories'].'.code AS category_code,
                            DATE_FORMAT('.$sys_tables['blog'].'.datetime,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                        FROM '.$sys_tables['blog'].' 
                        LEFT JOIN '.$sys_tables['blog_categories'].' ON '.$sys_tables['blog'].'.id_category='.$sys_tables['blog_categories'].'.id 
                        ORDER BY '.$sys_tables['blog'].'.id DESC');
foreach($list as $item){
    $sitemap_url=$url.'/blog/'.$item['category_code'].'/'.$item['chpu_title'].'/';
    $sitemap->add_sitemap_url($sitemap_url,$item['lastmod'],'monthly','0.2');
}
//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually('sitemap_blog');
$sitemap->finish_maps_manually();
  
    
//#################################################################
// НОВОСТИ
//главная новостей
$lastmod = getLastItem('news','datetime',''); //последняя новость
$sitemap->add_sitemap_url($url.'/news/',$lastmod['last_change'],'weekly','0.5');

//списки категорий и регионов
$list_cat=$db->fetchall('SELECT '.$sys_tables['news_categories'].'.code, '.$sys_tables['news_categories'].'.id FROM '.$sys_tables['news_categories']);
$list_reg=$db->fetchall('SELECT '.$sys_tables['news_regions'].'.code, '.$sys_tables['news_regions'].'.id FROM '.$sys_tables['news_regions']);
//регион
foreach($list_reg as $item_reg){
    $lastmod = getLastItem('news','datetime','id_region = '.$item_reg['id']); //последняя новость для региона
    switch(true){
        case $lastmod['date_diff'] <= 3: $changefreq = 'daily'; break;
        case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
        case $lastmod['date_diff'] <= 180: $changefreq = 'monthly'; break;
        default: $changefreq = 'yearly'; break;
    }
    $sitemap->add_sitemap_url($url.'/news/'.$item_reg['code'].'/',$lastmod['last_change'],$changefreq,$item_reg['code']=='spb'?'0.5':'0.4');
}
$sitemap->create_xmls_manually('sitemap_news', true);
//категория
foreach ($list_cat as $item_cat){
    $lastmod = getLastItem('news','datetime','id_category = '.$item_cat['id']); //последняя новость для категории
    switch(true){
        case $lastmod['date_diff'] <= 3: $changefreq = 'daily'; break;
        case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
        case $lastmod['date_diff'] <= 180: $changefreq = 'monthly'; break;
        default: $changefreq = 'daily'; break;
    }
    $sitemap->add_sitemap_url($url.'/news/'.$item_cat['code'].'/',$lastmod['last_change'],$changefreq,'0.4');
    //категория+регион
    foreach($list_reg as $item_reg){
        $lastmod = getLastItem('news','datetime','id_category = '.$item_cat['id'].' AND id_region = '.$item_reg['id']); //последняя новость для региона
        switch(true){
            case $lastmod['date_diff'] <= 3: $changefreq = 'daily'; break;
            case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
            case $lastmod['date_diff'] <= 180: $changefreq = 'monthly'; break;
            default: $changefreq = 'yearly'; break;
        }
        $sitemap->add_sitemap_url($url.'/news/'.$item_cat['code'].'/'.$item_reg['code'].'/',$lastmod['last_change'],$changefreq,$item_reg['code']=='spb'?'0.5':'0.4');
    }
}
$sitemap->create_xmls_manually('sitemap_news');
//вписываем url карточек
$list = $db->fetchall('SELECT 
                            '.$sys_tables['news'].'.chpu_title,
                            '.$sys_tables['news'].'.id,
                            '.$sys_tables['news_categories'].'.code AS category_code,
                            '.$sys_tables['news_regions'].'.code AS region_code,
                            DATE_FORMAT('.$sys_tables['news'].'.datetime,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                        FROM '.$sys_tables['news'].' 
                        LEFT JOIN '.$sys_tables['news_categories'].' ON '.$sys_tables['news'].'.id_category='.$sys_tables['news_categories'].'.id 
                        LEFT JOIN '.$sys_tables['news_regions'].' ON '.$sys_tables['news'].'.id_region='.$sys_tables['news_regions'].'.id
                        ORDER BY '.$sys_tables['news'].'.id DESC');
foreach($list as $item){
    $sitemap_url=$url.'/news/'.$item['category_code'].'/'.$item['region_code'].'/'.$item['chpu_title'].'/';
    $sitemap->add_sitemap_url($sitemap_url,$item['lastmod'],'monthly','0.2');
}
//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually('sitemap_news');
$sitemap->finish_maps_manually();
  
//#################################################################
// статьи
//главная статей
$sitemap->create_xmls_manually('sitemap_articles', true);
$lastmod = getLastItem('articles','datetime',''); //последняя новость
$sitemap->add_sitemap_url($url.'/articles/', $lastmod['last_change'], 'weekly', '0.5');

//списки категорий и регионов
$list_cat=$db->fetchall('SELECT '.$sys_tables['articles_categories'].'.code, '.$sys_tables['articles_categories'].'.id FROM '.$sys_tables['articles_categories']);
//категория
foreach ($list_cat as $item_cat){
    $lastmod = getLastItem('articles','datetime','id_category = '.$item_cat['id']); //последняя новость для категории
    switch(true){
        case $lastmod['date_diff'] <= 3: $changefreq = 'daily'; break;
        case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
        case $lastmod['date_diff'] <= 180: $changefreq = 'monthly'; break;
        default: $changefreq = 'daily'; break;
    }
    $sitemap->add_sitemap_url($url.'/articles/'.$item_cat['code'].'/',$lastmod['last_change'], $changefreq, '0.5');
    //категория+регион
    foreach($list_reg as $item_reg){
        $lastmod = getLastItem('articles','datetime','id_category = '.$item_cat['id'].' AND id_region = '.$item_reg['id']); //последняя новость для региона
        switch(true){
            case $lastmod['date_diff'] <= 3: $changefreq = 'daily'; break;
            case $lastmod['date_diff'] <= 21: $changefreq = 'weekly'; break;
            case $lastmod['date_diff'] <= 180: $changefreq = 'monthly'; break;
            default: $changefreq = 'yearly'; break;
        }
        $sitemap->add_sitemap_url($url.'/articles/'.$item_cat['code'].'/'.$item_reg['code'].'/', $lastmod['last_change'], $changefreq, '0.5');
    }
}
$sitemap->create_xmls_manually('sitemap_articles');
//вписываем url карточек
$list = $db->fetchall('SELECT 
                            '.$sys_tables['articles'].'.id,
                            '.$sys_tables['articles'].'.chpu_title,
                            '.$sys_tables['articles_categories'].'.code AS category_code,
                            DATE_FORMAT('.$sys_tables['articles'].'.datetime,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                        FROM '.$sys_tables['articles'].' 
                        LEFT JOIN '.$sys_tables['articles_categories'].' ON '.$sys_tables['articles'].'.id_category='.$sys_tables['articles_categories'].'.id 
                        ORDER BY '.$sys_tables['articles'].'.id DESC');
foreach($list as $item){
    $sitemap_url=$url.'/articles/'.$item['category_code'].'/'.$item['chpu_title'].'/';
    $sitemap->add_sitemap_url($sitemap_url,$item['lastmod'],'monthly','0.2');
}
//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually('sitemap_articles');
$sitemap->finish_maps_manually();

//#################################################################
// ОБЪЕКТЫ НЕДВИЖИМОСТИ
//главная и категории
$estate_category = array();
$estates = array('build','commercial','country','live');
$deals = array('sell','rent');
foreach($estates as $estate){
    $estate_category[] = ''.$estate;
    $sitemap->add_sitemap_url($url.'/'.$estate.'/', $today, 'daily', '0.9'); 
    foreach($deals as $deal){
         if(!($estate=='build' && $deal=='rent')){
              $estate_category[] = ''.$estate.'/'.$deal;
              $sitemap->add_sitemap_url($url.'/'.$estate.'/'.$deal.'/', $today, 'daily', '0.9'); 
              if($estate!='build') $object_types = $db->fetchall('SELECT alias FROM '.$sys_tables['object_type_groups'].' WHERE type = ?', false, $estate);
              foreach($object_types as $object_type) {
                  $estate_category[] = ''.$estate.'/'.$deal.'/'.$object_type['alias'];
                  $sitemap->add_sitemap_url($url.'/'.$estate.'/'.$deal.'/'.$object_type['alias'].'/', $today, 'daily', '0.9'); 
              }
         }
     }
}              
$sitemap->create_xmls_manually('sitemap_estate_category', true);
$sitemap->finish_maps_manually();


$other_estate_types = array('cottages'=>'cottedzhnye_poselki','housing_estates'=>'zhiloy_kompleks','business_centers'=>'business_centers');
foreach($other_estate_types as $table=>$other_estate_type){
    switch($table){
        case 'cottages': $data = 'idate'; break;
        case 'housing_estates': $data = 'date_change'; break;
        case 'business_centers': $data = 'date_change'; break;
    }

    $list = $db->fetchall('SELECT chpu_title,
                                  DATE_FORMAT('.$data.',"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                           FROM '.$sys_tables[$table].'
                           WHERE chpu_title!=""  ');
    foreach($list as $k=>$item) {
        $estate_url = !empty($item['apartments']) && $item['apartments'] == 1 ? 'apartments' : $other_estate_type; 
        $sitemap->add_sitemap_url($url.'/' . $estate_url.'/'.$item['chpu_title'].'/',$item['lastmod'],'weekly','0.7');
    }
   
}
$sitemap->create_xmls_manually('sitemap_other_estate',true);
$sitemap->finish_maps_manually();

//#################################################################
// КАЛЕНДАРЬ СОБЫТИЙ
//главная
$lastmod = getLastItem('calendar_events','date_begin',' YEAR(date_begin)<YEAR(CURDATE())'); //последняя статья
$sitemap->add_sitemap_url($url.'/calendar/',$lastmod['last_change'],'daily','0.4');
//года
$now_year = date('Y', time());
for ($i=2009;$i<=$now_year;$i++){
    switch(true){
        case $i < ($now_year - 1):      $changefreq = 'yearly';     $priority = '0.4';  break;
        case $i == ($now_year - 1):     $changefreq = 'monthly';    $priority = '0.4';  break;
        case $i == $now_year:           $changefreq = 'daily';      $priority = '0.5';  break;
    }                        
    $lastmod = getLastItem('calendar_events','date_begin',' YEAR(date_begin)= '.$now_year);
    $sitemap->add_sitemap_url($url.'/calendar/y/'.$i.'/',$lastmod['last_change'], $changefreq, $priority);
}
//вписываем url карточек
$list = $db->fetchall('SELECT 
                            '.$sys_tables['calendar_events'].'.id,
                            DATE_FORMAT('.$sys_tables['calendar_events'].'.date_begin,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod,
                            DATEDIFF(CURDATE(), '.$sys_tables['calendar_events'].'.date_begin) as date_diff,
                            YEAR(date_begin) as year_begin
                        FROM '.$sys_tables['calendar_events'].' 
                        WHERE YEAR(date_begin) > 2006 OR YEAR(date_end) > 2006
                        ORDER BY '.$sys_tables['calendar_events'].'.id DESC  ');
foreach($list as $item){
    if($item['date_diff']<=0) $frequency = 'weekly';  
    elseif($item['date_diff']<=100) $frequency = 'monthly';  
    else $frequency = 'yearly';      
    $sitemap_url=$url.'/calendar/'.$item['id'].'/';    
    $sitemap->add_sitemap_url($sitemap_url,$item['lastmod'],$frequency,'0.2');
}
//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually('sitemap_calendar', true);
$sitemap->finish_maps_manually();



//#################################################################
//ОДИНОЧНЫЕ ССЫЛКИ + МНЕНИЯ + РЕГИСТРАЦИИ НА МЕРОПРИЯТИЯ
$sitemap->add_sitemap_url($url,                     $today, 'daily',     '1');
$sitemap->add_sitemap_url($url.'/about/',           $today, 'monthly',    '0.5');
$sitemap->add_sitemap_url($url.'/contacts/',        $today, 'monthly',   '0.5');
$sitemap->add_sitemap_url($url.'/advertising/',     $today, 'monthly',   '0.5');
$sitemap->add_sitemap_url($url.'/guestbook/',       $today, 'monthly',    '0.5');
$sitemap->add_sitemap_url($url.'/guestbook/add/',   $today, 'monthly',   '0.5');
$sitemap->add_sitemap_url($url.'/help/',            $today, 'monthly',   '0.5');

//МНЕНИЯ, ПРОГНОЗЫ, ИНТЕРВЬЮ
$array = array(1=>'opinions',2=>'predictions',3=>'interview');
foreach($array as $type=>$opi){
    $lastmod = getLastItem('opinions_predictions','date','type='.$type); //последнее мнение
    $sitemap->add_sitemap_url($url.'/'.$opi.'/',$lastmod['last_change'],'weekly','0.5');
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
            $sitemap->add_sitemap_url($url.'/'.$opi.'/'.$item_cat['url'].'/',$lastmod['last_change'],$changefreq,'0.4');
        }
    }
    //вписываем url карточек
    $list = $db->fetchall('SELECT 
                                '.$sys_tables['opinions_predictions'].'.id,
                                '.$sys_tables['opinions_estate_types'].'.url,
                                DATE_FORMAT('.$sys_tables['opinions_predictions'].'.date,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                            FROM '.$sys_tables['opinions_predictions'].' 
                            LEFT JOIN '.$sys_tables['opinions_estate_types'].' ON '.$sys_tables['opinions_predictions'].'.id_estate_type='.$sys_tables['opinions_estate_types'].'.id
                            WHERE '.$sys_tables['opinions_predictions'].'.type = '.$type.'
                            ORDER BY '.$sys_tables['opinions_predictions'].'.id DESC  ');
    foreach($list as $item){
        $sitemap_url=$url.'/'.$opi.'/'.$item['url'].'/'.$item['id'].'/';
        $sitemap->add_sitemap_url($sitemap_url,$item['lastmod'],'monthly','0.2');
    }
}
//РЕГИСТРАЦИИ НА МЕРОПРИЯТИЯ
$lastmod = getLastItem('events_registration','event_date'); //последнее мнение
$sitemap->add_sitemap_url($url.'/events_registration/',$lastmod['last_change'],'monthly','0.5');
//список всеъ регистраций
$list=$db->fetchall('SELECT 
                        DATE_FORMAT(event_date,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod,
                        url 
                     FROM '.$sys_tables['events_registration']."  ");
foreach($list as $item) $sitemap->add_sitemap_url($url.'/events_registration/'.$item['url'].'/',$item['lastmod'],'monthly','0.2');

//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually('sitemap_upper', true);
$sitemap->finish_maps_manually();

//#################################################################
//ОРГАНИЗАЦИИ
$item = $db->fetch('SELECT DATE_FORMAT(MAX('.$sys_tables['users'].'.datetime),"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                    FROM '.$sys_tables['users'].' 
                    RIGHT JOIN '.$sys_tables['agencies'].' ON '.$sys_tables['users'].'.id_agency = '.$sys_tables['agencies'].'.id
                    WHERE  '.$sys_tables['agencies'].'.id > 1');
$sitemap->add_sitemap_url($url.'/organizations/', $item['lastmod'], 'monthly', '0.5');
//список по категориям
$activities = array('agencies','adv_agencies','zastr','upr','bank','devel','invest','other');
foreach($activities as $k=>$activity)  {
    $item = $db->fetch('SELECT DATE_FORMAT(MAX('.$sys_tables['users'].'.datetime),"%Y-%m-%dT%H:%i:%s+04:00") as lastmod
                        FROM '.$sys_tables['users'].' 
                        RIGHT JOIN '.$sys_tables['agencies'].' ON '.$sys_tables['users'].'.id_agency = '.$sys_tables['agencies'].'.id
                        WHERE '.$sys_tables['agencies'].'.activity&'.pow(2,$k).' AND  '.$sys_tables['agencies'].'.id > 1');    
    $sitemap->add_sitemap_url($url.'/organizations/'.$activity.'/', $item['lastmod'], 'weekly', '0.4');
}
//список компаний
$list=$db->fetchall('SELECT 
                        IF(datetime>"2000-00-00",
                            DATE_FORMAT('.$sys_tables['users'].'.datetime,"%Y-%m-%dT%H:%i:%s+04:00"),
                            DATE_FORMAT(CURDATE() - INTERVAL 370 DAY,"%Y-%m-%dT%H:%i:%s+04:00")
                        )  as lastmod,
                        '.$sys_tables['agencies'].'.id 
                     FROM '.$sys_tables['agencies'].' 
                     RIGHT JOIN '.$sys_tables['users'].' ON '.$sys_tables['users'].'.id_agency = '.$sys_tables['agencies'].'.id
                     WHERE '.$sys_tables['agencies'].'.id > 1
                     ORDER BY id DESC   
                     ');
foreach($list as $item) $sitemap->add_sitemap_url($url.'/organizations/company/'.$item['id'].'/',$item['lastmod'],'monthly','0.2');

//транслитные варианты русских букв для каталога компаний
$subst_ru = array('rA', 'rB', 'rV', 'rG', 'rD', 'rJe', 'rZh', 'rZ', 'rI', 'rK', 'rL', 'rM', 'rN', 'rO', 'rP', 'rR', 'rS', 'rT', 'rU', 'rF', 'rH', 'rC', 'rCh', 'rSh', 'rE', 'rJu', 'rJa');
$arr_alph_ru = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Э', 'Ю', 'Я');
$arr_alph_en = range('A', 'Z');
foreach($subst_ru as $item) $sitemap->add_sitemap_url($url.'/organizations/company/'.$item.'/',date('Y-m-d\\TH:i:sP',time() - mt_rand(7000000,10000000)),'monthly','0.4');
foreach($arr_alph_en as $item) $sitemap->add_sitemap_url($url.'/organizations/company/'.$item.'/',date('Y-m-d\\TH:i:sP',time() - mt_rand(7000000,10000000)),'monthly','0.4');

//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually('sitemap_organizations', true);
$sitemap->finish_maps_manually();




//#################################################################
//СПРАВОЧНЫЕ ДОКУМЕНТЫ И КОНСУЛЬТАЦИИ (service)
//справочные
$sitemap->add_sitemap_url($url.'/service/',date('Y-m-d\\TH:i:sP',time() - mt_rand(1100000,1100100)),'monthly','0.5');
$sitemap->add_sitemap_url($url.'/service/information/',date('Y-m-d\\TH:i:sP',time() - mt_rand(1100000,1100100)),'monthly','0.5');

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
    $sitemap->add_sitemap_url($url.'/service/information/'.$item['code'].'/',date('Y-m-d\\TH:i:sP',time() - mt_rand(1100000,1100100)),'monthly','0.4');
    $list_cat = $db->fetchall("SELECT CONCAT_WS('/',".$sys_tables['references_docs_types'].".code, ".$sys_tables['references_docs'].".id)  as code,
                                      ".$sys_tables['references_docs_types'].".title as category_title,
                                      ".$sys_tables['references_docs_types'].".code as category_code,  
                                      ".$sys_tables['references_docs'].".title as docs_title
                               FROM ".$sys_tables['references_docs_types']."
                               LEFT JOIN ".$sys_tables['references_docs']."  ON ".$sys_tables['references_docs'].".id_type=".$sys_tables['references_docs_types'].".id
                               WHERE ".$sys_tables['references_docs_types'].".code='".$db->real_escape_string($item['code'])."'  ");
    foreach($list_cat as $item_cat) $sitemap->add_sitemap_url($url.'/service/information/'.$item_cat['code'].'/',date('Y-m-d\\TH:i:sP',time() - mt_rand(1100000,1100100)),'monthly','0.3');
}  
//консультант
$lastmod = getLastItem('consults','question_datetime'); //последняя дата консультации
$sitemap->add_sitemap_url($url.'/service/consultant/',$lastmod['last_change'],'weekly','0.4');
$sitemap->add_sitemap_url($url.'/service/consultant/add/',date('Y-m-d\\TH:i:sP',time() - mt_rand(1100000,1100100)),'monthly','0.2');
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
    $sitemap->add_sitemap_url($url.'/service/consultant/'.$item['code'].'/',$lastmod['last_change'],$changefreq,'0.4');
}
//карточки
$list = $db->fetchall('SELECT 
                            '.$sys_tables['consults'].'.id,
                            DATE_FORMAT('.$sys_tables['consults'].'.question_datetime,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod,
                            '.$sys_tables['consults_categories'].'.code
                       FROM '.$sys_tables['consults'].'
                       LEFT JOIN '.$sys_tables['consults_categories'].' ON '.$sys_tables['consults_categories'].'.id = '.$sys_tables['consults'].'.id_category
                        ');
foreach($list as $item) $sitemap->add_sitemap_url($url.'/service/consultant/'.$item['code'].'/'.$item['id'].'/',$item['lastmod'],'monthly','0.2');

//проверяем, можно ли уже писать файл и, если уже можно, пишем и дописываем в еще один файл все, что не влезло в предыдущие
$sitemap->create_xmls_manually('sitemap_service', true);
$sitemap->finish_maps_manually();


//добавляем url из webmaster в sitemap
//читаем список 200 и 301 и дописываем его в sitemap_urls
$list = $db->fetchall('SELECT url,
                      DATEDIFF( CURDATE( ) ,'.$sys_tables['webmaster_site_urls'].'.change_date) as date_diff,
                     (SELECT CASE date_diff
                        WHEN 0 THEN "0.7" 
                            WHEN 1 THEN "0.6"
                            ELSE "0.5"
                     END)  AS priority
                    FROM '.$sys_tables['webmaster_site_urls'].' WHERE server_answer=200 OR server_answer=301');
//добавляем считанные url в sitemap_url
foreach($list as $item) $sitemap->add_sitemap_url($url.$item['url'],$today,'always',$item['priority']);

//читаем список 404 для записи в sitemap0.xml
$webmaster_404 = $db->fetchall('SELECT url,
                                DATEDIFF( CURDATE( ) ,'.$sys_tables['webmaster_site_urls'].'.change_date) as date_diff,
                                (SELECT CASE date_diff
                                   WHEN 0 THEN "0.7" 
                                       WHEN 1 THEN "0.6"
                                       ELSE "0.5"
                                END)  AS priority
                              FROM '.$sys_tables['webmaster_site_urls'].' WHERE server_answer=404');
//записываем в sitemap_urls считанные url
foreach($webmaster_404 as $item) $sitemap->add_sitemap_url($url.$item['url'],$today,'always',$item['priority'],TRUE);

//проверяем, можно ли уже писать файл, и если уже можно, пишем
$sitemap->create_xmls_manually('sitemap_404',true);
$sitemap->finish_maps_manually();  

//#################################################################################################################################################################################################################
// ОБЪЕКТЫ НЕДВИЖИМОСТИ - КАРТОЧКИ  АКТИВНЫЕ
$sitemap->create_xmls_manually('sitemap_estate',true);
foreach($estates as $estate_type){
    //floor,top - от какого и сколько объектов выбираем
    $floor = 0; $top = $links_per_query;
    do{
        unset($list_estate);
        $list_estate = $db->fetchall('SELECT '.$sys_tables[$estate_type].'.published, IF(rent=1,"rent","sell") as rent, 
                                            DATE_FORMAT('.$sys_tables[$estate_type].'.date_in,"%Y-%m-%dT%H:%i:%s+04:00") as lastmod, 
                                            '.$sys_tables[$estate_type].'.id AS obj_id 
                                    FROM '.$sys_tables[$estate_type].'  
                                    WHERE '.$sys_tables[$estate_type].'.published = 1
                                    ORDER BY  '.$sys_tables[$estate_type].'.date_change DESC, '.$sys_tables[$estate_type].'.date_in DESC, '.$sys_tables[$estate_type].'.id DESC
                                    LIMIT '.$floor.', '.$links_per_query);
        $floor=$top;$top+=$links_per_query;
        //заносим объекты в sitemap->sitemap_urls
        foreach($list_estate as $item){
            $sitemap_url=$url.'/'.$estate_type.'/'.$item['rent'].'/'.$item['obj_id'].'/';
            $sitemap->add_sitemap_url($sitemap_url,$item['lastmod'],$item['published']==2?'yearly':'monthly',$item['published']==2?'0.1':'0.6');
        }
        
        $sitemap->create_xmls_manually('sitemap_estate');
    } while(!empty($list_estate));
    //проверяем, можно ли уже писать файл  и, если уже можно, пишем
}
$sitemap->create_xmls_manually('sitemap_estate');
$sitemap->finish_maps_manually();    

//чистим массив url (все старые url уже записаны)
$sitemap->reset_sitemap_urls();

//создаем индексный файл
$sitemap->generate_sitemap_index();

//копирование сгенеренных файлов в основную папку
$sitemap_folder = $root.'/'.$sitemap->folder;
$sitemap_folder_tmp = $root.'/'.$sitemap->folder_tmp;
$dh1 = opendir($sitemap_folder);
while($filename1 = readdir($dh1)){
        if($filename1!='.' && $filename1!='..' && $filename1!='tmp') {
            unlink($sitemap_folder.$filename1);
        }
}
closedir($dh1); 

$dh = opendir($root.'/'.$sitemap->folder_tmp);
while($filename = readdir($dh)){
        if($filename!='.' && $filename!='..') {
            copy($sitemap_folder_tmp.$filename,$sitemap_folder.$filename);
        }
}
closedir($dh); 


file_put_contents($test_performance,"\r\n/*--------------*/".'END RUNNING '.date('H:i:s')."\r\n",FILE_APPEND);



$mail_text = "";
if(!empty($log)){
   $mail_text = 'Сгенерированные файлы: <br />';
   foreach($log as $text) $mail_text .= $text."<br />";
   $mail_text .= 'Генерация файла закончена.<br>Всего ссылок - <b>'.($sitemap->links_total+$sitemap->links404_total).'</b>';
} else $mail_text = 'При генерации произошла какая-то ошибка';
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

$html = $mail_text.$error_log_text.$test_performance."<br />Время генерации".round(microtime(true) - $overall_time_counter, 4)."<br/>Выборка из базы estate:".$links_per_query."шт за раз";
echo $html;
if(!DEBUG_MODE){
    $mailer = new EMailer('mail');
    $html = iconv('UTF-8', $mailer->CharSet, $html);
    // параметры письма
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Генерация карты сайта. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
    $mailer->AddAddress('web@bsn.ru');
    $mailer->From = 'sitemap@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Карта сайта BSN');
    // попытка отправить
    $mailer->Send();
} else  echo $html;
$querylog = Convert::ArrayKeySort($db->querylog, 'time', true);
file_put_contents(ROOT_PATH.'/cron/gen_sitemap/query.log',print_r($querylog,true));



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
    
    if(strstr($item['last_change'], '0000-00-00')) {
        echo '123123123';
    }
    if(!empty($item['last_change'])) return $item;
    else return $db->fetch("SELECT 
                                        DATE_FORMAT(CURDATE() - INTERVAL 370 DAY,'%Y-%m-%dT%H:%i:%s+04:00') as last_change, 
                                        370 as date_diff");
    
}
?>
