<?
$xml = new DOMDocument('1.0','utf-8');
$xmlentire = $xml->appendChild($xml->createElement('objs'));
$xmlentire->setAttribute('date',date('Y-m-d H:i:s'));



        $sql = "
            SELECT `id_user`,`tab`,`published`,`info_source` FROM
            (
                (SELECT `id_user`, '".$module_tables['live']."' as tab,`published`,`info_source` FROM ".$module_tables['live']." WHERE id_user = 29298 AND published = 1 )
                UNION
                (SELECT `id_user`,'".$module_tables['country']."' as tab,`published`,`info_source` FROM ".$module_tables['country']." WHERE id_user = 29298 AND published = 1 )
                UNION
                (SELECT `id_user`,'".$module_tables['commercial']."' as tab,`published`,`info_source` FROM ".$module_tables['commercial']." WHERE id_user = 29298 AND published = 1 )
                UNION
                (SELECT `id_user`,'".$module_tables['build']."' as tab,`published`,`info_source` FROM ".$module_tables['build']." WHERE id_user = 29298 AND published = 1 )
            ) a
            LEFT JOIN ".$module_tables['users']." us ON us.id = a.id_user
            GROUP BY `id_user`
            ";
        $result = $db->query($sql) or trigger_error("MySQL error: ".$db->error, E_USER_WARNING);
        while ($row = $result->fetch_array(MYSQL_ASSOC))
        {
                    
                $user = $xmlentire->appendChild($xml->createElement('user'));
                $user->setAttribute('name','BSN.ru');
                $sql_obj = "
                        SELECT `id_user`,`id`, `tab`,`published`,`external_id`, `info_source` FROM
                        (
                          (SELECT `id_user`,`id`,'live' as tab,`published`,`external_id`, `info_source` FROM ".$module_tables['live']." WHERE `id_user` = ".$row['id_user']." AND published=1 AND id_user=29298)
                          UNION
                          (SELECT `id_user`,`id`,'country' as tab,`published`,`external_id`, `info_source` FROM ".$module_tables['country']." WHERE `id_user` = ".$row['id_user']." AND published=1 AND id_user=29298)
                          UNION
                          (SELECT `id_user`,`id`,'commercial' as tab,`published`,`external_id`, `info_source` FROM ".$module_tables['commercial']." WHERE `id_user` = ".$row['id_user']." AND published=1 AND id_user=29298) 
                          UNION
                          (SELECT `id_user`,`id`,'build' as tab,`published`,`external_id`, `info_source` FROM ".$module_tables['build']." WHERE `id_user` = ".$row['id_user']." AND published=1 AND id_user=29298) 
                        ) a
                        ";
                $res_obj = $db->query($sql_obj) or die($db->error);
                while($row_obj = $res_obj->fetch_array()){
                    $item = $user->appendChild($xml->createElement('item'));
                    $item->setAttribute('url','https://www.bsn.ru/'.$row_obj['tab'].'/'.$row_obj['id']);
                    $item->setAttribute('soft_estate_external_id',$row_obj['external_id']);
                }
        
        }

$filename = ROOT_PATH.'/soft_estate_objects.xml';
if(file_exists($filename)) unlink($filename);
exec("chmod 777 ".$filename);
$xml->formatOutput = true;
$xml->save($filename);
?>  
