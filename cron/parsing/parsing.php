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

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/comagic/spam_error.log';
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
require_once('includes/simple_html_dom.php');    //класс для парсинга html
require_once('includes/class.robot.php');        // класс с функциями робота, нужен для получения адреса
require_once('includes/functions.php');          // функции  из крона
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;     // Photos (работа с графикой)
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

require_once('includes/class.parsing.robot.php');

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//use com\soloproyectos\core\xml\phpQuery;
require_once('includes/phpquery/phpQuery.php');

//$text = file_get_contents('html.txt');
//$document = phpQuery::newDocument($text);
//echo json_encode($document)."!@#";
//die();


$url = "http://arenda.mirkvartir.ru/";
//$url = "http://arenda-v-pitere.ru";
//$url = "http://spb.posrednikovzdes.net";
//$url = "http://spb.snyat-kvartiru-bez-posrednikov.ru";


echo $result;
/*
$command = "@div.b-offer-item>@div.item#attribute=data-aliasid";#id
$command = "@h1.offer-title>@small";#type
$command = ".options.m-top>@li:first>@p#notags";#cost
$result = ParsingFunctions::parseCommand($command);
*/
$parsing = new Parsing($url);

/*
$phone_data = array("url" => "http://spb.snyat-kvartiru-bez-posrednikov.ru/snyat-komnatu-bez-posrednikov-v-sankt-peterburge/2297812-metro-primorskaya-veselynaya-d-5",
                    "text" => '<input value="b565ed528ef14dcd2f17c4d1d55863c6a65a4a5b" name="YII_CSRF_TOKEN" type="hidden">');
$parsing->getPhone($phone_data,2297812);
*/
//ParsingDataFromStub::type_parse("Аренда однокомнатной квартиры,");
//ParsingDataFromStub::details_parse(1,"Комнаты      1-комнатная#Площадь      39 м#Планировка      балкон#Этаж      7 из 14#Адрес        Санкт-Петербург, пр-кт Славы, 34#Лифт      пассажирский, грузовой#Состояние      кухонный гарнитур, меблирована#Бытовая техника      холодильник, стиральная м");
//$fields = ParsingDataFromStub::address_parse("Санкт-Петербург, пр-кт Славы, 34");

$page_num = 1;

$parsing->parseSite(1,1);
//$parsing->parseStubsToBase($page_num,1);

//$parsing->moveStubsToEstate();
?>