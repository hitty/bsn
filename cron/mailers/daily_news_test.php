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
require_once('includes/class.estate.statistics.php');
$memcache = new MCache(Config::$values['memcache']['host'], Config::$values['memcache']['port']);

print_r($_SERVER['argv']);
$debug = DEBUG_MODE || !empty($_SERVER['argv'][1]) ? true : false;

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
echo date('N');      
//проверка каждые 10 минут времени рассылки
if(empty($debug)){
    $check_time = $db->fetch("SELECT * FROM ".$sys_tables['news_mailer_schedule']." WHERE start > NOW() - INTERVAL 30 MINUTE AND start <= NOW() AND day_number = ?", date('N'));
    echo $db->last_query;
    if(empty($check_time)) die();
}
//сегодняшняя дата
$curdate = $db->fetch("SELECT DATE_FORMAT(CURDATE(),'%e %M %Y') as curdate")['curdate'];
Response::SetString('curdate', $curdate);
//определение периода выборки новостей
$previous = $db->fetch("SELECT * FROM ".$sys_tables['news_mailer_schedule']." WHERE day_number < ? ORDER BY day_number DESC",date('N'));
$date_where = ' AND datetime <= NOW()';
if(!empty($debug)) $date_where = '';
else if(!empty($previous)) $date_where .= '  AND datetime >= NOW() - INTERVAL '.(date('N') - $previous['day_number']).' DAY ';
else $date_where .= '  AND datetime >= NOW() - INTERVAL '.(date('N')).' DAY ';
 
    //получение списка новостей
    $news = new Content('news');
    $news_list = $news->getList(8, 0, false, false, 'newsletter_feed = 1 '.$date_where, $sys_tables['news'].".status = 2 DESC, ".$sys_tables['news'].".views_count DESC, ".$sys_tables['news_categories'].".position, ".$sys_tables['news'].".id DESC");
    if(!empty($news_list)) Response::SetArray('news_list',$news_list);

    //получение списка новостей БСН.ТВ
    $bsn_tv = new Content('doverie');
    $bsn_tv_news_list = $bsn_tv->getList(false, false, false, false, 'newsletter_feed = 1 '.$date_where, $sys_tables['doverie'].".status = 2 DESC, ".$sys_tables['doverie'].".views_count DESC, ".$sys_tables['doverie_categories'].".position, ".$sys_tables['doverie'].".id DESC");
    if(!empty($bsn_tv_news_list)) Response::SetArray('bsn_tv_news_list',$bsn_tv_news_list);

    //получение списка новостей Доверие потребителя
    $doverie = new Content('doverie');
    $doverie_news_list = $doverie->getList(false, false, false, false, 'newsletter_feed = 1 '.$date_where, $sys_tables['doverie'].".status = 2 DESC, ".$sys_tables['doverie'].".views_count DESC, ".$sys_tables['doverie_categories'].".position, ".$sys_tables['doverie'].".id DESC");
    if(!empty($doverie_news_list)) Response::SetArray('doverie_news_list',$doverie_news_list);

    //получение списка новостей Дизайн
    $news = new Content('news');
    $design_news_list = $news->getList(false, false, false, false, 'newsletter_feed = 1 AND id_category=37'.$date_where, $sys_tables['news'].".status = 2 DESC, ".$sys_tables['news'].".views_count DESC, ".$sys_tables['news_categories'].".position, ".$sys_tables['news'].".id DESC");
    if(!empty($design_news_list)) Response::SetArray('design_news_list',$design_news_list);

    //получение заголовка рассылки
    $title = $news->getList(1, 0, false, false, 'newsletter_feed = 1 AND newsletter_title=1');
    $newsletter_title = !empty($title) ? $title[0]['title'] : "Новости рынка недвижимости от БСН за ".date("d.m.Y");
    Response::SetArray('newsletter_title', $newsletter_title);

    if(empty($debug)) $db->query("UPDATE ".$sys_tables['news']." SET `newsletter_feed`=2, newsletter_title=2");

    //получение списка статей
    $articles = new Content('articles');
    $articles_list = $articles->getList(false, false, false, false,'newsletter_feed = 1'.$date_where ,$sys_tables['articles_categories'].".position, ".$sys_tables['articles'].".views_count DESC");
    if(!empty($articles_list)) Response::SetArray('articles_list', $articles_list);
    if(empty($debug)) $db->query("UPDATE ".$sys_tables['articles']." SET `newsletter_feed` = 2");


//мнения экспертов
    $opinions_where = date('w')==1 ? $sys_tables['opinions_predictions'].".date > CURDATE() - interval 3 day AND ".$sys_tables['opinions_predictions'].".`date` <= CURDATE()" : $sys_tables['opinions_predictions'].".`date` = CURDATE()";
    $opinions = new Opinions('opinions');
    $opinions_list = $opinions->getList(false, false, $opinions_where);
    if(!empty($opinions_list)) Response::SetArray('opinions_list',$opinions_list);

// Список баннеров
$banners_list = $db->fetchall("SELECT *,LEFT (`name`,2) as `subfolder` FROM ".$sys_tables['news_mailer_banners']." WHERE `published`=1 AND id!=33 ORDER BY `position`,`id`");
Response::SetArray('banners_list',$banners_list);

// Баннер - заглушка
$bsn_banners_list = $db->fetchall("SELECT *,LEFT (`name`,2) as `subfolder` FROM ".$sys_tables['news_mailer_banners']." WHERE id=33 ORDER BY `position`,`id`");
Response::SetArray('bsn_banners_list',$bsn_banners_list);
// Счетчики кол-ва объектов продажи / аренды / звонков за вчера   
$cnt = EstateStat::getCountPublished('sell');        
$cnt_sell = 0;
foreach($cnt as $k=>$cn) $cnt_sell += $cn['cnt'];
$cnt_sell = str_split($cnt_sell, 1);
Response::SetArray('estate_sell_count',$cnt_sell);

$cnt = EstateStat::getCountPublished('rent');
$cnt_rent = 0;
foreach($cnt as $k=>$cn) $cnt_rent += $cn['cnt'];
$cnt_rent = str_split($cnt_rent, 1);
Response::SetArray('estate_rent_count',$cnt_rent);

$cnt_calls = $db->fetch("SELECT SUM(`amount`) as cnt
        FROM ".$sys_tables['phone_clicks_full']."
        WHERE `date`= DATE_SUB(CURDATE(),INTERVAL 1 DAY)");
$cnt_calls['cnt'] = ( empty($cnt_calls['cnt']) ) ? (0) : ($cnt_calls['cnt']);
$cnt_calls = str_split($cnt_calls['cnt'], 1);
Response::SetArray('calls_count',$cnt_calls);

Response::SetArray('env',array('url'=>Host::GetWebPath('/'),'host'=>Host::$host));
  
if(!empty($debug)) $email_list = array(
    0 => array( 'id' => 3, 'email' => 'kya82@mail.ru'),
    1 => array( 'id' => 4, 'email' => 'hitty@bsn.ru'),
    2 => array( 'id' => 4, 'email' => 'web@bsn.ru'),
    3 => array( 'id' => 5, 'email' => 'pm@bsn.ru')
);
$mailer = new EMailer('mail');           
if( !empty($debug) || ( !empty($email_list) && (!empty($news_list) || !empty($articles_list) || !empty($opinions_list) || !empty($predictions_list))) ){
    Response::SetString('date', date("d.m.Y"));
    // инициализация шаблонизатора
    $eml_tpl = new Template('daily_news.email.html', 'cron/mailers/');
    $html = $eml_tpl->Processing();
    echo $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
    $mailer = $db->query("INSERT INTO ".$sys_tables['news_mailers']." SET datetime = CURDATE(), content = ?", $html);
    Response::SetInteger('mailer_id', $db->insert_id);

    foreach($email_list as $email){
        if(!Validate::isEmail($email['email'])){
            preg_match('!([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,4}!i', (string) $email['email'], $matches);
            if(!empty($matches[0])) $email['email'] = $matches[0];
            else $email['email'] = null;
        }
        if($email['email']){         
            Response::SetString('user_email',$email['email']);
            Response::SetString('user_id',$email['id']);
            Response::SetString('user_code',sha1(md5($email['id'].$email['email']."special!_adding")));
            $mailer = new EMailer('mail');
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $newsletter_title);
            $mailer->IsHTML(true);
            $mailer->AddAddress($email['email']);
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, "BSN.ru");
            // попытка отправить
            $mailer->Send();
        }

        
    }
} 
?>
