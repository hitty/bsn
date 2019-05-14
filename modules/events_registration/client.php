<?php
require_once('includes/class.paginator.php');
require_once('includes/class.email.php');
require('includes/pseudo_form/pseudo_form.php');
//сразу добавляем breadcrumb для списка событий
$this_page->addBreadcrumbs('Регистрация на форумах','events_registration');

//выбирается параметр 1, так как есть редирект
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

//записей на страницу
$strings_per_page = Config::Get('view_settings/strings_per_page');

$GLOBALS['css_set'][] = '/modules/events_registration/styles.css';
switch(true){
    
    //###########################################################################
    // редирект со старого на новый url
    //###########################################################################
    case preg_match('/^pr_investment/',$this_page->requested_url):
        $redirect_url = '';
        //получаем url для редиректа
        $item = $db->fetch("SELECT url  
                           FROM ".$sys_tables['events_registration']."  
                           WHERE url = '".$this_page->requested_url."'"
        );
        if(!empty($item)) 
           $redirect_url = '/events_registration/'.$item['url'].'/'; 
        else {
            $this_page->http_code=404;
            break;    
        }
        if($redirect_url!='') Host::Redirect($redirect_url);
        break;
    //###########################################################################
    // выводим список событий
    //###########################################################################
    //второе empty нужно, чтобы отсечь добавления к url. например: events_registration/ww/
    case (empty($action))&&(empty($this_page->page_parameters[0])):
            $module_template = 'mainpage.html';
            //редирект с несуществующих пейджей
            $page = Request::GetInteger('page',METHOD_GET);
            if ((isset($page))&&($page<=0)){
                $get_parameters=Request::GetParameters(METHOD_GET);
                $paginator_link_base = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&');
                Host::Redirect('/'.$paginator_link_base.'page=1');
                exit(0);
            }         
             //meta-тег robots = noindex

            if (empty($page)){
                $page=1;
            }
            // создаем пагинатор для списка
            $paginator = new Paginator($sys_tables['events_registration'], $strings_per_page, "");
            if($paginator->pages_count>0 && $paginator->pages_count<$page){
                Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count);
                exit(0);
            }
            //формирование url для пагинатора
            $paginator->link_prefix = '/'.$this_page->requested_path.'/?page=';
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }
            
            //выбираем страницы для отображения
            $list = $db->fetchall("SELECT *, DATE_FORMAT(".$sys_tables['events_registration'].".event_date,'%e %M, %k:%i') AS e_date ".
                                            "FROM ".$sys_tables['events_registration']."
                                            ORDER BY event_date DESC
                                            LIMIT ".$paginator->getFromString($page).",".$strings_per_page);

            //response
            Response::SetArray('list',$list);
            //устанавливаем breadcrumbs и title
            Response::SetString('h1', empty($this_page->page_seo_h1) ? 'Регистрация на форумы' : $this_page->page_seo_h1);
            $new_meta = array('title' =>'Регистрация на форумы', 'keywords' =>'Регистрация на форумы');
            $this_page->manageMetadata($new_meta, true);
        break;
    //###########################################################################
    // добавление на событие
    //###########################################################################
    
    //второе empty нужно, чтобы отсечь добавления к url. например: events_registration/pr_investment8/ww/
    case (!empty($action)&&(empty($this_page->page_parameters[2]))):
        $item=$db->fetch("SELECT * FROM ".$sys_tables['events_registration']." WHERE url='".$db->real_escape_string($action)."'");
        if (empty($item)){
            $this_page->http_code = 404;
            break;
        }
        //шлем url, чтобы вписать в action формы
        Response::SetString('event_url',$item['url']);
        
        //поля, которые возможны в форме
        $fields=array('fio','phone','email','company','rank','wishes');
        
        //по полю fields определяем, какие поля будут в форме
        foreach($fields as $key=>$val){
            if($item['fields']%(pow(2,$key+1))>=pow(2,$key)) $form_fields[$fields[$key]]=true;
        }
        
        //поля, которые будут в форме
        Response::SetArray('conf_form',$form_fields);
        
        $post_parameters = Request::GetParameters(METHOD_POST);
        
        //прикрепленная статья календаря событий
        if(!empty($item['id_calendar'])){
             $calendar_item = $db->fetch("
                            SELECT 
                                *, 
                                YEAR(`date_begin`) as `year`, 
                                DATE_FORMAT(`date_begin`,'%e %M') as `datebegin`, 
                                IF(`date_end`>`date_begin`, DATE_FORMAT(`date_end`,'%e %M'),'') as `dateend`
                             FROM ".$sys_tables['calendar_events']." WHERE `id` = ".$item['id_calendar']);      
            if(!empty($calendar_item)){
                 $GLOBALS['css_set'][] = '/modules/calendar_events/style.css';
                 Response::SetArray('calendar_item', $calendar_item);
                 Response::SetString('img_folder',Config::$values['img_folders']['calendar_events']);
                 if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
                 $photos = Photos::getList('calendar_events',$item['id_calendar']);
                 Response::SetArray('photos',$photos);                
            }      
        }
        
        // если была отправка формы
        if(!empty($post_parameters['submit'])){
            
            //проверяем на коррректность введенные значения
            //$text будет накапливать параметры, введенные пользователем
            $text="";
            if (!empty($form_fields['fio'])){
                if(empty($post_parameters['fio'])) $errors['fio'] = 'Не допускается пустое значение'; 
                else $text.="ФИО: ".$post_parameters['fio']."; ";
            }
            if (!empty($form_fields['email'])){
                if(!Validate::isEmail($post_parameters['user_email'])) $errors['user_email'] = 'Недопустимое значение';
                else $text.="Email: ".$post_parameters['user_email']."; ";
            }
            if (!empty($form_fields['phone'])){
                if(!Validate::isPhone($post_parameters['phone'])) $errors['phone'] = 'Недопустимое значение';
                else $text.="Контактный телефон: ".$post_parameters['phone']."; ";
            }
            if (!empty($form_fields['company'])){
                if(empty($post_parameters['user_company'])) $errors['user_company'] = 'Не допускается пустое значение';
                else $text .= "Компания: ".$post_parameters['user_company']."; ";
            }
            if (!empty($form_fields['rank'])){
                if(empty($post_parameters['user_rank'])) $errors['user_rank'] = 'Не допускается пустое значение';
                else $text .= "Должность: ".$post_parameters['user_rank']."; ";
            }
            if(empty($errors)){
                //проверка на отправку сообщения роботом
                if (!Botobor_Keeper::get()->isRobot()) {
                    //записываем в БД
                    $res = $db->query("INSERT INTO ".$sys_tables['events_request']." 
                                 (id_event,datetime,request_data)
                                 VALUES
                                 (?,CURRENT_TIMESTAMP(),?)",
                                 $item['id'],
                                 $text);
                     if(empty($res)){
                        $errors['error'] = true;
                     }
                     else{
                         //прячем форму и показываем уведомление об успехе
                         Response::SetBoolean('submit_succ',true);
                         //посылаем текст для пользователя для этого события
                         Response::SetString('invited_text',$item['invited_text']);
                         Response::SetString('forum_title',$item['title']);
                                                                    
                         // отправка оповещения о регистрации
                         $mailer = new EMailer();                                                  
                         // параметры письма
                         //текст для менеджера - данные о зарегистрировавшемся
                         $manager_text=iconv('UTF-8',$mailer->CharSet,'новый пользоваетль: '.$text);
                         $mailer->Subject = iconv('UTF-8', $mailer->CharSet,$item['title']);
                         $mailer->Body = $manager_text;
                         $mailer->AddAddress($item['manager_email']);
                         $mailer->From = 'no-reply@bsn.ru';
                         $mailer->FromName = 'bsn.ru';     
                         // попытка отправить
                         $mailer->Send();
                         
                         //отправляем пользователю, если указан email
                         if (!empty($post_parameters['user_email'])){
                             $html =  iconv('UTF-8', $mailer->CharSet,'Вы зарегистрированы на '.$item['title']."<br /><br />".$item['invited_text'].'<br /><br />Благодарим за регистрацию');
                             $mailer->ClearAddresses();
                             $mailer->AddAddress($post_parameters['user_email']);
                             $mailer->Subject = iconv('UTF-8', $mailer->CharSet,$item['title']);
                             $mailer->Body = $html;
                             $mailer->IsHTML(true);
                             $mailer->From = 'no-reply@bsn.ru';
                             $mailer->FromName = 'bsn.ru';
                             if($mailer->Send()) Response::SetString('sendmail_succ','email');
                         }
                     }
                }
                else{
                    Response::SetString('botobor_msg',"Извините, Ваша запись не может быть добавлена");
                }
            }
        }
        //response ошибок
        if (!empty($errors)) {
            Response::SetArray('errors',$errors);
            //response введенных значений если есть ошибки
            if (!empty($post_parameters)){
                Response::SetArray('item',$post_parameters);
            }
        }
        $module_template = 'item.html';
        $GLOBALS['css_set'][] = '/css/form.css';
        $tpl = new Template("modules/events_registration/templates/form.html");
        $formContent = $tpl->Processing();
        $botFormContent = new Botobor_Form($formContent);
        Response::SetString('form',$botFormContent->getCode());
        
        //добавляем breadcrumbs и title
        Response::SetString('h1', empty($this_page->page_seo_h1) ? $item['title'] : $this_page->page_seo_h1);
        $this_page->addBreadcrumbs($item['title'],'forum');
        $new_meta = array('title' =>$item['title'], 'keywords' =>'недвижимость, санкт-петербурге, петербург, спб, продажа, аренда, питер');
        $this_page->manageMetadata($new_meta, true);
        break;
    default:
        $this_page->http_code = 404;
        break;
        
}
?>