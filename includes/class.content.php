<?php
require_once('includes/class.host.php');
class Content {
    protected $type = [];
    protected $tables = [];
    public function __construct($type){
        $this->tables = Config::$sys_tables;
        $this->type = $type;
        $this->table = $this->tables[$this->type];
        $this->table_categories = $this->tables[$this->type . '_categories'];
        if(!empty($this->tables[$this->type . '_regions'])) $this->table_regions = $this->tables[$this->type . '_regions'];
        $this->table_photos = $this->tables[$this->type . '_photos'];
        $this->table_comments = $this->tables['comments'];

        $this->table_promo = $this->tables['articles_promo'];
        $this->table_promo_photos = $this->tables['articles_promo_photos'];

        $this->table_longread_advert = $this->tables['longread_advert'];
        $this->table_longread_advert_photos = $this->tables['longread_advert_photos'];

        $this->table_test = $this->tables['articles_test'];
        $this->table_test_questions = $this->tables['articles_test_questions'];
        $this->table_test_answers = $this->tables['articles_test_answers'];
        $this->table_test_results = $this->tables['articles_test_results'];

        $this->table_partner = $this->tables['content_partners'];
        $this->table_partner_photos = $this->tables['content_partners_photos'];
        switch($this->type){
            case 'news' :       $this->comment_type = 1; break;
            case 'articles' :   $this->comment_type = 2; break;
            case 'bsntv' :      $this->comment_type = 9; break;
            case 'blog' :       $this->comment_type = 10; break;
            case 'doverie' :   $this->comment_type = 12; break;
        }
        
    }
    /**
    * получение списка объектов
    * @param string $table - таблица, содержащая объекты
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param integer $from - начиная с этого элемента
    * @param string $order - набор полей сортировки, как для SQL (напр. "datetime DESC, title ASC")
    * @param string $where - набор ограничений, как для SQL (напр. "YEAR(datetime)=2012 AND MONTH(datetime)=3")
    * @return array of arrays
    */
    public function getList($count = 0, $from = 0, $id_category = 0, $id_region = 0, $added_where = [], $order = 1){
        global $db;
        
        $order = empty($order) || Validate::isDigit($order) ? $this->makeOrderBy($order) : $order;
        $where = $this->whereClause($added_where, $id_category, $id_region);
        
        $sql = "SELECT ". $this->table .".*, 
                        IF(                
                            YEAR(". $this->table .".`datetime`) < Year(CURDATE()),
                                DATE_FORMAT(". $this->table .".`datetime`,'%e %M %Y'),
                                DATE_FORMAT(". $this->table .".`datetime`,'%e %M, %k:%i')
                        ) as normal_date, 
                        '" . $this->type . "' as `type`,
                        ".$this->table_categories.".code as category_code,
                        ".$this->table_categories.".title as category_title,
                        ".$this->table_categories.".title_genitive as category_title_genitive,
                        IF(". $this->table .".content_short = '', LEFT(". $this->table .".content,200), ". $this->table .".content_short) as `content_short`,
                        " . ( !empty($this->table_regions) ? "
                            ".$this->table_regions.".code as region_code,
                            ".$this->table_regions.".title as region_title, 
                            IF(".$this->table_categories.".id!=33, ".$this->table_regions.".title_genitive, '') as region_title_genitive, 
                        " : "" ) . "
                        ".$this->table_photos.".`name` as `photo`, LEFT (".$this->table_photos.".`name`,2) as `subfolder`,
                        ". $this->table .".`exclusive`,
                        ".$this->table_partner.".title as partner_title,
                        ".$this->table_partner.".site as partner_site,
                        '" . $this->type . "' as content_type,
                        ".$this->table_partner_photos.".`name` as `partner_photo`, LEFT (".$this->table_partner_photos.".`name`,2) as `partner_subfolder`,
                        (SELECT COUNT(*) FROM ".$this->table_comments." WHERE comments_active = 1 AND id_parent = ". $this->table .".id AND parent_type = ".$this->comment_type.") as comments_count
                FROM ". $this->table ."
                LEFT JOIN ".$this->table_categories." ON ".$this->table_categories.".id = ". $this->table .".id_category
                " . ( !empty($this->table_regions) ? "LEFT JOIN ".$this->table_regions." ON ".$this->table_regions.".id = ".$this->table.".id_region" : "" ) . "
                LEFT JOIN ".$this->table_photos." ON ".$this->table_photos.".id = ". $this->table .".id_main_photo 
                LEFT JOIN ".$this->table_partner." ON ".$this->table_partner.".id = ". $this->table .".id_partner
                LEFT JOIN ".$this->table_partner_photos." ON ".$this->table_partner_photos.".id = ". $this->table_partner .".id_main_photo 
                
                ";
        if(!empty($where)) $sql .= " WHERE ".$where;
        if(!empty($order)) $sql .= " ORDER BY ".$order;
        if(!empty($count)) $sql .= " LIMIT ".$from.",".$count;
        $list = $db->fetchall($sql);  
        if(empty($list)) return [];
        return $list;
    }
      
    
    /**
    * получение объекта по его ID
    * @param integer $chpu_title - ЧПУ объекта
    * @return array
    */
    public function getItem($chpu_title = false, $id = false){
        global $db;
        $sql = "SELECT ". $this->table .".*,
                        IF(
                            YEAR(". $this->table .".`datetime`) < Year(CURDATE()),
                                DATE_FORMAT(". $this->table .".`datetime`,'%e %b %Y'),
                                DATE_FORMAT(". $this->table .".`datetime`,'%e %b, %k:%i')
                        ) as normal_date, 
                        ".$this->table_categories.".code as category_code,
                        ".$this->table_categories.".title as category_title,
                        ".$this->table_categories.".title_genitive as category_title_genitive,
                        ". $this->table .".content_short = '',
                        " . ( !empty($this->table_regions) ? "
                            ".$this->table_regions.".code as region_code,
                            ".$this->table_regions.".title as region_title,
                            ".$this->table_regions.".title_genitive as region_title_genitive,
                        " : "" ) . "
                        ".$this->table_photos.".`name` as `photo`, LEFT (".$this->table_photos.".`name`,2) as `subfolder`,
                        ".$this->table_partner.".title as partner_title,
                        ".$this->table_partner.".site as partner_site,
                        ".$this->table_partner_photos.".`name` as `partner_photo`, LEFT (".$this->table_partner_photos.".`name`,2) as `partner_subfolder`,
                        
                        (SELECT COUNT(*) FROM ".$this->table_comments." WHERE comments_active = 1 AND id_parent = ". $this->table .".id AND parent_type = ".$this->comment_type.") as comments_count
                FROM ". $this->table ."
                LEFT JOIN ".$this->table_categories." ON ".$this->table_categories.".id = ".$this->table.".id_category
                " . ( !empty($this->table_regions) ? "LEFT JOIN ".$this->table_regions." ON ".$this->table_regions.".id = ".$this->table.".id_region" : "" ) . "
                LEFT JOIN ".$this->table_photos." ON ".$this->table_photos.".id = ". $this->table .".id_main_photo
                LEFT JOIN ".$this->table_partner." ON ".$this->table_partner.".id = ". $this->table .".id_partner
                LEFT JOIN ".$this->table_partner_photos." ON ".$this->table_partner_photos.".id = ". $this->table_partner .".id_main_photo 

                ";
        $sql .= " WHERE " . $this->table . ( !empty($chpu_title) ? ".chpu_title=?" : ".id=?" );
        $row = $db->fetch($sql, !empty($chpu_title) ? $chpu_title : $id );
        return $row;
    }
    /**
    * получение списков категорий и регионов
    * @param string $order - набор полей сортировки
    * @return array
    */
    public function getSimpleList($table, $order, $where=''){
        global $db;
        $sql = "SELECT * FROM " . $table;
        if(!empty($where)) $sql .=" WHERE ".$where;
        $sql .= " ORDER BY ".$order;
        $row = $db->fetchall($sql);
        return $row;
    }   
     /**
    * получение списков категорий и регионов с подсчетом статей
    * @param string $order - набор полей сортировки
    * @return array
    */
    public function getListCount($table, $order, $where=''){
        global $db;
        $row = $db->fetchall("
            SELECT COUNT(*) as news_count, ". $table .".* 
            FROM ".$this->table." 
            RIGHT JOIN ". $table ." ON ". $table .".id = ".$this->table.".id_category
            WHERE ".$this->table.".status!=4 ".(!empty($where) ? " AND " . $where : "" ) ."
            GROUP BY ". $table .".id
            ORDER BY ".$order
            
        );
        return $row;
    }   
    /**
    * получение тегов для объекта
    * @param integer $id - ID объекта
    * @return array of arrays
    */
    public function getItemTags($id){
        global $db;
        $sql = "SELECT * FROM ".Config::$sys_tables['content_tags'];
        $sql .= " WHERE id IN (SELECT id_tag FROM ". $this->table ." WHERE id_object=?)";
        $rows = $db->fetchall($sql,$id);
        if(empty($rows)) return [];
        return $rows;
    }
    /**
    * получение условий для выборки
    * @param string $where - таблица, содержащая объекты
    * @param mixed $category - код/id категории
    * @param mixed $region - код/id региона
    * @return string
    */
    public function whereClause($where, $category=null, $region=null){
        global $db;
        if(!empty($category)) {
            if($catInt = Convert::ToInt($category)) $where .= ($where!=''?" AND":"")." id_category=".$catInt;
            else{
                $cat = $db->fetch("SELECT id FROM ".$this->table_categories." WHERE code=?", $category);
                if(!empty($cat)) $where .= ($where!=''?" AND":"")." id_category=".$cat['id'];
            }
        }
        if(!empty($region)) {
            if($regInt = Convert::ToInt($region)) $where .= ($where!=''?" AND":"")." id_region=".$regInt;
             else{
                 $reg = $db->fetch("SELECT id FROM ".$this->table_regions." WHERE code=?", $region);
                 if(!empty($reg)) $where .= ($where!=''?" AND":"")." id_region=".$reg['id'];
             }
        }
        return $where;
    }
    
    public function insertBlock($content, $limit){
         preg_match_all("#(\<[(p)|(table)|(div)|(blockquote)]\s?.*?\>.*?\<\/(p|table|div|blockquote)\>)#msi", trim($content), $matches);
         
         $flag = false;
         $text = '';
         $text_length = 0;
         foreach($matches[0] as $k=>$match){ 
             $text .= $match;
             
             $strip_text = strip_tags(str_replace(array("\r","\n","\t", "&nbsp;"), '',$match));
             $text_length += mb_strlen($strip_text, 'UTF-8');
             if(empty($flag) && $text_length > 300){
                  $flag = true;
                  $text .= '<div id="other-news"></div>';
             }
         }
         if(empty($flag)) $text .= '<div id="other-news"></div>';
         return $text;
    }
    
 /**
    * получение условий для выборки
    * @param string $order - значение сортировки
    * @return string
    */
    public function makeOrderBy($order){    
        switch($order){
            case 2: 
                return $order = $this->table .".views_count DESC"; 
                break;    
            case 1: 
            default: 
                return $order = $this->table .".datetime DESC, views_count DESC"; 
                break;    
        }
    }
    /**
    * получение списка категорий
    * @param array $with_count - подсчет статей в каждой категории
    * @return array of array
    */
    public function getCategoriesList($with_count = false){
        if(empty($with_count)) return $this->getSimpleList($this->table_categories, "position");
        else return $this->getListCount($this->table_categories,"position");
    }
    
    /**
    * получение списка регионов
    * @return array of array
    */
    public function getRegionsList(){
        return $this->getSimpleList($this->table_regions,"position");
    }
    
    /**
    * получение списка регионов
    * @return array of array
    */
    public function getMonthsList($category=null, $region=null){
        global $db;
        $where = $this->whereClause($this->table .".`datetime` <= NOW() " , $category, $region);
        $list = $db->fetchall("
            SELECT 
                DATE_FORMAT(". $this->table .".`datetime`, '%Y') as `year`,
                DATE_FORMAT(". $this->table .".`datetime`, '%c') as `month`,
                DATE_FORMAT(". $this->table .".`datetime`, '%m') as `month_number`
            FROM ". $this->table ."
            " . ( !empty($where) ? " WHERE " . $where : "" ) . "
            GROUP BY `year`, `month`
            ORDER BY `year` DESC, month_number
        ");
        $year = 0;
        $array = [];
        foreach($list as $k=>$item) {
            if($year == 0) $year = $item['year'];
            $array[$item['year']][] = array('month'=> Config::Get('months')[$item['month']], 'month_number'=>$item['month_number'], 'active'=>$year == $item['year'] ? true : false ) ;
        }
        return $array;
    }
    
    public function getNewsItemTelegramSnippet($news_item, $without_photo = false, $just_content = false){
        if(empty($news_item['id_main_photo']) || !empty($without_photo)){
            $content = "";
            $content .= "<b>".$news_item['title']."</b>\r\n";
            $content .= "".$news_item['content_short']."\r\n";
            $link = self::getNewsItemLink($news_item);
            $link = "https://www.bsn.ru/".Host::generateShortUri($link)."/";
            $content .= "<a href='".$link."'>Читать далее</a>";
            return (!empty($just_content) ? $content : array('content'=>$content));
        }else{
            $content = "";
            $content .= "".$news_item['title']."\r\n";
            $link = self::getNewsItemLink($news_item,true);
            $link = "https://www.bsn.ru/".Host::generateShortUri($link)."/";
            $content .= $link."\r\n";
            return array('content'=>$content,'photo'=>'https://' . "www.bsn.ru" . '/' . Config::$values['img_folders']['news'] . '/big/' . $news_item['subfolder'] . '/' . $news_item['photo']);
        }
        
        
    }
    /**
    * получение ссылки на новость по считанному $item
    * 
    * @param mixed $item
    */
    public function getNewsItemLink($item,$short = false){
        if(empty($item['category_code']) || empty($item['region_code']) || empty($item['chpu_title'])) return false;
        elseif(empty($short)) return "https://www.bsn.ru/news/".$item['category_code']."/".$item['region_code']."/".$item['chpu_title']."/?from=telegram";
        else return "https://www.bsn.ru/news/".$item['category_code']."/".$item['region_code']."/".$item['chpu_title']."/?from=telegram";
    }

    /**
    * получение списка промо блоков
    * @return array of arrays
    */
    public function getPromoList($id_parent = false, $id = false){
        global $db;
        $where = !empty($id_parent) ? $this->table_promo .".id_parent = " . $id_parent : $this->table_promo .".id = " . $id;
        $sql = "SELECT ". $this->table_promo .".*, 
                        ".$this->table_promo_photos.".`name` as `photo`, LEFT (".$this->table_promo_photos.".`name`,2) as `subfolder`
                FROM ". $this->table_promo ."
                LEFT JOIN ".$this->table_promo_photos." ON ".$this->table_promo_photos.".id = ". $this->table_promo .".id_main_photo 
                WHERE " . $where . "
                GROUP BY ". $this->table_promo .".id
                ORDER BY position ASC ";
        $list = $db->fetchall($sql);  
        if(empty($list)) return [];
        return $list;
    }    

    /**
    * получение списка вопросов теста
    * @return array of arrays
    */
    public function getTestList($id_parent = false, $step = false){
        global $db;
        $where = [];
        if( !empty($id_parent) ) $where[] = $this->table_test .".id_parent = " . $id_parent;
        if( !empty($step) ) $where[] = $this->table_test .".position = " . $step;
        $where = implode(" AND ", $where);
        if( empty( $where ) ) return false;
        $sql = "SELECT ". $this->table_test .".*
                FROM ". $this->table_test ."
                WHERE " . $where . "
                ORDER BY position ASC ";
        $list = $db->fetchall($sql);  
        //получение ответов по каждому вопросу
        foreach($list as $k=>$item){
            $questions = $db->fetchall(" SELECT * FROM " . $this->table_test_questions ." WHERE id_parent = ? ORDER BY id ASC", false, $item['id']);
            $list[$k]['questions'] = $questions;
        }
        if(empty($list)) return [];
        return $list;
    }    
   
    /**
    * получение списка результатов теста
    * @return array of arrays
    */
    public function getTestResultsList($id_parent = false, $id = false, $right_answers = false){
        global $db;
        $where = [];
        if ( !empty($id_parent) ) $where[] = $this->table_test_results .".id_parent = " . $id_parent;
        if ( !empty($id) ) $where[] = $this->table_test_results .".id = " . $id;
        if ( !empty( $right_answers) || $right_answers !== false ) $where[] = "`from` <= " . $right_answers . " AND `to` >= " . $right_answers;
        $where = implode(" AND ", $where);
        $sql = "SELECT ". $this->table_test_results .".*
                FROM ". $this->table_test_results ."
                WHERE " . $where . "
                GROUP BY ". $this->table_test_results .".id
                ORDER BY id ASC ";
        $list = $db->fetchall($sql);    
        if(empty($list)) return [];
        return $list;
    }      
    
    /**
    * получение списка рекламных блоков лонгрида
    * @return array of arrays
    */
    public function getAdvertList($id_parent = false, $id = false){
        global $db;
        $where = !empty($id_parent) ? $this->table_longread_advert .".id_parent = " . $id_parent : $this->table_longread_advert .".id = " . $id;
        $sql = "SELECT ". $this->table_longread_advert .".*, 
                        ".$this->table_longread_advert_photos.".`name` as `photo`, LEFT (".$this->table_longread_advert_photos.".`name`,2) as `subfolder`
                FROM ". $this->table_longread_advert ."
                LEFT JOIN ".$this->table_longread_advert_photos." ON ".$this->table_longread_advert_photos.".id = ". $this->table_longread_advert .".id_main_photo 
                WHERE " . $where . "
                GROUP BY ". $this->table_longread_advert .".id
                ORDER BY position ASC ";
        $list = $db->fetchall($sql);  
        if(empty($list)) return [];
        return $list;
    }    
    
}


abstract class Media{
    public static $tables = [];
    public static function Init(){
        self::$tables = Config::Get('sys_tables');
    }    
    
    /**
    * запись статистики
    * 
    * @param mixed $action
    * @param mixed $objects
    * @param mixed $packets
    * @param mixed $from
    * @param mixed $ref
    * @param mixed $ip
    * @param mixed $user_agent
    */
    public static function List( $count, $exclude_news_id,  $exclude_article_id ) {
        global $db;
        
        $types = array( 'news', 'articles', 'doverie', 'bsntv' );
        $list = [];
        foreach( $types as $k=>$type ){
            $content = new Content( $type );
            $content_list = $content->getList( 
                $count, 
                0, 
                false, 
                false, 
                self::$tables[$type] . ".datetime <= NOW()" . 
                (
                    $type == 'news' ? ( !empty( $exclude_news_id ) ? "  AND  " . self::$tables['news'] .".id != " . $exclude_news_id : "" )  :
                        ( $type == 'articles' ? ( !empty( $exclude_article_id ) ? "  AND  " . self::$tables['articles'] .".id != " . $exclude_article_id : "" )  : 
                            ( $type == 'longread' ? ( !empty( $exclude_longread_id ) ? "  AND  " . self::$tables['longread'] .".id != " . $exclude_longread_id : "" )  : "" ) 
                        )
                ),
                self::$tables[$type] . ".datetime DESC "
            );
            foreach( $content_list as $k => $item ) {
                $item['content_type'] = $type;
                $list[ strtotime( $item['datetime'] ) ] = $item;
            }
        }
        krsort( $list );
        
        $list = array_slice( $list, 0, $count);
        return $list;
    }
}


                        
?>