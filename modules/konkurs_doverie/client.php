<?php
require_once('includes/class.content.php');

$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

$GLOBALS['css_set'][] = '/modules/konkurs_doverie/common.css';
$GLOBALS['css_set'][] = '/modules/konkurs_doverie/styles.css';

$GLOBALS['js_set'][] = '/js/jquery.min.js';
$GLOBALS['js_set'][] = '/modules/konkurs_doverie/script.js';
$GLOBALS['js_set'][] = '/modules/konkurs_doverie/voting.js';

$user_ip =  Host::getUserIp();

switch(true){
    //////////////////////////////////////////////////////////////
    // главная страница
    //////////////////////////////////////////////////////////////
    case empty($action):
        //получаем информацию по конкурсу
        $info = $db->fetch(" SELECT * FROM " . $sys_tables['konkurs'] ." WHERE url = ? ", $this_page->real_path );
        
        Response::SetBoolean('konkurs_status',($info['status']==1));
        //заголовок таблицы голосования
        Response::SetArray('info',$info);
        
        //если конкурс активен, получаем список участников
        if($info['status']==1){
            $list = $db->fetchall("SELECT 
                                          ".$sys_tables['konkurs_members'].".*,
                                          ".$sys_tables['konkurs_members'].".status as member_status, 
                                          LTRIM(".$sys_tables['konkurs_members'].".title) as member_title,
                                          (SELECT COUNT(*) FROM ".$sys_tables['konkurs_members']." WHERE ".$sys_tables['konkurs_members'].".id_category = ".$sys_tables['konkurs_members_categories'].".id) as members_count,
                                          ".$sys_tables['konkurs_members_categories'].".title as category_title,
                                          ".$sys_tables['konkurs_members_categories'].".id as id_category,
                                          ".$sys_tables['konkurs_votings'].".vote_id_member,
                                          IF(".$sys_tables['konkurs_votings'].".id>0,0,1) as can_vote
                                   FROM ".$sys_tables['konkurs_members_categories']."
                                   LEFT JOIN  ".$sys_tables['konkurs']." ON ".$sys_tables['konkurs'].".id = ".$sys_tables['konkurs_members_categories'].".id_konkurs
                                   LEFT JOIN  ".$sys_tables['konkurs_votings']." ON 
                                        ".$sys_tables['konkurs_members_categories'].".id = ".$sys_tables['konkurs_votings'].".vote_id_category
                                        AND ".$sys_tables['konkurs_votings'].".id_konkurs = " . $sys_tables['konkurs'] . ".id
                                        AND ".$sys_tables['konkurs_votings'].".ip = '" . $user_ip . "' 
                                   LEFT JOIN  ".$sys_tables['konkurs_members']." ON 
                                        ".$sys_tables['konkurs_votings'].".vote_id_member = " . $sys_tables['konkurs_members'] . ".id
                                        AND ".$sys_tables['konkurs_votings'].".vote_id_category = " . $sys_tables['konkurs_members_categories'] . ".id
                                   WHERE 
                                        ".$sys_tables['konkurs'].".id = ".$info['id']."
                                   GROUP BY ".$sys_tables['konkurs_members_categories'].".id
                                   ORDER BY ".$sys_tables['konkurs_members_categories'].".id"); 
                                  
            foreach($list as $k=>$item){
                    //$cookie_title = Cookie::GetString('konkurs_vote_for_' . $item['id_category']);
                    if(!empty($cookie_title)){
                        $list[$k]['title'] = $db->fetch("SELECT title FROM " . $sys_tables['konkurs_members'] . " WHERE id = ?", $cookie_title)['title'];
                        $list[$k]['can_vote'] = 0;
                    }
            }
            Response::SetArray('list',$list);
            Response::SetString('konkurs_url',$action);
            Response::SetString('img_folder',Config::Get('img_folders/konkurs'));
        }
        
        $this_page->addBreadcrumbs('Конкурс «'.$info['title'].'»', $action);
        $new_meta = array('title'=>$info['title']);
        $this_page->manageMetadata($new_meta,true);
        
        $h1 = empty($this_page->page_seo_h1) ? $info['title'] : $this_page->page_seo_h1;
        Response::SetString('h1',$h1);
        $module_template = 'mainpage.html';
        break;
    //////////////////////////////////////////////////////////////
    // список номинантов
    //////////////////////////////////////////////////////////////
    case !empty( $action ) && $ajax_mode && Validate::isDigit( $action ) && empty( $this_page->page_parameters[1] ):
        $id = $action;
        $list = $db->fetchall("
            SELECT 
                  ".$sys_tables['konkurs_members'].".*,
                  ".$sys_tables['konkurs_members'].".status as member_status, 
                  LTRIM(".$sys_tables['konkurs_members'].".title) as member_title,
                  (SELECT COUNT(*) FROM ".$sys_tables['konkurs_members']." WHERE ".$sys_tables['konkurs_members'].".id_category = ".$sys_tables['konkurs_members_categories'].".id) as members_count,
                  ".$sys_tables['konkurs_members_categories'].".title as category_title,
                  ".$sys_tables['konkurs_votings'].".vote_id_member,
                  IF(".$sys_tables['konkurs_votings'].".id>0,0,1) as can_vote
           FROM ".$sys_tables['konkurs_members']."
           LEFT JOIN  ".$sys_tables['konkurs_members_categories']." ON ".$sys_tables['konkurs_members_categories'].".id=".$sys_tables['konkurs_members'].".id_category
           LEFT JOIN  ".$sys_tables['konkurs_votings']." ON 
                ".$sys_tables['konkurs_votings'].".vote_id_category=".$sys_tables['konkurs_members_categories'].".id 
                AND ".$sys_tables['konkurs_votings'].".ip = '".$user_ip."' 
                AND ".$sys_tables['konkurs_votings'].".id_konkurs=".$sys_tables['konkurs_members'].".id_konkurs
           WHERE ".$sys_tables['konkurs_members'].".id_category = ?
           ORDER BY ".$sys_tables['konkurs_members_categories'].".id , member_title",
           false, $id
        ); 
        foreach($list as $k=>$item){
                //$cookie_title = Cookie::GetString('konkurs_vote_for_' . $item['id_category']);
                if(!empty($cookie_title)){
                    $list[$k]['vote_id_member'] = $cookie_title;
                    $list[$k]['can_vote'] = 0;
                }
        }
        
        if( !empty( $list ) ) {
            Response::SetArray('list',$list);
            $ajax_result['can_vote'] = $list[0]['can_vote'];
            $ajax_result['id_category'] = $id;
        }
        Response::SetInteger('id_category', $id);
        $ajax_result['ok'] = true;
        
        $module_template = 'list.html';
        break;
    //////////////////////////////////////////////////////////////
    // голосование
    //////////////////////////////////////////////////////////////
    case $action =='vote' && $ajax_mode:
        //голосование
        $id = Request::GetInteger('id',METHOD_POST);
        $id_category = Request::GetInteger('id_category',METHOD_POST);
        if(!empty($id) && !empty($id_category)){
            //получаем категорию
            $list = $db->fetch("SELECT * FROM ".$sys_tables['konkurs_members']." WHERE id = ? AND id_category = ?",$id, $id_category);
            if(!empty($list)){
                $check = $db->fetch("SELECT ".$sys_tables['konkurs_votings'].".id 
                                     FROM ".$sys_tables['konkurs_votings']."
                                     WHERE  ip = ? AND vote_id_category = ? AND id_konkurs = ? AND HOUR( TIMEDIFF( NOW( ) , ".$sys_tables['konkurs_votings'].".`datetime` ) )< 9999993" ,
                                     $user_ip, $list['id_category'],$list['id_konkurs']
                );
                //$cookie_vote = Cookie::GetString('konkurs_vote_for_' . $id_category);
                if(empty($check) && empty($cookie_vote)){
                    $res = $db->query("INSERT INTO ".$sys_tables['konkurs_votings']." SET id_konkurs = ?, vote_id_category = ?, vote_id_member = ?, ip = ?, datetime = NOW()",
                                       $list['id_konkurs'],$list['id_category'],$id,$user_ip);
                    $res1 = $db->query("UPDATE ".$sys_tables['konkurs_members']." SET amount = amount+1 WHERE id=?",$id);
                    $ajax_result['ok'] = $res && $res1;
                    Cookie::SetCookie('konkurs_vote_for_' . $id_category, $id, 60*60*24*160, '/', DEBUG_MODE ? '.bsn.int' : '.bsn.ru');
                }
            }
        }
        
        break;
    //////////////////////////////////////////////////////////////
    // результаты голосования по категории
    //////////////////////////////////////////////////////////////
    case $action =='results' && $ajax_mode:
        //голосование
        $id_category = Request::GetInteger( 'id_category', METHOD_POST );
        if(!empty($id_category)){
            //получаем категорию
            $list = $db->fetchall("
                SELECT 
                      ".$sys_tables['konkurs_members'].".id,
                      LTRIM(".$sys_tables['konkurs_members'].".title) as member_title,
                      (SELECT COUNT(*) FROM ".$sys_tables['konkurs_votings']." WHERE ".$sys_tables['konkurs_votings'].".vote_id_member = ".$sys_tables['konkurs_members'].".id) as vote_count
               FROM ".$sys_tables['konkurs_members']."
               LEFT JOIN  ".$sys_tables['konkurs_members_categories']." ON ".$sys_tables['konkurs_members_categories'].".id=".$sys_tables['konkurs_members'].".id_category
               LEFT JOIN  ".$sys_tables['konkurs_votings']." ON 
                    ".$sys_tables['konkurs_votings'].".vote_id_category=".$sys_tables['konkurs_members_categories'].".id 
               WHERE ".$sys_tables['konkurs_members'].".id_category = ?
               GROUP BY ".$sys_tables['konkurs_members'].".id
               ORDER BY member_title",
               false, $id_category
            );
            if( !empty( $list ) ) {
                $total = 0;
                $votes = [];
                foreach( $list as $k => $item ) {
                    $total += $item['vote_count'];
                    $votes[ $item['id'] ] = $list[$k];
                }
                $ajax_result['ok'] = true;
                $ajax_result['list'] = $votes;
                $ajax_result['total'] = $total;
            }
        }
        break;
    //////////////////////////////////////////////////////////////
    // блоки
    //////////////////////////////////////////////////////////////
    case Validate::isDigit($action) && $ajax_mode:
        Response::SetInteger('action', $action);
        $ajax_result['ok'] = true;
        $module_template = 'block.' . $action . '.html';
        break;
    //////////////////////////////////////////////////////////////
    // 404
    //////////////////////////////////////////////////////////////
    default:
        $GLOBALS['css_set'][] = '/css/common.css';
        $GLOBALS['css_set'][] = '/css/central.css';
        $this_page->http_code = 404;
        break;
}
?>