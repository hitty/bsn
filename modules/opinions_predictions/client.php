<?php
require_once('includes/class.paginator.php');
require_once('includes/class.opinions.php');

// мэппинги модуля


$GLOBALS['css_set'][] = '/modules/opinions_predictions/style.css';
$GLOBALS['js_set'][] = '/modules/search/script.js';
$GLOBALS['css_set'][] = '/modules/search/style.css';

//записей на страницу
$strings_per_page = 12;
Response::SetString('img_folder',Config::$values['img_folders']['opinions_expert_profiles']);
//от какой записи вести отчет
$from=0;
$action = empty( $this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$types = [
    1   =>  'opinions',
    2   =>  'predictions',
    3   =>  'interview'
];
$opinions = new Opinions();

// обработка общих action-ов
switch(true){
    case $this_page->real_url=='interviews':
        Host::Redirect("/interview/"); 
        break;
    case strstr( $this_page->real_url,'predictions') != '':
        Host::Redirect("/opinions/".(!empty( $this_page->page_parameters[0])?$this_page->page_parameters[0].'/' : '').(!empty( $this_page->page_parameters[1])?$this_page->page_parameters[1].'/' : '').(!empty( $this_page->page_parameters[2])?$this_page->page_parameters[2].'/' : '') ); 
        break;
    //////////////////////////////////////////////////////////////////////////////
    // редирект /live/ -> /residental/ 
    //////////////////////////////////////////////////////////////////////////////
    case $action=='live':
        Host::Redirect( str_replace( '/live', '/residental', $this_page->real_url ) ); 
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // блок на главной для мнений и прогнозов
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='block':
        if(!$this_page->first_instance || $ajax_mode) {
            //$this_page->page_cache_time = Config::$values['blocks_cache_time']['opinions_block']; 
           $count = 3;//кол-во мнений и прогнозов
            switch(true){
                case $this_page->page_parameters[1] == 'all':
                    $estate_type=''; 
                    $count = 3;    
                    break;
                case $this_page->page_parameters[1] == 'last':
                    $estate_type=''; 
                    $count = empty( $this_page->page_parameters[2]) ? 3 : $this_page->page_parameters[2];
                    break;
                case $this_page->page_parameters[1] == 'analytics':
                    $estate_type=''; 
                    $count = 1;    
                    break;
                case !empty( $this_page->page_parameters[1]):
                    $estate_type = $this_page->page_parameters[1];
                    break;
            }
            $where = array( $sys_tables['opinions_predictions'].".date <= NOW()");
            if (!empty( $estate_type) ) $where[]=$sys_tables['opinions_expert_estate_types'].".url='".$estate_type."'";
            $where[] = $sys_tables['opinions_expert_profiles'].".id_main_photo > 0";
            
            $list = $opinions->getList( $count, 0, implode(" AND ",$where) );
            
            if( !empty ( $this_page->page_parameters[3]) ) Response::SetString( $this_page->page_parameters[3],$this_page->page_parameters[3])        ;
            Response::SetArray('list',$list);
            $module_template='block.html';
            //время жизни memcache
             $ajax_result['ok'] = true;
        } else $this_page->http_code=404;
        break;
     
     ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // архив
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'archive':
        $page_parameters = Request::GetParameters();
        Response::SetInteger( 'current_year', !empty( $page_parameters['year'] ) ? $page_parameters['year'] : date('Y') );
        Response::SetInteger( 'current_month', !empty( $page_parameters['month'] ) ? $page_parameters['month'] : date('m') );
        Response::SetString( 'category', !empty( $page_parameters['category'] ) ? $page_parameters['category'] : '' );
        Response::SetString( 'region', !empty( $page_parameters['region'] ) ? $page_parameters['region'] : '' );
        
        //список фильтров (месяц-год)
        $date_list = $opinions->getMonthsList( 
            !empty( $page_parameters['category'] ) ? $page_parameters['category'] : ''
        );
        Response::SetArray( 'date_list', $date_list );

        Response::SetBoolean( 'payed_format', true );
        
        $module_template = "archive.html";
        break;
    //////////////////////////////////////////////////////////////////////////////
    // карточка мнения / прогноза
    //////////////////////////////////////////////////////////////////////////////
        case !empty( $this_page->page_parameters[1]):
        
        //Матвей:просмотр по chpu_title
        if(count( $this_page->page_parameters)>2) {$this_page->http_code=404; break;}

        if(Validate::isDigit( $this_page->page_parameters[1]) ){
            $res = $db->fetch("SELECT chpu_title FROM ".$sys_tables['opinions_predictions']." WHERE id=?", $this_page->page_parameters[1]);
            if(empty( $res) ){$this_page->http_code=404; break;}
            Host::Redirect("opinions/".$this_page->page_parameters[0]."/".$res['chpu_title']);   
        }  else {
            $id = preg_split("/\_/",$this_page->page_parameters[1],2);
            if(!Validate::isDigit( $id[0]) ){$this_page->http_code=404; break;}
            $id = $id[0];
        }
        //Матвей:end     

        $opinions = new Opinions( $this_page->module_parameters['type']);
        $item = $opinions->getItem( $id);
        if ( (!$item)||(!empty( $this_page->page_parameters[2]) )){
            $this_page->http_code=404;
            break;
        }
        
        Response::SetArray('item',$item);

        //комментарии новости
        $GLOBALS['js_set'][] = '/modules/comments/script.js';
        $GLOBALS['css_set'][] = '/modules/comments/style.css';
        $comments_data = array('page_url'    =>  '/'.$this_page->real_url.'/',
                               'id_parent'   =>  $id,
                               'parent_type' =>  $item['type_url']
        );
        Response::SetArray('comments_data', $comments_data);  
        
                   
        Response::SetString('type_url',$this_page->page_url);
        //хлебные крошки
        $this_page->addBreadcrumbs( $item['estate_title'], $action);
        //title
        $h1 = $item['annotation'];
        $this_page->manageMetadata(
            array(
                'title' =>  $item['expert_title'] . ' - ' . $item['agency_title'] . ' - ' . $h1 . ' - ' . $item['type_title'],
                'keywords'  =>  $h1,
                'description'   =>  $h1
            ), true);
        $module_template='item.html';
        
        //похожие карточки
        $where = array( $sys_tables['opinions_predictions'].".date <= NOW()");
        $where[] = $sys_tables['opinions_predictions'].".type = ".$item['type'];
        $where[] = $sys_tables['opinions_predictions'].".id_estate_type = ".$item['id_estate_type'];
        $where[] = $sys_tables['opinions_predictions'].".id != ".$id;
        $similar_list = $opinions->getList(3,0,implode(" AND ",$where) );  
        Response::SetArray('similar_list',$similar_list);
        // увеличение счетчика просмотров
        $db->query("UPDATE ".$sys_tables['opinions_predictions']." SET `views_count`=`views_count`+1 WHERE `id`=?",$id);
        $excluded_ids = [];
        if( !empty ( $similar_list) ) 
            array_walk( 
                $similar_list, 
                function( $value, $key ) use( &$excluded_ids ){
                    $excluded_ids[$key] = $value['id'];    
                }
            );
        //3 предыдущих и 3 следующих
        $where = [];
        $prev_next = $opinions->getPrevNext( $item['type'], $item['id_estate_type'], $id, $excluded_ids);
        Response::SetArray('prev_next_list',$prev_next);
        
        Response::SetArray('open_graph', array(
                'title' => $item['annotation'] . ' ' . $item['expert_title'] . ', ' . $item['expert_company'] . ', ' . $item['agency_title'],
                'description' => $item['annotation'],
                'image' => 'https://' . Host::$host . '/' . Config::$values['img_folders']['opinions_expert_profiles'] . '/med/' . $item['experts_subfolder'] . '/' . $item['experts_photo'],
                'url' => 'https://' . Host::$host . '/' . $item['type_url'] . '/' . $item['estate_url'] . '/' . $item['chpu_title'] . '/'
            )
        );
        break;
    
    //////////////////////////////////////////////////////////////////////////////
    // главная страница, страница со списком категории 
    //////////////////////////////////////////////////////////////////////////////
    case $action=='residental':
    case $action=='build':
    case $action=='commercial':
    case $action=='country':
    case empty( $action) && empty( $this_page->page_parameters[1]):
        //определение текущего номера страницы
        $page = Request::GetInteger('page',METHOD_GET);
        // создаем пагинатор для списка
        if( empty( $page ) ) {
            if( isset( $page ) ) Host::Redirect( '/' . $this_page->requested_path . '/?page=1' );
            $page = 1;
        } elseif( $page < 1 ) Host::Redirect( '/' . $this_page->requested_path . '/?page=1' );
        else Response::SetBoolean( 'noindex', true ); //meta-тег robots = noindex
        if ( empty( $page ) ) $page=1;
        //формирование фильтра для пагинатора
        $where = array( $sys_tables['opinions_predictions'].".date <= NOW()");
        //////////////////////////////////////////////////////////////////////////////
        // списки мнений и прогнозов, смешанный список, в зависимости от url
        //////////////////////////////////////////////////////////////////////////////
        if ( empty( $this->page_parameters[1] ) ) {
            //список
            $opinions = new Opinions( $this_page->module_parameters['type'] );
            if (!empty( $opinions->content_type) ){
                if ( $opinions->content_type!=4 && ( $opinions->content_type == 3 ) ) {
                    $where[]=$sys_tables['opinions_predictions'].".type=".$opinions->content_type;
                    if (!empty( $action) ){
                        $estate_type = $db->fetch("SELECT `id` FROM ".$sys_tables['opinions_expert_estate_types']." WHERE `url` = '".$action."'");
                        if( $action!='all') $where[] = $sys_tables['opinions_predictions'].".id_estate_type = ".$estate_type['id'];
                    }
                }
            }
            else{
                $this_page->http_code=404;
                break;
            }
            $where[] = $sys_tables['opinions_expert_profiles'].".id_main_photo > 0";
            $get_parameters = Request::GetParameters( METHOD_GET );
            if( !empty ( $action ) ) {
                $category = $db->fetch( " SELECT * FROM " . $sys_tables['opinions_expert_estate_types'] . " WHERE url = ?", $action );
                if( empty( $category ) ) Host::RedirectLevelUp();
                $where[] = $sys_tables['opinions_predictions'] . ".id_estate_type = " . $category['id'];   
            }
            if( !empty ( $get_parameters['path'] ) )    unset($get_parameters['path']);
            if( !empty ( $get_parameters['month'] ) )   $where[] = "MONTH(" . $sys_tables[ 'opinions_predictions' ] . ".`date`) = " . $get_parameters['month'];
            if( !empty ( $get_parameters['year'] ) )    $where[] = "YEAR(" . $sys_tables[ 'opinions_predictions' ] . ".`date`) = " . $get_parameters['year'];

            $paginator = new Paginator( $sys_tables['opinions_predictions'], $strings_per_page, false, "SELECT count(*) as items_count FROM content.opinions LEFT JOIN ".$sys_tables['opinions_expert_profiles']." ON ".$sys_tables['opinions_expert_profiles'].".id = ".$sys_tables['opinions_predictions'].".id_expert WHERE ".implode(" AND ",$where) );
            if( $paginator->pages_count>0 && $paginator->pages_count<$page){
                Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count );
                exit(0);
            }
            //формирование url для пагинатора
            $paginator->link_prefix = '/'.$this_page->requested_path.'/?page=';
            if( $paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get( $page) );
            }
            $list = $opinions->getList( $strings_per_page,$paginator->getFromString( $page),implode(' AND ',$where) );
            Response::SetArray('list',$list);
            Response::SetString( 'type', $this_page->page_url );
           
            $h1 =  empty( $this_page->page_seo_title) ? $list[0]['type_title'] : $this_page->page_seo_title;
            $title = array( 'title' => $list[0]['type_title'], 'keywords' => $list[0]['type_title'], 'description' => $list[0]['type_title'] );
            Response::SetString( 'mainpage_h1', $h1 );
            $this_page->manageMetadata( $title, true );

            //добавление хлебных крошек и тайтла
            if( !empty ( $action) ){
                if ( ( $opinions->content_type!=4 ) ){
                    //хлебные крошки
                    $this_page->addBreadcrumbs( $list[0]['estate_title'], $action);
                    //title
                    $meta = $list[0]['type_title'].' по '.$list[0]['estate_title_genitive'].' недвижимости';
                    $h1 = empty( $this_page->page_seo_title) ? $meta : $this_page->page_seo_title;
                    $title = array('title' => $meta.' - '.$list[0]['type_title'], 'keywords' => $meta, 'description' => $meta);
                    Response::SetString('h1', $h1);
                    $this_page->manageMetadata( $title,true);
                }
                else{
                    //хлебные крошки
                    $this_page->addBreadcrumbs('Мнения, Прогнозы, интервью', $action);
                    //title
                    $title = array('title' =>'Мнения, Прогнозы, интервью: все');
                    $this_page->manageMetadata( $title,'add');
                }
            }
        }
        else $this_page->http_code=404;
        
        if (!empty( $this_page->page_parameters[1]) ) $this_page->http_code=404;
        if( $ajax_mode) {
            $ajax_result['ok'] = true;           
            Response::SetString('ajax_url', $paginator->link_prefix . ( $page + 1 ) );
            //показать еще  
            if( $paginator->pages_count!=$page) Response::SetBoolean('ajax_pagination', true);
        }    
        
        //пописка на рассылку
        $GLOBALS['js_set'][] ="/js/subscription.js";
        $subscription = array(
            'email' => $auth->isAuthorized() ? $auth->email : '',
            'url' => '/news/subscribe/',
            'text' => 'Подписаться на рассылку'
        );
        Response::SetArray( 'subscription', $subscription );    

        //список фильтров (месяц-год)
        if( !empty( $category ) ) Response::SetArray( 'category', $category );
        $date_list = $opinions->getMonthsList( !empty( $category ) ? $category['url'] : '' );
        Response::SetArray('date_list', $date_list );
         
        //поиск
        Response::SetBoolean( 'payed_format', true );
        Response::SetString( 'content_type', 'opinions' );
        $module_template = empty( $ajax_mode) ? 'list.html' : 'block.html';
        break;
    case isPage( $action):
        Host::Redirect("/".$this_page->page_url."/?page=".getPage( $action ) ); 
        break;
    default:
        $this_page->http_code=404;
}                 
?>