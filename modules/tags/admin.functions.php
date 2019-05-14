<?php
/**
* удаление связей тега
* @param integer id тега
*/
function deleteTagLinks($id){
    global $db;
    $sys_tables = Config::$sys_tables;
    $catlist = $db->fetchall("SELECT code FROM ".$sys_tables['content_tags_categories']);
    if(!empty($catlist)){
        $types = [];
        foreach($catlist as $catkey=>$catitem){
            $types[] = $catitem['code'].'_tags';
        }
        $tableslist = $db->fetchall("SHOW TABLES IN content LIKE '%_tags'");
        foreach($tableslist as $tlkey=>$tableitem){
            $table = array_pop($tableitem);
            if(in_array($table,$types)){
                $res = $db->query("DELETE FROM content.".$table." WHERE id_tag=?", $id);
            }
        }
    }
}
?>