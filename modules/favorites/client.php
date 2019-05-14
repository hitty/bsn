<?php
require_once('includes/class.estate.php');
require_once('includes/class.housing_estates.php');
require_once('includes/class.cottages.php');
require_once('includes/class.business_centers.php');
Response::SetString('img_folder',Config::Get('img_folders/live'));
//не показывать верхний баннер
Response::SetBoolean('not_show_top_banner',true);
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
$sys_tables = Config::$sys_tables;
switch(true){
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // отображение списка с избранным
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case 
        ( empty($action) && count($this_page->page_parameters) == 0 ) || 
        ( !empty( $action ) && count($this_page->page_parameters) == 2 ):
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        $GLOBALS['css_set'][] = '/css/style-cabinet.css';
        $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
        $GLOBALS['js_set'][] = '/modules/favorites/favorites.js';
        $GLOBALS['css_set'][] = '/modules/favorites/style.css';
        $GLOBALS['css_set'][] = '/css/style-cabinet.css';
        //управление данными таблиц
        $GLOBALS['js_set'][] = '/admin/js/admin.js';
        $img_folders = [];
        $object_type = Request::GetString('type',METHOD_GET); // тип объекта для автоматического выбора вкладки с объектами указанных типов
        Response::SetArray('object_types',Config::$values['object_types']);
        //устанавливаем флаг favorite_page, чтобы скрыть кнопки "в архив" и "редактировать"
        $favorite_page = TRUE;
        Response::SetBoolean('favorite_page',$favorite_page);
        $list = [];
        $first = true;    // автоматический выбор первой вкладки, если не передан тип объекта в GET
        foreach (Config::$values['object_types'] as $alias => $obj){     // формирование списка с избранным
            if (!empty(Favorites::$data_array[$obj['key']])){
                switch($alias){
                    case 'live':  // получение списка жилой недвижимости
                        $estate = new EstateListLive();
                        $where = $estate->work_table.'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                        $orderby = $estate->work_table.".status = 4 DESC, ".$estate->work_table.".id_main_photo>0 DESC, ".$estate->work_table.".date_change DESC, ".$estate->work_table.".date_in DESC";
                        $list[$alias] = $estate->Search($where,count(Favorites::$data_array[$obj['key']]),0);
                        $amount_list[$alias] = count($list[$alias]);
                        $img_folders[$alias] = Config::$values['img_folders'][$alias];
                        
                        if (count($list[$alias])==0){  // На случай, когда объект в избранном есть, но физически его больше нет (удален в личном кабинете)
                            unset($list[$alias]);
                        } else
                            if ((($first && empty($object_type)) || strcmp($object_type,$alias)==0)){
                                // Установка флага активной вкладки для случая, когда не передан в GET тип объекта
                                $list[$alias][0]['favorites'] = 1;
                                $first = false;
                            }
                           
                    break;
                    case 'build':  // получение списка новостроек
                        $estate = new EstateListBuild();
                        $where = $estate->work_table.'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                        $orderby = $estate->work_table.".status = 4 DESC, ".$estate->work_table.".id_main_photo>0 DESC, ".$estate->work_table.".date_change DESC, ".$estate->work_table.".date_in DESC";
                        $list[$alias] = $estate->Search( $where, count( Favorites::$data_array[$obj['key']] ), 0 );
                        $amount_list[$alias] = count( $list[$alias] );
                        $img_folders[$alias] = Config::$values['img_folders'][$alias];
                        
                        if (count($list[$alias])==0){  // На случай, когда объект в избранном есть, но физически его больше нет (удален в личном кабинете)
                            unset($list[$alias]);
                        } else
                            if (($first && empty($object_type)) || strcmp($object_type,$alias)==0){
                                // Установка флага активной вкладки для случая, когда не передан в GET тип объекта
                                $list[$alias][0]['favorites'] = 1;
                                $first = false;
                            }
                    break;
                    case 'commercial':  // получение списка коммерческой недвижимости
                        $estate = new EstateListCommercial();
                        $where = $estate->work_table.'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                        $orderby = $estate->work_table.".status = 4 DESC, ".$estate->work_table.".id_main_photo>0 DESC, ".$estate->work_table.".date_change DESC, ".$estate->work_table.".date_in DESC";
                        $list[$alias] = $estate->Search($where,count(Favorites::$data_array[$obj['key']]),0);
                        $amount_list[$alias] = count($list[$alias]);
                        $img_folders[$alias] = Config::$values['img_folders'][$alias];
                        
                        if (count($list[$alias])==0){   // На случай, когда объект в избранном есть, но физически его больше нет (удален в личном кабинете)
                            unset($list[$alias]);
                        } else
                            if (($first && empty($object_type)) || strcmp($object_type,$alias)==0){
                                // Установка флага активной вкладки для случая, когда не передан в GET тип объекта
                                $list[$alias][0]['favorites'] = 1;
                                $first = false;
                            }
                    break;
                    case 'country':  // получение списка загородной недвижимости
                        $estate = new EstateListCountry();
                        $where = $estate->work_table.'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                        $orderby = $estate->work_table.".status = 4 DESC, ".$estate->work_table.".id_main_photo>0 DESC, ".$estate->work_table.".date_change DESC, ".$estate->work_table.".date_in DESC";
                        $list[$alias] = $estate->Search($where,count(Favorites::$data_array[$obj['key']]),0);
                        $amount_list[$alias] = count($list[$alias]);
                        $img_folders[$alias] = Config::$values['img_folders'][$alias];
                        
                        if (count($list[$alias])==0){  // На случай, когда объект в избранном есть, но физически его больше нет (удален в личном кабинете)
                            unset($list[$alias]);
                        } else
                            if (($first && empty($object_type)) || strcmp($object_type,$alias)==0){
                                // Установка флага активной вкладки для случая, когда не передан в GET тип объекта
                                $list[$alias][0]['favorites'] = 1;
                                $first = false;
                            }
                    break;
                    case 'zhiloy_kompleks':  // получение списка жилых комплексов
                        $housing_estates = new HousingEstates();
                        $where = $sys_tables['housing_estates'].'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                        $orderby = $sys_tables['housing_estates'].".advanced = 1 DESC, ".$sys_tables['housing_estates'].".id_main_photo > 0 DESC, ".
                                   $sys_tables['housing_estates'].".id_region DESC, ".$sys_tables['housing_estates'].".id_district > 0 DESC, district ASC, district_area ASC"; 
                        $list[$alias] = $housing_estates->Search($where,count(Favorites::$data_array[$obj['key']]),0,$orderby);
                        foreach($list[$alias] as $key=>$item){
                            //$list[$alias][$key]['building_type'] = preg_replace('/\s?(жилой комплекс)\s?/','',$item['building_type']);
                            $list[$alias][$key]['class'] = (!empty($item['class']))?mb_strtolower($item['class'],"UTF-8"):"";
                            //если класс короткий, значит это буква-обозначение или две буквы и палка
                            if (!empty($list[$alias][$key]['building_type'])){
                                $list[$alias][$key]['building_type'] = trim($list[$alias][$key]['building_type']);
                                if (mb_strlen($list[$alias][$key]['building_type'],"UTF-8")<4){
                                    $type = explode('/',$list[$alias][$key]['building_type']);
                                    foreach($type as $type_key=>$type_item){
                                        switch($type_item){
                                            case 'м': $type[$type_key] = 'монолитн';break;
                                            case 'к': $type[$type_key] = 'кирпичн';break;
                                            case 'п': $type[$type_key] = 'панельн';break;
                                            case 'с': $type[$type_key] = 'сборн';break;
                                        }
                                    }
                                    if (count($type)==1) $type[0] .= 'ый';
                                    else{
                                        $type[0] .= 'о-';
                                        $type[1] .= 'ый';
                                    }
                                    $list[$alias][$key]['building_type'] = implode('',$type)." жилой комплекс";
                                    unset($type);
                                }
                                else{
                                    //если класс не короткий и нет пробелов, надо прибавить "жилой комплекс"
                                    if (!preg_match('/\s/',$list[$alias][$key]['building_type'])){
                                        $list[$alias][$key]['building_type'] .= ' жилой комплекс';
                                    }
                                }
                            }
                            $list[$alias][$key]['building_type'] = ucfirst($list[$alias][$key]['building_type']);
                        }
                        $amount_list[$alias] = count($list[$alias]);
                        if ($alias == 'zhiloy_kompleks') $img_folders['housing_estates'] = Config::$values['img_folders']['housing_estates'];
                        else $img_folders[$alias] = Config::$values['img_folders'][$alias];
                        if (count($list[$alias])==0){  // На случай, когда объект в избранном есть, но физически его больше нет (удален в личном кабинете)
                            unset($list[$alias]);
                        } else
                            if (($first && empty($object_type)) || strcmp($object_type,$alias)==0){
                                // Установка флага активной вкладки для случая, когда не передан в GET тип объекта
                                $list[$alias][0]['favorites'] = 1;
                                $first = false;
                            }
                    break;
                    case 'cottedzhnye_poselki':  // получение списка коттеджных поселков
                        $cottages = new Cottages();
                        $where = $sys_tables['cottages'].'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                        $orderby = $sys_tables['cottages'].".advanced = 1 DESC, ".$sys_tables['cottages'].".id_main_photo > 0 DESC, ".
                                   $sys_tables['cottages'].".id_district_area > 0 DESC, district_title ASC"; 
                        $list[$alias] = $cottages->getList(count(Favorites::$data_array[$obj['key']]),0,$where,$orderby);
                        $ids = [];
                        $clear_ids = [];
                        foreach($list[$alias] as $k=>$item){
                            $types = [];
                            $ids[] = '('.$item['id'].', 2, "'.Host::getUserIp().'", "'.$db->real_escape_string($_SERVER['HTTP_USER_AGENT']).'", "'.Host::getRefererURL().'")';
                            $clear_ids[] = $item['id'];
                            //Host::getUserIp()."','".$_SERVER['HTTP_USER_AGENT']."','".Host::getRefererURL()
                            if($item['u_count']>0) $types[] = 'участки';
                            if($item['c_count']>0) $types[] = 'коттеджи';
                            if($item['t_count']>0) $types[] = 'таунхаусы';
                            if($item['k_count']>0) $types[] = 'квартиры';
                            $list[$alias][$k]['types'] = implode(', ',$types);
                        }
                        $amount_list[$alias] = count($list[$alias]);
                        if (count($list[$alias])==0){  // На случай, когда объект в избранном есть, но физически его больше нет (удален в личном кабинете)
                            unset($list[$alias]);
                        } else
                            if (($first && empty($object_type)) || strcmp($object_type,$alias)==0){
                                // Установка флага активной вкладки для случая, когда не передан в GET тип объекта
                                $list[$alias][0]['favorites'] = 1;
                                $first = false;
                            }
                    break;
                    case 'business_centers':  // получение списка бизнес-центров
                        $bc = new BusinessCenters();
                        $where = $sys_tables['business_centers'].'.id IN ('.implode(",",Favorites::$data_array[$obj['key']]).')';
                        $orderby = $sys_tables['business_centers'].".advanced = 1 DESC, ".$sys_tables['business_centers'].".id_main_photo > 0 DESC, ".
                                   $sys_tables['business_centers'].".id_region DESC, ".$sys_tables['business_centers'].".id_district > 0 DESC, district ASC, district_area ASC"; 
                        $list[$alias] = $bc->getList(count(Favorites::$data_array[$obj['key']]),0,$where,$orderby);
                        $amount_list[$alias] = count($list[$alias]);
                        if (count($list[$alias])==0){   // На случай, когда объект в избранном есть, но физически его больше нет (удален в личном кабинете)
                            unset($list[$alias]);
                        } else
                            if (($first && empty($object_type)) || strcmp($object_type,$alias)==0){
                                // Установка флага активной вкладки для случая, когда не передан в GET тип объекта
                                $list[$alias][0]['favorites'] = 1;
                                $first = false;
                            }
                    break;
                }
            }                
        }
        $h1 = empty($this_page->page_seo_h1) ? 'Личный кабинет' : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);  // заголовок
        $this_page->page_seo_title = empty($this_page->page_seo_title) ? $h1 : $this_page->page_seo_title;
        Response::SetArray('object_types',Config::$values['object_types']);
        Response::SetArray('full_list', $list);
        if(!empty($amount_list))Response::SetArray('amount_list',$amount_list);
        Response::SetArray('img_folders',$img_folders);
        Response::SetBoolean('favorites',true);  // флаг для представлений списков объектов: выводить последним столбцом "звезду" или "крестик"
        Response::SetString('page','favorites');
        $module_template = '/modules/favorites/templates/list.html';
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Удаление из избранного
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='unclick'  && count($this_page->page_parameters) == 1:       
        $object_id = Request::GetInteger('id',METHOD_POST);    // id объекта
        $object_type = Request::GetString('type',METHOD_POST); // тип объекта
        if (!is_null($object_id) && !is_null($object_type)){
            $object_type = Config::$values['object_types'][$object_type]['key'];
            $ajax_result['ok'] = Favorites::Remove($object_id,$object_type);
        } else $this_page->http_code=404;
        break;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Добавление в избранное
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    case $action=='click' && count($this_page->page_parameters) == 1:                    
        $object_id = Request::GetInteger('id',METHOD_POST);    // id объекта
        $object_type = Request::GetString('type',METHOD_POST); // тип объекта
        if (!is_null($object_id) && !is_null($object_type)){
            $arr[Config::$values['object_types'][$object_type]['key']] = array($object_id);
            $ajax_result['ok'] = Favorites::Add($arr);
        } else $this_page->http_code=404;
        break;
    default:
        $this_page->http_code=404;
        break;
}
?>
