<?php
require_once('includes/class.paginator.php');
require_once('includes/class.content.php');

// мэппинги модуля


$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

//записей на страницу
$strings_per_page = 15;
//от какой записи вести отчет
$this_page->addBreadcrumbs('Статьи от партнеров', 'partners_articles');
$from=0;
switch(true){
    case $this_page->page_url=='artpay': // редирект со старого на новый url
        if(empty($this_page->page_parameters[1]) && !empty($this_page->page_parameters[0])) {
            $this_page->page_parameters[1] = $this_page->page_parameters[0];
            $redirect_url = '';
            // получаем новый числовой префикс  и url для редиректа
            $item = $db->fetch("SELECT *, DATE_FORMAT(`datetime`,'%d%m%y') as new_prefix 
                               FROM ".$sys_tables['partners_articles']." 
                               WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[1])."'
                                      AND `published` = 1"
            );
        }
        if(!empty($item)) 
           $redirect_url = '/partners_articles_'.$item['new_prefix'].'/'.$this_page->page_parameters[1].'/'; 
        else {
            if(!empty($action)){
                if(Validate::isDigit($action) && !empty($this_page->page_parameters[1])){ // карточка статьи
                    // получаем новый числовой префикс  и url для редиректа
                    $item = $db->fetch("SELECT *, DATE_FORMAT(`datetime`,'%d%m%y') as new_prefix 
                                       FROM ".$sys_tables['partners_articles']." 
                                       WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[1])."'
                                              AND `published` = 1"
                    );
                if(!empty($item)) 
                   $redirect_url = '/partners_articles_'.$item['new_prefix'].'/'.$this_page->page_parameters[1].'/';                
                }else{ // рубрикатор
                    if(isPage($this_page->page_parameters[1])) //редирект на GET паджинацию
                        $redirect_url = "/partners_articles/".$action."/?page=".getPage($this_page->page_parameters[1]);
                    elseif(Validate::isDigit($action))
                        $redirect_url = "/partners_articles/";
                    else
                        $redirect_url = "/partners_articles/".$action."/";
                }
            } else $redirect_url = "/partners_articles/";
        }
        
        if(!empty($redirect_url)) Host::Redirect($redirect_url);
        $this_page->http_code=404;
        break;
    case empty($action): // рубрикатор статей
        
        $h1 =  empty($this_page->page_seo_h1) ? 'Статьи от партнеров' : $this_page->page_seo_h1;
        Response::SetString('h1',$h1);
        
        //список категорий
        $list = $db->fetchall("SELECT * FROM ".$sys_tables['partners_articles_categories']." ORDER BY `position`");
        Response::SetArray('list',$list);                
        $module_template = 'mainpage.html';        
        break;
    case Validate::isDigit($action) && !empty($this_page->page_parameters[1]): //карточка статьи
        if(!empty($this_page->page_parameters[2])) {$this_page->http_code=404; break;}
        $item = $db->fetch("SELECT 
                                ".$sys_tables['partners_articles'].".*, 
                                DATE_FORMAT(".$sys_tables['partners_articles'].".datetime,'%e %M') as normal_date,
                                ".$sys_tables['partners_articles_categories'].".title as category_title, 
                                ".$sys_tables['partners_articles_categories'].".code as category_code 
                           FROM ".$sys_tables['partners_articles']."
                           RIGHT JOIN ".$sys_tables['partners_articles_categories']." ON ".$sys_tables['partners_articles_categories'].".id=".$sys_tables['partners_articles'].".id_category 
                           WHERE 
                                ".$sys_tables['partners_articles'].".`code` = '".$db->real_escape_string($this_page->page_parameters[1])."'
                                AND ".$sys_tables['partners_articles'].".published=1
                                AND '".$action."'=DATE_FORMAT(`datetime`,'%d%m%y')"
        );
        if(!empty($item)){
            Response::SetArray('item',$item);
            //шаблон карточки новости
            $module_template = 'item.html';
            //хлебные крошки
            $this_page->addBreadcrumbs($item['category_title'], $item['category_code']);
            $this_page->addBreadcrumbs($item['title'], $item['id']);
            //добавление title
            $new_meta = array('title'=>$item['title'].' - Статьи от партнеров','keywords'=>$item['title'],'description'=>$item['title']);
            $this_page->manageMetadata($new_meta,true);            
        }
        else {
            $this_page->http_code=404;
            break;    
        }

        break;
    case (!empty($action)&&(empty($this_page->page_parameters[1]))): // список новостей в категории
        //редирект с несуществующих пейджей
        $page = Request::GetInteger('page',METHOD_GET);
        if(empty($page)){
            if(isset($page)) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
            $page = 1;
        } elseif($page<1) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
        else Response::SetBoolean('noindex',true); //meta-тег robots = noindex

        $where = array('published=1');
        $category = $db->fetch("SELECT * FROM ".$sys_tables['partners_articles_categories']." WHERE `code` = '".$db->real_escape_string($action)."'");
        if(!empty($category)) $where[] = "id_category = ".$category['id'];
        else {
            $this_page->http_code=404;
            break;    
        }
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['partners_articles'], $strings_per_page, implode(" AND ",$where));
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count);
            exit(0);
        }
        //формирование url для пагинатора
        $paginator->link_prefix = '/'.$this_page->requested_path.'/?page=';
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        //список статей
        $list = $db->fetchall("SELECT 
                        ".$sys_tables['partners_articles'].".*,
                        DATE_FORMAT(`datetime`,'%d%m%y') as new_prefix, 
                        DATE_FORMAT(".$sys_tables['partners_articles'].".datetime,'%e %M') as normal_date
                   FROM ".$sys_tables['partners_articles']."
                   WHERE 
                        ".implode(" AND ",$where)."
                        ORDER BY `datetime` DESC, `id` DESC
                        LIMIT ".$paginator->getFromString($page).",".$strings_per_page
        );
        if(empty($list)) {
            $this_page->http_code=404;
            break;    
        } 
        Response::SetArray('list', $list);
        $module_template = 'list.html';
        $h1 = array();
        //хлебные крошки
        $this_page->addBreadcrumbs($category['title'], $action);
        //добавление title
        $new_meta = array('title'=>$category['title'], 'keywords'=>$category['title']);
        $this_page->manageMetadata($new_meta);
        $h1[] = $category['title'];
        $h1 =  empty($this_page->page_seo_h1) ? implode('. ',$h1) : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);                        
        break;
    default:
        $this_page->http_code=404;
        break;
}
?>