<?php

/**
* класс для всяких нужных методов для отслеживания робота на неиндексированных страницах
*/
abstract class CrawlerCatcher{
    //http://ajax.googleapis.com/ajax/services/search/web?v=1.0&filter=0&q=site:bsn.ru
    
    /**
    * проверяем что страница в стеке
    * 
    * @param mixed $bot_alias
    * @param mixed $url
    * @param mixed $check_active - флаг, проверять ли что страница не проиндексирована
    */
    private static function checkInStack( $bot_alias, $url = false, $check_active = false ){
        global $db;
        $sys_tables = Config::$sys_tables;
        
        if(empty($url)) $url = $_SERVER['REQUEST_URI'];
        $url = trim($url,'/');
        //ищем страницу
        $page_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]." WHERE url = ? ".(!empty($check_active)?" AND date_out = '0000-00-00 00:00:00'":""),$url);
        //если не нашли, пробуем подобрать страницу по pretty_url
        if(empty($page_id)){
            $page_id = $db->fetch("SELECT ".$sys_tables['pages_not_indexed_'.$bot_alias].".id 
                                   FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]."
                                   LEFT JOIN ".$sys_tables['pages_seo']." ON ".$sys_tables['pages_not_indexed_'.$bot_alias].".url = ".$sys_tables['pages_seo'].".url
                                   WHERE ".$sys_tables['pages_seo'].".pretty_url = ? ".(!empty($check_active)?" AND date_out = '0000-00-00 00:00:00'":""),$url);
            if(empty($page_id)) return false;
        }
        return $page_id['id'];
    }
    
    /**
    * проверяем что переход на страницу был из поисковой системы
    * 
    */
    public static function checkFromSearch(){
        global $db;
        $sys_tables = Config::$sys_tables;
        
        $page_ref = (!empty($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"");
        
        if(empty($page_ref)) return false;
        
        $bot_id = CrawlerCatcher::isCrawler();
        //если это робот, ничего не записываем
        if(!empty($bot_id)) return false;
        switch(true){
            case (strstr($page_ref,'https://www.google')):                
                $bot_alias = "google";
                break;
            case (strstr($page_ref,'https://www.yandex')):
                $bot_alias = "yandex";
                break;
            case (strstr($page_ref,'https://www.mail')):
                $bot_alias = "mailru";
                break;
        }
        if(empty($bot_alias)) return false;
        
        
        //пишем время перехода во все стеки
        $res = true;
        $crawlers = Config::$values['crawlers_aliases'];
        foreach($crawlers as $key=>$item){
            $page_id = CrawlerCatcher::checkInStack($item);
            $res =  $res && $db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$item]." SET ".$bot_alias."tm = NOW() WHERE id = ?",$page_id);
        }
        
        return $res;
    }
        
    /**
    * пишем переданную страницу в стек неиндексированных
    * 
    * @param mixed $url
    */
    public static function pageToStack( $url ) {
        global $db;
        $sys_tables = Config::$sys_tables;
        $page_url = trim($url,'/');
        $page_info = $db->fetch("SELECT title,description FROM ".$sys_tables['pages_seo']." WHERE url = '".$page_url."'");
        if(empty($page_info)) return "Страница не найдена в pages_seo, нельзя добавить.";
        else{
          $existing_id = $db->fetch("SELECT id FROM ".$sys_tables['pages_not_indexed']." WHERE url = ?",$page_url);
          if(!empty($existing_id))return $db->querys("UPDATE ".$sys_tables['pages_not_indexed']." SET url = ?,title = ?,description = ?,date_in = NOW(), bad_page = 2 WHERE id = ?",$page_url,$page_info['title'],$page_info['description'],$existing_id);
          else return $db->querys("INSERT INTO ".$sys_tables['pages_not_indexed']." 
                                    ( url, title, description, date_in ) VALUES ( ?, ?, ?, NOW() )", 
                                    $page_url, $page_info['title'], $page_info['description'] 
          );
        }
    }
    
    /**
    * проверяем что страница в индексе google
    * 
    */
    public static function checkGoogleIndex( $url = false,$host = false ) {
        $host = !empty($host) ? $host : $_SERVER['HTTP_HOST'];
        if( !empty( $url ) ) $google_result = file_get_contents( "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&filter=0&q=site:http://".$host.$url );
        else $google_result = file_get_contents( "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&filter=0&q=site:http://".$host.$_SERVER['REQUEST_URI'] );
        if(empty($google_result)) return false;
        else{
            $google_result = json_decode( $google_result );
            return !empty( $google_result->responseData->results ) && $google_result->responseStatus == 200;
        }
    }
    
    /**
    * определяем, робот ли это, если робот возвращаем данные по нему
    * 
    */
    private static function isCrawler(){
        global $db;
        $sys_tables = Config::$sys_tables;
        $bot_info = $db->fetch("SELECT 
                                    id_crawler AS id
                                FROM ".$sys_tables['crawlers_user_agents']."
                                WHERE ".$sys_tables['crawlers_user_agents'].".user_agent = ?", $_SERVER['HTTP_USER_AGENT']
        );
        return (empty($bot_info)?false:$bot_info['id']);
    }
    
    public static function checkPagesInGoogle($ids=false){
        global $db;
        $sys_tables = Config::$sys_tables;
        //читаем список всех страниц:
        if(!empty($ids)){
            $ids = array_map( "Convert::toInt" , explode( ',', $ids ) );
            $where = " WHERE id IN (".$ids.")";
        }else $where = "";
        $all_pages = $db->fetchall("SELECT id,url FROM ".$sys_tables['pages_not_indexed_google'].$where);
        foreach($all_pages as $key=>$item){
            $in_index = CrawlerCatcher::checkGoogleIndex("/".$item['url'],"www.bsn.ru");
            //если страница в индексе google, отмечаем в таблице
            $db->querys("UPDATE ".$sys_tables['pages_not_indexed_google']." SET in_index = ".(!empty($in_index)?1:2)." WHERE id = ?",$item['id']);
            echo $item['url'].(!empty($in_index)?"":" not")." in index"."\n";
        }
    }
    
    /**
    * пишем в базу заход на страницу и убираем из стека эту страницу, если страница в стеке, и отдаем список ссылок если нет
    * 
    */
    public static function processPageVisit(){
        global $db;
        $sys_tables = Config::$sys_tables;
        
        $bot_id = CrawlerCatcher::isCrawler();
        
        if(empty($bot_id)){
            //+новости, аналитика, аренда коммерческой, аренда офисов в спб, карта ЖК
            $res = $db->fetchall("SELECT CONCAT('/',pretty_url,'/') AS url,title,title AS text FROM ".$sys_tables['pages_seo']." WHERE id IN(26,25,14,42,66,194,364,211,203,373371) ORDER BY id ASC LIMIT 10");
            //при необходимости, записываем переход с поиска
            CrawlerCatcher::checkFromSearch();
        }
        else{
            $bot_alias = Config::$values['crawlers_aliases'][$bot_id];
            
            //проверяем что страница в активном стеке для этого робота
            $in_stack = CrawlerCatcher::checkInStack( $bot_alias, false, true );
            
            //если страница в стеке, выкидываем и записываем визит робота
            if(!empty($in_stack)) $db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$bot_alias]." SET date_out = NOW(), bot_visits_total = bot_visits_total + 1 WHERE id = ?",$in_stack);
            
            //возвращаем список ссылок для вставки (сначала выбираем те, которые еще не показывались (вообще и сегодня))
            $res = $db->fetchall("SELECT id,CONCAT('/',url,'/') AS url,title,title AS text FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]." WHERE date_out = '0000-00-00 00:00:00' AND has_shown = 2 AND shown_today = 0 LIMIT 10",'id');
            //если таких нет, показываем те которых не было сегодня
            if(empty($res)) $res = $db->fetchall("SELECT id,CONCAT('/',url,'/') AS url,title,title AS text FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]." WHERE date_out = '0000-00-00 00:00:00' AND shown_today = 0 LIMIT 10",'id');
            //если и таких нет, показываем просто какие-нибудь, смотря каких было меньше показано
            if(empty($res)) $res = $db->fetchall("SELECT id,CONCAT('/',url,'/') AS url,title,title AS text FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]." WHERE date_out = '0000-00-00 00:00:00' ORDER BY shown_total ASC LIMIT 10",'id');
            
            $links = implode(',',array_keys( $res ) );
            shuffle( $res );      
            
            //отмечаем на этих ссылках, что они показаны
            if(!empty($res)) $db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$bot_alias]." SET shown_today = shown_today + 1, shown_total = shown_total + 1 WHERE id IN (".$links.")");
            
            //записываем посещение
            $db->querys("INSERT INTO ".$sys_tables['pages_visits_'.$bot_alias."_day"]." (url,id_page_in_stack,visit_date) VALUES (?,?,NOW())",$_SERVER['REQUEST_URI'],(!empty($in_stack)?$in_stack:0));
        }
        
        Response::SetArray( 'crawler_links', $res );
        return $res;
    }
    
    /**
    * импорт из файла набора ссылок для робота
    * 
    * @param mixed $bot_alias
    * @param mixed $file_path
    */
    public static function importLinksFromFile($bot_alias,$file_path){
        global $db;
        $sys_tables = Config::$sys_tables;
        $rows = file($file_path);
        $errors_count = 0;
        $errors = [];
        
        $charsets = array("cp866","KOI8-R","WINDOWS-1251","CP1251","KOI8-RU", "ISO8859-5");
        $converted_charset = $charset_3 = false;
        for($i=0; $i<=4; ++$i){
            if(!empty($converted_charset)) break;
            if(!empty($rows[$i])){
                if(empty($converted_charset)) {
                    foreach($charsets as $charset){
                         $converted_row = iconv($charset,"UTF-8//TRANSLIT",$rows[$i]);
                         if(preg_match("#([а-я]{5,20})#is",$converted_row)){ $converted_charset = $charset; break; }
                    }
                }
            }
        }
        
        foreach($rows as $key=>$item){
            list($ankor,$url) = explode( ';', $item );
            $ankor = iconv($converted_charset,"UTF-8//TRANSLIT",$ankor);
            $url = trim($url);
            $url = trim($url,'/');
            if(!preg_match('/^[a-z0-9_\?\=\/]+$/siu',trim($url))) $errors[$key] = "Некорректный URL: строка ".$key.": '".$url."'";
            elseif(empty($ankor)) $errors[$key] = "Анкор не может быть пустым: строка ".$key;
            elseif($bot_alias == 'google' && !CrawlerCatcher::checkGoogleIndex( $url ) ) $errors[$key] = "Страница уже в индексе Google: строка ".$key.": '".$url."'";
            else $errors[$key] = "";
            
            if(!empty($errors[$key])) continue;
            else{
                $check_exists = $db->fetch("SELECT id FROM ".$sys_tables['pages_not_indexed_'.$bot_alias]." WHERE url = '".$url."'");
                if(!empty($check_exists)) $db->querys("UPDATE ".$sys_tables['pages_not_indexed_'.$bot_alias]." SET date_out = '0000-00-00 00:00:00', bad_page = 2");
                else $db->querys("INSERT INTO ".$sys_tables['pages_not_indexed_'.$bot_alias]." (url,title,description,date_in,date_out,bad_page) VALUES (?,?,?,NOW(),'0000-00-00 00:00:00',2)",$url,$ankor,$ankor);
            } 
        }
        return $errors;
    }
}
?>