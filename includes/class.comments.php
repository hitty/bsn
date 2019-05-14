<?php
require_once('includes/class.storage.php');
require_once('includes/class.auth.php');
require_once('includes/class.content.php');
require_once('includes/class.opinions.php');
require_once('includes/class.housing_estates.php');
require_once('includes/class.auth.php');

class Comments {

    private static $comments_table = 'content.comments';
    private static $comments_votings_table = 'content.comments_votings';
    private static $users_table = 'common.users';
    private static $users_photos_table = 'common.users_photos';
    private static $objects = array(
        'news' => array(
            'parent_type'  => 1,
            'table'    => 'content.news'
        ),
        'articles' => array(
            'parent_type'  => 2,
            'table'    => 'content.articles'
        ),
        'calendar_events'  => array(
            'parent_type'  => 3,
            'table'    => 'content.calendar_events'
        ),
        'opinions'  => array(
            'parent_type'  => 4,
            'table'    => 'content.opinions'
        ),
        'webinars'  => array(
            'parent_type'  => 5,
            'table'    => 'service.webinars'
        ),
        'predictions'  => array(
            'parent_type'  => 6,
            'table'    => 'content.opinions'
        ),
        'interview'  => array(
            'parent_type'  => 7,
            'table'    => 'content.opinions'
        ),
        'housing_estates'  => array(
            'parent_type'  => 8,
            'table'    => 'estate.housing_estates'
        ),
        'bsntv'  => array(
            'parent_type'  => 9,
            'table'    => 'content.bsntv'
        ),
        'blog'  => array(
            'parent_type'  => 10,
            'table'    => 'content.blog'
        ),
        'doverie'  => array(
            'parent_type'  => 12,
            'table'    => 'content.doverie'
        ),
    );
    
    public static $comments_on_page = 10;
    public static $comments_shift = 0;
    public static $parent_type = null;
    public static $type = null;
    public static $id_parent = null;
    public static $comments_id = null;
    public static $comments_count = 0;
    public static $eoc = false;
    public static $sys_tables = [];

    /**
    * Инициализация комментариев
    * @param string $type тип комментируемых объектов - news,articles или calendar
    * @param integer $id_parent ID коментируемого объекта в своей группе объектов
    * @param integer $comments_on_page кол-во коментариев на странице (по умолчанию)
    * @param integer $comments_shift с какого по счету коментария выводить (по умолчанию 0)
    */
    public static function Init($type, $id_parent, $comments_on_page=false, $comments_shift=false){
        if(in_array($type, array_keys(self::$objects))) {
            self::$type = $type;
            self::$parent_type = self::$objects[$type];
            if(!empty($id_parent)) self::$id_parent = $id_parent;
        }
        if(!empty($comments_on_page)) self::$comments_on_page = $comments_on_page;
        if(!empty($comments_shift)) self::$comments_shift = $comments_shift;
        self::$sys_tables = Config::Get('sys_tables');
        
    }
    
    public static function getCommentsCount($hide_blocked=true){
        global $db;
        $sql = "SELECT count(*) as cnt FROM ".self::$comments_table."
                WHERE id_parent IN (?) AND parent_type=?".($hide_blocked ? " AND comments_active=1" : "");
        $res = $db->query(
            $sql,
            self::$id_parent,
            self::$parent_type['parent_type']
        );
        echo $db->last_query;
        if($item = $res->fetch_assoc()) return self::$comments_count = $item['cnt'];
        return false;
    }
     /**
    * Получение порции коментариев (из указанного кол-ва штук)
    * @param boolean $hide_blocked скрывать заблокированные
    * @param integer $shift смещение начала выборки
    */
    public static function getLastComments($shift=null, $hide_blocked=true, $orderby,$node_id=0, $group_by = false, $limit = false){
        global $db, $sys_tables, $auth;
        if(empty($shift)) $shift = self::$comments_shift; 
        self::$comments_shift = $shift;   
        if(empty($node_id)) $node_id = 0;
        $sql = "SELECT 
                   ".self::$comments_table.".*, 
                   ".self::$users_photos_table.".`name` as `photo`, 
                   IFNULL(votes.all_votes,0) as all_votes,
                   LEFT (".self::$users_photos_table.".`name`,2) as `subfolder`,
                   ".self::$users_table.".name,
                   ".self::$users_table.".lastname,
                    CONCAT(
                        IF(DATE(comments_datetime) = CURDATE(), 'сегодня',
                            IF(DATE(comments_datetime) = CURDATE() - INTERVAL 1 day , 'вчера',
                                IF(DATE(comments_datetime) = CURDATE() - INTERVAL 2 day , '2 дня назад', DATE_FORMAT(comments_datetime,'%e %M'))
                            )
                        ), 
                        ' в ',
                        DATE_FORMAT(comments_datetime,'%k:%i') 
                    ) as normal_datetime,
                    IF(can.id>0,0,1) as can_vote,
                    IF(can.vote_for>0,1,0) as voted_plus,
                    IF(can.vote_against>0,1,0) as voted_minus
                FROM ".self::$comments_table."
                LEFT JOIN ".self::$users_table." ON ".self::$users_table.".id = ".self::$comments_table.".id_user
                LEFT JOIN ".self::$users_photos_table." ON ".self::$users_photos_table.".id_parent = ".self::$users_table.".id
                LEFT JOIN ".self::$comments_votings_table." can ON can.id_parent = ".self::$comments_table.".id  AND can.id_user = ".(!empty($auth->id)?$auth->id:0)."
                " . ( $node_id != 'all' ? "RIGHT JOIN " . self::$sys_tables[self::$type] . " ON " . self::$comments_table . ".id_parent = " . self::$sys_tables[self::$type] . ".id " : "" ) . "
                LEFT JOIN ( SELECT SUM(vote_for) - SUM(vote_against) AS all_votes, id_parent FROM ".self::$comments_votings_table." GROUP BY id_parent
                                   ) votes ON votes.id_parent = ".self::$comments_table.".id
                WHERE 
                    ".(!empty(self::$id_parent) ? self::$comments_table.".id_parent IN ( ".self::$id_parent." ) AND " : "" )." 
                    ".self::$comments_table.".id_comment_parent = '".$node_id . "' AND 
                    ".self::$comments_table.".parent_type=?".($hide_blocked ? " AND ".self::$comments_table.".comments_active=1" : "")."
                GROUP BY " . ( empty($group_by) ? self::$comments_table.".id " : $group_by ) . "
                ORDER BY  ".($node_id>0?self::$comments_table.".comments_datetime ":(!empty($orderby)?$orderby:self::$comments_table.".comments_datetime DESC"));
        $list = $db->fetchall(
            $sql,
            false,
            self::$parent_type['parent_type']
        );        
        if( empty( $group_by ) || empty( $limit ) ){
            foreach($list as $k=>$item){
                if(empty($item['id_comment_parent'])) {
                    $list[$k]['childs'] = self::getLastComments(false,$hide_blocked,false,$item['id']);
                }
            }
        }
        if(self::$comments_count<=$shift+self::$comments_on_page) self::$eoc = true;
        return $list;
    }
    
    public static function getComment($cid){
        global $db;
        $sql = "SELECT 
                   ".self::$comments_table.".*, 
                   ".self::$users_photos_table.".`name` as `photo`, 
                   IFNULL(votes.all_votes,0) as all_votes,
                   LEFT (".self::$users_photos_table.".`name`,2) as `subfolder`,
                   ".self::$users_table.".name,
                   ".self::$users_table.".lastname,
                    CONCAT(
                        IF(DATE(comments_datetime) = CURDATE(), 'сегодня',
                            IF(DATE(comments_datetime) = CURDATE() - INTERVAL 1 day , 'вчера',
                                IF(DATE(comments_datetime) = CURDATE() - INTERVAL 2 day , '2 дня назад', DATE_FORMAT(comments_datetime,'%e %M'))
                            )
                        ), 
                        ' в ',
                        DATE_FORMAT(comments_datetime,'%k:%i') 
                    ) as normal_datetime,
                    IF(can.id>0,0,1) as can_vote,
                    IF(can.vote_for>0,1,0) as voted_plus,
                    IF(can.vote_against>0,1,0) as voted_minus
                FROM ".self::$comments_table."
                LEFT JOIN ".self::$users_table." ON ".self::$users_table.".id = ".self::$comments_table.".id_user
                LEFT JOIN ".self::$users_photos_table." ON ".self::$users_photos_table.".id_parent = ".self::$users_table.".id
                LEFT JOIN ".self::$comments_votings_table." can ON can.id_parent = ".self::$comments_table.".id  AND can.id_user = ".(!empty($auth->id)?$auth->id:0)."
                LEFT JOIN ( SELECT SUM(vote_for) - SUM(vote_against) AS all_votes, id_parent FROM ".self::$comments_votings_table." GROUP BY id_parent
                                   ) votes ON votes.id_parent = ".self::$comments_table.".id
                WHERE 
                    ".self::$comments_table.".id = ?
                GROUP BY ".self::$comments_table.".id";
        $item = $db->fetch($sql, $cid);
        return $item;
    }
    
    /**
    * Добавление коментария к объекту
    * @param string $text - сдержимое коментария
    * @return boolean результат добавления
    */
    public static function addComment($text=null, $author_name=null, $author_email=null, $subscribed = null, $id_comment_parent=0, $active=false, $id_comment_answer=0){
        global $db, $auth;
        if(!empty($text) && !empty($author_name)){
            $text = strip_tags($text);
            
            $result = $db->query("INSERT INTO ".self::$comments_table." (id_parent, comments_datetime, author_name, author_email, parent_type, comments_text, user_ip, id_user, id_comment_parent, comments_active, id_comment_answer)
                                  VALUES (?,?,?,?,?,?,?,?,?,?,?)"
                                  , self::$id_parent
                                  , date('Y-m-d H:i:s')
                                  , $author_name
                                  , $author_email
                                  , self::$parent_type['parent_type']
                                  , $text
                                  , Host::getUserIp()
                                  , !empty($auth->id)?$auth->id:0
                                  , !empty($id_comment_parent)?$id_comment_parent:0
                                  , 1                          
                                  , !empty($id_comment_answer)?$id_comment_answer:0
                                  );
            return !empty($result);
        } else {
            return false;
        }
    }
    
    /**
    * заблокировать коментарий
    * @param int $cid ID комментария
    * @return boolean
    */
    public static function blockComment($cid){
        global $auth, $db;
        if($auth->isAuthorized() && $auth->getAccess('priv_comm_moderate')=='Y'){
            $res = $db->query("UPDATE ".self::$comments_table." SET comments_active=2 WHERE comments_id=?", $cid);
            return $res;
        }
        return false;
    }
    
    /**
    * разблокировать коментарий
    * @param int $cid ID комментария
    * @return boolean
    */
    public static function unblockComment($cid){
        global $auth, $db;
        if($auth->isAuthorized() && $auth->getAccess('priv_comm_moderate')=='Y'){
            $res = $db->query("UPDATE ".self::$comments_table." SET comments_active=1 WHERE comments_id=?", $cid);
            return $res;
        }
        return false;
    }
    
    public static function makeCommentBox($data){
        global $auth;
        $moderator = $auth->isAuthorized() && $auth->id_group==3;
        $str = '<div class="comment_item" id="cid'.$data['id'].'">';
        $str .= '<span class="username">'.$data['author_name'].'</span>';
        $str .= '<span class="userdata">';
        $str .= date('d.m.Y H:i',strtotime($data['comments_datetime'])).'. ';
        $str .= '</span>';
        if($moderator){
            if($data['comments_active'] == 1)
                $str .= '<div class="comment_text">'.$data['comments_text'].'</div>';
            else
                $str .= '<div class="comment_text inactive">'.$data['comments_text'].'</div>';
        } else {
            if($data['comments_active'] == 1)
                $str .= '<div class="comment_text">'.$data['comments_text'].'</div>';
            else
                $str .= '<div class="comment_text blocked">Сообщение заблокировано</div>';
        }
        $str .= '</div>';
        return $str;
    }
    
    public static function getInfo($item,$with_content=false){
        global $db;    
        global $sys_tables;
        if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
        switch($item['parent_type']){
                case 1:
                    $news_item = $db->fetch("SELECT chpu_title".(!empty($with_content) ? ",content,title" : "")." FROM ".$sys_tables['news']." WHERE id = ?", $item['id_parent']);
                    $news = new Content('news');
                    $link = $news->getItem($news_item['chpu_title']);
                    $title = $link['title'];
                    $type = "news";
                    $type_title = "Новость";
                    $content = $news_item['content'];
                    $link = '/news/'.$link['category_code'].'/'.$link['region_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 2:
                    $analytic_item = $db->fetch("SELECT chpu_title".(!empty($with_content) ? ",content,title" : "")." FROM ".$sys_tables['articles']." WHERE id = ?", $item['id_parent']);
                    $articles = new Content('articles');
                    $link = $articles->getItem($analytic_item['chpu_title']);
                    $title = $link['title'];
                    $type = "articles";
                    $type_title = "Аналитика";
                    $content = $analytic_item['content'];
                    $link = '/articles/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 3:
                    $calendar_item = $db->fetch("SELECT chpu_title".(!empty($with_content) ? ",text AS content,title" : "")." FROM ".$sys_tables['calendar_events']." WHERE id = ?", $item['id_parent']);
                    $title = $calendar_item['title'];
                    $content = $calendar_item['content'];
                    $type = "calendar_events";
                    $type_title = "Событие";
                    $link = '/calendar/'.$calendar_item['chpu_title'].'/';
                    break;
                case 5:
                    $webinar_item = $db->fetch("SELECT url".(!empty($with_content) ? ",text AS content,title" : "")." FROM ".$sys_tables['webinars']." WHERE id = ?", $item['id_parent']);
                    $title = $webinar_item['title'];
                    $content = $webinar_item['content'];
                    $type = "webinars";
                    $type_title = "Вебинар";
                    $link = '/webinars/'.$webinar_item['url'].'/';
                    break;
                case 4:
                case 6:
                case 7:
                    $this_type = ( $item['parent_type'] == 7 ? 'interview' : ( $item['parent_type'] == 6 ? 'predictions' : 'opinions' ) );
                    $opinions = new Opinions($this_type);
                    $opinion_item = $opinions->getItem($item['id_parent']);
                    $title = $opinion_item['annotation'];
                    $content = $opinion_item['text'];
                    $type = ($this_type);
                    $type_title = ( $item['parent_type'] == 7 ? 'Интервью' : ( $item['parent_type'] == 6 ? 'Прогноз' : 'Мнение' ) );
                    $link = '/'.$opinion_item['type_url'].'/'.$opinion_item['estate_url'].'/'.$opinion_item['chpu_title'].'/';
                    break;
                case 8:
                    $housing_estate_item = $db->fetch("SELECT * FROM ".$sys_tables['housing_estates']." WHERE id = ?", $item['id_parent']);
                    $title = $housing_estate_item['title'];
                    $content = $housing_estate_item['notes'];
                    $type = "housing_estates";
                    $type_title = "ЖК";
                    $link = '/zhiloy_kompleks/'.$housing_estate_item['chpu_title'].'/';
                    break;
                case 9:
                    $bsntv_item = $db->fetch("SELECT chpu_title".(!empty($with_content) ? ",content,title" : "")." FROM ".$sys_tables['bsntv']." WHERE id = ?", $item['id_parent']);
                    $bsntv = new Content('bsntv');
                    $link = $bsntv->getItem($bsntv_item['chpu_title']);
                    $title = $link['title'];
                    $content = $bsntv_item['content'];
                    $type = "bsntv";
                    $type_title = "БСН-ТВ";
                    $link = '/bsntv/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 10:
                    $blog_item = $db->fetch("SELECT chpu_title".(!empty($with_content) ? ",content,title" : "")." FROM ".$sys_tables['blog']." WHERE id = ?", $item['id_parent']);
                    $blog = new Content('blog');
                    $link = $blog->getItem($blog_item['chpu_title']);
                    $title = $link['title'];
                    $content = $blog_item['content'];
                    $type = "blog";
                    $type_title = "Блог";
                    $link = '/blog/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 12:
                    $doverie_item = $db->fetch("SELECT chpu_title".(!empty($with_content) ? ",content,title" : "")." FROM ".$sys_tables['doverie']." WHERE id = ?", $item['id_parent']);
                    $doverie = new Content('doverie');
                    $link = $doverie->getItem($doverie_item['chpu_title']);
                    $title = $link['title'];
                    $content = $doverie_item['content'];
                    $type = "doverie";
                    $type_title = "Доверие потребителя";
                    $link = '/doverie/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                    
        }   
            if(!empty($with_content)) return compact('title', 'link', 'content', 'type', 'type_title');
            else return array($title, $link);
    }
    
    public static function getUserAnswers($id_user, $id_parent = false, $unread = false){
        global $db;
        $sql = "SELECT 
                   answers.*, 
                   ".self::$users_photos_table.".`name` as `photo`, 
                   LEFT (".self::$users_photos_table.".`name`,2) as `subfolder`,
                   ".self::$users_table.".name,
                   ".self::$users_table.".lastname
                FROM ".self::$comments_table."
                RIGHT JOIN ".self::$comments_table." answers ON answers.id_comment_parent = ".self::$comments_table.".id
                LEFT JOIN ".self::$users_table." ON ".self::$users_table.".id = answers.id_user
                LEFT JOIN ".self::$users_photos_table." ON ".self::$users_photos_table.".id_parent = ".self::$users_table.".id
                WHERE 
                    ".(!empty($id_parent) ? self::$comments_table.".id_parent IN ( ".$id_parent." ) AND " : "" )." 
                    ".self::$comments_table.".id_user = ".$id_user." AND
                    ".(!empty(self::$parent_type['parent_type']) ? self::$comments_table.".parent_type = ".self::$parent_type['parent_type']." AND " : "")." 
                    ".(!empty($unread) ? "  answers.comments_viewed = 2 " : "" )." 
                GROUP BY answers.id
                ORDER BY  ".self::$comments_table.".comments_datetime";
        $list = $db->fetchall($sql);
        $list = self::getLink($list);
        return $list;
    }
    public static function getLink($list){
        global $db;
        self::$sys_tables = Config::Get('sys_tables');
        foreach($list as $k=>$item){
            switch($item['parent_type']){
                case 1:
                    $news_item = $db->fetch("SELECT chpu_title FROM ".self::$sys_tables['news']." WHERE id = ?", $item['id_parent']);
                    $news = new Content('news');
                    $link = $news->getItem($news_item['chpu_title']);
                    $list[$k]['link'] = '/news/'.$link['category_code'].'/'.$link['region_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 2:
                    $analytic_item = $db->fetch("SELECT chpu_title FROM ".self::$sys_tables['articles']." WHERE id = ?", $item['id_parent']);
                    $articles = new Content('articles');
                    $link = $articles->getItem($analytic_item['chpu_title']);
                    $list[$k]['link'] = '/articles/'.$link['category_code'].'/'.$link['chpu_title'].'/';
                    break;
                case 3:
                    $calendar_item = $db->fetch("SELECT chpu_title FROM ".self::$sys_tables['calendar_events']." WHERE id = ?", $item['id_parent']);
                    $list[$k]['link'] = '/calendar/'.$calendar_item['chpu_title'].'/';
                    break;
                case 8:
                    $calendar_item = $db->fetch("SELECT chpu_title FROM ".self::$sys_tables['housing_estates']." WHERE id = ?", $item['id_parent']);
                    $list[$k]['link'] = '/zhiloy_kompleks/'.$calendar_item['chpu_title'].'/';
                    break;
                case 5:
                    $webinar_item = $db->fetch("SELECT url FROM ".self::$sys_tables['webinars']." WHERE id = ?", $item['id_parent']);
                    $list[$k]['link'] = '/webinars/'.$webinar_item['url'].'/';
                    break;
                case 4:
                case 6:
                case 7:
                    $opinions = new Opinions($item['parent_type']=='Интервью'?'interview':($item['parent_type']=='Прогнозы'?'predictions':'opinions'));
                    $opinion_item = $opinions->getItem($item['id_parent']);
                    $list[$k]['link'] = '/'.$opinion_item['type_url'].'/'.$opinion_item['estate_url'].'/'.$opinion_item['chpu_title'].'/';
                    break;
            }
        } 
        return $list;       
    }
}

?>