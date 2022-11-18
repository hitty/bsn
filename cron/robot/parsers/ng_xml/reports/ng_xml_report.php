<?php

$moderate_statuses = array(2=>'маленькая стоимость',3=>'большая стоимость',4=>'нет адреса'); //статусы модерации
$rent_titles = array(1=>'аренда', 2=>'продажа'); //типы сделок

$xml = new DOMDocument('1.0','utf-8');
$xmlentire = $xml->appendChild($xml->createElement('objs'));
$xmlentire->setAttribute('date',date('Y-m-d H:i:s'));

$sql = "
    SELECT `id_user`,`tab`,`published`,`info_source`, ng.bsn_title, ng.ng_id, ng.bsn_id FROM
    (
        (SELECT `id_user`,'".$sys_tables['live']."'  as tab,`published`,`info_source` FROM ".$sys_tables['live']." WHERE published!=2 AND info_source=4)
        UNION
        (SELECT `id_user`,'".$sys_tables['live_new']."'  as tab,`published`,`info_source` FROM ".$sys_tables['live_new']." WHERE published!=2 AND info_source=4)
        UNION
        (SELECT `id_user`,'".$sys_tables['country']."'  as tab,`published`,`info_source` FROM ".$sys_tables['country']." WHERE published!=2 AND info_source=4)
        UNION
        (SELECT `id_user`,'".$sys_tables['country_new']."'  as tab,`published`,`info_source` FROM ".$sys_tables['country_new']." WHERE published!=2 AND info_source=4)
        UNION
        (SELECT `id_user`,'".$sys_tables['commercial']."'  as tab,`published`,`info_source` FROM ".$sys_tables['commercial']." WHERE published!=2 AND info_source=4)
        UNION
        (SELECT `id_user`,'".$sys_tables['commercial_new']."'  as tab,`published`,`info_source` FROM ".$sys_tables['commercial_new']." WHERE published!=2 AND info_source=4)
        UNION
        (SELECT `id_user`,'".$sys_tables['build']."'  as tab,`published`,`info_source` FROM ".$sys_tables['build']." WHERE published!=2 AND info_source=4)
        UNION
        (SELECT `id_user`,'".$sys_tables['build_new']."'  as tab,`published`,`info_source` FROM ".$sys_tables['build_new']." WHERE published!=2 AND info_source=4)
    ) a
    LEFT JOIN ".$sys_tables['users']." us ON us.id = a.id_user
    LEFT JOIN `bsn_ng`.`agencies_list` ng ON ng.bsn_id = us .id_agency
    GROUP BY `id_user`
    ";
$result = $db->querys($sql) or trigger_error("MySQL error: ".$db->error, E_USER_WARNING);
while ($row = $result->fetch_array(MYSQL_ASSOC))
{

        $user = $xmlentire->appendChild($xml->createElement('user'));
        $user->setAttribute('name',$row['bsn_title']);
        $user->setAttribute('bsn_id_user',$row['bsn_id']);
        $user->setAttribute('ng_id_user',$row['ng_id']);
        $sql_obj = "
                SELECT `id_user`,`id`, `tab`,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, `by_the_day`, `cost2meter`, `views_count` FROM
                (
                  (SELECT `id_user`,`id`,'live' as tab,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, `by_the_day`, '' as `cost2meter`, `views_count` FROM ".$sys_tables['live']." WHERE `id_user` = ".$row['id_user']." AND published!=2 AND info_source=4)
                  UNION
                  (SELECT `id_user`,`id`,'live' as tab,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, `by_the_day`, '' as `cost2meter`, `views_count` FROM ".$sys_tables['live_new']." WHERE `id_user` = ".$row['id_user']." AND published!=2 AND info_source=4)
                  UNION
                  (SELECT `id_user`,`id`,'country' as tab,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, `by_the_day`, '' as `cost2meter`, `views_count` FROM ".$sys_tables['country']." WHERE `id_user` = ".$row['id_user']." AND published!=2 AND info_source=4)
                  UNION
                  (SELECT `id_user`,`id`,'country' as tab,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, `by_the_day`, '' as `cost2meter`, `views_count` FROM ".$sys_tables['country_new']." WHERE `id_user` = ".$row['id_user']." AND published!=2 AND info_source=4)
                  UNION
                  (SELECT `id_user`,`id`,'commercial' as tab,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, '' as `by_the_day`, `cost2meter`, `views_count` FROM ".$sys_tables['commercial']." WHERE `id_user` = ".$row['id_user']." AND published!=2 AND info_source=4)
                  UNION
                  (SELECT `id_user`,`id`,'commercial' as tab,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, '' as `by_the_day`, `cost2meter`, `views_count` FROM ".$sys_tables['commercial_new']." WHERE `id_user` = ".$row['id_user']." AND published!=2 AND info_source=4)
                  UNION
                  (SELECT `id_user`,`id`,'build' as tab,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, '' as `by_the_day`, '' as `cost2meter`, `views_count` FROM ".$sys_tables['build']." WHERE `id_user` = ".$row['id_user']." AND published!=2 AND info_source=4)
                  UNION
                  (SELECT `id_user`,`id`,'build' as tab,`published`, `rent`, `external_id`, `info_source`, `id_street`, `txt_addr`, `cost`, '' as `by_the_day`, '' as `cost2meter`, `views_count` FROM ".$sys_tables['build_new']." WHERE `id_user` = ".$row['id_user']." AND published!=2 AND info_source=4)
                ) a GROUP BY `external_id`
                ";
        $res_obj = $db->querys($sql_obj) or die($db->error);
        while($row_obj = $res_obj->fetch_array()){
            $item = $user->appendChild($xml->createElement('item'));
             if($row_obj['published']==1) {
                 $item->setAttribute('url','https://www.bsn.ru/'.$row_obj['tab'].'/'.$row_obj['id']);
                 $item->setAttribute('views_count',$row_obj['views_count']);
            }
            $item->setAttribute('ng_id_obj',$row_obj['external_id']);
            
            if($row_obj['published']==3){
                //получение статуса модерации объекта
                $moderate = new Moderation($row_obj['tab'],0);
                $moderate_status = $moderate->getModerateStatus($row_obj);
                $item->setAttribute('moderate','false');
                $item->setAttribute('moderate_status',$moderate_statuses[$moderate_status]);
            }
        }

}

    
$filename = ROOT_PATH.'/ng_objects.xml';
if(file_exists($filename)) unlink($filename);
exec("chmod 777 ".$filename);
$xml->formatOutput = true;
$xml->save($filename);
?>  
