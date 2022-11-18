<?php
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("/home/hitty/public_html/bsn.int/") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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
$error_log = ROOT_PATH.'/cron/comagic/spam_error.log';
file_put_contents($error_log,'');
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
require_once('includes/class.email.php');        // для отправки писем
require_once('includes/functions.php');    // функции  из крона
require_once('includes/getid3/getid3.php');
if( !class_exists('Sendpulse') ) require_once("includes/class.sendpulse.php");
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

//login="pm@bsn.ru" password="Freelife0"  sale_bsn_id=4097
$curl = curl_init();
//логинимся
$session_key = comagic_login($curl,"ya.bsnru@yandex.ru","dR4ZEjaDaJEgozMKR9zW");
//читаем id bsn.ru
$date_type = "site";
$response = comagic_get_data($curl,$session_key,$date_type);
foreach ($response as $key=>$item){
    if ($item['domain'] == "bsn.ru"){
        $site_id = $item['id'];
        break;
    } 
}

//читаем список категорий звонков (нецелевой контакт, ЛИД вторичка итд)
$date_type = "tag";
$call_tags = comagic_get_data($curl,$session_key,$date_type);

//коммуникации
$date_type = "communication";
$parameters = array("site_id"=>$site_id);
$response = comagic_get_data($curl,$session_key,$date_type,$parameters);
print_r( $response );


//статистика за период (за неделю) по рекламным кампаниям
$date_type = "stat";             
$parameters = array("site_id"=>$site_id);
$date_from = date('o-m-d H:i:s',strtotime('-13120 minutes'));
$date_till = date('o-m-d H:i:s',strtotime('-20 minutes'));
//информация по звонкам рекламных кампаний с учетом типов звонков 
$date_type = "call";
/*$date_from = date('o-m-d',time()-60*60*24*6);
$date_from = date('o-m-d',time()-60*60*24*30);*/

$counter=0;
$parameters = array("site_id"=>$site_id,"date_from"=>"$date_from","date_till"=>"$date_till");
$calls = comagic_get_data( $curl, $session_key, $date_type, $parameters );
print_r( $calls );
foreach ( $calls as $key=>$item ) {
    if(!empty($calls[$key]['file_link'][0])) $calls[$key]['file_link'] = $calls[$key]['file_link'][0];
}

//пишем звонки в базу
foreach($calls as $key=>$item){
    $item['num_from'] = Convert::ToPhone($item['numa'], false, 8)[0];
    unset($item['numa']);
    $item['num_to'] = Convert::ToPhone($item['numb'], false, 8)[0];
    unset($item['numb']);

    //поиск компании по телефону
    $agency = $db->fetch("SELECT 
                             ".$sys_tables['users'].".id as id_user, 
                             ".$sys_tables['users'].".balance, 
                             ".$sys_tables['agencies'].".*,
                             ".$sys_tables['managers'].".email as manager_email,
                             ".$sys_tables['managers'].".name AS manager_name
                         FROM ".$sys_tables['agencies']."
                         RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id                                                              
                         LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager                                                              
                         WHERE advert_phone = ? OR advert_phone_objects = ?", $item['num_to'], $item['num_to']
    );
    if(!empty($agency) && !($item['duration']>0 && empty($item['file_link']))) {
        $item['call_hash'] = md5($item['num_from'].$item['num_to'].$item['call_date']);
        //выкидываем пустые значения, иначе будет валиться на insertFromArray в методе quoted
        foreach($item as $attr => $value){
            if(empty($value)) unset($item[$attr]);
        }
        //был ли раньше открыт данный номер телефона для данного агентства
        $status = $db->fetch("SELECT MAX(status) as max_status, COUNT(*) as cnt FROM ".$sys_tables['calls']." WHERE id_user = ? AND num_from = ?",$agency['id_user'], $item['num_from']);
        if(!empty($status['max_status']) && $status['max_status'] == 3) $item['status'] = 3;
        $item['user_call_number'] = !empty($status['cnt']) ? $status['cnt'] + 1 : 1;
        $item['id_user'] = $agency['id_user'];
        //стоимость звонка
        if(!isset($agency['call_cost'])) $agency['call_cost'] = 450;
        $item['cost'] = $agency['call_cost'];
        unset($item['tags']);
        $exists_already = $db->fetch("SELECT id FROM ".$sys_tables['calls']." WHERE id = ?",$item['id']);
        $add_result = (!empty($exists_already)?false:$db->insertFromArray($sys_tables['calls'],$item));
        //получение длительности звонка существующей записи для проверки длительности звонка
        if(empty($add_result)) $call_info = $db->fetch("SELECT id, duration FROM ".$sys_tables['calls']." WHERE call_hash = ?",$item['call_hash']);
        //если добавилось, скачиваем запись
        if ($add_result || (!empty($item['duration']) && $item['duration']>10 && $call_info['duration']<10)){
            //id последней операции
            $inserted_id = !empty($add_result) ? $db->insert_id : $call_info['id'];
            $folder = "audio/";
            $filename = comagic_download_audio($curl,$session_key,$item['file_link'],$folder);
            //копирование во временный поддомен
            $subfolder_lk = explode('/',$filename);
            if( !is_dir( $root . '/' . $folder . $subfolder_lk[1] ) ) mkdir( $root . '/' . $folder . $subfolder_lk[1], 0777, true);
            copy($root.'/'.$filename, $root.'/'.$filename);
            
            // Initialize getID3 engine 
            $getID3 = new getID3; 
            // Analyze file and store returned data in $ThisFileInfo 
            $file_info = $getID3->analyze($root.'/'.$filename); 
            $duration = (int) ($file_info['playtime_seconds']);
            $db->querys("UPDATE ".$sys_tables['calls']." SET file_link=?, duration=?, tags=? WHERE id=?",$filename, $duration, 2, $inserted_id);
            
            //отсекаем миллисекунды, которых не должно быть в письме
            $item['call_date'] = explode('.',$item['call_date'])[0];
            //ставим пометку - короткий звонок или нет, для скрытия коротких звонков в письме агентствам
            if ($duration<=20){
                if (!empty($item['num_from']))
                    $item['hidden_num_from'] = explode('-',$item['num_from'])[0]."-XX"."-XX";
                else{
                    $item['hidden_num_from'] = false;
                    $item['num_from'] = false;
                }
            } else { 
                //финансовые операции
                $db->querys("INSERT INTO ".$sys_tables['users_finances']." SET id_user = ?, obj_type = ?, expenditure = ?",
                    $item['id_user'], 'call', $item['cost']
                );
                $db->querys("UPDATE ".$sys_tables['users']." SET balance = balance - ? WHERE id = ?", $item['cost'], $item['id_user']);

				//если баланса компании не хватает на 3 звонка
                if($agency['balance'] < $agency['call_cost']*3){
                    $agency['balance'] -= $item['cost'];
                    //уведомляем менеджера
                    if(!empty($agency['manager_email']) && Validate::isEmail($agency['manager_email'])){
                        $manager_name = explode(' ',$agency['manager_name'])[0];
                        Response::SetString('manager_name',$manager_name);
                        Response::SetArray('agency',$agency);
                        // инициализация шаблонизатора
                        $eml_tpl = new Template('manager.low_balance.html', 'cron/comagic/');
                        $html = $eml_tpl->Processing();

                        $sendpulse = new Sendpulse( );
                        $result = $sendpulse->sendMail( 
                            "Баланс компании ".$agency['title']." уменьшился до ".$agency['balance'], 
                            $html, 
                            '', 
                            array(
                                array(
                                    'name' => '',
                                    'email' => $agency['manager_email']
                                ),
                                array(
                                    'name' => '',
                                    'email' => "web@bsn.ru"
                                )
                            )
                        );


                    }
                }
				
            }
            //оповещаем менеджера и агентство о новом звонке
            if ($add_result){
                Response::SetArray('item', $item);
                Response::SetArray('agency', $agency);
                //отправка менеджеру
                $mailer = new EMailer('mail');           
                if(!empty($agency['manager_email']) && Validate::isEmail($agency['manager_email'])){
                    // инициализация шаблонизатора
                    $eml_tpl = new Template('manager.email.html', 'cron/comagic/');
                    $mailer = new EMailer('mail');
                    $html = $eml_tpl->Processing();
                    $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                    // параметры письма
                    $mailer->Body = $html;
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Новый входящий звонок с сайта BSN.ru");
                    $mailer->IsHTML(true);
                    $mailer->AddAddress($agency['manager_email']);
                    $mailer->From = 'no-reply@bsn.ru';
                    $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
                    // попытка отправить
                    $mailer->Send();
                }       
                //отправка агентству          
                $mailer = new EMailer('mail'); 
                $agency_email = !empty($agency['email_service']) && Validate::isEmail($agency['email_service']) ? $agency['email_service'] : (!empty($agency['email']) && Validate::isEmail($agency['email']) ? $agency['email'] : false)           ;
                if(!empty($agency_email)){
                    // инициализация шаблонизатора
                    $eml_tpl = new Template('agency.email.html', 'cron/comagic/');
                    $mailer = new EMailer('mail');
                    $html = $eml_tpl->Processing();
                    $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
                    // параметры письма
                    $mailer->Body = $html;
                    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Новый входящий звонок с сайта BSN.ru");
                    $mailer->IsHTML(true);
                    $mailer->AddAddress($agency_email);
                    $mailer->From = 'no-reply@bsn.ru';
                    $mailer->FromName = iconv('UTF-8', $mailer->CharSet, 'BSN.ru');
                    // попытка отправить
                    //$mailer->Send();
                }                 

            
            }

        }
    }
}
//разлогиниваемся
comagic_logout($curl,$session_key);
curl_close($curl);
?>
<?php
     /**
     * Login to comagic with login and password. Returns session_key on success and FALSE on fail
     * 
     * @param mixed $curl  - connection
     * @param mixed $login
     * @param mixed $pwd
     * @return session_key
     */
     function comagic_login(&$curl,$login,$pwd){
         curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.comagic.ru/api/login/?login='.$login.'&password='.$pwd,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array())
        ));
        $response = json_decode(curl_exec($curl));
        if ($response) return $response->data->session_key;
        else return FALSE;
     }
     /**
     * get data from comagic. Returns list on success and FALSE on fail
     * 
     * @param mixed $curl
     * @param mixed $session_key
     * @param mixed $data_code
     * @param mixed $parameters=false
     */
     function comagic_get_data(&$curl,$session_key,$data_code,$parameters=false){
        $url = Convert::ToString("https://api.comagic.ru/api/v1/$data_code/?session_key=$session_key");
         if (!empty($parameters))
             foreach ($parameters as $key=>$item)
                 $url = Convert::ToString($url."&$key=$item");
         curl_setopt_array($curl,array(
                                CURLOPT_URL => Convert::ToString($url),
                                CURLOPT_POST => FALSE
                             ));
        $response=json_decode(curl_exec($curl),TRUE);
        if ($response['success']) return $response['data'];
        else return FALSE;
     }
     /**
     * Logout from comagic
     * 
     * @param mixed $curl
     * @param mixed $session_key
     * @return boolean
     */
     function comagic_logout(&$curl,$session_key){
         $url = Convert::ToString("https://api.comagic.ru/api/logout/?session_key=$session_key");
         curl_setopt_array($curl,array(
                                        CURLOPT_URL => $url,
                                        CURLOPT_POST => FALSE
                                      ));
         $response = curl_exec($curl);
         return $response;
     }
     /**
     * Download call audiorecord from comagic
     * 
     * @param mixed $curl
     * @param mixed $session_key
     * @param mixed $url - ссылка на файл
     * @param boolean $folder=false
     */
     function comagic_download_audio(&$curl,$session_key,$url,$folder=false){
        global $errors_log;
        //имя файла
        $filename = basename($url);
        //расширение
        $filename_extensions = explode('.',$filename);
        $extension = $filename_extensions[strtolower(count($filename_extensions)-1)];
        //допустимые расширения
        $extensions = array('mp3');
        /*
        if(in_array($extension,$extensions)) $targetExt = $extension;
        else{
            $errors_log['audio'][] = $url." - разрешение файла на внешнем сервере не определено";
            return false;
        }
        */
        $targetExt = 'mp3';
        //$filename = basename($url."?session_key=$session_key");
        //если нужно, создаем соответствующую папку (первые два символа хэша)
        $new_name=md5(microtime());
        $inner_folder = substr($new_name,0,2).'/';
        if (!file_exists($folder.$inner_folder)) {
            $result = mkdir($folder.$inner_folder, 0777, true);
        }
        if(strstr($url,'http:')=='') $url = 'http:'.$url;
        //имя сохраняемого файла
        $fullname = $folder.$inner_folder.$new_name.'.' . $targetExt;
        $fp = fopen($fullname, "w+");
        curl_setopt_array($curl,array(
                                      CURLOPT_URL => $url,  
                                      CURLOPT_RETURNTRANSFER => 1,
                                      CURLOPT_FILE => $fp,
                                      CURLOPT_HEADER => 0,
                                      CURLOPT_FOLLOWLOCATION => 1,
                                      CURLOPT_TIMEOUT => 1
                                     ));
        $save = curl_exec($curl);
        fclose($fp);
        return  $fullname;
    }
?>