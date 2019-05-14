<?php
/**
* Работа с дополнительным файловым хранилищем данных
* (требуется класс Host (/includes/class.host.php))
*/
class FileData {
    private static $filename = 'data/scripts_and_css.data';
    
    public static function Load(){
        if(file_exists(Host::getRealPath(self::$filename))) return unserialize(file_get_contents(Host::getRealPath(self::$filename)));
        return [];
    }
    
    public static function Save($array){
        return file_put_contents(Host::getRealPath(self::$filename), serialize($array));
    }
}
?>