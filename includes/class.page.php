<?php
/**    
* Основной класс обработки URL запросов
*/
                            
class Page {
    public $is_admin_page = false;
    public $is_members_page = false;
    public $is_advertising_page = false;
    public $is_finished_webinar_page = false;
    public $ab_test = false;                    // страницы для a/b тестирования
    
    private $requested_url = "";
    private $requested_path = "";
    private $real_url = "";
    private $real_path = "";
    private $real_params = [];
    private $query_params = [];
    private $template = "";
    private $cachetime = "";
    private $http_code = 200;
    private $error_message = "";
    private $incacheobjects = [];
    
    private $first_instance = true;
    private $module_path = "";                  // путь к папке модуля

    private $page_id = 0;
    private $page_url = "";                     // полный URI страницы
    private $page_alias = "";                   // последняя часть URI страницы
    private $page_title = "";                   // заголовок страницы (переопределяется в seo)
    private $page_module = "";                  // подключаемый модуль (с путем от корня сайта)
    private $page_parameters = [];         // параметры для модуля (из URL)
    private $module_parameters = [];       // параметры для модуля (из DB)
    private $page_cache_time = 0;               // время кэширования страницы (0 - не кэшировать)
    private $page_block = false;                // страница-блок (работает только из внутренних вызовов, не доступна по прямому url из браузера)
    private $page_template = "";                // шаблон оформления внутреннего содержимого
    private $page_content = "";                 // текст модуля 
    private $page_access = "";
    private $page_seo_title = "";               
    private $page_seo_h1 = "";
    private $page_pretty_url = "";
    public $page_seo_breadcrumbs = [];
    private $page_seo_keywords = "";
    private $page_seo_descriprion = "";
    private $page_seo_text = "";
    private $page_breadcrumbs = [];        // хлебные крошки для текущей страницы
    private $metadata = [];                // метаданные страницы (могут переопределяться в модуле)
    
    private $menu = [];                    // хранилище для меню
    private $menu_response_in_module = false;   //чтобы делался Response $this->menu во время обработки модуля, не позже - для страниц авторизации-регистрации-восстановления
    private $last_visited_page = "";            //последняя посещенная страница
    
    /** 
    * объект страницы
    * 
    * @param string URL страницы
    * @param array массив объектов для вхождения в ключ кеширования ('post','cookie')
    * @return Page
    */
    public function __construct($url, $incacheobjects=null){
        //для a/b теста в начало URLов вставляется new/
        if(substr($url, 0, 4) == 'new/'){
            $this->ab_test = true;
            $url = substr($url, 4);
        }
        // полный запрошенный урл (здесь могут быть GET параметры)
        $this->requested_url = $this->real_url =  $url;
        $parsed_url = parse_url(Host::getWebPath($url));
        // чистый URI страницы
        $this->requested_path = $this->real_path = trim($parsed_url['path'],'/');
        // определение признака первого запуска (страница или блок)
        if(!defined('__CLASS_PAGE_FIRST_INSTANCE__')){
            $GLOBALS['js_set'] = $GLOBALS['css_set'] = [];
            define('__CLASS_PAGE_FIRST_INSTANCE__',1);
            $this->first_instance = true;
            // сео - подмена урлов
            $this->urlChecker($url);
            // проверка региона
            $this->checkRegion();
        } else $this->first_instance = false;
        // заглатывание списка значащих объектов для ключа кэша
        if(!empty($incacheobjects)){
            foreach($incacheobjects as $key=>$val){
                if(in_array($key,array('post','session','cookie','POST','SESSION','COOKIE')))
                    $this->incacheobjects[strtolower($key)] = $val;
            }
        }           
        if(substr($this->requested_path,0,5)=='admin') $this->is_admin_page = true;
        if(substr($this->requested_path,0,7)=='members' || $this->requested_path =='objects_subscriptions' || $this->requested_path == 'favorites' ) $this->is_members_page = true;
        if(substr($this->requested_path,0,11)=='advertising') $this->is_advertising_page = true;
        $ajax_mode = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($internal_mode);
        //мобильная версия
        if($this->first_instance ){
            $desktop_version = Cookie::GetString('desktop_version');
            if(Host::isMobile() && empty($desktop_version) &&  
                                    (
                                        empty($this->requested_path) || 
                                        substr($this->requested_path,0,3)=='tgb' ||
                                        substr($this->requested_path,0,4)=='news' ||
                                        substr($this->requested_path,0,4)=='blog' ||
                                        substr($this->requested_path,0,5)=='bsntv' ||
                                        substr($this->requested_path,0,6)=='logout' ||
                                        substr($this->requested_path,0,6)=='photos' ||
                                        substr($this->requested_path,0,6)=='abuses' ||
                                        substr($this->requested_path,0,6)=='estate' ||
                                        substr($this->requested_path,0,4)=='live' ||
                                        substr($this->requested_path,0,5)=='build' ||
                                        substr($this->requested_path,0,10)=='commercial' ||
                                        substr($this->requested_path,0,7)=='country/' ||
                                        ( substr($this->requested_path,0,15)=='zhiloy_kompleks' && substr($this->requested_path,0,21)!='zhiloy_kompleks/votes' ) ||
                                        substr($this->requested_path,0,16)=='business_centers' ||
                                        substr($this->requested_path,0,19)=='cottedzhnye_poselki' ||
                                        substr($this->requested_path,0,7)=='banners' ||
                                        substr($this->requested_path,0,7)=='geodata' ||
                                        substr($this->requested_path,0,7)=='service' ||
                                        substr($this->requested_path,0,7)=='doverie' ||
                                        substr($this->requested_path,0,8)=='articles' ||
                                        substr($this->requested_path,0,8)=='calendar' ||
                                        substr($this->requested_path,0,8)=='webinars' ||
                                        substr($this->requested_path,0,9)=='favorites' ||
                                        substr($this->requested_path,0,10)=='promotions' ||
                                        substr($this->requested_path,0,11)=='specialists' ||
                                        substr($this->requested_path,0,12)=='applications'  ||
                                        substr($this->requested_path,0,12)=='lostpassword'  ||
                                        substr($this->requested_path,0,13)=='authorization'  ||
                                        substr($this->requested_path,0,12)=='registration'  ||
                                        substr($this->requested_path,0,13)=='organizations' ||
                                        substr($this->requested_path,0,15)=='estate_estimate'  ||
                                        substr($this->requested_path,0,19)=='service/information' ||
                                        substr($this->requested_path,0,33)=='konkurs_doverie_potrebiteley_2020'
                                    ) 
            ) {
                Host::Redirect( str_replace( ( DEBUG_MODE ? '' : 'www.' ) . 'bsn', 'm.bsn', Host::$root_url) . '/' . $this->real_url . '/' );
            }
            
        }
    }
    
    private function checkRegion(){
        global $db;
        // выбранные регионы и регионы по умолчанию
        $selected_regions = $default_regions = [];
        // список разрешенных регионов
        $regionlist = $db->fetchall("SELECT * FROM ".Config::$sys_tables['geoprefixes']);
        
        // если запрошенный урл пуст, то делаем фиктивный пустой префикс региона
        if(empty($this->real_path)) list($first,$last) = array('', $this->real_path);
        else list($first,$last) = explode('/',$this->real_path.'/',2);
        
        // собираем выбранные регионы
        foreach($regionlist as $region){
            // если префикс подходит - запоминаем
            if($first == $region['prefix']) $selected_regions[] = $region;
            // если префикс пустой - значит это дефолтный регион
            if(empty($region['prefix'])) $default_regions[] = $region;
        }

        // если ни один регион не выбран, значит устанавливаем дефолтные регионы
        if(empty($selected_regions)) {
            $selected_regions = $default_regions;
        } else {
            // сдвигаем урл
            $this->real_path = $last;
        }
        // запись данных по региону куда надо
        $this->selected_regions = $selected_regions;
    }
    
    private function urlChecker($url = '', $seo = true){
        global $db, $ajax_mode;
        if(empty($url)) $url = Host::getWebPath($url);
        $parsed_url = parse_url($url);
        if(!isset($parsed_url['path'])) $parsed_url['path'] = "";
        //проверка на короткий URI
        Host::getShortUri($url);
        
        $query = $clearquery = '';
        $gets = $additional_query = [];
        //редирект при пустом параметре
        $empty_get_parameters = false;
        if(!empty($parsed_url['query'])){
            $qry = explode('&', $parsed_url['query']);
            foreach($qry as $q) {
                list($key,$val) = explode('=',$q.'=');
                if(!isset($val)) $empty_get_parameters = true;
                else $gets[$key] = $q;
                
            }
            //if(!empty($empty_get_parameters)) Host::Redirect($parsed_url['path'] .'?' . implode('&', $gets));
            if(!empty($gets['page'])) {
                $page = $gets['page'];
                unset($gets['page']);
            }
            if(!empty($gets['sortby'])) {
                $sortby = $gets['sortby'];
                unset($gets['sortby']);
            }
            if(!empty($gets['currency'])) {
                $currency = $gets['currency'];
                unset($gets['currency']);
            }              
            if(!empty($gets['search_type'])) {
                $search_type = $gets['search_type'];
                unset($gets['search_type']);
            }            
            if(!empty($gets['id_subscription'])) {
                $id_subscription = $gets['id_subscription'];
                unset($gets['id_subscription']);
            }            
            ksort($gets);
            $query = $clearquery = implode('&',$gets);
            $this->query_params = $gets;
            if(!empty($min_cost)) $additional_query[] = $min_cost;
            if(!empty($max_cost)) $additional_query[] = $max_cost;
            if(!empty($sortby)) $additional_query[] = $sortby;
            if(!empty($currency)) $additional_query[] = $currency;
            if(!empty($search_type)) $additional_query[] = $search_type;
            if(!empty($id_subscription)) $additional_query[] = $id_subscription;
            if(!empty($page)) $additional_query[] = $page;
            $query .= !empty($additional_query) ? (empty($query)?'':'&').implode('&',$additional_query) : '';
            
        }
        
        if(!empty($seo)){
            $checking_url = trim($parsed_url['path'], '/').(empty($clearquery)?'':'/?'.$clearquery);
            // подкачка данных по СЕО для страницы
            $ajax_mode = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($internal_mode);
            if(empty($ajax_mode)){
                $page_seo = $db->fetch("SELECT * FROM ".Config::$sys_tables['pages_seo']."
                                    WHERE ? = url", $checking_url);
                if(!empty($page_seo['pretty_url']) && $page_seo['pretty_url'] != $page_seo['url'] && !empty($page_seo['only_params']) && $page_seo['only_params'] == 2) {
                    $new_url = (!empty($this->ab_test) ? 'new/' : '' ) . trim($page_seo['pretty_url']).'/';
                    Host::Redirect($new_url.(!empty($additional_query) ? '?'.implode('&',$additional_query) : ''));
                }
                if(!empty($parsed_url['query']) && $query != $parsed_url['query']) {
                    Host::Redirect( ( !empty($this->ab_test) ? 'new/' : '' ) . trim($parsed_url['path'], '/').'/'.(empty($query)?'':'?'.$query));
                }
            }
            
        }
    }
    public function Render($is_block = false){
        global $memcache, $ajax_mode, $db, $auth;
        //переопределение глобальных таблиц БД
        $sys_tables = Config::$sys_tables; 
        // сигнатура кэша
        $page_signature = $this->createPageSignature();
        
        //определение id сессии (при загрузке файлов пользователем)
        $sessname = Session::GetName();
        $internal_mode = Request::GetString($sessname, METHOD_POST);
        $ajax_mode = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($internal_mode);
        if($ajax_mode) {
            $page_signature = 'ajax::'.$page_signature;
            $ajax_result = array('ok'=>false);
            Response::SetBoolean('ajax_mode',$ajax_mode);
        } elseif($is_block)  $page_signature = 'block::'.$page_signature;

        
        if(!$ajax_mode && $this->first_instance && !$this->is_admin_page && !Host::isBot()) {
            // проверка URL на наличие метки
            $this->getMarkers();
            //запоминание последней посещенной страницы
            $last_visited_page = Session::GetString('last_visited_page');
            $this->last_visited_page = !empty( $last_visited_page ) ? $last_visited_page : '';
            if( ! ( substr($this->requested_path,0,3)=='map' ) ) Session::SetString('last_visited_page', $this->requested_url);
        }

        // проверка наличия в кэше страницы и блоки не AJAX
        if(Config::$values['memcache']['enabled']){
            $return = $memcache->get($page_signature);
            if($return !== FALSE) {
                if(!$ajax_mode) return $return;   
                else{ 
                        header("Content-type: application/json; charset=utf-8");
                        $return['cache_out_date'] = date('d.m.Y H:i:s'); 
                        echo Convert::json_encode($return);
                        exit(0);    
                }
            }
        }
                
        // здесь проверить в сео на предмет подмены адреса
        if($this->first_instance){
            if(!$ajax_mode){          
                $this->menuAdd('Каталог',   '/',            1, false,   'estate');
                $this->menuAdd('Медиа',     'media',        1, false,   'media');
                $this->menuAdd('Сервисы',   'service',      1, false,   'service');
                $this->menuSecondLevelAdd();
                //меню справа для авторизованных пользователей
                if($auth->isAuthorized()===true){
                    //агентство или администратор
                    $admin_privs = !empty($auth->id_agency) || $auth->id_group == 101 || $auth->id_group == 10 || $auth->id_group == 3 || (empty($auth->id_agency) && $auth->id_tarif>0);
                    //получение кол-ва объектов пользователя
                    require_once('includes/class.estate.php');     // Estate (объекты рынка недвижимости)
                    require_once('includes/class.estate.statistics.php');     // EstateStat (статистика объектов рынка недвижимости)
                    require_once('includes/class.messages.php');     // Сообщения
                    
                    //объекты 
                    $this->menuAdd('Объекты', 'members/objects', 4, false, 'category-title');
                        $this->menuAdd('Объекты',               'members/objects/list',          5,  false,  false,  'members/objects');
                        $this->menuAdd('Подписки',              'members/objects/objects_subscriptions',    5,  false,  false,  'members/objects');
                        $this->menuAdd('Избранное',             'members/objects/favorites',                5,  false,  false,  'members/objects');
                        if( !empty( $auth->agency_admin ) && $auth->agency_admin == 1 && !empty( $auth->id_agency )) $this->menuAdd('Выгрузка через XML',     'members/objects/agencies_uploads',                5,  false,  false,  'members/objects');
                        
                    
                    //теперь заявки выводятся для всех
                    $this->menuAdd('Мои сервисы',           'members/conversions',                  4,  false,  'category-title');
                        if( $auth->user_activity == 1 ) $this->menuAdd('Звонки',            'members/conversions/calls',            5,  false,  false,          'members/conversions');
                        $this->menuAdd('Заявки',            'members/conversions/applications',     5,  false,  false,          'members/conversions');
                        $this->menuAdd('Консультации',      'members/conversions/consults',         5,  false,  false,          'members/conversions');
                    
                    //уведомления
                    Notifications::Init();
                    $notifications_list = Notifications::getList($auth->id);
                    Response::SetArray('notifications_list', $notifications_list);
                    Response::SetInteger('notifications_count', Notifications::$count);                    
                    $this->menuAdd('Сообщения',             'members/messages',                     4,  false,  'category-title messages', false, Notifications::$count);

                    $this->menuAdd('Кошелек',               'members/pay/balance',                  4,  false,  'category-title balance');
                    $this->menuAdd('Профиль',               'members/personalinfo',                 4,  false,  'category-title personalinfo');
                    
                    if(!empty($auth->id_agency)){
                        $agency_limit = EstateStat::getAgenciesCount($auth->id);
                        Response::SetArray('agency_limit', $agency_limit);
                    }
                       
                } else{
                    $this->menuAdd('Объекты',       'members/cabinet',      5, false, 'cabinet');
                    $this->menuAdd('Избранное',     'favorites',            4, false, 'favorites');
                }
                //вывод избранного в меню
                Favorites::Init();
                $favorites_count = Favorites::getAmount();
                Response::SetInteger( 'favorites_count', $favorites_count );
            }
            // подкачка данных по СЕО для страницы
            $page_seo = $db->fetch("SELECT * FROM ".$sys_tables['pages_seo']."
                                    WHERE ? = pretty_url
                                       OR ? = url
                                    ORDER BY LENGTH(pretty_url) DESC, LENGTH(url) DESC"
                                    , $this->requested_path
                                    , $this->requested_url);
                                    
            if(!empty($page_seo)){
                $this->page_seo_title = $page_seo['title'];
                $this->page_seo_h1 = $page_seo['h1_title'];
                $this->page_seo_description = $page_seo['description'];
                $this->page_seo_keywords = $page_seo['keywords'];
                $this->page_pretty_url = $page_seo['pretty_url'];
                //СЕО хлебные крошки. Имеют больший приоритет, чем обычные
                if(!empty($page_seo['breadcrumbs']) && empty($this->ab_test)) {
                    $seo_bc = explode(',',$page_seo['breadcrumbs']);
                    foreach($seo_bc as $k=>$bc){
                        $bc = explode("=>",$bc);
                        if(!empty($bc[1])) $this->page_seo_breadcrumbs[$k] = array('title'=>$bc[1], 'level'=>$k, 'url'=>$bc[0]);
                    }
                    
                }

                $gets = [];
                $pd2 = parse_url($page_seo['url']);
                $qr2 = empty($pd2['query']) ? [] : Convert::StringGetToArray($pd2['query']);
                foreach($qr2 as $key=>$val) {
                    $gets[$key]=$val;
                    Response::SetParameter($key, $val, METHOD_GET);
                }
                $pd1 = parse_url($this->requested_url);
                $qr1 = empty($pd1['query']) ? [] : Convert::StringGetToArray($pd1['query']);
                foreach($qr1 as $key=>$val) $gets[$key]=$val;
                $this->real_path = trim($pd2['path'],'/');
                $this->real_url =  $this->real_path.'/'.(empty($gets) ? "" : '?'.Convert::ArrayToStringGet($gets));
                $this->urlChecker($this->real_url, false);
                $this->page_seo_text = trim($page_seo['url'], '/') == trim($this->real_url, '/') ? $page_seo['seo_text'] : '';
            }
        }        
        
        if(empty($ajax_mode)) EstateSubscriptions::Init($this->real_url);
         
        $temp_path = $this->real_path;
        if(!$this->is_admin_page){
            //поиск по префиксу
            foreach(Config::$values['fixed_prefix_urls'] as $prefix) 
                if(stristr($this->real_path, $prefix)!==false)  {$temp_path = $prefix; $fixed_prefix_urls=true; break; }
        }
        Response::SetString('basic_img_dolder', Config::$values['img_folders']['basic']);
        // загрузка страницы из DB
        $page = $db->fetch("SELECT p.*,m.path,m.level FROM ".$sys_tables['pages']." p
                            LEFT JOIN ".$sys_tables['pages_map']." m ON p.id=m.object_id
                            WHERE (".$db->quoted($temp_path)." = p.url
                                    OR ".$db->quoted($temp_path)." LIKE CONCAT(p.url,'/%'))
                                ".($this->first_instance ? " AND p.block_page!=1 " : "")."
                            ORDER BY LENGTH(p.url) DESC");          
        
        // если страницу не нашли, или запрошенный адрес включает параметры, а найденная страница параметры не принимает
        if(empty($page) || (strlen($page['url'])<strlen($this->real_path) && $page['no_require_params']==1)) {
            $this->error_message = "Page not found";
            $this->http_code = 404;
        } else {
            $this->page_id = $page['id'];
            $this->page_url = $page['url'];
            $this->page_alias = $page['alias'];
            $this->page_title = $page['title'];
            $this->page_block = $page['block_page']==1;
            $this->page_cache_time = $page['cache_time'];
            $this->page_module = $page['module'];
            $this->page_template = $page['template'];
            $this->page_access = $page['access'];
            $this->page_content = $page['content'];
            $this->module_parameters = Convert::StringGetToArray($page['parameters']);
            
            if(!empty($this->module_parameters['payed_format'])) Response::SetBoolean('payed_format',true);
            $this->page_parameters = [];
            if(strlen($page['url'])<strlen($this->real_path)){
                //не заменяем + на %2B, потому что urldecode заменит его на пробел
                $params = explode('/',substr($this->real_path, strlen($page['url'])+1));
                
                //добавлено 01032016 - экранируем кавычки
                $params = array_map('addslashes',array_map('urldecode',$params));
                $this->page_parameters = $params;
            }
                        
            // дубликат для использования в модуле
            $this_page =& $this;
            
            //для модерации открываем доступ
            if(!$this->checkAccess()){
                $this->error_message = "Not enough rights";
                $this->http_code = 403;
                Response::SetArray('current_page', get_object_vars($this));
            } else {
                if($this->first_instance && empty($fixed_prefix_urls)){                    
                    // подкачка списка страниц для хлебных крошек если нет СЕО хлебных крошек
                    if(!empty($page['path']) && empty($this->page_seo_breadcrumbs) )
                        $bc = $db->fetchall("SELECT m.object_id,m.level,p.title,p.url
                                        FROM ".$sys_tables['pages_map']." m
                                        LEFT JOIN ".$sys_tables['pages']." p ON m.object_id=p.id
                                        WHERE m.path IN (?)
                                        ORDER BY m.level", false, $page['path']);
                    if(!empty($bc)) $this->page_breadcrumbs = $bc;
                }
                Response::SetArray('current_page', get_object_vars($this));

                $this->metadata = array(
                    'title' => $this->page_title,
                    'description' => $this->page_title,
                    'keywords' => $this->page_title
                );
                
                if( $this->first_instance ){
                    //показ топлайна
                    echo $not_show_topline = Session::GetBoolean('not_show_topline');
                    if( empty( $not_show_topline ) ) {
                        $topline =  Banners::getItem( 'top', !empty( $this->page_alias ) ? $this->page_alias : false );
                        if( !empty( $topline) ) Response::SetArray( 'banner_item', $topline );
                    }
                    //определение баннера справа
                    $right_banner = Banners::getItem( 'right', !empty( $this->page_alias ) ? $this->page_alias : false );
                    if( !empty( $right_banner ) ) Response::SetArray( 'right_banner', $right_banner );
                }                        
                
                $page_module_file_exists = file_exists($this->page_module);
                if($page_module_file_exists) {
                    $this->module_path = dirname($this->page_module);
                }
                //отображение новогоднего баннера
                $not_show_ny_banner1 = Cookie::GetBoolean('not_show_ny_banner1');
                Response::SetBoolean('not_show_ny_banner1', $not_show_ny_banner1);
                //###########################################################################
                // подключение и выполнение модуля
                //###########################################################################
                if(!$page_module_file_exists || !require($this->page_module)) {
                    $this->http_code = 404;
                    $this->error_message = "Page not found";
                }
                //добавление мета-тега canonical для chpu страниц
                if($this->first_instance && !$this->is_admin_page){
                    $canonical = !empty($page_seo['pretty_url']) ? $page_seo['pretty_url'] : $this->requested_path;
                    $canonical_array = explode('/', trim($canonical, '/'));
                    foreach($canonical_array as $k=>$item) if($k>3) unset($canonical_array[$k]);
                    Response::SetString( 'meta_canonical', '/' .  implode( '/', $canonical_array ) . ( $canonical != '' ? '/' : '' ) ) ;
                }

                // информация об авторизованном пользователе
                if($auth->isAuthorized()===true) {
                    $title = $auth->name;
                    if(empty($title)) $title = $auth->lastname;
                    $auth_array = array(
                        'phone' => $auth->phone,
                        'name' => $auth->name,
                        'lastname' => $auth->lastname,
                        'agency' => $auth->agency_title,
                        'email' => $auth->email,
                        'id' => $auth->id,
                        'id_group' => $auth->id_group,
                        'user_photo' => $auth->user_photo,
                        'user_photo_folder' => Config::Get('img_folders/users'),
                        'balance' => $auth->balance,
                        'id_tarif' => $auth->id_tarif,
                        'active_objects' => $auth->active_objects,
                        'promo_left' => $auth->promo_left,
                        'premium_left' => $auth->premium_left,
                        'vip_left' => $auth->vip_left,
                        'tarif_title' => $auth->tarif_title,
                    );
                    Response::SetArray('auth',$auth_array);
                    //снятие флага для попапа сообщения
                    if(!$ajax_mode && !empty($this->first_instance))$db->query("UPDATE ".$sys_tables['messages']." SET popup_notification = 1 WHERE id_user_to = ?", $auth->id);
                    //проверка на наличие непрочитанного системного сообщения и приглашения агента
                    if($this->is_members_page && empty($ajax_mode)) {
                        Response::SetBoolean('show_topline', false);
                        $GLOBALS['css_set'][] = '/modules/members/style.css'; 
                        if($this->page_url!='members/messages'){
                            $message = new Messages();
                            $system_message = $message->GetLastUnreadSystemMessage();
                            if(!empty($system_message)) Response::SetArray('system_message', $system_message);
                        }
                        /* временно отключегы
                        //приглашение для агента
                        $invites = $db->fetchall("
                                       SELECT 
                                            ".$sys_tables['users_invites_agencies'].".*,
                                            ".$sys_tables['agencies'].".chpu_title,
                                            ".$sys_tables['agencies'].".title
                                       FROM ".$sys_tables['users_invites_agencies']."
                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users_invites_agencies'].".id_agency
                                       WHERE id_user = ?", false, $auth->id  
                        );
                        if(!empty($invites)) Response::SetArray('invites', $invites);
                        */
                    }                
                }

                // результат работы модуля в режиме ajax-запроса
                if($ajax_mode){
                    header("Content-type: application/json; charset=utf-8");
                    $ajax_result['http_code'] = $this->http_code;
                    if(!empty($module_template)){
                        $tpl = new Template($module_template, $this_page->module_path);
                        $module_content = $tpl->Processing();
                        $ajax_result['html'] = $module_content;
                        if( !DEBUG_MODE ) $ajax_result['module'] = $module_template;
                    }
                    if($this->page_cache_time>0){
                        $ajax_result['cache_in_date'] = date('d.m.Y H:i:s');
                        $memcache->set($page_signature, $ajax_result, FALSE, $this->page_cache_time);
                    }
                    echo Convert::json_encode($ajax_result);
                    exit(0);    
                }
                // результат работы модуля
                if($this->http_code==200){
                    if(empty($this->metadata['replace'])){
                        $non_unique_metadata = $this->metadata['title'] == $this->metadata['description'] &&  $this->metadata['description'] == $this->metadata['keywords'];
                        if(!empty($this->page_seo_title) && !empty($non_unique_metadata)) $this->metadata['title'] = $this->page_seo_title;
                        if(!empty($this->page_seo_description) && !empty($non_unique_metadata)) $this->metadata['description'] = $this->page_seo_description;
                        elseif(!empty($h1)){  //если < 3 слов в description, то равно h1
                            $exploded_description = preg_replace("![^a-zа-я0-9\s]!msiU","",$this->metadata['description']);
                            $exploded_description = explode(" ",trim($this->metadata['description']));
                            if(count($exploded_description)<=3) $this->metadata['description'] = $h1;
                        }                    
                        if(!empty($this->page_seo_keywords)) $this->metadata['keywords'] = $this->page_seo_keywords; 
                        elseif(!empty($h1)){  //если < 3 слов в keywords, то равно h1
                            $exploded_keywords = preg_replace("![^a-zа-я0-9\s]!msiU","",$this->metadata['keywords']);
                            $exploded_keywords = explode(" ",trim($this->metadata['keywords']));
                            if(count($exploded_keywords)<=3) $this->metadata['keywords'] = $h1;
                        }
                    }
                    if(!empty($this->page_seo_text)) $this->metadata['seo_text'] = $this->page_seo_text; 
                    
                    if(!empty($this->page_seo_breadcrumbs)) Response::SetArray('breadcrumbs', $this->page_seo_breadcrumbs);
                    elseif(!empty($this->page_breadcrumbs)) Response::SetArray('breadcrumbs', $this->page_breadcrumbs);

                    Response::SetArray('metadata', $this->metadata);
                    if(!($this->is_admin_page && !empty($module_content))){
                        if(empty($module_template)) $this->http_code = 404;
                        else {
                            $tpl = new Template($module_template, $this_page->module_path);
                            $module_content = $tpl->Processing();
                        }
                    }
                    if($this->first_instance){
                        // добавление css и js
                        $this->addScriptsAndCss();
                        // главное меню
                        if(!empty($this->menu[1])) Response::SetArray('mainmenu_first',$this->menu[1]);
                        if(!empty($this->menu[2])) Response::SetArray('mainmenu_second',$this->menu[2]);
                        if(!empty($this->menu[3])) Response::SetArray('submenu_second',$this->menu[3]);
                        
                        if(!empty($this->menu[4])) Response::SetArray('authmenu_first',$this->menu[4]);
                        if(!empty($this->menu[5])) Response::SetArray('authmenu_second',$this->menu[5]);
                        if(!empty($this->menu[6])) Response::SetArray('authsubmenu_second',$this->menu[6]);
                    }


                    if(!empty($module_template)) {
                        $module_content = str_replace('http://www.bsn.ru', 'https://www.bsn.ru', $module_content);
                        Response::SetString('content',$module_content);
                    }

                    if(!$this->first_instance) $result_html = $module_content;
                    else{ 
                        $tpl = new Template($this->page_template);   
                        $result_html = $tpl->Processing();
                    }
                    if($this->page_cache_time>0)
                        $memcache->set($page_signature, $result_html, FALSE, $this->page_cache_time);
                }
            }
        } 
        
        
        
        
        if($this->http_code!=200){
            // обработка ошибочных кодов страницы
            if($this->http_code==404){
                sendHTTPStatus(404);
                $GLOBALS['css_set'][] = '/css/404.css';
                $this->metadata['title'] = 'Ошибка 404. Страница не найдена.';
                Response::SetArray('metadata', $this->metadata);
                $error_template = '/templates/404.html'; 
                
                $session_404_order = Session::GetInteger('session_404_order');
                if(empty($session_404_order) || $session_404_order > 2) $session_404_order = 1;
                else ++$session_404_order;
                Session::SetInteger('session_404_order', $session_404_order);
                Response::SetInteger('session_404_order', $session_404_order);
            } elseif ($this->http_code == 403 ) {
                $GLOBALS['js_set'][] = '/js/jquery.min.js';
                $GLOBALS['js_set'][] = '/js/main.js';
                $GLOBALS['js_set'][] = '/js/interface.js';
                $GLOBALS['css_set'][] = '/css/common.css';
                $GLOBALS['css_set'][] = '/css/central.css';
                $GLOBALS['css_set'][] = '/css/topmenu.css';
                $GLOBALS['css_set'][] = '/css/final_corrections.css';
                $GLOBALS['css_set'][] = '/css/controls.css';
                sendHTTPStatus(403);
                $error_template = '/templates/403.html';
            } else {
                sendHTTPStatus(500);
                $error_template = '/templates/500.html';
                file_put_contents('situation.log',date('d.m.Y H:i:s')."\n".$this->requested_url."\n".$this->error_message."\n----------\n");
            }
            if($this->first_instance){
                $this->addScriptsAndCss();
                // главное меню
                if(!empty($this->menu[1])) Response::SetArray('mainmenu_first',$this->menu[1]);
                if(!empty($this->menu[2])) Response::SetArray('mainmenu_second',$this->menu[2]);
                if(!empty($this->menu[3])) Response::SetArray('submenu_second',$this->menu[3]);
                
                if(!empty($this->menu[4])) Response::SetArray('authmenu_first',$this->menu[4]);
                if(!empty($this->menu[5])) Response::SetArray('authmenu_second',$this->menu[5]);
                if(!empty($this->menu[6])) Response::SetArray('authsubmenu_second',$this->menu[6]);

            }
            Response::SetString('error_message',$this->error_message);
            $tpl = new Template($error_template);
            $result_html = $tpl->Processing();
        }
        
        return $result_html;
    }

    /**
    * добавление скриптов и стилей в шаблон
    *    
    */
    private function addScriptsAndCss(){
        global $memcache;
        $js_array = array_unique($GLOBALS['js_set'], SORT_REGULAR);
        $css_array = array_unique($GLOBALS['css_set'], SORT_REGULAR);
        $js_key  = md5('js::'.implode('|',$js_array));
        $css_key = md5('css::'.implode('|',$css_array));
        // ожидание освобождения файла с наборами js и css
        $counter = 50;
        do{
            $write_sig = $memcache->get('scripts_and_css_write_sig');
            if($write_sig!==false) usleep(10000);
            $counter--;
        }while($write_sig!==false && $counter);
        // загрузка файла с наборами js и css
        $scripts_and_css = FileData::Load();
        // если мы будем дополнять файл новыми наборами, блокируем файл для других
        if(empty($scripts_and_css['js'][$js_key]) || empty($scripts_and_css['css'][$css_key]))
            $write_sig = $memcache->set('scripts_and_css_write_sig',1);
        if(empty($scripts_and_css['counter'])) $scripts_and_css['counter'] = 0; // если файл был пустой
        // смотрим js
        if(empty($scripts_and_css['js'][$js_key])){
            $js_id = $scripts_and_css['counter']+1;
            $scripts_and_css['js'][$js_key] = array(
                'id' => empty($scripts_and_css['counter'])?1:$scripts_and_css['counter']+1,
                'files' => $js_array
            );
            $scripts_and_css['counter'] = $js_id;
        } else {
            $js_id = $scripts_and_css['js'][$js_key]['id'];
        }
        // смотрим css
        if(empty($scripts_and_css['css'][$css_key])){
            $css_id = $scripts_and_css['counter']+1;
            $scripts_and_css['css'][$css_key] = array(
                'id' => empty($scripts_and_css['counter'])?1:$scripts_and_css['counter']+1,
                'files' => $css_array
            );
            $scripts_and_css['counter'] = $css_id;
        } else {
            $css_id = $scripts_and_css['css'][$css_key]['id'];
        }
        // если мы дополняли - записываем и освобождаем файл для других
        if(!empty($write_sig)){
            FileData::Save($scripts_and_css);
            $memcache->delete('scripts_and_css_write_sig');
        }
        if( !$this->is_admin_page ) {
            include('js.php');
            include('css.php');
        }
        Response::SetInteger('js_id',$js_id);
        Response::SetInteger('css_id',$css_id);
    }

    /**
    * добавление хлебных крошек
    * 
    * @param string $title - название
    * @param string $url - url
    * @param integer $level - уровень вложенности
    */
    private function addBreadcrumbs($title, $url, $level=false, $list = false){
        if(empty($this->page_seo_breadcrumbs) || !empty($this->ab_test)){
            $level = $level!==false ? $level : count($this->page_breadcrumbs);
            if(!empty($list)){
                $new_list = [];
                foreach($list as $k => $item) $list[$k] = is_array($item) ? $item['title'] : $item;
            }
            if(!empty($title) && !empty($url)) $this->page_breadcrumbs[$level] = array('title'=>$title, 'level'=>$level-1, 'url'=>$url, 'list' => $list);
        }
    }
    /**
    * удаление хлебных крошек
    * 
    */
    private function clearBreadcrumbs(){
        //$this->page_breadcrumbs = [];
    }

    /**
    * управление метаданными страницы
    * 
    * @param array $module_metadata - массив title, keywords, description
    * @param boolean $replace полная замена значений
    * @param string $glue строка склейки
    */
    private function manageMetadata($new_metadata, $replace=false, $glue=' - '){
        if(!empty($new_metadata)){
            $this->metadata['replace'] = $replace;
            if(!empty($new_metadata['title'])){
                $this->metadata['title'] = $new_metadata['title'] . ($replace ? "" : (empty($this->metadata['title']) ? "" : $glue.$this->metadata['title']));
            }
            if(isset($new_metadata['keywords'])){
                $this->metadata['keywords'] = $new_metadata['keywords'] . ($replace ? "" : (empty($this->metadata['keywords']) ? "" : ', '.$this->metadata['keywords']));
            } elseif(empty($new_metadata['keywords']) || (strlen($new_metadata['keywords'])<10 && strlen($new_metadata['title'])>20)) {
                $this->metadata['keywords'] = ( trim( strtolower( $new_metadata['title'] ) ) ).', '.$this->metadata['keywords'];
            }
            if(isset($new_metadata['description'])){
                $this->metadata['description'] = $new_metadata['description'] . ($replace ? "" : (empty($this->metadata['description']) ? "" : '. '.$this->metadata['description']));
            }
            if(isset($new_metadata['seo_text'])) $this->metadata['seo_text'] = $new_metadata['seo_text'];
            
        }
    }
    
    /**
    * Формирование сигнатуры для кеширования страницы
    * в сигнатуру входят указанные при создании
    * объекты из массивов POST, SESSION и COOKIE
    * 
    * @return string Сигнатура
    */
    private function createPageSignature($custom_field=false){
        global $auth;
        $signature = 'bsn::'.$this->requested_url;
        // добавление в сигнатуру POST - параметров
        if(!empty($this->incacheobjects['post'])){
            $array = [];
            foreach($this->incacheobjects['post'] as $key){
                $array[$key] = Request::GetParameter($key,METHOD_POST);
            }
            $signature .= ":p:".sha1(Convert::ToString($array));
        }
        // добавление в сигнатуру Custom значениея
        if(!empty($custom_field)) $signature .= ":custom:".sha1(Convert::ToString($custom_field));  
  
        // добавление в сигнатуру COOKIE - параметров
        if(!empty($this->incacheobjects['cookie'])){
            $array = [];
            foreach($this->incacheobjects['cookie'] as $key){
                $array[$key] = Cookie::GetParameter($key);
            }
            $signature .= ":c:".sha1(Convert::ToString($array));
        }
        return $signature;
    }           

    /**
    * Проверка доступа текущего пользователя к странице с указанным путем
    * @param string путь к странице (если не задан - берется путь к текущей странице)
    * @param string проверяемые права (например 'r' | 'w' | 'rw')
    * @return boolean разрешение доступа
    */
    public function checkAccess($requested_path=null, $checkedRights=null){
        global $auth;
        if(empty($requested_path)) $requested_path = $this->requested_path;
        if(empty($checkedRights)) $checkedRights = 'r';
        // доступ для страницы (общий)
        $access_allow = true;
        for($i=0;$i<strlen($checkedRights);$i++){
            $access_allow = $access_allow && strpos($this->page_access, $checkedRights[$i].'-')===FALSE;
        }
        $page_path = "";
        // доступ для групп пользователей
        foreach($auth->group_rights as $right){
            if(strlen($right['path'])>strlen($page_path) && strpos($requested_path,$right['path'])===0){
                $page_path = $right['path'];
                for($i=0;$i<strlen($checkedRights);$i++){
                    $access_allow = $access_allow ? strpos($right['rights'],$checkedRights[$i].'-')===FALSE : strpos($right['rights'],$checkedRights[$i].'+')!==FALSE;
                }
            }
        }
        $page_path = "";
        // индивидуальный доступ для пользователя
        foreach($auth->user_rights as $right){
            if(strlen($right['path'])>strlen($page_path) && strpos($requested_path,$right['path'])===0){
                $page_path = $right['path'];
                for($i=0;$i<strlen($checkedRights);$i++){
                    $access_allow = $access_allow ? strpos($right['rights'],$checkedRights[$i].'-')===FALSE : strpos($right['rights'],$checkedRights[$i].'+')!==FALSE;
                }
            }
        }
        return $access_allow;
    }
    
    /**
    * Добавление элемента в меню
    * @param string заголовок пункта меню
    * @param string URL пункта меню
    * @param integer уровень меню (1 - главное,  2 - подменю, 4-меню справа)
    * @param boolean активность элемента - выбранный/текущий пункт 
    * @param string имя класса элемента 
    * @param string потомок меню
    * @param integer потомок меню
    */
    public function menuAdd($title, $url, $level=1, $active=false, $class=false, $child=false, $amount=false, $internal_link = false, $external_link = false){
        $menulevel = $level <= 1 ? 1 : $level;
        $active_state = $active                                                                         // принудительно установлена активность пункта
                        || (!empty($url) && substr($this->requested_url,0,strlen($url))==$url)          // URL совпадает с запрошенным урлом страницы
                        || (!empty($url) && substr($this->real_url,0,strlen($url))==$url)               // URL совпадает с реальным урлом страницы
                        || (empty($url) && (empty($this->requested_url) || empty($this->real_url)));    // главная страница
        if( !empty( $active_state ) ) {
            $this->menu[$menulevel-1]['active_state'] = true;
        }
        if($url == 'service' && strstr($this->real_url, 'ratings')!='') $active_state = false;
        if(!empty($child)) {
            if(empty($this->menu[$menulevel][$child])) $this->menu[$menulevel][$child] = [];
            array_push($this->menu[$menulevel][$child],array('title'=>$title, 'url'=>$url, 'active'=>$active_state, 'class'=>$class, 'amount'=>$amount, 'external_link' =>  !empty($external_link) ? true : false));
        } else $this->menu[$menulevel][] = array('title'=>$title, 'url'=>$url, 'active'=>$active_state, 'class'=>$class, 'amount'=>$amount);
        //если это верхнее меню, можем указать что элемент не должен быть <a>
        if($level == 4 && !empty($internal_link)) $this->menu[$menulevel][count($this->menu[$menulevel]) - 1]['internal_link'] = true;
    }
    
    /**
    * Очистка меню
    * @param integer уровень меню для очистки. 1, 2 - соответственно 1 или 2 уровень. Если не указан или не равен этим значениям - то очищается всё меню
    */
    public function menuClear($level=0){
        if($level===1 || $level===2 || $level===3) unset($this->menu[$level]);
        else unset($this->menu);
    }
    
    /**
    * Редактирование меню
    * @param integer уровень меню для редактирования
    * @param integer индекс элемента (с нуля)
    * @param string название
    * @param string url 
    * @param boolean  
    */
    public function menuEdit($level=0, $item, $title=null, $url=null, $active=null){
        if(!empty($this->menu[$level][$item])) {
            if(!is_null($title)) $this->menu[$level][$item]['title'] = $title;     
            if(!is_null($url)) $this->menu[$level][$item]['url'] = $url;     
            if(!is_null($active)) $this->menu[$level][$item]['active'] = $active;     
        }
    }
    /**
    * Переопределение шаблона окружения
    * @param string шаблон окружения
    */    
    public function setPageTemplate($template=false){
        if(!empty($template)) $this->page_template = $template;
        
    }
    /**
    * Получение рекламной метки
    * @param $url
    */    
    private function getMarkers($url=false){
        global $db;
        //определение реферера
        $referer = Host::getRefererURL();
        if(!empty($referer) && !Host::$is_bot){
            $url =  Host::$requested_uri;
            //получение метки
            $get = Request::GetParameters(METHOD_GET);
            //получение сессии
            $session_marker = Session::GetString('marker');
            if(!empty($get['from'])) {
                $item = $db->fetch("SELECT * FROM ".Config::$sys_tables['markers']." WHERE enabled = ? AND ? LIKE CONCAT(url,'%')",1,$get['from']);
                if(!empty($item)){
                    $marker =  $item['id'];
                    Session::SetString('marker',$item['id']);
                }
            } elseif(!empty($session_marker)) $marker = $session_marker;
            else  $marker = false;
            //сохранение статистики показов для метки
            if(!empty($marker)) $db->query("INSERT INTO ".Config::$sys_tables['markers_stats_show_day']." SET id_parent=?, url=?, ip=?, browser=?, ref=?",
                                                  $marker,$url,Host::getUserIp(),$_SERVER['HTTP_USER_AGENT'],Host::getRefererURL());
        }
        
    } 
    /**
    * Формирование меню по недвижке           
    */
    private function menuSecondLevelAdd(){
        global $db, $memcache;

        $this->menuAdd('Продажа', 'sell', 2, false, 'category-title', '/');
            $this->menuAdd('Квартиры в новостройках', 'build/sell', 3, false, false, 'sell');
            $this->menuAdd('Жилая недвижимость', 'live/sell', 3, false, false, 'sell');
            $this->menuAdd('Коммерческая недвижимость', 'commercial/sell', 3, false, false, 'sell');
            $this->menuAdd('Загородная недвижимость', 'country/sell', 3, false, false, 'sell');
            $this->menuAdd('Зарубежная недвижимость', 'inter/sell', 3, false, false, 'sell');

        $this->menuAdd('Аренда', 'rent', 2, false, 'category-title', '/');
            $this->menuAdd('Жилая недвижимость', 'live/rent', 3, false, false, 'rent');
            $this->menuAdd('Коммерческая недвижимость', 'commercial/rent', 3, false, false, 'rent');
            $this->menuAdd('Загородная недвижимость', 'country/rent', 3, false, false, 'rent');
            $this->menuAdd('Зарубежная недвижимость', 'inter/rent', 3, false, false, 'rent');
            
        $this->menuAdd('___________', 'complexes', 2, false, 'category-title', '/');
            $this->menuAdd('Жилые комплексы', 'zhiloy_kompleks', 3, false, false, 'complexes');
            $this->menuAdd('Апартаменты', 'apartments', 3, false, false, 'complexes');
            $this->menuAdd('Бизнес-центры', 'business_centers', 3, false, false, 'complexes');
            $this->menuAdd('Коттеджные поселки', 'cottedzhnye_poselki', 3, false, false, 'complexes');
        
        //меню медиа
        $this->menuAdd('Новости рынка',         'news',         2, false, false, 'media');
        $this->menuAdd('Статьи ',               'articles',     2, false, false, 'media');
        $this->menuAdd('Доверие потребителя',   'doverie',      2, false, false, 'media');
        $this->menuAdd('Мнения и интервью',     'opinions',     2, false, false, 'media');
        $this->menuAdd('БСН-ТВ',                'bsntv',        2, false, false, 'media');
        
        //меню сервисов
        $this->menuAdd('Оценка недвижимости',   'estate_estimate',      2, false, false, 'service');
        $this->menuAdd('Консультант',           'service/consultant',   2, false, false, 'service');
        $this->menuAdd('Календарь событий',     'calendar',             2, false, false, 'service');
        $this->menuAdd('Заявки',                'applications',         2, false, false, 'service');
        $this->menuAdd('Организации',           'organizations',        2, false, false, 'service');
    }
}
?>