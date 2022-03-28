<?
set_time_limit(100000);
require_once('includes/lib.errorhandler.php');
register_shutdown_function('newFatalCatcher');
//not loged query's
$query_not_log = true;
 // check if script is running
function is_running($proc_string, $silent = true)
{
    $proc_string = str_replace('.', '\.', $proc_string);
    if(!$silent){
        echo 'path:'.getcwd()."\n";
        echo "proc_string:".$proc_string."\n";
    }
    exec("ps ax -o '%a'| grep $proc_string | grep -v grep | grep -v '/bin/sh -c'", $rr);
    return (sizeof($rr) > 1);
}
/* Проставляет агентствам актуальное количество вариантов согласно кол-ву объектов по каждому рынку при выбранном тарифном плане */
function updateAgenciesByPackets(){
   $warn_sql="
    SELECT title, id, id_tarif, max_val, objects, email, manager_email, dbtable, SUM(counts) as counts
    FROM
    (
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   o.cnt as max_val, 
                   a.live_sell_objects as objects,
                   a.email,
                   m.email as manager_email,
                   'live' as dbtable,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN information.agencies_packets o ON o.id = a.id_tarif
            LEFT JOIN information.managers m ON m.id = a.id_manager
            LEFT JOIN estate.live l ON l.id_user = u.id
            WHERE  l.info_source != 4 AND l.info_source != 6 AND l.published = 1  AND elite=2  AND rent = 2 AND u.id_tarif = 0 GROUP BY u.id
        )
        UNION    
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   o.cnt as max_val, 
                   a.build_objects as objects,
                   a.email,
                   m.email as manager_email,
                  'build' as dbtable,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN information.agencies_packets o ON o.id = a.id_tarif
            LEFT JOIN information.managers m ON m.id = a.id_manager
            LEFT JOIN estate.build l ON l.id_user = u.id
            WHERE l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2 AND u.id_tarif = 0 GROUP BY u.id
        )
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   o.cnt as max_val, 
                   a.commercial_sell_objects as objects,
                   a.email,
                   m.email as manager_email,
                   'commercial' as dbtable,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN information.agencies_packets o ON o.id = a.id_tarif
            LEFT JOIN information.managers m ON m.id = a.id_manager
            LEFT JOIN estate.commercial l ON l.id_user = u.id
            WHERE  l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2 AND rent = 2 AND u.id_tarif = 0 GROUP BY u.id
        )
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   o.cnt as max_val, 
                   a.commercial_rent_objects as objects,
                   a.email,
                   m.email as manager_email,
                   'commercial' as dbtable,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN information.agencies_packets o ON o.id = a.id_tarif
            LEFT JOIN information.managers m ON m.id = a.id_manager
            LEFT JOIN estate.commercial l ON l.id_user = u.id
            WHERE  l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2 AND rent = 1 AND u.id_tarif = 0 GROUP BY u.id
        )
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   o.cnt as max_val, 
                   a.country_sell_objects as objects,
                   a.email,
                   m.email as manager_email,
                   'country' as dbtable,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN information.agencies_packets o ON o.id = a.id_tarif
            LEFT JOIN information.managers m ON m.id = a.id_manager
            LEFT JOIN estate.country l ON l.id_user = u.id
            WHERE  l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2 AND rent = 2 AND u.id_tarif = 0 GROUP BY u.id
        )                   
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   o.cnt as max_val, 
                   a.country_rent_objects as objects,
                   a.email,
                   m.email as manager_email,
                   'country' as dbtable,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN information.agencies_packets o ON o.id = a.id_tarif
            LEFT JOIN information.managers m ON m.id = a.id_manager
            LEFT JOIN estate.country l ON l.id_user = u.id
            WHERE  l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2 AND rent = 1 AND u.id_tarif = 0 GROUP BY u.id
        )                   
    ) as t
    WHERE ((t.id_tarif > 0 AND t.id_tarif < 7 AND  t.counts > t.objects ) OR (t.counts > t.objects AND t.objects > 0) )
    GROUP BY title, dbtable
    ";


    $estates = array('live'=>'Жилая (продажа)','build'=>'Строящаяся','country'=>'Загородная','commercial'=>'Коммерческая');
    global $db;

    //$warn_res = $db->fetchall($warn_sql);
    if(!empty($warn_res)){
        foreach($warn_res as $k=>$row){
            $mail_text = "У агентства ".$row['title']." проставлено в админке ".$row['objects']." объект, тип недвижимости ".$estates[($row['dbtable'])]."., а прислали они нам ".$row['counts']." объект. </body></html>";
            $mailer = new EMailer('mail');
            // перевод письма в кодировку мейлера
            $html = iconv('UTF-8', $mailer->CharSet, $mail_text);
            // параметры письма
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Превышение агентством количества выгруженных вариантов.'.date('Y-m-d H:i:s'));
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags($html);
            $mailer->IsHTML(true);
            $mailer->AddAddress(!empty($row['manager_email'])?$row['manager_email']:'hitty@bsn.ru');
            $mailer->AddAddress('web@bsn.ru');
            $mailer->From = 'wmailer@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Робот bsn.ru');
            // попытка отправить
            //$mailer->Send();        
        }
    }
    

    $sql = "
    SELECT title, id, id_tarif, objects, dbtable, rent_type, counts
    FROM
    (
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   a.live_sell_objects as objects,
                   'live' as dbtable,
                   'sell' as rent_type,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN estate.live l ON l.id_user = u.id 
            WHERE l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2 AND rent = 2 AND u.id_tarif = 0 GROUP BY u.id
        )
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   a.live_rent_objects as objects,
                   'live' as dbtable,
                   'rent' as rent_type,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN estate.live l ON l.id_user = u.id 
            WHERE l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2 AND rent = 1 AND u.id_tarif = 0 GROUP BY u.id
        )
        UNION        
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   a.build_objects as objects,
                   'build' as dbtable,
                   'sell' as rent_type,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN estate.build l ON l.id_user = u.id 
            WHERE  l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2 AND u.id_tarif = 0  GROUP BY u.id
        )
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   a.commercial_sell_objects as objects,
                   'commercial' as dbtable,
                   'sell' as rent_type,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN estate.commercial l ON l.id_user = u.id 
            WHERE l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2  AND rent = 2 AND u.id_tarif = 0  GROUP BY u.id
        )
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   a.commercial_rent_objects as objects,
                   'commercial' as dbtable,
                   'rent' as rent_type,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN estate.commercial l ON l.id_user = u.id 
            WHERE l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2  AND rent = 1 AND u.id_tarif = 0  GROUP BY u.id
        )
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   a.country_sell_objects as objects,
                   'country' as dbtable,
                   'sell' as rent_type,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN estate.country l ON l.id_user = u.id 
            WHERE  l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2  AND rent = 2 AND u.id_tarif = 0  GROUP BY u.id
        )
        UNION
        (
            SELECT a.title,
                   u.id as id,
                   a.id_tarif as id_tarif,
                   a.country_rent_objects as objects,
                   'country' as dbtable,
                   'rent' as rent_type,
                   COUNT(l.id) as counts
            FROM common.agencies a
            LEFT JOIN common.users u ON u.id_agency = a.id
            LEFT JOIN estate.country l ON l.id_user = u.id 
            WHERE  l.info_source != 4 AND l.info_source != 6 AND l.published = 1 AND elite=2  AND rent = 1 AND u.id_tarif = 0 GROUP BY u.id
        )
    ) as t
    WHERE ((t.id_tarif > 0 AND t.id_tarif < 7 AND  t.counts > t.objects ) OR (t.counts > t.objects AND t.objects > 0) )
    ";
    $res = $db->query($sql) or die($db->error);
    while($row = $res->fetch_array(MYSQL_ASSOC)) {
        if($row['id_tarif'] != 7 || ($row['rent_type'] == 'rent' && $row['dbtable'] == 'live')){
            if($row['rent_type']=='sell') $where = " AND rent = 2 ";
            elseif($row['rent_type']=='rent') $where = " AND rent = 1 ";
            else $where = "";
            $db->query("UPDATE `estate`.".$row['dbtable']." SET `published` = 2 WHERE `elite`=2 AND `id_user` = ".$row['id']." AND published = 1 AND info_source != 4 AND info_source != 6 ".$where." ORDER BY `id` LIMIT ".($row['counts']-$row['objects'])) or die($db->error);
        }
    }
}

/* обновление статусов объектов от Н-Маркет (сумма НГ и Н-Маркет <=300) */
function updateNMarketEliteObjects(){
    global $db;
    //определение кол-ва элитных объектов от НГ  и Н-Маркета
    $elite = $db->fetch("SELECT SUM(ng_cnt) as ng_cnt, SUM(nmarket_cnt) as nmarket_cnt FROM (
                    (SELECT count(*) as ng_cnt, 0 as nmarket_cnt FROM estate.live WHERE published=1 AND info_source=4 AND status>2)
                    UNION
                    (SELECT count(*) as ng_cnt, 0 as nmarket_cnt FROM estate.build WHERE published=1 AND info_source=4 AND status>2)
                    UNION
                    (SELECT count(*) as ng_cnt, 0 as nmarket_cnt FROM estate.commercial WHERE published=1 AND info_source=4 AND status>2)
                    UNION
                    (SELECT count(*) as ng_cnt, 0 as nmarket_cnt FROM estate.country WHERE published=1 AND info_source=4 AND status>2)
                    UNION
                    (SELECT 0 as ng_cnt, count(*) as nmarket_cnt FROM estate.live WHERE published=1 AND id_user=26821 AND status>2)
                    UNION
                    (SELECT 0 as ng_cnt, count(*) as nmarket_cnt FROM estate.build WHERE published=1 AND id_user=26821 AND status>2)
                    UNION
                    (SELECT 0 as ng_cnt, count(*) as nmarket_cnt FROM estate.commercial WHERE published=1 AND id_user=26821 AND status>2)
                    UNION
                    (SELECT 0 as ng_cnt, count(*) as nmarket_cnt FROM estate.country WHERE published=1 AND id_user=26821 AND status>2)
    ) as a");
    if(!empty($elite) && ($elite['ng_cnt']+$elite['nmarket_cnt'])>300){
        
        //элитные объекты от Н-Маркета
        $nmarket_list = $db->fetchall("SELECT estate_type,id FROM (
                        (SELECT 'country' as estate_type, id FROM estate.country WHERE published=1 AND id_user=26821 AND status>2)
                        UNION
                        (SELECT 'commercial' as estate_type, id FROM estate.commercial WHERE published=1 AND id_user=26821 AND status>2)
                        UNION
                        (SELECT 'live' as estate_type, id FROM estate.live WHERE published=1 AND id_user=26821 AND status>2)
                        UNION
                       (SELECT 'build' as estate_type, id FROM estate.build WHERE published=1 AND id_user=26821 AND status>2)
        ) as a LIMIT ".($elite['ng_cnt']+$elite['nmarket_cnt']-300));
        if(!empty($nmarket_list)){
            foreach($nmarket_list as $k=>$value) $db->query("UPDATE estate.".$value['estate_type']." SET status=2 WHERE `id` = ?",$value['id']);
        }
    }
}

/* скачивание xml файла и проверка новизну файла (hash) */
function downloadXmlFile($format=false, $agency_title, $link, $id_user, $db_check=true, $folder = false, $return_array = false, $curl = true){
    global $db;
    $date_check = $db->fetch("SELECT `id` FROM `service`.`xml_imported_hash` WHERE `agency_title` = ? AND `date` = CURDATE()", $agency_title);
    if(!empty($date_check) && !empty($db_check)) return 'Файл '.$link.' агентства '.$agency_title.' сегодня уже выгружался';
    else{
        $folder = (ROOT_PATH).(empty($folder) ? "/cron/robot/files/".$format."_xml/" : "/".$folder."/");
        $filename = $id_user."_".createCHPUTitle($agency_title).".xml";
        if(file_exists($folder.$filename)) unlink($folder.$filename);
        downloadFile($folder.$filename,$link, $curl);
        if(!file_exists($folder.$filename)) return false;
        exec("chmod 777 ".$folder.$filename);
        $hash = md5_file($folder.$filename);
        if(!file_exists($folder.$filename) || filesize($folder.$filename)<10) {
            if(file_exists($folder.$filename)) unlink($folder.$filename);
            $db->query("UPDATE `service`.`xml_imported_hash` SET `hash` = '".$hash."', `date` = CURDATE() WHERE `agency_title` = '".$agency_title."' ");
            return 'Файл '.$link.' не может быть скачан';
        }
        $item = $db->fetch("SELECT `id`, `date` FROM `service`.`xml_imported_hash` WHERE `agency_title` = ? AND `hash` = ?", $agency_title, $hash);     
        if(!empty($db_check)){
            if(!empty($item)){
                if(file_exists($folder.$filename)) unlink($folder.$filename);
                return 'Файл '.$link.' агентства '.$agency_title.' не изменился с последней выгрузки - '.$item['date'].' : '.$db_check.'-';
            }
        } 
        $db->query("UPDATE `service`.`xml_imported_hash` SET `hash` = '".$hash."', `date` = CURDATE() WHERE `agency_title` = '".$agency_title."' ");
    }
    if(empty($return_array)) return 'Файл '.$link.' агентства '.$agency_title.' выгружен. Проводится парсинг.';
    else return array($folder.$filename, 'Файл '.$link.' агентства '.$agency_title.' выгружен. Проводится парсинг.');
}
/* скачивание xml файла по ФТП проверка новизну файла (hash) */
function downloadFtpXmlFile($format,$ftp_server,$ftp_login,$ftp_pass,$filename,$agency_title=false,$id_user=false){
    global $db;
    $date_check = $db->fetch("SELECT `id` FROM `service`.`xml_imported_hash` WHERE `agency_title` = ? AND `date` = CURDATE()", $agency_title);
    if(!empty($date_check)) { return 'Файл '.$filename.' агентства '.$agency_title.' сегодня уже выгружался';}
    else{

                
        $folder = ROOT_PATH."/cron/robot/files/".$format."_xml/";
        if(!empty($id_user) || !empty($agency_title)) $fullname = $folder.$id_user."_".$agency_title.".xml";
        else $fullname = $folder.$filename;    
        
        if(file_exists($fullname)) unlink($fullname);
        
        $log = '';
        /*Вход по фтп на основной сервер */
        $conn_id = ftp_connect($ftp_server) or die("Не удалось установить соединение с $ftp_server");

        // попытка входа
        if (@ftp_login($conn_id, $ftp_login, $ftp_pass)) {
            $log .= "Произведен вход на ".$ftp_server." под именем ".$ftp_login."<br />";
        } else {
            $log .= "Не удалось войти на ".$ftp_server." под именем ".$ftp_login."<br />";
        }
        

        $ftp_success = 0;
        // turn passive mode on
        ftp_pasv($conn_id, true);

        // попытка скачать $filename и сохранить в $fullname
        if (ftp_get($conn_id, $fullname, $filename, FTP_BINARY)) {
            $log .=  "Произведена запись в $fullname<br />";
            $ftp_success = 1;
        } else  $log .=  "Не удалось завершить операцию<br />";
        // закрытие соединения
        ftp_close($conn_id);
        if(!file_exists($fullname)) $log .= 'Файл '.$fullname.' не обнаружен на сервере<br />';
        else{
            chmod($fullname,"777");
            if($ftp_success == 1 && !empty($agency_title) && !empty($id_user)){
                $log .= 'Файл '.$filename.' агентства '.$agency_title.' успешно скачан и ушел в обработку<br />';
                $hash = md5_file($fullname);
                $item = $db->fetch("SELECT `id` FROM `service`.`xml_imported_hash` WHERE `agency_title` = ? AND `hash` = ?", $agency_title, $hash);     
                if(empty($item)) {
                    $db->query("UPDATE `service`.`xml_imported_hash` SET `hash` = '".$hash."', `date` = CURDATE() WHERE `agency_title` = '".$agency_title."' ");
                    $log .= 'Файл '.$filename.' агентства '.$agency_title.' ушел в обработку<br />';    
                } else {
                    unlink($folder.$filename);
                    $log .= 'Файл '.$filename.' агентства '.$agency_title.' не изменился с последней выгрузкой';
                }
            }
        } 
    }    
    
}
//загрузка любого типа файла
function downloadFile($fullname, $link, $curl = true){
    $file = '';
    if(!empty($curl)){
        $ref = "https://www.bsn.ru/";
        $curl = curl_init(); 
        curl_setopt($curl,CURLOPT_URL,$link); 
        curl_setopt($curl, CURLOPT_COOKIESESSION, TRUE); 
        curl_setopt($curl, CURLOPT_COOKIEFILE, "cookiefile"); 
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true); 
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($curl,CURLOPT_REFERER, $ref); 
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,30); 
        curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($curl, CURLOPT_STDERR, $verbose);
        
        $file = curl_exec($curl); 
        
        if ($file === FALSE) {
            printf("cUrl error (#%d): %s<br>\n", curl_errno($curl),
                   htmlspecialchars(curl_error($curl)));
        }

        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

    }
    
    if(strlen($file)>1000) {
        file_put_contents($fullname,$file);
        exec("chmod 777 ".$fullname);
        return true;
    } else {
        exec("wget \"$link\" --output-document=".$fullname);
        if(file_exists($fullname)) {
            exec("chmod 777 ".$fullname);
            return true;
        } else {
            exec('curl -O "'.$link.'"');
            if(file_exists($fullname)) {
                exec("chmod 777 ".$fullname);
                return true;
            } 
        } 
    }
    return false;
}

function makeAddressAndContacts($item)   {
    global $xmlItem, $db, $sys_tables;
    $area = $city = $place = $street = false;
    //определение района области
    if(!empty($item['id_area'])){
        $area = $db->fetch("SELECT offname as title 
                              FROM ".$sys_tables['geodata']." 
                              WHERE a_level=? AND id_region=? AND id_area=?",
                              2, $item['id_region'], $item['id_area']);
    }
    //определение города
    if(!empty($item['id_city'])){
        $city = $db->fetch("SELECT offname as title 
                              FROM ".$sys_tables['geodata']." 
                              WHERE a_level=? AND id_region=? AND id_area=? AND id_city=?",
                              3, $item['id_region'], $item['id_area'], $item['id_city']);
    }
    //определение части города
    if(!empty($item['id_place'])){
        $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                              FROM ".$sys_tables['geodata']." 
                              WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                              4, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place']);
    }
    //определение названия улицы
    if(!empty($item['id_street'])){
        $street = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                              FROM ".$sys_tables['geodata']." 
                              WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                              5, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']);
    }
    $xmlItem->append('location', '',1); // * обязательное поле
        $xmlItem->append('country', 'Россия',2); // * обязательное поле
        if($item['id_region'] == 78){
            $xmlItem->append('locality-name','Санкт-Петербург',2);
            if(!empty($item['district_title'])) $xmlItem->append('sub-locality-name',$item['district_title'],2);

            if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : '') ,2);
            elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],2);     
            
        }
        elseif($item['id_region'] == 47){
            $xmlItem->append('region', 'Ленинградская область',2); 
            if(!empty($area['title'])) $xmlItem->append('district',$area['title'].' район',2);
            if(!empty($city['title'])) $xmlItem->append('locality-name',$city['title'],2);
            elseif(!empty($place['title'])) $xmlItem->append('locality-name',$place['title'],2);

            if(!empty($street['title'])) $xmlItem->append('address',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', '.$item['house'].', '.$item['corp'] : ', '.$item['house'] ) : '') ,2);
            elseif($item['txt_addr']!='') $xmlItem->append('address',$item['txt_addr'],2);
        }
        if($item['lat']>0 && $item['lng']>0){
            $xmlItem->append('latitude', $item['lat'],2);
            $xmlItem->append('longitude', $item['lng'],2);
        }
        if(!empty($item['id_subway'])) {
            $xmlItem->append('metro','',2);
            $xmlItem->append('name',$item['subway_title'],3);
            if(!empty($item['id_way_type'])){
                if($item['id_way_type']==2) $xmlItem->append('time-on-foot',$item['id_way_type'],3);
                if($item['id_way_type']==3) $xmlItem->append('time-on-transport',$item['id_way_type'],3);
            }
        }
        if(!empty($item['railstation'])) $xmlItem->append('railway-station', $item['railstation'],2);
        $xmlItem->append('sales-agent', '', 1); // * обязательное поле
            if(empty($item['agency_title']) || $item['agency_title'] == 'владелец'){
                //формирование телефона пользователя
                $real_phones = Convert::ToPhone($item['seller_phone'],'812','+7');
                foreach($real_phones as $real_phone){
                    $xmlItem->append('phone', $real_phone,2); // * обязательное поле    
                }
                if($item['seller_name']!='')  $xmlItem->append('name', $item['seller_name'],2); 
                $xmlItem->append('category', 'владелец',2);
            }
            elseif(!empty($item['agency_title'])){
                if(!empty($item['seller_phone'])) $xmlItem->append('phone', $item['seller_phone'],2); // * обязательное поле
                if($item['agency_phone_1']!='')  $xmlItem->append('phone', $item['agency_phone_1'],2); // * обязательное поле
                if($item['agency_phone_2']!='')  $xmlItem->append('phone', $item['agency_phone_2'],2); // * обязательное поле
                if($item['agency_phone_3']!='')  $xmlItem->append('phone', $item['agency_phone_3'],2); // * обязательное поле
                if($item['seller_name']!='')  $xmlItem->append('name', $item['seller_name'],2); 
                $xmlItem->append('category', 'агентство',2);
                $xmlItem->append('organization', $item['agency_title'],2);
                $xmlItem->append('agency-id', $item['id_user'],2);
                if(!empty($item['agency_url'])) $xmlItem->append('url', $item['agency_url'],2);
                if(!empty($item['agent_email'])) $xmlItem->append('email', $item['agent_email'],2);
            }        

}

function isnertLineIntoBilling( $bsn_id_user, $date, $estate_type, $status, $db = false ) {
    
    if( empty( $db ) ) {
        global $db;
        if( empty( $db ) ) return false;
    }
    
    $sys_tables = Config::$values['sys_tables'];
    $k = 0;
    $res = false;
    echo "trying to insert\r\n";
    //пихаем строчку пока не вставится или не будет превышен лимит
    while(!$res && $k < 10){
        $res = $db->query("INSERT INTO ".$sys_tables['billing']." SET external_id = ?, bsn_id = ?, date = ?, type = ?, bsn_id_user = ?, status = ?, adv_agency = ?",
                           mt_rand(10000,200000), mt_rand(100000,1300000), $date, $estate_type,  $bsn_id_user, $status, 1);
    if( !empty( $db->error ) || empty( $db ) ){
        echo $db->last_query."\r\n";
         echo $db->error."\r\n";
        die();
    }
        ++$k;
    }
        
    return $res;
}
?>

