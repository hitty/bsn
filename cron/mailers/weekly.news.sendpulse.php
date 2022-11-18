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
require_once('includes/class.content.php');
require_once('includes/class.opinions.php');
require_once('includes/class.memcache.php');     // MCache (memcached, кеширование в памяти)
require_once('includes/class.estate.php');       // подключен для использования EstateListBuild
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;       // подключен для использования EstateListBuild
//require_once('../../sale.bsn.ru/public_html/includes/class.sale.php');  
require_once('includes/class.estate.statistics.php');
$memcache = new MCache(Config::$values['memcache']['host'], Config::$values['memcache']['port']);
print_r($_SERVER['argv']);
$debug = DEBUG_MODE || !empty($_SERVER['argv'][1]) ? true : false;
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$argc = ( !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false ) || DEBUG_MODE;
//проверка каждые 10 минут времени рассылки//подключить в случае ежедневной рассылки
if(empty($argc)){
    $check_time = $db->fetch("SELECT status FROM ".$sys_tables['check_news_time']." WHERE sent_time > NOW() - INTERVAL 10 MINUTE AND sent_time <= NOW()");
    if(empty($check_time) || date('N') != 5) die();
}

//получение списка новостей
$news = new Content('news');
$is_weekly = false;
//Еженедельная рассылка - установить параметр в 5
$dates = ( date('M',time() - 518400) == date( 'M', time() ) ? ltrim( date('d',time() - 518400), 0 ) : Convert::ru_date( ltrim( date('d M',time() - 518400), 0 ), false ) ) . " - ".Convert::ru_date( ltrim( date('d M Y'), 0) ) ;
$email_title = "Новости недвижимости за " . $dates;
$news_list = array();

if( $top_news = $news->getList( 1, 0, false, false, "DATE_FORMAT (`datetime`,'%Y-%m-%d') =  DATE_SUB(CURDATE(),INTERVAL 4 DAY) AND newsletter_feed = 1 AND partner_feed = 2", $sys_tables['news'].".views_count DESC"))
    array_push( $news_list, $top_news[0] );
if( $top_news = $news->getList( 1, 0, false, false, "DATE_FORMAT (`datetime`,'%Y-%m-%d') =  DATE_SUB(CURDATE(),INTERVAL 3 DAY) AND newsletter_feed = 1 AND partner_feed = 2", $sys_tables['news'].".views_count DESC"))
    array_push( $news_list, $top_news[0] );
if( $top_news = $news->getList( 1, 0, false, false, "DATE_FORMAT (`datetime`,'%Y-%m-%d') =  DATE_SUB(CURDATE(),INTERVAL 2 DAY) AND newsletter_feed = 1 AND partner_feed = 2", $sys_tables['news'].".views_count DESC"))
    array_push( $news_list, $top_news[0] );
if( $top_news = $news->getList( 1, 0, false, false, "DATE_FORMAT (`datetime`,'%Y-%m-%d') =  DATE_SUB(CURDATE(),INTERVAL 1 DAY) AND newsletter_feed = 1 AND partner_feed = 2", $sys_tables['news'].".views_count DESC"))
    array_push( $news_list, $top_news[0] );
if( $top_news = $news->getList( 1, 0, false, false, "DATE(`datetime`) =  CURDATE() AND newsletter_feed = 1 AND partner_feed = 2", $sys_tables['news'].".views_count DESC"))
    array_push( $news_list, $top_news[0] );
print_r( $news_list );
//
$ids = array();
foreach( $news_list as $n => $news_item ) $news_ids[] = $news_item['id'];
$partner_news = $news->getList( 2, 0, false, false, "DATE(`datetime`) <=  CURDATE() AND partner_feed = 1", $sys_tables['news'].".views_count DESC");
foreach( $partner_news as $n => $partner_news_item ) $news_ids[] = $partner_news_item['id'];
$news_list = array_merge( 
    $news_list, 
    $news->getList( 10 - count( $news_list ) - count( $partner_news ), 0, false, false, "`datetime` > DATE_SUB(CURDATE(),INTERVAL 5 DAY) AND `datetime` <= CURDATE() AND " . $sys_tables['news'] . ".id NOT IN (" . implode( ",", $news_ids ). ")", $sys_tables['news'].".views_count DESC")
);

if( !empty( $partner_news ) ) $news_list = array_merge( $news_list, $partner_news );

 if( empty( $debug ) ) $db->querys("UPDATE ".$sys_tables['news']." SET `newsletter_feed`=2, partner_feed = 2");
    
//получение списка новостей БСН.ТВ
$bsn_tv = new Content('bsntv');
$bsn_tv_news_list = $bsn_tv->getList( 1, 0, false, false, "`datetime` > DATE_SUB(CURDATE(),INTERVAL 5 DAY) AND `datetime` <= CURDATE() AND newsletter_feed = 1", $sys_tables['bsntv'].".views_count DESC" );
if( !empty($bsn_tv_news_list) && count( $news_list ) > 9 ) array_pop( $news_list );
    
$is_weekly = true;
Response::SetString( 'is_weekly',$is_weekly );
Response::SetString( 'email_title', $email_title );
Response::SetString( 'dates', $dates );

//блок "Статьи
$articles = new Content('articles');
$articles_list = $articles->getList( 1, 0, false, false, "`datetime` >= NOW() - INTERVAL 5 DAY AND `datetime` <= NOW()", $sys_tables['articles'].".views_count DESC");
if( !empty( $articles_list ) ) {
    $article = $articles_list[0];
    Response::SetArray( 'article', $article ); 
    
    //заглавная фотка
    $dest = $root . '/img/uploads/mailers/' . $article['photo'];
    $src = $root . '/img/uploads/big/' . $article['subfolder'] . '/' . $article['photo'];
    if( !file_exists( $dest ) && file_exists( $src ) ) {
        $watermark_src = '/img/layout/mailer/black_bg.png';
        Photos::imageResize( 
            $src, 
            $dest, 
            580, 
            250, 
            'cut',  
            90, 
            '#ffffff', 
            $watermark_src, 
            70
        );      
    }     
}
  
//блок "Интервью" -//- заккоментируйте, чтобы не отображать блоки "Рекомендуем" и "Интервью"
$opinions = new Opinions('opinions');
$opinions_list = $opinions->getList(1,0,"type=1 AND ".$sys_tables['opinions_predictions'].".date > CURDATE() - interval 5 day AND ".$sys_tables['opinions_predictions'].".date < CURDATE() AND ".$sys_tables['opinions_predictions'].".`date` <= CURDATE()");
if( !empty($opinions_list) && 
    ( 
        ( !empty($bsn_tv_news_list) && count( $news_list ) > 8 ) || 
        count( $news_list ) > 9
    )
) array_pop( $news_list );

$list = array();

if( !empty( $news_list ) ) $list = array_merge( $list, $news_list );
if( !empty( $bsn_tv_news_list ) ) $list = array_merge( $list, $bsn_tv_news_list );
if( !empty( $opinions_list ) ) $list = array_merge( $list, $opinions_list );

Response::SetArray( 'list', $list );

//блок VIP - объекты
$vip_list = array();
$estate_types = array('live','build', 'commercial', 'country');
foreach($estate_types as $estate_type){
    //формируем доп. условие в зависимости от того, что нужно
    $where = $sys_tables[$estate_type].".status = 6 AND ".$sys_tables[$estate_type].".published = 1 AND ".$sys_tables[$estate_type].".id_main_photo > 0 ";
    switch($estate_type){
        case 'live': $list = new EstateListLive(TYPE_ESTATE_LIVE);   break;
        case 'build': $list = new EstateListBuild(TYPE_ESTATE_BUILD);   break;
        case 'commercial': $list = new EstateListCommercial(TYPE_ESTATE_COMMERCIAL);   break;
        case 'country': $list = new EstateListCountry(TYPE_ESTATE_COUNTRY);   break;
    }
    $list = $list->Search($where, 30);
    if(!empty($list)) foreach($list as $k=>$item) {
        $list[$k]['type'] = $estate_type;
        switch($list[$k]['status']){
            case 3: 
                $list[$k]['highlighting'] = "promo";
                break;
            case 4:
                $list[$k]['highlighting'] = "premium";
                break;
            case 6:
                $list[$k]['highlighting'] = "vip";
                break;
        }
        $vip_list[] = $list[$k];
    }
}      
if( !empty( $vip_list ) ) {
    $vip_list_count = count( $vip_list );
    if( count( $vip_list ) > 2 ) 
        $vip_list_count =  count( $vip_list ) % 2 == 1 ? count( $vip_list ) - 1 : count( $vip_list );
    
    $vip_list = array_splice( $vip_list, 0, $vip_list_count );     
    Response::SetArray('vip_list', $vip_list);

    foreach( $vip_list as $k=> $item){
        //заглавная фотка
        $dest = $root . '/img/uploads/mailers/' . $item['photo'];
        if( !file_exists( $dest ) ) {
            Photos::imageResize( 
                $root . '/img/uploads/big/' . $item['subfolder'] . '/' . $item['photo'], 
                $dest, 
                280, 
                210, 
                'cut',  
                90, 
                '#ffffff', 
                false, 
                70
            );      
        }        
    }
}   

$date = new DateTime(); 
$date->modify("+15 minutes");

Response::SetString('date', date("d.m.Y"));
$eml_tpl = new Template('weekly_news.email.html', 'cron/mailers/');
echo $html = $eml_tpl->Processing();

if( !class_exists('Sendpulse') ) require_once("includes/class.sendpulse.php");
$sendpulse = new Sendpulse( $debug ? 'test' : 'subscriberes' );
$result = $sendpulse->createCampaign( $email_title, $html, $email_title,  $date->format('Y-m-d H:i:00') );

// инициализация шаблонизатора
Response::SetString( 'content', 'Создана ' . (  $debug ? 'тестовая ' : '' ) . 'РК «Еженедельная рассылка» в Сендпульс. Отправка рассылки в ' . $date->format('d.m.Y H:i:00') . ' <br/.<br/>' . print_r( $result, true ) );
$eml_tpl = new Template('report.html', 'modules/mailers/');
// формирование html-кода письма по шаблону
$email_html = $eml_tpl->Processing();         
//отправка письма
$sendpulse = new Sendpulse( );
$result = $sendpulse->sendMail( 'Создана ' . (  $debug ? 'тестовая ' : '' ) . 'РК «Еженедельная рассылка» в Сендпульс', $email_html, 'Юрий', 'kya1982@gmail.com' );

?>
