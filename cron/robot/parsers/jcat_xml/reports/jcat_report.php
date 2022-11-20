<?php
$xml = new DOMDocument('1.0','utf-8');
$xmlentire = $xml->appendChild($xml->createElement('objs'));
$xmlentire->setAttribute('date',date('Y-m-d H:i:s'));

$sql = "
    SELECT `id_user`,`tab`,`published`,`info_source`,`views_count` FROM
    (
        (SELECT `id_user`, '".$sys_tables['live']."' as tab,`published`,`info_source`,`views_count` FROM ".$sys_tables['live'].")
        UNION
        (SELECT `id_user`,'".$sys_tables['country']."' as tab,`published`,`info_source`,`views_count` FROM ".$sys_tables['country'].")
        UNION
        (SELECT `id_user`,'".$sys_tables['commercial']."' as tab,`published`,`info_source`,`views_count` FROM ".$sys_tables['commercial'].")
        UNION
        (SELECT `id_user`,'".$sys_tables['build']."' as tab,`published`,`info_source`,`views_count` FROM ".$sys_tables['build'].")
    ) a
    LEFT JOIN ".$sys_tables['users']." us ON us.id = a.id_user
    WHERE a.published=1 AND a.id_user=29298
    GROUP BY `id_user`
    ";
$result = $db->querys($sql) or trigger_error("MySQL error: ".$db->error, E_USER_WARNING);
echo '';
while ($row = $result->fetch_array(MYSQL_ASSOC))
{
            
        $user = $xmlentire->appendChild($xml->createElement('user'));
        $user->setAttribute('name','BSN.ru');
        $sql_obj = "
                SELECT `id_user`,`id`, `tab`,`published`,`external_id`, `info_source`,`views_count` FROM
                (
                  (SELECT `id_user`,`id`,'live' as tab,`published`,`external_id`, `info_source`,`views_count` FROM ".$sys_tables['live']." WHERE `id_user` = ".$row['id_user']." AND published=1 AND id_user=29298)
                  UNION
                  (SELECT `id_user`,`id`,'country' as tab,`published`,`external_id`, `info_source`,`views_count` FROM ".$sys_tables['country']." WHERE `id_user` = ".$row['id_user']." AND published=1 AND id_user=29298)
                  UNION
                  (SELECT `id_user`,`id`,'commercial' as tab,`published`,`external_id`, `info_source`,`views_count` FROM ".$sys_tables['commercial']." WHERE `id_user` = ".$row['id_user']." AND published=1 AND id_user=29298) 
                  UNION
                  (SELECT `id_user`,`id`,'build' as tab,`published`,`external_id`, `info_source`,`views_count` FROM ".$sys_tables['build']." WHERE `id_user` = ".$row['id_user']." AND published=1 AND id_user=29298) 
                ) a
                ";
        $res_obj = $db->querys($sql_obj) or die($db->error);
        while($row_obj = $res_obj->fetch_array()){
            $item = $user->appendChild($xml->createElement('item'));
            $item->setAttribute('url','http://www.bsn.ru/'.$row_obj['tab'].'/'.$row_obj['id']);
            $item->setAttribute('jcat_external_id',$row_obj['external_id']);
            $item->setAttribute('views_today',$row_obj['views_count']);
        }

}

$filename = ROOT_PATH.'/jcat_objects.xml';
if(file_exists($filename)) unlink($filename);
$xml->formatOutput = true;
$xml->save($filename);
exec("chmod 777 ".$filename);
?>  
