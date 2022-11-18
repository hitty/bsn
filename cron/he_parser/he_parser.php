#!/usr/bin/php
<?php
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

//запись всех ошибок в лог
$error_log = ROOT_PATH.'/cron/comagic/spam_error.log';
$test_performance = ROOT_PATH.'/cron/gen_sitemap/test_performance.log';
file_put_contents($error_log,'');
file_put_contents($test_performance,'');
ini_set('error_log', $error_log);
ini_set('log_errors', 'On');

// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.template.php');     // Template (шаблонизатор), FileCache (файловое кеширование)
require_once('includes/simple_html_dom.php');    //класс для парсинга html
require_once('includes/class.robot.php');        // класс с функциями робота, нужен для получения адреса
require_once('includes/functions.php');          // функции  из крона
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
$db->querys("SET lc_time_names = 'ru_RU';");



// вспомогательные таблицы модуля
$sys_tables = Config::$sys_tables;
$page_num = 1;
$robot = new BNTxtRobot(42379);
$robot->estate_type = 'build';
$total_counter = array();
while($page_num<84){
    // Create DOM from URL or file
    $html = file_get_html('http://www.novostroy.su/spb/buildings/search/q/f1c0c30518/?Building_page='.$page_num);
    
    //читаем информацию по объектам: название ЖК, адрес, метро, застройщик, срок сдачи
    $counter = 0;
    foreach($html->find('div[class="building-info"]') as $element){
        //название жилого комплекса (выкидываем "Жилой комплекс" и кавычки)
        $robot->fields['title'] = str_replace('»','',explode('«',html_entity_decode($element->parent()->parent()->children(0)->children(0)->children(0)->children(0)->innertext()))[1]);
        
        //район и регион
        if (preg_match('/(rayon_lo)/',$element->children(0)->children(1)->attr['href'])){
            $robot->fields['area'] = explode(' ',$element->children(0)->children(1)->innertext())[0];
            $robot->fields['id_region'] = 47;
        }else{
            $robot->fields['district'] = explode(' ',$element->children(0)->children(1)->innertext())[0];
            $robot->fields['id_region'] = 78;
        }
        //адрес (убираем лишние поля, которые уже прочитали, или которые не нужны)
        $element->children(0)->children(0)->outertext = '';
        $element->children(0)->children(1)->outertext = '';
        $element->children(0)->children(2)->outertext = '';
        
        $robot->fields['txt_addr'] = mb_substr(trim(explode('/',html_entity_decode(strip_tags($element->children(0)->innertext())))[0]),1,null,"UTF-8");
        //$robot->fields['txt_addr'] = preg_replace('/[^А-я\.\s,0-9]/sui','',$robot->fields['txt_addr']);
        $robot->fields['type_realty'] = 'жилая';
        //метро
        $robot->fields['subway'] = $element->children(1)->children(0)->innertext();
        //формируем данные по объекту
        $robot->getConvertedFields($robot->fields,"","","",TRUE);
        //определяем станцию метро напрямую, чтобы был поиск по title
        $robot->fields['id_subway'] = $robot->getInfoFromTable($robot->sys_tables['subways'],trim($robot->fields['subway']),'title')['id'];
        //дописываем в данные объекта сформированные поля
        
        //для срока сдачи id формировать не надо, для него текстовое поле
        if (!empty($element->parent()->children(2)->children))
            if (!empty($element->parent()->children(2)->children(1)->children))
                if (!empty($element->parent()->children(2)->children(1)->children(0)->children))
                    if (!empty($element->parent()->children(2)->children(1)->children(0)->children(1)->children)){
                        $robot->fields['build_complete'] = trim(preg_replace('/[^IV\d\s(кв)]/sui','',$element->parent()->children(2)->children(1)->children(0)->children(1)->children(0)->innertext()));
                    }   
        
        //застройщик - формируем как id_user, так и текстовое поле с названием застройщика
        $robot->fields['developer'] = preg_replace("/[«»]/sui","",trim($element->parent()->children(2)->children(0)->children(1)->innertext));
        $robot->fields['id_developer'] = $db->fetch("SELECT id FROM ".Config::$values['sys_tables']['housing_estate_developers']." WHERE title LIKE '%".$robot->fields['developer']."%'")['id'];
        $id_agency = $db->fetch("SELECT id FROM ".Config::$values['sys_tables']['agencies']." WHERE title LIKE '%".$robot->fields['developer']."%'")['id'];
        if (!empty($id_agency)){
            $robot->fields['id_user'] = $db->fetch("SELECT id FROM ".Config::$values['sys_tables']['users']." WHERE ".Config::$values['sys_tables']['users'].".id_agency=".$id_agency)['id'];
        }
        
        //ищем в базе этот объект: смотрим совпадение улицы, метро, района, нестрогое совпадение названия и девелопера
        //формируем условие сравнения
        $conditions = array();
        if (!empty($robot->fields['id_region'])) $conditions[] = Config::$values['sys_tables']['housing_estates'].".id_region = ".$robot->fields['id_region'];
        if (!empty($robot->fields['id_area'])) $conditions[] = Config::$values['sys_tables']['housing_estates'].".id_area = ".$robot->fields['id_area'];
        if (!empty($robot->fields['id_district'])) $conditions[] = Config::$values['sys_tables']['housing_estates'].".id_district = ".$robot->fields['id_district'];
        if (!empty($robot->fields['id_street'])) $conditions[] = Config::$values['sys_tables']['housing_estates'].".id_street = ".$robot->fields['id_street'];
        if (!empty($robot->fields['id_developer'])) $conditions[] = Config::$values['sys_tables']['housing_estates'].".id_developer = ".$robot->fields['id_developer'];
        else if (!empty($robot->fields['developer'])) $conditions[] = Config::$values['sys_tables']['housing_estates'].".developer LIKE '%".$robot->fields['developer']."%'";
        if (!empty($robot->fields['title'])) $conditions[] = Config::$values['sys_tables']['housing_estates'].".title LIKE '%".$robot->fields['title']."%'";
        $conditions = implode(" AND ",$conditions);
        $robot->fields['date_in'] = date("Y-m-d",time());
        $robot->fields['chpu_title'] = strtolower(str_replace(' ','_',trim(Convert::ToTranslit($robot->fields['title']))));
        
        $similar = $db->fetch("SELECT id,parsed FROM ".Config::$values['sys_tables']['housing_estates']." WHERE ".$conditions);
        //если ничего не нашли, добавляем в базу
        if (!$similar){
            $robot->fields['parsed'] = 1;
            foreach ($robot->fields as $key=>$item)
                if(empty($item)) unset($robot->fields[$key]);
            $robot->fields['date_change'] = date('Y-m-d h.j.s',time());
            $db->insertFromArray(Config::$values['sys_tables']['housing_estates'],$robot->fields);
            ++$total_counter['added'];
        }
        else{
            //если нашли, и это не спарсенный объект, помечаем, как имеющееся и у нас, и на novostroy.su
            if ($similar['parsed']!=1){
                $db->querys("UPDATE ".Config::$values['sys_tables']['housing_estates']." SET ".Config::$values['sys_tables']['housing_estates'].".parsed=3 WHERE id=".$similar['id']);
                ++$total_counter['existed'];
            }
        }
            
        $robot->fields = array();
        //$robot->estate_type="";
        ++$total_counter['total'];
        ++$counter;
    }
    
    ++$page_num;
    echo $page_num." page processed\r\n";
}
echo $total_counter['added']." added,
   ".$total_counter['existed']." existed already,
   ".$total_counter['total']." processed total
   from ".($page_num-1)." pages";
?>