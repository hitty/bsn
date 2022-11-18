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
require_once('includes/class.paginator.php');
require_once('includes/class.estate.php');
require_once('includes/class.estate.statistics.php');
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;

define('__XMLPATH__',ROOT_PATH.'/elama_realty.xml');
define('__URL__','https://www.bsn.ru/');


$db->select_db('estate');
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = '".Config::$values['mysql']['lc_time_names']."';");

// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;

$xml = new DOMDocument('1.0','UTF-8');
$yml =  $xml->appendChild($xml->createElement('yml_catalog'));
$yml->setAttribute('date',date('c'));
$xmlUrlset = $yml->appendChild($xml->createElement('shop'));

//Init xml-making
$xmlItem = new xmlGenerate;

$live_count=0; $build_count=0; $commercial_count=0;$country_count=0;

$count = 100000;
$orderby = 'date_in DESC, date_change DESC';
$page = 1;
/* ЖИЛАЯ НЕДВИЖИМОСТЬ */
$estate = new EstateListLive();
$list = $estate->Search( $sys_tables['live'] . '.published=1', $count,0,$orderby);
//$list = array();
foreach($list as $k=>$item){
    $xmlItem->append();
    $xmlItem->attr("id",$item['id']);
    $xmlItem->attr("available",'true');

        $xmlItem->append('url', 'https://www.bsn.ru/live/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1);
        $xmlItem->append('price', $item['cost'], 1); 
        $xmlItem->append('currency', 'RUR', 1);
        $xmlItem->append('marketSegment', 'Жилая недвижимость', 1); 
        $xmlItem->append('dealType', $item['rent'] == 1 ? 'аренда' : 'продажа', 1); 
        $xmlItem->append('objectType', $item['type_object'],1); 
        $xmlItem->append('rooms', $item['rooms_total'],1); 
        if($item['id_type_object']==2) $xmlItem->append('rooms-offered', $item['rooms_sale'],1);
        $xmlItem->append('name', $item['header'],1);
        if(!empty($item['notes']))  $xmlItem->append('description', Convert::StripText($item['notes']), 1);
    
        $street = false;

        //определение названия улицы
        if(!empty($item['id_street'])){
            $street = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                  5, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']);
        }
        
        $xmlItem->append('country', 'Россия',1); // * обязательное поле
        if($item['id_region'] == 78){
            $xmlItem->append('region','Санкт-Петербург',1);
            if(!empty($item['district'])) $xmlItem->append('district',$item['district'],1);
            $xmlItem->append('address','',1);
            if(!empty($street['title'])) $xmlItem->attr('title',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : ''));
            elseif($item['txt_addr']!='') $xmlItem->attr('title',$item['txt_addr']);
            if(!empty($street['title']))  $xmlItem->append('street',$street['title'],2);
            if(!empty($item['house']))  $xmlItem->append('house',$item['house'],2);
            if(!empty($item['corp']))  $xmlItem->append('corp',$item['corp'],2);
            
        }
        elseif($item['id_region'] == 47){
            $xmlItem->append('region','Ленинградская область',1);
            if(!empty($item['id_area'])) $xmlItem->append('district',$item['district_area'],1);
            $xmlItem->append('address','',1);
            if(!empty($street['title'])) $xmlItem->attr('title',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : ''));
            elseif($item['txt_addr']!='') $xmlItem->attr('title',$item['txt_addr']);
            if(!empty($street['title']))  $xmlItem->append('street',$street['title'],2);
            if(!empty($item['house']))  $xmlItem->append('house',$item['house'],2);
            if(!empty($item['corp']))  $xmlItem->append('corp',$item['corp'],2);
        }
        
        if(!empty($item['subway'])) $xmlItem->append('metro',$item['subway'],1);
         
        $estateItem = new EstateItemLive($item['id']);
        $info = $estateItem->getInfo();

        if(!empty($info['building_type'])) $xmlItem->append('buildingType',$info['building_type'],1); //тип здания
        if(!empty($info['balcon']) && $item['id_balcon']!=8) $xmlItem->append('balcon',$info['balcon'],1); //балкон
        if(!empty($info['build_complete'])) $xmlItem->append('buildСomplete',$info['build_complete'],1); //срок сдачи
        if(!empty($info['developer_status'])) $xmlItem->append('developerStatus',$info['developer_status'],1); //статус застройщика
        if(!empty($info['toilet']) && $item['id_toilet']!=2 && $item['id_toilet']!=8) $xmlItem->append('toilet',$info['toilet'],1); //тип санузла
        if(!empty($info['elevator']) && ($item['id_elevator']==3 || $item['id_elevator']==4)) $xmlItem->append('elevator',$info['elevator'],1); //лифт
        if(!empty($info['facing'])) $xmlItem->append('facing',$info['facing'],1); //ремонт
        if(!empty($info['hot_water']) && $item['id_hot_water']!=5) $xmlItem->append('hotWater',$info['hot_water'],1); //горячая вода
        if(!empty($info['floor'])) $xmlItem->append('floorType',$info['floor'],1); //тип пола
        
    $live_count++;
}

/* СТРОЯЩАЯСЯ НЕДВИЖИМОСТЬ */
$estate = new EstateListBuild();
$list = $estate->Search( $sys_tables['build'] . '.published=1', $count, 0, $orderby );
//$list = array();
foreach($list as $k=>$item){
    $xmlItem->append();
    $xmlItem->attr("id",$item['id']);
    $xmlItem->attr("available",'true');

        $xmlItem->append('url', 'https://www.bsn.ru/build/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1);
        $xmlItem->append('price', $item['cost'], 1); 
        $xmlItem->append('currency', 'RUR', 1);
        $xmlItem->append('marketSegment', 'Строящаяся недвижимость', 1); 
        $xmlItem->append('dealType', 'продажа', 1); 
        $xmlItem->append('objectType', 'квартира',1); 
        $xmlItem->append('rooms', $item['rooms_sale'],1); 
        $xmlItem->append('name', $item['header'],1);
        if(!empty($item['notes']))  $xmlItem->append('description', Convert::StripText($item['notes']), 1);
    
        $street = false;

        //определение названия улицы
        if(!empty($item['id_street'])){
            $street = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                  5, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']);
        }
        
        $xmlItem->append('country', 'Россия',1); // * обязательное поле
        if($item['id_region'] == 78){
            $xmlItem->append('region','Санкт-Петербург',1);
            if(!empty($item['district'])) $xmlItem->append('district',$item['district'],1);
            $xmlItem->append('address','',1);
            if(!empty($street['title'])) $xmlItem->attr('title',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : ''));
            elseif($item['txt_addr']!='') $xmlItem->attr('title',$item['txt_addr']);
            if(!empty($street['title']))  $xmlItem->append('street',$street['title'],2);
            if(!empty($item['house']))  $xmlItem->append('house',$item['house'],2);
            if(!empty($item['corp']))  $xmlItem->append('corp',$item['corp'],2);
            
        }
        elseif($item['id_region'] == 47){
            $xmlItem->append('region','Ленинградская область',1);
            if(!empty($item['id_area'])) $xmlItem->append('district',$item['district_area'],1);
            $xmlItem->append('address','',1);
            if(!empty($street['title'])) $xmlItem->attr('title',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : ''));
            elseif($item['txt_addr']!='') $xmlItem->attr('title',$item['txt_addr']);
            if(!empty($street['title']))  $xmlItem->append('street',$street['title'],2);
            if(!empty($item['house']))  $xmlItem->append('house',$item['house'],2);
            if(!empty($item['corp']))  $xmlItem->append('corp',$item['corp'],2);
        }
        
        if(!empty($item['subway'])) $xmlItem->append('metro',$item['subway'],1);

        $estateItem = new EstateItemBuild($item['id']);
        $info = $estateItem->getInfo();
        
        if(!empty($info['building_type'])) $xmlItem->append('buildingType',$info['building_type'],1); //тип здания
        if(!empty($info['balcon']) && $item['id_balcon']!=8) $xmlItem->append('balcon',$info['balcon'],1); //балкон
        if(!empty($info['build_complete'])) $xmlItem->append('buildСomplete',$info['build_complete'],1); //срок сдачи
        if(!empty($info['developer_status'])) $xmlItem->append('developerStatus',$info['developer_status'],1); //статус застройщика
        if(!empty($info['toilet']) && $item['id_toilet']!=2 && $item['id_toilet']!=8) $xmlItem->append('toilet',$info['toilet'],1); //тип санузла
        if(!empty($info['elevator']) && ($item['id_elevator']==3 || $item['id_elevator']==4)) $xmlItem->append('elevator',$info['elevator'],1); //лифт
        if(!empty($info['facing'])) $xmlItem->append('facing',$info['facing'],1); //ремонт
    $build_count++;
}

/* КОММЕРЧЕСКА НЕДВИЖИМОСТЬ */
$estate = new EstateListCommercial();
$list = $estate->Search( $sys_tables['commercial'] . '.published=1', $count, 0, $orderby );
//$list = array();
foreach($list as $k=>$item){
    $xmlItem->append();
    $xmlItem->attr("id",$item['id']);
    $xmlItem->attr("available",'true');

        $xmlItem->append('url', 'https://www.bsn.ru/commercial/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1);
        $xmlItem->append('price', $item['cost'], 1); 
        $xmlItem->append('currency', 'RUR', 1);
        $xmlItem->append('marketSegment', 'Коммерческая недвижимость', 1); 
        $xmlItem->append('dealType', 'продажа', 1); 
        $xmlItem->append('objectType', $item['type_object'],1); 
        $xmlItem->append('name', $item['header'],1);
        if(!empty($item['notes']))  $xmlItem->append('description', Convert::StripText($item['notes']), 1);
    
        $street = false;

        //определение названия улицы
        if(!empty($item['id_street'])){
            $street = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                  5, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']);
        }
        
        $xmlItem->append('country', 'Россия',1); // * обязательное поле
        if($item['id_region'] == 78){
            $xmlItem->append('region','Санкт-Петербург',1);
            if(!empty($item['district'])) $xmlItem->append('district',$item['district'],1);
            $xmlItem->append('address','',1);
            if(!empty($street['title'])) $xmlItem->attr('title',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : ''));
            elseif($item['txt_addr']!='') $xmlItem->attr('title',$item['txt_addr']);
            if(!empty($street['title']))  $xmlItem->append('street',$street['title'],2);
            if(!empty($item['house']))  $xmlItem->append('house',$item['house'],2);
            if(!empty($item['corp']))  $xmlItem->append('corp',$item['corp'],2);
            
        }
        elseif($item['id_region'] == 47){
            $xmlItem->append('region','Ленинградская область',1);
            if(!empty($item['id_area'])) $xmlItem->append('district',$item['district_area'],1);
            $xmlItem->append('address','',1);
            if(!empty($street['title'])) $xmlItem->attr('title',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : ''));
            elseif($item['txt_addr']!='') $xmlItem->attr('title',$item['txt_addr']);
            if(!empty($street['title']))  $xmlItem->append('street',$street['title'],2);
            if(!empty($item['house']))  $xmlItem->append('house',$item['house'],2);
            if(!empty($item['corp']))  $xmlItem->append('corp',$item['corp'],2);
        }
        
        if(!empty($item['subway'])) $xmlItem->append('metro',$item['subway'],1);
        
        $estateItem = new EstateItemCommercial($item['id']);
        $info = $estateItem->getInfo();
        
        if(!empty($info['facing'])) $xmlItem->append('facing',$info['facing'],1); //отделка
        if(!empty($info['enter'])) $xmlItem->append('enter',$info['enter'],1); //вход в здание
        

    $commercial_count++;
}
/* ЗАГОРОДНАЯ НЕДВИЖИМОСТЬ */
$estate = new EstateListCountry();
$list = $estate->Search( $sys_tables['country'] . '.published=1', $count, 0, $orderby );
//$list = array();
foreach($list as $k=>$item){
    $xmlItem->append();
    $xmlItem->attr("id",$item['id']);
    $xmlItem->attr("available",'true');

        $xmlItem->append('url', 'https://www.bsn.ru/country/'.($item['rent']==2?'sell':'rent').'/'.$item['id'].'/',1);
        $xmlItem->append('price', $item['cost'], 1); 
        $xmlItem->append('currency', 'RUR', 1);
        $xmlItem->append('marketSegment', 'Загородная недвижимость', 1); 
        $xmlItem->append('dealType', 'продажа', 1); 
        $xmlItem->append('objectType', $item['type_object'],1); 
        $xmlItem->append('name', $item['header'],1);
        if(!empty($item['notes']))  $xmlItem->append('description', Convert::StripText($item['notes']), 1);
    
        $street = false;

        //определение названия улицы
        if(!empty($item['id_street'])){
            $street = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title 
                                  FROM ".$sys_tables['geodata']." 
                                  WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                  5, $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']);
        }
        
        $xmlItem->append('country', 'Россия',1); // * обязательное поле
        if($item['id_region'] == 78){
            $xmlItem->append('region','Санкт-Петербург',1);
            if(!empty($item['district'])) $xmlItem->append('district',$item['district'],1);
            $xmlItem->append('address','',1);
            if(!empty($street['title'])) $xmlItem->attr('title',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : ''));
            elseif($item['txt_addr']!='') $xmlItem->attr('title',$item['txt_addr']);
            if(!empty($street['title']))  $xmlItem->append('street',$street['title'],2);
            if(!empty($item['house']))  $xmlItem->append('house',$item['house'],2);
            if(!empty($item['corp']))  $xmlItem->append('corp',$item['corp'],2);
            
        }
        elseif($item['id_region'] == 47){
            $xmlItem->append('region','Ленинградская область',1);
            if(!empty($item['id_area'])) $xmlItem->append('district',$item['district_area'],1);
            $xmlItem->append('address','',1);
            if(!empty($street['title'])) $xmlItem->attr('title',$street['title'].($item['house']> 0 ? ($item['corp']> 0 ? ', д.'.$item['house'].', к.'.$item['corp'] : ', д.'.$item['house'] ) : ''));
            elseif($item['txt_addr']!='') $xmlItem->attr('title',$item['txt_addr']);
            if(!empty($street['title']))  $xmlItem->append('street',$street['title'],2);
            if(!empty($item['house']))  $xmlItem->append('house',$item['house'],2);
            if(!empty($item['corp']))  $xmlItem->append('corp',$item['corp'],2);
        }
        
        if(!empty($item['subway'])) $xmlItem->append('metro',$item['subway'],1);
        
        $estateItem = new EstateItemCountry($item['id']);
        $info = $estateItem->getInfo();
        
        if(!empty($info['ownership'])) $xmlItem->append('ownership',$info['ownership'],1); //владение
        if(!empty($info['construct_material'])) $xmlItem->append('constructMaterial',$info['construct_material'],1); //материал дома
        if(!empty($info['heating']) && $item['id_heating']>4) $xmlItem->append('heating',$info['heating'],1); //отопление
        if(!empty($info['roof_material']) && $item['id_roof_material']>3) $xmlItem->append('roofMaterial',$info['roof_material'],1); //материал крыши
        if(!empty($info['river']) && $item['id_roof_material']!=8 && $item['id_roof_material']!=3) $xmlItem->append('river',$info['river'],1); //водоем
        if(!empty($info['building_progress'])) $xmlItem->append('buildingProgress',$info['building_progress'],1); //материал крыши
        if(!empty($info['garden'])) $xmlItem->append('garden',$info['garden'],1); //материал крыши
        if(!empty($info['bathroom'])  && $item['id_bathroom']>2) $xmlItem->append('sauna',$info['bathroom'],1); //сауна

    $country_count++;
}
$xml->formatOutput = true;
$xml->save(__XMLPATH__);
if(file_exists(__XMLPATH__.".gz")) unlink(__XMLPATH__.".gz");
exec("gzip -rv ".__XMLPATH__);
exec("chmod 777 ".__XMLPATH__.".gz");

$html = '
<style>
    body, table, div {font-family:Calibri, "Palatino Linotype", "Book Antiqua", Palatino, serif;}
    table {font-size:15px; border-collapse:collapse;  table-layout:auto;}
    .mail_text td, .mail_text th { text-align:center; vertical-align:top;}
    .mail_small_text {text-align:left; font-size:9px!important; width:95%;}
</style>
<table width="99%" class="mail_text" cellpadding="2" cellspacing="0">
<tr><th>Варианты для elama-realty</th>';
$html .= "<tr><td>
- жилая: $live_count<br/>
- стройка: $build_count<br/>
- коммерческая: $commercial_count<br/>
- загородка: $country_count<br/>
</td></tr>
<tr><td><hr size=\"1\" width=\"100%\" color=\"#999999\" /></td></tr>";
$html .= "</table>";

$mailer = new EMailer('mail');

// перевод письма в кодировку мейлера
$html = iconv('UTF-8', $mailer->CharSet, $html);
// параметры письма
$mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Генерация фида в Elama');
$mailer->Body = $html;
$mailer->AltBody = strip_tags($html);
$mailer->IsHTML(true);
$mailer->AddAddress(Config::Get('emails/web'));     //отправка письма агентству
$mailer->From = 'elamabsnxml@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'Генерация фида в Elama');
// попытка отправить
$mailer->Send();        

class xmlGenerate{
    
    public $item = 0;
    public $currentitem = '';
    public $subitem = '';
    public $checkitem = '';
    
    public $itemContent = array();

    public function __construct()
    {
        global $db, $xmlUrlset, $xml;
        $this->db=& $db;
        $this->xmlUrlset=& $xmlUrlset;
        $this->xml=& $xml;
        
    }
    
    public function append($child = false, $nodeText = false, $sub = false)
    {
        if($child == false) {
            $this->currentitem = $this->xmlUrlset->appendChild($this->xml->createElement('offer'));
            $this->itemContent[0] = array('offer' => array($this->currentitem) );
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
        if($nodeText!=false) $this->currentitem -> appendChild($this->xml ->createTextNode($nodeText));
    }

    public function attr($title, $value)
    {
        $this->currentitem -> setAttribute($title, $value);
    }

}
?>
