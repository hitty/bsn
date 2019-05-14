<?php
$GLOBALS['js_set'][] = '/modules/tgb/ajax_actions.js';

require_once('includes/class.paginator.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
require_once('includes/class.tgb.php');
Tgb::Init();
// мэппинги модуля
$mapping = include(dirname(__FILE__).'/conf_mapping.php');

// добавление title
$this_page->manageMetadata(array('title'=>'ТГБ'));

// собираем GET-параметры
$get_parameters = [];
$filters = [];
$filters['title'] = Request::GetString('f_title',METHOD_GET);
$filters['campaign'] = $db->real_escape_string(Request::GetInteger('f_campaign',METHOD_GET));
$filters['manager'] = $db->real_escape_string(Request::GetInteger('f_manager',METHOD_GET));
$filters['credit_clicks'] = Request::GetInteger('f_credit_clicks',METHOD_GET);
$filters['status'] = Request::GetString('f_status',METHOD_GET);
if(!empty($filters['title'])) {
    $filters['title'] = urldecode($filters['title']);
    $get_parameters['f_title'] = $filters['title'];
}
if(!empty($filters['campaign'])) $get_parameters['f_campaign'] = $filters['campaign']; else $filters['campaign'] = false;
if(!empty($filters['manager'])) $get_parameters['f_manager'] = $filters['manager']; else $filters['manager'] = false;
if(!empty($filters['credit_clicks'])) $get_parameters['f_credit_clicks'] = $filters['credit_clicks']; else $filters['credit_clicks'] = false;
if(!empty($filters['status'])) $get_parameters['f_status'] = $filters['status']; else $filters['status'] = 'active';

$page = Request::GetInteger('page',METHOD_GET);
if(empty($page)) $page = 1;
else $get_parameters['page'] = $page;
// определяем запрошенный экшн
$action = empty($this_page->page_parameters[1]) ? "" : $this_page->page_parameters[1];
// обработка action-ов
switch($action){
	/*********************************************\
    |*  перенос в архив/восстановление кампаний  *|
    \*********************************************/		
    case 'banner_credit':
        if(!empty($ajax_mode)){
            $id_banner = Request::GetInteger('id_banner',METHOD_POST);    
            $date_start = Request::GetString('date_start',METHOD_POST);    
            $date_end = Request::GetString('date_end',METHOD_POST);    
            $limit = Request::GetInteger('limit',METHOD_POST);    
            $day_limit = Request::GetInteger('day_limit',METHOD_POST);    
            //Определение менеджера
            if(!empty($auth->email)){
                $manager = $db->fetch("SELECT * FROM ".$sys_tables['managers']." WHERE email = ? AND bsn_manager = ?",$auth->email,1);
                $db->query("INSERT INTO ".$sys_tables['tgb_banners_credits']." SET id_banner=?, id_manager=?, date_start=?, date_end=?, `limit`=?, day_limit=?",
                            $id_banner, $manager['id'], date( 'Y-m-d H:i:s', strtotime($date_start)),  date( 'Y-m-d H:i:s', strtotime($date_end)), $limit, $day_limit
                );
                $ajax_result['lq_1'] = '';
                $db->query("UPDATE ".$sys_tables['managers']." SET naydidom_credit_limit = ? WHERE email = ? AND bsn_manager = ?", $manager['naydidom_credit_limit']-$limit,$auth->email,1);
                $ajax_result['lq_2'] = '';
            }
        }
        break;
	case 'restore':
	case 'archive':
		$id =  empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		//значение чекбокса
		$value = Request::GetString('value',METHOD_POST);
		$status = $action=='restore'?1:2;
		if($id>0){
			$res = $db->query("UPDATE ".$sys_tables['tgb_campaigns']." SET `published` = ? WHERE id=?", $status, $id);
			$results['setStatus'] = ($res && $db->affected_rows) ? $id : -1;
			if($ajax_mode){
				$ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
				break;
			}
		} else $ajax_result = false;
		break;
	/*************************\
    |*  Работа со статитикой *|
    \*************************/
    case 'stats':
        // переопределяем экшн
		$module_template = 'admin.stats.html';
		$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
		$GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
		$GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
		$GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
		//получение данных по объекту из базы
		$info = $db->fetch("SELECT 
								`id`,
                                `title`,
								`price`,
                                `in_estate_section`,
                                `id_context`,
                                `context_date_start`,
                                DATEDIFF(`date_end`,`date_start`) as `date_diff`,
								IF(img_link='',
									  CONCAT('/','".Config::$values['img_folders']['tgb']."','/',`img_src`),
								`img_link`) as photo
							FROM ".$sys_tables['tgb_banners']."
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
            
            //определение выбранного временного интервала
            $datetime1 = new DateTime($date_start);
            $datetime2 = new DateTime($date_end);
            $date_now = new DateTime();
            $today_included = ($date_now->diff($datetime2)->d == 0 || $date_now < $datetime2);
            //дата с которой плюсуется статистика тгб в разделе
            //дата с которой плюсуется статистика тгб в разделе
            if(!empty($info['context_date_start'])){
                $datetime_context = new DateTime($info['context_date_start']);
                $date_start_context = date_format($datetime_context,"d.m.Y");
            } 
            $interval = $datetime1->diff($datetime2);
            $info['interval'] = $interval->format('%a')+1;
            $info['price_on_period'] =  !empty($post_parameters['price_on_period']) ? $post_parameters['price_on_period'] : '';
            $info['wanted_price'] =  !empty($post_parameters['wanted_price']) ? $post_parameters['wanted_price'] : '';
            
			$stats = $db->fetchall("
					SELECT 
                        IFNULL(a.show_amount,0) as show_amount, 
                        IFNULL(b.click_amount,0) as click_bsn_amount, 
                        IFNULL(b.click_amount_in_estate,0) as click_amount_in_estate,
                        ".(!empty($info['id_context'])?"IFNULL(k.click_amount,0) as click_bsn_context_amount,":"")."
                        IFNULL(c.click_amount,0) as click_naydidom_amount, 
                        IFNULL(d.click_amount,0) as click_naydidom_top_amount, 
                        IFNULL(e.click_amount,0) as click_naydidom_center_amount, 
                        IFNULL(f.click_amount,0) as click_naydidom_right_amount, 
                        IFNULL(fb.click_amount,0) as click_facebook_amount, 
                        IFNULL(ba.click_amount,0) as click_bezagenta_amount, 
                        IFNULL(ga.click_amount,0) as click_ga_amount, 
                        IFNULL(yd.click_amount,0) as click_yd_amount, 
                        IFNULL(pu.click_amount,0) as click_popunder_amount, 
                        IFNULL(g.banners_credits_clicks_total,0) as banners_credits_clicks_total,
                        a.date 
                    FROM 
					(
                        (
					      SELECT 
						      IFNULL(`amount`,0) as show_amount, 
						      DATE_FORMAT(`date`,'%d.%m.%Y') as date
					      FROM ".$sys_tables['tgb_stats_full_shows']."
					      WHERE
						      `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
						      `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id."
					      GROUP BY `date`
					    ) a
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              SUM(IF(in_estate > 0,IFNULL(`amount`,0),0)) as click_amount_in_estate,
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 1
                          GROUP BY `date`
                         ) b ON a.date = b.date
                         
					    LEFT JOIN 
					    (
					      SELECT 
						      SUM(IFNULL(`amount`,0)) as click_amount, 
						      DATE_FORMAT(`date`,'%d.%m.%Y') as date
					      FROM ".$sys_tables['tgb_stats_full_clicks']."
					      WHERE
						      `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
						      `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 2
					      GROUP BY `date`
					     ) c ON a.date = c.date
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `position` = 1 AND `from` = 2
                          GROUP BY `date`
                         ) d ON a.date = d.date                         
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `position` = 2 AND `from` = 2
                          GROUP BY `date`
                         ) e ON a.date = e.date                         
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `position` = 3 AND `from` = 2
                          GROUP BY `date`
                         ) f ON a.date = f.date     
                         LEFT JOIN
                        (
                          SELECT  
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date,
                              SUM(IFNULL(`amount`,0)) as banners_credits_clicks_total,                    
                              SUM(IFNULL(`clicks_amount`,0)) as banners_credits_clicks,                         
                              id_parent
                          FROM ".$sys_tables['tgb_banners_credits_stats']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." 
                          GROUP BY `date`           
                         ) g ON a.date = g.date                                                                                                           
                         ".(!empty($info['id_context'])?"
                         LEFT JOIN 
                         (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['context_stats_click_full']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start_context."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` IN (".$info['id_context'].")
                          GROUP BY `date`
                         ) k ON a.date = k.date":"")."

                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 3
                          GROUP BY `date`
                         ) i ON a.date = i.date
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 4
                          GROUP BY `date`
                         ) fb ON a.date = fb.date
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 8
                          GROUP BY `date`
                         ) ba ON a.date = ba.date
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 6
                          GROUP BY `date`
                         ) ga ON a.date = ga.date
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 7
                          GROUP BY `date`
                         ) yd ON a.date = yd.date
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') AND `id_parent` = ".$id." AND `from` = 5
                          GROUP BY `date`
                         ) pu ON a.date = pu.date
                         
                                             
                    )"
                    .(!empty($today_included) ? 
                      " UNION (
                            SELECT 
                                IFNULL(aa.show_amount,0) as show_amount, 
                                IFNULL(bb.click_amount,0) as click_bsn_amount, 
                                IFNULL(bb.click_amount_in_estate,0) as click_amount_in_estate,
                                ".(!empty($info['id_context'])?"IFNULL(kk.click_amount,0) as click_bsn_context_amount,":"")."
                                IFNULL(cc.click_amount,0) as click_naydidom_amount, 
                                IFNULL(dd.click_amount,0) as click_naydidom_top_amount, 
                                IFNULL(ee.click_amount,0) as click_naydidom_center_amount, 
                                IFNULL(ff.click_amount,0) as click_naydidom_right_amount,
                                IFNULL(fbfb.click_amount,0) as click_facebook_amount, 
                                IFNULL(baba.click_amount,0) as click_bezagenta_amount, 
                                IFNULL(gaga.click_amount,0) as click_ga_amount, 
                                IFNULL(ydyd.click_amount,0) as click_yd_amount, 
                                IFNULL(pupu.click_amount,0) as click_popunder_amount, 
                                IFNULL(gg.banners_credits_clicks_total,0) as banners_credits_clicks_total, 
                                aa.date 
                            FROM 
                            (   SELECT
                                  IFNULL(COUNT(*),0) as show_amount,
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_shows']."
                              WHERE `id_parent` = ".$id."
                            ) aa
                            LEFT JOIN 
                            (
                              SELECT 
                                  IFNULL(COUNT(in_estate = 0),0) as click_amount,
                                  SUM(IF(in_estate > 0,1,0)) AS click_amount_in_estate,
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `from` = 1
                             ) bb ON aa.id_parent = bb.id_parent
                             ".(!empty($info['id_context'])?"
                            LEFT JOIN 
                             (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['context_stats_click_day']."
                              WHERE  `id_parent` IN (".$info['id_context'].")
                             ) kk ON kk.id_parent IN (".$info['id_context'].") AND aa.id_parent = ".$id:"")."                        LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `from` = 2
                             ) cc ON aa.id_parent = cc.id_parent
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `position` = 1  AND `from` = 2
                             ) dd ON aa.id_parent = dd.id_parent
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `position` = 2  AND `from` = 2
                             ) ee ON aa.id_parent = ee.id_parent
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `position` = 3  AND `from` = 2
                             ) ff ON aa.id_parent = ff.id_parent     
                             LEFT JOIN
                            (
                              SELECT  
                                  'сегодня' as date,
                                  day_limit as banners_credits_clicks_total,                    
                                  (SELECT  COUNT(*) as cnt FROM ".$sys_tables['tgb_stats_day_clicks']." WHERE ".$sys_tables['tgb_banners_credits'].".id_banner = ".$id.") as banners_credits_clicks,
                                  id_banner
                              FROM ".$sys_tables['tgb_banners_credits']."
                              WHERE  `id_banner` = ".$id."
                             ) gg ON aa.id_parent = gg.id_banner
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `from` = 3
                             ) ii ON aa.id_parent = ii.id_parent
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `from` = 4
                             ) fbfb ON aa.id_parent = fbfb.id_parent
                             LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `from` = 8
                             ) baba ON aa.id_parent = baba.id_parent
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `from` = 6
                             ) gaga ON aa.id_parent = gaga.id_parent
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `from` = 7
                             ) ydyd ON aa.id_parent = ydyd.id_parent
                            LEFT JOIN
                            (
                              SELECT 
                                  IFNULL(COUNT(*),0) as click_amount, 
                                  'сегодня' as date,
                                  id_parent
                              FROM ".$sys_tables['tgb_stats_day_clicks']."
                              WHERE  `id_parent` = ".$id." AND `from` = 5
                             ) pupu ON aa.id_parent = pupu.id_parent
                             
                        )"
                      : ""
                     )."
                    ");
            Response::SetBoolean('has_context_tgb',!empty($info['id_context']));
            $naydidom_clicks = false;
            $in_estate_clicks = false;
            foreach($stats as $key=>$item){
                if(!empty($item['click_naydidom_amount'])){
                    $naydidom_clicks = true;
                }
                if(!empty($item['click_amount_in_estate'])){
                    $in_estate_clicks = true;
                }
                if($naydidom_clicks && $in_estate_clicks) break;
            }
            Response::SetBoolean('naydidom_clicks',$naydidom_clicks);
            Response::SetBoolean('in_estate_clicks',$in_estate_clicks);
			Response::SetArray('stats',$stats); // статистика объекта	
			// расчет статистики докрутки кликов
            //кол-во зарезервированных кликов найдидом
            $advert_clicks = $db->fetch("SELECT * FROM ".$sys_tables['tgb_banners_credits']." WHERE id_banner = ?",$id);
            Response::SetArray('advert_clicks', $advert_clicks);
            //кол-во кликов за последние 30 дней
            $average_clicks = $db->fetch("SELECT 30*AVG(amount) as sum_amount
                                          FROM ".$sys_tables['tgb_stats_full_clicks']." 
                                          WHERE `from` = ?  AND id_parent = ? AND `date` >= CURDATE() - INTERVAL 30 DAY GROUP BY id_parent",
                                          1, $id); 
            Response::SetArray('average_clicks', $average_clicks);
		}
		Response::SetArray('info',$info); // информация об объекте										
	break;
    
    /*************************\
    |*  Статистика всех ТГБ   *|
    \*************************/
    case 'total_stats':
        $partner = !empty($auth->id_group)&&$auth->id_group==11;
        if (!$partner){
            $fields = array(
                    array('string','Дата')
                    ,array('number','Показы')
                    ,array('number','Переходы BSN')
                    ,array('number','Найдидом Все')
                    ,array('number','Найдидом Выдача')
                    ,array('number','Найдидом Центр')
                    ,array('number','Найдидом Сайдбар')
                    ,array('number','Переходы')
                    ,array('number','Заказано')
                    ,array('number','Откликано')
                    ,array('number','CTR, %')
                    ,array('number','Переходы BSN в разделе')
             );
        } else {
             $fields = array(
                    array('string','Дата')
                    ,array('number','Переходы Найдидом')
             );
        } 
        if  (!$ajax_mode){
            // переопределяем экшн 
            $module_template = 'admin.stats.total.html';
            $id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
            $GLOBALS['css_set'][] = '/js/datepicker/css/ui-lightness/jquery-ui-1.8.16.custom.css';
            $GLOBALS['js_set'][] = '/js/datepicker/js/jquery-ui-1.9.2.custom.min.js';
            $GLOBALS['js_set'][] = '/js/main.js';
            $GLOBALS['js_set'][] = '/modules/tgb/datepick_actions.js';
            $GLOBALS['js_set'][] = '/js/google.chart.api.js';
            $GLOBALS['js_set'][] = '/admin/js/statistics-chart.js'; 
            
            Response::SetArray('data_titles',$fields);
            //получение группы пользователя "Партнер"            
            
        }
        Response::SetBoolean('partner',$partner);
        $get_parameters = Request::GetParameters(METHOD_GET);
        
        
        // если была отправка формы - выводим данные 
        if(!empty($get_parameters['submit']) || $ajax_mode){
            Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
            //передача данных в шаблон
            $date_start = $get_parameters['date_start'];
            $date_end = $get_parameters['date_end'];
            $info['date_start'] = $date_start;
            $info['date_end'] = $date_end;
            
             
            //список БСН Таргет, которые присоединены
            $context_joined = $db->fetchall("SELECT id_context,context_date_start
                                                 FROM ".$sys_tables['tgb_banners']." WHERE id_context > 0
                                                 GROUP BY id");
            if(!empty($context_joined)){
                $context_joined_clicks = [];
                //набираем информацию по датам
                foreach($context_joined as $key=>$item){
                    $dates_sum = $db->fetchall("SELECT `date`,SUM(amount) AS amount
                                                FROM ".$sys_tables['context_stats_click_full']." 
                                                WHERE id_parent IN (".$item['id_context'].") AND 
                                                      `date`>='".$item['context_date_start']."' AND
                                                      `date`>='".date('Y-m-d',strtotime($date_start))."' AND
                                                      `date`<='".date('Y-m-d',strtotime($date_end))."'
                                                GROUP BY `date`",'date');
                    
                    $today_sum = $db->fetch("SELECT COUNT(*) AS amount
                                             FROM ".$sys_tables['context_stats_click_day']." 
                                             WHERE id_parent IN (".$item['id_context'].")");
                    $ajax_result['lq-'.($key+1)] = $db->last_query;
                    foreach($dates_sum as $k=>$i){
                        if(empty($context_joined_clicks[$k])) $context_joined_clicks[$k] = $i['amount'];
                        else $context_joined_clicks[$k] += $i['amount'];
                    }
                    if(empty($context_joined_clicks['сегодня'])) $context_joined_clicks['сегодня'] = $today_sum['amount'];
                    else $context_joined_clicks['сегодня'] += $today_sum['amount'];
                }
                //за сегодня
            }      
            $ids = $db->fetch("SELECT GROUP_CONCAT(id) as ids FROM ".$sys_tables['tgb_banners']." WHERE published = 1 AND enabled = 1 AND credit_clicks = 1")['ids'];
            $stats = $db->fetchall("
                    SELECT 
                        a.date,
                        IFNULL(a.show_amount,0) as show_amount, 
                        IFNULL(b.click_amount,0) as click_bsn_amount, 
                        IFNULL(c.click_amount,0) as click_naydidom_amount, 
                        IFNULL(d.click_amount,0) as click_naydidom_top_amount, 
                        IFNULL(e.click_amount,0) as click_naydidom_center_amount, 
                        IFNULL(f.click_amount,0) as click_naydidom_right_amount, 
                        IFNULL(b.click_amount,0) + IFNULL(c.click_amount,0) as total_amount, 
                        IFNULL(g.banners_credits_clicks_total,0) as banners_credits_clicks_total,
                        IFNULL(g.banners_credits_clicks,0) as banners_credits_clicks,
                        IFNULL(a.show_amount,0)/a.tgb_per_day as show_per_day, 
                        IFNULL(b.click_amount,0)/a.tgb_per_day as click_per_day,
                        IFNULL(fb.click_amount,0) as click_facebook_amount,                         
                        IFNULL(ba.click_amount,0) as click_bezagenta_amount,                         
                        IFNULL(ga.click_amount,0) as click_ga_amount,                         
                        IFNULL(yd.click_amount,0) as click_yd_amount,                         
                        IFNULL(pu.click_amount,0) as click_popunder_amount                         
                    FROM 
                    (
                      (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as show_amount, 
                              COUNT(DISTINCT(id_parent)) as tgb_per_day,
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_shows']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y') 
                          GROUP BY `date`
                        ) a
                        LEFT JOIN 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 1 
                          GROUP BY `date`
                         ) b ON a.date = b.date
                         
                        LEFT JOIN
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 2
                          GROUP BY `date`
                         ) c ON a.date = c.date
                        LEFT JOIN
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `position` = 1
                          GROUP BY `date`
                         ) d ON a.date = d.date
                        LEFT JOIN
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `position` = 2
                          GROUP BY `date`
                         ) e ON a.date = e.date
                        LEFT JOIN
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `position` = 3
                          GROUP BY `date`
                         ) f ON a.date = f.date    
                         LEFT JOIN
                        (
                          SELECT  
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date,
                              SUM(amount) as banners_credits_clicks_total,                    
                              SUM(clicks_amount) as banners_credits_clicks,                         
                              id_parent
                          FROM ".$sys_tables['tgb_banners_credits_stats']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  
                          GROUP BY `date`           
                         ) g ON a.date = g.date  
                         LEFT JOIN                                                                                                                                 
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 3
                          GROUP BY `date`
                         ) i ON a.date = i.date
                         LEFT JOIN                                                                                                                                
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 4
                          GROUP BY `date`
                         ) fb ON a.date = fb.date
                         LEFT JOIN                                                                                                                                
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 8
                          GROUP BY `date`
                         ) ba ON a.date = ba.date
                         LEFT JOIN                                                                                                                                
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 6
                          GROUP BY `date`
                         ) ga ON a.date = ga.date
                         LEFT JOIN                                                                                                                                
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 7
                          GROUP BY `date`
                         ) yd ON a.date = yd.date
                         LEFT JOIN                                                                                                                                
                        (
                          SELECT 
                              SUM(IFNULL(`amount`,0)) as click_amount, 
                              DATE_FORMAT(`date`,'%d.%m.%Y') as date
                          FROM ".$sys_tables['tgb_stats_full_clicks']."
                          WHERE
                              `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                              `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')  AND `from` = 5
                          GROUP BY `date`
                         ) pu ON a.date = pu.date

                    ) UNION (
                        SELECT aa.date, 
                        IFNULL(aa.show_amount,0) as show_amount, 
                        IFNULL(bb.click_amount,0) as click_bsn_amount, 
                        IFNULL(cc.click_amount,0) as click_naydidom_amount, 
                        IFNULL(dd.click_amount,0) as click_naydidom_top_amount, 
                        IFNULL(ee.click_amount,0) as click_naydidom_center_amount, 
                        IFNULL(ff.click_amount,0) as click_naydidom_right_amount,
                        IFNULL(bb.click_amount,0) + IFNULL(cc.click_amount,0) as total_amount,
                        IFNULL(gg.banners_credits_clicks_total,0) as banners_credits_clicks_total,
                        IFNULL(gg.banners_credits_clicks,0) as banners_credits_clicks,
                        IFNULL(aa.show_amount,0)/aa.tgb_per_day as show_per_day, 
                        IFNULL(bb.click_amount,0)/aa.tgb_per_day as click_per_day,
                        IFNULL(fbfb.click_amount,0) as click_facebook_amount,
                        IFNULL(baba.click_amount,0) as click_bezagenta_amount,
                        IFNULL(gaga.click_amount,0) as click_ga_amount,
                        IFNULL(ydyd.click_amount,0) as click_yd_amount,
                        IFNULL(pupu.click_amount,0) as click_popunder_amount
                        FROM 
                       (   SELECT
                              'сегодня' as date, 
                              IFNULL(COUNT(*),0) as show_amount, 
                              COUNT(DISTINCT(id_parent)) as tgb_per_day,
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_shows']."
                        ) aa
                        LEFT JOIN 
                        (
                          SELECT 
                                'сегодня' as date,
                              IFNULL(COUNT(*),0) as click_amount,                              
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `from` = 1
                         ) bb ON aa.date = bb.date
                        LEFT JOIN
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `from` = 2
                         ) cc ON aa.date = cc.date
                        LEFT JOIN
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `position` = 1
                         ) dd ON aa.date = dd.date
                        LEFT JOIN
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `position` = 2
                         ) ee ON aa.date = ee.date
                        LEFT JOIN
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                      
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `position` = 3
                         ) ff ON aa.date = ff.date  
                         LEFT JOIN
                        (
                          SELECT  
                              'сегодня' as date,
                              SUM(day_limit) as banners_credits_clicks_total,                    
                              (SELECT  COUNT(*) as cnt FROM ".$sys_tables['tgb_stats_day_clicks']." WHERE ".$sys_tables['tgb_banners_credits'].".id_banner = ".$sys_tables['tgb_stats_day_clicks'].".id_parent) as banners_credits_clicks,
                              id_banner
                          FROM ".$sys_tables['tgb_banners_credits']."
                          WHERE id_banner IN (".$ids.")
                         ) gg ON aa.date = gg.date  
                         LEFT JOIN                                                                                                                    
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `from` = 3
                         ) ii ON aa.date = ii.date
                         LEFT JOIN                                                                                                                     
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `from` = 4
                         ) fbfb ON aa.date = fbfb.date
                         LEFT JOIN                                                                                                                     
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `from` = 8
                         ) baba ON aa.date = baba.date
                         LEFT JOIN                                                                                                                     
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `from` = 6
                         ) gaga ON aa.date = gaga.date
                         LEFT JOIN                                                                                                                     
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `from` = 7
                         ) ydyd ON aa.date = ydyd.date
                         LEFT JOIN                                                                                                                     
                        (
                          SELECT
                                'сегодня' as date, 
                              IFNULL(COUNT(*),0) as click_amount,                               
                              id_parent
                          FROM ".$sys_tables['tgb_stats_day_clicks']."
                          WHERE `from` = 5
                         ) pupu ON aa.date = pupu.date

                    )
                     
                ");    
               
                if(!empty($context_joined_clicks)){
                    foreach($stats as $key=>$item){
                        if($stats[$key]['date'] == 'сегодня')
                            $stats[$key]['click_bsn_context_amount'] = (empty($context_joined_clicks['сегодня'])?0:$context_joined_clicks['сегодня']);
                        else $stats[$key]['click_bsn_context_amount'] = (empty($context_joined_clicks[date('Y-m-d',strtotime($item['date']))])?0:$context_joined_clicks[date('Y-m-d',strtotime($item['date']))]);
                    }
                }
                Response::SetBoolean('has_context_tgb',!empty($context_joined_clicks));
                Response::SetArray('stats',$stats);
                
                //Подсчет суммарной статистики по ТГБ за период с прогнозом
                //вычисление лимитов - среднее кол-во в день
                $ids_list  = $db->fetchall("
                    SELECT 
                            a.id_parent as id
                    FROM ".$sys_tables['tgb_banners']."
                    LEFT JOIN (
                        SELECT 
                                id_parent
                        FROM 
                                ".$sys_tables['tgb_stats_full_clicks']."
                        WHERE  
                                `date` >= STR_TO_DATE('".$date_start."', '%d.%m.%Y') AND 
                                `date` <= STR_TO_DATE('".$date_end."', '%d.%m.%Y')     
                    ) a ON a.id_parent = ".$sys_tables['tgb_banners'].".id
                    WHERE 
                        ".$sys_tables['tgb_banners'].".enabled = 1 AND 
                        ".$sys_tables['tgb_banners'].".published = 1 AND
                        ".$sys_tables['tgb_banners'].".clicks_limit > 0
                    GROUP BY id
                ");
                
                $ids = [];
                foreach($ids_list as $k=>$item) if(!empty($item['id'])) $ids[] = $item['id'];
               
                //сколько должен заказано кликов для данного промежутка времени 
                // AVG(clicks_limit/DATEDIFF(date_end,date_start) - кол-во кликов в день для данного промежутка времени 
                $limits = $db->fetch("
                    SELECT 
                        AVG(clicks_limit/DATEDIFF(date_end,date_start)) as average_limit_per_day,
                        DATEDIFF( STR_TO_DATE('".$date_end."', '%d.%m.%Y'), STR_TO_DATE('".$date_start."', '%d.%m.%Y')) as `datediff`,
                        ( AVG(clicks_limit/DATEDIFF(date_end,date_start)) ) * ( DATEDIFF( STR_TO_DATE('".$date_end."', '%d.%m.%Y'), STR_TO_DATE('".$date_start."', '%d.%m.%Y')) ) as `limit`
                    FROM ".$sys_tables['tgb_banners']." 
                    WHERE id IN (".implode(",", $ids).")                    
                ");
                Response::SetArray('limit', $limits);
                $limit = (int) count($ids) * $limits['limit'];
                Response::SetInteger('banners_count', (int) count($ids));
                Response::SetInteger('clicks_limit', (int) $limit);
                
            if (!$ajax_mode) Response::SetArray('info',$info); // информация об объекте 
            else {
                $module_template = 'admin.stats.table.html';
                if (!$partner)
                    $graphic_colors = array('#3366CC','#DC3912','#FF9900','#109618','#990099','#3cff00', '#808000', '#800000','#FF0000','#F2BB20','#0011EE');       // Цвета графиков
                else
                    $graphic_colors = array('#FF9900');       // Цвета графиков
                $data = [];
                if($stats) {
                    foreach($stats as $ind=>$item) {   // Преобразование массива
                        $arr = [];
                        foreach($item as $key=>$val){
                            if ($key!='date')
                                $arr[] = array(Convert::ToString($key),Convert::ToInt($val));
                            else
                                $arr[] = array(Convert::ToString($key),Convert::ToString($val));
                        }     
                        $s_amount = Convert::ToInt($item['show_amount']);
                        $arr[11] = $arr[12];
                        if(!empty($s_amount)) $arr[10] = array('ctr',Convert::ToFloat(number_format((Convert::ToInt($item['total_amount'])/Convert::ToInt($item['show_amount']))*100,2)));                 // CTR
                        $data[] = $arr;
                    }
                }
                $ajax_result = array(
                    'ok' => true,
                    'data' => $data,
                    'count' => count($data),
                    'height'=>300,
                    'width'=>725,
                    'fields' => $fields,
                    'colors' => $graphic_colors
                );
            }   
            
        }
        break;        
	/****************************\
    |*  Работа с баннерами ТГБ  *|
    \****************************/		
	case 'banners':
		$action = empty($this_page->page_parameters[2]) ? "" : $this_page->page_parameters[2];
		switch($action){
			case 'add':
			case 'edit':
                $GLOBALS['js_set'][] = '/js/main.js';
                $GLOBALS['js_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.js';
                $GLOBALS['css_set'][] = '/admin/js/datetimepicker/jquery.datetimepicker.css';
				$module_template = 'admin.banners.edit.html';
				$id = empty($this_page->page_parameters[3]) ? 0 : $this_page->page_parameters[3];
				if($action=='add'){
					// создание болванки новой записи
					$info = $db->prepareNewRecord($sys_tables['tgb_banners']);
				} else {
					// получение данных из БД
					$info = $db->fetch("SELECT *, in_estate_section/2 AS in_estate_section
										FROM ".$sys_tables['tgb_banners']." 
										WHERE id=?", $id) ;
                    $info['show_in_estate_section'] = (!empty($info['in_estate_section']) ? 1 : 2);
					//предустановка ссылки на картинки на главной и в шапке
					$cnt = count($_POST);
					Response::SetString('img_link_double', $cnt==0?$info['img_link']:(!empty($_POST['img_link_double'])?$_POST['img_link_double']:false));                     
                }
				// перенос дефолтных (считанных из базы) значений в мэппинг формы
				foreach($info as $key=>$field){
					if(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['value'] = $info[$key];
				}

                if($mapping['banners']['context_date_start']['value'] == '0000-00-00') $mapping['banners']['context_date_start']['value'] = "";
				// получение данных, отправленных из формы
				$post_parameters = Request::GetParameters(METHOD_POST);
                array_walk($post_parameters,function($e){if(is_string($e)) return trim($e);});
                $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
                foreach($managers as $key=>$val){
                    $mapping['banners']['id_manager']['values'][$val['id']] = $val['name'];
                }				
                // формирование дополнительных данных для формы (не из основной таблицы)
				$campaigns = $db->fetchall("SELECT id,title FROM ".$sys_tables['tgb_campaigns']." ORDER BY id");
				foreach($campaigns as $key=>$val){
					$mapping['banners']['id_campaign']['values'][$val['id']] = $val['title'];
				}				
				Response::SetBoolean('not_show_submit_button', true); // не показывать кнопку сохранить в темплейте формы
				//папки для картинок спецпредложений
				Response::SetString('img_folder', Config::$values['img_folders']['tgb']); // папка для ТГБ
				// если была отправка формы - начинаем обработку     
                
                $utm_campaign = !empty($post_parameters['utm_campaign']) ? $post_parameters['utm_campaign'] : ( !empty($mapping['banners']['utm_campaign']['value']) ? $mapping['banners']['utm_campaign']['value'] : false ); 
                $utm_content = !empty($post_parameters['utm_content']) ? $post_parameters['utm_content'] : ( !empty($mapping['banners']['utm_content']['value']) ? $mapping['banners']['utm_content']['value'] : false ); 
                $title = !empty($post_parameters['title']) ? $post_parameters['title'] : ( !empty($mapping['banners']['title']['value']) ? $mapping['banners']['title']['value'] : false ); 
                
                //if(!empty($post_parameters['show_in_estate_section']) && $post_parameters['show_in_estate_section'] == 2) $post_parameters['in_estate_section'] = 0;
                
                if(empty($utm_campaign)) {
                    $agency = $db->fetch("SELECT 
                                              ".$sys_tables['agencies'].".title
                                          FROM ".$sys_tables['agencies']."
                                          LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id   
                                          LEFT JOIN ".$sys_tables['tgb_campaigns']." ON ".$sys_tables['tgb_campaigns'].".id_user = ".$sys_tables['users'].".id   
                                          WHERE ".$sys_tables['tgb_campaigns'].".id = ?
                                          GROUP BY ".$sys_tables['agencies'].".id",
                                          $mapping['banners']['id_campaign']['value']
                    );
                    if(!empty($agency)) $mapping['banners']['utm_campaign']['value'] = $post_parameters['utm_campaign'] = Convert::chpuTitle($agency['title']);
                }
                if(empty($utm_content) && !empty($title)) $mapping['banners']['utm_content']['value'] = $post_parameters['utm_content'] = Convert::chpuTitle($title);
                
				if(!empty($post_parameters['submit'])){
					Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
					// проверка значений из формы
					$errors = Validate::validateParams($post_parameters,$mapping['banners']);
                    
                    //проверяем ссылку на переход (ссылки с кириллицей считаем рабочими)
                    if(preg_match('/[А-я]/sui',$post_parameters['direct_link'])){
                        $link_response = true;
                    }elseif(preg_match('/^https/si',$post_parameters['direct_link'])){
                        require_once('includes/functions.php');
                        $link_response = get_http_response_code($post_parameters['direct_link']);
                    }else $link_response = curlThis( (!preg_match("/^https?/si",$post_parameters['direct_link']) ? "http:" : "").$post_parameters['direct_link'] );
                    
                    if(empty($link_response)) $errors['direct_link'] = "Ссылка не работает";
                    
					// замена фотографий ТГБ
					if(!empty($_FILES)){
						foreach ($_FILES as $fname => $data){
							if ($data['error']==0) {
                                $_folder = Host::$root_path.'/'.Config::$values['img_folders']['tgb'].'/'; // папка для файлов  тгб
								$_temp_folder = Host::$root_path.'/img/uploads/'; // папка для файлов  тгб
								$fileTypes = array('jpg','jpeg','gif','png'); // допустимые расширения файлов
								$fileParts = pathinfo($data['name']);
								$targetExt = $fileParts['extension'];
								$_targetFile = md5(microtime()).'.' . $targetExt; // конечное имя файла
                                Photos::makeDir( $_temp_folder.$_targetFile );
								Photos::makeDir( $_folder.$_targetFile );
                                if (in_array(strtolower($targetExt),$fileTypes)) {
									move_uploaded_file($data['tmp_name'],$_temp_folder.$_targetFile);
                                    Photos::imageResize($_temp_folder.$_targetFile,$_folder.$_targetFile,280,210,'cut',90);
                                    if(file_exists($_temp_folder.$_targetFile) && is_file($_temp_folder.$_targetFile)) unlink($_temp_folder.$_targetFile);
									if(file_exists($_folder.$mapping['banners'][$fname]['value']) && is_file($_folder.$mapping['banners'][$fname]['value'])) unlink($_folder.$mapping['banners'][$fname]['value']);
									$post_parameters[$fname] = $_targetFile;
								}
							}
						}
					}
					// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
                    foreach($post_parameters as $key=>$field){
                        if(!empty($mapping['banners'][$key]) && $mapping['banners'][$key]['fieldtype']=='set') {
                            if(!empty($post_parameters[$key.'_set'])){
                                $mapping['banners'][$key]['value'] = 0;
                                foreach($post_parameters[$key.'_set'] as $pkey=>$pval){
                                    $mapping['banners'][$key]['value'] += pow(2,$pkey);
                                }
                                $post_parameters[$key] = trim($mapping['banners'][$key]['value']);
                            }
                        }
                        elseif(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['value'] = trim($post_parameters[$key]);
                    }

					// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
					foreach($errors as $key=>$value){
						if(!empty($mapping['banners'][$key])) $mapping['banners'][$key]['error'] = $value;
					}
                    //проверяем на дубляж метки
                    if(!empty($mapping['banners']['utm']['value']) && $mapping['banners']['utm']['value'] == 1 && preg_match('#utm_#sui', $mapping['banners']['direct_link']['value'])) $errors['utm'] =  $mapping['banners']['utm']['error'] = "Нельзя добавить метки. Прямая ссылка уже содержит метки'";
                    //проверяем поля прикрепленного context
                    if(!empty($mapping['banners']['id_context']['value'])){
                        if($mapping['banners']['context_date_start']['value'] == '') $mapping['banners']['context_date_start']['value'] = date("Y-m-01");
                        $mapping['banners']['id_context']['value'] = str_replace(' ','',trim($mapping['banners']['id_context']['value'],','));
                        $contexts_from = strtotime($mapping['banners']['context_date_start']['value']);
                        if($contexts_from>time()){
                            $mapping['banners']['context_date_start']['error'] = "Некорректное значение даты,'".$mapping['banners']['context_date_start']['value']."' распознано как '".date("d.m.Y",$contexts_from)."'";
                            $errors['context_date_start'] = $mapping['banners']['context_date_start']['error'];
                        } 
                    } else $mapping['banners']['context_date_start']['value'] = "";
					// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
					if(empty($errors)) {
                        // подготовка всех значений для сохранения
						foreach($info as $key=>$field){
							if(isset($mapping['banners'][$key]['value'])) $info[$key] = $mapping['banners'][$key]['value'];
						}
                        $info['utm_campaign'] = 
						//переопределение ссылок на картинку на главной и на картинку в шапке
						$info['img_link'] = !empty($post_parameters['img_link_double']) ? $post_parameters['img_link_double'] : false;
						// сохранение в БД
						if($action=='edit'){
                            if( date('Y-m-d') < date($info['date_end']) && $info['published']==2) $info['published']=1; 
							//статус - отредактирован объект
							$res = $db->updateFromArray($sys_tables['tgb_banners'], $info, 'id') or die($db->error);
						} else {
							//дата дообавления объекта
							$res = $db->insertFromArray($sys_tables['tgb_banners'], $info, 'id');
							if(!empty($res)){
								$new_id = $db->insert_id;
								// редирект на редактирование свеженькой страницы
								if(!empty($res)) {
									header('Location: '.Host::getWebPath('/admin/advert_objects/tgb/banners/edit/'.$new_id.'/'));
									exit(0);
								}
							}
						}
                        if(!empty($info['in_estate_section'])) $mapping['banners']['in_estate_section']['value'] /= 2;
                        
                        if($info['credit_clicks'] == 2){
                            $db->query("DELETE FROM ".$sys_tables['tgb_banners_credits']." WHERE id_banner=?",
                                        $info['id']
                            );
                        } else {
                            $clicks = Tgb::getClicksPerDay($info['id']);
                            $db->query("INSERT INTO ".$sys_tables['tgb_banners_credits']." SET id_banner=?, day_limit=?
                                        ON DUPLICATE KEY UPDATE day_limit=?",
                                        $info['id'], $clicks, $clicks
                            );
                            
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
                //скрывать utm при добавлении
                if($action == 'add') $mapping['banners']['utm']['fieldtype'] = 'hidden';
                
				// запись данных для отображения на странице
				Response::SetArray('data_mapping',$mapping['banners']);
				break;
			case 'restore':
			case 'archive':
				$id =  empty($this_page->page_parameters[3]) ? "" : $this_page->page_parameters[3];
				//значение чекбокса
				$value = Request::GetString('value',METHOD_POST);
				$status = $action=='restore'?1:3;
				if($id>0){
					$res = $db->query("UPDATE ".$sys_tables['tgb_banners']." SET `published` = ? WHERE id=?", $status, $id) or die($db->error);
					$results['setStatus'] = ($res && $db->affected_rows) ? $id : -1;
					if($ajax_mode){
						$ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
						break;
					}
				} else $ajax_result = false;
				break;				
            case 'setStatus':
                //установка флагов для объектов
                 $id = Request::GetInteger('id',METHOD_POST);
                //значение чекбокса
                $value = Request::GetString('value',METHOD_POST);
                 $status = $value == 'checked'?1:2;
                if($id>0){
                    $res = $db->query("UPDATE ".$sys_tables['tgb_banners']." SET `enabled` = ? WHERE id=?", $status, $id);
                    $results['setStatus'] = $db->affected_rows>0 ? $id : -1;
                    if($ajax_mode){
                        $ajax_result = array('ok' => $results['setStatus']>0, 'ids'=>array($id));
                        break;
                    }
                } else $ajax_result = false;
                break;
			default:
				$module_template = 'admin.banners.list.html';
				//кол-во эл-ов в каждом блоке размещения
				 $sql_where  = !empty($filters['campaign'])?" AND `id_campaign` = ".$filters['campaign']." ":"";
				 $sql = "
					SELECT  
						(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['tgb_banners']." WHERE `published` !=3 $sql_where) AS alls,
						(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['tgb_banners']." WHERE `published` = 1 and enabled = 1 $sql_where) AS active,
						(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['tgb_banners']." WHERE `published` = 3 $sql_where) AS archive
					FROM dual";
				$counts = $db->fetch($sql) or die($sql.$db->error);
				Response::SetArray('statuses',array(
													'active'	=>	'Активные - '.$counts['active'],
													'alls'		=>	'Все - '.$counts['alls'],
													'archive'	=>	'В архиве - '.$counts['archive']
													));
				// формирование дополнительных данных для формы (не из основной таблицы)
				$campaigns = $db->fetchall("SELECT id,title FROM ".$sys_tables['tgb_campaigns']." ORDER BY id");
				Response::SetArray('campaigns',$campaigns);												
                $managers = $db->fetchall("SELECT id,name FROM ".$sys_tables['managers']." WHERE bsn_manager=1 ORDER BY name");
                Response::SetArray('managers', $managers);
				$conditions = [];
				if(!empty($filters)){
                    if(!empty($filters['manager'])) $conditions['manager'] = $sys_tables['tgb_banners'].'.id_manager = '.$db->real_escape_string($filters['manager']);
                    if(!empty($filters['campaign'])) $conditions['campaign'] = 'id_campaign = '.$db->real_escape_string($filters['campaign']);
					if(!empty($filters['credit_clicks'])) $conditions['credit_clicks'] = $sys_tables['tgb_banners_credits'].".id".($filters['credit_clicks']==1?'>0':' IS NULL');
                        
					switch($filters['status']){
						case  'active'	: $conditions['status'] = '`published` = 1 and enabled = 1';    break;
						case  'alls'	: $conditions['status'] = '`published` !=3'; 	break;
						case  'archive'	: $conditions['status'] = '`published` = 3'; 	break;
					}
				} 
				// формирование списка для фильтра
				$condition = implode(" AND ",$conditions);		
				$list = Tgb::getList(false, false, $condition, false, false, $sys_tables['tgb_banners'].".id ");
                foreach($list as $k =>$item){
                    $stats = Tgb::getItemStats($item['id']);
                    $list[$k] = array_merge($item, $stats);
                }
				// формирование списка
				Response::SetArray('list', $list);

				break;
			}
		break;
    /*****************************\
    |*  Работа с кампаниями ТГБ  *|
    \*****************************/				
	case 'add':
	case 'edit':
        $GLOBALS['css_set'][] = '/css/autocomplete.css';
        $GLOBALS['js_set'][] = '/js/jquery.typewatch.js';
        $GLOBALS['js_set'][] = '/js/jui_new/jquery-ui.js';
        $GLOBALS['css_set'][] = '/js/jui_new/jquery-ui.css';		
        $id = empty($this_page->page_parameters[2]) ? 0 : $this_page->page_parameters[2];
		$module_template = 'admin.campaigns.edit.html';
		if($action=='add'){
			// создание болванки новой записи
			$info = $db->prepareNewRecord($sys_tables['tgb_campaigns']);
		} else {
			// получение данных из БД
			$info = $db->fetch("SELECT *
								FROM ".$sys_tables['tgb_campaigns']." 
								WHERE id=?", $id);
		}
		// перенос дефолтных (считанных из базы) значений в мэппинг формы
		foreach($info as $key=>$field){
			if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['value'] = $info[$key];
		}
		// получение данных, отправленных из формы
		$post_parameters = Request::GetParameters(METHOD_POST);
        // формирование дополнительных данных для формы (не из основной таблицы)
        if(!empty($info['id_user']) || !empty($mapping['campaigns']['id_user']['value']) || !empty($post_parameters['id_user'])){
            $agency = Tgb::getAgency($sys_tables['users'].".id = " . ( !empty($post_parameters['id_user']) ? $post_parameters['id_user'] : ( !empty($mapping['campaigns']['id_user']['value']) ? $mapping['campaigns']['id_user']['value'] : $info['id_user'] ) ) );
            $post_parameters['agency_title'] = $mapping['campaigns']['agency_title']['value'] = $agency['title'];
        }

		// если была отправка формы - начинаем обработку
		if(!empty($post_parameters['submit'])){
			Response::SetBoolean('form_submit', true); // признак того, что форма была обработана
			// перенос полученных значений в мэппинг формы для последующего отображения (подмена дефолотных)
			foreach($post_parameters as $key=>$field){
				if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['value'] = $post_parameters[$key];
			}
			// проверка значений из формы
			$errors = Validate::validateParams($post_parameters,$mapping['campaigns']);
			// выписывание ошибок в мэппинг формы (для отображения ошибочных полей)
			foreach($errors as $key=>$value){
				if(!empty($mapping['campaigns'][$key])) $mapping['campaigns'][$key]['error'] = $value;
			}
			// если ошибок не было - готовимся к сохранению данных в БД и производим попытку сохранения
			if(empty($errors)) {
				// подготовка всех значений для сохранения
				foreach($info as $key=>$field){
					if(isset($mapping['campaigns'][$key]['value'])) $info[$key] = $mapping['campaigns'][$key]['value'];
				}
				// сохранение в БД
				if($action=='edit'){
					$res = $db->updateFromArray($sys_tables['tgb_campaigns'], $info, 'id') or die($db->error);
				} else {
					$res = $db->insertFromArray($sys_tables['tgb_campaigns'], $info, 'id');
					if(!empty($res)){
						$new_id = $db->insert_id;
						// редирект на редактирование свеженькой страницы
						if(!empty($res)) {
							header('Location: '.Host::getWebPath('/admin/advert_objects/tgb/edit/'.$new_id.'/'));
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
		Response::SetArray('data_mapping',$mapping['campaigns']);
		break;
	default:
		$module_template = 'admin.campaigns.list.html';
		//кол-во эл-ов в каждом блоке размещения
		 $sql = "
			SELECT  
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['tgb_campaigns']." WHERE `published` > 0) AS alls,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['tgb_campaigns']." WHERE `published` = 1) AS active,
				(SELECT IFNULL(COUNT(*),0) FROM ".$sys_tables['tgb_campaigns']." WHERE `published` = 2) AS archive
			FROM dual";
		$counts = $db->fetch($sql) or die($sql.$db->error);
		Response::SetArray('statuses',array(
											'active'	=>	'Активные - '.$counts['active'],
											'alls'		=>	'Все - '.$counts['alls'],
											'archive'	=>	'В архиве - '.$counts['archive']
											));
        $conditions = [];
		if(!empty($filters)){
			switch($filters['status']){
				case  'active'	: $conditions['status'] = $sys_tables['tgb_campaigns'].".`published` = 1"; 		break;
				case  'alls'	: $conditions['status'] = $sys_tables['tgb_campaigns'].".`published` > 0"; 	break;
				case  'archive'	: $conditions['status'] = $sys_tables['tgb_campaigns'].".`published` = 2"; 	break;
			}
            if(!empty($filters['manager'])) $conditions[] = "`id_manager` = ".($filters['manager']!=99 ? $filters['manager'] : 0);
		} 
		// формирование списка для фильтра
		$condition = implode(" AND ",$conditions);		
		// создаем пагинатор для списка
		$paginator = new Paginator($sys_tables['tgb_campaigns'], 30, $condition);
		// get-параметры для ссылок пагинатора
		$get_in_paginator = [];
		foreach($get_parameters as $gk=>$gv){
			if($gk!='page') $get_in_paginator[] = $gk.'='.$gv;
		}
		// ссылка пагинатора
		$paginator->link_prefix = '/admin/advert_objects/tgb/campaigns'                // модуль
								  ."/?"                                       // конечный слеш и начало GET-строки
								  .implode('&',$get_in_paginator)             // GET-строка
								  .(empty($get_in_paginator)?"":'&')."page="; // параметр для номера страницы
		if($paginator->pages_count>0 && $paginator->pages_count<$page){
			Header('Location: '.Host::getWebPath($paginator->link_prefix.$paginator->pages_count));
			exit(0);
		}

		$sql = "SELECT 
                        ".$sys_tables['tgb_campaigns'].".*, 
                        IFNULL(a.cnt_1,0) as cnt_1, 
                        IFNULL(b.cnt_2,0) as cnt_2 ,
                        IFNULL(d.cnt_day,0) as cnt_day,
                        IFNULL(d.cnt_full,0) as cnt_full,
                        IFNULL(e.cnt_click_day,0) as cnt_click_day,
                        IFNULL(e.cnt_click_full,0) as cnt_click_full,
                        ".$sys_tables['agencies'].".title as agency_title
                FROM ".$sys_tables['tgb_campaigns']."
                LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".id = ".$sys_tables['tgb_campaigns'].".id_user
                LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['users'].".id_agency
                LEFT JOIN (SELECT COUNT(*) as cnt_1, id_campaign FROM ".$sys_tables['tgb_banners']." WHERE  `enabled` = 1 AND `published` = 1 GROUP BY `id_campaign`) a ON a.id_campaign = ".$sys_tables['tgb_campaigns'].".id
                LEFT JOIN (SELECT COUNT(*) as cnt_2, id_campaign FROM ".$sys_tables['tgb_banners']." WHERE  `published` != 3 GROUP BY `id_campaign`) b ON b.id_campaign = ".$sys_tables['tgb_campaigns'].".id
                LEFT JOIN (SELECT aa.id, SUM(ab.cnt_day) as cnt_day, SUM(ac.cnt_full) as cnt_full, aa.id_campaign FROM ".$sys_tables['tgb_banners']."  aa 
                LEFT JOIN (SELECT COUNT(*) as cnt_day, id_parent FROM ".$sys_tables['tgb_stats_day_shows']." GROUP BY id_parent) ab ON ab.id_parent = aa.id 
                LEFT JOIN (SELECT SUM(amount) as cnt_full, id_parent FROM ".$sys_tables['tgb_stats_full_shows']." GROUP BY id_parent) ac ON ac.id_parent = aa.id GROUP BY aa.id_campaign) d ON d.id_campaign = ".$sys_tables['tgb_campaigns'].".id
                LEFT JOIN (SELECT ba.id, SUM(bb.cnt_click_day) as cnt_click_day, SUM(bc.cnt_click_full) as cnt_click_full, ba.id_campaign FROM ".$sys_tables['tgb_banners']."  ba 
                LEFT JOIN (SELECT COUNT(*) as cnt_click_day, id_parent FROM ".$sys_tables['tgb_stats_day_clicks']." GROUP BY id_parent) bb ON bb.id_parent = ba.id 
                LEFT JOIN (SELECT SUM(amount) as cnt_click_full, id_parent FROM ".$sys_tables['tgb_stats_full_clicks']." GROUP BY id_parent) bc ON bc.id_parent = ba.id GROUP BY ba.id_campaign) e ON e.id_campaign = ".$sys_tables['tgb_campaigns'].".id";        
        if(!empty($condition)) $sql .= " WHERE ".$condition;
        $sql .= " ORDER BY ".$sys_tables['tgb_campaigns'].".id";
        $sql .= " LIMIT ".$paginator->getLimitString($page); 
        $list = $db->fetchall($sql);
		// формирование списка
		Response::SetArray('list', $list);
		
		if($paginator->pages_count>1){
			Response::SetArray('paginator', $paginator->Get($page));
		}
		break;
}



// запоминаем для шаблона GET - параметры
Response::SetArray('get_array', $get_parameters);
foreach($get_parameters as $gk=>$gv) $get_parameters[$gk] = $gv;
Response::SetString('get_string', implode('&',$get_parameters));


?>