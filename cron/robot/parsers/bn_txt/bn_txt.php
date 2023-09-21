#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../../../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
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
$error_log = ROOT_PATH.'/cron/robot/parsers/bn_txt/error.log';
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
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');
include('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
include('includes/class.moderation.php'); // Moderation (процедура модерации)
include('includes/class.robot.php');      // Robot (конвертация обработанных строк/нодов файлов в поля объектов недвижимости)
include('cron/robot/class.xml2array.php');  // конвертация xml в array

//логирование выгрузок xml-я
$log = array();
// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$rent_titles = array(1=>'аренда', 2=>'продажа'); //типы сделок
    
//папка с txt файлами 
$dir = ROOT_PATH."/cron/robot/files/bn_txt/";
$dh = opendir($dir);
$mail_text = '';
while($filename = readdir($dh))
{
    
    if($filename!='.' && $filename!='..')
    {
        exec("chmod 777 ".$dir.$filename);
        
        $mail_text .= 'Файл:'.$dir.$filename.'<br />';  
        $errors_log='';  // ошибки
        if(preg_match("#\.(all|ard|kn|ned|zd)#is",$filename)){ //обработка файла
            //определение рынка недвижимости по типу файла
            $file_type = preg_replace("#([a-z]{1,5})?\.([a-z]{1,3})(\d+)?#is",'\2',$filename);

            //счетчик объектов
            $counter = array('live_sell'=>0,            'live_rent'=>0,             'commercial_sell'=>0,            'commercial_rent'=>0,          'build'=>0,            'country_sell'=>0,            'country_rent'=>0, 
                             'live_sell_promo'=>0,      'live_rent_promo'=>0,       'commercial_sell_promo'=>0,      'commercial_rent_promo'=>0,    'build_promo'=>0,      'country_sell_promo'=>0,      'country_rent_promo'=>0, 
                             'live_sell_premium'=>0,    'live_rent_premium'=>0,     'commercial_sell_premium'=>0,    'commercial_rent_premium'=>0,  'build_premium'=>0,    'country_sell_premium'=>0,    'country_rent_premium'=>0, 
                             'live_sell_elite'=>0,      'live_rent_elite'=>0,       'commercial_sell_elite'=>0,      'commercial_rent_elite'=>0,    'build_elite'=>0,      'country_sell_elite'=>0,      'country_rent_elite'=>0,
                             'live_sell_merged'=>0,      'live_rent_merged'=>0,       'commercial_sell_merged'=>0,      'commercial_rent_merged'=>0,    'build_merged'=>0,      'country_sell_merged'=>0,      'country_rent_merged'=>0,
                             'total'=>0
            );
            //определение агентства
            $rows = file($dir.$filename);
            //информация об агентстве
            $id_user = Convert::ToInteger($rows[1]);
            $agency = $db->fetch("SELECT ".$sys_tables['agencies'].".*, 
                                         ".$sys_tables['managers'].".`email`
                                  FROM ".$sys_tables['agencies']."
                                  RIGHT JOIN ".$sys_tables['users']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency 
                                  LEFT JOIN ".$sys_tables['managers']." ON ".$sys_tables['managers'].".id = ".$sys_tables['agencies'].".id_manager 
                                  LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['users'].".id_tarif = ".$sys_tables['tarifs'].".id
                                  WHERE ".$sys_tables['users'].".`id` = ?",
                                  $id_user) ;            
            if(empty($agency)) $mail_text.="Ошибка авторизации";
            elseif(empty($agency['id_tarif'])) $mail_text .= "Объекты агентства ".$agency['title']." не выгрузились, потому что для агентства не установлен тариф ".$agency['tarif_title'];
            else
            {
                //текст письма
                $mail_text .= "Агентство <i>".$agency['title']."</i><br />";

                //определение кодировки
                //список возможных кодировок
                $charsets = array("ibm-866","cp866","KOI8-R","WINDOWS-1251","CP1251","KOI8-RU", "ISO8859-5");
                $converted_charset = $charset_3 = false;
                for($i=6; $i>=2; $i=$i-2){
                    if(!empty($converted_charset)) break;
                    if(!empty($rows[$i]) && strlen($rows[$i])>40){
                        if(empty($converted_charset)) {
                            foreach($charsets as $charset){
                                 $converted_row = iconv($charset,"UTF-8//TRANSLIT",$rows[$i]);
                                 if(preg_match("#([а-я]{7,20})#is",$converted_row)){ $converted_charset = $charset; break; }
                            }
                        }
                    }
                }     

                if(empty($converted_charset)) {
                    if (count($rows)>1)
                        $mail_text .= "<b>Файл пуст<br /></b>";
                    else
                        $mail_text .= "<b>Не определена кодировка файла<br /></b>";
                }
                else{
                    
                    //простановка в архив объектов по заданному типу недвижимости от этого агентства
                    switch($file_type){
                        case 'all':    
                        case 'ard': 
                            $estate_type='live';
                            break;   
                        case 'kn': 
                            $estate_type='commercial';
                            break;   
                        case 'zd': 
                            $estate_type='country';
                            break;   
                        case 'ned': 
                            $estate_type='build';
                            break;   
                    }
                    $db->querys("UPDATE estate.".$estate_type." SET `published` = 2, `date_change` = NOW() WHERE id_user = ? AND info_source=5 AND published=1 AND elite!=1",$id_user);
                    //обработка полученных значений
                    foreach($rows as $key=>$values){
                        if($key>1){ //0 и 1 - справочная инфа в файлах
                            if(strlen($values)>40){ //проверка на незаполеннные поля
                                $overall_time_counter = microtime(true);
                                $robot = new BNTxtRobot($id_user);
                                $values = iconv($converted_charset,"UTF-8//TRANSLIT",$values);
                                echo $values;
                                $fields = $robot->getConvertedFields($values,$file_type, $estate_type, $agency); 
                                //проверка лимита
                                $deal_type = $robot->estate_type == 'build' ? '' : ($fields['rent'] == 1 ? '_rent' : '_sell');
                                $check_limit = ($robot->estate_type.$deal_type == 'live_rent' && $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects']) || ($robot->estate_type.$deal_type != 'live_rent' && ($agency['id_tarif']==1 || $agency['id_tarif']==7 || $counter[$robot->estate_type.$deal_type] < $agency[$robot->estate_type.$deal_type.'_objects'])); 
                                if(!empty($fields)) {
                                    //счетчик сверх лимита
                                    if(!$check_limit) {
                                        if(empty($errors_log['over_limit'])) $errors_log['over_limit'] = 0;
                                        $errors_log['over_limit']++;
                                        empty($counter[$robot->estate_type.$deal_type.'_over_limit']) ? $counter[$robot->estate_type.$deal_type.'_over_limit'] = 1 : $counter[$robot->estate_type.$deal_type.'_over_limit']++;
                                    }
                                }
                                
                                if(!empty($fields) && $check_limit){ //отсечение лимитов
                                    //сумма всех итераций
                                    ++$counter['total'];
                                    //сохранение варианта
                                    $fields['date_in']= date('Y-m-d H:i:s');
                                    $res = $db->insertFromArray($sys_tables[($robot->estate_type).'_new'], $fields, 'id');
                                    $inserted_id = $db->insert_id;
                                    
                                    ///считаем и записываем вес объекта (в таблицу estate_type или estate_type_new):
                                    $prefix="";
                                    switch($robot->estate_type){
                                        case 'live':$item_weight = new Estate(TYPE_ESTATE_LIVE);break;
                                        case 'build':$item_weight = new Estate(TYPE_ESTATE_BUILD);break;
                                        case 'country':$item_weight = new Estate(TYPE_ESTATE_COUNTRY);break;
                                        case 'commercial':$item_weight = new Estate(TYPE_ESTATE_COMMERCIAL);break;
                                    }
                                    $item_weight = $item_weight->getItemWeight($inserted_id,$robot->estate_type);
                                    $res_weight = $db->querys("UPDATE ".$sys_tables[$robot->estate_type.$prefix]." SET weight=? WHERE id=?",$item_weight,$inserted_id);
                                    ///
                                    
                                    //модерация объектов
                                    $moderate = new Moderation($robot->estate_type,$inserted_id);
                                    $moderate->checkObject();
                                    //счетчик кол-ва вариантов
                                     if($moderate->moderated_status==2){
                                         $cost = (!empty($fields['cost'])?$fields['cost'].' руб. ':'').(!empty($fields['cost2meter'])?'('.$fields['cost2meter'].' руб/м)':'');
                                         $errors_log['post_moderate'][] = array(($moderate->moderated_status!=4?$cost.', '.$rent_titles[$fields['rent']]:$fields['txt_addr']),$moderate->moderated_status, $values);
                                     } elseif($moderate->merged == true) $counter[$robot->estate_type.$deal_type.'_merged']++;
                                     else $counter[$robot->estate_type.$deal_type]++; 
                                    echo " : ";
                                    
                                } // end of : if(!empty($fields)){    
                            }  // end of:if(strlen($values)<40) break; //проверка на незаполеннные поля;
                        } //end of: if($key>1){ //0 и 1 - справочная инфа в файлах
                    } // end of : foreach($rows as $key=>$values){
                } // if(empty($converted_charset)) // не определена кодировка файла
    
            } //end of : if($id_user<1) 
            
        } else { // файл не распознан
            $mail_text .= 'Файл '.$filename.' не распознан.';
        }  //end of: if(preg_match("#\.(all|ard|kn|ned|zd)#is")){ 
        if(!empty($counter)){
            $total = $counter['total'];
            $mail_text .= "Обработано всего объектов:".$total;
            $mail_text .= "<br />Добавлено объектов: ".($counter['live_sell']+$counter['live_rent']+$counter['build']+$counter['country_sell']+$counter['commercial_sell']+$counter['country_rent']+$counter['commercial_rent'])."<br />
            - жилая (продажа): ".$counter['live_sell'].($counter['live_sell_promo']>0? ", промо: ".$counter['live_sell_promo']:"").($counter['live_sell_premium']>0? ", премиум: ".$counter['live_sell_premium']:"").($counter['live_sell_elite']>0? ", элитных: ".$counter['live_sell_elite']:"").(!empty($counter['live_sell_merged'])?' (склеено похожих - '.$counter['live_sell_merged'].')':'')."<br />
            - жилая (аренда): ".$counter['live_rent'].($counter['live_rent_promo']>0? ", промо: ".$counter['live_rent_promo']:"").($counter['live_rent_premium']>0? ", премиум: ".$counter['live_rent_premium']:"").($counter['live_rent_elite']>0? ", элитных: ".$counter['live_rent_elite']:"").(!empty($counter['live_rent_merged'])?' (склеено похожих - '.$counter['live_rent_merged'].')':'')."<br />
            - стройка: ".$counter['build'].($counter['build_promo']>0? ", промо: ".$counter['build_promo']:"").($counter['build_premium']>0? ", премиум: ".$counter['build_premium']:"").($counter['build_elite']>0? ", элитных: ".$counter['build_elite']:"").(!empty($counter['live_merged'])?' (склеено похожих - '.$counter['live_merged'].')':'')."<br />
            - коммерческая (продажа): ".$counter['commercial_sell'].($counter['commercial_sell_promo']>0? ", промо: ".$counter['commercial_sell_promo']:"").($counter['commercial_sell_premium']>0? ", премиум: ".$counter['commercial_sell_premium']:"").($counter['commercial_sell_elite']>0? ", элитных: ".$counter['commercial_sell_elite']:"").(!empty($counter['commercial_sell_merged'])?' (склеено похожих - '.$counter['commercial_sell_merged'].')':'')."<br />
            - коммерческая (аренда): ".$counter['commercial_rent'].($counter['commercial_rent_promo']>0? ", промо: ".$counter['commercial_rent_promo']:"").($counter['commercial_rent_premium']>0? ", премиум: ".$counter['commercial_rent_premium']:"").($counter['commercial_rent_elite']>0? ", элитных: ".$counter['commercial_rent_elite']:"").(!empty($counter['commercial_rent_merged'])?' (склеено похожих - '.$counter['commercial_rent_merged'].')':'')."<br />
            - загородная (продажа): ".$counter['country_sell'].($counter['country_sell_promo']>0? ", промо: ".$counter['country_sell_promo']:"").($counter['country_sell_premium']>0? ", премиум: ".$counter['country_sell_premium']:"").($counter['country_sell_elite']>0? ", элитных: ".$counter['country_sell_elite']:"").(!empty($counter['country_sell_merged'])?' (склеено похожих - '.$counter['country_sell_merged'].')':'')."<br />
            - загородная (аренда): ".$counter['country_rent'].($counter['country_rent_promo']>0? ", промо: ".$counter['country_rent_promo']:"").($counter['country_rent_premium']>0? ", премиум: ".$counter['country_rent_premium']:"").($counter['country_rent_elite']>0? ", элитных: ".$counter['country_rent_elite']:"").(!empty($counter['country_rent_merged'])?' (склеено похожих - '.$counter['country_rent_merged'].')':'')."<br /><br />
            ";
        }
        //логирование ошибок
        if(!empty($errors_log)){
            $mail_text .= "<br /><br />При обработке файла возникли следующие ошибки:";
           
            if(!empty($errors_log['over_limit'])) $mail_text .= "<br /><br />Объекты сверх установленного лимита (не выгружены):".$errors_log['over_limit']; 
            $mail_text .= "
            ".(!empty($counter['live_sell_over_limit'])?"- жилая (продажа): ".$counter['live_sell_over_limit']."<br />":"")."
            ".(!empty($counter['live_rent_over_limit'])?"- жилая (аренда): ".$counter['live_rent_over_limit']."<br />":"")."
            ".(!empty($counter['build_over_limit'])?"- стройка: ".$counter['build_over_limit']."<br />":"")."
            ".(!empty($counter['commercial_sell_over_limit'])?"- коммерческая (продажа): ".$counter['commercial_sell_over_limit']."<br />":"")."
            ".(!empty($counter['commercial_rent_over_limit'])?"- коммерческая (аренда): ".$counter['commercial_rent_over_limit']."<br />":"")."
            ".(!empty($counter['country_sell_over_limit'])?"- загородная (продажа): ".$counter['country_sell_over_limit']."<br />":"")."
            ".(!empty($counter['country_rent_over_limit'])?"- загородная (аренда): ".$counter['country_rent_over_limit']."<br />":"")."
            ";

            if(!empty($errors_log['post_moderate']))  {
                $mail_text .= "<br /><br /><strong>Не прошли модерацию: ".count($errors_log['post_moderate'])."</strong>";
                foreach($errors_log['post_moderate'] as $k=>$moderation) {
                    $mail_text .= "<br />строка: <strong>".$moderation[2].'</strong><br />статус: <i>'.$moderate_statuses[$moderation[1]]."</i>, значение: <strong>".$moderation[0]."</strong><br />";
                }
            }    

            if(!empty($errors_log['moderation']))  {
                $mail_text .= "<br /><br /><strong>Неправильно заполнены строки: ".count($errors_log['moderation'])."</strong>";
                foreach($errors_log['moderation'] as $k=>$moderation) $mail_text .= "<br />строка: <strong>".$k.'</strong>, статус: <i>'.$moderation."</i>";;
            }    
        }
        $mail_text .= "<br /><br />";
        unlink($dir.$filename);
    } // end of: if($filename!='.' && $filename!='..')
}
//если были ошибки выполнения скрипта
if(filesize($error_log)>10){
    $error_log_text = '<br><br>Логи ошибок <br><font size="1">';
    $error_log_text .= fread(fopen($error_log, "r"), filesize($error_log));
    $error_log_text .= '</font>';
} else $error_log_text = "";
echo $mail_text;
$mail_text= iconv("UTF-8", "CP1251//TRANSLIT", $mail_text);
$error_log_text= iconv("UTF-8", "CP1251//TRANSLIT", $error_log_text);
if($mail_text!=''){//отсылка программеру со всеми типами ошибок
    $mailer = new EMailer('mail');
    
    // перевод письма в кодировку мейлера
    $html = $mail_text.$error_log_text;
    // параметры письма
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Обработка формата BN_TXT. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
	if(!empty($agency['email_service']) && Validate::isEmail($agency['email_service'])) $mailer->AddAddress($agency['email_service']);
    $mailer->AddAddress('hitty@bsn.ru');
    $mailer->From = 'bsntxt@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Обработка BN TXT');
    // попытка отправить
    $mailer->Send();       


    $mailer = new EMailer('mail');

    // перевод письма в кодировку мейлера
    $html = $mail_text;
    // параметры письма
    $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Обработка формата BN_TXT. '.date('Y-m-d H:i:s'));
    $mailer->Body = $html;
    $mailer->AltBody = strip_tags($html);
    $mailer->IsHTML(true);
    $mailer->AddAddress('scald@bsn.ru');
    $mailer->AddAddress('hitty@bsn.ru');
    if(!empty($agency['email']) )$mailer->AddAddress($agency['email']);     //отправка письма ответственному менеджеру
    if(empty($agency['id_tarif']) && !empty($agency['email_service']) && Validate::isEmail($agency['email_service'])) $mailer->AddAddress($agency['email_service']);     //отправка письма агентству
    $mailer->From = 'bsntxt@bsn.ru';
    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Обработка BN TXT');
    // попытка отправить
    $mailer->Send();
}
?>