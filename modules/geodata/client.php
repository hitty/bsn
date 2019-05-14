<?php
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');


// определяем запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

// обработка action-ов
switch($action){
     /*************************************\
    |*  Список районов СПб и ЛО           *|
    \*************************************/	
	case 'districts':
        if($ajax_mode) {
            $parent_id = 34142;
            $id_region = 47;
            if(!empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1])) $parent_id = Convert::ToInteger($this_page->page_parameters[1]);
            $selected_items = Request::GetArray('selected', METHOD_POST);
            $sql = "SELECT id, `title`
                    FROM ".$sys_tables['districts']."
                    WHERE parent_id = ?
                    ORDER BY `title`";
            $list = $db->fetchall($sql, false, $parent_id);
            $ajax_result['ok'] = !empty($list);
            if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['title'] = 'Административные районы Санкт-Петербурга';
        } else $this_page->http_code=404; 
        break;
     /*************************************\
    |*  Список метро                      *|
    \*************************************/    
    case 'subways':
        if($ajax_mode) {
            $parent_id = 34142;
            if(!empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1])) $parent_id = Convert::ToInteger($this_page->page_parameters[1]);
            $selected_items = Request::GetArray('selected', METHOD_POST);
            $sql = "SELECT id, `title`
                    FROM ".$sys_tables['subways']."
                    WHERE parent_id = ?
                    ORDER BY `title`";
            $list = $db->fetchall($sql, false, $parent_id);
            $ajax_result['ok'] = !empty($list);
            if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['title'] = 'Станции Санкт-Петербургского метрополитена';
		} else $this_page->http_code=404; 
        break;
     /*************************************\
    |*  Список районов ЛО                 *|
    \*************************************/    
    case 'district_areas':
        if($ajax_mode) {
            $id_region = 47;
            if(!empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1])) $id_region = Convert::ToInteger($this_page->page_parameters[1]);
            $selected_items = Request::GetArray('selected', METHOD_POST);
            $sql = "SELECT id, offname as `title`
                    FROM ".$sys_tables['geodata']."
                    WHERE a_level = 2 AND id_region = ?
                    ORDER BY offname";
            $list = $db->fetchall($sql, false, $id_region);
            $ajax_result['ok'] = !empty($list);
            if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['title'] = 'Районы Ленинградской области';
        } else $this_page->http_code=404; 
        break;
     /*************************************\
    |*  Список стран (зарубежка)          *|
    \*************************************/    
    case 'countries':
        if($ajax_mode) {
            $selected_items = Request::GetArray('selected', METHOD_POST);
            $sql = "SELECT id, `title`
                    FROM ".$sys_tables['foreign_countries']."
                    ORDER BY `title`";
            $list = $db->fetchall($sql, false);
            $ajax_result['ok'] = !empty($list);
            if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['title'] = 'Страны';
        } else $this_page->http_code=404; 
        break;
        case 'registration_list':
        case 'regions_list':
            $search_str = Request::GetString('search_string', METHOD_POST);
            $search_str = Convert::ToRusian($search_str);
            
            $list = $db->fetchall("SELECT 
                                        ".$sys_tables['geodata'].".id, 
                                        ".$sys_tables['geodata'].".id_region, 
                                        CONCAT_WS('. ',shortname_cut, offname) as g_offname, parentguid, id_district, 
                                        ".$sys_tables['districts'].".title as district_title
                                   FROM ".$sys_tables['geodata']."
                                   LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables['geodata'].".id_district
                                   WHERE offname LIKE ? AND a_level IN(1,3,4) AND ".$sys_tables['geodata'].".id!=23329
                                   ORDER BY a_level, offname", false, "%".$search_str."%");
            if(!empty($list)){
                $parentguids = [];
                foreach($list as $key=>$item) $parentguids[] = "'".$item['parentguid']."'";
                $parentguids = implode(',',array_unique($parentguids));
                $parent_info = $db->fetchall("SELECT    IF(id_region=78, '', CONCAT(', ',CONCAT_WS(' ',offname,shortname))) as f_offname, aoguid,id_region
                                              FROM ".$sys_tables['geodata']." WHERE ".$sys_tables['geodata'].".aoguid IN (".$parentguids.")",'aoguid');
                foreach($list as $key=>$item)
                    $list[$key]['region'] = (empty($parent_info[$list[$key]['parentguid']]['f_offname']) ? '' : $parent_info[$list[$key]['parentguid']]['f_offname']);
            }
            $ajax_result['ok'] = true;
            foreach($list as $k=>$item) $list[$k]['region'] = trim($item['region'],', ');
            $ajax_result['list'] = $list;
            break;
        case 'address':
            $search_str = Request::GetString('search_string', METHOD_POST);
            $search_str = Convert::ToRusian($search_str);
            $search_str = addslashes($search_str);
            $where_clauses = explode(" ", trim($search_str));
            if(count($where_clauses)>1){
               switch(count($where_clauses)){
                   case 2:
                    $where = " ((g.offname LIKE '%".$where_clauses[0]."%'  AND g.shortname LIKE '%".$where_clauses[1]."%') OR (g.offname LIKE '%".$where_clauses[1]."%' AND g.shortname LIKE '%".$where_clauses[0]."%') OR (g.offname LIKE '%".$where_clauses[0]." ".$where_clauses[1]."%') OR (g.offname LIKE '%".$where_clauses[1]." ".$where_clauses[0]."%'))";
                    break;
                   default:
                    $where = " ((g.offname LIKE '%".implode(" ",array_slice($where_clauses, 0, 2))."%'  AND g.shortname LIKE '%".$where_clauses[count($where_clauses)-1]."%') OR (g.offname LIKE '%".$where_clauses[count($where_clauses)-1]."%' AND g.shortname LIKE '%".implode(" ",array_slice($where_clauses, 0, 2))."%'))";
                    break;
               }    
                
            }  else $where = "(offname LIKE '%".$search_str."%'  OR shortname LIKE '%".$search_str."%')";
            if(!empty($this_page->page_parameters[1])) $where.= " AND ( a_level < 5 AND shortname IN ('город', 'городок', 'деревня', 'тер', 'поселок', 'шоссе') )";
            $list = $db->fetchall("
                            SELECT g.id, g.a_level,
                                  CONCAT_WS('. ',g.shortname_cut, g.offname) as title,
                                  g.parentguid
                            FROM ".$sys_tables['geodata']." g
                            LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = g.id_district
                            WHERE ".$where."
                            ORDER BY g.parentguid='c2deb16a-0330-4f05-821f-1d09c93331e6' DESC, g.id_area=1 DESC, g.id_city=1 DESC, g.id_place=0 DESC, g.a_level, g.offname 
                            LIMIT ?", false, 40);
            if(!empty($list)){
                $parentguids = [];
                foreach($list as $key=>$item) $parentguids[] = "'".$item['parentguid']."'";
                $parentguids = implode(',',array_unique($parentguids));
                $parent_info = $db->fetchall("SELECT 
                                                IFNULL( 
                                                    CONCAT(' ', 
                                                        CONCAT_WS(
                                                                '. ', ".$sys_tables['geodata'].".shortname_cut, ".$sys_tables['geodata'].".offname
                                                        )
                                                    ), ''
                                                ) as f_offname,aoguid
                                              FROM ".$sys_tables['geodata']." WHERE ".$sys_tables['geodata'].".aoguid IN (".$parentguids.")",'aoguid');
                foreach($list as $key=>$item){
                    if(!empty($parent_info[$list[$key]['parentguid']]['f_offname'])){
                        $list[$key]['additional_title'] = ( !empty( $list[$key]['additional_title'] ) ? " /" . $list[$key]['additional_title'] : "" ) . $parent_info[$list[$key]['parentguid']]['f_offname'];
                    }
                }
            }
            
            $ajax_result['ok'] = true;
            foreach($list as $k=>$item) $list[$k]['title'] = preg_replace('|'.$search_str.'|umsi', '<b>\\0</b>', $item['title']);
            $ajax_result['list'] = $list;
            $ajax_result['lq'] = $db->last_query;
            break;  
        case 'estate_estimate_address':
            $search_str = Request::GetString('search_string', METHOD_POST);
            $search_str = Convert::ToRusian($search_str);
            $search_str = addslashes($search_str);
            $where_clauses = explode(" ", trim($search_str));
            if(count($where_clauses)>1){
               switch(count($where_clauses)){
                   case 2:
                    $where = " (g.offname LIKE '%".$where_clauses[0]."%'  AND g.shortname LIKE '%".$where_clauses[1]."%') OR (g.offname LIKE '%".$where_clauses[1]."%' AND g.shortname LIKE '%".$where_clauses[0]."%') OR (g.offname LIKE '%".$where_clauses[0]." ".$where_clauses[1]."%') OR (g.offname LIKE '%".$where_clauses[1]." ".$where_clauses[0]."%')";
                    break;
                   default:
                    $where = " (g.offname LIKE '%".implode(" ",array_slice($where_clauses, 0, 2))."%'  AND g.shortname LIKE '%".$where_clauses[count($where_clauses)-1]."%') OR (g.offname LIKE '%".$where_clauses[count($where_clauses)-1]."%' AND g.shortname LIKE '%".implode(" ",array_slice($where_clauses, 0, 2))."%')";
                    break;
               }    
                
            }  else $where = "offname LIKE '%".$search_str."%'  OR shortname LIKE '%".$search_str."%'";
            $list = $db->fetchall("
                            SELECT g.id, g.a_level,
                                   ".$sys_tables['districts'].".title AS district_title, 
                                   CONCAT_WS(' ',g.shortname, g.offname) AS title,
                                   g.parentguid
                            FROM ".$sys_tables['geodata']." g
                            LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = g.id_district
                            WHERE ".$where."
                            ORDER BY g.parentguid='c2deb16a-0330-4f05-821f-1d09c93331e6' DESC, g.id_area=1 DESC, g.id_city=1 DESC, g.id_place = 0 DESC, g.a_level, g.offname 
                            LIMIT ?", false, 40);
            //запрос разбит на три
            $parentguids = [];
            foreach($list as $key=>$item) $parentguids[] = "'".$item['parentguid']."'";
            $parentguids = implode(',',array_unique($parentguids));
            $list_f = $db->fetchall("SELECT IFNULL(CONCAT(' ',CONCAT_WS('. ',shortname_cut,offname)),'') as offname, aoguid, parentguid
                                     FROM ".$sys_tables['geodata']." WHERE aoguid IN(".$parentguids.")","aoguid");
            $f_parentguids = [];
            foreach($list_f as $key=>$item) $f_parentguids[] = "'".$item['parentguid']."'";
            $f_parentguids = implode(',',array_unique($f_parentguids));
            $list_h = $db->fetchall("SELECT IFNULL(CONCAT(' ',CONCAT_WS(' ',offname,shortname)),'') as offname,  aoguid, parentguid
                                     FROM ".$sys_tables['geodata']." WHERE aoguid IN(".$f_parentguids.")");
            unset($f_parentguids);
            foreach($list as $key=>$item){
                $parentguid = $list[$key]['parentguid'];
                if(!empty($list_f[$parentguid]['offname'])){
                    $f_offname = (!empty($list_f[$parentguid])?$list_f[$parentguid]['offname']:"");
                    $h_offname = (!empty($list_h[$parentguid])?$list_h[$parentguid]['offname']:"");
                    $list[$key]['title'] = (!empty($list[$key]['district_title'])? $list[$key]['district_title']." район" : (!empty($h_offname) ? $h_offname.", " : "")).
                                           (!empty($f_offname) ? $f_offname.", " : "").$list[$key]['title'];
                }
            }
            
            $ajax_result['ok'] = true;
            $ajax_result['list'] = $list;
            $ajax_result['lq'] = $db->last_query;
            break;                                
         case 'streets_list':
            // список улиц для автокомплита
            $geo_id = Request::GetInteger('geo_id', METHOD_POST);
            if($geo_id==0) $ajax_result['ok'] = false;
            else {
                $info = $db->fetch("SELECT `aoguid`, id_district
                                    FROM ".$sys_tables['geodata']."
                                    WHERE ".$sys_tables['geodata'].".id=?
                                    ", $geo_id
                );
                $search_str = Request::GetString('search_string', METHOD_POST);
                $search_str = Convert::ToRusian($search_str);
                $list = $db->fetchall("SELECT *, ".$sys_tables['districts'].".title as district_title 
                                       FROM ".$sys_tables['geodata']." 
                                       LEFT JOIN ".$sys_tables['districts']." ON ".$sys_tables['districts'].".id = ".$sys_tables['geodata'].".id_district 
                                       WHERE parentguid=? AND a_level=5 AND offname LIKE ? 
                                       ORDER BY offname 
                                       ", false, $info['aoguid'], "%".$search_str."%"
                );
                $ajax_result['ok'] = true;
                $ajax_result['list'] = $list;
            }
            break;        
         case 'subways_list':
         case 'districts_list':
            
            $search_str = Request::GetString('search_string', METHOD_POST);
            $search_str = Convert::ToRusian($search_str);
            $list = $db->fetchall("SELECT *
                                   FROM ".$sys_tables[($action=='subways_list'?'subways':'districts')]." 
                                   WHERE title LIKE ? 
                                   ORDER BY title 
                                   ", false, "%".$search_str."%"
            );
            $ajax_result['ok'] = true;
            $ajax_result['list'] = $list;
            break; 
        case 'form':
            $action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
            switch($action){
                 /*************************************\
                |*  Список районов СПб                *|
                \*************************************/    
                case 'districts':
                    if($ajax_mode) {
                        $parent_id = 34142;
                        $id_region = 47;
                        if(!empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1])) $parent_id = Convert::ToInteger($this_page->page_parameters[1]);
                        $selected_items = Request::GetArray('selected', METHOD_POST);
                        $list = $db->fetchall("SELECT * FROM ".$sys_tables['districts']." WHERE parent_id = ?", false, $parent_id);
                        if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
                        Response::SetArray('list',$list);
                        $module_template = 'block.districts.html';
                    } else $this_page->http_code=404; 
                    break;
                 /*************************************\
                |*  Список метро                      *|
                \*************************************/    
                case 'subways':
                    if($ajax_mode) {
                        $parent_id = 34142;
                        if(!empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1])) $parent_id = Convert::ToInteger($this_page->page_parameters[1]);
                        //список линий
                        $list = $db->fetchall("SELECT * FROM ".$sys_tables['subway_lines']." WHERE  ".$sys_tables['subway_lines'].".parent_id = ?", false, $parent_id);
                        Response::SetArray('lines_list',$list);
                        
                        //список станций
                        $selected_items = Request::GetArray('selected', METHOD_POST);
                        $sql = "SELECT ".$sys_tables['subways'].".id,
                                       ".$sys_tables['subways'].".id_subway_line,
                                       ".$sys_tables['subways'].".map_title as title,
                                       ".$sys_tables['subways'].".point_x_coords,
                                       ".$sys_tables['subways'].".point_y_coords,
                                       ".$sys_tables['subways'].".text_left_offset,
                                       ".$sys_tables['subways'].".text_top_offset,
                                       ".$sys_tables['subway_lines'].".line_color
                                FROM ".$sys_tables['subways']."
                                LEFT JOIN  ".$sys_tables['subway_lines']." ON ".$sys_tables['subway_lines'].".id = ".$sys_tables['subways'].".id_subway_line
                                WHERE  ".$sys_tables['subways'].".parent_id = ?";
                        $list = $db->fetchall($sql, false, $parent_id);
                        if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
                        Response::SetArray('stations_list',$list);
                        $module_template = 'block.subways.html';
                    } else $this_page->http_code=404; 
                    break;
                 /*************************************\
                |*  Список районов ЛО                 *|
                \*************************************/    
                case 'district_areas':
                    if($ajax_mode) {
                        $id_region = 47;
                        if(!empty($this_page->page_parameters[1]) && Validate::isDigit($this_page->page_parameters[1])) $id_region = Convert::ToInteger($this_page->page_parameters[1]);
                        $selected_items = Request::GetArray('selected', METHOD_POST);
                        $list = $db->fetchall("SELECT * FROM ".$sys_tables['geodata']." WHERE id_region = ? AND a_level = 2", false, $id_region);
                        if(!empty($selected_items)) foreach($list as $lkey=>$litem) if(in_array($litem['id'], $selected_items)) $list[$lkey]['selected'] = true;
                        Response::SetArray('list',$list);
                        $module_template = 'block.districts_areas.html';
                    } else $this_page->http_code=404; 
                    break;
            }
            break;               
}
?>