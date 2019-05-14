<?php
require_once('includes/class.paginator.php');
require('includes/pseudo_form/pseudo_form.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

//от какой записи вести отчет
$from = 0;
//записей на страницу
$strings_per_page = Config::Get('view_settings/strings_per_page');

$GLOBALS['css_set'][] = '/modules/guestbook/styles.css';
switch(true){
    
    //###########################################################################
    // редирект на GET паджинацию
    //###########################################################################
    case isPage($action): 
            Host::Redirect("/guestbook/?page=".getPage($action));
        break;
    //###########################################################################
    // редирект с /addguestbook/ на /guestbook/add/
    //###########################################################################
    case $this_page->page_url=='add_guestbook':
            Host::Redirect("/guestbook/add/");
        break;
    //###########################################################################
    // добавление записи в гостевую книгу
    //###########################################################################
    case (($action=='add')&&(empty($this_page->page_parameters[1]))):
            $post_parameters = Request::GetParameters(METHOD_POST);
            // если была отправка формы
            if(!empty($post_parameters['submit'])){
                if(empty($post_parameters['author_fio'])) $errors['author_fio'] = 'Не допускается пустое значение'; else $question_name = Convert::ToString($post_parameters['author_fio']);
                if(!Validate::isEmail($post_parameters['author_email'])) $errors['author_email'] = 'Недопустимое значение';  else $question_email = Convert::ToString($post_parameters['author_email']);
                if(empty($post_parameters['author_text'])) $errors['author_text'] = 'Не допускается пустое значение';  else $question_text = Convert::ToString($post_parameters['author_text']);
                if(empty($errors)){
                    //проверка на отправку сообщения роботом
                    if (!Botobor_Keeper::get()->isRobot()) {
                        //записываем в БД
                        $res = $db->query("INSERT INTO ".$sys_tables['guestbook']." 
                                    (question,question_datetime,name,email)
                                    VALUES
                                     (?,NOW(),?,?)"
                                     , $question_text
                                     , $question_name
                                     , $question_email );
                         //спрячем форму и покажем уведомление об успехе
                         Response::SetBoolean('submit_succ',true);
                         //                                     
                         if(empty($res)){
                            $errors['error'] = true;
                        }
                    }
                }
            }
            //response ошибок
            if (!empty($errors)) {
                Response::SetArray('errors',$errors);
                //response введенных значений если есть ошибки
                if (!empty($post_parameters)){
                    Response::SetArray('user_input',$post_parameters);
            }
            }
            $module_template = 'block.html';
            //
            $tpl = new Template("modules/guestbook/templates/form.html");
            $formContent = $tpl->Processing();
            $botFormContent = new Botobor_Form($formContent);
            Response::SetString('form',$botFormContent->getCode());
            //
            //добавляем breadcrumbs и title
            $h1='Добавление записи';
            Response::SetString('h1', empty($this_page->page_seo_h1) ? $h1 : $this_page->page_seo_h1);
            $this_page->addBreadcrumbs('Добавление записи','add_guestbook');
            $new_meta = array('title' =>'Добавление записи', 'keywords' =>'Добавление записи');
            $this_page->manageMetadata($new_meta, true);
        break;
    //###########################################################################
    // выводим список записей
    //###########################################################################
    case empty($action): 
            $module_template = 'mainpage.html';
            //редирект с несуществующих пейджей
            $page = Request::GetInteger('page',METHOD_GET);
             if(empty($page)){
                if(isset($page)) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
                $page = 1;
            }
            elseif($page<1) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
            else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
            if (empty($page)) $page=1;
            // создаем пагинатор для списка
            $where=" published=1";
            $paginator = new Paginator($sys_tables['guestbook'], $strings_per_page, $where);
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
            $list = $db->fetchall("SELECT ".$sys_tables['guestbook'].".question, ".
                                            "DATE_FORMAT(".$sys_tables['guestbook'].".question_datetime,'%e %M, %k:%i') AS q_date, ".
                                            $sys_tables['guestbook'].".name,".
                                            $sys_tables['guestbook'].".answer
                                            FROM ".$sys_tables['guestbook']."
                                            WHERE published=1  
                                            ORDER BY id DESC
                                            LIMIT ".$paginator->getFromString($page).",".$strings_per_page);

            //response
            Response::SetArray('list',$list);
            //устанавливаем breadcrumbs и title
            $h1='Гостевая книга';
            Response::SetString('h1', empty($this_page->page_seo_h1) ? $h1 : $this_page->page_seo_h1);
            $new_meta = array('title' =>'Гостевая книга', 'keywords' =>'Гостевая книга');
            $this_page->manageMetadata($new_meta, true);
        break;
    default:
            $this_page->http_code = 404;
        break;
}
?>