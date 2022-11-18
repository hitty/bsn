<?php
$this_page->manageMetadata(array('title'=>'Комплексы'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['id'] = Request::GetInteger('f_id',METHOD_GET);
//фильтр для агентств
$filters['user'] = Request::GetInteger('f_user',METHOD_GET);
if(!empty($filters['user'])) {
   $get_parameters['f_user'] = $filters['user'];
}
//фильтр для менеджеров
$filters['manager'] = Request::GetInteger('f_manager',METHOD_GET);
if(!empty($filters['manager'])) {
   $get_parameters['f_manager'] = $filters['manager'];
}
//фильтр для статуса
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
if(!empty($filters['status'])) {
   $get_parameters['f_status'] = $filters['status'];
}  else $filters['status'] = $get_parameters['f_status'] = 2;
$filters['published'] = Request::GetInteger('f_published',METHOD_GET);
if(!empty($filters['published'])) {
   $get_parameters['f_published'] = $filters['published'];
}  else $filters['published'] = $get_parameters['f_published'] = 1;

// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

switch($action){
    case 'save_complex':
        $id = Request::GetInteger('id',METHOD_POST);
        $id_complex = Request::GetInteger('id_complex',METHOD_POST);
        if(!empty($id_complex) && !empty($id)){
            $res = $db->querys("UPDATE ".$sys_tables['estate_complexes_external']." SET id_complex = ? WHERE id = ?",$id_complex, $id);
            $ajax_result['ok'] = $res;
        }
        break;
    case 'find_complex':
            $type = Request::GetInteger('type',METHOD_POST);
            switch($type){
                case 3: $table = 'cottage'; break;
                case 2: $table = 'business_centers'; break;
                case 1: 
                default:$table = 'housing_estates'; break;
            }
            $search_string = Request::GetString('search_string',METHOD_POST);
            $list = $db->fetchall("SELECT ".$sys_tables[$table].".id, ".$sys_tables[$table].".title FROM
                                    ".$sys_tables[$table]."
                                    WHERE ".$sys_tables[$table].".title LIKE '%".$search_string."%' AND ".$sys_tables[$table].".published = 1
                                    ORDER BY  ".$sys_tables[$table].".title
                                    LIMIT 10
            ");
            $ajax_result['ok'] = true;
            if(!empty($list)) $ajax_result['list'] = $list;
            else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Ничего не найдено'));
                    
        break;      
    default:
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/modules/estate_complex/admin.autocomplette.js';

            
        $managers = $db->fetchall("SELECT id, name as title FROM ".$sys_tables['managers']." WHERE bsn_manager = 1");
        Response::SetArray('managers',$managers);
        //если выбран менеджер - список агентств данного менеджера
        if(!empty($filters['manager'])) {
            $users = $db->fetchall("SELECT 
                                        ".$sys_tables['agencies'].".title,
                                        ".$sys_tables['users'].".id
                                    FROM ".$sys_tables['estate_complexes_external']."
                                    LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['estate_complexes_external'].".id_user AND ".$sys_tables['users'].".agency_admin = 1
                                    LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    WHERE ".$sys_tables['agencies'].".id_manager = ".$filters['manager']."
                                    GROUP BY id
                                    ORDER BY title 
            ");
            echo $db->error;
            if(!empty($users)) Response::SetArray('users',$users);
            else Response::SetString('warn_text','У данного менеджера нет комплексов для прикрепления');
            $where = [];
            //если выбрано агентство - список комплексов
            if(!empty($filters['user'])) {
                switch($filters['status']){
                    case 2: $where[] = " id_complex = 0 "; break;
                    case 1: $where[] = " id_complex >0 "; break;
                }
                if(!empty($filters['published']) && ($filters['status'] == 1))  $where[] = "published = ".$filters['published'];
                $where = !empty($where) ? " AND ".implode(" AND ", $where) : "";
                $list = $db->fetchall("(SELECT 
                                            ".$sys_tables['estate_complexes_external'].".*,
                                            'ЖК' as complex_type,
                                            ".$sys_tables['housing_estates'].".title as complex_title
                                        FROM ".$sys_tables['estate_complexes_external']."
                                        LEFT JOIN ".$sys_tables['housing_estates']." ON ".$sys_tables['housing_estates'].".id = ".$sys_tables['estate_complexes_external'].".id_complex
                                        WHERE ".$sys_tables['estate_complexes_external'].".id_user = ".$filters['user']."
                                        AND ".$sys_tables['estate_complexes_external'].".type = 1
                                        $where
                                        ORDER BY external_title 
                                        )UNION(
                                        SELECT 
                                            ".$sys_tables['estate_complexes_external'].".*,
                                            'БЦ' as complex_type,
                                            ".$sys_tables['business_centers'].".title as complex_title
                                        FROM ".$sys_tables['estate_complexes_external']."
                                        LEFT JOIN ".$sys_tables['business_centers']." ON ".$sys_tables['business_centers'].".id = ".$sys_tables['estate_complexes_external'].".id_complex
                                        WHERE ".$sys_tables['estate_complexes_external'].".id_user = ".$filters['user']."
                                        AND ".$sys_tables['estate_complexes_external'].".type = 2
                                        $where
                                        ORDER BY external_title 
                                        )UNION(
                                        SELECT 
                                            ".$sys_tables['estate_complexes_external'].".*,
                                            'КП' as complex_type,
                                            ".$sys_tables['cottages'].".title as complex_title
                                        FROM ".$sys_tables['estate_complexes_external']."
                                        LEFT JOIN ".$sys_tables['cottages']." ON ".$sys_tables['cottages'].".id = ".$sys_tables['estate_complexes_external'].".id_complex
                                        WHERE ".$sys_tables['estate_complexes_external'].".id_user = ".$filters['user']."
                                        AND ".$sys_tables['estate_complexes_external'].".type = 3
                                        $where
                                        ORDER BY external_title
                                        ) 
                ");
                Response::SetArray('list', $list);
            }        
        }
        $module_template = 'admin.complex.external.html';     
        break;        
}


        

// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk.'='.$gv;
Response::SetString('get_string', implode('&',$get_parameters));
?>