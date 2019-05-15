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

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 
//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/mailers/spam_error.log';
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
require_once('includes/class.email.php');
require_once('includes/class.memcache.php');     // MCache (memcached, кеширование в памяти)

$memcache = new MCache(Config::$values['memcache']['host'], Config::$values['memcache']['port']);

print_r($_SERVER['argv']);
$debug = DEBUG_MODE || !empty($_SERVER['argv'][1]) ? true : false;

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$argc = ( !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false ) || DEBUG_MODE;


//Тестовый список получателей email рассылки
if( DEBUG_MODE )
    $email_list = array(
        0 => array( 'id' => 3, 'email' => 'kya82@mail.ru')
    );
else if(!empty($debug)) 
    $email_list = array(
        0 => array( 'id' => 3, 'email' => 'kya82@mail.ru'),
        1 => array( 'id' => 4, 'email' => 'ep5il0n.alphabet@gmail.com'),
        2 => array( 'id' => 4, 'email' => 'web@bsn.ru'),
        3 => array( 'id' => 5, 'email' => 'pm@bsn.ru')
    );
else //Рабочий список получателей email рассылки
    $email_list = $db->fetchall("SELECT DISTINCT s.email, s.id FROM ( 
                                    (SELECT email, id FROM ".$sys_tables['subscribed_users']." WHERE published=1) 
                                    UNION 
                                    (SELECT email, id FROM ".$sys_tables['users']." WHERE subscribe_news = 1) 
                                ) as s GROUP BY s.email");     


$mailer = new EMailer('mail');           
if(!empty($email_list) ){
    Response::SetString('date', date("d.m.Y"));
    // инициализация шаблонизатора
    foreach($email_list as $email){
        $eml_tpl = new Template('ny2018.email.html', 'cron/mailers/');
        if(!Validate::isEmail($email['email'])){
            preg_match('!([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,4}!i', (string) $email['email'], $matches);
            if(!empty($matches[0])) $email['email'] = $matches[0];
            else $email['email'] = null;
        }
        if($email['email']){
            Response::SetString('user_email',$email['email']);
            Response::SetString('user_id',$email['id']);
            Response::SetString('user_code',sha1(md5($email['id'].$email['email']."special!_adding")));
            $mailer = new EMailer('mail');
            echo $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet, $html);
            // параметры письма
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet.'//IGNORE', 'С Новым годом! Команда БСН.');
            $mailer->IsHTML(true);
            $mailer->AddAddress($email['email']);
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
            // попытка отправить
            $mailer->Send();
        }
    }
} 
?>
