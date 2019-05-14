<?php
/**
* Формирование дерева страниц
* @param integer ID родителя ветки дерева, которую хотим сформировать (если 0 - то целиком всё дерево)
* @param mixed массив ID-шников открытых узлов дерева, или 'all' - всё дерево раскрыто
* @return array дерево страниц
*/
function make_pages_tree($parent_id=0, $nodes_expanded='all', $exclude_id = null){
    global $db;
    $sys_tables = Config::$sys_tables;
    $tree = [];
    $nodes = $db->fetchall(
        "SELECT a.id,a.title,a.alias,a.url,
                b.id as map_id, b.path, b.level, IFNULL(c.quantity,0) as quantity
         FROM ".$sys_tables['pages']." a
         LEFT JOIN ".$sys_tables['pages_map']." b ON a.id=b.object_id
         LEFT JOIN (SELECT parent_id, count(*) as quantity FROM ".$sys_tables['pages_map']." GROUP BY parent_id) c ON b.id=c.parent_id
         WHERE b.parent_id=?
         ORDER BY b.position,a.url"
        , false
        , $parent_id
    );
    if(empty($nodes)) return $tree;
    foreach($nodes as $node){
        if(empty($exclude_id) || $node['id']!=$exclude_id){
            if(!empty($node['quantity']) && ($nodes_expanded=='all' || in_array($node['id'],$nodes_expanded))){
                $node['expanded'] = true;
                $tree[] = $node;
                $childs = make_pages_tree($node['map_id'],$nodes_expanded,$exclude_id);
                $tree = array_merge($tree,$childs);
                $last_sub = sizeof($childs);
            } else {
                $tree[] = $node;
                $last_sub = 0;
            }
        }
    }
    if(!empty($tree)) $tree[sizeof($tree)-1-$last_sub]['last'] = true;
    return $tree;    
}

/**
* Удаление страницы (включая все вложенные страницы)
* @param integer ID страницы
* @return boolean
*/
function delete_page($id){
    global $db;
    $sys_tables = Config::$sys_tables;
    $page_in_map = $db->fetch("
        SELECT id, path 
        FROM ".$sys_tables['pages_map']."
        WHERE object_id=?"
        , $id
    );
    if(empty($page_in_map)) return false;
    $del_ids = $db->fetchall("SELECT object_id FROM ".$sys_tables['pages_map']." WHERE path LIKE CONCAT(?,'.%')", 'object_id', $page_in_map['path']);
    if(empty($del_ids)) $del_ids = [];
    $del_ids[$id] = array('object_id' => $id);
    $del_ids = array_keys($del_ids);
    // удаление из pages
    $d2 = $db->query("DELETE FROM ".$sys_tables['pages']." WHERE id IN (".implode(',',$del_ids).")");
    // удаление из pages_map
    $d1 = $db->query("DELETE FROM ".$sys_tables['pages_map']." WHERE path LIKE CONCAT(?,'.%') OR id=?",$page_in_map['path'],$page_in_map['id']);
    if($d1 && $d2) return $del_ids;
    return false;
}

/**
* Перенос страницы в структуре
* @param integer ID страницы
* @param integer ID целевой записи в структуре
* @return boolean
*/
function move_page($page_id, $target_map_id){
    global $db;
    $sys_tables = Config::$sys_tables;
    // получаем данные перемещаемого узла
    $object = $db->fetch("SELECT * FROM ".$sys_tables['pages_map']." WHERE object_id=?",$page_id);
    if(empty($object)) return false; // некого перемещать
    if($object['parent_id']==$target_map_id) return true; // перемещение не требуется (цель уже достигнута)
    // получаем данные цели
    if($target_map_id<1){
        $target = array('id'=>0,'level'=>-1,'path'=>'');
    } else {
        $target = $db->fetch("SELECT * FROM ".$sys_tables['pages_map']." WHERE id=?",$target_map_id);
        if(empty($target)) return false; // некуда перемещать (нет целевого узла)
        if(strpos($target['path'],$object['path'])===0) return false;   // перемещение в потомка запрещено (зацикливание)
    }
    // updating node parent
    $result = $db->query("UPDATE ".$sys_tables['pages_map']."
                                SET parent_id=? WHERE id=?",
                                $target_map_id,
                                $object['id']);
    if(!$result) return false;
    // перемещаем "кустик"
    $result = $db->query("UPDATE ".$sys_tables['pages_map']." 
                                SET path = REPLACE(path,?,?),
                                    level = (level-?+?)
                                WHERE path = ?
                                    OR path LIKE ?"
                                , $object['path']
                                , (empty($target['path']) ? "" : $target['path'].".").$object['id']
                                , $object['level']
                                , $target['level'] + 1
                                , $object['path']
                                , $object['path'].'.%'
                              );
    if(!$result) {
        // возвращаем узел к начальному состоянию
        $result = $db->query("UPDATE ".$sys_tables['pages_map']."
                                    SET parent_id=? WHERE id=?"
                                    , $object['parent_id']
                                    , $object['id']
                                  );
        return false;
    }
    return true;
}

/**
* Добавление записи в структуру страниц (позиционирование страницы)
* @param integer ID страницы
* @param integer ID записи - предка по структуре
* @return mixed ID записи в структуре или FALSE
*/
function add_page_in_map($page_id, $map_parent_id){
    global $db;
    $sys_tables = Config::$sys_tables;
    if($map_parent_id<1){
        $target = array('id'=>0,'level'=>-1,'path'=>'');
    } else {
        $target = $db->fetch("SELECT id,level,path FROM ".$sys_tables['pages_map']."
                              WHERE id=?", $map_parent_id);
    }
    if(empty($target)) return false; // некуда добавлять
    $order_select = $db->fetch("SELECT max(position) as max_position FROM ".$sys_tables['pages_map']."
                                WHERE parent_id=?", $map_parent_id);
    if(empty($order_select) || $order_select['max_position']===null) $order_select = array('max_position'=>0);
    
    $result = $db->query("INSERT INTO ".$sys_tables['pages_map']."
                            (object_id, parent_id, level, position)
                          VALUES 
                            (?, ?, ?, ?)"
                          , $page_id
                          , $target['id']
                          , $target['level']+1
                          , $order_select['max_position']+0.02
                          );
    if(!$result) return false;
    $id = $db->insert_id;
    $result = $db->query("UPDATE ".$sys_tables['pages_map']." SET path=? WHERE id=?"
                        , (empty($target['path']) ? '' : $target['path'].'.').$id
                        , $id
                        );
    if(!$result) return false;
    return $id;
}

/**
* Получение URL страницы - предка
* @param integer ID предка по структуре
* @return mixed URL или false
*/
function get_parent_url($parent_id){
    global $db;
    $sys_tables = Config::$sys_tables;
    $parent = $db->fetch("SELECT a.url FROM ".$sys_tables['pages']." a
                          LEFT JOIN ".$sys_tables['pages_map']." b ON a.id=b.object_id
                          WHERE b.id=?", $parent_id);
    if(empty($parent)) return false;
    return $parent['url'];
}
?>