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
$error_log = ROOT_PATH.'/cron/gen_sitemap/error.log';
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
$GLOBALS['db']=$db;

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//логи для почты
$log = array();

//читаем список новостей и email
$list=$db->fetchall('SELECT *,LEFT (`up_banner`,2) as `subfolder_up`,LEFT (`down_banner`,2) as `subfolder_down` FROM '.$sys_tables['specspam'].' WHERE published=1');
if(!empty($list)){
    $list_emails=$db->fetchall('SELECT email, id FROM '.$sys_tables['specspam_users']." GROUP BY email");

    if( !empty( $item['subject'] )) {
    //создание кампании для рассылки
    $db->insertFromArray(
        $sys_tables['newsletters_campaigns'],
        array( 'title' => 'Спецрассылка: ' . $item['subject'] )
    );
    $id_campaign = $db->insert_id;
    if( empty( $id_campaign ) ) $id_campaign = $db->fetch(" SELECT MAX(id) as id FROM " . $sys_tables['newsletters_campaigns'] )['id'];
    }

    foreach ($list as $item){
	//создание кампании для рассылки
	$db->insertFromArray(
        	$sys_tables['newsletters_campaigns'],
	        array( 'title' => 'Спецрассылка: ' . $item['subject'] )
	);
	$id_campaign = $db->insert_id;
	if( empty( $id_campaign ) ) $id_campaign = $db->fetch(" SELECT MAX(id) as id FROM " . $sys_tables['newsletters_campaigns'] )['id'];


        $db->query('UPDATE '.$sys_tables['specspam'].' SET begin_datetime = NOW() WHERE id=?',$item['id']);
        // параметры письма
        Response::SetString('subject',$item['subject']);
        Response::SetString('type',$item['type']);
        if(!empty($item['up_banner'])) Response::SetString('up_banner','https://st1.bsn.ru/'.Config::$values['img_folders']['spam_banners'].'/'.$item['subfolder_up'].'/'.$item['up_banner']);
        if(!empty($item['down_banner']))Response::SetString('down_banner','https://st1.bsn.ru/'.Config::$values['img_folders']['spam_banners'].'/'.$item['subfolder_down'].'/'.$item['down_banner']);
        Response::SetString('content',$item['content']);
        //не выводить подложку "Отписаться от рассылки"
        Response::SetString('unsubscribe_hide',true);
        Response::SetArray('env',array('url'=>Host::GetWebPath('/'),'host'=>Host::$host));
        // инициализация шаблонизатора
        $eml_tpl = new Template('spam.email.html', 'cron/mailers/');
        // формирование html-кода письма по шаблону
        foreach($list_emails as $item_email){
            // перевод письма в кодировку мейлера
            $mailer = new EMailer('mail');
            Response::SetString('user_email',$item_email['email']);
            Response::SetString('user_id',$item_email['id']);
            Response::SetString('user_code',sha1(md5($email['id'].$email['email']."special!_adding")));
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet . '//IGNORE', $html);
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $item['subject']);
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags($html);
            $mailer->IsHTML(true);
		

	        Response::SetString( 'pixel', '<img src="https://www.bsn.ru/pxl/?campaign=' . $id_campaign . '&email=' . $item_email['email'] . '&status=2" />');

            if (Validate::isEmail($item_email['email'])) {
                $mailer->AddAddress($item_email['email']);
                switch($item['type']){
                    case 2:
                        $mailer->From = 'no-reply@dizbook.com';
                        $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'Dizbook Weekly');
                        break;
                    case 3:
                        $mailer->From = 'newsletter@interestate.ru';
                        $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'INTERESTATE Зарубежная недвижимость');
                        break;
                    default: 
                        $mailer->From = 'no-reply@bsn.ru';
                        $mailer->FromName = @iconv('UTF-8', $mailer->CharSet. "//TRANSLIT",'BSN.ru');
                }
                usleep( 100 );
		// попытка отправить
            	$user_data = array(
                	'id_campaign' => $id_campaign,
	                'email' => $item_email['email'],
        	        'status' => 1
	        );
                if( $mailer->Send() ) $db->insertFromArray( $sys_tables['newsletters'], $user_data );


            } else echo "Invalid email ".$item_email['email'].", ";
        }
        //
        $db->query('UPDATE '.$sys_tables['specspam'].' SET published=2, end_datetime = NOW() WHERE id=?',$item['id']);    
    }
    
}
