<?
/**
* Class for storing ierarchic url schema
*/
class pagetree
{
    function __construct($url=null,$level=null){
        //если передан, сразу записываем адрес страницы
        $this->node=(empty($url))?"":$url;
        $this->level=$level;
        $this->children=[];
    }
    //url узла
    public $node="";
    //уровень вложенности
    public $level=0;
    //потомки
    public $children=[];
}
/**
* Class for parsing, generating sitemap
*/
class sitemap
{
    //массив всех url, которые войдут в sitemap
    public $sitemap_urls = [];
    //массив тегов lastmod для всех url
    public $lastmod = [];
    //массив частот обновления
    public $changefreq = [];
    //массив приоритетов
    public $priority = [];
    //нужно для curl
    private $base;
    private $protocol;
    private $domain;
    private $check = [];
    private $proxy = "";
    //url, записанных в файлы
    public $links_mapped=0;
    //url всего
    public $links_total=0;
    //число записанных sitemap
    private $maps_number=0;
    private $file_number=0;
    //число записанных sitemap
    private $file_list = [];
    public $folder_tmp = 'sitemaps/tmp/';
    public $folder = 'sitemaps/';
    //число записанных sitemap c 404 из webmaster
    private $maps404_number = 0;
    //url-404, записанных в файлы
    public $links404_mapped = 0;
    //url-404 всего
    public $links404_total = 0;
    
    public $links_per_file = 39500; //url в одном файле sitemap
    public $filename = 'sitemap'; //имя файла
    //флаг: TRUE - sitemap с 404 из webmaster есть
    private $sitemap404=FALSE;
    //NOT USED:
    public $maps_limit = 200;
    private $status_time='';
    private $prev_status_time='';
    private $url_to_sitemap = '';
    public $maps_limit_reached=false;
    private $latest_lastmod = []; //ластмод для итогового сайтмепа
    
    //чистим массивы sitemap_urls,lastmod,changefreq,priority
    //нужно для записи в sitemap url-404 из webmaster
    /**
    * Clearing arrays with urls and its attributes
    * @return bool
    */   
    public function reset_sitemap_urls(){
        unset($this->sitemap_urls);
        unset($this->lastmod);
        unset($this->changefreq);
        unset($this->priority);
        return TRUE;
    }
    
    //добавляем url с атрибутами в массив sitemap_urls
    /**
    * Adding url with attributes for sitemap
    * @param mixed $url             //url to add
    * @param string $lastmod        //date in W3C
    * @param string $changefreq     //change frequency
    * @param string $priority       //page priority 
    * @param bool $is404            //if 404 from webmaster -> TRUE
    */
    public function add_sitemap_url($url,$lastmod,$changefreq,$priority,$is404=FALSE){
        $this->sitemap_urls[]=$url;
        $this->lastmod[]=$lastmod;
        $this->changefreq[]=$changefreq;
        $this->priority[]=$priority;
        if ($is404) ++$this->links404_total;
        else ++$this->links_total;
    }
    
    //подстановка нужного url
    //NOT USED
    public function set_url($url){
        $this->url_to_sitemap=$url;
    }
    
    //игнорируемые расширения файлов 
    //например: (".js",".css",...)
    /**
    * Setting file extentions to ignore while parsing
    * @param array $ignore_list       //list of extensions
    * @return  bool
    */    
    public function set_ignore($ignore_list){
        $this->check = $ignore_list;
        return TRUE;
    }
  
    
    //set a proxy host and port (such as someproxy:8080 or 10.1.1.1:8080
    /**
    * Set a proxy host and port (such as someproxy:8080 or 10.1.1.1:8080
    * @param string $host_proxy      
    */    
    public function set_proxy($host_port){
        $this->proxy = $host_port;
    }
    
    //url проверяется на наличие игнорируемых расширений
    /**
    * Check if url contains ignoring file extensions
    * @param string $url       //url to check
    * @return  bool
    */    
    private function validate($url){
        $valid = true;
        //add substrings of url that you don't want to appear using set_ignore() method
        foreach($this->check as $val)
        {
            if(stripos($url, $val) !== false)
            {
                $valid = false;
                break;
            }
        }
        return $valid;
    }
 
    //пробегаем по списку url, с помощью curl-запросов получаем контент, потом парсим его
    /**
    * Recursive parsing of url list
    * @param array $urls       //urls to parse
    * @return  bool
    */    
    private function multi_curl($urls){
        // for curl handlers
        $curl_handlers = [];
        //setting curl handlers
        foreach ($urls as $url)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);//указываем url
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//указываем, что строка результата не должна быть выдана в браузер
            curl_setopt($curl,CURLOPT_FILETIME,true);//указываем, что нужно время изменения файла
            if (isset($this->proxy) && !$this->proxy == '')
            {
                curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
            }
            $curl_handlers[] = $curl;
        }
        //initiating multi handler
        $multi_curl_handler = curl_multi_init();
 
        // adding all the single handler to a multi handler
        foreach($curl_handlers as $key => $curl)
        {
            curl_multi_add_handle($multi_curl_handler,$curl);
        }
 
        // executing the multi handler
        do
        {
            $multi_curl = curl_multi_exec($multi_curl_handler, $active);
        }
        while ($multi_curl == CURLM_CALL_MULTI_PERFORM  || $active);
 
        foreach($curl_handlers as $curl)
        {
            //checking for errors
            if(curl_errno($curl) == CURLE_OK)
            {
                //if no error then getting content
                $content = curl_multi_getcontent($curl);
                $this->sitemap_dates[]=curl_getinfo($curl,CURLINFO_FILETIME);
                //parsing content
                $this->parse_content($content);
            }
        }
        curl_multi_close($multi_curl_handler);
        return true;
    }
    
    //для site_urls.php - curling url list
    /**
    * Curling url array (it may be field of xml) with pause between curlings.
    * @param mixed $urls            //urls to parse
    * @param string $prefix         //to concat with url
    * @param string $login          //for HTTP-auth
    * @param string $password       //for HTTP-auth
    * @param int $limit             //limit for url number
    * @param bool $bsn_to_pingola   //flag, if (in/not in archive needed)
    * @return  mixed
    */
    public function multi_curl_site_urls($urls,$prefix=null,$login=null,$password=null,$limit=null,$bsn_to_pingola=false){
         global $log;
        //счетчики 404 и 0
        $i_404=0;$i_0=0;
        
        //для curl handlers
        $curl_handlers = [];
        //массив-результат
        $result=[];
        //массив ответов сервера
        $result['server_answers']=[];
        //title прочитанных страниц
        $result['pages_titles']=[];
        //время прочтения страниц
        $result['pages_date']=[];
        //в архиве объект, или нет
        $result['in_archive']=[];
        $i=0;
        //счетчик url
        $urls_processed=0;
        //setting curl handlers
        
        foreach ($urls as $url){
            
            //создаем корректный url
            if (gettype($url)!='string'){
                if (!$bsn_to_pingola){
                    //если встретился старый url - заменяем на новый
                    $url['url']=str_replace('www.bsn.ru','new.bsn.ru',$url['url']);
                }
                //если в url - строка без 'http://', прибавляем переданный префикс (http://new.bsn.ru)
                if(strpos($url['url'],'https://')===FALSE){$url['url']=$prefix.$url['url'];}
                $url=$url['url'];
            }
            else{
                if (!$bsn_to_pingola){
                    //если встретился старый url - заменяем на новый
                    $url=str_replace('www.bsn.ru','new.bsn.ru',$url);
                }
                //если в url - строка без 'http://', прибавляем переданный префикс (http://new.bsn.ru)
                if(strpos($url,'https://')===FALSE){$url=$prefix.$url;}
            }
            
            //инициализируем отдельный curl для каждой строки
            $curl = curl_init();
            
            //если переданы login и password, устанавливавем их в curl
            if (!empty($login)&&(!empty($password))){
                $loginpwd=(!empty($login)&&!empty($password))?$login.":".$password:'';//строчка "LOGIN:PASSWORD"
                curl_setopt($curl, CURLOPT_USERPWD, $loginpwd); // HTTP аутентификация
            }
            //указываем url
            curl_setopt($curl, CURLOPT_URL, $url);
            //указываем, что строка результата не должна быть выдана в браузер
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            if (isset($this->proxy) && !$this->proxy == ''){
                curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
            }
            
            //добавляем созданный curl в массив 
            $curl_handlers[] = $curl;
            $i++;
            
            //если передан $limit и он превышен, то выходим
            if ((!empty($limit))&&($i>$limit)){break;}
        }
        
        //инициализируем multi handler
        $multi_curl_handler = curl_multi_init();

        //добавляем все одиночные handler в multi handler
        foreach($curl_handlers as $key => $curl){
            curl_multi_add_handle($multi_curl_handler,$curl);
        }
        
        //исполняем multi handler
        do{
            $multi_curl = curl_multi_exec($multi_curl_handler, $active);
        }
        while ($multi_curl == CURLM_CALL_MULTI_PERFORM  || $active);

        $i=0;
        //перебираем curl и читаем параметры
        foreach($curl_handlers as $curl){
            
            //если ответ - 0, делаем запрос еще раз
            $j=0;
            while (curl_getinfo($curl, CURLINFO_HTTP_CODE)==0){
                curl_exec($curl);
                 "0 code";
                $j++;
                if ($j>1){break;}
            }
            
            //записываем ответ сервера
            array_push($result['server_answers'],curl_getinfo($curl, CURLINFO_HTTP_CODE));
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE)==404){$i_404++;}
            
            //записываем время прочтения страницы
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE)!=0)
            {array_push($result['pages_date'],date("Y:m:d",time()));}else
            {array_push($result['pages_date'],0);$i_0++;}
            
            //если ответ получен
            if(curl_errno($curl) == CURLE_OK){
                
                //читаем текст страницы
                $content = curl_multi_getcontent($curl);
                
                //определяем, где начинается и где кончается title
                $title_start=strpos($content,'<title>');
                $title_end=strpos($content,'</title>');
                
                //если title есть, то пишем в массив
                if (($title_start!==FALSE)&&($title_end!==FALSE)){
                    //записываем title
                    $title=substr($content,$title_start+7,$title_end-$title_start-7);
                    array_push($result['pages_titles'],$title);
                }
                //если его нет, то пишем пусто
                else{
                    array_push($result['pages_titles'],"");
                }
                
                //если нужно, определяем, в архиве ли объект (нужно для bsn_to_pingola)
                if (($bsn_to_pingola)&&($result['server_answers'][$i])==200){
                    if (preg_match('/<p>Объявление утратило актуальность/siu',$content)){
                        array_push($result['in_archive'],'2');
                    }
                    else{
                        array_push($result['in_archive'],'1');
                    }
                }
                else{
                    array_push($result['in_archive'],'3');
                }
            }
            else{
                //если ответа нет, то пишем пустые значения
                array_push($result['pages_titles'],"");
                if ($bsn_to_pingola) array_push($result['in_archive'],'3');
            }
            $i++;
            //если лимит превышен, выходим
            if ((!empty($limit))&&($i>$limit)){break;}
        }
        curl_multi_close($multi_curl_handler);
        $log [] = "!0!=".$i_0;
        $log [] = "404=".$i_404;flush();
        
        return $result;
    }    
   
    //нужно для parser_pingola.php - curling url list with pauses
    //prefix - то, что возможно нужно приписывать слева к переданным url 
    /**
    * Curling url array (it may be field of xml) with pause between requests.
    * @param mixed $urls            //urls to parse
    * @param string $prefix         //to concat with url
    * @param string $login          //for HTTP-auth
    * @param string $password       //for HTTP-auth
    * @param int $limit             //limit for url number
    * @param bool $bsn_to_pingola   //flag, if (in/not in archive needed)
    * @return  mixed
    */
    public function curl_site_urls($urls,$prefix=null,$login,$password,$limit=null,$bsn_to_pingola=false){
        global $log;
        //счетчики 404 и 0
        $i_404=0;$i_0=0;
        //для curl handlers
        $curl_handlers = [];
        //массив-результат
        $result=[];
        //массив ответов сервера
        $result['server_answers']=[];
        //title прочитанных страниц
        $result['pages_titles']=[];
        //время прочтения страниц
        $result['pages_date']=[];
        //в архиве объект, или нет
        $result['in_archive']=[];
        $i=0;
        //счетчик url
        $urls_processed=0;
        
        //setting curl handlers
        foreach ($urls as $url){
            
            //собираем корректный url
            if (gettype($url)!='string'){
                if (!$bsn_to_pingola){
                    //если встретился старый url - заменяем на новый
                    $url['url']=str_replace('www.bsn.ru','new.bsn.ru',$url['url']);
                }
                //если в url - строка без 'http://', прибавляем переданный префикс (http://new.bsn.ru)
                if(strpos($url['url'],'https://')===FALSE){$url['url']=$prefix.$url['url'];}
                $url=$url['url'];
            }
            else{
                if (!$bsn_to_pingola){
                    //если встретился старый url - заменяем на новый
                    $url=str_replace('www.bsn.ru','new.bsn.ru',$url);
                }
                //если в url - строка без 'http://', прибавляем переданный префикс (http://new.bsn.ru)
                if(strpos($url,'https://')===FALSE){$url=$prefix.$url;}
            }
            
            //инициализируем отдельный curl для каждой строки
            $curl = curl_init();
            
            //если указаны login и password, устанавливаем их в curl
            if (!empty($login)&&(!empty($password))){
                $loginpwd=(!empty($login)&&!empty($password))?$login.":".$password:'';//строчка "LOGIN:PASSWORD"
                curl_setopt($curl, CURLOPT_USERPWD, $loginpwd); // HTTP аутентификация
            }
            //указываем url
            curl_setopt($curl, CURLOPT_URL, $url);
            //указываем, что строка результата не должна быть выдана в браузер
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            if (isset($this->proxy) && !$this->proxy == ''){
                curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
            }
            
            //запускаем curl для этого url
            curl_exec($curl);
            //если получен ответ 0, то пробуем повторить
            while (curl_getinfo($curl, CURLINFO_HTTP_CODE)==0){
                curl_exec($curl);
                $log [] = "0 code";
                $j++;
                if ($j>1){break;}
            }
            
            //записываем ответ сервера
            array_push($result['server_answers'],curl_getinfo($curl, CURLINFO_HTTP_CODE));
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE)==404){$i_404++;}
            
            //записываем время прочтения страницы
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE)!=0)
            {array_push($result['pages_date'],date("Y:m:d",time()));}else
            {array_push($result['pages_date'],0);$i_0++;}
            
            //если ответ получен
            if(curl_errno($curl) == CURLE_OK){
                
                //читаем текст страницы
                $content = curl_multi_getcontent($curl);
                
                //определяем, где начинается и где кончается title
                $title_start=strpos($content,'<title>');
                $title_end=strpos($content,'</title>');
                
                //если title есть, то пишем в массив
                if (($title_start!==FALSE)&&($title_end!==FALSE)){
                    //записываем title
                    $title=substr($content,$title_start+7,$title_end-$title_start-7);
                    array_push($result['pages_titles'],$title);
                }
                //если его нет, то пишем пусто
                else{
                    array_push($result['pages_titles'],"");
                }
                
                //если нужно, определяем, в архиве ли объект (нужно для bsn_to_pingola)
                if (($bsn_to_pingola)&&($result['server_answers'][$i])==200){
                    if (preg_match('/<p>Объявление утратило актуальность/siu',$content)){
                        array_push($result['in_archive'],'2');
                    }
                    else{
                        array_push($result['in_archive'],'1');
                    }
                }
                else{
                    array_push($result['in_archive'],'3');
                }
            }
            else{
                //если ответа нет, добавляем пустые значения
                array_push($result['pages_titles'],"");
                if ($bsn_to_pingola) array_push($result['in_archive'],'3');
            }
            
            //ждем перед следующим запросом
            sleep(2);
            $i++;
        }
        $log [] = "!0!=".$i_0;
        $log [] = "404=".$i_404;flush();
        
        return $result;
    }
    
    //function to call to start parse
    /**
    * This will start recursive parsing
    * @param array $domain       //url where we start
    * @return  bool
    */    
    public function get_links($domain){
        //getting base of domain url address
        $this->base = str_replace("https://", "", $domain);
        $this->base = str_replace("https://", "", $this->base);
        $host = explode("/", $this->base);
        $this->base = $host[0];
        //getting proper domain name and protocol
        $this->domain = trim($domain);
        if(strpos($this->domain, "https") !== 0)
        {
            $this->protocol = "https://";
            $this->domain = $this->protocol.$this->domain;
        }
        else
        {
            $protocol = explode("//", $domain);
            $this->protocol = $protocol[0]."//";
        }
 
        if(!in_array($this->domain, $this->sitemap_urls))
        {
            $this->sitemap_urls[] = $this->domain;
        }
        //requesting link content using curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->domain);
        if (isset($this->proxy) && !$this->proxy == '')
        {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl,CURLOPT_FILETIME,true);
        $page = curl_exec($curl);
        $this->sitemap_dates[] = curl_getinfo($curl, CURLINFO_FILETIME);
        curl_close($curl);
        $this->parse_content($page);
    }
 
    //parses content and checks for URLs
    /**
    * Searching for links on page, then parsing
    * @param array $page       //page url to parse
    * @return  bool
    */    
    private function parse_content($page){
        global $log;
        //getting all links from href attributes
        preg_match_all("/<a[^>]*href\s*=\s*'([^']*)'|".
                    '<a[^>]*href\s*=\s*"([^"]*)"'."/is", $page, $match);
        //storing new links
        $new_links = [];
        for($i = 1; $i < sizeof($match); $i++)
        {
            //walking through links
            foreach($match[$i] as $url)
            {
                //if doesn't start with http and is not empty
                if(strpos($url, "https") === false  && trim($url) !== "")
                {
                    //checking if absolute path
                    if($url[0] == "/") $url = substr($url, 1);
                    //checking if relative path
                    else if($url[0] == ".")
                    {
                        while($url[0] != "/")
                        {
                            $url = substr($url, 1);
                        }
                        $url = substr($url, 1);
                    }
                    //transforming to absolute url
                    $url = $this->protocol.$this->base."/".$url;
                }
                //if new and not empty
                if(!in_array($url, $this->sitemap_urls) && trim($url) !== "")
                {
                    //if valid url
                    if($this->validate($url))
                    {
                        //checking if it is url from our domain
                        if(strpos($url, "https://".$this->base) === 0 || strpos($url, "https://".$this->base) === 0)
                        {
                            //adding url to sitemap array
                            $this->sitemap_urls[] = $url;
                            //adding url to new link array
                            $new_links[] = $url;
                        }
                    }
                }
            }
        }
        
        //проверяем сколько новых ссылок
        if (count($this->sitemap_urls)>($this->maps_number*$this->links_per_file+$this->links_per_file))
        {
            //если накопилось достаточно новых ссылок для записи нового файла
            $file=$this->filename.$this->maps_number.'.xml';
            $count_new=count($this->sitemap_urls)-$this->maps_number*$this->links_per_file;
            //считаем сколько всего ссылок
            $this->links_total+=$count_new;
            do{
                $map=$this->generate_sitemap();
                file_put_contents($file,$map);
                $log[] = "sitemap".$this->maps_number.".xml<br>";
                $this->maps_number++;
            }//проверяем не хватит ли остатков на еще один файл
            while(($this->links_total-$this->maps_number*$this->links_per_file)>$this->links_per_file);
            
            
            if ($this->maps_number>$this->maps_limit){
                $this->maps_limit_reached=true;
                $this->finish_maps();
                $this->generate_sitemap_index();
                $log[] = "Finished";
                exit(0);
            }
        }
        else{
            //set_time_limit(0);
            $this->status_time=date('Y-m-d H:i:s');
            //пишем лог каждые 1 минут
            if (abs(strtotime($this->status_time) - strtotime($this->prev_status_time))/60.2>1){
                file_put_contents('sitemapgen_status'.date('H_i_s').'.txt','urls found: '.count($this->sitemap_urls));
                $this->prev_status_time=$this->status_time;
            }
            
        }
        // делаем запросы к полученным url
        $this->multi_curl($new_links);
        return true;
    }
 
    //returns array of sitemap URLs
    /**
    * Returns array of sitemap urls (sitemap_urls)
    * @param array $page       //page url to parse
    * @return  bool
    */
    public function get_[]{
        return $this->sitemap_urls;
    }
 
    //notifies services like google, bing, yahoo, ask and moreover about your site map update
    //NOT USED
    public function ping($sitemap_url, $title ="", $siteurl = ""){
        /*
        // for curl handlers
        $curl_handlers = [];
 
        $sitemap_url = trim($sitemap_url);
        if(strpos($sitemap_url, "http") !== 0)
        {
            $sitemap_url = "http://".$sitemap_url;
        }
        $site = explode("//", $sitemap_url);
        $start = $site[0];
        $site = explode("/", $site[1]);
        $middle = $site[0];
        if(trim($title) == "")
        {
            $title = $middle;
        }
        if(trim($siteurl) == "")
        {
            $siteurl = $start."//".$middle;
        }
        //urls to ping
        //$urls[0] = "http://www.google.com/webmasters/tools/ping?sitemap=".urlencode($sitemap_url);
        //$urls[1] = "http://www.bing.com/webmaster/ping.aspx?siteMap=".urlencode($sitemap_url);
        //$urls[2] = "http://search.yahooapis.com/SiteExplorerService/V1/updateNotification".
                "?appid=YahooDemo&url=".urlencode($sitemap_url);
        //$urls[3] = "http://submissions.ask.com/ping?sitemap=".urlencode($sitemap_url);
        //$urls[4] = "http://rpc.weblogs.com/pingSiteForm?name=".urlencode($title).
        //        "&url=".urlencode($siteurl)."&changesURL=".urlencode($sitemap_url);
 
        //setting curl handlers
        foreach ($urls as $url)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURL_HTTP_VERSION_1_1, 1);
            $curl_handlers[] = $curl;
        }
        //initiating multi handler
        $multi_curl_handler = curl_multi_init();
 
        // adding all the single handler to a multi handler
        foreach($curl_handlers as $key => $curl)
        {
            curl_multi_add_handle($multi_curl_handler,$curl);
        }
 
        // executing the multi handler
        do
        {
            $multi_curl = curl_multi_exec($multi_curl_handler, $active);
        }
        while ($multi_curl == CURLM_CALL_MULTI_PERFORM  || $active);
 
        // check if there any error
        $submitted = true;
        foreach($curl_handlers as $key => $curl)
        {
            //you may use curl_multi_getcontent($curl); for getting content
            //and curl_error($curl); for getting errors
            if(curl_errno($curl) != CURLE_OK)
            {
                $submitted = false;
            }
        }
        curl_multi_close($multi_curl_handler);
        */
        $submitted=true;
        return $submitted;
    }
 
    //generates sitemap
    /**
    * Constructing xml from list of urls without attributes
    * @return string                //xml in string
    */    
    public function generate_sitemap(){
        $sitemap = new SimpleXMLElement('<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9/"></urlset>');
        $i=0;
        $limit=min(($this->maps_number*$this->links_per_file)+$this->links_per_file,$this->links_total);
        for ($i=$this->maps_number*$this->links_per_file;$i<$limit;$i++)
        {
            $url=$this->sitemap_urls[$i];
            $url_tag = $sitemap->addChild("url");
            //$url=preg_replace('/'.$this->base.'/si',$this->url_to_sitemap,$url);
            $url_tag->addChild("loc", htmlspecialchars($url));
            //if ($this->sitemap_dates[$i]!=-1) $url_tag->addChild("lastmod",$this->sitemap_dates[$i]);
        }
        $this->links_mapped+=$this->links_per_file;
        return $sitemap->asXML();
    }
    
    //generates sitemap from list of urls with attributes
    /**
    * Constructing xml from list of urls with attributes
    * @return string                //xml in string
    */   
    public function generate_sitemap_manually(){
        
        $sitemap = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        $i=0;
        $limit=min(($this->maps_number*$this->links_per_file)+$this->links_per_file,$this->links_total);
        $lastmod = date('Y-m-d\\TH:i:sP',time() - 20000000);
        for ($i=$this->maps_number*$this->links_per_file;$i<$limit;$i++)
        {
            if(empty($this->sitemap_urls[$i])) {
                die($this->maps_number);
            }
            $url = $this->sitemap_urls[$i];
            $url_tag = $sitemap->addChild("url");
            $url_tag->addChild("loc", htmlspecialchars($url));
            $url_tag->addChild("lastmod",$this->lastmod[$i]);
            $url_tag->addChild("changefreq",$this->changefreq[$i]);
            $url_tag->addChild("priority",$this->priority[$i]);
            if($this->lastmod[$i] > $lastmod) $lastmod = $this->lastmod[$i];
            unset($this->sitemap_urls[$i]);
            unset($this->lastmod[$i]);
            unset($this->changefreq[$i]);
            unset($this->priority[$i]);
        }
        $this->links_mapped+=$limit-$this->maps_number*$this->links_per_file;
        $this->latest_lastmod[] = $lastmod;
        return $sitemap->asXML();
    }
    
    //generates sitemap from list of 404-urls with attributes
    /**
    * Constructing xml from list of urls-404 from webmaster
    * @return string                //xml in string
    */   
    public function generate_sitemap404_manually(){
        
        $sitemap = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        $i=0;
        $limit=min(($this->maps404_number*$this->links_per_file)+$this->links_per_file,$this->links404_total);
        for ($i=$this->maps404_number*$this->links_per_file;$i<$limit;$i++)
        {
            $url=$this->sitemap_urls[$i];
            $url_tag = $sitemap->addChild("url");
            $url_tag->addChild("loc", htmlspecialchars($url));
            $url_tag->addChild("lastmod",$this->lastmod[$i]);
            $url_tag->addChild("changefreq",$this->changefreq[$i]);
            $url_tag->addChild("priority",$this->priority[$i]);
        }
        $this->links404_mapped+=$limit-$this->maps404_number*$this->links_per_file;
        return $sitemap->asXML();
    }
    
    //creates xml for list of urls with attributes
    /**
    * Creating xml file for urls with attributes
    * @return bool
    */   
    public function create_xmls_manually($filename=false, $clean_queue=false){
        global $log, $root;
        if(!empty($filename)) $this->filename = $filename;
        if(!empty($clean_queue)) $this->file_number = 0;
        if ($this->links_total-$this->links_mapped>$this->links_per_file){
            do{
                $this->file_list[] = $file = $this->folder_tmp.$this->filename.(!empty($this->file_number)?'_'.$this->file_number:'').'.xml';
                $map = $this->generate_sitemap_manually();
                //автоматически, после работы функции в начале будет не совсем то, что нужно - не будет строки
                //'encoding="UTF-8"'
                $map=preg_replace('/^<\?xml version="1.0"\?>/si','<?xml version="1.0" encoding="UTF-8"?>',$map);
                file_put_contents($file,$map);
                exec("gzip -rv ".$root.'/'.$file);
                exec("chmod 777 ".$root.'/'.$file.".gz");
                $log[] =  $this->filename.$this->maps_number.".xml<br>";
                $this->file_number++; $this->maps_number++;
            }//проверяем не хватит ли остатков на еще один файл
            while(($this->links_total-$this->maps_number*$this->links_per_file)>$this->links_per_file);
            echo ' links : '.count($this->sitemap_urls)."\n";
        }
        return TRUE;
    }
    
    //creates xml for of 404-urls from webmaster
    /**
    * Creating xml file from list of urls-404 from webmaster (sitemap0*.xml)
    * @return bool
    */   
    public function create_xml_404(){
        global $log, $root;
        if ($this->links404_total-$this->links404_mapped>$this->links_per_file){
            do{
                $file='sitemap0'.$this->maps404_number.'.xml';
                $map=$this->generate_sitemap404_manually(TRUE);
                //автоматически, после работы функции в начале будет не совсем то, что нужно - не будет строки
                //'encoding="UTF-8"'
                $map=preg_replace('/^<\?xml version="1.0"\?>/si','<?xml version="1.0" encoding="UTF-8"?>',$map);
                file_put_contents($file,$map);
                exec("gzip -rv ".$root.'/'.$file);
                exec("chmod 777 ".$root.'/'.$file.".gz");
                $log[] =  "sitemap_0.xml.gz<br>";
                ++$this->maps404_number;
            }//проверяем не хватит ли остатков на еще один файл
            while(($this->links404_total-$this->maps404_number*$this->links_per_file)>$this->links_per_file);
        }
        //если что-то осталось, пишем еще один файл
        if (($this->maps404_number*$this->links_per_file<$this->links_total)||($this->maps404_number==0)){
            $file='sitemap0'.$this->maps404_number.'.xml';
            $map=$this->generate_sitemap404_manually();
            $map=preg_replace('/^<\?xml version="1.0"\?>/si','<?xml version="1.0" encoding="UTF-8"?>',$map);
            file_put_contents($file,$map);
            exec("gzip -rv ".$root.'/'.$file);
            exec("chmod 777 ".$root.'/'.$file.".gz");
            $log[] = $file;
        }
        $this->sitemap404=TRUE;
        return TRUE;
    }
    
    //составляем sitemap-index
    /**
    * Creating index xml file (sitemap.xml)
    * @return bool
    */   
    public function generate_sitemap_index(){
        global $log;
        $file=fopen('sitemap.xml','w');
        fwrite($file,'<?xml version="1.0" encoding="UTF-8"?>');
        fwrite($file,'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        $i=0;
        while($i<count($this->file_list)){
            if(!empty($this->file_list[$i])){
                fwrite($file,'<sitemap>');
                fwrite($file,'<loc>https://www.bsn.ru/'.str_replace('tmp/','',$this->file_list[$i]).'.gz</loc>');
                fwrite($file,'<lastmod>'.$this->latest_lastmod[$i].'</lastmod>');
                fwrite($file,'</sitemap>');
            }
            ++$i;
        }
        $i=0;
        fwrite($file,'</sitemapindex>');
        fclose($file);
        $log[] = "sitemap_index.xml<br>";
        return TRUE;
    }
    
    //дозаписываем остатки, которые не вошли в предыдущие файлы для создания sitemap из базы
    /**
    * Creating ending xml file from remains of list of urls with attributes
    * @return bool
    */   
    public function finish_maps_manually($unzip = true, $clear = false){
        global $log, $root;
        if(!empty($this->sitemap_urls)){
            $this->file_list[] = $file = $this->folder_tmp . $this->filename.(!empty($this->file_number)?'_'.$this->file_number:'').'.xml';
            $map=$this->generate_sitemap_manually();
            //автоматически, после работы функции в начале будет не совсем то, что нужно - не будет строки
            //'encoding="UTF-8"'
            if( file_exists( $file ) && !empty( $clear ) ) unlink( $file );
            $map=preg_replace('/^<\?xml version="1.0"\?>/si','<?xml version="1.0" encoding="UTF-8"?>',$map);
            file_put_contents( $file, $map );
            exec( "chmod 777 " . $root . '/' . $file );
            if( !empty( $unzip ) ) {
                exec("gzip -rv ".$root.'/'.$file);
                exec("chmod 777 ".$root.'/'.$file.".gz");
            }
            $log[] = $file;
        }
        $this->links_total = $this->maps_number = $this->links_mapped = 0;
        $this->reset_sitemap_urls();
        return TRUE;
    }
    
    //дозаписываем остатки, которые не вошли в предыдущие файлы
    /**
    * Creating ending xml file from remains of list of urls without attributes
    * @return bool
    */   
    public function finish_maps(){
         global $log, $root;
         if (($this->maps_number*$this->links_per_file<count($this->sitemap_urls))||($this->maps_number==0)){
            $file=$this->filename.$this->maps_number.'.xml';
            $map=$this->generate_sitemap();
            $map=preg_replace('/^<\?xml version="1.0"\?>/si','<?xml version="1.0" encoding="UTF-8"?>',$map);
            file_put_contents($file,$map);
            exec("gzip -rv ".$root.'/'.$file);
            exec("chmod 777 ".$root.'/'.$file.".gz");
            $log[] = $file;
        }
        return TRUE;
    }
    
    
    //---далее для xml-схемы
    //call to start building schema
    /**
    * Start creating ierarchic xml-schema
    * @return pagetree      //root of url tree
    */   
    public function get_schema($domain){
        //getting base of domain url address
        $this->base = str_replace("https://", "", $domain);
        $this->base = str_replace("https://", "", $this->base);
        
        $root= new pagetree($this->base,0);
        //getting proper domain name and protocol
        $this->domain = trim($domain);
        if(strpos($this->domain, "https") !== 0)
        {
            $this->protocol = "https://";
            $this->domain = $this->protocol.$this->domain;
        }
        else
        {
            $protocol = explode("//", $domain);
            $this->protocol = $protocol[0]."//";
        }
 
        if(!in_array($this->domain, $this->sitemap_urls))
        {
            $this->sitemap_urls[] = $this->domain;
        }
        //requesting link content using curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->domain);
        if (isset($this->proxy) && !$this->proxy == '')
        {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $page = curl_exec($curl);
        curl_close($curl);
        $this->parse_node($page,$root);
        $xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $schema=$this->generate_schema_simple($root,$xml);
        $xml.='</urlset>';
        file_put_contents('test_schema.xml',$xml);
        return $root;
    }
    
    //парсим url узла, получаем ссылки, парсим дальше
    /**
    * Parsing node of ierarchic xml-schema
    * @return bool
    */   
    private function parse_node($page,pagetree $node=null){
        //getting all links from href attributes
        preg_match_all("/<a[^>]*href\s*=\s*'([^']*)'|".
                    '<a[^>]*href\s*=\s*"([^"]*)"'."/is", $page, $match);
        //storing new links
        $new_links = [];
        for($i = 1; $i < sizeof($match); $i++)
        {
            //walking through links
            foreach($match[$i] as $url)
            {
                //if doesn't start with http and is not empty
                if(strpos($url, "https") === false  && trim($url) !== "")
                {
                    //checking if absolute path
                    if($url[0] == "/") $url = substr($url, 1);
                    //checking if relative path
                    else if($url[0] == ".")
                    {
                        while($url[0] != "/")
                        {
                            $url = substr($url, 1);
                        }
                        $url = substr($url, 1);
                    }
                    //transforming to absolute url
                    $url = $this->protocol.$this->base."/".$url;
                }
                //if new and not empty
                if(!in_array($url, $this->sitemap_urls) && trim($url) !== "")
                {
                    //if valid url
                    if($this->validate($url))
                    {
                        //checking if it is url from our domain
                        if(strpos($url, "http://".$this->base) === 0 || strpos($url, "https://".$this->base) === 0)
                        {
                            //adding url to sitemap array
                            $this->sitemap_urls[] = $url;
                            
                            //adding url to new link array
                            $new_links[] = $url;
                            //добавляем страницу в дочерние элементы переданного узла
                            //$node->children[] = new pagetree($url,($node->level+1));
                        }
                    }
                }
            }
        }
        
        // делаем запросы к полученным url если найдены новые
        //if (!empty($node->children))    $this->schema_multi_curl($new_links,$node->children);
        if (!empty($new_links))    $this->multi_curl($new_links);
        return true;
    }
    
    //curl для набора url, заполняем массив потомков узла
    /**
    * Recursive curling list of urls 
    * @return bool
    */
    private function schema_multi_curl($urls,$nodes=null){
        // for curl handlers
        $curl_handlers = [];
        //setting curl handlers
        foreach ($urls as $url)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);//указываем url
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//указываем, что строка результата не должна быть выдана в браузер
            curl_setopt($curl,CURLOPT_FILETIME,true);//указываем, что нужно время изменения файла
            if (isset($this->proxy) && !$this->proxy == '')
            {
                curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
            }
            $curl_handlers[] = $curl;
        }
        //initiating multi handler
        $multi_curl_handler = curl_multi_init();
 
        // adding all the single handler to a multi handler
        foreach($curl_handlers as $key => $curl)
        {
            curl_multi_add_handle($multi_curl_handler,$curl);
        }
 
        // executing the multi handler
        do
        {
            $multi_curl = curl_multi_exec($multi_curl_handler, $active);
        }
        while ($multi_curl == CURLM_CALL_MULTI_PERFORM  || $active);
 
        $i=0;
        foreach($curl_handlers as $curl)
        {
            //checking for errors
            if(curl_errno($curl) == CURLE_OK)
            {
                //if no error then getting content
                $content = curl_multi_getcontent($curl);
                $this->sitemap_dates[]=curl_getinfo($curl,CURLINFO_FILETIME);
                //parsing content
                //$this->parse_node($content,$nodes[$i]);
                $this->parse_node($content);
            }
            $i++;
        }
        curl_multi_close($multi_curl_handler);
        return true;
    }
    
    //не работает
    public function generate_schema(pagetree $node, SimpleXMLElement $map = null){
        if (empty($map)){
            $sitemap = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        } 
            
        $i=0;
        $value=$node->node;
        if(empty($map)){
            $url_tag = $sitemap->addChild(stripslashes($value));
        }else{
            $url_tag = $map->addChild(stripslashes($value));
        }
        if ($node->level<2)
        {
            foreach($node->children as $childnode)
            {
                if(empty($map)){
                    $url_tag->addChild($this->generate_schema($childnode,$sitemap));
                }else{
                    $url_tag->addChild($this->generate_schema($childnode,$map));
                }
            }
        }
        if (empty($map)){
            return $sitemap->asXML();
        }
        else{
            return $map->asXML();
        }
    }
    
    //генерация xml схемы вручную
    /**
    * Constructing(recursive) ierarhic xml of childs for a pagetree node
    * @return pagetree      //root of url tree
    */
    public function generate_schema_simple(pagetree $node,&$xml){
        $xml.='<url_'.$node->level.'><node>"'.htmlspecialchars($node->node).'"</node>';
        $new_xml="";
        foreach($node->children as $childnode)
        {
            $xml.=$this->generate_schema_simple($childnode,$new_xml);
            $new_xml="";
        }
        $xml.='</url_'.$node->level.'>';
        return $xml;
    }
}
?>