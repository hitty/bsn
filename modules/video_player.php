<?php
$get_params = Request::GetParameters(METHOD_GET);
$name = !empty($get_params['name']) ? 'http://' . str_replace('http://', '', $get_params['name']) : '';
$posterUrl = !empty($get_params['posterUrl']) ? $get_params['posterUrl'] : '';
$title = !empty($get_params['title']) ? $get_params['title'] : '';
$advertising = !empty($get_params['advertising']) ? Convert::ToInt($get_params['advertising']) : 1;
$auto_play = !empty($get_params['auto_play']) ? $get_params['auto_play'] : 2;

$array = array(
    'playlist' => array(
            array(
            'video' => $name,
            'Title' => $title,
            'posterUrl' => $posterUrl
        )
    ),                             
    "uiLanguage" => "ru",
    "design"  => array(
        "skinName" => "islands","color" => array("scheme" => "dark","buttonBg" => "#333333","buttonNormal" => "#FFFFFF","buttonHover" => "#1e88e5"
    ),
    "hide" => array("followPlaylistButton","shareCodeButton")),
    "autoplay" => $auto_play == 1 ? true : false,
    "beforePlay" => "none",
    "afterPlay" => "start",
    "plugins" => array(
                    array(
                        "url" => "http://s3.spruto.org/embed/gaplugin.swf",
                        "settings"  => array(
                                    "gaAccount"  => "UA-12979802-1",
                                    'gaCategory'  => 'Показ видео',
                                    'trackImpression'  => true,
                                    'trackPlayerStart' => true,
                                    'trackViewVideo'  => true,
                                    'trackPlayerFirstQuartile' => true,
                                    'trackPlayerMidPoint' => true,
                                    'trackPlayerThirdQuartile' => true,
                                    'trackPlayerComplete' => true
                        )
                  )    
    )
    
);     
    
/*
if($advertising == 1){         
    array_push(
        $array['plugins'],
        array(
            "url" => "https://s3.advarkads.com/modules/advarksprutoplugin.swf",
            "settings" => array(
                        "id" => "3969-1-1"
            )
        )
        
    );
} ;
*/
echo json_encode($array, JSON_UNESCAPED_UNICODE);
exit(0);     
    
?>
