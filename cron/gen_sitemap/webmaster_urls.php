#!/usr/bin/php
<?php
define("DEBUG_MODE",true);
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
file_put_contents($error_log,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
//require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.sitemap.php');      // Sitemap (класс для работы с curl)

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");
$url=DEBUG_MODE?'https://www.bsnnew.int':'https://www.bsn.ru';

//инициализируем класс для работы с curl
$sitemap = new sitemap();
$result=array();

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//url, где будем логиниться
$url='https://passport.yandex.ru/passport?mode=auth&retpath=https%3A%2F%2Fmail.yandex.ru';
//логин
$login='ya.bsnru@ya.ru';//$login = 'id246146';    
//пароль
$passwd='parol_bsnru13';//$passwd = 'asd098asd';  
//передается в параметрах 
$twoweeks='yes';
//файл, в котором будут храниться cookie
$user_cookie_file = dirname(__FILE__).'/cookies.txt'; 
//url, откуда будем скачивать CSV
$url_csv='http://webmaster.yandex.ru/downloads/error_urls.csv.xml?host=11706&code=2024&output_type=xml&format=csv';
//файл, в который будем скачивать csv
$path='cron/gen_sitemap/webmaster.csv';

//(авторизоваться надо только раз в 2 недели)
//проверяем, прошло ли 2 недели:
$cookietime=date('Y-m-d',filemtime($path));
$today=date('Y-m-d',time());
$diff = abs(strtotime($today) - strtotime($cookietime));
$years = floor($diff / (365*60*60*24));
$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));

if (($years==0)&&($months==0)&&($days>=14)){
    if (auth($url)==FALSE) echo "Ошибка при авторизации<br>";
}

//скачиваем csv
download_csv($url_csv,'cron/gen_sitemap/webmaster.csv');

//чистим таблицу в базе перед записью
$db->query('TRUNCATE '.$sys_tables['webmaster_site_urls']);

//читаем скачанный csv и пишем в базу новый список url
if(!parse_csv($path)){
    die;
}

//префикс к которому будем делать запросы, приписывается слева к url в базе
$prefix='www.bsn.ru';

//читаем список url из базы:
do{
    //выбираются 1000 записей, которые не проверялись сегодня
    $query="SELECT url FROM ".$sys_tables['webmaster_site_urls']." WHERE server_answer = 0 LIMIT 1000";
    $urls=$db->fetchall($query);
    if(empty($urls)) break;
    //обрабатываем url
    $result = $sitemap->multi_curl_site_urls($urls,$prefix);
    $i=0;
    foreach($urls as $url){    
        $url=$url['url'];
        //убираем префикс перед записью в базу
        $url_to_base=str_replace('https://www.bsn.ru','',$url);
        //пишем в базу
        $query="UPDATE ".$sys_tables['webmaster_site_urls']." SET server_answer='".$db->real_escape_string($result['server_answers'][$i])."', title='".$db->real_escape_string($result['pages_titles'][$i])."', check_date='".$db->real_escape_string($result['pages_date'][$i])."', url='".$db->real_escape_string($url_to_base)."' WHERE url='".$db->real_escape_string($url_to_base)."' OR url='".$db->real_escape_string($url)."'";
        $db->query($query);
        $i++;
    }
    echo count($urls)." urls processed<br>";
}while (!empty($urls));
echo "Finished<br>";
?>
<?php
//###########################################################################
// авторизуемся по указанному url
//###########################################################################
function auth($url){
    
    GLOBAL $user_cookie_file, $login, $passwd,$twoweeks;
    
    //для передачи при логине
    $timestamp=time();
    
    $curl = curl_init($url);
    //устанаваливаем URL
    curl_setopt($curl, CURLOPT_URL,$url);
    //результат должен отдаваться в переменную
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
    //нужно, чтобы не выдавало ошибку SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    //нужно, чтобы проходило редиректы
    curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
    //автоматическая установка поля Referer при редиректах
    curl_setopt($curl,CURLOPT_AUTOREFERER,true);
    //если включить след. строку, не будут писаться cookie, заголовки включатся в html и сможет работать get_headers(url)
    //curl_setopt($curl, CURLOPT_HEADER, true); 
    //версия браузера
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)");
    //файл, где будут храниться cookie
    curl_setopt($curl, CURLOPT_COOKIEFILE, $user_cookie_file);
    //сюда сохранятся текущие cookie после curl_close
    curl_setopt($curl, CURLOPT_COOKIEJAR, $user_cookie_file);
    //флаг, что будем использовать POST
    curl_setopt($curl, CURLOPT_POST,1);
    //передаваемые данные
    curl_setopt($curl, CURLOPT_POSTFIELDS, "login=$login&passwd=$passwd&twoweeks=$twoweeks&timestamp=$timestamp");
    //передаваемые заголовки (без этого не будет отдаваться код страницы)
    $headers = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8',
        'Accept-Language: ru,en-us;q=0.7,en;q=0.3',
        'Accept-Encoding: deflate',
        'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7'
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
    
    //делаем запрос
    $html = curl_exec($curl);
    $header  = curl_getinfo($curl);
    //@$response_headers=get_headers($url);
    if(!$html){
        $error = curl_error($curl).'('.curl_errno($curl).')';
        return FALSE;
    }  
    curl_close($curl);
}
//###########################################################################
// скачиваем файл csv с указанного url кладем в файл по адресу $path
//###########################################################################
function download_csv($url,$path){
    global $user_cookie_file;
    
    //открываем файл, в который положим csv
    $fp = fopen($path, 'w');
    
    $curl = curl_init($url);
    
    //первая страница имеет url без page_num и парсится отдельно
    curl_setopt($curl, CURLOPT_URL,$url);
    //результат должен отдаваться в переменную
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
    //нужно, чтобы не выдавало ошибку SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    //нужно для редиректов
    curl_setopt($curl,CURLOPT_FOLLOWLOCATION,1);
    //Подставляем куки раз
    curl_setopt($curl, CURLOPT_COOKIEFILE, $user_cookie_file);
    //Подставляем куки два 
    curl_setopt($curl, CURLOPT_COOKIEJAR, $user_cookie_file); 
    //указываем, что нужно положить результат в файл
    curl_setopt($curl, CURLOPT_FILE, $fp);
    //версия браузера выбирается из набора
    switch(rand(0,4)){
        case 0: curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)");break;
        case 1: curl_setopt($curl, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.9.168 Version/11.51");break;
        case 2: curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.1 (KHTML, like Gecko) Chrome/6.0.427.0 Safari/534.1");break;
        case 3: curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)");break;
        case 4: curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5");break;
        default: curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5");break;
    }
    
    //запрос
    $data = curl_exec($curl);
    if(!$data){
        $error = curl_error($curl).'('.curl_errno($curl).')';
        echo $error;
        return FALSE;
    }
    
    //закрываем curl и файл
    curl_close($curl);
    fclose($fp);
}
//###########################################################################
// читаем файл csv (читаем url и дату изменения)
//###########################################################################
function parse_csv($path){
    //массив url
    $list=array();
    //массив дат изменения
    $change_dates=array();
    //счетчик url
    $k=0;
    //открываем указанный файл
    $handle=fopen(ROOT_PATH.'/'.$path,"r");
    if ($handle) {
        //пропускаем первые 4 строки
        $buffer = fgets($handle, 4096);//тип содержимого
        $buffer = fgets($handle, 4096);//дата
        $buffer = fgets($handle, 4096);//сколько
        $buffer = fgets($handle, 4096);//заголовки
        //читаем url
        while (($buffer = fgets($handle, 4096)) !== false) {
            //получили url
            $list[]=preg_replace('/(;.*)/siu','',$buffer);
            //убрали дату в конце (последнее посещение)
            $temp=preg_replace('/;([^;]+)$/siu','',$buffer);
            //получили дату последнего изменения
            $temp=preg_replace('/^(.*;)/siu','',$temp);
            $change_dates[]=implode('-',array_reverse(explode('.',trim($temp))));
            ++$k;
        }
        if (!feof($handle)) {
            echo "Error while reading".ROOT_PATH.'/'.$path.'/'.$filename."<br>";
            return FALSE;
        }
        fclose($handle);
    }
    else{
        echo 'Error while opening '.ROOT_PATH.'/'.$path.'/'.$filename."<br>";
        return FALSE;
    }
    //пишем в базу
    urls_to_db($list,$change_dates);
    unset($list);
    return TRUE;
}
//###########################################################################
//пишем список url в базу
//###########################################################################
function urls_to_db($urls,$change_dates){
    GLOBAL $db,$sys_tables;
    $i=0;
    while ($i < count($urls)){
        $db->query("INSERT IGNORE INTO ".$sys_tables['webmaster_site_urls']." SET url = ?, change_date = ?
                    ON DUPLICATE KEY UPDATE url = ?",
                    $urls[$i], $change_dates[$i], $urls[$i]);
        ++$i;
    }
}
?>
