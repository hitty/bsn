<?php
require_once('includes/class.paginator.php');
require_once('includes/sphinxapi.php');

$GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.min.js';
$GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';
$GLOBALS['js_set'][] = '/js/jui_new/datepicker-ru.js';

//количество записей на страницу
$count = 15;
$page = Request::GetInteger('page', METHOD_GET);
if ((isset($page))&&($page==0)){
    //чтобы не потерялись фильтры, надо включить их в redirect
    $parameters = Request::GetParameters(METHOD_GET);
    //здесь будем накапливать строку с get-параметрами
    $url=[];
    foreach($parameters as $key=>$item){
        if ($key!='path'){
            if ($key!='page') $url[]=$key.'='.$item;
            else $url[]=$key.'=1';//заменяем page на посл.страницу
        } 
    }
    $url = '?' . implode('&',$url);
    //url не может быть пуст - там будет хотя бы page
    Host::Redirect('/'.$this_page->requested_path.'/'.$url);
    exit(0);
}
if(empty($page)) $page = 1;
else Response::SetBoolean('noindex',true); //meta-тег robots = noindex

//убираем некорректные и лишние get-параметры
$parameters=Request::GetParameters(METHOD_GET);
unset($parameters['path']);
$params_count = count($parameters);
$parameters = array_intersect_key($parameters,array('date_start'=>0,'date_end'=>0,'type'=>0,'sort'=>0,'query'=>0,'page'=>0));
if(empty($parameters['date_start']) || !preg_match("/^[0-9]{2}\.[0-9]{2}\.[0-9]{2}$/",$parameters['date_start']) ) unset($parameters['date_start']);
if(empty($parameters['date_end']) || !preg_match("/^[0-9]{2}\.[0-9]{2}\.[0-9]{2}$/",$parameters['date_end']) ) unset($parameters['date_end']);
if(empty($parameters['type']) || !preg_match("/^[A-z]+$/",$parameters['type']) ) unset($parameters['type']);
if(empty($parameters['sort']) || !preg_match("/^[A-z]+$/",$parameters['sort']) ) unset($parameters['sort']);
if(empty($parameters['query']) || !preg_match("/^[A-zА-я0-9\s]+$/sui",$parameters['query']) ) unset($parameters['query']);
//если что-то удаляли, редиректим
if($params_count > count($parameters)) Host::Redirect('/'.$this_page->requested_path.'/?'.(empty($parameters)?"":Convert::ArrayToStringGet($parameters).'&'));

// определяем возможный запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
// обработка общих action-ов
switch(true){
    case empty($action):                                                                
            $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
            $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
            $GLOBALS['js_set'][] = '/modules/search/script.js';
            $GLOBALS['css_set'][] = '/modules/search/style.css';
            // формирование набора условий для поиска
            $parameters = Request::GetParameters(METHOD_GET);
            //поиск по типам индексов
            $index_types = array(
                                    'all'               =>  'все разделы',
                                    'news'              =>  'Новости',
                                    'articles'          =>  'Статьи',
                                    'calendar_events'   =>  'Мероприятия',
                                    'agencies'          =>  'Агентства',
                                    'doverie'           =>  'Доверия потребителя',
                                    'opinions'          =>  'Мнения экспертов',
                                    'bsntv'             =>  'БСН-ТВ'
            );
            if(empty($parameters['type']) || $parameters['type'] == 'content') $parameters['type'] = 'all';
            foreach($index_types as $k=>$val){
                if(!empty($parameters['type']) && $k==$parameters['type']) Response::SetString('selected_type',$k);
            }
            Response::SetArray('index_types',$index_types); 
            //инициализация сфинкс
            $sphinx = new SphinxClient;
            $sphinx->SetServer( '127.0.0.1', Config::Get('sphinx/port') ); // устанавливаем сервер и порт, на котором установлен Sphinx 
            $sphinx->SetLimits((int)($page-1)*$count,(int)$count); // page, count
            $sphinx->SetConnectTimeout(1); // Устанавливаем таймаут на случай долгого ответа Sphinx в 1 секунду
            $sphinx->SetArrayResult(true); // указываем вид возвращаемого массива, результата поиска (массив)
            //поиск во временном промежутке                                                                  
            if(!empty($parameters['date_start']) || !empty($parameters['date_end'])){
               $dates_start = DateTime::createFromFormat('d.m.y', !empty($parameters['date_start']) ? $parameters['date_start'] : '01.01.01');
               $dates_end = DateTime::createFromFormat('d.m.y', !empty($parameters['date_end']) ? $parameters['date_end'] : date('d.m.y'));
               
               if($dates_start > $dates_end){
                   $date_end = $dates_start->format("d.m.Y");
                   $date_start = strtotime('01.01.2001');
                   list($dates_start,$dates_end) = array($dates_end,$dates_start);
                   $parameters['date_end'] = $parameters['date_start'];
                   $parameters['date_start'] = '01.01.01';
               }else{
                   $date_start = $dates_start->format("Y.m.d");
                   $date_end = $dates_end->format("Y.m.d");
               }
               $sphinx->SetFilterRange('datetime', $dates_start->getTimestamp(), $dates_end->getTimestamp());
            }
            
            
            //  Режим сортировки
            if(isset($parameters['sort']) && $parameters['sort']=='date') $sphinx->SetSortMode(SPH_SORT_ATTR_DESC, 'datetime'); // по дате
            else $sphinx->SetSortMode(SPH_SORT_RELEVANCE); // по релевантности
            // Режим полнотекстового поиска
            //по умолчанию установлен
            $sphinx->SetMatchMode(SPH_MATCH_ANY); // ищет любое слово из фразы
            /*
            if(strstr(Host::getRefererURL(),'search')=='') $parameters['match'] = 1;
            if(isset($parameters['match']) && $parameters['match']==1) $sphinx->SetMatchMode(SPH_MATCH_ANY); // ищет любое слово из фразы ---- SPH_MATCH_EXTENDED2
            else  $sphinx->SetMatchMode(SPH_MATCH_EXTENDED2); // ищет все слова в запросе
            */
            //определение индексов, по которым вести поиск
            $search_by_index = !empty($parameters['type']) && $parameters['type']=='media' ? 'news,bsntv,doverie,articles,opinions': ( 
                array_key_exists( $parameters['type'], $index_types ) && $parameters['type'] != 'all' ? $parameters['type'] : '*'
            );
            //разделы для индексации
                        
            //поиск
            if(isset($parameters['query']) && $parameters['query']!='' && (array_key_exists($parameters['type'], $index_types) || $parameters['type']=='media')) {
                //результаты поиска в шаблон
                Response::SetString('submit', true);
                $query = $sphinx->Query('"' . $parameters['query'] . '"', $search_by_index);
                if( !empty( $sphinx->_error ) ) $ajax_result['error'] = $sphinx->_error;
                //массив с результатами
                $results = [];
                if($query){
                    if (!empty( $query["matches"] ) ) {
                        foreach( $query['matches'] as $k => $match ) {
                            $content = '';
                            $pres = $sphinx->BuildExcerpts ( array($match['attrs']['content']), $match['attrs']['type'], $parameters['query'], array('html_strip_mode'=>'strip'));
                            if(!empty($pres)) $match['attrs']['content'] = $pres[0];
                            $results[] = $match['attrs'];
                        }
                    } else $error[] = "По вашему запросу ничего не найдено. Попробуйте изменить запрос. ";
                    //кол-во найденных объектов
                    Response::SetString( 'total', $query['total_found'] );
                    Response::SetInteger( 'count_items', 0 );
                    //результаты поиска в шаблон
                    $ids = $list = [];
                    if(!empty($results)){
                        $type = $results[0]['type'];
                        Response::SetArray('list', $results);
                        $ajax_result['list'] = $results;
                        $ajax_result['ok'] = true;
                        Response::SetString('content_type', $type);
                    }
                    $paginator = new Paginator('search', $count, null, "SELECT ".$query['total_found']." as items_count");
                    if(!empty($parameters['page'])) unset($parameters['page']);
                    unset($parameters['path']);
                    $paginator_link_base = '/'.$this_page->requested_path.'/?'.(empty($parameters)?"":Convert::ArrayToStringGet($parameters).'&');
                    //редирект с несуществующих пейджей
                     if(empty($page)){
                        if(isset($page)) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
                        $page = 1;
                    } elseif($page<1) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
                    else Response::SetBoolean('noindex',true); //meta-тег robots = noindex 
                    if($paginator->pages_count>0 && $paginator->pages_count<$page){
                        Host::Redirect('/'.$paginator_link_base.'page='.$paginator->pages_count);
                        exit(0);
                    }
                    Response::SetString('paginator_link_base', $paginator_link_base);

                    //формирование url для пагинатора
                    $paginator->link_prefix = $paginator_link_base.'page=';
                    if($paginator->pages_count>1){
                        Response::SetArray('paginator', $paginator->Get($page));
                    }
                }
                elseif ($query ===false)  $error[] = "Техническая ошибка: " . $sphinx->GetLastError() . ".<br>n";
                if ($sphinx->GetLastWarning()) $error[] = "WARNING: " . $sphinx->GetLastWarning() . "<br>n";
            }
            Response::SetArray('data', $parameters);
            $ajax_result['ok'] = true;
            if($ajax_mode && !empty($paginator)) {
                if($paginator->pages_count!=$page) Response::SetBoolean('ajax_pagination', true);
                
                Response::SetString('ajax_url', $paginator->link_prefix = $paginator_link_base.'page=' . ( $page + 1) );
            }
            $module_template = 'list.html';
        break;
    default:
        //редирект для элитки на новый УРЛ для спецпредложений
        $id = $packet = false;
        $elite_url = explode('estate/elite',$this_page->real_url);
        if(!empty($elite_url) && count($elite_url)>1) {
            Host::Redirect('estate/elite/exclusive'.$elite_url[1]);
        }
        $this_page->http_code=404;
}

?>