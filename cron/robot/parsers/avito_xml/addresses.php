#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SCRIPT_FILENAME']) && preg_match('/.+\.int/i', $_SERVER['SCRIPT_FILENAME']));
define('TEST_MODE', false);

$root = DEBUG_MODE ? realpath("../../../..") : realpath('/home/bsn/sites/bsn.ru/public_html/');

if (defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if (strtolower(substr($os, 0, 3)) == "win") $root = str_replace("\\", '/', $root);
define("ROOT_PATH", $root);
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  (крона
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
setlocale(LC_ALL, 'rus');
/**
 * Обработка новых объектов
 */
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/functions.php');          // функции  (модуля
Session::Init();
Request::Init();
Cookie::Init();
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names " . Config::$values['mysql']['charset']);

$db->querys("set lc_time_names = 'ru_RU'");
require_once('includes/class.host.php');
require_once('includes/class.email.php');
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
require_once('cron/robot/class.xml2array.php');  // конвертация xml в array
require_once("includes/class.sendpulse.php");

//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$link = 'https://autoload.avito.ru/format/New_developments.xml';
$link_response = get_http_response_code($link);

if ($link_response != 200) {
    $error_text = 'Файл недоступен.';
    echo $error_text;
    $success = false;
    //сразу отправляем письма отв. менеджеру и на web@bsn.ru
    $admin_mailer = new EMailer('mail');
    $mail_text = 'Файл агентства #4467 "avito" по ссылке $link недоступен для скачивания';
    $html = iconv('UTF-8', $admin_mailer->CharSet, $mail_text);
    // параметры письма
    $admin_mailer->Subject = iconv('UTF-8', $admin_mailer->CharSet, "Файл агентства #" . $agency['id'] . " недоступен " . $process['id_user'] . " " . date('Y-m-d H:i:s'));
    $admin_mailer->Body = nl2br($html);
    $admin_mailer->AltBody = nl2br($html);
    $admin_mailer->IsHTML(true);
    $admin_mailer->AddAddress('web@bsn.ru');
    $admin_mailer->From = 'bsnxml@bsn.ru';
    $admin_mailer->FromName = iconv('UTF-8', $admin_mailer->CharSet, 'Парсинг ' . (!empty($file_type) ? $file_type : '') . ' XML файла');

    // попытка отправить
    $admin_mailer->Send();
    die();
}
$errors_log = $log = [];
$dir = ROOT_PATH . "/cron/robot/files/avito_xml/";
$filename = 'developments.xml';

if (!is_dir($dir)) mkdir($dir);

if (!file_exists($dir . $filename)) $log['download'][] = downloadFile($dir . $filename, $link, true);

//читаем в строку нужный файл
$contents = file_get_contents($dir . $filename);

// Convert xml string into an object
$new = simplexml_load_string($contents);

// Convert into json
$con = json_encode($new);

// Convert into associative array
$xml_values = json_decode($con, true);

//обработка полученных значений
foreach ($xml_values as $key => $region) {
    //приведение всех ключей в нижний регистр
    foreach ($region as $k => $city) {
        if ($city['@attributes']['name'] == 'Ленинградская область') {
            $db->query(" TRUNCATE table " . $sys_tables['avito_developments']);
            $robot = new Robot();
            foreach ($city['City'] as $ki => $object) {
                $city_title = $object['@attributes'];
                foreach ($object['Object'] as $oki => $item) {
                    if (!empty($item['@attributes'])) {
                        $attr = $item['@attributes'];
                        $data = ['object_id' => $attr['id'] ?? 0, 'name' => $attr['name'] ?? '', 'address' => $city_title['name'] . ', ' . $attr['address'] ?? '', 'developer' => $attr['developer'] ?? ''];
                        $db->insertFromArray($sys_tables['avito_developments'], $data, 'id', true);
                        if (!empty($item['Housing'])) {
                            foreach ($item['Housing'] as $item_key => $h_item) {
                                if (!empty($h_item['@attributes'])) {
                                    $h_attr = $h_item['@attributes'];
                                    $data = ['housing_id' => $h_attr['id'] ?? 0, 'name' => $h_attr['name'] ?? '', 'address' => $city_title['name'] . ', ' . $h_attr['address'] ?? '', 'object_id' => $attr['id'] ?? 0];
                                    $db->insertFromArray($sys_tables['avito_developments'], $data, 'id', true);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
if (file_exists($dir . $filename)) unlink( $dir . $filename );
?>