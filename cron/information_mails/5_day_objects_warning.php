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
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

////////////////////////////////////////////////////////////////////////////////////////////////
// Отправка писем о снятии объектов через 5 дней
////////////////////////////////////////////////////////////////////////////////////////////////   
$list = $db->fetchall("
        SELECT aliases.id, aliases.id_user, aliases.estate_table_title,  aliases.db_name, aliases.agency_title, aliases.email  FROM
        (
            (
                SELECT estate_table.id, estate_table.id_user, 'Жилая' as estate_table_title,  'live' as db_name, ".$sys_tables['agencies'].".title AS agency_title, ".$sys_tables['managers'].".email
                FROM ".$sys_tables['live']." estate_table
                LEFT JOIN ".$sys_tables['users']."  ON ".$sys_tables['users'].".id = estate_table.id_user
                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                WHERE estate_table.published = 1 AND estate_table.date_change = CURDATE() - INTERVAL 25 DAY 
            ) UNION (
                SELECT estate_table.id, estate_table.id_user, 'Строящаяся' as estate_table_title,  'build' as db_name, ".$sys_tables['agencies'].".title AS agency_title, ".$sys_tables['managers'].".email
                FROM ".$sys_tables['build']." estate_table
                LEFT JOIN ".$sys_tables['users']."  ON ".$sys_tables['users'].".id = estate_table.id_user
                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                WHERE estate_table.published = 1 AND estate_table.date_change = CURDATE() - INTERVAL 55 DAY 
            ) UNION (
                SELECT estate_table.id, estate_table.id_user,'Коммерческая' as estate_table_title,  'commercial' as db_name, ".$sys_tables['agencies'].".title AS agency_title, ".$sys_tables['managers'].".email
                FROM ".$sys_tables['commercial']." estate_table
                LEFT JOIN ".$sys_tables['users']."  ON ".$sys_tables['users'].".id = estate_table.id_user
                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                WHERE estate_table.published = 1 AND estate_table.date_change = CURDATE() - INTERVAL 25 DAY 
            ) UNION (
                SELECT estate_table.id, estate_table.id_user,'Загородная' as estate_table_title, 'country' as db_name,  ".$sys_tables['agencies'].".title AS agency_title, ".$sys_tables['managers'].".email
                FROM ".$sys_tables['country']." estate_table
                LEFT JOIN ".$sys_tables['users']."  ON ".$sys_tables['users'].".id = estate_table.id_user
                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager
                WHERE estate_table.published = 1 AND estate_table.date_change = CURDATE() - INTERVAL 25 DAY 
            )
        )
        as aliases
        GROUP BY aliases.db_name,aliases.id_user
        ORDER BY aliases.email, aliases.agency_title
");
$email_text_title = "Следующие объявления агентств будут перемещены в архив <b>через 5 дней</b>.<br><br>";
$email_text = "";
if(!empty($list)){
    // формирование текстов для менеджеров
    foreach($list as $k=>$item){
        if(empty($item['email'])) $item['email'] = 'hitty@bsn.ru';
        $log[$item['email']][] = $item['agency_title']." - ".$item['estate_table_title'].", id пользователя: ".$item['id_user'];
    }

    //отправка пакетов писем
    foreach($log as $email=>$text){
        $mailer = new EMailer('mail');
        // перевод письма в кодировку мейлера
        $html = iconv('UTF-8', $mailer->CharSet, $email_text_title.implode("<br />",$text));
        // параметры письма
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Агентства с заканчивающимися сроками размещения BSN.ru. '.date('Y-m-d H:i:s'));
        $mailer->Body = $html;
        $mailer->AltBody = strip_tags($html);
        $mailer->IsHTML(true);
        if(!Validate::isEmail($email)){
            preg_match('!([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,4}!i', (string) $email, $matches);
            if(!empty($matches[0])) $email = $matches[0];
            else $email = null;
        }
        if($email){         
            $mailer->AddAddress($email);
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'Большой Сервер Недвижимости bsn.ru');
            // попытка отправить
            $mailer->Send();
            echo $html;        
        }
    }
}


?>
