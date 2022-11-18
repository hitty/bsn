<?php
require_once('includes/class.paginator.php');
require_once('includes/class.content.php');

// мэппинги модуля

Response::SetString('img_folder',Config::$values['img_folders']['news']);

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

//получаем список возможных url конкурсов
$list_konkurs = $db->fetchall("SELECT url FROM ".$sys_tables['konkurs']);
foreach($list_konkurs as $key=>$item){
    $list_konkurs[$key] = $item['url'];
}
$user_ip =  Host::getUserIp();
switch(true){
    //###########################################################################
    // список конкурсов
    //###########################################################################
    case empty($action):
        $GLOBALS['css_set'][] = '/modules/konkurs/style.css';
        $real_url=explode('/',$this_page->page_url);
        //редирект со старой страницы конкурса на новую
        if ($real_url[0]=='doverie_potrebitelya'){
            Host::Redirect('/konkurs/doverie_potrebitelya');
            break;
        }
        //заглавная страница
        $h1 = empty($this_page->page_seo_h1) ? "Конкурсы" : $this_page->page_seo_h1;
        Response::SetString('h1',$h1);
        $list = $db->fetchall("SELECT * FROM ".$sys_tables['konkurs']." WHERE status=1 OR (status=2 AND text_end!='') ORDER BY status=2, id DESC");
        Response::SetArray('list',$list);
        $module_template = 'mainpage.html';
        break;
    //###########################################################################
    // страница конкурса
    //###########################################################################
    case (in_array($action,$list_konkurs)):
        //получаем информацию по конкурсу
        $info = $db->fetch("SELECT id, title, type, text_begin_top, text_begin_bottom, text_end, status FROM ".$sys_tables['konkurs']." WHERE url=?",$action);
        Response::SetBoolean('konkurs_status',($info['status']==1));
        //заголовок таблицы голосования
        Response::SetArray('info',$info);
        
        //голосование
        if (!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1]=='voting' && $ajax_mode){
            $id = Request::GetInteger('id',METHOD_POST);
            if($id){
                //получаем категорию
                $list = $db->fetch("SELECT * FROM ".$sys_tables['konkurs_members']." WHERE id = ?",$id);
                if(!empty($list)){
                    //по типу конкурса определяем тип голосования
                    switch($info['type']){
                        case 'doverie':  //можно голосовать только один в 3 часа
                            $clauses = " AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['konkurs_votings'].".`datetime` ) )< 9999993 ";
                            break;
                        case 'ambition':
                            $clauses = " AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['konkurs_votings'].".`datetime` ) )<1 ";
                            break;
                        case 'photokonkurs':
                            //можно голосовать один раз в час
                            $clauses = " AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['konkurs_votings'].".`datetime` ) )<24 ";
                            break;
                    }
                    $check = $db->fetch("SELECT ".$sys_tables['konkurs_votings'].".id 
                                         FROM ".$sys_tables['konkurs_votings']."
                                         WHERE  ip = ? AND vote_id_category = ? AND id_konkurs = ? ".$clauses,
                                         $user_ip, $list['id_category'],$list['id_konkurs']
                    );

                    if(empty($check)){
                        $res = $db->querys("INSERT INTO ".$sys_tables['konkurs_votings']." SET id_konkurs = ?, vote_id_category = ?, vote_id_member = ?, ip = ?, datetime = NOW()",
                                           $list['id_konkurs'],$list['id_category'],$id,$user_ip);
                        $res1 = $db->querys("UPDATE ".$sys_tables['konkurs_members']." SET amount = amount+1 WHERE id=?",$id);
                        $ajax_result['ok'] = $res && $res1;
                    }
                }
            }
            break;
        }
        $GLOBALS['js_set'][] = '/modules/konkurs/voting.js';
        $GLOBALS['css_set'][] = '/modules/konkurs/style.css';
        
        //если конкурс активен, получаем список участников
        if($info['status']==1){
            switch($info['type']){
                case 'doverie':
                     $clauses = "  ";
                     $order = "  ";
                     break;
                case 'ambition':
                    $clauses = " AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['konkurs_votings'].".`datetime` ) ) < 1 ";
                    $order = "  ";
                    break;
                case 'photokonkurs'://голосование раз в сутки
                    $GLOBALS['js_set'][] = '/modules/konkurs/photogallery.js';
                    $GLOBALS['js_set'][] = '/modules/konkurs/share.js';
                    $clauses = " AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['konkurs_votings'].".`datetime` ) ) < 24 ";
                    $order = " RAND(), ";
                    //подулючене псеводформы
                    require_once('includes/class.email.php');
                    require_once('includes/pseudo_form/pseudo_form.php');                    
                    if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');                    
                    break;
                default:
                    break;
            }
            $list = $db->fetchall("SELECT ".$sys_tables['konkurs_members'].".*,".$sys_tables['konkurs_members'].".status as member_status, 
                                          votes.all_votes,
                                          LTRIM(".$sys_tables['konkurs_members'].".title) as member_title,
                                          ".$sys_tables['konkurs_members_categories'].".title as category_title,
                                          ".$sys_tables['konkurs_votings'].".vote_id_member,
                                          ".$sys_tables['konkurs_members_photos'].".`name` as `photo`, 
                                          LEFT (".$sys_tables['konkurs_members_photos'].".`name`,2) as `subfolder`,
                                          IF(".$sys_tables['konkurs_votings'].".id>0,0,1) as can_vote
                                   FROM ".$sys_tables['konkurs_members']."
                                   LEFT JOIN (
                                        SELECT SUM(amount) AS all_votes, id_category FROM ".$sys_tables['konkurs_members']." WHERE status=1 AND id_konkurs = ".$info['id']." GROUP BY id_category
                                   ) votes ON votes.id_category = ".$sys_tables['konkurs_members'].".id_category
                                   LEFT JOIN  ".$sys_tables['konkurs_members_categories']." ON ".$sys_tables['konkurs_members_categories'].".id=".$sys_tables['konkurs_members'].".id_category
                                   LEFT JOIN  ".$sys_tables['konkurs_members_photos']." ON ".$sys_tables['konkurs_members_photos'].".id=".$sys_tables['konkurs_members'].".id_main_photo
                                   LEFT JOIN  ".$sys_tables['konkurs_votings']." ON 
                                    ".$sys_tables['konkurs_votings'].".vote_id_category=".$sys_tables['konkurs_members_categories'].".id 
                                    AND ".$sys_tables['konkurs_votings'].".ip = '".$user_ip."' 
                                    AND ".$sys_tables['konkurs_votings'].".id_konkurs=".$sys_tables['konkurs_members'].".id_konkurs
                                    ".$clauses."
                                   WHERE ".$sys_tables['konkurs_members'].".id_konkurs=".$info['id']." AND ".$sys_tables['konkurs_members'].".status = 1
                                   ORDER BY ".$sys_tables['konkurs_members_categories'].".id , ".$order." member_title"); 
            Response::SetArray('list',$list);
            Response::SetString('konkurs_url',$action);
            Response::SetString('img_folder',Config::Get('img_folders/konkurs'));
        }
        
        //загрузка новых фотографий для Фотоконкурса (если была отправка формы)
        $post_parameters = Request::GetParameters(METHOD_POST);
        if($info['status']==1 && $info['type']=='photokonkurs'){
            $GLOBALS['css_set'][] = '/css/form.css';
            $errors = [];
            //список категорий
            $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['konkurs_members_categories']." WHERE id_konkurs = ".$info['id']." ORDER BY id");
            Response::SetArray('categories',$categories);

            $form_title = $form_email = $form_text = $form_category = "";
            if(!empty($post_parameters['submit'])){
                // логин
                if(empty($post_parameters['p_title'])) $errors['title'] = 'Не допускается пустое значение';
                $form_title=$post_parameters['p_title'];
                // сопроводительный текст
                $form_text=$post_parameters['text'];
                // категория
                if(empty($post_parameters['category'])) $errors['category'] = 'Не допускается пустое значение';
                $form_category=$post_parameters['category'];
                // Email
                if(!empty($post_parameters['p_email'])){
                    if(!Validate::isEmail($post_parameters['p_email'])) $errors['email'] = 'ошибка, неверный email';
                    $form_email = $post_parameters['p_email'];
                } else $errors['email'] = 'Не допускается пустое значение';
                //загрузка фотографии                 
                if(empty($_FILES['image']['name'])) $errors['image'] = 'Загрузите фотографию';                
                if(empty($errors)){

                    if (!Botobor_Keeper::get()->isRobot()) {
                        //записываем в БД
                        $res = $db->querys("INSERT INTO ".$sys_tables['konkurs_members']." 
                                    (title,id_konkurs,id_category,email,text, status)
                                    VALUES
                                     (?,?,?,?,?,?)"
                                     , $form_title
                                     , $info['id']
                                     , $form_category
                                     , $form_email 
                                     , $form_text 
                                     , 2 
                                     );
                         if(empty($res)){
                            $errors['error'] = true;
                        } else {
                            $id = $db->insert_id;
                            Photos::$__folder_options=array(
                                    'sm'=>array(160,120,'cut',65),
                                    'big'=>array(1200,960,'',50)
                                    ); 
                            $res = Photos::Add('konkurs_members',$db->insert_id, false, false, false, false,false);
                            Response::SetString('success','email');
                            
                            // отправка на мыло PR оповещения о новом номинанте
                            $mailer = new EMailer('mail');
                            // данные пользователя для шаблона
                            Response::SetArray( "data", array('email'=>$form_email, 'name'=>$form_title, 'id'=>$id) );
                            // данные окружения для шаблона
                            $env = array(
                                'url' => Host::GetWebPath('/'),
                                'host' => Host::$host,
                                'datetime' => date('d.m.Y H:i:s')
                            );
                            Response::SetArray('env', $env);
                            // инициализация шаблонизатора
                            $eml_tpl = new Template('photokonkurs.sent_mail.pr.html', $this_page->module_path);
                            // формирование html-кода письма по шаблону
                            $html = $eml_tpl->Processing();
                            // перевод письма в кодировку мейлера
                            $html = iconv('UTF-8', $mailer->CharSet, $html);
                            // параметры письма
                            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Новая фотография для конкурса на сайте '.Host::$host);
                            $mailer->Body = $html;
                            $mailer->AltBody = strip_tags($html);
                            $mailer->IsHTML(true);
                            $mailer->AddAddress(Config::$values['emails']['pr'], iconv('UTF-8',$mailer->CharSet, $form_title));
                            $mailer->From = 'no-reply@bsn.ru';
                            $mailer->FromName = 'bsn.ru';
                            // попытка отправить
                            $mailer->Send();
                            
                            //поиск зарегистрировшегося ранее аккаунта
                            $row = $db->fetch("SELECT id FROM ".$sys_tables['users']." WHERE email='".$db->real_escape_string($form_email)."'");
                            $reg_passwd = '';
                            if(empty($row)){
                                Response::SetBoolean('new_user',true);    
                                // генерируем пароль
                                $reg_passwd = substr(md5(time()),-6);
                                // создание нового пользователя в БД
                                $res = $db->querys("INSERT INTO ".$sys_tables['users']."
                                                    (email,name,passwd,datetime,access)
                                                   VALUES
                                                    (?,?,?,NOW(),'')"
                                                   , $form_email
                                                   , $form_title
                                                   , sha1(sha1($reg_passwd)));                                
                            }
                            // отправка на мыло пользователя оповещения о новой регистрации
                            $mailer = new EMailer('mail');
                            // данные пользователя для шаблона
                            Response::SetArray( "data", array('email'=>$form_email, 'name'=>$form_title, 'id'=>$db->insert_id, 'password'=>$reg_passwd) );
                            // данные окружения для шаблона
                            $env = array(
                                'url' => Host::GetWebPath('/'),
                                'host' => Host::$host,
                                'datetime' => date('d.m.Y H:i:s')
                            );
                            Response::SetArray('env', $env);
                            // инициализация шаблонизатора
                            $eml_tpl = new Template('photokonkurs.sent_mail.user.html', $this_page->module_path);
                            // формирование html-кода письма по шаблону
                            $html = $eml_tpl->Processing();
                            // перевод письма в кодировку мейлера
                            $html = iconv('UTF-8', $mailer->CharSet, $html);
                            // параметры письма
                            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Новая фотография для конкурса на сайте '.Host::$host);
                            $mailer->Body = $html;
                            $mailer->AltBody = strip_tags($html);
                            $mailer->IsHTML(true);
                            $mailer->AddAddress($form_email, iconv('UTF-8',$mailer->CharSet, $form_title));
                            $mailer->From = 'no-reply@bsn.ru';
                            $mailer->FromName = 'bsn.ru';
                            // попытка отправить
                            $mailer->Send();                          

                        } 
                    }                                                    
                }
            } 
            
            //вставка формы отпраки через формоанализатор 
            Response::SetArray('form_vars',array(
                                     'title'=>$form_title
                                    ,'email'=>$form_email
                                    ,'category'=>$form_category
                                    ,'text'=>$form_text
                                    ,'url'=>$this_page->real_url
                               ));
            Response::SetArray('errors',$errors);
            $tpl = new Template("konkurs.photokonkurs.form.html",$this_page->module_path);
            $formContent = $tpl->Processing();
                                                
            $botFormContent = new Botobor_Form($formContent);
            Response::SetString('form',$botFormContent->getCode());
        }
        
        $module_template = 'konkurs.'.$info['type'].'.html';
        $this_page->addBreadcrumbs('Конкурс «'.$info['title'].'»', $action);
        $new_meta = array('title'=>$info['title']);
        $this_page->manageMetadata($new_meta,true);
        
        $h1 = empty($this_page->page_seo_h1) ? $info['title'] : $this_page->page_seo_h1;
        Response::SetString('h1',$h1);
        break;
}
?>