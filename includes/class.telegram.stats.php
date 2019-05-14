<?php
  abstract class TelegramStats extends TelegramController{
      //private static $channel_id = -1001099909798;
      protected static $channel_id = "@bsn_ru";
      protected static $bot_id = "259398153:AAESOmjnUGi9QVMi1f59ooo5f0YRC3EktYs";
      protected static $api_url = "https://api.telegram.org/bot259398153:AAESOmjnUGi9QVMi1f59ooo5f0YRC3EktYs/";
      
      private static function getChannelInfo($channel_id = false){
          $parameters = array('chat_id' => (!empty($channel_id) ? $channel_id : self::$channel_id));
          $channel_info = self::apiRequestPost("getChat",$parameters);
          return $channel_info;
      }
      
      private static function saveChannelInfo($update){
          if(empty($update) || !is_array($update) || empty($update['result'])) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          $channels_stats = array();
          $last_update_id = array();
          foreach($update as $key=>$item){
              $last_update_id = $item['update_id'];
              if(!empty($item['channel_post'])){
                  $item = $item['channel_post'];
                  
              }
              
          }
      }
      
      /**
      * получаем update за последние x секунд
      */
      private static function getUpdate($offset = false){
          $parameters = (!empty($offset) ? array('offset' => $offset) : array());
          $update = self::apiRequestPost("getUpdates",$parameters);
          
          $channel_info = self::getChannelInfo(self::$channel_id);
          self::saveChannelInfo($update);
          file_put_contents("./telegramupdates.log",$update);
          return $update;
      }
      
      /**
      * обрабатываем update
      * 
      */
      public static function processUpdates(){
          self::getUpdate();
          return true;
      }
  }
?>
