<?php
/**    
* Класс работы со спецредложениями
*/

class contextCampaigns {
    private static $tables = [];
    //то, по чему ищем (кроме типа сделки, типа недвижимости и типа объекта)
    private static $search_exploders = array('districts','subways','district_areas','max_cost','min_cost','roooms');
    //типы недвижимости: нумерация совпадает с соответствующей нумерацией в базе
    private static $estate_types = array('live'=>1,'build'=>2,'commercial'=>3,'country'=>4);
    
    /* Статистика Спепредложений */
    public static function Init(){
        self::$tables = array(
            'context_tags'            => Config::$values['sys_tables']['context_tags'],
            'context_advertisements'       => Config::$values['sys_tables']['context_advertisements'],
            'context_tags_conformity' => Config::$values['sys_tables']['context_tags_conformity'],
            'show'         => array (
                                'objects' => Config::$values['sys_tables']['spec_objects_stats_show_day'],
                                'packets' => Config::$values['sys_tables']['spec_packets_stats_show_day'],
                              ),
            'click'        => array (
                                'objects' => Config::$values['sys_tables']['spec_objects_stats_click_day'],
                                'packets' => Config::$values['sys_tables']['spec_packets_stats_click_day'],
                              ) 
            );
    }
    
    public static function getItem($id){
        if(empty($id)) return false;
        global $db;
        $data = $db->fetch("SELECT ".Config::$values['sys_tables']['context_advertisements'].".*,
                                   ".Config::$values['sys_tables']['context_places'].".alias AS place_name,
                                   ".Config::$values['sys_tables']['context_places'].".textblock_capacity AS text_allowed,
                                   GROUP_CONCAT(".Config::$values['sys_tables']['context_advertisements_photos'].".id) AS photos_ids
                            FROM ".Config::$values['sys_tables']['context_advertisements']."
                            LEFT JOIN ".Config::$values['sys_tables']['context_advertisements_photos']." ON ".Config::$values['sys_tables']['context_advertisements'].".id = ".Config::$values['sys_tables']['context_advertisements_photos'].".id_parent
                            LEFT JOIN ".Config::$values['sys_tables']['context_places']." ON ".Config::$values['sys_tables']['context_places'].".id = ".Config::$values['sys_tables']['context_advertisements'].".id_place
                            WHERE ".Config::$values['sys_tables']['context_advertisements'].".id = ".$id);
        //выбираем одну из картинок кампании для показа
        $photos = explode(',',$data['photos_ids']);
        $photo_id = $photos[mt_rand(0,count($photos)-1)];
        //читаем данные по выбранной картинке и пишем в $data
        if(!empty($photo_id)){
            $photo_info = $db->fetch("SELECT SUBSTRING(".Config::$values['sys_tables']['context_advertisements_photos'].".name,1,2) AS folder,
                                                   ".Config::$values['sys_tables']['context_advertisements_photos'].".name
                                  FROM ".Config::$values['sys_tables']['context_advertisements_photos']."
                                  WHERE ".Config::$values['sys_tables']['context_advertisements_photos'].".id = ".$photo_id);
            $data['folder'] = $photo_info['folder'];
            $data['name'] = $photo_info['name'];
            return $data;
        }else return false;
    }
    
    public static function getItems($ids){
        if(empty($ids)) return false;
        global $db;
        if(is_array($ids)) $ids = implode($ids);
        $data = $db->fetchall("SELECT ".Config::$values['sys_tables']['context_advertisements'].".*,
                                   ".Config::$values['sys_tables']['context_places'].".alias AS place_name,
                                   ".Config::$values['sys_tables']['context_places'].".textblock_capacity AS text_allowed,
                                   GROUP_CONCAT(".Config::$values['sys_tables']['context_advertisements_photos'].".id) AS photos_ids
                               FROM ".Config::$values['sys_tables']['context_advertisements']."
                               LEFT JOIN ".Config::$values['sys_tables']['context_advertisements_photos']." ON ".Config::$values['sys_tables']['context_advertisements'].".id = ".Config::$values['sys_tables']['context_advertisements_photos'].".id_parent
                               LEFT JOIN ".Config::$values['sys_tables']['context_places']." ON ".Config::$values['sys_tables']['context_places'].".id = ".Config::$values['sys_tables']['context_advertisements'].".id_place
                               WHERE ".Config::$values['sys_tables']['context_advertisements'].".id IN (".$ids.")
                               GROUP BY ".Config::$values['sys_tables']['context_advertisements'].".id
                               ORDER BY RAND()");
        if(empty($data)) return false;
        foreach($data as $key=>$item){
            //выбираем одну из картинок кампании для показа, читаем данные по выбранной картинке и пишем в $data
            if(!empty($item['photos_ids'])){
                $photos = explode(',',$item['photos_ids']);
                $photo_id = $photos[mt_rand(0,count($photos)-1)];
                $photo_info = $db->fetch("SELECT SUBSTRING(".Config::$values['sys_tables']['context_advertisements_photos'].".name,1,2) AS folder,
                                                       ".Config::$values['sys_tables']['context_advertisements_photos'].".name
                                      FROM ".Config::$values['sys_tables']['context_advertisements_photos']."
                                      WHERE ".Config::$values['sys_tables']['context_advertisements_photos'].".id = ".$photo_id);
                if(!empty($photo_id)){
                    $data[$key]['folder'] = $photo_info['folder'];
                    $data[$key]['name'] = $photo_info['name'];
                }
            }
        }
        if($data[0]['block_type'] == 1){
            $data = $data[0];
            $data['txt_blocks'] = false;
            if(empty($photo_id)) return false;
        }
        else $data['txt_blocks'] = true;
        return $data;
    }
    
    /**
    * Объект-выборка из базы рекламных кампаний для ajax контейнеров 
    * @param string $place - местоположение блока (id поля)
    * @param array $search_parameters - строка, полученная с помощью Request GET
    * @param array $item_parameters - массив параметров объекта (в случае карточки)
    * @return array (результат выборки из базы)
    */
    public static function findItem($place,$search_parameters,$item_parameters = false){
        global $db;
        //массив для условий поиска кампаний
        $conditions = [];
        //тип страницы(поиск/карточка)
        $is_item_page = true;
        //определяем место размещения (если не определилось,сразу выходим):
        $place_info = $db->fetch("SELECT id,textblock_capacity 
                                  FROM ".Config::$values['sys_tables']['context_places']."
                                  WHERE alias = '".$place."'");
        $id_place = $place_info['id'];
        if(empty($id_place)) return false;
        $conditions[] = Config::$values['sys_tables']['context_advertisements'].".id_place = ".$id_place;
        //читаем теги в зависимости от того, что передано
        if(!empty($search_parameters)){
            //обозначаем что это поиск
            $is_item_page = false;
            //в случае переданной строки с параметрами поиска
            //по переданным условиям ищем соответствующие теги таргетинга
            //из первого куска параметров читаем тип недвижимости и тип сделки
            $nget_param = explode('/',$search_parameters['path']);
            $estate_type = $nget_param[1];
            $conditions[] = Config::$values['sys_tables']['context_advertisements'].".estate_type LIKE  '%".contextCampaigns::$estate_types[$estate_type]."%'";
            $deal_type = ($nget_param[2] == 'rent')?1:2;
            $conditions[] = Config::$values['sys_tables']['context_advertisements'].".deal_type LIKE '%".$deal_type."%'";
            unset($search_parameters['path']);
            //для стройки тип объекта один, поэтому не учитываем его
            if($estate_type == 'build') unset($search_parameters['obj_type']);
            //переименовываем поле для поиска в базе
            if(!empty($search_parameters['obj_type'])){
                $search_parameters['type_objects_'.$estate_type] = $search_parameters['obj_type'];
                unset($search_parameters['obj_type']);
            }
            //записываем ограничения по цене
            $price_floor =  Convert::ToInt((empty($search_parameters['min_cost']))?0:$search_parameters['min_cost']);
            $price_top =  Convert::ToInt((empty($search_parameters['max_cost']))?0:$search_parameters['max_cost']);
            unset($search_parameters['min_cost']);
            unset($search_parameters['max_cost']);
            
            //подсчитываем количество тегов, минимально необходимое для показа (для всех категорий кроме р-в,р-вЛО и метро, должно быть хотя бы по одному тегу из каждой + 1 тег из р-в,р-вЛО, метро)
            //$min_amount = count($search_parameters) - ((!empty($search_parameters['districts']))?1:0) - ((!empty($search_parameters['district_areas']))?1:0) - ((!empty($search_parameters['subways']))?1:0);
            //if($min_amount<count($search_parameters)) ++$min_amount;
            $min_amount = 1;
            
            //перебираем параметры, ищем теги
            if(!empty($search_parameters)){
                foreach($search_parameters as $key=>$value){
                    //пробуем найти тег по значению (например комнатность) или id источника (например метро или район)
                    if(!preg_match('/,/',$value)){
                        $tags_list[$key] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = '".$key."' AND (txt_value = '".$value."' OR source_id = '".$value."' )")['ids'];
                        //если это район или район области и мы ничего не нашли, пробуем найти в geodata
                        if(empty($tags_list[$key]) && ($key == 'districts' || $key =='district_areas')){
                            //список названий тегов
                            $titles_list = $db->fetch("SELECT GROUP_CONCAT(offname) AS offnames FROM ".Config::$values['sys_tables']['geodata']." WHERE id IN (".$value.")")['offnames'];
                            $titles_list = "'".preg_replace('/,/',"','",$titles_list)."'";
                            //по этому списку читаем id соответствующих тегов
                            $tags_list[$key] = $db->fetch("SELECT GROUP_CONCAT(id) FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_value IN (".$titles_list.")")['GROUP_CONCAT(id)'];
                        }
                    }
                    else{
                        $value = "'".str_replace(',','\',\'',$value)."'";
                        $tags_list[$key] = $db->fetch("SELECT GROUP_CONCAT(id) 
                                                       FROM 
                                                       (SELECT id FROM ".Config::$values['sys_tables']['context_tags']." 
                                                       WHERE txt_field = '".$key."' AND (source_id IN (".$value.") OR txt_value IN (".$value."))
                                                       )AS a")['GROUP_CONCAT(id)'];
                        //если это район или район области и мы ничего не нашли, пробуем найти в geodata
                        if(empty($tags_list[$key]) && ($key == 'districts' || $key =='district_areas')){
                            //список названий тегов
                            $titles_list = $db->fetch("SELECT GROUP_CONCAT(offname) AS offnames FROM ".Config::$values['sys_tables']['geodata']." WHERE id IN (".$value.")")['offnames'];
                            $titles_list = "'".preg_replace('/,/',"','",$titles_list)."'";
                            //по этому списку читаем id соответствующих тегов
                            $tags_list[$key] = $db->fetch("SELECT GROUP_CONCAT(id) FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_value IN (".$titles_list.")")['GROUP_CONCAT(id)'];
                        }
                    }
                }
                //формируем общее условие для тегов (не записываем теги районов, районов ЛО и метро, из всех категорий должно быть хотя бы по одному тегу)
                $tags_conditions = [];
                foreach($tags_list as $key=>$tag_ids){
                    if(($key!='districts')&&($key!='district_areas')&&($key!='subways')){
                        if(!empty($tag_ids)) $tags_conditions[] = " id_tag IN (".$tag_ids.")";
                        else unset($tags_list[$key]);
                    }
                }
                
                //список обычных тегов, разделенные запятыми
                $tags_list_united = implode(',',$tags_conditions);
                //записываем, по скольки обычным тегам ищем
                $search_tags_amount = count(explode(',',$tags_list_united));
                
                //запоминаем, по каким категориям местоположения искали
                $area_categories = [];
                //добавляем обязательные условия по тегам(район,район ЛО, метро)
                $tags_required = [];
                
                if(!empty($tags_list['districts'])){
                    $tags_required[] = " id_tag IN (".$tags_list['districts'].")";
                    $area_categories['districts'] = count($tags_list['districts']);
                } 
                if(!empty($tags_list['district_areas'])){
                    $tags_required[] = " id_tag IN (".$tags_list['district_areas'].")";
                    $area_categories['district_areas'] = count($tags_list['district_areas']);
                } 
                if(!empty($tags_list['subways'])){
                    $tags_required[] = " id_tag IN (".$tags_list['subways'].")";
                    $area_categories['subways'] = count($tags_list['subways']);
                }
                if(!empty($tags_required)){
                    //так как кроме обычных тегов должен быть хотя бы один из районов, районов ЛО и метро(если что-либо из этого указано)
                    //++$min_amount;
                    $tags_required = "(".implode(' OR ',$tags_required).")";
                    $tags_conditions[] = $tags_required;
                }
                if(!empty($tags_conditions)){
                    $tags_conditions = implode(" OR ",$tags_conditions);
                    $conditions[] = "(".$tags_conditions.")";
                }
            }
            //границы цены кампании должны влезать в переданный интервал
            if(!empty($price_top)) $conditions[] = Config::$values['sys_tables']['context_advertisements'].".price_top <= ".$price_top;
            if(!empty($price_floor)) $conditions[] = Config::$values['sys_tables']['context_advertisements'].".price_floor >= ".$price_floor;
            //если не выбраны границы цены и теги, то ничего не возвращаем
            //if(empty($price_floor) && empty($price_top) && empty($tags_conditions)) return false;
            //данные про цену, которые будем читать в запросе
            $price_data = ((!empty($price_top))?"(".$price_top." - ".Config::$values['sys_tables']['context_advertisements'].".price_top) AS top_diff, ":" ").
                               ((!empty($price_floor))?"(".$price_floor." - ".Config::$values['sys_tables']['context_advertisements'].".price_floor) AS floor_diff,":" ");
            $price_sorting = ((!empty($price_top))?", top_diff ASC":"").((!empty($price_floor))?", floor_diff ASC":"");
        }
        else{
            //в случае захода с карточки переданы параметры объекта
            $conditions[] = Config::$values['sys_tables']['context_advertisements'].".estate_type LIKE '%".contextCampaigns::$estate_types[$item_parameters['estate_type']]."%'";
            $conditions[] = Config::$values['sys_tables']['context_advertisements'].".deal_type LIKE '%".$item_parameters['deal_type']."%'";
            $object_price = $item_parameters['price'];
            $estate_type = $item_parameters['estate_type'];
            //набираем теги (комнатность, тип объекта, метро, район, район ЛО)
            if(!empty($item_parameters['rooms']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'rooms' AND txt_value = '".$item_parameters['rooms']."'")['ids'];
            if(!empty($item_parameters['type_object']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'type_objects_".$item_parameters['estate_type']."' AND txt_value = '".$item_parameters['type_object']."'")['ids'];
            if(!empty($item_parameters['subway']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'subways' AND txt_value = '".$item_parameters['subway']."'")['ids'];
            if(!empty($item_parameters['district']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'districts' AND txt_value = '".$item_parameters['district']."'")['ids'];
            if(!empty($item_parameters['district_area']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'district_areas' AND txt_value = '".$item_parameters['district_area']."'")['ids'];
            //удаляем пустые
            foreach($tags_list as $key=>$item)
                if(empty($item)) unset($tags_list[$key]);
            //список тегов, разделенные запятыми
            $tags_list_united = implode(',',$tags_list);
            //записываем, по скольки тегам ищем
            $search_tags_amount = count(explode(',',$tags_list_united));
            //формируем условие для тегов (если что-то нашли)
            if(!empty($tags_list)){
                $tags_conditions = (empty($tags_list))?"":"id_tag IN(".$tags_list_united.")";
                $conditions[] = $tags_conditions;
            }
            //данные про цену, которые будем вычислять в запросе
            $price_data = "ABS(".$object_price." - ".Config::$values['sys_tables']['context_advertisements'].".price_top)+ABS(".$object_price." - ".Config::$values['sys_tables']['context_advertisements'].".price_floor) AS total_diff,";
            $price_sorting = ((!empty($object_price))?", total_diff ASC":"");
        }
        
        //если текстовые блоки не впихнуть, добавляем условие
        if(empty($place_info['textblock_capacity'])) $conditions[] = Config::$values['sys_tables']['context_advertisements'].".block_type = 1";
        
        if(!empty($conditions)) $conditions = implode(" AND ",$conditions);
        //читаем список камапний, у которых есть выбранные теги и которые отвечают условиям
        $campaigns_list = $db->fetchall("SELECT DISTINCT ".Config::$values['sys_tables']['context_tags_conformity'].".id_context,
                                                         COUNT(*) AS search_tags_amount,
                                                         GROUP_CONCAT(".Config::$values['sys_tables']['context_tags_conformity'].".id_tag) AS tags_set,
                                                         ".Config::$values['sys_tables']['context_advertisements'].".block_type,
                                                         ".$price_data."
                                                         t_tags.amount AS tags_amount
                                         FROM ".Config::$values['sys_tables']['context_tags_conformity']."
                                         LEFT JOIN ".Config::$values['sys_tables']['context_advertisements']." ON ".Config::$values['sys_tables']['context_tags_conformity'].".id_context = ".Config::$values['sys_tables']['context_advertisements'].".id
                                         LEFT JOIN ".Config::$values['sys_tables']['context_campaigns']." ON ".Config::$values['sys_tables']['context_campaigns'].".id = ".Config::$values['sys_tables']['context_advertisements'].".id_campaign
                                         LEFT JOIN ".Config::$values['sys_tables']['context_advertisements_photos']." ON ".Config::$values['sys_tables']['context_advertisements'].".id_main_photo = ".Config::$values['sys_tables']['context_advertisements_photos'].".id
                                         LEFT JOIN (SELECT ".Config::$values['sys_tables']['context_tags_conformity'].".id_context,COUNT(*) AS amount
                                                    FROM ".Config::$values['sys_tables']['context_tags']." 
                                                    LEFT JOIN ".Config::$values['sys_tables']['context_tags_conformity']."
                                                    ON ".Config::$values['sys_tables']['context_tags_conformity'].".id_tag = ".Config::$values['sys_tables']['context_tags'].".id
                                                    WHERE ".Config::$values['sys_tables']['context_tags'].".estate_type LIKE '%".contextCampaigns::$estate_types[$estate_type]."%'
                                                    GROUP BY id_context)AS t_tags ON t_tags.id_context = ".Config::$values['sys_tables']['context_advertisements'].".id
                                         WHERE ".$conditions." AND ".Config::$values['sys_tables']['context_advertisements'].".published = 1 AND 
                                               ".Config::$values['sys_tables']['context_campaigns'].".date_start<=NOW() AND
                                               ".Config::$values['sys_tables']['context_campaigns'].".date_end>NOW()
                                         GROUP BY ".Config::$values['sys_tables']['context_tags_conformity'].".id_context
                                         ORDER BY search_tags_amount DESC,
                                                   tags_amount ASC".
                                                  $price_sorting);
        //список для наиболее подходящих кампаний (если tags_amount,search_tags_amount одинаковые)
        $most_suitable = [];
        //будем записывать найденные блоки по типам - графика и графика+текст
        $textblock_found = 0;
        $banners_found = 0;
        //если ничего не нашли, возвращаем false
        if(empty($campaigns_list[0])) return false;
        else{
            //если что-то нашли для карточки, выбираем те, чей набор тегов содержит набор карточки
            if(!empty($is_item_page))
                foreach($campaigns_list as $key=>$values){
                    if($values['search_tags_amount'] >= $search_tags_amount){
                        if($campaigns_list[$key]['block_type'] == 2) ++$textblock_found;
                        else ++$banners_found;
                        $most_suitable[] = array('id'=>$campaigns_list[$key]['id_context'],'block_type'=>$campaigns_list[$key]['block_type']);
                    }
                    else break;
                }
            else
            //если что-то нашли для поиска
                foreach($campaigns_list as $key=>$values){
                    $area_categories_local = (empty($area_categories)?[]:$area_categories);
                    //проверяем, что количество совпавших тегов превышает минимально необходимое
                    if($values['search_tags_amount'] >= $min_amount){
                        //набор тегов объявления, совпавший при поиске
                        $cmp_set = explode(',',$values['tags_set']);
                        //флаг, что есть совпадающие теги во всех категориях
                        $all_categories = true;
                        //количество категорий тегов, совпдаающих с поиском
                        $shared_categories = 0;
                        //проверяем, что в каждой из категорий тегов по которым искали есть совпавшие
                        if(!empty($tags_list))
                            foreach($tags_list as $category=>$c_values){
                                //создаем массив тегов поиска в данной категории (например набор задействованных при поиске тегов комнатности)
                                $c_values = explode(',',$c_values);
                                //совпавшие теги в текущей категории
                                $shared_tags = array_intersect($c_values,$cmp_set);
                                if(empty($shared_tags)) $all_categories = false;
                                else ++$shared_categories;
                            }
                        
                        //если есть совпадающие теги во всех категориях по которым искали, или совпало по всем категориям которые есть у объявления
                        if($all_categories || $shared_categories == count($tags_list)){
                            //проверяем, что в каждой из категорий тегов выбранной кампании(кроме тех где выбраны все) есть совпавшие с тегами поиска
                            if(!empty($area_categories)){
                                //считаем сколько у объявления в категории тегов, которая учавствует в выдаче
                                $c_tags_amount = $db->fetchall("SELECT txt_field,COUNT(*)
                                                                FROM ".Config::$values['sys_tables']['context_tags_conformity']." ctc
                                                                LEFT JOIN ".Config::$values['sys_tables']['context_tags']." ct ON ct.id = ctc.id_tag
                                                                LEFT JOIN ".Config::$values['sys_tables']['context_advertisements']." adv ON adv.id=ctc.id_context
                                                                WHERE adv.id=".$values['id_context']." AND txt_field IN('".array_keys($area_categories)[0]."')
                                                                GROUP BY txt_field",'txt_field');
                                //считаем общее количество тегов в метро, районах и районах ЛО
                                $full_amount = $db->fetchall("SELECT txt_field,COUNT(*)
                                                              FROM ".Config::$values['sys_tables']['context_tags']."
                                                              WHERE txt_field IN ('subways','districts','district_areas')
                                                              GROUP BY txt_field ",'txt_field');
                                
                                //перебираем категории тегов объявления, если выбраны все, исключаем из условий совпадения
                                $area_categories_count = (empty($area_categories)?0:count($area_categories));
                                foreach($c_tags_amount as $c_tag_cat=>$amount){
                                    if($full_amount[$c_tag_cat] == $amount){
                                        unset($area_categories_local[$c_tag_cat]);
                                        unset($c_tags_amount[$c_tag_cat]);
                                    }
                                }
                            }
                            //если после удаления полных категорий, у запроса и объявления одинаковое количество категорий, множества категорий поиска и объявления совпадают, объявление подходит
                            if((empty($c_tags_amount) && empty($area_categories)) || count($c_tags_amount) == count($area_categories_local)){
                                if($campaigns_list[$key]['block_type'] == 2) ++$textblock_found;
                                else ++$banners_found;
                                $most_suitable[] = array('id'=>$campaigns_list[$key]['id_context'],'block_type'=>$campaigns_list[$key]['block_type']);
                            } 
                        }
                    }
                }
            if(empty($most_suitable)) return false;
            //если подходящих несколько, выбираем случайное
            if(count($most_suitable) > 1){
                $total = $banners_found + $textblock_found;
                //если нашли только графику - просто возвращаем случайный
                if(!empty($banners_found) && empty($textblock_found)) return $most_suitable[mt_rand(0,count($most_suitable)-1)]['id'];
                //если нашли только текстовые - выбираем столько сколько вмещает блок
                elseif(empty($banners_found) && !empty($textblock_found)){
                    $result = array('txt_blocks'=>true,'ids'=>[]);
                    while(!empty($most_suitable) && count($result['ids'])<$place_info['textblock_capacity']){
                        $returning_key = mt_rand(0,count($most_suitable)-1);
                        if($most_suitable[$returning_key]['block_type'] == 2) $result['ids'][] = $most_suitable[$returning_key]['id'];
                        unset($most_suitable[$returning_key]);
                        $most_suitable = array_values($most_suitable);
                    }
                    $result['txt_blocks'] = true;
                    //shuffle($result['ids']);
                    $result['ids'] = implode(',',$result['ids']);
                    return $result;
                }
                else{
                    if($total == 0) return false;
                    //если есть и те и те, смотрим что показывать в зависимости от соотношения
                    $total = $textblock_found + $banners_found;
                    if(mt_rand(0,100)>floor($banners_found/$total*100)){
                        $result = array('txt_blocks'=>true,'ids'=>[]);
                        //идем пока не опустеет результат или не заполнится рекламное место
                        while(!empty($most_suitable) && count($result['ids'])<$place_info['textblock_capacity']){
                            $returning_key = mt_rand(0,count($most_suitable)-1);
                            //$returning_key = 0;
                            if($most_suitable[$returning_key]['block_type'] == 2) $result['ids'][] = $most_suitable[$returning_key]['id'];
                            unset($most_suitable[$returning_key]);
                            shuffle($most_suitable);
                        }
                        $result['txt_blocks'] = true;
                        $result['ids'] = implode(',',($result['ids']));
                        return $result;
                    }else{
                        //убираем не-картинки
                        foreach($most_suitable as $key=>$item){
                            if($most_suitable[$key]['block_type'] != 1) unset($most_suitable[$key]);
                        }
                        shuffle($most_suitable);
                        $result['txt_blocks'] = false;
                        $result['ids'] = $most_suitable[mt_rand(0,count($most_suitable)-1)]['id'];
                        return $result;
                    } 
                }
            } 
            else
                if($most_suitable[0]['block_type'] == 2) {
                    $result = array('txt_blocks'=>true,'ids'=>[]);
                    $result['txt_blocks'] = true;
                    $result['ids'] = $most_suitable[0]['id'];
                    return $result;
                }
                else return $most_suitable[0]['id'];
        }
    }
    
    /*тестовый метод для проверки*/
    public static function findItem2($place,$search_parameters,$item_parameters = false){
        global $db;
        //массив для условий поиска кампаний
        $conditions = [];
        //тип страницы(поиск/карточка)
        $is_item_page = true;
        //определяем место размещения (если не определилось,сразу выходим):
        $place_info = $db->fetch("SELECT id,textblock_capacity 
                                  FROM ".Config::$values['sys_tables']['context_places']."
                                  WHERE alias = '".$place."'");
        $id_place = $place_info['id'];
        if(empty($id_place)) return false;
        $conditions[] = Config::$values['sys_tables']['context_advertisements'].".id_place = ".$id_place;
        //читаем теги в зависимости от того, что передано
        if(!empty($search_parameters)){
            //обозначаем что это поиск
            $is_item_page = false;
            //в случае переданной строки с параметрами поиска
            //по переданным условиям ищем соответствующие теги таргетинга
            //из первого куска параметров читаем тип недвижимости и тип сделки
            $nget_param = explode('/',$search_parameters['path']);
            $estate_type = $nget_param[1];
            $conditions[] = Config::$values['sys_tables']['context_advertisements'].".estate_type LIKE  '%".contextCampaigns::$estate_types[$estate_type]."%'";
            $deal_type = ($nget_param[2] == 'rent')?1:2;
            $conditions[] = Config::$values['sys_tables']['context_advertisements'].".deal_type LIKE '%".$deal_type."%'";
            unset($search_parameters['path']);
            //для стройки тип объекта один, поэтому не учитываем его
            if($estate_type == 'build') unset($search_parameters['obj_type']);
            //переименовываем поле для поиска в базе
            if(!empty($search_parameters['obj_type'])){
                $search_parameters['type_objects_'.$estate_type] = $search_parameters['obj_type'];
                unset($search_parameters['obj_type']);
            }
            //записываем ограничения по цене
            $price_floor =  (empty($search_parameters['min_cost']))?0:$search_parameters['min_cost'];
            $price_top =  (empty($search_parameters['max_cost']))?0:$search_parameters['max_cost'];
            unset($search_parameters['min_cost']);
            unset($search_parameters['max_cost']);
            
            //подсчитываем количество тегов, минимально необходимое для показа (для всех категорий кроме р-в,р-вЛО и метро, должно быть хотя бы по одному тегу из каждой + 1 тег из р-в,р-вЛО, метро)
            //$min_amount = count($search_parameters) - ((!empty($search_parameters['districts']))?1:0) - ((!empty($search_parameters['district_areas']))?1:0) - ((!empty($search_parameters['subways']))?1:0);
            //if($min_amount<count($search_parameters)) ++$min_amount;
            $min_amount = 1;
            
            //перебираем параметры, ищем теги
            if(!empty($search_parameters)){
                foreach($search_parameters as $key=>$value){
                    //пробуем найти тег по значению (например комнатность) или id источника (например метро или район)
                    if(!preg_match('/,/',$value)){
                        $tags_list[$key] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = '".$key."' AND (txt_value = '".$value."' OR source_id = '".$value."' )")['ids'];
                        //если это район или район области и мы ничего не нашли, пробуем найти в geodata
                        if(empty($tags_list[$key]) && ($key == 'districts' || $key =='district_areas')){
                            //список названий тегов
                            $titles_list = $db->fetch("SELECT GROUP_CONCAT(offname) AS offnames FROM ".Config::$values['sys_tables']['geodata']." WHERE id IN (".$value.")")['offnames'];
                            $titles_list = "'".preg_replace('/,/',"','",$titles_list)."'";
                            //по этому списку читаем id соответствующих тегов
                            $tags_list[$key] = $db->fetch("SELECT GROUP_CONCAT(id) FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_value IN (".$titles_list.")")['GROUP_CONCAT(id)'];
                        }
                    }
                    else{
                        $tags_list[$key] = $db->fetch("SELECT GROUP_CONCAT(id) 
                                                       FROM 
                                                       (SELECT id FROM ".Config::$values['sys_tables']['context_tags']." 
                                                       WHERE txt_field = '".$key."' AND (source_id IN (".$value.") OR txt_value IN (".$value."))
                                                       )AS a")['GROUP_CONCAT(id)'];
                        //если это район или район области и мы ничего не нашли, пробуем найти в geodata
                        if(empty($tags_list[$key]) && ($key == 'districts' || $key =='district_areas')){
                            //список названий тегов
                            $titles_list = $db->fetch("SELECT GROUP_CONCAT(offname) AS offnames FROM ".Config::$values['sys_tables']['geodata']." WHERE id IN (".$value.")")['offnames'];
                            $titles_list = "'".preg_replace('/,/',"','",$titles_list)."'";
                            //по этому списку читаем id соответствующих тегов
                            $tags_list[$key] = $db->fetch("SELECT GROUP_CONCAT(id) FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_value IN (".$titles_list.")")['GROUP_CONCAT(id)'];
                        }
                    }
                }
                //формируем общее условие для тегов (не записываем теги районов, районов ЛО и метро, из всех категорий должно быть хотя бы по одному тегу)
                $tags_conditions = [];
                foreach($tags_list as $key=>$tag_ids){
                    if(($key!='districts')&&($key!='district_areas')&&($key!='subways')){
                        if(!empty($tag_ids)) $tags_conditions[] = " id_tag IN (".$tag_ids.")";
                        else unset($tags_list[$key]);
                    }
                }
                
                //список обычных тегов, разделенные запятыми
                $tags_list_united = implode(',',$tags_conditions);
                //записываем, по скольки обычным тегам ищем
                $search_tags_amount = count(explode(',',$tags_list_united));
                
                //запоминаем, по каким категориям местоположения искали
                $area_categories = [];
                //добавляем обязательные условия по тегам(район,район ЛО, метро)
                $tags_required = [];
                
                if(!empty($tags_list['districts'])){
                    $tags_required[] = " id_tag IN (".$tags_list['districts'].")";
                    $area_categories['districts'] = count($tags_list['districts']);
                } 
                if(!empty($tags_list['district_areas'])){
                    $tags_required[] = " id_tag IN (".$tags_list['district_areas'].")";
                    $area_categories['district_areas'] = count($tags_list['district_areas']);
                } 
                if(!empty($tags_list['subways'])){
                    $tags_required[] = " id_tag IN (".$tags_list['subways'].")";
                    $area_categories['subways'] = count($tags_list['subways']);
                }
                if(!empty($tags_required)){
                    //так как кроме обычных тегов должен быть хотя бы один из районов, районов ЛО и метро(если что-либо из этого указано)
                    //++$min_amount;
                    $tags_required = "(".implode(' OR ',$tags_required).")";
                    $tags_conditions[] = $tags_required;
                }
                if(!empty($tags_conditions)){
                    $tags_conditions = implode(" OR ",$tags_conditions);
                    $conditions[] = "(".$tags_conditions.")";
                }
            }
            //границы цены кампании должны влезать в переданный интервал
            if(!empty($price_top)) $conditions[] = Config::$values['sys_tables']['context_advertisements'].".price_top <= ".$price_top;
            if(!empty($price_floor)) $conditions[] = Config::$values['sys_tables']['context_advertisements'].".price_floor >= ".$price_floor;
            //если не выбраны границы цены и теги, то ничего не возвращаем
            //if(empty($price_floor) && empty($price_top) && empty($tags_conditions)) return false;
            //данные про цену, которые будем читать в запросе
            $price_data = ((!empty($price_top))?"(".$price_top." - ".Config::$values['sys_tables']['context_advertisements'].".price_top) AS top_diff, ":" ").
                               ((!empty($price_floor))?"(".$price_floor." - ".Config::$values['sys_tables']['context_advertisements'].".price_floor) AS floor_diff,":" ");
            $price_sorting = ((!empty($price_top))?", top_diff ASC":"").((!empty($price_floor))?", floor_diff ASC":"");
        }
        else{
            //в случае захода с карточки переданы параметры объекта
            $conditions[] = Config::$values['sys_tables']['context_advertisements'].".estate_type LIKE '%".contextCampaigns::$estate_types[$item_parameters['estate_type']]."%'";
            $conditions[] = Config::$values['sys_tables']['context_advertisements'].".deal_type LIKE '%".$item_parameters['deal_type']."%'";
            $object_price = $item_parameters['price'];
            $estate_type = $item_parameters['estate_type'];
            //набираем теги (комнатность, тип объекта, метро, район, район ЛО)
            if(!empty($item_parameters['rooms']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'rooms' AND txt_value = '".$item_parameters['rooms']."'")['ids'];
            if(!empty($item_parameters['type_object']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'type_objects_".$item_parameters['estate_type']."' AND txt_value = '".$item_parameters['type_object']."'")['ids'];
            if(!empty($item_parameters['subway']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'subways' AND txt_value = '".$item_parameters['subway']."'")['ids'];
            if(!empty($item_parameters['district']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'districts' AND txt_value = '".$item_parameters['district']."'")['ids'];
            if(!empty($item_parameters['district_area']))
                $tags_list[] = $db->fetch("SELECT CONCAT(id) as ids FROM ".Config::$values['sys_tables']['context_tags']." WHERE txt_field = 'district_areas' AND txt_value = '".$item_parameters['district_area']."'")['ids'];
            //удаляем пустые
            foreach($tags_list as $key=>$item)
                if(empty($item)) unset($tags_list[$key]);
            //список тегов, разделенные запятыми
            $tags_list_united = implode(',',$tags_list);
            //записываем, по скольки тегам ищем
            $search_tags_amount = count(explode(',',$tags_list_united));
            //формируем условие для тегов (если что-то нашли)
            if(!empty($tags_list)){
                $tags_conditions = (empty($tags_list))?"":"id_tag IN(".$tags_list_united.")";
                $conditions[] = $tags_conditions;
            }
            //данные про цену, которые будем вычислять в запросе
            $price_data = "ABS(".$object_price." - ".Config::$values['sys_tables']['context_advertisements'].".price_top)+ABS(".$object_price." - ".Config::$values['sys_tables']['context_advertisements'].".price_floor) AS total_diff,";
            $price_sorting = ((!empty($object_price))?", total_diff ASC":"");
        }
        
        //если текстовые блоки не впихнуть, добавляем условие
        if(empty($place_info['textblock_capacity'])) $conditions[] = Config::$values['sys_tables']['context_advertisements'].".block_type = 1";
        
        if(!empty($conditions)) $conditions = implode(" AND ",$conditions);
        //читаем список камапний, у которых есть выбранные теги и которые отвечают условиям
        $campaigns_list = $db->fetchall("SELECT DISTINCT ".Config::$values['sys_tables']['context_tags_conformity'].".id_context,
                                                         COUNT(*) AS search_tags_amount,
                                                         GROUP_CONCAT(".Config::$values['sys_tables']['context_tags_conformity'].".id_tag) AS tags_set,
                                                         ".Config::$values['sys_tables']['context_advertisements'].".block_type,
                                                         ".$price_data."
                                                         t_tags.amount AS tags_amount
                                         FROM ".Config::$values['sys_tables']['context_tags_conformity']."
                                         LEFT JOIN ".Config::$values['sys_tables']['context_advertisements']." ON ".Config::$values['sys_tables']['context_tags_conformity'].".id_context = ".Config::$values['sys_tables']['context_advertisements'].".id
                                         LEFT JOIN ".Config::$values['sys_tables']['context_campaigns']." ON ".Config::$values['sys_tables']['context_campaigns'].".id = ".Config::$values['sys_tables']['context_advertisements'].".id_campaign
                                         LEFT JOIN ".Config::$values['sys_tables']['context_advertisements_photos']." ON ".Config::$values['sys_tables']['context_advertisements'].".id_main_photo = ".Config::$values['sys_tables']['context_advertisements_photos'].".id
                                         LEFT JOIN (SELECT ".Config::$values['sys_tables']['context_tags_conformity'].".id_context,COUNT(*) AS amount
                                                    FROM ".Config::$values['sys_tables']['context_tags']." 
                                                    LEFT JOIN ".Config::$values['sys_tables']['context_tags_conformity']."
                                                    ON ".Config::$values['sys_tables']['context_tags_conformity'].".id_tag = ".Config::$values['sys_tables']['context_tags'].".id
                                                    WHERE ".Config::$values['sys_tables']['context_tags'].".estate_type LIKE '%".contextCampaigns::$estate_types[$estate_type]."%'
                                                    GROUP BY id_context)AS t_tags ON t_tags.id_context = ".Config::$values['sys_tables']['context_advertisements'].".id
                                         WHERE ".$conditions." AND ".Config::$values['sys_tables']['context_advertisements'].".published = 1 AND 
                                               ".Config::$values['sys_tables']['context_campaigns'].".date_start<=NOW() AND
                                               ".Config::$values['sys_tables']['context_campaigns'].".date_end>NOW()
                                         GROUP BY ".Config::$values['sys_tables']['context_tags_conformity'].".id_context
                                         ORDER BY search_tags_amount DESC,
                                                   tags_amount ASC".
                                                  $price_sorting);
        
        return $campaigns_list;
    }
    
    /**
    * Оповещаем компанию (и/или менеджера) или специалиста о различных событиях:
    * 
    * 
    * @param mixed $notification_type - тип события:
    * 1 - объявление поступило на модерацию(м)
    * 2 - объявление прошло модерацию(к/с)
    * 3 - объявление ушло на модерацию(к/с)
    * 4 - кампания убрана в архив клиентом(м)
    * 5 - кампания убрана в архив по истечении срока действия(к/с м)
    * 6 - низкий баланс кампании(к/с м)
    * 7 - кампания убрана в архив по достижении нулевого баланса(к/с м)
    * @param mixed $data - данные по событию
    * @param mixed $nomanager - по умолчанию false, true = не уведомлять менеджера
    * @param mixed $noclient - по умолчанию false, true = не уведомлять клиента
    */
    public static function Notification($notification_type,$data=false,$nomanager=false,$noclient=false){
            //если указано, оповещаем компанию
            if(!empty($data['is_specialist'])) Response::SetBoolean('is_specialist',$data['is_specialist']);
            if (!empty($data['agency_email'])){
                Response::SetString('letter_starting','Уважаемый партнер!');
                Response::SetString('letter_ending','');
                switch($notification_type){
                    case 2:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 2;
                        $data['subject'] = "Ваше рекламное объявление ".$data['adv_title']." прошло модерацию и опубликовано";
                        break;
                    case 3:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 3;
                        $data['subject'] = "Ваше рекламное объявление ".$data['adv_title']." отправлено на модерацию.";
                        break;
                    case 5:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 5;
                        $agency_title = $data['title'];
                        $data['cmp_titles'] = implode(',<br>',$data['campaigns_titles']);
                        if($data['multiple']){
                            Response::SetBoolean('multiple_campaigns',TRUE);
                            $data['letter_reason'] = "Ваши контекстные рекламные кампании перемещены в архив по истечении срока действия:";
                            $data['subject'] = "Ваши контекстные рекламные кампании на bsn.ru перемещены в архив";
                        }
                        else $data['subject'] = "Контекстная рекламная кампания ".$data['cmp_titles'][0]." убрана в архив по истечении срока действия";
                        break;
                    case 6:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 6;
                        $data['subject'] = "Бюджет контекстной рекламной кампании ".$data['cmp_title']." заканчивается";
                        break;
                    case 7:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 7;
                        $data['subject'] = "Контекстная рекламная кампания ".$data['cmp_title']." убрана в архив по достижении нулевого баланса";
                        break;
                }
                Response::SetArray('letter_data',$data);
                $eml_tpl = new Template('/modules/context_campaigns/templates/mail.notification.html');
                $html = $eml_tpl->Processing();
                $mailer = new EMailer('mail');
                $mail_text = iconv('UTF-8', $mailer->CharSet, $html);
                if(!empty($data['subject'])) $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $data['subject']);
                $mailer->Body = $mail_text;
                $mailer->AltBody = strip_tags($mail_text);
                $mailer->IsHTML(true);
                //если email корректный, отправляем письмо
                if(!empty($data['agency_email'])  && Validate::isEmail($data['agency_email']) && !$noclient){
                    $mailer->AddAddress($data['agency_email']);
                    $mailer->AddAddress('hitty@bsn.ru');
                    $mailer->From = 'no-reply@bsn.ru';
                    $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
                    // попытка отправить
                    $mailer->Send();
                }
            }
            //оповещаем менеджера
            if (!$nomanager && !empty($data)){
                if(is_array($data['manager_name'])) $data['managers_name'] = $data['manager_name'][0];
                else $data['managers_name'] = $data['manager_name'];
                Response::SetString('letter_starting','Добрый день, '.$data['managers_name']."!");
                $manager_email = $data['manager_email'];
                switch($notification_type){
                    case 1:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 1;
                        $data['subject'] = "Необходимо проверить рекламное объявление";
                    break;
                    case 4:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 4;
                        if (!$noclient) $data['subject'] = "Рекламная кампания на bsn.ru перемещена в архив клиентом";
                        else $data['subject'] = "Рекламная кампания на bsn.ru перемещена в архив";
                    break;
                    case 5:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 5;
                        Response::SetBoolean('multiple_campaigns',true);
                        Response::SetBoolean('to_manager',true);
                        $data['letter_reason'] = "Контекстные рекламные кампании перемещены в архив по истечении срока действия:";
                        $data['subject'] = "Контекстные рекламные кампании на bsn.ru перемещены в архив";
                    break;
                    case 6:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 6;
                        $data['cmp_titles'] = $cmp_title;
                        $data['subject'] = "Баланс рекламной кампании ".$cmp_title." компании ".$agency_title." приблизился к нулю";
                    break;
                    case 7:
                        $data['letter_reason'] = '';
                        $data['letter_type'] = 7;
                        $data['subject'] = "Контекстная рекламная кампания ".$data['cmp_title']." убрана в архив по достижении нулевого баланса";
                        break;
                }
                Response::SetArray('letter_data',$data);
                $eml_tpl = new Template('/modules/context_campaigns/templates/mail.notification.html');
                $html = $eml_tpl->Processing();
                $mailer = new EMailer('mail');
                $mail_text = iconv('UTF-8', $mailer->CharSet, $html);
                if(!empty($mail_text)){
                    if(!empty($data['subject'])) $mailer->Subject = iconv('UTF-8', $mailer->CharSet, $data['subject']);
                    $mailer->Body = $mail_text;
                    $mailer->AltBody = strip_tags($mail_text);
                    $mailer->IsHTML(true);
                    //если email корректный, отправляем письмо
                    if(!empty($data['manager_email'])  && Validate::isEmail($data['manager_email'])){
                        if($notification_type == 1 && $data['manager_email'] != "s.sokolov76@gmail.com") $mailer->AddAddress("pm@bsn.ru");
                        $mailer->AddAddress($data['manager_email']);
                        $mailer->AddAddress("hitty@bsn.ru");
                        $mailer->From = 'no-reply@bsn.ru';
                        $mailer->FromName = iconv('UTF-8', $mailer->CharSet,'bsn.ru');
                        // попытка отправить
                        $mailer->Send();
                    }
                }
            }
        }
}
?>