<?php
require_once('includes/class.paginator.php');
// мэппинги модуля

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
Response::SetString('img_folder',Config::$values['img_folders']['galleries']);
//записей на страницу
$strings_per_page = 15;
//от какой записи вести отчет
$from=0;
switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    //карточка галереи
    ////////////////////////////////////////////////////////////////////////////////////////////////////////        
    case !empty($this_page->page_parameters[0]) && empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[0]):
        $id = $this_page->page_parameters[0];
        if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
        
        $db->query("UPDATE ".$sys_tables['galleries']." SET `views_count`=`views_count`+1 WHERE `id`=?",$id);
        $item = $db->fetch("SELECT *, IF(YEAR(".$sys_tables['galleries'].".`datetime`) < Year(CURDATE()),DATE_FORMAT(".$sys_tables['galleries'].".`datetime`,'%e %M %Y'),DATE_FORMAT(".$sys_tables['galleries'].".`datetime`,'%e %M, %k:%i')) as normal_date
                            FROM  ".$sys_tables['galleries']." WHERE id = ?",
                            $id
        ); 
        if(empty($item)){
            $this_page->http_code=404;
            break;            
        }
        Response::SetArray('item',$item);
        //фотогалерея
        $photos = Photos::getList('galleries',$id);
        Response::SetArray('photos',$photos);
        //шаблон карточки галереи
        $module_template = 'item.html';
        //хлебные крошки
        $new_meta = [];
        $this_page->manageMetadata(array('title'=>$item['title'].(!empty($new_meta)?' - '.implode(' ',$new_meta):''),'keywords'=>$item['title'],'description'=>$item['title']),true);
        // карточка галереи
        $this_page->addBreadcrumbs($item['title'], $item['id']);
        break;
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // список
    ////////////////////////////////////////////////////////////////////////////////////////////////////////          
    case empty($this_page->page_parameters[0]): 
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        $get_parameters = [];
        Response::SetString('sorting_url', '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'sortby=');
        Response::SetInteger('sortby', $sortby); 
        $module_template = 'list.html';
        $h1 = empty($this_page->page_seo_h1) ? 'Фотогалереи' : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);        
        $page = Request::GetInteger('page',METHOD_GET);
        //редирект с несуществующих пейджей
         if(empty($page)){
            if(isset($page)) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
            $page = 1;
        }
        elseif($page<1) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
        else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['galleries'], $strings_per_page);
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count);
            exit(0);
        }
        $paginator->link_prefix = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page=';
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        // контент списка галереи
        $list = $db->fetchall("SELECT   ".$sys_tables['galleries'].".*,
                                IF(YEAR(".$sys_tables['galleries'].".`datetime`) < Year(CURDATE()),DATE_FORMAT(".$sys_tables['galleries'].".`datetime`,'%e %M %Y'),DATE_FORMAT(".$sys_tables['galleries'].".`datetime`,'%e %M, %k:%i')) as normal_date, 
                                LEFT(".$sys_tables['galleries'].".content,200)as `content_short`,
                                ".$sys_tables['galleries_photos'].".`name` as `photo`, LEFT (".$sys_tables['galleries_photos'].".`name`,2) as `subfolder`
                       FROM  ".$sys_tables['galleries']."
                       LEFT JOIN ".$sys_tables['galleries_photos']." ON ".$sys_tables['galleries_photos'].".id = ".$sys_tables['galleries'].".id_main_photo
                       WHERE ".$sys_tables['galleries'].".datetime <= NOW()
                       GROUP BY ".$sys_tables['galleries'].".id
                       ORDER BY ".$sys_tables['galleries'].".datetime DESC
                       LIMIT ".$paginator->getFromString($page).",".$strings_per_page);
        Response::SetArray('list',$list); 
        Response::SetInteger('sortby', $sortby);               
        break;
    default:
        $this_page->http_code=404;
        break;
}
//подключение ТГБ (carousel) tgb_type 200613
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