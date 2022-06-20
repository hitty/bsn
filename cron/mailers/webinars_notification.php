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
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//определение периода выборки новостей     
$list = $db->fetchall("SELECT *,
                       DATE_FORMAT (`datetime`, '%d.%m.%y') as `date_w`,
                       DATE_FORMAT (`datetime`, '%k:%i') as `time_w`
                       FROM ".$sys_tables['webinars']." 
                       WHERE status = 1 AND TIMESTAMPDIFF( HOUR, NOW(), `datetime`) <= 5 AND notification_status = 1");
Response::SetString('mailer_title', 'Вебинары');                       
foreach($list as $k=>$item){
    $db->query("UPDATE ".$sys_tables['webinars']." SET notification_status = 2 WHERE id = ?", $item['id']);
    $users = $db->fetchall("SELECT ".$sys_tables['webinars_users'].".*,
                                       TRIM(CONCAT(".$sys_tables['users'].".name, ' ', ".$sys_tables['users'].".lastname)) as user_name,
                                       TRIM(".$sys_tables['users'].".name) as name,
                                       ".$sys_tables['users'].".email,
                                       ".$sys_tables['users'].".passwd
                                FROM ".$sys_tables['webinars_users']." 
                                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['webinars_users'].".id_user
                                WHERE ".$sys_tables['webinars_users'].".id_parent = ?", false, $item['id']);
        // инициализация шаблонизатора
        $eml_tpl = new Template('webinars_notification.html', 'cron/mailers/');
        foreach($users as $k=>$user){
            if(!Validate::isEmail($user['email'])){
                preg_match('!([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,4}!i', (string) $user['email'], $matches);
                if(!empty($matches[0])) $user['email'] = $matches[0];
                else $user['email'] = null;
            }
            if($user['email']){         
                $item['link'] = 'https://go.myownconference.ru/ru/bsnru/'.urlencode($user['name']).'/'.md5($user['passwd']).'/';
                Response::SetArray('user',$user);
                Response::SetArray('item',$item);

                if( !class_exists( 'Sendpulse' ) ) require_once("includes/class.sendpulse.php");
                $sender_title = 'BSN.ru';         
                $subject = 'Напоминание о вебинаре «'.$item['title'].'»';         
                
                $html = $eml_tpl->Processing();
                $emails = array(
                    array(
                        'name' => '',
                        'email'=> $user['email']
                    )
                );
                //отправка письма
                $sendpulse = new Sendpulse( );
                $result = $sendpulse->sendMail( $subject, $html, false, false, $sender_title, 'no-reply@bsn.ru', $emails );
            }

            
        }    
}
?>
