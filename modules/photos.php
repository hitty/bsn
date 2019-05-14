<?php
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
$module_template = 'templates/photos.box.html';
Response::SetString('type', !empty($this_page->page_parameters[0]) ? $this_page->page_parameters[0] : '');
if(sizeof($this_page->page_parameters)==2 && !empty($this_page->page_parameters[0])){
        $link_table = 'estate.live_photos';
        $img_folder = Config::Get('img_folders/live');
        $photo_id = Convert::ToInt($this_page->page_parameters[1]);
        if(!empty($photo_id) ){
            $photo = $db->fetch("SELECT * FROM ".Config::$sys_tables[$this_page->page_parameters[0].'_photos']." WHERE id=?", $photo_id);
            if(!empty($photo)){
                $id_parent = $photo['id_parent'];
                //определяем, нужен ли будет яндекс-директ
                $ajax_result['par'] = $this_page->page_parameters[0];
                $no_ydirect = false;  
                if(in_array($this_page->page_parameters[0],array('housing_estates','housing_estates_progresses','cottages','business_centers','business_centers', 'live','build','commercial','country'))){
                    if($this_page->page_parameters[0] ==  'housing_estates_progresses'){
                        $parent = $db->fetch("SELECT * FROM ".Config::$sys_tables['housing_estates_progresses']." WHERE id=?", $photo['id_parent']);
                        $id_parent = $parent['id_parent'];
                        $table = 'housing_estates';
                    } else if ( in_array($this_page->page_parameters[0],array('live','build','country', 'commercial')) ) {
                        switch( $this_page->page_parameters[0] ){
                            case 'live':
                            case 'build':
                                $table = 'housing_estates';
                                $field = 'id_housing_estate';
                                break;
                            case 'country':
                                $complex_table = 'cottages';
                                $field = 'id_cottage';
                                break;
                            case 'commercial':
                                $complex_table = 'business_centers';
                                $field = 'id_business_center';
                                break;
                        } 
                        $item = $db->fetch("SELECT ".$field." as id FROM ".$sys_tables[$this_page->page_parameters[0]]." WHERE id = ?", $id_parent);
                        if(empty($item) || $item['id'] == 0) $table = $this_page->page_parameters[0];
                        else {
                            if(!empty($item[$field])){
                                $id_parent = $item['id'];
                                $table = $complex_table;
                            } 
                        }
                    } else  $table = $this_page->page_parameters[0];
                    if(in_array($table, array('housing_estates','housing_estates_progresses','cottages', 'business_centers'))){
                        $no_ydirect = $db->fetch("SELECT (advanced = 1) AS payed_page FROM ".$sys_tables[$table]." WHERE id = ".$id_parent)['payed_page'];
                    }
                }   else if($this_page->page_parameters[0] == 'business_centers_offices') $no_ydirect = true;
                else $no_ydirect = false;   
                $no_target = $no_ydirect;
                $no_direct = Request::GetString('no_direct', METHOD_POST);
                Response::SetBoolean("no_ydirect", !empty($no_direct) ? $no_direct : $no_ydirect);
                Response::SetBoolean("no_target", $no_ydirect);
                $post_title = str_replace("\n", "",Request::GetString('title',METHOD_POST));
                $title = (!empty($photo['title'])?$photo['title']." ":"").$post_title.'. Фото #'.$photo_id;
                Response::SetString('title', $title);
                Response::SetString('photo_id', $photo_id);
                Response::SetString('photo_name', $photo['name']);
                if(!empty($photo['title'])) $ajax_result['photo_title'] = $photo['title'];
                Response::SetString('img_folder', $img_folder);
                Response::SetString('img_subfolder', substr($photo['name'],0,2));
                Response::SetBoolean('ajax_mode', $ajax_mode);
                Response::SetString('url', 'http://'.Host::$host.'/'.$this_page->real_url.'/');
                //переопределение шаблона окружения и title
                if(!$ajax_mode) {
                    $this_page->setPageTemplate('templates/client.html');
                    //добавление title
                    $keywords = explode(" ",trim($title));
                    $new_meta = array('title'=>$title, 'keywords'=>implode(', ',$keywords));
                    $this_page->manageMetadata($new_meta);                        
                }
                else $ajax_result['ok'] = true;
            }
    } else $this_page->http_code=404;
} else if($this_page->page_parameters[0] == 'block' && sizeof($this_page->page_parameters)==5 && !empty($this_page->page_parameters[0])){
    $parent_id  = Convert::ToInt($this_page->page_parameters[2]);
    $table = Convert::ToString($this_page->page_parameters[1]);
    $from  =  Convert::ToInt($this_page->page_parameters[3]);
    $to  =  Convert::ToInt($this_page->page_parameters[4]);
    if(!empty($parent_id) && !empty($table)){
        $photos = Photos::getList($table,$parent_id);
        $list = [];
        foreach($photos as $k=>$item){
            if($k>=$from-1 && $k<= $to-1) $list[] = $item;
        }
        Response::SetArray('list',$list);
        Response::SetString('img_folder',Config::Get('img_folders/'.$table));
        $module_template = 'templates/photos.box.slide.html';
    } else $this_page->http_code=404;
} else $this_page->http_code=404;

?>