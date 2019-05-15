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
$db->query("set names ".Config::$values['mysql']['charset']);
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
include('includes/class.estate.php');     // Photos (работа с графикой)
//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//файл с результатом
$filename = ROOT_PATH . '/fb.pixel.feed.xml';

$xml = new DOMDocument('1.0','windows-1251');

$source_title = 'Facebook Pixel Feed';//'название RSS-потока 
$description = 'Facebook Pixel Feed - БСН.ру';//описание источника (1 предложение)
$link = 'https://www.bsn.ru'.'/news/';//ссылка на источник
$image_url = 'https://www.bsn.ru'.'/img/layout/logo.png';//url логотипа*
$image_description = '';

//rss*
$xmlRss = $xml->appendChild($xml->createElement('rss'));
$xmlRss->setAttribute( 'version', '2.0' );

//channel*
$xmlChannel = $xmlRss->appendChild($xml->createElement('channel'));

$list = array();

$clauses = array(
    'published' => array('value'=> 1),
    'id_main_photo' => array('from'=> 1)
);
;
//КоЛИЧЕСТВО ОБЪЕКТОВ ПО КАЖДОМУ ТИПУ НЕДВИЖИМОСТИ
$total = !empty( $_GET['limit'] ) ? $_GET['limit'] : 200000;
echo 'Лимит объектов:' . $total . ';';
$estate_types = array( 'build' );
foreach( $estate_types as $estate_type ){
    switch( $estate_type ){
        case 'live': $estate = new EstateListlive(); break;
        case 'build': $estate = new EstateListBuild(); break;
        case 'commercial': $estate = new EstateListCommercial(); break;
        case 'country': $estate = new EstateListCountry(); break;

    }
    $list = $estate->Search($clauses, $total, 0, $sys_tables[$estate_type].'.date_in DESC');
    //часовой пояс для даты
    $timezone=preg_replace('/[^0-9+]/','',date('P',time()));

    foreach($list as $k=>$item){
        
        $photos = Photos::getList( $estate_type, $item['id'] );
        if(!empty( $photos ) ) {

            //item* - тег новости
            $xmlItem = $xmlChannel->appendChild($xml->createElement('item'));
            
            //title* (title новости)
            $estate_item = new EstateItemlive( $item['id'] );
            switch( $estate_type ){
                case 'live': $estate_item = new EstateItemLive( $item['id'] ); break;
                case 'build': $estate_item = new EstateItemBuild( $item['id'] ); break;
                case 'commercial': $estate_item = new EstateItemCommercial( $item['id'] ); break;
                case 'country': $estate_item = new EstateItemCountry( $item['id'] ); break;

            }
            
            $title = $estate_item->getTitles();
            $xmlItem->appendChild( $xml->createElement( 'title', htmlspecialchars( strip_tags( $title['title'] ), ENT_QUOTES, 'UTF-8' ) ) );
            $xmlItem->appendChild( $xml->createElement( 'id', $item['id'] ) );
            $xmlItem->appendChild( $xml->createElement( 'availability', 'in stock' ) );
            $xmlItem->appendChild( $xml->createElement( 'condition', 'new' ) );
            $description = !empty( $item['notes'] ) && strlen( $item['notes'] ) > 100 ? $item['notes'] : $title['description'];
            $xmlItem->appendChild( $xml->createElement( 'description', htmlspecialchars( strip_tags( $description ), ENT_QUOTES, 'UTF-8' ) ) );
            $xmlItem->appendChild( $xml->createElement( 'link', 'https://www.bsn.ru/' . $estate_type . '/' . ( $item['rent'] == 1 ? 'rent' : 'sell' ). '/' . $item['id'] . '/' ) );
            $xmlItem->appendChild( $xml->createElement( 'price', $item['cost'] . 'RUB' ) );
            $xmlItem->appendChild( $xml->createElement( 'mpn', $estate_type . $item['id'] ) );
            $xmlItem->appendChild( $xml->createElement( 'image_link', 'https://st.bsn.ru/img/uploads/med/' . $photos['0']['subfolder'] . '/' . $photos['0']['name'] ) );
        }
       
    } 
    $xml->formatOutput = true;
    $xml->save($filename);
}
exec("chmod 777 ".$filename."");
?>
