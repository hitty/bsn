<?php
require_once('includes/class.paginator.php');
// определяем возможный запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
switch(true){
    //////////////////////////////////////////////////////////////////////////////
    // главная страница 
    //////////////////////////////////////////////////////////////////////////////
    case empty($action) && count($this_page->page_parameters) == 0:
        require_once('includes/getid3/getid3.php');
        $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/form.validate.js';
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['css_set'][] = '/modules/video_konkurs/fonts.css';
        $GLOBALS['css_set'][] = '/modules/video_konkurs/style.css';
        $GLOBALS['js_set'][] = '/modules/video_konkurs/script.js';
        $GLOBALS['css_set'][] = '/js/parallax/css/jquery.parallax.css';
        $GLOBALS['js_set'][] = '/js/parallax/js/jquery.parallax.js';
        Response::SetBoolean('mainpage',true)         ;
        $module_template = 'mainpage.html';
        $parameters = Request::GetParameters(METHOD_POST);
        $errors = array();
        if(!empty($_FILES) && !empty($_FILES['userfile']['name']) && !empty($parameters)){
            //флаг проверки повторной отправки
            $upload = Cookie::GetString('upload');
            if(!empty($upload)) Host::Redirect('/video_konkurs_2015/');
            foreach ($_FILES as $fname => $data){
                if ($data['error']==0) {
                    $_folder = Host::$root_path.'/'.Config::$values['video_folders']['konkurs_2015'].'/'; // папка для файлов  тгб
                    $fileTypes = array('mp4', '3gp', 'ogg', 'avi', 'mov', 'wmf'); // допустимые расширения файлов
                    $fileParts = pathinfo($data['name']);
                    $targetExt = $fileParts['extension'];
                    $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                    
                    if (in_array(strtolower($targetExt),$fileTypes)) {
                        move_uploaded_file($data['tmp_name'],$_folder.$_targetFile);
                        //получение информации о файле
                        $getID3 = new getID3; 
                        $file = $getID3->analyze($_folder.$_targetFile);
                        if($file['playtime_seconds'] >180) $errors[] = 'Длительность файла больше 2-х минут.';
                        if($file['filesize']/1024/1024 >200) $errors[] = 'Размер файла превышает 200 МБайт.';
                            
                        $post_parameters[$fname] = $_targetFile;
                    } else $errors[] = 'Неверное расширение файла.';
                    if(empty($errors)) { // видео загрузилось
                        //запись в базу
                        $db->querys("INSERT INTO ".$sys_tables['video_konkurs']." SET 
                                    id_estate_complex = ?,
                                    complex_type = ?,
                                    name = ?,
                                    email = ?,
                                    phone = ?,
                                    link = ?",
                                    $parameters['estate_complex'], $parameters['complex_type'], $parameters['name'], $parameters['email'], $parameters['phone'], $_targetFile
                                    
                        );
                        $id = $db->insert_id;
                        //загрузка видео на cloud4video
                        $url = "http://ficus-n2.cloud4video.ru:8089/rest-api/file?login=pm%40bsn.ru&password=4d651eb627&gen_int_id=true";
                        $body = '<?xml version="1.0" encoding="utf-8"?>
                        <root>
                        <file id="konkurs_bsn_id_'.$id.'" convert_formats="flash_h264_hq@360p-512;">http://st.bsn.ru/img/video/konkurs_2015/dbe23e3efcb2b0033552bb68794d1921.mp4</file>
                        </root>';
                        $result = curlThis($url, 'POST', false, true, $body); 
                        include('cron/robot/class.xml2array.php');  // конвертация xml в array
                        $xml_str = xml2array($result);
                        if($xml_str['response']['status'] == 'WITH_ERRORS') {
                           
                            $errors[] = 'Произошла непредвиденная ошибка. Попробуйте загрузить ваше видео позднее.';
                        } else {
                            //ждем пока не загрузится файл
                            $url = "http://ficus-n2.cloud4video.ru:8089/rest-api/infos/externalstatuslist?login=pm%40bsn.ru&password=4d651eb627&externalIds=konkurs_bsn_id_".$id;
                            for($i=0; $i<=1000; $i++){
                                $status = curlThis($url, 'GET');                 
                                $xml_str = xml2array($status);
                                if(!empty($xml_str['response']['files']['file']['status']) && $xml_str['response']['files']['file']['status'] == 'done') break;
                                else if($xml_str['response']['files']['file']['status'] == 'download_error') {
                                   
                                    Response::SetString('notify','Ваше видео обрабатывается и после успешной проверки модератором появится на сайте.');
                                    break;
                                }
                                sleep(.5);
                            }
                            if(empty($errors)){
                                //получение свойств файла из cloud4video
                                $url = "http://ficus-n2.cloud4video.ru:8089/rest-api/file/konkurs_bsn_id_".$id."?login=pm%40bsn.ru&password=4d651eb627";
                                $info = curlThis($url, 'GET'); 

                                $xml_str = xml2array($info);
                                $external_link = $xml_str['response']['video']['formats']['format'][1]['types']['type']['encoded_url'];
                                if(!empty($external_link))  {
                                    $res = $db->querys("UPDATE ".$sys_tables['video_konkurs']." SET external_link = ? WHERE id = ?",
                                                   $external_link, $id
                                    );  
                                    if($res){
                                        if(file_exists($_folder.$_targetFile)) unlink($_folder.$_targetFile);      
                                        Cookie::SetCookie('upload',true, 2);
                                        Host::Redirect('/video_konkurs_2015/?ok=true#thanks');
                                        //письмо менеджеру
                                        require_once('includes/class.email.php');
                                        $mailer = new EMailer('mail');
                                        Response::SetInteger('id', $id);
                                        $eml_tpl = new Template('mail.manager.html', $this_page->module_path);
                                        $html = $eml_tpl->Processing();
                                        $html = iconv('UTF-8', $mailer->CharSet, $html);
                                        $mailer->ClearAddresses();
                                        $mailer->AddAddress('pm@bsn.ru');
                                        $mailer->Subject = iconv('UTF-8', $mailer->CharSet,'Новое видео в разделе Видео конкурс ЖК');
                                        $mailer->Body = $html;
                                        $mailer->IsHTML(true);
                                        $mailer->From = 'no-reply@bsn.ru';
                                        $mailer->FromName = 'bsn.ru';
                                        $mailer->Send();

                                    }
                                } else {
                                    Cookie::SetCookie('upload',true, 2);
                                    Host::Redirect('/video_konkurs_2015/?ok=true#thanks');
                                } 
                            }
                        }
                    } else{
                        if(file_exists($_folder.$_targetFile)) unlink($_folder.$_targetFile); 
                        
                    }
                }
            }
            $estate_complex = $db->fetch("SELECT `title` FROM ".$sys_tables['housing_estates']." WHERE id = ?", $parameters['estate_complex']);
            $parameters['estate_complex_title'] = $estate_complex['title'];
            Response::SetArray('parameters', $parameters);
        } else if(!empty($parameters)) $errors[] = 'Выберите файл для загрузки';
        if(!empty($errors)) Response::SetArray('errors', $errors);; 
        if(!empty($parameters)) Response::SetArray('parameters', $parameters);
        $item = $db->fetch("SELECT COUNT(*) as count FROM ".$sys_tables['video_konkurs']." WHERE status = 1");
        Response::SetInteger('count', $item['count']);
        Response::SetString('page_type', 'mainpage');
        break;
        //////////////////////////////////////////////////////////////////////////////
        // голосование 
        //////////////////////////////////////////////////////////////////////////////
        case $action == 'vote_for':
            $user_agent = $db->real_escape_string($_SERVER['HTTP_USER_AGENT']);
            $user_ip = Host::getUserIp();
            $id = Request::GetInteger('id', METHOD_POST);
            $action = Request::GetString('action', METHOD_POST);
            if(!empty($id) && !empty($user_ip) && !empty($user_agent) && !empty($action)){
                //dislike
                if($action == 'minus') $db->querys("DELETE FROM ".$sys_tables['video_konkurs_votings']."
                                        WHERE 
                                            ".$sys_tables['video_konkurs_votings'].".id_parent = ?
                                            AND ".$sys_tables['video_konkurs_votings'].".ip = '".$user_ip."' 
                                            AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['video_konkurs_votings'].".`datetime` ) ) < 24", $id
                                        );
                else {
                    //можем ли мы голосовать
                    $item = $db->fetch("SELECT *
                                        FROM ".$sys_tables['video_konkurs_votings']."
                                        WHERE 
                                            ".$sys_tables['video_konkurs_votings'].".id_parent = ?
                                            AND ".$sys_tables['video_konkurs_votings'].".ip = '".$user_ip."' 
                                            AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['video_konkurs_votings'].".`datetime` ) ) < 24", $id
                    );                                                      
                    if(empty($item)){
                        $res = $db->querys("INSERT INTO ".$sys_tables['video_konkurs_votings']." SET
                                        id_parent = ?, user_agent = ?, ip = ?
                        ",              $id, $user_agent, $user_ip
                        );
                    }
                }
            }
            break;

        //////////////////////////////////////////////////////////////////////////////
        // список 
        //////////////////////////////////////////////////////////////////////////////
        case $action == 'list':
            if(count($this_page->page_parameters)>2 || (count($this_page->page_parameters) == 2 && $this_page->page_parameters[1]!='block')) Host::Redirect('/video_konkurs_2015/');
            if($ajax_mode) {
                $strings_per_page = 10;
                Response::SetBoolean('block', true);
            } else if(!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]=='block') {
                $strings_per_page = 3;
                Response::SetBoolean('block', true);
            } else {
                $strings_per_page = 10;
            }
            if(!(!empty($ajax_mode) || (!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]=='block'))){
                $GLOBALS['css_set'][] = '/modules/video_konkurs/fonts.css';
                $GLOBALS['css_set'][] = '/modules/video_konkurs/style.css';
                $GLOBALS['js_set'][] = '/modules/video_konkurs/script.js';
                
            }
            $page = Request::GetInteger('page',METHOD_GET);
            //редирект с несуществующих пейджей
             if(empty($page)){
                if(isset($page)) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
                $page = 1;
            }
            elseif($page<1) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
            else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
            Response::SetInteger('page', $page);
            $where = $sys_tables['video_konkurs'].".status = 1 AND ".$sys_tables['video_konkurs'].".external_link!=''";
            // создаем пагинатор для списка
            $paginator = new Paginator($sys_tables['video_konkurs'], $strings_per_page, $where);
            if($paginator->pages_count>0 && $paginator->pages_count<$page){
                Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count);
                exit(0);
            }
            //сортировка
            $sortby = Request::GetInteger('sortby', METHOD_GET);
            if(!empty($sortby)) Response::SetBoolean('noindex',true);
            else $sortby = 1;
            Response::SetInteger('sortby', $sortby);
            switch($sortby){            
                case 6:
                    $orderby = $sys_tables['housing_estates'].".title ASC";
                    break;
                case 5:
                    $orderby = $sys_tables['housing_estates'].".title DESC";
                    break;
                case 4:
                    $orderby = "votings ASC";
                    break;
                case 3:
                    $orderby = "votings DESC";
                    break;
                case 2:
                    $orderby = $sys_tables['video_konkurs'].".datetime ASC";
                    break;
                case 1:
                default:
                    $orderby = $sys_tables['video_konkurs'].".datetime DESC";
                    break;
            }
            
            //формирование url для пагинатора
            $paginator->link_prefix = '/'.$this_page->requested_path.'/?page=';
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }
            $user_ip = Host::getUserIp();
            $sql = "SELECT ".$sys_tables['video_konkurs'].".*, 
                            IF(YEAR(".$sys_tables['video_konkurs'].".`datetime`) < Year(CURDATE()),DATE_FORMAT(".$sys_tables['video_konkurs'].".`datetime`,'%e %M %Y'),DATE_FORMAT(".$sys_tables['video_konkurs'].".`datetime`,'%e %M, %k:%i')) as normal_date,
                            IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates'].".title,".$sys_tables['cottages'].".title) as title,
                            IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates'].".chpu_title,".$sys_tables['cottages'].".chpu_title) as chpu_title,
                            IF(".$sys_tables['video_konkurs'].".complex_type=1,".$sys_tables['housing_estates_photos'].".name,".$sys_tables['cottages_photos'].".name) as photo_name,
                            IF(".$sys_tables['video_konkurs'].".complex_type=1,LEFT (".$sys_tables['housing_estates_photos'].".`name`,2),LEFT (".$sys_tables['cottages_photos'].".`name`,2)) as subfolder,
                            IFNULL(v.votings,0) as votings,
                            IF(".$sys_tables['video_konkurs_votings'].".id>0,0,1) as can_vote
                    FROM ".$sys_tables['video_konkurs']." 
                    LEFT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['housing_estates'].".id = ".$sys_tables['video_konkurs'].".id_estate_complex AND complex_type = 1
                    LEFT JOIN ".$sys_tables['housing_estates_photos']." ON ".$sys_tables['housing_estates'].".id_main_photo = ".$sys_tables['housing_estates_photos'].".id
                    LEFT JOIN ".$sys_tables['cottages']." ON ".$sys_tables['cottages'].".id = ".$sys_tables['video_konkurs'].".id_estate_complex AND complex_type = 2
                    LEFT JOIN ".$sys_tables['cottages_photos']." ON ".$sys_tables['cottages'].".id_main_photo = ".$sys_tables['cottages_photos'].".id
                    LEFT JOIN (SELECT IFNULL(COUNT(*),0) as votings, id_parent FROM ".$sys_tables['video_konkurs_votings']." GROUP BY id_parent) v ON v.id_parent = ".$sys_tables['video_konkurs'].".id
                    LEFT JOIN  ".$sys_tables['video_konkurs_votings']." ON 
                        ".$sys_tables['video_konkurs_votings'].".id_parent=".$sys_tables['video_konkurs'].".id 
                        AND ".$sys_tables['video_konkurs_votings'].".ip = '".$user_ip."' 
                        AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['video_konkurs_votings'].".`datetime` ) ) < 24
                    WHERE $where
                    ORDER BY $orderby
                    LIMIT ".$paginator->getLimitString($page); 
            $list = $db->fetchall($sql);
            // формирование списка
            Response::SetArray('list', $list);
            $module_template = "list.html";
            if(!empty($ajax_mode)) {
                $ajax_result['ok'] = true;
                if($page == $paginator->pages_count) $ajax_result['hide_button'] = true;
            }
            break;
        case 'offer':
            $GLOBALS['css_set'][] = '/modules/video_konkurs/fonts.css';
            $GLOBALS['css_set'][] = '/modules/video_konkurs/style.css';
            $GLOBALS['js_set'][] = '/modules/video_konkurs/script.js';
            $module_template = 'offer.html';
            break;
}

?>