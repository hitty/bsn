<?php
require_once('includes/class.paginator.php');
require_once('includes/class.estate.php');
require_once('includes/class.housing_estates.php');
require_once('includes/class.estate.statistics.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.videos.php');
Response::SetString('img_folder',Config::Get('img_folders/live'));
// определяем тип недвижимости
$estate_type = "";
$estate_types = array('live','build','commercial','country','inter');
if(!empty($this_page->module_parameters['estate_type']) && in_array($this_page->module_parameters['estate_type'], $estate_types)){
    $estate_type = $this_page->module_parameters['estate_type'];    
} elseif(!empty($this_page->page_parameters[0]) && in_array($this_page->page_parameters[0], $estate_types)) {
    $estate_type = $this_page->page_parameters[0];
}

// определяем тип сделки
$deal_type = '';
$deal_types = array('rent','sell');
if(!empty($this_page->module_parameters['deal_type']) && in_array($this_page->module_parameters['deal_type'], $deal_types)){
    $deal_type = $this_page->module_parameters['deal_type'];    
} elseif(!empty($this_page->page_parameters[0]) && in_array($this_page->page_parameters[0], $deal_types)) {
    $deal_type = $this_page->page_parameters[0];    
} 
// определяем ID объекта
$id = 0;
if(!empty($this_page->module_parameters['object_id']) && Validate::Digit($this_page->module_parameters['object_id']))
    $id = Convert::ToInteger($this_page->module_parameters['object_id']);
elseif(!empty($this_page->page_parameters[1]) && Validate::Digit($this_page->page_parameters[1])) 
    $id = Convert::ToInteger($this_page->page_parameters[1]);

$map_mode = Request::GetString('map',METHOD_POST);
$sort_mode = Request::GetString('sort',METHOD_POST);

// определяем возможный запрошенный экшн
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

$GLOBALS['css_set'][] = '/modules/housing_estates/style.css';
$GLOBALS['css_set'][] = '/modules/applications/style.css';

$object_type = "";
switch($estate_type){
     case 'live':
     case 'build':
     case 'commercial':
     case 'country':
        $object_type = Config::$values['object_types'][$estate_type]['key'];
     break;
     default:
        if (!empty($this_page->page_parameters[0]) && !empty(Config::$values['object_types'][$this_page->page_parameters[0]]['key']))
            $object_type = Config::$values['object_types'][$this_page->page_parameters[0]]['key'];
     break;
}

//редирект с /search/ на без /search/
if(strstr($this_page->real_url, '/search/') != '') {
    Host::Redirect( str_replace('search/', $estate_type == 'build' ? '' : '', $this_page->real_url) );
}
//редирект с /estate/ на без /estate/
if(strstr($this_page->real_url, 'estate/') != '' && !empty($estate_type) && $this_page->first_instance) {
    if(strstr($this_page->real_url, 'estate/build/zhiloy_kompleks')) Host::Redirect( '/' . str_replace('estate/build/', '',  trim($this_page->real_url,'/' )) . '/' );
    elseif(strstr($this_page->real_url, 'estate/country/cottedzhnye_poselki')) Host::Redirect( '/' . str_replace('estate/country/', '',  trim($this_page->real_url,'/' )) . '/' );
    elseif(strstr($this_page->real_url, 'estate/commercial/business_centers')) Host::Redirect( '/' . str_replace('estate/commercial/', '',  trim($this_page->real_url,'/' )) . '/' );
    else Host::Redirect( '/' . str_replace('estate/', '',  trim($this_page->real_url,'/' )) . '/' );
}
//проверка новостроек на старые урлы
if($estate_type=='build'){
    if((!empty($action) && ($action=='sell' || $action=='kvartira') && !empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'flats')) Host::Redirect('/' . str_replace('/sell/flats', '/sell', $this_page->real_url) . '/');
    if($deal_type == 'rent') Host::Redirect('/' . str_replace('/rent', '/sell', $this_page->real_url) . '/');
}
// обработка общих action-ов
switch(true){
    //////////////////////////////////////////////////////////////////////////////
    // главная страница раздела недвижимость - статистика по всему рынку недвижки
    //////////////////////////////////////////////////////////////////////////////
    case empty($action) && count($this_page->page_parameters) == 0:                                                                // URL = '/
        $GLOBALS['css_set'][] = '/css/estate_catalog.css';
        $popular_list = $memcache->get('bsn::estate::popular_list');
        if($popular_list === FALSE) {
            $popular_list = EstateStat::GetCountPopular();
            $memcache->set('bsn::estate::popular_list', $popular_list, FALSE, Config::$values['blocks_cache_time']['estate_popular_list']);
        }
        if(!empty($popular_list)) Response::SetArray('popular_list', $popular_list);            
        //не показывать левый ТГБ
        if(empty($clauses['id_type_object']) && $estate_type!='build') Response::SetBoolean('left_vip_not_show', true); 
            
        $module_template = 'list.catalog.html';
        $h1 = empty($this_page->page_seo_h1) ?  'Новостройки' : $this_page->page_seo_h1;
        Response::SetString('h1', $h1);

        Response::SetBoolean('estate_main', true);
        Response::SetBoolean('payed_format', true);
        break;
    ///////////////////////////////////////////////////////
    // флаги (страны) зарубежки
    ///////////////////////////////////////////////////////
    case $action=='inter' 
        && !empty($this_page->page_parameters[0]) 
        && $this_page->page_parameters[0]=='flags' 
        && count($this_page->page_parameters)==2:                                       // URL = '/inter/flags/
        $module_template = 'inter.flags.html';
        $countries = $db->fetchall("SELECT * FROM ".$sys_tables['foreign_countries']." WHERE active=1 ORDER BY title");
        Response::SetArray('countries', $countries); 
        
        $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        break;
    ///////////////////////////////////////////////////////
    // карточка объекта для зарубежки (старый вариант адреса)
    ///////////////////////////////////////////////////////
    case !empty($estate_type) 
        && !empty($this_page->page_parameters[0]) 
        && $this_page->page_parameters[0]=='info' 
        && !empty($this_page->page_parameters[1]) 
        && Validate::Digit($this_page->page_parameters[1])
        && count($this_page->page_parameters)==3:                             // URL = '/inter/info/123456/'  (старые адреса)
        $estateItem = new EstateItemInter($id);
        if($estateItem->data_loaded){
            $rent = $estateItem->getField('rent');
            $id = $estateItem->getField('id');
            Host::Redirect('/inter/'.($rent==1?'rent':'sell').'/'.$id.'/');
            exit();    
        } else {
            Host::RedirectLevelUp();
        }
        break;
    ///////////////////////////////////////////////////////
    // карточка объекта   вида  URL = '/live/1234567/'  - редирект на новый URL = '/live/rent||sell/1234567'
    ///////////////////////////////////////////////////////
    case (Validate::Digit($action) && empty($this_page->page_parameters[1])):
        $id = Convert::ToInteger($action);
        // в зависимости от рынка создаем нужный объект
        switch($estate_type){
            case 'live':
                $estateItem = new EstateItemLive($id);
                break;
            case 'build':
                $estateItem = new EstateItemBuild($id);
                break;
            case 'commercial':
                $estateItem = new EstateItemCommercial($id);
                break;
            case 'country':
                $estateItem = new EstateItemCountry($id);
                break;
            case 'inter':
                $estateItem = new EstateItemInter($id);
                break;
            default:
                $estateItem = null;
                Host::RedirectLevelUp();
                break;
        }
        // если объект не пуст, значит он есть в БД
        if(!empty($estateItem) && !empty($estateItem->data_loaded)){
            $item = $estateItem->getData();
            $item = Favorites::ToItem($item,$object_type);
            $deal_type = !empty($item['rent']) && $item['rent']==1 ? 'rent' : 'sell';
            Host::Redirect('/'.$estate_type.'/'.$deal_type.'/'.$id.'/');
        } else Host::RedirectLevelUp();
        break;
  
    ///////////////////////////////////////////////////////
    // карточка объекта   вида  URL = 'estate/live/rent||sell/1234567/' или печать карточки - URL = 'estate/live/rent||sell/1234567/print'
    ///////////////////////////////////////////////////////
    case !empty($estate_type) && !empty($deal_type) && !empty($id) &&(empty($this_page->page_parameters[2]) || ($this_page->page_parameters[2]=='print'&& count($this_page->page_parameters)==4)) :  // URL = '/live/rent/1234567/'
        $print=!empty($this_page->page_parameters[2]);
        // в зависимости от рынка создаем нужный объект
        switch($estate_type){
            case 'live':
                $estateItem = new EstateItemLive($id);
                $estate = new EstateListLive();
                break;
            case 'build':
                $estateItem = new EstateItemBuild($id);
                $estate = new EstateListBuild();
                break;
            case 'commercial':
                $estateItem = new EstateItemCommercial($id);
                $estate = new EstateListCommercial();
                break;
            case 'country':
                $estateItem = new EstateItemCountry($id);
                $estate = new EstateListCountry();
                break;
            case 'inter':
                $estateItem = new EstateItemInter($id);
                $estate = new EstateListInter();
                break;
            default:
                $estateItem = null;
                Host::RedirectLevelUp();
                break;
        }
        // если объект не пуст, значит он есть в БД
        if(!empty($estateItem) && !empty($estateItem->data_loaded)){
            Response::SetString('this_page_url', Host::$protocol . '://'.$_SERVER['HTTP_HOST'].'/'.$this_page->requested_url.'/');
            if ($print){
                $GLOBALS['css_set'][]='/css/print.css';
                $GLOBALS['js_set'][]='/modules/estate/print.js';
                $this_page->page_template='/templates/print.html';
                Response::SetBoolean('print',TRUE);
            }else {
                $GLOBALS['css_set'][]='/css/estate_search.css';
                $GLOBALS['css_set'][]='/css/jquery-ui.css';
                $GLOBALS['js_set'][]='/js/jquery-ui.min.js';

                $GLOBALS['js_set'][]='/modules/credit_calculator/mortgage-application.js';
                $GLOBALS['js_set'][]='/modules/credit_calculator/block.js';
                $GLOBALS['css_set'][]='/modules/credit_calculator/block.css';
                
                $GLOBALS['css_set'][] = '/js/carousel/carousel.css';
                $GLOBALS['js_set'][] =  '/js/carousel/carousel.js';

            }
            $module_template = 'item.html';
            
            Response::SetBoolean( 'payed_format', true );
            
            $item = $estateItem->getData();
            
            if(!empty($item['id_type_object'])) {
                if($estate_type != 'build'){
                    $object_type_group = $db->fetch("SELECT * FROM ".$sys_tables['type_objects_' . $estate_type]." WHERE id = ?", $item['id_type_object']);
                    $object_type_id = $object_type_group['id_group'];
                } else $object_type_id = 1;
            }
            //для архивного объекта редирект на максимально релевантную выдачу
            if( ( $item['published'] == 2 || $item['published'] == 4 ) && empty( Request::GetString( 'show', METHOD_GET ) ) ){
                $params['obj_type'] = $object_type_id;
                //набор параметров
                $params = [];
                if(!empty($item['rooms_sale']) && $item['id_type_object'] == 2) $params['rooms_sale'] = $item['rooms_sale'];
                if(!empty($item['rooms_total'])) $params['rooms'] = $item['rooms_total'];

                if(!empty($item['id_street'])) {
                    $street = $db->fetch("SELECT * FROM ".$sys_tables['geodata']." WHERE id_region = ? AND id_area = ? AND id_city = ? AND id_place = ? AND id_street = ?",
                        $item['id_region'], $item['id_area'], $item['id_city'], $item['id_place'], $item['id_street']
                    );
                    $params['geodata'] = $street['id'];
                } 
                if(!empty($item['id_area']) && !empty($item['id_region']) && $item['id_region'] == 47 && empty($item['id_street'])) {
                    $area = $db->fetch("SELECT * FROM ".$sys_tables['geodata']." WHERE id_region = ? AND id_area = ? AND id_city = 0 AND id_place = 0 AND id_street = 0", 
                        $item['id_region'], $item['id_area']
                    );
                    $params['district_areas'] = $area['id'];
                } 
                if(!empty($item['id_subway']) && empty($item['id_street']) && !(!empty($item['id_area']) && !empty($item['id_region']) && $item['id_region'] == 47) ) $params['subways'] = $item['id_subway'];
                if(!empty($item['id_district']) && empty($item['id_street']) && !(!empty($item['id_area']) && !empty($item['id_region']) && $item['id_region'] == 47) ) $params['districts'] = $item['id_district'];
                if(!empty($item['id_country'])) $params['country'] = $item['id_country'];
                ksort($params);
                $url = $estate_type . '/' . $deal_type . '/?'.http_build_query($params);
                //поиск чпу страницы
                $chpu = $db->fetch('SELECT * FROM '.$sys_tables['pages_seo']." WHERE url = ?", $url);
                Session::SetBoolean('from_archive_page', true);
                if(!empty($chpu)) Host::Redirect('/' . $chpu['pretty_url'] . '/' );
                Host::Redirect('/' . $estate_type . '/' . $deal_type . '/?'.http_build_query($params));
                
                
                
            } else {
                //если это не печать карточки и не архивный объект, увеличиваем счетчик просмотров
                if (!$print && !$item['archive_object']){
                    $estateItem->setField('views_count', $estateItem->getField('views_count')+1);
                    if($estate_type!='inter') $estateItem->getComplexCoord();
                    $estateItem->Save();
                }
                
                //если это Пет. недвижимость, делаем Response, чтобы вместо обычного консультанта был их
                //if(!empty($item['id_user']) && $item['id_user'] == 1454) Response::SetBoolean('petned_consultant',true);
                Response::SetBoolean('rfn_noconsultant',true);
                $deal_type = $this_page->page_parameters[0];
                if($item['published']>2) Host::Redirect('/'.$estate_type.'/'.$deal_type.'/');
                      
                $info = $estateItem->getInfo();
                
                $geo_info = $estateItem->getGeoNames();
                
                //формирование ссылок с ЧПУ на  категории (метро, район, комнатность)
                if(!empty($item['id_type_object']) || $estate_type == 'build'){
                    //район города
                    if(!empty($item['id_district']) && $item['id_region'] == 78) {
                        $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$estate_type.'/'.$deal_type.'/?districts='.$item['id_district'].($estate_type!='build' ? '&obj_type='.$object_type_id:''),0);
                        if(!empty($page)) Response::SetArray('district_link',$page);
                        
                    }
                    //район области
                    if(!empty($item['id_area']) && !empty($item['id_region']) && $item['id_region']==47) {
                        $geo = $db->fetch("SELECT id FROM ".$sys_tables['geodata']." WHERE a_level = ? AND id_region = ? AND id_area = ?", 2, 47, $item['id_area']);
                        if(!empty($geo)){
                            $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$estate_type.'/'.$deal_type.'/?district_areas='.$geo['id'].($estate_type!='build' ? '&obj_type='.$object_type_id:''),0);
                            if(!empty($page)) Response::SetArray('district_area_link',$page);
                        }
                    }
                    
                    //город
                    if(!empty($geo_info['city'])) {
                        $geo = $geo_info['city'];
                        $info['city'] = $geo['title'];
                        if(!empty($geo['id'])){
                            $link = $estate_type.'/'.$deal_type.'/?geodata='.$geo['id'].($estate_type!='build' ? '&obj_type='.$object_type_id:'');
                            $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$link,0);
                            if(!empty($page)) Response::SetArray('city_link',$page);
                            else Response::SetArray('city_link',array('pretty_url'=>$link,'h1_title'=>$geo['title']));
                        }
                    }
                    
                    //место
                    if(!empty($geo_info['place'])) {
                        $geo = $geo_info['place'];
                        $info['place'] = $geo['title'];
                        if(!empty($geo['id'])){
                            $link = $estate_type.'/'.$deal_type.'/?geodata='.$geo['id'].($estate_type!='build' ? '&obj_type='.$object_type_id:'');
                            $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$link,0);
                            if(!empty($page)) Response::SetArray('place_link',$page);
                            else{
                                $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ?",$estate_type.'/'.$deal_type.'/'.($estate_type!='build' ? '?obj_type='.$item['id_type_object']:''));
                                $geo['title'] = (empty($page) ? $geo['title'] : $page['h1_title']." по адресу ".$geo['title']);
                                Response::SetArray('place_link',array('pretty_url'=>$link,'h1_title'=>$geo['title']));
                            }
                        }
                    }
                    
                    //улица
                    if(!empty($geo_info['street'])) {
                        $geo = $geo_info['street'];
                        $info['street'] = trim($geo['title'], ',');
                        if(!empty($geo['id'])){
                            $link = $estate_type.'/'.$deal_type.'/?geodata='.$geo['id'].($estate_type!='build' ? '&obj_type='.$object_type_id:'');
                            $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$link,0);
                            if(!empty($page)) Response::SetArray('street_link',$page);
                            else Response::SetArray('street_link',array('pretty_url'=>$link,'h1_title'=>$info['street']));
                        }
                    }
                    
                    //метро              
                    if(!empty($item['id_subway'])) {
                        $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$estate_type.'/'.$deal_type.'/?'.($estate_type!='build' ? 'obj_type='.$object_type_id.'&':'').'subways='.$item['id_subway'],0);
                        if(!empty($page)) Response::SetArray('subway_link',$page);
                        
                    }
                    
                    //тип объекта (жилая и новостройки)
                    if(!empty($item['rooms_total']) && $item['id_type_object'] < 3){
                        $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$estate_type.'/'.$deal_type.'/?'.($estate_type!='build' ? 'obj_type=1&':'').'rooms='.$item['rooms_total'],0);
                        if(!empty($page)) Response::SetArray('type_object_link',$page);                    
                    }   else {
                        $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$estate_type.'/'.$deal_type.'/?'.($estate_type!='build' ? 'obj_type='.$item['id_type_object']:''),0);
                        if(!empty($page)) Response::SetArray('type_object_link',$page);                    
                    }
                    if(!empty($item['rooms_total']) && !empty($item['rooms_sale']) && $item['rooms_total']!=$item['rooms_sale']){
                        $page = $db->fetch("SElECT * FROM ".$sys_tables['pages_seo']." WHERE url = ? AND filled > ?",$estate_type.'/'.$deal_type.'/?'.($estate_type!='build' ? 'obj_type=2&':'').'rooms='.$item['rooms_sale'],0);
                        if(!empty($page)) Response::SetArray('rooms_object_link',$page);                    
                    }
                                
                }
                
                if (!$print){
                    
                    // если был задан тип сделки, то он долен точно соответствовать типу сделки в объекте
                    if(!empty($deal_type)){
                        $deal_code = $deal_type=='rent' ? 1 : 2;
                        if(Convert::ToInteger($item['rent'])!=$deal_code){
                            Host::RedirectLevelUp();
                            break;
                        } 
                    }
                    
                    $GLOBALS['css_set'][] = '/css/phones.click.css';
                    $GLOBALS['js_set'][] = '/js/phones.click.js';
                }
                
                $GLOBALS['js_set'][] = '/js/form.validate.js';
                $GLOBALS['css_set'][] = '/modules/applications/style.css';
                $GLOBALS['js_set'][] = "/modules/favorites/favorites.js";
                $GLOBALS['js_set'][] = "/modules/estate/list_options.js";
                $GLOBALS['js_set'][] = '/modules/infrastructure/yandex.map.js';
                $GLOBALS['css_set'][] = '/modules/infrastructure/styles.css';
                $GLOBALS['js_set'][] = '/modules/estate/item.js';
                
                
                if(!empty($item['date_in'])) $item['date_in_normal'] = date('d.m.Y', strtotime($item['date_in']));
                if(!empty($item['date_change'])) $item['date_change_normal'] = date('d.m.Y', strtotime($item['date_change']));
                
                $photos = Photos::getList( $estate_type, $id );
                if( empty( $photos ) ) $photos = Photos::getList($estate_type, false, false, false, false, $item['id_main_photo'] );
                Response::SetArray('photos', $photos);
                
                $info['shows_full'] = (empty($info['shows_full'])?0:$info['shows_full']) + (empty($item['views_count'])?0:$item['views_count']);
                 
                if(!empty($info['agency_advert_phone'])) {
                    //проверка на наличие баланса для открытия телефона
                    $info['seller_phone'] = $item['seller_phone'] = '';
                    if(strlen($info['agency_advert_phone'])<7) $item['seller_phone'] = '';
                    else {
                        $agency_phone = Convert::ToPhone($info['agency_advert_phone']);
                        $item['seller_phone'] = $agency_phone[0];
                    }
                    $info['agency_phone_1'] = $info['agency_phone_2'] = $info['agency_phone_3'] = '';
                }
                else  {
                    if(!empty($item['seller_phone'])) $seller_phone = Convert::ToPhone($item['seller_phone']);
                    elseif($estate_type == 'inter' && !empty($info['seller_phone'])) $seller_phone = Convert::ToPhone($info['seller_phone']);
                    else $seller_phone = '';
                    if(!empty($seller_phone[0]) && strlen($seller_phone[0])>=7) {
                        if( $estate_type == 'inter' ) $info['seller_phone'] = $seller_phone[0];
                        else $item['seller_phone'] = $seller_phone[0];
                    } elseif(!empty($info['agency_phone_1']) && strlen($item['seller_phone']>=7)) {
                        $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_1'], $item['id_user'], $estate_type);
                    } elseif(!empty($info['agency_phone_2']) && strlen($item['seller_phone']>=7)) {
                        $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_2'], $item['id_user'], $estate_type); 
                    } elseif(!empty($info['agency_phone_3']) && strlen($item['seller_phone']>=7)) {
                        $item['seller_phone'] = EstateStat::getPhone($info['agency_phone_3'], $item['id_user'], $estate_type);
                    }
                }
                
                //вывод рекламного блока
                if(!empty($info['agency_advert_text'])) Response::SetString('advert_text',$info['agency_advert_text']);
                
                //контекстный блок
                $info['estate_type'] = $estate_type;
                $info['deal_type'] = $deal_type;
                $info['price'] = $item['cost'];
                $info['rooms'] = (!empty($item['rooms_sale']))?$item['rooms_sale']:"";
                Response::SetInteger('object_id',$id);
                
                Response::SetString('object_info',json_encode($info));
                $item = Favorites::ToItem($item,$object_type);
                
                //формируем расширенное описание
                if($estate_type == 'live' || $estate_type == 'build') $item['text_description'] = $estateItem->getTextDescription();
                
                //убираем атрибуты из тегов описания
                $item['notes'] = preg_replace('/<([A-z][A-z0-9]*)[^>]*?(\/?)>/sui','<$1$2>', !empty($item['notes']) ? $item['notes'] : ( !empty($item['info']) ? $item['info'] : '') );
                $item['notes'] = str_replace('&nbsp;', ' ', $item['notes']);
                $item['notes'] = str_replace( '&amp;', '&', $item['notes'] );
                
                Response::SetArray('item', $item); 
                Response::SetArray('info', $info);
                            
                $empty_extra = true;
                // Проверки на то, есть ли что-то в "Доп.информации"
                switch($estate_type){
                    case 'live':
                        if (!empty($item['level']) || !empty($item['level_total']) || !empty($info['building_type']) ||
                            $item['phone']==1 || $item['wash_mash']==1 || $item['refrigerator']==1 || $item['furniture']==1 ||
                            $item['ceiling_height']>0 || !empty($info['toilet']) || !empty($info['floor']) || !empty($info['hot_water']) || 
                            !empty($info['facing']) || !empty($info['window']) || !empty($info['enter']) || !empty($info['elevator']) ||
                            !empty($info['balcon'])) 
                            $empty_extra = false; 
                        break;
                    case 'build':
                        if (!empty($item['id']) || !empty($info['building_type']) || $item['ceiling_height']>0 ||
                            !empty($item['level']) || !empty($item['level_total']) || !empty($info['toilet']) ||
                            !empty($info['facing']) || !empty($info['window']) || !empty($info['elevator']) || 
                            !empty($info['balcon']) || !empty($info['developer_status']) || !empty($info['build_complete']) || 
                            $item['build_completed']==1 || $item['build_in_operation']==1 || $item['installment']==1) 
                            $empty_extra = false;   
                        break;
                    case 'commercial':
                        if (!empty($item['id']) || $item['ceiling_height']>0 || !empty($item['phones_count']) ||
                            $item['parking']==1 || $item['security']==1 || $item['service_line']==1 || 
                            $item['canalization']==1 || $item['hot_water']==1 || $item['electricity']==1 ||
                            $item['heating']==1 || !empty($item['transport_entrance']) || !empty($item['rent_duration']) ||
                            !empty($info['facing']) || !empty($info['enter']))
                            $empty_extra = false;
                        break;
                    case 'country':
                        if (!empty($info['id']) || $item['phone']==1 || !empty($info['ownership']) || !empty($info['year_build']) ||
                            !empty($item['level_total']) || !empty($item['rooms']) || !empty($info['construct_material']) ||
                            !empty($info['roof_material']) || !empty($info['heating']) || !empty($info['electricity']) ||
                            !empty($info['water_supply']) || !empty($info['gas']) || !empty($info['toilet']) || !empty($info['bathroom']) || 
                            !empty($info['buildimg_progress']) || !empty($info['river']) || !empty($info['garden']))
                            $empty_extra = false;
                        break;
                }
                
                Response::SetBoolean('empty_extra',$empty_extra);
                
                $titles = $estateItem->getTitles($estate_type != 'inter' ? '' : $info);
                Response::SetArray('titles',$titles);
                if($ajax_mode) $ajax_result['ok']=true;
                //расчет кредитного калькулятора
                if( $item['rent']==2 ){
                    switch($estate_type){
                        case 'live':
                            $credit_type = 1;
                            break;
                        case 'build': 
                            $credit_type = 2;
                            break;
                        case 'commercial': 
                            $credit_type = 3;
                            break;
                        case 'country': 
                            $credit_type = 4;
                            break;
                    }            
                    $credit_calculator = $db->fetch("SELECT * FROM ".$sys_tables['credit_calculator']." WHERE `published` = ? AND `enabled` = ? AND `date_start` <= CURDATE() AND `date_end` > CURDATE() AND type=?",
                                        1, 1, $credit_type
                    );
                    if(!empty($credit_calculator)){
                        Response::SetBoolean('credit_calculator',true);
                        if(preg_match('/bspb\.ru/',$credit_calculator['direct_link'])){
                            Response::SetBoolean('bspb_calculator',true);
                        } 
                    } 
                }
                
                if(!empty($info['agency_title'])){//вид деятельности агентства
                    $agencies_activities = array(
                                             array('title'=>'Агентство')
                                            ,array('title'=>'Рекламное агентство') 
                                            ,array('title'=>'Застройщик') 
                                            ,array('title'=>'Управляющая компания') 
                                            ,array('title'=>'Банк') 
                                            ,array('title'=>'Девелопер') 
                                            ,array('title'=>'Инвестицинная компания') 
                                            ,array('title'=>'Другой профиль')
                    ); 
                    //вычисление видов деятельности по битовой маске
                    $activity = [];
                    foreach($agencies_activities as $key=>$val){
                        if($info['agency_activity']%(pow(2,$key+1))>=pow(2,$key)) $activity[]=$val['title'];
                    }
                    if(count($activity)!=1) $activity = array('Агентство');
                    Response::SetString('agency_activity',$activity[0]);
                }
                
                //определение координат
                if((empty($item['lat']) || empty($item['lng']) || $item['lat'] == 0 || $item['lng'] == 0 )){
                    require_once( 'includes/class.robot.php' );
                    $robot = new Robot( $item['id_user'] ) ;
                    if($estate_type == 'inter') $address = $info['country_title'].', '.$item['address'];
                    else $address = (!empty($info['district'])?'Санкт-Петербург, '.$info['district'].' район, ': (!empty($info['district_area']) ? 'Ленинградская область, '.$info['district_area'].' район' : 'Санкт-Петербург, ')).(!empty($item['address'])?$item['address']:'');
                    
                    $dadata = new SuggestClient( );
                    $data = array(
                        'query' => $address,
                        'count' => 2
                    );
                    $geo = $dadata->suggest( "address", $data );
                    $lat = !empty( $geo->suggestions[0]->data->geo_lat ) ? $geo->suggestions[0]->data->geo_lat : '';
                    $lng = !empty( $geo->suggestions[0]->data->geo_lon ) ? $geo->suggestions[0]->data->geo_lon : '';
                    if( !empty( $lat ) && !empty( $lat ) ) {
                        $item['lng'] = $lng;
                        $item['lat'] = $lat;
                        
                        $db->query("UPDATE ".$sys_tables[$estate_type]." SET lat=?, lng=? WHERE id=?",$item['lat'], $item['lng'],$item['id']);
                        Response::SetArray('item', $item);
                    }
                }
                //данные по пикселю FB
                Response::SetArray('fbq',
                     array(
                        'value'             =>  $item['cost'], 
                        'currency'          =>  'RUB', 
                        'content_name'      =>  $titles['title'], 
                        'content_type'      =>  'product', 
                        'content_ids'       =>  $item['id'], 
                        'content_category'  =>  Config::Get('object_types')[$estate_type]['name']
                    )
                );
                 // поиск аналогичных квартир в новостройках/жилой
                if($deal_type == 'sell' && ($estate_type == 'build' ||  ($estate_type == 'live') && $item['id_type_object'] == 1)){
                    $clauses = [];
                    $clauses['published'] = array('value'=> 1);
                    $clauses['id_type_object'] = array('value'=> $item['id_type_object']);
                    $clauses['rent'] = array('value'=> $item['rent']);
                    $clauses['cost']['from'] = $item['cost']*0.9; 
                    $clauses['cost']['to'] = $item['cost']*1.1; 
                    $estate = new EstateListLive();    

                    $estate_search = new EstateSearch();

                    $where = $estate->makeWhereClause($clauses);

                    if(!empty($reg_where)) $where .= " AND (".implode(" OR ", $reg_where).")";
                    if(!empty($range_where)) $where .= " AND (".implode(" AND ", $range_where).")";
                    $paginator = new Paginator($estate->work_table, 1, $where); 
                    if(!empty($paginator->items_count)) Response::SetString('objects_in_price',$paginator->items_count.' '.makeSuffix($paginator->items_count,'квартир',array('а','ы','')));
                }
                //Баннеры в районах
                if(!empty($item['id_district']) || (!empty($item['id_region']) && $item['id_region']==47)){
                    $district_banners = $db->fetch("SELECT * FROM ".$sys_tables['district_banners']." WHERE `published` = ? AND `enabled` = ? AND `date_start` <= CURDATE() AND `date_end` > CURDATE() AND id_district = ? ",
                                        1, 1,  !empty($item['id_region']) && $item['id_region']==47?47:$item['id_district']
                    ); 
                    if(!empty($district_banners)) Response::SetArray('district_banners',$district_banners);    
                }
                // меняем тайтл
                $this_page->manageMetadata(
                    array(
                        'title'=>$titles['title'],
                        'description'=>!empty($titles['description']) ? Convert::firstLetterUpperCase($titles['description']) : $titles['header'],
                        'keywords'=>$titles['header']
                    ), true
                );
            }
        } else Host::Redirect('/'.$estate_type.'/'.$deal_type.'/');
        Response::SetBoolean('left_tgb', true);
        break;
    //запись перехода из поиска на карточку (URL вида '/live/sell/18641281/from_search/')
    case !empty($estate_type) && !empty($deal_type) && !empty($id) && $ajax_mode &&(!empty($this_page->page_parameters[2]) && $this_page->page_parameters[2] == 'from_search') : 
        // в зависимости от рынка создаем нужный объект
        switch($estate_type){
            case 'live':
                $estateItem = new EstateItemLive($id);
                break;
            case 'build':
                $estateItem = new EstateItemBuild($id);
                break;
            case 'commercial':
                $estateItem = new EstateItemCommercial($id);
                break;
            case 'country':
                $estateItem = new EstateItemCountry($id);
                break;
            case 'inter':
                $estateItem = new EstateItemInter($id);
                break;
            default:
                $estateItem = null;
                Host::RedirectLevelUp();
                break;
        }
        //если объект не пуст, записываем ему переход с поиска в карточку
        if(!empty($estateItem) && !empty($estateItem->data_loaded)){
            $ajax_result['ok'] = $estateItem->updateFromSearch();
        }
        break;
    //////////////////////////////////////////////////////////////////////////
    // список объектов по набору тегов
    //////////////////////////////////////////////////////////////////////////
    case !empty($estate_type)
         &&!empty($deal_type)
         &&!empty($this_page->page_parameters[1]) && $this_page->page_parameters[1] == 'tags'
         && !empty($this_page->page_parameters[2])
         && count($this_page->page_parameters) == 4:
         
         Host::Redirect('/'.$estate_type.'/'.$deal_type.'/');
         break;

    //////////////////////////////////////////////////////////////////////////
    // списки объектов по рынку, статистика по рынку, карты
    //////////////////////////////////////////////////////////////////////////
    case !empty($estate_type) && 
        (count($this_page->page_parameters) == 1 && !empty($deal_type)) || 
        (!empty($deal_type) && empty($this_page->page_parameters[2]) && count($this_page->page_parameters)==2):    // URL = '/live/[rent/]
        if(!empty($estate_type) && !empty($deal_type) && !empty($this_page->page_parameters[1]))  $type_object = $this_page->page_parameters[1];  // URL = '/live/rent/rooms/
        if(!empty($type_object)){
            $types_group = $db->fetch("SELECT * FROM ".$sys_tables['object_type_groups']." WHERE alias=? AND type=?", $type_object, $estate_type);
            if(empty($types_group)){
                 Host::RedirectLevelUp();
                 break;
            }
        }  
        
        if(!empty($this_page->page_parameters[2]) && isPage($this_page->page_parameters[2])) Host::Redirect("/".$this_page->page_parameters[0]."/".$this_page->page_parameters[1]."/?page=".getPage($this_page->page_parameters[2])); 
        Response::SetString('tgb_type',$estate_type);
        
        //иначе бился фильтр у загородной
        $GLOBALS['js_set'][] ="/modules/estate/list_options.js";
        
        //параметры поиска для таргетинга
        $search_parameters = Request::GetParameters(METHOD_GET);
        $search_parameters['path'] = preg_replace('/\/\//','/',$search_parameters['path']);
        Response::SetString('search_parameters',json_encode(array('search_parameters'=>$search_parameters)));
        //при необходимости - трубка для попапа пет.недвижимости
        Response::SetBoolean('petned_call',(!empty($search_parameters['agency']) && $search_parameters['agency'] == 1454));
        $type_where = "";
        switch($estate_type){
            case 'build':
                $estate = new EstateListBuild();
                //заголовки h1
                $h1 = empty($this_page->page_seo_h1) ?  'Новостройки' : $this_page->page_seo_h1;
                Response::SetString('h1', $h1);
                $this_page->page_seo_title = empty($this_page->page_seo_title) ? $h1 : $this_page->page_seo_title;
                Response::SetString('h1', empty($this_page->page_seo_h1) ?  'Новостройки' : $this_page->page_seo_h1);
                break;
            case 'inter':
                $estate = new EstateListInter();
                //заголовки h1
                $h1 = empty($this_page->page_seo_h1) ? 'Зарубежная недвижимость' : $this_page->page_seo_h1;
                Response::SetString('h1', $h1);
                $this_page->page_seo_title = empty($this_page->page_seo_title) ? $h1 : $this_page->page_seo_title;
                break;
            case 'commercial':
                $estate = new EstateListCommercial();
                //заголовки h1
                $h1 = empty($this_page->page_seo_h1) ? 'Коммерческая недвижимость' : $this_page->page_seo_h1;
                Response::SetString('h1', $h1);
                $this_page->page_seo_title = empty($this_page->page_seo_title) ? $h1 : $this_page->page_seo_title;
                if(!empty($type_object)){
                    $types = $db->fetchall("SELECT id FROM ".$sys_tables['type_objects_commercial']." WHERE id_group=?", 'id', $types_group['id']);
                    $type_where = "id_type_object IN (".implode(',',array_keys($types)).")";
                }
                if(empty($deal_type)) {
                    Response::SetBoolean('commercial_last_items',true); //вывод последних объектов по типам
                    Response::SetString('view_type', 'list');   // вид всегда строчный
                }       
                break;
            case 'country':
                $estate = new EstateListCountry();
                //заголовки h1
                $h1 = empty($this_page->page_seo_h1) ? 'Загородная недвижимость'  : $this_page->page_seo_h1;
                Response::SetString('h1', $h1);
                $this_page->page_seo_title = empty($this_page->page_seo_title) ? $h1 : $this_page->page_seo_title;
                if(!empty($type_object)){
                    $types = $db->fetchall("SELECT id FROM ".$sys_tables['type_objects_country']." WHERE id_group=?", 'id', $types_group['id']);
                    $type_where = "id_type_object IN (".implode(',',array_keys($types)).")";
                }
                if(empty($deal_type)) {
                    Response::SetBoolean('country_last_items',true); //вывод последних объектов по типам
                    Response::SetString('view_type', 'list');   // вид всегда строчный
                }  
                break;
            case 'live':
                $estate = new EstateListLive();
                //заголовки h1
                $h1 = empty($this_page->page_seo_h1) ? 'Жилая недвижимость' : $this_page->page_seo_h1;
                Response::SetString('h1', $h1);
                $this_page->page_seo_title = empty($this_page->page_seo_title) ? $h1 : $this_page->page_seo_title;
                if(!empty($type_object)){
                    $types = $db->fetchall("SELECT id FROM ".$sys_tables['type_objects_live']." WHERE id_group=?", 'id', $types_group['id']);
                    $type_where = "id_type_object IN (".implode(',',array_keys($types)).")";
                }
                if(empty($deal_type)) {
                    Response::SetBoolean('live_last_items',true); //вывод последних объектов по типам
                    Response::SetString('view_type', 'list');   // вид всегда строчный
                }           
                break;
            default:
                $estate = null;
                Host::RedirectLevelUp();
                break;
        }
        if(empty($deal_type)) $deal_type = 'sell';
        //форма поиска
        $GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
        $GLOBALS['css_set'][] = '/css/estate_search.css';
        $GLOBALS['js_set'][] = '/modules/estate/interface.js';
        $GLOBALS['js_set'][] = "/modules/favorites/favorites.js";
        $GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';

        $GLOBALS['js_set'][] ="/js/subscription.js";

        $GLOBALS['css_set'][]='/css/jquery-ui.css';
        $GLOBALS['js_set'][]='/js/jquery-ui.min.js';
        $GLOBALS['js_set'][]='/modules/credit_calculator/block.js';
        $GLOBALS['css_set'][]='/modules/credit_calculator/block.css';
        
        
        if(!empty($estate)){     
            $parameters = Request::GetParameters(METHOD_GET);
            $GLOBALS['js_set'][] = '/js/form.validate.js';
            $GLOBALS['css_set'][] = '/css/autocomplete.css';
            
            $build_main_page = false;
            //заглавная страница новостроек - изменения по СЕО
            $group_by = false;

            // кол-во элементов в списке
            $count = !empty($parameters['housing_estate_page']) ? 10 : Request::GetInteger('count', METHOD_GET);
            if(empty($count)) $count = Cookie::GetInteger('View_count_estate');
            if(empty($count)) {
                $count = Config::$values['view_settings']['strings_per_page'];
                Cookie::SetCookie('View_count_estate', Convert::ToString($count), 60*60*24*30, '/');
            }
            $where = [];
            $estate_search = new EstateSearch();
            list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams();
            if( $deal_type == 'sell' && ($estate_type == 'build' || $estate_type == 'live' )  && ( empty($get_parameters['group_id'])) && empty($get_parameters['top_left_lat']) && empty($get_parameters['housing_estate']) && empty($get_parameters['company_page']) && empty($parameters['members_page']) && !( ( !empty( $ajax_mode ) && !empty( $parameters['agency'] ) ) )) $group_by =  'group_id, rooms_sale';
            //обнуление счетчика подписки при просмотре из ЛК и получение даты последнего просмотра подписки
            if (!empty($parameters['id_subscription']) && Validate::isDigit($parameters['id_subscription'])) {
                //получение даты последнего просмотра
                $estate_subscription = $db->fetch("SELECT *,IF(last_seen_setinterval  - INTERVAL 15 MINUTE > NOW(), last_seen_setinterval - INTERVAL 15 MINUTE, last_seen) as last_seen FROM ".$sys_tables['objects_subscriptions']." WHERE id = ?",$parameters['id_subscription']);
                if(!empty($estate_subscription)){
                    $clauses['date_in']['from'] = $estate_subscription['last_seen'];
                    if(empty($ajax_mode)) $db->query("UPDATE ".$sys_tables['objects_subscriptions']." SET last_delivery = NOW(),last_seen_setinterval = NOW() + INTERVAL 15 MINUTE WHERE id = ?",$parameters['id_subscription']);
                } 
            } 
            //подписка на поиск
            EstateSubscriptions::Init($this_page->real_url);
            list($subscription_title, $description) = EstateSubscriptions::getTitle(false, $parameters, true, false, true);

            $subscription = array(
                'titleSubscribeButton' => $subscription_title ,   
                'email' => $auth->isAuthorized() ? $auth->email : '',
                'deal_type' => $deal_type,
                'estate_type' => $estate_type,
                'subscription_link' => $this_page->real_url,
                'url' => '/objects_subscriptions/subscribe/',
                'text' => 'Подписаться на результаты'
            );
            Response::SetArray( 'subscription', $subscription );
            Response::SetBoolean('showSubscribeButton',EstateSubscriptions::checkSubscribeOpportunity());

            require_once("includes/form.estate.php");
            $where[] = $estate->makeWhereClause($clauses);
            $deal = null;
            if(!empty($deal_type)) $deal = $deal_type=='rent';
            if($estate_type!='build' && empty($clauses['rent'])) $where[]= $sys_tables[$estate_type].'.rent='.(empty($deal_type) || $deal_type == 'sell' ? 2 : 1);
            $page = Request::GetInteger('page', METHOD_GET);
            if ((isset($page))&&($page==0)){
                //чтобы не потерялись фильтры, надо включить их в redirect
                $parameters=Request::GetParameters(METHOD_GET);
                //здесь будем накапливать строку с get-параметрами
                $url=[];
                foreach($parameters as $key=>$item){
                    if ($key!='path'){
                        if ($key!='page') $url[]=$key.'='.$item;
                        else $url[]=$key.'=1';//заменяем page на 1
                    } 
                }
                $url='?'.implode('&',$url);
                Host::Redirect('/'.$this_page->requested_path.'/'.$url);
                exit(0);
            }
            //редирект с комнатностью более 4
            /*
            if( (!empty($parameters['rooms']) && $parameters['rooms'] > 4) || (!empty($parameters['rooms_sale']) && $parameters['rooms_sale'] > 4)){
                if( !empty($parameters['rooms']) && $parameters['rooms'] > 4 ) $parameters['rooms'] = 4;
                if( !empty($parameters['rooms_sale']) && $parameters['rooms_sale'] > 4) $parameters['rooms_sale'] = 4;
                unset($parameters['path']);
                Host::Redirect('/' . $estate_type . '/' . $deal_type . '/?' . http_build_query($parameters));
            }
            */
            if(!$ajax_mode && !empty($parameters['exclude_id'])) {
                unset($parameters['path']);
                unset( $parameters['exclude_id'] );
                unset( $parameters['new_groups'] );
                Host::Redirect('/' . $estate_type . '/' . $deal_type . '/?' . http_build_query($parameters));
            }
            if(empty($page)) $page = 1;
            else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
            if(!empty($reg_where)) $where[] = " (".implode(" OR ", $reg_where).")";
            if(!empty($range_where)) $where[] = " (".implode(" AND ", $range_where).")";
            if(!empty($where)) {
                $subscription_where = $where;
                $where = implode(" AND ",$where);
            }
            else $where = false;      
            $paginator = new Paginator($estate->work_table, $count, $where, false, $group_by);
            //только подсчет кол-ва объектов через ajax
            if( !empty( $parameters['ajax_count']) ){
                $ajax_result['ok'] = true;
                $total_items = !empty($paginator->total_items_count) ? $paginator->total_items_count : $paginator->items_count;
                $ajax_result['count'] = Convert::ToNumber( $total_items ) . ' ' . makeSuffix($total_items, 'объект', array('','а','ов') );
                break;
            }
            //редирект с несуществующих пейджей
            if ($page<0){
                //чтобы не потерялись фильтры, надо включить их в redirect
                $parameters=Request::GetParameters(METHOD_GET);
                //здесь будем накапливать строку с get-параметрами
                $url=[];
                foreach($parameters as $key=>$item){
                    if ($key!='path'){
                        if ($key!='page') $url[]=$key.'='.$item;
                        else $url[]=$key.'=1';//заменяем page на 1
                    } 
                }
                $url='?'.implode('&',$url);
                Host::Redirect('/'.$this_page->requested_path.'/'.$url);
                exit(0);
            }
            if($paginator->pages_count>0 && $paginator->pages_count<$page){
                //чтобы не потерялись фильтры, надо включить их в redirect
                $parameters = Request::GetParameters(METHOD_GET);
                //здесь будем накапливать строку с get-параметрами
                $url=[];
                foreach($parameters as $key=>$item){
                    if ($key!='path'){
                        if ($key!='page') $url[]=$key.'='.$item;
                        else $url[]=$key.'='.$paginator->pages_count;//заменяем page на посл.страницу
                    } 
                }
                $url='?'.implode('&',$url);
                //url не может быть пуст - там будет хотя бы page
                Host::Redirect('/'.$this_page->requested_path.'/'.$url);
                exit(0);
            }
            
            //сортировка
            $sortby = Request::GetInteger('sortby', METHOD_GET);
            
            if(!empty($sortby)) Response::SetBoolean('noindex',true); //meta-тег robots = noindex
            else $sortby = 0;
            $orderby = !empty($parameters['housing_estate_page']) || !empty($parameters['group_id']) ? $sys_tables[$estate_type] . ".`cost` " : $estate->makeSort($sortby, true, $estate_type) ;
            if(!empty($this_page->query_params['page'])) unset($this_page->query_params['page']);
            if(!empty($this_page->query_params['sortby'])) unset($this_page->query_params['sortby']);
            if(!empty($this_page->query_params['count'])) unset($this_page->query_params['count']);
            
            $paginator_link_base = '/'.$this_page->real_path.'/?'.(!empty($this_page->query_params) ? '' . implode('&', $this_page->query_params) . '&' : '');
            if(!empty($parameters['search_form']) && empty($ajax_mode)) unset($parameters['search_form']);
            Response::SetBoolean('search_form', true);
            Response::SetBoolean('only_objects', !empty($parameters['only_objects']));
            Response::SetBoolean('ajax_pagination', true);
            if( !empty($parameters['members_page'] ) ) Response::SetBoolean('members_page', true);
            Response::SetString('sorting_url', $paginator_link_base.'page='.$page.'&sortby=');
            Response::SetInteger('sortby', $sortby);

            //сортировка по застройщику для ajax запроса платного ЖК
            if( !empty($get_parameters['housing_estate']) ){
                   $housing_estates = new HousingEstates();
                   $housing_estate_item = $housing_estates->getItem($get_parameters['housing_estate'], false);
                   if(!empty($housing_estate_item['advanced']) && $housing_estate_item['advanced'] == 1) {
                       Response::SetBoolean('order_by_housing_estate_agency', true);
                       $agency = !empty($housing_estate_item['id_seller']) ? $housing_estate_item['id_seller'] : (!empty($housing_estate_item['id_user']) ? $housing_estate_item['id_user'] : false);
                       if(!empty($housing_estate_item['id_seller'])) Response::SetBoolean('seller', true);
                       if(!empty($agency)) {
                           Response::SetInteger('housing_estate_agency', $agency);
                           Response::SetString('housing_estate_agency_title', !empty($housing_estate_item['id_seller']) ? $housing_estate_item['seller_title'] : (!empty($housing_estate_item['id_user']) ? $housing_estate_item['developer_title'] : false));
                           Response::SetString('housing_estate_chpu_title', !empty($housing_estate_item['id_seller']) ? $housing_estate_item['seller_chpu_title'] : (!empty($housing_estate_item['id_user']) ? $housing_estate_item['developer_chpu_title'] : false));
                           $orderby = $estate->work_table.".id_user = ".$agency." DESC, ".$orderby;
                       }
                   }
            }            
            //читаем список сортировок:
            $sort_list = $estate->getSortList($estate_type);
            Response::SetArray('sort_list',$sort_list);
            if(!empty($sortby)){
                Response::SetString('sortby_title',$sort_list[$sortby]['sort_title']);
                Response::SetString('sortby_num',$sortby);
            }else{
                Response::SetString('sortby_title',"");
                Response::SetString('sortby_num',0);
            }
            
            //формирование url для пагинатора
            $paginator->link_prefix = $paginator_link_base.(!empty($sortby)?'sortby='.$sortby.'&':'').'page=';
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }
            //поиск по карте
            $map_mode = !empty($map_mode) || (!empty($ajax_mode) && (!empty($parameters['top_left_lat']) || !empty($parameters['bottom_right_lng'])) && strstr(Host::getRefererURL(), '/map/')!='');

            if(empty($map_mode)){
                //поиск премиум объектов 
                $premium_list = [];
                $premium_count = Config::$values['view_settings']['premium_estate_offers'];
                $premium_list = $estate->Search( $where . ( !empty( $where ) ? " AND " : "" ) . $estate->work_table.".status=4 ", $premium_count,$premium_count*($page-1), $orderby);
                //уменьшение кол-ва обычных объектов на кол-во Премиум
                if(!empty($premium_list)) {
                    $count = $count - count($premium_list);
                    shuffle($premium_list);
                }
                
                $list = $estate->Search( $where . ( !empty( $where ) ? " AND " : "" ) . $estate->work_table.".status!=4 " ,$count,$count*($page-1), $orderby, $group_by);
                $list = array_merge((array)$premium_list, (array)$list);
                $list = Favorites::ToList($list,$object_type);
            }

            if(empty($map_mode)){
                if(!empty($list)) {
                    // увеличение счетчика поисков
                    foreach($list as $k=>$obj){
                        if(!empty($obj['discount']) && !empty($obj['discount_type'])) {
                             $list[$k]['new_cost'] = $obj['discount_type'] == 1 ? $obj['cost'] - $obj['discount'] : $obj['cost'] * ( (100 - $obj['discount']) / 100 );    
                        } else $list[$k]['new_cost'] = 0;
                        $update_ids[] = $obj['id'];
                        //общая статистика просмотров для ЛК
                        if( !empty($parameters['members_page'] ) ) {
                            $stats = $db->fetch(" SELECT SUM(amount) as sum FROM " . $sys_tables[ $estate_type . '_stats_show_full'] . " WHERE id_parent = ?", $obj['id'] )['sum'];
                            $list[$k]['views_count'] = $obj['views_count'] + $stats;
                        }
                    }
                    if(!empty($update_ids)) $res = $db->query("UPDATE ".$estate->work_table." SET search_count=search_count+1 WHERE id IN (".implode(',',$update_ids).")");

                    Response::SetArray('list', $list);
                    Response::SetBoolean('group_by', $group_by);
                    Response::SetInteger('total_items_count', $paginator->total_items_count);
                    Response::SetInteger('items_count', $paginator->items_count);                                       
                    
                } else {
                    if( ( empty($page) || $page == 1 ) && empty($parameters['search_form']) && empty($ajax_mode) ){
                        //поиск по "раздробленному" поиску
                        unset($parameters['path']);
                        list($new_list) = $estate->searchExploded($parameters, $estate_type);
                        if(!empty($new_list)){
                            EstateSubscriptions::Init($this_page->requested_url, $h1);
                            $search_list = [];
                            //получение новых условий для каждого запроса
                            $count_items = $count_similar_objects = 0;
                            foreach($new_list as $k => $new_parameters){
                                $estate_search = new EstateSearch();
                                list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams($new_parameters, true);
                                // "прямые" условия  
                                $where = [];
                                $where[] = $estate->makeWhereClause($clauses);
                                if(!empty($reg_where)) $where[] = " (".implode(" OR ", $reg_where).")";
                                if(!empty($range_where)) $where[] = " (".implode(" AND ", $range_where).")";
                                $where = implode(" AND ",$where);
                                $paginator_similar = new Paginator($estate->work_table, 1, $where); 
                                if(!empty($paginator_similar->items_count)){
                                    if($count_items++ > 4) break;
                                    $count_similar_objects += $paginator_similar->items_count;
                                    //получение сниппетов
                                    $list = $estate->Search($where, 2, 0, $orderby);
                                    $search_list[] = array(
                                                        'title' => EstateSubscriptions::getTitle($new_parameters, false, true),
                                                        'params_count' => count($new_parameters),
                                                        'count' => $paginator_similar->items_count,
                                                        'link' =>  '/'.$this_page->requested_path.'/?'.(empty($new_parameters)?"":Convert::ArrayToStringGet($new_parameters)),
                                                        'list' => $list
                                    );
                                }
                            }
                            $from_archive_page = Session::GetBoolean('from_archive_page');
                            if(!empty($from_archive_page)){
                                Response::SetBoolean('from_archive_page', $from_archive_page);
                                Session::Destroy('from_archive_page');
                            }
                            Response::SetArray('search_list', $search_list);
                        }
                    }                    
                }
            }

            if(!empty($types) && sizeof($types)==1) {
                // если можно выбрать тип для формы поиска - выбираем
                $current_type = array_shift($types);
                Response::SetArray('form_data', array('obj_type'=>$current_type['id']));
            }
            Response::SetString('requested_url', Host::$protocol . '://' . Host::$host . $paginator_link_base );
            if(!empty($ajax_mode))  $ajax_result['ok'] = true;
            
            if(!empty($parameters['group_id']) && !empty($ajax_mode) && empty($parameters['page'])) {
                if( empty( $parameters['map_group_id'] ) ) $module_template = 'list.block.groups.html'; //новый вид группированных объектов
                else {
                    //список объектов для карты
                    //для 1 объекта выдаем сниппет
                    if(count($list) == 1){
                        Response::SetArray('item', $list[0]);
                        $module_template = 'list.block.item.html';
                    }
                    // для несколько - список
                    else $module_template = 'list.block.map.html'; 
                }
            }
            else if(!empty($parameters['search_form']) && empty($parameters['housing_estate'])) {
                $module_template = 'list.'.$estate_type.'.html';
                Response::SetBoolean('notarget', true);
                Response::SetBoolean('showSubscribeButton', false);
            } elseif(!empty($ajax_mode)) $module_template = 'list.block.html';
            else $module_template = 'list.html';
            //определение рекламной ссылки from=advp
            if(empty($this_page->page_parameters[0])){
                $url_params = parse_url($this_page->real_url);
                if(!empty($url_params['query']) && strstr($url_params['query'],'from=advp')!='')  Response::SetBoolean('parameter_from',true);
            }
            Response::SetBoolean('social_buttons', true);
        } else Host::RedirectLevelUp();

        if($map_mode) { //ПОиск по карте      
            $index = $total_objects = 0;
            $map_list = $estate->SearchMap($where, 1, 0);
            
            $indexes = [];
            //отправляем в карту только название, текст и координаты
            $index = 0;
            //очищаем параметры от координат
            unset($this_page->query_params['top_left_lat']);
            unset($this_page->query_params['top_left_lng']);
            unset($this_page->query_params['bottom_right_lat']);
            unset($this_page->query_params['bottom_right_lng']);
            foreach($map_list as $key=>$item){
                if(!empty($item['lat']) && !empty($item['lng']) && $item['lng']>0 && $item['lat']>0){
                    if(empty($points[$index])) $c = 0;
                    else $c = count($points[$index]);
                    $points[$index]['lat'] =  $item['lat'];
                    $points[$index]['lng'] =  $item['lng'];
                } else {
                    $coords = file_get_contents('http://geocode-maps.yandex.ru/1.x/?format=json&geocode='.$item['full_address']);
                    $json = json_decode($coords,true); 
                    $data = explode(" ",$json['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']);                      
                    if(!empty($data[0]) && !empty($data[1])) {
                        $db->query("UPDATE ".$sys_tables[$estate_type]." SET lat=?, lng=? WHERE id=?",$data[1],$data[0],$item['id']);
                        $index = $data[1].' '.$data[0];
                        if(empty($points[$index])) $c = 0;
                        else $c = count($points[$index]);
                        $points[$index]['lat'] =  $data[1];
                        $points[$index]['lng'] =  $data[0];

                    }
                }
                $points[$index]['addr'] = $item['txt_addr'];
                $points[$index]['title'] = $item['total_objects'] . makeSuffix($item['total_objects'], ' объект', array('','а','ов')) .' - ' . $item['txt_addr'];
                $points[$index]['total_objects'] =  $item['total_objects'];
                $points[$index]['id'] =  $item['id'];
                $points[$index]['group_id'] =  $item['group_id'];
                if(!empty($item['housing_estate_title'])) $points[$index]['addr'] .=  ' - <a href="/zhiloy_kompleks/'.$item['housing_estate_chpu_title'].'/" target="_blank">ЖК «'.$item['housing_estate_title'].'»</a>';
                $paginator_link_base = '/'.$this_page->real_path.'/?'.(!empty($this_page->query_params) ? '' . implode('&', $this_page->query_params) . '&' : '');
                $points[$index]['link'] = $paginator_link_base .'group_id=' . $item['group_id'];
                $total_objects += $item['total_objects'];
                ++$index;
            }
            Response::SetBoolean('map_mode', true);
            //группировка маркеров
            $ajax_result['total'] = $total_objects;
            if(!empty($parameters['id_housing_estate'])) $ajax_result['bounced'] = true;
            $ajax_result['ok'] = true;
            $ajax_result['points'] = [];
            if(!empty($points)) $ajax_result['points'] = $points;
        }
        //сео параметры
        if( ( ( !empty($ajax_mode) && $this_page->requested_path != $this_page->page_pretty_url ) || ( empty($ajax_mode) && $this_page->requested_url == $this_page->requested_path ) ) ) {
            $h1 = !empty($this_page->page_seo_h1) ? $this_page->page_seo_h1 : $subscription_title;
            $page_title = !empty($this_page->page_seo_title) ? $this_page->page_seo_title : $h1;
            $pretty_url = $this_page->page_pretty_url;
        } else {
            $page_title = $h1 = $subscription_title;
        }
        Response::SetString('h1', $h1);
        //добавление кол-ва объявлений в title
        $items_count = '';
        if(!empty($paginator->total_items_count)) $items_count = $paginator->total_items_count; 
        elseif(!empty($paginator->items_count)) $items_count = $paginator->items_count; 
        else if(!empty($count_similar_objects)) $items_count = $count_similar_objects;
        $page_title = $page_title . ( !empty($items_count) ? ' - ' . Convert::ToNumber($items_count) . makeSuffix($items_count, ' объявлени', array('е', 'я', 'й')) : '' );
        if(!empty($ajax_mode)) {
            $ajax_result['h1'] = $h1;
            $ajax_result['seo_text'] = '';  
            if(!empty($pretty_url)) $ajax_result['pretty_url'] = $pretty_url;
            $ajax_result['title'] = $page_title;
        } else {
            $this_page->manageMetadata( 
                array(
                    'title'=>$page_title, 
                    'description' => !empty( $this_page->page_seo_description ) && strlen( $this_page->page_seo_description ) > 20 ? $this_page->page_seo_description : $description . ' ☆ Уникальные предложения, которых не найти на других сайтах. ☆ Мы постоянно отслеживаем актуальность и достоверность объявлений'
                ) 
                , true
            );
        }
                
        break;
    
    //////////////////////////////////////////////////////////////////////////
    // блоки (последних и похожих)
    /////////////////////////////////////////////////////////////////////////
    case $action=='block':
    case $action=='similar':    
        $GLOBALS['js_set'][] = "/modules/estate/list_options.js";
        // работают только по внутреннему вызову или по аякс-запросу
        if(!$this_page->first_instance || $ajax_mode) {
            Response::SetBoolean('only_objects', true);
            $estate_type = "";
            if(!empty($this_page->page_parameters[1]) && in_array($this_page->page_parameters[1], $estate_types)) {
                $estate_type = $this_page->page_parameters[1];       
            }
            switch($estate_type){
                case 'live':
                    $estate = new EstateListLive();
                    break;
                case 'build':
                    $estate = new EstateListBuild();
                    break;
                case 'commercial':
                    $estate = new EstateListCommercial();
                    break;
                case 'country':
                    $estate = new EstateListCountry();
                    break;
                case 'inter':
                    $estate = new EstateListInter();
                    break;
                default:
                    $estate = null;
                    Host::RedirectLevelUp();
                    break;
            }  
            
            if(!empty($estate)){
                //блок похожих предложений
                if(empty($this_page->page_parameters[2]) || !Validate::isDigit($this_page->page_parameters[2])){
                    Host::RedirectLevelUp();
                    break;
                }
                $id = Convert::ToInteger($this_page->page_parameters[2]);
                $module_template = 'list.divide.queries.html';
                //показать только объекты
                Response::SetBoolean('only_objects', true);
                $count = Config::$values['view_settings']['strings_per_page'];
                if(!empty($this_page->page_parameters[3]) && Validate::isDigit($this_page->page_parameters[3])) $count = Convert::ToInteger($this_page->page_parameters[3]);
                //инициируем класс Item для определения похожих id
                switch($estate_type){
                    case 'live':
                        $estateItem = new EstateItemLive($id);
                        break;
                    case 'build':
                        $estateItem = new EstateItemBuild($id);
                        break;
                    case 'commercial':
                        $estateItem = new EstateItemCommercial($id);
                        break;
                    case 'country':
                        $estateItem = new EstateItemCountry($id);
                        break;
                    case 'inter':
                        $estateItem = new EstateItemInter($id);
                        break;
                }

               $properties = array('rent','id_type_object','id_region','id_area','id_city','id_place','id_street','id_district','id_district_area','id_subway','rooms_sale','rooms_total','cost');
               foreach($properties as $k) $properties[$k] = $estateItem->getField($k);
               if(!empty($properties['rent']))            $parameters['rent'] = $properties['rent'];
               if(!empty($properties['id_type_object']))  {
                   if($estate_type == 'build') $parameters['obj_type'] = 1;
                   else {
                        $obj_type = $db->fetch( "SELECT * FROM ".$sys_tables['type_objects_' . $estate_type]." WHERE id = ?", $properties['id_type_object'] );
                        $parameters['obj_type'] = $obj_type['id_group'];
                   }
                   
               }
               if(!empty($properties['id_street'])) {
                   $geo = $db->fetch(
                        "SELECT * FROM ".$sys_tables['geodata']." WHERE id_region = ? AND id_area = ? AND id_place = ? AND id_street = ? AND a_level = 5", 
                        $properties['id_region'], $properties['id_area'], $properties['id_place'], $properties['id_street']
                   );
                   $parameters['geodata'] = $geo['id'];
               }
               if(!empty($properties['id_district']))     $parameters['districts'] = $properties['id_district'];
               if(!empty($properties['id_district_area']))$parameters['districts_area'] = $properties['id_district_area'];
               if(!empty($properties['id_subway']))       $parameters['subways'] = $properties['id_subway'];
               if(!empty($properties['rooms_total']))     $parameters['rooms_total'] = $properties['rooms_total'];
               //if(!empty($properties['rooms_sale']) && !empty($properties['id_type_object'])) $parameters['rooms' . ( $estate_type == 'build' ? '' : '_sale')] = $properties['rooms_sale'];
               if(!empty($properties['square_full']))     $parameters['square_full'] = $properties['square_full'];
               $deal_type = $properties['rent'] == 2 ? 'sell' : 'rent';
               Response::SetString('deal_type', $deal_type);
               list($new_list) = $estate->searchExploded($parameters, $estate_type);
               if(!empty($new_list)){
                    EstateSubscriptions::Init($this_page->requested_url, false, $deal_type);
                    $search_list = [];
                    //получение новых условий для каждого запроса
                    $count_items = $count_similar_objects = 0;
                    foreach($new_list as $k => $new_parameters){
                        $estate_search = new EstateSearch();
                        $new_parameters['cost'] = $properties['cost'];
                        // "прямые" условия  
                        $where = array($estate->work_table.'.id!='.$id);
                        list($parameters, $clauses, $get_parameters, $reg_where, $range_where) = $estate_search->formParams($new_parameters, true);
                        $where[] = $estate->makeWhereClause($clauses);
                        if(!empty($reg_where)) $where[] = " (".implode(" OR ", $reg_where).")";
                        if(!empty($range_where)) $where[] = " (".implode(" AND ", $range_where).")";
                        $where = implode(" AND ",$where);
                        $paginator_similar = new Paginator($estate->work_table, 1, $where); 
                        if( !empty( $paginator_similar->items_count ) ) {
                            if($count_items++ > 2) break;
                            $count_similar_objects += $paginator_similar->items_count;
                            //получение сниппетов
                            $list = $estate->Search($where, 3, 0, $estate->work_table.'.cost');
                            unset($new_parameters['cost']);
                            unset($new_parameters['rent']);
                            $ids = [];
                            foreach($list as $k=>$litem) array_push($ids, $litem['id']);
                            $increase_count = !empty($id) && in_array($id, $ids) ? 0 : 1;
                            $search_list[] = array(
                                                'title' => EstateSubscriptions::getTitle($new_parameters, false, true),
                                                'params_count' => count($new_parameters),
                                                'count' => $paginator_similar->items_count + $increase_count,
                                                'link' =>  '/'.$estate_type.'/' . $deal_type . '/?'.(empty($new_parameters)?"":Convert::ArrayToStringGet($new_parameters)),
                                                'list' => $list
                            );
                           
                        }
                    }
                    Response::SetArray('search_list', $search_list);
               }
               if($ajax_mode) $ajax_result['ok']=true;
            }
        } else Host::RedirectLevelUp();
        break;
    //////////////////////////////////////////////////////////////////////////
    // блок (VIP)
    /////////////////////////////////////////////////////////////////////////
    case $action=='vip' || $action =="payed":
        // работают только по внутреннему вызову или по аякс-запросу
        if(!$this_page->first_instance || $ajax_mode) {
            $module_template = 'block.'.$action.( !empty($this_page->page_parameters[2]) && Convert::ToInt($this_page->page_parameters[2]) == 0 ? '.' . $this_page->page_parameters[2] : '').'.html';
            if($action == 'vip'){
                $block_type = Convert::ToString($this_page->page_parameters[1]);
                if(empty($block_type) || ($this_page->real_url == '' && $block_type == 'left')) break;
                $count = $block_type == 'left' ? 1 : (!empty($this_page->page_parameters[2]) ? $this_page->page_parameters[2] : 4);
                Response::SetInteger('count', $count);
                // проверка наличия в кэше
                $full_list = false;
                $memcache->get('bsn::estate::'.$action.'-block::'.$block_type);
                Response::SetString('type', $block_type);
            } 
            else{
                //в случае агента, берем только его
                if(!empty($this_page->page_parameters[2]) && $this_page->page_parameters[2] == 'agent') $users_ids = $this_page->page_parameters[1];
                //в случае агентства подтягиваем id пользователей
                else{
                    $id_agency = $this_page->page_parameters[1];
                    $users_ids = $db->fetch("SELECT GROUP_CONCAT(id) AS ids FROM ".$sys_tables['users']." WHERE id_agency = ".$id_agency)['ids'];
                }
                $count = 3;
                $full_list = false;
            } 
            
            if(empty($full_list)) {
                $full_list = [];
                
                $estate_types = array('live','build', 'commercial', 'country');
                foreach($estate_types as $estate_type){
                    //формируем доп. условие в зависимости от того, что нужно
                    if($action == 'payed') 
                        $where =(empty($users_ids)?"":$sys_tables[$estate_type].".id_user IN (".$users_ids.") AND ")." "
                                .$sys_tables[$estate_type].".status IN (3,4,6) AND
                               ".$sys_tables[$estate_type].".published = 1";
                    else $where = $sys_tables[$estate_type].".status = 6 AND ".$sys_tables[$estate_type].".published = 1";
                    switch($estate_type){
                        case 'live': $list = new EstateListLive(TYPE_ESTATE_LIVE);   break;
                        case 'build': $list = new EstateListBuild(TYPE_ESTATE_BUILD);   break;
                        case 'commercial': $list = new EstateListCommercial(TYPE_ESTATE_COMMERCIAL);   break;
                        case 'country': $list = new EstateListCountry(TYPE_ESTATE_COUNTRY);   break;
                    }
                    $list = $list->Search($where, 30);
                    if(!empty($list)) foreach($list as $k=>$item) {
                        $list[$k]['type'] = $estate_type;
                        switch($list[$k]['status']){
                            case 3: 
                                $list[$k]['highlighting'] = "promo";
                                break;
                            case 4:
                                $list[$k]['highlighting'] = "premium";
                                break;
                            case 6:
                                $list[$k]['highlighting'] = "vip";
                                break;
                        }
                        $full_list[] = $list[$k];
                    }
                }      
                if($action == 'vip'){
                    $memcache->set('bsn::estate::vip-block'.$block_type, $full_list, FALSE, Config::$values['blocks_cache_time']['vip_objects']);
                    Response::SetString('block_type', $block_type);
                } 
            }
            if(empty($full_list)) break;
            $ajax_result['ok'] = true;
            shuffle($full_list);
            if( !empty( $this_page->page_parameters[1] ) && $this_page->page_parameters[1] == 'main' && $action == 'vip' ){
                
            }
            else if($action == 'vip' && count( $full_list ) == 1) $count = 1;
            else if(count($full_list) < $count) $count = max(1,((int) (count($full_list)/2))*2);
            $list = array_splice($full_list,0,$count);
            Response::SetArray($action.'_list', $list);
        }
        break;  
    
    default:
        //проверка на старые ЧПУшки
        if(!empty($this_page->page_parameters[0]) && in_array($this_page->page_parameters[0],array('live','country','commercial','build'))
            && !empty($this_page->page_parameters[0]) && !in_array($this_page->page_parameters[0],array('rent','sell'))
            && !empty($this_page->page_parameters[1]) && in_array($this_page->page_parameters[1],array('rent','sell'))){
                $estate_type = $this_page->page_parameters[0];
                $old_alias =  $this_page->page_parameters[0];
                $deal =  $this_page->page_parameters[1];
                //редирект новостроек
                if($old_alias=='kvartira' && $deal=='sell' && $estate_type=='build') Host::Redirect('/build/sell/'.(!empty($this_page->page_parameters[2])?$this_page->page_parameters[2].'/':'').(!empty($this_page->page_parameters[3])?$this_page->page_parameters[3].'/':'').(!empty($this_page->page_parameters[4])?$this_page->page_parameters[4].'/':''));
                $alias = $db->fetch("SELECT * FROM ".$sys_tables['type_objects_'.$estate_type]." WHERE old_alias = ?",$old_alias)       ;
                if(!empty($alias)) Host::Redirect('/'.$estate_type.'/'.$deal.'/'.$alias['new_alias'].'/'.(!empty($this_page->page_parameters[2])?$this_page->page_parameters[2].'/':'').(!empty($this_page->page_parameters[3])?$this_page->page_parameters[3].'/':'').(!empty($this_page->page_parameters[4])?$this_page->page_parameters[4].'/':''));
        }
        //редирект для элитки на новый УРЛ для спецпредложений
        $id = $packet = false;
        Host::RedirectLevelUp();
}       
//хлебные крошки
$this_page->clearBreadcrumbs();
$estate_types_breadcrumbs = array(
                                    'live' . (!empty($deal_type) ? '/' . $deal_type : '') => $estate_type!='live' ? 'Жилая недвижимость' : '',
                                    'build' . (!empty($deal_type) ? '/' . $deal_type : '') => $estate_type!='build' ? 'Новостройки' : '',
                                    'commercial' . (!empty($deal_type) ? '/' . $deal_type : '') => $estate_type!='commercial' ? 'Коммерческая недвижимость' : '',
                                    'country' . (!empty($deal_type) ? '/' . $deal_type : '') => $estate_type!='country' ? 'Загородная недвижимость' : '',
                                    'zhiloy_kompleks' => 'Жилые комплексы',
                                    'business_centers' => 'Бизнес-центры',
                                    'cottedzhnye_poselki' => 'Коттеджные поселки'
);
$this_page->addBreadcrumbs( $this_page->page_title == 'Жилая недвижимость' ? 'Жилая' : $this_page->page_title, $estate_type, 0, $estate_types_breadcrumbs);
$deal_types_breadcrumbs = array(
                                    (!empty($estate_type) ? $estate_type . '/': '') . 'rent' => $deal_type!='rent' ? 'Аренда' : '',
                                    (!empty($estate_type) ? $estate_type . '/': '') . 'sell' => $deal_type!='sell' ? 'Продажа' : ''
);
$this_page->addBreadcrumbs($deal_type == 'rent' ? 'Аренда' : 'Продажа', $deal_type, 1, $estate_type != 'build' ? $deal_types_breadcrumbs : []);

Response::SetString('estate_type', $estate_type);
Response::SetString('deal_type', $deal_type);
//ссылки паджинатора - <a>
Response::SetBoolean('direct_link_paginator',true);
if(!empty($parameters)){
    if(!empty($parameters['only_objects'])) Response::SetBoolean('only_objects', true);
}
Response::SetBoolean('show_overlay', true);
?>