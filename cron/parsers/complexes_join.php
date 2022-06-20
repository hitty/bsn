#!/usr/bin/php
<?php
$overall_time_counter = microtime(true);
// переход в корневую папку сайта
$root = realpath("../..");
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);
include_once('cron/robot/robot_functions.php');    // функции  из крона
include_once('includes/functions.php');    // функции  из крона
// подключение классов ядра
require_once('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
require_once('includes/class.host.php');         // Host (вспомогательные данные по текущему хосту)
Host::Init();
require_once('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
require_once('includes/class.storage.php');      // Session, Cookie, Responce, Request
require_once('includes/class.db.mysqli.php');    // mysqli_db (база данных)
require_once('includes/class.robot.php');    // mysqli_db (база данных)
if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');     // Photos (работа с графикой)
 
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

session_start();
set_time_limit(0);
// Инициализация рабочих классов
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$query_not_log  = true;
$db->query("set names ".Config::$values['mysql']['charset']);
$db->query("SET lc_time_names = 'ru_RU';");
$sys_tables = Config::$sys_tables;
$dir = ROOT_PATH."/cron/parsers/complex_images/";
/*

//определение уникальных ЖК
$u_list = $db->fetchall("SELECT * FROM 2gis.ne WHERE title!='' AND title!='?' AND id>0");
foreach($u_list as $k=>$u_item){
    $list = $db->fetchall("SELECT * FROM 2gis.ne WHERE title LIKE '".$u_item['title']."%'");
    $data = array();
    foreach($list as $k=>$item){
        if(!empty($item['Очереди'])) $data['phases'][] = $item['Очереди'];
        if(!empty($item['Корпуса'])) $data['korpuses'][] = $item['Корпуса'];
        if(!empty($item['Срок сдачи'])) $data['build_complete'][] = $item['Срок сдачи'];
        if(!empty($item['Этажность'])) $data['floors'][] = $item['Этажность'];
    }
    $robot = new Robot(1);
    //определение адреса
    $robot->fields['id_area'] = $robot->fields['id_street'] = $robot->fields['id_city'] = $robot->fields['id_place'] = $robot->fields['id_district'] = $robot->fields['id_subway'] = 0;
    $robot->fields['house'] = $robot->fields['corp'] = '';
    if($u_item['Регион']=='ЛО'){
        $robot->fields['id_region'] = 47;
        $area = $db->fetch("SELECT `id_area` FROM ".$sys_tables['geodata']." WHERE a_level=2 AND id_region=47 AND offname=?",$u_item['Район']);
        $robot->fields['id_area'] = $area['id_area'];
    } else {
        $robot->fields['id_region'] = 78;
        $robot->fields['id_area'] = 0;
        //Район
        $district = $robot->getInfoFromTable($sys_tables['districts'],$u_item['Район'],'title');
        if(!empty($district)) $robot->fields['id_district'] = $district['id'];
    }
    //Метро
    $subway = $robot->getInfoFromTable($sys_tables['subways'],$u_item['Метро'],'bntxt_value');
    if(!empty($subway)) $robot->fields['id_subway'] = $subway['id'];
    
    $robot->getAddress($u_item['Адрес']);
    
    $db->query("INSERT INTO ".$sys_tables['housing_estates']." SET 
                    published = 1, 
                    title = ?,
                    id_region = ?,
                    id_area = ?,
                    id_city = ?,
                    id_place = ?,
                    id_street = ?,
                    id_district = ?,
                    house = ?,
                    corp = ?,
                    txt_addr = ?,
                    id_subway = ?,
                    developer = ?,
                    phases = ?,
                    korpuses = ?,
                    214_fz = ?,
                    build_complete = ?,
                    building_type = ?,
                    floors = ?,
                    low_rise = ?,
                    elite = ?,
                    declaration = ?,
                    playground = ?,
                    parking = ?,
                    security = ?,
                    lifts = ?,
                    service_lifts = ?,
                    site = ?,
                    forum = ?,
                    seller_phone = ?",
                    $u_item['title'],
                    $robot->fields['id_region'],
                    $robot->fields['id_area'],
                    $robot->fields['id_city'],
                    $robot->fields['id_place'],
                    $robot->fields['id_street'],
                    $robot->fields['id_district'],
                    $robot->fields['house'],
                    $robot->fields['corp'],
                    $u_item['Адрес'],
                    $robot->fields['id_subway'],
                    $u_item['Застройщик'],
                    !empty($data['phases']) ? implode(', ',$data['phases']) : '',
                    !empty($data['korpuses']) ? implode(', ',$data['korpuses']) : '',
                    $u_item['214 ФЗ !!! (1/0)']==1?1:2,
                    !empty($data['build_complete']) ? implode(', ',$data['build_complete']) : '',
                    $u_item['Тип дома'],
                    !empty($data['floors']) ? implode(', ',$data['floors']) : '',
                    $u_item['Малоэтажный (1/0)']==1?1:2,
                    $u_item['Элитный (1/0)']==1?1:2,
                    $u_item['Проектная декларация'],
                    $u_item['Детская площадка'],
                    $u_item['Паркинг'],
                    $u_item['Охрана (видео, консьерж)'],
                    $u_item['Количество лифтов'],
                    $u_item['Из них грузовых'],
                    $u_item['Сайт'],
                    $u_item['Форум дольщиков'],
                    $u_item['Телефон']
    ) or die($db->error);
}



$dh = opendir($dir);
while($pic_dir = readdir($dh))
{
    if($pic_dir!='.' && $pic_dir!='..' && !is_file($pic_dir))
    {
        $item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates']." WHERE id = ?",$pic_dir);        
        if(!empty($item)){
            echo $item['id']."\n";
            $pic_dh = opendir($dir.$pic_dir);
            while($images = readdir($pic_dh))
            {                                        
                $image_name = $dir.$pic_dir.'/'.$images;
                $new_image_name = $dir.$pic_dir.'/rotated-'.$images;
                $size = @getimagesize($image_name);
                if($images!='.' && $images!='..' && !empty($size)){
                    $info = pathinfo($image_name);
                    $extensions = array('jpg','jpeg','png','gif');
                    $extension = $info['extension'];
                    if(in_array(strtolower($extension), $extensions)){
                        rename($image_name, $dir.$pic_dir.'/'.randomstring(10).'.'.strtolower($extension));
                    } else unlink($image_name);
                }
            }
        }
    }
}
      die(); 
      */             
//флаг однократного обновления
$dh = opendir($dir);
while($pic_dir = readdir($dh))
{
    if($pic_dir!='.' && $pic_dir!='..' && !is_file($pic_dir))
    {
        $item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates']." WHERE id = ?",$pic_dir);        
        if(!empty($item)){
            echo $item['id']."\n";
            $pic_dh = opendir($dir.$pic_dir);
            while($images = readdir($pic_dh))
            {                                        
                $image_name = $dir.$pic_dir.'/'.$images;
                $new_image_name = $dir.$pic_dir.'/rotated-'.$images;
                $size = @getimagesize($image_name);
                if($images!='.' && $images!='..' && !empty($size)){
                    if($size[0]>600 && $size[1]>420){
                        $new_image = new Imagick(); 
                        $new_image->readImage($image_name); 
                        $new_image->rotateImage(new ImagickPixel('#FFFFFF'), 1.2); 
                        $new_image->writeImage($new_image_name);
                        $new_image->clear(); 
                        $new_image->destroy(); 
                        Photos::imageResize($new_image_name,ROOT_PATH.'/'.Config::$values['img_folders']['housing_estates'].'/'.$images,$size[0]*0.9, $size[1]*0.9,'cut_wo_resize');
                        unlink($new_image_name);
                        unlink($image_name);
                        Photos::$__folder_options=array(
                            'sm'=>array(110,82,'cut',85),
                            'med'=>array(540,405,'cut',85),
                            'big'=>array(800,600,'',70)
                        );// свойства папок для загрузки и формата фотографий
                       
                        Photos::Add('housing_estates', $item['id'], '', false, $images);
                    } else  unlink($image_name);            
                }
            }
        }
    }
}
/*


                    if($size[0]>600 && $size[1]>400){
                        $new_image = new Imagick(); 
                        $new_image->readImage($image_name); 
                        $new_image->rotateImage(new ImagickPixel('#FFFFFF'), 1.2); 
                        $new_image->writeImage($new_image_name);
                        $new_image->clear(); 
                        $new_image->destroy(); 
                        Photos::imageResize($new_image_name,ROOT_PATH.'/'.Config::$values['img_folders']['housing_estates'].'/'.$images,$size[0]*0.9, $size[1]*0.9,'cut_wo_resize');
                        unlink($new_image_name);
                        unlink($image_name);
                       
                        Photos::Add('housing_estates', $item['id'], '', false, $images);
                    } else  unlink($image_name);            
                }
            }
        }
    }
}
*/
function createCHPUTitle($title){
    $title = trim($title);
    $ru=array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ь','ы','ъ','э','ю','я',' ','.',',','-','"','/','\\');
    $en=array('a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya','_','','','','','','');
    $chpu_title = mb_strtolower($title, 'UTF-8');
    $chpu_title = str_replace($ru,$en,$chpu_title);
    return trim($chpu_title);
}
?>
