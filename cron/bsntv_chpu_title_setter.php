#!/usr/bin/php
<?php
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
define( "ROOT_PATH", $root );
chdir(ROOT_PATH);


mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'ru_RU.UTF-8');
mb_regex_encoding('UTF-8');

// подключение классов ядра
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.storage.php');      // Session, Cookie, Responce, Request
include('includes/functions.php');          // функции  из модуля
include('includes/class.tags.php');          // функции  из модуля

include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);

$sys_tables = Config::$sys_tables;

$bsntv = $db->fetchall("
                    SELECT 
                        ".$sys_tables['news'].".id as news_id, 
                        ".$sys_tables['bsntv'].".id as bsntv_id,
                        ".$sys_tables['content_tags'].".title,
                        ".$sys_tables['news_tags'].".id_tag,
                        ".$sys_tables['news_tags'].".id as tags_id
                    FROM ".$sys_tables['news']." 
                    LEFT JOIN ".$sys_tables['news_tags']." ON ".$sys_tables['news_tags'].".id_object = ".$sys_tables['news'].".id
                    LEFT JOIN ".$sys_tables['bsntv']." ON ".$sys_tables['bsntv'].".old_articles_id = ".$sys_tables['news'].".id
                    LEFT JOIN ".$sys_tables['content_tags']." ON ".$sys_tables['content_tags'].".id = ".$sys_tables['news_tags'].".id_tag
                    WHERE ".$sys_tables['news'].".id_category = 32 AND ".$sys_tables['news_tags'].".id IS NOT NULL
                    GROUP BY ".$sys_tables['news_tags'].".id

");
foreach($bsntv as $k=>$item){
    $id_tag = Tags::addTag($item['title'], 3);
    Tags::linkTag($id_tag, $item['bsntv_id'], $sys_tables['bsntv_tags']);
    Tags::unlinkTag($item['id_tag'], $item['news_id'], $sys_tables['news_tags']);
}

$bsntv = $db->fetchall("
                    SELECT 
                        ".$sys_tables['news'].".id as news_id, 
                        ".$sys_tables['bsntv'].".id as bsntv_id,
                        ".$sys_tables['comments'].".id as comments_id
                    FROM ".$sys_tables['news']." 
                    LEFT JOIN ".$sys_tables['comments']." ON ".$sys_tables['comments'].".id_parent = ".$sys_tables['news'].".id AND ".$sys_tables['comments'].".parent_type = 1
                    LEFT JOIN ".$sys_tables['bsntv']." ON ".$sys_tables['bsntv'].".old_articles_id = ".$sys_tables['news'].".id
                    WHERE ".$sys_tables['news'].".id_category = 32 AND ".$sys_tables['comments'].".id IS NOT NULL
                    GROUP BY ".$sys_tables['comments'].".id

");
foreach($bsntv as $k=>$item){
    $db->querys("UPDATE ".$sys_tables['comments']." SET id_parent = ?, parent_type = 9 WHERE id = ?", $item['bsntv_id'], $item['comments_id']);
}

die();
$sys_tables = Config::$sys_tables;
$bsntv = $db->fetchall("SELECT * FROM ".$sys_tables['bsntv']." ORDER BY old_articles_id ASC");
foreach($bsntv as $k=>$item){
    $chpu_title = explode("_", $item['chpu_title']);
    $new_id = $k + 1;
    $chpu_title = array_replace($chpu_title, array( 0 => $new_id));
    $db->querys("UPDATE ".$sys_tables['bsntv']." SET id = ?, chpu_title = ? WHERE old_articles_id = ?", $new_id, implode("_", $chpu_title), $item['old_articles_id']);
    $db->querys("UPDATE ".$sys_tables['bsntv_photos']." SET id_parent = ? WHERE id = ?", $new_id, $item['id_main_photo']);
}

?>
