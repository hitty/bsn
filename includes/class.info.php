<?php
/**    
* Информационный контент. Обобщенный класс
*/

abstract class Content {
    public $__table='';                 // таблица в базе данных
    public $__structure = [];      // список полей в таблице
    public $__id_field='';              // название поля с первичным ключем (ID объекта)

    /**
    * создание объекта    * 
    * @param string таблица в БД
    * @param string название поля с ID в таблице
    */
    public function __construct($table=null, $id_field=null, $structure=null){
        if(!empty($table)) $this->__table = $table;
        if(!empty($id_field)) $this->__id_field = $id_field;
        if(!empty($structure) && is_array($structure)) {
            $this->__structure = $structure;
            $this->clear();
        }
    }

    /**
    * очистка объекта
    */
    protected function clear(){
        foreach($this->__structure as $key){
            $prop_name = 'field_'.$key;
            $this->$prop_name = null;
        }
    }
    
    /**
    * загрузка данных из БД
    * @param integer id объекта
    * @return boolean успешность загрузки
    */
    public function load($id){
        global $db;
        $row = $db->fetch("SELECT * FROM ".$this->__table." WHERE ".$this->__table.".".$this->__id_field."=?",$id);
        if($row) {
            $this->clear();
            foreach($this->__structure as $key){
                $prop_name = 'field_'.$key;
                $this->$prop_name = $row[$key];
            }
            return true;
        } return false;
    }
    
    /**
    * запись данных объекта в БД
    * @return boolean успешность сохранения
    */
    public function save(){
        global $db;
        $ins = $upd = [];
        foreach($this->__structure as $key){
            if($key!=$this->__id_field){
                $prop_name = "field_".$key;
                switch(true){            
                    case is_null($this->$prop_name):
                        $val = "NULL";
                        break;
                    case Validate::Numeric($this->$prop_name):
                        $val = $this->$prop_name;
                        break;
                    default:
                        $val = "'".$this->$prop_name."'";
                }
                $ins[] = $val;
                $upd[] = "`".$key."`=".$val;
            }
        }
        $id_field_name = "field_".$this->__id_field;
        if(empty($this->$id_field_name)){
            $res = $db->query("INSERT ".$this->__table." (`".implode(`,`,$this->__structure)."`)
                           VALUES (".implode(',',$ins).")");
            if($res) return $db->insert_id();
        } else {
            $res = $db->query("UPDATE ".$this->__table." 
                               SET ".implode(', ',$upd)."
                               WHERE ".$this->__table.".".$this->__id_field."=?",$this->$id_field_name);
            if($res) return true;
        }
        return false;
    }
    
    /**
    * удаление объекта из БД
    * @param integer ID объекта
    * @return boolean успех операции
    */
    public function delete($id=null){
        $id_field_name = 'field'.$this->__id_field;
        if(empty($id)) $id = $this->$id_field_name;
        if(empty($id)) return false;
        $res = $db->query("DELETE FROM ".$this->__table." WHERE ".$this->__table.".".$this->__id_field."=?", $id);
        if($res) $this->clear();
        return $res;
    }
}


/**
* Новостной кнтент.
*/
class NewsContent extends Content {
    public $__table = "bsnweb.news";
    public $__id_field = "id";
    public $__structure = array('id', 'id_category', 'id_region', 'title', 'content', 'content_cut',
            'author', 'author_url', 'image', 'visibility', 'datetime', 'keywords', 'r_live', 'r_build',
            'r_com', 'r_country', 'r_garage', 'r_business', 'r_trade', 'r_mortgage', 'r_insurance',
            'r_elite', 'r_foreign', 'r_invest', 'yandex_news', 'redirect');
    
    public $__relations = array(
        'id_category' => array('bsnweb.news_category','id','title','category_title'),
        'id_region'=> array('bsnweb.news_region','id','title','region_title')
    );
    
    public function __construct(){
        parent::__construct($this->__table, $this->__id_field, $this->__structure);
    }
    
    /**
    * загрузка объекта из БД
    * @param integer ID объекта
    * @param boolean выполнять связь таблицы со справочниками
    * @return boolean успешная загрузка
    */
    public function load($id,$makejoins=false){
        global $db;
        $joins = $fields = "";
        if($makejoins){
            foreach($this->__relations as $key=>$tbl){
                $fields .= ", ".$tbl[0].".".$tbl[2]." as ".$tbl[3];
                $joins .= "LEFT JOIN ".$tbl[0]." ON ".$tbl[0].".".$tbl[1]."=".$this->__table.".".$key." ";
            }
        }
        $row = $db->fetch("SELECT ".$this->__table.".*".$fields." FROM ".$this->__table." ".$joins."
                           WHERE ".$this->__table.".".$this->__id_field."=?"
                           ,$id);
        if($row) {
            $this->clear();
            foreach($this->__structure as $key){
                $prop_name = 'field_'.$key;
                $this->$prop_name = $row[$key];
            }
            if($makejoins){
                foreach($this->__relations as $key=>$tbl){
                    $prop_name = 'field_'.$tbl[3];
                    $this->$prop_name = $row[$tbl[3]];
                }
            }
            return true;
        } return false;
    }
           
}
?>