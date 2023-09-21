<?php
require_once('includes/class.robot.php');
/**
* класс для внутренних методов при парсинге
*/
class ParsingFunctions {
    //при чтении прикрываемся роботом Yahoo (сейчас выбирается всегда первый)
    private static $user_agents = array("Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)",
                                        "Mozilla/5.0 (compatible; Yahoo! DE Slurp; http://help.yahoo.com/help/us/ysearch/slurp)",
                                        "Mozilla/5.0 (compatible; Yahoo! Slurp China; http://misc.yahoo.com.cn/help.html)",
                                        "Mozilla/5.0 (compatible; Yahoo! Slurp/3.0; http://help.yahoo.com/help/us/ysearch/slurp)");
    /**
    * читаем из базы схему разбора страницы данного типа
    * 
    */
    protected function getMapping($source_id){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $result = $db->fetch("SELECT * FROM ".$sys_tables['parsing_schemas']." WHERE id_source = ?",$source_id);
        if(empty($result) || empty($result['id'])) throw new Exception("Не найдена схема обработки для парсинга #".$source_id,2);
        
        unset($result['id']);
        unset($result['id_source']);
        return $result;
    }
    
    /**
    * разбираем строку с описанием элемента
    * например строки:
    *  "@h1.offer-title>@small"
    *  "@div.options.m-top>@li:first>@p#notags"
    * @param mixed $command
    */
    private function parseCommand($command){
        $result = [];
        $command_elements = explode('>',$command);
        
        foreach($command_elements as $key => $element){
            
            //тип элемента
            if(strstr($element,"@") !== false){
                if(strstr($element,'[')) preg_match("/(?<=\@)\w+(\[[^\[\]]*\])/si",$element,$element_type);
                else preg_match("/(?<=\@)\w+/si",$element,$element_type);
                $result[$key]['element_type'] = (!empty($element_type) ? $element_type[0] : "");
                $element = str_replace("@".$result[$key]['element_type'],'',$element);
            }
            //класс
            if(strstr($element,".") !== false){
                preg_match("/(?<=\.)[A-z\:\-\.]+/si",$element,$element_class);
                $result[$key]['element_class'] = (!empty($element_class) ? $element_class[0] : "");
                $element = str_replace(".".$element_class[0],'',$element);
            }
            //модификатор :first, :last, :[0-9]
            if(strstr($element,":") !== false){
                preg_match("/(?<=\:)[A-z0-9]+/si",$element,$element_order);
                if(!empty($element_order)){
                    $element_order = array_pop($element_order);
                    switch(true){
                        case $element_order == "first": 
                            $result[$key]['element_order'] = 0;
                            break;
                        case Validate::isDigit($element_order): 
                            $result[$key]['element_order'] = $element_order;
                            break;
                    }
                    $element = str_replace(":".$element_order,'',$element);
                }
            }
            
            //специальная команда
            if(strstr($element,'#') !== false){
                preg_match_all("/(?<=\#)[A-z0-9\:\-\=]+/si",$element,$element_options);
                $element_options = array_pop($element_options);
                if(!is_array($element_options)) $element_options = array($element_options);
                foreach($element_options as $element_option){
                    switch(true){
                        case preg_match("/attribute\=[A-z\-\:\_]+/si",$element_option):
                            $result[$key]['element_action'][] = $element_option;
                            break;
                        case $element_option == "notags":
                        case $element_option == "getall":
                        case $element_option == "getimagelinks":
                        case $element_option == "getname":
                        case $element_option == "removetags":
                            $result[$key]['element_action'][] = $element_option;
                            break;
                    }
                }
            }
            
            //извлекаемый тип
            if(strstr($element,'%') !== false){
                preg_match("/(?<=\%)[A-z0-9\:\-\=]+/si",$element,$element_options);
                $result[$key]['target_type'] = array_pop($element_options);
            }
        }
        return $result;
    }
    
    /**
    * по команде строим селектор
    * 
    * @param mixed $parsed_command
    */
    private function buildCommand($parsed_command){
        $result = array("selector" => [],"order" => [],"actions" => []);
        foreach($parsed_command as $key=>$selector){
            $result['selector'][$key] .= (!empty($selector['element_type']) ? $selector['element_type'] : "");
            $result['selector'][$key] .= (!empty($selector['element_class']) ? ".".$selector['element_class']."" : "");
            $result['selector'][$key] .= (isset($selector['element_order']) ? ":eq(".$selector['element_order'].")" : "");
            if(!empty($selector['element_action'])) $result['action'] = $selector['element_action'];
            if(!empty($selector['target_type'])) $result['target_type'] = $selector['target_type'];
        }
        $result['selector'] = implode(' ',$result['selector']);
        return $result;
    }
    
    /**
    * по команде-селектору получаем информацию со страницы
    * 
    * @param mixed $document
    * @param mixed $command
    */
    protected function getDataByCommand($document,$command){
        $selector = $this->parseCommand($command);
        $selector = $this->buildCommand($selector);
        
        if(empty($selector)) throw new Exception("Не удалось создать команду: ".$command,4);
        
        $current_node = $document;
        $current_node = $current_node->find($selector['selector']);
        
        $result = [];
        
        foreach($current_node as $element){
            if(!empty($selector['action'])){
                $value = "";
                foreach($selector['action'] as $action_to_do){
                    switch(true){
                        case strstr($action_to_do,"attribute") !== false:
                            $attribute_name = explode('=',$action_to_do);
                            $attribute_name = array_pop($attribute_name);
                            $value = pq($element)->attr($attribute_name);
                            break;
                        case strstr($action_to_do,"notags") !== false:
                            $value = strip_tags( (!empty($value) ? $value : pq($element)->html()) );
                            $value = trim( preg_replace( '/[^А-я0-9\-\.\,]/sui',' ',$value));
                            break;
                        case strstr($action_to_do,"removetags") !== false:
                            $html = pq($element)->html();
                            while(preg_match('/\<[^\>\<]+\>[^\<\>]*\<\/[^\>\<]+\>/sui',$html)){
                                $html = preg_replace('/\<[^\>\<]+\>[^\<\>]*\<\/[^\>\<]+\>/sui','',$html);
                            }
                            $value = trim(strip_tags($html));
                            $value = trim(preg_replace('/адрес\:?/sui','',$value));
                            break;
                        case strstr($action_to_do,"getimagelinks") !== false:
                            $value = pq($element)->html();
                            preg_match_all("/(http\:\/\/|\/)[A-z0-9\/\-\_\.]+(\.jpg|\.jpeg|\.png|\.gif)/",$value,$photo_links);
                            if(!empty($photo_links)) $value = implode('#',$photo_links[0]);
                            else $value = "";
                            break;
                    }
                    
                }
                $result[] = $value;
            }
            else{
                $result[] = pq($element)->html();
            }
        }
        if(is_array($result)) $result = array_unique($result);
        if(!empty($selector['action']) && in_array("getname",$selector['action']) && !empty($result)) {
            $result = array_pop($result);
            $result = preg_replace("/(показать)|(телефон)/sui",'',$result);
            preg_match("/[А-я]+(\s[А-я]+)?/sui",trim($result),$res);
            $result = $res;
        }
        elseif(!empty($selector['action']) && in_array("getall",$selector['action']) && !empty($result)) $result = array(0 => implode('#',$result));
        
        return $result;
    }
    
    /**
    * читаем из базы информацию по данному сайту, используется в конструкторе
    * 
    * @param mixed $site_url
    */
    protected static function getSourceInfo($site_url){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $siteInfo = $db->fetch("SELECT * FROM ".$sys_tables['parsing_sources']." WHERE main_url = ?",$site_url);
        return (!empty($siteInfo) && !empty($siteInfo['id']) ? $siteInfo : false);
    }
    
    /**
    * делаем неравные паузы перед запросом
    * 
    */
    private static function CurlDelay(){
        $delay = rand(4,7);
        sleep($delay);
        return true;
    }
    
    /**
    * читаем контент страницы с помощью cURL
    * 
    * @param mixed $url
    */
    protected static function getPageContent($url){
        if(empty($url)) return false;
        
        self::CurlDelay();
        
        $curl = curl_init();
        
        $user_agent = self::$user_agents[0];

        $options = array(

            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
            CURLOPT_POST           => false,       //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIESESSION  => true,
            CURLOPT_COOKIEJAR      => dirname(__FILE__)."\\cookiejar.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_URL => $url,
            CURLOPT_REFERER => (empty($referer) ? "http://spb.snyat-kvartiru-bez-posrednikov.ru" : $referer)
        );
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        return $result;
    }
}

/**
* набор методов для распознавания текстовых данных из спарсенной заготовки
* поле -> метод
*/
class ParsingDataFromStub{
    
    //схемы распознавания для источников
    /*
    Комнаты      1-комнатная#
    Площадь      39 м#
    Планировка      балкон#
    Этаж      7 из 14#
    Адрес        Санкт-Петербург, пр-кт Славы, 34#
    Лифт      пассажирский, грузовой#
    Состояние      кухонный гарнитур, меблирована#
    Бытовая техника      холодильник, стиральная м
    */
    
    private static $mapping_unstructured = array("/холодильник/sui" => array("refrigerator" => 1),
                                                 "/интернет\s+(есть|да)/sui" => [],
                                                 "/мебель/" => array("furniture" => 1),
                                                 "/стиральная\s+машина/" => array("wash_mash" => 1),
                                                 "/телевизор/" => [],
                                                );
    
    private static $mapping = array(1 => array('rooms_total' => "комнаты",
                                               'square_full' => "площадь",
                                               'square_rooms' => "планировка",
                                               'level' => "этаж",
                                               'address' => "адрес",
                                               'elevator' => "лифт",
                                               'furniture' => "состояние",
                                               'mechanisms' => "бытовая техника",
                                               'address' => "адрес",
                                               'subway' => "метро"
                                               )
    );
    
    private static function getInfoFromTable($table,$values,$fields = false,$complex = false,$field_to_return = false,$unstrict = false){
       global $db;
       if(!empty($unstrict) && is_string($values)) $values = (empty($complex) ? "%".$values."%" : ".*".$values.".*");
       if(empty($fields)) $fields = $this->file_format."_value";
       if(empty($complex)) $res = $db->fetch("SELECT * FROM ".$table." WHERE ".$fields." ".(!empty($unstrict) ? "LIKE" : "=")." ?",$values);
       else $res = $db->fetch("SELECT * FROM ".$table." WHERE ".$fields." REGEXP '^.*#?(".$values.")(#.*)*'");
       
       if(!empty($field_to_return)) $res = (empty($res[$field_to_return]) ? false : $res[$field_to_return]);
       return $res;
    }
    
    //методы для получения значений из обычных полей базы
    public static function external_id_parse($field_value){
        return (int)$field_value;
    }
    
    private static function type_notes_parse($already_parsed, $field_value){
        $result = $already_parsed;
        if(empty($field_value)) return $result;
        
        if(empty($result['rent'])){
            switch(true){
                case preg_match("/(аренда|сдам)/sui",$field_value):
                    $result['rent'] = 1;
                    break;
                case preg_match("/(продажа|продам)/sui",$field_value):
                    $result['rent'] = 2;
                    break;
            }
        }
        
        if(empty($result['id_type_object'])){
            //тип объекта
            switch(true){
                case preg_match("/[^А-я](квартир|студи)(а?|ы?|у?|ю?[^А-я])/sui",$field_value):
                    $type = "квартира";
                    break;
                case preg_match("/[^А-я]комнат(а?|ы?|[^А-я])/sui",$field_value):
                    $type = "комната";
                    break;
            }
            $result['id_type_object'] = self::getInfoFromTable($sys_tables['type_objects_live'],$type,"title",false,'id');
        }
        
        if($result['id_type_object'] == 2 && empty($result['rooms_total'])){
            
        }
    }
    
    public static function type_parse($field_value,$notes_value = false){
        $result = [];
        if(empty($field_value)) return $result;
        
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        //тип сделки
        switch(true){
            case preg_match("/(аренда|сдам)/sui",$field_value,$matching):
                $result['rent'] = 1;
                $field_value = str_replace($matching[0],"",$field_value);
                break;
            case preg_match("/(продажа|продам)/sui",$field_value,$matching):
                $result['rent'] = 2;
                $field_value = str_replace($matching[0],"",$field_value);
                break;
        }
        
        //тип объекта
        switch(true){
            case preg_match("/[^А-я](квартир|студи)(а?|ы?|у?|ю?[^А-я])/sui",$field_value,$matching):
                $type = "квартира";
                $field_value = str_replace($matching[0],"",$field_value);
                break;
            case preg_match("/[^А-я]комнат(а?|ы?|[^А-я])/sui",$field_value,$matching):
                $field_value = str_replace($matching[0],"",$field_value);
                $type = "комната";
                break;
        }
        $result['id_type_object'] = self::getInfoFromTable($sys_tables['type_objects_live'],$type,"title",false,'id');
        
        //комнатность
        //preg_match("/(одно|двух|трех|четырех|много|[0-9][^А-я]?)к(омнатной)?/sui",$field_value,$rooms)
        preg_match("/((одно|двух|трех|четырех|много|[0-9][^А-я]?)к(омнат[А-я]+)?(?=[А-Я]|(студи)|[^А-я]))/sui",$field_value,$rooms);
        if(!empty($rooms)){
            $rooms = $rooms[0];
            switch(true){
                case preg_match("/^\s*[0-9]+\s?[А-я]?/sui",$rooms):
                    $result['rooms_total'] = (int)$rooms;
                    break;
                case preg_match("/(студи|1)/sui",$rooms):
                    $result['rooms_total'] = 0;
                    break;
                case preg_match("/(одн|1)/sui",$rooms):
                    $result['rooms_total'] = 1;
                    break;
                case preg_match("/(дву(х|ш)|2)/sui",$rooms):
                    $result['rooms_total'] = 2;
                    break;
                case preg_match("/(тре(х|ш)|3)/sui",$rooms):
                    $result['rooms_total'] = 3;
                    break;
                case preg_match("/(чет|4)/sui",$rooms):
                    $result['rooms_total'] = 4;
                    break;
            }
            
            if($type == "комната"){
                $result['rooms_sale'] = 1;
            }else $result['rooms_sale'] = $result['rooms_total'];
        }elseif($type == "комната") $result['rooms_sale'] = 1;
        
        return $result;
    }
    
    public static function seller_name_parse($field_value){
        return $field_value;
    }
    
    public static function cost_parse($field_value){
        $result = Convert::ToInt(preg_replace('/[^0-9]/si','',$field_value));
        return array('cost' => $result);
    }
    
    public static function address_parse($field_value){
        $robot = new Robot(54667);
        $result = $robot->getTxtGeodata($field_value);
        $result['id_region'] = $robot->fields['id_region'];
        $result['id_area'] = $robot->fields['id_area'];
        $result['id_city'] = $robot->fields['id_city'];
        $result['id_place'] = $robot->fields['id_place'];
        $result['id_street'] = $robot->fields['id_street'];
        $result['id_district'] = $robot->fields['id_district'];
        $result['house'] = $robot->fields['house'];
        $result['corp'] = $robot->fields['corp'];
        $result['txt_addr'] = $field_value;
        return $result;
    }
    
    public static function notes_parse($field_value){
        $result = Validate::StripPhone(Validate::StripEmail(Convert::StripText($field_value)));
        return array('notes' => $result);
    }
    
    public static function photos_parse($field_value,$source_id){
        if(empty($field_value)) return [];
        $photo_urls = explode('#',$field_value);
        //дополнительная обработка чтобы получить самые большие
        switch($source_id){
            case 1:
                $photo_urls = array_filter(array_map(function($e){
                                                        return (strstr($e,'320x240') !== false ? str_replace('320x240','1024x768',$e) : false);
                                                        },$photo_urls));
                break;
        }
        return $photo_urls;
    }
    
    private static function details_parse_unstructured($field_value){
        $value = "состояние: отличное, холодильник, интернет: есть, мебель, стиральная машинка, телевизор ";
    }
    
    //читаем значение комплексного поля `details`. На входе строка вида:
    //"Комнаты      1-комнатная#Площадь      39 м#Этаж      7 из 14#"
    public static function details_parse($field_value,$source_id){
        if(empty($source_id) || empty(self::$mapping[1]) || empty($field_value)) return false;
        $result = [];
        
        //накапливаем пары "название поля => распознанное значение"
        //из строк вида "Этаж      7 из 14"
        $values = explode('#',$field_value);
        
        if(count($values) == 1){
            $values = array_pop($values);
            foreach(self::$mapping_unstructured as $key=>$item){
                if(preg_match($key,$values)) $result += $item;
            }
        }else{
            foreach($values as $value){
                //выделяем заголовок-название(в примере - "Этаж") что у нас тут
                preg_match("/[^\s]+(?=\s\s)/sui",$value,$value_title);
                if(empty($value_title)) continue;
                else $value_title = array_pop($value_title);
                $value_title = trim($value_title);
                $value_type = array_keys(array_filter(self::$mapping[1],function($v) use ($value_title){
                    return preg_match("/".$value_title."/sui",$v);
                }));
                if(empty($value_type)) continue;
                else $value_type = array_pop($value_type);
                
                //выделяем остаток(в примере - "7 из 14") - оттуда будем парсить значение
                $value_data = trim(str_replace($value_title,'',$value));
                
                $parsing_method = $value_type."_parse";
                if(method_exists(__CLASS__,$parsing_method)){
                    $value_data = self::$parsing_method($value_data);
                    if(is_array($value_data)) $result = array_merge($result,$value_data);
                    else $result[$value_type] = $value_data;
                }
            }
        }
        
        
        
        return $result;
    }
    
    //методы для получения значений из строк комплексного поля
    private static function square_full_parse($txt_value){
        preg_match("/[^0-9]*[0-9]{2,}(?=[^0-9])/sui",$txt_value,$result);
        return (empty($result) ? false : $result[0]);
    }
    
    private static function level_parse($txt_value){
        preg_match("/(?<=[0-9])[^0-9]+(?=[0-9])/sui",$txt_value,$divider);
        if(!empty($divider)) $result = explode($divider[0],$txt_value);
        else preg_match('/[0-9](?=[^0-9])/sui',$txt_value,$result);
        
        return array("level" => $result[0], "level_total" => (!empty($result[1]) ? $result[1] : false));
    }
    
    private static function mechanisms_parse($txt_value){
        return "";
    }
}

/**
* класс для собственно парсинга
*/
class Parsing extends ParsingFunctions{
    
    private $mapping = [];
    private $id = "";               //id источника
    private $id_user = 54667;       //id пользоавтеля которому грузим объекты.
    private $line_id = "";          //id строки процесса в БД 
    private $id_selector = "";      //селектор значений ID со страницы выдачи
    private $main_page = "";        //главная страница источника
    private $estate_page = "";      //страница выдачи
    private $estateitem_page = "";  //страница карточки
    private $phone_page = "";       //(если есть) - отдельный адрес для телефона
    private $photo_page = "";       //то же самое для картинок
    private $loading_log = array('phone_errors' => [],
                                 'images_errors' => [], 
                                 'address_errors' => [], 
                                 'loaded' => 0, 
                                 'published' => 0);
    private $lines_parsed = 0;
    private $lines_added = 0;
    private $from_page = 0;
    private $to_page = 0;
    
    public function __construct($url){
        $source_info = self::getSourceInfo($url);
        if(empty($source_info)) throw new Exception("Нет записей для такого адреса: ".$url,1);
        
        $this->id = $source_info['id'];
        $this->main_page = $source_info['main_url'];
        $this->id_selector = $source_info['id_selector'];
        $this->estate_page = $source_info['estate_page'];
        $this->estateitem_page = $source_info['estateitem_page'];
        $this->phone_page = $source_info['phone_page'];
        $this->phone_page_method = $source_info['phone_page_method'];
        $this->photo_page = $source_info['photo_page'];
        
        $this->mapping = self::getMapping($this->id);
    }
    
    /**
    * по маппингу читаем данные с карточки
    * 
    * @param mixed $source
    * @param mixed $data
    */
    private function mapDataFromPage($document,$item_id){
        $result = [];
        foreach($this->mapping as $data_key=>$command){
            $command = str_replace('#ID',$item_id,$command);
            if(empty($command)) continue;
            $result[$data_key] = $this->getDataByCommand($document,$command);
            $result[$data_key] = array_pop($result[$data_key]);
        }
        return $result;
    }
    
    /**
    * сохраняем заготовку в базу
    * 
    * @param mixed $data
    */
    private function saveStubToBase($data){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $data['id_source'] = $this->id;
        return $db->insertFromArray($sys_tables['parsed_stubs'],$data);
    }
    
    /**
    * преобразуем заготовку в готовый к записи в нашу базу вариант
    * 
    * @param mixed $stub_data
    */
    private function parseDataFromStub($stub_data){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $result = [];
        //парсим информацию из строчки
        foreach($stub_data as $field_name => $field_value){
            $action = $field_name."_parse";
            //проверяем наличие нужного метода обработки
            if(is_callable(array("ParsingDataFromStub", $action))){
                //проверяем количество аргументов, вызываем
                $method = new ReflectionMethod('ParsingDataFromStub',$action);
                $args_count = count($method->getParameters());
                //if($field_name == 'type') $value = ParsingDataFromStub::$action($field_value,$stub_data['notes']);
                if($args_count == 2) $value = ParsingDataFromStub::$action($field_value,$this->id);
                else $value = ParsingDataFromStub::$action($field_value,$this->id);
                
                //добавляем к результату
                if($field_name == "photos") $result[$field_name] = $value;
                elseif(is_array($value)) $result = array_merge($result,$value);
                else $result[$field_name] = $value;
            }
        }
        
        return $result;
    }
    
    /**
    * парсим дополнительные данные(площади и этаж) из текстовых полей заготовки
    * 
    * @param mixed $stub_data
    */
    private function parseDetailsFromStub($stub_data){
        if(empty($stub_data) || !is_array($stub_data)) return false;
        
        $result = [];
        
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $searches = array('details','notes','type');
        foreach($searches as $key=>$search){
            $data = preg_replace("/[^А-я0-9²]/sui",' ',$stub_data[$search]);
            if(empty($result['square_kitchen']) && preg_match("/(?<=[^А-я])кухн(я|и)?\s+[0-9]+\s?м?/sui",$data,$square_kitchen)){
                $result['square_kitchen'] = preg_replace("/[^0-9]/sui",'',array_shift($square_kitchen));
            }
            if(empty($result['square_live']) && preg_match("/(?<=[^А-я])жил(ая|ой)?\s+[0-9]+\s?м?/sui",$data,$square_live)){
                $result['square_live'] = preg_replace("/[^0-9]/sui",'',array_shift($square_live));
            }
            if(empty($result['level']) && preg_match("/(?<=[^А-я])этаж\s+[0-9]+\s+?(из|\/)?\s+?[0-9]+/sui",$data,$level)){
                preg_match_all("/[0-9]+/sui",$level[0],$level_values);
                if(!empty($level_values)){
                    $level_values = array_pop($level_values);
                    $result['level'] = (!empty($level_values[0]) ? $level_values[0] : 0);
                    $result['level_total'] = (!empty($level_values[1]) ? $level_values[1] : 1);
                }
                
            }
            if(!empty($result['square_kitchen']) && !empty($result['square_live']) && !empty($result['level'])) return $result;
        }
        
        return false;
    }
    
    /**
    * парсим район из текстовых полей заготовки
    * 
    * @param mixed $stub_data
    * @return mixed
    */
    private function parseDistrictFromStub($stub_data){
        
        if(empty($stub_data) || !is_array($stub_data)) return false;
        
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $districts_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['districts'],'id');
        
        foreach($districts_list as $key=>$value){
            if(strstr($stub_data['details'],$value['title'])) return $key;
            if(strstr($stub_data['notes'],$value['title'])) return $key;
            if(strstr($stub_data['type'],$value['title'])) return $key;
        }
        
        return 0;
    }
    
    /**
    * парсим метро из текстовых полей заготовки
    * 
    * @param mixed $stub_data
    * @return mixed
    */
    private function parseSubwayFromStub($stub_data){
        
        if(empty($stub_data) || !is_array($stub_data)) return false;
        
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $subways_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['subways'],'id');
        
        foreach($subways_list as $key=>$value){
            if(strstr($stub_data['details'],$value['title'])) return $key;
            if(strstr($stub_data['notes'],$value['title'])) return $key;
            if(strstr($stub_data['type'],$value['title'])) return $key;
        }
        
        return 0;
    }
    
    /**
    * метод для получения скрытого телефона
    * 
    * @param mixed $phone_data
    * @param mixed $item_id
    */
    public function getPhone($phone_data,$item_id,$page_url = false){
        $result = false;
        switch($this->id){
            case 1:
                //получаем ключ
                preg_match("/(?<=key\=(\"|\'))[A-z0-9]+(?=(\"|\'))/",$phone_data,$key);
                if(empty($key)) return false;
                else $key = $key[0];
                $phone_url = str_replace('#PHONEKEY',$key,$this->phone_page);
                $phone_url = str_replace('#ID',$item_id,$phone_url);
                
                if(get_http_response_code($phone_url) !== "200") return false;
                
                $item_phone = file_get_contents($phone_url);
                if(!empty($item_phone)){
                    if($this->phone_page_method == 2){
                        $item_phone = json_decode($item_phone);
                        $item_phone = $item_phone->phone;
                    }
                    $result = preg_replace('/[^0-9]/sui','',$item_phone);
                    return $result;
                }
                break;
            case 2:
                //тут просто делаем запрос и получаем телефон
                $phone_url = str_replace('#ID',$item_id,$this->phone_page);
                $result = $this->getPageContent($phone_url);
                return $result;
                break;
            case 3:
                //
                break;
            //не работает! там Yii, пока не справиться с формой отправки
            case 4:
                //
                $url = $this->phone_page;
                $fields = array("openTelephone" => "1",
                                "code" => "free",
                                "yt0" => ""
                               );
                //получаем ключ
                preg_match("/(?<=value\=(\"|\'))[A-z0-9]+(?=(\"|\'))/",$phone_data,$fields['YII_CSRF_TOKEN']);
                $fields['YII_CSRF_TOKEN'] = $fields['YII_CSRF_TOKEN'][0];
                
                $curl = curl_init();
        
                $user_agent = "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.132 Safari/537.36";

                $saved_cookies = file_get_contents(dirname(__FILE__)."\\cookiejar.txt");
                $saved_cookies = explode("\r\n",$saved_cookies);
                $cookies_for_curl = "";
                $saved_cookies = array_filter(array_map(function($v){ $v = explode("\t",$v); return (!empty($v[5]) && !empty($v[6]) ? $v[5]."=".$v[6].";" : false);},$saved_cookies));
                //$saved_cookies = array_shift($saved_cookies);
                $cookies_for_curl = $saved_cookies[5]." _ym_uid=1486454004376868996; _ym_isad=2; ".$saved_cookies[4]." _ga=GA1.2.47220965.1486454005; _ym_visorc_22889371=w;";
                
                //в запросе используем cookie со страницы карточки которую прочитали + добавляем:
                //_ym_uid=1486454004376868996;_ym_isad=2;_gat=1;_ga=GA1.2.47220965.1486454005;_ym_visorc_22889371=w;
                
                //составляем заголовок
                $header = [];
                $cookies_for_curl = "YII_CSRF_TOKEN=963618c2d42c61c5577a61e2dff4eab12e3871c4s%3A40%3A%22b565ed528ef14dcd2f17c4d1d55863c6a65a4a5b%22%3B; _ym_uid=1486454004376868996; _ym_isad=2; PHPSESSID=191a2bb4f37c77c51b550d79b172eba6; _ga=GA1.2.47220965.1486454005; _gat=1; _ym_visorc_22889371=w";
                /**
                * 
                *
                Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*\/*;q=0.8
                Accept-Encoding:gzip, deflate
                Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4
                Cache-Control:max-age=0
                Connection:keep-alive
                Content-Length:86
                Content-Type:application/x-www-form-urlencoded
                Cookie:YII_CSRF_TOKEN=963618c2d42c61c5577a61e2dff4eab12e3871c4s%3A40%3A%22b565ed528ef14dcd2f17c4d1d55863c6a65a4a5b%22%3B; _ym_uid=1486454004376868996; _ym_isad=2; PHPSESSID=063ffde5266c5427e6f339cf3c713506; _ga=GA1.2.47220965.1486454005; _gat=1; _ym_visorc_22889371=w
                Host:spb.snyat-kvartiru-bez-posrednikov.ru
                Origin:http://spb.snyat-kvartiru-bez-posrednikov.ru
                Referer:http://spb.snyat-kvartiru-bez-posrednikov.ru/snyat-komnatu-bez-posrednikov-v-sankt-peterburge/2303630-metro-ulica-dybenko-podvoyskogo-29-okkervily
                Upgrade-Insecure-Requests:1
                User-Agent:Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36
                */
                $options = array(
                    CURLOPT_HEADER         => 1,
                    CURLOPT_HTTPHEADER     => array("Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                                                    "Accept-Encoding:gzip, deflate",
                                                    "Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
                                                    "Cache-Control:max-age=0",
                                                    "Connection:keep-alive",
                                                    "Content-Length:86",   //там всегда 86
                                                    "Content-Type:application/x-www-form-urlencoded",
                                                    "Cookie:".$cookies_for_curl,
                                                    "Host:spb.snyat-kvartiru-bez-posrednikov.ru",
                                                    "Origin:http://spb.snyat-kvartiru-bez-posrednikov.ru",
                                                    "Referer:".$page_url,
                                                    "Upgrade-Insecure-Requests:1",
                                                    "User-Agent:".$user_agent,
                                                    "Expect:"
                                                   ),
                    CURLOPT_POSTFIELDS     => $fields,
                    CURLOPT_POST           => true,        
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLINFO_HEADER_OUT    => true,
                    CURLOPT_CONNECTTIMEOUT => 20,
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_URL => trim($page_url)."#telephone"
                );
                curl_setopt_array($curl, $options);
                $result = curl_exec($curl);
                return $result;
                break;
            case 5:
            case 6:
                $url = $this->phone_page;
                $url = str_replace("#ID",$item_id,$url);
                $path = "/var/static/img/parsing/";
                //$path = "img/parsing/";
                $k = 0;
                $cookies_for_curl = "";
                $cookies_for_curl = "_ym_uid=1486370768629138736; vitodom=fa2361118c1797f6393fa7f038b884b5";
                $options = array(
                                CURLOPT_HEADER         => 1,
                                CURLOPT_HTTPHEADER     => array("Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                                                                "Accept-Encoding:gzip, deflate, sdch",
                                                                "Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
                                                                "Cache-Control:max-age=0",
                                                                "Connection:keep-alive",
                                                                "Content-Type:application/x-www-form-urlencoded",
                                                                "Cookie:".$cookies_for_curl,
                                                                "Host:vitodom.ru",
                                                                "Upgrade-Insecure-Requests:1",
                                                                "User-Agent:Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36",
                                                                "Expect:"
                                                               ),
                                CURLOPT_POSTFIELDS     => false,
                                CURLOPT_POST           => false,        
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLINFO_HEADER_OUT    => true,
                                CURLOPT_CONNECTTIMEOUT => 20,
                                CURLOPT_TIMEOUT        => 20,
                                CURLOPT_MAXREDIRS      => 10,
                                CURLOPT_URL => trim($url)
                            );
                
                while ($k < 10 && !preg_match("/^\s*(\+7|8)\s*\(?[0-9]{3}\)?\s*[0-9]{3}(\—|\-|\s)*[0-9]{2}(\—|\-|\s)*[0-9]{2}\s*$/sui",$result)){
                    //кастом для витодома, иначе криво сохранится
                    if($this->id == 6){
                        $curl = curl_init();
                        curl_setopt_array($curl, $options);
                        $result = curl_exec($curl);
                        $result = substr($result,strpos($result,"\r\n\r\n") + 4);
                        $image = imagecreatefromstring($result);
                        if(!empty($image)){
                            $filename = md5(time()).".png";
                            $full_name = $path.$filename;
                            $file = fopen($full_name,"w");
                            imagepng($image,$full_name);
                            fclose($file);
                        }
                    }
                    else $filename = Photos::Download($url,$path);
                    $full_name = $path.$filename;
                    list($width, $height) = getimagesize($full_name);
                    if($this->id == 5){
                        $new_width = $width * 2;
                        $new_height = $height * 2;
                    }else{
                        $new_width = $width * 2;
                        $new_height = $height * 2;
                    }
                    
                    $image_1 = imagecreatefrompng($full_name);
                    $image_2 = imagecreatetruecolor($new_width,$new_height);
                    imagecopyresampled($image_2,$image_1,0,0,0,0,$new_width,$new_height,$width,$height);
                    $resized_full_name = $path."BIG_".$filename;
                    imagepng($image_2, $resized_full_name, 0);
                    $result = trim(shell_exec("tesseract ".$resized_full_name." -psm 7 stdout"));
                    $result_fixed = (strstr($result,'?') ? str_replace('?','7',$result) : $result);
                    $result_fixed = (strstr($result_fixed,'B') ? str_replace('B','8',$result_fixed) : $result_fixed);
                    $result_fixed = (strstr($result_fixed,'С') ? str_replace('С','0',$result_fixed) : $result_fixed);
                    echo $result." => ".$result_fixed."\r\n";
                    if(!empty($result_fixed)) $result = $result_fixed;
					unlink($full_name);
					unlink($resized_full_name);
                    ++$k;
                }
                if(!preg_match("/^\s*(\+7|8)\s*\(?[0-9]{3}\)?\s*[0-9]{3}(\—|\-|\s)*[0-9]{2}(\—|\-|\s)*[0-9]{2}\s*$/sui",$result)) $result = "";
                //$result = "+7 (921) 577—30—32";                                                                                   
                echo "PHONE PARSED: ".$result."\r\n";
                
                
                
                return $result;
                break;
        }
        return false;
    }
    
    /**
    * формируем данные для отправки письма с отчетом
    * 
    * @param mixed $starting_letter
    */
    private function formLetterData($starting_letter = false){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $data['site'] = $this->estate_page;
        
        $data['from_page'] = $this->from_page;
        $data['to_page'] = $this->to_page;
        
        $data['start'] = ($starting_letter == 1 ? 1 : 0);
        
        $base_info = $db->fetch("SELECT objects_parsed,
                                        objects_added,
                                        DATE_FORMAT(`datetime_start`,'%Y.%m.%d %h:%i:%s') AS datetime_start,
                                        DATE_FORMAT(`datetime_end`,'%Y.%m.%d %h:%i:%s') AS datetime_end
                                 FROM ".$sys_tables['parsing_stats']." 
                                 WHERE id = ?",$this->line_id);
        
        $data = array_merge($data,$base_info);
        
        $data['objects_parsed'] = $this->lines_parsed;
        $data['objects_added'] = $this->lines_added;
        $data['objects_phone_error'] = count($this->loading_log['phone_errors']);
        $data['phone_errors'] = $this->loading_log['phone_errors'];
        $data['objects_images_error'] = count($this->loading_log['images_errors']);
        $data['image_errors'] = $this->loading_log['images_errors'];
        
        return array('letter_data' => $data);
    }
    
    /**
    * запись о начале парсинга
    * 
    */
    private function startParsing(){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $db->querys("INSERT INTO ".$sys_tables['parsing_stats']." (id_source,datetime_start,from_page) VALUES (?,NOW(),?)",$this->id,$this->from_page);
        $this->line_id = $db->insert_id;
        
        //письмо с отчетом
        require_once('includes/class.email.php');
        $mailer = new EMailer('mail');
        $letter_data = $this->formLetterData(1);
        $mailer->sendEmail("hitty@bsn.ru",
                           "Миша",
                           "Парсинг ".substr($this->estate_page,0,50)." начат",
                           "cron/parsing/templates/mail.endparsing.html",
                           "",
                           $letter_data,
                           false);
		
    }
    
    /**
    * обновляем информацию по ходу чтения
    * 
    * @param mixed $lines_parsed
    * @param mixed $lines_added
    * @param mixed $current_page
    */
    private function updateParsing($lines_parsed, $lines_added,$current_page){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $db->querys("UPDATE ".$sys_tables['parsing_stats']." 
                    SET objects_parsed = ?, objects_added = ?, to_page = ?
                    WHERE id = ? ",$lines_parsed, $lines_added, $current_page, $this->line_id);
                    
        require_once('includes/class.email.php');
        $mailer = new EMailer('mail');
    }
    
    /**
    * завершаем парсинг, пишем сколько всего объектов и страниц
    * 
    * @param mixed $lines_added
    * @param mixed $pages_affected
    */
    private function endParsing(){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $db->querys("UPDATE ".$sys_tables['parsing_stats']." 
                    SET datetime_end = NOW() 
                    WHERE id = ? ",$this->line_id);
        
        //письмо с отчетом
        require_once('includes/class.email.php');
        $mailer = new EMailer('mail');
        $letter_data = $this->formLetterData();
        $mailer->sendEmail("hitty@bsn.ru",
                           "Миша",
                           "Парсинг ".substr($this->estate_page,0,50)." начат",
                           "cron/parsing/templates/mail.endparsing.html",
                           "",
                           $letter_data,
                           false);
    }
    
    /**
    * читаем адресную строку с яндекс-карты (если есть)
    * 
    * @param mixed $page_text
    */
    private function getAddressTextFromYmap($page_text){
        $result = "";
        preg_match("/(?<=ymaps\.geocode\(\"|\')[^\)\'\"]*(?=\"|\'\))/",$page_text,$result);
        return (!empty($result) ? $result[0] : false);
    }
    
    /**
    * убираем в архив предыдущее перед выгрузкой
    * 
    */
    private function moveOldToArchive(){
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        return $db->querys("UPDATE ".$sys_tables['live']." SET published = 2 WHERE info_source = 1".$this->id." AND id_user = ? AND published = 1",$this->id_user);
    }
    
    /**
    * парсим площадку:
    *   убираем в архив старое из этого($this->id) источника, с этим($this->id_user) пользователем, письмо-оповещение
    *   цикл по страницам:
    *       открываем страницу
    *       читаем id с нее
    *       с помощью id читаем карточки с этой страницы, пишем заготовки в базы
    *       читаем заготовки с базы, парсим данные, пишем в live
    *   письмо с логом
    * @param mixed $from_page
    * @param mixed $to_page
    */
    public function parseSite($from_page = false,$to_page = false){
        
        $from_page = (!empty($from_page) ? $from_page : 1);
        $to_page = (!empty($to_page) ? $to_page : 1);
        
        $this->from_page = $from_page;
        $this->to_page = $to_page;
        
        $this->moveOldToArchive();
        
        $this->startParsing();
        
        while($from_page <= $to_page){
            
            $delay = rand(4,7);
            sleep($delay);
                                                                                                    
            $this->lines_parsed += $this->parseStubsToBase($from_page);
            //$this->lines_parsed += 20;
            echo $from_page." page processed, ".$this->lines_parsed." lines parsed\r\n";
            
            $this->lines_added += $this->moveStubsToEstate();
            //$this->lines_added += rand(10,20);
            echo $from_page." page finished, ".$this->lines_added." lines added\r\n";
            
            $this->updateParsing($this->lines_parsed,$this->lines_added,$from_page);
            
            ++$from_page;
        }
        
        $this->endParsing();
        return true;
    }
    
    /**
    * парсим карточки в таблицу заготовок
    * 
    */
    public function parseStubsToBase($page_num,$limit = false){
        
        
        //читаем страницу выдачи
        $page_url = str_replace('#PAGE',$page_num,$this->estate_page);
        $response = get_http_response_code($page_url);
        if(!in_array($response,array("200","301","302"))){
            $this->loading_log['errors'][] = $page_num."->".$response." error";
            return false;
        }
        
        $text = Parsing::getPageContent($page_url);
        $document = phpQuery::newDocument($text);
        if(empty($document)) throw new Exception("Не удалось скачать страницу по указанному адресу: ".$page_url,3);
        
        //читаем ID со страницы выдачи
        $ids = $this->getDataByCommand($document,$this->id_selector);
        if(!strstr($ids[0],'/')){
            $ids = array_filter(array_map(function($v){ return preg_replace("/[^0-9]/","",$v);},$ids));
            $ids = array_values(array_unique($ids));
        }
        shuffle($ids);
        
        if(!empty($limit)) $ids = array_slice($ids,0,$limit);
        
        $this->loading_log['loaded'] += count($ids);
        
        //$ids = array_slice($ids,0,1);
        //$ids = array(0 => "/about.php?p=53198");
        //$ids = array(0 => '29024');
        $counter = 0;
        //читаем карточки по списку ID и пишем заготовки в базу
        foreach($ids as $item_id){
            
            //читаем карточку (либо по ссылке либо по ссылке с заменяемым параметром)
            if($this->estateitem_page == "#LINK"){
                //когда вместо id ссылка, просто подставляем ее
                $item_url = $this->main_page.$item_id;
                $item_url = preg_replace('/(?<=[^\:])\/\//','/',$item_url);
            }else $item_url = str_replace('#ID',$item_id,$this->estateitem_page);
            
            $response = get_http_response_code($item_url);
            if(!in_array($response,array("200","301","302"))) continue;
            $text = Parsing::getPageContent($item_url);
            
            //костыль для http://spb.posrednikovzdes.net - у них windows-1251
            if($this->id == 3){
                $text = mb_convert_encoding($text,"UTF-8","windows-1251");
            }
            
            //$text = file_get_contents("html_item.txt");
            $item_document = phpQuery::newDocument($text);
            $item_data = $this->mapDataFromPage($item_document,preg_replace("/[^0-9]/sui",'',$item_id));
            
            if(!validate::isDigit($item_id)) $item_data['external_id'] = preg_replace("/[^0-9]/sui",'',$item_id);
            else $item_data['external_id'] = $item_id;
            
            //читаем телефон для этой карточки
            $item_data['phone'] = $this->getPhone($item_data['phone'],$item_data['external_id'],$item_url);
            //$item_data['phone'] = "+7 (952) 123-45-67";
            $address_line = $this->getAddressTextFromYmap($text);
            if(!empty($address_line)) $item_data['address'] = $address_line;
            
            //пишем заготовку в базу
            if(!empty($item_data['phone'])) $this->saveStubToBase($item_data);
            else $this->loading_log['phone_errors'][] = $item_url;
            ++$counter;
        }
        return $counter;
    }
    
    //копируем папки с картинками к нам, чистим файлы перед следующей загрузкой
    private function moveImages(){
        //чистим временные файлы снаружи
        shell_exec('find /home/bsn/static/img/uploads/ -maxdepth 1 -type f -name "*.*" -exec rm "{}" \;');
        
        //копируем
        shell_exec("scp -r /home/bsn/static/img/uploads/* root@77.221.133.66:/var/static/img/uploads/");
        
        //чистим содержимое папок с фотками
        shell_exec('find /home/bsn/static/img/uploads/*/ -mindepth 1 -type d -exec rm -rf {} \;');
    }
    
    /**
    * пишем заготовки в базу, прочитав данные
    * 
    */
    public function moveStubsToEstate(){
        require_once('includes/class.estate.php');
        if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
        global $db;
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        
        $stubs = $db->fetchall("SELECT * FROM ".$sys_tables['parsed_stubs']." WHERE id_source = ?",false,$this->id);
        $errors_log = array('img' => []);
        foreach($stubs as $stub){
            //читаем данные, составляем объявление
            $line = $this->parseDataFromStub($stub);
            
            $line['id_subway'] = $this->parseSubwayFromStub($stub);
            
            if(empty($line['id_district'])) $line['id_district'] = $this->parseDistrictFromStub($stub);
            
            $details = $this->parseDetailsFromStub($stub);
            if(empty($line['square_kitchen'])) $line['square_kitchen'] = (!empty($details['square_kitchen']) ? $details['square_kitchen'] : 0);
            if(empty($line['square_live'])) $line['square_live'] = (!empty($details['square_live']) ? $details['square_live'] : 0);
            if(empty($line['level'])) $line['level'] = (!empty($details['level']) ? $details['level'] : 0);
            if(empty($line['level_total'])) $line['level_total'] = (!empty($details['level_total']) ? $details['level_total'] : 0);
            
            $line['date_change'] = date("Y-m-d H:i:s");
            $line['info_source'] = "1".$this->id;
            $line['id_user'] = $this->id_user;
            $line['published'] = 1;
            $line['seller_phone'] = $stub['phone'];
            if(empty($line['txt_addr']) || empty($line['id_region'])) $line['txt_addr'] = $stub['address'];
            
            if(empty($line['txt_addr']) || $line['txt_addr'] != strip_tags($line['txt_addr'])){
                $this->loading_log['address_errors'][] = "external: ".$stub['external_id'];
                unset($line);
                unset($stub);
                continue;
            } 
            
            //поиск ранее загруженного объекта в основной таблице
            $check_object = $db->fetch("SELECT `id`, `id_main_photo`,`published`, info_source
                                        FROM ".$sys_tables["live"]."
                                        WHERE `external_id` = ? AND `id_user` = ? AND `info_source` = ?",
                                        $line['external_id'], $this->id_user, "1".$this->id);
                                        
            $robot = new EMLSXmlRobot($this->id_user);
            $robot->estate_type = "live";
            $robot->deal_type = 1;
            
            $has_photos = false;
            
            $inserted_id = 0;
            
            if(empty($check_object)){
                $line['id_main_photo'] = 0;
                $db->insertFromArray($sys_tables['live'],$line);
                $inserted_id = $db->insert_id;
            }
            else{
                $line['id'] = $check_object['id'];
                echo "archived restored: ".$check_object['id'];
                $db->updateFromArray($sys_tables['live'],$line,"id");
                if(!empty($line['photos'])) {
                    list($photos['already_in'],$photos['to_add']) = $robot->getPhotoList($line['photos'], $check_object['id']);
                    
                    $photos_list_in = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                     WHERE `id_parent` = ".$check_object['id']."
                                                     ".(!empty($photos['to_delete'])?" AND `external_img_src` IN (".implode(',', $photos['already_in']).")":""),'id');

                    $photos_list = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                  WHERE `id_parent` = ".$check_object['id']."
                                                  ".(!empty($photos_list_in)?" AND `id` NOT IN (".implode(',', array_keys($photos_list_in)).")":""),'id');
                } else
                    $photos_list = $db->fetchall("SELECT DISTINCT `id` FROM ".$sys_tables[$robot->estate_type.'_photos']." 
                                                  WHERE `id_parent` = ".$check_object['id']);
                
                if(!empty($photos_list)){
                    foreach($photos_list as $k => $val) Photos::Delete($robot->estate_type,$val['id']);
                    $photos_to_delete_ids = implode(',', array_keys($photos_list));
                    if(!empty($photos_to_delete_ids)) $db->querys("DELETE FROM ".$sys_tables[$robot->estate_type.'_photos']." WHERE `id` IN (".$photos_to_delete_ids.")");
                }
                $inserted_id = $check_object['id'];
                $line['photos'] = $photos['to_add'];
                if(!empty($photos['already_in'])) $has_photos = true;
            }
            
            //если фотки есть, грузим
            if(!empty($line['photos'])){
                foreach($line['photos'] as $key => $photo_url){
                    $line['photos'][$key] = str_replace('small','big',$photo_url);
                    //если дана относительная ссылка, составляем с использованием главной ссылки
                    if(!strstr($photo_url,"http")){
                        $line['photos'][$key] = preg_replace('/(?<!\:|^)\/\//sui','/',$this->main_page.$photo_url);
                    }
                }
                Photos::$__folder_options=array(
                                'sm'=>array(90,90,'cut',65),
                                'med'=>array(560,415,'',75),
                                'big'=>array(800,600,'',70)
                                );
                if(!is_array($line['photos'])) $line['photos'] = array($line['photos']);
                $line['photos'] = array_unique($line['photos']);
                $line['photos'] = array_filter($line['photos'],function($v){return (!strstr($v,'0000'));});
                $external_img_sources = Photos::MultiDownload($line['photos'], ROOT_PATH.'/'.Config::$values['img_folders']["live"].'/');
                foreach($external_img_sources as $k=>$img) {
                    print_r($img);
                    $photo_add_result = Photos::Add("live", $inserted_id, '', $img['external_img_src'], $img['filename'], false, false, false, Config::Get('watermark_src'));
                    if(!is_array($photo_add_result)) {
                        $errors_log['img'][] = $img['external_img_src'];
                        if($img['external_img_src'] == $fields['main_photo']) $fields['main_photo'] = '';
                    } else $has_photos = true;
                }
            }
            //если нет фоток, убираем (кроме vitodom.ru)
            if(!$has_photos && $this->id != 6){
                $this->loading_log['images_errors'][] = $inserted_id;
                $db->querys("UPDATE ".$sys_tables['live']." SET published = 3 WHERE id = ?",$inserted_id);
                continue;
            } elseif(!$has_photos) $db->querys("UPDATE ".$sys_tables['live']." SET id_main_photo = 0 WHERE id = ?",$inserted_id);
            
            //считаем вес
            $item_weight = new Estate(TYPE_ESTATE_LIVE);
            $item_weight = $item_weight->getItemWeight($inserted_id,"live");
            $res_weight = $db->querys("UPDATE ".$sys_tables["live"]." SET weight=? WHERE id=?", $item_weight, $inserted_id);
        }
        
        $this->moveImages();
        
        //чистим таблицу
        $db->querys("DELETE FROM ".$sys_tables['parsed_stubs']." WHERE id_source = ?",$this->id);
        if(!empty($db->error)) echo "ERROR CLEARING: ".$db->last_query;
        
        return count($stubs);
    }
}
?>