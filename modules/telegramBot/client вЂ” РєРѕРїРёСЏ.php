#!/usr/bin/php;
<?php
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('includes/robot_functions.php');    // функции  из крона
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');
if (is_running($_SERVER['PHP_SELF'])) die('Already running');
//запись всех ошибок в лог
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
require_once('includes/class.email.php');        // для отправки писем
require_once('includes/functions.php');    // функции  из крона
require_once('includes/getid3/getid3.php');

require_once("includes/telegram-api/vendor/autoload.php");
echo "<br />";
echo Config::$values['telegram']['token']."<br />";
$module_template = "templates/clearcontent.html";

use \TelegramBot;
//use \TelegramBot\Api\Client;

//if(!class_exists("\TelegramBot\Api\BotApi")) echo "noclass!<br />";
//else echo "class exists<br />";
try {
    //to set webhook: https://api.telegram.org/bot312438821:AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/setWebhook?url=https://test.bsn.ru/telegramBot/
    
    $bot = new \TelegramBot\Api\Client(Config::$values['telegram']['token']);
    $input = $bot->getRawBody();
    switch($input['text']){
        case '/start':
            $bot->sendMessage($input['message']['chat']['id'], 'start recieved');
            break;
        case '/hello':
            $bot->sendMessage($input['message']['chat']['id'], 'Hi!');
            break;
        default:
            $bot->sendMessage($input['message']['chat']['id'], $input['text']." recieved");
            break;
    }
    
    /*
    $bot->command('start', function ($message) use ($bot) {
        file_put_contents("modules/telegramBot/errors.log",json_encode($message));
        $bot->sendMessage($message->getChat()->getId(), 'start recieved');
    });
    
    $bot->command('ping', function ($message) use ($bot) {
        file_put_contents("modules/telegramBot/errors.log",json_encode($message));
        $bot->sendMessage($message->getChat()->getId(), 'pong!');
    });
    */
    //file_put_contents("modules/telegramBot/errors.log",$bot->getRawBody());    
    
    //$bot->run();

} catch (\TelegramBot\Api\Exception $e) {
    echo "@!#".$e->getMessage();
}
?>
