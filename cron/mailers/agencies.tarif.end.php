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
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//определение периода выборки новостей
$list = $db->fetchall("SELECT 
                            ".$sys_tables['agencies'].".*,
                            IF(YEAR(".$sys_tables['agencies'].".`tarif_end`) > 0,DATE_FORMAT(".$sys_tables['agencies'].".`tarif_end`,'%e.%m.%y'), '0') as tarif_end,
                            ".$sys_tables['users'].".email as user_email,
                            ".$sys_tables['users'].".name as user_name,
                            ".$sys_tables['users'].".lastname as user_lastname,
                            ".$sys_tables['tarifs_agencies'].".title as tarif_title,
                            ".$sys_tables['managers'].".name as manager_name,
                            ".$sys_tables['managers'].".email as manager_email
                       FROM ".$sys_tables['agencies']." 
                       RIGHT JOIN ".$sys_tables['tarifs_agencies']." ON ".$sys_tables['agencies'].".id_tarif = ".$sys_tables['tarifs_agencies'].".id
                       LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                       RIGHT JOIN
                            ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                       WHERE 
                            ".$sys_tables['agencies'].".tarif_end > CURDATE() AND 
                            ".$sys_tables['agencies'].".tarif_end <= CURDATE() + INTERVAL 3 DAY AND
                            ".$sys_tables['agencies'].".id_tarif > 0 AND
                            ".$sys_tables['users'].".agency_admin = 1
                       GROUP BY  ".$sys_tables['agencies'].".id
");
foreach($list as $k=>$item){
    if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
    $sender_title = 'BSN.ru';         
    $subject = 'Уведомление о приближении окончания действия тарифа «'.$item['tarif_title'].'»';         
    $eml_tpl = new Template('agencies.tarif.end.html', 'cron/mailers/');
    $html = $eml_tpl->Processing();
    $emails = array(
        array(
            'name' => '',
            'email'=> 'web@bsn.ru'
        )
    );
    if( Validate::isEmail( $item['email_service'] ) ) $emails[] = array( 'name' => '', 'email'=> $item['email_service'] );
    if( Validate::isEmail( $item['user_email'] ) ) $emails[] = array( 'name' => '', 'email'=> $item['user_email'] );
    //отправка письма
    $sendpulse = new Sendpulse( );
    $result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );
}
?>
