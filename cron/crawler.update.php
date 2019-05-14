<?php

/*
INSERT INTO service.pages_not_indexed_google (url,title,description,date_in)
SELECT pretty_url, title, description, NOW() FROM common.pages_seo GROUP BY pretty_url
*/

$base_memory_usage = memory_get_usage();
function is_running($proc_string)
{
    $proc_string = str_replace('.', '\.', $proc_string);
    exec("ps ax -o '%a'| grep $proc_string | grep -v grep | grep -v '/bin/sh -c'", $rr);
    return (sizeof($rr) > 1);
} 
if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 
$overall_time_counter = microtime(true);
define("DEBUG_MODE",isset($_SERVER['SERVER_ADDR']) && isset($_SERVER['REMOTE_ADDR']) && $_SERVER['SERVER_ADDR']==$_SERVER['REMOTE_ADDR'] && $_SERVER['SERVER_ADDR']=="127.0.0.1");
$root = DEBUG_MODE ? realpath('..').'/' : '//';
define( "ROOT_PATH", $root );
chdir(ROOT_PATH); 
include_once($root.'includes/functions.php');  ;// функции  из крона
// подключение обработчиков ошибок
include($root.'includes/lib.errorhandler.php'); 
set_error_handler('newErrorHandler');
register_shutdown_function('newFatalCatcher');

// подключение классов ядра
require_once($root.'includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once($root.'includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();  
require_once($root.'includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once($root.'includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once($root.'includes/class.db.mysqli.php');   // mysqli_db (база данных)
$sys_tables = Config::$sys_tables;

