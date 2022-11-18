<?php
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


//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/cottages/error.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');
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
include('includes/class.template.php');     // Photos (работа с графикой)
require_once('includes/dompdf/dompdf_config.inc.php');
require_once('includes/class.business_centers.php');

//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$ids = array(1,4, 1);

$geo = curlThis("http://static.maps.2gis.com/1.0?center=59.975784,30.323812&zoom=15&size=206,152");
$geo = json_decode($geo);


$business_centers = new BusinessCenters();
$dompdf = new DOMPDF();// Создаем обьект    

$eml_tpl = new Template('offices.block.pdf.styles.html');
$html = $eml_tpl->Processing();
$count = 0;
foreach($ids as $id){
    $count++;
    $item = $business_centers->getOfficesList(1,$sys_tables['business_centers_offices'].".id = ".$id);
    $item = $item[0];    
    $item_photos = Photos::getList('business_centers_offices', $id);
    if(empty($item_photos) && !empty($item['id_object'])) $item_photos = Photos::getList('commercial',$item['id_object']);
    $bc_item = $business_centers->getItem(false, $item['id_business_center']);
    Response::SetArray('bc_item', $bc_item);

    $agency = $db->fetch("
                           SELECT          
                                  ".$sys_tables['agencies'].".*,
                                  ".$sys_tables['users'].".name,
                                  ".$sys_tables['users'].".lastname,
                                  ".$sys_tables['users'].".email as user_email,
                                  LEFT(".$sys_tables['agencies_photos'].".name,2) as `subfolder`,
                                  ".$sys_tables['agencies_photos'].".name as photo_name
                           FROM ".$sys_tables['agencies']."
                           RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                           LEFT JOIN ".$sys_tables['agencies_photos']." ON ".$sys_tables['agencies_photos'].".id= ".$sys_tables['agencies'].".id_main_photo
                           WHERE ".$sys_tables['users'].".id = ?", $item['id_user']

    );
    Response::SetArray('item', $item);
    Response::SetArray('agency', $agency);
    Response::SetArray('item_photos',array_splice($item_photos, 0, 3));
    $bc_photos = Photos::getList('business_centers', $item['id_business_center']);
    Response::SetArray('bc_photos',array_splice($bc_photos, 0, !empty($item['lng']) ? 2 : 1));
    Response::SetInteger('count', $count);
    Response::SetInteger('count_ids', count($ids));
    $eml_tpl = new Template('offices.block.pdf.html');
    $html .= $eml_tpl->Processing();
}
$dompdf->load_html($html.'</body></html>'); // Загружаем в него наш html код
$dompdf->render(); // Создаем из HTML PDF
$dompdf->stream('mypdf.pdf'); // Выводим результат (скачивание)


?>
