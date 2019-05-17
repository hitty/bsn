<?php
require_once('includes/class.paginator.php');
$this_page->manageMetadata(array( 'title'=>'Недвижимость' ) ) ;

// определяем запрошенный экшн
$action = empty( $this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];

// собираем GET-параметры
$get_parameters = array();
$filters = array();
$filters['id'] = Request::GetInteger('f_id',METHOD_GET);
//фильтр для агентств
$filters['f_agency'] = Request::GetInteger('f_agency',METHOD_GET);
if(!empty( $filters['f_agency'] ) )  {
    $get_parameters['f_agency'] = $filters['f_agency'];
}
//фильтр для биллинга
$filters['user_id'] = Request::GetInteger('f_user_id',METHOD_GET);
if(!empty( $filters['user_id'] ) )  {
   $get_parameters['f_user_id'] = $filters['user_id'];
}
$filters['date_start'] = Request::GetString('f_date_start',METHOD_GET);
if(!empty( $filters['date_start'] ) )  {
   $get_parameters['date_start'] = $filters['date_start'];
}
$filters['date_end'] = Request::GetString('f_date_end',METHOD_GET);
if(!empty( $filters['date_end'] ) )  {
   $get_parameters['date_end'] = $filters['date_end'];
}
//фильтр по типам недвижимости для статистики личного кабинета
$filters['estate_type'] = Request::GetInteger('estate_type',METHOD_GET);
if(!empty( $filters['estate_type'] ) )  {
   $get_parameters['estate_type'] = $filters['estate_type'];
}
///фильтры для статистики финансов
if( $action == 'cabinet_stats' || $action == 'finances_stats'){
    //фильтры по дате
    $filters['date_start'] = Request::GetString('f_date_start',METHOD_GET);
    if(!empty( $filters['date_start'] ) )  {
        unset( $get_parameters['date_start']);
        $get_parameters['f_date_start'] = $filters['date_start'];
    }
    $filters['date_end'] = Request::GetString('f_date_end',METHOD_GET);
    if(!empty( $filters['date_end'] ) )  {
       unset( $get_parameters['date_end']);
       $get_parameters['f_date_end'] = $filters['date_end'];
    }
    //фильтр по цели операции
    $filters['estate_type'] = Request::GetString('f_estate_type',METHOD_GET);
    if(!empty( $filters['estate_type'] ) )  {
       $get_parameters['f_estate_type'] = $filters['estate_type'];
    }
    //фильтр по услугам
    $filters['service_type'] = Request::GetString('f_service_type',METHOD_GET);
    if(!empty( $filters['service_type'] ) )  {
       $get_parameters['f_service_type'] = $filters['service_type'];
    }
    //фильтр по ID пользователя
    $filters['user_id'] = Request::GetString('f_userID',METHOD_GET);
    if(!empty( $filters['user_id'] ) )  {
       $get_parameters['f_userID'] = $filters['user_id'];
    }
    //фильтр по типу платежа
    $filters['income_type'] = Request::GetString('f_income_type',METHOD_GET);
    if(!empty( $filters['income_type'] ) ) {
        $get_parameters['f_income_type'] = $filters['income_type'];
    }
    //флаг, что нажата кнопка выбора даты
    $button_pressed = Request::GetString('button_pressed',METHOD_GET);
    if(!empty( $button_pressed ) )  $get_parameters['button_pressed'] = $button_pressed;
    Response::SetString('button_pressed',$button_pressed);
}
///
$page = Request::GetInteger('page',METHOD_GET);
if(empty( $page ) )  $page = 1;
else $get_parameters['page'] = $page;
    
// обработка общих action-ов 
switch( $action){
    case 'billing':
        //определение типа агентства для статистики
        if(!empty( $this_page->page_parameters[2]) && $this_page->page_parameters[2]==2){
            $adv_agency = 2;
            Response::SetString('agency_type','usual') ;
        } else {
            $adv_agency = 1;
            Response::SetString('agency_type','advertising') ;
        }
        $this_page->manageMetadata(array( 'title'=>'Биллинг рекламных агентств' ) ) ;
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js';
        //получение списка рекламных агентств
        $users = $db->fetchall("SELECT DISTINCT(bsn_id_user) as id FROM ".$sys_tables['billing']." WHERE adv_agency = ".$adv_agency);
        foreach( $users as $k=>$user) $users_array[] = $user['id'];
        $agencies = $db->fetchall("SELECT ".$sys_tables['users'].".id, ".$sys_tables['agencies'].".title
                                FROM ".$sys_tables['users']."
                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                WHERE ".$sys_tables['users'].".id IN (".implode(",",$users_array).")
        ");
        Response::Setarray( 'agencies',$agencies);
        
        $post_parameters = Request::GetParameters(METHOD_POST);
        // если была отправка формы - выводим данные 
        if(!empty( $post_parameters['submit']) && !empty( $filters['user_id'] ) ) {
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //передача данных в шаблон
            $date_start = $post_parameters['date_start'];
            $date_end = $post_parameters['date_end'];
            $info['date_start'] = $date_start;
            $info['date_end'] = $date_end;
            $stats = $db->fetchall("
                    SELECT normal, promo, premium, elite, live_rent, vip, a.month_date FROM 
                    (
                      SELECT 
                        COUNT(IF(status=2,'normal',NULL ) )  as normal,
                        COUNT(IF(status=3,'promo',NULL ) )  as promo,
                        COUNT(IF(status=4,'premium',NULL ) )  as premium,
                        COUNT(IF(status=5,'elite',NULL ) )  as elite,
                        COUNT(IF(status=99,'live_rent',NULL ) )  as live_rent,
                        COUNT(IF(status=6,'vip',NULL ) )  as vip,
                        DATE_FORMAT(`date`,'%d.%m.%Y') as month_date
                      FROM ".$sys_tables['billing']."
                      WHERE
                          `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                          `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `bsn_id_user` = ".$get_parameters['f_user_id']."
                      GROUP BY `date`
                      ORDER BY date DESC
                    )  a
                    ");
                           
            Response::Setarray( 'stats',$stats); // информация об объекте    
            Response::Setarray( 'info',$info); // информация об объекте    
        }
        $module_template = 'admin.billing.html'; 
        break;   

    case 'varcount':
        $this_page->manageMetadata(array( 'title'=>'Количество вариантов в БД' ) ) ;
        //читаем фильтр по типу недвижимости
        $filters['estatetype'] = Request::GetInteger('f_estatetype',METHOD_GET);
        if(!empty( $filters['estatetype'] ) )  {
            //если выбран тип недвижимости, читаем список агенств
            $get_parameters['f_estatetype'] = $filters['estatetype'];
            switch( $get_parameters['f_estatetype']){
                case 1: $estate='live';break;
                case 2: $estate='build';break;
                case 3: $estate='commercial';break;
                case 4: $estate='country';break;
                case 5: $estate='all';break;
            }
            if( $estate=='all'){
                $where = array();
                foreach(array( 'live','build','country','commercial') as $item){
                    $where[]  = "( SELECT 
                                    id_user,
                                     IF(".$sys_tables['agencies'].".activity&2,'РА',
                                        IF( ".$sys_tables[$item].".info_source=1,'личный кабинет',
                                            IF( ".$sys_tables[$item].".info_source=2,'BN XML',
                                                IF( ".$sys_tables[$item].".info_source=3,'EIP XML',
                                                    IF( ".$sys_tables[$item].".info_source=4,'Недвижимость города',
                                                        IF( ".$sys_tables[$item].".info_source=5,'BN TXT',
                                                            IF( ".$sys_tables[$item].".info_source=6,'Пригород.Су',' не определено')
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                     ) as agency_type,
                                    SUM(rent=1) AS rent_count,
                                    SUM(rent=2) AS sell_count,
                                    DATEDIFF(NOW(),MIN(date_in ) )  AS old,
                                    DATEDIFF(NOW(),MAX(date_change ) )  AS new,
                                    id_agency,
                                    title,
                                    ".$sys_tables[$item].".info_source,
                                    (SELECT CASE id_agency
                                        WHEN 0 THEN ''
                                        WHEN 1 THEN ''
                                        ELSE title
                                    END)  AS title_sort
                                  FROM ".$sys_tables[$item]." 
                                  LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id=id_user
                                  LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency=".$sys_tables['agencies'].".id
                                  WHERE ".$sys_tables[$item].".published=1 AND (id_agency IS NOT NULL)
                                  GROUP BY id_agency, info_source ORDER BY title_sort )"; 
                }
                 $sql = "SELECT id_user, agency_type, SUM(rent_count) as rent_count, SUM(sell_count) as sell_count, MIN(old) as old, MAX(new) as new, id_agency, title, info_source, title_sort 
                         FROM ( ".implode(" UNION ",$where)." ) as a 
                         GROUP BY id_agency, info_source ORDER BY title_sort";                   
            }  else {
                //выбираем агентства, у которых есть объекты в этом типе
                $sql="SELECT 
                        id_user,
                         IF(".$sys_tables['agencies'].".activity&2,'РА',
                            IF( ".$sys_tables[$estate].".info_source=1,'личный кабинет',
                                IF( ".$sys_tables[$estate].".info_source=2,'BN XML',
                                    IF( ".$sys_tables[$estate].".info_source=3,'EIP XML',
                                        IF( ".$sys_tables[$estate].".info_source=4,'Недвижимость города',
                                            IF( ".$sys_tables[$estate].".info_source=5,'BN TXT',
                                                IF( ".$sys_tables[$estate].".info_source=6,'Пригород.Су',' не определено')
                                            )
                                        )
                                    )
                                )
                            )
                         ) as agency_type,
                        SUM(rent=1) AS rent_count,
                        SUM(rent=2) AS sell_count,
                        DATEDIFF(NOW(),MIN(date_in ) )  AS old,
                        DATEDIFF(NOW(),MAX(date_change ) )  AS new,
                        id_agency,
                        title,
                        (SELECT CASE id_agency
                            WHEN 0 THEN ''
                            WHEN 1 THEN ''
                            ELSE title
                        END)  AS title_sort
                      FROM ".$sys_tables[$estate]." 
                      LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id=id_user
                      LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency=".$sys_tables['agencies'].".id
                      WHERE ".$sys_tables[$estate].".published=1 AND (id_agency IS NOT NULL)
                      GROUP BY id_agency, info_source ORDER BY title_sort";
            }
            $list=$db->fetchall( $sql);
            $list_count=count( $list);
            
            //инициализируем 'всего'
            $list[$list_count]['title']='Всего';
            $list[$list_count]['rent_count']=0;
            $list[$list_count]['sell_count']=0;
            $list[$list_count]['deals_total']=0;
            $list[$list_count]['old']=0;
            $list[$list_count]['new']=99999;
            
            //объединяем id_agency=0 и id_agency=1
            if ( $list[0]['id_agency']==0||$list[0]['id_agency']==1){
                $list[0]['old']=max( $list[0]['old'],$list[1]['old']);
                $list[0]['new']=min( $list[0]['new'],$list[1]['new']);
                $list[0]['rent_count']+=$list[1]['rent_count'];
                $list[0]['sell_count']+=$list[1]['sell_count'];
                $list[0]['id_agency']=0;
                $list[0]['title']='Частные заявки';
                unset( $list[1]);
                $i=0;
                //вычисляем deals_total
                if (empty( $list[0]['title'] ) )   $list[0]['title']='Частные заявки';
                $list[0]['deals_total']=$list[0]['rent_count']+$list[0]['sell_count'];
                
                //от какого номера будем считать дальше. 2 - так как [1] удален
                $floor=2;
                
                $list[$list_count]['old']=max( $list[$list_count]['old'],$list[0]['old']);
                $list[$list_count]['new']=min( $list[$list_count]['new'],$list[0]['new']);
                $list[$list_count]['rent_count']+=$list[0]['rent_count'];
                $list[$list_count]['sell_count']+=$list[0]['sell_count'];
                $list[$list_count]['deals_total']+=$list[0]['deals_total'];
            }
            else{
                //если частных лиц нет, то начинаем с начала
                $floor=0;
            }
            
            //заполняем агентства 
            for( $i=$floor;$i<$list_count;$i++){
                if (empty( $list[$i]['title'] ) )   $list[$i]['title']='Частные заявки';
                $list[$i]['deals_total']=$list[$i]['rent_count']+$list[$i]['sell_count'];
                //считаем 'всего'
                $list[$list_count]['old']=max( $list[$list_count]['old'],$list[$i]['old']);
                $list[$list_count]['new']=min( $list[$list_count]['new'],$list[$i]['new']);
                $list[$list_count]['rent_count']+=$list[$i]['rent_count'];
                $list[$list_count]['sell_count']+=$list[$i]['sell_count'];
                $list[$list_count]['deals_total']+=$list[$i]['rent_count']+$list[$i]['sell_count'];
            }
            $list[$list_count]['id_agency']=0;
            
            Response::Setarray( 'list',$list);
        } else{
            //если не задан тип недвижимости, общий список 
            $sql="SELECT 
                    (SELECT COUNT(*) 
                     FROM ".$sys_tables['live']."
                     LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id=id_user
                     LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency=".$sys_tables['agencies'].".id
                     WHERE ".$sys_tables['live'].".published=1 AND (id_agency IS NOT NULL ) )  AS live,
                     (SELECT COUNT(*) 
                     FROM ".$sys_tables['build']." 
                     LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id=id_user
                     LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency=".$sys_tables['agencies'].".id
                     WHERE ".$sys_tables['build'].".published=1 AND (id_agency IS NOT NULL ) )  AS build,
                    (SELECT COUNT(*) 
                     FROM ".$sys_tables['commercial']." 
                     LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id=id_user
                     LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency=".$sys_tables['agencies'].".id
                     WHERE ".$sys_tables['commercial'].".published=1 AND (id_agency IS NOT NULL ) )  AS commercial,
                    (SELECT COUNT(*) 
                     FROM ".$sys_tables['country']." 
                     LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id=id_user
                     LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency=".$sys_tables['agencies'].".id
                     WHERE ".$sys_tables['country'].".published=1 AND (id_agency IS NOT NULL ) )  AS country,
                    (SELECT live+commercial+country+build) AS total";
            $list=$db->fetchall( $sql);
            $list=$list[0];
            $list_gen['live']['title']='Жилая недвижимость';
            $list_gen['live']['count']=$list['live'];
            $list_gen['build']['title']='Новостройки';
            $list_gen['build']['count']=$list['build'];
            $list_gen['commercial']['title']='Коммерческая недвижимость';
            $list_gen['commercial']['count']=$list['commercial'];
            $list_gen['country']['title']='Загородная недвижимость';
            $list_gen['country']['count']=$list['country'];
            $list_gen['total']['title']='Всего';
            $list_gen['total']['count']=$list['total'];
            unset( $list);
            Response::Setarray( 'list_gen',$list_gen);
        }            
        $module_template = 'admin.varcount.html';
        break;
    ///////////////////////////////////////////////////////////////////
    // Рассылки
    ///////////////////////////////////////////////////////////////////
    case 'newsletters':
        $action = empty( $this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
        switch( true ){
            ///////////////////////////////////////////////////////////////////
            // детальная статистика
            ///////////////////////////////////////////////////////////////////
            case !empty( $action ) && in_array( $action, array( 'open', 'send' ) ) :
                $id = empty( $this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
                if( empty( $id ) ) Host::RedirectLevelUp();
                $where = array(
                    'id_campaign = ' . $id,
                    'status = ' . ( $action == 'open' ? 2 : 1 )
                ); 
                $where = implode( " AND ", $where );
                
                $paginator = new Paginator( $sys_tables['newsletters'], 50, $where );
                $paginator->Links( '/admin/service/stats/newsletters/' . $action . '/' . $id , $page, $get_parameters );
                
                $list = $db->fetchall(" SELECT *, DATE_FORMAT(`datetime`, '%d.%m %H:%i:%s') as normal_date 
                                        FROM " . $sys_tables['newsletters'] . " 
                                        WHERE " . $where . "
                                        LIMIT ".$paginator->getLimitString( $page )
                );
                Response::SetArray( 'list', $list );
                
                Response::SetString( 'action', $action );
                $module_template = 'admin.newsletters.list.status.html';
                break;
            ///////////////////////////////////////////////////////////////////
            // Список
            ///////////////////////////////////////////////////////////////////
            default:
                
                $paginator = new Paginator( $sys_tables['newsletters_campaigns'], 50, false, false, false );
                $paginator->Links( '/admin/service/stats/newsletters', $page, $get_parameters );
                
                $list = $db->fetchall("
                    SELECT 
                        " . $sys_tables['newsletters_campaigns'] . ".*,
                        DATE_FORMAT(" . $sys_tables['newsletters_campaigns'] . ".`datetime`, '%d.%m %H:%i') as normal_date, 
                        ( SELECT COUNT(*) FROM " . $sys_tables['newsletters'] . " WHERE id_campaign = " . $sys_tables['newsletters_campaigns'] . ".id AND status = 1 ) as total_send,
                        ( SELECT COUNT(*) FROM " . $sys_tables['newsletters'] . " WHERE id_campaign = " . $sys_tables['newsletters_campaigns'] . ".id AND status = 2 ) as total_open
                    FROM " . $sys_tables['newsletters_campaigns'] . "
                    GROUP BY " . $sys_tables['newsletters_campaigns'] . ".id
                    ORDER BY " . $sys_tables['newsletters_campaigns'] . ".id DESC
                    LIMIT ".$paginator->getLimitString( $page ) 
                );
                Response::SetArray( 'list', $list );
                
                $module_template = 'admin.newsletters.list.html';
                break;
        }
        
        break;
    case 'phones':
        $fields = array(
                 array( 'string', 'Дата')
                ,array( 'number', 'Жилая')
                ,array( 'number', 'Новостройки')
                ,array( 'number', 'Коммерческая')
                ,array( 'number', 'Загородная')
                ,array( 'number', 'ЖК')
                ,array( 'number', 'КП')
                ,array( 'number', 'БЦ')
                ,array( 'number', 'Суммарно')
         );
          //получение списка агентств
            $users = $db->fetchall("SELECT DISTINCT(id_parent) as id FROM ".$sys_tables['phone_clicks_full']);
            foreach( $users as $k=>$user) $users_array[] = $user['id'];
            $agencies = $db->fetchall("
                                    SELECT  'Суммарно' AS title, 'summary' AS `id`,1 AS order_type
                                    UNION ALL
                                    SELECT  'Агентства' AS title, 'agency' AS `id`,2 AS order_type
                                    UNION ALL
                                    SELECT  'Частники' AS title, 'users' AS `id`,3 AS order_type
                                    UNION ALL  
                                    SELECT 
                                    IF(".$sys_tables['users'].".id_agency<2,'Частник', ".$sys_tables['agencies'].".title) as title,
                                    IF(".$sys_tables['users'].".id_agency<2,1, ".$sys_tables['users'].".id) as id,
                                    IF(".$sys_tables['users'].".id_agency=1,3,4) AS order_type
                                    FROM ".$sys_tables['users']."
                                    LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                    WHERE ".$sys_tables['users'].".id IN (".implode(",",$users_array).") AND title!=''
                                    GROUP BY id
                                    ORDER BY order_type,title 
            ");
         if  (!$ajax_mode){
             $this_page->manageMetadata(array( 'title'=>'Клики по телефонам' ) ) ;
             $module_template = 'admin.click.phones.html';
             $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
             $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
             $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js'; 
             $GLOBALS['js_set'][] = '/js/google.chart.api.js';
            $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js';
            $GLOBALS['js_set'][] = '/modules/stats/ajax_actions.js';
            
            Response::Setarray( 'agencies',$agencies);
            Response::Setarray( 'data_titles',$fields); 
         }
         $get_parameters = Request::GetParameters(METHOD_GET);
         if(!empty( $get_parameters['submit']) || $ajax_mode){
             Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
             if ( $get_parameters['f_user_id'] == 'summary'){ // все суммарно
                $where = ""; 
            } else if( $get_parameters['f_user_id'] == 'agency'){ // все агентства
                $agencies_ids = array();
                foreach( $agencies as $k=>$item){
                    if(!in_array( $item['id'],array( 'summary','agency','users' ) )  && $item['id']!=1) $agencies_ids[] = $item['id'];
                }
                $where = ' AND id_parent IN ('.implode(',',$agencies_ids).')';    
            } else if( $get_parameters['f_user_id'] == 'users' || $get_parameters['f_user_id']==1) { //статистика частников
                $agencies_ids = array();
                foreach( $agencies as $k=>$item){
                    if(!in_array( $item['id'],array( 'summary','agency','users' ) )  && $item['id']!=1) $agencies_ids[] = $item['id'];
                }
                $where = ' AND id_parent NOT IN ('.implode(',',$agencies_ids).')';
            } else $where = ' AND id_parent = '.Convert::ToInt( $get_parameters['f_user_id']);  //статистика агентств
            
            $date_start = new DateTime( $get_parameters['date_start']);;
            $date_end = new DateTime( $get_parameters['date_end']);
            $interval = $date_start->diff( $date_end);
            
            if( $date_start > $date_end && $date_start>0 && $date_end>0){
                $date_start = new DateTime( $filters['date_end']);
                $date_end = new DateTime( $filters['date_start']);
                $get_parameters['date_start'] = $filters['date_end'];
                $get_parameters['date_end'] = $filters['date_start'];
            }
            
            $index_date = $date_start; $count = 0;
            $stats = array();
            
            $lq = array();
            do{
                $types = array(
                    array( 'live'       =>1 ),
                    array( 'build'      =>2 ),
                    array( 'commercial' =>3 ), 
                    array( 'country'    =>4 ), 
                    array( 'housing_estates' =>5 ), 
                    array( 'business_centers' =>6 ), 
                    array( 'cottages'    =>7 ) 
                ) ;
                $formated_index_date = $index_date->format('Y-m-d') ;
                //определение последовательности запросов (даты могут быть пустые);
                if( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE   type = 1 AND `date` = '".$formated_index_date."'  $where " ) ) ;
                elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type = 2 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[1]); array_unshift( $types,array( 'build'=>2 ) ) ;
                }elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type = 3 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[2]); array_unshift( $types,array( 'commercial'=>3 ) ) ;
                }elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type = 4 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[3]); array_unshift( $types,array( 'country'=>4 ) ) ;
                }elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type = 5 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[4]); array_unshift( $types,array( 'housing_estates'=>5 ) ) ;
                }elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type =6 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[5]); array_unshift( $types,array( 'business_centers'=>6 ) ) ;
                }elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type = 7 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[6]); array_unshift( $types,array( 'cottages'=>7 ) ) ;
                }
                $k = array();
                $k[0] = array_keys( $types[0]);  $e[0] = $types[0][( $k[0][0])];
                $k[1] = array_keys( $types[1]);  $e[1] = $types[1][( $k[1][0])];
                $k[2] = array_keys( $types[2]);  $e[2] = $types[2][( $k[2][0])];
                $k[3] = array_keys( $types[3]);  $e[3] = $types[3][( $k[3][0])];
                $k[4] = array_keys( $types[4]);  $e[4] = $types[4][( $k[4][0])];
                $k[5] = array_keys( $types[5]);  $e[5] = $types[5][( $k[5][0])];
                $k[6] = array_keys( $types[6]);  $e[6] = $types[6][( $k[6][0])];
                $list = $db->fetch("
                        SELECT IFNULL(a.".$k[0][0]."_amount,0) as ".$k[0][0]."_amount, 
                               IFNULL(aa.".$k[0][0]."_amount,0) as ".$k[0][0]."_amount_clicked, 
                               IFNULL(b.".$k[1][0]."_amount,0) as ".$k[1][0]."_amount, 
                               IFNULL(bb.".$k[1][0]."_amount,0) as ".$k[1][0]."_amount_clicked, 
                               IFNULL(c.".$k[2][0]."_amount,0) as ".$k[2][0]."_amount, 
                               IFNULL(cc.".$k[2][0]."_amount,0) as ".$k[2][0]."_amount_clicked, 
                               IFNULL(d.".$k[3][0]."_amount,0) as ".$k[3][0]."_amount, 
                               IFNULL(dd.".$k[3][0]."_amount,0) as ".$k[3][0]."_amount_clicked, 
                               IFNULL(e.".$k[4][0]."_amount,0) as ".$k[4][0]."_amount, 
                               IFNULL(ee.".$k[4][0]."_amount,0) as ".$k[4][0]."_amount_clicked, 
                               IFNULL(f.".$k[5][0]."_amount,0) as ".$k[5][0]."_amount, 
                               IFNULL(ff.".$k[5][0]."_amount,0) as ".$k[5][0]."_amount_clicked, 
                               IFNULL(g.".$k[6][0]."_amount,0) as ".$k[6][0]."_amount, 
                               IFNULL(gg.".$k[6][0]."_amount,0) as ".$k[6][0]."_amount_clicked, 
                               a.date,
                               (
                                    IFNULL(a.".$k[0][0]."_amount,0) +
                                    IFNULL(b.".$k[1][0]."_amount,0) +
                                    IFNULL(c.".$k[2][0]."_amount,0) +
                                    IFNULL(d.".$k[3][0]."_amount,0 ) + 
                                    IFNULL(e.".$k[4][0]."_amount,0 ) + 
                                    IFNULL(f.".$k[5][0]."_amount,0 ) + 
                                    IFNULL(g.".$k[6][0]."_amount,0 )
                               )  AS summary  FROM 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[0][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."'  AND
                              type = ".$e[0]." $where
                          GROUP BY `date`
                        ) a
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[0][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."'  AND
                              status = 2 AND
                              type = ".$e[0]." $where
                          GROUP BY `date`
                        ) aa ON a.date = aa.date 
                        LEFT JOIN                     
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[1][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[1]."  $where
                          GROUP BY `date`
                         ) b ON a.date = b.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[1][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."'  AND
                              status = 2 AND
                              type = ".$e[1]." $where
                          GROUP BY `date`
                        ) bb ON a.date = bb.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[2][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE  
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[2]." $where
                          GROUP BY `date`
                         ) c ON a.date = c.date           
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[2][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."'  AND
                              status = 2 AND
                              type = ".$e[2]." $where
                          GROUP BY `date`
                        ) cc ON a.date = cc.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[3][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[3]." $where
                          GROUP BY `date`
                         ) d ON a.date = d.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[3][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              status = 2 AND
                              type = ".$e[3]." $where
                          GROUP BY `date`
                         ) dd ON a.date = dd.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[4][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[4]." $where
                          GROUP BY `date`
                         ) e ON a.date = e.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[4][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              status = 2 AND
                              type = ".$e[4]." $where
                          GROUP BY `date`
                         ) ee ON a.date = ee.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[5][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[5]." $where
                          GROUP BY `date`
                         ) f ON a.date = f.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[5][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              status = 2 AND
                              type = ".$e[5]." $where
                          GROUP BY `date`
                         ) ff ON a.date = ff.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[6][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[6]." $where
                          GROUP BY `date`
                         ) g ON a.date = g.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[6][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              status = 2 AND
                              type = ".$e[6]." $where
                          GROUP BY `date`
                         ) gg ON a.date = gg.date 
                         
                         
                ");       
                $lq[] = $db->last_query;
                if(!empty( $list ) )  $stats[] = $list;
                $index_date->add(new DateInterval('P1D' ) ) ; 
            } while( $date_end>=$index_date);
            $stats[] = $db->fetch("
                        SELECT IFNULL(a.".$k[0][0]."_amount,0) as ".$k[0][0]."_amount, 
                               IFNULL(aa.".$k[0][0]."_amount,0) as ".$k[0][0]."_amount_clicked, 
                               IFNULL(b.".$k[1][0]."_amount,0) as ".$k[1][0]."_amount, 
                               IFNULL(bb.".$k[1][0]."_amount,0) as ".$k[1][0]."_amount_clicked, 
                               IFNULL(c.".$k[2][0]."_amount,0) as ".$k[2][0]."_amount, 
                               IFNULL(cc.".$k[2][0]."_amount,0) as ".$k[2][0]."_amount_clicked, 
                               IFNULL(d.".$k[3][0]."_amount,0) as ".$k[3][0]."_amount, 
                               IFNULL(dd.".$k[3][0]."_amount,0) as ".$k[3][0]."_amount_clicked, 
                               IFNULL(e.".$k[4][0]."_amount,0) as ".$k[4][0]."_amount, 
                               IFNULL(ee.".$k[4][0]."_amount,0) as ".$k[4][0]."_amount_clicked, 
                               IFNULL(f.".$k[5][0]."_amount,0) as ".$k[5][0]."_amount, 
                               IFNULL(ff.".$k[5][0]."_amount,0) as ".$k[5][0]."_amount_clicked, 
                               IFNULL(g.".$k[6][0]."_amount,0) as ".$k[6][0]."_amount, 
                               IFNULL(gg.".$k[6][0]."_amount,0) as ".$k[6][0]."_amount_clicked, 
                               a.date,
                               (
                                    IFNULL(a.".$k[0][0]."_amount,0) +
                                    IFNULL(b.".$k[1][0]."_amount,0) +
                                    IFNULL(c.".$k[2][0]."_amount,0) +
                                    IFNULL(d.".$k[3][0]."_amount,0 ) + 
                                    IFNULL(e.".$k[4][0]."_amount,0 ) + 
                                    IFNULL(f.".$k[5][0]."_amount,0 ) + 
                                    IFNULL(g.".$k[6][0]."_amount,0 )
                               )  AS summary FROM 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[0][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE
                              type = ".$e[0]." $where 
                        ) a
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[0][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE
                              status = 2 AND
                              type = ".$e[0]." $where
                        ) aa ON a.date = aa.date 
                        LEFT JOIN                     
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[1][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE
                              type = ".$e[1]."  $where 
                         ) b ON a.date = b.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[1][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE
                              status = 2 AND
                              type = ".$e[1]." $where 
                        ) bb ON a.date = bb.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[2][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              type = ".$e[2]." $where
                         ) c ON a.date = c.date           
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[2][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE
                              status = 2 AND
                              type = ".$e[2]." $where
                        ) cc ON a.date = cc.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[3][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              type = ".$e[3]." $where 
                         ) d ON a.date = d.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[3][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              status = 2 AND
                              type = ".$e[3]." $where
                         ) dd ON a.date = dd.date 


                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[4][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              type = ".$e[4]." $where
                          GROUP BY `date`
                         ) e ON a.date = e.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[4][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              status = 2 AND
                              type = ".$e[4]." $where
                          GROUP BY `date`
                         ) ee ON a.date = ee.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[5][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              type = ".$e[5]." $where
                          GROUP BY `date`
                         ) f ON a.date = f.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[5][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              status = 2 AND
                              type = ".$e[5]." $where
                          GROUP BY `date`
                         ) ff ON a.date = ff.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[6][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              type = ".$e[6]." $where
                          GROUP BY `date`
                         ) g ON a.date = g.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              IFNULL(COUNT(*),0) as ".$k[6][0]."_amount, 
                              'сегодня' as date
                          FROM ".$sys_tables['phone_clicks_day']."
                          WHERE 
                              status = 2 AND
                              type = ".$e[6]." $where
                          GROUP BY `date`
                         ) gg ON a.date = gg.date
                         
                         
                         
                ");     
            $lq[] = $db->last_query;    
            Response::Setarray( 'stats',$stats); // информация об объекте
            $date_start = $get_parameters['date_start'];
            $date_end = $get_parameters['date_end']; 
            $info['date_start'] = $date_start;
            $info['date_end'] = $date_end;
            if (!$ajax_mode) Response::Setarray( 'info',$info); // информация об объекте
            else {
                $module_template = 'admin.phones.html';
                $graphic_colors = array( '#3366CC','#DC3912','#FF9900','#109618','#CC0099','#EE0099','#AA0099','#990099');       // Цвета графиков
                $data = array();
                if( $stats) {
                    foreach( $stats as $ind=>$item) {   // Преобразование массива
                        $arr = array();
                        $arr[] = array( 'date',Convert::ToString( $item['date'] ) ) ;
                        $arr[] = array( 'live_amount',Convert::ToInt( $item['live_amount'] ) ) ;
                        $arr[] = array( 'build_amount',Convert::ToInt( $item['build_amount'] ) ) ;
                        $arr[] = array( 'commercial_amount',Convert::ToInt( $item['commercial_amount'] ) ) ;
                        $arr[] = array( 'country_amount',Convert::ToInt( $item['country_amount'] ) ) ;  
                        $arr[] = array( 'housing_estates_amount',Convert::ToInt( $item['housing_estates_amount'] ) ) ;  
                        $arr[] = array( 'business_centers_amount',Convert::ToInt( $item['business_centers_amount'] ) ) ;  
                        $arr[] = array( 'cottages_amount',Convert::ToInt( $item['cottages_amount'] ) ) ;  
                        $arr[] = array( 'summary',Convert::ToInt( $item['summary'] ) ) ;   
                        $data[] = $arr;
                    }
                }
                $ajax_result = array(
                    'ok' => true,
                    'data' => $data,
                    'count' => count( $data),
                    'height'=>300,
                    'width'=>725,
                    'fields' => $fields,
                    'colors' => $graphic_colors
                );
                $ajax_result['lq-1'] = implode( " ||||| ", $lq );
            }
         }
    break;
    case 'Old_phones':
        $this_page->manageMetadata(array( 'title'=>'Клики по телефонам' ) ) ;
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js';
        //получение списка агентств
        $users = $db->fetchall("SELECT DISTINCT(id_parent) as id FROM ".$sys_tables['phone_clicks_full']);
        foreach( $users as $k=>$user) $users_array[] = $user['id'];
        $agencies = $db->fetchall("
                                SELECT  'Суммарно' AS title, -1 AS `id`,1 AS order_type
                                UNION ALL
                                SELECT  'Агентства' AS title, -2 AS `id`,2 AS order_type
                                UNION ALL 
                                SELECT 
                                IF(".$sys_tables['users'].".id_agency<2,'Частник', ".$sys_tables['agencies'].".title) as title,
                                IF(".$sys_tables['users'].".id_agency<2,1, ".$sys_tables['users'].".id) as id,
                                IF(".$sys_tables['users'].".id_agency=1,3,4) AS order_type
                                FROM ".$sys_tables['users']."
                                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                                WHERE ".$sys_tables['users'].".id IN (".implode(",",$users_array).") AND title!=''
                                GROUP BY id
                                ORDER BY order_type,title 
        ");
        Response::Setarray( 'agencies',$agencies);
        
        $post_parameters = Request::GetParameters(METHOD_POST);
        // если была отправка формы - выводим данные 
        if(!empty( $filters['date_start']) && !empty( $filters['date_end']) && !empty( $filters['user_id'] ) ) {
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //передача данных в шаблон
            if ( $filters['user_id']==-1){ // все суммарно
                $where = ""; 
            } else if ( $filters['user_id']==-2){ // все агентства
                $agencies_ids = array();
                foreach( $agencies as $k=>$item){
                    if( $item['id']!=1) $agencies_ids[] = $item['id'];
                }
                $where = ' AND id_parent IN ('.implode(',',$agencies_ids).')';       
            } else if( $filters['user_id']==1) { //статистика частников
                $agencies_ids = array();
                foreach( $agencies as $k=>$item){
                    if( $item['id']!=1) $agencies_ids[] = $item['id'];
                }
                $where = ' AND id_parent NOT IN ('.implode(',',$agencies_ids).')';
            } else $where = ' AND id_parent = '.$filters['user_id'];  //статистика агентств
            
            $date_start = new DateTime( $filters['date_start']);;
            $date_end = new DateTime( $filters['date_end']);
            $interval = $date_start->diff( $date_end);
            
            if( $date_start > $date_end && $date_start>0 && $date_end>0){
                $date_start = new DateTime( $filters['date_end']);
                $date_end = new DateTime( $filters['date_start']);
                $get_parameters['date_start'] = $filters['date_end'];
                $get_parameters['date_end'] = $filters['date_start'];
            }
            
            $index_date = $date_start; $count = 0;
            $stats = array();
            
            do{
                $types = array(array( 'live'=>1),array( 'build'=>2),array( 'commercial'=>3),array( 'country'=>4 ) ) ;
                $formated_index_date = $index_date->format('Y-m-d') ;
                //определение последовательности запросов (даты могут быть пустые);
                if( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE   type = 1 AND `date` = '".$formated_index_date."'  $where " ) ) ;
                elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type = 2 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[1]); array_unshift( $types,array( 'build'=>2 ) ) ;
                }elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type = 3 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[2]); array_unshift( $types,array( 'commercial'=>3 ) ) ;
                }elseif( $db->fetch("SELECT * FROM ".$sys_tables['phone_clicks_full']." WHERE type = 4 AND `date` = '".$formated_index_date."'  $where " ) ) {
                    unset( $types[3]); array_unshift( $types,array( 'country'=>4 ) ) ;
                }
                $k = array();
                $k[0] = array_keys( $types[0]);  $e[0] = $types[0][( $k[0][0])];
                $k[1] = array_keys( $types[1]);  $e[1] = $types[1][( $k[1][0])];
                $k[2] = array_keys( $types[2]);  $e[2] = $types[2][( $k[2][0])];
                $k[3] = array_keys( $types[3]);  $e[3] = $types[3][( $k[3][0])];
               
                $list = $db->fetch("
                        SELECT IFNULL(a.".$k[0][0]."_amount,0) as ".$k[0][0]."_amount, 
                               IFNULL(aa.".$k[0][0]."_amount,0) as ".$k[0][0]."_amount_clicked, 
                               IFNULL(b.".$k[1][0]."_amount,0) as ".$k[1][0]."_amount, 
                               IFNULL(bb.".$k[1][0]."_amount,0) as ".$k[1][0]."_amount_clicked, 
                               IFNULL(c.".$k[2][0]."_amount,0) as ".$k[2][0]."_amount, 
                               IFNULL(cc.".$k[2][0]."_amount,0) as ".$k[2][0]."_amount_clicked, 
                               IFNULL(d.".$k[3][0]."_amount,0) as ".$k[3][0]."_amount, 
                               IFNULL(dd.".$k[3][0]."_amount,0) as ".$k[3][0]."_amount_clicked, 
                               a.date FROM 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[0][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."'  AND
                              type = ".$e[0]." $where
                          GROUP BY `date`
                        ) a
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[0][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."'  AND
                              status = 2 AND
                              type = ".$e[0]." $where
                          GROUP BY `date`
                        ) aa ON a.date = aa.date 
                        LEFT JOIN                     
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[1][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[1]."  $where
                          GROUP BY `date`
                         ) b ON a.date = b.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[1][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."'  AND
                              status = 2 AND
                              type = ".$e[1]." $where
                          GROUP BY `date`
                        ) bb ON a.date = bb.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[2][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE  
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[2]." $where
                          GROUP BY `date`
                         ) c ON a.date = c.date           
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[2][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE
                              `date` = '".$formated_index_date."'  AND
                              status = 2 AND
                              type = ".$e[2]." $where
                          GROUP BY `date`
                        ) cc ON a.date = cc.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[3][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              type = ".$e[3]." $where
                          GROUP BY `date`
                         ) d ON a.date = d.date 
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0 ) )  as ".$k[3][0]."_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['phone_clicks_full']."
                          WHERE 
                              `date` = '".$formated_index_date."' AND
                              status = 2 AND
                              type = ".$e[3]." $where
                          GROUP BY `date`
                         ) dd ON a.date = dd.date 
                ");       
                if(!empty( $list ) )  $stats[] = $list;
                $index_date->add(new DateInterval('P1D' ) ) ;
            } while( $date_end>=$index_date);
            Response::Setarray( 'stats',$stats); // информация об объекте    
        }
        $module_template = 'admin.phones.html'; 
        break;
    ////////////////////////////////////////////////////////////
    //// Количество объектов в избранном
    ////////////////////////////////////////////////////////////
    case 'favorites':
        $this_page->manageMetadata(array( 'title'=>'Количество объектов в избранном' ) ) ;
        $sql = "SELECT DISTINCT CASE";
        foreach(Config::$values['object_types'] AS $key=>$obj) $sql .= " WHEN type_object=".$obj['key']." THEN '".$obj['name']."'";    
        $sql .= " END AS obj_type,
            (SELECT COUNT(*) FROM ".$sys_tables['favorites']." f WHERE f.type_object = cf.type_object) AS amount
        FROM ".$sys_tables['favorites']." cf ORDER BY type_object";
        $list = $db->fetchall( $sql);
        
        $sql = "SELECT COUNT(DISTINCT id_user) AS PeopleAmount FROM ".$sys_tables['favorites'];
        $people_amount = $db->fetch( $sql);
        $sum = 0;
        // Подсчет общего количества
        foreach ( $list AS $obj=>$val) $sum += Convert::ToInt( $val['amount']);
        $list_sum = array(); 
        $list_sum[] = array( 'obj_type'=>"Всего",'amount'=>$sum);
        $list_sum[] = array( 'obj_type'=>"Пользователи",'amount'=>$people_amount['PeopleAmount']);
        Response::Setarray( 'favorites_list',$list);
        Response::Setarray( 'summary_favorites_list',$list_sum);  
        $module_template = 'admin.favorites.html';
        break;
    ////////////////////////////////////////////////////////////
    //// Статистика личного кабинета
    ////////////////////////////////////////////////////////////
    case 'cabinet_stats':
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js'; 
        $GLOBALS['js_set'][] = '/js/google.chart.api.js';
        $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js';
        $GLOBALS['js_set'][] = '/modules/stats/ajax_actions.js';
        $get_parameters = Request::GetParameters(METHOD_GET);
        //читаем строчки за вчера
        
        $fields = array(
                array( 'string','Дата')
                ,array( 'number','Обычные')
                ,array( 'number','Обычные платные')
                ,array( 'number','Промо')
                ,array( 'number','Премиум')
                ,array( 'number','Суммарно')
            );
        Response::Setarray( 'data_titles',$fields); 
        if (!$ajax_mode){
            $module_template = "admin.cabinet.stats.html";
        }
        else {
            $sql = "SELECT *, DATE_FORMAT(".Config::$values['sys_tables']['cabinet_stats'].".date,'%d.%m.%Y') AS date_formatted FROM ".Config::$values['sys_tables']['cabinet_stats'];
            $where = array();
            //если какого-то фильтра не хватает, выходим
            if( $ajax_mode && (empty( $get_parameters['f_estate_type'])||empty( $get_parameters['date_start'])||empty( $get_parameters['date_end'] ) ) )
                die();
            //формируем данные для фильтра по времени
            $date_start = new DateTime( $get_parameters['date_start']);
            $date_end = new DateTime( $get_parameters['date_end']);
            if( $date_start > $date_end && $date_start>0 && $date_end>0){
                $date_start = new DateTime( $get_parameters['date_end']);
                $date_end = new DateTime( $get_parameters['date_start']);
            }
            $formatted_start = $date_start->format('Y-m-d');
            $formatted_end = $date_end->format('Y-m-d');
            $where = " WHERE ".Config::$values['sys_tables']['cabinet_stats'].".estate_type =".$get_parameters['f_estate_type']."
                        AND ".Config::$values['sys_tables']['cabinet_stats'].".`date` >= '".$formatted_start."'
                        AND ".Config::$values['sys_tables']['cabinet_stats'].".`date` <= '".$formatted_end."'";
            $stats_list = $db->fetchall( $sql.$where." ORDER BY `date` ASC");
            //преобразуем массив для отображения
            if (!empty( $stats_list ) ) {
                $info = array();
                foreach( $stats_list as $item){
                    $info[$item['date']]['total'] = 0;
                    $info[$item['date']]['rent'] = 0;
                    $info[$item['date']]['sell'] = 0;
                    $info[$item['date']]['promo'] = 0;
                    $info[$item['date']]['premium'] = 0;
                    $info[$item['date']]['common_payed'] = 0;
                }
                foreach( $stats_list as $item){
                    $field_title = "";
                    if( $item['deal_type'] == 1){
                        $info[$item['date']]['rent'] += $item['amount'];
                        $field_title = "_rent";
                    }else{
                        $info[$item['date']]['sell'] += $item['amount'];
                        $field_title = "_sell";
                    }
                    switch( $item['status']){
                        case 2: $field_title = "common".$field_title;break;
                        case 3: $field_title = "promo".$field_title;$info[$item['date']]['promo'] += $item['amount'];break;
                        case 4: $field_title = "premium".$field_title;$info[$item['date']]['premium'] += $item['amount'];break;
                        case 5: $field_title = "common_payed".$field_title;$info[$item['date']]['common_payed'] += $item['amount'];break;
                    }
                    $info[$item['date']][$field_title] = (!empty( $item['amount'] ) ) ?$item['amount']:0;
                    $info[$item['date']]['total'] += $item['amount'];
                    $info[$item['date']]['date'] = $item['date_formatted'];
                    $info[$item['date']]['common'] = $info[$item['date']]['total'] - $info[$item['date']]['promo'] - $info[$item['date']]['premium'] - $info[$item['date']]['common_payed'];
                    $info[$item['date']]['common_pay'] = $info[$item['date']]['common_payed']*150;
                    $info[$item['date']]['promo_pay'] = $info[$item['date']]['promo']*450;
                    $info[$item['date']]['premium_pay'] = $info[$item['date']]['premium']*900;
                    $info[$item['date']]['total_pay'] = $info[$item['date']]['common_pay'] + $info[$item['date']]['promo_pay'] + $info[$item['date']]['premium_pay'];
                }
                //подсчитываем общее количество
                foreach( $info as $key=>$item){
                    if( $key!='total')
                    foreach( $item as $field_key=>$field_value){
                        if(empty( $info['total'][$field_key] ) )  $info['total'][$field_key] = 0;
                        $info['total'][$field_key] += $field_value;
                    }
                }
                $info['total']['date'] = 'Всего';
                $graphic_colors = array( '#3366CC','#DC3912','#FF9900','#109618','#CC0099');       // Цвета графиков
                $data = array();
                if( $info){
                    foreach( $info as $ind=>$item) {   // Преобразование массива
                        if( $ind!='total'){
                            $arr = array();
                            $arr[] = array( 'date',Convert::ToString( $item['date'] ) ) ;
                            $arr[] = array( 'common_amount',Convert::ToInt( $item['common'] ) ) ;
                            $arr[] = array( 'common_payed',Convert::ToInt( $item['common_payed'] ) ) ;
                            $arr[] = array( 'promo_amount',Convert::ToInt( $item['promo'] ) ) ;
                            $arr[] = array( 'premium_amount',Convert::ToInt( $item['premium'] ) ) ;
                            $arr[] = array( 'total',Convert::ToInt( $item['total'] ) ) ;
                            $data[] = $arr;
                        }
                    }
                }
                $ajax_result = array(
                    'ok' => true,
                    'data' => $data,
                    'count' => count( $data),
                    'height'=>300,
                    'width'=>725,
                    'fields' => $fields,
                    'colors' => $graphic_colors
                );
                Response::Setarray( 'info',$info);
            }else{
                $ajax_result = array(
                    'ok' => false,
                );
            }
            $module_template = 'admin.stats.cabinet.html';
        }
        break;
    case 'finances_stats':
        
        $where = array( $sys_tables['users_finances'] . ".obj_type != 'call'");
        $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
        $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
        $GLOBALS['js_set'][] = '/modules/spec_offers/datepick_actions.js'; 
        $GLOBALS['js_set'][] = '/js/google.chart.api.js';
        $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js';
        $GLOBALS['js_set'][] = '/modules/stats/ajax_actions.js';
        ///применяем фильтры
        //фильтр по цели операции
        if(!empty( $filters['service_type'] ) )  $where[] = $sys_tables['users_fnances_transactions'].".obj_type = '".$filters['service_type']."'";
        if(!empty( $filters['estate_type'] ) )  $where[] = $sys_tables['users_finances'].".obj_type = '".$filters['estate_type']."'";
        //фильтр по id пользователя
        //теперь можно передавать последовательности и исключать значения
        if(!empty( $filters['user_id'] ) ) {
            $filters['user_id'] = preg_replace('/[^0-9\,\-]/si','',$filters['user_id']);
            if(preg_match('/\,/si',$filters['user_id'] ) ) {
                $filters['user_id'] = explode(',',$filters['user_id']);
                $include_ids = array_filter( $filters['user_id'],function( $v){
                    return ( $v>0);
                });
                $exclude_ids = array_filter( $filters['user_id'],function( $v){
                    return ( $v<0);
                });
                if(!empty( $include_ids ) )  $where[] = $sys_tables['users_finances'].".id_user IN (".implode(',',$include_ids).")";
                if(!empty( $exclude_ids ) )  $where[] = $sys_tables['users_finances'].".id_user NOT IN (".implode(',',array_map('abs',$exclude_ids ) ) .")";
                
            }elseif( $filters['user_id'] < 0) $where[] = $sys_tables['users_finances'].".id_user != ".abs( $filters['user_id']);
            else $where[] = $sys_tables['users_finances'].".id_user = ".$filters['user_id'];
        }
        //фильтр по дате
        if(!empty( $filters['date_start'] ) )  $where[] = $sys_tables['users_finances'].".`datetime`>='".explode(' ',date_create_from_format("d.m.Y",$filters['date_start'])->format('Y-m-d' ) ) [0]."'";
        if(!empty( $filters['date_end'] ) )  $where[] = $sys_tables['users_finances'].".`datetime`<='".explode(' ',date_create_from_format("d.m.Y",$filters['date_end'])->format('Y-m-d')."9")[0]."'";//лишний символ - чтобы включить барьер
        
        //фильтр по типу зачисления (если выбран фильтр, то автоматически выбираем фильтр "Пополнение баланса")
        if(!empty( $filters['income_type'] ) ) {
            $get_parameters['service_type'] = 'balance';
            $where[] = $sys_tables['users_finances'].".paygate = ".(( $filters['income_type'] == 'robokassa')?"2":"1");
        }
        if(!empty( $where ) )  $where = " WHERE ".implode(' AND ',$where);
        else $where = "";
        ///
        
        ///создаем пагинатор
        $sql_condition = "FROM ".$sys_tables['users_finances']."
                          LEFT JOIN  ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users_finances'].".id_parent AND obj_type = 'tarif'
                          LEFT JOIN  ".$sys_tables['users_fnances_transactions']." ON ".$sys_tables['users_fnances_transactions'].".obj_type = ".$sys_tables['users_finances'].".obj_type
                          ".$where."";
        $paginator = new Paginator(false, 30, false,"SELECT COUNT(*) as items_count ".$sql_condition);
        // get-параметры для ссылок пагинатора
        $get_in_paginator = array();
        foreach( $get_parameters as $gk=>$gv){
            if( $gk!='page') $get_in_paginator[] = $gk.'='.$gv;
        }
        // ссылка пагинатора
        $paginator->link_prefix = '/admin/service/stats/finances_stats'                           // модуль
                                  ."/?"                                         // конечный слеш и начало GET-строки
                                  .implode('&',$get_in_paginator)           // GET-строка
                                  .(empty( $get_in_paginator)?"":'&')."page="; // параметр для номера страницы
        if( $paginator->pages_count>0 && $paginator->pages_count<$page){
            Header('Location: '.Host::getWebPath( $paginator->link_prefix.$paginator->pages_count ) ) ;
            exit(0);
        }
        ///
        
        $list = $db->fetchall("SELECT ".$sys_tables['users_finances'].".*,
                                      IF(YEAR(".$sys_tables['users_finances'].".datetime) < Year(CURDATE( ) ) ,DATE_FORMAT(".$sys_tables['users_finances'].".datetime,'%e %M %Y'),DATE_FORMAT(".$sys_tables['users_finances'].".datetime,'%e %M, %k:%i' ) )  as normal_date, 
                                      ".$sys_tables['users_fnances_transactions'].".title as service_title,
                                      IF(".$sys_tables['tarifs'].".title!='',IF(admin_agency.id IS NOT NULL,".$sys_tables['tarifs_agencies'].".title,".$sys_tables['tarifs'].".title), 
                                        IF(".$sys_tables['users_finances'].".obj_type = 'balance','', 
                                          IF(".$sys_tables['users_finances'].".obj_type = 'call','',
                                            IF(".$sys_tables['users_finances'].".obj_type = 'application',CONCAT('ID ',id_parent),
                                              IF(".$sys_tables['users_finances'].".obj_type = 'mortgage_application',CONCAT('ID ',id_parent),CONCAT_WS('/', ".$sys_tables['users_finances'].".obj_type, id_parent ) ) 
                                              )
                                            )
                                          )
                                         ) as object_title,
                                         IF(estate_type='live',(SELECT rent FROM ".$sys_tables['live']." WHERE id = ".$sys_tables['users_finances'].".id_parent),
                                         IF(estate_type='build',(SELECT rent FROM ".$sys_tables['build']." WHERE id = ".$sys_tables['users_finances'].".id_parent),
                                         IF(estate_type='country',(SELECT rent FROM ".$sys_tables['country']." WHERE id = ".$sys_tables['users_finances'].".id_parent),
                                         IF(estate_type='commercial',(SELECT rent FROM ".$sys_tables['commercial']." WHERE id = ".$sys_tables['users_finances'].".id_parent),0 ) )  ) )  as deal_type,
                                          admin_agency.title as agency_title,
                                          IF(".$sys_tables['users_finances'].".obj_type = 'context_banner',
                                             (SELECT id_campaign FROM ".$sys_tables['context_advertisements']." WHERE id = ".$sys_tables['users_finances'].".id_parent),0) AS context_campaign_id,
                                          IF(".$sys_tables['users'].".name!='',CONCAT(".$sys_tables['users'].".lastname, ' ',".$sys_tables['users'].".name),'') as user_name
                               FROM ".$sys_tables['users_finances']."
                               LEFT JOIN ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users_finances'].".id_parent AND obj_type = 'tarif'
                               LEFT JOIN ".$sys_tables['tarifs_agencies']." ON ".$sys_tables['tarifs_agencies'].".id = ".$sys_tables['users_finances'].".id_parent AND obj_type = 'tarif'
                               LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users_finances'].".id_user
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['users_finances'].".id_user
                               LEFT JOIN ".$sys_tables['agencies']." admin_agency ON admin_agency.id = ".$sys_tables['users'].".id_agency
                               LEFT JOIN ".$sys_tables['users_fnances_transactions']." ON ".$sys_tables['users_fnances_transactions'].".obj_type = ".$sys_tables['users_finances'].".obj_type
                               ".$where."
                               GROUP BY ".$sys_tables['users_finances'].".id
                               ORDER BY ".$sys_tables['users_finances'].".id DESC
                               LIMIT ".$paginator->getLimitString( $page)."
        ");
        $total = $db->fetch("SELECT COUNT(*) AS total, 
                                    SUM(expenditure) AS total_exp, 
                                    SUM(income) AS total_inc
                             FROM ".$sys_tables['users_finances']." 
                             LEFT JOIN  ".$sys_tables['tarifs']." ON ".$sys_tables['tarifs'].".id = ".$sys_tables['users_finances'].".id_parent AND obj_type = 'tarif'
                             LEFT JOIN  ".$sys_tables['users_fnances_transactions']." ON ".$sys_tables['users_fnances_transactions'].".obj_type = ".$sys_tables['users_finances'].".obj_type
                             ".$where);
        foreach( $list as $k=>$item) {
            //если услуга не определилась, значит это не объект, а что-то еще
            if(empty( $item['service_title'] ) ) {
                //контекстный баннер
                if (preg_match('/context_banner/',$item['object_title'] ) ) {
                    $list[$k]['object_title'] = "ID ".explode('/',$item['object_title'])[1];
                    $list[$k]['service_title'] = 'БСН Таргет';
                }
            }
        }
        Response::Setarray( 'total',$total);
        Response::Setarray( 'list',$list);
        
        if( $paginator->pages_count>1){
            Response::Setarray( 'paginator', $paginator->Get( $page ) ) ;
        }
        
        $module_template = 'admin.stats.finances.html';
        break;
    default:
        $module_template = 'admin.stats.html';
        break;
}

// запоминаем для шаблона GET - параметры
Response::Setarray( 'get_array', $get_parameters);
foreach( $get_parameters as $gk=>$gv) $get_parameters[$gk] = $gk.'='.$gv;
Response::SetString('get_string', implode('&',$get_parameters ) ) ;
?>