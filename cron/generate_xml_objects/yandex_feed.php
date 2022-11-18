#!/usr/bin/php
<?php
// переход в корневую папку сайта
//$root = realpath('/home/bsn/sites/bsn.ru/public_html/' );
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
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
include('includes/functions.php');          // функции  из модуля
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
//include('cron/class.xml.generate.php');     // Photos (работа с графикой)
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//файл с результатом
$filename='yandex_news.xml';

//создание XML по http://partner.news.yandex.ru/tech.pdf
//обязательные теги обозначены звездочкой

$xml = new DOMDocument('1.0','windows-1251');

$source_title='Новости недвижимости';//'название RSS-потока 
$description='Недвижимость в Санкт-Петербурге (СПб): продажа и аренда недвижимости - БСН.ру';//описание источника (1 предложение)
$link='https://www.bsn.ru'.'/news/';//ссылка на источник
$image_url='https://www.bsn.ru'.'/img/layout/logo.png';//url логотипа*
$image_description='';

//rss*
$xmlRss = $xml->appendChild($xml->createElement('rss'));
$xmlRss->setAttribute('xmlns:yandex','http://news.yandex.ru');
$xmlRss->setAttribute('xmlns:media','http://search.yahoo.com/mrss/');
$xmlRss->setAttribute('version','2.0');

//channel*
$xmlChannel = $xmlRss->appendChild($xml->createElement('channel'));

//title, link, description
$xmlChannel->appendChild($xml->createElement('title',$source_title));
$xmlChannel->appendChild($xml->createElement('link',$link));
$xmlChannel->appendChild($xml->createElement('description',$description));

//image: url*, title, description
$xmlSourceimage=$xmlChannel->appendChild($xml->createElement('image'));
$xmlSourceimage->appendChild($xml->createElement('url',$image_url));
$xmlSourceimage->appendChild($xml->createElement('title',$source_title));
if($image_description!='') $xmlSourceimage->appendChild($xml->createElement('link',$image_description));

$list = array();

//список новостей для яндекса (выбираем новости за последние 8 дней, у которых установлен флаг yandex_feed)
$sql="SELECT ".$sys_tables['news'].".id, 
             DATE_FORMAT(".$sys_tables['news'].".datetime,'%a, %e %b %Y %T ') AS date,
             ".$sys_tables['news'].".title,
             ".$sys_tables['news'].".chpu_title,
             ".$sys_tables['news'].".content,
             ".$sys_tables['news'].".content_short,
             ".$sys_tables['news_categories'].".code AS category_code,
             ".$sys_tables['news_categories'].".title AS category_title,
             ".$sys_tables['news_regions'].".code AS region_code,
             ".$sys_tables['news_photos'].".name AS pic_name,
             LEFT(".$sys_tables['news_photos'].".name,2) AS pic_subfolder
      FROM ".$sys_tables['news']." 
      LEFT JOIN ".$sys_tables['news_categories']." ON ".$sys_tables['news_categories'].".id=".$sys_tables['news'].".id_category
      LEFT JOIN ".$sys_tables['news_regions']." ON ".$sys_tables['news_regions'].".id=".$sys_tables['news'].".id_region
      LEFT JOIN ".$sys_tables['news_photos']." ON ".$sys_tables['news_photos'].".id=".$sys_tables['news'].".id_main_photo
      WHERE (DATEDIFF(NOW(),".$sys_tables['news'].".datetime)<219) AND yandex_feed=1
      ORDER BY ".$sys_tables['news'].".id DESC";
$list = $db->fetchall($sql);

//часовой пояс для даты
$timezone=preg_replace('/[^0-9+]/','',date('P',time()));

foreach($list as $k=>$item){
    
    //item* - тег новости
    $xmlItem=$xmlChannel->appendChild($xml->createElement('item'));
    
    //title* (title новости)
    $xmlItem->appendChild($xml->createElement('title', htmlspecialchars(strip_tags($item['title']),ENT_QUOTES,'UTF-8')));
    
    //link* (ссылка на страницу с новостью)
    $url='https://www.bsn.ru'.'/news/'.$item['category_code'].'/'.$item['region_code'].'/'.$item['chpu_title'].'/';
    $xmlItem->appendChild($xml->createElement('link',htmlspecialchars($url)));
    
    //категория новости - "оригинальная, как в издании". 
    //(документ говорит, что рубрикация должна быть фиксирована заранее)
    //$xmlItem->appendChild($xml->createElement('category',$item['category_title']));
    
    //description (если есть, краткое описание)
    if(!empty($item['content_short'])){
        $xmlDescription=$xmlItem->appendChild($xml->createElement('description',htmlspecialchars(preg_replace('/\r\n/',' ',strip_tags($item['content_short'])))));
    }
    
    //enclosure (если есть, картинка (по id_main_photo))
    if(!empty($item['pic_name'])){
        $xmlEnclosure=$xmlItem->appendChild($xml->createElement('enclosure'));
        $xmlEnclosure->setAttribute('url',htmlspecialchars('https://www.bsn.ru'.'/img/uploads/big/'.$item['pic_subfolder'].'/'.$item['pic_name']));
        $xmlEnclosure->setAttribute('type',"image/jpeg");
    }
    
    //pubDate* (дата новости)
    $xmlItem->appendChild($xml->createElement('pubDate',$item['date'].$timezone));
    
    //yandex:full-text* (полный текст новости для индекса (не публикуется))
    $xmlItem->appendChild($xml->createElement('yandex:full-text',htmlspecialchars(preg_replace('/\r\n/',' ',strip_tags($item['content'])),ENT_QUOTES,'UTF-8')));
} 
$xml->formatOutput = true;
$xml->save($filename);
?>
