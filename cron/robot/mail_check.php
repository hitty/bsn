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
require_once('includes/mailboxer/mailboxer.class');

// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$module_tables = include('cron/conf_tables.php');

$sys_tables = Config::$values['sys_tables'];

// log file
$mail_text = array();

// new directories
define('ROBOT_DIRECTORY', '/home/bsn/sites/bsn.ru/public_html/cron/robot/files/bn_txt/');
// imap connection
define('CONNECT_ATTEMPTS', 5);    // max number of attempts to connect
define('DELAY_TIME', 30);    // time between attempts


// function for logging
function logf(){
    global $mail_text;
    $mail_text[] =  "From: wmailer@bsn.ru\n";
    $mail_text[] =  "To: scald@bsn.ru\n";
    $mail_text[] =  "Subject: " . date('d.m.Y H:i:s') . ". BSNROBOT REPORT.\n";
    $mail_text[] =  "Content-Type: text/html; charset=koi8-r\n\n";
    $mail_text[] =  "<html>\n<body bgcolor=#ffffff>\n";
    $mail_text[] =  "<br>Данное письмо сформировала программа обрабатывающая<br>почтовые сообщения приходящие на e-mail <b>bsnrobot@bsn.ru</b>.<br>";

}

// function for simple logging
function logf_s($msg){
    global $mail_text;
    $mail_text[] =  "\n<br>$msg";
}

// проверка логина и пароля
function checkLogin($login, $passwd){
    echo $login;
    global $db;
    $login = $db->real_escape_string($login);
    $passwd = $db->real_escape_string($passwd);
    echo $sql = "SELECT `users`.`id` FROM ".Config::$sys_tables['users']."
                 WHERE `login` = '" . $login . "' AND `passwd` = '" . sha1(sha1($passwd)) . "' LIMIT 1;";
    logf_s($sql);
    $result = $db->querys($sql);
    if($db->affected_rows == 1){
        list($id) = $result->fetch_array();
		echo "ID = ".$id;
        return $id;
    } else return false;
    
}

// получить имя файла для записи
function getFileName($directory, $prefix, $extension)
{
    $i = 0;
    while(1)
    {
        $i++;
        $t_filename = $directory .  $extension . $i;
        if( file_exists($t_filename) == false) break;
    }
    return $t_filename;
}

$DELETE = true;            // delete messages from server

// trying to connect
$attempts = CONNECT_ATTEMPTS;
do
{
    $attempts--;
    try
    {
        $connected = true;
        $mailboxer = new mailboxer('pop.yandex.ru:110','robot@bsn.ru','taAlW0_sw4F1');;
    }
    catch (Exception $e)
    {
        $connected = false;
        logf($e->getMessage());
        unset($mailboxer);
        if ($attempts > 0) sleep(DELAY_TIME);
    }
}
while (!$connected && ($attempts > 0));

if ($connected) logf();
else{
    logf();
    logf_s("Couldn't connect to bsnrobot@bsn.ru with " . CONNECT_ATTEMPTS . " attempts");
    fclose($LOG);
    die();
}

// сколько сообщений в ящике
logf_s("Сообщений в ящике : " . $mailboxer->msg_count());
$_letter_count = $mailboxer->msg_count();
$_processed_count =0;
// читаем письма по одному
if ($mailboxer->msg_count() > 0){
    for ($current_msgno = 1; $current_msgno <= $mailboxer->msg_count(); $current_msgno++){
        $msg_info = $mailboxer->fetch_message($current_msgno);

        // по умолчанию
        $login = false;
        $passwd = false;
        $format = false;
        $ofrom = false;
        $authorized = false;
        $user_id = false;

        logf_s("\n" . $current_msgno. ". -------------------------------------------------------------------");
        logf_s("E-mail    : " . $msg_info->from_addr);
        logf_s("Name    : " . $msg_info->from_name);
        logf_s("Subject    : " . $msg_info->subject);
        logf_s("");

        //Флаг для агентства РФН
        $rfn_flag = 0;
        
        // обработка частей письма
        foreach ($msg_info->parts as $part)
        {
            logf_s("type : " . $part->type);
            logf_s("subtype : " . $part->subtype);
            if($part->subtype == 'HTML' || ($part->subtype == 'PLAIN' && empty($part->filename)) || ($part->subtype == 'VND.MS-EXCEL' && empty($part->filename))){
                $content = preg_replace("/\<br(\/)?\>/i","\n",$part->content);
                $content = str_replace("&nbsp;"," ",$content);
                $content = strip_tags($content);
                preg_match("/^(.*)login(.*)password(.*)format(.*)ofrom(.*)$/msiU",$content,$matches);
                if(!empty($matches[2]) && !empty($matches[3]) && !empty($matches[4])){
                    $pattern = "/[^(a-zA-Z\-\@\;\+\.\,\_\(\)\{\}\[\]\<\>\~0-9)]/i";
                    echo $login =  strip_tags(preg_replace($pattern,"",$matches[2]));
                    
                    echo $passwd =  strip_tags(preg_replace($pattern,"",$matches[3]));
                    $format =  preg_replace($pattern,"",$matches[4]);
                    $format = mb_strtolower($format,mb_detect_encoding($format));
                    $ofrom =  preg_replace("/[^(a-zA-Z\_0-9)]/i","",$matches[5]);
                }
				echo "#############################################";
				echo "login = ".$login."\r\n";
				echo "pass = ".$passwd."\r\n";
				echo "format = ".$format."\r\n";
				echo "ofrom = ".$ofrom."\r\n";
				echo "#############################################";
                if($login != false && $passwd != false && $user_id == false){
					$user_id = checkLogin($login,$passwd);
                    if($user_id){
                        
                        $authorized = true;
                        logf_s("Авторизация : OK !!!" . " ==> ( ". $login . " / " . $passwd . " )");
                        logf_s("USER_ID : " . $user_id);
                        logf_s("");
                        
                        //проверяем есть ли тариф
                        $sender_info = $db->fetch("SELECT ".$sys_tables['agencies'].".title,".$sys_tables['agencies'].".id,".$sys_tables['agencies'].".id_tarif 
                                                   FROM ".$sys_tables['users']." 
                                                   LEFT JOIN ".$sys_tables['agencies']." 
                                                   ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
													WHERE ".$sys_tables['users'].".id = ".$user_id);
                        if(empty($sender_info['id_tarif'])){
                            $mail_text[] = "У агентства #".$sender_info['id']." ".$sender_info['title']." не проставлен тариф";
                            break;
                        }
                    }
                }
                $login = false; 
                $passwd = false;
            }

            $t_extn = false;
            $t_flag = false;

            //Если файл от РФН, то запись всех файлов в отдельную папку, с последующей обработкой и приведением к формату БН
            $path_info = pathinfo($part->filename);
            $path_info = !empty($path_info['extension']) ? $path_info['extension'] : 'no extension';
            //decode MIME header
            if(strstr($part->filename,'=?UTF-8')!='') $part->filename = iconv_mime_decode($part->filename);
            logf_s("path_info:".$path_info.',format:'.$format.',user_id'.$user_id);
            if( ($format == "excel") && $authorized == true ){
                $t_generate_file = '/home/bsn/sites/bsn.ru/public_html/cron/robot/files/emls_xls/'.$user_id.'_'.strtolower($part->filename);
                $out = fopen($t_generate_file,"a");
                fwrite($out, $part->content);
                fclose($out);
                $rfn_flag = 1;
            }
			if( ($format == "excel_custom") && $authorized == true ){
                $t_generate_file = '/home/bsn/sites/bsn.ru/public_html/cron/robot/files/excel_custom/'.$user_id.'_'.strtolower($part->filename);
                $out = fopen($t_generate_file,"a");
                fwrite($out, $part->content);
                fclose($out);
                $rfn_flag = 1;
            }
            if($format == "eip" && $path_info == 'xml'){
                $t_generate_file = '/home/bsn/sites/bsn.ru/public_html/cron/robot/files/eip_xml/'.$user_id.'_'.strtolower($part->filename);
                $out = fopen($t_generate_file,"a");
                fwrite($out, $part->content);
                fclose($out);
                logf_s("Файл <b>" . $part->filename . "</b> извлечен, обработан и сохранен как <b>" . $user_id.'_'.strtolower($part->filename) . "</b><br>");
            }
            $bnxml_flag = 0;
            $bnxml_count=0;
            if($format == "bnxml" && !empty($part->filename) && $path_info == 'xml')
            {
                $bnxml_count++;
                if($authorized == true) {
                    $t_generate_file = '/home/bsn/sites/bsn.ru/public_html/cron/robot/files/bn_xml/'.$user_id.'_'.$bnxml_count.'.xml';
                    $out = fopen($t_generate_file,"a");
                    fwrite($out, $part->content);
                    fclose($out);
                    logf_s("Файл <b>" . $part->filename . "</b> извлечен, обработан и сохранен как <b>" . strtolower($part->filename) . "</b><br>"); 
                } else $xml_file = strtolower($part->filename);  
            }

            if($authorized == true){
                //parsing xml file
                if($bnxml_count>0 && !empty($xml_file)){
                    $t_generate_file = '/home/bsn/sites/bsn.ru/public_html/cron/robot/files/bn_xml/'.$user_id.'_'.$bnxml_count.'.xml';
                    $out = fopen($t_generate_file,"a");
                    fwrite($out, $part->content);
                    fclose($out);
                    logf_s("Файл <b>" . $part->filename . "</b> извлечен, обработан и сохранен как <b>" . strtolower($part->filename) . "</b><br>");
                }

                if (isset($part->filename)){
                    $t_file_name = strtolower($part->filename);

                    if($t_file_name == "all.txt" || $t_file_name == "zhil.txt" || $t_file_name == "komn.txt" || $t_file_name == "prodaja.txt" || $t_file_name == "KK2BN.TXT" || $t_file_name == "kk2bn.txt"){
                        // продажа жилой недвижимости
                        $t_extn = ".all"; 
                        $t_flag = true;
                    } elseif ($t_file_name == "ard.txt"){
                        // аренда жилой недвижимости
                        $t_extn = ".ard"; 
                        $t_flag = true;
                    } elseif ($t_file_name == "kn.txt") {
                        // коммерческая недвижимость
                        $t_extn = ".kn"; 
                        $t_flag = true;  
                    } elseif ($t_file_name == "ned.txt" || $t_file_name == "ned1.txt")  {
                        $t_extn = ".ned"; 
                        $t_flag = true;
                    } elseif ($t_file_name == "zdd.txt" || $t_file_name == "zag.txt")  {
                        $t_extn = ".zd"; 
                        $t_flag = true;
                    }
                    // если файл опознан - записать в нужную директорию
                    if($t_flag == true){
                        $t_generate_file = getFileName(ROBOT_DIRECTORY,$format,$format . $t_extn);
                        $out = fopen($t_generate_file,"a");
                        fwrite($out, $ofrom . "\n" . $user_id . "\n");
                        fwrite($out, $part->content);
                        fclose($out);
                        $_processed_count++;
                        logf_s("Файл <b>" . $part->filename . "</b> извлечен, обработан и сохранен как <b>" . $t_generate_file . "</b><br>");
                    } // IF T_FLAG == TRUE
                }
            } // IF AUTHORIZED == TRUE
        } // END OF FOREACH

        if($authorized == false){
            logf_s("Авторизация : ERROR !!!");
            logf_s("");
        }
        // deleting message from mailbox
        $mailboxer->delete($current_msgno);
    }
}
// closing imap connection
unset($mailboxer);
$mail_text[] =  "\n</body>\n</html>\n";
   echo $mail_text = implode("\n",$mail_text);
if(!empty($mail_text)){
    $mailer = new EMailer('mail');
    $html = iconv(mb_detect_encoding($mail_text),"CP1251//TRANSLIT",$mail_text);
    if(empty($html)) $html = $mail_text;
    // параметры письма
    $mailer->Subject = iconv('UTF-8', "CP1251//TRANSLIT", 'Проверка почты роботом BSN. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
    $mailer->AddAddress('web@bsn.ru');
    $mailer->From = 'wmailer@bsn.ru';
    $mailer->FromName = iconv('UTF-8', "CP1251//TRANSLIT",'Проверка почты роботом BSN');
    // попытка отправить
    $mailer->Send();        
    echo $html;
}
?>