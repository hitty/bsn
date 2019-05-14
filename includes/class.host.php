<?php
/**    
* Основной класс обработки запросов
*/

class Host {
    public static $requested_uri = '';          // запрошенный uri
    public static $requested_path = '';          // запрошенный uri
    public static $protocol = "http";           // протокол (как часть url)
    public static $host = "";                   // хост (как часть url)
    public static $port = "";                   // порт (как часть url)
    public static $path = "";                   // путь (как часть url)
    public static $query = "";                  // get-параметры (как часть url)
    public static $fragment = "";               // метка (как часть url)
    public static $user = "";                   // логин/имя пользователя (как часть url)
    public static $pass = "";                   // пароль (как часть url)
    public static $remote_user_ip = "";         // IP пользователя (прямой)
    public static $forwarded_user_ip = "";      // IP пользователя (вычисленный)
    public static $root_url = "";               // корневой URL сайта
    public static $root_path = "";              // путь от корня сервера до корня сайта
    public static $user_agent = "";             // путь от корня сервера до корня сайта
    public static $is_bot = false;              // определение бота
    public static $referer = "";                //Referer
    public static $referer_uri = "";            //URI referer'a
    public static $city = '';                   //город
    public static $country = '';                //страна
    public static $block_params = array(
          array( 'time' => 10, 'visits' => 7)
        , array( 'time' => 20, 'visits' => 12)
        , array( 'time' => 30, 'visits' => 18)
        , array( 'time' => 60, 'visits' => 25)
    );
    private static $bsn_ip_list = array("92.255.25.34","46.72.119.83","92.255.25.36");

    public static $static_urls = null;
    /**
    * вычисление всех необходимых системных переменных
    */
    public static function Init(){
        self::$requested_uri = (empty($_SERVER['REQUEST_URI'])?"":$_SERVER['REQUEST_URI']);
        //защита от двойного++ слеша
        if( strpos( self::$requested_uri, '//' ) != '' && strpos( self::$requested_uri, '/?' ) == '' ) self::Redirect( rtrim( str_replace( '//', '/', self::$requested_uri ),'/' ) );
        self::$requested_uri = trim(self::$requested_uri,'/');
        if(!empty(self::$requested_uri)) $url_info = parse_url(self::$requested_uri); else $url_info = [];
        if(empty($url_info['scheme'])){
            self::$protocol = "http";
            if(getenv("HTTPS") == "on" ) self::$protocol = "https";
        } else self::$protocol = $url_info['scheme'];
        if(!empty($url_info['path'])) self::$requested_path = trim($url_info['path'], '/');
        self::$port = !empty($url_info['port']) ? $url_info['port'] : getenv("SERVER_PORT");
        self::$host = !empty($url_info['host']) ? $url_info['host'] : (getenv("HTTP_HOST") ? getenv("HTTP_HOST") : getenv("SERVER_NAME"));
        self::$host = rtrim( self::$host, ":" . self::$port );
        self::$path = !empty($url_info['path']) ? trim($url_info['path'],'/') : "";
        self::$query = !empty($url_info['query']) ? trim($url_info['query'],'?') : "";
        self::$fragment = !empty($url_info['fragment']) ? trim($url_info['fragment'],'#') : "";
        self::$user = !empty($url_info['user']) ? $url_info['user'] : "";
        self::$pass = !empty($url_info['pass']) ? $url_info['pass'] : "";
        self::$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";

        if((self::$protocol=="http" && self::$port=="80") || (self::$protocol=="https" && self::$port=="443") ) {
            self::$root_url = sprintf( "%s://%s", self::$protocol, self::$host);
        } else {
            self::$root_url = sprintf( "%s://%s:%s", self::$protocol, self::$port, self::$host);
        }
        self::$root_path = $_SERVER['DOCUMENT_ROOT'];
        // кешируем в свойство адреса серверов статики (для более быстрого доступа)
        self::$static_urls = Config::Get('nginx/url');
        $overall_time_counter = microtime(true);
        self::$is_bot = self::isBot();
        self::getRefererURL();
        if(!self::$is_bot){
            require(ROOT_PATH."/modules/geoip/SxGeo.php");
            $SxGeo = new SxGeo(ROOT_PATH.'/modules/geoip/SxGeoCity.dat'); // Режим по умолчанию, файл бд SxGeo.dat
            $geo = $SxGeo->get(self::getUserIp());    // выполняет getCountry либо getCity в зависимости от типа базы
            self::$city = !empty($geo['city']['name_en']) ? $geo['city']['name_en'] : '';
            self::$country = !empty($geo['country']['iso']) ? $geo['country']['iso'] : '';
        }
    }

    /**
    * Redirects to location
    * @param string $location
    */
    public static function Redirect( $location, $type = 301, $url = true ) {
        if( !class_exists( 'Session' ) ) include_once( 'includes/class.storage.php' );
        $location = self::GetWebPath( $location );
        if($type == 301){
            Session::SetBoolean('redirect_301', true);
            header( "Location: " . $location .(strstr($location,'?')=='' && strlen($location)>19 ? ( !empty( $url ) ? '/' : '' )  : ''), true, 301);
        } else {
            header( "Location: " . $location .(strstr($location,'?')=='' && strlen($location)>19 ? '/' : ''));
        }
        
        
        
        exit();
    }
    /**
    * Redirects to 1 level up 
    * @param string $location
    */
    public static function RedirectLevelUp(  ) {
        $location = explode("/", self::$requested_uri);
        unset($location[ count($location)-1 ]);
        self::Redirect('/' . implode('/', $location) . '/');
        exit();
    }    
    /**
    * Получение абсолютного полного URL по относительному URI от корня сайта или полному URL
    * @param string URI (от корня) или URL (полный)
    * @return string URL (абсолютный)
    */
    public static function getWebPath( $uri="" ) {
        $uri = trim($uri,'/');
        $url_info = parse_url($uri);
        if(!empty($url_info['scheme']) && !empty($url_info['host'])) return $uri;
        return sprintf("%s/%s", self::$root_url, $uri);
    }

    /**
    * Generate short uri
    * @param string $real_uri
    */
    public static function generateShortUri(  $real_uri ) {
        global $db;
        do{
            $uri = substr( sha1( sha1( microtime() ) ), 0, 8 );
            $item = self::getShortUri($uri, false) ;
        } while( !empty($item));
        $db->query("INSERT INTO " . Config::Get('sys_tables/short_uri') ." SET short_uri = ?, real_uri = ? ", $uri, $real_uri);
        return $uri;
    }
        
    /**
    * Generate short uri
    * @param string $real_uri
    */
    public static function getShortUri(  $uri, $redirect = true ) {
        global $db;
        $item = $db->fetch("SELECT real_uri FROM " . Config::Get('sys_tables/short_uri') ." WHERE short_uri = ? ", $uri);
        if(!empty($item) && !empty($redirect) ) Host::Redirect( trim( $item['real_uri'], '/') );
        return $item;
    }
        
    /**
    * Получение абсолютного полного URL статики по относительному URI от корня сайта
    * @param string URI (от корня) или URL (полный)
    * @return string URL (абсолютный)
    */
    public static function getImgUrl( $uri="" , $number=null, $path=false ) {
        if( !empty($path) ) {
            if(strstr(self::$requested_uri, 'inter')!='' || strstr(self::$requested_uri, 'photos/inter')!='') return 'http://interestate.ru/';
            else return "/";
        }
        $uri = trim($uri,'/');
        if(is_null($number)) $number = mt_rand(0,sizeof(self::$static_urls)-1);
        return sprintf("%s/%s", self::$static_urls[$number], $uri);
    }

    /**
     * Получение физического пути файла по uri
     * @param string uri от корня сайта
     * @return string физический полный путь к файлу на сервере
     */
    public static function getRealPath( $uri='/' ) {
        return sprintf( "%s/%s", self::$root_path, trim($uri,'/'));
    }

    /**
     * Getting Requested Uri
     * @return string
     */
    public static function getRequestedUri() {
        return self::$requested_uri;
    }

    /**
     * Getting Current Uri Without (Get) Params
     * @return unknown
     */
    public static function getRequestedUriEx() {
        return preg_replace( '/\?.*$/', '', self::$requested_uri);
    }

    /**
    * Получение Реферальной ссылки
    * @return mixed referer link or false
    */
    public static function getRefererURL() {
        self::$referer = getenv('HTTP_REFERER');
        $referer_parse = parse_url(self::$referer);
        self::$referer_uri = trim($referer_parse['path'], '/');
        return self::$referer;
    }
    
    /**
    * Получение IP пользователя
    * @param bool если true - IP через прокси, если false - прямой IP
    * @return string IP
    */
    public static function getUserIp( $forwarded = false ){
        if($forwarded){ if(!empty(self::$forwarded_user_ip)) return self::$forwarded_user_ip; }
        else{ if(!empty(self::$remote_user_ip)) return self::$remote_user_ip; }
        if(!$forwarded) $user_ip = getenv('REMOTE_ADDR');
        elseif(getenv('HTTP_FORWARDED_FOR')) $user_ip = getenv('HTTP_FORWARDED_FOR');
        elseif(getenv('HTTP_X_FORWARDED_FOR')) $user_ip = getenv('HTTP_X_FORWARDED_FOR');
        elseif(getenv('HTTP_X_COMING_FROM')) $user_ip = getenv('HTTP_X_COMING_FROM');
        elseif(getenv('HTTP_VIA')) $user_ip = getenv('HTTP_VIA');
        elseif(getenv('HTTP_XROXY_CONNECTION')) $user_ip = getenv('HTTP_XROXY_CONNECTION');
        elseif(getenv('HTTP_CLIENT_IP')) $user_ip = getenv('HTTP_CLIENT_IP');
        else return 'unknown';
        $user_ip = trim($user_ip);
        if(strlen($user_ip) > 15){
            $ar = split (', ', $user_ip);
            $ar_size = sizeof($ar)-1;
            for ($i= $ar_size; $i> 0; $i--){
                if($ar[$i]!='' and !preg_match('|[^\d\.]|', $ar[$i])){
                    $user_ip = $ar[$i];
                    break; 
                }
                if($i== sizeof($ar)-1) $user_ip = 'unknown';
            }
        }
        if(preg_match('|[^\d\.]|', $user_ip)) return 'unknown';
        if($forwarded) self::$forwarded_user_ip = $user_ip;
        else self::$remote_user_ip = $user_ip; 
        return $user_ip;
    }

    /**
    * Получение параметра из URL
    *       параметр в урл передается в одном из видов:
    *       - part-of-url/name-VALUE/part-of-url (для числовых и стрчных значений)
    *       - part-of-url/name_VALUE/part-of-url (для числовых и стрчных значений)
    *       - part-of-url/nameVALUE/part-of-url (только для числовых значений)
    * @param string имя параметра
    * @param boolean удалять ли параметр из URL
    * @return mixed значение параметра
    */
    public static function getParameterFromURL($name,$remove=false){
        if(!empty(self::$path)){
            $parameters = preg_split("![\/\?\&\#]!",self::$path);
            foreach($parameters as $param){
                if(preg_match("!^".$name."[\-\_\=]{1}([^\/\&]+)$!i",$param,$match)){
                    if($remove) self::$path = trim(str_replace(array('//','/?','/&','?&','&&'),array('/','?','?','?','&'),str_replace($match[0],"",self::$path)),'/?&');
                    return $match[1];
                }
                if(preg_match("!^".$name."(\d+)$!i",$param,$match)){
                    if($remove) self::$path = trim(str_replace(array('//','/?','/&','?&','&&'),array('/','?','?','?','&'),str_replace($match[0],"",self::$path)),'/?&');
                    return $match[1];
                }
            }
        }
        return null;
    }

    /**
    * Получение содержимого страницы по URL (полному или относительному)
    * @param string url
    * @return mixed content
    */
    public static function GetWebContent($url){
        $result = '';
        if(!empty( $url )){
            $url = self::GetWebPath($url);
            if($ch = curl_init($url)){
                $opt = array(
                    CURLOPT_FAILONERROR => 1,
                    CURLOPT_FOLLOWLOCATION => 1,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_HEADER => 0,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_CONNECTTIMEOUT => 5
                );
                if(curl_setopt_array($ch, $opt)){
                    $result = curl_exec($ch);
                    if(curl_errno($ch)) $result = curl_error($ch)." [ $url ]";
                    curl_close($ch);
                } else $result = "Error: Curl set opt error ($url)";
            } else $result = "Error: can not get content ($url)";
        }
        return $result;
    }
    /**
    * Определение посетителя как робот поисковой системы
    * @return boolean
    */    
    public static function isBsn($tablename = false, $id_to_click = false) {
        global $db;
        $ip = self::getUserIp();
        if(!in_array($ip,self::$bsn_ip_list)) return false;
        elseif(empty($tablename) && (empty($id_to_click) || !Validate::isDigit($id_to_click))) return true;
        else{
            //смотрим, кликали ли уже сегодня по этой штуке
            $clicked_already = $db->fetch("SELECT id FROM ".Config::$values['sys_tables'][$tablename]." WHERE ip = ? AND id_parent = ?",$ip,$id_to_click);
            return (!empty($clicked_already) && !empty($clicked_already['id']));
        }
    }
    public static function isBot($user_agent = false){
    /* Эта функция будет проверять, является ли посетитель роботом поисковой системы */
        $bots = 'rambler|agooglebot|googlebot|aport|yahoo|msnbot|turtle|mail.ru|omsktele|yetibot|picsearch|sape.bot|sape_context|gigabot|snapbot|alexa.com|megadownload.net|askpeter.info|igde.ru|ask.com|qwartabot|yanga.co.uk|scoutjet|similarpages|oozbot|shrinktheweb.com|aboutusbot|followsite.com|dataparksearch|google-sitemaps|appEngine-google|feedfetcher-google|liveinternet.ru|xml-sitemaps.com|agama|metadatalabs.com|h1.hrn.ru|googlealert.com|seo-rus.com|yaDirectBot|yandeG|yandex|yandexSomething|Copyscape.com|AdsBot-Google|domaintools.com|Nigma.ru|bing.com|dotnetdotcom|Apache-HttpClient|SputnikBot|Baiduspider|GrapeshotCrawler|MJ12bot|Spider|AhrefsBot|Twitterbot|YandexBot|Mail.RU_Bot|CCBot|NTENTbot|Exabot|HybridBot|Mediapartners-Google|ltx71|Slackbot|facebookexternalhit|buck|SemrushBot|DotBot|trendictionbot';
        $user_agent = (!empty($user_agent) ? $user_agent : self::$user_agent);
        if(empty($user_agent)) return true;
        return (bool) preg_match("/".$bots."/sui", $user_agent);
    }
    /**
    * Проверяем ip пользоваеля на наличие в черном списке, забитом вручную + список из базы
    * 
    * @param mixed $user_ip = false - при необходимости проверить какой-то отдельный ip, передаем его
    */
    public static function checkUser($user_ip = false, $block = false, $disable_ajax_mode = false){
        global $ajax_mode, $this_page, $db;
        $sys_tables = Config::Get('sys_tables');
        $redirect_301 = Session::GetBoolean('redirect_301');
        if(!empty($redirect_301)){
            Session::SetBoolean('redirect_301', false);
            return false;    
        }
        if(self::isBot() || (empty($disable_ajax_mode) && $ajax_mode) || substr(self::$requested_uri, 0, 5) == 'admin' || substr(self::$requested_uri, 0, 11) == 'ip_validate' || substr(self::$requested_uri, 0, 3) == 'img' || substr(self::$requested_uri, 0, 10) == 'csp-report' || substr(self::$requested_uri, 0, 8) == 'news_rss' || substr(self::$requested_uri, 0, 7) == 'members' || substr(self::$requested_uri, 0, 3) == 'pay') return false;
        //проверка IP в черном списке
        $ip = self::checkBlacklist($user_ip) ;
        if(!empty($ip)){
            Session::SetString('referer', self::$requested_uri);
           // self::Redirect('/ip_validate/');
        }
       
    }
    
    public static function checkBlacklist($user_ip = false){
        global $db;
        $ip = !empty($user_ip) ? $user_ip : self::getUserIp();
        return $db->fetch("     SELECT 
                                    * 
                                FROM 
                                    ".Config::Get('sys_tables')['blacklist_ips']." 
                                WHERE 
                                    ( `range` = 1 AND ? LIKE CONCAT(ip, '%') ) OR 
                                    (  `range` = 2 AND ip = ? )
                                ", $ip, $ip
        );
    }
    /**
    * Проверка на мобильную версию
    * @return boolean
    */    
    public static function isMobile(){
        $useragent = self::$user_agent;
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) return true;
        return false;

    }      

}
?>