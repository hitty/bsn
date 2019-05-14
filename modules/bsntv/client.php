<?php
require_once('includes/class.paginator.php');
require_once('includes/class.content.php');
//вынесено сюда, иначе подключалось с ошибками
$GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.min.js';
$GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
$GLOBALS['js_set'][] = '/js/jui_new/datepicker-ru.js';

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

Response::SetString('img_folder',Config::$values['img_folders']['bsntv']);

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
//записей на страницу
$strings_per_page = 20;
//от какой записи вести отчет
$from=0;
$user_ip =  Host::getUserIp();
switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // список новостей на главную страницу
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='block':  
        if(!$this_page->first_instance || $ajax_mode) {
            //принимаемые значения тегов
            $tags_array = array(
                            'live'=>'жилая недвижимость',
                            'build'=>'строящаяся недвижимость',
                            'commercial'=>'коммерческая недвижимость',
                            'country'=>'загородная недвижимость',
                            'inter'=>'зарубежная недвижимость',
                            'events'=>'мероприятия',
                            'all'=>'Все новости'
                            );
            
            $module_template = 'custom_block.html';
            $count=4; // кол-во записей
            $id_category = false; // id категории
            $this_page->page_cache_time = Config::$values['blocks_cache_time']['news_block'];
            $where = false;
            $action = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false;
            if(!empty($action) && !empty($this_page->page_parameters[2]) && $this_page->page_parameters[2] == 'mainpage') {
                $bsntv_type = 'all';
                $module_template = 'block.html';
                $category = $db->fetch("SELECT * FROM ".$sys_tables['bsntv_categories']." WHERE code = ?", $action);
                $where = 'status < 3 AND id_main_photo > 0';
                if(!empty($category)) $where .= ' AND '.$sys_tables['bsntv'].".id_category = ".$category['id'];
                if(!empty($this_page->page_parameters[3]) && $this_page->page_parameters[3] == 'new'){
                    $count = 6;
                    $module_template = 'block.new.html';
                }
            } elseif(empty($action)) { //блок на главной
                $bsntv_type = 'all';
                $module_template = 'block.html';
            }  elseif(Validate::isDigit($action)) { //последние N новостей
                $bsntv_type = 'all'; 
                $count = Convert::ToInt($action);
            } elseif($action=='category' && Validate::isDigit($this_page->page_parameters[2])) { //новости по категориям
                $bsntv_type = 'all'; 
                $id_category=Convert::ToInt($this_page->page_parameters[2]);
            } elseif($action=='bsntv_mainpage') { //новости на главной новостей и списком
                $bsntv_type = 'all'; 
                $count = 4;
                $where = [];
                $where[] = "id_main_photo > 0"; 
                if(!empty($this_page->page_parameters[2])) {
                    switch($this_page->page_parameters[2]){
                        /*
                        //убрано 090916, меняются условия выборки
                        case 'days': 
                            $order_by = $sys_tables['bsntv'].".datetime >= NOW() - INTERVAL 24 HOUR DESC, DATEDIFF(DATE(". $sys_tables['bsntv'].".datetime), CURDATE()) DESC, ".$sys_tables['bsntv'].".views_count DESC";
                            $where[] = $sys_tables['bsntv'].".datetime >= NOW() - INTERVAL 24 HOUR";
                            break;
                        case 'week': 
                            $order_by = $sys_tables['bsntv'].".datetime >= NOW() - INTERVAL 24*7 HOUR DESC, ".$sys_tables['bsntv'].".views_count DESC";
                            $where[] = $sys_tables['bsntv'].".datetime >= NOW() - INTERVAL 24*7 HOUR";
                            break;
                        */
                        case 'year':
                            $order_by = $sys_tables['bsntv'].".views_count DESC";
                            $where[] = $sys_tables['bsntv'].".datetime >= NOW() - INTERVAL 1 YEAR";
                            break;
                        //по умолчанию выбран месяц
                        case 'month':
                        default:
                            $order_by = $sys_tables['bsntv'].".views_count DESC, ".$sys_tables['bsntv'].".datetime DESC";
                            $where[] = $sys_tables['bsntv'].".datetime >= NOW() - INTERVAL 24*30 HOUR";
                            break;
                    }
                }
                
                if(!empty($this_page->page_parameters[3])) $where[] = "id_category = ".$this_page->page_parameters[3];
                $where = implode(" AND ",$where);
                $module_template = 'block.html';
            } else {break;}
            
            if(!empty($this_page->page_parameters[2]) && $this_page->page_parameters[2]=='video') {
                $count = 1;
                $module_template = 'block.html';
            } elseif(!empty($this_page->page_parameters[2]) && $this_page->page_parameters[2]=='bsntv') {
                $count = 4;
                $where = "status < 3 AND id_main_photo > 0"; 
                $module_template = 'block.html';
            }
            $bsntv = new BsntvContent();

            //получение списка нововстей 
            $list = $bsntv_type == 'all' ? 
                        $list = $bsntv->getBsntvList($count,0,$id_category,false,true,$where,!empty($order_by) ? $order_by : $sys_tables['bsntv'].".status DESC, ".$sys_tables['bsntv'].".datetime DESC, ".$sys_tables['bsntv'].".id DESC")
                        :
                        $bsntv->getBsntvListByTag($count,0,$tags_array[$bsntv_type],false,false,true);
            Response::SetString('img_folder',Config::$values['img_folders']['bsntv']);
            Response::SetArray('list',$list);        
            if($ajax_mode) $ajax_result['ok']=true;
        } else Host::RedirectLevelUp();
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // список по тегу 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='tags' && !empty($this_page->page_parameters[1]) && empty($this_page->page_parameters[2]):
        if( count($this_page->page_parameters) > 2) Host::RedirectLevelUp();
        $tag_id = Convert::ToInt($this_page->page_parameters[1]);
        if(empty($tag_id)) {
            Host::Redirect('/bsntv/');
            break;
        }
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        $scroll_to_last = Request::GetInteger('scroll-to-last',METHOD_GET);
        $get_parameters = [];
        if(empty($sortby)) $sortby = 1;
         //meta-тег robots = noindex
        $orderby = "";
        
        switch($sortby){
            case 5:
                // архивные
                $time_limit_for_popular = "DATEDIFF(NOW(),".$sys_tables['bsntv'].".`datetime`) > 90";
                $orderby .= $sys_tables['bsntv'].".`datetime` DESC"; 
                break;
            case 4:
                // по популярности по убыванию
                $time_limit_for_popular = "DATEDIFF(NOW(),".$sys_tables['bsntv'].".`datetime`) <= 90";
                $orderby .= $sys_tables['bsntv'].".views_count DESC"; 
                break;
            case 1:
            default:
                // по дате по убыванию
                $time_limit_for_popular = "DATEDIFF(NOW(),".$sys_tables['bsntv'].".`datetime`) <= 90";
                $orderby .= $sys_tables['bsntv'].".`datetime` DESC, views_count DESC"; 
                break;
        }
        
        switch($sortby){
            case 1:
                $items_block_title = "Последняя аналитика";
                $new_items_next_status = 0;
                $popular_items_next_status = 4;
                $archive_items_next_status = 5;
            break;
            case 4:
                $items_block_title = "Популярная аналитика";
                $new_items_next_status = 1;
                $popular_items_next_status = 0;
                $archive_items_next_status = 5;
            break;
            case 5:
                $items_block_title = "Архив аналитики";
                $new_items_next_status = 1;
                $popular_items_next_status = 4;
                $archive_items_next_status = 0;
            break;
        }
        
        Response::SetString('items_block_title',$items_block_title);
        Response::SetString('new_items_sortlink','/'.$this_page->requested_path.'/?scrollto=content-list'.(!empty($new_items_next_status) ? "&sortby=".$new_items_next_status : ""));
        Response::SetString('popular_items_sortlink','/'.$this_page->requested_path.'/?scrollto=content-list'.(!empty($popular_items_next_status) ? "&sortby=".$popular_items_next_status : ""));
        Response::SetString('archive_items_sortlink','/'.$this_page->requested_path.'/?scrollto=content-list'.(!empty($archive_items_next_status) ? "&sortby=".$archive_items_next_status : ""));
        
        Response::SetString('sorting_url', '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'sortby=');
        Response::SetInteger('sortby', $sortby);
        $bsntv = new BsntvContent();

        $tag = $db->fetch("SELECT * FROM ".$sys_tables['content_tags']." WHERE `id` = ".$tag_id." AND `id_category` = 4");
        if(empty($tag)){
            Host::Redirect('/bsntv/');
            break;
        }
        //добавление title
        $tag_title = '"'.mb_strtoupper(mb_substr($tag['title'],0,1,'UTF-8'),'UTF-8').mb_strtolower(mb_substr($tag['title'],1,strlen($tag['title']),'UTF-8'),'UTF-8').'"';
        $h1 = empty($this_page->page_seo_h1) ? 'Новости БСН-ТВ по теме '.$tag_title : $this_page->page_seo_h1;
        $new_meta = array('title'=>'Новости БСН-ТВ по теме '.$tag_title.' - БСН.ру',
                          'keywords'=>$h1, 
                          'description'=>"Новости БСН-ТВ по теме: ".preg_replace('/^"|"$/','',$tag_title).".");
        Response::SetString('h1', $h1);
        $this_page->manageMetadata($new_meta, true);
        $module_template = 'list.html';

        $page = Request::GetInteger('page',METHOD_GET);
        //редирект с несуществующих пейджей
         if(empty($page)){
            if(isset($page)) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
            $page = 1;
        }
        elseif($page<1) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
         //meta-тег robots = noindex

        $where = ' id_tag = '.$tag_id.(!empty($time_limit_for_popular) ? $time_limit_for_popular : "");
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['bsntv_tags'], $strings_per_page, $where);
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count);
            exit(0);
        }
        //формирование url для пагинатора
        $paginator->link_prefix = '/'.$this_page->requested_path.'/?page=';
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        //список новостей
        $bsntv_content = $bsntv->getBsntvListByTag($strings_per_page,$paginator->getFromString($page),$tag['title'],false,false,false,$orderby,$time_limit_for_popular);
        Response::SetArray('list',$bsntv_content);
        break;
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // карточка новости
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case !empty($this_page->page_parameters[1]):
        
        //Матвей:просмотр новости по chpu_title
        if(count($this_page->page_parameters)>3) {Host::RedirectLevelUp(); break;}

        if(Validate::isDigit($this_page->page_parameters[1])){
            $res = $db->fetch("SELECT chpu_title FROM ".$sys_tables['bsntv']." WHERE id=?", $this_page->page_parameters[1]);
            if(empty($res)){Host::RedirectLevelUp(); break;}
            Host::Redirect("bsntv/".$this_page->page_parameters[0]."/".$res['chpu_title']);   
        }  else {
            $bsntv_id = preg_split("/\_/",$this_page->page_parameters[1],2);
            if(!Validate::isDigit($bsntv_id[0])){Host::RedirectLevelUp(); break;}
            $bsntv_id = $bsntv_id[0];
        }
        //Матвей:end
        
        $bsntv = new BsntvContent();
        if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
        require('includes/class.tags.php');
        $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
        $GLOBALS['js_set'][] = '/js/slide.photogallery.js';
        $GLOBALS['css_set'][] = '/css/slide.photogallery.css';
        
        $GLOBALS['js_set'][] = '/js/video-player/script.js';
        $GLOBALS['css_set'][] = '/js/video-player/style.css';
        
        $category = $db->fetch("SELECT * FROM ".$sys_tables['bsntv_categories']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
        // увеличение счетчика просмотров
        $db->query("UPDATE ".$sys_tables['bsntv']." SET `views_count`=`views_count`+1 WHERE `id`=?",$bsntv_id);
        
        $bsntv_content = $bsntv->getBsntvItem($this_page->page_parameters[1]);      
        $bsntv_content['content'] = preg_replace( '#\{gallery:([0-9]{1,})\-([0-9]{1,})\}#msiU', '{block photos/block/bsntv/'.$bsntv_content['id'].'/\\1/\\2/}', $bsntv_content['content'] );
        
        //коды для ссылок-сниппетов на другие статью
        preg_match_all('/(?<=\{article_link\s)(\d+((\s|&nbsp;)*(r|l))?)(?=\})/sui', $bsntv_content['content'], $matches);
        if(!empty($matches) && !empty($matches[0])){
            foreach($matches[0] as $k=>$i){
                
                preg_match('/r/',$i,$align);
                $id = preg_replace('/[^\d]/','',$i);
                $align = (count($align) > 0?" ".($align[0] == 'r'?" right":""):"");
                
                //читаем информацию по статье
                $chpu_title = $db->fetch("SELECT chpu_title FROM ".$sys_tables['bsntv']." WHERE id = ?",$id);
                if(empty($chpu_title)){
                    $bsntv_content['content'] = str_replace('{article_link ' + $i + '}','',$bsntv_content['content']);
                }else{
                    $tpl = new Template("link_snippet.html",$this_page->module_path);
                    $article_info = $bsntv->getBsntvItem($chpu_title['chpu_title']);
                    Response::SetArray('item',$article_info);
                    $link_snippet = $tpl->Processing();
                    $bsntv_content['content'] = str_replace('{article_link '.$i.'}','<div class="article-snippet content-last-data'.$align.'" data-id="'.$id.'">'.$link_snippet.'</div>',$bsntv_content['content']);
                }
            }
        }
        
        Response::SetArray('item',$bsntv_content);
        if(empty($bsntv_content) || empty($category)){
            Host::RedirectLevelUp();
            break;
        }    
        //комментарии новости
        $GLOBALS['js_set'][] = '/modules/comments/script.js';
        $GLOBALS['css_set'][] = '/modules/comments/style.css';
        $comments_data = array('page_url'    =>  '/'.$this_page->real_url.'/',
                              'id_parent'   =>  $bsntv_id,
                              'parent_type' =>  'bsntv'
                            );
        Response::SetArray('comments_data', $comments_data);
        
        //теги новости
        $linkedTags = Tags::getLinkedTags($bsntv_id, $sys_tables['bsntv_tags']);
        if(!empty($linkedTags)){
            //вывод блока строки для соответствуюющих тегов
            foreach($linkedTags as $k=>$tag) if(in_array($tag['id'],array(44,554))) Response::SetBoolean('build_block',true);
            Response::SetArray('tags',$linkedTags);                
        }
        
        //фотогалерея
        $photos = Photos::getList('bsntv',$bsntv_id);
        Response::SetArray('photos',$photos);
        //шаблон карточки новости
        $module_template = 'item.html';
        //хлебные крошки
        
        $new_meta = [];
        if(!empty($bsntv_content['category_title_genitive'])) $new_meta[] = $bsntv_content['category_title_genitive']; // категория
        $this_page->manageMetadata(array('title'=>$bsntv_content['title'].(!empty($new_meta)?' - '.implode(' ',$new_meta):''),
                                         'keywords'=>$bsntv_content['title'],
                                         'description'=>$bsntv_content['title'].". ".$bsntv_content['category_title_genitive']." от портала BSN.ru"),true);
        //предыдущая-следущая новость
        $prev_next_list = $bsntv->getBsntvPrevNext($bsntv_content['id_category'], false, $bsntv_content['id']);
        Response::SetArray('prev_next_list',$prev_next_list);
        //другие новости этой рубрики
        $last_news = $bsntv->getBsntvList(4,0,false,false,false,$sys_tables['bsntv'].'.id!='.$bsntv_content['id'].' AND '.$sys_tables['bsntv'].'.id_category='.$bsntv_content['id_category'], $sys_tables['bsntv'].'.datetime DESC');
        Response::SetArray('last_news',$last_news);
        
        //метаданные для шаринга
        Response::SetArray('open_graph', array(
                'title' => $bsntv_content['title'],
                'description' => $bsntv_content['content_short'],
                'image' => 'http://' . Host::$host . '/' . Config::$values['img_folders']['bsntv'] . '/big/' . $bsntv_content['subfolder'] . '/' . $bsntv_content['photo'],
                'url' => 'http://' . Host::$host . '/bsntv/' . $bsntv_content['category_code'] . '/' . $bsntv_content['chpu_title'] . '/'
            )
        );        
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // главная страница новостей, категория или категория
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case empty($action) || !empty($this_page->page_parameters[0]):
        $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
        $bsntv = new BsntvContent();
        //список категорий
        $categories = $bsntv->getCategoriesList(true);
        Response::SetArray('categories',$categories);

        if(!empty($this_page->page_parameters[0]) && count($this_page->page_parameters)==1){ // категория 
            $action = $this_page->page_parameters[0];
            switch(true){
                case isPage($action): //редирект на GET паджинацию
                    Host::Redirect("/bsntv/".$this_page->page_parameters[0]."/?page=".getPage($action));
                break;
                default: // список новостей Категория-Регион
                    $category = $db->fetch("SELECT * FROM ".$sys_tables['bsntv_categories']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
                    if(empty($category)){
                        Host::RedirectLevelUp();
                        break;
                    }
                    Response::SetArray('category', $category);
                break;
            }
        } else if(!empty($this_page->page_parameters[0]) && count($this_page->page_parameters)==1){ // список новостей по Категории или по Региону
            $category = $db->fetch("SELECT * FROM ".$sys_tables['bsntv_categories']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
            if(empty($category)){
                Host::RedirectLevelUp();
                break;
            }
            Response::SetArray('category', $category);
        } else if(count($this_page->page_parameters)>0){
            Host::RedirectLevelUp();
            break;
        }
        
        $h1 = [];
        //хлебные крошки
        if(!empty($category)){ // категория
            //добавление title
            $new_meta[] = $category['title_genitive'];
        }
        if(!empty($new_meta))$this_page->manageMetadata(array('title'=> !empty($new_meta) ? implode(' ',$new_meta) : ''.' БСН-ТВ','description'=>implode(' ',$new_meta)),true);
        
        $h1 = empty($this_page->page_seo_h1) ? !empty($new_meta) ? implode(' ',$new_meta) : '' : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);   

        //список последних новостей
        // сортировка
        $sortby = Request::GetInteger('sortby', METHOD_GET);
        $scrollto = Request::GetInteger('scrollto', METHOD_GET);
        $get_parameters = [];
        $get_parameters['scrollto'] = (!empty($scrollto) ? $scrollto : "content-list");
        if(empty($sortby)) $sortby = 1;
        
        //подсчитываем количества по вкладкам
        $counts = $db->fetchall("SELECT 'new' AS type, COUNT(*) AS amount 
                                 FROM ".$sys_tables['bsntv']." 
                                 WHERE DATEDIFF(NOW(),".$sys_tables['bsntv'].".`datetime`) <= 90 ".(!empty($category) ? " AND id_category = ".$category['id'] : "")." AND published = 1
                                 UNION
                                 SELECT 'archive' AS type, COUNT(*) AS amount 
                                 FROM ".$sys_tables['bsntv']." 
                                 WHERE DATEDIFF(NOW(),".$sys_tables['bsntv'].".`datetime`) > 90 ".(!empty($category) ? " AND id_category = ".$category['id'] : "")." AND published = 1",'type');
        $counts['popular'] = $counts['new'];
        $counts = array_map(function($v){return $v['amount'];},$counts);
        Response::SetArray('counts',$counts);
        $types_count = count(array_filter($counts));
        Response::SetInteger('types_count',$types_count);
        if(empty($counts['new'])) $sortby = 5;
        
         //meta-тег robots = noindex
        $orderby = "";
        
        switch($sortby){
            case 5:
                // архивные
                $time_limit_for_popular = "DATEDIFF(NOW(),".$sys_tables['bsntv'].".`datetime`) > 90";
                $orderby .= $sys_tables['bsntv'].".`datetime` DESC"; 
                break;
            case 4:
                // по популярности по убыванию
                $time_limit_for_popular = "DATEDIFF(NOW(),".$sys_tables['bsntv'].".`datetime`) <= 90";
                $orderby .= $sys_tables['bsntv'].".views_count DESC"; 
                break;
            case 1:
            default:
                // по дате по убыванию
                $time_limit_for_popular = "DATEDIFF(NOW(),".$sys_tables['bsntv'].".`datetime`) <= 90";
                $orderby .= $sys_tables['bsntv'].".`datetime` DESC, views_count DESC"; 
                break;
        }
        
        switch($sortby){
            case 1:
                $items_block_title = "Последнее видео";
                $new_items_next_status = 0;
                $popular_items_next_status = 4;
                $archive_items_next_status = 5;
            break;
            case 4:
                $items_block_title = "Популярное видео";
                $new_items_next_status = 1;
                $popular_items_next_status = 0;
                $archive_items_next_status = 5;
            break;
            case 5:
                $items_block_title = "Архив видео";
                $new_items_next_status = 1;
                $popular_items_next_status = 4;
                $archive_items_next_status = 0;
            break;
        }
        
        Response::SetString('items_block_title',$items_block_title);
        Response::SetString('new_items_sortlink','/'.$this_page->requested_path.'/?scrollto=content-list'.(!empty($new_items_next_status) ? "&sortby=".$new_items_next_status : ""));
        Response::SetString('popular_items_sortlink','/'.$this_page->requested_path.'/?scrollto=content-list'.(!empty($popular_items_next_status) ? "&sortby=".$popular_items_next_status : ""));
        Response::SetString('archive_items_sortlink','/'.$this_page->requested_path.'/?scrollto=content-list'.(!empty($archive_items_next_status) ? "&sortby=".$archive_items_next_status : ""));
        
        Response::SetString('sorting_url', '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'sortby=');
        Response::SetInteger('sortby', $sortby); 
        // кол-во элементов в списке
        $count = Request::GetInteger('count', METHOD_GET);            
        if(!empty($count)) $get_parameters[] = 'count='.$count;
        else $count = Cookie::GetInteger('View_count');
        if(empty($count)) {
            $count = Config::$values['view_settings']['strings_per_page'];
            Cookie::SetCookie('View_count', Convert::ToString($count), 60*60*24*30, '/');
        }
        //страница
        $page = Request::GetInteger('page',METHOD_GET);
        //редирект с несуществующих пейджей
         if(empty($page)){
            if(isset($page)) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
            $page = 1;
        } elseif($page<1) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
         //meta-тег robots = noindex

        //для категории все статьи обнуляем id_category
        $where = array('status=1');
        if(!empty($category)) $where[] = "id_category = ".$category['id'];
        if($sortby == 5) $where[] = (!empty($time_limit_for_popular) ? $time_limit_for_popular : "");
        
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables['bsntv'], $count, implode(" AND ",$where));
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count);
            exit(0);
        }
        //формирование url для пагинатора
        $get_parameters['sortby'] =  $sortby;
        $paginator->link_prefix = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page=';
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        unset($get_parameters['scrollto']);
        //список новостей
        $list = $bsntv->getBsntvList($count, 
                                     $paginator->getFromString($page), 
                                     !empty($category)?$category['id']:false, 
                                     false, 
                                     false, 
                                     false, 
                                     $orderby,
                                     false,
                                     (!empty($time_limit_for_popular) ? $time_limit_for_popular : ""));
        Response::SetArray('list', $list);
        echo $module_template = 'mainpage.html';
        //блок поиска по новостям
        //$GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        //$GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/search/script.js';
        $GLOBALS['css_set'][] = '/modules/search/style.css';
        //предустановка поиска по новостям
        Response::SetString('selected_type_value','bsntv');
        
        break;
    default:
        Host::RedirectLevelUp();
        break;
}

//хлебные крошки
$this_page->clearBreadcrumbs();
$types_breadcrumbs = array(
                                    'news' => 'Новости',
                                    'analytics' => 'Аналитика'
                                    
);
$this_page->addBreadcrumbs( $this_page->page_title, 'bsntv', 0, $types_breadcrumbs);
if(!empty($category) && !empty($this_page->page_parameters[0])) {
    
    $category_list = $db->fetchall("SELECT CONCAT('bsntv/', code) as url, title as title FROM ".$sys_tables['bsntv_categories']." WHERE id != ?", 'url', $this_page->page_parameters[0]);
    $category_breadcrumbs = $category_list;    
    $this_page->addBreadcrumbs($category['title'], $this_page->page_parameters[0], 1, $category_breadcrumbs);
}
?>