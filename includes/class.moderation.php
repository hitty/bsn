<?php
/**    
* обработка объектов находящихся в таблицах *_new
*/

class Moderation extends EstateItem{
    public $moderated_status = 1; // статус модерации (1-ожидание, 2-стоимость маленькая, 3-стоимость большая, 4-нет адреса)
    public $hash = ''; // получаемый hash
    public $merged = false; // флаг склейки
    protected $type = 0; // тип недвижимости
    protected $action = 'insert'; // тип обновления варианта в основной таблице (update , insert)
    protected $where_clause = ''; // условие обновления варианта в основной таблице
    public $id = ''; // id объекта в основной таблице
    /**
    * создание объекта    * 
    * @param string $type - тип недвижимости
    * @param integer $id - id объекта в таблице *_new
    */    
    public function __construct($type, $id=false){
        parent::__construct($type, $id, 'new');  
        $this->type = $type;
    }
    /**
    * начало модерации
    * $admin - модерация руками из админки (true/false)
    */
    public function checkObject($id_new=false,$admin_moderated=false){
        global $db;
        //проверка статуса модерации
        $this->moderated_status = $this->getModerateStatus();
        if($this->moderated_status!=1) { // не прошел модерацию
            $this->makeHash();
            //поиск на похожий для склейки
            $item = $db->fetch("SELECT `id` FROM ".$this->work_table_new." WHERE `id_user`=? AND `hash`=? AND id!=?",$this->data_array['id_user'], $this->data_array['hash'], $this->data_array['id']);
            if(!empty($item)) $db->query("DELETE FROM ".$this->work_table_new." WHERE id=?",$item['id']);
            $db->query("UPDATE ".$this->work_table_new." SET `id_moderate_status` = ?, `hash` = ? WHERE `id` = ?", $this->moderated_status, $this->data_array['hash'], $this->data_array['id']);          
            return array('ok'=>false,'reason'=>'Не прошел модерацию', 'status'=>$this->moderated_status);
        } else { // прошел модерацию
            $this->data_changed = true;
            $id_object = !empty($this->data_array['id_object'])?$this->data_array['id_object']:0;
            $this->Save('new');  // сохранить все данные + новый hash  
            $this->data_array['date_change'] = date('Y-m-d H:i:s');
            if($id_object>0){ //если объект на перемодерации
                $this->action = 'update';
                $this->where_clause = $this->work_table.".id = ".$id_object;
                //id в основной таблице
                $this->id = $id_object;
            } else { // новый объект
                //проверка на существование похожего объекта в основной таблице по hash и id_user  или по id_user и external_id
                if($this->data_array['external_id']>0)  $similar = $db->fetch("SELECT `id`, `date_in` FROM ".$this->work_table." WHERE `external_id` = ".$this->data_array['external_id']." AND `id_user` = ".$this->data_array['id_user']."");
                else $similar = $db->fetch("SELECT `id`,`published`, `date_in` FROM ".$this->work_table." WHERE `id_user` = ".$this->data_array['id_user']." AND `hash` = '".$this->data_array['hash']."'");
                
                $this->id = $similar['id'];
                if($this->id > 0) { // есть похожий объект
                    $this->action = 'update';
                    $this->where_clause = $this->work_table.".id = ".$this->id;
                    if(!empty($similar['published']) && $similar['published']==1) $this->merged = true;
                    $this->data_array['date_in']= $similar['date_in'];
                } else {
                    $this->data_array['date_in']=date('Y-m-d H:i:s');
                    $this->merged = false;
                    $this->action = 'insert'; // нет похожего, вставка нового
                }
            }
            // переопределение id для таблицы _new 
            $id_new = $this->data_array['id'];
            //время последней модерации (для модерируемых вручную отличается от времени поступления)
            $this->data_array['date_moderated'] = date("Y-m-d H:i:s");
            if(!empty($admin_moderated)) $this->data_array['admin_moderated'] = 1;
            if($this->action == 'update') {
                $this->data_array['id'] = $this->id;
                
                unset($this->data_array['id_object']);
                $this->data_array['published']=1;
                $result = $db->updateFromArray($this->work_table, $this->data_array,'id');
            }
            else {
                unset($this->data_array['id_object']);
                $this->data_array['published']=1;
                $result = $db->insertFromArray($this->work_table, $this->data_array,'id');
                $this->id = $db->insert_id;
            }
            if($result){
                // обновление графики объекта
                if($this->data_array['info_source']!=5) $this->updateObjectGraphics($this->id, $id_new);
                //удаление записи в таблице new
                $res = $db->query("DELETE FROM ".$this->work_table_new." WHERE `id` = ?",$id_new);
                return $res;
            } else return false;
        }
    }
    /**
    * получение статуса модерации объекта
    */
    public function getModerateStatus($data=false){
        if(!empty($data)) $this->data_array = $data;
        if((empty($this->data_array['id_street']) && $this->data_array['txt_addr'] == '' && $this->type != 'country') || 
           (empty($this->data_array['id_street']) && $this->data_array['txt_addr'] == '' && empty($this->data_array['id_area']) && 
            empty($this->data_array['id_city']) && empty($this->data_array['id_place']) && $this->type == 'country') ) return 4; // нет адреса (для загородной теперь улица не обязательна)
        //elseif($this->type == 'build' && !empty($this->data_array['id_type_object']) ) return 5; // не указан тип объекта
        switch($this->type){
            case 'live':
                if( ($this->data_array['id_type_object']==1 || $this->data_array['id_type_object']==2 ) && 
                    ( (!Validate::isDigit((int)$this->data_array['rooms_total']) && !Validate::isDigit((int)$this->data_array['rooms_sale'])) ||
                      ((int) $this->data_array['rooms_total'] < 0 || (int) $this->data_array['rooms_sale'] < 0)
                     ) ) return 6; // не указано кол-во комнат
                break;
            case 'build':
                if(empty($this->data_array['id_street']) && $this->data_array['txt_addr'] == '') return 4; // нет адреса
                elseif( empty($this->data_array['rooms_total']) && 
                      (!Validate::isDigit((int)$this->data_array['rooms_sale']) || $this->data_array['rooms_sale']<0) ) return 6; // не указано кол-во комнат
                break;
        }
        return $this->moderateCost();
        
    }
    
    /**
    * получение статуса модерации объекта по стоимости
    */
    public function moderateCost($data = false){
        if(!empty($data)) $this->data_array = $data;  
        switch($this->type){
            case 'live':
                if($this->data_array['rent']==1){ // для аренды
                    if(!empty($this->data_array['by_the_day']) && $this->data_array['by_the_day']==1){ // для аренды посуточно
                        if($this->data_array['cost']<100) return 2; // для аренды посуточно с подозрительно маленькой ценой
                        elseif($this->data_array['cost']>20000) return 3; // для аренды посуточно с подозрительно большой ценой
                    } else { // для аренды долгосрочной
                        if($this->data_array['cost']<500) return 2; // для аренды долгосрочной с подозрительно маленькой ценой
                        elseif($this->data_array['cost']>650000) return 3; // для аренды долгосрочной с подозрительно большой ценой
                    }
                } else { // для продажи
                        if($this->data_array['cost']<500000) return 2; // для продажи с подозрительно маленькой ценой
                        elseif($this->data_array['cost']>1000000000) return 3; // для продажи с подозрительно большой ценой
                }
                break;
            case 'build':
                if($this->data_array['cost']<500000) return 2; // для продажи с подозрительно маленькой ценой
                elseif($this->data_array['cost']>1000000000) return 3; // для продажи с подозрительно большой ценой
                break;
            case 'commercial':
                if($this->data_array['rent']==1){ // для аренды
                    $max_cost = !empty($this->data_array['id_type_object']) && $this->data_array['id_type_object'] == 20 ? 35000000 : 10000000;
                    if(empty($this->data_array['cost']) || $this->data_array['cost']<3000)  return 2; // для аренды с подозрительно маленькой ценой
                    elseif(!empty($this->data_array['cost2meter']) && $this->data_array['cost2meter']<100) return 2; // для аренды с подозрительно маленькой ценой
                    elseif($this->data_array['cost']>$max_cost){
                        if(!empty($this->data_array['square_full'])){
                            if($this->data_array['cost']/$this->data_array['square_full']>50000) return 3; // для аренды с подозрительно большой ценой
                        } else return 3;
                    } elseif(!empty($this->data_array['cost2meter']) && $this->data_array['cost2meter']>50000) return 3; // для аренды с подозрительно большой ценой
                } else { // для продажи
                    if(empty($this->data_array['cost']) || $this->data_array['cost']<100000) return 2; // для продажи с подозрительно маленькой ценой
                    elseif(!empty($this->data_array['cost2meter']) && $this->data_array['cost2meter']<1000) return 2; // для продажи с подозрительно маленькой ценой
                    elseif($this->data_array['cost']>1000000000){
                        if(!empty($this->data_array['square_full'])){
                            if($this->data_array['cost']/$this->data_array['square_full']>5000000) return 3; // для аренды с подозрительно большой ценой
                        } else return 3;
                    } elseif(!empty($this->data_array['cost2meter']) && $this->data_array['cost2meter']>5000000) return 3; // для продажи с подозрительно большой ценой
                }
                break;
            case 'country':
                if($this->data_array['rent']==1){ // для аренды
                    if(!empty($this->data_array['by_the_day']) && $this->data_array['by_the_day']==1){ // для аренды посуточно
                        if($this->data_array['cost']<100) return 2; // для аренды посуточно с подозрительно маленькой ценой
                        elseif($this->data_array['cost']>26000) return 3; // для аренды посуточно с подозрительно большой ценой
                    } else {
                        if($this->data_array['cost']<3000) return 2; // для аренды с подозрительно маленькой ценой
                        elseif($this->data_array['cost']>2000000) return 3; // для аренды с подозрительно большой ценой
                    }
                } else { // для продажи
                    if($this->data_array['cost']<50000) return 2; // для продажи с подозрительно маленькой ценой
                    elseif($this->data_array['cost']>1000000000) return 3; // для продажи с подозрительно большой ценой
                }
                break;
            }
            return 1;
        }        
          
    
    /**
    * перенос графики из _new в основные таблицы
    * @param integer $id - id в основной таблице
    * @param  integer $id_new - id в таблице new
    */
    private function updateObjectGraphics($id, $id_new){
        global $db;
        //фотки в таблице new
        $list_new = $db->fetchall("SELECT `id`, `external_img_src` FROM ".$this->work_photos_table." WHERE `id_parent_new`=".$id_new);
        if(!empty($list_new)){
            //фотки в основной таблице
            $list = $db->fetchall("SELECT `id`, `external_img_src` FROM ".$this->work_photos_table." WHERE `id_parent`=".$id);
            if(!empty($list)){
                foreach($list as $key=>$value){
                    $delete = true;
                    foreach($list_new as $key_new=>$value_new){
                        if($value_new['external_img_src'] == $value['external_img_src']) {
                            //если эта фотография была главной, переназначаем
                            if($this->data['id_main_photo']) $this->data['id_main_photo'] = 
                            Photos::Delete($this->type,$value_new['id'],'_new'); 
                            $delete = false;
                            break;
                        }
                    }
                    if($delete==true) Photos::Delete($this->type,$value['id']); 
                }
            }
            $db->query("UPDATE ".$this->work_photos_table." SET `id_parent` = ?, `id_parent_new` = 0 WHERE `id_parent_new`=?",$id, $id_new);
        }
        //читаем id окончательного списка фотографий
        $list = explode(',',$db->fetch("SELECT GROUP_CONCAT(`id`) AS ids FROM ".$this->work_photos_table." WHERE `id_parent`=".$id)['ids']);
        //если id_main_photo не установлен, или почему-то не попал в список $list, берем первую из тех, что есть 
        if((empty($this->data_array['id_main_photo']) || !in_array($this->data_array['id_main_photo'],$list)) && Photos::getMainPhoto($this->type, $id) == false) Photos::setMain($this->type, $id, 0);
    }
    /**
    * удаление фото из таблицы и с сервера
    * @param integer $name - имя фото
    */
    private function deletePhoto($id){    
    }
    /**
    * расчет хеша объекта
    * 
    */
    public function makeHash(){
        foreach($this->hash_fields as $fieldname) {
            if(empty($this->data_array[$fieldname])) $this->data_array[$fieldname] = 0;
        }
        parent::makeHash();
        $estate_data = parent::getData();
        return $estate_data['hash'];
    }
}
?>