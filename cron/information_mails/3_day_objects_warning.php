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
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

////////////////////////////////////////////////////////////////////////////////////////////////
// Отправка писем о перемещении объектов в архив
////////////////////////////////////////////////////////////////////////////////////////////////   
$list = $db->fetchall("
        SELECT aliases.id, aliases.estate_type, aliases.id_user, aliases.estate_table_title,  aliases.user_name, aliases.email  FROM
        (
            (
                SELECT estate_table.id, 'live' as estate_type, estate_table.id_user, 'Жилая' as estate_table_title,  IF(".$sys_tables['users'].".name='',".$sys_tables['users'].".lastname,".$sys_tables['users'].".name) AS user_name, ".$sys_tables['users'].".email
                FROM ".$sys_tables['live']." estate_table
                LEFT JOIN ".$sys_tables['users']."  ON ".$sys_tables['users'].".id = estate_table.id_user
                WHERE estate_table.published = 1 AND estate_table.info_source = 1 AND estate_table.date_change = CURDATE() - INTERVAL 28 DAY AND ".$sys_tables['users'].".email!='' 
            ) UNION (
                SELECT estate_table.id, 'build' as estate_type, estate_table.id_user, 'Строящаяся' as estate_table_title, IF(".$sys_tables['users'].".name='',".$sys_tables['users'].".lastname,".$sys_tables['users'].".name) AS user_name, ".$sys_tables['users'].".email
                FROM ".$sys_tables['build']." estate_table
                LEFT JOIN ".$sys_tables['users']."  ON ".$sys_tables['users'].".id = estate_table.id_user
                WHERE estate_table.published = 1 AND estate_table.info_source = 1 AND estate_table.date_change = CURDATE() - INTERVAL 28 DAY AND ".$sys_tables['users'].".email!='' 
            ) UNION (
                SELECT estate_table.id, 'commercial' as estate_type, estate_table.id_user,'Коммерческая' as estate_table_title,  IF(".$sys_tables['users'].".name='',".$sys_tables['users'].".lastname,".$sys_tables['users'].".name) AS user_name, ".$sys_tables['users'].".email
                FROM ".$sys_tables['commercial']." estate_table
                LEFT JOIN ".$sys_tables['users']."  ON ".$sys_tables['users'].".id = estate_table.id_user
                WHERE estate_table.published = 1 AND estate_table.info_source = 1 AND estate_table.date_change = CURDATE() - INTERVAL 28 DAY AND ".$sys_tables['users'].".email!='' 
            ) UNION (
                SELECT estate_table.id, 'country' as estate_type, estate_table.id_user,'Загородная' as estate_table_title, IF(".$sys_tables['users'].".name='',".$sys_tables['users'].".lastname,".$sys_tables['users'].".name) AS user_name, ".$sys_tables['users'].".email
                FROM ".$sys_tables['country']." estate_table
                LEFT JOIN ".$sys_tables['users']."  ON ".$sys_tables['users'].".id = estate_table.id_user
                WHERE estate_table.published = 1 AND estate_table.info_source = 1 AND estate_table.date_change = CURDATE() - INTERVAL 28 DAY AND ".$sys_tables['users'].".email!='' 
            )
        )
        as aliases
        ORDER BY aliases.email
");

if(!empty($list)){
    // формирование текстов для для пользщователей
    foreach($list as $k=>$item) {
            $log[$item['email']]['user_name'] = $item['user_name'];
            $log[$item['email']]['text'][]= '&bull; <a href="https://www.bsn.ru/'.$item['estate_type'].'/'.$item['id'].'" target="_blank">'.$item['id'].'</a>, '.$item['estate_table_title'].' недвижимость.';
    }

    //отправка пакетов писем
    foreach($log as $email=>$text){
        $mailer = new EMailer('mail');
        $html = 'Здравствуйте, '.$text['user_name'].'!<br><br>
        Напоминаем, что следующие ваши объявления будут <b>перемещены в архив</b> через 3 дня:<br><br>';
        $html .= implode("<br />",$text['text']);
        $html .= '<br><br>
        Продлить данные объявления (или добавить новые) Вы можете в <b>Личном кабинете</b>:
        <a href="https://www.bsn.ru/members/cabinet/">https://www.bsn.ru/members/cabinet</a>
        <br><br>
        Желаем Вам приятной работы!<br /><br />

        --<br />
        С уважением, администрация портала BSN.ru.<br />
        <a href="https://www.bsn.ru/">https://www.bsn.ru</a><br />
        info@bsn.ru ';

            
        // перевод письма в кодировку мейлера
        $html = iconv('UTF-8', $mailer->CharSet, $html);
        // параметры письма
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Ваши объявления на сайте BSN.ru');
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
