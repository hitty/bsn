<?php
/**
* Конвертация обработанных строк/нодов файлов в поля объектов недвижимости
*/
abstract class Geo {
    
    /**
    * получение всех геоданных из сложного текстового адреса
    * @param string $txt_addr - текстовый адрес
    * @return array of array
    */    
    public function getTxtGeodata($txt_addr=false){
        global $db;
        $this->fields['txt_addr'] = $txt_addr;
        //не убираем все, что в скобках (20.08.2015)
        //$txt_addr = preg_replace('/\(.*\)/sui','',$txt_addr);
        //убираем все кроме букв, точек, запятых, пробелов, скобок и все в нижний регистр
        $txt_addr = preg_replace('/[^А-я-,0-9\(\)\.\s]/sui','',mb_strtolower($txt_addr,"UTF-8"));
        //заменяем обозначения улицы на корректные
        $txt_addr=str_replace(array('пр.','просп.','проспект','пр-кт','бульвар','аллея','ал.','улица','линия В.О.','линия В.О','шоссе','пер.','переулок','дорога'),array(' пр ',' пр ',' пр ',' пр ',' бул ',' алл ',' алл ',' ул ',' В.О. линия ','В.О. линия',' шос ',' пер ',' пер ',' дор '),$txt_addr);
        
        //заменяем обозначения локаций на корректные
        $txt_addr=str_replace(array('область','район','р-он','поселок','микрорайон','мик-н','мкр-н'),array('обл','р-н','р-н','пос','мкр','мкр','мкр'),$txt_addr);
        //заменяем "дер." на " деревня "
        $txt_addr = preg_replace('/дер\.?\s?[^евня]/sui',' деревня ',$txt_addr);
        //заменяем "пос." на " п "
        $txt_addr = trim(preg_replace('/пос\.?(\s|[^елок])/sui',' п ',$txt_addr));
        //заменяем "п." на " п "
        $txt_addr = trim(preg_replace('/^\s?п\./sui',' п ',$txt_addr));
        //заменяем "село" на "пос"
        $txt_addr = " ".$txt_addr." ";
        $txt_addr = trim(preg_replace('/(?<=([^А-я]{1}))село(?=([^А-я]{1}))/sui',' пос ',$txt_addr));
        //заменяем "пл." на "площадь"
        $txt_addr = trim(preg_replace('/пл\.?(?=([^А-я]))/sui',' площадь ',$txt_addr));
        //заменяем -ого, -ая -я, ...
        $txt_addr = preg_replace('/(?(?<=[0-9])-[а-я]+)/sui','',$txt_addr);
        //заменяем &nbsp на пробелы
        $txt_addr = preg_replace('/&nbsp;/sui',' ',$txt_addr);
        //несколько пробелов подряд заменяем на один пробел
        $txt_addr = preg_replace('/[\s]+/',' ',$txt_addr);
        //читаем типы локаций и их a_level
        $exploders_with_levels = $db->fetchall("SELECT shortname,shortname_cut,MIN(a_level) as level FROM ".$this->sys_tables['geodata']." GROUP BY shortname");
        //составляем списки типов локаций и сокращенных типов локаций
        foreach($exploders_with_levels as $object){
            $exploders[] = $object['shortname'];
            $exploders_cut[] = $object['shortname_cut'];
        }
        //разбиваем текст по запятым
        $comma_blocks = explode(',',str_replace('.','',$txt_addr));
        foreach ($comma_blocks as $cb_key=>$comma_block){
            $txt_addr = trim($comma_block);
            
            //разбиваем блок по пробелам
            //$txt_blocks = explode(' ',$txt_addr);
            //теперь разбиваем так, чтобы проглотить &nbsp;
            $txt_blocks = preg_split('/\s/sui',$txt_addr);
            $txt_blocks = array_filter($txt_blocks);
            //если уже прочитали улицу, или пытались прочитать, выходим
            if(!empty($this->fields['id_street']) || $a_level == 5) break;
            foreach($txt_blocks as $key=>$value){
                //ищем в разделителях из базы не учитывая точки, пробелы и регистр
                $value = mb_strtolower(trim(preg_replace('/\./sui','', $value)),"UTF-8");
                if (in_array(trim(preg_replace('/\./sui','',$value)),$exploders)||in_array(trim(preg_replace('/\./sui','',$value)),$exploders_cut)){
                    //если предыдущий элемент не пуст, значит конструкция вида "... Мурино село"
                    if(!empty($txt_blocks[$key-1])){
                        $k = $key-1;
                        //убираем из строки разделитель, который мы нашли
                        unset($txt_blocks[$key]);
                        $addr_block = "";
                        //берем все, что было до("... Мурино"), соединяем с разделяющим блоком("село")
                        while (!empty($txt_blocks[$k])){
                            $addr_block = $txt_blocks[$k].' '.$addr_block;
                            //убираем подобранные блоки из строки
                            unset($txt_blocks[$k]);
                            --$k;
                        }
                        //получаем ключ разделителя, по которому найдем a_level этого типа объекта
                        $exploder_key = array_search($value,$exploders);
                        if(empty($exploder_key)) $exploder_key = array_search($value,$exploders_cut);
                        
                        //читаем a_level
                        $a_level = $exploders_with_levels[$exploder_key]['level'];
                        
                        //правка для линий В.о.
                        if($exploder_key == 23 && ($this->fields['id_district'] == 3 && $a_level == 5)){
                            if(!preg_match('/в\.о\./sui',$addr_block)) $addr_block = trim($addr_block)." В.О.";
                        }
                        
                        $addr_block = trim($addr_block);
                        
                        // в условие уже найденные геоданные(кроме улицы, так как тогда уже нечего искать)
                        $location_where = [];
                        $order_by_params = [];
                        $location_where[] = "id_region = ".(empty($this->fields['id_region'])?0:$this->fields['id_region']);
                        $order_by_params[] = "id_region = ".(empty($this->fields['id_region'])?0:$this->fields['id_region'])." DESC";
                        $city_place_params = [];
                        
                        if($a_level > 3){
                            //если район один из 5, то он обязательно есть
                            if(!empty($this->fields['id_district']) && in_array($this->fields['id_district'],array(27,29,38,43,53))) $location_where[] = "id_district = ".$this->fields['id_district'];
                            else $location_where[] = " (id_district = ".(empty($this->fields['id_district'])?0:$this->fields['id_district'])." OR id_district = 0)";
                            $order_by_params[] = " id_district = ".(empty($this->fields['id_district'])?0:$this->fields['id_district'])." DESC";
                        } 
                        if($a_level >= 3){
                            $location_where[] = "(id_area = ".(empty($this->fields['id_area'])?0:$this->fields['id_area'])." OR id_area = 0)";
                            $order_by_params[] = "id_area = ".(empty($this->fields['id_area'])?0:$this->fields['id_area'])." DESC";
                        } 
                        if($a_level > 3){
                            $location_where[] = "(id_city = ".(empty($this->fields['id_city'])?0:$this->fields['id_city'])." OR id_city = 0)";
                            $order_by_params[] = "id_city = ".(empty($this->fields['id_city'])?0:$this->fields['id_city'])." DESC";
                            if(!empty($this->fields['id_city'])) $city_place_params[] = "id_city = ".$this->fields['id_city'];
                        } 
                        if($a_level > 3){
                            $location_where[] = "(id_place = ".(empty($this->fields['id_place'])?0:$this->fields['id_place'])." OR id_place = 0)";
                            $order_by_params[] = "id_place = ".(empty($this->fields['id_place'])?0:$this->fields['id_place'])." DESC";
                            if(!empty($this->fields['id_place'])) $city_place_params[] = "id_place = ".$this->fields['id_place'];
                        } 
                        
                        $city_place_params = (empty($city_place_params)?"":",(".implode(',',$city_place_params).") AS place_match");
                        
                        //сортируем по совпадениям с параметрами поиска - чтобы выбиралось самое подходящее
                        if(!empty($location_where)){
                            $location_where = implode(" AND ",$location_where);
                            $order_by_params = implode(", ",$order_by_params)."";
                            if(!empty($city_place_params)) $order_by_params = "place_match DESC, ".$order_by_params;
                        } 
                        else $location_where = "";
                        $_geo = [];
                        if(preg_match('/линия/sui',$value))
                            $addr_block = mb_ereg_replace("(?<=[0-9])\s","-я ",$addr_block);
                        //город - единственное, у чего два варианта a_level
                        if($value == 'г'||$value == 'город'){
                            //ищем(shortname не учитывается) в базе соответствие найденному куску с таким a_level
                            $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                WHERE `offname` = ? AND 
                                       (a_level = 1 OR a_level = 3) "
                                       .(!empty($location_where)?" AND ".$location_where:"")."
                                       ".(!empty($order_by_params)?" ORDER BY ".$order_by_params:"")."
                                       LIMIT 1",
                                       $addr_block
                                 );
                        }
                        else{
                            
                            //ищем (shortname учитывается, то что не указано считаем равным 0)
                            $_geo = $db->fetch("SELECT *".$city_place_params." FROM ".$this->sys_tables['geodata']."
                                    WHERE `offname` = '".$addr_block."' AND
                                           a_level = '".$a_level."' AND
                                           `shortname` = '".$exploders_with_levels[$exploder_key]['shortname']."'
                                           ".(!empty($order_by_params)?" ORDER BY ".$order_by_params:""));
                            //если район области найден, и он не совпадает с тем что нашли, чистим
                            if(!empty($this->fields['id_area']) && !empty($_geo['id_area']) && $_geo['id_area']!=$this->fields['id_area'] && $a_level == 5) $_geo = [];
                            //ищем без учета района города, что не указано считаем равным 0
                             if(empty($_geo)){
                                
                                $location_where = [];
                                $location_where[] = "id_region = ".(empty($this->fields['id_region'])?0:$this->fields['id_region']);
                                //если район один из 5, то он обязательно есть
                                if(!empty($this->fields['id_district']) && in_array($this->fields['id_district'],array(27,29,38,43,53))) $location_where[] = "id_district = ".$this->fields['id_district'];
                                if($a_level >= 3) $location_where[] = "id_area = ".(empty($this->fields['id_area'])?0:$this->fields['id_area']);
                                if($a_level > 3) $location_where[] = "id_city = ".(empty($this->fields['id_city'])?0:$this->fields['id_city']);
                                if($a_level > 3) $location_where[] = "id_place = ".(empty($this->fields['id_place'])?0:$this->fields['id_place']);
                                if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                                else $location_where = "";
                                $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                    WHERE `offname` = ? AND ".$location_where."
                                           a_level = ? AND
                                           `shortname` = ?
                                           LIMIT 1",
                                           $addr_block,
                                           $a_level,
                                           $exploders_with_levels[$exploder_key]['shortname']
                                     );
                                if(empty($_geo)){
                                    $location_where = [];
                                    if(isset($this->fields['id_region'])) $location_where[] = "id_region = ".$this->fields['id_region'];
                                    
                                    if(!empty($this->fields['id_district']) && in_array($this->fields['id_district'],array(27,29,38,43,53))) $location_where[] = "id_district = ".$this->fields['id_district'];
                                    elseif($a_level < 5 && isset($this->fields['id_district'])) $location_where[] = "id_district = ".$this->fields['id_district'];
                                    
                                    if(isset($this->fields['id_area']) && $a_level > 3) $location_where[] = "id_area = ".$this->fields['id_area'];
                                    if(isset($this->fields['id_city']) && $a_level > 3) $location_where[] = "id_city = ".$this->fields['id_city'];
                                    if(isset($this->fields['id_place']) && $a_level > 3) $location_where[] = "id_place = ".$this->fields['id_place'];
                                    if(isset($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                                    else $location_where = "";
                                    
                                    //ищем(shortname учитывается) в базе соответствие найденному куску с таким a_level
                                    $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                            WHERE `offname` = ? AND ".$location_where."
                                                   a_level = ? AND
                                                   `shortname` = ?
                                                   LIMIT 1",
                                                   $addr_block,
                                                   $a_level,
                                                   $exploders_with_levels[$exploder_key]['shortname']
                                             );
                                    //если и так не получилось, ищем без учета shortname
                                    if(empty($_geo)){
                                        $this->fields['addr_problems'] = 1;
                                        $addr_block = preg_replace('/\./sui','%',$addr_block);
                                        $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                            WHERE `offname` LIKE ? AND ".$location_where."
                                                   a_level = ?
                                                   LIMIT 1",
                                                   $addr_block,
                                                   $a_level
                                             );
                                        //если все еще ничего, пробуем искать без учета id_city и id_place
                                        if(empty($_geo)){
                                            $location_where = "";
                                            if(isset($this->fields['id_region'])) $location_where = "id_region = ".$this->fields['id_region']." AND ";
                                            
                                            if(!empty($this->fields['id_district']) && in_array($this->fields['id_district'],array(27,29,38,43,53))) $location_where .= "id_district = ".$this->fields['id_district']." AND ";
                                            elseif($a_level < 5 && !empty($this->fields['id_district'])) $location_where .= "id_district = ".$this->fields['id_district']." AND ";
                                            
                                            if(!empty($this->fields['id_area'])) $location_where .= "id_area = ".$this->fields['id_area']." AND ";
                                            $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                                WHERE `offname` = ? AND ".$location_where."
                                                       a_level = ? AND
                                                       shortname = ?
                                                       LIMIT 1",
                                                       $addr_block,
                                                       $a_level,$exploders_with_levels[$exploder_key]['shortname']
                                                 );
                                        }
                                    }
                                }
                            }
                            
                        }
                        
                        //если что-то нашли,  новые данные и дальше этот блок не заполняем
                        if(!empty($_geo)){
                            if(empty($this->fields['id_street'])) $this->fields['id_street'] = (!empty($_geo['id_street']))?$_geo['id_street']:0;
                            if(empty($this->fields['id_place'])) $this->fields['id_place'] = (!empty($_geo['id_place']))?$_geo['id_place']:0;
                            if(empty($this->fields['id_city'])) $this->fields['id_city'] = (!empty($_geo['id_city']))?$_geo['id_city']:0;
                            if(empty($this->fields['id_area'])) $this->fields['id_area'] = (!empty($_geo['id_area']))?$_geo['id_area']:0;
                            if(empty($this->fields['id_district'])) $this->fields['id_district'] = (!empty($_geo['id_district']))?$_geo['id_district']:0;
                            if(empty($this->fields['id_region'])) $this->fields['id_region'] = (!empty($_geo['id_region']))?$_geo['id_region']:0;
                            $addr_blocks[] = $addr_block.' '.$value;
                        }else
                        //если это улица, ее не нашли, добавляем в таблицу
                        if($a_level == 5){
                            //смотрим нет ли уже такого в таблице
                            $exists_already = $db->fetch("SELECT id 
                                                          FROM ".$this->sys_tables['addresses_to_add']." 
                                                          WHERE id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_district = ? AND offname = ? AND shortname = ?",
                                                          $this->fields['id_region'],
                                                          (empty($this->fields['id_area'])?0:$this->fields['id_area']),
                                                          (empty($this->fields['id_city'])?0:$this->fields['id_city']),
                                                          (empty($this->fields['id_place'])?0:$this->fields['id_place']),
                                                          (empty($this->fields['id_district'])?0:$this->fields['id_district']),
                                                          $addr_block,$exploders_with_levels[$exploder_key]['shortname']);
                            if(empty($exists_already))
                                $db->query("INSERT INTO ".$this->sys_tables['addresses_to_add']." (id_user,file_format,addr_source,id_region,id_area,id_city,id_place,id_district,offname,shortname,shortname_cut,date_in) 
                                            VALUES (?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)",
                                            $this->fields['id_user'],
                                            $this->file_format,
                                            $this->fields['addr_source'],
                                            $this->fields['id_region'],
                                            (empty($this->fields['id_area'])?0:$this->fields['id_area']),
                                            (empty($this->fields['id_city'])?0:$this->fields['id_city']),
                                            (empty($this->fields['id_place'])?0:$this->fields['id_place']),
                                            (empty($this->fields['id_district'])?0:$this->fields['id_district']),
                                            $addr_block,
                                            $exploders_with_levels[$exploder_key]['shortname'],
                                            $exploders_with_levels[$exploder_key]['shortname_cut']);
                        }
                        //если прочитали улицу, выходим (ситуации когда деревня, город или район указаны после улицы не разбираются)
                        if(!empty($this->fields['id_street']) || empty($txt_blocks)) break;
                    }
                    else{
                        //если предыдущий элемент пуст, значит конструкция вида "село Мурино ..."
                        $k = $key+1;
                        //убираем из строки разделитель, который мы нашли
                        unset($txt_blocks[$key]);
                        $addr_block = "";
                        //здесь будем хранить адрес, который распознался до конца блока
                        $saved_geo = [];
                        //подбираем блоки после разделителя, после каждого проверяя по базе, не нашли ли что-нибудь
                        while((!empty($txt_blocks[$k])) && !in_array($txt_blocks[$k],$exploders)){
                            //подбираем блок, проверяем в базе, если не нашли, идем дальше пока не наткнемся на следующий разделитель
                            $addr_block = trim($addr_block.' '.$txt_blocks[$k]);
                            //убираем подобранный блок из строки
                            unset($txt_blocks[$k]);
                            //получаем ключ разделителя, по которому найдем a_level этого типа объекта
                            $exploder_key = array_search($value,$exploders);
                            if(empty($exploder_key)) $exploder_key = array_search($value,$exploders_cut);
                            //читаем a_level
                            $a_level = $exploders_with_levels[$exploder_key]['level'];
                            
                            //правка для линий В.о.
                            if($exploder_key == 23 && ($this->fields['id_district'] == 3 && $a_level == 5)){
                                if(!preg_match('/в\.о\./sui',$addr_block)) $addr_block = trim($addr_block)." В.О.";
                            }
                            
                            // в условие уже найденные геоданные(кроме улицы, так как тогда уже нечего искать)
                            $location_where = [];
                            $location_where[] = "id_region = ".(empty($this->fields['id_region'])?0:$this->fields['id_region']);
                            if($a_level > 3) $location_where[] = "id_area = ".(empty($this->fields['id_area'])?0:$this->fields['id_area']);
                            if($a_level < 5) $location_where[] = "id_district = ".(empty($this->fields['id_district'])?0:$this->fields['id_district']);
                            if($a_level > 3) $location_where[] = "id_city = ".(empty($this->fields['id_city'])?0:$this->fields['id_city']);
                            if($a_level > 3) $location_where[] = "id_place = ".(empty($this->fields['id_place'])?0:$this->fields['id_place']);
                            if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                            else $location_where = "";
                            $_geo = [];
                            //город - единственное, у чего два варианта a_level
                            if($value == 'г'||$value == 'город'){
                                //ищем(shortname не учитывается) в базе соответствие найденному куску с таким a_level
                                $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                    WHERE `offname` = ? AND ".$location_where."
                                           (a_level = 1 OR a_level = 3) 
                                           LIMIT 1",
                                           $addr_block
                                     );
                            }
                            else{
                                
                                //ищем (shortname учитывается, то что не указано считаем равным 0)
                                $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                        WHERE `offname` = ? AND ".$location_where."
                                               a_level = ? AND
                                               `shortname` = ?
                                               LIMIT 1",
                                               $addr_block,
                                               $a_level,
                                               $exploders_with_levels[$exploder_key]['shortname']
                                         );
                                if(empty($_geo)){
                                    
                                    $location_where = [];
                                    if(!empty($this->fields['id_region'])) $location_where[] = "id_region = ".$this->fields['id_region'];
                                    if(!empty($this->fields['id_area'])) $location_where[] = "id_area = ".$this->fields['id_area'];
                                    if($a_level < 5 && !empty($this->fields['id_district'])) $location_where[] = "id_district = ".$this->fields['id_district'];
                                    if(!empty($this->fields['id_city'])) $location_where[] = "id_city = ".$this->fields['id_city'];
                                    if(!empty($this->fields['id_place'])) $location_where[] = "id_place = ".$this->fields['id_place'];
                                    if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                                    else $location_where = "";
                                    
                                    //ищем(shortname учитывается) в базе соответствие найденному куску с таким a_level
                                    $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                            WHERE `offname` = ? AND ".$location_where."
                                                   a_level = ? AND
                                                   `shortname` = ?
                                                   LIMIT 1",
                                                   $addr_block,
                                                   $a_level,
                                                   $exploders_with_levels[$exploder_key]['shortname']
                                             );
                                    //если и так не получилось, ищем без учета shortname, заменяем точки на %, отмечаем что есть проблемы
                                    if(empty($_geo)){
                                        $this->fields['addr_problems'] = 1;
                                        $addr_block = preg_replace('/\./sui','%',$addr_block);
                                        $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                            WHERE `offname` LIKE ? AND ".$location_where."
                                                   a_level = ?
                                                   LIMIT 1",
                                                   $addr_block,
                                                   $a_level
                                             );
                                        //ксли все еще ничего, пробуем искать без учета id_area,id_city и id_place
                                        if(empty($_geo)){
                                            $location_where = "";
                                            if(isset($this->fields['id_region'])) $location_where = "id_region = ".$this->fields['id_region']." AND ";
                                            if(!empty($this->fields['id_area'])) $location_where .= "id_area = ".$this->fields['id_area']." AND ";
                                            $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                                WHERE `offname` = ? AND ".$location_where."
                                                       a_level = ? AND
                                                       shortname = ?
                                                       LIMIT 1",
                                                       $addr_block,
                                                       $a_level,$exploders_with_levels[$exploder_key]['shortname']
                                                 );
                                        }
                                    }
                                }
                            }
                            //если что-то нашли,  новые данные и дальше этот блок не заполняем
                            if(!empty($_geo)){
                                //если дальше еще что-то есть, лезем дальше. если нет - выходим
                                if( (!empty($txt_blocks[$k+1])) && !in_array($txt_blocks[$k+1],$exploders) ) $saved_geo = $_geo;
                                else{
                                    if(empty($this->fields['id_street'])) $this->fields['id_street'] = (!empty($_geo['id_street']))?$_geo['id_street']:0;
                                    if(empty($this->fields['id_place'])) $this->fields['id_place'] = (!empty($_geo['id_place']))?$_geo['id_place']:0;
                                    if(empty($this->fields['id_city'])) $this->fields['id_city'] = (!empty($_geo['id_city']))?$_geo['id_city']:0;
                                    if(empty($this->fields['id_area'])) $this->fields['id_area'] = (!empty($_geo['id_area']))?$_geo['id_area']:0;
                                    if(empty($this->fields['id_district'])) $this->fields['id_district'] = (!empty($_geo['id_district']))?$_geo['id_district']:0;
                                    if(empty($this->fields['id_region'])) $this->fields['id_region'] = (!empty($_geo['id_region']))?$_geo['id_region']:0;
                                    $addr_blocks[] = $addr_block.' '.$value;
                                    break;
                                }
                            }
                            ++$k;
                        }
                        //если что-то находили в процессе, дописываем (например пр. Авиаторов бла-бла-бла, отсюда вычленили проспект Авиаторов. а если бы был пр. Авиаторов Балтики, то распознали бы все)
                        if(empty($_geo) && !empty($saved_geo)){
                            $_geo = $saved_geo;
                            if(empty($this->fields['id_street'])) $this->fields['id_street'] = (!empty($_geo['id_street']))?$_geo['id_street']:0;
                            if(empty($this->fields['id_place'])) $this->fields['id_place'] = (!empty($_geo['id_place']))?$_geo['id_place']:0;
                            if(empty($this->fields['id_city'])) $this->fields['id_city'] = (!empty($_geo['id_city']))?$_geo['id_city']:0;
                            if(empty($this->fields['id_area'])) $this->fields['id_area'] = (!empty($_geo['id_area']))?$_geo['id_area']:0;
                            if(empty($this->fields['id_district'])) $this->fields['id_district'] = (!empty($_geo['id_district']))?$_geo['id_district']:0;
                            if(empty($this->fields['id_region'])) $this->fields['id_region'] = (!empty($_geo['id_region']))?$_geo['id_region']:0;
                            $addr_blocks[] = $addr_block.' '.$value;
                        } 
                        if($a_level == 5){
                            //смотрим нет ли уже такого в таблице
                            $exists_already = $db->fetch("SELECT id 
                                                          FROM ".$this->sys_tables['addresses_to_add']." 
                                                          WHERE id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_district = ? AND offname = ? AND shortname = ?",
                                                          $this->fields['id_region'],
                                                          (empty($this->fields['id_area'])?0:$this->fields['id_area']),
                                                          (empty($this->fields['id_city'])?0:$this->fields['id_city']),
                                                          (empty($this->fields['id_place'])?0:$this->fields['id_place']),
                                                          (empty($this->fields['id_district'])?0:$this->fields['id_district']),
                                                          $addr_block,$exploders_with_levels[$exploder_key]['shortname']);
                            if(empty($exists_already))
                                $db->query("INSERT INTO ".$this->sys_tables['addresses_to_add']." (id_user,file_format,addr_source,id_region,id_area,id_city,id_place,id_district,offname,shortname,shortname_cut,date_in) 
                                            VALUES (?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)",
                                            $this->fields['id_user'],
                                            $this->file_format,
                                            $this->fields['addr_source'],
                                            $this->fields['id_region'],
                                            (empty($this->fields['id_area'])?0:$this->fields['id_area']),
                                            (empty($this->fields['id_city'])?0:$this->fields['id_city']),
                                            (empty($this->fields['id_place'])?0:$this->fields['id_place']),
                                            (empty($this->fields['id_district'])?0:$this->fields['id_district']),
                                            $addr_block,
                                            $exploders_with_levels[$exploder_key]['shortname'],
                                            $exploders_with_levels[$exploder_key]['shortname_cut']);
                        }
                        //если прочитали улицу, выходим (ситуации когда деревня, город или район указаны после улицы не разбираются)
                        if(!empty($this->fields['id_street']) || empty($txt_blocks)) break;
                    }
                }elseif(count($txt_blocks) == 1){
                    //если в текстовом адресе например "Пискаревский" или "Комендантский" и все, пробуем подобрать только по offname
                    //ищем  в базе соответствие найденному куску с a_level = 5, отмечаем что есть проблемы
                    $this->fields['addr_problems'] = 1;
                    $location_where = [];$a_level = 5;$_geo = [];
                    if(!empty($this->fields['id_region'])) $location_where[] = "id_region = ".$this->fields['id_region'];
                    if(!empty($this->fields['id_area'])) $location_where[] = "id_area = ".$this->fields['id_area'];
                    if(!empty($this->fields['id_district'])) $location_where[] = "id_district = ".$this->fields['id_district'];
                    if(!empty($this->fields['id_place'])) $location_where[] = "id_place = ".$this->fields['id_place'];
                    elseif(empty($this->fields['id_district'])) $a_level = 4;
                    if(!empty($this->fields['id_city'])) $location_where[] = "id_city = ".$this->fields['id_city'];
                    else $a_level = 3;
                    if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                    //перебираем увеличивая a_level пока что-нибудь не найдем
                    while($a_level<=5 && empty($_geo)){
                        if(!empty($txt_blocks[0]))
                            $_geo = $db->fetch("SELECT * FROM ".$this->sys_tables['geodata']."
                                                WHERE `offname` = ? AND ".$location_where."
                                                       a_level = ?
                                                       ORDER BY id_city ASC,id_place ASC
                                                       LIMIT 1",
                                                       $txt_blocks[0],$a_level);
                        if(empty($_geo)) ++$a_level;
                    }
                    //если что-то нашли,  новые данные
                    if(!empty($_geo)){
                        $addr_block = $_geo['offname']." ".$_geo['shortname'];
                        if(empty($this->fields['id_street'])) $this->fields['id_street'] = (!empty($_geo['id_street']))?$_geo['id_street']:0;
                        if(empty($this->fields['id_place'])) $this->fields['id_place'] = (!empty($_geo['id_place']))?$_geo['id_place']:0;
                        if(empty($this->fields['id_city'])) $this->fields['id_city'] = (!empty($_geo['id_city']))?$_geo['id_city']:0;
                        if(empty($this->fields['id_area'])) $this->fields['id_area'] = (!empty($_geo['id_area']))?$_geo['id_area']:0;
                        if(empty($this->fields['id_district'])) $this->fields['id_district'] = (!empty($_geo['id_district']))?$_geo['id_district']:0;                        
                        if(empty($this->fields['id_region'])) $this->fields['id_region'] = (!empty($_geo['id_region']))?$_geo['id_region']:0;
                        $addr_blocks[] = $addr_block;
                    }
                }
            }
        }//foreach end
        
        //в последнем из оставшихся блоков ищем номер дома и корпус
        $num = "";$is_house = false;$is_corp = false;
        foreach($txt_blocks as $key=>$txt_block){
            if(empty($txt_block)) unset($txt_blocks[$key]);
            //если конструкция вида "18к2" и блок один, добавляем "д" в начало
            if(!preg_match('/дом|д\.?/',$txt_block)&&preg_match('/корп|к\.?/',$txt_block)&&count($txt_blocks) == 1){
                $txt_block = "д".$txt_block;
            }
            //если и дом и корпус в одном блоке, то между ними нет пробелов
            if(preg_match('/дом|д\.?/',$txt_block)&&preg_match('/корп|к\.?/',$txt_block)){
                $txt_block = preg_split('//u', "д18к2", -1, PREG_SPLIT_NO_EMPTY);;
                foreach($txt_block as $pos=>$char){
                    if($char == 'д') $is_house = true;
                    if($char == 'к') $is_corp = true;
                    if(Validate::isDigit($char)) $num .= $char;
                    else{
                        if($is_house && !empty($num)){
                            $this->fields['house'] = Convert::ToInt($num);
                            $num = "";
                            $is_house = false;
                        }
                        if($is_corp && !empty($num)){
                            $this->fields['corp'] = Convert::ToInt($num);
                            $num = "";
                            $is_corp = false;
                        }
                    }
                }
                if(!empty($num) && empty($this->fields['house'])) $this->fields['house'] = $num;
                elseif(!empty($num) && empty($this->fields['corp'])) $this->fields['corp'] = $num;
            }
            else{
                if(preg_match('/дом|д\.?/',$txt_block)){//конструкция вида "д12"
                    if(Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block))!= 0)
                        $this->fields['house'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                    else
                        $is_house = true;
                }
                elseif(preg_match('/корп|к\.?/',$txt_block)){//конструкция вида "к3"
                    if(Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block))!= 0)
                        $this->fields['corp'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                    else
                        $is_corp = true;
                }
                else{//конструкция вида "12"
                    if(Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block))!= 0){
                        if($is_house){
                            $is_house = false;
                            $this->fields['house'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                        }
                        elseif($is_corp){
                            $is_corp = false;
                            $this->fields['corp'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                        }
                        else{
                            //если это просто номер и номера дома или корпуса нет, заполняем их
                            if(empty($this->fields['house']))
                                $this->fields['house'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                            else
                                $this->fields['corp'] = Convert::ToInt(preg_replace('/[^0-9]/','',$txt_block));
                        }
                    }
                }
            }
        }
    }
    
    /**
    * получение адреса по $_geo
    * 
    * @param mixed $_geo          набор геоданных из geodata
    */
    public static function getAddress($_geo){
        global $db;
        $sys_tables = Config::$sys_tables;
            if(!empty($_geo['id_area']) && $_geo['id_region'] == 78) $_geo['id_area'] = 0;
            if(!empty($_geo['id_district'])){   
                $district = $db->fetch("SELECT title  FROM ".$sys_tables['districts']."  
                                        WHERE id = ?",
                                        $_geo['id_district']);
            }elseif(!empty($_geo['id_area'])){
                $area = $db->fetch("SELECT CONCAT(shortname, ' ', offname,' ЛО') as title  FROM ".$sys_tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=?",
                                    2,
                                    $_geo['id_region'],
                                    $_geo['id_area']
                );
            }
            
            if(!empty($_geo['id_city'])){   
                $city = $db->fetch("SELECT CONCAT(shortname, ' ', offname) as title  FROM ".$sys_tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? ",
                                    3,
                                    $_geo['id_region'],
                                    $_geo['id_area'],
                                    $_geo['id_city']
                );
            }            
            if(!empty($_geo['id_place'])){   
                $place = $db->fetch("SELECT CONCAT(shortname, ' ',offname) as title  FROM ".$sys_tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=?",
                                    4,
                                    $_geo['id_region'],
                                    $_geo['id_area'],
                                    $_geo['id_city'],
                                    $_geo['id_place']
                                    
                );
            }
            
            $addr = !empty($district) ? $district['title'].', ' : '';
            $addr .= !empty($area) ? $area['title'].', ' : '';
            $addr .= !empty($city) ? $city['title'].', ' : '';
            $addr .= !empty($place) ? $place['title'].', ' : '';

            if(!empty($_geo['id_street'])){
                $street = $db->fetch("SELECT CONCAT(offname, ' ',shortname) as title  FROM ".$sys_tables['geodata']."  
                                    WHERE a_level=? AND id_region=? AND id_area=? AND id_city=? AND id_place=? AND id_street=?",
                                    5,
                                    $_geo['id_region'],
                                    $_geo['id_area'],
                                    $_geo['id_city'],
                                    $_geo['id_place'],
                                    $_geo['id_street']
                                    
                );
                $addr .= !empty($street) ? $street['title'] : '';
                $addr .= !empty($_geo['house']) ? ', д.'.$_geo['house']: ''; 
                $addr .= !empty($_geo['corp']) ? ', к.'.$_geo['corp']: '';
            }
            
            return $addr;
    }
    
    /**
    * подборка адресов из базы по txt_addr
    * 
    * @param mixed $txt_addr
    */
    public static function getAddrList($txt_addr,$geo_data = false){
        global $db;
        $sys_tables = Config::$sys_tables;
        $result = [];
        //не убираем все, что в скобках (20.08.2015)
        //$txt_addr = preg_replace('/\(.*\)/sui','',$txt_addr);
        //убираем все кроме букв, точек, запятых, пробелов, скобок и все в нижний регистр
        $txt_addr = preg_replace('/[^А-я-,0-9\(\)\.\s]/sui','',mb_strtolower($txt_addr,"UTF-8"));
        //заменяем обозначения улицы на корректные
        $txt_addr=str_replace(array('пр.','просп.','проспект','пр-кт','бульвар','аллея','ал.','улица','линия В.О.','линия В.О','шоссе',' ш.','пер.','переулок','дорога'),array(' пр ',' пр ',' пр ',' пр ',' бул ',' алл ',' алл ',' ул ',' В.О. линия ','В.О. линия',' шос ',' шос ',' пер ',' пер ',' дор '),$txt_addr);
        
        //заменяем обозначения локаций на корректные
        $txt_addr=str_replace(array('область','район','р-он','поселок','микрорайон','мик-н','мкр-н'),array('обл','р-н','р-н','пос','мкр','мкр','мкр'),$txt_addr);
        //заменяем "дер." на " деревня "
        $txt_addr = preg_replace('/дер\.?\s?[^евня]/sui',' деревня ',$txt_addr);
        //заменяем "пос." на " п "
        $txt_addr = trim(preg_replace('/пос(\s|(\.[^елок]))/sui',' п ',$txt_addr));
        //заменяем "п." на " п "
        $txt_addr = trim(preg_replace('/^\s?п\./sui',' п ',$txt_addr));
        //заменяем "село" на "пос"
        $txt_addr = " ".$txt_addr." ";
        $txt_addr = trim(preg_replace('/(?<=([^А-я]{1}))село(?=([^А-я]{1}))/sui',' пос ',$txt_addr));
        //заменяем "пл." на "площадь"
        $txt_addr = trim(preg_replace('/пл\.?(?=([^А-я]))/sui',' площадь ',$txt_addr));
        //заменяем -ого, -ая -я, ...
        $txt_addr = preg_replace('/(?(?<=[0-9])-[а-я]+)/sui','',$txt_addr);
        //заменяем &nbsp на пробелы
        $txt_addr = preg_replace('/&nbsp;/sui',' ',$txt_addr);
        //несколько пробелов подряд заменяем на один пробел
        $txt_addr = preg_replace('/[\s]+/',' ',$txt_addr);
        //читаем типы локаций и их a_level
        $exploders_with_levels = $db->fetchall("SELECT shortname,shortname_cut,MIN(a_level) as level FROM ".$sys_tables['geodata']." GROUP BY shortname");
        //составляем списки типов локаций и сокращенных типов локаций
        foreach($exploders_with_levels as $object){
            $exploders[] = $object['shortname'];
            $exploders_cut[] = $object['shortname_cut'];
        }
        //разбиваем текст по запятым
        $comma_blocks = explode(',',str_replace('.','',$txt_addr));
        foreach ($comma_blocks as $cb_key=>$comma_block){
            $txt_addr = trim($comma_block);
            
            //разбиваем блок по пробелам
            //$txt_blocks = explode(' ',$txt_addr);
            //теперь разбиваем так, чтобы проглотить &nbsp;
            $txt_blocks = preg_split('/\s/sui',$txt_addr);
            $txt_blocks = array_filter($txt_blocks);
            
            if(!empty($result[5]) || (!empty($a_level) && $a_level == 5)) return $result;
            
            foreach($txt_blocks as $key=>$value){
                //ищем в разделителях из базы не учитывая точки, пробелы и регистр
                $value = mb_strtolower(trim(preg_replace('/\./sui','', $value)),"UTF-8");
                if (in_array(trim(preg_replace('/\./sui','',$value)),$exploders)||in_array(trim(preg_replace('/\./sui','',$value)),$exploders_cut)){
                    //если предыдущий элемент не пуст, значит конструкция вида "... Мурино село"
                    if(!empty($txt_blocks[$key-1])){
                        $k = $key-1;
                        //убираем из строки разделитель, который мы нашли
                        unset($txt_blocks[$key]);
                        $addr_block = "";
                        //берем все, что было до("... Мурино"), соединяем с разделяющим блоком("село")
                        while (!empty($txt_blocks[$k])){
                            $addr_block = $txt_blocks[$k].' '.$addr_block;
                            //убираем подобранные блоки из строки
                            unset($txt_blocks[$k]);
                            --$k;
                        }
                        //получаем ключ разделителя, по которому найдем a_level этого типа объекта
                        $exploder_key = array_search($value,$exploders);
                        if(empty($exploder_key)) $exploder_key = array_search($value,$exploders_cut);
                        
                        //читаем a_level
                        $a_level = $exploders_with_levels[$exploder_key]['level'];
                        
                        $addr_block = trim($addr_block);
                        
                        // в условие уже найденные геоданные(кроме улицы, так как тогда уже нечего искать)
                        $location_where = [];
                        $order_by_params = [];
                        $location_where[] = "id_region = ".(empty($geo_data['id_region'])?0:$geo_data['id_region']);
                        $order_by_params[] = "id_region = ".(empty($geo_data['id_region'])?0:$geo_data['id_region'])." DESC";
                        $city_place_params = [];
                        
                        if($a_level > 3){
                            //если район один из 5, то он обязательно есть
                            if(!empty($geo_data['id_district']) && in_array($geo_data['id_district'],array(27,29,38,43,53))) $location_where[] = "id_district = ".$geo_data['id_district'];
                            else $location_where[] = " (id_district = ".(empty($geo_data['id_district'])?0:$geo_data['id_district'])." OR id_district = 0)";
                            $order_by_params[] = " id_district = ".(empty($geo_data['id_district'])?0:$geo_data['id_district'])." DESC";
                        } 
                        if($a_level >= 3){
                            $location_where[] = "(id_area = ".(empty($geo_data['id_area'])?0:$geo_data['id_area'])." OR id_area = 0)";
                            $order_by_params[] = "id_area = ".(empty($geo_data['id_area'])?0:$geo_data['id_area'])." DESC";
                        } 
                        if($a_level > 3){
                            $location_where[] = "(id_city = ".(empty($geo_data['id_city'])?0:$geo_data['id_city'])." OR id_city = 0)";
                            $order_by_params[] = "id_city = ".(empty($geo_data['id_city'])?0:$geo_data['id_city'])." DESC";
                            if(!empty($geo_data['id_city'])) $city_place_params[] = "id_city = ".$geo_data['id_city'];
                        } 
                        if($a_level > 3){
                            $location_where[] = "(id_place = ".(empty($geo_data['id_place'])?0:$geo_data['id_place'])." OR id_place = 0)";
                            $order_by_params[] = "id_place = ".(empty($geo_data['id_place'])?0:$geo_data['id_place'])." DESC";
                            if(!empty($geo_data['id_place'])) $city_place_params[] = "id_place = ".$geo_data['id_place'];
                        } 
                        
                        $city_place_params = (empty($city_place_params)?"":",(".implode(',',$city_place_params).") AS place_match");
                        
                        //сортируем по совпадениям с параметрами поиска - чтобы выбиралось самое подходящее
                        if(!empty($location_where)){
                            $location_where = implode(" AND ",$location_where);
                            $order_by_params = implode(", ",$order_by_params)."";
                            if(!empty($city_place_params)) $order_by_params = "place_match DESC, ".$order_by_params;
                        } 
                        else $location_where = "";
                        $_geo = [];
                        if(preg_match('/линия/sui',$value))
                            $addr_block = mb_ereg_replace("(?<=[0-9])\s","-я ",$addr_block);
                        //город - единственное, у чего два варианта a_level
                        if($value == 'г'||$value == 'город'){
                            //ищем(shortname не учитывается) в базе соответствие найденному куску с таким a_level
                            $_geo = $db->fetch("SELECT * FROM ".$sys_tables['geodata']."
                                WHERE `offname` LIKE '%".$addr_block."%' AND 
                                       (a_level = 1 OR a_level = 3) "
                                       .(!empty($location_where)?" AND ".$location_where:"")."
                                       ".(!empty($order_by_params)?" ORDER BY ".$order_by_params:"")." LIMIT 1");
                        }
                        else{
                            $addr_block = str_replace(' ','%',$addr_block);
                            //ищем (shortname учитывается, то что не указано считаем равным 0)
                            $_geo = $db->fetchall("SELECT *".$city_place_params." FROM ".$sys_tables['geodata']."
                                    WHERE `offname` LIKE '%".$addr_block."%' AND
                                           a_level = '".$a_level."' AND
                                           `shortname` = '".$exploders_with_levels[$exploder_key]['shortname']."'
                                           ".(!empty($location_where)?" AND ".$location_where:"")."
                                           ".(!empty($order_by_params)?" ORDER BY ".$order_by_params:"").($a_level < 5?" LIMIT 1":""));
                            //если район области найден, и он не совпадает с тем что нашли, чистим
                            if(!empty($geo_data['id_area']) && !empty($_geo['id_area']) && $_geo['id_area']!=$geo_data['id_area'] && $a_level == 5) $_geo = [];
                            //ищем без учета района города, что не указано считаем равным 0
                             if(empty($_geo)){
                                
                                $location_where = [];
                                $location_where[] = "id_region = ".(empty($geo_data['id_region'])?0:$geo_data['id_region']);
                                //если район один из 5, то он обязательно есть
                                if(!empty($geo_data['id_district']) && in_array($geo_data['id_district'],array(27,29,38,43,53))) $location_where[] = "id_district = ".$geo_data['id_district'];
                                if($a_level >= 3) $location_where[] = "id_area = ".(empty($geo_data['id_area'])?0:$geo_data['id_area']);
                                if($a_level > 3) $location_where[] = "id_city = ".(empty($geo_data['id_city'])?0:$geo_data['id_city']);
                                if($a_level > 3) $location_where[] = "id_place = ".(empty($geo_data['id_place'])?0:$geo_data['id_place']);
                                if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                                else $location_where = "";
                                $_geo = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                    WHERE `offname` LIKE '%".$addr_block."%' AND ".$location_where."
                                           a_level = ? AND
                                           `shortname` = ?".($a_level < 5?" LIMIT 1":""),false,
                                           $a_level,
                                           $exploders_with_levels[$exploder_key]['shortname']
                                     );
                                if(empty($_geo)){
                                    $location_where = [];
                                    if(isset($geo_data['id_region'])) $location_where[] = "id_region = ".$geo_data['id_region'];
                                    
                                    if(!empty($geo_data['id_district']) && in_array($geo_data['id_district'],array(27,29,38,43,53))) $location_where[] = "id_district = ".$geo_data['id_district'];
                                    elseif($a_level < 5 && isset($geo_data['id_district'])) $location_where[] = "id_district = ".$geo_data['id_district'];
                                    
                                    if(isset($geo_data['id_area']) && $a_level > 3) $location_where[] = "id_area = ".$geo_data['id_area'];
                                    if(isset($geo_data['id_city']) && $a_level > 3) $location_where[] = "id_city = ".$geo_data['id_city'];
                                    if(isset($geo_data['id_place']) && $a_level > 3) $location_where[] = "id_place = ".$geo_data['id_place'];
                                    if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                                    else $location_where = "";
                                    
                                    //ищем(shortname учитывается) в базе соответствие найденному куску с таким a_level
                                    $_geo = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                            WHERE `offname` LIKE '%".$addr_block."%' AND ".$location_where."
                                                   a_level = ? AND
                                                   `shortname` = ?".($a_level < 5?" LIMIT 1":""),false,
                                                   $a_level,
                                                   $exploders_with_levels[$exploder_key]['shortname']
                                             );
                                }
                            }
                        }
                        
                        //если что-то нашли
                        if(!empty($_geo)){
                            if($a_level < 5){
                                if(empty($geo_data)) $geo_data = [];
                                if(empty($geo_data['id_place'])) $geo_data['id_place'] = (!empty($_geo[0]['id_place']))?$_geo[0]['id_place']:0;
                                if(empty($geo_data['id_city'])) $geo_data['id_city'] = (!empty($_geo[0]['id_city']))?$_geo[0]['id_city']:0;
                                if(empty($geo_data['id_area'])) $geo_data['id_area'] = (!empty($_geo[0]['id_area']))?$_geo[0]['id_area']:0;
                                if(empty($geo_data['id_district'])) $geo_data['id_district'] = (!empty($_geo[0]['id_district']))?$_geo[0]['id_district']:0;
                                if(empty($geo_data['id_region'])) $geo_data['id_region'] = (!empty($_geo[0]['id_region']))?$_geo[0]['id_region']:0;
                            }
                            else{
                                foreach($_geo as $gk=>$i) $_geo[$gk]['txt_addr'] = Geo::getAddress($_geo[$gk]);
                                $result[$a_level] = $_geo;
                            }
                        }
                        elseif($a_level < 5) return false;
                    }
                    else{
                        //если предыдущий элемент пуст, значит конструкция вида "село Мурино ..."
                        $k = $key+1;
                        //убираем из строки разделитель, который мы нашли
                        unset($txt_blocks[$key]);
                        $addr_block = "";
                        //здесь будем хранить адрес, который распознался до конца блока
                        $saved_geo = [];
                        //подбираем блоки после разделителя, после каждого проверяя по базе, не нашли ли что-нибудь
                        while((!empty($txt_blocks[$k])) && !in_array($txt_blocks[$k],$exploders)){
                            //подбираем блок, проверяем в базе, если не нашли, идем дальше пока не наткнемся на следующий разделитель
                            $addr_block = trim($addr_block.' '.$txt_blocks[$k]);
                            //убираем подобранный блок из строки
                            unset($txt_blocks[$k]);
                            //получаем ключ разделителя, по которому найдем a_level этого типа объекта
                            $exploder_key = array_search($value,$exploders);
                            if(empty($exploder_key)) $exploder_key = array_search($value,$exploders_cut);
                            //читаем a_level
                            $a_level = $exploders_with_levels[$exploder_key]['level'];
                            
                            //правка для линий В.о.
                            if($exploder_key == 23 && ($geo_data['id_district'] == 3 && $a_level == 5)){
                                if(!preg_match('/в\.о\./sui',$addr_block)) $addr_block = trim($addr_block)." В.О.";
                            }
                            
                            // в условие уже найденные геоданные(кроме улицы, так как тогда уже нечего искать)
                            $location_where = [];
                            $location_where[] = "id_region = ".(empty($geo_data['id_region'])?0:$geo_data['id_region']);
                            if($a_level > 3) $location_where[] = "id_area = ".(empty($geo_data['id_area'])?0:$geo_data['id_area']);
                            if($a_level < 5) $location_where[] = "id_district = ".(empty($geo_data['id_district'])?0:$geo_data['id_district']);
                            if($a_level > 3) $location_where[] = "id_city = ".(empty($geo_data['id_city'])?0:$geo_data['id_city']);
                            if($a_level > 3) $location_where[] = "id_place = ".(empty($geo_data['id_place'])?0:$geo_data['id_place']);
                            if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                            else $location_where = "";
                            $_geo = [];
                            //город - единственное, у чего два варианта a_level
                            if($value == 'г'||$value == 'город'){
                                //ищем(shortname не учитывается) в базе соответствие найденному куску с таким a_level
                                $_geo = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                    WHERE `offname` = ? AND ".$location_where."
                                           (a_level = 1 OR a_level = 3) ",false,
                                           $addr_block
                                     );
                            }
                            else{
                                
                                //ищем (shortname учитывается, то что не указано считаем равным 0)
                                $_geo = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                        WHERE `offname` = ? AND ".$location_where."
                                               a_level = ? AND
                                               `shortname` = ?",false,
                                               $addr_block,
                                               $a_level,
                                               $exploders_with_levels[$exploder_key]['shortname']
                                         );
                                if(empty($_geo)){
                                    
                                    $location_where = [];
                                    if(!empty($geo_data['id_region'])) $location_where[] = "id_region = ".$geo_data['id_region'];
                                    if(!empty($geo_data['id_area'])) $location_where[] = "id_area = ".$geo_data['id_area'];
                                    if($a_level < 5 && !empty($geo_data['id_district'])) $location_where[] = "id_district = ".$geo_data['id_district'];
                                    if(!empty($geo_data['id_city'])) $location_where[] = "id_city = ".$geo_data['id_city'];
                                    if(!empty($geo_data['id_place'])) $location_where[] = "id_place = ".$geo_data['id_place'];
                                    if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                                    else $location_where = "";
                                    
                                    //ищем(shortname учитывается) в базе соответствие найденному куску с таким a_level
                                    $_geo = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                            WHERE `offname` = ? AND ".$location_where."
                                                   a_level = ? AND
                                                   `shortname` = ?",false,
                                                   $addr_block,
                                                   $a_level,
                                                   $exploders_with_levels[$exploder_key]['shortname']
                                             );
                                    //если и так не получилось, ищем без учета shortname, заменяем точки на %, отмечаем что есть проблемы
                                    if(empty($_geo)){
                                        $geo_data['addr_problems'] = 1;
                                        $addr_block = preg_replace('/\./sui','%',$addr_block);
                                        $_geo = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                            WHERE `offname` LIKE ? AND ".$location_where."
                                                   a_level = ?",false,
                                                   $addr_block,
                                                   $a_level
                                             );
                                        //ксли все еще ничего, пробуем искать без учета id_area,id_city и id_place
                                        if(empty($_geo)){
                                            $location_where = "";
                                            if(isset($geo_data['id_region'])) $location_where = "id_region = ".$geo_data['id_region']." AND ";
                                            if(!empty($geo_data['id_area'])) $location_where .= "id_area = ".$geo_data['id_area']." AND ";
                                            $_geo = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                                WHERE `offname` LIKE '%".$addr_block."%' AND ".$location_where."
                                                       a_level = ? AND
                                                       shortname = ?",false,
                                                       $a_level,$exploders_with_levels[$exploder_key]['shortname']
                                                 );
                                        }
                                    }
                                }
                            }
                            /*
                            //если что-то нашли,  новые данные и дальше этот блок не заполняем
                            if(!empty($_geo)){
                                //если дальше еще что-то есть, лезем дальше. если нет - выходим
                                if( (!empty($txt_blocks[$k+1])) && !in_array($txt_blocks[$k+1],$exploders) ) $saved_geo = $_geo;
                            }
                            */
                            if(!empty($_geo)){
                                if($a_level < 5){
                                    if(empty($geo_data)) $geo_data = [];
                                    if(empty($geo_data['id_place'])) $geo_data['id_place'] = (!empty($_geo[0]['id_place']))?$_geo[0]['id_place']:0;
                                    if(empty($geo_data['id_city'])) $geo_data['id_city'] = (!empty($_geo[0]['id_city']))?$_geo[0]['id_city']:0;
                                    if(empty($geo_data['id_area'])) $geo_data['id_area'] = (!empty($_geo[0]['id_area']))?$_geo[0]['id_area']:0;
                                    if(empty($geo_data['id_district'])) $geo_data['id_district'] = (!empty($_geo[0]['id_district']))?$_geo[0]['id_district']:0;
                                    if(empty($geo_data['id_region'])) $geo_data['id_region'] = (!empty($_geo[0]['id_region']))?$_geo[0]['id_region']:0;
                                }
                                else{
                                    if( (!empty($txt_blocks[$k+1])) && !in_array($txt_blocks[$k+1],$exploders) ) $saved_geo = $_geo;
                                    foreach($_geo as $gk=>$i) $_geo[$gk]['txt_addr'] = Geo::getAddress($_geo[$gk]);
                                    $result[$a_level] = $_geo;
                                }
                            }
                            //если не нашли то что выше улицы, выходим
                            elseif($a_level < 5) return false;
                            ++$k;
                        }
                        //если что-то находили в процессе, дописываем (например пр. Авиаторов бла-бла-бла, отсюда вычленили проспект Авиаторов. а если бы был пр. Авиаторов Балтики, то распознали бы все)
                        if($a_level == 5){
                            if(empty($_geo) && !empty($saved_geo)) $_geo = $saved_geo;
                            if(!empty($_geo)){
                                foreach($_geo as $gk=>$i) $_geo[$gk]['txt_addr'] = Geo::getAddress($_geo[$gk]);
                                $result[$a_level] = $_geo;
                            }
                        }
                        
                    }
                }elseif(count($txt_blocks) == 1){
                    //если в текстовом адресе например "Пискаревский" или "Комендантский" и все, пробуем подобрать только по offname
                    //ищем  в базе соответствие найденному куску с a_level = 5, отмечаем что есть проблемы
                    $geo_data['addr_problems'] = 1;
                    $location_where = [];$a_level = 5;$_geo = [];
                    if(!empty($geo_data['id_region'])) $location_where[] = "id_region = ".$geo_data['id_region'];
                    if(!empty($geo_data['id_area'])) $location_where[] = "id_area = ".$geo_data['id_area'];
                    if(!empty($geo_data['id_district'])) $location_where[] = "id_district = ".$geo_data['id_district'];
                    if(!empty($geo_data['id_place'])) $location_where[] = "id_place = ".$geo_data['id_place'];
                    elseif(empty($geo_data['id_district'])) $a_level = 4;
                    if(!empty($geo_data['id_city'])) $location_where[] = "id_city = ".$geo_data['id_city'];
                    else $a_level = 3;
                    if(!empty($location_where)) $location_where = implode(" AND ",$location_where)." AND";
                    else $location_where = "";
                    //перебираем увеличивая a_level пока что-нибудь не найдем
                    while($a_level<=5 && empty($_geo)){
                        if(!empty($txt_blocks[0]))
                            $_geo = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']."
                                                WHERE `offname` LIKE '%".$txt_blocks[0]."%' AND ".$location_where."
                                                       a_level = ?
                                                       ORDER BY id_city ASC,id_place ASC",false,$a_level);
                        if(empty($_geo)) ++$a_level;
                    }
                    //если что-то нашли,  новые данные
                    if(!empty($_geo)){
                        if($a_level < 5){
                            if(empty($geo_data)) $geo_data = [];
                            if(empty($geo_data['id_place'])) $geo_data['id_place'] = (!empty($_geo[0]['id_place']))?$_geo[0]['id_place']:0;
                            if(empty($geo_data['id_city'])) $geo_data['id_city'] = (!empty($_geo[0]['id_city']))?$_geo[0]['id_city']:0;
                            if(empty($geo_data['id_area'])) $geo_data['id_area'] = (!empty($_geo[0]['id_area']))?$_geo[0]['id_area']:0;
                            if(empty($geo_data['id_district'])) $geo_data['id_district'] = (!empty($_geo[0]['id_district']))?$_geo[0]['id_district']:0;
                            if(empty($geo_data['id_region'])) $geo_data['id_region'] = (!empty($_geo[0]['id_region']))?$_geo[0]['id_region']:0;
                        }else{
                            if( (!empty($txt_blocks[$k+1])) && !in_array($txt_blocks[$k+1],$exploders) ) $saved_geo = $_geo;
                            foreach($_geo as $gk=>$i) $_geo[$gk]['txt_addr'] = Geo::getAddress($_geo[$gk]);
                            $result[$a_level] = $_geo;
                        }
                    }
                    else return $_geo;
                }
            }
        }//foreach end
        return $result;
    }
}
?>