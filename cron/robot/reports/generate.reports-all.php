#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
define('TEST_MODE', !empty($_SERVER['SCRIPT_FILENAME']) && preg_match('/test\.bsn\.ru/sui', $_SERVER['SCRIPT_FILENAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);

require_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

/**
* Обработка новых объектов
*/
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;     // Photos (работа с графикой)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('cron/class.xml.generate.php');     // Photos (работа с графикой)
require_once("includes/class.sendpulse.php");
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$argc = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;
if(DEBUG_MODE) $argc = "4516";
if( empty( $argc ) ) die( ' empty argc ' );


$agency_info = $db->fetch( " 
                            SELECT 
                                " . $sys_tables['agencies'] . ".*,
                                " . $sys_tables['users'] . ".id as user_id 
                            FROM " . $sys_tables['agencies'] . " 
                            LEFT JOIN " . $sys_tables['users'] . " ON " . $sys_tables['users'] . ".id_agency = " . $sys_tables['agencies'] . ".id AND " . $sys_tables['users'] . ".agency_admin = 1
                            WHERE " . $sys_tables['agencies'] . ".id = ?", 
                            $argc 
);
if( empty( $agency_info ) )  die( ' empty agency_info ' );
$filename =  Convert::chpuTitle( $agency_info['title'] ) . '_' . date('d_m') . '.xml';
define( '__XMLPATH__', ROOT_PATH . '/xml/reports/' . $filename );
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$xml = new DOMDocument('1.0','UTF-8');
$xmlUrlset = $xml->appendChild($xml->createElement('root'));
$xmlgenerationdate = $xmlUrlset->appendChild($xml->createElement('generation-date'));
$xmlgenerationdate -> appendChild($xml->createTextNode( date('c') ));
$xmlItem = new generateXml;

$counter = array('live'=>0,'live_photos'=>0,'build'=>0,'build_photos'=>0,'commercial'=>0,'commercial_photos'=>0,'country'=>0,'country_photos'=>0,);
//стройка
$ids_conformity = $db->fetchall("SELECT id,
                                 IF(rent=1,'rent','sell') AS deal_type,
                                 external_id,
                                 IFNULL(photos.amount,0) AS photos_amount , views_count, views_count_week
                                 FROM ".$sys_tables['build']." 
                                 LEFT JOIN (SELECT id_parent,COUNT(*) AS amount FROM ".$sys_tables['build_photos']." GROUP BY id_parent) AS photos ON photos.id_parent = ".$sys_tables['build'].".id
                                 WHERE id_user = " . $agency_info['user_id'] . " AND published = 1 ");
foreach($ids_conformity as $item){
    $xmlItem->append();
    $xmlItem->append('id', $item['external_id'], 1);
    $xmlItem->append('url', "https://www.bsn.ru/estate/build/".$item['deal_type']."/".$item['id']."/",1);
    $xmlItem->append('photos_amount', $item['photos_amount'],1);
    $xmlItem->append('views_today', $item['views_count'],1);
    $xmlItem->append('views_week', $item['views_count_week'] + $item['views_count'],1);
    ++$counter['build'];
    $counter['build_photos'] += $item['photos_amount'];
}
echo "build processed: ".$counter['build']." total;";

//жилая
$ids_conformity = $db->fetchall("SELECT id,
                                 IF(rent=1,'rent','sell') AS deal_type,
                                 external_id,
                                 IFNULL(photos.amount,0) AS photos_amount , views_count, views_count_week
                                 FROM ".$sys_tables['live']." 
                                 LEFT JOIN (SELECT id_parent,COUNT(*) AS amount FROM ".$sys_tables['live_photos']." GROUP BY id_parent) AS photos ON photos.id_parent = ".$sys_tables['live'].".id
                                 WHERE id_user = " . $agency_info['user_id'] . " AND published = 1 ");
foreach($ids_conformity as $item){
    $xmlItem->append();
    $xmlItem->append('id', $item['external_id'], 1);
    $xmlItem->append('url', "https://www.bsn.ru/estate/live/".$item['deal_type']."/".$item['id']."/",1);
    $xmlItem->append('photos_amount', $item['photos_amount'],1);
    $xmlItem->append('views_today', $item['views_count'],1);
    $xmlItem->append('views_week', $item['views_count_week'] + $item['views_count'],1);
    ++$counter['live'];
    $counter['live_photos'] += $item['photos_amount'];
}
echo "live processed: ".$counter['live']." total;";
//коммерческая
$ids_conformity = $db->fetchall("SELECT id,
                                 IF(rent=1,'rent','sell') AS deal_type,
                                 external_id,
                                 IFNULL(photos.amount,0) AS photos_amount , views_count, views_count_week
                                 FROM ".$sys_tables['commercial']." 
                                 LEFT JOIN (SELECT id_parent,COUNT(*) AS amount FROM ".$sys_tables['commercial_photos']." GROUP BY id_parent) AS photos ON photos.id_parent = ".$sys_tables['commercial'].".id
                                 WHERE id_user = " . $agency_info['user_id'] . " AND published = 1 ");
foreach($ids_conformity as $item){
    $xmlItem->append();
    $xmlItem->append('id', $item['external_id'], 1);
    $xmlItem->append('url', "https://www.bsn.ru/estate/commercial/".$item['deal_type']."/".$item['id']."/",1);
    $xmlItem->append('photos_amount', $item['photos_amount'],1);
    $xmlItem->append('views_today', $item['views_count'],1);
    $xmlItem->append('views_week', $item['views_count_week'] + $item['views_count'],1);

    ++$counter['commercial'];
    $counter['commercial_photos'] += $item['photos_amount'];
}
echo "commercial processed: ".$counter['commercial']." total;";
//загородная
$ids_conformity = $db->fetchall("SELECT id,
                                 IF(rent=1,'rent','sell') AS deal_type,
                                 external_id,
                                 IFNULL(photos.amount,0) AS photos_amount, views_count, views_count_week
                                 FROM ".$sys_tables['country']."
                                 LEFT JOIN (SELECT id_parent,COUNT(*) AS amount FROM ".$sys_tables['country_photos']." GROUP BY id_parent) AS photos ON photos.id_parent = ".$sys_tables['country'].".id
                                 WHERE id_user = " . $agency_info['user_id'] . " AND published = 1 ");
foreach($ids_conformity as $item){
    $xmlItem->append();
    $xmlItem->append('id', $item['external_id'], 1);
    $xmlItem->append('url', "https://www.bsn.ru/estate/country/".$item['deal_type']."/".$item['id']."/",1);
    $xmlItem->append('photos_amount', $item['photos_amount'],1);
    $xmlItem->append('views_today', $item['views_count'],1);
    $xmlItem->append('views_week', $item['views_count_week'] + $item['views_count'],1);

    ++$counter['country'];
    $counter['country_photos'] += $item['photos_amount'];
}
echo "country processed: ".$counter['country']." total;";
$counter['total'] = $counter['live'] + $counter['build'] + $counter['country'] + $counter['commercial'];
$counter['total_photos'] = $counter['live_photos'] + $counter['build_photos'] + $counter['country_photos'] + $counter['commercial_photos'];
$xml->formatOutput = true;
$xml->save(__XMLPATH__);

exec( "chmod 777 " . __XMLPATH__ );

echo "XML соответствия успешно создан: ".$counter['total']." объектов всего.";

// перевод письма в кодировку мейлера
$text = "Выгрузилось: <br />  <br />
- всего: ".$counter['total']."<br />  <br />
- жилая: ".$counter['live']."<br />
- стройка: ".$counter['build']."<br />
- коммерческая: ".$counter['commercial']."<br />
- загородка: ".$counter['country']."<br />";
Response::SetString( 'text', $text );

$mailer_title = 'Выгрузка объектов '.$agency_info['title'].'. '.date('d.m.Y');
Response::SetString( 'mailer_title', $mailer_title );

// инициализация шаблонизатора
$eml_tpl = new Template('generate.report.email.html', 'cron/robot/reports/');
$html = $eml_tpl->Processing();
$emails = array(
    array(
        'name' => '',
        'email'=> 'hitty@bsn.ru'
    )
);
$attachments = array(
    'path' => $filename,
    'title' => file_get_contents( __XMLPATH__ ),
);

if(!empty( $agency_info['email_service'] ) ) 
    $emails = [
        [
            'name' => '',
            'email'=> $agency_info['email_service']
        ],
        [
                'name' => '',
                'email'=> 'kya1982@gmail.com'
        ],
    ];
//отправка письма
$sendpulse = new Sendpulse( );
$result = $sendpulse->sendMail( $mailer_title, $html, false, false, $mailer_title, 'no-reply@bsn.ru', $emails, $attachments );
var_dump( $result );
?>