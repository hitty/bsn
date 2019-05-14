<?php
/**    
* Конфигурация сайта
*/
class Config {
    private static $php_file = 'config.php';
    private static $cached_file = 'data/config.data';
    
    public static $values = [];          // здесь хранятся загруженные значения
    public static $sys_tables = [];      // таблицы БД 
    
    /**
    * При создании объекта сразу происходит загрузка данных из БД
    * @param string раздел конфига для загрузки (часть пути)
    * @return Configuration
    */
    public static function Init(){
        if (!file_exists(ROOT_PATH.'/'.self::$cached_file) || filemtime(ROOT_PATH.'/'.self::$cached_file) <= filemtime(ROOT_PATH.'/'.self::$php_file)) {
            self::$values = include(ROOT_PATH.'/'.self::$php_file);
            if ($f = fopen(ROOT_PATH.'/'.self::$cached_file, "w")) {
                fwrite($f, serialize(self::$values));
                fclose($f);
            }
        } else {
            self::$values = unserialize(file_get_contents(ROOT_PATH.'/'.self::$cached_file));
        }
        self::$sys_tables = self::Get('sys_tables');
    }
    

    /**
    * Получить элемент
    * @param string путь
    * @return array(value,description)
    */
    public static function Get($path){
        $path = trim($path,'/');
        if(empty($path)) return null;
        $keys = explode('/',$path);
        $value =& self::$values;
        while(!empty($keys)){
            $key = array_shift($keys);
            if(isset($value[$key])) $value =& $value[$key];
            else return null;
        }
        return $value;
    }
}
?>