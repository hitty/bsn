#!/usr/bin/php
<?php
$overall_time_counter = microtime(true);
set_time_limit(0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
// переход в корневую папку сайта
define('DEBUG_MODE', !empty($_SERVER['SERVER_NAME']) && preg_match('/.+\.int$/i', $_SERVER['SERVER_NAME']) ? true : false);
$root = DEBUG_MODE ? realpath("../..") : realpath('/home/bsn/sites/bsn.ru/public_html/' );
if(defined("PHP_OS")) $os = PHP_OS; else $os = php_uname();
if(strtolower(substr( $os, 0, 3 ) ) == "win" )  $root = str_replace( "\\", '/', $root );
if(!defined("ROOT_PATH"))define( "ROOT_PATH", $root );
chdir(ROOT_PATH);

$run_type = !empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;


    mb_internal_encoding('UTF-8');
    setlocale(LC_ALL, 'ru_RU.UTF-8');
    mb_regex_encoding('UTF-8');

    //запись всех ошибок в лог
    $error_log = ROOT_PATH.'/cron/news_parser/spam_error.log';
    //$test_performance = ROOT_PATH.'/cron/news_parser/test_performance.log';
    file_put_contents($error_log,'');
    //file_put_contents($test_performance,'');
    ini_set('error_log', $error_log);
    //для ручного запуска отключаем ошибки
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
    
    if( !class_exists( 'Photos' ) ) require_once('includes/class.photos.php');;        //для фоток
    require_once('includes/functions.php');          // функции  из крона
    // Инициализация рабочих классов
    $db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
    $db->query("set names ".Config::$values['mysql']['charset']);
    $db->query("SET lc_time_names = 'ru_RU';");
    require_once('includes/class.email.php');


    // вспомогательные таблицы модуля
    $sys_tables = Config::$sys_tables;

require_once('includes/simple_html_dom.php');    //класс для парсинга html


//список источников
$sources_list = $db->fetchall("SELECT * FROM ".$sys_tables['news_sources']." WHERE status = 1",'id');

//стоп-слова
$stopwords = $db->fetchall("SELECT title FROM ".$sys_tables['news_stopwords'],'title');
$stopwords = array_keys($stopwords);
$mail_text = "";

//parse_date("2016.1.2");

$log = array();


if(!empty($run_type)){
    ob_clean();
    $run_type = 2;
}else $run_type = 1;
//делаем запись в статистике
$db->query("INSERT INTO ".$sys_tables['news_parsing_stats']." (run_type,sources_ids) VALUES (".$run_type.",'".implode(',',array_keys($sources_list))."')");
$this_parsing_id = $db->insert_id;
$links_total = 0;
$links_total_added = 0;

foreach($sources_list as $source_key=>$source){
    //при начале удобнее просто прочитать, потому что нам нужны только ссылки
    if(get_http_response_code($source['url']) != "200") continue;
    else $html = file_get_contents($source['url']);
    
    $source_host = preg_replace('/(?<=\.[A-z]{2})\/.*$/','',$source['url']);
    $source['item_regular'] = explode('#',$source['item_regular']);
    foreach($source['item_regular'] as $k=>$title_regular){
        preg_match_all('/'.$title_regular.'/s'.((($source['encoding'] == 'UTF-8')?'u':'')).'i',trim($html),$links);
        if(empty($links)) break;
        $links = $links[0];
        if(is_array($links)) $html = implode(' ',$links);
    }
    if(empty($links)) continue;
    unset($html);
    
    //удаляем старые зависшие
    $db->query("DELETE FROM ".$sys_tables['news_parsing_stats']." WHERE end_datetime LIKE '%000%'");
    
    $db->query("UPDATE ".$sys_tables['news_sources']." SET last_view = NOW() WHERE id = ?",$source_key);
    
    //берем первые 5 ссылок - чтобы было только актуальное
    $links_amount = count($links);
    $links = array_slice($links,0,10);
    $counter = 0;
    foreach($links as $k=>$link){
        if(empty($link)) continue;
		
        if(get_http_response_code($source_host.$link) != "200"){
            if(get_http_response_code($source_host."/".$link) != "200") continue;
            else $item_html = file_get_html($source_host."/".$link);
        }
        else $item_html = file_get_html($source_host.$link);
        
        @list($content_selector,$head_selector,$text_selector,$date_selector,$article_image) = explode(',',$source['content_selector']);
        $article = $item_html->find($content_selector);
        $article_text = array();
        $article_head = $article_date = "";
        $article_date_formatted = "";
        $article_url = $source_host.$link;
        if(!empty($head_selector))
            foreach($item_html->find($head_selector) as $node){
                $article_head = mb_convert_encoding(strip_tags($node->innertext()),'UTF-8',$source['encoding']);
            }
        if(!empty($text_selector))
            $text_selector = explode('|',$text_selector);
            foreach($text_selector as $ts_key=>$selector){
                $article_element = $article[0];
                if(!empty($article[0]))
                    foreach($article[0]->find($selector) as $node){
                        $node_text = mb_convert_encoding(strip_tags($node->innertext()),'UTF-8',$source['encoding']);
                        
                        if(empty($article_date_formatted)) $article_date_formatted = parse_date($node_text);
                        //если даты нет или есть что-то кроме даты, пишем в текст
                        if(empty($article_date_formatted) || preg_match_all('/[А-я]/sui',$node_text,$matches) > 7) $article_text[] = $node_text;
                        
                    }
            }
            
        if(!empty($date_selector)){
            foreach($item_html->find($date_selector) as $node){
                $node_text = mb_convert_encoding(strip_tags($node->innertext()),'UTF-8',$source['encoding']);
                $article_date = $node_text;
            }
            if(!empty($article_date)) $article_date_formatted = parse_date($article_date);
        }
            
        $article_text = "<p>".implode('</p><p>',$article_text)."</p>";
        
        
        if(empty($article_head)) $article_head = mb_substr(strip_tags($article_text),0,50)."...";
        $article_to_insert = array('id_source'=>$source_key,'title'=>$article_head,'url'=>$article_url,'source_date'=>$article_date, 'text'=>$article_text);
        if(!empty($article_date_formatted)) $article_to_insert['source_date_parsed'] = $article_date_formatted;
        
        //проверяем на наличие стоп-слов, проверяем, вдруг такая уже есть
        if(check_article($article_head)){
            $exists = $db->fetch("SELECT id FROM ".$sys_tables['news_parsing']." WHERE url = ?",$article_url);
            if(empty($exists)){
                $db->insertFromArray($sys_tables['news_parsing'],$article_to_insert);
                $new_id = $db->insert_id;
                ++$counter;
                
                $db->query("UPDATE ".$sys_tables['news_sources']." SET articles_recieved = articles_recieved + 1 WHERE id = ?",$source_key);
                
                //ищем и при возможности добавляем картинки
                if(!empty($article_image)){
                    foreach($item_html->find($article_image) as $node)
                        preg_match_all('/(https?\:\/)?\/[A-z0-9\/\_\-\.]+\.(jpg|jpeg|png|gif)(?=[^A-z])/si',$node->innertext(),$images);
                        $images = (!empty($images)?$images[0]:array());
                }else $images = array();
                
                preg_match_all('/(https?\:\/)?\/[A-z0-9\/\_\-\.]+\.(jpg|jpeg|png|gif)(?=[^A-z])/si',$article_element->innertext(),$images_from_text);
                $images_from_text = (!empty($images_from_text)?$images_from_text[0]:array());
                
                $images = (!empty($images)?array_merge($images,$images_from_text):$images_from_text);
                //корректируем относительные ссылки
                
                if(!empty($images)){
                    foreach($images as $image_key=>$image_link){
                        if(!preg_match('/^https?.*/si',$image_link)) $image_link = $source_host.$image_link;
                        //Photos::Add('news_parsing',$new_id,NULL,$image_link,false,300,200,true);
                    }
                }
            }
        }
        
    }
    $log[] = $source['title']." ".$links_amount." ссылок всего, 10 взято, ".$counter." добавлено";
    $links_total += $links_amount;
    $links_total_added += $counter;
    
}

$log = implode('<br />',$log);

$db->query("UPDATE ".$sys_tables['news_parsing_stats']." SET end_datetime = NOW(), articles_readed = ?, articles_added = ? WHERE id = ?",$links_total,$links_total_added,$this_parsing_id);

if($run_type == 2){
    $ajax_result['message'] = 'Парсер успешно отработал';
    $ajax_result['ok'] = true;
    return;
}

$mailer = new EMailer('mail');
$mail_text = iconv('UTF-8', $mailer->CharSet, "Сбор новостей из источников на bsn.ru:<br />".$log);
if(!empty($data['subject'])) $mailer->Subject = iconv('UTF-8', $mailer->CharSet, "Сбор новостей из источников bsn.ru");
$mailer->Body = $mail_text;
$mailer->AltBody = strip_tags($mail_text);
$mailer->IsHTML(true);
$mailer->AddAddress('web@bsn.ru');
$mailer->From = 'no-reply@bsn.ru';
$mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
// попытка отправить
$mailer->Send();

function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}
function check_article($article_head,$article_text = false){
    global $stopwords;
    if(!empty($stopwords) && is_array($stopwords)) $stopwords = implode('|',$stopwords);
    preg_match('/'.$stopwords.'/sui',$article_head,$stopwords_matches_head);
    preg_match('/'.$stopwords.'/sui',$article_text,$stopwords_matches);
    return (empty($stopwords_matches_head) && empty($stopwords_matches));
}
function get_key_words(){
    global $db;
    global $sys_tables;
    $keywords = array();
    $district_areas = $db->fetchall("SELECT CONCAT(title,title_genitive,title_accusative,title_prepositional) AS words FROM ".$sys_tables['district_areas'],'id');
    foreach($district_areas as $k=>$item) array_merge($keywords,explode(',',$item['words']));
    //$tags = $db->fetchall("SELECT ")
    return false;
}
function parse_date($date_string){
    //13.05.2016 | 19:30
    //2016-05-30 12:33:46
    //06.05.2016 — 17:18
    //30 мая 2016
    //31.05.2016 11:53
    //30 мая 2016, 13:58
    if(empty($date_string)) return false;
    $date_string = trim(preg_replace('/[^0-9А-я\s\:\.\-\/]/sui','',$date_string));
    //читаем дату
    $year = "(20[0-9]{2}|[0-9]{2})";
    $month = "([0-9]{1,2}|[А-я]{3,6})";
    $day = "[0-9]{1,2}";
    $date_exploders = array('\\-','\\.','\\s','\\/');
    //$date_exploder = "(\\-|\\.|\\s|\\/)";
    foreach($date_exploders as $k=>$date_exploder){
        switch(true){
            case preg_match('/'.$day.$date_exploder.$month.$date_exploder.$year.'/sui',$date_string,$matches):
                if(preg_match('/[А-я]/sui',$matches[0])){
                    $month_found = preg_replace('/[^А-я]/sui','',$matches[0]);
                    $months_genitive = Config::$values['months_genitive'];
                    foreach(Config::$values['months_genitive'] as $m_num=>$m_title)
                        if($m_title == $month_found) $matches[0] = str_replace(' ','.',str_replace($month_found,$m_num,$matches[0]));
                }
                break;
            case preg_match('/'.$year.$date_exploder.$month.$date_exploder.$day.'/sui',$date_string,$matches):
                $matches[0] = implode('.',array_reverse(explode($date_exploder,$matches[0])));
                break;
        }
        if(!empty($matches[0])){
            $matches[0] = str_replace($date_exploder[1],'.',$matches[0]);
            break;
        }
    }
    if(empty($matches[0])) return false;
    //сразу переделываем в представление с ведущим нулем
    $formatted_datetime_str = preg_replace('/(?<=[^0-9]|^)[0-9]{1}(?=\.)/si','0$0',$matches[0]);
    
    //читаем время
    if(preg_match('/[0-9]{2}:[0-9]{2}(:[0-9]{2})?/si',$date_string,$matches) && !empty($matches[0])){
        $formatted_datetime_str .= " ".$matches[0];
        $datetime =  DateTime::createFromFormat('d.m.Y H:i:s',$formatted_datetime_str);
        if(empty($datetime)) $datetime =  DateTime::createFromFormat('d.m.Y H:i',$formatted_datetime_str);
    }
    else $datetime =  DateTime::createFromFormat('d.m.Y',$formatted_datetime_str);
    
    return (!empty($datetime)?$datetime->format("Y-m-d H:i:s"):false);
}
?>