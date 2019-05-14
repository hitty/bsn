<?php          
    // Create an image, 1x1 pixel in size
    $im=imagecreate(1,1);

    // Set the background colour
    $white=imagecolorallocate($im,255,255,255);

    // Allocate the background colour
    imagesetpixel($im,1,1,$white);

    // Set the image type
    header("content-type:image/jpg");

    // Create a JPEG file from the image
    imagejpeg($im);

    // Free memory associated with the image
    imagedestroy($im);
  
    $data = array(
        'id_campaign' => isset( $_GET['campaign'] ) ? $_GET['campaign'] : '',
        'ip' => isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '',
        'referer' => isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '',
        'useragent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
        'status' => isset( $_GET['status'] ) ? $_GET['status'] : 1,
        'email' => isset( $_GET['email'] ) ? $_GET['email'] : ''
    );
    
    $db->insertFromArray( $sys_tables['newsletters'], $data, false, false, true );
    var_dump( $db );
    exit(0);

?>