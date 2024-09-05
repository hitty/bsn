<?php
error_reporting(E_ALL);
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', (!empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME'])) || preg_match('/bsn\.int/i', $_SERVER['SCRIPT_NAME']) ? true : false);
echo $root = DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/trunk/');
if (defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if (strtolower(substr($os, 0, 3)) == "win") $root = str_replace("\\", '/', $root);
define("ROOT_PATH", $root);
chdir(ROOT_PATH);

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
//запись всех ошибок в лог
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/functions.php');    // функции  из крона
require_once("modules/geoip/SxGeo.php");


// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names " . Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$SxGeo = new SxGeo(ROOT_PATH . '/modules/geoip/SxGeoCity.dat'); // Режим по умолчанию, файл бд SxGeo.dat
$votings = $db->fetchall(" SELECT * FROM " . $sys_tables['konkurs_votings'] . " WHERE city = '' OR country = '' ORDER BY id DESC ");
foreach ($votings as $v => $voting) {
    $geo = $SxGeo->get($voting['ip']);    // выполняет getCountry либо getCity в зависимости от типа базы

    if (filter_var($voting['ip'], FILTER_VALIDATE_IP)) {
        $ip_black_list = dnsbllookup($voting['ip']);
    } else {
        $ip_black_list = "Please enter a valid IP";
    }

    $data = [
        'id' => $voting['id'],
        'city' => !empty($geo['city']['name_en']) ? $geo['city']['name_en'] : '',
        'country' => !empty($geo['country']['iso']) ? $geo['country']['iso'] : '',
        'ip_black_list' => $ip_black_list,
    ];

    $db->updateFromArray($sys_tables['konkurs_votings'], $data, 'id');
}

function dnsbllookup($ip)
{
    $dnsbl_lookup =
        [
            'dnsbl-1.uceprotect.net', 'all.s5h.net', 'wormrbl.imp.ch',
            'dnsbl-2.uceprotect.net', 'blacklist.woody.ch',
            'dnsbl-3.uceprotect.net', 'combined.abuse.ch', 'dnsbl.spfbl.net',
            'dnsbl.dronebl.org', 'http.dnsbl.sorbs.net'
        ];

    $dns_count = count($dnsbl_lookup);

    $list = [];

    if ($ip)
    {
        $reverse_ip = implode(".", array_reverse(explode(".", $ip)));

        foreach ($dnsbl_lookup as $host)
            if (checkdnsrr("$reverse_ip.$host.", "A"))
                $list[] = "$ip <font color=\"red\"><strong>is Listed in </strong></font> $host<br />\n";
    }

    $list_count = count($list);

    if (0 === $list_count)
        echo "No record was not found in the IP blacklist database for:  $ip<br>\n";
    else
    {
        $list_str = implode(" ", $list);
        echo "$list_str<br>\nBlacklisted: $list_count<br>\n";
    }
    echo "$list_count|$dns_count\n";
}