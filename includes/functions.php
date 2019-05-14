<?php
$moderate_statuses = Config::Get('moderate_statuses');

/**    
* Набор функций
*/

/**
* Определение паджинатора
* return boolean
*/
ini_set("memory_limit", "4600M");
function isPage($str)
{
    return preg_match("/^page\d+$/i",$str);
}
/**
* Определение номера страницы паджинатора
* return integer
*/
function getPage($param_num)
{
    $tmp = sscanf($param_num, 'page%d');
    if(sizeof($tmp) > 0 && $tmp[0]!='')
    {
        return current($tmp);
    }
    return 1;
}

/**
 * генератор случайных символов
 * @param integer $len lenght of string to be generated
 * @return string generated random string
 */
function randomstring($len, $type = 'lowcase')
{
    $c = array_merge(range('48','57'),range('65','90'));
    $token = '';
    for($i=0;$i<$len;$i++){
        $token .= chr($c[mt_rand(0,sizeof($c)-1)]);
    }
    if($type == 'lowcase')  return strtolower($token);
    else return strtoupper($token);
}
/*
 * Форматируем строку с размеров файла
 */
function formatSize($size) {
    $units = array(' Б', ' Кб', ' Мб', ' Гб', ' Тб');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 2).$units[$i];
}


/**
 * загрузка и удаление фоток спецпредложений
 * @param string $imgName  - имя файла - старой фотки, на замену
 * @param array $data  - массив с инфо о загружаемом файле
 * @param string $type  - раздел для загрузки (в шапку или на главную и  разделы)
 * @return string - имя нового файла файла
 */
function replaceSpecFoto($imgName, $data, $type){
    $tempFolder = Host::$root_path.'/'.Config::$values['img_folders']['tmp'].'/'; // папка для временных файлов 
    $fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
    $fileParts = pathinfo($data['name']);
    $targetExt = $fileParts['extension'];
    $targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
    $_150x150Folder = Host::$root_path.'/'.Config::$values['img_folders']['spec_offers'];
    $_180x90Folder = Host::$root_path.'/'.Config::$values['img_folders']['spec_offers'];
    if (in_array(strtolower($targetExt),$fileTypes)) {
        move_uploaded_file($data['tmp_name'],$tempFolder.$targetFile);
        if($type=='main_img_src'){
            copy($tempFolder.$targetFile, $_150x150Folder.'/'.$targetFile);
            //удаление старых фото
            if(file_exists($_150x150Folder.'/'.$imgName)  && is_file($_150x150Folder.'/'.$imgName)) unlink($_150x150Folder.'/'.$imgName);
        } else {
            Photos::imageResize($tempFolder.$targetFile, $_180x90Folder.'/'.$targetFile, '180','90','cut');
            //удаление старых фото
            $_180x90Folder.'/'.$imgName;
            if(file_exists($_180x90Folder.'/'.$imgName) && is_file($_180x90Folder.'/'.$imgName)) unlink($_180x90Folder.'/'.$imgName);
        }
        unlink($tempFolder.$targetFile);
        return $targetFile;
    }
    else return false;
    
}


function sendHTTPStatus($code){
    switch ($code) {
        case 301:
            header( "HTTP/1.0 301 Moved Permanently" );
            break;    
        case 302:
            header( "HTTP/1.0 302 Moved Temporarily" );
            break;    
        case 403:
            header( "HTTP/1.0 403 Forbiden" );
            break;    
        case 404:
            header( "HTTP/1.0 404 Not Found" );
            break;    
        case 409:
            header( "HTTP/1.0 409 Conflict" );
            break;    
        case 500:
            header( "HTTP/1.0 500 Internal Server Error" );
            break;    
        case 501:
            header( "HTTP/1.0 501 Not Implemented" );
            break;    
        case 503:
            header( "HTTP/1.0 503 Service Unavailable" );
            break;    
        default:
            header( "HTTP/1.0 404 Not Found" );
            break;    
    }
}

function createCHPUTitle($title){
    $title = preg_replace('/[^0-9а-яa-я\_\-\s]/msiuU', '', trim($title));
    $ru = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ь','ы','ъ','э',    'ю', 'я',' ','.',',','-','"','/','\\','«' ,'»','?',')','(');
    $en = array('a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya','_','' , '', '', '', '', '' ,  '', '','' ,'' ,'');
    $chpu_title = mb_strtolower($title, 'UTF-8');
    $chpu_title = str_replace($ru,$en,$chpu_title);
    return trim($chpu_title);
}

/**
 * Склонение числительных
 * @param int $numberof — склоняемое число
 * @param string $value — первая часть слова (можно назвать корнем)
 * @param array $suffix — массив возможных окончаний слов
 * @return string
 *
 */
 function makeSuffix($numberof, $value, $suffix)
{
    // не будем склонять отрицательные числа
    $numberof = abs($numberof);
    $keys = array(2, 0, 1, 1, 1, 2);
    $mod = $numberof % 100;
    $suffix_key = $mod > 4 && $mod < 20 ? 2 : $keys[min($mod%10, 5)];
    return $value . $suffix[$suffix_key];
}
function curlThis($url, $method = 'GET', $data = false, $ref = false, $body = false,$return_result_url = false){
    $agents = array("Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36"
                    ,"Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3"
                    ,"Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko"
                    ,"Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; Motorola VIP12xx)"
                    ,"Mozilla/5.0 (X11; U; Linux i686; cs-CZ; rv:1.7.12) Gecko/20050929"
                    ,"Opera/9.80 (Macintosh; Intel Mac OS X 10.6.7; U; ru) Presto/2.8.131 Version/11.10"
                    ,"Opera/9.80 (S60; SymbOS; Opera Mobi/499; U; ru) Presto/2.4.18 Version/10.00"
                    ,"Opera/9.80 (Android; Opera Mini/7.5.31657/28.2555; U; ru) Presto/2.8.119 Version/11.10"
    );
    $parse_url = parse_url($url);
    if(empty($ref)) $ref = "http://".(mt_rand(0,5)>2?"www":randomstring(mt_rand(3,4))).".".randomstring(mt_rand(3,7)).".ru/"; 
    else $ref = "http://".Host::$host;
    $curl = curl_init(); 
    curl_setopt($curl,CURLOPT_URL,$url); 
    if($method != 'DELETE'){
        curl_setopt($curl,CURLOPT_COOKIESESSION, TRUE); 
        curl_setopt($curl,CURLOPT_COOKIEFILE, "cookiefile_" . ( !empty($parse_url['query']) ? $parse_url['query'] : $parse_url['host'] ) ); 
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true); 
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
        if($method == 'POST'){
            curl_setopt($curl, CURLOPT_POST, true);
            if(!empty($data)) curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($data)));        
            if(!empty($body)) curl_setopt($curl, CURLOPT_POSTFIELDS, $body);        
        } 
        curl_setopt($curl,CURLOPT_REFERER,$ref); 
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,2); 
        curl_setopt($curl,CURLOPT_USERAGENT,$agents[mt_rand(0,count($agents)-1)]); 
    } else {
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");   
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);        
    }
    $result = curl_exec($curl);    
    if(curl_error($curl)) $error = 'error:' . curl_error($curl);
    if(empty($return_result_url)) return $result;
    $result = curl_getinfo($curl);
    return (!empty($result['url']) ? $result['url'] : false);
}

function utf8_str_split($str) { 
  // place each character of the string into and array 
  $split=1; 
  $array = []; 
  for ( $i=0; $i < strlen( $str ); ){ 
    $value = ord($str[$i]); 
    if($value > 127){ 
      if($value >= 192 && $value <= 223) 
        $split=2; 
      elseif($value >= 224 && $value <= 239) 
        $split=3; 
      elseif($value >= 240 && $value <= 247) 
        $split=4; 
    }else{ 
      $split=1; 
    } 
      $key = NULL; 
    for ( $j = 0; $j < $split; $j++, $i++ ) { 
      $key .= $str[$i]; 
    } 
    array_push( $array, $key ); 
  } 
  return $array; 
} 
/** 
 * Функция вырезки
 * @param <string> $str 
 * @return <string> 
 */ 
function clearstr($str){ 
        $sru = 'ёйцукенгшщзхъфывапролджэячсмитьбю<>divpabcdefghijklmnopqrstuvwxyz'; 
        $s1 = array_merge(utf8_str_split($sru), utf8_str_split(strtoupper($sru)), range('A', 'Z'), range('a','z'), range('0', '9'), array('&',' ','#',';','%','?',':','(',')','-','_','=','+','[',']',',','.','/')); 
        $codes = []; 
        for ($i=0; $i<count($s1); $i++){ 
                $codes[] = ord($s1[$i]); 
        } 
        $str_s = utf8_str_split($str); 
        for ($i=0; $i<count($str_s); $i++){ 
                if (!in_array(ord($str_s[$i]), $codes)){ 
                        $str = str_replace($str_s[$i], '', $str); 
                } 
        } 
        return $str; 
} 
function memoryUsage($usage, $base_memory_usage) {
    $bytes = $usage - $base_memory_usage;    
    if ($bytes >= 1073741824)
    {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    }
    elseif ($bytes >= 1048576)
    {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    }
    elseif ($bytes >= 1024)
    {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    }
    elseif ($bytes > 1)
    {
        $bytes = $bytes . ' bytes';
    }
    elseif ($bytes == 1)
    {
        $bytes = $bytes . ' byte';
    }
    else
    {
        $bytes = '0 bytes';
    }
    return "Bytes diff: ".$bytes;
}
function squareformat($value){
    return rtrim(rtrim(number_format($value,2, ".", ""), "0"), ".");
}
function get_http_response_code($url) {
    $headers = get_headers(trim($url));
    return substr($headers[0], 9, 3);
}
function arrayCombineToArrays($array, $limit = false) {
    // инициализируем пустым множеством
    $results = array([]);
    foreach ($array as $element){
        foreach ($results as $combination){
            $array = array_merge($combination,array($element));
            if(empty($limit) || count($array)<=$limit) array_push($results, array_merge($array));
        }
    }
    return $results;
}
?>
