<?php
require_once('includes/class.paginator.php');
require_once('includes/class.estate.statistics.php');
require_once('includes/class.specoffers.php');

// мэппинги модуля

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
//Инициализация класса статистики
specOffers::Init();
// обработка общих action-ов
switch(true){
    case $action=='last': //список последних для Пинголы
    case $action=='lastpng': //список последних для Пинголы
        if(date('G')>8 && $action!='last'){
            $list = $db->fetchall("
                SELECT `id` ,  `title` ,  `direct_link` ,  'external' as `link_type`, `photo` ,  `get_pixel` ,  `main_img_src` ,  `day_limit`
                FROM (
                    SELECT 
                            ".$sys_tables['spec_offers_objects'].".`id`,  
                            ".$sys_tables['spec_offers_objects'].".`title`,  
                            IF(".$sys_tables['spec_offers_objects'].".`direct_link`='',
                                CONCAT_WS('/','https://www.bsn.ru', 'estate',".$sys_tables['spec_offers_categories'].".url,".$sys_tables['spec_offers_objects'].".id,''),
                                ".$sys_tables['spec_offers_objects'].".`direct_link`
                            ) as `direct_link`, 
                            'external' as `link_type`, 
                            IF(".$sys_tables['spec_offers_objects'].".main_img_link='',
                                CONCAT('".Host::getImgUrl()."' ,'".Config::$values['img_folders']['spec_offers']."/',".$sys_tables['spec_offers_objects'].".main_img_src),
                                ".$sys_tables['spec_offers_objects'].".main_img_link
                            ) as photo,
                            ".$sys_tables['spec_offers_objects'].".`get_pixel`, 
                            ".$sys_tables['spec_offers_objects'].".`main_img_src`,   
                            ".$sys_tables['spec_objects_credits'].".day_limit
                    FROM  ".$sys_tables['spec_offers_objects']." 
                    LEFT JOIN ".$sys_tables['spec_offers_categories']." ON ".$sys_tables['spec_offers_categories'].".id = ".$sys_tables['spec_offers_objects'].".id_category
                    RIGHT JOIN ".$sys_tables['spec_objects_credits']." ON ".$sys_tables['spec_objects_credits'].".id_object = ".$sys_tables['spec_offers_objects'].".id 
                    AND  ".$sys_tables['spec_offers_objects'].".`date_start` <= CURDATE() 
                    AND  ".$sys_tables['spec_offers_objects'].".`date_end` > CURDATE() 
                    AND  `day_limit` > (SELECT IFNULL(COUNT(".$sys_tables['spec_objects_stats_click_day'].".id),0) as ct FROM ".$sys_tables['spec_objects_stats_click_day']." WHERE `from` = 3 AND ".$sys_tables['spec_objects_stats_click_day'].".id_parent = ".$sys_tables['spec_offers_objects'].".id)
                    AND ".$sys_tables['spec_offers_objects'].".`id` > 0
                    GROUP BY ".$sys_tables['spec_offers_objects'].".`id`
                ) as a
                WHERE a.`id` > 0
                GROUP BY  a.`id`
            ");    
        } else $list = false; 
       if(!empty($list) && count($list)>1){      
            shuffle($list);
            $spec_objects_list = array_splice($list,0,3);
            if(count($spec_objects_list)<3){ //добавление баннеров с минимальными показателями
                $where = "";
                if(!empty($spec_objects_list)){
                    foreach($spec_objects_list as $k=>$item) $ids[] = $item['id'];
                    $where = " AND ".$sys_tables['spec_offers_objects'].".id NOT IN (".implode(",",$ids).")";
                }
                $list = $db->fetchall("
                    SELECT 
                            ".$sys_tables['spec_offers_objects'].".`id`,  
                            ".$sys_tables['spec_offers_objects'].".`title`,  
                            IF(".$sys_tables['spec_offers_objects'].".`direct_link`='',
                                CONCAT_WS('/','https://www.bsn.ru', 'estate',".$sys_tables['spec_offers_categories'].".url,".$sys_tables['spec_offers_objects'].".id,''),
                                ".$sys_tables['spec_offers_objects'].".`direct_link`
                            ) as `direct_link`, 
                            'external' as `link_type`, 
                            IF(".$sys_tables['spec_offers_objects'].".main_img_link='',
                                CONCAT('".Host::getImgUrl()."' ,'".Config::$values['img_folders']['spec_offers']."/',".$sys_tables['spec_offers_objects'].".main_img_src),
                                ".$sys_tables['spec_offers_objects'].".main_img_link
                            ) as photo,
                            ".$sys_tables['spec_offers_objects'].".`get_pixel`, 
                            ".$sys_tables['spec_offers_objects'].".`main_img_src`,   
                            IFNULL(COUNT(".$sys_tables['spec_objects_stats_click_day'].".id),0) as clickamount
                    FROM  ".$sys_tables['spec_offers_objects']." 
                    LEFT JOIN ".$sys_tables['spec_offers_categories']." ON ".$sys_tables['spec_offers_categories'].".id = ".$sys_tables['spec_offers_objects'].".id_category
                    LEFT JOIN ".$sys_tables['spec_objects_stats_click_day']." ON ".$sys_tables['spec_objects_stats_click_day'].".id_parent = ".$sys_tables['spec_offers_objects'].".id
                    AND  ".$sys_tables['spec_offers_objects'].".`date_start` <= CURDATE() 
                    AND  ".$sys_tables['spec_offers_objects'].".`date_end` > CURDATE()
                    AND  ".$sys_tables['spec_offers_objects'].".`direct_link` !=''
                    ".$where."
                    GROUP BY ".$sys_tables['spec_offers_objects'].".id
                    ORDER BY COUNT(".$sys_tables['spec_objects_stats_click_day'].".id), RAND()
                    LIMIT 0, ".(8-count($spec_objects_list)*2));    
                    shuffle($list);
                    $list = array_splice($list,0,3-count($spec_objects_list));
                    foreach($list as $k=>$item) $spec_objects_list[] = $list[$k];
            }
            shuffle($spec_objects_list);
            Response::SetArray('list',$spec_objects_list);
        }
        $this_page->page_template = '/templates/spec_objects_clear.html';
        $module_template = 'block.pingola.html';
        Response::SetString('action', $action);
        break;
    case $action == 'main_page':
        if(!$this_page->first_instance || $ajax_mode) {
            $module_template = 'mainpage.html';
            $list = specOffers::getList('first_page_flag');
            Response::SetArray('list', $list);
            //запись статистики показов
            $objects = $packets = array();
            foreach($list as $item){
                if($item['type']=='object') $objects[]=$item['id'];
                elseif($item['type']=='packet') $packets[]=$item['id'];
            }
            if((!empty($objects) || !empty($packets)) && !Host::$is_bot) specOffers::Statistics('show',$objects,$packets);  
            
            $ajax_result['ok'] = true;
        }
        break;
    case $action == 'show': //запись статистики показов
            if($ajax_mode && empty(Host::$is_bot)){
                $offers = Request::GetArray('offers',METHOD_POST);
                if(!empty($offers)){
                    $objects = $packets = array();
                    foreach($offers as $item){
                        if($item['type']=='object') $objects[]=$item['id'];
                        elseif($item['type']=='packet') $packets[]=$item['id'];
                    }
                    if(!empty($objects) || !empty($packets) && !Host::$is_bot) specOffers::Statistics('show',$objects,$packets); 
                    $ajax_result['ok'] = true;
                }
            } else $this_page->http_code=404;
        break;
    case $action == 'click': //запись статистики кликов
            if($ajax_mode && empty(Host::$is_bot)){
                $id = Request::GetString('id',METHOD_POST);
                $type = Request::GetString('type',METHOD_POST);
                $objects = $packets = array();
                if($type=='object') $objects[]=$id;
                elseif($type=='packet') $packets[]=$id;


                $time = $db->fetch("SELECT TIMESTAMPDIFF(MINUTE, `datetime`, NOW()) as `time` FROM ".$sys_tables['spec_'.$type.'s_stats_click_day']." WHERE id_parent = ? AND ip = ? ORDER BY id DESC",$id, Host::getUserIp());
                
                if(empty($time) || $time['time']>=2){
                    $from = Request::GetString('from',METHOD_POST);
                    $ref = Request::GetString('ref',METHOD_POST);
                    specOffers::Statistics('click',$objects,$packets,$from,$ref,Host::getUserIp(),$_SERVER['HTTP_USER_AGENT']); 
                    $ajax_result['ok'] = true;
                    //сохранение статистики показов для метки
                    $session_marker = Session::GetString('marker');
                    if(!empty($session_marker)) $db->querys("INSERT INTO ".$sys_tables['markers_stats_click_day']." SET id_parent=?",$session_marker);
                }
            } else $this_page->http_code=404;
        break;
    case $action == 'block': // блок ТГБ (carousel)
                //определение рынка недвижимости для ТГБ
                if(!empty($this_page->page_parameters[1])){
                    $type = $this_page->page_parameters[1];
                    if($type == 'map'){ //ТГБ на карте
                        $estate_types = array('live','country','commercial','build');
                        $tgb_list = specOffers::getList('inestate_flag', $estate_types[mt_rand(0,3)]);
                        shuffle($tgb_list);
                        $tgb_list = array_splice($tgb_list,0,1);
                        if(!Host::$is_bot) specOffers::Statistics('show',$tgb_list); 
                    } else {
                        $tgb_list = specOffers::getList('inestate_flag' ,$type);
                        if(!empty($this_page->page_parameters[2]) && $this_page->page_parameters[2] == 'estate_list'){
                            shuffle($tgb_list);
                            $tgb_list = array_splice($tgb_list,0,2);
                            if(!Host::$is_bot) specOffers::Statistics('show',$tgb_list); 
                        }
                    }
                    if(!empty($this_page->page_parameters[3]) && Validate::isDigit($this_page->page_parameters[3])) Response::SetInteger('ga_number',$this_page->page_parameters[3]==4?1:2);
                    if(!empty($tgb_list)){
                        //если что-то есть, назначаем цвета
                        $colors = Config::$values['tgb_colors'];
                        shuffle($colors);
                        Response::SetArray('carousel_colors',$colors);
                        if(!empty($this_page->page_parameters[2]) && $this_page->page_parameters[2] == 'estate_list') $module_template = "block.html";
                        else  $module_template = "carousel.html";
                        if(!empty($this_page->page_parameters[2]) && Validate::isDigit($this_page->page_parameters[2])) {
                            $tgb_list_new = array();
                            $objects = $packets = array();
                            for($k=0; $k<$this_page->page_parameters[2]; $k++) {
                                $tgb_list_new[] =  $tgb_list[$k];
                                if($tgb_list[$k]['type']=='object') $objects[]=$tgb_list[$k]['id'];
                                elseif($tgb_list[$k]['type']=='packet') $packets[]=$tgb_list[$k]['id'];
                            }
                            if(!Host::$is_bot) specOffers::Statistics('show',$objects,$packets); 
                            $tgb_list = $tgb_list_new;
                            $module_template = "block.one.html";
                        }

                        Response::SetArray('tgb_list', $tgb_list);
                        
                        if($ajax_mode){
                            $template = new Template($module_template,$this_page->module_path);
                            $html = $template->Processing();
                            $ajax_result['ok'] = true;
                            $ajax_result['html'] = $html;
                        }
                        
                    } else $module_template='clearcontent.html';
                } else $module_template='clearcontent.html';
        break;
    default:
        //определение категории спецпредложения
        $shift_url = str_replace('estate/','',$this_page->page_url);
        //тип недвижимости для блока ТГБ с каруселью
        $type = explode('/',$shift_url);
        $category = $db->fetch("SELECT * FROM ".$sys_tables['spec_offers_categories']." WHERE `url` = '".$shift_url."'");
        if(!empty($category)){
            $GLOBALS['css_set'][] = '/modules/spec_offers/style.css';
            if(!empty($this_page->page_parameters[0])){ // определяем страницу список или карточка
                //релирект со страницы результатов поиска коттеджных поселков
                if($this_page->page_parameters[0]=='results' && $shift_url=='country/complex')  Host::Redirect("/cottedzhnye_poselki/");
                
                $id = $packet = false;
                if(!empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1])){ //карточка
                    $id = Convert::ToInt($this_page->page_parameters[1]);
                    $packet = $db->fetch("SELECT `id`, `title` FROM ".$sys_tables['spec_offers_packets']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
                    if(empty($packet) || !empty($this_page->page_parameters[2])) {$this_page->http_code=404; break; }
                } elseif(Validate::isDigit($this_page->page_parameters[0])) { //карточка
                    $id = Convert::ToInt($this_page->page_parameters[0]);
                    if(!empty($this_page->page_parameters[1])) {$this_page->http_code=404; break; }
                } else{ // список для пакета
                    $packet = $db->fetch("SELECT `id`, `title` FROM ".$sys_tables['spec_offers_packets']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
                }  
                if($id){  // получение данных для карточки
                   if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
                   
                   //данные объекта
                   $item = specOffers::getItem($id, !empty($packet)?$packet['id']:false);
                   if(empty($item)) {$this_page->http_code=404; break; }
                   Response::SetArray('item', $item);
                   //запись статистики  показа
                   specOffers::Statistics('click',array($id));
                   //фотогалерея
                   Response::SetString('img_folder',Config::$values['img_folders']['spec_offers_objects']);
                   $photos = Photos::getList('spec_offers_objects',$id);
                   Response::SetArray('photos',$photos);

                   //хлебные крошки 
                   //если объект в пакете
                   if($packet) $this_page->addBreadcrumbs($packet['title'], $this_page->page_parameters[0]);
                    //Хлебные крошки
                    if(!empty($type[1])) $this_page->addBreadcrumbs($category['title_exclusive'], $type[1],2);
                    $this_page->addBreadcrumbs($packet['title'], $this_page->page_parameters[0]);
                    $this_page->addBreadcrumbs($item['title'], $id);
                   //добавление title
                   $new_meta = array('title'=>$item['title'].' - Спецпредложения', 'keywords'=>$item['title'], 'description'=>$item['title']);
                   $this_page->manageMetadata($new_meta, true);
                   $h1[] = $item['title'];
                   
                   switch($shift_url){
                       case 'country/complex':
                        Response::SetString('tgb_type',$type[0].'_'.$type[1]);
                        break;
                       default:
                        Response::SetString('tgb_type',$type[0]);
                        break;
                   }
                   
                   $module_template = 'item.html'; 
                }elseif($packet){ // получение данных для списка в пакете
                   $list = specOffers::getPacketList($packet['id']);
                   if(empty($list)){
                       $this_page->http_code = 404;
                       break;
                   }
                   Response::SetArray('list', $list);
                    //запись статистики показов
                    $objects = $packets = array();
                    foreach($list as $item){
                        if($item['type']=='object') $objects[]=$item['id'];
                        elseif($item['type']=='packet') $packets[]=$item['id'];
                    }
                    if((!empty($objects) || !empty($packets)) && !Host::$is_bot) specOffers::Statistics('show',$objects,$packets);                       
                    $module_template = 'list.html'; 

                    //Хлебные крошки
                    if(!empty($type[1])) $this_page->addBreadcrumbs($category['title_exclusive'], $type[1],2);
                    $this_page->addBreadcrumbs($packet['title'], $this_page->page_parameters[0]);
                    //добавление title
                    $new_meta = array('title'=>$packet['title'].' - Спецпредложения', 'keywords'=>$packet['title'], 'description'=>$packet['title']);
                    $this_page->manageMetadata($new_meta,true);
                    
                    $h1[] = $packet['title'];
                    
                }else $this_page->http_code=404; 
                            
            } else {  // заглавная страница спецпредложения
                $list = specOffers::getList('base_page_flag' ,false, in_array($category['id'], array(1,2)) ? '1,2' : $category['id'] ,true);
                Response::SetArray('list', $list);
                //запись статистики показов
                $objects = $packets = array();
                foreach($list as $item){
                    if($item['type']=='object') $objects[]=$item['id'];
                    elseif($item['type']=='packet') $packets[]=$item['id'];
                }
                if((!empty($objects) || !empty($packets)) && !Host::$is_bot) specOffers::Statistics('show',$objects,$packets);
                
                //Хлебные крошки
                if(!empty($type[1])) $this_page->addBreadcrumbs($category['title_exclusive'], $type[1],2);
                //дополнительные блоки для карты БЦ
                if($shift_url=='business'){
                    
                    $GLOBALS['js_set'][] ='/js/yandex.map.js';
                    Response::SetString('bc_map',true);
                    Response::SetString('tgb_type',$type[0]);                    
                } else Response::SetString('tgb_type',$type[0]);
                //подключение Карусели-ТГБ
                $h1[] = $category['title_estate'].' недвижимость';
                $module_template = 'list.html';
            }
            $h1 = empty($this_page->page_seo_h1) ? implode($h1) : $this_page->page_seo_h1;
            Response::SetString('h1', $h1);
            
        } else $this_page->http_code=404; 
        break;
}


?>
