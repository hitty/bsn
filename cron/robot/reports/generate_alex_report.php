#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
define('__XMLPATH__',ROOT_PATH.'/bsn_to_alexander.xml');
chdir($root);

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

/**
* Обработка новых объектов
*/
// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
include('cron/class.xml.generate.php');     // Photos (работа с графикой)
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
$ids_active = $db->fetchall("SELECT id,external_id
                             FROM ".$sys_tables['build']."
                             WHERE id_user = 4764 AND published = 1 AND info_source = 3",'id');
if( !empty( $ids_active ) ) {
    $ids_conformity = $db->fetchall("SELECT id_parent, COUNT(*) AS photos_amount
                                 FROM ".$sys_tables['build_photos']."
                                 WHERE id_parent IN (".implode(',',array_keys($ids_active)).")
                                 GROUP BY id_parent");
    foreach($ids_conformity as $item){
        $xmlItem->append();
        $xmlItem->append('id', $ids_active[$item['id_parent']]['external_id'], 1);
        $xmlItem->append('url', "https://www.bsn.ru/build/sell/".$item['id_parent']."/",1);
        $xmlItem->append('photos_amount', $item['photos_amount'],1);
        ++$counter['build'];
        $counter['build_photos'] += $item['photos_amount'];
    }
    echo "build processed: ".$counter['build']." total;";
}

//жилая                                                                              
$ids_active = $db->fetchall("SELECT id,IF(rent=1,'rent','sell') AS deal_type,external_id
                             FROM ".$sys_tables['live']."
                             WHERE id_user = 4764 AND published = 1 AND info_source = 3",'id');
if( !empty( $ids_active ) ) {
    $ids_conformity = $db->fetchall("SELECT id_parent, COUNT(*) AS photos_amount
                                     FROM ".$sys_tables['live_photos']."
                                     WHERE id_parent IN (".implode(',',array_keys($ids_active)).")
                                     GROUP BY id_parent");
    /*
    $ids_conformity = $db->fetchall("SELECT id,
                                     IF(rent=1,'rent','sell') AS deal_type,
                                     external_id,
                                     IF(photos.amount IS NOT NULL,photos.amount,0) AS photos_amount 
                                     FROM ".$sys_tables['live']." 
                                     LEFT JOIN (SELECT id_parent,COUNT(*) AS amount FROM ".$sys_tables['live_photos']." GROUP BY id_parent) AS photos ON photos.id_parent = ".$sys_tables['live'].".id
                                     WHERE id_user = 4764 AND published = 1 AND info_source = 3");
    */
    foreach($ids_conformity as $item){
        $xmlItem->append();
        $xmlItem->append('id', $ids_active[$item['id_parent']]['external_id'], 1);
        $xmlItem->append('url', "https://www.bsn.ru/live/".$ids_active[$item['id_parent']]['deal_type']."/".$item['id_parent']."/",1);
        $xmlItem->append('photos_amount', $item['photos_amount'],1);
        ++$counter['live'];
        $counter['live_photos'] += $item['photos_amount'];
    }
    echo "live processed: ".$counter['live']." total;";
}
//коммерческая
$ids_active = $db->fetchall("SELECT id,IF(rent=1,'rent','sell') AS deal_type,external_id
                             FROM ".$sys_tables['commercial']."
                             WHERE id_user = 4764 AND published = 1 AND info_source = 3",'id');
if( !empty( $ids_active ) ) {
    $ids_conformity = $db->fetchall("SELECT id_parent, COUNT(*) AS photos_amount
                                     FROM ".$sys_tables['commercial_photos']."
                                     WHERE id_parent IN (".implode(',',array_keys($ids_active)).")
                                     GROUP BY id_parent");
    foreach($ids_conformity as $item){
        $xmlItem->append();
        $xmlItem->append('id', $ids_active[$item['id_parent']]['external_id'], 1);
        $xmlItem->append('url', "https://www.bsn.ru/commercial/".$ids_active[$item['id_parent']]['deal_type']."/".$item['id_parent']."/",1);
        $xmlItem->append('photos_amount', $item['photos_amount'],1);
        ++$counter['commercial'];
        $counter['commercial_photos'] += $item['photos_amount'];
    }
    echo "commercial processed: ".$counter['commercial']." total;";
}
//загородная
$ids_active = $db->fetchall("SELECT id,IF(rent=1,'rent','sell') AS deal_type,external_id
                             FROM ".$sys_tables['country']."
                             WHERE id_user = 4764 AND published = 1 AND info_source = 3",'id');
if( !empty( $ids_active ) ) {
    $ids_conformity = $db->fetchall("SELECT id_parent, COUNT(*) AS photos_amount
                                     FROM ".$sys_tables['country_photos']."
                                     WHERE id_parent IN (".implode(',',array_keys($ids_active)).")
                                     GROUP BY id_parent");
    foreach($ids_conformity as $item){
        $xmlItem->append();
        $xmlItem->append('id', $ids_active[$item['id_parent']]['external_id'], 1);
        $xmlItem->append('url', "https://www.bsn.ru/country/".$ids_active[$item['id_parent']]['deal_type']."/".$item['id_parent']."/",1);
        $xmlItem->append('photos_amount', $item['photos_amount'],1);
        ++$counter['country'];
        $counter['country_photos'] += $item['photos_amount'];
    }
    echo "country processed: ".$counter['country']." total;";
}
$counter['total'] = $counter['live'] + $counter['build'] + $counter['country'] + $counter['commercial'];
$counter['total_photos'] = $counter['live_photos'] + $counter['build_photos'] + $counter['country_photos'] + $counter['commercial_photos'];
$xml->formatOutput = true;
$xml->save(__XMLPATH__);

if(file_exists(__XMLPATH__.".gz")) unlink(__XMLPATH__.".gz");
exec("gzip -rv ".__XMLPATH__);
exec("chmod 777 ".__XMLPATH__.".gz");

echo "XML соответствия успешно создан: ".$counter['total']." объектов всего.";

$mailer = new EMailer('mail');
// перевод письма в кодировку мейлера
$html = "Выгрузилось: <br />
- всего: ".$counter['total']." фотографий - ".$counter['total_photos']."<br />
- жилая: ".$counter['live']." фотографий - ".$counter['live_photos']."<br />
- стройка: ".$counter['build']." фотографий - ".$counter['build_photos']."<br />
- коммерческая: ".$counter['commercial']." фотографий - ".$counter['commercial_photos']."<br />
- загородка: ".$counter['country']." фотографий - ".$counter['country_photos']."<br />";
$html = iconv('UTF-8', $mailer->CharSet, $html);
// параметры письма
$mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Выгрузка объектов Александр-недвижимость. '.date('Y-m-d H:i:s'));
$mailer->Body = $html;
$mailer->AltBody = strip_tags($html);
$mailer->IsHTML(true);
$mailer->AddAddress("alexander@pro.bkn.ru");
$mailer->AddAddress("hitty@bsn.ru");
$mailer->AddAddress("web@bsn.ru");
$mailer->AddAttachment(__XMLPATH__.".gz");
$mailer->From = 'no-reply@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Выгрузка объектов Александр-недвижимость');
$mailer->Send();
?>