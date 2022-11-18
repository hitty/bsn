#!/usr/bin/php
<?php
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);

$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir($root);

include('cron/robot/robot_functions.php');    // функции  из крона

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

if (is_running($_SERVER['PHP_SELF'])) die('Already running'); 

/**
* Обработка новых объектов
*/
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
$db->querys("set names ".Config::$values['mysql']['charset']);
require_once('includes/class.email.php');


define('__XMLPATH__',ROOT_PATH.'/xml/spbtalk_objects.xml');
define('__URL__','https://www.bsn.ru/');


$db->select_db('estate');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = '".Config::$values['mysql']['lc_time_names']."';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$log = array();
$xml = new DOMDocument('1.0','windows-1251');

$xmlUrlset = $xml->appendChild($xml->createElement('realty-feed'));
$xmlUrlset->setAttribute('xmlns','http://webmaster.yandex.ru/schemas/feed/realty/2010-06');

$xmlgenerationdate = $xmlUrlset->appendChild($xml->createElement('generation-date'));
$xmlgenerationdate-> appendChild($xml->createTextNode( date('c') ));

//Init xml-making
$xmlItem = new xmlGenerate;

$live_count=0; $build_count=0; $country_count=0;

/*
* ЖИЛАЯ НЕДВИЖИМОСТЬ
*/
//обшее условие для жилой
$where =   "(
                (
                    (".$sys_tables['live'].".cost) > 15000 AND ".$sys_tables['live'].".id_region = 78 AND ".$sys_tables['live'].".id_district IN (2,3,4,5,6,7,8,10,11,12,13,15,16)
                )
            ) AND 
            ".$sys_tables['live'].".rooms_total >=1  AND
            ".$sys_tables['live'].".cost>0 AND
            ".$sys_tables['live'].".square_full>0 AND
            ".$sys_tables['live'].".published = 1 AND
            ".$sys_tables['live'].".id_type_object = 1 AND
            ".$sys_tables['live'].".id_main_photo > 0 AND 
            ".$sys_tables['live'].".by_the_day = 2 AND
	    ".$sys_tables['live'].".id_user!=4764 AND
            DATEDIFF(CURDATE(),".$sys_tables['live'].".date_change) < 15 AND
            ".$sys_tables['live'].".rent = 1";

$sql = "SELECT 
            ".$sys_tables['live'].".*, 
            ".$sys_tables['districts'].".title as district_title, 
            ".$sys_tables['live_photos'].".name as photo_name,
            LEFT (".$sys_tables['live_photos'].".`name`,2) as `subfolder` 
        FROM ".$sys_tables['live']." 
        LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables['live'].".id_district 
        RIGHT JOIN ".$sys_tables['live_photos']." ON ".$sys_tables['live_photos'].".id = ".$sys_tables['live'].".id_main_photo
        WHERE 
            ".$where."  
        ORDER BY ".$sys_tables['live'].".cost ASC, ".$sys_tables['live'].".id DESC     
        LIMIT 100
";
$res = $db->fetchall($sql);

foreach($res as $k=>$item){
    foreach($item as $k=>$v) $item[$k] = trim($v);
    $xmlItem->append();
        $xmlItem->append('url', 'https://www.bsn.ru/live/rent/'.$item['id'].'/',1); // * обязательное поле
        $xmlItem->append('picurl', "https://www.bsn.ru/".Config::Get('img_folders/live')."/sm/".$item['subfolder']."/".$item['photo_name'],1);
        $xmlItem->append('region', $item['district_title'],1);
        $xmlItem->append('kkv', $item['rooms_total'],1); // * обязательное поле ДЛЯ ЖИЛОЙ НЕДВИЖИМОСТИ
        $xmlItem->append('so', number_format($item['square_full'],1,'.',' '),1); 
        $xmlItem->append('price', $item['cost'],1);
}

$xml->formatOutput = true;
$spbtalk_objects = $xml->saveXML(); // put string in spbtalk_objects
$spbtalk_objects = str_replace(array("<![CDATA[","]]>"),"",$spbtalk_objects);
if(file_exists(__XMLPATH__)) unlink(__XMLPATH__);
file_put_contents(__XMLPATH__,$spbtalk_objects);
exec("chmod 777 ".__XMLPATH__);

class xmlGenerate{
    
    public $item = 0;
    public $currentitem = '';
    public $subitem = '';
    public $checkitem = '';
    
    public $itemContent = array();

    public function __construct()
    {
        global $db, $xmlUrlset, $xml;
        $this->db=&$db;
        $this->xmlUrlset=& $xmlUrlset;
        $this->xml=&$xml;
        
    }
    
    public function append($child = false, $nodeText = false, $sub = false)
    {
        if($child == false) {
            $this->currentitem = $this->xmlUrlset->appendChild($this->xml->createElement('item'));
            $this->itemContent[0] = array('item' => array($this->currentitem) );
        } elseif($sub > 0) {
            $key = array_keys($this->itemContent[($sub-1)]);
            $current = $this->itemContent[($sub-1)][$key[0]];
            $current = $current[0];
            $this->currentitem = $current->appendChild($this->xml->createElement($child));
            $this->itemContent[$sub] = array($child => array($this->currentitem) );
        }
        if($nodeText!=false) $this->setNode($nodeText);
    }

    /* Create node Text */
    public function setNode($nodeText = false)
    {
        if($nodeText!=false) $this->currentitem -> appendChild($this->xml->createCDATASection(htmlspecialchars($nodeText)));
    }

    public function attr($title, $value)
    {
        $this->currentitem -> setAttribute($title, $value);
    }

}
?>
