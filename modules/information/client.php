<?php
require_once('includes/class.paginator.php');
require_once('includes/class.content.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

//записей на страницу
$strings_per_page = 15;
//от какой записи вести отчет
$from=0;

$this_page->addBreadcrumbs('Сервисы', 'service',0);
$this_page->addBreadcrumbs('Справочник', 'information', 1, Config::Get('services_breadcrumbs'));
$GLOBALS['css_set'][] = '/modules/information/styles.css';
 $docs_folder = Config::$values['docs_folders'];
Response::SetString('docs_folder', $docs_folder);

$h1 = empty($this_page->page_seo_h1) ? false : $this_page->page_seo_h1;
Response::SetString('h1', $h1);  
switch(true){
    case empty($action): // перечень всех типов справочных документов
        $list = $db->fetchall("SELECT ".$sys_tables['references_docs_categories'].".title as category_title,
                                      ".$sys_tables['references_docs_categories'].".id as category_id,
                                      IF(".$sys_tables['references_docs_categories'].".id IN (3,5,7,9),
                                        IF(".$sys_tables['references_docs_categories'].".id=9,
                                            CONCAT_WS('/','offices',".$sys_tables['references_docs_types'].".chpu_title), 
                                            ".$sys_tables['references_docs_types'].".chpu_title
                                        ), 
                                        CONCAT_WS('/',".$sys_tables['references_docs_types'].".chpu_title, ".$sys_tables['references_docs'].".chpu_title)
                                      )  as chpu_title,
                                      ".$sys_tables['references_docs_types'].".chpu_title  category_chpu_title,
                                      IF(".$sys_tables['references_docs_categories'].".id IN (3,5,7,9), ".$sys_tables['references_docs_types'].".title, ".$sys_tables['references_docs'].".title) as docs_title
                               FROM ".$sys_tables['references_docs_categories']."
                               LEFT JOIN ".$sys_tables['references_docs_types']."  ON ".$sys_tables['references_docs_types'].".id_category=".$sys_tables['references_docs_categories'].".id
                               LEFT JOIN ".$sys_tables['references_docs']."  ON ".$sys_tables['references_docs'].".id_type=".$sys_tables['references_docs_types'].".id
                               GROUP BY  docs_title
                               ORDER BY ".$sys_tables['references_docs_categories'].".id = 8 DESC,".$sys_tables['references_docs_categories'].".id, docs_title, ".$sys_tables['references_docs_types'].".title");
        Response::SetArray('list',$list);

        $module_template = 'mainpage.html';
        break;
    case $action=='offices': //категория офисы (отдельный вывод информации)
        if(empty($this_page->page_parameters[1])){ // рубрикатор для учреждений
            $list = $db->fetchall("SELECT CONCAT_WS('/','offices',".$sys_tables['references_docs_types'].".chpu_title)  as chpu_title,
                                  ".$sys_tables['references_docs_types'].".title as category_title,
                                  ".$sys_tables['references_docs_types'].".chpu_title as category_chpu_title,  
                                  ".$sys_tables['references_docs_types'].".title as docs_title,
                                  COUNT(".$sys_tables['references_docs_offices'].".id) as amount
                           FROM ".$sys_tables['references_docs_types']."
                           LEFT JOIN ".$sys_tables['references_docs_offices']."  ON ".$sys_tables['references_docs_offices'].".id_category=".$sys_tables['references_docs_types'].".id
                           WHERE ".$sys_tables['references_docs_types'].".id_category=9
                           GROUP BY ".$sys_tables['references_docs_types'].".id");
            Response::SetArray('list',$list);                
            Response::SetArray('amounts_included',true);
            $module_template = 'list.html';        
            //хлебные крошки
            $this_page->addBreadcrumbs('Учреждения', 'offices');
            //добавление title
            $new_meta = array('title'=>'Учреждения - Справочник');
            $this_page->manageMetadata($new_meta,true);
            $h1 = empty($this_page->page_seo_h1) ? 'Учреждения' : $this_page->page_seo_h1;
            Response::SetString('h1', $h1); 
        }else{ //карточка списка учреждений по типу
            $type = $db->fetch("SELECT * FROM ".$sys_tables['references_docs_types']." WHERE `chpu_title` = '".$db->real_escape_string($this_page->page_parameters[1])."'");
            if(empty($type)){
                Host::RedirectLevelUp();
                break;
            }
            //шаблон карточки
            $module_template = 'list_offices.html';
            $list = $db->fetchall("SELECT * FROM ".$sys_tables['references_docs_offices']." WHERE `id_category` = ".$type['id']);
            Response::SetArray('list',$list);
            //хлебные крошки
            $this_page->addBreadcrumbs('Учреждения', 'offices');
            $this_page->addBreadcrumbs($type['title'], $type['chpu_title']);
            //добавление title
            $new_meta = array('title'=>$type['title'].' - Учреждения');
            $this_page->manageMetadata($new_meta,true);
            $h1 = empty($this_page->page_seo_h1) ? $type['title'] : $this_page->page_seo_h1;
            Response::SetString('h1', $h1);                    
        }
        break;
    case empty($this_page->page_parameters[1]): // список типов по категориям
        $list = $db->fetchall("SELECT CONCAT_WS('/',".$sys_tables['references_docs_types'].".chpu_title, ".$sys_tables['references_docs'].".id)  as chpu_title,
                                      ".$sys_tables['references_docs_types'].".title as type_title,
                                      ".$sys_tables['references_docs_categories'].".title as category_title,
                                      ".$sys_tables['references_docs_categories'].".id as category_id,
                                      ".$sys_tables['references_docs_types'].".chpu_title as type_chpu_title,  
                                      ".$sys_tables['references_docs_categories'].".chpu_title as category_chpu_title,  
                                      ".$sys_tables['references_docs'].".title as docs_title
                               FROM ".$sys_tables['references_docs_types']."
                               LEFT JOIN ".$sys_tables['references_docs']."  ON ".$sys_tables['references_docs'].".id_type=".$sys_tables['references_docs_types'].".id
                               LEFT JOIN ".$sys_tables['references_docs_categories']."  ON ".$sys_tables['references_docs_types'].".id_category=".$sys_tables['references_docs_categories'].".id
                               WHERE ".$sys_tables['references_docs_types'].".chpu_title='".$db->real_escape_string($action)."'");
        if(!empty($list)){
            Response::SetArray('list',$list);                
            $module_template = 'list.html';        
            //хлебные крошки
            $this_page->addBreadcrumbs($list[0]['type_title'], $list[0]['category_chpu_title']);
            //добавление title
            $new_meta = array('title'=>$list[0]['type_title'].' - Справочник', 'description'=>$list[0]['type_title'], 'keywords'=>$list[0]['type_title']);
            $this_page->manageMetadata($new_meta,true);
            Response::SetString('h1', empty($this_page->page_seo_h1) ? $list[0]['type_title'] : $this_page->page_seo_h1); 
        }
        else {
            $new_url = $db->fetch("SELECT * FROM ".$sys_tables['references_docs_types']." WHERE ".$sys_tables['references_docs_types'].".code= ?", $action);
            if(!empty($new_url)) Host::Redirect('/service/information/'.$new_url['chpu_title'].'/');
            Host::RedirectLevelUp();
            break;    
        }
        break;
    case !empty($this_page->page_parameters[1]) && count($this_page->page_parameters) == 2: //карточка документа
        if(!empty($this_page->page_parameters[2])) {Host::RedirectLevelUp(); break;}
        $chpu = Convert::ToString($this_page->page_parameters[1]);
        $item = $db->fetch("SELECT    ".$sys_tables['references_docs'].".*,
                                      ".$sys_tables['references_docs_types'].".title as type_title,
                                      ".$sys_tables['references_docs_categories'].".title as category_title,
                                      ".$sys_tables['references_docs_categories'].".id as category_id,
                                      ".$sys_tables['references_docs_types'].".chpu_title as type_chpu_title,  
                                      ".$sys_tables['references_docs_categories'].".chpu_title as category_chpu_title
                               FROM ".$sys_tables['references_docs']."
                               LEFT JOIN ".$sys_tables['references_docs_types']."  ON ".$sys_tables['references_docs'].".id_type=".$sys_tables['references_docs_types'].".id
                               LEFT JOIN ".$sys_tables['references_docs_categories']."  ON ".$sys_tables['references_docs_types'].".id_category=".$sys_tables['references_docs_categories'].".id
                               WHERE ".$sys_tables['references_docs'].".chpu_title = ? AND ".$sys_tables['references_docs_types'].".chpu_title = ?",
                               $chpu, $this_page->page_parameters[0]); 
        
        if(!empty($item)){
            if($item['category_chpu_title']=='type' && $this_page->page_parameters[0]!='type') Host::Redirect('/service/information/type/'.$this_page->page_parameters[1].'/');
            //фотогалерея
            $item['content'] = preg_replace( '#\{gallery:([0-9]{1,})\-([0-9]{1,})\}#msiU', '{block photos/block/references_docs/'.$item['id'].'/\\1/\\2/}', $item['content'] );
            Response::SetArray('item',$item);
            //шаблон карточки
            $module_template = 'item.html';
            $GLOBALS['js_set'][] = '/js/slide.photogallery.js';
            $GLOBALS['css_set'][] = '/css/slide.photogallery.css';

            //хлебные крошки
            $this_page->addBreadcrumbs($item['type_title'], $item['type_chpu_title']);
            $this_page->addBreadcrumbs($item['title'], $item['id']);
            //добавление title
            $new_meta = array('title'=>$item['title'].' - '.$item['type_title'].' - Справочник','description'=>$item['title'],'keywords'=>$item['title']);
            $this_page->manageMetadata($new_meta,true);     
            //предыдущая и след. статья
            $prev = $db->fetchall("SELECT    ".$sys_tables['references_docs'].".*,
                                          ".$sys_tables['references_docs_types'].".title as type_title,
                                          ".$sys_tables['references_docs_types'].".chpu_title as type_chpu_title
                               FROM ".$sys_tables['references_docs']."
                               LEFT JOIN ".$sys_tables['references_docs_types']."  ON ".$sys_tables['references_docs'].".id_type=".$sys_tables['references_docs_types'].".id
            
                               WHERE   
                                       ".$sys_tables['references_docs'].".`id` = (SELECT ".$sys_tables['references_docs'].".`id` FROM ".$sys_tables['references_docs']." WHERE ".$sys_tables['references_docs'].".`id` < ".$item['id']." AND ".$sys_tables['references_docs'].".id_type=".$item['id_type']." ORDER BY id DESC LIMIT 1)
                                        OR 
                                       ".$sys_tables['references_docs'].".`id` = (SELECT MAX(".$sys_tables['references_docs'].".`id`) FROM ".$sys_tables['references_docs']." WHERE ".$sys_tables['references_docs'].".id_type=".$item['id_type'].")
                               ORDER BY ".$sys_tables['references_docs'].".`id` ASC
            ");         
            $next = $db->fetchall("SELECT    ".$sys_tables['references_docs'].".*,
                                          ".$sys_tables['references_docs_types'].".title as type_title,
                                          ".$sys_tables['references_docs_types'].".chpu_title as type_chpu_title
                               FROM ".$sys_tables['references_docs']."
                               LEFT JOIN ".$sys_tables['references_docs_types']."  ON ".$sys_tables['references_docs'].".id_type=".$sys_tables['references_docs_types'].".id
            
                               WHERE   
                                       ".$sys_tables['references_docs'].".`id` = (SELECT ".$sys_tables['references_docs'].".`id` FROM ".$sys_tables['references_docs']." WHERE ".$sys_tables['references_docs'].".`id` > ".$item['id']." AND ".$sys_tables['references_docs'].".id_type=".$item['id_type']." ORDER BY id ASC LIMIT 1)
                                        OR 
                                       ".$sys_tables['references_docs'].".`id` = (SELECT MIN(".$sys_tables['references_docs'].".`id`) FROM ".$sys_tables['references_docs']." WHERE ".$sys_tables['references_docs'].".id_type=".$item['id_type'].")
                               ORDER BY ".$sys_tables['references_docs'].".`id` DESC
            ");         
            Response::SetArray('prev_next',array(0=>$prev[0],1=>$next[0]));
            if(file_exists(ROOT_PATH.'/'.Config::$values['docs_folders'].'/'.$item['fileattach'])){
                $pathinfo = pathinfo(ROOT_PATH.'/'.Config::$values['docs_folders'].'/'.$item['fileattach']);
                if(!empty($pathinfo['extension'])) Response::SetString('extension', $pathinfo['extension']);
                $size = filesize(ROOT_PATH.'/'.Config::$values['docs_folders'].'/'.$item['fileattach']);
                Response::SetString('filesize', number_format(($size * .0009765625) * .0009765625,2));
            } else unset($item['fileattach']);
            Response::SetString('h1', empty($this_page->page_seo_h1) ? $item['title'] : $this_page->page_seo_h1);            
        } else {
            $new_url = $db->fetch("SELECT * FROM ".$sys_tables['references_docs_types']." WHERE ".$sys_tables['references_docs_types'].".code= ?", $this_page->page_parameters[0]);
            $item = $db->fetch("SELECT * FROM ".$sys_tables['references_docs']." WHERE ".$sys_tables['references_docs'].".id = ?", $chpu);
            if(!empty($new_url) || !empty($item)) Host::Redirect('/service/information/'.(!empty($new_url)?$new_url['chpu_title']:$this_page->page_parameters[0]).'/'.$item['chpu_title'].'/');

            Host::RedirectLevelUp();
            break;    
        }
        break;
    default:
        Host::RedirectLevelUp();
        break;
}
$GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
$GLOBALS['css_set'][] = '/css/estate_search.css';

if(!empty($this_page->page_parameters[0])){
    switch($this_page->page_parameters[0]){
        case 'commercial_real_estate':
            Response::SetString('tgb_type','commercial');
            break;
        case 'estate_country':
            Response::SetString('tgb_type','country');
            break;
        default:
            Response::SetString('tgb_type','live');
            break;
    }
}
?>