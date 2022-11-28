<?php
require_once('includes/class.host.php');
class Calendar {
    protected $type = [];
    protected $tables = [];
    public function __construct( ){
        $this->tables = Config::$sys_tables;
        $this->table = $this->tables['calendar_events'];
        $this->table_photos = $this->tables['calendar_events_photos'];
    }
    /**
    * получение списка объектов
    * @param string $table - таблица, содержащая объекты
    * @param integer $count - кол-во элементов (если 0 - то без ограничения)
    * @param integer $from - начиная с этого элемента
    * @return array of arrays
    */
    public function getList($count = 0, $from = 0, $where = '', $order = 1){
        global $db;
        $events = $db->fetchall("
            SELECT " .$this->table. " .*, 
                   DATE_FORMAT(`date_begin`,'%e') as `daybegin`, DATE_FORMAT(`date_end`,'%e') as `dayend`,
                   DATE_FORMAT(`date_begin`,'%M') as `monthbegin`, DATE_FORMAT(`date_end`,'%M') as `monthend`,
                   IF(date_begin <= DATE_FORMAT(NOW(),'%Y-%m-%d') AND date_end >= DATE_FORMAT(NOW(),'%Y-%m-%d'),1,0) as active_event,
                   IF((DATEDIFF(date_end,NOW()) < 0 AND date_end != '0000-00-00') OR DATEDIFF(date_begin,NOW()) < 0,1,0) as past_event,
                   " .$this->table_photos. " .name as photo_name,
                   LEFT( " .$this->table_photos. " .name,2) as subfolder

            FROM " .$this->table. " 
            LEFT JOIN " .$this->table_photos. " ON " .$this->table. " .id_main_photo = " .$this->table_photos. " .id
            WHERE " . $where . " 
            GROUP BY " .$this->table. " .id
            ORDER BY `date_begin`, `date_end`
            " . ( !empty( $count ) || !empty( $from) ? " LIMIT " . $from . "," . $count : "" ) . "
        ");  
        return $events;      
    }
      
    
    /**
    * получение объекта по его ID
    * @param integer $chpu_title - ЧПУ объекта
    * @return array
    */
    public function getItem($chpu_title = false, $id = false){
        
    }
    /**
    * получение списков категорий и регионов
    * @param string $order - набор полей сортировки
    * @return array
    */
    public function getSimpleList($table, $order, $where=''){
        global $db;
        $sql = "SELECT * FROM " . $table;
        if(!empty($where)) $sql .=" WHERE " .$where;
        $sql .= " ORDER BY " .$order;
        $row = $db->fetchall($sql);
        return $row;
    }   
    
    
    /**
    * получение списка регионов
    * @return array of array
    */
    public function getMonthsList($category=null, $region=null){
        global $db;
        $list = $db->fetchall("
            SELECT 
                DATE_FORMAT(" . $this->table . ".`date_begin`, '%Y') as `year`,
                DATE_FORMAT(" . $this->table . ".`date_begin`, '%c') as `month`,
                DATE_FORMAT(" . $this->table . ".`date_begin`, '%m') as `month_number`
            FROM " . $this->table . "
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
    
}


                        
?>