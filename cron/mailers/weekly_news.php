#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SCRIPT_NAME']) && preg_match('/.+\.int/i', $_SERVER['SCRIPT_NAME']) || !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int/i', $_SERVER['SERVER_NAME']) ? true : false);
define('TEST_MODE', !empty($_SERVER['SCRIPT_FILENAME']) && preg_match('/test\.bsn\.ru/sui', $_SERVER['SCRIPT_FILENAME']) ? true : false);

/** @var TYPE_NAME $root */
$root = TEST_MODE ? realpath( '/home/bsn/sites/test.bsn.ru/public_html/trunk/' ) : ( DEBUG_MODE ? realpath( "../.." ) : realpath('/home/bsn/sites/bsn.ru/public_html/' ) ) ;
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

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
if( !class_exists( 'Photos' ) )  require_once('includes/class.photos.php');;       // подключен для использования EstateListBuild
//require_once('../../sale.bsn.ru/public_html/includes/class.sale.php');  
require_once('includes/class.estate.statistics.php');
$memcache = new MCache(Config::$values['memcache']['host'], Config::$values['memcache']['port']);
$debug = DEBUG_MODE || ( !empty($_SERVER['argv'][1]) && $_SERVER['argv'][1] === 'test' ) ? true : false;
$send = ( !empty($_SERVER['argv'][1]) && $_SERVER['argv'][1] === 'send' ) ? true : false;
// Инициализация рабочих классов
$db = !TEST_MODE ? new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']) : new mysqli_db(Config::$values['mysql_remote']['host'], Config::$values['mysql_remote']['user'], Config::$values['mysql_remote']['pass']);
$db->querys("set names ".Config::$values['mysql_remote']['charset']);
$db->querys("set lc_time_names = 'ru_RU'");
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
//проверка каждые 10 минут времени рассылки//подключить в случае ежедневной рассылки
if( empty( $debug ) && empty( $send ) ){
    $check_time = $db->fetch("SELECT status FROM ".$sys_tables['check_news_time']." WHERE sent_time > NOW() - INTERVAL 10 MINUTE AND sent_time <= NOW()");
    if(empty($check_time) ) die( 'Wrong time' );
}

//получение списка новостей
$news = new Content('news');
$is_weekly = false;
//Еженедельная рассылка - установить параметр в 5
$dates = ( date('M',time() - 518400) == date( 'M', time() ) ? ltrim( date('d',time() - 518400), '0' ) : Convert::ru_date( date('d M Y',time() - 518400), false ) ) . " - ".Convert::ru_date( date('d M Y') ) ;
$email_title = ( !empty( $debug ) ? 'Тест: ' : '' )  . "Топ новостей за неделю. " . $dates;
$news_list = array();            

$news_list = $news->getList( 10, 0, false, false, "`datetime` >= NOW() - INTERVAL 7 DAY AND `datetime` <= NOW()  AND newsletter_feed = 1", $sys_tables['news'].".views_count DESC");
foreach( $news_list as $n => $item )  createPhoto( $item );

if( empty( $debug ) ) $db->querys("UPDATE ".$sys_tables['news']." SET `newsletter_feed`=2, partner_feed = 2");

//получение списка новостей Доверия
$doverie = new Content('doverie');
$doverie_news_list = $doverie->getList( 1, 0, false, false, " `datetime` >= NOW() - INTERVAL 7 DAY AND `datetime` <= NOW()  AND newsletter_feed = 1 ", $sys_tables['doverie'].".views_count DESC" );
if( !empty( $doverie_news_list ) )  {
    createPhoto( $doverie_news_list[0] );
    array_splice( $news_list, 0, 0, [ $doverie_news_list[0] ] );
}

//блок "Интервью" -//- заккоментируйте, чтобы не отображать блоки "Рекомендуем" и "Интервью"
$opinions = new Opinions('opinions');
$opinions_list = $opinions->getList(1,0," type=1 AND ".$sys_tables['opinions_predictions'].".date > CURDATE() - interval 5 day AND ".$sys_tables['opinions_predictions'].".`date` <= CURDATE()");
if( !empty( $opinions_list ) )  {
    $opinions_list[0]['photo'] = $opinions_list[0]['experts_photo'];
    $opinions_list[0]['subfolder'] = $opinions_list[0]['experts_subfolder'];
    createPhoto( $opinions_list[0] );
    array_splice( $news_list, 6, 0, [ $opinions_list[0] ] );
}
    
//получение списка новостей БСН.ТВ
$bsn_tv = new Content('bsntv');
$bsn_tv_news_list = $bsn_tv->getList( 1, 0, false, false, "`datetime` > DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND `datetime` <= CURDATE() AND newsletter_feed = 1", $sys_tables['bsntv'].".views_count DESC" );
if( !empty( $bsn_tv_news_list ) )  {
    createPhoto( $bsn_tv_news_list[0] );
    array_splice( $news_list, 0, 0, [ $bsn_tv_news_list[0] ] );
}



$is_weekly = true;
Response::SetString( 'is_weekly',$is_weekly );
Response::SetString( 'email_title', $email_title );
Response::SetString( 'dates', $dates );

//блок "Статьи
$articles = new Content('articles');
$articles_list = $articles->getList( 2, 0, false, false, "`datetime` >= NOW() - INTERVAL 6 DAY AND `datetime` <= NOW() AND newsletter_feed = 1", $sys_tables['articles'].".views_count DESC");
if( !empty( $articles_list ) ) {
    $article = $articles_list[0];
    Response::SetArray( 'article', $article ); 
    createPhoto( $article );
        
}

$list = [];
if( !empty( $news_list ) ) $list = array_merge( $list, $news_list );

Response::SetArray( 'list', array_slice( $list, 0, 9 ) );
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
        createPhoto( $item );
        
    }
}   


//Рабочий список получателей email рассылки
$email_list = $db->fetchall("SELECT DISTINCT s.email, s.id FROM ( 
                                (SELECT email, id FROM ".$sys_tables['users']." WHERE YEAR(last_enter) > 2015 AND email!='' AND subscribe_news = 1) 
                                UNION
                                (SELECT email, id FROM ".$sys_tables['subscribed_users']." WHERE email!='' AND published=1) 
                            ) as s  GROUP BY s.email ORDER BY id DESC");      
//Тестовый список получателей email рассылки
if( DEBUG_MODE )
    $email_list = array(
        0 => array( 'id' => 3, 'email' => 'kya1982@gmail.com')
    );
else if( !empty( $debug ) ) 
    $email_list = array(
        0 => array( 'id' => 3, 'email' => 'kya1982@gmail.com'),
        2 => array( 'id' => 4, 'email' => 'web@bsn.ru'),
        4 => array( 'id' => 4, 'email' => 'val@bsn.ru'),
        5 => array( 'id' => 5, 'email' => 'pm@bsn.ru'),
        6 => array( 'id' => 6, 'email' => 'pr@bsn.ru')
    );

$mailer = new EMailer('mail');    
 
if(!empty($email_list) && !empty($news_list)){
    Response::SetString('date', date("d.m.Y"));
    //создание кампании для рассылки
    $db->insertFromArray( 
        $sys_tables['newsletters_campaigns'], 
        array( 'title' => $email_title )
    );
    $id_campaign = $db->insert_id;
    if( empty( $id_campaign ) ) $id_campaign = $db->fetch(" SELECT MAX(id) as id FROM " . $sys_tables['newsletters_campaigns'] )['id'];
    // инициализация шаблонизатора
    Response::SetString('host','www.bsn.' . ( DEBUG_MODE ? 'int' : 'ru' ) );
    foreach($email_list as $email){
        $eml_tpl = new Template('weekly_news.email.html', 'cron/mailers/');
        if(!Validate::isEmail($email['email'])){
            preg_match('!([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,4}!i', (string) $email['email'], $matches);
            if(!empty($matches[0])) $email['email'] = $matches[0];
            else $email['email'] = null;
        }
        if($email['email']){
            Response::SetString('user_email',$email['email']);
            Response::SetString('user_id',$email['id']);
            Response::SetString('user_code',sha1(md5($email['id'].$email['email']."special!_adding")));
            Response::SetString( 'pixel', '<img src="https://www.bsn.ru/pxl/?campaign=' . $id_campaign . '&email=' . $email['email'] . '&status=2" />');
            $mailer = new EMailer('mail');
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet, $html);
            // параметры письма
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $email_title);
            $mailer->IsHTML(true);
            $mailer->AddAddress($email['email']);
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
            // попытка отправить
            $user_data = array(
                'id_campaign' => $id_campaign,
                'email' => $email['email'],
                'status' => 1
            );
            if( $mailer->Send() ) $db->insertFromArray( $sys_tables['newsletters'], $user_data );
        }
    }
} 

function createPhoto( $item, $params = [] ){
    global $root;
    if( empty( $params ) )
        $params = [
            [
                'watermark' => false,
                'width' => '280',
                'height' => '210',
                'folder' => 'sm'
            ],
            [
                'watermark' => true,
                'width' => '570',
                'height' => '240',
                'folder' => 'big'
            ]
        ];
    foreach( $params as $p => $param ){
        //заглавная фотка
        $dest = $root . '/img/uploads/mailers/' . $param['folder'] . '/' . $item['photo'];
        if( !file_exists( $dest ) ) {
            $src = $root . '/img/uploads/big/' . $item['subfolder'] . '/' . $item['photo'];
            if( !file_exists( $dest ) && file_exists( $src ) ) {
                $watermark_src = $param['watermark'] ? '/img/layout/mailer/black_bg.png' : '';
                Photos::imageResize( 
                    $src, 
                    $dest, 
                    $param['width'], 
                    $param['height'], 
                    'cut',  
                    90, 
                    '#ffffff', 
                    $watermark_src, 
                    70
                );      
            }     
        }     
    }
}

?>
