#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
echo DEBUG_MODE;
echo $root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );

if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/mailers/vk_error.log';
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');


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
require_once('includes/class.host.php');
require_once('includes/class.template.php');
require_once('includes/class.email.php');
require_once('includes/class.content.php');
require_once('includes/class.opinions.php');
require_once('includes/class.estate.statistics.php');
require_once('includes/class.vk.php');

define('IS_DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int/i', __SERVER_NAME__) ? true : false);
$bsn_url = IS_DEBUG_MODE ? "https://www.bsnnew.int/" : "http://st.bsn.ru/";
 $filename = ROOT_PATH.'/cron/mailers/vk_params.txt';
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$_secret = '9NWfVGpVALvssJz1cu0W';
$_client_id = 2816223;
$scopes = array('offline','wall','groups','photos');
$get_parameters = Request::GetParameters();      
if( !empty( $get_parameters['get_code'] ) ) {
    $auth_link ='http://oauth.vk.com/authorize?'.http_build_query(array(
                'client_id'     => $_client_id,
                'scope'         => implode(',', $scopes),
                'redirect_uri'  => 'http://api.vk.com/blank.html',
                'display'       => 'page',
                'response_type' => 'code'
    )); 
    Host::Redirect($auth_link);
} else if(!empty($get_parameters['get_token'])){
    $post_parameters = Request::GetParameters(METHOD_POST);
    if(!empty($post_parameters)){
        $token = json_decode(file_get_contents('https://oauth.vk.com/access_token?'.http_build_query(array(
            'client_id'     => $_client_id,
            'client_secret' => $_secret,
            'code'          => $post_parameters['code'],
            'redirect_uri'  => 'http://api.vk.com/blank.html'
        ))));  
        $fpointer = fopen($filename, 'w');
        fwrite($fpointer, 'code:' . $post_parameters['code'] . ';token:' . $token->access_token . ';');
        fclose($fpointer); 
        chmod($filename, 0666);    
        echo 'Новый токен получен!';
    } else {
        echo "<form method=\"POST\" action=\"/cron/mailers/sent_news_vk.php?get_token=1\"><input type=\"text\" value=\"\" name=\"code\"/><input type=\"submit\" value=\"Отправить\" /></form>";
    }
}  else {
    $content = file_get_contents($filename);
    preg_match_all("|code\:([a-z0-9]{1,})\;|msiU", $content, $code);
    preg_match_all("|token\:([a-z0-9]{1,})\;|msiU", $content, $token);
    if(!empty($token[1][0])) $_access_token = $token[1][0];
    else die('wrong filename');
    $vk_posting = new vk($_access_token, $_client_id, 33058562);
    $_album_id = '190833663'; //$vk_posting->create_album('Новости BSN.ru','');


    $news = new Content('news');
    $list = $news->getList(30,0,false,false,'vkontakte_feed = 1 AND published = 1 AND datetime < NOW()');
    foreach($list as $k=>$item){
        $photo_id = false;
        $photo_error = false;
        if(!empty($item['photo'])){
            echo $file = $root . '/' . Config::$values['img_folders']['news'].'/big/'.$item['subfolder'].'/'.$item['photo'];
            echo "\n";
            if(file_exists($file)) $photo_id = $vk_posting->upload_photo($file, $_album_id, html_entity_decode(strip_tags($item['content_short'])));
            else $photo_error = true;
        }
        if(empty($photo_error))
            $status = $vk_posting->post(html_entity_decode($item['title'])."\n".'https://www.bsn.ru/news/'.$item['category_code'].'/'.$item['region_code'].'/'.$item['id'].'/',$photo_id);    
        if(empty($status)) {
            $mailer = new EMailer('mail');
            $eml_tpl = new Template('sent.news.vk.html', 'cron/mailers/');
            $html = $eml_tpl->Processing();
            $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
            // параметры письма
            $mailer->Body = $html;
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Неудачный постинг новостей ВК'.(!empty($photo_error) ? ", нет картинки ".$file : ""));
            $mailer->IsHTML(true);
            $mailer->AddAddress('web@bsn.ru');
            $mailer->From = 'no-reply@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet, "BSN.ru");
            // попытка отправить
            $mailer->Send();        
        }
        else $db->query("UPDATE ".$sys_tables['news']." SET `vkontakte_feed` = 3 WHERE  vkontakte_feed = 1 AND published = 1 AND datetime <=NOW() AND id = ?", $item['id']);
    }
}
       

//если были ошибки выполнения скрипта
if(filesize($error_log)>10){
    $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
    $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
    $error_log_text .= '</font>';
} else $error_log_text = "";

?>
