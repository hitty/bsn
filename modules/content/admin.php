<?php

//сигнатуры новостей
$news_signatures = array(
    'ajax::bsn::news/block/live/mainpage',
    'ajax::bsn::news/block/commercial/mainpage',
    'ajax::bsn::news/block/country/mainpage',
    'ajax::bsn::news/block/build/mainpage',
    'ajax::bsn::news/block/all/mainpage',
    'block::bsn::news/block/all/mainpage',
    'block::bsn::news/block/category/4',
    'block::bsn::news/block/3',
    'block::bsn::news/block/live',
    'block::bsn::news/block/build',
    'block::bsn::news/block/country',
    'block::bsn::news/block/commercial',
    'block::bsn::news/block/inter',
    'block::bsn::news/spb/editor/lastitem',
    'block::bsn::analytics/block/country/graphics',
    'block::bsn::analytics/block/5',
    'block::bsn::analytics/block'    
);

$GLOBALS['js_set'][] = '/modules/content/ajax_actions.js';
require_once('includes/class.paginator.php');
require_once('includes/class.tags.php');
require_once('includes/class.content.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.email.php');

// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
$content_type = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];
Response::SetString('content_type', $content_type);
// добавление title
$this_page->manageMetadata(array('title'=>'Новости'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['date'] = Request::GetString('f_date',METHOD_GET);
$filters['status'] = Request::GetInteger('f_status',METHOD_GET);
$filters['region'] = Request::GetInteger('f_region',METHOD_GET);
$filters['category'] = Request::GetInteger('f_category',METHOD_GET);
$filters['published'] = Request::GetInteger('f_published',METHOD_GET);
$filters['source'] = Request::GetInteger('f_source',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['date'])) {
    $filters['date'] = urldecode($filters['date']);
    $get_parameters['f_date'] = $filters['date'];
}
if(!empty($filters['status'])) {
    $get_parameters['f_status'] = $filters['status'];
}
if(!empty($filters['published'])) {
    $get_parameters['f_published'] = $filters['published'];
}
if(!empty($filters['region'])) {
    $get_parameters['f_region'] = $filters['region'];
}
if(!empty($filters['category'])) {
    $get_parameters['f_category'] = $filters['category'];
}
if(!empty($filters['status'])) {
    $get_parameters['f_status'] = $filters['status'];
}
if(!empty($filters['source'])) {
    $get_parameters['f_source'] = $filters['source'];
}

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;

$GLOBALS['css_set'][] = '/modules/content/admin.css';
$GLOBALS['css_set'][] = '/css/content.css';

        
// обработка action-ов
switch($action){
    case 'flush_memcache':
        if($ajax_mode){
            $memcache->flush();
            $ajax_result['ok']=true;
        }
        break;
    /*********************\
    |*  Работа с тегами  *|
    \*********************/
        case 'time':
            if($ajax_mode){
                $ajax_result['error'] = '';
                $start = Request::GetString('start',METHOD_POST);
                $action = Request::GetString('action',METHOD_POST);
                $day = Request::GetString('day',METHOD_POST);
                if(!empty($action) && !empty($day)){
                    if($action == 'off') $db->querys("DELETE FROM ".$sys_tables['news_mailer_schedule']." WHERE day_number = ?",$day);
                    elseif($action == 'on'){
                        $db->querys("INSERT IGNORE INTO ".$sys_tables['news_mailer_schedule']."  SET day_number=?, start=?
                                    ON DUPLICATE KEY UPDATE start=?
                        ", $day, $start, $start
                        );
                        $ajax_result['q'] = '';
                    }
                }
            }
            break;
    /*************************\
    |*  Работа со статитикой *|
    \*************************/
    case 'stats':
        // переопределяем экшн
        $module_template = 'admin.stats.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
        //получение данных по объекту из базы
        $info = $db->fetch("SELECT 
                                *
                            FROM ".$sys_tables[ $content_type ]."
                            WHERE `id` = ?",$id);
                    
        $post_parameters = Request::GetParameters(METHOD_POST);
        // если была отправка формы - выводим данные 
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //передача данных в шаблон
            $date_start = $post_parameters['date_start'];
            $date_end = $post_parameters['date_end'];
            $info['date_start'] = $date_start;
            $info['date_end'] = $date_end;
            $type = $db->fetch( " SELECT id FROM " . $sys_tables['content_types'] . " WHERE content_type = ?", $content_type)['id'];
            $stats = $db->fetchall("
                    SELECT 
                        IFNULL(a.show_amount,0) as show_amount, 
                        IFNULL(b.click_amount,0) as click_amount,
                        IFNULL(c.finish_amount,0) as finish_amount,
                        a.date 
                    FROM 
                    (
                      SELECT 
                          SUM(IFNULL(`amount`,0)) as show_amount, 
                          DATE_FORMAT(`date`,'%d.%m.%Y') as date
                      FROM ".$sys_tables['content_stats_full_shows']."
                      WHERE
                          `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                          `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
                      GROUP BY `date`
                    ) a
                    LEFT JOIN 
                    (
                      SELECT 
                          SUM(IFNULL(`amount`,0)) as click_amount, 
                          DATE_FORMAT(`date`,'%d.%m.%Y') as date
                      FROM ".$sys_tables['content_stats_full_clicks']."
                      WHERE
                          `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                          `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `type` = " . $type . "
                      GROUP BY `date`
                     ) b ON a.date = b.date
                    LEFT JOIN 
                    (
                      SELECT 
                          SUM(IFNULL(`amount`,0)) as finish_amount, 
                          DATE_FORMAT(`date`,'%d.%m.%Y') as date
                      FROM ".$sys_tables['content_stats_full_finish']."
                      WHERE
                          `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                          `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
                      GROUP BY `date`
                     ) c ON a.date = c.date
                     
                     UNION
                     
                     SELECT 
                        IFNULL(aa.show_amount,0) as show_amount, 
                        IFNULL(bb.click_amount,0) as click_amount,
                        IFNULL(cc.finish_amount,0) as finish_amount,
                        'сегодня' as date 
                    FROM 
                    (
                      SELECT 
                          IFNULL( COUNT(*),0 ) as show_amount, 
                         'сегодня' as date
                      FROM ".$sys_tables['content_stats_day_shows']."
                      WHERE `id_parent` = ".$id." AND type = " . $type . "
                    ) aa
                    LEFT JOIN 
                    (
                      SELECT 
                          IFNULL( COUNT(*),0 ) as click_amount, 
                          'сегодня' as date
                      FROM ".$sys_tables['content_stats_day_clicks']."
                      WHERE `id_parent` = ".$id." AND `type` = " . $type . "
                     ) bb ON aa.date = bb.date
                    LEFT JOIN 
                    (
                      SELECT 
                          IFNULL( COUNT(*),0 ) as finish_amount, 
                          'сегодня' as date
                      FROM ".$sys_tables['content_stats_day_finish']."
                      WHERE `id_parent` = ".$id."
                     ) cc ON aa.date = cc.date
                    
                ");
            Response::SetArray('stats',$stats); // статистика объекта    
            // общее количество показов/кликов/
        }
        Response::SetArray('info',$info); // информация об объекте                                        
        break;

    /***************************\
    |*  Работа с промоблоками  *|
    \***************************/
    case 'promo':
    case 'test':
    case 'advert':
    case 'test_results':
        $promo_type = $action;
        $table = $action != 'advert' ? 'articles_' . $promo_type : 'longread_advert' ;
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch(true){
            case $action == 'photos':
                if($ajax_mode){
                    $ajax_result['error'] = '';
                    // переопределяем экшн
                    $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                    
                    switch($action){
                        case 'list':
                            //получение списка фотографий
                            //id текущей новости
                            $id = Request::GetInteger('id', METHOD_POST);
                            if(!empty( $id )){
                                $list = Photos::getList( $table, $id);
                                if(!empty($list)){
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $list;
                                    $ajax_result['folder'] = Config::$values['img_folders']['news'];
                                } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        case 'add':
                            //загрузка фотографий
                            //id текущей новости
                            if( $content_type == 'longread')
                                Photos::$__folder_options = array(
                                    'sm'=>array(940,940,'',98)
                                ); // свойства папок для загрузки и формата фотографий
                            else
                                Photos::$__folder_options = array(
                                    'sm'=>array(90,90,'cut',98) ,
                                    'big'=>array(720,720,'',95) 
                                ); // свойства папок для загрузки и формата фотографий
                            $id = Request::GetInteger('id', METHOD_POST);                
                            if(!empty( $id )){
                                //default sizes removed. saving original
                                $res = Photos::Add( $table,$id,false,false,false,Config::Get('images/min_width'),Config::Get('images/min_height'), true,false,false,false,false,false,false,true);
                                if(!empty($res)){
                                    if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                    else {
                                        if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                        else {
                                            $ajax_result['ok'] = true;
                                            $ajax_result['list'] = $res;
                                        }
                                    }
                                } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;
                        
                        case 'del':
                            //удаление фото
                            //id фотки
                            $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                            if(!empty($id_photo)){
                                $res = Photos::Delete( $table, $id_photo );
                                if(!empty($res)){
                                    $ajax_result['ok'] = true;
                                } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;       
                    }         
                }         
                break;            
            case $action == 'add':
                $id_parent = Request::GetString('id',METHOD_POST);
                $db->querys("INSERT INTO " . $sys_tables[ $table ]." SET id_parent = ?", $id_parent);
                $ajax_result['id'] = $db->insert_id;
                $ajax_result['ok'] = true;  
                break;
            case $action == 'list':
                $id = Request::GetString('id',METHOD_POST);
                $content = new Content( $content_type );
                switch($promo_type){
                    case 'promo':
                        $list = $content->getPromoList( $id );
                        break;
                    case 'test':
                        $list = $content->getTestList( $id );
                        break;
                    case 'test_results':
                        $list = $content->getTestResultsList( $id );
                        break;
                    case 'advert':
                        $list = $content->getAdvertList( $id );
                        break;
                }
                Response::SetArray('list', $list);
                $ajax_result['ok'] = true; 
                $module_template = 'admin.' . $promo_type . '.list.html' ;
                break;
            case $action == 'delete':
                $id = Request::GetString('id', METHOD_POST);
                $id_parent = $db->fetch("SELECT id_parent FROM " . $sys_tables[ $table ] . ' WHERE id = ?', $id)['id_parent'];
                $db->querys("DELETE FROM " . $sys_tables[ $table ]." WHERE id = ?", $id);
                Photos::Delete( $table, $id );
                break;
            case $action == 'save':
                $post_parameters = Request::GetParameters(METHOD_POST);
                $id_parent = $db->fetch("SELECT id_parent FROM " . $sys_tables[ $table ] . ' WHERE id = ?', $post_parameters['id'])['id_parent'];
                if( $promo_type == 'promo')
                    $db->querys("UPDATE " . $sys_tables['articles_promo']." SET
                                title = ?,
                                content = ?,
                                background_position = ?,
                                background_color = ?,
                                height = ?,
                                padding_bottom = ?
                            WHERE id = ?",
                                !empty( $post_parameters['promo_title'] ) ? $post_parameters['promo_title'] : '',
                                !empty( $post_parameters['content'] ) ? $post_parameters['content'] : '',
                                !empty( $post_parameters['background_position'] ) ? $post_parameters['background_position'] : 0,
                                !empty( $post_parameters['background_color'] ) ? $post_parameters['background_color'] : 0,
                                !empty( $post_parameters['height'] ) ? $post_parameters['height'] : 0,
                                !empty( $post_parameters['padding_bottom'] ) ? $post_parameters['padding_bottom'] : 0,
                                $post_parameters['id']
                    );
                else if( $promo_type == 'advert')
                    $db->querys("UPDATE " . $sys_tables['longread_advert']." SET
                                title = ?,
                                description = ?,
                                link = ?
                            WHERE id = ?",
                                !empty( $post_parameters['longread_title'] ) ? $post_parameters['longread_title'] : '',
                                !empty( $post_parameters['longread_description'] ) ? $post_parameters['longread_description'] : '',
                                !empty( $post_parameters['longread_link'] ) ? $post_parameters['longread_link'] : '',
                                $post_parameters['id']
                    );
                else {
                    $db->querys("UPDATE " . $sys_tables['articles_test']." SET
                                title = ?
                            WHERE id = ?",
                                !empty( $post_parameters['test_title'] ) ? $post_parameters['test_title'] : '',
                                $post_parameters['id']
                    );
                    //обновление вопросов
                    $array = [];
                    foreach($post_parameters as $name=>$value){
                        if( strstr($name, 'questions_')!= ''){
                            $name = str_replace('questions_', '', $name);
                            $params = explode("_", $name);
                            $array[ $params[1] ][ $params[0] ] = $value;
                        }
                    }
                    if(!empty($array)){
                        foreach($array as $k=>$values) {
                            $values['id'] = $k;
                            $db->updateFromArray($sys_tables['articles_test_questions'], $values, 'id');
                        }
                    }
                }
                break;
            case 'questions':
            case 'results':
                  $re_action = $action;
                  $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                  switch($action){
                    case 'add':
                        $id_parent = Request::GetString('id',METHOD_POST);
                        $db->querys("INSERT INTO " . $sys_tables['articles_test_' . $re_action]." SET id_parent = ?", $id_parent);
                        $id = $ajax_result['id'] = $db->insert_id;
                        Response::SetInteger('id', $id);
                        $module_template = $re_action == 'questions' ? 'admin.test.question.item.html' : 'admin.test.results.item.html';
                        $ajax_result['ok'] = true;                          
                        break;
                    case 'save':
                        $post_parameters = Request::GetParameters(METHOD_POST);
                        $db->querys("UPDATE " . $sys_tables['articles_test_results']." SET
                                    `title` = ?,
                                    `from` = ?,
                                    `to` = ?,
                                    content = ?
                                WHERE id = ?",
                                    !empty( $post_parameters['title'] ) ? $post_parameters['title'] : '',
                                    !empty( $post_parameters['from'] ) ? $post_parameters['from'] : '',
                                    !empty( $post_parameters['to'] ) ? $post_parameters['to'] : '',
                                    !empty( $post_parameters['content'] ) ? $post_parameters['content'] : '',
                                    $post_parameters['id']
                        );
                        break;
                    
                    case 'delete':
                        $id = Request::GetString('id', METHOD_POST);
                        $db->querys("DELETE FROM " . $sys_tables['articles_test_' . $re_action]." WHERE id = ?", $id);
                        break;                        
                  }
                  break;
            
        }
        //упорядочивание позиций
        if( !empty( $id_parent ) && in_array($promo_type, array('promo', 'test'))) { 
            $list = $db->fetchall("SELECT * FROM " . $sys_tables[ $table ]. " WHERE id_parent = ? ORDER BY id ASC", false, $id_parent);
            foreach($list as $k=>$item) {
                //обновление короткого заголовка родительской статьи
                if($k == 0 && $promo_type == 'promo') $db->querys(" UPDATE " . $sys_tables['articles']." SET content_short = ?, content = ? WHERE id = ?", $item['content'], $item['content'], $id_parent);
                //обновление позиций
                $db->querys("UPDATE " . $sys_tables[ $table ] . " SET position = ? WHERE id = ?", $promo_type == 'test' ? $k + 1 : $k, $item['id']);
            }
        }
        break; 
    /*************************\
    |*  Работа с партнерами   *|
    \*************************/        
     case 'partners':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        // дефолтное значение папки выгрузки и свойств фото
        Photos::$__folder_options = array(
            'sm'=>array(300,16,'',85), 
            'big'=>array(800,60,'',75)
        );
        switch($action){
        /**************************\
        |*  Работа с фотографиями  *|
        \**************************/
        case 'photos':
            if($ajax_mode){
                $ajax_result['error'] = '';
                // переопределяем экшн
                $action = empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                switch($action){
                    case 'list':
                        //получение списка фотографий
                        //id текущей новости
                        $id = Request::GetInteger('id', METHOD_POST);
                        if(!empty($id)){
                            $list = Photos::getList('content_partners',$id);
                            if(!empty($list)){
                                $ajax_result['ok'] = true;
                                $ajax_result['list'] = $list;
                                $ajax_result['folder'] = Config::$values['img_folders']['news'];
                            } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                    case 'add':
                        //загрузка фотографий
                        //id текущей новости
                        $id = Request::GetInteger('id', METHOD_POST);                
                        if(!empty($id)){
                            //default sizes 70x90 removed
                            $res = Photos::Add('content_partners',$id,false,false,false,false,false,true);
                            if(!empty($res)){
                                $ajax_result['ok'] = true;
                                $ajax_result['list'] = $res;
                            } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                        case 'setTitle':
                            //добавление названия
                            $id = Request::GetInteger('id_photo', METHOD_POST);                
                            $title = Request::GetString('title', METHOD_POST);                
                            if(!empty($id)){
                                $res = Photos::setTitle('content_partners',$id, $title);
                                $ajax_result['last_query'] = '';
                                if(!empty($res)) $ajax_result['ok'] = true;
                                else $ajax_result['error'] = 'Невозможно выполнить обновление названия фото';
                            } else $ajax_result['error'] = 'Неверные входные параметры';
                            break;    
                    case 'del':
                        //удаление фото
                        //id фотки
                        $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                        if(!empty($id_photo)){
                            $res = Photos::Delete('content_partners',$id_photo);
                            
                            if(!empty($res)){
                                $ajax_result['ok'] = true;
                            } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                        } else $ajax_result['error'] = 'Неверные входные параметры';
                        break;
                }
            }
            break;    
            case 'list':
                    $search_string = Request::GetString('search_string',METHOD_POST);
                    $list = $db->fetchall("SELECT ".$sys_tables['content_partners'].".id, ".$sys_tables['content_partners'].".title 
                                            FROM ".$sys_tables['content_partners']."
                                            WHERE ".$sys_tables['content_partners'].".title LIKE '%".$search_string."%'
                                            GROUP BY ".$sys_tables['content_partners'].".id
                                            ORDER BY  ".$sys_tables['content_partners'].".title
                                            LIMIT 10
                    ");
                    $ajax_result['ok'] = true;
                    if(!empty($list)) $ajax_result['list'] = $list;
                    else $ajax_result['list'] = array(0=>array('id'=>0,'title'=>'Партнер не найден'));
                    
                break;            
            case 'add':
            case 'edit':
                // переопределяем экшн
                $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];    
                $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
                $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';                                                
                $module_template = 'admin.partners.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['content_partners']);
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables['content_partners']." 
                                        WHERE id=?", $id) ;
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['partners'][$key])) $mapping['partners'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                //выкусывание http
                if(!empty($post_parameters['site'])){
                    $post_parameters['site'] = trim( preg_replace( '#https?://#sui', '', $post_parameters['site']), '/' );
                }
                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['partners'][$key])) $mapping['partners'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['partners']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['partners'][$key])) $mapping['partners'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['partners'][$key]['value'])) $info[$key] = $mapping['partners'][$key]['value'];
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['content_partners'], $info, 'id') or die($db->error);
                        } else {
                            $res = $db->insertFromArray($sys_tables['content_partners'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/articles/partners/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления, 
                // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['partners']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
                $res = $db->querys("DELETE FROM ".$sys_tables['content_partners']." WHERE id=?", $id);
                //удаление фото эксперта
                $del_photos = Photos::DeleteAll('content_partners',$id);                
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            default:
                $module_template = 'admin.partners.list.html';
                $conditions = [];
                if(!empty($filters)){
                    if(!empty($filters['title'])) $conditions['title'] = $sys_tables['content_partners'].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
                }
                // формирование списка для фильтра
                $condition = implode(" AND ",$conditions);        
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['content_partners'], 30, $condition);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/articles/partners/'                // модуль
                                          ."?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }
                
                $sql = "SELECT 
                            ".$sys_tables['content_partners'].".*, 
                            CONCAT_WS('/','".Config::$values['img_folders']['news']."','sm',LEFT(photos.name,2)) as partner_photo_folder,
                            photos.name as partner_photo 
                        FROM ".$sys_tables['content_partners'];
                $sql .= " LEFT JOIN  ".$sys_tables['content_partners_photos']." photos ON photos.id_parent=".$sys_tables['content_partners'].".id";
                if(!empty($condition)) $sql .= " WHERE ".$condition;
                $sql .= " GROUP BY ".$sys_tables['content_partners'].".id
                          ORDER BY ".$sys_tables['content_partners'].".id DESC";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
            
                Response::SetArray('list', $list);
                if($paginator->pages_count>1){
                    Response::SetArray('paginator', $paginator->Get($page));
                }
                break;    
        }
        break;
    /************************************\
    |*  Теги                            *|
    \************************************/        
    case 'tags':
        if($ajax_mode){
            $ajax_result['error'] = '';
            // переопределяем экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            switch($action){
                case 'list':
                    $search_str = Request::GetString('search_string', METHOD_POST);
                    $list = Tags::searchTags($search_str, $content_type);
                    $ajax_result['ok'] = true;
                    $ajax_result['list'] = $list;
                    break;
                case 'add':
                    $tag = Request::GetString('tag', METHOD_POST);
                    $id_object = Request::GetString('id_object', METHOD_POST);
                    if(!empty($tag) && !empty($id_object)){
                        $id_tag = Tags::addTag($tag, $content_type);
                        if(!empty($id_tag)){
                            $res = Tags::linkTag($id_tag, $id_object, $sys_tables[$content_type . '_tags']);
                            if(!empty($res)){
                                $ajax_result['ok'] = true;
                                $ajax_result['tag'] = $tag;
                                $ajax_result['id'] = $id_tag;
                            } else $ajax_result['error'] = 'Невозможно выполнить присоединение тега';
                        }  else $ajax_result['error'] = 'Невозможно добавить тег в БД';
                    } else $ajax_result['error'] = 'Не верные входные параметры';
                    break;
                case 'del':
                    $id_tag = Request::GetString('id_tag', METHOD_POST);
                    $id_object = Request::GetString('id_object', METHOD_POST);
                    if(!empty($id_tag) && !empty($id_object)){
                        $res = Tags::unlinkTag($id_tag, $id_object, $sys_tables[$content_type . '_tags']);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно выполнить отсоединение тега';
                    } else $ajax_result['error'] = 'Не верные входные параметры';
                    break;
            }
        }
        break;
    /**************************\
    |*  Работа с фотографиями  *|
    \**************************/
    case 'photos':
        if($ajax_mode){
            $ajax_result['error'] = '';
            // переопределяем экшн
            $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
            
            switch($action){
                case 'list':
                    //получение списка фотографий
                    //id текущей новости
                    $id = Request::GetInteger('id', METHOD_POST);
                    if(!empty($id)){
                        $list = Photos::getList($content_type, $id);
                        if(!empty($list)){
                            $ajax_result['ok'] = true;
                            $ajax_result['list'] = $list;
                            $ajax_result['folder'] = Config::$values['img_folders']['news'];
                        } else $ajax_result['error'] = 'Невозможно построить список фотографий';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'add':
                    //загрузка фотографий
                    //id текущей новости
                    Photos::$__folder_options=array(
                        'sm'=>array(110,82,'cut',80),
                        'med'=>array(460,310,'',90),
                        'big'=>array(2000,1600,'',95) 
                    );// свойства папок для загрузки и формата фотографий
                    $id = Request::GetInteger('id', METHOD_POST);                
                    if(!empty($id)){
                        //default sizes removed. saving original
                        $res = Photos::Add($content_type,$id,false,false,false,Config::Get('images/min_width'),Config::Get('images/min_height'), true,false,false,false,false,false,false,true);
                        if(!empty($res)){
                            if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                            else {
                                if(gettype($res) == 'string') $ajax_result['error'] = $res;  
                                else {
                                    $ajax_result['ok'] = true;
                                    $ajax_result['list'] = $res;
                                }
                            }
                        } else $ajax_result['error'] = 'Невозможно выполнить добавление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'setTitle':
                    //добавление названия
                    $id = Request::GetInteger('id_photo', METHOD_POST);                
                    $title = Request::GetString('title', METHOD_POST);                
                    if(!empty($id)){
                        $res = Photos::setTitle($content_type,$id, $title);
                        $ajax_result['last_query'] = '';
                        if(!empty($res)) $ajax_result['ok'] = true;
                        else $ajax_result['error'] = 'Невозможно выполнить обновление названия фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;

                case 'del':
                    //удаление фото
                    //id фотки
                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                    if(!empty($id_photo)){
                        $res = Photos::Delete($content_type,$id_photo);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно выполнить удаление фото';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'setMain':
                    // установка флага "главное фото" для объекта
                    //id текущей новости
                    $id = Request::GetInteger('id', METHOD_POST);
                    //id фотки
                    $id_photo = Request::GetInteger('id_photo', METHOD_POST);                
                    if(!empty($id_photo)){
                        $res = Photos::setMain($content_type, $id, $id_photo);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно установить статус';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
                case 'sort':
                    // сортировка фото 
                    //порядок следования фотографий
                    $order = Request::GetArray('order', METHOD_POST);
                    if(!empty($order)){
                        $res = Photos::Sort($content_type, $order);
                        if(!empty($res)){
                            $ajax_result['ok'] = true;
                        } else $ajax_result['error'] = 'Невозможно отсортировать';
                    } else $ajax_result['error'] = 'Неверные входные параметры';
                    break;
            }
        }
        break;
    /********************************\
    |*  Работа со списком регионов  *|
    \********************************/
    case 'regions':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            case 'add':
            case 'edit':
                $module_template = 'admin.regions.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables[$content_type . '_regions']);
                    // определяем позицию
                    $row = $db->fetch("SELECT max(position) as position FROM ".$sys_tables[$content_type . '_regions']);
                    if(!empty($row)) $info['position'] = $row['position']+1;
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables[$content_type . '_regions']." 
                                        WHERE id=?", $id);
                    if(empty($info)) Host::Redirect('/admin/content/' . $content_type . '/regions/add/');
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['regions'][$key])) $mapping['regions'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);

                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['regions'][$key])) $mapping['regions'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['regions']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['regions'][$key])) $mapping['regions'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['regions'][$key]['value'])) $info[$key] = $mapping['regions'][$key]['value'];
                        }
                        
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables[$content_type . '_regions'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables[$content_type . '_regions'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/' . $content_type . '}/regions/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления, 
                // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['regions']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->querys("DELETE FROM ".$sys_tables[$content_type . '_regions']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            case 'up':
                if($action == 'up'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables[$content_type . '_regions']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables[$content_type . '_regions']."
                                SET `position` = `position` + 2
                                WHERE `id` <> ?  AND `position` >= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables[$content_type . '_regions']."
                                    SET `position` = ? + 1
                                    WHERE `position` < ?
                                    ORDER BY `position` DESC LIMIT 1";
                            $res = $db->querys($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            case 'down':
                if($action == 'down'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables[$content_type . '_regions']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables[$content_type . '_regions']."
                                SET `position` = `position` - 2
                                WHERE `id` <> ?  AND `position` <= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables[$content_type . '_regions']."
                                    SET `position` = ? - 1
                                    WHERE `position` > ?
                                    ORDER BY `position` ASC LIMIT 1";
                            $res = $db->querys($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            default:
                $module_template = 'admin.regions.list.html';
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables[$content_type . '_regions'], 30);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/' . $content_type . '}/regions'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT id,title,position FROM ".$sys_tables[$content_type . '_regions'];
                $sql .= " ORDER BY `position`";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
        }
        break;
    /*************************************\
    |*  Работа с баннерами для рассылки  *|
    \*************************************/
    case 'mailer_banners':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            case 'add':
            case 'edit':
                $module_template = 'admin.mailer_banners.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables['news_mailer_banners']);
                    // определяем позицию
                    $row = $db->fetch("SELECT max(position) as position FROM ".$sys_tables['news_mailer_banners']);
                    if(!empty($row)) $info['position'] = $row['position']+1;
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *,LEFT (`name`,2) as `subfolder`
                                        FROM ".$sys_tables['news_mailer_banners']." 
                                        WHERE id=?", $id);
                    if(empty($info)) Host::Redirect('/admin/content/' . $content_type . '}/mailer_banners/add/');
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['mailer_banners'][$key])) $mapping['mailer_banners'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);
                if(!empty($info['subfolder']))Response::SetString('img_folder', Config::$values['img_folders']['news_mailer_banners'].'/'.$info['subfolder']); // папка для баннеров
                // если была отправка формы - начинаем обработку
                // замена фотографий баннера
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    if(!empty($_FILES)){
                        foreach ($_FILES as $fname => $data){
                            if ($data['error']==0) {
                                $size = getimagesize($data['tmp_name']);
                                if($size[0]>240) $mapping['mailer_banners']['img_src']['error'] = 'Максимальная ширина файла 240px. Размер вашего файла'.$size[0].'x'.$size[1].'px'; 
                                else{
                                    //определение размера загруженного фото
                                    $_folder = Host::$root_path.'/'.Config::$values['img_folders']['news_mailer_banners'].'/'; // папка для файлов  тгб
                                    $fileTypes = array('jpg','jpeg','png'); // допустимые расширения файлов
                                    $fileParts = pathinfo($data['name']);
                                    $targetExt = $fileParts['extension'];
                                    $_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                                    if (in_array(strtolower($targetExt),$fileTypes)) {
                                        $_subfolder = substr($_targetFile,0,2);
                                        move_uploaded_file($data['tmp_name'],$_folder.$_subfolder.'/'.$_targetFile);
                                        if(file_exists($_folder.$mapping['mailer_banners'][$fname]['value']) && is_file($_folder.$mapping['mailer_banners'][$fname]['value'])) unlink($_folder.$mapping['mailer_banners'][$fname]['value']);
                                        $post_parameters[$fname] = $_targetFile;
                                        Response::SetString('img_folder', Config::$values['img_folders']['news_mailer_banners'].'/'.$_subfolder);
                                    } else $mapping['mailer_banners']['img_src']['error'] = 'Разрешенные форматы JPG и PNG. Формат вашего файла: '.strtolower($targetExt); 
                                }
                            }
                        }
                    }

                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['mailer_banners'][$key])) $mapping['mailer_banners'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['mailer_banners']);
                    

                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['mailer_banners'][$key])) $mapping['mailer_banners'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['mailer_banners'][$key]['value'])) $info[$key] = $mapping['mailer_banners'][$key]['value'];
                        }
                        
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables['news_mailer_banners'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables['news_mailer_banners'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/' . $content_type . '}/mailer_banners/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления, 
                // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['mailer_banners']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->querys("DELETE FROM ".$sys_tables['news_mailer_banners']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            case 'up':
                if($action == 'up'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables['news_mailer_banners']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables['news_mailer_banners']."
                                SET `position` = `position` + 2
                                WHERE `id` <> ?  AND `position` >= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables['news_mailer_banners']."
                                    SET `position` = ? + 1
                                    WHERE `position` < ?
                                    ORDER BY `position` DESC LIMIT 1";
                            $res = $db->querys($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            case 'down':
                if($action == 'down'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables['news_mailer_banners']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables['news_mailer_banners']."
                                SET `position` = `position` - 2
                                WHERE `id` <> ?  AND `position` <= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables['news_mailer_banners']."
                                    SET `position` = ? - 1
                                    WHERE `position` > ?
                                    ORDER BY `position` ASC LIMIT 1";
                            $res = $db->querys($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            default:
                $module_template = 'admin.mailer_banners.list.html';
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables['news_mailer_banners'], 30);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/' . $content_type . '}/mailer_banners'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT *, LEFT (`name`,2) as `subfolder` FROM ".$sys_tables['news_mailer_banners'];
                $sql .= " ORDER BY `position`";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                Response::SetString('img_folder', Config::$values['img_folders']['news_mailer_banners']); // папка для баннеров
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
        }
        break;        
    /*********************************\
    |*  Работа со списком категорий  *|
    \*********************************/
    case 'categories':
        // переопределяем экшн
        $action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch($action){
            case 'add':
            case 'edit':
                $module_template = 'admin.categories.edit.html';
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                if($action=='add'){
                    // создание болванки новой записи
                    $info = $db->prepareNewRecord($sys_tables[$content_type . '_categories']);
                    // определяем позицию
                    $row = $db->fetch("SELECT max(position) as position FROM ".$sys_tables[$content_type . '_categories']);
                    if(!empty($row)) $info['position'] = $row['position']+1;
                } else {
                    // получение данных из БД
                    $info = $db->fetch("SELECT *
                                        FROM ".$sys_tables[$content_type . '_categories']." 
                                        WHERE id=?", $id);
                    if(empty($info)) Host::Redirect('/admin/content/' . $content_type . '}/categories/add/');
                }
                // перенос дефолтных (считанных из базы) значений в мэппинг формы
                foreach($info as $key=>$field){
                    if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $info[$key];
                }
                // получение данных, отправленных из формы
                $post_parameters = Request::GetParameters(METHOD_POST);

                // если была отправка формы - начинаем обработку
                if(!empty($post_parameters['submit'])){
                    Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
                    // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['value'] = $post_parameters[$key];
                    }
                    // проверка значений из формы
                    $errors = Validate::validateParams($post_parameters,$mapping['categories']);
                    // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
                    foreach($errors as $key=>$value){
                        if(!empty($mapping['categories'][$key])) $mapping['categories'][$key]['error'] = $value;
                    }
                    // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
                    if(empty($errors)) {
                        // подготовка всех значений для сохранения
                        foreach($info as $key=>$field){
                            if(isset($mapping['categories'][$key]['value'])) $info[$key] = strip_tags($mapping['categories'][$key]['value'],'<a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3><blockquote><figcaption><iframe>');
                        }
                        // сохранение в БД
                        if($action=='edit'){
                            $res = $db->updateFromArray($sys_tables[$content_type . '_categories'], $info, 'id');
                        } else {
                            $res = $db->insertFromArray($sys_tables[$content_type . '_categories'], $info, 'id');
                            if(!empty($res)){
                                $new_id = $db->insert_id;
                                // редирект на редактирование свеженькой страницы
                                if(!empty($res)) {
                                    header('Location: '.Host::getWebPath('/admin/content/' . $content_type . '}/categories/edit/'.$new_id.'/'));
                                    exit(0);
                                }
                            }
                        }
                        Response::SetBoolean('saved', $res); // результат сохранения
                    } else Response::SetBoolean('errors', true); // признак наличия ошибок
                }
                // если мы попали на страницу редактирования путем редиректа с добавления, 
                // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
                $referer = Host::getRefererURL();
                if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
                    Response::SetBoolean('form_submit', true);
                    Response::SetBoolean('saved', true);
                }
                // запись данных для отображения на странице
                Response::SetArray('data_mapping',$mapping['categories']);
                break;
            case 'del':
                $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                $res = $db->querys("DELETE FROM ".$sys_tables[$content_type . '_categories']." WHERE id=?", $id);
                $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
                if($ajax_mode){
                    $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
                    break;
                }
            case 'up':
                if($action == 'up'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables[$content_type . '_categories']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables[$content_type . '_categories']."
                                SET `position` = `position` + 2
                                WHERE `id` <> ?  AND `position` >= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables[$content_type . '_categories']."
                                    SET `position` = ? + 1
                                    WHERE `position` < ?
                                    ORDER BY `position` DESC LIMIT 1";
                            $res = $db->querys($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            case 'down':
                if($action == 'down'){
                    $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
                    $item = $db->fetch("SELECT position FROM ".$sys_tables[$content_type . '_categories']." WHERE id=?",$id);
                    if(empty($item)) $results['move'] = -1;
                    else {
                        $sql = "UPDATE ".$sys_tables[$content_type . '_categories']."
                                SET `position` = `position` - 2
                                WHERE `id` <> ?  AND `position` <= ?";
                        $res = $db->querys($sql, $id, $item['position']);
                        if(empty($res)) $results['move'] = -1;
                        else {
                            $sql = "UPDATE ".$sys_tables[$content_type . '_categories']."
                                    SET `position` = ? - 1
                                    WHERE `position` > ?
                                    ORDER BY `position` ASC LIMIT 1";
                            $res = $db->querys($sql, $item['position'], $item['position']);
                            if(empty($res)) $results['move'] = -1;
                            else $results['move'] = $id;
                        }
                    }
                }
            default:
                $module_template = 'admin.categories.list.html';
                // создаем пагинатор для списка
                $paginator = new Paginator($sys_tables[$content_type . '_categories'], 30);
                // get-параметры для ссылок пагинатора
                $get_in_paginator = [];
                foreach($get_parameters as $gk=>$gv){
                    if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
                }
                // ссылка пагинатора
                $paginator->link_prefix = '/admin/content/' . $content_type . '}/categories'                  // модуль
                                          ."/?"                                       // конечный слеш и начало GET-строки
                                          .implode('&',$get_in_paginator)             // GET-строка
                                          .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
                if($paginator->pages_count>0 && $paginator->pages_count<$page){
                    Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
                    exit(0);
                }

                $sql = "SELECT id,title,position FROM ".$sys_tables[$content_type . '_categories'];
                $sql .= " ORDER BY `position`";
                $sql .= " LIMIT ".$paginator->getLimitString($page); 
                $list = $db->fetchall($sql);
                // формирование списка
                Response::SetArray('list', $list);
                Response::SetArray('paginator', $paginator->Get($page));
        }
        break;
    /*********************************\
    |*  Работа со временем рассылки  *|
    \*********************************/
    case 'check_time':
        $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
        $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
        $module_template = 'admin.time.html';
        //часы работы
        $open_hours = $db->fetchall("SELECT * FROM ".$sys_tables['news_mailer_schedule']);
        $open_hours_array = [];
        foreach($open_hours as $k=>$item) $open_hours_array[$item['day_number']] = $item;
        Response::SetArray('open_hours',$open_hours_array);                        
        break;
    /************************\
    |*  Работа с новостями  *|
    \************************/
    case 'add':
    case 'edit':
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/modules/content/tags_autocomplette.js';
        $GLOBALS['js_set'][] = '/js/file_upload/jquery.uploadifive.js';
        $GLOBALS['css_set'][] = '/js/file_upload/uploadify.css';
        $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
        $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
        
        $module_template = 'admin.edit.html';
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
            
        if($action=='add'){
            // создание болванки новой записи
            $info = $db->prepareNewRecord($sys_tables[$content_type]);
            $info['datetime'] = date('d.m.Y H:i');
            $info['content'] = $info['content_short'] = "";
        } else {
            // получение данных из БД
            $info = $db->fetch("SELECT *, DATE_FORMAT(`datetime`,'%d.%m.%Y %H:%i') as datetime
                                FROM ".$sys_tables[$content_type]." 
                                WHERE id=?", $id);
            if(empty($info)) Host::Redirect('/admin/content/' . $content_type . '}/add/');
            // начальное получение списка прилинкованных тегов
            $tags_list = Tags::getLinkedTags($id, $sys_tables[$content_type . '_tags']);
            Response::SetArray('tags_list',$tags_list);
        }
        // перенос дефолтных (считанных из базы) значений в мэппинг формы
        foreach($info as $key=>$field) if(!empty($mapping[$content_type][$key])) $mapping[$content_type][$key]['value'] = $info[$key];
        
        // формирование дополнительных данных для формы (не из основной таблицы)
        if(!empty($sys_tables[$content_type . '_regions'])){
            $regions = $db->fetchall("SELECT id,title, code FROM ".$sys_tables[$content_type . '_regions']." ORDER BY position");
            foreach($regions as $key=>$val) $mapping[$content_type]['id_region']['values'][$val['id']] = $val['title'];
        }
        $categories = $db->fetchall("SELECT id,title, code FROM ".$sys_tables[$content_type . '_categories']." ORDER BY position");
        foreach($categories as $key=>$val) $mapping[$content_type]['id_category']['values'][$val['id']] = $val['title'];
            
        // получение данных, отправленных из формы
        $post_parameters = Request::GetParameters(METHOD_POST);
         if(!empty($info['id_partner']) || !empty($mapping[$content_type]['id_partner']['value']) || !empty($post_parameters['id_partner'])){
            $post_parameters['partner_title'] = $mapping[$content_type]['partner_title']['value'] = $db->fetch("SELECT title FROM " . $sys_tables['content_partners'] . " WHERE id = ?", ( !empty($post_parameters['id_partner']) ? $post_parameters['id_partner'] : ( !empty($mapping[$content_type]['id_partner']['value']) ? $mapping[$content_type]['id_partner']['value'] : $info['id_partner'] ) ) )['title'];
        }
        
    
        // если была отправка формы - начинаем обработку
        if(!empty($post_parameters['submit'])){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            // перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
            foreach($post_parameters as $key=>$field){
                if(!empty($mapping[$content_type][$key])) $mapping[$content_type][$key]['value'] = $post_parameters[$key];
            }
            // проверка значений из формы
            $errors = Validate::validateParams($post_parameters,$mapping[$content_type]);
            // выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
            foreach($errors as $key=>$value){
                if(!empty($mapping[$content_type][$key])) $mapping[$content_type][$key]['error'] = $value;
            }
            $news = new Content( 'news' );
            // если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения

            if(empty($errors)) {
                // подготовка всех значений для сохранения
                foreach($info as $key=>$field){
                    if (isset($mapping[$content_type][$key]['value'])) $info[$key] = strip_tags($mapping[$content_type][$key]['value'],'<figcaption><table><tr><td><th><a><strong><b><a><i><em><img><ul><li><p><div><span><br><h2><h3><blockquote><iframe>');
                }
                if(!empty($info['video_link'])){
                     $parse_link = parse_url($info['video_link']);
                     $info['video_link'] = !empty($parse_link['query'])?trim($parse_link['query'],'v='):trim($parse_link['path'],'/');;
                }
                //преобразование даты в Mysql-формат
                $info['datetime'] = date("Y-m-d H:i:s", strtotime($info['datetime'])); 

                // сохранение в БД
                if($action=='edit'){
                    $res = $db->updateFromArray($sys_tables[$content_type], $info, 'id');
                } else {
                    $res = $db->insertFromArray($sys_tables[$content_type], $info, 'id');

                    if(!empty($res)){
                        $new_id = $db->insert_id;
                        
                        //Матвей:формирование ЧПУ-строки
                        $db->querys( "UPDATE ".$sys_tables[$content_type]." SET `chpu_title` = ? WHERE `id` = ?", $new_id.'_'.createCHPUTitle($info['title']), $new_id);
                        //Матвей:end
                        
                        //отправка Яндексу пинга новости
                        //определение urla
                        //категория
                        foreach($categories as $k=>$value) 
                            if($value['id']==$info['id_category'])
                                $category = $value['code'];
                        //категория
                        foreach($regions as $k=>$value) 
                            if($value['id']==$info['id_region'])
                                $region = $value['code'];
                        
                        // редирект на редактирование свеженькой страницы
                        header('Location: '.Host::getWebPath('/admin/content/' . $content_type . '/edit/'.$new_id.'/'));
                        exit(0);
                    }
                }
                Response::SetBoolean('saved', $res); // результат сохранения
                foreach($news_signatures as $type) if($memcache->get($type)) $memcache->delete($type);
            } else Response::SetBoolean('errors', true); // признак наличия ошибок
        }
        // возможность комментирования
        if( !empty( $mapping[$content_type]['paid'] ) ) {
            if($mapping[$content_type]['paid']['value']==1){
                $mapping[$content_type]['show_comments']['hidden'] = false;
            } else {
                $mapping[$content_type]['show_comments']['hidden'] = true;
            }        
        }        
        
        // если мы попали на страницу редактирования путем редиректа с добавления, 
        // значит мы успешно создали новый объект, нужно об этом сообщить в шаблон
        $referer = Host::getRefererURL();
        if($action=='edit' && !empty($referer) && substr($referer,-5)=='/add/') {
            Response::SetBoolean('form_submit', true);
            Response::SetBoolean('saved', true);
        }
        // запись данных для отображения на странице
        Response::SetArray('data_mapping',$mapping[$content_type]);

        break;
    case 'del':
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
        $del_photos = Photos::DeleteAll($content_type, $id);
        $res = $db->querys("DELETE FROM ".$sys_tables[$content_type]." WHERE id=?", $id);
        $results['delete'] = ($res && $db->affected_rows) ? $id : -1;
        if($ajax_mode){
            $ajax_result = array('ok' => $results['delete']>0, 'ids'=>array($id));
            break;
        }
    //список новостей по тегам
    case 'tagged_articles':
        $id = Request::GetInteger('article_id',METHOD_POST);
        $tags_list = Request::GetArray('tags',METHOD_POST);
        if(empty($tags_list)){
            $ajax_result['articles'] = [];
            $ajax_result['ok'] = true;
            break;
        }else $tags_list = array_map("Convert::ToInt",$tags_list);
        
        $tags_amount = count($tags_list);
        $tags_list = implode(',',$tags_list);
        $news_ids = $db->fetchall("SELECT id_object FROM ".$sys_tables[$content_type . '_tags']."  WHERE id_tag IN (".$tags_list.") AND id_object != ? GROUP BY id_object HAVING COUNT(id_tag) = ".$tags_amount,"id_object",$id);
        $news_ids = implode(',',array_keys($news_ids));
        $tagged_articles_list = $db->fetchall("SELECT ".$sys_tables[$content_type].".id,".$sys_tables[$content_type].".title,".$sys_tables[$content_type].".published,".$sys_tables[$content_type].".`datetime`,
                                               ".$sys_tables[$content_type . '_categories'].".code as category_code,
                                               ".$sys_tables[$content_type . '_regions'].".code as region_code
                                               FROM ".$sys_tables[$content_type]."
                                               LEFT JOIN ".$sys_tables[$content_type . '_categories']." ON ".$sys_tables[$content_type . '_categories'].".id = ".$sys_tables[$content_type].".id_category
                                               LEFT JOIN ".$sys_tables[$content_type . '_regions']." ON ".$sys_tables[$content_type . '_regions'].".id = ".$sys_tables[$content_type].".id_region
                                               WHERE ".$sys_tables[$content_type].".id IN (".$news_ids.")
                                               ORDER BY ".$sys_tables[$content_type].".`datetime` DESC");
        
        $tpl = new Template("/modules/content/templates/admin.tagged_articles_block.html","");
        Response::SetArray('list',$tagged_articles_list);
        $tagged_articles = $tpl->Processing();
        $ajax_result['html'] = $tagged_articles;
        $ajax_result['ok'] = true;
        break;        
    //статистика парсера новостей
    case 'news_sources':
        $action = (!empty($this_page->page_parameters[2])?$this_page->page_parameters[2]:"");
        //расписание парсера новостей загнано в cron
        $parser_timetable = array(8,10,13,16,19,21,23);
        switch(true){
            //перенос в новости - создаем заготовку
            case $ajax_mode && $action == 'change_status':
                $id = Request::GetInteger('id',METHOD_POST);
                $active = Request::GetBoolean('active',METHOD_POST);
                if(empty($id)){
                    $ajax_result['ok'] = false;
                    break;
                }
                $ajax_result['ok'] = $db->querys("UPDATE ".$sys_tables['news_sources']." SET status = ? WHERE id = ?",(empty($active)?2:1),$id);
                break;
            //парсер новостей
            //case $ajax_mode && $action == 'run_parser':
            case $action == 'run_parser':
                
                //проверяем, что можем запустить
                $parser_active = $db->fetch("SELECT id FROM ".$sys_tables['news_articles_parsing']." WHERE end_datetime LIKE '%000%'");
                if(!empty($parser_active) && !empty($parser_active['id'])){
                    $ajax_result['message'] = "Парсер в данный момент работает, запуск невозможен.";
                    $ajax_result['ok'] = true;
                    break;
                }
                $time_from_last = $db->fetch("SELECT MIN(TIMESTAMPDIFF(MINUTE,end_datetime,NOW())) AS time_from_last,HOUR(start_datetime) AS start_hour FROM ".$sys_tables['news_articles_parsing']." WHERE run_type = 2");
                
                if(!empty($time_from_last) && !empty($time_from_last['start_hour'])){
                    foreach($parser_timetable as $auto_run_hour){
                        if($time_from_last['start_hour'] < $auto_run_hour) break;
                    }
                    if( $time_from_last['time_from_last'] < 90 || date('H',time()) < $auto_run_hour){
                        $ajax_result['message'] = "Парсер не может быть запущен вручную более чем один раз между автоматическими чтениями и менее чем через 1.5 часа после последнего запуска.";
                        $ajax_result['ok'] = true;
                        break;
                    }
                }
                //пускаем
                $run_type = 2;
                exec('cron/news_parser/news_parser.php 2 &');
                $ajax_result['message'] = 'Парсер успешно отработал';
                $ajax_result['ok'] = true;
                break;
            default:
                $can_run_parser = true;
                $parser_status = "";
                $parser_busy = false;
                //проверяем, что можем запустить парсер
                $parser_active = $db->fetch("SELECT id,run_type FROM ".$sys_tables['news_articles_parsing']." WHERE end_datetime LIKE '%000%'");
                if(!empty($parser_active) && !empty($parser_active['id'])){
                    $parser_status = ($parser_active['run_type'] == 1?"auto":"");
                    $parser_busy = true;
                    $can_run_parser = false;
                } 
                
                $time_from_last = $db->fetch("SELECT MIN(TIMESTAMPDIFF(MINUTE,end_datetime,NOW())) AS time_from_last,DATE_FORMAT(start_datetime,'%d.%m.%Y %H:%i:%s') AS start_datetime,HOUR(start_datetime) AS start_hour FROM ".$sys_tables['news_articles_parsing']." WHERE run_type = 2");
                
                Response::SetString('last_hand_run',$time_from_last['start_datetime']);
                
                if(!empty($time_from_last) && !empty($time_from_last['start_hour'])){
                    foreach($parser_timetable as $auto_run_hour){
                        if($time_from_last['start_hour'] < $auto_run_hour) break;
                    }
                    if( $time_from_last['time_from_last'] < 90 || date('H',time()) < $auto_run_hour){
                        $can_run_parser = false;
                    }
                }
                Response::SetBoolean('can_run_parser',$can_run_parser);
                Response::SetString('parser_status',$parser_status);
                Response::SetString('parser_busy',$parser_busy);
                $list = $db->fetchall("SELECT id,url,title,status,
                                              CASE
                                                WHEN status = 1 THEN 'Активен'
                                                WHEN status = 2 THEN 'Отключен'
                                              END AS status_title,
                                              articles_recieved,
                                              articles_published
                                              FROM ".$sys_tables['news_sources']."
                                       UNION
                                       SELECT 0,0,0,0,0,SUM(articles_recieved) AS articles_total,SUM(articles_published) AS articles_total_published FROM ".$sys_tables['news_sources']."");
                $totals = array_pop($list);
                Response::SetArray('totals',$totals);
                Response::SetArray('list',$list);
                $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
                $module_template = "admin.sources.list.html";
        }
        break;
    //список статей, подобранных из источников
    case 'news_from_sources':
        $action = (!empty($this_page->page_parameters[2])?$this_page->page_parameters[2]:"");
        switch(true){
            //перенос в новости - создаем заготовку
            case $ajax_mode && $action == 'to_news':
                $id = (!empty($this_page->page_parameters[3])?$this_page->page_parameters[3]:"");
                $article_info = $db->fetch("SELECT * FROM ".$sys_tables['news_parsing']." WHERE id = ?",$id);
                if(empty($article_info)){
                    $ajax_result['ok'] = false;
                    break;
                }
                $info = $db->prepareNewRecord($sys_tables[$content_type]);
                $info['chpu_title'] = Convert::ToTranslit($article_info['title']);
                $info['content'] = $article_info['text'];
                $info['author'] = "bsn.ru";
                $info['title'] = $article_info['title'];
                $info['status'] = 5;
                $info['yandex_feed'] = 2;
                $info['vkontakte_feed'] = 2;
                $info['newsletter_feed'] = 2;
                $ajax_result['ok'] = $db->insertFromArray($sys_tables[$content_type],$info);
                $news_inserted_id = $db->insert_id;
                
                $db->querys("UPDATE ".$sys_tables[$content_type]." SET chpu_title = CONCAT(id,'_',chpu_title) WHERE id = ?", $news_inserted_id);
                
                $db->querys("UPDATE ".$sys_tables['news_sources']." SET articles_published = articles_published + 1 WHERE id = ?",$article_info['id_source']);
                
                if(!empty($this_page->page_parameters[4]) && $this_page->page_parameters[4] == 'w_edit') $ajax_result['new_href'] = '/admin/content/' . $content_type . '}/edit/'.$news_inserted_id."/";
                $db->querys("UPDATE ".$sys_tables['news_parsing']." SET status = 2, id_news = ? WHERE id = ?",$news_inserted_id,$id);
                
                //переносим фотографии
                $db->querys("INSERT INTO ".$sys_tables['news_photos']." (id_parent,name) (SELECT '".$news_inserted_id."' AS id_parent,name FROM ".$sys_tables['news_parsing_photos']." WHERE id_parent = ?)",$id);
                //если фотки есть, устанавливаем main_photo
                if(!empty($db->insert_id)){
                    $db->querys("UPDATE ".$sys_tables[$content_type]." SET id_main_photo = ? WHERE id = ?",$db->insert_id,$news_inserted_id);
                }
                
                $ajax_result['ids'] = array($id);
                break;
            //не переносим в новости
            case $ajax_mode && $action == 'reject':
                $id = (!empty($this_page->page_parameters[3])?$this_page->page_parameters[3]:"");
                if(empty($id)){
                    $ajax_result['ok'] = false;
                    break;
                }
                $ajax_result['ok'] = $db->querys("UPDATE ".$sys_tables['news_parsing']." SET status = 3 WHERE id = ?",$id);
                $ajax_result['ids'] = array($id);
                break;
            //список статей
            default:
                
                //список источников
                $sources_list = $db->fetchall("SELECT id,title FROM ".$sys_tables['news_sources']);
                Response::SetArray('sources',$sources_list);
                
                Response::SetArray('statuses',array(1=>'новое',2=>'в новостях',3=>'отклоненные',4=>'в архиве') );
                
                $where = [];
                
                if(!empty($filters['title'])) $where[] = $sys_tables['news_parsing'].".title LIKE '%".$filters['title']."%'";
                if(!empty($filters['date'])) $where[] = "DATE_FORMAT(".$sys_tables['news_parsing'].".`creation_datetime`, '%d.%m.%Y %H:%i:%s') LIKE '".$db->real_escape_string($filters['date'])."%'";
                if(!empty($filters['source_date'])) $where[] = "DATE_FORMAT(".$sys_tables['news_parsing'].".`source_date_parsed`, '%d.%m.%Y %H:%i:%s') LIKE '".$db->real_escape_string($filters['source_date'])."%'";
                if(!empty($filters['status'])) $where[] = "".$sys_tables['news_parsing'].".status = ".$filters['status'];
                else $where[] = "".$sys_tables['news_parsing'].".status < 4";
                if(!empty($filters['source'])) $where[] = "".$sys_tables['news_parsing'].".id_source = ".$filters['source'];
                
                $list = $db->fetchall("SELECT ".$sys_tables['news_parsing'].".*,
                                              DATE_FORMAT(".$sys_tables['news_parsing'].".creation_datetime,'%d.%m.%Y %H:%i:%s') AS date_in_formatted,
                                              IF(".$sys_tables['news_parsing'].".source_date_parsed NOT LIKE '000%',DATE_FORMAT(".$sys_tables['news_parsing'].".source_date_parsed,'%d.%m.%Y %H:%i:%s'),'') AS source_date_formatted,
                                              IF(".$sys_tables['news_parsing'].".source_date_parsed NOT LIKE '000% AND ',TIMESTAMPDIFF(MINUTE,source_date_parsed,creation_datetime) mod 60,0) AS delay_minutes,
                                              IF(".$sys_tables['news_parsing'].".source_date_parsed NOT LIKE '000%',TIMESTAMPDIFF(HOUR,source_date_parsed,creation_datetime),0) AS delay_hours,
                                              ((TIMESTAMPDIFF(MINUTE,source_date_parsed,creation_datetime) mod 60) != 0 OR TIMESTAMPDIFF(HOUR,source_date_parsed,creation_datetime) !=0) AS delay,
                                              CASE 
                                                WHEN ".$sys_tables['news_parsing'].".status = 1 THEN 'новая'
                                                WHEN ".$sys_tables['news_parsing'].".status = 2 THEN 'в новостях'
                                                WHEN ".$sys_tables['news_parsing'].".status = 3 THEN 'отклонена'
                                              END AS status_title,
                                              ".$sys_tables['news_parsing_photos'].".`name` as `photo`, LEFT (".$sys_tables['news_parsing_photos'].".`name`,2) as `subfolder`,
                                              ph.amount AS photos_amount,
                                              ".$sys_tables['news_sources'].".title AS source_title,
                                              ".$sys_tables['news_sources'].".url AS source_url
                                       FROM ".$sys_tables['news_parsing']."
                                       LEFT JOIN ".$sys_tables['news_parsing_photos']." ON ".$sys_tables['news_parsing'].".id_main_photo = ".$sys_tables['news_parsing_photos'].".id
                                       LEFT JOIN (SELECT id_parent,COUNT(*) AS amount FROM ".$sys_tables['news_parsing_photos']." GROUP BY id_parent) ph ON ph.id_parent = ".$sys_tables['news_parsing'].".id
                                       LEFT JOIN ".$sys_tables['news_sources']." ON ".$sys_tables['news_sources'].".id = ".$sys_tables['news_parsing'].".id_source
                                       ".(empty($where)?"":" WHERE ".implode(" AND ",$where)." ")."
                                       ORDER BY source_date_parsed DESC");
                Response::SetString('photo_folder',Config::$values['img_folders']['news']);
                Response::SetArray('list',$list);
                $module_template = "admin.from_sources.list.html";
        }
        break;
    default:                         
        $module_template = 'admin.list.html';
        // формирование спискоф для фильтров
        if(!empty($sys_tables[$content_type . '_regions'])){
            $regions = $db->fetchall("SELECT id, title FROM ".$sys_tables[$content_type . '_regions']." ORDER BY position");
            Response::SetArray('regions',$regions);
        }
        $categories = $db->fetchall("SELECT id, title FROM ".$sys_tables[$content_type . '_categories']." ORDER BY position");
        Response::SetArray('categories',$categories);
        Response::SetArray('statuses',$mapping[$content_type]['status']['values']);
        // формирование списка
        $conditions = [];
        if(!empty($filters)){
            if(!empty($filters['title'])) $conditions[] = $sys_tables[$content_type].".`title` LIKE '%".$db->real_escape_string($filters['title'])."%'";
            if(!empty($filters['date'])) $conditions[] = "(".$sys_tables[$content_type].".`datetime` LIKE '".$db->real_escape_string($filters['date'])."%' OR DATE_FORMAT(".$sys_tables[$content_type].".`datetime`, '%d.%m.%Y %H:%i:%s') LIKE '".$db->real_escape_string($filters['date'])."%')";
            if(!empty($filters['status'])) $conditions[] = "".$sys_tables[$content_type].".`status` = ".$db->real_escape_string($filters['status']);
            if(!empty($filters['published'])) $conditions[] = "".$sys_tables[$content_type].".`published` = ".$db->real_escape_string($filters['published']);
            if(!empty($filters['region'])) $conditions[] = "".$sys_tables[$content_type].".`id_region` = ".$db->real_escape_string($filters['region']);
            if(!empty($filters['category'])) $conditions[] = "".$sys_tables[$content_type].".`id_category` = ".$db->real_escape_string($filters['category']);
        }
        if(!empty($conditions)) $condition = implode(' AND ',$conditions);
        else $condition = '';
        // создаем пагинатор для списка
        $paginator = new Paginator($sys_tables[$content_type], 30, $condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = [];
        foreach($get_parameters as $gk=>$gv){
            if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/content/' . $content_type                           // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if($paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
            exit(0);
        }

        $news = new Content($content_type);
        $list = $news->getList(30,$paginator->getFromString($page),false,false,!empty($condition) ? $condition:false,false,  true);
        // определение главной фотки для новости
        $news_photo_folder = Config::$values['img_folders']['news'];
        foreach($list as $key=>$value){
            $photo = Photos::getMainPhoto($content_type, $value['id']);
            if(!empty($photo)) {
                $list[$key]['photo'] = $news_photo_folder.'/sm/'.$photo['subfolder']."/".$photo['name'];
            }
        }
        // формирование списка
        Response::SetArray('list', $list);
        if($paginator->pages_count>1){
            Response::SetArray('paginator', $paginator->Get($page));
        }
}
// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk.'='.$gv;
Response::SetString('get_string', implode('&',$get_parameters));
//Название тип контента
$content_title = 
            $content_type == 'news' ? 'Новости' : 
                ( $content_type == 'articles' ? 'Статьи' : 
                    ( $content_type == 'longread' ? 'Лонгриды' : 
                        ( $content_type == 'bsntv' ? 'БСН-ТВ' : 
                            ( $content_type == 'doverie' ? 'Доверие потребителя' : 
                                ( $content_type == 'blog' ? 'Блог' : '' ) ) ) ) ) ;
Response::SetString('content_title', $content_title);
$content_title_prepositional = 
            $content_type == 'news' ? 'новость' : 
                ( $content_type == 'articles' ? 'статью' : 
                    ( $content_type == 'longread' ? 'лонгрид' : 
                        ( $content_type == 'bsntv' ? 'видео' : 
                            ( $content_type == 'doverie' ? 'статью' : 
                                ( $content_type == 'blog' ? 'статью' : '' ) ) ) ) );
Response::SetString('content_title_prepositional', $content_title_prepositional);
?>