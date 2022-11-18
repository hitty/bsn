<?php
/**    
* Pagination
*/
class Paginator {
    public $current_page = 1;
    public $pages_to_show = [];
    
    private $tablename = null;
    public $group_by = '';
    public $limit = 10;
    public $items_count = 0;
    public $total_items_count = 0;
    public $pages_count = 0;
    public $link_prefix = '';
    public $links_count = 5;

    /**
    * Объект-пагинатор 
    * @param string $tablename - таблица, из которой происходит выборка
    * @param integer $limit -  кол-во элементов на страницу
    * @param string $where - условие where для sql-запроса, по которому формируется список
    * @param string $sql - полный sql-запрос, по которому формируется список
    * @param string $group_by - группировка
    * @return Paginator
    */
    public function __construct($tablename, $limit=null, $where = '', $sql_full = '', $group_by = false){
        $this->tablename = $tablename;
        if(!empty($limit)) $this->limit = $limit;
        if(!empty($group_by)) $this->group_by = $group_by;
        if(!empty($sql_full)) $this->PrepareFull($sql_full);
        else $this->Prepare($where);
    }

    private function Prepare($where=''){
        global $db;
        $sql = "SELECT count(*) as items_count FROM ".$this->tablename;
        if(!empty($where)) $where = " WHERE ".$where;
        if(empty($this->group_by)){
            $row = $db->fetch($sql.$where);
            $this->items_count = empty($row) ? 0 : $row['items_count'];
        } else {
            $total_rows = $db->fetch($sql.$where);
            $this->total_items_count = empty($total_rows) ? 0 : $total_rows['items_count']; 
            $row = $db->fetchall($sql.$where." GROUP BY ".$this->group_by);
            $this->items_count = empty($row) ? 0 : count($row); 
        }
        
        
        $this->pages_count = ceil($this->items_count / $this->limit);
    }

    private function PrepareFull($sql_full){
        global $db;
        $sql = $sql_full;
        $t_result = $db->querys($sql);
        if($t_result) $row = $t_result->fetch_array(MYSQLI_ASSOC);
        $this->items_count = empty($row) ? 0 : $row['items_count'];
        $this->pages_count = ceil($this->items_count / $this->limit);
    }
    
    /**
    * Получение данных пагинатора
    * @param integer Номер текущес страницы
    * @return array Данные пагинатора
    */
    public function Get($current_page=1){
        $min_page = $current_page-floor($this->links_count/2);
        $max_page = $current_page+floor($this->links_count/2);
        while($min_page<1){
            $min_page++;
            $max_page++;
        }
        while($max_page>$this->pages_count) {
            $max_page--;
            if($min_page>1) $min_page--;
        }
        if($current_page<5 && $this->pages_count>8) {
            $max_page++;
            if($current_page<4) {
                $max_page++;
            }
        }    
        if($current_page>$this->pages_count-4 && $this->pages_count>8) {
            $min_page--;
            if($current_page>$this->pages_count-3) {
                $min_page--;
            }
        }    
        $array = range($min_page,$max_page);
        $left = $current_page>1 ? $current_page-1 : null;
        $right = $current_page<$this->pages_count ? $current_page+1 : null;
        $result =  array(
            'pages' => $array,
            'pages_count' => $this->pages_count,
            'link' => $this->link_prefix,
            'active_page' => $current_page,
            'left' => $left,
            'right' => $right,
            'first' => $array[0]>1?1:null,
            'last' => $array[count($array)-1]<$this->pages_count?$this->pages_count:null,
            'items_count' => $this->items_count,
            'from_item' => ($current_page-1)*$this->limit+1,
            'to_item' => $current_page<$this->pages_count ? ($current_page)*$this->limit : $this->items_count,
            'limit' => $this->limit
        );
        return $result;
    }

    public function getLimitString($page, $limit=null){
        if(empty($limit)) $limit = $this->limit;
        return (($page-1)*$limit).",$limit";
    }
    
    public function getFromString($page, $limit=null){
        if(empty($limit)) $limit = $this->limit;
        return ($page-1)*$limit;
    }

    public function Links( $uri, $page, $get_parameters ){
         // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach( $get_parameters as $gk=>$gv) if( $gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        // ссылка пагинатора
        $this->link_prefix = rtrim( $uri, '/' ) . "/?"                                             // конечный слеш и начало GET-строки
                                  .implode( '&', $get_in_paginator )                // GET-строка
                                  .(empty( $get_in_paginator ) ? "" : '&' )."page=";// параметр для номера страницы
        if( $this->pages_count > 0 && $this->pages_count < $page ){
            Header('Location: '.Host::getWebPath( $this->link_prefix . $this->pages_count ) ) ;
            exit(0);
        }
        if( $this->pages_count > 1 ) Response::Setarray( 'paginator', $this->Get( $page ) ) ;
    }
}
?>
