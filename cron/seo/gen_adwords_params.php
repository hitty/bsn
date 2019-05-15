#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');



// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
// вспомогательные таблицы
$sys_tables = Config::$sys_tables; 
$sys_tables['pages_seo'] = 'common.pages_seo';


 $list = $db->fetchall("SELECT * FROM ".$sys_tables['pages_seo']." WHERE seo_text!='' GROUP BY pretty_url, url ORDER BY id ASC");
 foreach($list as $k=>$item){
     $db->query("DELETE FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ? AND url = ? AND id != ? AND seo_text=''", $item['pretty_url'], $item['url'], $item['id']);
 }

$list = $db->fetchall("SELECT * FROM ".$sys_tables['pages_seo']." GROUP BY pretty_url, url ORDER BY id ASC");
foreach($list as $k=>$item){
 $db->query("DELETE FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ? AND url = ? AND id != ? ", $item['pretty_url'], $item['url'], $item['id']);
}
   die();                                                             
//  Жилая 
//  live sell   ?district_areas=30574&districts=5&obj_type=1&rooms=2
// /элитность/тип_недвижимости/тип_объекта/тип_сделки/количество_комнат/посуточно/метро/район/район_ло/страна/с_фото/

$params = array('obj_type','streets');
$result_array = array();
$arrr = pc_array_power_set($params);
foreach($arrr as $k=>$a) $result_array[] = $a;
$deal_types = array('sell','rent');
$res_name = "result_";
$res_error_name = "result_error_";

$line_counter = $line_err_counter = 0;
$file_counter = $file_err_counter = 1;
$new_file_needed = $new_err_file_needed = true; 
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        $sql_array = array();
        if(in_array('obj_type',$arr)) {
            $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, new_alias as title, title_genitive, title_genitive_plural as h1_title, title_genitive_plural as breadcrumbs FROM ".$sys_tables['type_objects_live']."");                                              
        }
        else  continue;//$sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>'','h1_title'=>'жилой недвижимости','breadcrumbs' => 'Жилая недвижимость'));
        
        if(in_array('streets',$arr)) {
            if(empty($street_ids)){
                $streets_list = $db->fetchall("SELECT id_street FROM ".$sys_tables['live']." WHERE id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND id_street > 0 GROUP BY id_street");
                if(!empty($streets_list)){
                    $street_ids = array();
                    foreach($streets_list as $k=>$sid) $street_ids[] = $sid['id_street'];
                }
            }
            $sql_array[] = $db->fetchall("SELECT 'streets' as type, id_street as id, CONCAT(shortname,' ',offname) as title, CONCAT(shortname_cut,(IF (shortname_cut NOT IN ('Площадь','Линия','Проезд','Участок'),'. ',' ')),offname) AS shortname, CONCAT('по адресу ',shortname,' ',offname) as h1_title, CONCAT(shortname,' ',offname) as breadcrumbs, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND a_level = 5 AND id_street IN (".implode(', ',$street_ids).")");        
        }
        
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'live/'.$deal_type.'';
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('estate','Недвижимость');
            $chpu[] = array('live','Жилая');
            if(!empty($item[0]['title'])) {
                $chpu[] = array($deal_type.'/'.$item[0]['title'],($deal_type=='rent'?'Аренда ':'Продажа ').$item[0]['breadcrumbs']);
            }
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();
            //наполнение h1 title
            $h1_title = array($deal_type=='rent'?'Аренда':'Продажа');
            foreach($item as $kk=>$it){
                if(in_array('obj_type',$it) && !empty($it['h1_title'])) {
                    $h1_title[] = $it['h1_title']; 
                    $item[$kk]['h1_title'] = false;
                    break;
                }
            }

            foreach($item as $k=>$values){

                //формирование ЧПУ
                if($k>0){
                    if(empty($values['title'])) $chpu[] = array($values['type'],$values['breadcrumbs']);
                    elseif(Validate::isDigit($values['title'])) $chpu[] =  array($values['type'].'-'.$values['title'],$values['breadcrumbs']);
                    else $chpu[] = array($values['type'].'-'.createCHPUTitle($values['title']),$values['breadcrumbs']);
                }
                //формирование ht title
                if(!empty($values['h1_title'])) $h1_title[] = $values['h1_title'];
            }
            if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
                 
            $sql_chpu = $sql_breadcrumbs = array();
            foreach($chpu as $k_chpu=>$v_chpu){
               $sql_chpu[] = $v_chpu[0]; 
               $sql_breadcrumbs[] = $v_chpu[0].'=>'.$v_chpu[1]; 
            }
            
            if (empty($item[1]['shortname']))
                continue;
            $csv_url = 'www.bsn.ru/'.implode('/',$sql_chpu);   // формирование url
            
            $csv_title = "";                         // формирование заголовка
            if (!empty($chpu[2][1]))
                $csv_title .= $chpu[2][1].'. '.mb_convert_case($item[1]['shortname'], MB_CASE_TITLE, "UTF-8");
            
            $csv_keywords = array();
            $keywords_template = array();
            if (empty($item[0]['type']) || !in_array($item[0]['title_genitive'],array('квартиры','комнаты')))     // если объект без типа или тип не нужный нам, то игнорируем запись
                continue;
            switch($item[0]['title_genitive']){
                case 'квартиры':
                   $keywords_template = array(  "продажа квартиры на",
                                                "куплю квартиру на",
                                                "продам квартиру на",
                                                "продажа жилья на",
                                                "продажа жилой недвижимости на",
                                                "покупка квартиры на",
                                                "недвижимость на",
                                                "жилая недвижимость на",
                                                "купить квартиру на",
                                                "квартиры на",
                                                "продажа квартиры",
                                                "куплю квартиру",
                                                "продам квартиру",
                                                "продажа жилья",
                                                "продажа жилой недвижимости",
                                                "покупка квартиры",
                                                "недвижимость",
                                                "жилая недвижимость",
                                                "купить квартиру",
                                                "квартиры"); 
                break;
                case 'комнаты':
                    $keywords_template = array( "продажа комнаты на",
                                                "куплю комнату на",
                                                "продам комнату на",
                                                "продажа жилой комнаты на",
                                                "покупка комнаты на",
                                                "жилая комната на",
                                                "купить комнату на",
                                                "комнаты на",
                                                "продажа комнаты",
                                                "куплю комнату",
                                                "продам комнату",
                                                "продажа жилой комнаты",
                                                "покупка комнаты",
                                                "жилая комната",
                                                "купить комнату",
                                                "комнаты");

                break; 
                default:
                    continue;
                break;   
            }
            $csv_advert = "База только актуальных предложений! Удобный поиск и фильтры. Консультации.";
            foreach($keywords_template as $ind=>$kw_template){
                $key_word = $kw_template." ". $item[1]['shortname'];    
                $csv_text = implode(';',array($csv_url,$csv_title,$csv_advert,$key_word))."\r\n";
                if ($line_counter%1500==0 && $new_file_needed){
                    $fp = fopen("result_".$file_counter.".csv", "w");
                                      
                }
                if ($line_err_counter%1500==0 && $new_err_file_needed){
                    $fp_err = fopen("result_error_".$file_err_counter.".csv", "w");
                    
                }
                if (mb_strlen($csv_title, 'utf-8')<=33){ 
                    fwrite($fp,$csv_text);
                    $line_counter++;
                    $new_file_needed = false;
                }
                else {
                    fwrite($fp_err,$csv_text);
                    $line_err_counter++;
                    $new_err_file_needed = false;
                }
                
                if ($line_counter%1500==0 && !$new_file_needed){
                    fclose($fp);
                    $file_counter++;
                    $new_file_needed = true;
                }
                if ($line_err_counter%1500==0 && !$new_err_file_needed){
                    fclose($fp_err);
                    $file_err_counter++;
                    $new_err_file_needed = true;
                }
            }
        }
        
    }        
}

echo "Completed! See result.csv";
                                                                       
function toFile($filename,$text) {
  ob_start();
  echo $text;
  file_put_contents($filename, ob_get_clean());
}

function pc_array_power_set($array) {
    // инициализируем пустым множеством
    $results = array(array());
    foreach ($array as $element)
    foreach ($results as $combination)
    array_push($results, array_merge($combination,array($element)));
    return $results;
}
function cartesian($input) {
    $result = array();

    while (list($key, $values) = each($input)) {
        // If a sub-array is empty, it doesn't affect the cartesian product
        if (empty($values)) {
            continue;
        }

        // Seeding the product array with the values from the first sub-array
        if (empty($result)) {
            foreach($values as $value) {
                $result[] = array($key => $value);
            }
        }
        else {
            // Second and subsequent input sub-arrays work like this:
            //   1. In each existing array inside $product, add an item with
            //      key == $key and value == first item in input sub-array
            //   2. Then, for each remaining item in current input sub-array,
            //      add a copy of each existing array inside $product with
            //      key == $key and value == first item of input sub-array

            // Store all items to be added to $product here; adding them
            // inside the foreach will result in an infinite loop
            $append = array();

            foreach($result as &$product) {
                // Do step 1 above. array_shift is not the most efficient, but
                // it allows us to iterate over the rest of the items with a
                // simple foreach, making the code short and easy to read.
                $product[$key] = array_shift($values);

                // $product is by reference (that's why the key we added above
                // will appear in the end result), so make a copy of it here
                $copy = $product;

                // Do step 2 above.
                foreach($values as $item) {
                    $copy[$key] = $item;
                    $append[] = $copy;
                }

                // Undo the side effecst of array_shift
                array_unshift($values, $product[$key]);
            }

            // Out of the foreach, we can add to $results now
            $result = array_merge($result, $append);
        }
    }

    return $result;
}

 ?>        