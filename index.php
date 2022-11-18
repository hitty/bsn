<?php
$overall_memory_usage = memory_get_peak_usage();
$overall_time_counter = microtime( true );
//DEBUG - local
define( "DEBUG_MODE", (isset($_SERVER['SERVER_ADDR']) && isset($_SERVER['REMOTE_ADDR']) && $_SERVER['SERVER_ADDR']==$_SERVER['REMOTE_ADDR'] && $_SERVER['SERVER_ADDR']=="127.0.0.1") || (!empty($_SERVER['HTTP_HOST']) && substr($_SERVER['HTTP_HOST'], -4) == '.int'));
// TEST - test.bsn.ru
$host = explode(".", $_SERVER['HTTP_HOST']); 
$is_test = array_shift($host);
define("TEST_MODE", !empty($is_test) && $is_test == 'test');
define("NEW_MODE", !empty($is_test) && $is_test == 'new');
//рутовый путь
define( "ROOT_PATH", str_replace("\\", '/', realpath(".")));
if(DEBUG_MODE){
    // абсолютно все ошибки логируются и показываются в общем порядке, время исполнения скрипта увеличено
    error_reporting(E_ALL);
    set_time_limit(45); 
} else {
    // все ошибки только логируются, на экран не выводятся, время выполнения скрипта стандартное
    error_reporting(0);
    set_time_limit(10);
    // подключение обработчиков ошибок
    include('includes/lib.errorhandler.php');
    set_error_handler('newErrorHandler');
    register_shutdown_function('newFatalCatcher');
}
if(TEST_MODE){
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else $ip = $_SERVER['REMOTE_ADDR'];
    if(empty($ip)) die("Hey buddy it's only for special ones!");
              
}
// абсолютно все ошибки логируются и показываются в общем порядке, время исполнения скрипта увеличено
error_reporting(E_ALL);
set_time_limit(45); 

// подключение классов ядра
if( !class_exists( 'Config' ) ) {
    require_once('includes/class.config.php');       // Config (конфигурация сайта)
    Config::Init();
}
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
Session::Init();
Request::Init();
Cookie::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.memcache.php');     // MCache (memcached, кеширование в памяти)
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.auth.php');         // Auth (авторизация)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.filedata.php');     // FileData (работа с файловым хранилищем рабочих данных)
require_once('includes/class.page.php');         // Page
require_once('includes/functions.php');          // набор функций
require_once("includes/class.notifications.php");//уведомления
require_once('includes/class.banners.php');
require_once('includes/class.crawler_catcher.php'); // ловец ботов
Banners::Init();
// Инициализация рабочих классов
$memcache = new MCache(Config::$values['memcache']['host'], Config::$values['memcache']['port']);
$db = new mysqli_db(Config::$values[ DEBUG_MODE ? 'mysql' : 'mysql' ]['host'], Config::$values[ DEBUG_MODE ? 'mysql' : 'mysql' ]['user'], Config::$values[ DEBUG_MODE ? 'mysql' : 'mysql' ]['pass']);
$db->querys("set names ".Config::$values[ DEBUG_MODE ? 'mysql' : 'mysql' ]['charset']);
$db->querys("SET lc_time_names = '".Config::$values[ DEBUG_MODE ? 'mysql' : 'mysql' ]['lc_time_names']."';");
 
FileCache::Init('filecache');
$auth = new Auth();
require_once('includes/class.favorites.php');    // Избранное
require_once('includes/class.estate.subscriptions.php');
// проверка авторизации
$_authorized = $auth->checkAuth();
Response::SetBoolean('authorized', $auth->authorized);
//if( NEW_MODE ) Response::SetBoolean( 'noindex', true );

// старт запрошенной страницы
if($_SERVER['HTTP_HOST'] == "mipimspb2017.ru"){
    $requested_uri = Host::getRequestedUri();
    $requested_page = new Page("http://invest.bsn.ru/".(!empty($requested_uri) ? $requested_uri : "invest")."/");
}
else if(strstr(Host::$host,'navigator.bsn') != ''){
    $requested_uri = Host::getRequestedUri();
    if( !($requested_uri == '' || strstr($requested_uri, 'navigator') != '') ) Host::Redirect( 'https://www.bsn.ru/');
    $requested_page = new Page("http://navigator.bsn.ru/".(!empty($requested_uri) ? $requested_uri : "navigator")."/");
}
else $requested_page = new Page(Host::getRequestedUri());     

Favorites::Init();
// определение режима ajax-запроса
$ajax_mode = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($internal_mode);
//проверка пользователя|url на "черный" список
Host::checkUser();

if(empty($requested_page->is_admin_page) && empty($ajax_mode)){
    //подключение стилей и js-скриптов
    $GLOBALS['js_set'][] = '/js/jquery.min.js';
    $GLOBALS['js_set'][] = '/js/form.validate.js';
    
    $GLOBALS['js_set'][] = '/js/lazyload.min.js';
    $GLOBALS['js_set'][] = '/js/main.js';
    $GLOBALS['js_set'][] = '/js/interface.js';
    $GLOBALS['js_set'][] = '/js/history.min.js';
    //$GLOBALS['js_set'][] = '/js/fixed_columns.js';
    $GLOBALS['js_set'][] = '/js/slider.popup.js';
    
    $GLOBALS['js_set'][]  = '/modules/tgb/list.popup.js';
    $GLOBALS['css_set'][] = '/modules/tgb/list.popup.css';
    
    $GLOBALS['js_set'][]  = '/js/simplebar/simplebar.js';
    $GLOBALS['css_set'][] = '/js/simplebar/simplebar.css';
    
    $GLOBALS['js_set'][] =  '/modules/notifications/script.js';
    //авторизованным пользователям проверка на наличие новых сообщений в ЛК
    if($auth->authorized === true) $GLOBALS['js_set'][] = '/modules/messages/auth_check.js';
    
    $GLOBALS['css_set'][] = '/css/reset.css';
    $GLOBALS['css_set'][] = '/css/final_corrections.css';
    $GLOBALS['css_set'][] = '/css/common.css';
    $GLOBALS['css_set'][] = '/css/controls.css';
    $GLOBALS['css_set'][] = '/css/topmenu.css';
    $GLOBALS['css_set'][] = '/css/central.css';
    $GLOBALS['css_set'][] = '/css/content.css';
    
    $GLOBALS['js_set'][] =  '/js/gallery/script.js';
    $GLOBALS['css_set'][] = '/js/gallery/style.css';
    
    $GLOBALS['js_set'][] = '/js/popup.window/script.js';
    $GLOBALS['css_set'][] = '/js/popup.window/styles.css';
}
Response::SetBoolean( 'debug', DEBUG_MODE );
Response::SetBoolean( 'test_mode', TEST_MODE );
$content = $requested_page->Render();

if(substr($content,0,5)=='<?xml'){
    header('Content-Type: application/xml; charset='.Config::$values['site']['charset']);
} else {
    header('Content-Type: text/html; Charset='.Config::$values['site']['charset']);
}
echo $content;
/*

$querylog = Convert::ArrayKeySort($db->querylog, 'time', true);
$overall_time_counter = round(microtime(true) - $overall_time_counter, 4);
$overall_memory_usage = memory_get_peak_usage() - $overall_memory_usage;
if($overall_time_counter>0.1 || (defined("DEBUG_MODE") && DEBUG_MODE)) file_put_contents('query.log', print_r($querylog,true) );
if(!empty($_GET['showtime']) || (defined("DEBUG_MODE") && DEBUG_MODE)){
    echo "\n<!--";
    printf("\nExecution time: %01.4f", $overall_time_counter);
    printf("\nAlocated memory: %d", $overall_memory_usage);
    echo "\n-->";
}
*/
?>
