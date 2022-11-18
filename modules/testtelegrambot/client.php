#!/usr/bin/php;
<?php
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG ? realpath("../..") : realpath('/home/bsn/sites/test.bsn.ru/public_html/trunk/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('includes/robot_functions.php');    // функции  из крона
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
//if (is_running($_SERVER['PHP_SELF'])) die('Already running');
//запись всех ошибок в лог
ini_set('log_errors', 'On');
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.email.php');        // для отправки писем
require_once('includes/functions.php');    // функции  из крона
require_once('includes/getid3/getid3.php');
if(empty(Host::$requested_path)) Host::Init();

$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
//$db = new mysqli_db(Config::$values['mysql_remote']['host'], Config::$values['mysql_remote']['user'], Config::$values['mysql_remote']['pass']);
//$db->querys("set names ".Config::$values['mysql_remote']['charset']);
$db->querys("SET lc_time_names = '".Config::$values['mysql']['lc_time_names']."';");

require_once("includes/class.telegram.test.php");

//to set webhook: https://api.telegram.org/bot336199007:AAFBzJr4TIXJSn4ZvHwz13xQGAZD9ZkeLA8/setWebhook?url=https://test.bsn.ru/telegramBot/AAFBzJr4TIXJSn4ZvHwz13xQGAZD9ZkeLA8/
//channel invite https://telegram.me/joinchat/DzEfMkGPSqZgaT5TTH8JNQ
//our channel ID: -1001099909798
//bot to channel: https://api.telegram.org/bot336199007:AAFBzJr4TIXJSn4ZvHwz13xQGAZD9ZkeLA8/sendMessage?chat_id=@joinchannel_BSNRU&text=123
//{"ok":true,"result":{"message_id":2,"chat":{"id":-1001099909798,"title":"_BSNRU_","username":"joinchannel_BSNRU","type":"channel"},"date":1481810736,"text":"123"}}

//на всякий случай заново ставим webhook
//Telegram::apiRequest("setWebhook",array("url" => "https://test.bsn.ru/testtelegramBot/"));

//lines for working
file_put_contents("modules/testtelegramBot/errors.log","ACCEPT".$_SERVER['REQUEST_METHOD']."; ".$_SERVER['REQUEST_URI']."\r\n",FILE_APPEND);
try{
    //$is_ready = TelegramController::checkWebHook();
    //$result = Telegram::acceptRequest();
    Telegram::searchWithParams(254877490,true);
    $module_template = "templates/clearcontent.html";
}
catch(TelegramException $exception){
    $line_number = $exception->getLine();
    TelegramException::exceptionHandler($exception,$line_number);
    //если что-то пошло не так(например неверный запрос), отдаем 404
    file_put_contents("modules/testtelegramBot/errors.log","404!!!".$_SERVER['REQUEST_METHOD']."; ".$_SERVER['REQUEST_URI']."\r\n",FILE_APPEND);
    $this_page->http_code=404;
    $module_template = "templates/404.html";
}

//for tests
// /search
//$update = json_decode('{"update_id":86616074,"message":{"message_id":807,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482335922,"text":"\/search","entities":[{"type":"bot_command","offset":0,"length":7}]}}',true);
// Снять
//$update = json_decode('{"update_id":86616075,"message":{"message_id":809,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482335924,"text":"\u0421\u043d\u044f\u0442\u044c"}}',true);
// Закончить ввод
//$update = json_decode('{"update_id":86616136,"message":{"message_id":930,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482393692,"text":"\u0417\u0430\u043a\u043e\u043d\u0447\u0438\u0442\u044c \u0432\u0432\u043e\u0434"}}',true);
// Искать
//$update = json_decode('{"update_id":86616152,"message":{"message_id":960,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482395876,"text":"\u0418\u0441\u043a\u0430\u0442\u044c"}}',true);
// Метро
//$update = json_decode('{"update_id":86616406,"message":{"message_id":1465,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482834570,"text":"\u041c\u0435\u0442\u0440\u043e"}}',true);
// Еще
//$update = json_decode('{"update_id":86616279,"message":{"message_id":1219,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482481675,"text":"\u0415\u0449\u0435"}}',true);
// Международная
//$update = json_decode('{"update_id":86616337,"message":{"message_id":1326,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482485738,"text":"\u043c\u0435\u0436\u0434\u0443\u043d\u0430\u0440\u043e\u0434\u043d\u0430\u044f"}}',true);
// Фрунзенский
//$update = json_decode('{"update_id":86616372,"message":{"message_id":1397,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482503011,"text":"\u0424\u0440\u0443\u043d\u0437\u0435\u043d\u0441\u043a\u0438\u0439"}}',true);
// /1/2/3Калининский
//$update = json_decode('{"update_id":86616417,"message":{"message_id":1487,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482838921,"text":"\/1\/2\/3\u041a\u0430\u043b\u0438\u043d\u0438\u043d\u0441\u043a\u0438\u0439"}}',true);
// /1/2/3
//$update = json_decode('{"update_id":86616417,"message":{"message_id":1487,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482838921,"text":"\/1\/2\/3"}}',true);
// Квартира/Комната
//$update = json_decode('{"update_id":86616473,"message":{"message_id":1599,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482847974,"text":"\u041a\u0432\u0430\u0440\u0442\u0438\u0440\u0430\/\u041a\u043e\u043c\u043d\u0430\u0442\u0430"}}',true);
// /0
//$update = json_decode('{"update_id":86616481,"message":{"message_id":1615,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482848295,"text":"\/0","entities":[{"type":"bot_command","offset":0,"length":2}]}}',true);
// Снять посуточно
//$update = json_decode('{"update_id":86616829,"message":{"message_id":2298,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482935037,"text":"\u0421\u043d\u044f\u0442\u044c \u043f\u043e\u0441\u0443\u0442\u043e\u0447\u043d\u043e"}}',true);
// Цена
//$update = json_decode('{"update_id":86616858,"message":{"message_id":2357,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482937136,"text":"\u0426\u0435\u043d\u0430"}}',true);
// до 19000р
//$update = json_decode('{"update_id":86616868,"message":{"message_id":2378,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482938264,"text":"\u0434\u043e 19000\u0440"}}',true);
// /help
//$update = json_decode('{"update_id":86616926,"message":{"message_id":2497,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1482940299,"text":"\/help","entities":[{"type":"bot_command","offset":0,"length":5}]}}',true);
// Очистить
//$update = json_decode('{"update_id":86616992,"message":{"message_id":2628,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1483011131,"text":"\u041e\u0447\u0438\u0441\u0442\u0438\u0442\u044c"}}',true);
// /45r
//$update = json_decode('{"update_id":86617115,"message":{"message_id":2878,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1483093414,"text":"\/45r","entities":[{"type":"bot_command","offset":0,"length":4}]}}',true);
// 1
//$update = json_decode('{"update_id":86617271,"message":{"message_id":3132,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1484047388,"text":"1"}}',true);
// 2
//$update = json_decode('{"update_id":86617271,"message":{"message_id":3132,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1484047388,"text":"2"}}',true);
//комната
//$update = json_decode('{"update_id":86617763,"message":{"message_id":3879,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1485250970,"text":"\u043a\u043e\u043c\u043d\u0430\u0442\u0430"}}',true);
//last {"update_id":566315752,"message":{"message_id":142,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1490600146,"text":"\u0421\u043d\u044f\u0442\u044c \u043f\u043e\u0441\u0443\u0442\u043e\u0447\u043d\u043e"}}

//lines for testing

//$update = json_decode('{"update_id":566315752,"message":{"message_id":142,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1490600146,"text":"\u0421\u043d\u044f\u0442\u044c \u043f\u043e\u0441\u0443\u0442\u043e\u0447\u043d\u043e"}}',true);
//$result = Telegram::acceptRequest();

?>