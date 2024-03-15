#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SCRIPT_NAME']) && preg_match('/.+\.int/i', $_SERVER['SCRIPT_NAME']) || !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int/i', $_SERVER['SERVER_NAME']) ? true : false);
define('TEST_MODE', !empty($_SERVER['SCRIPT_FILENAME']) && preg_match('/test\.bsn\.ru/sui', $_SERVER['SCRIPT_FILENAME']) ? true : false);

/** @var TYPE_NAME $root */
$root = TEST_MODE ? realpath('/home/bsn/sites/test.bsn.ru/public_html/trunk/') : (DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/'));
if (defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if (strtolower(substr($os, 0, 3)) == "win") $root = str_replace("\\", '/', $root);
define("ROOT_PATH", $root);
chdir(ROOT_PATH);

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.content.php');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names " . Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");


// вспомогательные таблицы модуля
$sys_tables = Config::Get('sys_tables');
$sm = 'img/uploads/sm';
$med = 'img/uploads/med';
$big = 'img/uploads/big';
if (!class_exists('Photos')) require_once('includes/class.photos.php');;

$tables = ['housing_estates', 'business_centers', 'cottages'];


foreach ($tables as $table) {

    $list = $db->fetchall("
        SELECT 
            " . $sys_tables[$table] . ".*, 
            LEFT(" . $sys_tables[$table . '_photos'] . ".name,2) as subfolder, 
            " . $sys_tables[$table . '_photos'] . ".name as filename
        FROM " . $sys_tables[$table] . " 
        LEFT JOIN " . $sys_tables[$table . '_photos'] . " ON " . $sys_tables[$table . '_photos'] . ".id = " . $sys_tables[$table] . ".id_main_photo");
    foreach ($list as $k => $item) {
            if (file_exists($root . '/' . $sm . '/' . $item['subfolder'] . '/' . $item['filename'])) unlink($root . '/' . $sm . '/' . $item['subfolder'] . '/' . $item['filename']);
            if (file_exists($root . '/' . $med . '/' . $item['subfolder'] . '/' . $item['filename'])) unlink($root . '/' . $med . '/' . $item['subfolder'] . '/' . $item['filename']);
            if (file_exists($root . '/' . $big . '/' . $item['subfolder'] . '/' . $item['filename'])) unlink($root . '/' . $big . '/' . $item['subfolder'] . '/' . $item['filename']);
            $db->querys(" UPDATE " . $sys_tables[$table] . " SET id_main_photo = 0 WHERE id = ?", $item['id']);
            $db->querys(" DELETE FROM " . $sys_tables[$table . '_photos'] . " WHERE id = ?", $item['id_main_photo']);
    }

    /*
    $list = $db->fetchall("
        SELECT  " . $sys_tables[$table . '_photos'] . ".*,
                LEFT(" . $sys_tables[$table . '_photos'] . ".name,2) as subfolder, 
            " . $sys_tables[$table . '_photos'] . ".name as filename
        FROM " . $sys_tables[$table . '_photos']);
    foreach ($list as $k => $item) {
        if (!empty($item['filename'])) {
        if (file_exists($root . '/' . $sm . '/' . $item['subfolder'] . '/' . $item['filename'])) unlink($root . '/' . $sm . '/' . $item['subfolder'] . '/' . $item['filename']);
        if (file_exists($root . '/' . $med . '/' . $item['subfolder'] . '/' . $item['filename'])) unlink($root . '/' . $med . '/' . $item['subfolder'] . '/' . $item['filename']);
        if (file_exists($root . '/' . $big . '/' . $item['subfolder'] . '/' . $item['filename'])) unlink($root . '/' . $big . '/' . $item['subfolder'] . '/' . $item['filename']);
        $db->querys(" DELETE FROM " . $sys_tables[$table . '_photos'] . " WHERE id = ?", $item['id']);
        }
    }
    */

}
