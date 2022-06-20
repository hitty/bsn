#!/usr/bin/php
<?php
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
include('includes/class.excel.reader.php');  // конвертация excel в array
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$sys_tables = Config::$sys_tables;

/*
1-страница раньше была, сейчас нет и не будет
2- ajax запрос
301 - ошибка в скрипте, сделан 301 редирект
9 - новый урл есть , что делать с таким Урлом?
4 - удаленный объект (новость, недвижимость), в общем карточка раньше была, но ее удалили
5 - старые урлы для аналитики. Сами статьи остались. Что делать?
6 - старые урлы статей перекочевали в новости, процентов 70 из них есть, что делать?
7 - Коттеджный поселок: объект существует,  но статус стоит - завершен, поэтому отдается 404
8 - Объект (country, build, commercial, country) существует, но в черновике
*/
//папка с txt файлами 
$dir = ROOT_PATH."/cron/404_manual_fixed/export/";
$dh = opendir($dir);
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..'){
        $data = new Spreadsheet_Excel_Reader($dir.$filename);
        
        //формирование файла mapping
        for($row=1; $row<=$data->rowcount(); $row++){
            for($col=1; $col<=$data->colcount(); $col++){
                $rows[$row][$col] = $data->val($row,$col);
            }    
            if($row>300) break;
        } 
        foreach($rows as $k=>$row){
            if(!empty($row[1])) {
                $date = explode('/',$row[1]);
                $date = $date[2].'-'.$date[0].'-'.$date[1].' 12:00:00';
            }
            $db->query("INSERT INTO law.contacts SET id_user = 4, status = ?, fio = ?, phone = ?, question = ?, result = ?, notes = ?, datetime = ?",
                $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $date 
            );        
        }
        
        
    }
}
?>
