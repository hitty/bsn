<?php
$total_width = 200;
$total_height = 80;
session_name('RSASESSIONID');
if(!ini_get("session.auto_start")) session_start();
srand((double)microtime()*1000000);
$chars = '23456789ABCEFHKMNPRSTUVWXYZ';
$captcha_length = rand(4,5);
$confirm_id = htmlspecialchars($_GET['captid']);
if(isset($_SESSION["captches"][$confirm_id])){
	$code = $_SESSION["captches"][$confirm_id];
} else {
    $code = '';
    for($i=0;$i<$captcha_length;$i++) $code = $code.$chars[rand(0, strlen($chars)-1)];
	$code = $_SESSION["captches"][$confirm_id] = strtoupper($code);
}
$_SESSION["captches"] = [];
$_SESSION["captches"][$confirm_id] = $code;

// For better compatibility with some servers which need absolut path to load TTFonts
define("HOMEDIR", $_SERVER['DOCUMENT_ROOT']."/");
// путь к корню капчи
$captcha_path = HOMEDIR.'includes/captcha/';
$fonts_path = $captcha_path.'fonts/';

if(!empty($_GET['w'])) $total_width = intval($_GET['w']);
if(!empty($_GET['h'])) $total_height = intval($_GET['h']);

$bg_color = array(round(rand(192,255)),round(rand(192,255)),round(rand(192,255)));
$cell = true;
$cell_size = round(rand(7,20));
$ellipse = true;
$lines = true;

// Create list of fonts
$fonts = [];
if ($fonts_dir = opendir($fonts_path)){
    while (true == ($file = @readdir($fonts_dir))){
        if ((substr(strtolower($file), -3) == 'ttf')){
            $fonts[] = $file;
        }
    }
    closedir($fonts_dir);
}
$font = rand(0, (count($fonts)-1));

// Generate image
$image = imagecreatetruecolor($total_width, $total_height);
// Fill background
$background_color = imagecolorallocate($image, $bg_color[0], $bg_color[1], $bg_color[2]);
imagefill($image, 0, 0, $background_color);
if ($cell){
    // Draw cells
    for($i=0;$i<=round($total_width/$cell_size);$i++){
    	$cell_delta=round(rand(-5,5));
    	$cell_color = imagecolorallocate($image, round(rand(170,230)), round(rand(170,230)), round(rand(170,230)));
        imageline($image, $i*$cell_size+$cell_delta, 0, $i*$cell_size, $total_height, $cell_color);
    }
    for($i=0-abs($cell_delta);$i<=round($total_height/$cell_size)+abs($cell_delta);$i++){
    	$cell_delta=round(rand(-5,5));
    	$cell_color = imagecolorallocate($image, round(rand(170,230)), round(rand(170,230)), round(rand(170,230)));
        imageline($image, 0, $i*$cell_size-$cell_delta*$total_width/$total_height, $total_width, $i*$cell_size, $cell_color);
    }
}
if ($ellipse){
    // Draw ellipses
    for($i=0;$i<20;$i++){
        $ellipse_color = imagecolorallocate($image, round(rand(170,230)), round(rand(170,230)), round(rand(170,230)));
        imageellipse($image, rand(-$total_width,$total_width*2), rand(-$total_height,$total_height*2), rand($total_height,$total_width), rand($total_height,$total_width), $ellipse_color);
    }
}

// Create random colors for text symbols
$text_color_array = [];
for ($i=0;$i<10;$i++) {
        $l = $i%2>0 ? 2 : 4;
        $h = $i%2>0 ? 4 : 6;
        $r=127;
        $g=127;
        $b=127;
	$text_color_array[] = round(255*rand($l,$h)/10).','.round(127*rand($l,$h)/10).','.round(2*rand($l,$h)/10);
}

$char_width = (round(($total_width - 20) / strlen($code)) + rand(-2,2));
// Printing chars
for ($i = 0; $i < strlen($code); $i++)
{
    $char = $code{$i};
    $size = round(rand($total_height/2.3, $total_height/1.9));
    $font = rand(0, (count($fonts)-1));
    $angle = mt_rand(-25, 25);
    $char_pos = imagettfbbox($size, $angle, $fonts_path.$fonts[$font], $char);
    $letter_width = max(abs($char_pos[0]) + abs($char_pos[4]),abs($char_pos[2]) + abs($char_pos[6]));
    $letter_height = max(abs($char_pos[1]) + abs($char_pos[7]),abs($char_pos[3]) + abs($char_pos[4]));
    $letter_up = max(abs($char_pos[5]),abs($char_pos[7]));
    $letter_down = max(abs($char_pos[1]),abs($char_pos[3]));
    $x_pos = ($char_width / 4) + ($i * $char_width);
    $y_pos = rand($letter_up, $total_height-$letter_down);
    $text_color = $text_color_array[rand(0,count($text_color_array)-1)];
    $text_color = explode(",", $text_color);
    $textcolor = imagecolorallocate($image, $text_color[0], $text_color[1], $text_color[2]);
    imagettftext($image, $size, $angle, $x_pos, $y_pos, $textcolor, $fonts_path.$fonts[$font], $char);
}

if ($lines){
    // Draw lines
    for($i=0;$i<10;$i++){
        $line_color = imagecolorallocate($image, round(rand(70,170)), round(rand(70,170)), round(rand(70,170)));
        imageline($image, rand(0,$total_width), rand(0,$total_height), rand(0,$total_width), rand(0,$total_height), $line_color);
    }
}

// Display
header("Last-Modified: " . gmdate("D, d M Y H:i:s") ." GMT");
header("Pragma: no-cache");
header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");
header("Content-Type: image/png");
imagepng($image);
imagedestroy($image);
?>