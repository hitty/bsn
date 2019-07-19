<?php
require_once('includes/class.paginator.php');
require_once('includes/class.content.php');
//вынесено сюда, иначе подключалось с ошибками
$GLOBALS['css_set'][] = '/modules/content/style.css';
$GLOBALS['js_set'][] = '/modules/content/subscribe.js';
//тип новости
$content_type = $this_page->page_url;
if( empty($content_type) || !in_array($content_type, array('blog', 'bsntv', 'doverie', 'news', 'articles', 'media', 'analytics', 'longread') ) ) Host::RedirectLevelUp();
Response::SetString('content_type', $content_type);
//инициализация класса
if($content_type != 'media') $content = new Content($content_type);
//получение урла
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
if(!empty(Config::$values['img_folders'][$content_type]) )Response::SetString( 'img_folder' , Config::$values['img_folders'][$content_type] );

////////////////////////////////////////////////////////////////////////////////////////////////////////
// редирект со старых статей 
////////////////////////////////////////////////////////////////////////////////////////////////////////
if( $content_type == 'articles' ) {
    $old_articles = array( 'analitics_articles' );
    $new_articles = array( 'liveestate' );
    if( !empty( $this_page->page_parameters[0] ) && in_array( $this_page->page_parameters[0], $old_articles)) {
        $index = array_search( $this_page->page_parameters[0], $old_articles );
        Host::Redirect( '/articles/' . $new_articles[ $index ] . '/' . ( !empty( $this_page->page_parameters[1] ) ? $this_page->page_parameters[1] . '/' : '' ) );
    }
} else if( $content_type == 'longread' ) {
    if( empty( $this_page->page_parameters[0] ) ) Host::Redirect( '/articles/longread/' )  ;
    else {
        $content_id = preg_split( "/\_/", $this_page->page_parameters[0], 2 );
        if( !Validate::isDigit( $content_id[0] ) ) Host::RedirectLevelUp();
        $content_id = $content_id[0];
        $item = $db->fetch( " SELECT * FROM " . $sys_tables['articles'] . " WHERE id_longread = ? ", $content_id );
        if( !empty( $item ) ) Host::Redirect( '/articles/longread/' . $item['chpu_title'] . '/' );
        else Host::RedirectLevelUp();
    }
}


switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // блоки
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='block':  
        if(!$this_page->first_instance || $ajax_mode) {
            $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
            switch( $action ){
                ////////////////////////////////////////////////////////////////////////////////////////////////////////
                // хаб
                ////////////////////////////////////////////////////////////////////////////////////////////////////////
                case 'hub':

                    $main = new Content( $content_type == 'media' ? 'news' : $content_type );
                    //новость дня
                    $main_item = $main->getList( 1, 0, false, false, 'status = 2', 'id DESC' );
                    if( !empty( $main_item ) ) $main_item = $main_item[0];
                    //партнерский материал
                    if( $content_type == 'media' || $content_type == 'articles' ) {
                        $articles = new Content( 'articles' );
                        $article_partner = $articles->getList( 1, 0, false, false, 'promo != 2', 'id DESC' );
                        $article_partner = !empty( $article_partner ) ? $article_partner = [0] : false;
                    }
                    $count = !empty( $this_page->page_parameters[2] ) && Validate::isDigit( $this_page->page_parameters[2] ) ? $this_page->page_parameters[2] : 0;
                    // список всего контента
                    // кол-во блоков
                    $hub_count = !empty( $count ) ? $count : ( !empty( $main_item ) ? ( !empty( $article_partner) ? 5 : 7 ) : ( !empty( $article_partner) ? 8 : 9 ) );
                    //отбивка блоков
                    $breaks = !empty( $main_item ) ? ( !empty( $article_partner) ? array( 2, 5 ) : array( 2, 5 ) ) : ( !empty( $article_partner) ? array( 3, 6 ) : array( 3, 6 ) ) ;
                    Response::SetArray( 'breaks', $breaks );
                    //получение списка
                    Media::Init();
                    $list = Media::List( 
                        $hub_count, 
                        !empty( $main_item['id'] ) ? $main_item['id'] : false, 
                        !empty( $article_partner['id'] ) ? $article_partner['id'] : false  
                    );
                    
                    if( !empty( $main_item) ) {
                        Response::SetArray( 'main_item', $main_item);
                        $main_item['main_item'] = 1;
                        array_unshift( $list, $main_item );
                    }
                    
                    if( !empty( $article_partner) ) {
                        Response::SetArray( 'article_partner', $article_partner);
                        $article_partner['article_partner'] = 1;
                        array_push( $list, $article_partner );
                    }
                    $count = 9;
                    if( !empty( $this_page->page_parameters[2] ) ) Response::SetString( 'hub_params', $this_page->page_parameters[2] );
                    Response::SetBoolean( 'first_big', true );
                    break;
                default:
                    $count = 4; // кол-во записей
                    $id_category = false; // id категории
                    $this_page->page_cache_time = Config::$values['blocks_cache_time']['content_block'];
                    $where = array($sys_tables[$content_type] . ".status != 4 ");
                    $action = !empty($this_page->page_parameters[1]) ? $this_page->page_parameters[1] : false;
                    $id_region = 0;
                    
                    $where[] = $sys_tables[$content_type] . ".id_main_photo > 0 ";
                    $where[] = $sys_tables[$content_type] . ". `datetime` < NOW() ";
                    $order = $sys_tables[$content_type] . ".status = 2 DESC, " . $sys_tables[$content_type] . ".datetime DESC, " . $sys_tables[$content_type].".views_count DESC";
                    switch($action){
                        case 'popular':
                            $count = 8;
                            $order = $sys_tables[$content_type] . ".status = 2 DESC, " . $sys_tables[$content_type].".views_count DESC";
                            switch($content_type){
                                case 'news': $days = 14; break;
                                case 'articles': $days = 3430; break;
                                case 'bsntv': $days = 260; break;
                                case 'doverie': $days = 260; break;
                                case 'blog': $days = 260; break;
                            }
                            $where[] = "NOW() - INTERVAL " . $days ." DAY < `datetime` ";
                            //первая большая фотка
                            Response::SetBoolean( 'first_big', true );

                            break;
                        case 'last':
                            //кол-во записей в блоке
                            if(!empty($this_page->page_parameters[2]) ) {
                                switch (true){
                                    case $this_page->page_parameters[2] == 'mainpage': 
                                        $count = 6; 
                                        break;
                                    case $this_page->page_parameters[2] == 'content_mainpage': 
                                        $count = 4; 
                                        break;
                                    case Validate::isDigit($this_page->page_parameters[2]):
                                        $count = $this_page->page_parameters[2];
                                        break;
                                    
                                }
                            } 
                            break;
                    }
                    
                    if(!empty($this_page->page_parameters[2])) {
                         
                        if( empty( $this_page->page_parameters[3] ) || $this_page->page_parameters[3] != 'allsmall' ) {
                            $category = $db->fetch("SELECT * FROM ".$sys_tables[$content_type . '_categories']." WHERE code = ?", $this_page->page_parameters[2]);
                            if(!empty($category)) $where[] = $sys_tables[$content_type].".id_category = ".$category['id'];
                                if( !empty( $this_page->page_parameters[3] ) && $content_type == 'news' ) {
                                    $region = $db->fetch("SELECT * FROM ".$sys_tables[$content_type . '_regions']." WHERE code = ?", $this_page->page_parameters[3]);
                                    if(!empty($region)) $where[] = $sys_tables[$content_type].".id_region = ".$region['id'];
                                }
                        } else Response::SetBoolean( 'allsmall', true );//все сниппеты - обычные
                    }
                    //получение списка нововстей 
                    $list = $content->getList( $count, 0, $id_category, $id_region, implode(" AND ", $where), $order);
                    Response::SetBoolean( 'first_big', false );
                    break;
            }
            if( !empty( $list ) ) {
                Response::SetArray( 'list' , $list);       
            }
            $module_template = 'block.html';
            Response::SetInteger( 'count', $count );
            if($ajax_mode) $ajax_result['ok']=true;
        } else Host::RedirectLevelUp();
        break;    
  
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // главная страница с контентом  - Media
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case !empty($this_page->module_parameters['page']) && $this_page->module_parameters['page'] == 'media':
        $GLOBALS['js_set'][] = '/modules/search/script.js';
        $GLOBALS['css_set'][] = '/modules/search/style.css';
        $GLOBALS['js_set'][] ="/js/subscription.js";
        
        //лента новостей
        $news = new Content( 'news' );
        $news_list = $news->getList( 30, 0, false, false, $sys_tables['news'] . ".datetime <= NOW() " );
        Response::SetArray( 'news_list', $news_list );
        
        $module_template = 'mainpage.content.html';
        Response::SetBoolean( 'payed_format', true );
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // клики по кнопкам
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='click': 
        if($ajax_mode){
            $id = Request::GetInteger('id', METHOD_POST);
            if(!empty($id)){
                $ajax_result['ok'] = true;
                if(!Host::$is_bot) $res = $db->query("INSERT INTO ".$sys_tables['content_stats_day_clicks']." SET `id_parent` = ? , type = ( SELECT id FROM " . $sys_tables['content_types'] . " WHERE content_type = ? ), ip = ?, browser = ?, ref = ?", 
                                                        $id, $content_type, Host::getUserIp(),$db->real_escape_string($_SERVER['HTTP_USER_AGENT']),Host::getRefererURL()
                );
                $ajax_result['ok'] = $res;
            }
        } else $this_page->http_code = 404;
        break;     
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // промоблок
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'promo':
        $id = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        $item = $content->getPromoList(false, $id);
        Response::SetArray('pitem', $item[0]);
        $ajax_result['ok'] = true;
        $module_template = 'promo.block.item.html';
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // тесты
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'test':
        $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
        switch($action){
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Вопрос
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            case 'question': 
                $step = Request::GetInteger('step', METHOD_POST);
                $id_parent = Request::GetInteger('id_parent', METHOD_POST);
                $question = $content->getTestList($id_parent, $step);
                if(!empty($question)) Response::SetArray('question', $question[0]);
                //общие данные по статье
                $item = $content->getItem( false, $id_parent );
                Response::SetArray('item', $item); 
                //общее кол-во воппрос теста
                $list = $content->getTestList($id_parent);
                Response::SetArray( 'list', $list );
                Response::SetInteger('total_questions', count( $list ) );
                $ajax_result['ok'] = true;
                $module_template = 'test.item.question.html';
                break;            
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Ответ
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            case 'answer':
                $step = Request::GetInteger('step', METHOD_POST);
                $id_parent = Request::GetInteger('id_parent', METHOD_POST);
                $id_answer = Request::GetInteger('id_answer', METHOD_POST);
                $id_question = Request::GetInteger('id_question', METHOD_POST);
                
                $question = $content->getTestList($id_parent, $step);
                $answers = $question[0]['questions'];

                //определение правильности ответов
                $array = [];
                foreach($answers as $k=>$answer){
                    if($answer['id'] == $id_answer) {
                        $array[$answer['id']] = array(
                            'status' => $answer['rightanswer'] == 1 ? 'right' : 'wrong',
                            'answer' => $answer['answer']
                        );
                        $right_answer = $answer['rightanswer'];
                    }
                    elseif( $answer['id'] != $id_answer && $answer['rightanswer'] == 1 ) {
                        $array[$answer['id']] = array(
                            'status' => 'right-then-wrong',
                            'answer' => $answer['answer']
                        );
                    }
                }
                //идентификация пользователя
                if( $step == 1 ){
                    $voter = $db->fetch("SELECT MAX(id_voter) as id_voter FROM " . $sys_tables['articles_test_answers']);
                    $id_voter = $voter['id_voter'] + 1;
                    Session::SetInteger($id_parent . 'test_voter', $id_voter);
                } else {
                    $id_voter = Session::GetInteger($id_parent . 'test_voter');
                    if( !empty($id_voter) ) {
                        $answer = $db->fetch(" SELECT * FROM " . $sys_tables['articles_test_answers'] . " WHERE id_parent = ? AND id_voter = ? AND id_question = ?",
                            $question[0]['id_parent'], $id_voter, $id_question    
                        );
                        if(!empty($answer)) exit();
                    }
                    
                }
                $db->query("INSERT INTO " . $sys_tables['articles_test_answers'] . " SET answer = ?, right_answer = ?, id_parent = ?, id_voter = ?",
                    $id_answer, $right_answer, $question[0]['id_parent'], $id_voter    
                );
                $ajax_result['results'] = $array;
                $ajax_result['ok'] = true;
                break;     
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Результаты теста
            ////////////////////////////////////////////////////////////////////////////////////////////////////////
            case 'results': 
                $id_parent = Request::GetInteger('id_parent', METHOD_POST);
                $id_voter = Session::GetInteger($id_parent . 'test_voter');
                
                $id_voter = Session::GetInteger($id_parent . 'test_voter');
            
                $right_answers = $db->fetch("SELECT COUNT(*) as cnt FROM " . $sys_tables['articles_test_answers'] ." WHERE right_answer = ? AND id_parent = ? AND id_voter = ?",
                    1, $id_parent, $id_voter 
                )['cnt'];
                Response::SetInteger('right_answers', $right_answers);
                //результаты
                $result = $content->getTestResultsList($id_parent, false, $right_answers);
                if(!empty($result[0])) {
                    Response::SetArray('result', !empty($result[0]) ? $result[0] : false );
                }

                //общее кол-во воппрос теста
                $total = $content->getTestList($id_parent);
                Response::SetInteger('total_questions', count( $total ) );

                //общие данные по статье
                $item = $content->getItem( false, $id_parent );
                Response::SetArray('item', $item); 
                               
                $ajax_result['ok'] = true;
                $module_template = 'test.item.results.html';
                
                $ajax_result['ok'] = true;
                if(!Host::$is_bot) $res = $db->query("INSERT INTO ".$sys_tables['content_stats_day_finish']." SET `id_parent` = ? , type = ( SELECT id FROM " . $sys_tables['content_types'] . " WHERE content_type = ? ), ip = ?, browser = ?, ref = ?", 
                                                        $id_parent, $content_type, Host::getUserIp(),$db->real_escape_string($_SERVER['HTTP_USER_AGENT']),Host::getRefererURL()
                );
                
                break;            
                       
        }
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // отписка пользователя от новостей
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action == 'unsubscribe':
        Page::setPageTemplate('templates/client_clear.html');
        $GLOBALS['css_set'][] = '/modules/content/unsubscribe.css';
        $module_template = 'unsubscribe.html'; 
        $id = Request::GetInteger('id', METHOD_GET);
        $email = Request::GetString('email', METHOD_GET);
        $code = Request::GetString('code',METHOD_GET);
        //проверяем код отписки и если что выкидываем
        if(empty($code) || $code != sha1(md5($id.$email."special!_adding"))) $this_page->http_code = 404;
        
        if(!Validate::isEmail($email)) unset($email);
        if(empty($id) || empty($email)){
            Response::SetString('error', 'noparam');
        } else {
            
            $list = $db->fetchall(" SELECT id, 'users' as type FROM ".$sys_tables['users']." WHERE id = ".$id." AND email = '".$email."' AND subscribe_news = 1
                                UNION
                                SELECT id, 'subscribed_users' as type FROM ".$sys_tables['subscribed_users']." WHERE id = ".$id." AND email = '".$email."' AND  published = 1"
                                );
            if(empty($list)){
                Response::SetString('error','nouser');
            } else {
                if (!empty($this_page->page_parameters[1]) && ($this_page->page_parameters[1] == 'end')){
                    foreach($list as $k=>$item){
                        //отписка пользователя
                        $db->query("UPDATE ".$sys_tables[$item['type']]." SET ".($item['type']=='users'?'subscribe_news':'published')." = 2 WHERE id = ".$item['id']);
                        Response::SetString('error','unsubscribed');
                    }
                } else {
                    Response::SetString('uri', '/news/unsubscribe/end/?id='.$id.'&email='.$email.'&code='.$code);
                    Response::SetString('success', 'start');
                }
            }
        }
                                                         
        Response::SetString('host','http://'.Host::$host);
        $this_page->addBreadcrumbs('Отписаться от рассылки BSN', 'unsubscribe');
        //добавление title
        $new_meta = array('title'=>'Отписаться от рассылки BSN');
        $this_page->manageMetadata($new_meta, true);        
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // подписка на новости
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='subscribe':
        if(!empty($ajax_mode)){
            $email = Request::GetString('email', METHOD_POST);
            if(!empty($email) && Validate::isEmail($email)){
                //запись в таблицу пользователей
                $res = $db->query("UPDATE ".$sys_tables['users']." SET subscribe_news = 1 WHERE email = ?", $email);
                if(empty($db->affected_rows)) 
                    $db->query(" INSERT INTO ".$sys_tables['subscribed_users']." SET email = ?, published = ?
                                 ON DUPLICATE KEY UPDATE published = ?
                    ", $email, 1, 1); 
            }
        } else Host::Redirect('/' . $content_type . '/');
    
        
        
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // лента rss
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case !empty($this_page->module_parameters['mode']) && $this_page->module_parameters['mode']=='rss':
        $module_template = 'rss_list.html';
        $item = $content->getList(30, 0, false, false);
        //убираем &laquo; и &raquo; - чтобы не было ошибок в rss
        foreach ($item as $key=>$item){
            $item[$key]['content'] = str_replace('&laquo;','«',$item[$key]['content']);
            $item[$key]['content'] = str_replace('&raquo;','»',$item[$key]['content']);
            $item[$key]['title'] = str_replace('&laquo;','«',$item[$key]['title']);
            $item[$key]['title'] = str_replace('&raquo;','»',$item[$key]['title']);
            $item[$key]['content_short'] = str_replace('«','&laquo;',$item[$key]['content_short']);
            $item[$key]['content_short'] = str_replace('»','&raquo;',$item[$key]['content_short']);
        }
        Response::SetArray('list', $item);
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
        $date_list = $content->getMonthsList( 
            !empty( $page_parameters['category'] ) ? $page_parameters['category'] : '',
            !empty( $page_parameters['region'] ) ? $page_parameters['region'] : '' 
        );
        Response::SetArray('date_list', $date_list);

        Response::SetBoolean( 'payed_format', true );
        
        $module_template = "archive.html";
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // карточка
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case 
        ( $content_type == 'news' && !empty($this_page->page_parameters[2])) || 
        ($content_type != 'news' && !empty($this_page->page_parameters[1]))  :
        
        //Матвей:просмотр новости по chpu_title
        if( $content_type == 'news' && count($this_page->page_parameters)>3) {Host::RedirectLevelUp(); break;}
        else if( $content_type != 'news' && count($this_page->page_parameters)>2) {Host::RedirectLevelUp(); break;}

        $id_part = $content_type == 'news' ? $this_page->page_parameters[2] : $this_page->page_parameters[1];
        if(Validate::isDigit( $id_part )){
            
            $res = $db->fetch("SELECT chpu_title FROM ".$sys_tables[$content_type]." WHERE id=?", $id_part);
            if(empty($res)){Host::RedirectLevelUp(); break;}
            Host::Redirect($content_type . "/".$this_page->page_parameters[0]."/" . ( !empty($sys_tables[$content_type . '_regions'] ) ? $this_page->page_parameters[1]."/" : "" ) . $res['chpu_title']);   
        }  else {
            $content_id = preg_split("/\_/",$id_part,2);
            if(!Validate::isDigit($content_id[0])){Host::RedirectLevelUp(); break;}
            $content_id = $content_id[0];
            echo "<!-- " . $content_type . "-->";
            echo "<!-- " . $this_page->page_parameters[0] . "-->";
            if( $content_type == 'doverie' ) {
                $news = $db->fetch(" SELECT * FROM " . $sys_tables['news'] ." WHERE id = ?", $content_id );
                if( !empty( $news ) ) Host::Redirect( '/news/' . $this_page->page_parameters[0] . '/' . $this_page->page_parameters[1] . '/'  . $this_page->page_parameters[2] . '/' );
            }
        }
        
        
        if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
        
        $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
        $GLOBALS['js_set'][] = '/js/slide.photogallery.js';
        $GLOBALS['css_set'][] = '/css/slide.photogallery.css';
        
        $category = $db->fetch("SELECT * FROM ".$sys_tables[$content_type . '_categories']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
        if(!empty($sys_tables[$content_type . '_regions'])) $region = $db->fetch("SELECT `id`, `code`, `title` FROM ".$sys_tables[$content_type . '_regions']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[1])."'");
        
        $item = $content->getItem(false, $content_id);      
         
        //обычная карточка
        $item['content'] = trim(preg_replace( '#\{gallery:([0-9]{1,})\-([0-9]{1,})\}#msiU', '{block photos/block/' . $content_type . '/'.$item['id'].'/\\1/\\2/}', $item['content'] ));
        
        //перебираем картинки в тексте, заменяем на штуку со всплывашкой
        preg_match_all('/\<a\shref\=\"http\:\/\/www\.bsn\.ru\/img\/uploads\/[^\>]*/sui', $item['content'], $intext_images);
        if(!empty($intext_images)){
            $intext_images = $intext_images[0];
            foreach($intext_images as $key=>$code){
                if(strstr($code,"onclick") !== false){
                    $new_code = preg_replace("/onclick\=\"[^\"]+[\"]/sui",'',$code)." class='popup-intext-image'";
                    $item['content'] = str_replace($code,$new_code,$item['content']);
                }
            }
        }
        
        //коды для ссылок-сниппетов на другие статью
        preg_match_all('/(?<=\{article_link\s)(\d+((\s|&nbsp;)*(r|l))?)(?=\})/sui', $item['content'], $matches);
        if(!empty($matches) && !empty($matches[0])){
            foreach($matches[0] as $k=>$i){
                
                preg_match('/r/',$i,$align);
                $id = preg_replace('/[^\d]/','',$i);
                $align = (count($align) > 0?" ".($align[0] == 'r'?" right":""):"");
                
                //читаем информацию по статье
                $chpu_title = $db->fetch("SELECT chpu_title FROM ".$sys_tables[$content_type]." WHERE id = ?",$id);
                if(empty($chpu_title)){
                    $item['content'] = str_replace('{article_link ' + $i + '}','',$item['content']);
                }else{
                    $tpl = new Template("link_snippet.html",$this_page->module_path);
                    $article_info = $content->getItem($chpu_title['chpu_title']);
                    Response::SetArray('item',$article_info);
                    $link_snippet = $tpl->Processing();
                    $item['content'] = str_replace('{article_link '.$i.'}','<div class="article-snippet content-last-data'.$align.'" data-id="'.$id.'">'.$link_snippet.'</div>',$item['content']);
                }
            }
        }

        //коды для рекламных блоков в лонгридах
        if( $content_type == 'articles' && $item['id_category'] == 27 ){
            preg_match_all( '#\{advert\}#msiU', $item['content'], $matches );
            if(!empty($matches) && !empty($matches[0])){
                
                $tpl = new Template( "block.longread.advert.html",$this_page->module_path );
                $advert = $content->getAdvertList( $item['id'] );
                
                Response::SetArray( 'list', $advert );
                
                $advert_snippet = $tpl->Processing();
                $item['content'] = str_replace('<p>{advert}</p>', '{advert}', $item['content'] );
                $item['content'] = str_replace('{advert}', $advert_snippet, $item['content'] );
                
            }
            $GLOBALS['js_set'][] = '/modules/content/promo.script.js';
        }
                
        $item['content'] = preg_replace('/\s{2,}/', ' ', $item['content']);
        $item['content'] = preg_replace('#\<p\>(?-i:\s++|&nbsp;?)*\<\/p\>#sui', ' ', $item['content']);
        $item['content'] = Convert::CleanHtml( $item['content'] );
        Response::SetArray('item',$item);
        
        //шаблон карточки новости
        $module_template =  'item.html';
    
        //промокарточка
        if( !empty( $item['promo'] ) ) {
            if( $item['promo'] == 1){
                $promo_list = $content->getPromoList($content_id);
                Response::SetArray('promo_list', $promo_list);
                $GLOBALS['js_set'][] = '/modules/content/promo.script.js';
                Response::SetBoolean('promo', true);
            } else if ($item['promo'] == 3){
                //тест
                $GLOBALS['js_set'][] = '/modules/content/test.script.js';
                $GLOBALS['css_set'][] = '/css/clearcontent.css';
                Response::SetBoolean('test', true);
                $module_template =  'test.item.html';
                Page::setPageTemplate('templates/clearcontent_w_head.html');
         
            }
        }

        if( empty( $item ) ||  empty($category ) ) {
            Host::RedirectLevelUp();
            break;
        }    
        
        // увеличение счетчика просмотров
        $ref = Host::getRefererURL();
        
        if( empty( $ajax_mode ) && $this_page->first_instance && !Host::$is_bot && !empty( $ref ) ) {
           $db->query("UPDATE ".$sys_tables[$content_type]." SET `views_count`=`views_count`+1 WHERE `id`=?", $content_id);
           if( !empty( $article_id ) ) $db->query("UPDATE ".$sys_tables['articles']." SET `views_count`=`views_count`+1 WHERE `id`=?", $article_id);

           $info = array(
                 'id_parent' => $item['id']
                ,'type' => $db->fetch( " SELECT id FROM " . $sys_tables['content_types'] . " WHERE content_type = ? ", $content_type)['id']
                ,'ref' => $ref
                ,'ip' => Host::getUserIp() 
                ,'browser' => $_SERVER['HTTP_USER_AGENT']
           );
           $db->insertFromArray( $sys_tables['content_stats_day_shows'], $info );
           
        }
        //комментарии новости
        $GLOBALS['js_set'][] = '/modules/comments/script.js';
        $GLOBALS['css_set'][] = '/modules/comments/style.css';
        $comments_data = array('page_url'    =>  '/'.$this_page->real_url.'/',
                              'id_parent'   =>  $content_id,
                              'parent_type' =>  $content_type
                            );
        Response::SetArray('comments_data', $comments_data);
        
        //фотогалерея
        $photos = Photos::getList($content_type, $content_id);
        Response::SetArray('photos',$photos);

        //хлебные крошки
        $new_meta = [];
        if(!empty($item['category_title_genitive'])) $new_meta[] = $item['category_title_genitive']; // категория
        if(!empty($item['region_title_genitive'])) $new_meta[] = (empty($item['category_title_genitive'])?'Новости ' . $item['region_title_genitive'] : $item['region_title_genitive']); // регион
        $this_page->manageMetadata(array('title'=>$item['title'].(!empty($new_meta)?' - '.implode(' ',$new_meta):''),
                                         'keywords'=>$item['title'],
                                         'description'=>$item['title'].". ".$item['category_title_genitive']." " . ( !empty($item['region_title_genitive']) ? $item['region_title_genitive'] : "" ) ." от портала BSN.ru"),true);
        $this_page->addBreadcrumbs($item['title'], $item['id']);
        //другие новости этой рубрики
        $last_news = $content->getList(6, 0, $item['id_category'], !empty($item['id_region']) ? $item['id_region'] : 0, $sys_tables[$content_type].'.id != '.$item['id'].' AND ' . $sys_tables[$content_type] . '.datetime <= NOW() ');
        Response::SetArray('last_news', $last_news);
        
        //увеодмление о наличии AMP страницы для обычной статьи
        if( empty( $item['promo'] ) || $item['promo'] == 2) Response::SetString( 'amp_url', 'https://m.bsn.ru/' . $item['category_code'] . '/' . ( !empty($item['region_code']) ? $item['region_code'] : "" ) . '/' . $item['chpu_title'] . '/amp/' );

        //метаданные для шаринга
        Response::SetArray('open_graph', array(
                'title' => $item['title'],
                'description' => $item['content_short'],
                'image' => 'https://' . Host::$host . '/' . Config::$values['img_folders'][$content_type] . '/big/' . ( !empty( $item['subfolder'] ) ? $item['subfolder'] : ( !empty( $photos[0]['subfolder'] ) ? $photos[0]['subfolder'] : false ) ) . '/' . ( !empty( $item['photo'] ) ? $item['photo'] : ( !empty( $photos[0]['name']) ? $photos[0]['name'] : false )  ),
                'url' => 'https://' . Host::$host . '/' . $item['category_code'] . '/' . ( !empty($item['region_code']) ? $item['region_code'] : "" ) . '/' . $item['chpu_title'] . '/'
            )
        );        
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // главная страница новостей, категория или категория + регион
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case 
        ( $content_type == 'news' && ( empty($action) || !empty($this_page->page_parameters[1]) || !empty($this_page->page_parameters[0])) ||
        ( $content_type != 'news' && ( empty($action) || !empty($this_page->page_parameters[0]) ) ) ):
        $GLOBALS['js_set'][] = '/modules/estate/list_options.js';
        
        
        //список категорий
        $categories = $content->getCategoriesList(true);
        Response::SetArray('categories',$categories);

        if(!empty($this_page->page_parameters[1]) && count($this_page->page_parameters)==2){ // категория + регион 
            $action = $this_page->page_parameters[1];
            switch(true){
                case isPage($action): //редирект на GET паджинацию
                    Host::Redirect("/" . $content_type . "/".$this_page->page_parameters[0]."/?page=".getPage($action));
                break;
                default: // список новостей Категория-Регион
                    $category = $db->fetch("SELECT * FROM ".$sys_tables[$content_type . '_categories']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
                    if($category['old_articles_categories_id']>0){
                        $new_category = $db->fetch("SELECT * FROM ".$sys_tables[$content_type . '_categories']." WHERE `id` = ?", $category['old_articles_categories_id']);
                        Host::Redirect('/news/'.$new_category['code'].'/'.$this_page->page_parameters[1].'/');
                    }
                    $region = $db->fetch("SELECT `id`, `code`, `title`, `title_genitive` FROM ".$sys_tables[$content_type . '_regions']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[1])."'");
                    if( empty($region) || empty( $category ) ) {
                        if( empty( $region ) && $content_type == 'news' ) {
                            $item = $content->getItem( $this_page->page_parameters[1] );
                            if( !empty( $item ) ) Host::Redirect( '/news/' . $item['category_code'] . '/' . $item['region_code'] . '/' . $item['chpu_title'] . '/');
                        }
                        Host::RedirectLevelUp();
                        break;
                    }
                break;
            }
        } else if(!empty($this_page->page_parameters[0]) && count($this_page->page_parameters)==1){ // список новостей по Категории или по Региону
            $category = $db->fetch("SELECT * FROM ".$sys_tables[$content_type . '_categories']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
            if(empty($category) && !empty( $sys_tables[$content_type . '_regions'] ) ) $region = $db->fetch("SELECT `id`, `code`, `title`, `title_genitive` FROM ".$sys_tables[$content_type . '_regions']." WHERE `code` = '".$db->real_escape_string($this_page->page_parameters[0])."'");
            if(empty($region) && empty($category)){
                Host::RedirectLevelUp();
                break;
            }
            Response::SetArray('category', $category);
        } else if(count($this_page->page_parameters)>0){
            Host::RedirectLevelUp();
            break;
        }
        if(!empty($category)) Response::SetArray('category', $category);
        if(!empty($region)) Response::SetArray('region', $region);
        
        $h1 = [];
        //хлебные крошки
        if(!empty($category)){ // категория
            $this_page->addBreadcrumbs($category['title'], $this_page->page_parameters[0]);
            //добавление title
            $new_meta[] = $category['title_genitive'];
        }
        if( !empty( $region ) ){ // регион
            $this_page->addBreadcrumbs($region['title'], empty($category)?$this_page->page_parameters[0]:$this_page->page_parameters[1]);
            //добавление title
            $new_meta[] = (empty($category) ? 'Новости ' . $region['title_genitive'] : $region['title_genitive']);
        }
        if( empty( $this_page->page_seo_title ) && !empty( $new_meta ) && !empty( $new_meta[0] ) ) 
            $this_page->manageMetadata(
                [
                    'title'=> !empty( $new_meta ) ? implode(' ', $new_meta ) : ''.' Новости',
                    'description'=>implode(' ',$new_meta)
                ], true 
            );
        
        $h1 = empty($this_page->page_seo_h1) ? !empty($new_meta) ? implode(' ',$new_meta) : '' : $this_page->page_seo_h1;
        Response::SetString('content_h1', $h1);   

        // кол-во элементов в списке
        $count = 12;
       
        //страница
        $page = Request::GetInteger('page',METHOD_GET);
        //редирект с несуществующих пейджей
         if(empty($page)) $page = 1;

        //для категории все статьи обнуляем id_category
        $where = array('status<=2');
        $where[] = " datetime <= NOW() " ;
        if(!empty($category)) $where[] = "id_category = ".$category['id'];
        if(!empty($region)) $where[] = "id_region = ".$region['id'];
        
        $get_parameters = Request::GetParameters(METHOD_GET);
        if(!empty($get_parameters['path'])) unset($get_parameters['path']);
        if(!empty($get_parameters['month'])) $where[] = "MONTH(" . $sys_tables[$content_type] . ".`datetime`) = " . $get_parameters['month'];
        if(!empty($get_parameters['year'])) $where[] = "YEAR(" . $sys_tables[$content_type] . ".`datetime`) = " . $get_parameters['year'];
        // создаем пагинатор для списка
        $where = implode(" AND ",$where);
        $paginator = new Paginator($sys_tables[$content_type], $count, $where);
        if($paginator->pages_count>0 && $paginator->pages_count<$page) exit(0);
        if(!empty($get_parameters['page'])) unset($get_parameters['page']);
        //формирование url для пагинатора
        $paginator->link_prefix = '/'.$this_page->requested_path.'/?'.(empty($get_parameters)?"":Convert::ArrayToStringGet($get_parameters).'&').'page=';
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
        //список новостей
        $list = $content->getList($count,
                                   $paginator->getFromString($page),
                                   !empty($category)?$category['id']:false,
                                   !empty($region)?$region['id']:false,
                                   $where,
                                   false,
                                   1,
                                   false,
                                   (!empty($time_limit_for_popular) ? $time_limit_for_popular : ""));
        Response::SetArray('list', $list);
        if($ajax_mode) {
            $ajax_result['ok'] = true;           
            Response::SetString('ajax_url', $paginator->link_prefix . ($page + 1) );
            //показать еще  
            if($paginator->pages_count!=$page) Response::SetBoolean('ajax_pagination', true);
            if($page == 1 && !empty($get_parameters['month']) && !empty($get_parameters['year'])) Response::SetString('search_title', Config::Get('months')[Convert::ToInt($get_parameters['month'])] . ' ' . $get_parameters['year']);
        }
        $module_template = empty($ajax_mode) ? 'mainpage.html' : 'block.html';
        Response::SetBoolean( 'payed_format', true );
        //блок поиска по новостям
        $GLOBALS['js_set'][] = '/modules/search/script.js';
        $GLOBALS['css_set'][] = '/modules/search/style.css';
        
        //пописка на рассылку
        $GLOBALS['js_set'][] ="/js/subscription.js";
        $subscription = array(
            'email' => $auth->isAuthorized() ? $auth->email : '',
            'url' => '/news/subscribe/',
            'text' => 'Подписаться на рассылку'
        );
        Response::SetArray( 'subscription', $subscription );    

        //список фильтров (месяц-год)
        $date_list = $content->getMonthsList( !empty( $action ) ? $action : '' );
        Response::SetArray( 'date_list', $date_list );
                    
        break;
    default:
        Host::RedirectLevelUp();
        break;
}

if(!empty($this_page->page_parameters[0])){
    switch($this_page->page_parameters[0]){
        case 'commercialestate':
            Response::SetString('tgb_type','commercial');
            break;
        case 'countryestate':
            Response::SetString('tgb_type','country');
            break;
        default:
            Response::SetString('tgb_type','live');
            break;
    }
}

if( $this_page->first_instance ){
    //хлебные крошки
    $this_page->clearBreadcrumbs();
    $types_breadcrumbs = array(
                                        'news' => 'Новости',
                                        'articles' => 'Статьи',
                                        'bsntv' => 'БСН-ТВ',
                                        'doverie' => 'Доверие потребителя',
                                        'blog' => 'Блог'
    );
    $this_page->addBreadcrumbs( $this_page->page_title, $content_type, 0, $types_breadcrumbs);
    if(!empty($category) && !empty($this_page->page_parameters[0])) {
        
        $category_list = $db->fetchall("SELECT CONCAT('" . $content_type . "/', code) as url, title as title FROM ".$sys_tables[$content_type . '_categories']." WHERE id != ?", 'url', $this_page->page_parameters[0]);
        $category_breadcrumbs = $category_list;    
        $this_page->addBreadcrumbs($category['title'], $this_page->page_parameters[0], 1, $category_breadcrumbs);
        if(!empty($region) && !empty($this_page->page_parameters[1])) {
            $region_list = $db->fetchall("SELECT CONCAT('news/" . $category['code']. "/', code) as url, title as title FROM ".$sys_tables[$content_type . '_regions']." WHERE id != ?", 'url', $this_page->page_parameters[1]);
            $this_page->addBreadcrumbs($region['title'], $this_page->page_parameters[1], 2, $region_list);
        }
    }
    if( ( !empty( $item['id'] ) &&  ( $content_type == 'blog' || ( $content_type == 'articles' && $item['id_category'] == 27 ) ) ) || ( !empty($item['promo']) && $item['promo'] != 2) )  {
        Response::SetBoolean('wide_format', true);
        Response::SetBoolean('not_show_topline', true);
    }
    Response::SetBoolean('show_overlay', true);
}
?>