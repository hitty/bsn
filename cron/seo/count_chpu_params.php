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
$db->querys("set names ".Config::$values['mysql']['charset']);
// вспомогательные таблицы
$sys_tables = Config::$sys_tables; 
$sys_tables['pages_seo'] = 'common.pages_seo';


// БЦв
//  business_centers sell   ?district_areas=30574&districts=5
$params = array('subways','districts','district_areas');
$result_array = array();
$arrr = pc_array_power_set($params);
//отсекаем метро, район 
foreach($arrr as $k=>$a){
        if(in_array('streets',$a)){
            if(in_array('subways',$a)) unset($a[array_search('subways', $a)]);
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } elseif(in_array('subways',$a)){
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } else if(in_array('districts',$a)){
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        }
        $result_array[] = $a;    
}

foreach($result_array as $arr){
    $sql_array = array();
    if(in_array('subways',$arr))        $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title, CONCAT('у метро ',title) as h1_title, CONCAT('метро ',title) as breadcrumbs FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
    if(in_array('districts',$arr))      $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title, CONCAT('в ',title_prepositional,' районе Санкт-Петербурга') as h1_title, CONCAT (title, ' район СПб') as breadcrumbs FROM ".$sys_tables['districts']." ");
    if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, CONCAT(title_prepositional,' районе Ленинградской области') as h1_title, CONCAT (offname, ' район ЛО') as breadcrumbs, id_area FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2 ");        
    if(in_array('streets',$arr))        $sql_array[] = $db->fetchall("SELECT 'streets' as type, id_street as id, CONCAT(shortname,' ',offname) as title, CONCAT('по адресу ',shortname,' ',offname) as h1_title, CONCAT(shortname,' ',offname) as breadcrumbs, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND a_level = 5");        
    
    $result = cartesian($sql_array);
    foreach($result as $k=>$item){
        $query_catalogs = 'business_centers';
        //параметры запроса для определения количества объектов
        $filled_query = array('published=1');
        //набор для ЧПУ и хлебных крошек
        $chpu = array();
        $chpu[] = array('commercial','Строящаяся');
        $chpu[] = array('business_centers','БЦ');
        $query = array();
        //наполнение h1 title
        $h1_title = array('БЦ');

        foreach($item as $k=>$values){
            //формирование параметров запроса
            if(!empty($values['type']) && !empty($values['id'])) $query[$values['type']] = $values['id'];
            //формирование ЧПУ и ХК
            if(empty($values['title'])) $chpu[] = array($values['type'],$values['breadcrumbs']);
            elseif(Validate::isDigit($values['title'])) $chpu[] =  array($values['type'].'-'.$values['title'],$values['breadcrumbs']);
            else $chpu[] = array($values['type'].'-'.createCHPUTitle($values['title']),$values['breadcrumbs']);
            //формирование ht title
            if(!empty($values['h1_title'])) $h1_title[] = $values['h1_title'];
            //поля для подсчета кол-ва объектов
            if($values['type']=='subways') $filled_query[] = 'id_subway = '.$values['id'];
            elseif($values['type']=='districts') $filled_query[] = 'id_district = '.$values['id']." AND id_region = 78";
            elseif($values['type']=='district_areas') $filled_query[] = 'id_area = '.$values['id_area']." AND id_region = 47"; 
            
        }
        if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
        $sql_chpu = $sql_breadcrumbs = array();
        foreach($chpu as $k_chpu=>$v_chpu){
           $sql_chpu[] = $v_chpu[0]; 
           $sql_breadcrumbs[] = $v_chpu[0].'=>'.$v_chpu[1]; 
        }
        
        $url_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",implode('/',$sql_chpu));
        if(!empty($url_id['id'])){
            $count = $db->fetch("SELECT COUNT(*) as filled, MAX(date_change) as lastmod_date FROM ".$sys_tables['business_centers']." WHERE ".implode(" AND ",$filled_query));
            if(!empty($count['lastmod_date'])) $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ?, lastmod_date = ? WHERE id=?", $count['filled'], $count['lastmod_date'], $url_id['id']);
            else $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ? WHERE id=?", $count['filled'], $url_id['id']);
            echo $db->last_query;
        }
    }
}        
// Коттеджные поселки
//  cottedzhnye_poselki sell   ?district_areas=30574&districts=5
$params = array('district_areas');
$result_array = array();
$arrr = pc_array_power_set($params);
//отсекаем метро, район 
foreach($arrr as $k=>$a) $result_array[] = $a;    

foreach($result_array as $arr){
    $sql_array = array();
    if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, old_id_lenobl as  id, offname as title, CONCAT(title_prepositional,' районе Ленинградской области') as h1_title, CONCAT (offname, ' район ЛО') as breadcrumbs, id_area FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2 AND old_id_lenobl > 0");        
        
    $result = cartesian($sql_array);
    foreach($result as $k=>$item){
        $query_catalogs = 'cottedzhnye_poselki';
        //параметры запроса для определения количества объектов
        $filled_query = array('id_stady=2');
        //набор для ЧПУ и хлебных крошек
        $chpu = array();
        $chpu[] = array('cottedzhnye_poselki','Коттеджные поселки');
        $query = array();
        //наполнение h1 title
        $h1_title = array('Коттеджные поселки');

        foreach($item as $k=>$values){
            //формирование параметров запроса
            if(!empty($values['type']) && !empty($values['id'])) $query[$values['type']] = $values['id'];
            //формирование ЧПУ и ХК
            if(empty($values['title'])) $chpu[] = array($values['type'],$values['breadcrumbs']);
            elseif(Validate::isDigit($values['title'])) $chpu[] =  array($values['type'].'-'.$values['title'],$values['breadcrumbs']);
            else $chpu[] = array($values['type'].'-'.createCHPUTitle($values['title']),$values['breadcrumbs']);
            //формирование ht title
            if(!empty($values['h1_title'])) $h1_title[] = $values['h1_title'];
            //поля для подсчета кол-ва объектов
            if($values['type']=='district_areas') $filled_query[] = 'id_district_area = '.$values['id'];
            
        }
        if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
        $sql_chpu = $sql_breadcrumbs = array();
        foreach($chpu as $k_chpu=>$v_chpu){
           $sql_chpu[] = $v_chpu[0]; 
           $sql_breadcrumbs[] = $v_chpu[0].'=>'.$v_chpu[1]; 
        }
      
        //запись кол-ва объектов
        $url_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",implode('/',$sql_chpu));
        if(!empty($url_id['id'])){
            $count = $db->fetch("SELECT COUNT(*) as filled, MAX(idate) as lastmod_date FROM ".$sys_tables['cottages']." WHERE ".implode(" AND ",$filled_query));
            if(!empty($count['lastmod_date'])) $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ?, lastmod_date = ? WHERE id=?", $count['filled'], $count['lastmod_date'], $url_id['id']);
            else $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ? WHERE id=?", $count['filled'], $url_id['id']);
            echo $db->last_query;
        }
    }
} 


// Жилые комплексыв
//  zhiloy_kompleks sell   ?district_areas=30574&districts=5
$params = array('subways','districts','district_areas');
$result_array = array();
$arrr = pc_array_power_set($params);
//отсекаем метро, район 
foreach($arrr as $k=>$a){
        if(in_array('streets',$a)){
            if(in_array('subways',$a)) unset($a[array_search('subways', $a)]);
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } elseif(in_array('subways',$a)){
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } else if(in_array('districts',$a)){
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        }
        $result_array[] = $a;    
}

foreach($result_array as $arr){
    $sql_array = array();
    if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title, CONCAT('у метро ',title) as h1_title, CONCAT('метро ',title) as breadcrumbs FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
    if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title, CONCAT('в ',title_prepositional,' районе Санкт-Петербурга') as h1_title, CONCAT (title, ' район СПб') as breadcrumbs FROM ".$sys_tables['districts']." ");
    if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, CONCAT(title_prepositional,' районе Ленинградской области') as h1_title, CONCAT (offname, ' район ЛО') as breadcrumbs, id_area FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2 ");        
    if(in_array('streets',$arr)) $sql_array[] = $db->fetchall("SELECT 'streets' as type, id_street as id, CONCAT(shortname,' ',offname) as title, CONCAT('по адресу ',shortname,' ',offname) as h1_title, CONCAT(shortname,' ',offname) as breadcrumbs, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND a_level = 5");        
    
    $result = cartesian($sql_array);
    foreach($result as $k=>$item){
        $query_catalogs = 'zhiloy_kompleks';
        //параметры запроса для определения количества объектов
        $filled_query = array('published=1');
        //набор для ЧПУ и хлебных крошек
        $chpu = array();
        $chpu[] = array('zhiloy_kompleks','Жилые комплексы');
        $query = array();
        //наполнение h1 title
        $h1_title = array('Жилые комплексы');

        foreach($item as $k=>$values){
            //формирование параметров запроса
            if(!empty($values['type']) && !empty($values['id'])) $query[$values['type']] = $values['id'];
            //формирование ЧПУ и ХК
            if(empty($values['title'])) $chpu[] = array($values['type'],$values['breadcrumbs']);
            elseif(Validate::isDigit($values['title'])) $chpu[] =  array($values['type'].'-'.$values['title'],$values['breadcrumbs']);
            else $chpu[] = array($values['type'].'-'.createCHPUTitle($values['title']),$values['breadcrumbs']);
            //формирование ht title
            if(!empty($values['h1_title'])) $h1_title[] = $values['h1_title'];
            //поля для подсчета кол-ва объектов
            if($values['type']=='subways') $filled_query[] = 'id_subway = '.$values['id'];
            elseif($values['type']=='districts') $filled_query[] = 'id_district = '.$values['id']." AND id_region = 78";
            elseif($values['type']=='district_areas') $filled_query[] = 'id_area = '.$values['id_area']." AND id_region = 47"; 
            
        }
        if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
        $sql_chpu = $sql_breadcrumbs = array();
        foreach($chpu as $k_chpu=>$v_chpu){
           $sql_chpu[] = $v_chpu[0]; 
           $sql_breadcrumbs[] = $v_chpu[0].'=>'.$v_chpu[1]; 
        }
        
        $url_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",implode('/',$sql_chpu));
        if(!empty($url_id['id'])){
            $count = $db->fetch("SELECT COUNT(*) as filled, MAX(date_change) as lastmod_date FROM ".$sys_tables['housing_estates']." WHERE ".implode(" AND ",$filled_query));
            if(!empty($count['lastmod_date'])) $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ?, lastmod_date = ? WHERE id=?", $count['filled'], $count['lastmod_date'], $url_id['id']);
            else $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ? WHERE id=?", $count['filled'], $url_id['id']);
            echo $db->last_query;
        }
    }
}        

//Зарубежка
$list = $db->fetchall("SELECT 
                            ".$sys_tables['foreign_countries'].".title as country_title
                            , ".$sys_tables['inter'].".id_country
                            , ".$sys_tables['foreign_countries'].".title_prepositional
                            , ".$sys_tables['foreign_countries'].".image_name
                            
                       FROM ".$sys_tables['inter']."
                       LEFT JOIN  ".$sys_tables['foreign_countries']." ON ".$sys_tables['foreign_countries'].".id = ".$sys_tables['inter'].".id_country
                       WHERE ".$sys_tables['foreign_countries'].".title_prepositional IS NOT NULL
                       GROUP BY id_country

");
foreach($list as $k=>$item){
    $name = explode('.',$item['image_name']);
    $name = $name[0];
    $db->querys("INSERT IGNORE INTO ".$sys_tables['pages_seo']." SET url=?, pretty_url=?, title=?, h1_title=?, breadcrumbs=?, keywords=?, description=?, seo_text=?",
        'inter/sell/?countries='.$item['id_country'], 
        'inter/sell/'.$name, 
        'Продажа зарубежной недвижимости в '.$item['title_prepositional'].' - BSN.ru',
        'Зарубежная недвижимость в '.$item['title_prepositional'],
        'inter=>Зарубежная,sell=>Продажа,'.$name.'=>'.$item['country_title'],
        'Здесь вы можете посмотреть список всех объектов зарубежной недвижимости в '.$item['title_prepositional'].' на портале БСН.ру',
        'Зарубежная недвижимость в '.$item['title_prepositional'].', Продажа зарубежной недвижимости в '.$item['title_prepositional'],
        'Здесь вы можете посмотреть список всех объектов зарубежной недвижимости в '.$item['title_prepositional'].' на портале БСН.ру'
    );
    
}
$deals = array(
    'sell'=>'Продажа',
    'rent'=>'Аренда'
);
$estate_types = array(
    'live'=>'жилой', 'build'=>'строящейся', 'country'=>'загородной', 'commercial'=>'коммерческой'
);


//Коммерческая
$params = array('obj_type','subways','districts','district_areas');
$result_array = array();
$arrr = pc_array_power_set($params);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
        if(in_array('subways',$a)){
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } else if(in_array('districts',$a)){
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        }
        $result_array[] = $a;    
}

$deal_types = array('sell','rent');
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        $sql_array = array();
        if(in_array('obj_type',$arr)) $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, new_alias as title, title_genitive_plural as h1_title, title_genitive_plural as breadcrumbs FROM ".$sys_tables['type_objects_commercial']." ");
        else  $sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>'','h1_title'=>'коммерческой недвижимости','breadcrumbs' => 'Коммерческая недвижимость'));
        if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title, CONCAT('у метро ',title) as h1_title, CONCAT('метро ',title) as breadcrumbs FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
        if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title, CONCAT('в ',title_prepositional,' районе Санкт-Петербурга') as h1_title, CONCAT (title, ' район СПб') as breadcrumbs FROM ".$sys_tables['districts']." ");
        if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, CONCAT(title_prepositional,' районе Ленинградской области') as h1_title, CONCAT (offname, ' район ЛО') as breadcrumbs, id_area FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2 ");        
        
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'commercial/'.$deal_type.'';
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('commercial','Коммерческая');
            if(!empty($item[0]['title'])) $chpu[] = array($deal_type.'/'.$item[0]['title'],($deal_type=='rent'?'Аренда ':'Продажа ').$item[0]['breadcrumbs']);
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();

            //наполнение h1 title
            $h1_title = array($deal_type=='rent'?'Аренда':'Продажа');
            foreach($item as $kk=>$it){
                if(in_array('obj_type',$it)) {
                    $h1_title[] = $it['h1_title']; 
                    $item[$kk]['h1_title'] = false;
                    break;
                }
            }

            foreach($item as $k=>$values){
                //формирование параметров запроса
                 if(!empty($values['type']) && !empty($values['id'])) $query[$values['type']] = $values['id'];
                //формирование ЧПУ
                if($k>0){
                    if(empty($values['title'])) $chpu[] = array($values['type'],$values['breadcrumbs']);
                    elseif(Validate::isDigit($values['title'])) $chpu[] =  array($values['type'].'-'.$values['title'],$values['breadcrumbs']);
                    else $chpu[] = array($values['type'].'-'.createCHPUTitle($values['title']),$values['breadcrumbs']);
                }
                //формирование ht title
                if(!empty($values['h1_title'])) $h1_title[] = $values['h1_title'];
                //поля для подсчета кол-ва объектов
                if($values['type']=='obj_type') $filled_query[] = 'id_type_object = '.$values['id'];
                elseif($values['type']=='subways') $filled_query[] = 'id_subway = '.$values['id'];
                elseif($values['type']=='districts') $filled_query[] = 'id_district = '.$values['id']." AND id_region = 78";
                elseif($values['type']=='district_areas') $filled_query[] = 'id_area = '.$values['id_area']." AND id_region = 47"; 
                
            }
            if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
            $sql_chpu = $sql_breadcrumbs = array();
            foreach($chpu as $k_chpu=>$v_chpu){
               $sql_chpu[] = $v_chpu[0]; 
               $sql_breadcrumbs[] = $v_chpu[0].'=>'.$v_chpu[1]; 
            }
            
        $url_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",implode('/',$sql_chpu));
        if(!empty($url_id['id'])){
                $filled_query[] = $deal_type=='rent'?'rent=1':'rent=2';
                $count = $db->fetch("SELECT COUNT(*) as filled, MAX(date_in) as lastmod_date FROM ".$sys_tables['commercial']." WHERE ".implode(" AND ",$filled_query));
                if(!empty($count['lastmod_date'])) $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ?, lastmod_date = ? WHERE id=?", $count['filled'], $count['lastmod_date'], $url_id['id']);
                else $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ? WHERE id=?", $count['filled'], $url_id['id']);
                echo $db->last_query;
            }
        }
    }        
}
// Строящаяся
//  build sell   ?district_areas=30574&districts=5&obj_type=1&rooms=2
// /элитность/тип_недвижимости/тип_объекта/тип_сделки/количество_комнат/посуточно/метро/район/район_ло/страна/с_фото/
$params = array('rooms','subways','districts','district_areas');
$result_array = array();
$arrr = pc_array_power_set($params);
//отсекаем метро, район 
foreach($arrr as $k=>$a){
        if(in_array('streets',$a)){
            if(in_array('subways',$a)) unset($a[array_search('subways', $a)]);
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } elseif(in_array('subways',$a)){
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        } else if(in_array('districts',$a)){
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
        }
        $result_array[] = $a;    
}

$deal_types = array('sell');
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        $sql_array = array();
        if(in_array('rooms',$arr)) $sql_array[] = array(
                                                            0=>array('type'=>'rooms','id'=>1,'title'=>1,'h1_title'=>'однокомнатных', 'breadcrumbs' => 'Однокомнатные'),
                                                            1=>array('type'=>'rooms','id'=>2,'title'=>2,'h1_title'=>'двухкомнатных', 'breadcrumbs' => 'Двухкомнатные'),
                                                            2=>array('type'=>'rooms','id'=>3,'title'=>3,'h1_title'=>'трехкомнатных', 'breadcrumbs' => 'Трехкомнатные'),
                                                            3=>array('type'=>'rooms','id'=>4,'title'=>4,'h1_title'=>'четырехкомнатных', 'breadcrumbs' => 'Четырехкомнатные'),
                                                        );
        if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title, CONCAT('у метро ',title) as h1_title, CONCAT('метро ',title) as breadcrumbs FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
        if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title, CONCAT('в ',title_prepositional,' районе Санкт-Петербурга') as h1_title, CONCAT (title, ' район СПб') as breadcrumbs FROM ".$sys_tables['districts']." ");
        if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, CONCAT(title_prepositional,' районе Ленинградской области') as h1_title, CONCAT (offname, ' район ЛО') as breadcrumbs, id_area FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2 ");        
        if(in_array('streets',$arr)) $sql_array[] = $db->fetchall("SELECT 'streets' as type, id_street as id, CONCAT(shortname,' ',offname) as title, CONCAT('по адресу ',shortname,' ',offname) as h1_title, CONCAT(shortname,' ',offname) as breadcrumbs, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND a_level = 5");        
        
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'build/'.$deal_type.'';
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('build','Строящаяся');
            $chpu[] = array('sell','Продажа квартир в новостройках');
            $query = array();
            //наполнение h1 title
            $h1_title = array('Продажа');
            foreach($item as $kk=>$it){
                if(in_array('rooms',$it)) {
                    $h1_title[] = $it['h1_title']; 
                    $item[$kk]['h1_title'] = false;
                    break;
                }
            }
            $h1_title[] = 'квартир в новостройках';

            foreach($item as $k=>$values){
                //формирование параметров запроса
                if(!empty($values['type']) && !empty($values['id'])) $query[$values['type']] = $values['id'];
                //формирование ЧПУ и ХК
                if(empty($values['title'])) $chpu[] = array($values['type'],$values['breadcrumbs']);
                elseif(Validate::isDigit($values['title'])) $chpu[] =  array($values['type'].'-'.$values['title'],$values['breadcrumbs']);
                else $chpu[] = array($values['type'].'-'.createCHPUTitle($values['title']),$values['breadcrumbs']);
                //формирование ht title
                if(!empty($values['h1_title'])) $h1_title[] = $values['h1_title'];
                //поля для подсчета кол-ва объектов
                if($values['type']=='obj_type') $filled_query[] = 'id_type_object = '.$values['id'];
                elseif($values['type']=='rooms') $filled_query[] = 'rooms_sale = '.$values['id'];
                elseif($values['type']=='subways') $filled_query[] = 'id_subway = '.$values['id'];
                elseif($values['type']=='districts') $filled_query[] = 'id_district = '.$values['id']." AND id_region = 78";
                elseif($values['type']=='district_areas') $filled_query[] = 'id_area = '.$values['id_area']." AND id_region = 47"; 
                elseif($values['type']=='streets') $filled_query[] = 'id_street = '.$values['id']." AND id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0";
                
            }
            if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
            $sql_chpu = $sql_breadcrumbs = array();
            foreach($chpu as $k_chpu=>$v_chpu){
               $sql_chpu[] = $v_chpu[0]; 
               $sql_breadcrumbs[] = $v_chpu[0].'=>'.$v_chpu[1]; 
            }
            
        $url_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",implode('/',$sql_chpu));
        if(!empty($url_id['id'])){
                $count = $db->fetch("SELECT COUNT(*) as filled, MAX(date_in) as lastmod_date FROM ".$sys_tables['build']." WHERE ".implode(" AND ",$filled_query));
                if(!empty($count['lastmod_date'])) $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ?, lastmod_date = ? WHERE id=?", $count['filled'], $count['lastmod_date'], $url_id['id']);
                else $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ? WHERE id=?", $count['filled'], $url_id['id']);
                echo $db->last_query;
            }
        }
    }        
}



//Загородная
$params = array('obj_type','district_areas');
$result_array = pc_array_power_set($params);

$deal_types = array('sell','rent');
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        $sql_array = array();
        
        if(in_array('obj_type',$arr)) $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, new_alias as title, title_genitive_plural as h1_title, title_genitive_plural as breadcrumbs FROM ".$sys_tables['type_objects_country']." ");
        else  $sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>'','h1_title'=>'загородной недвижимости','breadcrumbs' => 'Загородная недвижимость'));
        if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, CONCAT(title_prepositional,' районе Ленинградской области') as h1_title, CONCAT (offname, ' район ЛО') as breadcrumbs, id_area FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2 ");        
        
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'country/'.$deal_type.'';
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('country','Загородная');
            if(!empty($item[0]['title'])) $chpu[] = array($deal_type.'/'.$item[0]['title'],($deal_type=='rent'?'Аренда ':'Продажа ').$item[0]['breadcrumbs']);
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();
            //наполнение h1 title
            $h1_title = array($deal_type=='rent'?'Аренда':'Продажа');
            foreach($item as $kk=>$it){
                if(in_array('obj_type',$it)) {
                    $h1_title[] = 'загородных '.$it['h1_title']; 
                    $item[$kk]['h1_title'] = false;
                    break;
                }
            }

            foreach($item as $k=>$values){
                //формирование параметров запроса
                 if(!empty($values['type']) && !empty($values['id'])) $query[$values['type']] = $values['id'];
                //формирование ЧПУ
                if($k>0){
                    if(empty($values['title'])) $chpu[] = array($values['type'],$values['breadcrumbs']);
                    elseif(Validate::isDigit($values['title'])) $chpu[] =  array($values['type'].'-'.$values['title'],$values['breadcrumbs']);
                    else $chpu[] = array($values['type'].'-'.createCHPUTitle($values['title']),$values['breadcrumbs']);
                }
                //формирование ht title
                if(!empty($values['h1_title'])) $h1_title[] = $values['h1_title'];
                //поля для подсчета кол-ва объектов
                if($values['type']=='obj_type') $filled_query[] = 'id_type_object = '.$values['id'];
                elseif($values['type']=='district_areas') $filled_query[] = 'id_area = '.$values['id_area']." AND id_region = 47"; 
                
            }
            if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
            $sql_chpu = $sql_breadcrumbs = array();
            foreach($chpu as $k_chpu=>$v_chpu){
               $sql_chpu[] = $v_chpu[0]; 
               $sql_breadcrumbs[] = $v_chpu[0].'=>'.$v_chpu[1]; 
            }
            
        $url_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",implode('/',$sql_chpu));
        if(!empty($url_id['id'])){
                $filled_query[] = $deal_type=='rent'?'rent=1':'rent=2';
                $count = $db->fetch("SELECT COUNT(*) as filled, MAX(date_in) as lastmod_date FROM ".$sys_tables['country']." WHERE ".implode(" AND ",$filled_query));
                if(!empty($count['lastmod_date'])) $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ?, lastmod_date = ? WHERE id=?", $count['filled'], $count['lastmod_date'], $url_id['id']);
                else $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ? WHERE id=?", $count['filled'], $url_id['id']);
                echo $db->last_query;
            } 
        }
    }        
}

//  Жилая 
//  live sell   ?district_areas=30574&districts=5&obj_type=1&rooms=2
// /элитность/тип_недвижимости/тип_объекта/тип_сделки/количество_комнат/посуточно/метро/район/район_ло/страна/с_фото/

$params = array('obj_type','rooms','by_the_day','subways','districts','district_areas','streets');
$result_array = array();
$arrr = pc_array_power_set($params);
//отсекаем метро, район и район области
foreach($arrr as $k=>$a){
        if(in_array('subways',$a)){
            if(in_array('districts',$a)) unset($a[array_search('districts', $a)]);
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
            if(in_array('streets',$a)) unset($a[array_search('streets', $a)]);
        } elseif(in_array('districts',$a)){
            if(in_array('district_areas',$a)) unset($a[array_search('district_areas', $a)]);
            if(in_array('streets',$a)) unset($a[array_search('streets', $a)]);
        } else if(in_array('district_areas',$a)){
            if(in_array('streets',$a)) unset($a[array_search('streets', $a)]);
        }
        $result_array[] = $a;    
}
$deal_types = array('sell','rent');
foreach($deal_types as $deal_type){
    foreach($result_array as $arr){
        
        $sql_array = array();
        if(in_array('obj_type',$arr)) {
            $sql_array[] = $db->fetchall("SELECT 'obj_type' as type, id, new_alias as title, title_genitive_plural as h1_title, title_genitive_plural as breadcrumbs FROM ".$sys_tables['type_objects_live']."");
            if(in_array('rooms',$arr)) $sql_array[] = array(
                                                            0=>array('type'=>'rooms','id'=>1,'title'=>1,'h1_title'=>'однокомнатных', 'breadcrumbs' => 'Однокомнатные'),
                                                            1=>array('type'=>'rooms','id'=>2,'title'=>2,'h1_title'=>'двухкомнатных', 'breadcrumbs' => 'Двухкомнатные'),
                                                            2=>array('type'=>'rooms','id'=>3,'title'=>3,'h1_title'=>'трехкомнатных', 'breadcrumbs' => 'Трехкомнатные'),
                                                            3=>array('type'=>'rooms','id'=>4,'title'=>4,'h1_title'=>'четырехкомнатных', 'breadcrumbs' => 'Четырехкомнатные'),
                                                        );                                                        
        } elseif(in_array('rooms',$arr)){
            $sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>'','h1_title'=>'','breadcrumbs' => ''));
            if(in_array('rooms',$arr)) $sql_array[] = array(
                                                            0=>array('type'=>'rooms','id'=>1,'title'=>1,'h1_title'=>'1 комнаты', 'breadcrumbs' => 'Однокомнатные'),
                                                            1=>array('type'=>'rooms','id'=>2,'title'=>2,'h1_title'=>'2 комнат', 'breadcrumbs' => 'Двухкомнатные'),
                                                            2=>array('type'=>'rooms','id'=>3,'title'=>3,'h1_title'=>'3 комнат', 'breadcrumbs' => 'Трехкомнатные'),
                                                            3=>array('type'=>'rooms','id'=>4,'title'=>4,'h1_title'=>'4 комнат', 'breadcrumbs' => 'Четырехкомнатные'),
                                                        );                                                        
        }
        else  $sql_array[] = array(0=>array('type'=>false,'id'=>false,'title'=>'','h1_title'=>'жилой недвижимости','breadcrumbs' => 'Жилая недвижимость'));
        if(in_array('by_the_day',$arr) && $deal_type=='rent') $sql_array[] = array(0=>array('type'=>'by_the_day', 'id'=>1, 'h1_title'=>'на сутки', 'breadcrumbs'=>'На сутки'));
        if(in_array('subways',$arr)) $sql_array[] = $db->fetchall("SELECT 'subways' as type, id, title, CONCAT('у метро ',title) as h1_title, CONCAT('метро ',title) as breadcrumbs FROM ".$sys_tables['subways']." WHERE parent_id = 34142 ");
        if(in_array('districts',$arr)) $sql_array[] = $db->fetchall("SELECT 'districts' as type, id, title, CONCAT('в ',title_prepositional,' районе Санкт-Петербурга') as h1_title, CONCAT (title, ' район СПб') as breadcrumbs FROM ".$sys_tables['districts']."  ");
        if(in_array('district_areas',$arr)) $sql_array[] = $db->fetchall("SELECT 'district_areas' as type, id, offname as title, CONCAT(title_prepositional,' районе Ленинградской области') as h1_title, CONCAT (offname, ' район ЛО') as breadcrumbs, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 47 AND a_level = 2  ");        
        if(in_array('streets',$arr)) {
            if(empty($street_ids)){
                $streets_list = $db->fetchall("SELECT id_street FROM ".$sys_tables['live']." WHERE id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND id_street > 0 GROUP BY id_street");
                if(!empty($streets_list)){
                    $street_ids = array();
                    foreach($streets_list as $k=>$sid) $street_ids[] = $sid['id_street'];
                }
            }
            $sql_array[] = $db->fetchall("SELECT 'streets' as type, id_street as id, CONCAT(shortname,' ',offname) as title, CONCAT('по адресу ',shortname,' ',offname) as h1_title, CONCAT(shortname,' ',offname) as breadcrumbs, id_area  FROM ".$sys_tables['geodata']." WHERE  id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0 AND a_level = 5 AND id_street IN (".implode(', ',$street_ids).")");        
        }
        
        $result = cartesian($sql_array);
        foreach($result as $k=>$item){
            $query_catalogs = 'live/'.$deal_type.'';
            //параметры запроса для определения количества объектов
            $filled_query = array('published=1');
            //набор для ЧПУ и хлебных крошек
            $chpu = array();
            $chpu[] = array('live','Жилая');
            if(!empty($item[0]['title'])) {
                $chpu[] = array($deal_type.'/'.$item[0]['title'],($deal_type=='rent'?'Аренда ':'Продажа ').$item[0]['breadcrumbs']);
            }
            else $chpu[] = array($deal_type,$deal_type=='rent'?'Аренда':'Продажа');
            $query = array();
            //наполнение h1 title
            $h1_title = array($deal_type=='rent'?'Аренда':'Продажа');
            foreach($item as $kk=>$it){
                if(in_array('rooms',$it)) {
                    //костыль для N-комнатных комнт
                    if(!empty($item[0]['id']) && $item[0]['id']==2 ){
                        $h1_title[] = $it['id'].($it['id']==1?' комнаты':' комнат').' в квартире'; 
                        $item[$kk]['h1_title'] = false;
                        $item[0]['h1_title'] = false;
                    } else {
                        $h1_title[] = $it['h1_title']; 
                        $item[$kk]['h1_title'] = false;
                    }
                    break;
                }
            }
            foreach($item as $kk=>$it){
                if(in_array('obj_type',$it) && !empty($it['h1_title'])) {
                    $h1_title[] = $it['h1_title']; 
                    $item[$kk]['h1_title'] = false;
                    break;
                }
            }

            foreach($item as $k=>$values){
                //формирование параметров запроса
                if(!empty($values['type']) && !empty($values['id'])) $query[$values['type']] = $values['id'];
                //формирование ЧПУ
                if($k>0){
                    if(empty($values['title'])) $chpu[] = array($values['type'],$values['breadcrumbs']);
                    elseif(Validate::isDigit($values['title'])) $chpu[] =  array($values['type'].'-'.$values['title'],$values['breadcrumbs']);
                    else $chpu[] = array($values['type'].'-'.createCHPUTitle($values['title']),$values['breadcrumbs']);
                }
                //формирование ht title
                if(!empty($values['h1_title'])) $h1_title[] = $values['h1_title'];
                //поля для подсчета кол-ва объектов
                if($values['type']=='obj_type') $filled_query[] = 'id_type_object = '.$values['id'];
                elseif($values['type']=='rooms') $filled_query[] = 'rooms_sale = '.$values['id'];
                elseif($values['type']=='by_the_day') $filled_query[] = 'by_the_day = '.$values['id'];
                elseif($values['type']=='subways') $filled_query[] = 'id_subway = '.$values['id'];
                elseif($values['type']=='districts') $filled_query[] = 'id_district = '.$values['id']." AND id_region = 78";
                elseif($values['type']=='streets') $filled_query[] = 'id_street = '.$values['id']." AND id_region = 78 AND id_city=0 AND id_place=0 AND id_area=0";
                elseif($values['type']=='district_areas') $filled_query[] = 'id_area = '.$values['id_area']." AND id_region = 47"; 

            }
            if(!empty($query)) {ksort($query); $query_catalogs.='/?'.http_build_query($query, '', '&'); }
                 
            $sql_chpu = $sql_breadcrumbs = array();
            foreach($chpu as $k_chpu=>$v_chpu){
               $sql_chpu[] = $v_chpu[0]; 
               $sql_breadcrumbs[] = $v_chpu[0].'=>'.$v_chpu[1]; 
            }
           
        $url_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_seo']." WHERE pretty_url = ?",implode('/',$sql_chpu));
        if(!empty($url_id['id'])){
                $filled_query[] = $deal_type=='rent'?'rent=1':'rent=2';
                $count = $db->fetch("SELECT COUNT(*) as filled, MAX(date_in) as lastmod_date FROM ".$sys_tables['live']." WHERE ".implode(" AND ",$filled_query));
                if(!empty($count['lastmod_date'])) $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ?, lastmod_date = ? WHERE id=?", $count['filled'], $count['lastmod_date'], $url_id['id']);
                else $db->querys("UPDATE ".$sys_tables['pages_seo']." SET filled = ? WHERE id=?", $count['filled'], $url_id['id']);
                echo $db->last_query;
            }
           
        }
    }        
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