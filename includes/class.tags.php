<?php
/**    
* Класс управления тегами (для контента)
*/

class Tags {
    private static $tables = array(
        'tags' => 'content.tags',
        'categories' => 'content.tags_category'
    );
    
    /**
    * Получение списка привязанных к объекту тегов
    * @param integer ID объекта
    * @param string таблица связи
    * @return array массив тегов
    */
    public static function getLinkedTags($object_id, $link_table){
        global $db;
        $res = $db->fetchall("SELECT ".self::$tables['tags'].".*, ".$link_table.".id_object FROM ".$link_table."
                              LEFT JOIN ".self::$tables['tags']." ON ".self::$tables['tags'].".id = ".$link_table.".id_tag
                              WHERE ".$link_table.".id_object=?
                              ORDER BY ".self::$tables['tags'].".title", 'id', $object_id);
        if(empty($res)) return [];
        return $res;
    }
    
    /**
    * Поиск тегов по началу названия
    * @param string строка поиска (начало названия тега)
    * @param mixed ID типа тега или алиас типа тега (из таблицы tag_category)
    * @return array массив тегов
    */
    public static function searchTags($search_string, $type=null, $count=10){
        global $db;
        if(!is_int($type) && !Validate::Digit($type)){
            $res = $db->fetch("SELECT id FROM ".self::$tables['categories']." WHERE code=?", $type);
            if(empty($res)) return [];
            $type = intval($res['id']);
        }
        $res = $db->fetchall("SELECT * FROM ".self::$tables['tags']." WHERE id_category=? AND title LIKE ? ORDER BY title LIMIT ?", false, $type, $search_string."%", $count);
        if(empty($res)) return [];
        return $res;
    }
    
    /**
    * Добавление нового тега
    * @param string тег (название/содержимое/сам тег)
    * @param mixed ID типа тега или алиас типа тега
    * @return mixed ID тега (нового или уже существовавшего) или false
    */
    public static function addTag($title, $type){
        global $db;
        if(!is_int($type) && !Validate::Digit($type)){
            $res = $db->fetch("SELECT id FROM ".self::$tables['categories']." WHERE code=?", $type);
            if(empty($res)) return false;
            $type = intval($res['id']);
        }
        $res = $db->fetch("SELECT id FROM ".self::$tables['tags']." WHERE id_category=? AND title=?", $type, $title);
        if(!empty($res)) return intval($res['id']);
        $res = $db->query("INSERT INTO ".self::$tables['tags']." (title, id_category) VALUES (?, ?)", $title, $type);
        if(empty($res)) return false;
        return $db->insert_id;
    }
    
    /**
    * привязка тега к объекту
    * @param integer ID тэга
    * @param integer ID объекта
    * @param string таблица связи
    */
    public static function linkTag($id_tag, $id_object, $link_table){
        global $db;
        $res = $db->query("INSERT INTO ".$link_table." (id_tag, id_object) VALUES (?,?)", $id_tag, $id_object);
        if(empty($res)) return false;
        $res = $db->query("UPDATE ".self::$tables['tags']." SET tag_count=tag_count+1 WHERE id=?", $id_tag);
        return !empty($res) && $db->affected_rows>0;
    }

    /**
    * отвязка тега от объекта
    * @param integer ID тэга
    * @param integer ID объекта
    * @param string таблица связи
    */
    public static function unlinkTag($id_tag, $id_object, $link_table){
        global $db;
        $res = $db->query("DELETE FROM ".$link_table." WHERE id_tag=? AND id_object=?", $id_tag, $id_object);
        if(empty($res) || $db->affected_rows<1) return false;
        $res = $db->query("UPDATE ".self::$tables['tags']." SET tag_count=tag_count-1 WHERE id=?", $id_tag);
        return !empty($res) && $db->affected_rows>0;
    }
}
?>