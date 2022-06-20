#!/usr/bin/php
<?php

function sendPostData($url, $post){
  $ch = curl_init($url);
  $headers= array('Accept: application/json','Content-Type: application/json'); 
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
  $result = curl_exec($ch);
  curl_close($ch);  // Seems like good practice
  return $result;
}

$apiUserId = '44XQE5XAhZAWibY7ypsqIN68I7XaHeames2Lj7ztHZY=';
$apiUserKey = 'n7i12TbY1knmT5xTeddbM638cxgbY28cH9pMPLyjgZ8=';

$id = '5758335178c568145232170c';

$urlWithoutScheme = 'api.platformcraft.ru/1/transcoder/'.$id;
$params = array(
	'presets' => ['571e1a30702b930774ff22ea'],
    'path' => '/objects'
);

$time = time();
$message = 'POST+'.$urlWithoutScheme.'?apiuserid='.$apiUserId.'&timestamp='.$time;
$hash = hash_hmac('sha256', $message, $apiUserKey);

$url = 'https://'.$urlWithoutScheme.'?apiuserid='.$apiUserId.'&timestamp='.$time.'&hash='.$hash;
$str_data = json_encode($params);

echo sendPostData($url, $str_data)."\n";

?>