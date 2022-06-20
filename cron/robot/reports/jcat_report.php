#!/usr/bin/php
<?php
//если самостоятельный запуск, подключаем все что нужно
if(empty($sys_tables)){
    // переход в корневую папку сайта
    define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
    define('TEST_MODE', !empty($_SERVER['SCRIPT_FILENAME']) && preg_match('/test\.bsn\.ru/sui', $_SERVER['SCRIPT_FILENAME']) ? true : false);

    $root = TEST_MODE ? realpath( '/home/bsn/sites/bsn.ru/public_html/' ) : ( DEBUG_MODE ? realpath( "../../.." ) : realpath('/home/bsn/sites/bsn.ru/public_html/' ) ) ;
    if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
    if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
    define( "ROOT_PATH", $root );
    chdir(ROOT_PATH);
    include_once('cron/robot/robot_functions.php');    // функции  (крона
    mb_internal_encoding('UTF-8');
    setlocale(LC_ALL, 'ru_RU.UTF-8');
    mb_regex_encoding('UTF-8');
    setlocale(LC_ALL, 'rus');



    //запись всех ошибок в лог
    $error_log = ROOT_PATH.'/cron/robot/parsers/jcat_xml/error.log';
    file_put_contents($error_log,'');
    ini_set('error_log', $error_log);
    ini_set('log_errors', 'On');

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
    include('includes/class.email.php');
    include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
    if( !class_exists( 'Photos' ) )  require_once('includes/class.photos.php');     // Photos (работа с графикой)
    include('includes/class.moderation.php'); // Moderation (процедура модерации)
    include('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
    include('cron/robot/class.xml2array.php');  // конвертация xml в array

    //логирование выгрузок xml-я
    $log = array();
    // вспомогательные таблицы модуля
    $sys_tables = Config::$sys_tables;
}

$xml = new DOMDocument('1.0','utf-8');
$xmlentire = $xml->appendChild($xml->createElement('objs'));
$xmlentire->setAttribute('date',date('Y-m-d H:i:s'));
$user = $xmlentire->appendChild($xml->createElement('user'));
$user->setAttribute('name','BSN.ru');

$estate_types = array('build','live','commercial','country');

foreach($estate_types as $estate_type){
    $lines = $db->fetchall("SELECT `id_user`,`id`, '".$estate_type."' AS `tab`,rent,`published`,`external_id`, `info_source`,`views_count` FROM ".$sys_tables[$estate_type]." WHERE id_user = 29298 AND published = 1");
    if(empty($lines)) continue;
    foreach($lines as $row_obj){
        $phone_clicks = $db->fetch("SELECT COUNT(*) as cnt FROM ".$sys_tables['phone_clicks_day']." WHERE id_object = ?", $row_obj['id'])['cnt'];
        $item = $user->appendChild($xml->createElement('item'));
        $item->setAttribute('url','https://www.bsn.ru/'.$row_obj['tab'].'/'.($row_obj['rent'] == 1 ? "rent" : "sell")."/".$row_obj['id']."/");
        $item->setAttribute('jcat_external_id',$row_obj['external_id']);
        $item->setAttribute('views_today',$row_obj['views_count']);
        $item->setAttribute('phone_views',$phone_clicks);
    }
}

$filename = ROOT_PATH.'/jcat_objects.xml';
if(file_exists($filename)) unlink($filename);
$xml->formatOutput = true;
$xml->save($filename);
exec("chmod 777 ".$filename);

$mailer = new EMailer('mail');
$mailer->sendEmail("web@bsn.ru","Миша","Сгенерирован XML-отчет по JCAT",false,false,false,"Сгенерирован XML-отчет по JCAT, ".date('Y-m-d H:i:s'));
?>  