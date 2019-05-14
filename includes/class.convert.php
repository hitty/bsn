<?php
if(!defined("TYPE_STRING")) define( "TYPE_STRING", "string" );
if(!defined("TYPE_INTEGER")) define( "TYPE_INTEGER", "integer" );
if(!defined("TYPE_FLOAT")) define( "TYPE_FLOAT", "float" );
if(!defined("TYPE_BOOLEAN")) define( "TYPE_BOOLEAN", "boolean" );
if(!defined("TYPE_ARRAY")) define( "TYPE_ARRAY", "array" );
if(!defined("TYPE_OBJECT")) define( "TYPE_OBJECT", "object" );
if(!defined("TYPE_PARAMETER")) define( "TYPE_PARAMETER", "parameter" );
if(!defined("TYPE_DATETIME")) define( "TYPE_DATETIME", "datetime" );
if(!defined("TYPE_DATE")) define( "TYPE_DATE", "date" );
if(!defined("TYPE_TIME")) define( "TYPE_TIME", "time" );
if(!defined("SITE_CHARSET")) define ( "SITE_CHARSET", Config::$values['site']['charset']);
include_once('includes/simple_html_dom.php');

class Convert {
    public static $json_internal = null;
    // --- Convertors --- //
    public static function ToString( $value ) {
        if( $value===null ) return null;
        if( is_object( $value ) || is_array( $value )) {
            $result = serialize($value);
        } else $result = (string) $value;
        return $result;
    }

    public static function ToInt( $value , $validate = false) {
        if(!empty($validate)) $value = preg_replace('/[^0-9]/msiU', '', $value);
        if( is_object( $value ) || is_array( $value ) || $value===null ) {
            return null;
        }
        $result = (int) $value;
        return $result;
    }
    
    public static function ToInteger( $value , $validate = false ) {
        
        return self::ToInt( $value, $validate );
    }

    public static function ToFloat( $value  , $validate = false, $range = '') {
        if(!empty($validate)){
            $value = str_replace(',','.',$value);
            $value = preg_replace('/[^0-9\.]/msiU', '', $value);
        }
        if( is_object( $value ) || is_array( $value ) || $value===null ) {
            return null;
        }
        if(!empty($range)) $value = number_format($value / 1000000, 2, ".", " ");
        $result = (float) $value;
        return $result;
    }

    public static function ToDouble( $value ) {
        return self::ToFloat( $value );
    }

    public static function ToBoolean( $value ) {
        if( $value===null ) {
            return null;
        }
        $result = (bool) $value;
        return $result;
    }

    public static function ToArray( $value ) {
        $result = (array) $value;
        return $result;
    }

    public static function ToObject( $value ) {
        $result = (object) $value;
        return $result;
    }
    
    public static function ToDateTime( $value ) {
        if(empty($value)) return null;
        $datetime = date('Y-m-d H:i:s',strtotime(self::ToString($value)));
        return $datetime;
    }

    public static function ToDate( $value ) {
        if(empty($value)) return null;
        $datetime = date('Y-m-d',strtotime(self::ToString($value)));
        return $datetime;
    }

    public static function ToTime( $value ) {
        if(empty($value)) return null;
        $datetime = date('H:i:s',strtotime(self::ToString($value)));
        return $datetime;
    }
    
    public static function ToSquare( $value ) {
        if(empty($value)) return null;
        return rtrim(rtrim(number_format($value, 2, ".", ""), "0"), ".");
    }
    
    public static function ToNumber( $value, $type = '') {
        if(!isset($value)) return null;
        if(!empty($type)) $value = $value / 10000000;
        return number_format($value, 0, ".", " ");
    }
    
    public static function ToPhone( $value , $prefix = 812, $country_code = false, $string = false) {
        $return = [];
        if(empty($value) || strlen($value)<7) return $return;
        $phones = preg_replace('/[^0-9\,]/', '', $value);
        $phones = explode(',',$phones);
        foreach($phones as $k=>$phone){
            if(strlen($phone)>6){
                if(strlen($phone)==7) $phone = $prefix.$phone;
                elseif(strlen($phone)>10)  $phone = substr($phone,-10);
                $phone = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{2})([0-9]{2})/", "($1) $2-$3-$4", $phone);
                $return[] = (!empty($country_code)?$country_code.' ':'').$phone;
            }
        }
        if(!empty($string)) return implode(', ', $return);
        return $return;
    }
    /**
     * Common convert function
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public static function ToValue( &$value, $type = "parameter" ) {
        switch( $type ) {
            case TYPE_STRING:
                return self::ToString( $value );
            case TYPE_INTEGER:
                return self::ToInt( $value );
            case TYPE_FLOAT:
                return self::ToFloat( $value );
            case TYPE_BOOLEAN:
                return self::ToBoolean( $value );
            case TYPE_ARRAY:
                return self::ToArray( $value );
            case TYPE_OBJECT:
                return self::ToObject( $value );
            case TYPE_PARAMETER:
                return $value;
            case TYPE_DATE:
                return self::ToDate( $value );
            case TYPE_TIME:
                return self::ToTime( $value );
            case TYPE_DATETIME:
                return self::ToDateTime( $value );
            default:
                return null;
        }
    }
    
    /**
    * @desc Convert object or array to array with keys, represented in sourse array|object as one of fieldvalues
    * @param array|object sourse data set
    * @param string Field name, which will be a key
    * @param boolean convert values with same keys in array (or rewrite it)
    */
    public static function Collapse( $sourceObjects, $collapseKeys, $toArray = true) {
        if ( empty( $sourceObjects ) ) {
            return null;
        }
        $result = [];
        $keys = explode(',',$collapseKeys);
        foreach ( $sourceObjects as $object ) {
            $keystring = '';
            foreach($keys as $key){
                if(is_int($key)) $keystring .= '['.$object[trim($key)].']';
                else $keystring .= "['".$object[trim($key)]."']";
            }
            if($toArray) $str = '$result'.$keystring.'[] = $object;';
            else $str = '$result'.$keystring.' = $object;';
            @eval($str);
        }
        return $result;
    }
    

    /**
    * @desc Sort named array by keys
    * @param array sourse data set
    * @param string key name
    * @param boolean convert values with same keys in array (or rewrite it)
    */
    public static function ArrayKeySort($array, $key, $toArray = false){
        $array = self::Collapse($array,$key,$toArray);
        $keys = array_keys($array);
        natsort($keys);
        $result = [];
        foreach($keys as $key){
            $result[$key] = $array[$key];
        }
        return $result;
    }


    public static function ToTranslit($str,$isFileName=false,$removeBrackets=false){
        if($isFileName){
            $tbl= array(
            'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 'д'=>'d', 'е'=>'e', 'ж'=>'g', 'з'=>'z',
            'и'=>'i', 'й'=>'y', 'к'=>'k', 'л'=>'l', 'м'=>'m', 'н'=>'n', 'о'=>'o', 'п'=>'p',
            'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u', 'ф'=>'f', 'ы'=>'i', 'э'=>'e', 'А'=>'A',
            'Б'=>'B', 'В'=>'V', 'Г'=>'G', 'Д'=>'D', 'Е'=>'E', 'Ж'=>'G', 'З'=>'Z', 'И'=>'I',
            'Й'=>'Y', 'К'=>'K', 'Л'=>'L', 'М'=>'M', 'Н'=>'N', 'О'=>'O', 'П'=>'P', 'Р'=>'R',
            'С'=>'S', 'Т'=>'T', 'У'=>'U', 'Ф'=>'F', 'Ы'=>'I', 'Э'=>'E', 'ё'=>"yo", 'х'=>"h",
            'ц'=>"ts", 'ч'=>"ch", 'ш'=>"sh", 'щ'=>"shch", 'ъ'=>"_", 'ь'=>"_", 'ю'=>"yu", 'я'=>"ya",
            'Ё'=>"YO", 'Х'=>"H", 'Ц'=>"TS", 'Ч'=>"CH", 'Ш'=>"SH", 'Щ'=>"SHCH", 'Ъ'=>"_", 'Ь'=>"_",
            'Ю'=>"YU", 'Я'=>"YA", ' '=>'_', ','=>'_', '?'=>'_', '*'=>'_', '\\'=>'_', '#'=>'_', '&'=>'_'
            );
            if($removeBrackets) $str = preg_replace('/[\[\]\(\)\{\}]/msiuU', '', trim($str));
            return strtolower(strtr($str, $tbl));
        } else {
            $str = preg_replace('/[^0-9а-яa-я\_\-\s]/msiuU', '', trim($str));
            $ru = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ь','ы','ъ','э','ю',    'я', ' ','.',',','-','"','/','\\','«','»','?');
            $en = array('a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya','_','', '', '', '', '', '',  '', '','');
            $str = mb_strtolower($str, 'UTF-8');
            $str = str_replace($ru,$en,$str);
            return trim($str);
        }
    }
    public static function ToRusian($str){
        if(!preg_match( '!^[а-яА-Я]{1,}$!i', (string) $str) ){
            $ru = array('я','ч','с','м','и','т','ь','б','ю','ф','ы','в','а','п','р','о','л','д','ж','й','ц','у','к','е','н','г','ш','щ','з','х','ъ');
            $en = array('z','x','c','v','b','n','m',',','.','a','s','d','f','g','h','j','k','l',';','q','w','e','r','t','y','u','i','o','p','[',']');
            $str = mb_strtolower($str, 'UTF-8');
            $str = str_replace($en,$ru,$str);
        }
        return trim($str);
        
    }    
    /**
    * Получение русской даты в формате "13 августа 2010"
    * @param mixed $datetime - дата сторокой или timestamp
    * @return string
    */
    public static function ru_date($datetime=null){
        if(!is_numeric($datetime)) $datetime = strtotime($datetime);
        $months = array('января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря');
        return date('j',$datetime).' '.$months[intval(date('n',$datetime))-1].' '.date('Y',$datetime);
    }
    
    /**
    * Получение русского названия месяца
    * @param mixed $datetime - дата сторокой или timestamp
    * @param bool $basic_form - именительный падеж (иначе родительный падеж)
    */
    public static function ru_month($datetime=null,$basic_form=false){
        if(!is_numeric($datetime)) $datetime = strtotime($datetime);
        $months = array('января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря');
        $months_basic = array('январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь');
        return $basic_form ? $months_basic[intval(date('n',$datetime))-1] : $months[intval(date('n',$datetime))-1];
    }
    
    /**
    * Получение русского названия дня недели
    * @param mixed $datetime - дата сторокой или timestamp
    * @param bool $basic_form - именительный падеж (иначе родительный падеж)
    */
    public static function ru_week($datetime=null, $basic_form=false, $get_array=false){
        $weekdays = array('понедельник','вторник','среду','четверг','пятницу','субботу','воскресенье');
        $weekdays_basic = array('понедельник','вторник','среда','четверг','пятница','суббота','воскресенье');
        if(!empty($get_array)) return $weekdays_basic;
        else{
            if(!is_numeric($datetime)) $datetime = strtotime($datetime);
            return $basic_form ? $weekdays_basic[intval(date('w',$datetime))-1] : $weekdays[intval(date('w',$datetime))-1];
        }
    }

    /**
    * Возвращает json-кодированный параметр
    * @param mixed $a
    * @return string
    */
    public static function json_encode($a=false){
        if(self::$json_internal===null) {
            $funcs = get_defined_functions();
            self::$json_internal = in_array('json_encode',$funcs['internal']);
        }    
        if(self::$json_internal) return json_encode($a);
        if ($a === null) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a)){
            if (is_float($a)){
                return floatval(str_replace(",", ".", strval($a)));
            }
            if (is_string($a)){
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            } else return $a;
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)){
            if (key($a) !== $i){
                $isList = false;
                break;
            }
        }
        $result = [];
        if ($isList){
            foreach ($a as $v) $result[] = self::json_encode($v);
            return '[' . join(',', $result) . ']';
        } else {
            foreach ($a as $k => $v) $result[] = self::json_encode($k).':'.self::json_encode($v);
            return '{' . join(',', $result) . '}';
        }
    }
    
    /**
    * Преобразование массива в строку (для записи в бд или файл)
    * @param array $arr
    * @return string
    */
    public static function ArrayToString($arr){
        return addslashes(serialize($arr));
    }
    /**
    * преобразование сохраненного сериализованного массива обратно к виду массива
    * @param strig $str
    * @return array
    */
    public static function StringToArray($str){
        if(empty($str)) return [];
        return unserialize(stripslashes($str));
    }
    
    /**
    * Преобразование строки в фомате GET-параметров в массив
    * @param string строка в формате GET-параметров
    * @param boolean декодировать строку при помощи urldecode() перед разбором
    * @return array именованный массив
    */
    public static function StringGetToArray($str, $urldecoding=false){
        $return = [];
        if(empty($str)) return $return;
        $str = trim($str,'?');
        $array = explode('&',$str);
        foreach($array as $param){
            list($key,$val) = explode('=',$param.'=');
            if($urldecoding) $val = urldecode($val);
            if($val=="null") $val = null;
            elseif($val=="true") $val = true;
            elseif($val=="false") $val = false;
            $return[$key] = $val;
        }
        return $return;
    }
    
    /**
    * Конверсия именованного массива в строку вида GET-запроса
    * @param array именованный массив
    * @param boolean кодировать результат при помощи urlencode()
    * @return string строка в виде GET-запроса
    */
    public static function ArrayToStringGet($array,$urlencoding=false){
        $return = [];
        foreach($array as $key=>$val){
            if(is_null($val)) $val = "null";
            elseif($val===true) $val = "true";
            elseif($val===false) $val = "false";
            else $val = self::ToString($val);
            if($urlencoding) $val = urlencode($val);
            $return[] = $key.'='.$val;
        }
        return implode('&',$return);
    }
    /**
    * Вырезание всех тегов, кроме исключенного набора и аттрибутов html
    * @return string
    */
    public static function StripText($string){
                
        $string = preg_replace('/<([A-z][A-z0-9]*)[^>]*?(\/?)>/sui','<$1$2>', strip_tags($string,'<div><p><ul><ol><li><b><strong><i><h3><h4>') );
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace( '&amp;', '&', $string );
        return $string;
    }

    /**
    * Замена в url подобный формат
    * @param   string   input string
    * @return  boolean
    */     
    public static function chpuTitle($string){
        $string = preg_replace('/[^0-9а-яa-я\_\-\s]/msiuU', '', trim($string));
        $ru = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ь','ы','ъ','э',    'ю', 'я',' ','.',',','-','"','/','\\','«' ,'»','?',')','(');
        $en = array('a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya','_','' , '', '', '', '', '' ,  '', '','' ,'' ,'');
        $chpu_title = mb_strtolower($string, 'UTF-8');
        $chpu_title = str_replace($ru,$en,$chpu_title);
        return trim($chpu_title);
    }
    /**
    * Поиск значения в массиве по названию ключа
    * @param   $array   input $array  - массив
    * @param   $value   input string - значение
    * @param   $key   input string - ключ
    * @return  boolean
    */    
    public static function arraySearchValueByKey($array, $value, $key){
        $i=0;
        do 
            if($array[$i][$key] == $value)
                return $array[$i][$key];
        while(++$i<count($array));
        return false;
    }      
    /**
    * Первый символ с заглавной буквы
    * @param   $value   input string - значение
    * @return  string
    */    
    public static function firstLetterUpperCase($value, $to_lower = false){
        return !empty($to_lower) ? mb_strtoupper(mb_substr($value,0,1, 'UTF-8'), 'UTF-8') . mb_strtolower(mb_substr($value,1,strlen($value), 'UTF-8'), 'UTF-8')
                                 : mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value,1,strlen($value), 'UTF-8');  
    }           

    
    public static function CleanHtml( $html ){
        return preg_replace( "#(\<[p|div]{1,3}\s?(style=\"[a-z0-9\s\:\.\;\,\-]{0,}\")?\>\r?\n?\s?&nbsp;\<\/[p|div]{1,3}\>)#msiU", "", strip_tags($html,"<table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3><div><p><span><u><i><em><ul><ol><li><blockquote>" ) );
    }
    
    public static function stripUnwantedTagsAndAttrs($html_str){
        if(!empty($html_str)){
            $html_str = str_replace('&amp;', '&', $html_str);
            $html_str = str_replace('&nbsp;', ' ', $html_str);
            $html_str = str_replace('&quot;', '"', $html_str);
            $html_str = str_replace(array("\r","\n","\t"), '', $html_str);
            $html_str = (preg_replace('#\<p\>(?-i:\s++|&nbsp;)*\<\/p\>#sui', ' ', $html_str));
            $html_str = (preg_replace('#\<div\>(?-i:\s++|&nbsp;)*\<\/div\>#sui', ' ', $html_str));
            $html = str_get_html($html_str);
            foreach($html->find('strong,b,i,em,ol,ul,li,p,div,span,br,h1,h2,h3,blockquote,font,table,tr,td') as $p) {
                foreach ($p->getAllAttributes() as $attr => $val) {
                    $p->removeAttribute($attr);
                }    
            }

            return $html->innertext;
        }
    }
}

/**
* ----------------------------------------------------------------------------------------------------------------------
* Validator Class
* ----------------------------------------------------------------------------------------------------------------------
*/
class Validate{

    /**
     * Check for valid value by type
     * @param mixed $value
     * @param const $type
     * @return bool
     */
    public static function isValidType( $value, $type ) {
        switch( $type ) {
            case TYPE_STRING:
                return true;
            case TYPE_INTEGER:
                return self::Digit($value) || (self::Digit(0-$value) && self::Numeric($value));
            case TYPE_FLOAT:
                return self::Numeric( $value );
            case TYPE_BOOLEAN:
                return ($value == 1 || $value == 0);
            case TYPE_ARRAY:
                return (Convert::ToArray( $value ) == $value);
            case TYPE_DATE:
            case TYPE_TIME:
            case TYPE_DATETIME:
                return !empty( $value );
            case TYPE_OBJECT:
                return (Convert::ToObject( $value ) == $value);
            case TYPE_PARAMETER:
                return true;
            default:
                return false;
        }
    }
    
    /**
     * Check for valid value by min value
     * @param mixed $value
     * @param mixed $min
     * @param const $type
     * @return boolean
     */
    public static function IsValidMax( $value, $max, $type ) {
        switch( $type ) {
            case TYPE_STRING:
                return mb_strlen( $value, SITE_CHARSET ) <= $max;
            case TYPE_INTEGER:
            case TYPE_FLOAT:
                return $value <= $max;
            case TYPE_ARRAY:
                return count( $value ) <= $max;
            case TYPE_PARAMETER:
                return true;
            default:
                return false;
        }
    }

    /**
     * Check for valid value by min value
     * @param mixed $value
     * @param mixed $min
     * @param const $type
     * @return bool
     */
    public static function IsValidMin( $value, $min, $type ) {
        switch( $type ) {
            case TYPE_STRING:
                return mb_strlen( $value, SITE_CHARSET ) >= $min;
            case TYPE_INTEGER:
            case TYPE_FLOAT:
                return $value >= $min;
            case TYPE_ARRAY:
                return count( $value ) >= $min;
            case TYPE_PARAMETER:
                return true;
            default:
                return false;
        }
    }

    /**
    * Validate email, commonly used characters only
    * @param   string   email address
    * @return  boolean
    */
    public static function isLogin( $login ) {
        return (bool) preg_match( '!^[a-zA-Z\-\+\.\,\_\(\)\{\}\[\]\<\>\~0-9\ ]{4,24}$!i', (string) $login);
    }    
    
    /**
    * Validate email, commonly used characters only
    * @param   string   email address
    * @return  boolean
    */
    public static function isPassword( $password ) {
        return (bool) preg_match( '!^[a-zA-Z\-\+\.\,\_\(\)\{\}\[\]\<\>\~0-9\ ]{4,24}$!i', (string) $password);
    }    
    
    /**
    * Validate email, commonly used characters only
    * @param   string   email address
    * @return  boolean
    */
    public static function isEmail( $email ) {
        return (bool) preg_match( '!^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,10}$!i', (string) $email);
    } 
    
    /**
    * Strip emails from given text
    * @param   string   
    */
    public static function stripEmail( $text ) {
        return self::stripUrl((string) preg_replace ("/\S+@\S+[\ \t\n\r]{1}/" , "", $text));
    } 
    /**
    * Strip url from given text
    * @param   string   
    * @return  string
    */
    public static function stripUrl( $text ) {
        return (string) preg_replace ("/(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?/" , "#", $text);
    } 

    /**
    * Validate phone number, 
    * @param   string   phone number
    * @return  boolean
    */
    public static function isPhone( $phone ) {
        $phone = Convert::ToPhone($phone);
        if(!empty($phone) && is_array($phone)) $phone = $phone[0];
        else return false;
        return (bool) preg_match( '!(8)?[\d]{10}$!i', (string) str_replace(array('-','(',')',' '),'',$phone));
    }

    /**
    * Strip hidden (for example "+7%  ^%-921#@ -985-@ # 91- !@#41") phone numbers(number sequences longer than 6) from given text 
    * 
    * @param string $text
    * @return string
    */
    public static function stripPhone($text,$short_method = false){
        //заменяем старым способом 
        $text = preg_replace ("/((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,16}/" , " ", $text);
        if(!empty($short_method)) return (string) $text;
        //ищем все цифровые последовательности, длинные заменяем
        preg_match_all("/(?<!=[0-9[:punct:]?])([0-9[:punct:]]+)(?!=[0-9[:punct:]?])/sui",$text,$matches);
        foreach($matches[0] as $key=>$match){
            //если цифр много, заменяем этот фрагмент в тексте
            if(preg_match_all('/\d/si',$match) >= 7)
                $text = str_replace($match,'',$text);
        }
        //убираем цифровые последовательности и не-пробелы перед ней "Тел.:+79219859141."
        $text =  preg_replace('/[^\s][0-9]{6,}/sui','',$text);
        return (string) $text;
    }
    
    /**
    * Checks whether a string consists of digits only (no dots or dashes).
    * @param   string   input string
    * @return  boolean
    */
    public static function Digit( $value ) {
        return ctype_digit(Convert::ToString($value));
    }
    /**
    * check for valid (only) digital value (alias of "Digit" method)
    * @param string $value
    * @return boolean
    */
    public static function isDigit($value){
        return self::Digit( $value );
    }
    /**
    * check is email in our black list
    * @param string $value
    * @return boolean
    */
    public static function emailBlackList($value){
        $bl = array('lackmail.net', 'alkmail.net', 'bigprofessor.so', 'alivance.com','walkmail.net');
        return in_array($value, $bl);
    }
    /**
    * Checks whether a string is a valid number (negative and decimal numbers allowed).
    * @param   string   input string
    * @return  boolean
    */
    public static function Numeric($str) {
        return (is_numeric($str) && preg_match('/^[-0-9.]+$/D', (string) $str));
    }

                          
    public static function validateParams($params,&$mapping){
        $errors = [];
        if(!is_array($params) || !is_array($mapping)) return array('wrong_input_data'=>'Некорректные данные');
        foreach($params as $key=>$value){
            if(isset($mapping[$key])){
                foreach($mapping[$key] as $type=>$critery){
                    switch($type){
                        case 'type':
                            switch($critery){
                                case 'integer' :
                                    $mapping[$key]['value'] = Convert::ToInt($value,true);
                                    $critery_text = 'числовым'; 
                                case 'float' : 
                                    $mapping[$key]['value'] = Convert::ToFloat($value,true);
                                    $critery_text = 'числовым';
                                    break;
                                case 'string' :
                                    $mapping[$key]['value'] = Convert::ToString($value);
                                    $critery_text = 'текстовым';
                                    break;
                            }
                            if(!self::isValidType($mapping[$key]['value'],$critery)) $errors[$key] = 'Значение поля должно быть '.$critery_text;
                            break;
                        case 'min':
                            if(!self::IsValidMin($value,$critery,$mapping[$key]['type'])) {
                                if($mapping[$key]['type']==TYPE_STRING) $errors[$key] = 'Минимальная длина '.$critery.' символов';
                                else $errors[$key] = 'Минимально допустимое значение '.$critery;
                            }
                            break;
                        case 'max':
                            if(!self::IsValidMax($value,$critery,$mapping[$key]['type'])) {
                                if($mapping[$key]['type']==TYPE_STRING) $errors[$key] = 'Максимальная длина '.$critery.' символов';
                                else $errors[$key] = 'Максимально допустимое значение '.$critery;
                            }
                            break;
                        case 'allow_empty':
                            if(empty($critery) && ($value===FALSE || is_null($value) || (is_string($value) && $value=='') || (Validate::Numeric($value) && $value==0))) $errors[$key] = 'Значение не может быть пустым';
                            break;
                        case 'allow_null':
                            if(empty($critery) && $value===null) $errors[$key] = 'Значение NULL не разрешено';
                            if(isset($errors[$key]) && $errors[$key]=='type' && $critery && $value===null) unset($errors[$key]);
                            break;
                        case 'default':
                            break;
                    }
                }
            }
        }
        return $errors;
    }
    
    /**
    * Checks whether a string is a valid url (eng letters, numbers, '_' allowed).
    * @param   string   input string
    * @return  boolean
    */
     public static function isUrl($url){
         if (preg_match('/^[a-z0-9_]+$/siu',$url)) return true;
         return false;
     }
    /**
    * Checks vote interval with logical site $section name for voting with setting cookie $value
    * @section string   input string 
    * @time   string   input string
    * @value string   input string 
    * @return  boolean
    */
     public static function CanVote($section){
         $bsn_cookie_name = "bsnvoteinterval".$section;
         $answer = false;
         if (!in_array($bsn_cookie_name,array_keys($_COOKIE))){
            if(func_num_args() == 3){
                setcookie($bsn_cookie_name, func_get_arg(2), time()+func_get_arg(1),'/');
            }
            $answer = true;
         }
         return $answer;
     }
     
    /**
    * Проверка на гласную/согласную буквы
    * @section 
    * @return  boolean
    */
     public static function isConsonantsEn($letter){
        return in_array($letter, array('q','w','r','t','p','s','d','f','g','h','j','k','l','z','x','c','v','b','n','m'));
     }
     

}
?>