#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
$inter_root = DEBUG_MODE ? realpath("../..") : realpath('/home/interestate/sites/interestate.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "INTER_ROOT_PATH", $inter_root );
define( "ROOT_PATH", $root );
chdir($root);

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Valdate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
Session::Init();
Request::Init();
Cookie::Init(); 
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');


define('__XMLPATH__',INTER_ROOT_PATH.'//interestate_realty.xml');
define('__URL__','http://www.interestate.ru/');


$db->select_db('estate');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = '".Config::$values['mysql']['lc_time_names']."';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$log = array();

$liv_count=0; $build_count=0; $vil_count=0;

$db->select_db('inter_site');
$db->query('set names utf8');

$xml = new DOMDocument('1.0','UTF-8');

$xmlUrlset = $xml->appendChild($xml->createElement('root'));

//Init xml-making
$xmlItem = new generateXml;
$estates = '(1,13)';
$sql = "SELECT 
            `o`.*, 
            c.name as country
        FROM `inter_objects` o
        LEFT JOIN `inter_country` c ON c.id=o.id_country
        WHERE `o`.`actual`='Y' AND c.actual='Y' AND `cost`>0 AND `o`.`idate` < NOW() - INTERVAL 5 DAY AND `o`.`id_htype` NOT IN ".$estates." AND `o`.`id_curr` IN (2,3,4,23,8)
        ";
$res = $db->query($sql) or die($db->error);

while($row = $res->fetch_array())
{
    $good_photo=array();
    //если есть хотя бы 1 большая фотография - записывать в xml
    $photo_res = $db->query("(SELECT `pic` FROM `inter_pics` WHERE `id_object` = ".$row['id'].") UNION
                             (SELECT `pic` FROM `inter_plans` WHERE `id_object` = ".$row['id'].")") or die($db->error);
    while($photo_row = $photo_res->fetch_array(MYSQL_ASSOC)){
        if(file_exists(INTER_ROOT_PATH.'/object_pics/'.$photo_row['pic'])) {
            $size1 = getimagesize(INTER_ROOT_PATH.'/object_pics/'.$photo_row['pic']);
            if(!empty($size1[0]) && !empty($size1[1]) && $size1[0]>300 && $size1[1]>250)    $good_photo[] = $photo_row['pic'];
        } elseif(file_exists(INTER_ROOT_PATH.'/pics/'.$photo_row['pic'])) {
            
            $size2 = getimagesize(INTER_ROOT_PATH.'/pics/'.$photo_row['pic']);
             if($size2[0]>700) $ratio = 0.85;
             elseif($size2[0]>600) $ratio = 0.9;
             else $ratio = 0.95;
             $ratio = 1;
             if($size2[0]>350){
                    inter_img_resize(INTER_ROOT_PATH.'/pics/'.$photo_row['pic'], INTER_ROOT_PATH.'/object_pics/'.$photo_row['pic'], $size2[0]*$ratio, $size2[1]*$ratio, '', 'true', '0xFFFFFF');
                $size3 = getimagesize(INTER_ROOT_PATH.'/object_pics/'.$photo_row['pic']);
                if(!empty($size3[0]) && !empty($size3[1]) && $size3[0]>300 && $size3[1]>250)    $good_photo[] = $photo_row['pic'];
            }
        }
    }

    if(count($good_photo)>0){
        $xmlItem->append();
 
        $xmlItem->append('objectid', $row['id'],1); // * обязательное поле
        $xmlItem->append('type', $row['id_htype']==13?'rent':'sale',1);
        $xmlItem->append('market', $row['id_htype']==48 || $row['id_htype']==50?'primary':'secondary',1);
        $xmlItem->append('title', clean($row['address']),1); // * обязательное поле
        $xmlItem->append('description', clean($row['info']),1, $cdata=true);
        $xmlItem->append('price', $row['cost'],1);
        switch($row['id_curr']){
            case 3: $curr = 'usd'; break;
            case 4: $curr = 'eur'; break;
            case 23: $curr = 'chf'; break;
            case 8: $curr = 'gbp'; break;
            default: $curr = 'rur'; break;
        }
        $xmlItem->append('currency', $curr,1);
        //тип недвижимости
        $xmlItem->append('realty_type', getIdEstate($row['id_htype']),1);
        
        $country =  getIdCountry($row['country']);
        $xmlItem->append('region', $country>0?$country:$row['country'],1);
        //фотографии
        for($i=0; $i<(count($good_photo)>5?5:count($good_photo)); $i++) $xmlItem->append('photo', 'http://www.interestate.ru/object_pics/'.$good_photo[$i],1);
    }
}
 
$xml->formatOutput = true;
$xml->save(__XMLPATH__);
/*
if(file_exists(__XMLPATH__.".gz")) unlink(__XMLPATH__.".gz");
exec("gzip -rv ".__XMLPATH__);
exec("chmod 777 ".__XMLPATH__.".gz");
*/

class generateXml{
    private $item = 0;
    private $currentitem = '';
    private $subitem = '';
    private $checkitem = '';
    private $cdata = false;
    private $itemContent = array();

    public function __construct()
    {
        global $db, $xmlUrlset, $xml;
        $this->db=& $db;
        $this->xmlUrlset=& $xmlUrlset;
        $this->xml=& $xml;
    }
    public function append($child = false, $nodeText = false, $sub = false, $cdata = false)
    {
        if($child == false) {
            $this->currentitem = $this->xmlUrlset->appendChild($this->xml->createElement('object'));
            $this->itemContent[0] = array('object' => array($this->currentitem) );
        }
        elseif($sub > 0) {
            $key = array_keys($this->itemContent[($sub-1)]);
            $current = $this->itemContent[($sub-1)][$key[0]];
            $current = $current[0];
            $this->currentitem = $current->appendChild($this->xml->createElement($child));
            $this->itemContent[$sub] = array($child => array($this->currentitem) );
        }
        $this->cdata = $cdata;
        if($nodeText!=false) $this->setNode($nodeText);
    }
    /* Create node Text */
    private function setNode($nodeText = false)
    {
        if($nodeText!=false) {
            if(!empty($this->cdata)) $this->currentitem->appendChild($this->xml->createCDATASection($nodeText));
            else $this->currentitem->appendChild($this->xml->createTextNode($nodeText));
        }
    }
    public function attr($title, $value)
    {
        $this->currentitem -> setAttribute($title, $value);
    }

}

function inter_img_resize($src, $dest,$width,$height, $mode, $rotate='false', $rgb=0xFFFFFF)
{
        if (!file_exists($src)) return false;
        $size = getimagesize($src);
        if ($size === false) return false;

        $format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));

        $icfunc = "imagecreatefrom" . $format;
        $iwfunc = "imagejpeg";
        if (!function_exists($icfunc)) return false;
        if (!function_exists($iwfunc)) return false;
        
        $idest = imagecreatetruecolor($width, $height);

        $isrc = $icfunc($src);
        $rand = mt_rand(-10,10);
        if($rotate == 'true'){
            $degrees = $rand > 0 ? 0 : 0;
            $isrc = imagerotate($isrc, $degrees, $rgb);
        }       
        $rgb = imagecolorallocate($idest, 255, 255, 255);
        imagefill($idest, 0, 0, $rgb);
        imagecopy($idest, $isrc, 0, 0, $size[0]-$width, $size[1]-$height, $width, $height);
        $iwfunc($idest, $dest,90);
        imagedestroy($isrc);
        imagedestroy($idest);
        return true;
}
function getIdCountry($fild){
    $country = array(144 => 'Австралия', 25 => 'Австрия', 1065 => 'Азербайджан', 245 => 'Албания', 1391 => 'Андорра', 1355 => 'Антигуа и Барбуда', 208 => 'Аргентина', 203 => 'Армения', 180 => 'Барбадос', 189 => 'Белиз', 1354 => 'Бельгия', 28 => 'Болгария', 240 => 'Босния и Герцеговина', 182 => 'Бразилия', 119 => 'Великобритания', 145 => 'Венгрия', 190 => 'Вьетнам', 42 => 'Германия', 143 => 'Гондурас', 44 => 'Греция', 147 => 'Дания', 196 => 'Доминикана', 128 => 'Египет', 148 => 'Израиль', 149 => 'Индия', 207 => 'Индонезия', 150 => 'Ирландия', 51 => 'Испания', 81 => 'Италия', 156 => 'Кабо-Верде', 184 => 'Камбоджа', 157 => 'Канада', 235 => 'Кения', 73 => 'Кипр', 159 => 'Китай', 115 => 'Коста-Рика', 262 => 'Куба', 261 => 'Кыргызстан', 137 => 'Латвия', 160 => 'Литва', 241 => 'Маврикий', 206 => 'Малайзия', 197 => 'Мальта', 161 => 'Марокко', 179 => 'Мексика', 162 => 'Молдавия', 163 => 'Монако', 164 => 'Нидерланды', 165 => 'Новая Зеландия', 166 => 'Норвегия', 88 => 'ОАЭ', 169 => 'Оман', 114 => 'Панама', 170 => 'Польша', 121 => 'Португалия', 171 => 'Румыния', 1356 => 'Северные Марианские острова', 274 => 'Сейшельские острова', 205 => 'Сент-Киттс и Невис', 183 => 'Сербия', 172 => 'Сингапур', 140 => 'Словакия', 173 => 'Словения', 116 => 'США', 124 => 'Таиланд', 124 => 'Тайланд', 201 => 'Тунис', 178 => 'Турция', 132 => 'Украина', 572 => 'Уругвай', 273 => 'Филиппины', 131 => 'Финляндия', 17 => 'Франция', 100 => 'Хорватия', 103 => 'Черногория', 134 => 'Чехия', 139 => 'Швейцария', 175 => 'Швеция', 181 => 'Шри-Ланка', 176 => 'Эстония', 177 => 'ЮАР', 204 => 'Ямайка', 221 => 'Япония');
    if(in_array($fild,$country)) return array_search($fild,$country);
}
function clean($data)
{
    $data = str_replace('²','<sup>2</sup>', $data);
    $data = preg_replace("/(\r\n)+/i", "<br />", $data);
    $data = preg_replace('/\\r\\n?|\\n/', '<br />', $data);  
    $data=str_replace(array('\01','&#8470;','&#8364;','&#8211;','&#171;','&#187;','&#8226;','&#8221;','&#8222;','&#8220;','"'),array(''),$data);
    $data=str_replace('[/b]','',$data);
    $data=str_replace('[b]','',$data);
    $data=str_replace('&laquo;','',$data); 
    $data=str_replace('&raquo;','',$data); 
    $data=str_replace('[/u]','</u>',$data);
    $data=str_replace('[u]','<u>',$data);
    $data=str_replace('[/i]','</i>',$data);
    $data=str_replace('[i]','<i>',$data);    
    $data=str_replace('¸','',$data);     
    $data =  preg_replace("/\[url link=(.*)\](.*)\[\/url\]/i","<a href=\"$1\" target=\"_blank\">$2</a>",$data);
    $data = strip_tags($data,"<br><strong><b><ul><li><i><u><sup>");
    return $data;
}
function getIdEstate($id_htype){
    switch($id_htype){
        case 9:
        case 57:
            return 15; //– Земельные участки
            break;
        case 2:
        case 16:
        case 18:
        case 30:
        case 42:
        case 45:
        case 48:
        case 50:
        case 51:
        case 62:
        case 70:
        case 81:
        case 31:
        case 5:
            return 16; //– Апартаменты
            break;
        case 3:
        case 4:
        case 7:
        case 14:
        case 15:
        case 20:
        case 26:
        case 27:
        case 29:
        case 32:
        case 34:
        case 36:
        case 39:
        case 77:
        case 56:
        case 44:
        case 46:
        case 49:
        case 54:
        case 55:
        case 60:
        case 61:
        case 63:
        case 81:
            return 17; //– Виллы
            break;
        case 17:
        case 33:
        case 38:
        case 53:
            return 18; //- Таунхаузы
            break;
        default:
            return 14; //- Ком. недвижимость
            break;
    }
}

?>

