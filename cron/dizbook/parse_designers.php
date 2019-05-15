#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
require_once('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');



// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/functions.php');          // функции  из модуля
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/simple_html_dom.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$extensions = array('png','jpg','jpeg','gif'); // File extensions

//дизайнеры с фотками
//определение страниц с фотками
/*
for($i=6; $i<=11; $i++){
    $url = curlThiss("http://www.architonic.com/pmdes/designer/0/".$i);
    file_put_contents($root."/cron/dizbook/designers_w_photo/".$i.".html",$url);
}

$dir = $root."/cron/dizbook/designers_w_photo/";
$dh = opendir($dir);
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..')
    {
        $html = file_get_html($dir.$filename);
        foreach($html->find('.nav_des_photo img') as $info){
            $title = $info->alt;
            $img_url = $info->src;
            $filename = randomstring(10);
            $imginfo = @pathinfo($img_url);
            if(!empty($imginfo)){
                foreach($extensions as $k=>$item) {
                    if(strstr($imginfo['extension'],$item)!='') {$imgextension =  $item; break;}
                }
                $picture = file_get_contents($img_url);
                file_put_contents($root."/cron/dizbook/photos/".$filename.'.'.$imgextension,$picture);
            }    

            $db->query("INSERT INTO dizbook.designers SET firstName=?, photoUrl=?",$title,$filename.'.'.$imgextension);
        }
    }
    
}



//выпарсивание стран
$dir = $root."/cron/dizbook/designers_w_photo/";
$dh = opendir($dir);
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..')
    {
        $html = file_get_html($dir.$filename);
        foreach($html->find('select.filter_script option') as $info){
            $url = $info->value;
            $name_eng = trim($info->plaintext);
            $db->query("INSERT INTO dizbook.countries SET name_eng=?, url=?",$name_eng,$url);
        }
    }
    
}

$list = $db->fetchall("SELECT * FROM dizbook.yii_country");
foreach($list as $k=>$item){
    $country = $db->fetch("SELECT * FROM dizbook.countries WHERE name = ?",trim($item['name']));
    if(empty($country)) $db->query("INSERT INTO dizbook.countries SET name=?,d_id=?",trim($item['name']),$item['id']);
    else $db->query("UPDATE dizbook.countries SET d_id=? WHERE id=?",$item['id'],$country['id']);
    $db->query("UPDATE dizbook.yii_country SET name=? WHERE id=?",trim($item['name']), $item['id']);
}

$list = $db->fetchall("SELECT * FROM dizbook.countries");
foreach($list as $k=>$item){
    $country = $db->fetch("SELECT * FROM dizbook.yii_country WHERE name = ?",trim($item['name']));
    if(empty($country)) {
        $db->query("INSERT INTO dizbook.yii_country SET name=?,created=CURDATE(),creatorId=1",trim($item['name']));
        $db->query("UPDATE dizbook.countries SET d_id=? WHERE id=?",$db->insert_id, $item['id']);
    }
}


$urls = $db->query("SELECT * FROM dizbook.countries WHERE url!=''");
foreach($urls as $k=>$url){
    
    getDesigners("http://www.architonic.com/".$url['url'], $url, $page=1);
}
function getDesigners($url, $data,$page){
    global $root;
    $html = curlThiss($url);
    file_put_contents($root."/cron/dizbook/designers_wo_photo/".strtolower(addslashes($data['name_eng']))."_".$page.".html",$html);
    
    preg_match_all("#\<a id\=\"right_arrow\" href\=\"(.*?)\"#sui",$html,$info); 
    if(!empty($info[1][0])) getDesigners($info[1][0], $data, ++$page);
}   
*/

$dir = $root."/cron/dizbook/designers_wo_photo/";
$dh = opendir($dir);
while($filename = readdir($dh))
{
    if($filename!='.' && $filename!='..')
    {
        $html = file_get_html($dir.$filename);
        $country = explode('_',$filename);
        $country = $db->fetch("SElECT id FROM dizbook.countries WHERE name_eng = ?",$country[0]);
        if(empty($country)) die($filename);
        $countryId = $country['id'];
        foreach($html->find('#sheet_content_inside li') as $info){
            $surname = $firstName = '';
            $name = trim($info->plaintext);
            $name = explode(',',$name);
            if(!empty($name[0])) $surname = trim($name[0]);
            if(!empty($name[1])) $firstName = trim($name[1]);
            $fullname = $firstName.' '.$surname;
            $reverse_fullname = $surname.' '.$firstName; 
            $designer = $db->fetch("SELECT * FROM dizbook.designers WHERE firstName = ? OR firstName = ?",$fullname,$reverse_fullname);
            if(!empty($designer)) $db->query("UPDATE dizbook.designers SET firstName=?, surname=?,countryId=? WHERE id=?",
                                              $firstName, $surname, $countryId, $designer['id']
            );
            else $db->query("INSERT INTO dizbook.designers SET firstName=?, surname=?,countryId=?",
                                              $firstName, $surname, $countryId
            );
            
        }
        
    }
}    
/*
//
$_dir = $root."cron/parsers/autoi/old_descr/";
$parsed = $db->fetchall("SELECT * FROM 2gis.autoi_cars WHERE id_submodel=0");
foreach($parsed as $pk=>$pb){
    $filename = $pb['car_id'].".html";
    if(filesize($_dir.$filename)>50000){
        $car_id = $pb['car_id'];
        $id_brand = $pb['id_brand'];
        $car_id = $pb['car_id'];
        $html = file_get_html($_dir.$filename);

        //субмодель
        foreach($html->find('.car_info_main div.car_info_main_row') as $submodel_info){
            $submodel_value = $submodel_info->find('h3', 0)->plaintext;
            if($submodel_value=='Комплектация'){
                    
                $submodel_title = $submodel_info->find('div', 0)->plaintext;
                $item = $db->fetch("SELECT * FROM 2gis.autoi_cars WHERE id_brand=? AND id_model=? AND title=?", $id_brand, $id_model, $submodel_title);
                if(empty($item)) {
                    $db->query("INSERT INTO 2gis.autoi_cars SET id_brand=?, id_model=?, title=?", $id_brand, $id_model, $submodel_title);
                    $id_submodel = $db->insert_id;
                } else {
                    $id_submodel = $item['id'];
                }
                $db->query("UPDATE 2gis.autoi_cars SET id_submodel=? WHERE id=?",$id_submodel, $pb['id']);
            }
        }
        
        //характеристики   div.car_page_features
        $charactreristics = $html->find('div.car_page_features',0);
        $html->clear();
        unset($html);        
        foreach($charactreristics->find('div.car_page_features_row') as $option){
            $sql_group = '';
            $title = trim($option->first_child()->plaintext," ,:");
            $sql_value = trim($option->last_child()->plaintext);
            
            $item = $db->fetch("SELECT * FROM 2gis.autoi_characteristic_types WHERE title=?", $title);
            if(empty($item)) {
                $db->query("INSERT INTO 2gis.autoi_characteristic_types SET id_brandtitle=?", $title);
                $id_type = $db->insert_id;
            } else {
                $id_type = $item['id'];
            }
            $db->query("INSERT INTO 2gis.autoi_characteristics SET `car_id`=?, `id_type`=?, `value`=?",
                        $car_id, $id_type, trim($sql_value) 
            );
                
        }
    }
}   
*/

function curlThiss($url){
    $ref = "http://".(mt_rand(0,5)>2?"www":randomstring(mt_rand(3,4))).".".randomstring(mt_rand(3,7)).".ru/"; 
    $curl = curl_init(); 
    curl_setopt($curl,CURLOPT_URL,$url); 
    curl_setopt($curl, CURLOPT_COOKIESESSION, TRUE); 
    curl_setopt($curl, CURLOPT_COOKIEFILE, "cookiefile"); 
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true); 
    curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
    curl_setopt($curl,CURLOPT_REFERER,$ref); 
    curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,30); 
    curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3"); 
    return curl_exec($curl);    
}


 ?>        