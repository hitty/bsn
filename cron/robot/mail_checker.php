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
include_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

echo '$_SERVER[PHP_SELF]:'.$_SERVER['PHP_SELF']."\n";
if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/class.email.php');
require_once('includes/mailboxer/mailboxer.class');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

// log file
$mail_text = array();

    $mail_text[] =  "<html>\n<body bgcolor=#ffffff>\n";
    $mail_text[] =  "<br>Д123123123анное письмо сформировала программа обрабатывающая<br>почтовые сообщения приходящие на e-mail <b>bsnrobot@bsn.ru</b>.<br>";

// closing imap connection
unset($mailboxer);
$mail_text[] =  "\n</body>\n</html>\n";
if(!empty($mail_text)){
    $mailer = new EMailer('mail');
    $html = iconv('UTF-8', $mailer->CharSet, implode(" ",$mail_text));
    // параметры письма
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Проверка почты роботом BSN. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
    $mailer->AddAddress('web@bsn.ru');
    $mailer->From = 'wmailer@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Проверка почты роботом BSN');
    // попытка отправить
    $mailer->Send();        
    echo $html;
}
?>
